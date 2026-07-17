<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\PatientMedicalHistory;
use App\Models\RadiologyStudy;
use App\Models\RadiologyTest;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        return view('hmis::vendor.patient.index');
    }

    public function list(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $search   = $request->search;

        $patients = Patient::where('store_id', $store_id)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('patient_uid', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15);

        return view('hmis::vendor.patient.list', compact('patients', 'search'));
    }

    public function export(Request $request)
    {
        if (!auth('vendor')->check() && !hasPermission('patient', 'export')) abort(403);
        $store_id = Helpers::get_store_id();
        $search   = $request->search;

        $patients = Patient::where('store_id', $store_id)
            ->when($search, fn($q) => $q->where(fn($q2) => $q2
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('patient_uid', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->latest()->get();

        $headings = ['UID', 'Name', 'Phone', 'Email', 'Gender', 'DOB', 'Blood Group', 'Registered On'];
        $data = $patients->map(fn($p) => [
            $p->patient_uid,
            $p->name,
            $p->phone,
            $p->email,
            $p->gender,
            $p->dob,
            $p->blood_group,
            $p->created_at->format('d M Y'),
        ])->toArray();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($data, $headings),
            'patients_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function save(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:150',
            'phone'  => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female,other',
            'dob'    => 'nullable|date',
        ]);

        $store_id = Helpers::get_store_id();

        DB::beginTransaction();
        try {
            $patient              = new Patient();
            $patient->store_id   = $store_id;
            $patient->patient_uid = $this->generateUid($store_id);
            $patient->name        = $request->name;
            $patient->dob         = $request->dob;
            $patient->gender      = $request->gender;
            $patient->blood_group = $request->blood_group;
            $patient->phone       = $request->phone;
            $patient->email       = $request->email;
            $patient->address     = $request->address;
            $patient->city        = $request->city;
            $patient->state       = $request->state;
            $patient->pincode     = $request->pincode;
            $patient->emergency_contact_name     = $request->emergency_contact_name;
            $patient->emergency_contact_phone    = $request->emergency_contact_phone;
            $patient->emergency_contact_relation = $request->emergency_contact_relation;
            $patient->allergies  = $request->allergies;
            $patient->created_by = auth('vendor_employee')->id() ?? auth('vendor')->id();

            if ($request->hasFile('photo')) {
                $patient->photo = Helpers::upload('patient/', 'jpg', $request->file('photo'));
            }

            $patient->save();

            PatientMedicalHistory::create([
                'patient_id'          => $patient->id,
                'chronic_conditions'  => $request->chronic_conditions,
                'past_surgeries'      => $request->past_surgeries,
                'current_medications' => $request->medications,
                'family_history'      => $request->family_history,
                'smoking'             => $request->has('smoking') ? 1 : 0,
                'alcohol'             => $request->has('alcohol') ? 1 : 0,
                'notes'               => $request->medical_notes,
                'updated_by'          => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            ]);

            if ($request->hasFile('id_proof')) {
                $file     = $request->file('id_proof');
                $dir      = 'patient/documents/';
                $filename = Helpers::upload($dir, $file->getClientOriginalExtension(), $file);
                PatientDocument::create([
                    'patient_id'    => $patient->id,
                    'document_type' => 'id_proof',
                    'document_name' => $file->getClientOriginalName(),
                    'file_path'     => $dir . $filename,
                    'uploaded_by'   => auth('vendor_employee')->id() ?? auth('vendor')->id(),
                ]);
            }

            if ($request->hasFile('reports')) {
                $dir = 'patient/documents/';
                foreach ($request->file('reports') as $file) {
                    $filename = Helpers::upload($dir, $file->getClientOriginalExtension(), $file);
                    PatientDocument::create([
                        'patient_id'    => $patient->id,
                        'document_type' => 'report',
                        'document_name' => $file->getClientOriginalName(),
                        'file_path'     => $dir . $filename,
                        'uploaded_by'   => auth('vendor_employee')->id() ?? auth('vendor')->id(),
                    ]);
                }
            }

            $docFiles = $request->file('docs', []);
            if (!empty($docFiles)) {
                $dir = 'patient/documents/';
                foreach ($docFiles as $i => $docData) {
                    $file = $docData['file'] ?? null;
                    if ($file && $file->isValid()) {
                        $filename = Helpers::upload($dir, $file->getClientOriginalExtension(), $file);
                        $type     = $request->input("docs.{$i}.type", 'other');
                        $label    = $request->input("docs.{$i}.name");
                        PatientDocument::create([
                            'patient_id'    => $patient->id,
                            'document_type' => $type,
                            'document_name' => !empty($label) ? $label : $file->getClientOriginalName(),
                            'file_path'     => $dir . $filename,
                            'uploaded_by'   => auth('vendor_employee')->id() ?? auth('vendor')->id(),
                        ]);
                    }
                }
            }

            DB::commit();

            \App\Models\HospitalActivityLog::record(
                $store_id, 'patient', $patient->id, 'created',
                "Patient registered: {$patient->name} ({$patient->patient_uid})"
            );

            Toastr::success('Patient registered successfully');
            return redirect()->route('vendor.patient.list');
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to register patient: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function show($id)
    {
        if (!auth('vendor')->check() && !hasPermission('patient', 'view')) abort(403);
        $store_id = Helpers::get_store_id();
        $patient  = Patient::where('store_id', $store_id)
            ->with('medicalHistory', 'documents')
            ->findOrFail($id);

        $appointments = \App\Models\Appointment::where('store_id', $store_id)
            ->where('patient_id', $id)
            ->with('doctorProfile.employee')
            ->orderByDesc('appointment_date')
            ->get();

        $opdVisits = \App\Models\OpdVisit::where('store_id', $store_id)
            ->where('patient_id', $id)
            ->with('doctorProfile.employee')
            ->orderByDesc('visit_date')
            ->get();

        $ipdAdmissions = \App\Models\IpdAdmission::where('store_id', $store_id)
            ->where('patient_id', $id)
            ->with(['ward', 'bed', 'doctorProfile.employee'])
            ->orderByDesc('admission_date')
            ->get();

        $prescriptions = \App\Models\Prescription::where('store_id', $store_id)
            ->where('patient_id', $id)
            ->with('doctorProfile.employee')
            ->withCount('items')
            ->orderByDesc('created_at')
            ->get();

        $consents = \App\Models\PatientConsent::where('store_id', $store_id)
            ->where('patient_id', $id)
            ->with('admission')
            ->orderByDesc('signed_at')
            ->get();

        // Lab/Radiology build their tables lazily on first visit to those modules, so a hospital
        // that has never opened them has no lab_* tables at all. Guard every read — the patient
        // page must not depend on another module having been visited.
        $hasLab = Schema::hasTable('lab_tests') && Schema::hasTable('lab_orders');
        $hasRad = Schema::hasTable('radiology_tests') && Schema::hasTable('radiology_studies');

        // Catalogs the doctor picks from, grouped the way each department reads them.
        $labTests = $hasLab
            ? LabTest::where('store_id', $store_id)->where('is_active', 1)
                ->orderBy('department')->orderBy('name')->get()
                ->groupBy(fn ($t) => $t->department ?: 'Other')
            : collect();

        $radiologyTests = $hasRad
            ? RadiologyTest::where('store_id', $store_id)->where('is_active', 1)
                ->orderBy('modality')->orderBy('name')->get()
                ->groupBy(fn ($t) => $t->modality ?: 'Other')
            : collect();

        $doctors = DoctorProfile::where('store_id', $store_id)
            ->with('employee:id,f_name,l_name')->get();

        // What has already been raised for this patient, so the doctor sees it before re-ordering.
        $labOrders = $hasLab
            ? LabOrder::where('store_id', $store_id)->where('patient_id', $id)
                ->with(['items', 'doctorProfile.employee'])
                ->orderByDesc('created_at')->get()
            : collect();

        $radiologyStudies = $hasRad
            ? RadiologyStudy::where('store_id', $store_id)->where('patient_id', $id)
                ->with('doctorProfile.employee')
                ->orderByDesc('created_at')->get()
            : collect();

        return view('hmis::vendor.patient.view', compact(
            'patient', 'appointments', 'opdVisits', 'ipdAdmissions', 'prescriptions', 'consents',
            'labTests', 'radiologyTests', 'doctors', 'labOrders', 'radiologyStudies'
        ));
    }

    /**
     * Raise the tests a doctor selected on the patient page: one lab order carrying every
     * selected lab test, plus one radiology study per selected scan (radiology tracks each
     * scan as its own study). Both land in their department's worklist queue.
     */
    public function orderTests(Request $request, $id)
    {
        if (!auth('vendor')->check() && !hasPermission('patient', 'view')) abort(403);

        $request->validate([
            'lab_tests'         => 'nullable|array',
            'lab_tests.*'       => 'integer',
            'radiology_tests'   => 'nullable|array',
            'radiology_tests.*' => 'integer',
            'doctor_profile_id' => 'nullable|integer',
            'priority'          => 'nullable|in:routine,urgent,stat',
            'clinical_notes'    => 'nullable|string|max:1000',
        ]);

        $store_id = Helpers::get_store_id();
        $patient  = Patient::where('store_id', $store_id)->findOrFail($id);

        $hasLab = Schema::hasTable('lab_tests') && Schema::hasTable('lab_orders');
        $hasRad = Schema::hasTable('radiology_tests') && Schema::hasTable('radiology_studies');

        // Scope by store as well as id — an id alone would let one store order another's tests.
        $labSelected = $hasLab
            ? LabTest::where('store_id', $store_id)->whereIn('id', $request->lab_tests ?: [])->get()
            : collect();
        $radSelected = $hasRad
            ? RadiologyTest::where('store_id', $store_id)->whereIn('id', $request->radiology_tests ?: [])->get()
            : collect();

        if ($labSelected->isEmpty() && $radSelected->isEmpty()) {
            Toastr::error('Select at least one test or scan.');
            return back();
        }

        $actorId   = auth('vendor_employee')->id() ?? auth('vendor')->id();
        $actorType = auth('vendor_employee')->check() ? 'vendor_employee' : 'vendor';
        $doctorId  = $request->doctor_profile_id ?: null;
        $priority  = $request->priority ?: 'routine';

        DB::beginTransaction();
        try {
            $summary = [];

            if ($labSelected->isNotEmpty()) {
                $order = LabOrder::create([
                    'store_id'          => $store_id,
                    'patient_id'        => $patient->id,
                    'doctor_profile_id' => $doctorId,
                    'source'            => 'patient',
                    'department'        => 'OPD',
                    'priority'          => $priority,
                    'status'            => 'ordered',
                    'sample_type'       => $labSelected->pluck('sample_type')->filter()->unique()->implode(', ') ?: null,
                    'clinical_notes'    => $request->clinical_notes,
                    'total_amount'      => $labSelected->sum('price'),
                    'created_by'        => $actorId,
                    'created_by_type'   => $actorType,
                ]);
                $order->order_no = 'LAB-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                $order->save();

                foreach ($labSelected as $t) {
                    LabOrderItem::create([
                        'lab_order_id' => $order->id,
                        'lab_test_id'  => $t->id,
                        'test_name'    => $t->name,
                        'department'   => $t->department,
                        'price'        => $t->price,
                        'status'       => 'pending',
                    ]);
                }
                $summary[] = $order->order_no . ' (' . $labSelected->count() . ' test' . ($labSelected->count() > 1 ? 's' : '') . ')';
            }

            foreach ($radSelected as $t) {
                $study = RadiologyStudy::create([
                    'store_id'          => $store_id,
                    'patient_id'        => $patient->id,
                    'doctor_profile_id' => $doctorId,
                    'modality'          => $t->modality ?: 'X-Ray',
                    'study_name'        => $t->name,
                    'body_part'         => $t->body_part,
                    'priority'          => $priority,
                    'status'            => 'pending',
                    'source'            => 'patient',
                    'department'        => 'OPD',
                    'clinical_history'  => $request->clinical_notes,
                    'price'             => $t->price,
                    'scheduled_at'      => now(),
                    'created_by'        => $actorId,
                    'created_by_type'   => $actorType,
                ]);
                $study->update(['study_no' => 'RAD-' . str_pad($study->id, 4, '0', STR_PAD_LEFT)]);
                $summary[] = $study->study_no;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Toastr::error('Could not raise the tests: ' . $e->getMessage());
            return back();
        }

        \App\Models\HospitalActivityLog::record(
            $store_id, 'patient', $patient->id, 'tests_ordered',
            'Tests ordered for ' . $patient->name . ': ' . implode(', ', $summary)
        );

        Toastr::success('Raised ' . implode(', ', $summary) . '.');
        return back();
    }

    public function edit($id)
    {
        if (!auth('vendor')->check() && !hasPermission('patient', 'edit')) abort(403);
        $store_id = Helpers::get_store_id();
        $patient  = Patient::where('store_id', $store_id)->with('medicalHistory', 'documents')->findOrFail($id);

        return view('hmis::vendor.patient.edit', compact('patient'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'   => 'required|string|max:150',
            'phone'  => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female,other',
            'dob'    => 'nullable|date',
        ]);

        $store_id = Helpers::get_store_id();
        $patient  = Patient::where('store_id', $store_id)->findOrFail($id);

        DB::beginTransaction();
        try {
            // Only overwrite fields that were actually submitted. The sidebar inline
            // editors (allergies / chronic conditions) post just one field at a time, so
            // assigning every column unconditionally would wipe everything not included.
            $patientFields = [
                'name', 'dob', 'gender', 'blood_group', 'phone', 'email', 'address',
                'city', 'state', 'pincode', 'emergency_contact_name',
                'emergency_contact_phone', 'emergency_contact_relation', 'allergies',
            ];
            foreach ($patientFields as $f) {
                if ($request->has($f)) {
                    $patient->{$f} = $request->input($f);
                }
            }

            if ($request->hasFile('photo')) {
                $patient->photo = Helpers::upload('patient/', 'jpg', $request->file('photo'));
            }

            $patient->save();

            // request key => medical_history column
            $historyMap = [
                'chronic_conditions' => 'chronic_conditions',
                'past_surgeries'     => 'past_surgeries',
                'medications'        => 'current_medications',
                'family_history'     => 'family_history',
                'medical_notes'      => 'notes',
            ];
            $history = $patient->medicalHistory ?? new PatientMedicalHistory(['patient_id' => $patient->id]);
            $historyTouched = false;
            foreach ($historyMap as $reqKey => $col) {
                if ($request->has($reqKey)) {
                    $history->{$col} = $request->input($reqKey);
                    $historyTouched = true;
                }
            }
            // Checkboxes are absent when unchecked, so only flip them on the full edit form.
            if ($request->has('_full_patient_update')) {
                $history->smoking = $request->has('smoking') ? 1 : 0;
                $history->alcohol = $request->has('alcohol') ? 1 : 0;
                $historyTouched = true;
            }
            if ($historyTouched) {
                $history->updated_by = auth('vendor_employee')->id() ?? auth('vendor')->id();
                $history->save();
            }

            $docFiles = $request->file('docs', []);
            if (!empty($docFiles)) {
                $dir = 'patient/documents/';
                foreach ($docFiles as $i => $docData) {
                    $file = $docData['file'] ?? null;
                    if ($file && $file->isValid()) {
                        $filename = Helpers::upload($dir, $file->getClientOriginalExtension(), $file);
                        $type     = $request->input("docs.{$i}.type", 'other');
                        $label    = $request->input("docs.{$i}.name");
                        PatientDocument::create([
                            'patient_id'    => $patient->id,
                            'document_type' => $type,
                            'document_name' => !empty($label) ? $label : $file->getClientOriginalName(),
                            'file_path'     => $dir . $filename,
                            'uploaded_by'   => auth('vendor_employee')->id() ?? auth('vendor')->id(),
                        ]);
                    }
                }
            }

            DB::commit();

            \App\Models\HospitalActivityLog::record(
                $store_id, 'patient', $patient->id, 'updated',
                "Patient record updated: {$patient->name} ({$patient->patient_uid})"
            );

            if ($request->expectsJson()) {
                return response()->json(['ok' => true]);
            }
            Toastr::success('Patient updated successfully');
            return redirect()->route('vendor.patient.show', $id);
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to update patient: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function uploadDocuments(Request $request, $id)
    {
        $request->validate([
            'files'         => 'required|array|min:1',
            'files.*'       => 'required|file|max:10240',
            'document_type' => 'required|in:id_proof,report,prescription,other,arogyasri,insurance,aadhaar,pan,ration_card,abha,govt_other',
            'document_name' => 'nullable|string|max:100',
        ]);

        $store_id   = Helpers::get_store_id();
        $patient    = Patient::where('store_id', $store_id)->findOrFail($id);
        $dir        = 'patient/documents/';
        $customName = $request->document_name;
        $uploaded   = [];

        foreach ($request->file('files') as $file) {
            $filename = Helpers::upload($dir, $file->getClientOriginalExtension(), $file);
            $doc = PatientDocument::create([
                'patient_id'    => $patient->id,
                'document_type' => $request->document_type,
                'document_name' => $customName ?: $file->getClientOriginalName(),
                'file_path'     => $dir . $filename,
                'uploaded_by'   => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            ]);
            $uploaded[] = [
                'id'            => $doc->id,
                'document_name' => $doc->document_name,
                'document_type' => $doc->document_type,
                'url'           => asset('storage/' . $doc->file_path),
            ];
        }

        return response()->json(['ok' => true, 'documents' => $uploaded]);
    }

    public function listDocuments($id)
    {
        $store_id = Helpers::get_store_id();
        $patient  = Patient::where('store_id', $store_id)->findOrFail($id);

        $docs = $patient->documents()->orderByDesc('created_at')->get()->map(fn($doc) => [
            'id'            => $doc->id,
            'document_type' => $doc->document_type,
            'document_name' => $doc->document_name,
            'url'           => asset('storage/' . $doc->file_path),
        ]);

        return response()->json(['ok' => true, 'documents' => $docs]);
    }

    public function deleteDocument(Request $request, $id, $docId)
    {
        $store_id = Helpers::get_store_id();
        $patient  = Patient::where('store_id', $store_id)->findOrFail($id);
        $doc      = PatientDocument::where('patient_id', $patient->id)->findOrFail($docId);

        \Illuminate\Support\Facades\Storage::disk('public')->delete($doc->file_path);
        $doc->delete();

        return response()->json(['ok' => true]);
    }

    public function destroy($id)
    {
        if (!auth('vendor')->check() && !hasPermission('patient', 'delete')) abort(403);
        $store_id = Helpers::get_store_id();
        $patient  = Patient::where('store_id', $store_id)->findOrFail($id);
        $patient->delete();

        Toastr::success('Patient deleted');
        return redirect()->route('vendor.patient.list');
    }

    public function upload_excel(Request $request)
    {
        Toastr::info('Excel import coming soon');
        return back();
    }

    public function quickSave(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:150',
            'phone' => 'nullable|string|max:20',
        ]);

        $store_id = Helpers::get_store_id();

        DB::beginTransaction();
        try {
            $patient             = new Patient();
            $patient->store_id   = $store_id;
            $patient->patient_uid = $this->generateUid($store_id);
            $patient->name       = $request->name;
            $patient->phone      = $request->phone;
            $patient->address    = $request->address;
            $patient->created_by = auth('vendor_employee')->id() ?? auth('vendor')->id();
            $patient->save();

            PatientMedicalHistory::create(['patient_id' => $patient->id]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'patient' => [
                'id'   => $patient->id,
                'text' => $patient->name . ' (' . $patient->patient_uid . ')',
            ],
        ]);
    }

    private function generateUid(int $store_id): string
    {
        return Patient::generateUid($store_id);
    }
}
