<?php

namespace App\Modules\Laundry\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\CentralLogics\Helpers;
use App\Models\EmployeeRole;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BasicStaffController extends Controller
{
    const MAX_STAFF = 10;

    // ─── Staff ───────────────────────────────────────────────────

    public function index()
    {
        $staff = VendorEmployee::where('store_id', Helpers::get_store_id())
            ->select('id', 'f_name', 'l_name', 'phone', 'email', 'image', 'id_document', 'employee_role_id', 'status')
            ->latest()
            ->get();

        $count = $staff->count();

        return view('laundry::vendor.basic-staff.index', compact('staff', 'count'));
    }

    public function create()
    {
        $count = VendorEmployee::where('store_id', Helpers::get_store_id())->count();

        if ($count >= self::MAX_STAFF) {
            Toastr::warning('Free plan allows up to ' . self::MAX_STAFF . ' staff members. Please upgrade to HR Management to add more.');
            return redirect()->route('vendor.basic-staff.index');
        }

        $roles = EmployeeRole::where('store_id', Helpers::get_store_id())->where('status', 1)->get();

        return view('laundry::vendor.basic-staff.form', compact('roles'));
    }

    public function store(Request $request)
    {
        $count = VendorEmployee::where('store_id', Helpers::get_store_id())->count();

        if ($count >= self::MAX_STAFF) {
            Toastr::error('Free plan limit reached (' . self::MAX_STAFF . ' members). Upgrade to HR Management to add more.');
            return back();
        }

        $request->validate([
            'f_name'   => 'required|string|max:100',
            'phone'    => 'required|regex:/^[0-9\s\-\+\(\)]{7,20}$/|unique:vendor_employees,phone',
            'email'    => 'required|email|unique:vendor_employees,email',
            'image'    => 'nullable|image|max:2048',
            'id_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        $emp = new VendorEmployee();
        $emp->f_name            = $request->f_name;
        $emp->l_name            = $request->l_name;
        $emp->phone             = $request->phone;
        $emp->email             = $request->email;
        $emp->vendor_id         = Helpers::get_vendor_id();
        $emp->store_id          = Helpers::get_store_id();
        $emp->employee_role_id  = $request->employee_role_id ?: null;
        $emp->status            = 1;

        if ($request->hasFile('image')) {
            $emp->image = Helpers::upload('profile/', 'png', $request->file('image'));
        }

        if ($request->hasFile('id_proof')) {
            $emp->id_document = Helpers::upload('employee/id-proof/', $request->file('id_proof')->getClientOriginalExtension(), $request->file('id_proof'));
        }

        $emp->save();

        Toastr::success('Staff member added successfully.');
        return redirect()->route('vendor.basic-staff.index');
    }

    public function edit($id)
    {
        $member = VendorEmployee::where('store_id', Helpers::get_store_id())->findOrFail($id);
        $roles  = EmployeeRole::where('store_id', Helpers::get_store_id())->where('status', 1)->get();

        return view('laundry::vendor.basic-staff.form', compact('member', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $emp = VendorEmployee::where('store_id', Helpers::get_store_id())->findOrFail($id);

        $request->validate([
            'f_name'   => 'required|string|max:100',
            'phone'    => ['required', 'regex:/^[0-9\s\-\+\(\)]{7,20}$/', Rule::unique('vendor_employees', 'phone')->ignore($id)],
            'email'    => ['required', 'email', Rule::unique('vendor_employees', 'email')->ignore($id)],
            'image'    => 'nullable|image|max:2048',
            'id_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        $emp->f_name           = $request->f_name;
        $emp->l_name           = $request->l_name;
        $emp->phone            = $request->phone;
        $emp->email            = $request->email;
        $emp->employee_role_id = $request->employee_role_id ?: null;

        if ($request->hasFile('image')) {
            if ($emp->image) {
                Helpers::delete_image($emp->image, 'profile/');
            }
            $emp->image = Helpers::upload('profile/', 'png', $request->file('image'));
        }

        if ($request->hasFile('id_proof')) {
            if ($emp->id_document) {
                Helpers::delete_image($emp->id_document, 'employee/id-proof/');
            }
            $ext = $request->file('id_proof')->getClientOriginalExtension();
            $emp->id_document = Helpers::upload('employee/id-proof/', $ext, $request->file('id_proof'));
        }

        $emp->save();

        Toastr::success('Staff member updated successfully.');
        return redirect()->route('vendor.basic-staff.index');
    }

    public function destroy($id)
    {
        $emp = VendorEmployee::where('store_id', Helpers::get_store_id())->findOrFail($id);

        if ($emp->image) {
            Helpers::delete_image($emp->image, 'profile/');
        }
        if ($emp->id_document) {
            Helpers::delete_image($emp->id_document, 'employee/id-proof/');
        }

        $emp->delete();

        Toastr::success('Staff member removed.');
        return back();
    }

    // ─── Roles ───────────────────────────────────────────────────

    public function roles()
    {
        $roles = EmployeeRole::where('store_id', Helpers::get_store_id())->latest()->get();

        return view('laundry::vendor.basic-staff.roles', compact('roles'));
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('employee_roles', 'name')->where(fn ($q) => $q->where('store_id', Helpers::get_store_id())),
            ],
        ]);

        $role           = new EmployeeRole();
        $role->name     = $request->name;
        $role->modules  = null;
        $role->status   = 1;
        $role->store_id = Helpers::get_store_id();
        $role->save();

        Toastr::success('Role created.');
        return back();
    }

    public function updateRole(Request $request, $id)
    {
        $role = EmployeeRole::where('store_id', Helpers::get_store_id())->findOrFail($id);

        $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('employee_roles', 'name')->where(fn ($q) => $q->where('store_id', Helpers::get_store_id()))->ignore($id),
            ],
        ]);

        $role->name = $request->name;
        $role->save();

        Toastr::success('Role updated.');
        return back();
    }

    public function destroyRole($id)
    {
        $role = EmployeeRole::where('store_id', Helpers::get_store_id())->findOrFail($id);

        // Unassign staff from this role before deleting
        VendorEmployee::where('store_id', Helpers::get_store_id())
            ->where('employee_role_id', $id)
            ->update(['employee_role_id' => null]);

        $role->delete();

        Toastr::success('Role deleted.');
        return back();
    }
}
