@extends('layouts.vendor.app')
@section('title', 'Doctor Slots')

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
            <a href="{{ route('vendor.doctor.list') }}" class="btn btn-outline-secondary btn-sm">
                <i class="tio-arrow-backward"></i> Back
            </a>
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
                                <input type="time" name="slot_start" class="form-control" required>
                            </div>
                            <div class="col-6 form-group">
                                <label class="input-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" name="slot_end" class="form-control" required>
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
                                                    class="btn btn-sm btn-outline-info"
                                                    title="Clone to other days"
                                                    onclick="openCloneModal({{ $slot->id }}, '{{ $name }}', '{{ \Carbon\Carbon::parse($slot->slot_start)->format('h:i A') }} – {{ \Carbon\Carbon::parse($slot->slot_end)->format('h:i A') }}')">
                                                    <i class="tio-copy"></i>
                                                </button>

                                                {{-- Toggle --}}
                                                <a href="{{ route('vendor.doctor.slot.toggle', [$doctor->id, $slot->id]) }}"
                                                    class="btn btn-sm btn-outline-{{ $slot->is_active ? 'secondary' : 'success' }}"
                                                    title="{{ $slot->is_active ? 'Deactivate' : 'Activate' }}">
                                                    <i class="tio-{{ $slot->is_active ? 'block' : 'checkmark-circle' }}"></i>
                                                </a>

                                                {{-- Delete --}}
                                                <a href="{{ route('vendor.doctor.slot.delete', [$doctor->id, $slot->id]) }}"
                                                    class="btn btn-sm btn--danger btn-outline-danger form-alert"
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
