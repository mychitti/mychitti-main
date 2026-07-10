<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\ShortLeaveController;

Route::group(['prefix' => 'short-leave', 'as' => 'short-leave.'], function () {
    Route::get('/',            [ShortLeaveController::class, 'index'])->name('index')->middleware('permission:short_leave,view,short_leave,add,short_leave,return');
    Route::get('create',       [ShortLeaveController::class, 'create'])->name('create')->middleware('permission:short_leave,add');
    Route::post('store',       [ShortLeaveController::class, 'store'])->name('store')->middleware('permission:short_leave,add');
    Route::post('{id}/return', [ShortLeaveController::class, 'markReturn'])->name('return')->middleware('permission:short_leave,return');
    Route::get('{id}/slip',    [ShortLeaveController::class, 'slip'])->name('slip')->middleware('permission:short_leave,view');
    Route::get('{id}/delete',  [ShortLeaveController::class, 'delete'])->name('delete')->middleware('permission:short_leave,delete');
});
 