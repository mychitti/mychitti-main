<?php

use App\Modules\HMIS\Controllers\Vendor\PreOpController;

// Pre-Op Patient Preparation — surgical prep workflow (single page), bundled with Hospital Management.
// Multi-feature permission model. The page itself is accessible with any sub-feature
// view permission (checked in the controller); each action below maps to its own feature.
// Features: preop_schedule, preop_case, preop_checklist, preop_med, preop_consent,
//           preop_clearance, preop_anaesthesia, preop_result, preop_blood, preop_handover.
Route::group(['prefix' => 'preop', 'as' => 'preop.'], function () {
    Route::get('/',                            [PreOpController::class, 'index'])->name('index');
    Route::post('schedule',                    [PreOpController::class, 'schedule'])->name('schedule')->middleware('permission:preop_schedule,add');
    Route::post('{id}/update',                 [PreOpController::class, 'updateCase'])->name('update')->middleware('permission:preop_case,edit');
    Route::post('{id}/anaesthesia',            [PreOpController::class, 'anaesthesiaSave'])->name('anaesthesia')->middleware('permission:preop_anaesthesia,edit');
    Route::get('check/{checkId}/toggle',       [PreOpController::class, 'toggleCheck'])->name('check.toggle')->middleware('permission:preop_checklist,edit');
    Route::post('{id}/med',                    [PreOpController::class, 'medAdd'])->name('med.add')->middleware('permission:preop_med,add');
    Route::post('med/{medId}/status',          [PreOpController::class, 'medStatus'])->name('med.status')->middleware('permission:preop_med,edit');
    Route::get('consent/{consentId}/sign',     [PreOpController::class, 'consentSign'])->name('consent.sign')->middleware('permission:preop_consent,edit');
    Route::post('{id}/consent',                [PreOpController::class, 'consentAdd'])->name('consent.add')->middleware('permission:preop_consent,add');
    Route::post('clearance/{clearanceId}/set', [PreOpController::class, 'clearanceSet'])->name('clearance.set')->middleware('permission:preop_clearance,edit');
    Route::post('{id}/result',                 [PreOpController::class, 'resultAdd'])->name('result.add')->middleware('permission:preop_result,add');
    Route::post('{id}/blood',                  [PreOpController::class, 'bloodAdd'])->name('blood.add')->middleware('permission:preop_blood,add');
    Route::post('{id}/handover',               [PreOpController::class, 'handover'])->name('handover')->middleware('permission:preop_handover,edit');
});
