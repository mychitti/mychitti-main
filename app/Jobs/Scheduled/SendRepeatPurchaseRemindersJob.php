<?php

namespace App\Jobs\Scheduled;

use App\Services\RepeatPurchaseReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Daily sweep for "time to rebuy" reminders.
 *
 * Once a day, mid-morning: a nudge about running low on rice is a daytime message, and running it
 * hourly would only spread the same customers across the day for no benefit. Only stores that have
 * actually configured a repeat cycle are looked at.
 */
class SendRepeatPurchaseRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // No auto-retry: a re-run would message the same customers a second time.
    public int $tries = 1;
    public int $timeout = 900;

    public function handle(): void
    {
        if (!Schema::hasTable('invoice_items') || !Schema::hasTable('manual_invoices')) {
            return;
        }

        $stores = RepeatPurchaseReminder::configuredStoreIds();
        if (empty($stores)) {
            return;
        }

        $total = 0;
        foreach ($stores as $storeId) {
            try {
                $total += RepeatPurchaseReminder::runStore((int) $storeId);
            } catch (\Throwable $e) {
                // One store's bad data must not stop every other store's reminders.
                Log::error('Repeat reminders failed for store ' . $storeId . ': ' . $e->getMessage());
            }
        }

        if ($total) {
            Log::info('Sent repeat purchase reminders to ' . $total . ' customer(s).');
        }
    }
}
