<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Real Search Console performance data (clicks/impressions/CTR/position) — reads only from the
 * tables SyncSearchConsoleStats fills; never calls the Google API live on page load.
 */
class SearchConsoleController extends Controller
{
    public function index(Request $request)
    {
        $hasData = Schema::hasTable('search_console_daily_totals');

        $daily = $hasData
            ? DB::table('search_console_daily_totals')->orderBy('stat_date')->get()
            : collect();

        $totals = [
            'clicks'      => (int) $daily->sum('clicks'),
            'impressions' => (int) $daily->sum('impressions'),
            'avg_ctr'     => $daily->count() ? round((float) $daily->avg('ctr') * 100, 2) : 0,
            'avg_position' => $daily->where('impressions', '>', 0)->count()
                ? round((float) $daily->where('impressions', '>', 0)->avg('position'), 1)
                : 0,
        ];

        $topPages = $hasData
            ? DB::table('search_console_top_pages')->orderByDesc('clicks')->limit(50)->get()
            : collect();

        $topQueries = $hasData
            ? DB::table('search_console_top_queries')->orderByDesc('clicks')->limit(50)->get()
            : collect();

        $lastSynced = $topPages->max('synced_at');

        return view('admin-views.search-console.index', compact('daily', 'totals', 'topPages', 'topQueries', 'lastSynced', 'hasData'));
    }

    /** Manual "Sync now" — runs the same command the daily scheduler calls, inline (admin-triggered, so a wait is fine). */
    public function syncNow()
    {
        try {
            $exitCode = \Illuminate\Support\Facades\Artisan::call('search-console:sync');
            $exitCode === 0
                ? Toastr::success('Search Console data synced.')
                : Toastr::error('Sync finished with errors — check the logs.');
        } catch (\Throwable $e) {
            Toastr::error('Sync failed: ' . $e->getMessage());
        }

        return back();
    }
}
