<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_deletions', function (Blueprint $table) {
            $table->id();
            $table->string('user_type')->default('user'); // user, vendor, admin
            $table->unsignedBigInteger('user_id');
            $table->string('reason_id');
            $table->text('other_reason')->nullable();
            $table->timestamp('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletions');
    }
};
