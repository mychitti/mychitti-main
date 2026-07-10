<?php

namespace App\Console\Commands;

use App\Jobs\GenerateServiceZoneSeo;
use App\Models\ServiceZoneSeo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncServiceZoneSeoCombos extends Command
{
    // An item only gets its own SEO page in a city once at least this many distinct stores
    // offer it there — below that, the category page already covers it better (avoids thin pages).
    private const ITEM_MIN_STORES = 3;

    protected $signature = 'seo:sync-combos {--generate : Queue keyword generation for new/ungenerated combos}';
    protected $description = 'Ensure schema, backfill zone slugs, and reconcile category-level AND item-level SEO combos from the item_store pivot';

    public function handle(): int
    {
        $this->ensureSchema();
        $this->backfillZoneSlugs();
        $this->reconcile();
        return self::SUCCESS;
    }

    private function ensureSchema(): void
    {
        if (!Schema::hasColumn('zones', 'slug')) {
            DB::statement("ALTER TABLE zones ADD COLUMN slug VARCHAR(191) NULL");
            DB::statement("ALTER TABLE zones ADD UNIQUE KEY uq_zone_slug (slug)");
            $this->info('Added zones.slug column.');
        }

        if (!Schema::hasTable('service_zone_seo')) {
            DB::statement("
                CREATE TABLE service_zone_seo (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    category_id BIGINT UNSIGNED NOT NULL,
                    zone_id BIGINT UNSIGNED NOT NULL,
                    item_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
                    slug VARCHAR(255) NOT NULL,
                    keywords JSON NULL,
                    meta_title VARCHAR(70) NULL,
                    meta_description VARCHAR(300) NULL,
                    h1 VARCHAR(160) NULL,
                    intro_paragraph TEXT NULL,
                    faqs JSON NULL,
                    store_count INT NOT NULL DEFAULT 0,
                    status VARCHAR(20) NOT NULL DEFAULT 'draft',
                    model VARCHAR(60) NULL,
                    generated_at TIMESTAMP NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    UNIQUE KEY uq_combo (category_id, zone_id, item_id),
                    UNIQUE KEY uq_slug (slug),
                    KEY idx_zone (zone_id),
                    KEY idx_status (status),
                    KEY idx_item (item_id)
                )
            ");
            $this->info('Created service_zone_seo table.');
        } elseif (!Schema::hasColumn('service_zone_seo', 'item_id')) {
            // Table exists from before item-level pages were added — upgrade it in place.
            DB::statement("ALTER TABLE service_zone_seo ADD COLUMN item_id BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER zone_id");

            $oldIndexExists = DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', 'service_zone_seo')
                ->where('index_name', 'uq_cat_zone')
                ->exists();
            if ($oldIndexExists) {
                DB::statement("ALTER TABLE service_zone_seo DROP INDEX uq_cat_zone");
            }

            $newIndexExists = DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', 'service_zone_seo')
                ->where('index_name', 'uq_combo')
                ->exists();
            if (!$newIndexExists) {
                DB::statement("ALTER TABLE service_zone_seo ADD UNIQUE KEY uq_combo (category_id, zone_id, item_id)");
            }

            // idx_item may already exist if this upgrade partially ran before — check first.
            $idxItemExists = DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', 'service_zone_seo')
                ->where('index_name', 'idx_item')
                ->exists();
            if (!$idxItemExists) {
                DB::statement("ALTER TABLE service_zone_seo ADD KEY idx_item (item_id)");
            }

            $this->info('Upgraded service_zone_seo: added item_id column.');
        }
    }

    private function backfillZoneSlugs(): void
    {
        $count = 0;
        DB::table('zones')
            ->where(fn($q) => $q->whereNull('slug')->orWhere('slug', ''))
            ->select('id', 'name')
            ->orderBy('id')
            ->chunkById(200, function ($zones) use (&$count) {
                foreach ($zones as $z) {
                    // Same rule as _zoneCitySlug() (app/helpers.php) — first comma-segment of the
                    // zone name — so this column always agrees with store_details()/_storeCity().
                    $base = _zoneCitySlug($z->name);
                    $slug = $base;
                    if (DB::table('zones')->where('slug', $slug)->where('id', '!=', $z->id)->exists()) {
                        $slug = $base . '-' . $z->id;
                    }
                    DB::table('zones')->where('id', $z->id)->update(['slug' => $slug]);
                    $count++;
                }
            });
        if ($count) {
            $this->info("Backfilled {$count} zone slug(s).");
        }
    }

    private function reconcile(): void
    {
        $seen = [];
        $queued = 0;

        // Pass 1 — category-level combos (item_id = 0): every (category, zone) with active supply.
        $categoryCombos = DB::table('item_store as ist')
            ->join('items as i', 'i.id', '=', 'ist.item_id')
            ->join('categories as c', 'c.id', '=', 'i.category_id')
            ->join('stores as s', 's.id', '=', 'ist.store_id')
            ->where('s.status', 1)->where('s.active', 1)->where('i.status', 1)
            ->groupBy('c.id', 's.zone_id')
            ->select('c.id as category_id', 's.zone_id', DB::raw('COUNT(DISTINCT s.id) as store_count'))
            ->get();

        foreach ($categoryCombos as $combo) {
            $this->upsertCombo([
                'category_id' => $combo->category_id,
                'zone_id'     => $combo->zone_id,
                'item_id'     => 0,
                'store_count' => $combo->store_count,
            ], $seen, $queued);
        }

        // Pass 2 — item-level combos (item_id > 0): only items with real multi-store supply in a
        // city get their own page; everything below the threshold stays covered by the category page.
        $itemCombos = DB::table('item_store as ist')
            ->join('items as i', 'i.id', '=', 'ist.item_id')
            ->join('stores as s', 's.id', '=', 'ist.store_id')
            ->where('s.status', 1)->where('s.active', 1)->where('i.status', 1)
            ->groupBy('i.id', 'i.category_id', 's.zone_id')
            ->havingRaw('COUNT(DISTINCT s.id) >= ?', [self::ITEM_MIN_STORES])
            ->select('i.id as item_id', 'i.category_id', 's.zone_id', DB::raw('COUNT(DISTINCT s.id) as store_count'))
            ->get();

        foreach ($itemCombos as $combo) {
            $this->upsertCombo([
                'category_id' => $combo->category_id,
                'zone_id'     => $combo->zone_id,
                'item_id'     => $combo->item_id,
                'store_count' => $combo->store_count,
            ], $seen, $queued);
        }

        // Unpublish combos (either level) that no longer have any qualifying supply.
        $unpublished = 0;
        ServiceZoneSeo::where('status', '!=', 'unpublished')
            ->select('id', 'category_id', 'zone_id', 'item_id')
            ->chunkById(500, function ($rows) use ($seen, &$unpublished) {
                foreach ($rows as $row) {
                    if (empty($seen[$row->category_id . ':' . $row->zone_id . ':' . $row->item_id])) {
                        ServiceZoneSeo::where('id', $row->id)->update(['status' => 'unpublished', 'store_count' => 0]);
                        $unpublished++;
                    }
                }
            });

        $total = count($categoryCombos) + count($itemCombos);
        $this->info("Category combos: {$categoryCombos->count()} | Item combos: {$itemCombos->count()} | Total: {$total} | queued generation: {$queued} | unpublished: {$unpublished}.");
    }

    private function upsertCombo(array $combo, array &$seen, int &$queued): void
    {
        if (!$combo['zone_id']) {
            return;
        }
        $key = $combo['category_id'] . ':' . $combo['zone_id'] . ':' . $combo['item_id'];
        $seen[$key] = true;

        $zoneSlug = DB::table('zones')->where('id', $combo['zone_id'])->value('slug');
        $catSlug  = DB::table('categories')->where('id', $combo['category_id'])->value('slug');
        if (!$zoneSlug || !$catSlug) {
            return;
        }

        // Category page: {city}/services/{category}. Item page: {city}/services/{category}/{item}.
        $slug = $zoneSlug . '/services/' . $catSlug;
        if ($combo['item_id'] > 0) {
            $itemSlug = DB::table('items')->where('id', $combo['item_id'])->value('slug');
            if (!$itemSlug) {
                return;
            }
            $slug .= '/' . $itemSlug;
        }

        $existing = ServiceZoneSeo::where('category_id', $combo['category_id'])
            ->where('zone_id', $combo['zone_id'])
            ->where('item_id', $combo['item_id'])
            ->first();

        if (!$existing) {
            $row = ServiceZoneSeo::create([
                'category_id' => $combo['category_id'],
                'zone_id'     => $combo['zone_id'],
                'item_id'     => $combo['item_id'],
                'slug'        => $slug,
                'store_count' => $combo['store_count'],
                'status'      => 'draft',
            ]);
            if ($this->option('generate')) {
                GenerateServiceZoneSeo::dispatch($row->id);
                $queued++;
            }
            return;
        }

        $existing->store_count = $combo['store_count'];
        $existing->slug = $slug;
        // Supply is back — republish if we already have content, else leave as draft.
        if ($existing->status === 'unpublished') {
            $existing->status = $existing->keywords ? 'published' : 'draft';
        }
        $existing->save();

        if ($this->option('generate') && empty($existing->keywords)) {
            GenerateServiceZoneSeo::dispatch($existing->id);
            $queued++;
        }
    }
}
