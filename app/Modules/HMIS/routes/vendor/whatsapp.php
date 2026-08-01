<?php

use App\Modules\HMIS\Controllers\Vendor\HmisWhatsAppController;

// "Send on WhatsApp" from the hospital screens — consultation summary, prescription, medicine
// instructions, follow-up, feedback request and lab report.
//
// Each action is gated on the permission for the record it sends, not on a WhatsApp permission of
// its own: whoever may view a lab report may send that report to its patient, and nobody who
// cannot see a prescription can push one out over WhatsApp.
Route::group(['prefix' => 'hmis-whatsapp', 'as' => 'hmis-whatsapp.'], function () {
    Route::post('opd/{id}/treatment', [HmisWhatsAppController::class, 'treatment'])
        ->name('treatment')->middleware('permission:opd_register,view');
    Route::post('opd/{id}/feedback', [HmisWhatsAppController::class, 'opdFeedback'])
        ->name('opd-feedback')->middleware('permission:opd_register,view');

    Route::post('prescription/{id}/share', [HmisWhatsAppController::class, 'prescription'])
        ->name('prescription')->middleware('permission:prescription,print');
    Route::post('prescription/{id}/pdf', [HmisWhatsAppController::class, 'prescriptionPdf'])
        ->name('prescription-pdf')->middleware('permission:prescription,print');
    Route::post('prescription/{id}/medicines', [HmisWhatsAppController::class, 'medicines'])
        ->name('medicines')->middleware('permission:prescription,print');
    Route::post('prescription/{id}/follow-up', [HmisWhatsAppController::class, 'prescriptionFollowUp'])
        ->name('prescription-followup')->middleware('permission:prescription,print');

    // Appointments carry no per-feature permission anywhere in this module — the whole group is
    // gated by planwise:hospital_manage and nothing finer. Naming a feature that does not exist
    // in `features` would deny every staff role outright, so these two match their neighbours.
    Route::post('appointment/{id}/follow-up', [HmisWhatsAppController::class, 'appointmentFollowUp'])
        ->name('appointment-followup');
    Route::post('appointment/{id}/feedback', [HmisWhatsAppController::class, 'appointmentFeedback'])
        ->name('appointment-feedback');

    Route::post('lab/{id}/report', [HmisWhatsAppController::class, 'labReport'])
        ->name('lab-report')->middleware('permission:lab_report,send');
});
