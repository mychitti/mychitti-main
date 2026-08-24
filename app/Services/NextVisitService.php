<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentToken;
use App\Models\DoctorSlot;
use App\Models\HospitalActivityLog;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; 

/**
 * Books a patient's follow-up ("next visit") as a fresh appointment.
 *
 * Shared by the appointment detail page and the OPD consultation screen, so a follow-up booked
 * mid-consultation is the same record as one booked from the appointment — it gets a token, shows
 * in the patient's Appointments tab, and rides SendAppointmentRemindersJob.
 */
class NextVisitService
{
    /**
     * @param  array  $context  Provenance for the activity log, e.g.
     *                          ['from_appointment_id' => 12] or ['from_opd_visit_id' => 34]
     * @throws \RuntimeException when the chosen slot is full
     */
    public static function schedule(
        int $storeId,
        int $patientId,
        int $doctorProfileId,
        string $date,
        string $time,
        ?int $slotId = null,
        ?string $reason = null,
        array $context = []
    ): Appointment {
        self::assertSlotFree($slotId, $date);

        DB::beginTransaction();
        try {
            $next = Appointment::create([
                'store_id'          => $storeId,
                'patient_id'        => $patientId,
                'doctor_profile_id' => $doctorProfileId,
                'slot_id'           => $slotId,
                'appointment_date'  => $date,
                'appointment_time'  => $time,
                'booking_type'      => 'follow_up',
                'status'            => 'scheduled',
                'reason'            => trim((string) $reason) ?: 'Follow-up visit',
                'booked_by'         => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            ]);

            AppointmentToken::issue($doctorProfileId, $date, $next->id);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        try {
            $source = isset($context['from_opd_visit_id'])
                ? "OPD visit #{$context['from_opd_visit_id']}"
                : (isset($context['from_appointment_id']) ? "appointment #{$context['from_appointment_id']}" : 'consultation');

            HospitalActivityLog::record(
                $storeId,
                'appointment',
                $next->id,
                'next_visit_scheduled',
                "Next visit scheduled from {$source} on {$date}. New appointment #{$next->id}",
                $context + ['date' => $date, 'time' => $time]
            );
        } catch (\Throwable $e) {
            Log::warning('Next-visit activity log skipped: ' . $e->getMessage());
        }

        self::notifyPatient($storeId, $patientId, $date, $time, $next);

        return $next;
    }

    /**
     * Move an already-booked follow-up to a new date or time.
     *
     * A treatment scheduled on the consultation screen carries the appointment it was booked as,
     * so dragging that treatment to another day has to move the booking too — otherwise the plan
     * says Friday while the desk's day list still says Tuesday.
     *
     * Follows the register's own reschedule: the old row is cancelled and a new one created
     * pointing back at it, so the day it was taken off still shows what happened rather than the
     * appointment silently vanishing from that morning's list.
     *
     * @throws \RuntimeException when the appointment is closed or the chosen slot is full
     */
    public static function reschedule(
        Appointment $old,
        string $date,
        string $time,
        ?int $slotId = null,
        array $context = []
    ): Appointment {
        if (in_array($old->status, ['completed', 'cancelled'])) {
            throw new \RuntimeException('That follow-up is already ' . $old->status . ' and cannot be moved.');
        }

        self::assertSlotFree($slotId, $date);

        DB::beginTransaction();
        try {
            $old->status        = 'cancelled';
            $old->cancel_reason = 'Rescheduled';
            $old->save();

            $next = Appointment::create([
                'store_id'          => $old->store_id,
                'patient_id'        => $old->patient_id,
                'doctor_profile_id' => $old->doctor_profile_id,
                'slot_id'           => $slotId,
                'appointment_date'  => $date,
                'appointment_time'  => $time,
                'booking_type'      => $old->booking_type ?: 'follow_up',
                'status'            => 'scheduled',
                'reason'            => $old->reason,
                'rescheduled_from'  => $old->id,
                'booked_by'         => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            ]);

            AppointmentToken::issue((int) $old->doctor_profile_id, $date, $next->id);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        try {
            HospitalActivityLog::record(
                (int) $old->store_id,
                'appointment',
                $next->id,
                'rescheduled',
                "Appointment #{$old->id} rescheduled to {$date}. New appointment #{$next->id}",
                $context + ['old_appointment_id' => $old->id, 'to_date' => $date, 'time' => $time]
            );
        } catch (\Throwable $e) {
            Log::warning('Next-visit reschedule log skipped: ' . $e->getMessage());
        }

        self::notifyPatient((int) $old->store_id, (int) $old->patient_id, $date, $time, $next);

        return $next;
    }

    /** @throws \RuntimeException when the slot has no room left on that date */
    private static function assertSlotFree(?int $slotId, string $date): void
    {
        if (!$slotId) {
            return;
        }

        $slot = DoctorSlot::find($slotId);
        if (!$slot) {
            return;
        }

        $booked = Appointment::where('slot_id', $slotId)
            ->where('appointment_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();

        if ($booked >= $slot->max_patients) {
            throw new \RuntimeException('Selected slot is fully booked.');
        }
    }

    /**
     * Courtesy WhatsApp confirmation from the vendor's own number. Free text, so it only
     * delivers inside the patient's 24h window; the scheduled reminder template covers everyone
     * regardless, closer to the visit. Never allowed to fail the booking.
     */
    private static function notifyPatient(int $storeId, int $patientId, string $date, string $time, ?Appointment $appointment = null): void
    {
        try {
            $patient = Patient::find($patientId);
            if (!$patient || !$patient->phone) {
                return;
            }

            $wa = WhatsAppService::make($storeId);
            if ($wa->source() !== 'vendor') {
                return;
            }

            // A hospital that turned the follow-up on under Send Notifications gets the approved
            // template, which delivers whether or not the patient has an open 24h window. The
            // free-text note below is the old behaviour and only reaches someone already in a
            // conversation — so it stays as the fallback rather than being sent as well.
            if ($appointment && HmisWhatsAppShare::auto(
                'followup',
                $storeId,
                (int) $appointment->id,
                fn() => HmisWhatsAppShare::followUpForAppointment($appointment),
                'appointment',
                HmisWhatsAppShare::followUpDueAt($storeId, $appointment->appointment_date)
            )) {
                return;
            }

            $storeName = DB::table('stores')->where('id', $storeId)->value('name') ?: 'our clinic';
            $when      = Carbon::parse($date)->format('d M Y') . ' at ' . Carbon::parse($time)->format('h:i A');

            $wa->sendText(
                $patient->phone,
                "Hi {$patient->name}, your next visit at {$storeName} has been scheduled for {$when}. "
                    . "We'll remind you before the appointment. Reply here if you need to change it.",
                false,
                'next visit'
            );
        } catch (\Throwable $e) {
            Log::warning('Next-visit WhatsApp skipped: ' . $e->getMessage());
        }
    }
}
