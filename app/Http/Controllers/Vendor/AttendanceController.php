<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Lead;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\Department;
use App\Models\Attendance;
use App\Models\Staff;
use App\Models\Leave;
use App\Models\StoreConfig;
use App\Exports\AttendanceExport;
use App\Models\EmployeeTimeCard;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\VendorEmployee;
use DateInterval;
use DatePeriod;
use DateTime; 
use Carbon\Carbon;
use Carbon\CarbonInterval;

class AttendanceController extends Controller
{


    public function index(Request $request)
    {
        $v_id = Helpers::get_store_id();

        // Unified "Attendance & Leave" workspace — one page, front-end tabs.
        $staff = VendorEmployee::where('store_id', $v_id)->with(['role'])->latest()->get();
        $store_config = StoreConfig::where('store_id', $v_id)->first();
        $leaves = Leave::with('employee')
            ->where('vendor_id', $v_id)
            ->where('employee_type', 'vendor_employee')
            ->latest()
            ->get();

        return view('vendor-views.attendance.index', compact('staff', 'leaves', 'store_config'));
    }


    public function report(Request $request)
    {

        $fromdate = $request->from ?? date('Y-m-d');
        $todate = $request->to ?? date('Y-m-d');

        $staff =  VendorEmployee::where('store_id', Helpers::get_store_id())->get()->toArray();

        $startDate = new DateTime($fromdate);
        $endDate = new DateTime($todate);
        $endDate->modify('+1 day');
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($startDate, $interval, $endDate);

        // Batch-fetch the whole range once, then group in PHP (avoids a query per staff per day).
        $attRows = Attendance::where('vendor_id', Helpers::get_store_id())
            ->where('employee_type', 'vendor_employee')
            ->whereBetween('date', [$fromdate, $todate])->get();
        $grouped = [];
        foreach ($attRows as $r) {
            $grouped[$r->employee_id][Carbon::parse($r->date)->format('Y-m-d')][] = $r->toArray();
        }

        foreach ($staff as $key => $value) {
            $lev = 0;
            $present = 0;
            $absent = 0;
            foreach ($period as $date) {
                $dt = $date->format('Y-m-d');
                $staff[$key][$dt] = $grouped[$value['id']][$dt] ?? [];
                $label = $staff[$key][$dt][0]['label'] ?? null;
                if (in_array($label, ['CL', 'SL'])) {
                    $lev++;
                } elseif ($label === 'A') {
                    $absent++;
                } elseif ($label === 'P') {
                    $present++;
                }
            }
            $staff[$key]['casual_leaves'] = $lev;
            $staff[$key]['absent_days'] = $absent;
            $staff[$key]['present_days'] = $present;
        }
        //format dates 
        $formattedDate = [];
        $dates = [];
        foreach ($period as $date) {
            array_push($formattedDate, $date->format('d M'));
            array_push($dates, $date->format('Y-m-d'));
        }

        return view('vendor-views.attendance.report', compact('staff', 'fromdate', 'todate', 'dates', 'formattedDate'));
    }

    public function export(Request $request)
    {
        $fromdate = $request->from ?? date('Y-m-d');
        $todate = $request->to ?? date('Y-m-d');

        $staff =  VendorEmployee::where('store_id', Helpers::get_store_id())->get()->toArray();

        $startDate = new DateTime($fromdate);
        $endDate = new DateTime($todate);
        $endDate->modify('+1 day');
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($startDate, $interval, $endDate);

        // Batch-fetch the whole range once, then group in PHP (avoids a query per staff per day).
        $attRows = Attendance::where('vendor_id', Helpers::get_store_id())
            ->where('employee_type', 'vendor_employee')
            ->whereBetween('date', [$fromdate, $todate])->get();
        $grouped = [];
        foreach ($attRows as $r) {
            $grouped[$r->employee_id][Carbon::parse($r->date)->format('Y-m-d')][] = $r->toArray();
        }

        foreach ($staff as $key => $value) {
            $lev = 0;
            $present = 0;
            $absent = 0;
            foreach ($period as $date) {
                $dt = $date->format('Y-m-d');
                $staff[$key][$dt] = $grouped[$value['id']][$dt] ?? [];
                $label = $staff[$key][$dt][0]['label'] ?? null;
                if (in_array($label, ['CL', 'SL'])) {
                    $lev++;
                } elseif ($label === 'A') {
                    $absent++;
                } elseif ($label === 'P') {
                    $present++;
                }
            }
            $staff[$key]['casual_leaves'] = $lev;
            $staff[$key]['absent_days'] = $absent;
            $staff[$key]['present_days'] = $present;
        }
        //format dates 
        $dates = [];
        $headings =  ['Employee', 'P', 'A', 'L'];
        foreach ($period as $date) {
            array_push($headings, $date->format('d M'));
            array_push($dates, $date->format('Y-m-d'));
        }
        $data = [];
        foreach ($staff as $key => $lead) {
            $data[$key] = [
                $lead['f_name'] . ' ' . $lead['l_name'],
                $lead['present_days'],
                $lead['absent_days'],
                $lead['casual_leaves']
            ];

            foreach ($dates as $date) {
                array_push($data[$key], !empty($lead[$date]) ? $lead[$date][0]['label'] : '-');
            }
        }

        return Excel::download(new AttendanceExport($data, $headings), 'attendance.xlsx');
    }



