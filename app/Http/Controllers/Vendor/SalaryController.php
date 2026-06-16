<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Salary;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Exports\SalaryExport;
use App\Exports\AllSalaryExport;
use App\Models\AdvanceRequest;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Staff;
use App\Models\StoreTask;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class SalaryController extends Controller
{ 

    public function index(Request $request)
    {
        $month_year = $request->month ?? date('Y-m');
        $v_id = Helpers::get_store_id();
        $salary = DB::table('vendor_employees')
            ->leftJoin('salaries', function ($join) use ($month_year) {
                $join->on('vendor_employees.id', '=', 'salaries.employee_id')
                    ->where('salaries.employee_type', '=', 'vendor_employee')
                    ->where('salary_month', $month_year);
            })
            ->where('vendor_employees.store_id', $v_id)
            ->select('vendor_employees.id as ven_id', 'vendor_employees.f_name', 'vendor_employees.l_name', 'vendor_employees.salary_type', 'vendor_employees.base_salary as emp_base_salary', 'salaries.*')
            ->get();

        // For months not yet generated, show a live PREVIEW (computed from attendance, leaves,
        // allowances & advances) instead of zeros — same maths the "Generate" button will save.
        [$pvYear, $pvMonth] = explode('-', $month_year);
        $empById = VendorEmployee::where('store_id', $v_id)->get()->keyBy('id');
        foreach ($salary as $row) {
            if (empty($row->id) && ($emp = $empById->get($row->ven_id))) {
                $calc = $this->computeSalaryForMonth($emp, $pvYear, $pvMonth);
                $row->base_salary                = $calc['base_salary'];
                $row->payable_salary             = $calc['payable_salary'];
                $row->allowance_amount           = $calc['allowance_amount'];
                $row->deductions                 = $calc['deductions'];
                $row->bonus_incentives           = $calc['bonus_incentives'];
                $row->advance_payment_deductions = $calc['advance_payment_deductions'];
                $row->total_payable              = $calc['total_payable'];
                $row->is_preview                 = true;
            }
        }

        // Unified "Salary Management" workspace — one page, front-end tabs (Payroll / Advance / Reports).
        // NOTE: no with('employee') — the employee() relation switches table on $this->store_id,
        // which eager-loading resolves on a blank instance (store_id null == 0 → wrong Admin table).
        // Lazy-loading per row keeps the correct VendorEmployee resolution.
        $advance_requests = AdvanceRequest::where('store_id', $v_id)->latest()->get();
        $staff = VendorEmployee::where('store_id', $v_id)->where('status', 1)->get();

        return view('vendor-views.salary.index', compact('salary', 'month_year', 'advance_requests', 'staff'));
    }
    public function pay(Request $request)
    {
        $id = $request->salary_id;
        $salary = Salary::where('id', $id)->where('vendor_id', Helpers::get_store_id())->first();
        if ($salary && $salary->pay_status != 'paid') {

            if (!empty($request->file('file'))) {
                $extension = $request->file('file')->getClientOriginalExtension(); // e.g. jpg, pdf, png
                $file = Helpers::upload('vendor/documents/', $extension, $request->file('file'));
                $salary->pay_reciept = $file;
            }

            $salary->pay_status = 'paid';
            $salary->update();

            if ($salary->total_payable > 0) {
                //ledger entry
                $storeId = Helpers::get_store_id();
                $debit_account = Helpers::ensureSalaryLedger($storeId); // Debit
                $credit_account = Helpers::ensureBankAccount();
                $data = [
                    'date' => now(),
                    'amount' => $salary->total_payable,
                    'voucher_type' => 'Payment',
                    'status' => 'approved',
                    'description' => 'Salary',
                ];
                $voucher =  _masterLedgerEntry($data, $credit_account, $debit_account, 'store', 'employee', null);
                _saveDayBookEntry($salary->total_payable, 'debit', $storeId, "Salary Payment of " . $salary->employee->f_name . ' ' . $salary->employee->l_name, null, $voucher?->id);
            }

            if ($salary) {
                $title = 'Salary Recieved';
                $msg = 'You have recieved salary for month ' . _monthNYear($salary->salary_month);
                $to = $salary->employee_id;
                $url = '';

                _inAppNotification($title, $msg, $assignment_id = null, $to, $url, 'vendor_employee');
                _sendMailToStaff($title, $msg, $to, $url);

                Toastr::success('Salary Information saved successfully');
            } else {
                Toastr::warning('Some Error Occured');
            }
        }
        return back();
    }
    /**
     * Advance recovery for a given payroll month: deducts one installment per month
     * across `installments` months starting from repayment_start_date, never exceeding
     * the approved amount (the final installment absorbs any rounding remainder).
     */
    private function advanceDeductionForMonth($advance, $year, $month): float
    {
        if (!$advance || $advance->approved_amount <= 0) {
            return 0;
        }
        $installments  = max(1, (int) $advance->installments);
        $perInstallment = $advance->approved_amount / $installments;
        $start   = Carbon::parse($advance->repayment_start_date ?: $advance->created_at)->startOfMonth();
        $current = Carbon::create((int) $year, (int) $month, 1)->startOfMonth();
        $elapsed = $start->diffInMonths($current, false); // 0 on the repayment-start month
        if ($elapsed < 0 || $elapsed >= $installments) {
            return 0;
        }
        if ($elapsed === $installments - 1) {
            return round($advance->approved_amount - round($perInstallment, 2) * ($installments - 1), 2);
        }
        return round($perInstallment, 2);
    }

    // Sum the month's installment across ALL of an employee's approved advances —
    // each advance repays on its own schedule, so a staff member can have several running at once.
    private function advanceDeductionsForMonth($empId, $year, $month): float
    {
        $advances = AdvanceRequest::where('employee_id', $empId)
            ->where('status', 'approved')
            ->where('approved_amount', '>', 0)
            ->get();

        $total = 0;
        foreach ($advances as $advance) {
            $total += $this->advanceDeductionForMonth($advance, $year, $month);
        }
        return round($total, 2);
    }

    // Compute (without persisting) an employee's salary breakdown for a month — the single
    // source of truth used by BOTH payroll generation and the on-screen preview, so the
    // preview always equals what "Generate" will save.
    private function computeSalaryForMonth($empInfo, $year, $month): array
    {
        $salary_type = $empInfo->salary_type;
        $base_salary = (float) ($empInfo->base_salary ?? 0);
        $bonus       = (float) ($empInfo->bonus_incentives ?? 0);
        $deductions  = (float) ($empInfo->deductions ?? 0);

        $payable_salary  = 0;
        $total_allowance = 0;
        $allowances      = [];

        $total_days_in_month = cal_days_in_month(CAL_GREGORIAN, (int) $month, (int) $year);
        // Loss-of-pay days only (CL/SL within allowance are paid). Single source: Attendance.
        $vacation_or_leave = _payrollLopDays($empInfo->store_id, $empInfo, $year, $month);

        // Pay only for days that have actually elapsed. A current (in-progress) month is
        // prorated up to today; a finished month uses all its days; a future month earns nothing.
        $now           = Carbon::now();
        $firstOfTarget = Carbon::create((int) $year, (int) $month, 1);
        $effective_days = $total_days_in_month;
        if ($firstOfTarget->isSameMonth($now)) {
            $effective_days = (int) $now->day;
        } elseif ($firstOfTarget->greaterThan($now)) {
            $effective_days = 0;
        }
        $worked_days = max(0, $effective_days - $vacation_or_leave);

        if ($salary_type === 'Monthly') {
            $per_day_salary = $total_days_in_month > 0 ? $base_salary / $total_days_in_month : 0;
            $payable_salary = $per_day_salary * $worked_days;
        } elseif ($salary_type === 'Hourly') {
            $startOfMonth = Carbon::create($year, $month)->startOfMonth();
            $endOfMonth   = Carbon::create($year, $month)->endOfMonth();
            $records = DB::table('employee_time_cards')
                ->where('vendor_id', $empInfo->store_id)
                ->where('emp_id', $empInfo->id)
                ->whereBetween('in_time', [$startOfMonth, $endOfMonth])
                ->get();
            $totalSeconds = 0;
            foreach ($records as $record) {
                if ($record->in_time && $record->out_time) {
                    $in  = Carbon::parse($record->in_time);
                    $out = Carbon::parse($record->out_time);
                    if ($out->greaterThan($in)) {
                        $totalSeconds += $out->diffInSeconds($in);
                    }
                }
            }
            if ($totalSeconds > 0) {
                $workedHours = gmdate('H:i', $totalSeconds);
                [$h, $m] = explode(':', $workedHours);
                $total_hours = ((int) $h) + (((int) $m) / 60);
                $payable_salary = $base_salary * $total_hours;
            }
        } elseif ($salary_type === 'Task-Wise') {
            $payable_salary = $base_salary;
        }

        $monthlyAllowances = [];
        if (!empty($empInfo->monthly_allowances)) {
            if (is_string($empInfo->monthly_allowances)) {
                $decoded = json_decode($empInfo->monthly_allowances, true);
                $monthlyAllowances = is_array($decoded) ? $decoded : [];
            } elseif (is_array($empInfo->monthly_allowances)) {
                $monthlyAllowances = $empInfo->monthly_allowances;
            }
        }
        foreach ($monthlyAllowances as $item) {
            $value = (float) ($item['amount'] ?? 0);
            if (in_array($salary_type, ['Monthly', 'Task-Wise'])) {
                $per_day_allowance = $total_days_in_month > 0 ? $value / $total_days_in_month : 0;
                $value = $per_day_allowance * $worked_days;
            }
            $allowances[] = ['title' => $item['title'] ?? '', 'amount' => round($value, 3)];
            $total_allowance += $value;
        }

        $advance_payment_deductions = $this->advanceDeductionsForMonth($empInfo->id, $year, $month);

        $total_payable = $payable_salary + $total_allowance - $deductions - $advance_payment_deductions + $bonus;

        return [
            'base_salary'                => $base_salary,
            'payable_salary'             => round($payable_salary, 3),
            'allowance_amount'           => round($total_allowance, 3),
            'total_payable'              => round($total_payable, 3),
            'bonus_incentives'           => $bonus,
            'deductions'                 => $deductions,
            'advance_payment_deductions' => $advance_payment_deductions,
            'allowance'                  => json_encode($allowances),
        ];
    }

    public function edit(Request $request, $id)
    {
        $month_year = $request->month ?? date('Y-m');
        $salary = DB::table('vendor_employees')
            ->leftJoin('salaries', function ($join) {
                $join->on('vendor_employees.id', '=', 'salaries.employee_id')
                    ->where('salaries.employee_type', '=', 'vendor_employee');
            })
            ->where('vendor_employees.id', $id)
            ->select('vendor_employees.id as ven_id', 'vendor_employees.*', 'salaries.*')
            ->first();

        // leaves calc
        $month = explode('-', $month_year)[1] ?? date('m');
        $year = explode('-', $month_year)[0] ?? date('Y');
        $empInfo = VendorEmployee::find($id);

        // Loss-of-pay days only (CL/SL within allowance are paid). Single source: Attendance.
        $data['vacation_or_leave'] = _payrollLopDays(Helpers::get_store_id(), $empInfo, $year, $month);

        if ($empInfo->salary_type == 'Hourly') {
            $startOfMonth = Carbon::create((int)$year, (int)$month, 1)->startOfMonth();
            $endOfMonth = (clone $startOfMonth)->endOfMonth();

            $records = DB::table('employee_time_cards')
                ->where('emp_id', $empInfo->id)
                ->where('vendor_id', Helpers::get_store_id())
                ->whereBetween('in_time', [$startOfMonth, $endOfMonth])
                ->get();

            $totalSeconds = 0;

            foreach ($records as $record) {
                if ($record->in_time && $record->out_time) {
                    $in = Carbon::parse($record->in_time);
                    $out = Carbon::parse($record->out_time);

                    $totalSeconds += $out->diffInSeconds($in);
                }
            }

            $workedHours = gmdate('H:i', $totalSeconds);
            $data['hours_worked'] = $workedHours;
        } else if ($empInfo->salary_type == 'Task-Wise') {

            $tasks = StoreTask::where('employee_id', $empInfo->id)
                ->where('status', 'Completed')
                ->get();

            $totalAmount = $tasks->sum('task_amount');


            $data['tasks_done'] = count($tasks);
            $data['tasks_amount'] = $totalAmount;
        }
        $all_salaries = DB::table('salary_transactions')->where('employee_id', $id)->where('employee_type', 'vendor_employee')->get();


        // advance payment — sum this month's installment across all approved advances
        $salary->advance_deductions = $this->advanceDeductionsForMonth($id, $year, $month);
        return view('vendor-views.salary.manage', compact('empInfo', 'month_year', 'salary', 'all_salaries', 'data'));
    }
    public function mark_paid(Request $request)
    {
        $month_year = $request->month ?? date('Y-m');
        $paidAmount = 0;
        $salaries = Salary::where('salary_month', $month_year)
            ->where('vendor_id', Helpers::get_store_id())
            ->where('employee_type', 'vendor_employee')
            ->get();

        $totalAmount = $salaries->filter(function ($salary) {
            return $salary->pay_status !== 'paid';
        })->sum('total_payable');

        foreach ($salaries as $salary) {
            if ($salary->pay_status != 'paid') {
                $paidAmount += $salary->total_payable;
                $salary->pay_status = 'paid';
                $salary->update();

                if ($salary) {
                    $title = 'Salary Recieved';
                    $msg = 'You have recieved salary for month ' . _monthNYear($salary->salary_month);
                    $to = $salary->employee_id;
                    $url = '';

                    _inAppNotification($title, $msg, $assignment_id = null, $to, $url, 'vendor_employee');
                    _sendMailToStaff($title, $msg, $to, $url);
                }
            }
        }

        //ledger entry
        if ($totalAmount > 0) {
            $storeId = Helpers::get_store_id();
            $debit_account = Helpers::ensureSalaryLedger($storeId); // Debit
            $credit_account = Helpers::ensureBankAccount();
            $data = [
                'date' => now(),
                'amount' => $totalAmount,
                'voucher_type' => 'Payment',
                'status' => 'approved',
                'description' => 'Salary',
            ];
            $voucher  =  _masterLedgerEntry($data, $credit_account, $debit_account, 'store', 'other', null);
            // day book entry
            _saveDayBookEntry($paidAmount, 'debit', Helpers::get_store_id(), "Salary Payment", null, $voucher?->id);
        }
        return redirect()->back();
    }

    public function export(Request $request)
    {
        $id = $request->id;
        $headings =  ['Base Salary', 'Month', '	Deductions', 'Leaves', 'Paid Amount'];
        $all_salaries = DB::table('salary_transactions')->where('employee_id', $id)->where('employee_type', 'vendor_employee')->get();
        $data = [];
        foreach ($all_salaries as $key => $salary) {
            $data[$key] = [
                $salary->base_salary,
                $salary->salary_month,
                $salary->deductions,
                $salary->vacation_or_leave,
                $salary->temp_calculated,
            ];
        }

        return Excel::download(new SalaryExport($data, $headings), 'salary.xlsx');
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
    public function get_info(Request $request)
    {
        // echo $request->id; 
        $salary_info = Salary::where('employee_id', $request->id)->where('employee_type', 'vendor_employee')->where('vendor_id', Helpers::get_store_id())->get();
        return response()->json(['data' => $salary_info]);
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

    public function export_salaries(Request $request)
    {

        if ($request->has('month')) {
            $month_year = $request->month;
        } else {
            $month_year = date("Y-m");
        }

        // leaves calc

        $v_id = Helpers::get_store_id();
        $salary = DB::table('vendor_employees')
            ->join('salaries', function ($join) use ($month_year) {
                $join->on('vendor_employees.id', '=', 'salaries.employee_id')
                    ->on('vendor_employees.vendor_id', '=', 'salaries.vendor_id')
                    ->where('salaries.salary_month', '=', $month_year);
            })
            ->where('vendor_employees.vendor_id', $v_id)
            ->select('vendor_employees.f_name', 'vendor_employees.l_name', 'salaries.*')
            ->get();

        foreach ($salary as $key => $value) {

            $data[$key] = [
                $value->f_name . ' ' . $value->l_name,
                $value->base_salary,
                $value->payable_salary,
                $value->allowance_amount,
                $value->deductions,
                $value->bonus_incentives,
                $value->total_payable,
                $value->pay_status,
                _monthNYear($month_year),
            ];
            // prx( $data);
        }

        $headings =  ['Employee Name', 'Base Salary', 'Payable Salary', 'Allowance', 'Deductions',  'Bonus Incentives', 'Total Payable', 'Pay Status', 'Month'];

        return Excel::download(new AllSalaryExport($data, $headings), 'Salary_Report_' . $month_year . '.xlsx');
    }
    public function report(Request $request)
    {
        if ($request->has('month')) {
            $month = $request->month;
        } else {
            $month = date("Y-m");
        }
        $v_id = Helpers::get_store_id();
        $monthEnd = Carbon::parse($month . '-01')->endOfMonth()->format('Y-m-d');
        $salary = DB::table('vendor_employees')
            ->join('salary_transactions', function ($join) {
                $join->on('vendor_employees.id', '=', 'salary_transactions.employee_id')
                    ->where('salary_transactions.employee_type', '=', 'vendor_employee');
            })
            ->where('vendor_employees.store_id', $v_id)
            ->whereBetween('salary_transactions.created_at', [$month . '-01 00:00:00', $monthEnd . ' 23:59:59'])
            ->select('vendor_employees.id as ven_id', 'vendor_employees.*', 'salary_transactions.*')
            ->get();
        foreach ($salary as $key => $value) {
            $allowance_amount = 0;
            if ($value->allowance) {
                $fkd = json_decode($value->allowance);
                foreach ($fkd as $key2 => $value2) {
                    $allowance_amount += $value2->amount;
                }
            }

            $salary[$key]->allowance_amount = $allowance_amount;
        }

        return view('vendor-views.salary.report', compact('salary', 'month'));
    }
    public function add()
    {
        $employees =  VendorEmployee::where('store_id', Helpers::get_store_id())->get();
        return view('vendor-views.salary.add', compact('employees'));
    }
    public function save_info(Request $request)
    {
        $id = $request->post('salary_id');

        $validator = Validator::make($request->all(), [
            'emp_id' => 'required',
        ], [
            'emp_id.required' => 'Please Select Employee',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $staff = $id ? Salary::find($id) : new Salary;

        $v_id = \App\CentralLogics\Helpers::get_store_id();
        $empInfo = VendorEmployee::find($request->emp_id);

        $salary_type = $empInfo->salary_type; // 'monthly', 'hourly', 'task_wise'
        $month = explode('-', $request->month)[1] ?? date('m');
        $year = explode('-', $request->month)[0] ?? date('Y');

        // Loss-of-pay days only (CL/SL within allowance are paid). Single source: Attendance.
        $vacation_or_leave = _payrollLopDays($v_id, $empInfo, $year, $month);

        $base_salary = $empInfo->base_salary ?? 0;
        $total_days_in_month = cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year);
        $payable_salary = 0;
        $total_allowance = 0;
        $allowances = [];

        // Get common inputs
        $bonus = (float) $request->input('bonus_incentives', 0);
        $deductions = (float) $request->input('deductions', 0);
        $advance_payment_deductions = (float) $request->input('advance_deductions', 0);

        // Salary calculation based on type
        if ($salary_type === 'Monthly') {
            $per_day_salary = $total_days_in_month ? ($base_salary / $total_days_in_month) : 0;
            $payable_salary = $base_salary - ($per_day_salary * $vacation_or_leave);

            if ($request->has('item_name')) {
                foreach ($request->item_name as $key => $title) {
                    $value = (float) $request->item_price[$key];
                    $per_day_allowance = $total_days_in_month ? ($value / $total_days_in_month) : 0;
                    $adjusted = $value - ($per_day_allowance * $vacation_or_leave);

                    $allowances[] = ['title' => $title, 'amount' => $value];
                    $total_allowance += $adjusted;
                }
            }
        } elseif ($salary_type === 'Hourly') {
            $startOfMonth = Carbon::create((int)$year, (int)$month, 1)->startOfMonth();
            $endOfMonth = (clone $startOfMonth)->endOfMonth();

            // fetch time card records for the selected month
            $records = DB::table('employee_time_cards')
                ->where('vendor_id', Helpers::get_store_id())
                ->where('emp_id', $empInfo->id)
                ->whereBetween('in_time', [$startOfMonth, $endOfMonth])
                ->get();

            $totalSeconds = 0;

            foreach ($records as $record) {
                if ($record->in_time && $record->out_time) {
                    $in = Carbon::parse($record->in_time);
                    $out = Carbon::parse($record->out_time);

                    // ensure out time is after in time
                    if ($out->greaterThan($in)) {
                        $totalSeconds += $out->diffInSeconds($in);
                    }
                }
            }

            $workedHours = gmdate('H:i', $totalSeconds);
            $base_salary = (float) $base_salary; // hourly rate
            $payable_salary = 0;

            if ($workedHours && preg_match('/^\d{1,2}:\d{2}$/', $workedHours)) {
                [$hours, $minutes] = explode(':', $workedHours);
                $total_hours = ((int)$hours) + (((int)$minutes) / 60);
                $payable_salary = $base_salary * $total_hours;
            }

            // Calculate allowances
            $total_allowance = 0;
            $allowances = [];

            if ($request->has('item_name')) {
                foreach ($request->item_name as $key => $title) {
                    $value = (float) $request->item_price[$key];
                    $allowances[] = ['title' => $title, 'amount' => $value];
                    $total_allowance += $value;
                }
            }
        } elseif ($salary_type === 'Task-Wise') {
            $payable_salary = 0;

            $tasks = StoreTask::where('employee_id', $empInfo->id)
                ->where('status', 'Completed')
                ->get();

            $totalAmount = $tasks->sum('task_amount');

            $payable_salary = $totalAmount;

            if ($request->has('item_name')) {
                foreach ($request->item_name as $key => $title) {
                    $value = (float) $request->item_price[$key];
                    $per_day_allowance = $total_days_in_month ? ($value / $total_days_in_month) : 0;
                    $adjusted = $value - ($per_day_allowance * $vacation_or_leave);

                    $allowances[] = ['title' => $title, 'amount' => $value];
                    $total_allowance += $adjusted;
                }
            }
        }

        // Save allowances JSON in emp profile
        $empInfo->monthly_allowances = json_encode($allowances);
        $empInfo->save();

        $total_payable = $payable_salary + $total_allowance + $bonus - $deductions - $advance_payment_deductions;

        $staff->vendor_id = $v_id;
        $staff->employee_id = $request->post('emp_id');
        $staff->base_salary = $base_salary;
        $staff->salary_month = $request->month;
        $staff->bonus_incentives = $bonus;
        $staff->payable_salary = $payable_salary;
        $staff->total_payable = $total_payable;
        $staff->allowance_amount = $total_allowance;
        // $staff->payable_salary = 43.433;
        // $staff->total_payable = 34.342;
        // $staff->allowance_amount = 23.233;
        // $staff->advance_payment_deductions = 34.324;
        $staff->advance_payment_deductions = $advance_payment_deductions;
        $staff->allowance = json_encode($allowances);
        $staff->deductions = $deductions;
        $staff->created_at = date('Y-m-d H:i:s');

        if ($id == '') {
            $staff->save();
            Toastr::success('Salary Information saved successfully');
        } else {
            $staff->update();
            Toastr::success('Salary Information updated successfully');
        }

        return redirect()->route('vendor.salary.list', ['month' => $request->month]);
    }


    public function generate_monthly(Request $request, $month = null)
    {
        $monthInput = $month ?? Carbon::now()->subMonth()->format('Y-m');
        [$year, $month] = explode('-', $monthInput);
        $employees = VendorEmployee::where('store_id', Helpers::get_store_id())->get();
        foreach ($employees as $empInfo) {
            try {

                // Regenerate semantics: recompute UNPAID rows from current attendance /
                // advances / pay, but NEVER touch a salary that's already been PAID (locked).
                $existingRows = Salary::where('employee_id', $empInfo->id)
                    ->where('employee_type', 'vendor_employee')
                    ->where('salary_month', "$year-$month")
                    ->get();
                $alreadyPaid = false;
                foreach ($existingRows as $row) {
                    if (($row->pay_status ?? null) === 'paid') {
                        $alreadyPaid = true;
                    } else {
                        $row->delete(); // unpaid → drop so it is recomputed fresh below
                    }
                }
                if ($alreadyPaid) {
                    continue; // a paid salary already exists for this month — leave it untouched
                }
                // Same calculation used by the on-screen preview — guarantees parity.
                $calc = $this->computeSalaryForMonth($empInfo, $year, $month);
                Salary::create(array_merge($calc, [
                    'vendor_id'    => $empInfo->store_id,
                    'employee_id'  => $empInfo->id,
                    'salary_month' => "$year-$month",
                    'generated_at' => now(),
                    'created_at'   => now(),
                ]));
            } catch (\Exception $e) {
                // Log and continue with the next employee — never halt the whole run.
                \Log::error('Salary generation failed', [
                    'employee_id' => $empInfo->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        Toastr::success('Generated Successfully');
        return back();
    }

    public function all_advance_requests(Request $request)
    { // store's
        $staff = VendorEmployee::where('store_id', Helpers::get_store_id())->where('status', 1)->get();
        $advance_requests = AdvanceRequest::where('store_id', Helpers::get_store_id())->get();
        return view('vendor-views.salary.advance-requests', compact('advance_requests', 'staff'));
    }
    public function lead_approval(Request $request)
    {
        $id = $request->post('lead_id');
        $lead = Lead::find($id);
        $lead->approval = $request->post('approval');
        $lead->save();
        return response()->json(['msg' => ucfirst($request->post('approval')) . 'ed', 'status' => true]);
    }

    public function advance_payment(Request $request)
    { // employee specific
        $emp = Helpers::get_loggedin_user();

        $advance_requests = AdvanceRequest::with('employee')->where('employee_id', $emp->id)->get();
        return view('vendor-views.salary.advance-requests', compact('advance_requests'));
    }
    public function advance_request_store(Request $request)
    {
        $rules = [
            'amount' => 'required|numeric',
            'required_on' => 'required',
        ];
        if (auth('vendor')->check()) {
            $rules['emp_id'] = 'required';
            $emp = VendorEmployee::find($request->emp_id);
        } else {
            $emp = Helpers::get_loggedin_user();
        }
        $request->validate($rules);
        if ($emp->base_salary < $request->amount) {
            Toastr::error("Can't request more than salary");
            return back();
        }
        $advance = new AdvanceRequest();
        $advance->store_id  = Helpers::get_store_id();
        $advance->employee_id  = $emp->id;
        $advance->requested_amount  = $request->amount;
        $advance->reason  = $request->reason;
        $advance->required_on  = $request->required_on;
        if (auth('vendor')->check()) {
            $advance->status  = 'approved';
            $advance->approved_amount  = $request->amount;
        }
        $advance->save();

        // Owner-created advances are approved immediately — record them in accounts.
        if ($advance->status === 'approved') {
            $this->postAdvanceToAccounts($advance);
        }

        Toastr::success('Saved successfully');
        return back();
    }

    public function approve_advance_payment(Request $request, $id)
    {
        $request->validate([
            // 'approved_amount' => 'required|numeric|min:1',
        ]);

        $advance = AdvanceRequest::where('id', $id)->where('store_id', Helpers::get_store_id())->first();
        $advance->update([
            'approved_amount' => $advance->requested_amount,
            'status' => 'approved',
            'installments' => $request->installments,
            'repayment_start_date' => $request->repayment_start_date,
        ]);

        // Record the approved advance in the accounts module.
        $this->postAdvanceToAccounts($advance->fresh());

        Toastr::success('Advance Payment Approved');
        return back();
    }

    public function reject_advance_payment($id)
    {
        $advance = AdvanceRequest::findOrFail($id);
        $advance->update(['status' => 'rejected']);

        Toastr::success('Advance Payment Rejected');
        return back();
    }

    // Post an approved staff advance to the accounts module (ledger + daybook):
    // Debit "Staff Advances" (recoverable asset), Credit Cash. Idempotent via account_posted flag.
    private function postAdvanceToAccounts(AdvanceRequest $advance): void
    {
        try {
            $amount = (float) ($advance->approved_amount ?: $advance->requested_amount);
            if ($amount <= 0) {
                return;
            }

            if (!Schema::hasColumn('advance_requests', 'account_posted')) {
                DB::statement('ALTER TABLE `advance_requests` ADD COLUMN `account_posted` TINYINT(1) NOT NULL DEFAULT 0');
            }
            if ($advance->account_posted) {
                return; // already posted — avoid duplicate entries
            }

            $storeId = $advance->store_id ?: Helpers::get_store_id();
            $emp     = $advance->employee;
            $empName = $emp ? trim(($emp->f_name ?? '') . ' ' . ($emp->l_name ?? '')) : 'Staff';

            $debit  = Helpers::ensureStaffAdvanceAccount($storeId); // recoverable asset ↑
            $credit = Helpers::ensureCashAccount();                 // cash ↓

            $ledgerData = [
                'date'         => now(),
                'amount'       => $amount,
                'voucher_type' => 'Payment',
                'invoice_id'   => 'ADV-' . $advance->id,
                'status'       => 'approved',
                'description'  => 'Salary Advance — ' . $empName,
                'payment_mode' => 'cash',
            ];
            $voucher = _masterLedgerEntry($ledgerData, $credit, $debit, 'store', 'store', null);
            _saveDayBookEntry($amount, 'debit', $storeId, 'Salary Advance — ' . $empName, 'ADV-' . $advance->id, $voucher?->id, now(), null, 'cash');

            $advance->account_posted = 1;
            $advance->save();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Staff advance ledger post failed: ' . $e->getMessage());
        }
    }
}
