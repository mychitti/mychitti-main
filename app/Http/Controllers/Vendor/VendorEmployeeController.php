<?php

namespace App\Http\Controllers\Vendor;

use App\Models\EmployeeRole;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\VendorEmployee;
use App\Models\Department;
use App\Models\EmployeeTimeCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Exports\StoreEmployeeListExport;
use Illuminate\Validation\Rules\Password;
use App\Models\Leave;
use App\Models\Attendance;
use App\Models\StoreConfig;

class VendorEmployeeController extends Controller
{

    public function clock_in()
    { 
        $emp_id = Helpers::get_loggedin_user()->id;
        $v_id = Helpers::get_store_id();

        $obj  = new EmployeeTimeCard;
        $obj->emp_id = $emp_id;
        $obj->vendor_id = $v_id;
        $obj->in_time = date('Y-m-d H:i:s');
        $obj->date = date('Y-m-d');
        $obj->created_at = date('Y-m-d H:i:s');

        $att = Attendance::where('employee_id', $emp_id)->where('employee_type', 'vendor_employee')->where('vendor_id', $v_id)->where('date', date('Y-m-d'))->first();
        if (!$att) {
            $att = new Attendance;
            $att->employee_id = $emp_id;
            $att->vendor_id = $v_id;
            $att->employee_type = 'vendor_employee';
            $att->date = date('Y-m-d');
            $att->day = date('d');
            $att->month = date('m');
            $att->year = date('Y');
        }
        $att->label = 'P';
        $att->created_at = date('Y-m-d H:i:s');
        $att->save();


        if ($obj->save()) {
            return response()->json(['status' => true]);
        } else {
            return response()->json(['status' => false]);
        };
    }
    
    public function clock_out()
    {
        $emp_id = Helpers::get_loggedin_user()->id;
        $v_id = Helpers::get_store_id();
        $today_attendance = EmployeeTimeCard::where('emp_id', $emp_id)
                ->where('vendor_id', Helpers::get_store_id() )

            ->orderBy('id', 'desc')
            ->limit(1)
            ->first();
        $obj  =  EmployeeTimeCard::find($today_attendance->id);

        $obj->emp_id = $emp_id;
        $obj->vendor_id = $v_id;
        $obj->out_time =  date('Y-m-d H:i:s');

        // Snapshot worked + extra-duty (overtime) at clock-out against the shift in effect NOW,
        // so the value is preserved even if the staff member's shift hours change later.
        if (!Schema::hasColumn('employee_time_cards', 'overtime_seconds')) {
            DB::statement('ALTER TABLE `employee_time_cards` ADD COLUMN `worked_seconds` INT NULL, ADD COLUMN `overtime_seconds` INT NULL');
        }
        if ($obj->in_time) {
            $emp   = Helpers::get_loggedin_user();
            $shift = $emp->storeShift ?? null;
            if ($shift && $shift->start_time && $shift->end_time) {
                $s = \Carbon\Carbon::parse('2000-01-01 ' . substr($shift->start_time, 0, 8));
                $e = \Carbon\Carbon::parse('2000-01-01 ' . substr($shift->end_time, 0, 8));
                if ($e->lessThanOrEqualTo($s)) { $e->addDay(); }
                $shiftSeconds = $s->diffInSeconds($e);
            } else {
                $shiftSeconds = 9 * 3600;
            }
            $worked = max(0, strtotime($obj->out_time) - strtotime($obj->in_time));
            $obj->worked_seconds   = $worked;
            $obj->overtime_seconds = max(0, $worked - $shiftSeconds);
        }

        if ($obj->update()) {
            return response()->json(['status' => true]);
        } else {
            return response()->json(['status' => false]);
        };
    }

    public function leaves(Request $request)
    {
        // echo 'here'; die;
        $v_id = \App\CentralLogics\Helpers::get_store_id();
        $id = Helpers::get_loggedin_user()->id;
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
        $attendance = Attendance::where(['vendor_id' => $v_id, 'employee_id' => $id, 'employee_type' => 'vendor_employee', 'month' => $filter_month, 'year' => $filter_year])->get()->toArray();
        $leaves = Leave::where(['vendor_id' => $v_id, 'emp_id' => $id, 'month' => $filter_month, 'year' => $filter_year])->get()->toArray();
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
        $emp = \App\Models\VendorEmployee::find($id);
        $clAllowed = $emp->cl_allowance ?? ($store_config->cl_for_employees ?? 0);
        $slAllowed = $emp->sl_allowance ?? ($store_config->sl_for_employees ?? 0);
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

        return view('vendor-views.employee.leave', compact(
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
            'monthlyClleaveBalance',
            'monthlySlleaveBalance'
        ));
    }

