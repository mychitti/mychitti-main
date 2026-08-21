<?php

use App\Modules\HMIS\Controllers\Vendor\PrescriptionController;

Route::group(['prefix' => 'prescription', 'as' => 'prescription.'], function () {
    Route::get('list',                               [PrescriptionController::class, 'index'])->name('list');
    Route::get('export',                             [PrescriptionController::class, 'export'])->name('export')->middleware('permission:prescription,export');
    Route::get('create',                             [PrescriptionController::class, 'create'])->name('create')->middleware('permission:prescription,add');
    Route::post('store',                             [PrescriptionController::class, 'store'])->name('store')->middleware('permission:prescription,add');
    Route::get('search-medicines',                   [PrescriptionController::class, 'searchMedicines'])->name('search-medicines');
    Route::get('dispense',                           [PrescriptionController::class, 'dispenseQueue'])->name('dispense.queue');
    Route::get('dispense/export',                    [PrescriptionController::class, 'dispenseExport'])->name('dispense.export')->middleware('permission:pharmacy_dispense_queue,export');
    // Reusable prescription templates. Declared above the {id} catch-all below, which would
    // otherwise swallow /prescription/templates as a prescription called "templates".
    Route::get('templates',                          [PrescriptionController::class, 'templates'])->name('templates')->middleware('permission:prescription,add');
    Route::post('templates/save',                    [PrescriptionController::class, 'saveTemplate'])->name('templates.save')->middleware('permission:prescription,add');
    Route::get('templates/{id}',                     [PrescriptionController::class, 'templateShow'])->name('templates.show')->middleware('permission:prescription,add');
    Route::post('templates/{id}/delete',             [PrescriptionController::class, 'deleteTemplate'])->name('templates.delete')->middleware('permission:prescription,add');

    Route::get('{id}',                               [PrescriptionController::class, 'show'])->name('show')->middleware('permission:prescription,print');
    Route::get('{id}/edit',                          [PrescriptionController::class, 'edit'])->name('edit')->middleware('permission:prescription,edit');
    Route::post('{id}/update',                       [PrescriptionController::class, 'update'])->name('update')->middleware('permission:prescription,edit');
    Route::get('{id}/dispense',                      [PrescriptionController::class, 'dispenseShow'])->name('dispense.show')->middleware('permission:pharmacy_dispense_queue,view');
    Route::post('{id}/dispense',                     [PrescriptionController::class, 'dispenseProcess'])->name('dispense.process')->middleware('permission:pharmacy_dispense_queue,dispense');
});
