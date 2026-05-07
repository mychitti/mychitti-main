<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->string('domain');
            $table->decimal('charge', 10, 2)->default(0);
            $table->decimal('gst_percent', 5, 2)->default(0);
            $table->decimal('gst_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('transaction_id')->nullable();
            $table->enum('status', ['active', 'removed'])->default('active');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->index(['store_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_purchases');
    }
};
