<?php

use App\Modules\HMIS\Controllers\Vendor\IpdController;
use App\Modules\HMIS\Controllers\Vendor\PatientNotesController;

Route::group(['prefix' => 'ipd', 'as' => 'ipd.'], function () {
    Route::get('',                                   [IpdController::class, 'index'])->name('index')->middleware('permission:ipd_admission,list');
    Route::get('export',                             [IpdController::class, 'export'])->name('export')->middleware('permission:ipd_admission,export');
    Route::get('create',                             [IpdController::class, 'create'])->name('create')->middleware('permission:ipd_admission,add');
    Route::post('store',                             [IpdController::class, 'store'])->name('store')->middleware('permission:ipd_admission,add');
    Route::get('bed-dashboard',                      [IpdController::class, 'bedDashboard'])->name('bed-dashboard')->middleware('permission:ipd_admission,list');
    Route::get('available-beds',                     [IpdController::class, 'getAvailableBeds'])->name('available-beds')->middleware('permission:ipd_admission,add');
    Route::get('{id}',                               [IpdController::class, 'show'])->name('show')->middleware('permission:ipd_admission,view');
    Route::get('{id}/discharge',                     [IpdController::class, 'dischargeForm'])->name('discharge-form')->middleware('permission:ipd_admission,discharge');
    Route::put('{id}/discharge',                     [IpdController::class, 'discharge'])->name('discharge')->middleware('permission:ipd_admission,discharge');
    Route::post('{id}/nursing-note',                 [PatientNotesController::class, 'nursingNoteStore'])->name('nursing-note.store')->middleware('permission:nursing_notes,add');
    Route::delete('{id}/nursing-note/{noteId}',      [PatientNotesController::class, 'nursingNoteDestroy'])->name('nursing-note.destroy')->middleware('permission:nursing_notes,delete');
    Route::post('{id}/diet',                         [PatientNotesController::class, 'dietStore'])->name('diet.store')->middleware('permission:diet_chart,add');
    Route::delete('{id}/diet/{dietId}',              [PatientNotesController::class, 'dietDestroy'])->name('diet.destroy')->middleware('permission:diet_chart,delete');

    // Assign / unassign nurses to an admission (multiple, re-assignable)
    Route::post('{id}/nurses',                       [IpdController::class, 'assignNurses'])->name('nurses.assign')->middleware('permission:ipd_admission,view');
    Route::get('{id}/nurses/{nurseId}/remove',       [IpdController::class, 'removeNurse'])->name('nurses.remove')->middleware('permission:ipd_admission,view');
});
