<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
 
/**
 * AI Search — RAG-powered natural-language local discovery (Phase 3 §3.1 / §3.7).
 *
 * Flow: query -> ai-server semantic retrieval (candidate store_ids + score) -> load live
 * stores from MySQL -> hybrid ranking (semantic 40 / trust 25 / distance 20 / popularity 15,
 * per the master doc) -> short natural-language answer via ai-server /business/ask.
 * 
 * If the ai-server is unreachable we fall back to a MySQL keyword search so the endpoint
 * still returns real results (doc risk register: "auto-fallback to MySQL full-text").
 */
class AiSearchController extends Controller
{
    // Hybrid ranking weights — must sum to 1.0 (master doc "Hybrid Search Ranking Formula").
    private const W_SEMANTIC   = 0.40;
    private const W_TRUST      = 0.25;
    private const W_DISTANCE   = 0.20;
    private const W_POPULARITY = 0.15;

    private string $businessUrl;

    public function __construct()
    {
        $this->businessUrl = rtrim(config('services.ai_server.url', ''), '/') . '/business';
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'query'     => 'required|string|max:300',
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'limit'     => 'nullable|integer|min:1|max:30',
        ]);

        $query = trim($validated['query']);
        $lat   = $validated['latitude']  ?? null;
        $lng   = $validated['longitude'] ?? null;
        $limit = (int) ($validated['limit'] ?? 10);

        [$candidates, $usedFallback] = $this->retrieveCandidates($query);
        if (empty($candidates)) {
            return response()->json([
                'success'  => true,
                'query'    => $query,
                'answer'   => '',
                'results'  => [],
                'fallback' => $usedFallback,
            ]);
        }

        $stores = $this->loadStores(array_keys($candidates));
        if ($stores->isEmpty()) {
            return response()->json([
                'success'  => true,
                'query'    => $query,
                'answer'   => '',
                'results'  => [],
                'fallback' => $usedFallback,
            ]);
        }

        $ranked = $this->rankStores($stores, $candidates, $lat, $lng)->take($limit)->values();
        $results = $this->presentResults($ranked);
        $answer  = $this->composeAnswer($query, $results);

        return response()->json([
            'success'  => true,
            'query'    => $query,
            'answer'   => $answer,
            'results'  => $results,
            'fallback' => $usedFallback,
        ]);
    }

    /**
     * @return array{0: array<int,float>, 1: bool} [ store_id => semanticScore, usedFallback ]
     */
    private function retrieveCandidates(string $query): array
    {
        try {
            $response = Http::timeout(15)->post("{$this->businessUrl}/search", [
                'query' => $query,
                'top_k' => 40,
            ]);
            if ($response->ok() && $response->json('success')) {
                $map = [];
                foreach ($response->json('results', []) as $row) {
                    if (isset($row['store_id'])) {
                        $map[(int) $row['store_id']] = (float) ($row['score'] ?? 0);
                    }
                }
                if (!empty($map)) {
                    return [$map, false];
                }
            }
            Log::warning('AI search: ai-server returned no candidates, using MySQL fallback', [
                'status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::warning('AI search: ai-server unreachable, using MySQL fallback', ['error' => $e->getMessage()]);
        }

        return [$this->keywordFallback($query), true];
    }

    /**
     * MySQL keyword fallback over store name/address and the services they offer. Every match
     * gets a neutral semantic score so hybrid ranking still orders by trust / distance / popularity.
     *
     * @return array<int,float>
     */
    private function keywordFallback(string $query): array
    {
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $query) . '%';

        $ids = DB::table('stores')
            ->where('stores.status', 1)
            ->where('stores.active', 1)
            ->where(function ($q) use ($like) {
                $q->where('stores.name', 'like', $like)
                    ->orWhere('stores.address', 'like', $like)
                    ->orWhereExists(function ($sub) use ($like) {
                        $sub->select(DB::raw(1))
                            ->from('item_store as ist')
                            ->join('items as i', 'i.id', '=', 'ist.item_id')
                            ->whereColumn('ist.store_id', 'stores.id')
                            ->where('i.status', 1)
                            ->where('i.name', 'like', $like);
                    });
            })
            ->orderByDesc('stores.total_order')
            ->limit(40)
            ->pluck('stores.id')
            ->all();

        return array_fill_keys(array_map('intval', $ids), 0.5);
    }

    /**
     * @param array<int> $storeIds
     */
    private function loadStores(array $storeIds)
    {
        return DB::table('stores')
            ->leftJoin('zones', 'zones.id', '=', 'stores.zone_id')
            ->whereIn('stores.id', $storeIds)
            ->where('stores.status', 1)
            ->where('stores.active', 1)
            ->select(
                'stores.id', 'stores.name', 'stores.slug', 'stores.address', 'stores.logo',
                'stores.average_rating', 'stores.rating_count', 'stores.total_order',
                'stores.latitude', 'stores.longitude', 'stores.gst',
                DB::raw('COALESCE(stores.vendor_trust_score, 0) as vendor_trust_score'),
                'zones.name as zone_name', 'zones.slug as zone_slug'
            )
            ->get();
    }

    private function rankStores($stores, array $candidates, ?float $lat, ?float $lng)
    {
        $maxOrders = max(1, (int) $stores->max('total_order'));

        return $stores->map(function ($s) use ($candidates, $lat, $lng, $maxOrders) {
            $semantic   = $candidates[(int) $s->id] ?? 0.0;
            $trust      = min(max((int) $s->vendor_trust_score, 0), 100) / 100;
            $popularity = min((int) $s->total_order, $maxOrders) / $maxOrders;

            $distanceKm = $this->distanceKm($lat, $lng, $s->latitude, $s->longitude);
            // Proximity decay: 0km -> 1.0, 5km -> 0.5, 20km -> 0.2. Neutral 0.5 when we can't
            // compute distance (no user coords or store has no geo), so it neither helps nor hurts.
            $proximity = $distanceKm === null ? 0.5 : 1 / (1 + ($distanceKm / 5));

            $score = self::W_SEMANTIC * $semantic
                + self::W_TRUST * $trust
                + self::W_DISTANCE * $proximity
                + self::W_POPULARITY * $popularity;

            $s->_score       = $score;
            $s->_semantic    = $semantic;
            $s->_distance_km = $distanceKm;
            return $s;
        })->sortByDesc('_score');
    }

    private function distanceKm(?float $lat, ?float $lng, $storeLat, $storeLng): ?float
    {
        if ($lat === null || $lng === null || !is_numeric($storeLat) || !is_numeric($storeLng)) {
            return null;
        }
        $storeLat = (float) $storeLat;
        $storeLng = (float) $storeLng;
        if ($storeLat == 0.0 && $storeLng == 0.0) {
            return null;
        }
        // Haversine, same earth radius as _calcDeliveryCharge() in helpers.php.
        $x = cos(deg2rad($lat)) * cos(deg2rad($storeLat)) * cos(deg2rad($storeLng) - deg2rad($lng))
            + sin(deg2rad($lat)) * sin(deg2rad($storeLat));
        $x = min(1.0, max(-1.0, $x)); // guard against float drift outside acos domain
        return round(6371 * acos($x), 2);
    }

    private function presentResults($ranked): array
    {
        $storeIds   = $ranked->pluck('id')->all();
        $services   = $this->topServices($storeIds);
        $offers     = $this->offersByStore($storeIds);
        $categories = $this->categoriesByStore($storeIds);

        return $ranked->map(function ($s) use ($services, $offers, $categories) {
            $citySlug = $s->zone_slug ?: null;
            $cityName = $s->zone_name ? trim(explode(',', $s->zone_name)[0]) : null;

            // Knowledge-graph view of the entity: vendor -> categories -> city -> services -> offers,
            // plus review/trust signals — resolved live from MySQL (see class doc).
            return [
                'id'            => (int) $s->id,
                'name'          => $s->name,
                'slug'          => $s->slug,
                'address'       => $s->address,
                'logo'          => $s->logo,
                'city'          => $cityName,
                'categories'    => $categories[$s->id] ?? [],
                'rating'        => $s->average_rating !== null ? round((float) $s->average_rating, 1) : null,
                'rating_count'  => (int) $s->rating_count,
                'trust_score'   => (int) $s->vendor_trust_score,
                'badges'        => store_trust_badges($s),
                'gst_verified'  => !empty($s->gst),
                'services'      => $services[$s->id] ?? [],
                'offers'        => $offers[$s->id] ?? [],
                'distance_km'   => $s->_distance_km,
                'score'         => round($s->_score, 4),
                'booking_url'   => ($citySlug && $s->slug) ? url("{$citySlug}/store/{$s->slug}") : null,
            ];
        })->all();
    }

    /**
     * Active offer labels per store, for AI Search result cards (Phase 3 §3.5).
     *
     * @param array<int> $storeIds
     * @return array<int, array<string>>
     */
    private function offersByStore(array $storeIds): array
    {
        if (empty($storeIds)) {
            return [];
        }
        $today = now()->toDateString();
        $rows = DB::table('store_offers')
            ->whereIn('store_id', $storeIds)
            ->where('status', 1)
            ->where(fn($w) => $w->whereNull('start_date')->orWhere('start_date', '<=', $today))
            ->where(fn($w) => $w->whereNull('end_date')->orWhere('end_date', '>=', $today))
            ->orderByDesc('created_at')
            ->get(['store_id', 'title']);

        $byStore = [];
        foreach ($rows as $r) {
            $sid = (int) $r->store_id;
            $byStore[$sid] ??= [];
            if (count($byStore[$sid]) < 3) {
                $byStore[$sid][] = $r->title;
            }
        }
        return $byStore;
    }

    /**
     * Distinct category names per store (Vendor -> Category edge of the knowledge graph).
     *
     * @param array<int> $storeIds
     * @return array<int, array<string>>
     */
    private function categoriesByStore(array $storeIds): array
    {
        if (empty($storeIds)) {
            return [];
        }
        $rows = DB::table('item_store as ist')
            ->join('items as i', 'i.id', '=', 'ist.item_id')
            ->join('categories as c', 'c.id', '=', 'i.category_id')
            ->whereIn('ist.store_id', $storeIds)
            ->where('i.status', 1)
            ->distinct()
            ->select('ist.store_id', 'c.name')
            ->get();

        $byStore = [];
        foreach ($rows as $r) {
            $sid = (int) $r->store_id;
            $byStore[$sid] ??= [];
            if (count($byStore[$sid]) < 5 && !in_array($r->name, $byStore[$sid], true)) {
                $byStore[$sid][] = $r->name;
            }
        }
        return $byStore;
    }

    /**
     * Up to 5 service (item) names per store, for display + the answer context.
     *
     * @param array<int> $storeIds
     * @return array<int, array<string>>
     */
    private function topServices(array $storeIds): array
    {
        if (empty($storeIds)) {
            return [];
        }
        $rows = DB::table('item_store as ist')
            ->join('items as i', 'i.id', '=', 'ist.item_id')
            ->whereIn('ist.store_id', $storeIds)
            ->where('i.status', 1)
            ->orderByDesc('i.order_count')
            ->select('ist.store_id', 'i.name')
            ->get();

        $byStore = [];
        foreach ($rows as $r) {
            $sid = (int) $r->store_id;
            $byStore[$sid] ??= [];
            if (count($byStore[$sid]) < 5 && !in_array($r->name, $byStore[$sid], true)) {
                $byStore[$sid][] = $r->name;
            }
        }
        return $byStore;
    }

    private function composeAnswer(string $query, array $results): string
    {
        if (empty($results)) {
            return '';
        }
        $businesses = array_map(fn($r) => [
            'name'        => $r['name'],
            'city'        => $r['city'],
            'services'    => $r['services'] ? implode(', ', $r['services']) : null,
            'rating'      => $r['rating'],
            'trust_score' => $r['trust_score'],
            'distance_km' => $r['distance_km'],
        ], array_slice($results, 0, 5));

        try {
            $response = Http::timeout(30)->post("{$this->businessUrl}/ask", [
                'query'      => $query,
                'businesses' => $businesses,
            ]);
            if ($response->ok()) {
                return (string) $response->json('answer', '');
            }
        } catch (\Exception $e) {
            Log::warning('AI search: /business/ask failed', ['error' => $e->getMessage()]);
        }
        return '';
    }
}
