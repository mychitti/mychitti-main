<?php

namespace App\Jobs\Scheduled;

use App\Services\OpdDiscontinue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * The nightly pass that closes care the patient never came back for.
 *
 * Runs once a day rather than hourly: the thing being measured is a gap of weeks, so a sweep that
 * ran twelve times a day would find the same nothing eleven extra times. It writes only to
 * hospitals that set an interval — see OpdDiscontinue, which holds the rules.
 */
class DiscontinueStaleOpdCareJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 900;

    /** A single hospital, for a run started by hand. Null sweeps every hospital that asked. */
    public function __construct(public ?int $onlyStoreId = null, public bool $dryRun = false)
    {
    }

    public function handle(): void
    {
        $totals = $this->onlyStoreId
            ? OpdDiscontinue::sweepStore($this->onlyStoreId, null, $this->dryRun)
            : OpdDiscontinue::sweepAll($this->dryRun);

        // Logged only when it actually did something: a nightly line saying "closed nothing" on
        // every install that never switched this on is noise in a log people read for problems.
        if (($totals['patients'] ?? 0) > 0) {
            Log::info('OPD care discontinued' . ($this->dryRun ? ' (dry run)' : ''), $totals);
        }
    }
}
