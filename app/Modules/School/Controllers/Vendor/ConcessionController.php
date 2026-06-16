<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\FeeConcession;
use App\Models\FeeInvoice;
use App\Models\Student;
use App\Models\StudentConcession;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConcessionController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('fee_concessions')) {
            DB::statement("CREATE TABLE IF NOT EXISTS fee_concessions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                name VARCHAR(150) NOT NULL, type VARCHAR(10) NOT NULL DEFAULT 'percent',
                value DECIMAL(12,2) NOT NULL DEFAULT 0, max_amount DECIMAL(12,2) NULL,
                description VARCHAR(255) NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('student_concessions')) {
            DB::statement("CREATE TABLE IF NOT EXISTS student_concessions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                student_id BIGINT UNSIGNED NOT NULL, fee_concession_id BIGINT UNSIGNED NOT NULL,
                academic_session_id BIGINT UNSIGNED NULL, note VARCHAR(255) NULL, status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id), KEY idx_student (student_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (Schema::hasTable('fee_invoices') && !Schema::hasColumn('fee_invoices', 'concession_note')) {
            DB::statement("ALTER TABLE `fee_invoices` ADD COLUMN `concession_note` VARCHAR(255) NULL");
        }
    }

    public function index(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $sessions  = AcademicSession::where('store_id', $store_id)->orderByDesc('is_current')->orderByDesc('id')->get();
        $sessionId = $request->query('session') ?: optional($sessions->firstWhere('is_current', true))->id;

        $schemes = FeeConcession::where('store_id', $store_id)->orderBy('sort_order')->orderBy('name')->get();

        $assignments = StudentConcession::where('store_id', $store_id)
            ->when($sessionId, fn($q) => $q->where(function ($w) use ($sessionId) {
                $w->whereNull('academic_session_id')->orWhere('academic_session_id', $sessionId);
            }))
            ->with(['scheme', 'student'])->orderByDesc('id')->get();

        $awarded = (float) FeeInvoice::where('store_id', $store_id)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->sum('concession');
        $beneficiaries = $assignments->where('status', true)->pluck('student_id')->unique()->count();

        return view('school::vendor.fees.concessions', compact(
            'schemes', 'assignments', 'sessions', 'sessionId', 'awarded', 'beneficiaries'
        ));
    }

    public function save(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'name'       => 'required|string|max:150',
            'type'       => 'required|in:percent,fixed',
            'value'      => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
        ]);
        if ($request->type === 'percent' && (float) $request->value > 100) {
            Toastr::error('Percentage cannot exceed 100.');
            return back();
        }
        $store_id = Helpers::get_store_id();
        FeeConcession::updateOrCreate(
            ['id' => $request->id, 'store_id' => $store_id],
            [
                'store_id'    => $store_id,
                'name'        => $request->name,
                'type'        => $request->type,
                'value'       => (float) $request->value,
                'max_amount'  => $request->filled('max_amount') ? (float) $request->max_amount : null,
                'description' => $request->description,
                'is_active'   => $request->boolean('is_active'),
                'sort_order'  => (int) ($request->sort_order ?? 0),
            ]
        );
        Toastr::success('Scholarship / concession saved.');
        return back();
    }

    public function delete($id)
    {
        FeeConcession::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Scheme removed.');
        return back();
    }

    public function assign(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'admission_no'      => 'required|string',
            'fee_concession_id' => 'required|integer',
        ]);
        $store_id = Helpers::get_store_id();

        $student = Student::where('store_id', $store_id)->where('admission_no', trim($request->admission_no))->first();
        if (!$student) {
            Toastr::error('No student found with admission no ' . $request->admission_no . '.');
            return back();
        }
        $scheme = FeeConcession::where('store_id', $store_id)->find($request->fee_concession_id);
        if (!$scheme) {
            Toastr::error('Invalid scheme.');
            return back();
        }

        StudentConcession::updateOrCreate(
            [
                'store_id'            => $store_id,
                'student_id'          => $student->id,
                'fee_concession_id'   => $scheme->id,
                'academic_session_id' => $request->session_id ?: null,
            ],
            ['note' => $request->note, 'status' => 1]
        );
        Toastr::success($scheme->name . ' assigned to ' . $student->name . '.');
        return back();
    }

    public function assignDelete($id)
    {
        StudentConcession::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Assignment removed.');
        return back();
    }
}