    public function manage(Request $request, $id)
    {
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

        // Per-day duty / overtime from the staff member's clock-in/out punches.
        $dutyHistory = \App\Models\Attendance::dutyHistory($staff, $v_id, sprintf('%04d-%02d', $filter_year, $filter_month));

        $attendance = Attendance::where(['vendor_id' => $v_id, 'employee_type' => 'vendor_employee',  'employee_id' => $id, 'month' => $filter_month, 'year' => $filter_year])->get()->toArray();

        $day_data['absent'] = 0;
        $day_data['present'] = 0;
        $day_data['holiday'] = 0;
        $day_data['cl'] = 0;
        $day_data['sl'] = 0;
        $day_data['halfday'] = 0;
        $day_data['sunday'] = 0;
        $labelArr = [];
        $daArr = [];

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
                $day_data['cl'] += 0.5;
            }
            if ($att['label'] == 'SL') {
                $day_data['sl']++;
            }
            if ($att['label'] == 'HSL') {
                $day_data['sl'] += 0.5;
            }
            if (in_array($att['label'], ['HDF', 'HDS', 'HCL', 'HSL'])) {
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

        // logs ==================
        if ($request->has('month')) {
            $currentmonth = $request->get('month');
        } else {
            $currentmonth = date('Y-m');
        }
        $attendanceLogs = EmployeeTimeCard::where('emp_id', $id)
            ->where('date', '>=', $currentmonth . '-01')
            ->where('date', '<=', $currentmonth . '-31')
                ->where('vendor_id', Helpers::get_store_id() )

            ->whereNotNull('out_time')
            ->whereNotNull('in_time')->get();

        $data['late_arrivals'] = 0;
        $data['early_departures'] = 0;
        $total_time = 0;

        // Shift-based thresholds (fall back to legacy 10:00–19:00, no grace, Sunday-off
        // when the staff has no shift assigned or the shift omits these fields).
        $shift        = $staff ? $staff->shift : null;
        $shiftStart   = $shift && $shift->start_time ? substr($shift->start_time, 0, 8) : '10:00:00';
        $shiftEnd     = $shift && $shift->end_time ? substr($shift->end_time, 0, 8) : '19:00:00';
        $graceMinutes = $shift && $shift->grace_minutes !== null ? (int) $shift->grace_minutes : 0;

        $data['time_worked'] = CarbonInterval::seconds(0);

        foreach ($attendanceLogs as $value) {
            $lateThreshold = Carbon::parse($value->date . ' ' . $shiftStart)->addMinutes($graceMinutes);
            if (Carbon::parse($value->in_time)->gt($lateThreshold)) {
                $data['late_arrivals']++;
            }
            if (Carbon::parse($value->out_time)->lt(Carbon::parse($value->date . ' ' . $shiftEnd))) {
                $data['early_departures']++;
            }
            $inTime = Carbon::parse($value->in_time);
            $outTime = Carbon::parse($value->out_time);

            $timeWorked = $inTime->diffInSeconds($outTime);

            $data['time_worked'] = $data['time_worked']->addSeconds($timeWorked);
        }

        $totalHours = $data['time_worked']->totalHours;
        $totalMinutes = $data['time_worked']->totalMinutes;

        $data['time_worked'] =  $data['time_worked']->cascade()->forHumans(['short' => true, 'parts' => 2]);

        // $totalHours = $data['time_worked']->hours + ($data['time_worked']->days * 24);
        // $totalMinutes = $data['time_worked']->minutes;
        // echo "Total time worked: {$totalHours} hours {$totalMinutes} minutes";

        if ($request->has('month')) {
            $currentmonth = $request->get('year') . '-'. $request->get('month'); // expected format: YYYY-MM
        } else {
            $currentmonth = date('Y-m');
        }

        // Extract year and month from string like "2025-07"
        [$year, $month] = explode('-', $currentmonth);

        $start = new DateTime("$year-$month-01");
        $end = new DateTime($start->format('Y-m-t')); // last day of the month
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

        // Week-offs come from the shift's working_days (off = any weekday not worked).
        // No shift / unparseable value → legacy behaviour (Sunday is the only week-off).
        $offDays = $this->shiftOffDays($shift);
        $sundays = 0;
        foreach ($period as $date) {
            if (in_array((int) $date->format('w'), $offDays, true)) {
                $sundays++;
            }
        }
        $sundays_in_month = $sundays;


        return view('vendor-views.attendance.manage', compact(
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
            'attendanceLogs',
            'currentmonth',
            'data',
            'dutyHistory'

        ));
    }

