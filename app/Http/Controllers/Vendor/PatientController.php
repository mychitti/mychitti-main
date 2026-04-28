<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\PatientMedicalHistory;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        return view('vendor-views.patient.index');
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

        return view('vendor-views.patient.list', compact('patients', 'search'));
    }

    public function export(Request $request)
    {
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

            // Save ID proof
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

            // Save medical reports (multiple)
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

        return view('vendor-views.patient.view', compact(
            'patient', 'appointments', 'opdVisits', 'ipdAdmissions', 'prescriptions', 'consents'
        ));
    }

    public function edit($id)
    {
        $store_id = Helpers::get_store_id();
        $patient  = Patient::where('store_id', $store_id)->with('medicalHistory')->findOrFail($id);

        return view('vendor-views.patient.edit', compact('patient'));
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

            if ($request->hasFile('photo')) {
                $patient->photo = Helpers::upload('patient/', 'jpg', $request->file('photo'));
            }

            $patient->save();

            $history = $patient->medicalHistory ?? new PatientMedicalHistory(['patient_id' => $patient->id]);
            $history->chronic_conditions  = $request->chronic_conditions;
            $history->past_surgeries      = $request->past_surgeries;
            $history->current_medications = $request->medications;
            $history->family_history      = $request->family_history;
            $history->smoking             = $request->has('smoking') ? 1 : 0;
            $history->alcohol             = $request->has('alcohol') ? 1 : 0;
            $history->notes               = $request->medical_notes;
            $history->updated_by          = auth('vendor_employee')->id() ?? auth('vendor')->id();
            $history->save();

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
            'document_type' => 'required|in:id_proof,report,prescription,other',
            'document_name' => 'nullable|string|max:100',
        ]);

        $store_id    = Helpers::get_store_id();
        $patient     = Patient::where('store_id', $store_id)->findOrFail($id);
        $dir         = 'patient/documents/';
        $customName  = $request->document_name;
        $uploaded    = [];

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

    // ── Quick-create patient via AJAX (modal) ──────────────────────────
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
        $prefix  = strtoupper(Helpers::get_business_settings('patient_uid_prefix_' . $store_id) ?? 'P');
        $padding = (int)(Helpers::get_business_settings('patient_uid_padding_' . $store_id) ?? 5);
        $minSerial = (int)(Helpers::get_business_settings('patient_uid_serial_' . $store_id) ?? 1);

        $lastUid = Patient::where('store_id', $store_id)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('patient_uid');

        $autoNext = 1;
        if ($lastUid && preg_match('/(\d+)$/', $lastUid, $m)) {
            $autoNext = (int)$m[1] + 1;
        }

        $next = max($autoNext, $minSerial);

        return $prefix . '-' . str_pad($next, $padding, '0', STR_PAD_LEFT);
    }
}
