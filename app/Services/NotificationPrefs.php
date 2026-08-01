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
                    'template' => 'customer_welcome',
                ],
                'auto_reply' => [
                    'label'   => 'AI auto-reply to customer messages',
                    'desc'    => 'Automatically answers customer WhatsApp messages using your Auto-Reply Knowledge documents. Stays silent if you have no active knowledge saved.',
                    'default' => true,
                ],
                // appointment_reminder is rendered on the page too, but its on/off lives in
                // stores.wa_appt_reminder (the hours value) — see the settings view.

                // Off by default: it messages past customers unprompted and each one is billed,
                // and it sends nothing at all until the store sets a repeat cycle somewhere.
                'repeat_purchase' => [
                    'label'    => 'Repeat purchase reminder',
                    'desc'     => 'Reminds a customer when something they buy regularly is due again — one message listing everything due, at most once a fortnight per customer (needs your approved repeat_purchase_reminder template). Switch it on item by item under Inventory, where you also set how many days after the purchase it goes out.',
                    'default'  => false,
                    'template' => 'repeat_purchase_reminder',
                ],

                // On by default: this only ever fires when a human has just taken money from this
                // customer at the counter, and a receipt is what they expect to walk away with.
                'payment_receipt' => [
                    'label'    => 'Payment receipt',
                    'desc'     => 'Sends the customer their receipt as a PDF each time a payment is recorded against one of their bills, part payment or full (needs your approved payment_receipt template).',
                    'default'  => true,
                    'template' => 'payment_receipt',
                ],

                // Hospital records. All default OFF, unlike everything above: these send a
                // patient their own medical information without a human pressing anything, and
                // each message is billed. That is a decision a hospital makes deliberately, not
                // one they discover after the fact. Every one of them can still be sent by hand
                // from its own screen whether the toggle is on or off.
                'hmis_treatment' => [
                    'label'   => 'Consultation summary',
                    'desc'    => 'Sent to the patient when an OPD visit is marked completed (needs your approved treatment_summary template).',
                    'default' => false,
                    'template' => 'treatment_summary',
                    'module'  => 'hospital_manage',
                ],
                'hmis_prescription' => [
                    'label'   => 'Prescription',
                    'desc'    => 'Sent when a prescription is finalized (needs your approved prescription_share template).',
                    'default' => false,
                    'template' => 'prescription_share',
                    'module'  => 'hospital_manage',
                ],
                'hmis_prescription_pdf' => [
                    'label'   => 'Prescription as a PDF instead',
                    'desc'    => 'Sends the prescription as an attached PDF rather than a link — it never expires and the patient can forward it (needs your approved prescription_pdf template). This replaces the message above rather than adding to it: with both on, the patient still gets the prescription once, as the PDF.',
                    'default' => false,
                    'template' => 'prescription_pdf',
                    'module'  => 'hospital_manage',
                ],
                'hmis_medicines' => [
                    'label'   => 'Medicine instructions',
                    'desc'    => 'How to take each medicine, sent when a prescription is finalized (needs your approved medicine_instructions template).',
                    'default' => false,
                    'template' => 'medicine_instructions',
                    'module'  => 'hospital_manage',
                ],
                'hmis_followup' => [
                    'label'   => 'Follow-up confirmation',
                    'desc'    => 'Sent when you book a patient\'s next visit (needs your approved followup_reminder template). Separate from the reminder before the appointment.',
                    'default' => false,
                    'template' => 'followup_reminder',
                    'module'  => 'hospital_manage',
                ],
                // Off by default like the rest of this group, and for a sharper reason: it messages
                // people who stopped coming, unprompted, and each one is billed.
                'hmis_rebook' => [
                    'label'   => 'Rebook reminder',
                    'desc'    => 'Invites a patient back when they are past their doctor\'s recall interval and have nothing booked (needs your approved rebook_reminder template). Anyone with an appointment already on the books is skipped. Set the interval per doctor under Doctors — leave a doctor blank and their patients are never chased.',
                    'default' => false,
                    'template' => 'rebook_reminder',
                    'module'  => 'hospital_manage',
                ],
                'hmis_feedback' => [
                    'label'   => 'Feedback request',
                    'desc'    => 'Asks how the visit went once an OPD visit or appointment is completed (needs your approved visit_feedback template).',
                    'default' => false,
                    'template' => 'visit_feedback',
                    'module'  => 'hospital_manage',
                ],
                'hmis_lab_report' => [
                    'label'   => 'Lab report',
                    'desc'    => 'Sent when a lab report is verified and finalized (needs your approved lab_report_ready template). Never sent for unverified results.',
                    'default' => false,
                    'template' => 'lab_report_ready',
                    'module'  => 'hospital_manage',
                ],
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
                'chat_escalation' => [
                    'label'   => 'Customer chat needs your reply',
                    'desc'    => 'Panel notification when the WhatsApp auto-reply could not answer a customer\'s question from your knowledge documents.',
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
     * Is this action enabled for this store? Falls back to the registry default, including when
     * the lookup itself fails — a broken prefs table must never silently mute notifications that
     * were working before, and must never silently switch on ones nobody asked for.
     *
     * That second half matters for the opt-in items: returning a blanket TRUE on error would mean
     * a table problem started WhatsApping patients their prescriptions at a hospital that had
     * deliberately left every one of these off.
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
            return $default;
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
        // Fetched at most once per call, and only if some visible item needs it.
        $statuses = null;
        foreach (self::CHANNELS as $channel => $meta) {
            foreach (self::GROUPS as $group => $g) {
                if ($g['channel'] !== $channel || $g['direction'] !== $direction) {
                    continue;
                }
                // An item tied to a module is only offered to stores that run it — a restaurant
                // has no use for a consultation-summary toggle it could never trigger.
                $items = [];
                foreach ($g['items'] as $key => $item) {
                    if (!empty($item['module']) && !vendorPlanHasModule($item['module'])) {
                        continue;
                    }
                    $item['enabled'] = static::enabled($storeId, $group, $key);

                    // Whether the template this action sends actually exists and is approved on
                    // the vendor's own WABA. Without it the toggle is a switch wired to nothing.
                    // null = no template needed, or the status could not be read.
                    if (!empty($item['template']) && $channel === 'whatsapp') {
                        $statuses = $statuses ?? WhatsAppService::templateStatuses($storeId);
                        $item['template_status'] = $statuses
                            ? ($statuses[strtolower($item['template'])] ?? 'MISSING')
                            : null;
                    }

                    $items[$key] = $item;
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
