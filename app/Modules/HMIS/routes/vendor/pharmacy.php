<?php

use App\Modules\HMIS\Controllers\Vendor\BasicPharmacyController;

// Basic Pharmacy — FREE (no hospital_manage plan required). Shares inventory_items with the
// premium Inventory module; items added here are visible in paid Inventory and vice-versa.
Route::group(['prefix' => 'pharmacy', 'as' => 'pharmacy.'], function () {
    Route::get('medicines',                  [BasicPharmacyController::class, 'medicines'])->name('medicines')->middleware('permission:pharmacy,view');
    Route::post('medicines/save',            [BasicPharmacyController::class, 'saveMedicine'])->name('medicines.save')->middleware('permission:pharmacy,add');
    Route::post('medicines/{id}/update',     [BasicPharmacyController::class, 'updateMedicine'])->name('medicines.update')->middleware('permission:pharmacy,edit');
    Route::post('medicines/{id}/add-stock',  [BasicPharmacyController::class, 'addStock'])->name('medicines.add-stock')->middleware('permission:pharmacy,edit');
    Route::get('medicines/{id}/delete',      [BasicPharmacyController::class, 'deleteMedicine'])->name('medicines.delete')->middleware('permission:pharmacy,delete');

    // Import / Export medicines
    Route::get('medicines/export',           [BasicPharmacyController::class, 'exportMedicines'])->name('medicines.export')->middleware('permission:pharmacy,view');
    Route::post('medicines/import',          [BasicPharmacyController::class, 'importMedicines'])->name('medicines.import')->middleware('permission:pharmacy,add');

    // Sale Orders (free pharmacy view)
    Route::get('sale-orders',                [BasicPharmacyController::class, 'saleOrders'])->name('sale-orders')->middleware('permission:pharmacy,view');

    // Banned / Blocked items (flag on the inventory item)
    Route::get('banned-items',               [BasicPharmacyController::class, 'bannedItems'])->name('banned-items')->middleware('permission:pharmacy,view');
    Route::post('banned-items/save',         [BasicPharmacyController::class, 'saveBannedItem'])->name('banned-items.save')->middleware('permission:pharmacy,edit');
    Route::get('banned-items/{id}/delete',   [BasicPharmacyController::class, 'deleteBannedItem'])->name('banned-items.delete')->middleware('permission:pharmacy,edit');

    // Walk-in Sale
    Route::get('walkin',        [BasicPharmacyController::class, 'walkin'])->name('walkin')->middleware('permission:pharmacy,dispense');
    Route::post('walkin/store', [BasicPharmacyController::class, 'walkinStore'])->name('walkin.store')->middleware('permission:pharmacy,dispense');
});
