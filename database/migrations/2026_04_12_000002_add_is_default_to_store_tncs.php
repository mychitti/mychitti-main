<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_tncs', function (Blueprint $table) {
            if (!Schema::hasColumn('store_tncs', 'is_default')) {
                $table->boolean('is_default')->default(0)->after('tnc_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_tncs', function (Blueprint $table) {
            $table->dropColumnIfExists('is_default');
        });
    }
};
