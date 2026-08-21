@extends('layouts.vendor.app')
@section('title', 'Register OPD Visit')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-document-text" style="font-size:22px;"></i></span>
            Register OPD Visit
        </h1>
        <div class="d-flex gap-2 align-items-center">
            <button type="button" id="opdDocsBtn" onclick="openDocsPanel()"
                style="display:none;background-color: #00c9db !important;color: white !important;"
                class="btn btn-sm btn-soft-info">
                <i class="tio-file mr-1"></i> Patient Documents
                <span id="opdDocsBadge" class="badge badge-soft-primary ml-1" style="display:none;"></span>
            </button>
            <a href="{{ route('vendor.opd.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-arrow-backward"></i> Register
            </a>
        </div>
    </div>

    {{-- Floating Documents Panel --}}
    <div id="docsModalDialog" style="display:none; position:fixed; top:80px; left:50%; transform:translateX(-50%);
         width:520px; max-width:95vw; background:#fff; border-radius:10px; box-shadow:0 8px 32px rgba(0,0,0,.22);
         overflow:hidden; z-index:9999;">
        <div id="docsModalHandle" title="Drag to move"
             style="background:#f8fafc; border-bottom:1px solid #e5e7eb;
             padding:10px 16px; display:flex; align-items:center; justify-content:space-between; cursor:grab; user-select:none;">
            <div style="display:flex; align-items:center; gap:8px;">
                <span style="display:inline-grid; grid-template-columns:repeat(2,4px); gap:3px; opacity:.4; flex-shrink:0;">
                    <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                    <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                    <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                    <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                    <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                    <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                </span>
                <span style="font-weight:600; font-size:14px;">
                    <i class="tio-file mr-1"></i> Patient Documents
                    <span class="badge badge-soft-info ml-1" id="docBadgeCount" style="display:none;"></span>
                </span>
            </div>
            <button onclick="closeDocsModal()" style="background:none;border:none;font-size:18px;line-height:1;cursor:pointer;color:#6b7280;">&times;</button>
        </div>

        @if(hasPermission('patient_documents', 'add'))
        <div style="padding:6px 16px; border-bottom:1px solid #f3f4f6;">
            <button onclick="toggleUploadForm()" id="uploadToggleBtn"
                    style="background:none; border:none; padding:0; font-size:12px; color:red; cursor:pointer;">
                <i class="tio-add-circle mr-1"></i> Upload documents
            </button>
        </div>
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

        <div style="padding:12px 16px; max-height:50vh; overflow-y:auto;">
            <ul class="list-group list-group-flush mb-0" id="docList">
                <li class="list-group-item text-center text-muted py-4 px-0" id="docEmptyState">
                    <i class="tio-file" style="font-size:28px;opacity:.35;display:block;margin-bottom:6px;"></i>
                    No documents yet.
                </li>
            </ul>
        </div>
    </div>

    @php $isBooked = !empty($prefillBooking); @endphp

    {{-- Mode Toggle --}}
    @if(!$isBooked)
    <div class="row justify-content-center mb-3">
        <div class="col-lg-8">
            <div class="btn-group w-100" role="group">
                <button type="button" class="btn btn--primary mode-btn active" data-mode="walkin">
                    <i class="tio-walk"></i> Walk In
                </button>
                <button type="button" class="btn btn-outline-primary mode-btn" data-mode="booked">
                    <i class="tio-ticket"></i> Already Booked Appointment
                </button>
            </div>
        </div>
    </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">

            {{-- ── Already Booked: lookup section ── --}}
            <div id="bookedSection" {!! $isBooked ? '' : 'style="display:none;"' !!}>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <label class="input-label">Appointment ID <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" id="leadId" class="form-control"
                                    placeholder="Enter the appointment ID given to the patient"
                                    value="{{ $isBooked ? $prefillBooking['sr_id'] : '' }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn--primary" id="lookupBtn" onclick="lookupLead()">
                                        <i class="tio-search"></i> Find
                                    </button>
                                </div>
                            </div>
                            <div class="text-danger small mt-1 d-none" id="leadErr"></div>
                        </div>

                        @if($isBooked && !$prefillBooking['doctor_name'])
                        <div class="form-group mt-3 mb-0" id="bookedDoctorWrap">
                            <label class="input-label">Doctor <span class="text-danger">*</span></label>
                            <select name="booked_doctor_profile_id" id="bookedDoctorSelect" class="form-control js-select2" required>
                                <option value="">Select doctor...</option>
                                @foreach($doctors as $doc)
                                    <option value="{{ $doc->id }}">Dr. {{ $doc->employee?->f_name }} {{ $doc->employee?->l_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div id="leadPreview" style="{{ $isBooked ? '' : 'display:none;' }} margin-top:14px;">
                            <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:14px 16px; margin-bottom:12px;">
                                <p class="mb-1" style="font-size:12px; font-weight:700; color:#1e40af; text-transform:uppercase; letter-spacing:.05em;">Booking Details</p>
                                @if($isBooked && !empty($prefillBooking['service_name']))
                                <div class="mb-2" style="background:#dbeafe; border-radius:6px; padding:6px 10px; font-size:13px; font-weight:700; color:#1e3a8a;" id="previewServiceWrap">
                                    <i class="tio-medical-square-outlined" style="margin-right:4px;"></i>
                                    <span id="previewService">{{ $prefillBooking['service_name'] }}</span>
                                </div>
                                @else
                                <div class="mb-2 d-none" style="background:#dbeafe; border-radius:6px; padding:6px 10px; font-size:13px; font-weight:700; color:#1e3a8a;" id="previewServiceWrap">
                                    <i class="tio-medical-square-outlined" style="margin-right:4px;"></i>
                                    <span id="previewService"></span>
                                </div>
                                @endif
                                <div class="row" style="font-size:13px;">
                                    <div class="col-sm-6 mb-1"><span class="text-muted">Patient: </span><strong id="previewPatient">{{ $prefillBooking['patient_name'] ?? '' }}</strong></div>
                                    <div class="col-sm-6 mb-1"><span class="text-muted">Phone: </span><strong id="previewPhone">{{ $prefillBooking['patient_phone'] ?? '' }}</strong></div>
                                    <div class="col-sm-6 mb-1"><span class="text-muted">Doctor: </span><strong id="previewDoctor">{{ $prefillBooking['doctor_name'] ?? 'Not specified' }}</strong></div>
                                    <div class="col-sm-6 mb-1"><span class="text-muted">Date: </span><strong id="previewDate">{{ $prefillBooking['appointment_date'] ?? '' }}</strong></div>
                                    <div class="col-sm-6 mb-0"><span class="text-muted">Slot/Time: </span><strong id="previewSlot">{{ $prefillBooking['slot_label'] ?? '' }}</strong></div>
                                    <div class="col-sm-6 mb-0" id="previewReasonRow" {{ ($isBooked && !empty($prefillBooking['reason'])) ? '' : 'style="display:none;"' }}><span class="text-muted">Reason: </span><span id="previewReason">{{ $prefillBooking['reason'] ?? '' }}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Main OPD form (walk-in always shown, booked hides patient block) ── --}}
            <form action="{{ route('vendor.opd.store') }}" method="POST" id="opdForm">
                @csrf
                <input type="hidden" name="booking_mode" id="bookingMode" value="{{ $isBooked ? 'booked' : 'walkin' }}">
                <input type="hidden" name="service_request_id" id="srId" value="{{ $isBooked ? $prefillBooking['sr_id'] : '' }}">

                {{-- Patient & Doctor block — only rendered in walk-in mode --}}
                @if(!$isBooked)
                <div id="walkinPatientBlock">
                    @include('hmis::vendor.opd._form', ['visit' => null])
                </div>
                @endif

                {{-- Chief Complaint, Vitals, Notes — always shown once regardless of mode --}}
                @include('hmis::vendor.opd._form_vitals_only', ['visit' => null])

                <div class="d-flex gap-2 mt-3 justify-content-end w-100">
                    <a href="{{ route('vendor.opd.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Ready for Doctor</button>
                </div>
            </form>

        </div>
    </div>
