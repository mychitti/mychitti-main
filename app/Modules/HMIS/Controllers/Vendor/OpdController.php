<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcceptedServiceRequest;
use App\Models\DoctorProfile;
use App\Models\DoctorSlot;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\ServiceRequest;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;

class OpdController extends Controller
{
    // The opd_register feature was originally seeded without an "edit" action, so the
    // role grid had no permission row to save against. Self-heal any missing actions here.
    private function ensureOpdPermissions(): void
    {
        if (!Schema::hasTable('features') || !Schema::hasTable('feature_permissions')) {
            return;
        }
        $featureId = DB::table('features')->where('name', 'opd_register')->value('id');
        if (!$featureId) {
            return;
        }
        // receipt_view gates the OP consultation receipt for the Receptionist role only; every
        // other role gets it unconditionally (see _canViewOpdReceipt).
        foreach (['edit', 'receipt_view'] as $action) {
            if (!DB::table('feature_permissions')->where('feature_id', $featureId)->where('action', $action)->exists()) {
                DB::table('feature_permissions')->insert(['feature_id' => $featureId, 'action' => $action, 'free' => 0]);
            }
        }
    }

    // Diagnosis/treatment are recorded on the visit itself after registration, and the dropdown
    // they are picked from is per-store. Both are provisioned here so no migration is needed.
    private function ensureClinicalSchema(): void
    {
        if (Schema::hasTable('opd_visits')) {
            foreach (['diagnosis', 'treatment'] as $column) {
                if (!Schema::hasColumn('opd_visits', $column)) {
                    DB::statement("ALTER TABLE `opd_visits` ADD COLUMN `{$column}` TEXT NULL AFTER `chief_complaint`");
                }
            }
        }

        if (!Schema::hasTable('opd_clinical_terms')) {
            DB::statement("CREATE TABLE `opd_clinical_terms` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `type` VARCHAR(20) NOT NULL,
                `name` VARCHAR(150) NOT NULL,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `oct_store_type_name` (`store_id`, `type`, `name`),
                KEY `oct_store_type_idx` (`store_id`, `type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function index(Request $request)
    {
        $this->ensureOpdPermissions();
        $this->ensureClinicalSchema();
        $preset = request('date_range') ?? 'today';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from = $from  = $range['start'];
        $formatted_to = $to = $range['end'];

        $store_id = Helpers::get_store_id();
        $search   = $request->search;

        // "My OPD Appointments" scope: restrict to this doctor's visits
        $myScope = $request->scope === 'my' && auth('vendor_employee')->check();
        $myDoctorProfileId = null;
        if ($myScope) {
            $myProfile = DoctorProfile::where('emp_id', auth('vendor_employee')->id())
                ->where('store_id', $store_id)
                ->first();
            $myDoctorProfileId = $myProfile?->id;
        }

        $doctor = $myScope ? null : $request->doctor;

        $visits = OpdVisit::where('store_id', $store_id)
            ->whereBetween('visit_date', [$formatted_from, $formatted_to])
            ->when($myScope, fn($q) => $q->where('doctor_profile_id', $myDoctorProfileId))
            ->when(!$myScope && $doctor, fn($q) => $q->where('doctor_profile_id', $doctor))
            ->when($search, function ($q) use ($search) {
                $q->whereHas('patient', fn($p) => $p->where('name', 'like', "%$search%")
                    ->orWhere('patient_uid', 'like', "%$search%"));
            })
            ->with(['patient', 'doctorProfile.employee'])
            ->orderByRaw('consultation_receipt_id IS NOT NULL') // fresh appointments first, completed (receipt generated) last
            ->orderBy('token_number')
            ->paginate(20);

        $doctors = $myScope ? collect() : DoctorProfile::where('store_id', $store_id)
            ->with('employee')
            ->get();

        return view('hmis::vendor.opd.index', compact('preset', 'visits', 'doctors', 'from', 'to', 'myScope'));
    }

    public function export(Request $request)
    {
        if (!auth('vendor')->check() && !hasPermission('opd_register', 'export')) abort(403);
        $preset = $request->get('date_range', 'today');
        $custom = $request->get('custom_date_range');
        $range  = Helpers::calculatePresetDates($preset, $custom);
        $from   = $range['start']->toDateString();
        $to     = $range['end']->toDateString();

        $store_id = Helpers::get_store_id();

        $visits = OpdVisit::where('store_id', $store_id)
            ->whereBetween('visit_date', [$from, $to])
            ->when($request->doctor, fn($q) => $q->where('doctor_profile_id', $request->doctor))
            ->when($request->search, function ($q) use ($request) {
                $q->whereHas('patient', fn($p) => $p->where('name', 'like', "%{$request->search}%")
                    ->orWhere('patient_uid', 'like', "%{$request->search}%"));
            })
            ->with(['patient', 'doctorProfile.employee'])
            ->orderBy('token_number')
            ->get();

        $headings = ['Token', 'Visit Date', 'Patient', 'MUID', 'Doctor', 'Chief Complaint', 'Diagnosis', 'Treatment', 'BP', 'Temperature', 'Weight', 'Status'];
        $data = $visits->map(fn($v) => [
            $v->token_number,
            $v->visit_date,
            $v->patient?->name,
            $v->patient?->patient_uid,
            'Dr. ' . trim(($v->doctorProfile?->employee?->f_name ?? '') . ' ' . ($v->doctorProfile?->employee?->l_name ?? '')),
            $v->chief_complaint,
            $v->diagnosis,
            $v->treatment,
            $v->bp,
            $v->temperature,
            $v->weight,
            ucfirst($v->status ?? ''),
        ])->toArray();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($data, $headings),
            'opd_visits_' . $from . '_' . $to . '.xlsx'
        );
    }

    public function create(Request $request, $id = null)
    {
        if (!auth('vendor')->check() && !hasPermission('opd_register', 'add')) abort(403);
        $store_id = Helpers::get_store_id();
        $patients = Patient::where('store_id', $store_id)->where('status', 1)->orderBy('name')->get();
        $doctors  = DoctorProfile::where('store_id', $store_id)->with('employee')->get();

        $nextToken = (OpdVisit::where('store_id', $store_id)
            ->whereDate('visit_date', now()->toDateString())
            ->max('token_number') ?? 0) + 1;

        $prefillPatient = $request->patient_id
            ? Patient::where('store_id', $store_id)->find($request->patient_id)
            : null;

        $prefillBooking = null;
        if ($id) {
            $sr = ServiceRequest::find((int) $id);
            if ($sr) {
                $sentTo = array_map('intval', array_filter(explode(',', $sr->sent_to ?? '')));
                if (in_array((int) $store_id, $sentTo)) {
                    $user   = User::find($sr->user_id);
                    $doctor = $sr->preferred_doctor_id
                        ? DoctorProfile::with('employee')->find($sr->preferred_doctor_id)
                        : null;
                    $slot   = $sr->preferred_slot_id
                        ? DoctorSlot::find($sr->preferred_slot_id)
                        : null;

                    $isOther      = $sr->patient_for === 'other' && $sr->patient_name;
                    $patientName  = $isOther
                        ? $sr->patient_name
                        : ($user ? (trim(($user->f_name ?? '') . ' ' . ($user->l_name ?? '')) ?: $user->name) : 'Unknown');
                    $patientPhone = $isOther ? $sr->patient_phone : $user?->phone;

                    $slotLabel = $slot
                        ? substr($slot->slot_start, 0, 5) . ' – ' . substr($slot->slot_end, 0, 5)
                        : $sr->preferred_time;

                    $hmisPatient = null;
                    if ($sr->user_id && !$isOther) {
                        $hmisPatient = \App\Models\Patient::firstOrCreate(
                            ['store_id' => $store_id, 'user_id' => $sr->user_id],
                            ['name' => $patientName, 'phone' => $patientPhone, 'store_id' => $store_id, 'user_id' => $sr->user_id]
                        );
                    } elseif ($patientPhone) {
                        $hmisPatient = \App\Models\Patient::where('store_id', $store_id)->where('phone', $patientPhone)->first();
                    }

                    $prefillBooking = [
                        'sr_id'            => $sr->id,
                        'service_name'     => $sr->item?->name,
                        'patient_name'     => $patientName,
                        'patient_phone'    => $patientPhone,
                        'patient_id'       => $hmisPatient?->id,
                        'doctor_name'      => $doctor
                            ? 'Dr. ' . trim(($doctor->employee?->f_name ?? '') . ' ' . ($doctor->employee?->l_name ?? ''))
                            : null,
                        'appointment_date' => $sr->preferred_date,
                        'slot_label'       => $slotLabel,
                        'reason'           => $sr->requirements,
                    ];
                }
            }
        }

        return view('hmis::vendor.opd.create', compact('patients', 'doctors', 'nextToken', 'prefillPatient', 'prefillBooking'));
    }

    public function store(Request $request)
    {
        $store_id = Helpers::get_store_id();

        if ($request->booking_mode === 'booked') {
            $request->validate(['service_request_id' => 'required|integer']);

            $sr = ServiceRequest::findOrFail($request->service_request_id);

            $sentTo = array_map('intval', array_filter(explode(',', $sr->sent_to ?? '')));
            if (!in_array((int)$store_id, $sentTo)) {
                Toastr::error('This appointment does not belong to your store.');
                return back();
            }

            if (!$sr->preferred_doctor_id && !$request->booked_doctor_profile_id) {
                Toastr::error('Please select a doctor for this booking.');
                return back();
            }

            $patientId        = $this->resolvePatientId($sr, $store_id);
            $doctorProfileId  = $sr->preferred_doctor_id ?: (int) $request->booked_doctor_profile_id;
            $visitDate        = $sr->preferred_date ?? now()->toDateString();
            $visitType        = 'new';
        } else {
            $request->validate([
                'patient_id'        => 'required|integer',
                'doctor_profile_id' => 'required|integer',
                'visit_date'        => 'required|date',
                'visit_type'        => 'required|in:' . implode(',', array_keys(OpdVisit::VISIT_TYPES)),
            ]);

            $patientId       = $request->patient_id;
            $doctorProfileId = $request->doctor_profile_id;
            $visitDate       = $request->visit_date;
            $visitType       = $request->visit_type;
        }

        $request->validate([
            'chief_complaint'  => 'nullable|string|max:500',
            'bp_systolic'      => 'nullable|integer|min:0|max:300',
            'bp_diastolic'     => 'nullable|integer|min:0|max:200',
            'temperature'      => 'nullable|numeric|min:90|max:110',
            'weight'           => 'nullable|numeric|min:0|max:500',
            'height'           => 'nullable|numeric|min:0|max:300',
            'spo2'             => 'nullable|integer|min:0|max:100',
            'pulse_rate'       => 'nullable|integer|min:0|max:300',
            'respiratory_rate' => 'nullable|integer|min:0|max:100',
            'notes'            => 'nullable|string',
        ]);

        // Link the visit to the appointment provisioned when the lead was confirmed, so the
        // appointment, its token and the OPD record stay one chain instead of three orphans.
        $appointmentId = $request->appointment_id ?: null;
        if (!$appointmentId && $request->booking_mode === 'booked' && isset($sr)) {
            try {
                $appointmentId = \App\Services\LeadAppointmentService::provision((int) $sr->id, (int) $store_id)?->id;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('OPD appointment link skipped for lead ' . $sr->id . ': ' . $e->getMessage());
            }
        }

        $nextToken = (OpdVisit::where('store_id', $store_id)
            ->whereDate('visit_date', $visitDate)
            ->max('token_number') ?? 0) + 1;

        OpdVisit::create([
            'store_id'            => $store_id,
            'patient_id'          => $patientId,
            'doctor_profile_id'   => $doctorProfileId,
            'appointment_id'      => $appointmentId,
            'service_request_id'  => $request->booking_mode === 'booked' ? ($sr->id ?? null) : null,
            'visit_date'          => $visitDate,
            'token_number'        => $request->token_number ?? $nextToken,
            'visit_type'          => $visitType,
            'chief_complaint'     => $request->chief_complaint,
            'bp_systolic'         => $request->bp_systolic,
            'bp_diastolic'        => $request->bp_diastolic,
            'temperature'         => $request->temperature,
            'weight'              => $request->weight,
            'height'              => $request->height,
            'spo2'                => $request->spo2,
            'pulse_rate'          => $request->pulse_rate,
            'respiratory_rate'    => $request->respiratory_rate,
            'notes'               => $request->notes,
            'recorded_by'         => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            'status'              => 'visited',
        ]);

        if ($request->booking_mode === 'booked' && isset($sr) && $doctorProfileId) {
            $doctorProfile  = DoctorProfile::find($doctorProfileId);
            $acceptedRecord = AcceptedServiceRequest::where('service_request_id', $sr->id)
                ->where('vendor_id', $store_id)
                ->first();

            if ($acceptedRecord && $doctorProfile?->emp_id) {
                $acceptedRecord->assigned_status = 'Assigned';
                $acceptedRecord->assigned_type   = 'staff';
                $acceptedRecord->assigned_to     = $doctorProfile->emp_id;
                $acceptedRecord->assigned_at     = date('Y-m-d H:i:s');
                $acceptedRecord->save();
            }
        }

        \App\Models\HospitalActivityLog::record(
            $store_id, 'opd_visit', null, 'created',
            "OPD visit recorded for patient #{$patientId} with doctor #{$doctorProfileId} on {$visitDate} (token #{$nextToken})",
            ['patient_id' => $patientId, 'doctor_profile_id' => $doctorProfileId, 'visit_date' => $visitDate]
        );

        Toastr::success('OPD visit recorded.');
        return Redirect::route('vendor.opd.index');
    }

    private function resolvePatientId(ServiceRequest $sr, int $storeId): int
    {
        // Shared find-then-create, so a repeat booking reuses the existing patient instead of
        // fragmenting the history across duplicate rows.
        $patient = \App\Services\LeadAppointmentService::resolvePatient($sr, $storeId);
        if ($patient) {
            return $patient->id;
        }

        return Patient::create([
            'store_id'    => $storeId,
            'user_id'     => null,
            'patient_uid' => Patient::generateUid($storeId),
            'name'        => 'Patient',
            'phone'       => null,
            'email'       => null,
            'status'      => 1,
        ])->id;
    }

    public function show($id)
    {
        if (!auth('vendor')->check() && !hasPermission('opd_register', 'view')) abort(403);
        $this->ensureClinicalSchema();
        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)
            ->with(['patient.documents', 'patient.medicalHistory', 'doctorProfile.employee', 'recorder'])
            ->findOrFail($id);

        $pastVisits = OpdVisit::where('store_id', $store_id)
            ->where('patient_id', $visit->patient_id)
            ->where('id', '!=', $visit->id)
            ->with('doctorProfile.employee')
            ->orderByDesc('visit_date')
            ->get();

        // Prescription written for this visit, if any (linked via appointment / service request,
        // else the latest one this doctor wrote for this patient on the visit date).
        $currentPrescription = Prescription::where('store_id', $store_id)
            ->where('patient_id', $visit->patient_id)
            ->where(function ($q) use ($visit) {
                if ($visit->appointment_id) {
                    $q->where('appointment_id', $visit->appointment_id);
                } elseif ($visit->service_request_id) {
                    $q->where('service_request_id', $visit->service_request_id);
                } else {
                    $q->where('doctor_profile_id', $visit->doctor_profile_id)
                        ->whereDate('created_at', $visit->visit_date ?? today());
                }
            })
            ->with(['store', 'doctorProfile.employee', 'patient', 'items'])
            ->latest()
            ->first();

        // Prior prescriptions for this patient (excludes the current visit's prescription).
        $pastPrescriptions = Prescription::where('store_id', $store_id)
            ->where('patient_id', $visit->patient_id)
            ->when($currentPrescription, fn($q) => $q->where('id', '!=', $currentPrescription->id))
            ->with(['doctorProfile.employee', 'items'])
            ->latest()
            ->get();

        // Lab/Radiology build their tables lazily on first visit to those modules, so guard every
        // read — the OPD page must not break for a hospital that has never opened them.
        $hasLab = Schema::hasTable('lab_tests') && Schema::hasTable('lab_orders');
        $hasRad = Schema::hasTable('radiology_tests') && Schema::hasTable('radiology_studies');

        $labTests = $hasLab
            ? \App\Models\LabTest::where('store_id', $store_id)->where('is_active', 1)
                ->orderBy('department')->orderBy('name')->get()
                ->groupBy(fn ($t) => $t->department ?: 'Other')
            : collect();

        $radiologyTests = $hasRad
            ? \App\Models\RadiologyTest::where('store_id', $store_id)->where('is_active', 1)
                ->orderBy('modality')->orderBy('name')->get()
                ->groupBy(fn ($t) => $t->modality ?: 'Other')
            : collect();

        // Already raised for this patient, so the doctor doesn't re-order what is pending.
        $labOrders = $hasLab
            ? \App\Models\LabOrder::where('store_id', $store_id)->where('patient_id', $visit->patient_id)
                ->with('items')->orderByDesc('created_at')->get()
            : collect();

        $radiologyStudies = $hasRad
            ? \App\Models\RadiologyStudy::where('store_id', $store_id)->where('patient_id', $visit->patient_id)
                ->orderByDesc('created_at')->get()
            : collect();

        $diagnosisOptions = \App\Models\OpdClinicalTerm::listFor($store_id, \App\Models\OpdClinicalTerm::TYPE_DIAGNOSIS);
        $treatmentOptions = \App\Models\OpdClinicalTerm::listFor($store_id, \App\Models\OpdClinicalTerm::TYPE_TREATMENT);

        // This hospital's own casemix — how often each term is used and which treatments actually
        // accompany which diagnosis here. Drives the ordering and the suggestion chips.
        $termInsights = \App\Services\OpdTermInsights::for($store_id);

        // Follow-ups already on the books for this patient, so the doctor doesn't double-book.
        $upcomingVisits = \App\Models\Appointment::where('store_id', $store_id)
            ->where('patient_id', $visit->patient_id)
            ->where('appointment_date', '>=', today())
            ->whereIn('status', ['scheduled', 'checked_in'])
            ->with('doctorProfile.employee', 'token')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        return view('hmis::vendor.opd.show', compact(
            'visit', 'pastVisits', 'currentPrescription', 'pastPrescriptions',
            'labTests', 'radiologyTests', 'labOrders', 'radiologyStudies',
            'diagnosisOptions', 'treatmentOptions', 'upcomingVisits', 'termInsights'
        ));
    }

    /**
     * Schedule the patient's next visit straight from the consultation screen, so the doctor
     * never has to leave the encounter to book a follow-up.
     */
    public function nextVisit(Request $request, $id)
    {
        if (!auth('vendor')->check() && !hasPermission('opd_register', 'edit')) abort(403);

        $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'slot_id'          => 'nullable|integer|exists:doctor_slots,id',
            'reason'           => 'nullable|string|max:500',
        ]);

        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)->findOrFail($id);

        if (!$visit->doctor_profile_id) {
            Toastr::error('This visit has no doctor assigned, so a follow-up cannot be booked.');
            return back();
        }

        try {
            $next = \App\Services\NextVisitService::schedule(
                (int) $store_id,
                (int) $visit->patient_id,
                (int) $visit->doctor_profile_id,
                $request->appointment_date,
                $request->appointment_time,
                $request->slot_id ? (int) $request->slot_id : null,
                $request->reason,
                ['from_opd_visit_id' => (int) $visit->id]
            );
        } catch (\RuntimeException $e) {
            Toastr::error($e->getMessage());
            return back();
        } catch (\Throwable $e) {
            Toastr::error('Could not schedule next visit: ' . $e->getMessage());
            return back();
        }

        Toastr::success(
            'Next visit scheduled for ' . \Carbon\Carbon::parse($next->appointment_date)->format('d M Y')
                . '. The patient will get a WhatsApp reminder before it.'
        );

        return Redirect::route('vendor.opd.show', $visit->id);
    }

    public function quickUpdate(Request $request, $id)
    {
        $this->ensureClinicalSchema();
        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)->findOrFail($id);

        $request->validate([
            'chief_complaint' => 'nullable|string|max:500',
            'notes'           => 'nullable|string',
            'diagnosis'       => 'nullable|array',
            'diagnosis.*'     => 'string|max:150',
            'treatment'       => 'nullable|array',
            'treatment.*'     => 'string|max:150',
        ]);

        // The show page saves chief complaint, consultation notes and diagnosis/treatment
        // independently, sending only the changed field. Update only what was submitted so
        // the others aren't wiped.
        if ($request->has('chief_complaint')) {
            $visit->chief_complaint = $request->chief_complaint;
        }
        if ($request->has('notes')) {
            $visit->notes = $request->notes;
        }

        $saved = [];
        foreach (['diagnosis' => \App\Models\OpdClinicalTerm::TYPE_DIAGNOSIS,
                  'treatment' => \App\Models\OpdClinicalTerm::TYPE_TREATMENT] as $field => $type) {
            if (!$request->has($field)) {
                continue;
            }
            $terms = collect((array) $request->input($field, []))
                ->map(fn($term) => trim($term))
                ->filter()
                ->unique()
                ->values();

            \App\Models\OpdClinicalTerm::remember($store_id, $type, $terms->all());

            $visit->{$field} = $terms->isEmpty() ? null : $terms->implode(', ');
            $saved[$field]   = $terms->all();
        }

        $visit->save();

        // The casemix just changed. Dropped rather than recomputed — the next page load rebuilds
        // it, and a doctor who corrects a diagnosis should see that reflected on the next patient
        // instead of waiting out an hour of cache.
        if ($saved) {
            \App\Services\OpdTermInsights::forget($store_id);
        }

        return response()->json(['ok' => true] + $saved);
    }

    public function edit($id)
    {
        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)->findOrFail($id);
        $patients = Patient::where('store_id', $store_id)->where('status', 1)->orderBy('name')->get();
        $doctors  = DoctorProfile::where('store_id', $store_id)->with('employee')->get();

        return view('hmis::vendor.opd.edit', compact('visit', 'patients', 'doctors'));
    }

    public function update(Request $request, $id)
    {
        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)->findOrFail($id);

        $request->validate([
            'visit_type'       => 'required|in:' . implode(',', array_keys(OpdVisit::VISIT_TYPES)),
            'chief_complaint'  => 'nullable|string|max:500',
            'bp_systolic'      => 'nullable|integer|min:0|max:300',
            'bp_diastolic'     => 'nullable|integer|min:0|max:200',
            'temperature'      => 'nullable|numeric|min:90|max:110',
            'weight'           => 'nullable|numeric|min:0|max:500',
            'height'           => 'nullable|numeric|min:0|max:300',
            'spo2'             => 'nullable|integer|min:0|max:100',
            'pulse_rate'       => 'nullable|integer|min:0|max:300',
            'respiratory_rate' => 'nullable|integer|min:0|max:100',
            'notes'            => 'nullable|string',
        ]);

        $visit->update([
            'visit_type'        => $request->visit_type,
            'chief_complaint'   => $request->chief_complaint,
            'bp_systolic'       => $request->bp_systolic,
            'bp_diastolic'      => $request->bp_diastolic,
            'temperature'       => $request->temperature,
            'weight'            => $request->weight,
            'height'            => $request->height,
            'spo2'              => $request->spo2,
            'pulse_rate'        => $request->pulse_rate,
            'respiratory_rate'  => $request->respiratory_rate,
            'notes'             => $request->notes,
        ]);

        Toastr::success('Visit updated.');
        return Redirect::route('vendor.opd.show', $id);
    }
}
