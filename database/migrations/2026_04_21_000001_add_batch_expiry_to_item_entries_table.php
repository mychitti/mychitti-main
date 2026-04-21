<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBatchExpiryToItemEntriesTable extends Migration
{
    public function up()
    {
        Schema::table('item_entries', function (Blueprint $table) {
            $table->string('batch_number')->nullable()->after('bill_number');
            $table->date('expiry_date')->nullable()->after('batch_number');
        });
    }

    public function down()
    {
        Schema::table('item_entries', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'expiry_date']);
        });
    }
}
