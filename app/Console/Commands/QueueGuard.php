<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Watches the queue for the failure supervisor cannot see: a worker that is alive but hung.
 *
 * On 9 July 2026 the only worker on the `default` queue blocked on something that never timed
 * out. It stayed up for 42 days on one second of CPU. Supervisor reported it RUNNING the whole
 * time and never restarted it, because autorestart only fires when a process EXITS — and
 * `--max-time` could not save it either, since the worker never reached the check. 156,000 jobs
 * piled up behind it, and nothing told anyone for six weeks.
 *
 * This closes both gaps: it notices a queue that has stopped draining, restarts the worker, and
 * raises an alert if that does not clear it.
 *
 * Deliberately a COMMAND, not a queued job. Scheduling it with $schedule->job() would put the
 * watchdog into the very queue it is meant to watch, where it would sit unprocessed alongside
 * everything else — which is exactly what happened to ResumeWhatsAppBulkRunsJob (335 copies
 * queued, none ever run). $schedule->command() executes inside the scheduler process, so it
 * still runs when the queue is dead.
 */
class QueueGuard extends Command
{
    protected $signature = 'queue:guard
        {--stale-minutes=15 : Age of the oldest waiting job before the queue counts as stuck}
        {--depth=2000       : Backlog size that raises an alert on its own}
        {--restart          : Restart the worker when the queue looks stuck}
        {--program=laravel-worker : supervisor program to restart}';

    protected $description = 'Detect a stalled queue, restart the worker, and alert if it stays stuck';

    /** Never restart more than once per this many minutes, so a genuinely slow queue is not thrashed. */
    const RESTART_COOLDOWN_MINUTES = 20;

    /** One alert per this many minutes, so a stuck queue does not bury the notification list. */
    const ALERT_COOLDOWN_MINUTES = 60;

    public function handle(): int
    {
        if (!Schema::hasTable('jobs')) {
            $this->info('No jobs table — nothing to watch.');
            return self::SUCCESS;
        }

        $staleMinutes = (int) $this->option('stale-minutes');
        $depthLimit   = (int) $this->option('depth');

        $depth = DB::table('jobs')->count();

        // Oldest job that no worker has claimed. `available_at` rather than `created_at`, so a
        // deliberately delayed job is not mistaken for a backlog.
        $oldest = DB::table('jobs')
            ->whereNull('reserved_at')
            ->where('available_at', '<=', now()->getTimestamp())
            ->min('available_at');

        $waitingMinutes = $oldest ? (int) floor((now()->getTimestamp() - (int) $oldest) / 60) : 0;

        $this->line("depth={$depth} oldest_waiting={$waitingMinutes}m");

        $stuck = $waitingMinutes >= $staleMinutes;
        $deep  = $depth >= $depthLimit;

        if (!$stuck && !$deep) {
            return self::SUCCESS;
        }

        $reason = $stuck
            ? "Queue has not drained for {$waitingMinutes} minutes ({$depth} jobs waiting)."
            : "Queue backlog is {$depth} jobs.";

        Log::warning('QueueGuard: ' . $reason);
        $this->warn($reason);

        $restarted = false;
        if ($stuck && $this->option('restart')) {
            $restarted = $this->restartWorker();
        }

        // Alerting only after the restart attempt, so the message says whether it was handled.
        // A restart that fixes things still alerts once — a worker that hangs repeatedly is
        // worth knowing about even when the guard is coping.
        $this->alert_($reason, $restarted);

        return self::SUCCESS;
    }

    /**
     * Bounce the worker through supervisor.
     *
     * SIGTERM is not enough on its own: the hung worker in July ignored it and needed SIGKILL,
     * so `restart` is given time and the outcome is reported rather than assumed.
     */
    private function restartWorker(): bool
    {
        $key = 'queue_guard:restarted';
        if (!Cache::add($key, 1, self::RESTART_COOLDOWN_MINUTES * 60)) {
            $this->warn('Restart skipped — already bounced within the cooldown.');
            return false;
        }

        $program = preg_replace('/[^a-zA-Z0-9_\-:]/', '', (string) $this->option('program'));
        $cmd = "supervisorctl restart {$program}: 2>&1";

        $output = [];
        $code = 0;
        @exec($cmd, $output, $code);

        $result = trim(implode(' ', $output));
        Log::warning("QueueGuard: restart {$program} exit={$code} {$result}");
        $this->line("restart {$program}: exit={$code} {$result}");

        return $code === 0;
    }

    /** In-app alert to the admins, rate-limited so a long outage does not spam the bell. */
    private function alert_(string $reason, bool $restarted): void
    {
        if (!Cache::add('queue_guard:alerted', 1, self::ALERT_COOLDOWN_MINUTES * 60)) {
            return;
        }

        $body = $reason . "\n"
            . ($restarted
                ? 'The worker was restarted automatically — check that the backlog is now falling.'
                : 'The worker was NOT restarted. Check supervisorctl status on the queue host.')
            . "\nBackground jobs (reminders, campaigns, notifications) do not run while this lasts.";

        try {
            _inAppNotification('Queue is not draining', $body, null, null, null, 'admin');
        } catch (\Throwable $e) {
            Log::warning('QueueGuard: alert failed — ' . $e->getMessage());
        }
    }
}
