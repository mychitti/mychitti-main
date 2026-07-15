<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\EmployeeRole;
use App\Models\NurseProfile;
use App\Models\VendorEmployee;
use App\Models\Ward;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class NurseController extends Controller
{
    public function index()
    {
        $store_id = Helpers::get_store_id();
        $nurses   = NurseProfile::where('store_id', $store_id)
            ->with(['employee.storeShift', 'ward'])
            ->latest()
            ->paginate(15);

        return view('hmis::vendor.nurse.list', compact('nurses'));
    }

    public function export()
    {
        if (!auth('vendor')->check() && !hasPermission('staff_nurse', 'export')) abort(403);
        $store_id = Helpers::get_store_id();
        $nurses   = NurseProfile::where('store_id', $store_id)->with(['employee', 'ward'])->get();

        $headings = ['#', 'Name', 'Email', 'Phone', 'Qualification', 'Shift', 'Ward', 'Status'];
        $data = $nurses->map(fn($n) => [
            $n->id,
            trim(($n->employee?->f_name ?? '') . ' ' . ($n->employee?->l_name ?? '')),
            $n->employee?->email,
            $n->employee?->phone,
            $n->qualification,
            $n->employee?->storeShift?->name,
            $n->ward?->ward_name,
            $n->employee?->status ? 'Active' : 'Inactive',
        ])->toArray();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($data, $headings),
            'nurses_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function create()
    {
        $store_id = Helpers::get_store_id();

        $takenEmpIds = NurseProfile::where('store_id', $store_id)->pluck('emp_id')
            ->merge(DoctorProfile::where('store_id', $store_id)->pluck('emp_id'))
            ->unique();
        $employees = VendorEmployee::where('store_id', $store_id)
            ->where('status', 1)
            ->whereNotIn('id', $takenEmpIds)
            ->get();

        $wards = Ward::where('store_id', $store_id)->where('is_active', true)->orderBy('ward_name')->get();

        return view('hmis::vendor.nurse.add', compact('employees', 'wards'));
    }

    public function store(Request $request)
    {
        $rules = [
            'qualification' => 'nullable|string|max:150',
            'ward_id'       => 'nullable|exists:wards,id',
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
            if ($request->filled('emp_id')) {
                $empId = $request->emp_id;
            } else {
                $emp            = new VendorEmployee();
                $emp->f_name    = $request->new_f_name;
                $emp->l_name    = $request->new_l_name;
                $emp->email     = $request->new_email;
                $emp->phone     = $request->new_phone;
                $emp->store_id  = $store_id;
                $emp->vendor_id = $vendor_id;
                $emp->status    = 1;
                $emp->password  = bcrypt(Str::random(16));
                $emp->employee_id = _newEmpId(true);
                $emp->save();
                $empId = $emp->id;
            }

            NurseProfile::create([
                'store_id'            => $store_id,
                'emp_id'              => $empId,
                'qualification'       => $request->qualification,
                'ward_id'             => $request->ward_id ?: null,
                'department'          => $request->department,
                'registration_number' => $request->registration_number,
                'notes'               => $request->notes,
            ]);

            $nurseRole = EmployeeRole::firstOrCreate(
                ['store_id' => $store_id, 'name' => 'Nurse'],
                ['status' => 1]
            );
            VendorEmployee::where('id', $empId)->update(['employee_role_id' => $nurseRole->id]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to create nurse: ' . $e->getMessage());
            return back()->withInput();
        }

        Toastr::success('Nurse profile created successfully.');
        return Redirect::route('vendor.nurse.list');
    }

    public function show($id)
    {
        if (!auth('vendor')->check() && !hasPermission('staff_nurse', 'view')) abort(403);
        $store_id = Helpers::get_store_id();
        $nurse    = NurseProfile::where('store_id', $store_id)
            ->with(['employee.storeShift', 'ward'])
            ->findOrFail($id);

        $duty = \App\Models\Attendance::dutySummary($nurse->employee, $store_id);
        $dutyHistory = \App\Models\Attendance::dutyHistory($nurse->employee, $store_id);

        return view('hmis::vendor.nurse.view', compact('nurse', 'duty', 'dutyHistory'));
    }

    public function edit($id)
    {
        if (!auth('vendor')->check() && !hasPermission('staff_nurse', 'edit')) abort(403);
        $store_id = Helpers::get_store_id();
        $nurse    = NurseProfile::where('store_id', $store_id)->with('employee', 'ward')->findOrFail($id);

        $takenEmpIds = NurseProfile::where('store_id', $store_id)->pluck('emp_id')
            ->merge(DoctorProfile::where('store_id', $store_id)->pluck('emp_id'))
            ->unique();
        $employees = VendorEmployee::where('store_id', $store_id)
            ->where('status', 1)
            ->where(function ($q) use ($nurse, $takenEmpIds) {
                $q->where('id', $nurse->emp_id)
                  ->orWhereNotIn('id', $takenEmpIds);
            })
            ->get();

        $wards = Ward::where('store_id', $store_id)->where('is_active', true)->orderBy('ward_name')->get();

        return view('hmis::vendor.nurse.edit', compact('nurse', 'employees', 'wards'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'emp_id'        => 'required|exists:vendor_employees,id',
            'qualification' => 'nullable|string|max:150',
            'ward_id'       => 'nullable|exists:wards,id',
        ]);

        $store_id = Helpers::get_store_id();
        $nurse    = NurseProfile::where('store_id', $store_id)->findOrFail($id);

        $nurse->update([
            'emp_id'              => $request->emp_id,
            'qualification'       => $request->qualification,
            'ward_id'             => $request->ward_id ?: null,
            'department'          => $request->department,
            'registration_number' => $request->registration_number,
            'notes'               => $request->notes,
        ]);

        Toastr::success('Nurse profile updated successfully.');
        return Redirect::route('vendor.nurse.list');
    }

    public function destroy($id)
    {
        if (!auth('vendor')->check() && !hasPermission('staff_nurse', 'delete')) abort(403);
        $store_id = Helpers::get_store_id();
        NurseProfile::where('store_id', $store_id)->findOrFail($id)->delete();

        Toastr::success('Nurse profile deleted.');
        return Redirect::route('vendor.nurse.list');
    }
}
