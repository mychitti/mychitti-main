<?php

namespace App\Modules\HMIS\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HospitalBedTier;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class HospitalBedTierController extends Controller
{
    public function index()
    {
        $tiers = HospitalBedTier::orderBy('sort_order')->orderBy('min_beds')->get();
        return view('hmis::admin.plan.hospital_bed_tiers', compact('tiers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tier_name'     => 'required|string|max:100',
            'min_beds'      => 'required|integer|min:0',
            'max_beds'      => 'nullable|integer',
            'price_monthly' => 'required_unless:is_custom,1|nullable|numeric|min:0',
            'price_yearly'  => 'nullable|numeric|min:0',
        ]);

        HospitalBedTier::create([
            'tier_name'     => $request->tier_name,
            'min_beds'      => $request->min_beds,
            'max_beds'      => $request->max_beds ?: null,
            'price_monthly' => $request->boolean('is_custom') ? 0 : $request->price_monthly,
            'price_yearly'  => $request->boolean('is_custom') ? 0 : ($request->price_yearly ?? 0),
            'is_custom'     => $request->boolean('is_custom'),
            'is_active'     => true,
            'sort_order'    => HospitalBedTier::max('sort_order') + 1,
        ]);

        Toastr::success('Tier added successfully.');
        return back();
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tier_name'     => 'required|string|max:100',
            'min_beds'      => 'required|integer|min:0',
            'max_beds'      => 'nullable|integer',
            'price_monthly' => 'nullable|numeric|min:0',
            'price_yearly'  => 'nullable|numeric|min:0',
        ]);

        $tier = HospitalBedTier::findOrFail($id);
        $tier->update([
            'tier_name'     => $request->tier_name,
            'min_beds'      => $request->min_beds,
            'max_beds'      => $request->max_beds ?: null,
            'price_monthly' => $request->boolean('is_custom') ? 0 : $request->price_monthly,
            'price_yearly'  => $request->boolean('is_custom') ? 0 : ($request->price_yearly ?? 0),
            'is_custom'     => $request->boolean('is_custom'),
            'is_active'     => $request->boolean('is_active'),
            'sort_order'    => $request->sort_order ?? $tier->sort_order,
        ]);

        Toastr::success('Tier updated successfully.');
        return back();
    }

    public function destroy($id)
    {
        HospitalBedTier::findOrFail($id)->delete();
        Toastr::success('Tier deleted.');
        return back();
    }
}
