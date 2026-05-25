<?php

namespace App\Modules\SalesCRM;

use Illuminate\Support\ServiceProvider;

class SalesCRMServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/views', 'sales_crm');
    }
}
