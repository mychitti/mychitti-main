@extends('layouts.vendor.app')
@section('title', 'Exams')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-album mr-1"></i> Exams</h1>
        @if(hasPermission("exams","add"))<a href="{{ route('vendor.school.exams.create') }}" class="btn btn-sm btn--primary"><i class="tio-add"></i> New Exam</a>@endif
    </div>

    @if(hasPermission("exams","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
            <thead class="thead-light"><tr><th>Exam</th><th>Type</th><th>Class</th><th>Subjects</th><th class="text-right">Action</th></tr></thead>
            <tbody>
            @forelse($exams as $e)
                <tr>
                    <td class="font-weight-bold"><a href="{{ route('vendor.school.exams.show', $e->id) }}">{{ $e->name }}</a></td>
                    <td>{{ $e->exam_type }}</td>
                    <td>{{ $e->schoolClass?->name }}</td>
                    <td>{{ $e->subjects_count }}</td>
                    <td class="text-right">
                        <div class="dropdown sch-actions">
                            <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="{{ route('vendor.school.exams.show', $e->id) }}"><i class="tio-settings"></i> Setup</a>
                                <a class="dropdown-item" href="{{ route('vendor.school.exams.report-cards', $e->id) }}"><i class="tio-poll"></i> Results</a>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty<tr><td colspan="5" class="text-center text-muted py-5">No exams yet.</td></tr>@endforelse
            </tbody>
        </table>
    </div></div></div>@endif
    @if(hasPermission("exams","view") && count($exams))<div class="mt-3 px-2">{!! $exams->links() !!}</div>@endif
</div>
@endsection
