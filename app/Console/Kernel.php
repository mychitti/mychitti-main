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
        //
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

        // PUNCH IN REMINDER =======================================
        $schedule->call(function () {
            PunchInReminder::dispatch();
        })->name('punch-in-reminder')
            ->everyMinute() // main 
            // ->dailyAt('22:30')
            ->timezone($tz)
            ->withoutOverlapping();


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
