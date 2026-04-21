<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\DietChart;
use App\Models\DoctorProfile;
use App\Models\IpdAdmission;
use App\Models\NurseProfile;
use App\Models\NursingNote;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HospitalDashboardController extends Controller
{
    public function index(Request $request)
    {
        $store_id = Helpers::get_store_id();

        $preset = $request->get('date_range', 'this_month');
        $custom = $request->get('custom_date_range');
        $range  = Helpers::calculatePresetDates($preset, $custom);
        $from   = $range['start']->toDateString();
        $to     = $range['end']->toDateString();

        // ── Summary stats (date-range aware for dynamic counts) ────────────
        $stats = [
            'patients'          => Patient::where('store_id', $store_id)->count(),
            'doctors'           => DoctorProfile::where('store_id', $store_id)->count(),
            'nurses'            => NurseProfile::where('store_id', $store_id)->count(),
            'wards'             => Ward::where('store_id', $store_id)->count(),
            'beds_total'        => Bed::where('store_id', $store_id)->count(),
            'beds_available'    => Bed::where('store_id', $store_id)->where('status', 'available')->count(),
            'beds_occupied'     => Bed::where('store_id', $store_id)->where('status', 'occupied')->count(),
            'opd_in_range'      => OpdVisit::where('store_id', $store_id)->whereDate('visit_date', '>=', $from)->whereDate('visit_date', '<=', $to)->count(),
            'ipd_admitted'      => IpdAdmission::where('store_id', $store_id)->where('status', 'admitted')->count(),
            'ipd_in_range'      => IpdAdmission::where('store_id', $store_id)->whereDate('admission_date', '>=', $from)->whereDate('admission_date', '<=', $to)->count(),
            'prescriptions_in_range' => Prescription::where('store_id', $store_id)->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->count(),
        ];

        // ── OPD trend — within selected range ─────────────────────────────
        $opdTrend = OpdVisit::where('store_id', $store_id)
            ->whereDate('visit_date', '>=', $from)
            ->whereDate('visit_date', '<=', $to)
            ->select(DB::raw('DATE(visit_date) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')->orderBy('day')
            ->pluck('total', 'day');

        $opdLabels = [];
        $opdData   = [];
        $current   = \Carbon\Carbon::parse($from);
        $end       = \Carbon\Carbon::parse($to);
        while ($current->lte($end)) {
            $d = $current->toDateString();
            $opdLabels[] = $current->format('d M');
            $opdData[]   = $opdTrend[$d] ?? 0;
            $current->addDay();
        }

        // ── IPD trend — within selected range ─────────────────────────────
        $ipdTrend = IpdAdmission::where('store_id', $store_id)
            ->whereDate('admission_date', '>=', $from)
            ->whereDate('admission_date', '<=', $to)
            ->select(DB::raw('DATE(admission_date) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')->orderBy('day')
            ->pluck('total', 'day');

        $ipdLabels = [];
        $ipdData   = [];
        $current   = \Carbon\Carbon::parse($from);
        while ($current->lte($end)) {
            $d = $current->toDateString();
            $ipdLabels[] = $current->format('d M');
            $ipdData[]   = $ipdTrend[$d] ?? 0;
            $current->addDay();
        }

        // ── Bed occupancy by ward ─────────────────────────────────────────
        $wards = Ward::where('store_id', $store_id)
            ->where('is_active', true)
            ->withCount(['beds', 'availableBeds'])
            ->orderBy('ward_name')
            ->get();

        // ── Recent records (all filtered by selected date range) ──────────
        $recentPatients = Patient::where('store_id', $store_id)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->orderByDesc('created_at')->limit(5)->get();

        $recentDoctors = DoctorProfile::where('store_id', $store_id)
            ->with('employee')->orderByDesc('created_at')->limit(5)->get();

        $recentNurses = NurseProfile::where('store_id', $store_id)
            ->with(['employee', 'ward'])->orderByDesc('created_at')->limit(5)->get();

        $recentAdmissions = IpdAdmission::where('store_id', $store_id)
            ->with(['patient', 'ward', 'bed'])
            ->whereDate('admission_date', '>=', $from)
            ->whereDate('admission_date', '<=', $to)
            ->orderByDesc('admission_date')->limit(5)->get();

        $recentOpdVisits = OpdVisit::where('store_id', $store_id)
            ->with(['patient', 'doctorProfile.employee'])
            ->whereDate('visit_date', '>=', $from)->whereDate('visit_date', '<=', $to)
            ->orderByDesc('token_number')->limit(8)->get();

        return view('vendor-views.hospital.dashboard', compact(
            'stats', 'opdLabels', 'opdData', 'ipdLabels', 'ipdData',
            'wards', 'recentPatients', 'recentDoctors', 'recentNurses',
            'recentAdmissions', 'recentOpdVisits', 'preset', 'from', 'to'
        ));
    }
}
