<?php

use App\Http\Controllers\Vendor\InventoryGatepassController;
use App\Http\Controllers\Vendor\InventoryOrderController;
use App\Http\Controllers\Vendor\InventoryPurchaseController;
use App\Http\Controllers\Vendor\InventoryReportController;
use App\Http\Controllers\Vendor\InventoryStockController;
use App\Modules\HMIS\Controllers\Vendor\InventoryController;

Route::post('inventory/get-item-info', [InventoryController::class, 'get_item_info'])->name('inventory.get-item-info');

Route::group(['prefix' => 'inventory', 'as' => 'inventory.'], function () {
    Route::get('settings',       [InventoryController::class, 'settings'])->name('settings')->middleware('permission:inventory,settings');
    Route::post('settings-save', [InventoryController::class, 'settings_save'])->name('settings-save')->middleware('permission:inventory,settings_save');
    Route::get('dashboard',      [InventoryController::class, 'dashboard'])->name('dashboard')->middleware('permission:inventory,dashboard');
    Route::get('/',              [InventoryController::class, 'inventory_management'])->name('index');
    Route::post('get_my_fee_amount',     [InventoryController::class, 'get_my_fee_amount'])->name('get_my_fee_amount');
    Route::post('get_item_details',      [InventoryController::class, 'get_item_details'])->name('get_item_details');
    Route::post('get_variation_details', [InventoryController::class, 'get_variation_details'])->name('get_variation_details');
    Route::get('entries',        [InventoryController::class, 'entries'])->name('entries');
    Route::post('save-item',     [InventoryController::class, 'save_item'])->name('item.save')->middleware('permission:inventory_item,add');
    Route::post('update-item',   [InventoryController::class, 'update_item'])->name('item.update')->middleware('permission:inventory_item,edit');
    Route::get('edit-item/{id}', [InventoryController::class, 'edit_item'])->name('edit-item')->middleware('permission:inventory_item,edit');
    Route::post('variation-store',[InventoryController::class, 'variation_store'])->name('item.variation-store');
    Route::post('save-entry',    [InventoryController::class, 'save_entry'])->name('entry.save')->middleware('permission:inventory_item_entry,add');
    Route::post('save-entry-pdf',[InventoryController::class, 'save_entry_pdf'])->name('entry.save-pdf');
    Route::get('item-images',    [InventoryController::class, 'item_images'])->name('item-images');
    Route::post('item-images-store', [InventoryController::class, 'item_images_store'])->name('item-images-store');
    Route::post('import',        [InventoryController::class, 'items_import'])->name('import')->middleware('permission:inventory_item,import');
    Route::get('storage-spaces', [InventoryController::class, 'storage_spaces'])->name('storage-spaces');

    Route::group(['prefix' => 'entry', 'as' => 'entry.'], function () {
        Route::get('export-selected', [InventoryController::class, 'entry_export_selected'])->name('export-selected')->middleware('permission:inventory_item_entry,export');
        Route::post('bulk-delete',    [InventoryController::class, 'entry_bulk_delete'])->name('bulk-delete')->middleware('permission:inventory_item_entry,delete');
        Route::post('import',         [InventoryController::class, 'entry_import'])->name('import')->middleware('permission:inventory_item_entry,import');
        Route::get('export',          [InventoryController::class, 'entry_export'])->name('export')->middleware('permission:inventory_item_entry,export');
    });

    Route::group(['prefix' => 'item', 'as' => 'item.'], function () {
        Route::get('scan-barcode',  [InventoryController::class, 'scan_barcode'])->name('scan-barcode');
        Route::post('fetch-by-sku', [InventoryController::class, 'fetch_item_by_sku'])->name('fetch-by-sku');
        Route::post('update-variant-combination',    [InventoryController::class, 'update_variant_combination'])->name('update-variant-combination');
        Route::get('remove-image/{item_id}/{photo}', [InventoryController::class, 'remove_item_image'])->name('remove-image')->middleware('permission:inventory_item,edit');
        Route::get('show_on_website/{id}/{status}',  [InventoryController::class, 'show_on_website'])->name('show_on_website')->middleware('permission:inventory_item,show_on_website');
        Route::get('delete/{id}',    [InventoryController::class, 'item_delete'])->name('delete')->middleware('permission:inventory_item,delete');
        Route::post('bulk-delete',   [InventoryController::class, 'item_bulk_delete'])->name('bulk-delete')->middleware('permission:inventory_item,delete');
        Route::get('export',         [InventoryController::class, 'item_export'])->name('export')->middleware('permission:inventory_item,export');
        Route::get('export-selected',[InventoryController::class, 'item_export_selected'])->name('export-selected')->middleware('permission:inventory_item,export');
        Route::get('detail/{id}',    [InventoryController::class, 'item_detail'])->name('detail')->middleware('permission:inventory_item,view');
        Route::post('variant-combination', [InventoryController::class, 'variant_combination'])->name('variant-combination');
        Route::get('print/{item_id}/{type}', [InventoryController::class, 'print'])->name('print');
    });

    Route::group(['prefix' => 'storage-unit', 'as' => 'storage-unit.'], function () {
        Route::get('get-stands',   [InventoryController::class, 'get_stands'])->name('get-stands');
        Route::post('store',       [InventoryController::class, 'storage_spaces_store'])->name('store')->middleware('permission:inventory_storage_units,add');
        Route::post('update',      [InventoryController::class, 'storage_spaces_update'])->name('update')->middleware('permission:inventory_storage_units,edit');
        Route::post('delete/{id}', [InventoryController::class, 'storage_spaces_delete'])->name('delete')->middleware('permission:inventory_storage_units,delete');
    });

    Route::group(['prefix' => 'gatepass', 'as' => 'gatepass.'], function () {
        Route::get('{tab}',         [InventoryGatepassController::class, 'gatepass_list'])->name('list');
        Route::post('store',        [InventoryGatepassController::class, 'store'])->name('store');
        Route::get('return/{id}',   [InventoryGatepassController::class, 'return'])->name('return');
        Route::post('return-store', [InventoryGatepassController::class, 'return_store'])->name('return-store');
    });

    Route::group(['prefix' => 'purchase', 'as' => 'purchase.'], function () {
        Route::post('items-in-invoice', [InventoryPurchaseController::class, 'items_in_invoice'])->name('items-in-invoice')->middleware('permission:inventory_purchase_return,add');
        Route::get('orders',            [InventoryPurchaseController::class, 'purchase_orders'])->name('orders')->middleware('permission:inventory_purchase_order,list');
        Route::get('export_orders',     [InventoryPurchaseController::class, 'export_purchase_orders'])->name('export-orders')->middleware('permission:inventory_purchase_order,export');
        Route::post('order-place',      [InventoryPurchaseController::class, 'order_place'])->name('order-place')->middleware('permission:inventory_purchase_order,add');
        Route::get('return',            [InventoryPurchaseController::class, 'return'])->name('return')->middleware('permission:inventory_purchase_return,add');
        Route::post('return-store',     [InventoryPurchaseController::class, 'return_store'])->name('return-store')->middleware('permission:inventory_purchase_return,add');
    });

    Route::group(['prefix' => 'stock', 'as' => 'stock.'], function () {
        Route::get('stock-in-out', [InventoryStockController::class, 'stock_in_out'])->name('stock-in-out')->middleware('permission:inventory_stock_in_out,list');
    });

    Route::group(['prefix' => 'sale', 'as' => 'sale.'], function () {
        Route::get('orders',                     [InventoryOrderController::class, 'sale_orders'])->name('orders');
        Route::get('order-status/{id}/{status}', [InventoryOrderController::class, 'sale_order_status'])->name('order-status')->middleware('permission:inventory_sale_order,status_change');
        Route::get('order-export/{return?}',     [InventoryOrderController::class, 'sale_order_export'])->name('order-export');
        Route::get('orders-return',              [InventoryOrderController::class, 'order_return'])->name('orders-return')->middleware('permission:inventory_sale_return,add');
        Route::post('order-details-fetch',       [InventoryOrderController::class, 'order_details_fetch'])->name('order-details-fetch');
    });

    Route::group(['prefix' => 'report', 'as' => 'report.'], function () {
        Route::get('gst/{export?}/{file_type?}',             [InventoryReportController::class, 'gst'])->name('gst');
        Route::get('sale/{export?}/{file_type?}',            [InventoryReportController::class, 'sale'])->name('sale');
        Route::get('profit-and-loss/{export?}/{file_type?}', [InventoryReportController::class, 'profit_and_loss'])->name('profit-and-loss');
        Route::get('purchase/{export?}/{file_type?}',        [InventoryReportController::class, 'purchase'])->name('purchase');
        Route::get('stock/{export?}/{file_type?}',           [InventoryReportController::class, 'stock'])->name('stock');
        Route::get('batch-expiry',                           [InventoryReportController::class, 'batchExpiry'])->name('batch-expiry');
    });
});
