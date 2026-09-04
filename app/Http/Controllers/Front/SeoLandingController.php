<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\ServiceZoneSeo;
use App\Models\Store;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;

/**
 * Programmatic SEO landing pages for (service category x city) combos.
 *
 * IMPORTANT: this controller deliberately does NOT use FrontController's _setLocation()
 * middleware. The city is resolved from the URL slug — never from the session/default zone —
 * so Googlebot (which has no session) gets the correct city's server-rendered content
 * instead of the default (Tirupati) zone.
 */
class SeoLandingController extends Controller
{
    public function show(string $citySlug, string $categorySlug, ?string $itemSlug = null)
    {
        $zone     = Zone::where('slug', $citySlug)->first();
        $category = Category::where('slug', $categorySlug)->first();
        abort_if(!$zone || !$category, 404);

        $item = null;
        if ($itemSlug) {
            $item = Item::where('slug', $itemSlug)->where('category_id', $category->id)->first();
            abort_if(!$item, 404);
        }

        $seo = ServiceZoneSeo::where('zone_id', $zone->id)
            ->where('category_id', $category->id)
            ->where('item_id', $item->id ?? 0)
            ->where('status', 'published')
            ->first();
        abort_if(!$seo, 404);

        // Real stores in THIS city offering THIS category (or this exact item) — via item_store.
        $stores = Store::visibleOnMychitti()
            ->where('stores.zone_id', $zone->id)
            ->where('stores.status', 1)
            ->where('stores.active', 1)
            ->whereExists(function ($q) use ($category, $item) {
                $q->select(DB::raw(1))
                    ->from('item_store as ist')
                    ->join('items as i', 'i.id', '=', 'ist.item_id')
                    ->whereColumn('ist.store_id', 'stores.id')
                    ->where('i.category_id', $category->id)
                    ->where('i.status', 1)
                    ->when($item, fn($q2) => $q2->where('i.id', $item->id));
            })
            ->select('id', 'name', 'address', 'logo', 'slug', 'rating_count', 'average_rating', 'gst', 'total_order')
            ->orderByDesc('average_rating')
            ->limit(60)
            ->get();

        $canonical = url($zone->slug . '/services/' . $category->slug . ($item ? '/' . $item->slug : ''));

        // On a category landing, link its published item-level sub-service landings (AC Repair, …)
        // — the proper SEO silo: category page → the specific services it contains.
        $subServices = collect();
        if (!$item) {
            $subServices = ServiceZoneSeo::where('service_zone_seo.zone_id', $zone->id)
                ->where('service_zone_seo.category_id', $category->id)
                ->where('service_zone_seo.item_id', '>', 0)
                ->where('service_zone_seo.status', 'published')
                ->join('items', 'items.id', '=', 'service_zone_seo.item_id')
                ->orderByDesc('service_zone_seo.store_count')
                ->limit(30)
                ->get(['items.name as name', 'service_zone_seo.slug as slug']);
        }

        // Same service in nearby cities — the "Category → City" internal-link pattern. Links this
        // exact category (or item) to its published landings in OTHER cities, spreading link equity
        // across the multi-city page set. Each city name is reduced to its first comma-segment.
        $otherCities = ServiceZoneSeo::join('zones', 'zones.id', '=', 'service_zone_seo.zone_id')
            ->where('service_zone_seo.category_id', $category->id)
            ->where('service_zone_seo.item_id', $item->id ?? 0)
            ->where('service_zone_seo.status', 'published')
            ->where('service_zone_seo.zone_id', '!=', $zone->id)
            ->orderByDesc('service_zone_seo.store_count')
            ->limit(20)
            ->get(['zones.name as zone_name', 'service_zone_seo.slug as slug'])
            ->map(function ($r) {
                $r->city = trim(explode(',', $r->zone_name)[0]);
                return $r;
            });

        // AggregateRating over this city's providers — feeds Schema.org aggregateRating (only
        // meaningful when at least one provider actually has reviews).
        $rated = $stores->where('rating_count', '>', 0);
        $ratingValue = $rated->count() ? round((float) $rated->avg('average_rating'), 1) : null;
        $reviewCount = (int) $rated->sum('rating_count');

        return view('front-views.seo-landing', compact(
            'seo', 'zone', 'category', 'item', 'stores', 'canonical',
            'subServices', 'otherCities', 'ratingValue', 'reviewCount'
        ));
    }
}
