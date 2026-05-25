<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcceptedServiceRequest;
use App\Models\DoctorProfile;
use App\Models\DoctorSlot;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\ServiceRequest;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class OpdController extends Controller
{
    public function index(Request $request)
    {
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

        $headings = ['Token', 'Visit Date', 'Patient', 'MUID', 'Doctor', 'Chief Complaint', 'BP', 'Temperature', 'Weight', 'Status'];
        $data = $visits->map(fn($v) => [
            $v->token_number,
            $v->visit_date,
            $v->patient?->name,
            $v->patient?->patient_uid,
            'Dr. ' . trim(($v->doctorProfile?->employee?->f_name ?? '') . ' ' . ($v->doctorProfile?->employee?->l_name ?? '')),
            $v->chief_complaint,
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

                    $prefillBooking = [
                        'sr_id'            => $sr->id,
                        'service_name'     => $sr->item?->name,
                        'patient_name'     => $patientName,
                        'patient_phone'    => $patientPhone,
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

        $nextToken = (OpdVisit::where('store_id', $store_id)
            ->whereDate('visit_date', $visitDate)
            ->max('token_number') ?? 0) + 1;

        OpdVisit::create([
            'store_id'            => $store_id,
            'patient_id'          => $patientId,
            'doctor_profile_id'   => $doctorProfileId,
            'appointment_id'      => $request->appointment_id ?: null,
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
        $isOther = $sr->patient_for === 'other' && $sr->patient_name;
        $user    = User::find($sr->user_id);

        if ($isOther) {
            return Patient::create([
                'store_id'    => $storeId,
                'user_id'     => null,
                'patient_uid' => Patient::generateUid($storeId),
                'name'        => $sr->patient_name,
                'phone'       => $sr->patient_phone,
                'email'       => null,
                'status'      => 1,
            ])->id;
        }

        if ($user) {
            $patient = Patient::where('user_id', $user->id)->where('store_id', $storeId)->first();
            if ($patient) return $patient->id;

            return Patient::create([
                'store_id'    => $storeId,
                'user_id'     => $user->id,
                'patient_uid' => Patient::generateUid($storeId),
                'name'        => trim(($user->f_name ?? '') . ' ' . ($user->l_name ?? '')) ?: ($user->name ?? 'Patient'),
                'phone'       => $user->phone,
                'email'       => $user->email,
                'status'      => 1,
            ])->id;
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
        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)
            ->with(['patient.documents', 'patient.medicalHistory', 'doctorProfile.employee', 'recorder'])
            ->findOrFail($id);

        return view('hmis::vendor.opd.show', compact('visit'));
    }

    public function quickUpdate(Request $request, $id)
    {
        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)->findOrFail($id);

        $request->validate([
            'chief_complaint' => 'nullable|string|max:500',
            'notes'           => 'nullable|string',
        ]);

        $visit->chief_complaint = $request->chief_complaint;
        $visit->notes           = $request->notes;
        $visit->save();

        return response()->json(['ok' => true]);
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
