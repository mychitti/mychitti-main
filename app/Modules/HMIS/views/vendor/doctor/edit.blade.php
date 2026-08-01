@extends('layouts.vendor.app')
@section('title', 'Edit Doctor')

@section('content')
<div class="content container-fluid">
    @include('hmis::vendor.hospital._hospital_submenu_header')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-edit" style="font-size:22px;"></i></span>
            Edit Doctor Profile
        </h1>
        <a href="{{ route('vendor.doctor.slots', $doctor->id) }}" class="btn btn-outline-info btn-sm">
            <i class="tio-time mr-1"></i> Manage Slots
            <span class="badge badge-info ml-1">{{ $doctor->slots->count() }}</span>
        </a>
    </div>
 
    <form action="{{ route('vendor.doctor.update', $doctor->id) }}" method="POST">
        @csrf
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Basic Information</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label class="input-label">Employee <span class="text-danger">*</span></label>
                        <select name="emp_id" class="form-control" required>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}" {{ $doctor->emp_id == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->f_name }} {{ $emp->l_name }} ({{ $emp->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="input-label">Specialization <span class="text-danger">*</span></label>
                        <input type="text" name="specialization" class="form-control"
                            value="{{ $doctor->specialization }}" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="input-label">Registration Number</label>
                        <input type="text" name="registration_number" class="form-control"
                            value="{{ $doctor->registration_number }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="input-label">Department</label>
                        <input type="text" name="department" class="form-control"
                            value="{{ $doctor->department }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 form-group mb-0">
                        <label class="input-label">OPD Room</label>
                        <input type="text" name="opd_room" class="form-control"
                            value="{{ $doctor->opd_room }}">
                    </div>
                    <div class="col-md-3 form-group mb-0">
                        <label class="input-label">Consultation Fee (₹)</label>
                        <input type="number" name="consultation_fee" class="form-control"
                            value="{{ $doctor->consultation_fee }}" min="0" step="0.01">
                    </div>
                    <div class="col-md-3 form-group mb-0">
                        <label class="input-label">Services</label>
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
                    <div class="col-md-3 form-group mb-0">
                        <label class="input-label">Bio</label>
                        <textarea name="bio" class="form-control" rows="3">{{ $doctor->bio }}</textarea>
                    </div>
                </div>
                <div class="row mt-3">
                    @include('hmis::vendor.doctor._rebook_days', ['rebookDays' => $doctor->rebook_days ?? 0])
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-4">
            <button type="submit" class="btn btn--primary">Update</button>
        </div>
    </form>
</div>
@endsection
