<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentImportController extends Controller
{
    private array $columns = [
        'first_name', 'last_name', 'dob', 'gender', 'blood_group', 'category',
        'guardian_name', 'guardian_phone', 'phone', 'email',
        'address', 'city', 'state', 'pincode', 'admission_no',
        'class', 'section', 'roll_no', 'session',
    ];

    private function ensureSchema(): void
    {
        if (!Schema::hasTable('school_students')) {
            DB::statement("CREATE TABLE IF NOT EXISTS school_students (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                admission_no VARCHAR(50) NULL, admission_date DATE NULL,
                first_name VARCHAR(100) NULL, last_name VARCHAR(100) NULL,
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
    }

    public function index()
    {
        $this->ensureSchema();
        return view('school::vendor.students.import', ['columns' => $this->columns]);
    }

    public function template()
    {
        $headers = $this->columns;
        $example = [
            'Aarav', 'Sharma', '2015-04-12', 'male', 'O+', 'General',
            'Rajesh Sharma', '9876543210', '', 'rajesh@example.com',
            '12 MG Road', 'Tirupati', 'AP', '517507', '',
            'Class 1', 'A', '', '2025-26',
        ];
        return response()->streamDownload(function () use ($headers, $example) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $example);
            fclose($out);
        }, 'student_import_template.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request)
    {
        $this->ensureSchema();
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);
        $store_id = Helpers::get_store_id();

        $classes  = SchoolClass::where('store_id', $store_id)->get()->keyBy(fn($c) => strtolower(trim($c->name)));
        $sections = ClassSection::where('store_id', $store_id)->get();
        $sessions = AcademicSession::where('store_id', $store_id)->get()->keyBy(fn($s) => strtolower(trim($s->name)));
        $currentSession = AcademicSession::where('store_id', $store_id)->where('is_current', 1)->value('id');

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if (!$handle) return back()->with('import_result', ['created' => 0, 'errors' => ['Could not read the uploaded file.']]);

        $header = fgetcsv($handle);
        $header = array_map(fn($h) => strtolower(trim((string) $h)), $header ?: []);

        $created = 0;
        $errors  = [];
        $rowNum  = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count(array_filter($row, fn($v) => trim((string) $v) !== '')) === 0) continue;

            $data = array_combine($header, array_pad($row, count($header), null)) ?: [];
            $get = fn($k) => trim((string) ($data[$k] ?? ''));

            $firstName = $get('first_name');
            if ($firstName === '') { $errors[] = "Row $rowNum: first_name is required."; continue; }

            // Class / section / session lookups by name.
            $classModel = null;
            if ($get('class') !== '') {
                $classModel = $classes[strtolower($get('class'))] ?? null;
                if (!$classModel) { $errors[] = "Row $rowNum: class \"{$get('class')}\" not found."; continue; }
            }
            $sectionModel = null;
            if ($classModel && $get('section') !== '') {
                $sectionModel = $sections->first(fn($s) => (int) $s->school_class_id === (int) $classModel->id
                    && strtolower(trim($s->name)) === strtolower($get('section')));
                if (!$sectionModel) { $errors[] = "Row $rowNum: section \"{$get('section')}\" not found in {$classModel->name}."; continue; }
            }
            $sessionId = $get('session') !== '' ? (optional($sessions[strtolower($get('session'))] ?? null)->id ?: $currentSession) : $currentSession;

            // Admission number (provided unique, or auto).
            $admissionNo = $get('admission_no');
            if ($admissionNo !== '') {
                $dup = Student::withoutGlobalScope('schoolBranch')->where('store_id', $store_id)->where('admission_no', $admissionNo)->exists();
                if ($dup) { $errors[] = "Row $rowNum: admission no \"$admissionNo\" already exists."; continue; }
            } else {
                $admissionNo = Student::generateAdmissionNo($store_id);
            }

            $gender = strtolower($get('gender'));
            if (!in_array($gender, ['male', 'female', 'other'])) $gender = null;

            if ($get('dob') === '') { $errors[] = "Row $rowNum: dob is required (YYYY-MM-DD)."; continue; }
            try {
                $dob = \Carbon\Carbon::parse($get('dob'))->toDateString();
            } catch (\Throwable $e) {
                $errors[] = "Row $rowNum: invalid dob \"{$get('dob')}\" (use YYYY-MM-DD)."; continue;
            }

            try {
                $student = Student::create([
                    'store_id'       => $store_id,
                    'admission_no'   => $admissionNo,
                    'admission_date' => now()->toDateString(),
                    'first_name'     => $firstName,
                    'last_name'      => $get('last_name') ?: null,
                    'name'           => trim($firstName . ' ' . $get('last_name')),
                    'dob'            => $dob,
                    'gender'         => $gender,
                    'blood_group'    => $get('blood_group') ?: null,
                    'category'       => $get('category') ?: null,
                    'guardian_name'  => $get('guardian_name') ?: null,
                    'guardian_phone' => $get('guardian_phone') ?: null,
                    'phone'          => $get('phone') ?: null,
                    'email'          => $get('email') ?: null,
                    'address'        => $get('address') ?: null,
                    'city'           => $get('city') ?: null,
                    'state'          => $get('state') ?: null,
                    'pincode'        => $get('pincode') ?: null,
                    'status'         => 1,
                ]);

                if ($classModel) {
                    StudentEnrollment::create([
                        'store_id'            => $store_id,
                        'student_id'          => $student->id,
                        'academic_session_id' => $sessionId,
                        'school_class_id'     => $classModel->id,
                        'class_section_id'    => $sectionModel?->id,
                        'roll_no'             => $get('roll_no') ?: null,
                        'status'              => 1,
                    ]);
                }
                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Row $rowNum: " . $e->getMessage();
            }
        }
        fclose($handle);

        return back()->with('import_result', ['created' => $created, 'errors' => $errors]);
    }
}
