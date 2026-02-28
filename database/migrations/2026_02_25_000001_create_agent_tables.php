<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── AI Providers ──────────────────────────────────────────────────
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider_key')->unique();  // 'anthropic', 'openai', 'gemini'
            $table->string('api_url')->nullable();
            $table->tinyInteger('status')->default(1); // 1=active
            $table->timestamps();
        });

        // ── Agent Versions ────────────────────────────────────────────────
        Schema::create('agent_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->string('version_tag');              // e.g. 'v1.0'
            $table->text('changelog')->nullable();
            $table->string('updated_by')->nullable();
            $table->boolean('is_live')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('agent_id')->references('id')->on('system_prompts')->onDelete('cascade');
        });

        // ── Agent API Tools ───────────────────────────────────────────────
        Schema::create('agent_api_tools', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->string('api_name');                 // matches AgentFunction.function_name
            $table->string('endpoint');                 // e.g. /api/v1/ai-internal/user/{userId}/service-booking
            $table->string('method')->default('POST');  // GET | POST
            $table->string('auth_type')->default('api_key'); // api_key | bearer | none
            $table->string('action_type')->nullable();  // categorisation: read | write | booking
            $table->boolean('require_confirm')->default(false);
            $table->integer('rate_limit')->nullable();  // max calls per minute
            $table->boolean('write_guard')->default(false);
            $table->tinyInteger('status')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('agent_id')->references('id')->on('system_prompts')->onDelete('cascade');
        });

        // ── Agent Function Schemas ────────────────────────────────────────
        Schema::create('agent_functions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->string('function_name');            // tool name Claude sees
            $table->text('description');                // tool description for Claude
            $table->json('json_schema')->nullable();    // input_schema object
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('agent_id')->references('id')->on('system_prompts')->onDelete('cascade');
        });

        // ── Agent Tasks ───────────────────────────────────────────────────
        Schema::create('agent_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->string('task_name');
            $table->text('description')->nullable();
            $table->string('trigger_type')->nullable();   // manual | cron | event
            $table->string('skill_category')->nullable();
            $table->boolean('is_destructive')->default(false);
            $table->boolean('require_approval')->default(false);
            $table->tinyInteger('status')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('agent_id')->references('id')->on('system_prompts')->onDelete('cascade');
        });

        // ── Agent Run Logs ────────────────────────────────────────────────
        Schema::create('agent_run_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->string('version_tag')->nullable();
            $table->string('trigger_type')->nullable();   // manual_test | cron | webhook
            $table->string('status');                     // success | failed
            $table->text('message')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->string('triggered_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('agent_id')->references('id')->on('system_prompts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_run_logs');
        Schema::dropIfExists('agent_tasks');
        Schema::dropIfExists('agent_functions');
        Schema::dropIfExists('agent_api_tools');
        Schema::dropIfExists('agent_versions');
        Schema::dropIfExists('ai_providers');
    }
};
