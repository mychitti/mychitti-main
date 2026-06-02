<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\DataSetting;
use App\Models\PlanDuration;
use App\Models\StoreWallet;
use App\Models\SubModuleDiscount;
use App\Models\Category;
use App\Models\ZoneWalletConfig;
use App\Models\LeadSubscriptionPlan;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PricingController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'wallet');

        // --- Module Pricing tab data ---
        $sub_modules        = DB::table('sub_modules')->get();
        $plan_durations     = PlanDuration::orderBy('sort_order')->get();
        $sub_module_discounts = SubModuleDiscount::all()->groupBy('sub_module_id');
        $gst_settings       = DataSetting::where('type', 'plan_gst')
            ->whereIn('key', ['gst_mode', 'gst_percent', 'hsn'])
            ->pluck('value', 'key')
            ->toArray();

        // --- Webpage Templates tab data ---
        $templates = DB::table('store_webpage_templates')->orderBy('id')->get();

        // --- Platform Fee tab data ---
        $platform_fee_subscribed   = BusinessSetting::where('key', 'platform_fee_subscribed')->value('value') ?? 0;
        $platform_fee_unsubscribed = BusinessSetting::where('key', 'platform_fee_unsubscribed')->value('value') ?? 0;

        // --- Wallet tab: settings ---
        $wallet_recharge_gst_percent = BusinessSetting::where('key', 'wallet_recharge_gst_percent')->value('value') ?? 0;
        $wallet_recharge_gst_status  = BusinessSetting::where('key', 'wallet_recharge_gst_status')->value('value') ?? 'excluded';
        $wallet_recharge_hsn         = BusinessSetting::where('key', 'wallet_recharge_hsn')->value('value') ?? '';
        $wallet_min_balance          = BusinessSetting::where('key', 'wallet_min_balance')->value('value') ?? 100;

        // --- Zone Wallet Minimums tab data (per-zone / per-category min balance to receive leads) ---
        $zones = DB::table('zones')
            ->join('module_zone', 'module_zone.zone_id', '=', 'zones.id')
            ->where('module_zone.module_id', 6)
            ->select('zones.*')
            ->get();
        $categories        = Category::where('module_id', 6)->get();
        $zoneWalletConfigs = ZoneWalletConfig::with('zone', 'category')->get();

        // --- Leads monetization tab data ---
        $leadSubPlans = LeadSubscriptionPlan::orderBy('type')->orderBy('name')->get();
        $charges = DB::table('lead_charges')
            ->join('categories', 'categories.id', '=', 'lead_charges.category_id')
            ->leftJoin('zones', 'zones.id', '=', 'lead_charges.zone_id')
            ->leftJoin('items', 'items.id', '=', 'lead_charges.item_id')
            ->select('lead_charges.*', 'categories.name as cat_name', 'zones.name as zone_name', 'items.name as item_name')
            ->paginate(50);

        return view('admin-views.pricing.index', compact(
            'tab',
            'sub_modules', 'plan_durations', 'sub_module_discounts', 'gst_settings',
            'templates',
            'platform_fee_subscribed', 'platform_fee_unsubscribed',
            'wallet_recharge_gst_percent', 'wallet_recharge_gst_status',
            'wallet_recharge_hsn', 'wallet_min_balance',
            'zones', 'categories', 'zoneWalletConfigs',
            'leadSubPlans', 'charges'
        ));
    }

    public function updatePlatformFee(Request $request)
    {
        $request->validate([
            'platform_fee_subscribed'   => 'required|numeric|min:0',
            'platform_fee_unsubscribed' => 'required|numeric|min:0',
        ]);

        BusinessSetting::updateOrInsert(
            ['key' => 'platform_fee_subscribed'],
            ['value' => $request->platform_fee_subscribed]
        );
        BusinessSetting::updateOrInsert(
            ['key' => 'platform_fee_unsubscribed'],
            ['value' => $request->platform_fee_unsubscribed]
        );

        Toastr::success('Platform fee updated successfully');
        return redirect()->route('admin.pricing.index', ['tab' => 'platform-fee']);
    }

    public function updateWalletSettings(Request $request)
    {
        $request->validate([
            'wallet_recharge_gst_percent' => 'required|numeric|min:0|max:100',
            'wallet_recharge_gst_status'  => 'required|in:included,excluded',
            'wallet_recharge_hsn'         => 'required|string|max:20',
            'wallet_min_balance'          => 'required|numeric|min:0',
        ]);

        BusinessSetting::updateOrInsert(['key' => 'wallet_recharge_gst_percent'], ['value' => $request->wallet_recharge_gst_percent]);
        BusinessSetting::updateOrInsert(['key' => 'wallet_recharge_gst_status'],  ['value' => $request->wallet_recharge_gst_status]);
        BusinessSetting::updateOrInsert(['key' => 'wallet_recharge_hsn'],         ['value' => $request->wallet_recharge_hsn]);
        BusinessSetting::updateOrInsert(['key' => 'wallet_min_balance'],          ['value' => $request->wallet_min_balance]);

        Toastr::success('Wallet settings updated successfully');
        return redirect()->route('admin.pricing.index', ['tab' => 'wallet']);
    }
}
