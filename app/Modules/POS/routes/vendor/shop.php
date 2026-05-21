<?php

use App\Modules\POS\Controllers\Vendor\POSController;

Route::post('variant_price', [POSController::class, 'variant_price'])->name('variant_price');
Route::group(['middleware' => ['planwise:pos']], function () {
    Route::get('/',                    [POSController::class, 'index'])->name('index');
    Route::get('quick-view',           [POSController::class, 'quick_view'])->name('quick-view');
    Route::get('quick-view-cart-item', [POSController::class, 'quick_view_card_item'])->name('quick-view-cart-item');
    Route::post('add-to-cart',         [POSController::class, 'addToCart'])->name('add-to-cart');
    Route::post('add-delivery-info',   [POSController::class, 'addDeliveryInfo'])->name('add-delivery-info');
    Route::post('remove-from-cart',    [POSController::class, 'removeFromCart'])->name('remove-from-cart');
    Route::post('cart-items',          [POSController::class, 'cart_items'])->name('cart_items');
    Route::post('update-quantity',     [POSController::class, 'updateQuantity'])->name('updateQuantity');
    Route::post('empty-cart',          [POSController::class, 'emptyCart'])->name('emptyCart');
    Route::post('tax',                 [POSController::class, 'update_tax'])->name('tax');
    Route::post('paid',                [POSController::class, 'update_paid'])->name('paid');
    Route::post('discount',            [POSController::class, 'update_discount'])->name('discount');
    Route::get('customers',            [POSController::class, 'get_customers'])->name('customers');
    Route::post('order',               [POSController::class, 'place_order'])->name('order');
    Route::post('customer-store',      [POSController::class, 'customer_store'])->name('customer-store');
    Route::get('data',                 [POSController::class, 'extra_charge'])->name('extra_charge');
});
