@extends('layouts.vendor.app')
@section('title', 'Academic Setup')

@section('content')
@php $canAdd = hasPermission('academic_setup','add'); $canDel = hasPermission('academic_setup','delete'); $canView = hasPermission('academic_setup','view'); @endphp
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-book" style="font-size:22px;"></i></span>
            Academic Setup
        </h1>
    </div>

    <ul class="nav nav-tabs mb-3" id="acadTabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-sessions">Sessions</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-classes">Classes</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-sections">Sections</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-subjects">Subjects</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-mapping">Subject ↔ Teacher</a></li>
    </ul>

    <div class="tab-content">
        {{-- ===== SESSIONS ===== --}}
        <div class="tab-pane fade show active" id="tab-sessions">
            @if($canAdd)
            <div class="card mb-3"><div class="card-body">
                <form action="{{ route('vendor.school.academic.session.store') }}" method="POST" class="form-row align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="input-label">Session Name *</label>
                        <input name="name" class="form-control" placeholder="2025-26" required></div>
                    <div class="col-md-2"><label class="input-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control"></div>
                    <div class="col-md-2"><label class="input-label">End Date</label>
                        <input type="date" name="end_date" class="form-control"></div>
                    <div class="col-md-2"><label class="input-label d-block">Current</label>
                        <label class="toggle-switch"><input type="checkbox" name="is_current" value="1" class="toggle-switch-input"><span class="toggle-switch-label"><span class="toggle-switch-indicator"></span></span></label></div>
                    <div class="col-md-2"><button class="btn btn--primary">Add Session</button></div>
                </form>
            </div></div>
            @endif
            @if(hasPermission("academic_setup","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light"><tr><th>Session</th><th>Period</th><th>Current</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                    @forelse($sessions as $s)
                        <tr>
                            <td class="font-weight-bold">{{ $s->name }}</td>
                            <td>{{ $s->start_date?->format('d M Y') }} – {{ $s->end_date?->format('d M Y') }}</td>
                            <td>@if($s->is_current)<span class="badge badge-soft-success">Current</span>@endif</td>
                            <td class="text-right">
                                <div class="dropdown sch-actions">
                                    <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        @if($canDel)<a class="dropdown-item text-danger" href="{{ route('vendor.school.academic.session.delete', $s->id) }}" onclick="return confirm('Delete this session?')"><i class="tio-delete"></i> Delete</a>@endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty<tr><td colspan="4" class="text-center text-muted py-4">No sessions yet.</td></tr>@endforelse
                    </tbody>
                </table>
            </div></div></div>@endif
        </div>

        {{-- ===== CLASSES ===== --}}
        <div class="tab-pane fade" id="tab-classes">
            @if($canAdd)
            <div class="card mb-3"><div class="card-body">
                <form action="{{ route('vendor.school.academic.class.store') }}" method="POST" class="form-row align-items-end">
                    @csrf
                    <div class="col-md-4"><label class="input-label">Class Name *</label>
                        <input name="name" class="form-control" placeholder="LKG / Class 1 / Class 10" required></div>
                    <div class="col-md-3"><label class="input-label">Order</label>
                        <input type="number" name="numeric_order" class="form-control" placeholder="0" min="0"></div>
                    <div class="col-md-2"><button class="btn btn--primary">Add Class</button></div>
                </form>
            </div></div>
            @endif
            @if(hasPermission("academic_setup","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light"><tr><th>Order</th><th>Class</th><th>Sections</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                    @forelse($classes as $c)
                        <tr>
                            <td>{{ $c->numeric_order }}</td>
                            <td class="font-weight-bold">{{ $c->name }}</td>
                            <td>{{ $sections->where('school_class_id', $c->id)->pluck('name')->implode(', ') ?: '—' }}</td>
                            <td class="text-right">
                                <div class="dropdown sch-actions">
                                    <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        @if($canDel)<a class="dropdown-item text-danger" href="{{ route('vendor.school.academic.class.delete', $c->id) }}" onclick="return confirm('Delete this class?')"><i class="tio-delete"></i> Delete</a>@endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty<tr><td colspan="4" class="text-center text-muted py-4">No classes yet.</td></tr>@endforelse
                    </tbody>
                </table>
            </div></div></div>@endif
        </div>

        {{-- ===== SECTIONS ===== --}}
        <div class="tab-pane fade" id="tab-sections">
            @if($canAdd)
            <div class="card mb-3"><div class="card-body">
                <form action="{{ route('vendor.school.academic.section.store') }}" method="POST" class="form-row align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="input-label">Class *</label>
                        <select name="school_class_id" class="form-control js-select2-custom" required>
                            <option value="">Select class</option>
                            @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                        </select></div>
                    <div class="col-md-2"><label class="input-label">Section *</label>
                        <input name="name" class="form-control" placeholder="A" required></div>
                    <div class="col-md-2"><label class="input-label">Capacity</label>
                        <input type="number" name="capacity" class="form-control" min="0"></div>
                    <div class="col-md-3"><label class="input-label">Class Teacher</label>
                        <select name="class_teacher_emp_id" class="form-control js-ajax-teacher"
                                data-url="{{ route('vendor.school.lookup.teachers') }}" data-placeholder="Search teacher…">
                            <option value="">—</option>
                        </select></div>
                    <div class="col-md-2"><button class="btn btn--primary">Add Section</button></div>
                </form>
            </div></div>
            @endif
            @if(hasPermission("academic_setup","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light"><tr><th>Class</th><th>Section</th><th>Capacity</th><th>Class Teacher</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                    @forelse($sections as $sec)
                        <tr>
                            <td>{{ $sec->schoolClass?->name }}</td>
                            <td class="font-weight-bold">{{ $sec->name }}</td>
                            <td>{{ $sec->capacity ?? '—' }}</td>
                            <td>{{ $sec->classTeacher?->full_name ?? '—' }}</td>
                            <td class="text-right">
                                <div class="dropdown sch-actions">
                                    <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        @if($canDel)<a class="dropdown-item text-danger" href="{{ route('vendor.school.academic.section.delete', $sec->id) }}" onclick="return confirm('Delete this section?')"><i class="tio-delete"></i> Delete</a>@endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty<tr><td colspan="5" class="text-center text-muted py-4">No sections yet.</td></tr>@endforelse
                    </tbody>
                </table>
            </div></div></div>@endif
        </div>

        {{-- ===== SUBJECTS ===== --}}
        <div class="tab-pane fade" id="tab-subjects">
            @if($canAdd)
            <div class="card mb-3"><div class="card-body">
                <form action="{{ route('vendor.school.academic.subject.store') }}" method="POST" class="form-row align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="input-label">Subject Name *</label>
                        <input name="name" class="form-control" placeholder="Mathematics" required></div>
                    <div class="col-md-2"><label class="input-label">Code</label>
                        <input name="code" class="form-control" placeholder="MATH"></div>
                    <div class="col-md-2"><button class="btn btn--primary">Add Subject</button></div>
                </form>
            </div></div>
            @endif
            @if(hasPermission("academic_setup","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light"><tr><th>Code</th><th>Subject</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                    @forelse($subjects as $sub)
                        <tr>
                            <td>{{ $sub->code ?? '—' }}</td>
                            <td class="font-weight-bold">{{ $sub->name }}</td>
                            <td class="text-right">
                                <div class="dropdown sch-actions">
                                    <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        @if($canDel)<a class="dropdown-item text-danger" href="{{ route('vendor.school.academic.subject.delete', $sub->id) }}" onclick="return confirm('Delete this subject?')"><i class="tio-delete"></i> Delete</a>@endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty<tr><td colspan="3" class="text-center text-muted py-4">No subjects yet.</td></tr>@endforelse
                    </tbody>
                </table>
            </div></div></div>@endif
        </div>

        {{-- ===== SUBJECT ↔ TEACHER ===== --}}
        <div class="tab-pane fade" id="tab-mapping">
            @if($canAdd)
            <div class="card mb-3"><div class="card-body">
                <form action="{{ route('vendor.school.academic.mapping.store') }}" method="POST" class="form-row align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="input-label">Class *</label>
                        <select name="school_class_id" class="form-control js-select2-custom" required>
                            <option value="">Select class</option>
                            @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                        </select></div>
                    <div class="col-md-2"><label class="input-label">Section</label>
                        <select name="class_section_id" class="form-control js-select2-custom">
                            <option value="">All sections</option>
                            @foreach($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->schoolClass?->name }} - {{ $sec->name }}</option>@endforeach
                        </select></div>
                    <div class="col-md-3"><label class="input-label">Subject *</label>
                        <select name="subject_id" class="form-control js-select2-custom" required>
                            <option value="">Select subject</option>
                            @foreach($subjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach
                        </select></div>
                    <div class="col-md-2"><label class="input-label">Teacher *</label>
                        <select name="teacher_emp_id" class="form-control js-ajax-teacher"
                                data-url="{{ route('vendor.school.lookup.teachers') }}" data-placeholder="Search teacher…" required>
                            <option value="">Select</option>
                        </select></div>
                    <div class="col-md-2"><button class="btn btn--primary">Map</button></div>
                </form>
            </div></div>
            @endif
            @if(hasPermission("academic_setup","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light"><tr><th>Class</th><th>Section</th><th>Subject</th><th>Teacher</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                    @forelse($mappings as $m)
                        <tr>
                            <td>{{ $m->schoolClass?->name }}</td>
                            <td>{{ $m->section?->name ?? 'All' }}</td>
                            <td class="font-weight-bold">{{ $m->subject?->name }}</td>
                            <td>{{ $m->teacher?->full_name ?? '—' }}</td>
                            <td class="text-right">
                                <div class="dropdown sch-actions">
                                    <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        @if($canDel)<a class="dropdown-item text-danger" href="{{ route('vendor.school.academic.mapping.delete', $m->id) }}" onclick="return confirm('Remove this mapping?')"><i class="tio-delete"></i> Remove</a>@endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty<tr><td colspan="5" class="text-center text-muted py-4">No mappings yet.</td></tr>@endforelse
                    </tbody>
                </table>
            </div></div></div>@endif
        </div>
    </div>
</div>
@endsection
