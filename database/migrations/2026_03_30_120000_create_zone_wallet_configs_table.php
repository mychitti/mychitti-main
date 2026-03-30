<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zone_wallet_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zone_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->decimal('min_balance', 10, 2)->default(100);
            $table->timestamps();

            $table->unique(['zone_id', 'category_id']);
            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_wallet_configs');
    }
};
