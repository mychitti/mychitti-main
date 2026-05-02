@extends('layouts.vendor.app')
@section('title', 'Doctor Slots')

@push('css_or_js')
<style>
    .time-picker-wrap {
        position: relative;
    }
    .time-picker-wrap input[type="time"].form-control {
        padding-right: 44px;
    }
    .time-picker-wrap input[type="time"].form-control::-webkit-calendar-picker-indicator {
        opacity: 0;
        position: absolute;
        right: 0;
        width: 44px;
        height: 100%;
        cursor: pointer;
    }
    .time-clock-btn {
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        width: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        color: #3766e8;
        font-size: 1.15rem;
        background: #e8f0fe;
        border-radius: 0 6px 6px 0;
        border-left: 1px solid #c5d5f8;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h1 class="page-header-title mb-0">
                    <span class="page-header-icon"><i class="tio-time" style="font-size:22px;"></i></span>
                    Slots — Dr. {{ $doctor->employee?->f_name }} {{ $doctor->employee?->l_name }}
                </h1>
                <small class="text-muted ml-5">{{ $doctor->specialization }}</small>
            </div>
            <div class="d-flex gap-2" style="gap:8px;">
                <button type="button" class="btn btn-outline-info btn-sm" data-toggle="modal" data-target="#slotHelpModal">
                    <i class="tio-info-outined mr-1"></i> How it works
                </button>
                <a href="{{ route('vendor.doctor.list') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="tio-arrow-backward"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Add Slot --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Add Slot</h5></div>
                <div class="card-body">
                    <form action="{{ route('vendor.doctor.slot.store', $doctor->id) }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label class="input-label">Days <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-1" style="gap:6px;">
                                @foreach($days as $num => $name)
                                <label class="day-pill" id="pill-{{ $num }}">
                                    <input type="checkbox" name="days_of_week[]" value="{{ $num }}"
                                        class="day-checkbox" onchange="togglePill({{ $num }})">
                                    {{ substr($name, 0, 3) }}
                                </label>
                                @endforeach
                            </div>
                            <div class="mt-1">
                                <a href="#" class="small text-primary mr-2" onclick="selectAllDays(true);return false;">All</a>
                                <a href="#" class="small text-secondary mr-2" onclick="selectAllDays(false);return false;">None</a>
                                <a href="#" class="small text-info" onclick="selectWeekdays();return false;">Weekdays</a>
                            </div>
                            @error('days_of_week')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-6 form-group">
                                <label class="input-label">Start Time <span class="text-danger">*</span></label>
                                <div class="time-picker-wrap">
                                    <input type="time" name="slot_start" class="form-control" required>
                                    <span class="time-clock-btn"><i class="tio-time"></i></span>
                                </div>
                            </div>
                            <div class="col-6 form-group">
                                <label class="input-label">End Time <span class="text-danger">*</span></label>
                                <div class="time-picker-wrap">
                                    <input type="time" name="slot_end" class="form-control" required>
                                    <span class="time-clock-btn"><i class="tio-time"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 form-group">
                                <label class="input-label">Duration (mins) <span class="text-danger">*</span></label>
                                <input type="number" name="slot_duration_minutes" class="form-control" value="15" min="5" required>
                            </div>
                            <div class="col-6 form-group">
                                <label class="input-label">Max Patients <span class="text-danger">*</span></label>
                                <input type="number" name="max_patients" class="form-control" value="10" min="1" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn--primary w-100">Add Slot</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Slot List --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Configured Slots</h5></div>
                @php $grouped = $doctor->slots->groupBy('day_of_week'); @endphp
                @if($doctor->slots->isEmpty())
                    <div class="card-body text-center text-muted py-4">No slots configured yet.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Duration</th>
                                <th>Max Patients</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($days as $num => $name)
                                @if(isset($grouped[$num]))
                                    @foreach($grouped[$num] as $slot)
                                    <tr>
                                        <td><span class="badge badge-soft-primary">{{ $name }}</span></td>
                                        <td>{{ \Carbon\Carbon::parse($slot->slot_start)->format('h:i A') }} – {{ \Carbon\Carbon::parse($slot->slot_end)->format('h:i A') }}</td>
                                        <td>{{ $slot->slot_duration_minutes }} mins</td>
                                        <td>{{ $slot->max_patients }}</td>
                                        <td>
                                            <span class="badge badge-{{ $slot->is_active ? 'success' : 'secondary' }}">
                                                {{ $slot->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn--container">
                                                {{-- Clone --}}
                                                <button type="button"
                                                    class="btn action-btn btn-outline-info"
                                                    title="Clone to other days"
                                                    onclick="openCloneModal({{ $slot->id }}, '{{ $name }}', '{{ \Carbon\Carbon::parse($slot->slot_start)->format('h:i A') }} – {{ \Carbon\Carbon::parse($slot->slot_end)->format('h:i A') }}')">
                                                    <i class="tio-copy"></i>
                                                </button>

                                                {{-- Toggle --}}
                                                <a href="{{ route('vendor.doctor.slot.toggle', [$doctor->id, $slot->id]) }}"
                                                    class="btn action-btn btn-outline-{{ $slot->is_active ? 'secondary' : 'success' }}"
                                                    title="{{ $slot->is_active ? 'Deactivate' : 'Activate' }}">
                                                    <i class="tio-{{ $slot->is_active ? 'block' : 'checkmark-circle' }}"></i>
                                                </a>

                                                {{-- Delete --}}
                                                <a href="{{ route('vendor.doctor.slot.delete', [$doctor->id, $slot->id]) }}"
                                                    class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                    data-id="slot-del-{{ $slot->id }}"
                                                    data-message="Delete this slot?">
                                                    <i class="tio-delete"></i>
                                                </a>
                                                <form id="slot-del-{{ $slot->id }}" action="{{ route('vendor.doctor.slot.delete', [$doctor->id, $slot->id]) }}" method="get">@csrf</form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Help / Instructions Modal --}}
<div class="modal fade" id="slotHelpModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;" role="document">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header" style="background:#f8f9fa;border-bottom:1px solid #e9ecef;">
                <h5 class="modal-title d-flex align-items-center" style="font-size:15px;font-weight:700;">
                    <i class="tio-time mr-2 text-primary" style="font-size:20px;"></i>
                    How Doctor Slots Work
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" style="font-size:13.5px;line-height:1.7;">

                <p class="mb-2"><strong>What is a slot?</strong><br>
                A slot defines the doctor's availability for a specific day and time range. The system automatically divides that range into individual appointments based on the <strong>Duration</strong> you set.</p>

                <hr class="my-2">

                <p class="mb-1"><strong>How to add a slot:</strong></p>
                <ol class="pl-3 mb-2">
                    <li>Select one or more <strong>days</strong> (e.g. Mon, Wed, Fri).</li>
                    <li>Set a <strong>Start Time</strong> and <strong>End Time</strong> for the availability window.</li>
                    <li>Set the <strong>Duration</strong> (in minutes) for each appointment — e.g. 15 mins means a 9:00 AM–11:00 AM window gives 8 slots.</li>
                    <li>Set <strong>Max Patients</strong> allowed per time slot.</li>
                    <li>Click <strong>Add Slot</strong>.</li>
                </ol>

                <hr class="my-2">

                <div class="alert alert-soft-info d-flex" style="font-size:13px;border-radius:8px;gap:10px;align-items:flex-start;">
                    <i class="tio-info-outined mt-1" style="font-size:18px;flex-shrink:0;"></i>
                    <div>
                        <strong>Multiple shifts on the same day</strong><br>
                        To add a <span class="text-dark"><b>morning shift</b></span> <em>and</em> an <span class="text-dark"><b>evening shift</b></span> on the same day, simply add the same day twice with different time ranges.<br><br>
                        <strong>Example:</strong><br>
                        &bull; Monday · 09:00 AM – 12:00 PM (Morning)<br>
                        &bull; Monday · 05:00 PM – 08:00 PM (Evening)<br><br>
                        Both will appear separately in the slot list and patients can book either shift.
                    </div>
                </div>

                <hr class="my-2">

                <p class="mb-1"><strong>Other actions:</strong></p>
                <ul class="pl-3 mb-0">
                    <li><i class="tio-copy text-info"></i> <strong>Clone</strong> — copy a slot's time range to other days instantly.</li>
                    <li><i class="tio-block text-secondary"></i> / <i class="tio-checkmark-circle text-success"></i> <strong>Toggle</strong> — temporarily deactivate a slot without deleting it.</li>
                    <li><i class="tio-delete text-danger"></i> <strong>Delete</strong> — permanently remove a slot.</li>
                </ul>

            </div>
            <div class="modal-footer" style="border-top:1px solid #e9ecef;">
                <button type="button" class="btn btn-primary btn-sm px-4" data-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>

{{-- Clone Modal --}}
<div class="modal fade" id="cloneModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clone Slot</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="cloneForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-2" id="cloneSlotLabel"></p>
                    <label class="input-label">Copy to days:</label>
                    <div class="d-flex flex-wrap" style="gap:6px; margin-top:6px;">
                        @foreach($days as $num => $name)
                        <label class="day-pill" id="clone-pill-{{ $num }}">
                            <input type="checkbox" name="days_of_week[]" value="{{ $num }}"
                                class="clone-day-checkbox" onchange="toggleClonePill({{ $num }})">
                            {{ substr($name, 0, 3) }}
                        </label>
                        @endforeach
                    </div>
                    <div class="mt-2">
                        <a href="#" class="small text-primary mr-2" onclick="selectAllCloneDays(true);return false;">All</a>
                        <a href="#" class="small text-secondary mr-2" onclick="selectAllCloneDays(false);return false;">None</a>
                        <a href="#" class="small text-info" onclick="selectCloneWeekdays();return false;">Weekdays</a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--primary btn-sm">Clone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.day-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 36px;
    border: 2px solid #e7eaf3;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    color: #677788;
    background: #fff;
    margin: 2px;
    transition: all 0.15s;
    user-select: none;
}
.day-pill input[type=checkbox] { display: none; }
.day-pill.active {
    border-color: #377dff;
    background: #377dff;
    color: #fff;
}
</style>
@endsection

