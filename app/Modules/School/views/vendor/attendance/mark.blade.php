@extends('layouts.vendor.app')
@section('title', 'Mark Attendance')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-checkmark-circle mr-1"></i> Student Attendance</h1>
        <a href="{{ route('vendor.school.student-attendance.report') }}" class="btn btn-sm btn-outline-info"><i class="tio-chart-bar-1"></i> Reports</a>
    </div>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="form-row align-items-end">
            <div class="col-md-3"><label class="input-label">Class</label>
                <select name="class_id" class="form-control js-select2-custom" required>
                    <option value="">Select</option>
                    @foreach($classes as $c)<option value="{{ $c->id }}" {{ (string)$classId===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
                </select></div>
            <div class="col-md-3"><label class="input-label">Section</label>
                <select name="section_id" class="form-control js-select2-custom">
                    <option value="">All</option>
                    @foreach($sections as $s)<option value="{{ $s->id }}" {{ (string)$sectionId===(string)$s->id?'selected':'' }}>{{ $s->schoolClass?->name }} - {{ $s->name }}</option>@endforeach
                </select></div>
            <div class="col-md-3"><label class="input-label">Date</label>
                <input type="date" name="date" class="form-control" value="{{ $date }}"></div>
            <div class="col-md-2"><button class="btn btn--primary">Load</button></div>
        </form>
    </div></div>

    @if($classId)
    <form action="{{ route('vendor.school.student-attendance.store') }}" method="POST">
        @csrf
        <input type="hidden" name="class_id" value="{{ $classId }}">
        <input type="hidden" name="section_id" value="{{ $sectionId }}">
        <input type="hidden" name="date" value="{{ $date }}">
        <div class="card"><div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Roster — {{ \Carbon\Carbon::parse($date)->format('d M Y') }} ({{ count($roster) }})</h6>
            <div>
                <button type="button" class="btn btn-xs btn-outline-success" onclick="setAll('present')">All Present</button>
                <button type="button" class="btn btn-xs btn-outline-danger" onclick="setAll('absent')">All Absent</button>
            </div>
        </div>
        <div class="card-body p-0"><div class="table-responsive">
            <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                <thead class="thead-light"><tr><th style="width:70px;">Roll</th><th>Admission</th><th>Student</th><th style="width:220px;">Status</th></tr></thead>
                <tbody>
                @forelse($roster as $e)
                    @php $cur = $existing->get($e->student_id)?->status ?? 'present'; @endphp
                    <tr>
                        <td>{{ $e->roll_no ?? '—' }}</td>
                        <td>{{ $e->student->admission_no }}</td>
                        <td class="font-weight-bold">{{ $e->student->name }}</td>
                        <td>
                            <select name="status[{{ $e->student_id }}]" class="form-control form-control-sm att-status">
                                @foreach(\App\Models\StudentAttendance::STATUSES as $k=>$v)
                                    <option value="{{ $k }}" {{ $cur===$k?'selected':'' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                @empty<tr><td colspan="4" class="text-center text-muted py-5">No students enrolled for this class/section.</td></tr>@endforelse
                </tbody>
            </table>
        </div></div>
        @if(count($roster))
        <div class="card-footer text-right">@if(hasPermission("student_attendance","add"))<button class="btn btn--primary"><i class="tio-save"></i> Save Attendance</button>@endif</div>
        @endif
        </div>
    </form>
    @endif
</div>
@endsection

@push('script_2')
<script>
function setAll(v){ document.querySelectorAll('.att-status').forEach(s => s.value = v); }
</script>
@endpush
