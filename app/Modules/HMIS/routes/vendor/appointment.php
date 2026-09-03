<?php

use App\Modules\HMIS\Controllers\Vendor\AppointmentController;

// Every route here is gated on hmis_appointment, seeded by AppointmentController::ensurePermission()
// and shown on the role grid under Hospital. The lookup endpoints ride the permission of the screen
// that calls them rather than one of their own — searching for a doctor is only ever a step in
// booking or reassigning, never something a role is granted on its own.
Route::group(['prefix' => 'appointment', 'as' => 'appointment.'], function () {
    Route::get('list',                               [AppointmentController::class, 'index'])->name('list')->middleware('permission:hmis_appointment,list');
    Route::get('create',                             [AppointmentController::class, 'create'])->name('create')->middleware('permission:hmis_appointment,add');
    Route::post('store',                             [AppointmentController::class, 'store'])->name('store')->middleware('permission:hmis_appointment,add');
    Route::get('lookup-lead',                        [AppointmentController::class, 'lookupLead'])->name('lookup-lead')->middleware('permission:hmis_appointment,add');
    Route::post('store-from-lead',                   [AppointmentController::class, 'storeFromLead'])->name('store-from-lead')->middleware('permission:hmis_appointment,add');
    Route::get('available-slots',                    [AppointmentController::class, 'availableSlots'])->name('available-slots')->middleware('permission:hmis_appointment,add,hmis_appointment,reschedule');
    Route::get('search-patients',                    [AppointmentController::class, 'searchPatients'])->name('search-patients')->middleware('permission:hmis_appointment,add');
    Route::get('search-doctors',                     [AppointmentController::class, 'searchDoctors'])->name('search-doctors')->middleware('permission:hmis_appointment,add,hmis_appointment,reassign');
    Route::get('{id}',                               [AppointmentController::class, 'show'])->name('show')->middleware('permission:hmis_appointment,view');
    Route::post('{id}/status',                       [AppointmentController::class, 'updateStatus'])->name('status')->middleware('permission:hmis_appointment,status_change');
    // One endpoint, two modes: move it now, or put the new time to the patient and wait. See the
    // controller — which one runs is a field on the form, not a different button on a wall.
    Route::post('{id}/reschedule',                   [AppointmentController::class, 'reschedule'])->name('reschedule')->middleware('permission:hmis_appointment,reschedule');
    Route::post('reschedule-request/{id}/withdraw',  [AppointmentController::class, 'rescheduleWithdraw'])->name('reschedule.withdraw')->middleware('permission:hmis_appointment,reschedule');
    Route::post('reschedule-request/{id}/resend',    [AppointmentController::class, 'rescheduleResend'])->name('reschedule.resend')->middleware('permission:hmis_appointment,reschedule');
    // A follow-up is a new appointment, so it is booking, not rescheduling.
    Route::post('{id}/next-visit',                   [AppointmentController::class, 'nextVisit'])->name('next-visit')->middleware('permission:hmis_appointment,add');
    Route::post('{id}/reassign',                     [AppointmentController::class, 'reassign'])->name('reassign')->middleware('permission:hmis_appointment,reassign');
});
