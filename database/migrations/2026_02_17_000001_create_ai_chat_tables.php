<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('a_i_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->enum('role', ['user', 'assistant']);
            $table->longText('content');
            $table->string('type', 20)->default('text');
            $table->boolean('summarized')->default(false);
            $table->timestamps();

            $table->index(['admin_id', 'summarized']);
            $table->index(['vendor_id', 'summarized']);
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
        });

        Schema::create('summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->longText('summary');
            $table->unsignedInteger('messages_covered')->default(0);
            $table->timestamps();

            $table->index('admin_id');
            $table->index('vendor_id');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
        });

        Schema::create('admin_memories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->string('key');
            $table->text('value');
            $table->timestamps();

            $table->unique(['admin_id', 'key']);
            $table->unique(['vendor_id', 'key']);
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_memories');
        Schema::dropIfExists('summaries');
        Schema::dropIfExists('a_i_chat_messages');
    }
};
