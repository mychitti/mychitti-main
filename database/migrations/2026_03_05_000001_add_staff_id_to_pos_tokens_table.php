<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('staff_id')->default(0)->after('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('pos_tokens', function (Blueprint $table) {
            $table->dropColumn('staff_id');
        });
    }
};
