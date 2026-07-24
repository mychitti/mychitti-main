<?php
// Direct DB check using Laravel database connection
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\Store;

$store = Store::withoutGlobalScopes()->where('name', 'like', '%firoz%')->first();

header('Content-Type: text/plain');
if ($store) {
    echo "Store ID: " . $store->id . "\n"; 
    echo "Name: " . $store->name . "\n";
    echo "Slug: " . $store->slug . "\n";
    echo "Domain: '" . $store->domain . "'\n";
    echo "Galleries count: " . $store->galleries()->count() . "\n";
    echo "Active status: " . $store->active . "\n";
    echo "Status: " . $store->status . "\n";
} else { 
    echo "Store not found!\n";
}
