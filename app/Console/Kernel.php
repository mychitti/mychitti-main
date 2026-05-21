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
use App\Jobs\Scheduled\SendPaymentRemindersJob;
use App\Jobs\Scheduled\UnavailableProviderNotificationJob;
use App\Models\Banner;
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
        $schedule->call(function () {
            $date = now()->toDateString();

            // Process vendors in chunks to avoid memory overload
            Store::withoutGlobalScopes()->chunk(100, function ($stores) use ($date) {
                foreach ($stores as $store) {
                    ProcessSingleVendorAccount::dispatch($store->id, $date);
                }
            });
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
