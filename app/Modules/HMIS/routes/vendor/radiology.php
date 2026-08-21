<?php

use App\Modules\HMIS\Controllers\Vendor\RadiologyController;

// Radiology — imaging department, bundled with Hospital Management.
// Sub-features: radiology_study, radiology_viewer, radiology_report,
// radiology_urgent, radiology_schedule, radiology_equipment, radiology_billing.
Route::group(['prefix' => 'radiology', 'as' => 'radiology.'], function () {
    // Smart landing (no per-feature gate — redirects to the first accessible tab)
    Route::get('/',                          [RadiologyController::class, 'home'])->name('home');

    Route::get('worklist',                   [RadiologyController::class, 'worklist'])->name('worklist')->middleware('permission:radiology_study,view');
    Route::get('studies/{id}/start',         [RadiologyController::class, 'startScan'])->name('studies.start')->middleware('permission:radiology_study,edit');

    Route::get('viewer',                     [RadiologyController::class, 'viewer'])->name('viewer')->middleware('permission:radiology_viewer,view');

    Route::get('report',                     [RadiologyController::class, 'reportForm'])->name('report')->middleware('permission:radiology_report,add');
    Route::post('studies/{id}/report',       [RadiologyController::class, 'saveReport'])->name('studies.report')->middleware('permission:radiology_report,add');
    Route::get('reports',                    [RadiologyController::class, 'reports'])->name('reports')->middleware('permission:radiology_report,view');
    Route::get('studies/{id}/print',         [RadiologyController::class, 'report'])->name('studies.print')->middleware('permission:radiology_report,view');
    Route::get('studies/{id}/send',          [RadiologyController::class, 'sendReport'])->name('studies.send')->middleware('permission:radiology_report,send');

    Route::get('urgent',                     [RadiologyController::class, 'urgent'])->name('urgent')->middleware('permission:radiology_urgent,view');
    Route::post('studies/{id}/notify',       [RadiologyController::class, 'notifyUrgent'])->name('studies.notify')->middleware('permission:radiology_urgent,notify');

    Route::get('schedule',                   [RadiologyController::class, 'schedule'])->name('schedule')->middleware('permission:radiology_schedule,view');
    Route::post('schedule/settings',         [RadiologyController::class, 'saveSettings'])->name('schedule.settings')->middleware('permission:radiology_schedule,edit');
    Route::post('order',                     [RadiologyController::class, 'orderStore'])->name('order')->middleware('permission:radiology_schedule,add');

    Route::get('equipment',                  [RadiologyController::class, 'equipment'])->name('equipment')->middleware('permission:radiology_equipment,view');
    Route::post('equipment/save',            [RadiologyController::class, 'saveEquipment'])->name('equipment.save')->middleware('permission:radiology_equipment,add');
    Route::post('equipment/{id}/update',     [RadiologyController::class, 'updateEquipment'])->name('equipment.update')->middleware('permission:radiology_equipment,edit');

    Route::get('billing',                    [RadiologyController::class, 'billing'])->name('billing')->middleware('permission:radiology_billing,view');
    Route::post('studies/{id}/invoice',      [RadiologyController::class, 'generateInvoice'])->name('studies.invoice')->middleware('permission:radiology_billing,add');
    Route::get('invoices/{id}',              [RadiologyController::class, 'invoice'])->name('invoices.view')->middleware('permission:radiology_billing,view');

    // Scan Catalog (scans + prices)
    Route::get('catalog',              [RadiologyController::class, 'catalog'])->name('catalog')->middleware('permission:radiology_catalog,view');
    Route::get('catalog/create',       [RadiologyController::class, 'testForm'])->name('catalog.create')->middleware('permission:radiology_catalog,add');
    Route::post('catalog/store',       [RadiologyController::class, 'saveTest'])->name('catalog.store')->middleware('permission:radiology_catalog,add');
    Route::get('catalog/{id}/edit',    [RadiologyController::class, 'testForm'])->name('catalog.edit')->middleware('permission:radiology_catalog,edit');
    Route::post('catalog/{id}/update', [RadiologyController::class, 'saveTest'])->name('catalog.update')->middleware('permission:radiology_catalog,edit');
    Route::get('catalog/{id}/delete',  [RadiologyController::class, 'deleteTest'])->name('catalog.delete')->middleware('permission:radiology_catalog,delete');
});
