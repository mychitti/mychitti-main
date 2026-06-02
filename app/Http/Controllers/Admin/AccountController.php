<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Lead;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Storage;
use App\CentralLogics\Helpers;
use App\Exports\DayBookExport;
use App\Imports\DayBookImport;
use App\Imports\JournalEntryImport;
use App\Models\Account;
use App\Models\AccountOption;
use App\Models\AssetDepreciation;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\CashBook;
use App\Models\DayBook;
use App\Models\Department;
use App\Models\Item;
use App\Models\ManualInvoice;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AccountController extends Controller
{

    public function index(Request $request)
    {
        $storeId = 0;
        $v_id = 0;
        $account = Account::where('store_id', $v_id)->get();
        $ledger_entries = StoreLedgerEntry::with('account')->where('store_id', $storeId)->get();
        // prx( $ledger_entries);
        return view('admin-views.account.index', compact('account', 'ledger_entries'));
    }
    public function edit(Request $request, $id)
    {
        $account = Account::find($id);
        return view('admin-views.account.manage', compact('account'));
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
        $account_option->store_id = 0;
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
        $account_option->store_id = 0;
        $account_option->name = $request->name;
        $account_option->type = 'ledger_account_type';
        $account_option->save();

        Toastr::success('Added Successfully');
        return redirect()->back();
    }
    public function dashboard(Request $request)
    {
        $storeId = 0;
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

        return view('admin-views.account.dashboard', compact('ledger_entries', 'preset', 'data'));
    }

    public function revenue(Request $request)
    {
        $preset = $request->query('date_range', 'this_year');
        $custom = $request->query('custom_date_range');
        $from = null;
        $to = null;

        if ($preset !== 'all_time') {
            try {
                $range = Helpers::calculatePresetDates($preset, $custom);
                $from = $range['start']->toDateString();
                $to = $range['end']->toDateString();
            } catch (\Exception $e) {
                $preset = 'this_year';
                try {
                    $range = Helpers::calculatePresetDates($preset);
                    $from = $range['start']->toDateString();
                    $to = $range['end']->toDateString();
                } catch (\Exception $ex) {
                    $preset = 'all_time';
                }
            }
        }

        // Date range helper for query
        $applyDateFilter = function ($query) use ($from, $to, $preset) {
            if ($preset === 'all_time') {
                return $query;
            }
            return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
        };

        // 1. Admin Manual Invoices (Paid)
        $invoicesQuery = ManualInvoice::where('generated_by', 'admin')
            ->where('payment_status', 'Paid')
            ->with(['invoiceItems', 'websiteVendor']);
        $invoicesQuery = $applyDateFilter($invoicesQuery);
        $invoices = $invoicesQuery->latest()->get();

        $subscription_income = 0;
        $module_income = 0;
        $manual_lead_income = 0;

        $manual_lead_invoices = [];
        $general_invoices = [];

        foreach ($invoices as $invoice) {
            $invoice->invoice_type = 'Subscription Plan';
            $has_lead = false;
            $has_module = false;
            foreach ($invoice->invoiceItems as $item) {
                $name = strtolower($item->name);
                if (str_contains($name, 'lead subscription')) {
                    $manual_lead_income += $item->price;
                    $has_lead = true;
                } elseif (str_contains($name, 'module')) {
                    $module_income += $item->price;
                    $has_module = true;
                } else {
                    $subscription_income += $item->price;
                }
            }
            if ($has_lead) {
                $invoice->invoice_type = 'Lead Subscription';
                $manual_lead_invoices[] = $invoice;
            } elseif ($has_module) {
                $invoice->invoice_type = 'Module Purchase';
                $general_invoices[] = $invoice;
            } else {
                $general_invoices[] = $invoice;
            }
        }
        $invoices = collect($general_invoices);

        // 1.5. Vendor purchased Lead Subscriptions
        $leadSubscriptionsQuery = \App\Models\LeadSubscription::with(['store', 'plan']);
        $leadSubscriptionsQuery = $applyDateFilter($leadSubscriptionsQuery);
        $lead_subscriptions = $leadSubscriptionsQuery->latest()->get();

        $vendor_lead_income = $lead_subscriptions->sum(function ($sub) {
            return $sub->plan ? $sub->plan->price : 0;
        });

        $lead_sub_income = $vendor_lead_income + $manual_lead_income;

        // 2. Custom Domain Purchase Income
        $domainPurchaseQuery = \App\Models\DomainPurchase::query();
        $domainPurchaseQuery = $applyDateFilter($domainPurchaseQuery);
        $domain_income = $domainPurchaseQuery->sum('total_amount');
        $domains = $domainPurchaseQuery->with('store')->latest()->get();

        // Overall total
        $total_income = $subscription_income + $module_income + $lead_sub_income + $domain_income;

        // Dynamic trend intervals based on date range filter
        $intervals = [];
        if ($preset === 'all_time') {
            for ($i = 5; $i >= 0; $i--) {
                $intervals[] = [
                    'start' => now()->subMonths($i)->startOfMonth()->format('Y-m-d H:i:s'),
                    'end' => now()->subMonths($i)->endOfMonth()->format('Y-m-d H:i:s'),
                    'label' => now()->subMonths($i)->format('M Y'),
                ];
            }
        } else {
            $start = Carbon::parse($from)->startOfDay();
            $end = Carbon::parse($to)->endOfDay();
            $diffInDays = $start->diffInDays($end);

            if ($diffInDays <= 31) {
                // Daily breakdown
                $temp = $start->copy();
                while ($temp->lte($end)) {
                    $intervals[] = [
                        'start' => $temp->copy()->startOfDay()->format('Y-m-d H:i:s'),
                        'end' => $temp->copy()->endOfDay()->format('Y-m-d H:i:s'),
                        'label' => $temp->format('d M'),
                    ];
                    $temp->addDay();
                }
            } else {
                // Monthly breakdown
                $temp = $start->copy()->startOfMonth();
                while ($temp->lte($end)) {
                    $m_start = $temp->copy()->startOfMonth();
                    if ($m_start->lt($start)) {
                        $m_start = $start->copy();
                    }
                    $m_end = $temp->copy()->endOfMonth();
                    if ($m_end->gt($end)) {
                        $m_end = $end->copy();
                    }
                    
                    $intervals[] = [
                        'start' => $m_start->format('Y-m-d H:i:s'),
                        'end' => $m_end->format('Y-m-d H:i:s'),
                        'label' => $temp->format('M Y'),
                    ];
                    $temp->addMonth();
                }
            }
        }

        $chart_data = [];
        foreach ($intervals as $interval) {
            $m_invoices = ManualInvoice::where('generated_by', 'admin')
                ->where('payment_status', 'Paid')
                ->whereBetween('created_at', [$interval['start'], $interval['end']])
                ->with('invoiceItems')
                ->get();
            
            $m_sub = 0;
            $m_mod = 0;
            $m_lead = 0;
            foreach ($m_invoices as $inv) {
                foreach ($inv->invoiceItems as $item) {
                    $name = strtolower($item->name);
                    if (str_contains($name, 'lead subscription')) {
                        $m_lead += $item->price;
                    } elseif (str_contains($name, 'module')) {
                        $m_mod += $item->price;
                    } else {
                        $m_sub += $item->price;
                    }
                }
            }

            // Add vendor lead subscriptions for this interval
            $vendor_m_lead = \App\Models\LeadSubscription::whereBetween('created_at', [$interval['start'], $interval['end']])
                ->with('plan')
                ->get()
                ->sum(function ($sub) {
                    return $sub->plan ? $sub->plan->price : 0;
                });
            $m_lead += $vendor_m_lead;

            $m_domain = \App\Models\DomainPurchase::whereBetween('created_at', [$interval['start'], $interval['end']])
                ->sum('total_amount');

            $chart_data[] = [
                'month' => $interval['label'],
                'subscription' => round($m_sub, 2),
                'module' => round($m_mod, 2),
                'lead' => round($m_lead, 2),
                'domain' => round($m_domain, 2),
            ];
        }

        return view('admin-views.account.revenue', compact(
            'preset', 'custom', 'from', 'to',
            'subscription_income', 'module_income', 'lead_sub_income',
            'domain_income', 'total_income', 'invoices', 'lead_subscriptions', 'domains', 'chart_data'
        ));
    }
    public function send_otp(Request $request)
    {
        $phone = BusinessSetting::where('key', 'phone')->first()?->value;

        $check = _check_otp_send_allowed($phone);
        if (!$check['allowed']) {
            return response()->json(['status' => false, 'message' => $check['message']], 429);
        }

        $otp = rand(1000, 9999);
        _send_confirmation_sms('mobile_verification', $phone, $otp);
        _store_otp($phone, $otp);

        return response()->json(['status' => true, 'message' => "OTP sent to store's registered phone number", 'action' => 'otp_sent']);
    }
    public function reset_accounts_module(Request $request)
    {
        // verify otp
        $phone = BusinessSetting::where('key', 'phone')->first()?->value;
        $otp = is_array($request->otp) ? implode('', $request->otp) :  $request->otp ;
        $verify = _verify_otp($phone, $otp);

        $storeId = 0;

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
  
    public function my_bills(Request $request)
    {
        $storephone = BusinessSetting::where('key', 'phone')->first()?->value;

        $bills1 = ManualInvoice::where('user_type', 'store_user')
            ->whereHas('storeCustomer', function ($query) use ($storephone) {
                $query->where('phone', $storephone);
            })
            ->get();

        $bills2 = ManualInvoice::where([
            'bill_to_type' => 'admin',
            'bill_to' => 0
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


        return view('admin-views.billing.my_bills', compact('bills'));
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

        $store_id = 0;
        $account = Account::with('storeAsset.inventoryItem')->where('store_id', $store_id)->get();
        $departments = Department::where('status', '1')->get();
        $categories = AccountOption::where('type', 'category')->get();
        $ledger_account_types = AccountOption::where('type', 'ledger_account_type')->get();
        $puposes = AccountOption::where('type', 'purpose')->get();
        $customers = StoreCustomer::where('store_id', 0)->get();
        $assets = StoreAsset::with('inventoryItem')->where('store_id', $store_id)->get();
        $data['subledger_account_types'] = AccountOption::where('type', 'subledger_account_type')->where('store_id', 0)->get();

        $ledger_entries = StoreLedgerEntry::with(['account', 'voucher'])
            ->where('store_id', $store_id)
            ->whereHas('voucher', function ($q) {
                $q->where(function ($q2) {
                    $q2->where('debit_entity_type', 'admin')
                        ->orWhere('credit_entity_type', 'admin');
                });
            });

        if ($request->has('date_range')) {
            $ledger_entries->whereBetween(DB::raw('DATE(entry_date)'), [$formatted_from, $formatted_to]);
        }

        $ledger_entries = $ledger_entries
            ->orderBy('created_at', 'desc')
            ->get();

        $voucherNo =  Helpers::_generateVoucherNumber($store_id);
        return view('admin-views.account.add', compact('preset', 'tab', 'assets', 'ledger_entries', 'departments', 'categories', 'voucherNo', 'customers', 'ledger_account_types', 'puposes', 'account', 'data'));
    }
    public function journal_entry_export(Request $request)
    {
        $storeId = 0;
        $journalEntries = StoreVoucher::with('ledgerEntries')->where('store_id', $storeId)->where('status', 'approved')->get();
        _auditLogs('Imported Journal entries. Vouchers : ' . implode(',',$voucherIds));

    }

    public function journal_entry_import(Request $request)
    {
        $file = $request->file('file');
        $import = new JournalEntryImport(0);
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
        $storeId = 0;
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
        return view('admin-views.account.journal-entry.index', compact('journalEntries', 'preset'));
    }
    public function fetchEmployees(Request $request)
    {
        $role = $request->role;
        $dep = $request->dep;
        // prx($request->all());

        $employees = Admin::where("role_id", $role)->where('department_id', $dep)->where('store_id', 0)
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
            $account_option->store_id = 0;
            $account_option->name = $request->purpose;
            $account_option->type = 'purpose';
            $account_option->save();
        }
        // save new subledger account type 
        $subledgerExists = AccountOption::where('type', 'subledger_account_type')->where('name', $request->subledger_account_type)->exists();
        if (!$subledgerExists) {
            $account_option = new AccountOption();
            $account_option->store_id = 0;
            $account_option->name = $request->subledger_account_type;
            $account_option->type = 'subledger_account_type';
            $account_option->save();
        }

        $v_id = 0;

        $user_type = auth('admin')->user()->role_id == 1 ? 'admin' : 'staff';
        $user_type_id = auth('admin')->id();
 
        $fileName = '';
        $staff->store_id = $v_id;
        $staff->user_type = 'admin';
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
        return redirect()->route('admin.account.add');
    }


    public function petty_cashbook(Request $request)
    {
        $preset = request('date_range') ?? 'last_30_days';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        $cashbook_entries = CashBook::where('store_id', 0)->whereBetween('entry_date', [$formatted_from, $formatted_to])->get();
        return view('admin-views.account.petty-cashbook.index', compact('cashbook_entries', 'preset'));
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
        $store_id = 0;
        $store = StoreConfig::where('store_id', $store_id)->first();
        return view('admin-views.account.settings', compact('store'));
    }
    public function report(Request $request)
    {
        $preset = request('date_range') ?? 'this_month';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        $storeId = 0;
        $accountReport = StoreVoucher::with('ledgerEntries')->whereBetween('voucher_date', [$formatted_from, $formatted_to])->where('store_id', $storeId)->orderBy('id', 'desc')->get();

        return view('admin-views.account.report', compact('accountReport', 'preset'));
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
        $data = DayBook::where('store_id', 0)->whereBetween('entry_date', [$formatted_from, $formatted_to])->get();
        return view('admin-views.account.day_book', compact('preset', 'data', 'date'));
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
        $dayBook = DayBook::where('store_id', 0)->whereDate('created_at', $request->query('date'))->get();
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
