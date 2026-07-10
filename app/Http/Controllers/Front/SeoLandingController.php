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
        $stores = Store::where('stores.zone_id', $zone->id)
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
            ->select('id', 'name', 'address', 'logo', 'slug', 'rating_count', 'average_rating')
            ->orderByDesc('average_rating')
            ->limit(60)
            ->get();

        $canonical = url($zone->slug . '/services/' . $category->slug . ($item ? '/' . $item->slug : ''));

        return view('front-views.seo-landing', compact('seo', 'zone', 'category', 'item', 'stores', 'canonical'));
    }
}
