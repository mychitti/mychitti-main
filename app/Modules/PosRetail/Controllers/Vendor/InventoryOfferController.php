<?php

namespace App\Modules\PosRetail\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\InventoryOffer;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class InventoryOfferController extends Controller
{
    public function index(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $offers = InventoryOffer::with(['item', 'rewardProduct'])
            ->where('store_id', $store_id)
            ->when($request->filled('item_id'), function ($q) use ($request) {
                $id = (int) $request->item_id;
                $q->where(function ($w) use ($id) {
                    $w->where('item_id', $id)
                        ->orWhere('reward_product_id', $id)
                        ->orWhereJsonContains('buy_product_ids', $id)
                        ->orWhereJsonContains('buy_product_ids', (string) $id);
                });
            })
            ->orderByDesc('id')
            ->paginate(30);

        // Preload names for buy products / rewards / anchors so the cards avoid N+1.
        $itemIds = [];
        foreach ($offers as $o) {
            if ($o->item_id) $itemIds[] = (int) $o->item_id;
            if ($o->reward_product_id) $itemIds[] = (int) $o->reward_product_id;
            foreach ((array) $o->buy_product_ids as $b) $itemIds[] = (int) $b;
        }
        $itemNames = InventoryItem::whereIn('id', array_unique($itemIds))->pluck('item_name', 'id');
        $branchNames = Branch::where('store_id', $store_id)->pluck('name', 'id');

        return view('posretail::vendor-views.inventory.offer.index', compact('offers', 'itemNames', 'branchNames'));
    }

    public function create(Request $request, $item_id = null)
    {
        $store_id = Helpers::get_store_id();

        $item = null;
        if ($item_id) {
            $item = InventoryItem::where('store_id', $store_id)->findOrFail($item_id);
        }

        $branches = Branch::where('store_id', $store_id)->get();
        $offer = null;
        $buyProducts = collect();
        $rewardProduct = null;

        return view('posretail::vendor-views.inventory.offer.create', compact('item', 'branches', 'offer', 'buyProducts', 'rewardProduct'));
    }

    public function edit(Request $request, $id)
    {
        $store_id = Helpers::get_store_id();
        $offer = InventoryOffer::where('store_id', $store_id)->findOrFail($id);

        $item = $offer->item_id ? InventoryItem::where('store_id', $store_id)->find($offer->item_id) : null;
        $branches = Branch::where('store_id', $store_id)->get();

        $buyIds = array_map('intval', (array) $offer->buy_product_ids);
        $buyProducts = InventoryItem::whereIn('id', $buyIds)->get(['id', 'item_name', 'sku_id', 'stock']);
        $rewardProduct = $offer->reward_product_id
            ? InventoryItem::find($offer->reward_product_id)
            : null;

        return view('posretail::vendor-views.inventory.offer.create', compact('item', 'branches', 'offer', 'buyProducts', 'rewardProduct'));
    }

    public function searchItems(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $q = trim($request->get('q', ''));

        // Variation SKUs live in inv_item_variation_details; match there too so a variation SKU
        // surfaces its parent item. Store scope is enforced by the outer where('store_id').
        $varItemIds = $q === '' ? [] : \App\Models\InvItemVariationDetail::where('sku', 'like', "%{$q}%")
            ->distinct()->pluck('item_id')->all();

        $items = InventoryItem::where('store_id', $store_id)
            ->when($q !== '', function ($query) use ($q, $varItemIds) {
                $query->where(function ($sub) use ($q, $varItemIds) {
                    $sub->where('item_name', 'like', "%{$q}%")
                        ->orWhere('sku_id', 'like', "%{$q}%");
                    if (!empty($varItemIds)) {
                        $sub->orWhereIn('id', $varItemIds);
                    }
                });
            })
            ->orderBy('item_name')
            ->limit(20)
            ->get(['id', 'item_name', 'sku_id', 'stock']);

        return response()->json(
            $items->map(fn ($i) => [
                'id' => $i->id,
                'name' => $i->item_name,
                'sku' => $i->sku_id,
                'stock' => $i->stock,
            ])
        );
    }

