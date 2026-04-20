<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_employees', function (Blueprint $table) {
            $table->enum('employee_type', ['permanent', 'temporary'])->default('permanent')->after('tentative_joining_date');
            $table->date('employment_end_date')->nullable()->after('employee_type');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_employees', function (Blueprint $table) {
            $table->dropColumn(['employee_type', 'employment_end_date']);
        });
    }
};
