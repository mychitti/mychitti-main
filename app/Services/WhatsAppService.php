<?php

namespace App\Services;

use App\CentralLogics\Helpers;
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
    const DEFAULT_LEAD_TEMPLATE = 'vendor_lead_alert4';

    /** Language of the default lead template (must match the approved template's language exactly). */
    const DEFAULT_LEAD_TEMPLATE_LANG = 'en_US';

    /** Template sent to a vendor when a lead is auto-accepted (overridden by whatsapp_config.lead_accepted_template). */
    const DEFAULT_LEAD_ACCEPTED_TEMPLATE = 'vendor_lead_alert_accepted2';

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

    protected function endpoint(): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            $this->cfg['api_version'],
            $this->cfg['phone_number_id']
        );
    }

    /** Low-level send. $payload is merged onto {messaging_product:'whatsapp'}. */
    public function send(array $payload, array $meta = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'WhatsApp API not configured', 'id' => null];
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
                $err = data_get($resp->json(), 'error.message', 'HTTP ' . $resp->status());
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
                    'fields' => 'name,status,category,language,components,id',
                ]);
            if ($resp->successful()) {
                return ['success' => true, 'error' => null, 'data' => data_get($resp->json(), 'data', [])];
            }
            $err = data_get($resp->json(), 'error.message', 'HTTP ' . $resp->status());
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
    protected function buildComponents(string $bodyText, array $example, array $buttons, ?string $header, ?string $footer): array
    {
        $components = [];

        if ($header !== null && trim($header) !== '') {
            $components[] = ['type' => 'HEADER', 'format' => 'TEXT', 'text' => $header];
        }

        $body = ['type' => 'BODY', 'text' => $bodyText];
        if (!empty($example)) {
            $body['example'] = ['body_text' => [array_values($example)]];
        }
        $components[] = $body;

        if ($footer !== null && trim($footer) !== '') {
            $components[] = ['type' => 'FOOTER', 'text' => $footer];
        }

        $btnDefs = [];
        foreach ($buttons as $btn) {
            if (!empty($btn['text']) && !empty($btn['url'])) {
                $btnDefs[] = ['type' => 'URL', 'text' => $btn['text'], 'url' => $btn['url']];
            }
        }
        if (!empty($btnDefs)) {
            $components[] = ['type' => 'BUTTONS', 'buttons' => $btnDefs];
        }

        return $components;
    }

    public function createTemplate(string $name, string $category, string $lang, string $bodyText, array $example = [], array $buttons = [], ?string $header = null, ?string $footer = null): array
    {
        if (!$this->hasWaba()) {
            return ['success' => false, 'error' => 'WhatsApp Business Account ID is required to manage templates.', 'id' => null];
        }
        $components = $this->buildComponents($bodyText, $example, $buttons, $header, $footer);

        try {
            $resp = Http::withToken($this->cfg['token'])->acceptJson()
                ->post($this->wabaEndpoint('message_templates'), [
                    'name'       => $name,
                    'category'   => $category,
                    'language'   => $lang,
                    'components' => $components,
                ]);
            if ($resp->successful()) {
                return ['success' => true, 'error' => null, 'id' => data_get($resp->json(), 'id'), 'response' => $resp->json()];
            }
            $err = data_get($resp->json(), 'error.message', 'HTTP ' . $resp->status());
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
    public function updateTemplate(string $templateId, ?string $category, string $bodyText, array $example = [], array $buttons = [], ?string $header = null, ?string $footer = null): array
    {
        if (!$this->hasWaba()) {
            return ['success' => false, 'error' => 'WhatsApp Business Account ID is required to manage templates.'];
        }
        $components = $this->buildComponents($bodyText, $example, $buttons, $header, $footer);

        $payload = ['components' => $components];
        if ($category) {
            $payload['category'] = $category;
        }

        try {
            $resp = Http::withToken($this->cfg['token'])->acceptJson()
                ->post(sprintf('https://graph.facebook.com/%s/%s', $this->cfg['api_version'], $templateId), $payload);
            if ($resp->successful()) {
                return ['success' => true, 'error' => null, 'response' => $resp->json()];
            }
            $err = data_get($resp->json(), 'error.message', 'HTTP ' . $resp->status());
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
            $err = data_get($resp->json(), 'error.message', 'HTTP ' . $resp->status());
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
        ];
        foreach ($cols as $name => $def) {
            if (!Schema::hasColumn('stores', $name)) {
                DB::statement("ALTER TABLE `stores` ADD COLUMN `$name` $def");
            }
        }
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
     * Record an opt-out. Always scoped to the store whose number was messaged.
     *
     * When the number is not in that store's own client book we reached them through the
     * platform user base, so the opt-out is also recorded platform-wide — otherwise the
     * person would have to reply STOP separately to every vendor we hand their number to.
     */
    public static function recordOptOut(?int $storeId, string $phone, string $source = 'reply'): void
    {
        static::ensureOptOutTable();
        $normalized = static::make($storeId)->normalizePhone($phone);
        if ($normalized === '') {
            return;
        }

        $scopes = [$storeId];

        $isOwnClient = $storeId && DB::table('store_customers')
            ->where('store_id', $storeId)
            ->whereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') LIKE ?", ['%' . substr($normalized, -10)])
            ->exists();

        if (!$isOwnClient) {
            $scopes[] = null;
        }

        foreach (array_unique($scopes, SORT_REGULAR) as $scope) {
            DB::table('wa_opt_outs')->updateOrInsert(
                ['store_id' => $scope, 'phone' => $normalized],
                ['source' => $source, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        Log::info('WA opt-out recorded', ['store_id' => $storeId, 'phone' => $normalized, 'platform_wide' => !$isOwnClient]);
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
                PRIMARY KEY (`id`),
                KEY `wam_idx` (`wamid`),
                KEY `wam_store_idx` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    protected function logMessage(array $payload, array $meta, array $result): void
    {
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
    }
}
