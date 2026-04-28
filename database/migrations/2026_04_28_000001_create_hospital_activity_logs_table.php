<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHospitalActivityLogsTable extends Migration
{
    public function up()
    {
        Schema::create('hospital_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->string('subject_type', 50);   // appointment, prescription, patient, opd_visit, doctor, slot
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('action', 60);          // created, updated, status_changed, rescheduled, reassigned, finalized, deleted, toggled
            $table->string('description', 500);
            $table->string('causer_type', 20)->nullable();  // vendor, vendor_employee, api_user
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->string('causer_name', 100)->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index('store_id');
            $table->index(['store_id', 'subject_type']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hospital_activity_logs');
    }
}
