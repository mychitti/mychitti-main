<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laundry_orders', function (Blueprint $table) {
            $table->dropUnique('laundry_orders_order_no_unique');
            $table->unique(['store_id', 'order_no'], 'laundry_orders_store_order_no_unique');
        });

        Schema::table('laundry_challans', function (Blueprint $table) {
            if ($this->uniqueExists('laundry_challans', 'laundry_challans_challan_no_unique')) {
                $table->dropUnique('laundry_challans_challan_no_unique');
            }
            $table->unique(['store_id', 'challan_no'], 'laundry_challans_store_challan_no_unique');
        });
    }

    public function down(): void
    {
        Schema::table('laundry_orders', function (Blueprint $table) {
            $table->dropUnique('laundry_orders_store_order_no_unique');
            $table->unique('order_no', 'laundry_orders_order_no_unique');
        });

        Schema::table('laundry_challans', function (Blueprint $table) {
            $table->dropUnique('laundry_challans_store_challan_no_unique');
            $table->unique('challan_no', 'laundry_challans_challan_no_unique');
        });
    }

    private function uniqueExists(string $table, string $indexName): bool
    {
        $indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
