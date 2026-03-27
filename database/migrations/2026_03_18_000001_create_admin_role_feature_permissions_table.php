<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_role_feature_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_role_id');
            $table->unsignedBigInteger('feature_permission_id');
            $table->timestamps();

            $table->foreign('admin_role_id')->references('id')->on('admin_roles')->onDelete('cascade');
            $table->foreign('feature_permission_id')->references('id')->on('feature_permissions')->onDelete('cascade');
            $table->unique(['admin_role_id', 'feature_permission_id'], 'admin_role_fp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_role_feature_permissions');
    }
};
