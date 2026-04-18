<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\OpdVisit;
use App\Models\Patient;
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
        $doctor   = $request->doctor;
        $search   = $request->search;

        $visits = OpdVisit::where('store_id', $store_id)
            ->whereBetween('visit_date', [$formatted_from, $formatted_to])
            ->when($doctor, fn($q) => $q->where('doctor_profile_id', $doctor))
            ->when($search, function ($q) use ($search) {
                $q->whereHas('patient', fn($p) => $p->where('name', 'like', "%$search%")
                    ->orWhere('patient_uid', 'like', "%$search%"));
            })
            ->with(['patient', 'doctorProfile.employee'])
            ->orderBy('token_number')
            ->paginate(20);

        $doctors = DoctorProfile::where('store_id', $store_id)
            ->with('employee')
            ->get();

        return view('vendor-views.opd.index', compact('preset', 'visits', 'doctors', 'from', 'to'));
    }

    public function export(Request $request)
    {
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

        $headings = ['Token', 'Visit Date', 'Patient', 'UID', 'Doctor', 'Chief Complaint', 'BP', 'Temperature', 'Weight', 'Status'];
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

    public function create(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $patients = Patient::where('store_id', $store_id)->where('status', 1)->orderBy('name')->get();
        $doctors  = DoctorProfile::where('store_id', $store_id)->with('employee')->get();

        $nextToken = (OpdVisit::where('store_id', $store_id)
            ->whereDate('visit_date', now()->toDateString())
            ->max('token_number') ?? 0) + 1;

        $prefillPatient = $request->patient_id
            ? Patient::where('store_id', $store_id)->find($request->patient_id)
            : null;

        return view('vendor-views.opd.create', compact('patients', 'doctors', 'nextToken', 'prefillPatient'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'        => 'required|integer',
            'doctor_profile_id' => 'required|integer',
            'visit_date'        => 'required|date',
            'visit_type'        => 'required|in:' . implode(',', array_keys(OpdVisit::VISIT_TYPES)),
            'chief_complaint'   => 'nullable|string|max:500',
            'bp_systolic'       => 'nullable|integer|min:0|max:300',
            'bp_diastolic'      => 'nullable|integer|min:0|max:200',
            'temperature'       => 'nullable|numeric|min:90|max:110',
            'weight'            => 'nullable|numeric|min:0|max:500',
            'height'            => 'nullable|numeric|min:0|max:300',
            'spo2'              => 'nullable|integer|min:0|max:100',
            'pulse_rate'        => 'nullable|integer|min:0|max:300',
            'respiratory_rate'  => 'nullable|integer|min:0|max:100',
            'notes'             => 'nullable|string',
        ]);

        $store_id = Helpers::get_store_id();

        OpdVisit::create([
            'store_id'          => $store_id,
            'patient_id'        => $request->patient_id,
            'doctor_profile_id' => $request->doctor_profile_id,
            'appointment_id'    => $request->appointment_id ?: null,
            'visit_date'        => $request->visit_date,
            'token_number'      => $request->token_number,
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
            'recorded_by'       => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            'status'            => 'visited',
        ]);

        Toastr::success('OPD visit recorded.');
        return Redirect::route('vendor.opd.index');
    }

    public function show($id)
    {
        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)
            ->with(['patient', 'doctorProfile.employee', 'recorder'])
            ->findOrFail($id);

        return view('vendor-views.opd.show', compact('visit'));
    }

    public function edit($id)
    {
        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)->findOrFail($id);
        $patients = Patient::where('store_id', $store_id)->where('status', 1)->orderBy('name')->get();
        $doctors  = DoctorProfile::where('store_id', $store_id)->with('employee')->get();

        return view('vendor-views.opd.edit', compact('visit', 'patients', 'doctors'));
    }

    public function update(Request $request, $id)
    {
        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)->findOrFail($id);

        $request->validate([
            'visit_type'      => 'required|in:' . implode(',', array_keys(OpdVisit::VISIT_TYPES)),
            'chief_complaint' => 'nullable|string|max:500',
            'bp_systolic'     => 'nullable|integer|min:0|max:300',
            'bp_diastolic'    => 'nullable|integer|min:0|max:200',
            'temperature'     => 'nullable|numeric|min:90|max:110',
            'weight'          => 'nullable|numeric|min:0|max:500',
            'height'          => 'nullable|numeric|min:0|max:300',
            'spo2'            => 'nullable|integer|min:0|max:100',
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
