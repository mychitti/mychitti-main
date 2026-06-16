@extends('layouts.vendor.app')
@section('title', 'File Student Leave')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-calendar-note mr-1"></i> File Student Leave</h1>
        <a href="{{ route('vendor.school.student-leave.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    {{-- Step 1: pick class / section to load students --}}
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
        <form action="{{ route('vendor.school.student-leave.store') }}" method="POST">
            @csrf
            <div class="card"><div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="input-label">Student *</label>
                        <select name="student_id" class="form-control js-select2-custom" required>
                            <option value="">Select student</option>
                            @foreach($students as $e)
                                <option value="{{ $e->student->id }}">{{ $e->student->name }} — {{ $e->student->admission_no }} (Roll {{ $e->roll_no }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="input-label">Leave Type *</label>
                        <select name="leave_type" class="form-control">
                            @foreach(\App\Models\StudentLeave::TYPES as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="input-label">Status *</label>
                        <select name="status" class="form-control">
                            <option value="pending">Pending (needs approval)</option>
                            <option value="approved">Approve now</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3 mb-2 mb-md-0"><label class="input-label">From *</label>
                        <input type="date" name="from_date" id="from_date" class="form-control" value="{{ now()->toDateString() }}" required oninput="syncTo()"></div>
                    <div class="form-group col-md-3 mb-2 mb-md-0"><label class="input-label">To *</label>
                        <input type="date" name="to_date" id="to_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                    <div class="form-group col-md-6 mb-0"><label class="input-label">Reason</label>
                        <input name="reason" class="form-control" maxlength="500" placeholder="e.g. Fever / out of station / family function"></div>
                </div>
                <div class="text-right">
                    <button class="btn btn--primary"><i class="tio-save"></i> Submit Leave</button>
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
function syncTo() {
    var f = document.getElementById('from_date'), t = document.getElementById('to_date');
    if (t.value < f.value) t.value = f.value;
    t.min = f.value;
}
syncTo();
</script>
@endpush
