<?php

namespace App\Modules\Laundry;

use Illuminate\Support\ServiceProvider;

class LaundryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/views', 'laundry');
    }
}
