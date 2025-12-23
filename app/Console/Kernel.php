<?php

namespace App\Console;

use App\Jobs\LowBalanceNotification;
use App\Jobs\ProcessMonthlyDepreciation;
use App\Jobs\ProcessSingleVendorAccount;
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
        $schedule->call(function () {
            $date = now()->toDateString();

            // Process vendors in chunks to avoid memory overload
            Store::withoutGlobalScopes()->chunk(100, function ($stores) use ($date) {
                foreach ($stores as $store) {
                    ProcessSingleVendorAccount::dispatch($store->id, $date);
                }
            });
        })->dailyAt('23:59');

        $schedule->call(function () {
            LowBalanceNotification::dispatch();
        })->dailyAt('23:59');
        // })->everyTwoMinutes();  


        $schedule->call(function () {
            if (now()->isLastOfMonth()) {
            // Log::info('Schedule job started at ' . now());
            ProcessMonthlyDepreciation::dispatch();
            // Log::info('Schedule job finished at ' . now());
            }
        // })->everyFiveSeconds();
        })->dailyAt('23:59');

        $schedule->call(function () {
            PunchInReminder::dispatch();
        })->everyMinute();
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
