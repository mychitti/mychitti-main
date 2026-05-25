@extends('layouts.admin.app')
@section('title', translate('Sales & Marketing'))

@push('css_or_js')
<style>
    .crm-stat-card { border-radius: 10px; padding: 20px 24px; color: #fff; display: flex; justify-content: space-between; align-items: center; }
    .crm-stat-card .crm-stat-num { font-size: 2rem; font-weight: 700; line-height: 1; }
    .crm-stat-card .crm-stat-label { font-size: .8rem; opacity: .85; margin-top: 4px; }
    .crm-stat-card .crm-stat-icon { font-size: 2.4rem; opacity: .35; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-header-title">{{ translate('Sales & Marketing') }}</h1>
                <p class="text-muted mb-0">{{ now()->format('l, d F Y') }}</p>
            </div>
        </div>
    </div>

    {{-- Sales Queries --}}
    <h5 class="mb-3">{{ translate('Sales Queries') }}</h5>
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="crm-stat-card" style="background: linear-gradient(135deg,#17a2b8,#138496)">
                <div>
                    <div class="crm-stat-num">{{ $crmStats['queries_new'] }}</div>
                    <div class="crm-stat-label">{{ translate('New Queries') }}</div>
                </div>
                <i class="tio-add-circle-outlined crm-stat-icon"></i>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="crm-stat-card" style="background: linear-gradient(135deg,#fd7e14,#e8650a)">
                <div>
                    <div class="crm-stat-num">{{ $crmStats['queries_in_progress'] }}</div>
                    <div class="crm-stat-label">{{ translate('In Progress') }}</div>
                </div>
                <i class="tio-reload crm-stat-icon"></i>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="crm-stat-card" style="background: linear-gradient(135deg,#6c757d,#545b62)">
                <div>
                    <div class="crm-stat-num">{{ $crmStats['queries_total'] }}</div>
                    <div class="crm-stat-label">{{ translate('Total Queries') }}</div>
                </div>
                <i class="tio-city crm-stat-icon"></i>
            </div>
        </div>
    </div>

    {{-- Follow-ups --}}
    <h5 class="mb-3">{{ translate('Follow-ups') }}</h5>
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="crm-stat-card" style="background: linear-gradient(135deg,#28a745,#1e7e34)">
                <div>
                    <div class="crm-stat-num">{{ $crmStats['followups_today'] }}</div>
                    <div class="crm-stat-label">{{ translate('Due Today') }}</div>
                </div>
                <i class="tio-calendar crm-stat-icon"></i>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="crm-stat-card" style="background: linear-gradient(135deg,#dc3545,#bd2130)">
                <div>
                    <div class="crm-stat-num">{{ $crmStats['followups_overdue'] }}</div>
                    <div class="crm-stat-label">{{ translate('Overdue') }}</div>
                </div>
                <i class="tio-alarm crm-stat-icon"></i>
            </div>
        </div>
    </div>

    {{-- Support Tickets --}}
    <h5 class="mb-3">{{ translate('Support Tickets') }}</h5>
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="crm-stat-card" style="background: linear-gradient(135deg,#007bff,#0062cc)">
                <div>
                    <div class="crm-stat-num">{{ $crmStats['tickets_open'] }}</div>
                    <div class="crm-stat-label">{{ translate('Open Tickets') }}</div>
                </div>
                <i class="tio-help-outlined crm-stat-icon"></i>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="crm-stat-card" style="background: linear-gradient(135deg,#fd7e14,#e8650a)">
                <div>
                    <div class="crm-stat-num">{{ $crmStats['tickets_in_progress'] }}</div>
                    <div class="crm-stat-label">{{ translate('In Progress') }}</div>
                </div>
                <i class="tio-refresh crm-stat-icon"></i>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="crm-stat-card" style="background: linear-gradient(135deg,#6c757d,#545b62)">
                <div>
                    <div class="crm-stat-num">{{ $crmStats['tickets_total'] }}</div>
                    <div class="crm-stat-label">{{ translate('Total Tickets') }}</div>
                </div>
                <i class="tio-layers-outlined crm-stat-icon"></i>
            </div>
        </div>
    </div>

    {{-- Quick links --}}
    <div class="row">
        <div class="col-md-4 mb-3">
            <a href="{{ route('admin.sales-crm.query.create') }}" class="btn btn-primary btn-block py-3">
                <i class="tio-add mr-2"></i>{{ translate('Add Sales Query') }}
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="{{ route('admin.sales-crm.followup.create') }}" class="btn btn-success btn-block py-3">
                <i class="tio-calendar mr-2"></i>{{ translate('Schedule Follow-up') }}
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="{{ route('admin.sales-crm.ticket.create') }}" class="btn btn-info btn-block py-3">
                <i class="tio-add mr-2"></i>{{ translate('New Support Ticket') }}
            </a>
        </div>
    </div>
</div>
@endsection
