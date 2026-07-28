<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Variation stock rework — settle what every item with variations means, once.
 *
 * Three things need seeding before the new rules can be trusted:
 *
 *   stock_type            NULL on every existing row. The helpers infer it from the variation
 *                         labels, which is right for "100gm" but guesses on "Qtr kg". Writing it
 *                         down makes the behaviour explicit and stops it changing if a label is
 *                         edited later.
 *   pack_qty / pack_unit  Only written when an item is saved through the form. Stamping them here
 *                         means the pack size stops being re-parsed on every read.
 *   variations[].stock    The dangerous one. Stock-in only ever credited inventory_items.stock,
 *                         never the variation, so on a countable product the per-variation counts
 *                         are 0 while the real goods sit on the main figure. Once main becomes the
 *                         SUM of those counts, that stock reads as zero.
 *
 * Run --check first. It writes nothing and tells you which items need a human.
 */
class BackfillVariationStockCommand extends Command
{
    protected $signature = 'inventory:variation-stock
                            {--check : Report what would change, write nothing}
                            {--backfill : Write stock_type and pack sizes}
                            {--distribute : Also move a countable item\'s main stock onto its first variation}
                            {--store= : Limit to one store id}
                            {--chunk=200 : Rows per batch}';

    protected $description = 'Seed stock_type, variation pack sizes, and countable variation stock counts';

    public function handle(): int
    {
        _ensureUnitDimensionColumns();
        _ensureStockTypeColumn();

        if (!\Illuminate\Support\Facades\Schema::hasColumn('inventory_items', 'stock_type')) {
            $this->error('stock_type column is missing and could not be created.');
            return self::FAILURE;
        }

        if (!$this->option('check') && !$this->option('backfill')) {
            $this->warn('Nothing to do — pass --check or --backfill (optionally with --distribute).');
            return self::SUCCESS;
        }

        $dryRun = !$this->option('backfill');
        $rows = ['measured' => 0, 'countable' => 0, 'simple' => 0, 'loose' => 0];
        $needsCount = [];
        $written = 0;

        $query = InventoryItem::query()->orderBy('id');
        if ($this->option('store')) {
            $query->where('store_id', (int) $this->option('store'));
        }

        $query->chunkById((int) $this->option('chunk'), function ($items) use ($dryRun, &$rows, &$needsCount, &$written) {
            foreach ($items as $item) {
                $type = _stockTypeOf($item);
                $rows[$type === 'countable_variation' ? 'countable' : $type]++;

                $variations = _itemVariations($item);
                $changed = false;

                // Stamp the resolved pack size on each variation so it stops being re-read from
                // the label. _variationPack already prefers an explicit value, so this is a no-op
                // for anything a vendor has already filled in by hand.
                if ($type === 'measured') {
                    foreach ($variations as $i => $var) {
                        $pack = _variationPack($item, $var);
                        if ($pack && (($var['pack_qty'] ?? null) != $pack['qty'] || ($var['pack_unit'] ?? null) !== $pack['code'])) {
                            $variations[$i]['pack_qty']  = $pack['qty'];
                            $variations[$i]['pack_unit'] = $pack['code'];
                            $changed = true;
                        }
                    }
                }

                // A countable product whose variation counts are all zero while the main figure
                // holds real stock cannot be switched over safely — the SUM would read as nothing.
                if ($type === 'countable_variation') {
                    $sum = _sumCountableVariationStock($variations);
                    $main = (float) $item->stock;
                    if ($sum <= 0 && $main > 0) {
                        $needsCount[] = [$item->id, mb_substr((string) $item->item_name, 0, 36), $item->store_id,
                            rtrim(rtrim(number_format($main, 3, '.', ''), '0'), '.'), count($variations)];

                        if ($this->option('distribute') && !empty($variations)) {
                            // Everything onto the first variation. Not a guess at the real split —
                            // it keeps the total honest so nothing disappears, and leaves an
                            // obviously-wrong distribution for the vendor to correct.
                            $variations[0]['stock'] = $main;
                            $changed = true;
                        }
                    }
                }

                if ($dryRun) {
                    continue;
                }

                $item->stock_type = $type;
                if ($changed) {
                    $item->variations = json_encode($variations);
                    if ($type === 'countable_variation') {
                        $item->stock = _sumCountableVariationStock($variations);
                    }
                }
                $item->save();
                $written++;
            }
            $this->output->write('.');
        }, 'id');

        $this->newLine();
        $this->table(
            ['simple', 'loose', 'measured', 'countable'],
            [[$rows['simple'], $rows['loose'], $rows['measured'], $rows['countable']]]
        );

        if (!$dryRun) {
            $this->info("Wrote {$written} item(s).");
        }

        if (!empty($needsCount)) {
            $this->newLine();
            $this->warn(count($needsCount) . ' countable item(s) hold stock on the main figure with no per-variation counts:');
            $this->table(['id', 'item', 'store', 'main stock', 'variations'], array_slice($needsCount, 0, 40));
            if (count($needsCount) > 40) {
                $this->warn('...' . (count($needsCount) - 40) . ' more.');
            }
            $this->warn($this->option('distribute')
                ? 'Stock was moved onto each item\'s FIRST variation. Vendors must redistribute it.'
                : 'Re-run with --distribute to move it onto the first variation, or have vendors count them in.');
        }

        return self::SUCCESS;
    }
}
