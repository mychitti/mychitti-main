<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\DoctorSlot;
use App\Models\EmployeeRole;
use App\Models\Item;
use App\Models\Store;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DoctorController extends Controller
{
    public function index()
    {
        $store_id = Helpers::get_store_id();
        $doctors  = DoctorProfile::where('store_id', $store_id)
            ->with('employee')
            ->latest()
            ->paginate(15);

        return view('vendor-views.doctor.list', compact('doctors'));
    }

    public function create()
    {
        if (!auth('vendor')->check() && !hasPermission('staff_doctor', 'add')) abort(403);
        $store_id  = Helpers::get_store_id();

        // Employees not already assigned a doctor profile
        $assigned_emp_ids = DoctorProfile::where('store_id', $store_id)->pluck('emp_id');
        $employees = VendorEmployee::where('store_id', $store_id)
            ->where('status', 1)
            ->whereNotIn('id', $assigned_emp_ids)
            ->get();

        $store_services = $this->getStoreServices($store_id);

        return view('vendor-views.doctor.add', compact('employees', 'store_services'));
    }

    public function store(Request $request)
    {
        // emp_id is optional — if missing, we create a new staff member
        $rules = [
            'specialization'  => 'required|string|max:150',
            'consultation_fee' => 'nullable|numeric|min:0',
            'available_from'  => 'nullable|date_format:H:i,H:i:s',
            'available_to'    => 'nullable|date_format:H:i,H:i:s',
        ];

        if ($request->filled('emp_id')) {
            $rules['emp_id'] = 'exists:vendor_employees,id';
        } else {
            $rules['new_f_name'] = 'required|string|max:100';
            $rules['new_l_name'] = 'nullable|string|max:100';
            $rules['new_email']  = 'required|email|unique:vendor_employees,email';
            $rules['new_phone']  = 'nullable|string|max:20';
        }

        $request->validate($rules);

        $store_id  = Helpers::get_store_id();
        $vendor_id = Helpers::get_vendor_id();

        DB::beginTransaction();
        try {
            // Resolve or create the employee
            if ($request->filled('emp_id')) {
                $empId = $request->emp_id;
            } else {
                $emp           = new VendorEmployee();
                $emp->f_name   = $request->new_f_name;
                $emp->l_name   = $request->new_l_name;
                $emp->email    = $request->new_email;
                $emp->phone    = $request->new_phone;
                $emp->store_id = $store_id;
                $emp->vendor_id = $vendor_id;
                $emp->status   = 1;
                $emp->password = bcrypt(Str::random(16));
                $emp->save();
                $empId = $emp->id;
            }

            $doctor = DoctorProfile::create([
                'emp_id'              => $empId,
                'store_id'            => $store_id,
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

            // Save services
            $this->syncServices($doctor->id, $request->input('services', []));

            // Ensure Doctor role exists and assign it
            $doctorRole = EmployeeRole::firstOrCreate(
                ['store_id' => $store_id, 'name' => 'Doctor'],
                ['status' => 1]
            );
            VendorEmployee::where('id', $empId)->update(['employee_role_id' => $doctorRole->id]);

            DB::commit();

            $doctor->load('employee');
            $drName = 'Dr. ' . trim(($doctor->employee?->f_name ?? '') . ' ' . ($doctor->employee?->l_name ?? ''));
            \App\Models\HospitalActivityLog::record(
                $store_id, 'doctor', $doctor->id, 'created',
                "Doctor profile created: {$drName} ({$doctor->specialization})"
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to create doctor: ' . $e->getMessage());
            return back()->withInput();
        }

        Toastr::success('Doctor profile created successfully');
        return redirect()->route('vendor.doctor.list');
    }

    public function edit($id)
    {
        if (!auth('vendor')->check() && !hasPermission('staff_doctor', 'edit')) abort(403);
        $store_id = Helpers::get_store_id();
        $doctor   = DoctorProfile::with('services')->where('store_id', $store_id)->with('employee', 'slots')->findOrFail($id);

        $store_services = $this->getStoreServices($store_id);

        $employees = VendorEmployee::where('store_id', $store_id)
            ->where('status', 1)
            ->where(function ($q) use ($doctor) {
                $q->where('id', $doctor->emp_id)
                    ->orWhereNotIn('id', DoctorProfile::where('store_id', $doctor->store_id)->pluck('emp_id'));
            })
            ->get();

        $days = DoctorSlot::DAYS;

        return view('vendor-views.doctor.edit', compact('doctor', 'employees', 'days', 'store_services'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'emp_id'           => 'required|exists:vendor_employees,id',
            'specialization'   => 'required|string|max:150',
            'consultation_fee' => 'nullable|numeric|min:0',
            'available_from'   => 'nullable|date_format:H:i,H:i:s',
            'available_to'     => 'nullable|date_format:H:i,H:i:s',
        ]);

        $store_id = Helpers::get_store_id();
        $doctor   = DoctorProfile::where('store_id', $store_id)->findOrFail($id);

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

        $this->syncServices($doctor->id, $request->input('services', []));

        $doctor->load('employee');
        $drName = 'Dr. ' . trim(($doctor->employee?->f_name ?? '') . ' ' . ($doctor->employee?->l_name ?? ''));
        \App\Models\HospitalActivityLog::record(
            $store_id, 'doctor', $doctor->id, 'updated',
            "Doctor profile updated: {$drName}"
        );

        Toastr::success('Doctor profile updated successfully');
        return redirect()->route('vendor.doctor.list');
    }

    public function destroy($id)
    {
        if (!auth('vendor')->check() && !hasPermission('staff_doctor', 'delete')) abort(403);
        $store_id = Helpers::get_store_id();
        DoctorProfile::where('store_id', $store_id)->findOrFail($id)->delete();

        Toastr::success('Doctor profile deleted');
        return redirect()->route('vendor.doctor.list');
    }

    public function export()
    {
        if (!auth('vendor')->check() && !hasPermission('staff_doctor', 'export')) abort(403);
        $store_id = Helpers::get_store_id();
        $doctors  = DoctorProfile::where('store_id', $store_id)->with('employee', 'services')->get();

        $headings = ['#', 'Name', 'Email', 'Phone', 'Specialization', 'Qualification', 'Department', 'OPD Room', 'Fee', 'Available Days'];
        $data = $doctors->map(fn($d) => [
            $d->id,
            'Dr. ' . trim(($d->employee?->f_name ?? '') . ' ' . ($d->employee?->l_name ?? '')),
            $d->employee?->email,
            $d->employee?->phone,
            $d->specialization,
            $d->qualification,
            $d->department,
            $d->opd_room,
            $d->consultation_fee,
            $d->available_days,
        ])->toArray();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($data, $headings),
            'doctors_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    // ── Helpers ────────────────────────────────────────────

    private function getStoreServices(int $storeId): \Illuminate\Support\Collection
    {
        $store = Store::find($storeId);
        $ids   = array_filter(array_unique(array_merge(
            explode(',', $store->services_1 ?? ''),
            explode(',', $store->services_2 ?? '')
        )));
        return Item::withoutGlobalScopes()->whereIn('id', $ids)->select('id', 'name')->get();
    }

    private function syncServices(int $doctorProfileId, array $serviceIds): void
    {
        \App\Models\DoctorService::where('doctor_profile_id', $doctorProfileId)->delete();
        foreach (array_filter($serviceIds) as $itemId) {
            \App\Models\DoctorService::create([
                'doctor_profile_id' => $doctorProfileId,
                'item_id'           => $itemId,
            ]);
        }
    }

    // ── Slots ──────────────────────────────────────────────

    public function slots($id)
    {
        if (!auth('vendor')->check() && !hasPermission('staff_doctor', 'slots')) abort(403);
        $store_id = Helpers::get_store_id();
        $doctor   = DoctorProfile::where('store_id', $store_id)->with('employee', 'slots')->findOrFail($id);
        $days     = DoctorSlot::DAYS;

        return view('vendor-views.doctor.slots', compact('doctor', 'days'));
    }

    public function slotStore(Request $request, $id)
    {
        $request->validate([
            'days_of_week'          => 'required|array|min:1',
            'days_of_week.*'        => 'integer|between:0,6',
            'slot_start'            => 'required|date_format:H:i',
            'slot_end'              => 'required|date_format:H:i|after:slot_start',
            'slot_duration_minutes' => 'required|integer|min:5',
            'max_patients'          => 'required|integer|min:1',
        ]);

        $store_id = Helpers::get_store_id();
        $doctor   = DoctorProfile::where('store_id', $store_id)->findOrFail($id);

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

        $doctor->load('employee');
        $drName  = 'Dr. ' . trim(($doctor->employee?->f_name ?? '') . ' ' . ($doctor->employee?->l_name ?? ''));
        $dayNames = array_map(fn($d) => DoctorSlot::DAYS[$d], $request->days_of_week);
        \App\Models\HospitalActivityLog::record(
            $store_id, 'slot', null, 'created',
            "Slot(s) added for {$drName}: " . implode(', ', $dayNames) . " {$request->slot_start}–{$request->slot_end}"
        );

        $count = count($request->days_of_week);
        Toastr::success($count === 1 ? 'Slot added' : "{$count} slots added");
        return back();
    }

    public function slotClone(Request $request, $id, $slot_id)
    {
        $request->validate([
            'days_of_week'   => 'required|array|min:1',
            'days_of_week.*' => 'integer|between:0,6',
        ]);

        $store_id = Helpers::get_store_id();
        $doctor   = DoctorProfile::where('store_id', $store_id)->findOrFail($id);
        $source   = DoctorSlot::where('doctor_profile_id', $doctor->id)->findOrFail($slot_id);

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
        Toastr::success("Slot cloned to {$count} " . ($count === 1 ? 'day' : 'days'));
        return back();
    }

    public function slotToggle($id, $slot_id)
    {
        $store_id = Helpers::get_store_id();
        $doctor   = DoctorProfile::where('store_id', $store_id)->findOrFail($id);
        $slot     = DoctorSlot::where('doctor_profile_id', $doctor->id)->findOrFail($slot_id);
        $slot->is_active = !$slot->is_active;
        $slot->save();

        $state = $slot->is_active ? 'activated' : 'deactivated';
        \App\Models\HospitalActivityLog::record(
            $store_id, 'slot', $slot->id, 'toggled',
            "Slot #{$slot->id} {$state} ({$slot->slot_start}–{$slot->slot_end})"
        );

        Toastr::success('Slot updated');
        return back();
    }

    public function slotDestroy($id, $slot_id)
    {
        $store_id = Helpers::get_store_id();
        $doctor   = DoctorProfile::where('store_id', $store_id)->findOrFail($id);
        $slot     = DoctorSlot::where('doctor_profile_id', $doctor->id)->findOrFail($slot_id);
        $slotDesc = "{$slot->slot_start}–{$slot->slot_end} (" . (DoctorSlot::DAYS[$slot->day_of_week] ?? "Day {$slot->day_of_week}") . ")";
        $slot->delete();

        \App\Models\HospitalActivityLog::record(
            $store_id, 'slot', $slot_id, 'deleted',
            "Slot #{$slot_id} deleted: {$slotDesc}"
        );

        Toastr::success('Slot deleted');
        return back();
    }
}
