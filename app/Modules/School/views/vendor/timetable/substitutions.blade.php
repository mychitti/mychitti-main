@extends('layouts.vendor.app')
@section('title', 'Substitutions')

@php use App\Modules\School\Controllers\Vendor\TimetableController as TT; @endphp

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-replace mr-1"></i> Period Substitutions</h1>
        <a href="{{ route('vendor.school.timetable.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    <div class="card mb-3"><div class="card-body py-3">
        <form method="GET" class="form-row align-items-end">
            <div class="col-md-4"><label class="input-label mb-1">Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" onchange="this.form.submit()">
            </div>
            <div class="col-md-6 pt-3">
                <span class="text-muted">Showing scheduled periods for <b>{{ $days[$dow] ?? \Carbon\Carbon::parse($date)->format('l') }}</b>, {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</span>
            </div>
        </form>
    </div></div>

    @if(!array_key_exists($dow, $days))
        <div class="alert alert-info">No timetable runs on {{ \Carbon\Carbon::parse($date)->format('l') }} (off day).</div>
    @else
        <div class="card"><div class="card-body p-0"><div class="table-responsive">
            <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                <thead class="thead-light"><tr>
                    <th>Period</th><th>Class</th><th>Subject</th><th>Regular Teacher</th><th style="min-width:330px;">Substitute</th>
                </tr></thead>
                <tbody>
                @forelse($entries as $e)
                    @php $sub = $subs->get($e->id); @endphp
                    <tr @if($sub) style="background:#fff7ed;" @endif>
                        <td>{{ $e->period?->name ?? '—' }}</td>
                        <td>{{ $e->schoolClass?->name }} - {{ $e->section?->name }}</td>
                        <td class="font-weight-bold">{{ $e->subject?->name ?? '—' }}</td>
                        <td>{{ TT::teacherName($e->teacher) }}</td>
                        <td>
                            <form action="{{ route('vendor.school.timetable.substitutions.save') }}" method="POST" class="form-inline">
                                @csrf
                                <input type="hidden" name="sub_date" value="{{ $date }}">
                                <input type="hidden" name="timetable_entry_id" value="{{ $e->id }}">
                                <select name="substitute_teacher_emp_id" class="form-control form-control-sm mr-1 js-ajax-teacher"
                                        data-url="{{ route('vendor.school.lookup.teachers') }}" data-width="220px" required>
                                    <option value="">— Select —</option>
                                    @if(optional($sub)->substitute_teacher_emp_id)
                                        @php $st = $teachers->firstWhere('id', $sub->substitute_teacher_emp_id); @endphp
                                        @if($st)<option value="{{ $st->id }}" selected>{{ trim(($st->f_name ?? '').' '.($st->l_name ?? '')) }}</option>@endif
                                    @endif
                                </select>
                                <input name="reason" class="form-control form-control-sm mr-1" style="width:110px;" placeholder="Reason" value="{{ optional($sub)->reason }}">
                                <button class="btn btn-sm btn--primary"><i class="tio-save"></i></button>
                                @if($sub)
                                    @if(hasPermission("timetable","delete"))<a href="{{ route('vendor.school.timetable.substitutions.delete', $sub->id) }}" class="btn btn-sm btn-outline-danger ml-1" onclick="return confirm('Remove substitution?')"><i class="tio-delete"></i></a>@endif
                                @endif
                            </form>
                        </td>
                    </tr>
                @empty<tr><td colspan="5" class="text-center text-muted py-5">No periods scheduled for this day.</td></tr>@endforelse
                </tbody>
            </table>
        </div></div></div>
    @endif
</div>
@endsection