</div>

@push('script_2')
<script>
const lookupUrl = "{{ route('vendor.appointment.lookup-lead') }}";

// ── Mode toggle ──────────────────────────────────────────────────────────────
document.querySelectorAll('.mode-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.mode-btn').forEach(b => {
            b.classList.remove('active', 'btn--primary');
            b.classList.add('btn-outline-primary');
        });
        this.classList.add('active', 'btn--primary');
        this.classList.remove('btn-outline-primary');

        const mode = this.dataset.mode;
        document.getElementById('bookingMode').value = mode;
        document.getElementById('bookedSection').style.display = mode === 'booked' ? '' : 'none';
        const wb = document.getElementById('walkinPatientBlock');
        if (wb) wb.style.display = mode === 'walkin' ? '' : 'none';

        // Toggle required off on all walk-in fields when hidden
        const walkinRequired = ['patientSelect', 'doctor_profile_id', 'visit_date', 'visit_type'];
        walkinRequired.forEach(function(name) {
            const el = document.getElementById(name) || document.querySelector('[name="' + name + '"]');
            if (el) el.required = (mode === 'walkin');
        });

        if (mode === 'booked') initBookedSelect2();
    });
});

// Built on first show, not at load: the pane starts display:none and Select2 measures its
// container, so building it early leaves a zero-width box that never recovers.
let bookedSelect2Ready = false;
function initBookedSelect2() {
    if (bookedSelect2Ready || typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
    jQuery('#bookedDoctorSelect').select2({ placeholder: 'Select doctor...', width: '100%' });
    bookedSelect2Ready = true;
}

// A page opened straight into booked mode has the pane visible already.
document.addEventListener('DOMContentLoaded', function () {
    const pane = document.getElementById('bookedSection');
    if (pane && pane.style.display !== 'none') initBookedSelect2();
});

// ── Already Booked: AJAX lookup ──────────────────────────────────────────────
function lookupLead() {
    const id     = document.getElementById('leadId').value.trim();
    const errEl  = document.getElementById('leadErr');
    const preview= document.getElementById('leadPreview');

    errEl.classList.add('d-none');
    preview.style.display = 'none';
    document.getElementById('srId').value = '';

    if (!id) { errEl.textContent = 'Please enter an appointment ID.'; errEl.classList.remove('d-none'); return; }

    const btn = document.getElementById('lookupBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    fetch(`${lookupUrl}?id=${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            if (!ok) { errEl.textContent = data.error || 'Not found.'; errEl.classList.remove('d-none'); return; }

            document.getElementById('previewPatient').textContent = data.patient_name || '—';
            document.getElementById('previewPhone').textContent   = data.patient_phone || '—';
            document.getElementById('previewDoctor').textContent  = data.doctor_name || 'Not specified';
            document.getElementById('previewDate').textContent    = data.appointment_date || '—';
            document.getElementById('previewSlot').textContent    = data.slot_label || data.appointment_time || '—';

            const serviceWrap = document.getElementById('previewServiceWrap');
            if (data.service_name) {
                document.getElementById('previewService').textContent = data.service_name;
                serviceWrap.classList.remove('d-none');
            } else {
                serviceWrap.classList.add('d-none');
            }

            const reasonRow = document.getElementById('previewReasonRow');
            if (data.reason) {
                document.getElementById('previewReason').textContent = data.reason;
                reasonRow.style.display = '';
            } else {
                reasonRow.style.display = 'none';
            }

            document.getElementById('srId').value = id;
            preview.style.display = '';
            const walkinBlock = document.getElementById('walkinPatientBlock');
            if (walkinBlock) walkinBlock.remove();

            if (data.patient_id) setPatientDocs(data.patient_id);

            // Show doctor selector if booking has no assigned doctor
            const bookedDoctorWrap = document.getElementById('bookedDoctorWrap');
            if (bookedDoctorWrap) {
                const needsDoctor = !data.doctor_name;
                bookedDoctorWrap.style.display = needsDoctor ? '' : 'none';
                const sel = document.getElementById('bookedDoctorSelect');
                if (sel) {
                    sel.required = needsDoctor;
                    if (!needsDoctor) sel.value = '';
                }
            }
        })
        .catch(() => { errEl.textContent = 'Something went wrong.'; errEl.classList.remove('d-none'); })
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="tio-search"></i> Find'; });
}

