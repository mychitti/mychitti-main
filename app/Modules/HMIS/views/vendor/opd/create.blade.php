@extends('layouts.vendor.app')
@section('title', 'Register OPD Visit')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-document-text" style="font-size:22px;"></i></span>
            Register OPD Visit
        </h1>
        <a href="{{ route('vendor.opd.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-arrow-backward"></i> Register
        </a>
    </div>

    @php $isBooked = !empty($prefillBooking); @endphp

    {{-- Mode Toggle --}}
    <div class="row justify-content-center mb-3">
        <div class="col-lg-8">
            <div class="btn-group w-100" role="group">
                <button type="button" class="btn {{ $isBooked ? 'btn-outline-primary' : 'btn--primary' }} mode-btn{{ $isBooked ? '' : ' active' }}" data-mode="walkin">
                    <i class="tio-walk"></i> Walk In
                </button>
                <button type="button" class="btn {{ $isBooked ? 'btn--primary' : 'btn-outline-primary' }} mode-btn{{ $isBooked ? ' active' : '' }}" data-mode="booked">
                    <i class="tio-ticket"></i> Already Booked Appointment
                </button>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">

            {{-- ── Already Booked: lookup section ── --}}
            <div id="bookedSection" {{ $isBooked ? '' : 'style="display:none;"' }}>
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
    });
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
