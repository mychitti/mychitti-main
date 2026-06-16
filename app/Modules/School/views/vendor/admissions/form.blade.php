@extends('layouts.vendor.app')
@section('title', $enquiry ? 'Edit Enquiry' : 'New Enquiry')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-user-add mr-1"></i> {{ $enquiry ? 'Edit Enquiry' : 'New Admission Enquiry' }}</h1>
        <a href="{{ route('vendor.school.admissions.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    <form action="{{ $enquiry ? route('vendor.school.admissions.update', $enquiry->id) : route('vendor.school.admissions.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3"><div class="card-header py-3"><i class="tio-user mr-1 text-primary"></i> Applicant</div><div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6"><label class="input-label">Student Name *</label>
                            <input name="student_name" class="form-control" value="{{ old('student_name', $enquiry?->student_name) }}" required>
                            @error('student_name')<small class="text-danger">{{ $message }}</small>@enderror</div>
                        <div class="form-group col-md-3"><label class="input-label">Date of Birth</label>
                            <input type="date" name="dob" class="form-control" value="{{ old('dob', $enquiry?->dob?->format('Y-m-d')) }}"></div>
                        <div class="form-group col-md-3"><label class="input-label">Gender</label>
                            <select name="gender" class="form-control js-select2-custom">
                                @foreach(['' => '—', 'male'=>'Male','female'=>'Female','other'=>'Other'] as $k=>$v)
                                    <option value="{{ $k }}" @selected(old('gender', $enquiry?->gender)===$k)>{{ $v }}</option>
                                @endforeach
                            </select></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4"><label class="input-label">Seeking Class</label>
                            <select name="seeking_class_id" class="form-control js-select2-custom">
                                <option value="">—</option>
                                @foreach($classes as $c)<option value="{{ $c->id }}" @selected(old('seeking_class_id', $enquiry?->seeking_class_id)==$c->id)>{{ $c->name }}</option>@endforeach
                            </select></div>
                        <div class="form-group col-md-8"><label class="input-label">Previous School</label>
                            <input name="previous_school" class="form-control" value="{{ old('previous_school', $enquiry?->previous_school) }}"></div>
                    </div>
                </div></div>

                <div class="card mb-3"><div class="card-header py-3"><i class="tio-contacts mr-1 text-primary"></i> Guardian &amp; Contact</div><div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6"><label class="input-label">Guardian Name</label>
                            <input name="guardian_name" class="form-control" value="{{ old('guardian_name', $enquiry?->guardian_name) }}"></div>
                        <div class="form-group col-md-3"><label class="input-label">Guardian Phone</label>
                            <input name="guardian_phone" class="form-control" value="{{ old('guardian_phone', $enquiry?->guardian_phone) }}"></div>
                        <div class="form-group col-md-3"><label class="input-label">Alt Phone</label>
                            <input name="phone" class="form-control" value="{{ old('phone', $enquiry?->phone) }}"></div>
                    </div>
                    <div class="form-group mb-0"><label class="input-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $enquiry?->email) }}">
                        @error('email')<small class="text-danger">{{ $message }}</small>@enderror</div>
                </div></div>
            </div>

            <div class="col-lg-4">
                <div class="card"><div class="card-header py-3"><i class="tio-poll mr-1 text-primary"></i> Enquiry</div><div class="card-body">
                    <div class="form-group"><label class="input-label">Source</label>
                        <select name="source" class="form-control js-select2-custom">
                            <option value="">—</option>
                            @foreach($sources as $s)<option value="{{ $s }}" @selected(old('source', $enquiry?->source)===$s)>{{ $s }}</option>@endforeach
                        </select></div>
                    <div class="form-group"><label class="input-label">Status</label>
                        <select name="status" class="form-control js-select2-custom">
                            @foreach($statuses as $k => $v)<option value="{{ $k }}" @selected(old('status', $enquiry?->status ?? 'new')===$k)>{{ $v }}</option>@endforeach
                        </select></div>
                    <div class="form-group"><label class="input-label">Follow-up Date</label>
                        <input type="date" name="follow_up_date" class="form-control" value="{{ old('follow_up_date', $enquiry?->follow_up_date?->format('Y-m-d')) }}"></div>
                    <div class="form-group mb-0"><label class="input-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3">{{ old('remarks', $enquiry?->remarks) }}</textarea></div>
                </div>
                <div class="card-footer text-right"><button class="btn btn--primary"><i class="tio-save"></i> {{ $enquiry ? 'Update' : 'Save Enquiry' }}</button></div></div>
            </div>
        </div>
    </form>
</div>
@endsection
