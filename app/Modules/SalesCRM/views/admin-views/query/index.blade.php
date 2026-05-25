@extends('layouts.admin.app')
@section('title', translate('Sales Queries'))

@push('css_or_js')
<style>
    .badge-new        { background: #17a2b8; }
    .badge-in_progress{ background: #fd7e14; }
    .badge-converted  { background: #28a745; }
    .badge-lost       { background: #dc3545; }
    .badge-on_hold    { background: #6c757d; }
    .badge-high       { background: #dc3545; }
    .badge-medium     { background: #fd7e14; }
    .badge-low        { background: #28a745; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">{{ translate('Sales Queries') }}</h1>
                <ol class="breadcrumb breadcrumb-no-gutter">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ translate('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ translate('Sales Queries') }}</li>
                </ol>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.sales-crm.query.create') }}" class="btn btn-primary btn-sm">
                    <i class="tio-add mr-1"></i> {{ translate('Add Sales Query') }}
                </a>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row gx-2 align-items-end" id="filter-form">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ translate('Search name, phone, ref...') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">{{ translate('All Status') }}</option>
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </select>
                </div>
                @if($zones->count())
                <div class="col-md-2">
                    <select name="zone_id" class="form-control form-control-sm">
                        <option value="">{{ translate('All Zones') }}</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" {{ request('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">{{ translate('Filter') }}</button>
                    <a href="{{ route('admin.sales-crm.query.index') }}" class="btn btn-secondary btn-sm">{{ translate('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-2">
            <h5 class="card-title mb-0">{{ translate('Query List') }} <span class="badge badge-soft-dark ml-2">{{ $queries->total() }}</span></h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Ref') }}</th>
                        <th>{{ translate('Contact') }}</th>
                        <th>{{ translate('Zone') }}</th>
                        <th>{{ translate('Source') }}</th>
                        <th>{{ translate('Priority') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Assigned To') }}</th>
                        <th>{{ translate('Date') }}</th>
                        <th>{{ translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($queries as $q)
                    <tr>
                        <td><a href="{{ route('admin.sales-crm.query.show', $q->id) }}" class="font-weight-bold">{{ $q->ref_no }}</a></td>
                        <td>
                            <div class="font-weight-bold">{{ $q->contact_name }}</div>
                            <small class="text-muted">{{ $q->phone }}</small>
                            @if($q->company) <small class="d-block text-muted">{{ $q->company }}</small> @endif
                        </td>
                        <td>{{ $q->zone?->name ?? '—' }}</td>
                        <td>{{ ucfirst($q->source) }}</td>
                        <td><span class="badge badge-{{ $q->priority }} text-white">{{ ucfirst($q->priority) }}</span></td>
                        <td><span class="badge badge-{{ $q->status }} text-white">{{ ucfirst(str_replace('_', ' ', $q->status)) }}</span></td>
                        <td>{{ $q->assignedAdmin ? ($q->assignedAdmin->f_name . ' ' . $q->assignedAdmin->l_name) : '—' }}</td>
                        <td><small>{{ $q->created_at->format('d M Y') }}</small></td>
                        <td>
                            <a href="{{ route('admin.sales-crm.query.show', $q->id) }}" class="btn btn-sm btn-white" title="View"><i class="tio-visible-outlined"></i></a>
                            <a href="{{ route('admin.sales-crm.query.edit', $q->id) }}" class="btn btn-sm btn-white" title="Edit"><i class="tio-edit"></i></a>
                            <form action="{{ route('admin.sales-crm.query.destroy', $q->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this query?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-white text-danger" title="Delete"><i class="tio-delete-outlined"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">{{ translate('No queries found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($queries->hasPages())
        <div class="card-footer">{{ $queries->links() }}</div>
        @endif
    </div>
</div>
@endsection
