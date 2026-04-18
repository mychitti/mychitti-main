<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceRequestIdToPrescriptionsTable extends Migration
{
    public function up()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('prescriptions', 'service_request_id')) {
                $table->unsignedBigInteger('service_request_id')->nullable()->after('appointment_id');
            }
        });
    }

    public function down()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (Schema::hasColumn('prescriptions', 'service_request_id')) {
                $table->dropColumn('service_request_id');
            }
        });
    }
}
