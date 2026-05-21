<?php

use App\Modules\Laundry\Controllers\Admin\LaundryController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'laundry', 'as' => 'laundry.'], function () {
    Route::get('orders',   [LaundryController::class, 'orders'])->name('orders');
    Route::get('challans', [LaundryController::class, 'challans'])->name('challans');
});
