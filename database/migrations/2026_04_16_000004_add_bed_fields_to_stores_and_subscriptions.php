<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBedFieldsToStoresAndSubscriptions extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->unsignedInteger('bed_count')->default(0)->after('module_id');
        });

        Schema::table('vendor_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('bed_tier_id')->nullable()->after('plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('bed_count');
        });
        Schema::table('vendor_subscriptions', function (Blueprint $table) {
            $table->dropColumn('bed_tier_id');
        });
    }
}
