@extends('layouts.vendor.app')
@section('title', 'Teacher Timetable')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-user mr-1"></i> Teacher Timetable</h1>
        <a href="{{ route('vendor.school.timetable.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    <div class="card mb-3"><div class="card-body py-3">
        <form method="GET" class="form-row align-items-end">
            <div class="col-md-5"><label class="input-label mb-1">Teacher</label>
                <select name="teacher_id" class="form-control form-control-sm js-ajax-teacher"
                        data-url="{{ route('vendor.school.lookup.teachers') }}" required onchange="this.form.submit()">
                    <option value="">Select teacher</option>
                    @if($teacherId)
                        @php $st = $teachers->firstWhere('id', $teacherId); @endphp
                        @if($st)<option value="{{ $st->id }}" selected>{{ trim(($st->f_name ?? '').' '.($st->l_name ?? '')) }}</option>@endif
                    @endif
                </select>
            </div>
        </form>
    </div></div>

    @if($teacherId && count($periods))
        <div class="card"><div class="card-body p-0"><div class="table-responsive">
            <table class="table table-bordered table-align-middle mb-0" style="font-size:12px;">
                <thead class="thead-light">
                    <tr><th style="min-width:120px;">Period</th>@foreach($days as $dn => $day)<th class="text-center" style="min-width:140px;">{{ $day }}</th>@endforeach</tr>
                </thead>
                <tbody>
                @foreach($periods as $p)
                    <tr class="{{ $p->is_break ? 'bg-light' : '' }}">
                        <td class="font-weight-bold">{{ $p->name }}
                            @if($p->start_time && $p->end_time)<br><small class="text-muted">{{ \Carbon\Carbon::parse($p->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($p->end_time)->format('g:i A') }}</small>@endif
                        </td>
                        @foreach($days as $dn => $day)
                            @if($p->is_break)
                                <td class="text-center text-muted">—</td>
                            @else
                                @php $cell = $grid[$dn][$p->id] ?? null; @endphp
                                <td class="text-center">
                                    @if($cell)
                                        <div class="font-weight-bold">{{ $cell->subject?->name ?? '—' }}</div>
                                        <small class="text-muted">{{ $cell->schoolClass?->name }} - {{ $cell->section?->name }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div></div></div>
    @elseif($teacherId)
        <div class="alert alert-warning">No periods defined. <a href="{{ route('vendor.school.timetable.periods') }}">Set up periods</a> first.</div>
    @else
        <div class="card"><div class="card-body text-center text-muted py-5">Select a teacher to view their weekly schedule.</div></div>
    @endif
</div>
@endsection
