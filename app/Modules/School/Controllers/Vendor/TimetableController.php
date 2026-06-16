<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\ClassSubjectTeacher;
use App\Models\SchoolClass;
use App\Models\Store;
use App\Models\Subject;
use App\Models\TimetableEntry;
use App\Models\TimetablePeriod;
use App\Models\TimetableSubstitution;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class TimetableController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('timetable_periods')) {
            DB::statement("CREATE TABLE IF NOT EXISTS timetable_periods (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                period_no INT DEFAULT 0, name VARCHAR(100) NOT NULL,
                start_time TIME NULL, end_time TIME NULL, is_break TINYINT(1) DEFAULT 0,
                sort_order INT DEFAULT 0, status TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('timetable_entries')) {
            DB::statement("CREATE TABLE IF NOT EXISTS timetable_entries (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                school_class_id BIGINT UNSIGNED NOT NULL, class_section_id BIGINT UNSIGNED NULL,
                day_of_week TINYINT NOT NULL, timetable_period_id BIGINT UNSIGNED NOT NULL,
                subject_id BIGINT UNSIGNED NULL, teacher_emp_id BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id),
                KEY idx_section (class_section_id), KEY idx_teacher (teacher_emp_id, day_of_week)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('timetable_substitutions')) {
            DB::statement("CREATE TABLE IF NOT EXISTS timetable_substitutions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                sub_date DATE NOT NULL, timetable_entry_id BIGINT UNSIGNED NOT NULL,
                substitute_teacher_emp_id BIGINT UNSIGNED NULL, reason VARCHAR(255) NULL,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id), KEY idx_date (sub_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    /* ===== Grid editor / landing ===== */
    public function index(Request $request)
    {
        $this->ensureSchema();
        $store_id  = Helpers::get_store_id();
        $classId   = $request->query('class_id');
        $sectionId = $request->query('section_id');

        $classes  = SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->orderBy('id')->get();
        $sections = ClassSection::where('store_id', $store_id)->with('schoolClass')->orderByDesc('id')->get();
        $periods  = $this->periodsFor($store_id);
        $subjects = Subject::where('store_id', $store_id)->where('status', 1)->orderBy('name')->get();
        $teachers = VendorEmployee::where('store_id', $store_id)->orderBy('f_name')->get();

        $grid = [];      // [day][period_id] => entry
        $autoMap = [];   // "classId-subjectId" => teacher_emp_id  (for JS auto-fill)
        if ($classId && $sectionId) {
            $entries = TimetableEntry::where('store_id', $store_id)
                ->where('class_section_id', $sectionId)->get();
            foreach ($entries as $e) {
                $grid[$e->day_of_week][$e->timetable_period_id] = $e;
            }
            foreach (ClassSubjectTeacher::where('store_id', $store_id)->where('school_class_id', $classId)->get() as $m) {
                $autoMap[$m->subject_id] = $m->teacher_emp_id;
            }
        }

        return view('school::vendor.timetable.index', [
            'classes'  => $classes,
            'sections' => $sections,
            'periods'  => $periods,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'days'     => TimetableEntry::DAYS,
            'classId'  => $classId,
            'sectionId' => $sectionId,
            'grid'     => $grid,
            'autoMap'  => $autoMap,
        ]);
    }

    public function save(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $request->validate([
            'class_id'   => 'required|integer',
            'section_id' => 'required|integer',
        ]);

        TimetableEntry::where('store_id', $store_id)->where('class_section_id', $request->section_id)->delete();

        $rows = [];
        foreach ((array) $request->input('entry', []) as $day => $periods) {
            foreach ((array) $periods as $periodId => $cell) {
                $subjectId = $cell['subject'] ?? null;
                $teacherId = $cell['teacher'] ?? null;
                if (!$subjectId && !$teacherId) continue;
                $rows[] = [
                    'store_id'            => $store_id,
                    'school_class_id'     => $request->class_id,
                    'class_section_id'    => $request->section_id,
                    'day_of_week'         => (int) $day,
                    'timetable_period_id' => (int) $periodId,
                    'subject_id'          => $subjectId ?: null,
                    'teacher_emp_id'      => $teacherId ?: null,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];
            }
        }
        if ($rows) TimetableEntry::insert($rows);

        Toastr::success('Timetable saved.');
        return redirect()->route('vendor.school.timetable.index', ['class_id' => $request->class_id, 'section_id' => $request->section_id]);
    }

    /* ===== Periods setup ===== */
    public function periods()
    {
        $this->ensureSchema();
        $periods = $this->periodsFor(Helpers::get_store_id());
        return view('school::vendor.timetable.periods', compact('periods'));
    }

    public function period_save(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'name'       => 'required|string|max:100',
            'period_no'  => 'nullable|integer|min:0',
            'start_time' => 'nullable',
            'end_time'   => 'nullable',
        ]);
        $store_id = Helpers::get_store_id();
        TimetablePeriod::updateOrCreate(
            ['id' => $request->id, 'store_id' => $store_id],
            [
                'store_id'   => $store_id,
                'period_no'  => (int) $request->period_no,
                'name'       => $request->name,
                'start_time' => $request->start_time ?: null,
                'end_time'   => $request->end_time ?: null,
                'is_break'   => $request->boolean('is_break'),
                'sort_order' => (int) ($request->period_no ?: 0),
                'status'     => 1,
            ]
        );
        Toastr::success('Period saved.');
        return back();
    }

    public function period_delete($id)
    {
        TimetablePeriod::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Period removed.');
        return back();
    }

    /* ===== Class timetable view + PDF ===== */
    public function show(Request $request)
    {
        $this->ensureSchema();
        [$data, $section] = $this->classTimetableData($request);
        if (!$section) {
            Toastr::error('Select a class & section first.');
            return redirect()->route('vendor.school.timetable.index');
        }
        return view('school::vendor.timetable.show', array_merge($data, ['pdf' => false]));
    }

    public function pdf(Request $request)
    {
        $this->ensureSchema();
        [$data, $section] = $this->classTimetableData($request);
        if (!$section) {
            Toastr::error('Select a class & section first.');
            return redirect()->route('vendor.school.timetable.index');
        }
        $html = View::make('school::vendor.timetable.show', array_merge($data, ['pdf' => true]))->render();
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10, 'tempDir' => storage_path('tmp')]);
        $mpdf->WriteHTML($html);
        $mpdf->Output('timetable_' . $section->id . '.pdf', 'I');
    }

    /* ===== Teacher timetable ===== */
    public function teacher(Request $request)
    {
        $this->ensureSchema();
        $store_id  = Helpers::get_store_id();
        $teacherId = $request->query('teacher_id');

        $teachers = VendorEmployee::where('store_id', $store_id)->orderBy('f_name')->get();
        $periods  = $this->periodsFor($store_id);

        $grid = [];
        if ($teacherId) {
            $entries = TimetableEntry::where('store_id', $store_id)->where('teacher_emp_id', $teacherId)
                ->with(['subject', 'schoolClass', 'section'])->get();
            foreach ($entries as $e) {
                $grid[$e->day_of_week][$e->timetable_period_id] = $e;
            }
        }

        return view('school::vendor.timetable.teacher', [
            'teachers'  => $teachers,
            'periods'   => $periods,
            'days'      => TimetableEntry::DAYS,
            'teacherId' => $teacherId,
            'grid'      => $grid,
        ]);
    }

    /* ===== Substitutions ===== */
    public function substitutions(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $date     = $request->query('date', now()->toDateString());
        $dow      = \Carbon\Carbon::parse($date)->dayOfWeekIso; // 1=Mon..7=Sun

        $entries = collect();
        if (array_key_exists($dow, TimetableEntry::DAYS)) {
            $entries = TimetableEntry::where('store_id', $store_id)->where('day_of_week', $dow)
                ->whereNotNull('teacher_emp_id')
                ->with(['subject', 'teacher', 'period', 'schoolClass', 'section'])
                ->get()
                ->sortBy(fn($e) => [optional($e->period)->sort_order, optional($e->schoolClass)->numeric_order]);
        }

        $subs = TimetableSubstitution::where('store_id', $store_id)->whereDate('sub_date', $date)
            ->get()->keyBy('timetable_entry_id');
        $teachers = VendorEmployee::where('store_id', $store_id)->orderBy('f_name')->get();

        return view('school::vendor.timetable.substitutions', [
            'date'     => $date,
            'dow'      => $dow,
            'days'     => TimetableEntry::DAYS,
            'entries'  => $entries,
            'subs'     => $subs,
            'teachers' => $teachers,
        ]);
    }

    public function substitution_save(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'sub_date'           => 'required|date',
            'timetable_entry_id' => 'required|integer',
            'substitute_teacher_emp_id' => 'required|integer',
        ]);
        $store_id = Helpers::get_store_id();
        TimetableSubstitution::updateOrCreate(
            ['store_id' => $store_id, 'sub_date' => $request->sub_date, 'timetable_entry_id' => $request->timetable_entry_id],
            ['substitute_teacher_emp_id' => $request->substitute_teacher_emp_id, 'reason' => $request->reason]
        );
        Toastr::success('Substitution saved.');
        return back();
    }

    public function substitution_delete($id)
    {
        TimetableSubstitution::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Substitution removed.');
        return back();
    }

    /* ===== helpers ===== */
    private function periodsFor($store_id)
    {
        return TimetablePeriod::where('store_id', $store_id)->where('status', 1)
            ->orderBy('sort_order')->orderBy('period_no')->orderBy('id')->get();
    }

    private function classTimetableData(Request $request): array
    {
        $store_id  = Helpers::get_store_id();
        $sectionId = $request->query('section_id');
        $section   = $sectionId ? ClassSection::where('store_id', $store_id)->with('schoolClass')->find($sectionId) : null;
        if (!$section) return [[], null];

        $periods = $this->periodsFor($store_id);
        $grid = [];
        $entries = TimetableEntry::where('store_id', $store_id)->where('class_section_id', $sectionId)
            ->with(['subject', 'teacher'])->get();
        foreach ($entries as $e) {
            $grid[$e->day_of_week][$e->timetable_period_id] = $e;
        }
        $store = Store::withoutGlobalScopes()->find($store_id);

        return [[
            'section' => $section,
            'periods' => $periods,
            'days'    => TimetableEntry::DAYS,
            'grid'    => $grid,
            'store'   => $store,
            'branch'  => school_active_branch(),
        ], $section];
    }

    public static function teacherName($emp): string
    {
        if (!$emp) return '—';
        return trim(($emp->f_name ?? '') . ' ' . ($emp->l_name ?? '')) ?: 'Staff';
    }
}
