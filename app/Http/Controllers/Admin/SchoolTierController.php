<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolStudentTier;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchoolTierController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('school_student_tiers')) {
            DB::statement("CREATE TABLE IF NOT EXISTS school_student_tiers (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                tier_name VARCHAR(100) NOT NULL,
                min_students INT NOT NULL DEFAULT 0,
                max_students INT NULL,
                max_branches INT NULL,
                price_monthly DECIMAL(12,2) NOT NULL DEFAULT 0,
                price_yearly DECIMAL(12,2) NOT NULL DEFAULT 0,
                is_custom TINYINT(1) NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Seed sensible defaults the first time.
            SchoolStudentTier::insert([
                ['tier_name' => 'Starter',      'min_students' => 0,    'max_students' => 200,  'max_branches' => 1,    'price_monthly' => 0, 'price_yearly' => 0, 'is_custom' => 0, 'is_active' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['tier_name' => 'Growth',       'min_students' => 201,  'max_students' => 1000, 'max_branches' => 3,    'price_monthly' => 0, 'price_yearly' => 0, 'is_custom' => 0, 'is_active' => 1, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
                ['tier_name' => 'Professional', 'min_students' => 1001, 'max_students' => 5000, 'max_branches' => 10,   'price_monthly' => 0, 'price_yearly' => 0, 'is_custom' => 0, 'is_active' => 1, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
                ['tier_name' => 'Enterprise',   'min_students' => 5001, 'max_students' => null, 'max_branches' => null, 'price_monthly' => 0, 'price_yearly' => 0, 'is_custom' => 0, 'is_active' => 1, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // Custom pricing was removed — ensure no tier stays flagged custom.
        if (Schema::hasColumn('school_student_tiers', 'is_custom')) {
            SchoolStudentTier::where('is_custom', 1)->update(['is_custom' => 0]);
        }

        // Columns the purchase flow writes to (mirrors bed_tier_id).
        if (Schema::hasTable('vendor_subscriptions') && !Schema::hasColumn('vendor_subscriptions', 'student_tier_id')) {
            DB::statement("ALTER TABLE `vendor_subscriptions` ADD COLUMN `student_tier_id` BIGINT UNSIGNED NULL");
        }
        if (Schema::hasTable('temp_module_purchases') && !Schema::hasColumn('temp_module_purchases', 'student_tier_id')) {
            DB::statement("ALTER TABLE `temp_module_purchases` ADD COLUMN `student_tier_id` BIGINT UNSIGNED NULL");
        }
    }

    public function index()
    {
        $this->ensureSchema();
        $tiers = SchoolStudentTier::orderBy('sort_order')->orderBy('min_students')->get();
        return view('admin-views.school-tiers.index', compact('tiers'));
    }

    public function store(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'tier_name'     => 'required|string|max:100',
            'min_students'  => 'nullable|integer|min:0',
            'max_students'  => 'nullable|integer|min:0',
            'max_branches'  => 'nullable|integer|min:1',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly'  => 'required|numeric|min:0',
            'sort_order'    => 'nullable|integer|min:0',
        ]);

        SchoolStudentTier::updateOrCreate(
            ['id' => $request->id],
            [
                'tier_name'     => $request->tier_name,
                'min_students'  => (int) $request->min_students,
                'max_students'  => $request->filled('max_students') ? (int) $request->max_students : null,
                'max_branches'  => $request->filled('max_branches') ? (int) $request->max_branches : null,
                'price_monthly' => (float) $request->price_monthly,
                'price_yearly'  => (float) $request->price_yearly,
                'is_custom'     => $request->boolean('is_custom'),
                'is_active'     => $request->boolean('is_active'),
                'sort_order'    => (int) ($request->sort_order ?? 0),
            ]
        );
        Toastr::success('Plan tier saved.');
        return back();
    }

    public function toggle($id)
    {
        $this->ensureSchema();
        $tier = SchoolStudentTier::findOrFail($id);
        $tier->update(['is_active' => !$tier->is_active]);
        Toastr::success('Tier status updated.');
        return back();
    }

    public function delete($id)
    {
        SchoolStudentTier::where('id', $id)->delete();
        Toastr::success('Plan tier removed.');
        return back();
    }
}
