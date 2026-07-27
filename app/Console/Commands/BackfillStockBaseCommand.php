<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Unit rework phase 3 — backfill and reconcile inventory_items.stock_base.
 *
 * stock_base holds an item's stock in its dimension's BASE unit (grams, millimetres,
 * millilitres, pieces), so a single number covers stock bought in tonnes and sold in
 * grams. It is written alongside the legacy `stock` column by the InventoryItem saving
 * hook; nothing reads it yet.
 *
 * Run --backfill once to seed existing rows, then --check on a schedule. Any drift means
 * a write path is bypassing the model, and must be fixed BEFORE any screen reads
 * stock_base — a stock refactor that miscounts silently is worse than the limitation it
 * replaces.
 */
class BackfillStockBaseCommand extends Command
{
    protected $signature = 'inventory:stock-base
                            {--backfill : Write stock_base for rows that are NULL}
                            {--recompute : Rewrite stock_base for every row, not just NULLs}
                            {--check : Report rows where stock_base disagrees with stock x factor}
                            {--chunk=500 : Rows per batch}';

    protected $description = 'Backfill and reconcile inventory_items.stock_base against the legacy stock column';

    public function handle(): int
    {
        _ensureUnitDimensionColumns();
        _ensureStockBaseColumn();

        if (!\Illuminate\Support\Facades\Schema::hasColumn('inventory_items', 'stock_base')) {
            $this->error('stock_base column is missing and could not be created.');
            return self::FAILURE;
        }

        $did = false;
        if ($this->option('backfill') || $this->option('recompute')) {
            $this->fill((bool) $this->option('recompute'));
            $did = true;
        }
        if ($this->option('check')) {
            return $this->check();
        }

        if (!$did) {
            $this->warn('Nothing to do — pass --backfill, --recompute or --check.');
        }
        return self::SUCCESS;
    }

    private function fill(bool $all): void
    {
        $query = DB::table('inventory_items')->select('id', 'unit', 'stock');
        if (!$all) {
            $query->whereNull('stock_base');
        }

        $total = (clone $query)->count();
        $this->info(($all ? 'Recomputing' : 'Backfilling') . " stock_base for {$total} item(s)...");

        // chunkById, not chunk: --backfill filters on the very column it writes, so OFFSET
        // paging would skip rows as they drop out of the result set (502 matched, 500 written).
        // Paging on id > lastId is immune to the result set shrinking underneath it.
        $written = 0;
        $query->chunkById((int) $this->option('chunk'), function ($rows) use (&$written) {
            foreach ($rows as $row) {
                [$base, $baseUnit] = _stockBaseFor($row->unit, $row->stock);
                DB::table('inventory_items')->where('id', $row->id)->update([
                    'stock_base' => $base,
                    'base_unit'  => $baseUnit,
                ]);
                $written++;
            }
            $this->output->write('.');
        }, 'id');

        $this->newLine();
        $this->info("Wrote {$written} row(s).");

        if ($written !== $total) {
            $this->warn("Expected {$total} but wrote {$written} — re-run --check.");
        }
    }

    private function check(): int
    {
        $this->info('Reconciling stock_base against stock x unit factor...');

        $drift = [];
        DB::table('inventory_items as i')
            ->leftJoin('units as u', 'u.id', '=', 'i.unit')
            ->select('i.id', 'i.item_name', 'i.store_id', 'i.stock', 'i.stock_base', 'u.unit as unit_name', 'u.factor')
            ->chunkById((int) $this->option('chunk'), function ($rows) use (&$drift) {
                foreach ($rows as $row) {
                    $factor   = (float) ($row->factor ?: 1);
                    $expected = round((float) $row->stock * $factor, 4);
                    $actual   = $row->stock_base === null ? null : round((float) $row->stock_base, 4);

                    // Tolerance absorbs DECIMAL rounding, not real drift.
                    if ($actual === null || abs($expected - $actual) > 0.0001) {
                        $drift[] = [
                            $row->id,
                            mb_substr((string) $row->item_name, 0, 30),
                            $row->store_id,
                            rtrim(rtrim(number_format((float) $row->stock, 3, '.', ''), '0'), '.'),
                            $row->unit_name ?? '-',
                            $expected,
                            $actual === null ? 'NULL' : $actual,
                        ];
                    }
                }
            }, 'i.id', 'id');

        if (empty($drift)) {
            $this->info('No drift. stock_base agrees with stock on every row.');
            return self::SUCCESS;
        }

        $this->error(count($drift) . ' row(s) out of step:');
        $this->table(['id', 'item', 'store', 'stock', 'unit', 'expected base', 'actual base'], array_slice($drift, 0, 50));
        if (count($drift) > 50) {
            $this->warn('...' . (count($drift) - 50) . ' more.');
        }
        $this->warn('Do not switch any screen to read stock_base until this is clean.');

        return self::FAILURE;
    }
}
