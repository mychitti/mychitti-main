@extends('layouts.vendor.app')
@section('title', 'OPD Visit #' . $visit->id)

@section('content')
@php $patient = $visit->patient; @endphp
<div class="content container-fluid">

    {{-- Page header --}}
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-document-text" style="font-size:22px;"></i></span>
            OPD Visit
            <span class="badge badge-primary ml-2" style="font-size:16px;">Token {{ $visit->token_number }}</span>
        </h1>
        <div class="d-flex gap-2">
        @if (hasPermission('prescription', 'add'))
         <a href="{{ route('vendor.prescription.create', array_filter(['service_request_id' => $visit->service_request_id, 'patient_id' => $visit->patient_id, 'doctor_profile_id' => $visit->doctor_profile_id])) }}"
           class="btn btn-sm btn-primary">
            <i class="tio-file-text"></i> Write Prescription
        </a>
        @endif
        @if (hasPermission('ipd_admission', 'add'))
           <a href="{{ route('vendor.ipd.create', ['patient_id' => $visit->patient_id]) }}" class="btn btn-sm btn-outline-warning">
            <i class="tio-hospital"></i> Admit to IPD
        </a>
        @endif
        @if (hasPermission('ipd_admission', 'generate_bill'))
            <a href="{{ route('vendor.hospital-bill.create-opd', $visit->id) }}" class="btn btn-sm btn-outline-success">
                <i class="tio-receipt"></i> Generate Bill
            </a>
        @endif
            <a href="{{ route('vendor.opd.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-arrow-backward"></i> Register
            </a>
        </div>
    </div>

    {{-- Info bar --}}
    <div class="card mb-3" style="background:linear-gradient(90deg,#eff6ff,#f0fdf4); border:1px solid #bfdbfe;">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap gap-4">
                <div>
                    <small class="text-muted d-block">Patient</small>
                    <strong><a href="{{ route('vendor.patient.show', $visit->patient_id) }}">{{ $patient?->name }}</a></strong>
                    
                </div>
                <div>
                    <small class="text-muted d-block">ID</small>
                    <strong><a href="{{ route('vendor.patient.show', $visit->patient_id) }}">{{ $patient?->patient_uid }}</a></strong>
                </div>
                <div>
                    <small class="text-muted d-block">Doctor</small>
                    <strong>Dr. {{ $visit->doctorProfile?->employee?->f_name }} {{ $visit->doctorProfile?->employee?->l_name }}</strong>
                </div>
                <div>
                    <small class="text-muted d-block">Date</small>
                    <strong>{{ $visit->visit_date?->format('d M Y') }}</strong>
                </div>
                <div>
                    <small class="text-muted d-block">Visit Type</small>
                    <strong>{{ \App\Models\OpdVisit::VISIT_TYPES[$visit->visit_type] ?? $visit->visit_type }}</strong>
                </div>
                @if($visit->recorder)
                <div>
                    <small class="text-muted d-block">Recorded By</small>
                    <strong>{{ $visit->recorder?->f_name }} {{ $visit->recorder?->l_name }}</strong>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Vitals + Chief Complaint / Notes --}}
    <div class="row">

        {{-- Vitals --}}
        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-header py-2"><h6 class="mb-0">Vitals</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted pl-3" style="width:50%;">Blood Pressure</td>
                                <td>
                                    @if($visit->bp_systolic && $visit->bp_diastolic)
                                        <strong>{{ $visit->bp_systolic }}/{{ $visit->bp_diastolic }}</strong> mmHg
                                        @if($visit->bp_systolic >= 140 || $visit->bp_diastolic >= 90)
                                            <span class="badge badge-soft-danger ml-1">High</span>
                                        @elseif($visit->bp_systolic < 90 || $visit->bp_diastolic < 60)
                                            <span class="badge badge-soft-warning ml-1">Low</span>
                                        @else
                                            <span class="badge badge-soft-success ml-1">Normal</span>
                                        @endif
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-3">Temperature</td>
                                <td>
                                    @if($visit->temperature)
                                        <strong>{{ $visit->temperature }}</strong> °F
                                        @if($visit->temperature >= 100.4)
                                            <span class="badge badge-soft-danger ml-1">Fever</span>
                                        @endif
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                            </tr>
                            <tr><td class="text-muted pl-3">Pulse</td><td>{{ $visit->pulse_rate ? $visit->pulse_rate.' bpm' : '—' }}</td></tr>
                            <tr><td class="text-muted pl-3">Resp. Rate</td><td>{{ $visit->respiratory_rate ? $visit->respiratory_rate.' /min' : '—' }}</td></tr>
                            <tr>
                                <td class="text-muted pl-3">SpO2</td>
                                <td>
                                    @if($visit->spo2)
                                        <strong class="{{ $visit->spo2 < 95 ? 'text-danger' : 'text-success' }}">{{ $visit->spo2 }}%</strong>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                            </tr>
                            <tr><td class="text-muted pl-3">Weight</td><td>{!! $visit->weight ? $visit->weight.' kg' : '<span class="text-muted">—</span>' !!}</td></tr>
                            <tr><td class="text-muted pl-3">Height</td><td>{!! $visit->height ? $visit->height.' cm' : '<span class="text-muted">—</span>' !!}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Chief Complaint + Notes (inline editable) --}}
        <div class="col-md-7">

            {{-- Chief Complaint --}}
            <div class="card mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Chief Complaint</h6>
                    <button class="btn btn-xs btn-soft-secondary" onclick="toggleEdit('cc')">
                        <i class="tio-edit" id="ccEditIcon"></i>
                    </button>
                </div>
                <div class="card-body py-2" id="ccView">
                    <span id="ccText">{{ $visit->chief_complaint ?: '—' }}</span>
                </div>
                <div class="card-body py-2" id="ccEdit" style="display:none;">
                    <textarea class="form-control" id="ccInput" rows="3" maxlength="500"
                        placeholder="Enter chief complaint…">{{ $visit->chief_complaint }}</textarea>
                    <div class="mt-2 d-flex gap-2">
                        <button class="btn btn-sm btn--primary" onclick="saveField('cc')">Save</button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleEdit('cc')">Cancel</button>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div class="card mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Notes</h6>
                    <button class="btn btn-xs btn-soft-secondary" onclick="toggleEdit('notes')">
                        <i class="tio-edit" id="notesEditIcon"></i>
                    </button>
                </div>
                <div class="card-body py-2" id="notesView">
                    <span id="notesText" style="white-space:pre-wrap;">{{ $visit->notes ?: '—' }}</span>
                </div>
                <div class="card-body py-2" id="notesEdit" style="display:none;">
                    <textarea class="form-control" id="notesInput" rows="4"
                        placeholder="Add notes…">{{ $visit->notes }}</textarea>
                    <div class="mt-2 d-flex gap-2">
                        <button class="btn btn-sm btn--primary" onclick="saveField('notes')">Save</button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleEdit('notes')">Cancel</button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Patient Profile ───────────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="tio-user mr-1"></i> Patient Profile</h6>
            @if(hasPermission('patient_documents', 'list') || hasPermission('patient_documents', 'add'))
            <button class="btn btn-xs btn-outline-info" onclick="openDocsPanel();">
                <i class="tio-file mr-1"></i> Documents
                @if($patient?->documents?->count())
                    <span class="badge badge-soft-info ml-1">{{ $patient->documents->count() }}</span>
                @endif
            </button>
            @endif
        </div>
        <div class="card-body pb-2">
            {{-- Summary row --}}
            <div class="row align-items-start">
                <div class="col-auto">
                    @if($patient?->photo)
                        <img src="{{ asset('storage/patient/' . $patient->photo) }}"
                            class="rounded-circle" width="72" height="72" style="object-fit:cover;">
                    @else
                        <div class="rounded-circle bg-soft-primary d-inline-flex align-items-center justify-content-center"
                            style="width:72px;height:72px;font-size:28px;">
                            <i class="tio-user"></i>
                        </div>
                    @endif
                </div>
                <div class="col">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h5 class="mb-0">{{ $patient?->name }}</h5>
                        <span class="badge badge-soft-info">MUID: {{ $patient?->patient_uid }}</span>
                        @if($patient?->blood_group)
                            <span class="badge badge-soft-danger">{{ $patient->blood_group }}</span>
                        @endif
                    </div>
                    <div class="row" style="font-size:13px;">
                        <div class="col-sm-4"><span class="text-muted">Phone: </span>{{ $patient?->phone ?: '—' }}</div>
                        <div class="col-sm-4"><span class="text-muted">Email: </span>{{ $patient?->email ?: '—' }}</div>
                        <div class="col-sm-4"><span class="text-muted">Gender: </span>{{ ucfirst($patient?->gender ?? '—') }}</div>
                        <div class="col-sm-4 mt-1"><span class="text-muted">DOB: </span>
                            @if($patient?->dob)
                                {{ \Carbon\Carbon::parse($patient->dob)->format('d M Y') }}
                                <small class="text-muted">({{ \Carbon\Carbon::parse($patient->dob)->age }} yrs)</small>
                            @else —
                            @endif
                        </div>
                        <div class="col-sm-8 mt-1"><span class="text-muted">Address: </span>{{ implode(', ', array_filter([$patient?->address, $patient?->city, $patient?->state, $patient?->pincode])) ?: '—' }}</div>
                        @if($patient?->allergies)
                        <div class="col-12 mt-1"><span class="text-muted">Allergies: </span><span class="badge badge-soft-danger">{{ $patient->allergies }}</span></div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Expand / collapse toggle --}}
            <div class="mt-2">
                <button class="btn btn-xs btn-soft-secondary w-100" type="button"
                        data-toggle="collapse" data-target="#patientFullDetails"
                        aria-expanded="false" aria-controls="patientFullDetails"
                        id="patientDetailsToggle">
                    <i class="tio-chevron-down mr-1" id="patientDetailsChevron"></i> Show full details
                </button>
            </div>

            {{-- Collapsible full details --}}
            <div class="collapse" id="patientFullDetails">
                <hr class="my-3">
                @php $h = $patient?->medicalHistory; @endphp

                {{-- Emergency contact --}}
                <h6 class="text-muted mb-2" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Emergency Contact</h6>
                <div class="row mb-3" style="font-size:13px;">
                    <div class="col-sm-4"><span class="text-muted">Name: </span>{{ $patient?->emergency_contact_name ?: '—' }}</div>
                    <div class="col-sm-4"><span class="text-muted">Phone: </span>{{ $patient?->emergency_contact_phone ?: '—' }}</div>
                    <div class="col-sm-4"><span class="text-muted">Relation: </span>{{ $patient?->emergency_contact_relation ?: '—' }}</div>
                </div>

                {{-- Medical history --}}
                <h6 class="text-muted mb-2" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Medical History</h6>
                @if($h)
                <div class="row" style="font-size:13px;">
                    <div class="col-sm-6 mb-2">
                        <span class="text-muted d-block">Chronic Conditions</span>
                        {{ $h->chronic_conditions ?: '—' }}
                    </div>
                    <div class="col-sm-6 mb-2">
                        <span class="text-muted d-block">Past Surgeries</span>
                        {{ $h->past_surgeries ?: '—' }}
                    </div>
                    <div class="col-sm-6 mb-2">
                        <span class="text-muted d-block">Current Medications</span>
                        {{ $h->current_medications ?: '—' }}
                    </div>
                    <div class="col-sm-6 mb-2">
                        <span class="text-muted d-block">Family History</span>
                        {{ $h->family_history ?: '—' }}
                    </div>
                    <div class="col-sm-6 mb-2">
                        <span class="text-muted d-block">Habits</span>
                        <span class="badge badge-soft-{{ $h->smoking ? 'danger' : 'success' }}">Smoking: {{ $h->smoking ? 'Yes' : 'No' }}</span>
                        <span class="badge badge-soft-{{ $h->alcohol ? 'danger' : 'success' }} ml-1">Alcohol: {{ $h->alcohol ? 'Yes' : 'No' }}</span>
                    </div>
                    @if($h->notes)
                    <div class="col-12 mb-2">
                        <span class="text-muted d-block">Notes</span>
                        {{ $h->notes }}
                    </div>
                    @endif
                </div>
                @else
                <p class="text-muted mb-0" style="font-size:13px;">No medical history recorded.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="d-flex gap-2 mb-4">
       
     
    </div>

