<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\CentralLogics\Helpers;
use App\Models\DoctorProfile;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\Patient;
use App\Models\RadiologyEquipment;
use App\Models\RadiologyInvoice;
use App\Models\RadiologyStudy;
use App\Models\RadiologyTest;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RadiologyController extends Controller
{
    public const SLOT_TIMES = ['08:00', '09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00'];

    private function storeId()
    {
        return Helpers::get_store_id();
    }

    private function actor(): array
    {
        $emp = auth('vendor_employee')->user();
        if ($emp) {
            return [$emp->id, 'vendor_employee', trim(($emp->f_name ?? '') . ' ' . ($emp->l_name ?? ''))];
        }
        $v = auth('vendor')->user();
        return [auth('vendor')->id(), 'vendor', trim(($v->f_name ?? 'Radiologist') . ' ' . ($v->l_name ?? ''))];
    }

    // ── Schema (guarded, idempotent) ──────────────────────────────────────
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('radiology_studies')) {
            DB::statement("CREATE TABLE `radiology_studies` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL, `study_no` VARCHAR(40) NULL,
                `patient_id` BIGINT UNSIGNED NULL, `ipd_admission_id` BIGINT UNSIGNED NULL, `doctor_profile_id` BIGINT UNSIGNED NULL,
                `modality` VARCHAR(40) NULL, `study_name` VARCHAR(200) NULL, `body_part` VARCHAR(80) NULL,
                `priority` VARCHAR(20) NOT NULL DEFAULT 'routine', `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `source` VARCHAR(20) NULL, `department` VARCHAR(30) NULL, `referred_by` VARCHAR(150) NULL,
                `clinical_history` TEXT NULL, `price` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `radiology_equipment_id` BIGINT UNSIGNED NULL,
                `scheduled_at` TIMESTAMP NULL, `started_at` TIMESTAMP NULL, `reported_at` TIMESTAMP NULL,
                `findings` TEXT NULL, `impression` TEXT NULL, `recommendations` TEXT NULL, `radiologist` VARCHAR(150) NULL,
                `is_critical` TINYINT(1) NOT NULL DEFAULT 0, `critical_notified_at` TIMESTAMP NULL, `critical_notified_to` VARCHAR(150) NULL,
                `created_by` BIGINT UNSIGNED NULL, `created_by_type` VARCHAR(30) NULL,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `rs_store_idx` (`store_id`), KEY `rs_status_idx` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('radiology_tests')) {
            DB::statement("CREATE TABLE `radiology_tests` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `store_id` BIGINT UNSIGNED NULL,
                `name` VARCHAR(200) NOT NULL, `modality` VARCHAR(40) NULL, `body_part` VARCHAR(80) NULL,
                `price` DECIMAL(12,2) NOT NULL DEFAULT 0, `tat_text` VARCHAR(60) NULL, `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `rt_store_idx` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('radiology_equipment')) {
            DB::statement("CREATE TABLE `radiology_equipment` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `store_id` BIGINT UNSIGNED NULL,
                `name` VARCHAR(160) NOT NULL, `model` VARCHAR(160) NULL, `modality` VARCHAR(40) NULL, `location` VARCHAR(80) NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'online', `last_service` DATE NULL, `note` VARCHAR(255) NULL,
                `studies_total` INT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `re_store_idx` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('radiology_settings')) {
            DB::statement("CREATE TABLE `radiology_settings` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `store_id` BIGINT UNSIGNED NULL,
                `day_start` VARCHAR(5) NOT NULL DEFAULT '09:00', `day_end` VARCHAR(5) NOT NULL DEFAULT '18:00',
                `slot_minutes` INT NOT NULL DEFAULT 30, `lunch_start` VARCHAR(5) NULL, `lunch_end` VARCHAR(5) NULL,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), UNIQUE KEY `rset_store_uniq` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('radiology_invoices')) {
            DB::statement("CREATE TABLE `radiology_invoices` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `store_id` BIGINT UNSIGNED NULL,
                `radiology_study_id` BIGINT UNSIGNED NULL, `invoice_no` VARCHAR(50) NULL, `patient_id` BIGINT UNSIGNED NULL,
                `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0, `insurance_provider` VARCHAR(120) NULL,
                `insurance_covered` DECIMAL(12,2) NOT NULL DEFAULT 0, `discount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `payable` DECIMAL(12,2) NOT NULL DEFAULT 0, `payment_mode` VARCHAR(40) NULL, `status` VARCHAR(20) NOT NULL DEFAULT 'finalized',
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `ri_store_idx` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    // Sub-features (each becomes a row in the role grid with standard actions).
    public const FEATURES = [
        'radiology_study'     => ['Radiology Worklist', ['view', 'edit']],
        'radiology_viewer'    => ['Radiology DICOM Viewer', ['view']],
        'radiology_report'    => ['Radiology Reports', ['view', 'add', 'send']],
        'radiology_urgent'    => ['Radiology Urgent Findings', ['view', 'notify']],
        'radiology_schedule'  => ['Radiology Schedule', ['view', 'add', 'edit']],
        'radiology_equipment' => ['Radiology Equipment', ['view', 'add', 'edit']],
        'radiology_billing'   => ['Radiology Billing', ['view', 'add']],
    ];

    // All "view" sub-permissions — used to decide sidebar visibility / landing.
    public const VIEW_PERMS = [
        'radiology_study.view', 'radiology_viewer.view', 'radiology_report.view',
        'radiology_urgent.view', 'radiology_schedule.view', 'radiology_equipment.view', 'radiology_billing.view',
    ]; 

    public function ensurePermission(): void
    {
        if (!Schema::hasTable('features') || !Schema::hasTable('feature_permissions')) {
            return;
        }
        foreach (self::FEATURES as $name => [$display, $actions]) {
            $fid = DB::table('features')->where('name', $name)->value('id');
            if (!$fid) {
                $fid = DB::table('features')->insertGetId([
                    'name' => $name, 'display_name' => $display, 'master_module' => 'hospital_manage',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            foreach ($actions as $a) {
                if (!DB::table('feature_permissions')->where('feature_id', $fid)->where('action', $a)->exists()) {
                    DB::table('feature_permissions')->insert(['feature_id' => $fid, 'action' => $a, 'free' => 0]);
                }
            }
        }
        // Drop the legacy single 'radiology' feature now superseded by the sub-features.
        $legacy = DB::table('features')->where('name', 'radiology')->value('id');
        if ($legacy) {
            $pids = DB::table('feature_permissions')->where('feature_id', $legacy)->pluck('id');
            if ($pids->count() && Schema::hasTable('role_feature_permissions')) {
                DB::table('role_feature_permissions')->whereIn('feature_permission_id', $pids)->delete();
            }
            DB::table('feature_permissions')->where('feature_id', $legacy)->delete();
            DB::table('features')->where('id', $legacy)->delete();
        }
    }

    private function seedDefaults($storeId): void
    {
        if (!RadiologyTest::where('store_id', $storeId)->exists()) {
            $catalog = [
                ['Chest X-Ray PA View', 'X-Ray', 'Chest', 400, '20 min'],
                ['X-Ray Knee AP & Lateral', 'X-Ray', 'Knee', 350, '20 min'],
                ['X-Ray Abdomen Erect', 'X-Ray', 'Abdomen', 400, '20 min'],
                ['X-Ray Spine LS', 'X-Ray', 'Spine', 450, '20 min'],
                ['CT Head (Non-Contrast)', 'CT Scan', 'Head', 2500, '45 min'],
                ['CT Abdomen (Contrast)', 'CT Scan', 'Abdomen', 4500, '60 min'],
                ['CT Chest', 'CT Scan', 'Chest', 3500, '45 min'],
                ['MRI Brain (w/o contrast)', 'MRI', 'Brain', 6500, '40 min'],
                ['MRI Spine', 'MRI', 'Spine', 7000, '45 min'],
                ['USG Abdomen', 'Ultrasound', 'Abdomen', 900, '20 min'],
                ['USG Pelvis', 'Ultrasound', 'Pelvis', 900, '20 min'],
                ['USG KUB', 'Ultrasound', 'KUB', 900, '20 min'],
                ['ECG (12-Lead)', 'ECG', 'Cardiac', 200, '10 min'],
            ];
            foreach ($catalog as $r) {
                RadiologyTest::create(['store_id' => $storeId, 'name' => $r[0], 'modality' => $r[1], 'body_part' => $r[2], 'price' => $r[3], 'tat_text' => $r[4], 'is_active' => 1]);
            }
        }
        if (!RadiologyEquipment::where('store_id', $storeId)->exists()) {
            $equip = [
                ['Digital X-Ray (DR System)', 'Siemens Ysio Max', 'X-Ray', 'Room 1', 'online'],
                ['CT Scanner (64-Slice)', 'GE Revolution EVO', 'CT Scan', 'CT Suite', 'online'],
                ['MRI Scanner (1.5T)', 'Philips Ingenia 1.5T', 'MRI', 'MRI Suite', 'maintenance'],
                ['Ultrasound System', 'GE LOGIQ E10', 'Ultrasound', 'USG Room', 'online'],
            ];
            foreach ($equip as $e) {
                RadiologyEquipment::create(['store_id' => $storeId, 'name' => $e[0], 'model' => $e[1], 'modality' => $e[2], 'location' => $e[3], 'status' => $e[4], 'last_service' => now()->subDays(rand(5, 30))]);
            }
        }
    }

    private function boot(): void
    {
        $this->ensureSchema();
        $this->ensurePermission();
        $this->seedDefaults($this->storeId());
    }

    // Per-store schedule settings (operating hours, slot interval, optional lunch break).
    private function settings($storeId)
    {
        $s = DB::table('radiology_settings')->where('store_id', $storeId)->first();
        if (!$s) {
            DB::table('radiology_settings')->insert([
                'store_id' => $storeId, 'day_start' => '09:00', 'day_end' => '18:00', 'slot_minutes' => 30,
                'lunch_start' => '13:00', 'lunch_end' => '14:00', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $s = DB::table('radiology_settings')->where('store_id', $storeId)->first();
        }
        return $s;
    }

    // Build the day's slot times from the store settings.
    private function slotTimes($settings): array
    {
        $times = [];
        try {
            $toMin = fn($hi) => (int) substr($hi, 0, 2) * 60 + (int) substr($hi, 3, 2);
            $start = $toMin($settings->day_start);
            $end   = $toMin($settings->day_end);
            $step  = max(5, (int) $settings->slot_minutes);
            $ls = $settings->lunch_start ? $toMin($settings->lunch_start) : null;
            $le = $settings->lunch_end ? $toMin($settings->lunch_end) : null;
            for ($m = $start, $g = 0; $m < $end && $g < 300; $m += $step, $g++) {
                if ($ls !== null && $le !== null && $m >= $ls && $m < $le) {
                    continue;
                }
                $times[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
            }
        } catch (\Throwable $e) {
            // ignore — falls through to default below
        }
        return $times ?: self::SLOT_TIMES;
    }

    private function chrome(): array
    {
        $storeId = $this->storeId();
        $today = today();
        $base = fn() => RadiologyStudy::where('store_id', $storeId);

        $revenueToday = RadiologyInvoice::where('store_id', $storeId)->whereDate('created_at', $today)->sum('payable');
        if (!$revenueToday) {
            $revenueToday = $base()->whereDate('created_at', $today)->sum('price');
        }

        $stats = [
            'pending'      => $base()->where('status', 'pending')->count(),
            'in_progress'  => $base()->where('status', 'in_progress')->count(),
            'reports_ready' => $base()->whereIn('status', ['verified', 'sent'])->whereDate('updated_at', $today)->count(),
            'urgent'       => $base()->where('is_critical', 1)->whereNull('critical_notified_at')->count(),
            'total_today'  => $base()->whereDate('created_at', $today)->count(),
            'equip_online' => RadiologyEquipment::where('store_id', $storeId)->where('status', 'online')->count(),
            'equip_maint'  => RadiologyEquipment::where('store_id', $storeId)->where('status', '!=', 'online')->count(),
            'revenue'      => $revenueToday,
        ];

        $urgentAlerts = RadiologyStudy::with('patient')->where('store_id', $storeId)
            ->where('is_critical', 1)->whereNull('critical_notified_at')->latest()->take(5)->get();

        return ['radStats' => $stats, 'urgentAlerts' => $urgentAlerts];
    }

    private function view(string $name, array $data = [])
    {
        return view('hmis::vendor.radiology.' . $name, array_merge($this->chrome(), $data));
    }

    private function study($id): RadiologyStudy
    {
        return RadiologyStudy::where('store_id', $this->storeId())->findOrFail($id);
    }

    // ── TAB 1: Worklist ────────────────────────────────────────────────────
    // Sidebar landing — redirect to the first radiology tab the user can actually open.
    public function home()
    {
        $this->boot();
        if (auth('vendor')->check()) {
            return redirect()->route('vendor.radiology.worklist');
        }
        $map = [
            'radiology_study.view'     => 'vendor.radiology.worklist',
            'radiology_report.view'    => 'vendor.radiology.reports',
            'radiology_urgent.view'    => 'vendor.radiology.urgent',
            'radiology_schedule.view'  => 'vendor.radiology.schedule',
            'radiology_equipment.view' => 'vendor.radiology.equipment',
            'radiology_billing.view'   => 'vendor.radiology.billing',
            'radiology_viewer.view'    => 'vendor.radiology.viewer',
            'radiology_report.add'     => 'vendor.radiology.report',
        ];
        foreach ($map as $perm => $route) {
            [$f, $a] = explode('.', $perm);
            if (hasPermission($f, $a)) {
                return redirect()->route($route);
            }
        }
        abort(403);
    }

    public function worklist(Request $request)
    {
        $this->boot();
        $studies = RadiologyStudy::where('store_id', $this->storeId())
            ->with(['patient', 'doctorProfile.employee'])
            ->whereDate('created_at', '>=', today()->subDays(3))
            ->when($request->modality, fn($q) => $q->where('modality', $request->modality))
            ->latest()->get();
        $equipment = RadiologyEquipment::where('store_id', $this->storeId())->get();
        return $this->view('worklist', compact('studies', 'equipment'));
    }

    public function startScan($id)
    {
        $this->boot();
        $study = $this->study($id);
        if ($study->status === 'pending') {
            $study->update(['status' => 'in_progress', 'started_at' => now()]);
        }
        Toastr::success($study->study_no . ' — scan started.');
        return redirect()->route('vendor.radiology.viewer', ['study' => $study->id]);
    }

    // ── TAB 2: DICOM Viewer ────────────────────────────────────────────────
    public function viewer(Request $request)
    {
        $this->boot();
        $studies = RadiologyStudy::where('store_id', $this->storeId())->whereIn('status', ['in_progress', 'pending', 'reported'])->with('patient')->latest()->get();
        $study = $request->study ? RadiologyStudy::where('store_id', $this->storeId())->with('patient', 'doctorProfile.employee')->find($request->study) : $studies->first();
        if ($study && !$study->relationLoaded('patient')) {
            $study->load('patient', 'doctorProfile.employee');
        }
        return $this->view('viewer', compact('study', 'studies'));
    }

    // ── TAB 3: Report Writing ──────────────────────────────────────────────
    public function reportForm(Request $request)
    {
        $this->boot();
        $pickable = RadiologyStudy::where('store_id', $this->storeId())->whereIn('status', ['in_progress', 'pending', 'reported'])->with('patient')->latest()->get();
        $study = $request->study ? RadiologyStudy::where('store_id', $this->storeId())->with('patient', 'doctorProfile.employee')->find($request->study) : $pickable->first();
        return $this->view('report', compact('study', 'pickable'));
    }

    public function saveReport(Request $request, $id)
    {
        $this->boot();
        [, , $name] = $this->actor();
        $study = $this->study($id);

        $study->fill([
            'clinical_history' => $request->clinical_history,
            'findings'         => $request->findings,
            'impression'       => $request->impression,
            'recommendations'  => $request->recommendations,
            'radiologist'      => $request->radiologist ?: $name,
            'is_critical'      => $request->boolean('is_critical'),
        ]);

        if ($request->boolean('finalize')) {
            $study->status = 'verified';
            $study->reported_at = now();
            Toastr::success('Report ' . $study->study_no . ' finalized.');
        } else {
            if ($study->status === 'in_progress') {
                $study->status = 'reported';
            }
            Toastr::success('Report draft saved.');
        }
        $study->save();

        return $request->boolean('finalize') ? redirect()->route('vendor.radiology.reports') : back();
    }

    // ── TAB 4: Reports ─────────────────────────────────────────────────────
    public function reports(Request $request)
    {
        $this->boot();
        $studies = RadiologyStudy::where('store_id', $this->storeId())
            ->whereIn('status', ['verified', 'sent'])->with('patient')
            ->when($request->search, fn($q) => $q->where('study_no', 'like', "%{$request->search}%")
                ->orWhereHas('patient', fn($p) => $p->where('name', 'like', "%{$request->search}%")))
            ->when($request->modality, fn($q) => $q->where('modality', $request->modality))
            ->latest()->paginate(25)->withQueryString();
        return $this->view('reports', compact('studies'));
    }

    public function sendReport($id)
    {
        $this->boot();
        $study = $this->study($id);
        $study->update(['status' => 'sent']);
        Toastr::success('Report ' . $study->study_no . ' sent to referring doctor.');
        return back();
    }

    public function report($id)
    {
        $this->boot();
        $study = RadiologyStudy::where('store_id', $this->storeId())->with('patient', 'doctorProfile.employee')->findOrFail($id);
        $store = Helpers::get_store_data();
        return view('hmis::vendor.radiology.report_print', compact('study', 'store'));
    }

    // ── TAB 5: Urgent Findings ─────────────────────────────────────────────
    public function urgent()
    {
        $this->boot();
        $storeId = $this->storeId();
        $open = RadiologyStudy::with('patient', 'doctorProfile.employee')->where('store_id', $storeId)
            ->where('is_critical', 1)->whereNull('critical_notified_at')->latest()->get();
        $resolved = RadiologyStudy::with('patient')->where('store_id', $storeId)
            ->where('is_critical', 1)->whereNotNull('critical_notified_at')->latest()->take(20)->get();
        return $this->view('urgent', compact('open', 'resolved'));
    }

    public function notifyUrgent(Request $request, $id)
    {
        $this->boot();
        $study = $this->study($id);
        $doc = $study->doctorProfile ? 'Dr. ' . trim(($study->doctorProfile->employee->f_name ?? '') . ' ' . ($study->doctorProfile->employee->l_name ?? '')) : ($request->doctor ?: $study->referred_by);
        $study->update(['critical_notified_at' => now(), 'critical_notified_to' => $doc ?: 'Referring doctor']);
        Toastr::success($study->critical_notified_to . ' notified — urgent finding.');
        return back();
    }

    // ── TAB 6: Appointment Schedule ────────────────────────────────────────
    public function schedule(Request $request)
    {
        $this->boot();
        $storeId = $this->storeId();
        $date = $request->date ?: today()->toDateString();
        $modality = $request->modality;

        $dayStudies = RadiologyStudy::where('store_id', $storeId)
            ->whereDate('scheduled_at', $date)
            ->when($modality, fn($q) => $q->where('modality', $modality))
            ->with('patient')->get();

        $settings = $this->settings($storeId);
        $times = $this->slotTimes($settings);
        $step = max(5, (int) $settings->slot_minutes);
        $toMin = fn($hi) => (int) substr($hi, 0, 2) * 60 + (int) substr($hi, 3, 2);

        // Place each scheduled study into the slot whose interval contains its time.
        $slots = [];
        foreach ($times as $t) {
            $tMin = $toMin($t);
            $slots[$t] = $dayStudies->first(function ($s) use ($tMin, $step, $toMin) {
                if (!$s->scheduled_at) {
                    return false;
                }
                $m = $toMin($s->scheduled_at->format('H:i'));
                return $m >= $tMin && $m < $tMin + $step;
            });
        }

        $patients = Patient::where('store_id', $storeId)->orderBy('name')->get(['id', 'name', 'patient_uid', 'gender', 'dob']);
        $doctors = DoctorProfile::where('store_id', $storeId)->with('employee:id,f_name,l_name')->get();
        $tests = RadiologyTest::where('store_id', $storeId)->where('is_active', 1)->orderBy('modality')->orderBy('name')->get();

        return $this->view('schedule', compact('slots', 'date', 'modality', 'patients', 'doctors', 'tests', 'settings'));
    }

    public function saveSettings(Request $request)
    {
        $this->boot();
        $request->validate([
            'day_start'    => 'required|date_format:H:i',
            'day_end'      => 'required|date_format:H:i|after:day_start',
            'slot_minutes' => 'required|integer|min:5|max:240',
        ]);
        DB::table('radiology_settings')->updateOrInsert(
            ['store_id' => $this->storeId()],
            [
                'day_start'    => $request->day_start,
                'day_end'      => $request->day_end,
                'slot_minutes' => $request->slot_minutes,
                'lunch_start'  => $request->lunch_start ?: null,
                'lunch_end'    => $request->lunch_end ?: null,
                'updated_at'   => now(),
            ]
        );
        Toastr::success('Schedule hours updated.');
        return back();
    }

    public function orderStore(Request $request)
    {
        $this->boot();
        $request->validate(['patient_id' => 'required|exists:patients,id', 'study_name' => 'required|string|max:200']);
        $storeId = $this->storeId();
        [$actorId, $actorType] = $this->actor();

        $test = RadiologyTest::where('store_id', $storeId)->where('name', $request->study_name)->first();
        $price = $request->filled('price') ? $request->price : ($test->price ?? 0);

        $study = RadiologyStudy::create([
            'store_id' => $storeId, 'patient_id' => $request->patient_id,
            'doctor_profile_id' => $request->doctor_profile_id ?: null,
            'modality' => $request->modality ?: ($test->modality ?? 'X-Ray'),
            'study_name' => $request->study_name, 'body_part' => $test->body_part ?? null,
            'priority' => $request->priority ?: 'routine', 'status' => 'pending',
            'source' => $request->source ?: 'walkin', 'department' => $request->department ?: 'OPD',
            'referred_by' => $request->referred_by, 'clinical_history' => $request->clinical_history,
            'price' => $price, 'scheduled_at' => $request->scheduled_at ?: now(),
            'created_by' => $actorId, 'created_by_type' => $actorType,
        ]);
        $study->update(['study_no' => 'RAD-' . str_pad($study->id, 4, '0', STR_PAD_LEFT)]);

        Toastr::success('Study ' . $study->study_no . ' booked.');
        return redirect()->route('vendor.radiology.worklist');
    }

    // ── TAB 7: Equipment ───────────────────────────────────────────────────
    public function equipment()
    {
        $this->boot();
        $equipment = RadiologyEquipment::where('store_id', $this->storeId())->orderBy('name')->get();
        return $this->view('equipment', compact('equipment'));
    }

    public function saveEquipment(Request $request)
    {
        $this->boot();
        $request->validate(['name' => 'required|string|max:160']);
        RadiologyEquipment::create([
            'store_id' => $this->storeId(), 'name' => $request->name, 'model' => $request->model,
            'modality' => $request->modality, 'location' => $request->location, 'status' => $request->status ?: 'online',
            'last_service' => $request->last_service ?: null, 'note' => $request->note,
        ]);
        Toastr::success('Equipment added.');
        return back();
    }

    public function updateEquipment(Request $request, $id)
    {
        $this->boot();
        $e = RadiologyEquipment::where('store_id', $this->storeId())->findOrFail($id);
        $e->update($request->only(['name', 'model', 'modality', 'location', 'status', 'last_service', 'note']));
        Toastr::success('Equipment updated.');
        return back();
    }

    // ── TAB 8: Billing ─────────────────────────────────────────────────────
    public function billing(Request $request)
    {
        $this->boot();
        $storeId = $this->storeId();

        $study = $request->study ? RadiologyStudy::where('store_id', $storeId)->with('patient')->find($request->study) : null;
        $billable = RadiologyStudy::where('store_id', $storeId)
            ->whereNotIn('id', RadiologyInvoice::where('store_id', $storeId)->pluck('radiology_study_id'))
            ->with('patient')->latest()->take(40)->get();

        $today = today();
        $revenue = [
            'billed'  => RadiologyInvoice::where('store_id', $storeId)->whereDate('created_at', $today)->sum('subtotal'),
            'insured' => RadiologyInvoice::where('store_id', $storeId)->whereDate('created_at', $today)->sum('insurance_covered'),
            'cash'    => RadiologyInvoice::where('store_id', $storeId)->whereDate('created_at', $today)->sum('payable'),
            'count'   => RadiologyInvoice::where('store_id', $storeId)->whereDate('created_at', $today)->count(),
        ];
        $recent = RadiologyInvoice::where('store_id', $storeId)->with('patient', 'study')->latest()->take(8)->get();

        return $this->view('billing', compact('study', 'billable', 'revenue', 'recent'));
    }

    public function generateInvoice(Request $request, $id)
    {
        $this->boot();
        $storeId = $this->storeId();
        $study = $this->study($id);

        $request->validate([
            'transaction_id' => 'required_if:payment_mode,Online,Card,UPI|nullable|string|max:100',
        ]);

        $subtotal = (float) $study->price + (float) ($request->reading_fee ?: 0);
        $insurance = (float) ($request->insurance_covered ?: 0);
        $discount = (float) ($request->discount ?: 0);
        $payable = max(0, $subtotal - $insurance - $discount);
        $taxType = 'non-gst';
        $mode = strtolower($request->payment_mode ?: 'cash');
        $isOnline = in_array($mode, ['online', 'card', 'upi']);

        DB::beginTransaction();
        try {
            $invoiceId = Helpers::generateInvoiceId('H', true, null, $taxType);
            $manual = ManualInvoice::create([
                'invoice_id' => $invoiceId, 'invoice_serial' => (int) substr($invoiceId, strrpos($invoiceId, '_') + 1),
                'financial_year' => _currentFinancialYear(), 'bill_to' => $study->patient_id, 'bill_to_type' => 'patient',
                'user_type' => 'hospital_patient', 'vendor_id' => $storeId, 'total_amount' => $payable,
                'payment_status' => 'Paid', 'payment_method' => $request->payment_mode ?: 'Cash',
                'payment_date' => now()->toDateString(), 'invoice_date' => now()->toDateString(), 'tax_type' => $taxType,
                'cash_amount' => $isOnline ? 0 : $payable, 'online_amount' => $isOnline ? $payable : 0,
                'reference_number' => $isOnline && $request->transaction_id ? ['transaction_id' => $request->transaction_id] : [],
                'meta' => $isOnline && $request->transaction_id ? ['transaction_id' => $request->transaction_id] : null,
            ]);
            InvoiceItem::create([
                'rand_invoice_id' => $invoiceId, 'manual_invoice_id' => $manual->id,
                'name' => $study->study_name . ' (' . $study->modality . ')', 'qty' => 1, 'price' => $study->price, 'tax' => 0, 'gst_status' => 'excluding',
            ]);
            if ($request->reading_fee) {
                InvoiceItem::create(['rand_invoice_id' => $invoiceId, 'manual_invoice_id' => $manual->id, 'name' => 'Radiologist Reading Fee', 'qty' => 1, 'price' => $request->reading_fee, 'tax' => 0, 'gst_status' => 'excluding']);
            }
            RadiologyInvoice::updateOrCreate(
                ['radiology_study_id' => $study->id],
                ['store_id' => $storeId, 'patient_id' => $study->patient_id, 'invoice_no' => $invoiceId,
                 'subtotal' => $subtotal, 'insurance_provider' => $request->insurance_provider, 'insurance_covered' => $insurance,
                 'discount' => $discount, 'payable' => $payable, 'payment_mode' => $request->payment_mode ?: 'cash', 'status' => 'finalized']
            );
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Toastr::error('Could not create invoice: ' . $e->getMessage());
            return back();
        }

        try {
            $data = _createBillPdf($manual, 'vendor');
            $manual->update(['pdf' => $data['pdf']]);
        } catch (\Throwable $e) {
        }

        Toastr::success('Radiology invoice ' . $invoiceId . ' finalized.');
        return back();
    }
}
