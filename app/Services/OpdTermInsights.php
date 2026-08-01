<?php

namespace App\Services;

use App\Models\OpdVisit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * What this hospital actually diagnoses, and what it actually does about it.
 *
 * The term lists on their own are a flat alphabetical dropdown of ~60 entries, which means a
 * doctor seeing their tenth viral fever of the morning scrolls past Anaemia and Arthritis to get
 * there. Everything here is derived from the store's own completed visits, so the screen reflects
 * that hospital's real casemix rather than a generic list:
 *
 *   - how often each term has been used, so the common ones sort to the top
 *   - which treatments actually accompany a given diagnosis here, so picking "Viral Fever" can
 *     offer the antipyretic and the ORS this hospital in fact gives for it
 *
 * These are suggestions and nothing more — every one still has to be chosen by the doctor, and
 * nothing is ever filled in on their behalf. A frequency count is not a clinical recommendation,
 * and the moment it starts behaving like one it stops being safe.
 */
class OpdTermInsights
{
    /** How far back the casemix is read. Two years covers seasonal illness twice over. */
    const LOOKBACK_DAYS = 730;

    /** Ceiling on rows scanned, so a very large hospital cannot turn this into a slow query. */
    const MAX_VISITS = 20000;

    /** Recomputed at most this often; cleared immediately whenever a visit's terms are saved. */
    const CACHE_SECONDS = 3600;

    /** Terms offered as one-tap chips above the picker. */
    const TOP_QUICK = 8;

    /** Treatments suggested per diagnosis. */
    const TOP_SUGGESTIONS = 6;

    public static function for(int $storeId): array
    {
        return Cache::remember(self::cacheKey($storeId), self::CACHE_SECONDS, fn() => self::build($storeId));
    }

    /** Called when a visit's terms change, so the doctor sees their own edit reflected at once. */
    public static function forget(int $storeId): void
    {
        Cache::forget(self::cacheKey($storeId));
    }

    protected static function cacheKey(int $storeId): string
    {
        return 'opd_term_insights_' . $storeId;
    }

    /** Same normalisation the repeat rules use, so "AC Servicing" and "ac  servicing" are one key. */
    public static function key(?string $term): string
    {
        $key = mb_strtolower(trim((string) $term));
        $key = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $key) ?? $key;

        return trim(preg_replace('/\s+/', ' ', $key) ?? $key);
    }

    /**
     * @return array{
     *     diagnosis: array<string,int>, treatment: array<string,int>,
     *     diagnosisByKey: array<string,int>, treatmentByKey: array<string,int>,
     *     pairs: array<string,array<string,int>>
     * }
     *
     * Counts are totalled per normalised term, not per raw string. "Viral Fever" typed once with a
     * double space is the same condition, and counting the two apart both splits the ranking and
     * offers the doctor the same chip twice. Each total is then labelled with the spelling that
     * hospital uses most, so the chip reads the way their records read.
     */
    protected static function build(int $storeId): array
    {
        $dxVariants = [];   // key => [raw spelling => times used]
        $txVariants = [];
        $pairs      = [];

        $rows = DB::table('opd_visits')
            ->where('store_id', $storeId)
            ->where('visit_date', '>=', now()->subDays(self::LOOKBACK_DAYS)->toDateString())
            ->where(function ($q) {
                $q->whereNotNull('diagnosis')->orWhereNotNull('treatment');
            })
            ->orderByDesc('id')
            ->limit(self::MAX_VISITS)
            ->get(['diagnosis', 'treatment']);

        foreach ($rows as $row) {
            $dx = OpdVisit::splitTerms($row->diagnosis);
            $tx = OpdVisit::splitTerms($row->treatment);

            foreach ($dx as $term) {
                if (($key = self::key($term)) !== '') {
                    $dxVariants[$key][$term] = ($dxVariants[$key][$term] ?? 0) + 1;
                }
            }
            foreach ($tx as $term) {
                if (($key = self::key($term)) !== '') {
                    $txVariants[$key][$term] = ($txVariants[$key][$term] ?? 0) + 1;
                }
            }

            // Co-occurrence within one visit. Keyed on the normalised diagnosis so a term typed
            // with different spacing or capitalisation still lands on the same suggestions.
            foreach ($dx as $term) {
                $key = self::key($term);
                if ($key === '') {
                    continue;
                }
                foreach ($tx as $t) {
                    $pairs[$key][$t] = ($pairs[$key][$t] ?? 0) + 1;
                }
            }
        }

        foreach ($pairs as $key => $list) {
            arsort($list);
            $pairs[$key] = array_slice($list, 0, self::TOP_SUGGESTIONS, true);
        }

        [$diagnosis, $diagnosisByKey] = self::collapse($dxVariants);
        [$treatment, $treatmentByKey] = self::collapse($txVariants);

        return [
            'diagnosis'      => $diagnosis,       // canonical label => total, for the chips
            'treatment'      => $treatment,
            'diagnosisByKey' => $diagnosisByKey,  // normalised key => total, for ranking any spelling
            'treatmentByKey' => $treatmentByKey,
            'pairs'          => $pairs,
        ];
    }

    /**
     * Fold spelling variants into one total, labelled with the spelling used most.
     *
     * @param  array<string,array<string,int>>  $variants  key => [raw => count]
     * @return array{0: array<string,int>, 1: array<string,int>}
     */
    protected static function collapse(array $variants): array
    {
        $byLabel = [];
        $byKey   = [];

        foreach ($variants as $key => $spellings) {
            arsort($spellings);
            $total = array_sum($spellings);

            $byKey[$key] = $total;
            $byLabel[(string) array_key_first($spellings)] = $total;
        }

        arsort($byLabel);
        arsort($byKey);

        return [$byLabel, $byKey];
    }
}