document.getElementById('leadId').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); lookupLead(); } });

// ── Patient Documents Panel ───────────────────────────────────────────────────
var docUploadUrl     = '';
var docDeleteBaseUrl = '';
var docListBaseUrl   = '{{ url("vendor/patient") }}';
const csrfToken      = '{{ csrf_token() }}';

(function() {
    const panel  = document.getElementById('docsModalDialog');
    const handle = document.getElementById('docsModalHandle');
    if (!panel) return;
    let dragging = false, ox = 0, oy = 0;
    handle.addEventListener('mousedown', function(e) {
        dragging = true; handle.style.cursor = 'grabbing';
        const r = panel.getBoundingClientRect();
        if (panel.style.transform) { panel.style.left = r.left + 'px'; panel.style.transform = 'none'; }
        ox = e.clientX - r.left; oy = e.clientY - r.top; e.preventDefault();
    });
    document.addEventListener('mousemove', function(e) {
        if (!dragging) return;
        panel.style.left = (e.clientX - ox) + 'px'; panel.style.top = (e.clientY - oy) + 'px';
    });
    document.addEventListener('mouseup', function() { dragging = false; handle.style.cursor = 'grab'; });
})();

function setPatientDocs(patientId) {
    if (!patientId || !document.getElementById('docsModalDialog')) return;
    docUploadUrl     = docListBaseUrl + '/' + patientId + '/upload-documents';
    docDeleteBaseUrl = docListBaseUrl + '/' + patientId + '/document';
    document.getElementById('opdDocsBtn').style.display = '';
    loadDocs(patientId);
}

