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
        if ($slotId) {
            $slot = DoctorSlot::find($slotId);
            if ($slot) {
                $booked = Appointment::where('slot_id', $slotId)
                    ->where('appointment_date', $date)
                    ->whereNotIn('status', ['cancelled', 'no_show'])
                    ->count();

                if ($booked >= $slot->max_patients) {
                    throw new \RuntimeException('Selected slot is fully booked.');
                }
            }
        }

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