    public function attendance(Request $request)
    {
        $emp_id = Helpers::get_loggedin_user()->id;
        if ($request->has('month')) {
            $currentmonth = $request->get('month');
        } else {
            $currentmonth = date('Y-m');
        }
        $attendance = EmployeeTimeCard::where('emp_id', $emp_id)
            ->where('date', '>=', $currentmonth . '-01')
            ->where('date', '<=', $currentmonth . '-31')
                ->where('vendor_id', Helpers::get_store_id() )

            ->whereNotNull('out_time')
            ->whereNotNull('in_time')->get();

        // Shift length (seconds) for per-day extra-duty (overtime) calc — fallback 9h when no shift.
        $emp   = Helpers::get_loggedin_user();
        $shift = $emp->storeShift ?? null;
        $shiftName = $shift->name ?? null;
        if ($shift && $shift->start_time && $shift->end_time) {
            $s = \Carbon\Carbon::parse('2000-01-01 ' . substr($shift->start_time, 0, 8));
            $e = \Carbon\Carbon::parse('2000-01-01 ' . substr($shift->end_time, 0, 8));
            if ($e->lessThanOrEqualTo($s)) { $e->addDay(); }
            $shiftSeconds = $s->diffInSeconds($e);
        } else {
            $shiftSeconds = 9 * 3600;
        }

        return view('vendor-views.employee.attendance', compact('attendance', 'currentmonth', 'shiftSeconds', 'shiftName'));
    }

    public function my_salary_history(Request $request)
    {
        $all_salaries = DB::table('salaries')->where('employee_id', Helpers::get_loggedin_user()->id)->where('employee_type', 'vendor_employee')->get();
        return view('vendor-views.employee.salary-history', compact('all_salaries'));
    }

    public function leave_reject(Request $request, $id)
    {
        $obj = Leave::find($id);
        $obj->status = 'rejected';
        $obj->update();

        $leaveDate = $obj->day . '/' . $obj->month . '/' . $obj->year;
        _inAppNotification(
            'Leave Rejected',
            "Your leave request for {$leaveDate} ({$obj->leave_type}) has been rejected.",
            null,
            $obj->emp_id,
            null,
            'vendor_employee'
        );

        return redirect()->back();
    }

    public function leave_approve(Request $request, $id)
    {
        $obj = Leave::find($id);
        // Stamp the leave's OWN date with its leave type (canonical half-day code), upserting
        // so the day isn't duplicated and isn't wrongly marked Present.
        $date = $obj->leave_date ?: sprintf('%04d-%02d-%02d', $obj->year, $obj->month, $obj->day);
        $label = $obj->leave_type === 'HD' ? 'HDS' : $obj->leave_type;
        Attendance::updateOrCreate(
            [
                'employee_id'   => $obj->emp_id,
                'employee_type' => 'vendor_employee',
                'vendor_id'     => $obj->vendor_id,
                'date'          => $date,
            ],
            [
                'label' => $label,
                'day'   => $obj->day,
                'month' => $obj->month,
                'year'  => $obj->year,
            ]
        );

        $obj->status = 'approved';
        $obj->update();

        $leaveDate = $obj->day . '/' . $obj->month . '/' . $obj->year;
        _inAppNotification(
            'Leave Approved',
            "Your leave request for {$leaveDate} ({$obj->leave_type}) has been approved.",
            null,
            $obj->emp_id,
            null,
            'vendor_employee'
        );

        return redirect()->back();
    }

    public function leave_save(Request $request)
    {

        // $id = $request->post('staff_id');


        // Attendance::where(['month'=> $request->post('month'), 'year' =>  $request->post('year')])->delete();


        $v_id = \App\CentralLogics\Helpers::get_store_id();

        $leave = new Leave;
        $leave->vendor_id = $v_id;
        $leave->employee_type = 'vendor_employee';
        $leave->emp_id =  Helpers::get_loggedin_user()->id;
        $leave->day = $request->post('day');
        $leave->month = $request->post('month');
        $leave->year = $request->post('year');
        $leave->added_by = 'vendor_employee';
        $leave->status = 'pending';
        $leave->leave_type = $request->post('leaveType');
        $leave->reason = $request->post('reason');
        $leave->created_at = date('Y-m-d H:i:s');
        $leave->leave_date = $request->post('year') . '-' . $request->post('month') . '-' . $request->post('day');
        $leave->save();

        Toastr::success('Leave Request saved successfully');

        $data['status'] = true;
        $data['msg'] = 'Updated Successfully';

        echo json_encode($data);
    }
}
