<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHospitalBedTiersTable extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_bed_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('tier_name');
            $table->unsignedInteger('min_beds');
            $table->unsignedInteger('max_beds')->nullable()->comment('NULL = unlimited');
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->boolean('is_custom')->default(false)->comment('true = contact us for pricing');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_bed_tiers');
    }
}
