<?php

use Illuminate\Support\Facades\Route;

// ══════════════════════════════════════════════════════════════════════════════
//  POS RETAIL  (business_type = 'pos_retail')  →  URL: /retail-pos/…
//  Gated by planwise:pos (free for pos_retail stores via config/planwise.php).
// ══════════════════════════════════════════════════════════════════════════════
Route::group(['middleware' => ['planwise:pos']], function () {
    require __DIR__ . '/vendor/retail-pos.php';
});