</div>

{{-- ── Draggable Documents Panel (no backdrop, background stays interactive) --}}
<div id="docsModalDialog" style="display:none; position:fixed; top:80px; left:50%; transform:translateX(-50%);
     width:520px; max-width:95vw; background:#fff; border-radius:10px; box-shadow:0 8px 32px rgba(0,0,0,.22);
     overflow:hidden; z-index:9999;">

        {{-- Header (drag handle) --}}
        <div id="docsModalHandle" title="Drag to move"
             style="background:#f8fafc; border-bottom:1px solid #e5e7eb;
             padding:10px 16px; display:flex; align-items:center; justify-content:space-between; cursor:grab; user-select:none;">
            <div style="display:flex; align-items:center; gap:8px;">
                {{-- Grip dots --}}
                <span style="display:inline-grid; grid-template-columns:repeat(2,4px); gap:3px; opacity:.4; flex-shrink:0;">
                    <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                    <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                    <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                    <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                    <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                    <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                </span>
                <span style="font-weight:600; font-size:14px;">
                    <i class="tio-file mr-1"></i>
                    Patient Documents
                    @if($patient?->documents?->count())
                        <span class="badge badge-soft-info ml-1">{{ $patient->documents->count() }}</span>
                    @endif
                </span>
            </div>
            <button onclick="closeDocsModal()" style="background:none;border:none;font-size:18px;line-height:1;cursor:pointer;color:#6b7280;">&times;</button>
        </div>

        {{-- Upload toggle --}}
        <div style="padding:6px 16px; border-bottom:1px solid #f3f4f6;">
        @if(hasPermission('patient_documents', 'add'))
            <button onclick="toggleUploadForm()" id="uploadToggleBtn"
                    style="background:none; border:none; padding:0; font-size:12px; color:red; cursor:pointer;">
                <i class="tio-add-circle mr-1"></i> Upload documents
            </button>
            @endif
        </div>
