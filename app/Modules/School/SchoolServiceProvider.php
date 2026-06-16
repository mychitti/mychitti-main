<?php

namespace App\Modules\School;

use Illuminate\Support\ServiceProvider;

class SchoolServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/views', 'school');
    }
}
