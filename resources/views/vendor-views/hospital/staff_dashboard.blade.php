@extends('layouts.vendor.app')
@section('title', 'My OPD Visits')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="content container-fluid">

    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-document-text" style="font-size:22px;"></i></span>
                My OPD Visits
            </h1>
            @if($doctorProfile)
                <small class="text-muted">
                    Dr. {{ $doctorProfile->employee?->f_name }} {{ $doctorProfile->employee?->l_name }}
                    @if($doctorProfile->specialization)
                        &mdash; {{ $doctorProfile->specialization }}
                    @endif
                </small>
            @endif
        </div>
        <div class="d-flex align-items-center" style="gap:8px;">
            <a href="{{ route('vendor.my-doctor-profile.edit') }}" class="btn btn-outline-primary btn-sm">
                <i class="tio-user-outlined mr-1"></i> My Profile & Slots
            </a>
            <form class="date-range-form mb-0">
                @include('vendor-views/form_modals/date_range')
                <button type="button" class="btn btn-outline-warning btn-sm" data-toggle="modal" data-target="#dateRangeModal">
                    <i class="tio-calendar"></i> {{ translate($preset) }}
                </button>
            </form>
        </div>
    </div>

    @if(!$doctorProfile)
        <div class="alert alert-warning mt-3">
            <i class="tio-warning-outlined"></i>
            No doctor profile is linked to your account. Please contact the administrator.
        </div>
    @else

    {{-- Summary strip --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-4">
            <div class="card text-center py-3">
                <div style="font-size:28px; font-weight:700; color:#2563eb;">{{ $opdVisits->total() }}</div>
                <div class="text-muted" style="font-size:13px;">Visits ({{ translate($preset) }})</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-center py-3">
                <div style="font-size:28px; font-weight:700; color:#16a34a;">
                    {{ $opdVisits->where('visit_type', 'new')->count() }}
                </div>
                <div class="text-muted" style="font-size:13px;">New Patients</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-center py-3">
                <div style="font-size:28px; font-weight:700; color:#0891b2;">
                    {{ $opdVisits->where('visit_type', 'followup')->count() }}
                </div>
                <div class="text-muted" style="font-size:13px;">Follow-Ups</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3 py-2 px-3">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
            <input type="hidden" name="date_range" value="{{ $preset }}">
            @if(request('custom_date_range'))
                <input type="hidden" name="custom_date_range" value="{{ request('custom_date_range') }}">
            @endif
            <input type="text" name="search" class="form-control form-control-sm"
                style="min-width:200px;" placeholder="Search by patient name or UID..."
                value="{{ $search }}">
            <button type="submit" class="btn btn-sm btn--primary">Search</button>
            @if($search)
                <a href="{{ request()->url() }}?date_range={{ $preset }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Token</th>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Visit Type</th>
                            <th>Chief Complaint</th>
                            <th>Vitals</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($opdVisits as $visit)
                            <tr>
                                <td>
                                    <span class="badge badge-primary" style="font-size:14px; padding:6px 10px;">
                                        {{ $visit->token_number }}
                                    </span>
                                </td>
                                <td style="white-space:nowrap;">
                                    {{ \Carbon\Carbon::parse($visit->visit_date)->format('d M Y') }}
                                </td>
                                <td>
                                    <strong>{{ $visit->patient?->name }}</strong>
                                    <br><small class="text-muted">{{ $visit->patient?->patient_uid }}</small>
                                </td>
                                <td>
                                    @php
                                        $typeColors = ['new'=>'primary','followup'=>'info','emergency'=>'danger','review'=>'warning'];
                                        $color = $typeColors[$visit->visit_type] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-soft-{{ $color }}">
                                        {{ \App\Models\OpdVisit::VISIT_TYPES[$visit->visit_type] ?? $visit->visit_type }}
                                    </span>
                                </td>
                                <td>{{ \Illuminate\Support\Str::limit($visit->chief_complaint, 50) ?: '—' }}</td>
                                <td style="font-size:12px; white-space:nowrap;">
                                    @if($visit->bp_systolic && $visit->bp_diastolic)
                                        <span title="Blood Pressure"><i class="tio-heart-outlined text-danger"></i>
                                            {{ $visit->bp_systolic }}/{{ $visit->bp_diastolic }}</span><br>
                                    @endif
                                    @if($visit->temperature)
                                        <span title="Temperature"><i class="tio-thermometer text-warning"></i>
                                            {{ $visit->temperature }}°F</span><br>
                                    @endif
                                    @if($visit->spo2)
                                        <span title="SpO2"><i class="tio-pulse text-info"></i>
                                            {{ $visit->spo2 }}%</span><br>
                                    @endif
                                    @if($visit->pulse_rate)
                                        <span title="Pulse"><i class="tio-heart text-danger"></i>
                                            {{ $visit->pulse_rate }} bpm</span>
                                    @endif
                                    @if(!$visit->bp_systolic && !$visit->temperature && !$visit->spo2 && !$visit->pulse_rate)
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-soft-success">{{ ucfirst($visit->status ?? 'visited') }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('vendor.opd.show', $visit->id) }}"
                                        class="btn btn-xs btn--primary">
                                        <i class="tio-visible"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="tio-document-text" style="font-size:40px; opacity:.3; display:block; margin-bottom:8px;"></i>
                                    No OPD visits found for this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $opdVisits->appends(request()->query())->links() }}
    </div>

    @endif
</div>
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
@endpush
