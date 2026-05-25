<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('features')->where('name', 'leads_manage')->exists()) {
            return;
        }

        $featureId = DB::table('features')->insertGetId([
            'name'         => 'leads_manage',
            'display_name' => 'Appointment Management',
            'master_module' => 'leads_manage',
        ]);

        $actions = ['list', 'add', 'statuses', 'export', 'report', 'settings'];
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
        $feature = DB::table('features')->where('name', 'leads_manage')->first();
        if ($feature) {
            DB::table('feature_permissions')->where('feature_id', $feature->id)->delete();
            DB::table('features')->where('id', $feature->id)->delete();
        }
    }
};
