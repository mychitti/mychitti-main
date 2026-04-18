<?php

namespace App\Http\Controllers;

use App\CentralLogics\Helpers;
use App\CentralLogics\ProductLogic;
use App\Models\AssetDepreciation;
use App\Models\Attendance;
use App\Models\BusinessSetting;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\MonthlyMaintanance;
use App\Models\Order;
use App\Models\ServiceInvoice;
use App\Models\ServiceRequest;
use App\Models\Store;
use App\Models\StoreAsset;
use App\Models\StoreBankAccount;
use App\Models\StoreBankTransaction;
use App\Models\StoreConfig;
use App\Models\StoreDailyBalance;
use App\Models\StoreEnabledModule;
use App\Models\SubModule;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorEmployee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PHPUnit\TextUI\Help;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;


use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;

class CronController extends Controller
{

    public function calculate_salary(Request $request)
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

        if ($id == '') { // for new lead  
            $staff = new Salary;
        } else {
            $staff = Salary::find($id);
        }
        $v_id = \App\CentralLogics\Helpers::get_store_id();
        $empInfo = VendorEmployee::find($request->emp_id);

        // new flow 
        // leaves 

        $month = explode('-', $request->month)[1] ?? date('m');
        $year = explode('-', $request->month)[0] ?? date('Y');
        $attendance = Attendance::where(['vendor_id' => Helpers::get_store_id(), 'employee_type' => 'vendor_employee',  'employee_id' => $empInfo->id, 'month' => $month, 'year' => $year])->get()->toArray();
        $vacation_or_leave = 0;
        // prx($attendance);
        foreach ($attendance as $att) {
            if ($att['label'] == 'HDF' || $att['label'] == 'HDS') {
                $vacation_or_leave += 0.5;
            } else if (!in_array($att['label'], ['Sun', 'HL', 'P'])) {
                $vacation_or_leave++;
            }
        }
        // prx($vacation_or_leave);

