<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentToken;
use App\Models\DoctorProfile;
use App\Models\DoctorSlot;
use App\Models\HospitalActivityLog;
use App\Models\Patient;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Turns a confirmed service_request (lead) into a real `appointments` row.
 *
 * Without this, hospital leads only ever became opd_visits, so they never appeared in the
 * patient's Appointments tab, never got a token, never rode the WhatsApp reminder job, and
 * had no detail page from which to schedule a next visit.
 *
 * Idempotent on appointments.service_request_id — safe to call from every confirm path and
 * to re-run.
 */
class LeadAppointmentService
{
    /**
     * @param string|null $statusOverride Only for backfilling historical leads, where
     *                                    'scheduled' would misrepresent a visit that is
     *                                    already in the past.
     */
    public static function provision(int $serviceRequestId, int $storeId, ?string $statusOverride = null): ?Appointment
    {
        if (!Schema::hasTable('appointments') || !Schema::hasColumn('appointments', 'service_request_id')) {
            Log::warning('LeadAppointmentService: appointments.service_request_id missing — run the ALTER TABLE first.');
            return null;
        }

        $existing = Appointment::where('store_id', $storeId)
            ->where('service_request_id', $serviceRequestId)
            ->first();
        if ($existing) {
            return $existing;
        }

        $sr = ServiceRequest::find($serviceRequestId);
        if (!$sr) {
            return null;
        }

        // Non-hospital leads (no doctor / no date) must never create appointments.
        if (!$sr->preferred_doctor_id || !$sr->preferred_date) {
            return null;
        }

        // The doctor has to belong to this store, otherwise we'd book across tenants.
        $doctor = DoctorProfile::where('id', $sr->preferred_doctor_id)
            ->where('store_id', $storeId)
            ->first();
        if (!$doctor) {
            Log::warning("LeadAppointmentService: doctor {$sr->preferred_doctor_id} not in store {$storeId} for lead {$serviceRequestId}.");
            return null;
        }

        $slot = $sr->preferred_slot_id ? DoctorSlot::find($sr->preferred_slot_id) : null;
        $time = $sr->preferred_time ?: ($slot?->slot_start ?? '00:00');

        // A slot booked after it has already started would be recorded at its start time — in the
        // past before the row even existed. SendAppointmentRemindersJob skips anything not in the
        // future, so such an appointment silently never gets a reminder and nobody finds out.
        //
        // Only today's bookings are nudged: a future date keeps exactly the time the customer
        // chose, and a past date is a historical backfill, which must keep its original time.
        try {
            if (\Carbon\Carbon::parse($sr->preferred_date)->isToday()
                && \Carbon\Carbon::parse($sr->preferred_date . ' ' . $time)->isPast()) {
                $time = now()->format('H:i:s');
            }
        } catch (\Throwable $e) {
            // Unparseable date/time — keep what was chosen rather than inventing one.
        }

        // Capacity is NOT enforced here: the booking was already confirmed to the customer,
        // so refusing to record it would lose the appointment entirely. Overbooking is logged
        // for the vendor to sort out instead.
        if ($slot) {
            $booked = Appointment::where('slot_id', $slot->id)
                ->where('appointment_date', $sr->preferred_date)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->count();
            if ($booked >= $slot->max_patients) {
                Log::info("LeadAppointmentService: slot {$slot->id} on {$sr->preferred_date} is over capacity ({$booked}/{$slot->max_patients}) — provisioning lead {$serviceRequestId} anyway.");
            }
        }

        $patient = self::resolvePatient($sr, $storeId);
        if (!$patient) {
            return null;
        }

        $status = $statusOverride ?: 'scheduled';

        DB::beginTransaction();
        try {
            $appointment = Appointment::create([
                'store_id'           => $storeId,
                'service_request_id' => $sr->id,
                'patient_id'         => $patient->id,
                'doctor_profile_id'  => $sr->preferred_doctor_id,
                'slot_id'            => $sr->preferred_slot_id ?: null,
                'appointment_date'   => $sr->preferred_date,
                'appointment_time'   => $time,
                'booking_type'       => 'online',
                'status'             => $status,
                'reason'             => $sr->requirements,
                'booked_by'          => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            ]);

            AppointmentToken::issue((int) $sr->preferred_doctor_id, (string) $sr->preferred_date, $appointment->id);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("LeadAppointmentService: failed to provision lead {$serviceRequestId} for store {$storeId}: " . $e->getMessage());
            return null;
        }

        try {
            HospitalActivityLog::record(
                $storeId,
                'appointment',
                $appointment->id,
                'created_from_lead',
                "Appointment #{$appointment->id} created from confirmed enquiry #{$sr->id} for {$patient->name} on {$sr->preferred_date}",
                ['service_request_id' => $sr->id, 'date' => $sr->preferred_date, 'status' => $status]
            );
        } catch (\Throwable $e) {
            Log::warning('LeadAppointmentService: activity log skipped: ' . $e->getMessage());
        }

        return $appointment;
    }

