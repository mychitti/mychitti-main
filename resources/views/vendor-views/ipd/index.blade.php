@extends('layouts.vendor.app')
@section('title', 'IPD Admissions')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-hospital" style="font-size:22px;"></i></span>
            IPD Admissions
        </h1>
        <div class="d-flex gap-2">
            @if (hasPermission('ipd_admission', 'export'))
            <a href="{{ route('vendor.ipd.export', array_filter(['date_range'=>$preset,'custom_date_range'=>request('custom_date_range'),'status'=>$status,'search'=>request('search')])) }}"
               class="btn btn-outline-success"><i class="tio-download"></i> Export</a>
            @endif
            <a href="{{ route('vendor.ipd.bed-dashboard') }}" class="btn btn-outline-secondary">
                <i class="tio-grid-squares"></i> Bed View
            </a>
            @if (hasPermission('ipd_admission', 'add'))
            <a href="{{ route('vendor.ipd.create') }}" class="btn btn--primary btn-sm">
                <i class="tio-add"></i> Admit Patient
            </a>
            @endif
        </div>
    </div>
    @if(hasPermission('ipd_admission', 'list'))

    {{-- Filters --}}
    <form method="GET" class="card card-body mb-3 py-2 date-range-form">
        @include('vendor-views/form_modals/date_range')
        <div class="d-flex flex-wrap gap-2 align-items-end">
            <button type="button" class="btn btn-outline-warning " data-toggle="modal" data-target="#dateRangeModal">
                <i class="tio-calendar"></i> {{ translate($preset) }}
            </button>
            <div>
                <label class="input-label mb-1" style="font-size:12px;">Status</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="admitted" {{ $status === 'admitted' ? 'selected' : '' }}>Currently Admitted</option>
                    <option value="discharged" {{ $status === 'discharged' ? 'selected' : '' }}>Discharged</option>
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
                </select>
            </div>
            <div>
                <label class="input-label mb-1" style="font-size:12px;">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" style="min-width:200px;"
                    placeholder="Patient name, UID, admission no..." value="{{ request('search') }}">
            </div>
            <button type="submit" class="btn  btn--primary">Filter</button>
            <a href="{{ route('vendor.ipd.index') }}" class="btn  btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="card">
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
                                <a href="{{ route('vendor.ipd.show', $adm->id) }}" class="btn btn-xs btn-outline-primary">
                                    View
                                </a>
                                @endif
                                @if($adm->status === 'admitted' && (hasPermission('ipd_admission', 'discharge')))
                                <a href="{{ route('vendor.ipd.discharge-form', $adm->id) }}" class="btn btn-xs btn-outline-warning">
                                    Discharge
                                </a>
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

    <div class="mt-3">{{ $admissions->appends(request()->query())->links() }}</div>
    @endif
</div>
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
@endpush
