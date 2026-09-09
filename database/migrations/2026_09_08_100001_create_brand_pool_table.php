<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Centralised brand directory — admin curates, vendors pick from it,
        // SEO pages show "Brand + Service" chips.
        if (!Schema::hasTable('brand_pool')) {
            Schema::create('brand_pool', function (Blueprint $table) {
                $table->id();
                $table->string('name', 200);
                $table->string('slug', 250)->unique();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->unsignedInteger('usage_count')->default(0);
                $table->timestamps();
            });
        }

        // Which brands apply to which service categories.
        if (!Schema::hasTable('brand_pool_category')) {
            Schema::create('brand_pool_category', function (Blueprint $table) {
                $table->unsignedBigInteger('brand_pool_id');
                $table->unsignedBigInteger('category_id');
                $table->primary(['brand_pool_id', 'category_id']);
            });
        }

        // Which brands apply to specific services (items where module_id = 6, e.g. AC Repair)
        if (!Schema::hasTable('brand_pool_service')) {
            Schema::create('brand_pool_service', function (Blueprint $table) {
                $table->unsignedBigInteger('brand_pool_id');
                $table->unsignedBigInteger('item_id');
                $table->primary(['brand_pool_id', 'item_id']);
            });
        }

        // FK on inventory_items so a vendor's item can link to a pooled brand.
        if (!Schema::hasColumn('inventory_items', 'brand_pool_id')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->unsignedBigInteger('brand_pool_id')->nullable()->after('brand');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_pool_service');
        Schema::dropIfExists('brand_pool_category');
        Schema::dropIfExists('brand_pool');

        if (Schema::hasColumn('inventory_items', 'brand_pool_id')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropColumn('brand_pool_id');
            });
        }
    }
};
