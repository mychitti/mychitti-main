<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->enum('category', ['admin', 'user', 'vendor']);
            $table->string('skill_type'); // billing, chatbot, user_mgmt, onboarding, analytics, support, custom
            $table->enum('status', ['active', 'draft', 'archived'])->default('draft');
            $table->string('description')->nullable();
            $table->longText('system_prompt')->nullable();
            $table->json('variables')->nullable();       // [{ key, val }, ...]
            $table->json('settings')->nullable();        // { inject_context, memory, tool_calling, rag }
            $table->integer('version')->default(1);
            $table->timestamps();
        });

        Schema::create('ai_prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_prompt_id')->constrained()->cascadeOnDelete();
            $table->string('version_label');             // v1.0, v1.1 ...
            $table->longText('system_prompt')->nullable();
            $table->json('variables')->nullable();
            $table->string('saved_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_versions');
        Schema::dropIfExists('ai_prompts');
    }
};
