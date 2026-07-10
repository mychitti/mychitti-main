<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Lead;
use Brian2694\Toastr\Facades\Toastr;
use App\Exports\AccountExport;
use Illuminate\Support\Facades\Storage;
use App\CentralLogics\Helpers;
use App\Exports\DayBookExport;
use App\Exports\JournalEntryExport;
use App\Imports\DayBookImport;
use App\Imports\JournalEntryImport;
use App\Models\Account;
use App\Models\AccountOption;
use App\Models\AssetDepreciation;
use App\Models\AuditLog;
use App\Models\CashBook;
use App\Models\DayBook;
use App\Models\Department;
use App\Models\Item;
use App\Models\ManualInvoice;
use App\Models\ManualTrial;
use App\Models\MonthlyMaintanance;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\RequestForm;
use App\Models\RequestRule;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Models\ServiceInvoice;
use App\Models\Staff;
use App\Models\StoreAccount;
use App\Models\StoreAsset;
use App\Models\StoreConfig;
use App\Models\StoreCustomer;
use App\Models\StoreLedgerEntry;
use App\Models\StoreVoucher;
use App\Models\VendorEmployee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Console\Helper\Helper;

class AccountController extends Controller
{

    public function index(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $v_id = Helpers::get_store_id();
        $account = Account::where('store_id', $v_id)->get();
        $ledger_entries = StoreLedgerEntry::with('account')->where('store_id', $storeId)->get();
        // prx( $ledger_entries);
        return view('vendor-views.account.index', compact('account', 'ledger_entries'));
    }
    public function edit(Request $request, $id)
    {
        $account = Account::find($id);
        return view('vendor-views.account.manage', compact('account'));
    }



