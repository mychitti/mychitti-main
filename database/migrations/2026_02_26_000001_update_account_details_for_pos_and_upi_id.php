<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('account_details')) { 
            return;
        }

        if (!Schema::hasColumn('account_details', 'upi_id')) {
            DB::statement("ALTER TABLE `account_details` ADD `upi_id` VARCHAR(255) NULL AFTER `ifsc_code`");
        } else {
            DB::statement("ALTER TABLE `account_details` MODIFY `upi_id` VARCHAR(255) NULL");
        }

        if (Schema::hasColumn('account_details', 'type')) {
            DB::statement("ALTER TABLE `account_details` MODIFY `type` ENUM('invoice','quotation','pos') NOT NULL DEFAULT 'invoice'");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('account_details')) {
            return;
        }

        if (Schema::hasColumn('account_details', 'type')) {
            DB::statement("ALTER TABLE `account_details` MODIFY `type` ENUM('invoice','quotation') NOT NULL DEFAULT 'invoice'");
        }
    }
};
