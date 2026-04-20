<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // 0 = pending, 1 = approved, 2 = rejected. NULL means admin-created (no approval needed).
            $table->tinyInteger('approval')->nullable()->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('approval');
        });
    }
};
