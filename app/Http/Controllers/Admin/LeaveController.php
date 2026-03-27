<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Lead;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\Admin;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Leave;
use App\Models\Attendance;
use App\Models\StoreConfig;

class LeaveController extends Controller
{

    public function index(Request $request)
    {
        $v_id = 0;

        $store_config = StoreConfig::where('store_id', $v_id)->first();

        $staff = Admin::where('role_id', '!=', 1)->get();
        return view('admin-views.leave.index', compact('staff', 'store_config'));
    }
    public function manage(Request $request, $id)
    {
        $totalLeaveBalance = 0;

        $v_id = 0;
        if (isset($request->year)) {
            $filter_year =   $request->year;
        } else {
            $filter_year = date('Y');
        }
        if (isset($request->month)) {
            $filter_month =  $request->month;
        } else {
            $filter_month = date('m');
        }
        $staff = Admin::find($id);
        $departments = Department::where('status', 1)->where('vendor_id', $v_id)->get();
        $attendance = Attendance::where(['vendor_id' => $v_id,  'employee_type' => 'admin_employee', 'employee_id' => $id, 'month' => $filter_month, 'year' => $filter_year])->get()->toArray();
        $leaves = Leave::where(['vendor_id' => $v_id, 'emp_id' => $id, 'employee_type' => 'admin_employee', 'month' => $filter_month, 'year' => $filter_year])->get()->toArray();
        $pendingleaves = Leave::where(['vendor_id' => $v_id, 'emp_id' => $id, 'employee_type' => 'admin_employee',  'status' => 'pending'])->get()->toArray();
        $day_data['absent'] = 0;
        $day_data['present'] = 0;
        $day_data['holiday'] = 0;
        $day_data['cl'] = 0;
        $day_data['halfday'] = 0;
        $day_data['sunday'] = 0;
        $labelArr = [];
        $daArr = [];

        //leaves balance
        $clLeavesTaken = Leave::where(['vendor_id' => $v_id, 'emp_id' => $id, 'employee_type' => 'admin_employee', 'month' => $filter_month, 'year' => $filter_year, 'leave_type' => 'CL', 'status' => 'approved'])->get()->toArray();
        $slLeavesTaken = Leave::where(['vendor_id' => $v_id, 'emp_id' => $id, 'employee_type' => 'admin_employee', 'month' => $filter_month, 'year' => $filter_year, 'leave_type' => 'SL', 'status' => 'approved'])->get()->toArray();
        $store_config = StoreConfig::where('store_id', $v_id)->first();
        $monthlyClleaveBalance = ($store_config ? $store_config->cl_for_employees : 0) - count($clLeavesTaken);
        $monthlySlleaveBalance = ($store_config ? $store_config->sl_for_employees : 0) - count($slLeavesTaken);


        foreach ($attendance as $att) {
            // print_r($att);
            array_push($daArr, $att['day']);
            array_push($labelArr, $att['label']);
            if ($att['label'] == 'Sun') {
                $day_data['sunday']++;
            }
            if ($att['label'] == 'A') {
                $day_data['absent']++;
            }
            if ($att['label'] == 'P') {
                $day_data['present']++;
            }
            if ($att['label'] == 'CL') {
                $day_data['cl']++;
            }
            if ($att['label'] == 'HD') {
                $day_data['halfday']++;
            }
            if ($att['label'] == 'HL') {
                $day_data['holiday']++;
            }
        }

        // print_r($day_data);
        // die;

        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $filter_month, $filter_year);

        $firstDayOfMonth = date('N', strtotime(date($filter_year . '-' . $filter_month . '-01')));

        $sundays_in_month = 0;

        for ($t = 1; $t <=  $days_in_month; $t++) {
            if (date('l', strtotime(date('Y-' . $filter_month . '-' . $t))) == 'Sunday') {
                $sundays_in_month++;
            }
        }

        if (!empty($attendance)) {
            $sundays_in_month = $day_data['sunday'];
        }


        // die;
        // print_r($attendance);die;
        return view('admin-views.leave.manage', compact(
            'staff',
            'departments',
            'filter_year',
            'filter_month',
            'attendance',
            'day_data',
            'days_in_month',
            'firstDayOfMonth',
            'sundays_in_month',
            'daArr',
            'labelArr',
            'leaves',
            'pendingleaves',
            'monthlyClleaveBalance',
            'monthlySlleaveBalance',
            'id'
        ));
    }

    public function save_leave(Request $request)
    {

        $v_id = 0;
        $leave = Leave::where(['emp_id' => $request->post('emp_id'), 'day' => $request->post('day'), 'month' => $request->post('month'), 'year' => $request->post('year')])->where('vendor_id' ,0)->exists();
        if (!$leave) {

            $leave_date = sprintf('%04d-%02d-%02d',$request->post('year'),$request->post('month'),$request->post('day'));

            $leave = new Leave;
            $leave->vendor_id = $v_id;
            $leave->emp_id = $request->post('emp_id');
            $leave->day = $request->post('day');
            $leave->status = 'approved';
            $leave->added_by = auth('admin')->user()->role_id == 1 ? 'admin' : 'admin_employee';
            $leave->month = $request->post('month');
            $leave->year = $request->post('year');
            $leave->leave_date = $leave_date;
            $leave->leave_type = $request->post('leaveType');
            $leave->reason = $request->post('reason');
            $leave->employee_type = 'admin_employee';
            $leave->created_at = date('Y-m-d H:i:s');
            $leave->save();

            // attendance 
            if ($request->post('leaveType') == 'HDS' || $request->post('leaveType') == 'HDF') {
                $leaveType = 'HD';
            } else {
                $leaveType = $request->post('leaveType');
            }
            $att = new Attendance;
            $att->vendor_id = $v_id;
            $att->employee_id = $request->post('emp_id');
            $att->date = $leave_date;
            $att->label = $leaveType;
            $att->day =  $request->post('day');
            $att->month = $request->post('month');
            $att->year = $request->post('year');
            $att->created_at = date('Y-m-d H:i:s');
            $att->save();
            Toastr::success('Leave saved successfully');
        } else {
            Toastr::warning('Leave already exists for this date');
        }



        $data['status'] = true;
        $data['msg'] = 'Updated Successfully';

        echo json_encode($data);
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
        $query =  Staff::find($id)
            ->delete();
        Toastr::success('Staff Deleted Successfully');
        return back();
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
        $v_id =0;
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
