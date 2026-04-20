<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $featureId = DB::table('features')->insertGetId([
            'name'         => 'marketing_dashboard',
            'display_name' => 'Marketing Dashboard',
            'master_module' => 'marketing_dashboard',
        ]);

        DB::table('feature_permissions')->insert([
            'feature_id' => $featureId,
            'action'     => 'view',
            'free'       => 0,
        ]);
    }

    public function down(): void
    {
        $feature = DB::table('features')->where('name', 'marketing_dashboard')->first();
        if ($feature) {
            DB::table('feature_permissions')->where('feature_id', $feature->id)->delete();
            DB::table('features')->where('id', $feature->id)->delete();
        }
    }
};
