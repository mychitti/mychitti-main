<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = Schema::hasTable('storeConfigs') ? 'storeConfigs' : 'store_configs';

        if (Schema::hasTable($table) && !Schema::hasColumn($table, 'default_invoice_tnc_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('default_invoice_tnc_id')->nullable()->after('tnc_invoice_status');
            }); 
        }
    }

    public function down(): void
    {
        $table = Schema::hasTable('storeConfigs') ? 'storeConfigs' : 'store_configs';

        if (Schema::hasTable($table) && Schema::hasColumn($table, 'default_invoice_tnc_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('default_invoice_tnc_id');
            });
        }
    }
};