@if(hasPermission('patient_documents', 'add'))
        {{-- Upload form (collapsed by default) --}}
        <div id="docUploadForm" style="display:none; padding:10px 16px; border-bottom:1px solid #f0f0f0; background:#fafafa;">
            <div class="d-flex gap-2 align-items-end flex-wrap">
                <div style="flex:1; min-width:110px;">
                    <label style="font-size:11px; color:#6b7280; margin-bottom:3px; display:block;">Type</label>
                    <select id="docTypeSelect" class="form-control form-control-sm">
                        <option value="report">Report</option>
                        <option value="id_proof">ID Proof</option>
                        <option value="prescription">Prescription</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div style="flex:1; min-width:110px;">
                    <label style="font-size:11px; color:#6b7280; margin-bottom:3px; display:block;">Name <span style="opacity:.5;">(e.g. X-ray)</span></label>
                    <input type="text" id="docNameInput" class="form-control form-control-sm" placeholder="Optional">
                </div>
                <div style="flex:2; min-width:150px;">
                    <label style="font-size:11px; color:#6b7280; margin-bottom:3px; display:block;">Files</label>
                    <input type="file" id="docFileInput" class="form-control form-control-sm" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                </div>
                <button class="btn btn-sm btn--primary" onclick="uploadDocs()" id="docUploadBtn" style="white-space:nowrap;">
                    <i class="tio-upload mr-1"></i> Upload
                </button>
            </div>
            <div id="docUploadErr" class="text-danger small mt-1" style="display:none;"></div>
            <div id="docUploadProgress" style="display:none; margin-top:6px;">
                <div class="progress" style="height:4px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
                </div>
            </div>
        </div>
        @endif
