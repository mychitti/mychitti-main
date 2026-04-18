@extends('front-views.layout')
@section('title', 'Book Appointment — ' . $store->name)

@push('css_or_js')
<style>
    .appt-wrap { max-width: 780px; margin: 40px auto; padding: 0 16px 60px; }
    .appt-store-header { display:flex; align-items:center; gap:14px; margin-bottom:28px; }
    .appt-store-logo { width:54px; height:54px; border-radius:10px; object-fit:cover; border:1px solid #eee; }
    .appt-store-name { font-size:20px; font-weight:700; margin:0; }
    .appt-store-sub  { font-size:13px; color:#888; margin:0; }

    /* Doctor Cards */
    .doctor-grid { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:8px; }
    .doctor-card {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px 16px;
        cursor: pointer;
        width: calc(50% - 6px);
        transition: all .15s;
        background: #fff;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .doctor-card:hover { border-color: #2563eb; }
    .doctor-card.selected { border-color: #2563eb; background: #eff6ff; }
    .doctor-avatar {
        width: 46px; height: 46px; border-radius: 50%;
        object-fit: cover; background: #e0e7ff;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; font-weight: 700; color: #2563eb; flex-shrink: 0;
    }
    .doctor-name  { font-weight: 600; font-size: 14px; margin: 0 0 2px; }
    .doctor-spec  { font-size: 12px; color: #6b7280; margin: 0 0 2px; }
    .doctor-fee   { font-size: 12px; color: #059669; font-weight: 600; margin: 0; }

    /* Slot Grid */
    .slot-grid { display:flex; flex-wrap:wrap; gap:10px; min-height:48px; }
    .slot-card {
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 16px;
        cursor: pointer;
        text-align: center;
        min-width: 140px;
        transition: all .15s;
        background: #fff;
        user-select: none;
    }
    .slot-card:hover:not(.slot-full) { border-color: #2563eb; background: #eff6ff; }
    .slot-card.slot-selected { border-color: #2563eb; background: #2563eb; color: #fff; }
    .slot-card.slot-selected .slot-avail { color: rgba(255,255,255,.8); }
    .slot-card.slot-full { background: #f9fafb; border-color: #e5e7eb; color: #9ca3af; cursor: not-allowed; }
    .slot-time  { font-weight: 700; font-size: 13px; }
    .slot-avail { font-size: 11px; color: #6b7280; margin-top: 2px; }

    .section-label { font-weight: 700; font-size: 15px; margin-bottom: 12px; color: #111; }
    .appt-section  { margin-bottom: 28px; }
    .switch-link   { font-size: 13px; color: #2563eb; cursor: pointer; text-decoration: underline; }

    @media(max-width:540px) { .doctor-card { width: 100%; } }
</style>
@endpush

@section('content')
<div class="appt-wrap">

    {{-- Store Header --}}
    <div class="appt-store-header">
        @if($store->logo)
            <img src="{{ asset('storage/app/public/store/' . $store->logo) }}" class="appt-store-logo" alt="{{ $store->name }}">
        @endif
        <div>
            <p class="appt-store-name">{{ $store->name }}</p>
            <p class="appt-store-sub">Book an Appointment</p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('front.appointment.store', [request()->city, $store->slug]) }}" method="POST">
        @csrf
        <input type="hidden" name="slot_id"          id="selectedSlotId">
        <input type="hidden" name="appointment_time"  id="selectedTime">
        <input type="hidden" name="doctor_profile_id" id="selectedDoctorId">

        {{-- Step 1: Doctor --}}
        <div class="appt-section">
            <div class="section-label">1. Select Doctor</div>
            <div class="doctor-grid">
                @foreach($doctors as $d)
                @php $empName = 'Dr. ' . ($d->employee?->f_name . ' ' . $d->employee?->l_name); @endphp
                <div class="doctor-card {{ old('doctor_profile_id', $selectedDoctorId) == $d->id ? 'selected' : '' }}"
                    onclick="selectDoctor({{ $d->id }}, this)">
                    <div class="doctor-avatar">{{ strtoupper(substr($d->employee?->f_name ?? 'D', 0, 1)) }}</div>
                    <div>
                        <p class="doctor-name">{{ $empName }}</p>
                        <p class="doctor-spec">{{ $d->specialization }}</p>
                        @if($d->consultation_fee > 0)
                            <p class="doctor-fee">₹{{ number_format($d->consultation_fee, 0) }} fee</p>
                        @else
                            <p class="doctor-fee">Free consultation</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @error('doctor_profile_id')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Step 2: Date --}}
        <div class="appt-section">
            <div class="section-label">2. Select Date</div>
            <input type="date" name="appointment_date" id="appointmentDate" class="form-control"
                style="max-width:260px;"
                value="{{ old('appointment_date', date('Y-m-d')) }}"
                min="{{ date('Y-m-d') }}"
                onchange="loadSlots()">
            @error('appointment_date')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Step 3: Slot --}}
        <div class="appt-section" id="slotSection" style="display:none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-label mb-0">3. Pick a Slot</div>
                <span class="switch-link" id="switchToManual" onclick="showManual()">Enter time manually</span>
            </div>
            <div id="slotGrid" class="slot-grid">
                <span class="text-muted small">Loading...</span>
            </div>
        </div>

        {{-- Manual Time --}}
        <div class="appt-section" id="manualSection" style="display:none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-label mb-0">3. Appointment Time</div>
                <span class="switch-link" id="switchToSlots" onclick="showSlots()" style="display:none;">Pick from slots</span>
            </div>
            <input type="time" id="manualTime" class="form-control" style="max-width:200px;"
                oninput="document.getElementById('selectedTime').value = this.value; clearSlotSelection();">
            @error('appointment_time')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Step 4: Reason --}}
        <div class="appt-section">
            <div class="section-label">4. Reason <span style="font-weight:400;color:#9ca3af;">(optional)</span></div>
            <textarea name="reason" class="form-control" rows="2"
                placeholder="Chief complaint or reason for visit..." >{{ old('reason') }}</textarea>
        </div>

        {{-- Submit --}}
        @if(auth('web')->check())
            <button type="submit" class="btn btn-primary btn-block" style="max-width:360px;">
                Confirm Booking
            </button>
        @else
            <a href="{{ route('user-login') }}?redirect={{ urlencode(url()->current()) }}"
                class="btn btn-primary btn-block" style="max-width:360px;">
                Login to Book Appointment
            </a>
        @endif

    </form>
</div>
@endsection

@push('script')
<script>
    const slotsUrl = "{{ route('front.appointment.slots') }}";
    let hasSlots   = false;

    function selectDoctor(id, el) {
        document.querySelectorAll('.doctor-card').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('selectedDoctorId').value = id;
        loadSlots();
    }

    function loadSlots() {
        const doctorId = document.getElementById('selectedDoctorId').value;
        const date     = document.getElementById('appointmentDate').value;

        clearSlotSelection();
        document.getElementById('slotSection').style.display = 'none';
        document.getElementById('manualSection').style.display = 'none';

        if (!doctorId || !date) return;

        document.getElementById('slotGrid').innerHTML = '<span class="text-muted small">Loading slots...</span>';
        document.getElementById('slotSection').style.display = 'block';

        fetch(`${slotsUrl}?doctor_profile_id=${doctorId}&date=${date}`)
            .then(r => r.json())
            .then(slots => {
                hasSlots = slots.length > 0;

                if (!hasSlots) {
                    document.getElementById('slotSection').style.display = 'none';
                    document.getElementById('switchToSlots').style.display = 'none';
                    document.getElementById('manualSection').style.display = 'block';
                    return;
                }

                let html = '';
                slots.forEach(s => {
                    const full = s.available <= 0;
                    html += `<div class="slot-card ${full ? 'slot-full' : ''}"
                        data-slot-id="${s.id}" data-start="${s.slot_start}"
                        ${full ? '' : 'onclick="selectSlot(this)"'}>
                        <div class="slot-time">${fmt(s.slot_start)} – ${fmt(s.slot_end)}</div>
                        <div class="slot-avail">${full ? 'Fully Booked' : s.available + ' / ' + s.max_patients + ' available'}</div>
                    </div>`;
                });

                document.getElementById('slotGrid').innerHTML = html;
                document.getElementById('slotSection').style.display = 'block';
                document.getElementById('manualSection').style.display = 'none';
                document.getElementById('switchToSlots').style.display = 'inline';
            })
            .catch(() => {
                document.getElementById('slotGrid').innerHTML = '<span class="text-danger small">Failed to load slots.</span>';
            });
    }

    function selectSlot(el) {
        const slotId = el.getAttribute('data-slot-id');
        const start  = el.getAttribute('data-start');

        if (document.getElementById('selectedSlotId').value === slotId) {
            clearSlotSelection(); return;
        }

        document.querySelectorAll('.slot-card').forEach(c => c.classList.remove('slot-selected'));
        el.classList.add('slot-selected');
        document.getElementById('selectedSlotId').value = slotId;
        document.getElementById('selectedTime').value   = start.substring(0, 5);
        document.getElementById('manualTime') && (document.getElementById('manualTime').value = start.substring(0, 5));
    }

    function clearSlotSelection() {
        document.querySelectorAll('.slot-card').forEach(c => c.classList.remove('slot-selected'));
        document.getElementById('selectedSlotId').value = '';
        document.getElementById('selectedTime').value   = '';
    }

    function showManual() {
        clearSlotSelection();
        document.getElementById('slotSection').style.display   = 'none';
        document.getElementById('manualSection').style.display = 'block';
    }

    function showSlots() {
        document.getElementById('manualTime').value = '';
        document.getElementById('selectedTime').value = '';
        document.getElementById('manualSection').style.display = 'none';
        document.getElementById('slotSection').style.display   = 'block';
    }

    function fmt(t) {
        if (!t) return '';
        const [h, m] = t.split(':');
        const hr = parseInt(h);
        return `${hr > 12 ? hr - 12 : (hr || 12)}:${m} ${hr >= 12 ? 'PM' : 'AM'}`;
    }

    // Auto-load if doctor pre-selected
    @if($selectedDoctorId)
        document.getElementById('selectedDoctorId').value = {{ $selectedDoctorId }};
        loadSlots();
    @endif
</script>
@endpush
