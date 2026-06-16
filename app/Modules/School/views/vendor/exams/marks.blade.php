@extends('layouts.vendor.app')
@section('title', 'Enter Marks')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-edit mr-1"></i> {{ $exam->name }} — Marks</h1>
        <a href="{{ route('vendor.school.exams.show', $exam->id) }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="form-row align-items-end">
            <div class="col-md-4"><label class="input-label">Subject</label>
                <select name="exam_subject_id" class="form-control js-select2-custom" required onchange="this.form.submit()">
                    <option value="">Select subject</option>
                    @foreach($exam->subjects as $es)<option value="{{ $es->id }}" {{ (string)$examSubjectId===(string)$es->id?'selected':'' }}>{{ $es->subject?->name }} (max {{ rtrim(rtrim(number_format($es->max_marks,2),'0'),'.') }})</option>@endforeach
                </select></div>
            <div class="col-md-4"><label class="input-label">Section</label>
                <select name="section_id" class="form-control js-select2-custom" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($sections as $s)<option value="{{ $s->id }}" {{ (string)$sectionId===(string)$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach
                </select></div>
        </form>
    </div></div>

    @if($examSubject)
    <form action="{{ route('vendor.school.exams.marks.store', $exam->id) }}" method="POST">
        @csrf
        <input type="hidden" name="exam_subject_id" value="{{ $examSubject->id }}">
        <input type="hidden" name="section_id" value="{{ $sectionId }}">
        <div class="card"><div class="card-header py-2"><h6 class="mb-0">{{ $examSubject->subject?->name }} — Max {{ rtrim(rtrim(number_format($examSubject->max_marks,2),'0'),'.') }}, Pass {{ rtrim(rtrim(number_format($examSubject->pass_marks,2),'0'),'.') }}</h6></div>
        <div class="card-body p-0"><div class="table-responsive">
            <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                <thead class="thead-light"><tr><th style="width:70px;">Roll</th><th>Student</th><th style="width:160px;">Marks</th><th style="width:100px;">Absent</th></tr></thead>
                <tbody>
                @forelse($roster as $e)
                    @php $m = $existing->get($e->student_id); @endphp
                    <tr>
                        <td>{{ $e->roll_no ?? '—' }}</td>
                        <td class="font-weight-bold">{{ $e->student->name }}</td>
                        <td><input type="number" step="0.01" min="0" max="{{ $examSubject->max_marks }}" name="marks[{{ $e->student_id }}]" class="form-control form-control-sm" value="{{ $m && !$m->is_absent ? $m->marks_obtained : '' }}"></td>
                        <td><input type="checkbox" name="absent[]" value="{{ $e->student_id }}" {{ $m && $m->is_absent ? 'checked' : '' }}></td>
                    </tr>
                @empty<tr><td colspan="4" class="text-center text-muted py-5">No students for this class/section.</td></tr>@endforelse
                </tbody>
            </table>
        </div></div>
        @if(count($roster))<div class="card-footer text-right"><button class="btn btn--primary"><i class="tio-save"></i> Save Marks</button></div>@endif
        </div>
    </form>
    @endif
</div>
@endsection
