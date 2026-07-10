<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\StudentLeave;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentLeaveController extends Controller
{
    private function ensureSchema(): void
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

    public function index(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $status = $request->query('status');
        $search = trim($request->query('search', ''));

        $base = StudentLeave::where('store_id', $store_id);

        $counts = [
            'pending'  => (clone $base)->where('status', 'pending')->count(),
            'approved' => (clone $base)->where('status', 'approved')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
        ];

        $leaves = StudentLeave::where('store_id', $store_id)
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($search, fn($q) => $q->whereHas('student', fn($s) => $s->where('name', 'like', "%$search%")->orWhere('admission_no', 'like', "%$search%")))
            ->with('student.currentEnrollment.schoolClass')
            ->orderByRaw("FIELD(status,'pending','approved','rejected')")
            ->orderByDesc('id')
            ->paginate(config('default_pagination'))->withQueryString();

        return view('school::vendor.leave.index', compact('leaves', 'counts', 'status', 'search'));
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
                ->sortBy(fn($e) => (int) $e->roll_no)
                ->values();
        }

        return view('school::vendor.leave.create', compact('classes', 'sections', 'classId', 'sectionId', 'students'));
    }

    public function store(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'student_id' => 'required|integer',
            'leave_type' => 'required|string|max:30',
            'from_date'  => 'required|date',
            'to_date'    => 'required|date|after_or_equal:from_date',
            'reason'     => 'nullable|string|max:500',
            'status'     => 'required|in:pending,approved',
        ]);
        $store_id = Helpers::get_store_id();

        $student = Student::where('store_id', $store_id)->with('currentEnrollment')->findOrFail($request->student_id);

        $from = Carbon::parse($request->from_date);
        $to   = Carbon::parse($request->to_date);
        $days = $from->diffInDays($to) + 1;
        $by   = $this->currentUserName();

        $leave = StudentLeave::create([
            'store_id'            => $store_id,
            'student_id'          => $student->id,
            'academic_session_id' => $student->currentEnrollment?->academic_session_id,
            'leave_type'          => $request->leave_type,
            'from_date'           => $from->toDateString(),
            'to_date'             => $to->toDateString(),
            'days'                => $days,
            'reason'              => $request->reason,
            'status'              => $request->status,
            'applied_by'          => $by,
            'reviewed_by'         => $request->status === 'approved' ? $by : null,
        ]);

        if ($leave->status === 'approved') {
            $this->markAttendance($leave, $student);
        }

        Toastr::success('Leave ' . ($leave->status === 'approved' ? 'recorded & approved' : 'request submitted') . '.');
        return redirect()->route('vendor.school.student-leave.index');
    }

    public function approve($id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $leave = StudentLeave::where('store_id', $store_id)->findOrFail($id);
        if ($leave->status !== 'approved') {
            $leave->update(['status' => 'approved', 'reviewed_by' => $this->currentUserName()]);
            $student = Student::where('store_id', $store_id)->with('currentEnrollment')->find($leave->student_id);
            if ($student) {
                $this->markAttendance($leave, $student);
                // Notify parent 
                $from = date('d-M-Y', strtotime($leave->from_date));
                $to = date('d-M-Y', strtotime($leave->to_date));
                $msg = "Dear Parent, the leave request for {$student->name} from {$from} to {$to} ({$leave->days} days) has been APPROVED.";
                $push = [
                    'title' => 'Leave Request Approved',
                    'description' => "Leave for {$student->name} from {$from} to {$to} is approved."
                ];
                _sendSchoolNotification($student, 'leave_status', $msg, $push);
            }
        }
        Toastr::success('Leave approved — attendance marked as Leave for those dates.');
        return back();
    }

    public function reject(Request $request, $id)
    {
        $this->ensureSchema();
        $leave = StudentLeave::where('store_id', Helpers::get_store_id())->findOrFail($id);
        $leave->update([
            'status'      => 'rejected',
            'reviewed_by' => $this->currentUserName(),
            'remarks'     => $request->remarks,
        ]);

        $student = Student::where('store_id', $leave->store_id)->find($leave->student_id);
        if ($student) {
            $from = date('d-M-Y', strtotime($leave->from_date));
            $to = date('d-M-Y', strtotime($leave->to_date));
            $remarksStr = $request->remarks ? " Reason/Remarks: " . $request->remarks : "";
            $msg = "Dear Parent, the leave request for {$student->name} from {$from} to {$to} has been REJECTED.{$remarksStr}";
            $push = [
                'title' => 'Leave Request Rejected',
                'description' => "Leave for {$student->name} from {$from} to {$to} is rejected."
            ];
            _sendSchoolNotification($student, 'leave_status', $msg, $push);
        }

        Toastr::success('Leave rejected.');
        return back();
    }

    public function delete($id)
    {
        StudentLeave::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Leave request removed.');
        return back();
    }

    /** Mark every day in the leave span as 'leave' in attendance (preserves holidays). */
    private function markAttendance(StudentLeave $leave, Student $student): void
    {
        if (!Schema::hasTable('student_attendances')) return;

        $enr = $student->currentEnrollment;
        $by  = auth('vendor_employee')->id() ?? auth('vendor')->id();

        foreach (Carbon::parse($leave->from_date)->daysUntil(Carbon::parse($leave->to_date)->addDay()) as $day) {
            $date = $day->toDateString();
            $existing = StudentAttendance::where('store_id', $leave->store_id)
                ->where('student_id', $leave->student_id)->whereDate('attendance_date', $date)->first();
            if ($existing && $existing->status === 'holiday') continue;

            StudentAttendance::updateOrCreate(
                ['store_id' => $leave->store_id, 'student_id' => $leave->student_id, 'attendance_date' => $date],
                [
                    'academic_session_id' => $leave->academic_session_id,
                    'school_class_id'     => $enr?->school_class_id,
                    'class_section_id'    => $enr?->class_section_id,
                    'status'              => 'leave',
                    'marked_by'           => $by,
                ]
            );
        }
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
