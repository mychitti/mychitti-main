@extends('layouts.vendor.app')
@section('title', 'Edit Nurse')

@section('content')
<div class="content container-fluid">
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
                        @foreach(\App\Models\NurseProfile::SHIFTS as $key => $label)
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" name="shift" id="shift_{{ $key }}" value="{{ $key }}"
                                class="custom-control-input"
                                {{ old('shift', $nurse->shift) === $key ? 'checked' : '' }}>
                            <label class="custom-control-label" for="shift_{{ $key }}">{{ $label }}</label>
                        </div>
                        @endforeach
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
