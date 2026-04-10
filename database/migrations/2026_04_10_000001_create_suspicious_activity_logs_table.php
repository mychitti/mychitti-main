<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuspiciousActivityLogsTable extends Migration
{
    public function up()
    {
        Schema::create('suspicious_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('phone', 20)->nullable()->index();
            $table->string('type', 50)->index(); // otp_abuse | enquiry_abuse
            $table->string('detail')->nullable();  // e.g. item_id, item name
            $table->unsignedSmallInteger('count')->default(0);
            $table->string('ip', 45)->nullable();
            $table->enum('status', ['open', 'reviewed', 'dismissed'])->default('open')->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('suspicious_activity_logs');
    }
}
