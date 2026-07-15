<?php
 
namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
 
/**    
 * Business Insights (Phase 4 §4.3) — trends & reputation for the vendor. Complements, not
 * duplicates: Performance Analytics = marketing counts; Lead Inbox = the actionable follow-up list;
 * this = trends over time + review sentiment + trust breakdown. All from Phase 3 data.
 */ 
class InsightsController extends Controller
{
    private const SOFT_TYPES = ['call', 'whatsapp', 'direction', 'website'];

    public function index()
    {
        $storeId = Helpers::get_store_id();
        $since   = now()->subDays(30)->toDateTimeString();

        // Lead signals (last 30 days) — total + by type.
        $byType = DB::table('lead_signals')
            ->where('store_id', $storeId)->where('created_at', '>=', $since)
            ->whereIn('type', self::SOFT_TYPES)
            ->select('type', DB::raw('COUNT(*) as c'))->groupBy('type')->pluck('c', 'type');
        $leadTotal = (int) array_sum($byType->all());

        // Daily leads for the last 14 days (for the bar chart).
        $dailyRaw = DB::table('lead_signals')
            ->where('store_id', $storeId)->whereIn('type', self::SOFT_TYPES)
            ->where('created_at', '>=', now()->subDays(13)->startOfDay()->toDateTimeString())
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c'))
            ->groupBy('d')->pluck('c', 'd');
        $daily = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $daily[$d] = (int) ($dailyRaw[$d] ?? 0);
        }

        // Review sentiment from the customer's OWN star rating (the explicit signal — can't
        // contradict itself). ≥4 = positive, 3 = neutral, ≤2 = negative. This avoids the AI
        // text-classifier occasionally mislabeling a happy 4★ review as negative.
        $reviewQ = fn() => DB::table('store_reviews')->where('store_id', $storeId)
            ->where('status', 1)->whereNotNull('rating')->where('rating', '>', 0);
        $sent = [
            'positive' => (int) $reviewQ()->where('rating', '>=', 4)->count(),
            'neutral'  => (int) $reviewQ()->where('rating', '=', 3)->count(),
            'negative' => (int) $reviewQ()->where('rating', '<=', 2)->count(),
        ];
        $sentTotal = array_sum($sent);

        // Trust + rating + offers.
        $store = DB::table('stores')->where('id', $storeId)
            ->first(['gst', 'average_rating', 'rating_count', 'total_order',
                DB::raw('COALESCE(vendor_trust_score, 0) as vendor_trust_score')]);

        $today = now()->toDateString();
        $activeOffers = DB::table('store_offers')->where('store_id', $storeId)->where('status', 1)
            ->where(fn($w) => $w->whereNull('start_date')->orWhere('start_date', '<=', $today))
            ->where(fn($w) => $w->whereNull('end_date')->orWhere('end_date', '>=', $today))
            ->count();

        $badges = $store ? store_trust_badges($store) : [];

        return view('vendor-views.insights.index', [
            'leadTotal'    => $leadTotal,
            'byType'       => $byType,
            'daily'        => $daily,
            'sent'         => $sent,
            'sentTotal'    => $sentTotal,
            'store'        => $store,
            'badges'       => $badges,
            'activeOffers' => $activeOffers,
        ]);
    }
}
