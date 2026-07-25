<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\StoreWallet;
use App\Models\UserNotificationPreference;
use App\Services\WhatsAppService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WhatsAppController extends Controller
{
    /** How many clients the bulk recipient picker will load at once. */
    const BULK_PICKER_LIMIT = 1000;

    /** Recipients accepted per bulk-send call — the browser drives the batches so a long run
     *  never hits max_execution_time and the vendor sees live progress. */
    const BULK_BATCH_LIMIT = 25;

    // Vendor "Connect WhatsApp" screen (Embedded Signup).
    public function connect(Request $request)
    {
        WhatsAppService::ensureStoreColumns();
        $config = Helpers::get_business_settings('whatsapp_config');
        $storeId = Helpers::get_store_id();
        $store = DB::table('stores')->where('id', $storeId)
            ->select('wa_enabled', 'wa_phone_number_id', 'wa_business_account_id', 'phone')
            ->first();

        $es = [
            'app_id'      => $config['es_app_id'] ?? null,
            'config_id'   => $config['es_config_id'] ?? null,
            'api_version' => $config['api_version'] ?? 'v21.0',
            'ready'       => !empty($config['es_app_id']) && !empty($config['es_config_id']),
        ];

        $connected = (bool) ($store && $store->wa_enabled && $store->wa_phone_number_id);

        // Bulk sending is only offered on the vendor's own connected number — Meta bills them
        // directly and a marketing blast must never burn the platform number's quality rating.
        $templates = [];
        $templateError = null;
        $clientCount = 0;
        $platformUserCount = 0;
        $nearbyUserCount = 0;
        $optOutCount = 0;
        if ($connected) {
            $res = WhatsAppService::make($storeId)->listTemplates();
            $templates = $this->bulkTemplateOptions($res['data']);
            if (!$res['success']) {
                $templateError = $res['error'];
            }
            $clientCount = $this->clientQuery($storeId)->count();
            $platformUserCount = $this->platformUserQuery($storeId)->count();
            $nearbyUserCount = $this->nearbyUserQuery($storeId)->count();
            $optOutCount = count(WhatsAppService::optedOutPhones($storeId));
        }

        return view('vendor-views.whatsapp.connect', compact(
            'es', 'store', 'connected', 'templates', 'templateError',
            'clientCount', 'platformUserCount', 'nearbyUserCount', 'optOutCount'
        ));
    }

    /**
     * WhatsApp activity dashboard for the vendor.
     *
     * "Involving this store" is two things: campaigns the vendor sent from their own connected
     * number (whatsapp_messages.store_id = this store) and alerts MyChitti sent TO the store
     * (platform-sent, so store_id is NULL but recipient matches the store phone). Both are
     * surfaced because a vendor with no bulk activity yet still receives lead notifications,
     * and an all-zero dashboard would read as broken.
     */
    public function dashboard(Request $request)
    {
        WhatsAppService::ensureMessagesTable();
        $storeId = Helpers::get_store_id();
        $store = DB::table('stores')->where('id', $storeId)
            ->select('id', 'name', 'phone', 'wa_enabled', 'wa_phone_number_id')
            ->first();

        $connected = (bool) ($store && $store->wa_enabled && $store->wa_phone_number_id);
        $phone10 = substr(preg_replace('/[^0-9]/', '', (string) ($store->phone ?? '')) ?? '', -10);

        // One filter reused across every aggregate below.
        $scope = function ($q) use ($storeId, $phone10) {
            $q->where('whatsapp_messages.store_id', $storeId);
            if (strlen($phone10) === 10) {
                $q->orWhereRaw("RIGHT(REPLACE(REPLACE(REPLACE(recipient, ' ', ''), '-', ''), '+', ''), 10) = ?", [$phone10]);
            }
        };

        // Delivery funnel. Meta's webhook advances status accepted → sent → delivered → read
        // (or failed), keyed on wamid, so these update regardless of store_id attribution.
        $statusCounts = DB::table('whatsapp_messages')->where($scope)
            ->where('direction', 'out')
            ->select('status', DB::raw('count(*) c'))->groupBy('status')
            ->pluck('c', 'status');

        // A read message is also delivered; read isn't surfaced on its own because WhatsApp
        // only reports it when the recipient has read receipts on and the webhook receives it.
        $delivered = ($statusCounts['delivered'] ?? 0) + ($statusCounts['read'] ?? 0);
        $failed    = $statusCounts['failed'] ?? 0;
        $total     = (int) $statusCounts->sum();

        $stats = [
            'total'         => $total,
            'delivered'     => $delivered,
            'failed'        => $failed,
            'delivery_rate' => $total > 0 ? round((($total - $failed) / $total) * 100) : 0,
        ];

        // Daily volume, last 14 days — one grouped query, zero-filled in PHP so gaps show as 0.
        $since = now()->subDays(13)->startOfDay();
        $daily = DB::table('whatsapp_messages')->where($scope)
            ->where('direction', 'out')
            ->where('sent_at', '>=', $since)
            ->select(DB::raw('DATE(sent_at) d'), DB::raw('count(*) c'))
            ->groupBy('d')->pluck('c', 'd');

        $days = [];
        $counts = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $days[] = now()->subDays($i)->format('d M');
            $counts[] = (int) ($daily[$day] ?? 0);
        }

        // What the traffic is: lead alerts vs campaigns vs tests.
        $contextLabels = [
            'lead notify'    => 'Lead alerts',
            'lead accepted'  => 'Lead accepted alerts',
            'welcome'        => 'Customer welcome messages',
            'chat reply'     => 'Chat replies',
            'auto reply'     => 'AI auto-replies',
            'inbound'        => 'Customer messages (received)',
            'bulk'           => 'Bulk campaigns',
            'nearby'         => 'Nearby-offer campaigns',
            'test message'   => 'Test messages',
        ];
        $byContext = DB::table('whatsapp_messages')->where($scope)
            ->where('direction', 'out')
            ->select('context', DB::raw('count(*) c'))->groupBy('context')
            ->pluck('c', 'context');

        $contextRows = [];
        foreach ($byContext as $ctx => $c) {
            // Appointment reminders carry a per-appointment context ("appt reminder:{id}:{mode}")
            // for dedupe — fold them into one row here.
            $label = $contextLabels[$ctx]
                ?? (str_starts_with((string) $ctx, 'appt reminder') ? 'Appointment reminders' : ucfirst($ctx ?: 'Other'));
            $contextRows[$label] = ($contextRows[$label] ?? 0) + $c;
        }
        arsort($contextRows);

        $recent = DB::table('whatsapp_messages')->where($scope)
            ->where('direction', 'out')
            ->orderByDesc('sent_at')->limit(15)
            ->get(['recipient', 'type', 'body', 'context', 'status', 'error', 'sent_at']);

        // The vendor's own customer book — the audience for bulk sends, and what the Excel
        // import below fills.
        $customerStats = [
            'total'       => DB::table('store_customers')->where('store_id', $storeId)->count(),
            'with_phone'  => DB::table('store_customers')->where('store_id', $storeId)
                ->whereNotNull('phone')->where('phone', '!=', '')->count(),
        ];
        $recentCustomers = DB::table('store_customers')->where('store_id', $storeId)
            ->orderByDesc('id')->limit(8)->get(['f_name', 'phone']);

        $chart = [
            'days'          => $days,
            'counts'        => $counts,
            'status'        => [
                'sent'      => (int) (($statusCounts['sent'] ?? 0) + ($statusCounts['accepted'] ?? 0)),
                // "read" folds into delivered — WhatsApp only reports read when the recipient
                // has read receipts on and the webhook receives it, so it is not shown on its own.
                'delivered' => $delivered,
                'failed'    => (int) $failed,
            ],
        ];

        return view('vendor-views.whatsapp.dashboard', compact(
            'store', 'connected', 'stats', 'chart', 'contextRows', 'recent',
            'customerStats', 'recentCustomers'
        ));
    }

    // WhatsApp-like inbox: two-way chat on the vendor's own connected number.
    public function inbox(Request $request)
    {
        WhatsAppService::ensureMessagesTable();
        WhatsAppService::ensureStoreColumns();
        $storeId = Helpers::get_store_id();
        $wa = WhatsAppService::make($storeId);
        $connected = $wa->source() === 'vendor';

        // Self-heal: without this subscription Meta never forwards the customer's messages
        // to our webhook, and the inbox stays silently empty. Idempotent, so safe per load.
        $subscribeError = null;
        if ($connected) {
            $sub = $wa->ensureWebhookSubscription();
            if (!$sub['success']) {
                $subscribeError = $sub['error'];
            }
        }

        $storeName = DB::table('stores')->where('id', $storeId)->value('name') ?: '';

        return view('vendor-views.whatsapp.inbox', compact('connected', 'subscribeError', 'storeName'));
    }

    /** Active staff (with a phone on file) for the "forward to staff" picker. */
    public function inboxStaff(Request $request)
    {
        $storeId = Helpers::get_store_id();

        // Resigned staff have their phone nulled, so the phone filter already excludes them.
        $staff = \App\Models\VendorEmployee::where('store_id', $storeId)
            ->whereNotNull('phone')->where('phone', '!=', '')
            ->orderBy('f_name')
            ->get(['id', 'f_name', 'l_name', 'phone'])
            ->map(fn($e) => [
                'id'   => $e->id,
                'name' => trim($e->f_name . ' ' . $e->l_name),
            ]);

        return response()->json(['success' => true, 'staff' => $staff]);
    }

    /**
     * Forward an inbox message (with the customer's details) to a staff member's WhatsApp,
     * sent from the store's own connected number.
     *
     * Prefers the approved `staff_forward` template so it delivers any time; the template's
     * fixed text keeps the layout and {{4}} carries the message (its own line breaks collapse
     * to spaces, per Meta). Falls back to free text — which only lands if the staff member
     * messaged the store number in the last 24h — when the template isn't approved yet.
     */
    public function inboxForward(Request $request)
    {
        $request->validate([
            'staff_id'     => 'required|integer',
            'sender_name'  => 'nullable|string|max:200',
            'sender_phone' => 'nullable|string|max:40',
            'message'      => 'required|string|max:4000',
        ]);

        $storeId = Helpers::get_store_id();
        $wa = WhatsAppService::make($storeId);
        if ($wa->source() !== 'vendor') {
            return response()->json(['success' => false, 'error' => 'Connect your own WhatsApp number to forward chats.'], 422);
        }

        // Phone comes from the staff record, never the client — resigned staff (phone nulled) are excluded.
        $staff = \App\Models\VendorEmployee::where('store_id', $storeId)
            ->whereNotNull('phone')->where('phone', '!=', '')
            ->find($request->staff_id);
        if (!$staff) {
            return response()->json(['success' => false, 'error' => 'That staff member has no phone number on file.'], 422);
        }

        $storeName   = DB::table('stores')->where('id', $storeId)->value('name') ?: 'our store';
        $senderName  = trim((string) $request->sender_name) ?: 'Customer';
        $senderPhone = trim((string) $request->sender_phone) ?: '—';
        $message     = trim((string) $request->message);

        WhatsAppService::ensurePresetsTable();
        $lang = DB::table('wa_template_presets')->where('name', 'staff_forward')->value('language') ?: 'en_US';

        // Body vars: {{1}} store, {{2}} sender name, {{3}} phone, {{4}} message.
        $params = array_map(fn($v) => $this->sanitizeParam((string) $v), [$storeName, $senderName, $senderPhone, $message]);
        $components = [[
            'type'       => 'body',
            'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => $v], $params),
        ]];

        $res  = $wa->sendTemplate($staff->phone, 'staff_forward', $lang, $components, 'forward to staff');
        $sent = !empty($res['success']);

        // Free-text fallback mirrors the template and keeps the message's own line breaks.
        if (!$sent) {
            $text = "📩 New message forwarded from {$storeName}.\n\n"
                . "From: {$senderName} ({$senderPhone})\n"
                . "Message: {$message}\n\n"
                . "Please follow up with the customer.";
            $fallback = $wa->sendText($staff->phone, $text, false, 'forward to staff');
            if (empty($fallback['success'])) {
                return response()->json([
                    'success' => false,
                    'error'   => $res['error'] ?: ($fallback['error'] ?? 'Could not forward the message.'),
                ]);
            }
        }

        return response()->json(['success' => true, 'staff' => $staff->name]);
    }

    /** Conversation list: one row per contact, newest activity first. */
    public function inboxThreads(Request $request)
    {
        $storeId = Helpers::get_store_id();

        $rows = DB::table('whatsapp_messages')
            ->where('store_id', $storeId)
            ->whereNotNull('recipient')
            ->where('recipient', '!=', '')
            ->orderByDesc('sent_at')
            ->limit(2000)
            ->get(['recipient', 'direction', 'body', 'type', 'sent_at']);

        // Newest-first walk: the first row seen per contact IS the thread's latest message.
        $threads = [];
        foreach ($rows as $m) {
            $key = substr(preg_replace('/[^0-9]/', '', (string) $m->recipient) ?? '', -10);
            if (strlen($key) < 10) {
                continue;
            }
            if (!isset($threads[$key])) {
                $threads[$key] = [
                    'key'       => $key,
                    'phone'     => $m->recipient,
                    'name'      => null,
                    'last_body' => mb_substr((string) $m->body, 0, 80),
                    'last_dir'  => $m->direction,
                    'last_at'   => $m->sent_at,
                ];
            }
        }

        // Contact names from the store's own customer book, matched on the last 10 digits.
        if ($threads) {
            foreach (DB::table('store_customers')->where('store_id', $storeId)
                ->whereNotNull('phone')->where('phone', '!=', '')
                ->get(['f_name', 'phone']) as $c) {
                $ckey = substr(preg_replace('/[^0-9]/', '', (string) $c->phone) ?? '', -10);
                if (isset($threads[$ckey]) && $threads[$ckey]['name'] === null) {
                    $threads[$ckey]['name'] = $c->f_name;
                }
            }
        }

        return response()->json(['success' => true, 'threads' => array_values($threads)]);
    }

    /** Full message history with one contact + whether the 24h free-text window is open. */
    public function inboxThread(Request $request)
    {
        $request->validate(['phone' => 'required|digits:10']);
        $storeId = Helpers::get_store_id();
        $key = $request->phone;

        $messages = DB::table('whatsapp_messages')
            ->where('store_id', $storeId)
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(recipient, ' ', ''), '-', ''), '+', ''), 10) = ?", [$key])
            ->orderByDesc('sent_at')
            ->limit(300)
            ->get(['id', 'direction', 'type', 'body', 'context', 'status', 'error', 'sent_at'])
            ->reverse()
            ->values();

        // Free-form text only delivers within 24h of the customer's last inbound message;
        // outside that, Meta requires an approved template.
        $lastInbound = $messages->where('direction', 'in')->last();
        $windowOpen = $lastInbound && Carbon::parse($lastInbound->sent_at)->gt(now()->subHours(24));

        return response()->json([
            'success'     => true,
            'messages'    => $messages,
            'window_open' => (bool) $windowOpen,
        ]);
    }

    /** Send a manual reply (free text) from the vendor's own number. */
    public function inboxSend(Request $request)
    {
        $request->validate([
            'phone'   => 'required|digits:10',
            'message' => 'required|string|max:4000',
        ]);

        $storeId = Helpers::get_store_id();
        $wa = WhatsAppService::make($storeId);
        if ($wa->source() !== 'vendor') {
            return response()->json(['success' => false, 'error' => 'Connect your own WhatsApp number to reply to chats.'], 422);
        }

        $res = $wa->sendText($request->phone, trim((string) $request->message), false, 'chat reply');

        return response()->json([
            'success' => (bool) $res['success'],
            'error'   => $res['error'] ?? null,
        ]);
    }

    /** Import the vendor's customers into store_customers from an uploaded Excel/CSV sheet. */
    public function importCustomers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ], [
            'file.mimes' => 'Upload an Excel (.xlsx, .xls) or CSV file.',
            'file.max'   => 'The file must be 5 MB or smaller.',
        ]);

        $storeId = Helpers::get_store_id();
        $import = new \App\Imports\StoreCustomerImport($storeId);

        // Per-row synchronous welcomes would stall a big upload — suppress the model hook
        // and queue the batch below instead when the vendor opted in.
        \App\Models\StoreCustomer::$welcomeOnCreate = false;
        try {
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            Log::error('Store customer import failed: ' . $e->getMessage());
            Toastr::error('Could not read that file. Make sure it has columns: Name, Phone, Email, GST, Address.');
            return back();
        } finally {
            \App\Models\StoreCustomer::$welcomeOnCreate = true;
        }

        $msg = "Imported {$import->imported} customer(s).";
        if ($import->duplicate) {
            $msg .= " {$import->duplicate} already existed.";
        }
        if ($import->skipped) {
            $msg .= " {$import->skipped} row(s) skipped (missing name or phone).";
        }

        if ($request->boolean('send_welcome') && !empty($import->welcomeRecipients)) {
            foreach (array_chunk($import->welcomeRecipients, 50) as $chunk) {
                \App\Jobs\SendWelcomeMessages::dispatch($storeId, $chunk);
            }
            $msg .= ' WhatsApp welcome messages are being sent in the background to ' . count($import->welcomeRecipients) . ' new customer(s).';
        }

        $import->imported > 0 ? Toastr::success($msg) : Toastr::warning($msg);
        return back();
    }

    /** A ready-to-fill sample sheet so the vendor uses the right columns. */
    public function customerTemplate()
    {
        $headers = ['Name', 'Phone', 'Email', 'GST', 'Address'];
        $sample  = ['Ramesh Kumar', '9876543210', 'ramesh@example.com', '', 'MG Road, Tirupati'];

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);
        fputcsv($csv, $sample);
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customers-template.csv"',
        ]);
    }

    /** Send a test WhatsApp from the MyChitti platform number to a chosen or the registered number. */
    public function sendTestMessage(Request $request)
    {
        $outcome = WhatsAppService::sendTestMessage(Helpers::get_store_id(), $request->input('test_phone'));

        if (!empty($outcome['success'])) {
            Toastr::success($outcome['message'] . ' It can take a few seconds to arrive.');
        } else {
            Toastr::error($outcome['message']);
        }

        return back();
    }

    /** Clients of this store that are actually reachable on WhatsApp. */
    private function clientQuery(int $storeId)
    {
        return $this->excludeOptedOut(
            DB::table('store_customers')
                ->where('store_id', $storeId)
                ->whereNotNull('phone')
                ->where('phone', '!=', ''),
            $storeId
        );
    }

    /** Messages one person may receive from the shared nearby-offers pool per 30 days. */
    const NEARBY_MONTHLY_CAP = 4;

    /**
     * Customers who opted in to hearing from nearby businesses they have no relationship with.
     *
     * Distinct from clientQuery(): these people consented to the shared pool, so any vendor in
     * their zone may reach them. The frequency cap is what keeps the pool alive — without it
     * the first vendors to find it each blast the same few hundred people, everyone opts out,
     * and the pool is dead in a month.
     */
    private function nearbyUserQuery(int $storeId)
    {
        $zoneId = DB::table('stores')->where('id', $storeId)->value('zone_id');
        $zoneIds = Helpers::zone_with_descendants($zoneId);

        if (empty($zoneIds)) {
            return DB::table('users')->whereRaw('1 = 0');
        }

        // ensureTable() rather than a bare hasTable() check: on an environment where the table
        // was created by an earlier deploy it exists but has no nearby_offers column, and the
        // query below would fail with "Unknown column 'p.nearby_offers'". ensureTable() is
        // idempotent and adds the column when it is missing.
        try {
            UserNotificationPreference::ensureTable();
            $ready = Schema::hasColumn('user_notification_prefs', 'nearby_offers');
        } catch (\Throwable $e) {
            Log::warning('nearby pool unavailable: ' . $e->getMessage());
            $ready = false;
        }

        if (!$ready) {
            return DB::table('users')->whereRaw('1 = 0');
        }

        $query = DB::table('users')
            ->join('user_notification_prefs as p', 'p.user_id', '=', 'users.id')
            ->where('p.nearby_offers', 1)
            // A pool opt-in never overrides turning WhatsApp off entirely.
            ->where('p.whatsapp', 1)
            ->whereIn('users.zone_id', $zoneIds)
            ->whereNotNull('users.phone')
            ->where('users.phone', '!=', '')
            ->select('users.*');

        $capped = $this->nearbyCappedPhones();
        if (!empty($capped)) {
            $query->whereNotIn(
                DB::raw("RIGHT(REPLACE(REPLACE(REPLACE(`users`.`phone`, ' ', ''), '-', ''), '+', ''), 10)"),
                $capped
            );
        }

        return $this->excludeOptedOut($query, $storeId);
    }

    /** Last-10-digit forms of numbers that already hit the pool's 30-day cap. */
    private function nearbyCappedPhones(): array
    {
        try {
            $phones = DB::table('whatsapp_messages')
                ->where('context', 'nearby')
                ->where('sent_at', '>=', now()->subDays(30))
                ->whereNotNull('recipient')
                ->groupBy('recipient')
                ->havingRaw('count(*) >= ?', [self::NEARBY_MONTHLY_CAP])
                ->pluck('recipient');
        } catch (\Throwable $e) {
            return [];
        }

        return array_values(array_unique(array_filter($phones
            ->map(fn($p) => substr(preg_replace('/[^0-9]/', '', (string) $p) ?? '', -10))
            ->all())));
    }

    /**
     * Drop anyone who has opted out of marketing from this store (or platform-wide).
     * Matched on the last 10 digits because stored numbers vary between "+91 98…",
     * "098…" and "98…" — the opt-out table holds the normalized form.
     */
    private function excludeOptedOut($query, int $storeId)
    {
        $suffixes = array_values(array_unique(array_filter(array_map(
            fn($p) => substr(preg_replace('/[^0-9]/', '', (string) $p) ?? '', -10),
            WhatsAppService::optedOutPhones($storeId)
        ))));

        if (empty($suffixes)) {
            return $query;
        }

        return $query->whereNotIn(
            DB::raw("RIGHT(REPLACE(REPLACE(REPLACE(`phone`, ' ', ''), '-', ''), '+', ''), 10)"),
            $suffixes
        );
    }

    /**
     * MyChitti users in this store's OWN zone who are reachable on WhatsApp.
     *
     * Matches users.zone_id, which is populated at registration, on address add/update and on
     * service-request creation, and was backfilled for existing accounts.
     *
     * Matched against the store's zone AND every zone inside it, because zones nest: a store
     * registered in "India" or "Andhra Pradesh" must reach the Tirupati customers within it,
     * not just the few tagged with that exact broad zone. A store with no zone gets nobody
     * rather than everybody.
     */
    private function platformUserQuery(int $storeId)
    {
        $zoneId = DB::table('stores')->where('id', $storeId)->value('zone_id');
        $zoneIds = Helpers::zone_with_descendants($zoneId);

        if (empty($zoneIds)) {
            return DB::table('users')->whereRaw('1 = 0');
        }

        return $this->excludeOptedOut(
            DB::table('users')
                ->whereIn('zone_id', $zoneIds)
                ->whereNotNull('phone')
                ->where('phone', '!=', ''),
            $storeId
        );
    }

    /**
     * Reduce Meta's template payload to what the bulk composer needs.
     * Only APPROVED templates can be sent, and only BODY variables are supported — a template
     * with a variable header or a dynamic URL button needs parameters this UI doesn't collect,
     * so it is listed as unsupported instead of failing at send time.
     */
    private function bulkTemplateOptions(array $data): array
    {
        $out = [];
        foreach ($data as $tpl) {
            if (strtoupper((string) data_get($tpl, 'status')) !== 'APPROVED') {
                continue;
            }

            $body = '';
            $unsupported = null;
            foreach ((array) data_get($tpl, 'components', []) as $c) {
                $type = strtoupper((string) data_get($c, 'type'));
                if ($type === 'BODY') {
                    $body = (string) data_get($c, 'text', '');
                } elseif ($type === 'HEADER' && str_contains((string) data_get($c, 'text', ''), '{{')) {
                    $unsupported = 'has a variable in its header';
                } elseif ($type === 'BUTTONS') {
                    foreach ((array) data_get($c, 'buttons', []) as $b) {
                        if (str_contains((string) data_get($b, 'url', ''), '{{')) {
                            $unsupported = 'has a dynamic button URL';
                        }
                    }
                }
            }

            preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $body, $m);
            $varCount = $m[1] ? max(array_map('intval', $m[1])) : 0;

            $out[] = [
                'name'        => data_get($tpl, 'name'),
                'language'    => data_get($tpl, 'language', 'en_US'),
                'category'    => data_get($tpl, 'category'),
                'body'        => $body,
                'var_count'   => $varCount,
                'unsupported' => $unsupported,
            ];
        }

        usort($out, fn($a, $b) => strcmp((string) $a['name'], (string) $b['name']));
        return $out;
    }

    /** Client list for the bulk composer's recipient picker. */
    public function bulkRecipients(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $query = $this->clientQuery($storeId);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('f_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");
            });
        }
        if ($type = $request->input('user_type')) {
            $query->where('user_type', $type);
        }

        $total = (clone $query)->count();
        $clients = $query->orderBy('f_name')
            ->limit(self::BULK_PICKER_LIMIT)
            ->get(['id', 'f_name', 'phone', 'user_type']);

        return response()->json([
            'success'   => true,
            'total'     => $total,
            'truncated' => $total > self::BULK_PICKER_LIMIT,
            'clients'   => $clients,
        ]);
    }

    /**
     * Send one approved template to a batch of clients from the vendor's own number.
     * Returns a per-recipient result so the composer can show exactly what failed and why.
     */
    public function bulkSend(Request $request)
    {
        $request->validate([
            'template'     => 'required|string',
            'language'     => 'required|string',
            'mode'         => 'required|in:clients,platform,nearby',
            'client_ids'   => 'required_if:mode,clients|array|max:' . self::BULK_BATCH_LIMIT,
            'client_ids.*' => 'integer',
            'offset'       => 'required_if:mode,platform,nearby|integer|min:0',
            'limit'        => 'required_if:mode,platform,nearby|integer|min:1|max:' . self::BULK_BATCH_LIMIT,
            'params'       => 'nullable|array',
        ]);

        $storeId = Helpers::get_store_id();
        $wa = WhatsAppService::make($storeId);

        if ($wa->source() !== 'vendor') {
            return response()->json([
                'success' => false,
                'message' => 'Connect your own WhatsApp number before sending bulk messages.',
            ], 422);
        }

        $mode = $request->input('mode');
        $platform = in_array($mode, ['platform', 'nearby'], true);

        if ($platform) {
            // Ordered by id so the browser's offset walk covers each user exactly once.
            $recipients = ($mode === 'nearby' ? $this->nearbyUserQuery($storeId) : $this->platformUserQuery($storeId))
                ->orderBy('users.id')
                ->offset((int) $request->input('offset'))
                ->limit((int) $request->input('limit'))
                // Qualified: the nearby query joins user_notification_prefs, so a bare `id`
                // or `phone` would be ambiguous.
                ->get(['users.id', 'users.f_name', 'users.l_name', 'users.phone'])
                ->map(fn($u) => (object) [
                    'id'    => $u->id,
                    'name'  => trim($u->f_name . ' ' . $u->l_name),
                    'phone' => $u->phone,
                ]);
        } else {
            $recipients = $this->clientQuery($storeId)
                ->whereIn('id', $request->input('client_ids'))
                ->get(['id', 'f_name', 'phone'])
                ->map(fn($c) => (object) [
                    'id'    => $c->id,
                    'name'  => trim((string) $c->f_name),
                    'phone' => $c->phone,
                ]);
        }

        $rawParams = array_values((array) $request->input('params', []));
        $results = [];

        foreach ($recipients as $client) {
            $name = trim((string) $client->name) ?: 'Customer';

            // {name} is substituted per recipient; everything else is the literal text the
            // vendor typed. Meta rejects newlines and runs of 4+ spaces inside a parameter.
            $params = array_map(
                fn($v) => $this->sanitizeParam(str_replace('{name}', $name, (string) $v)),
                $rawParams
            );

            $components = $params
                ? [['type' => 'body', 'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => $v], $params)]]
                : [];

            // Context 'nearby' is what nearbyCappedPhones() counts — it must stay distinct
            // from ordinary bulk sends or the frequency cap silently stops working.
            $res = $wa->sendTemplate(
                $client->phone,
                $request->template,
                $request->language,
                $components,
                $mode === 'nearby' ? 'nearby' : 'bulk'
            );

            $results[] = [
                'id'      => $client->id,
                'name'    => $name,
                // Platform users are not the vendor's contacts — report the outcome without
                // handing their full number back to the sender.
                'phone'   => $platform ? $this->maskPhone($client->phone) : $client->phone,
                'success' => (bool) $res['success'],
                'error'   => $res['error'] ?? null,
            ];
        }

        return response()->json([
            'success' => true,
            'sent'    => count(array_filter($results, fn($r) => $r['success'])),
            'failed'  => count(array_filter($results, fn($r) => !$r['success'])),
            'results' => $results,
        ]);
    }

    /**
     * Meta rejects a template body whose first or last character is a variable
     * ("Leading or trailing params not allowed", error_subcode 2388299). Catch it here so the
     * vendor gets a fixable message instead of a raw OAuthException from Graph.
     */
    private function templateBodyError(string $body): ?string
    {
        $body = trim($body);
        if ($body === '') {
            return null;
        }
        if (preg_match('/^\{\{\s*\d+\s*\}\}/', $body)) {
            return 'The message can’t start with a variable. Put some text before it — e.g. "Hi {{1}}" instead of "{{1}}".';
        }
        if (preg_match('/\{\{\s*\d+\s*\}\}$/', $body)) {
            return 'The message can’t end with a variable. Add some text after it — e.g. "{{2}}. See you then!" instead of ending on "{{2}}".';
        }
        return null;
    }

    private function maskPhone(?string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone) ?? '';
        return strlen($digits) < 4 ? '••••' : '••••••' . substr($digits, -4);
    }

    private function sanitizeParam(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return trim(mb_substr($value, 0, 900));
    }

    // Subscribe / renew a receiving add-on — debits the vendor wallet for one month.
    public function featureSubscribe(Request $request)
    {
        $request->validate(['feature' => 'required']);
        $feature = $request->feature;
        $meta = WhatsAppService::RECEIVING_FEATURES[$feature] ?? null;
        if (!$meta) {
            Toastr::error('Unknown feature.');
            return back();
        }

        $storeId  = Helpers::get_store_id();
        $vendorId = auth('vendor')->id();
        $price    = (float) $meta['price'];

        $wallet  = StoreWallet::where('vendor_id', $vendorId)->first();
        $balance = $wallet ? $wallet->total_earning : 0;
        if (!$wallet || $balance < $price) {
            Toastr::error('Insufficient wallet balance. Required: ' . _price($price));
            return back();
        }

        // Balance lives in total_earning — deduct from it directly.
        $wallet->decrement('total_earning', $price);

        $txn = new AccountTransaction();
        $txn->current_balance = $wallet->total_earning;
        $txn->from_type  = 'store';
        $txn->amount     = $price;
        $txn->from_id    = $vendorId;
        $txn->method     = 'wallet';
        $txn->action     = 'debit';
        $txn->reason     = 'WhatsApp Receiving — ' . $meta['label'];
        $txn->created_by = 'store';
        $txn->save();

        WhatsAppService::ensureReceivingTable();
        $row  = DB::table('wa_receiving_features')->where('store_id', $storeId)->where('feature', $feature)->first();
        $base = ($row && $row->active_until && $row->active_until >= now()->toDateString())
            ? Carbon::parse($row->active_until) : now();
        $activeUntil = $base->copy()->addMonth()->toDateString();

        DB::table('wa_receiving_features')->updateOrInsert(
            ['store_id' => $storeId, 'feature' => $feature],
            [
                'enabled'      => 1,
                'price'        => $price,
                'active_until' => $activeUntil,
                'updated_at'   => now(),
                'created_at'   => $row->created_at ?? now(),
            ]
        );

        Toastr::success($meta['label'] . ' active until ' . $activeUntil . '.');
        return back();
    }

    // Pause / resume a receiving add-on within its paid period (no charge).
    public function featureToggle(Request $request)
    {
        $request->validate(['feature' => 'required']);
        $feature = $request->feature;
        if (!isset(WhatsAppService::RECEIVING_FEATURES[$feature])) {
            Toastr::error('Unknown feature.');
            return back();
        }

        $storeId = Helpers::get_store_id();
        WhatsAppService::ensureReceivingTable();
        $row = DB::table('wa_receiving_features')->where('store_id', $storeId)->where('feature', $feature)->first();
        if (!$row || !$row->active_until || $row->active_until < now()->toDateString()) {
            Toastr::error('Subscribe first to enable this feature.');
            return back();
        }

        DB::table('wa_receiving_features')->where('id', $row->id)
            ->update(['enabled' => $row->enabled ? 0 : 1, 'updated_at' => now()]);

        Toastr::success($row->enabled ? 'Receiving paused.' : 'Receiving resumed.');
        return back();
    }

    // Completes Embedded Signup: exchanges the auth code for a token and saves the vendor's number.
    public function finish(Request $request)
    {
        $request->validate([
            'code'            => 'required|string',
            'phone_number_id' => 'required|string',
            'waba_id'         => 'required|string',
        ]);

        WhatsAppService::ensureStoreColumns();
        $config = Helpers::get_business_settings('whatsapp_config');
        $appId     = $config['es_app_id'] ?? null;
        $appSecret = $config['es_app_secret'] ?? null;
        $version   = $config['api_version'] ?? 'v21.0';

        if (!$appId || !$appSecret) {
            return response()->json(['success' => false, 'message' => 'Embedded Signup not configured by admin.'], 422);
        }

        try {
            // 1) Exchange the short-lived code for a business access token scoped to the vendor's WABA.
            $tokenResp = Http::get("https://graph.facebook.com/{$version}/oauth/access_token", [
                'client_id'     => $appId,
                'client_secret' => $appSecret,
                'code'          => $request->code,
            ]);
            if (!$tokenResp->successful() || !data_get($tokenResp->json(), 'access_token')) {
                Log::warning('WA ES token exchange failed', ['body' => $tokenResp->json()]);
                return response()->json(['success' => false, 'message' => data_get($tokenResp->json(), 'error.message', 'Token exchange failed')], 422);
            }
            $token = data_get($tokenResp->json(), 'access_token');

            // 2) Subscribe our app to the vendor's WABA so webhooks/status flow in.
            // Without this Meta never forwards inbound messages — fail loudly, not silently.
            $sub = Http::withToken($token)->post("https://graph.facebook.com/{$version}/{$request->waba_id}/subscribed_apps");
            if (!$sub->successful()) {
                Log::warning('WA ES subscribed_apps failed', ['waba' => $request->waba_id, 'body' => $sub->json()]);
            }

            // 3) Register the phone number for Cloud API (idempotent; ignore "already registered").
            Http::withToken($token)->post("https://graph.facebook.com/{$version}/{$request->phone_number_id}/register", [
                'messaging_product' => 'whatsapp',
                'pin'               => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            ]);

            // 4) Persist on the store — WhatsAppService picks this up automatically.
            DB::table('stores')->where('id', Helpers::get_store_id())->update([
                'wa_enabled'             => 1,
                'wa_phone_number_id'     => $request->phone_number_id,
                'wa_token'               => $token,
                'wa_business_account_id' => $request->waba_id,
                'wa_api_version'         => $version,
            ]);

            return response()->json(['success' => true, 'message' => 'WhatsApp connected.']);
        } catch (\Throwable $e) {
            Log::error('WA ES finish error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()], 500);
        }
    }

    // Vendor message-template management (Business Management API on the vendor's own WABA).
    public function templates(Request $request)
    {
        WhatsAppService::ensureStoreColumns();
        $storeId = Helpers::get_store_id();
        $wa = WhatsAppService::make($storeId);

        $connected = $wa->hasWaba();
        $templates = [];
        $templateError = null;
        $presets = collect();
        if ($connected) {
            $res = $wa->listTemplates();
            $templates = $res['data'];
            if (!$res['success']) {
                $templateError = $res['error'];
            }

            // Admin-suggested presets, annotated with this vendor's WABA status so a preset
            // already submitted shows its review state instead of a second "Use" button.
            $statusByName = [];
            foreach ($templates as $tpl) {
                $statusByName[strtolower((string) data_get($tpl, 'name'))] = strtoupper((string) data_get($tpl, 'status'));
            }
            $presets = WhatsAppService::templatePresets()->map(function ($p) use ($statusByName) {
                $p->waba_status = $statusByName[strtolower($p->name)] ?? null;
                return $p;
            });
        }

        // Hours before the appointment that the reminder goes out; 0 = off, unset = 2 (default).
        $apptRaw = DB::table('stores')->where('id', $storeId)->value('wa_appt_reminder');
        $apptReminder = ($apptRaw === null || $apptRaw === '') ? WhatsAppService::DEFAULT_APPT_REMINDER_HOURS : (int) $apptRaw;

        return view('vendor-views.whatsapp.templates', compact('connected', 'templates', 'templateError', 'presets', 'apptReminder'));
    }

    // Vendor picks how many hours before the appointment the reminder goes out (0 = off).
    public function reminderSchedule(Request $request)
    {
        $request->validate([
            'hours' => 'required|integer|min:0|max:168',
        ], [
            'hours.max' => 'Reminders can be at most 168 hours (7 days) before the appointment.',
        ]);

        $hours = (int) $request->hours;
        WhatsAppService::ensureStoreColumns();
        // '0' is stored (not NULL): NULL means "never chose" and gets the 2-hour default,
        // while an explicit 0 means the vendor turned reminders off.
        DB::table('stores')->where('id', Helpers::get_store_id())->update([
            'wa_appt_reminder' => (string) $hours,
        ]);

        $hours > 0
            ? Toastr::success("Appointment reminders will be sent about {$hours} hour(s) before each appointment, once your appointment_reminder template is approved.")
            : Toastr::success('Appointment reminders turned off.');
        return back();
    }

    // Vendor picked an admin preset — submit it to THEIR OWN WABA for Meta review.
    public function templateFromPreset(Request $request)
    {
        $request->validate(['preset_id' => 'required|integer']);

        $wa = WhatsAppService::make(Helpers::get_store_id());
        if (!$wa->hasWaba()) {
            Toastr::error('Connect your WhatsApp number first.');
            return back();
        }

        WhatsAppService::ensurePresetsTable();
        $preset = DB::table('wa_template_presets')
            ->where('id', $request->preset_id)->where('active', 1)->first();
        if (!$preset) {
            Toastr::error('This suggested template is no longer available.');
            return back();
        }

        $example = array_values(array_filter(array_map('trim', explode('|', (string) $preset->example)), fn($v) => $v !== ''));
        $buttons = [];
        if ($preset->btn_text && $preset->btn_url) {
            $buttons[] = ['text' => $preset->btn_text, 'url' => $preset->btn_url];
        }

        $res = $wa->createTemplate(
            $preset->name,
            $preset->category,
            $preset->language ?: 'en_US',
            $preset->body,
            $example,
            $buttons,
            $preset->header,
            $preset->footer
        );

        if ($res['success']) {
            Toastr::success('"' . $preset->title . '" submitted to Meta for review on your WhatsApp account. Approval usually takes a few minutes but can take up to 24 hours — you can send with it once its status shows APPROVED.');
        } else {
            Toastr::error('Submit failed: ' . $res['error']);
        }

        $waba = DB::table('stores')->where('id', Helpers::get_store_id())->value('wa_business_account_id') ?: '{WABA_ID}';
        return back()->with('wa_create_result', [
            'success'  => $res['success'],
            'endpoint' => 'POST /' . $waba . '/message_templates',
            'id'       => $res['id'] ?? null,
            'error'    => $res['error'] ?? null,
            'response' => $res['response'] ?? null,
        ]);
    }

    public function templateCreate(Request $request)
    {
        $request->validate([
            'tpl_name'     => 'required|regex:/^[a-z0-9_]+$/',
            'tpl_category' => 'required',
            'tpl_lang'     => 'required',
            'tpl_body'     => 'required',
        ], [
            'tpl_name.regex' => 'Template name must be lowercase letters, numbers and underscores only.',
        ]);

        $wa = WhatsAppService::make(Helpers::get_store_id());
        if (!$wa->hasWaba()) {
            Toastr::error('Connect your WhatsApp number first.');
            return back();
        }

        if ($bodyError = $this->templateBodyError((string) $request->tpl_body)) {
            Toastr::error($bodyError);
            return back()->withInput();
        }

        $example = array_values(array_filter(array_map('trim', explode('|', (string) $request->tpl_example)), fn($v) => $v !== ''));
        $buttons = [];
        if ($request->filled('tpl_btn_text') && $request->filled('tpl_btn_url')) {
            $buttons[] = ['text' => trim((string) $request->tpl_btn_text), 'url' => trim((string) $request->tpl_btn_url)];
        }
        $res = $wa->createTemplate(
            trim((string) $request->tpl_name),
            $request->tpl_category,
            $request->tpl_lang ?: 'en_US',
            (string) $request->tpl_body,
            $example,
            $buttons
        );

        if ($res['success']) {
            Toastr::success('Template submitted to Meta for review. Approval usually takes a few minutes but can take up to 24 hours — you can send with it once its status shows APPROVED.');
        } else {
            Toastr::error('Create failed: ' . $res['error']);
        }

        $waba = DB::table('stores')->where('id', Helpers::get_store_id())->value('wa_business_account_id') ?: '{WABA_ID}';
        return back()->with('wa_create_result', [
            'success'  => $res['success'],
            'endpoint' => 'POST /' . $waba . '/message_templates',
            'id'       => $res['id'] ?? null,
            'error'    => $res['error'] ?? null,
            'response' => $res['response'] ?? null,
        ]);
    }

    public function templateUpdate(Request $request)
    {
        $request->validate([
            'tpl_id'   => 'required',
            'tpl_body' => 'required',
        ]);

        $wa = WhatsAppService::make(Helpers::get_store_id());
        if (!$wa->hasWaba()) {
            Toastr::error('Connect your WhatsApp number first.');
            return back();
        }

        if ($bodyError = $this->templateBodyError((string) $request->tpl_body)) {
            Toastr::error($bodyError);
            return back()->withInput();
        }

        $example = array_values(array_filter(array_map('trim', explode('|', (string) $request->tpl_example)), fn($v) => $v !== ''));
        $buttons = [];
        if ($request->filled('tpl_btn_text') && $request->filled('tpl_btn_url')) {
            $buttons[] = ['text' => trim((string) $request->tpl_btn_text), 'url' => trim((string) $request->tpl_btn_url)];
        }

        $res = $wa->updateTemplate(
            trim((string) $request->tpl_id),
            $request->tpl_category ?: null,
            (string) $request->tpl_body,
            $example,
            $buttons
        );

        if ($res['success']) {
            Toastr::success('Template re-submitted to Meta for review. Approval usually takes a few minutes but can take up to 24 hours — it stays unusable until its status shows APPROVED again.');
        } else {
            Toastr::error('Update failed: ' . $res['error']);
        }
        return back();
    }

    public function templateDelete(Request $request)
    {
        $request->validate(['name' => 'required']);
        $wa = WhatsAppService::make(Helpers::get_store_id());
        $res = $wa->deleteTemplate(trim((string) $request->name));
        if ($res['success']) {
            Toastr::success('Template deleted.');
        } else {
            Toastr::error('Delete failed: ' . $res['error']);
        }
        return back();
    }

    public function disconnect(Request $request)
    {
        WhatsAppService::ensureStoreColumns();
        DB::table('stores')->where('id', Helpers::get_store_id())->update([
            'wa_enabled'             => 0,
            'wa_phone_number_id'     => null,
            'wa_token'               => null,
            'wa_business_account_id' => null,
        ]);
        Toastr::success('WhatsApp disconnected.');
        return back();
    }
}
