<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentToken;
use App\Models\DoctorProfile;
use App\Models\Patient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * WhatsApp appointment actions for the auto-reply bot (HMIS stores).
 *
 * The AI collects details in conversation; when the customer confirms, it emits an action
 * marker instead of prose. This service supplies the prompt section that teaches the
 * protocol (doctors, the customer's upcoming appointments, marker formats) and executes
 * the parsed marker against the real appointment tables — booking and rescheduling exactly
 * like the vendor panel does (status flow, rescheduled_from link, token generation).
 */
class WhatsAppAppointmentBot
{
    const BOOK_MARKER       = 'BOOK_APPOINTMENT';
    const RESCHEDULE_MARKER = 'RESCHEDULE_APPOINTMENT';

    /** Appointment tooling applies only to stores that actually run appointments. */
    protected static function applicable(int $storeId): bool
    {
        try {
            return Schema::hasTable('appointments')
                && Schema::hasTable('doctor_profiles')
                && DoctorProfile::where('store_id', $storeId)->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Prompt section appended to the auto-reply system prompt ('' when not applicable). */
    public static function promptSection(int $storeId, string $phoneKey): string
    {
        if (!static::applicable($storeId)) {
            return '';
        }

        $doctors = DoctorProfile::where('store_id', $storeId)->with('employee')->get()
            ->map(function ($d) {
                $name = trim(($d->employee->f_name ?? '') . ' ' . ($d->employee->l_name ?? ''));
                return '- Dr. ' . ($name ?: ('#' . $d->id)) . ($d->specialization ? " ({$d->specialization})" : '');
            })->implode("\n");

        $upcoming = static::upcomingFor($storeId, $phoneKey)
            ->map(function ($a) {
                $dr = trim(($a->doctorProfile->employee->f_name ?? '') . ' ' . ($a->doctorProfile->employee->l_name ?? ''));
                return "- ID {$a->id}: " . Carbon::parse($a->appointment_date)->format('d M Y')
                    . ' at ' . Carbon::parse($a->appointment_time ?: '00:00')->format('h:i A')
                    . ($dr ? " with Dr. {$dr}" : '');
            })->implode("\n");

        return "\n\nAPPOINTMENT ACTIONS — you can book and reschedule appointments for this customer.\n"
            . 'Today is ' . now()->format('l, d M Y') . ".\n"
            . "Doctors:\n{$doctors}\n"
            . "Customer's upcoming appointments:\n" . ($upcoming ?: '- none') . "\n\n"
            . "ACTION RULES:\n"
            . "- To BOOK: collect (1) the patient's full name if they are new, (2) which doctor if there is more than one, (3) date, (4) time. "
            . "Confirm all details with the customer first. After they confirm, reply with ONLY this marker and no other text:\n"
            . '[[' . self::BOOK_MARKER . ': {"name":"<patient full name>","doctor":"<doctor name>","date":"YYYY-MM-DD","time":"HH:MM","reason":"<short reason>"}]]' . "\n"
            . "- To RESCHEDULE an upcoming appointment from the list above: confirm the new date and time, then reply with ONLY:\n"
            . '[[' . self::RESCHEDULE_MARKER . ': {"appointment_id": <ID from the list>, "date":"YYYY-MM-DD","time":"HH:MM"}]]' . "\n"
            . "- Time is 24-hour format. Dates must be today or later — interpret \"tomorrow\" etc. from today's date above.\n"
            . "- Only offer doctors from the list. Never emit a marker before the customer has confirmed the details.";
    }

    /**
     * Detect and execute an action marker in the model's reply.
     * Returns null when the reply contains no marker (normal Q&A continues), otherwise
     * ['message' => customer-facing text, 'escalate' => bool, 'reason' => string].
     */
    public static function tryHandle(string $reply, int $storeId, string $phoneKey, string $fromPhone): ?array
    {
        if (!preg_match('/\[\[(' . self::BOOK_MARKER . '|' . self::RESCHEDULE_MARKER . '):\s*(\{.*?\})\s*\]\]/s', $reply, $m)) {
            return null;
        }
        if (!static::applicable($storeId)) {
            return null;
        }

        $data = json_decode($m[2], true) ?: [];

        try {
            return $m[1] === self::BOOK_MARKER
                ? static::book($storeId, $phoneKey, $fromPhone, $data)
                : static::reschedule($storeId, $phoneKey, $data);
        } catch (\Throwable $e) {
            Log::warning("WA appointment action failed (store {$storeId}): " . $e->getMessage());
            return [
                'message'  => 'Sorry, something went wrong while processing your appointment. Our team will contact you shortly to sort it out.',
                'escalate' => true,
                'reason'   => 'Appointment action failed: ' . $e->getMessage(),
            ];
        }
    }

    protected static function book(int $storeId, string $phoneKey, string $fromPhone, array $data): array
    {
        $when = static::parseWhen($data['date'] ?? '', $data['time'] ?? '');
        if (!$when) {
            return static::failure('The requested date/time was unclear.');
        }

        $doctor = static::resolveDoctor($storeId, (string) ($data['doctor'] ?? ''));
        if (!$doctor) {
            return static::failure('No matching doctor found.');
        }

        $patient = static::resolvePatient($storeId, $phoneKey, $fromPhone, (string) ($data['name'] ?? ''));

        $appt = null;
        DB::transaction(function () use (&$appt, $storeId, $patient, $doctor, $when, $data) {
            $appt = Appointment::create([
                'store_id'          => $storeId,
                'patient_id'        => $patient->id,
                'doctor_profile_id' => $doctor->id,
                'slot_id'           => null,
                'appointment_date'  => $when->toDateString(),
                'appointment_time'  => $when->format('H:i'),
                'booking_type'      => 'online',
                'status'            => 'scheduled',
                'reason'            => trim((string) ($data['reason'] ?? '')) ?: 'Booked via WhatsApp',
                'booked_by'         => null,
            ]);
            static::generateToken($doctor->id, $when->toDateString(), $appt->id);
        });

        static::activityLog($storeId, $appt->id, 'booked_via_whatsapp',
            "Appointment #{$appt->id} booked via WhatsApp for patient #{$patient->id}");

        $drName = trim(($doctor->employee->f_name ?? '') . ' ' . ($doctor->employee->l_name ?? ''));
        $token  = $appt->token->token_number ?? null;
        $storeName = DB::table('stores')->where('id', $storeId)->value('name') ?: 'our clinic';

        return [
            'message' => "✅ Your appointment at {$storeName} is booked for " . $when->format('d M Y') . ' at ' . $when->format('h:i A')
                . ($drName ? " with Dr. {$drName}" : '')
                . ($token ? ". Your token number is {$token}" : '')
                . ". We'll remind you before the visit. Reply here anytime to reschedule.",
            'escalate' => false,
            'reason'   => '',
        ];
    }

    protected static function reschedule(int $storeId, string $phoneKey, array $data): array
    {
        $when = static::parseWhen($data['date'] ?? '', $data['time'] ?? '');
        if (!$when) {
            return static::failure('The requested date/time was unclear.');
        }

        $upcoming = static::upcomingFor($storeId, $phoneKey);
        if ($upcoming->isEmpty()) {
            return [
                'message'  => "I couldn't find an upcoming appointment for your number. Our team will check and get back to you shortly.",
                'escalate' => true,
                'reason'   => 'Reschedule requested but no upcoming appointment found',
            ];
        }

        $old = null;
        if (!empty($data['appointment_id'])) {
            $old = $upcoming->firstWhere('id', (int) $data['appointment_id']);
        }
        $old = $old ?: $upcoming->first();

        $new = null;
        DB::transaction(function () use (&$new, $old, $when) {
            $old->status        = 'cancelled';
            $old->cancel_reason = 'Rescheduled via WhatsApp';
            $old->save();

            $new = Appointment::create([
                'store_id'          => $old->store_id,
                'patient_id'        => $old->patient_id,
                'doctor_profile_id' => $old->doctor_profile_id,
                'slot_id'           => null,
                'appointment_date'  => $when->toDateString(),
                'appointment_time'  => $when->format('H:i'),
                'booking_type'      => $old->booking_type,
                'status'            => 'scheduled',
                'reason'            => $old->reason,
                'rescheduled_from'  => $old->id,
                'booked_by'         => null,
            ]);
            static::generateToken($old->doctor_profile_id, $when->toDateString(), $new->id);
        });

        static::activityLog($storeId, $new->id, 'rescheduled_via_whatsapp',
            "Appointment #{$old->id} rescheduled to {$when->toDateString()} via WhatsApp. New appointment #{$new->id}");

        $dr = trim(($old->doctorProfile->employee->f_name ?? '') . ' ' . ($old->doctorProfile->employee->l_name ?? ''));
        $token = $new->token->token_number ?? null;

        return [
            'message' => '✅ Done! Your appointment has been moved to ' . $when->format('d M Y') . ' at ' . $when->format('h:i A')
                . ($dr ? " with Dr. {$dr}" : '')
                . ($token ? ". Your new token number is {$token}" : '')
                . ". We'll remind you before the visit.",
            'escalate' => false,
            'reason'   => '',
        ];
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    protected static function upcomingFor(int $storeId, string $phoneKey)
    {
        $patientIds = Patient::where('store_id', $storeId)
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$phoneKey])
            ->pluck('id');

        if ($patientIds->isEmpty()) {
            return collect();
        }

        return Appointment::where('store_id', $storeId)
            ->whereIn('patient_id', $patientIds)
            ->where('status', 'scheduled')
            ->whereDate('appointment_date', '>=', now()->toDateString())
            ->with(['doctorProfile.employee', 'token'])
            ->orderBy('appointment_date')->orderBy('appointment_time')
            ->limit(5)
            ->get();
    }

    protected static function resolveDoctor(int $storeId, string $name): ?DoctorProfile
    {
        $doctors = DoctorProfile::where('store_id', $storeId)->with('employee')->get();
        if ($doctors->isEmpty()) {
            return null;
        }
        $name = mb_strtolower(trim(preg_replace('/^dr\.?\s*/i', '', $name) ?? ''));
        if ($name !== '') {
            foreach ($doctors as $d) {
                $full = mb_strtolower(trim(($d->employee->f_name ?? '') . ' ' . ($d->employee->l_name ?? '')));
                if ($full !== '' && (str_contains($full, $name) || str_contains($name, $full))) {
                    return $d;
                }
            }
        }
        return $doctors->first();
    }

    protected static function resolvePatient(int $storeId, string $phoneKey, string $fromPhone, string $name): Patient
    {
        $patient = Patient::where('store_id', $storeId)
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$phoneKey])
            ->first();
        if ($patient) {
            return $patient;
        }

        // New patient — registered from the WhatsApp conversation.
        $name = trim($name) ?: (DB::table('store_customers')->where('store_id', $storeId)
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$phoneKey])
            ->value('f_name') ?: 'WhatsApp Patient');

        return Patient::create([
            'store_id'    => $storeId,
            'patient_uid' => Patient::generateUid($storeId),
            'name'        => $name,
            'phone'       => preg_replace('/[^0-9]/', '', $fromPhone),
            'status'      => 'active',
            'created_by'  => null,
        ]);
    }

    protected static function parseWhen(string $date, string $time): ?Carbon
    {
        try {
            $when = Carbon::parse(trim($date) . ' ' . trim($time ?: '09:00'));
        } catch (\Throwable $e) {
            return null;
        }
        return $when->isPast() ? null : $when;
    }

    protected static function generateToken(int $doctorProfileId, string $date, int $appointmentId): void
    {
        $last = AppointmentToken::where('doctor_profile_id', $doctorProfileId)
            ->where('token_date', $date)
            ->max('token_number');

        AppointmentToken::create([
            'appointment_id'    => $appointmentId,
            'token_number'      => ($last ?? 0) + 1,
            'token_date'        => $date,
            'doctor_profile_id' => $doctorProfileId,
        ]);
    }

    protected static function failure(string $why): array
    {
        return [
            'message'  => 'Sorry, I couldn\'t complete that — ' . lcfirst($why) . ' Our team will contact you shortly to help.',
            'escalate' => true,
            'reason'   => 'Appointment action needs attention: ' . $why,
        ];
    }

    protected static function activityLog(int $storeId, int $appointmentId, string $action, string $message): void
    {
        try {
            \App\Models\HospitalActivityLog::record($storeId, 'appointment', $appointmentId, $action, $message, []);
        } catch (\Throwable $e) {
            // activity log must never break a booking
        }
    }
}
