<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class MarketingDashboardController extends Controller
{
    private function moduleId(): int
    {
        return (int) Config::get('module.current_module_id');
    }

    public function index(Request $request)
    {
        $moduleId = $this->moduleId();
        $preset   = $request->get('date_range', 'last_30_days');
        $custom   = $request->get('custom_date_range');
        $range    = Helpers::calculatePresetDates($preset, $custom);
        $from     = $range['start']->format('Y-m-d');
        $to       = $range['end']->format('Y-m-d');

        // ── Summary cards ────────────────────────────────────────────────────
        // Store visits scoped to this module's stores
        $moduleStoreIds = DB::table('stores')->where('module_id', $moduleId)->pluck('id');

        $totalVisits = DB::table('analytics_logs')
            ->where('screen_type', 'store')
            ->whereIn('ref_id', $moduleStoreIds)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->count();

        $moduleBannerIds = DB::table('banners')->where('module_id', $moduleId)->pluck('id');

        $totalBannerClicks = DB::table('analytics_logs')
            ->where('screen_type', 'banner')
            ->whereIn('ref_id', $moduleBannerIds)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->count();

        $totalLeads = DB::table('analytics_logs')
            ->where('screen_type', 'call')
            ->whereIn('ref_id', $moduleStoreIds)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->count();

        $totalLocationViews = DB::table('analytics_logs')
            ->where('screen_type', 'location')
            ->whereIn('ref_id', $moduleStoreIds)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->count();


        $totalAdClicks = DB::table('analytics_logs')
            ->where('screen_type', 'ad')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->count();

        // ── Top categories by store visits ───────────────────────────────────
        $topCategoriesByVisits = DB::table('analytics_logs as al')
            ->join('stores as s', 'al.ref_id', '=', 's.id')
            ->join('items as i', DB::raw('FIND_IN_SET(s.id, i.store_ids)'), '>', DB::raw('0'))
            ->join('categories as c', 'i.category_id', '=', 'c.id')
            ->where('al.screen_type', 'store')
            ->where('s.module_id', $moduleId)
            ->where('c.parent_id', 0)
            ->whereDate('al.created_at', '>=', $from)
            ->whereDate('al.created_at', '<=', $to)
            ->select('c.id', 'c.name', DB::raw('COUNT(DISTINCT s.id) as store_count'), DB::raw('COUNT(al.id) as visit_count'))
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('visit_count')
            ->limit(10)
            ->get();

        // ── Stores with most banners ─────────────────────────────────────────
        $topBannerStores = DB::table('banners as b')
            ->join('stores as s', 'b.data', '=', 's.id')
            ->where('b.created_by', 'store')
            ->where('b.module_id', $moduleId)
            ->select('s.id', 's.name', DB::raw('COUNT(b.id) as banner_count'), DB::raw('SUM(b.total_clicks) as total_clicks'))
            ->groupBy('s.id', 's.name')
            ->orderByDesc('banner_count')
            ->limit(10)
            ->get();

        // ── Categories generating most leads (calls) ─────────────────────────
        $topCategoriesByLeads = DB::table('analytics_logs as al')
            ->join('stores as s', 'al.ref_id', '=', 's.id')
            ->join('items as i', DB::raw('FIND_IN_SET(s.id, i.store_ids)'), '>', DB::raw('0'))
            ->join('categories as c', 'i.category_id', '=', 'c.id')
            ->where('al.screen_type', 'call')
            ->where('s.module_id', $moduleId)
            ->where('c.parent_id', 0)
            ->whereDate('al.created_at', '>=', $from)
            ->whereDate('al.created_at', '<=', $to)
            ->select('c.id', 'c.name', DB::raw('COUNT(DISTINCT s.id) as store_count'), DB::raw('COUNT(al.id) as lead_count'))
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('lead_count')
            ->limit(10)
            ->get();

        // ── Lead activity per store ───────────────────────────────────────────
        $storeLeadActivity = DB::table('analytics_logs as al')
            ->join('stores as s', 'al.ref_id', '=', 's.id')
            ->where('al.screen_type', 'call')
            ->where('s.module_id', $moduleId)
            ->whereDate('al.created_at', '>=', $from)
            ->whereDate('al.created_at', '<=', $to)
            ->select('s.id', 's.name', DB::raw('COUNT(al.id) as lead_count'), DB::raw('COUNT(DISTINCT al.user_id) as unique_leads'))
            ->groupBy('s.id', 's.name')
            ->orderByDesc('lead_count')
            ->limit(15)
            ->get();

        // ── Areas (zones) generating most leads ──────────────────────────────
        $topAreasByLeads = DB::table('analytics_logs as al')
            ->join('stores as s', 'al.ref_id', '=', 's.id')
            ->join('zones as z', 's.zone_id', '=', 'z.id')
            ->where('al.screen_type', 'call')
            ->where('s.module_id', $moduleId)
            ->whereDate('al.created_at', '>=', $from)
            ->whereDate('al.created_at', '<=', $to)
            ->select('z.id', 'z.name', DB::raw('COUNT(al.id) as lead_count'), DB::raw('COUNT(DISTINCT al.ref_id) as store_count'))
            ->groupBy('z.id', 'z.name')
            ->orderByDesc('lead_count')
            ->limit(10)
            ->get();

        // ── Top areas by store visits ─────────────────────────────────────────
        $topAreasByVisits = DB::table('analytics_logs as al')
            ->join('stores as s', 'al.ref_id', '=', 's.id')
            ->join('zones as z', 's.zone_id', '=', 'z.id')
            ->where('al.screen_type', 'store')
            ->where('s.module_id', $moduleId)
            ->whereDate('al.created_at', '>=', $from)
            ->whereDate('al.created_at', '<=', $to)
            ->select('z.id', 'z.name', DB::raw('COUNT(al.id) as visit_count'), DB::raw('COUNT(DISTINCT al.ref_id) as store_count'))
            ->groupBy('z.id', 'z.name')
            ->orderByDesc('visit_count')
            ->limit(10)
            ->get();

        return view('admin-views.marketing-dashboard.index', compact(
            'preset', 'from', 'to',
            'totalVisits', 'totalBannerClicks', 'totalLeads', 'totalLocationViews', 'totalAdClicks',
            'topCategoriesByVisits', 'topBannerStores', 'topCategoriesByLeads',
            'storeLeadActivity', 'topAreasByLeads', 'topAreasByVisits'
        ));
    }

    public function chartData(Request $request)
    {
        $moduleId  = $this->moduleId();
        $days      = max(7, min(90, (int) $request->get('days', 30)));
        $startDate = now()->subDays($days - 1)->startOfDay();

        $moduleStoreIds  = DB::table('stores')->where('module_id', $moduleId)->pluck('id');
        $moduleBannerIds = DB::table('banners')->where('module_id', $moduleId)->pluck('id');

        $labels       = [];
        $visits       = array_fill(0, $days, 0);
        $leads        = array_fill(0, $days, 0);
        $bannerClicks = array_fill(0, $days, 0);

        for ($i = 0; $i < $days; $i++) {
            $labels[] = $startDate->copy()->addDays($i)->format('d M');
        }

        // Store visits + calls scoped to module stores
        $storeRows = DB::table('analytics_logs')
            ->whereDate('created_at', '>=', $startDate)
            ->whereIn('screen_type', ['store', 'call'])
            ->whereIn('ref_id', $moduleStoreIds)
            ->selectRaw('screen_type, DATE(created_at) as date, COUNT(*) as cnt')
            ->groupBy('screen_type', 'date')
            ->get();

        foreach ($storeRows as $row) {
            $idx = $startDate->diffInDays($row->date);
            if ($idx < 0 || $idx >= $days) continue;
            if ($row->screen_type === 'store') $visits[$idx] = (int) $row->cnt;
            if ($row->screen_type === 'call')  $leads[$idx]  = (int) $row->cnt;
        }

        // Banner clicks scoped to module banners
        $bannerRows = DB::table('analytics_logs')
            ->whereDate('created_at', '>=', $startDate)
            ->where('screen_type', 'banner')
            ->whereIn('ref_id', $moduleBannerIds)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as cnt')
            ->groupBy('date')
            ->get();

        foreach ($bannerRows as $row) {
            $idx = $startDate->diffInDays($row->date);
            if ($idx >= 0 && $idx < $days) {
                $bannerClicks[$idx] = (int) $row->cnt;
            }
        }

        return response()->json(compact('labels', 'visits', 'leads', 'bannerClicks'));
    }
}
