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
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('vendor.appointment.store') }}" class="row" method="POST">
                        @csrf

                        {{-- Hidden fields set by slot grid --}}
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
                        <div class="form-group  col-md-6">
                            <label class="input-label">Reason / Chief Complaint</label>
                            <textarea row="1" name="reason" class="form-control" rows="2"
                                placeholder="Optional">{{ old('reason') }}</textarea>
                        </div>

                        {{-- Slot Grid --}}
                        <div class="form-group col-12" id="slotSection" style="display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="input-label mb-0">Available Slots</label>
                                <a href="#" id="switchToManual" class="small btn btn-outline-success btn-sm">Enter time manually </a>
                            </div>
                            <div id="slotGrid" class="slot-grid"></div>
                        </div>

                        {{-- Manual Time --}}
                        <div class="form-group col-12" id="manualSection" style="display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="input-label mb-0">Appointment Time <span class="text-danger">*</span></label>
                                <a href="#" id="switchToSlots" class="small btn btn-outline-info btn-sm" style="display:none;">Pick from slots </a>
                            </div>
                            <input type="time" name="manual_time" id="manualTime" class="form-control"
                                value="{{ old('appointment_time') }}">
                            @error('appointment_time')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                      

                        <button type="submit" class="btn btn--primary w-100">
                            <i class="tio-calendar-add"></i> Book Appointment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .slot-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        min-height: 48px;
        align-items: flex-start;
    }
    .slot-card {
        border: 2px solid #e7eaf3;
        border-radius: 8px;
        padding: 8px 14px;
        cursor: pointer;
        text-align: center;
        min-width: 130px;
        transition: all 0.15s ease;
        background: #fff;
        user-select: none;
    }
    .slot-card:hover:not(.slot-full) {
        border-color: #377dff;
        background: #f0f5ff;
    }
    .slot-card.slot-selected {
        border-color: #377dff;
        background: #377dff;
        color: #fff;
    }
    .slot-card.slot-selected .slot-count {
        color: rgba(255,255,255,0.85);
    }
    .slot-card.slot-full {
        background: #f8f9fa;
        border-color: #e7eaf3;
        color: #adb5bd;
        cursor: not-allowed;
    }
    .slot-time {
        font-weight: 600;
        font-size: 13px;
    }
    .slot-count {
        font-size: 11px;
        color: #8c98a4;
        margin-top: 2px;
    }
    .slot-loading {
        color: #8c98a4;
        font-size: 13px;
        padding: 10px 0;
    }
</style>
@endsection

@push('script_2')
<script>
    const patientSearchUrl = "{{ route('vendor.appointment.search-patients') }}";
    const doctorSearchUrl  = "{{ route('vendor.appointment.search-doctors') }}";
    const slotsUrl         = "{{ route('vendor.appointment.available-slots') }}";

    $('#patientSelect').select2({
        placeholder: 'Search by name, phone or UID...',
        minimumInputLength: 2,
        allowClear: true,
        width: '100%',
        ajax: {
            url: patientSearchUrl,
            dataType: 'json',
            delay: 300,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data }),
            cache: true,
        },
    });

    $('#doctorSelect').select2({
        placeholder: 'Search by name or specialization...',
        minimumInputLength: 1,
        allowClear: true,
        width: '100%',
        ajax: {
            url: doctorSearchUrl,
            dataType: 'json',
            delay: 300,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data }),
            cache: true,
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

                if (!hasSlots) {
                    // No slots — show manual time directly
                    $('#slotSection').hide();
                    $('#switchToSlots').hide();
                    $('#manualSection').show();
                    return;
                }

                let html = '';
                slots.forEach(s => {
                    const isFull = s.available <= 0;
                    html += `
                        <div class="slot-card ${isFull ? 'slot-full' : ''}"
                            data-slot-id="${s.id}"
                            data-start="${s.slot_start}"
                            ${isFull ? '' : 'onclick="selectSlot(this)"'}>
                            <div class="slot-time">${formatTime(s.slot_start)} – ${formatTime(s.slot_end)}</div>
                            <div class="slot-count">${isFull ? 'Full' : s.available + ' / ' + s.max_patients + ' available'}</div>
                        </div>`;
                });

                $('#slotGrid').html(html);
                $('#slotSection').show();
                $('#manualSection').hide();
                $('#switchToSlots').show();
            })
            .catch(() => {
                $('#slotGrid').html('<p class="text-danger small mb-0">Failed to load slots.</p>');
            });
    }

    function selectSlot(el) {
        const slotId = el.getAttribute('data-slot-id');
        const start  = el.getAttribute('data-start');

        if ($('#selectedSlotId').val() === slotId) {
            clearSlotSelection();
            return;
        }

        $('.slot-card').removeClass('slot-selected');
        $(el).addClass('slot-selected');
        $('#selectedSlotId').val(slotId);
        $('#selectedSlotTime').val(start.substring(0, 5));
    }

    function clearSlotSelection() {
        $('.slot-card').removeClass('slot-selected');
        $('#selectedSlotId').val('');
        $('#selectedSlotTime').val('');
        $('#manualTime').val('');
    }

    // Switch to manual time
    $('#switchToManual').on('click', function (e) {
        e.preventDefault();
        clearSlotSelection();
        $('#slotSection').hide();
        $('#manualSection').show();
    });

    // Switch back to slot grid
    $('#switchToSlots').on('click', function (e) {
        e.preventDefault();
        $('#manualTime').val('');
        $('#selectedSlotTime').val('');
        $('#manualSection').hide();
        $('#slotSection').show();
    });

    $('#doctorSelect').on('change', loadSlots);
    $('#appointmentDate').on('change', loadSlots);

    // On submit — use manual time if no slot selected
    $('form').on('submit', function () {
        if (!$('#selectedSlotTime').val()) {
            $('#selectedSlotTime').val($('#manualTime').val());
        }
    });

    function formatTime(t) {
        if (!t) return '';
        const [h, m] = t.split(':');
        const hour   = parseInt(h);
        return `${hour > 12 ? hour - 12 : (hour || 12)}:${m} ${hour >= 12 ? 'PM' : 'AM'}`;
    }

    loadSlots();
</script>
@endpush
