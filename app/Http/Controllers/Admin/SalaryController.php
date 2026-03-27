<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Salary;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Exports\SalaryExport;
use App\Exports\AllSalaryExport;
use App\Models\Admin;
use App\Models\AdvanceRequest;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Staff;
use App\Models\StoreTask;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

 class SalaryController extends Controller 
{ 

    public function index(Request $request)
    {
        $month_year = $request->month ?? date('Y-m');
        $v_id = 0;
        $salary = DB::table('admins')
            ->leftJoin('salaries', function ($join) use ($month_year) {
                $join->on('admins.id', '=', 'salaries.employee_id')
                    ->where('salaries.employee_type', '=', 'admin_employee')
                    ->where('salary_month', $month_year);
            })
            ->where('admins.role_id', '!=', 1)
            ->select('admins.id as ven_id', 'admins.f_name', 'admins.l_name', 'admins.salary_type', 'admins.base_salary', 'salaries.*')
            ->get();
        // prx($salary); 
        return view('admin-views.salary.index', compact('salary', 'month_year'));
    }
    public function pay(Request $request)
    {
        $id = $request->salary_id;
        $salary = Salary::where('id', $id)->first();
        if ($salary->pay_status != 'paid') {

            if (!empty($request->file('file'))) {
                $extension = $request->file('file')->getClientOriginalExtension(); // e.g. jpg, pdf, png
                $file = Helpers::upload('vendor/documents/', $extension, $request->file('file'));
                $salary->pay_reciept = $file;
            }

            $salary->pay_status = 'paid';
            $salary->update();

            if ($salary->total_payable > 0) {
                //ledger entry 
                $storeId = 0;
                $debit_account = Helpers::ensureSalaryLedger($storeId); // Debit
                $credit_account = Helpers::ensureBankAccount();
                $data = [
                    'date' => now(),
                    'amount' => $salary->total_payable,
                    'voucher_type' => 'Payment',
                    'status' => 'approved',
                    'description' => 'Salary',
                ];
               $voucher =  _masterLedgerEntry($data, $credit_account, $debit_account, 'admin', 'employee', null);
            }
            $empName = Admin::find($salary->employee_id);
            _saveDayBookEntry($salary->total_payable, 'debit', 0, "Salary Payment of " . ($empName ? $empName->f_name . ' ' . $empName->l_name : 'Unknown'), null, $voucher?->id);

            if ($salary) {
                $title = 'Salary Recieved';
                $msg = 'You have recieved salary for month ' . _monthNYear($salary->salary_month);
                $to = $salary->employee_id;
                $url = '';

                _inAppNotification($title, $msg, $assignment_id = null, $to, $url, 'admin_employee');
                _sendMailToStaff($title, $msg, $to, $url);

                Toastr::success('Salary Information saved successfully');
            } else {
                Toastr::warning('Some Error Occured');
            }
        }
        return back();
    }
    public function edit(Request $request, $id)
    {
        $month_year = $request->month ?? date('Y-m');
        $salary = DB::table('admins')
            ->leftJoin('salaries', function ($join) {
                $join->on('admins.id', '=', 'salaries.employee_id')
                    ->where('salaries.employee_type', '=', 'admin_employee');
            })
            ->where('admins.id', $id)
            ->select('admins.id as ven_id', 'admins.*', 'salaries.*')
            ->first();

        // leaves calc
        $month = explode('-', $month_year)[1] ?? date('m');
        $year = explode('-', $month_year)[0] ?? date('Y');
        $attendance = Attendance::where(['vendor_id' => 0, 'employee_type' => 'admin_employee',  'employee_id' => $id, 'month' => $month, 'year' => $year])->get()->toArray();

        $data['vacation_or_leave'] = 0;

        foreach ($attendance as $att) {
            if ($att['label'] == 'HDF' || $att['label'] == 'HDS') {
                $data['vacation_or_leave'] += 0.5;
            } else if (!in_array($att['label'], ['Sun', 'HL', 'P'])) {
                $data['vacation_or_leave']++;
            }
        }
        $empInfo = Admin::find($id);

        if ($empInfo->salary_type == 'Hourly') {
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            $records = DB::table('employee_time_cards')
                ->where('emp_id', $empInfo->id)
                ->where('vendor_id', 0 )
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
        $all_salaries = DB::table('salary_transactions')->where('employee_id', $id)->where('employee_type', 'admin_employee')->get();


        // advance payment
        $advance = AdvanceRequest::where('employee_id', $id)
            ->where('store_id', 0)
            ->where('status', 'approved')
            ->where('approved_amount', '>', 0)
            ->first();

        // prx($advance);
        if ($advance) {
            // $perInstallment = $advance->approved_amount / $advance->installments;
            $salary->advance_deductions = $advance->approved_amount;
        }
        return view('admin-views.salary.manage', compact('empInfo', 'month_year', 'salary', 'all_salaries', 'data'));
    }
    public function mark_paid(Request $request)
    {
        $month_year = $request->month ?? date('Y-m');
        $paidAmount = 0;
        $salaries = Salary::where('salary_month', $month_year)
            ->where('vendor_id', 0)
            ->where('employee_type', 'admin_employee')
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

                    _inAppNotification($title, $msg, $assignment_id = null, $to, $url, 'admin_employee');
                    _sendMailToStaff($title, $msg, $to, $url);
                }
            }
        }

        //ledger entry 
        if ($totalAmount > 0) {
            $storeId = 0;
            $debit_account = Helpers::ensureSalaryLedger($storeId); // Debit
            $credit_account = Helpers::ensureBankAccount();
            $data = [
                'date' => now(),
                'amount' => $totalAmount,
                'voucher_type' => 'Payment',
                'status' => 'approved',
                'description' => 'Salary',
            ];
           $voucher  =  _masterLedgerEntry($data, $credit_account, $debit_account, 'amdin', 'other', null);
        }

        // day book entry
        _saveDayBookEntry($paidAmount, 'debit', 0, "Salary Payment", null, $voucher?->id);
        return redirect()->back();
    }

    public function export(Request $request)
    {
        $id = $request->id;
        $headings =  ['Base Salary', 'Month', '	Deductions', 'Leaves', 'Paid Amount'];
        $all_salaries = DB::table('salary_transactions')->where('employee_id', $id)->where('employee_type', 'admin_employee')->get();
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
        $salary_info = Salary::where('employee_id', $request->id)->where('employee_type', 'admin_employee')->where('vendor_id', 0)->get();
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

        $v_id = 0;
        $salary = DB::table('admins')
            ->join('salaries', function ($join) use ($month_year) {
                $join->on('admins.id', '=', 'salaries.employee_id')
                    ->where("salaries.vendor_id", 0)
                    ->where('salaries.salary_month', '=', $month_year);
            })
            ->where('admins.role_id', '!=', 1)
            ->select('admins.f_name', 'admins.l_name', 'salaries.*')
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
        $v_id = 0;
        $salary = DB::table('admins')
            ->join('salary_transactions', function ($join) {
                $join->on('admins.id', '=', 'salary_transactions.employee_id')
                    ->where('salary_transactions.employee_type', '=', 'admin_employee');
            })
            ->where('admins.role_id', '!=', 1)
            ->whereBetween('salary_transactions.created_at', [$month . '-01 00:00:00', $month . '-31 23:59:59'])
            ->select('admins.id as ven_id', 'admins.*', 'salary_transactions.*')
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

        return view('admin-views.salary.report', compact('salary', 'month'));
    }
    public function add()
    {
        $employees =  Admin::where('role_id', '!=', 1)->get();
        return view('admin-views.salary.add', compact('employees'));
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

        $v_id = 0;
        $empInfo = Admin::find($request->emp_id);

        $salary_type = $empInfo->salary_type; // 'monthly', 'hourly', 'task_wise'
        $month = explode('-', $request->month)[1] ?? date('m');
        $year = explode('-', $request->month)[0] ?? date('Y');

        $vacation_or_leave = 0;
        $attendance = Attendance::where([
            'vendor_id' => 0,
            'employee_type' => 'admin_employee',
            'employee_id' => $empInfo->id,
            'month' => $month,
            'year' => $year
        ])->get();

        foreach ($attendance as $att) {
            if ($att->label === 'HDF' || $att->label === 'HDS') {
                $vacation_or_leave += 0.5;
            } else if (!in_array($att->label, ['Sun', 'HL', 'P'])) {
                $vacation_or_leave++;
            }
        }

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
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            // fetch time card records for current month
            $records = DB::table('employee_time_cards')
                ->where('emp_id', $empInfo->id)
                ->where('vendor_id', 0)
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

        return redirect()->route('admin.salary.list', ['month' => $request->month]);
    }


    public function generate_monthly(Request $request, $month = null)
    {
        $monthInput = $month ?? Carbon::now()->subMonth()->format('Y-m');
        [$year, $month] = explode('-', $monthInput);
        $employees = Admin::where('role_id','!=', 1)->get();
        foreach ($employees as $empInfo) {
            try {

                // Skip if already generated
                if (
                    Salary::where('employee_id', $empInfo->id)
                    ->where('salary_month', "$year-$month")
                    ->where('vendor_id', 0)
                    ->where('employee_type', 'admin_employee')
                    ->exists()
                ) {
                    continue;
                } 
                /* -------------------------------------------------
             | COMMON VARIABLES
             ------------------------------------------------- */
                $salary_type = $empInfo->salary_type;
                $base_salary = (float) ($empInfo->base_salary ?? 0);
                $bonus       = (float) ($empInfo->bonus_incentives ?? 0);
                $deductions  = (float) ($empInfo->deductions ?? 0);

                $payable_salary  = 0;
                $total_allowance = 0;
                $allowances      = [];

                $total_days_in_month = cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year);
                $vacation_or_leave = 0;

                /* -------------------------------------------------
             | MONTHLY SALARY
             ------------------------------------------------- */
                if ($salary_type === 'Monthly') {

                    $attendance = Attendance::where([
                        'vendor_id'     => 0,
                        'employee_type' => 'admin_employee',
                        'employee_id'   => $empInfo->id,
                        'month'         => $month,
                        'year'          => $year,
                    ])->get();
                    foreach ($attendance as $att) {
                        if (in_array($att->label, ['HDF', 'HDS'])) {
                            $vacation_or_leave += 0.5;
                        } elseif (!in_array($att->label, ['Sun', 'HL', 'P'])) {
                            $vacation_or_leave += 1;
                        }
                    }

                    $per_day_salary = $total_days_in_month > 0
                        ? $base_salary / $total_days_in_month
                        : 0;

                    $payable_salary = $base_salary - ($per_day_salary * $vacation_or_leave);
                }

                /* -------------------------------------------------
             | HOURLY SALARY
             ------------------------------------------------- */ elseif ($salary_type === 'Hourly') {

                    $startOfMonth = Carbon::create($year, $month)->startOfMonth();
                    $endOfMonth   = Carbon::create($year, $month)->endOfMonth();

                    $records = DB::table('employee_time_cards')
                        ->where('emp_id', $empInfo->id)
                        ->where('vendor_id', 0)
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
                        $total_hours = ((int)$h) + (((int)$m) / 60);
                        $payable_salary = $base_salary * $total_hours;
                    }
                }

                /* -------------------------------------------------
             | TASK-WISE SALARY (MATCHES FRONTEND)
             ------------------------------------------------- */ elseif ($salary_type === 'Task-Wise') {
                    $payable_salary = $base_salary;
                }

                /* -------------------------------------------------
             | ALLOWANCES (MATCHES JS LOGIC)
             ------------------------------------------------- */
$monthlyAllowances = [];

if (!empty($empInfo->monthly_allowances)) {

    if (is_string($empInfo->monthly_allowances)) {
        $decoded = json_decode($empInfo->monthly_allowances, true);
        $monthlyAllowances = is_array($decoded) ? $decoded : [];
    }

    elseif (is_array($empInfo->monthly_allowances)) {
        $monthlyAllowances = $empInfo->monthly_allowances;
    }
}

                foreach ($monthlyAllowances as $item) {
                    $value = (float) ($item['amount'] ?? 0);

                    if (in_array($salary_type, ['Monthly', 'Task-Wise'])) {
                        $per_day_allowance = $total_days_in_month > 0
                            ? $value / $total_days_in_month
                            : 0;

                        $value = $value - ($per_day_allowance * $vacation_or_leave);
                    }

                    $allowances[] = [
                        'title'  => $item['title'] ?? '',
                        'amount' => round($value, 3),
                    ];

                    $total_allowance += $value;
                }

                /* -------------------------------------------------
             | ADVANCE DEDUCTIONS
             ------------------------------------------------- */
                $advance_payment_deductions = 0;

                $advance = AdvanceRequest::where('employee_id', $empInfo->id)
                    ->where('store_id', 0)
                    ->where('status', 'approved')
                    ->where('approved_amount', '>', 0)
                    ->first();

                if ($advance) {
                    $advance_payment_deductions = (float) $advance->approved_amount;
                }

                /* -------------------------------------------------
             | FINAL TOTAL (SAME AS FRONTEND)
             ------------------------------------------------- */
                $total_payable =
                    $payable_salary
                    + $total_allowance
                    - $deductions
                    - $advance_payment_deductions
                    + $bonus;
                /* -------------------------------------------------
             | SAVE SALARY
             ------------------------------------------------- */
                Salary::create([
                    'vendor_id'        => 0,
                    'employee_id'      => $empInfo->id,
                    'base_salary'      => $base_salary,
                    'payable_salary'   => round($payable_salary, 3),
                    'allowance_amount' => round($total_allowance, 3),
                    'total_payable'    => round($total_payable, 3),
                    'bonus_incentives' => $bonus,
                    'deductions'       => $deductions,
                    'advance_payment_deductions' => $advance_payment_deductions,
                    'allowance'        => json_encode($allowances),
                    'salary_month'     => "$year-$month",
                    'generated_at'     => now(),
                    'created_at'       => now(),
                ]);
            } catch (\Exception $e) {
                prx($e->getMessage());
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
        $staff = Admin::where('role_id', '!=', 1)->get();
        $advance_requests = AdvanceRequest::where('store_id', 0)->get();
        return view('admin-views.salary.advance-requests', compact('advance_requests', 'staff'));
    }
    public function lead_approval(Request $request)
    {
        $id = $request->post('lead_id');
        $lead = Lead::find($id);
        $lead->approval = $request->post('approval');
        $lead->save();
        return response()->json(['msg' => ucfirst($request->post('approval')) . 'ed', 'status' => true]);
    }

    public function my_salary_history(Request $request)
    {
        $emp = auth('admin')->user();
        $all_salaries = Salary::where('employee_id', $emp->id)->where('employee_type', 'admin_employee')->get();
        return view('admin-views.salary.salary-history', compact('all_salaries'));
    }
 
    public function advance_payment(Request $request)
    { // employee specific
        $emp = auth('admin')->user();

        $advance_requests = AdvanceRequest::with('employee')->where('employee_id', $emp->id)->where('store_id', 0)->get();
        return view('admin-views.salary.advance-requests', compact('advance_requests'));
    }
    public function advance_request_store(Request $request)
    {
        $rules = [
            'amount' => 'required|numeric',
            'required_on' => 'required',
        ];
        if (auth('admin')->user()->role_id == 1) {
            $rules['emp_id'] = 'required';
            $emp = Admin::find($request->emp_id);
        } else {
            $emp = auth('admin')->user();
        }
        $request->validate($rules);
        if ($emp->base_salary < $request->amount) { 
            Toastr::error("Can't request more than salary");
            return back();
        }
        $advance = new AdvanceRequest();
        $advance->store_id  = 0;
        $advance->employee_id  = $emp->id;
        $advance->requested_amount  = $request->amount;
        $advance->reason  = $request->reason;
        $advance->required_on  = $request->required_on;
        if (auth('admin')->user()->role_id == 1) {
            $advance->status  = 'approved';
            $advance->approved_amount  = $request->amount;
        }
        $advance->save();

        Toastr::success('Saved successfully');
        return back();
    }

    public function approve_advance_payment(Request $request, $id)
    {
        $request->validate([
            // 'approved_amount' => 'required|numeric|min:1',
        ]);

        $advance = AdvanceRequest::where('id', $id)->where('store_id', 0)->first();
        $advance->update([
            'approved_amount' => $advance->requested_amount,
            'status' => 'approved',
            'installments' => $request->installments,
            'repayment_start_date' => $request->repayment_start_date,
        ]);

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
}
