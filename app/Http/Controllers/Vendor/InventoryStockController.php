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

        // Stock-out: damaged / theft / leaked write-offs. The quantity leaves inventory when the
        // request is raised, so that is the movement — a scrap or return-to-supplier disposition
        // simply keeps it out. Guarded on the table because the write-off workflow ships with POS
        // Retail; a store that has never used it has nothing to add here.
        $writeoffs = collect();
        $writeoff_returns = collect();

        if (Schema::hasTable('pos_stock_writeoff')) {
            $dispositions = Schema::hasTable('pos_writeoff_dispositions')
                ? DB::table('pos_writeoff_dispositions as d')
                    ->join('pos_stock_writeoff as w', 'w.id', '=', 'd.writeoff_id')
                    ->where('w.store_id', $storeId)
                    ->get(['d.writeoff_id', 'd.disposition', 'd.qty'])
                    ->groupBy('writeoff_id')
                : collect();

            $labels = ['return_supplier' => 'Returned to supplier', 'resell' => 'Converted to resell', 'scrap' => 'Scrapped'];

            $writeoffs = DB::table('pos_stock_writeoff as w')
                ->leftJoin('inventory_items as ii', 'ii.id', '=', 'w.inventory_item_id')
                ->leftJoin('branches as b', 'b.id', '=', 'w.branch_id')
                ->where('w.store_id', $storeId)
                ->whereBetween('w.created_at', [$formatted_from, $formatted_to])
                ->get([
                    'w.id', 'w.type', 'w.qty', 'w.status', 'w.created_at', 'w.inventory_item_id',
                    'ii.item_name', 'ii.stock', 'b.name as branch_name',
                ])
                ->map(function ($row) use ($dispositions, $labels) {
                    $row->dispositions = $dispositions->get($row->id, collect())
                        ->map(fn($d) => ($labels[$d->disposition] ?? $d->disposition) . ' '
                            . rtrim(rtrim(number_format((float) $d->qty, 3), '0'), '.'))
                        ->implode(', ');
                    return StockEntry::fromWriteoff($row);
                });

            // …and what came back: a rejected request restores the whole quantity, an accepted one
            // restores only the part a manager marked "convert to resell".
            $decided = DB::table('pos_stock_writeoff as w')
                ->leftJoin('inventory_items as ii', 'ii.id', '=', 'w.inventory_item_id')
                ->where('w.store_id', $storeId)
                ->whereNotNull('w.decided_at')
                ->whereBetween('w.decided_at', [$formatted_from, $formatted_to])
                ->get(['w.id', 'w.qty', 'w.status', 'w.decided_at', 'w.inventory_item_id', 'ii.item_name', 'ii.stock']);

            $writeoff_returns = $decided->map(function ($row) use ($dispositions) {
                if ($row->status === 'rejected') {
                    return StockEntry::fromWriteoffReturn($row, $row->qty, 'rejected, stock returned');
                }
                $resold = $dispositions->get($row->id, collect())
                    ->where('disposition', 'resell')->sum('qty');

                return $resold > 0 ? StockEntry::fromWriteoffReturn($row, $resold, 'converted to resell') : null;
            })->filter()->values();
        }

        // Merge all and sort by date
        $rows = collect()
            ->merge($supply_order_items)
            ->merge($entries)
            ->merge($inventory_orders)
            ->merge($writeoffs)
            ->merge($writeoff_returns)
            ->sortBy('date')
            ->values(); // reset keys

        return view("vendor-views.inventory.stock.stock_in_out", compact('preset', 'rows'));
    }
}
