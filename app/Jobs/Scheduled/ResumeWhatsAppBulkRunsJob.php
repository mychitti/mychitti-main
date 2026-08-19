<?php

namespace App\Jobs\Scheduled;

use App\Jobs\SendPlatformBulkWhatsAppJob;
use App\Jobs\SendVendorBulkWhatsAppJob;
use App\Services\WhatsAppBulkRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Picks bulk WhatsApp runs back up when the worker carrying one goes away.
 *
 * A bulk send is a chain of short jobs: each pass sends a few hundred people and queues the next.
 * A worker restarted mid-pass (a deploy, an OOM, a supervisor bounce) breaks that chain — the
 * remaining recipients are still sitting in wa_bulk_sends as claimed-but-unsent rows with nothing
 * scheduled to come for them. Once the send no longer lives in the browser there is no tab to
 * notice, so this does: any run whose lock has expired without finishing is handed to a fresh job.
 *
 * Safe to run against a live run — WhatsAppBulkRun::acquire() is an atomic claim, so a pass that
 * is genuinely still working simply keeps the run and this pass finds nothing to do.
 */
class ResumeWhatsAppBulkRunsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function handle(): void
    {
        if (!Schema::hasTable('wa_bulk_runs')) {
            return;
        }

        $stalled = DB::table('wa_bulk_runs')
            ->whereNotIn('status', WhatsAppBulkRun::CLOSED)
            ->where(fn($q) => $q->whereNull('locked_until')->orWhere('locked_until', '<', now()))
            // A run only moments old is one whose first job is still waiting its turn on the
            // queue, not a stalled one. updated_at is touched after every chunk, so anything
            // genuinely moving stays out of this window too.
            ->where('updated_at', '<', now()->subMinutes(5))
            ->orderBy('id')
            ->limit(20)
            ->get();

        foreach ($stalled as $run) {
            // Past the attempt ceiling this is a run that breaks every time it is touched, not one
            // that was merely interrupted. Whoever started it can send again — everyone already
            // messaged is claimed, so a fresh run skips them rather than repeating them.
            if ((int) $run->attempts >= WhatsAppBulkRun::MAX_ATTEMPTS) {
                WhatsAppBulkRun::close(
                    $run->run_id,
                    WhatsAppBulkRun::STATUS_FAILED,
                    'Stopped after ' . WhatsAppBulkRun::MAX_ATTEMPTS . ' attempts. Anyone already messaged was not messaged twice.'
                );
                continue;
            }

            // A stop asked for while nothing held the run: there is no pass left to tell.
            if ($run->status === WhatsAppBulkRun::STATUS_CANCELLING) {
                WhatsAppBulkRun::close($run->run_id, WhatsAppBulkRun::STATUS_STOPPED,
                    $run->message ?: 'Stopped. Nobody past this point was messaged.');
                continue;
            }

            Log::info('Resuming WhatsApp bulk run', [
                'run'      => $run->run_id,
                'store'    => $run->store_id,
                'attempts' => $run->attempts,
            ]);

            // Counted here rather than per pass: a run that is simply long takes dozens of passes,
            // and each one that finishes a chunk clears this again (see WhatsAppBulkRun::renew).
            // What climbs is a run being rescued over and over without ever moving.
            DB::table('wa_bulk_runs')->where('run_id', $run->run_id)->increment('attempts');

            if ((int) $run->store_id === WhatsAppBulkRun::PLATFORM_SCOPE) {
                SendPlatformBulkWhatsAppJob::dispatch($run->run_id);
            } else {
                SendVendorBulkWhatsAppJob::dispatch((int) $run->store_id, $run->run_id);
            }
        }
    }
}
