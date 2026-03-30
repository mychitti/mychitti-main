<?php

namespace App\Console\Commands;

use App\Jobs\Scheduled\CancelStaleOrdersJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CancelOrder extends Command
{
    protected $signature = 'cancel:order';

    protected $description = 'Cancel pending orders not accepted within 24 hours (runs same logic as the scheduled job)';

    public function handle(): int
    {
        Log::info('Cancel order: running CancelStaleOrdersJob synchronously');

        CancelStaleOrdersJob::dispatchSync();

        return self::SUCCESS;
    } 
} 