@if(hasPermission('patient_documents', 'list'))
        {{-- Document list --}}
        <div style="padding:12px 16px; max-height:50vh; overflow-y:auto;">
            <ul class="list-group list-group-flush mb-0" id="docList">
                @forelse($patient?->documents ?? [] as $doc)
                @php
                    $typeLabels = ['id_proof'=>'ID Proof','report'=>'Report','prescription'=>'Prescription','other'=>'Other'];
                    $typeBg     = ['id_proof'=>'#fef3c7','report'=>'#dbeafe','prescription'=>'#d1fae5','other'=>'#f3f4f6'];
                @endphp
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 doc-item" data-id="{{ $doc->id }}">
                    <span style="font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:300px;">
                        <i class="tio-file mr-1 text-muted"></i>
                        <span class="badge" style="font-size:11px; background:{{ $typeBg[$doc->document_type] ?? '#f3f4f6' }}; color:#374151; font-weight:600;">
                            {{ $typeLabels[$doc->document_type] ?? $doc->document_type }}
                        </span>
                        @if($doc->document_name)
                            <span class="text-muted ml-1" style="font-size:12px;">({{ $doc->document_name }})</span>
                        @endif
                    </span>
                    <div class="d-flex gap-1" style="flex-shrink:0;">
                    @if(hasPermission('patient_documents', 'view'))
                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-xs btn-soft-primary">
                            <i class="tio-visible"></i>
                        </a>
                        @endif
                        @if(hasPermission('patient_documents', 'delete'))
                        <button class="btn btn-xs btn-soft-danger" onclick="deleteDoc({{ $doc->id }}, this)" title="Delete">
                            <i class="tio-delete"></i>
                        </button>
                        @endif
                    </div>
                </li>
                @empty
                <li class="list-group-item text-center text-muted py-4 px-0" id="docEmptyState">
                    <i class="tio-file" style="font-size:28px;opacity:.35;display:block;margin-bottom:6px;"></i>
                    No documents yet.
                </li>
                @endforelse
            </ul>
        </div>
        @endif
