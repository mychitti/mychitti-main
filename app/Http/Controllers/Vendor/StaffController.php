<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\EmployeeRole;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\Department;
use App\Models\LedgerAccountType;
use App\Models\Staff;
use App\Models\Store;
use App\Models\StoreAccount;
use App\Models\StoreTeam;
use App\Models\StoreTeamMember;
use App\Models\VendorTeam;
use App\Models\VendorTeamMember;
use Faker\Extension\Helper;
use Illuminate\Support\Facades\DB;

class StaffController extends Controller
{
    public function save_settings(Request $request)
    {
        $storeId = Helpers::get_store_id();

        $store = Store::find($storeId);
        $store->emp_prefix = $request->has('prefix') ? $request->prefix : '';
        $store->prefix_status = $request->has('prefix_status') ? 1 : 0;
        $store->save();

        $data = [
            'vendor_id' => $storeId,
            'type' => 'for_staff',
            'terms_n_conditons' => $request->content,
            'updated_at' => now(),
        ];

        $exists = DB::table('vendor_terms_conditions')
            ->where('vendor_id', $storeId)
            ->where('type', 'for_staff')
            ->exists();

        if ($exists) {
            DB::table('vendor_terms_conditions')
                ->where('vendor_id', $storeId)
                ->where('type', 'for_staff')
                ->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('vendor_terms_conditions')->insert($data);
        }

        Toastr::success('Saved Successfully');
        return back();
    }
    public function team_member_delete(Request $request, $id)
    {
        StoreTeamMember::find($id)->delete();
        Toastr::success('Removed Successfully');
        return back();
    }
    public function team_delete(Request $request, $id)
    {
        StoreTeam::find($id)->delete();
        Toastr::success('Deleted Successfully');
        return back();
    }
    public function teams()
    {
        $staff = VendorEmployee::where('store_id', Helpers::get_store_id())->get();
        $teams = StoreTeam::with('members')->where('store_id', Helpers::get_store_id())->paginate(10);
        return view('vendor-views.employee.teams', compact('teams', 'staff'));
    }
    public function team_edit(Request $request, $id)
    {
        $staff = VendorEmployee::where('store_id', Helpers::get_store_id())->get();

        $team = StoreTeam::with('members')->where('id', $id)->first();
        $team_members = StoreTeamMember::with('employee')->where('team_id', $id)->get();
        // prx($team_members);
        return view('vendor-views.employee.teams_edit', compact('team', 'staff', 'team_members'));
    }
    public function team_update(Request $request)
    {
        $team = StoreTeam::where('id', $request->team_id)->where('store_id', Helpers::get_store_id())->first();
        $team->name = $request->team_name;
        $team->color = $request->team_color;
        if ($request->has('team_leader') && $request->team_leader) {
            $team->team_lead = $request->team_leader;
        }
        $team->save();

        if ($request->has('team_leader') && $request->team_leader) {

            // Unlead all existing leaders in this team
            StoreTeamMember::where('team_id', $request->team_id)
                ->where('is_team_lead', 1)
                ->update(['is_team_lead' => 0]);

            //  Promote selected member to leader
            $existing = StoreTeamMember::where([
                'team_id' => $request->team_id,
                'employee_id' => $request->team_leader
            ])->first();

            if ($existing) {
                $existing->is_team_lead = 1;
                $existing->save();
            } else {
                $team_member = new StoreTeamMember();
                $team_member->team_id = $request->team_id;
                $team_member->employee_id = $request->team_leader;
                $team_member->is_team_lead = 1;
                $team_member->joined_at = now()->toDateString();
                $team_member->save();
            }
        }


        if (isset($request->members)) {
            foreach ($request->members as $key => $value) {
                $exists = StoreTeamMember::where(['team_id' => $request->team_id, 'employee_id' => $value])->exists();
                if (!$exists) {
                    $team_member = new StoreTeamMember();
                    $team_member->team_id = $team->id;
                    $team_member->employee_id = $value;
                    $team_member->is_team_lead = 0;
                    $team_member->joined_at = date('Y-m-d');
                    $team_member->save();
                }
            }
        }
        Toastr::success('Team Details Updated Successfully');
        return back();
    }
    public function team_save(Request $request)
    {
        // prx($request->all());
        $validator = Validator::make($request->all(), [
            'members' => 'required',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $team = new StoreTeam();
        $team->store_id = Helpers::get_store_id();
        $team->name = $request->team_name;
        $team->color = $request->team_color;
        $team->team_lead = $request->team_leader ?? null;
        $team->save();

        if ($request->has('team_leader') && $request->team_leader) {
            $team_member = new StoreTeamMember();
            $team_member->team_id = $team->id;
            $team_member->employee_id = $request->team_leader;
            $team_member->is_team_lead = 1;
            $team_member->joined_at = date('Y-m-d');
            $team_member->save();
        }

        foreach ($request->members as $key => $value) {

            if ($request->has('team_leader') && $request->team_leader == $value) {
                continue;
            }
            $team_member = new StoreTeamMember();
            $team_member->team_id = $team->id;
            $team_member->employee_id = $value;
            $team_member->is_team_lead = 0;
            $team_member->joined_at = date('Y-m-d');
            $team_member->save();
        }
        Toastr::success('Team Created Successfully');
        return back();
    }
    public function settings()
    {
        $store = Helpers::get_store_data();
        $tAndCContentForStaff = DB::table('vendor_terms_conditions')->where('type', 'for_staff')->where('vendor_id', Helpers::get_store_id())->first();

        return view('vendor-views.staff.settings', compact('store', 'tAndCContentForStaff'));
    }
    public function departments()
    {

        $v_id = Helpers::get_store_id();

        $departments = Department::where('vendor_id', $v_id)->get();
        return view('vendor-views.department.index', compact('departments'));
    }

    public function store_department(Request $request)
    {
        $v_id = Helpers::get_store_id();

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
          
            $account =  self::createDepartmentLedger($request->post('title'));
            //save department 
            $dep = new Department;
            $dep->vendor_id = $v_id;
            $dep->title = $request->post('title');
            $dep->status = $request->post('status');
            $dep->ledger_account_id = $account->id;
            $dep->save();
            if ($request->form_type == 'ajax') {
                return response()->json(['status' => true, 'msg' => "Added  Successfully", 'action' => 'add_dept', 'dep_id' => $dep->id]);
            } else {
                Toastr::success("Department Added Successfully");
                return back();
            }
        }
    }


    public function createDepartmentLedger($departmentName)
    {
        $storeId = Helpers::get_store_id();

        $salaryAccount = StoreAccount::where('store_id', $storeId)
            ->whereHas('ledgerAccountType', function ($q) {
                $q->where('name', 'Expenses');
            })
            ->where('name', 'Salary')
            ->first();

        //  If not exists, create Salary account
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
                 'entity_type' => 'store'
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
             'entity_type' => 'store'
        ]);

