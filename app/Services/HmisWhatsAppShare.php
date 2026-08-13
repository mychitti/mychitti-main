<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\HospitalActivityLog;
use App\Models\LabOrder;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Sends a patient their own records on WhatsApp — consultation summary, prescription, how to take
 * the medicines, follow-up, lab report, and a feedback request.
 *
 * Everything goes out on an approved template, because a patient is almost never inside the 24h
 * window when their report is ready. A template parameter cannot carry line breaks, so anything
 * longer than a sentence (a medicine list, a panel of results) is summarised in the message and
 * the detail lives behind a one-per-send link — see shareUrl(). That also means the record itself
 * never sits in WhatsApp's message history, which for medical data is the safer place for it.
 *
 * Every send is best-effort from the caller's point of view: it returns a result array and never
 * throws, so a failed message can't roll back the clinical record that triggered it.
 */
class HmisWhatsAppShare
{
    /** How long a shared record link stays open. Long enough to read on a slow day, not forever. */
    const LINK_DAYS = 30;

    /**
     * Where generated PDFs live on the public disk (a DO Spaces mount).
     *
     * Public because Meta downloads the file itself when the message is sent — there is no way to
     * put a session in front of it. The random filename is therefore the only thing protecting it,
     * and PruneSharedPdfs deletes each file once Meta has had time to fetch it.
     */
    const PDF_DIR = 'hmis-shared';

    /**
     * Where each hospital template is actually used, shown on the suggested-templates card.
     *
     * Doubles as the list of hospital-only presets: a store without the hospital module never sees
     * these offered, since it could never send one.
     */
    const PRESET_USES = [
        'opd_visit_registered'  => 'Sent automatically when an OPD visit is booked in, if “Visit registered” is on.',
        'treatment_summary'     => 'Powers “Send summary” on the OPD consultation screen.',
        'prescription_share'    => 'Powers “Send prescription” on a prescription.',
        'prescription_pdf'      => 'Powers “Send as PDF” on a prescription — attaches the file to the chat.',
        'medicine_instructions' => 'Powers “Send medicine instructions” on a prescription.',
        'followup_reminder'     => 'Powers “Send follow-up” on a prescription and “Send reminder” on an appointment.',
        'visit_feedback'        => 'Powers “Ask for feedback” after an OPD visit or a completed appointment.',
        'lab_report_ready'      => 'Powers “WhatsApp” on Lab → Reports, once a report is verified.',
        'patient_document'      => 'Powers “Send a document” on a patient — attaches any file from their record to the chat.',
    ];

    /**
     * What can be sent, and the template each one needs. The name is what the vendor's approved
     * template must be called; the preset of the same name creates it in one click.
     */
    const KINDS = [
        // Sent the moment the visit is booked in, which is why it carries the token and the
        // doctor rather than a diagnosis — at registration there is no diagnosis yet. The
        // consultation summary below is the same patient's follow-up, once they have been seen.
        'visit_registered' => [
            'label'    => 'Visit registered',
            'template' => 'opd_visit_registered',
            'context'  => 'visit registered',
        ],
        'treatment' => [
            'label'    => 'Consultation summary',
            'template' => 'treatment_summary',
            'context'  => 'treatment',
        ],
        'prescription' => [
            'label'    => 'Prescription',
            'template' => 'prescription_share',
            'context'  => 'prescription',
        ],
        'prescription_pdf' => [
            'label'    => 'Prescription (PDF)',
            'template' => 'prescription_pdf',
            'context'  => 'prescription pdf',
        ],
        'medicines' => [
            'label'    => 'Medicine instructions',
            'template' => 'medicine_instructions',
            'context'  => 'medicine instructions',
        ],
        'followup' => [
            'label'    => 'Follow-up reminder',
            'template' => 'followup_reminder',
            'context'  => 'follow-up',
        ],
        'feedback' => [
            'label'    => 'Feedback request',
            'template' => 'visit_feedback',
            'context'  => 'feedback',
        ],
        'lab' => [
            'label'    => 'Lab report',
            'template' => 'lab_report_ready',
            'context'  => 'lab report',
        ],
        // Anything the hospital decides to send that this system did not generate — a discharge
        // summary typed in Word, an insurance form, a scan a patient brought in from elsewhere.
        // Manual only: there is no rule that could decide on its own when to send one.
        'document' => [
            'label'    => 'Document',
            'template' => 'patient_document',
            'context'  => 'document',
        ],
    ];

    /**
     * Which Send Notifications toggle governs each automatic send, and how each one is triggered.
     * A kind absent here can only ever be sent by hand.
     */
    const AUTO_PREFS = [
        'visit_registered' => 'hmis_visit_registered',
        'treatment'        => 'hmis_treatment',
        'prescription'     => 'hmis_prescription',
        'prescription_pdf' => 'hmis_prescription_pdf',
        'medicines'        => 'hmis_medicines',
        'followup'     => 'hmis_followup',
        'feedback'     => 'hmis_feedback',
        // Same toggle as 'feedback', separate kind on purpose. The dedupe key is
        // (store, kind, record_id) with no source in it, so a walk-in visit #7 keyed as 'feedback'
        // would collide with appointment #7's feedback and silently suppress one of them. A
        // distinct kind keeps the two id spaces apart.
        'feedback_opd' => 'hmis_feedback',
        'lab'          => 'hmis_lab_report',
    ];