    public function store(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $this->validateOffer($request);

        $data = $this->offerPayload($request);
        $data['store_id'] = $store_id;
        $data['banner'] = $request->hasFile('banner') ? Helpers::upload('offer/', 'png', $request->file('banner')) : null;

        InventoryOffer::create($data);

        Toastr::success('Offer created successfully');
        return redirect()->route('vendor.retail-pos.offer.index');
    }

    public function update(Request $request, $id)
    {
        $store_id = Helpers::get_store_id();
        $offer = InventoryOffer::where('store_id', $store_id)->findOrFail($id);
        $this->validateOffer($request);

        $data = $this->offerPayload($request);
        if ($request->hasFile('banner')) {
            $data['banner'] = Helpers::upload('offer/', 'png', $request->file('banner'));
        }

        $offer->update($data);

        Toastr::success('Offer updated successfully');
        return redirect()->route('vendor.retail-pos.offer.index');
    }

    private function validateOffer(Request $request): void
    {
        $request->validate([
            'offer_name' => 'required|string|max:255',
            'offer_code' => 'required|string|max:100',
            'offer_type' => 'required|in:buy_x_get_y_free,flat_discount,percent_discount,bundle_deal',
            'item_id' => 'nullable|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'buy_quantity' => 'required|integer|min:1',
            'free_quantity' => 'required|integer|min:1',
            'reward_product_id' => 'nullable|integer',
            'reward_value' => 'nullable|numeric|min:0',
            'priority' => 'required|integer|min:1',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);
    }

    private function offerPayload(Request $request): array
    {
        return [
            'item_id' => $request->item_id,
            'offer_name' => $request->offer_name,
            'offer_code' => $request->offer_code,
            'offer_type' => $request->offer_type,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'applicable_days' => $request->applicable_days ?? [],
            'all_branches' => $request->boolean('all_branches'),
            'branch_ids' => $request->all_branches ? [] : ($request->branch_ids ?? []),
            'customer_type' => $request->customer_type ?? 'all_customers',
            'apply_on' => $request->apply_on ?? 'specific_products',
            'buy_product_ids' => array_values(array_map('intval', array_filter((array) $request->buy_product_ids))),
            'buy_quantity' => $request->buy_quantity,
            'buy_type' => $request->buy_type ?? 'same_product',
            'count_based_on' => $request->count_based_on ?? 'quantity',
            'reward_type' => $request->reward_type ?? 'free_product',
            'reward_product_id' => $request->reward_product_id,
            'reward_value' => $request->reward_value,
            'free_quantity' => $request->free_quantity,
            'min_bill_value' => $request->min_bill_value,
            'max_offer_value' => $request->max_offer_value,
            'apply_only_if_reward_stock_available' => $request->boolean('apply_only_if_reward_stock_available'),
            'max_free_qty_per_bill' => $request->max_free_qty_per_bill,
            'max_uses_per_day' => $request->max_uses_per_day,
            'max_uses_per_customer' => $request->max_uses_per_customer,
            'total_campaign_limit' => $request->total_campaign_limit,
            'priority' => $request->priority,
            'combine_with_other_offers' => $request->boolean('combine_with_other_offers'),
            'show_in_pos' => $request->boolean('show_in_pos'),
            'auto_expire_after_end_date' => $request->boolean('auto_expire_after_end_date'),
            'notify_sms' => $request->boolean('notify_sms'),
            'notify_whatsapp' => $request->boolean('notify_whatsapp'),
            'notify_push' => $request->boolean('notify_push'),
            'notify_in_app' => $request->boolean('notify_in_app'),
            'customer_eligibility' => $request->customer_eligibility ?? 'all_customers',
            'allow_multiple_times' => $request->boolean('allow_multiple_times'),
            'status' => $request->input('status', 'published'),
        ];
    }

    public function delete(Request $request, $id)
    {
        $store_id = Helpers::get_store_id();
        $offer = InventoryOffer::where('store_id', $store_id)->findOrFail($id);
        if ($offer->banner) {
            Helpers::delete_file('offer/', $offer->banner);
        }
        $offer->delete();

        Toastr::success('Offer deleted successfully');
        return back();
    }
}
