@extends('layouts.vendor.app')
@section('title', 'New Exam')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-add-circle mr-1"></i> New Exam</h1>
        <a href="{{ route('vendor.school.exams.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    <div class="row justify-content-center"><div class="col-lg-6">
        <form action="{{ route('vendor.school.exams.store') }}" method="POST">
            @csrf
            <div class="card"><div class="card-body">
                <div class="form-group"><label class="input-label">Exam Name *</label>
                    <input name="name" class="form-control" placeholder="Mid-Term Examination" required></div>
                <div class="form-group"><label class="input-label">Type</label>
                    <select name="exam_type" class="form-control js-select2-custom">
                        @foreach(\App\Models\Exam::TYPES as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                    </select></div>
                <div class="form-group"><label class="input-label">Class *</label>
                    <select name="school_class_id" class="form-control js-select2-custom" required>
                        <option value="">Select class</option>
                        @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select></div>
                <div class="form-group mb-0"><label class="input-label">Session</label>
                    <select name="academic_session_id" class="form-control js-select2-custom">
                        @foreach($sessions as $s)<option value="{{ $s->id }}" {{ $s->is_current?'selected':'' }}>{{ $s->name }}</option>@endforeach
                    </select></div>
            </div>
            <div class="card-footer text-right"><button class="btn btn--primary">Create Exam</button></div></div>
        </form>
    </div></div>
</div>
@endsection
