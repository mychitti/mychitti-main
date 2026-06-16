<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Lead;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Leave;
use App\Models\Attendance;
use App\Models\StoreConfig;
use App\Models\VendorEmployee;

class LeaveController extends Controller
{

    public function index(Request $request)
    {
        $v_id = Helpers::get_store_id();

        $store_config = StoreConfig::where('store_id', $v_id)->first();
        $leaves = Leave::with('employee')->where('vendor_id', $v_id)->where('employee_type', 'vendor_employee')->get();
        $staff = VendorEmployee::where('store_id', Helpers::get_store_id())->get();
        return view('vendor-views.leave.index', compact('staff', 'leaves','store_config'));
    }
    public function manage(Request $request, $id)
    {
        $totalLeaveBalance = 0;

        $v_id = \App\CentralLogics\Helpers::get_store_id();
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
        $staff = VendorEmployee::find($id);
        $departments = Department::where('status', 1)->where('vendor_id', $v_id)->get();
        $attendance = Attendance::where(['vendor_id' => $v_id,  'employee_type' => 'vendor_employee', 'employee_id' => $id, 'month' => $filter_month, 'year' => $filter_year])->get()->toArray();
        $leaves = Leave::where(['vendor_id' => $v_id, 'emp_id' => $id, 'employee_type' => 'vendor_employee', 'month' => $filter_month, 'year' => $filter_year])->get()->toArray();
        $pendingleaves = Leave::where(['vendor_id' => $v_id, 'emp_id' => $id, 'employee_type' => 'vendor_employee',  'status' => 'pending'])->get()->toArray();
        $day_data['absent'] = 0;
        $day_data['present'] = 0;
        $day_data['holiday'] = 0;
        $day_data['cl'] = 0;
        $day_data['halfday'] = 0;
        $day_data['sunday'] = 0;
        $labelArr = [];
        $daArr = [];

        //leaves balance — counts derive from Attendance (single source of truth)
        $store_config = StoreConfig::where('store_id', $v_id)->first();
        // Per-staff allowance overrides the store default when set on the employee.
        $clAllowed = $staff->cl_allowance ?? ($store_config->cl_for_employees ?? 0);
        $slAllowed = $staff->sl_allowance ?? ($store_config->sl_for_employees ?? 0);
        $leaveCounts = _attendanceMonthCounts($v_id, $id, $filter_year, $filter_month);
        // Half-day casual/sick (HCL/HSL) draw 0.5 from the matching allowance.
        $monthlyClleaveBalance = $clAllowed - ($leaveCounts['CL'] + 0.5 * $leaveCounts['HCL']);
        $monthlySlleaveBalance = $slAllowed - ($leaveCounts['SL'] + 0.5 * $leaveCounts['HSL']);


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
            if ($att['label'] == 'HCL') {
                $day_data['cl'] = ($day_data['cl'] ?? 0) + 0.5;
            }
            if ($att['label'] == 'SL') {
                $day_data['sl'] = ($day_data['sl'] ?? 0) + 1;
            }
            if ($att['label'] == 'HSL') {
                $day_data['sl'] = ($day_data['sl'] ?? 0) + 0.5;
            }
            if (in_array($att['label'], ['HD', 'HDF', 'HDS', 'HCL', 'HSL'])) {
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
        return view('vendor-views.leave.manage', compact(
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

        $v_id = \App\CentralLogics\Helpers::get_store_id();
        $leave = Leave::where(['emp_id' => $request->post('emp_id'), 'day' => $request->post('day'), 'month' => $request->post('month'), 'year' => $request->post('year'), 'employee_type' => 'vendor_employee'])->where('vendor_id', $v_id)->exists();

        // Enforce per-staff monthly CL/SL allowance (staff value overrides store default).
        // Full CL/SL cost 1 day; half-day casual/sick (HCL/HSL) cost 0.5 against the same allowance.
        $leaveType = $request->post('leaveType');
        $allowanceMap = [
            'CL'  => ['pool' => 'cl', 'cost' => 1.0],
            'SL'  => ['pool' => 'sl', 'cost' => 1.0],
            'HCL' => ['pool' => 'cl', 'cost' => 0.5],
            'HSL' => ['pool' => 'sl', 'cost' => 0.5],
        ];
        if (!$leave && isset($allowanceMap[$leaveType])) {
            $emp = VendorEmployee::find($request->post('emp_id'));
            $store_config = StoreConfig::where('store_id', $v_id)->first();
            $pool = $allowanceMap[$leaveType]['pool'];
            $cost = $allowanceMap[$leaveType]['cost'];
            $allowed = $pool === 'cl'
                ? ($emp->cl_allowance ?? ($store_config->cl_for_employees ?? 0))
                : ($emp->sl_allowance ?? ($store_config->sl_for_employees ?? 0));
            $counts = _attendanceMonthCounts($v_id, $request->post('emp_id'), $request->post('year'), $request->post('month'));
            $taken = $pool === 'cl'
                ? ($counts['CL'] + 0.5 * $counts['HCL'])
                : ($counts['SL'] + 0.5 * $counts['HSL']);
            if ($taken + $cost > $allowed) {
                echo json_encode(['status' => false, 'msg' => strtoupper($pool) . ' balance exhausted for this month (allowed: ' . $allowed . ', used: ' . $taken . '). Mark it as a different type or increase the allowance.']);
                return;
            }
        }

        if (!$leave) {

            $leave_date = sprintf('%04d-%02d-%02d', $request->post('year'), $request->post('month'), $request->post('day'));

            $leave = new Leave;
            $leave->vendor_id = $v_id;
            $leave->emp_id = $request->post('emp_id');
            $leave->day = $request->post('day');
            $leave->status = 'approved';
            $leave->added_by = 'vendor';
            $leave->month = $request->post('month');
            $leave->year = $request->post('year');
            $leave->leave_date = $leave_date;
            $leave->leave_type = $request->post('leaveType');
            $leave->reason = $request->post('reason');
            $leave->created_at = date('Y-m-d H:i:s');
            $leave->save();

            // Attendance = canonical record. Keep the concrete half-day code (no generic 'HD'),
            // tag employee_type so counts see it, and upsert so a day never gets duplicate rows.
            $label = $request->post('leaveType') === 'HD' ? 'HDS' : $request->post('leaveType');
            Attendance::updateOrCreate(
                [
                    'vendor_id'     => $v_id,
                    'employee_id'   => $request->post('emp_id'),
                    'employee_type' => 'vendor_employee',
                    'date'          => $leave_date,
                ],
                [
                    'label' => $label,
                    'day'   => $request->post('day'),
                    'month' => $request->post('month'),
                    'year'  => $request->post('year'),
                ]
            );
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