    /** Hours after a completed visit before the feedback request goes, when the vendor hasn't chosen. */
    const DEFAULT_FEEDBACK_DELAY_HOURS = 4;

    /** Days before the follow-up date to remind, when the vendor hasn't chosen. 0 = at booking. */
    const DEFAULT_FOLLOWUP_LEAD_DAYS = 1;

    /** Hour of the day a scheduled message goes out — nobody wants a 3am reminder. */
    const SEND_HOUR = 10;

    /** How long after its due time a queued message is still worth sending. */
    const STALE_HOURS = 48;

    /** The vendor's chosen wait before a feedback request, in hours. */
    public static function feedbackDelayHours(int $storeId): int
    {
        $raw = DB::table('stores')->where('id', $storeId)->value('wa_feedback_delay');
        return ($raw === null || $raw === '')
            ? self::DEFAULT_FEEDBACK_DELAY_HOURS
            : max(0, min(720, (int) $raw));
    }

    /** The vendor's chosen lead time before a follow-up visit, in days. 0 = send at booking. */
    public static function followUpLeadDays(int $storeId): int
    {
        $raw = DB::table('stores')->where('id', $storeId)->value('wa_followup_lead');
        return ($raw === null || $raw === '')
            ? self::DEFAULT_FOLLOWUP_LEAD_DAYS
            : max(0, min(30, (int) $raw));
    }

    /**
     * When a feedback request should go out for a visit completed now.
     *
     * A delay is the point: asked as the patient walks out you learn about the waiting room, asked
     * that evening you learn whether the treatment helped. Anything that lands outside waking
     * hours is pushed to SEND_HOUR the next morning.
     */
    public static function feedbackDueAt(int $storeId): ?Carbon
    {
        $hours = self::feedbackDelayHours($storeId);
        if ($hours <= 0) {
            return null;
        }
        return self::withinWakingHours(now()->addHours($hours));
    }

    /** When a follow-up reminder should go out for a visit booked on $date. Null = send now. */
    public static function followUpDueAt(int $storeId, $date): ?Carbon
    {
        $days = self::followUpLeadDays($storeId);
        if ($days <= 0 || !$date) {
            return null;
        }

        try {
            $due = Carbon::parse($date)->startOfDay()->subDays($days)->setTime(self::SEND_HOUR, 0);
        } catch (\Throwable $e) {
            return null;
        }

        // A visit booked for tomorrow with a three-day lead is already past its reminder — send
        // the confirmation now rather than silently never reminding them.
        return $due->isPast() ? null : $due;
    }

    /** Nudge anything landing at night to the next morning. */
    protected static function withinWakingHours(Carbon $when): Carbon
    {
        $hour = (int) $when->format('G');
        if ($hour >= 21) {
            return $when->copy()->addDay()->setTime(self::SEND_HOUR, 0);
        }
        if ($hour < 8) {
            return $when->copy()->setTime(self::SEND_HOUR, 0);
        }
        return $when;
    }

