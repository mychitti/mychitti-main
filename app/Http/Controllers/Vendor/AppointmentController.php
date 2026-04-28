<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentToken;
use App\Models\DoctorProfile;
use App\Models\DoctorSlot;
use App\Models\Patient;
use App\Models\ServiceRequest;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $doctors  = DoctorProfile::where('store_id', $store_id)->with('employee')->get();

        $query = Appointment::where('store_id', $store_id)
            ->with(['patient', 'doctorProfile.employee', 'token'])
            ->orderByDesc('appointment_date')
            ->orderBy('appointment_time');

        if ($request->date) {
            $query->where('appointment_date', $request->date);
        }
        if ($request->doctor_id) {
            $query->where('doctor_profile_id', $request->doctor_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $appointments = $query->paginate(20)->withQueryString();

        return view('vendor-views.appointment.list', [
            'appointments' => $appointments,
            'doctors'      => $doctors,
            'statuses'     => Appointment::STATUSES,
            'filters'      => $request->only('date', 'doctor_id', 'status'),
        ]);
    }

    public function create()
    {
        return view('vendor-views.appointment.create');
    }

    // AJAX: search patients for Select2
    public function searchPatients(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $q        = $request->get('q');

        $patients = Patient::where('store_id', $store_id)
            ->where('status', 1)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%")
                      ->orWhere('patient_uid', 'like', "%{$q}%");
            })
            ->select('id', 'name', 'phone', 'patient_uid')
            ->limit(15)
            ->get()
            ->map(fn($p) => [
                'id'   => $p->id,
                'text' => "{$p->name} — {$p->phone} ({$p->patient_uid})",
            ]);

        return response()->json($patients);
    }

    // AJAX: search doctors for Select2
    public function searchDoctors(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $q        = $request->get('q');

        $doctors = DoctorProfile::where('store_id', $store_id)
            ->with('employee')
            ->whereHas('employee', function ($query) use ($q) {
                $query->where('f_name', 'like', "%{$q}%")
                      ->orWhere('l_name', 'like', "%{$q}%");
            })
            ->orWhere(function ($query) use ($store_id, $q) {
                $query->where('store_id', $store_id)
                      ->where('specialization', 'like', "%{$q}%");
            })
            ->limit(15)
            ->get()
            ->map(fn($d) => [
                'id'   => $d->id,
                'text' => "Dr. {$d->employee?->f_name} {$d->employee?->l_name} — {$d->specialization}",
            ]);

        return response()->json($doctors);
    }

    // AJAX: get available slots for doctor + date
    public function availableSlots(Request $request)
    {
        $request->validate([
            'doctor_profile_id' => 'required|integer',
            'date'              => 'required|date',
        ]);

        $store_id  = Helpers::get_store_id();
        $dayOfWeek = Carbon::parse($request->date)->dayOfWeek;

        $slots = DoctorSlot::where('doctor_profile_id', $request->doctor_profile_id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', 1)
            ->get();

        // Attach booking count to each slot
        $slots->each(function ($slot) use ($request) {
            $booked = Appointment::where('slot_id', $slot->id)
                ->where('appointment_date', $request->date)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->count();
            $slot->booked    = $booked;
            $slot->available = max(0, $slot->max_patients - $booked);
        });

        return response()->json($slots);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'        => 'required|integer|exists:patients,id',
            'doctor_profile_id' => 'required|integer|exists:doctor_profiles,id',
            'appointment_date'  => 'required|date',
            'appointment_time'  => 'required',
            'slot_id'           => 'nullable|integer|exists:doctor_slots,id',
            'reason'            => 'nullable|string|max:500',
        ]);

        $store_id = Helpers::get_store_id();

        // Validate slot capacity if slot selected
        if ($request->slot_id) {
            $booked = Appointment::where('slot_id', $request->slot_id)
                ->where('appointment_date', $request->appointment_date)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->count();

            $slot = DoctorSlot::findOrFail($request->slot_id);
            if ($booked >= $slot->max_patients) {
                return back()->withErrors(['slot_id' => 'This slot is fully booked.'])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $appointment = Appointment::create([
                'store_id'          => $store_id,
                'patient_id'        => $request->patient_id,
                'doctor_profile_id' => $request->doctor_profile_id,
                'slot_id'           => $request->slot_id,
                'appointment_date'  => $request->appointment_date,
                'appointment_time'  => $request->appointment_time,
                'booking_type'      => 'walk_in',
                'status'            => 'scheduled',
                'reason'            => $request->reason,
                'booked_by'         => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            ]);

            $this->generateToken($request->doctor_profile_id, $request->appointment_date, $appointment->id);

            DB::commit();

            $appointment->load(['patient', 'doctorProfile.employee']);
            $patName  = $appointment->patient?->name . ' (' . $appointment->patient?->patient_uid . ')';
            $drName   = 'Dr. ' . trim(($appointment->doctorProfile?->employee?->f_name ?? '') . ' ' . ($appointment->doctorProfile?->employee?->l_name ?? ''));
            \App\Models\HospitalActivityLog::record(
                $store_id, 'appointment', $appointment->id, 'created',
                "Appointment created for {$patName} with {$drName} on {$request->appointment_date}",
                ['doctor_profile_id' => $appointment->doctor_profile_id, 'date' => $request->appointment_date]
            );

            Toastr::success('Appointment booked successfully');
            return redirect()->route('vendor.appointment.show', $appointment->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to book appointment: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function show($id)
    {
        $store_id    = Helpers::get_store_id();
        $appointment = Appointment::where('store_id', $store_id)
            ->with(['patient', 'doctorProfile.employee', 'slot', 'token', 'rescheduledFrom'])
            ->findOrFail($id);

        $doctors = DoctorProfile::where('store_id', $store_id)->with('employee')->get();

        return view('vendor-views.appointment.show', [
            'appointment'  => $appointment,
            'nextStatuses' => Appointment::STATUS_TRANSITIONS[$appointment->status] ?? [],
            'doctors'      => $doctors,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status'        => 'required|in:' . implode(',', Appointment::STATUSES),
            'cancel_reason' => 'required_if:status,cancelled|nullable|string|max:500',
        ]);

        $store_id    = Helpers::get_store_id();
        $appointment = Appointment::where('store_id', $store_id)->findOrFail($id);

        $allowed = Appointment::STATUS_TRANSITIONS[$appointment->status] ?? [];
        if (!in_array($request->status, $allowed)) {
            Toastr::error('Invalid status transition.');
            return back();
        }

        $oldStatus = $appointment->status;
        $appointment->status        = $request->status;
        $appointment->cancel_reason = $request->cancel_reason;
        $appointment->save();

        \App\Models\HospitalActivityLog::record(
            $store_id, 'appointment', $appointment->id, 'status_changed',
            "Appointment #{$appointment->id} status changed from {$oldStatus} to {$request->status}",
            ['from' => $oldStatus, 'to' => $request->status]
        );

        Toastr::success('Status updated to ' . $request->status);
        return back();
    }

    public function reschedule(Request $request, $id)
    {
        $request->validate([
            'appointment_date' => 'required|date',
            'appointment_time' => 'required',
            'slot_id'          => 'nullable|integer|exists:doctor_slots,id',
        ]);

        $store_id    = Helpers::get_store_id();
        $old         = Appointment::where('store_id', $store_id)->findOrFail($id);

        if (in_array($old->status, ['completed', 'cancelled'])) {
            Toastr::error('Cannot reschedule a ' . $old->status . ' appointment.');
            return back();
        }

        // Validate slot capacity
        if ($request->slot_id) {
            $booked = Appointment::where('slot_id', $request->slot_id)
                ->where('appointment_date', $request->appointment_date)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->count();

            $slot = DoctorSlot::findOrFail($request->slot_id);
            if ($booked >= $slot->max_patients) {
                Toastr::error('Selected slot is fully booked.');
                return back();
            }
        }

        DB::beginTransaction();
        try {
            // Cancel old
            $old->status        = 'cancelled';
            $old->cancel_reason = 'Rescheduled';
            $old->save();

            // Create new
            $new = Appointment::create([
                'store_id'          => $store_id,
                'patient_id'        => $old->patient_id,
                'doctor_profile_id' => $old->doctor_profile_id,
                'slot_id'           => $request->slot_id,
                'appointment_date'  => $request->appointment_date,
                'appointment_time'  => $request->appointment_time,
                'booking_type'      => $old->booking_type,
                'status'            => 'scheduled',
                'reason'            => $old->reason,
                'rescheduled_from'  => $old->id,
                'booked_by'         => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            ]);

            $this->generateToken($old->doctor_profile_id, $request->appointment_date, $new->id);

            DB::commit();

            \App\Models\HospitalActivityLog::record(
                $store_id, 'appointment', $new->id, 'rescheduled',
                "Appointment #{$old->id} rescheduled to {$request->appointment_date}. New appointment #{$new->id}",
                ['old_appointment_id' => $old->id, 'from_date' => $old->appointment_date, 'to_date' => $request->appointment_date]
            );

            Toastr::success('Appointment rescheduled successfully');
            return redirect()->route('vendor.appointment.show', $new->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Reschedule failed: ' . $e->getMessage());
            return back();
        }
    }

    public function reassign(Request $request, $id)
    {
        $request->validate([
            'doctor_profile_id' => 'required|integer|exists:doctor_profiles,id',
        ]);

        $store_id    = Helpers::get_store_id();
        $appointment = Appointment::where('store_id', $store_id)->findOrFail($id);

        if (in_array($appointment->status, ['completed', 'cancelled'])) {
            Toastr::error('Cannot reassign a ' . $appointment->status . ' appointment.');
            return back();
        }

        if ($appointment->doctor_profile_id == $request->doctor_profile_id) {
            Toastr::warning('The selected doctor is already assigned to this appointment.');
            return back();
        }

        // Verify new doctor belongs to this store
        $doctor = DoctorProfile::with('employee')
            ->where('id', $request->doctor_profile_id)
            ->where('store_id', $store_id)
            ->first();

        if (!$doctor) {
            Toastr::error('Doctor not found in this store.');
            return back();
        }

        $oldDoctor = DoctorProfile::with('employee')->find($appointment->doctor_profile_id);

        $appointment->doctor_profile_id = $request->doctor_profile_id;
        $appointment->save();

        $oldDrName = 'Dr. ' . trim(($oldDoctor?->employee?->f_name ?? '') . ' ' . ($oldDoctor?->employee?->l_name ?? ''));
        $newDrName = 'Dr. ' . trim(($doctor->employee?->f_name ?? '') . ' ' . ($doctor->employee?->l_name ?? ''));
        \App\Models\HospitalActivityLog::record(
            $store_id, 'appointment', $appointment->id, 'reassigned',
            "Appointment #{$appointment->id} doctor reassigned from {$oldDrName} to {$newDrName}",
            ['from_doctor_id' => $oldDoctor?->id, 'to_doctor_id' => $doctor->id]
        );

        Toastr::success('Doctor reassigned successfully.');
        return back();
    }

    // AJAX: look up a service_request by ID for the "already booked" flow
    public function lookupLead(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $srId     = (int) $request->get('id');

        $sr = ServiceRequest::find($srId);

        if (!$sr) {
            return response()->json(['error' => 'Appointment ID not found.'], 404);
        }

        // Verify this lead was sent to this store
        $sentTo = array_map('intval', array_filter(explode(',', $sr->sent_to ?? '')));
        if (!in_array((int)$store_id, $sentTo)) {
            return response()->json(['error' => 'This appointment does not belong to your store.'], 403);
        }

        $user   = User::find($sr->user_id);
        $doctor = $sr->preferred_doctor_id
            ? DoctorProfile::with('employee')->find($sr->preferred_doctor_id)
            : null;
        $slot   = $sr->preferred_slot_id
            ? DoctorSlot::find($sr->preferred_slot_id)
            : null;

        $isOther     = $sr->patient_for === 'other' && $sr->patient_name;
        $patientName  = $isOther ? $sr->patient_name : ($user ? (trim(($user->f_name ?? '') . ' ' . ($user->l_name ?? '')) ?: $user->name) : 'Unknown');
        $patientPhone = $isOther ? $sr->patient_phone : $user?->phone;

        return response()->json([
            'patient_name'       => $patientName,
            'patient_phone'      => $patientPhone,
            'doctor_name'        => $doctor ? 'Dr. ' . $doctor->employee?->f_name . ' ' . $doctor->employee?->l_name : null,
            'doctor_profile_id'  => $sr->preferred_doctor_id,
            'appointment_date'   => $sr->preferred_date,
            'slot_id'            => $sr->preferred_slot_id,
            'slot_label'         => $slot ? $this->fmtTime($slot->slot_start) . ' – ' . $this->fmtTime($slot->slot_end) : null,
            'appointment_time'   => $sr->preferred_time,
            'reason'             => $sr->requirements,
            'service_name'       => $sr->item?->name,
            'status'             => $sr->status,
        ]);
    }

    // POST: create appointment from an existing service_request (online lead)
    public function storeFromLead(Request $request)
    {
        $request->validate(['service_request_id' => 'required|integer']);

        $store_id = Helpers::get_store_id();
        $sr       = ServiceRequest::findOrFail($request->service_request_id);

        $sentTo = array_map('intval', array_filter(explode(',', $sr->sent_to ?? '')));
        if (!in_array((int)$store_id, $sentTo)) {
            Toastr::error('This appointment does not belong to your store.');
            return back();
        }

        if (!$sr->preferred_doctor_id || !$sr->preferred_date) {
            Toastr::error('This booking has no preferred doctor or date. Please register as walk-in.');
            return back();
        }

        // Validate slot capacity if slot was chosen
        if ($sr->preferred_slot_id) {
            $booked = Appointment::where('slot_id', $sr->preferred_slot_id)
                ->where('appointment_date', $sr->preferred_date)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->count();
            $slot = DoctorSlot::find($sr->preferred_slot_id);
            if ($slot && $booked >= $slot->max_patients) {
                Toastr::error('The originally booked slot is now full. Please register as walk-in and pick another slot.');
                return back();
            }
        }

        // Silently find or create patient from the user who made the booking
        $patient = $this->resolvePatientFromLead($sr, $store_id);

        DB::beginTransaction();
        try {
            $appointment = Appointment::create([
                'store_id'          => $store_id,
                'patient_id'        => $patient->id,
                'doctor_profile_id' => $sr->preferred_doctor_id,
                'slot_id'           => $sr->preferred_slot_id ?: null,
                'appointment_date'  => $sr->preferred_date,
                'appointment_time'  => $sr->preferred_time ?: '00:00',
                'booking_type'      => 'online',
                'status'            => 'scheduled',
                'reason'            => $sr->requirements,
                'booked_by'         => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            ]);

            $this->generateToken($sr->preferred_doctor_id, $sr->preferred_date, $appointment->id);

            DB::commit();
            Toastr::success('Appointment registered successfully.');
            return redirect()->route('vendor.appointment.show', $appointment->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed: ' . $e->getMessage());
            return back();
        }
    }

    private function resolvePatientFromLead(ServiceRequest $sr, int $storeId): Patient
    {
        $isOther = $sr->patient_for === 'other' && $sr->patient_name;
        $user    = User::find($sr->user_id);

        if ($isOther) {
            // "Someone else" — create a standalone patient record (no user_id link)
            $last = Patient::where('store_id', $storeId)->latest('id')->first();
            $uid  = 'P-' . str_pad(($last ? $last->id + 1 : 1), 5, '0', STR_PAD_LEFT);
            return Patient::create([
                'store_id'    => $storeId,
                'user_id'     => null,
                'patient_uid' => $uid,
                'name'        => $sr->patient_name,
                'phone'       => $sr->patient_phone,
                'email'       => null,
                'status'      => 1,
            ]);
        }

        if ($user) {
            $patient = Patient::where('user_id', $user->id)->where('store_id', $storeId)->first();
            if ($patient) return $patient;

            $last = Patient::where('store_id', $storeId)->latest('id')->first();
            $uid  = 'P-' . str_pad(($last ? $last->id + 1 : 1), 5, '0', STR_PAD_LEFT);
            return Patient::create([
                'store_id'    => $storeId,
                'user_id'     => $user->id,
                'patient_uid' => $uid,
                'name'        => trim(($user->f_name ?? '') . ' ' . ($user->l_name ?? '')) ?: ($user->name ?? 'Patient'),
                'phone'       => $user->phone,
                'email'       => $user->email,
                'status'      => 1,
            ]);
        }

        $last = Patient::where('store_id', $storeId)->latest('id')->first();
        $uid  = 'P-' . str_pad(($last ? $last->id + 1 : 1), 5, '0', STR_PAD_LEFT);
        return Patient::create([
            'store_id'    => $storeId,
            'user_id'     => null,
            'patient_uid' => $uid,
            'name'        => 'Patient',
            'phone'       => null,
            'email'       => null,
            'status'      => 1,
        ]);
    }

    private function fmtTime(string $t): string
    {
        [$h, $m] = explode(':', $t);
        $hr = (int)$h;
        return ($hr > 12 ? $hr - 12 : ($hr ?: 12)) . ':' . $m . ' ' . ($hr >= 12 ? 'PM' : 'AM');
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
}
