<?php

namespace App\Services;

use App\CentralLogics\Helpers;
use App\Models\UserNotificationPreference;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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
     * Template warning a vendor that their wallet could not cover a receiving add-on renewal
     * (overridden by whatsapp_config.addon_low_balance_template). Body takes four variables:
     * store name, add-on label, price, wallet balance.
     */
    const DEFAULT_ADDON_LOW_BALANCE_TEMPLATE = 'vendor_addon_low_balance';

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
        self::DEFAULT_ADDON_LOW_BALANCE_TEMPLATE,
        self::DEFAULT_TEST_TEMPLATE,
        'staff_forward',
        // Every other suggested template behind a role in TEMPLATE_ROLES. Kept as literals
        // because a const cannot be built from the roles array, so this list has to be extended
        // alongside it whenever a role is added.
        'advice_note',
        'service_recall',
        'repeat_purchase_reminder',
        'rebook_reminder',
        'invoice_ready',
        'payment_receipt',
        'opd_visit_registered',
        'treatment_summary',
        'prescription_share',
        'prescription_pdf',
        'medicine_instructions',
        'followup_reminder',
        'visit_feedback',
        'lab_report_ready',
        'radiology_report_ready',
        'patient_document',
        'lab_work_status',
        'lab_work_vendor_job',
        'lab_work_handover',
        'lab_work_handover_otp',
    ];

    /**
     * The jobs that send from the VENDOR's own number, and the template each one needs.
     *
     * A role is a contract, not a name. The automation fills the body by position, so a template
     * can only stand in for a role if it takes exactly these variables in exactly this order —
     * bind one with a different shape and the customer is greeted with the store's name, or the
     * send fails outright on a parameter count Meta will not accept.
     *
     * `default` is the template the platform suggests and seeds as a preset. It is a starting
     * point, not a requirement: a vendor may delete it and use their own wording, which is the
     * whole reason bindings exist. Anything sent from the PLATFORM number (lead alerts, the test
     * message) is deliberately absent — those are ours, not the vendor's, and are not bindable.
     */
    const TEMPLATE_ROLES = [
        // ── Customers ───────────────────────────────────────────────────────────────────
        'welcome' => [
            'label'   => 'Customer welcome',
            'group'   => 'Customers',
            'default' => self::DEFAULT_WELCOME_TEMPLATE,
            'params'  => ['Customer name', 'Store name'],
            'blurb'   => 'Sent once when a new customer is added to your customer book.',
        ],
        'advice_note' => [
            'label'   => 'Advice note',
            'group'   => 'Customers',
            'default' => 'advice_note',
            'params'  => ['Customer name', 'Store name', 'Note'],
            'blurb'   => 'A note you type yourself and send to one customer from their profile.',
        ],
        'service_recall' => [
            'label'   => 'Service recall',
            'group'   => 'Customers',
            'default' => 'service_recall',
            'params'  => ['Customer name', 'Store name', 'Service name'],
            'blurb'   => 'Invites a customer back once their last service is due again.',
        ],
        'repeat_purchase' => [
            'label'   => 'Repeat purchase reminder',
            'group'   => 'Customers',
            'default' => 'repeat_purchase_reminder',
            'params'  => ['Customer name', 'Items', 'Store name'],
            'blurb'   => 'Reminds a customer to restock what they buy regularly.',
        ],

        // ── Appointments ────────────────────────────────────────────────────────────────
        'appt_reminder' => [
            'label'   => 'Appointment reminder',
            'group'   => 'Appointments',
            'default' => self::DEFAULT_APPT_REMINDER_TEMPLATE,
            'params'  => ['Patient name', 'Clinic name', 'Date', 'Time'],
            'blurb'   => 'Sent before a booked appointment, on the schedule set in your reminder settings.',
        ],
        'rebook' => [
            'label'   => 'Rebooking reminder',
            'group'   => 'Appointments',
            'default' => 'rebook_reminder',
            'params'  => ['Patient name', 'Doctor name', 'Clinic name'],
            'blurb'   => 'Nudges a patient who has not booked again since their last visit.',
        ],

        // ── Billing ─────────────────────────────────────────────────────────────────────
        // Both carry the PDF in a document header, so a stand-in needs that header as well as
        // the right variable count — see `header` and the note the automation screen shows.
        'invoice' => [
            'label'   => 'Invoice',
            'group'   => 'Billing',
            'default' => 'invoice_ready',
            'header'  => 'DOCUMENT',
            'params'  => ['Customer name', 'Store name', 'Invoice number', 'Total amount', 'Balance note'],
            'blurb'   => 'Sends a bill to the customer with the PDF attached.',
        ],
        'payment_receipt' => [
            'label'   => 'Payment receipt',
            'group'   => 'Billing',
            'default' => 'payment_receipt',
            'header'  => 'DOCUMENT',
            'params'  => ['Customer name', 'Amount paid', 'Invoice number', 'Store name', 'Balance note'],
            'blurb'   => 'Sent when a payment is recorded against a bill, with the receipt attached.',
        ],

        // ── Hospital ────────────────────────────────────────────────────────────────────
        // Keys match HmisWhatsAppShare::KINDS exactly, which is what lets dispatch() resolve a
        // role from the kind it was handed without a second lookup table to keep in step.
        'visit_registered' => [
            'label'   => 'Visit registered',
            'group'   => 'Hospital',
            'module'  => 'hospital_manage',
            'default' => 'opd_visit_registered',
            'params'  => ['Patient name', 'Hospital name', 'Visit date', 'Token number', 'Doctor name', 'Record link'],
            'blurb'   => 'Sent when an OPD visit is booked in, with the token number and doctor.',
        ],
        'treatment' => [
            'label'   => 'Consultation summary',
            'group'   => 'Hospital',
            'module'  => 'hospital_manage',
            'default' => 'treatment_summary',
            'params'  => ['Patient name', 'Hospital name', 'Visit date', 'Diagnosis', 'Record link'],
            'blurb'   => 'Sent once a consultation is finished — the summary itself is behind the link.',
        ],
        'prescription' => [
            'label'   => 'Prescription',
            'group'   => 'Hospital',
            'module'  => 'hospital_manage',
            'default' => 'prescription_share',
            'params'  => ['Patient name', 'Hospital name', 'Date', 'Medicine count', 'Record link'],
            'blurb'   => 'Sent when a prescription is finalized — the medicine list is behind the link.',
        ],
        'prescription_pdf' => [
            'label'   => 'Prescription as a PDF',
            'group'   => 'Hospital',
            'module'  => 'hospital_manage',
            'default' => 'prescription_pdf',
            'header'  => 'DOCUMENT',
            'params'  => ['Patient name', 'Hospital name', 'Date', 'Medicine count'],
            'blurb'   => 'The prescription as an attached file rather than a link.',
        ],
        'medicines' => [
            'label'   => 'Medicine instructions',
            'group'   => 'Hospital',
            'module'  => 'hospital_manage',
            'default' => 'medicine_instructions',
            'params'  => ['Patient name', 'Hospital name', 'Medicine schedule', 'Record link'],
            'blurb'   => 'How to take each medicine, sent when a prescription is finalized.',
        ],
        'followup' => [
            'label'   => 'Follow-up reminder',
            'group'   => 'Hospital',
            'module'  => 'hospital_manage',
            'default' => 'followup_reminder',
            'params'  => ['Patient name', 'Hospital name', 'Follow-up date', 'Doctor name'],
            'blurb'   => 'Sent when a patient\'s next visit is booked. Carries no link.',
        ],
        'feedback' => [
            'label'   => 'Feedback request',
            'group'   => 'Hospital',
            'module'  => 'hospital_manage',
            'default' => 'visit_feedback',
            'params'  => ['Patient name', 'Hospital name', 'Visit date'],
            'blurb'   => 'Asks the patient how their visit went, after the delay you set.',
        ],
        'lab' => [
            'label'   => 'Lab report ready',
            'group'   => 'Hospital',
            'module'  => 'hospital_manage',
            'default' => 'lab_report_ready',
            'params'  => ['Patient name', 'Hospital name', 'Tests done', 'Record link'],
            'blurb'   => 'Sent once a lab report is verified — results are behind the link, never in the message.',
        ],
        'radiology' => [
            'label'   => 'Radiology report ready',
            'group'   => 'Hospital',
            'module'  => 'hospital_manage',
            'default' => 'radiology_report_ready',
            'params'  => ['Patient name', 'Hospital name', 'Scan name', 'Record link'],
            'blurb'   => 'Sent once a radiology study is verified — findings are behind the link.',
        ],
        'lab_work' => [
            'label'   => 'Lab work update',
            'group'   => 'Hospital',
            'module'  => 'hospital_manage',
            'default' => 'lab_work_status',
            'params'  => ['Patient name', 'Hospital name', 'What the work is', 'Stage it has reached'],
            'blurb'   => 'Tells a patient their crown, denture, lens or appliance has moved on — sent by hand when staff update the stage.',
        ],
        // Outward to the lab, not the patient. Both carry the clinic's own name as {{2}} because
        // the recipient is a business the clinic deals with, and a lab handling work for six
        // practices needs to know which one is writing before anything else in the message.
        'lab_work_vendor' => [
            'label'   => 'Lab job sent to lab',
            'group'   => 'Hospital',
            'module'  => 'hospital_manage',
            'default' => 'lab_work_vendor_job',
            'params'  => ['Lab name', 'Hospital name', 'What the work is', 'Patient', 'Specification', 'Expected by'],
            'blurb'   => 'Tells an external lab there is a job for them, with the patient and the specification to make it to.',
        ],
        'lab_work_handover' => [
            'label'   => 'Lab work handover',
            'group'   => 'Hospital',
            'module'  => 'hospital_manage',
            'default' => 'lab_work_handover',
            'params'  => ['Lab name', 'Hospital name', 'What the work is', 'Who handed it over and who took it'],
            'blurb'   => 'Confirms to the lab who gave the work over and who carried it away or brought it back.',
        ],
        'lab_work_handover_otp' => [
            'label'   => 'Handover verification code',
            'group'   => 'Hospital',
            'module'  => 'hospital_manage',
            'default' => 'lab_work_handover_otp',
            'params'  => ['Lab name', 'Hospital name', 'Who is at our counter', 'What the work is', 'Code'],
            'blurb'   => 'Sends a one-time code to the lab so their own office can vouch for the person standing at your counter. Goes to the number on the lab record, never to the visitor.',
        ],
        'document' => [
            'label'   => 'Patient document',
            'group'   => 'Hospital',
            'module'  => 'hospital_manage',
            'default' => 'patient_document',
            'header'  => 'DOCUMENT',
            'params'  => ['Patient name', 'Hospital name', 'Document name', 'Date'],
            'blurb'   => 'Any file you send a patient from their record, attached to the message.',
        ],

        // ── Team ────────────────────────────────────────────────────────────────────────
        'staff_forward' => [
            'label'   => 'Forward chat to staff',
            'group'   => 'Team',
            'default' => 'staff_forward',
            'params'  => ['Store name', 'Sender name', 'Sender phone', 'Message'],
            'blurb'   => 'Sent to a staff member when you forward a customer chat to them.',
        ],
    ];

    /**
     * The template a role actually sends, with the platform's suggested name standing in.
     *
     * templateFor() answers null when there is nothing usable, which is right for an automation
     * deciding whether to run at all. These callers want the opposite: attempt the send and let
     * Meta's own refusal reach the vendor, who is then told which template to create. $fallback
     * exists for callers whose suggested name lives on their own class rather than in the role.
     */
    public static function roleTemplate(int $storeId, string $role, ?string $fallback = null): array
    {
        $tpl = static::templateFor($storeId, $role);
        if ($tpl) {
            return ['name' => $tpl['name'], 'language' => $tpl['language']];
        }

        $name = $fallback ?: (self::TEMPLATE_ROLES[$role]['default'] ?? '');

        return ['name' => $name, 'language' => static::templateLanguage($storeId, $name)];
    }

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

        // Meta reads *{{1}}* as a leading parameter and refuses it: bold, italic, strikethrough
        // and monospace marks are formatting, not text. Strip them before the edge checks, or a
        // body that only *looks* like it opens with a word is rejected after the round trip
        // ("Leading or Trailing Params Not Allowed") instead of here.
        $bare = trim(preg_replace('/^[*_~`\s]+|[*_~`\s]+$/u', '', $body));

        if (preg_match('/^\{\{\s*[a-z0-9_]+\s*\}\}/i', $bare)) {
            return 'The message can’t start with a variable. Put some text before it — e.g. "Hi {{1}}" instead of "{{1}}".';
        }
        if (preg_match('/\{\{\s*[a-z0-9_]+\s*\}\}$/i', $bare)) {
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

    protected ?string $purpose;
    protected ?int $numberId;

    /**
     * $purpose routes the send to whichever number the store put in charge of it; $numberId
     * pins one explicitly (replying from the number a customer actually messaged). Both are
     * optional, so every existing caller keeps resolving the store's default exactly as before.
     */
    public function __construct(?int $storeId = null, ?string $purpose = null, ?int $numberId = null)
    {
        $this->storeId = $storeId;
        $this->purpose = $purpose;
        $this->numberId = $numberId;
        $this->cfg = $this->resolveConfig($storeId);
    }

    public static function make(?int $storeId = null, ?string $purpose = null, ?int $numberId = null): self
    {
        return new self($storeId, $purpose, $numberId);
    }

    /** Which of the store's numbers this instance is sending from, when it is sending from one. */
    public function numberId(): ?int
    {
        return $this->cfg['number_id'] ?? null;
    }

    protected function resolveConfig(?int $storeId): array
    {
        // 1) One of the store's own connected numbers: pinned, bound to this purpose, or default.
        if ($storeId && Schema::hasTable('wa_numbers')) {
            $number = $this->numberId
                ? DB::table('wa_numbers')->where('store_id', $storeId)->where('id', $this->numberId)
                    ->where('status', 'active')->first()
                : static::numberFor($storeId, $this->purpose);

            if ($number && $number->phone_number_id && $number->token) {
                return [
                    'phone_number_id'      => $number->phone_number_id,
                    'token'                => $number->token,
                    'business_account_id'  => $number->business_account_id,
                    'api_version'          => $number->api_version ?: config('services.whatsapp.api_version', 'v21.0'),
                    'default_country_code' => config('services.whatsapp.default_country_code', '91'),
                    'source'               => 'vendor',
                    'number_id'            => $number->id,
                ];
            }
        }

        // 2) Legacy single-number columns, for stores connected before wa_numbers existed.
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

        // 3) Global config saved by admin in business_settings.
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

        // 4) .env / config fallback.
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
            // Bounded on purpose. This call sits inside web requests that must finish — raising a
            // bill, saving a consultation — and Meta is a third party whose slow day is not a
            // reason for a clinic's screen to hang. Unbounded, a stalled Graph call keeps the
            // request alive well past any proxy's patience and the user gets a gateway timeout,
            // with nothing in the log to explain it: PHP's max_execution_time counts CPU time, so
            // a socket sitting idle never trips it. 15s is far longer than a healthy send needs.
            $resp = Http::withToken($this->cfg['token'])
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
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
     * Hand a file to WhatsApp's own media store and get back the id a message can carry.
     *
     * The alternative — sending a public link and letting Meta fetch it — needs the file served
     * unauthenticated from our own host, which on this platform means a storage path carved out
     * of the auth rules and a chat attachment readable by anyone holding the URL. Uploading the
     * bytes avoids both: nothing of ours has to be public, and the file lives on Meta's side for
     * the 30 days they keep it.
     *
     * Returns the media id, or null with the reason logged.
     */
    public function uploadMedia(string $path, string $mime, ?string $filename = null): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $url = sprintf(
                'https://graph.facebook.com/%s/%s/media',
                $this->cfg['api_version'],
                $this->cfg['phone_number_id']
            );

            // Longer than a plain send: this one is pushing a file up. Still bounded, for the same
            // reason — a media upload that stalls must fail, not hold the whole request open.
            $resp = Http::withToken($this->cfg['token'])
                ->connectTimeout(5)
                ->timeout(30)
                ->attach('file', file_get_contents($path), $filename ?: basename($path), ['Content-Type' => $mime])
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'type'              => $mime,
                ]);

            $id = data_get($resp->json(), 'id');
            if (!$id) {
                Log::warning('WA media upload failed', ['status' => $resp->status(), 'body' => $resp->json()]);
                return null;
            }

            return (string) $id;
        } catch (\Throwable $e) {
            Log::error('WA media upload exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Pull an INBOUND attachment off Meta's media API and keep our own copy.
     *
     * A photo a customer sends never arrives in the webhook — only an id does, and the bytes sit
     * behind a Graph endpoint that needs the store's token, which is why the inbox could do
     * nothing but print the word "Photo". Two calls: the id resolves to a short-lived CDN URL,
     * and that URL only serves the file to a request carrying the same bearer token.
     *
     * The copy lands under whatsapp/inbox/, which the storage route leaves auth-gated — a picture
     * a patient sent their clinic is not something to publish under a guessable name, and only a
     * logged-in vendor ever looks at it.
     *
     * Returns ['path' => ..., 'mime' => ...] — a path on the public disk, not a URL. The
     * webhook runs on whichever host Meta was given, and the inbox is read on the vendor and
     * admin panels; a link baked here would carry the wrong host to whoever is looking at it.
     * The Spaces mount is shared, so the path resolves from all three.
     */
    public function downloadMedia(string $mediaId, ?string $filename = null): ?array
    {
        if (!$this->isConfigured() || $mediaId === '') {
            return null;
        }

        try {
            $lookup = Http::withToken($this->cfg['token'])->acceptJson()->timeout(20)
                ->get(sprintf('https://graph.facebook.com/%s/%s', $this->cfg['api_version'], $mediaId));

            $link = data_get($lookup->json(), 'url');
            $mime = (string) (data_get($lookup->json(), 'mime_type') ?: 'application/octet-stream');
            if (!$link) {
                Log::warning('WA media lookup failed', ['status' => $lookup->status(), 'body' => $lookup->json()]);
                return null;
            }

            // Meta serves the file only to a token-bearing request, and refuses one that does not
            // look like a browser — a plain fetch comes back 400.
            $file = Http::withToken($this->cfg['token'])
                ->withHeaders(['User-Agent' => 'MyChitti/1.0'])
                ->timeout(60)
                ->get($link);

            if (!$file->successful()) {
                Log::warning('WA media fetch failed', ['status' => $file->status(), 'media' => $mediaId]);
                return null;
            }

            $bytes = $file->body();
            if ($bytes === '' || strlen($bytes) > 26214400) {
                Log::warning('WA media skipped — empty or over 25MB', ['media' => $mediaId, 'bytes' => strlen($bytes)]);
                return null;
            }

            $ext  = static::extensionFor($mime, $filename);
            $dir  = 'whatsapp/inbox/' . ($this->storeId ?: 'platform');
            $name = $dir . '/' . bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');

            Storage::disk('public')->put($name, $bytes);

            return ['path' => $name, 'mime' => $mime];
        } catch (\Throwable $e) {
            Log::error('WA media download exception: ' . $e->getMessage());
            return null;
        }
    }

    /** File extension for a Meta mime type. The sender's own filename wins when there is one. */
    protected static function extensionFor(string $mime, ?string $filename = null): string
    {
        $fromName = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
        if ($fromName !== '' && preg_match('/^[a-z0-9]{1,5}$/', $fromName)) {
            return $fromName;
        }

        $map = [
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
            'video/mp4' => 'mp4', 'video/3gpp' => '3gp',
            'audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a', 'audio/amr' => 'amr',
            'audio/ogg' => 'ogg', 'audio/ogg; codecs=opus' => 'ogg',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/plain' => 'txt',
        ];

        $clean = strtolower(trim(explode(';', $mime)[0]));
        return $map[strtolower($mime)] ?? $map[$clean] ?? 'bin';
    }

    /**
     * Free-form image / video / document, by uploaded media id or by public link.
     *
     * WhatsApp keys the payload on the media kind rather than taking one generic "file", and
     * only a document may carry a filename, so the shape is built per kind here instead of at
     * every call site. The link must be publicly fetchable: Meta pulls the bytes itself.
     */
    public function sendMedia(string $to, string $link, string $kind, ?string $filename = null, ?string $caption = null, ?string $context = null, ?string $mediaId = null): array
    {
        $kind = in_array($kind, ['image', 'video', 'document'], true) ? $kind : 'document';

        // An uploaded id is preferred: it needs nothing of ours to be publicly readable, and it
        // cannot fail on Meta fetching an HTML error page instead of the file.
        $media = $mediaId ? ['id' => $mediaId] : ['link' => $link];
        if ($caption !== null && $caption !== '') {
            $media['caption'] = $caption;
        }
        if ($kind === 'document' && $filename) {
            $media['filename'] = $filename;
        }

        return $this->send([
            'to'   => $this->normalizePhone($to),
            'type' => $kind,
            $kind  => $media,
        ], [
            'body'      => $caption ?: $filename,
            'context'   => $context,
            'media_url' => $link,
        ]);
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
            // Bounded: this runs inline while a page renders, and Laravel's default would let a
            // slow Graph response hold the request open for 30s before anything is drawn.
            $resp = Http::withToken($this->cfg['token'])->acceptJson()
                ->connectTimeout(5)->timeout(12)
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

        // An AUTHENTICATION template does not carry its own wording: Meta writes the body, and the
        // only thing the template supplies is the code. buildComponents() always puts `text` on
        // BODY, which Meta answers with "component of type BODY has unexpected field(s) (text)" —
        // an error that says nothing about the actual problem, which is the category. Caught here
        // so the person reading the toast is told what to change.
        if (strtoupper($category) === 'AUTHENTICATION') {
            return [
                'success' => false,
                'id'      => null,
                'error'   => 'WhatsApp will not accept a body you have written on an AUTHENTICATION template — '
                    . 'Meta supplies the wording for those and the template carries only the code. '
                    . 'Either submit this as a UTILITY template with wording of your own, or build it '
                    . 'as a standard verification template and accept Meta\'s fixed text.',
            ];
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
            // Meta's own ceiling on how many numbers this business may connect — 2 until the
            // business is verified (or it clears a 2,000 messaging limit), then 20. It is not
            // ours to choose: Meta pushes it on the business_capability_update webhook as
            // max_phone_numbers_per_business. NULL = never heard, so no ceiling is enforced.
            'wa_max_numbers'         => 'INT NULL',
            // Picture for templates whose header carries an image (a welcome with a banner, say).
            // NULL falls back to the store's own cover or logo — see headerImageUrl().
            'wa_welcome_image'       => 'VARCHAR(255) NULL',
            // Days after a completed service request before the customer is invited back.
            // NULL means this store never chases — see ServiceRecallReminder.
            'wa_service_recall_days' => 'INT NULL',
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
            static::ensureWelcomeImagePreset();
            static::ensureAdviceNotePreset();
            static::ensureServiceRecallPreset();
            static::ensureHmisPresets();
            static::repairHmisPresetBodies();
            static::ensureRepeatPreset();
            static::ensurePaymentReceiptPreset();
            static::ensureInvoicePreset();
            static::ensureRadiologyPreset();
            static::ensureLabWorkPreset();
            static::ensureLabVendorPresets();
            static::repairLabHandoverBody();
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
        static::ensureInvoicePreset();
        static::ensureRadiologyPreset();
        static::ensureLabWorkPreset();
        static::ensureLabVendorPresets();
        static::repairLabHandoverBody();
    }

    /**
     * The handover preset shipped with {{3}} and {{4}} separated by a bare newline, which Meta
     * refuses to create — parameters must have real text between them, and whitespace is not it.
     *
     * A repair rather than a re-seed: ensureLabVendorPresets() only inserts where the name is
     * absent, so a store that already took the broken row would keep failing to submit it forever.
     * Matched on the broken shape so a vendor who has since reworded their own copy is left alone.
     */
    public static function repairLabHandoverBody(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (DB::table('business_settings')->where('key', 'wa_preset_lab_handover_body_v3')->exists()) {
            return;
        }

        $row = DB::table('wa_template_presets')->where('name', 'lab_work_handover')->first();
        if ($row && strpos((string) $row->body, "{{3}}\n{{4}}") !== false) {
            DB::table('wa_template_presets')->where('id', $row->id)->update([
                'body'       => "Hi {{1}}, confirming a handover with {{2}}.\n\nWork: {{3}}\nHandover: {{4}}\n\nPlease keep this message for your records.",
                'updated_at' => now(),
            ]);
        }

        // The verification-code preset shipped as AUTHENTICATION, which Meta will not create with
        // a body of its own. Moved to UTILITY so the wording survives — see the note on the insert
        // in ensureLabVendorPresets() for what that trades away.
        DB::table('wa_template_presets')
            ->where('name', 'lab_work_handover_otp')
            ->where('category', 'AUTHENTICATION')
            ->update(['category' => 'UTILITY', 'updated_at' => now()]);

        DB::table('business_settings')->updateOrInsert(
            ['key' => 'wa_preset_lab_handover_body_v3'],
            ['value' => '1', 'updated_at' => now(), 'created_at' => now()]
        );
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
     * The welcome message again, with a picture above it — some stores want one and some do not.
     *
     * A separate preset rather than a setting, because the choice is not ours to store: Meta
     * rejects a message whose payload does not match the shape the template was approved with, so
     * the template a store picked IS the answer, per store. The name carries an "_image" suffix
     * (a WABA holds one template per name) and templateFromPreset() binds the welcome role to it
     * on submit, since the automation resolves templates by name.
     *
     * Sent with the store's own cover or logo — see headerImageUrl(). The picture Meta reviews is
     * only a placeholder.
     */
    public static function ensureWelcomeImagePreset(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (DB::table('business_settings')->where('key', 'wa_preset_welcome_image_seeded')->exists()) {
            return;
        }

        if (!DB::table('wa_template_presets')->where('name', 'customer_welcome_image')->exists()) {
            $now = now();
            DB::table('wa_template_presets')->insert([
                'title'         => 'Welcome Message with picture',
                'name'          => 'customer_welcome_image',
                'category'      => 'MARKETING',
                'language'      => 'en_US',
                'header'        => null,
                'header_format' => 'IMAGE',
                'body'          => "Hi {{1}}, thank you for choosing {{2}}! We've added you to our customer list — you'll now receive your bills, updates and offers from us right here on WhatsApp. Reply to this message anytime and we'll be happy to help.",
                'footer'        => 'Reply STOP to unsubscribe',
                'example'       => 'Ramesh | Krishna Hospital',
                'btn_text'      => null,
                'btn_url'       => null,
                'active'        => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        DB::table('business_settings')->updateOrInsert(
            ['key' => 'wa_preset_welcome_image_seeded'],
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
        self::ensureRebookPreset();
        self::ensureVisitRegisteredPreset();

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
     * "It has been a while — due for a check-up?" — the invitation AppointmentRebookReminder
     * sends once a patient is past their doctor's recall interval.
     *
     * MARKETING, not UTILITY: nothing has happened on the patient's account to report. This asks
     * someone who stopped coming to come back, which is what Meta means by marketing, and filing
     * it as utility is how a WABA gets its category corrected the hard way.
     *
     * Stands on its own ABOVE ensureHmisPresets()' seeded-once guard. It used to sit inside that
     * method's list, which meant every hospital seeded before it was added never saw it - the
     * doctor form offered a recall interval, the toggle existed and the sweep ran, but the
     * template it needs could not be created from the suggested list at all.
     */
    public static function ensureRebookPreset(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (DB::table('wa_template_presets')->where('name', 'rebook_reminder')->exists()) {
            return;
        }

        DB::table('wa_template_presets')->insert([
            'title'         => 'Rebook Reminder (Hospital)',
            'name'          => 'rebook_reminder',
            'category'      => 'MARKETING',
            'language'      => 'en_US',
            'header'        => null,
            'header_format' => null,
            'body'          => "Hi {{1}}, it has been a while since your last visit with {{2}} at {{3}}. If you are due for a check-up, reply to this message and we will find you a slot.",
            'footer'        => 'Reply STOP to unsubscribe',
            'example'       => 'Ramesh | Dr. Anita Rao | Krishna Hospital',
            'btn_text'      => null,
            'btn_url'       => null,
            'active'        => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * "Due for a service again?" — the invitation ServiceRecallReminder sends a set number of
     * days after a job is completed.
     *
     * MARKETING, like rebook_reminder and for the same reason: it is a nudge to buy again, and
     * filing that as UTILITY is how a WABA gets its category corrected the hard way.
     */
    public static function ensureServiceRecallPreset(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (DB::table('wa_template_presets')->where('name', 'service_recall')->exists()) {
            return;
        }

        DB::table('wa_template_presets')->insert([
            'title'         => 'Service Due Again',
            'name'          => 'service_recall',
            'category'      => 'MARKETING',
            'language'      => 'en_US',
            'header'        => null,
            'header_format' => null,
            'body'          => "Hi {{1}}, it has been a while since {{2}} looked after your {{3}}. If it is due again, reply to this message and we will book you in.",
            'footer'        => 'Reply STOP to unsubscribe',
            'example'       => 'Ramesh | Sri Electricals | Washing Machine Repair and Services',
            'btn_text'      => null,
            'btn_url'       => null,
            'active'        => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * The fallback for a note typed by hand, when the customer has not messaged recently enough
     * for plain text to reach them. See CustomerNote, which picks between the two.
     *
     * UTILITY: it answers or advises one person about their own visit or order. That keeps notes
     * clear of the per-user marketing cap, which a note nobody can rely on arriving would fail —
     * and it is why the sending screen must say plainly that this is not for offers. A vendor who
     * types marketing copy into a UTILITY template puts their own WABA at risk, not ours.
     */
    public static function ensureAdviceNotePreset(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (DB::table('wa_template_presets')->where('name', 'advice_note')->exists()) {
            return;
        }

        DB::table('wa_template_presets')->insert([
            'title'         => 'Note to a customer',
            'name'          => 'advice_note',
            'category'      => 'UTILITY',
            'language'      => 'en_US',
            'header'        => null,
            'header_format' => null,
            'body'          => "Hi {{1}}, a note from {{2}}: {{3}}",
            'footer'        => 'Reply to this message if you have any questions',
            'example'       => 'Ramesh | Krishna Hospital | Please continue the tablets for three more days and come back if the pain returns',
            'btn_text'      => null,
            'btn_url'       => null,
            'active'        => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * The slip a patient gets as they are booked in: token, doctor, and a link to the visit.
     *
     * UTILITY, and genuinely so — it confirms something the patient just did at the counter, which
     * also keeps it clear of the per-user marketing cap and on the cheaper rate.
     *
     * Above ensureHmisPresets()' seeded-once guard for the same reason patient_document is: below
     * it, no hospital already running would ever be offered this.
     */
    protected static function ensureVisitRegisteredPreset(): void
    {
        if (DB::table('wa_template_presets')->where('name', 'opd_visit_registered')->exists()) {
            return;
        }

        DB::table('wa_template_presets')->insert([
            'title'         => 'OPD Visit Registered (Hospital)',
            'name'          => 'opd_visit_registered',
            'category'      => 'UTILITY',
            'language'      => 'en_US',
            'header'        => null,
            'header_format' => null,
            'body'          => "Hi {{1}}, your OPD visit at {{2}} is registered for {{3}}. Your token number is {{4}} and you will be seen by {{5}}. Open {{6}} for your visit details. Please arrive 10 minutes early and show this message at reception.",
            'footer'        => 'Reply to this message if you need to reschedule',
            'example'       => 'Ramesh | Krishna Hospital | 25 July 2026 | 7 | Dr. Firoz | https://mychitti.net/health-record/abc123',
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
     * Preset behind the imaging report a patient gets once a radiology study is verified.
     *
     * Deliberately the same shape as lab_report_ready — name, hospital, what was done, link — so a
     * hospital running both departments reads one message pattern rather than two. The findings
     * themselves are never in the body: a scan result is behind the link, where it can be read in
     * full rather than skimmed off a lock screen.
     *
     * Its own preset rather than reusing the lab one because a WABA holds one template per name,
     * and a hospital with imaging but no pathology should not have to submit a template that talks
     * about lab tests.
     */
    public static function ensureRadiologyPreset(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (DB::table('wa_template_presets')->where('name', 'radiology_report_ready')->exists()) {
            return;
        }

        DB::table('wa_template_presets')->insert([
            'title'         => 'Radiology Report Ready (Hospital)',
            'name'          => 'radiology_report_ready',
            'category'      => 'UTILITY',
            'language'      => 'en_US',
            'header'        => null,
            'header_format' => null,
            'body'          => "Hi {{1}}, your imaging report from {{2}} is ready. Scan done: {{3}}. Open {{4}} to view or download the full report, including the radiologist's findings and impression. This link works for 30 days.",
            'footer'        => 'Please review the report with your doctor',
            'example'       => 'Ramesh | Krishna Hospital | MRI Brain | https://mychitti.net/health-record/abc123',
            'btn_text'      => null,
            'btn_url'       => null,
            'active'        => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * Preset behind the "your crown is ready" message a clinic sends while work is out at a lab.
     *
     * The only hospital template with no link in it, and deliberately so. There is nothing for the
     * patient to read — the message exists to get them to come in, and a link would just be one
     * more thing between them and doing that. {{4}} carries the stage in the clinic's own words
     * ("Jaw / trial ready", "Ready at lab"), so one template serves a dental lab, an optical
     * counter and an orthotics workshop without any of them reading the others' vocabulary.
     */
    public static function ensureLabWorkPreset(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (DB::table('wa_template_presets')->where('name', 'lab_work_status')->exists()) {
            return;
        }

        DB::table('wa_template_presets')->insert([
            'title'         => 'Lab Work Update (Hospital)',
            'name'          => 'lab_work_status',
            'category'      => 'UTILITY',
            'language'      => 'en_US',
            'header'        => null,
            'header_format' => null,
            'body'          => "Hi {{1}}, an update from {{2}} on your {{3}} — {{4}}. Please visit us at your convenience, or reply to this message to fix a time.",
            'footer'        => 'Please bring this message with you',
            'example'       => 'Ramesh | Krishna Dental | Crown — 16, 17 | Work ready at lab',
            'btn_text'      => null,
            'btn_url'       => null,
            'active'        => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * The two suggested presets that go OUT to a lab rather than in to a patient.
     *
     * Both are UTILITY: they are sent to a business the clinic already deals with, about work that
     * business is doing, which is exactly what Meta means by a transactional message. The job
     * template names the patient because a lab cannot label a box without it — that is the whole
     * point of the message — but it carries nothing clinical beyond the specification, and the
     * handover one carries no patient detail at all, only who passed what to whom.
     *
     * Seeded once each, keyed by name so a clinic that has already built its own is left alone.
     */
    public static function ensureLabVendorPresets(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $now = now();

        if (!DB::table('wa_template_presets')->where('name', 'lab_work_vendor_job')->exists()) {
            DB::table('wa_template_presets')->insert([
                'title'         => 'Lab Job to Lab (Hospital)',
                'name'          => 'lab_work_vendor_job',
                'category'      => 'UTILITY',
                'language'      => 'en_US',
                'header'        => null,
                'header_format' => null,
                'body'          => "Hi {{1}}, {{2}} has a new job for you.\n\nWork: {{3}}\nPatient: {{4}}\nSpecification: {{5}}\nExpected by: {{6}}\n\nPlease reply to confirm you have received this.",
                'footer'        => null,
                'example'       => 'Sri Ceramics | Krishna Dental | Crown — 16, 17 | Ramesh Kumar, 42y/M | Shade: A2, Material: Zirconia, No. of units: 2 | 30 Aug 2026',
                'btn_text'      => null,
                'btn_url'       => null,
                'active'        => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        if (!DB::table('wa_template_presets')->where('name', 'lab_work_handover')->exists()) {
            DB::table('wa_template_presets')->insert([
                'title'         => 'Lab Work Handover (Hospital)',
                'name'          => 'lab_work_handover',
                'category'      => 'UTILITY',
                'language'      => 'en_US',
                'header'        => null,
                'header_format' => null,
                // "Handover: " between {{3}} and {{4}} is load-bearing, not decoration. Meta
                // rejects a body whose parameters are separated by nothing but whitespace, and a
                // newline counts as whitespace — {{3}}\n{{4}} reads to the reviewer as two
                // parameters butted together.
                'body'          => "Hi {{1}}, confirming a handover with {{2}}.\n\nWork: {{3}}\nHandover: {{4}}\n\nPlease keep this message for your records.",
                'footer'        => null,
                'example'       => 'Sri Ceramics | Krishna Dental | Crown — 16, 17 | Handed over by Dr Meera and collected by Suresh on 24 Aug 2026',
                'btn_text'      => null,
                'btn_url'       => null,
                'active'        => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        // UTILITY, though it carries a code. AUTHENTICATION is the category Meta reserves for
        // one-time codes, but an authentication template has no wording of its own — Meta writes
        // the body and the template supplies only the digits. That would throw away the entire
        // point of this message, which is naming the visitor and the work so the lab can refuse
        // it outright: "we did not send Suresh, and that went out last week" is the answer the
        // whole mechanism exists to make possible, and a bare code cannot ask the question.
        //
        // Worth knowing what is being traded: Meta's policy reserves one-time passwords for
        // authentication templates, so this leans on the code being a handover challenge to a
        // business contact rather than a login credential to an account holder.
        if (!DB::table('wa_template_presets')->where('name', 'lab_work_handover_otp')->exists()) {
            DB::table('wa_template_presets')->insert([
                'title'         => 'Handover Verification Code (Hospital)',
                'name'          => 'lab_work_handover_otp',
                'category'      => 'UTILITY',
                'language'      => 'en_US',
                'header'        => null,
                'header_format' => null,
                'body'          => "Hi {{1}}, {{2}} needs to verify a handover.\n\n{{3}}\nWork: {{4}}\n\nVerification code: {{5}}\n\nShare this code only if this person is yours. If you did not send anyone, do not share it and call us straight away.",
                'footer'        => null,
                'example'       => 'Sri Ceramics | Krishna Dental | Suresh is collecting it from us | Crown — 16, 17 | 481923',
                'btn_text'      => null,
                'btn_url'       => null,
                'active'        => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }
    }

    /**
     * Preset behind the bill a customer gets the moment it is raised.
     *
     * A media template, because the bill IS the attachment — a message saying what someone owes,
     * with nothing they can save or show, is worse than no message. {{5}} carries a whole sentence
     * rather than a bare balance so a settled bill reads as a thank-you and an open one as an
     * amount due; a naked "0" would read as a demand.
     *
     * Distinct from payment_receipt, which goes out later when money actually changes hands. One is
     * the ask, the other the acknowledgement, and a customer should be able to tell them apart.
     */
    public static function ensureInvoicePreset(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (DB::table('wa_template_presets')->where('name', 'invoice_ready')->exists()) {
            return;
        }

        DB::table('wa_template_presets')->insert([
            'title'         => 'Bill / Invoice',
            'name'          => 'invoice_ready',
            'category'      => 'UTILITY',
            'language'      => 'en_US',
            'header'        => null,
            'header_format' => 'DOCUMENT',
            'body'          => "Hi {{1}}, here is your bill from {{2}} — invoice {{3}} for {{4}}. {{5}} The bill is attached to this message; please keep it for your records.",
            'footer'        => 'Reply here if anything looks wrong',
            'example'       => 'Ramesh | Krishna Hospital | KHB_H_26-27_12 | ₹1,250.00 | Payment received in full, thank you.',
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
            // Whichever template this store uses as its welcome — the suggested one, or their own
            // if they replaced it. Null means they have neither, so there is nothing to send.
            $tpl = static::templateFor($storeId, 'welcome');
            if (!$tpl) {
                static::noteMissingTemplate($storeId, 'welcome');
                return;
            }
            $storeName = DB::table('stores')->where('id', $storeId)->value('name') ?: 'our store';

            // Some stores want a picture at the top of the welcome and some do not. The template
            // they had approved is the only thing that may decide it — Meta rejects the whole
            // message if the payload does not match the shape the template was created with.
            $header = static::mediaHeaderFor($storeId, $tpl['name'], $tpl['language']);
            if ($header === false) {
                Log::warning("WA welcome skipped for store {$storeId}: template {$tpl['name']} needs a media header this store has no picture for.");
                return;
            }

            // Body vars: {{1}} customer name, {{2}} store name.
            $components = array_merge($header, [[
                'type' => 'body',
                'parameters' => array_map(
                    fn($v) => ['type' => 'text', 'text' => $v],
                    [trim((string) $customerName) ?: 'there', $storeName]
                ),
            ]]);

            $wa->sendTemplate($phone, $tpl['name'], $tpl['language'], $components, 'welcome');
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

    /**
     * Guards the schema checks below. storeHasFeature() calls ensureReceivingTable() for every
     * store on every lead, so without this each fan-out would spend a hasTable plus three
     * hasColumn round trips per store on information_schema for a shape that cannot change
     * mid-request.
     */
    protected static bool $receivingTableChecked = false;

    /** Per-store paid receiving add-ons. Idempotent, no migration files. */
    public static function ensureReceivingTable(): void
    {
        if (static::$receivingTableChecked) {
            return;
        }
        static::$receivingTableChecked = true;

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

        // Monthly wallet renewal. Before these columns existed an add-on was a single manual
        // purchase that pushed active_until one month and was never touched again, so every
        // subscription lapsed silently a month after it was bought.
        if (!Schema::hasColumn('wa_receiving_features', 'auto_renew')) {
            DB::statement("ALTER TABLE `wa_receiving_features` ADD COLUMN `auto_renew` TINYINT(1) NOT NULL DEFAULT 1");
            DB::statement("ALTER TABLE `wa_receiving_features` ADD KEY `waf_renew_idx` (`auto_renew`, `active_until`)");
        }

        // Guards a double charge: the renewal run refuses to bill a store twice on one day
        // however many times it is invoked.
        if (!Schema::hasColumn('wa_receiving_features', 'last_renewed_on')) {
            DB::statement("ALTER TABLE `wa_receiving_features` ADD COLUMN `last_renewed_on` DATE NULL");
        }

        // Rate-limits the "wallet is short" alert to one a day, so a vendor who stays empty
        // through the whole grace window gets a nudge rather than a daily pile.
        if (!Schema::hasColumn('wa_receiving_features', 'last_alert_on')) {
            DB::statement("ALTER TABLE `wa_receiving_features` ADD COLUMN `last_alert_on` DATE NULL");
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
     * Warn a vendor that their wallet could not cover a receiving add-on renewal.
     *
     * Deliberately NOT gated on storeHasFeature(): the whole point is that the add-on is about
     * to lapse (or just has), so checking it would silence the one message that explains why.
     *
     * Logged at every exit for the same reason the lead alerts are — a renewal that fails
     * quietly is what put six stores off WhatsApp for a month without anyone noticing.
     */
    public static function sendAddOnRenewalFailedNotification(
        int $storeId,
        string $featureLabel,
        float $price,
        float $balance,
        ?string $activeUntil = null
    ): void {
        Log::info('ADDON-WA: renewal alert requested', [
            'store_id' => $storeId, 'feature' => $featureLabel, 'price' => $price, 'balance' => $balance,
        ]);

        $store = DB::table('stores')->where('id', $storeId)->first();
        if (!$store || empty($store->phone)) {
            Log::info('ADDON-WA: skipped — store missing or no phone', ['store_id' => $storeId]);
            return;
        }

        $wa = static::make();
        if (!$wa->isConfigured()) {
            Log::info('ADDON-WA: skipped — platform WhatsApp not configured', ['store_id' => $storeId]);
            return;
        }

        $cfg      = Helpers::get_business_settings('whatsapp_config');
        $template = !empty($cfg['addon_low_balance_template'])
            ? $cfg['addon_low_balance_template']
            : self::DEFAULT_ADDON_LOW_BALANCE_TEMPLATE;
        $lang = !empty($cfg['addon_low_balance_template_lang'])
            ? $cfg['addon_low_balance_template_lang']
            : self::DEFAULT_LEAD_TEMPLATE_LANG;

        $vendorName = $store->name ?: 'Vendor';
        $sent = false;

        if ($template) {
            $components = [[
                'type' => 'body',
                'parameters' => array_map(
                    fn($v) => ['type' => 'text', 'text' => $v],
                    [$vendorName, $featureLabel, _price($price), _price($balance)]
                ),
            ]];
            $res  = $wa->sendTemplate($store->phone, $template, $lang, $components, 'addon renewal failed');
            $sent = !empty($res['success']);
            if (!$sent) {
                Log::warning('ADDON-WA: template send failed', [
                    'store_id' => $storeId, 'template' => $template, 'error' => $res['message'] ?? null,
                ]);
            }
        }

        // Plain text only reaches a vendor who messaged us in the last 24 hours, so this is a
        // long shot rather than a safety net. It is still worth trying — and the in-app
        // notification raised alongside it by the renewal command does not share the limit.
        if (!$sent) {
            $until = $activeUntil ? " Your add-on runs out on {$activeUntil}." : '';
            $msg = "Hello {$vendorName}, we could not renew your {$featureLabel} add-on on MyChitti.\n\n"
                . "Amount due: " . _price($price) . "\n"
                . "Wallet balance: " . _price($balance) . "\n\n"
                . "Please top up your wallet to keep receiving lead alerts on WhatsApp."
                . $until;
            $wa->sendText($store->phone, $msg, true, 'addon renewal failed');
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
                // Subscribed at all? A row that has lapsed still exists, and the renewal
                // switch has to stay reachable so the vendor can turn it off while expired.
                'subscribed'   => (bool) $row,
                'auto_renew'   => (bool) ($row->auto_renew ?? false),
                'renews_on'    => $row->active_until ?? null,
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
        if (!$phoneNumberId) {
            return null;
        }

        // wa_numbers first: it knows every number a store owns, where stores.wa_* only ever
        // holds the default. Without this, messages to a second number resolve to no store.
        if (Schema::hasTable('wa_numbers')) {
            $storeId = DB::table('wa_numbers')->where('phone_number_id', $phoneNumberId)->value('store_id');
            if ($storeId) {
                return (int) $storeId;
            }
        }

        if (!static::storeColumnsExist()) {
            return null;
        }

        return DB::table('stores')->where('wa_phone_number_id', $phoneNumberId)->value('id');
    }

    /** Which of a store's numbers an inbound phone_number_id belongs to, for replying in kind. */
    public static function numberIdByPhoneNumberId(?string $phoneNumberId): ?int
    {
        if (!$phoneNumberId || !Schema::hasTable('wa_numbers')) {
            return null;
        }

        $id = DB::table('wa_numbers')->where('phone_number_id', $phoneNumberId)->value('id');

        return $id ? (int) $id : null;
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

        // Set on the inbound question whenever the bot told the sender the team would get back
        // to them. The alert that fires alongside is deduped to one per number per half hour and
        // can be missed in a busy notification list, so the obligation is recorded on the
        // conversation itself: a flag that survives, and that only a human reply clears.
        if (!Schema::hasColumn('whatsapp_messages', 'media_url')) {
            DB::statement("ALTER TABLE `whatsapp_messages` ADD COLUMN `media_url` VARCHAR(500) NULL");
        }

        if (!Schema::hasColumn('whatsapp_messages', 'needs_reply')) {
            DB::statement("ALTER TABLE `whatsapp_messages` ADD COLUMN `needs_reply` TINYINT(1) NOT NULL DEFAULT 0");
            DB::statement("ALTER TABLE `whatsapp_messages` ADD KEY `wam_needs_reply_idx` (`store_id`, `needs_reply`)");
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
            // The history listing groups a store's rows by run_id. With only (store_id, id) to
            // work from, that is a temp table over every row the store has ever sent; ordered by
            // run_id it groups straight off the index, and sent_at rides along for MIN/MAX.
            if (!static::hasIndex('wa_bulk_sends', 'wabs_store_runid')) {
                DB::statement("ALTER TABLE `wa_bulk_sends` ADD KEY `wabs_store_runid` (`store_id`, `run_id`, `sent_at`)");
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
            KEY `wabs_store_run` (`store_id`, `id`),
            KEY `wabs_store_runid` (`store_id`, `run_id`, `sent_at`)
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

    /* ----------------------------------------------------------- phone numbers */

    /**
     * What a number can be put in charge of.
     *
     * The three template roles are here so a store can send appointment reminders from the
     * clinic line and welcomes from the front desk; 'campaign' and 'transactional' cover the
     * sends that no template role describes. Anything left unbound falls back to the store's
     * default number, so a vendor with one number never has to touch this.
     */
    const NUMBER_PURPOSES = [
        'appt_reminder' => ['label' => 'Appointment reminders', 'blurb' => 'Reminders sent before a booked appointment.'],
        'welcome'       => ['label' => 'Customer welcome',      'blurb' => 'Sent once when a customer joins your customer book.'],
        'staff_forward' => ['label' => 'Forwarded staff chats', 'blurb' => 'Sent to a staff member when you forward a chat.'],
        'campaign'      => ['label' => 'Campaigns & bulk',      'blurb' => 'Marketing campaigns and bulk sends.'],
        'transactional' => ['label' => 'Invoices & receipts',   'blurb' => 'Invoice links, receipts and other one-off sends.'],
    ];

    /**
     * A store's connected numbers, and which purpose each one answers for.
     *
     * Kept out of `stores` because that table can hold exactly one number, and a vendor running
     * a clinic line and a reception line needs both live at once. The default row is mirrored
     * back onto stores.wa_* so every caller written against the single-number columns — billing
     * queries, the older jobs — keeps working untouched.
     */
    public static function ensureNumbersTable(): void
    {
        if (!Schema::hasTable('wa_numbers')) {
            DB::statement("CREATE TABLE `wa_numbers` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `phone_number_id` VARCHAR(64) NOT NULL,
                `business_account_id` VARCHAR(64) NULL,
                `token` TEXT NULL,
                `api_version` VARCHAR(12) NULL,
                `display_phone` VARCHAR(32) NULL,
                `verified_name` VARCHAR(190) NULL,
                `label` VARCHAR(120) NULL,
                `is_default` TINYINT(1) NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wa_num_pni` (`phone_number_id`),
                KEY `wa_num_store` (`store_id`, `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!Schema::hasTable('wa_number_bindings')) {
            DB::statement("CREATE TABLE `wa_number_bindings` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `purpose` VARCHAR(40) NOT NULL,
                `wa_number_id` BIGINT UNSIGNED NOT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wa_nb_store_purpose` (`store_id`, `purpose`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    /**
     * How many numbers one store may connect. 0 = no limit.
     *
     * Two independent ceilings, and the lower one wins:
     *   - Meta's, per business — 2 until the business is verified or clears a 2,000 messaging
     *     limit, then 20. Pushed to us on the business_capability_update webhook. We cannot
     *     raise it, and trying to add past it fails inside Meta's own signup window, so it is
     *     worth enforcing here where we can say why.
     *   - ours, an optional global admin cap (whatsapp_config.max_numbers_per_store).
     *
     * Store-scoped because Meta's cap differs per business; omit $storeId for the admin cap only.
     */
    public static function numberLimit(?int $storeId = null): int
    {
        $config = Helpers::get_business_settings('whatsapp_config');
        $adminCap = (int) (is_array($config) ? ($config['max_numbers_per_store'] ?? 0) : 0);

        $metaCap = $storeId ? (int) static::metaNumberCap($storeId) : 0;

        $caps = array_filter([$adminCap, $metaCap], fn($cap) => $cap > 0);

        return $caps ? min($caps) : 0;
    }

    /** Meta's max_phone_numbers_per_business for this store, 0 when it has never been sent. */
    public static function metaNumberCap(int $storeId): int
    {
        static::ensureStoreColumns();

        return (int) (DB::table('stores')->where('id', $storeId)->value('wa_max_numbers') ?? 0);
    }

    /**
     * Record the cap Meta just told us about. Written from the webhook, keyed by the WABA the
     * change arrived for, so a store with several numbers under one business is updated once.
     */
    public static function recordNumberCap(?string $wabaId, ?int $cap): void
    {
        if (!$wabaId || !$cap || $cap < 1) {
            return;
        }

        static::ensureStoreColumns();
        static::ensureNumbersTable();

        $storeIds = DB::table('wa_numbers')->where('business_account_id', $wabaId)
            ->distinct()->pluck('store_id')->all();

        if (!$storeIds) {
            $storeIds = DB::table('stores')->where('wa_business_account_id', $wabaId)->pluck('id')->all();
        }

        if ($storeIds) {
            DB::table('stores')->whereIn('id', $storeIds)->update(['wa_max_numbers' => $cap]);
        }
    }

    /**
     * Bring a pre-wa_numbers connection into the table.
     *
     * A store connected before wa_numbers existed keeps its number only in stores.wa_*. Sending
     * still finds it — resolveConfig() falls back to those columns — so the header reads "Number
     * connected" while the Numbers screen, which reads the table, showed "No number connected
     * yet" beside it.
     *
     * The cosmetic half is the lesser problem. wa_numbers being empty also means the next number
     * added becomes row one, and saveNumber() makes row one the default, which mirrors onto
     * stores.wa_* — every send would have silently moved to the newly added number. Insert-only
     * and keyed on phone_number_id, so running it repeatedly cannot duplicate a row.
     */
    public static function adoptLegacyNumber(int $storeId): void
    {
        if (!Schema::hasTable('wa_numbers') || !static::storeColumnsExist()) {
            return;
        }

        $store = DB::table('stores')->where('id', $storeId)
            ->select('wa_enabled', 'wa_phone_number_id', 'wa_token', 'wa_business_account_id', 'wa_api_version')
            ->first();

        if (!$store || !$store->wa_enabled || !$store->wa_phone_number_id || !$store->wa_token) {
            return;
        }

        if (DB::table('wa_numbers')->where('phone_number_id', $store->wa_phone_number_id)->exists()) {
            return;
        }

        DB::table('wa_numbers')->insert([
            'store_id'            => $storeId,
            'phone_number_id'     => $store->wa_phone_number_id,
            'business_account_id' => $store->wa_business_account_id,
            'token'               => $store->wa_token,
            'api_version'         => $store->wa_api_version,
            // It was the store's only number, so it keeps being the default unless one already
            // claimed the flag — never demote a number that is currently carrying the sends.
            'is_default'          => DB::table('wa_numbers')->where('store_id', $storeId)
                ->where('is_default', 1)->exists() ? 0 : 1,
            'status'              => 'active',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    /** Every number this store has connected, default first. */
    public static function numbers(int $storeId): array
    {
        static::ensureNumbersTable();
        static::adoptLegacyNumber($storeId);

        return DB::table('wa_numbers')
            ->where('store_id', $storeId)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->all();
    }

    /** The store's default number row, or null when it has none. */
    public static function defaultNumber(int $storeId)
    {
        static::ensureNumbersTable();
        static::adoptLegacyNumber($storeId);

        return DB::table('wa_numbers')
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /** Purpose => binding row, for the settings screen. */
    public static function numberBindings(int $storeId): array
    {
        static::ensureNumbersTable();

        return DB::table('wa_number_bindings')
            ->where('store_id', $storeId)
            ->get()
            ->keyBy('purpose')
            ->all();
    }

    /**
     * The number a given purpose should send from: its binding when one exists and still points
     * at an active number, otherwise the store default. A binding pointing at a disconnected
     * number resolves to the default rather than failing — losing a number should degrade the
     * routing, not stop the messages.
     */
    public static function numberFor(int $storeId, ?string $purpose)
    {
        static::ensureNumbersTable();

        if ($purpose && isset(self::NUMBER_PURPOSES[$purpose])) {
            $bound = DB::table('wa_number_bindings as b')
                ->join('wa_numbers as n', 'n.id', '=', 'b.wa_number_id')
                ->where('b.store_id', $storeId)
                ->where('b.purpose', $purpose)
                ->where('n.status', 'active')
                ->select('n.*')
                ->first();

            if ($bound) {
                return $bound;
            }
        }

        return static::defaultNumber($storeId);
    }

    /** Point a purpose at one of the store's numbers. */
    public static function bindNumber(int $storeId, string $purpose, int $numberId): void
    {
        static::ensureNumbersTable();

        DB::table('wa_number_bindings')->updateOrInsert(
            ['store_id' => $storeId, 'purpose' => $purpose],
            ['wa_number_id' => $numberId, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /** Drop a binding, falling the purpose back to the default number. */
    public static function unbindNumber(int $storeId, string $purpose): void
    {
        static::ensureNumbersTable();
        DB::table('wa_number_bindings')->where('store_id', $storeId)->where('purpose', $purpose)->delete();
    }

    /**
     * Record a freshly connected number, or refresh the token on one already connected.
     *
     * Reconnecting the same phone_number_id must not create a second row — Meta hands back the
     * same id, and a duplicate would leave inbound routing picking between two rows with
     * different tokens. The first number a store connects becomes its default.
     */
    public static function saveNumber(int $storeId, array $data): int
    {
        static::ensureNumbersTable();
        // Before is_default is decided below: on a store whose original number was never in this
        // table, the number being added now would otherwise count as the first and take default.
        static::adoptLegacyNumber($storeId);

        $existing = DB::table('wa_numbers')->where('phone_number_id', $data['phone_number_id'])->first();

        $row = [
            'store_id'            => $storeId,
            'phone_number_id'     => $data['phone_number_id'],
            'business_account_id' => $data['business_account_id'] ?? null,
            'token'               => $data['token'] ?? null,
            'api_version'         => $data['api_version'] ?? null,
            'display_phone'       => $data['display_phone'] ?? null,
            'verified_name'       => $data['verified_name'] ?? null,
            'status'              => 'active',
            'updated_at'          => now(),
        ];

        if ($existing) {
            DB::table('wa_numbers')->where('id', $existing->id)->update($row);
            $id = $existing->id;
        } else {
            $row['label']      = $data['label'] ?? null;
            $row['is_default'] = DB::table('wa_numbers')->where('store_id', $storeId)->count() === 0 ? 1 : 0;
            $row['created_at'] = now();
            $id = DB::table('wa_numbers')->insertGetId($row);
        }

        static::mirrorDefaultToStore($storeId);

        return $id;
    }

    /** Make one number the store's default, demoting whichever held it. */
    public static function setDefaultNumber(int $storeId, int $numberId): void
    {
        static::ensureNumbersTable();

        DB::table('wa_numbers')->where('store_id', $storeId)->update(['is_default' => 0]);
        DB::table('wa_numbers')->where('store_id', $storeId)->where('id', $numberId)
            ->update(['is_default' => 1, 'updated_at' => now()]);

        static::mirrorDefaultToStore($storeId);
    }

    /** Disconnect one number, promoting another to default if this one held it. */
    public static function removeNumber(int $storeId, int $numberId): void
    {
        static::ensureNumbersTable();

        $number = DB::table('wa_numbers')->where('store_id', $storeId)->where('id', $numberId)->first();
        if (!$number) {
            return;
        }

        DB::table('wa_number_bindings')->where('store_id', $storeId)->where('wa_number_id', $numberId)->delete();
        DB::table('wa_numbers')->where('id', $numberId)->delete();

        if ($number->is_default) {
            $next = DB::table('wa_numbers')->where('store_id', $storeId)->orderBy('id')->first();
            if ($next) {
                DB::table('wa_numbers')->where('id', $next->id)->update(['is_default' => 1]);
            }
        }

        static::mirrorDefaultToStore($storeId);
    }

    /**
     * Copy the default number back onto stores.wa_*.
     *
     * Everything written before numbers were a table reads those columns — the billing rollups,
     * the older scheduled jobs, the admin screens. Mirroring keeps them all correct without
     * touching a line of them. When the last number goes, the columns are cleared so the store
     * reads as disconnected rather than pointing at a number it no longer owns.
     */
    public static function mirrorDefaultToStore(int $storeId): void
    {
        static::ensureStoreColumns();

        $default = static::defaultNumber($storeId);

        DB::table('stores')->where('id', $storeId)->update($default ? [
            'wa_enabled'             => 1,
            'wa_phone_number_id'     => $default->phone_number_id,
            'wa_token'               => $default->token,
            'wa_business_account_id' => $default->business_account_id,
            'wa_api_version'         => $default->api_version,
        ] : [
            'wa_enabled'             => 0,
            'wa_phone_number_id'     => null,
            'wa_token'               => null,
            'wa_business_account_id' => null,
        ]);
    }

    /* ------------------------------------------------------- template bindings */

    /** Which of the store's own templates fulfils each automated role. */
    public static function ensureBindingsTable(): void
    {
        if (Schema::hasTable('wa_template_bindings')) {
            return;
        }

        DB::statement("CREATE TABLE `wa_template_bindings` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `store_id` BIGINT NOT NULL,
            `role` VARCHAR(40) NOT NULL,
            `template_name` VARCHAR(190) NOT NULL,
            `language` VARCHAR(20) NOT NULL DEFAULT 'en_US',
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `wa_tb_store_role` (`store_id`, `role`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /** Every binding this store has made, keyed by role. */
    public static function templateBindings(int $storeId): array
    {
        static::ensureBindingsTable();

        return DB::table('wa_template_bindings')
            ->where('store_id', $storeId)
            ->get()
            ->keyBy('role')
            ->all();
    }

    /** How many positional variables a role's template must carry. */
    public static function roleParamCount(string $role): int
    {
        return count(self::TEMPLATE_ROLES[$role]['params'] ?? []);
    }

    /**
     * The template to send for one automated role: the store's binding if it has made one, else
     * the platform default — but only when that default actually exists on their WABA.
     *
     * Returns null when there is nothing sendable, and the caller must treat that as "skip and
     * tell somebody" rather than pressing on with a guess. A vendor who deleted the suggested
     * template and wrote their own is the ordinary case this exists for: we cannot know which of
     * their templates means "welcome", so we ask them once and remember the answer.
     */
    public static function templateFor(int $storeId, string $role): ?array
    {
        if (!isset(self::TEMPLATE_ROLES[$role])) {
            return null;
        }

        static::ensureBindingsTable();
        $binding = DB::table('wa_template_bindings')
            ->where('store_id', $storeId)->where('role', $role)->first();

        $name = $binding->template_name ?? self::TEMPLATE_ROLES[$role]['default'];

        // A binding can rot the same way the default did — the vendor may delete the template it
        // points at — so existence is checked either way rather than trusted.
        if (!static::templateExists($storeId, $name)) {
            return null;
        }

        return [
            'name'     => $name,
            'language' => static::templateLanguage($storeId, $name, $binding->language ?? null),
            'bound'    => (bool) $binding,
        ];
    }

    /**
     * Is this template approved on the store's WABA right now?
     *
     * Stricter than templateExists(), and for a different job: existence is enough to decide
     * whether a feature is configured, but an automated send needs to know Meta will actually
     * accept it. A PENDING template is accepted by nothing — the message fails, the store is
     * billed for it anyway (charges are taken at dispatch), and any dedupe row it claimed is
     * spent, so the record never gets a second attempt once approval lands.
     *
     * An unreadable list still means "unknown", never "not approved": a Graph blip must not
     * silently switch off every automation, so the caller proceeds and lets the send report
     * the truth itself.
     */
    public static function templateApproved(int $storeId, string $name): bool
    {
        $statuses = static::templateStatuses($storeId);
        if ($statuses === []) {
            return true;
        }

        return strtoupper((string) ($statuses[strtolower($name)] ?? '')) === 'APPROVED';
    }

    /** Is a template of this name on the store's WABA at all, in any language? */
    public static function templateExists(int $storeId, string $name): bool
    {
        $statuses = static::templateStatuses($storeId);

        // An unreadable list must not be read as "deleted" — a Graph blip would silently stop
        // every automated message. Assume it is there and let the send report the truth.
        return $statuses === [] || isset($statuses[strtolower($name)]);
    }

    /**
     * The template a role will actually send, given the platform's suggested name for it.
     *
     * Screens that describe an automation name the suggested template, because that is all they
     * know statically. A store may have pointed the role at one of its own under Automation →
     * template roles, and reading the suggested name there warns about a template the store
     * deliberately replaced — and keeps warning after they have already fixed it. Falls back to
     * the name given, so a template with no role behind it is returned untouched.
     */
    public static function effectiveTemplateName(int $storeId, string $default): string
    {
        foreach (self::TEMPLATE_ROLES as $role => $meta) {
            if (strtolower($meta['default']) !== strtolower($default)) {
                continue;
            }
            return static::templateBindings($storeId)[$role]->template_name ?? $default;
        }

        return $default;
    }

    /** Point a role at one of the store's templates. */
    public static function bindTemplate(int $storeId, string $role, string $name, string $language): void
    {
        static::ensureBindingsTable();

        DB::table('wa_template_bindings')->updateOrInsert(
            ['store_id' => $storeId, 'role' => $role],
            [
                'template_name' => $name,
                'language'      => $language ?: 'en_US',
                'updated_at'    => now(),
                'created_at'    => now(),
            ]
        );

        static::clearMissingTemplate($storeId, $role);
    }

    /** Drop a binding, falling the role back to the platform default. */
    public static function unbindTemplate(int $storeId, string $role): void
    {
        static::ensureBindingsTable();
        DB::table('wa_template_bindings')->where('store_id', $storeId)->where('role', $role)->delete();
    }

    /**
     * Record that a role had no usable template when something tried to send it.
     *
     * Without this the failure is invisible: a vendor who deletes the suggested template simply
     * stops receiving welcomes, nothing errors anywhere a human looks, and it is found weeks
     * later by reading an inbox. The flag is what the setup screen reads to say which roles are
     * broken, and is cleared the moment one is bound.
     */
    public static function noteMissingTemplate(int $storeId, string $role): void
    {
        try {
            Cache::put('wa_tpl_missing_' . $storeId . '_' . $role, now()->toDateTimeString(), 86400 * 30);
            Log::warning('WhatsApp automation has no template for a role', [
                'store' => $storeId,
                'role'  => $role,
            ]);
        } catch (\Throwable $e) {
            // Never let bookkeeping break a send path.
        }
    }

    /** When this role last had nothing to send, or null if it has been fine. */
    public static function missingTemplateSince(int $storeId, string $role): ?string
    {
        try {
            return Cache::get('wa_tpl_missing_' . $storeId . '_' . $role);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function clearMissingTemplate(int $storeId, string $role): void
    {
        try {
            Cache::forget('wa_tpl_missing_' . $storeId . '_' . $role);
        } catch (\Throwable $e) {
            // Nothing to do.
        }
    }

    /**
     * Days Meta reserves a deleted template's NAME before it can be used again.
     *
     * This is the trap behind most "just re-create it" advice: deleting `customer_welcome` does
     * not free the name, so the obvious fix is unavailable for a month and the vendor is left
     * with automation that cannot be repaired by re-creating what they had. Binding a different
     * template to the role is the only same-day fix — see TEMPLATE_ROLES.
     */
    const TEMPLATE_NAME_LOCK_DAYS = 30;

    /**
     * Alternative names for a template whose own name is locked, so a vendor who has just
     * deleted one is not left guessing at something Meta will accept.
     *
     * Meta only allows lowercase letters, numbers and underscores, so every suggestion is
     * already in that shape. Anything the store still has is filtered out — offering a name
     * that is taken is as useless as offering the locked one.
     */
    public static function suggestTemplateNames(string $base, array $taken = []): array
    {
        $base = strtolower(preg_replace('/[^a-z0-9_]/i', '_', $base) ?? $base);
        $base = trim(preg_replace('/_+/', '_', $base) ?? $base, '_') ?: 'template';

        // Strip a trailing version marker so v2 does not become v2_v2 on the second attempt.
        $stem = preg_replace('/_(v\d+|\d+|new|updated)$/', '', $base) ?: $base;
        $year = now()->year;

        $candidates = [
            $stem . '_v2',
            $stem . '_' . $year,
            $stem . '_new',
            'my_' . $stem,
            $stem . '_v3',
        ];

        $taken = array_map('strtolower', $taken);
        $out = [];
        foreach ($candidates as $candidate) {
            $candidate = mb_substr($candidate, 0, 60);
            if ($candidate !== $base && !in_array($candidate, $taken, true) && !in_array($candidate, $out, true)) {
                $out[] = $candidate;
            }
        }

        return array_slice($out, 0, 4);
    }

    /** Header formats that carry a file rather than text, and so need media at send time. */
    const MEDIA_HEADERS = ['IMAGE', 'VIDEO', 'DOCUMENT'];

    /**
     * The language this template is actually approved in on THIS store's WABA.
     *
     * The preset table says what language the platform blueprint was written in, but the vendor
     * is the one who submitted the template to Meta, and Meta stores a template per language. Ask
     * for a locale the vendor did not create and Graph answers
     * "(#132001) Template name does not exist in the translation" — the template exists, just not
     * in the language requested. So the preset's language is a preference, never an assertion.
     *
     * Prefers an exact match on $preferred, then any APPROVED language, then whatever exists.
     * Falls back to $preferred unchanged when the list cannot be read, so a Graph outage behaves
     * exactly as it did before rather than silently switching language.
     */
    public static function templateLanguage(int $storeId, string $name, ?string $preferred = null): string
    {
        $fallback = $preferred ?: 'en_US';

        try {
            $key = 'wa_tpl_lang_' . $storeId . '_' . md5(strtolower($name . '|' . $fallback));
            return Cache::remember($key, 600, function () use ($storeId, $name, $fallback) {
                $wa = static::make($storeId);
                if ($wa->source() !== 'vendor' || !$wa->hasWaba()) {
                    return $fallback;
                }

                $res = $wa->listTemplates();
                if (!$res['success']) {
                    return $fallback;
                }

                $languages = [];
                foreach ($res['data'] as $tpl) {
                    if (strtolower((string) data_get($tpl, 'name')) !== strtolower($name)) {
                        continue;
                    }
                    $languages[] = [
                        'language' => (string) data_get($tpl, 'language', ''),
                        'approved' => strtoupper((string) data_get($tpl, 'status')) === 'APPROVED',
                    ];
                }

                if (!$languages) {
                    return $fallback;
                }

                foreach ($languages as $row) {
                    if (strtolower($row['language']) === strtolower($fallback)) {
                        return $row['language'];
                    }
                }
                foreach ($languages as $row) {
                    if ($row['approved'] && $row['language'] !== '') {
                        return $row['language'];
                    }
                }

                return $languages[0]['language'] ?: $fallback;
            });
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

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
     * The header components an automated send must carry for this store's bound template.
     *
     * Returns [] when the template has a TEXT header or none — the overwhelming majority — so a
     * caller can array_merge() it in front of the body unconditionally. Returns FALSE when the
     * template wants a picture and the store has none to give: the send must then be abandoned,
     * because a media template sent without its header is rejected whole with (#132012), and a
     * message that fails at Meta is still billed at dispatch.
     *
     * Only IMAGE is satisfiable automatically. A welcome bound to a VIDEO or DOCUMENT template
     * is asking for a file per recipient that nothing here can produce, so it is refused rather
     * than guessed at.
     */
    public static function mediaHeaderFor(int $storeId, string $name, ?string $lang = null)
    {
        $format = static::templateHeaderFormat($storeId, $name, $lang);
        if ($format === null || $format === 'TEXT') {
            return [];
        }
        if ($format !== 'IMAGE') {
            return false;
        }

        $url = static::headerImageUrl($storeId);
        return $url ? [static::mediaHeaderComponent('IMAGE', $url)] : false;
    }

    /**
     * The picture a media-header template carries for this store, as a URL Meta can fetch.
     *
     * An explicitly chosen welcome image wins; otherwise the store's own cover or logo stands in,
     * so a vendor who picks an image template gets something sensible without configuring
     * anything. Built on the canonical /storage path rather than asset('storage/app/public/…'),
     * which only resolves through a 301 — Meta is fetching this, not a browser.
     */
    public static function headerImageUrl(int $storeId): ?string
    {
        $store = DB::table('stores')->where('id', $storeId)->first();
        if (!$store) {
            return null;
        }

        foreach ([
            ['wa_welcome_image', 'whatsapp/header/'],
            ['cover_photo',      'store/cover/'],
            ['logo',             'store/'],
        ] as [$column, $dir]) {
            $file = $store->{$column} ?? null;
            if (is_string($file) && trim($file) !== '') {
                return url('storage/' . $dir . ltrim(trim($file), '/'));
            }
        }

        return null;
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
                // Where the file lives, so the chat can show the picture back rather than the
                // word "[image]". Null on every text message.
                'media_url' => isset($meta['media_url']) ? mb_substr((string) $meta['media_url'], 0, 500) : null,
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
