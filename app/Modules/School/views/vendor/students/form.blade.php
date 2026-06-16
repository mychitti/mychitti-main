@extends('layouts.vendor.app')
@section('title', $student ? 'Edit Student' : 'New Admission')

@php
    $enr = $student?->currentEnrollment;
    $action = $student
        ? route('vendor.school.students.update', $student->id)
        : route('vendor.school.students.store');
@endphp

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-user-add" style="font-size:22px;"></i></span>
            {{ $student ? 'Edit Student' : 'New Admission' }}
        </h1>
        <a href="{{ route('vendor.school.students.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    <form action="{{ $action }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3"><div class="card-header py-2"><h6 class="mb-0">Student Details</h6></div>
                <div class="card-body"><div class="form-row">
                    <div class="form-group col-md-3"><label class="input-label">First Name *</label>
                        <input name="first_name" class="form-control" value="{{ old('first_name', $student?->first_name) }}" required></div>
                    <div class="form-group col-md-3"><label class="input-label">Last Name</label>
                        <input name="last_name" class="form-control" value="{{ old('last_name', $student?->last_name) }}"></div>
                    <div class="form-group col-md-3"><label class="input-label">DOB *</label>
                        <input type="date" name="dob" class="form-control" value="{{ old('dob', $student?->dob?->format('Y-m-d')) }}" required>
                        @error('dob')<small class="text-danger">{{ $message }}</small>@enderror</div>
                    <div class="form-group col-md-3"><label class="input-label">Gender</label>
                        <select name="gender" class="form-control js-select2-custom">
                            @foreach(['' => '—', 'male'=>'Male','female'=>'Female','other'=>'Other'] as $k=>$v)
                                <option value="{{ $k }}" {{ old('gender',$student?->gender)===$k?'selected':'' }}>{{ $v }}</option>
                            @endforeach
                        </select></div>
                    <div class="form-group col-md-3"><label class="input-label">Blood Group</label>
                        <input name="blood_group" class="form-control" value="{{ old('blood_group', $student?->blood_group) }}"></div>
                    <div class="form-group col-md-3"><label class="input-label">Category</label>
                        <input name="category" class="form-control" placeholder="Gen/OBC/SC/ST" value="{{ old('category', $student?->category) }}"></div>
                    <div class="form-group col-md-3"><label class="input-label">Phone</label>
                        <input name="phone" class="form-control" value="{{ old('phone', $student?->phone) }}"></div>
                    <div class="form-group col-md-3"><label class="input-label">Email</label>
                        <input name="email" class="form-control" value="{{ old('email', $student?->email) }}"></div>
                </div></div></div>

                <div class="card mb-3"><div class="card-header py-2"><h6 class="mb-0">Guardian & Address</h6></div>
                <div class="card-body"><div class="form-row">
                    <div class="form-group col-md-4"><label class="input-label">Guardian Name</label>
                        <input name="guardian_name" class="form-control" value="{{ old('guardian_name', $student?->guardian_name) }}"></div>
                    <div class="form-group col-md-4"><label class="input-label">Guardian Phone</label>
                        <input name="guardian_phone" class="form-control" value="{{ old('guardian_phone', $student?->guardian_phone) }}"></div>
                    <div class="form-group col-md-4"><label class="input-label">Relation</label>
                        <input name="guardian_relation" class="form-control" placeholder="Father/Mother/Guardian" value="{{ old('guardian_relation', $student?->guardian_relation) }}"></div>
                    <div class="form-group col-md-12"><label class="input-label">Address</label>
                        <input name="address" class="form-control" value="{{ old('address', $student?->address) }}"></div>
                    <div class="form-group col-md-4"><label class="input-label">City</label>
                        <input name="city" class="form-control" value="{{ old('city', $student?->city) }}"></div>
                    <div class="form-group col-md-4"><label class="input-label">State</label>
                        <input name="state" class="form-control" value="{{ old('state', $student?->state) }}"></div>
                    <div class="form-group col-md-4"><label class="input-label">Pincode</label>
                        <input name="pincode" class="form-control" value="{{ old('pincode', $student?->pincode) }}"></div>
                </div></div></div>

                @if(!$student)
                <div class="card mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Documents <small class="text-muted">(optional — TC, marksheets, birth certificate…)</small></h6>
                        <button type="button" class="btn btn-xs btn-outline-primary" onclick="addDocRow()"><i class="tio-add"></i> Add</button>
                    </div>
                    <div class="card-body" id="docRows">
                        <div class="form-row align-items-end doc-row">
                            <div class="form-group col-md-3 mb-2"><label class="input-label">Type</label>
                                <select name="doc_type[]" class="form-control form-control-sm">
                                    @foreach($docTypes as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                                </select></div>
                            <div class="form-group col-md-4 mb-2"><label class="input-label">Title / Note</label>
                                <input name="doc_title[]" class="form-control form-control-sm" maxlength="190" placeholder="e.g. TC from ABC School"></div>
                            <div class="form-group col-md-4 mb-2"><label class="input-label">File</label>
                                <input type="file" name="doc_file[]" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.webp"></div>
                            <div class="form-group col-md-1 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-block" onclick="this.closest('.doc-row').remove()"><i class="tio-delete"></i></button></div>
                        </div>
                        <small class="text-muted">PDF or image, max 8 MB each.</small>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="card mb-3"><div class="card-header py-2"><h6 class="mb-0">Enrollment</h6></div>
                <div class="card-body">
                    <div class="form-group"><label class="input-label">Admission No</label>
                        <input name="admission_no" class="form-control" value="{{ old('admission_no', $student?->admission_no ?? $nextAdmissionNo) }}">
                        <small class="text-muted">Auto-generated; editable.</small></div>
                    <div class="form-group"><label class="input-label">Admission Date</label>
                        <input type="date" name="admission_date" class="form-control" value="{{ old('admission_date', $student?->admission_date?->format('Y-m-d') ?? date('Y-m-d')) }}"></div>
                    <div class="form-group"><label class="input-label">Session</label>
                        <select name="academic_session_id" class="form-control js-select2-custom">
                            <option value="">—</option>
                            @foreach($sessions as $se)
                                <option value="{{ $se->id }}" {{ (old('academic_session_id', $enr?->academic_session_id ?? $currentSession?->id) == $se->id)?'selected':'' }}>{{ $se->name }}</option>
                            @endforeach
                        </select></div>
                    <div class="form-group"><label class="input-label">Class</label>
                        <select name="school_class_id" class="form-control js-select2-custom">
                            <option value="">—</option>
                            @foreach($classes as $c)<option value="{{ $c->id }}" {{ (old('school_class_id', $enr?->school_class_id)==$c->id)?'selected':'' }}>{{ $c->name }}</option>@endforeach
                        </select></div>
                    <div class="form-group"><label class="input-label">Section</label>
                        <select name="class_section_id" class="form-control js-select2-custom">
                            <option value="">—</option>
                            @foreach($sections as $s)<option value="{{ $s->id }}" {{ (old('class_section_id', $enr?->class_section_id)==$s->id)?'selected':'' }}>{{ $s->schoolClass?->name }} - {{ $s->name }}</option>@endforeach
                        </select></div>
                    <div class="form-group"><label class="input-label">Roll No</label>
                        <input name="roll_no" class="form-control" value="{{ old('roll_no', $enr?->roll_no) }}" placeholder="Auto if blank"></div>
                </div></div>

                <div class="card mb-3"><div class="card-header py-2"><h6 class="mb-0">Photo</h6></div>
                <div class="card-body">
                    @if($student?->photo)<img src="{{ asset('storage/app/public/school/students/'.$student->photo) }}" class="img-fluid rounded mb-2" style="max-height:120px;">@endif
                    <input type="file" name="photo" accept="image/*" class="form-control-file">
                </div></div>

                <button class="btn btn--primary btn-block">{{ $student ? 'Update Student' : 'Admit Student' }}</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('script_2')
<script>
function addDocRow(){
    var rows = document.getElementById('docRows');
    var first = rows.querySelector('.doc-row');
    var clone = first.cloneNode(true);
    clone.querySelectorAll('input, select').forEach(function(el){ if(el.type==='file'){ el.value=''; } else { el.value=''; } });
    rows.insertBefore(clone, rows.querySelector('small'));
}
</script>
@endpush
