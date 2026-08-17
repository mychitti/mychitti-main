<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\HospitalActivityLog;
use App\Models\OpdVisit;
use Illuminate\Support\Facades\Log;

/**
 * Closing an appointment, and the messages that hang off that moment.
 *
 * Shared because a consultation ends in two different places. The status page is the explicit
 * route — Checked in → Consulting → Completed — but that page has no link in the sidebar and
 * almost nobody walks it. The route staff actually use is the OP consultation receipt: billing
 * the visit is the real "this is over" signal in a hospital.
 *
 * Before this was shared the two deferred to each other and the patient got nothing: the receipt
 * skipped the feedback request expecting the appointment to send it, while the appointment sat at
 * 'scheduled' forever because no one opened its page. The appointment also never reached
 * 'completed', which is the only thing AppointmentRebookReminder counts as a visit.
 */
class AppointmentCompletion
{
    /** Statuses a consultation can still be completed from. */
    const OPEN = ['scheduled', 'checked_in', 'consulting'];

    /**
     * Close an appointment and send what completion owes the patient.
     *
     * Idempotent, and safe to call from anywhere: an appointment already completed, cancelled or
     * marked no-show is left exactly as it is. Returns whether this call was the one that closed
     * it, so a caller can log the difference.
     */
    public static function complete(Appointment $appointment): bool
    {
        if (!in_array($appointment->status, self::OPEN, true)) {
            return false;
        }

        $from = $appointment->status;
        $appointment->status = 'completed';
        $appointment->save();

        try {
            HospitalActivityLog::record(
                (int) $appointment->store_id,
                'appointment',
                (int) $appointment->id,
                'status_changed',
                "Appointment #{$appointment->id} completed when the consultation was billed (was {$from})",
                ['from' => $from, 'to' => 'completed', 'via' => 'consultation_receipt']
            );
        } catch (\Throwable $e) {
            Log::warning('AppointmentCompletion: activity log skipped: ' . $e->getMessage());
        }

        static::autoSend($appointment);

        return true;
    }

    /**
     * Consultation summary and feedback request, for hospitals that turned them on under Send
     * Notifications. Best-effort and locked per record — a status flipped back and forth cannot
     * message the patient twice.
     *
     * The summary needs the OPD visit recorded against this appointment; an appointment with no
     * visit written up has nothing to summarise and only the feedback request goes. It is keyed on
     * the visit rather than the appointment because the receipt path sends the same summary under
     * the same key, so whichever fires first wins and the other is a no-op.
     */
    public static function autoSend(Appointment $appointment): void
    {
        $storeId = (int) $appointment->store_id;

        $visit = OpdVisit::where('store_id', $storeId)
            ->where('appointment_id', $appointment->id)
            ->latest('id')
            ->first();

        if ($visit) {
            HmisWhatsAppShare::auto('treatment', $storeId, (int) $visit->id,
                fn() => HmisWhatsAppShare::treatment($visit));
        }

        $appointment->loadMissing('patient');
        if (!$appointment->patient) {
            return;
        }

        // Held back by the hospital's chosen delay, so the question lands once the patient has
        // left and can actually judge the visit — not while they are still at the desk.
        HmisWhatsAppShare::auto(
            'feedback',
            $storeId,
            (int) $appointment->id,
            fn() => HmisWhatsAppShare::feedback(
                $storeId,
                $appointment->patient,
                $appointment->appointment_date,
                null,
                (int) $appointment->id
            ),
            'appointment',
            HmisWhatsAppShare::feedbackDueAt($storeId)
        );
    }
}
