<?php

use Illuminate\Support\Facades\Route;

// ══════════════════════════════════════════════════════════════════════════════
//  POS MANAGEMENT  →  URL: /pos/…
// ══════════════════════════════════════════════════════════════════════════════
Route::group(['prefix' => 'pos', 'as' => 'pos.', 'middleware' => ['planwise:pos']], function () {
    require __DIR__ . '/vendor/tables.php';
    require __DIR__ . '/vendor/pos-core.php';
    require __DIR__ . '/vendor/branch.php';
    require __DIR__ . '/vendor/order-type.php';
    require __DIR__ . '/vendor/settings.php';
});

// ══════════════════════════════════════════════════════════════════════════════
//  POS SHOP (variant_price outside planwise gate)  →  URL: /pos/…
// ══════════════════════════════════════════════════════════════════════════════
Route::group(['prefix' => 'pos', 'as' => 'pos.'], function () {
    require __DIR__ . '/vendor/shop.php';
});
