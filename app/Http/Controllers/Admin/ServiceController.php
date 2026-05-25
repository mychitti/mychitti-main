<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; 
use Illuminate\Http\Request;
use App\Models\EmployeeRole;
use App\Models\GatePass;
use App\Models\ServiceQuoteItem;
use App\Models\GatePassItem;
use App\Models\InServiceQuotation;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\Category;
use App\Models\CommonServiceIssue;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\ServiceInvoice;
use App\Models\ServiceRequest;
use App\Models\Store;
use App\Models\StoreCustomer;
use App\Models\StoreLedgerEntry;
use App\Models\StoreTask;
use App\Models\StoreVoucher;
use App\Models\TempStoreStatus;
use App\Models\User;
use App\Models\ZoneWalletConfig;

use function PHPSTORM_META\type;

class ServiceController extends Controller
{

    public function lead_list(Request $request)
    {
        // mark all as checked
        ServiceRequest::query()->update(['checked' => 1]);

        $type       = $request->type ?? 'all';
        $search     = $request->search ?? '';
        $zone_id    = $request->zone_id ?? '';
        $store_id   = $request->store_id ?? '';
        $preset     = $request->date_range ?? 'this_year';
        $custom     = $request->custom_date_range ?? null;
        $range      = Helpers::calculatePresetDates($preset, $custom);

        $leads = DB::table('service_requests')
            ->leftJoin('items', 'items.id', '=', 'service_requests.item_id')
            ->select(
                'service_requests.id',
                'service_requests.id as service_id',
                'service_requests.created_at as enquiry_date',
                'service_requests.sent_to',
                'items.name',
                'items.image',
                DB::raw('(SELECT COUNT(*) FROM accepted_service_requests asr WHERE asr.service_request_id = service_requests.id) as accepted_count'),
                DB::raw('(SELECT COUNT(*) FROM cancelled_service_requests csr WHERE csr.service_request_id = service_requests.id) as cancelled_count')
            )
            ->when($type && $type !== 'all', function ($q) use ($type) {
                $q->whereExists(function ($sub) use ($type) {
                    $sub->select(DB::raw(1))
                        ->from('accepted_service_requests as asr')
                        ->whereColumn('asr.service_request_id', 'service_requests.id')
                        ->where('asr.current_status', $type);
                });
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('items.name', 'like', "%{$search}%")
                          ->orWhere('service_requests.id', $search);
                });
            })
            ->when($zone_id, function ($q) use ($zone_id) {
                $q->whereExists(function ($sub) use ($zone_id) {
                    $sub->select(DB::raw(1))
                        ->from('accepted_service_requests as asr2')
                        ->join('stores as s2', 's2.id', '=', 'asr2.vendor_id')
                        ->whereColumn('asr2.service_request_id', 'service_requests.id')
                        ->where('s2.zone_id', $zone_id);
                });
            })
            ->when($store_id, function ($q) use ($store_id) {
                $q->whereRaw('FIND_IN_SET(?, service_requests.sent_to)', [$store_id]);
            })
            ->whereBetween(DB::raw('DATE(service_requests.created_at)'), [$range['start'], $range['end']])
            ->orderBy('service_requests.id', 'desc')
            ->paginate(15)
            ->appends($request->query());

        $zones = DB::table('zones')
            ->join('module_zone', 'module_zone.zone_id', '=', 'zones.id')
            ->where('module_zone.module_id', 6)
            ->select('zones.id', 'zones.name')
            ->get();

        return view('admin-views.service.leads-list', compact('leads', 'type', 'search', 'zone_id', 'store_id', 'preset', 'zones'));
    }
     public function mark_paid2(Request $request)
    {
        $type = $request->type;
        if ($type == 'service') {
            $invoice = ServiceInvoice::find($request->id);
        } else {
            $invoice = ManualInvoice::find($request->id);
        }
        if ($request->payment_mode == 'Cash and Online' && ($invoice->total_amount != ($request->cash_amount + $request->online_amount))) {
            Toastr::error('Amount Mismatched');
            return back();
        }
        // voucher update
        $voucher = StoreVoucher::where('invoice_id', $type . '-' . $invoice->id)->first();
        if ($voucher) {
            $voucher->status = 'approved';
            $voucher->completed_at = now();
            $voucher->save();

            // ledger entries update 
            $ledger_entries = StoreLedgerEntry::where('voucher_id', $voucher->id)->get();
            foreach ($ledger_entries as $key => $value) {
                $value->status = 'approved';
                $value->completed_at = now();
                $value->save();
            }
        }
        $invoice->cash_amount = $request->cash_amount;
        $invoice->online_amount = $request->online_amount;
        $invoice->payment_status = 'Paid';
        $invoice->payment_date = date('Y-m-d');
        $invoice->save();
        
        $data = _createBillPdf($invoice, 'admin');
        $invoice->update(['pdf' => $data['pdf']]);

        Toastr::success("Payment status changed successfully");
        return back();
    }
    public function save_manual_invoice(Request $request, $task_id = null)
    {
        // prx($request->all());
        $request->validate([
            'item_name.*' => 'required',
        ]);

        if ($request->payment_stts == 'Unpaid') {
            $request->validate([
                'payment_date' => 'required',
                'reminder_date' => 'required',
            ]);
        }
        if (
            $request->payment_stts == 'Paid' && $request->payment_mode == 'Cash and Online'
            && $request->final_total_amount != $request->cash_amount + $request->online_amount
        ) {
            Toastr::error("Amount Mismatched");
            return  back();
        }

        $rules = [];
        $messages = [];
        $bill_to = null;
        $customer_rule = 'required';

        if ($request->filled('bill_to')) {
            $bill_to = $request->bill_to;
        } elseif ($request->filled('customer_id')) {
            $bill_to = $request->customer_id;
            $customer_rule = 'nullable';
        } elseif (!empty($task_id)) {
            $task = StoreTask::find($task_id);
            if ($task && $task->user_id) {
                $customer_rule = 'nullable';
                $bill_to = $task->user_id;
            }
        }

        if (!$request->has('bill_from')) {
            $rules['bill_to'] = $customer_rule;
            $messages['bill_to.required'] = 'Customer field is required';
            $prefixe = _getInvoicePrefix($request->tax_type);
        } else {
            $rules['bill_from'] = 'required';
            $messages['bill_from.required'] = 'Seller field is required';

            $store = DB::table('stores')->find($request->bill_from);
            $prefixe = _getInvoicePrefix($request->tax_type, $store);
        }

        $request->validate($rules, $messages);

        if (!$request->filled('bill_to') && $bill_to) {
            $request->merge(['bill_to' => $bill_to]);
        }


        // check invoice number 
        if (is_serial_number_used($request->number, $prefixe,  $request->tax_type)) {
            Toastr::error("Serial Number Already Used");
            return  back();
        }

        $totalPrice = 0;
        if ($request->has('item_name')) {
            foreach ($request->item_price as $key => $price) {
                $totalPrice += _taxIncludedPrice($price, $request->item_tax[$key], 'actual') * $request->item_qty[$key];
            }
        }
        if (isset($request->item_price_new)) {
            foreach ($request->item_price_new as $key => $price) {
                $totalPrice += _taxIncludedPrice($price, $request->item_tax_new[$key], 'actual') * $request->item_qty_new[$key];
            }
        }


        if ($request->has('number')) {
            $invoice_id = Helpers::generateInvoiceIdAdmin(6); // M = manual
        } else {
            $invoice_id = Helpers::generateInvoiceIdAdmin(6); // M = manual
        }
        if ($request->has('bill_from') && $request->bill_from) {
            $store_id = $request->bill_from;
            $bill_to = Helpers::get_store_id();
            $bill_to_type =   'vendor';
            $user_type =  'vendor';
        } else {
            $store_id = Helpers::get_store_id();
            $bill_to = $bill_to ?? $request->bill_to;
            $bill_to_type =  'user';
            $userTypeInfo = StoreCustomer::find($bill_to)->user_type;
            $user_type =  $userTypeInfo == 'customer' ? 'store_user' : 'store_vendor';
        }


        $invoice = new ManualInvoice;
        $invoice->task_id =  $task_id;
        $invoice->invoice_id = $invoice_id;
        $invoice->invoice_serial = (int) substr($invoice_id, strrpos($invoice_id, '_') + 1);
        $invoice->financial_year = _currentFinancialYear();
        $invoice->reference_number = $request->reference_number;
        $invoice->vendor_id = $store_id;
        $invoice->bill_to = $bill_to;
        $invoice->bill_to_type = $bill_to_type;
        $invoice->user_type = $user_type;
        $invoice->module_id =  6;
        $invoice->total_amount =  $totalPrice;
        $invoice->tax_type =  $request->tax_type;
        $invoice->payment_method =  $request->payment_mode;
        $invoice->payment_mode = $request->payment_mode;

        if ($request->payment_stts == 'Paid' && $request->payment_mode == 'Cash and Online') {
            $invoice->cash_amount = $request->cash_amount;
            $invoice->online_amount = $request->online_amount;
        }
        $invoice->invoice_date = $request->invoice_date;
        $invoice->payment_status =  $request->payment_stts;
        $invoice->payment_date =  $request->payment_date;
        $invoice->reminder_date =  $request->reminder_date;
        $invoice->reminder_freq =  $request->reminder_freq;
        $invoice->reminder_freq_unit =  $request->reminder_freq_unit;
        $invoice->created_by = auth('admin')->id();
        $invoice->save();

        if ($request->has('item_name')) {
            foreach ($request->item_name as $key => $name) {
                $InvoiceItem = new InvoiceItem();
                $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
                $InvoiceItem->manual_invoice_id = $invoice->id;
                $InvoiceItem->name = $request->item_name[$key];
                $InvoiceItem->qty = $request->item_qty[$key];
                $InvoiceItem->price = $request->item_price[$key];
                $InvoiceItem->unit = $request->item_unit[$key];
                $InvoiceItem->tax = $request->tax_type == 'gst' ?  ($request->item_tax[$key] ?? 0) : 0;
                $InvoiceItem->hsn = $request->item_hsn[$key];
                $InvoiceItem->save();
            }
        }
        $inv_items = 0;
        if ($request->has('invoice_item_new')) { // insert new items to invoice
            foreach ($request->invoice_item_new as $key => $id) {
                $InvoiceItem = new InvoiceItem();
                $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
                $InvoiceItem->manual_invoice_id = $invoice->id;
                $InvoiceItem->name = $request->item_name_new[$key];
                $InvoiceItem->price = $request->item_price_new[$key];
                $InvoiceItem->qty = $request->item_qty_new[$key];
                $InvoiceItem->unit = $request->item_unit_new[$key] ?? null;
                $InvoiceItem->tax = $request->tax_type == 'gst' ?  ($request->item_tax_new[$key] ?? 0) : 0;
                $InvoiceItem->hsn = $request->item_hsn_new[$key];
                $InvoiceItem->inv_id = $request->inventory_item_id_new[$key] ?? null;
                $InvoiceItem->save();
                if ($request->inventory_item_id_new[$key]) {
                    $inv_items++;
                }

                if ($request->inventory_item_id_new[$key]) {
                    _updateInventoryStock($request->inventory_item_id_new[$key], $request->item_qty_new[$key], $request->item_unit_new[$key]);
                }
            }
        }
        // RECHECK AMOUNT 
        // if ($request->payment_mode == 'Cash and Online' && ($invoice->final_total_amount != $request->cash_amount + $request->online_amount)) {
        //     $invoice->payment_status = 'Unpaid';
        //     $invoice->save();
        // }

        // PETTY CASHBOOK ENTRY 
        if ($invoice->payment_status == 'Paid') {
            if ($request->payment_mode == 'Cash' || $request->payment_mode == 'Cash and Online') {
                if ($request->payment_mode == 'Cash and Online') {
                    $amount = $invoice->cash_amount;
                } else {
                    $amount = $totalPrice;
                }
            } else {
                $amount = $request->cash_amount;
            }
            _savePettyCashBookEntry($amount, 'Received', 'Invoice Payment', $request->invoice_date, $invoice->invoice_id);
        }

        // master ledger entry 
        $data = [
            'date' => $request->invoice_date ?? now(),
            'amount' => $totalPrice,
            'status' => $invoice->payment_status == 'Paid' ? 'approved' : 'pending',
            'payment_mode' => 'cash',
            'invoice_id' => 'manual-' . $invoice->id,
            'voucher_type' => 'Sales',
            'invoice_id' => 'manual-' . $invoice->id,
            'file' => $request->hasFile('file') ?? null,
        ];

        $customer = StoreCustomer::where('store_id', Helpers::get_store_id())->where('id', $request->bill_to)->first();
        $debit_account = Helpers::ensureCustomerLedger($customer);
        $credit_account = Helpers::ensureSalesAccount();
        $voucher =  _masterLedgerEntry(
            $data,
            $credit_account,
            $debit_account,
            'customer',
            'store',
            null
        );

        if ($invoice->payment_status == 'Paid') {
            _saveDayBookEntry($totalPrice, 'credit', Helpers::get_store_id(), "Sales Invoice", $invoice->id, $voucher?->id, $invoice->invoice_date, $invoice->reference_number, $request->payment_mode);
        }

        _auditLogs('Created Invoice : ' . $invoice->invoice_id);
        $docData = Helpers::generateInventoryGatepass($invoice, (object)[], 'sale');

        // Inventory order 
        if ($inv_items) {
            Helpers::_placeInventoryOrder($invoice);
        }
        $data = _createBillPdf($invoice, 'vendor');
        $invoice->update(['pdf' => $data['pdf']]);
        try {
            return redirect($data['url']);
        } catch (\Throwable $th) {
            //
        }
        // return redirect()->route('vendor.invoice.manual-invoice-view', ['manual', $invoice->invoice_id]);
    }
    public function common_issue_delete(Request $request)
    {
        CommonServiceIssue::destroy($request->id);
        Toastr::success('Deleted successfully');
        return back();
    }
    public function common_issue_save(Request $request)
    {
        $request->validate([
            'issue' => 'required',
        ]);
        $issue = new CommonServiceIssue;
        $issue->issue = $request->issue;
        $issue->save();

        Toastr::success('Added successfully');
        return back();
    }
    public function config()
    {
        $reported_issue_list  = CommonServiceIssue::all();

        $zones = DB::table('zones')
            ->join('module_zone', 'module_zone.zone_id', '=', 'zones.id')
            ->where('module_zone.module_id', 6)
            ->select('zones.*')
            ->get();

        $categories = Category::where('module_id', 6)->get(); 
        $zoneWalletConfigs = ZoneWalletConfig::with('zone', 'category')->get(); 

        return view('admin-views.service.config', compact('reported_issue_list', 'zones', 'categories', 'zoneWalletConfigs'));
    }

    public function zone_wallet_config_save(Request $request)
    {
        $request->validate([
            'zone_id' => 'required',
            'category_id' => 'nullable|array',
            'min_balance' => 'required|numeric|min:0',
        ]);

        $categoryIds = $request->category_id ?? [null];
        $skipped = 0;

        foreach ($categoryIds as $categoryId) {
            $exists = ZoneWalletConfig::where('zone_id', $request->zone_id)
                ->where('category_id', $categoryId ?: null)
                ->exists();
 
            if ($exists) {
                $skipped++;
                continue;
            }

            ZoneWalletConfig::create([
                'zone_id' => $request->zone_id,
                'category_id' => $categoryId ?: null,
                'min_balance' => $request->min_balance,
            ]);
        }

        if ($skipped > 0) {
            Toastr::warning($skipped . ' config(s) already existed and were skipped');
        }
        Toastr::success('Zone wallet config saved successfully');
        return back();
    }

    public function zone_wallet_config_update(Request $request, $id)
    {
        $request->validate([
            'min_balance' => 'required|numeric|min:0',
        ]);

        $config = ZoneWalletConfig::findOrFail($id);
        $config->update(['min_balance' => $request->min_balance]);

        Toastr::success('Zone wallet config updated successfully');
        return back();
    }

    public function zone_wallet_config_delete($id)
    {
        ZoneWalletConfig::findOrFail($id)->delete();
        Toastr::success('Zone wallet config deleted successfully');
        return back();
    }

    public function approve_status_request(Request $request)
    {
        $r = TempStoreStatus::find($request->id);

        if ($r) {
            $store = Store::find($r->store_id);

            if ($store) {
                $leadStatuses = array_filter(explode(',', $store->lead_statuses));

                if (!in_array($r->status_id, $leadStatuses)) {
                    $leadStatuses[] = $r->status_id;
                }

                $store->lead_statuses = implode(',', $leadStatuses);
                $store->save();

                $r->delete();
                Toastr::success('Status approved and added to store!');
            } else {
                Toastr::error('Store not found!');
            }
            return back();
        } else {
            Toastr::error('Request not found!');
            return back();
        }
    }

    // public function new_status_request()
    // {
    //     $stts_requests =  TempStoreStatus::with('serviceStatus', 'store')
    //         ->paginate(10);
    //     return view('admin-views.service.new_status_request', compact('stts_requests', 'stts_requests'));
    // }
    public function config_update(Request $request)
    {
        DB::table('business_settings')->updateOrInsert(['key' => 'exp_count'], [
            'value' => $request['exp_count']
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'exp_unit'], [
            'value' => $request['exp_unit']
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'leads_distribut_vendor'], [
            'value' => $request['leads_distribut_vendor']
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'leads_dispatch_round_timeout'], [
            'value' => (int)$request['leads_dispatch_round_timeout'] ?: 5
        ]);

        Toastr::success('Configurations updated successfully!');
        return back();
    }
    public function manual_bill()
    {
        $customers = User::where('status', 1)->get();

        return view('vendor-views.billing.invoice_generate', compact('customers'));
    }

    public function lead_detail_old(Request $request, $leadId)
    {

        $reqDetails = DB::table('service_requests')
            ->join('items', 'items.id', 'service_requests.item_id')
            ->where('service_requests.id', $leadId)
            ->select('items.name', 'service_requests.*', 'service_requests.id as s_id')
            ->first();

        $timeline = DB::table('lead_statuses')->where('service_request_id', $leadId)->get();

        $quotation = DB::table('in_service_quotations')->where('service_id', $leadId)->first();
        if ($quotation) {

            $quotationItems =  DB::table('service_quote_items')->where('quote_id', $quotation->id)->get();
        } else {
            $quotationItems = [];
        }
        $gatepass  = GatePass::where('service_id', $leadId)->first();
        if ($gatepass) {
            $gpItems = DB::table('gate_pass_items')->where('gatepass_id', $gatepass->id)->get();
        } else {
            $gpItems = [];
        }
        return view('admin-views.service.lead-details', compact('reqDetails',  'quotationItems', 'gpItems', 'timeline'));
    }
    public function lead_detail(Request $request, $leadId)
    {
        $confirmedLeadVendors = DB::table('service_requests')
            ->join('accepted_service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->join('stores', 'stores.id', 'accepted_service_requests.vendor_id')
            ->where('service_requests.id', $leadId)
            ->whereRaw("FIND_IN_SET(stores.id, service_requests.sent_to) > 0")
            ->whereNotNull('accepted_service_requests.confirmed_at')
            ->select('stores.name', 'stores.id as store_id', 'stores.logo', 'accepted_service_requests.id', 'service_requests.id as service_id', 'accepted_service_requests.confirmed_at')
            ->get();

        $acceptedLeadVendors = DB::table('service_requests')
            ->join('accepted_service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->join('stores', 'stores.id', 'accepted_service_requests.vendor_id')
            ->where('service_requests.id', $leadId)
            ->whereRaw("FIND_IN_SET(stores.id, service_requests.sent_to) > 0")
            ->select('stores.name', 'stores.id as store_id', 'stores.logo', 'accepted_service_requests.id', 'service_requests.id as service_id', 'accepted_service_requests.created_at')
            ->get();

        $completedLeadVendors = DB::table('service_requests')
            ->join('accepted_service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->join('stores', 'stores.id', 'accepted_service_requests.vendor_id')
            ->where('service_requests.id', $leadId)
            ->whereRaw("FIND_IN_SET(stores.id, service_requests.sent_to) > 0")
            ->whereNotNull('accepted_service_requests.completed_at')
            ->select('stores.name', 'stores.id as store_id', 'stores.logo', 'accepted_service_requests.id', 'service_requests.id as service_id', 'accepted_service_requests.completed_at')
            ->get();

        $recievedLeadVendors = DB::table('service_requests')
            ->join('stores', function ($join) {
                $join->on(DB::raw("FIND_IN_SET(stores.id, service_requests.sent_to)"), '>', DB::raw('0'));
            })
            ->where('service_requests.id', $leadId)
            ->select('stores.name', 'stores.id as store_id', 'stores.logo', 'service_requests.id as service_id', 'service_requests.created_at')
            ->get();
        // prx( $confirmedLeadVendors)

        $reqDetails = DB::table('service_requests')
            ->join('items', 'items.id', 'service_requests.item_id')
            ->where('service_requests.id', $leadId)
            ->select('items.name', 'service_requests.*', 'service_requests.id as s_id')
            ->first();

        $timeline = DB::table('lead_statuses')->where('service_request_id', $leadId)->get();

        $quotation = DB::table('in_service_quotations')->where('service_id', $leadId)->first();
        if ($quotation) {

            $quotationItems =  DB::table('service_quote_items')->where('quote_id', $quotation->id)->get();
        } else {
            $quotationItems = [];
        }
        $gatepass  = GatePass::where('service_id', $leadId)->first();
        if ($gatepass) {
            $gpItems = DB::table('gate_pass_items')->where('gatepass_id', $gatepass->id)->get();
        } else {
            $gpItems = [];
        }
        return view('admin-views.service.lead-details', compact('reqDetails', 'acceptedLeadVendors', 'recievedLeadVendors', 'completedLeadVendors', 'confirmedLeadVendors', 'quotationItems', 'gpItems', 'timeline'));
    }
    function lead_timeline(Request $request, $leadId)
    {
        $reqDetails = DB::table('service_requests')
            ->join('items', 'items.id', 'service_requests.item_id')
            ->join('users', 'users.id', 'service_requests.user_id')
            ->where('service_requests.id', $leadId)
            ->select('items.name', 'service_requests.*', 'service_requests.id as s_id', 'users.phone')
            ->first();
        $acceptanceDetails =  DB::table('accepted_service_requests')->where('service_request_id', $leadId)->where('vendor_id', Helpers::get_store_id())->first();
        if (!$acceptanceDetails) {
            $acceptanceDetails = DB::table('cancelled_service_requests')->where('service_request_id', $leadId)->where('vendor_id', Helpers::get_store_id())->first();
        }

        $timeline = DB::table('lead_statuses')->where('service_request_id', $leadId)->get();

        $quotation = DB::table('in_service_quotations')->where('service_id', $leadId)->first();
        if ($quotation) {
            $quotationItems =  DB::table('service_quote_items')->where('quote_id', $quotation->id)->get();
        } else {
            $quotationItems = [];
        }
        $gatepass  = GatePass::where('service_id', $leadId)->first();
        if ($gatepass) {
            $gpItems = DB::table('gate_pass_items')->where('gatepass_id', $gatepass->id)->get();
        } else {
            $gpItems =  [];
        }

        return view('admin-views.service.timeline', compact('reqDetails', 'acceptanceDetails', 'gatepass', 'quotation', 'quotationItems', 'gpItems', 'timeline'));
    }
}
