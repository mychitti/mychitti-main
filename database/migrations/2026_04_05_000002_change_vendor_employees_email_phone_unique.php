<?php

use Illuminate\Database\Migrations\Migration;

// Superseded by 2026_04_05_000004 — global unique on email/phone is kept as-is.
// Resigned employees have their email/phone moved to resigned_email/resigned_phone columns.
return new class extends Migration
{
    public function up(): void {}
    public function down(): void {}
};
