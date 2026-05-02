<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStoreIdToServiceStatusesTable extends Migration
{
    public function up()
    {
        Schema::table('service_statuses', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->nullable()->after('removable');
        });
    }

    public function down()
    {
        Schema::table('service_statuses', function (Blueprint $table) {
            $table->dropColumn('store_id');
        });
    }
}
