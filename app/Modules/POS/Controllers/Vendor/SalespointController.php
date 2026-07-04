<?php

namespace App\Modules\POS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Exports\POSItemsExport;
use App\Exports\POSReportExport;
use App\Exports\TokenExport;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessTokenPostSave;
use App\Imports\InvItemImport;
use App\Imports\POSInvItemImport;
use App\Imports\POSItemImport;
use App\Models\Account;
use App\Models\AccountDetail;
use App\Models\AccountOption;
use App\Models\Branch;
use App\Models\BranchInventoryItem;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\ManualInvoice;
use App\Models\OrderType;
use App\Models\PosToken;
use App\Models\PosTokenItem;
use App\Models\RestaurantTable;
use App\Models\Store;
use App\Models\StoreConfig;
use App\Models\StoreCustomer;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use Faker\Extension\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SalespointController extends Controller
{
    public function dashboard(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $today = date('Y-m-d');
        $preset = request('date_range') ?? 'today';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  =  $range['start'];
        $formatted_to =  $range['end'];
        $query = PosToken::with(['tokenItems.inventoryItem'])->where('store_id', $storeId)->whereBetween('created_at', [$formatted_from, $formatted_to]);

        $data['totalSales'] = (clone $query)->whereNot('payment_status', 'cancelled')->sum('total');
        $data['totalSales'] = (clone $query)->whereNot('payment_status', 'cancelled')->sum('total');
        $data['cancelledSales'] = (clone $query)->where('payment_status', 'cancelled')->sum('total');
        $data['avgValue'] = (clone $query)->whereNot('payment_status', 'cancelled')->avg('total');
        $data['tokensSold'] = (clone $query)->whereNot('payment_status', 'cancelled')->count();
        $data['tokensCancelled'] = (clone $query)->where('payment_status', 'cancelled')->count();
        $data['branches'] = Branch::where('store_id', $storeId)->get();

        foreach ($data['branches'] as $key => $branch) {
            $branch->totalSales = (clone $query)->where('branch_id', $branch->id)->whereNot('payment_status', 'cancelled')->sum('total');
            $branch->cancelledSales = (clone $query)->where('branch_id', $branch->id)->where('payment_status', 'cancelled')->sum('total');
            $branch->tokensSold = (clone $query)->where('branch_id', $branch->id)->whereNot('payment_status', 'cancelled')->count();
            $branch->tokensCancelled = (clone $query)->where('branch_id', $branch->id)->where('payment_status', 'cancelled')->count();
            $branch->avgValue = (clone $query)->where('branch_id', $branch->id)->whereNot('payment_status', 'cancelled')->avg('total');
            $branch->totalSalesPercent = $data['totalSales'] > 0  ? round(($branch->totalSales / $data['totalSales']) * 100, 2) : 0;
            $branch->totalGSTAmount =  (clone $query)->where('branch_id', $branch->id)->whereNot('payment_status', 'cancelled')->sum('gst_amount');

            $branch->topSellingItems = DB::table('pos_token_items as ti')
                ->join('pos_tokens as pt', 'pt.id', '=', 'ti.token_id')
                ->leftJoin('inventory_items as ii', 'ii.id', '=', 'ti.item_id')
                ->select(
                    'ti.item_id',
                    'ii.item_name',
                    'ii.image',
                    DB::raw('SUM(ti.qty) as total_qty'),
                    DB::raw('SUM(ti.item_total) as total_sales')
                )
                ->whereNot('pt.payment_status', 'cancelled')
                ->where('pt.store_id', $storeId)
                ->where('pt.branch_id', $branch->id)
                ->whereBetween('pt.created_at', [$formatted_from, $formatted_to])
                ->groupBy('ti.item_id', 'ii.item_name')
                ->orderByDesc('total_qty')
                ->limit(7)
                ->get();
        }

        $colors = _posDashboardColors();
        $sales = Helpers::pos_calendar_sales($request->branch_cl ?? null);
        return view('pos::vendor.salespoint.dashboard', compact('colors', 'data', 'preset', 'custom', 'sales'));
    }

    public function mark_paid(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'nullable|in:cash,card,upi',
            'transaction_reference' => 'nullable|string|max:255',
        ]);
        if ($request->payment_method === 'upi') {
            $request->validate(['transaction_reference' => 'required|string|max:255']);
        }

        $token = PosToken::findOrFail($id);
        $token->payment_status = 'paid';
        $token->paid_at = NOW();
        if ($request->filled('payment_method')) {
            $token->payment_method = $request->payment_method;
            $token->transaction_reference = $request->payment_method === 'upi' ? $request->transaction_reference : null;
        }
        $token->save();

        $store_id = Helpers::get_store_id();
        $store_config = StoreConfig::where('store_id', $store_id)->first();
        if (!$store_config || $store_config->pos_daybook_entry == 'token_generate') {
            _saveDayBookEntry($token->total, 'credit', $store_id, "POS Token", null);
        }
        if (!$store_config || $store_config->pos_account_entry == 'token_generate') {
            $account = new Account();
            $account->store_id = $store_id;
            $account->account_type = 'vendor';
            $account->user_type_id = $store_id;
            $account->user_type = 'vendor';
            $account->date = date('Y-m-d');
            $account->type = 'income';
            $account->status = 'completed';
            $account->payment_mode = 'cash';
            $account->category = 'POS Token';
            $account->description = 'POS Token Sales';
            $account->purpose = 'Sales';
            $account->gst_amount = 0;
            $account->ledger_account_type = 9;
            $account->amount = $token->total;
            $account->save();
        }
        if ($token->pdf) {
            Helpers::delete_file('store/tokens/', $token->pdf);
        }

        $data = Helpers::generatePOSToken($token, 're-generate');

        Toastr::success('Marked As Paid Successfully');
        return back();
    }

    public function convert_to_bill(Request $request, $id, $redirect = true)
    {
        $store = Helpers::get_store_data();
        $token = PosToken::with('tokenItems', 'client')->findOrFail($id);

        $invoice_id = Helpers::generateInvoiceId('M', $update = true, $serial_num = null, $tax_type = $token->gst_type, $store ?? null);
        $bill_to_type = $token->client ? ($token->client?->user_type == 'vendor' ? 'vendor' : 'user') : 'user';
        $user_type = $token->client?->user_type == 'vendor' ? 'store_vendor' : 'store_user';

        $totalAmount = 0;
        $add_charges[] = [
            'name' => 'delivery_charges',
            'amount' => $token->delivery ?? 0,
            'calc_amount' => $token->delivery ?? 0,
            'tax' => 0,
            'status' => 'included',
        ];
        $invoice = new ManualInvoice();
        $invoice->invoice_id = $invoice_id;
        $invoice->invoice_serial = (int) substr($invoice_id, strrpos($invoice_id, '_') + 1);
        $invoice->financial_year = _currentFinancialYear();
        $invoice->vendor_id = $store->id;
        $invoice->bill_to = $token->customer_id;
        $invoice->bill_to_type = $bill_to_type;
        $invoice->user_type = $user_type;
        $invoice->module_id = $store->module_id;
        $invoice->total_amount = $token->total;
        $invoice->payment_method = $token->payment_method;
        $invoice->payment_status = ucfirst($token->payment_status);
        $invoice->tax_type = $token->gst_type;
        $invoice->order_type = $token->order_from;
        $invoice->token_number = $token->token_number;
        $invoice->payment_date = $token->payment_status == 'paid' ? $token->created_at->format('Y-m-d') : null;
        $invoice->additional_charges = json_encode($add_charges);
        $invoice->customer_name = $token->customer_id == 0 ? 'Walk-in Customer' : null;
        $invoice->delivery_charges = $token->delivery ?? 0;
        $invoice->coupon_amount = $token->coupon ?? 0;
        $invoice->discount_amount = $token->discount ?? 0;
        $invoice->save();

        foreach ($token->tokenItems as $itemData) {
            $InvoiceItem = new InvoiceItem();
            $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
            $InvoiceItem->manual_invoice_id = $invoice->id;
            $InvoiceItem->name = $itemData['item_name'];
            $InvoiceItem->qty = $itemData['qty'];
            $InvoiceItem->price = $itemData['unit_price'];
            $InvoiceItem->tax = $itemData['gst_percent'];
            $InvoiceItem->hsn = '';
            $InvoiceItem->save();
        }
        $token->invoice_table_id = $invoice->id;
        $token->save();

        $customer = StoreCustomer::find($token->customer_id);
        if ($customer) {
            $credit_account = Helpers::ensureSalesAccount();
            $debit_account = Helpers::ensureCustomerLedger($customer);
            $data = [
                'date' => now(),
                'amount' => $token->total,
                'invoice_id' => 'manual-' . $invoice->id,
                'voucher_type' => 'Sales',
                'status' => $token->payment_status == 'paid' ? 'approved' : 'pending',
                'description' => 'Sales Invoice',
            ];

            _masterLedgerEntry($data, $credit_account, $debit_account, 'customer', 'store', null, null);
        }

        try {
            $data = _createBillPdf($invoice, 'vendor');
            $invoice->update(['pdf' => $data['pdf']]);
            if ($redirect) {
                return redirect($data['url']);
            } else {
                return $invoice;
            }
        } catch (\Throwable $th) {
            Toastr::error('Some error occured');
            return back();
        }
    }

    public function token(Request $request)
    {
        $store_id = Helpers::get_store_id();

        $inventoryItems = DB::table('inventory_items as ii')
            ->join('branch_inventory_item as bi', 'ii.id', '=', 'bi.inventory_item_id')
            ->join('branches as b', 'bi.branch_id', '=', 'b.id')
            ->where('ii.store_id', Helpers::get_store_id());

        $deliveryConfig = StoreConfig::where('store_id', $store_id)
            ->select('delivery_charges_gst_pos', 'delivery_charges_gst_percent')
            ->first();
        $delivery_gst['percent'] = $deliveryConfig?->delivery_charges_gst_percent ?? 0;
        $delivery_gst['status'] = $deliveryConfig?->delivery_charges_gst_pos ?? 0;

        if ($request->has('branch')) {
            $branch_id = $request->branch ?? 0;
        } else {
            $branch = Branch::where('store_id', $store_id)->first();
            $branch_id = $branch ? $branch->id : 0;
        }
        $inventoryItems->where('bi.branch_id', $branch_id);

        if ($request->filled('category')) {
            $inventoryItems->where('ii.category_id', $request->category);
        }

        $inventoryItems = $inventoryItems->select(
            'ii.id',
            'ii.item_name as name',
            'ii.image',
            'ii.model_number',
            'ii.sku_id',
            'ii.brand',
            'ii.category_id',
            'bi.branch_id',
            'b.name as branch_name',
            'b.type as branch_type',
            'bi.price',
            'bi.qty_left',
            'bi.qty',
            DB::raw("'Inventory Item' as item_type")
        )->get();

        $branchWiseItems = $inventoryItems->groupBy('branch_name')->map(function ($items) {
            return $items->values();
        });

        $store_config = StoreConfig::where('store_id', $store_id)->first();

        $defaultDesign = 3;

        if ($store_config && $store_config->pos_menu_design) {
            $designId = $store_config->pos_menu_design;
            $designCssId = ($designId == 4) ? $defaultDesign : $designId;
        } else {
            $designId = $defaultDesign;
            $designCssId = $defaultDesign;
        }

        $data['design_id'] = $designId;
        $data['design']    = "/token_generate_{$designCssId}.css";
        $data['category_position'] = $store_config?->category_position ?? 'dropdown';

        $data['branches'] = Branch::where('store_id', Helpers::get_store_id())->get();
        $data['order_from'] = OrderType::where('store_id', Helpers::get_store_id())->get();
        $data['branchWiseItems'] = $branchWiseItems;
        $data['tables'] = RestaurantTable::where('store_id', $store_id)->with('openOrder')
            ->orderBy('name')
            ->get();

        $posItemCategoryIds = DB::table('inventory_items')->where('store_id', $store_id)->distinct()->pluck('category_id');
        $data['categories'] = Category::whereIn('id', $posItemCategoryIds)->get();
        $data['selected_category'] = $request->category_id;

        $data['waiters'] = VendorEmployee::where('store_id', $store_id)->get();
        $data['payment_accounts'] = AccountDetail::where('user_type', 'vendor')
            ->where('user_id', $store_id)
            ->where('type', 'pos')
            ->orderByDesc('id')
            ->get();
        $last_generated_amount = DB::table('pos_tokens')->where('store_id', $store_id)->whereNot('payment_status', 'cancelled')->latest()->value('total');

        return view('pos::vendor.salespoint.index', compact('delivery_gst', 'data', 'branch_id', 'store_config', 'last_generated_amount'));
    }

    public function calendar(Request $request)
    {
        $sales = Helpers::pos_calendar();
        return view('pos::vendor.salespoint.calendar', compact('sales'));
    }

    public function token_delete(Request $request, $id)
    {
        PosToken::where('id', $id)->delete();
        Toastr::success("Deleted Successfully");
        return back();
    }

    public function token_generate(Request $request)
    {
        $store_id = Helpers::get_store_id();

        $request->validate([
            'payment_method' => 'required|in:cash,bank,upi,cash_online',
            'payment_detail_id_bank' => 'nullable|integer',
            'payment_detail_id_upi' => 'nullable|integer',
            'cash_amount' => 'nullable|numeric|min:0',
            'online_amount' => 'nullable|numeric|min:0',
        ]);
        if ($request->payment_method === 'bank') {
            $request->validate(['payment_detail_id_bank' => 'required|integer']);
        } elseif ($request->payment_method === 'upi') {
            $request->validate(['payment_detail_id_upi' => 'required|integer']);
        }

        $branch_id = $request->branch_id ?? 0;
        $branch = Branch::where('store_id', $store_id)->where('id', $request->branch_id)->first();
        $daybook_entry = false;
        $config = StoreConfig::where('store_id', Helpers::get_store_id())->first();
        $addr_type = $config ? $config->address_on_token : 'branch';
        $gst_type = 'non-gst';
        if ($addr_type == 'branch') {
            $branchDet = Branch::find($branch_id);
            if ($branchDet) {
                $addr = $branchDet->address;
                $seller_name = $branchDet->name;
                $GST = $branchDet->gst_number;
                $gst_type = $branchDet->gst_number ? 'gst' : 'non-gst';
            }
        } else {
            $store = Helpers::get_store_data();
            $addr = $store->address;
            $seller_name = $store->name;

            if ($store->gst && json_decode($store->gst)->status) {
                $GST = $store->gst;
                $gst_type = 'gst';
            }
        }
        $token_id = $request->token_id ?? null;
        $tkn = $request->token_prefix ?? null;

        if ($token_id) {
            $pos_token = PosToken::with('tokenItems')->findOrFail($token_id);
        } else {
            $pos_token = new PosToken();
            $pos_token->token_number = Helpers::generatePOSTokenNumber($tkn);
            $pos_token->serial_number = Helpers::_nextTokenNumber();
        }

        $pos_token->store_id = $store_id;
        $pos_token->branch_id = $branch_id;
        $pos_token->staff_id = auth('vendor_employee')->check() ? auth('vendor_employee')->id() : 0;
        $pos_token->customer_id = $request->customer_id;

        $pos_token->payment_method = $request->payment_method;
        $selectedBankId = null;
        $selectedUpiId = null;
        if ($request->payment_method === 'bank') {
            $selectedBankId = AccountDetail::where('id', $request->payment_detail_id_bank)
                ->where('user_type', 'vendor')
                ->where('user_id', $store_id)
                ->where('type', 'pos')
                ->where('payment_type', 'bank')
                ->value('id');
        } elseif ($request->payment_method === 'upi') {
            $selectedUpiId = AccountDetail::where('id', $request->payment_detail_id_upi)
                ->where('user_type', 'vendor')
                ->where('user_id', $store_id)
                ->where('type', 'pos')
                ->where('payment_type', 'upi')
                ->value('id');
        }
        $pos_token->bank_account_id = $selectedBankId;
        $pos_token->upi_account_id = $selectedUpiId;
        if ($request->payment_method === 'cash_online') {
            $pos_token->cash_amount = $request->cash_amount;
            $pos_token->online_amount = $request->online_amount;
        } else {
            $pos_token->cash_amount = null;
            $pos_token->online_amount = null;
        }
        $pos_token->payment_status = $request->payment_status;
        $pos_token->order_from = $request->order_from;
        $pos_token->order_type = $request->order_type ?? 'take_away';
        $pos_token->gst_type = $gst_type ?? 'non-gst';
        $pos_token->paid_at = $request->payment_status == 'paid' ? NOW() : null;
        $pos_token->token_type = $request->token_type ?? 'both';

        $pos_token->order_status = 'close';

        $pos_token->address = $addr ?? '';
        $pos_token->seller_name = $seller_name ?? '';
        $pos_token->address_type = $addr_type ?? '';
        $pos_token->gst_number = $GST ?? '';
        $pos_token->save();

        if ($request->has('token_id')) {
            RestaurantTable::where('id', $pos_token->table_id)
                ->where('store_id', $store_id)
                ->update([
                    'status' => 'free'
                ]);
        }

        $subtotal = 0;
        $total = 0;
        $gstAmountTotal = 0;

        if ($request->token_id) {
            PosTokenItem::where('token_id', $request->token_id)->delete();
        }

        $itemIds = $request->item_id;
        $branch_id = $request->branch_id ?? null;
        $branchItems = DB::table('branch_inventory_item')
            ->where('branch_id', $branch_id)
            ->whereIn('inventory_item_id', $itemIds)
            ->get()
            ->keyBy('inventory_item_id');
        $inventoryNames = InventoryItem::whereIn('id', $itemIds)->pluck('item_name', 'id');

        $lowStockItems = [];

        foreach ($request->item_id as $key => $item_id) {
            $qty = (float) $request->item_qty[$key];

            $itemRow = $branchItems[$item_id] ?? null;
            if (!$itemRow) continue;

            $priceRow = $itemRow->price;
            $gst_percent = $itemRow->gst_percent;

            $gstAmount = ($priceRow * $gst_percent) / (100 + $gst_percent);
            $basePrice = $priceRow - $gstAmount;

            $item_name = $inventoryNames[$item_id] ?? '';

            $unit_price = (float) $basePrice;
            $item_total = $qty * $unit_price;
            $subtotal += $item_total;

            $gstAmountTotal += $gstAmount * $qty;

            $token_item = new PosTokenItem();
            $token_item->token_id = $pos_token->id;
            $token_item->item_id = $item_id;
            $token_item->item_name = $item_name;
            $token_item->item_total = $item_total;
            $token_item->unit_price = $unit_price;
            $token_item->qty = $qty;
            $token_item->gst_percent = $gst_percent;
            $token_item->gst_status = 'included';
            $token_item->gst_amount = $gstAmount;
            $token_item->save();

            if (!$request->token_id) {
                $qty_left = $itemRow->qty_left - $token_item->qty;

                if ($qty_left >= 0) {
                    DB::table('branch_inventory_item')
                        ->where('branch_id', $branch_id)
                        ->where('inventory_item_id', $item_id)
                        ->update(['qty_left' => $qty_left]);
                }
                if ($qty_left < 5) {
                    $lowStockItems[] = ['item' => $itemRow, 'qty_left' => $qty_left];
                }
            }
        }

        $total = $subtotal + $gstAmountTotal;

        $pos_token->subtotal = $subtotal;
        $pos_token->gst_amount = $gstAmountTotal;

        $pos_token->coupon_type = $request->coupon_type ?? 'amount';
        $pos_token->discount_type = $request->discount_type ?? 'amount';

        if ($request->coupon > 0) {
            if ($pos_token->coupon_type === "percent") {
                $coupon_amount = ($total * $request->coupon / 100);
            } else {
                $coupon_amount = $request->coupon;
            }
        } else {
            $coupon_amount = 0;
        }
        if ($request->discount > 0) {
            if ($pos_token->discount_type === "percent") {
                $discount_amount = ($total * $request->discount / 100);
            } else {
                $discount_amount = $request->discount;
            }
        } else {
            $discount_amount = 0;
        }
        $pos_token->coupon = $coupon_amount;
        $pos_token->discount = $discount_amount;

        $store_id = Helpers::get_store_id();
        $store_config = StoreConfig::where('store_id', $store_id)->first();

        $delivery_gst_percent = $store_config?->delivery_charges_gst_percent ?? 0;
        $delivery_gst_status = $store_config?->delivery_charges_gst_pos ?? 0;

        $delivery_charge = $request->delivery;
        $delivery_gst_amount = 0;

        if ($delivery_gst_percent > 0 && $delivery_gst_status == 1) {
            $delivery_gst_amount = ($delivery_charge * $delivery_gst_percent) / 100;
        }
        $total_delivery = $delivery_charge + $delivery_gst_amount;
        $pos_token->delivery = $total_delivery;
        $pos_token->base_delivery = $request->delivery;
        $pos_token->delivery_gst_amt = $delivery_gst_amount;
        $pos_token->total = $total - $coupon_amount - $discount_amount + $total_delivery;

        $pos_token->save();

        if ($request->payment_status == 'paid') {
            if (!$store_config || $store_config->pos_daybook_entry == 'token_generate') {
                $daybook_entry = true;
            }
        }

        $shouldCreateAccount = $request->payment_status == 'paid' && (!$store_config || $store_config->pos_account_entry == 'token_generate');
        ProcessTokenPostSave::dispatch($store_id, $pos_token->total, $request->payment_status, $lowStockItems, $shouldCreateAccount);

        $token = PosToken::with('tokenItems')
            ->where('id', $pos_token->id)
            ->first();

        $store = Helpers::get_store_data();
        $store_config = StoreConfig::where('store_id', $store->id)->first();
        $template_id = $store_config && $store_config->pos_token_template ? $store_config->pos_token_template : 1;

        $print_html_array = [];
        if ($token->token_type == 'token' || $token->token_type == 'both') {
            $kitchen = false;
            $print_html_array['main'] = \Illuminate\Support\Facades\View::make('document_templates/pos_token/token_' . $template_id, compact('store', 'store_config', 'token', 'kitchen'))->render();
        }
        if ($token->token_type == 'kitchen' || $token->token_type == 'both') {
            $kitchen = true;
            $print_html_array['kitchen'] = \Illuminate\Support\Facades\View::make('document_templates/pos_token/token_' . $template_id, compact('store', 'store_config', 'token', 'kitchen'))->render();
        }

        if (class_exists('\Barryvdh\Debugbar\Facades\Debugbar')) {
            \Barryvdh\Debugbar\Facades\Debugbar::disable();
        }

        $data = Helpers::generatePOSToken($token);

        if ($store_config->auto_invoice_pos ?? 0) {
            $invoice = $this->convert_to_bill($request, $token->id, false);
            if ($invoice) {
                $customer = StoreCustomer::find($invoice->bill_to);
                $credit_account = Helpers::ensureSalesAccount();
                if ($customer) {
                    $debit_account = Helpers::ensureCustomerLedger($customer);
                } else {
                    $debit_account = Helpers::ensureOtherBankAccount();
                }
                $data2 = [
                    'date' => now(),
                    'amount' => $invoice->total_amount,
                    'voucher_type' => 'Sales',
                    'invoice_id' => 'manual-' . $invoice->id,
                    'status' => $invoice->payment_status == 'Paid' ? 'approved' : 'pending',
                    'description' => 'Sales Invoice',
                ];
                $voucher = _masterLedgerEntry($data2, $credit_account, $debit_account, 'customer', 'store', null);
                if ($daybook_entry) {
                    _saveDayBookEntry($invoice->total_amount, 'credit', Helpers::get_store_id(), "Sales Invoice", $invoice->id, $voucher?->id);
                }
            }
        }

        if ($request->ajax()) {
            if (isset($data['success']) && $data['success']) {
                return response()->json([
                    'success' => true,
                    'pdf_url' => $data['url'],
                    'print_html_array' => $print_html_array,
                ]);
            }
            return response()->json(['success' => false, 'message' => 'Failed to generate POS token'], 500);
        }

        session()->flash('clear_cart', true);
        if (isset($data['success']) && $data['success']) {
            return redirect()->back()->with([
                'pdf_url' => $data['url'],
                'print_html_array' => $print_html_array
            ]);
        } else {
            Toastr::error("Failed to generate POS token");
            return back();
        }
    }

    public function openTable(Request $request)
    {
        $request->validate([
            'table_id' => 'required',
            'item_id' => 'required|array',
            'item_qty' => 'required|array',
            'item_type' => 'required|array',
        ]);

        $store_id = Helpers::get_store_id();
        if (auth('vendor')->check()) {
            $branch_id = $request->branch_id ?? null;
        } else {
            $branch_id = Helpers::get_loggedin_user()->branch_id ?? 0;
        }

        $table = RestaurantTable::where('id', $request->table_id)
            ->where('store_id', $store_id)
            ->firstOrFail();

        $token = PosToken::where('store_id', $store_id)
            ->where('branch_id', $branch_id)
            ->where('table_id', $table->id)
            ->where('order_type', 'dine_in')
            ->where('order_status', 'open')
            ->first();

        if ($token) {
            return response()->json([
                'status'   => 'open',
                'order_id' => $token->id
            ]);
        }

        $table->status = 'occupied';
        $table->save();

        $token = new PosToken();
        $token->store_id      = $store_id;
        $token->branch_id     = $branch_id;
        $token->table_id      = $table->id;
        $token->waiter_id     = $request->waiter_id ?? null;
        $token->order_type    = 'dine_in';
        $token->order_from    = $request->order_from ?? 'pos';
        $token->order_status  = 'open';
        $token->payment_status = 'unpaid';
        $token->token_number  = Helpers::generatePOSTokenNumber(null);
        $token->serial_number = Helpers::_nextTokenNumber();
        $token->save();

        $subtotal = 0;
        $gstAmountTotal = 0;

        foreach ($request->item_id as $key => $item_id) {
            $type = $request->item_type[$key];
            $qty  = (float) $request->item_qty[$key];

            $itemQuery = DB::table('inventory_items as ii')
                ->join('branch_inventory_item as bi', 'ii.id', '=', 'bi.inventory_item_id')
                ->join('branches as b', 'bi.branch_id', '=', 'b.id')
                ->where('bi.inventory_item_id', $item_id);

            $current_branch_id = auth('vendor')->check() ? ($request->branch_id ?? $branch_id) : $branch_id;
            $itemQuery->where('b.id', $current_branch_id);

            $itemRow = (clone $itemQuery)
                ->select('bi.*')
                ->first();

            if (!$itemRow) continue;

            $priceRow    = $itemRow->price;
            $gst_percent = $itemRow->gst_percent;

            $gstAmount  = ($priceRow * $gst_percent) / (100 + $gst_percent);
            $basePrice  = $priceRow - $gstAmount;

            $item_det  = InventoryItem::find($item_id);
            $item_name = $item_det ? $item_det->item_name : '';

            $unit_price  = (float) $basePrice;
            $item_total  = $qty * $unit_price;
            $subtotal   += $item_total;
            $gstAmountTotal += $gstAmount * $qty;

            $token_item = new PosTokenItem();
            $token_item->token_id     = $token->id;
            $token_item->item_id      = $item_id;
            $token_item->item_name    = $item_name;
            $token_item->item_total   = $item_total;
            $token_item->unit_price   = $unit_price;
            $token_item->qty          = $qty;
            $token_item->gst_percent  = $gst_percent;
            $token_item->gst_status   = 'included';
            $token_item->gst_amount   = $gstAmount;
            $token_item->save();

            $qty_left = $itemRow->qty_left - $qty;
            if ($qty_left >= 0) {
                $itemQuery->update(['bi.qty_left' => $qty_left]);
            }
            if ($qty_left < 5) {
                _notifLowStock($itemRow, $qty_left);
            }
        }

        $token->subtotal   = $subtotal;
        $token->gst_amount = $gstAmountTotal;
        $token->total      = $subtotal + $gstAmountTotal;
        $token->save();

        return response()->json([
            'status'   => 'open',
            'order_id' => $token->id
        ]);
    }

    public function tableState(Request $request)
    {
        $store_id = Helpers::get_store_id();

        $table = RestaurantTable::where('id', $request->table_id)
            ->where('store_id', $store_id)
            ->firstOrFail();

        $token = PosToken::with('tokenItems')
            ->where('store_id', $store_id)
            ->where('table_id', $table->id)
            ->where('order_type', 'dine_in')
            ->where('order_status', 'open')
            ->first();

        if (!$token) {
            return response()->json([
                'has_order' => false
            ]);
        }

        return response()->json([
            'has_order' => true,
            'order'     => $token,
        ]);
    }

    public function updateDineOrder(Request $request)
    {
        $store_id = Helpers::get_store_id();

        $token = PosToken::where('id', $request->token_id)
            ->where('store_id', $store_id)
            ->where('order_status', 'open')
            ->firstOrFail();

        DB::beginTransaction();

        try {
            $oldItems = PosTokenItem::where('token_id', $token->id)->get();

            foreach ($oldItems as $old) {
                DB::table('branch_inventory_item')
                    ->where('inventory_item_id', $old->item_id)
                    ->where('branch_id', $token->branch_id)
                    ->increment('qty_left', $old->qty);
            }

            PosTokenItem::where('token_id', $token->id)->delete();

            $subtotal = 0;
            $gstAmountTotal = 0;

            foreach ($request->item_id as $key => $item_id) {
                $type = $request->item_type[$key];
                $qty = (float) $request->item_qty[$key];

                $itemQuery = DB::table('inventory_items as ii')
                    ->join('branch_inventory_item as bi', 'ii.id', '=', 'bi.inventory_item_id')
                    ->join('branches as b', 'bi.branch_id', '=', 'b.id')
                    ->where('bi.inventory_item_id', $item_id);

                if (auth('vendor')->check()) {
                    $branch_id = $request->branch_id ?? null;
                } else {
                    $branch_id = Helpers::get_loggedin_user()->branch_id;
                }
                $itemQuery->where('b.id', $branch_id);

                $itemRow = (clone $itemQuery)
                    ->select('bi.*')
                    ->first();

                $priceRow = $itemRow->price;
                $gst_percent = $itemRow->gst_percent;

                $gstAmount = ($priceRow * $gst_percent) / (100 + $gst_percent);
                $basePrice = $priceRow - $gstAmount;

                $item_det = InventoryItem::where('id', $item_id)->first();
                $item_name = $item_det ? $item_det->item_name : '';

                $unit_price = (float) $basePrice;
                $item_total = $qty * $unit_price;
                $subtotal += $item_total;

                $gstAmount = ($priceRow * $gst_percent) / (100 + $gst_percent);
                $gstAmountTotal += $gstAmount * $qty;

                $token_item = new PosTokenItem();
                $token_item->token_id = $token->id;
                $token_item->item_id = $item_id;
                $token_item->item_name = $item_name;
                $token_item->item_total = $item_total;
                $token_item->unit_price = $unit_price;
                $token_item->qty = $qty;
                $token_item->gst_percent = $gst_percent;
                $token_item->gst_status = 'included';
                $token_item->gst_amount = $gstAmount;
                $token_item->save();

                $qty_left = $itemRow->qty_left - $token_item->qty;

                if ($qty_left >= 0) {
                    $itemQuery->update(['bi.qty_left' => $qty_left]);
                }
                if ($qty_left < 5) {
                    _notifLowStock($itemRow, $qty_left);
                }
            }

            $token->subtotal   = $subtotal;
            $token->gst_amount = $gstAmountTotal;
            $token->total      = $subtotal + $gstAmountTotal;
            $token->waiter_id  = $request->waiter_id;
            $token->save();

            DB::commit();

            return response()->json(['status' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false], 500);
        }
    }

    public function payment_method(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,bank,upi,cash_online',
            'payment_detail_id_bank' => 'nullable|integer',
            'payment_detail_id_upi' => 'nullable|integer',
            'cash_amount' => 'nullable|numeric|min:0',
            'online_amount' => 'nullable|numeric|min:0',
        ]);
        if ($request->payment_method === 'bank') {
            $request->validate(['payment_detail_id_bank' => 'required|integer']);
        } elseif ($request->payment_method === 'upi') {
            $request->validate(['payment_detail_id_upi' => 'required|integer']);
        }

        $store_id = Helpers::get_store_id();
        $token = PosToken::findOrFail($request->token_id);
        $token->payment_method = $request->payment_method;
        if ($request->payment_method === 'bank') {
            $token->bank_account_id = AccountDetail::where('id', $request->payment_detail_id_bank)
                ->where('user_type', 'vendor')
                ->where('user_id', $store_id)
                ->where('type', 'pos')
                ->where('payment_type', 'bank')
                ->value('id');
            $token->upi_account_id = null;
            $token->cash_amount = null;
            $token->online_amount = null;
        } elseif ($request->payment_method === 'upi') {
            $token->upi_account_id = AccountDetail::where('id', $request->payment_detail_id_upi)
                ->where('user_type', 'vendor')
                ->where('user_id', $store_id)
                ->where('type', 'pos')
                ->where('payment_type', 'upi')
                ->value('id');
            $token->bank_account_id = null;
            $token->cash_amount = null;
            $token->online_amount = null;
        } elseif ($request->payment_method === 'cash_online') {
            $token->bank_account_id = null;
            $token->upi_account_id = null;
            $token->cash_amount = $request->cash_amount;
            $token->online_amount = $request->online_amount;
        } else {
            $token->bank_account_id = null;
            $token->upi_account_id = null;
            $token->cash_amount = null;
            $token->online_amount = null;
        }
        $token->save();

        Toastr::success('Payment method updated successfully');
        return back();
    }

    public function token_export(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $preset = request('date_range') ?? 'today';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        $tokens = PosToken::with("tokenItems", "branch", "client")->where('store_id', $store_id)->whereBetween('created_at', [$formatted_from, $formatted_to])->get();

        foreach ($tokens as $key => $token) {
            $pdfUrl = asset('storage/app/public/store/tokens/') . '/' . $token->pdf;
            $itemsTax = $token->tokenItems
                ->pluck('gst_percent')
                ->filter()
                ->implode('%, ');

            $data[$key] = [
                $token->token_number,
                $token->branch?->name ?? 'Deleted',
                $token->client?->f_name ?? ($token->client->name ?? 'Walk-in Customer'),
                $token->order_from,
                $token->subtotal,
                $itemsTax,
                $token->gst_amount,
                $token->delivery,
                $token->coupon,
                $token->discount,
                $token->total,
                $token->payment_method,
                $token->payment_status,
                $token->pdf ? $pdfUrl : '',
                $token->address_type,
                $token->created_at,
            ];
        }

        $headings = ['Token Number', 'Branch', 'Client', 'Order Type', 'Subtotal', 'GST %', 'GST Amount', 'Delivery', 'Coupon', 'Discount', 'Total', 'Payment Method', 'Payment Status', 'Token PDF', 'Address Type', 'Created At'];

        return Excel::download(new TokenExport($data, $headings), 'Tokens_' . time() . '.xlsx');
    }

    public function token_cancel(Request $request, $id)
    {
        $posToken = PosToken::where('store_id', Helpers::get_store_id())
            ->where('id', $id)
            ->first();

        if ($posToken) {
            $posToken->update(['payment_status' => 'cancelled']);
        }
        Toastr::success('Cancelled Successfully');
        return back();
    }

    public function setting_save(Request $request)
    {
        $data = $request->except(['_token', '_method']);
        if (!empty($data)) {
            $config = StoreConfig::firstOrNew(['store_id' => Helpers::get_store_id()]);
            $config->fill($data);
            $config->save();

            Toastr::success('Updated Successfully');
        } else {
            Toastr::info('No fields to update');
        }
        return back();
    }

    public function token_list(Request $request)
    {
        $preset = request('date_range') ?? 'today';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];
        $from = $range['start']->toDateString();
        $to  = $range['end']->toDateString();
        $paymentMethod = $request->payment_method ?? null;
        $upiAccountFilter = $request->upi_account_id ?? null;

        $staffFilter = $request->staff_id ?? null;

        $query = PosToken::with('client', 'invoice', 'upiAccount', 'staff')
            ->when($paymentMethod && $paymentMethod !== 'all', function ($q) use ($paymentMethod) {
                $q->where('payment_method', $paymentMethod);
            })
            ->when($upiAccountFilter && $upiAccountFilter !== 'all', function ($q) use ($upiAccountFilter) {
                $q->where('upi_account_id', $upiAccountFilter);
            })
            ->when($staffFilter && $staffFilter !== 'all', function ($q) use ($staffFilter) {
                $q->where('staff_id', $staffFilter);
            })
            ->where('store_id', Helpers::get_store_id());
        if ($request->has('search') && $request->search != '') {
            $query->where('token_number', 'like', "%{$request->search}%");
        } else {
            $query->whereBetween('created_at', [$formatted_from, $formatted_to]);
            if ($request->has('branch') && $request->branch != '' && $request->branch != 'all') {
                $query->where('branch_id', $request->branch);
            }
        }

        $branches = Branch::where('store_id', Helpers::get_store_id())->get();
        $upiAccounts = AccountDetail::where('user_type', 'vendor')
            ->where('user_id', Helpers::get_store_id())
            ->where('type', 'pos')
            ->where('payment_type', 'upi')
            ->orderByDesc('id')
            ->get();
        $staffList = VendorEmployee::where('store_id', Helpers::get_store_id())->where('status', 1)->get();

        $totalAmount = (clone $query)->sum('total');
        $tokens = $query->orderBy('id', 'desc')->paginate(50);
        return view('pos::vendor.salespoint.token_list', compact('branches', 'preset', 'tokens', 'upiAccounts', 'totalAmount', 'staffList'));
    }

    public function calendar_export(Request $request)
    {
        $query = DB::table('pos_tokens as pt')
            ->where('store_id', Helpers::get_store_id())
            ->select(
                DB::raw('SUM(pt.total) as total_amount'),
                DB::raw('YEAR(pt.created_at) as year'),
                DB::raw('MONTH(pt.created_at) as month'),
                DB::raw('DAY(pt.created_at) as day'),
                DB::raw('COUNT(pt.id) as total_qty')
            );
        $file_postfix = '';
        if ($request->has('year') && $request->has('month')) {
            $query->whereYear('pt.created_at', $request->year)
                ->whereMonth('pt.created_at', $request->month)
                ->groupBy('year', 'month', 'day');
            $file_postfix .= $request->year . '-' . $request->month;
        } elseif ($request->has('year')) {
            $query->whereYear('pt.created_at', $request->year)
                ->groupBy('year', 'month');
            $file_postfix .= $request->year;
        }

        $items = $query->get();

        $data = [];
        foreach ($items as $key => $item) {
            $data[$key] = [
                $item->day,
                $item->month,
                $item->year,
                $item->total_qty,
                $item->total_amount,
            ];
        }

        $headings = ['Day', 'Month', 'Year', 'Total Qty', 'Total Sales'];
        return Excel::download(new POSReportExport($data, $headings), 'POS_Report_' . $file_postfix . '_' . time() . '.xlsx');
    }

    public function report(Request $request, $action = 'view')
    {
        $branch_id  = $request->branch ?? null;
        $storeId = Helpers::get_store_id();
        $preset = request('date_range') ?? 'today';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        $query = DB::table('pos_token_items as ti')
            ->join('pos_tokens as pt', 'pt.id', '=', 'ti.token_id')
            ->join('inventory_items as ii', 'ii.id', '=', 'ti.item_id')
            ->join('branches as br', 'pt.branch_id', '=', 'br.id')
            ->select(
                'ii.id as item_id',
                'ii.item_name',
                'ii.image',
                'br.name as branch_name',
                'br.type as branch_type',
                'br.id as branch_id',
                DB::raw('SUM(CASE WHEN pt.payment_status != "cancelled" THEN ti.qty ELSE 0 END) as total_qty'),
                DB::raw('SUM(CASE WHEN pt.payment_status != "cancelled" THEN ti.item_total ELSE 0 END) as total_sales'),
                DB::raw('SUM(CASE WHEN pt.payment_status = "cancelled" THEN ti.qty ELSE 0 END) as cancelled_qty'),
                DB::raw('SUM(CASE WHEN pt.payment_status = "cancelled" THEN ti.item_total ELSE 0 END) as cancelled_sales')
            )
            ->where('pt.store_id', $storeId);
        if ($request->has('search') && $request->search != '') {
            $query->where('ii.item_name', 'like', "%{$request->search}%");
        } else {
            $query->whereBetween('pt.created_at', [$formatted_from, $formatted_to]);
        }

        if ($branch_id && $branch_id != 'all') {
            $query->where('pt.branch_id', $branch_id);
        }
        $items = $query->groupBy('ii.id', 'ii.item_name', 'ii.image', 'br.name', 'br.type');
        $data = [];
        if ($action == 'export') {
            foreach ($items as $key => $item) {
                $data[$key] = [
                    ucfirst($item->item_name),
                    ucfirst($item->branch_name),
                    ucfirst($item->branch_type),
                    $item->total_qty,
                    $item->total_sales,
                    $item->cancelled_qty,
                    $item->cancelled_sales,
                    _getRemainingStock($item->item_id, $item->branch_id),
                ];
            }
            $headings = ['Item Name', 'Branch Name', 'Branch Type', 'Total Qty', 'Total Sales', 'Cancelled Qty', 'Cancelled Sales', 'Remaining Stock'];

            return Excel::download(new POSReportExport($data, $headings), 'Items_Report_' . time() . '.xlsx');
        } else {
            $colors = _posDashboardColors();
            $sales = Helpers::pos_calendar_sales($request->branch_cl ?? null);
            $data['branches'] = Branch::where('store_id', $storeId)->get();
            $items = $query->paginate(10);
        }

        $branches = Branch::where('store_id', $storeId)->get();
        return view('pos::vendor.salespoint.report', compact('sales', 'colors', 'data', 'branch_id', 'items', 'branches', 'preset'));
    }

    public function items_import(Request $request)
    {
        $file = $request->file('file');
        $import = new POSInvItemImport(Helpers::get_store_id());
        Excel::import($import, $file);

        Toastr::success('Excel file imported successfully.');
        return redirect()->back();
    }

    public function items(Request $request, $action = 'view')
    {
        $store_id = Helpers::get_store_id();
        $inventory_items = _inventoryItems();
        $branches = Branch::where('store_id', $store_id)->get();

        $branch_inventory_items = _branchInventoryItems($request->branch, $request->search ?? null, $action);
        $posItems = $inventory_items = $branch_inventory_items;

        if ($action == 'export') {
            foreach ($posItems as $key => $item) {
                $data[$key] = [
                    $item->id,
                    ucfirst($item->name),
                    $item->qty,
                    $item->qty_left,
                    $item->price,
                    $item->gst_percent,
                    ucfirst($item->branch_name) . ' (' . $item->branch_type . ')',
                ];
            }
            $headings = ["Item ID", 'Item Name', 'Added Stock', 'Current Stock', 'Price', "GST %", 'Branch'];

            return Excel::download(new POSItemsExport($data, $headings), 'POS_Items_' . time() . '.xlsx');
        }

        return view('pos::vendor.salespoint.items', compact('posItems', 'inventory_items', 'branches'));
    }

    public function items_save(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $submittedItems = $request->items;
        $branch = $request->branch;

        if (is_numeric($branch)) {
            $branchId = $branch;
        } else {
            $existingBranch = Branch::where('name', $branch)
                ->where('store_id', $store_id)
                ->first();

            if ($existingBranch) {
                $branchId = $existingBranch->id;
            } else {
                $newBranch = Branch::create([
                    'name'     => $branch,
                    'store_id' => $store_id
                ]);
                $branchId = $newBranch->id;
            }
        }

        foreach ($submittedItems as $item) {
            $price = $request->prices[$item] ?? 0;
            $qty = $request->qty[$item] ?? 0;
            $gst_percent = $request->gst[$item] ?? 0;

            $invItem = BranchInventoryItem::where([
                'branch_id'         => $branchId,
                'inventory_item_id' => $item
            ])->first();

            if ($invItem) {
                BranchInventoryItem::where([
                    'branch_id'         => $branchId,
                    'inventory_item_id' => $item,
                    'store_id'          => $store_id
                ])->update([
                    'gst_percent' => $gst_percent,
                    'qty'         => $invItem->qty + $qty,
                    'qty_left'    => $invItem->qty_left + $qty,
                    'price'       => $price,
                ]);
            } else {
                BranchInventoryItem::create([
                    'gst_percent'       => $gst_percent,
                    'branch_id'         => $branchId,
                    'inventory_item_id' => $item,
                    'qty'               => $qty,
                    'qty_left'          => $qty,
                    'price'             => $price,
                    'store_id'          => $store_id
                ]);
            }
        }
        Toastr::success('Added Successfully');
        return back();
    }

    public function item_remove(Request $request, $item_id, $branch_id)
    {
        DB::table('branch_inventory_item')
            ->where('branch_id', $branch_id)
            ->where('inventory_item_id', $item_id)
            ->delete();

        Toastr::success('Item removed successfully');
        return back();
    }

    public function getBranchData(Request $request)
    {
        $itemIds = $request->item_ids;
        $branchId = $request->branch_id;

        $branch = Branch::find($branchId);

        $data['items'] = DB::table('branch_inventory_item as bi')
            ->join('branches as b', 'b.id', '=', 'bi.branch_id')
            ->whereIn('bi.inventory_item_id', $itemIds)
            ->where('bi.branch_id', $branchId)
            ->select('bi.*', 'b.gst_number')
            ->get()
            ->keyBy('inventory_item_id');

        $data['gst_status'] = $branch->gst_number ? true : false;
        return response()->json($data);
    }

    public function report_export(Request $request)
    {
        $data = $request->all();
        return response()->json(['message' => 'Report exported successfully']);
    }
}
