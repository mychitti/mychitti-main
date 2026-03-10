<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */ 
    public function boot()
    {
        Broadcast::routes();

        Broadcast::channel('locations', function () {
            \Log::info('Client is subscribing to public channel: locations');
            return true;
        });
    }
}
