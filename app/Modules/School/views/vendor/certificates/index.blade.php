@extends('layouts.vendor.app')
@section('title', 'Certificates')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-receipt-outlined mr-1"></i> Certificates</h1>
        <div>
            @if(hasPermission('certificates','edit'))<a href="{{ route('vendor.school.certificates.settings') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-settings"></i> Templates</a>@endif
            @if(hasPermission("certificates","add"))<a href="{{ route('vendor.school.certificates.create') }}" class="btn btn-sm btn--primary"><i class="tio-add"></i> Issue Certificate</a>@endif
        </div>
    </div>

    @if(hasPermission('certificates','view'))
    <div class="card mb-3"><div class="card-body py-3">
        <form method="GET" class="row align-items-end">
            <div class="col-md-4 mb-2 mb-md-0">
                <label class="input-label mb-1">Type</label>
                <select name="type" class="form-control form-control-sm js-select2-custom" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    @foreach($types as $k => $label)
                        <option value="{{ $k }}" @selected($type === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5 mb-2 mb-md-0">
                <label class="input-label mb-1">Search</label>
                <input name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Student / admission no / serial">
            </div>
            <div class="col-md-3">
                <button class="btn btn-sm btn--primary"><i class="tio-search"></i> Filter</button>
                @if($type || $search)<a href="{{ route('vendor.school.certificates.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>@endif
            </div>
        </form>
    </div></div>
    @endif

    @if(hasPermission("certificates","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
            <thead class="thead-light"><tr>
                <th>Serial</th><th>Type</th><th>Student</th><th>Admission No</th><th>Issued On</th><th class="text-right">Action</th>
            </tr></thead>
            <tbody>
            @forelse($certificates as $c)
                <tr>
                    <td class="font-weight-bold">{{ $c->serial_no }}</td>
                    <td>{{ $c->typeLabel() }}</td>
                    <td>{{ $c->student?->name ?? '—' }}</td>
                    <td>{{ $c->student?->admission_no ?? '—' }}</td>
                    <td>{{ \Carbon\Carbon::parse($c->issue_date)->format('d/m/Y') }}</td>
                    <td class="text-right">
                        <div class="dropdown sch-actions">
                            <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="{{ route('vendor.school.certificates.show', $c->id) }}"><i class="tio-visible"></i> View</a>
                                <a class="dropdown-item" href="{{ route('vendor.school.certificates.pdf', $c->id) }}" target="_blank"><i class="tio-download"></i> Download PDF</a>
                                <div class="dropdown-divider"></div>
                                @if(hasPermission("certificates","delete"))<a class="dropdown-item text-danger" href="{{ route('vendor.school.certificates.delete', $c->id) }}" onclick="return confirm('Delete this certificate?')"><i class="tio-delete"></i> Delete</a>@endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty<tr><td colspan="6" class="text-center text-muted py-5">No certificates issued yet.</td></tr>@endforelse
            </tbody>
        </table>
    </div></div></div>@endif
    @if(hasPermission("certificates","view") && count($certificates))<div class="mt-3 px-2">{!! $certificates->links() !!}</div>@endif
</div>
@endsection
