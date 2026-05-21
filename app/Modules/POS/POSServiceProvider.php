<?php

namespace App\Modules\POS;

use Illuminate\Support\ServiceProvider;

class POSServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/views', 'pos');
    }
}
