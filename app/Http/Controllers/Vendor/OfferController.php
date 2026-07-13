<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\StoreOffer;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
 
/**
 * Local Offers Engine (Phase 3 §3.5) — vendor CRUD for time-limited offers. Active offers are
 * surfaced in AI Search result cards (AiSearchController) and via the public offers API.
 */
class OfferController extends Controller
{
    public function index()
    {
        $offers = StoreOffer::where('store_id', Helpers::get_store_id()) 
            ->latest()
            ->paginate(config('default_pagination', 25));

        return view('vendor-views.offer.index', compact('offers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'          => 'required|string|max:150',
            'description'    => 'nullable|string|max:500',
            'discount_type'  => 'required|in:percent,flat,info',
            'discount_value' => 'nullable|numeric|min:0|required_unless:discount_type,info',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
            'category_id'    => 'nullable|integer',
        ]);

        StoreOffer::create([
            'store_id'       => Helpers::get_store_id(),
            'category_id'    => $request->category_id ?: null,
            'title'          => $request->title,
            'description'    => $request->description,
            'discount_type'  => $request->discount_type,
            'discount_value' => $request->discount_type === 'info' ? null : $request->discount_value,
            'start_date'     => $request->start_date ?: null,
            'end_date'       => $request->end_date ?: null,
            'status'         => 1,
        ]);

        Toastr::success('Offer published successfully.');
        return back();
    }

    public function update(Request $request, int $id)
    {
        $offer = $this->ownOffer($id);
        $request->validate([
            'title'          => 'required|string|max:150',
            'description'    => 'nullable|string|max:500',
            'discount_type'  => 'required|in:percent,flat,info',
            'discount_value' => 'nullable|numeric|min:0|required_unless:discount_type,info',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
        ]);

        $offer->update([
            'title'          => $request->title,
            'description'    => $request->description,
            'discount_type'  => $request->discount_type,
            'discount_value' => $request->discount_type === 'info' ? null : $request->discount_value,
            'start_date'     => $request->start_date ?: null,
            'end_date'       => $request->end_date ?: null,
        ]);

        Toastr::success('Offer updated successfully.');
        return back();
    }

    public function status(int $id)
    {
        $offer = $this->ownOffer($id);
        $offer->update(['status' => !$offer->status]);
        Toastr::success('Offer status updated.');
        return back();
    }

    public function destroy(int $id)
    {
        $this->ownOffer($id)->delete();
        Toastr::success('Offer deleted.');
        return back();
    }

    private function ownOffer(int $id): StoreOffer
    {
        return StoreOffer::where('store_id', Helpers::get_store_id())->findOrFail($id);
    }
}
