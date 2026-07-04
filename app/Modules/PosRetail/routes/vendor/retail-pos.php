<?php

use App\Modules\PosRetail\Controllers\Vendor\InventoryOfferController;
use App\Modules\PosRetail\Controllers\Vendor\RetailPosController;
use Illuminate\Support\Facades\Route;

// Retail POS  →  URL: /retail-pos/…
Route::prefix('retail-pos')->as('retail-pos.')->group(function () {

    // Inventory item offers (Buy X Get Y, discounts, bundles)
    Route::prefix('offer')->as('offer.')->group(function () {
        Route::get('/',                 [InventoryOfferController::class, 'index'])->name('index');
        Route::get('create/{item_id?}', [InventoryOfferController::class, 'create'])->name('create');
        Route::get('search-items',      [InventoryOfferController::class, 'searchItems'])->name('search-items');
        Route::post('store',            [InventoryOfferController::class, 'store'])->name('store');
        Route::get('delete/{id}',       [InventoryOfferController::class, 'delete'])->name('delete');
    });
    Route::get('dashboard',    [RetailPosController::class, 'dashboard'])->name('dashboard')->middleware('permission:pos_bills,view');
    Route::get('/',            [RetailPosController::class, 'index'])->name('index')->middleware('permission:pos_billing,create');
    Route::get('products',     [RetailPosController::class, 'products'])->name('products')->middleware('permission:pos_billing,create');
    Route::get('customers',    [RetailPosController::class, 'customers'])->name('customers')->middleware('permission:pos_billing,create');
    Route::get('offers-all',   [RetailPosController::class, 'offersAll'])->name('offers.all')->middleware('permission:pos_billing,create');
    Route::post('offers-match',[RetailPosController::class, 'offersMatch'])->name('offers.match')->middleware('permission:pos_billing,create');
    Route::post('offers-apply-code',[RetailPosController::class, 'offersApplyCode'])->name('offers.apply-code')->middleware('permission:pos_billing,create');
    Route::post('coupons',      [RetailPosController::class, 'couponsForCart'])->name('coupons')->middleware('permission:pos_billing,create');
    Route::post('coupon-apply', [RetailPosController::class, 'applyCoupon'])->name('coupon-apply')->middleware('permission:pos_billing,create');
    Route::post('finalize',    [RetailPosController::class, 'finalize'])->name('finalize')->middleware('permission:pos_billing,create');

    // Hold & Resume — actions of New Sale
    Route::post('hold',          [RetailPosController::class, 'holdBill'])->name('hold')->middleware('permission:pos_billing,hold');
    Route::get('held',           [RetailPosController::class, 'heldBills'])->name('held')->middleware('permission:pos_billing,resume');
    Route::get('resume/{id}',    [RetailPosController::class, 'resumeBill'])->name('resume')->middleware('permission:pos_billing,resume');
    Route::post('held/{id}/delete', [RetailPosController::class, 'deleteHeld'])->name('held.delete')->middleware('permission:pos_billing,hold');
    Route::get('today',        [RetailPosController::class, 'todaysBills'])->name('today')->middleware('permission:pos_bills,view');
    Route::post('void/{id}',   [RetailPosController::class, 'voidBill'])->name('void')->middleware('permission:pos_bills,void');
    Route::get('thermal/{id}', [RetailPosController::class, 'thermal'])->name('thermal')->middleware('permission:pos_bills,print');
    Route::get('email/{id}',   [RetailPosController::class, 'emailInvoice'])->name('email')->middleware('permission:pos_bills,print');
    Route::get('whatsapp/{id}',[RetailPosController::class, 'whatsappInvoice'])->name('whatsapp')->middleware('permission:pos_bills,print');

    // GST report
    Route::get('gst-report',   [RetailPosController::class, 'gstReport'])->name('gst-report')->middleware('permission:pos_gst_report,view');

    // Branches & Counters
    Route::get('terminals',    [RetailPosController::class, 'terminals'])->name('terminals')->middleware('permission:pos_branch,view,pos_branch,create,pos_branch,delete,pos_counter,create,pos_counter,delete');
    Route::post('terminals',   [RetailPosController::class, 'terminalStore'])->name('terminals.store')->middleware('permission:pos_counter,create');
    Route::post('terminals/{id}/staff', [RetailPosController::class, 'terminalAssignStaff'])->name('terminals.staff')->middleware('permission:pos_counter,create');
    Route::post('terminals/{id}/roster', [RetailPosController::class, 'terminalRoster'])->name('terminals.roster')->middleware('permission:pos_counter,create');
    Route::post('terminals/{id}/delete', [RetailPosController::class, 'terminalDelete'])->name('terminals.delete')->middleware('permission:pos_counter,delete');

    // Cash Flow — shift-to-shift cash request, handover & approval
    Route::get('cash-flow',               [RetailPosController::class, 'cashFlow'])->name('cash-flow')->middleware('permission:pos_cash,view');
    Route::get('cash-flow/new',           [RetailPosController::class, 'cashRequestForm'])->name('cash-flow.new')->middleware('permission:pos_cash,view');
    Route::post('cash-flow/save',         [RetailPosController::class, 'cashRequestSave'])->name('cash-flow.save')->middleware('permission:pos_cash,view');
    Route::get('cash-flow/{id}',          [RetailPosController::class, 'cashRequestForm'])->name('cash-flow.show')->whereNumber('id')->middleware('permission:pos_cash,view');
    Route::post('cash-flow/{id}/action',  [RetailPosController::class, 'cashRequestAction'])->name('cash-flow.action')->whereNumber('id')->middleware('permission:pos_cash,view');
    Route::get('cash-flow/{id}/slip',     [RetailPosController::class, 'cashSlip'])->name('cash-flow.slip')->whereNumber('id')->middleware('permission:pos_cash,view');
    Route::post('branches',    [RetailPosController::class, 'branchStore'])->name('branches.store')->middleware('permission:pos_branch,create');
    Route::post('branches/{id}/delete', [RetailPosController::class, 'branchDelete'])->name('branches.delete')->middleware('permission:pos_branch,delete');
    Route::post('branches/{id}/manager', [RetailPosController::class, 'branchAssignManager'])->name('branches.manager')->middleware('permission:pos_branch,create');
    Route::get('settings',     [RetailPosController::class, 'settings'])->name('settings')->middleware('permission:pos_billing,settings');
    Route::post('upi-id',      [RetailPosController::class, 'saveUpi'])->name('upi.save')->middleware('permission:pos_billing,settings');
    Route::post('ui-template', [RetailPosController::class, 'saveUiTemplate'])->name('ui-template.save')->middleware('permission:pos_billing,settings');
    Route::post('receipt-template', [RetailPosController::class, 'saveReceiptTemplate'])->name('receipt-template.save')->middleware('permission:pos_billing,settings');
    Route::post('default-branch', [RetailPosController::class, 'saveDefaultBranch'])->name('default-branch')->middleware('permission:pos_billing,settings');

    // Branch stock
    Route::get('branch-stock', [RetailPosController::class, 'branchStock'])->name('branch-stock')->middleware('permission:pos_branch_stock,view');
    Route::post('branch-stock',[RetailPosController::class, 'branchStockSave'])->name('branch-stock.save')->middleware('permission:pos_branch_stock,edit');

    // Stock Transfer Gatepass (main store → branch)
    Route::get('gatepass',            [RetailPosController::class, 'gatepass'])->name('gatepass')->middleware('permission:pos_gatepass,view');
    Route::post('gatepass',           [RetailPosController::class, 'gatepassStore'])->name('gatepass.store')->middleware('permission:pos_gatepass,create');
    Route::post('gatepass/delete',    [RetailPosController::class, 'gatepassDelete'])->name('gatepass.delete')->middleware('permission:pos_gatepass,delete');
    Route::get('gatepass/{id}/print', [RetailPosController::class, 'gatepassPrint'])->name('gatepass.print')->middleware('permission:pos_gatepass,view');

    // Damaged / Theft stock write-off
    Route::get('writeoff',            [RetailPosController::class, 'writeoff'])->name('writeoff')->middleware('permission:pos_writeoff,view');
    Route::get('writeoff/items',      [RetailPosController::class, 'writeoffItems'])->name('writeoff.items')->middleware('permission:pos_writeoff,view');
    Route::post('writeoff',           [RetailPosController::class, 'writeoffStore'])->name('writeoff.store')->middleware('permission:pos_writeoff,create');
    Route::post('writeoff/{id}/decide',[RetailPosController::class, 'writeoffDecide'])->name('writeoff.decide')->middleware('permission:pos_writeoff,view');
    Route::post('writeoff/{id}/delete',[RetailPosController::class, 'writeoffDelete'])->name('writeoff.delete')->middleware('permission:pos_writeoff,delete');
});
