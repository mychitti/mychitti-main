<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Exports\POExport;
use App\Http\Controllers\Controller;
use App\Models\InvoiceItem;
use App\Models\InventoryGatepass;
use App\Models\ManualInvoice;
use App\Models\ManualTrial;
use App\Models\PurchaseOrder;
use App\Models\ReturnPurchaseSlip;
use App\Models\StoreCustomer;
use App\Models\SupplyOrder;
use App\Models\SupplyOrderItem;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class InventoryPurchaseController extends Controller
{
    // Inventory order 
    public function order_place(Request $request)
    {
        // prx($request->all());
        $request->validate([
            'item_name'   => 'required|array|min:1',
            'item_name.*' => 'required|string|max:255', // each element must not be empty
            'bill_to' => 'required'
        ], [
            'bill_to.required' => 'Please select vendor'
        ]);

        $storeId = Helpers::get_store_id();

        // PO NUMBER ==========
        if ($request->tax_type == 'gst') {
            $po_number = $request->gst_po_number;
            $column = 'gst_serial_number';
        } else {
            $po_number = $request->non_gst_po_number;
            $column = 'nongst_serial_number';
        }
        if (!$po_number) {
            if ($request->tax_type == 'gst') {
                $po_number = Helpers::generatePoNumber('gst')['po_number'];
            } else {
                $po_number = Helpers::generatePoNumber('non-gst')['po_number'];
            }
        }
        // SERIAL NUMBER ===========
        $parts = explode("_", $po_number);
        $lastNumber = end($parts);
        $lastNumberInt = (int)$lastNumber;

        $serial_number_exists = SupplyOrder::where('store_id', $storeId)->where($column, $lastNumberInt)->exists();
        if ($serial_number_exists) {
            Toastr::error('Serial Number ' . $lastNumberInt . ' Already Used');
            return back();
        }
        // PO ORDER =============
        $supplyOrder = new SupplyOrder();
        $supplyOrder->$column = $lastNumberInt;
        $supplyOrder->store_id = $storeId;
        $supplyOrder->po_number = $po_number;
        $supplyOrder->gst_type = $request->tax_type;
        $supplyOrder->vendor_id = $request->bill_to; // bill from
        $supplyOrder->notes = $request->notes;
        $supplyOrder->purchase_date = $request->purchase_date ?? NOW();
        $supplyOrder->save();

        // PO ITEMS ==============
        $totalTaxAmount = 0;
        $subTotalAmount = 0;
        if ($request->has('item_name')) {
            foreach ($request->item_name as $key => $item_name) {
                $taxRate = $request->tax_type == 'gst' ?  $request->gst[$key] : 0;
                $totalAmount = ($request->price[$key] ?? 0) * ($request->qty[$key] ?? 1);
                $taxAmount = ($totalAmount * $taxRate) / 100;
                $totalTaxAmount +=  $taxAmount;
                $subTotalAmount +=  $totalAmount;

                $orderItem = new SupplyOrderItem();
                $orderItem->order_table_id  = $supplyOrder->id;
                $orderItem->item_id = $request->item_id[$key];
                $orderItem->item_name = $request->item_name[$key];
                $orderItem->unit_price = $request->price[$key];
                $orderItem->tax_rate = $taxRate;
                $orderItem->total_amount = $totalAmount + $taxAmount;
                $orderItem->tax_amount = $taxAmount;
                $orderItem->qty =  $request->qty[$key];
                $orderItem->save();
            }
            $supplyOrder->tax_amount = $totalTaxAmount;
            $supplyOrder->subtotal_amount = $subTotalAmount;
            $supplyOrder->total_amount = $subTotalAmount + $totalTaxAmount;
            $supplyOrder->save();
        }

        // try {
        $data = Helpers::_generateSupplyOrderPDF($supplyOrder, false);
        $supplyOrder->update(['pdf' => $data['pdf']]);
        return redirect($data['url']);
        // } catch (\Throwable $th) { 
        //     //
        // }
        Toastr::success("Purchase order placed successfully");
        return back();
    }
    public function return(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $preset = request('date_range') ?? 'last_3_month';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        $purchased_order_details = SupplyOrderItem::with(['item', 'order', 'order.invoice'])
            ->whereHas('item', function ($q) {
                $q->where('stock', '>', 0);
            })
            ->whereHas('order.invoice')
            ->whereHas('order', function ($q) {
                $q->where('store_id', Helpers::get_store_id());
            })
            ->whereBetween('created_at', [$formatted_from, $formatted_to])
            ->get();
        
        $return_slips = ReturnPurchaseSlip::where('store_id',Helpers::get_store_id())->get();

        return view('vendor-views.inventory.purchase.return', compact('return_slips','purchased_order_details', 'preset'));
    }
    public function return_store(Request $request)
    {
        $request->validate([
            'invoice_id'   => 'required|array|min:1',
            'invoice_id.*' => 'required|string|max:255', // each element must not be empty
        ], [
            'invoice_id.required' => 'Please select invoice id'
        ]);
        $data['vendor_id'] = $request->bill_to;
        $uDetails = StoreCustomer::with('billing_address')->where('id', $request->bill_to)->first();
        $bAddr = $uDetails->billing_address;
        $address = $uDetails->address;
        if ($bAddr) {
            $address  = $bAddr->address1 . ', ' . $bAddr->address2 . ', ' . $bAddr->state . ', ' . $bAddr->city . '- ' . $bAddr->pincode;
        }
        $data['vendor'] = [
            'name' => $uDetails->f_name,
            'address' => $address,
            'email' => $uDetails->email,
            'phone' => $uDetails->phone,
        ];
        $store = Helpers::get_store_data();
        $data['store'] = [
            'name' => $store->name,
            'address' => $store->address,
            'email' => $store->email,
            'phone' => $store->phone,
            'logo' => $store->logo,
        ];
        $invoiceIds = $request->invoice_id; // array of ids
        $invoices = ManualInvoice::whereIn('invoice_id', $invoiceIds)->where('bill_to', Helpers::get_store_id())->where('bill_to_type', 'vendor')
            ->get(['invoice_id', 'invoice_date', 'created_at'])
            ->map(function ($invoice) {
                return [
                    'invoice_id' => $invoice->invoice_id,
                    'date'       => $invoice->einvoice_date ?? $invoice->created_at,
                ];
            });
        $data['invoices'] = $invoices;

        $data['item_name'] = $request->item_name;
        $data['qty'] = $request->qty;
        $data['price'] = $request->price;
        $data['notes'] = $request->notes;
        $data = Helpers::_generatePurchaseReturnPDF($data, false);

        $slip = new ReturnPurchaseSlip();
        $slip->invoice_ids = json_encode($invoiceIds);
        $slip->pdf = $data['pdf'];
        $slip->store_id = Helpers::get_store_id();
        $slip->save();

        // $supplyOrder->update(['pdf' => $data['pdf']]);
        return redirect($data['url']);
    }
    public function  items_in_invoice(Request $request)
    {
        // prx($request->invoice_ids);
        $orderDetails = InvoiceItem::with(['item', 'invoice'])->whereIn('rand_invoice_id', $request->invoice_ids)->get();
        //    prx($orderDetails);
        $html = '';
        foreach ($orderDetails as $key => $detail) {
            if ($detail->invoice?->pdf) {
                $invoice = '<a target="_blank" href="' . asset('storage/app/public/invoice') . '/' . $detail->invoice?->pdf . '"> ' . $detail->invoice?->invoice_id . '</a>';
            } else {
                $invoice =  $detail->invoice?->invoice_id;
            }

            if ($detail->status  == 'returned') {
                $returnBtn = ucfirst($detail->status);
            } else {
                $returnBtn = '<a style="width:fit-content;padding:0 5px !important;"
                data-item-id="' . ($detail->item?->id ?? '') . '"
                data-item-gst="' . ($detail->tax ?? '') . '"
                data-item-stock="' . ($detail->item?->stock ?? '') . '"
                data-item-price="' . ($detail->price ?? '') . '"
                data-item-name="' . ($detail->item?->item_name ?? '') . '"
                data-order-id="' . $detail->id . '"
                class="add_btn add_btn_' . $detail->id . ' btn action-btn btn--primary btn-outline-primary"
                title="Add to Purchase Return">
                <i class="tio-add"></i> Add to Purchase Return
            </a> <span class="text-success added_item_' . $detail->id . '"
                                                    style="display:none;"><i class="tio-checkmark-circle"></i>
                                                    Added</span>';;
            }
            $html .= '<tr>';
            $html .= '<td>' . $key + 1 . '</td>';
            $html .= '<td>' . $detail->created_at->format('Y-m-d H:i') . '</td>';
            $html .= '<td>' . $invoice . '</td>';
            $html .= '<td><a href="' . ($detail->item?->id ?  route('vendor.inventory.item.detail', [$detail->item?->id]) : '#') . '">' . ($detail->item->item_name ?? 'Deleted') . '</a></td>';
            $html .= '<td>' . $detail->qty . '</td>';
            $html .= '<td>' . _price($detail->price) . '</td>';
            $html .= '<td>' .   $returnBtn . '</td>';
            $html .= '</tr>';
        }

        return response()->json(['status' => true, 'data' => $html]);
    }
    public function purchase_orders(Request $request)
    {

        $preset = request('date_range') ?? 'last_30_days';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];
        $search = $request->search ?? '';
        $ids = $request->invoice_ids ?? null;

        $storeId = Helpers::get_store_id();
        $data['po_number_gst'] = Helpers::generatePoNumber('gst')['po_number'];
        $data['po_number_nongst'] = Helpers::generatePoNumber('non-gst')['po_number'];
        $vendors = StoreCustomer::where('store_id', $storeId)->where('user_type', 'vendor')->get();

        // low stock items 
        $low_stock_items = PurchaseOrder::with("inventoryItem")->where('store_id', Helpers::get_store_id())->get();

    // purchase orders 
        $purchase_orders = SupplyOrder::with(["order_items", 'invoice'])->where('store_id', Helpers::get_store_id())->whereBetween('created_at', [$formatted_from, $formatted_to])->get();
        return view('vendor-views.inventory.purchase.orders', compact('data', 'preset', 'purchase_orders', 'low_stock_items', 'vendors'));
    }
    public function purchase_gatepass(Request $request)
    {
        return view('vendor-views.inventory.gatepass.list', compact('data', 'purchase_orders', 'low_stock_items', 'vendors'));
    }

    public function export_purchase_orders(Request $request)
    {
        $storeId = Helpers::get_store_id();

        // purchase orders 
        $purchase_orders = SupplyOrder::with(["order_items", 'invoice'])->where('store_id', Helpers::get_store_id())->get();
        foreach ($purchase_orders as $key => $order) {
            $data[$key] = [
                $key + 1,
                $order->po_number,
                $order->invoice_id,
                _price($order->total_amount),
                $order->purchase_date,
            ];
        }
        // prx($purchase_orders);
        $headings =  ['Sl',  'PO Number',  'Invoice Id', 'Total Amount', 'Purchase Date'];

        return Excel::download(new POExport($data, $headings), 'PO_' .  time() . '.xlsx');
    }

    // Delete a purchase order (SupplyOrder + its items). Optionally remove the
    // corresponding stock from inventory.
    public function purchase_order_delete(Request $request, $id)
    {
        $storeId = Helpers::get_store_id();
        $order = SupplyOrder::with('order_items')->where('store_id', $storeId)->find($id);
        if (!$order) {
            Toastr::error('Purchase order not found');
            return back();
        }

        if (function_exists('_ensureDecimalStockColumns')) {
            _ensureDecimalStockColumns();
        }
        if ($request->restock == '1') {
            foreach ($order->order_items as $it) {
                if (!$it->item_id) {
                    continue;
                }
                $item = \App\Models\InventoryItem::find($it->item_id);
                if ($item) {
                    $item->stock = max(0, (float) $item->stock - (float) $it->qty);
                    $item->save();
                }
            }
        }

        SupplyOrderItem::where('order_table_id', $order->id)->delete();
        $order->delete();

        Toastr::success('Purchase order deleted');
        return back();
    }

    // Delete a purchase return slip. (Return slips do not store structured per-item
    // quantities, so no inventory adjustment is applied here.)
    public function purchase_return_delete(Request $request, $id)
    {
        $storeId = Helpers::get_store_id();
        $slip = ReturnPurchaseSlip::where('store_id', $storeId)->find($id);
        if (!$slip) {
            Toastr::error('Purchase return not found');
            return back();
        }

        if ($slip->pdf && \Illuminate\Support\Facades\Storage::disk('public')->exists('invoice/' . $slip->pdf)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete('invoice/' . $slip->pdf);
        }
        $slip->delete();

        Toastr::success('Purchase return deleted');
        return back();
    }
}
