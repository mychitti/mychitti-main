<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Library\Payer;
use App\Library\Payment as PaymentInfo;
use App\Library\Receiver;
use App\Models\AccountTransaction;
use App\Models\BusinessSetting;
use App\Models\StoreWallet;
use App\Models\TmpWhatsAppSetup;
use App\Models\UserNotificationPreference;
use App\Services\WhatsAppAgent;
use App\Services\WhatsAppBilling;
use App\Services\WhatsAppRecurring;
use App\Services\WhatsAppService;
use App\Traits\Payment;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    use Payment;

    /** How many clients the bulk recipient picker will load at once. */
    const BULK_PICKER_LIMIT = 1000;

    /** Recipients accepted per bulk-send call — the browser drives the batches so a long run
     *  never hits max_execution_time and the vendor sees live progress. */
    const BULK_BATCH_LIMIT = 25;

    /** TEMPORARY (testing): list PENDING/REJECTED templates in the bulk composer as well.
     *  Meta still refuses to deliver them — the send just comes back with its error. Set to
     *  false to go back to approved-only. */
    const BULK_SHOW_UNAPPROVED = true;

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

        // Onboarding is paid for before the number is linked, so the connect button is a
        // checkout until the fee lands. finish() enforces the same rule server-side.
        $setupPaid  = WhatsAppBilling::setupFeePaid($storeId);
        $setupTotal = WhatsAppBilling::withTax(WhatsAppBilling::setupFee());

        // Bulk sending is only offered on the vendor's own connected number — Meta bills them
        // directly and a marketing blast must never burn the platform number's quality rating.
        $templates = [];
        $templateError = null;
        $clientCount = 0;
        $platformUserCount = 0;
        $optOutCount = 0;
        if ($connected) {
            $res = WhatsAppService::make($storeId)->listTemplates();
            $templates = $this->bulkTemplateOptions($res['data']);
            if (!$res['success']) {
                $templateError = $res['error'];
            }
            $clientCount = $this->clientQuery($storeId)->count();
            $platformUserCount = $this->outreachCount($storeId);
            $optOutCount = count(WhatsAppService::optedOutPhones($storeId));
        }

        return view('vendor-views.whatsapp.connect', compact(
            'es', 'store', 'connected', 'templates', 'templateError',
            'clientCount', 'platformUserCount', 'optOutCount',
            'setupPaid', 'setupTotal'
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

        // The vendor's own customer book — what the Excel import below fills, and the audience
        // behind the composer's "My customers" tab.
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

    /**
     * The store's own client book — people it already has a relationship with.
     *
     * nearby_offers deliberately does not apply here: that preference is about businesses the
     * customer has NOT dealt with, and this is the one store they have. Only an explicit STOP
     * removes someone.
     */
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

    /**
     * Everyone else reachable in this store's city, as one list: MyChitti account holders plus
     * the client books of the other stores in the city (and the orphan rows whose store_id was
     * never saved, which used to be reachable by nobody).
     *
     * Presented to the vendor as a single "MyChitti users" count. Numbers are never shown for
     * this audience and results come back masked — the vendor addresses it by size, not by
     * picking people out of it.
     *
     * Deduped on the last 10 digits of the phone across BOTH sources, so an account holder who
     * also sits in another store's book counts once and is messaged once. The store's own
     * customers are subtracted so this and clientQuery() never overlap.
     */
    private function outreachQuery(int $storeId)
    {
        $ownPhones = $this->ownClientPhones($storeId);

        $users = $this->platformUserQuery($storeId)
            ->select(
                DB::raw("TRIM(CONCAT(COALESCE(`users`.`f_name`, ''), ' ', COALESCE(`users`.`l_name`, ''))) as name"),
                'users.phone as phone'
            );

        $clients = $this->otherStoreClientQuery($storeId)
            ->select('f_name as name', 'phone as phone');

        if (!empty($ownPhones)) {
            $users->whereNotIn(DB::raw($this->phone10Sql('users.phone')), $ownPhones);
            $clients->whereNotIn(DB::raw($this->phone10Sql('phone')), $ownPhones);
        }

        $phone10 = $this->phone10Sql('t.phone');

        $query = DB::query()
            ->fromSub($users->unionAll($clients), 't')
            ->selectRaw("MIN(t.name) as name, MIN(t.phone) as phone")
            ->groupByRaw($phone10);

        // Same 30-day cap the shared pool has always had. It is what stops every vendor in a
        // city blasting the same few thousand people until they all opt out.
        $capped = $this->nearbyCappedPhones();
        if (!empty($capped)) {
            $query->whereNotIn(DB::raw($phone10), $capped);
        }

        return $query;
    }

    /** How many distinct people outreachQuery() would reach. */
    private function outreachCount(int $storeId): int
    {
        return DB::query()->fromSub($this->outreachQuery($storeId), 'c')->count();
    }

    /** Client books of the OTHER stores in this city, plus rows with no store_id at all. */
    private function otherStoreClientQuery(int $storeId)
    {
        $otherStoreIds = array_values(array_diff($this->storeIdsInCity($storeId), [$storeId]));

        // Resolved to a list of store ids rather than joined to `stores`: that table has its
        // own `phone` column, which would make the opt-out filters below ambiguous.
        $query = DB::table('store_customers')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where(function ($w) use ($otherStoreIds) {
                $w->whereNull('store_id')->orWhere('store_id', 0);
                if (!empty($otherStoreIds)) {
                    $w->orWhereIn('store_id', $otherStoreIds);
                }
            });

        // Phone-matched rather than joined: store_customers has no user_id, and the only thing
        // tying a vendor's client row to a MyChitti account is the number.
        $blocked = $this->offersOptedOutPhones();
        if (!empty($blocked)) {
            $query->whereNotIn(DB::raw($this->phone10Sql('phone')), $blocked);
        }

        return $this->excludeOptedOut($query, $storeId);
    }

    /** Last-10-digit forms of the numbers already in this store's own book. */
    private function ownClientPhones(int $storeId): array
    {
        $phones = DB::table('store_customers')
            ->where('store_id', $storeId)
            ->whereNotNull('phone')
            ->pluck('phone');

        return array_values(array_unique(array_filter($phones
            ->map(fn($p) => substr(preg_replace('/[^0-9]/', '', (string) $p) ?? '', -10))
            ->all())));
    }

    /**
     * Stores counting as "this store's city" — those sharing its zone or sitting in a zone
     * nested inside it, plus stores that never set a zone at all.
     */
    private function storeIdsInCity(int $storeId): array
    {
        $zoneIds = Helpers::zone_with_descendants(
            DB::table('stores')->where('id', $storeId)->value('zone_id')
        );

        return DB::table('stores')
            ->where(fn($q) => $this->inZoneOrUnknown($q, 'zone_id', $zoneIds))
            ->pluck('id')
            ->all();
    }

    /** Stored numbers vary between "+91 98…", "098…" and "98…" — compare the last 10 digits. */
    private function phone10Sql(string $column): string
    {
        $quoted = implode('.', array_map(fn($p) => "`$p`", explode('.', $column)));
        return "RIGHT(REPLACE(REPLACE(REPLACE($quoted, ' ', ''), '-', ''), '+', ''), 10)";
    }

    /**
     * Last-10-digit forms of numbers whose MyChitti account has nearby offers turned off.
     * Only accounts with a saved preference row count — never having set one is not a refusal.
     */
    private function offersOptedOutPhones(): array
    {
        try {
            UserNotificationPreference::ensureTable();
            $phones = DB::table('users')
                ->join('user_notification_prefs as p', 'p.user_id', '=', 'users.id')
                ->where('p.nearby_offers', 0)
                ->whereNotNull('users.phone')
                ->pluck('users.phone');
        } catch (\Throwable $e) {
            Log::warning('offers opt-out lookup failed: ' . $e->getMessage());
            return [];
        }

        return array_values(array_unique(array_filter($phones
            ->map(fn($p) => substr(preg_replace('/[^0-9]/', '', (string) $p) ?? '', -10))
            ->all())));
    }

    /** Messages one person may receive from the shared outreach pool per 30 days. */
    const NEARBY_MONTHLY_CAP = 4;

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
    private function excludeOptedOut($query, int $storeId, string $phoneColumn = 'phone')
    {
        $suffixes = array_values(array_unique(array_filter(array_map(
            fn($p) => substr(preg_replace('/[^0-9]/', '', (string) $p) ?? '', -10),
            WhatsAppService::optedOutPhones($storeId)
        ))));

        if (empty($suffixes)) {
            return $query;
        }

        return $query->whereNotIn(DB::raw($this->phone10Sql($phoneColumn)), $suffixes);
    }

    /**
     * MyChitti users in this store's OWN zone who are reachable on WhatsApp.
     *
     * Matches users.zone_id, which is populated at registration, on address add/update and on
     * service-request creation, and was backfilled for existing accounts.
     *
     * Matched against the store's zone AND every zone inside it, because zones nest: a store
     * registered in "India" or "Andhra Pradesh" must reach the Tirupati customers within it,
     * not just the few tagged with that exact broad zone.
     *
     * Users whose zone was never resolved are included too — see inZoneOrUnknown().
     *
     * Gated on nearby_offers: a saved preference of 0 removes them, no preference row at all
     * does not. Feeds outreachQuery() rather than being an audience on its own.
     */
    private function platformUserQuery(int $storeId)
    {
        $zoneId = DB::table('stores')->where('id', $storeId)->value('zone_id');
        $zoneIds = Helpers::zone_with_descendants($zoneId);

        $query = DB::table('users')
            ->where(fn($q) => $this->inZoneOrUnknown($q, 'users.zone_id', $zoneIds))
            ->whereNotNull('users.phone')
            ->where('users.phone', '!=', '')
            ->select('users.*');

        // leftJoin, not a phone match: these rows have a real user_id to key on. Nearby offers
        // are on by default, so most accounts have no preference row and an inner join would
        // cut the audience to the handful who once saved their settings.
        try {
            UserNotificationPreference::ensureTable();
            $query->leftJoin('user_notification_prefs as p', 'p.user_id', '=', 'users.id')
                ->where(fn($q) => $q->whereNull('p.nearby_offers')->orWhere('p.nearby_offers', 1))
                // Staying in the pool never overrides turning WhatsApp off entirely.
                ->where(fn($q) => $q->whereNull('p.whatsapp')->orWhere('p.whatsapp', 1));
        } catch (\Throwable $e) {
            Log::warning('offers preference unavailable: ' . $e->getMessage());
        }

        return $this->excludeOptedOut($query, $storeId, 'users.phone');
    }

    /**
     * "Same city, or no city on record."
     *
     * users.zone_id is only written when the app actually learns where someone is — signup
     * with location, an address, a service request, an order. Plenty of accounts never hit
     * any of those and sit NULL (or 0, from an API path that cast a missing header), and
     * excluding them made most of the customer base unreachable. They are treated as
     * potentially local rather than definitely not.
     *
     * With no zone list at all — a store that never set its own zone — only the unknowns
     * qualify, since there is no city to match against.
     */
    private function inZoneOrUnknown($query, string $column, array $zoneIds)
    {
        $query->whereNull($column)->orWhere($column, 0);

        if (!empty($zoneIds)) {
            $query->orWhereIn($column, $zoneIds);
        }

        return $query;
    }

    /**
     * Reduce Meta's template payload to what the bulk composer needs.
     * Normally only APPROVED templates are listed (see BULK_SHOW_UNAPPROVED), and only BODY
     * variables are supported — a template with a variable header or a dynamic URL button needs
     * parameters this UI doesn't collect, so it is listed as unsupported instead of failing at
     * send time.
     */
    private function bulkTemplateOptions(array $data): array
    {
        $out = [];
        foreach ($data as $tpl) {
            $status = strtoupper((string) data_get($tpl, 'status')) ?: 'UNKNOWN';

            if (!self::BULK_SHOW_UNAPPROVED && $status !== 'APPROVED') {
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

            // Every slot the body needs, in send order. Named slots the platform knows how to
            // fill are marked auto — the composer shows them as filled-per-recipient instead of
            // asking the vendor for a value.
            $vars = [];
            foreach (WhatsAppService::namedVariables($body) as $named) {
                $known = WhatsAppService::TEMPLATE_VARIABLES[$named] ?? null;
                $vars[] = [
                    'key'   => $named,
                    'label' => $known['label'] ?? ucfirst(str_replace('_', ' ', $named)),
                    'auto'  => $known !== null,
                ];
            }
            if (empty($vars)) {
                $count = WhatsAppService::positionalCount($body);
                for ($n = 1; $n <= $count; $n++) {
                    $vars[] = ['key' => (string) $n, 'label' => 'Variable ' . $n, 'auto' => false];
                }
            } elseif (WhatsAppService::positionalCount($body) > 0) {
                // Meta will not have approved this, but a hand-edited body could still reach us.
                $unsupported = 'mixes named and numbered variables';
            }

            $out[] = [
                'name'        => data_get($tpl, 'name'),
                'language'    => data_get($tpl, 'language', 'en_US'),
                'category'    => data_get($tpl, 'category'),
                'body'        => $body,
                'vars'        => $vars,
                'var_count'   => count($vars),
                'unsupported' => $unsupported,
                'status'      => $status,
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
            'mode'         => 'required|in:clients,platform',
            'client_ids'   => 'required_if:mode,clients|array|max:' . self::BULK_BATCH_LIMIT,
            'client_ids.*' => 'integer',
            'offset'       => 'required_if:mode,platform|integer|min:0',
            'limit'        => 'required_if:mode,platform|integer|min:1|max:' . self::BULK_BATCH_LIMIT,
            'params'       => 'nullable|array',
            'params.*.key' => 'nullable|string|max:64',
        ]);

        $storeId = Helpers::get_store_id();
        $wa = WhatsAppService::make($storeId);

        if ($wa->source() !== 'vendor') {
            return response()->json([
                'success' => false,
                'message' => 'Connect your own WhatsApp number before sending bulk messages.',
            ], 422);
        }

        // A cancelled or lapsed subscription keeps its connected number on the store row, so
        // without this the vendor could go on sending campaigns for free past the grace window.
        if (!WhatsAppBilling::isActive($storeId)) {
            return response()->json([
                'success' => false,
                'message' => 'Your WhatsApp Business Platform subscription isn’t active. Activate it under WhatsApp → Billing to send messages.',
            ], 402);
        }

        $mode = $request->input('mode');
        $platform = $mode === 'platform';

        if ($platform) {
            // Ordered by the dedupe key so the browser's offset walk covers each person exactly
            // once — the underlying rows come from two tables with unrelated id spaces.
            $recipients = $this->outreachQuery($storeId)
                ->orderByRaw($this->phone10Sql('t.phone'))
                ->offset((int) $request->input('offset'))
                ->limit((int) $request->input('limit'))
                ->get()
                ->map(fn($r) => (object) [
                    'id'    => null,
                    'name'  => trim((string) $r->name),
                    'phone' => $r->phone,
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
            $phone = trim((string) $client->phone);

            // {name} / {phone} are substituted inside whatever the vendor typed; the named
            // slots ({{customer_name}}, {{customer_phone}}) are filled outright. Either way the
            // number only ever lands in the message that number receives, so it stays hidden
            // from the sender even in platform mode.
            // Meta rejects newlines and runs of 4+ spaces inside a parameter.
            $auto = ['customer_name' => $name, 'customer_phone' => $phone];
            $tokens = [
                '{name}'           => $name,
                '{customer_name}'  => $name,
                '{phone}'          => $phone,
                '{customer_phone}' => $phone,
            ];

            $parameters = [];
            foreach ($rawParams as $i => $raw) {
                // A slot is {key, value} for named templates; older callers send bare strings,
                // which are positional in the order they arrive.
                $key   = trim(is_array($raw) ? (string) ($raw['key'] ?? '') : '') ?: (string) ($i + 1);
                $value = is_array($raw) ? (string) ($raw['value'] ?? '') : (string) $raw;

                $value = array_key_exists($key, $auto) ? $auto[$key] : strtr($value, $tokens);

                $parameters[] = WhatsAppService::bodyParameter($key, $this->sanitizeParam($value));
            }

            $components = $parameters
                ? [['type' => 'body', 'parameters' => $parameters]]
                : [];

            // Context 'nearby' is what nearbyCappedPhones() counts — it must stay distinct
            // from sends to the store's own book or the frequency cap silently stops working.
            $res = $wa->sendTemplate(
                $client->phone,
                $request->template,
                $request->language,
                $components,
                $platform ? 'nearby' : 'bulk'
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

    private function templateBodyError(string $body, ?string $name = null): ?string
    {
        return WhatsAppService::templateBodyProblem($body, $name);
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

    /**
     * WhatsApp Business Platform billing — subscription state, AI token balance, template
     * slots and the charge history, all in one page.
     */
    public function billing(Request $request)
    {
        $storeId = Helpers::get_store_id();
        WhatsAppBilling::ensureTables();

        $subscription = WhatsAppBilling::subscription($storeId);
        $pricing = [
            'monthly'          => WhatsAppBilling::monthlyFee(),
            'monthly_total'    => WhatsAppBilling::withTax(WhatsAppBilling::monthlyFee()),
            'setup'            => WhatsAppBilling::setupFee(),
            'setup_total'      => WhatsAppBilling::withTax(WhatsAppBilling::setupFee()),
            'manager'          => WhatsAppBilling::accountManagerFee(),
            'manager_total'    => WhatsAppBilling::withTax(WhatsAppBilling::accountManagerFee()),
            'template_slot'    => WhatsAppBilling::extraTemplateFee(),
            'template_total'   => WhatsAppBilling::withTax(WhatsAppBilling::extraTemplateFee()),
            'token_pack'       => WhatsAppBilling::tokenPackPrice(),
            'token_pack_total' => WhatsAppBilling::withTax(WhatsAppBilling::tokenPackPrice()),
            'token_pack_usd'   => WhatsAppBilling::TOKEN_PACK_USD,
            'usd_rate'         => WhatsAppBilling::usdInrRate(),
            'gst'              => WhatsAppBilling::gstPercent(),
        ];

        return view('vendor-views.whatsapp.billing', [
            'subscription'  => $subscription,
            'active'        => WhatsAppBilling::isActive($storeId),
            'pricing'       => $pricing,
            'tokens'        => $this->poolStats($storeId, WhatsAppBilling::POOL_CHATBOT),
            'agentTokens'   => $this->poolStats($storeId, WhatsAppBilling::POOL_AGENT),
            'agentPlans'    => WhatsAppBilling::agentPlans(),
            'agentSub'      => WhatsAppBilling::agentSubscription($storeId),
            'agentActive'   => WhatsAppBilling::agentActive($storeId),
            'agentTopup'    => WhatsAppBilling::withTax(WhatsAppBilling::agentTopupPerMillion()),
            'allowance'     => WhatsAppBilling::templateAllowance($storeId),
            'included'      => WhatsAppBilling::includedTemplates(),
            'walletBalance' => WhatsAppBilling::walletBalance($storeId),
            'invoices'      => WhatsAppBilling::invoices($storeId, 25),
            'graceDays'     => WhatsAppBilling::GRACE_DAYS,
            'usage'         => WhatsAppBilling::usageThisMonth($storeId),
            'feeOwn'        => WhatsAppBilling::messageFeeOwn(),
            'feePlatform'   => WhatsAppBilling::messageFeePlatform(),
        ]);
    }

    /** Balance / granted / used figures for one token pool. */
    private function poolStats(int $storeId, string $pool): array
    {
        $row = WhatsAppBilling::tokenWallet($storeId, $pool);

        return [
            'balance'    => WhatsAppBilling::tokenBalance($storeId, $pool),
            'plan'       => (int) ($row->plan_tokens ?? 0),
            'plan_used'  => (int) ($row->plan_tokens_used ?? 0),
            'topup'      => (int) ($row->topup_tokens ?? 0),
            'topup_used' => (int) ($row->topup_tokens_used ?? 0),
            'this_month' => (int) DB::table('wa_token_usage')->where('store_id', $storeId)
                ->where('pool', $pool)
                ->where('created_at', '>=', now()->startOfMonth())->sum('tokens'),
        ];
    }

    /** Chatbot settings — which bot answers, and what it may tell customers. */
    public function bot(Request $request)
    {
        $storeId = Helpers::get_store_id();

        return view('vendor-views.whatsapp.bot', [
            'mode'        => WhatsAppAgent::mode($storeId),
            'agentActive' => WhatsAppBilling::agentActive($storeId),
            'agentSub'    => WhatsAppBilling::agentSubscription($storeId),
            'shares'      => WhatsAppAgent::shares($storeId),
            'shareItems'  => WhatsAppAgent::SHARE_ITEMS,
            'agentPlans'  => WhatsAppBilling::agentPlans(),
            'gst'         => WhatsAppBilling::gstPercent(),
        ]);
    }

    public function botMode(Request $request)
    {
        $request->validate(['mode' => 'required|in:knowledge,agent']);
        $res = WhatsAppAgent::setMode(Helpers::get_store_id(), $request->mode);
        $res['success'] ? Toastr::success($res['message']) : Toastr::error($res['message']);
        return back();
    }

    public function botShares(Request $request)
    {
        WhatsAppAgent::saveShares(Helpers::get_store_id(), (array) $request->input('items', []));
        Toastr::success('Saved. The AI Agent will only discuss what you have allowed.');
        return back();
    }

    // Start / switch an AI Agent plan (lead & appointment management).
    public function agentSubscribe(Request $request)
    {
        $request->validate(['plan' => 'required|in:' . implode(',', array_keys(WhatsAppBilling::AGENT_PLANS))]);
        $res = WhatsAppBilling::subscribeAgent(Helpers::get_store_id(), $request->plan);
        $res['success'] ? Toastr::success($res['message']) : Toastr::error($res['message']);
        return back();
    }

    public function agentCancel(Request $request)
    {
        $res = WhatsAppBilling::cancelAgent(Helpers::get_store_id());
        $res['success'] ? Toastr::success($res['message']) : Toastr::error($res['message']);
        return back();
    }

    // AI Agent token top-up — ₹700 + GST per million.
    public function buyAgentTokens(Request $request)
    {
        $request->validate(['millions' => 'nullable|integer|min:1|max:50']);
        $res = WhatsAppBilling::buyAgentTokens(Helpers::get_store_id(), (int) ($request->millions ?: 1));
        $res['success'] ? Toastr::success($res['message']) : Toastr::error($res['message']);
        return back();
    }

    // Activate the WhatsApp Business Platform — setup fee (first time) + the first month.
    public function billingSubscribe(Request $request)
    {
        $storeId        = Helpers::get_store_id();
        $accountManager = $request->boolean('account_manager');
        $sub            = WhatsAppBilling::subscription($storeId);

        // The one-time setup fee is collected by the payment gateway at onboarding, not from the
        // wallet. Once it is paid the flag sticks, so a reactivation goes straight to subscribe()
        // and only the monthly is debited.
        if (!($sub && $sub->setup_fee_paid)) {
            return $this->redirectToSetupPayment($storeId, $accountManager, $request);
        }

        // Setup fee is settled — the monthly is a Razorpay mandate, so hand the vendor to the
        // hosted authorisation page. The platform switches on when subscription.charged lands.
        return $this->startMandate($storeId, $accountManager);
    }

    /** Create the Razorpay subscription and send the vendor to authorise the auto-debit. */
    public function billingAuthorizeMandate(Request $request)
    {
        return $this->startMandate(Helpers::get_store_id(), $request->boolean('account_manager'));
    }

    private function startMandate(int $storeId, bool $accountManager)
    {
        $res = WhatsAppRecurring::start($storeId, $accountManager);
        if (!$res['success']) {
            Toastr::error($res['message']);
            return back();
        }
        return redirect()->away($res['url']);
    }

    /**
     * Pay the one-time onboarding fee from the connect screen, before Embedded Signup runs.
     * Returns the vendor to connect — the next step there is linking the number, not billing.
     */
    public function connectSetupFee(Request $request)
    {
        $storeId = Helpers::get_store_id();

        if (WhatsAppBilling::setupFeePaid($storeId)) {
            Toastr::info('Your setup fee is already paid — you can connect your number.');
            return back();
        }

        return $this->redirectToSetupPayment($storeId, false, $request, 'whatsapp/connect');
    }

    /** Hand the vendor to Razorpay for the setup fee; whatsapp_setup_success() resumes activation. */
    private function redirectToSetupPayment(int $storeId, bool $accountManager, Request $request, string $returnTo = 'whatsapp/billing')
    {
        $vendor = \App\Models\Vendor::find(auth('vendor')->id());
        if (!$vendor) {
            Toastr::error('Could not identify your vendor account.');
            return back();
        }

        $tmp = TmpWhatsAppSetup::create([
            'store_id'        => $storeId,
            'account_manager' => $accountManager ? 1 : 0,
        ]);

        $payer = new Payer(
            trim($vendor->f_name . ' ' . $vendor->l_name),
            $vendor->email,
            $vendor->phone,
            ''
        );

        $domain = $request->getHost();
        $external_redirect_link = \Illuminate\Support\Str::contains($domain, 'staging')
            ? 'store-panel/' . $returnTo
            : $returnTo;

        $payment_info = new PaymentInfo(
            success_hook: 'whatsapp_setup_success',
            failure_hook: 'whatsapp_setup_fail',
            currency_code: BusinessSetting::where('key', 'currency')->value('value'),
            payment_method: 'razor_pay',
            payment_platform: 'web',
            payer_id: $storeId,
            receiver_id: 100,
            additional_data: [
                'business_name' => BusinessSetting::where('key', 'business_name')->value('value'),
                'business_logo' => asset('storage/app/public/business') . '/' . BusinessSetting::where('key', 'logo')->value('value'),
            ],
            // Gateway collects the GST-inclusive amount.
            payment_amount: WhatsAppBilling::withTax(WhatsAppBilling::setupFee()),
            external_redirect_link: $external_redirect_link,
            attribute: 'whatsapp_setup',
            attribute_id: $tmp->id
        );

        return redirect()->to($this->generate_link($payer, $payment_info, new Receiver('Admin', 'example.png')));
    }

    // Stop auto-renewal; the already-paid period is honoured.
    public function billingCancel(Request $request)
    {
        $storeId = Helpers::get_store_id();

        // Stop the mandate at Razorpay first — cancelling only on our side would leave the card
        // being debited every month for a subscription the vendor believes is closed.
        if (WhatsAppRecurring::isGatewayBilled($storeId)) {
            $mandate = WhatsAppRecurring::cancel($storeId);
            if (!$mandate['success']) {
                Toastr::error($mandate['message']);
                return back();
            }
        }

        $res = WhatsAppBilling::cancel($storeId);
        $res['success'] ? Toastr::success($res['message']) : Toastr::error($res['message']);
        return back();
    }

    public function billingAccountManager(Request $request)
    {
        $res = WhatsAppBilling::setAccountManager(Helpers::get_store_id(), $request->boolean('enable'));
        $res['success'] ? Toastr::success($res['message']) : Toastr::error($res['message']);
        return back();
    }

    // One-time purchase of a message-template slot beyond the included quota.
    public function buyTemplateSlot(Request $request)
    {
        $res = WhatsAppBilling::buyTemplateSlot(Helpers::get_store_id());
        $res['success'] ? Toastr::success($res['message']) : Toastr::error($res['message']);
        return back();
    }

    // AI auto-reply token top-up — 1M tokens per pack.
    public function buyTokens(Request $request)
    {
        $request->validate(['packs' => 'nullable|integer|min:1|max:50']);
        $res = WhatsAppBilling::buyTokenPack(Helpers::get_store_id(), (int) ($request->packs ?: 1));
        $res['success'] ? Toastr::success($res['message']) : Toastr::error($res['message']);
        return back();
    }

    /**
     * Template quota gate. Returns an error message when the store already has as many
     * templates as it has paid for, or null when it may create another.
     *
     * A failed listTemplates call never blocks the vendor — we only enforce on a count we
     * actually read back from Meta.
     */
    private function templateQuotaError(WhatsAppService $wa, int $storeId): ?string
    {
        $res = $wa->listTemplates();
        if (!$res['success']) {
            return null;
        }

        $allowance = WhatsAppBilling::templateAllowance($storeId);
        if (count($res['data']) < $allowance) {
            return null;
        }

        return 'You have used all ' . $allowance . ' of your message templates ('
            . WhatsAppBilling::includedTemplates() . ' included with your plan). Add an extra template slot for '
            . _price(WhatsAppBilling::withTax(WhatsAppBilling::extraTemplateFee()))
            . ' one-time from WhatsApp → Billing, or delete a template you no longer use.';
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
        $storeId = Helpers::get_store_id();

        // The onboarding fee gates the link-up, not the UI. Connecting costs us the Meta
        // onboarding the moment the WABA is attached, so this is checked here as well as on the
        // button — the endpoint is callable directly.
        if (!WhatsAppBilling::setupFeePaid($storeId)) {
            return response()->json([
                'success'  => false,
                'message'  => 'Pay the one-time setup fee before connecting your number.',
                'pay_url'  => route('vendor.whatsapp.connect'),
            ], 402);
        }

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
            DB::table('stores')->where('id', $storeId)->update([
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

        // Template quota: 4 included with the plan, plus any slots the vendor bought.
        $quota = [
            'included'  => WhatsAppBilling::includedTemplates(),
            'allowance' => WhatsAppBilling::templateAllowance($storeId),
            'used'      => count($templates),
            'slot_fee'  => WhatsAppBilling::withTax(WhatsAppBilling::extraTemplateFee()),
        ];

        return view('vendor-views.whatsapp.templates', compact('connected', 'templates', 'templateError', 'presets', 'apptReminder', 'quota'));
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

        if ($quotaError = $this->templateQuotaError($wa, Helpers::get_store_id())) {
            Toastr::error($quotaError);
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

        if ($bodyError = $this->templateBodyError((string) $request->tpl_body, $request->tpl_name)) {
            Toastr::error($bodyError);
            return back()->withInput();
        }

        if ($quotaError = $this->templateQuotaError($wa, Helpers::get_store_id())) {
            Toastr::error($quotaError);
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

        if ($bodyError = $this->templateBodyError((string) $request->tpl_body, $request->tpl_name)) {
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
