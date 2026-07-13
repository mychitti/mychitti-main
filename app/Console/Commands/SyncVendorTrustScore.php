<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MC Trust Layer — computes a 0–100 vendor_trust_score per store from real signals and stores it
 * on stores.vendor_trust_score. The score feeds hybrid search ranking (doc: 25% review/trust
 * weight) and the derived badges shown on listing pages. There are no explicit "verified" flags
 * in the schema, so trust is derived from proxies: GST present, address present, phone present,
 * completed orders, and review score/volume.
 */
class SyncVendorTrustScore extends Command
{
    protected $signature = 'vendor:sync-trust-score';
    protected $description = 'Ensure stores.vendor_trust_score and recompute the MC Trust Layer score (0-100) for every store';

    public function handle(): int
    {
        $this->ensureSchema();

        $updated = 0;
        DB::table('stores')
            ->select('id', 'gst', 'address', 'phone', 'total_order', 'average_rating', 'rating_count')
            ->orderBy('id')
            ->chunkById(500, function ($stores) use (&$updated) {
                foreach ($stores as $s) {
                    DB::table('stores')->where('id', $s->id)
                        ->update(['vendor_trust_score' => $this->score($s)]);
                    $updated++;
                }
            });

        $this->info("Recomputed vendor_trust_score for {$updated} store(s).");
        return self::SUCCESS;
    }

    private function ensureSchema(): void
    {
        if (!Schema::hasColumn('stores', 'vendor_trust_score')) {
            DB::statement("ALTER TABLE stores ADD COLUMN vendor_trust_score TINYINT UNSIGNED NOT NULL DEFAULT 0");
            $exists = DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', 'stores')
                ->where('index_name', 'idx_vendor_trust_score')
                ->exists();
            if (!$exists) {
                DB::statement("ALTER TABLE stores ADD KEY idx_vendor_trust_score (vendor_trust_score)");
            }
            $this->info('Added stores.vendor_trust_score column.');
        }
    }

    /**
     * Weighted 0–100 trust score. Max = 20+10+5 + 25 + (25+15) = 100.
     */
    private function score(object $s): int
    {
        $score = 0;

        // Business legitimacy signals
        if (!empty($s->gst))     $score += 20; // GST Verified
        if (!empty($s->address)) $score += 10; // Address on file
        if (!empty($s->phone))   $score += 5;  // Contactable

        // Booking Verified — completed order volume
        $orders = (int) ($s->total_order ?? 0);
        $score += $orders >= 20 ? 25 : ($orders >= 5 ? 15 : ($orders > 0 ? 5 : 0));

        // Review score + volume
        $count = (int) ($s->rating_count ?? 0);
        if ($count > 0) {
            $rating = min(max((float) ($s->average_rating ?? 0), 0), 5);
            $score += (int) round($rating / 5 * 25);                 // up to 25 for rating value
            $score += $count >= 20 ? 15 : ($count >= 5 ? 10 : 5);     // up to 15 for volume
        }

        return min($score, 100);
    }
}
