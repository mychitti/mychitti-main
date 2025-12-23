<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\EmployeeRole;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\Department;
use App\Models\Staff;

class StaffController extends Controller
{
    public function departments(){
    
        $v_id = '0';

        $departments = Department::where('vendor_id', $v_id )->get();
        // print_r($departments);
       return view('admin-views.department.index', compact('departments'));
    }

    public function store_department(Request $request){
       $v_id = '0';

       $departmentCheck = Department::where('title', strtolower($request->post('title')))->where('vendor_id', $v_id )->get();
       if(count($departmentCheck)){
        Toastr::error("Department Already Exist");
        return back();
       }else{
         $dep = new Department;
         $dep->vendor_id = $v_id;
         $dep->title = $request->post('title');
         $dep->status = $request->post('status');
         $dep->save();
         Toastr::success("Department Added Successfully");
         return back();
       }
    }
    
    public function index(Request $request)
    {
     
        $v_id = '0';
        
        $staff = VendorEmployee::all();
        return view('admin-views.staff.index', compact('staff'));
    } 
    public function edit(Request $request, $id)
    {
        $staff = Staff::find($id);
        $departments = Department::where('status', 1)->where('vendor_id', $v_id )->get();
        return view('admin-views.staff.manage', compact('staff', 'departments'));
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
        $query =  Department::find($id)
            ->delete();
            Toastr::success('Department Deleted Successfully');
        return back();
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
        return view('admin-views.staff.add', compact('departments'));
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
                'email' => 'required|email|unique:staff,email,'.$id,
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
        $v_id = '0'; 
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
        }else{
            $staff->update();
            Toastr::success('Staff Information updated successfully');
        }
        return redirect('admin/staff/list');
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
