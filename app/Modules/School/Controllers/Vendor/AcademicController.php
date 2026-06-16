<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\ClassSubjectTeacher;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('academic_sessions')) {
            DB::statement("CREATE TABLE IF NOT EXISTS academic_sessions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                name VARCHAR(100) NOT NULL, start_date DATE NULL, end_date DATE NULL,
                is_current TINYINT(1) DEFAULT 0, status TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('school_classes')) {
            DB::statement("CREATE TABLE IF NOT EXISTS school_classes (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                name VARCHAR(100) NOT NULL, numeric_order INT DEFAULT 0, status TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('class_sections')) {
            DB::statement("CREATE TABLE IF NOT EXISTS class_sections (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                school_class_id BIGINT UNSIGNED NOT NULL, name VARCHAR(50) NOT NULL,
                capacity INT NULL, class_teacher_emp_id BIGINT UNSIGNED NULL, status TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id), KEY idx_class (school_class_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('subjects')) {
            DB::statement("CREATE TABLE IF NOT EXISTS subjects (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                code VARCHAR(50) NULL, name VARCHAR(150) NOT NULL, status TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('class_subject_teachers')) {
            DB::statement("CREATE TABLE IF NOT EXISTS class_subject_teachers (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                school_class_id BIGINT UNSIGNED NOT NULL, class_section_id BIGINT UNSIGNED NULL,
                subject_id BIGINT UNSIGNED NOT NULL, teacher_emp_id BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function index(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $sessions = AcademicSession::where('store_id', $store_id)->orderByDesc('is_current')->orderByDesc('id')->get();
        $classes  = SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->orderBy('id')->get();
        $sections = ClassSection::where('store_id', $store_id)->with(['schoolClass', 'classTeacher'])->orderByDesc('id')->get();
        $subjects = Subject::where('store_id', $store_id)->orderBy('name')->get();
        $mappings = ClassSubjectTeacher::where('store_id', $store_id)->with(['schoolClass', 'section', 'subject', 'teacher'])->orderByDesc('id')->get();
        $teachers = VendorEmployee::where('store_id', $store_id)->orderBy('f_name')->get();

        return view('school::vendor.academic.index', compact('sessions', 'classes', 'sections', 'subjects', 'mappings', 'teachers'));
    }

    // ── Sessions ─────────────────────────────────────────────
    public function session_store(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'name'       => 'required|string|max:100',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);
        $store_id = Helpers::get_store_id();

        if ($request->boolean('is_current')) {
            AcademicSession::where('store_id', $store_id)->update(['is_current' => 0]);
        }

        AcademicSession::updateOrCreate(
            ['id' => $request->id, 'store_id' => $store_id],
            [
                'store_id'   => $store_id,
                'name'       => $request->name,
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date,
                'is_current' => $request->boolean('is_current'),
                'status'     => 1,
            ]
        );
        Toastr::success('Academic session saved.');
        return back();
    }

    public function session_delete($id)
    {
        AcademicSession::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Session removed.');
        return back();
    }

    // ── Classes ──────────────────────────────────────────────
    public function class_store(Request $request)
    {
        $this->ensureSchema();
        $request->validate(['name' => 'required|string|max:100', 'numeric_order' => 'nullable|integer|min:0']);
        $store_id = Helpers::get_store_id();
        SchoolClass::updateOrCreate(
            ['id' => $request->id, 'store_id' => $store_id],
            ['store_id' => $store_id, 'name' => $request->name, 'numeric_order' => (int) $request->numeric_order, 'status' => 1]
        );
        Toastr::success('Class saved.');
        return back();
    }

    public function class_delete($id)
    {
        SchoolClass::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Class removed.');
        return back();
    }

    // ── Sections ─────────────────────────────────────────────
    public function section_store(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'school_class_id' => 'required|integer',
            'name'            => 'required|string|max:50',
            'capacity'        => 'nullable|integer|min:0',
        ]);
        $store_id = Helpers::get_store_id();
        ClassSection::updateOrCreate(
            ['id' => $request->id, 'store_id' => $store_id],
            [
                'store_id'             => $store_id,
                'school_class_id'      => $request->school_class_id,
                'name'                 => $request->name,
                'capacity'             => $request->capacity,
                'class_teacher_emp_id' => $request->class_teacher_emp_id ?: null,
                'status'               => 1,
            ]
        );
        Toastr::success('Section saved.');
        return back();
    }

    public function section_delete($id)
    {
        ClassSection::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Section removed.');
        return back();
    }

    // ── Subjects ─────────────────────────────────────────────
    public function subject_store(Request $request)
    {
        $this->ensureSchema();
        $request->validate(['name' => 'required|string|max:150', 'code' => 'nullable|string|max:50']);
        $store_id = Helpers::get_store_id();
        Subject::updateOrCreate(
            ['id' => $request->id, 'store_id' => $store_id],
            ['store_id' => $store_id, 'name' => $request->name, 'code' => $request->code, 'status' => 1]
        );
        Toastr::success('Subject saved.');
        return back();
    }

    public function subject_delete($id)
    {
        Subject::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Subject removed.');
        return back();
    }

    // ── Subject ↔ Teacher mapping ────────────────────────────
    public function mapping_store(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'school_class_id' => 'required|integer',
            'subject_id'      => 'required|integer',
            'teacher_emp_id'  => 'required|integer',
        ]);
        $store_id = Helpers::get_store_id();
        ClassSubjectTeacher::updateOrCreate(
            [
                'store_id'         => $store_id,
                'school_class_id'  => $request->school_class_id,
                'class_section_id' => $request->class_section_id ?: null,
                'subject_id'       => $request->subject_id,
            ],
            ['teacher_emp_id' => $request->teacher_emp_id]
        );
        Toastr::success('Subject-teacher mapping saved.');
        return back();
    }

    public function mapping_delete($id)
    {
        ClassSubjectTeacher::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Mapping removed.');
        return back();
    }
}
