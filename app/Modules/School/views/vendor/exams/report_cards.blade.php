@extends('layouts.vendor.app')
@section('title', 'Results')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-poll mr-1"></i> {{ $exam->name }} — Results</h1>
        <div class="d-flex">
            @if(count($cards))
                <a href="{{ route('vendor.school.exams.notify-results', [$exam->id, 'section_id' => $sectionId]) }}" class="btn btn-sm btn-info mr-2"><i class="tio-send"></i> Notify Parents</a>
            @endif 
            <a href="{{ route('vendor.school.exams.show', $exam->id) }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="form-row align-items-end">
            <div class="col-md-4"><label class="input-label">Section</label>
                <select name="section_id" class="form-control js-select2-custom" onchange="this.form.submit()">
                    <option value="">Select section</option>
                    @foreach($sections as $s)<option value="{{ $s->id }}" {{ (string)$sectionId===(string)$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach
                </select></div>
        </form>
    </div></div>

    @if(count($cards))
    <div class="card"><div class="card-body p-0"><div class="table-responsive">
        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
            <thead class="thead-light"><tr><th>Rank</th><th>Roll</th><th>Student</th><th>Obtained</th><th>%</th><th>Grade</th><th>Result</th><th class="text-right">Report Card</th></tr></thead>
            <tbody>
            @foreach($cards as $c)
                <tr>
                    <td>{{ $c['rank'] }}</td>
                    <td>{{ $c['roll'] ?? '—' }}</td>
                    <td class="font-weight-bold">{{ $c['name'] }}</td>
                    <td>{{ rtrim(rtrim(number_format($c['obtained'],2),'0'),'.') }} / {{ rtrim(rtrim(number_format($c['max'],2),'0'),'.') }}</td>
                    <td>{{ $c['percentage'] }}%</td>
                    <td><span class="badge badge-soft-info">{{ $c['grade'] }}</span></td>
                    <td><span class="badge {{ $c['result']==='Pass'?'badge-soft-success':'badge-soft-danger' }}">{{ $c['result'] }}</span></td>
                    <td class="text-right"><a href="{{ route('vendor.school.exams.report-card', [$exam->id, $c['student_id']]) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="tio-print"></i> Card</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div></div></div>
    @else
        <div class="alert alert-soft-info">Select a section to view results.</div>
    @endif
</div>
@endsection
