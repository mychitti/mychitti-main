<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Quotation;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Storage;
use App\CentralLogics\Helpers;
use App\Models\InventoryItem;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\QuotationDetail;
use App\Models\QuotationDetailItem;
use App\Models\StoreCustomer;
use App\Models\StoreTask;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\DataSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;


class QuoteController extends Controller
{
    private function getQuoteEmailSettings()
    {
        $setting = DataSetting::where('key', 'quote_email_template')->where('type', 'quotation')->first();
        if ($setting) {
            return json_decode($setting->getRawOriginal('value'), true) ?: [];
        }
        return [];
    }

    public function check_email(Request $request)
    {
        $customerId = $request->get('customer_id');
        if ($customerId) {
            $customer = StoreCustomer::find($customerId);
            if ($customer) {
                return response()->json([
                    'has_email' => !empty($customer->email),
                    'email' => $customer->email,
                    'name' => $customer->f_name . ' ' . ($customer->l_name ?? ''),
                    'phone' => $customer->phone,
                ]);
            }
        }
        return response()->json(['has_email' => false, 'email' => null, 'name' => '', 'phone' => '']);
    }

    public function send_quote_email(Request $request)
    {
        $request->validate([
            'quote_id'      => 'required',
            'email_subject' => 'required|string',
            'email_body'    => 'required|string',
            'requirement_doc' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        // Resolve email: from selected customer or manually entered
        $email = $request->recipient_email;
        $customerId = $request->customer_id;

        // If new email was entered for a customer without email, save it
        if ($customerId && $request->customer_email_new) {
            $customer = StoreCustomer::find($customerId);
            if ($customer) {
                $customer->email = $request->customer_email_new;
                $customer->save();
                $email = $request->customer_email_new;
            }
        }

        if (empty($email)) {
            return response()->json(['success' => false, 'message' => 'Recipient email is required.']);
        }

        $quote = Quotation::with('quote_detail', 'storeCustomer')->find($request->quote_id);
        if (!$quote || !$quote->quote_detail?->pdf) {
            return response()->json(['success' => false, 'message' => 'Quotation or PDF not found.']);
        }

        $store_data = Helpers::get_store_data();
        // Use name from selected customer or from quote's linked customer
        $selectedCustomer = $customerId ? StoreCustomer::find($customerId) : null;
        $clientName = $selectedCustomer
            ? ($selectedCustomer->f_name . ' ' . ($selectedCustomer->l_name ?? ''))
            : ($quote->storeCustomer ? ($quote->storeCustomer->f_name . ' ' . $quote->storeCustomer->l_name) : 'Customer');

        // Replace placeholders
        $greeting   = str_replace(['{client_name}', '{quotation_id}'], [$clientName, $quote->quotation_id], $request->email_greeting ?? 'Dear Customer,');
        $body       = str_replace(['{client_name}', '{quotation_id}'], [$clientName, $quote->quotation_id], $request->email_body);
        $themeColor = $request->theme_color ?? '#a51d1d';
        $templateId = in_array($request->email_template, ['1', '2', '3']) ? $request->email_template : '1';

        // Get quotation items
        $items = QuotationDetailItem::where('quotation_det_id', $quote->quote_detail->id)->get();

        // Store logo
        $logo      = \App\Models\BusinessSetting::where('key', 'logo')->first();
        $storeLogo = $logo ? asset('storage/business/' . $logo->value) : '';

        // Select template view
        $templateView = $templateId == '1' ? 'email-templates.quotation_email'
            : 'email-templates.quotation_email_' . $templateId;

        $emailHtml = view($templateView, [
            'theme_color'    => $themeColor,
            'store_name'     => $store_data->name,
            'store_logo'     => $storeLogo,
            'email_subject'  => $request->email_subject,
            'greeting'       => $greeting,
            'body'           => $body,
            'footer_text'    => $request->email_footer ?? 'Thank you for your business!',
            'quotation_id'   => $quote->quotation_id,
            'quotation_date' => $quote->q_date ? date('d M Y', strtotime($quote->q_date)) : date('d M Y'),
            'items'          => $items,
            'total_amount'   => $quote->quote_detail->total_amount,
        ])->render();

        // Attachment paths
        $pdfPath     = storage_path('app/public/invoice/' . $quote->quote_detail->pdf);
        $reqDocPath  = null;
        $reqDocName  = null;
        if ($request->hasFile('requirement_doc')) {
            $file       = $request->file('requirement_doc');
            $reqDocPath = $file->getPathname();
            $reqDocName = $file->getClientOriginalName();
        }

        try {
            Mail::send([], [], function ($message) use ($email, $request, $emailHtml, $pdfPath, $quote, $reqDocPath, $reqDocName) {
                $message->to($email)
                    ->subject($request->email_subject)
                    ->html($emailHtml);
                if (file_exists($pdfPath)) {
                    $message->attach($pdfPath, [
                        'as'   => 'Quotation_' . $quote->quotation_id . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
                }
                if ($reqDocPath && file_exists($reqDocPath)) {
                    $message->attach($reqDocPath, ['as' => $reqDocName]);
                }
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()]);
        }

        // Save as default if requested
        if ($request->save_as_default) {
            DataSetting::updateOrCreate(
                ['key' => 'quote_email_template', 'type' => 'quotation'],
                ['value' => json_encode([
                    'theme_color'    => $themeColor,
                    'email_template' => $templateId,
                    'greeting'       => $request->email_greeting,
                    'body'           => $request->email_body,
                    'footer'         => $request->email_footer,
                ])]
            );
        }

        return response()->json(['success' => true, 'message' => 'Quotation email sent successfully!']);
    }

    public function send_quote(Request $request, $id)
    {
        $quote = Quotation::find($id);

        $store_data = Helpers::get_store_data();
        $data = [
            'store_name' => $store_data->name,
            'est_price' =>  json_decode($quote->services)->amount[0],
            'final_price' => json_decode($quote->services)->amount[0],
            'item_details' =>  DB::table('items')->where('id', json_decode($quote->services)->service[0])->first(),
            'user_details' => User::find($quote->client_name),
        ];
        $email = User::find($quote->client_name)->email;
        _sendQuotationMail('Recieved New Quotaion',  1, $data, $email);
        Toastr::success('Quotation sent successfully');
        return back();
    }
    public function index(Request $request)
    {
        $from = $request->from ?? date('Y-m-01');
        $to = $request->to ?? date('Y-m-t');
        $formatted_from = $from . ' 00:00:00';
        $formatted_to = $to . ' 23:59:59';
        $status = $request->status ?? 'All';
        $storeId = 0;

        if ($status == 'All') {
            $quotes = Quotation::with('quote_detail', 'client')->where('vendor_id', $storeId)->whereBetween('created_at', [$formatted_from, $formatted_to])
                ->paginate(config('default_pagination'));
        } else {
            $quotes = Quotation::with('quote_detail', 'client')->where('vendor_id', $storeId)->whereBetween('created_at', [$formatted_from, $formatted_to])
                ->where('status', $status)->paginate(config('default_pagination'));
        }
        $quote_email_settings = $this->getQuoteEmailSettings();
        return view('admin-views.quote.index', compact('quotes', 'from', 'to', 'status', 'quote_email_settings'));
    }
    public function convert_to_bill(Request $request, $quote_id)
    {
        $quotation = '';
        $quotation_det =  QuotationDetail::where('quotation_id', $quote_id)->first();
        $quotation = Quotation::find($quote_id);
        if ($quotation) {
            $invoice = new ManualInvoice();
            $invoice->invoice_id =  Helpers::generateInvoiceIdAdmin(6); // M = manual
            $invoice->vendor_id = 0;
            $invoice->bill_to =  $quotation_det->bill_to;
            $invoice->bill_to_type = $quotation_det->bill_to_type;
            $invoice->user_type = $quotation_det->user_type;
            $invoice->module_id = $quotation_det->module_id;
            $invoice->total_amount = $quotation_det->total_amount;
            $invoice->tax_type = $quotation_det->total_amount;
            $invoice->payment_method = $quotation_det->payment_method;
            $invoice->payment_status = $quotation_det->payment_status;
            $invoice->payment_date = now();
            $invoice->save();

            $items = QuotationDetailItem::where('quotation_det_id', $quotation_det->id)->get();

            foreach ($items as $key => $item) {
                $InvoiceItem = new InvoiceItem();
                $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
                $InvoiceItem->manual_invoice_id = $invoice->id;
                $InvoiceItem->name = $item->name;
                $InvoiceItem->qty = $item->qty;
                $InvoiceItem->price = $item->price;
                $InvoiceItem->unit = $item->unit;
                $InvoiceItem->tax = $item->tax;
                $InvoiceItem->hsn = '';
                $InvoiceItem->save();
            }
            // $quotation_det->delete();
            // $items->each->delete();
        }

        $data = _createBillPdf($invoice, 'admin');
        $invoice->update(['pdf' => $data['pdf']]);

        $quotation->status = 'Converted to Bill';
        $quotation->save();
        try {
            return redirect($data['url']);
        } catch (\Throwable $th) {
            //
        }
    }
    public function new(Request $request)
    {
        $v_id = 0;
        $quotes = Quotation::where('vendor_id', $v_id)->where('status', 'New')->paginate(config('default_pagination'));
        return view('admin-views.quote.index', compact('quotes'));
    }
    public function accepted(Request $request)
    {
        $v_id = 0;
        $quotes = Quotation::where('vendor_id', $v_id)->where('status', 'Accepted')->paginate(config('default_pagination'));
        return view('admin-views.quote.index', compact('quotes'));
    }
    public function declined(Request $request)
    {
        $v_id = 0;
        $quotes = Quotation::where('vendor_id', $v_id)->where('status', 'Declined')->paginate(config('default_pagination'));
        return view('admin-views.quote.index', compact('quotes'));
    }
    public function manage(Request $request, $id)
    {
        $quote = Quotation::with('storeCustomer')->where('id', $id)->first();
        $quote_det = QuotationDetail::where('quotation_id', $quote->id)->first();
        $quote_items =  QuotationDetailItem::where('quotation_det_id', $quote_det->id)->get();
        // prx($quote_items);
        $storeId = 0;
        $customers = User::where('added_by', 0)->get();
        $services = DB::table('items')
            ->where('status', '1')
            ->where(function ($query) use ($storeId) {
                $query->where('store_id', $storeId)
                    ->orWhereRaw("FIND_IN_SET(?, store_ids)", [$storeId]);
            })
            ->get();
        // prx( $quote);
        $quote_email_settings = $this->getQuoteEmailSettings();
        return view('admin-views.quote.manage', compact('quote_items', 'quote', 'services', 'customers', 'quote_email_settings'));
    }

    public function status_change(Request $request)
    {

        $id = $request->post('lead_id');
        $status = $request->post('status');

        $query =  Quotation::where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        // echo $query;
        return back();
    }
    public function delete(Request $request, $id)
    {

        $query =  Quotation::find($id)
            ->delete();
        return back();
    }

    public function add()
    {
        $store_id = 0;
        $customers = StoreCustomer::where('store_id', $store_id)->get();
        $storeId = 0;
        $inventory_items = InventoryItem::where('store_id', $store_id)->get();
        $services = DB::table('items')
            ->where('status', '1')
            ->where(function ($query) use ($storeId) {
                $query->where('store_id', $storeId)
                    ->orWhereRaw("FIND_IN_SET(?, store_ids)", [$storeId]);
            })
            ->get();
        return view('admin-views.quote.add', compact('inventory_items',  'services', 'customers'));
    }
    public function quotation_number_validation(Request $request)
    {
        $exists = Quotation::where('vendor_id', 0)->where("quotation_id", $request->number)->exists();
        if ($exists) {
            return response()->json(['status' => false, 'msg' => 'This quotation number already used']);
        } else {
            return response()->json(['status' => true, 'msg' => 'ok']);
        }
    }
    public function save_info(Request $request, $task_id = null)
    {
        // prx($request->all());
        $id = $request->post('quote_id');
        $task_id = $task_id ?? null; // if not set
        if (!$task_id) {
            $rules = [
                'item_qty_new.*'   => 'required',
                'item_price_new.*' => 'required',
            ];
            if (!$id) {
                $rules['bill_to'] = 'required|max:100';
            }

            $validator = Validator::make($request->all(), $rules, [
                'bill_to.required' => 'Client is required',
            ]);
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
        }
        // ================= PREPARE ARRAYS =================
        $service_names = [];
        $item_id       = [];
        $item_type     = [];

        // Inputs
        $inventory_input = $request->post('invoice_item_id', []); // from request
        $item_names      = $request->post('item_name_new', []);

        // Loop through both: inventory items & manual new items
        $max = max(count($inventory_input), count($item_names));

        for ($i = 0; $i < $max; $i++) {
            if (!empty($inventory_input[$i])) {
                $df = DB::table('inventory_items')->where('id', $inventory_input[$i])->first();
                $service_names[$i] = $df ? $df->item_name : null;
                $item_type[$i]     = 'inv_item';
                $item_id[$i]       = $df ? $df->id : null;
            } elseif (!empty($item_names[$i])) {
                $service_names[$i] = $item_names[$i];
                $item_type[$i]     = 'custom';
                $item_id[$i]       = null;
            }
        }

        // ================= BUILD DATA JSON =================
        $data['service']       = $item_id;
        $data['item_type']     = $item_type;
        $data['service_name']  = $service_names;
        $data['unit']          = $request->post('item_unit_new', []);
        $data['qty']           = $request->post('item_qty_new', []);
        $data['amount']        = $request->post('item_price_new', []);
        $data['tax']           = $request->post('item_tax_new', []);
        $data['hsn']           = $request->post('item_hsn_new', []);

        $service_data = json_encode($data);

        // =============    QUOTATION ID VALIDATION ================= 
        if ($request->has('quotation_id')) {
            $exists = Quotation::where('vendor_id', 0)->where("quotation_id", $request->quotation_id)->exists();
            if ($exists) {
                Toastr::error('This quotation id already used');
                return back();
            }
        }

        // ================= SAVE QUOTE =================
        $id = $request->post('quote_id'); // new identifier
        if (empty($id)) {
            $quote = new Quotation;
            $quote->created_at = now();
            $quote->quotation_id  =  $request->quotation_id ?? Helpers::quoteId();
        } else {
            $quote = Quotation::find($id);
        }
        // prx($quote);
        if ($task_id ?? null) {
            $quote->task_id = $task_id;
        }
        if ($task_id) {
            $quoteExists =  Quotation::where('task_id', $task_id)->exists();
            if ($quoteExists) {
                Toastr::error('Quotation for this task already exists');
                return back();
            }
            $task = StoreTask::find($task_id);
        }

        $quote->vendor_id   = 0;
        $quote->subject     = $request->post('subject');
        $quote->status      = $request->post('status');
        $quote->q_date      = $request->post('invoice_date');
        $quote->exp_date    = $request->post('reminder_date');
        $quote->remarks     = $request->post('remarks');
        $quote->client_name = $request->post('bill_to') ?? $request->customer;
        $quote->services    = $service_data;
        $quote->created_by  =  auth('admin')->id();
        $quote->tax_type    = $request->tax_type;

        empty($id) ? $quote->save() : $quote->update();

        // ================= CALCULATE TOTAL =================
        $totalAmount = 0;
        if ($request->has('item_price_new')) {
            $itemPrices = $request->post('item_price_new', []);
            $itemQtys   = $request->post('item_qty_new', []);
            $itemTaxes  = $request->post('item_tax_new', []);

            foreach ($itemPrices as $key => $price) {
                $qty  = $itemQtys[$key] ?? 1;
                $tax  = $itemTaxes[$key] ?? 0;

                $lineAmount = ($price ?? 0) * $qty;

                // add tax percentage
                $lineAmountWithTax = $lineAmount + ($lineAmount * ($tax / 100));

                $totalAmount += $lineAmountWithTax;
            }
        }
        if ($request->has('item_price')) {
            $itemPrices = $request->post('item_price', []);
            $itemQtys   = $request->post('item_qty', []);
            $itemTaxes  = $request->post('item_tax', []);

            foreach ($itemPrices as $key => $price) {
                $qty  = $itemQtys[$key] ?? 1;
                $tax  = $itemTaxes[$key] ?? 0;

                $lineAmount = ($price ?? 0) * $qty;

                // add tax percentage
                $lineAmountWithTax = $lineAmount + ($lineAmount * ($tax / 100));

                $totalAmount += $lineAmountWithTax;
            }
        }
        // prx($totalAmount);

        // pdf =====================================
        $quotation_det = QuotationDetail::where('quotation_id', $quote->id)->first();
        if ($id && $quotation_det) {
            $quotation_det->total_amount = $totalAmount;
            $quotation_det->task_id = $task_id ?? null;
            $quotation_det->save();

            //delete items if already exists
            $items = QuotationDetailItem::where('quotation_det_id', $quotation_det->id)->get();
            $items->each->delete();

            if (Storage::disk('public')->exists('invoice/' . $quotation_det->pdf)) {
                Storage::disk('public')->delete('invoice/' . $quotation_det->pdf);
            }
        } else {
            $quotation_det = new QuotationDetail();
            $quotation_det->vendor_id = 0;
            $quotation_det->task_id = $task_id ?? null;
            $quotation_det->quotation_id = $quote->id;
            $quotation_det->bill_to = isset($task) ? $task->user_id : ($request->post('bill_to') ?? $request->customer);
            $bill_to_type = $request->post('bill_to_type', 'user');
            if ($bill_to_type === 'mychitti_client') {
                $userTypeInfo = StoreCustomer::find($quotation_det->bill_to)->user_type ?? 'customer';
                $user_type = $userTypeInfo === 'customer' ? 'store_user' : 'store_vendor';
            } elseif ($bill_to_type === 'vendor') {
                $user_type = 'store_vendor';
            } else {
                $user_type = 'user';
            }
            $quotation_det->user_type = $user_type;
            $quotation_det->bill_to_type = $bill_to_type;
            $quotation_det->module_id = Config::get('module.current_module_id');
            $quotation_det->total_amount = $totalAmount;
            $quotation_det->tax_type = $request->tax_type;
            $quotation_det->payment_method = 'Cash';
            $quotation_det->payment_status = $request->payment_stts;
            $quotation_det->save();
        }
            // prx($quotation_det);
        ;        // prx($request->all());
        // existing items
        foreach ($request->post('item_name', []) as $key => $name) {
            $quotation_det_item = new QuotationDetailItem();
            $quotation_det_item->quotation_det_id = $quotation_det->id;
            $quotation_det_item->name = $request->item_name[$key];
            $quotation_det_item->item_id = $request->invoice_item[$key] ?? null;
            $quotation_det_item->qty = $request->item_qty[$key];
            $quotation_det_item->price = $request->item_price[$key];
            $quotation_det_item->unit =   $request->item_unit[$key] ?? null;
            $quotation_det_item->tax = $request->item_tax[$key] ? $request->item_tax[$key] :  0;
            $quotation_det_item->hsn = $request->item_hsn[$key];
            $quotation_det_item->save();
        }
        foreach ($request->post('item_name_new', []) as $key => $name) {
            $quotation_det_item = new QuotationDetailItem();
            $quotation_det_item->quotation_det_id = $quotation_det->id;
            $quotation_det_item->name = $request->item_name_new[$key];
            $quotation_det_item->item_id = $request->invoice_item_new[$key] ?? null;
            $quotation_det_item->qty = $request->item_qty_new[$key];
            $quotation_det_item->price = $request->item_price_new[$key];
            $quotation_det_item->unit = $request->item_unit_new[$key] ?? null;
            $quotation_det_item->tax = $request->item_tax_new[$key] ? $request->item_tax_new[$key] :  0;
            $quotation_det_item->hsn = $request->item_hsn_new[$key];
            $quotation_det_item->save();
        }


        // prx( $quotation_det_item);
        $data = _createBillPdf($quotation_det, 'admin', null, false, true, '');
        $quotation_det->update(['pdf' => $data['pdf']]);
        $quote->update(['pdf' => $data['pdf']]);

        if ($task_id && $request->resp_type != 'redirect') {
            // return ['success' => true, 'file_name' => $data['pdf'],  'url'  => $data['url']];
        }
        try {
            return redirect($data['url']);
        } catch (\Throwable $th) {
            //
        }
        Toastr::success('Quotation Information saved successfully');

        return back();
    }
}