    /**
     * Find-then-create, so repeat bookings reuse one patient record. The previous callers
     * created a fresh Patient on every "booking for someone else", which fragmented a
     * patient's history across duplicate rows.
     */
    public static function resolvePatient(ServiceRequest $sr, int $storeId): ?Patient
    {
        $isOther = ($sr->patient_for ?? '') === 'other' && $sr->patient_name;

        if ($isOther) {
            $phone = $sr->patient_phone;

            if ($phone) {
                $existing = Patient::where('store_id', $storeId)
                    ->where('phone', $phone)
                    ->where('name', $sr->patient_name)
                    ->first()
                    ?? Patient::where('store_id', $storeId)->where('phone', $phone)->first();
                if ($existing) {
                    return static::fillPatientDetails($existing, $sr);
                }
            }

            return Patient::create(array_merge([
                'store_id'    => $storeId,
                'user_id'     => null,
                'patient_uid' => Patient::generateUid($storeId),
                'name'        => $sr->patient_name,
                'phone'       => $phone,
                'status'      => 1,
            ], static::patientDetails($sr)));
        }

        $user = $sr->user_id ? User::find($sr->user_id) : null;

        if ($user) {
            $existing = Patient::where('store_id', $storeId)->where('user_id', $user->id)->first();
            if ($existing) {
                return static::fillPatientDetails($existing, $sr);
            }

            $name = trim(($user->f_name ?? '') . ' ' . ($user->l_name ?? '')) ?: ($user->name ?? 'Patient');

            // A walk-in record may already exist for this phone before the account was linked.
            if ($user->phone) {
                $byPhone = Patient::where('store_id', $storeId)
                    ->where('phone', $user->phone)
                    ->whereNull('user_id')
                    ->first();
                if ($byPhone) {
                    $byPhone->user_id = $user->id;
                    $byPhone->save();
                    return static::fillPatientDetails($byPhone, $sr);
                }
            }

            return Patient::create(array_merge([
                'store_id'    => $storeId,
                'user_id'     => $user->id,
                'patient_uid' => Patient::generateUid($storeId),
                'name'        => $name,
                'phone'       => $user->phone,
                'email'       => $user->email,
                'status'      => 1,
            ], static::patientDetails($sr)));
        }

        return null;
    }

    /**
     * Age, gender and address as the enquiry captured them.
     *
     * The WhatsApp booking bot asks for these whether the appointment is for the caller or for
     * somebody else; a web booking still does not, so every one of them can be absent.
     */
    protected static function patientDetails(ServiceRequest $sr): array
    {
        return array_filter([
            'age'     => trim((string) ($sr->patient_age ?? '')) ?: null,
            'gender'  => trim((string) ($sr->patient_gender ?? '')) ?: null,
            'address' => trim((string) ($sr->patient_address ?? '')) ?: null,
        ], fn($v) => $v !== null);
    }

    /**
     * Fill in what the record is missing, and only that.
     *
     * A patient the clinic already has may carry details entered at the desk from documents. An
     * enquiry is one person typing on their phone, so it fills blanks — it never overwrites what
     * the clinic already recorded.
     */
    protected static function fillPatientDetails(Patient $patient, ServiceRequest $sr): Patient
    {
        $fill = [];
        foreach (static::patientDetails($sr) as $field => $value) {
            if (trim((string) ($patient->{$field} ?? '')) === '') {
                $fill[$field] = $value;
            }
        }

        if ($fill) {
            try {
                $patient->fill($fill)->save();
            } catch (\Throwable $e) {
                Log::warning('LeadAppointmentService: patient detail fill skipped: ' . $e->getMessage());
            }
        }

        return $patient;
    }

}
