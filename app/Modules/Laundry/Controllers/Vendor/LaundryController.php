<?php

namespace App\Modules\Laundry\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Exports\AttendanceExport;
use App\Http\Controllers\Controller;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\Store;
use App\Models\StoreCustomer;
use App\Modules\Laundry\LaundryHelpers;
use App\Models\LaundryChallan;
use App\Models\LaundryChallanItem;
use App\Models\InventoryItem;
use App\Models\LaundryOrder;
use App\Models\LaundryOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Brian2694\Toastr\Facades\Toastr;

class LaundryController extends Controller
{
    // ================================================================
    //  DASHBOARD
    // ================================================================

    public function dashboard(\Illuminate\Http\Request $request)
    {
        $storeId = Helpers::get_store_id();

        $preset = $request->get('date_range', 'this_month');
        $custom = $request->get('custom_date_range');
        $range  = Helpers::calculatePresetDates($preset, $custom);
        $from   = $range['start']->toDateString();
        $to     = $range['end']->toDateString();

        $ordersInRange  = LaundryOrder::where('store_id', $storeId)->whereDate('drop_date', '>=', $from)->whereDate('drop_date', '<=', $to)->count();
        $ordersPending  = LaundryOrder::where('store_id', $storeId)->whereNotIn('status', ['delivered', 'cancelled'])->count();
        $revenueInRange = LaundryOrder::where('store_id', $storeId)->whereDate('drop_date', '>=', $from)->whereDate('drop_date', '<=', $to)->where('payment_status', 'paid')->sum('total_amount');

        $challansPending  = LaundryChallan::where('store_id', $storeId)->where('status', 'pending')->count();
        $challansPartial  = LaundryChallan::where('store_id', $storeId)->where('status', 'partial')->count();
        $challansInRange  = LaundryChallan::where('store_id', $storeId)->whereDate('outward_date', '>=', $from)->whereDate('outward_date', '<=', $to)->count();

        $recentOrders   = LaundryOrder::with('customer')->where('store_id', $storeId)->whereDate('drop_date', '>=', $from)->whereDate('drop_date', '<=', $to)->orderByDesc('created_at')->limit(5)->get();
        $recentChallans = LaundryChallan::with('hotel')->where('store_id', $storeId)->whereDate('outward_date', '>=', $from)->whereDate('outward_date', '<=', $to)->orderByDesc('created_at')->limit(5)->get();

        $sales = LaundryHelpers::laundry_calendar();

        return view('laundry::vendor.dashboard', compact(
            'ordersInRange', 'ordersPending', 'revenueInRange',
            'challansPending', 'challansPartial', 'challansInRange',
            'recentOrders', 'recentChallans',
            'preset', 'from', 'to', 'sales'
        ));
    }

    // ================================================================
    //  WALK-IN ORDERS
    // ================================================================

