<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\SchoolClass;
use App\Models\Store;
use App\Models\StoreConfig;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\StudentEnrollment;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('school_students')) {
            DB::statement("CREATE TABLE IF NOT EXISTS school_students (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                admission_no VARCHAR(50) NULL, admission_date DATE NULL,
                name VARCHAR(150) NOT NULL, dob DATE NULL, gender VARCHAR(20) NULL,
                blood_group VARCHAR(10) NULL, category VARCHAR(50) NULL, photo VARCHAR(255) NULL,
                guardian_name VARCHAR(150) NULL, guardian_phone VARCHAR(30) NULL, guardian_relation VARCHAR(50) NULL,
                phone VARCHAR(30) NULL, email VARCHAR(150) NULL,
                address VARCHAR(255) NULL, city VARCHAR(100) NULL, state VARCHAR(100) NULL, pincode VARCHAR(20) NULL,
                status TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id), KEY idx_adm (admission_no)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('student_enrollments')) {
            DB::statement("CREATE TABLE IF NOT EXISTS student_enrollments (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                student_id BIGINT UNSIGNED NOT NULL, academic_session_id BIGINT UNSIGNED NULL,
                school_class_id BIGINT UNSIGNED NULL, class_section_id BIGINT UNSIGNED NULL,
                roll_no VARCHAR(30) NULL, status TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id), KEY idx_student (student_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('student_documents')) {
            DB::statement("CREATE TABLE IF NOT EXISTS student_documents (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                student_id BIGINT UNSIGNED NOT NULL, doc_type VARCHAR(30) NULL,
                title VARCHAR(190) NULL, file VARCHAR(255) NOT NULL, uploaded_by VARCHAR(150) NULL,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id), KEY idx_student (student_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasColumn('school_students', 'first_name')) {
            DB::statement("ALTER TABLE `school_students`
                ADD COLUMN `first_name` VARCHAR(100) NULL AFTER `admission_date`,
                ADD COLUMN `last_name` VARCHAR(100) NULL AFTER `first_name`");
        }
        $cfg = (new StoreConfig)->getTable();
        if (!Schema::hasColumn($cfg, 'admission_no_prefix')) {
            DB::statement("ALTER TABLE `{$cfg}`
                ADD COLUMN `admission_no_prefix` VARCHAR(20) NULL,
                ADD COLUMN `admission_no_padding` INT NULL,
                ADD COLUMN `admission_no_serial` INT NULL");
        }
        if (!Schema::hasColumn($cfg, 'school_serial_scope')) {
            DB::statement("ALTER TABLE `{$cfg}` ADD COLUMN `school_serial_scope` VARCHAR(10) NULL");
        }
    }

    public function index(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $classId   = $request->query('class_id');
        $sectionId = $request->query('section_id');
        $search    = trim($request->query('search', ''));

        $students = Student::where('store_id', $store_id)
            ->with(['currentEnrollment.schoolClass', 'currentEnrollment.section'])
            ->when($search, fn($q) => $q->where(fn($w) => $w->where('name', 'like', "%$search%")
                ->orWhere('admission_no', 'like', "%$search%")->orWhere('guardian_phone', 'like', "%$search%")))
            ->when($classId || $sectionId, fn($q) => $q->whereHas('currentEnrollment', function ($e) use ($classId, $sectionId) {
                $e->when($classId, fn($x) => $x->where('school_class_id', $classId))
                  ->when($sectionId, fn($x) => $x->where('class_section_id', $sectionId));
            }))
            ->orderByDesc('id')
            ->paginate(config('default_pagination'))->withQueryString();

        $classes  = SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->get();
        $sections = ClassSection::where('store_id', $store_id)->get();

        return view('school::vendor.students.index', compact('students', 'classes', 'sections', 'classId', 'sectionId', 'search'));
    }

    public function create()
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $student  = null;
        [$classes, $sections, $sessions, $currentSession, $nextAdmissionNo] = $this->formData($store_id);
        $docTypes = StudentDocument::TYPES;
        return view('school::vendor.students.form', compact('student', 'classes', 'sections', 'sessions', 'currentSession', 'nextAdmissionNo', 'docTypes'));
    }

    public function store(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        // Plan-tier enforcement: block new admissions once the subscribed student cap is reached.
        if (school_student_limit_reached($store_id)) {
            $tier = school_student_tier($store_id);
            Toastr::error('Your plan (' . ($tier->tier_name ?? 'current') . ') allows up to ' . ($tier->max_students ?? '') . ' students. Upgrade your plan to admit more.');
            return back()->withInput();
        }

        $data = $this->validateStudent($request);

        $photo = null;
        if ($request->hasFile('photo')) {
            $photo = Helpers::upload('school/students/', $request->file('photo')->getClientOriginalExtension(), $request->file('photo'));
        }

        $student = Student::create(array_merge($data, [
            'store_id'       => $store_id,
            'admission_no'   => $request->admission_no ?: Student::generateAdmissionNo($store_id),
            'admission_date' => $request->admission_date ?: now()->toDateString(),
            'photo'          => $photo,
            'status'         => 1,
        ]));

        $this->saveEnrollment($request, $store_id, $student->id);
        $this->saveDocuments($request, $student);

        Toastr::success('Student admitted successfully.');
        return redirect()->route('vendor.school.students.show', $student->id);
    }

    /** Save any documents attached on the admission form. */
    private function saveDocuments(Request $request, Student $student): void
    {
        if (!$request->hasFile('doc_file')) return;

        $request->validate(['doc_file.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:8192']);

        foreach ($request->file('doc_file') as $i => $docFile) {
            if (!$docFile) continue;
            $type  = $request->input("doc_type.$i") ?: 'other';
            if (!array_key_exists($type, StudentDocument::TYPES)) $type = 'other';
            $title = $request->input("doc_title.$i") ?: StudentDocument::TYPES[$type];
            $path  = Helpers::upload('school/student-docs/', $docFile->getClientOriginalExtension(), $docFile);

            StudentDocument::create([
                'store_id'    => $student->store_id,
                'branch_id'   => $student->branch_id,
                'student_id'  => $student->id,
                'doc_type'    => $type,
                'title'       => $title,
                'file'        => $path,
                'uploaded_by' => $this->currentUserName(),
            ]);
        }
    }

    public function show($id)
    {
        $this->ensureSchema();
        $student = Student::where('store_id', Helpers::get_store_id())
            ->with(['currentEnrollment.schoolClass', 'currentEnrollment.section', 'currentEnrollment.session'])
            ->findOrFail($id);
        $documents = StudentDocument::where('store_id', Helpers::get_store_id())->where('student_id', $student->id)->orderByDesc('id')->get();
        $docTypes  = StudentDocument::TYPES;
        return view('school::vendor.students.show', compact('student', 'documents', 'docTypes'));
    }

    public function edit($id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $student  = Student::where('store_id', $store_id)->with('currentEnrollment')->findOrFail($id);
        [$classes, $sections, $sessions, $currentSession, $nextAdmissionNo] = $this->formData($store_id);
        $docTypes = StudentDocument::TYPES;
        return view('school::vendor.students.form', compact('student', 'classes', 'sections', 'sessions', 'currentSession', 'nextAdmissionNo', 'docTypes'));
    }

    public function update(Request $request, $id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $student  = Student::where('store_id', $store_id)->findOrFail($id);
        $data = $this->validateStudent($request);

        if ($request->hasFile('photo')) {
            $data['photo'] = Helpers::upload('school/students/', $request->file('photo')->getClientOriginalExtension(), $request->file('photo'));
        }
        $student->update($data);

        $this->saveEnrollment($request, $store_id, $student->id);

        Toastr::success('Student updated.');
        return redirect()->route('vendor.school.students.show', $student->id);
    }

    public function delete($id)
    {
        $store_id = Helpers::get_store_id();
        StudentEnrollment::where('store_id', $store_id)->where('student_id', $id)->delete();
        Student::where('store_id', $store_id)->where('id', $id)->delete();
        Toastr::success('Student removed.');
        return redirect()->route('vendor.school.students.index');
    }

    public function idCard($id)
    {
        $this->ensureSchema();
        $student = Student::withoutGlobalScope('schoolBranch')->where('store_id', Helpers::get_store_id())
            ->with(['currentEnrollment.schoolClass', 'currentEnrollment.section'])->findOrFail($id);
        $store = Store::withoutGlobalScopes()->find($student->store_id);
        $branch = $student->branch_id ? \App\Models\Branch::find($student->branch_id) : null;
        return view('school::vendor.students.id_card', compact('student', 'store', 'branch'));
    }

    /** Deep per-student analytics dashboard. */
    public function dashboard($id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $student = Student::where('store_id', $store_id)
            ->with(['currentEnrollment.schoolClass', 'currentEnrollment.section', 'currentEnrollment.session'])
            ->findOrFail($id);

        // Attendance — 6-month trend + lifetime status tally
        $attTrend = collect();
        $attTotals = ['present' => 0, 'absent' => 0, 'late' => 0, 'leave' => 0, 'half_day' => 0];
        if (Schema::hasTable('student_attendances')) {
            for ($i = 5; $i >= 0; $i--) {
                $mth = now()->subMonths($i);
                $range = [$mth->copy()->startOfMonth()->toDateString(), $mth->copy()->endOfMonth()->toDateString()];
                $base = \App\Models\StudentAttendance::where('store_id', $store_id)->where('student_id', $id)->whereBetween('attendance_date', $range);
                $marked = (clone $base)->count();
                $present = (clone $base)->whereIn('status', ['present', 'late', 'half_day'])->count();
                $attTrend->push((object) ['label' => $mth->format('M'), 'pct' => $marked ? round($present / $marked * 100) : 0]);
            }
            foreach (\App\Models\StudentAttendance::where('store_id', $store_id)->where('student_id', $id)
                ->select('status', DB::raw('COUNT(*) as c'))->groupBy('status')->pluck('c', 'status') as $st => $c) {
                if (isset($attTotals[$st])) $attTotals[$st] = (int) $c;
            }
        }
        $attMarked = array_sum($attTotals);
        $attPct = $attMarked > 0 ? round(($attTotals['present'] + $attTotals['late'] + $attTotals['half_day']) / $attMarked * 100) : 0;

        // Exam performance — percentage per exam appeared
        $examPerf = collect();
        if (Schema::hasTable('mark_entries') && Schema::hasTable('exam_subjects')) {
            $examIds = \App\Models\MarkEntry::where('store_id', $store_id)->where('student_id', $id)->distinct()->pluck('exam_id');
            foreach (\App\Models\Exam::where('store_id', $store_id)->whereIn('id', $examIds)->orderBy('id')->get() as $exam) {
                $subjects = \App\Models\ExamSubject::where('store_id', $store_id)->where('exam_id', $exam->id)->get();
                $maxTotal = (float) $subjects->sum('max_marks');
                $marks = \App\Models\MarkEntry::where('store_id', $store_id)->where('exam_id', $exam->id)->where('student_id', $id)->get()->keyBy('exam_subject_id');
                $obtained = 0; $fail = false; $has = false;
                foreach ($subjects as $es) {
                    $mk = $marks->get($es->id);
                    if (!$mk) continue;
                    if ($mk->is_absent) { $fail = true; continue; }
                    $has = true; $g = (float) $mk->marks_obtained; $obtained += $g;
                    if ($g < $es->pass_marks) $fail = true;
                }
                if (!$has) continue;
                $examPerf->push((object) ['name' => $exam->name, 'pct' => $maxTotal > 0 ? round($obtained / $maxTotal * 100, 1) : 0, 'result' => $fail ? 'Fail' : 'Pass']);
            }
        }

        // Fees
        $fee = ['billed' => 0, 'paid' => 0, 'due' => 0, 'invoices' => collect()];
        if (Schema::hasTable('fee_invoices')) {
            $inv = \App\Models\FeeInvoice::where('store_id', $store_id)->where('student_id', $id)->orderByDesc('id')->get();
            $fee = [
                'billed'   => (float) $inv->sum('total_amount'),
                'paid'     => (float) $inv->sum('paid_amount'),
                'due'      => (float) $inv->where('due_amount', '>', 0)->sum('due_amount'),
                'invoices' => $inv->take(8),
            ];
        }

        // Leave history
        $leaves = collect();
        if (Schema::hasTable('student_leaves')) {
            $leaves = \App\Models\StudentLeave::where('store_id', $store_id)->where('student_id', $id)->orderByDesc('id')->limit(6)->get();
        }

        return view('school::vendor.students.dashboard', compact('student', 'attTrend', 'attTotals', 'attMarked', 'attPct', 'examPerf', 'fee', 'leaves'));
    }

    // ── Documents (TC, marksheets, certificates from prior school) ──
    public function document_store(Request $request, $id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $student  = Student::where('store_id', $store_id)->findOrFail($id);

        $request->validate([
            'doc_type' => 'required|in:' . implode(',', array_keys(StudentDocument::TYPES)),
            'title'    => 'nullable|string|max:190',
            'file'     => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:8192',
        ]);

        $file = Helpers::upload('school/student-docs/', $request->file('file')->getClientOriginalExtension(), $request->file('file'));

        StudentDocument::create([
            'store_id'    => $store_id,
            'branch_id'   => $student->branch_id,
            'student_id'  => $student->id,
            'doc_type'    => $request->doc_type,
            'title'       => $request->title ?: StudentDocument::TYPES[$request->doc_type],
            'file'        => $file,
            'uploaded_by' => $this->currentUserName(),
        ]);

        Toastr::success('Document uploaded.');
        return back();
    }

    public function document_delete($id, $docId)
    {
        $store_id = Helpers::get_store_id();
        StudentDocument::where('store_id', $store_id)->where('student_id', $id)->where('id', $docId)->delete();
        Toastr::success('Document removed.');
        return back();
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

    // ── Admission-number settings ────────────────────────────
    public function settings()
    {
        $this->ensureSchema();
        $config = StoreConfig::where('store_id', Helpers::get_store_id())->first();
        return view('school::vendor.students.settings', [
            'prefix'  => $config?->admission_no_prefix ?? 'ADM',
            'padding' => (int) ($config?->admission_no_padding ?? 4),
            'serial'  => (int) ($config?->admission_no_serial ?? 1),
            'serialScope' => $config?->school_serial_scope ?: 'store',
        ]);
    }

    public function save_settings(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'prefix'  => 'required|string|max:20',
            'padding' => 'required|integer|min:1|max:10',
            'serial'  => 'required|integer|min:1',
            'serial_scope' => 'nullable|in:store,branch',
        ]);
        StoreConfig::updateOrInsert(
            ['store_id' => Helpers::get_store_id()],
            [
                'admission_no_prefix'  => strtoupper($request->prefix),
                'admission_no_padding' => (int) $request->padding,
                'admission_no_serial'  => (int) $request->serial,
                'school_serial_scope'  => $request->serial_scope ?: 'store',
            ]
        );
        Toastr::success('Admission number format saved.');
        return back();
    }

    // ── helpers ──────────────────────────────────────────────
    private function validateStudent(Request $request): array
    {
        $data = $request->validate([
            'first_name'        => 'required|string|max:100',
            'last_name'         => 'nullable|string|max:100',
            'dob'               => 'required|date',
            'gender'            => 'nullable|in:male,female,other',
            'blood_group'       => 'nullable|string|max:10',
            'category'          => 'nullable|string|max:50',
            'guardian_name'     => 'nullable|string|max:150',
            'guardian_phone'    => 'nullable|string|max:30',
            'guardian_relation' => 'nullable|string|max:50',
            'phone'             => 'nullable|string|max:30',
            'email'             => 'nullable|email|max:150',
            'address'           => 'nullable|string|max:255',
            'city'              => 'nullable|string|max:100',
            'state'             => 'nullable|string|max:100',
            'pincode'           => 'nullable|string|max:20',
            'photo'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        // Keep a combined name in sync (used by lists, search, ID card, receipts)
        $data['name'] = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

        return $data;
    }

    private function saveEnrollment(Request $request, int $storeId, int $studentId): void
    {
        if (!$request->school_class_id) return;

        $rollNo = $request->roll_no;
        if (!$rollNo && $request->class_section_id) {
            $max = StudentEnrollment::where('store_id', $storeId)
                ->where('academic_session_id', $request->academic_session_id)
                ->where('school_class_id', $request->school_class_id)
                ->where('class_section_id', $request->class_section_id)
                ->where('status', 1)
                ->get()->pluck('roll_no')->map(fn($r) => (int) $r)->max();
            $rollNo = ($max ?? 0) + 1;
        }

        StudentEnrollment::updateOrCreate(
            ['store_id' => $storeId, 'student_id' => $studentId, 'status' => 1],
            [
                'academic_session_id' => $request->academic_session_id ?: null,
                'school_class_id'     => $request->school_class_id,
                'class_section_id'    => $request->class_section_id ?: null,
                'roll_no'             => $rollNo,
            ]
        );
    }

    private function formData(int $storeId): array
    {
        $classes  = SchoolClass::where('store_id', $storeId)->orderBy('numeric_order')->get();
        $sections = ClassSection::where('store_id', $storeId)->with('schoolClass')->get();
        $sessions = AcademicSession::where('store_id', $storeId)->orderByDesc('is_current')->orderByDesc('id')->get();
        $currentSession = $sessions->firstWhere('is_current', true);
        $nextAdmissionNo = Student::generateAdmissionNo($storeId);
        return [$classes, $sections, $sessions, $currentSession, $nextAdmissionNo];
    }
}
