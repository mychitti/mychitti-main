<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\IssuedCertificate;
use App\Models\Store;
use App\Models\StoreConfig;
use App\Models\Student;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class CertificateController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('issued_certificates')) {
            DB::statement("CREATE TABLE IF NOT EXISTS issued_certificates (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                student_id BIGINT UNSIGNED NOT NULL, type VARCHAR(30) NOT NULL,
                serial_no VARCHAR(50) NULL, issue_date DATE NULL, reason VARCHAR(255) NULL,
                design VARCHAR(30) NULL,
                body LONGTEXT NULL, issued_by VARCHAR(150) NULL,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id), KEY idx_student (student_id), KEY idx_type (type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasColumn('issued_certificates', 'design')) {
            DB::statement("ALTER TABLE `issued_certificates` ADD COLUMN `design` VARCHAR(30) NULL AFTER `type`");
        }
        $cfg = (new StoreConfig)->getTable();
        if (!Schema::hasColumn($cfg, 'cert_tc_body')) {
            DB::statement("ALTER TABLE `{$cfg}`
                ADD COLUMN `cert_tc_body` LONGTEXT NULL,
                ADD COLUMN `cert_bonafide_body` LONGTEXT NULL,
                ADD COLUMN `cert_character_body` LONGTEXT NULL");
        }
        if (!Schema::hasColumn($cfg, 'cert_design')) {
            DB::statement("ALTER TABLE `{$cfg}` ADD COLUMN `cert_design` VARCHAR(30) NULL");
        }
    }

    private function storeDefaultDesign($store_id): string
    {
        $d = StoreConfig::where('store_id', $store_id)->value('cert_design');
        return array_key_exists($d, IssuedCertificate::DESIGNS) ? $d : 'classic';
    }

    /* ===== Landing: issued certificates ===== */
    public function index(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $type     = $request->query('type');
        $search   = trim($request->query('search', ''));

        $certificates = IssuedCertificate::where('store_id', $store_id)
            ->with('student')
            ->when($type, fn($q) => $q->where('type', $type))
            ->when($search, fn($q) => $q->whereHas('student', fn($s) => $s->where('name', 'like', "%$search%")->orWhere('admission_no', 'like', "%$search%"))
                ->orWhere('serial_no', 'like', "%$search%"))
            ->orderByDesc('id')->paginate(config('default_pagination'))->withQueryString();

        return view('school::vendor.certificates.index', [
            'certificates' => $certificates,
            'types'        => IssuedCertificate::TYPES,
            'type'         => $type,
            'search'       => $search,
        ]);
    }

    /* ===== Issue form ===== */
    public function create(Request $request)
    {
        $this->ensureSchema();
        $store_id  = Helpers::get_store_id();
        $studentId = $request->query('student_id');

        $preStudent = $studentId
            ? Student::where('store_id', $store_id)->with('currentEnrollment')->find($studentId)
            : null;

        return view('school::vendor.certificates.issue', [
            'types'         => IssuedCertificate::TYPES,
            'designs'       => IssuedCertificate::DESIGNS,
            'defaultDesign' => $this->storeDefaultDesign($store_id),
            'studentId'     => $studentId,
            'preStudent'    => $preStudent,
            'selectedType'  => $request->query('type', 'bonafide'),
        ]);
    }

    /* ===== Student search (select2 ajax) ===== */
    public function search(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $q = trim($request->query('q', ''));

        $students = Student::where('store_id', $store_id)->where('status', 1)
            ->with('currentEnrollment')
            ->when($q, fn($query) => $query->where(fn($w) => $w
                ->where('name', 'like', "%$q%")
                ->orWhere('admission_no', 'like', "%$q%")
                ->orWhereHas('currentEnrollment', fn($e) => $e->where('roll_no', 'like', "%$q%"))))
            ->orderBy('name')->limit(20)->get();

        return response()->json($students->map(fn($s) => [
            'id'           => $s->id,
            'name'         => $s->name,
            'admission_no' => $s->admission_no,
            'roll_no'      => $s->currentEnrollment?->roll_no,
        ]));
    }

    public function store(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $request->validate([
            'student_id' => 'required|integer',
            'type'       => 'required|in:' . implode(',', array_keys(IssuedCertificate::TYPES)),
            'design'     => 'nullable|in:' . implode(',', array_keys(IssuedCertificate::DESIGNS)),
            'issue_date' => 'nullable|date',
            'reason'     => 'nullable|string|max:255',
        ]);

        $student = Student::where('store_id', $store_id)
            ->with(['currentEnrollment.schoolClass', 'currentEnrollment.section', 'currentEnrollment.session'])
            ->findOrFail($request->student_id);

        $type      = $request->type;
        $prefixMap = ['tc' => 'TC', 'bonafide' => 'BON', 'character' => 'CHR'];
        $serial    = $prefixMap[$type] . '-' . school_next_serial(IssuedCertificate::class, $store_id, 'serial_no', fn($q) => $q->where('type', $type));

        $body = $this->renderTemplate($store_id, $type, $student, $request->reason, $request->issue_date);

        $certificate = IssuedCertificate::create([
            'store_id'   => $store_id,
            'student_id' => $student->id,
            'type'       => $type,
            'design'     => $request->design ?: $this->storeDefaultDesign($store_id),
            'serial_no'  => $serial,
            'issue_date' => $request->issue_date ?: now()->toDateString(),
            'reason'     => $request->reason,
            'body'       => $body,
            'issued_by'  => $this->currentUserName(),
        ]);

        Toastr::success(IssuedCertificate::TYPES[$type] . ' issued.');
        return redirect()->route('vendor.school.certificates.show', $certificate->id);
    }

    public function show($id)
    {
        $this->ensureSchema();
        $certificate = IssuedCertificate::withoutGlobalScope('schoolBranch')->where('store_id', Helpers::get_store_id())->with('student')->findOrFail($id);
        $store = Store::withoutGlobalScopes()->find($certificate->store_id);
        $branch = $certificate->branch_id ? \App\Models\Branch::find($certificate->branch_id) : null;
        return view('school::vendor.certificates.certificate', ['certificate' => $certificate, 'store' => $store, 'branch' => $branch, 'design' => $this->resolveDesign($certificate), 'pdf' => false]);
    }

    public function pdf($id)
    {
        $this->ensureSchema();
        $certificate = IssuedCertificate::withoutGlobalScope('schoolBranch')->where('store_id', Helpers::get_store_id())->with('student')->findOrFail($id);
        $store = Store::withoutGlobalScopes()->find($certificate->store_id);
        $branch = $certificate->branch_id ? \App\Models\Branch::find($certificate->branch_id) : null;

        $html = View::make('school::vendor.certificates.certificate', ['certificate' => $certificate, 'store' => $store, 'branch' => $branch, 'design' => $this->resolveDesign($certificate), 'pdf' => true])->render();
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 14, 'margin_right' => 14, 'margin_top' => 14, 'margin_bottom' => 14, 'tempDir' => storage_path('tmp')]);
        $mpdf->WriteHTML($html);
        $mpdf->Output($certificate->type . '_' . $certificate->serial_no . '.pdf', 'I');
    }

    public function delete($id)
    {
        IssuedCertificate::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Certificate removed.');
        return redirect()->route('vendor.school.certificates.index');
    }

    /* ===== Templates ===== */
    public function settings()
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $config = StoreConfig::where('store_id', $store_id)->first();
        return view('school::vendor.certificates.settings', [
            'tc'        => $config?->cert_tc_body ?: $this->defaultTemplate('tc'),
            'bonafide'  => $config?->cert_bonafide_body ?: $this->defaultTemplate('bonafide'),
            'character' => $config?->cert_character_body ?: $this->defaultTemplate('character'),
            'tokens'    => $this->tokenList(),
            'designs'   => IssuedCertificate::DESIGNS,
            'design'    => $this->storeDefaultDesign($store_id),
        ]);
    }

    public function save_settings(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'tc'        => 'required|string',
            'bonafide'  => 'required|string',
            'character' => 'required|string',
            'design'    => 'nullable|in:' . implode(',', array_keys(IssuedCertificate::DESIGNS)),
        ]);
        StoreConfig::updateOrInsert(
            ['store_id' => Helpers::get_store_id()],
            [
                'cert_tc_body'        => $request->tc,
                'cert_bonafide_body'  => $request->bonafide,
                'cert_character_body' => $request->character,
                'cert_design'         => $request->design ?: 'classic',
            ]
        );
        Toastr::success('Certificate templates saved.');
        return back();
    }

    private function resolveDesign(IssuedCertificate $certificate): string
    {
        if (array_key_exists($certificate->design, IssuedCertificate::DESIGNS)) {
            return $certificate->design;
        }
        return $this->storeDefaultDesign($certificate->store_id);
    }

    /* ===== helpers ===== */
    private function renderTemplate($store_id, string $type, Student $student, ?string $reason, ?string $issueDate): string
    {
        $config = StoreConfig::where('store_id', $store_id)->first();
        $column = ['tc' => 'cert_tc_body', 'bonafide' => 'cert_bonafide_body', 'character' => 'cert_character_body'][$type];
        $template = $config?->{$column} ?: $this->defaultTemplate($type);

        $gender = strtolower((string) $student->gender);
        $sonDaughter = $gender === 'male' ? 'son' : ($gender === 'female' ? 'daughter' : 'son/daughter');
        $heShe       = $gender === 'male' ? 'He' : ($gender === 'female' ? 'She' : 'He/She');
        $hisHer      = $gender === 'male' ? 'his' : ($gender === 'female' ? 'her' : 'his/her');

        $enr = $student->currentEnrollment;

        return strtr($template, [
            '{school_name}'   => $store_id ? (Store::withoutGlobalScopes()->find($store_id)->name ?? 'School') : 'School',
            '{student_name}'  => $student->name ?: '—',
            '{admission_no}'  => $student->admission_no ?: '—',
            '{class}'         => $enr?->schoolClass?->name ?? '—',
            '{section}'       => $enr?->section?->name ?? '',
            '{roll_no}'       => $enr?->roll_no ?? '—',
            '{guardian_name}' => $student->guardian_name ?: '—',
            '{dob}'           => $student->dob ? \Carbon\Carbon::parse($student->dob)->format('d/m/Y') : '—',
            '{gender}'        => ucfirst($gender) ?: '—',
            '{category}'      => $student->category ?: '—',
            '{address}'       => trim(implode(', ', array_filter([$student->address, $student->city, $student->state]))) ?: '—',
            '{session}'       => $enr?->session?->name ?? '—',
            '{issue_date}'    => \Carbon\Carbon::parse($issueDate ?: now())->format('d/m/Y'),
            '{reason}'        => $reason ?: '—',
            '{son_daughter}'  => $sonDaughter,
            '{he_she}'        => $heShe,
            '{his_her}'       => $hisHer,
        ]);
    }

    private function defaultTemplate(string $type): string
    {
        return match ($type) {
            'tc' => 'This is to certify that {student_name}, {son_daughter} of {guardian_name}, bearing Admission No. {admission_no}, was a bonafide student of this institution. As per our records, {his_her} date of birth is {dob}. {he_she} studied in Class {class} {section} during the academic session {session}. {he_she} has cleared all dues and {his_her} conduct was found to be good. This Transfer Certificate is issued on request.',
            'bonafide' => 'This is to certify that {student_name}, {son_daughter} of {guardian_name}, bearing Admission No. {admission_no}, is a bonafide student of this institution, currently studying in Class {class} {section} during the academic session {session}. This certificate is issued for the purpose of {reason}.',
            'character' => 'This is to certify that {student_name}, {son_daughter} of {guardian_name}, bearing Admission No. {admission_no}, was a student of this institution in Class {class} {section}. To the best of our knowledge, {his_her} character and conduct were found to be GOOD during the period of study.',
            default => '',
        };
    }

    private function tokenList(): array
    {
        return [
            '{student_name}', '{admission_no}', '{class}', '{section}', '{roll_no}',
            '{guardian_name}', '{dob}', '{gender}', '{category}', '{address}',
            '{session}', '{issue_date}', '{reason}', '{son_daughter}', '{he_she}', '{his_her}', '{school_name}',
        ];
    }

    private function currentUserName(): string
    {
        if (auth('vendor_employee')->check()) {
            $u = auth('vendor_employee')->user();
            return trim(($u->f_name ?? '') . ' ' . ($u->l_name ?? '')) ?: 'Staff';
        }
        $v = auth('vendor')->user();
        return trim(($v->f_name ?? '') . ' ' . ($v->l_name ?? '')) ?: 'Admin';
    }
}
