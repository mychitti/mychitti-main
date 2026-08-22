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
use App\Services\LeadAppointmentService;
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
 * the parsed marker against the real appointment tables — booking, rescheduling and
 * cancelling exactly like the vendor panel does (status flow, rescheduled_from link, tokens).
 *
 * Every action a customer takes here alerts the clinic. A slot that empties itself at 1am is
 * only useful to a store that finds out about it, and the panel has no other way to learn.
 */
class WhatsAppAppointmentBot
{
    const BOOK_MARKER       = 'BOOK_APPOINTMENT';
    const RESCHEDULE_MARKER = 'RESCHEDULE_APPOINTMENT';
    const CANCEL_MARKER     = 'CANCEL_APPOINTMENT';

    /**
     * How far into the past a requested time may sit and still be read as "now".
     *
     * Covers the gap between the model choosing a clock time and the queued job acting on it —
     * the customer confirming, the webhook, the job. Wide enough to absorb that, far too narrow
     * to swallow a genuinely stale request for a slot earlier in the day.
     */
    const PAST_GRACE_MINUTES = 10;

    /**
     * What a caller is filed as before they tell us their name.
     *
     * Only ever a stand-in. Anything still wearing it is treated as nameless, so the real name
     * replaces it the moment one is given rather than sitting behind it forever.
     */
    const PLACEHOLDER_NAME = 'WhatsApp Customer';

    /** Is this a stand-in rather than something the patient actually told us? */
    protected static function isPlaceholderName(?string $name): bool
    {
        $name = mb_strtolower(trim((string) $name));

        return $name === '' || $name === mb_strtolower(self::PLACEHOLDER_NAME) || $name === 'patient';
    }

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
                // The patient's name matters here: this list can hold appointments booked for
                // other people, and "cancel my daughter's" is unanswerable without it.
                $for = trim((string) ($a->patient->name ?? ''));

