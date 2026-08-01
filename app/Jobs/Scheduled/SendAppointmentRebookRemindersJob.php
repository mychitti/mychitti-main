<?php

namespace App\Jobs\Scheduled;

use App\Services\AppointmentRebookReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Daily sweep inviting patients back who are past their doctor's recall interval.
 *
 * Late morning, once a day, and only for hospitals that set a recall on at least one doctor.
 */
class SendAppointmentRebookRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // No auto-retry: a re-run would message the same patients a second time.
    public int $tries = 1;
    public int $timeout = 900;

    public function handle(): void
    {
        if (!Schema::hasTable('appointments') || !Schema::hasTable('doctor_profiles')) {
            return;
        }

        $stores = AppointmentRebookReminder::configuredStoreIds();
        if (empty($stores)) {
            return;
        }

        $total = 0;
        foreach ($stores as $storeId) {
            try {
                $total += AppointmentRebookReminder::runStore((int) $storeId);
            } catch (\Throwable $e) {
                // One hospital's bad data must not stop every other hospital's recalls.
                Log::error('Rebook reminders failed for store ' . $storeId . ': ' . $e->getMessage());
            }
        }

        if ($total) {
            Log::info('Sent rebook reminders to ' . $total . ' patient(s).');
        }
    }
}
