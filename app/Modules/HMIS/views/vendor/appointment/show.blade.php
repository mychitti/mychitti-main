@extends('layouts.vendor.app')
@section('title', 'Appointment Detail')

@section('content')
<div class="content container-fluid">
    @include('hmis::vendor.hospital._hospital_submenu_header')
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-calendar" style="font-size:22px;"></i></span>
                Appointment Detail
            </h1>
            {{-- No actions here. The reminder before the visit and the feedback request after it
                 are both standing settings under Notification Settings, sent by the scheduled
                 sweep and by AppointmentCompletion respectively — which fires from Update Status
                 below or, far more often, when the consultation receipt is generated in OPD.
                 The submenu above carries the way back to the list. --}}
        </div>
    </div>
 
    {{-- Top row: Appointment info + Patient/Actions --}}
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
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Patient Info</h5></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th width="40%">Phone</th><td>{{ $appointment->patient?->phone ?? '—' }}</td></tr>
                        <tr><th>Gender</th><td>{{ ucfirst($appointment->patient?->gender ?? '—') }}</td></tr>
                        <tr><th>Blood Group</th><td>{{ $appointment->patient?->blood_group ?? '—' }}</td></tr>
                        <tr><th>DOB</th><td>{{ $appointment->patient?->dob ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>

            {{-- Reassign Doctor --}}
            @if(!in_array($appointment->status, ['completed', 'cancelled']))
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Reassign Doctor</h5></div>
                <div class="card-body">
                    <form action="{{ route('vendor.appointment.reassign', $appointment->id) }}" method="POST">
                        @csrf
                        <div class="form-group mb-2">
                            <label class="input-label">Select Doctor <span class="text-danger">*</span></label>
                            <select name="doctor_profile_id" class="form-control" required>
                                @foreach($doctors as $d)
                                    <option value="{{ $d->id }}"
                                        {{ $appointment->doctor_profile_id == $d->id ? 'selected' : '' }}>
                                        Dr. {{ $d->employee?->f_name }} {{ $d->employee?->l_name }}
                                        @if($d->specialization) — {{ $d->specialization }} @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="tio-user-add"></i> Reassign
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Reschedule --}}
            @if(!in_array($appointment->status, ['completed', 'cancelled']))
            <div class="card mb-3">
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

            {{-- Next Visit (follow-up) — available even after completion --}}
            @if(!in_array($appointment->status, ['cancelled', 'no_show']))
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0"><i class="tio-calendar-note"></i> Schedule Next Visit</h5></div>
                <div class="card-body">
                    <p class="text-muted mb-2" style="font-size:12px;">
                        Books a follow-up for this patient with the same doctor. The patient gets a WhatsApp
                        confirmation now and an automatic reminder before the visit.
                    </p>
                    <form action="{{ route('vendor.appointment.next-visit', $appointment->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="input-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="appointment_date" id="nextVisitDate" class="form-control"
                                min="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label class="input-label">Slot</label>
                            <select name="slot_id" id="nextVisitSlot" class="form-control">
                                <option value="">-- Select date first --</option>
                            </select>
                            <small class="text-muted">Leave blank to enter time manually</small>
                        </div>
                        <div class="form-group">
                            <label class="input-label">Time <span class="text-danger">*</span></label>
                            <input type="time" name="appointment_time" id="nextVisitTime" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="input-label">Reason / Notes</label>
                            <input type="text" name="reason" class="form-control" maxlength="500"
                                   placeholder="Review, dressing change, report follow-up…">
                        </div>
                        <button type="submit" class="btn btn--primary w-100">
                            <i class="tio-calendar-note"></i> Schedule Next Visit
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         PRESCRIPTION — full-width inline section
    ══════════════════════════════════════════════════════════ --}}
    @php
        $existingRx = \App\Models\Prescription::where('appointment_id', $appointment->id)
            ->with('items')
            ->first();
        $rxOpen = session('rx_saved') || $existingRx || old('diagnosis') !== null;
    @endphp

    <div class="card mb-4" id="rxCard" style="border:1.5px solid #3b82f6;">
        <div class="card-header d-flex justify-content-between align-items-center"
             style="background:linear-gradient(90deg,#eff6ff,#f0fdf4); cursor:pointer;"
             onclick="toggleRx()">
            <div class="d-flex align-items-center gap-2">
                <i class="tio-medicine" style="font-size:20px; color:#2563eb;"></i>
                <h5 class="card-title mb-0" style="color:#1e3a5f;">Prescription</h5>
                @if($existingRx)
                    <span class="badge {{ $existingRx->is_finalized ? 'badge-success' : 'badge-warning' }} ml-2">
                        {{ $existingRx->is_finalized ? 'Finalized' : 'Draft' }}
                    </span>
                @else
                    <span class="badge badge-soft-secondary ml-2">Not written yet</span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($existingRx)
                    <a href="{{ route('vendor.prescription.show', $existingRx->id) }}"
                       class="btn btn-sm btn--primary" onclick="event.stopPropagation()">
                        <i class="tio-print"></i> View / Print
                    </a>
                @endif
                <i class="tio-chevron-down" id="rxChevron" style="font-size:18px; color:#6b7280;
                   transition:transform .2s; {{ $rxOpen ? 'transform:rotate(180deg)' : '' }}"></i>
            </div>
        </div>

        <div id="rxBody" style="{{ $rxOpen ? '' : 'display:none;' }}">
                {{-- Editable inline form --}}
                <div class="card-body p-3">
                    @if(session('rx_saved'))
                        <div class="alert alert-success alert-dismissible mb-3" style="font-size:13px;">
                            Prescription saved.
                            <a href="{{ route('vendor.prescription.show', session('rx_saved')) }}" class="alert-link">View / Print</a>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif
                    @if($existingRx && $existingRx->is_finalized)
                        <div class="alert alert-info mb-3" style="font-size:13px;">
                            <i class="tio-checkmark-circle mr-1"></i>
                            This prescription is marked as <strong>Finalized</strong>. You can still edit and re-save it.
                            <a href="{{ route('vendor.prescription.show', $existingRx->id) }}" class="alert-link ml-2">View / Print →</a>
                        </div>
                    @endif

                    @include('hmis::vendor.prescription._rx_templates', [
                        'formId'   => 'inlineRxForm',
                        'addRowFn' => 'apptAddMedRow',
                    ])

                    <form action="{{ $existingRx ? route('vendor.prescription.update', $existingRx->id) : route('vendor.prescription.store') }}"
                          method="POST" id="inlineRxForm">
                        @csrf
                        <input type="hidden" name="appointment_id"    value="{{ $appointment->id }}">
                        <input type="hidden" name="patient_id"        value="{{ $appointment->patient_id }}">
                        <input type="hidden" name="doctor_profile_id" value="{{ $appointment->doctor_profile_id }}">

                        <div class="row">
                            {{-- Diagnosis + Notes + Follow-up --}}
                            <div class="col-lg-5">
                                <div class="form-group">
                                    <label class="input-label font-weight-bold">Diagnosis</label>
                                    <textarea name="diagnosis" class="form-control" rows="3"
                                        placeholder="Primary diagnosis / chief complaint...">{{ old('diagnosis', $existingRx?->diagnosis ?? '') }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label class="input-label font-weight-bold">Doctor's Notes / Advice</label>
                                    <textarea name="notes" class="form-control" rows="3"
                                        placeholder="Rest, diet, precautions...">{{ old('notes', $existingRx?->notes ?? '') }}</textarea>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="input-label font-weight-bold">Follow-Up Date</label>
                                    <input type="date" name="follow_up_date" class="form-control" style="max-width:200px;"
                                        value="{{ old('follow_up_date', $existingRx?->follow_up_date?->format('Y-m-d') ?? '') }}">
                                </div>
                            </div>

                            {{-- Medicines --}}
                            <div class="col-lg-7">
                                {{-- Picking a medicine on the last line opens the next one by
                                     itself, so this is a quiet outlined + only. --}}
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="input-label font-weight-bold mb-0">Medicines</label>
                                    <button type="button" class="btn btn-sm rx-add-row" title="Add row" onclick="apptAddMedRow()">
                                        <i class="tio-add"></i>
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table rx-table mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width:34px;">#</th>
                                                <th style="width:92px;">Type</th>
                                                <th style="min-width:280px;">Medicine</th>
                                                <th style="width:130px;">Dose</th>
                                                <th style="width:125px;">When</th>
                                                <th style="width:125px;">Frequency</th>
                                                <th style="width:105px;">Duration</th>
                                                <th style="width:70px;">Qty</th>
                                                <th style="min-width:150px;">Notes / Instructions</th>
                                                <th style="width:40px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="apptMedTable">
                                            @if($existingRx && $existingRx->items->count())
                                                @foreach($existingRx->items as $i => $item)
                                                    @include('hmis::vendor.prescription._med_row', ['i' => $i, 'item' => $item])
                                                @endforeach
                                            @else
                                                @include('hmis::vendor.prescription._med_row', ['i' => 0, 'item' => null])
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <hr class="mt-3 mb-3">

                        <div class="rx-actions">
                            <button type="submit" class="btn btn--primary" name="action" value="draft">
                                <i class="tio-save"></i> Save Draft
                            </button>
                            <button type="submit" class="btn btn-success" name="finalize" value="1">
                                <i class="tio-checkmark-circle"></i> Finalize &amp; Print
                            </button>
                            @include('hmis::vendor.prescription._rx_language', ['selected' => $existingRx->language ?? null])
                        </div>
                    </form>
                </div>
        </div>
    </div>

</div>

{{-- Hidden template for new medicine rows --}}
<template id="apptMedRowTpl">
    @include('hmis::vendor.prescription._med_row', ['i' => '__IDX__', 'item' => null])
</template>

<style>

@endsection

@push('script_2')
<script>
    const slotsUrl  = "{{ route('vendor.appointment.available-slots') }}";
    const doctorId  = {{ $appointment->doctor_profile_id }};
    const pharmUrl  = "{{ route('vendor.prescription.search-medicines') }}";

    // ── Rx accordion ─────────────────────────────────────────
    function toggleRx() {
        const body    = document.getElementById('rxBody');
        const chevron = document.getElementById('rxChevron');
        const open    = body.style.display !== 'none';
        body.style.display    = open ? 'none' : '';
        chevron.style.transform = open ? '' : 'rotate(180deg)';
    }

    // ── Status update ────────────────────────────────────────
    document.getElementById('statusSelect')?.addEventListener('change', function () {
        document.getElementById('cancelReasonWrap').style.display =
            this.value === 'cancelled' ? 'block' : 'none';
    });

    // ── Reschedule slot loader ───────────────────────────────
    document.getElementById('rescheduleDate')?.addEventListener('change', function () {
        const date    = this.value;
        const slotSel = document.getElementById('rescheduleSlot');

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

    // ── Next-visit slot loader (same doctor, same slots endpoint) ──
    document.getElementById('nextVisitDate')?.addEventListener('change', function () {
        const date    = this.value;
        const slotSel = document.getElementById('nextVisitSlot');

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

    document.getElementById('nextVisitSlot')?.addEventListener('change', function () {
        const start = this.options[this.selectedIndex].getAttribute('data-start');
        if (start) document.getElementById('nextVisitTime').value = start.substring(0, 5);
    });

    function formatTime(t) {
        if (!t) return '';
        const [h, m] = t.split(':');
        const hour = parseInt(h);
        return `${hour > 12 ? hour - 12 : hour}:${m} ${hour >= 12 ? 'PM' : 'AM'}`;
    }

    // ── Inline Rx — medicine rows ────────────────────────────
    let apptMedIdx = {{ $existingRx ? max(0, $existingRx->items->count() - 1) : 0 }};

    @php
        $rxBannedNames = \Illuminate\Support\Facades\Schema::hasColumn('inventory_items', 'is_banned')
            ? \App\Models\InventoryItem::where('store_id', \App\CentralLogics\Helpers::get_store_id())
                ->where('item_type', 'product')->where('is_banned', 1)
                ->pluck('item_name')->map(fn($n) => mb_strtolower(trim($n)))->values()
            : collect();
    @endphp
    const RX_BANNED = @json($rxBannedNames);

    // Warn (but allow) when a banned/blocked medicine is entered on the prescription.
    function rxBannedCheck(input) {
        if (!input) return;
        const row = input.closest('.med-row'); if (!row) return;
        const banned = RX_BANNED.includes((input.value || '').trim().toLowerCase());
        let warn = row.querySelector('.rx-banned-warn');
        if (banned) {
            if (!warn) {
                warn = document.createElement('div');
                warn.className = 'rx-banned-warn text-danger small mt-1';
                warn.innerHTML = '<i class="tio-warning"></i> This medicine is <strong>banned/blocked</strong>. You can still prescribe, but please confirm it is justified.';
                input.insertAdjacentElement('afterend', warn);
            }
            warn.style.display = ''; input.style.borderColor = '#dc2626';
        } else if (warn) {
            warn.style.display = 'none'; input.style.borderColor = '';
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        const t = document.getElementById('apptMedTable');
        if (!t) return;
        t.addEventListener('input', function (e) {
            if (e.target.matches('input[name$="[medicine_name]"]')) rxBannedCheck(e.target);
        });
        t.querySelectorAll('input[name$="[medicine_name]"]').forEach(rxBannedCheck);
    });

    function apptAddMedRow(prefill) {
        apptMedIdx++;
        const tpl = document.getElementById('apptMedRowTpl').innerHTML
            .replace(/__IDX__/g, apptMedIdx);
        // Parsed inside a <tbody>: a <tr> assigned to a <div>'s innerHTML is discarded by the HTML
        // parser, which silently produced no row at all once these became table rows.
        const host = document.createElement('tbody');
        host.innerHTML = tpl;
        const row = host.firstElementChild;
        if (prefill) {
            const nameInput = row.querySelector('input[name$="[medicine_name]"]');
            if (nameInput) nameInput.value = prefill.name;
            const invInput = row.querySelector('.med-inv-id');
            if (invInput && prefill.id) invInput.value = prefill.id;
        }
        document.getElementById('apptMedTable').appendChild(row);
        rxBannedCheck(row.querySelector('input[name$="[medicine_name]"]'));
        if (prefill) row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // removeMedRow is shared — defined here for the inline form
    function removeMedRow(btn) {
        const rows = document.querySelectorAll('#apptMedTable .med-row');
        if (rows.length <= 1) {
            const row = btn.closest('.med-row');
            row.querySelectorAll('input,select').forEach(el => el.value = '');
            if (window.rxMedClearRow) window.rxMedClearRow(row);
            return;
        }
        btn.closest('.med-row').remove();
    }

</script>
@endpush
