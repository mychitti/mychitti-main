@extends('layouts.vendor.app')
@section('title', 'Book Appointment')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-calendar" style="font-size:22px;"></i></span>
                Book Appointment
            </h1>
            <a href="{{ route('vendor.appointment.list') }}" class="btn btn-outline-secondary btn-sm">
                <i class="tio-arrow-backward"></i> Back
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">

            {{-- Mode Toggle --}}
            <div class="card mb-3">
                <div class="card-body p-2">
                    <div class="d-flex gap-2" id="modeToggle">
                        <button type="button" class="btn btn--primary flex-fill mode-btn active" data-mode="walkin">
                            <i class="tio-walk"></i> Walk In
                        </button>
                        <button type="button" class="btn btn-outline-primary flex-fill mode-btn" data-mode="lead">
                            <i class="tio-ticket"></i> Already Booked Appointment
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── WALK-IN FORM ── --}}
            <div class="card" id="walkinForm">
                <div class="card-body">
                    <form action="{{ route('vendor.appointment.store') }}" class="row" method="POST">
                        @csrf

                        <input type="hidden" name="slot_id" id="selectedSlotId">
                        <input type="hidden" name="appointment_time" id="selectedSlotTime">

                        {{-- Patient --}}
                        <div class="form-group col-md-6">
                            <label class="input-label">Patient <span class="text-danger">*</span></label>
                            <select name="patient_id" id="patientSelect" class="form-control w-100" required>
                                @if(old('patient_id'))
                                    <option value="{{ old('patient_id') }}" selected>{{ old('patient_id') }}</option>
                                @endif
                            </select>
                            @error('patient_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- Doctor --}}
                        <div class="form-group col-md-6">
                            <label class="input-label">Doctor <span class="text-danger">*</span></label>
                            <select name="doctor_profile_id" id="doctorSelect" class="form-control w-100" required>
                                @if(old('doctor_profile_id'))
                                    <option value="{{ old('doctor_profile_id') }}" selected>{{ old('doctor_profile_id') }}</option>
                                @endif
                            </select>
                            @error('doctor_profile_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- Date --}}
                        <div class="form-group col-md-6">
                            <label class="input-label">Appointment Date <span class="text-danger">*</span></label>
                            <input type="date" name="appointment_date" id="appointmentDate" class="form-control"
                                value="{{ old('appointment_date', date('Y-m-d')) }}"
                                min="{{ date('Y-m-d') }}" required>
                            @error('appointment_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- Reason --}}
                        <div class="form-group col-md-6">
                            <label class="input-label">Reason / Chief Complaint</label>
                            <textarea name="reason" class="form-control" rows="2"
                                placeholder="Optional">{{ old('reason') }}</textarea>
                        </div>

                        {{-- Slot Grid --}}
                        <div class="form-group col-12" id="slotSection" style="display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="input-label mb-0">Available Slots</label>
                                <a href="#" id="switchToManual" class="small btn btn-outline-success btn-sm">Enter time manually</a>
                            </div>
                            <div id="slotGrid" class="slot-grid"></div>
                        </div>

                        {{-- Manual Time --}}
                        <div class="form-group col-12" id="manualSection" style="display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="input-label mb-0">Appointment Time <span class="text-danger">*</span></label>
                                <a href="#" id="switchToSlots" class="small btn btn-outline-info btn-sm" style="display:none;">Pick from slots</a>
                            </div>
                            <input type="time" name="manual_time" id="manualTime" class="form-control"
                                value="{{ old('appointment_time') }}">
                            @error('appointment_time')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn--primary w-100">
                                <i class="tio-calendar-add"></i> Book Walk-In Appointment
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── ALREADY BOOKED FORM ── --}}
            <div class="card" id="leadForm" style="display:none;">
                <div class="card-body">
                    {{-- Step 1: enter ID --}}
                    <div class="form-group">
                        <label class="input-label">Appointment ID <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" id="leadId" class="form-control" placeholder="Enter the appointment ID given to the patient">
                            <div class="input-group-append">
                                <button type="button" class="btn btn--primary" id="lookupBtn" onclick="lookupLead()">
                                    <i class="tio-search"></i> Find
                                </button>
                            </div>
                        </div>
                        <div class="text-danger small mt-1 d-none" id="leadErr"></div>
                    </div>

                    {{-- Preview card (shown after lookup) --}}
                    <div id="leadPreview" style="display:none;">
                        <div class="card border mb-3" style="background:#f8fbff;">
                            <div class="card-body py-3">
                                <h6 class="font-weight-bold mb-3" style="color:#1e3a5f;">
                                    <i class="tio-verified" style="color:#2563eb;"></i> Booking Details
                                </h6>
                                <div class="row">
                                    <div class="col-sm-6 mb-2">
                                        <span class="text-muted small d-block">Patient</span>
                                        <strong id="previewPatient">—</strong>
                                        <span class="text-muted small" id="previewPhone"></span>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <span class="text-muted small d-block">Doctor</span>
                                        <strong id="previewDoctor">—</strong>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <span class="text-muted small d-block">Date</span>
                                        <strong id="previewDate">—</strong>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <span class="text-muted small d-block">Slot / Time</span>
                                        <strong id="previewSlot">—</strong>
                                    </div>
                                    <div class="col-sm-12 mb-0" id="previewReasonWrap" style="display:none;">
                                        <span class="text-muted small d-block">Reason</span>
                                        <span id="previewReason"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('vendor.appointment.store-from-lead') }}" method="POST">
                            @csrf
                            <input type="hidden" name="service_request_id" id="leadServiceRequestId">
                            <button type="submit" class="btn btn--primary w-100">
                                <i class="tio-calendar-add"></i> Register Appointment
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .slot-grid { display:flex; flex-wrap:wrap; gap:10px; min-height:48px; align-items:flex-start; }
    .slot-card { border:2px solid #e7eaf3; border-radius:8px; padding:8px 14px; cursor:pointer; text-align:center; min-width:130px; transition:all .15s; background:#fff; user-select:none; }
    .slot-card:hover:not(.slot-full) { border-color:#377dff; background:#f0f5ff; }
    .slot-card.slot-selected { border-color:#377dff; background:#377dff; color:#fff; }
    .slot-card.slot-selected .slot-count { color:rgba(255,255,255,.85); }
    .slot-card.slot-full { background:#f8f9fa; border-color:#e7eaf3; color:#adb5bd; cursor:not-allowed; }
    .slot-time { font-weight:600; font-size:13px; }
    .slot-count { font-size:11px; color:#8c98a4; margin-top:2px; }
    .slot-loading { color:#8c98a4; font-size:13px; padding:10px 0; }
    .mode-btn.active { font-weight:700; }
</style>
@endsection

@push('script_2')
<script>
    const patientSearchUrl = "{{ route('vendor.appointment.search-patients') }}";
    const doctorSearchUrl  = "{{ route('vendor.appointment.search-doctors') }}";
    const slotsUrl         = "{{ route('vendor.appointment.available-slots') }}";
    const lookupUrl        = "{{ route('vendor.appointment.lookup-lead') }}";

    // ── Mode toggle ──────────────────────────────────────────────────────────
    document.querySelectorAll('.mode-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active', 'btn--primary'));
            document.querySelectorAll('.mode-btn').forEach(b => b.classList.add('btn-outline-primary'));
            this.classList.add('active', 'btn--primary');
            this.classList.remove('btn-outline-primary');

            const mode = this.dataset.mode;
            document.getElementById('walkinForm').style.display = mode === 'walkin' ? '' : 'none';
            document.getElementById('leadForm').style.display   = mode === 'lead'   ? '' : 'none';
        });
    });

    // ── Walk-in: Select2 + slots ─────────────────────────────────────────────
    $('#patientSelect').select2({
        placeholder: 'Search by name, phone or UID...',
        minimumInputLength: 2,
        allowClear: true,
        width: '100%',
        ajax: {
            url: patientSearchUrl, dataType: 'json', delay: 300,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data }), cache: true,
        },
    });

    $('#doctorSelect').select2({
        placeholder: 'Search by name or specialization...',
        minimumInputLength: 1,
        allowClear: true,
        width: '100%',
        ajax: {
            url: doctorSearchUrl, dataType: 'json', delay: 300,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data }), cache: true,
        },
    });

    let hasSlots = false;

    function loadSlots() {
        const doctorId = $('#doctorSelect').val();
        const date     = $('#appointmentDate').val();
        clearSlotSelection();
        $('#slotSection').hide();
        $('#manualSection').hide();
        if (!doctorId || !date) return;
        $('#slotGrid').html('<span class="slot-loading"><i class="tio-refresh"></i> Loading...</span>');
        $('#slotSection').show();
        fetch(`${slotsUrl}?doctor_profile_id=${doctorId}&date=${date}`)
            .then(r => r.json())
            .then(slots => {
                hasSlots = slots.length > 0;
                if (!hasSlots) { $('#slotSection').hide(); $('#switchToSlots').hide(); $('#manualSection').show(); return; }
                let html = '';
                slots.forEach(s => {
                    const isFull = s.available <= 0;
                    html += `<div class="slot-card ${isFull ? 'slot-full' : ''}" data-slot-id="${s.id}" data-start="${s.slot_start}" ${isFull ? '' : 'onclick="selectSlot(this)"'}>
                        <div class="slot-time">${formatTime(s.slot_start)} – ${formatTime(s.slot_end)}</div>
                        <div class="slot-count">${isFull ? 'Full' : s.available + ' / ' + s.max_patients + ' available'}</div>
                    </div>`;
                });
                $('#slotGrid').html(html);
                $('#slotSection').show(); $('#manualSection').hide(); $('#switchToSlots').show();
            })
            .catch(() => { $('#slotGrid').html('<p class="text-danger small mb-0">Failed to load slots.</p>'); });
    }

    function selectSlot(el) {
        const slotId = el.getAttribute('data-slot-id');
        const start  = el.getAttribute('data-start');
        if ($('#selectedSlotId').val() === slotId) { clearSlotSelection(); return; }
        $('.slot-card').removeClass('slot-selected');
        $(el).addClass('slot-selected');
        $('#selectedSlotId').val(slotId);
        $('#selectedSlotTime').val(start.substring(0, 5));
    }

    function clearSlotSelection() {
        $('.slot-card').removeClass('slot-selected');
        $('#selectedSlotId').val(''); $('#selectedSlotTime').val(''); $('#manualTime').val('');
    }

    $('#switchToManual').on('click', e => { e.preventDefault(); clearSlotSelection(); $('#slotSection').hide(); $('#manualSection').show(); });
    $('#switchToSlots').on('click',  e => { e.preventDefault(); $('#manualTime').val(''); $('#selectedSlotTime').val(''); $('#manualSection').hide(); $('#slotSection').show(); });
    $('#doctorSelect').on('change', loadSlots);
    $('#appointmentDate').on('change', loadSlots);
    $('form').first().on('submit', function () { if (!$('#selectedSlotTime').val()) $('#selectedSlotTime').val($('#manualTime').val()); });

    function formatTime(t) {
        if (!t) return '';
        const [h, m] = t.split(':');
        const hr = parseInt(h);
        return `${hr > 12 ? hr - 12 : (hr || 12)}:${m} ${hr >= 12 ? 'PM' : 'AM'}`;
    }

    loadSlots();

    // ── Already Booked: lookup ────────────────────────────────────────────────
    function lookupLead() {
        const id = document.getElementById('leadId').value.trim();
        const errEl = document.getElementById('leadErr');
        const preview = document.getElementById('leadPreview');

        errEl.classList.add('d-none');
        preview.style.display = 'none';

        if (!id) { errEl.textContent = 'Please enter an appointment ID.'; errEl.classList.remove('d-none'); return; }

        const btn = document.getElementById('lookupBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch(`${lookupUrl}?id=${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (!ok) {
                    errEl.textContent = data.error || 'Not found.';
                    errEl.classList.remove('d-none');
                    return;
                }

                document.getElementById('previewPatient').textContent = data.patient_name || '—';
                document.getElementById('previewPhone').textContent   = data.patient_phone ? ' · ' + data.patient_phone : '';
                document.getElementById('previewDoctor').textContent  = data.doctor_name || 'Not specified';
                document.getElementById('previewDate').textContent    = data.appointment_date || '—';
                document.getElementById('previewSlot').textContent    = data.slot_label || (data.appointment_time ? data.appointment_time : '—');

                const reasonWrap = document.getElementById('previewReasonWrap');
                if (data.reason) {
                    document.getElementById('previewReason').textContent = data.reason;
                    reasonWrap.style.display = '';
                } else {
                    reasonWrap.style.display = 'none';
                }

                document.getElementById('leadServiceRequestId').value = id;
                preview.style.display = '';
            })
            .catch(() => { errEl.textContent = 'Something went wrong. Please try again.'; errEl.classList.remove('d-none'); })
            .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="tio-search"></i> Find'; });
    }

    document.getElementById('leadId').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); lookupLead(); } });
</script>
@endpush
