{{--
    Shared prescription form partial.
    Expects:  $rx (Prescription|null), $doctors, $patients,
              $appointment (Appointment|null), $serviceRequest (ServiceRequest|null),
              $patient (Patient|null), $doctorProfileId (int|null),
              $formAction (string), $formMethod ('POST')
--}}

<form action="{{ $formAction }}" method="POST" id="rxForm">
    @csrf
    @if(isset($rx))
        @method('POST') {{-- update route uses POST --}}
    @endif

    {{-- Hidden context fields --}}
    @if(isset($appointment) && $appointment)
        <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">
    @endif
    @if(isset($serviceRequest) && $serviceRequest)
        <input type="hidden" name="service_request_id" value="{{ $serviceRequest->id }}">
    @endif

    {{-- Dynamic hidden fields filled by JS when appointment mode is used --}}
    @if(!isset($rx) && !isset($appointment) && !isset($serviceRequest))
        <input type="hidden" name="appointment_id"    id="rxApptId">
        <input type="hidden" name="patient_id"        id="rxPatientId">
        <input type="hidden" name="doctor_profile_id" id="rxDoctorId">
    @endif

    <div class="row">
        {{-- LEFT: Patient + Doctor + Diagnosis --}}
        <div class="col-lg-7">

            @if(!isset($rx) && !isset($appointment) && !isset($serviceRequest))
                {{-- ── Mode toggle: Appointment vs Manual ── --}}
                <div class="card mb-3">
                    <div class="card-body pb-2">
                        <div class="d-flex gap-3 mb-3">
                            <label class="d-flex align-items-center gap-2 mb-0" style="cursor:pointer; font-weight:600;">
                                <input type="radio" name="_rx_mode" value="appointment" id="modeAppt" checked
                                    onchange="rxModeSwitch('appointment')">
                                <i class="tio-calendar" style="color:#2563eb;"></i> Select from Appointment
                            </label>
                            <label class="d-flex align-items-center gap-2 mb-0" style="cursor:pointer; font-weight:600;">
                                <input type="radio" name="_rx_mode" value="manual" id="modeManual"
                                    onchange="rxModeSwitch('manual')">
                                <i class="tio-user" style="color:#6b7280;"></i> Enter manually
                            </label>
                        </div>

                        {{-- Appointment picker --}}
                        <div id="rxApptBlock">
                            <label class="input-label">Appointment <span class="text-danger">*</span></label>
                            <select id="rxApptSelect" class="form-control" onchange="rxApptSelected(this)">
                                <option value="">-- Select appointment --</option>
                                @foreach($appointments ?? [] as $appt)
                                    <option value="{{ $appt->id }}"
                                        data-patient-id="{{ $appt->patient_id }}"
                                        data-patient-name="{{ $appt->patient?->name }}"
                                        data-patient-uid="{{ $appt->patient?->patient_uid }}"
                                        data-doctor-id="{{ $appt->doctor_profile_id }}"
                                        data-doctor-name="Dr. {{ $appt->doctorProfile?->employee?->f_name }} {{ $appt->doctorProfile?->employee?->l_name }}">
                                        {{ $appt->appointment_date?->format('d M Y') }}
                                        — {{ $appt->patient?->name }}
                                        ({{ $appt->patient?->patient_uid }})
                                        — Dr. {{ $appt->doctorProfile?->employee?->f_name }} {{ $appt->doctorProfile?->employee?->l_name }}
                                        <span style="text-transform:capitalize;">[{{ $appt->status }}]</span>
                                    </option>
                                @endforeach
                            </select>
                            <div id="rxApptPreview" class="mt-2" style="display:none;">
                                <div class="d-flex align-items-center gap-3 p-2 rounded" style="background:#f0fdf4; border:1px solid #bbf7d0;">
                                    <div class="rx-avatar" id="rxPreviewAvatar"></div>
                                    <div>
                                        <p class="mb-0 font-weight-bold" id="rxPreviewPatient"></p>
                                        <small class="text-muted" id="rxPreviewDoctor"></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Manual patient + doctor --}}
                        <div id="rxManualBlock" style="display:none;">
                            <div class="form-group">
                                <label class="input-label">Patient <span class="text-danger">*</span></label>
                                <select id="rxManualPatient" class="form-control" onchange="rxManualPatientSet(this)">
                                    <option value="">-- Select patient --</option>
                                    @foreach($patients as $p)
                                        <option value="{{ $p->id }}"
                                            {{ old('patient_id') == $p->id ? 'selected' : '' }}>
                                            {{ $p->name }} ({{ $p->patient_uid }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="input-label">Doctor <span class="text-danger">*</span></label>
                                <select id="rxManualDoctor" class="form-control" onchange="rxManualDoctorSet(this)">
                                    <option value="">-- Select doctor --</option>
                                    @foreach($doctors as $d)
                                        <option value="{{ $d->id }}"
                                            {{ old('doctor_profile_id') == $d->id ? 'selected' : '' }}>
                                            Dr. {{ $d->employee?->f_name }} {{ $d->employee?->l_name }}
                                            {{ $d->specialization ? '— '.$d->specialization : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

            @elseif(isset($appointment) && $appointment)
                {{-- Pre-filled from appointment --}}
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Patient</h5></div>
                    <div class="card-body">
                        @php $p = $appointment->patient; @endphp
                        <input type="hidden" name="patient_id"        value="{{ $p?->id }}">
                        <input type="hidden" name="doctor_profile_id" value="{{ $appointment->doctor_profile_id }}">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rx-avatar">{{ strtoupper(substr($p?->name ?? 'P', 0, 1)) }}</div>
                            <div>
                                <p class="mb-0 font-weight-bold">{{ $p?->name }}</p>
                                <small class="text-muted">{{ $p?->patient_uid }}
                                    &bull; Dr. {{ $appointment->doctorProfile?->employee?->f_name }} {{ $appointment->doctorProfile?->employee?->l_name }}</small>
                            </div>
                        </div>
                    </div>
                </div>

            @elseif(isset($serviceRequest) && $serviceRequest)
                {{-- Pre-filled from service request --}}
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Patient</h5></div>
                    <div class="card-body">
                        @php $p = $patient; @endphp
                        <input type="hidden" name="patient_id"        value="{{ $p?->id }}">
                        <input type="hidden" name="doctor_profile_id" value="{{ $doctorProfileId }}">
                        @if($serviceRequest->item)
                            <div class="mb-3 px-3 py-2 rounded" style="background:#eff6ff; border:1px solid #bfdbfe; font-size:13px;">
                                <i class="tio-stethoscope mr-1" style="color:#2563eb;"></i>
                                <strong>{{ $serviceRequest->item->name }}</strong>
                                <span class="text-muted ml-1">#{{ $serviceRequest->id }}</span>
                            </div>
                        @endif
                        @if($p)
                        <div class="d-flex align-items-center gap-3">
                            <div class="rx-avatar">{{ strtoupper(substr($p->name ?? 'P', 0, 1)) }}</div>
                            <div>
                                <p class="mb-0 font-weight-bold">{{ $p->name }}</p>
                                <small class="text-muted">{{ $p->patient_uid }}</small>
                            </div>
                        </div>
                        @else
                        <p class="text-muted mb-2" style="font-size:13px;">No patient record found for this request.</p>
                        <div class="form-group mb-0">
                            <label class="input-label">Select Patient <span class="text-danger">*</span></label>
                            <select name="patient_id" class="form-control" required>
                                <option value="">-- Select patient --</option>
                                @foreach($patients as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->patient_uid }})</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </div>
                </div>

            @else
                {{-- Edit mode: pre-filled from existing rx --}}
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Patient</h5></div>
                    <div class="card-body">
                        <input type="hidden" name="patient_id"        value="{{ $rx->patient_id }}">
                        <input type="hidden" name="doctor_profile_id" value="{{ $rx->doctor_profile_id }}">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rx-avatar">{{ strtoupper(substr($rx->patient?->name ?? 'P', 0, 1)) }}</div>
                            <div>
                                <p class="mb-0 font-weight-bold">{{ $rx->patient?->name }}</p>
                                <small class="text-muted">{{ $rx->patient?->patient_uid }}
                                    &bull; Dr. {{ $rx->doctorProfile?->employee?->f_name }} {{ $rx->doctorProfile?->employee?->l_name }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Diagnosis --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Diagnosis</h5></div>
                <div class="card-body">
                    <textarea name="diagnosis" class="form-control" rows="3"
                        placeholder="Primary diagnosis / chief complaint...">{{ old('diagnosis', $rx->diagnosis ?? '') }}</textarea>
                </div>
            </div>

            {{-- Notes --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Doctor's Notes / Advice</h5></div>
                <div class="card-body">
                    <textarea name="notes" class="form-control" rows="3"
                        placeholder="Rest, diet, precautions, advice...">{{ old('notes', $rx->notes ?? '') }}</textarea>
                </div>
            </div>

            {{-- Follow-up --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Follow-Up Date</h5></div>
                <div class="card-body">
                    <input type="date" name="follow_up_date" class="form-control" style="max-width:220px;"
                        value="{{ old('follow_up_date', isset($rx) && $rx->follow_up_date ? $rx->follow_up_date->format('Y-m-d') : '') }}">
                </div>
            </div>

        </div>

        {{-- RIGHT: Medicines --}}
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Medicines</h5>
                    <button type="button" class="btn btn-sm btn--primary" onclick="addMedRow()">
                        <i class="tio-add"></i> Add manually
                    </button>
                </div>

                {{-- Pharmacy search --}}
                <div class="px-2 pt-2 pb-1" style="border-bottom:1px solid #f0f0f0;">
                    <div class="input-group input-group-sm" style="position:relative;">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0">
                                <i class="tio-search" style="font-size:14px;color:#6b7280;"></i>
                            </span>
                        </div>
                        <input type="text" id="pharmacySearch" class="form-control border-left-0"
                            placeholder="Search pharmacy inventory..."
                            autocomplete="off"
                            oninput="pharmacySearchDebounce(this.value)">
                    </div>
                    <ul id="pharmacySuggestions"
                        style="display:none; list-style:none; margin:0; padding:4px 0;
                               border:1px solid #e5e7eb; border-top:none; border-radius:0 0 8px 8px;
                               background:#fff; max-height:200px; overflow-y:auto; position:relative; z-index:99;">
                    </ul>
                </div>

                <div class="card-body p-2" id="medTable">
                    {{-- Existing rows --}}
                    @if(isset($rx) && $rx->items->count())
                        @foreach($rx->items as $i => $item)
                            @include('hmis::vendor.prescription._med_row', ['i' => $i, 'item' => $item])
                        @endforeach
                    @else
                        @include('hmis::vendor.prescription._med_row', ['i' => 0, 'item' => null])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Footer actions --}}
    <div class="d-flex gap-2 mt-1 mb-4">
        <button type="submit" class="btn btn--primary" name="action" value="draft">
            <i class="tio-save"></i> Save Draft
        </button>
        <button type="submit" class="btn btn-success" name="finalize" value="1"
            onclick="document.querySelector('[name=finalize]').value=1;">
            <i class="tio-checkmark-circle"></i> Finalize &amp; Print
        </button>
        <a href="{{ route('vendor.prescription.list') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

{{-- Hidden template row --}}
<template id="medRowTpl">
    @include('hmis::vendor.prescription._med_row', ['i' => '__IDX__', 'item' => null])
</template>

<style>
.rx-avatar {
    width:44px; height:44px; border-radius:50%;
    background:#dbeafe; color:#2563eb;
    font-weight:700; font-size:18px;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.med-row { background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:10px; margin-bottom:8px; }
.med-row input, .med-row select { font-size:13px; }
</style>

@push('script_2')
<script>
let medIdx = {{ isset($rx) ? max(0, $rx->items->count() - 1) : 0 }};
const pharmacySearchUrl = "{{ route('vendor.prescription.search-medicines') }}";

function addMedRow(prefill) {
    medIdx++;
    const tpl = document.getElementById('medRowTpl').innerHTML
        .replace(/__IDX__/g, medIdx);
    const div = document.createElement('div');
    div.innerHTML = tpl;
    const row = div.firstElementChild;
    if (prefill) {
        const nameInput = row.querySelector('input[name$="[medicine_name]"]');
        if (nameInput) nameInput.value = prefill.name;
        const invInput = row.querySelector('.med-inv-id');
        if (invInput && prefill.id) invInput.value = prefill.id;
    }
    document.getElementById('medTable').appendChild(row);
    if (prefill) row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function removeMedRow(btn) {
    const rows = document.querySelectorAll('#medTable .med-row');
    if (rows.length <= 1) { btn.closest('.med-row').querySelectorAll('input,select').forEach(el => el.value = ''); return; }
    btn.closest('.med-row').remove();
}

// ── Pharmacy search ───────────────────────────────────────
let _pharmTimer = null;
function pharmacySearchDebounce(val) {
    clearTimeout(_pharmTimer);
    const ul = document.getElementById('pharmacySuggestions');
    if (val.length < 2) { ul.style.display = 'none'; return; }
    _pharmTimer = setTimeout(() => pharmacyFetch(val), 280);
}

function pharmacyFetch(q) {
    fetch(`${pharmacySearchUrl}?q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(items => {
            const ul = document.getElementById('pharmacySuggestions');
            if (!items.length) { ul.style.display = 'none'; return; }
            ul.innerHTML = items.map(it => `
                <li onclick="pharmacySelect(${JSON.stringify(it).replace(/"/g, '&quot;')})"
                    style="padding:7px 12px; cursor:pointer; font-size:13px; border-bottom:1px solid #f3f4f6;"
                    onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background=''">
                    <strong>${it.name}</strong>
                    ${it.price > 0 ? `<span style="float:right;color:#059669;font-size:12px;">₹${parseFloat(it.price).toFixed(0)}</span>` : ''}
                </li>`).join('');
            ul.style.display = 'block';
        })
        .catch(() => {});
}

function pharmacySelect(item) {
    const firstNameInput = document.querySelector('#medTable .med-row input[name$="[medicine_name]"]');
    if (firstNameInput && firstNameInput.value.trim() === '') {
        firstNameInput.value = item.name;
        const invInput = firstNameInput.closest('.med-row').querySelector('.med-inv-id');
        if (invInput) invInput.value = item.id;
    } else {
        addMedRow(item);
    }
    document.getElementById('pharmacySearch').value = '';
    document.getElementById('pharmacySuggestions').style.display = 'none';
}

// Close suggestions on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('#pharmacySearch') && !e.target.closest('#pharmacySuggestions')) {
        document.getElementById('pharmacySuggestions').style.display = 'none';
    }
});

// ── Mode toggle: Appointment vs Manual ───────────────────
function rxModeSwitch(mode) {
    const apptBlock   = document.getElementById('rxApptBlock');
    const manualBlock = document.getElementById('rxManualBlock');
    if (!apptBlock) return; // not in standalone create mode

    if (mode === 'appointment') {
        apptBlock.style.display   = '';
        manualBlock.style.display = 'none';
        // Clear manual selections
        document.getElementById('rxManualPatient').value = '';
        document.getElementById('rxManualDoctor').value  = '';
        rxClearHidden();
    } else {
        apptBlock.style.display   = 'none';
        manualBlock.style.display = '';
        // Clear appointment selection
        document.getElementById('rxApptSelect').value = '';
        document.getElementById('rxApptPreview').style.display = 'none';
        rxClearHidden();
    }
}

function rxApptSelected(sel) {
    const opt     = sel.options[sel.selectedIndex];
    const preview = document.getElementById('rxApptPreview');

    if (!sel.value) {
        rxClearHidden();
        preview.style.display = 'none';
        return;
    }

    document.getElementById('rxApptId').value    = sel.value;
    document.getElementById('rxPatientId').value  = opt.dataset.patientId;
    document.getElementById('rxDoctorId').value   = opt.dataset.doctorId;

    document.getElementById('rxPreviewAvatar').textContent  = (opt.dataset.patientName || 'P').charAt(0).toUpperCase();
    document.getElementById('rxPreviewPatient').textContent = opt.dataset.patientName + ' (' + opt.dataset.patientUid + ')';
    document.getElementById('rxPreviewDoctor').textContent  = opt.dataset.doctorName;
    preview.style.display = '';
}

function rxManualPatientSet(sel) {
    document.getElementById('rxPatientId').value = sel.value;
}
function rxManualDoctorSet(sel) {
    document.getElementById('rxDoctorId').value = sel.value;
}

function rxClearHidden() {
    ['rxApptId', 'rxPatientId', 'rxDoctorId'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
}

// Validate before submit
document.getElementById('rxForm')?.addEventListener('submit', function(e) {
    const apptBlock = document.getElementById('rxApptBlock');
    if (!apptBlock) return; // not in toggle mode

    const mode      = document.querySelector('input[name="_rx_mode"]:checked')?.value;
    const patientId = document.getElementById('rxPatientId')?.value;
    const doctorId  = document.getElementById('rxDoctorId')?.value;

    if (!patientId || !doctorId) {
        e.preventDefault();
        alert(mode === 'appointment'
            ? 'Please select an appointment.'
            : 'Please select both a patient and a doctor.');
    }
});
</script>
@endpush
