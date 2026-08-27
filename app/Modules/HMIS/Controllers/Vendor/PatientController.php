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
use App\Models\StoreCustomer;
use App\Services\HmisWhatsAppShare;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        // A dental chair needs five details to start work, not twenty-three. For those stores this
        // entry point serves the trimmed intake instead — same URL and the same place in the menu,
        // so nothing has to be found somewhere new. Every other category keeps the full form.
        if (DentalIntakeController::isDental((int) Helpers::get_store_id())) {
            return app(DentalIntakeController::class)->create($request);
        }

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
            'age'    => 'nullable|integer|min:0|max:150',
        ]);

        $store_id = Helpers::get_store_id();

        // Add the patient↔client column before the transaction opens — DDL inside one would
        // implicitly commit it and split the registration in half.
        \App\Services\PatientCustomerLink::ensureSchema();
        DentalIntakeController::ensureSchema();

        DB::beginTransaction();
        try {
            $patient              = new Patient();
            $patient->store_id   = $store_id;
            $patient->patient_uid = $this->generateUid($store_id);
            $patient->name        = $request->name;
            $patient->dob         = $request->dob;
            // Age is kept alongside dob, not derived from it on read: the desk may correct it, and
            // plenty of records carry an age with no birth date at all.
            $patient->age         = $request->filled('age')
                ? (int) $request->age
                : ($request->dob ? \Carbon\Carbon::parse($request->dob)->age : null);
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

        // Patients registered before the two records were joined get their client record here,
        // so the link works without waiting on the backfill command. Silent — these people
        // registered long ago and must not be greeted as if they just signed up.
        if (\App\Services\PatientCustomerLink::ensureSchema() && !$patient->store_customer_id) {
            \App\Models\StoreCustomer::$welcomeOnCreate = false;
            try {
                \App\Services\PatientCustomerLink::fromPatient($patient);
            } finally {
                \App\Models\StoreCustomer::$welcomeOnCreate = true;
            }
        }

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
                ->with(['items', 'doctorProfile.employee', 'results'])
                ->orderByDesc('created_at')->get()
            : collect();

        $radiologyStudies = $hasRad
            ? RadiologyStudy::where('store_id', $store_id)->where('patient_id', $id)
                ->with('doctorProfile.employee')
                ->orderByDesc('created_at')->get()
            : collect();

        // Every HMIS counter — consultation, hospital bill, lab, radiology, pharmacy — bills the
        // patient through a ManualInvoice keyed by bill_to/bill_to_type, so one query is the
        // patient's whole billing history. vendor_id holds the STORE id here, not a user id.
        //
        // The same person is also the store's client, so anything billed to them at a
        // non-hospital counter (pharmacy retail, a service invoice) belongs on this ledger too.
        $customerId = $patient->store_customer_id;
        $invoices = \App\Models\ManualInvoice::where('vendor_id', $store_id)
            ->where(function ($q) use ($id, $customerId) {
                $q->where(fn($q2) => $q2->where('bill_to_type', 'patient')->where('bill_to', $id));
                if ($customerId) {
                    $q->orWhere(fn($q2) => $q2
                        ->where('bill_to_type', '!=', 'patient')
                        ->where('user_type', 'store_user')
                        ->where('bill_to', $customerId));
                }
            })
            ->withCount('invoiceItems')
            ->orderByDesc('created_at')
            ->get();

        $invoiceTotals = [
            'billed' => $invoices->sum('total_amount'),
            'due'    => $invoices->where('payment_status', '!=', 'Paid')->sum('total_amount'),
        ];

        // Every document this patient has been sent — the record link on WhatsApp, or the
        // prescription as a PDF. What they uploaded and what they were given belong on the same
        // screen: "have they got their report yet?" is asked far more often at the desk than it
        // is answerable from the record itself.
        $sentDocs = Schema::hasTable('wa_patient_shares')
            ? DB::table('wa_patient_shares')
                ->where('store_id', $store_id)
                ->where('patient_id', $id)
                ->orderByDesc('created_at')
                ->limit(200)
                ->get()
            : collect();

        return view('hmis::vendor.patient.view', compact(
            'patient', 'appointments', 'opdVisits', 'ipdAdmissions', 'prescriptions', 'consents',
            'labTests', 'radiologyTests', 'doctors', 'labOrders', 'radiologyStudies',
            'invoices', 'invoiceTotals', 'sentDocs'
        ));
    }

    /**
     * Every document the hospital has sent its patients, in one log.
     *
     * The patient profile answers "what did we send this person"; this answers the question the
     * front desk actually asks at the end of a day — which reports went out, and which of them
     * nobody has opened.
     */
    public function sentDocuments(Request $request)
    {
        $store_id = Helpers::get_store_id();

        if (!Schema::hasTable('wa_patient_shares')) {
            return view('hmis::vendor.patient.sent_documents', [
                'shares'  => new LengthAwarePaginator([], 0, 25, 1, ['path' => $request->url()]),
                'summary' => ['total' => 0, 'opened' => 0, 'pending' => 0, 'files' => 0],
            ]);
        }

        // Brings sent_as onto older installs, so the filters below can rely on it.
        HmisWhatsAppShare::ensureTable();

        $search = trim((string) $request->search);
        $kind   = $request->kind;
        $status = $request->status;

        $query = DB::table('wa_patient_shares as s')
            ->leftJoin('patients as p', 'p.id', '=', 's.patient_id')
            ->where('s.store_id', $store_id)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('p.name', 'like', "%{$search}%")
                        ->orWhere('p.patient_uid', 'like', "%{$search}%")
                        ->orWhere('p.phone', 'like', "%{$search}%")
                        ->orWhere('s.sent_to', 'like', "%{$search}%");
                });
            })
            ->when($kind, fn($q) => $q->where('s.kind', $kind))
            ->when($request->from, fn($q) => $q->whereDate('s.created_at', '>=', $request->from))
            ->when($request->to, fn($q) => $q->whereDate('s.created_at', '<=', $request->to))
            ->when($status === 'opened', fn($q) => $q->where('s.views', '>', 0))
            // A file send has no link to open, so it can never be "waiting to be read".
            ->when($status === 'pending', fn($q) => $q->where('s.views', 0)->where('s.sent_as', '!=', 'pdf'))
            ->when($status === 'pdf', fn($q) => $q->where('s.sent_as', 'pdf'))
            ->when($status === 'expired', fn($q) => $q
                ->where('s.sent_as', '!=', 'pdf')
                ->where('s.views', 0)
                ->whereNotNull('s.expires_at')
                ->where('s.expires_at', '<', now()));

        $summary = [
            'total'   => (clone $query)->count(),
            'opened'  => (clone $query)->where('s.views', '>', 0)->count(),
            'pending' => (clone $query)->where('s.views', 0)->where('s.sent_as', '!=', 'pdf')->count(),
            'files'   => (clone $query)->where('s.sent_as', 'pdf')->count(),
        ];

        $shares = $query
            ->orderByDesc('s.created_at')
            ->select('s.*', 'p.name as patient_name', 'p.patient_uid', 'p.phone as patient_phone')
            ->paginate(25)
            ->withQueryString();

        return view('hmis::vendor.patient.sent_documents', compact('shares', 'summary'));
    }

    /**
     * Raise the tests a doctor selected: one lab order carrying every selected lab test, plus
     * one radiology study per selected scan (radiology tracks each scan as its own study). Both
     * land in their department's worklist queue.
     *
     * Shared by the patient page (form post) and the OPD consultation tab (fetch) — one ordering
     * path, so the two screens cannot drift apart. Answers JSON when the caller asks for it.
     */
    public function orderTests(Request $request, $id)
    {
        // Two entry points, two different permissions: the patient page needs patient.view, the
        // OPD consultation tab needs opd_register.view. Gating on patient.view alone 403'd every
        // doctor ordering from a consult, which is the common case.
        if (
            !auth('vendor')->check()
            && !hasPermission('patient', 'view')
            && !hasPermission('opd_register', 'view')
        ) {
            abort(403);
        }

        $request->validate([
            'lab_tests'         => 'nullable|array',
            'lab_tests.*'       => 'integer',
            'radiology_tests'   => 'nullable|array',
            'radiology_tests.*' => 'integer',
            'doctor_profile_id' => 'nullable|integer',
            'opd_id'            => 'nullable|integer',
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
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Select at least one test or scan.'], 422);
            }
            Toastr::error('Select at least one test or scan.');
            return back();
        }

        $actorId   = auth('vendor_employee')->id() ?? auth('vendor')->id();
        $actorType = auth('vendor_employee')->check() ? 'vendor_employee' : 'vendor';
        $doctorId  = $request->doctor_profile_id ?: null;
        $priority  = $request->priority ?: 'routine';
        $opdId     = $request->opd_id ?: null;
        $source    = $opdId ? 'opd' : 'patient';

        DB::beginTransaction();
        try {
            $summary = [];

            if ($labSelected->isNotEmpty()) {
                $order = LabOrder::create([
                    'store_id'          => $store_id,
                    'patient_id'        => $patient->id,
                    'doctor_profile_id' => $doctorId,
                    'opd_id'            => $opdId,
                    'source'            => $source,
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
                    // radiology_studies has no opd_id column, so the visit link lives in source only.
                    'source'            => $source,
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
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Could not raise the tests: ' . $e->getMessage()], 500);
            }
            Toastr::error('Could not raise the tests: ' . $e->getMessage());
            return back();
        }

        \App\Models\HospitalActivityLog::record(
            $store_id, 'patient', $patient->id, 'tests_ordered',
            'Tests ordered for ' . $patient->name . ': ' . implode(', ', $summary)
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'summary'  => implode(', ', $summary),
                'lab'      => $labSelected->count(),
                'scans'    => $radSelected->count(),
                'redirect' => $radSelected->isNotEmpty() && $labSelected->isEmpty()
                    ? route('vendor.radiology.worklist')
                    : route('vendor.lab.worklist'),
            ]);
        }

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
            'age'    => 'nullable|integer|min:0|max:150',
        ]);

        $store_id = Helpers::get_store_id();
        $patient  = Patient::where('store_id', $store_id)->findOrFail($id);

        \App\Services\PatientCustomerLink::ensureSchema();
        DentalIntakeController::ensureSchema();

        DB::beginTransaction();
        try {
            // Only overwrite fields that were actually submitted. The sidebar inline
            // editors (allergies / chronic conditions) post just one field at a time, so
            // assigning every column unconditionally would wipe everything not included.
            $patientFields = [
                'name', 'dob', 'age', 'gender', 'blood_group', 'phone', 'email', 'address',
                'city', 'state', 'pincode', 'emergency_contact_name',
                'emergency_contact_phone', 'emergency_contact_relation', 'allergies',
            ];
            foreach ($patientFields as $f) {
                if ($request->has($f)) {
                    $patient->{$f} = $request->input($f) === '' ? null : $request->input($f);
                }
            }

            // A blanked age on a form that did send a dob falls back to the computed one, so the
            // record never ends up with neither.
            if ($request->has('age') && $patient->age === null && $patient->dob) {
                $patient->age = \Carbon\Carbon::parse($patient->dob)->age;
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

    /**
     * Send a patient a document on WhatsApp — one already on their record, or one uploaded here
     * and then sent.
     *
     * An uploaded file is filed on the patient's record as well as sent: a document worth sending
     * is a document worth keeping, and a hospital that has to upload the same discharge summary
     * twice stops filing it at all.
     */
    public function sendDocument(Request $request, $id)
    {
        $request->validate([
            'document_id' => 'nullable|integer',
            'file'        => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp',
            'title'       => 'nullable|string|max:100',
            'phone'       => 'nullable|string|max:20',
        ]);

        $store_id = Helpers::get_store_id();
        $patient  = Patient::where('store_id', $store_id)->findOrFail($id);

        $doc = null;
        if ($request->hasFile('file')) {
            $file     = $request->file('file');
            $dir      = 'patient/documents/';
            $filename = Helpers::upload($dir, $file->getClientOriginalExtension(), $file);
            $doc = PatientDocument::create([
                'patient_id'    => $patient->id,
                'document_type' => 'other',
                'document_name' => $request->title ?: $file->getClientOriginalName(),
                'file_path'     => $dir . $filename,
                'uploaded_by'   => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            ]);
        } elseif ($request->document_id) {
            $doc = PatientDocument::where('patient_id', $patient->id)->find($request->document_id);
        }

        if (!$doc) {
            return response()->json(['ok' => false, 'message' => 'Choose a document from the record, or upload one to send.'], 422);
        }

        $result = HmisWhatsAppShare::document(
            $store_id,
            $patient,
            $doc->file_path,
            $request->title ?: $doc->document_name,
            $request->phone,
            (int) $doc->id
        );

        return response()->json([
            'ok'      => !empty($result['success']),
            'message' => $result['message'],
        ], !empty($result['success']) ? 200 : 422);
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

    /**
     * Everything that records something having happened to a patient — clinical or financial.
     *
     * Six of these hold the row down at the database with ON DELETE RESTRICT (appointments,
     * opd_visits, ipd_admissions, prescriptions, lab_test_requests, ot_schedules); the rest carry
     * a patient_id with no constraint behind it and would simply be orphaned. Both are treated
     * the same way here: a patient who has any history is not deleted, because a hospital may not
     * quietly destroy visit, prescription or billing records to tidy up a list.
     *
     * Deliberately NOT in this list: patient_documents and patient_medical_history, which cascade
     * at the database and belong to the patient rather than recording an event.
     */
    const PATIENT_HISTORY_TABLES = [
        'appointments'              => 'appointment',
        'opd_visits'                => 'OPD visit',
        'ipd_admissions'            => 'IPD admission',
        'prescriptions'             => 'prescription',
        'lab_test_requests'         => 'lab test request',
        'lab_orders'                => 'lab order',
        'lab_invoices'              => 'lab invoice',
        'ot_schedules'              => 'surgery schedule',
        'preop_cases'               => 'pre-op case',
        'radiology_studies'         => 'radiology study',
        'radiology_invoices'        => 'radiology invoice',
        'opd_consultation_receipts' => 'consultation receipt',
        'nursing_notes'             => 'nursing note',
        'nursing_vitals'            => 'vitals record',
        'nursing_mar_orders'        => 'medication order',
        'nursing_fluid_entries'     => 'fluid entry',
        'diet_charts'               => 'diet chart',
        'patient_consents'          => 'consent form',
    ];

    /**
     * Everything hanging off the client record a patient is mirrored onto, as
     * [table => [column, label]].
     *
     * Separate from PATIENT_HISTORY_TABLES and not a duplicate of it: a hospital client can be
     * billed, quoted and given tasks from the counter without any of it touching HMIS, so a
     * patient can be clean while the client behind them is not.
     *
     * Every entry is Schema-guarded — most belong to optional modules a hospital may never have
     * installed — and the ambiguous columns are qualified: tasks.user_id holds a platform user id
     * as often as a client id, and only user_type says which.
     */
    const CLIENT_HISTORY_TABLES = [
        'laundry_orders'   => ['store_customer_id', 'store_id',  'laundry order'],
        'laundry_challans' => ['store_customer_id', 'store_id',  'laundry challan'],
        // client_name is an id whose table depends on the flow that wrote it — QuoteController
        // reads it as a platform user in one place and as a store client in another, with no
        // discriminator column. Scoped to this store so an unrelated user id elsewhere on the
        // platform cannot block a delete; within one store a match is worth a human looking.
        'quotations'       => ['client_name',       'vendor_id', 'quotation'],
    ];

    /**
     * What stands in the way of removing this client along with their patient record.
     *
     * Returns human-readable phrases, empty when the client is only a mirror of the patient and
     * carries nothing of its own.
     */
    private function clientHistory(StoreCustomer $client, int $storeId): array
    {
        $blocking = [];

        // bill_to is a bare id whose meaning comes from bill_to_type: 'patient' rows are keyed on
        // the patient and already covered above, and a patient id can collide numerically with a
        // client id, so both those and supplier invoices are excluded rather than counted twice.
        if (Schema::hasTable('manual_invoices')) {
            $invoices = DB::table('manual_invoices')
                ->where('vendor_id', $storeId)
                ->where('bill_to', $client->id)
                ->whereNotIn('bill_to_type', ['vendor', 'patient'])
                ->count();
            if ($invoices) {
                $blocking[] = $invoices . ' invoice' . ($invoices === 1 ? '' : 's');
            }
        }

        if (Schema::hasTable('tasks') && Schema::hasColumn('tasks', 'user_id') && Schema::hasColumn('tasks', 'user_type')) {
            $tasks = DB::table('tasks')
                ->where('user_id', $client->id)
                ->where('user_type', 'customer')
                ->count();
            if ($tasks) {
                $blocking[] = $tasks . ' task' . ($tasks === 1 ? '' : 's');
            }
        }

        foreach (self::CLIENT_HISTORY_TABLES as $table => [$column, $storeColumn, $label]) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }
            $count = DB::table($table)
                ->where($column, $client->id)
                ->when(Schema::hasColumn($table, $storeColumn), fn($q) => $q->where($storeColumn, $storeId))
                ->count();
            if ($count > 0) {
                $blocking[] = $count . ' ' . $label . ($count === 1 ? '' : 's');
            }
        }

        return $blocking;
    }

    public function destroy($id)
    {
        if (!auth('vendor')->check() && !hasPermission('patient', 'delete')) abort(403);
        $store_id = Helpers::get_store_id();
        $patient  = Patient::where('store_id', $store_id)->findOrFail($id);

        // Without this the RESTRICT constraints surface as a raw SQLSTATE 23000 dump, which tells
        // the receptionist nothing about which record is in the way.
        $blocking = [];
        foreach (self::PATIENT_HISTORY_TABLES as $table => $label) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $count = DB::table($table)->where('patient_id', $patient->id)->count();
            if ($count > 0) {
                $blocking[] = $count . ' ' . $label . ($count === 1 ? '' : 's');
            }
        }

        if (!empty($blocking)) {
            Toastr::error(
                $patient->name . ' has ' . implode(', ', $blocking) . ' on record and cannot be '
                . 'deleted. Those are part of the patient\'s history — edit the patient instead.'
            );
            return back();
        }

        // In a hospital the patient and the client are one person, so the mirror goes with them —
        // otherwise the client survives every deletion and is silently re-linked the next time the
        // same number is registered, which is why a re-added patient never gets a second welcome.
        $client = $patient->store_customer_id
            ? StoreCustomer::where('store_id', $store_id)->find($patient->store_customer_id)
            : null;

        // Refused outright rather than deleting the patient and keeping the client: half a
        // deletion is the state nobody can reason about later, and these records are the client's
        // own, not the patient's, so the staff member has to deal with them first.
        if ($client) {
            $clientBlocking = $this->clientHistory($client, (int) $store_id);
            if (!empty($clientBlocking)) {
                Toastr::error(
                    $patient->name . ' is also a client of this store with ' . implode(', ', $clientBlocking)
                    . ' against them, so nothing was deleted. Clear those from the client record first, '
                    . 'or keep the patient and edit it instead.'
                );
                return back();
            }
        }

        DB::transaction(function () use ($patient, $client) {
            // patient_documents cascades, but the files it points at do not — deleting the row
            // without this leaves the uploads behind on the disk for good.
            foreach (PatientDocument::where('patient_id', $patient->id)->get() as $doc) {
                if ($doc->file_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($doc->file_path);
                }
            }
            $patient->delete();

            if ($client) {
                // Patient goes first: the client's `deleted` side has no hook, but leaving the
                // patient pointing at a client that is gone would be the worse half-state if the
                // transaction were ever split.
                Helpers::delete_file('profile/', $client->profile_pic);
                $client->delete();
            }
        });

        Toastr::success($client ? 'Patient and their client record deleted' : 'Patient deleted');
        return redirect()->route('vendor.patient.list');
    }

    public function upload_excel(Request $request)
    {
        Toastr::info('Excel import coming soon');
        return back();
    }

    public function quickSave(Request $request)
    {
        // Separators stripped before validating, so the stored number is the tidy one however it
        // was keyed. Identical handling to the dental intake screen.
        $request->merge([
            'phone' => preg_replace('/[\s\-()]/', '', (string) $request->input('phone')),
        ]);

        $request->validate([
            'name'    => 'required|string|max:150',
            // Optional +91 / 0 trunk prefix, then a 10-digit mobile — Indian numbers start 6-9.
            'phone'   => ['required', 'string', 'max:20', 'regex:/^(?:\+?91|0)?[6-9]\d{9}$/'],
            'age'     => 'required|integer|min:0|max:150',
            'dob'     => 'nullable|date|before_or_equal:today',
            'phone_relation' => 'nullable|string|max:100',
            'gender'  => 'required|in:male,female,other',
            'address' => 'nullable|string|max:500',
        ], [
            'phone.regex' => 'Enter a valid 10-digit mobile number.',
        ]);

        $store_id = Helpers::get_store_id();

        \App\Services\PatientCustomerLink::ensureSchema();
        // DDL before the transaction, same reason as the link table above.
        DentalIntakeController::ensureSchema();

        DB::beginTransaction();
        try {
            $patient             = new Patient();
            $patient->store_id   = $store_id;
            $patient->patient_uid = $this->generateUid($store_id);
            $patient->name       = $request->name;
            $patient->phone      = $request->phone;
            $patient->age        = $request->age;
            $patient->dob        = $request->filled('dob') ? $request->dob : null;
            // Only recorded when the number turned out to be shared and the desk answered.
            $patient->phone_relation = $request->filled('phone_relation')
                ? trim((string) $request->phone_relation)
                : null;
            $patient->gender     = $request->gender;
            $patient->address    = $request->address;
            // "More Info" rows off the quick-add modal, in the same label → value shape the intake
            // screen and the bill use, so a patient added here bills identically to one added there.
            $patient->custom_info = json_encode(DentalIntakeController::rowsFrom($request));
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
