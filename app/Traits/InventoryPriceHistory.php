<?php

namespace App\Traits;

use App\Models\InventoryOrderDetail;
use App\Models\ItemEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait InventoryPriceHistory
{
    /**
     * Every purchase and selling price an inventory item has been given, and the average of the
     * ones money actually changed hands at.
     *
     * Two kinds of price point, and only one of them is averaged:
     *
     *  - Transactions. Goods bought at a price — on a purchase bill, or on a stock entry — and the
     *    item sold at a price. These are counted.
     *  - Prices that were merely set: the item created with a price, that price edited, or a stock
     *    entry setting the selling price. These are listed for context and not counted.
     *
     * The split exists because a price typed on the item form has no stock behind it — an item is
     * created before anything is bought, so its opening figure is an intention rather than a deal.
     * Averaging it in drags the answer away from what the store really buys and sells at: created
     * at 100, then bought at 120 and 140, averages 130 across the two real purchases, not 120
     * across all three numbers.
     *
     * The average is a plain mean over the counted points, deliberately not weighted by quantity.
     * Weighting answers a different question — what the stock on hand cost — and would let one
     * large intake drown out every other price the item was traded at.
     *
     * Sources cannot overlap: `inventory_item_price_logs` records writes to the item row, stock
     * entries carry their own prices and are stopped from logging the item save they trigger (see
     * _withoutItemPriceLog), and sale lines are a different table again.
     *
     * Lives in a trait because the item detail page is served by four controllers — the vendor
     * one, the HMIS and POS subclasses of it, and Laundry's standalone copy — and an average that
     * differs between modules is worse than no average at all.
     */
    protected function item_price_history($item): array
    {
        $blank = ['average' => null, 'min' => null, 'max' => null, 'count' => 0];
        $history = [
            'purchase_points' => collect(),
            'sell_points' => collect(),
            'purchase' => $blank,
            'sell' => $blank,
            'margin' => null,
            'margin_percent' => null,
        ];

        if (!$item) {
            return $history;
        }

        _ensureItemPriceLogTable();

        $sort = fn($point) => $this->point_sort_key($point);

        $history['purchase_points'] = $this->with_previous_prices(
            $this->logged_price_points($item, 'purchase')
                ->concat($this->entry_price_points($item, 'landing_price', 'Purchased', true))
                ->concat($this->billed_purchase_points($item))
                ->sortByDesc($sort)
                ->values()
        );

        $history['sell_points'] = $this->with_previous_prices(
            $this->logged_price_points($item, 'sell')
                ->concat($this->entry_price_points($item, 'selling_price', 'Priced at stock entry', false))
                ->concat($this->sold_price_points($item))
                ->sortByDesc($sort)
                ->values()
        );

        $history['purchase'] = $this->price_summary($history['purchase_points']);
        $history['sell'] = $this->price_summary($history['sell_points']);

        $purchaseAvg = $history['purchase']['average'];
        $sellAvg = $history['sell']['average'];

        if ($purchaseAvg !== null && $sellAvg !== null) {
            $history['margin'] = $sellAvg - $purchaseAvg;
            $history['margin_percent'] = $purchaseAvg > 0 ? ($history['margin'] / $purchaseAvg) * 100 : null;
        }

        return $history;
    }

    /**
     * Prices written onto the item row itself — the value it was created with, and every edit
     * since. Context only: nothing was bought or sold at these, they are what the item was
     * listed at.
     */
    protected function logged_price_points($item, string $type)
    {
        if (!Schema::hasTable('inventory_item_price_logs')) {
            return collect();
        }

        return DB::table('inventory_item_price_logs')
            ->where('item_id', $item->id)
            ->where('price_type', $type)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn($row) => [
                'at' => $row->created_at,
                'seq' => (int) $row->id,
                'price' => (float) $row->new_price,
                'old_price' => $row->old_price !== null ? (float) $row->old_price : null,
                'label' => match ($row->source) {
                    'create' => 'Item created',
                    // Today's price, seeded for items that predate the price log — the point the
                    // history opens at, not a claim about when it was set.
                    'opening' => 'Opening price',
                    default => 'Price edited',
                },
                'reference' => null,
                'variation' => null,
                'counted' => false,
            ]);
    }

    /**
     * Prices on a stock entry. The purchase price is a transaction — that is what the goods cost
     * — so it counts. The selling price on the same row is only the item being re-listed, so it
     * does not.
     */
    protected function entry_price_points($item, string $column, string $label, bool $counted)
    {
        return ItemEntry::where('item_id', $item->id)
            ->where('store_id', $item->store_id)
            ->where($column, '>', 0)
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->map(fn($entry) => [
                'at' => $entry->date ?: $entry->created_at,
                'seq' => (int) $entry->id,
                'price' => (float) $entry->{$column},
                'old_price' => null,
                'label' => $label,
                'reference' => $entry->bill_number ?: null,
                'variation' => $entry->variation_type ?: null,
                'counted' => $counted,
            ]);
    }

    /**
     * Prices paid on a purchase bill. For most stores this is *the* purchase record: booking a
     * bill runs the stock-in itself (save_purchase_invoice → _incrementInventoryStock) and never
     * writes an ItemEntry, so the price paid exists only on the bill line. A store that never
     * uses the separate stock-entry form would otherwise have no purchase history at all.
     *
     * The bill also leaves `inventory_items.landing_price` untouched, which is why the item's
     * current purchase price can sit well away from what it was last actually bought for.
     */
    protected function billed_purchase_points($item)
    {
        if (!Schema::hasTable('invoice_items') || !Schema::hasTable('manual_invoices')) {
            return collect();
        }

        // A stock entry can be booked against a purchase bill by its invoice id
        // (ItemEntry::invoice() joins bill_number to manual_invoices.invoice_id). Where that
        // happened the entry already carries the price, so counting the bill too would turn one
        // purchase into two price points.
        $bookedIn = ItemEntry::where('item_id', $item->id)
            ->whereNotNull('bill_number')
            ->where('bill_number', '!=', '')
            ->pluck('bill_number')
            ->all();

        return DB::table('invoice_items as ii')
            ->join('manual_invoices as mi', 'mi.id', '=', 'ii.manual_invoice_id')
            ->where('ii.inv_id', $item->id)
            ->where('ii.price', '>', 0)
            ->where('mi.bill_to', $item->store_id)
            ->where('mi.bill_to_type', 'vendor')
            ->when($bookedIn, fn($q) => $q->whereNotIn('mi.invoice_id', $bookedIn))
            ->orderBy('mi.invoice_date')
            ->orderBy('ii.id')
            ->select('ii.id', 'ii.price', 'mi.invoice_id', 'mi.invoice_date', 'mi.created_at')
            ->get()
            ->map(fn($row) => [
                'at' => $row->invoice_date ?: $row->created_at,
                'seq' => (int) $row->id,
                'price' => (float) $row->price,
                'old_price' => null,
                'label' => 'Purchase bill',
                'reference' => $row->invoice_id ?: null,
                'variation' => null,
                'counted' => true,
            ]);
    }

    /**
     * Prices the item was actually sold at. These can differ from what it was listed at — a
     * discount at the counter is still the price it went out the door for, which is exactly why
     * these are the ones worth averaging.
     */
    protected function sold_price_points($item)
    {
        return InventoryOrderDetail::query()
            ->join('inventory_orders', 'inventory_orders.order_id', '=', 'inventory_order_details.order_id')
            ->where('inventory_orders.store_id', $item->store_id)
            ->where('inventory_order_details.item_id', $item->id)
            ->where('inventory_order_details.unit_price', '>', 0)
            // A NULL status is an ordinary sale line, so it has to be spelled out — `NOT IN` on a
            // NULL is NULL, which would silently drop those rows from the history and the average.
            ->where(function ($q) {
                $q->whereNull('inventory_order_details.status')
                    ->orWhereNotIn('inventory_order_details.status', ['returned', 'cancelled']);
            })
            ->select('inventory_order_details.*', 'inventory_orders.invoice_id as sale_invoice_id')
            ->orderBy('inventory_order_details.created_at')
            ->orderBy('inventory_order_details.id')
            ->get()
            ->map(fn($sale) => [
                'at' => $sale->created_at,
                'seq' => (int) $sale->id,
                'price' => (float) $sale->unit_price,
                'old_price' => null,
                'label' => 'Sold',
                'reference' => $sale->sale_invoice_id ?: $sale->order_id,
                'variation' => null,
                'counted' => true,
            ]);
    }

    /**
     * Fill each row's "was" from the point below it, so the column reads as a running progression
     * instead of sitting blank on every row that is not an edit.
     *
     * Only the price-log rows ever carried an old price of their own, because only they record a
     * column being overwritten — a purchase bill or a sale has no "previous" to store. But in a
     * list ordered in time, the row beneath one *is* its previous price, which is what the column
     * is asking. The oldest row has nothing before it and keeps whatever its own record knew,
     * which for a freshly created item is nothing.
     *
     * Expects $points newest first, so the previous price of row i is row i + 1.
     */
    protected function with_previous_prices($points)
    {
        $ordered = $points->values();
        $total = $ordered->count();

        for ($i = 0; $i < $total; $i++) {
            $row = $ordered[$i];
            $previous = ($i + 1) < $total ? $ordered[$i + 1]['price'] : null;
            $row['old_price'] = $previous ?? $row['old_price'];
            $ordered[$i] = $row;
        }

        return $ordered;
    }

    /**
     * Newest first, and deterministic when two points share a moment.
     *
     * Dates arrive in different shapes: a bill carries `invoice_date`, which is a date with no
     * time, while a price-log row carries a full timestamp. Three bills raised on the same day
     * therefore all tie on date alone, and a plain sort left them in the order they were read —
     * oldest first, inside a list that reads newest first. Normalising to one format and breaking
     * the tie on the source row id puts the last bill raised at the top, where it belongs.
     */
    protected function point_sort_key(array $point): string
    {
        try {
            $at = $point['at']
                ? \Carbon\Carbon::parse($point['at'])->format('Y-m-d H:i:s')
                : '0000-00-00 00:00:00';
        } catch (\Throwable $e) {
            $at = '0000-00-00 00:00:00';
        }

        return $at . '|' . str_pad((string) ($point['seq'] ?? 0), 12, '0', STR_PAD_LEFT);
    }

    protected function price_summary($points): array
    {
        $prices = $points->filter(fn($p) => $p['counted'] && $p['price'] > 0)->pluck('price')->values();

        if ($prices->isEmpty()) {
            return ['average' => null, 'min' => null, 'max' => null, 'count' => 0];
        }

        return [
            'average' => $prices->sum() / $prices->count(),
            'min' => $prices->min(),
            'max' => $prices->max(),
            'count' => $prices->count(),
        ];
    }
}
