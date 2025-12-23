<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Salary;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\Department;
use App\Models\Admin;
use App\Models\Staff;

class SalaryController extends Controller
{
    public function index(Request $request)
    {
        $v_id = 0;
        
        $salary = Salary::where('vendor_id', $v_id )->where('employee_type', 'admin_employee')->get();
        // print_r($salary); die;
        return view('admin-views.salary.index', compact('salary'));
    } 
    public function edit(Request $request, $id)
    {
        $salary = Salary::find($id);
        $all_salaries = Salary::where('employee_id', $salary->employee_id)->where('employee_type', 'admin_employee')->get();
        return view('admin-views.salary.manage', compact('salary', 'all_salaries'));
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
    public function get_info(Request $request){
        // echo $request->id; 
        $salary_info = Salary::where('employee_id', $request->id)->get();
        return response()->json(['data'=> $salary_info]);
    }
    public function status(Request $request)
    {
        $coupon = Staff::find($request->id);
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
      
        $query =  Salary::where('id', $id)->where('employee_type', 'admin_employee')
            ->delete();
          
            Toastr::success('Salary Info Deleted Successfully');
        return back();
    }

    public function add()
    {
        $employees =  Admin::whereNot('role_id', 1)->latest()->paginate(config('default_pagination'));
        // print_r($employees);die;
        return view('admin-views.salary.add', compact('employees'));
    }
    public function save_info(Request $request)
    {
        $id = $request->post('salary_id');
        
            $validator = Validator::make($request->all(), [
                'emp_id' => 'required',
                'base_salary' => 'required',
                'pay_frequency' => 'required',
                'pay_type' => 'required',
                // 'work_hours' => 'required',
                'payment_method' => 'required', 
            ], [
                'emp_id.required' => 'Please Select Employee',
                'base_salary.required' => 'Please Enter Base Salary',
                'pay_frequency.required' => 'Please Enter Pay Frequency',
                'pay_type.required' => 'Please Select Pay Type',
                // 'work_hours.required' => 'Please Enter Work Hours',
                'payment_method.required' => 'Please Select Payment Method',
            ]);
             if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

        if ($id == '') { // for new lead  
            $staff = new Salary;
        } else {
            $staff = Salary::find($id);
        }
        $v_id = 0; 
        // echo $v_id;die;

        $staff->vendor_id = $v_id;
        $staff->employee_id = $request->post('emp_id');
        $staff->employee_type = 'admin_employee';
        $staff->base_salary = $request->post('base_salary');
        $staff->pay_frequency = $request->post('pay_frequency');
        $staff->pay_type = $request->post('pay_type');
        $staff->bonus_incentives = $request->post('bonus_incentives');
        $staff->allowance = $request->post('allowance');
        $staff->deductions = $request->post('deductions');
        // $staff->work_hours = $request->post('work_hours');
        $staff->vacation_or_leave = $request->post('vacation_or_leave');
        $staff->payment_method = $request->post('payment_method');
        $staff->acc_holder_name =$request->post('acc_holder_name');
        $staff->ifsc = $request->post('ifsc');
        $staff->upi_id =$request->post('upi_id');
        $staff->created_at = date('Y-m-d H:i:s');
        
        if ($id == '') { // for new lead
            $staff->save();
            Toastr::success('Salary Information saved successfully');
        }else{
            $staff->update();
            Toastr::success('Salary Information updated successfully');
        }
        return redirect('admin/users/salary/list');
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