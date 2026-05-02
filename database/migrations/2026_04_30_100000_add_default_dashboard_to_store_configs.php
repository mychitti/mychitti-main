<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultDashboardToStoreConfigs extends Migration
{
    public function up()
    {
        $table = Schema::hasTable('storeConfigs') ? 'storeConfigs' : 'store_configs';
        Schema::table($table, function (Blueprint $table) {
            $table->string('default_dashboard', 30)->nullable()->after('leads_guide_dismissed');
        });
    }

    public function down()
    {
        $table = Schema::hasTable('storeConfigs') ? 'storeConfigs' : 'store_configs';
        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn('default_dashboard');
        });
    }
}
