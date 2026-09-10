<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Real Search Console performance data (clicks/impressions/CTR/position) — reads from the
 * tables SyncSearchConsoleStats fills. The Google API itself is only ever called from that
 * command, either by the daily 06:00 schedule, the "Sync now" button, or the stale-data check
 * below — never on every page load, since Search Console data lags 2-3 days and syncing more
 * often than daily gets nothing new for the extra latency and API calls.
 */
class SearchConsoleController extends Controller
{
    // Just under the daily scheduled sync's 24h cycle, so a visit before tonight's run
    // self-heals instead of showing yesterday's numbers all day.
    private const STALE_AFTER_HOURS = 20;

    public function index(Request $request)
    {
        $this->autoSyncIfStale();

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

    /** Manual "Sync now" — loud about the result, since an admin explicitly asked for it. */
    public function syncNow()
    {
        $this->runSync()
            ? Toastr::success('Search Console data synced.')
            : Toastr::error('Sync failed — check the logs.');

        return back();
    }

    /**
     * Silent, best-effort sync when the data is missing or stale. Never lets a Google API
     * hiccup break the page — on failure it just falls back to whatever is already in the
     * tables (or the empty state, on a brand new install).
     */
    private function autoSyncIfStale(): void
    {
        if (!Schema::hasTable('search_console_daily_totals')) {
            $this->runSync();
            return;
        }

        $lastSynced = DB::table('search_console_top_pages')->max('synced_at');
        $isStale = !$lastSynced || Carbon::parse($lastSynced)->lt(now()->subHours(self::STALE_AFTER_HOURS));

        if ($isStale) {
            $this->runSync();
        }
    }

    private function runSync(): bool
    {
        try {
            Artisan::call('search-console:sync');
            return true;
        } catch (\Throwable $e) {
            Log::warning('SearchConsoleController: sync failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
