<?php

namespace App\Console;

use App\Jobs\LowBalanceNotification;
use App\Jobs\MarkEmployeeAttendance;
use App\Jobs\ProcessMonthlyDepreciation;
use App\Jobs\ProcessSingleVendorAccount;
use App\Jobs\PunchInReminder;
use App\Jobs\Scheduled\CancelStaleOrdersJob;
use App\Jobs\Scheduled\DatabaseBackupJob;
use App\Jobs\Scheduled\EmployeeAttendanceMarkJob;
use App\Jobs\Scheduled\MonthlyMaintenanceReminderJob;
use App\Jobs\Scheduled\RegenerateSitemapJob;
use App\Jobs\Scheduled\SendAppointmentRemindersJob;
use App\Jobs\Scheduled\SendPaymentRemindersJob;
use App\Jobs\Scheduled\UnavailableProviderNotificationJob;
use App\Models\Banner;
use App\Models\PosToken;
use App\Models\Store;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\DeductPlatformFee::class,
        \App\Console\Commands\BillWhatsAppSubscriptions::class,
        \App\Console\Commands\BackfillVariationStockCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $tz = 'Asia/Kolkata';
 
        // URL crons migrated from wget → queued jobs (run `php artisan queue:work` with QUEUE_CONNECTION=redis or database)
        $schedule->job(new CancelStaleOrdersJob)->everyFiveMinutes()
            ->name('cancel-stale-orders')
            ->withoutOverlapping();

        // UNAVAILABLE PROVIDER NOTIFICATION =======================
        $schedule->job(new UnavailableProviderNotificationJob)->everyFiveMinutes()
            ->name('unavailable-provider-notification')
            ->withoutOverlapping();

        $schedule->job(new SendPaymentRemindersJob)->dailyAt('09:00')
            ->timezone($tz)
            ->name('payment-reminders')
            ->withoutOverlapping();

        $schedule->job(new MonthlyMaintenanceReminderJob)->dailyAt('09:00')
            ->timezone($tz)
            ->name('maintenance-reminders')
            ->withoutOverlapping();

        // WhatsApp reminders for HMIS appointments (per-vendor schedule; deduped per appointment)
        $schedule->job(new SendAppointmentRemindersJob)->hourly()
            ->timezone($tz)
            ->name('whatsapp-appointment-reminders')
            ->withoutOverlapping();

        $schedule->job(new RegenerateSitemapJob)->dailyAt('00:00')
            ->timezone($tz)
            ->name('regenerate-sitemap')
            ->withoutOverlapping();

        // Was: 0 0,12 */2 * * — midnight & noon on every 2nd day of month (mysqldump; long timeout on job)
        $schedule->job(new DatabaseBackupJob)->cron('0 0,12 */2 * *')
            ->timezone($tz)
            ->name('database-sql-backup')
            ->withoutOverlapping();

        $schedule->job(new EmployeeAttendanceMarkJob)->dailyAt('21:00')
            ->timezone($tz)
            ->name('employee-attendance-mark')
            ->withoutOverlapping();

        // PROCESS VENDOR ====================================
        // Only enqueue stores that actually had a paid POS token today. Most stores
        // have none on a given day, so fanning out one job per store just floods the
        // queue with no-ops — dispatch for the stores with real sales only.
        $schedule->call(function () {
            $date  = now()->toDateString();
            $start = $date . ' 00:00:00';
            $end   = $date . ' 23:59:59';

            $storeIds = PosToken::where('payment_status', 'paid')
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('created_at', [$start, $end])
                        ->orWhereBetween('paid_at', [$start, $end]);
                })
                ->distinct()
                ->pluck('store_id');

            foreach ($storeIds as $storeId) {
                ProcessSingleVendorAccount::dispatch($storeId, $date);
            }
        })->name('process-vendor-accounts')->dailyAt('23:59')
            ->timezone($tz)
            ->withoutOverlapping();

        // LOW BALANCE NOTIFICATION ============================
        $schedule->call(function () {
            LowBalanceNotification::dispatch();
        })->name('low-balance-notification')->dailyAt('23:59')
            ->timezone($tz)
            ->withoutOverlapping();
        // })->everyTwoMinutes();  


        // PROCESS MONTHLY DEPRECIATION ==========================
        $schedule->call(function () {
            if (now()->isLastOfMonth()) {
                // Log::info('Schedule job started at ' . now());
                ProcessMonthlyDepreciation::dispatch();
                // Log::info('Schedule job finished at ' . now());
            }
            // })->everyFiveSeconds();
        })->name('monthly-depreciation')->dailyAt('23:59')
            ->timezone($tz)
            ->withoutOverlapping();

        // BANNER SCHEDULED PUBLISH ================================
        $schedule->call(function () { 
            Banner::withoutGlobalScopes()
                ->where('status', 0)
                ->whereNotNull('publish_at')
                ->where('publish_at', '<=', now())
                ->update(['status' => 1]);
                Log::info('Schedule job finished at ' . now());

        })->name('banner-scheduled-publish')
            ->everyMinute()
            ->timezone($tz)
            ->withoutOverlapping();

        // SCHEDULED EMAIL BROADCAST =====================================
        $schedule->call(function () {
            \App\Models\ScheduledEmail::pending()->each(function ($scheduled) {
                \App\Jobs\SendScheduledEmailBroadcast::dispatch($scheduled->id);
                $scheduled->update(['status' => 'sent', 'sent_at' => now()]);
            });
        })->everyMinute()
        ->timezone($tz)
        ->name('send-scheduled-emails')
        ->withoutOverlapping();

       // NOTIFICATION SCHEDULED PUBLISH ================================
        $schedule->call(function () {
            \App\Jobs\SendScheduledNotifications::dispatch();
        })->everyMinute()
        ->timezone($tz)
        ->name('send-scheduled-notifications')
        ->withoutOverlapping();

        // PUNCH IN REMINDER =======================================
        $schedule->call(function () {
            PunchInReminder::dispatch();
        })->name('punch-in-reminder')
            ->everyMinute() // main 
            // ->dailyAt('22:30')
            ->timezone($tz)
            ->withoutOverlapping();


        // LEAD ROUND DISPATCH =======================================
        $schedule->command('leads:dispatch-rounds')
            ->everyMinute()
            ->timezone($tz)
            ->name('lead-round-dispatch')
            ->withoutOverlapping();

        // LEAD NOTE REMINDERS =======================================
        $schedule->call(function () {
            $due = \App\Models\LeadNote::whereNotNull('remind_at')
                ->whereNull('notified_at')
                ->where('remind_at', '<=', now())
                ->with('store.vendor')
                ->get();
            foreach ($due as $note) {
                $fcm = $note->store?->vendor?->cm_firebase_token;
                if ($fcm) {
                    \App\CentralLogics\Helpers::send_push_notif_to_device($fcm, [
                        'title' => 'Lead Reminder',
                        'description' => $note->note,
                        'order_id' => $note->service_id,
                        'image' => '',
                        'type' => 'block',
                    ]);
                }
                $note->update(['notified_at' => now()]);
            }
        })->everyMinute()->timezone($tz)->name('lead-note-reminders')->withoutOverlapping();

        // SEO COMBOS (category x zone) reconciliation + queue keyword generation =========
        // Reconciles landing-page combos and queues AI copy generation for new/ungenerated ones.
        // Generation is dispatched to the `seo` queue — a worker MUST be processing it, e.g.
        // `php artisan queue:work --queue=seo` (or add `seo` to your Supervisor worker's --queue list),
         // otherwise the jobs pile up unprocessed.
        $schedule->command('seo:sync-combos --generate')->dailyAt('02:30')
            ->timezone($tz)
            ->name('seo-sync-combos')
            ->withoutOverlapping();
  
        // PHASE 3 — AI SEARCH & INTELLIGENCE =================================
        // Recompute MC Trust Layer scores/badges (pure SQL, fast).
        $schedule->command('vendor:sync-trust-score')
            ->dailyAt('01:30')
            ->timezone($tz)
            ->name('vendor-sync-trust-score')
            ->withoutOverlapping();

        // Review Intelligence — sentiment on NEW reviews only (incremental, cheap). Falls back to
        // OpenAI on the ai-server if Anthropic is unfunded.
        $schedule->command('reviews:analyze-sentiment')
            ->dailyAt('01:45')
            ->timezone($tz)
            ->name('reviews-analyze-sentiment')
            ->withoutOverlapping();

        // Rebuild the AI Search business index. Paced for the Voyage free tier (~70 min full run),
        // so give the overlap lock a wide window. Runs at a low-traffic hour.
        $schedule->command('ai:sync-business-embeddings')
            ->dailyAt('02:00')
            ->timezone($tz)
            ->name('ai-sync-business-embeddings')
            ->withoutOverlapping(180);

        // DPDP DATA RETENTION — anonymize PII on behavioral logs older than 12 months.
        // Dry-run first (`php artisan data:purge-retention --dry-run`) to verify counts before
        // relying on this scheduled run.
        $schedule->command('data:purge-retention')
            ->dailyAt('03:30')
            ->timezone($tz)
            ->name('data-purge-retention')
            ->withoutOverlapping();

        // WHATSAPP BUSINESS PLATFORM RENEWALS ==================================
        // Daily, not monthly: every store renews on its own anniversary, and a store whose
        // wallet was short is retried the next day inside the grace window.
        $schedule->command('whatsapp:bill')
            ->dailyAt('05:00')
            ->timezone($tz)
            ->name('whatsapp-billing')
            ->withoutOverlapping();

        // PLATFORM FEE MONTHLY DEDUCTION =======================================
        $schedule->command('platform-fee:deduct')
            ->monthlyOn(1, '00:00')
            ->timezone($tz)
            ->withoutOverlapping()
            ->name('platform-fee-deduct');

        // EMPLOYEE ATTENDANCE =======================================
        // $schedule->call(function () {
        //     Log::info('Attendance scheduler triggered at ' . now());
        //     MarkEmployeeAttendance::dispatch();
        // })->name('employee-attendance')
        //     // ->dailyAt('21:00')
        //     ->everyMinute()
        //     ->timezone('Asia/Kolkata')
        //     ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
