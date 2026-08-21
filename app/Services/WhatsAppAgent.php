<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\OpdConsultationReceipt;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\ServiceRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The WhatsApp AI Agent: it answers from the store's Auto-Reply Knowledge, and on top of that
 * manages leads and appointments — book, reschedule, report status — plus whichever records the
 * vendor has allowed it to share (prescription, diagnosis, bill, reminders).
 *
 * The plan decides whether it runs at all. Basic has no chatbot; Starter and Pro have the agent,
 * and it is on from the moment the plan is. There is no bot-type choice any more: the agent is a
 * superset of the old knowledge-only bot and costs exactly the same to run, so asking the vendor
 * to pick between them only ever left them paying for something they were not receiving.
 *
 * What the agent may DO and SAY is still the vendor's call, item by item (SHARE_ITEMS). Every
 * item is opt-in per store: the customer's records only reach the model for the items the vendor
 * ticked, so what the bot cannot see it cannot say. Turning `booking` off leaves an agent that
 * answers questions but never acts on the vendor's behalf.
 */
class WhatsAppAgent
{
    /**
     * What the AI Agent may do, and what it may tell a customer about their lead / appointment.
     * 'default' is what a store gets before the vendor has touched the settings.
     */
    const SHARE_ITEMS = [
        'booking' => [
            'label'   => 'Book and reschedule appointments',
            'desc'    => 'Let the agent act, not just answer — it can take a booking or move one. Turn this off for an agent that answers questions and hands anything else to your team.',
            'default' => 1,
        ],
        'status' => [
            'label'   => 'Lead & appointment status',
            'desc'    => 'Whether their request or appointment is pending, confirmed, completed or cancelled.',
            'default' => 1,
        ],
        'reminder' => [
            'label'   => 'Appointment reminders',
            'desc'    => 'Automatic reminder before the appointment (uses your approved appointment_reminder template).',
            'default' => 1,
        ],
        'diagnosis' => [
            'label'   => 'Diagnosis',
            'desc'    => 'The diagnosis recorded on their last visit.',
            'default' => 0,
        ],
        'prescription' => [
            'label'   => 'Prescription',
            'desc'    => 'Medicines, dosage and duration from their last prescription.',
            'default' => 0,
        ],
        'bill' => [
            'label'   => 'Bill & dues',
            'desc'    => 'Their last bill amount, what is paid and what is due.',
            'default' => 0,
        ],
    ];

    /** How far back records stay relevant to a WhatsApp question. */
    const RECORD_WINDOW_DAYS = 120;

