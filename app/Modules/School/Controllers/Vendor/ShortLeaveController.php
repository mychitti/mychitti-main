<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\SchoolClass;
use App\Models\Store;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\StudentShortLeave;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShortLeaveController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('student_short_leaves')) {
            DB::statement("CREATE TABLE IF NOT EXISTS student_short_leaves (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                student_id BIGINT UNSIGNED NOT NULL, academic_session_id BIGINT UNSIGNED NULL,
                gate_pass_no VARCHAR(50) NULL, leave_date DATE NOT NULL,
                out_time VARCHAR(10) NULL, return_time VARCHAR(10) NULL, is_returning TINYINT(1) NOT NULL DEFAULT 0,
                reason VARCHAR(500) NULL, taken_by VARCHAR(150) NULL, taken_by_relation VARCHAR(60) NULL, contact VARCHAR(30) NULL,
                issued_by VARCHAR(150) NULL, status VARCHAR(20) NOT NULL DEFAULT 'out',
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id), KEY idx_student (student_id), KEY idx_date (leave_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function index(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $date   = $request->query('date', now()->toDateString());
        $status = $request->query('status');
        $search = trim($request->query('search', ''));

        $dayBase = StudentShortLeave::where('store_id', $store_id)->whereDate('leave_date', $date);
        $counts = [
            'total'    => (clone $dayBase)->count(),
            'out'      => (clone $dayBase)->where('status', 'out')->count(),
            'returned' => (clone $dayBase)->where('status', 'returned')->count(),
        ];

        $passes = StudentShortLeave::where('store_id', $store_id)
            ->whereDate('leave_date', $date)
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($search, fn($q) => $q->whereHas('student', fn($s) => $s->where('name', 'like', "%$search%")->orWhere('admission_no', 'like', "%$search%")))
            ->with('student.currentEnrollment.schoolClass')
            ->orderByDesc('id')->paginate(config('default_pagination'))->withQueryString();

        return view('school::vendor.short-leave.index', compact('passes', 'counts', 'date', 'status', 'search'));
    }

    public function create(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $classId   = $request->query('class_id');
        $sectionId = $request->query('section_id');

        $classes  = SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->get();
        $sections = ClassSection::where('store_id', $store_id)->with('schoolClass')->get();

        $students = collect();
        if ($classId) {
            $students = StudentEnrollment::where('store_id', $store_id)->where('status', 1)
                ->where('school_class_id', $classId)
                ->when($sectionId, fn($q) => $q->where('class_section_id', $sectionId))
                ->with('student')->get()
                ->filter(fn($e) => $e->student)
                ->sortBy(fn($e) => (int) $e->roll_no)->values();
        }

        return view('school::vendor.short-leave.create', compact('classes', 'sections', 'classId', 'sectionId', 'students'));
    }

    public function store(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'student_id' => 'required|integer',
            'out_time'   => 'required|string|max:10',
            'reason'     => 'nullable|string|max:500',
            'taken_by'   => 'nullable|string|max:150',
        ]);
        $store_id = Helpers::get_store_id();

        $student = Student::where('store_id', $store_id)->with('currentEnrollment')->findOrFail($request->student_id);
        $isReturning = $request->boolean('is_returning');
        $passNo = 'GP-' . school_next_serial(StudentShortLeave::class, $store_id, 'gate_pass_no');

        $pass = StudentShortLeave::create([
            'store_id'            => $store_id,
            'student_id'          => $student->id,
            'academic_session_id' => $student->currentEnrollment?->academic_session_id,
            'gate_pass_no'        => $passNo,
            'leave_date'          => now()->toDateString(),
            'out_time'            => $request->out_time,
            'is_returning'        => $isReturning,
            'reason'              => $request->reason,
            'taken_by'            => $request->taken_by,
            'taken_by_relation'   => $request->taken_by_relation,
            'contact'             => $request->contact,
            'issued_by'           => $this->currentUserName(),
            'status'              => 'out',
        ]);

        // If the student isn't coming back today, optionally reflect a half-day in attendance.
        if (!$isReturning && $request->boolean('mark_half_day') && Schema::hasTable('student_attendances')) {
            $existing = StudentAttendance::where('store_id', $store_id)->where('student_id', $student->id)
                ->whereDate('attendance_date', $pass->leave_date)->first();
            if (!$existing || in_array($existing->status, ['present', 'late'])) {
                StudentAttendance::updateOrCreate(
                    ['store_id' => $store_id, 'student_id' => $student->id, 'attendance_date' => $pass->leave_date],
                    [
                        'academic_session_id' => $pass->academic_session_id,
                        'school_class_id'     => $student->currentEnrollment?->school_class_id,
                        'class_section_id'    => $student->currentEnrollment?->class_section_id,
                        'status'              => 'half_day',
                        'marked_by'           => auth('vendor_employee')->id() ?? auth('vendor')->id(),
                    ]
                );
            }
        }

        Toastr::success('Gate pass ' . $passNo . ' issued.');
        return redirect()->route('vendor.school.short-leave.slip', $pass->id);
    }

    public function markReturn(Request $request, $id)
    {
        $this->ensureSchema();
        $pass = StudentShortLeave::where('store_id', Helpers::get_store_id())->findOrFail($id);
        $pass->update(['status' => 'returned', 'return_time' => $request->return_time ?: now()->format('H:i')]);
        Toastr::success('Marked as returned.');
        return back();
    }

    public function slip($id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $pass  = StudentShortLeave::where('store_id', $store_id)->with('student.currentEnrollment.schoolClass')->findOrFail($id);
        $store = Store::withoutGlobalScopes()->find($store_id);
        return view('school::vendor.short-leave.slip', compact('pass', 'store'));
    }

    public function delete($id)
    {
        StudentShortLeave::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Gate pass removed.');
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
}
