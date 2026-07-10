@extends('layouts.vendor.app')
@section('title', 'Students')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-user" style="font-size:22px;"></i></span>
            Students <span class="badge badge-soft-secondary">{{ $students->total() }}</span>
        </h1>
        <div class="d-flex" style="gap:8px;">
            @if(hasPermission('school_settings','view'))<a href="{{ route('vendor.school.settings.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-settings"></i> Settings</a>@endif
            @if(hasPermission('students','import'))<a href="{{ route('vendor.school.students.import') }}" class="btn btn-sm btn-outline-primary"><i class="tio-upload"></i> Import</a>@endif
            @if(hasPermission('students','add'))<a href="{{ route('vendor.school.students.create') }}" class="btn btn-sm btn--primary"><i class="tio-add"></i> New Admission</a>@endif
        </div>
    </div> 

    @if(hasPermission("students","view"))<div class="card mb-3"><div class="card-body">
        <form method="GET" class="form-row align-items-end">
            <div class="col-md-3"><label class="input-label">Class</label>
                <select name="class_id" class="form-control js-select2-custom">
                    <option value="">All</option>
                    @foreach($classes as $c)<option value="{{ $c->id }}" {{ (string)$classId===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
                </select></div>
            <div class="col-md-3"><label class="input-label">Section</label>
                <select name="section_id" class="form-control js-select2-custom">
                    <option value="">All</option>
                    @foreach($sections as $s)<option value="{{ $s->id }}" {{ (string)$sectionId===(string)$s->id?'selected':'' }}>{{ $s->schoolClass?->name }} - {{ $s->name }}</option>@endforeach
                </select></div>
            <div class="col-md-4"><label class="input-label">Search</label>
                <input name="search" value="{{ $search }}" class="form-control" placeholder="Name / Admission No / Phone"></div>
            <div class="col-md-2"><button class="btn btn--primary">Filter</button></div>
        </form>
    </div></div>@endif

    @if(hasPermission("students","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
            <thead class="thead-light"><tr>
                <th>Admission No</th><th>Name</th><th>Class / Section</th><th>Roll</th><th>Guardian</th><th class="text-right">Action</th>
            </tr></thead>
            <tbody>
            @forelse($students as $st)
                <tr>
                    <td class="font-weight-bold">{{ $st->admission_no }}</td>
                    <td>
                        @if($st->photo)<img src="{{ asset('storage/app/public/school/students/'.$st->photo) }}" class="avatar avatar-xs avatar-circle mr-2">@endif
                        <a href="{{ route('vendor.school.students.show', $st->id) }}">{{ $st->name }}</a>
                    </td>
                    <td>{{ $st->currentEnrollment?->schoolClass?->name }} {{ $st->currentEnrollment?->section ? '- '.$st->currentEnrollment->section->name : '' }}</td>
                    <td>{{ $st->currentEnrollment?->roll_no ?? '—' }}</td>
                    <td>{{ $st->guardian_name ?? '—' }}<br><small class="text-muted">{{ $st->guardian_phone }}</small></td>
                    <td class="text-right">
                        <div class="dropdown sch-actions">
                            <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="{{ route('vendor.school.students.show', $st->id) }}"><i class="tio-visible"></i> View</a>
                                <a class="dropdown-item" href="{{ route('vendor.school.students.dashboard', $st->id) }}"><i class="tio-chart-bar-1"></i> Dashboard</a>
                                @if(hasPermission('students','edit'))<a class="dropdown-item" href="{{ route('vendor.school.students.edit', $st->id) }}"><i class="tio-edit"></i> Edit</a>@endif
                                @if(hasPermission('students','id_card'))<a class="dropdown-item" href="{{ route('vendor.school.students.id-card', $st->id) }}" target="_blank"><i class="tio-credit-card"></i> ID Card</a>@endif
                                @if(hasPermission('fee_collection','collect'))<a class="dropdown-item" href="{{ route('vendor.school.fees.collect', $st->id) }}"><i class="tio-money"></i> Collect Fee</a>@endif
                                @if(hasPermission('students','delete'))
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="{{ route('vendor.school.students.delete', $st->id) }}" onclick="return confirm('Delete this student?')"><i class="tio-delete"></i> Delete</a>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty<tr><td colspan="6" class="text-center text-muted py-5">No students found.</td></tr>@endforelse
            </tbody>
        </table>
    </div></div></div>@endif

    @if(hasPermission("students","view") && count($students))<div class="mt-3 px-2">{!! $students->links() !!}</div>@endif
</div>
@endsection