function loadDocs(patientId) {
    fetch(docListBaseUrl + '/' + patientId + '/documents', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) return;
        const list = document.getElementById('docList');
        list.innerHTML = '';
        const typeLabels = { id_proof:'ID Proof', report:'Report', prescription:'Prescription', other:'Other' };
        const typeColors = { id_proof:'#fef3c7', report:'#dbeafe', prescription:'#d1fae5', other:'#f3f4f6' };
        if (!data.documents.length) {
            list.innerHTML = '<li class="list-group-item text-center text-muted py-4 px-0" id="docEmptyState"><i class="tio-file" style="font-size:28px;opacity:.35;display:block;margin-bottom:6px;"></i>No documents yet.</li>';
        } else {
            data.documents.forEach(doc => {
                const label = typeLabels[doc.document_type] || doc.document_type;
                const color = typeColors[doc.document_type] || '#f3f4f6';
                const namePart = doc.document_name ? `<span class="text-muted ml-1" style="font-size:12px;">(${doc.document_name})</span>` : '';
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
        }
        updateDocBadge(data.documents.length);
    });
}

function openDocsPanel() {
    const panel = document.getElementById('docsModalDialog');
    panel.style.left = '50%'; panel.style.top = '80px'; panel.style.transform = 'translateX(-50%)';
    panel.style.display = '';
}
function closeDocsModal() { document.getElementById('docsModalDialog').style.display = 'none'; }

function toggleUploadForm() {
    const form = document.getElementById('docUploadForm');
    const btn  = document.getElementById('uploadToggleBtn');
    const open = form.style.display === 'none';
    form.style.display = open ? '' : 'none';
    btn.innerHTML = open ? '<i class="tio-close mr-1"></i> Cancel' : '<i class="tio-add-circle mr-1"></i> Upload documents';
}

function updateDocBadge(total) {
    const badge    = document.getElementById('docBadgeCount');
    const btnBadge = document.getElementById('opdDocsBadge');
    if (total > 0) {
        if (badge)    { badge.textContent = total; badge.style.display = ''; }
        if (btnBadge) { btnBadge.textContent = total; btnBadge.style.display = ''; }
    } else {
        if (badge)    badge.style.display = 'none';
        if (btnBadge) btnBadge.style.display = 'none';
    }
}

