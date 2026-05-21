<?php

use App\Modules\POS\Controllers\Vendor\SalespointController;

// Dashboard & calendar
Route::get('dashboard',      [SalespointController::class, 'dashboard'])->name('dashboard')->middleware('permission:pos,dashboard');
Route::post('new-bank-account', 'BusinessSettingsController@new_bank_account')->name('new-bank-account')->middleware('permission:pos,settings');
Route::get('delete-account/{id}', 'BusinessSettingsController@delete_account')->name('delete-account')->middleware('permission:pos,settings');
Route::get('calendar',       [SalespointController::class, 'calendar'])->name('calendar');

// Tokens
Route::get('token/{id?}',     [SalespointController::class, 'token'])->name('token')->middleware('permission:pos_token,generate');
Route::get('token-list',      [SalespointController::class, 'token_list'])->name('token.list')->middleware('permission:pos_token,list');
Route::get('token-export',    [SalespointController::class, 'token_export'])->name('token.export')->middleware('permission:pos_token,export');
Route::get('convert-to-bill/{id}', [SalespointController::class, 'convert_to_bill'])->name('token.convert-to-bill')->middleware('permission:pos_token,convert to invoice');
Route::get('token-delete/{id}', [SalespointController::class, 'token_delete'])->name('token.delete')->middleware('permission:pos_token,delete');
Route::get('token-cancel/{id}', [SalespointController::class, 'token_cancel'])->name('token.cancel')->middleware('permission:pos_token,cancel');
Route::post('token-generate', [SalespointController::class, 'token_generate'])->name('token-generate')->middleware('permission:pos_token,generate');
Route::get('mark-paid/{id}',  [SalespointController::class, 'mark_paid'])->name('token.mark-paid')->middleware('permission:pos_token,mark_paid');
Route::post('payment-method', [SalespointController::class, 'payment_method'])->name('token.payment-method')->middleware('permission:pos_token,edit');

// Dine-in
Route::post('dine-in/open-table',       [SalespointController::class, 'openTable'])->name('dinein.open');
Route::get('pos/dine-in/table-state',   [SalespointController::class, 'tableState'])->name('dinein.table-state');
Route::post('pos/dine-in/update',       [SalespointController::class, 'updateDineOrder'])->name('dinein.update');

// Items
Route::post('items-import',             [SalespointController::class, 'items_import'])->name('items_import');
Route::get('items/{action?}',           [SalespointController::class, 'items'])->name('items');
Route::post('items-save',               [SalespointController::class, 'items_save'])->name('items.save')->middleware('permission:pos_items,add');
Route::get('item-remove/{item_id}/{branch_id}', [SalespointController::class, 'item_remove'])->name('item.remove')->middleware('permission:pos_items,delete');

// Report
Route::post('get-branch-item-data',     [SalespointController::class, 'getBranchData'])->name('product-branch-data');
Route::get('report/{action?}',          [SalespointController::class, 'report'])->name('report')->middleware('permission:pos,report');
Route::get('calendar-export',           [SalespointController::class, 'calendar_export'])->name('calendar-export')->middleware('permission:pos,report');
