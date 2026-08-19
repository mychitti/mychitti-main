<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\DoctorProfile;
use App\Models\IpdAdmission;
use App\Models\NurseProfile;
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
        if (auth('vendor')->check()) {

            $store_id = Helpers::get_store_id();

            $preset = $request->get('date_range', 'this_month');
            $custom = $request->get('custom_date_range');
            $range  = Helpers::calculatePresetDates($preset, $custom);
            $from   = $range['start']->toDateString();
            $to     = $range['end']->toDateString();

            $stats = [
                'patients'               => Patient::where('store_id', $store_id)->count(),
                'doctors'                => DoctorProfile::where('store_id', $store_id)->count(),
                'nurses'                 => NurseProfile::where('store_id', $store_id)->count(),
                'wards'                  => Ward::where('store_id', $store_id)->count(),
                'beds_total'             => Bed::where('store_id', $store_id)->count(),
                'beds_available'         => Bed::where('store_id', $store_id)->where('status', 'available')->count(),
                'beds_occupied'          => Bed::where('store_id', $store_id)->where('status', 'occupied')->count(),
                'opd_in_range'           => OpdVisit::where('store_id', $store_id)->notCancelled()->whereDate('visit_date', '>=', $from)->whereDate('visit_date', '<=', $to)->count(),
                'ipd_admitted'           => IpdAdmission::where('store_id', $store_id)->where('status', 'admitted')->count(),
                'ipd_in_range'           => IpdAdmission::where('store_id', $store_id)->whereDate('admission_date', '>=', $from)->whereDate('admission_date', '<=', $to)->count(),
                'prescriptions_in_range' => Prescription::where('store_id', $store_id)->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->count(),
            ];

            $opdTrend = OpdVisit::where('store_id', $store_id)
                ->notCancelled()
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

            $wards = Ward::where('store_id', $store_id)
                ->where('is_active', true)
                ->withCount(['beds', 'availableBeds'])
                ->orderBy('ward_name')
                ->get();

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
                ->notCancelled()
                ->with(['patient', 'doctorProfile.employee'])
                ->whereDate('visit_date', '>=', $from)->whereDate('visit_date', '<=', $to)
                ->orderByDesc('token_number')->limit(8)->get();

            return view('hmis::vendor.hospital.dashboard', compact(
                'stats', 'opdLabels', 'opdData', 'ipdLabels', 'ipdData',
                'wards', 'recentPatients', 'recentDoctors', 'recentNurses',
                'recentAdmissions', 'recentOpdVisits', 'preset', 'from', 'to'
            ));
        } else {
            $emp      = auth('vendor_employee')->user();
            $store_id = Helpers::get_store_id();

            $preset = $request->get('date_range', 'today');
            $custom = $request->get('custom_date_range');
            $range  = Helpers::calculatePresetDates($preset, $custom);
            $from   = $range['start']->toDateString();
            $to     = $range['end']->toDateString();

            $search = $request->get('search');

            $doctorProfile = DoctorProfile::where('emp_id', $emp->id)
                ->where('store_id', $store_id)
                ->first();

            $opdVisits = OpdVisit::where('store_id', $store_id)
                ->notCancelled()
                ->when($doctorProfile, fn($q) => $q->where('doctor_profile_id', $doctorProfile->id))
                ->when(!$doctorProfile, fn($q) => $q->whereRaw('1=0'))
                ->whereBetween('visit_date', [$from, $to])
                ->when($search, function ($q) use ($search) {
                    $q->whereHas('patient', fn($p) => $p->where('name', 'like', "%$search%")
                        ->orWhere('patient_uid', 'like', "%$search%"));
                })
                ->with('patient')
                ->orderBy('token_number')
                ->paginate(20);

            return view('hmis::vendor.hospital.staff_dashboard', compact(
                'doctorProfile', 'opdVisits', 'preset', 'from', 'to', 'search'
            ));
        }
    }

    public function settings()
    {
        $store_id = Helpers::get_store_id();
        $config   = \App\Models\StoreConfig::where('store_id', $store_id)->first();
        $prefix   = $config?->patient_uid_prefix ?? 'P';
        $padding  = (int) ($config?->patient_uid_padding ?? 5);
        $serial   = (int) ($config?->patient_uid_serial ?? 1);
        $opd_consultation_count          = (int) ($config?->opd_consultation_count ?? 1);
        $opd_consultation_validity_days  = (int) ($config?->opd_consultation_validity_days ?? 7);

        $lastUid  = Patient::where('store_id', $store_id)->orderByDesc('id')->value('patient_uid');
        $autoNext = 1;
        if ($lastUid && preg_match('/(\d+)$/', $lastUid, $m)) {
            $autoNext = (int)$m[1] + 1;
        }
        $nextSerial  = max($autoNext, $serial);
        $previewMuid = strtoupper($prefix) . '-' . str_pad($nextSerial, $padding, '0', STR_PAD_LEFT);

        // Which prescription languages this hospital offers its doctors. Empty = English only.
        $rxLanguages = \App\Models\Prescription::enabledLanguages($store_id);

        return view('hmis::vendor.hospital.settings', compact(
            'prefix', 'padding', 'serial', 'previewMuid',
            'opd_consultation_count', 'opd_consultation_validity_days', 'rxLanguages'
        ));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'prefix'  => 'required|string|max:10|alpha_dash',
            'padding' => 'required|integer|min:1|max:10',
            'serial'  => 'required|integer|min:1',
            'opd_consultation_count'         => 'required|integer|min:1|max:50',
            'opd_consultation_validity_days' => 'required|integer|min:1|max:365',
            'rx_languages'                   => 'nullable|array',
            'rx_languages.*'                 => 'string|in:' . implode(',', array_keys(\App\Models\Prescription::LANGUAGES)),
        ]);

        $store_id = Helpers::get_store_id();

        $cfgTable = (new \App\Models\StoreConfig)->getTable();
        if (!\Illuminate\Support\Facades\Schema::hasColumn($cfgTable, 'patient_uid_prefix')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$cfgTable}`
                ADD COLUMN `patient_uid_prefix` VARCHAR(10) NULL,
                ADD COLUMN `patient_uid_padding` INT NULL,
                ADD COLUMN `patient_uid_serial` INT NULL");
        }
        // Which languages the prescription screen offers. Stored as JSON on the store's config so
        // a hospital that only ever writes English never has to scroll past twenty-two others.
        if (!\Illuminate\Support\Facades\Schema::hasColumn($cfgTable, 'rx_languages')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$cfgTable}` ADD COLUMN `rx_languages` TEXT NULL");
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($cfgTable, 'opd_consultation_count')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$cfgTable}`
                ADD COLUMN `opd_consultation_count` INT NULL,
                ADD COLUMN `opd_consultation_validity_days` INT NULL");
        }

        \App\Models\StoreConfig::updateOrInsert(
            ['store_id' => $store_id],
            [
                'patient_uid_prefix'             => strtoupper($request->prefix),
                'patient_uid_padding'            => (int) $request->padding,
                'patient_uid_serial'             => (int) $request->serial,
                'opd_consultation_count'         => (int) $request->opd_consultation_count,
                'opd_consultation_validity_days' => (int) $request->opd_consultation_validity_days,
                // English is always kept: it is the fallback the printed sheet falls back to for
                // anything without a translation, so it can never be switched off.
                'rx_languages'                   => json_encode(
                    collect($request->input('rx_languages', []))->prepend('en')->unique()->values()->all()
                ),
            ]
        );

        \Brian2694\Toastr\Facades\Toastr::success('Hospital settings saved.');
        return back();
    }
}
