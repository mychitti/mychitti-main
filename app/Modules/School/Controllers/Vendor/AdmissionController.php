<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AdmissionEnquiry;
use App\Models\SchoolClass;
use App\Models\Student;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdmissionController extends Controller
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

    public function index(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $status   = $request->query('status');
        $search   = trim($request->query('search', ''));

        $enquiries = AdmissionEnquiry::where('store_id', $store_id)
            ->with('seekingClass')
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($search, fn($q) => $q->where(fn($w) => $w
                ->where('student_name', 'like', "%$search%")
                ->orWhere('enquiry_no', 'like', "%$search%")
                ->orWhere('guardian_phone', 'like', "%$search%")
                ->orWhere('phone', 'like', "%$search%")))
            ->orderByDesc('id')->paginate(config('default_pagination'))->withQueryString();

        $counts = AdmissionEnquiry::where('store_id', $store_id)
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('school::vendor.admissions.index', [
            'enquiries' => $enquiries,
            'statuses'  => AdmissionEnquiry::STATUSES,
            'status'    => $status,
            'search'    => $search,
            'counts'    => $counts,
            'total'     => AdmissionEnquiry::where('store_id', $store_id)->count(),
        ]);
    }

    public function create()
    {
        $this->ensureSchema();
        $enquiry = null;
        $classes = SchoolClass::where('store_id', Helpers::get_store_id())->orderBy('numeric_order')->orderBy('id')->get();
        return view('school::vendor.admissions.form', [
            'enquiry'  => $enquiry,
            'classes'  => $classes,
            'statuses' => AdmissionEnquiry::STATUSES,
            'sources'  => AdmissionEnquiry::SOURCES,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $data = $this->validateEnquiry($request);

        $data['store_id']   = $store_id;
        $data['enquiry_no'] = 'ENQ-' . school_next_serial(AdmissionEnquiry::class, $store_id, 'enquiry_no');
        $data['enquiry_date'] = $request->enquiry_date ?: now()->toDateString();
        $data['status']     = $request->status ?: 'new';

        AdmissionEnquiry::create($data);
        Toastr::success('Enquiry recorded.');
        return redirect()->route('vendor.school.admissions.index');
    }

    public function show($id)
    {
        $this->ensureSchema();
        $enquiry = AdmissionEnquiry::where('store_id', Helpers::get_store_id())
            ->with(['seekingClass', 'student'])->findOrFail($id);
        return view('school::vendor.admissions.show', [
            'enquiry'  => $enquiry,
            'statuses' => AdmissionEnquiry::STATUSES,
        ]);
    }

    public function edit($id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $enquiry  = AdmissionEnquiry::where('store_id', $store_id)->findOrFail($id);
        $classes  = SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->orderBy('id')->get();
        return view('school::vendor.admissions.form', [
            'enquiry'  => $enquiry,
            'classes'  => $classes,
            'statuses' => AdmissionEnquiry::STATUSES,
            'sources'  => AdmissionEnquiry::SOURCES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->ensureSchema();
        $enquiry = AdmissionEnquiry::where('store_id', Helpers::get_store_id())->findOrFail($id);
        $enquiry->update($this->validateEnquiry($request) + ['status' => $request->status ?: $enquiry->status]);
        Toastr::success('Enquiry updated.');
        return redirect()->route('vendor.school.admissions.show', $enquiry->id);
    }

    public function update_status(Request $request, $id)
    {
        $this->ensureSchema();
        $request->validate(['status' => 'required|in:' . implode(',', array_keys(AdmissionEnquiry::STATUSES))]);
        $enquiry = AdmissionEnquiry::where('store_id', Helpers::get_store_id())->findOrFail($id);
        $enquiry->update(['status' => $request->status, 'follow_up_date' => $request->follow_up_date ?: $enquiry->follow_up_date]);
        Toastr::success('Status updated.');
        return back();
    }

    public function convert($id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $enquiry  = AdmissionEnquiry::where('store_id', $store_id)->findOrFail($id);

        if ($enquiry->converted_student_id && Student::where('store_id', $store_id)->where('id', $enquiry->converted_student_id)->exists()) {
            Toastr::info('This enquiry is already admitted.');
            return redirect()->route('vendor.school.students.edit', $enquiry->converted_student_id);
        }

        $student = Student::create([
            'store_id'       => $store_id,
            'admission_no'   => Student::generateAdmissionNo($store_id),
            'admission_date' => now()->toDateString(),
            'first_name'     => $enquiry->student_name,
            'name'           => $enquiry->student_name,
            'dob'            => $enquiry->dob,
            'gender'         => $enquiry->gender,
            'guardian_name'  => $enquiry->guardian_name,
            'guardian_phone' => $enquiry->guardian_phone,
            'phone'          => $enquiry->phone,
            'email'          => $enquiry->email,
            'status'         => 1,
        ]);

        $enquiry->update(['status' => 'admitted', 'converted_student_id' => $student->id]);

        Toastr::success('Enquiry converted — complete the admission below.');
        return redirect()->route('vendor.school.students.edit', $student->id);
    }

    public function delete($id)
    {
        AdmissionEnquiry::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Enquiry removed.');
        return redirect()->route('vendor.school.admissions.index');
    }

    private function validateEnquiry(Request $request): array
    {
        return $request->validate([
            'student_name'    => 'required|string|max:150',
            'dob'             => 'nullable|date',
            'gender'          => 'nullable|in:male,female,other',
            'seeking_class_id' => 'nullable|integer',
            'guardian_name'   => 'nullable|string|max:150',
            'guardian_phone'  => 'nullable|string|max:30',
            'phone'           => 'nullable|string|max:30',
            'email'           => 'nullable|email|max:150',
            'previous_school' => 'nullable|string|max:190',
            'source'          => 'nullable|string|max:50',
            'follow_up_date'  => 'nullable|date',
            'remarks'         => 'nullable|string|max:2000',
        ]);
    }
}
