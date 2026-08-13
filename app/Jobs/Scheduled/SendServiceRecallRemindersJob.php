<?php

namespace App\Jobs\Scheduled;

use App\Services\ServiceRecallReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Daily sweep inviting service customers back once their store's recall gap has passed.
 *
 * Runs after the retail and hospital sweeps, so a person who is due in more than one of them is
 * already on the shared fortnight cooldown by the time this looks at them.
 */
class SendServiceRecallRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // No auto-retry: a re-run would message the same customers a second time.
    public int $tries = 1;
    public int $timeout = 900;

    public function handle(): void
    {
        if (!Schema::hasTable('accepted_service_requests') || !Schema::hasTable('service_requests')) {
            return;
        }

        $stores = ServiceRecallReminder::configuredStoreIds();
        if (empty($stores)) {
            return;
        }

        $total = 0;
        foreach ($stores as $storeId) {
            try {
                $total += ServiceRecallReminder::runStore((int) $storeId);
            } catch (\Throwable $e) {
                // One store's bad data must not stop every other store's recalls.
                Log::error('Service recalls failed for store ' . $storeId . ': ' . $e->getMessage());
            }
        }

        if ($total) {
            Log::info('Sent service recalls to ' . $total . ' customer(s).');
        }
    }
}
