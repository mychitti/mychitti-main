<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\HospitalActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HospitalActivityLogController extends Controller
{
    /**
     * The trail records who opened which patient, so it is not something every ward clerk should
     * be able to read. It hangs off the existing hospital_manage feature as its own action rather
     * than riding on `settings`, which is a write permission.
     */
    public static function ensurePermission(): void
    {
        try {
            $seeded = DB::table('feature_permissions as fp')
                ->join('features as f', 'fp.feature_id', '=', 'f.id')
                ->where('f.name', 'hospital_manage')
                ->where('fp.action', 'activity_log')
                ->exists();
            if ($seeded) {
                return;
            }
        } catch (\Throwable $e) {
            return; // permission tables not provisioned on this database yet
        }

        if (!Schema::hasTable('features') || !Schema::hasTable('feature_permissions')) {
            return;
        }

        $featureId = DB::table('features')->where('name', 'hospital_manage')->value('id');
        if (!$featureId) {
            return;
        }

        DB::table('feature_permissions')->insert([
            'feature_id' => $featureId,
            'action'     => 'activity_log',
            'free'       => 0,
        ]);
    }

    const CATEGORIES = [
        'appointments'  => ['appointment'],
        'prescriptions' => ['prescription'],
        'patients'      => ['patient'],
        'opd'           => ['opd_visit'],
        'ipd'           => ['ipd_admission'],
        'doctors_slots' => ['doctor', 'slot'],
        'lab_work'      => ['opd_lab_work'],
    ];

    public function index(Request $request)
    {
        $storeId  = Helpers::get_store_id();
        $category = $request->get('category', '');

        $preset = $request->get('date_range', 'today');
        $custom = $request->get('custom_date_range');
        $range  = Helpers::calculatePresetDates($preset, $custom);
        $from   = $range['start']->toDateString();
        $to     = $range['end']->toDateString();

        $base = HospitalActivityLog::where('store_id', $storeId)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        $rawCounts = (clone $base)->selectRaw('subject_type, COUNT(*) as total')
            ->groupBy('subject_type')
            ->pluck('total', 'subject_type');

        $categoryCounts = ['all' => $rawCounts->sum()];
        foreach (self::CATEGORIES as $slug => $types) {
            $categoryCounts[$slug] = $rawCounts->only($types)->sum();
        }

        $query = (clone $base)->latest();

        if ($category && isset(self::CATEGORIES[$category])) {
            $query->whereIn('subject_type', self::CATEGORIES[$category]);
        }

        if ($request->filled('causer_type')) {
            $query->where('causer_type', $request->causer_type);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('description', 'like', "%{$q}%")
                    ->orWhere('causer_name', 'like', "%{$q}%");
            });
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('hmis::vendor.hospital.activity_log', compact('logs', 'categoryCounts', 'category', 'preset'));
    }
}
