@extends('layouts.vendor.app')
@section('title', 'Admissions')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-user-add mr-1"></i> Admission Enquiries</h1>
        @if(hasPermission('admissions','add'))<a href="{{ route('vendor.school.admissions.create') }}" class="btn btn-sm btn--primary"><i class="tio-add"></i> New Enquiry</a>@endif
    </div>

    {{-- Pipeline tiles --}}
    <div class="row">
        @php
            $tiles = [
                ['' , 'All', 'slate', $total],
                ['new', 'New', 'sky', $counts['new'] ?? 0],
                ['contacted', 'Contacted', 'amber', $counts['contacted'] ?? 0],
                ['visited', 'Visited', 'indigo', $counts['visited'] ?? 0],
                ['admitted', 'Admitted', 'green', $counts['admitted'] ?? 0],
                ['rejected', 'Rejected', 'rose', $counts['rejected'] ?? 0],
            ];
        @endphp
        @foreach($tiles as [$k, $label, $color, $c])
            <div class="col-6 col-md-4 col-xl-2 mb-3">
                <a href="{{ route('vendor.school.admissions.index', array_filter(['status' => $k, 'search' => $search])) }}"
                   class="sch-stat sch-stat--{{ $color }} d-block" style="text-decoration:none; {{ (string)$status===(string)$k ? 'outline:3px solid rgba(79,70,229,.35); outline-offset:2px;' : '' }}">
                    <div class="sch-stat-label">{{ $label }}</div>
                    <div class="sch-stat-value">{{ $c }}</div>
                </a>
            </div>
        @endforeach
    </div>

    @if(hasPermission("admissions","view"))<div class="card mb-3"><div class="card-body py-3">
        <form method="GET" class="form-row align-items-end">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="col-md-6 mb-2 mb-md-0"><label class="input-label mb-1">Search</label>
                <input name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Name / enquiry no / phone">
            </div>
            <div class="col-md-3">
                <button class="btn btn-sm btn--primary"><i class="tio-search"></i> Filter</button>
                @if($status || $search)<a href="{{ route('vendor.school.admissions.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>@endif
            </div>
        </form>
    </div></div>@endif

    @if(hasPermission("admissions","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
            <thead class="thead-light"><tr>
                <th>Enquiry No</th><th>Student</th><th>Class</th><th>Guardian</th><th>Source</th><th>Status</th><th>Follow-up</th><th class="text-right">Action</th>
            </tr></thead>
            <tbody>
            @forelse($enquiries as $e)
                <tr>
                    <td class="font-weight-bold"><a href="{{ route('vendor.school.admissions.show', $e->id) }}">{{ $e->enquiry_no }}</a></td>
                    <td>{{ $e->student_name }}</td>
                    <td>{{ $e->seekingClass?->name ?? '—' }}</td>
                    <td>{{ $e->guardian_name ?? '—' }}<br><small class="text-muted">{{ $e->guardian_phone ?? $e->phone }}</small></td>
                    <td>{{ $e->source ?? '—' }}</td>
                    <td><span class="badge {{ $e->statusBadge() }}">{{ $e->statusLabel() }}</span></td>
                    <td>{{ $e->follow_up_date ? \Carbon\Carbon::parse($e->follow_up_date)->format('d/m/Y') : '—' }}</td>
                    <td class="text-right">
                        <div class="dropdown sch-actions">
                            <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="{{ route('vendor.school.admissions.show', $e->id) }}"><i class="tio-visible"></i> View</a>
                                @if($e->status !== 'admitted')
                                    @if(hasPermission('admissions','add'))<a class="dropdown-item text-success" href="{{ route('vendor.school.admissions.convert', $e->id) }}" onclick="return confirm('Convert this enquiry into a student admission?')"><i class="tio-user-add"></i> Admit</a>@endif
                                @else
                                    <a class="dropdown-item text-success" href="{{ route('vendor.school.students.show', $e->converted_student_id) }}"><i class="tio-user"></i> View Student</a>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty<tr><td colspan="8" class="text-center text-muted py-5">No enquiries yet.</td></tr>@endforelse
            </tbody>
        </table>
    </div></div></div>@endif
    @if(hasPermission("admissions","view") && count($enquiries))<div class="mt-3 px-2">{!! $enquiries->links() !!}</div>@endif
</div>
@endsection
