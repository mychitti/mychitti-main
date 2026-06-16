@extends('layouts.vendor.app')
@section('title', 'Attendance Report')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-chart-bar-1 mr-1"></i> Attendance Report</h1>
        <a href="{{ route('vendor.school.student-attendance.mark') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Mark Attendance</a>
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
            <div class="col-md-3"><label class="input-label">Month</label>
                <input type="month" name="month" class="form-control" value="{{ $month }}"></div>
            <div class="col-md-2"><button class="btn btn--primary">View</button></div>
        </form>
    </div></div>

    @if($classId)
    <div class="card"><div class="card-header py-2">
        <h6 class="mb-0">{{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }}
            <span class="badge badge-soft-secondary ml-2">Working days: {{ $workingDays }}</span></h6>
    </div>
    <div class="card-body p-0"><div class="table-responsive">
        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
            <thead class="thead-light"><tr>
                <th>Roll</th><th>Student</th><th>Present</th><th>Absent</th><th>Late</th><th>Half</th><th>Leave</th><th class="text-right">%</th>
            </tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r['roll'] ?? '—' }}</td>
                    <td class="font-weight-bold">{{ $r['name'] }}</td>
                    <td>{{ $r['present'] }}</td>
                    <td>{{ $r['absent'] }}</td>
                    <td>{{ $r['late'] }}</td>
                    <td>{{ $r['half'] }}</td>
                    <td>{{ $r['leave'] }}</td>
                    <td class="text-right">
                        <span class="badge {{ $r['pct'] < 75 ? 'badge-soft-danger' : 'badge-soft-success' }}">{{ $r['pct'] }}%</span>
                    </td>
                </tr>
            @empty<tr><td colspan="8" class="text-center text-muted py-5">No data for this selection.</td></tr>@endforelse
            </tbody>
        </table>
    </div></div></div>
    <p class="text-muted mt-2" style="font-size:12px;">Students below 75% are highlighted in red.</p>
    @endif
</div>
@endsection
