@extends('layouts.admin.app')
@section('title', translate('Support Tickets'))

@push('css_or_js')
<style>
    .badge-open       { background: #17a2b8; }
    .badge-in_progress{ background: #fd7e14; }
    .badge-resolved   { background: #28a745; }
    .badge-closed     { background: #6c757d; }
    .badge-on_hold    { background: #adb5bd; color:#333!important; }
    .badge-urgent     { background: #dc3545; }
    .badge-high       { background: #e65c00; }
    .badge-medium     { background: #fd7e14; }
    .badge-low        { background: #28a745; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">{{ translate('Support Tickets') }}</h1>
                <ol class="breadcrumb breadcrumb-no-gutter">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ translate('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ translate('Support Tickets') }}</li>
                </ol>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.sales-crm.ticket.create') }}" class="btn btn-primary btn-sm">
                    <i class="tio-add mr-1"></i>{{ translate('New Ticket') }}
                </a>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row gx-2 align-items-end">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ translate('Search ticket no, subject, contact...') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">{{ translate('All Status') }}</option>
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="priority" class="form-control form-control-sm">
                        <option value="">{{ translate('All Priority') }}</option>
                        @foreach($priorities as $p)
                            <option value="{{ $p }}" {{ request('priority') == $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
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
                    <a href="{{ route('admin.sales-crm.ticket.index') }}" class="btn btn-secondary btn-sm">{{ translate('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-2">
            <h5 class="card-title mb-0">{{ translate('Ticket List') }} <span class="badge badge-soft-dark ml-2">{{ $tickets->total() }}</span></h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Ticket') }}</th>
                        <th>{{ translate('Contact') }}</th>
                        <th>{{ translate('Subject') }}</th>
                        <th>{{ translate('Zone') }}</th>
                        <th>{{ translate('Channel') }}</th>
                        <th>{{ translate('Priority') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Assigned To') }}</th>
                        <th>{{ translate('Date') }}</th>
                        <th>{{ translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $t)
                    <tr>
                        <td><a href="{{ route('admin.sales-crm.ticket.show', $t->id) }}" class="font-weight-bold">{{ $t->ticket_no }}</a></td>
                        <td>
                            <div class="font-weight-bold">{{ $t->contact_name }}</div>
                            <small class="text-muted">{{ $t->phone }}</small>
                        </td>
                        <td style="max-width:200px;white-space:normal">{{ Str::limit($t->subject, 60) }}</td>
                        <td>{{ $t->zone?->name ?? '—' }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $t->channel)) }}</td>
                        <td><span class="badge badge-{{ $t->priority }} text-white">{{ ucfirst($t->priority) }}</span></td>
                        <td><span class="badge badge-{{ $t->status }} text-white">{{ ucfirst(str_replace('_', ' ', $t->status)) }}</span></td>
                        <td>{{ $t->assignedAdmin ? ($t->assignedAdmin->f_name . ' ' . $t->assignedAdmin->l_name) : '—' }}</td>
                        <td><small>{{ $t->created_at->format('d M Y') }}</small></td>
                        <td>
                            <a href="{{ route('admin.sales-crm.ticket.show', $t->id) }}" class="btn btn-sm btn-white" title="View"><i class="tio-visible-outlined"></i></a>
                            <a href="{{ route('admin.sales-crm.ticket.edit', $t->id) }}" class="btn btn-sm btn-white" title="Edit"><i class="tio-edit"></i></a>
                            <form action="{{ route('admin.sales-crm.ticket.destroy', $t->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete ticket?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-white text-danger"><i class="tio-delete-outlined"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center py-4 text-muted">{{ translate('No tickets found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())
        <div class="card-footer">{{ $tickets->links() }}</div>
        @endif
    </div>
</div>
@endsection
