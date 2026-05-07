<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\DomainPurchase;
use App\Models\Store;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;

class DomainController extends Controller
{
    public function index(Request $request)
    {
        $priceSettings = [
            'charge'      => (float) (BusinessSetting::where('key', 'custom_domain_charge')->value('value') ?? 0),
            'gst_percent' => (float) (BusinessSetting::where('key', 'cd_gst_percent')->value('value') ?? 0),
            'gst_include' => (bool)  (BusinessSetting::where('key', 'cd_gst_include')->value('value') ?? 0),
        ];
       $storesQuery = Store::query()
    ->with(['vendor'])
    ->when($request->store_id_filter, fn($q) => $q->where('id', $request->store_id_filter))
    ->when(
        ($request->domain_filter ?? 'active') === 'active',
        fn($q) => $q->whereNotNull('domain')
    )
    ->when(
        ($request->domain_filter ?? 'active') === 'none',
        fn($q) => $q->whereNull('domain')
    )
    ->latest();

        $stores = $storesQuery->paginate(20)->withQueryString();

        $selectedStore = $request->store_id_filter
            ? Store::find($request->store_id_filter, ['id', 'name'])
            : null;

        $historyQuery = DomainPurchase::with('store')
            ->when($request->store_id,     fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->hist_status,  fn($q) => $q->where('status', $request->hist_status))
            ->when($request->from,         fn($q) => $q->whereDate('activated_at', '>=', $request->from))
            ->when($request->to,           fn($q) => $q->whereDate('activated_at', '<=', $request->to))
            ->latest();

        $history = $historyQuery->paginate(25)->withQueryString();

        $stats = [
            'total_active'  => DomainPurchase::where('status', 'active')->count(),
            'total_revenue' => DomainPurchase::sum('total_amount'),
            'stores_with_domain' => Store::whereNotNull('domain')->count(),
        ];

        return view('admin-views.domain.index', compact('priceSettings', 'stores', 'history', 'stats', 'selectedStore'));
    }

    public function updatePrice(Request $request)
    {
        $request->validate([
            'charge'      => 'required|numeric|min:0',
            'gst_percent' => 'required|numeric|min:0|max:100',
            'gst_include' => 'nullable',
        ]);

        $settings = [
            'custom_domain_charge' => $request->charge,
            'cd_gst_percent'       => $request->gst_percent,
            'cd_gst_include'       => $request->has('gst_include') ? 1 : 0,
        ];

        foreach ($settings as $key => $value) {
            $setting = BusinessSetting::firstOrNew(['key' => $key]);
            $setting->value = $value;
            $setting->save();
        }

        Toastr::success('Domain pricing updated successfully.');
        return back()->with('tab', 'price');
    }
}
