<?php

namespace App\Console\Commands;

use App\Models\OpdClinicalTerm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clears the general-medicine list that the old copy-on-first-use design wrote into every store.
 *
 * Those rows are why a dental clinic is offered Typhoid and Malaria. They are only removed where
 * the store has demonstrably never used them: a term appearing in any of that store's opd_visits
 * is a real clinical choice and stays, whatever list it originally came from.
 *
 * DRY RUN BY DEFAULT. Pass --apply to delete.
 */
class PruneCopiedOpdTerms extends Command
{
    protected $signature = 'hmis:prune-copied-terms
                            {--store= : Limit to one store id}
                            {--apply : Actually delete, instead of only reporting}';

    protected $description = 'Remove unused copies of the old default OPD term list from each store';

    public function handle(): int
    {
        if (!Schema::hasTable('opd_clinical_terms')) {
            $this->error('opd_clinical_terms does not exist — nothing to prune.');
            return self::FAILURE;
        }

        OpdClinicalTerm::ensureSchema();

        $apply = (bool) $this->option('apply');
        if (!$apply) {
            $this->warn('Dry run — nothing will be deleted. Re-run with --apply to remove.');
        }

        $storeIds = OpdClinicalTerm::query()
            ->when($this->option('store'), fn($q) => $q->where('store_id', (int) $this->option('store')))
            ->distinct()->pluck('store_id')->filter();

        $totalRemoved = 0;
        $totalKept    = 0;

        foreach ($storeIds as $storeId) {
            [$removed, $kept] = $this->pruneStore((int) $storeId, $apply);
            $totalRemoved += $removed;
            $totalKept    += $kept;

            if ($removed || $kept) {
                $this->line(sprintf(
                    'store %-6d  %s %d  kept-in-use %d',
                    $storeId,
                    $apply ? 'removed' : 'would remove',
                    $removed,
                    $kept
                ));
            }
        }

        $this->info(sprintf(
            '%s %d row(s) across %d store(s); %d kept because they are in use.',
            $apply ? 'Removed' : 'Would remove',
            $totalRemoved,
            $storeIds->count(),
            $totalKept
        ));

        return self::SUCCESS;
    }

    /** @return array{0:int,1:int} [removed, kept] */
    private function pruneStore(int $storeId, bool $apply): array
    {
        // Every term this store has ever actually recorded, from both columns, lowercased.
        $used = [];
        foreach (['diagnosis', 'treatment'] as $column) {
            if (!Schema::hasColumn('opd_visits', $column)) {
                continue;
            }
            DB::table('opd_visits')->where('store_id', $storeId)
                ->whereNotNull($column)->where($column, '!=', '')
                ->orderBy('id')
                ->select($column)
                ->chunk(500, function ($rows) use (&$used, $column) {
                    foreach ($rows as $row) {
                        foreach (\App\Models\OpdVisit::splitTerms($row->$column) as $term) {
                            $used[mb_strtolower(trim($term))] = true;
                        }
                    }
                });
        }

        $removed = 0;
        $kept    = 0;

        foreach (OpdClinicalTerm::DEFAULTS as $type => $names) {
            $seeded = collect($names)->mapWithKeys(fn($n) => [mb_strtolower($n) => true])->all();

            $rows = OpdClinicalTerm::where('store_id', $storeId)->where('type', $type)
                // A hidden row is this hospital's own decision, never a leftover copy.
                ->where('hidden', false)
                ->get(['id', 'name']);

            foreach ($rows as $row) {
                $key = mb_strtolower(trim($row->name));

                if (!isset($seeded[$key])) {
                    continue;          // typed by a doctor, not part of the old default list
                }
                if (isset($used[$key])) {
                    $kept++;           // seeded, but this hospital actually uses it
                    continue;
                }

                $removed++;
                if ($apply) {
                    $row->delete();
                }
            }
        }

        return [$removed, $kept];
    }
}
