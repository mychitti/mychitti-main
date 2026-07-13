<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StoreOffer;
use Illuminate\Http\Request;
 
/**
 * Public Local Offers API (Phase 3 §3.5) — active offers for a store, or a recent feed across
 * all stores for the "Special Offers from verified vendors" home feed.
 */
class OfferController extends Controller
{
    public function byStore(int $store_id)
    {
        $offers = StoreOffer::active()->where('store_id', $store_id)->latest()->get()
            ->map(fn($o) => $this->present($o));

        return response()->json(['success' => true, 'offers' => $offers]); 
    }

    public function feed(Request $request)
    {
        $limit = min((int) $request->input('limit', 20), 50);

        $offers = StoreOffer::active()
            ->join('stores', 'stores.id', '=', 'store_offers.store_id')
            ->where('stores.status', 1)->where('stores.active', 1)
            ->when($request->filled('zone_id'), fn($q) => $q->where('stores.zone_id', (int) $request->zone_id))
            ->orderByDesc('store_offers.created_at')
            ->limit($limit)
            ->get([
                'store_offers.id', 'store_offers.store_id', 'store_offers.title', 'store_offers.description',
                'store_offers.discount_type', 'store_offers.discount_value', 'store_offers.end_date',
                'stores.name as store_name', 'stores.logo as store_logo', 'stores.slug as store_slug',
            ]);

        return response()->json(['success' => true, 'offers' => $offers]);
    }

    private function present(StoreOffer $o): array
    {
        return [
            'id'          => $o->id,
            'title'       => $o->title,
            'description' => $o->description,
            'label'       => $o->label,
            'ends_on'     => optional($o->end_date)->toDateString(),
        ];
    }
}
