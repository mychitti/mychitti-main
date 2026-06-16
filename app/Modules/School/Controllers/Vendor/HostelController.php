<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\SchoolHostelAllocation;
use App\Models\SchoolHostelBlock;
use App\Models\SchoolHostelRoom;
use App\Models\StudentEnrollment;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HostelController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('school_hostel_blocks')) {
            DB::statement("CREATE TABLE IF NOT EXISTS school_hostel_blocks (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                name VARCHAR(191) NOT NULL, type VARCHAR(20) DEFAULT 'boys',
                warden_name VARCHAR(191) NULL, warden_phone VARCHAR(50) NULL,
                status TINYINT NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store_branch (store_id, branch_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('school_hostel_rooms')) {
            DB::statement("CREATE TABLE IF NOT EXISTS school_hostel_rooms (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                school_hostel_block_id BIGINT UNSIGNED NOT NULL,
                room_no VARCHAR(50) NOT NULL, floor VARCHAR(50) NULL,
                capacity INT NOT NULL DEFAULT 1, rent DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                status TINYINT NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store_block (store_id, branch_id, school_hostel_block_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('school_hostel_allocations')) {
            DB::statement("CREATE TABLE IF NOT EXISTS school_hostel_allocations (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                student_id BIGINT UNSIGNED NOT NULL,
                school_hostel_block_id BIGINT UNSIGNED NOT NULL,
                school_hostel_room_id BIGINT UNSIGNED NOT NULL,
                allocated_on DATE NULL, monthly_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), UNIQUE KEY uniq_student_hostel (store_id, student_id),
                KEY idx_store_room (store_id, branch_id, school_hostel_room_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function index(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $blocks = SchoolHostelBlock::where('store_id', $store_id)->withCount('allocations')->orderBy('name')->get();
        $rooms  = SchoolHostelRoom::where('store_id', $store_id)->with('block')->withCount('allocations')->orderBy('room_no')->get();
        $allocations = SchoolHostelAllocation::where('store_id', $store_id)
            ->with(['student.currentEnrollment.schoolClass', 'student.currentEnrollment.section', 'block', 'room'])
            ->orderByDesc('id')->get();

        $students = StudentEnrollment::where('store_id', $store_id)->where('status', 1)
            ->with('student')->get()
            ->filter(fn($e) => $e->student)->map(fn($e) => $e->student)
            ->unique('id')->sortBy('name')->values();

        return view('school::vendor.hostel.index', compact('blocks', 'rooms', 'allocations', 'students'));
    }

    /* ===== Blocks ===== */
    public function blockStore(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'name'         => 'required|string|max:191',
            'type'         => 'required|in:boys,girls,mixed',
            'warden_name'  => 'nullable|string|max:191',
            'warden_phone' => 'nullable|string|max:50',
        ]);
        SchoolHostelBlock::updateOrCreate(
            ['id' => $request->id, 'store_id' => Helpers::get_store_id()],
            [
                'store_id'     => Helpers::get_store_id(),
                'name'         => $request->name,
                'type'         => $request->type,
                'warden_name'  => $request->warden_name ?: null,
                'warden_phone' => $request->warden_phone ?: null,
                'status'       => 1,
            ]
        );
        Toastr::success('Hostel block saved.');
        return back();
    }

    public function blockDelete($id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $block = SchoolHostelBlock::where('store_id', $store_id)->findOrFail($id);
        SchoolHostelRoom::where('school_hostel_block_id', $block->id)->delete();
        SchoolHostelAllocation::where('school_hostel_block_id', $block->id)->delete();
        $block->delete();
        Toastr::success('Hostel block deleted.');
        return back();
    }

    /* ===== Rooms ===== */
    public function roomStore(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'school_hostel_block_id' => 'required|integer',
            'room_no'  => 'required|string|max:50',
            'floor'    => 'nullable|string|max:50',
            'capacity' => 'required|integer|min:1',
            'rent'     => 'required|numeric|min:0',
        ]);
        SchoolHostelRoom::updateOrCreate(
            ['id' => $request->id, 'store_id' => Helpers::get_store_id()],
            [
                'store_id'               => Helpers::get_store_id(),
                'school_hostel_block_id' => $request->school_hostel_block_id,
                'room_no'                => $request->room_no,
                'floor'                  => $request->floor ?: null,
                'capacity'               => (int) $request->capacity,
                'rent'                   => (float) $request->rent,
                'status'                 => 1,
            ]
        );
        Toastr::success('Room saved.');
        return back();
    }

    public function roomDelete($id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $room = SchoolHostelRoom::where('store_id', $store_id)->findOrFail($id);
        SchoolHostelAllocation::where('school_hostel_room_id', $room->id)->delete();
        $room->delete();
        Toastr::success('Room removed.');
        return back();
    }

    /* ===== Allocations ===== */
    public function allocationStore(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $request->validate([
            'student_id'             => 'required|integer',
            'school_hostel_block_id' => 'required|integer',
            'school_hostel_room_id'  => 'required|integer',
            'allocated_on'           => 'nullable|date',
            'monthly_fee'            => 'nullable|numeric|min:0',
        ]);

        $room = SchoolHostelRoom::where('store_id', $store_id)->findOrFail($request->school_hostel_room_id);

        // Capacity check (exclude this student's existing allocation).
        $occupied = SchoolHostelAllocation::where('store_id', $store_id)
            ->where('school_hostel_room_id', $room->id)
            ->where('student_id', '!=', (int) $request->student_id)->count();
        if ($occupied >= $room->capacity) {
            Toastr::error('That room is already at full capacity.');
            return back();
        }

        SchoolHostelAllocation::updateOrCreate(
            ['store_id' => $store_id, 'student_id' => (int) $request->student_id],
            [
                'school_hostel_block_id' => (int) $request->school_hostel_block_id,
                'school_hostel_room_id'  => (int) $request->school_hostel_room_id,
                'allocated_on'           => $request->allocated_on ?: now()->toDateString(),
                'monthly_fee'            => (float) ($request->monthly_fee ?: $room->rent),
            ]
        );
        Toastr::success('Student allocated to hostel.');
        return back();
    }

    public function allocationDelete($id)
    {
        $this->ensureSchema();
        SchoolHostelAllocation::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Hostel allocation removed.');
        return back();
    }
}
