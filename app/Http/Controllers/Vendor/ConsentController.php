<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\ConsentTemplate;
use App\Models\IpdAdmission;
use App\Models\Patient;
use App\Models\PatientConsent;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class ConsentController extends Controller
{
    // ── All consents list ──────────────────────────────────────────────
    public function index(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $search   = $request->search;

        $consents = PatientConsent::where('store_id', $store_id)
            ->when($search, fn($q) => $q->where('title', 'like', "%$search%")
                ->orWhereHas('patient', fn($p) => $p->where('name', 'like', "%$search%")))
            ->with(['patient', 'admission'])
            ->orderByDesc('signed_at')
            ->paginate(20)->withQueryString();

        return view('vendor-views.consent.index', compact('consents', 'search'));
    }

    // ── Templates ──────────────────────────────────────────────────────

    public function templateIndex()
    {
        $store_id  = Helpers::get_store_id();
        $templates = ConsentTemplate::where('store_id', $store_id)
            ->orderBy('title')->paginate(20);
        return view('vendor-views.consent.template_index', compact('templates'));
    }

    public function templateCreate()
    {
        return view('vendor-views.consent.template_form', ['template' => null]);
    }

    public function templateStore(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:200',
            'content' => 'required|string',
        ]);

        ConsentTemplate::create([
            'store_id' => Helpers::get_store_id(),
            'title'    => $request->title,
            'content'  => $request->content,
            'is_active'=> true,
        ]);

        Toastr::success('Consent template created.');
        return redirect()->route('vendor.consent.template.index');
    }

    public function templateEdit($id)
    {
        $store_id = Helpers::get_store_id();
        $template = ConsentTemplate::where('store_id', $store_id)->findOrFail($id);
        return view('vendor-views.consent.template_form', compact('template'));
    }

    public function templateUpdate(Request $request, $id)
    {
        $request->validate([
            'title'   => 'required|string|max:200',
            'content' => 'required|string',
        ]);

        $store_id = Helpers::get_store_id();
        $template = ConsentTemplate::where('store_id', $store_id)->findOrFail($id);
        $template->update([
            'title'    => $request->title,
            'content'  => $request->content,
            'is_active'=> $request->boolean('is_active', true),
        ]);

        Toastr::success('Template updated.');
        return redirect()->route('vendor.consent.template.index');
    }

    public function templateDestroy($id)
    {
        $store_id = Helpers::get_store_id();
        ConsentTemplate::where('store_id', $store_id)->findOrFail($id)->delete();
        Toastr::success('Template deleted.');
        return back();
    }

    // ── Patient Consents ───────────────────────────────────────────────

    /** Show create form — can be called with ?admission_id=X */
    public function create(Request $request)
    {
        $store_id   = Helpers::get_store_id();
        $templates  = ConsentTemplate::where('store_id', $store_id)
            ->where('is_active', true)->orderBy('title')->get();

        $admission  = null;
        $patient    = null;

        if ($request->admission_id) {
            $admission = IpdAdmission::where('store_id', $store_id)
                ->with('patient')->findOrFail($request->admission_id);
            $patient   = $admission->patient;
        } elseif ($request->patient_id) {
            $patient = Patient::where('store_id', $store_id)->findOrFail($request->patient_id);
        }

        $patients = Patient::where('store_id', $store_id)->orderBy('name')->get();

        return view('vendor-views.consent.create', compact(
            'templates', 'admission', 'patient', 'patients'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'     => 'required|integer',
            'title'          => 'required|string|max:200',
            'content'        => 'required|string',
            'signatory_name' => 'nullable|string|max:150',
            'witness_name'   => 'nullable|string|max:150',
            'signed_at'      => 'nullable|date',
        ]);

        $store_id = Helpers::get_store_id();

        $patient = Patient::where('store_id', $store_id)->findOrFail($request->patient_id);

        // Resolve doctor name — from IPD admission or fall back to empty
        $doctorName = '';
        if ($request->ipd_admission_id) {
            $adm = IpdAdmission::with('doctorProfile.employee')->find($request->ipd_admission_id);
            $doctorName = trim(
                ($adm?->doctorProfile?->employee?->f_name ?? '')
                . ' ' . ($adm?->doctorProfile?->employee?->l_name ?? '')
            );
        }

        // Replace placeholders in content
        $content = str_replace(
            ['{{patient_name}}', '{{date}}', '{{doctor_name}}'],
            [
                $patient->name,
                now()->format('d M Y'),
                $doctorName ?: 'the attending doctor',
            ],
            $request->content
        );

        PatientConsent::create([
            'store_id'            => $store_id,
            'patient_id'          => $request->patient_id,
            'ipd_admission_id'    => $request->ipd_admission_id ?: null,
            'consent_template_id' => $request->consent_template_id ?: null,
            'title'               => $request->title,
            'content'             => $content,
            'signatory_name'      => $request->signatory_name,
            'witness_name'        => $request->witness_name,
            'signed_at'           => $request->signed_at ?: now(),
            'notes'               => $request->notes,
            'created_by'          => auth('vendor_employee')->id() ?? auth('vendor')->id(),
        ]);

        Toastr::success('Consent form saved.');

        if ($request->ipd_admission_id) {
            return redirect()->route('vendor.ipd.show', $request->ipd_admission_id);
        }
        return redirect()->route('vendor.patient.show', $request->patient_id);
    }

    public function show($id)
    {
        $store_id = Helpers::get_store_id();
        $consent  = PatientConsent::where('store_id', $store_id)
            ->with(['patient', 'admission', 'createdBy'])
            ->findOrFail($id);
        return view('vendor-views.consent.show', compact('consent'));
    }

    public function destroy($id)
    {
        $store_id = Helpers::get_store_id();
        $consent  = PatientConsent::where('store_id', $store_id)->findOrFail($id);
        $admId    = $consent->ipd_admission_id;
        $patId    = $consent->patient_id;
        $consent->delete();
        Toastr::success('Consent deleted.');

        if ($admId) return redirect()->route('vendor.ipd.show', $admId);
        return redirect()->route('vendor.patient.show', $patId);
    }

    /** AJAX: load template content by ID, with placeholders pre-filled if context is known */
    public function templateContent(Request $request, $id)
    {
        $store_id = Helpers::get_store_id();
        $template = ConsentTemplate::where('store_id', $store_id)
            ->where('is_active', true)->findOrFail($id);

        $patientName = '';
        $doctorName  = '';

        if ($request->patient_id) {
            $patient     = Patient::find($request->patient_id);
            $patientName = $patient?->name ?? '';
        }

        if ($request->admission_id) {
            $adm = IpdAdmission::with('doctorProfile.employee')->find($request->admission_id);
            $doctorName = trim(
                ($adm?->doctorProfile?->employee?->f_name ?? '')
                . ' ' . ($adm?->doctorProfile?->employee?->l_name ?? '')
            );
        }

        $content = str_replace(
            ['{{patient_name}}', '{{date}}', '{{doctor_name}}'],
            [
                $patientName ?: '{{patient_name}}',
                now()->format('d M Y'),
                $doctorName  ?: '{{doctor_name}}',
            ],
            $template->content
        );

        return response()->json(['title' => $template->title, 'content' => $content]);
    }
}
