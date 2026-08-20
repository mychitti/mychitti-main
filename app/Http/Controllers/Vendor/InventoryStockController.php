<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\DTOs\StockEntry;
use App\Models\InventoryOrderDetail;
use App\Models\ItemEntry;
use App\Models\SupplyOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class InventoryStockController extends Controller
{
    public function stock_in_out(Request $request)
    {
        $storeId = Helpers::get_store_id();

        $preset  = $request->input('date_range', 'last_30_days');
        $custom  = $request->input('custom_date_range');
        $range   = Helpers::calculatePresetDates($preset, $custom);

        $formatted_from = $range['start'];
        $formatted_to   = $range['end'];

        // Stock-in: Supply Order Items (created from purchase bills)
        $supply_order_items = SupplyOrderItem::with(['order.invoice', 'item'])
            ->whereHas('order', fn($q) => $q->where('store_id', $storeId))
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

        // Stock-out: damaged / theft / leaked write-offs.
        //
        // Stock leaves when a manager ACCEPTS, so the decision is the movement and its date is
        // decided_at. A pending request has taken nothing off the shelf and a rejected one never
        // will, so neither belongs in a movement ledger. Only the part that actually left counts:
        // a disposition marked "convert to resell" stays in stock.
        //
        // Guarded on the table because the write-off workflow ships with POS Retail; a store that
        // has never used it has nothing to add here.
        $writeoffs = collect();

        if (Schema::hasTable('pos_stock_writeoff')) {
            $accepted = DB::table('pos_stock_writeoff as w')
                ->leftJoin('inventory_items as ii', 'ii.id', '=', 'w.inventory_item_id')
                ->leftJoin('branches as b', 'b.id', '=', 'w.branch_id')
                ->where('w.store_id', $storeId)
                ->where('w.status', 'accepted')
                ->whereNotNull('w.decided_at')
                ->whereBetween('w.decided_at', [$formatted_from, $formatted_to])
                ->get([
                    'w.id', 'w.type', 'w.qty', 'w.status', 'w.decided_at', 'w.inventory_item_id',
                    'ii.item_name', 'ii.stock', 'b.name as branch_name',
                ]);

            $dispositions = ($accepted->isNotEmpty() && Schema::hasTable('pos_writeoff_dispositions'))
                ? DB::table('pos_writeoff_dispositions')
                    ->whereIn('writeoff_id', $accepted->pluck('id'))
                    ->get(['writeoff_id', 'disposition', 'qty'])
                    ->groupBy('writeoff_id')
                : collect();

            $labels = ['return_supplier' => 'Returned to supplier', 'resell' => 'Kept for resale', 'scrap' => 'Scrapped'];

            $writeoffs = $accepted->map(function ($row) use ($dispositions, $labels) {
                $rows = $dispositions->get($row->id, collect());

                // What actually left the shelf.
                $row->qty = (float) $row->qty - (float) $rows->where('disposition', 'resell')->sum('qty');
                if ($row->qty <= 0) {
                    return null;
                }

                $row->dispositions = $rows
                    ->map(fn($d) => ($labels[$d->disposition] ?? $d->disposition) . ' '
                        . rtrim(rtrim(number_format((float) $d->qty, 3), '0'), '.'))
                    ->implode(', ');

                return StockEntry::fromWriteoff($row);
            })->filter()->values();
        }

        // Merge all and sort by date
        $rows = collect()
            ->merge($supply_order_items)
            ->merge($entries)
            ->merge($inventory_orders)
            ->merge($writeoffs)
            ->sortBy('date')
            ->values(); // reset keys

        return view("vendor-views.inventory.stock.stock_in_out", compact('preset', 'rows'));
    }
}
