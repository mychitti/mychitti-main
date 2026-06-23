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
