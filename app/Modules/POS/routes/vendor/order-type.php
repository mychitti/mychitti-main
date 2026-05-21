<?php

use App\Modules\POS\Controllers\Vendor\OrderTypeController;

Route::group(['prefix' => 'order-type', 'as' => 'order-type.'], function () {
    Route::post('store',      [OrderTypeController::class, 'store'])->name('store')->middleware('permission:pos_order_type,add');
    Route::get('delete/{id}', [OrderTypeController::class, 'delete'])->name('delete')->middleware('permission:pos_order_type,delete');
    Route::post('update',     [OrderTypeController::class, 'update'])->name('update')->middleware('permission:pos_order_type,edit');
});
