<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\DoctorSlot;
use App\Models\EmployeeRole;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index()
    {
        $store_id = Helpers::get_store_id();
        $doctors  = DoctorProfile::where('vendor_id', $store_id)
            ->with('employee')
            ->latest()
            ->paginate(15);

        return view('vendor-views.doctor.list', compact('doctors'));
    }

    public function create()
    {
        $store_id  = Helpers::get_store_id();

        // Employees not already assigned a doctor profile
        $assigned_emp_ids = DoctorProfile::where('vendor_id', $store_id)->pluck('emp_id');
        $employees = VendorEmployee::where('store_id', $store_id)
            ->where('status', 1)
            ->whereNotIn('id', $assigned_emp_ids)
            ->get();

        return view('vendor-views.doctor.add', compact('employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'emp_id'          => 'required|exists:vendor_employees,id',
            'specialization'  => 'required|string|max:150',
            'consultation_fee'=> 'nullable|numeric|min:0',
            'available_from'  => 'nullable|date_format:H:i',
            'available_to'    => 'nullable|date_format:H:i',
        ]);

        $store_id = Helpers::get_store_id();

        DoctorProfile::create([
            'emp_id'              => $request->emp_id,
            'vendor_id'           => $store_id,
            'specialization'      => $request->specialization,
            'qualification'       => $request->qualification,
            'registration_number' => $request->registration_number,
            'department'          => $request->department,
            'opd_room'            => $request->opd_room,
            'consultation_fee'    => $request->consultation_fee ?? 0,
            'available_days'      => $request->available_days ? implode(',', $request->available_days) : null,
            'available_from'      => $request->available_from,
            'available_to'        => $request->available_to,
            'bio'                 => $request->bio,
        ]);

        // Auto-assign the Doctor role to this employee
        $doctorRole = EmployeeRole::where('store_id', $store_id)->where('name', 'Doctor')->first();
        if ($doctorRole) {
            VendorEmployee::where('id', $request->emp_id)->update(['role_id' => $doctorRole->id]);
        }

        Toastr::success('Doctor profile created successfully');
        return redirect()->route('vendor.doctor.list');
    }

    public function edit($id)
    {
        $store_id = Helpers::get_store_id();
        $doctor   = DoctorProfile::where('vendor_id', $store_id)->with('employee', 'slots')->findOrFail($id);

        $employees = VendorEmployee::where('store_id', $store_id)
            ->where('status', 1)
            ->where(function ($q) use ($doctor) {
                $q->where('id', $doctor->emp_id)
                  ->orWhereNotIn('id', DoctorProfile::where('vendor_id', $doctor->vendor_id)->pluck('emp_id'));
            })
            ->get();

        $days = DoctorSlot::DAYS;

        return view('vendor-views.doctor.edit', compact('doctor', 'employees', 'days'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'emp_id'          => 'required|exists:vendor_employees,id',
            'specialization'  => 'required|string|max:150',
            'consultation_fee'=> 'nullable|numeric|min:0',
            'available_from'  => 'nullable|date_format:H:i',
            'available_to'    => 'nullable|date_format:H:i',
        ]);

        $store_id = Helpers::get_store_id();
        $doctor   = DoctorProfile::where('vendor_id', $store_id)->findOrFail($id);

        $doctor->update([
            'emp_id'              => $request->emp_id,
            'specialization'      => $request->specialization,
            'qualification'       => $request->qualification,
            'registration_number' => $request->registration_number,
            'department'          => $request->department,
            'opd_room'            => $request->opd_room,
            'consultation_fee'    => $request->consultation_fee ?? 0,
            'available_days'      => $request->available_days ? implode(',', $request->available_days) : null,
            'available_from'      => $request->available_from,
            'available_to'        => $request->available_to,
            'bio'                 => $request->bio,
        ]);

        Toastr::success('Doctor profile updated successfully');
        return redirect()->route('vendor.doctor.list');
    }

    public function destroy($id)
    {
        $store_id = Helpers::get_store_id();
        DoctorProfile::where('vendor_id', $store_id)->findOrFail($id)->delete();

        Toastr::success('Doctor profile deleted');
        return redirect()->route('vendor.doctor.list');
    }

    // ── Slots ──────────────────────────────────────────────

    public function slots($id)
    {
        $store_id = Helpers::get_store_id();
        $doctor   = DoctorProfile::where('vendor_id', $store_id)->with('employee', 'slots')->findOrFail($id);
        $days     = DoctorSlot::DAYS;

        return view('vendor-views.doctor.slots', compact('doctor', 'days'));
    }

    public function slotStore(Request $request, $id)
    {
        $request->validate([
            'day_of_week'           => 'required|integer|between:0,6',
            'slot_start'            => 'required|date_format:H:i',
            'slot_end'              => 'required|date_format:H:i|after:slot_start',
            'slot_duration_minutes' => 'required|integer|min:5',
            'max_patients'          => 'required|integer|min:1',
        ]);

        $store_id = Helpers::get_store_id();
        $doctor   = DoctorProfile::where('vendor_id', $store_id)->findOrFail($id);

        DoctorSlot::create([
            'doctor_profile_id'     => $doctor->id,
            'day_of_week'           => $request->day_of_week,
            'slot_start'            => $request->slot_start,
            'slot_end'              => $request->slot_end,
            'slot_duration_minutes' => $request->slot_duration_minutes,
            'max_patients'          => $request->max_patients,
            'is_active'             => 1,
        ]);

        Toastr::success('Slot added');
        return back();
    }

    public function slotToggle($id, $slot_id)
    {
        $store_id = Helpers::get_store_id();
        $doctor   = DoctorProfile::where('vendor_id', $store_id)->findOrFail($id);
        $slot     = DoctorSlot::where('doctor_profile_id', $doctor->id)->findOrFail($slot_id);
        $slot->is_active = !$slot->is_active;
        $slot->save();

        Toastr::success('Slot updated');
        return back();
    }

    public function slotDestroy($id, $slot_id)
    {
        $store_id = Helpers::get_store_id();
        $doctor   = DoctorProfile::where('vendor_id', $store_id)->findOrFail($id);
        DoctorSlot::where('doctor_profile_id', $doctor->id)->findOrFail($slot_id)->delete();

        Toastr::success('Slot deleted');
        return back();
    }
}
