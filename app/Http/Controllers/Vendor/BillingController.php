<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Enums\ExportFileNames\Admin\Category;
use App\Http\Controllers\Controller;
use App\Library\Payer;
use App\Models\BusinessSetting;
use App\Exports\InvoiceExport;
use App\Models\ManualInvoice;
use App\Models\Store;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Library\Payment as PaymentInfo;
use App\Library\Receiver;
use App\Models\AccountDetail;
use App\Models\Category as ModelsCategory;
use App\Models\InventoryItem;
use App\Models\InvoiceItem;
use App\Models\ManualTrial; 
use App\Models\ServiceInvoice;
use App\Models\StorageUnit;
use App\Models\StoreCustomer;
use App\Models\StoreLedgerEntry;
use App\Models\StoreSignature;
use App\Models\StoreTnc;
use App\Models\StoreVoucher;
use App\Models\TaxRate;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorEmployee;
use App\Traits\Payment;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class BillingController extends Controller
{

  public function pay_bill(Request $request, $invoice_id)
  {
    $invoice = ManualInvoice::find($invoice_id);
    return view('vendor-views.billing.payment_details', compact('invoice'));
  }
  public function getInvoiceIdForDate(Request $request)
  {
    $date  = $request->date ? \Carbon\Carbon::parse($request->date) : now();
    $store = Helpers::get_store_data();

    // Determine FY for the selected date
    $fyYear             = $date->month >= 4 ? $date->year : $date->year - 1;
    $financialYearStart = \Carbon\Carbon::createFromDate($fyYear, 4, 1);
    $financialYearEnd   = $financialYearStart->copy()->addYear()->subDay();
    $fy                 = $financialYearStart->format('y') . '-' . $financialYearEnd->format('y');

    $store_prefix  = substr(strtoupper(preg_replace('/[^A-Za-z]/', '', $store->name)), 0, 3);
    $prefix        = $store_prefix . '_M_' . $fy . '_';
    $nongst_prefix = $store_prefix . '_';

    // Determine current FY to decide whether to use store counter or scan invoices
    $currentFyYear  = now()->month >= 4 ? now()->year : now()->year - 1;
    $currentFy      = (\Carbon\Carbon::createFromDate($currentFyYear, 4, 1)->format('y')) . '-' .
                      (\Carbon\Carbon::createFromDate($currentFyYear + 1, 4, 1)->subDay()->format('y'));

    if ($fy === $currentFy) {
        // Current FY — use store counters directly (source of truth)
        $nextSerial   = (int) $store->bill_serial_number;
        $nonGstSerial = (int) $store->non_gst_sno;
    } else {
        // Past FY — scan invoices to find last used serial
        $lastInvoice = \App\Models\ManualInvoice::where('vendor_id', $store->id)
            ->where('invoice_id', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(invoice_id, "_", -1) AS UNSIGNED) DESC')
            ->first();

        $nextSerial = $lastInvoice
            ? ((int) substr($lastInvoice->invoice_id, strrpos($lastInvoice->invoice_id, '_') + 1)) + 1
            : 1;

        $lastService = \App\Models\ServiceInvoice::where('vendor_id', $store->id)
            ->where('invoice_id', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(invoice_id, "_", -1) AS UNSIGNED) DESC')
            ->first();

        if ($lastService) {
            $nextSerial = max($nextSerial, (int) substr($lastService->invoice_id, strrpos($lastService->invoice_id, '_') + 1) + 1);
        }

        $lastNonGstManual = \App\Models\ManualInvoice::where('vendor_id', $store->id)
            ->where('invoice_id', 'like', $nongst_prefix . '%')
            ->where('invoice_id', 'not like', $store_prefix . '_M_%')
            ->whereBetween('created_at', [$financialYearStart, $financialYearEnd])
            ->orderByRaw('CAST(SUBSTRING_INDEX(invoice_id, "_", -1) AS UNSIGNED) DESC')
            ->first();

        $lastNonGstService = \App\Models\ServiceInvoice::where('vendor_id', $store->id)
            ->where('invoice_id', 'like', $nongst_prefix . '%')
            ->where('invoice_id', 'not like', $store_prefix . '_M_%')
            ->whereBetween('created_at', [$financialYearStart, $financialYearEnd])
            ->orderByRaw('CAST(SUBSTRING_INDEX(invoice_id, "_", -1) AS UNSIGNED) DESC')
            ->first();

        $nonGstSerial = 1;
        if ($lastNonGstManual) {
            $nonGstSerial = max($nonGstSerial, (int) substr($lastNonGstManual->invoice_id, strrpos($lastNonGstManual->invoice_id, '_') + 1) + 1);
        }
        if ($lastNonGstService) {
            $nonGstSerial = max($nonGstSerial, (int) substr($lastNonGstService->invoice_id, strrpos($lastNonGstService->invoice_id, '_') + 1) + 1);
        }
    }

    return response()->json([
        'prefix'        => $prefix,
        'number'        => $nextSerial,
        'fy'            => $fy,
        'non_gst_serial' => $nonGstSerial,
    ]);
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
    // prx($request->all());
    $storeId = Helpers::get_store_id();

    $selected_items = json_decode($request->selected_items);

    if ($request->type == 'selected') {
      if (!is_array($selected_items) || empty($selected_items)) {
        Toastr::error('No invoice IDs selected for export.');
        return back();
      }

      $manualInvoices = ManualInvoice::with(['invoiceItems', 'storeCustomer'])
        ->where('vendor_id', $storeId)
        ->where('type', 'manual')
        ->whereIn('id', $selected_items)
        ->get();

      $serviceInvoices = ServiceInvoice::with(['invoiceItems', 'storeCustomer'])
        ->where('vendor_id', $storeId)
        ->whereIn('id', $selected_items)
        ->get();

      $invoices = $manualInvoices->merge($serviceInvoices);
    } else {
      $today = date('Y-m-d');
      $preset = $request->date_range ?? 'last_30_days';
      $custom = $request->custom_date_range ?? null;
      $range = Helpers::calculatePresetDates($preset, $custom);
      $from = $range['start'];
      $to = $range['end'];

      $status = $request->status ?? 'all';
      $search = $request->search;

      $applyFilters = function ($query) use ($search, $status, $from, $to, $today) {
        if ($search) {
          $query->where('invoice_id', 'like', "%{$search}%");
        } else {
          if ($status === 'overdue') {
            $query->where('payment_status', 'Unpaid')->where('created_at', '<', $today)->whereBetween('created_at', [$from, $to]);
          } elseif ($status === 'pending') {
            $query->where('payment_status', 'Unpaid')->where('created_at', '>=', $today)->whereBetween('created_at', [$from, $to]);
          } elseif ($status === 'credit') {
            $query->where('payment_status', 'Paid')->whereBetween('created_at', [$from, $to]);
          } else {
            $query->where(function ($q) use ($from, $to) {
              $q->whereBetween('created_at', [$from, $to])->orWhereNull('created_at');
            });
          }
        }
      };

      $manualQuery = ManualInvoice::with(['invoiceItems', 'storeCustomer'])
        ->where('vendor_id', $storeId)
        ->where('type', 'manual');
      $applyFilters($manualQuery);

      $serviceQuery = ServiceInvoice::with(['invoiceItems', 'storeCustomer'])
        ->where('vendor_id', $storeId);
      $applyFilters($serviceQuery);

      $invoices = $manualQuery->get()->merge($serviceQuery->get());
    }

    $invoices = $invoices->unique('invoice_id')->values();

    if ($invoices->isEmpty()) {
      Toastr::error('No invoices found.');
      return back();
    }

    $sheetData = [];
    $header = [
      'Temp Group',
      'Invoice Number',
      'Invoice Date',
      'Client Name',
      'Client ID',
      'Client Phone',
      'Payment Status',
      'Payment Date',
      'Total Amount',
      'Tax Type',
      'Reminder Date',
      'Reminder Frequency',
      'Reminder Frequency Unit',
      'Item Name',
      'Item Price',
      'Tax Percent',
      'HSN',
      'Qty',
    ];
    $headings = $header;
    $sheetData = [];
    $tempIndex = 1;

    foreach ($invoices as $invoice) {
      $client = StoreCustomer::find($invoice->bill_to);
      $phone = $client->phone ?? '';
      $clientId = $client->id ?? '';
      $clientName = trim(($client->f_name ?? '') . ' ' . ($client->l_name ?? ''));

      foreach ($invoice->invoiceItems as $item) {
        $sheetData[] = [
          "GROUP_" . $tempIndex,
          $invoice->invoice_id,
          Carbon::parse($invoice->invoice_date)->format('d-m-Y'),
          $clientName,
          $clientId,
          $phone,
          $invoice->payment_status,
          $invoice->payment_date ? Carbon::parse($invoice->payment_date)->format('d-m-Y') : '',
          $invoice->total_amount ?? '',
          $invoice->tax_type,
          $invoice->reminder_date ? Carbon::parse($invoice->reminder_date)->format('d-m-Y') : '',
          $invoice->reminder_freq,
          $invoice->reminder_freq_unit,
          $item->name,
          $item->price,
          $item->tax,
          $item->hsn,
          $item->qty,
        ];
      }

      $tempIndex++;
    }
    return Excel::download(new InvoiceExport($sheetData, $headings), 'invoices_' . now()->format('Y_m_d_H_i_s') . '.xlsx');
  }

  public function importInvoiceSheet(Request $request)
  {
    $store = Helpers::get_store_data();
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
        $user = StoreCustomer::where('id', $firstRow['client id'])->where('store_id', Helpers::get_store_id())->first();
        $user_to_find = $firstRow['client id'];
      } elseif (!empty($firstRow['client phone'])) {
        $user = StoreCustomer::where('phone', $firstRow['client phone'])->where('store_id', Helpers::get_store_id())->first();
        $user_to_find  = $firstRow['client phone'];
      }

      if ($user) {
        $bill_to_id = $user->id;
        $user_type = $user->user_type === 'customer' ? 'store_user' : 'store_vendor';
      } else {
        array_push($notFoundCustomer, $user_to_find);
        continue; // skip this group if no user found
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
        $bill_number = Helpers::generateInvoiceId('M', $update = true);
        $invoice_serial = substr($bill_number, strrpos($bill_number, '_') + 1);

        if (($firstRow['tax type'] ?? '') == 'gst') {
          $prefix = substr($bill_number, 0, strrpos($bill_number, '_') + 1);
          $invoice_number = $prefix . $invoice_serial;
        } else {
          $prefix = Helpers::_storePrefix($store->name);
          $invoice_number = $prefix . $invoice_serial;
        }
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
      $invoice->vendor_id           = Helpers::get_store_id();
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
      $invoice->total_amount =  $totalPrice;
      $invoice->save();

      $data = _createBillPdf($invoice, 'vendor');
      $invoice->update(['pdf' => $data['pdf']]);

      //ledger entry 
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
      $voucher = _masterLedgerEntry($data, $credit_account, $debit_account, 'customer', 'store', null);

      if ($request->payment_stts == 'Paid') {
        _saveDayBookEntry($totalPrice, 'credit', Helpers::get_store_id(), "Sales Invoice", $invoice->id, $voucher?->id);
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

    // Try to convert if it's a numeric date (Excel format)
    if (is_numeric($value)) {
      return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
    }

    // If it's already a date string
    return date('Y-m-d', strtotime($value));
  }
  public function make_payment(Request $request, $invoice_id)
  {
    $invoice = ManualInvoice::find($invoice_id);
    if ($invoice->payment_status == 'Paid') {
      Toastr::success('Bill Already Paid');
      return back();
    }
    $amount = $invoice->total_amount;
    $store =  Store::find(Helpers::get_vendor_id());
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
      payer_id: Helpers::get_store_id(),
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
    // prx($invoice);
    $invoice_items = InvoiceItem::where('rand_invoice_id', $invoice->invoice_id)->get();

    $customers = User::where('status', 1)->where('added_by', Helpers::get_store_id())->get();

    return view('vendor-views.billing.service_invoice_edit', compact('customers', 'invoice_items', 'invoice'));
  }
  public function edit(Request $request, $invoice_id)
  {
    $invoice = ManualInvoice::with('storeCustomer')->where('id', $invoice_id)->first();

    $invoice_items = InvoiceItem::where('rand_invoice_id', $invoice->invoice_id)->get();

    $customers = User::where('status', 1)->where('added_by', Helpers::get_store_id())->get();

    return view('vendor-views.billing.invoice_edit', compact('customers', 'invoice_items', 'invoice'));
  }
  public function update_invoice(Request $request)
  {
    $request->validate(
      [
        'bill_to' => 'required',
        'item_name' => 'required|array|min:1',
        'item_name.*' => 'required|string|max:255', // each item must be a string
      ],
      ['item_name.min' => 'Atleast 1 item is required']
    );

    $totalPrice = 0;
    foreach ($request->item_price as $key => $price) {
      $totalPrice += _taxIncludedPrice($price, $request->item_tax[$key] ?? 0, 'actual') * $request->item_qty[$key];
    }
    if (isset($request->item_price_new)) {
      foreach ($request->item_price_new as $key => $price) {
        $gstStatus = $request->item_gst_status_new[$key] ?? 'excluding';
        $qty = $request->item_qty_new[$key];
        $tax = $request->item_tax_new[$key] ?? 0;
        $totalPrice += $gstStatus === 'including'
          ? $price * $qty
          : _taxIncludedPrice($price, $tax, 'actual') * $qty;
      }
    }

    $invoice = ManualInvoice::where('invoice_id', $request->invoice_id)->first();

    if (Storage::disk('public')->exists('invoice/' . $invoice->pdf)) {
      Storage::disk('public')->delete('invoice/' . $invoice->pdf);
    }

    $invoice->bill_to = $request->bill_to;
    $invoice->total_amount = $totalPrice;
    $invoice->tax_type =  $request->tax_type;
    $invoice->payment_status =  $request->payment_stts;
    $invoice->payment_date =  $request->payment_date ?? date('Y-m-d');
    $invoice->reminder_date =  $request->reminder_date ?? date('Y-m-d');
    $invoice->reminder_freq =  $request->reminder_freq ?? 1;
    $invoice->reminder_freq_unit =  $request->reminder_freq_unit ?? 'week';
    $invoice->save();

    $invoice_items = InvoiceItem::where('rand_invoice_id', $invoice->invoice_id)->get();
    $invoice_items->each->delete(); // delete old

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

    if (isset($request->item_name_new)) { // insert new items to invoice
      foreach ($request->item_name_new as $key => $id) {
        $InvoiceItem = new InvoiceItem();
        $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
        $InvoiceItem->name = $request->item_name_new[$key]; 
        $InvoiceItem->price = $request->item_price_new[$key];
        $InvoiceItem->qty = $request->item_qty_new[$key];
        $InvoiceItem->unit = $request->item_unit_new[$key];
        $InvoiceItem->tax = $request->tax_type == 'gst' ?  ($request->item_tax_new[$key] ?? 0) : 0;
        $InvoiceItem->hsn = $request->item_hsn_new[$key];
        $InvoiceItem->gst_status = $request->item_gst_status_new[$key] ?? 'excluding';
        $InvoiceItem->save();
      }
    }

    // voucher update
    $voucher = StoreVoucher::where('invoice_id', 'manual-' . $invoice->id)->first();
    if ($voucher) {
      $voucher->status =  $invoice->payment_status == 'Paid' ? 'approved' : 'pending';
      $voucher->completed_at = $invoice->payment_status == 'Paid' ?  now() : null;
      $voucher->total_amount = $totalPrice;
      $voucher->save();

      // ledger entries update 
      $ledger_entries = StoreLedgerEntry::where('voucher_id', $voucher->id)->get();
      foreach ($ledger_entries as $key => $value) {
        $value->status =  $invoice->payment_status == 'Paid' ? 'approved' : 'pending';
        $value->completed_at =  $invoice->payment_status == 'Paid' ?  now() : null;
        $value->credit =  $value->credit > 0 ? $totalPrice : 0;
        $value->debit =  $value->debit > 0 ? $totalPrice : 0;
        $value->save();
      }
    }
    _auditLogs('Edited Invoice : ' . $invoice->invoice_id);

    $data = _createBillPdf($invoice, 'vendor');
    $invoice->update(['pdf' => $data['pdf']]);
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
    _auditLogs('Deleted Invoice : ' . $invoice->invoice_id);
    InvoiceItem::where('rand_invoice_id', $invoice_id)->delete();
    return redirect()->back();
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
        $gstStatus = $request->item_gst_status_new[$key] ?? 'excluding';
        $qty = $request->item_qty_new[$key];
        $tax = $request->item_tax_new[$key] ?? 0;
        $totalPrice += $gstStatus === 'including'
          ? $price * $qty
          : _taxIncludedPrice($price, $tax, 'actual') * $qty;
      }
    }

    $invoice = ServiceInvoice::where('invoice_id', $request->invoice_id)->first();

    if (Storage::disk('public')->exists('invoice/' . $invoice->pdf)) {
      Storage::disk('public')->delete('invoice/' . $invoice->pdf);
    }
    $invoice->total_amount = $totalPrice;
    // $invoice->tax_type =  $request->tax_type;
    $invoice->payment_status =  $request->payment_stts;
    $invoice->payment_date =  $request->payment_date ?? date('Y-m-d');
    $invoice->reminder_date =  $request->reminder_date ?? date('Y-m-d');
    $invoice->reminder_freq =  $request->reminder_freq ?? 1;
    $invoice->reminder_freq_unit =  $request->reminder_freq_unit ?? 'week';
    $invoice->save();

    $invoice_items = InvoiceItem::where('rand_invoice_id', $invoice->invoice_id)->get();
    $invoice_items->each->delete(); // delete old

    if (isset($request->item_name)) {

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

    if (isset($request->item_name_new)) { // insert new items to invoice
      foreach ($request->item_name_new as $key => $id) {
        $InvoiceItem = new InvoiceItem();
        $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
        $InvoiceItem->name = $request->item_name_new[$key];
        $InvoiceItem->price = $request->item_price_new[$key];
        $InvoiceItem->qty = $request->item_qty_new[$key];
        $InvoiceItem->unit = $request->item_unit_new[$key];
        $InvoiceItem->tax = $request->tax_type == 'gst' ?  ($request->item_tax_new[$key] ?? 0) : 0;
        $InvoiceItem->hsn = $request->item_hsn_new[$key];
        $InvoiceItem->gst_status = $request->item_gst_status_new[$key] ?? 'excluding';
        $InvoiceItem->save();
      }
    }
    $data = _createBillPdf($invoice, 'vendor');
    $invoice->pdf = $data['pdf'];
    $invoice->save();
    try {
      return redirect($data['url']);
    } catch (\Throwable $th) {
      //
    }
  }
  public function create_invoice(Request $request)
  {
    $store = Helpers::get_store_data();
    $store_id = $store->id;

    $categories = ModelsCategory::where(['position' => 0])->module(Helpers::get_store_data()->module_id)->get();
    $storage_units = StorageUnit::with('parent')->where('store_id', $store_id)->get();
    $singatures = StoreSignature::with('employee')->where('store_id', $store_id)->where('type', 'invoice')->orderBy('staff_id', 'asc')->get();
    $accounts = AccountDetail::where('user_type', 'vendor')->where('type', 'invoice')->where('user_id', $store_id)->get();
    $tAndCContent = DB::table('vendor_terms_conditions')->where('type', 'for_customer')->where('vendor_id', $store_id)->first();
    $upcoming_bill_number = Helpers::generateInvoiceId('M', $update = false); // only get .. not update
    $bill_number = $upcoming_bill_number; // Example: 'PJS_M_25-26_82'
    $lastUnderscorePos = strrpos($bill_number, '_');
    $bill_num['prefix'] = substr($bill_number, 0, strrpos($bill_number, '_') + 1); // 'PJS_M_25-26_'
    $bill_num['nongst_prefix'] = Helpers::_storePrefix($store->name);
    $bill_num['number'] = substr($bill_number, strrpos($bill_number, '_') + 1);    // '82'
    $data['tax_rates_tcs'] = TaxRate::where('type', 'TCS')->get();
    $data['tax_rates_tds'] = TaxRate::where('type', 'TDS')->get();
    $staffs = VendorEmployee::where('store_id', $store_id)->get();
    $data['store_tncs'] = StoreTnc::where('store_id', $store_id)->where('tnc_type', 'invoice')->get();
    // Keep incrementing until unique
    do {
      $invoice_num = $bill_num['prefix'] . $bill_num['number'];
      $exists = ManualInvoice::where('invoice_id', $invoice_num)->exists();
      if ($exists) {
        $bill_num['number']++;
      }
    } while ($exists);
    $customers = StoreCustomer::where('store_id', Helpers::get_store_id())->get();
    return view('vendor-views.billing.create_invoice', compact('categories', 'storage_units', 'staffs', 'data', 'singatures', 'accounts', 'tAndCContent', 'store', 'customers', 'bill_num'));
  }
  private function processInvoice(array $data, $isFile = false)
  {

    $totalPrice = 0;
    $inv_items = 0;
    foreach ($data['items'] as $item) {
      $totalPrice += _taxIncludedPrice($item['price'], $item['tax'], 'actual') * $item['qty'];
    }
    $store = null;
    if ($data['store_vendor_id']) {
      $store = StoreCustomer::where('id', $data['store_vendor_id'])->where('store_id', Helpers::get_store_id())->first();
    }
    if (!$store) {
      return 'store_not_found';
    }
    // $invoice_id = Helpers::generateInvoiceId('M', true, null, $data['tax_type'], $store);
    // $prefixe = _getInvoicePrefix($data['tax_type'], $store);

    $invoice = new ManualInvoice;
    $invoice->invoice_id = $data['invoice_id'] ?? '';
    $invoice->store_vendor_id = $data['store_vendor_id'];
    // $invoice->vendor_id =  $data['bill_from'];
    $invoice->bill_to = Helpers::get_store_id();
    $invoice->bill_to_type = 'vendor';
    $invoice->user_type = 'store_vendor';
    $invoice->module_id = Helpers::get_store_data()->module_id;
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

      // prx($InvoiceItem);

      if ($item['inv_id']) {
        $inv_items++;
      }
    }
    $pdf = _createBillPdf($invoice, 'store_vendor', null, false, false, 'Reference Purchase Invoice');
    $invoice->update(['pdf' => $pdf['pdf']]);

    // Inventory order 
    if ($inv_items) {
      Helpers::createSupplyOrder($invoice);
    }
    return ['pdf' => $pdf, 'invoice' => $invoice];
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

      // Group by Temp Group instead of invoice_no
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
      $invoiceStatus =  $this->processInvoice($data);
      if ($invoiceStatus == 'store_not_found') {
        array_push($notFoundStores, $data['store_vendor_id']);
      } else {
        // ledger entry
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
        $voucher = _masterLedgerEntry($data, $credit_account, $debit_account, 'store', 'other', null);
        if ($request->payment_stts == 'Paid') {
          _saveDayBookEntry($totalPrice, 'debit', Helpers::get_store_id(), "Purchase Invoice", $invoiceStatus['invoice']->id, $voucher?->id);
        }
      }
    }
    if (count($invoiceIds)) {
      $auditMsg .= implode(',', $invoiceIds);
      _auditLogs($auditMsg);
    }

    $notFoundStores = array_unique($notFoundStores);
    $store_error = 'Vendor ID(s): ' . implode(', ', $notFoundStores) . ' do not exist. ';

    if (!empty($notFoundStores)) {
      Toastr::error($store_error . 'Other Invoices Imported Successfully.');
    } else {
      Toastr::success('Invoices imported successfully');
    }

    return back();
  }

  public function save_purchase_invoice(Request $request)
  {
    if ($request->payment_stts == 'Unpaid') {
      $request->validate([
        'payment_date' => 'required',
        'reminder_date' => 'required',
      ]);
    }

    $request->validate([
      'bill_from' => 'required',
      'file' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf,doc,docx',
    ], ['bill_from.required' => 'Seller field is required']);

    $exists = ManualInvoice::where("invoice_id", $request->invoice_id)->exists();
    if ($exists) {
      Toastr::error('Invoice Id Already Exists');
      return back();
    }
    // prx($request->all());
    $items = [];
    foreach ($request->invoice_item_new as $key => $id) {
      $inventory_item = InventoryItem::where('item_name', $request->item_name_new[$key])->where('store_id', Helpers::get_store_id())->first();
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

      // INCREMENT INVENTORY STOCK (purchase invoice adds stock)
      if ($request->inventory_item_id[$key]) {
        _incrementInventoryStock($request->inventory_item_id[$key], $request->item_qty_new[$key], ($request->has('item_unit_new.' . $key) ? $request->item_unit_new[$key] : null));
      }
    }

    $data = [
      'store_vendor_id' => $request->bill_from,
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

    $invoiceData = $this->processInvoice($data, true);

    if ($invoiceData != 'store_note_found') {

      // master ledger entry 
      $totalPrice = 0;
      foreach ($items as $item) {
        $totalPrice += _taxIncludedPrice($item['price'], $item['tax'], 'actual') * $item['qty'];
      }

      $customer = StoreCustomer::find($request->bill_from);
      $debit_account = Helpers::ensurePurchaseAccount('Purchase Bill');
      $credit_account = Helpers::ensureCustomerLedger($customer);
      $data = [
        'date' => now(),
        'amount' => $totalPrice,
        'invoice_id' => 'manual-' . $invoiceData['invoice']->id,
        'voucher_type' => 'Purchase',
        'status' => $request->payment_stts == 'Paid' ? 'approved' : 'pending',
        'description' => 'Purchase Invoice',
      ];
      $voucher = _masterLedgerEntry($data, $credit_account, $debit_account, 'store', 'other', null);
      if ($request->payment_stts == 'Paid') {
        _saveDayBookEntry($totalPrice, 'debit', Helpers::get_store_id(), "Purchase Invoice", $invoiceData['invoice']->id, $voucher?->id);
      }
      _auditLogs('Created Purchase Invoice : ' . $invoiceData['invoice']->invoice_id);

      $docData = Helpers::generateInventoryGatepass($invoiceData['invoice'], (object)[], 'purchase');
    }

    if (!is_array($invoiceData)) {
      Toastr::error($invoiceData);
      return back();
    }
    // prx($invoiceData);
    return redirect($invoiceData['pdf']['url']);
  }


  public function  get_invoices_by_vendor(Request $request)
  {
    $storeId = Helpers::get_store_id();
    $vendor_id = $request->vendor_id;
    $invoices = ManualInvoice::where('bill_to', $storeId)->where('store_vendor_id', $vendor_id)->where('bill_to_type', 'vendor')->pluck('invoice_id');
    return response()->json(['status' => true, 'invoices' => $invoices]);
  }
  public function  save_new_manual_invoice(Request $request)
  {
    // prx($request->all());
    $request->validate([
      'bill_to' => 'required',
      'item_name_new.*' => 'required',
    ], [
      'bill_to.required' => 'Customer field is required',
      'item_name_new.*.required' => 'Item Name is required',
    ]);
    // Items
    // if (is_serial_number_used($request->invoice_serial, $request->prefixe,  $request->tax_type)) {
    //   Toastr::error("Serial Number Already Used");
    //   return  back();
    // }

    // match amount
    if ($request->payment_stts == 'Paid' && $request->payment_mode == 'Cash and Online') {
      if ($request->inv_total_amount != $request->cash_amount + $request->online_amount) {
        Toastr::error("Amount Mismatched");
        return  back();
      }
    }

    $totalItemsAmount = 0;
    $totalItemsTax = 0;
    $inv_items = 0;

    foreach ($request->item_name_new as $key => $name) {
      $InvoiceItem = new InvoiceItem();
      $InvoiceItem->inventory_item_id = $request->inventory_item_id[$key];
      $InvoiceItem->name = $request->item_name_new[$key] ?? '';
      $InvoiceItem->qty = $quantity = $request->item_qty_new[$key] ?? 1;
      $InvoiceItem->price = $unitPrice = $request->item_price_new[$key] ?? 0;
      $InvoiceItem->unit = $request->has('item_unit_new.' . $key) ? $request->item_unit_new[$key] : null;
      $InvoiceItem->tax = $taxPercent = $request->tax_type == 'gst' ?  ($request->item_tax_new[$key] ?? 0) : 0;
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

    // additional charges
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
      $taxPercent =  (float) $taxes[$index] ?? 0;
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
        'tax' => $request->tax_type == 'gst' ?  ($taxes[$index] ?? 0) : 0,
        'status' => $statuses[$index] ?? 'included',
      ];
    }

    // additional charges end

    // Discount
    $discountValue = (float) $request->total_discount;
    $discountType = $request->total_discount_type; // 'percent' or 'amount'

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

    $userTypeInfo = StoreCustomer::find($request->bill_to)->user_type;

    if ($request->has('number')) {
      $invoice_id = Helpers::generateInvoiceId('M', $update = true, $serial_num = $request->number, $tax_type = $request->tax_type); // M = manual
    } else {
      $invoice_id = Helpers::generateInvoiceId('M', $update = true, $serial_num = null,  $tax_type = $request->tax_type); // M = manual
    }

    // custom header labels 
    $labels =  $request->header_label ?? [];  // or your source
    $fields = $request->header_field ?? [];

    $other_headers = [];

    foreach ($labels as $index => $label) {
      $field = $fields[$index] ?? null;
      $other_headers[$label] = $field;
    }

    $invoice = new ManualInvoice;
    $invoice->invoice_id = $invoice_id;
    $invoice->vendor_id = Helpers::get_store_id();
    $invoice->bill_to = $request->bill_to;
    $invoice->bill_to_type = 'user';
    $invoice->user_type = $userTypeInfo == 'customer' ? 'store_user' : 'store_vendor';
    $invoice->module_id =  Helpers::get_store_data()->module_id;
    $invoice->tax_type =  $request->tax_type;
    $invoice->payment_method = $request->payment_mode;
    $invoice->payment_mode = $request->payment_mode;
    $invoice->invoice_date = $request->invoice_date;
    $invoice->payment_status =  $request->payment_stts;
    $invoice->payment_date =  $request->payment_date;
    $invoice->reminder_date =  $request->reminder_date;
    $invoice->reminder_freq =  $request->reminder_freq;
    $invoice->reminder_freq_unit =  $request->reminder_freq_unit;



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
      $item->rand_invoice_id = $invoice->invoice_id; // assign foreign key if needed
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
    $customer = StoreCustomer::where('store_id', Helpers::get_store_id())
      ->where('id', $request->bill_to)
      ->first();

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
      'store',
      null
    );
    if ($request->payment_stts == 'Paid') {
      _saveDayBookEntry($total_amount, 'debit', Helpers::get_store_id(), "Sales Invoice", $invoice->id, $voucher?->id);
    }

    $data = _createBillPdf($invoice, 'vendor');
    $invoice->update(['pdf' => $data['pdf']]);
    _auditLogs('Created Bill (Advanced) : ' . $invoice->invoice_id);

    try {
      if ($request->form_type == 'ajax') {
        return response()->json(['status' => true, 'msg' => "Added  Successfully"]);
      } else {
        if (hasPermission('billing', 'view')) {
          return redirect()->route('vendor.invoice.view-invoice', ['invoice_id' => $invoice->id]);
        } else {
          Toastr::success('Invoice created successfully');
          return back();
        }
      }
    } catch (\Throwable $th) {
      //
    }
  }
  public function purchase_bill_delete(Request $request, $id)
  {
    $invoice = ManualInvoice::where('id', $id)->first();
    if ($invoice) {
      if (Storage::disk('public')->exists('invoice/' . $invoice->pdf)) {
        Storage::disk('public')->delete('invoice/' . $invoice->pdf);
      }
      $invoice->delete();
      Toastr::success('Invoice deleted successfully');
    } else {
      Toastr::error('Invoice not found');
    }
    _auditLogs('Deleted Purchase Invoice : ' . $invoice->invoice_id);
    InvoiceItem::where('rand_invoice_id', $invoice->invoice_id)->delete();
    return redirect()->back();
  }
  public function view_invoice(Request $request, $id)
  {
    $invoice = ManualInvoice::find($id);
    return view('vendor-views.billing.view_invoice', compact('invoice'));
  }
}
