<?php

namespace App\Services;

use App\CentralLogics\Helpers;
use App\Models\UserNotificationPreference;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Meta WhatsApp Cloud API client.
 *
 * Credential resolution is hybrid:
 *   1. Per-vendor override  — stores.wa_* columns, when the store has opted in.
 *   2. Global (admin)       — business_settings key `whatsapp_config`.
 *   3. .env / config        — config('services.whatsapp.*').
 *
 * Build with WhatsAppService::make($storeId) to honour a vendor's own number,
 * or WhatsAppService::make() for the platform default (e.g. OTP, admin alerts).
 */
class WhatsAppService
{
    /** Default approved template for vendor lead notifications (overridden by whatsapp_config.lead_template). */
    const DEFAULT_LEAD_TEMPLATE = 'vendor_lead_alert5';

    /** Language of the default lead template (must match the approved template's language exactly). */
    const DEFAULT_LEAD_TEMPLATE_LANG = 'en_US';

    /** Template sent to a vendor when a lead is auto-accepted (overridden by whatsapp_config.lead_accepted_template). */
    const DEFAULT_LEAD_ACCEPTED_TEMPLATE = 'vendor_lead_alert_accepted3';

    /**
     * Template for the vendor's "send test message" button. vendor_test_template2 is the branded
     * one approved on the platform WABA; its body takes one variable — the recipient's name.
     * Override with whatsapp_config.test_template / test_template_lang.
     */
    const DEFAULT_TEST_TEMPLATE = 'vendor_test_template2';
    const DEFAULT_TEST_TEMPLATE_LANG = 'en_US';

    /**
     * Template sent to a customer when the vendor adds them (Add Customer / Vendor screen).
     * This is the admin preset the vendor submits to their own WABA — the welcome only goes
     * out from the vendor's own connected number, once that copy is APPROVED.
     */
    const DEFAULT_WELCOME_TEMPLATE = 'customer_welcome';

    /**
     * Template for automatic HMIS appointment reminders — the admin preset the vendor submits
     * to their own WABA. Sent by the hourly SendAppointmentReminders job on the schedule the
     * vendor chose (stores.wa_appt_reminder: day_before | 2h_before | both).
     */
    const DEFAULT_APPT_REMINDER_TEMPLATE = 'appointment_reminder';

    /** Hours-before default when the vendor never chose a value (stores.wa_appt_reminder NULL). */
    const DEFAULT_APPT_REMINDER_HOURS = 2;

    /** Messages one person may receive from the shared outreach pool per 30 days. */
    const NEARBY_MONTHLY_CAP = 4;

    /** Whether the send composers list PENDING/REJECTED templates alongside approved ones.
     *  Off: Meta refuses to deliver them, so offering one only buys the vendor a failed send
     *  and a charge for it. Set to true to list them again while testing. */
    const BULK_SHOW_UNAPPROVED = false;

    /**
     * Make the WhatsApp sidebar item reachable for every store, and keep it that way.
     *
     * selected_menu() has two modes. A store that has never opened Menu Preference falls back to
     * the `menu` table, so the item needs a row there with default = 1 or it is invisible to
     * everyone. A store that HAS saved preferences is in explicit mode, where a slug missing from
     * store_menu_visibility returns false — which is every store that saved its menus before this
     * module existed. Both are seeded here.
     *
     * Only ever inserts. A vendor who deliberately switches the menu off owns a row with
     * is_visible = 0, and that must survive the next page load.
     */
    public static function ensureMenuVisible(?int $storeId): void
    {
        static $done = [];
        $key = (int) $storeId;
        if (isset($done[$key])) {
            return;
        }
        $done[$key] = true;

        try {
            if (!Schema::hasTable('menu')) {
                return;
            }

            $cols = Schema::getColumnListing('menu');
            $exists = DB::table('menu')->where('slug', 'whatsapp')->where('menu_type', 'sidebar')->exists();
            if (!$exists) {
                // business_type 'all' and free = 1: the module is sold per store on its own
                // subscription, so it must not be filtered out by the plan checks the Menu
                // Preference page applies to generic items.
                DB::table('menu')->insert(array_intersect_key([
                    'slug'              => 'whatsapp',
                    'name'              => 'WhatsApp',
                    'route'             => 'vendor.whatsapp.dashboard',
                    'menu_type'         => 'sidebar',
                    'business_type'     => 'all',
                    'group'             => 'communication',
                    'status'            => 1,
                    'default'           => 1,
                    'free'              => 1,
                    'under_development' => 0,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ], array_flip($cols)));
            }

            if (!$storeId || !Schema::hasTable('store_menu_visibility')) {
                return;
            }

            // No saved preferences at all — the `menu` default above already shows it, and writing
            // a single row here would flip the store into explicit mode and hide every other menu.
            $hasPrefs = DB::table('store_menu_visibility')
                ->where('store_id', $storeId)->where('menu_type', 'sidebar')->exists();
            if (!$hasPrefs) {
                return;
            }

            $where = ['store_id' => $storeId, 'menu_type' => 'sidebar', 'menu_key' => 'whatsapp'];
            if (!DB::table('store_menu_visibility')->where($where)->exists()) {
                DB::table('store_menu_visibility')->insert($where + ['is_visible' => 1]);
            }
        } catch (\Throwable $e) {
            // Best-effort — a menu row must never break a page render.
        }
    }

    /**
     * Named body variables a template may use instead of {{1}}, {{2}}. Meta calls this the
     * NAMED parameter format: the body carries {{customer_name}} and the send supplies a
     * parameter_name for each value, so the slot can never drift out of order.
     *
     * Everything listed here is filled in automatically per recipient at send time — the
     * sender is never asked for a value. `example` is the sample Meta's reviewers see.
     */
    const TEMPLATE_VARIABLES = [
        'customer_name'  => ['label' => 'Customer name', 'example' => 'Rahul Sharma'],
        'customer_phone' => ['label' => 'Customer phone', 'example' => '9876543210'],
    ];

    /**
     * Templates driven by automation that still builds its parameters by position
     * (welcome, appointment reminders, lead alerts). Named variables are refused for these
     * so a body edit can't silently break a job that has no name to give its values.
     */
    const POSITIONAL_ONLY_TEMPLATES = [
        self::DEFAULT_WELCOME_TEMPLATE,
        self::DEFAULT_APPT_REMINDER_TEMPLATE,
        self::DEFAULT_LEAD_TEMPLATE,
        self::DEFAULT_LEAD_ACCEPTED_TEMPLATE,
        self::DEFAULT_TEST_TEMPLATE,
        'staff_forward',
    ];

