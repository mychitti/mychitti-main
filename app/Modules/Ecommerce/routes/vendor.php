<?php

use App\Modules\Ecommerce\Controllers\Vendor\AddOnController;
use App\Modules\Ecommerce\Controllers\Vendor\BannerController;
use App\Modules\Ecommerce\Controllers\Vendor\CampaignController;
use App\Modules\Ecommerce\Controllers\Vendor\CategoryController;
use App\Modules\Ecommerce\Controllers\Vendor\CouponController;
use App\Modules\Ecommerce\Controllers\Vendor\ItemController;
use App\Modules\Ecommerce\Controllers\Vendor\OrderController;
use Illuminate\Support\Facades\Route;

// CATEGORY ---------------------------------------------------------------
Route::group(['prefix' => 'category', 'as' => 'category.', 'middleware' => ['module:item']], function () {
    Route::post('update-selected', [CategoryController::class, 'update_selected'])->name('update-selected');
    Route::get('selected',         [CategoryController::class, 'selected'])->name('selected');
    Route::get('get-all',          [CategoryController::class, 'get_all'])->name('get-all');
    Route::get('list',             [CategoryController::class, 'index'])->name('add');
    Route::get('sub-category-list',[CategoryController::class, 'sub_index'])->name('add-sub-category');
    Route::get('export-categories',    [CategoryController::class, 'export_categories'])->name('export-categories');
    Route::get('export-sub-categories',[CategoryController::class, 'export_sub_categories'])->name('export-sub-categories');
});

// ITEMS ------------------------------------------------------------------
Route::group(['prefix' => 'item', 'as' => 'item.', 'middleware' => ['module:item']], function () {
    Route::get('add-new',            [ItemController::class, 'index'])->name('add-new');
    Route::get('select',             [ItemController::class, 'select_view'])->name('service_select');
    Route::post('save-services',     [ItemController::class, 'service_save'])->name('service_save');
    Route::get('service-requests',   [ItemController::class, 'service_request_list'])->name('service_request_list');
    Route::post('variant-combination',       [ItemController::class, 'variant_combination'])->name('variant-combination');
    Route::post('update-variant-combination',[ItemController::class, 'update_variant_combination'])->name('update-variant-combination');
    Route::post('store',             [ItemController::class, 'store'])->name('store');
    Route::get('edit/{id}',          [ItemController::class, 'edit'])->name('edit');
    Route::post('update/{id}',       [ItemController::class, 'update'])->name('update');
    Route::get('list',               [ItemController::class, 'list'])->name('list');
    Route::delete('delete/{id}',     [ItemController::class, 'delete'])->name('delete');
    Route::get('status/{id}/{status}',[ItemController::class, 'status'])->name('status');
    Route::post('search',            [ItemController::class, 'search'])->name('search');
    Route::get('view/{id}',          [ItemController::class, 'view'])->name('view');
    Route::get('remove-image',       [ItemController::class, 'remove_image'])->name('remove-image');
    Route::get('get-categories',     [ItemController::class, 'get_categories'])->name('get-categories');
    Route::get('recommended/{id}/{status}', [ItemController::class, 'recommended'])->name('recommended');
    Route::get('pending/item/list',  [ItemController::class, 'pending_item_list'])->name('pending_item_list');
    Route::get('requested/item/view/{id}', [ItemController::class, 'requested_item_view'])->name('requested_item_view');
    Route::get('product-gallery',    [ItemController::class, 'product_gallery'])->name('product_gallery');
    Route::get('get-variations',     [ItemController::class, 'get_variations'])->name('get-variations');
    Route::get('get-price-variations',[ItemController::class, 'get_price_variations'])->name('get-price-variations');
    Route::get('stock-limit-list',   [ItemController::class, 'stock_limit_list'])->name('stock-limit-list');
    Route::post('stock-update',      [ItemController::class, 'stock_update'])->name('stock-update');
    Route::get('price-update-list',  [ItemController::class, 'price_update_list'])->name('price-update-list');
    Route::post('price-update',      [ItemController::class, 'price_update'])->name('price-update');
    Route::post('food-variation-generate', [ItemController::class, 'food_variation_generator'])->name('food-variation-generate');
    Route::post('variation-generate',[ItemController::class, 'variation_generator'])->name('variation-generate');
    Route::get('bulk-import',        [ItemController::class, 'bulk_import_index'])->name('bulk-import');
    Route::post('bulk-import',       [ItemController::class, 'bulk_import_data']);
    Route::get('bulk-export',        [ItemController::class, 'bulk_export_index'])->name('bulk-export-index');
    Route::post('bulk-export',       [ItemController::class, 'bulk_export_data'])->name('bulk-export');
    Route::get('flash-sale',         [ItemController::class, 'flash_sale'])->name('flash_sale');
});