function uploadDocs() {
    const input    = document.getElementById('docFileInput');
    const type     = document.getElementById('docTypeSelect').value;
    const name     = document.getElementById('docNameInput').value.trim();
    const btn      = document.getElementById('docUploadBtn');
    const errEl    = document.getElementById('docUploadErr');
    const progress = document.getElementById('docUploadProgress');
    errEl.style.display = 'none';
    if (!input.files.length) { errEl.textContent = 'Please select at least one file.'; errEl.style.display = ''; return; }
    const form = new FormData();
    form.append('document_type', type);
    if (name) form.append('document_name', name);
    Array.from(input.files).forEach(f => form.append('files[]', f));
    btn.disabled = true; progress.style.display = '';
    fetch(docUploadUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
        body: form
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) throw new Error(data.message || 'Upload failed');
        const list  = document.getElementById('docList');
        const empty = document.getElementById('docEmptyState');
        if (empty) empty.remove();
        const typeLabels = { id_proof:'ID Proof', report:'Report', prescription:'Prescription', other:'Other' };
        const typeColors = { id_proof:'#fef3c7', report:'#dbeafe', prescription:'#d1fae5', other:'#f3f4f6' };
        data.documents.forEach(doc => {
            const label = typeLabels[doc.document_type] || doc.document_type;
            const color = typeColors[doc.document_type] || '#f3f4f6';
            const namePart = doc.document_name ? `<span class="text-muted ml-1" style="font-size:12px;">(${doc.document_name})</span>` : '';
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
        updateDocBadge(document.querySelectorAll('#docList .doc-item').length);
        input.value = ''; document.getElementById('docNameInput').value = '';
        toggleUploadForm();
    })
    .catch(err => { errEl.textContent = err.message; errEl.style.display = ''; })
    .finally(() => { btn.disabled = false; progress.style.display = 'none'; });
}

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
        btn.closest('.doc-item').remove();
        const total = document.querySelectorAll('#docList .doc-item').length;
        updateDocBadge(total);
        if (total === 0) {
            document.getElementById('docList').innerHTML =
                `<li class="list-group-item text-center text-muted py-4 px-0" id="docEmptyState">
                    <i class="tio-file" style="font-size:28px;opacity:.35;display:block;margin-bottom:6px;"></i>
                    No documents yet.</li>`;
        }
    })
    .catch(() => { btn.disabled = false; alert('Failed to delete document.'); });
}

// Walk-in: patient select change (supports both native and Select2)
$(document).on('change', '#patientSelect', function() {
    var val = $(this).val();
    if (val && val !== 'add_new') setPatientDocs(val);
    else { var b = document.getElementById('opdDocsBtn'); if (b) b.style.display = 'none'; }
});
// Pre-selected patient on page load
$(function() {
    var val = $('#patientSelect').val();
    if (val && val !== 'add_new') setPatientDocs(val);
});

// ── Pre-load docs panel when page opens with a known booked patient ──────────
@if($isBooked && !empty($prefillBooking['patient_id']))
setPatientDocs({{ $prefillBooking['patient_id'] }});
@endif

// ── Disable walk-in required fields when pre-loaded in booked mode ───────────
@if($isBooked)
(function() {
    const walkinRequired = ['patientSelect', 'doctor_profile_id', 'visit_date', 'visit_type'];
    walkinRequired.forEach(function(name) {
        const el = document.getElementById(name) || document.querySelector('[name="' + name + '"]');
        if (el) el.required = false;
    });
})();
@endif

// ── Prevent submit in booked mode without a valid lookup ─────────────────────
document.getElementById('opdForm').addEventListener('submit', function (e) {
    if (document.getElementById('bookingMode').value === 'booked' && !document.getElementById('srId').value) {
        e.preventDefault();
        document.getElementById('leadErr').textContent = 'Please look up a valid appointment ID first.';
        document.getElementById('leadErr').classList.remove('d-none');
    }
});
</script>
@endpush
@endsection
