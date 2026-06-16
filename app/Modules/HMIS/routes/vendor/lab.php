<?php

use App\Modules\HMIS\Controllers\Vendor\LabController;

// Laboratory — bundled with Hospital Management (gated by planwise:hospital_manage in vendor.php).
// Multi-feature permission model: each tab is its own feature with standard actions.
// Features: lab_worklist, lab_result, lab_report, lab_critical, lab_order,
//           lab_reagent, lab_history, lab_billing, lab_catalog.
Route::group(['prefix' => 'lab', 'as' => 'lab.'], function () {
    // Landing — smart redirect to the first tab the user can access (no gate here).
    Route::get('/', [LabController::class, 'home'])->name('home');

    // Worklist
    Route::get('worklist',          [LabController::class, 'worklist'])->name('worklist')->middleware('permission:lab_worklist,view');
    Route::get('orders/{id}/start', [LabController::class, 'startTest'])->name('orders.start')->middleware('permission:lab_worklist,edit');

    // Result Entry
    Route::get('result-entry',         [LabController::class, 'resultEntry'])->name('result-entry')->middleware('permission:lab_result,view');
    Route::post('orders/{id}/results', [LabController::class, 'saveResults'])->name('orders.results')->middleware('permission:lab_result,edit');

    // Reports
    Route::get('reports',            [LabController::class, 'reports'])->name('reports')->middleware('permission:lab_report,view');
    Route::get('orders/{id}/report', [LabController::class, 'report'])->name('orders.report')->middleware('permission:lab_report,view');
    Route::get('orders/{id}/send',   [LabController::class, 'sendReport'])->name('orders.send')->middleware('permission:lab_report,send');

    // Critical Values
    Route::get('critical',              [LabController::class, 'critical'])->name('critical')->middleware('permission:lab_critical,view');
    Route::post('results/{id}/notify',  [LabController::class, 'notifyCritical'])->name('results.notify')->middleware('permission:lab_critical,notify');
    Route::post('critical/notify-all',  [LabController::class, 'notifyAllCritical'])->name('critical.notify-all')->middleware('permission:lab_critical,notify');

    // Order New Test
    Route::get('order',        [LabController::class, 'orderForm'])->name('order')->middleware('permission:lab_order,view');
    Route::post('order/store', [LabController::class, 'storeOrder'])->name('order.store')->middleware('permission:lab_order,add');
    // Order created from an OPD/IPD consultation (no per-feature gate — any hospital_manage staff doing a consult)
    Route::post('order/from-opd', [LabController::class, 'orderFromOpd'])->name('order.from-opd');

    // Reagents
    Route::get('reagents',              [LabController::class, 'reagents'])->name('reagents')->middleware('permission:lab_reagent,view');
    Route::post('reagents/save',        [LabController::class, 'saveReagent'])->name('reagents.save')->middleware('permission:lab_reagent,add');
    Route::post('reagents/{id}/update', [LabController::class, 'updateReagent'])->name('reagents.update')->middleware('permission:lab_reagent,edit');
    Route::get('reagents/{id}/delete',  [LabController::class, 'deleteReagent'])->name('reagents.delete')->middleware('permission:lab_reagent,delete');

    // Test History
    Route::get('history', [LabController::class, 'history'])->name('history')->middleware('permission:lab_history,view');

    // Billing
    Route::get('billing',              [LabController::class, 'billing'])->name('billing')->middleware('permission:lab_billing,view');
    Route::post('orders/{id}/invoice', [LabController::class, 'generateInvoice'])->name('orders.invoice')->middleware('permission:lab_billing,add');

    // Test Catalog (management)
    Route::get('catalog',              [LabController::class, 'catalog'])->name('catalog')->middleware('permission:lab_catalog,view');
    Route::get('catalog/create',       [LabController::class, 'testForm'])->name('catalog.create')->middleware('permission:lab_catalog,add');
    Route::post('catalog/store',       [LabController::class, 'saveTest'])->name('catalog.store')->middleware('permission:lab_catalog,add');
    Route::get('catalog/{id}/edit',    [LabController::class, 'testForm'])->name('catalog.edit')->middleware('permission:lab_catalog,edit');
    Route::post('catalog/{id}/update', [LabController::class, 'saveTest'])->name('catalog.update')->middleware('permission:lab_catalog,edit');
    Route::get('catalog/{id}/delete',  [LabController::class, 'deleteTest'])->name('catalog.delete')->middleware('permission:lab_catalog,delete');
});
