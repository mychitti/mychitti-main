<?php

use App\Modules\Laundry\Controllers\Vendor\LaundryController;

// Dashboard
Route::get('dashboard', [LaundryController::class, 'dashboard'])->name('dashboard');

// Walk-in orders
Route::get('orders',                   [LaundryController::class, 'orders'])->name('orders');
Route::get('orders/create',            [LaundryController::class, 'order_create'])->name('orders.create');
Route::post('orders/store',            [LaundryController::class, 'order_store'])->name('orders.store');
Route::get('orders/{id}',              [LaundryController::class, 'order_show'])->name('orders.show');
Route::post('orders/status',           [LaundryController::class, 'order_status'])->name('orders.status');
Route::get('orders/{id}/receipt',      [LaundryController::class, 'order_receipt'])->name('orders.receipt');
Route::post('orders/{id}/invoice',     [LaundryController::class, 'order_invoice'])->name('orders.invoice');

// Challans
Route::get('challans',                 [LaundryController::class, 'challans'])->name('challans');
Route::get('challans/create',          [LaundryController::class, 'challan_create'])->name('challans.create');
Route::post('challans/store',          [LaundryController::class, 'challan_store'])->name('challans.store');
Route::get('challans/{id}',            [LaundryController::class, 'challan_show'])->name('challans.show');
Route::get('challans/{id}/receive',    [LaundryController::class, 'challan_receive'])->name('challans.receive');
Route::post('challans/receive-update', [LaundryController::class, 'challan_receive_update'])->name('challans.receive-update');
Route::get('challans/{id}/print',      [LaundryController::class, 'challan_print'])->name('challans.print');
Route::post('challans/{id}/invoice',   [LaundryController::class, 'challan_invoice'])->name('challans.invoice');

// Monthly register
Route::get('register',                 [LaundryController::class, 'monthly_register'])->name('register');
Route::get('register/export',          [LaundryController::class, 'monthly_register_export'])->name('register.export');
Route::get('register/billing',         [LaundryController::class, 'monthly_billing_form'])->name('register.billing');
Route::post('register/billing/store',  [LaundryController::class, 'monthly_billing_store'])->name('register.billing.store');