    public static function ensureAutoTable(): void
    {
        if (Schema::hasTable('wa_hmis_auto_sends')) {
            // due_at turns the lock row into a queue: a claimed row whose time has not come is a
            // scheduled message, which SendDueHmisMessagesJob picks up. source disambiguates a
            // kind that can come from two records — a follow-up hangs off either a prescription
            // or an appointment, and the sweeper has to know which table to read.
            foreach (['due_at' => 'TIMESTAMP NULL', 'source' => 'VARCHAR(20) NULL'] as $col => $def) {
                if (!Schema::hasColumn('wa_hmis_auto_sends', $col)) {
                    DB::statement("ALTER TABLE `wa_hmis_auto_sends` ADD COLUMN `{$col}` {$def}");
                }
            }
            return;
        }

        // The unique key IS the lock. Re-saving a finalized prescription, a second nurse marking
        // the same visit complete, a double-submitted form — all of them try to insert the same
        // row, and only the first one gets to message the patient.
        DB::statement("CREATE TABLE `wa_hmis_auto_sends` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `store_id` BIGINT NOT NULL,
            `kind` VARCHAR(20) NOT NULL,
            `record_id` BIGINT NOT NULL,
            `source` VARCHAR(20) NULL,
            `due_at` TIMESTAMP NULL,
            `sent` TINYINT(1) NOT NULL DEFAULT 0,
            `error` VARCHAR(255) NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `wahas_once` (`store_id`, `kind`, `record_id`),
            KEY `wahas_due` (`sent`, `due_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Send a record automatically, the moment it is generated — but only if the hospital asked
     * for it on Send Notifications, and only once per record.
     *
     * Silent by design: this runs inside saving a prescription or finalizing a report, and a
     * WhatsApp problem must never surface as a failure to save the clinical record. Anything that
     * goes wrong is recorded on the lock row and logged.
     *
     * $send is the closure that actually sends, so each caller keeps its own argument shape.
     *
     * $dueAt defers it: the claim row becomes a queue entry and SendDueHmisMessagesJob sends it
     * when the time comes, rebuilding the message from $source + $kind + $recordId rather than
     * from the closure. That is why a deferred kind must pass a $source the resolver understands.
     *
     * Returns whether this call was the one that took responsibility — false when the toggle is
     * off or another save already claimed the record, so a caller with its own fallback knows to
     * use it. True covers "queued for later" as well as "sent".
     */
    public static function auto(string $kind, int $storeId, ?int $recordId, callable $send, ?string $source = null, ?Carbon $dueAt = null): bool
    {
        try {
            $pref = self::AUTO_PREFS[$kind] ?? null;
            if (!$pref || !$recordId) {
                return false;
            }
            if (!NotificationPrefs::enabled($storeId, 'whatsapp_send', $pref)) {
                return false;
            }

            // Nothing can send on a template Meta has not approved. Attempting it anyway would
            // fail, be billed regardless (charges are taken at dispatch), and spend the claim row
            // below — so this record could never be sent again even after approval came through.
            // Skipping without claiming leaves the toggle safe to turn on while a template is
            // still in review; records registered during that window are skipped, not queued.
            $template = self::KINDS[$kind]['template'] ?? null;
            if (!$template || !WhatsAppService::templateApproved($storeId, $template)) {
                Log::info('HMIS auto-send skipped — template not approved', [
                    'kind' => $kind, 'store' => $storeId, 'template' => $template,
                ]);
                return false;
            }

            self::ensureAutoTable();

            // Claim it before sending. A duplicate key means somebody already has.
            try {
                DB::table('wa_hmis_auto_sends')->insert([
                    'store_id'   => $storeId,
                    'kind'       => $kind,
                    'record_id'  => $recordId,
                    'source'     => $source,
                    'due_at'     => $dueAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                return false;
            }

            // Scheduled for later: the claim row IS the queue entry, and
            // SendDueHmisMessagesJob sends it when its time comes.
            if ($dueAt && $dueAt->isFuture()) {
                return true;
            }

            $result = $send();

            DB::table('wa_hmis_auto_sends')
                ->where('store_id', $storeId)->where('kind', $kind)->where('record_id', $recordId)
                ->update([
                    'sent'       => !empty($result['success']) ? 1 : 0,
                    'error'      => empty($result['success']) ? mb_substr((string) ($result['message'] ?? ''), 0, 255) : null,
                    'updated_at' => now(),
                ]);

            if (empty($result['success'])) {
                Log::warning('HMIS auto-send failed', [
                    'kind' => $kind, 'store' => $storeId, 'record' => $recordId,
                    'reason' => $result['message'] ?? null,
                ]);
            }

            // True once the record is claimed, even if the send itself failed — a fallback
            // message would only be a second attempt at the same thing, and the lock row already
            // records why it didn't land.
            return true;
        } catch (\Throwable $e) {
            Log::error('HMIS auto-send error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a message that was queued earlier, rebuilding it from the row.
     *
     * Deliberately re-reads the record rather than storing a rendered message: a follow-up
     * reminder queued three days ago should carry the date the visit is on NOW, and a prescription
     * edited since should not send yesterday's medicines. If the record is gone, so is the reason
     * to message anyone about it.
     */
    public static function sendQueued(string $kind, string $source, int $storeId, int $recordId): array
    {
        switch ($source . ':' . $kind) {
            case 'prescription:followup':
                $rx = Prescription::with('patient', 'doctorProfile.employee')
                    ->where('store_id', $storeId)->find($recordId);
                if (!$rx || !$rx->patient) {
                    return self::fail('Prescription no longer available.');
                }
                return self::followUp(
                    $storeId,
                    $rx->patient,
                    $rx->follow_up_date,
                    self::doctorName($rx->doctorProfile),
                    null,
                    (int) $rx->id
                );

            case 'appointment:followup':
                $appointment = Appointment::with('patient', 'doctorProfile.employee')
                    ->where('store_id', $storeId)->find($recordId);
                if (!$appointment) {
                    return self::fail('Appointment no longer available.');
                }
                // Cancelled between booking and the reminder — reminding them now would be worse
                // than saying nothing.
                if (in_array($appointment->status, ['cancelled', 'no_show'], true)) {
                    return self::fail('Appointment was ' . $appointment->status . ' — reminder skipped.');
                }
                return self::followUpForAppointment($appointment);

            case 'appointment:feedback':
                $appointment = Appointment::with('patient')
                    ->where('store_id', $storeId)->find($recordId);
                if (!$appointment || !$appointment->patient) {
                    return self::fail('Appointment no longer available.');
                }
                return self::feedback(
                    $storeId,
                    $appointment->patient,
                    $appointment->appointment_date,
                    null,
                    (int) $appointment->id
                );

            case 'opd:feedback':
                $visit = OpdVisit::with('patient')->where('store_id', $storeId)->find($recordId);
                if (!$visit || !$visit->patient) {
                    return self::fail('Visit no longer available.');
                }
                return self::feedback($storeId, $visit->patient, $visit->visit_date, null, (int) $visit->id);
        }

        return self::fail('Nothing knows how to send a queued ' . $source . ' ' . $kind . '.');
    }

    /**
     * Everything queued whose time has come. Rows older than STALE_HOURS past due are dropped —
     * a feedback request that surfaces a week late reads as the hospital losing track of you.
     */
    public static function dueQueue(int $limit = 200)
    {
        self::ensureAutoTable();

        return DB::table('wa_hmis_auto_sends')
            ->where('sent', 0)
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now())
            ->where('due_at', '>=', now()->subHours(self::STALE_HOURS))
            ->orderBy('due_at')
            ->limit($limit)
            ->get();
    }

    /** Mark a queued row done (or failed) after the sweeper has tried it. */
    public static function settleQueued(int $rowId, array $result): void
    {
        DB::table('wa_hmis_auto_sends')->where('id', $rowId)->update([
            'sent'       => !empty($result['success']) ? 1 : 0,
            'error'      => empty($result['success']) ? mb_substr((string) ($result['message'] ?? ''), 0, 255) : null,
            'due_at'     => null,
            'updated_at' => now(),
        ]);
    }

    public static function ensureTable(): void
    {
        if (Schema::hasTable('wa_patient_shares')) {
            // sent_as separates a link send from a file send — the patient's profile lists both,
            // and only one of them has a link left to open. title names a manually sent document,
            // which is the only kind whose name isn't derivable from the record it points at.
            foreach ([
                'sent_as' => "VARCHAR(10) NOT NULL DEFAULT 'link'",
                'title'   => 'VARCHAR(150) NULL',
            ] as $col => $def) {
                if (!Schema::hasColumn('wa_patient_shares', $col)) {
                    DB::statement("ALTER TABLE `wa_patient_shares` ADD COLUMN `{$col}` {$def}");
                }
            }
            return;
        }

        // One row per send. The token is the whole credential, so it is long, random and scoped to
        // a single record — a leaked link exposes that one document and nothing else about the
        // patient, and it stops working after LINK_DAYS.
        DB::statement("CREATE TABLE `wa_patient_shares` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `store_id` BIGINT NOT NULL,
            `patient_id` BIGINT NOT NULL,
            `kind` VARCHAR(20) NOT NULL,
            `record_id` BIGINT NULL,
            `token` VARCHAR(64) NOT NULL,
            `sent_to` VARCHAR(32) NULL,
            `sent_by` BIGINT NULL,
            `sent_as` VARCHAR(10) NOT NULL DEFAULT 'link',
            `title` VARCHAR(150) NULL,
            `views` INT NOT NULL DEFAULT 0,
            `first_viewed_at` TIMESTAMP NULL,
            `last_viewed_at` TIMESTAMP NULL,
            `expires_at` TIMESTAMP NULL,
            `revoked_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `waps_token` (`token`),
            KEY `waps_record` (`store_id`, `kind`, `record_id`),
            KEY `waps_patient` (`patient_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /* ------------------------------------------------------------------ senders */

    /** Consultation summary — what was found and what was advised on an OPD visit. */
    /**
     * The visit slip: what the patient needs in order to turn up, sent as they are booked in.
     *
     * Deliberately not the vitals or the complaint — those are on the linked page. A registration
     * message that a patient reads standing at the counter is useful only if the token and the
     * doctor are in the message itself.
     */
    public static function visitRegistered(OpdVisit $visit, ?string $phone = null): array
    {
        $visit->loadMissing('patient', 'doctorProfile.employee');
        $patient = $visit->patient;
        if (!$patient) {
            return self::fail('This visit has no patient on it.');
        }

        return self::dispatch('visit_registered', (int) $visit->store_id, $patient, $phone, (int) $visit->id, [
            self::name($patient),
            self::storeName((int) $visit->store_id),
            self::date($visit->visit_date),
            (string) ($visit->token_number ?: '—'),
            self::doctorName($visit->doctorProfile ?? null) ?: 'our doctor',
        ], true);
    }

    public static function treatment(OpdVisit $visit, ?string $phone = null): array
    {
        $visit->loadMissing('patient', 'doctorProfile.employee');
        $patient = $visit->patient;
        if (!$patient) {
            return self::fail('This visit has no patient on it.');
        }

        $diagnosis = self::oneLine($visit->diagnosis) ?: 'as discussed with the doctor';

        return self::dispatch('treatment', (int) $visit->store_id, $patient, $phone, (int) $visit->id, [
            self::name($patient),
            self::storeName((int) $visit->store_id),
            self::date($visit->visit_date),
            $diagnosis,
        ], true);
    }

    /** The prescription itself — the medicine list lives behind the link. */
    public static function prescription(Prescription $rx, ?string $phone = null): array
    {
        $rx->loadMissing('patient', 'items');
        $patient = $rx->patient;
        if (!$patient) {
            return self::fail('This prescription has no patient on it.');
        }

        $count = $rx->items->count();

        return self::dispatch('prescription', (int) $rx->store_id, $patient, $phone, (int) $rx->id, [
            self::name($patient),
            self::storeName((int) $rx->store_id),
            self::date($rx->created_at),
            $count . ' ' . ($count === 1 ? 'medicine' : 'medicines'),
        ], true);
    }

    /**
     * The prescription as a PDF attached to the message.
     *
     * Needs a media template — a document cannot ride a normal template, and a plain document
     * message only delivers inside the 24h window, which a patient walking out of a consultation
     * is not in. Unlike the link version this file is permanent and forwardable once it lands, so
     * it is a deliberate second option rather than the default.
     */
    public static function prescriptionPdf(Prescription $rx, ?string $phone = null): array
    {
        $rx->loadMissing('patient', 'doctorProfile.employee', 'items');
        $patient = $rx->patient;
        if (!$patient) {
            return self::fail('This prescription has no patient on it.');
        }

        $pdf = self::buildPdf('prescription', $rx, 'Prescription-' . $rx->id);
        if (!$pdf) {
            return self::fail('Could not build the PDF. Please try again, or send it as a link instead.');
        }

        $count = $rx->items->count();

        return self::dispatch('prescription_pdf', (int) $rx->store_id, $patient, $phone, (int) $rx->id, [
            self::name($patient),
            self::storeName((int) $rx->store_id),
            self::date($rx->created_at),
            $count . ' ' . ($count === 1 ? 'medicine' : 'medicines'),
        ], false, $pdf);
    }

    /**
     * How to take the medicines. The same prescription, aimed at the patient rather than the
     * pharmacist: the message carries a readable digest of the schedule and the link has the rest.
     */
    public static function medicines(Prescription $rx, ?string $phone = null): array
    {
        $rx->loadMissing('patient', 'items');
        $patient = $rx->patient;
        if (!$patient) {
            return self::fail('This prescription has no patient on it.');
        }
        if ($rx->items->isEmpty()) {
            return self::fail('This prescription has no medicines to explain.');
        }

        // A template parameter is one line, so the schedule is joined with semicolons and cut to
        // the first few medicines — the link carries the complete list either way.
        $parts = $rx->items->take(3)->map(function ($item) {
            $bits = array_filter([
                trim((string) $item->medicine_name),
                trim((string) $item->dosage),
                trim((string) $item->frequency),
                trim((string) $item->duration),
            ], fn($v) => $v !== '');
            return implode(' ', $bits);
        })->all();

        if ($rx->items->count() > 3) {
            $parts[] = 'and ' . ($rx->items->count() - 3) . ' more';
        }

        return self::dispatch('medicines', (int) $rx->store_id, $patient, $phone, (int) $rx->id, [
            self::name($patient),
            self::storeName((int) $rx->store_id),
            self::oneLine(implode('; ', $parts)),
        ], true);
    }

    /**
     * Follow-up reminder. Short and self-contained — there is nothing to open, so this one carries
     * no link. Works off a booked appointment, or off a prescription's follow-up date.
     */
    public static function followUp(int $storeId, Patient $patient, $date, ?string $doctor = null, ?string $phone = null, ?int $recordId = null): array
    {
        if (!$date) {
            return self::fail('No follow-up date has been set yet.');
        }

        // $doctor arrives already titled by doctorName() — prefixing "Dr." here as well is how you
        // get "Dr. Dr. Anita Rao" on a patient's phone.
        return self::dispatch('followup', $storeId, $patient, $phone, $recordId, [
            self::name($patient),
            self::storeName($storeId),
            self::date($date),
            $doctor ? self::oneLine($doctor) : 'your doctor',
        ], false);
    }

    /** Follow-up straight off an appointment. */
    public static function followUpForAppointment(Appointment $appointment, ?string $phone = null): array
    {
        $appointment->loadMissing('patient', 'doctorProfile.employee');
        if (!$appointment->patient) {
            return self::fail('This appointment has no patient on it.');
        }

        $when = self::date($appointment->appointment_date);
        if ($appointment->appointment_time) {
            $when .= ' at ' . Carbon::parse($appointment->appointment_time)->format('h:i A');
        }

        return self::dispatch('followup', (int) $appointment->store_id, $appointment->patient, $phone, (int) $appointment->id, [
            self::name($appointment->patient),
            self::storeName((int) $appointment->store_id),
            $when,
            self::doctorName($appointment->doctorProfile) ?: 'your doctor',
        ], false);
    }

    /**
     * Feedback request. The template carries quick-reply buttons, so the patient answers with one
     * tap and the reply lands in the vendor's WhatsApp inbox tagged as a button tap.
     */
    public static function feedback(int $storeId, Patient $patient, $visitDate = null, ?string $phone = null, ?int $recordId = null): array
    {
        return self::dispatch('feedback', $storeId, $patient, $phone, $recordId, [
            self::name($patient),
            self::storeName($storeId),
            self::date($visitDate ?: now()),
        ], false);
    }

    /** Lab report — results behind the link, never in the message body. */
    public static function labReport(LabOrder $order, ?string $phone = null): array
    {
        $order->loadMissing('patient', 'items');
        $patient = $order->patient;
        if (!$patient) {
            return self::fail('This lab order has no patient on it.');
        }

        $tests = $order->items->pluck('test_name')->filter()->take(3)->implode(', ');
        if ($order->items->count() > 3) {
            $tests .= ' and ' . ($order->items->count() - 3) . ' more';
        }

        return self::dispatch('lab', (int) $order->store_id, $patient, $phone, (int) $order->id, [
            self::name($patient),
            self::storeName((int) $order->store_id),
            self::oneLine($tests) ?: 'your tests',
        ], true);
    }

    /**
     * Any file the hospital chooses to send this patient — a discharge summary, an insurance
     * form, a scan they brought in from another clinic. Not every document a patient needs is one
     * this system generated, and the ones it didn't generate used to have no way out of the panel.
     *
     * $storedPath is a path on the public disk (a patient document). It is copied to PDF_DIR under
     * a random name rather than linked where it lies: /storage/patient/... is auth-gated by design,
     * and Meta fetches the file itself with no session. The copy is what PruneSharedPdfs deletes
     * once Meta has had time to collect it, so the exposed name is short-lived.
     */
    public static function document(int $storeId, Patient $patient, string $storedPath, ?string $title = null, ?string $phone = null, ?int $documentId = null): array
    {
        $label = trim((string) $title) ?: 'Document';

        $file = self::stageFile($storedPath, $label);
        if (!$file) {
            return self::fail('Could not prepare that file for sending. Please try another file.');
        }

        return self::dispatch('document', $storeId, $patient, $phone, $documentId, [
            self::name($patient),
            self::storeName($storeId),
            self::oneLine($label),
            self::date(now()),
        ], false, $file, $label);
    }

    /**
     * Copy a stored document somewhere Meta can fetch it, and hand back the link and filename.
     *
     * An image is wrapped in a PDF first: the template's header is a DOCUMENT, and Meta rejects a
     * JPEG offered as one. Patients are handed photographed reports constantly, so refusing them
     * would gut the feature.
     */
    protected static function stageFile(string $storedPath, string $label): ?array
    {
        try {
            $disk = Storage::disk('public');
            if (!$disk->exists($storedPath)) {
                return null;
            }

            $ext  = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));
            $safe = preg_replace('/[^A-Za-z0-9\-_ ]/', '', $label) ?: 'Document';

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                return self::imageAsPdf($disk->path($storedPath), $safe);
            }

            $name = bin2hex(random_bytes(24)) . ($ext ? '.' . $ext : '');
            $disk->copy($storedPath, self::PDF_DIR . '/' . $name);

            return [
                'url'      => asset('storage/app/public/' . self::PDF_DIR . '/' . $name),
                'filename' => $safe . ($ext ? '.' . $ext : ''),
                'stored'   => self::PDF_DIR . '/' . $name,
            ];
        } catch (\Throwable $e) {
            Log::error('HMIS document staging failed: ' . $e->getMessage());
            return null;
        }
    }

    /** One image, one A4 page — enough to make a photographed report sendable as a document. */
    protected static function imageAsPdf(string $absolutePath, string $safeLabel): ?array
    {
        try {
            $temp = storage_path('app/tmp');
            if (!is_dir($temp)) {
                mkdir($temp, 0775, true);
            }

            $mpdf = new \Mpdf\Mpdf([
                'mode'          => 'utf-8',
                'format'        => 'A4',
                'margin_left'   => 8,
                'margin_right'  => 8,
                'margin_top'    => 8,
                'margin_bottom' => 8,
                'tempDir'       => $temp,
            ]);
            $mpdf->WriteHTML('<div style="text-align:center;"><img src="' . $absolutePath . '" style="max-width:100%;"></div>');

            $name = bin2hex(random_bytes(24)) . '.pdf';
            $path = $temp . '/' . $name;
            $mpdf->Output($path, 'F');

            Storage::disk('public')->putFileAs(self::PDF_DIR, new File($path), $name);
            @unlink($path);

            return [
                'url'      => asset('storage/app/public/' . self::PDF_DIR . '/' . $name),
                'filename' => $safeLabel . '.pdf',
                'stored'   => self::PDF_DIR . '/' . $name,
            ];
        } catch (\Throwable $e) {
            Log::error('HMIS image-to-PDF failed: ' . $e->getMessage());
            return null;
        }
    }

    /* ------------------------------------------------------------------ plumbing */

    /**
     * The one path every send goes through: check the number can send, mint a link when the record
     * has detail worth opening, fill the template, log it.
     *
     * $withLink appends the record URL as the last body parameter, so a template that ends in a
     * link has exactly one more parameter than the summary needs.
     */
    protected static function dispatch(string $kind, int $storeId, Patient $patient, ?string $phone, ?int $recordId, array $params, bool $withLink, ?array $document = null, ?string $title = null): array
    {
        try {
            $meta = self::KINDS[$kind] ?? null;
            if (!$meta) {
                return self::fail('Unknown record type.');
            }

            $to = trim((string) ($phone ?: $patient->phone));
            if ($to === '') {
                return self::fail('This patient has no phone number on file.');
            }

            $wa = WhatsAppService::make($storeId);
            if ($wa->source() !== 'vendor') {
                return self::fail('Connect your own WhatsApp number under WhatsApp → Connection before sending records.');
            }
            if (!WhatsAppBilling::isActive($storeId)) {
                return self::fail('Your WhatsApp subscription isn’t active. Activate it under WhatsApp → Plan & Billing.');
            }

            $url = null;
            if ($withLink) {
                $url = self::shareUrl($kind, $storeId, (int) $patient->id, $recordId, $to);
                $params[] = $url;
            }

            $components = [];

            // A media template's header carries the file. Meta fetches the link from its own
            // servers when the message is sent, which is why the PDF has to be reachable without
            // a session — and why its filename is a random token rather than the patient's name.
            if ($document) {
                $components[] = [
                    'type'       => 'header',
                    'parameters' => [[
                        'type'     => 'document',
                        'document' => [
                            'link'     => $document['url'],
                            'filename' => $document['filename'],
                        ],
                    ]],
                ];
            }

            $components[] = [
                'type'       => 'body',
                'parameters' => array_map(
                    fn($value) => ['type' => 'text', 'text' => self::sanitize((string) $value)],
                    array_values($params)
                ),
            ];

            $res = $wa->sendTemplate($to, $meta['template'], 'en_US', $components, $meta['context']);

            if (!$res['success']) {
                // The commonest failure by far is the template not existing on this vendor's WABA
                // yet, and Meta's own wording for that helps nobody — say what to do about it.
                $error = (string) ($res['error'] ?? 'WhatsApp refused the message.');
                if (stripos($error, 'template') !== false) {
                    $error .= ' Create the "' . $meta['template'] . '" template under WhatsApp → Message Templates '
                        . '(it is in the suggested list) and wait for Meta to approve it.';
                }
                return self::fail($error);
            }

            // A file send leaves no link to log, but the patient is holding the document all the
            // same — record it so their profile shows what they were given, not only what they
            // could open.
            if (!$withLink && $document) {
                self::recordFileSend($kind, $storeId, (int) $patient->id, $recordId, $to, $title);
            }

            self::log($storeId, $kind, $recordId, $patient, $to);

            return [
                'success' => true,
                'message' => $meta['label'] . ' sent on WhatsApp to ' . self::maskPhone($to) . '.',
                'url'     => $url,
            ];
        } catch (\Throwable $e) {
            Log::error('HMIS WhatsApp share failed: ' . $e->getMessage());
            return self::fail('Could not send that right now. Please try again.');
        }
    }

    /**
     * Render a record to a PDF on the public disk and return its link and filename.
     *
     * The file has to be fetchable by Meta with no session — that is how a media template works —
     * so the whole of its privacy is in the name: a 48-character random token, unlisted, never
     * guessable. Same view the patient's link page uses, so the attachment and the web page can't
     * drift apart.
     *
     * Returns null on any failure; the caller reports it rather than sending a message with a
     * broken attachment.
     */
    protected static function buildPdf(string $kind, $record, string $label): ?array
    {
        try {
            $store = DB::table('stores')->where('id', $record->store_id)
                ->first(['name', 'address', 'phone', 'logo']);

            $html = view('patient-record.show', [
                'kind'    => $kind,
                'record'  => $record,
                'store'   => $store,
                'expires' => null,
                'pdf'     => true,
            ])->render();

            $temp = storage_path('app/tmp');
            if (!is_dir($temp)) {
                mkdir($temp, 0775, true);
            }

            $mpdf = new \Mpdf\Mpdf([
                'mode'          => 'utf-8',
                'format'        => 'A4',
                'margin_left'   => 10,
                'margin_right'  => 10,
                'margin_top'    => 10,
                'margin_bottom' => 10,
                'tempDir'       => $temp,
            ]);
            $mpdf->WriteHTML($html);

            $name = bin2hex(random_bytes(24)) . '.pdf';
            $path = $temp . '/' . $name;
            $mpdf->Output($path, 'F');

            Storage::disk('public')->putFileAs(self::PDF_DIR, new File($path), $name);
            @unlink($path);

            return [
                'url'      => asset('storage/app/public/' . self::PDF_DIR . '/' . $name),
                'filename' => preg_replace('/[^A-Za-z0-9\-_]/', '', $label) . '.pdf',
                'stored'   => self::PDF_DIR . '/' . $name,
            ];
        } catch (\Throwable $e) {
            Log::error('HMIS share PDF build failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Mint a fresh link for this send. Deliberately not reused across sends: re-sending a report
     * issues a new token, so a link the patient forwarded to someone can be revoked on its own by
     * clearing that row, and the view counts stay attributable to one message.
     */
    public static function shareUrl(string $kind, int $storeId, int $patientId, ?int $recordId, ?string $sentTo = null): string
    {
        self::ensureTable();

        $token = bin2hex(random_bytes(24));

        DB::table('wa_patient_shares')->insert([
            'store_id'   => $storeId,
            'patient_id' => $patientId,
            'kind'       => $kind,
            'record_id'  => $recordId,
            'token'      => $token,
            'sent_to'    => $sentTo ? mb_substr($sentTo, 0, 32) : null,
            'sent_by'    => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            'sent_as'    => 'link',
            'expires_at' => now()->addDays(self::LINK_DAYS),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return route('patient-record', ['token' => $token]);
    }

    /**
     * Log a document that went out as a file rather than a link, so it appears on the patient's
     * profile alongside the link sends.
     *
     * The token is minted and revoked in the same breath. The column is unique and not null, and
     * this row exists to be listed, never opened — the delivery was the attachment itself, and a
     * live link nobody was ever given is a credential with no reason to exist.
     */
    protected static function recordFileSend(string $kind, int $storeId, int $patientId, ?int $recordId, ?string $sentTo, ?string $title = null): void
    {
        try {
            self::ensureTable();

            DB::table('wa_patient_shares')->insert([
                'store_id'   => $storeId,
                'patient_id' => $patientId,
                'kind'       => $kind,
                'record_id'  => $recordId,
                'token'      => bin2hex(random_bytes(24)),
                'sent_to'    => $sentTo ? mb_substr($sentTo, 0, 32) : null,
                'sent_by'    => auth('vendor_employee')->id() ?? auth('vendor')->id(),
                'sent_as'    => 'pdf',
                'title'      => $title ? mb_substr($title, 0, 150) : null,
                'revoked_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('HMIS share file log skipped: ' . $e->getMessage());
        }
    }

    /**
     * Where the record behind a share lives inside the vendor panel, so staff reading the sent log
     * can open the thing that was sent. One mapping, used by the patient profile and the sent-
     * documents log alike — kind => record has to mean the same thing on every screen.
     */
    public static function panelUrl(string $kind, ?int $recordId): ?string
    {
        if (!$recordId) {
            return null;
        }

        try {
            switch ($kind) {
                case 'treatment':
                    return route('vendor.opd.show', $recordId);
                case 'prescription':
                case 'prescription_pdf':
                case 'medicines':
                    return route('vendor.prescription.show', $recordId);
                case 'lab':
                    return route('vendor.lab.orders.report', $recordId);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /** Masked for display — a sent log is read across a busy desk. */
    public static function maskedPhone(?string $phone): string
    {
        return self::maskPhone($phone);
    }

    protected static function log(int $storeId, string $kind, ?int $recordId, Patient $patient, string $phone): void
    {
        try {
            HospitalActivityLog::record(
                $storeId,
                $kind === 'lab' ? 'lab_order' : $kind,
                $recordId,
                'whatsapp_sent',
                (self::KINDS[$kind]['label'] ?? 'Record') . ' sent on WhatsApp to ' . $patient->name . ' (' . self::maskPhone($phone) . ')',
                ['kind' => $kind, 'patient_id' => $patient->id]
            );
        } catch (\Throwable $e) {
            Log::warning('HMIS share activity log skipped: ' . $e->getMessage());
        }
    }

    protected static function fail(string $message): array
    {
        return ['success' => false, 'message' => $message, 'url' => null];
    }

    /* ------------------------------------------------------------------ formatting */

    /** Meta rejects newlines and runs of 4+ spaces inside a parameter. */
    protected static function sanitize(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);
        return $value === '' ? '-' : mb_substr($value, 0, 500);
    }

    protected static function oneLine(?string $value): string
    {
        return trim(preg_replace('/\s*[,;\n\r]+\s*/u', ', ', (string) $value) ?? '');
    }

    protected static function name(Patient $patient): string
    {
        return trim((string) $patient->name) ?: 'there';
    }

    protected static function storeName(int $storeId): string
    {
        return DB::table('stores')->where('id', $storeId)->value('name') ?: 'our clinic';
    }

    protected static function date($date): string
    {
        try {
            return Carbon::parse($date)->format('d M Y');
        } catch (\Throwable $e) {
            return (string) $date;
        }
    }

    public static function doctorName($doctorProfile): ?string
    {
        if (!$doctorProfile) {
            return null;
        }
        $employee = $doctorProfile->employee ?? null;
        if (!$employee) {
            return null;
        }
        $name = trim(($employee->f_name ?? '') . ' ' . ($employee->l_name ?? ''));
        return $name !== '' ? 'Dr. ' . $name : null;
    }

    protected static function maskPhone(?string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone) ?? '';
        return strlen($digits) < 4 ? '••••' : '••••••' . substr($digits, -4);
    }
}
