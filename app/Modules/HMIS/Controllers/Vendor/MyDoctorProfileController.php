<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\DoctorSlot;
use App\Models\IpdAdmission;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class MyDoctorProfileController extends Controller
{
    private function getMyProfile(): ?DoctorProfile
    {
        $emp_id   = auth('vendor_employee')->id();
        $store_id = Helpers::get_store_id();
        return DoctorProfile::where('emp_id', $emp_id)
            ->where('store_id', $store_id)
            ->with('services', 'slots')
            ->first();
    }

    // The doctor's own admitted (IPD) patients, with nursing notes + diet charts.
    public function patients()
    {
        $doctor = $this->getMyProfile();
        if (!$doctor) {
            Toastr::warning('No doctor profile is linked to your account.');
            return redirect()->route('vendor.dashboard');
        }

        IpdAdmission::ensureNurseAssignTable();
        $admissions = IpdAdmission::where('store_id', Helpers::get_store_id())
            ->where('doctor_profile_id', $doctor->id)
            ->with(['patient', 'ward', 'bed', 'assignedNurses.employee', 'nursingNotes.recorder', 'dietCharts.recorder'])
            ->orderByRaw("status = 'active' desc")
            ->orderByDesc('admission_date')
            ->get();

        return view('hmis::vendor.hospital.my_patients', [
            'admissions' => $admissions,
            'heading'    => 'My Admitted Patients',
            'role'       => 'doctor',
        ]);
    }

    public function edit()
    {
        $doctor = $this->getMyProfile();
        if (!$doctor) {
            Toastr::warning('No doctor profile is linked to your account.');
            return redirect()->route('vendor.dashboard');
        }

        $store_id       = Helpers::get_store_id();
        $serviceIds     = Helpers::store_item_ids($store_id);
        $store_services = \App\Models\Item::withoutGlobalScopes()->whereIn('id', $serviceIds)->select('id', 'name')->get();
        $days           = DoctorSlot::DAYS;

        return view('hmis::vendor.hospital.my_doctor_profile', compact('doctor', 'store_services', 'days'));
    }

    public function update(Request $request)
    {
        $doctor = $this->getMyProfile();
        if (!$doctor) abort(403);

        $request->validate([
            'consultation_fee' => 'nullable|numeric|min:0',
            'bio'              => 'nullable|string|max:1000',
        ]);

        $doctor->consultation_fee = $request->consultation_fee ?? $doctor->consultation_fee;
        $doctor->bio              = $request->bio;
        $doctor->save();

        \App\Models\DoctorService::where('doctor_profile_id', $doctor->id)->delete();
        foreach (array_filter((array) $request->input('services', [])) as $itemId) {
            \App\Models\DoctorService::create([
                'doctor_profile_id' => $doctor->id,
                'item_id'           => $itemId,
            ]);
        }

        Toastr::success('Profile updated.');
        return back();
    }

    public function slotStore(Request $request)
    {
        $doctor = $this->getMyProfile();
        if (!$doctor) abort(403);

        $request->validate([
            'days_of_week'          => 'required|array|min:1',
            'days_of_week.*'        => 'integer|between:0,6',
            'slot_start'            => 'required|date_format:H:i',
            'slot_end'              => 'required|date_format:H:i|after:slot_start',
            'slot_duration_minutes' => 'required|integer|min:5',
            'max_patients'          => 'required|integer|min:1',
        ]);

        foreach ($request->days_of_week as $day) {
            DoctorSlot::create([
                'doctor_profile_id'     => $doctor->id,
                'day_of_week'           => $day,
                'slot_start'            => $request->slot_start,
                'slot_end'              => $request->slot_end,
                'slot_duration_minutes' => $request->slot_duration_minutes,
                'max_patients'          => $request->max_patients,
                'is_active'             => 1,
            ]);
        }

        $count = count($request->days_of_week);
        Toastr::success($count === 1 ? 'Slot added.' : "{$count} slots added.");
        return back();
    }

    public function slotToggle($slot_id)
    {
        $doctor = $this->getMyProfile();
        if (!$doctor) abort(403);

        $slot = DoctorSlot::where('doctor_profile_id', $doctor->id)->findOrFail($slot_id);
        $slot->is_active = !$slot->is_active;
        $slot->save();

        Toastr::success('Slot updated.');
        return back();
    }

    public function slotDestroy($slot_id)
    {
        $doctor = $this->getMyProfile();
        if (!$doctor) abort(403);

        DoctorSlot::where('doctor_profile_id', $doctor->id)->findOrFail($slot_id)->delete();

        Toastr::success('Slot deleted.');
        return back();
    }

    public function slotClone(Request $request, $slot_id)
    {
        $doctor = $this->getMyProfile();
        if (!$doctor) abort(403);

        $request->validate([
            'days_of_week'   => 'required|array|min:1',
            'days_of_week.*' => 'integer|between:0,6',
        ]);

        $source = DoctorSlot::where('doctor_profile_id', $doctor->id)->findOrFail($slot_id);

        foreach ($request->days_of_week as $day) {
            DoctorSlot::create([
                'doctor_profile_id'     => $doctor->id,
                'day_of_week'           => $day,
                'slot_start'            => $source->slot_start,
                'slot_end'              => $source->slot_end,
                'slot_duration_minutes' => $source->slot_duration_minutes,
                'max_patients'          => $source->max_patients,
                'is_active'             => 1,
            ]);
        }

        $count = count($request->days_of_week);
        Toastr::success("Slot cloned to {$count} " . ($count === 1 ? 'day.' : 'days.'));
        return back();
    }
}
