<?php

namespace App\Jobs\Scheduled;

use App\Http\Controllers\CronController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function handle(): void
    {
        try {
            app(CronController::class)->test_dbbackup(request());
        } catch (\Throwable $e) {
            Log::error('DatabaseBackupJob failed: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }
}
 