    /**
     * Templates the vendor has trashed. The template itself stays at Meta — approved and still
     * occupying one of their slots — so this is only about hiding it from the working list.
     * That is what makes Restore instant: nothing was deleted, so nothing needs re-approving.
     * Permanent delete is the only thing that touches Meta.
     */
    public static function ensureTrashTable(): void
    {
        if (!Schema::hasTable('wa_trashed_templates')) {
            DB::statement("CREATE TABLE `wa_trashed_templates` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `name` VARCHAR(190) NOT NULL,
                `language` VARCHAR(20) NOT NULL DEFAULT 'en_US',
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wa_trash_store_tpl` (`store_id`, `name`, `language`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    /** "name|language" keys of everything this store has trashed. */
    public static function trashedTemplateKeys(int $storeId): array
    {
        static::ensureTrashTable();
        return DB::table('wa_trashed_templates')->where('store_id', $storeId)
            ->get(['name', 'language'])
            ->map(fn($r) => strtolower($r->name . '|' . $r->language))
            ->all();
    }

    /** Named placeholders a body uses, in first-appearance order and deduped. */
    public static function namedVariables(string $body): array
    {
        preg_match_all('/\{\{\s*([a-z][a-z0-9_]*)\s*\}\}/i', $body, $m);
        return array_values(array_unique(array_map('strtolower', $m[1] ?? [])));
    }

    /** Highest {{n}} in a body — how many positional values a send must supply. */
    public static function positionalCount(string $body): int
    {
        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $body, $m);
        return $m[1] ? max(array_map('intval', $m[1])) : 0;
    }

    /** Sample value shown to Meta's reviewers for a named variable. */
    public static function variableExample(string $name): string
    {
        return self::TEMPLATE_VARIABLES[$name]['example']
            ?? ucfirst(str_replace('_', ' ', $name));
    }

    /**
     * Why Meta would refuse this template body, or null when it is fine.
     *
     * Covers the rejection vendors hit most ("Leading or trailing params not allowed",
     * error_subcode 2388299) plus the rules that come with named variables: a body is either
     * named or positional, never both, and only the names the platform knows how to fill are
     * accepted. $name is the template name, when known — the automation templates have to stay
     * positional because the jobs behind them supply values by order.
     */
    public static function templateBodyProblem(string $body, ?string $name = null): ?string
    {
        $body = trim($body);
        if ($body === '') {
            return null;
        }
        if (preg_match('/^\{\{\s*[a-z0-9_]+\s*\}\}/i', $body)) {
            return 'The message can’t start with a variable. Put some text before it — e.g. "Hi {{1}}" instead of "{{1}}".';
        }
        if (preg_match('/\{\{\s*[a-z0-9_]+\s*\}\}$/i', $body)) {
            return 'The message can’t end with a variable. Add some text after it — e.g. "{{2}}. See you then!" instead of ending on "{{2}}".';
        }

        $named = self::namedVariables($body);
        if (empty($named)) {
            return null;
        }

        if (self::positionalCount($body) > 0) {
            return 'Use either named variables like {{customer_name}} or numbered ones like {{1}} — Meta does not allow both in the same message.';
        }

        $unknown = array_diff($named, array_keys(self::TEMPLATE_VARIABLES));
        if (!empty($unknown)) {
            return 'Unknown variable {{' . reset($unknown) . '}}. The named variables available are '
                . implode(', ', array_map(fn($v) => '{{' . $v . '}}', array_keys(self::TEMPLATE_VARIABLES)))
                . ' — use {{1}}, {{2}} for anything else.';
        }

        if ($name !== null && in_array(strtolower(trim($name)), self::POSITIONAL_ONLY_TEMPLATES, true)) {
            return 'The "' . trim($name) . '" template is filled in automatically by MyChitti and has to use numbered variables ({{1}}, {{2}}) instead of named ones.';
        }

        return null;
    }

    /**
     * One body parameter for a send. A numeric key is positional (Meta reads the order);
     * anything else is a named parameter and must carry its name.
     */
    public static function bodyParameter(string $key, string $value): array
    {
        $param = ['type' => 'text', 'text' => $value];
        if (!ctype_digit($key)) {
            // Meta only accepts lowercase letters, digits and underscores as a parameter name.
            $param['parameter_name'] = preg_replace('/[^a-z0-9_]/', '', strtolower($key)) ?: 'value';
        }
        return $param;
    }

    /**
     * Paid WhatsApp message-receiving add-ons (per vendor, ₹/month).
     * Add a new receiving capability here — no schema change needed.
     */
    const RECEIVING_FEATURES = [
        'leads' => [
            'label' => 'Lead Notifications',
            'price' => 200,
            'desc'  => 'Get a WhatsApp message whenever MyChitti sends you a new lead.',
        ],
    ];

    protected ?int $storeId;
    protected array $cfg;

    public function __construct(?int $storeId = null)
    {
        $this->storeId = $storeId;
        $this->cfg = $this->resolveConfig($storeId);
    }

    public static function make(?int $storeId = null): self
    {
        return new self($storeId);
    }

    protected function resolveConfig(?int $storeId): array
    {
        // 1) Per-vendor override (only when the store opted in and the columns exist).
        if ($storeId && static::storeColumnsExist()) {
            $store = DB::table('stores')->where('id', $storeId)
                ->select('wa_enabled', 'wa_phone_number_id', 'wa_token', 'wa_business_account_id', 'wa_api_version')
                ->first();
            if ($store && $store->wa_enabled && $store->wa_phone_number_id && $store->wa_token) {
                return [
                    'phone_number_id'      => $store->wa_phone_number_id,
                    'token'                => $store->wa_token,
                    'business_account_id'  => $store->wa_business_account_id,
                    'api_version'          => $store->wa_api_version ?: config('services.whatsapp.api_version', 'v21.0'),
                    'default_country_code' => config('services.whatsapp.default_country_code', '91'),
                    'source'               => 'vendor',
                ];
            }
        }

        // 2) Global config saved by admin in business_settings.
        $global = Helpers::get_business_settings('whatsapp_config');
        if (is_array($global) && !empty($global['status']) && !empty($global['phone_number_id']) && !empty($global['token'])) {
            return [
                'phone_number_id'      => $global['phone_number_id'],
                'token'                => $global['token'],
                'business_account_id'  => $global['business_account_id'] ?? null,
                'api_version'          => $global['api_version'] ?: config('services.whatsapp.api_version', 'v21.0'),
                'default_country_code' => $global['default_country_code'] ?: config('services.whatsapp.default_country_code', '91'),
                'source'               => 'global',
            ];
        }

        // 3) .env / config fallback.
        return [
            'phone_number_id'      => config('services.whatsapp.phone_number_id'),
            'token'                => config('services.whatsapp.token'),
            'business_account_id'  => config('services.whatsapp.business_account_id'),
            'api_version'          => config('services.whatsapp.api_version', 'v21.0'),
            'default_country_code' => config('services.whatsapp.default_country_code', '91'),
            'source'               => 'env',
        ];
    }

    protected static function storeColumnsExist(): bool
    {
        static $exists = null;
        if ($exists === null) {
            try {
                $exists = Schema::hasColumn('stores', 'wa_phone_number_id');
            } catch (\Throwable $e) {
                $exists = false;
            }
        }
        return $exists;
    }

    public function isConfigured(): bool
    {
        return !empty($this->cfg['phone_number_id']) && !empty($this->cfg['token']);
    }

    /** Which credential set is in use: vendor | global | env | none. */
    public function source(): string
    {
        return $this->isConfigured() ? ($this->cfg['source'] ?? 'none') : 'none';
    }

    /** E.164 digits, no leading '+'. Local numbers get the default country code. */
    public function normalizePhone(string $phone): string
    {
        $p = preg_replace('/[^0-9]/', '', $phone) ?? '';
        if ($p === '') {
            return $p;
        }
        $cc = (string) ($this->cfg['default_country_code'] ?? '91');
        // A bare local number (<= 10 digits) is assumed to belong to the default country.
        if (strlen($p) <= 10) {
            $p = $cc . $p;
        }
        return $p;
    }

    /**
     * What a preset stores in btn_phone to mean "this vendor's own number".
     *
     * A preset is written once and submitted by every vendor on the platform, so a literal number
     * in one would point all of their customers at a single phone. This token is resolved per
     * vendor at the moment they submit the template — see presetCallButton().
     */
    const STORE_PHONE_TOKEN = '{store_phone}';

    /**
     * The call button a preset asks for, resolved for one store — or null when there isn't one,
     * or when the store has no number to dial.
     *
     * Returning null rather than a button with an empty number is deliberate: Meta would reject
     * the whole template, and losing the button is better than losing the submission.
     */
    public static function presetCallButton($preset, ?int $storeId): ?array
    {
        $phone = trim((string) ($preset->btn_phone ?? ''));
        if ($phone === '') {
            return null;
        }

        if ($phone === self::STORE_PHONE_TOKEN) {
            $phone = trim((string) (DB::table('stores')->where('id', $storeId)->value('phone') ?? ''));
        }

        if (strlen(preg_replace('/[^0-9]/', '', $phone) ?? '') < 10) {
            return null;
        }

        return [
            'type'  => 'PHONE_NUMBER',
            'text'  => trim((string) ($preset->btn_phone_text ?? '')) ?: 'Call now',
            'phone' => $phone,
        ];
    }

    /**
     * Full international form with the leading '+', for a template's call button.
     *
     * Separate from normalizePhone(): a recipient address is sent as bare digits, but the
     * phone_number on a PHONE_NUMBER button is shown to a reviewer and dialled by the handset, and
     * Meta rejects it without the country code. A number the vendor already typed with a '+' or a
     * country code is left as it is.
     */
    public static function e164(string $phone, string $defaultCountryCode = '91'): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }
        // A landline written the way it is dialled at home — 080 1234 5678 — carries the national
        // prefix, which must come off before the country code goes on or the number is nonsense.
        if (strlen($digits) === 11 && $digits[0] === '0') {
            $digits = substr($digits, 1);
        }
        if (strlen($digits) <= 10) {
            $digits = $defaultCountryCode . $digits;
        }
        return '+' . $digits;
    }

    protected function endpoint(): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            $this->cfg['api_version'],
            $this->cfg['phone_number_id']
        );
    }

    /**
     * Human-friendly error from a Graph API response. Meta's error_user_title/error_user_msg
     * explain the actual problem and fix (e.g. "Message template language is being deleted —
     * try again in less than 1 minute"); the bare error.message is often just "Invalid
     * parameter". Prefer the user-facing fields, fall back to the raw message.
     */
    protected function graphError($resp): string
    {
        $err   = data_get($resp->json(), 'error', []);
        $title = trim((string) data_get($err, 'error_user_title', ''));
        $msg   = trim((string) data_get($err, 'error_user_msg', ''));
        if ($msg !== '') {
            return ($title !== '' && stripos($msg, $title) === false) ? $title . ' — ' . $msg : $msg;
        }
        return (string) (data_get($err, 'message') ?: 'HTTP ' . $resp->status());
    }

    /** Low-level send. $payload is merged onto {messaging_product:'whatsapp'}. */
    public function send(array $payload, array $meta = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'WhatsApp API not configured', 'id' => null];
        }

        // Per-message charges come out of the wallet at dispatch, so an empty wallet has to stop
        // the send before it happens — otherwise the vendor keeps messaging on credit we never
        // agreed to extend. Only their own number is billable; platform-sent alerts are ours.
        if ($this->storeId && $this->source() === 'vendor') {
            $audience = static::audienceFor($meta['context'] ?? null, $this->storeId, $payload['to'] ?? null);
            if (!WhatsAppBilling::canAffordMessage($this->storeId, $audience)) {
                return [
                    'success' => false,
                    'error'   => 'Wallet balance too low to send. Each message costs '
                        . _price(WhatsAppBilling::messageCost($audience))
                        . ' — recharge your wallet to keep sending.',
                    'id'      => null,
                ];
            }
        }

        try {
            $resp = Http::withToken($this->cfg['token'])
                ->acceptJson()
                ->post($this->endpoint(), array_merge(['messaging_product' => 'whatsapp'], $payload));

            if ($resp->successful()) {
                $result = [
                    'success'  => true,
                    'error'    => null,
                    'id'       => data_get($resp->json(), 'messages.0.id'),
                    'response' => $resp->json(),
                ];
            } else {
                $err = $this->graphError($resp);
                Log::warning('WhatsApp send failed', ['status' => $resp->status(), 'body' => $resp->json()]);
                $result = ['success' => false, 'error' => $err, 'id' => null, 'response' => $resp->json()];
            }
        } catch (\Throwable $e) {
            Log::error('WhatsApp send exception: ' . $e->getMessage());
            $result = ['success' => false, 'error' => $e->getMessage(), 'id' => null];
        }

        $this->logMessage($payload, $meta, $result);
        return $result;
    }

    /**
     * Free-form text. Only delivered inside the 24h customer-initiated window;
     * outside it, use sendTemplate() with an approved template.
     */
    public function sendText(string $to, string $body, bool $previewUrl = true, ?string $context = null): array
    {
        return $this->send([
            'to'   => $this->normalizePhone($to),
            'type' => 'text',
            'text' => ['preview_url' => $previewUrl, 'body' => $body],
        ], ['body' => $body, 'context' => $context]);
    }

    /** Free-form document (PDF/image link). Same 24h-window rule as sendText(). */
    public function sendDocument(string $to, string $link, ?string $filename = null, ?string $caption = null, ?string $context = null): array
    {
        $doc = ['link' => $link];
        if ($filename) {
            $doc['filename'] = $filename;
        }
        if ($caption) {
            $doc['caption'] = $caption;
        }
        return $this->send([
            'to'       => $this->normalizePhone($to),
            'type'     => 'document',
            'document' => $doc,
        ], ['body' => $caption ?: $filename, 'context' => $context]);
    }

    /**
     * Approved template message — required for business-initiated conversations
     * (OTP, order updates, marketing) outside the 24h window.
     */
    public function sendTemplate(string $to, string $template, string $lang = 'en_US', array $components = [], ?string $context = null): array
    {
        $tpl = ['name' => $template, 'language' => ['code' => $lang]];
        if (!empty($components)) {
            $tpl['components'] = $components;
        }
        return $this->send([
            'to'       => $this->normalizePhone($to),
            'type'     => 'template',
            'template' => $tpl,
        ], ['body' => 'template: ' . $template, 'context' => $context]);
    }

    /**
     * Every template on this store's WABA as name => STATUS (APPROVED / PENDING / REJECTED …).
     *
     * For screens that need to say whether a feature can actually send — a toggle whose template
     * Meta has not approved is a switch that does nothing, and the vendor deserves to be told that
     * at the moment they flip it rather than by noticing no messages ever arrived.
     *
     * Cached briefly: a settings page reading six templates must not make six Graph calls, and the
     * answer changes only when Meta finishes a review. Returns [] when the list can't be fetched,
     * which callers must read as "unknown", never as "not approved".
     */
    public static function templateStatuses(int $storeId): array
    {
        try {
            return Cache::remember('wa_tpl_status_' . $storeId, 300, function () use ($storeId) {
                $wa = static::make($storeId);
                if ($wa->source() !== 'vendor' || !$wa->hasWaba()) {
                    return [];
                }

                $res = $wa->listTemplates();
                if (!$res['success']) {
                    return [];
                }

                $out = [];
                foreach ($res['data'] as $tpl) {
                    $name = strtolower((string) data_get($tpl, 'name'));
                    if ($name === '') {
                        continue;
                    }
                    // A template can exist per language; APPROVED anywhere means sendable.
                    $status = strtoupper((string) data_get($tpl, 'status'));
                    if (!isset($out[$name]) || $status === 'APPROVED') {
                        $out[$name] = $status;
                    }
                }
                return $out;
            });
        } catch (\Throwable $e) {
            Log::warning('WA template status lookup failed: ' . $e->getMessage());
            return [];
        }
    }

    /** Drop the cached statuses — called when a template is created, edited or deleted. */
    public static function forgetTemplateStatuses(?int $storeId): void
    {
        if ($storeId) {
            Cache::forget('wa_tpl_status_' . $storeId);
        }
    }

    /**
     * Business Management API base for the WABA (templates, phone numbers, etc.).
     * Requires the `whatsapp_business_management` permission and a business_account_id.
     */
    protected function wabaEndpoint(string $edge): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/%s',
            $this->cfg['api_version'],
            $this->cfg['business_account_id'],
            $edge
        );
    }

    public function hasWaba(): bool
    {
        return $this->isConfigured() && !empty($this->cfg['business_account_id']);
    }

    /**
     * The store's OWN WABA — never the platform's.
     *
     * resolveConfig() falls back to the global/env credentials whenever a store has not
     * connected a number, so hasWaba() is true for an unconnected vendor and points at
     * MyChitti's WABA. Anything a vendor drives that writes to Meta — listing, creating,
     * editing or deleting templates — must gate on this instead, or one vendor's click lands
     * on the platform account that every other vendor depends on.
     */
    public function hasVendorWaba(): bool
    {
        return $this->source() === 'vendor' && !empty($this->cfg['business_account_id']);
    }

    /**
     * Make sure our app is subscribed to this WABA so inbound messages and delivery
     * statuses reach the platform webhook. Idempotent — Meta returns success when already
     * subscribed — so it doubles as a self-heal for stores whose original subscription
     * failed silently or that connected before webhooks were wired.
     */
    public function ensureWebhookSubscription(): array
    {
        if (!$this->hasWaba()) {
            return ['success' => false, 'error' => 'No WhatsApp Business Account connected.'];
        }
        try {
            $resp = Http::withToken($this->cfg['token'])->acceptJson()
                ->post($this->wabaEndpoint('subscribed_apps'));
            if ($resp->successful()) {
                return ['success' => true, 'error' => null];
            }
            $err = $this->graphError($resp);
            Log::warning('WhatsApp subscribed_apps failed', ['waba' => $this->cfg['business_account_id'], 'error' => $err]);
            return ['success' => false, 'error' => $err];
        } catch (\Throwable $e) {
            Log::error('WhatsApp subscribed_apps exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * List message templates on the WABA (Business Management API).
     * GET /{WABA_ID}/message_templates
     */
    public function listTemplates(int $limit = 100): array
    {
        if (!$this->hasWaba()) {
            return ['success' => false, 'error' => 'WhatsApp Business Account ID is required to manage templates.', 'data' => []];
        }
        try {
            $resp = Http::withToken($this->cfg['token'])->acceptJson()
                ->get($this->wabaEndpoint('message_templates'), [
                    'limit'  => $limit,
                    'fields' => 'name,status,category,language,components,id,parameter_format',
                ]);
            if ($resp->successful()) {
                return ['success' => true, 'error' => null, 'data' => data_get($resp->json(), 'data', [])];
            }
            $err = $this->graphError($resp);
            Log::warning('WhatsApp listTemplates failed', ['status' => $resp->status(), 'body' => $resp->json()]);
            return ['success' => false, 'error' => $err, 'data' => []];
        } catch (\Throwable $e) {
            Log::error('WhatsApp listTemplates exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Create a message template (Business Management API).
     * POST /{WABA_ID}/message_templates
     * $bodyText may contain {{1}}, {{2}}, … placeholders; pass $example values in order.
     */
    /**
     * Builds the component array shared by create/update.
     * Order required by Meta: HEADER, BODY, FOOTER, BUTTONS.
     */
    /**
     * Upload a file for use as a template's media header.
     *
     * Meta will not take the bytes at template-create time — the file goes through the app's
     * resumable upload endpoint first and comes back as a handle, which is what the template
     * then references. Returns the handle, or null with the reason logged.
     */
    public function uploadHeaderMedia(string $path, string $mime, string $appId, string $appSecret): ?string
    {
        $version = $this->cfg['api_version'];
        $token   = $appId . '|' . $appSecret;   // app access token

        try {
            $session = Http::post("https://graph.facebook.com/{$version}/{$appId}/uploads", [
                'file_length' => filesize($path),
                'file_type'   => $mime,
                'access_token' => $token,
            ]);
            $sessionId = data_get($session->json(), 'id');
            if (!$sessionId) {
                Log::warning('WA header upload session failed', ['body' => $session->json()]);
                return null;
            }

            // Single-shot upload: offset 0, whole file. Templates cap well below the point
            // where chunking would matter.
            $resp = Http::withHeaders([
                    'Authorization' => 'OAuth ' . $token,
                    'file_offset'   => '0',
                    'Content-Type'  => 'application/octet-stream',
                ])
                ->withBody(file_get_contents($path), 'application/octet-stream')
                ->post("https://graph.facebook.com/{$version}/{$sessionId}");

            $handle = data_get($resp->json(), 'h');
            if (!$handle) {
                Log::warning('WA header upload failed', ['body' => $resp->json()]);
                return null;
            }
            return $handle;
        } catch (\Throwable $e) {
            Log::error('WA header upload exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * $header is either a plain string (TEXT header) or
     * ['format' => 'IMAGE'|'VIDEO'|'DOCUMENT', 'handle' => '...'] for a media header.
     */
    protected function buildComponents(string $bodyText, array $example, array $buttons, $header, ?string $footer): array
    {
        $components = [];

        if (is_array($header) && !empty($header['handle']) && !empty($header['format'])) {
            // Media headers carry the uploaded sample rather than text — Meta shows it to the
            // reviewer and sends it with every message.
            $components[] = [
                'type'    => 'HEADER',
                'format'  => strtoupper($header['format']),
                'example' => ['header_handle' => [$header['handle']]],
            ];
        } elseif (is_string($header) && trim($header) !== '') {
            $components[] = ['type' => 'HEADER', 'format' => 'TEXT', 'text' => $header];
        }

        $body = ['type' => 'BODY', 'text' => $bodyText];
        // A named body carries its own examples (one per variable name); the pipe-separated
        // list the form collects only applies to positional {{1}}, {{2}} bodies.
        $named = self::namedVariables($bodyText);
        if (!empty($named)) {
            $body['example'] = ['body_text_named_params' => array_map(fn($n) => [
                'param_name' => $n,
                'example'    => self::variableExample($n),
            ], $named)];
        } elseif (!empty($example)) {
            $body['example'] = ['body_text' => [array_values($example)]];
        }
        $components[] = $body;

        if ($footer !== null && trim($footer) !== '') {
            $components[] = ['type' => 'FOOTER', 'text' => $footer];
        }

        // Three button shapes: a link that opens a page, a call button that dials a number, and a
        // quick reply whose label comes back to the vendor as an inbound message. A row with no
        // text is simply an unused slot.
        $btnDefs = [];
        foreach ($buttons as $btn) {
            $text = trim((string) ($btn['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $type = strtoupper((string) ($btn['type'] ?? (empty($btn['url']) ? 'QUICK_REPLY' : 'URL')));

            if ($type === 'URL' && !empty($btn['url'])) {
                $btnDefs[] = ['type' => 'URL', 'text' => $text, 'url' => $btn['url']];
            } elseif ($type === 'PHONE_NUMBER' && !empty($btn['phone'])) {
                $btnDefs[] = [
                    'type'         => 'PHONE_NUMBER',
                    'text'         => $text,
                    'phone_number' => self::e164($btn['phone'], $this->cfg['default_country_code'] ?? '91'),
                ];
            } elseif ($type === 'QUICK_REPLY') {
                $btnDefs[] = ['type' => 'QUICK_REPLY', 'text' => $text];
            }
        }
        if (!empty($btnDefs)) {
            $components[] = ['type' => 'BUTTONS', 'buttons' => $btnDefs];
        }

        return $components;
    }

    public function createTemplate(string $name, string $category, string $lang, string $bodyText, array $example = [], array $buttons = [], $header = null, ?string $footer = null): array
    {
        if (!$this->hasWaba()) {
            return ['success' => false, 'error' => 'WhatsApp Business Account ID is required to manage templates.', 'id' => null];
        }
        $components = $this->buildComponents($bodyText, $example, $buttons, $header, $footer);

        $payload = [
            'name'       => $name,
            'category'   => $category,
            'language'   => $lang,
            'components' => $components,
        ];
        // Meta defaults a template to POSITIONAL; a body written with {{customer_name}} has to
        // declare NAMED at creation or the placeholders are rejected as invalid.
        if (self::namedVariables($bodyText)) {
            $payload['parameter_format'] = 'NAMED';
        }

        try {
            $resp = Http::withToken($this->cfg['token'])->acceptJson()
                ->post($this->wabaEndpoint('message_templates'), $payload);
            if ($resp->successful()) {
                return ['success' => true, 'error' => null, 'id' => data_get($resp->json(), 'id'), 'response' => $resp->json()];
            }
            $err = $this->graphError($resp);
            Log::warning('WhatsApp createTemplate failed', ['status' => $resp->status(), 'body' => $resp->json()]);
            return ['success' => false, 'error' => $err, 'id' => null, 'response' => $resp->json()];
        } catch (\Throwable $e) {
            Log::error('WhatsApp createTemplate exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'id' => null];
        }
    }

    /**
     * Edit an existing template (Business Management API).
     * POST /{message_template_id} — name & language are immutable; only category/components.
     * Meta rejects edits while a template is in review (PENDING).
     */
    public function updateTemplate(string $templateId, ?string $category, string $bodyText, array $example = [], array $buttons = [], $header = null, ?string $footer = null): array
    {
        if (!$this->hasWaba()) {
            return ['success' => false, 'error' => 'WhatsApp Business Account ID is required to manage templates.'];
        }
        $components = $this->buildComponents($bodyText, $example, $buttons, $header, $footer);

        $payload = ['components' => $components];
        if ($category) {
            $payload['category'] = $category;
        }
        if (self::namedVariables($bodyText)) {
            $payload['parameter_format'] = 'NAMED';
        }

        try {
            $resp = Http::withToken($this->cfg['token'])->acceptJson()
                ->post(sprintf('https://graph.facebook.com/%s/%s', $this->cfg['api_version'], $templateId), $payload);
            if ($resp->successful()) {
                return ['success' => true, 'error' => null, 'response' => $resp->json()];
            }
            $err = $this->graphError($resp);
            Log::warning('WhatsApp updateTemplate failed', ['status' => $resp->status(), 'body' => $resp->json()]);
            return ['success' => false, 'error' => $err, 'response' => $resp->json()];
        } catch (\Throwable $e) {
            Log::error('WhatsApp updateTemplate exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a message template by name (Business Management API).
     * DELETE /{WABA_ID}/message_templates?name=...
     */
    public function deleteTemplate(string $name): array
    {
        if (!$this->hasWaba()) {
            return ['success' => false, 'error' => 'WhatsApp Business Account ID is required to manage templates.'];
        }
        try {
            $resp = Http::withToken($this->cfg['token'])->acceptJson()
                ->delete($this->wabaEndpoint('message_templates'), ['name' => $name]);
            if ($resp->successful()) {
                return ['success' => true, 'error' => null];
            }
            $err = $this->graphError($resp);
            return ['success' => false, 'error' => $err];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Per-vendor credential columns on `stores` (for Phase 2 — each vendor's own number).
     * Idempotent, guarded, no migration files. Populated later by the onboarding flow;
     * resolveConfig() already reads them when wa_enabled is set.
     */
    public static function ensureStoreColumns(): void
    {
        if (!Schema::hasTable('stores')) {
            return;
        }
        $cols = [
            'wa_enabled'             => 'TINYINT(1) NOT NULL DEFAULT 0',
            'wa_phone_number_id'     => 'VARCHAR(64) NULL',
            'wa_token'               => 'TEXT NULL',
            'wa_business_account_id' => 'VARCHAR(64) NULL',
            'wa_api_version'         => 'VARCHAR(12) NULL',
            'wa_appt_reminder'       => 'VARCHAR(20) NULL',
            // Hours after a visit is completed before the feedback request goes out. NULL = never
            // chosen, which takes HmisWhatsAppShare's default rather than meaning "immediately".
            'wa_feedback_delay'      => 'VARCHAR(20) NULL',
            // Days before the follow-up date to remind the patient. 0 = send the confirmation at
            // the moment the visit is booked instead.
            'wa_followup_lead'       => 'VARCHAR(20) NULL',
            // DEAD: used to pick between the knowledge bot and the AI Agent, back when they were
            // priced differently. The plan decides that now, so nothing reads or writes this.
            // Kept so the column definition survives a fresh install identically; drop it when
            // you are happy no reporting still joins on it.
            'wa_bot_mode'            => "VARCHAR(20) NULL",
        ];
        foreach ($cols as $name => $def) {
            if (!Schema::hasColumn('stores', $name)) {
                DB::statement("ALTER TABLE `stores` ADD COLUMN `$name` $def");
            }
        }
    }

    /**
     * Admin-curated template presets. Stored locally; nothing is sent to Meta until a vendor
     * picks one, at which point it is submitted to THAT vendor's own WABA for review.
     * Idempotent, no migration files. Seeded with starter presets on first creation only,
     * so an admin deleting a default doesn't see it resurrected.
     */
    public static function ensurePresetsTable(): void
    {
        if (Schema::hasTable('wa_template_presets')) {
            // Quick-reply labels, comma separated. A preset could only ever offer one URL button
            // before, which is no use for a template whose whole point is asking a question.
            if (!Schema::hasColumn('wa_template_presets', 'btn_replies')) {
                DB::statement("ALTER TABLE `wa_template_presets` ADD COLUMN `btn_replies` VARCHAR(200) NULL AFTER `btn_url`");
            }
            // TEXT (the `header` string) or DOCUMENT — a media template, whose header carries a
            // file rather than words. Meta wants a sample file at creation, which the preset
            // path generates rather than asking the vendor to find one.
            if (!Schema::hasColumn('wa_template_presets', 'header_format')) {
                DB::statement("ALTER TABLE `wa_template_presets` ADD COLUMN `header_format` VARCHAR(20) NULL AFTER `header`");
            }
            // A "Call now" button. Usually holds the STORE_PHONE_TOKEN rather than a number: one
            // preset is submitted by every vendor, and a literal number would have all of their
            // customers ringing whoever the admin typed in.
            foreach ([
                'btn_phone'      => 'VARCHAR(30) NULL',
                'btn_phone_text' => 'VARCHAR(60) NULL',
            ] as $col => $def) {
                if (!Schema::hasColumn('wa_template_presets', $col)) {
                    DB::statement("ALTER TABLE `wa_template_presets` ADD COLUMN `{$col}` {$def} AFTER `btn_replies`");
                }
            }
            static::ensureStaffForwardPreset();
            static::ensureHmisPresets();
            static::repairHmisPresetBodies();
            static::ensureRepeatPreset();
        static::ensurePaymentReceiptPreset();
            return;
        }
        DB::statement("CREATE TABLE `wa_template_presets` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(120) NOT NULL,
            `name` VARCHAR(120) NOT NULL,
            `category` VARCHAR(30) NOT NULL DEFAULT 'UTILITY',
            `language` VARCHAR(12) NOT NULL DEFAULT 'en_US',
            `header` VARCHAR(200) NULL,
            `header_format` VARCHAR(20) NULL,
            `body` TEXT NOT NULL,
            `footer` VARCHAR(200) NULL,
            `example` TEXT NULL,
            `btn_text` VARCHAR(60) NULL,
            `btn_url` VARCHAR(500) NULL,
            `btn_replies` VARCHAR(200) NULL,
            `btn_phone` VARCHAR(30) NULL,
            `btn_phone_text` VARCHAR(60) NULL,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `watp_name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $now = now();
        DB::table('wa_template_presets')->insert([
            [
                'title'    => 'Welcome Message',
                'name'     => 'customer_welcome',
                'category' => 'MARKETING',
                'language' => 'en_US',
                'header'   => null,
                'body'     => "Hi {{1}}, thank you for choosing {{2}}! We've added you to our customer list — you'll now receive your bills, updates and offers from us right here on WhatsApp. Reply to this message anytime and we'll be happy to help.",
                'footer'   => 'Reply STOP to unsubscribe',
                'example'  => 'Ramesh | Krishna Hospital',
                'btn_text' => null,
                'btn_url'  => null,
                'active'   => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title'    => 'Appointment Reminder',
                'name'     => 'appointment_reminder',
                'category' => 'UTILITY',
                'language' => 'en_US',
                'header'   => null,
                'body'     => "Hi {{1}}, this is a reminder of your appointment with {{2}} on {{3}} at {{4}}. Please arrive 10 minutes early. If you need to reschedule, just reply to this message.",
                'footer'   => null,
                'example'  => 'Ramesh | Krishna Hospital | 25 July | 10:30 AM',
                'btn_text' => null,
                'btn_url'  => null,
                'active'   => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        static::ensureStaffForwardPreset();
        static::ensureHmisPresets();
        static::repairHmisPresetBodies();
        static::ensureRepeatPreset();
        static::ensurePaymentReceiptPreset();
    }

    /**
     * Suggested preset for forwarding an inbox message (with the customer's details) to a
     * staff member. Structure lives in the template's fixed text so it keeps its layout;
     * only {{4}} carries the forwarded message, whose own line breaks Meta collapses to spaces.
     *
     * Added after the two starter presets, so it must be back-filled on installs whose table
     * predates it. Guarded by a settings flag — a one-time insert that an admin can delete for
     * good, matching how the starter presets are seeded only once.
     */
    public static function ensureStaffForwardPreset(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (DB::table('business_settings')->where('key', 'wa_preset_staff_forward_seeded')->exists()) {
            return;
        }

        if (!DB::table('wa_template_presets')->where('name', 'staff_forward')->exists()) {
            $now = now();
            DB::table('wa_template_presets')->insert([
                'title'    => 'Forward to Staff',
                'name'     => 'staff_forward',
                'category' => 'UTILITY',
                'language' => 'en_US',
                'header'   => null,
                'body'     => "📩 New message forwarded from {{1}}.\n\nFrom: {{2}} ({{3}})\nMessage: {{4}}\n\nPlease follow up with the customer.",
                'footer'   => null,
                'example'  => 'Krishna Hospital | Ramesh | +919876543210 | I need to reschedule my appointment',
                'btn_text' => null,
                'btn_url'  => null,
                'active'   => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('business_settings')->updateOrInsert(
            ['key' => 'wa_preset_staff_forward_seeded'],
            ['value' => '1', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /**
     * Presets behind "Send on WhatsApp" in the hospital module — consultation summary,
     * prescription, medicine instructions, follow-up, feedback and lab report.
     *
     * The names are fixed: HmisWhatsAppShare sends against exactly these, so a vendor creating
     * them from the suggested list ends up with templates their hospital screens can already use.
     * Anything longer than a sentence (a medicine list, a panel of results) is deliberately NOT in
     * the template — Meta rejects newlines inside a parameter, and a patient's results have no
     * business sitting in a WhatsApp message forever. The last parameter is a private link instead.
     *
     * Seeded once; an admin who deletes one does not get it back.
     */
    public static function ensureHmisPresets(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        // Added after the first HMIS seed shipped, so it sits above the seeded-once guard —
        // below it, no existing hospital would ever be offered the template.
        self::ensurePatientDocumentPreset();

        if (DB::table('business_settings')->where('key', 'wa_preset_hmis_seeded')->exists()) {
            return;
        }

        $now = now();
        $presets = [
            [
                'title'    => 'Consultation Summary (Hospital)',
                'name'     => 'treatment_summary',
                'category' => 'UTILITY',
                'body'     => "Hi {{1}}, here is your consultation summary from {{2}} for your visit on {{3}}. Diagnosis noted: {{4}}. Open {{5}} to read the full summary, including the treatment advised and your vitals. This link works for 30 days.",
                'footer'   => 'Please discuss any concerns with your doctor',
                'example'  => 'Ramesh | Krishna Hospital | 25 July 2026 | Viral fever | https://mychitti.net/health-record/abc123',
            ],
            [
                'title'    => 'Prescription (Hospital)',
                'name'     => 'prescription_share',
                'category' => 'UTILITY',
                'body'     => "Hi {{1}}, your prescription from {{2}} dated {{3}} is ready — {{4}} prescribed. Open {{5}} to view, save or print it. This link works for 30 days.",
                'footer'   => 'Please take medicines only as prescribed',
                'example'  => 'Ramesh | Krishna Hospital | 25 July 2026 | 3 medicines | https://mychitti.net/health-record/abc123',
            ],
            [
                'title'    => 'Medicine Instructions (Hospital)',
                'name'     => 'medicine_instructions',
                'category' => 'UTILITY',
                'body'     => "Hi {{1}}, here is how to take the medicines prescribed at {{2}}: {{3}}. Open {{4}} for the full instructions for every medicine. Please finish the full course even if you feel better sooner.",
                'footer'   => 'Do not change the dose without asking your doctor',
                'example'  => 'Ramesh | Krishna Hospital | Paracetamol 500mg twice a day 3 days; Azithromycin 250mg once a day 5 days | https://mychitti.net/health-record/abc123',
            ],
            [
                'title'    => 'Follow-Up Reminder (Hospital)',
                'name'     => 'followup_reminder',
                'category' => 'UTILITY',
                'body'     => "Hi {{1}}, this is a reminder from {{2}} that your follow-up visit is due on {{3}} with {{4}}. Please reply to this message if you need a different date.",
                'footer'   => null,
                'example'  => 'Ramesh | Krishna Hospital | 02 Aug 2026 | Dr. Anita Rao',
            ],
            [
                // MARKETING, not UTILITY: nothing has happened on the patient's account to report.
                // This asks someone who stopped coming to come back, which is what Meta means by
                // marketing, and filing it as utility is how a WABA gets its category corrected
                // the hard way.
                'title'    => 'Rebook Reminder (Hospital)',
                'name'     => 'rebook_reminder',
                'category' => 'MARKETING',
                'body'     => "Hi {{1}}, it has been a while since your last visit with {{2}} at {{3}}. If you are due for a check-up, reply to this message and we will find you a slot.",
                'footer'   => 'Reply STOP to unsubscribe',
                'example'  => 'Ramesh | Dr. Anita Rao | Krishna Hospital',
            ],
            [
                'title'    => 'Lab Report Ready (Hospital)',
                'name'     => 'lab_report_ready',
                'category' => 'UTILITY',
                'body'     => "Hi {{1}}, your lab report from {{2}} is ready. Tests done: {{3}}. Open {{4}} to view or download the full report. This link works for 30 days.",
                'footer'   => 'Please review the report with your doctor',
                'example'  => 'Ramesh | Krishna Hospital | Complete Blood Count, Lipid Profile | https://mychitti.net/health-record/abc123',
            ],
        ];

        foreach ($presets as $preset) {
            if (DB::table('wa_template_presets')->where('name', $preset['name'])->exists()) {
                continue;
            }
            DB::table('wa_template_presets')->insert($preset + [
                'language'   => 'en_US',
                'header'     => null,
                'btn_text'   => null,
                'btn_url'    => null,
                'active'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // The prescription as an attached PDF. A media template: its header carries the file, which
        // is the only way to put a document in a business-initiated message — a plain document
        // message needs an open 24h window the patient will not be in.
        if (!DB::table('wa_template_presets')->where('name', 'prescription_pdf')->exists()) {
            DB::table('wa_template_presets')->insert([
                'title'         => 'Prescription as PDF (Hospital)',
                'name'          => 'prescription_pdf',
                'category'      => 'UTILITY',
                'language'      => 'en_US',
                'header'        => null,
                'header_format' => 'DOCUMENT',
                'body'          => "Hi {{1}}, your prescription from {{2}} dated {{3}} is attached — {{4}} prescribed. Please take them exactly as written and finish the full course.",
                'footer'        => 'Do not change the dose without asking your doctor',
                'example'       => 'Ramesh | Krishna Hospital | 25 July 2026 | 3 medicines',
                'btn_text'      => null,
                'btn_url'       => null,
                'active'        => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        // Feedback is the one that asks a question, so it carries quick replies — the patient
        // answers in one tap and the tap lands in the vendor's inbox as a readable label.
        if (!DB::table('wa_template_presets')->where('name', 'visit_feedback')->exists()) {
            DB::table('wa_template_presets')->insert([
                'title'        => 'Visit Feedback (Hospital)',
                'name'         => 'visit_feedback',
                'category'     => 'UTILITY',
                'language'     => 'en_US',
                'header'       => null,
                'body'         => "Hi {{1}}, thank you for visiting {{2}} on {{3}}. How was your experience with us? Your answer helps us take better care of our patients.",
                'footer'       => 'One tap is all it takes',
                'example'      => 'Ramesh | Krishna Hospital | 25 July 2026',
                'btn_text'     => null,
                'btn_url'      => null,
                'btn_replies'  => 'Very good,Okay,Not good',
                'active'       => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        DB::table('business_settings')->updateOrInsert(
            ['key' => 'wa_preset_hmis_seeded'],
            ['value' => '1', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /**
     * Preset behind "Send a document" on a patient — any file the hospital attaches by hand.
     *
     * A media template, like prescription_pdf: the header carries the file, which is the only way
     * to put a document in a message a patient never asked for. The body says what the file is,
     * because an attachment with no explanation reads like a scam.
     */
    protected static function ensurePatientDocumentPreset(): void
    {
        if (DB::table('wa_template_presets')->where('name', 'patient_document')->exists()) {
            return;
        }

        DB::table('wa_template_presets')->insert([
            'title'         => 'Send a Document (Hospital)',
            'name'          => 'patient_document',
            'category'      => 'UTILITY',
            'language'      => 'en_US',
            'header'        => null,
            'header_format' => 'DOCUMENT',
            'body'          => "Hi {{1}}, {{2}} has sent you a document — {{3}}, dated {{4}}. It is attached to this message. Please save it for your records.",
            'footer'        => 'Reply here if you cannot open the file',
            'example'       => 'Ramesh | Krishna Hospital | Discharge summary | 25 July 2026',
            'btn_text'      => null,
            'btn_url'       => null,
            'active'        => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * Preset behind the repeat purchase reminder. MARKETING, not UTILITY — it is a nudge to buy
     * again, and Meta categorises it that way whatever we call it.
     *
     * Quick replies rather than a link: the answer we want is "yes, keep it ready", and a tap that
     * lands in the vendor's inbox is the whole interaction.
     */
    public static function ensureRepeatPreset(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (DB::table('wa_template_presets')->where('name', 'repeat_purchase_reminder')->exists()) {
            return;
        }

        DB::table('wa_template_presets')->insert([
            'title'       => 'Repeat Purchase Reminder',
            'name'        => 'repeat_purchase_reminder',
            'category'    => 'MARKETING',
            'language'    => 'en_US',
            'header'      => null,
            // Ends in text on purpose: Meta rejects a body that finishes on a placeholder.
            'body'        => "Hi {{1}}, it's been a while since you bought {{2}} from {{3}}. Running low? Tap below and we'll keep it ready for you.",
            'footer'      => 'Reply STOP to unsubscribe',
            'example'     => 'Ramesh | rice, sunflower oil and 2 more | Green Mart',
            'btn_text'    => null,
            'btn_url'     => null,
            'btn_replies' => 'Order now,Not now',
            'active'      => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    /**
     * Preset behind the receipt a customer gets each time they pay something towards a bill.
     *
     * A media template, because the receipt itself is the attachment: a payment acknowledgement the
     * customer cannot save is not a receipt. The balance is a whole sentence in {{5}} rather than a
     * bare number — "0" would read as a demand rather than a settlement.
     */
    public static function ensurePaymentReceiptPreset(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (DB::table('wa_template_presets')->where('name', 'payment_receipt')->exists()) {
            return;
        }

        DB::table('wa_template_presets')->insert([
            'title'         => 'Payment Receipt',
            'name'          => 'payment_receipt',
            'category'      => 'UTILITY',
            'language'      => 'en_US',
            'header'        => null,
            'header_format' => 'DOCUMENT',
            'body'          => "Hi {{1}}, we have received {{2}} towards invoice {{3}} at {{4}}. {{5}} Your receipt is attached to this message — please keep it for your records.",
            'footer'        => 'Reply here if anything looks wrong',
            'example'       => 'Ramesh | ₹2,000.00 | KHB_M_26-27_234 | Krishna Hospital | Balance still due: ₹500.00.',
            'btn_text'      => null,
            'btn_url'       => null,
            'active'        => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * Rewrite hospital preset bodies that end in a variable.
     *
     * Meta rejects any template whose body starts or finishes with a placeholder ("Leading or
     * trailing params not allowed"), and the first four hospital presets shipped with the link
     * as their last character — so every one of them was refused at review. The corrected wording
     * moves the link into the sentence.
     *
     * Only rewrites a row still carrying the broken shape, so an admin who has since edited the
     * wording themselves keeps their version. Bodies only: a preset's title, category and buttons
     * are untouched.
     */
    public static function repairHmisPresetBodies(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (DB::table('business_settings')->where('key', 'wa_preset_hmis_bodies_v2')->exists()) {
            return;
        }

        $fixed = [
            'treatment_summary'     => "Hi {{1}}, here is your consultation summary from {{2}} for your visit on {{3}}. Diagnosis noted: {{4}}. Open {{5}} to read the full summary, including the treatment advised and your vitals. This link works for 30 days.",
            'prescription_share'    => "Hi {{1}}, your prescription from {{2}} dated {{3}} is ready — {{4}} prescribed. Open {{5}} to view, save or print it. This link works for 30 days.",
            'medicine_instructions' => "Hi {{1}}, here is how to take the medicines prescribed at {{2}}: {{3}}. Open {{4}} for the full instructions for every medicine. Please finish the full course even if you feel better sooner.",
            'lab_report_ready'      => "Hi {{1}}, your lab report from {{2}} is ready. Tests done: {{3}}. Open {{4}} to view or download the full report. This link works for 30 days.",
        ];

        foreach ($fixed as $name => $body) {
            $row = DB::table('wa_template_presets')->where('name', $name)->first();
            if (!$row || !preg_match('/\{\{\d+\}\}\s*$/', (string) $row->body)) {
                continue;
            }
            DB::table('wa_template_presets')->where('id', $row->id)
                ->update(['body' => $body, 'updated_at' => now()]);
        }

        DB::table('business_settings')->updateOrInsert(
            ['key' => 'wa_preset_hmis_bodies_v2'],
            ['value' => '1', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /**
     * Welcome a customer the vendor just added (Add Customer / Vendor screen).
     *
     * Sent from the VENDOR's own connected number using the `customer_welcome` template they
     * submitted from the admin preset — never the platform number. Silently skips when the
     * vendor hasn't connected WhatsApp; if their copy of the template isn't APPROVED yet,
     * Meta rejects the send and it's logged as failed under context 'welcome'.
     * Never throws — adding a customer must not fail because a WhatsApp send did.
     */
    public static function sendWelcomeMessage(int $storeId, ?string $customerName, ?string $phone): void
    {
        try {
            $phone = trim((string) $phone);
            if (strlen(preg_replace('/[^0-9]/', '', $phone) ?? '') < 10) {
                return;
            }

            if (!NotificationPrefs::enabled($storeId, 'whatsapp_send', 'customer_welcome')) {
                return;
            }

            $wa = static::make($storeId);
            if ($wa->source() !== 'vendor') {
                return;
            }

            // Connected but unpaid stores keep wa_enabled set, so the subscription is what
            // decides whether a message actually goes out.
            if (!WhatsAppBilling::isActive($storeId)) {
                return;
            }

            // One welcome per number per store — re-adding the same person must not re-send.
            static::ensureMessagesTable();
            $normalized = $wa->normalizePhone($phone);
            $already = DB::table('whatsapp_messages')
                ->where('store_id', $storeId)
                ->where('context', 'welcome')
                ->where('recipient', $normalized)
                ->where('status', '!=', 'failed')
                ->exists();
            if ($already) {
                return;
            }

            static::ensurePresetsTable();
            $preset = DB::table('wa_template_presets')->where('name', self::DEFAULT_WELCOME_TEMPLATE)->first();
            $lang = $preset->language ?? 'en_US';
            $storeName = DB::table('stores')->where('id', $storeId)->value('name') ?: 'our store';

            // Body vars: {{1}} customer name, {{2}} store name.
            $components = [[
                'type' => 'body',
                'parameters' => array_map(
                    fn($v) => ['type' => 'text', 'text' => $v],
                    [trim((string) $customerName) ?: 'there', $storeName]
                ),
            ]];

            $wa->sendTemplate($phone, self::DEFAULT_WELCOME_TEMPLATE, $lang, $components, 'welcome');
        } catch (\Throwable $e) {
            Log::warning('WA welcome send skipped: ' . $e->getMessage());
        }
    }

    /** Presets shown to vendors (active only) or to the admin manage screen (all). */
    public static function templatePresets(bool $activeOnly = true)
    {
        static::ensurePresetsTable();
        return DB::table('wa_template_presets')
            ->when($activeOnly, fn($q) => $q->where('active', 1))
            ->orderBy('title')
            ->get();
    }

    /** Per-store paid receiving add-ons. Idempotent, no migration files. */
    public static function ensureReceivingTable(): void
    {
        if (!Schema::hasTable('wa_receiving_features')) {
            DB::statement("CREATE TABLE `wa_receiving_features` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `feature` VARCHAR(40) NOT NULL,
                `enabled` TINYINT(1) NOT NULL DEFAULT 0,
                `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `active_until` DATE NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `waf_store_feature` (`store_id`, `feature`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    /**
     * Notify a store of a new lead on WhatsApp from the platform number — only when
     * the store has the paid "leads" add-on active. Safe to call from any dispatch path.
     */
    public static function sendLeadNotification(int $storeId, ?string $serviceName, ?string $clientName): void
    {
        Log::info('LEAD-WA: sendLeadNotification called', ['store_id' => $storeId, 'service' => $serviceName, 'client' => $clientName]);

        if (!static::storeHasFeature($storeId, 'leads')) {
            Log::info('LEAD-WA: skipped — store does not have active "leads" add-on', ['store_id' => $storeId]);
            return;
        }
        if (!NotificationPrefs::enabled($storeId, 'whatsapp_receive', 'lead_notify')) {
            Log::info('LEAD-WA: skipped — store muted lead alerts in notification settings', ['store_id' => $storeId]);
            return;
        }
        $store = DB::table('stores')->where('id', $storeId)->first();
        if (!$store || empty($store->phone)) {
            Log::info('LEAD-WA: skipped — store missing or no phone', ['store_id' => $storeId, 'phone' => $store->phone ?? null]);
            return;
        }

        $wa = static::make();
        if (!$wa->isConfigured()) {
            Log::info('LEAD-WA: skipped — platform WhatsApp not configured (no phone_number_id/token)', ['store_id' => $storeId, 'source' => $wa->source()]);
            return;
        }

        Log::info('LEAD-WA: all checks passed, attempting send', ['store_id' => $storeId, 'to' => $store->phone, 'source' => $wa->source()]);

        $serviceName = $serviceName ?: 'a service';
        $clientName  = $clientName ?: 'a customer';
        $cfg = Helpers::get_business_settings('whatsapp_config');


        // Config field overrides; otherwise fall back to the default lead template.
        $template = !empty($cfg['lead_template']) ? $cfg['lead_template'] : self::DEFAULT_LEAD_TEMPLATE;

        $sent = false;
        if ($template) {
            $components = [[
                'type' => 'body',
                'parameters' => array_map(
                    fn($v) => ['type' => 'text', 'text' => $v],
                    [$store->name ?: 'Vendor', $serviceName, $clientName]
                ),
            ]];

            $lang = !empty($cfg['lead_template_lang']) ? $cfg['lead_template_lang'] : self::DEFAULT_LEAD_TEMPLATE_LANG;
            $res = $wa->sendTemplate($store->phone, $template, $lang, $components, 'lead notify');
            $sent = !empty($res['success']);
        }
        // Fallback when no template is configured or the template send fails
        // (e.g. not yet approved) — the vendor still gets notified.
        // Fallback mirrors the approved `vendor_lead_alert3` template content.
        if (!$sent) {
            $vendorName = $store->name ?: 'Vendor';
            $msg = "Hello {$vendorName}, you have received a new service request on MyChitti.\n\n"
                . "Service: {$serviceName}\n"
                . "Customer: {$clientName}\n\n"
                . "Log in to your vendor panel to view the details and respond.";
            $wa->sendText($store->phone, $msg, true, 'lead notify');
        }
    }

    /**
     * Notify a store that a lead was auto-accepted on their behalf (WhatsApp add-on active).
     * Uses the configured/approved `vendor_lead_alert_accepted` template, with a plain-text fallback.
     */
    public static function sendLeadAcceptedNotification(int $storeId, ?string $serviceName, ?string $clientName, $visitingCharge = null, ?string $clientPhone = null): void
    {
        if (!NotificationPrefs::enabled($storeId, 'whatsapp_receive', 'lead_accepted')) {
            return;
        }
        $store = DB::table('stores')->where('id', $storeId)->first();
        if (!$store || empty($store->phone)) {
            return;
        }
        $wa = static::make();
        if (!$wa->isConfigured()) {
            return;
        }

        $serviceName = $serviceName ?: 'a service';
        $clientName  = $clientName ?: 'a customer';
        $clientPhone = $clientPhone ?: 'N/A';
        $charge      = ($visitingCharge !== null && $visitingCharge !== '') ? (string) $visitingCharge : '0';
        $cfg = Helpers::get_business_settings('whatsapp_config');

        // Send the customer phone with the country code (+91 default) in the template.
        if ($clientPhone !== 'N/A') {
            $digits = ltrim(preg_replace('/[^0-9]/', '', $clientPhone), '0');
            $cc = preg_replace('/[^0-9]/', '', (string) ($cfg['default_country_code'] ?? '91')) ?: '91';
            if ($digits !== '') {
                $clientPhone = (strlen($digits) > 10 && str_starts_with($digits, $cc))
                    ? '+' . $digits
                    : '+' . $cc . $digits;
            }
        }

        $template = !empty($cfg['lead_accepted_template']) ? $cfg['lead_accepted_template'] : self::DEFAULT_LEAD_ACCEPTED_TEMPLATE;
        $lang     = !empty($cfg['lead_accepted_template_lang']) ? $cfg['lead_accepted_template_lang'] : self::DEFAULT_LEAD_TEMPLATE_LANG;

        $sent = false;
        if ($template) {
            $components = [[
                'type' => 'body',
                'parameters' => array_map(
                    fn($v) => ['type' => 'text', 'text' => $v],
                    [$store->name ?: 'Vendor', $serviceName, $clientName, $clientPhone]
                ),
            ]];
            $res = $wa->sendTemplate($store->phone, $template, $lang, $components, 'lead accepted');
            $sent = !empty($res['success']);
        }

        // Fallback mirrors the approved `vendor_lead_alert_accepted` template content.
        if (!$sent) {
            $vendorName = $store->name ?: 'Vendor';
            $msg = "*Service request accepted*\n\n"
                . "Hello {$vendorName}, a new service request has been accepted for your account on MyChitti.\n\n"
                . "Service: {$serviceName}\n"
                . "Customer: {$clientName}\n"
                . "Customer phone: {$clientPhone}\n\n"
                . "A confirmation request has been sent to the customer. Open your vendor panel to view and proceed.";
            $wa->sendText($store->phone, $msg, true, 'lead accepted');
        }
    }

    /**
     * Per-store status of every receiving add-on (subscribed / paid-until / live).
     * Shared by the vendor lead-settings screen and anything else that renders the add-on cards.
     */
    public static function receivingFeatureStatus(int $storeId): array
    {
        static::ensureReceivingTable();
        $rows = DB::table('wa_receiving_features')->where('store_id', $storeId)->get()->keyBy('feature');

        $out = [];
        foreach (self::RECEIVING_FEATURES as $key => $meta) {
            $row = $rows->get($key);
            $active = $row && $row->active_until && $row->active_until >= now()->toDateString();
            $out[$key] = [
                'meta'         => $meta,
                'enabled'      => (bool) ($row->enabled ?? false),
                'active_until' => $row->active_until ?? null,
                'paid_active'  => (bool) $active,
                'live'         => $active && (bool) ($row->enabled ?? false),
            ];
        }
        return $out;
    }

    /**
     * Send a test WhatsApp from the MyChitti platform number — deliberately not the vendor's
     * own connection, so this answers "can MyChitti reach this number?" even before (or
     * without) the vendor connecting a number of their own.
     *
     * $toPhone lets the vendor test any number; it falls back to the store's registered phone.
     *
     * @return array{success: bool, message: string}
     */
    public static function sendTestMessage(int $storeId, ?string $toPhone = null): array
    {
        $store = DB::table('stores')->where('id', $storeId)->first();

        $toPhone = trim((string) $toPhone);
        $target  = $toPhone !== '' ? $toPhone : ($store->phone ?? '');
        if ($target === '') {
            return ['success' => false, 'message' => 'Enter a phone number to send the test to, or add one to your store profile first.'];
        }
        if (strlen(preg_replace('/[^0-9]/', '', $target)) < 10) {
            return ['success' => false, 'message' => 'That does not look like a valid phone number. Include the number with country code, e.g. 91XXXXXXXXXX.'];
        }

        $wa = static::make(); // platform credentials, never the vendor's
        if (!$wa->isConfigured()) {
            return ['success' => false, 'message' => 'MyChitti WhatsApp is not configured yet. Please contact support.'];
        }

        $cfg = Helpers::get_business_settings('whatsapp_config');
        $template = !empty($cfg['test_template']) ? $cfg['test_template'] : self::DEFAULT_TEST_TEMPLATE;
        $lang     = !empty($cfg['test_template_lang']) ? $cfg['test_template_lang'] : self::DEFAULT_TEST_TEMPLATE_LANG;

        // vendor_test_template2's body is "Hi {{1}}, ..." — {{1}} is the recipient name.
        // Sending a body param to a variable-less template (e.g. hello_world override) errors,
        // so only attach it for the default branded template.
        $components = [];
        if ($template === self::DEFAULT_TEST_TEMPLATE) {
            $components = [[
                'type'       => 'body',
                'parameters' => [['type' => 'text', 'text' => $store->name ?? 'there']],
            ]];
        }

        $res = $wa->sendTemplate($target, $template, $lang, $components, 'test message');

        // Plain text only lands inside a 24h window, so treat it as a bonus attempt rather
        // than a reliable fallback — report the template error if both fail.
        if (empty($res['success'])) {
            $text = static::make()->sendText(
                $target,
                "This is a test message from MyChitti. If you received this, WhatsApp notifications to "
                    . ($store->name ?? 'your store') . " are working.",
                false,
                'test message'
            );
            if (empty($text['success'])) {
                return ['success' => false, 'message' => $res['error'] ?: 'Send failed.'];
            }
        }

        return ['success' => true, 'message' => 'Test message sent to ' . static::maskForDisplay($target) . '.'];
    }

    /** Show enough of the number to confirm it is the right one, without printing it in full. */
    protected static function maskForDisplay(?string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone) ?? '';
        return strlen($digits) < 4 ? 'your registered number' : '******' . substr($digits, -4);
    }

    /** True only when the store has the add-on enabled AND the paid period is still valid. */
    public static function storeHasFeature(int $storeId, string $feature): bool
    {
        if (!array_key_exists($feature, self::RECEIVING_FEATURES)) {
            return false;
        }
        static::ensureReceivingTable();
        $row = DB::table('wa_receiving_features')
            ->where('store_id', $storeId)->where('feature', $feature)->first();
        return $row && $row->enabled && $row->active_until && $row->active_until >= now()->toDateString();
    }

    /** Keywords that mean "stop messaging me". Matched on the whole message, case-insensitive. */
    const OPT_OUT_KEYWORDS = ['stop', 'unsubscribe', 'opt out', 'optout', 'remove me', 'do not message', 'dont message'];

    /** Marketing opt-outs. store_id NULL means "every sender on the platform". */
    public static function ensureOptOutTable(): void
    {
        if (!Schema::hasTable('wa_opt_outs')) {
            DB::statement("CREATE TABLE `wa_opt_outs` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NULL,
                `phone` VARCHAR(32) NOT NULL,
                `source` VARCHAR(40) NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wao_store_phone` (`store_id`, `phone`),
                KEY `wao_phone_idx` (`phone`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    /** Does this message body ask us to stop? */
    public static function isOptOutMessage(?string $body): bool
    {
        $text = trim(mb_strtolower(preg_replace('/[^\p{L}\s]/u', ' ', (string) $body) ?? ''));
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        return $text !== '' && in_array($text, self::OPT_OUT_KEYWORDS, true);
    }

    /**
     * Record an opt-out, both for the store whose number was messaged and platform-wide.
     *
     * The platform-wide row is not optional any more. Store customer books are a shared
     * bulk-send pool now, so an opt-out scoped to one store would leave the person open to
     * the identical campaign from every other store holding their number — they would have to
     * reply STOP to each one in turn.
     */
    public static function recordOptOut(?int $storeId, string $phone, string $source = 'reply'): void
    {
        static::ensureOptOutTable();
        $normalized = static::make($storeId)->normalizePhone($phone);
        if ($normalized === '') {
            return;
        }

        foreach (array_unique([$storeId, null], SORT_REGULAR) as $scope) {
            DB::table('wa_opt_outs')->updateOrInsert(
                ['store_id' => $scope, 'phone' => $normalized],
                ['source' => $source, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        // Turn off nearby offers on their MyChitti account, so the same STOP covers the pool
        // the vendor panel sends from.
        //
        // updateOrCreate, not update: nearby offers are on by default and most customers have
        // no preference row at all, so an UPDATE would match nothing and the person would keep
        // receiving exactly what they just asked to stop. The row IS the refusal.
        try {
            UserNotificationPreference::ensureTable();
            $userIds = DB::table('users')
                ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [substr($normalized, -10)])
                ->pluck('id');
            foreach ($userIds as $userId) {
                UserNotificationPreference::updateOrCreate(['user_id' => $userId], ['nearby_offers' => false]);
            }
        } catch (\Throwable $e) {
            Log::warning('opt-out offers clear failed: ' . $e->getMessage());
        }

        Log::info('WA opt-out recorded', ['store_id' => $storeId, 'phone' => $normalized, 'platform_wide' => true]);
    }

    /** Undo an opt-out — the customer turning WhatsApp back on in their dashboard. */
    public static function clearOptOut(?int $storeId, string $phone): void
    {
        static::ensureOptOutTable();
        $normalized = static::make($storeId)->normalizePhone($phone);
        if ($normalized === '') {
            return;
        }

        // Only the matching scope. Turning the dashboard toggle back on clears the
        // platform-wide row but leaves any explicit "STOP" the customer sent to a specific
        // vendor — that was a decision about that vendor, not about MyChitti as a whole.
        DB::table('wa_opt_outs')
            ->where('phone', $normalized)
            ->when(
                $storeId,
                fn($q) => $q->where('store_id', $storeId),
                fn($q) => $q->whereNull('store_id')
            )
            ->delete();
    }

    /**
     * Last-10-digit forms of numbers that already hit the shared outreach pool's 30-day cap.
     *
     * Counted on the 'nearby' context, which is why every platform-audience send has to carry it:
     * the cap is what stops every vendor in a city messaging the same few thousand people until
     * they all opt out. Re-checked between the steps of a drip campaign, not just when the
     * audience is first picked — a four-step series would otherwise blow straight through it.
     */
    public static function nearbyCappedPhones(): array
    {
        try {
            $phones = DB::table('whatsapp_messages')
                ->where('context', 'nearby')
                ->where('sent_at', '>=', now()->subDays(30))
                ->whereNotNull('recipient')
                ->groupBy('recipient')
                ->havingRaw('count(*) >= ?', [static::NEARBY_MONTHLY_CAP])
                ->pluck('recipient');
        } catch (\Throwable $e) {
            return [];
        }

        return array_values(array_unique(array_filter($phones
            ->map(fn($p) => substr(preg_replace('/[^0-9]/', '', (string) $p) ?? '', -10))
            ->all())));
    }

    /**
     * Normalized phone numbers that must not receive marketing from this store —
     * its own opt-outs plus everyone who opted out platform-wide.
     */
    public static function optedOutPhones(?int $storeId): array
    {
        static::ensureOptOutTable();
        return DB::table('wa_opt_outs')
            ->where(fn($q) => $q->whereNull('store_id')->orWhere('store_id', $storeId))
            ->pluck('phone')
            ->all();
    }

    /** Resolve which store owns an inbound message, by its Cloud API phone_number_id. */
    public static function storeByPhoneNumberId(?string $phoneNumberId): ?int
    {
        if (!$phoneNumberId || !static::storeColumnsExist()) {
            return null;
        }
        return DB::table('stores')->where('wa_phone_number_id', $phoneNumberId)->value('id');
    }

    /** Creates the delivery-log table once (no migration files, per project rules). */
    public static function ensureMessagesTable(): void
    {
        if (!Schema::hasTable('whatsapp_messages')) {
            DB::statement("CREATE TABLE `whatsapp_messages` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NULL,
                `wamid` VARCHAR(255) NULL,
                `direction` VARCHAR(10) NOT NULL DEFAULT 'out',
                `recipient` VARCHAR(32) NULL,
                `type` VARCHAR(30) NULL,
                `body` TEXT NULL,
                `context` VARCHAR(120) NULL,
                `status` VARCHAR(20) NULL,
                `error` TEXT NULL,
                `sent_at` TIMESTAMP NULL,
                `status_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                `audience` VARCHAR(10) NULL,
                PRIMARY KEY (`id`),
                KEY `wam_idx` (`wamid`),
                KEY `wam_store_idx` (`store_id`),
                KEY `wam_billing_idx` (`store_id`, `direction`, `sent_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            return;
        }

        // Whose contact list the message went to — 'own' (₹0.06) or 'platform' (₹0.12).
        // Rows written before this column existed fall back to the context at billing time.
        if (!Schema::hasColumn('whatsapp_messages', 'audience')) {
            DB::statement("ALTER TABLE `whatsapp_messages` ADD COLUMN `audience` VARCHAR(10) NULL");
            DB::statement("ALTER TABLE `whatsapp_messages` ADD KEY `wam_billing_idx` (`store_id`, `direction`, `sent_at`)");
        }
    }

    /**
     * One row per person per bulk-send run, claimed *before* the message is dispatched.
     *
     * The delivery log (whatsapp_messages) is written after the API call returns, which makes it
     * a record of what happened rather than a lock on what may happen. If a run of 17,000 broke
     * at 500 and the vendor pressed send again, nothing stopped those 500 being messaged a second
     * time. The unique key on (run_id, phone10) is what stops it: a repeat claim fails, and the
     * recipient is skipped instead of re-messaged.
     *
     * phone10 rather than the raw number, so "+91 98…", "098…" and "98…" are one person — the
     * same dedupe key the audience queries use.
     */
    public static function ensureBulkSendTable(): void
    {
        if (Schema::hasTable('wa_bulk_sends')) {
            // The message each person actually received, variables already filled in. The delivery
            // log only records "template: {name}", which cannot answer "what did you send my
            // customer?" months later — a template can be edited or deleted after the fact.
            if (!Schema::hasColumn('wa_bulk_sends', 'body')) {
                DB::statement("ALTER TABLE `wa_bulk_sends` ADD COLUMN `body` TEXT NULL");
            }
            if (!Schema::hasColumn('wa_bulk_sends', 'language')) {
                DB::statement("ALTER TABLE `wa_bulk_sends` ADD COLUMN `language` VARCHAR(20) NULL");
            }
            // Run history is read newest-first for one store; without this it is a full scan of
            // every vendor's sends.
            if (!static::hasIndex('wa_bulk_sends', 'wabs_store_run')) {
                DB::statement("ALTER TABLE `wa_bulk_sends` ADD KEY `wabs_store_run` (`store_id`, `id`)");
            }
            return;
        }

        DB::statement("CREATE TABLE `wa_bulk_sends` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `store_id` BIGINT NOT NULL,
            `run_id` VARCHAR(40) NOT NULL,
            `phone10` VARCHAR(10) NOT NULL,
            `phone` VARCHAR(32) NULL,
            `name` VARCHAR(190) NULL,
            `client_id` BIGINT NULL,
            `audience` VARCHAR(10) NOT NULL DEFAULT 'own',
            `template` VARCHAR(190) NULL,
            `language` VARCHAR(20) NULL,
            `body` TEXT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'queued',
            `wamid` VARCHAR(255) NULL,
            `error` TEXT NULL,
            `sent_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `wabs_once` (`run_id`, `phone10`),
            KEY `wabs_rotation` (`store_id`, `audience`, `sent_at`),
            KEY `wabs_run` (`run_id`, `status`),
            KEY `wabs_store_run` (`store_id`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Whether a named index exists, so the self-heal above can add one without re-running it.
     * Reads information_schema rather than SHOW INDEX — SHOW takes no placeholders, and the
     * table name would have to be interpolated to ask the question at all.
     * Any failure answers "yes", so an unreadable catalogue never turns into a duplicate-key ALTER.
     */
    private static function hasIndex(string $table, string $index): bool
    {
        try {
            return DB::table('information_schema.statistics')
                ->whereRaw('table_schema = DATABASE()')
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->exists();
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * The approved body text of one template, so a send can be recorded as the words the customer
     * read rather than a template name.
     *
     * Cached because a bulk run posts a batch at a time — without this a 1,000-person send would
     * make forty Graph calls for one unchanging string. Returns null when the list can't be
     * fetched; callers must treat that as "unknown", never as "no body".
     */
    public static function templateBodyText(int $storeId, string $name, ?string $lang = null): ?string
    {
        try {
            $key = 'wa_tpl_body_' . $storeId . '_' . md5(strtolower($name . '|' . $lang));
            return Cache::remember($key, 600, function () use ($storeId, $name, $lang) {
                $wa = static::make($storeId);
                if ($wa->source() !== 'vendor' || !$wa->hasWaba()) {
                    return null;
                }

                $res = $wa->listTemplates();
                if (!$res['success']) {
                    return null;
                }

                foreach ($res['data'] as $tpl) {
                    if (strtolower((string) data_get($tpl, 'name')) !== strtolower($name)) {
                        continue;
                    }
                    // Language is a tiebreak, not a filter — a template approved in one language
                    // still tells the vendor what was sent.
                    if ($lang && strtolower((string) data_get($tpl, 'language')) !== strtolower($lang)) {
                        continue;
                    }
                    foreach ((array) data_get($tpl, 'components', []) as $c) {
                        if (strtoupper((string) data_get($c, 'type')) === 'BODY') {
                            return (string) data_get($c, 'text', '');
                        }
                    }
                }

                return null;
            });
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Header formats that carry a file rather than text, and so need media at send time. */
    const MEDIA_HEADERS = ['IMAGE', 'VIDEO', 'DOCUMENT'];

    /**
     * The header format Meta approved this template with: TEXT, IMAGE, VIDEO, DOCUMENT, or null
     * when it has no header at all.
     *
     * A template is sent with the components it was CREATED with — send an IMAGE-header template
     * without a header component and Graph rejects the whole message with
     * "(#132012) Parameter format does not match format in the created template". So anything
     * that sends a template has to know this before it builds the payload.
     */
    public static function templateHeaderFormat(int $storeId, string $name, ?string $lang = null): ?string
    {
        try {
            $key = 'wa_tpl_hdr_' . $storeId . '_' . md5(strtolower($name . '|' . $lang));
            return Cache::remember($key, 600, function () use ($storeId, $name, $lang) {
                $wa = static::make($storeId);
                if ($wa->source() !== 'vendor' || !$wa->hasWaba()) {
                    return null;
                }

                $res = $wa->listTemplates();
                if (!$res['success']) {
                    return null;
                }

                foreach ($res['data'] as $tpl) {
                    if (strtolower((string) data_get($tpl, 'name')) !== strtolower($name)) {
                        continue;
                    }
                    if ($lang && strtolower((string) data_get($tpl, 'language')) !== strtolower($lang)) {
                        continue;
                    }
                    foreach ((array) data_get($tpl, 'components', []) as $c) {
                        if (strtoupper((string) data_get($c, 'type')) === 'HEADER') {
                            return strtoupper((string) data_get($c, 'format', 'TEXT')) ?: 'TEXT';
                        }
                    }
                    // Matched the template and it has no header component.
                    return null;
                }

                return null;
            });
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * The header component for a media template — the file that rides at the top of the message.
     *
     * `$url` must be publicly reachable: Meta fetches it from their own servers, so a link behind
     * a login or on localhost silently fails the send rather than erroring usefully.
     */
    public static function mediaHeaderComponent(string $format, string $url, ?string $filename = null): array
    {
        $format = strtoupper($format);
        $kind   = match ($format) {
            'VIDEO'    => 'video',
            'DOCUMENT' => 'document',
            default    => 'image',
        };

        $media = ['link' => $url];
        if ($kind === 'document') {
            $media['filename'] = $filename ?: (basename(parse_url($url, PHP_URL_PATH) ?: '') ?: 'document.pdf');
        }

        return [
            'type'       => 'header',
            'parameters' => [['type' => $kind, $kind => $media]],
        ];
    }

    /**
     * How long a platform recipient stays out of this store's own pool after being messaged, so
     * consecutive sends walk forward through the audience instead of restarting at the same
     * people. Distinct from NEARBY_MONTHLY_CAP, which limits what *every* vendor together may
     * send one person; this is one vendor not talking to the same strangers twice. Matched to
     * the same window so the two agree about what "recently" means.
     */
    const PLATFORM_ROTATION_DAYS = 30;

    /**
     * Send contexts that target MC Vendor Hub's own customer database rather than the vendor's
     * contact list. These carry the higher per-message data usage charge — add any new
     * platform-audience send path here or it will be billed at the cheaper own-list rate.
     */
    const PLATFORM_AUDIENCE_CONTEXTS = ['nearby'];

    /**
     * Which rate a message is billed at, decided by WHO is being messaged rather than by which
     * screen sent it.
     *
     *   own      — the number is in this store's own customer book (store_customers).
     *   platform — everyone else, called "MyChitti customers" throughout the vendor UI: the
     *              MyChitti database, other vendors' customers, and any number the vendor has
     *              never imported all attract the same Data Usage Charge.
     *
     * Membership is the test, not the context, because a context whitelist defaults every new
     * send path to the cheaper rate — which is how a number the vendor has no relationship with
     * would end up billed at the own-list price. Matched on the last 10 digits, the same way the
     * opt-out and outreach queries match, so +91/0 prefixes and spacing never split one person
     * into two.
     */
    public static function audienceFor(?string $context, ?int $storeId = null, ?string $phone = null): string
    {
        if (in_array((string) $context, self::PLATFORM_AUDIENCE_CONTEXTS, true)) {
            return 'platform';
        }
        if (!$storeId || !$phone) {
            return 'platform';
        }

        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';
        if (strlen($digits) < 10) {
            return 'platform';
        }

        try {
            $isOwn = DB::table('store_customers')
                ->where('store_id', $storeId)
                ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [substr($digits, -10)])
                ->exists();
        } catch (\Throwable $e) {
            // Never let a lookup failure silently downgrade the rate.
            Log::warning('WhatsApp audience lookup failed: ' . $e->getMessage());
            return 'platform';
        }

        return $isOwn ? 'own' : 'platform';
    }

    protected function logMessage(array $payload, array $meta, array $result): void
    {
        $audience = static::audienceFor($meta['context'] ?? null, $this->storeId, $payload['to'] ?? null);

        try {
            static::ensureMessagesTable();
            DB::table('whatsapp_messages')->insert([
                'store_id'  => $this->storeId,
                'wamid'     => $result['id'] ?? null,
                'direction' => 'out',
                'recipient' => $payload['to'] ?? null,
                'type'      => $payload['type'] ?? null,
                'body'      => isset($meta['body']) ? mb_substr((string) $meta['body'], 0, 1000) : null,
                'context'   => $meta['context'] ?? null,
                'audience'  => $audience,
                'status'    => $result['success'] ? 'accepted' : 'failed',
                'error'     => $result['error'] ?? null,
                'sent_at'   => now(),
                'status_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('WhatsApp log insert failed: ' . $e->getMessage());
        }

        // Billed at dispatch, so a failed send still counts — the message left the platform
        // either way. Only the vendor's own number is billable; platform-sent alerts are ours.
        if ($this->storeId && $this->source() === 'vendor') {
            WhatsAppBilling::chargeMessage($this->storeId, $audience);
        }
    }
}
