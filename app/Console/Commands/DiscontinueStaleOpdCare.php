<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Services\OpdDiscontinue;
use Illuminate\Console\Command;

/**
 * The discontinue sweep, run by hand.
 *
 * Exists mostly for --dry-run. This is a feature that closes clinical records unattended, and the
 * only fair way to switch one on is to be able to ask "what would you have closed last night?"
 * and read the answer before it happens.
 */
class DiscontinueStaleOpdCare extends Command
{
    protected $signature = 'hmis:discontinue-stale
                            {--store= : One store id. Omit to sweep every hospital that set an interval.}
                            {--days= : Override the hospital\'s own interval, for a what-if run.}
                            {--dry-run : Report what would be closed and change nothing.}';

    protected $description = 'Close planned treatments, lab work and unattended follow-ups for patients who stopped coming';

    public function handle(): int
    {
        $storeId = $this->option('store') ? (int) $this->option('store') : null;
        $days    = $this->option('days') ? (int) $this->option('days') : null;
        $dry     = (bool) $this->option('dry-run');

        if ($days && !$storeId) {
            $this->error('--days only makes sense with --store: it overrides that hospital\'s own interval.');
            return self::FAILURE;
        }

        if ($dry) {
            $this->warn('Dry run — nothing will be written.');
        }

        if (!$storeId) {
            $totals = OpdDiscontinue::sweepAll($dry);
            $this->info(sprintf(
                '%d hospital(s) swept: %d patient(s), %d visit(s), %d treatment(s), %d lab work job(s), %d follow-up(s).',
                $totals['stores'], $totals['patients'], $totals['visits'],
                $totals['treatments'], $totals['lab_works'], $totals['appointments']
            ));

            return self::SUCCESS;
        }

        $days = $days ?: hmis_discontinue_days($storeId);
        if (!$days) {
            $this->error('Store ' . $storeId . ' has the sweep switched off (Hospital Settings → Discontinue Abandoned Care). Pass --days to run it anyway.');
            return self::FAILURE;
        }

        $result = OpdDiscontinue::sweepStore($storeId, $days, $dry);

        if (!$result['patients']) {
            $this->info('Nothing to close for store ' . $storeId . ' at ' . $days . ' days.');
            return self::SUCCESS;
        }

        $names = Patient::whereIn('id', collect($result['detail'])->pluck('patient_id'))
            ->pluck('name', 'id');

        $this->table(
            ['Patient', 'Treatments', 'Lab work', 'Follow-ups', 'Why'],
            collect($result['detail'])->map(fn($row) => [
                ($names[$row['patient_id']] ?? '#' . $row['patient_id']),
                $row['treatments'],
                $row['lab_works'],
                $row['appointments'],
                $row['reason'],
            ])->all()
        );

        $this->info(sprintf(
            '%s %d patient(s): %d treatment(s), %d lab work job(s), %d follow-up(s) across %d visit(s).',
            $dry ? 'Would close for' : 'Closed for',
            $result['patients'], $result['treatments'], $result['lab_works'],
            $result['appointments'], $result['visits']
        ));

        return self::SUCCESS;
    }
}
