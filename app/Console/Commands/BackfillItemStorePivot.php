<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillItemStorePivot extends Command
{
    protected $signature = 'items:backfill-store-pivot {--fresh : Truncate the pivot before backfilling}';
    protected $description = 'Create the item_store pivot (if missing) and backfill it from items.store_ids AND stores.services_1/2';

    public function handle(): int
    {
        if (!Schema::hasTable('item_store')) {
            DB::statement("
                CREATE TABLE item_store (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    item_id BIGINT UNSIGNED NOT NULL,
                    store_id BIGINT UNSIGNED NOT NULL,
                    UNIQUE KEY uq_item_store (item_id, store_id),
                    KEY idx_store (store_id),
                    KEY idx_item (item_id)
                )
            ");
            $this->info('Created item_store table.');
        }

        if ($this->option('fresh')) {
            DB::table('item_store')->truncate();
            $this->warn('Truncated item_store.');
        }

        // Pass 1 — from items.store_ids (item → stores). Skipped once that column is dropped.
        $insertedFromItems = 0;
        if (Schema::hasColumn('items', 'store_ids')) {
            DB::table('items')
                ->whereNotNull('store_ids')->where('store_ids', '!=', '')
                ->select('id', 'store_ids')->orderBy('id')
                ->chunk(500, function ($rows) use (&$insertedFromItems) {
                    $pivot = [];
                    foreach ($rows as $row) {
                        foreach ($this->parseCsv($row->store_ids) as $storeId) {
                            $pivot[] = ['item_id' => $row->id, 'store_id' => $storeId];
                        }
                    }
                    if ($pivot) {
                        $insertedFromItems += DB::table('item_store')->insertOrIgnore($pivot);
                    }
                });
            $this->info("Pass 1 (items.store_ids): {$insertedFromItems} edges.");
        } else {
            $this->warn('Pass 1 skipped — items.store_ids column no longer exists.');
        }

        // Pass 2 — from stores.services_1 + services_2 (store → items). insertOrIgnore skips edges
        // already added in pass 1, so the count here is exactly the drift: edges that existed in
        // the store's service list but not in items.store_ids.
        $insertedFromServices = 0;
        if (Schema::hasColumn('stores', 'services_1')) {
            DB::table('stores')
                ->where(function ($q) {
                    $q->where('services_1', '!=', '')->orWhere('services_2', '!=', '');
                })
                ->select('id', 'services_1', 'services_2')->orderBy('id')
                ->chunk(500, function ($rows) use (&$insertedFromServices) {
                    $pivot = [];
                    foreach ($rows as $row) {
                        $itemIds = array_unique(array_merge(
                            $this->parseCsv($row->services_1),
                            $this->parseCsv($row->services_2)
                        ));
                        foreach ($itemIds as $itemId) {
                            $pivot[] = ['item_id' => $itemId, 'store_id' => $row->id];
                        }
                    }
                    if ($pivot) {
                        $insertedFromServices += DB::table('item_store')->insertOrIgnore($pivot);
                    }
                });
        }

        if ($insertedFromServices > 0) {
            $this->warn("Pass 2 (stores.services_1/2): {$insertedFromServices} extra edges NOT in items.store_ids — the two columns had drifted; the pivot now reconciles both.");
        } else {
            $this->info('Pass 2 (stores.services_1/2): 0 extra edges — the two sources agree.');
        }

        $total = DB::table('item_store')->count();
        $this->info("Pivot total: {$total} edges.");

        return self::SUCCESS;
    }

    private function parseCsv(?string $csv): array
    {
        return array_values(array_unique(array_filter(array_map('intval', explode(',', trim((string) $csv))))));
    }
}
