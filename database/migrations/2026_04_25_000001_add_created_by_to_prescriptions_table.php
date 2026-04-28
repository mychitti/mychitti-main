<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedByToPrescriptionsTable extends Migration
{
    public function up()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('is_finalized');
            $table->string('created_by_type', 20)->nullable()->after('created_by'); // 'vendor' or 'vendor_employee'
        });
    }

    public function down()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'created_by_type']);
        });
    }
}
