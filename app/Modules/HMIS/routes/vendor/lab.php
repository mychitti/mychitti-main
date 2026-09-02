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
    Route::get('history/export', [LabController::class, 'historyExport'])->name('history.export')->middleware('permission:lab_history,view');

    // The lab's own day, in and out: a batch of orders raised from a list, an analyser's values
    // read back against the samples that produced them. Exports are gated on view, imports on the
    // permission that already governs doing the same thing by hand — ordering a test, entering a
    // result — because a file should not be a way round a permission.
    Route::get('worklist/export',   [LabController::class, 'worklistExport'])->name('worklist.export')->middleware('permission:lab_worklist,view');
    Route::get('orders/template',   [LabController::class, 'ordersTemplate'])->name('orders.template')->middleware('permission:lab_order,view');
    Route::post('orders/import',    [LabController::class, 'ordersImport'])->name('orders.import')->middleware('permission:lab_order,add');
    Route::get('results/export',    [LabController::class, 'resultsExport'])->name('results.export')->middleware('permission:lab_result,view');
    Route::get('results/template',  [LabController::class, 'resultsTemplate'])->name('results.template')->middleware('permission:lab_result,view');
    Route::post('results/import',   [LabController::class, 'resultsImport'])->name('results.import')->middleware('permission:lab_result,edit');

    // Billing
    Route::get('billing',              [LabController::class, 'billing'])->name('billing')->middleware('permission:lab_billing,view');
    Route::post('orders/{id}/invoice', [LabController::class, 'generateInvoice'])->name('orders.invoice')->middleware('permission:lab_billing,add');
    Route::get('invoices/{id}',        [LabController::class, 'invoice'])->name('invoices.view')->middleware('permission:lab_billing,view');

    // Test Catalog (management)
    Route::get('catalog',              [LabController::class, 'catalog'])->name('catalog')->middleware('permission:lab_catalog,view');
    // Bulk load and bulk read-back of the test catalog. Export and template are gated on view —
    // reading out what you already have is not a change — while the import writes and is gated
    // on add, the same permission as creating a test by hand.
    Route::get('catalog/export',       [LabController::class, 'catalogExport'])->name('catalog.export')->middleware('permission:lab_catalog,view');
    Route::get('catalog/template',     [LabController::class, 'catalogTemplate'])->name('catalog.template')->middleware('permission:lab_catalog,view');
    Route::post('catalog/import',      [LabController::class, 'catalogImport'])->name('catalog.import')->middleware('permission:lab_catalog,add');
    Route::get('catalog/create',       [LabController::class, 'testForm'])->name('catalog.create')->middleware('permission:lab_catalog,add');
    Route::post('catalog/store',       [LabController::class, 'saveTest'])->name('catalog.store')->middleware('permission:lab_catalog,add');
    Route::get('catalog/{id}/edit',    [LabController::class, 'testForm'])->name('catalog.edit')->middleware('permission:lab_catalog,edit');
    Route::post('catalog/{id}/update', [LabController::class, 'saveTest'])->name('catalog.update')->middleware('permission:lab_catalog,edit');
    Route::get('catalog/{id}/delete',  [LabController::class, 'deleteTest'])->name('catalog.delete')->middleware('permission:lab_catalog,delete');
});
