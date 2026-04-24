<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMcCampaignsTable extends Migration
{
    public function up()
    {
        Schema::create('mc_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('status', ['draft', 'active', 'ended', 'cancelled'])->default('draft');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->date('draw_date')->nullable();

            // Prize
            $table->string('prize_title')->nullable();
            $table->decimal('prize_value', 12, 2)->nullable();
            $table->integer('number_of_winners')->default(1);
            $table->text('prize_description')->nullable();

            // Entries
            $table->integer('max_entries_per_person')->default(1);
            $table->integer('total_entry_limit')->nullable(); // null = unlimited

            // Content
            $table->longText('page_content')->nullable();
            $table->longText('how_it_works')->nullable();
            $table->longText('terms')->nullable();

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_image')->nullable();

            // Settings
            $table->boolean('require_login')->default(true);
            $table->boolean('show_entry_count')->default(false);

            // Internal
            $table->longText('plan_notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mc_campaigns');
    }
}
