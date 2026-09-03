<?php

use App\Modules\HMIS\Controllers\Vendor\WardController;

Route::group(['prefix' => 'ward', 'as' => 'ward.'], function () {
    Route::get('',                                   [WardController::class, 'index'])->name('index')->middleware('permission:ward,list');
    Route::get('create',                             [WardController::class, 'create'])->name('create')->middleware('permission:ward,add');
    Route::post('store',                             [WardController::class, 'store'])->name('store')->middleware('permission:ward,add');
    Route::get('{id}/edit',                          [WardController::class, 'edit'])->name('edit')->middleware('permission:ward,edit');
    Route::put('{id}/update',                        [WardController::class, 'update'])->name('update')->middleware('permission:ward,edit');
    Route::delete('{id}/delete',                     [WardController::class, 'destroy'])->name('destroy')->middleware('permission:ward,delete');
    Route::post('{id}/toggle',                       [WardController::class, 'toggleStatus'])->name('toggle')->middleware('permission:ward,status_change');
    Route::get('{wardId}/beds',                      [WardController::class, 'beds'])->name('beds')->middleware('permission:bed,list');
    Route::post('{wardId}/bed/store',                [WardController::class, 'bedStore'])->name('bed.store')->middleware('permission:bed,add');
    Route::put('{wardId}/bed/{bedId}/update',        [WardController::class, 'bedUpdate'])->name('bed.update')->middleware('permission:bed,edit');
    Route::delete('{wardId}/bed/{bedId}/delete',     [WardController::class, 'bedDestroy'])->name('bed.destroy')->middleware('permission:bed,delete');
});
