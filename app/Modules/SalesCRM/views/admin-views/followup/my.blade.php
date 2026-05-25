@extends('layouts.admin.app')
@section('title', translate('My Follow-ups'))

@push('css_or_js')
<link rel="stylesheet" href="{{ asset('public/assets/admin/vendor/fontawesome-free/css/all.min.css') }}">
<style>
    .badge-pending   { background:#fd7e14; }
    .badge-done      { background:#28a745; }
    .badge-missed    { background:#dc3545; }
    .badge-cancelled { background:#6c757d; }
    .fu-overdue-row td:first-child { border-left: 3px solid #dc3545; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-header-title">{{ translate('My Follow-ups') }}</h1>
                <ol class="breadcrumb breadcrumb-no-gutter">
                    <li class="breadcrumb-item"><a href="{{ route('admin.sales-crm.followup.index') }}">{{ translate('Follow-ups') }}</a></li>
                    <li class="breadcrumb-item active">{{ translate('My History') }}</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0">{{ translate('My Follow-ups History') }}</h5>
            <form method="GET" class="d-flex align-items-center gap-2">
                <select name="status" class="form-control form-control-sm" style="min-width:140px" onchange="this.form.submit()">
                    <option value="">{{ translate('All Statuses') }}</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>
                            {{ ucfirst($s) }}
                        </option>
                    @endforeach
                </select>
                @if(request('status'))
                    <a href="{{ route('admin.sales-crm.followup.my') }}" class="btn btn-sm btn-outline-secondary">{{ translate('Clear') }}</a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Title') }}</th>
                            <th>{{ translate('Query') }}</th>
                            <th>{{ translate('Due') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Role') }}</th>
                            <th>{{ translate('Zone') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($followups as $fu)
                            @php
                                $isOverdue = $fu->status === 'pending' && $fu->due_date->lt(today());
                                $myId = auth('admin')->id();
                                $role = ($fu->assigned_to == $myId && $fu->admin_id == $myId)
                                    ? 'Creator & Assignee'
                                    : ($fu->assigned_to == $myId ? 'Assigned to me' : 'Created by me');
                            @endphp
                            <tr class="{{ $isOverdue ? 'fu-overdue-row' : '' }}">
                                <td>
                                    <div class="font-weight-semibold">{{ $fu->title }}</div>
                                    @if($fu->notes)
                                        <small class="text-muted">{{ Str::limit($fu->notes, 60) }}</small>
                                    @endif
                                    @if($isOverdue)
                                        <span class="badge badge-soft-danger ml-1" style="font-size:.65rem;">OVERDUE</span>
                                    @endif
                                </td>
                                <td>
                                    @if($fu->salesQuery)
                                        <a href="{{ route('admin.sales-crm.query.show', $fu->salesQuery->id) }}" class="text-primary">
                                            {{ $fu->salesQuery->ref_no }}
                                        </a>
                                        <div class="small text-muted">{{ $fu->salesQuery->contact_name }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <div>{{ $fu->due_date->format('d M Y') }}</div>
                                    @if($fu->due_time)
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($fu->due_time)->format('h:i A') }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge text-white badge-{{ $fu->status }}">
                                        {{ ucfirst($fu->status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-secondary">{{ $role }}</span>
                                </td>
                                <td>{{ $fu->zone?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    {{ translate('No follow-ups found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($followups->hasPages())
            <div class="card-footer">
                {{ $followups->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
