<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceRequestIdToAccountTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('service_request_id')->nullable()->after('current_balance');
        });
    }

    public function down()
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropColumn('service_request_id');
        });
    }
}
