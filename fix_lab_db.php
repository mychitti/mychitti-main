<?php
header('Content-Type: text/plain');

$host = 'localhost';
$db   = 'mychitti_staging';
$user = 'mychitti';
$pass = 'JamalS@876457881P';
$port = '3306';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected successfully to database.\n\n";

    $tables = [
        'lab_tests' => [
            'store_id' => 'BIGINT UNSIGNED NULL',
            'name' => 'VARCHAR(200) NOT NULL DEFAULT \'\'',
            'code' => 'VARCHAR(60) NULL',
            'department' => 'VARCHAR(80) NULL',
            'sample_type' => 'VARCHAR(80) NULL',
            'price' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
            'tat_text' => 'VARCHAR(60) NULL',
            'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'created_at' => 'TIMESTAMP NULL',
            'updated_at' => 'TIMESTAMP NULL'
        ],
        'lab_test_parameters' => [
            'lab_test_id' => 'BIGINT UNSIGNED NOT NULL',
            'name' => 'VARCHAR(160) NOT NULL DEFAULT \'\'',
            'unit' => 'VARCHAR(40) NULL',
            'normal_low' => 'DECIMAL(12,3) NULL',
            'normal_high' => 'DECIMAL(12,3) NULL',
            'ref_range_text' => 'VARCHAR(120) NULL',
            'critical_low' => 'DECIMAL(12,3) NULL',
            'critical_high' => 'DECIMAL(12,3) NULL',
            'sort_order' => 'INT NOT NULL DEFAULT 0',
            'created_at' => 'TIMESTAMP NULL',
            'updated_at' => 'TIMESTAMP NULL'
        ],
        'lab_orders' => [
            'store_id' => 'BIGINT UNSIGNED NULL',
            'order_no' => 'VARCHAR(40) NULL',
            'patient_id' => 'BIGINT UNSIGNED NULL',
            'doctor_profile_id' => 'BIGINT UNSIGNED NULL',
            'prescription_id' => 'BIGINT UNSIGNED NULL',
            'opd_id' => 'BIGINT UNSIGNED NULL',
            'source' => 'VARCHAR(20) NULL',
            'department' => 'VARCHAR(30) NULL',
            'priority' => 'VARCHAR(20) NOT NULL DEFAULT \'routine\'',
            'status' => 'VARCHAR(20) NOT NULL DEFAULT \'ordered\'',
            'sample_type' => 'VARCHAR(80) NULL',
            'clinical_notes' => 'TEXT NULL',
            'total_amount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
            'referred_by' => 'VARCHAR(150) NULL',
            'technician_notes' => 'TEXT NULL',
            'analysed_by' => 'VARCHAR(120) NULL',
            'verified_by_name' => 'VARCHAR(120) NULL',
            'collected_at' => 'TIMESTAMP NULL',
            'reported_at' => 'TIMESTAMP NULL',
            'created_by' => 'BIGINT UNSIGNED NULL',
            'created_by_type' => 'VARCHAR(30) NULL',
            'created_at' => 'TIMESTAMP NULL',
            'updated_at' => 'TIMESTAMP NULL'
        ],
        'lab_order_items' => [
            'lab_order_id' => 'BIGINT UNSIGNED NOT NULL',
            'lab_test_id' => 'BIGINT UNSIGNED NOT NULL',
            'price' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
            'tat_text' => 'VARCHAR(60) NULL',
            'created_at' => 'TIMESTAMP NULL',
            'updated_at' => 'TIMESTAMP NULL'
        ],
        'lab_order_results' => [
            'lab_order_id' => 'BIGINT UNSIGNED NOT NULL',
            'lab_test_id' => 'BIGINT UNSIGNED NOT NULL',
            'lab_test_parameter_id' => 'BIGINT UNSIGNED NOT NULL',
            'parameter_name' => 'VARCHAR(160) NOT NULL DEFAULT \'\'',
            'unit' => 'VARCHAR(40) NULL',
            'normal_low' => 'DECIMAL(12,3) NULL',
            'normal_high' => 'DECIMAL(12,3) NULL',
            'ref_range_text' => 'VARCHAR(120) NULL',
            'critical_low' => 'DECIMAL(12,3) NULL',
            'critical_high' => 'DECIMAL(12,3) NULL',
            'result_value' => 'VARCHAR(120) NULL',
            'result_flag' => 'VARCHAR(10) NULL',
            'is_critical' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'critical_notified_at' => 'TIMESTAMP NULL',
            'critical_notified_to' => 'VARCHAR(120) NULL',
            'sort_order' => 'INT NOT NULL DEFAULT 0',
            'created_at' => 'TIMESTAMP NULL',
            'updated_at' => 'TIMESTAMP NULL'
        ],
        'lab_reagents' => [
            'store_id' => 'BIGINT UNSIGNED NULL',
            'name' => 'VARCHAR(160) NOT NULL DEFAULT \'\'',
            'machine' => 'VARCHAR(120) NULL',
            'for_test' => 'VARCHAR(120) NULL',
            'expiry_date' => 'DATE NULL',
            'stock' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
            'min_level' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
            'unit_label' => 'VARCHAR(40) NULL DEFAULT \'tests\'',
            'created_at' => 'TIMESTAMP NULL',
            'updated_at' => 'TIMESTAMP NULL'
        ],
        'lab_invoices' => [
            'store_id' => 'BIGINT UNSIGNED NULL',
            'lab_order_id' => 'BIGINT UNSIGNED NULL',
            'invoice_no' => 'VARCHAR(50) NULL',
            'patient_id' => 'BIGINT UNSIGNED NULL',
            'subtotal' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
            'insurance_provider' => 'VARCHAR(120) NULL',
            'insurance_covered' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
            'discount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
            'payable' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
            'payment_mode' => 'VARCHAR(40) NULL',
            'status' => 'VARCHAR(20) NOT NULL DEFAULT \'finalized\'',
            'created_at' => 'TIMESTAMP NULL',
            'updated_at' => 'TIMESTAMP NULL'
        ]
    ];

    foreach ($tables as $table => $columns) {
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            echo "Table '$table' does not exist. Skipping.\n";
            continue;
        }

        echo "Checking table '$table'...\n";
        
        // Get existing columns
        $q = $pdo->query("DESCRIBE `$table`");
        $existingCols = [];
        while ($row = $q->fetch()) {
            $existingCols[strtolower($row['Field'])] = $row;
        }

        foreach ($columns as $col => $definition) {
            if (!isset($existingCols[strtolower($col)])) {
                echo " - Column '$col' is MISSING. Adding it...\n";
                $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $definition");
                echo "   -> Added '$col' successfully.\n";
            }
        }
    }

    echo "\nAll checks completed successfully.\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}