    public function orders(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $status  = $request->get('status');
        $search  = $request->get('search');

        $preset = $request->get('date_range', 'this_month');
        $custom = $request->get('custom_date_range');
        $range  = Helpers::calculatePresetDates($preset, $custom);
        $from   = $range['start']->toDateString();
        $to     = $range['end']->toDateString();

        $query = LaundryOrder::with('customer')
            ->where('store_id', $storeId)
            ->whereDate('drop_date', '>=', $from)
            ->whereDate('drop_date', '<=', $to);

        if ($status) {
            $query->where('status', $status);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderByDesc('created_at')->paginate(20)->appends($request->query());
        return view('laundry::vendor.orders', compact('orders', 'status', 'search', 'from', 'to', 'preset'));
    }

    public function order_create()
    {
        $storeId = Helpers::get_store_id();
        $items   = InventoryItem::where('store_id', $storeId)->orderBy('item_name')->get();
        return view('laundry::vendor.order_create', compact('items'));
    }

    public function order_store(Request $request)
    {
        $request->validate([
            'drop_date'            => 'required|date',
            'items'                => 'required|array|min:1',
            'items.*.item_name'    => 'required|string',
            'items.*.qty'          => 'required|integer|min:1',
            'items.*.rate'         => 'required|numeric|min:0',
        ]);

        $storeId = Helpers::get_store_id();

        DB::beginTransaction();
        try {
            $orderNo = $this->generateOrderNo($storeId);

            $total = 0;
            foreach ($request->items as $row) {
                $total += ($row['qty'] * $row['rate']);
            }

            $order = LaundryOrder::create([
                'store_id'            => $storeId,
                'order_no'            => $orderNo,
                'store_customer_id'   => $request->store_customer_id ?: null,
                'customer_name'       => $request->customer_name,
                'customer_phone'      => $request->customer_phone,
                'drop_date'           => $request->drop_date,
                'expected_ready_date' => $request->expected_ready_date ?: null,
                'status'              => 'received',
                'total_amount'        => $total,
                'payment_status'      => 'pending',
                'employee_id'         => $request->employee_id ?: null,
                'notes'               => $request->notes,
            ]);

            foreach ($request->items as $row) {
                LaundryOrderItem::create([
                    'laundry_order_id'  => $order->id,
                    'inventory_item_id' => $row['inventory_item_id'] ?? null,
                    'item_name'         => $row['item_name'],
                    'qty'              => $row['qty'],
                    'rate'             => $row['rate'],
                    'amount'           => $row['qty'] * $row['rate'],
                    'notes'            => $row['notes'] ?? null,
                ]);
            }

            DB::commit();
            Toastr::success('Order created: ' . $orderNo);
            return redirect()->route('vendor.laundry.orders.show', $order->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to create order');
            return back()->withInput();
        }
    }

    public function order_show($id)
    {
        $storeId = Helpers::get_store_id();
        $order   = LaundryOrder::with('items', 'customer', 'employee')
            ->where('store_id', $storeId)
            ->findOrFail($id);
        return view('laundry::vendor.order_show', compact('order'));
    }

    public function order_status(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'status'   => 'required|in:received,processing,ready,delivered,cancelled',
        ]);

        $order = LaundryOrder::where('store_id', Helpers::get_store_id())->findOrFail($request->order_id);
        $data  = ['status' => $request->status];

        if ($request->status === 'delivered') {
            $data['pickup_date']    = now()->toDateString();
            $data['payment_status'] = 'paid';
        }

        $order->update($data);
        Toastr::success('Status updated');
        return back();
    }

    // ================================================================
    //  CHALLANS — hotel / B2B
    // ================================================================

    public function challans(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $status  = $request->get('status');
        $hotelId = $request->get('hotel_id');

        $preset = $request->get('date_range', 'this_month');
        $custom = $request->get('custom_date_range');
        $range  = Helpers::calculatePresetDates($preset, $custom);
        $from   = $range['start']->toDateString();
        $to     = $range['end']->toDateString();

        $query = LaundryChallan::with('hotel')
            ->where('store_id', $storeId)
            ->whereDate('outward_date', '>=', $from)
            ->whereDate('outward_date', '<=', $to);

        if ($status) {
            $query->where('status', $status);
        }
        if ($hotelId) {
            $query->where('store_customer_id', $hotelId);
        }

        $challans = $query->orderByDesc('created_at')->paginate(20)->appends($request->query());
        $hotels   = StoreCustomer::where('store_id', $storeId)->where('user_type', 'vendor')->orderBy('f_name')->get();

        return view('laundry::vendor.challans', compact('challans', 'hotels', 'status', 'hotelId', 'from', 'to', 'preset'));
    }

    public function challan_create()
    {
        $storeId = Helpers::get_store_id();
        $items   = InventoryItem::where('store_id', $storeId)->orderBy('item_name')->get();
        return view('laundry::vendor.challan_create', compact('items'));
    }

