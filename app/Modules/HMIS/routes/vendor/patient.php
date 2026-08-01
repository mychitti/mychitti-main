<?php

use App\Modules\HMIS\Controllers\Vendor\PatientController;

Route::get('patient/add', [PatientController::class, 'index'])->name('patient.add');
Route::group(['prefix' => 'patient', 'as' => 'patient.'], function () {
    Route::post('quick-save',                        [PatientController::class, 'quickSave'])->name('quick-save');
    Route::get('list',                               [PatientController::class, 'list'])->name('list');
    Route::get('export',                             [PatientController::class, 'export'])->name('export')->middleware('permission:patient,export');
    // Above {id} on purpose — a literal segment declared after the wildcard is never reached.
    Route::get('sent-documents',                     [PatientController::class, 'sentDocuments'])->name('sent-documents')->middleware('permission:patient_documents,list');
    Route::post('save',                              [PatientController::class, 'save'])->name('save')->middleware('permission:patient,add');
    Route::post('upload-excel',                      [PatientController::class, 'upload_excel'])->name('upload-excel')->middleware('permission:patient,export');
    Route::get('{id}',                               [PatientController::class, 'show'])->name('show')->middleware('permission:patient,view');
    Route::get('{id}/edit',                          [PatientController::class, 'edit'])->name('edit')->middleware('permission:patient,edit');
    Route::post('{id}/update',                       [PatientController::class, 'update'])->name('update')->middleware('permission:patient,edit');
    // Tests/scans raised from the patient page (no per-feature gate — same as lab.order.from-opd,
    // any hospital_manage staff examining the patient may order).
    Route::post('{id}/order-tests',                  [PatientController::class, 'orderTests'])->name('order-tests');
    Route::get('{id}/documents',                     [PatientController::class, 'listDocuments'])->name('documents')->middleware('permission:patient_documents,list');
    Route::post('{id}/upload-documents',             [PatientController::class, 'uploadDocuments'])->name('upload-documents')->middleware('permission:patient_documents,add');
    Route::post('{id}/send-document',                [PatientController::class, 'sendDocument'])->name('send-document')->middleware('permission:patient_documents,add');
    Route::delete('{id}/document/{docId}',           [PatientController::class, 'deleteDocument'])->name('delete-document')->middleware('permission:patient_documents,delete');
    Route::get('{id}/delete',                        [PatientController::class, 'destroy'])->name('delete')->middleware('permission:patient,delete');
});