        return $departmentAccount;
    }


    public function index(Request $request)
    {

        $v_id = Helpers::get_store_id();

        $staff = VendorEmployee::all();
        return view('vendor-views.staff.index', compact('staff'));
    }
    public function edit(Request $request, $id)
    {
        $staff = Staff::find($id);
        $departments = Department::where('status', 1)->where('vendor_id', $v_id)->get();
        return view('vendor-views.staff.manage', compact('staff', 'departments'));
    }

    public function status_change(Request $request)
    {

        $id = $request->post('d_id');
        $status = $request->post('status');

        $query =  Department::where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        // echo $query;
        return back();
    }
    public function status(Request $request)
    {
        $coupon = VendorEmployee::find($request->id);
        $coupon->status = $request->status;
        $coupon->save();
        Toastr::success('Staff Status Changed Successfully');
        return back();
    }
    public function delete_department(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $department = Department::findOrFail($id);
            $staffCount = VendorEmployee::where('department_id', $department->id)->count();

            if ($staffCount > 0) {
                Toastr::error('Cannot delete department. Staff members are assigned to this department.');
                return redirect()->back();
            }

            $storeAccount = StoreAccount::where('store_id', Helpers::get_store_id())
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
            // Toastr::error('Some Error Occured');
            return redirect()->back();
        }
    }
    public function delete(Request $request, $id)
    {
        $query =  Staff::find($id)
            ->delete();
        Toastr::success('Staff Deleted Successfully');
        return back();
    }

    public function add()
    {
        $departments = Department::where('status', '1')->get();
        return view('vendor-views.staff.add', compact('departments'));
    }
    public function save_info(Request $request)
    {
        $id = $request->post('staff_id');

        $validator = Validator::make($request->all(), [
            'username' => 'required|max:100',
            'name' => 'required|max:100',
            'city' => 'required|max:100',
            'pincode' => 'required',
            'department_id' => 'required',
            'salary_per_month' => 'required',
            'email' => 'required|email|unique:staff,email,' . $id,
            'mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20',
        ], [
            'name.required' => 'Please Enter Name',
            'username.required' => 'Please Enter Username',
            'city.required' => 'Please Enter city',
            'email.required' => 'Please Enter Email',
            'pincode.required' => 'Please Enter Pincode',
            'department_id.required' => 'Please Select Department',
            'salary_per_month.required' => 'Please Enter Salary',
            'mobile.required' => 'Please Enter Mobile',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if ($id == '') { // for new lead  
            $staff = new Staff;
        } else {
            $staff = Staff::find($id);
        }
        $v_id = \App\CentralLogics\Helpers::get_store_id();
        // echo $v_id;die;

        $staff->vendor_id = $v_id;
        $staff->department_id = $request->post('department_id');
        $staff->username = $request->post('username');
        $staff->name = $request->post('name');
        $staff->email = $request->post('email');
        $staff->mobile = $request->post('mobile');
        $staff->address = $request->post('address');
        $staff->city = $request->post('city');
        $staff->pincode = $request->post('pincode');
        $staff->salary_per_month = $request->post('salary_per_month');
        $staff->dob = $request->post('dob');
        $staff->created_at = date('Y-m-d H:i:s');

        if ($id == '') { // for new lead
            $staff->save();
            Toastr::success('Staff Information saved successfully');
        } else {
            $staff->update();
            Toastr::success('Staff Information updated successfully');
        }
        return redirect('staff/list');
    }

    public function lead_approval(Request $request)
    {
        $id = $request->post('lead_id');
        $lead = Lead::find($id);
        $lead->approval = $request->post('approval');
        $lead->save();
        return response()->json(['msg' => ucfirst($request->post('approval')) . 'ed', 'status' => true]);
    }
}
