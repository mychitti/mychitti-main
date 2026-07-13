<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DPDP Act 2023 data-retention enforcement.
 *
 * Anonymizes personal identifiers on behavioral log rows once they pass the retention window
 * (12 months) — the row is KEPT so aggregate Admin Analytics still works, but user_id / ip are
 * scrubbed so no personal data is retained past the window. Idempotent: already-scrubbed rows
 * are skipped on subsequent runs.
 *
 * GPS/location note: there is no persistent GPS-log table — customer coordinates live only in the
 * session (ephemeral) — so the 90-day GPS rule has nothing to purge here. Add a target below if a
 * persistent location table is introduced.
 *
 * Run `php artisan data:purge-retention --dry-run` first to see the counts without changing data.
 */
class PurgeDataRetention extends Command
{
    protected $signature = 'data:purge-retention {--dry-run : Report what would be anonymized without changing anything}';
    protected $description = 'DPDP retention: anonymize PII on behavioral logs older than the retention window';

    private const BEHAVIORAL_MONTHS = 12;
    private const CHUNK = 2000;

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $cutoff = now()->subMonths(self::BEHAVIORAL_MONTHS);
        $this->info(($dry ? '[dry-run] ' : '') . 'Behavioral retention cutoff: ' . $cutoff->toDateString());

        $total = 0;
        // Store visits / contact clicks / screen views — keep screen_type, ref_id, created_at for
        // aggregate reporting; scrub the person link (user_id) and ip.
        $total += $this->anonymize('analytics_logs', $cutoff, ['user_id' => null, 'ip' => null],
            fn($q) => $q->where(fn($w) => $w->whereNotNull('user_id')->orWhereNotNull('ip')), $dry);

        // Per-user search history — keep text/zone for "popular searches" aggregates, unlink the user.
        $total += $this->anonymize('user_recent_searches', $cutoff, ['user_id' => null],
            fn($q) => $q->whereNotNull('user_id'), $dry);

        $this->info(($dry ? '[dry-run] ' : '') . "Done. {$total} row(s) " . ($dry ? 'would be' : '') . ' anonymized.');
        return self::SUCCESS;
    }

    private function anonymize(string $table, $cutoff, array $updates, \Closure $piiFilter, bool $dry): int
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'created_at')) {
            $this->warn("  {$table}: skipped (missing table or created_at column).");
            return 0;
        }

        // Only rows past the cutoff that still carry personal data.
        $base = fn() => $piiFilter(DB::table($table)->where('created_at', '<', $cutoff));

        $count = $base()->count();
        if ($dry) {
            $this->line("  {$table}: {$count} row(s) would be anonymized.");
            return $count;
        }
        if ($count === 0) {
            $this->line("  {$table}: nothing to anonymize.");
            return 0;
        }

        $affected = 0;
        do {
            $ids = $base()->limit(self::CHUNK)->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }
            $affected += DB::table($table)->whereIn('id', $ids)->update($updates);
        } while ($ids->count() === self::CHUNK);

        $this->line("  {$table}: anonymized {$affected} row(s).");
        return $affected;
    }
}
