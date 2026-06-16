<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\HostelController;

Route::group(['prefix' => 'hostel', 'as' => 'hostel.'], function () {
    Route::get('/',                     [HostelController::class, 'index'])->name('index')->middleware('permission:hostel,view,hostel,add,hostel,edit');

    Route::post('block/store',          [HostelController::class, 'blockStore'])->name('block.store')->middleware('permission:hostel,add');
    Route::get('block/{id}/delete',     [HostelController::class, 'blockDelete'])->name('block.delete')->middleware('permission:hostel,delete');

    Route::post('room/store',           [HostelController::class, 'roomStore'])->name('room.store')->middleware('permission:hostel,add');
    Route::get('room/{id}/delete',      [HostelController::class, 'roomDelete'])->name('room.delete')->middleware('permission:hostel,delete');

    Route::post('allocation/store',     [HostelController::class, 'allocationStore'])->name('allocation.store')->middleware('permission:hostel,add');
    Route::get('allocation/{id}/delete',[HostelController::class, 'allocationDelete'])->name('allocation.delete')->middleware('permission:hostel,delete');
});
