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
use App\Models\Store;
use App\Models\User;
use App\Models\Zone;
use Brian2694\Toastr\Facades\Toastr as FacadesToastr;
use Brian2694\Toastr\Toastr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;

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
            ->where("generated_by", 'admin')->get();
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
        FacadesToastr::success('Invoices Deleted Successfully');
        return back();
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
        FacadesToastr::success('Invoice Deleted Successfully');
        return back();
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
            FacadesToastr::error('Bill to pincode required');
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
            // Optionally log the error: Log::error($th);
            return redirect()->back()->with('error', 'Failed to generate invoice PDF.');
        }
    }
}
