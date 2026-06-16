@extends('layouts.vendor.app')
@section('title', 'Issue Gate Pass')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-exit-to-app mr-1"></i> Issue Short Leave / Gate Pass</h1>
        <a href="{{ route('vendor.school.short-leave.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="form-row align-items-end mb-0">
            <div class="form-group col-md-4 mb-2 mb-md-0">
                <label class="input-label">Class</label>
                <select name="class_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Select class</option>
                    @foreach($classes as $c)<option value="{{ $c->id }}" @selected((string)$classId === (string)$c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div class="form-group col-md-4 mb-0">
                <label class="input-label">Section</label>
                <select name="section_id" class="form-control" onchange="this.form.submit()">
                    <option value="">All sections</option>
                    @foreach($sections->where('school_class_id', $classId) as $s)<option value="{{ $s->id }}" @selected((string)$sectionId === (string)$s->id)>{{ $s->name }}</option>@endforeach
                </select>
            </div>
        </form>
    </div></div>

    @if($classId)
        <form action="{{ route('vendor.school.short-leave.store') }}" method="POST">
            @csrf
            <div class="card"><div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="input-label">Student *</label>
                        <select name="student_id" class="form-control js-select2-custom" required>
                            <option value="">Select student</option>
                            @foreach($students as $e)<option value="{{ $e->student->id }}">{{ $e->student->name }} — {{ $e->student->admission_no }} (Roll {{ $e->roll_no }})</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="input-label">Out Time *</label>
                        <input type="time" name="out_time" class="form-control" value="{{ now()->format('H:i') }}" required>
                    </div>
                    <div class="form-group col-md-3 d-flex align-items-end pb-2">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_returning" name="is_returning" value="1" checked onchange="retUI()">
                            <label class="custom-control-label" for="is_returning">Returning today</label>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4"><label class="input-label">Picked up by</label>
                        <input name="taken_by" class="form-control" maxlength="150" placeholder="Guardian / self"></div>
                    <div class="form-group col-md-4"><label class="input-label">Relation</label>
                        <input name="taken_by_relation" class="form-control" maxlength="60" placeholder="Father / Mother / Self"></div>
                    <div class="form-group col-md-4"><label class="input-label">Contact</label>
                        <input name="contact" class="form-control" maxlength="30" placeholder="phone"></div>
                </div>
                <div class="form-group"><label class="input-label">Reason</label>
                    <input name="reason" class="form-control" maxlength="500" placeholder="e.g. doctor appointment, family emergency"></div>
                <div class="custom-control custom-switch mb-2" id="halfWrap" style="display:none;">
                    <input type="checkbox" class="custom-control-input" id="mark_half_day" name="mark_half_day" value="1">
                    <label class="custom-control-label" for="mark_half_day">Mark today's attendance as Half-Day</label>
                </div>
                <div class="text-right">
                    <button class="btn btn--primary"><i class="tio-receipt"></i> Issue &amp; Print Slip</button>
                </div>
            </div></div>
        </form>
    @else
        <div class="card"><div class="card-body text-center text-muted py-5">Select a class above to choose a student.</div></div>
    @endif
</div>
@endsection

@push('script_2')
<script>
function retUI() {
    document.getElementById('halfWrap').style.display = document.getElementById('is_returning').checked ? 'none' : 'block';
}
retUI();
</script>
@endpush
