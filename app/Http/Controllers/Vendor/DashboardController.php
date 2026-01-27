<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcceptedServiceRequest;
use App\Models\AccountTransaction;
use App\Models\Attendance;
use App\Models\BusinessSetting;
use App\Models\Coupon;
use App\Models\InAppNotification;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\Leave;
use App\Models\ManualInvoice;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\OrderTransaction;
use App\Models\Project;
use App\Models\ServiceInvoice;
use App\Models\StoreConfig;
use App\Models\StoreCustomer;
use App\Models\StoreGallery;
use App\Models\StoreLedgerEntry;
use App\Models\StoreTask;
use App\Models\StoreVoucher;
use App\Models\SubscriptionPlanRequest;
use App\Models\User;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL as FacadesURL;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Validation\Validator;
use Soap\Url;

class DashboardController extends Controller
{

    public function dashboard(Request $request)
    {
        $storeId = Helpers::get_store_id();
        if (auth('vendor')->check()) {

            $preset = request('date_range') ?? Cookie::get('date_range')  ?? 'last_30_days';
            if ($request->has('date_range')) {
                Cookie::queue('date_range', $request->date_range, 60 * 24 * 360);
            }
            $custom = request('custom_date_range') ?? null;
            $range = Helpers::calculatePresetDates($preset, $custom);
            $formatted_from  = $range['start'];
            $formatted_to = $range['end'];
            $from = $range['start']->toDateString();
            $to  = $range['end']->toDateString();

            $earning = [];
            $commission = [];
            $expenses = [];


            // ===================== LEADS =============================
            $baseQuery = DB::table('service_requests')
                ->join('items', 'service_requests.item_id', '=', 'items.id')
                ->join('categories', 'items.category_id', '=', 'categories.id')
                ->join('users', 'service_requests.user_id', '=', 'users.id')
                ->whereRaw("FIND_IN_SET(?, service_requests.sent_to)", [Helpers::get_store_id()])
                ->whereRaw('FIND_IN_SET(?, items.store_ids)', [Helpers::get_store_id()])
                ->whereBetween('service_requests.created_at', [$formatted_from, $formatted_to]);

            $leadStatistics['new'] = (clone $baseQuery)
                ->where('service_requests.created_at', '>', now()->subMinutes(Helpers::get_lead_exp_minutes()))
                ->distinct('service_requests.id')
                ->count();
            // $leadStatistics['in_progress'] = (clone $baseQuery)
            //     ->join('accepted_service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            //     ->where(function ($q) {
            //         $q->whereNull('accepted_service_requests.tieup')
            //             ->orWhere('accepted_service_requests.current_status', 'Confirmed');
            //     })
            //     ->where('accepted_service_requests.vendor_id', Helpers::get_store_id())
            //     ->distinct('service_requests.id')
            //     ->count();
            $leadStatistics['in_progress'] = (clone $baseQuery)
                ->join('accepted_service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
                ->where('accepted_service_requests.current_status', 9)
                ->where('accepted_service_requests.vendor_id', Helpers::get_store_id())
                ->distinct('service_requests.id')
                ->count();

            $completedBase = DB::table('accepted_service_requests')
                ->join('service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
                ->join('stores', 'stores.id', 'accepted_service_requests.vendor_id')
                ->join('items', 'items.id', 'service_requests.item_id')
                ->where('stores.id', Helpers::get_store_id())
                ->where('accepted_service_requests.current_status', 'Completed')
                ->whereRaw("FIND_IN_SET(?, service_requests.sent_to)", [Helpers::get_store_id()])
                ->whereBetween('service_requests.created_at', [$formatted_from, $formatted_to]);

            $leadStatistics['completed'] = (clone $completedBase)->count();

            // =================== TASKS ==================================
            $baseTasks = StoreTask::where('store_id', $storeId)->whereNull('parent_id');
            $taskStats['new'] = (clone $baseTasks)->where('status', 'New')->count();
            $taskStats['completed'] = (clone $baseTasks)->where('status', 'Completed')->count();
            $taskStats['in_progress'] = (clone $baseTasks)->whereIn(DB::raw('LOWER(status)'), [
                'in progress',
                'in_progress',
                'inprogress',
            ])->count();

            // ====================== INVENTORY =====================
            $inventory_items = InventoryItem::where('store_id', $storeId);
            $inventoryStats['total'] = (clone $inventory_items)->count();
            $inventoryStats['products'] = (clone $inventory_items)->where('item_type', 'product')->count();
            $inventoryStats['services'] = (clone $inventory_items)->where('item_type', 'service')->count();

            // ================ ACCOUNT SUMMARY ==================
            // $accountBaseQ = StoreVoucher::where('store_id', $storeId)
            //     ->whereBetween("voucher_date", [$formatted_from, $formatted_to]);
            // $accountstats['expense'] = (clone $accountBaseQ)->where('status', 'approved')->where('debit_entity_type', 'store')->sum('total_amount');
            // $accountstats['income'] = (clone $accountBaseQ)->where('status', 'approved')->where('credit_entity_type', 'store')->sum('total_amount');
            // $accountstats['pending_payments'] = (clone $accountBaseQ)->where('status', 'pending')->sum('total_amount');


            $baseQuery = StoreLedgerEntry::where('store_id', $storeId)
                ->whereHas('voucher', function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('debit_entity_type', 'store')
                            ->orWhere('credit_entity_type', 'store');
                    });
                });
            $accountstats['income'] = (clone $baseQuery)
                ->whereHas('voucher', function ($q) {
                    $q->where('credit_entity_type', 'store');
                })
                ->where('credit', '>', 0)
                ->sum('credit');
            $accountstats['expense'] = (clone $baseQuery)
                ->whereHas('voucher', function ($q) {
                    $q->where('debit_entity_type', 'store');
                })
                ->where('debit', '>', 0)
                ->sum('debit');

            $accountstats['pending_payments'] = (clone $baseQuery)
                ->where('status', 'pending')
                ->sum(DB::raw('credit - debit'));

            // ================= EMPLOYEE ACTIVITY ===================
            $empBaseQ = VendorEmployee::where('store_id', $storeId);
            $empStats['total_employees'] = (clone $empBaseQ)->count();
            $empStats['present_employees'] = _clockedInEmployee(true);

            return view('vendor-views.dashboard', compact('empStats', 'inventoryStats', 'taskStats', 'accountstats', 'leadStatistics'));
        } else {
            $employee_id = Helpers::get_loggedin_user()->id;
            $month = (int) date('n');
            $year = date('Y');
            // $attendance = Attendance::where('month', $month)
            //     ->where('year', $year)
            //     ->where('employee_id', $employee_id)
            //     ->get();
            $att = Attendance::selectRaw("
            SUM(label = 'P') as present,
            SUM(label = 'A') as absent,
            SUM(label = 'H') as holiday
            ")
                ->where('year', $year)
                ->where('month', $month)
                ->where('employee_id', $employee_id)
                ->first();
            $att['totalDays'] = date('t');

            $leaves = Leave::selectRaw("
    SUM(leave_type = 'SL') as SL,
    SUM(leave_type = 'CL') as CL,
    SUM(leave_type IN ('HDF', 'HDS')) as HD
")
                ->where('year', $year)
                ->where('month', $month)
                ->where('employee_type', 'vendor_employee')
                ->where('emp_id', $employee_id)
                ->first();

            $store_config = StoreConfig::where('store_id', $storeId)->first();
            $leaves['allowed_leaves'] = ($store_config->cl_for_employees ?? 0) + $store_config->sl_for_employees ?? 0;
            $leaves['taken'] = $leaves['CL'] + $leaves['SL']  + ($leaves['HD'] / 2);
            // prx($leaves);

            $employeeTaskIds = StoreTask::where('employee_id', $employee_id)
                ->pluck('id')
                ->toArray();

            $assingned_tasks = StoreTask::where('task_type', 'common')
                ->where(function ($query) use ($employeeTaskIds, $employee_id) {
                    $query->where(function ($q) use ($employeeTaskIds, $employee_id) {
                        $q->whereNotNull('parent_id')
                            ->whereNotIn('parent_id', $employeeTaskIds)
                            ->where('offered_to', $employee_id);
                    })
                        ->orWhere(function ($q) use ($employee_id) {
                            $q->whereNull('parent_id')
                                ->where('offered_to', $employee_id);
                        });
                })->limit(8)->get();


            $assigned_projects = Project::with('teamMembers')
                ->where('vendor_id', Helpers::get_store_id())
                ->whereHas('teamMembers', function ($query) use ($employee_id) {
                    $query->where('employee_id', $employee_id);
                })
                ->get();

            // prx($assigned_projects);

prx(_clockedInEmployee());
            return view('vendor-views.staff_dashboard', compact('att', 'leaves', 'assingned_tasks', 'assigned_projects', 'employee_id'));
        }
    }

    public function lastNotification()
    {
        if (auth('vendor')->check()) {
            $type = 'vendor';
            $reciever = Helpers::get_store_id();
        } else {
            $type = 'vendor_employee';
            $reciever = auth('vendor_employee')->id();
        }
        $notification = InAppNotification::where('reciever', $reciever)
            ->where('user_type', $type)
            ->where('is_read', 0)
            ->latest('id')
            ->select('id', 'title', 'message', 'created_at')
            ->first();

        return response()->json([
            'count' => $notification ? 1 : 0,
            'data'  => $notification
        ]);
    }

    public function request_subscription_plan(Request $request)
    {
        $validator = FacadesValidator::make($request->all(), [
            'features' => 'required|array|min:1',
        ], ['features.required' => 'Please Select Atleast One Feature', 'features.array' => 'Please Select Atleast One Feature']);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $store_id = Helpers::get_store_id();

        $req = new SubscriptionPlanRequest();
        $req->store_id = $store_id;
        $req->features = json_encode($request->features);
        $req->additional_requirements = $request->additional_requirements;
        $req->save();

        if ($req->save()) {
            return response()->json(['status' => true, 'message' => "Requested successfully. We’ve received your request and will get back to you shortly."]);
        } else {
            return response()->json(['status' => false, 'message' => "Some Error Occurred"]);
        }
    }
    public function dashboard_old(Request $request)
    {
        $params = [
            'statistics_type' => $request['statistics_type'] ?? 'overall'
        ];
        session()->put('dash_params', $params);

        $data = self::dashboard_order_stats_data();
        $earning = [];
        $commission = [];
        $from = Carbon::now()->startOfYear()->format('Y-m-d');
        $to = Carbon::now()->endOfYear()->format('Y-m-d');

        $from_day = date('Y-m-01');
        $to_day = date('Y-m-31');

        $PLStatistics['earning'] = 0;
        $PLStatistics['commission'] = 0;




        if (Helpers::get_store_data()['module_id'] == 5) {

            $store_earnings = OrderTransaction::NotRefunded()->where(['vendor_id' => Helpers::get_store_id()])->select(
                DB::raw('IFNULL(sum(store_amount),0) as earning'),
                DB::raw('IFNULL(sum(admin_commission + admin_expense - delivery_fee_comission),0) as commission'),
                DB::raw('YEAR(created_at) year, MONTH(created_at) month')
            )->whereBetween('created_at', [$from, $to])->groupby('year', 'month')->get()->toArray();
            for ($inc = 1; $inc <= 12; $inc++) {
                $earning[$inc] = 0;
                $commission[$inc] = 0;
                foreach ($store_earnings as $match) {
                    if ($match['month'] == $inc) {
                        $earning[$inc] = $match['earning'];
                        $commission[$inc] = $match['commission'];
                    }
                }
            }


            $statistics = OrderTransaction::NotRefunded()->where(['vendor_id' => Helpers::get_store_id()])->select(
                DB::raw('IFNULL(sum(store_amount),0) as earning'),
                DB::raw('IFNULL(sum(admin_commission + admin_expense - delivery_fee_comission),0) as commission'),
                DB::raw('YEAR(created_at) year, MONTH(created_at) month')
            )->whereBetween('created_at', [$from_day, $to_day])->groupby('year', 'month')->get()->toArray();

            if (!empty($statistics)) {
                // prx($statistics);
                foreach ($statistics as $key => $value) {
                    $PLStatistics['earning'] += $value['earning'];
                    $PLStatistics['commission'] +=   $value['commission'];
                }
            }
        } else {
            $earning = [];
            $commission = [];
            $expenses = [];



            // Fetch monthly earnings
            $store_earnings = ServiceInvoice::where(['vendor_id' => Helpers::get_store_id(), 'payment_status' => 'Paid'])
                ->whereBetween('created_at', [$from_day, $to_day])
                ->select(
                    DB::raw('IFNULL(sum(total_amount),0) as earning'),
                    DB::raw('YEAR(created_at) as year, MONTH(created_at) as month')
                )
                ->groupBy('year', 'month')
                ->get()
                ->toArray();

            // Fetch monthly expenses
            $store_expenses = DB::table('account_transactions')
                ->where('from_id', Helpers::get_store_id())
                ->where('action', 'debit')
                ->whereBetween('created_at', [$from_day, $to_day])
                ->select(
                    DB::raw('IFNULL(sum(amount),0) as expense'),
                    DB::raw('YEAR(created_at) as year, MONTH(created_at) as month')
                )
                ->groupBy('year', 'month')
                ->get()
                ->toArray();

            for ($inc = 1; $inc <= 12; $inc++) {
                $earning[$inc] = 0;
                $commission[$inc] = 0;

                // Assign earnings month-wise
                foreach ($store_earnings as $match) {
                    if ($match['month'] == $inc) {
                        $earning[$inc] = $match['earning'];
                    }
                }

                // Assign expenses month-wise
                foreach ($store_expenses as $match) {
                    if ($match->month == $inc) {
                        $commission[$inc] = $match->expense;
                    }
                }
            }

            // Now you have $earning and $expenses arrays populated month-wise

            $statistics = ServiceInvoice::where(['vendor_id' => Helpers::get_store_id(), 'payment_status' => 'Paid'])->whereBetween('created_at', [$from_day, $to_day])->select('invoice_id', 'total_amount', 'created_at')->get()->toArray();


            if (!empty($statistics)) {
                foreach ($statistics as $key => $value) {
                    $PLStatistics['earning'] += $value['total_amount'];
                }
            }
        }

        $accountQ = DB::table('account_transactions')->where('from_id', Helpers::get_store_id())->whereBetween('created_at', [$from_day . ' 00:00:00', $to_day . ' 23:59:59'])->where('from_type', 'store')->whereNot('amount', 0)->where('action', 'debit')->sum('amount');
        $PLStatistics['commission']  = $accountQ;

        $accountQ2 = DB::table('accounts')->where('store_id', Helpers::get_store_id())->whereBetween('created_at', [$from_day . ' 00:00:00', $to_day . ' 23:59:59'])->where('status', 'completed')->where('type', 'expense')->whereNot('amount', 0)->sum('amount');
        $PLStatistics['commission']  += $accountQ2;

        $invoices = DB::table('manual_invoices')->join('users', 'users.id', 'manual_invoices.bill_to')->where('vendor_id', Helpers::get_store_id())->select('users.f_name', 'users.l_name', 'manual_invoices.*')->whereBetween('manual_invoices.created_at',  [$from_day . ' 00:00:00', $to_day . ' 23:59:59'])->get();
        $totalAmount = 0;
        foreach ($invoices as $key => $value) {
            $items = DB::table('invoice_items')->where('rand_invoice_id', $value->invoice_id)->get();
            foreach ($items as $key2 => $value2) {
                $totalAmount +=  _taxIncludedPrice($value2->price * $value2->qty, $value2->tax);
            }
        }

        $PLStatistics['commission']  += $totalAmount;

        if (Helpers::get_store_data()['module_id'] == 5) {
            $top_sell = Item::orderBy("order_count", 'desc')
                ->take(6)
                ->get();
            $most_rated_items = Item::orderBy('rating_count', 'desc')
                ->take(6)
                ->get();
            $data['most_rated_items'] = $most_rated_items;
        } else {
            $top_sell = Item::withoutGlobalScopes()
                ->join('stores', function ($join) {
                    $join->whereRaw('FIND_IN_SET(stores.id, items.store_ids) > 0');
                })
                ->leftJoin(
                    DB::raw('(SELECT service_requests.item_id, COUNT(*) as service_request_count 
                FROM service_requests 
                INNER JOIN accepted_service_requests ON service_requests.id = accepted_service_requests.service_request_id 
                WHERE accepted_service_requests.current_status = "Completed" and accepted_service_requests.vendor_id =  ' . Helpers::get_store_id() . '
                GROUP BY service_requests.item_id) as request_counts'),
                    'items.id',
                    '=',
                    'request_counts.item_id'
                )
                ->where('stores.active', 1)
                ->where('stores.id', Helpers::get_store_id())
                ->select('items.*', DB::raw('COALESCE(request_counts.service_request_count, 0) as order_count'))
                ->distinct()
                ->orderBy('order_count', 'desc')
                ->take(6)
                ->get();
        }

        $storeId = Helpers::get_store_id();

        $leadStatistics['new'] =  DB::table('service_requests')
            ->join('items', 'service_requests.item_id', '=', 'items.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->join('users', 'service_requests.user_id', '=', 'users.id')
            ->whereRaw("FIND_IN_SET(?, service_requests.sent_to)", [Helpers::get_store_id()])
            ->whereRaw('FIND_IN_SET(?, items.store_ids)', [Helpers::get_store_id()])
            ->whereBetween('service_requests.created_at', [$from, $to])
            ->select(
                'service_requests.*',
                'items.name as item_name',
                'items.image as image',
                'categories.name as category_name',
                'users.f_name as f_name',
                'users.id as uid'
            )->leftJoin('accepted_service_requests', 'service_requests.id', '=', 'accepted_service_requests.service_request_id')
            ->where(function ($query) {
                $query->whereRaw('NOT FIND_IN_SET(?, service_requests.accepted_by)', [Helpers::get_store_id()])
                    ->orWhereNull('service_requests.accepted_by');
            })
            ->where('service_requests.created_at', '>', now()->subMinutes(Helpers::get_lead_exp_minutes()))
            ->distinct('service_requests.id')
            ->get()
            ->count();

        $leadStatistics['confirmed'] =    $query = DB::table('service_requests')
            ->join('items', 'service_requests.item_id', '=', 'items.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->join('users', 'service_requests.user_id', '=', 'users.id')
            ->whereRaw('FIND_IN_SET(?, items.store_ids)', [Helpers::get_store_id()])
            ->whereRaw("FIND_IN_SET(?, service_requests.sent_to)", [Helpers::get_store_id()])
            ->join('accepted_service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->where(function ($query) {
                $query->whereNull('accepted_service_requests.tieup')
                    ->orWhere('accepted_service_requests.current_status', 'Confirmation Request Sent');
            })
            ->where('accepted_service_requests.vendor_id', Helpers::get_store_id())
            ->distinct('service_requests.id')
            ->get()
            ->count();


        $leadStatistics['cancelled'] = DB::table('cancelled_service_requests')
            ->join('service_requests', 'cancelled_service_requests.service_request_id', 'service_requests.id')
            ->join('stores', 'stores.id', 'cancelled_service_requests.vendor_id')
            ->whereRaw("FIND_IN_SET(?, service_requests.sent_to)", [Helpers::get_store_id()])

            ->join('items', 'items.id', 'service_requests.item_id')
            ->where('stores.id', Helpers::get_store_id())
            ->select('service_requests.id as service_id', 'items.name as item_name', 'items.image as item_image',  'cancelled_service_requests.*')
            ->get()
            ->count();

        $leadStatistics['completed'] = DB::table('accepted_service_requests')
            ->join('service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->join('stores', 'stores.id', 'accepted_service_requests.vendor_id')
            ->join('items', 'items.id', 'service_requests.item_id')
            ->where('stores.id', Helpers::get_store_id())
            ->whereRaw("FIND_IN_SET(?, service_requests.sent_to)", [Helpers::get_store_id()])

            ->where('accepted_service_requests.current_status', 'Completed')
            ->select('service_requests.id as service_id', 'items.name as item_name', 'items.image as item_image',  'accepted_service_requests.*')
            ->get()
            ->count();

        $query = DB::table('service_requests')
            ->join('items', 'service_requests.item_id', '=', 'items.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->join('users', 'service_requests.user_id', '=', 'users.id')
            ->whereRaw("FIND_IN_SET(?, service_requests.sent_to)", [$storeId])
            ->whereBetween('service_requests.created_at', [$from, $to])
            ->select(
                'service_requests.*',
                'items.name as item_name',
                'items.image as image',
                'categories.name as category_name',
                'users.f_name as f_name',
                'users.id as uid'
            );
        $query->leftJoin('accepted_service_requests', 'service_requests.id', '=', 'accepted_service_requests.service_request_id')
            ->where(function ($query) use ($storeId) {
                $query->whereRaw('NOT FIND_IN_SET(?, service_requests.accepted_by)', [$storeId])
                    ->orWhereNull('service_requests.accepted_by');
            });
        $query->whereRaw("FIND_IN_SET(?, service_requests.sent_to)", [$storeId])->where('service_requests.created_at', '<', now()->subMinutes(Helpers::get_lead_exp_minutes()));
        // ->whereNull('accepted_service_requests.id');
        $query->addSelect(
            DB::raw("'missed' as additional_status")
        )
            ->distinct('service_requests.id')
            ->get();

        $leadStatistics['missed'] =  $query->count();

        $data['top_sell'] = $top_sell;

        $data['new_tasks'] = StoreTask::where('store_id', $storeId)->where('status', 'Pending')->whereNull('parent_id')->count();
        $data['new_projects'] = Project::where('vendor_id', $storeId)->where('progress_status', 'New')->count();

        $data['mycustomersCount'] = StoreCustomer::where('store_id', $storeId)->count();
        $data['mytasksCount'] = StoreTask::where('store_id', $storeId)->whereNull('parent_id')->count();

        // prx(StoreTask::where('store_id', $storeId)
        //     ->whereNull('parent_id')
        //     ->pluck('id')
        //     ->implode(',')
        // );   

        $data['mybillsCount'] = ServiceInvoice::where('vendor_id', $storeId)->count() + ManualInvoice::where('vendor_id', $storeId)->count();
        // prx(ManualInvoice::where('vendor_id', $storeId)->count()); 


        return view('vendor-views.dashboard', compact('data', 'earning', 'commission', 'params', 'PLStatistics', 'leadStatistics'));
    }

    public function view_terms_and_conditions(Request $request)
    {
        $terms_and_conditions = '';
        if (Helpers::get_store_data()->module_id == 5) {
            $fsdf =  BusinessSetting::where('key', 'shop_vendor_tnc')->first();
        } elseif (Helpers::get_store_data()->module_id == 6) {
            $fsdf =  BusinessSetting::where('key', 'service_vendor_tnc')->first();
        }
        if ($fsdf) {
            $terms_and_conditions = $fsdf->value;
        }
        return view('vendor-views.terms-and-conditions', compact('terms_and_conditions'));
    }
    public function order_invoices(Request $request)
    {
        $key = explode(' ', request()?->search);
        Order::where(['checked' => 0])->where('store_id', Helpers::get_store_id())->update(['checked' => 1]);

        $orders = Order::with(['customer'])->whereNotIn('order_status', (config('order_confirmation_model') == 'store' || Helpers::get_store_data()->self_delivery_system) ? ['failed', 'canceled', 'refund_requested', 'refunded'] : ['pending', 'failed', 'canceled', 'refund_requested', 'refunded'])
            ->StoreOrder()->NotDigitalOrder()
            ->where('store_id', \App\CentralLogics\Helpers::get_store_id())
            ->orderBy('schedule_at', 'desc')
            ->paginate(config('default_pagination'));
        $status = 'all';
        return view('vendor-views.invoice.order_invoices', compact('orders', 'status'));
    }
    public function invoices(Request $request)
    {

        if (isset($request->from)) {
            $fromdate = $request->from;
            $todate = $request->to;
        } else {
            $fromdate = date('Y-m') . '-01';
            $todate = date('Y-m') . '-30';
        }

        $invoices1 = ServiceInvoice::where('vendor_id', Helpers::get_store_id())->whereBetween('created_at', [$fromdate . ' 00:00:00', $todate . ' 23:59:59'])->whereNotNull('pdf')->get();
        $invoices2 = ManualInvoice::where('vendor_id', Helpers::get_store_id())->whereBetween('created_at', [$fromdate . ' 00:00:00', $todate . ' 23:59:59'])->get();
        $invoices = $invoices1->merge($invoices2);

        return view('vendor-views.invoice.dashboard', compact('invoices', 'fromdate', 'todate'));
    }
    public function filter_pnl(Request $request)
    {
        $year_mont = $request->month;
        $from_day = $year_mont . '-01';
        $to_day =  $year_mont . '-31';

        $PLStatistics['earning'] = 0;
        $PLStatistics['commission'] = 0;

        //accounts
        $accountQ2 = DB::table('accounts')->where('store_id', Helpers::get_store_id())->whereBetween('created_at', [$from_day . ' 00:00:00', $to_day . ' 23:59:59'])->where('status', 'completed')->where('type', 'expense')->whereNot('amount', 0)->sum('amount');
        $PLStatistics['commission']  =  $accountQ2;

        // wallet 
        $expenses = AccountTransaction::where('type', 'collected')
            ->where('created_by', 'store')
            ->where('from_id', Helpers::get_store_id())
            ->where('action', 'debit')
            ->whereBetween('created_at', [$from_day . ' 00:00:00', $to_day . ' 23:59:59'])
            ->where('from_type', 'store')->sum('amount');

        $PLStatistics['commission']  += $expenses;

        //offline billing
        $invoices = DB::table('manual_invoices')->where('bill_to', Helpers::get_store_id())->whereBetween('manual_invoices.created_at',  [$from_day . ' 00:00:00', $to_day . ' 23:59:59'])->get();
        // prx( $invoices);
        $totalAmount = 0;
        foreach ($invoices as $key => $value) {
            $items = DB::table('invoice_items')->where('rand_invoice_id', $value->invoice_id)->get();
            foreach ($items as $key2 => $value2) {
                $totalAmount +=  _taxIncludedPrice($value2->price * $value2->qty, $value2->tax);
            }
        }

        $PLStatistics['commission']  += $totalAmount;

        if (Helpers::get_store_data()['module_id'] == 5) {
            $statistics = OrderTransaction::NotRefunded()->where(['vendor_id' => Helpers::get_store_id()])->select(
                DB::raw('IFNULL(sum(store_amount),0) as earning'),
                DB::raw('IFNULL(sum(admin_commission + admin_expense - delivery_fee_comission),0) as commission'),
                DB::raw('YEAR(created_at) year, MONTH(created_at) month')
            )->whereBetween('created_at', [$from_day, $to_day])->groupby('year', 'month')->get()->toArray();

            if (!empty($statistics)) {
                prx($statistics);
                foreach ($statistics as $key => $value) {
                    $PLStatistics['earning'] += $value->earning;
                    $PLStatistics['commission'] +=   $value->commission;
                }
            }
            $accountQ2 = DB::table('accounts')->where('store_id', Helpers::get_store_id())->whereBetween('created_at', [$from_day . ' 00:00:00', $to_day . ' 23:59:59'])->where('status', 'completed')->where('type', 'expense')->whereNot('amount', 0)->sum('amount');
            $PLStatistics['commission']  +=  $accountQ2;
        } else {


            // Fetch monthly earnings
            $store_earnings = ServiceInvoice::where(['vendor_id' => Helpers::get_store_id(), 'payment_status' => 'Paid'])
                ->whereBetween('created_at', [$from_day, $to_day])
                ->select(
                    DB::raw('IFNULL(sum(total_amount),0) as earning'),
                    DB::raw('YEAR(created_at) as year, MONTH(created_at) as month')
                )
                ->groupBy('year', 'month')
                ->get()
                ->toArray();

            // Fetch monthly expenses
            $store_expenses = DB::table('account_transactions')
                ->where('from_id', Helpers::get_store_id())
                ->where('action', 'debit')
                ->whereBetween('created_at', [$from_day, $to_day])
                ->select(
                    DB::raw('IFNULL(sum(amount),0) as expense'),
                    DB::raw('YEAR(created_at) as year, MONTH(created_at) as month')
                )
                ->groupBy('year', 'month')
                ->get()
                ->toArray();

            for ($inc = 1; $inc <= 12; $inc++) {
                $earning[$inc] = 0;
                $commission[$inc] = 0;

                // Assign earnings month-wise
                foreach ($store_earnings as $match) {
                    if ($match['month'] == $inc) {
                        $earning[$inc] = $match['earning'];
                    }
                }

                // Assign expenses month-wise
                foreach ($store_expenses as $match) {
                    if ($match->month == $inc) {
                        $commission[$inc] = $match->expense;
                    }
                }
            }

            // Now you have $earning and $expenses arrays populated month-wise
            $statistics = ServiceInvoice::where(['vendor_id' => Helpers::get_store_id(), 'payment_status' => 'Paid'])->whereBetween('created_at', [$from_day, $to_day])->get()->toArray();

            if (!empty($statistics)) {
                foreach ($statistics as $key => $value) {
                    $PLStatistics['earning'] += $value['total_amount'];
                }
            }
        }

        $html = '';
        if ($PLStatistics['commission'] > $PLStatistics['earning']) {
            $html .= '<h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                    <span>Loss</span>
                </h6>
                <span class="card-title text-danger">
                  -' . _price($PLStatistics['earning'] - $PLStatistics['commission']) . '
                </span>';
        } else {
            $html .= '<h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                    <span>Profit</span>
                </h6>
                <span class="card-title text-success">
                +' . _price($PLStatistics['earning'] - $PLStatistics['commission']) . '
                </span>';
        }
        return ['html' => $html, 'earning' => _price($PLStatistics['earning']), 'commission' => _price($PLStatistics['commission'])];
    }
    public function  notifications()
    {
        if (auth('vendor')->check()) {
            $type = 'vendor';
            $notifications = tap(
                InAppNotification::where('user_type', $type)
                    ->where('reciever', Helpers::get_store_id())
                    ->orderBy('id', 'desc')
            )->update(['is_read' => 1])->get();
        } else {
            $type = 'vendor_employee';
            $notifications = tap(
                InAppNotification::where('user_type', $type)
                    ->where('reciever', Helpers::get_loggedin_user()->id)
                    ->orderBy('id', 'desc')
            )->update(['is_read' => 1])->get();
        }

        return view('vendor-views.notifications', compact('notifications'));
    }

    public function store_data()
    {
        $new_pending_order = DB::table('orders')->where(['checked' => 0])->where('store_id', Helpers::get_store_id())->where('order_status', 'pending');
        if (config('order_confirmation_model') != 'store' && !Helpers::get_store_data()->self_delivery_system) {
            $new_pending_order = $new_pending_order->where('order_type', 'take_away');
        }
        $new_pending_order = $new_pending_order->count();
        $new_confirmed_order = DB::table('orders')->where(['checked' => 0])->where('store_id', Helpers::get_store_id())->whereIn('order_status', ['confirmed', 'accepted'])->whereNotNull('confirmed')->count();

        return response()->json([
            'success' => 1,
            'data' => ['new_pending_order' => $new_pending_order, 'new_confirmed_order' => $new_confirmed_order]
        ]);
    }

    public function order_stats(Request $request)
    {
        $params = session('dash_params');
        foreach ($params as $key => $value) {
            if ($key == 'statistics_type') {
                $params['statistics_type'] = $request['statistics_type'];
            }
        }
        session()->put('dash_params', $params);

        $data = self::dashboard_order_stats_data();
        return response()->json([
            'view' => view('vendor-views.partials._dashboard-order-stats', compact('data'))->render()
        ], 200);
    }

    public function dashboard_order_stats_data()
    {
        $params = session('dash_params');
        $today = $params['statistics_type'] == 'today' ? 1 : 0;
        $this_month = $params['statistics_type'] == 'this_month' ? 1 : 0;

        $confirmed = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['store_id' => Helpers::get_store_id()])->whereIn('order_status', ['confirmed', 'accepted'])->whereNotNull('confirmed')->StoreOrder()->NotDigitalOrder()->OrderScheduledIn(30)->count();

        $cooking = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['order_status' => 'processing', 'store_id' => Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count();

        $ready_for_delivery = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['order_status' => 'handover', 'store_id' => Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count();

        $item_on_the_way = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->ItemOnTheWay()->where(['store_id' => Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count();

        $delivered = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['order_status' => 'delivered', 'store_id' => Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count();

        $refunded = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['order_status' => 'refunded', 'store_id' => Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count();

        $scheduled = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->Scheduled()->where(['store_id' => Helpers::get_store_id()])->where(function ($q) {
            if (config('order_confirmation_model') == 'store') {
                $q->whereNotIn('order_status', ['failed', 'canceled', 'refund_requested', 'refunded']);
            } else {
                $q->whereNotIn('order_status', ['pending', 'failed', 'canceled', 'refund_requested', 'refunded'])->orWhere(function ($query) {
                    $query->where('order_status', 'pending')->where('order_type', 'take_away');
                });
            }
        })->StoreOrder()->NotDigitalOrder()->count();

        $all = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['store_id' => Helpers::get_store_id()])
            ->where(function ($query) {
                return $query->whereNotIn('order_status', (config('order_confirmation_model') == 'store' || \App\CentralLogics\Helpers::get_store_data()->self_delivery_system) ? ['failed', 'canceled', 'refund_requested', 'refunded'] : ['pending', 'failed', 'canceled', 'refund_requested', 'refunded'])
                    ->orWhere(function ($query) {
                        return $query->where('order_status', 'pending')->where('order_type', 'take_away');
                    });
            })
            ->StoreOrder()->NotDigitalOrder()->count();

        $data = [
            'confirmed' => $confirmed,
            'cooking' => $cooking,
            'ready_for_delivery' => $ready_for_delivery,
            'item_on_the_way' => $item_on_the_way,
            'delivered' => $delivered,
            'refunded' => $refunded,
            'scheduled' => $scheduled,
            'all' => $all,
        ];

        return $data;
    }

    public function updateDeviceToken(Request $request)
    {
        $vendor = Vendor::find(Helpers::get_store_id());
        $vendor->firebase_token =  $request->token;

        $vendor->save();

        return response()->json(['Token successfully stored.']);
    }
    public function gallery(Request $request)
    {
        $galleries = StoreGallery::where('store_id', Helpers::get_store_id())->paginate(40);
        return view('vendor-views.gallery.index', compact('galleries'));
    }
    public function gallery_bulk_delete(Request $request)
    {
        foreach ($request->gallery_id as $key => $value) {
            $gallery = StoreGallery::where('id', $value)->first();
            Helpers::delete_file('store/gallery/',  $gallery->image);
            $gallery->delete();
        }
        Toastr::success('Bulk Deleted Successfully');
        return redirect()->back();
    }
    public function gallery_delete(Request $request)
    {

        $gallery = StoreGallery::where('id', $request->id)->first();
        Helpers::delete_file('store/gallery/',  $gallery->image);
        $gallery->delete();
        return response()->json(['Image Deleted Successfully.']);
    }
    public function gallery_store(Request $request)
    {

        $request->validate([
            'file.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp',
        ]);

        if (!empty($request->file('file'))) {
            foreach ($request->file as $img) {
                $image_name = Helpers::upload('store/gallery/', 'png', $img);
                $gallery = new StoreGallery();
                $gallery->image = $image_name;
                $gallery->store_id = Helpers::get_store_id();
                $gallery->save();
            }
        }

        Toastr::success('Gallery Added Successfully');
        return redirect()->back();
    }
}
