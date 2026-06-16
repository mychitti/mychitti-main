<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\NoticeController;

Route::group(['prefix' => 'notices', 'as' => 'notices.'], function () {
    Route::get('/',             [NoticeController::class, 'index'])->name('index')->middleware('permission:notices,view,notices,add,notices,edit');
    Route::get('create',        [NoticeController::class, 'create'])->name('create')->middleware('permission:notices,add');
    Route::post('store',        [NoticeController::class, 'store'])->name('store')->middleware('permission:notices,add');
    Route::get('{id}/edit',     [NoticeController::class, 'edit'])->name('edit')->middleware('permission:notices,edit');
    Route::post('{id}/update',  [NoticeController::class, 'update'])->name('update')->middleware('permission:notices,edit');
    Route::get('{id}/toggle',   [NoticeController::class, 'toggle'])->name('toggle')->middleware('permission:notices,edit');
    Route::get('{id}/delete',   [NoticeController::class, 'delete'])->name('delete')->middleware('permission:notices,delete');
});
