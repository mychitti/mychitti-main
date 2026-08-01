<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gives every existing inventory item an opening price point, so its price history shows what the
 * item was listed at before the log existed rather than starting blank.
 *
 * Context only — it does not move any average. Averages count transactions (goods purchased at a
 * price, the item sold at a price), and an opening price has no stock behind it. See the
 * InventoryPriceHistory trait for that split.
 *
 * Price changes are only recorded from the day `inventory_item_price_logs` shipped. Items created
 * before that carry a purchase and a selling price with no record of either being set, so their
 * history showed nothing until the vendor happened to edit one.
 *
 * Dated to the item's own created_at rather than now, so the opening point sorts to the start of
 * the timeline instead of looking like a change that just happened. Labelled 'opening' rather than
 * 'create' because that is the honest claim: this is the price as it stands, attributed to where
 * the history begins — not proof it was the price the item was created with.
 *
 * Safe to run repeatedly: a price type that already has any log row for an item is left alone, so
 * a second run seeds nothing and items priced since the hook shipped are never touched.
 *
 *   php artisan inventory:backfill-opening-prices            # everything
 *   php artisan inventory:backfill-opening-prices --store=12 # one store
 *   php artisan inventory:backfill-opening-prices --dry-run  # report only, write nothing
 */
class BackfillItemOpeningPrices extends Command
{
    protected $signature = 'inventory:backfill-opening-prices
        {--store= : Only this store id}
        {--prune : First drop duplicate opening rows left by the wasRecentlyCreated bug}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Seed each inventory item\'s current prices as the opening point of its price history';

    public function handle(): int
    {
        _ensureItemPriceLogTable();

        if (!Schema::hasTable('inventory_item_price_logs')) {
            $this->error('inventory_item_price_logs does not exist and it could not be created.');
            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $storeId = $this->option('store') ? (int) $this->option('store') : null;

        if ($this->option('prune')) {
            $this->prune($storeId, $dry);
        }

        $query = DB::table('inventory_items')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->where(function ($q) {
                $q->where('landing_price', '>', 0)->orWhere('selling_price', '>', 0);
            })
            ->select('id', 'store_id', 'landing_price', 'selling_price', 'created_at');

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('Nothing to backfill — no items carry a price.');
            return self::SUCCESS;
        }

        $this->info(($dry ? '[dry run] ' : '') . 'Checking ' . $total . ' item(s)…');
        $bar = $this->output->createProgressBar($total);

        $seeded = ['purchase' => 0, 'sell' => 0];
        $alreadyLogged = 0;

        $query->orderBy('id')->chunk(500, function ($items) use (&$seeded, &$alreadyLogged, $dry, $bar) {
            $ids = collect($items)->pluck('id')->all();

            // One lookup per chunk rather than per item — what matters is only whether a price
            // type has any history at all, not what that history says.
            $existing = DB::table('inventory_item_price_logs')
                ->whereIn('item_id', $ids)
                ->select('item_id', 'price_type')
                ->distinct()
                ->get()
                ->map(fn($row) => $row->item_id . ':' . $row->price_type)
                ->flip();

            $rows = [];

            foreach ($items as $item) {
                $bar->advance();
                $at = $item->created_at ?: now();

                foreach (['purchase' => 'landing_price', 'sell' => 'selling_price'] as $type => $column) {
                    if ($existing->has($item->id . ':' . $type)) {
                        $alreadyLogged++;
                        continue;
                    }
                    if ((float) ($item->{$column} ?? 0) <= 0) {
                        continue;
                    }

                    $rows[] = [
                        'store_id' => $item->store_id,
                        'item_id' => $item->id,
                        'price_type' => $type,
                        'old_price' => null,
                        'new_price' => (float) $item->{$column},
                        'source' => 'opening',
                        'created_at' => $at,
                        'updated_at' => $at,
                    ];
                    $seeded[$type]++;
                }
            }

            if ($rows && !$dry) {
                DB::table('inventory_item_price_logs')->insert($rows);
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info(($dry ? 'Would seed ' : 'Seeded ')
            . $seeded['purchase'] . ' opening purchase price(s) and '
            . $seeded['sell'] . ' opening selling price(s).');

        if ($alreadyLogged) {
            $this->line($alreadyLogged . ' price(s) already had history and were left alone.');
        }

        return self::SUCCESS;
    }

    /**
     * Drop the surplus opening rows written before the logging hook moved off `saved`.
     *
     * An item can only be created once, so more than one 'create' or 'opening' row for the same
     * item and price type is always the duplicate — save_item() saves a new row three times while
     * attaching barcode, images and variations, and every one of them logged an opening price.
     * The earliest row of each group is the real one and is kept. Edits are never touched: two
     * edits to the same price are two genuine price points.
     */
    private function prune(?int $storeId, bool $dry): void
    {
        $groups = DB::table('inventory_item_price_logs')
            ->whereIn('source', ['create', 'opening'])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->groupBy('item_id', 'price_type', 'source')
            ->havingRaw('COUNT(*) > 1')
            ->select('item_id', 'price_type', 'source', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->get();

        if ($groups->isEmpty()) {
            $this->line('No duplicate opening rows to prune.');
            return;
        }

        $surplus = $groups->sum(fn($g) => $g->total - 1);

        if ($dry) {
            $this->warn('[dry run] Would delete ' . $surplus . ' duplicate opening row(s) across '
                . $groups->count() . ' item price(s).');
            return;
        }

        $deleted = 0;
        foreach ($groups as $group) {
            $deleted += DB::table('inventory_item_price_logs')
                ->where('item_id', $group->item_id)
                ->where('price_type', $group->price_type)
                ->where('source', $group->source)
                ->where('id', '!=', $group->keep_id)
                ->delete();
        }

        $this->warn('Deleted ' . $deleted . ' duplicate opening row(s).');
    }
}
