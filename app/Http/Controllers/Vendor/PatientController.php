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

        return view('vendor-views.patient.view', compact('patient'));
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
            Toastr::success('Patient updated successfully');
            return redirect()->route('vendor.patient.show', $id);
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to update patient: ' . $e->getMessage());
            return back()->withInput();
        }
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

    private function generateUid(int $store_id): string
    {
        $last = Patient::where('store_id', $store_id)->latest('id')->first();
        $next = $last ? ($last->id + 1) : 1;
        return 'P-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
