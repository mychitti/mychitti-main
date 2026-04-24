<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMcCampaignRelationsTables extends Migration
{
    public function up()
    {
        // FAQ entries
        Schema::create('mc_campaign_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('mc_campaigns')->cascadeOnDelete();
            $table->text('question');
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Winners / Results
        Schema::create('mc_campaign_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('mc_campaigns')->cascadeOnDelete();
            $table->string('winner_name');
            $table->unsignedInteger('position')->default(1);
            $table->string('prize_detail')->nullable();
            $table->date('drawn_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Sponsors
        Schema::create('mc_campaign_sponsors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('mc_campaigns')->cascadeOnDelete();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->enum('tier', ['title', 'gold', 'silver', 'bronze', 'partner'])->default('partner');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Influencers
        Schema::create('mc_campaign_influencers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('mc_campaigns')->cascadeOnDelete();
            $table->string('name');
            $table->string('handle')->nullable();
            $table->enum('platform', ['instagram', 'youtube', 'twitter', 'tiktok', 'facebook', 'other'])->default('instagram');
            $table->string('followers')->nullable();
            $table->decimal('agreed_rate', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Guests ("CHEPGUST")
        Schema::create('mc_campaign_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('mc_campaigns')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('type', ['vip', 'general', 'press', 'complimentary'])->default('general');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Collaborations
        Schema::create('mc_campaign_collaborations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('mc_campaigns')->cascadeOnDelete();
            $table->string('partner_name');
            $table->string('type')->nullable(); // e.g. media, brand, ngo
            $table->text('terms')->nullable();
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Budget plan items
        Schema::create('mc_campaign_budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('mc_campaigns')->cascadeOnDelete();
            $table->string('category');
            $table->string('item_description');
            $table->decimal('budgeted_amount', 12, 2)->default(0);
            $table->decimal('actual_amount', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Expenses
        Schema::create('mc_campaign_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('mc_campaigns')->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('expense_date')->nullable();
            $table->string('receipt')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Targets
        Schema::create('mc_campaign_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('mc_campaigns')->cascadeOnDelete();
            $table->string('metric'); // e.g. "Total Entries", "Social Reach"
            $table->decimal('target_value', 14, 2)->default(0);
            $table->decimal('current_value', 14, 2)->default(0);
            $table->string('unit')->nullable(); // e.g. "entries", "impressions"
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Team members
        Schema::create('mc_campaign_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('mc_campaigns')->cascadeOnDelete();
            $table->string('member_name');
            $table->string('role');
            $table->text('responsibilities')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mc_campaign_team_members');
        Schema::dropIfExists('mc_campaign_targets');
        Schema::dropIfExists('mc_campaign_expenses');
        Schema::dropIfExists('mc_campaign_budget_items');
        Schema::dropIfExists('mc_campaign_collaborations');
        Schema::dropIfExists('mc_campaign_guests');
        Schema::dropIfExists('mc_campaign_influencers');
        Schema::dropIfExists('mc_campaign_sponsors');
        Schema::dropIfExists('mc_campaign_winners');
        Schema::dropIfExists('mc_campaign_faqs');
    }
}
