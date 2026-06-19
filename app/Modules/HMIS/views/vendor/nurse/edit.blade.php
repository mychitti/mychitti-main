@extends('layouts.vendor.app')
@section('title', 'Edit Nurse')

@section('content')
<div class="content container-fluid">
    @include('hmis::vendor.hospital._hospital_submenu_header')
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-edit" style="font-size:22px;"></i></span>
            Edit Nurse — {{ $nurse->employee?->f_name }} {{ $nurse->employee?->l_name }}
        </h1>
    </div>
 
    <form action="{{ route('vendor.nurse.update', $nurse->id) }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Basic Information</h5></div>
                    <div class="card-body">

                        <div class="form-group">
                            <label class="input-label">Employee <span class="text-danger">*</span></label>
                            <select name="emp_id" class="form-control" required>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ $nurse->emp_id == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->f_name }} {{ $emp->l_name }} ({{ $emp->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="input-label">Qualification</label>
                                <input type="text" name="qualification" class="form-control"
                                    value="{{ old('qualification', $nurse->qualification) }}"
                                    placeholder="e.g. B.Sc Nursing, GNM">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">Registration Number</label>
                                <input type="text" name="registration_number" class="form-control"
                                    value="{{ old('registration_number', $nurse->registration_number) }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="input-label">Department</label>
                                <input type="text" name="department" class="form-control"
                                    value="{{ old('department', $nurse->department) }}"
                                    placeholder="e.g. ICU, General">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">Assigned Ward</label>
                                <select name="ward_id" class="form-control">
                                    <option value="">— None —</option>
                                    @foreach($wards as $ward)
                                        <option value="{{ $ward->id }}"
                                            {{ old('ward_id', $nurse->ward_id) == $ward->id ? 'selected' : '' }}>
                                            {{ $ward->ward_name }}
                                            ({{ \App\Models\Ward::TYPES[$ward->ward_type] ?? $ward->ward_type }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="input-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $nurse->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Shift</h5></div>
                    <div class="card-body">
                        <p class="text-muted mb-2" style="font-size:13px;">
                            <i class="tio-info mr-1"></i> Shift is managed centrally in <strong>Staff Management → Shifts</strong>
                            and assigned to the staff member.
                            @if($nurse->employee?->storeShift)
                                <br>Current: <strong>{{ $nurse->employee->storeShift->name }}</strong>
                                ({{ \Carbon\Carbon::parse($nurse->employee->storeShift->start_time)->format('h:i A') }}–{{ \Carbon\Carbon::parse($nurse->employee->storeShift->end_time)->format('h:i A') }})
                            @endif
                        </p>
                        @if($nurse->emp_id && Route::has('vendor.staff.edit'))
                            <a href="{{ route('vendor.staff.edit', $nurse->emp_id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="tio-edit mr-1"></i> Edit Staff Profile / Shift
                            </a>
                        @endif
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('vendor.nurse.list') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn--primary">Update Nurse</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