</div>

@push('script_2')
<script>
const opdQuickUpdateUrl    = "{{ route('vendor.opd.quick-update', $visit->id) }}";
const docUploadUrl         = "{{ route('vendor.patient.upload-documents', $visit->patient_id) }}";
const docDeleteBaseUrl     = "{{ url('vendor/patient/' . $visit->patient_id . '/document') }}";
const csrfToken            = "{{ csrf_token() }}";

// ── Notes / Chief Complaint inline edit ─────────────────────────────────────
function toggleEdit(field) {
    const view = document.getElementById(field === 'cc' ? 'ccView' : 'notesView');
    const edit = document.getElementById(field === 'cc' ? 'ccEdit' : 'notesEdit');
    const showing = edit.style.display === 'none';
    view.style.display = showing ? 'none' : '';
    edit.style.display = showing ? '' : 'none';
}

function saveField(field) {
    const isCC    = field === 'cc';
    const value   = document.getElementById(isCC ? 'ccInput' : 'notesInput').value;
    const textEl  = document.getElementById(isCC ? 'ccText' : 'notesText');

    fetch(opdQuickUpdateUrl, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(isCC ? { chief_complaint: value } : { notes: value })
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            textEl.textContent = value || '—';
            toggleEdit(field);
        }
    })
    .catch(() => alert('Save failed. Please try again.'));
}

// ── Patient details collapse label ───────────────────────────────────────────
document.getElementById('patientFullDetails').addEventListener('show.bs.collapse', function() {
    document.getElementById('patientDetailsToggle').innerHTML = '<i class="tio-chevron-up mr-1"></i> Hide details';
});
document.getElementById('patientFullDetails').addEventListener('hide.bs.collapse', function() {
    document.getElementById('patientDetailsToggle').innerHTML = '<i class="tio-chevron-down mr-1"></i> Show full details';
});

// ── Draggable Documents Panel ────────────────────────────────────────────────
(function() {
    const panel  = document.getElementById('docsModalDialog');
    const handle = document.getElementById('docsModalHandle');
    let dragging = false, ox = 0, oy = 0;

    handle.addEventListener('mousedown', function(e) {
        dragging = true;
        handle.style.cursor = 'grabbing';
        const r = panel.getBoundingClientRect();
        // Snap from transform-based centering to absolute coords on first drag
        if (panel.style.transform) {
            panel.style.left      = r.left + 'px';
            panel.style.transform = 'none';
        }
        ox = e.clientX - r.left;
        oy = e.clientY - r.top;
        e.preventDefault();
    });
    document.addEventListener('mousemove', function(e) {
        if (!dragging) return;
        panel.style.left = (e.clientX - ox) + 'px';
        panel.style.top  = (e.clientY - oy) + 'px';
    });
    document.addEventListener('mouseup', function() { dragging = false; handle.style.cursor = 'grab'; });
})();

function openDocsPanel() {
    const panel = document.getElementById('docsModalDialog');
    // Reset position to centred on each open
    panel.style.left      = '50%';
    panel.style.top       = '80px';
    panel.style.transform = 'translateX(-50%)';
    panel.style.display   = '';
}

function closeDocsModal() {
    document.getElementById('docsModalDialog').style.display = 'none';
}