// CAMPAIGNS --------------------------------------------------------------
Route::group(['prefix' => 'campaign', 'as' => 'campaign.', 'middleware' => ['module:campaign']], function () {
    Route::get('list',               [CampaignController::class, 'list'])->name('list');
    Route::get('item/list',          [CampaignController::class, 'itemlist'])->name('itemlist');
    Route::get('remove-store/{campaign}/{store}', [CampaignController::class, 'remove_store'])->name('remove-store');
    Route::get('add-store/{campaign}/{store}',    [CampaignController::class, 'addstore'])->name('add-store');
    Route::post('search-item',       [CampaignController::class, 'searchItem'])->name('searchItem');
});

// COUPONS ----------------------------------------------------------------
Route::group(['prefix' => 'coupon', 'as' => 'coupon.', 'middleware' => ['module:coupon']], function () {
    Route::get('add-new',            [CouponController::class, 'add_new'])->name('add-new');
    Route::post('store',             [CouponController::class, 'store'])->name('store');
    Route::get('update/{id}',        [CouponController::class, 'edit'])->name('update');
    Route::post('update/{id}',       [CouponController::class, 'update']);
    Route::get('status/{id}/{status}',[CouponController::class, 'status'])->name('status');
    Route::delete('delete/{id}',     [CouponController::class, 'delete'])->name('delete');
});

// ADD-ONS ----------------------------------------------------------------
Route::group(['prefix' => 'addon', 'as' => 'addon.', 'middleware' => ['module:addon']], function () {
    Route::get('add-new',            [AddOnController::class, 'index'])->name('add-new');
    Route::post('store',             [AddOnController::class, 'store'])->name('store');
    Route::get('edit/{id}',          [AddOnController::class, 'edit'])->name('edit');
    Route::post('update/{id}',       [AddOnController::class, 'update'])->name('update');
    Route::delete('delete/{id}',     [AddOnController::class, 'delete'])->name('delete');
});

// ORDERS -----------------------------------------------------------------
Route::group(['prefix' => 'order', 'as' => 'order.', 'middleware' => ['module:order']], function () {
    Route::get('list/{status}',      [OrderController::class, 'list'])->name('list');
    Route::put('status-update/{id}', [OrderController::class, 'status'])->name('status-update');
    Route::post('add-to-cart',       [OrderController::class, 'add_to_cart'])->name('add-to-cart');
    Route::post('remove-from-cart',  [OrderController::class, 'remove_from_cart'])->name('remove-from-cart');
    Route::get('update/{order}',     [OrderController::class, 'update'])->name('update');
    Route::get('edit-order/{order}', [OrderController::class, 'edit'])->name('edit');
    Route::get('details/{id}',       [OrderController::class, 'details'])->name('details');
    Route::get('status',             [OrderController::class, 'status'])->name('status');
    Route::get('quick-view',         [OrderController::class, 'quick_view'])->name('quick-view');
    Route::get('quick-view-cart-item',[OrderController::class, 'quick_view_cart_item'])->name('quick-view-cart-item');
    Route::get('generate-invoice/{id}', [OrderController::class, 'generate_invoice'])->name('generate-invoice');
    Route::get('generate-order-invoice/{id}', [OrderController::class, 'generate_order_invoice'])->name('generate-order-invoice');
    Route::post('add-payment-ref-code/{id}', [OrderController::class, 'add_payment_ref_code'])->name('add-payment-ref-code');
    Route::post('update-order-amount',  [OrderController::class, 'edit_order_amount'])->name('update-order-amount');
    Route::post('update-discount-amount',[OrderController::class, 'edit_discount_amount'])->name('update-discount-amount');
    Route::post('add-order-proof/{id}', [OrderController::class, 'add_order_proof'])->name('add-order-proof');
    Route::get('remove-proof-image',    [OrderController::class, 'remove_proof_image'])->name('remove-proof-image');
    Route::get('export-orders/{file_type}/{status}/{type}', [OrderController::class, 'export_orders'])->name('export');
});

// BANNERS ----------------------------------------------------------------
Route::group(['prefix' => 'banner', 'as' => 'banner.'], function () {
    Route::get('offer-banner',           [BannerController::class, 'offer_banner'])->name('offer');
    Route::post('store-offer-banner',    [BannerController::class, 'store_offer_banner'])->name('store-offer-banner');
    Route::get('delete-offer-banner/{id}',[BannerController::class, 'delete_offer_banner'])->name('delete-offer-banner');
});
Route::group(['prefix' => 'banner', 'as' => 'banner.', 'middleware' => ['module:banner']], function () {
    Route::get('list',               [BannerController::class, 'list'])->name('list');
    Route::post('store',             [BannerController::class, 'store'])->name('store');
    Route::get('edit/{banner}',      [BannerController::class, 'edit'])->name('edit');
    Route::post('update/{banner}',   [BannerController::class, 'update'])->name('update');
    Route::get('status/{id}/{status}',[BannerController::class, 'status_update'])->name('status_update');
    Route::delete('delete/{banner}', [BannerController::class, 'delete'])->name('delete');
    Route::get('join_campaign/{id}/{status}', [BannerController::class, 'status'])->name('status');
});
