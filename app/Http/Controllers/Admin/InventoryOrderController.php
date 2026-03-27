<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Exports\InventoryOrderExport;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class InventoryOrderController extends Controller
{
    public function sale_orders(Request $request)
    {
        $preset = request('date_range') ?? 'last_30_days';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        $search = $request->search ?? '';
        $storeId = 0;
        $query = InventoryOrderDetail::with(['order', 'item', 'order.invoice'])->whereHas('order', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })->whereBetween('created_at', [$formatted_from, $formatted_to]);
        if ($search) {
            $query->where(function ($q) use ($search) {
                // Match item name
                $q->whereHas('item', function ($q2) use ($search) {
                    $q2->where('item_name', 'like', '%' . $search . '%');
                })
                    // OR match order_id
                    ->orWhereHas('order', function ($q3) use ($search) {
                        $q3->where('invoice_id', 'like', '%' . $search . '%');
                    });
            });
        }

        $sale_order_items = $query->paginate(50);
        $sales = Helpers::inv_order_calendar();
        return view('admin-views.inventory.sale.orders', compact('sales', 'preset', 'sale_order_items'));
    }
    public function  order_return(Request $request)
    {
        $preset = request('date_range') ?? 'last_30_days';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        $search = $request->search ?? '';

        $storeId = 0;
        $orders = InventoryOrder::where('store_id', $storeId)->get();

        $returned_orders = InventoryOrderDetail::with(['order', 'item'])->whereHas('order', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })->when($search, function ($q) use ($search) {
            $q->whereHas('item', function ($q) use ($search) {
                $q->where('item_name', 'like', '%' . $search . '%'); // adjust 'name' to actual column
            })
                ->orWhereHas('order', function ($q) use ($search) {
                    $q->where('invoice_id', 'like', '%' . $search . '%'); // adjust 'order_number' to actual column
                });
        })

            ->whereBetween('created_at', [$formatted_from, $formatted_to])->where('status', 'returned')->get();

        return view('admin-views.inventory.sale.order_return', compact('preset', 'orders', 'returned_orders'));
    }
    public function  order_details_fetch(Request $request)
    {
        $orderDetails = InventoryOrderDetail::with(['order', 'item'])->where('order_id', $request->order_id)->get();
        $html = '';
        foreach ($orderDetails as $key => $detail) {
            if ($detail->order?->invoice?->pdf) {
                $invoice = '<a target="_blank" href="' . asset('storage/app/public/invoice') . '/' . $detail->order?->invoice?->pdf . '"> ' . $detail->order?->invoice?->invoice_id . '</a>';
            } else {
                $invoice =  $detail->order?->invoice?->invoice_id;
            }

            if ($detail->status  == 'returned') {
                $returnBtn = ucfirst($detail->status);
            } else {
                $returnBtn = '
                <a class="btn_sm btn btn-outline-warning form-alert" href="javascript:;" 
                    data-id="completed-' . $detail['id'] . '" 
                    data-message="Want to mark this order item as returned" 
                    title="Completed"> 
                    <i class="tio-checkmark-circle-outlined"></i> Return
                </a>
                 <form action="' . route('admin.inventory.sale.order-status', [$detail['id'], 'returned']) . '" 
                    method="get" 
                    id="completed-' . $detail['id'] . '">
                </form>';
            }
            $html .= '<tr>'; 
            $html .= '<td>' . $key + 1 . '</td>';
            $html .= '<td>' . $detail->created_at->format('Y-m-d H:i') . '</td>'; 
            $html .= '<td>' . $invoice . '</td>';
            $html .= '<td><a href="' . ($detail->item?->id ?  route('admin.inventory.item.detail', [$detail->item?->id]) : '#') . '">' . ($detail->item->item_name ?? 'Deleted') . '</a></td>';
            $html .= '<td>' . $detail->qty . '</td>';
            $html .= '<td>' . _price($detail->unit_price) . '</td>';
            $html .= '<td>' . _price($detail->total_price) . '</td>';
            $html .= '<td>' .   $returnBtn . '</td>';
            $html .= '</tr>';
        }

        return response()->json(['status' => true, 'data' => $html]);
    }
    public function  sale_order_export(Request $request, $return = false)
    {
       
            $storeId = 0;
            $order_items = InventoryOrderDetail::with(['order', 'item'])->whereHas('order', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })->when($return, function ($q) {
                $q->where('status', 'returned');
            })
                ->get();
            $headings = [
                'sl',
                'Invoice Id',
                'Item',
                'Qty',
                'Unit Price',
                'Status',
                'Created At'
            ];

            $data = [];

            foreach ($order_items as $key => $item) {
                $data[] = [
                    $key + 1,
                    $item->order?->invoice_id,
                    $item->item?->item_name,
                    $item->qty,
                    $item->unit_price,
                    ucfirst($item->status),
                    $item->created_at,
                ];
            }
            return Excel::download(new InventoryOrderExport($data, $headings), 'Inventory_Orders.xlsx');
       
    }
    public function  sale_order_status(Request $request, $id, $status)
    {
        $order_detail = InventoryOrderDetail::find($id);
        $order_detail->status = $status;
        if ($status == 'returned' || $status == 'cancelled') {
            $inventoryItem = InventoryItem::where('id', $order_detail->item_id)->first();
            if ($inventoryItem) {
                $inventoryItem->stock =  $inventoryItem->stock + $order_detail->qty;
                $inventoryItem->save();
            }
        }
        $order_detail->save();

        Toastr::success('Status Changed Successfully');
        return back();
    }
}