// ── Document upload ───────────────────────────────────────────────────────────
function toggleUploadForm() {
    const form = document.getElementById('docUploadForm');
    const btn  = document.getElementById('uploadToggleBtn');
    const open = form.style.display === 'none';
    form.style.display = open ? '' : 'none';
    btn.innerHTML = open
        ? '<i class="tio-close mr-1"></i> Cancel'
        : '<i class="tio-add-circle mr-1"></i> Upload documents';
}

function uploadDocs() {
    const input   = document.getElementById('docFileInput');
    const type    = document.getElementById('docTypeSelect').value;
    const name    = document.getElementById('docNameInput').value.trim();
    const btn     = document.getElementById('docUploadBtn');
    const errEl   = document.getElementById('docUploadErr');
    const progress= document.getElementById('docUploadProgress');

    errEl.style.display = 'none';
    if (!input.files.length) { errEl.textContent = 'Please select at least one file.'; errEl.style.display = ''; return; }

    const form = new FormData();
    form.append('document_type', type);
    if (name) form.append('document_name', name);
    Array.from(input.files).forEach(f => form.append('files[]', f));

    btn.disabled = true;
    progress.style.display = '';

    fetch(docUploadUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
        body: form
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) throw new Error(data.message || 'Upload failed');
        const list = document.getElementById('docList');
        const empty = document.getElementById('docEmptyState');
        if (empty) empty.remove();

        const typeLabels = { id_proof:'ID Proof', report:'Report', prescription:'Prescription', other:'Other' };
        const typeColors = { id_proof:'#fef3c7', report:'#dbeafe', prescription:'#d1fae5', other:'#f3f4f6' };

        data.documents.forEach(doc => {
            const label     = typeLabels[doc.document_type] || doc.document_type;
            const color     = typeColors[doc.document_type] || '#f3f4f6';
            const namePart  = doc.document_name
                ? `<span class="text-muted ml-1" style="font-size:12px;">(${doc.document_name})</span>`
                : '';
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center px-0 py-2 doc-item';
            li.dataset.id = doc.id;
            li.innerHTML = `
                <span style="font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:300px;">
                    <i class="tio-file mr-1 text-muted"></i>
                    <span class="badge" style="font-size:11px;background:${color};color:#374151;font-weight:600;">${label}</span>
                    ${namePart}
                </span>
                <div class="d-flex gap-1" style="flex-shrink:0;">
                    <a href="${doc.url}" target="_blank" class="btn btn-xs btn-soft-primary"><i class="tio-visible"></i></a>
                    <button class="btn btn-xs btn-soft-danger" onclick="deleteDoc(${doc.id}, this)" title="Delete"><i class="tio-delete"></i></button>
                </div>`;
            list.appendChild(li);
        });

        // Update badge count in header
        const badge = document.querySelector('#docsModalHandle .badge');
        const total = document.querySelectorAll('#docList .doc-item').length;
        if (badge) badge.textContent = total;
        else document.querySelector('#docsModalHandle span[style*="font-weight"]').insertAdjacentHTML('beforeend',
            `<span class="badge badge-soft-info ml-1">${total}</span>`);

        input.value = '';
        document.getElementById('docNameInput').value = '';
        toggleUploadForm();
    })
    .catch(err => { errEl.textContent = err.message; errEl.style.display = ''; })
    .finally(() => { btn.disabled = false; progress.style.display = 'none'; });
}

// ── Document delete ───────────────────────────────────────────────────────────
function deleteDoc(docId, btn) {
    if (!confirm('Delete this document?')) return;
    btn.disabled = true;

    fetch(`${docDeleteBaseUrl}/${docId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) throw new Error();
        const li = btn.closest('.doc-item');
        li.remove();

        // Update badge
        const total = document.querySelectorAll('#docList .doc-item').length;
        const badge = document.querySelector('#docsModalHandle .badge');
        if (badge) badge.textContent = total;
        if (total === 0) {
            document.getElementById('docList').innerHTML =
                `<li class="list-group-item text-center text-muted py-4 px-0" id="docEmptyState">
                    <i class="tio-file" style="font-size:28px;opacity:.35;display:block;margin-bottom:6px;"></i>
                    No documents yet.
                </li>`;
        }
    })
    .catch(() => { btn.disabled = false; alert('Failed to delete document.'); });
}
</script>
@endpush
@endsection