    public function category_store(Request $request)
    {
        $staticType = 'category'; // or any type you're using

        $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('account_options')->where(function ($query) use ($staticType) {
                    return $query->where('type', $staticType);
                }),
            ],
            'ledger_account_type' => 'required',
        ], [
            'name.unique' => 'This category name already exists.',
        ]);

        // Save the record
        $account_option = new AccountOption();
        $account_option->store_id = Helpers::get_store_id();
        $account_option->name = $request->name;
        $account_option->type = $staticType;
        $account_option->parent_id = $request->ledger_account_type; // ledger account type id 
        $account_option->save();

        Toastr::success('Added Successfully');
        return redirect()->back();
    }
    public function ledger_account_type_store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('account_options')->where(function ($query) use ($request) {
                    return $query->where('type', 'ledger_account_type'); // Ensure 'type' is same as 'ledger_account_type'
                }),
            ],
        ], [
            'name.unique' => 'This Ledger account type already exists.',
        ]);

        // Save the record
        $account_option = new AccountOption();
        $account_option->store_id = Helpers::get_store_id();
        $account_option->name = $request->name;
        $account_option->type = 'ledger_account_type';
        $account_option->save();

        Toastr::success('Added Successfully');
        return redirect()->back();
    }
    public function dashboard(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $preset = request('date_range') ?? 'this_year';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        $ledger_entries = StoreLedgerEntry::with(['account', 'voucher'])
            ->where('store_id', $storeId)
            ->whereHas('voucher', function ($q) {
                $q->where(function ($q2) {
                    $q2->where('debit_entity_type', 'store')
                        ->orWhere('credit_entity_type', 'store');
                });
            })
            ->where(function ($q) {
                // If store is credit entity → credit > 0
                $q->whereHas('voucher', function ($sub) {
                    $sub->where('credit_entity_type', 'store');
                })->where('credit', '>', 0)
                    // Else if store is debit entity → debit > 0
                    ->orWhere(function ($sub) {
                        $sub->whereHas('voucher', function ($inner) {
                            $inner->where('debit_entity_type', 'store');
                        })->where('debit', '>', 0);
                    });
            });

        if ($request->has('date_range')) {
            $ledger_entries->whereBetween(DB::raw('DATE(entry_date)'), [$formatted_from, $formatted_to]);
        }

        $ledger_entries = $ledger_entries
            ->orderBy('created_at', 'desc')
            ->get();


        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();

        // Day-wise Income
        $incomeByDay = StoreVoucher::where('store_id', $storeId)
            ->whereBetween("voucher_date", [$start, $end])
            ->whereIn('voucher_type', ['Sales', 'Receipt'])
            ->where('status', 'approved')
            ->select(
                DB::raw("DATE(voucher_date) as day"),
                DB::raw("SUM(total_amount) as total")
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Day-wise Expense
        $expenseByDay = StoreVoucher::where('store_id', $storeId)
            ->whereBetween("voucher_date", [$start, $end])
            ->whereIn('voucher_type', ['Payment', 'Purchase'])
            ->where('status', 'approved')
            ->select(
                DB::raw("DATE(voucher_date) as day"),
                DB::raw("SUM(total_amount) as total")
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Generate all dates of current month
        $days = collect();
        $current = $start->copy();
        while ($current->lte($end)) {
            $days->push($current->format('Y-m-d'));
            $current->addDay();
        }

        // Convert grouped totals into continuous arrays
        $incomeData = $days->map(fn($day) => optional($incomeByDay->firstWhere('day', $day))->total ?? 0);
        $expenseData = $days->map(fn($day) => optional($expenseByDay->firstWhere('day', $day))->total ?? 0);

        $data['days'] = $days;
        $data['income_daywise'] = $incomeData;
        $data['expense_daywise'] = $expenseData;


        $data['expense'] = StoreVoucher::where('store_id', $storeId)->where('status', 'approved')
            ->whereBetween("voucher_date", [$formatted_from, $formatted_to])
            ->whereIn('voucher_type', ['Payment', 'Purchase'])->get();
        // prx( $data['expense']);


        $incomeQ = StoreVoucher::where('store_id', $storeId)->where('status', 'approved')
            ->whereBetween("voucher_date", [$formatted_from, $formatted_to])
            ->whereIn('voucher_type', ['Sales', 'Receipt']);

        $data['income'] = (clone $incomeQ)->where('status', 'approved')->get();
        $data['pending_payments'] = (clone $incomeQ)->where('status', 'pending')->get();

        return view('vendor-views.account.dashboard', compact('ledger_entries', 'preset', 'data'));
    }
    public function send_otp(Request $request)
    {
        $otp  = rand(1000, 9999);
        $phone = Helpers::get_store_data()->phone;

        $sendsms = _send_confirmation_sms('mobile_verification', $phone, $otp);

        $insert  = DB::table('phone_otp')->updateOrInsert([
            'phone' =>  $phone,
        ], [
            'otp' => $otp,
            'created_at' => now()
        ]);

        if ($sendsms && $insert) {
            return response()->json(['status' => true, 'message' => "OTP sent to store's registered phone number", 'action' => 'otp_sent']);
        } else {
            return response()->json(['status' => false, 'message' => 'Some error occured', 'sms_status' => $sendsms, 'action' => '']);
        }
    }
    public function reset_accounts_module(Request $request)
    {
        // verify otp
        $phone = Helpers::get_store_data()->phone;
        $otp = $request->otp;
        $verify = _verify_otp($phone, $otp);

        $storeId = Helpers::get_store_id();

        if ($verify) {

            // delete ledger entries 
            StoreVoucher::where('store_id', $storeId)->delete();
            StoreLedgerEntry::where('store_id', $storeId)->delete();
            DayBook::where('store_id', $storeId)->delete();
            CashBook::where('store_id', $storeId)->delete();
            RequestRule::where('store_id', $storeId)->delete();
            RequestForm::where('store_id', $storeId)->delete();
            StoreAccount::where('store_id', $storeId)->delete();
            MonthlyMaintanance::where('store_id', $storeId)->delete();
            AssetDepreciation::where('store_id', $storeId)->delete();
            AuditLog::where('store_id', $storeId)->delete();

            // settings reset 
            StoreConfig::updateOrInsert(['store_id' => $storeId], [
                'account_type' => 'normal',
                'resubmit_per_req_form' => 0,
                'monthly_maintnnce_req' => 'manual_pay',
            ]);
            _auditLogs('Account Management Reset');

            Toastr::success('Account Management Reset Successfully');
            return back();
        } else {
            Toastr::error('Incorrect OTP');
            return back();
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

            $data['serviceAmountPaid'] = ServiceInvoice::where('vendor_id', Helpers::get_store_id())->where('payment_status', 'Paid')->whereBetween('created_at', [$from_day, $to_day])->sum('total_amount');
            $data['serviceAmountUnpaid']  = ServiceInvoice::where('vendor_id', Helpers::get_store_id())->where('payment_status', 'Unpaid')->whereBetween('created_at', [$from_day, $to_day])->sum('total_amount');
            $data['manualAmountPaid'] = ManualInvoice::where('vendor_id', Helpers::get_store_id())->where('payment_status', 'Paid')->whereBetween('created_at', [$from_day, $to_day])->sum('total_amount');
            $data['manualAmountUnpaid'] = ManualInvoice::where('vendor_id', Helpers::get_store_id())->where('payment_status', 'Unpaid')->whereBetween('created_at', [$from_day, $to_day])->sum('total_amount');
            $data['paidInvoicesAmount'] = $data['serviceAmountPaid'] + $data['manualAmountPaid'];
            $data['unpaidInvoicesAmount'] = $data['serviceAmountUnpaid'] + $data['manualAmountUnpaid'];


            $statistics = ServiceInvoice::where(['vendor_id' => Helpers::get_store_id(), 'payment_status' => 'Paid'])->whereBetween('created_at', [$from_day, $to_day])->select('invoice_id', 'total_amount', 'created_at')->get()->toArray();
            //   prx( $statistics);
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
                    $join->whereRaw('EXISTS (SELECT 1 FROM item_store ist WHERE ist.item_id = items.id AND ist.store_id = stores.id)');
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



        $leadStatistics['new'] = DB::table('service_requests')
            ->join('items', 'service_requests.item_id', '=', 'items.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->join('users', 'service_requests.user_id', '=', 'users.id')
            ->leftJoin('accepted_service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->whereNull('accepted_service_requests.tieup')
            ->whereIn('items.id', Helpers::store_items_sub(Helpers::get_store_id()))
            ->where('service_requests.created_at', '>', now()->subMinutes(Helpers::get_lead_exp_minutes()))
            ->select('accepted_service_requests.tieup', 'service_requests.*', 'items.name as item_name', 'items.image as image', 'categories.name as category_name', 'users.f_name as f_name', 'users.id as uid')
            ->count();

        $leadStatistics['confirmed'] = DB::table('accepted_service_requests')
            ->join('service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->join('stores', 'stores.id', 'accepted_service_requests.vendor_id')
            ->join('items', 'items.id', 'service_requests.item_id')
            ->where('stores.id', Helpers::get_store_id())
            ->where('accepted_service_requests.current_status', 'Confirmed')
            ->select('service_requests.id as service_id', 'items.name as item_name', 'items.image as item_image',  'accepted_service_requests.*')
            ->get()
            ->count();

        $leadStatistics['cancelled'] = DB::table('cancelled_service_requests')
            ->join('service_requests', 'cancelled_service_requests.service_request_id', 'service_requests.id')
            ->join('stores', 'stores.id', 'cancelled_service_requests.vendor_id')
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
            ->where('accepted_service_requests.current_status', 'Completed')
            ->select('service_requests.id as service_id', 'items.name as item_name', 'items.image as item_image',  'accepted_service_requests.*')
            ->get()
            ->count();

        $data['top_sell'] = $top_sell;

        return view('vendor-views.account.dashboard', compact('data', 'earning', 'commission', 'params', 'PLStatistics', 'leadStatistics'));
    }
    public function my_bills(Request $request)
    {
        $storephone = Helpers::get_store_data()->phone;

        $bills1 = ManualInvoice::where('user_type', 'store_user')
            ->whereHas('storeCustomer', function ($query) use ($storephone) {
                $query->where('phone', $storephone);
            })
            ->get();

        $bills2 = ManualInvoice::where([
            'bill_to_type' => 'vendor',
            'bill_to' => Helpers::get_store_id()
        ])->get();

        $merged = $bills1->merge($bills2)->sortByDesc('created_at')->values();

        $page = request()->get('page', 1);
        $perPage = config('default_pagination', 10);

        $bills = new LengthAwarePaginator(
            $merged->forPage($page, $perPage),
            $merged->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );


        return view('vendor-views.billing.my_bills', compact('bills'));
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
        $query =  Account::find($id)
            ->delete();
        Toastr::success('Deleted Successfully');
        return back();
    }

    public function add(Request $request, $tab = null)
    {

        $preset = request('date_range') ?? 'today';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        $store_id = Helpers::get_store_id();
        $account = Account::with('storeAsset.inventoryItem')->where('store_id', $store_id)->get();
        $departments = Department::where('status', '1')->get();
        $categories = AccountOption::where('type', 'category')->get();
        $ledger_account_types = AccountOption::where('type', 'ledger_account_type')->get();
        $puposes = AccountOption::where('type', 'purpose')->get();
        $customers = StoreCustomer::where('store_id', Helpers::get_store_id())->get();
        $assets = StoreAsset::with('inventoryItem')->where('store_id', $store_id)->get();
        $data['subledger_account_types'] = AccountOption::where('type', 'subledger_account_type')->where('store_id', Helpers::get_store_id())->get();

        $ledger_entries = StoreLedgerEntry::with(['account', 'voucher'])
            ->where('store_id', $store_id)
            ->whereHas('voucher', function ($q) {
                $q->where(function ($q2) {
                    $q2->where('debit_entity_type', 'store')
                        ->orWhere('credit_entity_type', 'store');
                });
            })
            ->where(function ($q) {
                // If store is credit entity → credit > 0
                $q->whereHas('voucher', function ($sub) {
                    $sub->where('credit_entity_type', 'store');
                })->where('credit', '>', 0)
                    // Else if store is debit entity → debit > 0
                    ->orWhere(function ($sub) {
                        $sub->whereHas('voucher', function ($inner) {
                            $inner->where('debit_entity_type', 'store');
                        })->where('debit', '>', 0);
                    });
            });

        if ($request->has('date_range')) {
            $ledger_entries->whereBetween(DB::raw('DATE(entry_date)'), [$formatted_from, $formatted_to]);
        }

        $ledger_entries = $ledger_entries
            ->orderBy('created_at', 'desc')
            ->get();


        // // Get all unique voucher IDs from ledger entries
        // $voucherIds = $ledger_entries->pluck('voucher_id')->unique();

        // // Fetch whether each voucher has debit and/or credit entries
        // $voucherWise = StoreLedgerEntry::whereIn('voucher_id', $voucherIds)
        //     ->select(
        //         'voucher_id',
        //         DB::raw('MAX(CASE WHEN debit > 0 THEN 1 ELSE 0 END) as has_debit'),
        //         DB::raw('MAX(CASE WHEN credit > 0 THEN 1 ELSE 0 END) as has_credit')
        //     )
        //     ->groupBy('voucher_id')
        //     ->get()
        //     ->keyBy('voucher_id');

        // // Attach has_debit and has_credit to each ledger entry
        // foreach ($ledger_entries as $ledger) {
        //     $info = $voucherWise[$ledger->voucher_id] ?? null;
        //     $ledger->has_debit = $info->has_debit ?? false;
        //     $ledger->has_credit = $info->has_credit ?? false;
        // }

        $voucherNo =  Helpers::_generateVoucherNumber($store_id);

        return view('vendor-views.account.add', compact('preset', 'tab', 'assets', 'ledger_entries', 'departments', 'categories', 'voucherNo', 'customers', 'ledger_account_types', 'puposes', 'account', 'data'));
    }
    public function journal_entry_export(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $journalEntries = StoreVoucher::with('ledgerEntries')->where('store_id', $storeId)->where('status', 'approved')->get();
        // _auditLogs('Imported Journal entries. Vouchers : ' . implode(',',$voucherIds));

    }

    public function journal_entry_import(Request $request)
    {
        $file = $request->file('file');
        $import = new JournalEntryImport(Helpers::get_store_id());
        Excel::import($import, $file);
        if (!empty($import->failedRows)) {
            dd($import->failedRows);
        } else {
        }

        Toastr::success('Excel file imported successfully.');
        return back();
    }
    public function journal_entry(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $preset = request('date_range') ?? 'last_30_days';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];
        $search = $request->search ?? '';

        $journalEntries = StoreVoucher::with('ledgerEntries')
            ->when($search, function ($query) use ($search) {
                $query->where('voucher_number', 'like', '%' . $search . '%')
                    ->orWhere('narration', 'like', '%' . $search . '%');
            })
            ->where('store_id', $storeId)->where('status', 'approved')->whereBetween('completed_at', [$formatted_from, $formatted_to])->get();
        // prx($journalEntries);
        return view('vendor-views.account.journal-entry.index', compact('journalEntries', 'preset'));
    }
    public function fetchEmployees(Request $request)
    {
        $role = $request->role;
        $dep = $request->dep;
        // prx($request->all());

        $employees = VendorEmployee::where("employee_role_id", $role)->where('department_id', $dep)->where('store_id', Helpers::get_store_id())
            ->select('f_name', 'l_name', 'id')->get();
        return response()->json(['emp' => $employees]);
    }
    public function save_info(Request $request)
    {
        $file = $request->file('file');

        $id = $request->post('account_id');

        $validator = Validator::make($request->all(), [
            'date' => 'required',
            'type' => 'required',
            'customer_id' => 'required',
            'status' => 'required',
            'description' => 'nullable|max:1000',
            'note' => 'nullable|max:1000',
            // 'bill_number' => 'required',
            'ledger_account_type' => 'required',
            // 'gst_amount' => 'required',
            'purpose' => 'required',
            'payment_mode' => 'required',
            'category' => 'required',
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if ($id == '') { // for new lead  
            $staff = new Account;
        } else {
            $staff = Account::find($id);
        }

        // save new purpose 
        $purposeExists = AccountOption::where('type', 'purpose')->where('name', $request->purpose)->exists();
        if (!$purposeExists) {
            $account_option = new AccountOption();
            $account_option->store_id = Helpers::get_store_id();
            $account_option->name = $request->purpose;
            $account_option->type = 'purpose';
            $account_option->save();
        }
        // save new subledger account type 
        $subledgerExists = AccountOption::where('type', 'subledger_account_type')->where('name', $request->subledger_account_type)->exists();
        if (!$subledgerExists) {
            $account_option = new AccountOption();
            $account_option->store_id = Helpers::get_store_id();
            $account_option->name = $request->subledger_account_type;
            $account_option->type = 'subledger_account_type';
            $account_option->save();
        }

        $v_id = \App\CentralLogics\Helpers::get_store_id();

        if (auth('vendor')->check()) {
            $user_type = 'vendor';
        } else if (auth('vendor_employee')->check()) {
            $user_type = 'staff';
        }
        $user_type_id = \App\CentralLogics\Helpers::get_loggedin_user()->id;

        $fileName = '';
        $staff->store_id = $v_id;
        $staff->user_type = $user_type;
        $staff->user_type_id = $user_type_id;
        $staff->date = $request->post('date');
        $staff->type = $request->post('type');
        $staff->description = $request->post('description');
        $staff->category = $request->post('category');
        $staff->customer_id = $request->post('customer_id');
        $staff->amount = $request->post('amount');
        $staff->payment_mode = $request->post('payment_mode');
        $staff->status = $request->post('status');
        $staff->additional_note = $request->post('note');
        $staff->bill_numer = $request->post('bill_number');
        $staff->purpose = $request->post('purpose');
        $staff->ledger_account_type = $request->post('ledger_account_type');
        $staff->subledger_account_type = $request->post('subledger_account_type');
        $staff->gst_amount = $request->post('gst_amount') ?? 0;
        $staff->asset_id = $request->post('asset_id');

        // daybook entry
        if ($request->status == 'completed') {

            if ($request->type == 'income') {
                $type = 'credit';
            } else {
                $type = 'debit';
            }

            if ($request->has('asset_id') && $request->post('asset_id') != '') {
                $asset = StoreAsset::find($request->post('asset_id'));
                $particulars = $type == 'debit' ? 'Asset Purchase - ' . $asset->inventoryItem->item_name : 'Asset Sale - ' . $asset->inventoryItem->item_name;
            } elseif ($request->post('category')) {
                $particulars = $request->post('category');
            } else {
                $particulars = $request->post('purpose')  ?? '';
            }
            _saveDayBookEntry($request->post('amount'), $type, $v_id, $particulars, null, null);
        }

        $file = $request->file('file');
        if ($file != '') {
            $imageName = \Carbon\Carbon::now()->toDateString() . "-" . uniqid() . "." . $file->getClientOriginalName();
            if (!Storage::disk('public')->exists('documents')) {
                Storage::disk('public')->makeDirectory('documents');
            }
            Storage::disk('public')->putFileAs('documents', $file, $imageName);
            $staff->document =  $imageName;
        }


        if ($id == '') { // for new lead
            $staff->save();
            Toastr::success('Information saved successfully');
        } else {
            $staff->update();
            Toastr::success('Information updated successfully');
        }
        return redirect()->route('vendor.account.add');
    }


    public function petty_cashbook(Request $request)
    {
        $preset = request('date_range') ?? 'last_30_days';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        $cashbook_entries = CashBook::where('store_id', Helpers::get_store_id())->whereBetween('entry_date', [$formatted_from, $formatted_to])->get();
        return view('vendor-views.account.petty-cashbook.index', compact('cashbook_entries', 'preset'));
    }
    public function mark_as_paid(Request $request)
    {
        $account = Account::find($request->id);
        if ($account) {
            $account->status = 'completed';
            $account->save();
            Toastr::success('Account marked as paid successfully');
        } else {
            Toastr::error('Account not found');
        }
        return redirect()->back();
    }
    public function setting(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $store = StoreConfig::where('store_id', $store_id)->first();
        return view('vendor-views.account.settings', compact('store'));
    }
    public function report(Request $request)
    {
        $preset = request('date_range') ?? 'this_month';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        $storeId = Helpers::get_store_id();
        $accountReport = StoreVoucher::with('ledgerEntries')->whereBetween('voucher_date', [$formatted_from, $formatted_to])->where('store_id', $storeId)->orderBy('id', 'desc')->get();

        return view('vendor-views.account.report', compact('accountReport', 'preset'));
    }
    public function lead_approval(Request $request)
    {
        $id = $request->post('lead_id');
        $lead = Lead::find($id);
        $lead->approval = $request->post('approval');
        $lead->save();
        return response()->json(['msg' => ucfirst($request->post('approval')) . 'ed', 'status' => true]);
    }

    public function day_book(Request $request)
    {
        $preset = request('date_range') ?? 'today';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        // $todaySalary = _todayPayableSalary();
        $date = $request->query('date', date('Y-m-d'));
        $data = DayBook::where('store_id', Helpers::get_store_id())->whereBetween('entry_date', [$formatted_from, $formatted_to])->get();
        return view('vendor-views.account.day_book', compact('preset', 'data', 'date'));
    }
    public function day_book_excel_import(Request $request)
    {
        $file = $request->file('file');
        Excel::import(new DayBookImport(), $file);

        _auditLogs("Imported Daybook Entries");

        Toastr::success('Excel file imported successfully.');
        return redirect()->back();
    }
    public function day_book_excel_export(Request $request)
    {
        $dayBook = DayBook::where('store_id', Helpers::get_store_id())->whereDate('created_at', $request->query('date'))->get();
        $data = [];
        foreach ($dayBook as $key => $entry) {
            $data[$key] = [
                $entry->created_at->format('Y-m-d'),
                $entry->particular,
                $entry->type == 'credit' ? $entry->amount : '',
                $entry->type == 'debit' ? $entry->amount : '',
            ];
        }
        _auditLogs("Exported Daybook Entries");
        return Excel::download(new DayBookExport($data, ['Date', 'Particulars', 'Credit', 'Debit']), 'day_book.xlsx');
    }
}
