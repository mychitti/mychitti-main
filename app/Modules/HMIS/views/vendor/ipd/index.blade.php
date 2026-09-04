@extends('layouts.vendor.app')
@section('title', 'IPD Admissions')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <style>
    .main { padding-top: 36px !important; }
.filter_form{
    padding: 12px;
}
    /* ── Mobile admission card ── */
    .ipd-card {
        border: 1px solid #c8d2e0;
        border-radius: 10px;
        padding: 14px;
        margin-bottom: 10px;
        background: #fff;
    }
    .ipd-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 8px;
    }
    .ipd-card-adm   { font-weight: 700; font-size: 13px; }
    .ipd-card-body  { font-size: 13px; color: #374151; }
    .ipd-card-body .row-item {
        display: flex;
        gap: 6px;
        padding: 3px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .ipd-card-body .row-item:last-child { border-bottom: none; }
    .ipd-card-body .lbl { color: #6b7280; min-width: 90px; font-size: 12px; }
    .ipd-card-footer {
        margin-top: 10px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    @media (max-width: 1000px) {
        .filter_form {
            padding: 0  !important;
        }
    }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    @include('hmis::vendor.hospital._hospital_submenu_header')
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-hospital" style="font-size:22px;"></i></span>
            IPD Admissions 
        </h1>
        <div class="d-flex gap-2 flex-wrap align-item-center">
            @if (hasPermission('ipd_admission', 'export'))
            <a href="{{ route('vendor.ipd.export', array_filter(['date_range'=>$preset,'custom_date_range'=>request('custom_date_range'),'status'=>$status,'search'=>request('search')])) }}"
               class="btn btn-outline-success btn_sm"><i class="tio-download"></i> Export</a>
            @endif
            <a href="{{ route('vendor.ipd.bed-dashboard') }}" class="btn btn-outline-secondary btn_sm">
                <i class="tio-grid-squares"></i> Bed View
            </a>
            @if (hasPermission('ipd_admission', 'add'))
            <a href="{{ route('vendor.ipd.create') }}" class="btn btn--primary btn-sm btn_sm">
                <i class="tio-add"></i> Admit Patient
            </a>
            @endif
        </div>
    </div>
    @if(hasPermission('ipd_admission', 'list'))

    {{-- Filters --}}
    <form method="GET" class="card card-body mb-3 date-range-form filter_form">
        @include('vendor-views/form_modals/date_range')
        {{-- Row 1: date + status --}}
        <div class="d-flex gap-2 mb-2">
            <button type="button" class="btn btn-outline-warning btn_sm flex-shrink-0"
                    data-toggle="modal" data-target="#dateRangeModal">
                <i class="tio-calendar"></i> {{ translate($preset) }}
            </button>
            <select name="status" class="form-control form-control-sm flex-grow-1">
                <option value="admitted"   {{ $status === 'admitted'   ? 'selected' : '' }}>Currently Admitted</option>
                <option value="discharged" {{ $status === 'discharged' ? 'selected' : '' }}>Discharged</option>
                <option value="all"        {{ $status === 'all'        ? 'selected' : '' }}>All</option>
            </select>
        </div>
        {{-- Row 2: search --}}
        <div class="mb-2">
            <input type="text" name="search" class="form-control form-control-sm"
                placeholder="Patient name, UID, admission no..." value="{{ request('search') }}">
        </div>
        {{-- Row 3: actions --}}
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn--primary btn_sm flex-grow-1">
                <i class="tio-filter-list"></i> Filter
            </button>
            <a href="{{ route('vendor.ipd.index') }}" class="btn btn-outline-secondary btn_sm flex-grow-1 text-center">
                Reset
            </a>
        </div>
    </form>

    {{-- ── Desktop table (md+) ── --}}
    <div class="card d-none d-md-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Adm. No.</th>
                            <th>Patient</th>
                            <th>Ward / Bed</th>
                            <th>Doctor</th>
                            <th>Adm. Date</th>
                            <th>Diagnosis</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($admissions as $adm)
                        <tr>
                            <td><strong>{{ $adm->admission_number }}</strong></td>
                            <td>
                                <strong>{{ $adm->patient?->name }}</strong>
                                <br><small class="text-muted">{{ $adm->patient?->patient_uid }}</small>
                            </td>
                            <td>
                                {{ $adm->ward?->ward_name }}
                                @if($adm->bed)
                                    <br><small class="text-muted">Bed {{ $adm->bed->bed_number }}</small>
                                @endif
                            </td>
                            <td>Dr. {{ $adm->doctorProfile?->employee?->f_name }} {{ $adm->doctorProfile?->employee?->l_name }}</td>
                            <td>{{ $adm->admission_date?->format('d M Y') }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($adm->diagnosis, 40) ?: '—' }}</td>
                            <td>
                                @if($adm->status === 'admitted')
                                    <span class="badge badge-success">Admitted</span>
                                    @php $days = $adm->admission_date?->diffInDays(now()); @endphp
                                    <br><small class="text-muted">Day {{ $days + 1 }}</small>
                                @else
                                    <span class="badge badge-secondary">Discharged</span>
                                    @if($adm->discharge_date)
                                        <br><small class="text-muted">{{ $adm->discharge_date->format('d M Y') }}</small>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if (hasPermission('ipd_admission', 'view'))
                                <a href="{{ route('vendor.ipd.show', $adm->id) }}" class="btn btn-xs btn-outline-primary">View</a>
                                @endif
                                @if($adm->status === 'admitted' && hasPermission('ipd_admission', 'discharge'))
                                <a href="{{ route('vendor.ipd.discharge-form', $adm->id) }}" class="btn btn-xs btn-outline-warning">Discharge</a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No admissions found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Mobile cards (< md) ── --}}
    <div class="d-md-none">
        @forelse($admissions as $adm)
            @php $days = $adm->admission_date?->diffInDays(now()); @endphp
            <div class="ipd-card">
                <div class="ipd-card-header">
                    <div>
                        <div class="ipd-card-adm"># {{ $adm->admission_number }}</div>
                        <div style="font-weight:600; font-size:14px;">{{ $adm->patient?->name }}</div>
                        <div style="font-size:11px; color:#9ca3af;">{{ $adm->patient?->patient_uid }}</div>
                    </div>
                    <div>
                        @if($adm->status === 'admitted')
                            <span class="badge badge-success">Admitted</span>
                            <div style="font-size:11px; color:#6b7280; text-align:right;">Day {{ $days + 1 }}</div>
                        @else
                            <span class="badge badge-secondary">Discharged</span>
                            @if($adm->discharge_date)
                                <div style="font-size:11px; color:#6b7280; text-align:right;">{{ $adm->discharge_date->format('d M Y') }}</div>
                            @endif
                        @endif
                    </div>
                </div>
                <div class="ipd-card-body">
                    <div class="row-item">
                        <span class="lbl">Ward / Bed</span>
                        <span>{{ $adm->ward?->ward_name }}{{ $adm->bed ? ' · Bed ' . $adm->bed->bed_number : '' }}</span>
                    </div>
                    <div class="row-item">
                        <span class="lbl">Doctor</span>
                        <span>Dr. {{ $adm->doctorProfile?->employee?->f_name }} {{ $adm->doctorProfile?->employee?->l_name }}</span>
                    </div>
                    <div class="row-item">
                        <span class="lbl">Adm. Date</span>
                        <span>{{ $adm->admission_date?->format('d M Y') }}</span>
                    </div>
                    @if($adm->diagnosis)
                    <div class="row-item">
                        <span class="lbl">Diagnosis</span>
                        <span>{{ \Illuminate\Support\Str::limit($adm->diagnosis, 60) }}</span>
                    </div>
                    @endif
                </div>
                <div class="ipd-card-footer">
                    @if (hasPermission('ipd_admission', 'view'))
                    <a href="{{ route('vendor.ipd.show', $adm->id) }}" class="btn btn-sm btn-outline-primary">
                        <i class="tio-eye"></i> View
                    </a>
                    @endif
                    @if($adm->status === 'admitted' && hasPermission('ipd_admission', 'discharge'))
                    <a href="{{ route('vendor.ipd.discharge-form', $adm->id) }}" class="btn btn-sm btn-outline-warning">
                        <i class="tio-exit-outlined"></i> Discharge
                    </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-5">No admissions found.</div>
        @endforelse
    </div>

    <div class="mt-3">{{ $admissions->appends(request()->query())->links() }}</div>
    @endif
</div>
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
@endpush
