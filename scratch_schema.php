<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\Store; 

$store = Store::withoutGlobalScopes()->with('galleries')->where('slug', 'firoz-dental')->first();
if ($store) {
    echo "SUCCESS: Found store ID " . $store->id . " with galleries count " . $store->galleries->count() . "\n";
} else {
    echo "FAILED: Store not found by slug 'firoz-dental'\n";
}
 