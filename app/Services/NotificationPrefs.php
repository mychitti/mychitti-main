<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-store notification preferences: which automatic messages a store sends to customers
 * and which alerts it receives, per action, per channel (WhatsApp / push).
 *
 * Only actions listed in GROUPS appear on the vendor settings page, and every listed action
 * IS enforced at its send site — never list an action here without wiring its check, or the
 * toggle would be a lie. Defaults preserve pre-existing behaviour (everything on), so a
 * store with no saved rows behaves exactly as before this page existed.
 */
class NotificationPrefs
{
    /** Tab order and labels on the settings pages. */
    const CHANNELS = [
        'whatsapp' => ['label' => 'WhatsApp',          'icon' => 'tio-chat'],
        'push'     => ['label' => 'Push Notification', 'icon' => 'tio-notifications'],
        'sms'      => ['label' => 'SMS',               'icon' => 'tio-sms-chat-outlined'],
    ];

    const GROUPS = [
        'whatsapp_send' => [
            'channel'   => 'whatsapp',
            'direction' => 'send',
            'items' => [
                'customer_welcome' => [
                    'label'   => 'Customer welcome message',
                    'desc'    => 'Sent automatically when a new customer is added to your store (needs your approved customer_welcome template).',
                    'default' => true,
                ],
                'auto_reply' => [
                    'label'   => 'AI auto-reply to customer messages',
                    'desc'    => 'Automatically answers customer WhatsApp messages using your Auto-Reply Knowledge documents. Stays silent if you have no active knowledge saved.',
                    'default' => true,
                ],
                // appointment_reminder is rendered on the page too, but its on/off lives in
                // stores.wa_appt_reminder (the hours value) — see the settings view.
            ],
        ],
        'push_send' => [
            'channel'   => 'push',
            'direction' => 'send',
            'items' => [
                'lead_status' => [
                    'label'   => 'Lead status updates',
                    'desc'    => 'Push notification to the customer when you accept or cancel their enquiry / appointment.',
                    'default' => true,
                ],
            ],
        ],
        'sms_send' => [
            'channel'   => 'sms',
            'direction' => 'send',
            'items' => [
                // No store-triggered automatic SMS to customers yet — the tab shows an
                // empty state. Add the action here AND its send-site check together.
            ],
        ],
        'whatsapp_receive' => [
            'channel'   => 'whatsapp',
            'direction' => 'receive',
            'items' => [
                'lead_notify' => [
                    'label'   => 'New lead alert',
                    'desc'    => 'WhatsApp message when a new lead/enquiry reaches your store. Also requires the paid Lead Notifications add-on.',
                    'default' => true,
                ],
                'lead_accepted' => [
                    'label'   => 'Lead auto-accepted alert',
                    'desc'    => 'WhatsApp message when a lead is auto-accepted for your store, with the customer\'s contact.',
                    'default' => true,
                ],
            ],
        ],
        'push_receive' => [
            'channel'   => 'push',
            'direction' => 'receive',
            'items' => [
                'new_lead' => [
                    'label'   => 'New lead / enquiry',
                    'desc'    => 'Panel notification when a new lead or appointment request reaches your store.',
                    'default' => true,
                ],
            ],
        ],
        'sms_receive' => [
            'channel'   => 'sms',
            'direction' => 'receive',
            'items' => [
                'new_lead' => [
                    'label'   => 'New lead SMS',
                    'desc'    => 'Text message to your registered phone when a new lead or appointment request reaches your store.',
                    'default' => true,
                ],
            ],
        ],
    ];

    /** Idempotent, no migration files (per project rules). */
    public static function ensureTable(): void
    {
        if (!Schema::hasTable('store_notification_prefs')) {
            DB::statement("CREATE TABLE `store_notification_prefs` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `pref` VARCHAR(80) NOT NULL,
                `enabled` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `snp_store_pref` (`store_id`, `pref`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    /**
     * Is this action enabled for this store? Falls back to the registry default, and to
     * TRUE on any failure — a broken prefs table must never silently mute notifications
     * that were working before.
     */
    public static function enabled(?int $storeId, string $group, string $key): bool
    {
        $default = (bool) (self::GROUPS[$group]['items'][$key]['default'] ?? true);
        if (!$storeId) {
            return $default;
        }

        try {
            $rows = static::rowsFor($storeId);
            return array_key_exists("$group.$key", $rows) ? (bool) $rows["$group.$key"] : $default;
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * One direction ('send' | 'receive') of the registry, grouped by channel tab in
     * CHANNELS order, with each item's resolved enabled state — for the settings pages.
     *
     * @return array<string, array{label: string, icon: string, group: string, items: array}>
     */
    public static function forDirection(int $storeId, string $direction): array
    {
        $out = [];
        foreach (self::CHANNELS as $channel => $meta) {
            foreach (self::GROUPS as $group => $g) {
                if ($g['channel'] !== $channel || $g['direction'] !== $direction) {
                    continue;
                }
                $items = $g['items'];
                foreach ($items as $key => &$item) {
                    $item['enabled'] = static::enabled($storeId, $group, $key);
                }
                $out[$channel] = $meta + ['group' => $group, 'items' => $items];
            }
        }
        return $out;
    }

    /** Persist one toggle. Unknown keys are rejected so stray posts can't pollute the table. */
    public static function set(int $storeId, string $group, string $key, bool $enabled): bool
    {
        if (!isset(self::GROUPS[$group]['items'][$key])) {
            return false;
        }
        static::ensureTable();
        DB::table('store_notification_prefs')->updateOrInsert(
            ['store_id' => $storeId, 'pref' => "$group.$key"],
            ['enabled' => $enabled ? 1 : 0, 'updated_at' => now(), 'created_at' => now()]
        );
        unset(static::$cache[$storeId]);
        return true;
    }

    /** Per-request cache: one query per store, however many enabled() checks a request makes. */
    protected static array $cache = [];

    protected static function rowsFor(int $storeId): array
    {
        if (!array_key_exists($storeId, static::$cache)) {
            static::ensureTable();
            static::$cache[$storeId] = DB::table('store_notification_prefs')
                ->where('store_id', $storeId)
                ->pluck('enabled', 'pref')
                ->all();
        }
        return static::$cache[$storeId];
    }
}
