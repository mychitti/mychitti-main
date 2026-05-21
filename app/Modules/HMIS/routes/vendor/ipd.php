<?php

use App\Modules\HMIS\Controllers\Vendor\IpdController;
use App\Modules\HMIS\Controllers\Vendor\PatientNotesController;

Route::group(['prefix' => 'ipd', 'as' => 'ipd.'], function () {
    Route::get('',                                   [IpdController::class, 'index'])->name('index');
    Route::get('export',                             [IpdController::class, 'export'])->name('export')->middleware('permission:ipd_admission,edit');
    Route::get('create',                             [IpdController::class, 'create'])->name('create')->middleware('permission:ipd_admission,add');
    Route::post('store',                             [IpdController::class, 'store'])->name('store')->middleware('permission:ipd_admission,add');
    Route::get('bed-dashboard',                      [IpdController::class, 'bedDashboard'])->name('bed-dashboard');
    Route::get('available-beds',                     [IpdController::class, 'getAvailableBeds'])->name('available-beds');
    Route::get('{id}',                               [IpdController::class, 'show'])->name('show')->middleware('permission:ipd_admission,view');
    Route::get('{id}/discharge',                     [IpdController::class, 'dischargeForm'])->name('discharge-form')->middleware('permission:ipd_admission,discharge');
    Route::put('{id}/discharge',                     [IpdController::class, 'discharge'])->name('discharge')->middleware('permission:ipd_admission,discharge');
    Route::post('{id}/nursing-note',                 [PatientNotesController::class, 'nursingNoteStore'])->name('nursing-note.store');
    Route::delete('{id}/nursing-note/{noteId}',      [PatientNotesController::class, 'nursingNoteDestroy'])->name('nursing-note.destroy');
    Route::post('{id}/diet',                         [PatientNotesController::class, 'dietStore'])->name('diet.store');
    Route::delete('{id}/diet/{dietId}',              [PatientNotesController::class, 'dietDestroy'])->name('diet.destroy');
});
