<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\StoreWallet;
use App\Services\WhatsAppService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            ->select('wa_enabled', 'wa_phone_number_id', 'wa_business_account_id')
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
        if ($connected) {
            $res = WhatsAppService::make($storeId)->listTemplates();
            $templates = $this->bulkTemplateOptions($res['data']);
            if (!$res['success']) {
                $templateError = $res['error'];
            }
            $clientCount = $this->clientQuery($storeId)->count();
            $platformUserCount = $this->platformUserQuery($storeId)->count();
            $optOutCount = count(WhatsAppService::optedOutPhones($storeId));
        }

        return view('vendor-views.whatsapp.connect', compact(
            'es', 'store', 'connected', 'templates', 'templateError',
            'clientCount', 'platformUserCount', 'optOutCount'
        ));
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
     * A store with no zone gets no platform recipients rather than the whole user base.
     */
    private function platformUserQuery(int $storeId)
    {
        $zoneId = DB::table('stores')->where('id', $storeId)->value('zone_id');

        return $this->excludeOptedOut(
            DB::table('users')
                ->when(
                    $zoneId,
                    fn($q) => $q->where('zone_id', $zoneId),
                    fn($q) => $q->whereRaw('1 = 0')
                )
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
            'mode'         => 'required|in:clients,platform',
            'client_ids'   => 'required_if:mode,clients|array|max:' . self::BULK_BATCH_LIMIT,
            'client_ids.*' => 'integer',
            'offset'       => 'required_if:mode,platform|integer|min:0',
            'limit'        => 'required_if:mode,platform|integer|min:1|max:' . self::BULK_BATCH_LIMIT,
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

        $platform = $request->input('mode') === 'platform';

        if ($platform) {
            // Ordered by id so the browser's offset walk covers each user exactly once.
            $recipients = $this->platformUserQuery($storeId)
                ->orderBy('id')
                ->offset((int) $request->input('offset'))
                ->limit((int) $request->input('limit'))
                ->get(['id', 'f_name', 'l_name', 'phone'])
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

            $res = $wa->sendTemplate($client->phone, $request->template, $request->language, $components, 'bulk');

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
            Http::withToken($token)->post("https://graph.facebook.com/{$version}/{$request->waba_id}/subscribed_apps");

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
        if ($connected) {
            $res = $wa->listTemplates();
            $templates = $res['data'];
            if (!$res['success']) {
                $templateError = $res['error'];
            }
        }

        return view('vendor-views.whatsapp.templates', compact('connected', 'templates', 'templateError'));
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
            Toastr::success('Template submitted to Meta for review (id: ' . ($res['id'] ?? '—') . ').');
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
            Toastr::success('Template updated and re-submitted to Meta for review.');
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