@push('script_2')
<script>
    // Add form pills
    function togglePill(num) {
        const cb  = document.querySelector(`#pill-${num} input`);
        const lbl = document.getElementById(`pill-${num}`);
        lbl.classList.toggle('active', cb.checked);
    }
    function selectAllDays(val) {
        document.querySelectorAll('.day-checkbox').forEach(cb => {
            cb.checked = val;
            togglePill(cb.value);
        });
    }
    function selectWeekdays() {
        selectAllDays(false);
        [1,2,3,4,5].forEach(d => {
            const cb = document.querySelector(`#pill-${d} input`);
            cb.checked = true;
            togglePill(d);
        });
    }

    // Clone modal pills
    function toggleClonePill(num) {
        const cb  = document.querySelector(`#clone-pill-${num} input`);
        const lbl = document.getElementById(`clone-pill-${num}`);
        lbl.classList.toggle('active', cb.checked);
    }
    function selectAllCloneDays(val) {
        document.querySelectorAll('.clone-day-checkbox').forEach(cb => {
            cb.checked = val;
            toggleClonePill(cb.value);
        });
    }
    function selectCloneWeekdays() {
        selectAllCloneDays(false);
        [1,2,3,4,5].forEach(d => {
            const cb = document.querySelector(`#clone-pill-${d} input`);
            cb.checked = true;
            toggleClonePill(d);
        });
    }

    function openCloneModal(slotId, dayName, timeLabel) {
        // Reset checkboxes
        selectAllCloneDays(false);

        document.getElementById('cloneSlotLabel').textContent = `${dayName} · ${timeLabel}`;
        document.getElementById('cloneForm').action = "{{ url('vendor/doctor/' . $doctor->id . '/slots') }}/" + slotId + "/clone";

        $('#cloneModal').modal('show');
    }
</script>
@endpush
