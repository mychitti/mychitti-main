<?php

namespace App\Console\Commands;

use App\CentralLogics\Helpers;
use App\Models\InventoryItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fills in `base_qty` and `line_cost` on sales lines billed before the Profit & Loss report
 * started recording them.
 *
 * Why they were wrong: `inventory_order_details.qty` is whatever unit the line was billed in. For
 * a measured variation that is a count of packs — three 250g packs off a per-kg item is qty 3 —
 * while `landing_price` is per kilo. The report multiplied the two and valued three packs as three
 * kilos, which is how a store with healthy margins ends up looking like it lost money on every
 * line. The variation that decides the conversion lives on `invoice_items`, so this walks back to
 * the billed line to recover it.
 *
 * Safe to run repeatedly: only touches rows that have no line_cost yet, and never writes a cost of
 * zero (a line whose item or purchase price is gone keeps falling back to the old behaviour rather
 * than being reported as pure profit).
 *
 *   php artisan inventory:backfill-cogs            # everything
 *   php artisan inventory:backfill-cogs --store=12 # one store
 *   php artisan inventory:backfill-cogs --dry-run  # report only, write nothing
 */
class BackfillInventoryCogs extends Command
{
    protected $signature = 'inventory:backfill-cogs
        {--store= : Only this store id}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Recompute cost of goods sold on historical inventory sales lines';

    public function handle(): int
    {
        Helpers::_ensureInvOrderCostColumns();

        if (!Schema::hasColumn('inventory_order_details', 'line_cost')) {
            $this->error('inventory_order_details has no line_cost column and it could not be added.');
            return self::FAILURE;
        }

        $dry     = (bool) $this->option('dry-run');
        $storeId = $this->option('store') ? (int) $this->option('store') : null;
        $hasVar  = Schema::hasColumn('invoice_items', 'variation_type');

        $query = DB::table('inventory_order_details as d')
            ->join('inventory_orders as o', 'o.order_id', '=', 'd.order_id')
            ->whereNull('d.line_cost')
            ->when($storeId, fn($q) => $q->where('o.store_id', $storeId))
            ->select('d.id', 'd.item_id', 'd.qty', 'd.unit_price', 'o.invoice_id', 'o.store_id');

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('Nothing to backfill — every sales line already carries its cost.');
            return self::SUCCESS;
        }

        $this->info(($dry ? '[dry run] ' : '') . 'Recomputing cost on ' . $total . ' sales line(s)…');
        $bar = $this->output->createProgressBar($total);

        $items   = [];
        $updated = 0;
        $skipped = 0;
        $flipped = 0;

        $query->orderBy('d.id')->chunk(500, function ($rows) use (&$items, &$updated, &$skipped, &$flipped, $dry, $hasVar, $bar) {
            foreach ($rows as $row) {
                $bar->advance();

                $itemId = (int) $row->item_id;
                if (!array_key_exists($itemId, $items)) {
                    $items[$itemId] = InventoryItem::find($itemId);
                }
                $item = $items[$itemId];

                if (!$item || (float) ($item->landing_price ?? 0) <= 0) {
                    $skipped++;
                    continue;
                }

                // The variation only exists on the billed line, which is what makes the pack
                // conversion recoverable at all. Matched on price and quantity as well as item so
                // an invoice carrying the same product twice at different sizes stays distinct.
                $varType = null;
                if ($hasVar && $row->invoice_id) {
                    $varType = DB::table('invoice_items')
                        ->where('rand_invoice_id', $row->invoice_id)
                        ->where('inv_id', $itemId)
                        ->where('qty', $row->qty)
                        ->where('price', $row->unit_price)
                        ->value('variation_type');
                }

                $baseQty = (float) _stockQtyForLine($item, (float) $row->qty, $item->unit, $varType);
                $cost    = round($baseQty * (float) $item->landing_price, 4);

                // Only worth reporting the ones that actually change the answer.
                $oldCost = (float) $row->qty * (float) $item->landing_price;
                $revenue = (float) $row->qty * (float) $row->unit_price;
                if (($revenue - $oldCost) < 0 && ($revenue - $cost) >= 0) {
                    $flipped++;
                }

                if (!$dry) {
                    DB::table('inventory_order_details')->where('id', $row->id)->update([
                        'base_qty'  => round($baseQty, 4),
                        'line_cost' => $cost,
                    ]);
                }
                $updated++;
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info(($dry ? 'Would update ' : 'Updated ') . $updated . ' line(s).');
        if ($flipped) {
            $this->info($flipped . ' line(s) were reported as a loss and are actually profitable.');
        }
        if ($skipped) {
            $this->warn($skipped . ' line(s) skipped — item deleted or no purchase price on record. '
                . 'These keep the old calculation.');
        }

        return self::SUCCESS;
    }
}
