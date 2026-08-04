<?php

namespace App\Console\Commands;

use App\CentralLogics\Helpers;
use App\Models\InventoryItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Revalue sales lines that were costed from a WRONG purchase price.
 *
 * Distinct from `inventory:backfill-cogs`, which only fills lines that never had a cost. This one
 * overwrites a cost that is already there, and is the only way to repair history after an item's
 * landing_price is corrected — a bag price typed into a per-kg field values every past sale of
 * that item at twenty-odd times its real cost, and nothing else goes back and fixes it.
 *
 * It deliberately revalues at TODAY's landing_price, which is the opposite of what costing
 * normally wants: the cost of a sale is the cost on the day it was sold, and that is why
 * line_cost is captured at billing time in the first place. Running this over items whose price
 * merely changed would rewrite good history with the current price. So it refuses to run without
 * --item unless --force is given: the intended use is a handful of items someone has just fixed.
 *
 *   php artisan inventory:recompute-cogs --store=5923 --item=1416 --dry-run
 *   php artisan inventory:recompute-cogs --store=5923 --item=1416,1199
 *   php artisan inventory:recompute-cogs --store=5923 --item=1416 --from=2026-08-01
 */
class RecomputeInventoryCogs extends Command
{
    protected $signature = 'inventory:recompute-cogs
        {--store= : Store id (required)}
        {--item= : Comma-separated inventory item ids to revalue}
        {--from= : Only lines billed on or after this date}
        {--to= : Only lines billed on or before this date}
        {--force : Allow running across every item in the store}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Revalue sales lines after an item\'s purchase price has been corrected';

    public function handle(): int
    {
        Helpers::_ensureInvOrderCostColumns();

        if (!Schema::hasColumn('inventory_order_details', 'line_cost')) {
            $this->error('inventory_order_details has no line_cost column.');
            return self::FAILURE;
        }

        $storeId = (int) $this->option('store');
        if (!$storeId) {
            $this->error('--store is required.');
            return self::FAILURE;
        }

        $itemIds = array_values(array_filter(array_map(
            'intval',
            explode(',', (string) $this->option('item'))
        )));

        if (!$itemIds && !$this->option('force')) {
            $this->error('--item is required. Recomputing every item would rewrite correctly-costed history at today\'s prices.');
            $this->line('Pass --force only if you genuinely mean the whole store.');
            return self::FAILURE;
        }

        $dry    = (bool) $this->option('dry-run');
        $hasVar = Schema::hasColumn('invoice_items', 'variation_type');

        $query = DB::table('inventory_order_details as d')
            ->join('inventory_orders as o', 'o.order_id', '=', 'd.order_id')
            ->where('o.store_id', $storeId)
            ->when($itemIds, fn($q) => $q->whereIn('d.item_id', $itemIds))
            ->when($this->option('from'), fn($q) => $q->whereDate('d.created_at', '>=', $this->option('from')))
            ->when($this->option('to'), fn($q) => $q->whereDate('d.created_at', '<=', $this->option('to')))
            ->select('d.id', 'd.item_id', 'd.qty', 'd.unit_price', 'd.line_cost', 'o.invoice_id');

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('No sales lines matched.');
            return self::SUCCESS;
        }

        $this->info(($dry ? '[dry run] ' : '') . 'Revaluing ' . $total . ' sales line(s)…');
        $bar = $this->output->createProgressBar($total);

        $items   = [];
        $updated = 0;
        $skipped = 0;
        $wasCost = 0.0;
        $nowCost = 0.0;

        $query->orderBy('d.id')->chunk(500, function ($rows) use (
            &$items, &$updated, &$skipped, &$wasCost, &$nowCost, $dry, $hasVar, $bar
        ) {
            foreach ($rows as $row) {
                $bar->advance();

                $itemId = (int) $row->item_id;
                if (!array_key_exists($itemId, $items)) {
                    $items[$itemId] = InventoryItem::find($itemId);
                }
                $item = $items[$itemId];

                // Never write a zero cost — an item with no purchase price on record would turn
                // every one of its sales into pure profit, which is a worse lie than the one
                // being corrected.
                if (!$item || (float) ($item->landing_price ?? 0) <= 0) {
                    $skipped++;
                    continue;
                }

                // The pack size lives on the billed line, not the sale-order mirror. Matched on
                // price and quantity too, so one invoice carrying the same product at two sizes
                // stays distinct.
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

                $wasCost += (float) ($row->line_cost ?? 0);
                $nowCost += $cost;

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

        $this->info(($dry ? 'Would revalue ' : 'Revalued ') . $updated . ' line(s).');
        if ($skipped) {
            $this->warn($skipped . ' line(s) skipped — item deleted or no purchase price on record.');
        }

        $this->line('Cost before: ' . number_format($wasCost, 2));
        $this->line('Cost after:  ' . number_format($nowCost, 2));
        $this->line('Difference:  ' . number_format($nowCost - $wasCost, 2)
            . ($nowCost < $wasCost ? '  (profit improves by ' . number_format($wasCost - $nowCost, 2) . ')' : ''));

        return self::SUCCESS;
    }
}
