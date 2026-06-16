@extends('layouts.vendor.app')
@section('title', 'Add Nurse')

@section('content')
<div class="content container-fluid">
    @include('hmis::vendor.hospital._hospital_submenu_header')
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-user-add" style="font-size:22px;"></i></span>
            Add Nurse Profile
        </h1>
    </div>
 
    <form action="{{ route('vendor.nurse.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Basic Information</h5></div>
                    <div class="card-body">

                        {{-- Staff selection or auto-create --}}
                        <div class="form-group">
                            <label class="input-label">Link to existing staff</label>
                            <select name="emp_id" id="empSelect" class="form-control" onchange="toggleNewStaff(this.value)">
                                <option value="">-- Create new staff automatically --</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('emp_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->f_name }} {{ $emp->l_name }} ({{ $emp->email }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Leave blank to create a new staff profile automatically.</small>
                        </div>

                        {{-- New staff block --}}
                        <div id="newStaffBlock" style="{{ old('emp_id') ? 'display:none;' : '' }}">
                            <div class="alert alert-info py-2 mb-3" style="font-size:13px;">
                                <i class="tio-info-outined mr-1"></i>
                                A new staff account will be created with the details below.
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="input-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="new_f_name" class="form-control"
                                        placeholder="First name" value="{{ old('new_f_name') }}">
                                    @error('new_f_name')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="input-label">Last Name</label>
                                    <input type="text" name="new_l_name" class="form-control"
                                        placeholder="Last name" value="{{ old('new_l_name') }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="input-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="new_email" class="form-control"
                                        placeholder="nurse@clinic.com" value="{{ old('new_email') }}">
                                    @error('new_email')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="input-label">Phone</label>
                                    <input type="text" name="new_phone" class="form-control"
                                        placeholder="+91 99999 99999" value="{{ old('new_phone') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="input-label">Qualification</label>
                                <input type="text" name="qualification" class="form-control"
                                    placeholder="e.g. B.Sc Nursing, GNM" value="{{ old('qualification') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">Registration Number</label>
                                <input type="text" name="registration_number" class="form-control"
                                    placeholder="Nursing council reg. no." value="{{ old('registration_number') }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="input-label">Department</label>
                                <input type="text" name="department" class="form-control"
                                    placeholder="e.g. ICU, General, Paediatrics" value="{{ old('department') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">Assigned Ward</label>
                                <select name="ward_id" class="form-control">
                                    <option value="">— None —</option>
                                    @foreach($wards as $ward)
                                        <option value="{{ $ward->id }}" {{ old('ward_id') == $ward->id ? 'selected' : '' }}>
                                            {{ $ward->ward_name }}
                                            ({{ \App\Models\Ward::TYPES[$ward->ward_type] ?? $ward->ward_type }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="input-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"
                                placeholder="Additional notes...">{{ old('notes') }}</textarea>
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
                                {{ old('shift', 'day') === $key ? 'checked' : '' }}>
                            <label class="custom-control-label" for="shift_{{ $key }}">{{ $label }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('vendor.nurse.list') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn--primary">Save Nurse</button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('script_2')
<script>
function toggleNewStaff(empId) {
    document.getElementById('newStaffBlock').style.display = empId ? 'none' : '';
}
</script>
@endpush
@endsection
