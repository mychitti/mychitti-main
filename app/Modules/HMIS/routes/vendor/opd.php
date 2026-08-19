<?php

use App\Modules\HMIS\Controllers\Vendor\OpdController;
use App\Modules\HMIS\Controllers\Vendor\OpdConsultationReceiptController;

Route::group(['prefix' => 'opd', 'as' => 'opd.'], function () {
    Route::get('',                   [OpdController::class, 'index'])->name('index');
    Route::get('export',             [OpdController::class, 'export'])->name('export')->middleware('permission:opd_register,export');
    Route::get('create/{id?}',       [OpdController::class, 'create'])->name('create')->middleware('permission:opd_register,add');
    Route::post('store',             [OpdController::class, 'store'])->name('store')->middleware('permission:opd_register,add');

    Route::get('{id}/consultation-receipt',        [OpdConsultationReceiptController::class, 'receipt'])->name('consultation-receipt')->middleware('permission:opd_register,view');
    Route::post('{id}/consultation-receipt/store', [OpdConsultationReceiptController::class, 'store'])->name('consultation-receipt.store')->middleware('permission:opd_register,add');
    Route::get('{id}/consultation-receipt/pdf',    [OpdConsultationReceiptController::class, 'pdf'])->name('consultation-receipt.pdf')->middleware('permission:opd_register,view');

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

    // The hospital's own view of the clinical dropdowns: what the platform offers for its
    // category, plus anything its doctors added, with a switch to stop offering a term.
    Route::get('terms/manage',        [OpdController::class, 'terms'])->name('terms')->middleware('permission:opd_register,edit');
    Route::post('terms/manage',       [OpdController::class, 'termsUpdate'])->name('terms.update')->middleware('permission:opd_register,edit');
});
