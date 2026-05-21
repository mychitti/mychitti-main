<?php

namespace App\Modules\HMIS;

use Illuminate\Support\ServiceProvider;

class HMISServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/views', 'hmis');
    }
}
