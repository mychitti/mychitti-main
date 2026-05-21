<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\DoctorProfile;
use App\Models\IpdAdmission;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\Ward;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class IpdController extends Controller
{
    public function index(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $status   = $request->status ?? 'admitted';
        $search   = $request->search;

        $preset = $request->get('date_range', 'this_month');
        $custom = $request->get('custom_date_range');
        $range  = Helpers::calculatePresetDates($preset, $custom);
        $from   = $range['start']->toDateString();
        $to     = $range['end']->toDateString();

        $admissions = IpdAdmission::where('store_id', $store_id)
            ->whereDate('admission_date', '>=', $from)
            ->whereDate('admission_date', '<=', $to)
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->where('admission_number', 'like', "%$search%")
                  ->orWhereHas('patient', fn($p) => $p->where('name', 'like', "%$search%")
                      ->orWhere('patient_uid', 'like', "%$search%"));
            })
            ->with(['patient', 'ward', 'bed', 'doctorProfile.employee'])
            ->orderByDesc('admission_date')
            ->paginate(20);

        return view('hmis::vendor.ipd.index', compact('admissions', 'status', 'preset', 'from', 'to'));
    }

    public function export(Request $request)
    {
        if (!auth('vendor')->check() && !hasPermission('ipd_admission', 'export')) abort(403);
        $store_id = Helpers::get_store_id();
        $status   = $request->status ?? 'all';
        $search   = $request->search;

        $preset = $request->get('date_range', 'this_month');
        $custom = $request->get('custom_date_range');
        $range  = Helpers::calculatePresetDates($preset, $custom);
        $from   = $range['start']->toDateString();
        $to     = $range['end']->toDateString();

        $admissions = IpdAdmission::where('store_id', $store_id)
            ->whereDate('admission_date', '>=', $from)
            ->whereDate('admission_date', '<=', $to)
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->where('admission_number', 'like', "%$search%")
                  ->orWhereHas('patient', fn($p) => $p->where('name', 'like', "%$search%")
                      ->orWhere('patient_uid', 'like', "%$search%"));
            })
            ->with(['patient', 'ward', 'bed', 'doctorProfile.employee'])
            ->orderByDesc('admission_date')
            ->get();

        $headings = ['Admission No', 'Patient', 'UID', 'Doctor', 'Ward', 'Bed', 'Admission Date', 'Discharge Date', 'Status'];
        $data = $admissions->map(fn($a) => [
            $a->admission_number,
            $a->patient?->name,
            $a->patient?->patient_uid,
            'Dr. ' . trim(($a->doctorProfile?->employee?->f_name ?? '') . ' ' . ($a->doctorProfile?->employee?->l_name ?? '')),
            $a->ward?->ward_name,
            $a->bed?->bed_number,
            $a->admission_date,
            $a->discharge_date,
            ucfirst($a->status),
        ])->toArray();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($data, $headings),
            'ipd_admissions_' . $from . '_' . $to . '.xlsx'
        );
    }

    public function create(Request $request)
    {
        if (!auth('vendor')->check() && !hasPermission('ipd_admission', 'add')) abort(403);
        $store_id = Helpers::get_store_id();
        $patients = Patient::where('store_id', $store_id)->where('status', 1)->orderBy('name')->get();
        $doctors  = DoctorProfile::where('store_id', $store_id)->with('employee')->get();
        $wards    = Ward::where('store_id', $store_id)->where('is_active', true)->orderBy('ward_name')->get();

        $prefillPatient = $request->patient_id
            ? Patient::where('store_id', $store_id)->find($request->patient_id)
            : null;

        return view('hmis::vendor.ipd.create', compact('patients', 'doctors', 'wards', 'prefillPatient'));
    }

    public function getAvailableBeds(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $ward_id  = $request->ward_id;

        $beds = Bed::where('store_id', $store_id)
            ->where('ward_id', $ward_id)
            ->where('status', 'available')
            ->orderBy('bed_number')
            ->get(['id', 'bed_number', 'bed_type', 'daily_charge']);

        return response()->json($beds);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'       => 'required|integer',
            'ward_id'          => 'required|integer',
            'bed_id'           => 'required|integer',
            'doctor_profile_id'=> 'required|integer',
            'admission_date'   => 'required|date',
            'diagnosis'        => 'nullable|string|max:500',
            'admission_reason' => 'nullable|string|max:500',
        ]);

        $store_id = Helpers::get_store_id();

        DB::beginTransaction();
        try {
            $bed = Bed::where('store_id', $store_id)
                ->where('id', $request->bed_id)
                ->where('status', 'available')
                ->firstOrFail();

            $admNumber = $this->generateAdmissionNumber($store_id);

            $admission = IpdAdmission::create([
                'store_id'          => $store_id,
                'patient_id'        => $request->patient_id,
                'bed_id'            => $request->bed_id,
                'ward_id'           => $request->ward_id,
                'doctor_profile_id' => $request->doctor_profile_id,
                'admission_number'  => $admNumber,
                'admission_date'    => $request->admission_date,
                'diagnosis'         => $request->diagnosis,
                'admission_reason'  => $request->admission_reason,
                'daily_charge'      => $bed->daily_charge,
                'status'            => 'admitted',
                'admitted_by'       => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            ]);

            $bed->update(['status' => 'occupied']);

            DB::commit();

            $admission->load(['patient', 'doctorProfile.employee']);
            $patName = $admission->patient?->name . ' (' . $admission->patient?->patient_uid . ')';
            $drName  = 'Dr. ' . trim(($admission->doctorProfile?->employee?->f_name ?? '') . ' ' . ($admission->doctorProfile?->employee?->l_name ?? ''));
            \App\Models\HospitalActivityLog::record(
                $store_id, 'ipd_admission', $admission->id, 'admitted',
                "Patient admitted: {$patName} under {$drName}. Admission# {$admission->admission_number}",
                ['admission_number' => $admission->admission_number, 'ward_id' => $request->ward_id, 'bed_id' => $request->bed_id]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Error admitting patient: ' . $e->getMessage());
            return back()->withInput();
        }

        Toastr::success('Patient admitted successfully.');
        return Redirect::route('vendor.ipd.index');
    }

    public function show($id)
    {
        if (!auth('vendor')->check() && !hasPermission('ipd_admission', 'view')) abort(403);
        $store_id  = Helpers::get_store_id();
        $admission = IpdAdmission::where('store_id', $store_id)
            ->with(['patient', 'ward', 'bed', 'doctorProfile.employee', 'admittedBy', 'dischargedBy'])
            ->findOrFail($id);

        $nursingNotes = \App\Models\NursingNote::where('ipd_admission_id', $id)
            ->with('recorder')
            ->orderByDesc('recorded_at')
            ->get();

        $dietCharts = \App\Models\DietChart::where('ipd_admission_id', $id)
            ->with('recorder')
            ->orderByDesc('date')
            ->get();

        $consents = PatientConsent::where('ipd_admission_id', $id)
            ->orderByDesc('signed_at')
            ->get();

        return view('hmis::vendor.ipd.show', compact('admission', 'nursingNotes', 'dietCharts', 'consents'));
    }

    public function dischargeForm($id)
    {
        if (!auth('vendor')->check() && !hasPermission('ipd_admission', 'discharge')) abort(403);
        $store_id  = Helpers::get_store_id();
        $admission = IpdAdmission::where('store_id', $store_id)
            ->where('status', 'admitted')
            ->with(['patient', 'ward', 'bed', 'doctorProfile.employee'])
            ->findOrFail($id);

        return view('hmis::vendor.ipd.discharge', compact('admission'));
    }

    public function discharge(Request $request, $id)
    {
        if (!auth('vendor')->check() && !hasPermission('ipd_admission', 'discharge')) abort(403);
        $request->validate([
            'discharge_date'    => 'required|date',
            'discharge_type'    => 'required|in:' . implode(',', array_keys(IpdAdmission::DISCHARGE_TYPES)),
            'discharge_summary' => 'nullable|string',
        ]);

        $store_id  = Helpers::get_store_id();
        $admission = IpdAdmission::where('store_id', $store_id)
            ->where('status', 'admitted')
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            $admission->update([
                'discharge_date'    => $request->discharge_date,
                'discharge_type'    => $request->discharge_type,
                'discharge_summary' => $request->discharge_summary,
                'status'            => 'discharged',
                'discharged_by'     => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            ]);

            if ($admission->bed_id) {
                Bed::where('id', $admission->bed_id)->update(['status' => 'available']);
            }

            DB::commit();

            $admission->load('patient');
            $patName = $admission->patient?->name . ' (' . $admission->patient?->patient_uid . ')';
            \App\Models\HospitalActivityLog::record(
                $store_id, 'ipd_admission', $admission->id, 'discharged',
                "Patient discharged: {$patName}. Type: {$request->discharge_type}. Admission# {$admission->admission_number}",
                ['discharge_type' => $request->discharge_type, 'discharge_date' => $request->discharge_date]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Error discharging patient: ' . $e->getMessage());
            return back();
        }

        Toastr::success('Patient discharged successfully.');
        return Redirect::route('vendor.ipd.show', $id);
    }

    public function bedDashboard()
    {
        $store_id = Helpers::get_store_id();
        $wards    = Ward::where('store_id', $store_id)
            ->where('is_active', true)
            ->with(['beds.activeAdmission.patient'])
            ->orderBy('ward_name')
            ->get();

        return view('hmis::vendor.ipd.bed_dashboard', compact('wards'));
    }

    private function generateAdmissionNumber(int $storeId): string
    {
        $prefix = 'IPD-' . now()->format('Ym') . '-';
        $last   = IpdAdmission::where('store_id', $storeId)
            ->where('admission_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('admission_number');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        $number = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
        while (IpdAdmission::where('admission_number', $number)->exists()) {
            $seq++;
            $number = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
        }

        return $number;
    }
}