        $base_salary = $empInfo->base_salary ?? 0;
        $unpaid_leaves = $vacation_or_leave ?? 0;
        $total_days_in_month = cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year);
        $per_day_salary = $total_days_in_month ? ($base_salary / $total_days_in_month) : 0;
        $payable_salary = $base_salary - ($per_day_salary * $unpaid_leaves);
        $bonus = (float) $request->input('bonus_incentives', 0);
        $deductions = (float) $request->input('deductions', 0);

        $allowances = [];
        $total_allowance = 0;

        foreach ($request->item_name as $key => $title) {
            $value = (float) $request->item_price[$key];
            $per_day_allowance = $total_days_in_month ? ($value / $total_days_in_month) : 0;
            $adjusted_amount = $value - ($per_day_allowance * $unpaid_leaves);

            $allowances[] = [
                'title' => $title,
                'amount' => $value
            ];
            $total_allowance += $adjusted_amount;
        }
        // update Allowances
        $empInfo->monthly_allowances = json_encode($allowances);
        $empInfo->save();

        $total_payable = $payable_salary + $total_allowance + $bonus - $deductions;

        $staff->vendor_id = $v_id;
        $staff->employee_id = $request->post('emp_id');
        $staff->base_salary = $base_salary;
        $staff->payable_salary = round($payable_salary, 2);
        $staff->total_payable = round($total_payable, 2);
        $staff->salary_month = $request->month;
        $staff->bonus_incentives =  $bonus;
        $staff->allowance_amount = round($total_allowance, 2);
        $staff->allowance = json_encode($allowances);
        $staff->deductions = $deductions;
        $staff->created_at = date('Y-m-d H:i:s');

        if ($id == '') { // for new lead
            $staff->save();
            Toastr::success('Salary Information saved successfully');
        } else {
            $staff->update();
            Toastr::success('Salary Information updated successfully');
        }
        if ($request->has('month')) {
            return redirect()->route('vendor.salary.list', ['month' => $request->month]);
        }
    }

    public function check_depreciation()
    {
        $today = \Carbon\Carbon::now()->endOfMonth();

        // Fetch all stores (without global scopes)
        $stores = \App\Models\Store::withoutGlobalScopes()->get();

        foreach ($stores as $store) {
            $storeId = $store->id;

            \App\Models\StoreAsset::with('inventoryItem')
                ->where('store_id', $storeId)
                ->chunk(100, function ($assets) use ($today, $storeId) {

                    foreach ($assets as $asset) {
                        $openingValue = $asset->current_value;

                        // Skip fully depreciated assets
                        if ($openingValue <= 0) {
                            continue;
                        }

                        // Convert useful life to months
                        $totalMonths = match ($asset->useful_life_unit) {
                            'years' => $asset->useful_life_count * 12,
                            'months' => $asset->useful_life_count,
                            'days' => ceil($asset->useful_life_count / 30),
                            default => $asset->useful_life_count * 12,
                        };

                        $totalDepreciationAmount = 0;

                        // Calculate depreciation
                        if ($asset->depreciation_method === 'straight_line') {
                            $perUnitDepreciation = round($asset->cost / $totalMonths, 2);
                            $totalDepreciationAmount = $perUnitDepreciation * $asset->quantity;
                        } elseif ($asset->depreciation_method === 'reducing_balance') {
                            $annualRate = 1 / ($totalMonths / 12);
                            $monthlyRate = $annualRate / 12;
                            $totalDepreciationAmount = round($openingValue * $monthlyRate, 2);
                        }

                        if ($totalDepreciationAmount <= 0) {
                            continue;
                        }

                        // Calculate closing value
                        $closingValue = max(0, $openingValue - $totalDepreciationAmount);

                        // Update current value
                        $asset->current_value = $closingValue;
                        $asset->save();

                        // Record depreciation history
                        \App\Models\AssetDepreciation::create([
                            'store_id' => $storeId,
                            'asset_id' => $asset->id,
                            'depreciation_date' => $today,
                            'opening_value' => $openingValue,
                            'depreciation_amount' => $totalDepreciationAmount,
                            'closing_value' => $closingValue,
                        ]);

                        // Ledger accounts
                        $credit_account = Helpers::ensureDepreciationExpenseAccount();
                        $debit_account = Helpers::ensureAccumulatedDepreciationAccount($asset->inventoryItem?->item_name, $storeId);

                        $ledgerData = [
                            'date' => $today,
                            'amount' => round($totalDepreciationAmount, 2),
                            'status' => 'approved',
                            'voucher_type' => 'Journal',
                            'description' => "Monthly depreciation for {$asset->quantity} x {$asset->inventoryItem?->item_name}",
                        ];

                        // Record ledger entries
                        _masterLedgerEntry(
                            $ledgerData,
                            $credit_account,
                            $debit_account,
                            'store',
                            'customer',
                            null,
                            $storeId
                        );
                    }
                });
        }
    }

    public function monthly_maintenance_reminder_old(Request $request)
    {
        $today = Carbon::today();
        $masterRecords = MonthlyMaintanance::where('master', 1)->where('store_id', 26)->get();

        foreach ($masterRecords as $master) {
            $storeConfig = StoreConfig::where('store_id', $master->store_id)->first();
            $reminderDaysBefore = $storeConfig && $storeConfig->reminder_day_before ? $storeConfig->reminder_day_before : 2;
            $paymentDay = $master->payment_day;

            $dueDate = Carbon::create($today->year, $today->month, $paymentDay);
            $reminderStartDate = $dueDate->copy()->subDays($reminderDaysBefore);

            $exists = MonthlyMaintanance::where('store_id', $master->store_id)
                ->where('expense_type', $master->expense_type)
                ->where('master', 0)
                ->whereMonth('for_month', $today->month)
                ->whereYear('for_month', $today->year)
                ->exists();

            if ($today->lt($reminderStartDate)) {
                continue;
            }
            $storeConfig = StoreConfig::where('store_id', $master->store_id)->first();
            $due = !$storeConfig || $storeConfig->monthly_maintnnce_req == 'manual_pay' ? 1 : 0;

            if (!$exists) {

                $created = MonthlyMaintanance::create([
                    'store_id' => $master->store_id,
                    'expense_type' => $master->expense_type,
                    'title' => $master->title,
                    'amount' => $master->amount,
                    'payment_day' => $master->payment_day,
                    'parent' => $master->id,
                    'master' => 0,
                    'for_month' => $today->copy()->startOfMonth(),
                    'due' => $due,
                    'status' => 'due',
                    'due_date' => $dueDate,
                ]);

                $store = Store::withoutGlobalScopes()->find($master->store_id);

                if ($store) {
                    $daysUntilDue = $today->diffInDays($dueDate, false);

                    if ($daysUntilDue > 0) {
                        $reminderText = "due in {$daysUntilDue} day(s)";
                    } elseif ($daysUntilDue == 0) {
                        $reminderText = "due today";
                    } else {
                        $reminderText = "overdue by " . abs($daysUntilDue) . " day(s)";
                    }

                    $data = [
                        'title' => "Monthly Maintenance Reminder",
                        'description' => "Your maintenance payment of Rs. {$master->amount} for " . ucfirst($master->expense_type) . " is {$reminderText} (Due: {$dueDate->format('M d, Y')}).",
                    ];
                    $url = route('vendor.account.maintenance.index');
                    _inAppNotification($data['title'], $data['description'], null, $store->id, $url, 'vendor');
                    $debit_account = Helpers::ensureMaintenanceExpenseAccount($store->id);

                    $credit_account = Helpers::ensureOtherBankAccount();
                    $ledgerData = [
                        'date' => $dueDate,
                        'amount' => $master->amount,
                        'voucher_type' => 'Payment',
                        'maintanace_id' => $created->id,
                        'status' => $due ? 'pending' : 'approved',
                        'description' => $master->expense_type,
                    ];
                    _masterLedgerEntry($ledgerData, $credit_account, $debit_account, 'store', 'other', null, $master->store_id);
                }
            } else {
                $entry = MonthlyMaintanance::where('store_id', $master->store_id)
                    ->where('expense_type', $master->expense_type)
                    ->where('master', 0)
                    ->whereMonth('for_month', $today->month)
                    ->whereYear('for_month', $today->year)
                    ->where('due', $due) // Only if still unpaid
                    ->first();

                if ($entry) {
                    $store = Store::withoutGlobalScopes()->find($master->store_id);

                    if ($store) {
                        $daysUntilDue = $today->diffInDays($dueDate, false);

                        if ($daysUntilDue > 0) {
                            $reminderText = "due in {$daysUntilDue} day(s)";
                        } elseif ($daysUntilDue == 0) {
                            $reminderText = "due today";
                        } else {
                            $reminderText = "OVERDUE by " . abs($daysUntilDue) . " day(s)";
                        }

                        $data = [
                            'title' => $daysUntilDue < 0 ? " Overdue Payment Reminder" : "Monthly Maintenance Reminder",
                            'description' => "Reminder: Your maintenance payment of Rs. {$master->amount} for " . ucfirst($master->expense_type) . " is {$reminderText}.",
                        ];

                        $url = route('vendor.account.maintenance.index');
                        _inAppNotification($data['title'], $data['description'], null, $store->id, $url, 'vendor');
                    }
                }
            }
        }
    }
    public function monthly_maintenance_reminder(Request $request)
    {
        $today = Carbon::today();
        $masterRecords = MonthlyMaintanance::where('master', 1)->get();

        foreach ($masterRecords as $master) {

            $storeConfig = StoreConfig::where('store_id', $master->store_id)->first();

            $reminderDaysBefore = $storeConfig && $storeConfig->reminder_day_before
                ? $storeConfig->reminder_day_before
                : 2;

            $paymentDay   = (int) $master->payment_day;
            $durationType = $master->duration_type ?? 'monthly'; // monthly / quarterly / annually
            $startMonth   = (int) $master->start_month;          // only for quarterly / annually

            /*
        |--------------------------------------------------------------------------
        | Decide whether this expense should be generated for this month
        |--------------------------------------------------------------------------
        */

            $generateForThisMonth = false;

            if ($durationType == 'monthly') {

                $generateForThisMonth = true;
            } elseif ($durationType == 'quarterly') {

                if (!$startMonth) {
                    continue;
                }

                $diff = ($today->month - $startMonth + 12) % 12;

                if ($diff % 3 == 0) {
                    $generateForThisMonth = true;
                }
            } elseif ($durationType == 'yearly') {

                if (!$startMonth) {
                    continue;
                }

                if ($today->month == $startMonth) {
                    $generateForThisMonth = true;
                }
            }

            if (!$generateForThisMonth) {
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | Due date (safe for 28/30/31)
        |--------------------------------------------------------------------------
        */

            $lastDay = Carbon::create($today->year, $today->month, 1)->endOfMonth()->day;

            $dueDate = Carbon::create(
                $today->year,
                $today->month,
                min($paymentDay, $lastDay)
            );

            $reminderStartDate = $dueDate->copy()->subDays($reminderDaysBefore);

            if ($today->lt($reminderStartDate)) {
                continue;
            }

            $exists = MonthlyMaintanance::where('store_id', $master->store_id)
                ->where('expense_type', $master->expense_type)
                ->where('master', 0)
                ->whereMonth('for_month', $today->month)
                ->whereYear('for_month', $today->year)
                ->exists();

            $due = !$storeConfig || $storeConfig->monthly_maintnnce_req == 'manual_pay'
                ? 1
                : 0;
            if (!$exists) {

                $created = MonthlyMaintanance::create([
                    'store_id'     => $master->store_id,
                    'expense_type' => $master->expense_type,
                    'title'        => $master->title,
                    'amount'       => $master->amount,
                    'payment_day'  => $master->payment_day,
                    'parent'       => $master->id,
                    'master'       => 0,
                    'for_month'    => $today->copy()->startOfMonth(),
                    'due'          => $due,
                    'status'       => 'due',
                    'due_date'     => $dueDate,
                ]);

                $store = Store::withoutGlobalScopes()->find($master->store_id);

                if ($store) {

                    $daysUntilDue = $today->diffInDays($dueDate, false);

                    if ($daysUntilDue > 0) {
                        $reminderText = "due in {$daysUntilDue} day(s)";
                    } elseif ($daysUntilDue == 0) {
                        $reminderText = "due today";
                    } else {
                        $reminderText = "overdue by " . abs($daysUntilDue) . " day(s)";
                    }

                    $data = [
                        'title'       => "Monthly Maintenance Reminder",
                        'description' => "Your maintenance payment of Rs. {$master->amount} for " .
                            ucfirst($master->expense_type) .
                            " is {$reminderText} (Due: {$dueDate->format('M d, Y')}).",
                    ];

                    $url = route('vendor.account.maintenance.index');

                    _inAppNotification(
                        $data['title'],
                        $data['description'],
                        null,
                        $store->id,
                        $url,
                        'vendor'
                    );

                    $debit_account  = $master->debit_account ?? Helpers::ensureMaintenanceExpenseAccount($store->id);
                    $credit_account = $master->credit_account ?? Helpers::ensureOtherBankAccount();

                    $ledgerData = [
                        'date'         => $dueDate,
                        'amount'       => $master->amount,
                        'voucher_type' => 'Payment',
                        'maintanace_id' => $created->id,
                        'status'       => $due ? 'pending' : 'approved',
                        'description'  => $master->expense_type,
                    ];

                    _masterLedgerEntry(
                        $ledgerData,
                        $credit_account,
                        $debit_account,
                        'store',
                        'other',
                        null,
                        $master->store_id
                    );
                }
            } else {

                $entry = MonthlyMaintanance::where('store_id', $master->store_id)
                    ->where('expense_type', $master->expense_type)
                    ->where('master', 0)
                    ->whereMonth('for_month', $today->month)
                    ->whereYear('for_month', $today->year)
                    ->where('due', $due)
                    ->first();


                if ($entry) {

                    $store = Store::withoutGlobalScopes()->find($master->store_id);

                    if ($store) {

                        $daysUntilDue = $today->diffInDays($dueDate, false);

                        if ($daysUntilDue > 0) {
                            $reminderText = "due in {$daysUntilDue} day(s)";
                        } elseif ($daysUntilDue == 0) {
                            $reminderText = "due today";
                        } else {
                            $reminderText = "OVERDUE by " . abs($daysUntilDue) . " day(s)";
                        }

                        $data = [
                            'title' => $daysUntilDue < 0
                                ? "Overdue Payment Reminder"
                                : "Monthly Maintenance Reminder",

                            'description' => "Reminder: Your maintenance payment of Rs. {$master->amount} for " .
                                ucfirst($master->expense_type) .
                                " is {$reminderText}.",
                        ];

                        $url = route('vendor.account.maintenance.index');

                        _inAppNotification(
                            $data['title'],
                            $data['description'],
                            null,
                            $store->id,
                            $url,
                            'vendor'
                        );
                    }
                }
            }
        }
    }


    public function employee_attendance(Request $request)
    {
        $today = now()->toDateString();
        $now = now();
        $isSunday = now()->isSunday();

        // 1. Get all employees
        $employees = DB::table('vendor_employees')
            ->where('status', 1)
            ->select('id', 'store_id')
            ->get()
            ->keyBy('id');

        $presentIds = DB::table('attendances')
            ->where('date', $today)
            ->pluck('employee_id')
            ->toArray();

        $absentIds = array_diff($employees->keys()->toArray(), $presentIds);

        $holidays = DB::table('holidays')
            ->where(function ($q) {
                $q->where('is_global', 1)->orWhereNotNull('vendor_id');
            })
            ->whereDate('date', $today)
            ->get();

        $overrides = DB::table('holiday_overrides')
            ->whereDate('custom_date', $today)
            ->get()
            ->keyBy(fn($h) => $h->vendor_id . '-' . $h->holiday_id);

        $vendorHolidayMap = [];

        foreach ($holidays as $h) {
            if ($h->is_global) {
                foreach ($employees as $emp) {
                    $override = $overrides->firstWhere(
                        fn($o) =>
                        $o->holiday_id == $h->id && $o->vendor_id == $emp->store_id && $o->is_deleted == 1
                    );
                    if (!$override) {
                        $vendorHolidayMap[$emp->store_id] = true;
                    }
                }
            } else {
                $vendorHolidayMap[$h->vendor_id] = true;
            }
        }

        $data = array_map(function ($id) use ($employees, $today, $now, $vendorHolidayMap, $isSunday) {
            $vendor_id = $employees[$id]->store_id;

            $label = 'A';
            if ($isSunday) {
                $label = 'Sun';
            } elseif (isset($vendorHolidayMap[$vendor_id])) {
                $label = 'HL';
            }

            return [
                'employee_id'   => $id,
                'vendor_id'     => $vendor_id,
                'employee_type' => 'vendor_employee',
                'date'          => $today,
                'label'         => $label,
                'day'           => $now->day,
                'month'         => $now->month,
                'year'          => $now->year,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }, $absentIds);

        if (!empty($data)) {
            DB::table('attendances')->insert($data);
        }

        echo count($data) . ' employees marked (A / HL / Sunday).';
    }

    public function cancel_order(Request $request)
    {
        // Cancel orders if not accepted by the vendor within 24 hours
        $orders = Order::where('created_at', '<', Carbon::now()->subHours(24))
            ->whereIn('order_status', ['pending'])
            ->get();

        foreach ($orders as $order) {
            $order = Order::where('id', $order->id)->with('module')->first();

            $order->order_status = 'canceled';
            $order->canceled = now();
            $order->canceled_by = 'store';
            $order->save();

            if (config('module.' . $order->module->module_type)['stock']) {
                foreach ($order->details as $detail) {
                    $variant = json_decode($detail['variation'], true);
                    $item = $detail->campaign ?? $detail->item; // Use campaign if available
                    ProductLogic::update_stock($item, -$detail->quantity, count($variant) ? $variant[0]['type'] : null)->save();
                }
            }
            $user_fcm = $order?->customer?->cm_firebase_token;
            $data = [
                'title' => translate('messages.order_push_title'),
                'description' => "We’re sorry for the inconvenience, but the Seller was unable to accept your order request at this moment. Please Try again after Some Time.",
                'order_id' => $order->id,
                'image' => '',
                'type' => 'order_status',
            ];
            Helpers::send_push_notif_to_device($user_fcm, $data);
            DB::table('user_notifications')->insert([
                'data' => json_encode($data),
                'user_id' => $order->user_id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    private function getDateDiff(Carbon $start, Carbon $now, $unit)
    {
        switch (strtolower($unit)) {
            case 'day':
            case 'days':
                return $start->diffInDays($now);
            case 'week':
            case 'weeks':
                return $start->diffInWeeks($now);
            case 'month':
            case 'months':
                return $start->diffInMonths($now);
            default:
                return 0;
        }
    }

    public function payment_reminder(Request $request)
    {
        $now = Carbon::today(); // Only date, no time
        $bill_to = null;
        $serviceReminders = ServiceInvoice::with([
            'service_request.item',
            'websiteUser',
            'storeCustomer',
            'store'
        ])->where('payment_status', 'Unpaid')
            ->where('reminder_status', 1)
            ->get()->map(function ($item) {
                $item->type = 'service';
                return $item;
            });

        // prx($serviceReminders);

        $manualReminders = ManualInvoice::with([
            'websiteUser',
            'storeCustomer',
            'store'
        ])->where('created_at', '2025-06-13 17:33:15')->where('payment_status', 'Unpaid')
            ->where('reminder_status', 1)
            ->get()->map(function ($item) {
                $item->type = 'manual';
                return $item;
            });

        // prx($manualReminders);
        // Merge both
        $reminders = $serviceReminders->concat($manualReminders)->values();

        foreach ($reminders as $reminder) {
            $start = Carbon::parse($reminder->reminder_start_date)->startOfDay();
            $today = Carbon::today();

            if ($reminder->reminder_last_sent_at && Carbon::parse($reminder->reminder_last_sent_at)->isSameDay($today)) {
                continue;
            }
            $diff = $this->getDateDiff($start, $now, $reminder->reminder_freq_unit);

            if (!empty($reminder->reminder_freq) && $reminder->reminder_freq != 0) {
                if ($diff % $reminder->reminder_freq === 0) {

                    if ($reminder->type == 'service') {
                        $product = $reminder->service_request?->item?->name ?? $reminder->store?->name;

                        if ($reminder->payment_date) {
                            $msg = "Your payment of " . _price($reminder->total_amount) . " for " . $product . " is due by " . $reminder->payment_date . ". Please complete it at your earliest.";
                        } else {
                            $msg = "Your payment of " . _price($reminder->total_amount) . " for " . $product . " is due. Please complete it at your earliest.";
                        }
                    } else {
                        if ($reminder->payment_date) {
                            $msg = "Your payment of " . _price($reminder->total_amount) . " is due by " . $reminder->payment_date . ". Please complete it at your earliest.";
                        } else {
                            $msg = "Your payment of " . _price($reminder->total_amount) . " is due. Please complete it at your earliest.";
                        }
                    }

                    // Send the reminder
                    if ($reminder->user_type == 'store_user') {
                        $existingUser = User::where('phone', $reminder->StoreCustomer?->phone)->first();
                        if ($existingUser) {
                            $fcmToken = $existingUser->cm_firebase_token ?? null;
                        }
                    } elseif ($reminder->user_type == 'store_vendor') {
                        $bill_to = 'store';
                    } else {
                        $fcmToken = $reminder->websiteUser->cm_firebase_token ?? null;
                    }
                    if ($bill_to == 'store') {
                        $registeredStore = Store::where('phone', $reminder->StoreCustomer?->phone)->first();

                        if ($registeredStore) {
                            $url = route('vendor.invoice.my-bills');
                            _inAppNotification("Payment Reminder", $msg, null, $registeredStore->id, $url, 'vendor');
                        }
                    } else {
                        if ($fcmToken) {
                            $data = [
                                'title' => "Payment Reminder",
                                'description' => $msg,
                                'order_id' => '',
                                'image' => '',
                                'type' => 'block'
                            ];

                            Helpers::send_push_notif_to_device($fcmToken, $data);

                            // Update last_sent_at to today 
                            $reminder->reminder_last_sent_at = $today;
                            $reminder->save();
                        }
                    }
                }
            }
        }
    }
    public function unavailable_provider(Request $request)
    {
        $expireMinutes = Helpers::get_lead_exp_minutes();
        $upper = Carbon::now()->subMinutes($expireMinutes);
        $lower = Carbon::now()->subMinutes($expireMinutes + 100);

        ServiceRequest::whereNull('accepted_by')
            ->where('created_at', '<', $upper)
            ->where('created_at', '>', $lower)
            ->where(fn($q) => $q->whereNull('user_notified')->orWhere('user_notified', '!=', 1))
            ->with('user')
            ->chunk(100, function ($services) {
                foreach ($services as $service) {
                    $customer = $service->user;

                    if ($customer) {
                        $fcm_token = $customer->cm_firebase_token;

                        $data = [
                            'title' => "Service Request Update",
                            'description' => "We're sorry for the inconvenience, but the vendor was unable to accept your request at this moment. Please try again after some time.",
                            'order_id' => $service->id,
                            'image' => '',
                            'type' => 'notification'
                        ];

                        if ($fcm_token) {
                            Helpers::send_push_notif_to_device($fcm_token, $data);
                        }
                        DB::table('user_notifications')->insert([
                            'data' => json_encode($data),
                            'user_id' => $service->user_id,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                    $service->update(['user_notified' => 1]);
                }
            });
    }
    public function test_dbbackup(Request $request)
    {

        // Define database credentials from Laravel config
        $dbHost = env('DB_HOST', '127.0.0.1');
        $dbUser = env('DB_USERNAME', 'root');
        $dbPass = env('DB_PASSWORD', '');
        $dbName = env('DB_DATABASE', 'my_database');

        // Set the backup file path
        $backupPath = storage_path('backups');
        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true, true);
        }

        $backupFile = $backupPath . '/backup_' . date('Y-m-d_H-i-s') . '.sql';

        // Run the mysqldump command
        $command = "/usr/bin/mysqldump -h $dbHost -u $dbUser -p'$dbPass' $dbName > $backupFile";

        exec($command, $output, $result);

        // Check if the backup was successful
        if ($result === 0) {
            return response()->json([
                'message' => 'Database backup completed!',
                'backup_file' => $backupFile
            ]);
        } else {
            return response()->json([
                'message' => 'Database backup failed!',
                'error' => implode("\n", $output)
            ], 500);
        }
    }

    public function monthly_billing(Request $request)
    {
        // Clean expired trials
        StoreEnabledModule::whereNotNull('free_trial_extended_until')
            ->where('free_trial_extended_until', '<', now())
            ->update(['free_trial_extended_until' => null]);

        $billingMonth = Carbon::now()->subMonth()->startOfMonth(); // Previous month
        $billingPeriodStart = $billingMonth->copy();
        $billingPeriodEnd = $billingMonth->copy()->endOfMonth();

        $stores = Store::all();

        foreach ($stores as $store) {
            // if ($store->id == 26) { // TESTING

            $storeModules = StoreEnabledModule::where('store_id', $store->id)
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', now());
                })->get();

            $totalAmount = 0;
            $invoiceItemsData = [];
            $submodule_key = [];
            $store_module_ids = [];

            foreach ($storeModules as $storeModule) {
                $subModule = SubModule::find($storeModule->submodule_id);
                if (!$subModule) continue;


                $startDate = Carbon::parse($storeModule->start_date);

                // Skip modules still in free trial
                $trialEndDate = $storeModule->free_trial_extended_until
                    ? Carbon::parse($storeModule->free_trial_extended_until)
                    : $startDate->copy()->addDays(max(0, $subModule->free_trial_days ?? 0) - 1);

                $billingStart = $trialEndDate ? $trialEndDate->copy()->addDay() : $startDate->copy();
                if ($billingPeriodEnd->lt($billingStart)) {
                    continue; // Nothing to bill this month
                }

                $alreadyBilled = ManualInvoice::where('bill_to', $store->id)
                    ->where('bill_to_type', 'vendor')
                    ->where('key_purpose', 'services_billing')
                    ->where('service_key', $submodule_key)
                    ->whereMonth('created_at', $billingPeriodStart->month)
                    ->whereYear('created_at', $billingPeriodStart->year)
                    ->exists();

                if ($alreadyBilled) {
                    continue; // Already billed for this month
                }
                // Grace period check: look for unpaid invoice from this billing month
                $unpaidInvoice = ManualInvoice::where('bill_to', $store->id)
                    ->where('bill_to_type', 'vendor')
                    ->where('key_purpose', 'services_billing')
                    ->where('service_key', $submodule_key)
                    ->where('payment_status', 'Unpaid')
                    ->whereDate('created_at', '>=', $billingPeriodEnd->copy()->addDays(1)) // Invoice would be created on 1st of current month
                    ->latest()
                    ->first();

                $graceEndDate = $billingPeriodEnd->copy()->addDays(8); // Billing due = 2nd, grace ends = 9th
                if ($unpaidInvoice && now()->lessThanOrEqualTo($graceEndDate)) {
                    continue; // Still in grace period, don’t bill again
                }

                // Bill for actual usage
                $submoduleBillingStart = $billingPeriodStart->copy()->max($billingStart);
                $moduleEnd = $storeModule->end_date ? Carbon::parse($storeModule->end_date) : null;
                $submoduleBillingEnd = $billingPeriodEnd->copy();
                if ($moduleEnd && $moduleEnd->lt($submoduleBillingEnd)) {
                    $submoduleBillingEnd = $moduleEnd;
                }

                if ($submoduleBillingEnd->lt($submoduleBillingStart)) {
                    continue;
                }

                $daysToBill = $submoduleBillingStart->diffInDays($submoduleBillingEnd) + 1;
                $daysInMonth = $billingPeriodStart->daysInMonth;
                $amount = round(($daysToBill / $daysInMonth) * $subModule->price_per_month, 2);
                if ($amount > 0) {
                    $totalAmount += $amount;

                    $invoiceItemsData[] = [
                        'name' => $subModule->name . ' (' . $submoduleBillingStart->toDateString() . ' to ' . $submoduleBillingEnd->toDateString() . ')',
                        'qty' => 1,
                        'price' => $amount,
                        'tax' => 0,
                        'hsn' => '',
                        'details' => [
                            'billed_from' => $submoduleBillingStart->toDateString(),
                            'billed_until' => $submoduleBillingEnd->toDateString(),
                        ],
                    ];
                    $submodule_key[] = $subModule->Key;
                    $store_module_ids[] = $storeModule->id;
                }
            }

            if ($totalAmount > 0) {

                // generate bill 
                $invoice = new ManualInvoice();
                $invoice->invoice_id = Helpers::generateInvoiceIdAdmin();
                $invoice->invoice_serial = BusinessSetting::where('key', 'admin_bill_serial_number')->first()->value - 1;
                $invoice->vendor_id = NULL;
                $invoice->bill_to = $store->id;
                $invoice->bill_to_type = 'vendor';
                $invoice->module_id = $store->module_id;
                $invoice->total_amount = $totalAmount;
                $invoice->payment_method = 'Cash';
                $invoice->tax_type = 'non-gst';
                $invoice->payment_status = 'Unpaid';
                $invoice->payment_date = date('Y-m-d');
                $invoice->generated_by = 'admin';
                $invoice->key_purpose = 'services_billing';
                $invoice->service_key = json_encode($submodule_key);
                $invoice->store_module_ids = json_encode($store_module_ids);
                $invoice->other_details = json_encode($invoiceItemsData);
                $invoice->save();

                foreach ($invoiceItemsData as $itemData) {
                    $InvoiceItem = new InvoiceItem();
                    $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
                    $InvoiceItem->manual_invoice_id = $invoice->id;
                    $InvoiceItem->name = $itemData['name'];
                    $InvoiceItem->qty = $itemData['qty'];
                    $InvoiceItem->price = $itemData['price'];
                    $InvoiceItem->tax = $itemData['tax'];
                    $InvoiceItem->hsn = $itemData['hsn'];
                    $InvoiceItem->save();
                }

                try {
                    $data = _createBillPdf($invoice, 'admin');
                    $invoice->update(['pdf' => $data['pdf']]);
                } catch (\Throwable $th) {
                    // log or ignore
                }
            }
            // }
        }
    }
}
