<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $featureId = DB::table('features')->insertGetId([
            'name'         => 'restaurant_tables',
            'display_name' => 'Restaurant Tables',
            'master_module' => 'pos',
        ]);

        $actions = ['list', 'add', 'edit', 'delete'];
        foreach ($actions as $action) {
            DB::table('feature_permissions')->insert([
                'feature_id' => $featureId,
                'action'     => $action,
                'free'       => 0,
            ]);
        }
    }

    public function down(): void
    {
        $feature = DB::table('features')->where('name', 'restaurant_tables')->first();
        if ($feature) {
            DB::table('feature_permissions')->where('feature_id', $feature->id)->delete();
            DB::table('features')->where('id', $feature->id)->delete();
        }
    }
};
