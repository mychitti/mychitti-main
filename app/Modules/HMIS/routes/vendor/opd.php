<?php

use App\Modules\HMIS\Controllers\Vendor\OpdController;
use App\Modules\HMIS\Controllers\Vendor\OpdConsultationReceiptController;
use App\Modules\HMIS\Controllers\Vendor\OpdLabWorkController;

Route::group(['prefix' => 'opd', 'as' => 'opd.'], function () {
    Route::get('',                   [OpdController::class, 'index'])->name('index');
    Route::get('export',             [OpdController::class, 'export'])->name('export')->middleware('permission:opd_register,export');
    Route::get('create/{id?}',       [OpdController::class, 'create'])->name('create')->middleware('permission:opd_register,add');
    Route::post('store',             [OpdController::class, 'store'])->name('store')->middleware('permission:opd_register,add');

    Route::get('{id}/consultation-receipt',        [OpdConsultationReceiptController::class, 'receipt'])->name('consultation-receipt')->middleware('permission:opd_register,view');
    Route::post('{id}/consultation-receipt/store', [OpdConsultationReceiptController::class, 'store'])->name('consultation-receipt.store')->middleware('permission:opd_register,add');
    Route::get('{id}/consultation-receipt/pdf',    [OpdConsultationReceiptController::class, 'pdf'])->name('consultation-receipt.pdf')->middleware('permission:opd_register,view');

    // What each treatment costs. Rows are also created by pricing a treatment on a visit, so the
    // catalog fills itself in from real use rather than having to be typed out first.
    //
    // Declared above `{id}`: that route is a single segment and would otherwise swallow
    // 'treatment-catalog' and try to load a visit by that name.
    Route::get('treatment-catalog',              [OpdController::class, 'treatmentCatalog'])->name('treatment-catalog')->middleware('permission:opd_register,view');
    Route::post('treatment-catalog/store',       [OpdController::class, 'treatmentCatalogSave'])->name('treatment-catalog.store')->middleware('permission:opd_register,edit');
    Route::post('treatment-catalog/{id}/update', [OpdController::class, 'treatmentCatalogSave'])->name('treatment-catalog.update')->middleware('permission:opd_register,edit');
    Route::get('treatment-catalog/{id}/delete',  [OpdController::class, 'treatmentCatalogDelete'])->name('treatment-catalog.delete')->middleware('permission:opd_register,edit');

    // Lab work — a crown, a lens, a brace — made in-house or sent out. Shown on the consultation
    // screen only for hospitals whose speciality does lab work; the controller re-checks that on
    // every request rather than trusting the tab to have been hidden.
    //
    // Declared above `{id}` for the same reason treatment-catalog is: 'lab-work' would otherwise
    // be read as a visit id.
    //
    // notify goes to the PATIENT; notify-lab and notify-handover go to the LAB. Named apart rather
    // than switched on a parameter, because a message carrying a patient's name and specification
    // to an outside firm should never be one mistyped value away from a message to the patient.
    Route::post('{id}/lab-work',              [OpdLabWorkController::class, 'store'])->name('lab-work.store')->middleware('permission:opd_register,add');
    Route::put('lab-work/{id}',               [OpdLabWorkController::class, 'update'])->name('lab-work.update')->middleware('permission:opd_register,edit');
    Route::post('lab-work/{id}/status',       [OpdLabWorkController::class, 'status'])->name('lab-work.status')->middleware('permission:opd_register,edit');
    Route::post('lab-work/{id}/notify',       [OpdLabWorkController::class, 'notify'])->name('lab-work.notify')->middleware('permission:opd_register,edit');
    Route::post('lab-work/{id}/notify-lab',   [OpdLabWorkController::class, 'notifyVendor'])->name('lab-work.notify-lab')->middleware('permission:opd_register,edit');
    Route::post('lab-work/{id}/handover',     [OpdLabWorkController::class, 'notifyHandover'])->name('lab-work.handover')->middleware('permission:opd_register,edit');
    Route::delete('lab-work/{id}',            [OpdLabWorkController::class, 'destroy'])->name('lab-work.destroy')->middleware('permission:opd_register,delete');

    Route::get('{id}',               [OpdController::class, 'show'])->name('show')->middleware('permission:opd_register,view');
    Route::get('{id}/edit',          [OpdController::class, 'edit'])->name('edit')->middleware('permission:opd_register,edit');
    Route::put('{id}/update',        [OpdController::class, 'update'])->name('update')->middleware('permission:opd_register,edit');
    Route::patch('{id}/quick-update',[OpdController::class, 'quickUpdate'])->name('quick-update')->middleware('permission:opd_register,edit');
    Route::post('{id}/next-visit',   [OpdController::class, 'nextVisit'])->name('next-visit')->middleware('permission:opd_register,edit');
    Route::post('{id}/cancel',       [OpdController::class, 'cancel'])->name('cancel')->middleware('permission:opd_register,cancel');
    Route::delete('{id}',            [OpdController::class, 'destroy'])->name('destroy')->middleware('permission:opd_register,delete');

    // Complaint groups — the sets of complaints this hospital keeps recording together, saved from
    // whatever the doctor has selected. Store-scoped, so one clinic's habits stay its own.
    Route::post('complaint-groups',        [OpdController::class, 'complaintGroupStore'])->name('complaint-groups.store')->middleware('permission:opd_register,edit');
    Route::delete('complaint-groups/{id}', [OpdController::class, 'complaintGroupDestroy'])->name('complaint-groups.destroy')->middleware('permission:opd_register,edit');

    // The same idea for consultation notes, which are prose rather than a term list: the blocks
    // a doctor writes again and again, saved once and dropped in with one tap.
    Route::post('note-templates',        [OpdController::class, 'noteTemplateStore'])->name('note-templates.store')->middleware('permission:opd_register,edit');
    Route::delete('note-templates/{id}', [OpdController::class, 'noteTemplateDestroy'])->name('note-templates.destroy')->middleware('permission:opd_register,edit');

    // OP types — how a visit is paid for (cash, insurer, government scheme). The list is managed
    // on the Hospital Settings page; this is the endpoint its add/off/on buttons post to.
    Route::post('op-types', [OpdController::class, 'opTypesUpdate'])->name('op-types.update')->middleware('permission:opd_register,edit');

    // The hospital's own view of the clinical dropdowns: what the platform offers for its
    // category, plus anything its doctors added, with a switch to stop offering a term.
    Route::get('terms/manage',        [OpdController::class, 'terms'])->name('terms')->middleware('permission:opd_register,edit');
    Route::post('terms/manage',       [OpdController::class, 'termsUpdate'])->name('terms.update')->middleware('permission:opd_register,edit');
});
