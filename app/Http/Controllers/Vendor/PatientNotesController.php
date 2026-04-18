<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\DietChart;
use App\Models\IpdAdmission;
use App\Models\NursingNote;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class PatientNotesController extends Controller
{
    // ── Nursing Notes ─────────────────────────────────────────────────────

    public function nursingNoteStore(Request $request, $admissionId)
    {
        $request->validate([
            'note_type'   => 'required|in:' . implode(',', array_keys(NursingNote::TYPES)),
            'note'        => 'required|string',
            'recorded_at' => 'required|date',
        ]);

        $store_id  = Helpers::get_store_id();
        $admission = IpdAdmission::where('store_id', $store_id)->findOrFail($admissionId);

        NursingNote::create([
            'store_id'         => $store_id,
            'ipd_admission_id' => $admission->id,
            'patient_id'       => $admission->patient_id,
            'recorded_by'      => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            'note_type'        => $request->note_type,
            'note'             => $request->note,
            'recorded_at'      => $request->recorded_at,
        ]);

        Toastr::success('Nursing note added.');
        return Redirect::route('vendor.ipd.show', $admissionId);
    }

    public function nursingNoteDestroy($admissionId, $noteId)
    {
        $store_id = Helpers::get_store_id();
        IpdAdmission::where('store_id', $store_id)->findOrFail($admissionId);
        NursingNote::where('ipd_admission_id', $admissionId)->findOrFail($noteId)->delete();

        Toastr::success('Note deleted.');
        return Redirect::route('vendor.ipd.show', $admissionId);
    }

    // ── Diet Chart ────────────────────────────────────────────────────────

    public function dietStore(Request $request, $admissionId)
    {
        $request->validate([
            'date'         => 'required|date',
            'meal_type'    => 'required|in:' . implode(',', array_keys(DietChart::MEAL_TYPES)),
            'diet_type'    => 'required|in:' . implode(',', array_keys(DietChart::DIET_TYPES)),
            'instructions' => 'nullable|string',
        ]);

        $store_id  = Helpers::get_store_id();
        $admission = IpdAdmission::where('store_id', $store_id)->findOrFail($admissionId);

        DietChart::create([
            'store_id'         => $store_id,
            'ipd_admission_id' => $admission->id,
            'patient_id'       => $admission->patient_id,
            'date'             => $request->date,
            'meal_type'        => $request->meal_type,
            'diet_type'        => $request->diet_type,
            'instructions'     => $request->instructions,
            'recorded_by'      => auth('vendor_employee')->id() ?? auth('vendor')->id(),
        ]);

        Toastr::success('Diet entry added.');
        return Redirect::route('vendor.ipd.show', $admissionId);
    }

    public function dietDestroy($admissionId, $dietId)
    {
        $store_id = Helpers::get_store_id();
        IpdAdmission::where('store_id', $store_id)->findOrFail($admissionId);
        DietChart::where('ipd_admission_id', $admissionId)->findOrFail($dietId)->delete();

        Toastr::success('Diet entry deleted.');
        return Redirect::route('vendor.ipd.show', $admissionId);
    }
}
