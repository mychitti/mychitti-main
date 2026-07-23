<?php

namespace App\Services;

use App\Jobs\ProcessNewLeadNotifications;
use App\Models\Appointment;
use App\Models\AppointmentToken;
use App\Models\DoctorProfile;
use App\Models\DoctorService;
use App\Models\Patient;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
            . "- Booking submits an appointment REQUEST that the clinic confirms — never promise a confirmed slot, say the clinic will confirm shortly.\n"
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

    /**
     * Booking follows the SAME flow as a website appointment booking: it creates a
     * dedicated appointment-type ServiceRequest lead (service_type doctor_appointment),
     * records the lead status, and fans out the standard vendor notifications
     * (ProcessNewLeadNotifications, isAppointment) — so vendor acceptance, lead billing
     * and the HMIS lead→appointment conversion all work exactly as for web bookings.
     */
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

        // The service item this doctor offers — appointment leads are item-based like the
        // website's flow, where the doctor is picked FROM a service.
        $itemId = DoctorService::where('doctor_profile_id', $doctor->id)->value('item_id');
        if (!$itemId) {
            return static::failure('This doctor has no bookable service configured.');
        }

        $store = DB::table('stores')->where('id', $storeId)
            ->first(['id', 'name', 'zone_id', 'latitude', 'longitude', 'module_id', 'address']);

        $user = static::resolveUser($storeId, $phoneKey, $fromPhone, (string) ($data['name'] ?? ''), $store->zone_id ?? null);

        $sr = new ServiceRequest();
        $sr->user_id             = $user->id;
        $sr->item_id             = $itemId;
        $sr->sent_to             = (string) $storeId;
        $sr->is_dedicated        = 1;
        $sr->module_id           = $store->module_id ?? null;
        $sr->zone_id             = $store->zone_id ?? null;
        $sr->latitude            = (float) ($store->latitude ?? 0);
        $sr->longitude           = (float) ($store->longitude ?? 0);
        $sr->status              = 'new';
        $sr->address             = $store->address ?? null;
        $sr->requirements        = trim((string) ($data['reason'] ?? '')) ?: 'Booked via WhatsApp';
        $sr->patient_for         = 'myself';
        $sr->preferred_doctor_id = $doctor->id;
        $sr->preferred_date      = $when->toDateString();
        $sr->preferred_slot_id   = null;
        $sr->preferred_time      = $when->format('H:i');
        $sr->reason              = trim((string) ($data['reason'] ?? '')) ?: null;
        $sr->service_type        = 'doctor_appointment';
        $sr->created_at          = now();
        $sr->save();

        DB::table('lead_statuses')->insert([
            'service_request_id' => $sr->id,
            'status'             => 'User Requested Appointment',
            'created_at'         => now(),
        ]);

        // Standard vendor fan-out (SMS / panel / WhatsApp alert, auto-accept, wallet nudge)
        // — identical to a website appointment request.
        ProcessNewLeadNotifications::dispatch($sr->id, [$storeId], true);

        $drName = trim(($doctor->employee->f_name ?? '') . ' ' . ($doctor->employee->l_name ?? ''));

        return [
            'message' => '✅ Your appointment request at ' . ($store->name ?? 'the clinic') . ' has been sent for '
                . $when->format('d M Y') . ' at ' . $when->format('h:i A')
                . ($drName ? " with Dr. {$drName}" : '')
                . ". The clinic will confirm it shortly — you'll get a message here once it's confirmed.",
            'escalate' => false,
            'reason'   => '',
        ];
    }

    /** Find (or minimally register) the platform user this WhatsApp number belongs to. */
    protected static function resolveUser(int $storeId, string $phoneKey, string $fromPhone, string $name, $zoneId): User
    {
        $user = User::whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$phoneKey])
            ->orderBy('id')
            ->first();
        if ($user) {
            return $user;
        }

        $name = trim($name) ?: (DB::table('store_customers')->where('store_id', $storeId)
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$phoneKey])
            ->value('f_name') ?: 'WhatsApp Customer');

        $user = User::create([
            'f_name'   => $name,
            'phone'    => preg_replace('/[^0-9]/', '', $fromPhone),
            'password' => bcrypt(Str::random(16)),
        ]);
        if ($zoneId) {
            $user->zone_id = $zoneId;
            $user->save();
        }
        return $user;
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
