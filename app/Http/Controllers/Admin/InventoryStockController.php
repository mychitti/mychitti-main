<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\DTOs\StockEntry;
use App\Models\InventoryOrderDetail;
use App\Models\ItemEntry;
use App\Models\SupplyOrderItem;


class InventoryStockController extends Controller
{
    public function stock_in_out(Request $request)
    {
        $storeId = 0;

        $preset  = $request->input('date_range', 'last_30_days');
        $custom  = $request->input('custom_date_range');
        $range   = Helpers::calculatePresetDates($preset, $custom);

        $formatted_from = $range['start'];
        $formatted_to   = $range['end'];

        // Stock-in: Supply Order Items
        $supply_order_items = SupplyOrderItem::with(['order.invoice', 'item'])
            ->whereHas('order', fn($q) => $q->where('store_vendor_id', $storeId))
            ->whereBetween('created_at', [$formatted_from, $formatted_to])
            ->get()
            ->map(fn($item) => StockEntry::fromSupplyOrderItem($item));

        // Stock-in: Item Entries
        $entries = ItemEntry::with(['item', 'invoice'])
            ->where('store_id', $storeId)
            ->whereBetween('created_at', [$formatted_from, $formatted_to])
            ->get()
            ->map(fn($item) => StockEntry::fromItemEntry($item));

        // Stock-out: Inventory Orders
        $inventory_orders = InventoryOrderDetail::with(['order.invoice', 'item'])
            ->whereHas('order', fn($q) => $q->where('store_id', $storeId))
            ->whereBetween('created_at', [$formatted_from, $formatted_to])
            ->get()
            ->map(fn($item) => StockEntry::fromInventoryOrderDetail($item));

        // Merge all and sort by date
        $rows = collect()
            ->merge($supply_order_items)
            ->merge($entries)
            ->merge($inventory_orders)
            ->sortBy('date')
            ->values(); // reset keys

        return view("admin-views.inventory.stock.stock_in_out", compact('preset', 'rows'));
    }
}
