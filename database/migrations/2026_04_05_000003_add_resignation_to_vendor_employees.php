<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_employees', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_employees', 'resignation')) {
                $table->tinyInteger('resignation')->default(0)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_employees', function (Blueprint $table) {
            $table->dropColumn('resignation');
        });
    }
};
