<?php

use App\Modules\HMIS\Controllers\Vendor\HospitalBillController;

Route::group(['prefix' => 'hospital-bill', 'as' => 'hospital-bill.'], function () {
    Route::get('ipd/{id}',       [HospitalBillController::class, 'createForIPD'])->name('create-ipd')->middleware('permission:ipd_admission,generate_bill');
    Route::get('opd/{id}',       [HospitalBillController::class, 'createForOPD'])->name('create-opd')->middleware('permission:opd_register,generate_bill');
    Route::post('store',         [HospitalBillController::class, 'store'])->name('store');
    Route::get('inventory-search',[HospitalBillController::class, 'searchInventory'])->name('inventory-search');
});
