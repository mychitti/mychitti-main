<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\SchoolClass;
use App\Models\Subject;  
use App\Models\StudentEnrollment;
use App\Models\SchoolHomework;
use App\Models\SchoolHomeworkSubmission;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomeworkController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('school_homeworks')) {
            DB::statement("CREATE TABLE IF NOT EXISTS school_homeworks (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL,
                branch_id BIGINT UNSIGNED NULL,
                academic_session_id BIGINT UNSIGNED NULL,
                school_class_id BIGINT UNSIGNED NOT NULL,
                class_section_id BIGINT UNSIGNED NULL,
                subject_id BIGINT UNSIGNED NULL,
                title VARCHAR(191) NOT NULL,
                description TEXT NULL,
                assign_date DATE NOT NULL,
                submission_date DATE NOT NULL,
                max_marks DECIMAL(6,2) DEFAULT NULL,
                attachment VARCHAR(191) DEFAULT NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL DEFAULT NULL,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_store_class (store_id, branch_id, school_class_id, class_section_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!Schema::hasTable('school_homework_submissions')) {
            DB::statement("CREATE TABLE IF NOT EXISTS school_homework_submissions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL,
                branch_id BIGINT UNSIGNED NULL,
                school_homework_id BIGINT UNSIGNED NOT NULL,
                student_id BIGINT UNSIGNED NOT NULL,
                submission_date DATE NOT NULL,
                student_notes TEXT NULL,
                attachment VARCHAR(191) DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'submitted',
                marks_obtained DECIMAL(6,2) DEFAULT NULL,
                remarks TEXT NULL,
                evaluated_by BIGINT UNSIGNED NULL,
                evaluated_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP NULL DEFAULT NULL,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_submission (school_homework_id, student_id),
                KEY idx_store_student (store_id, student_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function index(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $classId   = $request->query('class_id');
        $sectionId = $request->query('section_id');
        $subjectId = $request->query('subject_id');
        $search    = trim($request->query('search', ''));

        $homeworks = SchoolHomework::where('store_id', $store_id)
            ->with(['schoolClass', 'classSection', 'subject'])
            ->withCount('submissions')
            ->when($classId, fn($q) => $q->where('school_class_id', $classId))
            ->when($sectionId, fn($q) => $q->where('class_section_id', $sectionId))
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->when($search, fn($q) => $q->where('title', 'like', "%$search%"))
            ->orderByDesc('assign_date')
            ->orderByDesc('id')
            ->paginate(config('default_pagination'))->withQueryString();

        $classes  = SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->get();
        $sections = ClassSection::where('store_id', $store_id)->get();
        $subjects = Subject::where('store_id', $store_id)->where('status', 1)->orderBy('name')->get();

        return view('school::vendor.homework.index', compact('homeworks', 'classes', 'sections', 'subjects', 'classId', 'sectionId', 'subjectId', 'search'));
    }

    public function create()
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $homework = null;

        $classes  = SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->get();
        $sections = ClassSection::where('store_id', $store_id)->get();
        $subjects = Subject::where('store_id', $store_id)->where('status', 1)->orderBy('name')->get();

        return view('school::vendor.homework.form', compact('homework', 'classes', 'sections', 'subjects'));
    }

    public function store(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $request->validate([
            'school_class_id'  => 'required|integer',
            'class_section_id' => 'nullable|integer',
            'subject_id'       => 'required|integer',
            'title'            => 'required|string|max:191',
            'description'      => 'nullable|string',
            'assign_date'      => 'required|date',
            'submission_date'  => 'required|date|after_or_equal:assign_date',
            'max_marks'        => 'nullable|numeric|min:0',
            'attachment'       => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,zip|max:8192',
        ]);

        $attachment = null;
        if ($request->hasFile('attachment')) {
            $attachment = Helpers::upload('school/homework/', $request->file('attachment')->getClientOriginalExtension(), $request->file('attachment'));
        }

        $sessionId = AcademicSession::where('store_id', $store_id)->where('is_current', 1)->value('id');
        $by = auth('vendor_employee')->id() ?? auth('vendor')->id();

        $homework = SchoolHomework::create([
            'store_id'            => $store_id,
            'academic_session_id' => $sessionId,
            'school_class_id'     => $request->school_class_id,
            'class_section_id'    => $request->class_section_id ?: null,
            'subject_id'          => $request->subject_id,
            'title'               => $request->title,
            'description'         => $request->description,
            'assign_date'         => $request->assign_date,
            'submission_date'     => $request->submission_date,
            'max_marks'           => $request->max_marks,
            'attachment'          => $attachment,
            'created_by'          => $by,
        ]);

        // Notify parents about homework
        $subjectName = \App\Models\Subject::where('id', $request->subject_id)->value('name') ?? 'Subject';
        $className = \App\Models\SchoolClass::where('id', $request->school_class_id)->value('name') ?? 'Class';
        
        $studentIds = StudentEnrollment::where('store_id', $store_id)
            ->where('status', 1)
            ->where('school_class_id', $request->school_class_id)
            ->when($request->class_section_id, fn($q) => $q->where('class_section_id', $request->class_section_id))
            ->pluck('student_id');
            
        $students = \App\Models\Student::whereIn('id', $studentIds)->where('status', 1)->get();
        if ($students->isNotEmpty()) {
            $subDate = date('d-M-Y', strtotime($request->submission_date));
            $msg = "New Homework assigned for Class {$className} - Subject: {$subjectName}. Title: {$request->title}. Submission Date: {$subDate}. Please check your portal.";
            $push = [
                'title' => "New Homework Assigned: {$subjectName}",
                'description' => "Homework: {$request->title}. Submit by {$subDate}."
            ];
            _sendSchoolNotificationBulk($students, 'homework', $msg, $push);
        }

        Toastr::success('Homework assigned successfully.');
        return redirect()->route('vendor.school.homework.index');
    }

    public function edit($id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $homework = SchoolHomework::where('store_id', $store_id)->findOrFail($id);

        $classes  = SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->get();
        $sections = ClassSection::where('store_id', $store_id)->get();
        $subjects = Subject::where('store_id', $store_id)->where('status', 1)->orderBy('name')->get();

        return view('school::vendor.homework.form', compact('homework', 'classes', 'sections', 'subjects'));
    }

    public function update(Request $request, $id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $homework = SchoolHomework::where('store_id', $store_id)->findOrFail($id);

        $request->validate([
            'school_class_id'  => 'required|integer',
            'class_section_id' => 'nullable|integer',
            'subject_id'       => 'required|integer',
            'title'            => 'required|string|max:191',
            'description'      => 'nullable|string',
            'assign_date'      => 'required|date',
            'submission_date'  => 'required|date|after_or_equal:assign_date',
            'max_marks'        => 'nullable|numeric|min:0',
            'attachment'       => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,zip|max:8192',
        ]);

        $attachment = $homework->attachment;
        if ($request->hasFile('attachment')) {
            if ($homework->attachment) {
                Helpers::delete_file('school/homework/', $homework->attachment);
            }
            $attachment = Helpers::upload('school/homework/', $request->file('attachment')->getClientOriginalExtension(), $request->file('attachment'));
        }

        $homework->update([
            'school_class_id'  => $request->school_class_id,
            'class_section_id' => $request->class_section_id ?: null,
            'subject_id'       => $request->subject_id,
            'title'            => $request->title,
            'description'      => $request->description,
            'assign_date'      => $request->assign_date,
            'submission_date'  => $request->submission_date,
            'max_marks'        => $request->max_marks,
            'attachment'       => $attachment,
        ]);

        Toastr::success('Homework updated successfully.');
        return redirect()->route('vendor.school.homework.index');
    }

    public function delete($id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $homework = SchoolHomework::where('store_id', $store_id)->findOrFail($id);

        if ($homework->attachment) {
            Helpers::delete_file('school/homework/', $homework->attachment);
        }

        $submissions = SchoolHomeworkSubmission::where('school_homework_id', $homework->id)->get();
        foreach ($submissions as $sub) {
            if ($sub->attachment) {
                Helpers::delete_file('school/homework/', $sub->attachment);
            }
            $sub->delete();
        }

        $homework->delete();

        Toastr::success('Homework assignment removed.');
        return back();
    }

    public function submissions(Request $request, $id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $homework = SchoolHomework::where('store_id', $store_id)->with(['schoolClass', 'classSection', 'subject'])->findOrFail($id);

        $roster = StudentEnrollment::where('store_id', $store_id)->where('status', 1)
            ->where('school_class_id', $homework->school_class_id)
            ->when($homework->class_section_id, fn($q) => $q->where('class_section_id', $homework->class_section_id))
            ->with('student')
            ->get()
            ->filter(fn($e) => $e->student)
            ->sortBy(fn($e) => (int) $e->roll_no)
            ->values();

        $existing = SchoolHomeworkSubmission::where('school_homework_id', $homework->id)
            ->get()
            ->keyBy('student_id');

        return view('school::vendor.homework.submissions', compact('homework', 'roster', 'existing'));
    }

    public function evaluate(Request $request, $id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $homework = SchoolHomework::where('store_id', $store_id)->findOrFail($id);

        $request->validate([
            'status' => 'required|array',
            'marks'  => 'nullable|array',
            'remarks'=> 'nullable|array',
        ]);

        $by = auth('vendor_employee')->id() ?? auth('vendor')->id();

        foreach ($request->status as $studentId => $status) {
            if (!array_key_exists($status, SchoolHomeworkSubmission::STATUSES)) continue;

            $obtained = $request->input("marks.{$studentId}");
            $remarks  = $request->input("remarks.{$studentId}");

            SchoolHomeworkSubmission::updateOrCreate(
                ['store_id' => $store_id, 'school_homework_id' => $homework->id, 'student_id' => (int) $studentId],
                [
                    'submission_date' => now()->toDateString(),
                    'status'          => $status,
                    'marks_obtained'  => is_numeric($obtained) ? min((float) $obtained, $homework->max_marks ?? 9999) : null,
                    'remarks'         => $remarks,
                    'evaluated_by'    => $by,
                    'evaluated_at'    => now(),
                ]
            );
        }

        Toastr::success('Evaluation completed.');
        return redirect()->route('vendor.school.homework.submissions', $homework->id);
    }
}
