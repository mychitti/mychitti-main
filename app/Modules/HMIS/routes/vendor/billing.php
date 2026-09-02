<?php

use App\Modules\HMIS\Controllers\Vendor\HospitalBillController;

Route::group(['prefix' => 'hospital-bill', 'as' => 'hospital-bill.'], function () {
    Route::get('ipd/{id}',       [HospitalBillController::class, 'createForIPD'])->name('create-ipd')->middleware('permission:ipd_admission,generate_bill');
    Route::get('opd/{id}',       [HospitalBillController::class, 'createForOPD'])->name('create-opd')->middleware('permission:opd_register,generate_bill');
    // The two GETs above are gated but this POST was not, so the screen they lead to was
    // permissioned while the act of raising the bill — and taking money against it — was open to
    // anyone who could reach the URL. Same pair of permissions, ORed, because one form serves both
    // the OPD and IPD screens.
    Route::post('store',         [HospitalBillController::class, 'store'])->name('store')->middleware('permission:opd_register,generate_bill,ipd_admission,generate_bill');
    Route::get('inventory-search',[HospitalBillController::class, 'searchInventory'])->name('inventory-search')->middleware('permission:opd_register,generate_bill,ipd_admission,generate_bill');
    // Reading a raised bill, on its own permission — a hospital role that bills patients should
    // not need billing,view, which opens the store's entire invoice book.
    Route::get('view/{id}',      [HospitalBillController::class, 'view'])->name('view')->middleware('permission:hospital_bill,view');
});
