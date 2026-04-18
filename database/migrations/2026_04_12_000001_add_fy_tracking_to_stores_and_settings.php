<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (!Schema::hasColumn('stores', 'receivable_receipt_fy')) {
                $table->string('receivable_receipt_fy', 6)->nullable()->after('receivable_receipt_serial_number');
            }
            if (!Schema::hasColumn('stores', 'jobcard_fy')) {
                $table->string('jobcard_fy', 6)->nullable()->after('jobcard_serial_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumnIfExists('receivable_receipt_fy');
            $table->dropColumnIfExists('jobcard_fy');
        });
    }
};
