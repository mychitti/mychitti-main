@extends('layouts.vendor.app')
@section('title', 'My Doctor Profile')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-user" style="font-size:22px;"></i></span>
                My Doctor Profile
            </h1>
            <small class="text-muted ml-4">
                {{ $doctor->specialization }}
                @if($doctor->department) &mdash; {{ $doctor->department }} @endif
            </small>
        </div>
        <a href="{{ route('vendor.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> Back
        </a>
    </div>

    {{-- Info strip (read-only) --}}
    <div class="alert alert-soft-info d-flex flex-wrap gap-3 mb-3 py-2 px-3" style="gap:16px; font-size:13px;">
        <span><i class="tio-certificate mr-1"></i> <strong>Reg. No.:</strong> {{ $doctor->registration_number ?: '—' }}</span>
        <span><i class="tio-building mr-1"></i> <strong>Department:</strong> {{ $doctor->department ?: '—' }}</span>
        <span><i class="tio-door mr-1"></i> <strong>OPD Room:</strong> {{ $doctor->opd_room ?: '—' }}</span>
    </div>

    <div class="row">
        {{-- Left: Editable profile --}}
        <div class="col-md-8">
            <form action="{{ route('vendor.my-doctor-profile.update') }}" method="POST">
                @csrf
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Profile Details</h5></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="input-label">Consultation Fee (₹)</label>
                                <input type="number" name="consultation_fee" class="form-control"
                                    value="{{ $doctor->consultation_fee }}" min="0" step="0.01">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">Services Offered</label>
                                <select name="services[]" multiple="multiple"
                                    class="form-control js-select2-custom js-example-basic-multiple"
                                    data-placeholder="Select services">
                                    <option value=""></option>
                                    @foreach ($store_services as $service)
                                        <option value="{{ $service->id }}"
                                            {{ in_array($service->id, $doctor->services->pluck('item_id')->toArray()) ? 'selected' : '' }}>
                                            {{ $service->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="input-label">Bio</label>
                            <textarea name="bio" class="form-control" rows="4"
                                placeholder="Short bio / about yourself">{{ $doctor->bio }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn--primary">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Right: Add Slot --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Add Slot</h5></div>
                <div class="card-body">
                    <form action="{{ route('vendor.my-doctor-profile.slot.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="input-label">Days <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap" style="gap:6px;">
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
                                <label class="input-label">Duration (mins)</label>
                                <input type="number" name="slot_duration_minutes" class="form-control" value="15" min="5" required>
                            </div>
                            <div class="col-6 form-group">
                                <label class="input-label">Max Patients</label>
                                <input type="number" name="max_patients" class="form-control" value="10" min="1" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn--primary w-100">Add Slot</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Slot list --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center"
                     style="cursor:pointer;" data-toggle="collapse" data-target="#slotsSection">
                    <h5 class="card-title mb-0">
                        <i class="tio-time mr-1"></i> My Slots
                        <span class="badge badge-soft-primary ml-1">{{ $doctor->slots->count() }}</span>
                    </h5>
                    <i class="tio-chevron-down"></i>
                </div>
                <div class="collapse show" id="slotsSection">
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
                                                    <button type="button" class="btn btn-sm btn-outline-info"
                                                        onclick="openCloneModal({{ $slot->id }}, '{{ $name }}', '{{ \Carbon\Carbon::parse($slot->slot_start)->format('h:i A') }} – {{ \Carbon\Carbon::parse($slot->slot_end)->format('h:i A') }}')"
                                                        title="Clone to other days">
                                                        <i class="tio-copy"></i>
                                                    </button>
                                                    <a href="{{ route('vendor.my-doctor-profile.slot.toggle', $slot->id) }}"
                                                        class="btn btn-sm btn-outline-{{ $slot->is_active ? 'secondary' : 'success' }}"
                                                        title="{{ $slot->is_active ? 'Deactivate' : 'Activate' }}">
                                                        <i class="tio-{{ $slot->is_active ? 'block' : 'checkmark-circle' }}"></i>
                                                    </a>
                                                    <a href="{{ route('vendor.my-doctor-profile.slot.delete', $slot->id) }}"
                                                        class="btn btn-sm btn--danger btn-outline-danger form-alert"
                                                        data-id="slot-del-{{ $slot->id }}"
                                                        data-message="Delete this slot?">
                                                        <i class="tio-delete"></i>
                                                    </a>
                                                    <form id="slot-del-{{ $slot->id }}" action="{{ route('vendor.my-doctor-profile.slot.delete', $slot->id) }}" method="get">@csrf</form>
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
    display: inline-flex; align-items: center; justify-content: center;
    width: 40px; height: 36px; border: 2px solid #e7eaf3; border-radius: 6px;
    cursor: pointer; font-size: 12px; font-weight: 600; color: #677788;
    background: #fff; margin: 2px; transition: all 0.15s; user-select: none;
}
.day-pill input[type=checkbox] { display: none; }
.day-pill.active { border-color: #377dff; background: #377dff; color: #fff; }
</style>

@push('script_2')
<script>
function togglePill(num) {
    const cb = document.querySelector(`#pill-${num} input`);
    document.getElementById(`pill-${num}`).classList.toggle('active', cb.checked);
}
function selectAllDays(val) {
    document.querySelectorAll('.day-checkbox').forEach(cb => { cb.checked = val; togglePill(cb.value); });
}
function selectWeekdays() {
    selectAllDays(false);
    [1,2,3,4,5].forEach(d => { const cb = document.querySelector(`#pill-${d} input`); cb.checked = true; togglePill(d); });
}
function toggleClonePill(num) {
    const cb = document.querySelector(`#clone-pill-${num} input`);
    document.getElementById(`clone-pill-${num}`).classList.toggle('active', cb.checked);
}
function selectAllCloneDays(val) {
    document.querySelectorAll('.clone-day-checkbox').forEach(cb => { cb.checked = val; toggleClonePill(cb.value); });
}
function selectCloneWeekdays() {
    selectAllCloneDays(false);
    [1,2,3,4,5].forEach(d => { const cb = document.querySelector(`#clone-pill-${d} input`); cb.checked = true; toggleClonePill(d); });
}
function openCloneModal(slotId, dayName, timeLabel) {
    selectAllCloneDays(false);
    document.getElementById('cloneSlotLabel').textContent = `${dayName} · ${timeLabel}`;
    document.getElementById('cloneForm').action = "{{ url('my-doctor-profile/slots') }}/" + slotId + "/clone";
    $('#cloneModal').modal('show');
}
</script>
@endpush
@endsection
