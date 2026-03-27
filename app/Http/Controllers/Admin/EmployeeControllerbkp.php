<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use App\Models\EmployeeRole;
use App\Models\Department;
use App\Models\LedgerAccountType;
use App\Models\StoreAccount;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\VendorEmployee;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller; 
use Brian2694\Toastr\Facades\Toastr;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Exports\StoreEmployeeListExport;
use App\Models\AcceptedServiceRequest;
use App\Models\AdminRole;
use App\Models\AdvanceRequest;
use App\Models\AssetAlotment;
use App\Models\Project;
use App\Models\StoreTask;
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon;

class EmployeeController extends Controller
{

    public function add_new()
    {

        $departments = Department::where('vendor_id', 0)->where('status', '1')->get();
        $rls = AdminRole::where('id', '!=', 1)->get();

        return view('admin-views.employee-addon.add-new', compact('rls', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'f_name' => 'required',
            'l_name' => 'nullable|max:100',
            'role_id' => 'required',
            'department_id' => 'required',
            'image' => 'required',
            'email' => 'required|unique:vendor_employees',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|max:20|unique:vendor_employees',
            'password' => ['required', Password::min(8)->mixedCase()->letters()->numbers()->symbols()],
        ]);
        $documents = [];
        if (!empty($request->file('documents'))) {
            foreach ($request->documents as $file) {
                $extension = $file->getClientOriginalExtension();
                $doc_name = Helpers::upload('vendor/documents/', $extension, $file);
                $documents[] = $doc_name;
            }
        }
        DB::table('vendor_employees')->insert([
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'employee_role_id' => $request->role_id,
            'department_id' => $request->department_id,
            'password' => bcrypt($request->password),
            'vendor_id' => Helpers::get_vendor_id(),
            'store_id' => 0,
            'salary_type' => $request->salary_type,
            'base_salary' => $request->salary_type == 'Task-Wise' ? null : $request->base_salary,
            'documents' =>  json_encode($documents),
            'image' => Helpers::upload('vendor/', 'png', $request->file('image')),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Toastr::success('Staff added successfully!');
        return redirect()->route('admin.users.employee.list');
    }
 
    function list(Request $request)
    {
        $key = explode(' ', $request['search']);

        $em = Admin::where('role_id', '!=', 1)->with(['role'])->latest()->paginate(config('default_pagination'));
        return view('admin-views.employee-addon.list', compact('em'));
    }

    public function edit($id)
    {
        $e = VendorEmployee::where('store_id', 0)->where(['id' => $id])->first();
        $rls = EmployeeRole::where('store_id', 0)->get();
        $department = Department::where('vendor_id', 0)->where('status', '1')->get();
        if (auth('vendor_employee')->id()  != $e['id']) {
            return view('admin-views.employee.edit', compact('rls', 'e', 'department'));
        }
        Toastr::warning(translate('messages.access_denied'));
        return back();
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'f_name' => 'required',
            'l_name' => 'nullable|max:100',
            'role_id' => 'required',
            'email' => 'required|unique:vendor_employees,email,' . $id,
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|max:20|unique:vendor_employees,phone,' . $id,
            'password' => ['nullable', Password::min(8)->mixedCase()->letters()->numbers()->symbols()],
        ], [
            'f_name.required' => translate('messages.first_name_is_required'),
        ]);

        $e = VendorEmployee::where('store_id', 0)->find($id);
        if ($request['password'] == null) {
            $pass = $e['password'];
        } else {
            if (strlen($request['password']) < 7) {
                Toastr::warning(translate('messages.password_length_warning', ['length' => '8']));
                return back();
            }
            $pass = bcrypt($request['password']);
            $e->remember_token = null;
        }

        if ($request->has('image')) {
            $e['image'] = Helpers::update('vendor/', $e->image, 'png', $request->file('image'));
        }
        $updated_docs =  json_decode($e->documents);
        if (!empty($request->file('documents'))) {
            foreach ($request->documents as $file) {
                $extension = $file->getClientOriginalExtension();
                $doc_name = Helpers::upload('vendor/documents/', $extension, $file);
                $documents[] = $doc_name;
            }
            if (json_decode($e->documents)) {
                $updated_docs =  array_merge(json_decode($e->documents), $documents);
            } else {
                $updated_docs =  $documents;
            }
        }
        $e->f_name = $request->f_name;
        $e->l_name = $request->l_name;
        $e->phone = $request->phone;
        $e->email = $request->email;
        $e->employee_role_id = $request->role_id;
        $e->vendor_id = Helpers::get_vendor_id();
        $e->store_id = 0;
        $e->salary_type = $request->salary_type;
        $e->base_salary = $request->salary_type == 'Task-Wise' ? null : $request->base_salary;
        $e->password = $pass;
        $e->documents =  json_encode($updated_docs);
        $e->image = $e['image'];
        $e->updated_at = now();
        $e->is_logged_in = 0;
        $e->save();

        Toastr::success('Staff updated successfully!');
        return redirect()->route('admin.users.employee.list');
    }

    public function distroy($id)
    {
        $role = VendorEmployee::where('store_id', 0)->where(['id' => $id])->delete();
        Toastr::info('Staff Deleted Successfully');
        return back();
    }

    // public function search(Request $request){
    //     $key = explode(' ', $request['search']);
    //     $employees=VendorEmployee::where('store_id', 0)->
    //     where(function ($q) use ($key) {
    //         foreach ($key as $value) {
    //             $q->orWhere('f_name', 'like', "%{$value}%");
    //             $q->orWhere('l_name', 'like', "%{$value}%");
    //             $q->orWhere('phone', 'like', "%{$value}%");
    //             $q->orWhere('email', 'like', "%{$value}%");
    //         }
    //     })->limit(50)->get();
    //     return response()->json([
    //         'view'=>view('admin-views.employee.partials._table',compact('employees'))->render(),
    //         'count'=>$employees->count()
    //     ]);
    // }

