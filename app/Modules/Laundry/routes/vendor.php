<?php

use App\Modules\Laundry\Controllers\Vendor\LaundryController;
use Illuminate\Support\Facades\Route;

// ══════════════════════════════════════════════════════════════════════════════
//  UNIQUE LAUNDRY ROUTES — URL: /laundry/…
//  (walk-in orders, challans, item master, monthly register)
//  HR, inventory, staff → served via base vendor routes + IoC controller binding
// ══════════════════════════════════════════════════════════════════════════════
Route::group(['prefix' => 'laundry', 'as' => 'laundry.', 'middleware' => ['planwise:laundry', 'business-type:laundry']], function () {
    require __DIR__ . '/vendor/laundry-core.php';
});
