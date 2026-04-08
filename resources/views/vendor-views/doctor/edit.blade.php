@extends('layouts.vendor.app')
@section('title', 'Edit Doctor')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-edit" style="font-size:22px;"></i></span>
            Edit Doctor Profile
        </h1>
    </div>

    @php $available_days = $doctor->available_days ? explode(',', $doctor->available_days) : []; @endphp

    <form action="{{ route('vendor.doctor.update', $doctor->id) }}" method="POST">
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
                                    <option value="{{ $emp->id }}" {{ $doctor->emp_id == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->f_name }} {{ $emp->l_name }} ({{ $emp->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="input-label">Specialization <span class="text-danger">*</span></label>
                                <input type="text" name="specialization" class="form-control" value="{{ $doctor->specialization }}" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">Qualification</label>
                                <input type="text" name="qualification" class="form-control" value="{{ $doctor->qualification }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="input-label">Registration Number</label>
                                <input type="text" name="registration_number" class="form-control" value="{{ $doctor->registration_number }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">Department</label>
                                <input type="text" name="department" class="form-control" value="{{ $doctor->department }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="input-label">OPD Room</label>
                                <input type="text" name="opd_room" class="form-control" value="{{ $doctor->opd_room }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">Consultation Fee (₹)</label>
                                <input type="number" name="consultation_fee" class="form-control" value="{{ $doctor->consultation_fee }}" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="input-label">Bio</label>
                            <textarea name="bio" class="form-control" rows="3">{{ $doctor->bio }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

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
                                            class="custom-control-input" id="day_{{ $day }}"
                                            {{ in_array($day, $available_days) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="day_{{ $day }}">{{ $day }}</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="input-label">From</label>
                            <input type="time" name="available_from" class="form-control" value="{{ $doctor->available_from }}">
                        </div>
                        <div class="form-group">
                            <label class="input-label">To</label>
                            <input type="time" name="available_to" class="form-control" value="{{ $doctor->available_to }}">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('vendor.doctor.slots', $doctor->id) }}" class="btn btn-outline-info">Manage Slots</a>
                    <button type="submit" class="btn btn--primary">Update</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
