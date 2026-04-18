<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storeConfigs', function (Blueprint $table) {
            $table->string('invoice_template', 50)->default('service_n_manual')->after('pos_token_template');
        });
    }

    public function down(): void
    {
        Schema::table('storeConfigs', function (Blueprint $table) {
            $table->dropColumn('invoice_template');
        });
    }
};
