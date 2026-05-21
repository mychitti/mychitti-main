<?php

namespace App\Modules\HMIS\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\HospitalActivityLog;
use Illuminate\Http\Request;

class HospitalActivityLogController extends Controller
{
    const CATEGORIES = [
        'appointments'  => ['appointment'],
        'prescriptions' => ['prescription'],
        'patients'      => ['patient'],
        'opd'           => ['opd_visit'],
        'ipd'           => ['ipd_admission'],
        'doctors_slots' => ['doctor', 'slot'],
    ];

    public function index(Request $request)
    {
        $category = $request->get('category', '');
        $storeId  = $request->get('store_id');

        $preset = $request->get('date_range', 'today');
        $custom = $request->get('custom_date_range');
        $range  = Helpers::calculatePresetDates($preset, $custom);
        $from   = $range['start']->toDateString();
        $to     = $range['end']->toDateString();

        $base = HospitalActivityLog::whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        if ($storeId) {
            $base->where('store_id', $storeId);
        }

        $rawCounts = (clone $base)->selectRaw('subject_type, COUNT(*) as total')
            ->groupBy('subject_type')
            ->pluck('total', 'subject_type');

        $categoryCounts = ['all' => $rawCounts->sum()];
        foreach (self::CATEGORIES as $slug => $types) {
            $categoryCounts[$slug] = $rawCounts->only($types)->sum();
        }

        $query = (clone $base)->with('store')->latest();

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

        return view('hmis::admin.logs.hospital-activity-logs', compact(
            'logs', 'categoryCounts', 'category', 'preset', 'storeId'
        ));
    }
}
