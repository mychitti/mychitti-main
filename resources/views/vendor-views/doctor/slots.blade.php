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
                            <label class="input-label">Day <span class="text-danger">*</span></label>
                            <select name="day_of_week" class="form-control" required>
                                <option value="">-- Select Day --</option>
                                @foreach($days as $num => $name)
                                    <option value="{{ $num }}">{{ $name }}</option>
                                @endforeach
                            </select>
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
                                                <a href="{{ route('vendor.doctor.slot.toggle', [$doctor->id, $slot->id]) }}"
                                                    class="btn btn-sm btn-outline-{{ $slot->is_active ? 'secondary' : 'success' }}"
                                                    title="{{ $slot->is_active ? 'Deactivate' : 'Activate' }}">
                                                    <i class="tio-{{ $slot->is_active ? 'block' : 'checkmark-circle' }}"></i>
                                                </a>
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
@endsection
