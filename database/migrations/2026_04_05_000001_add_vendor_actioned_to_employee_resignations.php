<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_resignations', function (Blueprint $table) {
            $table->tinyInteger('vendor_actioned')->default(0)->after('status')
                ->comment('1 = vendor has already actioned this, admin sees it as vendor-resolved');
            $table->tinyInteger('is_store_staff')->default(0)->after('vendor_actioned')
                ->comment('1 = this is a vendor/store employee resignation forwarded to admin');
        });
    }

    public function down(): void
    {
        Schema::table('employee_resignations', function (Blueprint $table) {
            $table->dropColumn('vendor_actioned');
        });
    }
};
