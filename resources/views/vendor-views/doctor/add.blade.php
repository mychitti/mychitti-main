@extends('layouts.vendor.app')
@section('title', 'Add Doctor')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-user-add" style="font-size:22px;"></i></span>
            Add Doctor Profile
        </h1>
    </div>

    <form action="{{ route('vendor.doctor.store') }}" method="POST">
        @csrf
        <div class="row">
            {{-- Left --}}
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

                        {{-- New staff fields — shown when no existing employee is selected --}}
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
                                        placeholder="doctor@clinic.com" value="{{ old('new_email') }}">
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
                                <label class="input-label">Specialization <span class="text-danger">*</span></label>
                                <input type="text" name="specialization" class="form-control" placeholder="e.g. Cardiology" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">Qualification</label>
                                <input type="text" name="qualification" class="form-control" placeholder="e.g. MBBS, MD">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="input-label">Registration Number</label>
                                <input type="text" name="registration_number" class="form-control" placeholder="Medical council reg. no.">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">Department</label>
                                <input type="text" name="department" class="form-control" placeholder="e.g. Cardiology, General Medicine">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="input-label">OPD Room</label>
                                <input type="text" name="opd_room" class="form-control" placeholder="e.g. Room 101">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">Consultation Fee (₹)</label>
                                <input type="number" name="consultation_fee" class="form-control" placeholder="0.00" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="input-label">Bio</label>
                                <textarea name="bio" class="form-control" rows="3" placeholder="Short bio / about the doctor"></textarea>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">Services Offered</label>
                                <select name="services[]" multiple="multiple"
                                    class="form-control js-select2-custom js-example-basic-multiple"
                                    data-placeholder="Select services">
                                    <option value=""></option>
                                    @foreach ($store_services as $service)
                                        <option value="{{ $service->id }}"
                                            {{ in_array($service->id, (array) old('services', [])) ? 'selected' : '' }}>
                                            {{ $service->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right --}}
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Availability</h5></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="input-label">Available Days</label>
                            <div class="row">
                                @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
                                <div class="col-6 mb-2">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="available_days[]" value="{{ $day }}"
                                            class="custom-control-input" id="day_{{ $day }}">
                                        <label class="custom-control-label" for="day_{{ $day }}">{{ $day }}</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="input-label">From</label>
                            <input type="time" name="available_from" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="input-label">To</label>
                            <input type="time" name="available_to" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('vendor.doctor.list') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn--primary">Save Doctor</button>
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
