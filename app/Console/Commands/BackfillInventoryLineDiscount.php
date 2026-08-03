<?php

namespace App\Console\Commands;

use App\CentralLogics\Helpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fills in `line_discount` on sales lines billed before the sale order started recording it.
 *
 * Bill-level discounts — the manual one, item offers and coupons — are held on the invoice, not on
 * the line. The sale-order mirror copied gross prices, so the Profit & Loss report counted revenue
 * the till never took and could not be reconciled against the POS sales figure. Each line's share
 * is its value over the invoice's total line value, so a bill split across several sale orders
 * (pharmacy dispense) still allocates the discount exactly once.
 *
 * Safe to run repeatedly: only touches rows with no line_discount yet.
 *
 *   php artisan inventory:backfill-line-discount            # everything
 *   php artisan inventory:backfill-line-discount --store=12 # one store
 *   php artisan inventory:backfill-line-discount --dry-run  # report only, write nothing
 */
class BackfillInventoryLineDiscount extends Command
{
    protected $signature = 'inventory:backfill-line-discount
        {--store= : Only this store id}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Apportion bill-level discounts onto historical inventory sales lines';

    public function handle(): int
    {
        Helpers::_ensureInvOrderCostColumns();

        if (!Schema::hasColumn('inventory_order_details', 'line_discount')) {
            $this->error('inventory_order_details has no line_discount column and it could not be added.');
            return self::FAILURE;
        }

        $dry     = (bool) $this->option('dry-run');
        $storeId = $this->option('store') ? (int) $this->option('store') : null;

        $query = DB::table('inventory_orders as o')
            ->join('manual_invoices as mi', function ($j) {
                $j->on('mi.invoice_id', '=', 'o.invoice_id')->on('mi.vendor_id', '=', 'o.store_id');
            })
            ->when($storeId, fn($q) => $q->where('o.store_id', $storeId))
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))->from('inventory_order_details as d')
                    ->whereColumn('d.order_id', 'o.order_id')->whereNull('d.line_discount');
            })
            ->select('o.id', 'o.order_id', 'mi.id as invoice_pk', 'mi.discount_amount');

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('Nothing to backfill — every sales line already carries its discount share.');
            return self::SUCCESS;
        }

        $this->info(($dry ? '[dry run] ' : '') . 'Apportioning discounts across ' . $total . ' sale order(s)…');
        $bar = $this->output->createProgressBar($total);

        $orders    = 0;
        $discounted = 0;
        $allocated = 0.0;

        $query->orderBy('o.id')->chunk(500, function ($rows) use (&$orders, &$discounted, &$allocated, $dry, $bar) {
            foreach ($rows as $row) {
                $bar->advance();
                $orders++;

                $discount = (float) ($row->discount_amount ?? 0);
                $rate = 0.0;

                if ($discount > 0) {
                    $lineTotal = (float) DB::table('invoice_items')
                        ->where('manual_invoice_id', $row->invoice_pk)
                        ->selectRaw('COALESCE(SUM(price * qty), 0) as line_total')->value('line_total');

                    if ($lineTotal > 0) {
                        $rate = min(1, $discount / $lineTotal);
                        $discounted++;
                    }
                }

                $pending = DB::table('inventory_order_details')
                    ->where('order_id', $row->order_id)->whereNull('line_discount');

                if ($rate > 0) {
                    $allocated += (float) (clone $pending)
                        ->selectRaw('COALESCE(SUM(COALESCE(total_price, qty * unit_price)), 0) * ? as d', [$rate])
                        ->value('d');
                }

                if (!$dry) {
                    $pending->update([
                        'line_discount' => DB::raw('ROUND(COALESCE(total_price, qty * unit_price) * ' . $rate . ', 4)'),
                    ]);
                }
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info(($dry ? 'Would update ' : 'Updated ') . $orders . ' sale order(s).');
        $this->info($discounted . ' carried a bill discount — ' . round($allocated, 2)
            . ' total taken off reported revenue.');

        return self::SUCCESS;
    }
}
