<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\CentralLogics\Helpers;
use App\Models\IpdAdmission;
use App\Models\NursingFluidEntry;
use App\Models\NursingHandover;
use App\Models\NursingMarAdmin;
use App\Models\NursingMarOrder;
use App\Models\NursingNote;
use App\Models\NursingTask;
use App\Models\NursingVital;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NursingStationController extends Controller
{
    // Standard MAR administration times (the 8-slot grid in the mockup).
    public const MAR_TIMES = ['06:00', '08:00', '10:00', '12:00', '14:00', '18:00', '20:00', '22:00'];

    private function storeId()
    {
        return Helpers::get_store_id();
    }

    private function actor(): array
    {
        $emp = auth('vendor_employee')->user();
        if ($emp) {
            return [$emp->id, trim(($emp->f_name ?? '') . ' ' . ($emp->l_name ?? ''))];
        }
        $v = auth('vendor')->user();
        return [auth('vendor')->id(), trim(($v->f_name ?? 'Nurse') . ' ' . ($v->l_name ?? ''))];
    }

    private function shiftNow(): string
    {
        $h = (int) now()->format('H');
        if ($h >= 7 && $h < 15) return 'Morning';
        if ($h >= 15 && $h < 23) return 'Evening';
        return 'Night';
    }

    // ── Schema (guarded, idempotent — no migration files) ──────────────────
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('nursing_vitals')) {
            DB::statement("CREATE TABLE `nursing_vitals` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `patient_id` BIGINT UNSIGNED NULL,
                `ipd_admission_id` BIGINT UNSIGNED NULL,
                `bp_systolic` INT NULL, `bp_diastolic` INT NULL,
                `hr` INT NULL, `temp` DECIMAL(5,1) NULL, `spo2` INT NULL,
                `rr` INT NULL, `pain` INT NULL,
                `recorded_by` BIGINT UNSIGNED NULL, `recorded_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `nv_adm_idx` (`ipd_admission_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('nursing_mar_orders')) {
            DB::statement("CREATE TABLE `nursing_mar_orders` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `patient_id` BIGINT UNSIGNED NULL,
                `ipd_admission_id` BIGINT UNSIGNED NULL,
                `medicine_name` VARCHAR(200) NOT NULL,
                `dose` VARCHAR(120) NULL,
                `route` VARCHAR(10) NULL DEFAULT 'PO',
                `frequency` VARCHAR(120) NULL,
                `schedule_times` TEXT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `nmo_adm_idx` (`ipd_admission_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('nursing_mar_admins')) {
            DB::statement("CREATE TABLE `nursing_mar_admins` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `nursing_mar_order_id` BIGINT UNSIGNED NOT NULL,
                `admin_date` DATE NULL,
                `scheduled_time` VARCHAR(5) NULL,
                `status` VARCHAR(12) NOT NULL DEFAULT 'given',
                `administered_by` BIGINT UNSIGNED NULL, `administered_at` TIMESTAMP NULL,
                `missed_reason` VARCHAR(255) NULL,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `nma_order_idx` (`nursing_mar_order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('nursing_fluid_entries')) {
            DB::statement("CREATE TABLE `nursing_fluid_entries` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `patient_id` BIGINT UNSIGNED NULL,
                `ipd_admission_id` BIGINT UNSIGNED NULL,
                `entry_date` DATE NULL, `entry_time` VARCHAR(5) NULL,
                `description` VARCHAR(255) NULL,
                `type` VARCHAR(5) NOT NULL DEFAULT 'in',
                `volume_ml` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `recorded_by` BIGINT UNSIGNED NULL,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `nfe_adm_idx` (`ipd_admission_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('nursing_tasks')) {
            DB::statement("CREATE TABLE `nursing_tasks` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `ward_id` BIGINT UNSIGNED NULL,
                `ipd_admission_id` BIGINT UNSIGNED NULL,
                `bed_label` VARCHAR(40) NULL,
                `task_date` DATE NULL, `due_time` VARCHAR(5) NULL,
                `description` VARCHAR(255) NOT NULL,
                `priority` VARCHAR(12) NOT NULL DEFAULT 'normal',
                `shift` VARCHAR(12) NULL,
                `status` VARCHAR(12) NOT NULL DEFAULT 'pending',
                `done_by` BIGINT UNSIGNED NULL, `done_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `nt_store_idx` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('nursing_handovers')) {
            DB::statement("CREATE TABLE `nursing_handovers` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `ward_id` BIGINT UNSIGNED NULL,
                `handover_date` DATE NULL, `shift` VARCHAR(12) NULL,
                `outgoing_nurse` VARCHAR(120) NULL, `incoming_nurse` VARCHAR(120) NULL,
                `notes` TEXT NULL, `completed_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `nh_store_idx` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public const FEATURES = [
        'nursing_vitals'   => ['Nursing Vitals', ['view', 'add']],
        'nursing_mar'      => ['Nursing Medication (MAR)', ['view', 'add', 'edit']],
        'nursing_fluid'    => ['Nursing Fluid Balance', ['view', 'add']],
        'nursing_note'     => ['Nursing Notes', ['view', 'add']],
        'nursing_task'     => ['Nursing Tasks', ['view', 'add', 'edit']],
        'nursing_handover' => ['Nursing Handover', ['view', 'edit']],
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
        $legacy = DB::table('features')->where('name', 'nursing_station')->value('id');
        if ($legacy) {
            $pids = DB::table('feature_permissions')->where('feature_id', $legacy)->pluck('id');
            if ($pids->count() && Schema::hasTable('role_feature_permissions')) {
                DB::table('role_feature_permissions')->whereIn('feature_permission_id', $pids)->delete();
            }
            DB::table('feature_permissions')->where('feature_id', $legacy)->delete();
            DB::table('features')->where('id', $legacy)->delete();
        }
    }

    public const VIEW_PERMS = [
        'nursing_vitals.view', 'nursing_mar.view', 'nursing_fluid.view',
        'nursing_note.view', 'nursing_task.view', 'nursing_handover.view',
    ];

    private function authorizeAccess(): void
    {
        if (auth('vendor')->check()) {
            return;
        }
        if (!hasAnyPermission(self::VIEW_PERMS)) {
            abort(403);
        }
    }

    private function boot(): void
    {
        $this->ensureSchema();
        $this->ensurePermission();
    }

    private function severity(?NursingVital $v): string
    {
        if (!$v) return 'obs';
        if (($v->spo2 !== null && $v->spo2 < 92) || ($v->bp_systolic !== null && $v->bp_systolic >= 160) || ($v->temp !== null && $v->temp >= 103)) {
            return 'crit';
        }
        if (($v->spo2 !== null && $v->spo2 < 95) || ($v->bp_systolic !== null && $v->bp_systolic >= 140) || ($v->temp !== null && $v->temp >= 100.4)) {
            return 'warn';
        }
        return 'stable';
    }

    // ── Main page ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $this->boot();
        $this->authorizeAccess();
        $storeId = $this->storeId();

        $admissions = IpdAdmission::where('store_id', $storeId)
            ->where('status', 'admitted')
            ->with(['patient', 'bed', 'ward', 'doctorProfile.employee'])
            ->orderBy('ward_id')->orderBy('bed_id')
            ->get();

        // latest vital + severity per admission (for ward list dots)
        $latestVitals = [];
        foreach ($admissions as $a) {
            $latestVitals[$a->id] = NursingVital::where('ipd_admission_id', $a->id)->latest('recorded_at')->latest('id')->first();
            $a->severity = $this->severity($latestVitals[$a->id]);
        }

        $wardId = $request->get('ward');
        $current = $admissions->firstWhere('id', $request->get('admission')) ?? $admissions->first();

        $emptyBeds = collect();
        if ($current && $current->ward_id) {
            $emptyBeds = \App\Models\Bed::where('store_id', $storeId)->where('ward_id', $current->ward_id)
                ->where('status', 'available')->pluck('bed_number');
        }

        // ── Selected-patient data ──
        $vital = $current ? $latestVitals[$current->id] : null;
        $vitalsTrend = $current
            ? NursingVital::where('ipd_admission_id', $current->id)->whereDate('recorded_at', today())->orderBy('recorded_at')->get()
            : collect();

        $marRows = [];
        if ($current) {
            $orders = NursingMarOrder::where('ipd_admission_id', $current->id)->where('is_active', 1)
                ->with(['admins' => fn($q) => $q->whereDate('admin_date', today())])->orderBy('id')->get();
            $nowT = now()->format('H:i');
            foreach ($orders as $o) {
                $times = $o->schedule_times ?: [];
                $byTime = $o->admins->keyBy('scheduled_time');
                $cells = [];
                foreach (self::MAR_TIMES as $t) {
                    if (!in_array($t, $times)) {
                        $cells[$t] = ['state' => 'na'];
                    } elseif ($byTime->has($t)) {
                        $cells[$t] = ['state' => $byTime[$t]->status];
                    } elseif ($t <= $nowT) {
                        $cells[$t] = ['state' => 'due'];
                    } else {
                        $cells[$t] = ['state' => 'future'];
                    }
                }
                $marRows[] = ['order' => $o, 'cells' => $cells];
            }
        }

        // ── Fluid balance (today) ──
        $fluids = $current
            ? NursingFluidEntry::where('ipd_admission_id', $current->id)->whereDate('entry_date', today())->orderBy('entry_time')->get()
            : collect();
        $fbIn = $fluids->where('type', 'in')->sum('volume_ml');
        $fbOut = $fluids->where('type', 'out')->sum('volume_ml');
        $fbNet = $fbIn - $fbOut;

        // ── Nursing notes ──
        $notes = $current
            ? NursingNote::where('ipd_admission_id', $current->id)->latest('recorded_at')->latest('id')->take(20)->get()
            : collect();

        // ── Ward tasks (today) — ward-wide list for the overview / handover ──
        $tasks = NursingTask::where('store_id', $storeId)
            ->whereDate('task_date', today())
            ->when($current && $current->ward_id, fn($q) => $q->where(fn($w) => $w->where('ward_id', $current->ward_id)->orWhereNull('ward_id')))
            ->orderBy('due_time')->get();

        // ── Tasks for the selected patient only (Task Queue tab) — plus any unassigned ward task ──
        $patientTasks = $current
            ? NursingTask::where('store_id', $storeId)
                ->whereDate('task_date', today())
                ->where(fn($w) => $w->where('ipd_admission_id', $current->id)->orWhereNull('ipd_admission_id'))
                ->orderBy('due_time')->get()
            : collect();

        // ── Handover (today / current shift) ──
        $handover = NursingHandover::where('store_id', $storeId)
            ->when($current && $current->ward_id, fn($q) => $q->where('ward_id', $current->ward_id))
            ->whereDate('handover_date', today())->latest()->first();

        // ── Names for note authors ──
        $nurseNames = VendorEmployee::whereIn('id', $notes->pluck('recorded_by')->filter()->unique())
            ->get()->mapWithKeys(fn($e) => [$e->id => trim(($e->f_name ?? '') . ' ' . ($e->l_name ?? ''))]);

        // ── Ward summary ──
        $summary = [
            'occupied' => $admissions->count(),
            'critical' => $admissions->where('severity', 'crit')->count(),
            'warning'  => $admissions->where('severity', 'warn')->count(),
            'stable'   => $admissions->whereNotIn('severity', ['crit', 'warn'])->count(),
        ];

        [, $nurseName] = $this->actor();

        // Logged-in nurse's punch in/out + extra duty for today.
        $duty = \App\Models\Attendance::dutySummary(auth('vendor_employee')->user(), $storeId);

        // Approved shift swaps effective today — to flag covered-out / covering nurses.
        \App\Models\ShiftSwap::ensureTable();
        $todaySwaps = \App\Models\ShiftSwap::where('store_id', $storeId)
            ->whereDate('swap_date', today())
            ->where('status', 'approved')
            ->with('fromEmployee')
            ->get();
        $coveredOutIds = $todaySwaps->pluck('from_emp_id')->all();
        $coveringFor = []; // to_emp_id => "from name"
        foreach ($todaySwaps as $s) {
            $coveringFor[$s->to_emp_id] = trim(($s->fromEmployee->f_name ?? '') . ' ' . ($s->fromEmployee->l_name ?? ''));
        }

        // Roster: every nurse's clock-in status + overtime + shift + allotted patients + tasks.
        IpdAdmission::ensureNurseAssignTable();
        $nurseRoster = \App\Models\NurseProfile::where('store_id', $storeId)
            ->with(['employee.storeShift', 'admissions' => fn($q) => $q->where('ipd_admissions.status', '!=', 'discharged')->with('patient', 'bed')])
            ->get()
            ->map(function ($n) use ($storeId, $coveredOutIds, $coveringFor) {
                $d = \App\Models\Attendance::dutySummary($n->employee, $storeId);
                $empId = $n->employee->id ?? null;
                $shift = $n->employee?->storeShift;

                $patients = $n->admissions->map(fn($a) => [
                    'id'   => $a->id,
                    'bed'  => $a->bed->bed_number ?? '—',
                    'name' => $a->patient->name ?? 'Patient',
                ])->values();

                $admissionIds = $n->admissions->pluck('id')->all();
                $pendingTasks = 0; $totalTasks = 0;
                if (!empty($admissionIds)) {
                    $totalTasks   = NursingTask::where('store_id', $storeId)->whereIn('ipd_admission_id', $admissionIds)->whereDate('task_date', today())->count();
                    $pendingTasks = NursingTask::where('store_id', $storeId)->whereIn('ipd_admission_id', $admissionIds)->whereDate('task_date', today())->where('status', 'pending')->count();
                }

                return [
                    'name'        => trim(($n->employee->f_name ?? '') . ' ' . ($n->employee->l_name ?? '')) ?: ('Nurse #' . $n->id),
                    'shift'       => $shift?->name,
                    'shift_hours' => $shift && $shift->start_time && $shift->end_time
                        ? \Carbon\Carbon::parse($shift->start_time)->format('h:i A') . ' – ' . \Carbon\Carbon::parse($shift->end_time)->format('h:i A') : null,
                    'logged_in'   => (bool) ($n->employee->is_logged_in ?? false),
                    'clocked'     => $d['has'],
                    'on_duty'     => $d['has'] && empty($d['out_time']),
                    'in'          => $d['in_time'],
                    'out'         => $d['out_time'],
                    'worked'      => $d['worked_label'],
                    'extra'       => $d['extra_label'],
                    'covered_out' => $empId && in_array($empId, $coveredOutIds),
                    'covering_for'=> $empId ? ($coveringFor[$empId] ?? null) : null,
                    'patients'    => $patients,
                    'pending_tasks' => $pendingTasks,
                    'total_tasks'   => $totalTasks,
                ];
            })
            ->sortByDesc(fn($r) => ($r['logged_in'] ? 2 : 0) + ($r['on_duty'] ? 1 : 0))
            ->values();

        // All-days attendance (per nurse) for the selected month — for the Attendance tab.
        $attMonth = $request->get('att_month', now()->format('Y-m'));
        $attMonthLabel = \Carbon\Carbon::createFromFormat('Y-m', $attMonth)->format('F Y');
        $attPrev = \Carbon\Carbon::createFromFormat('Y-m', $attMonth)->subMonth()->format('Y-m');
        $attNext = \Carbon\Carbon::createFromFormat('Y-m', $attMonth)->addMonth()->format('Y-m');
        $nurseAttendance = \App\Models\NurseProfile::where('store_id', $storeId)
            ->with('employee')
            ->get()
            ->map(fn($n) => [
                'name' => trim(($n->employee->f_name ?? '') . ' ' . ($n->employee->l_name ?? '')) ?: ('Nurse #' . $n->id),
                'rows' => \App\Models\Attendance::dutyHistory($n->employee, $storeId, $attMonth),
            ])
            ->values();

        return view('hmis::vendor.nursing.index', compact(
            'admissions', 'current', 'vital', 'vitalsTrend', 'marRows', 'fluids', 'fbIn', 'fbOut', 'fbNet',
            'notes', 'tasks', 'patientTasks', 'handover', 'nurseNames', 'summary', 'emptyBeds', 'nurseName', 'duty', 'nurseRoster',
            'nurseAttendance', 'attMonth', 'attMonthLabel', 'attPrev', 'attNext'
        ))->with('marTimes', self::MAR_TIMES)->with('shift', $this->shiftNow());
    }

    private function admission($id): IpdAdmission
    {
        return IpdAdmission::where('store_id', $this->storeId())->findOrFail($id);
    }

    // ── Record a vital ──
    public function recordVital(Request $request, $admissionId)
    {
        $this->boot();
        [$actorId] = $this->actor();
        $adm = $this->admission($admissionId);

        $latest = NursingVital::where('ipd_admission_id', $adm->id)->latest('recorded_at')->latest('id')->first();
        $data = [
            'store_id' => $this->storeId(), 'patient_id' => $adm->patient_id, 'ipd_admission_id' => $adm->id,
            'bp_systolic' => $latest->bp_systolic ?? null, 'bp_diastolic' => $latest->bp_diastolic ?? null,
            'hr' => $latest->hr ?? null, 'temp' => $latest->temp ?? null, 'spo2' => $latest->spo2 ?? null,
            'rr' => $latest->rr ?? null, 'pain' => $latest->pain ?? null,
            'recorded_by' => $actorId, 'recorded_at' => now(),
        ];

        switch ($request->metric) {
            case 'bp':
                $parts = explode('/', str_replace(' ', '', (string) $request->value));
                $data['bp_systolic'] = (int) ($parts[0] ?? 0);
                $data['bp_diastolic'] = (int) ($parts[1] ?? 0);
                break;
            case 'hr':   $data['hr'] = (int) $request->value; break;
            case 'temp': $data['temp'] = (float) $request->value; break;
            case 'spo2': $data['spo2'] = (int) preg_replace('/[^0-9]/', '', (string) $request->value); break;
            case 'rr':   $data['rr'] = (int) $request->value; break;
            case 'pain': $data['pain'] = (int) preg_replace('/[^0-9]/', '', (string) $request->value); break;
        }

        NursingVital::create($data);
        Toastr::success(strtoupper($request->metric) . ' recorded.');
        return back();
    }

    // ── MAR ──
    public function marAddOrder(Request $request, $admissionId)
    {
        $this->boot();
        $adm = $this->admission($admissionId);
        $request->validate(['medicine_name' => 'required|string|max:200']);

        $times = collect(explode(',', (string) $request->schedule_times))
            ->map(fn($t) => trim($t))->filter()->values()->all();

        NursingMarOrder::create([
            'store_id' => $this->storeId(), 'patient_id' => $adm->patient_id, 'ipd_admission_id' => $adm->id,
            'medicine_name' => $request->medicine_name, 'dose' => $request->dose,
            'route' => $request->route ?: 'PO', 'frequency' => $request->frequency,
            'schedule_times' => $times, 'is_active' => 1,
        ]);
        Toastr::success('Medication order added to MAR.');
        return back();
    }

    public function marGive(Request $request, $orderId)
    {
        $this->boot();
        [$actorId] = $this->actor();
        $order = NursingMarOrder::where('store_id', $this->storeId())->findOrFail($orderId);
        $time = $request->time;

        NursingMarAdmin::updateOrCreate(
            ['nursing_mar_order_id' => $order->id, 'admin_date' => today()->toDateString(), 'scheduled_time' => $time],
            ['status' => 'given', 'administered_by' => $actorId, 'administered_at' => now(), 'missed_reason' => null]
        );
        Toastr::success($order->medicine_name . ' marked given (' . $time . ').');
        return back();
    }

    public function marMiss(Request $request, $orderId)
    {
        $this->boot();
        [$actorId] = $this->actor();
        $order = NursingMarOrder::where('store_id', $this->storeId())->findOrFail($orderId);

        NursingMarAdmin::updateOrCreate(
            ['nursing_mar_order_id' => $order->id, 'admin_date' => today()->toDateString(), 'scheduled_time' => $request->time],
            ['status' => 'missed', 'administered_by' => $actorId, 'administered_at' => now(), 'missed_reason' => $request->reason ?: 'Not recorded']
        );
        Toastr::warning($order->medicine_name . ' recorded as MISSED — doctor will be notified.');
        return back();
    }

    // ── Fluid balance ──
    public function fluidAdd(Request $request, $admissionId)
    {
        $this->boot();
        [$actorId] = $this->actor();
        $adm = $this->admission($admissionId);
        $request->validate(['description' => 'required|string|max:255', 'volume_ml' => 'required|numeric|min:0', 'type' => 'required|in:in,out']);

        NursingFluidEntry::create([
            'store_id' => $this->storeId(), 'patient_id' => $adm->patient_id, 'ipd_admission_id' => $adm->id,
            'entry_date' => today()->toDateString(), 'entry_time' => now()->format('H:i'),
            'description' => $request->description, 'type' => $request->type,
            'volume_ml' => $request->volume_ml, 'recorded_by' => $actorId,
        ]);
        Toastr::success('Fluid entry added.');
        return back();
    }

    // ── Nursing note (reuses NursingNote) ──
    public function noteAdd(Request $request, $admissionId)
    {
        $this->boot();
        [$actorId] = $this->actor();
        $adm = $this->admission($admissionId);
        $request->validate(['note' => 'required|string']);

        NursingNote::create([
            'store_id' => $this->storeId(), 'ipd_admission_id' => $adm->id, 'patient_id' => $adm->patient_id,
            'recorded_by' => $actorId, 'note_type' => $this->shiftNow(), 'note' => $request->note, 'recorded_at' => now(),
        ]);
        Toastr::success('Nursing note saved.');
        return back();
    }

    // ── Tasks ──
    public function taskAdd(Request $request)
    {
        $this->boot();
        $request->validate(['description' => 'required|string|max:255']);
        $adm = $request->ipd_admission_id ? $this->admission($request->ipd_admission_id) : null;

        NursingTask::create([
            'store_id' => $this->storeId(), 'ward_id' => $adm->ward_id ?? $request->ward_id,
            'ipd_admission_id' => $adm->id ?? null, 'bed_label' => $adm?->bed?->bed_number ?? ($request->bed_label ?: 'All'),
            'task_date' => today()->toDateString(), 'due_time' => $request->due_time ?: now()->format('H:i'),
            'description' => $request->description, 'priority' => $request->priority ?: 'normal',
            'shift' => $this->shiftNow(), 'status' => 'pending',
        ]);
        Toastr::success('Task added.');
        return back();
    }

    public function taskComplete($id)
    {
        $this->boot();
        [$actorId] = $this->actor();
        $task = NursingTask::where('store_id', $this->storeId())->findOrFail($id);
        $task->update(['status' => $task->status === 'done' ? 'pending' : 'done', 'done_by' => $actorId, 'done_at' => now()]);
        return back();
    }

    // ── Handover ──
    public function handoverSave(Request $request)
    {
        $this->boot();
        $storeId = $this->storeId();
        [, $name] = $this->actor();

        NursingHandover::updateOrCreate(
            ['store_id' => $storeId, 'ward_id' => $request->ward_id, 'handover_date' => today()->toDateString(), 'shift' => $this->shiftNow()],
            [
                'outgoing_nurse' => $request->outgoing_nurse ?: $name,
                'incoming_nurse' => $request->incoming_nurse,
                'notes' => $request->notes,
                'completed_at' => $request->boolean('complete') ? now() : null,
            ]
        );
        Toastr::success($request->boolean('complete') ? 'Shift handover completed.' : 'Handover saved.');
        return back();
    }
}
