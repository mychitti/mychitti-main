<?php

namespace App\Modules\PosRetail;

use Illuminate\Support\ServiceProvider;

class PosRetailServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/views', 'posretail');
    }
}