    public function challan_store(Request $request)
    {
        $request->validate([
            'store_customer_id' => 'required|integer',
            'outward_date'      => 'required|date',
            'items'             => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.sent_qty'  => 'required|integer|min:0',
        ]);

        $storeId = Helpers::get_store_id();

        DB::beginTransaction();
        try {
            $challanNo = $this->generateChallanNo($storeId);

            $challan = LaundryChallan::create([
                'store_id'             => $storeId,
                'challan_no'           => $challanNo,
                'store_customer_id'    => $request->store_customer_id,
                'outward_date'         => $request->outward_date,
                'expected_inward_date' => $request->expected_inward_date ?: null,
                'status'               => 'pending',
                'notes'                => $request->notes,
            ]);

            foreach ($request->items as $row) {
                $prevBalance = $this->getPreviousBalance($storeId, $request->store_customer_id, $row['inventory_item_id'] ?? null, $row['item_name']);
                $sentQty     = (int) $row['sent_qty'];
                $total       = $prevBalance + $sentQty;

                LaundryChallanItem::create([
                    'laundry_challan_id' => $challan->id,
                    'inventory_item_id'  => $row['inventory_item_id'] ?? null,
                    'item_name'          => $row['item_name'],
                    'rate'               => (float) ($row['rate'] ?? 0),
                    'previous_balance'   => $prevBalance,
                    'sent_qty'           => $sentQty,
                    'total'              => $total,
                    'received_qty'       => 0,
                    'damaged_qty'        => 0,
                    'balance'            => $total,
                    'remarks'            => $row['remarks'] ?? null,
                ]);
            }

            DB::commit();
            Toastr::success('Challan created: ' . $challanNo);
            return redirect()->route('vendor.laundry.challans.show', $challan->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to create challan');
            return back()->withInput();
        }
    }

    public function challan_show($id)
    {
        $storeId = Helpers::get_store_id();
        $challan = LaundryChallan::with('items', 'hotel')
            ->where('store_id', $storeId)
            ->findOrFail($id);
        return view('laundry::vendor.challan_show', compact('challan'));
    }

    public function challan_receive($id)
    {
        $storeId = Helpers::get_store_id();
        $challan = LaundryChallan::with('items', 'hotel')
            ->where('store_id', $storeId)
            ->whereIn('status', ['pending', 'partial'])
            ->findOrFail($id);
        return view('laundry::vendor.challan_receive', compact('challan'));
    }

    public function challan_receive_update(Request $request)
    {
        $request->validate([
            'challan_id'           => 'required|integer',
            'inward_date'          => 'required|date',
            'items'                => 'required|array',
            'items.*.id'           => 'required|integer',
            'items.*.received_qty' => 'required|integer|min:0',
            'items.*.damaged_qty'  => 'required|integer|min:0',
        ]);

        $storeId = Helpers::get_store_id();
        $challan = LaundryChallan::where('store_id', $storeId)->findOrFail($request->challan_id);

        DB::beginTransaction();
        try {
            foreach ($request->items as $row) {
                $ci = LaundryChallanItem::where('laundry_challan_id', $challan->id)
                    ->where('id', $row['id'])
                    ->firstOrFail();

                $received = (int) $row['received_qty'];
                $damaged  = (int) $row['damaged_qty'];
                $balance  = $ci->total - $received - $damaged;

                $ci->update([
                    'received_qty' => $received,
                    'damaged_qty'  => $damaged,
                    'balance'      => $balance,
                    'remarks'      => $row['remarks'] ?? $ci->remarks,
                ]);
            }

            $challan->inward_date = $request->inward_date;
            $totalBalance = LaundryChallanItem::where('laundry_challan_id', $challan->id)->sum('balance');
            $challan->status = $totalBalance > 0 ? 'partial' : 'returned';
            $challan->save();

            DB::commit();
            Toastr::success('Return recorded successfully');
            return redirect()->route('vendor.laundry.challans.show', $challan->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to update return');
            return back();
        }
    }

    public function challan_print($id)
    {
        $storeId = Helpers::get_store_id();
        $challan = LaundryChallan::with('items', 'hotel', 'store')
            ->where('store_id', $storeId)
            ->findOrFail($id);
        return view('laundry::vendor.challan_print', compact('challan'));
    }

    public function monthly_register(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $hotelId = $request->get('hotel_id');

        $preset = $request->get('date_range', 'this_month');
        $custom = $request->get('custom_date_range');
        $range  = Helpers::calculatePresetDates($preset, $custom);
        $from   = $range['start']->toDateString();
        $to     = $range['end']->toDateString();

        $hotels = StoreCustomer::where('store_id', $storeId)->where('user_type', 'vendor')->orderBy('f_name')->get();

        $dates   = [];
        $current = \Carbon\Carbon::parse($from);
        $end     = \Carbon\Carbon::parse($to);
        while ($current->lte($end)) {
            $dates[] = $current->toDateString();
            $current->addDay();
        }

        $challanQuery = LaundryChallan::where('store_id', $storeId)
            ->whereDate('outward_date', '>=', $from)
            ->whereDate('outward_date', '<=', $to);
        if ($hotelId) {
            $challanQuery->where('store_customer_id', $hotelId);
        }
        $challanIds = $challanQuery->pluck('id');

        $rows = LaundryChallanItem::whereIn('laundry_challan_id', $challanIds)
            ->with('challan')
            ->get();

        $grid = [];
        foreach ($rows as $row) {
            $date = $row->challan->outward_date->toDateString();
            $key  = $row->item_name;
            if (!isset($grid[$key])) {
                $grid[$key] = array_fill_keys($dates, 0);
            }
            $grid[$key][$date] = ($grid[$key][$date] ?? 0) + max(0, $row->sent_qty - $row->damaged_qty);
        }

        return view('laundry::vendor.monthly_register', compact('grid', 'dates', 'from', 'to', 'preset', 'hotels', 'hotelId'));
    }

    public function monthly_register_export(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $hotelId = $request->get('hotel_id');

        $preset = $request->get('date_range', 'this_month');
        $custom = $request->get('custom_date_range');
        $range  = Helpers::calculatePresetDates($preset, $custom);
        $from   = $range['start']->toDateString();
        $to     = $range['end']->toDateString();

        $dates   = [];
        $current = \Carbon\Carbon::parse($from);
        $end     = \Carbon\Carbon::parse($to);
        while ($current->lte($end)) {
            $dates[] = $current->toDateString();
            $current->addDay();
        }

        $challanQuery = LaundryChallan::where('store_id', $storeId)
            ->whereDate('outward_date', '>=', $from)
            ->whereDate('outward_date', '<=', $to);
        if ($hotelId) {
            $challanQuery->where('store_customer_id', $hotelId);
        }
        $challanIds = $challanQuery->pluck('id');

        $rows = LaundryChallanItem::whereIn('laundry_challan_id', $challanIds)
            ->with('challan')
            ->get();

        $grid = [];
        foreach ($rows as $row) {
            $date = $row->challan->outward_date->toDateString();
            $key  = $row->item_name;
            if (!isset($grid[$key])) {
                $grid[$key] = array_fill_keys($dates, 0);
            }
            $grid[$key][$date] = ($grid[$key][$date] ?? 0) + max(0, $row->sent_qty - $row->damaged_qty);
        }

        $singleMonth = \Carbon\Carbon::parse($from)->format('Y-m') === \Carbon\Carbon::parse($to)->format('Y-m');
        $headings = ['Item'];
        foreach ($dates as $date) {
            $headings[] = $singleMonth
                ? (int) \Carbon\Carbon::parse($date)->format('j')
                : \Carbon\Carbon::parse($date)->format('d M');
        }
        $headings[] = 'Total';

        $data       = [];
        $dateTotals = array_fill_keys($dates, 0);
        foreach ($grid as $itemName => $days) {
            $row      = [$itemName];
            $rowTotal = 0;
            foreach ($dates as $date) {
                $qty = $days[$date] ?? 0;
                $row[]             = $qty ?: '';
                $dateTotals[$date] += $qty;
                $rowTotal          += $qty;
            }
            $row[]  = $rowTotal;
            $data[] = $row;
        }

        $footer     = ['Daily Total'];
        $grandTotal = 0;
        foreach ($dates as $date) {
            $footer[] = $dateTotals[$date] ?: '';
            $grandTotal += $dateTotals[$date];
        }
        $footer[] = $grandTotal;
        $data[]   = $footer;

        $hotelName = $hotelId
            ? (StoreCustomer::find($hotelId)?->f_name ?? '')
            : 'All Hotels';
        $filename = 'laundry_register_' . $from . '_' . $to . '_' . str_replace(' ', '_', $hotelName) . '.xlsx';

        return Excel::download(new AttendanceExport($data, $headings), $filename);
    }

    // ================================================================
    //  MONTHLY BILLING
    // ================================================================

    public function monthly_billing_form(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $hotelId = $request->get('hotel_id');

        if (!$hotelId) {
            Toastr::warning('Please select a hotel first to generate monthly billing.');
            return redirect()->route('vendor.laundry.register');
        }

        $preset = $request->get('date_range', 'this_month');
        $custom = $request->get('custom_date_range');
        $range  = Helpers::calculatePresetDates($preset, $custom);
        $from   = $range['start']->toDateString();
        $to     = $range['end']->toDateString();

        $hotel = StoreCustomer::where('store_id', $storeId)->findOrFail($hotelId);

        $challanIds = LaundryChallan::where('store_id', $storeId)
            ->where('store_customer_id', $hotelId)
            ->whereDate('outward_date', '>=', $from)
            ->whereDate('outward_date', '<=', $to)
            ->pluck('id');

        $rows = LaundryChallanItem::whereIn('laundry_challan_id', $challanIds)->get();

        $items = [];
        foreach ($rows as $row) {
            $sent    = (int) $row->sent_qty;
            $damaged = (int) $row->damaged_qty;
            $netQty  = max(0, $sent - $damaged);
            $key = $row->item_name;
            if (!isset($items[$key])) {
                $items[$key] = ['sent' => 0, 'damaged' => 0, 'qty' => 0, 'rate' => (float) $row->rate];
            }
            $items[$key]['sent']    += $sent;
            $items[$key]['damaged'] += $damaged;
            $items[$key]['qty']     += $netQty;
            $items[$key]['rate']     = (float) $row->rate;
        }
        $items = array_filter($items, fn($i) => $i['qty'] > 0);

        return view('laundry::vendor.monthly_billing', compact(
            'items', 'hotel', 'hotelId', 'from', 'to', 'preset'
        ));
    }

    public function monthly_billing_store(Request $request)
    {
        $request->validate([
            'hotel_id'        => 'required|integer',
            'from'            => 'required|date',
            'to'              => 'required|date',
            'tax_type'        => 'required|in:gst,non-gst',
            'gst_percent'     => 'nullable|numeric|min:0|max:100',
            'items'           => 'required|array|min:1',
            'items.*.name'    => 'required|string',
            'items.*.qty'     => 'required|integer|min:1',
            'items.*.rate'    => 'required|numeric|min:0',
        ]);

        $taxType    = $request->input('tax_type', 'non-gst');
        $gstPercent = $taxType === 'gst' ? (float) $request->input('gst_percent', 0) : 0;

        $storeId = Helpers::get_store_id();
        $hotel   = StoreCustomer::where('store_id', $storeId)->findOrFail($request->hotel_id);
        $store   = Store::find($storeId);

        DB::beginTransaction();
        try {
            $invoiceId = Helpers::generateInvoiceId('M', true, null, $taxType, $store);

            $baseTotal = 0;
            foreach ($request->items as $item) {
                $baseTotal += (float) $item['rate'] * (int) $item['qty'];
            }
            $total = $taxType === 'gst' ? round($baseTotal * (1 + $gstPercent / 100), 2) : $baseTotal;

            $invoice                 = new ManualInvoice();
            $invoice->invoice_id     = $invoiceId;
            $invoice->invoice_serial = (int) substr($invoiceId, strrpos($invoiceId, '_') + 1);
            $invoice->vendor_id      = $storeId;
            $invoice->bill_to        = $hotel->id;
            $invoice->bill_to_type   = 'user';
            $invoice->user_type      = 'store_vendor';
            $invoice->type           = 'laundry_monthly';
            $invoice->module_id      = Helpers::get_store_data()->module_id;
            $invoice->tax_type       = $taxType;
            $invoice->payment_method = 'Cash';
            $invoice->invoice_date   = now()->toDateString();
            $invoice->payment_status = 'Unpaid';
            $invoice->total_amount   = $total;
            $invoice->meta           = [
                'source'       => 'laundry',
                'billing_type' => 'monthly',
                'hotel'        => $hotel->f_name,
                'hotel_id'     => $hotel->id,
                'period_from'  => $request->from,
                'period_to'    => $request->to,
            ];
            $invoice->financial_year = _currentFinancialYear();
            $invoice->save();

            foreach ($request->items as $item) {
                $inv                    = new InvoiceItem();
                $inv->rand_invoice_id   = $invoiceId;
                $inv->manual_invoice_id = $invoice->id;
                $inv->name              = $item['name'];
                $inv->qty               = (int) $item['qty'];
                $inv->price             = (float) $item['rate'];
                $inv->tax               = $gstPercent;
                $inv->gst_status        = 'excluding';
                $inv->save();
            }

            DB::commit();

            $pdf = _createBillPdf($invoice, 'vendor');
            $invoice->update(['pdf' => $pdf['pdf']]);

            Toastr::success('Monthly bill generated: ' . $invoiceId);
            return redirect($pdf['url']);

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to generate bill: ' . $e->getMessage());
            return back();
        }
    }

    // ================================================================
    //  ORDER RECEIPT
    // ================================================================

    public function order_receipt($id)
    {
        $storeId = Helpers::get_store_id();
        $order   = LaundryOrder::with('items', 'customer')
            ->where('store_id', $storeId)
            ->findOrFail($id);
        $store   = Store::find($storeId);
        return view('laundry::vendor.order_receipt', compact('order', 'store'));
    }

    // ================================================================
    //  ORDER INVOICE
    // ================================================================

    public function order_invoice(Request $request, $id)
    {
        $storeId = Helpers::get_store_id();
        $order   = LaundryOrder::with('items', 'customer')
            ->where('store_id', $storeId)
            ->findOrFail($id);

        if ($order->invoice_id) {
            return redirect()->route('vendor.invoice.view-invoice', ['manual', $order->invoice_id]);
        }

        $request->validate([
            'tax_type'    => 'required|in:gst,non-gst',
            'gst_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $taxType    = $request->input('tax_type', 'non-gst');
        $gstPercent = $taxType === 'gst' ? (float) $request->input('gst_percent', 0) : 0;

        $store     = Store::find($storeId);
        $invoiceId = Helpers::generateInvoiceId('M', true, null, $taxType, $store);

        DB::beginTransaction();
        try {
            $customerId = $order->store_customer_id;
            $userType   = 'store_user';

            if ($customerId) {
                $cust     = StoreCustomer::find($customerId);
                $userType = ($cust && $cust->user_type === 'vendor') ? 'store_vendor' : 'store_user';
            } else {
                $customerId = null;
            }

            $baseTotal = (float) $order->items->sum(fn($i) => $i->qty * $i->rate);
            $total     = $taxType === 'gst' ? round($baseTotal * (1 + $gstPercent / 100), 2) : $baseTotal;

            $invoice                   = new ManualInvoice();
            $invoice->invoice_id       = $invoiceId;
            $invoice->invoice_serial   = (int) substr($invoiceId, strrpos($invoiceId, '_') + 1);
            $invoice->vendor_id        = $storeId;
            $invoice->bill_to          = $customerId;
            $invoice->bill_to_type     = 'user';
            $invoice->user_type        = $userType;
            $invoice->type             = 'laundry_walkin';
            $invoice->module_id        = Helpers::get_store_data()->module_id;
            $invoice->total_amount     = $total;
            $invoice->tax_type         = $taxType;
            $invoice->payment_method   = 'Cash';
            $invoice->invoice_date     = now()->toDateString();
            $invoice->payment_status   = $order->payment_status === 'paid' ? 'Paid' : 'Unpaid';
            $invoice->meta             = [
                'source'           => 'laundry',
                'laundry_order_id' => $order->id,
                'order_no'         => $order->order_no,
                'customer_name'    => $order->customer_display_name,
                'customer_phone'   => $order->customer_display_phone,
            ];
            $invoice->financial_year = _currentFinancialYear();
            $invoice->save();

            foreach ($order->items as $item) {
                $inv                    = new InvoiceItem();
                $inv->rand_invoice_id   = $invoiceId;
                $inv->manual_invoice_id = $invoice->id;
                $inv->name              = $item->item_name;
                $inv->qty               = $item->qty;
                $inv->price             = $item->rate;
                $inv->tax               = $gstPercent;
                $inv->gst_status        = 'excluding';
                $inv->save();
            }

            $order->update([
                'invoice_id'     => $invoiceId,
                'payment_status' => 'paid',
            ]);

            DB::commit();

            $pdf = _createBillPdf($invoice, 'vendor');
            $invoice->update(['pdf' => $pdf['pdf']]);

            Toastr::success('Invoice generated: ' . $invoiceId);
            return redirect($pdf['url']);

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to generate invoice: ' . $e->getMessage());
            return back();
        }
    }

    // ================================================================
    //  CHALLAN INVOICE
    // ================================================================

    public function challan_invoice(Request $request, $id)
    {
        $storeId = Helpers::get_store_id();
        $challan = LaundryChallan::with('items', 'hotel')
            ->where('store_id', $storeId)
            ->findOrFail($id);

        if ($challan->invoice_id) {
            return redirect()->route('vendor.invoice.view-invoice', [$challan->invoice_id]);
        }

        $request->validate([
            'tax_type'    => 'required|in:gst,non-gst',
            'gst_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $taxType    = $request->input('tax_type', 'non-gst');
        $gstPercent = $taxType === 'gst' ? (float) $request->input('gst_percent', 0) : 0;

        $store     = Store::find($storeId);
        $invoiceId = Helpers::generateInvoiceId('M', true, null, $taxType, $store);

        DB::beginTransaction();
        try {
            $hotel    = $challan->hotel;
            $userType = 'store_vendor';

            $invoice                   = new ManualInvoice();
            $invoice->invoice_id       = $invoiceId;
            $invoice->invoice_serial   = (int) substr($invoiceId, strrpos($invoiceId, '_') + 1);
            $invoice->vendor_id        = $storeId;
            $invoice->bill_to          = $hotel ? $hotel->id : null;
            $invoice->bill_to_type     = 'user';
            $invoice->user_type        = $userType;
            $invoice->type             = 'laundry_hotel';
            $invoice->module_id        = Helpers::get_store_data()->module_id;
            $invoice->tax_type         = $taxType;
            $invoice->payment_method   = 'Cash';
            $invoice->invoice_date     = now()->toDateString();
            $invoice->payment_status   = 'Unpaid';
            $invoice->meta             = [
                'source'             => 'laundry',
                'laundry_challan_id' => $challan->id,
                'challan_no'         => $challan->challan_no,
            ];

            $baseTotal = 0;
            foreach ($challan->items as $item) {
                $billableQty = max(0, $item->sent_qty - $item->damaged_qty);
                $baseTotal += (float) $item->rate * $billableQty;
            }
            $invoice->total_amount   = $taxType === 'gst'
                ? round($baseTotal * (1 + $gstPercent / 100), 2)
                : $baseTotal;
            $invoice->financial_year = _currentFinancialYear();
            $invoice->save();

            foreach ($challan->items as $item) {
                $billableQty = max(0, $item->sent_qty - $item->damaged_qty);
                if ($billableQty <= 0) continue;
                $inv                    = new InvoiceItem();
                $inv->rand_invoice_id   = $invoiceId;
                $inv->manual_invoice_id = $invoice->id;
                $inv->name              = $item->item_name;
                $inv->qty               = $billableQty;
                $inv->price             = (float) $item->rate;
                $inv->tax               = $gstPercent;
                $inv->gst_status        = 'excluding';
                $inv->save();
            }

            $challan->update(['invoice_id' => $invoiceId]);

            DB::commit();

            $pdf = _createBillPdf($invoice, 'vendor');
            $invoice->update(['pdf' => $pdf['pdf']]);

            Toastr::success('Invoice generated: ' . $invoiceId);
            return redirect($pdf['url']);

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to generate invoice: ' . $e->getMessage());
            return back();
        }
    }

    // ================================================================
    //  HELPERS
    // ================================================================

    private function generateOrderNo($storeId): string
    {
        $year = now()->format('Y');
        $last = LaundryOrder::where('store_id', $storeId)
            ->whereYear('created_at', $year)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('order_no');

        $next = 1;
        if ($last) {
            $parts = explode('-', $last);
            $next  = (int) end($parts) + 1;
        }

        return 'LDY-' . $year . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    private function generateChallanNo($storeId): string
    {
        $year = now()->format('Y');
        $last = LaundryChallan::where('store_id', $storeId)
            ->whereYear('created_at', $year)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('challan_no');

        $next = 1;
        if ($last) {
            $parts = explode('-', $last);
            $next  = (int) end($parts) + 1;
        }

        return 'CH-' . $year . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    private function getPreviousBalance($storeId, $hotelId, $inventoryItemId, $itemName): int
    {
        $lastChallanId = LaundryChallan::where('store_id', $storeId)
            ->where('store_customer_id', $hotelId)
            ->orderByDesc('id')
            ->value('id');

        if (!$lastChallanId) {
            return 0;
        }

        $lastItem = LaundryChallanItem::where('laundry_challan_id', $lastChallanId)
            ->when($inventoryItemId, fn($q) => $q->where('inventory_item_id', $inventoryItemId))
            ->when(!$inventoryItemId, fn($q) => $q->where('item_name', $itemName))
            ->first();

        return $lastItem ? $lastItem->balance : 0;
    }
}
