<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'stores');
        $search = $request->get('search');
        $refId = $request->get('ref_id');

        $preset = $request->get('date_range', 'last_30_days');
        $custom = $request->get('custom_date_range'); 
        $range = Helpers::calculatePresetDates($preset, $custom); 
        $dateFrom = $range['start']->format('Y-m-d');
        $dateTo = $range['end']->format('Y-m-d');
 
        // Dropdown options for entity filter
        $filterOptions = [];
        if ($tab == 'stores') {
            $filterOptions = DB::table('stores')->select('id', 'name')->where('total_visits', '>', 0)->orderBy('name')->get();
        } elseif ($tab == 'banners') {
            $filterOptions = DB::table('banners')->select('id', 'title as name')->where('total_clicks', '>', 0)->orderBy('title')->get();
        } elseif ($tab == 'notifications') {
            $filterOptions = DB::table('notifications')->select('id', 'title as name')->where('total_clicks', '>', 0)->orderBy('title')->get();
        } elseif ($tab == 'location_views') {
            $storeIds = DB::table('analytics_logs')->where('screen_type', 'location')->distinct()->pluck('ref_id');
            $filterOptions = DB::table('stores')->select('id', 'name')->whereIn('id', $storeIds)->orderBy('name')->get();
        } elseif ($tab == 'phone_unmasks') {
            $storeIds = DB::table('analytics_logs')->where('screen_type', 'call')->distinct()->pluck('ref_id');
            $filterOptions = DB::table('stores')->select('id', 'name')->whereIn('id', $storeIds)->orderBy('name')->get();
        }

        $data = [];

        if ($tab == 'stores') {
            $query = DB::table('analytics_logs as al')
                ->leftJoin('users as u', 'al.user_id', '=', 'u.id')
                ->leftJoin('stores as s', 'al.ref_id', '=', 's.id')
                ->where('al.screen_type', 'store')
                ->select('al.*', 'u.f_name', 'u.l_name', 'u.phone as user_phone', 's.name as store_name', 's.phone as store_phone');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('s.name', 'like', "%{$search}%")
                      ->orWhere('u.f_name', 'like', "%{$search}%")
                      ->orWhere('u.l_name', 'like', "%{$search}%")
                      ->orWhere('u.phone', 'like', "%{$search}%");
                });
            }

            if ($refId) {
                $query->where('al.ref_id', $refId);
            }
            if ($dateFrom) {
                $query->whereDate('al.created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('al.created_at', '<=', $dateTo);
            }

            $data['items'] = $query->orderByDesc('al.created_at')->paginate(20)->appends($request->query());

        } elseif ($tab == 'banners') {
            $query = DB::table('analytics_logs as al')
                ->leftJoin('users as u', 'al.user_id', '=', 'u.id') 
                ->leftJoin('banners as b', 'al.ref_id', '=', 'b.id') 
                ->where('al.screen_type', 'banner')
                ->select('al.*', 'u.f_name', 'u.l_name', 'u.phone as user_phone', 'b.title as banner_title', 'b.type as banner_type');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('b.title', 'like', "%{$search}%")
                      ->orWhere('u.f_name', 'like', "%{$search}%")
                      ->orWhere('u.l_name', 'like', "%{$search}%")
                      ->orWhere('u.phone', 'like', "%{$search}%");
                });
            }

            if ($refId) {
                $query->where('al.ref_id', $refId);
            }
            if ($dateFrom) {
                $query->whereDate('al.created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('al.created_at', '<=', $dateTo);
            }

            $data['items'] = $query->orderByDesc('al.created_at')->paginate(20)->appends($request->query());

        } elseif ($tab == 'notifications') {
            $query = DB::table('analytics_logs as al')
                ->leftJoin('users as u', 'al.user_id', '=', 'u.id')
                ->leftJoin('notifications as n', 'al.ref_id', '=', 'n.id')
                ->where('al.screen_type', 'ad')
                ->select('al.*', 'u.f_name', 'u.l_name', 'u.phone as user_phone', 'n.title as notif_title', 'n.description as notif_desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('n.title', 'like', "%{$search}%")
                      ->orWhere('u.f_name', 'like', "%{$search}%")
                      ->orWhere('u.l_name', 'like', "%{$search}%")
                      ->orWhere('u.phone', 'like', "%{$search}%");
                });
            }

            if ($refId) {
                $query->where('al.ref_id', $refId);
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
                ->leftJoin('stores as s', 'al.ref_id', '=', 's.id')
                ->where('al.screen_type', 'location')
                ->select('al.*', 'u.f_name', 'u.l_name', 'u.phone as user_phone', 's.name as store_name', 's.phone as store_phone');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('s.name', 'like', "%{$search}%")
                      ->orWhere('u.f_name', 'like', "%{$search}%")
                      ->orWhere('u.l_name', 'like', "%{$search}%")
                      ->orWhere('u.phone', 'like', "%{$search}%");
                });
            }

            if ($refId) {
                $query->where('al.ref_id', $refId);
            }
            if ($dateFrom) {
                $query->whereDate('al.created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('al.created_at', '<=', $dateTo);
            }

            $data['items'] = $query->orderByDesc('al.created_at')->paginate(20)->appends($request->query());

        } elseif ($tab == 'phone_unmasks') {
            $query = DB::table('analytics_logs as al')
                ->leftJoin('users as u', 'al.user_id', '=', 'u.id')
                ->leftJoin('stores as s', 'al.ref_id', '=', 's.id')
                ->where('al.screen_type', 'call')
                ->select('al.*', 'u.f_name', 'u.l_name', 'u.phone as user_phone', 's.name as store_name', 's.phone as store_phone');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('s.name', 'like', "%{$search}%")
                      ->orWhere('u.f_name', 'like', "%{$search}%")
                      ->orWhere('u.l_name', 'like', "%{$search}%")
                      ->orWhere('u.phone', 'like', "%{$search}%");
                });
            }

            if ($refId) {
                $query->where('al.ref_id', $refId);
            }
            if ($dateFrom) {
                $query->whereDate('al.created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('al.created_at', '<=', $dateTo);
            }

            $data['items'] = $query->orderByDesc('al.created_at')->paginate(20)->appends($request->query());
        }

        return view('admin-views.analytics.index', compact('tab', 'data', 'search', 'dateFrom', 'dateTo', 'filterOptions', 'refId', 'preset'));
    }

    public function detail(Request $request, $type, $id)
    {
        $search = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to'); 

        $query = DB::table('analytics_logs as al')
            ->leftJoin('users as u', 'al.user_id', '=', 'u.id')
            ->where('al.screen_type', $type)
            ->where('al.ref_id', $id)
            ->select('al.*', 'u.f_name', 'u.l_name', 'u.phone', 'u.email');

        if ($dateFrom) {
            $query->whereDate('al.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('al.created_at', '<=', $dateTo);
        }

        $logs = $query->orderByDesc('al.created_at')->paginate(30)->appends($request->query());

        // Get the entity name
        $entityName = '';
        if ($type == 'store') {
            $entityName = DB::table('stores')->where('id', $id)->value('name') ?? "Store #$id";
        } elseif ($type == 'banner') {
            $entityName = DB::table('banners')->where('id', $id)->value('title') ?? "Banner #$id";
        } elseif ($type == 'ad') {
            $entityName = DB::table('notifications')->where('id', $id)->value('title') ?? "Notification #$id";
        }

        return view('admin-views.analytics.detail', compact('logs', 'type', 'id', 'entityName', 'dateFrom', 'dateTo'));
    }

    public function chartData(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $startDate = now()->subDays($days - 1)->startOfDay();

        $types = ['store', 'banner', 'ad'];
        $labels = [];
        $datasets = [
            'store' => [],
            'banner' => [],
            'ad' => [],
        ];

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $labels[] = $date->format('d M');
            foreach ($types as $type) {
                $datasets[$type][] = 0;
            }
        }

        $counts = DB::table('analytics_logs')
            ->whereDate('created_at', '>=', $startDate)
            ->whereIn('screen_type', $types)
            ->selectRaw('screen_type, DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('screen_type', 'date')
            ->get();

        foreach ($counts as $row) {
            $dayIndex = $startDate->diffInDays($row->date);
            if (isset($datasets[$row->screen_type][$dayIndex])) {
                $datasets[$row->screen_type][$dayIndex] = $row->count;
            }
        }

        // Location views
        $locationData = array_fill(0, $days, 0);
        $locationCounts = DB::table('analytics_logs')
            ->where('screen_type', 'location')
            ->whereDate('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get();

        foreach ($locationCounts as $row) {
            $dayIndex = $startDate->diffInDays($row->date);
            if (isset($locationData[$dayIndex])) {
                $locationData[$dayIndex] = $row->count;
            }
        }

        // Phone calls (unmasks)
        $unmaskData = array_fill(0, $days, 0);
        $unmaskCounts = DB::table('analytics_logs')
            ->where('screen_type', 'call')
            ->whereDate('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get();

        foreach ($unmaskCounts as $row) {
            $dayIndex = $startDate->diffInDays($row->date);
            if (isset($unmaskData[$dayIndex])) {
                $unmaskData[$dayIndex] = $row->count;
            }
        }

        return response()->json([
            'labels' => $labels,
            'store_visits' => $datasets['store'],
            'banner_clicks' => $datasets['banner'],
            'ad_clicks' => $datasets['ad'],
            'location_views' => $locationData,
            'phone_unmasks' => $unmaskData,
        ]);
    }
}
