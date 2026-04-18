<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrescriptionsTables extends Migration
{
    public function up()
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_profile_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('service_request_id')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('notes')->nullable();              // doctor notes / advice
            $table->date('follow_up_date')->nullable();
            $table->boolean('is_finalized')->default(false);
            $table->timestamps();
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescription_id');
            $table->string('medicine_name');
            $table->string('dosage')->nullable();           // e.g. "500 mg"
            $table->string('frequency')->nullable();        // e.g. "3 times a day"
            $table->string('duration')->nullable();         // e.g. "7 days"
            $table->string('instructions')->nullable();     // e.g. "after food"
            $table->unsignedSmallInteger('quantity')->nullable();
            $table->timestamps();

            $table->foreign('prescription_id')->references('id')->on('prescriptions')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
    }
}