    public function list_export(Request $request)
    {

        $key = explode(' ', $request['search']);
        $em = VendorEmployee::where('store_id', 0)->with(['role'])

            ->when(isset($key), function ($query) use ($key) {
                $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('f_name', 'like', "%{$value}%");
                        $q->orWhere('l_name', 'like', "%{$value}%");
                        $q->orWhere('phone', 'like', "%{$value}%");
                        $q->orWhere('email', 'like', "%{$value}%");
                    }
                });
            })
            ->latest()->get();
        $data = [
            'employees' => $em,
            'search' => $request->search ?? null,
        ];

        if ($request->type == 'excel') {
            return Excel::download(new StoreEmployeeListExport($data), 'Employees.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new StoreEmployeeListExport($data), 'Employees.csv');
        }


        // if($request->type == 'excel'){
        //     return (new FastExcel($em))->download('Employee.xlsx');
        // }elseif($request->type == 'csv'){
        //     return (new FastExcel($em))->download('Employee.csv');
        // }
    }

    public function departments()
    {
        $v_id = 0;
        $departments = Department::where('vendor_id', $v_id)->get();
        return view('admin-views.department.index', compact('departments'));
    }

    public function store_department(Request $request)
    {
        $v_id = 0;

        $departmentCheck = Department::where('title', strtolower($request->post('title')))->where('vendor_id', $v_id)->get();
        if (count($departmentCheck)) {
            if ($request->form_type == 'ajax') {
                return response()->json(['status' => false, 'msg' => "Department Already Exist", 'action' => 'add_dept', 'dep_id' => null]);
            } else {
                Toastr::error("Department Already Exist");
                return back();
            }
        } else {
            // make account for department
            $account = self::createDepartmentLedger($request->post('title'));
            // save department
            $dep = new Department;
            $dep->vendor_id = $v_id;
            $dep->title = $request->post('title');
            $dep->status = $request->post('status');
            $dep->ledger_account_id = $account->id;
            $dep->save();
            if ($request->form_type == 'ajax') {
                return response()->json(['status' => true, 'msg' => "Added Successfully", 'action' => 'add_dept', 'dep_id' => $dep->id]);
            } else {
                Toastr::success("Department Added Successfully");
                return back();
            }
        }
    }

    public function createDepartmentLedger($departmentName)
    {
        $storeId = 0;

        $salaryAccount = StoreAccount::where('store_id', $storeId)
            ->whereHas('ledgerAccountType', function ($q) {
                $q->where('name', 'Expenses');
            })
            ->where('name', 'Salary')
            ->first();

        // If not exists, create Salary account
        if (!$salaryAccount) {
            $expenseType = LedgerAccountType::where('name', 'Expenses')->firstOrFail();

            $salaryAccount = StoreAccount::create([
                'store_id' => $storeId,
                'ledger_account_type_id' => $expenseType->id,
                'parent_id' => null,
                'code' => _accountCode($expenseType->id, null),
                'name' => 'Salary',
                'description' => 'Salary Expenses',
                'level' => 1,
                'entity_type' => 'admin'
            ]);
        }

        $departmentAccount = StoreAccount::create([
            'store_id' => $storeId,
            'ledger_account_type_id' => $salaryAccount->ledger_account_type_id,
            'parent_id' => $salaryAccount->id,
            'code' => _accountCode($salaryAccount->ledger_account_type_id, $salaryAccount->id),
            'name' => $departmentName,
            'description' => "Salary Account for {$departmentName} Department",
            'level' => $salaryAccount->level + 1,
            'entity_type' => 'admin'
        ]);

        return $departmentAccount;
    }

    public function terminate(Request $request, $id)
    {
        $emp = Admin::where('role_id', '!=', 1)->where('id', $id)->first();
        if (!$emp) {
            Toastr::error('Employee not found!');
            return back();
        }
        $emp->terminate = 1;
        $emp->status = 0;
        $emp->save();

        Toastr::success('Employee Terminated Successfully');
        return back();
    }

    public function resignation_action(Request $request, $id, $action)
    {
        $rr = DB::table('employee_resignations')
            ->where('id', $id)
            ->where('store_id', 0)
            ->first();

        if ($rr) {
            DB::table('employee_resignations')
                ->where('id', $id)
                ->where('store_id', 0)
                ->update(['status' => $action]);

            if ($action == 'approved') {
                $emp = Admin::where('role_id', '!=', 1)->where('id', $rr->employee_id)->first();
                if ($emp) {
                    $emp->status = 0;
                    $emp->resignation = 1;
                    $emp->save();
                }
            }

            Toastr::success(ucfirst($action) . ' successfully');
            return back();
        }

        Toastr::error('Resignation request not found');
        return back();
    }

    public function department_status_change(Request $request)
    {
        $id = $request->post('d_id');
        $status = $request->post('status');

        Department::where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        return back();
    }

    public function delete_department(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $department = Department::findOrFail($id);
            $staffCount = Admin::where('department_id', $department->id)->where('role_id', '!=', 1)->count();

            if ($staffCount > 0) {
                Toastr::error('Cannot delete department. Staff members are assigned to this department.');
                return redirect()->back();
            }

            $storeAccount = StoreAccount::where('store_id', 0)
                ->where('id', $department->ledger_account_id)
                ->first();

            if ($storeAccount) {
                $storeAccount->children()->delete();
                $storeAccount->delete();
            }

            $department->delete();

            DB::commit();
            Toastr::success('Department Deleted successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Error: ' . $e->getMessage());
            return redirect()->back();
        }
    }
}
