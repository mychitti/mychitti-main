<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCouponClaimFields extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->unsignedInteger('min_services')->default(0)->after('limit');
            $table->timestamp('claimed_at')->nullable()->after('min_services');
            $table->unsignedInteger('claimed_by_store_id')->nullable()->after('claimed_at');
            $table->string('claim_otp', 4)->nullable()->after('claimed_by_store_id');
            $table->timestamp('claim_otp_expires_at')->nullable()->after('claim_otp');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['min_services', 'claimed_at', 'claimed_by_store_id', 'claim_otp', 'claim_otp_expires_at']);
        });
    }
}