                return "- ID {$a->id}: " . ($for ? "for {$for}, " : '')
                    . Carbon::parse($a->appointment_date)->format('d M Y')
                    . ' at ' . Carbon::parse($a->appointment_time ?: '00:00')->format('h:i A')
                    . ($dr ? " with Dr. {$dr}" : '')
                    . ($a->token?->token_number ? ", token {$a->token->token_number}" : '');
            })->implode("\n");

        // What is already on file for this number. Asking a returning customer for their own name
        // and number is the fastest way to sound like a form rather than a clinic, so the model is
        // told what it already has and instructed not to ask for it again.
        $known = static::knownCaller($storeId, $phoneKey);
        $onFile = $known['name']
            ? "The person messaging is {$known['name']} ({$known['phone']}) — already on file."
            : "This number ({$known['phone']}) is on file but the name is not.";

        // The self branch reads differently depending on what is already known, and a rule that
        // says both "do not ask for their name" and "ask for their name" teaches nothing.
        $selfRule = $known['name']
            ? "  * FOR THEMSELVES — do NOT ask for their name or phone number, both are on file above. "
                . "Ask only for: address, age, gender.\n"
            : "  * FOR THEMSELVES — do NOT ask for their phone number, it is the number they are messaging from. "
                . "Ask for: their name, address, age, gender.\n";

        return "\n\nAPPOINTMENT ACTIONS — you can book, reschedule and cancel appointments for this customer.\n"
            . 'Right now it is ' . now()->format('l, d M Y') . ' at ' . now()->format('H:i')
            . " (24-hour clock, India time).\n"
            . $onFile . "\n"
            . "Doctors:\n{$doctors}\n"
            . "Customer's upcoming appointments:\n" . ($upcoming ?: '- none') . "\n\n"
            . "ACTION RULES:\n"
            . "- Booking submits an appointment REQUEST that the clinic confirms — never promise a confirmed slot, say the clinic will confirm shortly.\n"
            . "- To BOOK, FIRST ask whether the appointment is for themselves or for someone else. Everything after that depends on the answer:\n"
            . $selfRule
            . "  * FOR SOMEONE ELSE — ask for the patient's: name, phone number, address, age, gender.\n"
            . "- Then ask which doctor if there is more than one, plus the date and time.\n"
            . "- Ask for the details you still need in ONE message as a short list, not one question per reply.\n"
            . "- If the customer declines to give a detail, do not press them twice — send the marker with that field left as an empty string.\n"
            . "- Confirm the full details back to the customer. After they confirm, reply with ONLY this marker and no other text:\n"
            . '[[' . self::BOOK_MARKER . ': {"for":"self|other","name":"<patient full name>","phone":"<patient phone>",'
            . '"address":"<patient address>","age":"<age in years>","gender":"male|female|other",'
            . '"doctor":"<doctor name>","date":"YYYY-MM-DD","time":"HH:MM","reason":"<short reason>"}]]' . "\n"
            . "- When \"for\" is \"self\", leave phone as an empty string — it is the number they are messaging from. "
            . "ALWAYS send name: put the name they gave you, or the name on file above if there is one. An empty name "
            . "files a brand-new patient as \"WhatsApp Customer\", which is not their name.\n"
            . "- To RESCHEDULE an upcoming appointment from the list above: confirm the new date and time, then reply with ONLY:\n"
            . '[[' . self::RESCHEDULE_MARKER . ': {"appointment_id": <ID from the list>, "date":"YYYY-MM-DD","time":"HH:MM"}]]' . "\n"
            . "- To CANCEL an upcoming appointment from the list above: name the appointment you are about to cancel and ask them to confirm. Once they confirm, reply with ONLY:\n"
            . '[[' . self::CANCEL_MARKER . ': {"appointment_id": <ID from the list>, "reason":"<short reason, or empty string>"}]]' . "\n"
            . "- If they ask to cancel or move an appointment that is NOT in the list above, do not guess at one — say the team will check and get back to them, and append "
            . \App\Jobs\SendAutoReply::ESCALATE_MARKER . " so a human is told.\n"
            . "- Never tell the customer you cannot cancel or reschedule. You can do both, for any appointment in the list above.\n"
            . "- Time is 24-hour format. Dates must be today or later — interpret \"tomorrow\" etc. from the current date above.\n"
            . "- \"now\", \"right now\", \"immediately\", \"as soon as possible\", \"today itself\" and the like mean TODAY at the "
            . "current time above. Use that time. Never invent a later slot the customer did not ask for, and never offer a "
            . "time that has already passed today.\n"
            . "- Never state a date or time the customer did not give you. If they were vague about when, ask — do not fill it in.\n"
            . "- Changing the time or date of an appointment is a RESCHEDULE, never a new booking. Never answer "
            . "\"change the time\", \"make it earlier\", \"can we move it\" by booking again — that leaves the customer "
            . "with two appointments and two token numbers. Use the reschedule marker on the appointment they already have.\n"
            . "- If they want to change an appointment and the list above is empty, do NOT book a replacement. Say the team "
            . "will sort it out and append " . \App\Jobs\SendAutoReply::ESCALATE_MARKER . ".\n"
            . "- Only offer doctors from the list. Never emit a marker before the customer has confirmed the details.";
    }

    /**
     * Detect and execute an action marker in the model's reply.
     * Returns null when the reply contains no marker (normal Q&A continues), otherwise
     * ['message' => customer-facing text, 'escalate' => bool, 'reason' => string].
     */
    public static function tryHandle(string $reply, int $storeId, string $phoneKey, string $fromPhone): ?array
    {
        $markers = implode('|', [self::BOOK_MARKER, self::RESCHEDULE_MARKER, self::CANCEL_MARKER]);
        if (!preg_match('/\[\[(' . $markers . '):\s*(\{.*?\})\s*\]\]/s', $reply, $m)) {
            return null;
        }
        if (!static::applicable($storeId)) {
            return null;
        }

        $data = json_decode($m[2], true) ?: [];

        try {
            switch ($m[1]) {
                case self::BOOK_MARKER:
                    return static::book($storeId, $phoneKey, $fromPhone, $data);
                case self::CANCEL_MARKER:
                    return static::cancel($storeId, $phoneKey, $data);
                default:
                    return static::reschedule($storeId, $phoneKey, $data);
            }
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
     * (ProcessNewLeadNotifications, isAppointment) — so it appears on the vendor's
     * Appointments screen, and vendor acceptance, lead billing and the HMIS
     * lead→appointment conversion all work exactly as for web bookings.
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

        // Who the appointment is FOR, which is not the same question as who is messaging. The
        // account always belongs to the sender; only the patient details move when they book for
        // somebody else.
        $forOther = mb_strtolower(trim((string) ($data['for'] ?? 'self'))) === 'other';
        $patientName = trim((string) ($data['name'] ?? ''));

        if ($forOther && $patientName === '') {
            return static::failure('The patient\'s name was missing.');
        }

        // The sender's own name — never the other patient's, or booking for a relative would
        // rename the account holder.
        $senderName = $forOther ? '' : $patientName;
        $user = static::resolveUser($storeId, $phoneKey, $fromPhone, $senderName, $store->zone_id ?? null);

        $extraColumns = static::ensurePatientColumns();

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
        // 'myself' / 'other' are the values the web booking writes and every reader downstream
        // tests for (LeadAppointmentService, the appointments and OPD screens) — see
        // Front\UserController::storeServiceRequest.
        $sr->patient_for         = $forOther ? 'other' : 'myself';
        $sr->patient_name        = $forOther ? mb_substr($patientName, 0, 190) : null;
        $sr->patient_phone       = $forOther ? (static::cleanPhone((string) ($data['phone'] ?? '')) ?: null) : null;
        // Age, gender and address are asked for either way: they belong to the patient, and for a
        // self-booking the clinic has a name and number on file but rarely these.
        if ($extraColumns) {
            $sr->patient_age     = static::cleanAge($data['age'] ?? null);
            $sr->patient_gender  = static::cleanGender((string) ($data['gender'] ?? ''));
            $sr->patient_address = trim((string) ($data['address'] ?? '')) ?: null;
        }
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

        // Register the patient and the appointment here and now.
        //
        // A web booking waits for the customer to tap a confirmation, which is why leads sat as
        // enquiries with no patient record behind them. On WhatsApp that confirmation has already
        // happened in the conversation — the marker is only emitted after the customer has agreed
        // to the details read back to them — and the request goes out as a push notification they
        // may never see, so waiting for a second confirmation strands the booking indefinitely.
        //
        // provision() is idempotent on appointments.service_request_id and creates the patient
        // (resolvePatient) with the age, gender and address collected above, then issues a token.
        // Best-effort: the enquiry is already recorded, so a failure here must not lose it.
        $appointment = null;
        try {
            $appointment = LeadAppointmentService::provision((int) $sr->id, $storeId);
        } catch (\Throwable $e) {
            Log::warning("WA booking provisioned no appointment (lead {$sr->id}): " . $e->getMessage());
        }

        // The patient row is built from the account, so one created before this number had a name
        // is filed under the placeholder — and resolvePatient() reuses it rather than renaming it.
        // The clinic asked for a name and was given one; put it on the record they actually read.
        if ($appointment && !$forOther && $patientName !== '') {
            $patient = $appointment->patient;
            if ($patient && static::isPlaceholderName($patient->name)) {
                $patient->name = mb_substr($patientName, 0, 190);
                $patient->save();
            }
        }

        $drName = trim(($doctor->employee->f_name ?? '') . ' ' . ($doctor->employee->l_name ?? ''));

        $who = $forOther ? ' for ' . $patientName : '';
        $whenText = $when->format('d M Y') . ' at ' . $when->format('h:i A')
            . ($drName ? " with Dr. {$drName}" : '');

        // Told as it now stands. Promising "the clinic will confirm shortly" was wrong twice over:
        // the clinic had already auto-accepted, and it was the customer who was being waited on.
        if ($appointment) {
            $token = $appointment->token?->token_number;

            return [
                'message' => '✅ Booked' . $who . ' at ' . ($store->name ?? 'the clinic') . ' on ' . $whenText . '.'
                    . ($token ? " Your token number is {$token}." : '')
                    . " We'll remind you before the visit.",
                'escalate' => false,
                'reason'   => '',
            ];
        }

        return [
            'message' => '✅ Your appointment request at ' . ($store->name ?? 'the clinic') . ' has been sent'
                . $who . ' on ' . $whenText
                . '. The clinic will contact you shortly to confirm it.',
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
            // A number that booked before we knew who it was is still carrying the placeholder.
            // Now that they have given a name, take it — otherwise the very first booking decides
            // what this person is called forever, and they stay "WhatsApp Customer" on every
            // screen the clinic looks at.
            if (trim($name) !== '' && static::isPlaceholderName($user->f_name)) {
                $user->f_name = mb_substr(trim($name), 0, 100);
                $user->save();
            }

            return $user;
        }

        $name = trim($name) ?: (DB::table('store_customers')->where('store_id', $storeId)
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$phoneKey])
            ->value('f_name') ?: self::PLACEHOLDER_NAME);

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

    /**
     * Call off an upcoming appointment, exactly as the panel's own Cancel does: status
     * 'cancelled' with a reason, and an activity-log line naming WhatsApp as the source.
     *
     * The token is deliberately left alone. Numbers are issued per doctor per day and the panel
     * does not reclaim one on cancellation either; renumbering here would move every later
     * patient's token after they had already been told theirs.
     *
     * Only appointments already offered to the customer can be cancelled — upcomingFor() is
     * scoped to their own phone number, so an id invented by the model or typed by the customer
     * cannot reach somebody else's booking.
     */
    protected static function cancel(int $storeId, string $phoneKey, array $data): array
    {
        $upcoming = static::upcomingFor($storeId, $phoneKey);
        if ($upcoming->isEmpty()) {
            return [
                'message'  => "I couldn't find an upcoming appointment for your number. Our team will check and get back to you shortly.",
                'escalate' => true,
                'reason'   => 'Cancellation requested but no upcoming appointment found',
            ];
        }

        $appointment = null;
        if (!empty($data['appointment_id'])) {
            $appointment = $upcoming->firstWhere('id', (int) $data['appointment_id']);
        }

        // No id, or one that is not theirs: fall back to the only appointment they have. With
        // more than one on file, guessing which to cancel is worse than asking.
        if (!$appointment) {
            if ($upcoming->count() > 1) {
                return [
                    'message'  => 'You have more than one upcoming appointment — could you tell me which one to cancel (the date and time)?',
                    'escalate' => false,
                    'reason'   => '',
                ];
            }
            $appointment = $upcoming->first();
        }

        $reason = trim((string) ($data['reason'] ?? ''));
        $note   = 'Cancelled by the patient on WhatsApp' . ($reason ? ' — ' . mb_substr($reason, 0, 200) : '');

        $appointment->status        = 'cancelled';
        $appointment->cancel_reason = mb_substr($note, 0, 500);
        $appointment->save();

        // The queue must not keep calling a token nobody is coming for.
        static::releaseToken((int) $appointment->id);

        $when = Carbon::parse($appointment->appointment_date)->format('d M Y')
            . ' at ' . Carbon::parse($appointment->appointment_time ?: '00:00')->format('h:i A');
        $dr = trim(($appointment->doctorProfile->employee->f_name ?? '') . ' ' . ($appointment->doctorProfile->employee->l_name ?? ''));

        static::activityLog($storeId, $appointment->id, 'cancelled_via_whatsapp',
            "Appointment #{$appointment->id} ({$when}) cancelled by the patient via WhatsApp"
            . ($reason ? ". Reason: {$reason}" : ''));

        static::notifyClinic($storeId, $appointment->id, 'Appointment cancelled on WhatsApp',
            static::patientLabel($appointment) . ' cancelled their appointment on ' . $when
            . ($dr ? ' with Dr. ' . $dr : '') . '.' . ($reason ? ' Reason given: ' . $reason : ''));

        return [
            'message' => 'Your appointment on ' . $when . ($dr ? ' with Dr. ' . $dr : '')
                . ' has been cancelled. If you would like to book another time, just tell me when suits you.',
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

        $appointment = null;
        if (!empty($data['appointment_id'])) {
            $appointment = $upcoming->firstWhere('id', (int) $data['appointment_id']);
        }
        $appointment = $appointment ?: $upcoming->first();

        // Where it was, kept for the log and the clinic's alert before the row is overwritten.
        $fromDate = Carbon::parse($appointment->appointment_date)->format('d M Y');
        $fromTime = Carbon::parse($appointment->appointment_time ?: '00:00')->format('h:i A');

        // Moved in place. Cancelling and re-creating left the patient holding two appointments —
        // the old one still on the clinic's list, the new one with a different id and a different
        // token — when all they asked for was a different time. One appointment, one id, one
        // token: the clinic sees the same row change, not a cancellation and a fresh booking.
        DB::transaction(function () use ($appointment, $when) {
            $appointment->appointment_date = $when->toDateString();
            $appointment->appointment_time = $when->format('H:i');
            // The old slot belonged to the old time and no longer describes this appointment.
            $appointment->slot_id = null;
            $appointment->save();

            static::retimeToken($appointment, $when->toDateString());

            // The lead behind the booking still advertises the time it was requested for, and the
            // vendor's Appointments screen reads it. Left alone, the two disagree forever.
            if ($appointment->service_request_id) {
                ServiceRequest::where('id', $appointment->service_request_id)->update([
                    'preferred_date' => $when->toDateString(),
                    'preferred_time' => $when->format('H:i'),
                    'updated_at'     => now(),
                ]);
            }
        });

        static::activityLog($storeId, $appointment->id, 'rescheduled_via_whatsapp',
            "Appointment #{$appointment->id} moved from {$fromDate} {$fromTime} to "
            . $when->format('d M Y') . ' ' . $when->format('h:i A') . ' via WhatsApp');

        $dr = trim(($appointment->doctorProfile->employee->f_name ?? '') . ' '
            . ($appointment->doctorProfile->employee->l_name ?? ''));

        static::notifyClinic($storeId, $appointment->id, 'Appointment rescheduled on WhatsApp',
            static::patientLabel($appointment) . ' moved their appointment from '
            . $fromDate . ' at ' . $fromTime . ' to '
            . $when->format('d M Y') . ' at ' . $when->format('h:i A') . ($dr ? ' with Dr. ' . $dr : '') . '.');

        // Re-read: retimeToken() may have issued a new one for a new day, and the relation on the
        // model in memory still holds whatever was loaded before the move.
        $token = AppointmentToken::where('appointment_id', $appointment->id)->value('token_number');

        return [
            'message' => '✅ Done! Your appointment has been moved to ' . $when->format('d M Y') . ' at ' . $when->format('h:i A')
                . ($dr ? " with Dr. {$dr}" : '')
                . ($token ? ". Your token number is {$token}" : '')
                . ". We'll remind you before the visit.",
            'escalate' => false,
            'reason'   => '',
        ];
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * What the clinic already knows about the number that is messaging.
     *
     * A self-booking must not ask for a name and number the store already holds, so the prompt is
     * told what is on file. The name is looked for in the same two places a booking would find it:
     * the platform account, then the store's own customer book.
     */
    protected static function knownCaller(int $storeId, string $phoneKey): array
    {
        $suffix = "RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?";

        try {
            $user = User::whereRaw($suffix, [$phoneKey])->orderBy('id')->first();
            $name = $user ? trim(($user->f_name ?? '') . ' ' . ($user->l_name ?? '')) : '';

            if ($name === '') {
                $name = trim((string) DB::table('store_customers')->where('store_id', $storeId)
                    ->whereRaw($suffix, [$phoneKey])->value('f_name'));
            }

            // A placeholder is not a name — asking "and your name?" is better than greeting
            // somebody as "WhatsApp Customer".
            if (static::isPlaceholderName($name)) {
                $name = '';
            }

            return ['name' => $name, 'phone' => $phoneKey];
        } catch (\Throwable $e) {
            return ['name' => '', 'phone' => $phoneKey];
        }
    }

    /**
     * Patient details the WhatsApp booking collects that the web form never did.
     *
     * patient_name and patient_phone already exist (the web booking writes them for an "other"
     * booking); age, gender and address are new. Kept off the existing `address` column on
     * purpose — that one holds the STORE's address on a lead, not the patient's.
     */
    protected static function ensurePatientColumns(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            foreach ([
                'patient_age'     => "ALTER TABLE `service_requests` ADD COLUMN `patient_age` VARCHAR(12) NULL",
                'patient_gender'  => "ALTER TABLE `service_requests` ADD COLUMN `patient_gender` VARCHAR(12) NULL",
                'patient_address' => "ALTER TABLE `service_requests` ADD COLUMN `patient_address` VARCHAR(500) NULL",
            ] as $column => $sql) {
                if (!Schema::hasColumn('service_requests', $column)) {
                    DB::statement($sql);
                }
            }
            return $ready = true;
        } catch (\Throwable $e) {
            // Reported false, not rethrown: an appointment the customer has already confirmed is
            // worth more than the three details, so the booking goes through without them rather
            // than failing on an unknown column.
            Log::warning('service_requests patient columns unavailable: ' . $e->getMessage());
            return $ready = false;
        }
    }

    /** Digits only, and only if there are enough of them to be a real number. */
    protected static function cleanPhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';

        return strlen($digits) >= 10 ? $digits : '';
    }

    /**
     * Age as the customer said it, kept as digits.
     *
     * Stored as text rather than an integer because "6 months" is a real answer at a clinic; the
     * number is pulled out when it is plainly a year count, and anything unreadable is dropped
     * rather than guessed at.
     */
    protected static function cleanAge($age): ?string
    {
        $raw = trim((string) $age);
        if ($raw === '') {
            return null;
        }
        if (preg_match('/(\d{1,3})/', $raw, $m) && (int) $m[1] > 0 && (int) $m[1] <= 120) {
            return str_contains(mb_strtolower($raw), 'month') ? mb_substr($raw, 0, 12) : $m[1];
        }

        return null;
    }

    /** male / female / other, or nothing — a half-understood answer is worse than a blank. */
    protected static function cleanGender(string $gender): ?string
    {
        $g = mb_strtolower(trim($gender));
        if ($g === '') {
            return null;
        }
        if (str_starts_with($g, 'm')) {
            return 'male';
        }
        if (str_starts_with($g, 'f')) {
            return 'female';
        }

        return in_array($g, ['other', 'others'], true) ? 'other' : null;
    }

    /**
     * The appointments this number is allowed to move or cancel.
     *
     * Two ways in, and both are needed. Matching the patient's phone covers people who booked for
     * themselves. It does NOT cover a booking made for somebody else: that patient is created with
     * their OWN number (LeadAppointmentService::resolvePatient reads patient_phone), so a parent
     * who books for a child would never see it again — the list came back empty, the model was
     * told it had nothing to reschedule, and it booked a second appointment instead of moving the
     * first. The service request records who actually did the booking, so that is the second way.
     */
    protected static function upcomingFor(int $storeId, string $phoneKey)
    {
        $patientIds = Patient::where('store_id', $storeId)
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$phoneKey])
            ->pluck('id');

        // Every account on this number: resolveUser() matches the same way when booking.
        $userIds = User::whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$phoneKey])
            ->pluck('id');

        $requestIds = $userIds->isEmpty() ? collect() : ServiceRequest::whereIn('user_id', $userIds)
            ->where('service_type', 'doctor_appointment')
            ->pluck('id');

        if ($patientIds->isEmpty() && $requestIds->isEmpty()) {
            return collect();
        }

        return Appointment::where('store_id', $storeId)
            ->where(function ($q) use ($patientIds, $requestIds) {
                if ($patientIds->isNotEmpty()) {
                    $q->whereIn('patient_id', $patientIds);
                }
                if ($requestIds->isNotEmpty()) {
                    $q->orWhereIn('service_request_id', $requestIds);
                }
            })
            ->where('status', 'scheduled')
            ->whereDate('appointment_date', '>=', now()->toDateString())
            ->with(['doctorProfile.employee', 'token', 'patient'])
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

        // A walk-in booked for "now" is already a minute or two old by the time it reaches here:
        // the model picks the clock time, the customer confirms, the queued job runs. Rejecting
        // that as unclear tells the customer their own request made no sense and escalates a
        // booking that was perfectly valid, so a request that has only just slipped past is
        // treated as what it plainly meant — right now.
        if ($when->isPast() && $when->isToday() && $when->diffInMinutes(now()) <= self::PAST_GRACE_MINUTES) {
            return now();
        }

        return $when->isPast() ? null : $when;
    }

    /**
     * Keep an appointment's token pointing at the right day after it moves.
     *
     * Same day: nothing to do. The number is the patient's place in that day's queue and they
     * have already been told it — reissuing would push them to the back of a queue they were
     * already in, over a change of time they asked for.
     *
     * A different day is a different queue, so the old day's token is released and a new one
     * issued on the new day.
     */
    protected static function retimeToken(Appointment $appointment, string $date): void
    {
        $token = AppointmentToken::where('appointment_id', $appointment->id)->first();

        if ($token && (string) $token->token_date === $date) {
            return;
        }

        $token?->delete();
        static::generateToken($appointment->doctor_profile_id, $date, $appointment->id);
    }

    /** Give a token back to the day's queue, so a cancelled slot is not called out. */
    protected static function releaseToken(int $appointmentId): void
    {
        AppointmentToken::where('appointment_id', $appointmentId)->delete();
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

    /** Who the appointment is for, as the clinic knows them. */
    protected static function patientLabel($appointment): string
    {
        $name = trim((string) ($appointment->patient->name ?? ''));
        $phone = trim((string) ($appointment->patient->phone ?? ''));

        return ($name ?: 'A patient') . ($phone ? ' (' . $phone . ')' : '');
    }

    /**
     * Tell the clinic what the customer just did to their own booking.
     *
     * The bot changes the day's list without anybody in the panel touching it, so this is the
     * store's only notice that a slot has moved or emptied. It lands in the vendor's
     * notification bell and links straight to the appointment. Never throws: the appointment is
     * already cancelled or moved by this point, and failing to announce it must not undo that.
     */
    protected static function notifyClinic(int $storeId, int $appointmentId, string $title, string $message): void
    {
        try {
            _inAppNotification(
                $title,
                $message,
                null,
                $storeId,
                route('vendor.appointment.show', $appointmentId),
                'vendor'
            );
        } catch (\Throwable $e) {
            Log::warning("WA appointment notify failed (store {$storeId}): " . $e->getMessage());
        }
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
