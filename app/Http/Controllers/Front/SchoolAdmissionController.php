<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\AdmissionEnquiry;
use App\Models\Branch;
use App\Models\SchoolClass;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchoolAdmissionController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('admission_enquiries')) {
            DB::statement("CREATE TABLE IF NOT EXISTS admission_enquiries (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                enquiry_no VARCHAR(50) NULL, enquiry_date DATE NULL,
                student_name VARCHAR(150) NOT NULL, dob DATE NULL, gender VARCHAR(20) NULL,
                seeking_class_id BIGINT UNSIGNED NULL,
                guardian_name VARCHAR(150) NULL, guardian_phone VARCHAR(30) NULL,
                phone VARCHAR(30) NULL, email VARCHAR(150) NULL,
                previous_school VARCHAR(190) NULL, source VARCHAR(50) NULL,
                status VARCHAR(20) DEFAULT 'new', follow_up_date DATE NULL,
                remarks TEXT NULL, converted_student_id BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id), KEY idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function form(Request $request, $city, $slug)
    {
        $store = Store::where('slug', $slug)->first();
        if (!$store) return redirect()->route('home');
        if (strtolower($store->business_type ?? '') !== 'school') {
            return redirect()->route('store.details', [$city, $slug]);
        }

        $classes = Schema::hasTable('school_classes')
            ? SchoolClass::where('store_id', $store->id)->orderBy('numeric_order')->orderBy('id')->get() : collect();
        $branches = Schema::hasTable('branches')
            ? Branch::where('store_id', $store->id)->orderBy('name')->get() : collect();

        return view('front-views.school.admission', compact('store', 'classes', 'branches', 'city', 'slug'));
    }

    public function submit(Request $request, $city, $slug)
    {
        $store = Store::where('slug', $slug)->firstOrFail();
        $this->ensureSchema();

        $request->validate([
            'student_name'     => 'required|string|max:150',
            'guardian_name'    => 'required|string|max:150',
            'guardian_phone'   => 'required|string|max:20',
            'dob'              => 'nullable|date',
            'gender'           => 'nullable|in:male,female,other',
            'seeking_class_id' => 'nullable|integer',
            'email'            => 'nullable|email|max:150',
            'previous_school'  => 'nullable|string|max:190',
            'branch_id'        => 'nullable|integer',
            'remarks'          => 'nullable|string|max:1000',
        ]);

        // Validate referenced class / branch belong to this store.
        $classId = $request->seeking_class_id
            ? SchoolClass::where('store_id', $store->id)->where('id', $request->seeking_class_id)->value('id') : null;
        $branchId = $request->branch_id
            ? Branch::where('store_id', $store->id)->where('id', $request->branch_id)->value('id') : null;

        // Store-wide enquiry number.
        $last = AdmissionEnquiry::withoutGlobalScope('schoolBranch')->where('store_id', $store->id)->orderByDesc('id')->value('enquiry_no');
        $n = ($last && preg_match('/(\d+)\s*$/', (string) $last, $m)) ? (int) $m[1] + 1 : 1;

        AdmissionEnquiry::create([
            'store_id'         => $store->id,
            'branch_id'        => $branchId,
            'enquiry_no'       => 'ENQ-' . $n,
            'enquiry_date'     => now()->toDateString(),
            'student_name'     => $request->student_name,
            'dob'              => $request->dob,
            'gender'           => $request->gender,
            'seeking_class_id' => $classId,
            'guardian_name'    => $request->guardian_name,
            'guardian_phone'   => $request->guardian_phone,
            'phone'            => $request->guardian_phone,
            'email'            => $request->email,
            'previous_school'  => $request->previous_school,
            'source'           => 'Website',
            'status'           => 'new',
            'remarks'          => $request->remarks,
        ]);

        return redirect()->route('front.school.admission', [$city, $slug])
            ->with('success', 'Thank you! Your admission enquiry has been submitted. The school will contact you soon.');
    }
}
