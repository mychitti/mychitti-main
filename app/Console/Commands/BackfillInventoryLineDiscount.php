<?php

namespace App\Console\Commands;

use App\CentralLogics\Helpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fills in `line_discount` on sales lines billed before the sale order started recording it.
 *
 * Two kinds of discount were being lost. Bill-level ones (manual, offer, coupon) sit on the invoice
 * and never reached the line. Per-line ones — what a cashier takes off a single item — were
 * subtracted from the bill total and then discarded outright: the line kept its full unit price and
 * nothing recorded the reduction. Either way the sale-order mirror carried gross prices, so the
 * Profit & Loss report counted revenue the till never took.
 *
 * Neither is stored on historical lines, but their total per invoice is recoverable. The till
 * computed the bill as `total_amount = round(subtotal + tax - bill_discount)`, so
 *
 *     subtotal = total_amount - round_off - final_tax + discount_amount
 *
 * and whatever the invoice's own lines add up to above that subtotal is exactly the discount that
 * went missing. It is apportioned back across the lines by value — the original split between lines
 * is unrecoverable, so a single heavily-discounted item spreads across the bill, but every invoice
 * total and every report total comes out right.
 *
 * Invoices carrying tax-inclusive lines are skipped: their taxable value is the line value divided
 * down by the rate, so the same subtraction would read tax as discount.
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

    protected $description = 'Recover discounts onto historical inventory sales lines';

    public function handle(): int
    {
        Helpers::_ensureInvOrderCostColumns();

        if (!Schema::hasColumn('inventory_order_details', 'line_discount')) {
            $this->error('inventory_order_details has no line_discount column and it could not be added.');
            return self::FAILURE;
        }

        $dry     = (bool) $this->option('dry-run');
        $storeId = $this->option('store') ? (int) $this->option('store') : null;
        $hasGstStatus = Schema::hasColumn('invoice_items', 'gst_status');

        $query = DB::table('inventory_orders as o')
            ->join('manual_invoices as mi', function ($j) {
                $j->on('mi.invoice_id', '=', 'o.invoice_id')->on('mi.vendor_id', '=', 'o.store_id');
            })
            ->when($storeId, fn($q) => $q->where('o.store_id', $storeId))
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))->from('inventory_order_details as d')
                    ->whereColumn('d.order_id', 'o.order_id')->whereNull('d.line_discount');
            })
            ->select('o.id', 'o.order_id', 'mi.id as invoice_pk', 'mi.total_amount',
                'mi.round_off', 'mi.final_tax', 'mi.discount_amount');

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('Nothing to backfill — every sales line already carries its discount share.');
            return self::SUCCESS;
        }

        $this->info(($dry ? '[dry run] ' : '') . 'Recovering discounts across ' . $total . ' sale order(s)…');
        $bar = $this->output->createProgressBar($total);

        $orders     = 0;
        $discounted = 0;
        $skipped    = 0;
        $allocated  = 0.0;
        $rates      = [];

        $query->orderBy('o.id')->chunk(500, function ($rows) use (
            &$orders, &$discounted, &$skipped, &$allocated, &$rates, $dry, $hasGstStatus, $bar
        ) {
            foreach ($rows as $row) {
                $bar->advance();
                $orders++;

                // One invoice can own several sale orders (a bill appended to over time), so the
                // rate is worked out once and reused — otherwise each batch would take the whole
                // invoice's discount.
                if (!array_key_exists($row->invoice_pk, $rates)) {
                    $rates[$row->invoice_pk] = $this->invoiceDiscountRate($row, $hasGstStatus);
                }
                $rate = $rates[$row->invoice_pk];

                if ($rate === null) {
                    $skipped++;
                    continue;
                }

                $pending = DB::table('inventory_order_details')
                    ->where('order_id', $row->order_id)->whereNull('line_discount');

                if ($rate > 0) {
                    $discounted++;
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

        $this->info(($dry ? 'Would update ' : 'Updated ') . ($orders - $skipped) . ' sale order(s).');
        $this->info($discounted . ' carried a discount — ' . number_format($allocated, 2)
            . ' total coming off reported revenue.');
        if ($skipped) {
            $this->warn($skipped . ' order(s) skipped — tax-inclusive lines, where the discount '
                . 'cannot be told apart from the tax. These keep gross revenue.');
        }

        return self::SUCCESS;
    }

    /**
     * The share of each line's value that was discounted, or null when it cannot be worked out.
     */
    private function invoiceDiscountRate($row, bool $hasGstStatus): ?float
    {
        if ($hasGstStatus) {
            $inclusive = DB::table('invoice_items')->where('manual_invoice_id', $row->invoice_pk)
                ->where('gst_status', 'including')->exists();
            if ($inclusive) {
                return null;
            }
        }

        $lineValue = (float) DB::table('invoice_items')->where('manual_invoice_id', $row->invoice_pk)
            ->selectRaw('COALESCE(SUM(price * qty), 0) as v')->value('v');

        if ($lineValue <= 0) {
            return 0.0;
        }

        $subtotal = (float) $row->total_amount - (float) $row->round_off
            - (float) $row->final_tax + (float) $row->discount_amount;

        $discount = $lineValue - $subtotal;

        // A rounding-sized residual is noise, not a discount.
        if ($discount < 0.01) {
            return 0.0;
        }

        return min(1, $discount / $lineValue);
    }
}
