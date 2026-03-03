<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw queries as requested, compatible with both table name variants
        $table = Schema::hasTable('storeConfigs') ? 'storeConfigs' : 'store_configs';

        $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
        $existing = array_column($columns, 'Field');

        if (!in_array('printer_type', $existing)) {
            DB::statement("ALTER TABLE `{$table}` ADD COLUMN `printer_type` VARCHAR(20) NOT NULL DEFAULT 'none' COMMENT 'none|usb|bluetooth|lan'");
        }
        if (!in_array('printer_ip', $existing)) {
            DB::statement("ALTER TABLE `{$table}` ADD COLUMN `printer_ip` VARCHAR(100) NULL DEFAULT NULL COMMENT 'LAN printer IP address'");
        }
        if (!in_array('printer_port', $existing)) {
            DB::statement("ALTER TABLE `{$table}` ADD COLUMN `printer_port` SMALLINT UNSIGNED NULL DEFAULT 9100 COMMENT 'LAN printer port'");
        }
        if (!in_array('printer_name', $existing)) {
            DB::statement("ALTER TABLE `{$table}` ADD COLUMN `printer_name` VARCHAR(100) NULL DEFAULT NULL COMMENT 'USB device name or Bluetooth COM port'");
        }
        if (!in_array('printer_paper_width', $existing)) {
            DB::statement("ALTER TABLE `{$table}` ADD COLUMN `printer_paper_width` VARCHAR(10) NOT NULL DEFAULT '80mm' COMMENT '58mm|80mm'");
        }
        if (!in_array('printer_auto_print', $existing)) {
            DB::statement("ALTER TABLE `{$table}` ADD COLUMN `printer_auto_print` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Auto-print token on generate'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table = Schema::hasTable('storeConfigs') ? 'storeConfigs' : 'store_configs';

        foreach (['printer_type', 'printer_ip', 'printer_port', 'printer_name', 'printer_paper_width', 'printer_auto_print'] as $col) {
            $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
            $existing = array_column($columns, 'Field');
            if (in_array($col, $existing)) {
                DB::statement("ALTER TABLE `{$table}` DROP COLUMN `{$col}`");
            }
        }
    }
};
