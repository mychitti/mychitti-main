@extends('layouts.vendor.app')
@section('title', 'Appointment Detail')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-calendar" style="font-size:22px;"></i></span>
                Appointment Detail
            </h1>
            <a href="{{ route('vendor.appointment.list') }}" class="btn btn-outline-secondary btn-sm">
                <i class="tio-arrow-backward"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Left: Appointment Info --}}
        <div class="col-md-7">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Appointment Info</h5>
                    @if($appointment->token)
                        <span class="badge badge-soft-info font-size-16">
                            Token #{{ $appointment->token->token_number }}
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    @php
                        $colors = [
                            'scheduled'  => 'warning',
                            'checked_in' => 'info',
                            'consulting' => 'primary',
                            'completed'  => 'success',
                            'cancelled'  => 'danger',
                            'no_show'    => 'secondary',
                        ];
                    @endphp
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <th width="35%">Status</th>
                            <td>
                                <span class="badge badge-{{ $colors[$appointment->status] ?? 'secondary' }}">
                                    {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Patient</th>
                            <td>
                                {{ $appointment->patient?->name }}
                                <span class="text-muted">({{ $appointment->patient?->patient_uid }})</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Doctor</th>
                            <td>
                                Dr. {{ $appointment->doctorProfile?->employee?->f_name }}
                                {{ $appointment->doctorProfile?->employee?->l_name }}
                                <br><small class="text-muted">{{ $appointment->doctorProfile?->specialization }}</small>
                            </td>
                        </tr>
                        <tr>
                            <th>Date</th>
                            <td>{{ $appointment->appointment_date?->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <th>Time</th>
                            <td>{{ \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A') }}</td>
                        </tr>
                        @if($appointment->slot)
                        <tr>
                            <th>Slot</th>
                            <td>
                                {{ \Carbon\Carbon::parse($appointment->slot->slot_start)->format('h:i A') }}
                                – {{ \Carbon\Carbon::parse($appointment->slot->slot_end)->format('h:i A') }}
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th>Booking Type</th>
                            <td><span class="badge badge-soft-secondary">{{ ucfirst(str_replace('_', ' ', $appointment->booking_type)) }}</span></td>
                        </tr>
                        @if($appointment->reason)
                        <tr>
                            <th>Reason</th>
                            <td>{{ $appointment->reason }}</td>
                        </tr>
                        @endif
                        @if($appointment->cancel_reason)
                        <tr>
                            <th>Cancel Reason</th>
                            <td class="text-danger">{{ $appointment->cancel_reason }}</td>
                        </tr>
                        @endif
                        @if($appointment->rescheduledFrom)
                        <tr>
                            <th>Rescheduled From</th>
                            <td>
                                <a href="{{ route('vendor.appointment.show', $appointment->rescheduledFrom->id) }}">
                                    Appointment #{{ $appointment->rescheduledFrom->id }}
                                </a>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Status Update --}}
            @if(count($nextStatuses) > 0)
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Update Status</h5></div>
                <div class="card-body">
                    <form action="{{ route('vendor.appointment.status', $appointment->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="input-label">Move to</label>
                            <select name="status" class="form-control" id="statusSelect" required>
                                <option value="">-- Select Status --</option>
                                @foreach($nextStatuses as $s)
                                    <option value="{{ $s }}">{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="cancelReasonWrap" class="form-group" style="display:none;">
                            <label class="input-label">Cancel Reason</label>
                            <textarea name="cancel_reason" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn--primary">Update Status</button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        {{-- Right: Patient Info + Reschedule --}}
        <div class="col-md-5">
            {{-- Patient Summary --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Patient Info</h5></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th>Phone</th><td>{{ $appointment->patient?->phone ?? '—' }}</td></tr>
                        <tr><th>Gender</th><td>{{ ucfirst($appointment->patient?->gender ?? '—') }}</td></tr>
                        <tr><th>Blood Group</th><td>{{ $appointment->patient?->blood_group ?? '—' }}</td></tr>
                        <tr><th>DOB</th><td>{{ $appointment->patient?->dob ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>

            {{-- Reschedule --}}
            @if(!in_array($appointment->status, ['completed', 'cancelled']))
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Reschedule</h5></div>
                <div class="card-body">
                    <form action="{{ route('vendor.appointment.reschedule', $appointment->id) }}" method="POST" id="rescheduleForm">
                        @csrf
                        <div class="form-group">
                            <label class="input-label">New Date <span class="text-danger">*</span></label>
                            <input type="date" name="appointment_date" id="rescheduleDate" class="form-control"
                                min="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label class="input-label">Slot</label>
                            <select name="slot_id" id="rescheduleSlot" class="form-control">
                                <option value="">-- Select date first --</option>
                            </select>
                            <small class="text-muted">Leave blank to enter time manually</small>
                        </div>
                        <div class="form-group">
                            <label class="input-label">Time <span class="text-danger">*</span></label>
                            <input type="time" name="appointment_time" id="rescheduleTime" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="tio-refresh"></i> Reschedule
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    const slotsUrl   = "{{ route('vendor.appointment.available-slots') }}";
    const doctorId   = {{ $appointment->doctor_profile_id }};

    // Cancel reason toggle
    document.getElementById('statusSelect')?.addEventListener('change', function () {
        document.getElementById('cancelReasonWrap').style.display =
            this.value === 'cancelled' ? 'block' : 'none';
    });

    // Reschedule slot loader
    document.getElementById('rescheduleDate')?.addEventListener('change', function () {
        const date     = this.value;
        const slotSel  = document.getElementById('rescheduleSlot');
        const timeSel  = document.getElementById('rescheduleTime');

        slotSel.innerHTML = '<option value="">Loading...</option>';

        fetch(`${slotsUrl}?doctor_profile_id=${doctorId}&date=${date}`)
            .then(r => r.json())
            .then(slots => {
                slotSel.innerHTML = '<option value="">-- Manual time --</option>';
                slots.forEach(s => {
                    const label    = `${formatTime(s.slot_start)} – ${formatTime(s.slot_end)} | ${s.available}/${s.max_patients} available`;
                    const disabled = s.available <= 0 ? 'disabled' : '';
                    slotSel.innerHTML += `<option value="${s.id}" data-start="${s.slot_start}" ${disabled}>${label}</option>`;
                });
                if (slots.length === 0) {
                    slotSel.innerHTML = '<option value="">No slots for this day</option>';
                }
            });
    });

    document.getElementById('rescheduleSlot')?.addEventListener('change', function () {
        const start = this.options[this.selectedIndex].getAttribute('data-start');
        if (start) document.getElementById('rescheduleTime').value = start.substring(0, 5);
    });

    function formatTime(t) {
        if (!t) return '';
        const [h, m] = t.split(':');
        const hour = parseInt(h);
        return `${hour > 12 ? hour - 12 : hour}:${m} ${hour >= 12 ? 'PM' : 'AM'}`;
    }
</script>
@endpush
