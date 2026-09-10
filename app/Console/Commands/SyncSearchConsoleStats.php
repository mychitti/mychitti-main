<?php

namespace App\Console\Commands;

use App\Services\SearchConsoleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pulls real Search Console performance data (clicks/impressions/CTR/position) into three
 * tables an admin screen reads from — never queried live on page load, since Search Console
 * itself is rate-limited and this data only changes once a day anyway (with a ~2-3 day lag).
 */
class SyncSearchConsoleStats extends Command
{
    protected $signature = 'search-console:sync {--days=30 : How many days back to pull}';
    protected $description = 'Sync Search Console performance data (daily totals, top pages, top queries)';

    public function handle(SearchConsoleService $service): int
    {
        $this->ensureSchema();

        // Search Console data typically finalizes 2-3 days after the fact — pulling anything
        // more recent than that returns incomplete rows that would look like a traffic drop.
        $end = now()->subDays(3)->toDateString();
        $start = now()->subDays((int) $this->option('days'))->toDateString();

        $this->syncDailyTotals($service, $start, $end);
        $this->syncTopPages($service, $start, $end);
        $this->syncTopQueries($service, $start, $end);

        $this->info("Search Console stats synced for {$start} to {$end}.");
        return self::SUCCESS;
    }

    private function ensureSchema(): void
    {
        if (!Schema::hasTable('search_console_daily_totals')) {
            DB::statement("
                CREATE TABLE search_console_daily_totals (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    stat_date DATE NOT NULL,
                    clicks INT UNSIGNED NOT NULL DEFAULT 0,
                    impressions INT UNSIGNED NOT NULL DEFAULT 0,
                    ctr DECIMAL(7,4) NOT NULL DEFAULT 0,
                    position DECIMAL(6,2) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    UNIQUE KEY uq_date (stat_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->info('Created search_console_daily_totals.');
        }

        if (!Schema::hasTable('search_console_top_pages')) {
            DB::statement("
                CREATE TABLE search_console_top_pages (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    page VARCHAR(500) NOT NULL,
                    clicks INT UNSIGNED NOT NULL DEFAULT 0,
                    impressions INT UNSIGNED NOT NULL DEFAULT 0,
                    ctr DECIMAL(7,4) NOT NULL DEFAULT 0,
                    position DECIMAL(6,2) NOT NULL DEFAULT 0,
                    window_start DATE NOT NULL,
                    window_end DATE NOT NULL,
                    synced_at TIMESTAMP NULL,
                    KEY idx_clicks (clicks)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->info('Created search_console_top_pages.');
        }

        if (!Schema::hasTable('search_console_top_queries')) {
            DB::statement("
                CREATE TABLE search_console_top_queries (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    search_query VARCHAR(500) NOT NULL,
                    clicks INT UNSIGNED NOT NULL DEFAULT 0,
                    impressions INT UNSIGNED NOT NULL DEFAULT 0,
                    ctr DECIMAL(7,4) NOT NULL DEFAULT 0,
                    position DECIMAL(6,2) NOT NULL DEFAULT 0,
                    window_start DATE NOT NULL,
                    window_end DATE NOT NULL,
                    synced_at TIMESTAMP NULL,
                    KEY idx_clicks (clicks)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->info('Created search_console_top_queries.');
        }
    }

    /** One row per day, site-wide — the trend chart. Upserted so history accumulates run over run. */
    private function syncDailyTotals(SearchConsoleService $service, string $start, string $end): void
    {
        $rows = $service->query($start, $end, ['date'], 1000);

        foreach ($rows as $row) {
            DB::table('search_console_daily_totals')->updateOrInsert(
                ['stat_date' => $row['keys'][0]],
                [
                    'clicks'      => $row['clicks'] ?? 0,
                    'impressions' => $row['impressions'] ?? 0,
                    'ctr'         => $row['ctr'] ?? 0,
                    'position'    => $row['position'] ?? 0,
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ]
            );
        }

        $this->info('Daily totals: ' . count($rows) . ' day(s).');
    }

    /** Top pages by clicks over the whole window — replaced wholesale each run, not historical. */
    private function syncTopPages(SearchConsoleService $service, string $start, string $end): void
    {
        $rows = $service->query($start, $end, ['page'], 250);

        DB::transaction(function () use ($rows, $start, $end) {
            // delete(), not truncate() — TRUNCATE is DDL and causes an implicit commit in MySQL,
            // which ends this transaction early and makes the wrapper's own commit() below throw
            // "There is no active transaction".
            DB::table('search_console_top_pages')->delete();

            foreach ($rows as $row) {
                DB::table('search_console_top_pages')->insert([
                    'page'         => mb_substr($row['keys'][0], 0, 500),
                    'clicks'       => $row['clicks'] ?? 0,
                    'impressions'  => $row['impressions'] ?? 0,
                    'ctr'          => $row['ctr'] ?? 0,
                    'position'     => $row['position'] ?? 0,
                    'window_start' => $start,
                    'window_end'   => $end,
                    'synced_at'    => now(),
                ]);
            }
        });

        $this->info('Top pages: ' . count($rows) . ' page(s).');
    }

    /** Top search queries by clicks over the whole window — same replace-wholesale approach. */
    private function syncTopQueries(SearchConsoleService $service, string $start, string $end): void
    {
        $rows = $service->query($start, $end, ['query'], 250);

        DB::transaction(function () use ($rows, $start, $end) {
            DB::table('search_console_top_queries')->delete();

            foreach ($rows as $row) {
                DB::table('search_console_top_queries')->insert([
                    'search_query' => mb_substr($row['keys'][0], 0, 500),
                    'clicks'       => $row['clicks'] ?? 0,
                    'impressions'  => $row['impressions'] ?? 0,
                    'ctr'          => $row['ctr'] ?? 0,
                    'position'     => $row['position'] ?? 0,
                    'window_start' => $start,
                    'window_end'   => $end,
                    'synced_at'    => now(),
                ]);
            }
        });

        $this->info('Top queries: ' . count($rows) . ' quer(y/ies).');
    }
}
