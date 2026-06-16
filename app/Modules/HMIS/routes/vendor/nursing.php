<?php

use App\Modules\HMIS\Controllers\Vendor\NursingStationController;

// Nursing Station — ward workstation (single page), bundled with Hospital Management.
// Multi-feature permission model. The page itself is accessible with any sub-feature
// view permission (checked in the controller); each action below maps to its own feature.
// Features: nursing_vitals, nursing_mar, nursing_fluid, nursing_note, nursing_task, nursing_handover.
Route::group(['prefix' => 'nursing', 'as' => 'nursing.'], function () {
    Route::get('/',                        [NursingStationController::class, 'index'])->name('index');
    Route::post('vitals/{admissionId}',    [NursingStationController::class, 'recordVital'])->name('vitals')->middleware('permission:nursing_vitals,add');
    Route::post('mar/{admissionId}/order', [NursingStationController::class, 'marAddOrder'])->name('mar.order')->middleware('permission:nursing_mar,add');
    Route::post('mar/{orderId}/give',      [NursingStationController::class, 'marGive'])->name('mar.give')->middleware('permission:nursing_mar,edit');
    Route::post('mar/{orderId}/miss',      [NursingStationController::class, 'marMiss'])->name('mar.miss')->middleware('permission:nursing_mar,edit');
    Route::post('fluid/{admissionId}',     [NursingStationController::class, 'fluidAdd'])->name('fluid')->middleware('permission:nursing_fluid,add');
    Route::post('note/{admissionId}',      [NursingStationController::class, 'noteAdd'])->name('note')->middleware('permission:nursing_note,add');
    Route::post('task',                    [NursingStationController::class, 'taskAdd'])->name('task.add')->middleware('permission:nursing_task,add');
    Route::get('task/{id}/complete',       [NursingStationController::class, 'taskComplete'])->name('task.complete')->middleware('permission:nursing_task,edit');
    Route::post('handover',                [NursingStationController::class, 'handoverSave'])->name('handover')->middleware('permission:nursing_handover,edit');
});
