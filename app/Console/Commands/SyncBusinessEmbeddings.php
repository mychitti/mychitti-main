<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
 
/**
 * AI Search embedding pipeline (Phase 3 §3.7) — builds a grounded search text for each active
 * store (name, city, categories, real services offered, address) and pushes it to the ai-server
 * /business/ingest endpoint, which embeds + upserts it into the pgvector index.
 *
 * Grounded in real supply (item_store) on purpose — a store only carries the services it actually 
 * offers, mirroring the programmatic-SEO "no empty pages" rule.
 */
class SyncBusinessEmbeddings extends Command
{
    protected $signature = 'ai:sync-business-embeddings
        {--store= : Sync a single store by id}
        {--limit= : Cap the number of stores processed}
        {--batch=20 : Stores per ingest request}';

    protected $description = 'Push per-store search text to the ai-server pgvector index for AI Search';

    public function handle(): int
    {
        $base = rtrim(config('services.ai_server.url', ''), '/');
        if ($base === '') {
            $this->error('services.ai_server.url (AI_SERVER_URL) is not configured.');
            return self::FAILURE;
        }
        $endpoint = $base . '/business/ingest';

        $query = DB::table('stores')
            ->where('status', 1)
            ->where('active', 1)
            ->orderBy('id');

        if ($storeId = $this->option('store')) {
            $query->where('id', (int) $storeId);
        }
        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $stores = $query->get(['id', 'name', 'address', 'business_type', 'zone_id']);
        if ($stores->isEmpty()) {
            $this->warn('No matching stores to sync.');
            return self::SUCCESS;
        }

        $zoneNames = DB::table('zones')->pluck('name', 'id');
        $sent = 0;
        $failed = 0;
        $batchSize = max(1, (int) $this->option('batch'));

        foreach ($stores->chunk($batchSize) as $chunk) {
            $storeIds = $chunk->pluck('id')->all();
            $catsByStore = $this->categoriesByStore($storeIds);
            $itemsByStore = $this->servicesByStore($storeIds);

            $items = [];
            foreach ($chunk as $s) {
                $text = $this->buildText($s, $zoneNames[$s->zone_id] ?? null,
                    $catsByStore[$s->id] ?? [], $itemsByStore[$s->id] ?? []);
                $items[] = ['store_id' => (int) $s->id, 'text' => $text];
            }

            try {
                $response = Http::timeout(120)->post($endpoint, ['items' => $items]);
                if ($response->ok() && $response->json('success')) {
                    $sent += (int) $response->json('count', count($items));
                } else {
                    $failed += count($items);
                    $this->warn("Batch failed (HTTP {$response->status()}): " . $response->body());
                }
            } catch (\Exception $e) {
                $failed += count($items);
                $this->warn('Batch error: ' . $e->getMessage());
            }
        }

        $this->info("Synced {$sent} store(s) to AI Search index. Failed: {$failed}.");
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @return array<int, array<string>> */
    private function categoriesByStore(array $storeIds): array
    {
        $rows = DB::table('item_store as ist')
            ->join('items as i', 'i.id', '=', 'ist.item_id')
            ->join('categories as c', 'c.id', '=', 'i.category_id')
            ->whereIn('ist.store_id', $storeIds)
            ->where('i.status', 1)
            ->distinct()
            ->select('ist.store_id', 'c.name')
            ->get();

        return $this->group($rows, 8);
    }

    /** @return array<int, array<string>> */
    private function servicesByStore(array $storeIds): array
    {
        $rows = DB::table('item_store as ist')
            ->join('items as i', 'i.id', '=', 'ist.item_id')
            ->whereIn('ist.store_id', $storeIds)
            ->where('i.status', 1)
            ->orderByDesc('i.order_count')
            ->select('ist.store_id', 'i.name')
            ->get();

        return $this->group($rows, 30);
    }

    /** @return array<int, array<string>> */
    private function group($rows, int $cap): array
    {
        $out = [];
        foreach ($rows as $r) {
            $sid = (int) $r->store_id;
            $out[$sid] ??= [];
            if (count($out[$sid]) < $cap && $r->name !== null && !in_array($r->name, $out[$sid], true)) {
                $out[$sid][] = $r->name;
            }
        }
        return $out;
    }

    private function buildText($store, ?string $zoneName, array $categories, array $services): string
    {
        $city = $zoneName ? trim(explode(',', $zoneName)[0]) : null;

        $parts = [$store->name];
        if ($store->business_type) {
            $parts[0] .= ' — ' . str_replace('_', ' ', $store->business_type);
        }
        if ($city) {
            $parts[] = "Located in {$city}.";
        }
        if ($categories) {
            $parts[] = 'Categories: ' . implode(', ', $categories) . '.';
        }
        if ($services) {
            $parts[] = 'Services: ' . implode(', ', $services) . '.';
        }
        if ($store->address) {
            $parts[] = 'Address: ' . trim($store->address);
        }

        return trim(implode(' ', $parts));
    }
}
