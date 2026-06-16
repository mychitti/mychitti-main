@extends('layouts.vendor.app')
@section('title', $exam->name)

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">{{ $exam->name }}
            <span class="badge badge-soft-info">{{ $exam->exam_type }}</span>
            <span class="badge badge-soft-secondary">{{ $exam->schoolClass?->name }}</span></h1>
        <div class="d-flex" style="gap:8px;">
            <a href="{{ route('vendor.school.exams.report-cards', $exam->id) }}" class="btn btn-sm btn-outline-info"><i class="tio-poll"></i> Results</a>
            <a href="{{ route('vendor.school.exams.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <form action="{{ route('vendor.school.exams.subject.store', $exam->id) }}" method="POST" class="form-row align-items-end">
            @csrf
            <div class="col-md-4"><label class="input-label">Subject *</label>
                <select name="subject_id" class="form-control js-select2-custom" required>
                    <option value="">Select subject</option>
                    @foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select></div>
            <div class="col-md-3"><label class="input-label">Max Marks *</label>
                <input type="number" step="0.01" min="1" name="max_marks" class="form-control" value="100" required></div>
            <div class="col-md-3"><label class="input-label">Pass Marks *</label>
                <input type="number" step="0.01" min="0" name="pass_marks" class="form-control" value="33" required></div>
            <div class="col-md-2">@if(hasPermission("exams","edit"))<button class="btn btn--primary">Add</button>@endif</div>
        </form>
    </div></div>

    <div class="card"><div class="card-header py-2"><h6 class="mb-0">Subjects & Mark Entry</h6></div>
    <div class="card-body p-0"><div class="table-responsive">
        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
            <thead class="thead-light"><tr><th>Subject</th><th>Max</th><th>Pass</th><th class="text-right">Action</th></tr></thead>
            <tbody>
            @forelse($exam->subjects as $es)
                <tr>
                    <td class="font-weight-bold">{{ $es->subject?->name }}</td>
                    <td>{{ rtrim(rtrim(number_format($es->max_marks,2),'0'),'.') }}</td>
                    <td>{{ rtrim(rtrim(number_format($es->pass_marks,2),'0'),'.') }}</td>
                    <td class="text-right">
                        <div class="dropdown sch-actions">
                            <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                            <div class="dropdown-menu dropdown-menu-right">
                                @if(hasPermission("exams","enter_marks"))<a class="dropdown-item text-success" href="{{ route('vendor.school.exams.marks', [$exam->id, 'exam_subject_id'=>$es->id]) }}"><i class="tio-edit"></i> Enter Marks</a>@endif
                                <div class="dropdown-divider"></div>
                                @if(hasPermission("exams","edit"))<a class="dropdown-item text-danger" href="{{ route('vendor.school.exams.subject.delete', [$exam->id, $es->id]) }}" onclick="return confirm('Remove subject and its marks?')"><i class="tio-delete"></i> Remove</a>@endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty<tr><td colspan="4" class="text-center text-muted py-4">Add subjects with max/pass marks.</td></tr>@endforelse
            </tbody>
        </table>
    </div></div></div>
</div>
@endsection
