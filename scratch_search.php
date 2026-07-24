<?php
$files = [
    'routes/vendor.php',
    'routes/web.php',
    'routes/website-settings-routes.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (!file_exists($path)) {
        echo "File not found: $file\n";
        continue;
    }
    
    echo "=== Searching in $file ===\n";
    $lines = file($path);
    foreach ($lines as $i => $line) {
        if (stripos($line, 'settings') !== false || stripos($line, 'domain') !== false || stripos($line, 'gallery') !== false) {
            echo ($i + 1) . ": " . trim($line) . "\n";
        }
    }
}
 