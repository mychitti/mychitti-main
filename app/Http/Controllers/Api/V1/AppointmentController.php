<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentToken;
use App\Models\DoctorProfile;
use App\Models\DoctorSlot;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    // GET /hospital/slots?store_id=&doctor_profile_id=&date=
    public function slots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id'          => 'required|integer',
            'doctor_profile_id' => 'required|integer',
            'date'              => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $doctor = DoctorProfile::where('store_id', $request->store_id)
            ->where('id', $request->doctor_profile_id)
            ->first();

        if (!$doctor) {
            return response()->json(['message' => 'Doctor not found'], 404);
        }

        $dayOfWeek = Carbon::parse($request->date)->dayOfWeek;

        $slots = DoctorSlot::where('doctor_profile_id', $request->doctor_profile_id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', 1)
            ->get();

        $slots->each(function ($slot) use ($request) {
            $booked = Appointment::where('slot_id', $slot->id)
                ->where('appointment_date', $request->date)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->count();
            $slot->booked    = $booked;
            $slot->available = max(0, $slot->max_patients - $booked);
        });

        return response()->json([
            'date'       => $request->date,
            'day'        => Carbon::parse($request->date)->format('l'),
            'slots'      => $slots,
        ]);
    }

    // GET /hospital/doctor-slots?doctor_profile_id=&date=  (date defaults to today)
    public function doctorSlots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_profile_id' => 'required|integer|exists:doctor_profiles,id',
            'date'              => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $date = Carbon::parse($request->date ?? now())->toDateString();

        $slots = DoctorSlot::where('doctor_profile_id', $request->doctor_profile_id)
            ->where('is_active', 1)
            ->orderBy('day_of_week')
            ->orderBy('slot_start')
            ->get(['id', 'day_of_week', 'slot_start', 'slot_end', 'slot_duration_minutes', 'max_patients']);

        $slotIds = $slots->pluck('id');

        // Fetch all booking counts in one query
        $bookedCounts = Appointment::whereIn('slot_id', $slotIds)
            ->where('appointment_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->selectRaw('slot_id, COUNT(*) as booked')
            ->groupBy('slot_id')
            ->pluck('booked', 'slot_id');

        $grouped = $slots->map(function ($slot) use ($bookedCounts) {
            $booked          = $bookedCounts->get($slot->id, 0);
            $slot->booked    = $booked;
            $slot->available = max(0, $slot->max_patients - $booked);
            return $slot;
        })->groupBy('day_of_week')->map(function ($daySlots, $day) {
            return [
                'day'   => DoctorSlot::DAYS[$day],
                'slots' => $daySlots->values(),
            ];
        })->values();

        return response()->json([
            'status' => true,
            'date'   => $date,
            'data'   => $grouped,
        ]);
    }

    // GET /hospital/patient-lookup?store_id=&phone=
    public function patientLookup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|integer',
            'phone'    => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $patient = Patient::where('store_id', $request->store_id)
            ->where('phone', $request->phone)
            ->where('status', 1)
            ->first(['id', 'patient_uid', 'name', 'dob', 'gender', 'blood_group', 'phone', 'email']);

        if (!$patient) {
            return response()->json(['message' => 'Patient not found'], 404);
        }

        return response()->json($patient);
    }

    // POST /hospital/appointments/book  (auth:api)
    public function book(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id'          => 'required|integer',
            'doctor_profile_id' => 'required|integer|exists:doctor_profiles,id',
            'appointment_date'  => 'required|date|after_or_equal:today',
            'slot_id'           => 'nullable|integer|exists:doctor_slots,id',
            'appointment_time'  => 'required_without:slot_id',
            'reason'            => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth('api')->user();

        // Verify doctor belongs to store
        $doctor = DoctorProfile::where('id', $request->doctor_profile_id)
            ->where('store_id', $request->store_id)
            ->first();

        if (!$doctor) {
            return response()->json(['message' => 'Doctor not found in this store'], 404);
        }

        // Resolve patient — find by user_id + store_id, or auto-create for this store
        $patient = Patient::where('user_id', $user->id)
            ->where('store_id', $request->store_id)
            ->first();

        if (!$patient) {
            $patient = $this->createPatientFromUser($user, $request->store_id);
        }

        $appointmentTime = $request->appointment_time;

        // Slot-based booking
        if ($request->slot_id) {
            $slot = DoctorSlot::where('id', $request->slot_id)
                ->where('doctor_profile_id', $request->doctor_profile_id)
                ->where('is_active', 1)
                ->first();

            if (!$slot) {
                return response()->json(['message' => 'Slot not found or inactive'], 404);
            }

            $dayOfWeek = Carbon::parse($request->appointment_date)->dayOfWeek;
            if ($slot->day_of_week != $dayOfWeek) {
                return response()->json(['message' => 'Slot is not available on this day'], 422);
            }

            $booked = Appointment::where('slot_id', $slot->id)
                ->where('appointment_date', $request->appointment_date)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->count();

            if ($booked >= $slot->max_patients) {
                return response()->json(['message' => 'This slot is fully booked'], 422);
            }

            $appointmentTime = $slot->slot_start;
        }

        DB::beginTransaction();
        try {
            $appointment = Appointment::create([
                'store_id'          => $request->store_id,
                'patient_id'        => $patient->id,
                'doctor_profile_id' => $request->doctor_profile_id,
                'slot_id'           => $request->slot_id,
                'appointment_date'  => $request->appointment_date,
                'appointment_time'  => $appointmentTime,
                'booking_type'      => 'online',
                'status'            => 'scheduled',
                'reason'            => $request->reason,
                'booked_by'         => null,
            ]);

            $token = $this->generateToken(
                $request->doctor_profile_id,
                $request->appointment_date,
                $appointment->id
            );

            DB::commit();

            return response()->json([
                'message'          => 'Appointment booked successfully',
                'appointment_id'   => $appointment->id,
                'patient_id'       => $patient->id,
                'patient_uid'      => $patient->patient_uid,
                'token_number'     => $token,
                'appointment_date' => $appointment->appointment_date,
                'appointment_time' => $appointment->appointment_time,
                'status'           => $appointment->status,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Booking failed: ' . $e->getMessage()], 500);
        }
    }

    // GET /hospital/my-appointments  (auth:api)
    public function myAppointments(Request $request)
    {
        $user = auth('api')->user();

        $query = Appointment::whereHas('patient', fn($q) => $q->where('user_id', $user->id))
            ->with([
                'patient:id,name,patient_uid,phone',
                'doctorProfile:id,specialization,consultation_fee',
                'doctorProfile.employee:id,f_name,l_name',
                'token:appointment_id,token_number',
                'slot:id,slot_start,slot_end',
            ])
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time');

        if ($request->store_id) {
            $query->where('store_id', $request->store_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $appointments = $query->paginate(15);

        return response()->json($appointments);
    }

    // GET /hospital/appointments/{id}
    public function show($id)
    {
        $appointment = Appointment::with(['patient', 'doctorProfile.employee', 'slot', 'token'])
            ->find($id);

        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }

        return response()->json($appointment);
    }

    // POST /hospital/appointments/{id}/cancel  (auth:api)
    public function cancel(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'cancel_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }

        if (in_array($appointment->status, ['completed', 'cancelled'])) {
            return response()->json(['message' => 'Appointment cannot be cancelled'], 422);
        }

        $appointment->status        = 'cancelled';
        $appointment->cancel_reason = $request->cancel_reason;
        $appointment->save();

        return response()->json(['message' => 'Appointment cancelled successfully']);
    }

    // POST /hospital/appointments/{id}/reschedule  (auth:api)
    public function reschedule(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'appointment_date' => 'required|date|after_or_equal:today',
            'slot_id'          => 'nullable|integer|exists:doctor_slots,id',
            'appointment_time' => 'required_without:slot_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $old = Appointment::find($id);
        if (!$old) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }

        if (in_array($old->status, ['completed', 'cancelled'])) {
            return response()->json(['message' => 'Cannot reschedule a ' . $old->status . ' appointment'], 422);
        }

        $appointmentTime = $request->appointment_time;

        if ($request->slot_id) {
            $slot = DoctorSlot::where('id', $request->slot_id)
                ->where('doctor_profile_id', $old->doctor_profile_id)
                ->where('is_active', 1)
                ->first();

            if (!$slot) {
                return response()->json(['message' => 'Slot not found or inactive'], 404);
            }

            $dayOfWeek = Carbon::parse($request->appointment_date)->dayOfWeek;
            if ($slot->day_of_week != $dayOfWeek) {
                return response()->json(['message' => 'Slot is not available on this day'], 422);
            }

            $booked = Appointment::where('slot_id', $slot->id)
                ->where('appointment_date', $request->appointment_date)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->count();

            if ($booked >= $slot->max_patients) {
                return response()->json(['message' => 'Selected slot is fully booked'], 422);
            }

            $appointmentTime = $slot->slot_start;
        }

        DB::beginTransaction();
        try {
            $old->status        = 'cancelled';
            $old->cancel_reason = 'Rescheduled';
            $old->save();

            $new = Appointment::create([
                'store_id'          => $old->store_id,
                'patient_id'        => $old->patient_id,
                'doctor_profile_id' => $old->doctor_profile_id,
                'slot_id'           => $request->slot_id,
                'appointment_date'  => $request->appointment_date,
                'appointment_time'  => $appointmentTime,
                'booking_type'      => $old->booking_type,
                'status'            => 'scheduled',
                'reason'            => $old->reason,
                'rescheduled_from'  => $old->id,
                'booked_by'         => null,
            ]);

            $token = $this->generateToken($old->doctor_profile_id, $request->appointment_date, $new->id);

            DB::commit();

            return response()->json([
                'message'          => 'Appointment rescheduled successfully',
                'appointment_id'   => $new->id,
                'token_number'     => $token,
                'appointment_date' => $new->appointment_date,
                'appointment_time' => $new->appointment_time,
                'status'           => $new->status,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Reschedule failed: ' . $e->getMessage()], 500);
        }
    }

    private function createPatientFromUser($user, int $storeId): Patient
    {
        // Generate UID for this store
        $last  = Patient::where('store_id', $storeId)->latest('id')->first();
        $next  = $last ? ($last->id + 1) : 1;
        $uid   = 'P-' . str_pad($next, 5, '0', STR_PAD_LEFT);

        return Patient::create([
            'store_id'   => $storeId,
            'user_id'    => $user->id,
            'patient_uid'=> $uid,
            'name'       => trim(($user->f_name ?? '') . ' ' . ($user->l_name ?? '')) ?: $user->name,
            'phone'      => $user->phone,
            'email'      => $user->email,
            'status'     => 1,
        ]);
    }

    private function generateToken(int $doctorProfileId, string $date, int $appointmentId): int
    {
        $last        = AppointmentToken::where('doctor_profile_id', $doctorProfileId)
            ->where('token_date', $date)
            ->max('token_number');
        $tokenNumber = ($last ?? 0) + 1;

        AppointmentToken::create([
            'appointment_id'    => $appointmentId,
            'token_number'      => $tokenNumber,
            'token_date'        => $date,
            'doctor_profile_id' => $doctorProfileId,
        ]);

        return $tokenNumber;
    }

    // GET /hospital/doctors?store_id=&item_id=
    public function doctors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|integer|exists:stores,id',
            'item_id'  => 'nullable|integer|exists:items,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = DoctorProfile::where('store_id', $request->store_id)
            ->with(['employee:id,f_name,l_name,phone,image', 'services'])
            ->whereHas('slots', fn($s) => $s->where('is_active', 1));

        if ($request->filled('item_id')) {
            $query->whereHas('services', fn($s) => $s->where('item_id', $request->item_id));
        }

        $doctors = $query->get()->map(fn($d) => [
            'id'                => $d->id,
            'name'              => 'Dr. ' . trim(($d->employee?->f_name ?? '') . ' ' . ($d->employee?->l_name ?? '')),
            'image'             => $d->employee?->image,
            'specialization'    => $d->specialization,
            'qualification'     => $d->qualification,
            'department'        => $d->department,
            'consultation_fee'  => (float) $d->consultation_fee,
            'available_days'    => $d->available_days,
            'service_ids'       => $d->services->pluck('item_id'),
        ]);

        return response()->json(['doctors' => $doctors]);
    }
}
