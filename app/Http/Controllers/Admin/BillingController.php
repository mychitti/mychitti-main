<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\CustomerAddress;
use App\Models\DayBook;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\ServiceInvoice;
use App\Models\StorageUnit;
use App\Models\Store;
use App\Models\User;
use App\Models\Zone;
use App\Models\AccountDetail;
use App\Models\Category as ModelsCategory;
use App\Models\InventoryItem;
use App\Models\StoreCustomer;
use App\Models\StoreLedgerEntry;
use App\Models\StoreSignature;
use App\Models\StoreTnc;
use App\Models\StoreVoucher;
use App\Models\TaxRate;
use App\Models\Admin;
use App\Exports\InvoiceExport;
use App\Library\Payer;
use App\Library\Payment as PaymentInfo;
use App\Library\Receiver;
use App\Models\StoreTask;
use App\Traits\Payment;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use Illuminate\Pagination\LengthAwarePaginator;

use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class BillingController extends Controller
{
    public function billing(Request $request)
    {
        if (isset($request->from)) {
            $fromdate = $request->from;
            $todate = $request->to;
        } else {
            $fromdate = date('Y-m') . '-01';
            $todate = date('Y-m') . '-30';
        }
        $customers = User::where('status', 1)->get();
        $stores = Store::where('status', 1)->where('module_id', Config::get('module.current_module_id'))->get();
        $invoices = ManualInvoice::whereBetween('created_at', [$fromdate . ' 00:00:00', $todate . ' 23:59:59'])->where('module_id', Config::get('module.current_module_id'))
            ->where(function ($query) {
                $query->where("generated_by", 'admin')
                    ->orWhere("vendor_id", 0); 
            })->get();
        return view('admin-views.billing.invoice_generate', compact('customers', 'stores', 'invoices', 'fromdate', 'todate'));
    }
    public function invoice_bulk_delete(Request $request)
    {
        $invoices = ManualInvoice::whereBetween('invoice_serial', [$request->from, $request->to])->where('generated_by', 'admin')->get();

        foreach ($invoices as $invoice) {
            DayBook::where('invoice_id', $invoice->id)->first()?->delete(); // delete all matching DayBook entries (if any)
            Helpers::delete_file('invoice/',  $invoice->pdf);

            $invoice->invoiceItems()->delete(); // delete all associated invoice items

            $invoice->delete(); // delete the invoice
        }

        $maxSerial = DB::table('manual_invoices')->where('generated_by', 'admin')->max('invoice_serial');
        BusinessSetting::updateOrInsert(['key' => 'admin_bill_serial_number'], [
            'value' => $maxSerial + 1
        ]);
        Toastr::success('Invoices Deleted Successfully');
        return back();
    }
    public function manual_bill()
    {
        $upcoming_bill_number = Helpers::generateInvoiceIdAdmin(6);
        $bill_number = $upcoming_bill_number;
        $bill_num['prefix'] = 'MSM_';
        $bill_num['nongst_prefix'] = 'MSM_';
        $bill_num['number'] = substr($bill_number, strrpos($bill_number, '_') + 1);
        $bill_num['non_gst_sno'] = $bill_num['number'];
        $storage_units = StorageUnit::with('parent')->where('store_id', 0)->get();

        // Keep incrementing until unique
        do {
            $invoice_num = $bill_num['prefix'] . $bill_num['number'];
            $exists = ManualInvoice::where('invoice_id', $invoice_num)->exists();
            if ($exists) {
                $bill_num['number']++;
            } 
        } while ($exists);

        $customers = User::where('status', 1)->get();
        $stores = Store::where('status', 1)->where('module_id', Config::get('module.current_module_id'))->get();
        $tncs = StoreTnc::where('store_id', 0)->where('tnc_type', 'invoice')->get();
        return view('admin-views.billing.generate', compact('storage_units', 'stores', 'customers', 'bill_num', 'tncs'));
    }
    public function invoice_list(Request $request)
    {
        $storeId = 0;
        $today = date('Y-m-d');

        $preset = request('date_range') ?? 'last_30_days';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];
        $from = $range['start']->toDateString();
        $to  = $range['end']->toDateString();

        $status = request()->get('status') ?? 'all';
        $search = request()->get('search');

        $invoices = collect();
        if (in_array($status, ['overdue', 'pending', 'credit', 'Unpaid'])) {
            if ($status === 'overdue') {
                $invoices = fetchInvoices(ServiceInvoice::class, 'service', 'Unpaid', '<', $today, 'overdue', $formatted_from, $formatted_to, $search)
                    ->merge(fetchInvoices(ManualInvoice::class, 'manual', 'Unpaid', '<', $today, 'overdue', $formatted_from, $formatted_to, $search));
            } elseif ($status === 'pending') {
                $invoices = fetchInvoices(ServiceInvoice::class, 'service', 'Unpaid', '>=', $today, 'pending', $formatted_from, $formatted_to, $search)
                    ->merge(fetchInvoices(ManualInvoice::class, 'manual', 'Unpaid', '>=', $today, 'pending', $formatted_from, $formatted_to, $search));
            } elseif ($status === 'credit') {
                $invoices = fetchInvoices(ServiceInvoice::class, 'service', 'Paid', null, null, 'credit', $formatted_from, $formatted_to, $search)
                    ->merge(fetchInvoices(ManualInvoice::class, 'manual', 'Paid', null, null, 'credit', $formatted_from, $formatted_to, $search));
            } elseif ($status === 'Unpaid') {
                $invoices = fetchInvoices(ServiceInvoice::class, 'service', 'Unpaid', null, null, 'pending', $formatted_from, $formatted_to, $search)
                    ->merge(fetchInvoices(ManualInvoice::class, 'manual', 'Unpaid', null, null, 'pending', $formatted_from, $formatted_to, $search));
            }
        } else {
            $overdue = fetchInvoices(ServiceInvoice::class, 'service', 'Unpaid', '<', $today, 'overdue', $formatted_from, $formatted_to, $search)
                ->concat(fetchInvoices(ManualInvoice::class, 'manual', 'Unpaid', '<', $today, 'overdue', $formatted_from, $formatted_to, $search));

            $pending = fetchInvoices(ServiceInvoice::class, 'service', 'Unpaid', '>=', $today, 'pending', $formatted_from, $formatted_to, $search)
                ->concat(fetchInvoices(ManualInvoice::class, 'manual', 'Unpaid', '>=', $today, 'pending', $formatted_from, $formatted_to, $search));

            $credit = fetchInvoices(ServiceInvoice::class, 'service', 'Paid', null, null, 'credit', $formatted_from, $formatted_to, $search)
                ->concat(fetchInvoices(ManualInvoice::class, 'manual', 'Paid', null, null, 'credit', $formatted_from, $formatted_to, $search));


            $invoices = $overdue->concat($pending)->concat($credit)->unique('invoice_id')->values();;
        }
        // prx($invoices);
        // die;

        return view('admin-views.billing.invoices', compact('preset', 'invoices', 'from', 'to', 'status', 'search'));
    }
    public function invoice_settings(Request $request)
    {
        $tncs = StoreTnc::where('store_id', 0)->where('tnc_type', 'invoice')->get();
        $store_id = 0;
        $accounts = AccountDetail::where('user_type', 'vendor')->where('user_id',  $store_id)->where('type', 'invoice')->get();
        $staffs = \App\Models\Admin::all();
        $signatures = StoreSignature::with('adminEmployee')->where('store_id', $store_id)->where('type', 'invoice')->get();
        $store = Store::where('id',  $store_id)->first();
        return view('admin-views.billing.invoice_settings', compact('tncs', 'signatures', 'staffs', 'accounts',  'store'));
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
            'bill_to' => 0,
        ])->whereNotNull('pdf')->get();

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
    public function create_invoice(Request $request)
    {
        $store_id = 0; // admin

        $categories = ModelsCategory::where(['position' => 0])->module(6)->get();
        $storage_units = StorageUnit::with('parent')->where('store_id', $store_id)->get();
        $singatures = StoreSignature::with('employee')->where('store_id', $store_id)->where('type', 'invoice')->orderBy('staff_id', 'asc')->get();
        $accounts = AccountDetail::where('user_type', 'vendor')->where('type', 'invoice')->where('user_id', $store_id)->get();
        $tAndCContent = DB::table('vendor_terms_conditions')->where('type', 'for_customer')->where('vendor_id', $store_id)->first();
        $upcoming_bill_number = Helpers::generateInvoiceIdAdmin(6, false);
        $bill_number = $upcoming_bill_number;
        $bill_num['prefix'] = 'MSM_';
        $bill_num['nongst_prefix'] = 'MSM_';
        $bill_num['number'] = substr($bill_number, strrpos($bill_number, '_') + 1);
        $data['tax_rates_tcs'] = TaxRate::where('type', 'TCS')->get();
        $data['tax_rates_tds'] = TaxRate::where('type', 'TDS')->get();
        $staffs = Admin::where('role_id', '!=', 1)->get();
        $data['store_tncs'] = StoreTnc::where('store_id', $store_id)->where('tnc_type', 'invoice')->get();
        // Keep incrementing until unique 
        do {
            $invoice_num = $bill_num['prefix'] . $bill_num['number'];
            $exists = ManualInvoice::where('invoice_id', $invoice_num)->exists();
            if ($exists) {
                $bill_num['number']++;
            }
        } while ($exists);
        $customers = StoreCustomer::where('store_id', 0)->get();
        return view('admin-views.billing.create_invoice', compact('categories', 'storage_units', 'staffs', 'data', 'singatures', 'accounts', 'tAndCContent', 'customers', 'bill_num'));
    }
    public function invoice_delete(Request $request, $type, $invoice_id)
    {
        if ($type == 'manual') {
            $invoice = ManualInvoice::where('invoice_id', $invoice_id)->where('generated_by', 'admin')->first();
            DayBook::where('invoice_id', $invoice->id)->first()?->delete();
        } else if ($type == 'service') {
            $invoice = ServiceInvoice::where('invoice_id', $invoice_id)->first();
        }
        if ($invoice) {
            $invoice->invoiceItems()->delete(); // delete all associated invoice items
            $invoice->delete();
        }

        $maxSerial = DB::table('manual_invoices')->where('generated_by', 'admin')->max('invoice_serial');
        BusinessSetting::updateOrInsert(['key' => 'admin_bill_serial_number'], [
            'value' => $maxSerial + 1
        ]);
        Toastr::success('Invoice Deleted Successfully');
        return back();
    }
    public function manual_invoice_view(Request $request, $type, $invoice_id)
    {
        $existingInvoice[0] = ManualInvoice::where('invoice_id', $invoice_id)->first();
        $quotations = InvoiceItem::where('rand_invoice_id',  $existingInvoice[0]->invoice_id)->get();
        if ($request->has('store')) {
            $service_det = Store::find($existingInvoice[0]->bill_to);
            $type = 'store';

            $addr['address'] = $service_det->address;
            $addr['city'] = Zone::find($service_det->zone_id)->name;
            $vendor_contact_det = null;
        } else {
            $vendor_contact_det = Store::find(Helpers::get_store_id());
            $service_det = User::find($existingInvoice[0]->bill_to);
            $type = 'user';
            $address = CustomerAddress::where('user_id', $existingInvoice[0]->bill_to)->first();
            if ($address) {
                $addr['address'] = $address->house . ', ' . $address->floor . ', ' . $address->road . ', ' . $address->address;
                $addr['city'] = $address->city;
            } else {
                $addr['address'] = '';
                $addr['city'] = '';
            }
        }
        return view('admin-views.billing.invoice-manual', compact('vendor_contact_det', 'invoice_id', 'service_det', 'quotations',  'existingInvoice', 'addr', 'type'));
    }
    public function invoice_view(Request $request,  $invoice_id)
    {
        $existingInvoice[0] = ManualInvoice::where('invoice_id', $invoice_id)->first();
        $quotations = InvoiceItem::where('rand_invoice_id',  $existingInvoice[0]->invoice_id)->get();
        $service_det = User::find($existingInvoice[0]->bill_to);

        if ($request->has('store')) {
            $service_det = Store::find($existingInvoice[0]->bill_to);
            if ($service_det) {
                $type = 'store';
                $addr['address'] = $service_det->address;
                $addr['city'] = Zone::find($service_det->zone_id)->name;
                $vendor_contact_det = null;
            }
        } else {
            $vendor_contact_det = Store::find(0);
            $service_det = User::find($existingInvoice[0]->bill_to);
            $type = 'user';
            $address = CustomerAddress::where('user_id', $existingInvoice[0]->bill_to)->first();
            if ($address) {
                $addr['address'] = $address->house . ', ' . $address->floor . ', ' . $address->road . ', ' . $address->address;
                $addr['city'] = $address->city;
            } else {
                $addr['address'] = '';
                $addr['city'] = '';
            }
        }

        return view('admin-views.billing.invoice', compact('invoice_id', 'service_det', 'quotations', 'existingInvoice', 'type', 'addr'));
    }
    public function test_invoice(Request $request)
    {

        $tempDir = storage_path('app/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'tempDir' => $tempDir,
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);
        // return view('invoice_template.new_main');   
        $html = View::make('invoice_template.subscription')->render();
        // $html = View::make('invoice_template.new_main')->render();

        $mpdf->WriteHTML($html);
        $pdfName = 'invoice_' . date('YmdHis') . '.pdf';
        $mpdf->Output();
    }
    public function save_invoice(Request $request)
    {
        // prx($request->all());
        if ($request->payment_stts == 'Unpaid') {
            $request->validate([
                'item_name.*' => 'required',
                'item_price.*' => 'required',
                'payment_date' => 'required',
                'reminder_date' => 'required',
                'bill_to' => 'required',
            ], ['bill_to.required' => 'Customer field is required']);
        }

        if ($request->bill_to_type == 'user') {
            $user = User::find($request->bill_to);
        } else {
            $user = Store::find($request->bill_to);
        }
        if (!$user || !$user->pin_code) {
            Toastr::error('Bill to pincode required');
            return redirect()->back();
        }

        $totalPrice = 0;
        foreach ($request->item_price as $key => $price) {
            $totalPrice += _taxIncludedPrice($price, $request->item_tax[$key], 'actual') * $request->item_qty[$key];
        }
        if (isset($request->item_price_new)) {
            foreach ($request->item_price_new as $key => $price) {
                $totalPrice += _taxIncludedPrice($price, $request->item_tax_new[$key], 'actual') * $request->item_qty_new[$key];
            }
        }

        $invoice = new ManualInvoice();
        if (Config::get('module.current_module_id') == 6) {
            $invoice->invoice_id = Helpers::generateInvoiceIdAdmin(6);
        } else {
            $invoice->invoice_id = Helpers::generateInvoiceIdAdmin(5);
        }

        $invoice->invoice_serial = BusinessSetting::where('key', 'admin_bill_serial_number')->first()->value - 1;
        $invoice->vendor_id = null;
        $invoice->bill_to = $request->bill_to;
        $invoice->bill_to_type = $request->bill_to_type;
        $invoice->total_amount =  round($totalPrice);
        $invoice->payment_method = 'Cash';
        $invoice->tax_type =  $request->tax_type;
        $invoice->module_id = Config::get('module.current_module_id');
        $invoice->payment_status =  $request->payment_stts;
        $invoice->reminder_date =  $request->reminder_date;
        $invoice->bill_to_pin_code     =  $user->pin_code;
        $invoice->payment_date =  $request->payment_date;
        $invoice->invoice_date =  $request->invoice_date ?? NOW();
        $invoice->generated_by =  'admin';
        $invoice->save();

        foreach ($request->item_name as $key => $name) {
            $InvoiceItem = new InvoiceItem();
            $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
            $InvoiceItem->name = $request->item_name[$key];
            $InvoiceItem->qty = $request->item_qty[$key] ?? 1;
            $InvoiceItem->price = $request->item_price[$key];
            $InvoiceItem->tax =  $request->tax_type == 'gst' ?  ($request->item_tax[$key] ?? 0) : 0;
            $InvoiceItem->hsn = $request->item_hsn[$key] ?? '';
            $InvoiceItem->save();
        }

        if (isset($request->invoice_item_new)) { // insert new items to invoice
            foreach ($request->invoice_item_new as $key => $id) {
                $InvoiceItem = new InvoiceItem();
                $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
                $InvoiceItem->name = $request->item_name_new[$key];
                $InvoiceItem->price = $request->item_price_new[$key];
                $InvoiceItem->qty = $request->item_qty_new[$key] ?? 1;
                $InvoiceItem->tax = $request->tax_type == 'gst' ?  ($request->item_tax_new[$key] ?? 0) : 0;
                $InvoiceItem->hsn = $request->item_hsn_new[$key] ?? '';
                $InvoiceItem->save();
            }
        }
        try {


            $data = _createBillPdf($invoice, 'admin');
            $invoice->update(['pdf' => $data['pdf']]);
            return redirect($data['url']);
        } catch (\Throwable $th) {
            prx($th);
            // return redirect()->back();
        }
    }

    public function invoice_correction(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required',
        ]);

        $invoice = ManualInvoice::where('invoice_id', $request->invoice_id)->first();

        if (!$invoice) {
            return redirect()->back()->with('error', 'Invoice not found.');
        }
        $invoice_items = InvoiceItem::where('rand_invoice_id', $invoice->invoice_id)->get();
        $from = $invoice->vendor_id ? 'vendor' : 'admin';

        $totalPrice = 0;
        foreach ($invoice_items as $item) {
            $totalPrice += _taxIncludedPrice($item->price, $item->tax, 'actual') * $item->qty;
        }
        $invoice->total_amount = floor($totalPrice);
        $invoice->save();
        try {
            $data = _createBillPdf($invoice, $from);
            $invoice->pdf = $data['pdf'];
            $invoice->save();

            return redirect($data['url']);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Failed to generate invoice PDF.');
        }
    }

    public function validate_invoicenum(Request $request)
    {
        if (is_serial_number_used($request->number, $request->prefixe, $request->tax_type)) {
            return 'exists';
        } else {
            return 'not exists';
        }
    }

    public function exportInvoiceSheet(Request $request)
    {
        $storeId = 0; // admin store id

        $query = ManualInvoice::with(['invoiceItems', 'storeCustomer'])
            ->where('vendor_id', $storeId)
            ->where('type', 'manual');

        $selected_items = json_decode($request->selected_items);

        if ($request->type == 'selected') {
            if (!is_array($selected_items) || empty($selected_items)) {
                Toastr::error('No invoice IDs selected for export.');
                return back();
            }
            $query->whereIn('id', $selected_items);
        }

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            Toastr::error('No invoices found.');
            return back();
        }

        $sheetData = [];
        $header = [
            'Temp Group',
            'Invoice Number',
            'Invoice Date',
            'Payment Status',
            'Payment Date',
            'Client ID',
            'Client Phone',
            'Tax Type',
            'Reminder Date',
            'Reminder Frequency Unit',
            'Reminder Frequency',
            'Item Name',
            'Item Price',
            'Qty',
            'HSN',
            'Tax Percent'
        ];
        $headings = $header;
        $sheetData = [];
        $tempIndex = 1;

        foreach ($invoices as $invoice) {
            $client = StoreCustomer::find($invoice->bill_to);
            $phone = $client->phone ?? '';
            $clientId = $client->id ?? '';

            foreach ($invoice->invoiceItems as $item) {
                $sheetData[] = [
                    "GROUP_" . $tempIndex,
                    $invoice->invoice_id,
                    Carbon::parse($invoice->invoice_date)->format('d-m-Y'),
                    $invoice->payment_status,
                    $invoice->payment_date ? Carbon::parse($invoice->payment_date)->format('d-m-Y') : '',
                    $clientId,
                    $phone,
                    $invoice->tax_type,
                    $invoice->reminder_date ? Carbon::parse($invoice->reminder_date)->format('d-m-Y') : '',
                    $invoice->reminder_freq_unit,
                    $invoice->reminder_freq,
                    $item->name,
                    $item->price,
                    $item->qty,
                    $item->hsn,
                    $item->tax,
                ];
            }
            $tempIndex++;
        }
        return Excel::download(new InvoiceExport($sheetData, $headings), 'invoices_' . now()->format('Y_m_d_H_i_s') . '.xlsx');
    }

    public function importInvoiceSheet(Request $request)
    {
        $data = Excel::toArray([], $request->file('file'));
        $rows = $data[0];
        $header = array_map('strtolower', array_map('trim', $rows[0]));
        unset($rows[0]);
        $existingInvoiceNum = [];
        $notFoundCustomer = [];

        $auditMsg = 'Imported Invoices : ';
        $invoiceIds = [];

        $grouped = [];

        foreach ($rows as $row) {
            $rowData = array_combine($header, $row);
            $tempGroup = $rowData['temp group'] ?? 'UNKNOWN';
            $grouped[$tempGroup][] = $rowData;
        }

        foreach ($grouped as $group => $items) {
            $invoice = null;
            $firstRow = $items[0];
            $invoice_number = $firstRow['invoice number'] ?? null;

            $user = null;
            $bill_to_id = null;
            $user_type = null;

            if (!empty($firstRow['client id'])) {
                $user = StoreCustomer::where('id', $firstRow['client id'])->where('store_id', 0)->first();
                $user_to_find = $firstRow['client id'];
            } elseif (!empty($firstRow['client phone'])) {
                $user = StoreCustomer::where('phone', $firstRow['client phone'])->where('store_id', 0)->first();
                $user_to_find = $firstRow['client phone'];
            }

            if ($user) {
                $bill_to_id = $user->id;
                $user_type = $user->user_type === 'customer' ? 'store_user' : 'store_vendor';
            } else {
                array_push($notFoundCustomer, $user_to_find);
                continue;
            }

            if ($invoice_number && str_contains($invoice_number, '_')) {
                $invoice_serial = substr(strrchr($invoice_number, "_"), 1);
                $prefix = substr($invoice_number, 0, strrpos($invoice_number, "_")) . '_';
                $exists = is_serial_number_used($invoice_serial, $prefix, $firstRow['tax type'] ?? 'non-gst');
                if ($exists) {
                    array_push($existingInvoiceNum, $invoice_number);
                    continue;
                }
            } else {
                $invoice_number = Helpers::generateInvoiceIdAdmin(6);
                $invoice_serial = substr($invoice_number, strrpos($invoice_number, '_') + 1);
            }
            array_push($invoiceIds, $invoice_number);

            $invoice = new ManualInvoice();
            $invoice->bill_to             = $bill_to_id;
            $invoice->bill_to_type        = 'user';
            $invoice->user_type           = $user_type;
            $invoice->invoice_id          = $invoice_number;
            $invoice->invoice_serial      = $invoice_serial;
            $invoice->type                = 'manual';
            $invoice->module_id           = 6;
            $invoice->payment_method      = 'Cash';
            $invoice->vendor_id           = 0;
            $invoice->invoice_date        = $this->formatExcelDate($firstRow['invoice date']);
            $invoice->payment_status      = $firstRow['payment status'] ?? 'Unpaid';
            $invoice->payment_date        = $this->formatExcelDate($firstRow['payment date']);
            $invoice->tax_type            = $firstRow['tax type'] ?? 'non-gst';
            $invoice->reminder_date       = $this->formatExcelDate($firstRow['reminder date']);
            $invoice->reminder_freq_unit  = $firstRow['reminder frequency unit'] ?? 'week';
            $invoice->reminder_freq       = $firstRow['reminder frequency'] ?? 1;
            $invoice->reminder_status     = !empty($firstRow['reminder frequency']) ? 1 : 0;

            $totalPrice = 0;
            foreach ($items as $item) {
                $qty = $item['qty'] ?? 1;
                $price = $item['item price'];
                $tax = $item['tax percent'] ?? 0;

                InvoiceItem::create([
                    'rand_invoice_id' => $invoice_number,
                    'name'            => $item['item name'],
                    'price'           => $price,
                    'tax'             => $tax,
                    'hsn'             => $item['hsn'] ?? null,
                    'qty'             => $qty,
                ]);

                $totalPrice += _taxIncludedPrice($price, $tax, 'actual') * $qty;
            }
            $invoice->total_amount = $totalPrice;
            $invoice->save();

            $data = _createBillPdf($invoice, 'admin');
            $invoice->update(['pdf' => $data['pdf']]);

            $customer = StoreCustomer::find($bill_to_id);
            $credit_account = Helpers::ensureSalesAccount();
            $debit_account = Helpers::ensureCustomerLedger($customer);
            $data = [
                'date' => now(),
                'amount' => $totalPrice,
                'voucher_type' => 'Sales',
                'invoice_id' => 'manual-' . $invoice->id,
                'status' => $invoice->payment_status == 'Paid' ? 'approved' : 'pending',
                'description' => 'Sales Invoice',
            ];
            $voucher = _masterLedgerEntry($data, $credit_account, $debit_account, 'customer', 'admin', null);

            if ($request->payment_stts == 'Paid') {
                _saveDayBookEntry($totalPrice, 'credit', 0, "Sales Invoice", $invoice->id, $voucher?->id);
            }
        }

        if (count($invoiceIds)) {
            $auditMsg .= implode(',', $invoiceIds);
            _auditLogs($auditMsg);
        }

        $duplicate_error = '';
        $client_error = '';
        if (!empty($existingInvoiceNum)) {
            $duplicate_error = 'Invoice ID(s): ' . implode(', ', $existingInvoiceNum) . ' already used. ';
        }
        if (!empty($notFoundCustomer)) {
            $client_error = 'Client(s): ' . implode(', ', $notFoundCustomer) . ' not found. ';
        }

        if (!empty($existingInvoiceNum) || !empty($notFoundCustomer)) {
            return response()->json([
                'status' => 'existing',
                'message' => $duplicate_error . $client_error . 'Other Invoices Imported Successfully.'
            ]);
        } else {
            return response()->json(['status' => 'done', 'message' => 'Imported Successfully']);
        }
    }

    private function formatExcelDate($value)
    {
        if (!$value) return null;
        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
        }
        return date('Y-m-d', strtotime($value));
    }


    public function search_bill_from(Request $request)
    {
        $query = $request->input('q', '');
        $type_filter = $request->input('type'); // 'vendor' or 'store' from JS


        if ($type_filter === 'vendor') {

            $results = \App\Models\StoreCustomer::where('store_id', 0)
                ->where(function ($q) use ($query) {
                    $q->where('f_name', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%");
                })
                ->select('id', 'f_name',  'phone')
                ->limit(20)
                ->get()
                ->map(function ($vendor) {
                    return [
                        'id' => 'vendor_' . $vendor->id,
                        'text' => "{$vendor->f_name} {$vendor->l_name} ({$vendor->phone})",
                        'type' => 'vendor'
                    ];
                });
        } else {

            $results = \App\Models\Store::where('status', 1)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%");
                })
                ->select('id', 'name', 'phone')
                ->limit(20)
                ->get()
                ->map(function ($store) {
                    return [
                        'id' => 'store_' . $store->id,
                        'text' => "{$store->name} ({$store->phone})",
                        'type' => 'store'
                    ];
                });
        }

        return response()->json(['results' => $results->values()->all()]);
    }


    public function pay_bill(Request $request, $invoice_id)
    {
        $invoice = ManualInvoice::find($invoice_id);
        return view('admin-views.billing.payment_details', compact('invoice'));
    }

    public function make_payment(Request $request, $invoice_id)
    {
        $invoice = ManualInvoice::find($invoice_id);
        if ($invoice->payment_status == 'Paid') {
            Toastr::success('Bill Already Paid');
            return back();
        }
        $amount = $invoice->total_amount;
        $store = Store::find(0);
        $payer = new Payer($store['name'], $store['email'], $store['phone'], $store['address']);
        $currency = BusinessSetting::where(['key' => 'currency'])->first()->value;
        $additional_data = [
            'business_name' => BusinessSetting::where(['key' => 'business_name'])->first()?->value,
            'business_logo' => asset('storage/app/public/business') . '/' . BusinessSetting::where(['key' => 'logo'])->first()?->value
        ];
        $payment_info = new PaymentInfo(
            success_hook: 'invoice_payment_success',
            failure_hook: 'invoice_payment_failed',
            currency_code: $currency,
            payment_method: 'razor_pay',
            payment_platform: 'web',
            payer_id: 0,
            receiver_id: 100,
            additional_data: $additional_data,
            payment_amount: $amount,
            external_redirect_link: 'billing/pay-bill/' . $invoice->id,
            attribute: 'manual_invoice_payment',
            attribute_id: $invoice_id
        );

        $receiver_info = new Receiver('Admin', 'example.png');
        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);
        return redirect()->to($redirect_link);
    }

    public function edit_service_invoice(Request $request, $invoice_id)
    {
        $invoice = ServiceInvoice::where('id', $invoice_id)->first();
        $invoice_items = InvoiceItem::where('rand_invoice_id', $invoice->invoice_id)->get();
        $customers = User::where('status', 1)->get();
        return view('admin-views.billing.service_invoice_edit', compact('customers', 'invoice_items', 'invoice'));
    }

    public function edit(Request $request, $invoice_id)
    {
        $invoice = ManualInvoice::with('storeCustomer')->where('id', $invoice_id)->first();
        $invoice_items = InvoiceItem::where('rand_invoice_id', $invoice->invoice_id)->get();
        $customers = User::where('status', 1)->get();
        return view('admin-views.billing.invoice_edit', compact('customers', 'invoice_items', 'invoice'));
    }

    public function update_invoice(Request $request)
    {
        $request->validate(
            [
                'bill_to' => 'required',
                'item_name' => 'required|array|min:1',
                'item_name.*' => 'required|string|max:255',
            ],
            ['item_name.min' => 'Atleast 1 item is required']
        );

        $totalPrice = 0;
        foreach ($request->item_price as $key => $price) {
            $totalPrice += _taxIncludedPrice($price, $request->item_tax[$key] ?? 0, 'actual') * $request->item_qty[$key];
        }
        if (isset($request->item_price_new)) {
            foreach ($request->item_price_new as $key => $price) {
                $totalPrice += _taxIncludedPrice($price, $request->item_tax_new[$key] ?? 0, 'actual') * $request->item_qty_new[$key];
            }
        }

        $invoice = ManualInvoice::where('invoice_id', $request->invoice_id)->first();

        if (Storage::disk('public')->exists('invoice/' . $invoice->pdf)) {
            Storage::disk('public')->delete('invoice/' . $invoice->pdf);
        }

        $invoice->bill_to = $request->bill_to;
        if ($request->has('bill_to_type')) {
            $invoice->bill_to_type = $request->bill_to_type;
        }
        $invoice->total_amount = $totalPrice;
        $invoice->tax_type = $request->tax_type;
        $invoice->payment_status = $request->payment_stts;
        $invoice->payment_date = $request->payment_date ?? date('Y-m-d');
        $invoice->reminder_date = $request->reminder_date ?? date('Y-m-d');
        $invoice->reminder_freq = $request->reminder_freq ?? 1;
        $invoice->reminder_freq_unit = $request->reminder_freq_unit ?? 'week';
        $invoice->save();

        $invoice_items = InvoiceItem::where('rand_invoice_id', $invoice->invoice_id)->get();
        $invoice_items->each->delete();

        foreach ($request->item_name as $key => $name) {
            $InvoiceItem = new InvoiceItem();
            $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
            $InvoiceItem->name = $request->item_name[$key];
            $InvoiceItem->qty = $request->item_qty[$key];
            $InvoiceItem->price = $request->item_price[$key];
            $InvoiceItem->unit = $request->item_unit[$key];
            $InvoiceItem->tax = $request->tax_type == 'gst' ? ($request->item_tax[$key] ?? 0) : 0;
            $InvoiceItem->hsn = $request->item_hsn[$key];
            $InvoiceItem->save();
        }

        if (isset($request->item_name_new)) {
            foreach ($request->item_name_new as $key => $id) {
                $InvoiceItem = new InvoiceItem();
                $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
                $InvoiceItem->name = $request->item_name_new[$key];
                $InvoiceItem->price = $request->item_price_new[$key];
                $InvoiceItem->qty = $request->item_qty_new[$key];
                $InvoiceItem->unit = $request->item_unit_new[$key];
                $InvoiceItem->tax = $request->tax_type == 'gst' ? ($request->item_tax_new[$key] ?? 0) : 0;
                $InvoiceItem->hsn = $request->item_hsn_new[$key];
                $InvoiceItem->save();
            }
        }

        // voucher update
        $voucher = StoreVoucher::where('invoice_id', 'manual-' . $invoice->id)->first();
        if ($voucher) {
            $voucher->status = $invoice->payment_status == 'Paid' ? 'approved' : 'pending';
            $voucher->completed_at = $invoice->payment_status == 'Paid' ? now() : null;
            $voucher->total_amount = $totalPrice;
            $voucher->save();

            $ledger_entries = StoreLedgerEntry::where('voucher_id', $voucher->id)->get();
            foreach ($ledger_entries as $key => $value) {
                $value->status = $invoice->payment_status == 'Paid' ? 'approved' : 'pending';
                $value->completed_at = $invoice->payment_status == 'Paid' ? now() : null;
                $value->credit = $value->credit > 0 ? $totalPrice : 0;
                $value->debit = $value->debit > 0 ? $totalPrice : 0;
                $value->save();
            }
        }

        $from = $invoice->vendor_id ? 'vendor' : 'admin';
        $data = _createBillPdf($invoice, $from);
        $invoice->update(['pdf' => $data['pdf']]);
        try {
            return redirect($data['url']);
        } catch (\Throwable $th) {
            //
        }
    }

    public function service_update_invoice(Request $request)
    {
        $request->validate(
            [
                'item_name' => 'required_without:item_name_new|array',
                'item_name.*' => 'nullable|string|max:255',
                'item_name_new' => 'required_without:item_name|array',
                'item_name_new.*' => 'nullable|string|max:255',
            ]
        );

        $totalPrice = 0;
        if (isset($request->item_price)) {
            foreach ($request->item_price as $key => $price) {
                $totalPrice += _taxIncludedPrice($price, $request->item_tax[$key] ?? 0, 'actual') * $request->item_qty[$key];
            }
        }
        if (isset($request->item_price_new)) {
            foreach ($request->item_price_new as $key => $price) {
                $totalPrice += _taxIncludedPrice($price, $request->item_tax_new[$key] ?? 0, 'actual') * $request->item_qty_new[$key];
            }
        }

        $invoice = ServiceInvoice::where('invoice_id', $request->invoice_id)->first();

        if (Storage::disk('public')->exists('invoice/' . $invoice->pdf)) {
            Storage::disk('public')->delete('invoice/' . $invoice->pdf);
        }
        $invoice->total_amount = $totalPrice;
        $invoice->payment_status = $request->payment_stts;
        $invoice->payment_date = $request->payment_date ?? date('Y-m-d');
        $invoice->reminder_date = $request->reminder_date ?? date('Y-m-d');
        $invoice->reminder_freq = $request->reminder_freq ?? 1;
        $invoice->reminder_freq_unit = $request->reminder_freq_unit ?? 'week';
        $invoice->save();

        $invoice_items = InvoiceItem::where('rand_invoice_id', $invoice->invoice_id)->get();
        $invoice_items->each->delete();

        if (isset($request->item_name)) {
            foreach ($request->item_name as $key => $name) {
                $InvoiceItem = new InvoiceItem();
                $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
                $InvoiceItem->name = $request->item_name[$key];
                $InvoiceItem->qty = $request->item_qty[$key];
                $InvoiceItem->price = $request->item_price[$key];
                $InvoiceItem->unit = $request->item_unit[$key];
                $InvoiceItem->tax = $request->tax_type == 'gst' ? ($request->item_tax[$key] ?? 0) : 0;
                $InvoiceItem->hsn = $request->item_hsn[$key];
                $InvoiceItem->save();
            }
        }

        if (isset($request->item_name_new)) {
            foreach ($request->item_name_new as $key => $id) {
                $InvoiceItem = new InvoiceItem();
                $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
                $InvoiceItem->name = $request->item_name_new[$key];
                $InvoiceItem->price = $request->item_price_new[$key];
                $InvoiceItem->qty = $request->item_qty_new[$key];
                $InvoiceItem->unit = $request->item_unit_new[$key];
                $InvoiceItem->tax = $request->tax_type == 'gst' ? ($request->item_tax_new[$key] ?? 0) : 0;
                $InvoiceItem->hsn = $request->item_hsn_new[$key];
                $InvoiceItem->save();
            }
        }

        $from = $invoice->vendor_id ? 'vendor' : 'admin';
        $data = _createBillPdf($invoice, $from);
        $invoice->pdf = $data['pdf'];
        $invoice->save();
        try {
            return redirect($data['url']);
        } catch (\Throwable $th) {
            //
        }
    }

    public function delete(Request $request, $type, $id)
    {
        if ($type == 'service') {
            $invoice = ServiceInvoice::where('id', $id)->first();
            $invoice_id = $invoice->invoice_id;
            if ($invoice) {
                $invoice->delete();
                Toastr::success('Invoice deleted successfully');
            } else {
                Toastr::error('Invoice not found');
            }
        } else {
            $invoice = ManualInvoice::where('id', $id)->first();
            $invoice_id = $invoice->invoice_id;
            if ($invoice) {
                if (Storage::disk('public')->exists('invoice/' . $invoice->pdf)) {
                    Storage::disk('public')->delete('invoice/' . $invoice->pdf);
                }
                $invoice->delete();
                Toastr::success('Invoice deleted successfully');
            } else {
                Toastr::error('Invoice not found');
            }
        }
        InvoiceItem::where('rand_invoice_id', $invoice_id)->delete();
        return redirect()->back();
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
        if (is_serial_number_used($request->number, 'MSM_',  $request->tax_type)) {
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
            $invoice_id = $request->prefixe . $request->number;
        } else {
            $invoice_id = Helpers::generateInvoiceIdAdmin(6);
        }

        $bill_to = $bill_to ?? $request->bill_to;
        $bill_to_type =  $request->bill_to_type;
        if ($bill_to_type == 'mychitti_client') {
            // store_customers table (store_id = 0) 
            $userTypeInfo = StoreCustomer::find($bill_to)->user_type ?? 'customer';
            $user_type = $userTypeInfo == 'customer' ? 'store_user' : 'store_vendor';
        } elseif ($bill_to_type == 'vendor') {
            $user_type = 'store_vendor';
        } else {
            // bill_to_type == 'user' → users table 
            $user_type = 'user';
        }


        $invoice = new ManualInvoice;
        $invoice->task_id =  $task_id;
        $invoice->invoice_id = $invoice_id;
        $invoice->reference_number = $request->reference_number;
        $invoice->vendor_id = 0; // for admin invoice
        $invoice->bill_to = $bill_to;
        $invoice->bill_to_type = $bill_to_type;
        $invoice->user_type = $user_type;
        $invoice->module_id = 6;
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
        $invoice->created_by = auth('admin')->check() ? 0 : auth('admin')->id();
        $invoice->save();

        if ($request->has('item_name')) {
            foreach ($request->item_name as $key => $name) {
                $InvoiceItem = new InvoiceItem();
                $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
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
                $InvoiceItem->name = $request->item_name_new[$key];
                $InvoiceItem->price = $request->item_price_new[$key];
                $InvoiceItem->qty = $request->item_qty_new[$key];
                $InvoiceItem->unit = $request->item_unit_new[$key] ?? null;
                $InvoiceItem->tax = $request->tax_type == 'gst' ?  ($request->item_tax_new[$key] ?? 0) : 0;
                $InvoiceItem->hsn = $request->item_hsn_new[$key];
                $InvoiceItem->inv_id = isset($request->inventory_item_id_new[$key]) ? $request->inventory_item_id_new[$key] : null;
                $InvoiceItem->save();
                if ($request->has('inventory_item_id_new') && $request->inventory_item_id_new[$key]) {
                    $inv_items++;
                    _updateInventoryStock($request->inventory_item_id_new[$key], $request->item_qty_new[$key], (isset($request->item_unit_new[$key]) ? $request->item_unit_new[$key] : null));
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

        if ($bill_to_type == 'mychitti_client') {
            // store_customers table (store_id = 0)
            $customer = StoreCustomer::where('store_id', 0)->where('id', $request->bill_to)->first();
        } elseif ($bill_to_type == 'vendor') {
            $customer = Store::find($request->bill_to);
        } else {
            // bill_to_type == 'user' → users table
            $customer = User::find($request->bill_to);
        }
        // $debit_account = Helpers::ensureCustomerLedger($customer);
        // $credit_account = Helpers::ensureSalesAccount();
        // $voucher =  _masterLedgerEntry(
        //     $data,
        //     $credit_account,
        //     $debit_account,
        //     'customer',
        //     'admin',
        //     null
        // );

        if ($invoice->payment_status == 'Paid') {
            // _saveDayBookEntry($totalPrice, 'credit', 0, "Sales Invoice", $invoice->id, $voucher?->id, $invoice->invoice_date, $invoice->reference_number, $request->payment_mode);
        }

        _auditLogs('Created Invoice : ' . $invoice->invoice_id, 0);
        $docData = Helpers::generateInventoryGatepass($invoice, (object)[], 'sale', null, 0);

        // Inventory order 
        if ($inv_items) {
            Helpers::_placeInventoryOrder($invoice);
        }
        $data = _createBillPdf($invoice, 'admin');
        $invoice->update(['pdf' => $data['pdf']]);
        try {
            return redirect($data['url']);
        } catch (\Throwable $th) {
            //
        }
        // return redirect()->route('vendor.invoice.manual-invoice-view', ['manual', $invoice->invoice_id]);
    }
    public function save_new_manual_invoice(Request $request)
    {
        $request->validate([
            'bill_to' => 'required',
            'item_name_new.*' => 'required',
        ], [
            'bill_to.required' => 'Customer field is required',
            'item_name_new.*.required' => 'Item Name is required',
        ]);

        if ($request->payment_stts == 'Paid' && $request->payment_mode == 'Cash and Online') {
            if ($request->inv_total_amount != $request->cash_amount + $request->online_amount) {
                Toastr::error("Amount Mismatched");
                return back();
            }
        }

        $totalItemsAmount = 0;
        $totalItemsTax = 0;
        $inv_items = 0;
        $invoiceItemsToSave = [];

        foreach ($request->item_name_new as $key => $name) {
            $InvoiceItem = new InvoiceItem();
            $InvoiceItem->inventory_item_id = $request->inventory_item_id[$key];
            $InvoiceItem->name = $request->item_name_new[$key] ?? '';
            $InvoiceItem->qty = $quantity = $request->item_qty_new[$key] ?? 1;
            $InvoiceItem->price = $unitPrice = $request->item_price_new[$key] ?? 0;
            $InvoiceItem->unit = $request->has('item_unit_new.' . $key) ? $request->item_unit_new[$key] : null;
            $InvoiceItem->tax = $taxPercent = $request->tax_type == 'gst' ? ($request->item_tax_new[$key] ?? 0) : 0;
            $InvoiceItem->hsn = $request->item_hsn_new[$key];
            $InvoiceItem->inv_id = $request->inventory_item_id[$key] ?? null;

            $amount = $unitPrice * $quantity;
            $taxAmount = $amount * ($taxPercent / 100);

            $totalItemsAmount += $amount;
            $totalItemsTax += $taxAmount;

            $invoiceItemsToSave[] = $InvoiceItem;

            if ($request->inventory_item_id[$key]) {
                _updateInventoryStock($request->inventory_item_id[$key], $request->item_qty_new[$key], ($request->has('item_unit_new.' . $key) ? $request->item_unit_new[$key] : null));
                $inv_items++;
            }
        }

        // Additional Charges
        $additionalChargesTotal = 0;
        $additionalChargesTax = 0;
        $charges = [];
        $names = $request->charges_name;
        $amounts = $request->add_charges;
        $taxes = $request->charges_tax;
        $statuses = $request->tax_status;
        $add_charges = [];
        foreach ($names as $index => $name) {
            if (trim($name) === '' || trim($amounts[$index]) === '') {
                continue;
            }
            $chargeInput = (float) $amounts[$index] ?? 0;
            $taxPercent = (float) $taxes[$index] ?? 0;
            $taxStatus = $statuses[$index] ?? 'included';

            if (strtolower($taxStatus) === 'included') {
                $baseAmount = $chargeInput / (1 + ($taxPercent / 100));
                $taxAmount = $chargeInput - $baseAmount;
                $charges = $baseAmount;
            } else {
                $charges = $chargeInput;
                $taxAmount = $chargeInput * ($taxPercent / 100);
            }

            $additionalChargesTotal += $charges;
            $additionalChargesTax += $taxAmount;

            $add_charges[] = [
                'name' => $name,
                'amount' => $amounts[$index] ?? 0,
                'calc_amount' => $charges,
                'tax' => $request->tax_type == 'gst' ? ($taxes[$index] ?? 0) : 0,
                'status' => $statuses[$index] ?? 'included',
            ];
        }

        // Discount
        $discountValue = (float) $request->total_discount;
        $discountType = $request->total_discount_type;
        $baseAmount = $totalItemsAmount + $additionalChargesTotal;
        $discount = $discountType === 'percent'
            ? ($baseAmount * $discountValue) / 100
            : $discountValue;

        $taxableAmount = $baseAmount - $discount;

        // TDS, TDS under GST, TCS
        $tds_rate_info = TaxRate::find($request->tds_rate_id);
        $tcs_rate_info = TaxRate::find($request->tcs_rate_id);
        $tdsPercent = (float) $tds_rate_info?->rate ?? 0;
        $tdsGstPercent = (float) $request->tds_gst_percent ?? 0;
        $tcsPercent = (float) $tcs_rate_info?->rate ?? 0;

        $tdsAmount = $tdsPercent ? $taxableAmount * ($tdsPercent / 100) : 0;
        $tdsGstAmount = $tdsGstPercent ? $taxableAmount * ($tdsGstPercent / 100) : 0;
        $tcsAmount = $tcsPercent ? $taxableAmount * ($tcsPercent / 100) : 0;

        // GST Surcharge
        $gstTaxAmount = $totalItemsTax + $additionalChargesTax;
        $surchargePercent = (float) $request->surcharge;
        $surchargeAmount = $gstTaxAmount * ($surchargePercent / 100);
        $taxAfterSurcharge = $gstTaxAmount + $surchargeAmount;

        // Cess
        $cessPercent = (float) $request->cess;
        $cessAmount = $taxAfterSurcharge * ($cessPercent / 100);

        // Final Totals
        $finalTax = $taxAfterSurcharge + $cessAmount;
        $total_amount = $taxableAmount + $finalTax + $tcsAmount - $tdsAmount - $tdsGstAmount;
        // print_r($request->bill_to_type);
        // echo ' - ';
        // prx($request->bill_to);
        $bill_to_type = $request->bill_to_type ?? 'user';

        if ($bill_to_type == 'mychitti_client') {
            $userTypeInfo = StoreCustomer::find($request->bill_to)->user_type ?? 'customer';
            $user_type = $userTypeInfo == 'customer' ? 'store_user' : 'store_vendor';
        } elseif ($bill_to_type == 'vendor') {
            $user_type = 'vendor';
        } else {
            $user_type = 'user';
        }

        $invoice_id = Helpers::generateInvoiceIdAdmin(6);

        // custom header labels
        $labels = $request->header_label ?? [];
        $fields = $request->header_field ?? [];
        $other_headers = [];
        foreach ($labels as $index => $label) {
            $field = $fields[$index] ?? null;
            $other_headers[$label] = $field;
        }

        $invoice = new ManualInvoice;
        $invoice->invoice_id = $invoice_id;
        $invoice->vendor_id = 0; // admin
        $invoice->bill_to = $request->bill_to;
        $invoice->bill_to_type = $bill_to_type;
        $invoice->user_type = $user_type;
        $invoice->module_id = 6;
        $invoice->tax_type = $request->tax_type;
        $invoice->payment_method = $request->payment_mode;
        $invoice->payment_mode = $request->payment_mode;
        $invoice->invoice_date = $request->invoice_date;
        $invoice->payment_status = $request->payment_stts;
        $invoice->payment_date = $request->payment_date;
        $invoice->reminder_date = $request->reminder_date;
        $invoice->reminder_freq = $request->reminder_freq;
        $invoice->reminder_freq_unit = $request->reminder_freq_unit;

        if ($request->payment_stts == 'Paid' && $request->payment_mode == 'Cash and Online') {
            $invoice->cash_amount = $request->cash_amount;
            $invoice->online_amount = $request->online_amount;
        }

        if ($request->tax_type == 'gst') {
            $invoice->tds_rate_id = $request->tds_rate_id;
            $invoice->tds_percent = $tds_rate_info?->rate;
            $invoice->tds_amount = $tdsAmount;
            $invoice->tcs_rate_id = $request->tcs_rate_id;
            $invoice->tcs_percent = $tcs_rate_info?->rate;
            $invoice->tcs_amount = $tcsAmount;
            $invoice->tds_gst_amount = $tdsGstAmount;
            $invoice->cess_percent = $request->cess;
            $invoice->cess_amount = $cessAmount;
            $invoice->surcharge_percent = $request->surcharge;
            $invoice->surcharge_amount = $surchargeAmount;
        }

        $invoice->customer_comment = $request->customer_comment;
        $invoice->bank_account = $request->bank_account;
        $invoice->reference = $request->reference;
        $invoice->discount_value = $request->total_discount;
        $invoice->discount_type = $request->total_discount_type;
        $invoice->discount_amount = $discount;
        $invoice->sign = $request->sign;
        $invoice->terms_and_conditions = $request->terms_and_conditions;
        $invoice->payment_notes = $request->payment_notes;
        $invoice->advance_amount = $request->advance_amount;
        $invoice->additional_charges = json_encode($add_charges);
        $invoice->custom_headers = json_encode($other_headers);
        $invoice->taxable_amount = $taxableAmount;
        $invoice->final_tax = $finalTax;
        $invoice->total_amount = $total_amount;
        $invoice->save();

        // PETTY CASHBOOK ENTRY
        if ($request->payment_stts == 'Paid') {
            if ($request->payment_method == 'Cash') {
                $amount = $total_amount;
            } else {
                $amount = $request->cash_amount;
            }
            _savePettyCashBookEntry($amount, 'Received', 'Invoice Payment', $request->invoice_date, $invoice->invoice_id);
        }

        foreach ($invoiceItemsToSave as $item) {
            $item->rand_invoice_id = $invoice->invoice_id;
            $item->save();
        }

        // RECHECK AMOUNT
        if ($request->payment_mode == 'Cash and Online' && ($invoice->total_amount != $request->cash_amount + $request->online_amount)) {
            $invoice->payment_status = 'Unpaid';
            $invoice->save();
        }

        // Inventory order
        if ($inv_items) {
            Helpers::_placeInventoryOrder($invoice);
        }

        $invoice = ManualInvoice::with('bankAccount')->where('id', $invoice->id)->first();

        // master ledger entry
        if ($bill_to_type == 'mychitti_client') {
            // store_customers table (store_id = 0)
            $customer = StoreCustomer::where('store_id', 0)
                ->where('id', $request->bill_to)
                ->first();
        } elseif ($bill_to_type == 'vendor') {
            $customer = Store::find($request->bill_to);
        } else {
            // bill_to_type == 'user' → users table
            $customer = User::find($request->bill_to);
        }

        $debit_account = Helpers::ensureCustomerLedger($customer);
        $credit_account = Helpers::ensureSalesAccount();

        $data = [
            'date' => $request->invoice_date ?? now(),
            'amount' => round($total_amount),
            'status' => $request->payment_stts == 'Paid' ? 'approved' : 'pending',
            'payment_mode' => $request->payment_stts == 'Paid' ? $request->payment_method : null,
            'invoice_id' => 'manual-' . $invoice->id,
            'description' => 'Sales Invoice',
            'voucher_type' => 'Sales',
            'file' => $request->hasFile('file') ? $request->file('file') : null,
        ];

        $voucher = _masterLedgerEntry(
            $data,
            $credit_account,
            $debit_account,
            'other',
            'admin',
            null
        );
        if ($request->payment_stts == 'Paid') {
            _saveDayBookEntry($total_amount, 'debit', 0, "Sales Invoice", $invoice->id, $voucher?->id);
        }

        $data = _createBillPdf($invoice, 'admin');
        $invoice->update(['pdf' => $data['pdf']]);

        try {
            if ($request->form_type == 'ajax') {
                return response()->json(['status' => true, 'msg' => "Added Successfully"]);
            } else {
                return redirect()->route('admin.billing.view-invoice', ['id' => $invoice->id]);
            }
        } catch (\Throwable $th) {
            //
        }
    }

    public function view_invoice(Request $request, $id)
    {
        $invoice = ManualInvoice::find($id);
        return view('admin-views.billing.view_invoice', compact('invoice'));
    }

    public function get_invoices_by_vendor(Request $request)
    {
        $storeId = 0;
        $vendor_id = $request->vendor_id;
        $invoices = ManualInvoice::where('bill_to', $storeId)->where('store_vendor_id', $vendor_id)->where('bill_to_type', 'vendor')->pluck('invoice_id');
        return response()->json(['status' => true, 'invoices' => $invoices]);
    }

    public function save_purchase_invoice(Request $request)
    {
        if ($request->payment_stts == 'Unpaid') {
            $request->validate([
                'payment_date' => 'required',
                'reminder_date' => 'required',
            ]);
        }
        // prx($request->all());   
        $request->validate([
            'bill_from' => 'required',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf,doc,docx',
        ], ['bill_from.required' => 'Seller field is required']);

        // Parse bill_from prefixed ID
        if (str_starts_with($request->bill_from, 'vendor_')) {
            $bill_from_id = (int)str_replace('vendor_', '', $request->bill_from);
            $store = \App\Models\StoreCustomer::where('store_id', 0)->find($bill_from_id);
            $vendor_type = 'vendor';
        } elseif (str_starts_with($request->bill_from, 'store_')) {
            $bill_from_id = (int)str_replace('store_', '', $request->bill_from);
            $store = \App\Models\Store::withoutGlobalScopes()->find($bill_from_id);
            $vendor_type = 'store';
        } else {
            Toastr::error('Invalid seller selection');
            return back();
        }

        if (!$store) {
            Toastr::error('Seller not found');
            return back();
        }

        $exists = ManualInvoice::where("invoice_id", $request->invoice_id)->exists();
        if ($exists) {
            Toastr::error('Invoice Id Already Exists');
            return back();
        }

        $items = [];
        foreach ($request->invoice_item_new as $key => $id) {
            $inventory_item = InventoryItem::where('item_name', $request->item_name_new[$key])->where('store_id', 0)->first();
            if ($inventory_item) {
                $inv_id = $inventory_item->id;
            }
            $items[] = [
                'name' => $request->item_name_new[$key],
                'price' => $request->item_price_new[$key],
                'qty' => $request->item_qty_new[$key],
                'unit' => $request->item_unit_new[$key] ?? 0,
                'tax' => $request->item_tax_new[$key] ?? 0,
                'hsn' => $request->item_hsn_new[$key],
                'inv_id' => $request->inventory_item_id[$key] ?? null,
            ];
        }

        $data = [
            'store_vendor_id' => $bill_from_id,
            'vendor_type' => $vendor_type,
            'bill_to_type' => 'admin',
            'bill_from' => null,
            'tax_type' => $request->tax_type,
            'invoice_date' => $request->invoice_date,
            'invoice_id' => $request->invoice_id,
            'payment_stts' => $request->payment_stts,
            'payment_date' => $request->payment_date,
            'reminder_date' => $request->reminder_date,
            'reminder_freq' => $request->reminder_freq,
            'reminder_freq_unit' => $request->reminder_freq_unit,
            'items' => $items,
            'file' => $request->file('file'),
        ];
// prx($request->all());
        $invoiceData = $this->processInvoice($data, true);

        if ($invoiceData != 'store_not_found') {
            $totalPrice = 0;
            foreach ($items as $item) {
                $totalPrice += _taxIncludedPrice($item['price'], $item['tax'], 'actual') * $item['qty'];
            }

            if($request->bill_from_type == 'store'){
                $customer = Store::find($bill_from_id);
                $debit_account = Helpers::ensurePurchaseAccount('Purchase Bill');
                $credit_account = Helpers::ensureAdminDefaultExpensesAccount($customer);
            }else{
                $customer = StoreCustomer::find($bill_from_id);
                $debit_account = Helpers::ensurePurchaseAccount('Purchase Bill');
                $credit_account = Helpers::ensureCustomerLedger($customer);

            }
            $data = [
                'date' => now(),
                'amount' => $totalPrice,
                'invoice_id' => 'manual-' . $invoiceData['invoice']->id,
                'voucher_type' => 'Purchase',
                'status' => $request->payment_stts == 'Paid' ? 'approved' : 'pending',
                'description' => 'Purchase Invoice',
            ];
            $voucher = _masterLedgerEntry($data, $credit_account, $debit_account, 'admin', 'other', null);
            if ($request->payment_stts == 'Paid') {
                _saveDayBookEntry($totalPrice, 'debit', 0, "Purchase Invoice", $invoiceData['invoice']->id, $voucher?->id);
            }

            $docData = Helpers::generateInventoryGatepass($invoiceData['invoice'], (object)[], 'purchase', null, 0);
        }

        if (!is_array($invoiceData)) {
            Toastr::error($invoiceData);
            return back();
        }
        return redirect($invoiceData['pdf']['url']);
    }

    public function importPurchaseInvoices(Request $request)
    {
        $data = Excel::toArray([], $request->file('file'));
        $rows = $data[0];
        $header = array_map('strtolower', array_map('trim', $rows[0]));
        unset($rows[0]);

        $auditMsg = 'Imported Purchase Invoices : ';
        $invoiceIds = [];
        $grouped = [];
        $notFoundStores = [];

        foreach ($rows as $row) {
            $mapped = array_combine($header, $row);
            $invKey = $mapped['temp group'];
            $grouped[$invKey]['store_vendor_id'] = $mapped['vendor id'];
            $grouped[$invKey]['bill_from'] = $mapped['vendor id'];
            $grouped[$invKey]['tax_type'] = $mapped['tax type'];
            $grouped[$invKey]['invoice_date'] = $mapped['invoice date'];
            $grouped[$invKey]['payment_stts'] = $mapped['payment status'] ?? 'Paid';
            $grouped[$invKey]['payment_date'] = $mapped['payment date'] ?? $mapped['invoice date'];
            $grouped[$invKey]['reminder_date'] = $mapped['reminder date'] ?? null;
            $grouped[$invKey]['reminder_freq'] = $mapped['reminder frequency'] ?? 1;
            $grouped[$invKey]['reminder_freq_unit'] = $mapped['reminder frequency unit'] ?? 'week';

            $grouped[$invKey]['items'][] = [
                'name' => $mapped['item name'],
                'price' => $mapped['item price'],
                'qty' => $mapped['qty'],
                'unit' => $mapped['item_unit'] ?? null,
                'tax' => $mapped['tax percent'],
                'hsn' => $mapped['hsn'],
                'inv_id' => $mapped['inventory item id'],
            ];
        }

        foreach ($grouped as $data) {
            $invoiceStatus = $this->processInvoice($data);
            if ($invoiceStatus == 'store_not_found') {
                array_push($notFoundStores, $data['store_vendor_id']);
            } else {
                $totalPrice = 0;
                foreach ($data['items'] as $item) {
                    $totalPrice += _taxIncludedPrice($item['price'], $item['tax'], 'actual') * $item['qty'];
                }
                array_push($invoiceIds, $invoiceStatus['invoice']->invoice_id);
                $customer = StoreCustomer::find($data['store_vendor_id']);
                $debit_account = Helpers::ensurePurchaseAccount('Purchase Bill');
                $credit_account = Helpers::ensureCustomerLedger($customer);
                $data = [
                    'date' => now(),
                    'amount' => $totalPrice,
                    'invoice_id' => 'manual-' . $invoiceStatus['invoice']->id,
                    'voucher_type' => 'Purchase',
                    'status' => $invoiceStatus['payment_status'] == 'Paid' ? 'approved' : 'pending',
                    'description' => 'Purchase Invoice',
                ];
                $voucher = _masterLedgerEntry($data, $credit_account, $debit_account, 'admin', 'other', null);
                if ($request->payment_stts == 'Paid') {
                    _saveDayBookEntry($totalPrice, 'debit', 0, "Purchase Invoice", $invoiceStatus['invoice']->id, $voucher?->id);
                }
            }
        }

        if (count($invoiceIds)) {
            $auditMsg .= implode(',', $invoiceIds);
            _auditLogs($auditMsg);
        }

        $notFoundStores = array_unique($notFoundStores);

        if (!empty($notFoundStores)) {
            $store_error = 'Vendor ID(s): ' . implode(', ', $notFoundStores) . ' do not exist. ';
            Toastr::error($store_error . 'Other Invoices Imported Successfully.');
        } else {
            Toastr::success('Invoices imported successfully');
        }

        return back();
    }

    private function processInvoice(array $data, $isFile = false)
    {
        $totalPrice = 0;
        $inv_items = 0;
        foreach ($data['items'] as $item) {
            $totalPrice += _taxIncludedPrice($item['price'], $item['tax'], 'actual') * $item['qty'];
        }
        $store = null;

        // Parse prefixed ID
        $prefix = str($data['store_vendor_id'])->startsWith('vendor_') ? 'vendor_' : 'store_';
        $id = str_replace($prefix, '', $data['store_vendor_id']);

        if ($data['vendor_type'] === 'vendor') {
            $store = StoreCustomer::where('store_id', 0)->find($id);
            $from = 'store_vendor';
        }else{
            $store = Store::withoutGlobalScopes()->where('status', 1)->find($id);
            $from = 'vendor';
        }
        if (!$store) {
            return 'store_not_found';
        }
        $invoice = new ManualInvoice;
        $invoice->invoice_id = $data['invoice_id'] ?? '';
        $invoice->bill_to =  0;
        if($data['vendor_type'] == 'store'){
            $invoice->vendor_id = $data['store_vendor_id'];
        }else{
            $invoice->store_vendor_id = $data['store_vendor_id'];
        }
        $invoice->bill_to_type = $data['bill_to_type'] ?? 'vendor';
        $invoice->user_type = 'store_vendor';
        $invoice->module_id = 6;
        $invoice->total_amount = round($totalPrice);
        $invoice->tax_type = $data['tax_type'];
        $invoice->payment_method = 'Cash';
        $invoice->invoice_date = $data['invoice_date'];
        $invoice->payment_status = $data['payment_stts'];
        $invoice->payment_date = $data['payment_date'];
        $invoice->reminder_date = $data['reminder_date'];
        $invoice->reminder_freq = $data['reminder_freq'];
        $invoice->reminder_freq_unit = $data['reminder_freq_unit'];

        if ($isFile && isset($data['file'])) {
            $extension = $data['file']->getClientOriginalExtension();
            $invoice->reference_file = Helpers::upload('store/docs/', $extension, $data['file']);
        }

        $invoice->save();
        foreach ($data['items'] as $item) {
            $InvoiceItem = new InvoiceItem();
            $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
            $InvoiceItem->name = $item['name'] ?? '';
            $InvoiceItem->price = $item['price'] ?? 0;
            $InvoiceItem->qty = $item['qty'] ?? 1;
            $InvoiceItem->unit = $item['unit'];
            $InvoiceItem->tax = $data['tax_type'] === 'gst' ? ($item['tax'] ?? 0) : 0;
            $InvoiceItem->hsn = $item['hsn'] ?? '';
            $InvoiceItem->inv_id = $item['inv_id'];
            $InvoiceItem->save();

            if ($item['inv_id']) {
                $inv_items++;
            }
        }
        $pdf = _createBillPdf($invoice, $from, null, false, false, 'Reference Purchase Invoice');
        $invoice->update(['pdf' => $pdf['pdf']]);

        // if ($inv_items) {
        //     Helpers::createSupplyOrder($invoice);
        // }
        return ['pdf' => $pdf, 'invoice' => $invoice];
    }
}