    // Returns the week-off weekdays as PHP 'w' values (0=Sun..6=Sat) for a shift,
    // derived from working_days (any weekday NOT worked is a week-off). Accepts a JSON
    // array, a comma list, day numbers (ISO 1-7 or 0-6) or names (mon/tue/...).
    // Falls back to [0] (Sunday) when no shift or working_days can't be resolved.
    private function shiftOffDays($shift): array
    {
        $raw = $shift ? $shift->working_days : null;
        if (empty($raw)) {
            return [0];
        }

        $tokens = is_array($raw) ? $raw : (json_decode($raw, true) ?: explode(',', $raw));
        $nameMap = ['sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6];

        $working = [];
        foreach ($tokens as $t) {
            $t = strtolower(trim((string) $t));
            if ($t === '') {
                continue;
            }
            if (is_numeric($t)) {
                $working[] = ((int) $t) % 7; // ISO 7=Sun→0, 0-6 unchanged
            } elseif (isset($nameMap[substr($t, 0, 3)])) {
                $working[] = $nameMap[substr($t, 0, 3)];
            }
        }
        $working = array_unique($working);
        if (empty($working)) {
            return [0];
        }

        return array_values(array_diff([0, 1, 2, 3, 4, 5, 6], $working));
    }

    public function save_att(Request $request)
    {

        $id = $request->post('emp_id');
        $v_id = \App\CentralLogics\Helpers::get_store_id();

        // Atomic: the month rebuild (clear + re-insert) all-or-nothing, so a mid-way
        // failure can never leave the month half-wiped.
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $id, $v_id) {
            Attendance::where(['month' => $request->post('month'), 'employee_id' => $id, 'employee_type' => 'vendor_employee', 'vendor_id' => $v_id, 'year' => $request->post('year')])->delete();

            foreach ($request->post('daysArr') as $key => $day) {
                // Normalise a generic half-day ('HD') to a concrete code so attendance,
                // leave and salary all read the same label.
                $rawStatus = $request->post('statusArr')[$key];
                $status = $rawStatus === 'HD' ? 'HDS' : $rawStatus;
                $date = $request->post('year') . '-' . $request->post('month') . '-' . $day;

                $att = new Attendance;
                $att->vendor_id = $v_id;
                $att->employee_type = 'vendor_employee';
                $att->employee_id = $id;
                $att->date = $date;
                $att->label = $status;
                $att->day = $day;
                $att->month = $request->post('month');
                $att->year = $request->post('year');
                $att->created_at = date('Y-m-d H:i:s');
                $att->save();
                // Attendance is the single source of truth — no separate Leave row written here.
                // CL/SL/half-day counts are derived from these labels (see _attendanceMonthCounts).
            }
        });


        Toastr::success('Attendance Information saved successfully');

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
