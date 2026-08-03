<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Gives the columns holding quantities, unit prices and tax rates the precision their values need.
 *
 * All of these were integers. Everything writing them assigns a real figure straight across, so
 * 0.565 kg of avocado stored as 1, a ₹230.50 purchase price as ₹231, and a 2.5% CGST rate as 3%.
 * The Profit & Loss report valued weighed sales at whole-unit prices, which is how a store reading
 * 92,238.20 had actually billed 88,364.06.
 *
 * Only `inventory_order_details.qty` can be put back. Its `total_price` is a decimal computed in PHP
 * as price × qty before the row is written, so qty = total_price / unit_price recovers it exactly.
 * The other tables kept no second copy: widening them stops the corruption from here on, but what
 * has already been rounded is gone. The command reports how many rows are visibly affected so the
 * damage is at least known.
 *
 * Every ALTER preserves the column's existing nullability and default, skips anything already
 * decimal, and skips tables that do not exist on this install. Safe to re-run.
 *
 * Run out of hours — each ALTER rewrites its table.
 *
 *   php artisan inventory:fix-numeric-precision --dry-run
 *   php artisan inventory:fix-numeric-precision
 */
class FixNumericPrecision extends Command
{
    protected $signature = 'inventory:fix-numeric-precision
        {--dry-run : Show the plan without altering or writing}';

    protected $description = 'Widen integer quantity, price and tax-rate columns to decimals';

    /** [table, column, new type, what it holds] */
    private const TARGETS = [
        ['inventory_order_details', 'qty',        'DECIMAL(18,4)', 'billed quantity on a sale line'],
        ['inventory_order_details', 'tax_rate',   'DECIMAL(8,3)',  'tax rate on a sale line'],
        ['supply_order_items',      'qty',        'DECIMAL(18,4)', 'quantity taken into stock'],
        ['supply_order_items',      'unit_price', 'DECIMAL(24,3)', 'purchase price per unit'],
        ['supply_order_items',      'tax_rate',   'DECIMAL(8,3)',  'tax rate on a purchase line'],
        ['purchase_orders',         'stock',      'DECIMAL(18,4)', 'quantity ordered'],
        ['branch_inventory_item',   'qty',        'DECIMAL(18,4)', 'quantity allocated to a branch'],
        ['branch_inventory_item',   'qty_left',   'DECIMAL(18,4)', 'quantity remaining at a branch'],
        ['pos_token_items',         'qty',        'DECIMAL(18,4)', 'quantity on a POS token line'],
        ['inventory_items',         'gst_rate',   'DECIMAL(8,3)',  'the item GST rate'],
        ['invoice_items',           'cgst_rate',  'DECIMAL(8,3)',  'CGST rate — 2.5% stored as 3'],
        ['invoice_items',           'sgst_rate',  'DECIMAL(8,3)',  'SGST rate — 2.5% stored as 3'],
        ['invoice_items',           'igst_rate',  'DECIMAL(8,3)',  'IGST rate'],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $this->reportDamage();

        $plan = [];
        foreach (self::TARGETS as [$table, $column, $type, $holds]) {
            $current = $this->columnInfo($table, $column);

            if (!$current) {
                $this->line(str_pad("{$table}.{$column}", 44) . 'absent — skipped');
                continue;
            }
            if (strtolower($current->DATA_TYPE) === 'decimal') {
                $this->line(str_pad("{$table}.{$column}", 44) . 'already ' . $current->COLUMN_TYPE);
                continue;
            }

            $plan[] = [$table, $column, $type, $current, $holds];
            $this->line(str_pad("{$table}.{$column}", 44) . $current->COLUMN_TYPE . ' → ' . $type
                . '   (' . $holds . ')');
        }

        $this->newLine();

        if (!$plan) {
            $this->info('Nothing to widen — every column already holds decimals.');
        } elseif ($dry) {
            $this->warn('[dry run] ' . count($plan) . ' column(s) would be altered. Nothing written.');
        } else {
            foreach ($plan as [$table, $column, $type, $current, $holds]) {
                $this->info('Altering ' . $table . '.' . $column . '…');
                DB::statement('ALTER TABLE `' . $table . '` MODIFY `' . $column . '` '
                    . $this->definition($type, $current));
            }
            $this->info('Altered ' . count($plan) . ' column(s).');
        }

        $this->newLine();
        $this->restoreOrderQty($dry);

        return self::SUCCESS;
    }

    /**
     * The new column definition, carrying the old nullability and default across. MODIFY replaces
     * the whole definition, so anything not restated here is silently dropped — a nullable column
     * turning NOT NULL would break the next insert that passes null.
     */
    private function definition(string $type, $current): string
    {
        $sql = $type . ($current->IS_NULLABLE === 'YES' ? ' NULL' : ' NOT NULL');

        if ($current->COLUMN_DEFAULT !== null && !is_numeric($current->COLUMN_DEFAULT)) {
            return $sql;
        }
        if ($current->COLUMN_DEFAULT !== null) {
            return $sql . ' DEFAULT ' . $current->COLUMN_DEFAULT;
        }

        return $sql . ($current->IS_NULLABLE === 'YES' ? ' DEFAULT NULL' : '');
    }

    private function columnInfo(string $table, string $column)
    {
        return collect(DB::select(
            'SELECT DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS '
                . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        ))->first();
    }

    /**
     * Puts back the quantities the integer column destroyed on sale lines.
     */
    private function restoreOrderQty(bool $dry): void
    {
        $wrong = DB::table('inventory_order_details')
            ->whereRaw('unit_price > 0 AND total_price > 0')
            ->whereRaw('ABS(qty - (total_price / unit_price)) > 0.0001');

        $count = (clone $wrong)->count();
        if ($count === 0) {
            $this->info('Every sale line already holds its true quantity.');
            return;
        }

        $delta = (float) (clone $wrong)
            ->selectRaw('COALESCE(SUM(qty * unit_price - total_price), 0) as d')->value('d');

        $this->info(number_format($count) . ' sale line(s) hold a rounded quantity, overstating '
            . 'line value by ' . number_format($delta, 2) . ' (negative means understated).');

        if ($dry) {
            $this->warn('[dry run] Quantities left as they are.');
            return;
        }

        $updated = DB::table('inventory_order_details')
            ->whereRaw('unit_price > 0 AND total_price > 0')
            ->whereRaw('ABS(qty - (total_price / unit_price)) > 0.0001')
            ->update(['qty' => DB::raw('ROUND(total_price / unit_price, 4)')]);

        $this->info('Restored ' . number_format($updated) . ' sale line(s) from total_price.');
    }

    /**
     * What was rounded elsewhere and cannot be recovered. On a purchase line the product of price
     * and quantity survives in total_amount, so a row whose own price × quantity no longer matches
     * it was rounded — but the two factors cannot be told apart, so it can only be counted.
     */
    private function reportDamage(): void
    {
        if (!$this->columnInfo('supply_order_items', 'total_amount')) {
            return;
        }

        $rounded = DB::table('supply_order_items')
            ->whereRaw('ABS(unit_price * qty - (total_amount - COALESCE(tax_amount, 0))) > 0.01')
            ->count();

        if ($rounded > 0) {
            $this->warn(number_format($rounded) . ' purchase line(s) were rounded on the way in. '
                . 'Their quantity and unit price cannot be separated back out — widening the '
                . 'columns only stops it happening again.');
            $this->newLine();
        }
    }
}
