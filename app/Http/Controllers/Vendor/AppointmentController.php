<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentToken;
use App\Models\DoctorProfile;
use App\Models\DoctorSlot;
use App\Models\Patient;
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

        return view('vendor-views.appointment.show', [
            'appointment'  => $appointment,
            'nextStatuses' => Appointment::STATUS_TRANSITIONS[$appointment->status] ?? [],
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

        $appointment->status        = $request->status;
        $appointment->cancel_reason = $request->cancel_reason;
        $appointment->save();

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
            Toastr::success('Appointment rescheduled successfully');
            return redirect()->route('vendor.appointment.show', $new->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Reschedule failed: ' . $e->getMessage());
            return back();
        }
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
