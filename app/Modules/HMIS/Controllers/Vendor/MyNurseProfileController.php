<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\IpdAdmission;
use App\Models\NurseProfile;
use Brian2694\Toastr\Facades\Toastr;

class MyNurseProfileController extends Controller
{
    private function getMyProfile(): ?NurseProfile
    {
        return NurseProfile::where('emp_id', auth('vendor_employee')->id())
            ->where('store_id', Helpers::get_store_id())
            ->with('employee')
            ->first();
    }

    // The nurse's own assigned (IPD) patients, with nursing notes + diet charts.
    public function patients()
    {
        $nurse = $this->getMyProfile();
        if (!$nurse) {
            Toastr::warning('No nurse profile is linked to your account.');
            return redirect()->route('vendor.dashboard');
        }

        IpdAdmission::ensureNurseAssignTable();
        $admissions = $nurse->admissions()
            ->where('ipd_admissions.store_id', Helpers::get_store_id())
            ->with(['patient', 'ward', 'bed', 'doctorProfile.employee', 'nursingNotes.recorder', 'dietCharts.recorder'])
            ->orderByRaw("ipd_admissions.status = 'active' desc")
            ->orderByDesc('ipd_admissions.admission_date')
            ->get();

        $duty = Attendance::dutySummary($nurse->employee, Helpers::get_store_id());

        return view('hmis::vendor.hospital.my_patients', [
            'admissions' => $admissions,
            'heading'    => 'My Assigned Patients',
            'role'       => 'nurse',
            'duty'       => $duty,
        ]);
    }
}
