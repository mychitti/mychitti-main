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
        // cancel and delete arrived later still: cancel marks a visit as not having happened,
        // delete removes a row created by mistake, and they are separate rows in the grid so a
        // receptionist can be trusted with the first without being handed the second.
        foreach (['edit', 'receipt_view', 'cancel', 'delete'] as $action) {
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
            foreach (['diagnosis', 'treatment', 'willing_treatment', 'treatment_plan'] as $column) {
                if (!Schema::hasColumn('opd_visits', $column)) {
                    DB::statement("ALTER TABLE `opd_visits` ADD COLUMN `{$column}` TEXT NULL AFTER `chief_complaint`");
                }
            }

            // What time the patient was actually seen. visit_date alone cannot order a walk-in
            // register beyond the token, and a hospital running two sittings a day needs to know
            // which one a visit belonged to.
            // How the visit is being paid for — cash, insurer, government scheme. Separate from
            // visit_type, which describes the clinical nature of the visit.
            if (!Schema::hasColumn('opd_visits', 'op_type')) {
                DB::statement("ALTER TABLE `opd_visits` ADD COLUMN `op_type` VARCHAR(100) NULL AFTER `visit_type`");
            }

            if (!Schema::hasColumn('opd_visits', 'visit_time')) {
                DB::statement("ALTER TABLE `opd_visits` ADD COLUMN `visit_time` TIME NULL AFTER `visit_date`");
            }

            // A cancelled visit keeps its row and its token. The register has to be able to show
            // that a token was issued and came to nothing — a deleted row would read as a gap in
            // the day's numbering that nobody can account for later.
            if (!Schema::hasColumn('opd_visits', 'cancelled_at')) {
                DB::statement("ALTER TABLE `opd_visits`
                    ADD COLUMN `cancelled_at` TIMESTAMP NULL,
                    ADD COLUMN `cancel_reason` VARCHAR(255) NULL,
                    ADD COLUMN `cancelled_by` BIGINT UNSIGNED NULL");
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

        // The dropdowns are read from the platform catalogue plus this store's own rows, so both
        // sides have to exist before a consultation screen asks for them.
        \App\Models\OpdClinicalTerm::ensureSchema();
        \App\Models\OpdTermCatalogue::ensureTable();
        \App\Models\OpdOpType::ensureSchema();
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
            ->orderByRaw("status = 'cancelled'")                // cancelled sink below everything
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

        // Cancelled visits are left out: the export is what a hospital reconciles its day against,
        // and a visit that did not happen has no place in that count.
        $visits = OpdVisit::where('store_id', $store_id)
            ->notCancelled()
            ->whereBetween('visit_date', [$from, $to])
            ->when($request->doctor, fn($q) => $q->where('doctor_profile_id', $request->doctor))
            ->when($request->search, function ($q) use ($request) {
                $q->whereHas('patient', fn($p) => $p->where('name', 'like', "%{$request->search}%")
                    ->orWhere('patient_uid', 'like', "%{$request->search}%"));
            })
            ->with(['patient', 'doctorProfile.employee'])
            ->orderBy('token_number')
            ->get();

        // A hospital that does not chart vitals gets three fewer columns rather than three empty ones.
        $withVitals = hmis_vitals_enabled($store_id);

        $headings = array_merge(
            ['Token', 'Visit Date', 'Visit Time', 'Patient', 'MUID', 'Doctor', 'Chief Complaint', 'Diagnosis', 'Treatment'],
            $withVitals ? ['BP', 'Temperature', 'Weight'] : [],
            ['Status']
        );
        $data = $visits->map(fn($v) => array_merge([
            $v->token_number,
            $v->visit_date,
            $v->visit_time ? \Carbon\Carbon::parse($v->visit_time)->format('h:i A') : '',
            $v->patient?->name,
            $v->patient?->patient_uid,
            'Dr. ' . trim(($v->doctorProfile?->employee?->f_name ?? '') . ' ' . ($v->doctorProfile?->employee?->l_name ?? '')),
            $v->chief_complaint,
            $v->diagnosis,
            $v->treatment,
        ], $withVitals ? [$v->bp, $v->temperature, $v->weight] : [], [
            ucfirst($v->status ?? ''),
        ]))->toArray();

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
                        // Only reachable when the booking is for someone else — the family case,
                        // where one number covers several patients. Matching the number alone
                        // picked whichever relative was registered first, so the visit landed on
                        // the wrong person's record. locatePatient() matches name before number.
                        $hmisPatient = \App\Services\LeadAppointmentService::locatePatient($store_id, $sr);
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

        $complaintOptions = \App\Models\OpdClinicalTerm::listFor($store_id, \App\Models\OpdClinicalTerm::TYPE_COMPLAINT);
        $complaintGroups  = \App\Models\OpdComplaintGroup::listFor($store_id);
        $opTypes          = \App\Models\OpdOpType::listFor($store_id);

        return view('hmis::vendor.opd.create', compact('patients', 'doctors', 'nextToken', 'prefillPatient', 'prefillBooking', 'complaintOptions', 'complaintGroups', 'opTypes'));
    }

    public function store(Request $request)
    {
        // create/store can be reached straight from a link without the register having been
        // opened first, so the columns this writes are provisioned here too.
        $this->ensureClinicalSchema();
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
            'visit_time'       => 'nullable|date_format:H:i',
            'op_type'          => 'nullable|string|max:100',
            'chief_complaint'   => 'nullable|array',
            'chief_complaint.*' => 'string|max:150',
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

        $visit = OpdVisit::create([
            'store_id'            => $store_id,
            'patient_id'          => $patientId,
            'doctor_profile_id'   => $doctorProfileId,
            'appointment_id'      => $appointmentId,
            'service_request_id'  => $request->booking_mode === 'booked' ? ($sr->id ?? null) : null,
            'visit_date'          => $visitDate,
            // A booked visit is registered when the patient reaches the desk, so "now" is the
            // honest answer there too — the booked slot time lives on the appointment, not here.
            'visit_time'          => $request->visit_time ?: now()->format('H:i'),
            'token_number'        => $request->token_number ?? $nextToken,
            'chief_complaint'     => \App\Models\OpdClinicalTerm::absorb($store_id, \App\Models\OpdClinicalTerm::TYPE_COMPLAINT, $request->chief_complaint),
            'op_type'             => $request->op_type ?: null,
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

        // The patient's slip — token, doctor and a link to the visit. auto() is a no-op unless the
        // hospital has switched "Visit registered" on, and it dedupes on (store, kind, visit) so
        // re-saving the same visit cannot message the patient twice.
        \App\Services\HmisWhatsAppShare::auto('visit_registered', (int) $store_id, (int) $visit->id,
            fn() => \App\Services\HmisWhatsAppShare::visitRegistered($visit));

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
            $store_id, 'opd_visit', (int) $visit->id, 'created',
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

        $complaintOptions = \App\Models\OpdClinicalTerm::listFor($store_id, \App\Models\OpdClinicalTerm::TYPE_COMPLAINT);
        $diagnosisOptions = \App\Models\OpdClinicalTerm::listFor($store_id, \App\Models\OpdClinicalTerm::TYPE_DIAGNOSIS);
        $treatmentOptions = \App\Models\OpdClinicalTerm::listFor($store_id, \App\Models\OpdClinicalTerm::TYPE_TREATMENT);
        // What this hospital last charged for each advised treatment, so the amount box opens
        // with a figure rather than empty.
        $treatmentPrices  = \App\Models\OpdTreatmentPrice::mapFor($store_id, $visit->treatment_list);

        // The sets this hospital keeps recording together, one tap each.
        $complaintGroups = \App\Models\OpdComplaintGroup::listFor($store_id);

        // The same for consultation notes: the phrases this hospital's doctors reuse.
        $noteTemplates = \App\Models\OpdNoteTemplate::listFor($store_id);

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

        // The follow-ups the treatment plan is booked into, so a sitting on the plan and the
        // appointment it was booked as read as one thing on both the chip and the Next Visit tab.
        $treatmentAppointments = $this->treatmentAppointments($store_id, $visit->treatment_plan_map);

        // Lab work for this patient, in-house or out at a lab — every job, not only this visit's,
        // because a crown ordered three weeks ago is exactly what the doctor needs in front of
        // them today.
        $labWorkEnabled = hmis_lab_work_enabled($store_id);
        $labWorkProfile = \App\Models\OpdLabWork::profileFor($store_id);
        $labWorks         = collect();
        $labVendors       = collect();
        $labWorkHandovers = collect();
        // Initialised with the rest of them: it is passed to compact() unconditionally, so a
        // hospital with lab work switched off was raising "Undefined variable $labTechnicians"
        // there — which Laravel turns into an exception, taking the whole consultation screen
        // down for exactly the hospitals that never asked for the feature.
        $labTechnicians   = collect();

        if ($labWorkEnabled) {
            \App\Models\OpdLabWork::ensureSchema();
            $labWorks = \App\Models\OpdLabWork::where('store_id', $store_id)
                ->where('patient_id', $visit->patient_id)
                ->orderByRaw("FIELD(status, 'cancelled', 'fitted') ASC")
                ->orderByDesc('created_at')
                ->get();

            // The labs this clinic already deals with, out of the same address book it invoices
            // them from — picking one fills the name, number and address in rather than leaving
            // staff to retype a number that has to be right for the job to reach anybody.
            \App\Models\StoreCustomer::ensureLabTypeColumn();
            $labVendors = \App\Models\StoreCustomer::where('store_id', $store_id)
                ->where('user_type', 'vendor')
                ->orderBy('f_name')
                ->get(['id', 'f_name', 'phone', 'address', 'lab_type']);

            // Who can be put against an in-house job. Active staff only — a bench job cannot be
            // opened against somebody who has left, and the leavers are exactly the names that
            // would otherwise pile up at the bottom of the list forever.
            $labTechnicians = \App\Models\VendorEmployee::where('store_id', $store_id)
                ->where('status', 1)
                ->orderBy('f_name')
                ->get(['id', 'f_name', 'l_name', 'phone']);

            // Who physically carried each job in or out, keyed by job so the card can show its own
            // chain without a query per row. Drafts are excluded by the happened_at filter: a
            // verification somebody started and abandoned is not an exchange that took place, and
            // showing it as one would put a name against a handover that never happened.
            \App\Models\HmisHandover::ensureSchema();
            $labWorkHandovers = \App\Models\HmisHandover::where('store_id', $store_id)
                ->where('subject_type', 'opd_lab_work')
                ->whereIn('subject_id', $labWorks->pluck('id'))
                ->whereNotNull('happened_at')
                ->orderByDesc('happened_at')
                ->get()
                ->groupBy('subject_id');
        }

        // The access trail behind the Security tab. Only hospitals that switched the tab on keep
        // one, so this both writes the "opened" row and reads the history back — a store with the
        // setting off does neither and pays nothing for a tab it never shows.
        $securityEnabled = hmis_security_tab_enabled($store_id);
        $securityLog     = collect();

        if ($securityEnabled) {
            \App\Models\HospitalActivityLog::recordOnce(
                $store_id, 'opd_visit', (int) $visit->id, 'viewed',
                "Consultation record opened for patient #{$visit->patient_id} (token #{$visit->token_number})",
                ['patient_id' => $visit->patient_id]
            );

            $securityLog = $this->patientAccessTrail($store_id, (int) $visit->patient_id);
        }

        return view('hmis::vendor.opd.show', compact(
            'visit', 'pastVisits', 'currentPrescription', 'pastPrescriptions',
            'labTests', 'radiologyTests', 'labOrders', 'radiologyStudies',
            'complaintOptions', 'complaintGroups', 'noteTemplates',
            'diagnosisOptions', 'treatmentOptions', 'upcomingVisits', 'termInsights', 'treatmentPrices',
            'treatmentAppointments', 'securityEnabled', 'securityLog',
            'labWorkEnabled', 'labWorkProfile', 'labWorks', 'labVendors', 'labWorkHandovers', 'labTechnicians'
        ));
    }  

    /**
     * Everything the hospital has logged that touches one patient's records.
     *
     * The activity log is keyed by subject type and id, not by patient, so the patient's own
     * records are resolved first and the log matched against those id sets. Matching on the ids
     * rather than reading `properties` keeps this exact — `properties` is only written by some
     * callers, and searching inside it would miss the rest and match the wrong rows besides.
     */
    private function patientAccessTrail(int $storeId, int $patientId, int $limit = 100)
    {
        $subjects = [
            'patient'       => [$patientId],
            'opd_visit'     => OpdVisit::where('store_id', $storeId)
                                    ->where('patient_id', $patientId)->pluck('id')->all(),
            'appointment'   => \App\Models\Appointment::where('store_id', $storeId)
                                    ->where('patient_id', $patientId)->pluck('id')->all(),
            'prescription'  => Prescription::where('store_id', $storeId)
                                    ->where('patient_id', $patientId)->pluck('id')->all(),
            'ipd_admission' => Schema::hasTable('ipd_admissions')
                                ? \App\Models\IpdAdmission::where('store_id', $storeId)
                                    ->where('patient_id', $patientId)->pluck('id')->all()
                                : [],
            'opd_lab_work'  => Schema::hasTable('opd_lab_works')
                                ? \App\Models\OpdLabWork::where('store_id', $storeId)
                                    ->where('patient_id', $patientId)->pluck('id')->all()
                                : [],
        ];

        try {
            return \App\Models\HospitalActivityLog::where('store_id', $storeId)
                ->where(function ($q) use ($subjects) {
                    foreach ($subjects as $type => $ids) {
                        if ($ids) {
                            $q->orWhere(fn($w) => $w->where('subject_type', $type)->whereIn('subject_id', $ids));
                        }
                    }
                })
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();
        } catch (\Throwable $e) {
            // The tab is a read-only extra. It must never be the reason a consultation won't open.
            return collect();
        }
    }

    /**
     * Schedule the patient's next visit — or several of them — straight from the consultation
     * screen, so the doctor never has to leave the encounter to book a follow-up.
     *
     * A course of treatment is rarely one follow-up: a dressing on Friday, the second sitting a
     * fortnight later, a review after that. The form therefore posts a list, and each row may
     * name the advised treatments it is for. Those treatments are marked upcoming against that
     * date and remember the appointment they were booked into, which is what makes the plan and
     * the appointment book the same fact rather than two dates that can drift apart.
     */
    public function nextVisit(Request $request, $id)
    {
        if (!auth('vendor')->check() && !hasPermission('opd_register', 'edit')) abort(403);

        // The screen posts visits[]; a single-row payload is still accepted so an older tab left
        // open, or a link built by hand, books one follow-up rather than failing validation.
        if (!$request->has('visits') && $request->filled('appointment_date')) {
            $request->merge(['visits' => [$request->only(['appointment_date', 'appointment_time', 'slot_id', 'reason'])]]);
        }

        $request->validate([
            'visits'                      => 'required|array|min:1|max:12',
            'visits.*.appointment_date'   => 'required|date|after_or_equal:today',
            'visits.*.appointment_time'   => 'required|date_format:H:i,H:i:s',
            'visits.*.slot_id'            => 'nullable|integer|exists:doctor_slots,id',
            'visits.*.reason'             => 'nullable|string|max:500',
            'visits.*.treatments'         => 'nullable|array',
            'visits.*.treatments.*'       => 'string|max:150',
        ]);

        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)->findOrFail($id);

        if (!$visit->doctor_profile_id) {
            Toastr::error('This visit has no doctor assigned, so a follow-up cannot be booked.');
            return back();
        }

        $advised = $visit->treatment_list;
        // A closed visit is a document — the receipt was printed from its plan. The booking is a
        // new record of its own and still goes through, but nothing is written back onto the
        // treatment plan, so the patient's copy keeps saying what it said.
        $canLink = $visit->is_editable;
        $plan    = collect($visit->treatment_plan_map);

        $booked = [];
        $failed = [];
        $linked = 0;

        foreach ($request->input('visits', []) as $row) {
            $terms = collect($row['treatments'] ?? [])
                ->map(fn($term) => trim((string) $term))
                ->filter()
                ->unique()
                ->intersect($advised)
                ->values();

            // "Come Friday for the scaling" reads better on the desk's list than "Follow-up
            // visit", and it is what the doctor already typed by picking the treatments.
            $reason = trim((string) ($row['reason'] ?? '')) ?: ($terms->isNotEmpty() ? $terms->implode(', ') : null);

            try {
                $next = \App\Services\NextVisitService::schedule(
                    (int) $store_id,
                    (int) $visit->patient_id,
                    (int) $visit->doctor_profile_id,
                    $row['appointment_date'],
                    $row['appointment_time'],
                    !empty($row['slot_id']) ? (int) $row['slot_id'] : null,
                    $reason,
                    ['from_opd_visit_id' => (int) $visit->id, 'treatments' => $terms->all()]
                );
            } catch (\Throwable $e) {
                $failed[] = \Carbon\Carbon::parse($row['appointment_date'])->format('d M') . ' — ' . $e->getMessage();
                continue;
            }

            $booked[] = $next;

            if (!$canLink) {
                continue;
            }

            foreach ($terms as $term) {
                $current = (array) ($plan[$term] ?? []);
                // Already done is not rescheduled by booking the sitting that follows it.
                if (($current['status'] ?? 'pending') === 'completed') {
                    continue;
                }

                $plan[$term] = $current + [
                    'amount'   => null,
                    'discount' => null,
                    'paid'     => false,
                ];
                $plan[$term] = array_merge($plan[$term], [
                    'status'         => 'upcoming',
                    'date'           => $row['appointment_date'],
                    'time'           => substr((string) $row['appointment_time'], 0, 5),
                    'appointment_id' => (int) $next->id,
                ]);
                $linked++;
            }
        }

        if ($canLink && $linked) {
            // Both lists, for the reason quickUpdate prunes against both: the plan follows what
            // the patient accepted, so pruning to the advised list alone would strip the booking
            // that was just made against a willing treatment.
            $plan = $plan->only(collect($advised)->merge($visit->willing_treatment_list)->unique()->values()->all());
            $visit->treatment_plan = $plan->isEmpty() ? null : json_encode($plan->all());
            $visit->save();
        }

        if ($booked) {
            $when = collect($booked)
                ->map(fn($appointment) => \Carbon\Carbon::parse($appointment->appointment_date)->format('d M Y'))
                ->implode(', ');

            Toastr::success(
                (count($booked) === 1 ? 'Next visit scheduled for ' : count($booked) . ' next visits scheduled — ') . $when
                    . ($linked ? ', with ' . $linked . ' treatment' . ($linked === 1 ? '' : 's') . ' booked in' : '')
                    . '. The patient will get a WhatsApp reminder before ' . (count($booked) === 1 ? 'it' : 'each') . '.'
            );
        }

        foreach ($failed as $message) {
            Toastr::error($message);
        }

        if (!$booked) {
            return back();
        }

        return Redirect::route('vendor.opd.show', $visit->id);
    }

    /**
     * Save the complaints currently selected as a named group.
     *
     * Re-using a name overwrites that group rather than erroring: "save as Diabetes screen" twice
     * is a doctor refining the set, not a mistake to be scolded for.
     */
    public function complaintGroupStore(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:100',
            'terms'   => 'required|array|min:1',
            'terms.*' => 'string|max:150',
        ]);

        $store_id = Helpers::get_store_id();
        $terms    = collect($request->input('terms', []))
            ->map(fn($term) => trim($term))->filter()->unique()->values();

        if ($terms->isEmpty()) {
            return response()->json(['ok' => false, 'msg' => 'Pick at least one complaint first.'], 422);
        }

        \App\Models\OpdComplaintGroup::ensureSchema();

        // Anything the doctor typed by hand joins this store's complaint list too, so a group
        // cannot hold a term the dropdown has never heard of.
        \App\Models\OpdClinicalTerm::remember($store_id, \App\Models\OpdClinicalTerm::TYPE_COMPLAINT, $terms->all());

        $group = \App\Models\OpdComplaintGroup::updateOrCreate(
            ['store_id' => $store_id, 'name' => trim($request->name)],
            ['terms' => $terms->implode(', '), 'created_by' => auth('vendor_employee')->id() ?? auth('vendor')->id()]
        );

        return response()->json([
            'ok'    => true,
            'group' => ['id' => $group->id, 'name' => $group->name, 'terms' => $group->term_list],
        ]);
    }

    public function complaintGroupDestroy(Request $request, $id)
    {
        \App\Models\OpdComplaintGroup::ensureSchema();

        \App\Models\OpdComplaintGroup::forStore(Helpers::get_store_id())->where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Add a consultation-note phrase to this store's picker. Typed phrases are absorbed on save
     * by quickUpdate, so this exists for adding one deliberately without recording a visit.
     */
    public function noteTemplateStore(Request $request)
    {
        $request->validate(['name' => 'required|string|max:190']);

        $phrase   = trim((string) $request->input('name'));
        $store_id = Helpers::get_store_id();

        if ($phrase === '') {
            return response()->json(['ok' => false, 'msg' => 'Type the phrase first.'], 422);
        }

        \App\Models\OpdNoteTemplate::remember($store_id, [$phrase]);

        $saved = \App\Models\OpdNoteTemplate::forStore($store_id)->where('name', $phrase)->first();

        return response()->json([
            'ok'       => true,
            'template' => $saved ? ['id' => $saved->id, 'name' => $saved->name] : null,
        ]);
    }

    public function noteTemplateDestroy(Request $request, $id)
    {
        \App\Models\OpdNoteTemplate::ensureSchema();

        \App\Models\OpdNoteTemplate::forStore(Helpers::get_store_id())->where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }

    public function quickUpdate(Request $request, $id)
    {
        $this->ensureClinicalSchema();
        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)->findOrFail($id);

        // A completed or cancelled visit is a closed record. The screen hides its edit controls,
        // but that is presentation only — a tab left open before the receipt was generated would
        // still autosave into it, so the refusal has to live here.
        if (!$visit->is_editable) {
            return response()->json([
                'ok'  => false,
                'msg' => $visit->is_cancelled
                    ? 'This visit was cancelled and can no longer be edited.'
                    : 'This visit is completed. Reopen it before making changes.',
            ], 422);
        }

        $request->validate([
            'chief_complaint' => 'nullable|string|max:500',
            'notes'           => 'nullable|string',
            'complaint'       => 'nullable|array',
            'complaint.*'     => 'string|max:150',
            'diagnosis'       => 'nullable|array',
            'diagnosis.*'     => 'string|max:150',
            'treatment'       => 'nullable|array',
            'treatment.*'     => 'string|max:150',
            'willing_treatment'   => 'nullable|array',
            'willing_treatment.*' => 'string|max:150',
            'treatment_plan'             => 'nullable|array',
            'treatment_plan.*.status'    => 'required|in:pending,upcoming,in_progress,completed',
            'treatment_plan.*.date'      => 'nullable|date',
            'treatment_plan.*.time'      => 'nullable|date_format:H:i',
            'treatment_plan.*.amount'    => 'nullable|numeric|min:0|max:99999999',
            'treatment_plan.*.discount'  => 'nullable|numeric|min:0|max:99999999',
            'treatment_plan.*.paid'      => 'nullable|boolean',
            'treatment_plan.*.book'      => 'nullable|boolean',
            'note_terms'      => 'nullable|array',
            'note_terms.*'    => 'string|max:190',
            'bp_systolic'      => 'nullable|integer|min:0|max:300',
            'bp_diastolic'     => 'nullable|integer|min:0|max:200',
            'temperature'      => 'nullable|numeric|min:90|max:110',
            'weight'           => 'nullable|numeric|min:0|max:500',
            'height'           => 'nullable|numeric|min:0|max:300',
            'spo2'             => 'nullable|integer|min:0|max:100',
            'pulse_rate'       => 'nullable|integer|min:0|max:300',
            'respiratory_rate' => 'nullable|integer|min:0|max:100',
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

        // Consultation notes are a chip list now, held in the same column and split back by
        // OpdVisit::splitTerms() — so notes written as free text before this still read back as
        // one entry rather than disappearing.
        if ($request->has('note_terms')) {
            $phrases = collect($request->input('note_terms', []))
                ->map(fn($phrase) => trim((string) $phrase))
                ->filter()
                ->unique()
                ->values();

            // Anything typed by hand joins this store's list, so the picker learns it.
            \App\Models\OpdNoteTemplate::remember($store_id, $phrases->all());

            $visit->notes = $phrases->implode(', ');
        }

        $saved = [];
        // `complaint` writes to chief_complaint — the column it has always lived in, now holding a
        // comma-separated list rather than a sentence, so old free text still reads back as one entry.
        foreach (['complaint' => \App\Models\OpdClinicalTerm::TYPE_COMPLAINT,
                  'diagnosis' => \App\Models\OpdClinicalTerm::TYPE_DIAGNOSIS,
                  'treatment' => \App\Models\OpdClinicalTerm::TYPE_TREATMENT,
                  'willing_treatment' => \App\Models\OpdClinicalTerm::TYPE_TREATMENT] as $field => $type) {
            if (!$request->has($field)) {
                continue;
            }
            $terms = collect((array) $request->input($field, []))
                ->map(fn($term) => trim($term))
                ->filter()
                ->unique()
                ->values();

            \App\Models\OpdClinicalTerm::remember($store_id, $type, $terms->all());

            $column = $field === 'complaint' ? 'chief_complaint' : $field;
            $visit->{$column} = $terms->isEmpty() ? null : $terms->implode(', ');
            $saved[$field]    = $terms->all();

            // Editing either list can drop a term that carried a schedule and a price. The plan
            // is keyed on whichever list drives it — willing once the patient has chosen — so it
            // is pruned against both: keeping only the advised terms would delete the schedule
            // and price of anything the patient accepted that was never on the advised list.
            if ($field === 'treatment' || $field === 'willing_treatment') {
                $keep = collect($visit->treatment_list)->merge($visit->willing_treatment_list)->unique()->values();
                $plan = collect($visit->treatment_plan_map)->only($keep->all());
                $visit->treatment_plan   = $plan->isEmpty() ? null : json_encode($plan->all());
                $saved['treatment_plan'] = $plan->all();
            }
        }

        // Vitals, edited in place on the Details tab. Sent only when that card is saved, and a
        // box left empty means "not recorded" rather than "leave as it was" — so an emptied
        // field clears the column instead of keeping a reading the nurse just deleted.
        foreach (['bp_systolic', 'bp_diastolic', 'temperature', 'weight',
                  'height', 'spo2', 'pulse_rate', 'respiratory_rate'] as $vital) {
            if ($request->has($vital)) {
                $value = $request->input($vital);
                $visit->{$vital} = ($value === '' || $value === null) ? null : $value;
            }
        }

        // One chip is scheduled at a time from the Diagnosis & Treatment card, so the incoming
        // rows are merged onto what is already planned rather than replacing it. Terms no longer
        // advised are dropped: a plan for a treatment that is no longer offered is noise.
        if ($request->has('treatment_plan')) {
            // Both lists, as everywhere else the plan is trimmed: it follows what the patient
            // accepted, which need not still be on the advised list.
            $advised = collect($visit->treatment_list)->merge($visit->willing_treatment_list)->unique()->values()->all();
            $plan    = collect($visit->treatment_plan_map);
            $notes   = [];

            foreach ($request->input('treatment_plan', []) as $term => $row) {
                // A term the visit no longer advises is dropped by the trim below anyway, so it
                // is skipped here — booking a follow-up for it would leave an appointment behind
                // that nothing on the plan points at.
                if (!in_array($term, $advised, true)) {
                    continue;
                }

                // The appointment a sitting is booked into is never taken from the request — it
                // is what the visit already knows, moved on by the booking sync below. Otherwise
                // a stale tab could re-point a treatment at somebody else's appointment.
                $existing      = (array) ($plan[$term] ?? []);
                $appointmentId = $this->syncTreatmentBooking($visit, $term, $row, $existing, $plan, $notes);

                // Money is no longer edited on the OPD screen, so its inputs simply are not sent.
                // An absent key therefore means "leave alone", not "clear" — clearing would wipe
                // the figures Billing reads off the plan and blank the paid flag that keeps a
                // settled treatment off the next bill.
                $hasAmount   = array_key_exists('amount', $row)   && $row['amount']   !== '';
                $hasDiscount = array_key_exists('discount', $row) && $row['discount'] !== '';

                $amount   = $hasAmount   ? (float) $row['amount']   : ($existing['amount']   ?? null);
                $discount = $hasDiscount ? (float) $row['discount'] : ($existing['discount'] ?? null);

                $plan[$term] = [
                    'status'         => $row['status'],
                    'date'           => ($row['date'] ?? '') ?: null,
                    'time'           => ($row['time'] ?? '') ?: null,
                    'amount'         => $amount,
                    'discount'       => $discount,
                    'paid'           => array_key_exists('paid', $row)
                        ? (bool) $row['paid']
                        : (bool) ($existing['paid'] ?? false),
                    'appointment_id' => $appointmentId,
                ];

                // Whatever this hospital charged for the treatment becomes what it is offered
                // next time — there is no price list to read one from. Only when a price was
                // actually submitted: remembering the carried-over value would keep rewriting
                // the catalogue with a figure nobody just typed.
                if ($hasAmount || $hasDiscount) {
                    \App\Models\OpdTreatmentPrice::remember($store_id, $term, $amount, $discount);
                }
            }

            $plan = $plan->only($advised);
            $visit->treatment_plan = $plan->isEmpty() ? null : json_encode($plan->all());
            $saved['treatment_plan']   = $plan->all();
            $saved['treatment_prices'] = \App\Models\OpdTreatmentPrice::mapFor($store_id, $advised);
            $saved['treatment_appointments'] = $this->treatmentAppointments($store_id, $plan->all());

            // Anything the booking could not do — no time given, slot full — is reported without
            // failing the save: the price and status the doctor just set still belong on the record.
            if ($notes) {
                $saved['notice'] = implode(' ', $notes);
            }
        }

        $visit->save();

        // The casemix just changed. Dropped rather than recomputed — the next page load rebuilds
        // it, and a doctor who corrects a diagnosis should see that reflected on the next patient
        // instead of waiting out an hour of cache.
        if ($saved) {
            \App\Services\OpdTermInsights::forget($store_id);
        }

        // Same trail the Security tab reads, and only for hospitals that asked for one. This is
        // an autosave — it fires on every debounce — so it is deliberately coalesced: one "edited"
        // row per person per window, not one per keystroke.
        if (hmis_security_tab_enabled($store_id)) {
            \App\Models\HospitalActivityLog::recordOnce(
                $store_id, 'opd_visit', (int) $visit->id, 'edited',
                "Consultation record edited for patient #{$visit->patient_id} (token #{$visit->token_number})",
                ['patient_id' => $visit->patient_id]
            );
        }

        return response()->json(['ok' => true] + $saved);
    }

    /**
     * Keep one treatment's sitting and its follow-up appointment saying the same thing.
     *
     * The date on a treatment is only a note until someone books it; once booked it is an
     * appointment the desk works from, so moving the treatment has to move the appointment
     * rather than leaving the two to disagree. Nothing here is allowed to fail the save — the
     * status and the price the doctor just set belong on the record either way, so a booking
     * that cannot be made comes back as a note instead of an error.
     *
     * @param  \Illuminate\Support\Collection  $plan   the plan as it stands before this row
     * @param  array  $notes  anything the doctor needs telling, appended to
     * @return int|null  the appointment this treatment is now booked into
     */
    private function syncTreatmentBooking(OpdVisit $visit, string $term, array $row, array $existing, $plan, array &$notes): ?int
    {
        $appointmentId = ((int) ($existing['appointment_id'] ?? 0)) ?: null;
        $appointment   = $appointmentId
            ? \App\Models\Appointment::where('store_id', $visit->store_id)->find($appointmentId)
            : null;

        // Cancelled or missed at the desk: that booking is no longer this treatment's booking.
        if ($appointment && in_array($appointment->status, ['cancelled', 'no_show'])) {
            $appointment   = null;
            $appointmentId = null;
        }

        // The row wasn't sent by something that manages bookings — leave the link as it is.
        if (!array_key_exists('book', $row)) {
            return $appointmentId;
        }

        // The other sittings riding on the same follow-up. One appointment usually covers several
        // ("come Friday, we'll do the scaling and the filling"), which decides both what unticking
        // is allowed to cancel and whether moving this one may move the appointment itself.
        $shared = $appointment
            ? collect($plan)->filter(
                fn($other, $otherTerm) => $otherTerm !== $term
                    && (int) ($other['appointment_id'] ?? 0) === (int) $appointment->id
            )
            : collect();

        if (!$row['book']) {
            // Unticked. Called off only when this was the last treatment riding on it.
            if ($appointment && $shared->isEmpty() && $appointment->status === 'scheduled') {
                $appointment->status        = 'cancelled';
                $appointment->cancel_reason = 'Treatment no longer booked';
                $appointment->save();
                $notes[] = 'The follow-up booked for "' . $term . '" was cancelled.';
            }

            return null;
        }

        $date = $row['date'] ?? null;
        $time = !empty($row['time']) ? substr((string) $row['time'], 0, 5) : null;

        if (($row['status'] ?? 'pending') === 'completed') {
            return $appointmentId;
        }

        if (!$visit->doctor_profile_id) {
            $notes[] = 'This visit has no doctor assigned, so "' . $term . '" could not be booked.';
            return $appointmentId;
        }

        if (!$date || !$time) {
            $notes[] = 'Set a date and time to book "' . $term . '" as a next visit.';
            return $appointmentId;
        }

        // Yesterday is not bookable; later today is — a sitting after lunch is an ordinary thing
        // to write down at the chair in the morning.
        if (\Carbon\Carbon::parse($date)->startOfDay()->lt(\Carbon\Carbon::today())) {
            $notes[] = '"' . $term . '" is dated in the past, so no follow-up was booked for it.';
            return $appointmentId;
        }

        try {
            if ($appointment) {
                $sameDay  = \Carbon\Carbon::parse($appointment->appointment_date)->format('Y-m-d') === $date;
                $sameTime = substr((string) $appointment->appointment_time, 0, 5) === $time;

                if ($sameDay && $sameTime) {
                    return (int) $appointment->id;
                }

                // Moving a sitting that shares its follow-up with others moves only itself: the
                // rest of that appointment is still expected on the day it was booked for, and
                // dragging them along because one filling was put off would be news to them.
                if ($shared->isEmpty()) {
                    $moved = \App\Services\NextVisitService::reschedule(
                        $appointment, $date, $time, $appointment->slot_id ? (int) $appointment->slot_id : null,
                        ['from_opd_visit_id' => (int) $visit->id, 'treatments' => [$term]]
                    );

                    return (int) $moved->id;
                }
            }

            $next = \App\Services\NextVisitService::schedule(
                (int) $visit->store_id,
                (int) $visit->patient_id,
                (int) $visit->doctor_profile_id,
                $date,
                $time,
                null,
                $term,
                ['from_opd_visit_id' => (int) $visit->id, 'treatments' => [$term]]
            );

            return (int) $next->id;
        } catch (\Throwable $e) {
            $notes[] = 'Could not book "' . $term . '": ' . $e->getMessage();
            return $appointmentId;
        }
    }

    /** The follow-ups a treatment plan points at, as the consultation screen needs to show them. */
    private function treatmentAppointments(int $storeId, array $plan): array
    {
        $ids = collect($plan)->pluck('appointment_id')->filter()->map(fn($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return \App\Models\Appointment::where('store_id', $storeId)
            ->whereIn('id', $ids)
            ->with('token')
            ->get()
            ->mapWithKeys(fn($appointment) => [$appointment->id => [
                'id'     => (int) $appointment->id,
                'date'   => \Carbon\Carbon::parse($appointment->appointment_date)->format('Y-m-d'),
                'time'   => substr((string) $appointment->appointment_time, 0, 5),
                'token'  => $appointment->token?->token_number,
                'status' => $appointment->status,
                'url'    => route('vendor.appointment.show', $appointment->id),
            ]])
            ->all();
    }

    /**
     * What this hospital charges for each treatment. Rows arrive two ways: typed here, or
     * created the first time someone puts a price against that treatment on a visit. Either way
     * the consultation screen reads the price from here.
     */
    public function treatmentCatalog(Request $request)
    {
        \App\Models\OpdTreatmentPrice::ensureSchema();

        $store_id = Helpers::get_store_id();

        $treatments = \App\Models\OpdTreatmentPrice::forStore($store_id)
            ->when($request->search, fn($q) => $q->where('term', 'like', "%{$request->search}%"))
            ->orderBy('term')
            ->get();

        // The same list the consultation's Advised Treatment box offers, so the two can never
        // drift apart. What is already priced is left out — those rows are edited in the table.
        // Unfiltered on purpose: with a search active $treatments holds only the matches, and a
        // term priced outside the filter would be offered again and then rejected as a duplicate.
        $priced = \App\Models\OpdTreatmentPrice::forStore($store_id)->pluck('term_key')->all();
        $treatmentOptions = collect(\App\Models\OpdClinicalTerm::listFor($store_id, \App\Models\OpdClinicalTerm::TYPE_TREATMENT))
            ->reject(fn($term) => in_array(\App\Models\OpdTreatmentPrice::key($term), $priced, true))
            ->values();

        return view('hmis::vendor.opd.treatment_catalog', compact('treatments', 'treatmentOptions'));
    }

    public function treatmentCatalogSave(Request $request, $id = null)
    {
        $request->validate([
            'term'     => 'required|string|max:190',
            'amount'   => 'required|numeric|min:0|max:99999999',
            'discount' => 'nullable|numeric|min:0|max:99999999',
        ]);

        \App\Models\OpdTreatmentPrice::ensureSchema();

        $store_id = Helpers::get_store_id();
        $term     = trim($request->term);
        $key      = \App\Models\OpdTreatmentPrice::key($term);

        // The key is unique per store, so renaming a row onto a name that already exists would
        // otherwise fail on the index rather than telling anyone why.
        $clash = \App\Models\OpdTreatmentPrice::forStore($store_id)->where('term_key', $key)
            ->when($id, fn($q) => $q->where('id', '!=', $id))->exists();

        if ($clash) {
            Toastr::error($term . ' is already in the catalog.');
            return back();
        }

        $row = $id
            ? \App\Models\OpdTreatmentPrice::forStore($store_id)->findOrFail($id)
            : new \App\Models\OpdTreatmentPrice(['store_id' => $store_id]);

        $row->fill([
            'store_id'  => $store_id,
            'term_key'  => $key,
            'term'      => $term,
            'amount'    => $request->amount,
            'discount'  => $request->discount === null || $request->discount === '' ? null : $request->discount,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ])->save();

        // A priced treatment should also be offerable in the consultation dropdown.
        \App\Models\OpdClinicalTerm::remember($store_id, \App\Models\OpdClinicalTerm::TYPE_TREATMENT, [$term]);

        Toastr::success($id ? 'Treatment price updated.' : 'Treatment added to the catalog.');
        return redirect()->route('vendor.opd.treatment-catalog');
    }

    public function treatmentCatalogDelete($id)
    {
        \App\Models\OpdTreatmentPrice::forStore(Helpers::get_store_id())->where('id', $id)->delete();

        Toastr::success('Removed from the catalog.');
        return back();
    }

    /**
     * The register's filters, carried on the cancel/delete form action and handed back on the
     * redirect. Whitelisted rather than passing $request->query() straight through, so nothing
     * a URL happens to carry gets reflected back into the redirect.
     *
     * Not left to back(): that reads the Referer header, which is missing whenever the browser
     * or a proxy strips it, and the receptionist lands on today's unfiltered list instead of
     * the range they were working in.
     */
    private function registerFilters(Request $request): array
    {
        return array_filter(
            $request->only(['date_range', 'custom_date_range', 'doctor', 'search', 'scope', 'page']),
            fn($value) => $value !== null && $value !== ''
        );
    }

    /**
     * Mark a visit as not having happened, keeping the row.
     *
     * The token stays issued, the reason is recorded and the visit drops out of every count.
     * Allowed even once a fee has been collected — a patient who paid and then left still did
     * not have the consultation, and the receipt stays where it is to be refunded on its own
     * terms rather than vanishing with the visit.
     */
    public function cancel(Request $request, $id)
    {
        if (!auth('vendor')->check() && !hasPermission('opd_register', 'cancel')) abort(403);
        $this->ensureClinicalSchema();

        $request->validate(['cancel_reason' => 'required|string|max:255']);

        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)->findOrFail($id);

        if ($visit->is_cancelled) {
            Toastr::info('This visit is already cancelled.');
            return Redirect::route('vendor.opd.index', $this->registerFilters($request));
        }

        DB::transaction(function () use ($visit, $request, $store_id) {
            $visit->update([
                'status'        => OpdVisit::STATUS_CANCELLED,
                'cancel_reason' => $request->cancel_reason,
                'cancelled_at'  => now(),
                'cancelled_by'  => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            ]);

            // The booking behind the visit goes with it, so the doctor's slot is freed and the
            // appointment list stops showing a patient who is not coming. Only a live booking is
            // touched: a completed or already-cancelled one is history, not a reservation, and
            // Appointment::STATUS_TRANSITIONS allows 'cancelled' from these two states only.
            if ($visit->appointment_id) {
                \App\Models\Appointment::where('id', $visit->appointment_id)
                    ->where('store_id', $store_id)
                    ->whereIn('status', ['scheduled', 'checked_in'])
                    ->update(['status' => 'cancelled']);
            }
        });

        \App\Models\HospitalActivityLog::record(
            $store_id, 'opd_visit', (int) $visit->id, 'cancelled',
            "OPD visit token #{$visit->token_number} cancelled — {$request->cancel_reason}",
            ['patient_id' => $visit->patient_id, 'reason' => $request->cancel_reason]
        );

        Toastr::success('Visit cancelled.');
        return Redirect::route('vendor.opd.index', $this->registerFilters($request));
    }

    /**
     * Everything a visit can leave behind, and what to call it when it blocks a delete.
     *
     * Each entry is [label, closure returning a count]. A visit with any of these is history —
     * clinical or financial — and is cancelled rather than deleted, the same rule
     * PatientController::destroy applies to a patient with any record against them. Hard delete
     * exists only for a row created by mistake and never used.
     */
    private function visitAttachments(OpdVisit $visit, int $storeId): array
    {
        $blocking = [];

        $receipts = $visit->consultation_receipt_id ? 1 : 0;
        if (Schema::hasTable('opd_consultation_receipts')) {
            $receipts = max($receipts, DB::table('opd_consultation_receipts')
                ->where('opd_visit_id', $visit->id)->count());
        }
        if ($receipts) {
            $blocking[] = $receipts . ' consultation receipt' . ($receipts === 1 ? '' : 's');
        }

        // Same matching the consultation screen uses to find this visit's prescription, so what
        // blocks the delete is exactly what the user can see attached to the visit.
        $prescriptions = Prescription::where('store_id', $storeId)
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
            ->count();
        if ($prescriptions) {
            $blocking[] = $prescriptions . ' prescription' . ($prescriptions === 1 ? '' : 's');
        }

        // Lab and Radiology build their tables lazily, so both reads are guarded — a hospital
        // that has never opened those modules must still be able to delete a stray visit.
        if (Schema::hasTable('lab_orders')) {
            $labOrders = DB::table('lab_orders')
                ->where('store_id', $storeId)
                ->where('opd_id', $visit->id)
                ->count();
            if ($labOrders) {
                $blocking[] = $labOrders . ' lab order' . ($labOrders === 1 ? '' : 's');
            }
        }

        if (Schema::hasTable('radiology_studies') && $visit->visit_date) {
            $studies = DB::table('radiology_studies')
                ->where('store_id', $storeId)
                ->where('patient_id', $visit->patient_id)
                ->whereDate('created_at', $visit->visit_date)
                ->count();
            if ($studies) {
                $blocking[] = $studies . ' radiology stud' . ($studies === 1 ? 'y' : 'ies');
            }
        }

        return $blocking;
    }

    /**
     * Remove a visit outright. Only ever for a row registered by mistake — anything attached to
     * it stops the delete and points the user at Cancel instead.
     */
    public function destroy(Request $request, $id)
    {
        if (!auth('vendor')->check() && !hasPermission('opd_register', 'delete')) abort(403);
        $this->ensureClinicalSchema();

        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)->with('patient')->findOrFail($id);

        $blocking = $this->visitAttachments($visit, (int) $store_id);

        if (!empty($blocking)) {
            Toastr::error(
                'This visit has ' . implode(', ', $blocking) . ' against it and cannot be deleted. '
                . 'That is part of the patient\'s record — cancel the visit instead.'
            );
            return Redirect::route('vendor.opd.index', $this->registerFilters($request));
        }

        $token      = $visit->token_number;
        $patientId  = $visit->patient_id;
        $visitDate  = $visit->visit_date?->toDateString();
        $patientName = $visit->patient?->name ?? 'patient';

        $visit->delete();

        \App\Models\HospitalActivityLog::record(
            $store_id, 'opd_visit', (int) $id, 'deleted',
            "OPD visit token #{$token} for {$patientName} on {$visitDate} deleted",
            ['patient_id' => $patientId, 'token_number' => $token, 'visit_date' => $visitDate]
        );

        Toastr::success('Visit deleted.');
        return Redirect::route('vendor.opd.index', $this->registerFilters($request));
    }

    /**
     * The hospital's own view of the clinical dropdowns.
     *
     * Shows what it is actually offered — the platform catalogue for its category plus anything
     * its doctors have typed — and lets it stop offering any of them. Hiding is per store: the
     * catalogue itself is admin's and is never changed from here.
     */
    public function terms(Request $request)
    {
        $this->ensureClinicalSchema();
        $store_id = Helpers::get_store_id();

        $category = \App\Models\OpdClinicalTerm::categoryFor($store_id);
        $lists    = [];

        foreach ([\App\Models\OpdClinicalTerm::TYPE_DIAGNOSIS, \App\Models\OpdClinicalTerm::TYPE_TREATMENT] as $type) {
            $catalogue = collect(\App\Models\OpdTermCatalogue::namesFor($category, $type));
            $hidden    = collect(\App\Models\OpdClinicalTerm::hiddenNames($store_id, $type))
                ->mapWithKeys(fn($n) => [mb_strtolower(trim($n)) => true]);

            $own = \App\Models\OpdClinicalTerm::where('store_id', $store_id)
                ->where('type', $type)->where('hidden', false)
                ->orderBy('name')->pluck('name');

            $lists[$type] = [
                'catalogue' => $catalogue->sort(SORT_NATURAL | SORT_FLAG_CASE)->values(),
                'own'       => $own,
                'hidden'    => $hidden,
            ];
        }

        $categoryLabel = \App\Models\StoreConfig::hospitalCategoryLabel($category);

        return view('hmis::vendor.opd.terms', compact('lists', 'category', 'categoryLabel'));
    }

    public function opTypesUpdate(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:100',
            'action' => 'required|in:add,hide,restore',
        ]);

        $store_id = Helpers::get_store_id();
        $name     = trim($request->name);

        match ($request->action) {
            'add'     => \App\Models\OpdOpType::add($store_id, $name),
            'hide'    => \App\Models\OpdOpType::hide($store_id, $name),
            'restore' => \App\Models\OpdOpType::restore($store_id, $name),
        };

        Toastr::success('OP types updated');

        return back();
    }

    /**
     * Add an OP type from the register form. Same store list the settings screen manages — this
     * one just answers in JSON and redirects nowhere, because it is called mid-registration.
     */
    public function opTypesQuickAdd(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);

        $name = trim($request->name);
        \App\Models\OpdOpType::add(Helpers::get_store_id(), $name);

        return response()->json(['success' => true, 'name' => $name]);
    }

    /** Hide or restore one term for this store. */
    public function termsUpdate(Request $request)
    {
        $request->validate([
            'type'   => 'required|in:' . \App\Models\OpdClinicalTerm::TYPE_DIAGNOSIS . ',' . \App\Models\OpdClinicalTerm::TYPE_TREATMENT,
            'name'   => 'required|string|max:150',
            'action' => 'required|in:hide,show',
        ]);

        $store_id = Helpers::get_store_id();

        $request->action === 'hide'
            ? \App\Models\OpdClinicalTerm::hide($store_id, $request->type, $request->name)
            : \App\Models\OpdClinicalTerm::unhide($store_id, $request->type, $request->name);

        // The casemix ordering is built from what is on offer, so it has to be rebuilt.
        \App\Services\OpdTermInsights::forget($store_id);

        Toastr::success($request->action === 'hide'
            ? '"' . $request->name . '" will no longer be offered.'
            : '"' . $request->name . '" is available again.');

        return back();
    }

    public function edit($id)
    {
        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)->findOrFail($id);
        $patients = Patient::where('store_id', $store_id)->where('status', 1)->orderBy('name')->get();
        $doctors  = DoctorProfile::where('store_id', $store_id)->with('employee')->get();

        $complaintOptions = \App\Models\OpdClinicalTerm::listFor($store_id, \App\Models\OpdClinicalTerm::TYPE_COMPLAINT);

        $complaintGroups = \App\Models\OpdComplaintGroup::listFor($store_id);
        $opTypes         = \App\Models\OpdOpType::listFor($store_id);

        return view('hmis::vendor.opd.edit', compact('visit', 'patients', 'doctors', 'complaintOptions', 'complaintGroups', 'opTypes'));
    }

    public function update(Request $request, $id)
    {
        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)->findOrFail($id);

        $request->validate([
            'visit_type'       => 'required|in:' . implode(',', array_keys(OpdVisit::VISIT_TYPES)),
            'chief_complaint'   => 'nullable|array',
            'chief_complaint.*' => 'string|max:150',
            'bp_systolic'      => 'nullable|integer|min:0|max:300',
            'bp_diastolic'     => 'nullable|integer|min:0|max:200',
            'temperature'      => 'nullable|numeric|min:90|max:110',
            'weight'           => 'nullable|numeric|min:0|max:500',
            'height'           => 'nullable|numeric|min:0|max:300',
            'spo2'             => 'nullable|integer|min:0|max:100',
            'pulse_rate'       => 'nullable|integer|min:0|max:300',
            'respiratory_rate' => 'nullable|integer|min:0|max:100',
            'notes'            => 'nullable|string',
            'op_type'          => 'nullable|string|max:100',
        ]);

        $visit->update([
            'visit_type'        => $request->visit_type,
            'op_type'           => $request->op_type ?: null,
            'chief_complaint'   => \App\Models\OpdClinicalTerm::absorb($store_id, \App\Models\OpdClinicalTerm::TYPE_COMPLAINT, $request->chief_complaint),
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
