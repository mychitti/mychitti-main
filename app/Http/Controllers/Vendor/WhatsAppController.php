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
use App\Services\CustomerNote;
use App\Services\FeedbackFlow;
use App\Services\HmisWhatsAppShare;
use App\Services\WhatsAppAgent;
use App\Services\WhatsAppBilling;
use App\Services\WhatsAppRecurring;
use App\Services\WhatsAppService;
use App\Traits\Payment;
use App\Traits\WhatsAppAudience;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    use Payment, WhatsAppAudience;

    /** How many clients the bulk recipient picker will load at once. */
    const BULK_PICKER_LIMIT = 1000;

    /** Recipients accepted per bulk-send call — the browser drives the batches so a long run
     *  never hits max_execution_time and the vendor sees live progress. */
    const BULK_BATCH_LIMIT = 25;

    /** Both knobs now live on WhatsAppService so the audience trait can read them without
     *  depending on a controller; kept here because the blades reference them by this name. */
    const BULK_SHOW_UNAPPROVED = WhatsAppService::BULK_SHOW_UNAPPROVED;
    const NEARBY_MONTHLY_CAP = WhatsAppService::NEARBY_MONTHLY_CAP;

    // Vendor "Connect WhatsApp" screen (Embedded Signup).
    public function connect(Request $request)
    {
        WhatsAppService::ensureStoreColumns();
        $config = Helpers::get_business_settings('whatsapp_config');
        $storeId = Helpers::get_store_id();

        // Coming back from the payment gateway, which appends ?flag=…&token=… . Say what
        // happened, then bounce to the bare URL so a refresh or a shared link can't replay the
        // message — the fee itself was already recorded by whatsapp_setup_success().
        if ($request->has('flag')) {
            $request->query('flag') === 'success'
                ? Toastr::success('Onboarding fee received. You can connect your WhatsApp number now.')
                : Toastr::error('That payment didn’t go through, so nothing was charged. You can try again.');

            return redirect()->route('vendor.whatsapp.connect');
        }
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
        // Prices are shown GST-exclusive with the tax called out, the same way Plan & Billing
        // presents the monthly — the vendor should recognise the same number on both screens.
        $setupPaid = WhatsAppBilling::setupFeePaid($storeId);
        $plans     = WhatsAppBilling::plans();

        $tab = in_array($request->get('tab'), ['connection', 'numbers', 'billing'], true)
            ? $request->get('tab') : 'connection';
        // Nothing to manage on Numbers until one is linked, so a stale link lands on Connection.
        if ($tab === 'numbers' && !$connected) {
            $tab = 'connection';
        }

        $billing = $this->billingData();

        // The two screens quoted the same setup fee and GST from the same source, so merging the
        // arrays cannot disagree with itself. Connection additionally quotes the entry price —
        // the ladder itself is in the Plan & Billing pane, where a tier is actually chosen.
        $pricing = array_merge($billing['pricing'], [
            'monthly'       => $plans[WhatsAppBilling::DEFAULT_PLAN]['price'],
            'monthly_total' => WhatsAppBilling::withTax($plans[WhatsAppBilling::DEFAULT_PLAN]['price']),
            'plans'         => $plans,
        ]);
        $billing['pricing'] = $pricing;

        return view('vendor-views.whatsapp.connect', array_merge(
            compact('es', 'store', 'connected', 'setupPaid', 'pricing', 'tab'),
            $this->numbersData(),
            $billing
        ));
    }

    /**
     * Bulk Message — composing a batch, the customer book it goes to, and the history of what
     * previous batches did. History used to be its own menu item whose only action was "New bulk
     * message", pointing back at the composer on a third page.
     */
    public function bulk(Request $request)
    {
        WhatsAppService::ensureStoreColumns();
        $storeId = Helpers::get_store_id();
        $store = DB::table('stores')->where('id', $storeId)
            ->select('wa_enabled', 'wa_phone_number_id')->first();
        $connected = (bool) ($store && $store->wa_enabled && $store->wa_phone_number_id);

        $tab = in_array($request->get('tab'), ['compose', 'audience', 'history'], true)
            ? $request->get('tab') : 'compose';

        // Bulk sending is only offered on the vendor's own connected number — Meta bills them
        // directly and a marketing blast must never burn the platform number's quality rating.
        $templates = [];
        $templateError = null;
        $clientCount = 0;
        $platformUserCount = 0;
        $optOutCount = 0;

        // The vendor's own customer book — what the Excel import fills, and the audience behind
        // the composer's "My customers" tab. It lives here rather than on the dashboard because
        // this is the page where that audience is actually chosen and messaged.
        $customerStats = [
            'total'      => DB::table('store_customers')->where('store_id', $storeId)->count(),
            'with_phone' => DB::table('store_customers')->where('store_id', $storeId)
                ->whereNotNull('phone')->where('phone', '!=', '')->count(),
        ];
        $recentCustomers = DB::table('store_customers')->where('store_id', $storeId)
            ->orderByDesc('id')->limit(8)->get(['f_name', 'phone']);

        if ($connected) {
            $res = WhatsAppService::make($storeId)->listTemplates();

            // A trashed template is one the vendor has put away — it must not be offerable here
            // just because it is still approved at Meta.
            $trashedKeys = WhatsAppService::trashedTemplateKeys($storeId);
            $available = array_values(array_filter($res['data'], fn($tpl) => !in_array(
                strtolower(data_get($tpl, 'name') . '|' . data_get($tpl, 'language', 'en_US')),
                $trashedKeys,
                true
            )));

            $templates = $this->bulkTemplateOptions($available);
            if (!$res['success']) {
                $templateError = $res['error'];
            }
            $clientCount = $this->clientQuery($storeId)->count();
            $platformUserCount = $this->outreachCount($storeId);
            $optOutCount = count(WhatsAppService::optedOutPhones($storeId));
        }

        // Both audiences go out in one send now, and they are not billed at the same rate — the
        // composer has to be able to price a mixed batch before the vendor commits to it.
        $rates = [
            'own'      => WhatsAppBilling::messageCost('own'),
            'platform' => WhatsAppBilling::messageCost('platform'),
        ];

        return view('vendor-views.whatsapp.bulk', array_merge(
            compact(
                'connected', 'templates', 'templateError', 'clientCount', 'platformUserCount',
                'optOutCount', 'customerStats', 'recentCustomers', 'rates', 'tab'
            ),
            $this->bulkHistoryData()
        ));
    }

    /**
     * WhatsApp activity dashboard for the vendor.
     *
     * Strictly this store's OWN traffic — messages it sent from its own connected number.
     * MyChitti's platform number never appears here. It used to: the scope also matched anything
     * addressed to the store's phone, which pulled in our lead alerts and test messages to the
     * vendor. That inflated "Messages sent" with messages the vendor did not send and is not
     * billed for, so the tile could never be reconciled against their wallet — per-message
     * charges are taken at dispatch, and only store-owned sends are charged.
     */
    public function dashboard(Request $request)
    {
        WhatsAppService::ensureMessagesTable();
        $storeId = Helpers::get_store_id();
        $store = DB::table('stores')->where('id', $storeId)
            ->select('id', 'name', 'phone', 'wa_enabled', 'wa_phone_number_id')
            ->first();

        $connected = (bool) ($store && $store->wa_enabled && $store->wa_phone_number_id);

        // One filter reused across every aggregate below.
        $scope = fn($q) => $q->where('whatsapp_messages.store_id', $storeId);

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

        // What the traffic is. Only contexts the vendor's own number produces — 'lead notify',
        // 'lead accepted' and 'test message' are MyChitti talking to the vendor from the platform
        // number, so they carry no store_id and can no longer reach this page at all.
        $contextLabels = [
            'welcome'      => 'Customer welcome messages',
            'chat reply'   => 'Chat replies',
            'auto reply'   => 'AI auto-replies',
            'bulk'         => 'Bulk campaigns',
            'nearby'       => 'Nearby-offer campaigns',
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
            'store', 'connected', 'stats', 'chart', 'contextRows', 'recent'
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
        $tpl = WhatsAppService::templateFor($storeId, 'staff_forward');
        if (!$tpl) {
            WhatsAppService::noteMissingTemplate($storeId, 'staff_forward');
        }

        // Body vars: {{1}} store, {{2}} sender name, {{3}} phone, {{4}} message.
        $params = array_map(fn($v) => $this->sanitizeParam((string) $v), [$storeName, $senderName, $senderPhone, $message]);
        $components = [[
            'type'       => 'body',
            'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => $v], $params),
        ]];

        // No template is not fatal here — the free-text fallback below still reaches a staff
        // member who has messaged the store in the last 24h.
        $res  = $tpl
            ? $wa->sendTemplate($staff->phone, $tpl['name'], $tpl['language'], $components, 'forward to staff')
            : ['success' => false, 'error' => null];
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
     * What sending a note to this number would cost, asked before the sender commits.
     *
     * A reply inside the customer's own 24h window is free and reads as an ordinary message; once
     * that window shuts the same note has to travel on a template and is billed. The sender should
     * see which of the two they are about to do.
     */
    public function noteQuote(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:32']);

        return response()->json(
            CustomerNote::quote(Helpers::get_store_id(), (string) $request->input('phone'))
        );
    }

    /** Send one hand-written note to one customer. */
    public function noteSend(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:32',
            'name'  => 'nullable|string|max:190',
            'note'  => 'required|string|max:2000',
        ]);

        $result = CustomerNote::send(
            Helpers::get_store_id(),
            $request->input('name'),
            (string) $request->input('phone'),
            (string) $request->input('note')
        );

        return response()->json($result, empty($result['success']) ? 422 : 200);
    }

    /**
     * Feedback that came back unhappy, and what the patient said was wrong.
     *
     * Its own screen rather than a line in the inbox: a complaint is work to be picked up and
     * closed, and an inbox is read once and scrolled past.
     */
    public function complaints(Request $request)
    {
        FeedbackFlow::ensureTables();
        $storeId = Helpers::get_store_id();

        $status = in_array($request->get('status'), ['open', 'resolved'], true) ? $request->get('status') : 'open';

        $complaints = DB::table('store_complaints')
            ->where('store_id', $storeId)
            ->where('status', $status === 'resolved' ? 'resolved' : 'open')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // The counts either side of the tabs, so an empty list reads as "none open" rather than
        // "this screen is broken".
        $counts = [
            'open'     => DB::table('store_complaints')->where('store_id', $storeId)->where('status', 'open')->count(),
            'resolved' => DB::table('store_complaints')->where('store_id', $storeId)->where('status', 'resolved')->count(),
        ];

        // How the ratings have landed overall — the answer to "is this one angry patient or a pattern".
        $ratings = DB::table('wa_feedback_threads')
            ->where('store_id', $storeId)->whereNotNull('rating')
            ->select('rating', DB::raw('COUNT(*) as n'))->groupBy('rating')->pluck('n', 'rating')->all();

        return view('vendor-views.whatsapp.complaints', compact('complaints', 'counts', 'status', 'ratings'));
    }

    /** Mark one complaint dealt with. */
    public function complaintResolve(Request $request, $id)
    {
        FeedbackFlow::ensureTables();

        $updated = DB::table('store_complaints')
            ->where('id', (int) $id)->where('store_id', Helpers::get_store_id())
            ->update(['status' => 'resolved', 'resolved_at' => now(), 'updated_at' => now()]);

        $updated ? Toastr::success('Marked resolved.') : Toastr::error('That complaint is no longer there.');

        return back();
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
        // echo 'f';
        $request->validate([
            'template'     => 'required|string',
            'language'     => 'required|string',
            'mode'         => 'required|in:clients,platform',
            'run_id'       => 'required|string|max:40',
            'client_ids'   => 'required_if:mode,clients|array|max:' . self::BULK_BATCH_LIMIT,
            'client_ids.*' => 'integer',
            'limit'        => 'required_if:mode,platform|integer|min:1|max:' . self::BULK_BATCH_LIMIT,
            'params'       => 'nullable|array',
            'params.*.key' => 'nullable|string|max:64',
            // The file for a media-header template. Meta fetches it themselves, so it has to be
            // a public URL — bulkHeaderMedia() below produces one from an upload.
            'header_media' => 'nullable|url|max:1000',
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
        $runId = trim((string) $request->input('run_id'));

        WhatsAppService::ensureBulkSendTable();

        if ($platform) {
            // No offset — the pool is walked by exclusion. An offset walk restarted at zero on
            // every send, so a vendor asking for 500 got the same 500 lowest-numbered people
            // every time, never reached the rest of the pool, and re-messaged whoever had
            // already been served when a broken run was started again.
            //
            // outreachQuery drops everyone this store reached inside the rotation window; what
            // is left here is the within-run exclusion, so a batch never re-offers someone an
            // earlier batch of the same run already claimed. Between them, each batch returns the
            // next unmessaged people.
            //
            // A subquery rather than an id list pulled into PHP: a 17,000-person run is hundreds
            // of batches, and re-loading an ever-growing list of claimed numbers on each one
            // turns a long send quadratic. This reads the unique key head-on (run_id, phone10).
            $phone10 = $this->phone10Sql('t.phone');
            // Column against column, so both sides are pinned to one collation — see
            // collatedPhone(). The claim table and the contact tables can carry different ones.
            $claimed = $this->collatedPhone('b.`phone10`');
            $candidate = $this->collatedPhone($phone10);

            $recipients = $this->outreachQuery($storeId)
                ->whereNotExists(function ($q) use ($runId, $claimed, $candidate) {
                    $q->select(DB::raw(1))->from('wa_bulk_sends as b')
                        ->where('b.run_id', $runId)
                        ->whereRaw("{$claimed} = {$candidate}");
                })
                ->orderByRaw($phone10)
                ->limit((int) $request->input('limit'))
                ->get()
                ->map(fn($r) => (object) [
                    'id'    => null,
                    'name'  => trim((string) $r->name),
                    'phone' => $r->phone,
                ]);

                // prx($recipients);
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

        // Per-message charges leave the wallet at dispatch, so price the whole batch before any
        // of it goes out — a vendor would rather be told the batch is unaffordable than watch a
        // campaign stop halfway through. The rate is uniform per mode: 'clients' recipients come
        // from the store's own book, 'platform' recipients never do.
        $rate = WhatsAppBilling::messageCost($platform ? 'platform' : 'own');
        $batchCost = round($rate * $recipients->count(), 2);
        if ($recipients->count() && WhatsAppBilling::walletBalance($storeId) < $batchCost) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet balance too low for this batch. ' . $recipients->count() . ' message'
                    . ($recipients->count() === 1 ? '' : 's') . ' × ' . _price($rate) . ' = ' . _price($batchCost)
                    . ' (GST included). Recharge your wallet and try again.',
            ], 402);
        }

        $rawParams = array_values((array) $request->input('params', []));
        $results = [];
        $skipped = 0;

        // Read once for the whole batch, then filled per recipient below. Kept so the history
        // screen can show the vendor the words their customer read — the delivery log only has
        // "template: {name}", and a template can be edited or deleted long before anyone asks.
        $templateBody = WhatsAppService::templateBodyText($storeId, (string) $request->template, (string) $request->language);

        // A template is sent with the components it was created with. One with an image, video or
        // document header needs that file on every message, or Graph rejects the lot with
        // "(#132012) Parameter format does not match format in the created template".
        $headerFormat = WhatsAppService::templateHeaderFormat($storeId, (string) $request->template, (string) $request->language);
        $headerComponent = null;
        if (in_array($headerFormat, WhatsAppService::MEDIA_HEADERS, true)) {
            $mediaUrl = trim((string) $request->input('header_media'));
            if ($mediaUrl === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'This template has ' . ($headerFormat === 'IMAGE' ? 'an image' : 'a ' . strtolower($headerFormat))
                        . ' at the top, so a file has to be attached before it can be sent.',
                ], 422);
            }
            $headerComponent = WhatsAppService::mediaHeaderComponent($headerFormat, $mediaUrl);
        }

        foreach ($recipients as $client) {
            $name = trim((string) $client->name) ?: 'Customer';
            $phone = trim((string) $client->phone);

            // Claim the recipient before dispatching. The unique key on (run_id, phone10) is what
            // guarantees the answer to "if the run breaks and I send again, do they get it twice?"
            // — they do not. A repeat claim throws, and the person is skipped rather than
            // messaged a second time. Claimed first, not after, so a crash between the claim and
            // the API call still leaves the row behind to block a re-send.
            $sendId = $this->claimBulkRecipient($runId, $storeId, $client, $phone, $name, $platform, $request->template);
            if (!$sendId) {
                $skipped++;
                continue;
            }

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
            $filled = [];
            foreach ($rawParams as $i => $raw) {
                // A slot is {key, value} for named templates; older callers send bare strings,
                // which are positional in the order they arrive.
                $key   = trim(is_array($raw) ? (string) ($raw['key'] ?? '') : '') ?: (string) ($i + 1);
                $value = is_array($raw) ? (string) ($raw['value'] ?? '') : (string) $raw;

                $value = array_key_exists($key, $auto) ? $auto[$key] : strtr($value, $tokens);

                $clean = $this->sanitizeParam($value);
                $filled[$key] = $clean;
                $parameters[] = WhatsAppService::bodyParameter($key, $clean);
            }
            
            // Header first — Meta matches components against the approved template in order.
            $components = [];
            if ($headerComponent) {
                $components[] = $headerComponent;
            }
            if ($parameters) {
                $components[] = ['type' => 'body', 'parameters' => $parameters];
            }


            // Context 'nearby' is what nearbyCappedPhones() counts — it must stay distinct
            // from sends to the store's own book or the frequency cap silently stops working.
            $res = $wa->sendTemplate(
                $client->phone,
                $request->template,
                $request->language,
                $components,
                $platform ? 'nearby' : 'bulk'
            );

            DB::table('wa_bulk_sends')->where('id', $sendId)->update([
                'wamid'      => $res['id'] ?? null,
                'status'     => $res['success'] ? 'sent' : 'failed',
                'error'      => $res['error'] ?? null,
                // This recipient's own copy — {{customer_name}} carries their name, not the next
                // person's, so the history reads back exactly what each number received.
                'body'       => $templateBody ? mb_substr($this->fillTemplateBody($templateBody, $filled), 0, 2000) : null,
                'language'   => mb_substr((string) $request->language, 0, 20),
                'updated_at' => now(),
            ]);

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

        // These recipients have just entered this store's rotation window, so the displayed
        // audience size is now out of date — drop it rather than let the composer keep offering
        // people it can no longer reach for the next ten minutes.
        if ($platform) {
            $this->forgetOutreachCount($storeId);
        }

        return response()->json([
            'success' => true,
            'sent'    => count(array_filter($results, fn($r) => $r['success'])),
            'failed'  => count(array_filter($results, fn($r) => !$r['success'])),
            // Already messaged in this run — a resend after a broken batch, which is exactly what
            // the claim is for. Reported so the composer can say so rather than double-counting.
            'skipped' => $skipped,
            'results' => $results,
        ]);
    }

    /**
     * Which of the store's own templates each automation should use.
     *
     * The platform suggests a template per role and seeds it as a preset, but the vendor owns
     * their WABA — they can delete the suggestion and write their own wording, which is exactly
     * what breaks automation silently. This screen is where they say which template means
     * "welcome", so the job has something to send.
     */
    public function templateRoles(Request $request)
    {
        return redirect()->route('vendor.whatsapp.automation', ['tab' => 'automatic']);
    }

    /**
     * Automation — one page for the three things that send or answer on their own: which template
     * each automatic message uses, what the AI Agent may do, and the knowledge it answers from.
     * They were three menu items, each too small to stand alone, and fixing one usually meant
     * opening another.
     */
    public function automation(Request $request)
    {
        $tab = in_array($request->get('tab'), ['automatic', 'chatbot', 'knowledge'], true)
            ? $request->get('tab') : 'automatic';

        return view('vendor-views.whatsapp.automation', array_merge(
            $this->templateRolesData(),
            $this->botData(),
            app(KnowledgeController::class)->knowledgeData($request),
            ['tab' => $tab]
        ));
    }

    /** Every automatic message role with the template it currently resolves to. */
    private function templateRolesData(): array
    {
        $storeId = Helpers::get_store_id();
        $wa = WhatsAppService::make($storeId);

        $available = [];
        $listError = null;
        if ($wa->source() === 'vendor' && $wa->hasWaba()) {
            $res = $wa->listTemplates();
            if (!$res['success']) {
                $listError = $res['error'];
            }
            // Only APPROVED templates can actually be sent, so offering anything else would be
            // offering a choice that fails at send time.
            foreach ($res['data'] as $tpl) {
                if (strtoupper((string) data_get($tpl, 'status')) !== 'APPROVED') {
                    continue;
                }
                $body = '';
                foreach ((array) data_get($tpl, 'components', []) as $c) {
                    if (strtoupper((string) data_get($c, 'type')) === 'BODY') {
                        $body = (string) data_get($c, 'text', '');
                    }
                }
                $available[] = [
                    'name'     => (string) data_get($tpl, 'name'),
                    'language' => (string) data_get($tpl, 'language', 'en_US'),
                    'body'     => $body,
                    'vars'     => WhatsAppService::positionalCount($body),
                ];
            }
        }

        $bindings = WhatsAppService::templateBindings($storeId);

        // A role can only be filled by a template that takes exactly its variables, in order —
        // the automation fills the body by position, so a mismatched template either fails on
        // parameter count or puts the store's name where the customer's should be.
        $roles = [];
        foreach (WhatsAppService::TEMPLATE_ROLES as $role => $meta) {
            // A role tied to a module is only offered to stores that run it — a laundry has no use
            // for a consultation-summary card it could never trigger. Mirrors how the Send
            // Notifications toggles are filtered.
            if (!empty($meta['module']) && !vendorPlanHasModule($meta['module'])) {
                continue;
            }

            $need = count($meta['params']);
            $roles[$role] = $meta + [
                'key'      => $role,
                'need'     => $need,
                'current'  => $bindings[$role]->template_name ?? null,
                'resolved' => WhatsAppService::templateFor($storeId, $role),
                'missing'  => WhatsAppService::missingTemplateSince($storeId, $role),
                'options'  => array_values(array_filter($available, fn($t) => $t['vars'] === $need)),
                'rejected' => array_values(array_filter($available, fn($t) => $t['vars'] !== $need)),
                // Meta reserves a deleted template's name for a month, so a broken role usually
                // cannot be fixed by re-creating what was deleted. Offer names that will work.
                'suggested' => WhatsAppService::suggestTemplateNames(
                    $meta['default'],
                    array_column($available, 'name')
                ),
            ];
        }

        return [
            'roles'     => $roles,
            'connected' => $wa->source() === 'vendor',
            'listError' => $listError,
            'lockDays'  => WhatsAppService::TEMPLATE_NAME_LOCK_DAYS,
        ];
    }

    /**
     * Which automated messages this template was the last one standing for — the roles that stop
     * working the moment it is gone. Read after a delete, so the vendor is told at the moment
     * they break it rather than by noticing months of silence.
     */
    private function rolesLeftWithout(int $storeId, string $name): array
    {
        WhatsAppService::forgetTemplateStatuses($storeId);

        $orphaned = [];
        foreach (WhatsAppService::TEMPLATE_ROLES as $role => $meta) {
            $bound = WhatsAppService::templateBindings($storeId)[$role]->template_name ?? $meta['default'];
            if (strtolower($bound) !== strtolower($name)) {
                continue;
            }
            if (!WhatsAppService::templateFor($storeId, $role)) {
                $orphaned[] = strtolower($meta['label']);
            }
        }

        return $orphaned;
    }

    /** Point one role at one of the store's approved templates, or clear it. */
    public function templateRoleSave(Request $request)
    {
        $request->validate([
            'role'     => 'required|in:' . implode(',', array_keys(WhatsAppService::TEMPLATE_ROLES)),
            'template' => 'nullable|string|max:190',
            'language' => 'nullable|string|max:20',
        ]);

        $storeId = Helpers::get_store_id();
        $role    = $request->role;

        if (!$request->filled('template')) {
            WhatsAppService::unbindTemplate($storeId, $role);
            Toastr::success('Reset to the suggested template for ' . WhatsAppService::TEMPLATE_ROLES[$role]['label'] . '.');
            return back();
        }

        // Re-check the variable count server-side — the dropdown filters it, but a hand-posted
        // form must not be able to bind a template that would misfill the message.
        $body = WhatsAppService::templateBodyText($storeId, $request->template, $request->language);
        $need = WhatsAppService::roleParamCount($role);
        if ($body !== null && WhatsAppService::positionalCount($body) !== $need) {
            Toastr::error('That template needs ' . WhatsAppService::positionalCount($body) . ' value(s), but this message sends '
                . $need . '. Pick a template with exactly ' . $need . '.');
            return back();
        }

        WhatsAppService::bindTemplate($storeId, $role, $request->template, $request->language ?: 'en_US');
        Toastr::success(WhatsAppService::TEMPLATE_ROLES[$role]['label'] . ' will now use ' . $request->template . '.');
        return back();
    }

    /**
     * Take the file for a media-header template and hand back a public URL for it.
     *
     * Meta fetches header media from their own servers at send time, so it cannot be a private
     * path or a data URI — it has to be a link anyone can open. Uploading to the public disk is
     * what makes that true, and the same URL is reused for every recipient in the run rather
     * than re-uploaded per message.
     */
    public function bulkHeaderMedia(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120|mimes:jpg,jpeg,png,mp4,pdf',
        ], [
            'file.mimes' => 'Attach a JPG or PNG image, an MP4 video, or a PDF document.',
            'file.max'   => 'The file must be 5 MB or smaller.',
        ]);

        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension() ?: $file->extension();
            $name = Helpers::upload('whatsapp/header/', $extension, $file);

            return response()->json([
                'success' => true,
                'url'     => asset('storage/app/public/whatsapp/header/' . $name),
                'name'    => $file->getClientOriginalName(),
            ]);
        } catch (\Throwable $e) {
            Log::error('WhatsApp header media upload failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not upload that file. Try again.'], 500);
        }
    }

    /**
     * Reserve one recipient for this run. Returns the claim row id, or null when someone else
     * (an earlier batch, or a repeat of one) already holds them.
     */
    private function claimBulkRecipient(string $runId, int $storeId, $client, string $phone, string $name, bool $platform, ?string $template): ?int
    {
        $phone10 = substr(preg_replace('/[^0-9]/', '', $phone) ?? '', -10);
        if ($phone10 === '') {
            return null;
        }

        try {
            return (int) DB::table('wa_bulk_sends')->insertGetId([
                'store_id'   => $storeId,
                'run_id'     => $runId,
                'phone10'    => $phone10,
                'phone'      => $phone,
                'name'       => mb_substr($name, 0, 190),
                'client_id'  => $client->id ?: null,
                'audience'   => $platform ? 'platform' : 'own',
                'template'   => $template ? mb_substr($template, 0, 190) : null,
                'status'     => 'queued',
                'sent_at'    => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Duplicate key — already claimed in this run. Any other failure is treated the same
            // way on purpose: without a claim we cannot promise the person is not messaged twice,
            // and not sending is the safer half of that bargain.
            return null;
        }
    }

    /** Put this recipient's parameter values into the template body, for the record kept of it. */
    private function fillTemplateBody(string $body, array $values): string
    {
        $tokens = [];
        foreach ($values as $key => $value) {
            $tokens['{{' . $key . '}}'] = $value;
            $tokens['{{ ' . $key . ' }}'] = $value;
        }

        return strtr($body, $tokens);
    }

    /**
     * Every bulk send this store has made, one row per run — the batch the composer sent as a
     * unit. What went out, to how many people, when, and how it landed.
     *
     * Grouped in SQL rather than in PHP: a store with a year of 17,000-person runs has millions of
     * recipient rows, and this page must never load them to count them.
     */
    public function bulkHistory(Request $request)
    {
        return redirect()->route('vendor.whatsapp.bulk', ['tab' => 'history']);
    }

    /** Every batch this store has sent, with the totals shown above the list. */
    private function bulkHistoryData(): array
    {
        WhatsAppService::ensureBulkSendTable();
        $storeId = Helpers::get_store_id();

        $runs = DB::table('wa_bulk_sends')
            ->where('store_id', $storeId)
            ->select(
                'run_id',
                DB::raw('MAX(template) as template'),
                DB::raw('MAX(language) as language'),
                DB::raw('MAX(audience) as audience'),
                // LEFT(), not the bare column: body is TEXT, and a GROUP BY carrying a TEXT value
                // cannot use an in-memory temp table — MySQL spills the entire grouping to disk.
                // One row per recipient lives in here, so a store that has sent a few hundred
                // thousand messages was sorting all of them on disk to draw twenty rows. The
                // listing truncates to 110 characters anyway.
                DB::raw('MAX(LEFT(body, 200)) as body'),
                DB::raw('COUNT(*) as recipients'),
                DB::raw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent"),
                DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed"),
                DB::raw("SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) as queued"),
                DB::raw('MIN(sent_at) as started_at'),
                DB::raw('MAX(sent_at) as finished_at')
            )
            ->groupBy('run_id')
            ->orderByRaw('MIN(sent_at) DESC')
            ->paginate(20);

        $totals = DB::table('wa_bulk_sends')->where('store_id', $storeId)
            ->selectRaw("COUNT(*) as recipients,
                COUNT(DISTINCT run_id) as runs,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN sent_at >= ? THEN 1 ELSE 0 END) as last30", [now()->subDays(30)])
            ->first();

        return compact('runs', 'totals');
    }

    /**
     * One run, person by person: who it went to, what they were sent, and where it got to.
     *
     * Delivery state comes from whatsapp_messages, joined on the wamid Meta returned — the claim
     * row only knows the send was accepted, while the webhook is what later says delivered, read
     * or failed at the handset.
     */
    public function bulkHistoryRun(Request $request, $runId)
    {
        WhatsAppService::ensureBulkSendTable();
        WhatsAppService::ensureMessagesTable();
        $storeId = Helpers::get_store_id();

        $run = $this->ownedRun($storeId, $runId);
        if (!$run) {
            Toastr::error('That send could not be found.');
            return redirect()->route('vendor.whatsapp.bulk.history');
        }

        $masked = $run->audience === 'platform';

        $this->attachDeliveryCounts($run, $storeId);

        $query = DB::table('wa_bulk_sends as b')
            ->leftJoin('whatsapp_messages as m', function ($join) {
                $join->on('m.wamid', '=', 'b.wamid')->where('m.direction', 'out');
            })
            ->where('b.store_id', $storeId)
            ->where('b.run_id', $run->run_id);

        if ($status = $request->input('status')) {
            $status === 'delivered'
                ? $query->whereIn('m.status', ['delivered', 'read'])
                : $query->where('b.status', $status);
        }

        if ($search = trim((string) $request->input('search'))) {
            // Platform numbers are shown masked, so searching them would be a way to read one
            // back four digits at a time. Only the name is searchable for that audience.
            $query->where(function ($q) use ($search, $masked) {
                $q->where('b.name', 'like', "%{$search}%");
                if (!$masked) {
                    $q->orWhere('b.phone', 'like', "%{$search}%");
                }
            });
        }

        // Aliased, not `select *` — both tables carry a `status` column and the join would leave
        // only one of them standing, which is the difference between "we accepted it" and "the
        // handset got it".
        $rows = $query->orderBy('b.id')
            ->select(
                'b.name', 'b.phone', 'b.client_id', 'b.template', 'b.body', 'b.error', 'b.sent_at',
                'b.status as send_status',
                'm.status as delivery_status',
                'm.status_at as delivery_at'
            )
            ->paginate(50)
            ->appends($request->only('search', 'status'))
            ->through(function ($row) use ($masked) {
                // Masked here rather than in the view so no template can print the raw number of
                // someone who is not this vendor's contact.
                $row->phone = $masked ? $this->maskPhone($row->phone) : $row->phone;
                return $row;
            });

        return view('vendor-views.whatsapp.bulk-run', compact('run', 'rows', 'masked'));
    }

    /** The same run as a spreadsheet — what a vendor sends their accountant or their client. */
    public function bulkHistoryExport(Request $request, $runId)
    {
        WhatsAppService::ensureBulkSendTable();
        WhatsAppService::ensureMessagesTable();
        $storeId = Helpers::get_store_id();

        $run = $this->ownedRun($storeId, $runId);
        if (!$run) {
            Toastr::error('That send could not be found.');
            return redirect()->route('vendor.whatsapp.bulk.history');
        }

        $masked = $run->audience === 'platform';
        $filename = 'whatsapp-bulk-' . substr($run->run_id, 0, 8) . '-' . now()->format('Ymd-Hi') . '.csv';

        return response()->streamDownload(function () use ($storeId, $run, $masked) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Phone', 'Sent at', 'Template', 'Message sent', 'Result', 'Delivery', 'Error']);

            DB::table('wa_bulk_sends as b')
                ->leftJoin('whatsapp_messages as m', function ($join) {
                    $join->on('m.wamid', '=', 'b.wamid')->where('m.direction', 'out');
                })
                ->where('b.store_id', $storeId)
                ->where('b.run_id', $run->run_id)
                ->orderBy('b.id')
                ->select('b.name', 'b.phone', 'b.sent_at', 'b.template', 'b.body', 'b.status', 'b.error', 'm.status as delivery')
                ->chunk(500, function ($chunk) use ($out, $masked) {
                    foreach ($chunk as $row) {
                        fputcsv($out, [
                            $row->name ?: 'Customer',
                            $masked ? $this->maskPhone($row->phone) : $row->phone,
                            $row->sent_at,
                            $row->template,
                            $row->body,
                            $row->status,
                            $row->delivery ?: '—',
                            $row->error,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** One run's header, scoped to the store so a run id from elsewhere reads as not found. */
    private function ownedRun(int $storeId, string $runId)
    {
        return DB::table('wa_bulk_sends')
            ->where('store_id', $storeId)
            ->where('run_id', $runId)
            ->select(
                'run_id',
                DB::raw('MAX(template) as template'),
                DB::raw('MAX(language) as language'),
                DB::raw('MAX(audience) as audience'),
                DB::raw('MAX(body) as body'),
                DB::raw('COUNT(*) as recipients'),
                DB::raw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent"),
                DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed"),
                DB::raw("SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) as queued"),
                DB::raw('MIN(sent_at) as started_at'),
                DB::raw('MAX(sent_at) as finished_at')
            )
            ->groupBy('run_id')
            ->first();
    }

    /**
     * What the handsets reported back, counted onto the run header.
     *
     * Kept out of ownedRun() because these come from whatsapp_messages: the claim row only says
     * Meta accepted the send, the webhook is what later says delivered, read or failed. `read` is a
     * MySQL keyword, hence the aliases. Anything sent with no receipt yet is `awaiting`.
     */
    private function attachDeliveryCounts($run, int $storeId): void
    {
        $counts = DB::table('wa_bulk_sends as b')
            ->join('whatsapp_messages as m', function ($join) {
                $join->on('m.wamid', '=', 'b.wamid')->where('m.direction', 'out');
            })
            ->where('b.store_id', $storeId)
            ->where('b.run_id', $run->run_id)
            ->selectRaw("SUM(CASE WHEN m.status IN ('delivered','read') THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN m.status = 'read' THEN 1 ELSE 0 END) as read_count,
                SUM(CASE WHEN m.status = 'failed' THEN 1 ELSE 0 END) as undelivered_count")
            ->first();

        $run->delivered   = (int) ($counts->delivered_count ?? 0);
        $run->read        = (int) ($counts->read_count ?? 0);
        $run->undelivered = (int) ($counts->undelivered_count ?? 0);
        $run->awaiting    = max(0, (int) $run->sent - $run->delivered - $run->undelivered);
    }

    private function templateBodyError(string $body, ?string $name = null): ?string
    {
        return WhatsAppService::templateBodyProblem($body, $name);
    }

    /** Up to two buttons from the create form; blank rows are just unused slots. */
    private function templateButtons(Request $request): array
    {
        $buttons = [];
        foreach ((array) $request->input('tpl_btn', []) as $row) {
            $text = trim((string) ($row['text'] ?? ''));
            $type = strtoupper((string) ($row['type'] ?? ''));
            if ($text === '' || $type === '') {
                continue;
            }
            $buttons[] = [
                'type'  => $type,
                'text'  => $text,
                'url'   => trim((string) ($row['url'] ?? '')),
                'phone' => trim((string) ($row['phone'] ?? '')),
            ];
        }

        // Older single-button form fields, still posted by the admin panel.
        if (!$buttons && $request->filled('tpl_btn_text') && $request->filled('tpl_btn_url')) {
            $buttons[] = [
                'type' => 'URL',
                'text' => trim((string) $request->tpl_btn_text),
                'url'  => trim((string) $request->tpl_btn_url),
            ];
        }

        return $buttons;
    }

    /**
     * What Meta will refuse about this set of buttons, said in advance.
     *
     * Graph rejects a second call button (and a call button with no number) with a bare
     * "Invalid parameter", which tells the vendor nothing about which of the two rows to fix.
     * Mirrors templateBodyError(): checked before the submit, reported as a Toastr.
     */
    private function templateButtonError(array $buttons): ?string
    {
        $phoneButtons = array_values(array_filter($buttons, fn($b) => ($b['type'] ?? '') === 'PHONE_NUMBER'));

        if (count($phoneButtons) > 1) {
            return 'A template can carry only one "Call now" button. Remove one of them.';
        }

        foreach ($phoneButtons as $btn) {
            if (($btn['phone'] ?? '') === '') {
                return 'Enter the phone number the "Call now" button should dial.';
            }
            if (strlen(preg_replace('/[^0-9]/', '', $btn['phone']) ?? '') < 10) {
                return 'That "Call now" number does not look complete — enter the full number including area or country code.';
            }
        }

        foreach ($buttons as $btn) {
            if (($btn['type'] ?? '') === 'URL' && ($btn['url'] ?? '') === '') {
                return 'Enter the web address the link button should open.';
            }
        }

        return null;
    }

    /**
     * The template's header: a string for TEXT, or ['format','handle'] once the media file has
     * been uploaded to Meta. Returns null for no header, or false when the upload failed — the
     * caller bails in that case rather than submitting a template with a missing image.
     */
    private function templateHeader(Request $request, WhatsAppService $wa)
    {
        $format = strtoupper((string) $request->input('tpl_header_format', ''));

        if ($format === '' || $format === 'TEXT') {
            return trim((string) $request->input('tpl_header', '')) ?: null;
        }

        $file = $request->file('tpl_header_file');
        if (!$file || !$file->isValid()) {
            Toastr::error('Choose a file for the ' . strtolower($format) . ' header, or set the header back to None.');
            return false;
        }

        $config = Helpers::get_business_settings('whatsapp_config');
        $appId = $config['es_app_id'] ?? null;
        $appSecret = $config['es_app_secret'] ?? null;
        if (!$appId || !$appSecret) {
            Toastr::error('Media headers need the WhatsApp app credentials. Ask the admin to set them up.');
            return false;
        }

        $handle = $wa->uploadHeaderMedia($file->getRealPath(), $file->getMimeType(), $appId, $appSecret);
        if (!$handle) {
            Toastr::error('Could not upload that file to Meta. Check the size and format, then try again.');
            return false;
        }

        return ['format' => $format, 'handle' => $handle];
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
        // Razorpay's mandate page returns here with razorpay_subscription_id &co, and the one-off
        // gateway with ?flag= — so this still has to say what happened before handing over to the
        // merged page. The mandate itself is confirmed by the subscription.activated webhook, not
        // by this redirect, so the wording promises nothing the page can't back up.
        if ($request->has('razorpay_subscription_id') || $request->has('flag')) {
            $request->query('flag') === 'fail'
                ? Toastr::error('That payment didn’t go through, so nothing was charged. You can try again.')
                : Toastr::success('Thanks — Razorpay has your authorisation. This page updates the moment the first month is collected.');
        }

        return redirect()->route('vendor.whatsapp.connect', ['tab' => 'billing']);
    }

    /** Subscription state, AI token balance, template slots and the charge history. */
    private function billingData(): array
    {
        $storeId = Helpers::get_store_id();
        WhatsAppBilling::ensureTables();

        $subscription = WhatsAppBilling::subscription($storeId);

        // A mandate can be live at Razorpay while we never heard about it — the webhook was
        // added after the fact, or a delivery failed and its retries expired. Ask Razorpay
        // directly whenever the store looks inactive but has a subscription id, so the screen
        // can correct itself instead of the vendor re-authorising something they already have.
        if ($subscription && $subscription->rzp_subscription_id && !WhatsAppBilling::isActive($storeId)) {
            if (WhatsAppRecurring::reconcile($storeId)) {
                $subscription = WhatsAppBilling::subscription($storeId);
            }
        }

        $currentPlan = WhatsAppBilling::storePlan($storeId);

        $pricing = [
            'setup'          => WhatsAppBilling::setupFee(),
            'setup_total'    => WhatsAppBilling::withTax(WhatsAppBilling::setupFee()),
            'manager'        => WhatsAppBilling::accountManagerFee(),
            'manager_total'  => WhatsAppBilling::withTax(WhatsAppBilling::accountManagerFee()),
            'template_slot'  => WhatsAppBilling::extraTemplateFee(),
            'template_total' => WhatsAppBilling::withTax(WhatsAppBilling::extraTemplateFee()),
            'topup_in'       => WhatsAppBilling::topupPerMillion(WhatsAppBilling::DIR_IN),
            'topup_in_total' => WhatsAppBilling::withTax(WhatsAppBilling::topupPerMillion(WhatsAppBilling::DIR_IN)),
            'topup_out'      => WhatsAppBilling::topupPerMillion(WhatsAppBilling::DIR_OUT),
            'topup_out_total' => WhatsAppBilling::withTax(WhatsAppBilling::topupPerMillion(WhatsAppBilling::DIR_OUT)),
            'gst'            => WhatsAppBilling::gstPercent(),
        ];

        return [
            'subscription'  => $subscription,
            'active'        => WhatsAppBilling::isActive($storeId),
            'plans'         => WhatsAppBilling::plans(),
            'currentPlan'   => $currentPlan,
            'planMeta'      => WhatsAppBilling::plan($currentPlan),
            'hasPlan'       => WhatsAppBilling::hasPlan($storeId),
            'botIncluded'   => WhatsAppBilling::botIncluded($storeId),
            'pricing'       => $pricing,
            'tokens'        => $this->poolStats($storeId, WhatsAppBilling::POOL_PLAN),
            'allowance'     => WhatsAppBilling::templateAllowance($storeId),
            'included'      => WhatsAppBilling::includedTemplates(),
            'walletBalance' => WhatsAppBilling::walletBalance($storeId),
            'invoices'      => WhatsAppBilling::invoices($storeId, 25),
            'graceDays'     => WhatsAppBilling::GRACE_DAYS,
            'usage'         => WhatsAppBilling::usageThisMonth($storeId),
            'feeOwn'        => WhatsAppBilling::messageFeeOwn(),
            'feePlatform'   => WhatsAppBilling::messageFeePlatform(),
        ];
    }

    /**
     * Balance / granted / used figures for one token pool, split by direction. Input and output
     * are separate buckets, so the page has to show them apart or a vendor stuck on one of them
     * cannot tell which to top up.
     */
    private function poolStats(int $storeId, string $pool): array
    {
        $row   = WhatsAppBilling::tokenWallet($storeId, $pool);
        $month = DB::table('wa_token_usage')->where('store_id', $storeId)
            ->where('pool', $pool)
            ->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('COALESCE(SUM(tokens_in), 0) AS used_in, COALESCE(SUM(tokens_out), 0) AS used_out')
            ->first();

        $stats = [];
        foreach ([WhatsAppBilling::DIR_IN => 'in', WhatsAppBilling::DIR_OUT => 'out'] as $dir => $d) {
            $stats[$d] = [
                'balance'    => WhatsAppBilling::tokenBalance($storeId, $dir, $pool),
                'plan'       => (int) ($row->{"plan_tokens_{$d}"} ?? 0),
                'plan_used'  => (int) ($row->{"plan_tokens_{$d}_used"} ?? 0),
                'topup'      => (int) ($row->{"topup_tokens_{$d}"} ?? 0),
                'topup_used' => (int) ($row->{"topup_tokens_{$d}_used"} ?? 0),
                'this_month' => (int) ($month->{"used_{$d}"} ?? 0),
            ];
        }

        return $stats;
    }

    /** Chatbot settings — what the AI Agent may do and what it may tell customers. */
    public function bot(Request $request)
    {
        return redirect()->route('vendor.whatsapp.automation', ['tab' => 'chatbot']);
    }

    /** What the AI Agent is allowed to do, and whether the plan includes it at all. */
    private function botData(): array
    {
        $storeId = Helpers::get_store_id();

        return [
            'botIncluded'  => WhatsAppBilling::botIncluded($storeId),
            'agentActive'  => WhatsAppBilling::agentActive($storeId),
            'subscription' => WhatsAppBilling::subscription($storeId),
            'currentPlan'  => WhatsAppBilling::storePlan($storeId),
            'hasPlan'      => WhatsAppBilling::hasPlan($storeId),
            'plans'        => WhatsAppBilling::plans(),
            'shares'       => WhatsAppAgent::shares($storeId),
            'shareItems'   => WhatsAppAgent::SHARE_ITEMS,
            'gst'          => WhatsAppBilling::gstPercent(),
        ];
    }

    public function botShares(Request $request)
    {
        $storeId = Helpers::get_store_id();

        if (!WhatsAppBilling::agentActive($storeId)) {
            Toastr::error('Your plan has no AI Agent. Move to AI Agent Starter or Pro from Plan & Billing first.');
            return back();
        }

        WhatsAppAgent::saveShares($storeId, (array) $request->input('items', []));
        Toastr::success('Saved. The AI Agent will only do and discuss what you have allowed.');
        return back();
    }

    /**
     * Step 1 of activation: the one-time onboarding fee, collected by the payment gateway rather
     * than the wallet.
     *
     * No plan is chosen here on purpose — the vendor picks one at step 2, once the fee has
     * landed, and billingAuthorizeMandate() takes it from there. The plan recorded against the
     * store until then is only a placeholder; hasPlan() reports false and the UI says so.
     */
    public function billingSubscribe(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $sub     = WhatsAppBilling::subscription($storeId);

        if ($sub && $sub->setup_fee_paid) {
            Toastr::info('Your onboarding fee is already paid — choose a plan to switch WhatsApp on.');
            return back();
        }

        return $this->redirectToSetupPayment(
            $storeId,
            WhatsAppBilling::DEFAULT_PLAN,
            $request->boolean('account_manager'),
            $request
        );
    }

    /**
     * Create the Razorpay subscription and hand back the mandate authorisation.
     *
     * Asked over AJAX it returns the subscription id, so the page can open Razorpay Checkout
     * in a modal and keep the vendor on MyChitti — the Subscriptions API has no callback_url,
     * so a plain redirect to the hosted page strands them on Razorpay once they authorise.
     * The redirect remains the fallback for a normal form post.
     */
    public function billingAuthorizeMandate(Request $request)
    {
        $request->validate(['plan' => 'required|in:' . implode(',', array_keys(WhatsAppBilling::PLANS))]);

        $storeId = Helpers::get_store_id();
        $plan    = $request->plan;
        $accountManager = $request->boolean('account_manager');

        if (!$request->expectsJson()) {
            return $this->startMandate($storeId, $plan, $accountManager);
        }

        $res = WhatsAppRecurring::start($storeId, $plan, $accountManager);
        if (!$res['success']) {
            return response()->json(['success' => false, 'message' => $res['message']], 422);
        }

        return response()->json([
            'success'         => true,
            'subscription_id' => $res['id'],
            'key'             => WhatsAppRecurring::publicKey(),
            'url'             => $res['url'],
        ]);
    }

    private function startMandate(int $storeId, string $plan, bool $accountManager)
    {
        $res = WhatsAppRecurring::start($storeId, $plan, $accountManager);
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

        // Onboarding is charged before a plan is chosen, so the cheapest tier is recorded as the
        // intent; the vendor picks their plan on Plan & Billing when they authorise the mandate.
        return $this->redirectToSetupPayment($storeId, WhatsAppBilling::DEFAULT_PLAN, false, $request, 'whatsapp/connect');
    }

    /**
     * Hand the vendor to Razorpay for a one-time purchase — AI token top-ups or an extra
     * template slot. whatsapp_purchase_success() grants the goods once the gateway confirms;
     * nothing is handed over on our side before the money arrives.
     */
    private function redirectToPurchasePayment(int $storeId, string $kind, int $quantity, Request $request)
    {
        $vendor = \App\Models\Vendor::find(auth('vendor')->id());
        if (!$vendor) {
            Toastr::error('Could not identify your vendor account.');
            return back();
        }

        $intent = WhatsAppBilling::createPurchaseIntent($storeId, $kind, $quantity);
        if (!$intent) {
            Toastr::error('That purchase is not available.');
            return back();
        }

        $payer = new Payer(
            trim($vendor->f_name . ' ' . $vendor->l_name),
            $vendor->email,
            $vendor->phone,
            ''
        );

        $external_redirect_link = \Illuminate\Support\Str::contains($request->getHost(), 'staging')
            ? 'store-panel/whatsapp/billing'
            : 'whatsapp/billing';

        $payment_info = new PaymentInfo(
            success_hook: 'whatsapp_purchase_success',
            failure_hook: 'whatsapp_purchase_fail',
            currency_code: BusinessSetting::where('key', 'currency')->value('value'),
            payment_method: 'razor_pay',
            payment_platform: 'web',
            payer_id: $storeId,
            receiver_id: 100,
            additional_data: [
                'business_name' => BusinessSetting::where('key', 'business_name')->value('value'),
                'business_logo' => asset('storage/app/public/business') . '/' . BusinessSetting::where('key', 'logo')->value('value'),
            ],
            payment_amount: $intent['total'],
            external_redirect_link: $external_redirect_link,
            attribute: 'whatsapp_purchase',
            attribute_id: $intent['id']
        );

        return redirect()->to($this->generate_link($payer, $payment_info, new Receiver('Admin', 'example.png')));
    }

    /** Hand the vendor to Razorpay for the setup fee; whatsapp_setup_success() resumes activation. */
    private function redirectToSetupPayment(int $storeId, string $plan, bool $accountManager, Request $request, string $returnTo = 'whatsapp/billing')
    {
        $vendor = \App\Models\Vendor::find(auth('vendor')->id());
        if (!$vendor) {
            Toastr::error('Could not identify your vendor account.');
            return back();
        }

        $tmp = TmpWhatsAppSetup::create([
            'store_id'        => $storeId,
            'plan'            => $plan,
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

    // One-time purchase of message-template slots beyond the included quota.
    public function buyTemplateSlot(Request $request)
    {
        $request->validate(['slots' => 'nullable|integer|min:1|max:50']);

        return $this->redirectToPurchasePayment(
            Helpers::get_store_id(),
            'template_slot',
            (int) ($request->slots ?: 1),
            $request
        );
    }

    /**
     * AI token top-up, paid by card / UPI. Starter and Pro only, and priced per direction —
     * input and output are separate buckets that never lend to each other.
     */
    public function buyTokens(Request $request)
    {
        $request->validate([
            'direction' => 'required|in:in,out',
            'millions'  => 'nullable|integer|min:1|max:50',
        ]);

        return $this->redirectToPurchasePayment(
            Helpers::get_store_id(),
            'topup_tokens_' . $request->direction,
            (int) ($request->millions ?: 1),
            $request
        );
    }

    /**
     * Template quota gate. Returns an error message when the store already has as many
     * templates as it has paid for, or null when it may create another.
     *
     * A failed listTemplates call never blocks the vendor — we only enforce on a count we
     * actually read back from Meta.
     */
    /**
     * A throwaway one-page PDF for Meta's reviewers, uploaded to get the handle a DOCUMENT header
     * needs at template creation. It is only ever the sample shown in review — real sends replace
     * it with the patient's own file.
     *
     * Returns ['format' => 'DOCUMENT', 'handle' => …], or false when it could not be produced (the
     * caller has already flashed the reason).
     */
    private function sampleDocumentHeader(WhatsAppService $wa)
    {
        $config = Helpers::get_business_settings('whatsapp_config');
        $appId = $config['es_app_id'] ?? null;
        $appSecret = $config['es_app_secret'] ?? null;
        if (!$appId || !$appSecret) {
            Toastr::error('Templates with an attached file need the WhatsApp app credentials. Ask the admin to set them up.');
            return false;
        }

        try {
            $dir = storage_path('app/tmp');
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $path = $dir . '/wa-sample-' . uniqid() . '.pdf';

            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $dir]);
            $mpdf->WriteHTML(
                '<h2 style="font-family:sans-serif;">Sample document</h2>'
                . '<p style="font-family:sans-serif;font-size:13px;">This is a sample attachment for template review. '
                . 'Each message sends the patient their own document in its place.</p>'
            );
            $mpdf->Output($path, 'F');

            $handle = $wa->uploadHeaderMedia($path, 'application/pdf', $appId, $appSecret);
            @unlink($path);

            if (!$handle) {
                Toastr::error('Could not upload the sample file to Meta. Please try again in a moment.');
                return false;
            }

            return ['format' => 'DOCUMENT', 'handle' => $handle];
        } catch (\Throwable $e) {
            Log::error('WA sample document header failed: ' . $e->getMessage());
            Toastr::error('Could not prepare the sample file for this template.');
            return false;
        }
    }

    /**
     * The sample picture Meta reviews an IMAGE-header preset against.
     *
     * A placeholder that ships with the app, not the store's own picture: this is only what the
     * reviewer sees, and every message sends the store's own image in its place (resolved per
     * send by WhatsAppService::headerImageUrl()). Using a placeholder also keeps the preset a
     * genuine one-click submit for a store that has not uploaded anything yet.
     */
    private function sampleImageHeader(WhatsAppService $wa)
    {
        $config = Helpers::get_business_settings('whatsapp_config');
        $appId = $config['es_app_id'] ?? null;
        $appSecret = $config['es_app_secret'] ?? null;
        if (!$appId || !$appSecret) {
            Toastr::error('Templates with a picture need the WhatsApp app credentials. Ask the admin to set them up.');
            return false;
        }

        try {
            $path = public_path('assets/admin/img/900x400/img1.jpg');
            if (!is_file($path)) {
                Toastr::error('The sample picture is missing from this installation.');
                return false;
            }

            $handle = $wa->uploadHeaderMedia($path, 'image/jpeg', $appId, $appSecret);
            if (!$handle) {
                Toastr::error('Could not upload the sample picture to Meta. Please try again in a moment.');
                return false;
            }

            return ['format' => 'IMAGE', 'handle' => $handle];
        } catch (\Throwable $e) {
            Log::error('WA sample image header failed: ' . $e->getMessage());
            Toastr::error('Could not prepare the sample picture for this template.');
            return false;
        }
    }

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
        WhatsAppService::ensureNumbersTable();
        $storeId = Helpers::get_store_id();

        // The cap counts numbers the store already owns, but reconnecting one it owns is a token
        // refresh rather than a new number — Meta returns the same phone_number_id — so that case
        // is let through even at the limit. Otherwise an expired token would be unfixable.
        $limit = WhatsAppService::numberLimit($storeId);
        if ($limit > 0) {
            $owned = collect(WhatsAppService::numbers($storeId));
            $isReconnect = $owned->contains('phone_number_id', $request->phone_number_id);

            if (!$isReconnect && $owned->count() >= $limit) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have connected the maximum of ' . $limit . ' WhatsApp '
                        . ($limit === 1 ? 'number' : 'numbers') . '. Disconnect one before adding another.',
                ], 422);
            }
        }

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

            // 4) Ask Meta what this number actually is, so the vendor sees a phone number in the
            // list rather than an opaque id. Cosmetic — a failure here must not fail the connect.
            $display = null;
            $verified = null;
            try {
                $info = Http::withToken($token)->get("https://graph.facebook.com/{$version}/{$request->phone_number_id}", [
                    'fields' => 'display_phone_number,verified_name',
                ]);
                if ($info->successful()) {
                    $display  = data_get($info->json(), 'display_phone_number');
                    $verified = data_get($info->json(), 'verified_name');
                }
            } catch (\Throwable $e) {
                Log::info('WA ES number lookup failed: ' . $e->getMessage());
            }

            // 5) Persist. saveNumber() mirrors the default back onto stores.wa_*, so everything
            // written against the single-number columns keeps working.
            WhatsAppService::saveNumber($storeId, [
                'phone_number_id'     => $request->phone_number_id,
                'business_account_id' => $request->waba_id,
                'token'               => $token,
                'api_version'         => $version,
                'display_phone'       => $display,
                'verified_name'       => $verified,
                'label'               => $request->input('label'),
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

        // This page reads the live list from Meta anyway, so it is the natural place to refresh
        // the short-lived status cache the notification settings page reads. It also makes the
        // page a way to clear a stale warning: a vendor told a template isn't approved opens
        // Message Templates to look, and that visit re-checks it.
        WhatsAppService::forgetTemplateStatuses($storeId);

        // Vendor's own WABA only — hasWaba() would be true on the platform fallback and list
        // MyChitti's templates here, with working edit and delete buttons beside them.
        $connected = $wa->hasVendorWaba();
        $templates = [];
        $trashed = [];
        $templateError = null;
        $presets = collect();
        if ($connected) {
            $res = $wa->listTemplates();
            $templates = $res['data'];
            if (!$res['success']) {
                $templateError = $res['error'];
            }

            // Trashed templates are still live at Meta and still hold a slot — they are just
            // out of the way. Split them off so the working list stays about what's in use.
            $trashedKeys = WhatsAppService::trashedTemplateKeys($storeId);
            if ($trashedKeys) {
                $all = $templates;
                $templates = [];
                foreach ($all as $tpl) {
                    $key = strtolower(data_get($tpl, 'name') . '|' . data_get($tpl, 'language', 'en_US'));
                    in_array($key, $trashedKeys, true) ? $trashed[] = $tpl : $templates[] = $tpl;
                }
            }

            // Admin-suggested presets, annotated with this vendor's WABA status so a preset
            // already submitted shows its review state instead of a second "Use" button.
            $statusByName = [];
            foreach ($templates as $tpl) {
                $statusByName[strtolower((string) data_get($tpl, 'name'))] = strtoupper((string) data_get($tpl, 'status'));
            }
            // The hospital templates are only offered to stores that actually run the hospital
            // module — a laundry has no use for "Consultation Summary", and a suggested-list full
            // of templates a vendor can never send is how the list stops being read at all.
            $hospital = vendorPlanHasModule('hospital_manage');

            $presets = WhatsAppService::templatePresets()
                ->filter(fn($p) => $hospital || !array_key_exists($p->name, HmisWhatsAppShare::PRESET_USES))
                ->map(function ($p) use ($statusByName) {
                    $p->waba_status = $statusByName[strtolower($p->name)] ?? null;
                    // Says where the template gets used, so a vendor picks by what it does for
                    // them rather than by guessing from the wording.
                    $p->used_for = HmisWhatsAppShare::PRESET_USES[$p->name] ?? null;
                    return $p;
                })
                ->values();
        }

        // Hours before the appointment that the reminder goes out; 0 = off, unset = 2 (default).
        $apptRaw = DB::table('stores')->where('id', $storeId)->value('wa_appt_reminder');
        $apptReminder = ($apptRaw === null || $apptRaw === '') ? WhatsAppService::DEFAULT_APPT_REMINDER_HOURS : (int) $apptRaw;

        // Template quota: the included allowance from the plan, plus any slots the vendor bought.
        $quota = [
            'included'  => WhatsAppBilling::includedTemplates(),
            'allowance' => WhatsAppBilling::templateAllowance($storeId),
            // Trashed templates still exist at Meta and still occupy a slot, so they count.
            'used'      => count($templates) + count($trashed),
            'slot_fee'  => WhatsAppBilling::withTax(WhatsAppBilling::extraTemplateFee()),
        ];

        // Prefilled into a "Call now" button: a vendor adding one is almost always handing out
        // their own line, and a number typed without a country code is what Meta rejects.
        $storePhone = (string) (DB::table('stores')->where('id', $storeId)->value('phone') ?? '');

        // What an image-header template is actually sent with, so the preview shows the real
        // picture rather than leaving the vendor to guess which one Meta will fetch.
        $headerImage = WhatsAppService::headerImageUrl($storeId);

        return view('vendor-views.whatsapp.templates', compact('connected', 'templates', 'trashed', 'templateError', 'presets', 'apptReminder', 'quota', 'storePhone', 'headerImage'));
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
        // The vendor just changed their template set; the settings page must not keep
        // warning (or reassuring) from a stale copy.
        WhatsAppService::forgetTemplateStatuses(Helpers::get_store_id());
        $request->validate(['preset_id' => 'required|integer']);

        $wa = WhatsAppService::make(Helpers::get_store_id());
        if (!$wa->hasVendorWaba()) {
            Toastr::error('Connect your own WhatsApp number first — templates are created on your WABA, not MyChitti\'s.');
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
        // A call button on a preset almost always means "ring this vendor", so the number is
        // resolved per store at submit time rather than baked into the preset.
        if ($call = WhatsAppService::presetCallButton($preset, Helpers::get_store_id())) {
            $buttons[] = $call;
        }
        // Quick replies come back as their own label when tapped, which is what makes a preset
        // able to ask a question rather than only link somewhere.
        foreach (array_filter(array_map('trim', explode(',', (string) ($preset->btn_replies ?? '')))) as $reply) {
            $buttons[] = ['type' => 'QUICK_REPLY', 'text' => $reply];
        }

        // A media template needs a sample file uploaded to Meta before it can be created. Asking
        // the vendor to produce one would end the "one click" promise, so the platform generates
        // a representative sample and uploads it for them.
        $header = $preset->header;
        $headerFormat = strtoupper((string) ($preset->header_format ?? ''));
        if ($headerFormat === 'DOCUMENT') {
            $header = $this->sampleDocumentHeader($wa);
            if ($header === false) {
                return back();
            }
        } elseif ($headerFormat === 'IMAGE') {
            $header = $this->sampleImageHeader($wa);
            if ($header === false) {
                return back();
            }
        }

        $res = $wa->createTemplate(
            $preset->name,
            $preset->category,
            $preset->language ?: 'en_US',
            $preset->body,
            $example,
            $buttons,
            $header,
            $preset->footer
        );

        // Meta reserves a DELETED template's name for 30 days, so a vendor who removed this
        // template once cannot take the preset again until the reservation lapses — and the
        // automation behind it stays dead for the whole of that time. Rather than leave them at a
        // dead end for up to a month, submit the same content under the next free name.
        $submittedAs = $preset->name;
        $reboundRole = null;
        if (!$res['success'] && $this->templateNameReserved($res['error'] ?? null)) {
            $alternative = $this->nextFreeTemplateName(Helpers::get_store_id(), $preset->name);
            if ($alternative) {
                $retry = $wa->createTemplate(
                    $alternative,
                    $preset->category,
                    $preset->language ?: 'en_US',
                    $preset->body,
                    $example,
                    $buttons,
                    $header,
                    $preset->footer
                );
                if ($retry['success']) {
                    $res = $retry;
                    $submittedAs = $alternative;
                }
            }
        }

        if ($res['success']) {
            // The store's template set just changed; templateExists() reads a cached list and
            // would not see the new name (or the new binding's target) for another five minutes.
            WhatsAppService::forgetTemplateStatuses(Helpers::get_store_id());

            // Roles resolve by name, so any template not named after the role's default - the
            // picture variant, or a rename forced by Meta's hold - is invisible to the automation
            // until the role points at it.
            $reboundRole = $this->bindRoleForTemplate(
                Helpers::get_store_id(),
                $submittedAs,
                $preset->language ?: 'en_US'
            );

            $note = '"' . $preset->title . '" submitted to Meta for review on your WhatsApp account.';
            if ($reboundRole && $submittedAs === $preset->name) {
                $note .= ' Your "' . $reboundRole . '" automation now uses it.';
            }
            if ($submittedAs !== $preset->name) {
                $note .= ' WhatsApp still reserves the name "' . $preset->name . '" from when it was'
                    . ' deleted, so it went in as "' . $submittedAs . '".';
                $note .= $reboundRole
                    ? ' Your "' . $reboundRole . '" automation now points at the new name, so nothing else to do.'
                    : ' Point the matching automation at it on the Template Roles screen.';
            }
            Toastr::success($note . ' Approval usually takes a few minutes but can take up to 24 hours — you can send with it once its status shows APPROVED.');
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

    /**
     * Is this Meta refusing the name because the old template of that name is still being deleted?
     *
     * Deleting a template does not free its name — Meta holds the name and language for 30 days
     * and answers a resubmit with "Message template language is being deleted … Try again in N
     * days or consider creating a new message template". Matched on the wording rather than a
     * code: Graph reports this through more than one subcode, and the sentence is the stable part.
     */
    private function templateNameReserved(?string $error): bool
    {
        return str_contains(strtolower((string) $error), 'being deleted');
    }

    /**
     * The preset's name with the lowest _vN suffix this store is not already using.
     *
     * Only checks names that exist now — a suffix whose own template was deleted is still inside
     * its own 30-day reservation and cannot be told apart from a free one, so the retry can still
     * be refused. That is reported to the vendor rather than retried around.
     */
    private function nextFreeTemplateName(int $storeId, string $base): ?string
    {
        $taken = WhatsAppService::templateStatuses($storeId);

        for ($n = 2; $n <= 9; $n++) {
            $candidate = $base . '_v' . $n;
            if (!isset($taken[strtolower($candidate)])) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Point the automation at a template submitted under something other than the role's default
     * name. Returns the role's label so the vendor can be told, or null if it drives no role.
     *
     * Variant suffixes are stripped before matching: "_image" is the version whose header carries
     * a picture, "_vN" a resubmit forced by Meta's 30-day hold on a deleted name. Both are still
     * the same automation, and without the binding a renamed template is one the code never finds.
     */
    private function bindRoleForTemplate(int $storeId, string $submittedName, string $language): ?string
    {
        $base = preg_replace('/(_image)?(_v\d+)?$/', '', strtolower($submittedName));

        foreach (WhatsAppService::TEMPLATE_ROLES as $role => $meta) {
            $default = strtolower((string) ($meta['default'] ?? ''));
            if ($default === '' || $default !== $base) {
                continue;
            }
            // Binding a role to the name it already defaults to is a row that changes nothing.
            if ($default === strtolower($submittedName)) {
                return null;
            }
            WhatsAppService::bindTemplate($storeId, $role, $submittedName, $language);
            return $meta['label'] ?? $role;
        }

        return null;
    }

    public function templateCreate(Request $request)
    {
        // The vendor just changed their template set; the settings page must not keep
        // warning (or reassuring) from a stale copy.
        WhatsAppService::forgetTemplateStatuses(Helpers::get_store_id());
        $request->validate([
            'tpl_name'        => 'required|regex:/^[a-z0-9_]+$/',
            'tpl_category'    => 'required',
            'tpl_body'        => 'required',
            'tpl_header_format' => 'nullable|in:TEXT,IMAGE,DOCUMENT,VIDEO',
            'tpl_header_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf,mp4|max:16384',
            'tpl_btn'         => 'nullable|array|max:2',
            'tpl_btn.*.type'  => 'nullable|in:URL,QUICK_REPLY',
            'tpl_btn.*.text'  => 'nullable|string|max:25',
            'tpl_btn.*.url'   => 'nullable|url|max:2000',
        ], [
            'tpl_name.regex' => 'Template name must be lowercase letters, numbers and underscores only.',
            'tpl_header_file.max' => 'Header files must be 16 MB or smaller.',
        ]);

        $wa = WhatsAppService::make(Helpers::get_store_id());
        if (!$wa->hasVendorWaba()) {
            Toastr::error('Connect your own WhatsApp number first — templates are created on your WABA, not MyChitti\'s.');
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
        $buttons = $this->templateButtons($request);

        if ($buttonError = $this->templateButtonError($buttons)) {
            Toastr::error($buttonError);
            return back()->withInput();
        }

        $header = $this->templateHeader($request, $wa);
        if ($header === false) {
            return back()->withInput();
        }

        // English (US) only — the form no longer offers a choice, so it is not read from the
        // request at all. Existing templates keep whatever language they were created in; that
        // still travels on the delete and sync forms, which Meta matches a template by.
        $res = $wa->createTemplate(
            trim((string) $request->tpl_name),
            $request->tpl_category,
            'en_US',
            (string) $request->tpl_body,
            $example,
            $buttons,
            $header
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
        // The vendor just changed their template set; the settings page must not keep
        // warning (or reassuring) from a stale copy.
        WhatsAppService::forgetTemplateStatuses(Helpers::get_store_id());
        $request->validate([
            'tpl_id'   => 'required',
            'tpl_body' => 'required',
        ]);

        $wa = WhatsAppService::make(Helpers::get_store_id());
        if (!$wa->hasVendorWaba()) {
            Toastr::error('Connect your own WhatsApp number first — templates are created on your WABA, not MyChitti\'s.');
            return back();
        }

        if ($bodyError = $this->templateBodyError((string) $request->tpl_body, $request->tpl_name)) {
            Toastr::error($bodyError);
            return back()->withInput();
        }

        $example = array_values(array_filter(array_map('trim', explode('|', (string) $request->tpl_example)), fn($v) => $v !== ''));

        // The same rows the create form posts. This used to read only the legacy single-URL
        // hidden fields, so anything the vendor typed into the visible button rows on the edit
        // modal — a quick reply, a call button — was silently thrown away on save.
        // templateButtons() still falls back to those hidden fields when the rows are empty.
        $buttons = $this->templateButtons($request);

        if ($buttonError = $this->templateButtonError($buttons)) {
            Toastr::error($buttonError);
            return back()->withInput();
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

    /**
     * Move a template to the trash. Nothing is deleted at Meta — the template stays approved and
     * keeps its slot, which is why it still counts against the quota and why Restore is instant.
     */
    public function templateTrash(Request $request)
    {
        // The vendor just changed their template set; the settings page must not keep
        // warning (or reassuring) from a stale copy.
        WhatsAppService::forgetTemplateStatuses(Helpers::get_store_id());
        $request->validate(['name' => 'required', 'language' => 'nullable|string|max:20']);
        $storeId = Helpers::get_store_id();

        WhatsAppService::ensureTrashTable();
        DB::table('wa_trashed_templates')->updateOrInsert(
            [
                'store_id' => $storeId,
                'name'     => trim((string) $request->name),
                'language' => trim((string) $request->language) ?: 'en_US',
            ],
            ['updated_at' => now(), 'created_at' => now()]
        );

        Toastr::success('Moved to trash. It still uses one of your template slots until you delete it permanently.');
        return back();
    }

    /** Put a trashed template back in the working list. No re-approval — it never left Meta. */
    public function templateRestore(Request $request)
    {
        // The vendor just changed their template set; the settings page must not keep
        // warning (or reassuring) from a stale copy.
        WhatsAppService::forgetTemplateStatuses(Helpers::get_store_id());
        $request->validate(['name' => 'required', 'language' => 'nullable|string|max:20']);

        WhatsAppService::ensureTrashTable();
        DB::table('wa_trashed_templates')
            ->where('store_id', Helpers::get_store_id())
            ->where('name', trim((string) $request->name))
            ->where('language', trim((string) $request->language) ?: 'en_US')
            ->delete();

        Toastr::success('Template restored.');
        return back();
    }

    /** The only path that touches Meta: the template is gone and the slot is freed. */
    public function templateDelete(Request $request)
    {
        // The vendor just changed their template set; the settings page must not keep
        // warning (or reassuring) from a stale copy.
        WhatsAppService::forgetTemplateStatuses(Helpers::get_store_id());
        $request->validate(['name' => 'required', 'language' => 'nullable|string|max:20']);
        $storeId = Helpers::get_store_id();
        $name = trim((string) $request->name);

        $wa = WhatsAppService::make($storeId);

        // Without this the delete lands on whatever WABA resolveConfig() fell back to. For an
        // unconnected vendor that is the platform account, so a click here would permanently
        // remove a MyChitti template at Meta — vendor_lead_alert5 and friends are shared by
        // every store on the platform.
        if (!$wa->hasVendorWaba()) {
            Toastr::error('Connect your own WhatsApp number first — these are not your templates.');
            return back();
        }

        $res = $wa->deleteTemplate($name);

        if ($res['success']) {
            WhatsAppService::ensureTrashTable();
            DB::table('wa_trashed_templates')
                ->where('store_id', $storeId)->where('name', $name)->delete();
            Toastr::success('Template permanently deleted. That slot is free again, but the NAME "'
                . $name . '" is reserved by Meta for ' . WhatsAppService::TEMPLATE_NAME_LOCK_DAYS
                . ' days — a new template must use a different name.');

            // Deleting the template an automation depends on stops those messages dead, and
            // nothing else would say so — the sends simply stop and are noticed weeks later.
            if ($orphaned = $this->rolesLeftWithout($storeId, $name)) {
                Toastr::warning('That template was sending your ' . implode(' and ', $orphaned)
                    . ' messages. They have stopped — create a replacement and pick it under Automatic Messages.');
            }
        } else {
            Toastr::error('Delete failed: ' . $res['error']);
        }
        return back();
    }

    /**
     * Disconnect one number, or the whole account when no number is named.
     *
     * The no-argument form is what the old single-number button posts, so it still means
     * "disconnect everything" — anything else would quietly leave numbers connected.
     */
    public function disconnect(Request $request)
    {
        WhatsAppService::ensureStoreColumns();
        WhatsAppService::ensureNumbersTable();
        $storeId = Helpers::get_store_id();

        if ($request->filled('number_id')) {
            $number = DB::table('wa_numbers')->where('store_id', $storeId)
                ->where('id', $request->number_id)->first();

            if (!$number) {
                Toastr::error('That number is not connected to your store.');
                return back();
            }

            WhatsAppService::removeNumber($storeId, (int) $request->number_id);
            Toastr::success(($number->display_phone ?: 'The number') . ' has been disconnected.');
            return back();
        }

        DB::table('wa_numbers')->where('store_id', $storeId)->delete();
        DB::table('wa_number_bindings')->where('store_id', $storeId)->delete();
        DB::table('stores')->where('id', $storeId)->update([
            'wa_enabled'             => 0,
            'wa_phone_number_id'     => null,
            'wa_token'               => null,
            'wa_business_account_id' => null,
        ]);
        Toastr::success('WhatsApp disconnected.');
        return back();
    }

    /** Manage the store's connected numbers and what each one is in charge of sending. */
    public function numbers(Request $request)
    {
        return redirect()->route('vendor.whatsapp.connect', ['tab' => 'numbers']);
    }

    /** The connected numbers and which kind of message each one sends. */
    private function numbersData(): array
    {
        WhatsAppService::ensureNumbersTable();
        $storeId = Helpers::get_store_id();

        return [
            'numbers'  => WhatsAppService::numbers($storeId),
            'bindings' => WhatsAppService::numberBindings($storeId),
            'limit'    => WhatsAppService::numberLimit($storeId),
            // Shown apart from the effective limit so the screen can say whose ceiling was hit:
            // Meta's lifts on business verification, ours does not.
            'metaCap'  => WhatsAppService::metaNumberCap($storeId),
            'purposes' => WhatsAppService::NUMBER_PURPOSES,
        ];
    }

    /** Rename a number, so a vendor can tell "front desk" from "pharmacy" at a glance. */
    public function numberLabel(Request $request)
    {
        $request->validate(['number_id' => 'required|integer', 'label' => 'nullable|string|max:120']);

        WhatsAppService::ensureNumbersTable();
        $storeId = Helpers::get_store_id();

        $updated = DB::table('wa_numbers')->where('store_id', $storeId)->where('id', $request->number_id)
            ->update(['label' => $request->label ?: null, 'updated_at' => now()]);

        $updated ? Toastr::success('Name updated.') : Toastr::error('That number is not connected to your store.');

        return back();
    }

    public function numberDefault(Request $request)
    {
        $request->validate(['number_id' => 'required|integer']);

        WhatsAppService::ensureNumbersTable();
        $storeId = Helpers::get_store_id();

        $number = DB::table('wa_numbers')->where('store_id', $storeId)
            ->where('id', $request->number_id)->where('status', 'active')->first();

        if (!$number) {
            Toastr::error('That number is not connected to your store.');
            return back();
        }

        WhatsAppService::setDefaultNumber($storeId, (int) $request->number_id);
        Toastr::success(($number->display_phone ?: 'That number') . ' is now your default.');

        return back();
    }

    /** Point one kind of automated message at a specific number, or back at the default. */
    public function numberBind(Request $request)
    {
        $request->validate([
            'purpose'   => 'required|string|in:' . implode(',', array_keys(WhatsAppService::NUMBER_PURPOSES)),
            'number_id' => 'nullable|integer',
        ]);

        WhatsAppService::ensureNumbersTable();
        $storeId = Helpers::get_store_id();
        $label   = WhatsAppService::NUMBER_PURPOSES[$request->purpose]['label'];

        if (!$request->filled('number_id')) {
            WhatsAppService::unbindNumber($storeId, $request->purpose);
            Toastr::success($label . ' will use your default number.');
            return back();
        }

        $number = DB::table('wa_numbers')->where('store_id', $storeId)
            ->where('id', $request->number_id)->where('status', 'active')->first();

        if (!$number) {
            Toastr::error('That number is not connected to your store.');
            return back();
        }

        WhatsAppService::bindNumber($storeId, $request->purpose, (int) $request->number_id);
        Toastr::success($label . ' will now send from ' . ($number->display_phone ?: $number->label ?: 'that number') . '.');

        return back();
    }
}
