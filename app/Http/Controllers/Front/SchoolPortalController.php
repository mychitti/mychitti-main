<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\FeeInvoice;
use App\Models\MarkEntry;
use App\Models\SchoolHomework;
use App\Models\SchoolNotice;
use App\Models\Store;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentGuardianLink;
use App\Models\StudentLeave;
use App\Models\TimetableEntry;
use App\Models\TimetablePeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchoolPortalController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('student_guardian_links')) {
            DB::statement("CREATE TABLE IF NOT EXISTS student_guardian_links (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL, student_id BIGINT UNSIGNED NOT NULL,
                store_id BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), UNIQUE KEY uniq_link (user_id, student_id), KEY idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    private function ensureLeaveSchema(): void
    {
        if (!Schema::hasTable('student_leaves')) {
            DB::statement("CREATE TABLE IF NOT EXISTS student_leaves (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                student_id BIGINT UNSIGNED NOT NULL, academic_session_id BIGINT UNSIGNED NULL,
                leave_type VARCHAR(30) NOT NULL DEFAULT 'Sick',
                from_date DATE NOT NULL, to_date DATE NOT NULL, days INT NOT NULL DEFAULT 1,
                reason VARCHAR(500) NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending',
                applied_by VARCHAR(150) NULL, reviewed_by VARCHAR(150) NULL, remarks VARCHAR(500) NULL,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id), KEY idx_student (student_id), KEY idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    private function uid()
    {
        return auth('web')->id();
    }

    /** My Children — list linked students + link form. */
    public function index()
    {
        $this->ensureSchema();
        $children = collect();
        if (Schema::hasTable('school_students')) {
            $ids = StudentGuardianLink::where('user_id', $this->uid())->pluck('student_id');
            if ($ids->count()) {
                $children = Student::withoutGlobalScope('schoolBranch')->whereIn('id', $ids)
                    ->with(['currentEnrollment.schoolClass', 'currentEnrollment.section'])->get();
                $stores = Store::whereIn('id', $children->pluck('store_id'))->pluck('name', 'id');
                $children->each(fn($c) => $c->setAttribute('school_name', $stores[$c->store_id] ?? 'School'));
            }
        }
        return view('front-views.school.portal.index', compact('children'));
    }

    /** Link a student to the logged-in parent via admission no + DOB. */
    public function link(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'admission_no' => 'required|string|max:60',
            'dob'          => 'required|date',
        ]);

        if (!Schema::hasTable('school_students')) {
            return back()->with('error', 'No matching student found.');
        }

        $student = Student::withoutGlobalScope('schoolBranch')
            ->where('admission_no', trim($request->admission_no))
            ->whereDate('dob', $request->dob)->first();

        if (!$student) {
            return back()->with('error', 'No student found with that admission number and date of birth. Please check with the school.');
        }

        StudentGuardianLink::firstOrCreate(
            ['user_id' => $this->uid(), 'student_id' => $student->id],
            ['store_id' => $student->store_id]
        );

        return redirect()->route('school.portal.show', $student->id)->with('success', 'Student linked to your account.');
    }

    public function unlink($id)
    {
        $this->ensureSchema();
        StudentGuardianLink::where('user_id', $this->uid())->where('student_id', $id)->delete();
        return redirect()->route('school.portal.index')->with('success', 'Student removed from your account.');
    }

    private function linkedOrFail($id): Student
    {
        $this->ensureSchema();
        abort_unless(StudentGuardianLink::where('user_id', $this->uid())->where('student_id', $id)->exists(), 403);
        return Student::withoutGlobalScope('schoolBranch')
            ->with(['currentEnrollment.schoolClass', 'currentEnrollment.section', 'currentEnrollment.session'])
            ->findOrFail($id);
    }

    /** Child dashboard — attendance, results, fees, homework, timetable, notices. */
    public function show($id)
    {
        $student   = $this->linkedOrFail($id);
        $store     = Store::find($student->store_id);
        $enr       = $student->currentEnrollment;
        $classId   = $enr?->school_class_id;
        $sectionId = $enr?->class_section_id;

        // ---- Attendance (this month) ----
        $att = ['pct' => 0, 'present' => 0, 'marked' => 0];
        if (Schema::hasTable('student_attendances')) {
            $from = now()->startOfMonth()->toDateString();
            $to   = now()->toDateString();
            $base = StudentAttendance::withoutGlobalScope('schoolBranch')->where('student_id', $id)->whereBetween('attendance_date', [$from, $to]);
            $marked  = (clone $base)->count();
            $present = (clone $base)->whereIn('status', ['present', 'late', 'half_day'])->count();
            $att = ['marked' => $marked, 'present' => $present, 'pct' => $marked ? round($present / $marked * 100) : 0];
        }

        // ---- Fees ----
        $fees = ['due' => 0, 'invoices' => collect()];
        if (Schema::hasTable('fee_invoices')) {
            $invoices = FeeInvoice::withoutGlobalScope('schoolBranch')->where('student_id', $id)->orderByDesc('id')->limit(10)->get();
            $fees = ['due' => (float) $invoices->where('due_amount', '>', 0)->sum('due_amount'), 'invoices' => $invoices];
        }

        // ---- Results (latest exam with marks) ----
        $result = null;
        if (Schema::hasTable('mark_entries')) {
            $latestExamId = MarkEntry::where('student_id', $id)->orderByDesc('id')->value('exam_id');
            if ($latestExamId) {
                $result = [
                    'exam'  => Exam::withoutGlobalScope('schoolBranch')->find($latestExamId),
                    'marks' => MarkEntry::where('student_id', $id)->where('exam_id', $latestExamId)->with('examSubject.subject')->get(),
                ];
            }
        }

        // ---- Homework ----
        $homework = collect();
        if (Schema::hasTable('school_homeworks') && $classId) {
            $homework = SchoolHomework::withoutGlobalScope('schoolBranch')->where('school_class_id', $classId)
                ->when($sectionId, fn($q) => $q->where(fn($w) => $w->whereNull('class_section_id')->orWhere('class_section_id', $sectionId)))
                ->with('subject')->orderByDesc('assign_date')->orderByDesc('id')->limit(8)->get();
        }

        // ---- Timetable (student's section) ----
        $periods = collect();
        $grid = [];
        if (Schema::hasTable('timetable_entries') && $sectionId) {
            $periods = TimetablePeriod::withoutGlobalScope('schoolBranch')->where('store_id', $student->store_id)->where('status', 1)
                ->orderBy('sort_order')->orderBy('period_no')->orderBy('id')->get();
            foreach (TimetableEntry::withoutGlobalScope('schoolBranch')->where('class_section_id', $sectionId)->with(['subject', 'teacher'])->get() as $e) {
                $grid[$e->day_of_week][$e->timetable_period_id] = $e;
            }
        }
        $days = TimetableEntry::DAYS;

        // ---- Notices ----
        $notices = collect();
        if (Schema::hasTable('school_notices')) {
            $notices = SchoolNotice::withoutGlobalScope('schoolBranch')->where('store_id', $student->store_id)->where('is_published', 1)
                ->whereIn('audience', ['all', 'students', 'parents'])
                ->where(fn($q) => $q->whereNull('school_class_id')->orWhere('school_class_id', $classId))
                ->where(fn($q) => $q->whereNull('expires_on')->orWhereDate('expires_on', '>=', now()->toDateString()))
                ->orderByDesc('is_pinned')->orderByDesc('notice_date')->orderByDesc('id')->limit(6)->get();
        }

        // ---- Leave requests ----
        $leaves = collect();
        if (Schema::hasTable('student_leaves')) {
            $leaves = StudentLeave::withoutGlobalScope('schoolBranch')->where('student_id', $id)
                ->orderByDesc('id')->limit(10)->get();
        }

        return view('front-views.school.portal.show', compact('student', 'store', 'att', 'fees', 'result', 'homework', 'periods', 'grid', 'days', 'notices', 'leaves'));
    }

    /** Parent submits a leave request for their linked child (lands as pending in the school's pipeline). */
    public function leaveStore(Request $request, $id)
    {
        $student = $this->linkedOrFail($id);
        $this->ensureLeaveSchema();

        $request->validate([
            'leave_type' => 'required|string|max:30',
            'from_date'  => 'required|date',
            'to_date'    => 'required|date|after_or_equal:from_date',
            'reason'     => 'nullable|string|max:500',
        ]);

        $from = Carbon::parse($request->from_date);
        $to   = Carbon::parse($request->to_date);
        $u    = auth('web')->user();
        $name = $u->name ?? trim(($u->f_name ?? '') . ' ' . ($u->l_name ?? ''));

        StudentLeave::create([
            'store_id'            => $student->store_id,
            'branch_id'           => $student->branch_id,
            'student_id'          => $student->id,
            'academic_session_id' => $student->currentEnrollment?->academic_session_id,
            'leave_type'          => $request->leave_type,
            'from_date'           => $from->toDateString(),
            'to_date'             => $to->toDateString(),
            'days'                => $from->diffInDays($to) + 1,
            'reason'              => $request->reason,
            'status'              => 'pending',
            'applied_by'          => ($name ?: 'Parent') . ' (Parent)',
        ]);

        return redirect()->route('school.portal.show', $student->id)
            ->with('success', 'Leave request submitted. The school will review it.');
    }

    /** Printable student ID card (reuses the vendor-side card template). */
    public function idCard($id)
    {
        $student = $this->linkedOrFail($id);
        $store   = Store::find($student->store_id);
        $branch  = $student->branch_id ? \App\Models\Branch::find($student->branch_id) : null;
        return view('school::vendor.students.id_card', compact('student', 'store', 'branch'));
    }
}
