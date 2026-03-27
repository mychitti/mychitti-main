<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $vendorId = Helpers::get_vendor_id();
        $tab = $request->get('tab', 'store_visits');
        $search = $request->get('search');

        $preset = $request->get('date_range', 'last_30_days');
        $custom = $request->get('custom_date_range');
        $range = Helpers::calculatePresetDates($preset, $custom);
        $dateFrom = $range['start']->format('Y-m-d');
        $dateTo = $range['end']->format('Y-m-d');

        $data = [];

        if ($tab == 'store_visits') {
            $query = DB::table('analytics_logs as al')
                ->leftJoin('users as u', 'al.user_id', '=', 'u.id')
                ->where('al.screen_type', 'store')
                ->where('al.ref_id', $storeId)
                ->select('al.*', 'u.f_name', 'u.l_name', 'u.phone as user_phone');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('u.f_name', 'like', "%{$search}%")
                      ->orWhere('u.l_name', 'like', "%{$search}%")
                      ->orWhere('u.phone', 'like', "%{$search}%");
                });
            }
            if ($dateFrom) {
                $query->whereDate('al.created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('al.created_at', '<=', $dateTo);
            }

            $data['items'] = $query->orderByDesc('al.created_at')->paginate(20)->appends($request->query());

        } elseif ($tab == 'banners') {
            // Get banner IDs belonging to this store
            $bannerIds = DB::table('banners')->where('type', 'store_wise')->where('data', $storeId)->pluck('id');

            $query = DB::table('analytics_logs as al')
                ->leftJoin('users as u', 'al.user_id', '=', 'u.id')
                ->leftJoin('banners as b', 'al.ref_id', '=', 'b.id')
                ->where('al.screen_type', 'banner')
                ->whereIn('al.ref_id', $bannerIds)
                ->select('al.*', 'u.f_name', 'u.l_name', 'u.phone as user_phone', 'b.title as banner_title');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('b.title', 'like', "%{$search}%")
                      ->orWhere('u.f_name', 'like', "%{$search}%")
                      ->orWhere('u.l_name', 'like', "%{$search}%")
                      ->orWhere('u.phone', 'like', "%{$search}%");
                });
            }
            if ($dateFrom) {
                $query->whereDate('al.created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('al.created_at', '<=', $dateTo);
            }

            $data['items'] = $query->orderByDesc('al.created_at')->paginate(20)->appends($request->query());

        } elseif ($tab == 'ads') {
            $query = DB::table('analytics_logs as al')
                ->leftJoin('users as u', 'al.user_id', '=', 'u.id')
                ->leftJoin('notifications as n', 'al.ref_id', '=', 'n.id')
                ->where('al.screen_type', 'ad')
                ->where('n.vendor_id', $vendorId)
                ->select('al.*', 'u.f_name', 'u.l_name', 'u.phone as user_phone', 'n.title as notif_title');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('n.title', 'like', "%{$search}%")
                      ->orWhere('u.f_name', 'like', "%{$search}%")
                      ->orWhere('u.l_name', 'like', "%{$search}%")
                      ->orWhere('u.phone', 'like', "%{$search}%");
                });
            }
            if ($dateFrom) {
                $query->whereDate('al.created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('al.created_at', '<=', $dateTo);
            }

            $data['items'] = $query->orderByDesc('al.created_at')->paginate(20)->appends($request->query());

        } elseif ($tab == 'location_views') {
            $query = DB::table('analytics_logs as al')
                ->leftJoin('users as u', 'al.user_id', '=', 'u.id')
                ->where('al.screen_type', 'location')
                ->where('al.ref_id', $storeId)
                ->select('al.*', 'u.f_name', 'u.l_name', 'u.phone as user_phone');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('u.f_name', 'like', "%{$search}%")
                      ->orWhere('u.l_name', 'like', "%{$search}%")
                      ->orWhere('u.phone', 'like', "%{$search}%");
                });
            }
            if ($dateFrom) {
                $query->whereDate('al.created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('al.created_at', '<=', $dateTo);
            }

            $data['items'] = $query->orderByDesc('al.created_at')->paginate(20)->appends($request->query());

        } elseif ($tab == 'phone_calls') {
            $query = DB::table('analytics_logs as al')
                ->leftJoin('users as u', 'al.user_id', '=', 'u.id')
                ->where('al.screen_type', 'call')
                ->where('al.ref_id', $storeId)
                ->select('al.*', 'u.f_name', 'u.l_name', 'u.phone as user_phone');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('u.f_name', 'like', "%{$search}%")
                      ->orWhere('u.l_name', 'like', "%{$search}%")
                      ->orWhere('u.phone', 'like', "%{$search}%");
                });
            }
            if ($dateFrom) {
                $query->whereDate('al.created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('al.created_at', '<=', $dateTo);
            }

            $data['items'] = $query->orderByDesc('al.created_at')->paginate(20)->appends($request->query());
        }

        // Total counts for summary cards
        $counts = [
            'store_visits' => DB::table('analytics_logs')->where('screen_type', 'store')->where('ref_id', $storeId)->whereDate('created_at', '>=', $dateFrom)->whereDate('created_at', '<=', $dateTo)->count(),
            'banner_clicks' => DB::table('analytics_logs as al')->join('banners as b', 'al.ref_id', '=', 'b.id')->where('al.screen_type', 'banner')->where('b.type', 'store_wise')->where('b.data', $storeId)->whereDate('al.created_at', '>=', $dateFrom)->whereDate('al.created_at', '<=', $dateTo)->count(),
            'ad_clicks' => DB::table('analytics_logs as al')->join('notifications as n', 'al.ref_id', '=', 'n.id')->where('al.screen_type', 'ad')->where('n.vendor_id', $vendorId)->whereDate('al.created_at', '>=', $dateFrom)->whereDate('al.created_at', '<=', $dateTo)->count(),
            'location_views' => DB::table('analytics_logs')->where('screen_type', 'location')->where('ref_id', $storeId)->whereDate('created_at', '>=', $dateFrom)->whereDate('created_at', '<=', $dateTo)->count(),
            'phone_calls' => DB::table('analytics_logs')->where('screen_type', 'call')->where('ref_id', $storeId)->whereDate('created_at', '>=', $dateFrom)->whereDate('created_at', '<=', $dateTo)->count(),
        ];

        return view('vendor-views.analytics.index', compact('tab', 'data', 'search', 'dateFrom', 'dateTo', 'preset', 'counts'));
    } 

    public function chartData(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $days = (int) $request->get('days', 30);
        $startDate = now()->subDays($days - 1)->startOfDay();

        $labels = [];
        $storeVisits = array_fill(0, $days, 0);
        $bannerClicks = array_fill(0, $days, 0);
        $adClicks = array_fill(0, $days, 0);
        $locationViews = array_fill(0, $days, 0);
        $phoneCalls = array_fill(0, $days, 0);

        for ($i = 0; $i < $days; $i++) {
            $labels[] = $startDate->copy()->addDays($i)->format('d M');
        }

        // Store visits
        $counts = DB::table('analytics_logs')
            ->where('screen_type', 'store')
            ->where('ref_id', $storeId)
            ->whereDate('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get();
        foreach ($counts as $row) {
            $dayIndex = $startDate->diffInDays($row->date);
            if (isset($storeVisits[$dayIndex])) $storeVisits[$dayIndex] = $row->count;
        }

        // Banner clicks (banners belonging to this store)
        $bannerIds = DB::table('banners')->where('type', 'store_wise')->where('data', $storeId)->pluck('id');
        if ($bannerIds->count() > 0) {
            $counts = DB::table('analytics_logs')
                ->where('screen_type', 'banner')
                ->whereIn('ref_id', $bannerIds)
                ->whereDate('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->get();
            foreach ($counts as $row) {
                $dayIndex = $startDate->diffInDays($row->date);
                if (isset($bannerClicks[$dayIndex])) $bannerClicks[$dayIndex] = $row->count;
            }
        }

        // Ad clicks (notifications belonging to this store)
        $notifIds = DB::table('notifications')->where('vendor_id', $storeId)->pluck('id');
        if ($notifIds->count() > 0) {
            $counts = DB::table('analytics_logs')
                ->where('screen_type', 'ad')
                ->whereIn('ref_id', $notifIds)
                ->whereDate('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->get();
            foreach ($counts as $row) {
                $dayIndex = $startDate->diffInDays($row->date);
                if (isset($adClicks[$dayIndex])) $adClicks[$dayIndex] = $row->count;
            }
        }

        // Location views
        $counts = DB::table('analytics_logs')
            ->where('screen_type', 'location')
            ->where('ref_id', $storeId)
            ->whereDate('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get();
        foreach ($counts as $row) {
            $dayIndex = $startDate->diffInDays($row->date);
            if (isset($locationViews[$dayIndex])) $locationViews[$dayIndex] = $row->count;
        }

        // Phone calls
        $counts = DB::table('analytics_logs')
            ->where('screen_type', 'call')
            ->where('ref_id', $storeId)
            ->whereDate('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get();
        foreach ($counts as $row) {
            $dayIndex = $startDate->diffInDays($row->date);
            if (isset($phoneCalls[$dayIndex])) $phoneCalls[$dayIndex] = $row->count;
        }

        return response()->json([
            'labels' => $labels,
            'store_visits' => $storeVisits,
            'banner_clicks' => $bannerClicks,
            'ad_clicks' => $adClicks,
            'location_views' => $locationViews,
            'phone_calls' => $phoneCalls,
        ]);
    }
}
