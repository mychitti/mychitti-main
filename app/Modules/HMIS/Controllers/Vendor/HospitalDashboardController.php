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

            // OPD rows can be dated ahead, so the register reads 'this_month' / 'this_week' as the
            // whole period rather than stopping at now. The OPD card and chart follow that same
            // rule, otherwise the card counts fewer visits than the list it opens.
            $opdRange = OpdVisit::resolveRange($preset, $custom);
            $opdFrom  = $opdRange['start']->toDateString();
            $opdTo    = $opdRange['end']->toDateString();

            $stats = [
                'patients'               => Patient::where('store_id', $store_id)->count(),
                'doctors'                => DoctorProfile::where('store_id', $store_id)->count(),
                'nurses'                 => NurseProfile::where('store_id', $store_id)->count(),
                'wards'                  => Ward::where('store_id', $store_id)->count(),
                'beds_total'             => Bed::where('store_id', $store_id)->count(),
                'beds_available'         => Bed::where('store_id', $store_id)->where('status', 'available')->count(),
                'beds_occupied'          => Bed::where('store_id', $store_id)->where('status', 'occupied')->count(),
                'opd_in_range'           => OpdVisit::where('store_id', $store_id)->notCancelled()->whereDate('visit_date', '>=', $opdFrom)->whereDate('visit_date', '<=', $opdTo)->count(),
                'ipd_admitted'           => IpdAdmission::where('store_id', $store_id)->where('status', 'admitted')->count(),
                'ipd_in_range'           => IpdAdmission::where('store_id', $store_id)->whereDate('admission_date', '>=', $from)->whereDate('admission_date', '<=', $to)->count(),
                'prescriptions_in_range' => Prescription::where('store_id', $store_id)->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->count(),
            ];

            $opdTrend = OpdVisit::where('store_id', $store_id)
                ->notCancelled()
                ->whereDate('visit_date', '>=', $opdFrom)
                ->whereDate('visit_date', '<=', $opdTo)
                ->select(DB::raw('DATE(visit_date) as day'), DB::raw('COUNT(*) as total'))
                ->groupBy('day')->orderBy('day')
                ->pluck('total', 'day');

            $opdLabels = [];
            $opdData   = [];
            $current   = \Carbon\Carbon::parse($opdFrom);
            $opdEnd    = \Carbon\Carbon::parse($opdTo);
            while ($current->lte($opdEnd)) {
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
            $end       = \Carbon\Carbon::parse($to);
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
                ->whereDate('visit_date', '>=', $opdFrom)->whereDate('visit_date', '<=', $opdTo)
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
            // Only OPD visits on this screen, so it follows the register's window rule — see
            // OpdVisit::resolveRange. A doctor's "this month" used to stop at today and hide the
            // visits already booked into their own diary for later in the month.
            $range  = OpdVisit::resolveRange($preset, $custom);
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
        // So the Printed Documents card works on a hospital that has never saved its settings:
        // the reads below fall back safely without the columns, but the signature it uploads from
        // this page writes to them.
        self::ensurePrintColumns();

        $store_id = Helpers::get_store_id();
        $config   = \App\Models\StoreConfig::where('store_id', $store_id)->first();
        $prefix   = $config?->patient_uid_prefix ?? 'P';
        $padding  = (int) ($config?->patient_uid_padding ?? 5);
        $serial   = (int) ($config?->patient_uid_serial ?? 1);
        $opd_consultation_count          = (int) ($config?->opd_consultation_count ?? 1);
        $opd_consultation_validity_days  = (int) ($config?->opd_consultation_validity_days ?? 7);
        $vitals_enabled                  = hmis_vitals_enabled($store_id);
        $rx_print_clinical               = hmis_rx_print_clinical($store_id);
        // Keyed by document, so the settings pane can loop the catalogue rather than name each one.
        $print_header_docs               = hmis_print_header_docs();
        $print_headers                   = collect(array_keys($print_header_docs))
            ->mapWithKeys(fn($doc) => [$doc => hmis_print_doc_settings($doc, $store_id)])
            ->all();
        $signatures                      = hmis_signatures($store_id);
        $default_signature_id            = hmis_default_signature_id($store_id);
        // Doctors listed apart from the rest of the staff: a report is signed by whoever reported
        // it, and picking that person out of one flat list of every employee is the slow way.
        $signature_doctor_ids            = \App\Models\DoctorProfile::where('store_id', $store_id)
            ->pluck('emp_id')->filter()->unique()->all();
        $signature_staff                 = \App\Models\VendorEmployee::where('store_id', $store_id)
            ->orderBy('f_name')->get(['id', 'f_name', 'l_name'])
            ->groupBy(fn($e) => in_array($e->id, $signature_doctor_ids) ? 'Doctors' : 'Other staff');
        $signature_owner                 = \App\Models\Store::with('vendor')->find($store_id)?->vendor;
        $security_tab_enabled            = hmis_security_tab_enabled($store_id);
        $lab_work_enabled                = hmis_lab_work_enabled($store_id);
        $discontinue_days                = hmis_discontinue_days($store_id);
        $daily_report                    = hmis_daily_report_settings($store_id);
        $daily_report_metrics            = \App\Services\DailyHospitalReport::METRICS;
        // The report goes to the number on the store record — shown here so a hospital can see
        // where it will land rather than discovering it when the first one arrives.
        $daily_report_phone              = \App\CentralLogics\Helpers::get_store_data()->phone ?? null;
        $lab_work_profile                = \App\Models\OpdLabWork::profileFor($store_id);
        $lab_work_auto                   = \App\Models\OpdLabWork::isAutoCategory(
            \App\Models\OpdLabWork::categoryFor($store_id)
        );

        $lastUid  = Patient::where('store_id', $store_id)->orderByDesc('id')->value('patient_uid');
        $autoNext = 1;
        if ($lastUid && preg_match('/(\d+)$/', $lastUid, $m)) {
            $autoNext = (int)$m[1] + 1;
        }
        $nextSerial  = max($autoNext, $serial);
        $previewMuid = strtoupper($prefix) . '-' . str_pad($nextSerial, $padding, '0', STR_PAD_LEFT);

        // Which prescription languages this hospital offers its doctors. Empty = English only.
        $rxLanguages = \App\Models\Prescription::enabledLanguages($store_id);

        // Address, GSTIN and licence book for the lab, pharmacy and radiology departments. A
        // scan centre or a lab often runs from its own premises under its own registrations, so
        // each one carries its own letterhead rather than borrowing the hospital's.
        $departments = [];
        foreach (\App\Models\HospitalDepartmentProfile::DEPARTMENTS as $key => $label) {
            $departments[$key] = [
                'label'    => $label,
                'profile'  => \App\Models\HospitalDepartmentProfile::forDepartment($store_id, $key)
                                ?? new \App\Models\HospitalDepartmentProfile(),
                'licenses' => \App\Models\HospitalLicense::listFor($store_id, $key),
            ];
        }

        $states = \App\Models\State::orderBy('state_name')->get(['id', 'state_name']);

        // How OPD visits may be paid for. The platform defaults are read, never copied — a row
        // exists only where this hospital added a type or switched a default off.
        $opTypeDefaults = \App\Models\OpdOpType::DEFAULTS;
        $opTypesOwn     = \App\Models\OpdOpType::ownNames($store_id);
        $opTypesHidden  = collect(\App\Models\OpdOpType::hiddenNames($store_id))
            ->mapWithKeys(fn($n) => [mb_strtolower(trim($n)) => true])
            ->all();

        return view('hmis::vendor.hospital.settings', compact(
            'prefix', 'padding', 'serial', 'previewMuid',
            'opd_consultation_count', 'opd_consultation_validity_days', 'rxLanguages',
            'vitals_enabled', 'rx_print_clinical', 'security_tab_enabled',
            'lab_work_enabled', 'lab_work_profile', 'lab_work_auto', 'discontinue_days',
            'departments', 'states',
            'opTypeDefaults', 'opTypesOwn', 'opTypesHidden',
            'daily_report', 'daily_report_metrics', 'daily_report_phone',
            'print_header_docs', 'print_headers',
            'signatures', 'default_signature_id', 'signature_staff', 'signature_owner'
        ));
    }

    /**
     * The columns behind Printed Documents, added in place — no migration files (see CLAUDE.md).
     *
     * Called from saving settings AND from the signature actions: a hospital adds a signature
     * before it ever picks a default, so the signature screen reaches these columns first.
     */
    public static function ensurePrintColumns(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $cfgTable = (new \App\Models\StoreConfig)->getTable();
        if (!\Illuminate\Support\Facades\Schema::hasTable($cfgTable)) {
            return;
        }

        // Every per-document print decision — letterhead, blank space, signature and its side.
        if (!\Illuminate\Support\Facades\Schema::hasColumn($cfgTable, 'hmis_print_headers')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$cfgTable}` ADD COLUMN `hmis_print_headers` TEXT NULL");
        }
        // Which saved signature a document uses when it does not name one of its own.
        if (!\Illuminate\Support\Facades\Schema::hasColumn($cfgTable, 'hmis_default_signature_id')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$cfgTable}` ADD COLUMN `hmis_default_signature_id` INT NULL");
        }
    }

    /**
     * Add a signature to the hospital's library.
     *
     * Written to store_signatures under type 'hmis' rather than to a table of its own: the panel
     * already stores, uploads and serves signatures there for invoices and quotations, and a
     * second store would mean a second place to look when one goes missing.
     */
    public function saveSignature(Request $request)
    {
        if (!auth('vendor')->check() && !hasPermission('hospital_manage', 'settings')) abort(403);

        $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg|max:1024',
            'staff' => 'nullable|integer',
        ], [
            'image.max' => 'A signature image must be under 1 MB.',
        ]);

        self::ensurePrintColumns();
        $store_id = Helpers::get_store_id();

        $sign           = new \App\Models\StoreSignature();
        $sign->store_id = $store_id;
        $sign->type     = 'hmis';
        // Optional, unlike the billing signatures: a hospital's authorised signatory on a report
        // is frequently the institution rather than a named member of staff.
        // '0' is the owner and is stored as 0; '' is nobody in particular and stays null.
        $sign->staff_id = $request->input('staff') === null || $request->input('staff') === ''
            ? null
            : (int) $request->input('staff');
        $sign->image    = Helpers::upload('store/signature/', 'png', $request->file('image'));
        $sign->save();

        // The first one saved becomes the default, so a hospital that only ever needs one never
        // has to notice that choosing a default was a step.
        $current = \App\Models\StoreConfig::where('store_id', $store_id)->value('hmis_default_signature_id');
        if (!$current) {
            \App\Models\StoreConfig::updateOrInsert(
                ['store_id' => $store_id],
                ['hmis_default_signature_id' => $sign->id]
            );
        }

        \Brian2694\Toastr\Facades\Toastr::success('Signature saved.');
        return back();
    }

    public function deleteSignature($id)
    {
        if (!auth('vendor')->check() && !hasPermission('hospital_manage', 'settings')) abort(403);

        self::ensurePrintColumns();
        $store_id = Helpers::get_store_id();
        $sign = \App\Models\StoreSignature::where('store_id', $store_id)
            ->where('type', 'hmis')->find($id);

        if (!$sign) {
            \Brian2694\Toastr\Facades\Toastr::error('That signature is not on this hospital.');
            return back();
        }

        $sign->delete();

        // A document pointing at it falls back to the default on its own (hmis_print_sign), but
        // the default itself has to be cleared or every document follows a signature that is gone.
        \App\Models\StoreConfig::where('store_id', $store_id)
            ->where('hmis_default_signature_id', $id)
            ->update(['hmis_default_signature_id' => null]);

        \Brian2694\Toastr\Facades\Toastr::success('Signature removed.');
        return back();
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
            'vitals_enabled'                 => 'nullable|boolean',
            'rx_print_clinical'              => 'nullable|boolean',
            'security_tab_enabled'           => 'nullable|boolean',
            'lab_work_enabled'               => 'nullable|boolean',
            'discontinue_enabled'            => 'nullable|boolean',
            // Capped at a year: past that the sweep is not closing abandoned care, it is tidying
            // history, and a recall interval that long belongs in the appointment book instead.
            'discontinue_days'               => 'nullable|integer|min:7|max:365',
            'daily_report_enabled'           => 'nullable|boolean',
            'daily_report_metrics'           => 'nullable|array',
            'daily_report_metrics.*'         => 'string|in:' . implode(',', array_keys(\App\Services\DailyHospitalReport::METRICS)),
            'daily_report_time'              => 'nullable|date_format:H:i',
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
        // Whether this hospital takes vitals at all. Nullable with null read as on, so every
        // existing hospital keeps the vitals cards without visiting this page.
        if (!\Illuminate\Support\Facades\Schema::hasColumn($cfgTable, 'hmis_vitals_enabled')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$cfgTable}` ADD COLUMN `hmis_vitals_enabled` TINYINT(1) NULL DEFAULT 1");
        }
        // Whether a prescription names the condition and repeats the doctor's advice, or carries
        // only the medicines. Nullable with null read as on, so no hospital's sheet changes shape
        // without somebody choosing it here.
        if (!\Illuminate\Support\Facades\Schema::hasColumn($cfgTable, 'hmis_rx_print_clinical')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$cfgTable}` ADD COLUMN `hmis_rx_print_clinical` TINYINT(1) NULL DEFAULT 1");
        }
        // Whether the consultation screen carries a Security & Compliance tab, and with it the
        // access trail that feeds it. Defaults to 0: the trail is only worth keeping for a
        // hospital that asked for it, so an untouched store logs nothing.
        if (!\Illuminate\Support\Facades\Schema::hasColumn($cfgTable, 'hmis_security_tab_enabled')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$cfgTable}` ADD COLUMN `hmis_security_tab_enabled` TINYINT(1) NULL DEFAULT 0");
        }
        // Whether this hospital tracks work it sends out to a lab. Nullable with null meaning
        // "whatever suits this speciality", so a dental or optical practice gets the tab without
        // finding the switch; saving here writes an explicit 0/1 that then wins outright.
        if (!\Illuminate\Support\Facades\Schema::hasColumn($cfgTable, 'hmis_lab_work_enabled')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$cfgTable}` ADD COLUMN `hmis_lab_work_enabled` TINYINT(1) NULL DEFAULT NULL");
        }
        // The daily WhatsApp summary: whether it is wanted, which figures it carries and when it
        // goes out. Off by default — a report nobody asked for is an unsolicited message, and it
        // costs the platform a conversation every day to send.
        if (!\Illuminate\Support\Facades\Schema::hasColumn($cfgTable, 'hmis_daily_report_enabled')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$cfgTable}`
                ADD COLUMN `hmis_daily_report_enabled` TINYINT(1) NULL DEFAULT 0,
                ADD COLUMN `hmis_daily_report_metrics` TEXT NULL,
                ADD COLUMN `hmis_daily_report_time` VARCHAR(5) NULL");
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn($cfgTable, 'opd_consultation_count')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$cfgTable}`
                ADD COLUMN `opd_consultation_count` INT NULL,
                ADD COLUMN `opd_consultation_validity_days` INT NULL");
        }
        // After how many days without a visit a course of treatment is given up on. NULL means
        // never, which is what every hospital gets until it chooses a number — a sweep that closes
        // clinical records is not something to switch on for people by default.
        if (!\Illuminate\Support\Facades\Schema::hasColumn($cfgTable, 'hmis_discontinue_days')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$cfgTable}` ADD COLUMN `hmis_discontinue_days` INT NULL DEFAULT NULL");
        }
        // Which printed documents drop their letterhead, and the blank run of paper that replaces
        // it. One JSON column rather than two per document: the list of printable documents grows,
        // and a schema change per document added is a schema change nobody remembers to make.
        self::ensurePrintColumns();
        \App\Models\OpdVisit::ensureDiscontinueColumns();

        // Absent checkbox means the letterhead stays on, so this is built from the known list of
        // documents rather than from whatever the form happened to post.
        $printHeaders = [];
        foreach (hmis_print_header_docs() as $printDoc => $printMeta) {
            $row = (array) $request->input("print_header.{$printDoc}", []);

            // Built from what the document declares, not from what the form posted: an unticked
            // checkbox posts nothing, and reading the posted keys would make "off" unreachable.
            $printSecs = [];
            foreach (array_keys($printMeta['sections'] ?? []) as $printSec) {
                $printSecs[$printSec] = !empty($row['secs'][$printSec]) ? 1 : 0;
            }
            $printHeaders[$printDoc] = [
                'off'      => !empty($row['off']) ? 1 : 0,
                'mm'       => max(0, min(120, (int) ($row['mm'] ?? 40))),
                // The screen sends one value for both: 'off' for no signature, otherwise the id,
                // with 0 meaning "whichever is the hospital default" so a document keeps following
                // the default as it changes rather than being pinned at save time. Stored as two
                // fields still, because that is what every reader of this already expects.
                'sign'     => ($row['sign_id'] ?? 'off') === 'off' ? 0 : 1,
                'sign_id'  => ($row['sign_id'] ?? 'off') === 'off' ? 0 : max(0, (int) $row['sign_id']),
                'sign_pos' => (($row['sign_pos'] ?? 'right') === 'left') ? 'left' : 'right',
                'secs'     => $printSecs,
            ];
        }

        \App\Models\StoreConfig::updateOrInsert(
            ['store_id' => $store_id],
            [
                'patient_uid_prefix'             => strtoupper($request->prefix),
                'patient_uid_padding'            => (int) $request->padding,
                'patient_uid_serial'             => (int) $request->serial,
                'opd_consultation_count'         => (int) $request->opd_consultation_count,
                'opd_consultation_validity_days' => (int) $request->opd_consultation_validity_days,
                'hmis_vitals_enabled'            => $request->boolean('vitals_enabled') ? 1 : 0,
                'hmis_rx_print_clinical'         => $request->boolean('rx_print_clinical') ? 1 : 0,
                'hmis_print_headers'             => json_encode($printHeaders),
                'hmis_default_signature_id'      => (int) $request->input('default_signature_id') ?: null,
                'hmis_security_tab_enabled'      => $request->boolean('security_tab_enabled') ? 1 : 0,
                'hmis_lab_work_enabled'          => $request->boolean('lab_work_enabled') ? 1 : 0,
                // Nought, not null: null means "never said" and would hand the hospital straight
                // back to the platform default it just switched off. A stored 0 is the refusal,
                // and it stays a refusal whatever the default becomes later.
                'hmis_discontinue_days'          => $request->boolean('discontinue_enabled')
                    ? (int) ($request->input('discontinue_days') ?: \App\Services\OpdDiscontinue::DEFAULT_DAYS)
                    : 0,
                // English is always kept: it is the fallback the printed sheet falls back to for
                // anything without a translation, so it can never be switched off.
                'rx_languages'                   => json_encode(
                    collect($request->input('rx_languages', []))->prepend('en')->unique()->values()->all()
                ),
                'hmis_daily_report_enabled'      => $request->boolean('daily_report_enabled') ? 1 : 0,
                // Every box unticked while the report is on would send an empty message, so an
                // empty selection falls back to the defaults rather than being saved as nothing.
                'hmis_daily_report_metrics'      => json_encode(
                    $request->input('daily_report_metrics') ?: \App\Services\DailyHospitalReport::DEFAULT_METRICS
                ),
                'hmis_daily_report_time'         => $request->input('daily_report_time') ?: '21:00',
            ]
        );

        \Brian2694\Toastr\Facades\Toastr::success('Hospital settings saved.');
        return back();
    }

    /**
     * Send this hospital its daily report right now.
     *
     * Same code path as the nightly run, so a test that arrives is proof the real one will. Run
     * inline rather than queued: the whole point is telling the vendor what happened, and a
     * queued job could only leave them guessing.
     */
    public function testDailyReport()
    {
        $store_id = Helpers::get_store_id();

        $result = \App\Jobs\Scheduled\SendDailyHospitalReportJob::test((int) $store_id);

        $result['success']
            ? \Brian2694\Toastr\Facades\Toastr::success($result['message'])
            : \Brian2694\Toastr\Facades\Toastr::error($result['message']);

        return back();
    }

    /**
     * Save one department's letterhead — address, GSTIN and its licence list.
     *
     * The licences arrive from a repeater, so the whole set is rewritten on every save: rows the
     * user deleted are simply absent from the post and must not survive in the table.
     */
    public function saveDepartment(Request $request, $department)
    {
        if (!array_key_exists($department, \App\Models\HospitalDepartmentProfile::DEPARTMENTS)) {
            abort(404);
        }

        $request->validate([
            'display_name'               => 'nullable|string|max:190',
            'address'                    => 'nullable|string|max:500',
            'city'                       => 'nullable|string|max:100',
            'state'                      => 'nullable|exists:states,id',
            'pincode'                    => 'nullable|string|max:20',
            'phone'                      => 'nullable|string|max:40',
            'email'                      => 'nullable|email|max:190',
            'gst_no'                     => 'nullable|string|max:30',
            'licenses'                   => 'nullable|array',
            'licenses.*.license_type'    => 'nullable|string|max:150',
            'licenses.*.license_no'      => 'nullable|string|max:150',
            'licenses.*.issuing_authority' => 'nullable|string|max:190',
            'licenses.*.issued_on'       => 'nullable|date',
            'licenses.*.valid_till'      => 'nullable|date',
        ]);

        $store_id = Helpers::get_store_id();

        \App\Models\HospitalDepartmentProfile::ensureTable();
        \App\Models\HospitalDepartmentProfile::updateOrCreate(
            ['store_id' => $store_id, 'department' => $department],
            [
                'display_name' => $request->display_name,
                'address'      => $request->address,
                'city'         => $request->city,
                'state'        => $request->state,
                'pincode'      => $request->pincode,
                'phone'        => $request->phone,
                'email'        => $request->email,
                'gst_no'       => $request->gst_no ? strtoupper(trim($request->gst_no)) : null,
            ]
        );

        \App\Models\HospitalLicense::syncFor($store_id, $department, 0, $request->input('licenses', []));

        $label = \App\Models\HospitalDepartmentProfile::DEPARTMENTS[$department];
        \App\Models\HospitalActivityLog::record(
            $store_id, 'settings', null, 'updated',
            "{$label} details and licences updated"
        );

        \Brian2694\Toastr\Facades\Toastr::success($label . ' details saved.');
        return back();
    }
}
