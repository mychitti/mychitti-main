<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Gives `inventory_order_details.qty` the precision a weighed sale needs, and puts back the
 * quantities the integer column destroyed.
 *
 * The column was `int(11)`. Everything writing it assigns a billed quantity straight across, so
 * 0.565 kg of avocado was stored as 1, and 2.4 kg as 2. Anything sold loose — the whole vegetable
 * and fruit counter — was recorded at a whole unit, and the Profit & Loss report valued it at the
 * whole-unit price. Quantities under one overstated revenue, larger fractional ones understated it,
 * so the report could disagree with the POS dashboard in both directions on the same day.
 *
 * The true quantity survives: `total_price` is worked out in PHP as price × qty before it reaches
 * the row, and is a decimal, so qty = total_price / unit_price recovers it exactly. No invoice
 * lookup and no guessing.
 *
 * Run out of hours — the ALTER rewrites the table.
 *
 *   php artisan inventory:fix-order-qty --dry-run
 *   php artisan inventory:fix-order-qty
 */
class FixInventoryOrderQty extends Command
{
    protected $signature = 'inventory:fix-order-qty
        {--dry-run : Report what would change without altering or writing}';

    protected $description = 'Restore fractional quantities on inventory sales lines';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        // SHOW COLUMNS takes no bindings — MariaDB will not prepare a placeholder here, so the
        // whole column list comes back and the one we want is picked out in PHP.
        $column = collect(DB::select('SHOW COLUMNS FROM inventory_order_details'))
            ->firstWhere('Field', 'qty');
        if (!$column) {
            $this->error('inventory_order_details has no qty column.');
            return self::FAILURE;
        }

        $this->info('qty is currently ' . $column->Type);

        // Rows the integer column changed. Compared against total_price / unit_price, which is what
        // the quantity was before it was written.
        $wrong = DB::table('inventory_order_details')
            ->whereRaw('unit_price > 0 AND total_price > 0')
            ->whereRaw('ABS(qty - (total_price / unit_price)) > 0.0001');

        $count = (clone $wrong)->count();
        $delta = (float) (clone $wrong)
            ->selectRaw('COALESCE(SUM(qty * unit_price - total_price), 0) as d')->value('d');

        $this->info(number_format($count) . ' line(s) hold a rounded quantity.');
        $this->info('They overstate revenue by ' . number_format($delta, 2)
            . ' in total (negative means understated).');

        if ($dry) {
            $this->newLine();
            $this->warn('[dry run] Nothing altered, nothing written.');
            return self::SUCCESS;
        }

        if (stripos($column->Type, 'int') !== false) {
            $this->info('Widening qty to DECIMAL(18,4)…');
            DB::statement('ALTER TABLE `inventory_order_details` MODIFY `qty` DECIMAL(18,4) NOT NULL DEFAULT 1');
        }

        $this->info('Restoring quantities…');
        $updated = DB::table('inventory_order_details')
            ->whereRaw('unit_price > 0 AND total_price > 0')
            ->whereRaw('ABS(qty - (total_price / unit_price)) > 0.0001')
            ->update(['qty' => DB::raw('ROUND(total_price / unit_price, 4)')]);

        $this->info('Restored ' . number_format($updated) . ' line(s).');
        $this->newLine();
        $this->info('Profit & Loss reads revenue from total_price, so its figures were already '
            . 'right; this also corrects the sale-order screens and the qty x landing_price cost '
            . 'fallback for lines with no line_cost.');

        return self::SUCCESS;
    }
}
