<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\HospitalBedTier;
use App\Models\Store;
use App\Models\VendorSubscription;
use App\Models\Ward;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class WardController extends Controller
{
    // ── Ward CRUD ─────────────────────────────────────────────────────────

    public function index()
    {
        $store_id = Helpers::get_store_id();
        $wards    = Ward::where('store_id', $store_id)
            ->withCount(['beds', 'availableBeds'])
            ->orderBy('ward_name')
            ->get();

        return view('vendor-views.ward.index', compact('wards'));
    }

    public function create()
    {
        return view('vendor-views.ward.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'ward_name'    => 'required|string|max:100',
            'ward_type'    => 'required|in:' . implode(',', array_keys(Ward::TYPES)),
            'floor'        => 'nullable|string|max:50',
            'total_beds'   => 'required|integer|min:1',
            'daily_charge' => 'nullable|numeric|min:0',
        ]);

        Ward::create([
            'store_id'     => Helpers::get_store_id(),
            'ward_name'    => $request->ward_name,
            'ward_type'    => $request->ward_type,
            'floor'        => $request->floor,
            'total_beds'   => $request->total_beds,
            'daily_charge' => $request->daily_charge ?? 0,
            'is_active'    => true,
        ]);

        Toastr::success('Ward created successfully.');
        return Redirect::route('vendor.ward.index');
    }

    public function edit($id)
    {
        $store_id = Helpers::get_store_id();
        $ward     = Ward::where('store_id', $store_id)->findOrFail($id);
        return view('vendor-views.ward.edit', compact('ward'));
    }

    public function update(Request $request, $id)
    {
        $store_id = Helpers::get_store_id();
        $ward     = Ward::where('store_id', $store_id)->findOrFail($id);

        $request->validate([
            'ward_name'    => 'required|string|max:100',
            'ward_type'    => 'required|in:' . implode(',', array_keys(Ward::TYPES)),
            'floor'        => 'nullable|string|max:50',
            'total_beds'   => 'required|integer|min:1',
            'daily_charge' => 'nullable|numeric|min:0',
        ]);

        $ward->update([
            'ward_name'    => $request->ward_name,
            'ward_type'    => $request->ward_type,
            'floor'        => $request->floor,
            'total_beds'   => $request->total_beds,
            'daily_charge' => $request->daily_charge ?? 0,
            'is_active'    => $request->has('is_active'),
        ]);

        Toastr::success('Ward updated successfully.');
        return Redirect::route('vendor.ward.index');
    }

    public function destroy($id)
    {
        $store_id = Helpers::get_store_id();
        $ward     = Ward::where('store_id', $store_id)->findOrFail($id);

        if ($ward->beds()->where('status', 'occupied')->exists()) {
            Toastr::error('Cannot delete ward with occupied beds.');
            return Redirect::route('vendor.ward.index');
        }

        $ward->beds()->delete();
        $ward->delete();

        Toastr::success('Ward deleted.');
        return Redirect::route('vendor.ward.index');
    }

    public function toggleStatus($id)
    {
        $store_id = Helpers::get_store_id();
        $ward     = Ward::where('store_id', $store_id)->findOrFail($id);
        $ward->update(['is_active' => !$ward->is_active]);
        return response()->json(['status' => $ward->is_active]);
    }

    // ── Bed management ────────────────────────────────────────────────────

    public function beds($wardId)
    {
        $store_id = Helpers::get_store_id();
        $ward     = Ward::where('store_id', $store_id)->findOrFail($wardId);
        $beds     = Bed::where('ward_id', $wardId)
            ->with('activeAdmission.patient')
            ->orderBy('bed_number')
            ->get();

        return view('vendor-views.ward.beds', compact('ward', 'beds'));
    }

    public function bedStore(Request $request, $wardId)
    {
        $store_id = Helpers::get_store_id();
        $ward     = Ward::where('store_id', $store_id)->findOrFail($wardId);

        $request->validate([
            'bed_number'   => 'required|string|max:20',
            'bed_type'     => 'nullable|in:' . implode(',', array_keys(Bed::TYPES)),
            'daily_charge' => 'nullable|numeric|min:0',
        ]);

        $exists = Bed::where('ward_id', $wardId)->where('bed_number', $request->bed_number)->exists();
        if ($exists) {
            Toastr::error('Bed number already exists in this ward.');
            return back();
        }

        // Enforce bed limit based on active subscription tier
        $store = Store::find($store_id);
        $subscription = VendorSubscription::where('vendor_id', $store->id)
            ->whereNotNull('bed_tier_id')
            ->where('plan_expiry', '>=', now())
            ->latest()
            ->first();


        if ($subscription && $subscription->bed_tier_id) {
            $tier = HospitalBedTier::find($subscription->bed_tier_id);
            // prx($tier);
            if ($tier && !is_null($tier->max_beds)) {
                $currentBedCount = Bed::where('store_id', $store_id)->count();
                if ($currentBedCount >= $tier->max_beds) {
                    Toastr::error("Bed limit reached for your plan ({$tier->tier_name}: {$tier->bed_range}). Please upgrade to add more beds.");
                    return back();
                }
            }
        }

        Bed::create([
            'store_id'     => $store_id,
            'ward_id'      => $wardId,
            'bed_number'   => $request->bed_number,
            'bed_type'     => $request->bed_type ?? 'general',
            'status'       => 'available',
            'daily_charge' => $request->daily_charge ?? $ward->daily_charge,
        ]);

        Toastr::success('Bed added.');
        return Redirect::route('vendor.ward.beds', $wardId);
    }

    public function bedUpdate(Request $request, $wardId, $bedId)
    {
        $store_id = Helpers::get_store_id();
        Ward::where('store_id', $store_id)->findOrFail($wardId);
        $bed = Bed::where('ward_id', $wardId)->findOrFail($bedId);

        $request->validate([
            'bed_number'   => 'required|string|max:20',
            'bed_type'     => 'nullable|in:' . implode(',', array_keys(Bed::TYPES)),
            'status'       => 'nullable|in:available,occupied,reserved,maintenance',
            'daily_charge' => 'nullable|numeric|min:0',
        ]);

        $dup = Bed::where('ward_id', $wardId)
            ->where('bed_number', $request->bed_number)
            ->where('id', '!=', $bedId)
            ->exists();
        if ($dup) {
            Toastr::error('Bed number already exists in this ward.');
            return back();
        }

        $bed->update([
            'bed_number'   => $request->bed_number,
            'bed_type'     => $request->bed_type ?? $bed->bed_type,
            'status'       => $request->status ?? $bed->status,
            'daily_charge' => $request->daily_charge ?? $bed->daily_charge,
        ]);

        Toastr::success('Bed updated.');
        return Redirect::route('vendor.ward.beds', $wardId);
    }

    public function bedDestroy($wardId, $bedId)
    {
        $store_id = Helpers::get_store_id();
        Ward::where('store_id', $store_id)->findOrFail($wardId);
        $bed = Bed::where('ward_id', $wardId)->findOrFail($bedId);

        if ($bed->status === 'occupied') {
            Toastr::error('Cannot delete an occupied bed.');
            return back();
        }

        $bed->delete();
        Toastr::success('Bed deleted.');
        return Redirect::route('vendor.ward.beds', $wardId);
    }
}
