<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\MarkEntry;
use App\Models\SchoolClass;
use App\Models\Store;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExamController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('school_exams')) {
            DB::statement("CREATE TABLE IF NOT EXISTS school_exams (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                academic_session_id BIGINT UNSIGNED NULL, school_class_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(150) NOT NULL, exam_type VARCHAR(50) NULL, status TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('exam_subjects')) {
            DB::statement("CREATE TABLE IF NOT EXISTS exam_subjects (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, exam_id BIGINT UNSIGNED NOT NULL,
                subject_id BIGINT UNSIGNED NOT NULL, max_marks DECIMAL(6,2) DEFAULT 100, pass_marks DECIMAL(6,2) DEFAULT 33,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_exam (exam_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('mark_entries')) {
            DB::statement("CREATE TABLE IF NOT EXISTS mark_entries (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, exam_id BIGINT UNSIGNED NOT NULL,
                exam_subject_id BIGINT UNSIGNED NOT NULL, student_id BIGINT UNSIGNED NOT NULL,
                marks_obtained DECIMAL(6,2) NULL, is_absent TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), UNIQUE KEY uniq (exam_subject_id, student_id), KEY idx_exam (exam_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function index()
    {
        $this->ensureSchema();
        $exams = Exam::where('store_id', Helpers::get_store_id())->with('schoolClass')
            ->withCount('subjects')->orderByDesc('id')->paginate(config('default_pagination'));
        return view('school::vendor.exams.index', compact('exams'));
    }

    public function create()
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $classes  = SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->get();
        $sessions = AcademicSession::where('store_id', $store_id)->orderByDesc('is_current')->get();
        return view('school::vendor.exams.create', compact('classes', 'sessions'));
    }

    public function store(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'name'            => 'required|string|max:150',
            'exam_type'       => 'nullable|string|max:50',
            'school_class_id' => 'required|integer',
        ]);
        $store_id = Helpers::get_store_id();
        $exam = Exam::create([
            'store_id'            => $store_id,
            'academic_session_id' => $request->academic_session_id ?: AcademicSession::where('store_id', $store_id)->where('is_current', 1)->value('id'),
            'school_class_id'     => $request->school_class_id,
            'name'                => $request->name,
            'exam_type'           => $request->exam_type,
            'status'              => 1,
        ]);
        Toastr::success('Exam created. Add subjects & max marks.');
        return redirect()->route('vendor.school.exams.show', $exam->id);
    }

    public function show($id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $exam = Exam::where('store_id', $store_id)->with(['schoolClass', 'subjects.subject'])->findOrFail($id);
        $subjects = Subject::where('store_id', $store_id)->orderBy('name')->get();
        $sections = ClassSection::where('store_id', $store_id)->where('school_class_id', $exam->school_class_id)->get();
        return view('school::vendor.exams.show', compact('exam', 'subjects', 'sections'));
    }

    public function subject_store(Request $request, $examId)
    {
        $this->ensureSchema();
        $request->validate(['subject_id' => 'required|integer', 'max_marks' => 'required|numeric|min:1', 'pass_marks' => 'required|numeric|min:0']);
        $store_id = Helpers::get_store_id();
        $exam = Exam::where('store_id', $store_id)->findOrFail($examId);
        ExamSubject::updateOrCreate(
            ['store_id' => $store_id, 'exam_id' => $exam->id, 'subject_id' => $request->subject_id],
            ['max_marks' => $request->max_marks, 'pass_marks' => $request->pass_marks]
        );
        Toastr::success('Subject added to exam.');
        return back();
    }

    public function subject_delete($examId, $id)
    {
        MarkEntry::where('store_id', Helpers::get_store_id())->where('exam_subject_id', $id)->delete();
        ExamSubject::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Subject removed.');
        return back();
    }

    public function marks(Request $request, $examId)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $exam = Exam::where('store_id', $store_id)->with('subjects.subject')->findOrFail($examId);

        $examSubjectId = $request->query('exam_subject_id');
        $sectionId     = $request->query('section_id');
        $sections = ClassSection::where('store_id', $store_id)->where('school_class_id', $exam->school_class_id)->get();

        $roster = collect();
        $existing = collect();
        $examSubject = $examSubjectId ? $exam->subjects->firstWhere('id', (int) $examSubjectId) : null;
        if ($examSubject) {
            $roster = StudentEnrollment::where('store_id', $store_id)->where('status', 1)
                ->where('school_class_id', $exam->school_class_id)
                ->when($sectionId, fn($q) => $q->where('class_section_id', $sectionId))
                ->with('student')->get()->filter(fn($e) => $e->student)->sortBy(fn($e) => (int) $e->roll_no)->values();
            $existing = MarkEntry::where('store_id', $store_id)->where('exam_subject_id', $examSubject->id)->get()->keyBy('student_id');
        }

        return view('school::vendor.exams.marks', compact('exam', 'sections', 'examSubject', 'examSubjectId', 'sectionId', 'roster', 'existing'));
    }

    public function marks_store(Request $request, $examId)
    {
        $this->ensureSchema();
        $request->validate(['exam_subject_id' => 'required|integer', 'marks' => 'array']);
        $store_id = Helpers::get_store_id();
        $exam = Exam::where('store_id', $store_id)->findOrFail($examId);
        $examSubject = ExamSubject::where('store_id', $store_id)->where('exam_id', $exam->id)->findOrFail($request->exam_subject_id);

        foreach ($request->input('marks', []) as $studentId => $val) {
            $absent = in_array($studentId, $request->input('absent', []));
            MarkEntry::updateOrCreate(
                ['store_id' => $store_id, 'exam_subject_id' => $examSubject->id, 'student_id' => (int) $studentId],
                [
                    'exam_id'        => $exam->id,
                    'marks_obtained' => $absent ? null : (is_numeric($val) ? min((float) $val, $examSubject->max_marks) : null),
                    'is_absent'      => $absent,
                ]
            );
        }
        Toastr::success('Marks saved.');
        return back();
    }

    public function reportCards(Request $request, $examId)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $exam = Exam::where('store_id', $store_id)->with('schoolClass')->findOrFail($examId);
        $sectionId = $request->query('section_id');
        $sections = ClassSection::where('store_id', $store_id)->where('school_class_id', $exam->school_class_id)->get();

        $cards = collect();
        if ($sectionId || $sections->isEmpty()) {
            $cards = $this->computeResults($exam, $sectionId)->sortByDesc('percentage')->values();
            $rank = 0;
            $cards = $cards->map(function ($c) use (&$rank) { $c['rank'] = ++$rank; return $c; });
        }
        return view('school::vendor.exams.report_cards', compact('exam', 'sections', 'sectionId', 'cards'));
    }

    public function reportCard($examId, $studentId)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $exam = Exam::where('store_id', $store_id)->with('schoolClass')->findOrFail($examId);
        $student = Student::where('store_id', $store_id)->with('currentEnrollment.section')->findOrFail($studentId);
        $store = Store::withoutGlobalScopes()->find($store_id);

        $detail = $this->studentResult($exam, $student->id);

        return view('school::vendor.exams.report_card', compact('exam', 'student', 'store', 'detail'));
    }

    /* ===== result computation ===== */
    private function computeResults(Exam $exam, $sectionId)
    {
        $store_id = $exam->store_id;
        $enr = StudentEnrollment::where('store_id', $store_id)->where('status', 1)
            ->where('school_class_id', $exam->school_class_id)
            ->when($sectionId, fn($q) => $q->where('class_section_id', $sectionId))
            ->with('student')->get()->filter(fn($e) => $e->student);

        return $enr->map(function ($e) use ($exam) {
            $r = $this->studentResult($exam, $e->student_id);
            return [
                'student_id' => $e->student_id,
                'roll'       => $e->roll_no,
                'name'       => $e->student->name,
                'obtained'   => $r['obtained'],
                'max'        => $r['max'],
                'percentage' => $r['percentage'],
                'result'     => $r['result'],
                'grade'      => $r['grade'],
            ];
        })->values();
    }

    private function studentResult(Exam $exam, $studentId): array
    {
        $subjects = $exam->subjects()->with('subject')->get();
        $marks = MarkEntry::where('store_id', $exam->store_id)->where('exam_id', $exam->id)
            ->where('student_id', $studentId)->get()->keyBy('exam_subject_id');

        $rows = [];
        $obtained = 0; $max = 0; $fail = false;
        foreach ($subjects as $es) {
            $m = $marks->get($es->id);
            $got = $m && !$m->is_absent ? (float) $m->marks_obtained : ($m && $m->is_absent ? null : null);
            $max += $es->max_marks;
            if ($got !== null) $obtained += $got;
            $passed = $got !== null && $got >= $es->pass_marks;
            if (!$passed) $fail = true;
            $rows[] = [
                'subject'  => $es->subject?->name,
                'max'      => $es->max_marks,
                'pass'     => $es->pass_marks,
                'obtained' => $m && $m->is_absent ? 'AB' : ($got !== null ? $got : '—'),
                'grade'    => $got !== null ? $this->grade($got, $es->max_marks) : '—',
                'passed'   => $passed,
            ];
        }
        $pct = $max > 0 ? round(($obtained / $max) * 100, 2) : 0;
        return [
            'rows'       => $rows,
            'obtained'   => $obtained,
            'max'        => $max,
            'percentage' => $pct,
            'grade'      => $this->grade($obtained, $max),
            'result'     => $fail ? 'Fail' : 'Pass',
        ];
    } 

    public function notifyResults(Request $request, $examId)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $exam = Exam::where('store_id', $store_id)->with('schoolClass')->findOrFail($examId);
        $sectionId = $request->query('section_id');

        $cards = $this->computeResults($exam, $sectionId);

        foreach ($cards as $c) {
            $student = Student::where('store_id', $store_id)->find($c['student_id']);
            if ($student) {
                $obtained = rtrim(rtrim(number_format($c['obtained'], 2), '0'), '.');
                $max = rtrim(rtrim(number_format($c['max'], 2), '0'), '.');
                $msg = "Dear Parent, the results for {$exam->name} have been published. {$student->name} obtained {$obtained} / {$max} marks (Percentage: {$c['percentage']}%, Grade: {$c['grade']}, Result: {$c['result']}).";
                $push = [
                    'title' => "Exam Results Published: {$exam->name}",
                    'description' => "{$student->name} scored {$obtained}/{$max} ({$c['percentage']}%, Grade: {$c['grade']})."
                ];
                _sendSchoolNotification($student, 'exam_result', $msg, $push);
            }
        }

        Toastr::success('Exam results notifications sent to parents.');
        return back();
    }

    private function grade($obtained, $max): string
    {
        if ($max <= 0) return '—';
        $p = ($obtained / $max) * 100;
        return match (true) {
            $p >= 91 => 'A1', $p >= 81 => 'A2', $p >= 71 => 'B1', $p >= 61 => 'B2',
            $p >= 51 => 'C1', $p >= 41 => 'C2', $p >= 33 => 'D', default => 'E',
        };
    }
}