    public static function ensureTable(): void
    {
        if (!Schema::hasTable('wa_agent_shares')) {
            DB::statement("CREATE TABLE `wa_agent_shares` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `item` VARCHAR(30) NOT NULL,
                `enabled` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wa_share_store_item` (`store_id`, `item`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    /* ------------------------------------------------------------ availability */

    /**
     * Is the AI Agent answering for this store? Purely a plan question now — a paid-up
     * Starter/Pro has it, Basic does not, and there is nothing for the vendor to switch on.
     */
    public static function isAgent(int $storeId): bool
    {
        return WhatsAppBilling::agentActive($storeId);
    }

    /**
     * May the agent actually book or reschedule, rather than only answer? Vendors who want a
     * read-only agent untick `booking` and everything else it does stays as it was.
     */
    public static function canBook(int $storeId): bool
    {
        return static::isAgent($storeId) && static::shareEnabled($storeId, 'booking');
    }

    /** Which token pool this store's replies are metered against. */
    public static function pool(int $storeId): string
    {
        return WhatsAppBilling::POOL_PLAN;
    }

    /* ----------------------------------------------------------- share items */

    /** All share items with their current on/off state for this store. */
    public static function shares(int $storeId): array
    {
        static::ensureTable();
        $rows = DB::table('wa_agent_shares')->where('store_id', $storeId)->pluck('enabled', 'item');

        $out = [];
        foreach (self::SHARE_ITEMS as $key => $meta) {
            $out[$key] = isset($rows[$key]) ? (bool) $rows[$key] : (bool) $meta['default'];
        }
        return $out;
    }

    public static function shareEnabled(int $storeId, string $item): bool
    {
        return static::shares($storeId)[$item] ?? false;
    }

    /** Save the vendor's picks. Items missing from $enabled are turned off. */
    public static function saveShares(int $storeId, array $enabled): void
    {
        static::ensureTable();
        foreach (array_keys(self::SHARE_ITEMS) as $item) {
            DB::table('wa_agent_shares')->updateOrInsert(
                ['store_id' => $storeId, 'item' => $item],
                [
                    'enabled'    => in_array($item, $enabled, true) ? 1 : 0,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    /* ------------------------------------------------------- prompt sections */

    /**
     * The AI Agent's section of the auto-reply system prompt: what it may do, and this
     * customer's own records for the items the vendor allowed. Returns '' for the knowledge bot.
     *
     * Records are injected rather than fetched on demand — one AI call, and the model can only
     * repeat what is in front of it, which is exactly the set the vendor ticked.
     */
    public static function promptSection(int $storeId, string $phoneKey, string $fromPhone = ''): string
    {
        if (!static::isAgent($storeId)) {
            return '';
        }

        $shares  = static::shares($storeId);
        $section = "\n\nAI AGENT — you also handle this business's leads and appointments.\n";

        // Booking / rescheduling tooling (HMIS stores with doctors on file). Withheld entirely
        // when the vendor has turned booking off: a model that is never shown the markers cannot
        // emit them, which is a firmer guarantee than asking it not to act.
        if ($shares['booking'] ?? false) {
            $appointments = WhatsAppAppointmentBot::promptSection($storeId, $phoneKey);
            if ($appointments !== '') {
                $section .= $appointments . "\n";
            }
        } else {
            // "The team will handle it" has to be more than words: without the marker nothing is
            // flagged, nobody is notified, and the customer waits on a promise no one heard.
            $section .= "You cannot book, move or cancel appointments. If the customer asks for any of "
                . "that, tell them the team will take care of it and append "
                . \App\Jobs\SendAutoReply::ESCALATE_MARKER . " so a human picks it up.\n";
        }

        $records = static::recordsFor($storeId, $phoneKey, $shares);
        if ($records !== '') {
            $section .= "\nTHIS CUSTOMER'S RECORDS (the business has allowed you to share these — quote them exactly, never guess):\n"
                . $records . "\n";
        }

        $blocked = [];
        foreach (['diagnosis', 'prescription', 'bill'] as $item) {
            if (empty($shares[$item])) {
                $blocked[] = self::SHARE_ITEMS[$item]['label'];
            }
        }
        if ($blocked) {
            $section .= "\nNEVER share over WhatsApp: " . implode(', ', $blocked)
                . '. If asked, say the team will share it with them directly and append ' . \App\Jobs\SendAutoReply::ESCALATE_MARKER . ".\n";
        }

        $section .= "\nAGENT RULES:\n"
            . "- Only discuss the records listed above. If something is not listed, you do not have it — do not invent it.\n"
            . "- Confirm who you are speaking to before sharing anything personal; this number may be shared in a family.\n"
            . "- Never share another customer's details.\n";

        return $section;
    }

    /**
     * Build the customer's record block from real data, one line per allowed item.
     * Non-clinical stores simply have no clinical rows, so those blocks come back empty.
     */
    protected static function recordsFor(int $storeId, string $phoneKey, array $shares): string
    {
        $lines = [];
        $since = now()->subDays(self::RECORD_WINDOW_DAYS);

        try {
            if (!empty($shares['status'])) {
                $lines = array_merge($lines, static::statusLines($storeId, $phoneKey, $since));
            }

            $patientId = static::patientId($storeId, $phoneKey);
            if ($patientId) {
                if (!empty($shares['diagnosis'])) {
                    $lines = array_merge($lines, static::diagnosisLines($storeId, $patientId, $since));
                }
                if (!empty($shares['prescription'])) {
                    $lines = array_merge($lines, static::prescriptionLines($storeId, $patientId, $since));
                }
                if (!empty($shares['bill'])) {
                    $lines = array_merge($lines, static::billLines($storeId, $patientId, $since));
                }
            }
        } catch (\Throwable $e) {
            // A missing HMIS table (non-hospital store) must not break the reply.
            \Illuminate\Support\Facades\Log::warning('WA agent records lookup failed (store ' . $storeId . '): ' . $e->getMessage());
        }

        return implode("\n", $lines);
    }

    /** Lead (service request) and appointment status. */
    protected static function statusLines(int $storeId, string $phoneKey, Carbon $since): array
    {
        $lines = [];

        $userId = DB::table('users')
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$phoneKey])
            ->value('id');

        if ($userId) {
            $requests = ServiceRequest::where('user_id', $userId)
                ->whereRaw('FIND_IN_SET(?, sent_to)', [$storeId])
                ->where('created_at', '>=', $since)
                ->orderByDesc('id')->limit(3)->get(['id', 'status', 'created_at', 'requirements']);

            foreach ($requests as $r) {
                $lines[] = '- Request #' . $r->id . ' (' . Carbon::parse($r->created_at)->format('d M Y') . '): status '
                    . str_replace('_', ' ', (string) $r->status)
                    . ($r->requirements ? ' — "' . mb_substr((string) $r->requirements, 0, 80) . '"' : '');
            }
        }

        if (Schema::hasTable('appointments')) {
            $patientId = static::patientId($storeId, $phoneKey);
            if ($patientId) {
                $appts = Appointment::with('doctorProfile.employee')
                    ->where('store_id', $storeId)
                    ->where('patient_id', $patientId)
                    ->where('appointment_date', '>=', $since->toDateString())
                    ->orderByDesc('appointment_date')->limit(3)->get();

                foreach ($appts as $a) {
                    $dr = trim(($a->doctorProfile->employee->f_name ?? '') . ' ' . ($a->doctorProfile->employee->l_name ?? ''));
                    $lines[] = '- Appointment #' . $a->id . ' on ' . Carbon::parse($a->appointment_date)->format('d M Y')
                        . ' at ' . Carbon::parse($a->appointment_time ?: '00:00')->format('h:i A')
                        . ($dr ? ' with Dr. ' . $dr : '')
                        . ': status ' . str_replace('_', ' ', (string) $a->status);
                }
            }
        }

        return $lines;
    }

    protected static function diagnosisLines(int $storeId, int $patientId, Carbon $since): array
    {
        if (!Schema::hasTable('opd_visits')) {
            return [];
        }

        $visit = OpdVisit::where('store_id', $storeId)->where('patient_id', $patientId)
            ->where('visit_date', '>=', $since->toDateString())
            ->whereNotNull('diagnosis')->where('diagnosis', '!=', '')
            ->orderByDesc('visit_date')->first(['visit_date', 'diagnosis', 'treatment']);

        if (!$visit) {
            return [];
        }

        $line = '- Diagnosis (' . Carbon::parse($visit->visit_date)->format('d M Y') . '): ' . $visit->diagnosis;
        if ($visit->treatment) {
            $line .= '. Advice: ' . mb_substr((string) $visit->treatment, 0, 200);
        }
        return [$line];
    }

    protected static function prescriptionLines(int $storeId, int $patientId, Carbon $since): array
    {
        if (!Schema::hasTable('prescriptions')) {
            return [];
        }

        $rx = Prescription::with('items')
            ->where('store_id', $storeId)->where('patient_id', $patientId)
            ->where('is_finalized', 1)
            ->where('created_at', '>=', $since)
            ->orderByDesc('id')->first();

        if (!$rx || $rx->items->isEmpty()) {
            return [];
        }

        $meds = $rx->items->map(function ($i) {
            return trim($i->medicine_name . ' ' . ($i->dosage ?: '') . ' ' . ($i->frequency ? '(' . $i->frequency . ')' : '')
                . ($i->duration ? ' for ' . $i->duration : ''));
        })->implode('; ');

        $lines = ['- Prescription (' . Carbon::parse($rx->created_at)->format('d M Y') . '): ' . $meds];
        if ($rx->follow_up_date) {
            $lines[] = '- Follow-up advised on ' . Carbon::parse($rx->follow_up_date)->format('d M Y');
        }
        return $lines;
    }

    protected static function billLines(int $storeId, int $patientId, Carbon $since): array
    {
        if (!Schema::hasTable('opd_consultation_receipts')) {
            return [];
        }

        $bill = OpdConsultationReceipt::where('store_id', $storeId)->where('patient_id', $patientId)
            ->where('receipt_date', '>=', $since->toDateString())
            ->orderByDesc('id')->first(['bill_no', 'receipt_date', 'amount', 'paid', 'due']);

        if (!$bill) {
            return [];
        }

        return ['- Bill ' . ($bill->bill_no ?: '') . ' (' . Carbon::parse($bill->receipt_date)->format('d M Y') . '): total '
            . _price($bill->amount) . ', paid ' . _price($bill->paid) . ', due ' . _price($bill->due)];
    }

    /** The store's patient record for this WhatsApp number, if any. */
    protected static function patientId(int $storeId, string $phoneKey): ?int
    {
        if (!Schema::hasTable('patients')) {
            return null;
        }
        $id = Patient::where('store_id', $storeId)
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$phoneKey])
            ->orderByDesc('id')
            ->value('id');

        return $id ? (int) $id : null;
    }
}
