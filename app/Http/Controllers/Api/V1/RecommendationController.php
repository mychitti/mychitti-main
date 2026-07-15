<?php 

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
 
/**
 * Personalized Recommendation Engine (Phase 4 §4.2) — home-feed sections for consumers.
 * Reuses Phase 3 signals (trust score, offers, lead_signals, distance). All sections are scoped
 * to the user's city/zone(s). Feeds: nearby, trending-this-week, recommended-for-you, top-rated,
 * special-offers. 
 */
class RecommendationController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'latitude'   => 'nullable|numeric|between:-90,90',
            'longitude'  => 'nullable|numeric|between:-180,180',
            'zone_ids'   => 'nullable|array',
            'zone_ids.*' => 'integer',
            'user_id'    => 'nullable|integer',
            'limit'      => 'nullable|integer|min:1|max:20',
        ]);

        $lat     = $validated['latitude']  ?? null;
        $lng     = $validated['longitude'] ?? null;
        $zoneIds = array_values(array_filter(array_map('intval', $validated['zone_ids'] ?? [])));
        $userId  = $validated['user_id'] ?? null;
        $limit   = (int) ($validated['limit'] ?? 10);

        return response()->json([
            'success' => true,
            'feeds'   => [
                'nearby'           => $this->nearby($zoneIds, $lat, $lng, $limit),
                'trending'         => $this->trending($zoneIds, $lat, $lng, $limit),
                'recommended'      => $this->forYou($zoneIds, $lat, $lng, $userId, $limit),
                'top_rated'        => $this->topRated($zoneIds, $lat, $lng, $limit),
                'special_offers'   => $this->offers($zoneIds, $limit),
            ],
        ]);
    }

    /** Base store query, scoped to zone + active. */
    private function baseStores(array $zoneIds)
    {
        return DB::table('stores')
            ->leftJoin('zones', 'zones.id', '=', 'stores.zone_id')
            ->where('stores.status', 1)
            ->where('stores.active', 1)
            ->when($zoneIds, fn($q) => $q->whereIn('stores.zone_id', $zoneIds))
            ->select(
                'stores.id', 'stores.name', 'stores.slug', 'stores.address', 'stores.logo',
                'stores.average_rating', 'stores.rating_count', 'stores.total_order',
                'stores.latitude', 'stores.longitude', 'stores.gst',
                DB::raw('COALESCE(stores.vendor_trust_score, 0) as vendor_trust_score'),
                'zones.name as zone_name', 'zones.slug as zone_slug'
            );
    }

    /** Closest active stores (falls back to rating when no coordinates). */
    private function nearby(array $zoneIds, ?float $lat, ?float $lng, int $limit): array
    {
        $stores = $this->baseStores($zoneIds)
            ->orderByDesc('stores.average_rating')
            ->limit(60)
            ->get();

        return $this->rankByDistance($stores, $lat, $lng)->take($limit)->map(fn($s) => $this->present($s, $lat, $lng))->values()->all();
    }

    /** Most contacted / booked in the last 7 days (Phase 3 lead_signals + orders). */
    private function trending(array $zoneIds, ?float $lat, ?float $lng, int $limit): array
    {
        $since = now()->subDays(7)->toDateTimeString();

        $hot = DB::table('lead_signals')
            ->where('created_at', '>=', $since)
            ->select('store_id', DB::raw('COUNT(*) as hits'))
            ->groupBy('store_id')
            ->orderByDesc('hits')
            ->limit(80)
            ->pluck('hits', 'store_id');

        if ($hot->isEmpty()) {
            // No recent signals yet — fall back to all-time order volume.
            $stores = $this->baseStores($zoneIds)->orderByDesc('stores.total_order')->limit($limit)->get();
            return $stores->map(fn($s) => $this->present($s, $lat, $lng))->all();
        }

        $stores = $this->baseStores($zoneIds)->whereIn('stores.id', $hot->keys())->get()
            ->sortByDesc(fn($s) => $hot[$s->id] ?? 0)
            ->take($limit);

        return $stores->map(fn($s) => $this->present($s, $lat, $lng))->values()->all();
    }

    /** Recommended for the user from their recent enquiries' categories; else top-rated. */
    private function forYou(array $zoneIds, ?float $lat, ?float $lng, ?int $userId, int $limit): array
    {
        if (!$userId) {
            return $this->topRated($zoneIds, $lat, $lng, $limit);
        }

        // Categories the user recently showed intent in (via service_requests).
        $categoryIds = DB::table('service_requests as sr')
            ->join('items as i', 'i.id', '=', 'sr.item_id')
            ->where('sr.user_id', $userId)
            ->where('sr.created_at', '>=', now()->subDays(90)->toDateTimeString())
            ->distinct()
            ->pluck('i.category_id')
            ->filter()
            ->all();

        if (empty($categoryIds)) {
            return $this->topRated($zoneIds, $lat, $lng, $limit);
        }

        $stores = $this->baseStores($zoneIds)
            ->whereExists(function ($q) use ($categoryIds) {
                $q->select(DB::raw(1))
                    ->from('item_store as ist')
                    ->join('items as i', 'i.id', '=', 'ist.item_id')
                    ->whereColumn('ist.store_id', 'stores.id')
                    ->where('i.status', 1)
                    ->whereIn('i.category_id', $categoryIds);
            })
            ->orderByDesc('stores.vendor_trust_score')
            ->orderByDesc('stores.average_rating')
            ->limit($limit)
            ->get();

        if ($stores->isEmpty()) {
            return $this->topRated($zoneIds, $lat, $lng, $limit);
        }

        return $stores->map(fn($s) => $this->present($s, $lat, $lng))->all();
    }

    /** Highest trust + rating in the zone. */
    private function topRated(array $zoneIds, ?float $lat, ?float $lng, int $limit): array
    {
        $stores = $this->baseStores($zoneIds)
            ->where('stores.rating_count', '>', 0)
            ->orderByDesc('stores.vendor_trust_score')
            ->orderByDesc('stores.average_rating')
            ->limit($limit)
            ->get();

        return $stores->map(fn($s) => $this->present($s, $lat, $lng))->all();
    }

    /** Active offers from verified vendors in the zone. */
    private function offers(array $zoneIds, int $limit): array
    {
        $today = now()->toDateString();

        return DB::table('store_offers')
            ->join('stores', 'stores.id', '=', 'store_offers.store_id')
            ->leftJoin('zones', 'zones.id', '=', 'stores.zone_id')
            ->where('stores.status', 1)->where('stores.active', 1)
            ->when($zoneIds, fn($q) => $q->whereIn('stores.zone_id', $zoneIds))
            ->where('store_offers.status', 1)
            ->where(fn($w) => $w->whereNull('store_offers.start_date')->orWhere('store_offers.start_date', '<=', $today))
            ->where(fn($w) => $w->whereNull('store_offers.end_date')->orWhere('store_offers.end_date', '>=', $today))
            ->orderByDesc('store_offers.created_at')
            ->limit($limit)
            ->get([
                'store_offers.id', 'store_offers.title', 'store_offers.description',
                'store_offers.discount_type', 'store_offers.discount_value', 'store_offers.end_date',
                'stores.id as store_id', 'stores.name as store_name', 'stores.slug as store_slug',
                'stores.logo as store_logo', 'zones.slug as zone_slug',
            ])
            ->map(function ($o) {
                $o->booking_url = ($o->zone_slug && $o->store_slug) ? url("{$o->zone_slug}/store/{$o->store_slug}") : null;
                return $o;
            })
            ->all();
    }

    /** Sort a collection by distance when coordinates are available. */
    private function rankByDistance($stores, ?float $lat, ?float $lng)
    {
        if ($lat === null || $lng === null) {
            return $stores;
        }
        return $stores->sortBy(fn($s) => $this->distanceKm($lat, $lng, $s->latitude, $s->longitude) ?? 99999);
    }

    private function present($s, ?float $lat, ?float $lng): array
    {
        $citySlug = $s->zone_slug ?: null;
        return [
            'id'           => (int) $s->id,
            'name'         => $s->name,
            'slug'         => $s->slug,
            'logo'         => $s->logo,
            'city'         => $s->zone_name ? trim(explode(',', $s->zone_name)[0]) : null,
            'rating'       => $s->average_rating !== null ? round((float) $s->average_rating, 1) : null,
            'rating_count' => (int) $s->rating_count,
            'trust_score'  => (int) $s->vendor_trust_score,
            'badges'       => store_trust_badges($s),
            'distance_km'  => $this->distanceKm($lat, $lng, $s->latitude, $s->longitude),
            'booking_url'  => ($citySlug && $s->slug) ? url("{$citySlug}/store/{$s->slug}") : null,
        ];
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
        $x = cos(deg2rad($lat)) * cos(deg2rad($storeLat)) * cos(deg2rad($storeLng) - deg2rad($lng))
            + sin(deg2rad($lat)) * sin(deg2rad($storeLat));
        $x = min(1.0, max(-1.0, $x));
        return round(6371 * acos($x), 2);
    }
}
