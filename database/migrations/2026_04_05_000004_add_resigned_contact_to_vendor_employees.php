<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_employees', function (Blueprint $table) {
            $table->string('resigned_email')->nullable()->after('email');
            $table->string('resigned_phone')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_employees', function (Blueprint $table) {
            $table->dropColumn(['resigned_email', 'resigned_phone']);
        });
    }
};
