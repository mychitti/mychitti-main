<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\SchoolClass;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; 

class StudentAttendanceController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('student_attendances')) {
            DB::statement("CREATE TABLE IF NOT EXISTS student_attendances (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                student_id BIGINT UNSIGNED NOT NULL, academic_session_id BIGINT UNSIGNED NULL,
                school_class_id BIGINT UNSIGNED NULL, class_section_id BIGINT UNSIGNED NULL,
                attendance_date DATE NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'present',
                marked_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_day (store_id, student_id, attendance_date),
                KEY idx_lookup (store_id, school_class_id, class_section_id, attendance_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function mark(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $classId   = $request->query('class_id');
        $sectionId = $request->query('section_id');
        $date      = $request->query('date', now()->toDateString());

        $classes  = SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->get();
        $sections = ClassSection::where('store_id', $store_id)->with('schoolClass')->get();

        $roster = collect();
        $existing = collect();
        if ($classId) {
            $roster = StudentEnrollment::where('store_id', $store_id)->where('status', 1)
                ->where('school_class_id', $classId)
                ->when($sectionId, fn($q) => $q->where('class_section_id', $sectionId))
                ->with('student')
                ->get()
                ->filter(fn($e) => $e->student)
                ->sortBy(fn($e) => (int) $e->roll_no)
                ->values();

            $existing = StudentAttendance::where('store_id', $store_id)
                ->whereDate('attendance_date', $date)
                ->where('school_class_id', $classId)
                ->when($sectionId, fn($q) => $q->where('class_section_id', $sectionId))
                ->get()->keyBy('student_id');
        }

        return view('school::vendor.attendance.mark', compact('classes', 'sections', 'classId', 'sectionId', 'date', 'roster', 'existing'));
    }

    public function store(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'class_id' => 'required|integer',
            'date'     => 'required|date',
            'status'   => 'required|array',
        ]);
        $store_id = Helpers::get_store_id();
        $sessionId = AcademicSession::where('store_id', $store_id)->where('is_current', 1)->value('id');
        $by = auth('vendor_employee')->id() ?? auth('vendor')->id();

        foreach ($request->status as $studentId => $status) {
            if (!array_key_exists($status, StudentAttendance::STATUSES)) continue;

            $isAbsentNow = ($status === 'absent');
            if ($isAbsentNow) {
                // Check if already marked absent to avoid duplicate notifications
                $alreadyAbsent = StudentAttendance::where([
                    'store_id' => $store_id,
                    'student_id' => (int) $studentId,
                    'attendance_date' => $request->date,
                    'status' => 'absent'
                ])->exists();
                
                if (!$alreadyAbsent) {
                    $student = \App\Models\Student::find($studentId);
                    if ($student) {
                        $dateStr = date('d-M-Y', strtotime($request->date));
                        $msg = "Dear Parent, your child {$student->name} was marked ABSENT on {$dateStr}. Please contact the school for any query.";
                        $push = [
                            'title' => 'Student Absence Alert',
                            'description' => "Your child {$student->name} was marked ABSENT today ({$dateStr})."
                        ];
                        _sendSchoolNotification($student, 'student_absence', $msg, $push);
                    }
                }
            }

            StudentAttendance::updateOrCreate(
                ['store_id' => $store_id, 'student_id' => (int) $studentId, 'attendance_date' => $request->date],
                [
                    'academic_session_id' => $sessionId,
                    'school_class_id'     => $request->class_id,
                    'class_section_id'    => $request->section_id ?: null,
                    'status'              => $status,
                    'marked_by'           => $by,
                ]
            );
        }

        Toastr::success('Attendance saved.');
        return redirect()->route('vendor.school.student-attendance.mark', [
            'class_id' => $request->class_id, 'section_id' => $request->section_id, 'date' => $request->date,
        ]);
    }

    public function report(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $classId   = $request->query('class_id');
        $sectionId = $request->query('section_id');
        $month     = $request->query('month', now()->format('Y-m'));
        [$y, $m]   = array_pad(explode('-', $month), 2, now()->format('m'));
        $from = "$y-$m-01";
        $to   = date('Y-m-t', strtotime($from));

        $classes  = SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->get();
        $sections = ClassSection::where('store_id', $store_id)->with('schoolClass')->get();

        $rows = collect();
        $workingDays = 0;
        if ($classId) {
            $workingDays = StudentAttendance::where('store_id', $store_id)
                ->where('school_class_id', $classId)
                ->when($sectionId, fn($q) => $q->where('class_section_id', $sectionId))
                ->whereBetween('attendance_date', [$from, $to])
                ->distinct()->count('attendance_date');

            $enrollments = StudentEnrollment::where('store_id', $store_id)->where('status', 1)
                ->where('school_class_id', $classId)
                ->when($sectionId, fn($q) => $q->where('class_section_id', $sectionId))
                ->with('student')->get()->filter(fn($e) => $e->student);

            $att = StudentAttendance::where('store_id', $store_id)
                ->whereBetween('attendance_date', [$from, $to])
                ->whereIn('student_id', $enrollments->pluck('student_id'))
                ->get()->groupBy('student_id');

            $rows = $enrollments->map(function ($e) use ($att) {
                $a = $att->get($e->student_id, collect());
                $present = $a->whereIn('status', ['present', 'late'])->count();
                $halfday = $a->where('status', 'half_day')->count();
                $effective = $present + ($halfday * 0.5);
                $total = $a->whereIn('status', ['present', 'late', 'half_day', 'absent'])->count();
                $pct = $total > 0 ? round(($effective / $total) * 100, 1) : 0;
                return [
                    'roll'    => $e->roll_no,
                    'name'    => $e->student->name,
                    'present' => $present,
                    'absent'  => $a->where('status', 'absent')->count(),
                    'late'    => $a->where('status', 'late')->count(),
                    'leave'   => $a->where('status', 'leave')->count(),
                    'half'    => $halfday,
                    'pct'     => $pct,
                ];
            })->sortBy(fn($r) => (int) $r['roll'])->values();
        }

        return view('school::vendor.attendance.report', compact('classes', 'sections', 'classId', 'sectionId', 'month', 'rows', 'workingDays'));
    }
}
