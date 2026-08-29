<?php

use App\Modules\HMIS\Controllers\Vendor\AppointmentController;

Route::group(['prefix' => 'appointment', 'as' => 'appointment.'], function () {
    Route::get('list',                               [AppointmentController::class, 'index'])->name('list');
    Route::get('create',                             [AppointmentController::class, 'create'])->name('create');
    Route::post('store',                             [AppointmentController::class, 'store'])->name('store');
    Route::get('lookup-lead',                        [AppointmentController::class, 'lookupLead'])->name('lookup-lead');
    Route::post('store-from-lead',                   [AppointmentController::class, 'storeFromLead'])->name('store-from-lead');
    Route::get('available-slots',                    [AppointmentController::class, 'availableSlots'])->name('available-slots');
    Route::get('search-patients',                    [AppointmentController::class, 'searchPatients'])->name('search-patients');
    Route::get('search-doctors',                     [AppointmentController::class, 'searchDoctors'])->name('search-doctors');
    Route::get('{id}',                               [AppointmentController::class, 'show'])->name('show');
    Route::post('{id}/status',                       [AppointmentController::class, 'updateStatus'])->name('status');
    // One endpoint, two modes: move it now, or put the new time to the patient and wait. See the
    // controller — which one runs is a field on the form, not a different button on a wall.
    Route::post('{id}/reschedule',                   [AppointmentController::class, 'reschedule'])->name('reschedule');
    Route::post('reschedule-request/{id}/withdraw',  [AppointmentController::class, 'rescheduleWithdraw'])->name('reschedule.withdraw');
    Route::post('reschedule-request/{id}/resend',    [AppointmentController::class, 'rescheduleResend'])->name('reschedule.resend');
    Route::post('{id}/next-visit',                   [AppointmentController::class, 'nextVisit'])->name('next-visit');
    Route::post('{id}/reassign',                     [AppointmentController::class, 'reassign'])->name('reassign');
});
