<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentToken;
use App\Models\DoctorSlot;
use App\Models\HospitalActivityLog;
use Illuminate\Support\Facades\DB;

/**
 * Moving an appointment to another time.
 *
 * One implementation, two callers: the counter moving it there and then, and a patient tapping
 * Confirm on a request the hospital sent. They must do the identical thing — cancel the old row,
 * raise a new one that remembers what it came from, and issue a fresh token — or the two paths
 * drift and a patient-confirmed move ends up subtly different from a staff-made one, which is the
 * kind of difference nobody finds until a queue number is wrong.
 */
class AppointmentReschedule
{
    /**
     * Whether a slot has no room left on a date.
     *
     * Checked again at the moment of the move, never only when the time was proposed: a request
     * sent on Monday and confirmed on Thursday is three days in which somebody else can take the
     * last place in that slot.
     */
    public static function slotFull(?int $slotId, string $date): bool
    {
        if (!$slotId) {
            return false;
        }

        $slot = DoctorSlot::find($slotId);
        if (!$slot) {
            return false;
        }

        $booked = Appointment::where('slot_id', $slotId)
            ->where('appointment_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();

        return $booked >= $slot->max_patients;
    }

    /**
     * Move it, and say what the new appointment is.
     *
     * $by is what goes in the log — "the front desk" or "the patient" — because six weeks later
     * the only question anybody asks about a moved appointment is who moved it.
     */
    public static function apply(
        Appointment $old,
        string $date,
        string $time,
        ?int $slotId,
        string $by,
        ?int $bookedBy = null
    ): Appointment {
        return DB::transaction(function () use ($old, $date, $time, $slotId, $by, $bookedBy) {
            $from = $old->appointment_date;

            $old->status        = 'cancelled';
            $old->cancel_reason = 'Rescheduled';
            $old->save();

            $new = Appointment::create([
                'store_id'          => $old->store_id,
                'patient_id'        => $old->patient_id,
                'doctor_profile_id' => $old->doctor_profile_id,
                'slot_id'           => $slotId,
                'appointment_date'  => $date,
                'appointment_time'  => $time,
                'booking_type'      => $old->booking_type,
                'status'            => 'scheduled',
                'reason'            => $old->reason,
                'rescheduled_from'  => $old->id,
                'booked_by'         => $bookedBy,
            ]);

            AppointmentToken::issue((int) $old->doctor_profile_id, $date, (int) $new->id);

            HospitalActivityLog::record(
                (int) $old->store_id, 'appointment', (int) $new->id, 'rescheduled',
                "Appointment #{$old->id} rescheduled to {$date} by {$by}. New appointment #{$new->id}",
                [
                    'old_appointment_id' => $old->id,
                    'from_date'          => $from,
                    'to_date'            => $date,
                    'by'                 => $by,
                    'patient_id'         => $old->patient_id,
                ]
            );

            return $new;
        });
    }
}
