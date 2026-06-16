<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\CentralLogics\Helpers;
use App\Models\DoctorProfile;
use App\Models\IpdAdmission;
use App\Models\PreopBloodUnit;
use App\Models\PreopCase;
use App\Models\PreopCheck;
use App\Models\PreopClearance;
use App\Models\PreopConsent;
use App\Models\PreopMed;
use App\Models\PreopResult;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PreOpController extends Controller
{
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
        return [auth('vendor')->id(), 'vendor', trim(($v->f_name ?? '') . ' ' . ($v->l_name ?? ''))];
    }

    // ── Schema (guarded, idempotent) ──────────────────────────────────────
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('preop_cases')) {
            DB::statement("CREATE TABLE `preop_cases` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL, `patient_id` BIGINT UNSIGNED NULL, `ipd_admission_id` BIGINT UNSIGNED NULL,
                `procedure` VARCHAR(200) NULL, `icd_code` VARCHAR(60) NULL,
                `surgeon` VARCHAR(150) NULL, `assistant` VARCHAR(150) NULL, `anaesthetist` VARCHAR(150) NULL,
                `ot_room` VARCHAR(40) NULL, `scheduled_at` TIMESTAMP NULL, `est_duration` VARCHAR(60) NULL,
                `anaesthesia_type` VARCHAR(80) NULL, `asa_class` INT NULL, `airway` VARCHAR(120) NULL,
                `intubation_plan` VARCHAR(120) NULL, `nbm_since` VARCHAR(60) NULL, `anaesthesia_notes` TEXT NULL,
                `diagnosis` VARCHAR(255) NULL, `referred_by` VARCHAR(150) NULL, `special_instructions` TEXT NULL,
                `handover_from` VARCHAR(150) NULL, `handover_to` VARCHAR(150) NULL, `handover_notes` TEXT NULL, `shifted_at` TIMESTAMP NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'preparing',
                `created_by` BIGINT UNSIGNED NULL, `created_by_type` VARCHAR(30) NULL,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `pc_store_idx` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('preop_meds')) {
            DB::statement("CREATE TABLE `preop_meds` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `preop_case_id` BIGINT UNSIGNED NOT NULL,
                `name` VARCHAR(200) NOT NULL, `detail` VARCHAR(255) NULL, `dose` VARCHAR(80) NULL,
                `route_time` VARCHAR(120) NULL, `purpose` VARCHAR(200) NULL, `status` VARCHAR(12) NOT NULL DEFAULT 'due',
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `pm_case_idx` (`preop_case_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('preop_consents')) {
            DB::statement("CREATE TABLE `preop_consents` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `preop_case_id` BIGINT UNSIGNED NOT NULL,
                `name` VARCHAR(200) NOT NULL, `status` VARCHAR(12) NOT NULL DEFAULT 'pending',
                `signed_by` VARCHAR(150) NULL, `signed_at` TIMESTAMP NULL, `meta` VARCHAR(255) NULL,
                `is_optional` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `pco_case_idx` (`preop_case_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('preop_clearances')) {
            DB::statement("CREATE TABLE `preop_clearances` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `preop_case_id` BIGINT UNSIGNED NOT NULL,
                `type_label` VARCHAR(120) NOT NULL, `by_label` VARCHAR(200) NULL,
                `status` VARCHAR(12) NOT NULL DEFAULT 'pending', `note` VARCHAR(255) NULL, `sort_order` INT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `pcl_case_idx` (`preop_case_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('preop_checks')) {
            DB::statement("CREATE TABLE `preop_checks` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `preop_case_id` BIGINT UNSIGNED NOT NULL,
                `category` VARCHAR(20) NOT NULL, `label` VARCHAR(255) NOT NULL,
                `status` VARCHAR(12) NOT NULL DEFAULT 'pending', `meta` VARCHAR(120) NULL, `sort_order` INT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `pck_case_idx` (`preop_case_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('preop_results')) {
            DB::statement("CREATE TABLE `preop_results` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `preop_case_id` BIGINT UNSIGNED NOT NULL,
                `name` VARCHAR(160) NOT NULL, `value` VARCHAR(80) NULL, `ref_range` VARCHAR(120) NULL,
                `status` VARCHAR(12) NOT NULL DEFAULT 'normal', `sort_order` INT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `prs_case_idx` (`preop_case_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('preop_blood_units')) {
            DB::statement("CREATE TABLE `preop_blood_units` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `preop_case_id` BIGINT UNSIGNED NOT NULL,
                `unit_id` VARCHAR(60) NULL, `component` VARCHAR(40) NULL, `blood_group` VARCHAR(10) NULL,
                `expiry_date` DATE NULL, `status` VARCHAR(20) NOT NULL DEFAULT 'reserved',
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `pbu_case_idx` (`preop_case_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public const FEATURES = [
        'preop_schedule'    => ['Pre-Op Scheduling', ['view', 'add']],
        'preop_case'        => ['Pre-Op Case Details', ['view', 'edit']],
        'preop_checklist'   => ['Pre-Op Checklist', ['view', 'edit']],
        'preop_med'         => ['Pre-Op Medications', ['view', 'add', 'edit']],
        'preop_consent'     => ['Pre-Op Consent', ['view', 'add', 'edit']],
        'preop_clearance'   => ['Pre-Op Clearance', ['view', 'edit']],
        'preop_anaesthesia' => ['Pre-Op Anaesthesia', ['view', 'edit']],
        'preop_result'      => ['Pre-Op Investigations', ['view', 'add']],
        'preop_blood'       => ['Pre-Op Blood Bank', ['view', 'add']],
        'preop_handover'    => ['Pre-Op Handover', ['view', 'edit']],
    ];

    public const VIEW_PERMS = [
        'preop_schedule.view', 'preop_case.view', 'preop_checklist.view', 'preop_med.view',
        'preop_consent.view', 'preop_clearance.view', 'preop_anaesthesia.view',
        'preop_result.view', 'preop_blood.view', 'preop_handover.view',
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
        $legacy = DB::table('features')->where('name', 'preop')->value('id');
        if ($legacy) {
            $pids = DB::table('feature_permissions')->where('feature_id', $legacy)->pluck('id');
            if ($pids->count() && Schema::hasTable('role_feature_permissions')) {
                DB::table('role_feature_permissions')->whereIn('feature_permission_id', $pids)->delete();
            }
            DB::table('feature_permissions')->where('feature_id', $legacy)->delete();
            DB::table('features')->where('id', $legacy)->delete();
        }
    }

    private function boot(): void
    {
        $this->ensureSchema();
        $this->ensurePermission();
    }

    private function authorizeAccess(): void
    {
        if (auth('vendor')->check()) {
            return;
        }
        if (!hasAnyPermission(self::VIEW_PERMS)) {
            abort(403);
        }
    }

    // Seed the standard pre-op workflow items for a new case.
    private function seedCase(PreopCase $case): void
    {
        $investigations = [
            ['CBC', 'Haematology'], ['Blood Group & Cross-Match', 'Blood Bank'], ['Renal Function Test (RFT)', 'Biochemistry'],
            ['Liver Function Test (LFT)', 'Biochemistry'], ['Blood Sugar (Fasting)', 'Biochemistry'],
            ['Coagulation Profile (PT/INR/APTT)', 'Haematology'], ['ECG (12-Lead)', 'Cardiology'],
            ['Chest X-Ray PA', 'Radiology'], ['Urine Routine & Microscopy', 'Microbiology'],
            ['HIV / HBsAg / HCV Screening', 'Serology'], ['USG Abdomen', 'Radiology'],
        ];
        foreach ($investigations as $i => $row) {
            PreopCheck::create(['preop_case_id' => $case->id, 'category' => 'investigation', 'label' => $row[0], 'meta' => $row[1], 'sort_order' => $i]);
        }

        $quick = [
            'Admission & consent for admission done', 'All investigations ordered & collected', 'Lab reports received & reviewed',
            'X-Ray & ECG done & cleared', 'Pre-op medicines ordered', 'Surgical consent signed by patient',
            'Anaesthesia evaluation completed', 'Blood group & cross-match done', 'Skin prep, shaving, IV access done',
            'Surgeon & anaesthetist clearance obtained',
        ];
        foreach ($quick as $i => $label) {
            PreopCheck::create(['preop_case_id' => $case->id, 'category' => 'quick', 'label' => $label, 'sort_order' => $i]);
        }

        $prep = [
            'NBM — nil by mouth confirmed', 'IV cannula secured', 'Pre-op bath / body wash done', 'Site shaving / skin preparation done',
            'OT gown changed — jewellery removed', 'Nail polish / makeup removed', 'Dentures / contact lenses / hearing aids removed',
            'Foley catheter inserted (if ordered)', 'Nasogastric tube (if ordered)', 'Patient identity band checked',
            'Allergy band (RED) applied', 'Psychological support given',
        ];
        foreach ($prep as $i => $label) {
            PreopCheck::create(['preop_case_id' => $case->id, 'category' => 'prep', 'label' => $label, 'sort_order' => $i]);
        }

        $handover = [
            'Patient identity confirmed — wristband verified', 'Surgical site marked (surgeon confirmed)', 'Allergy status communicated — RED band',
            'All consent forms confirmed signed', 'NBM status confirmed', 'IV access confirmed patent',
            'Pre-op medicines given', 'Lab reports, imaging, ECG handed to OT nurse', 'Blood bank slip in file',
            'Valuables given to family', 'Patient counselled — team introduced', 'Receiving OT nurse confirmed handover',
        ];
        foreach ($handover as $i => $label) {
            PreopCheck::create(['preop_case_id' => $case->id, 'category' => 'handover', 'label' => $label, 'sort_order' => $i]);
        }

        $clearances = [
            'Surgical Clearance', 'Anaesthesia Clearance', 'Physician Clearance', 'Cardiologist Clearance',
            'Blood Bank', 'Consent Clearance', 'Lab Reports', 'Nursing Prep', 'OT Slot Confirmed',
        ];
        foreach ($clearances as $i => $label) {
            PreopClearance::create(['preop_case_id' => $case->id, 'type_label' => $label, 'sort_order' => $i]);
        }

        $consents = [
            ['Surgical Consent — ' . ($case->procedure ?: 'Procedure'), 0],
            ['Anaesthesia Consent', 0], ['Blood Transfusion Consent', 0],
            ['High Risk Consent', 1], ['Intra-op Photography / Video Consent', 1],
        ];
        foreach ($consents as $row) {
            PreopConsent::create(['preop_case_id' => $case->id, 'name' => $row[0], 'is_optional' => $row[1]]);
        }
    }

    private function loadCase($id): ?PreopCase
    {
        return PreopCase::where('store_id', $this->storeId())
            ->with(['patient', 'admission.bed', 'admission.ward', 'meds', 'consents', 'clearances', 'checks', 'results', 'bloodUnits'])
            ->find($id);
    }

    private function caseQuery($id): PreopCase
    {
        return PreopCase::where('store_id', $this->storeId())->findOrFail($id);
    }

    private function steps(PreopCase $c): array
    {
        $inv = $c->checks->where('category', 'investigation');
        $prep = $c->checks->where('category', 'prep');
        $handover = $c->checks->where('category', 'handover');
        $reqConsents = $c->consents->where('is_optional', false);
        $anaesClr = $c->clearances->firstWhere('type_label', 'Anaesthesia Clearance');

        $st = fn($all, $done) => $all === 0 ? 'pending' : ($done >= $all ? 'done' : ($done > 0 ? 'active' : 'pending'));

        return [
            ['Admission', 'done'],
            ['Investigations', $st($inv->count(), $inv->where('status', 'done')->count())],
            ['Reports', $c->results->count() > 0 ? 'done' : 'pending'],
            ['Medicines', $c->meds->count() === 0 ? 'pending' : ($c->meds->whereIn('status', ['held', 'due'])->count() ? 'warn' : 'done')],
            ['Consent', $reqConsents->count() && $reqConsents->where('status', 'signed')->count() >= $reqConsents->count() ? 'done' : ($reqConsents->where('status', 'signed')->count() ? 'warn' : 'pending')],
            ['Anaesthesia', $c->asa_class && optional($anaesClr)->status === 'cleared' ? 'done' : ($c->asa_class ? 'warn' : 'pending')],
            ['Blood Bank', $c->bloodUnits->count() > 0 ? 'done' : 'pending'],
            ['Pre-Op Prep', $st($prep->count(), $prep->where('status', 'done')->count())],
            ['Clearances', $c->clearances->where('status', 'cleared')->count() >= $c->clearances->count() && $c->clearances->count() ? 'done' : ($c->clearances->where('status', 'blocked')->count() ? 'warn' : 'active')],
            ['OT Handover', $c->shifted_at ? 'done' : ($handover->where('status', 'done')->count() ? 'active' : 'pending')],
        ];
    }

    // ── Main page ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $this->boot();
        $this->authorizeAccess();
        $storeId = $this->storeId();

        $cases = PreopCase::where('store_id', $storeId)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->with('patient')->orderBy('scheduled_at')->get();

        $current = $this->loadCase($request->get('case')) ?? ($cases->count() ? $this->loadCase($cases->first()->id) : null);

        $steps = $current ? $this->steps($current) : [];
        $stepsDone = collect($steps)->where(1, 'done')->count();

        // For the "Schedule Surgery" form
        $admissions = IpdAdmission::where('store_id', $storeId)->where('status', 'admitted')->with('patient', 'bed')->get();
        $doctors = DoctorProfile::where('store_id', $storeId)->with('employee:id,f_name,l_name')->get();

        return view('hmis::vendor.preop.index', compact('cases', 'current', 'steps', 'stepsDone', 'admissions', 'doctors'));
    }

    // ── Schedule a new case ──
    public function schedule(Request $request)
    {
        $this->boot();
        $request->validate([
            'ipd_admission_id' => 'required|exists:ipd_admissions,id',
            'procedure'        => 'required|string|max:200',
        ]);
        $storeId = $this->storeId();
        [$actorId, $actorType] = $this->actor();
        $adm = IpdAdmission::where('store_id', $storeId)->findOrFail($request->ipd_admission_id);

        $case = PreopCase::create([
            'store_id' => $storeId, 'patient_id' => $adm->patient_id, 'ipd_admission_id' => $adm->id,
            'procedure' => $request->procedure, 'icd_code' => $request->icd_code,
            'surgeon' => $request->surgeon, 'anaesthetist' => $request->anaesthetist,
            'ot_room' => $request->ot_room, 'scheduled_at' => $request->scheduled_at ?: now()->addDay(),
            'est_duration' => $request->est_duration, 'anaesthesia_type' => $request->anaesthesia_type ?: 'General Anaesthesia (GA)',
            'diagnosis' => $adm->diagnosis, 'nbm_since' => $request->nbm_since,
            'status' => 'preparing', 'created_by' => $actorId, 'created_by_type' => $actorType,
        ]);
        $this->seedCase($case);

        Toastr::success('Pre-op case scheduled for ' . ($adm->patient->name ?? 'patient') . '.');
        return redirect()->route('vendor.preop.index', ['case' => $case->id]);
    }

    public function updateCase(Request $request, $id)
    {
        $this->boot();
        $case = $this->caseQuery($id);
        $case->update($request->only([
            'procedure', 'icd_code', 'surgeon', 'assistant', 'anaesthetist', 'ot_room',
            'est_duration', 'anaesthesia_type', 'special_instructions',
            'asa_class', 'airway', 'intubation_plan', 'nbm_since', 'anaesthesia_notes',
        ]));
        Toastr::success('Case details updated.');
        return back();
    }

    public function anaesthesiaSave(Request $request, $id)
    {
        $this->boot();
        [, , $name] = $this->actor();
        $case = $this->caseQuery($id);
        $case->update($request->only(['asa_class', 'anaesthesia_type', 'airway', 'intubation_plan', 'nbm_since', 'anaesthesia_notes']));

        if ($request->boolean('clear')) {
            PreopClearance::where('preop_case_id', $case->id)->where('type_label', 'Anaesthesia Clearance')
                ->update(['status' => 'cleared', 'by_label' => $case->anaesthetist ?: $name, 'note' => 'Cleared ' . now()->format('h:i A')]);
            Toastr::success('Anaesthesia evaluation saved — clearance given.');
        } else {
            Toastr::success('Anaesthesia evaluation saved.');
        }
        return back();
    }

    // ── Checklist toggle (quick / prep / handover / investigation) ──
    public function toggleCheck($checkId)
    {
        $this->boot();
        $check = PreopCheck::find($checkId);
        $case = $check ? PreopCase::where('store_id', $this->storeId())->find($check->preop_case_id) : null;
        if (!$check || !$case) {
            abort(404);
        }
        $check->update(['status' => $check->status === 'done' ? 'pending' : 'done']);
        return back();
    }

    // ── Medicines ──
    public function medAdd(Request $request, $id)
    {
        $this->boot();
        $case = $this->caseQuery($id);
        $request->validate(['name' => 'required|string|max:200']);
        PreopMed::create([
            'preop_case_id' => $case->id, 'name' => $request->name, 'detail' => $request->detail,
            'dose' => $request->dose, 'route_time' => $request->route_time, 'purpose' => $request->purpose, 'status' => 'due',
        ]);
        Toastr::success('Pre-op medicine order added.');
        return back();
    }

    public function medStatus(Request $request, $medId)
    {
        $this->boot();
        $med = PreopMed::find($medId);
        $case = $med ? PreopCase::where('store_id', $this->storeId())->find($med->preop_case_id) : null;
        if (!$med || !$case) {
            abort(404);
        }
        $med->update(['status' => $request->status ?: 'given']);
        Toastr::success($med->name . ' marked ' . $med->status . '.');
        return back();
    }

    // ── Consents ──
    public function consentSign($consentId)
    {
        $this->boot();
        [, , $name] = $this->actor();
        $consent = PreopConsent::find($consentId);
        $case = $consent ? PreopCase::where('store_id', $this->storeId())->find($consent->preop_case_id) : null;
        if (!$consent || !$case) {
            abort(404);
        }
        $consent->update(['status' => 'signed', 'signed_by' => $name, 'signed_at' => now()]);
        Toastr::success('Consent signed and recorded.');
        return back();
    }

    public function consentAdd(Request $request, $id)
    {
        $this->boot();
        $case = $this->caseQuery($id);
        $request->validate(['name' => 'required|string|max:200']);
        PreopConsent::create(['preop_case_id' => $case->id, 'name' => $request->name, 'is_optional' => $request->boolean('is_optional')]);
        Toastr::success('Consent form added.');
        return back();
    }

    // ── Clearances ──
    public function clearanceSet(Request $request, $clearanceId)
    {
        $this->boot();
        [, , $name] = $this->actor();
        $clr = PreopClearance::find($clearanceId);
        $case = $clr ? PreopCase::where('store_id', $this->storeId())->find($clr->preop_case_id) : null;
        if (!$clr || !$case) {
            abort(404);
        }
        $status = $request->status ?: 'cleared';
        $clr->update(['status' => $status, 'by_label' => $request->by_label ?: ($clr->by_label ?: $name), 'note' => $status === 'cleared' ? 'Cleared ' . now()->format('h:i A') : $clr->note]);
        Toastr::success($clr->type_label . ' — ' . $status . '.');
        return back();
    }

    // ── Reports / results ──
    public function resultAdd(Request $request, $id)
    {
        $this->boot();
        $case = $this->caseQuery($id);
        $request->validate(['name' => 'required|string|max:160']);
        PreopResult::create([
            'preop_case_id' => $case->id, 'name' => $request->name, 'value' => $request->value,
            'ref_range' => $request->ref_range, 'status' => $request->status ?: 'normal',
        ]);
        Toastr::success('Result added.');
        return back();
    }

    // ── Blood bank ──
    public function bloodAdd(Request $request, $id)
    {
        $this->boot();
        $case = $this->caseQuery($id);
        $request->validate(['unit_id' => 'required|string|max:60']);
        PreopBloodUnit::create([
            'preop_case_id' => $case->id, 'unit_id' => $request->unit_id, 'component' => $request->component ?: 'PRBC',
            'blood_group' => $request->blood_group, 'expiry_date' => $request->expiry_date ?: null, 'status' => 'reserved',
        ]);
        Toastr::success('Blood unit reserved.');
        return back();
    }

    // ── OT handover ──
    public function handover(Request $request, $id)
    {
        $this->boot();
        $case = $this->caseQuery($id);
        $case->update([
            'handover_from' => $request->handover_from, 'handover_to' => $request->handover_to,
            'handover_notes' => $request->handover_notes,
        ]);
        if ($request->boolean('shift')) {
            $case->update(['shifted_at' => now(), 'status' => 'in_ot']);
            Toastr::success('Handover completed — patient shifted to OT.');
        } else {
            Toastr::success('Handover saved.');
        }
        return back();
    }
}
