@extends('layouts.vendor.app')
@section('title', 'Hospital Dashboard')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="content container-fluid">

    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-hospital" style="font-size:22px;"></i></span>
            Hospital Dashboard
        </h1>
        <form class="date-range-form">
            @include('vendor-views/form_modals/date_range')
            <button type="button" class="btn btn-outline-warning btn-sm" data-toggle="modal" data-target="#dateRangeModal">
                <i class="tio-calendar"></i> {{ translate($preset) }}
            </button>
        </form>
    </div>

    {{-- ── Stat Cards ─────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        @php
        $cards = [
            ['label'=>'Total Patients',    'value'=>$stats['patients'],          'icon'=>'tio-user',           'color'=>'#2563eb', 'bg'=>'#eff6ff', 'route'=>route('vendor.patient.list')],
            ['label'=>'Doctors',           'value'=>$stats['doctors'],           'icon'=>'tio-user-add',       'color'=>'#16a34a', 'bg'=>'#f0fdf4', 'route'=>route('vendor.doctor.list')],
            ['label'=>'Nurses',            'value'=>$stats['nurses'],            'icon'=>'tio-user-outlined',  'color'=>'#9333ea', 'bg'=>'#faf5ff', 'route'=>route('vendor.nurse.list')],
            ['label'=>'OPD Visits',        'value'=>$stats['opd_in_range'],      'icon'=>'tio-document-text',  'color'=>'#0891b2', 'bg'=>'#ecfeff', 'route'=>route('vendor.opd.index')],
            ['label'=>'IPD Admitted',      'value'=>$stats['ipd_admitted'],      'icon'=>'tio-hospital',       'color'=>'#dc2626', 'bg'=>'#fef2f2', 'route'=>route('vendor.ipd.index')],
            ['label'=>'IPD Admissions',    'value'=>$stats['ipd_in_range'],      'icon'=>'tio-layers-outlined', 'color'=>'#b45309','bg'=>'#fffbeb', 'route'=>route('vendor.ipd.index')],
            ['label'=>'Beds Available',    'value'=>$stats['beds_available'],    'icon'=>'tio-checkmark-circle','color'=>'#16a34a','bg'=>'#f0fdf4', 'route'=>route('vendor.ipd.bed-dashboard')],
            ['label'=>'Beds Occupied',     'value'=>$stats['beds_occupied'],     'icon'=>'tio-blocked',        'color'=>'#ea580c', 'bg'=>'#fff7ed', 'route'=>route('vendor.ipd.bed-dashboard')],
            ['label'=>'Prescriptions',     'value'=>$stats['prescriptions_in_range'],'icon'=>'tio-file-text',  'color'=>'#0d9488', 'bg'=>'#f0fdfa', 'route'=>route('vendor.prescription.list')],
        ];
        @endphp

        @foreach($cards as $card)
        <div class="col-6 col-md-3">
            <a href="{{ $card['route'] }}" class="text-decoration-none">
                <div class="card h-100" style="border-left:4px solid {{ $card['color'] }}; background:{{ $card['bg'] }};">
                    <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:46px; height:46px; background:{{ $card['color'] }}20;">
                            <i class="{{ $card['icon'] }}" style="font-size:22px; color:{{ $card['color'] }};"></i>
                        </div>
                        <div>
                            <div class="font-size-24 font-weight-bold" style="color:{{ $card['color'] }}; line-height:1.1;">
                                {{ $card['value'] }}
                            </div>
                            <div class="text-muted" style="font-size:12px;">{{ $card['label'] }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- ── Charts row ──────────────────────────────────────────────────── --}}
    <div class="row mb-4">
        {{-- OPD trend --}}
        <div class="col-md-7 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0">OPD Visits — {{ translate($preset) }}</h6>
                    <a href="{{ route('vendor.opd.index') }}" class="btn btn-xs btn-outline-secondary">View All</a>
                </div>
                <div class="card-body">
                    <canvas id="opdChart" height="100"></canvas>
                </div>
            </div>
        </div>

        {{-- Bed occupancy --}}
        <div class="col-md-5 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0">Bed Occupancy by Ward</h6>
                    <a href="{{ route('vendor.ipd.bed-dashboard') }}" class="btn btn-xs btn-outline-secondary">View Map</a>
                </div>
                <div class="card-body">
                    @if($wards->isEmpty())
                        <div class="text-center text-muted py-4">No wards configured.</div>
                    @else
                        @foreach($wards as $ward)
                        @php
                            $total     = max($ward->beds_count, 1);
                            $occupied  = $ward->beds_count - $ward->available_beds_count;
                            $pct       = round($occupied / $total * 100);
                            $color     = $pct >= 90 ? '#dc2626' : ($pct >= 70 ? '#ea580c' : '#16a34a');
                        @endphp
                        <div class="mb-2">
                            <div class="d-flex justify-content-between mb-1" style="font-size:12px;">
                                <span class="font-weight-bold">{{ $ward->ward_name }}</span>
                                <span class="text-muted">{{ $occupied }}/{{ $ward->beds_count }} occupied</span>
                            </div>
                            <div class="progress" style="height:8px; border-radius:4px;">
                                <div class="progress-bar" role="progressbar"
                                     style="width:{{ $pct }}%; background:{{ $color }}; border-radius:4px;"
                                     title="{{ $pct }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- IPD admissions chart + today's OPD --}}
    <div class="row mb-4">
        <div class="col-md-7 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0">IPD Admissions — {{ translate($preset) }}</h6>
                    <a href="{{ route('vendor.ipd.index') }}" class="btn btn-xs btn-outline-secondary">View All</a>
                </div>
                <div class="card-body">
                    <canvas id="ipdChart" height="100"></canvas>
                </div>
            </div>
        </div>

        {{-- Today's OPD --}}
        <div class="col-md-5 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0">Today's OPD Queue</h6>
                    <a href="{{ route('vendor.opd.create') }}" class="text-primary">
                        <i class="tio-add"></i> Register
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height:240px; overflow-y:auto;">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light" style="position:sticky; top:0;">
                                <tr><th>Token</th><th>Patient</th><th>Doctor</th></tr>
                            </thead>
                            <tbody>
                                @forelse($recentOpdVisits as $visit)
                                <tr>
                                    <td><span class="badge badge-primary">{{ $visit->token_number }}</span></td>
                                    <td>
                                        <strong style="font-size:13px;">{{ $visit->patient?->name }}</strong>
                                        <br><small class="text-muted">{{ $visit->patient?->patient_uid }}</small>
                                    </td>
                                    <td style="font-size:12px;">
                                        Dr. {{ $visit->doctorProfile?->employee?->f_name }}
                                        {{ $visit->doctorProfile?->employee?->l_name }}
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">No visits today.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Recent records grid ──────────────────────────────────────────── --}}
    <div class="row">
        {{-- Recent patients --}}
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0">New Patients — {{ translate($preset) }}</h6>
                    <a href="{{ route('vendor.patient.list') }}" class="btn btn-xs btn-outline-secondary">All</a>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse($recentPatients as $p)
                    <li class="list-group-item d-flex align-items-center gap-2 py-2">
                        <span class="avatar avatar-xs avatar-soft-primary avatar-circle">
                            <span class="avatar-initials">{{ strtoupper(substr($p->name, 0, 1)) }}</span>
                        </span>
                        <div class="flex-grow-1 min-width-0">
                            <a href="{{ route('vendor.patient.show', $p->id) }}"
                               class="d-block font-weight-bold text-truncate" style="font-size:13px;">
                                {{ $p->name }}
                            </a>
                            <small class="text-muted">{{ $p->patient_uid }}
                                @if($p->gender) · {{ ucfirst($p->gender) }} @endif
                            </small>
                        </div>
                        <small class="text-muted flex-shrink-0">{{ $p->created_at->diffForHumans(null, true) }}</small>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted py-3">No patients yet.</li>
                    @endforelse
                </ul>
                <div class="card-footer py-2 text-center">
                    <a href="{{ route('vendor.patient.add') }}" class="text-primary">
                        <i class="tio-add"></i> Add Patient
                    </a>
                </div>
            </div>
        </div>

        {{-- Recent doctors --}}
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0">Recent Doctors</h6>
                    <a href="{{ route('vendor.doctor.list') }}" class="btn btn-xs btn-outline-secondary">All</a>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse($recentDoctors as $doc)
                    <li class="list-group-item d-flex align-items-center gap-2 py-2">
                        <span class="avatar avatar-xs avatar-soft-success avatar-circle">
                            <span class="avatar-initials">{{ strtoupper(substr($doc->employee?->f_name ?? 'D', 0, 1)) }}</span>
                        </span>
                        <div class="flex-grow-1 min-width-0">
                            <span class="d-block font-weight-bold text-truncate" style="font-size:13px;">
                                Dr. {{ $doc->employee?->f_name }} {{ $doc->employee?->l_name }}
                            </span>
                            <small class="text-muted">{{ $doc->specialization }}</small>
                        </div>
                        <small class="text-muted flex-shrink-0">{{ $doc->created_at->diffForHumans(null, true) }}</small>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted py-3">No doctors yet.</li>
                    @endforelse
                </ul>
                <div class="card-footer py-2 text-center">
                    <a href="{{ route('vendor.doctor.create') }}" class="text-primary">
                        <i class="tio-add"></i> Add Doctor
                    </a>
                </div>
            </div>
        </div>

        {{-- Recent nurses --}}
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0">Recent Nurses</h6>
                    <a href="{{ route('vendor.nurse.list') }}" class="btn btn-xs btn-outline-secondary">All</a>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse($recentNurses as $nurse)
                    <li class="list-group-item d-flex align-items-center gap-2 py-2">
                        <span class="avatar avatar-xs avatar-soft-warning avatar-circle">
                            <span class="avatar-initials">{{ strtoupper(substr($nurse->employee?->f_name ?? 'N', 0, 1)) }}</span>
                        </span>
                        <div class="flex-grow-1 min-width-0">
                            <span class="d-block font-weight-bold text-truncate" style="font-size:13px;">
                                {{ $nurse->employee?->f_name }} {{ $nurse->employee?->l_name }}
                            </span>
                            <small class="text-muted">
                                {{ \App\Models\NurseProfile::SHIFTS[$nurse->shift] ?? $nurse->shift }}
                                @if($nurse->ward) · {{ $nurse->ward->ward_name }} @endif
                            </small>
                        </div>
                        <small class="text-muted flex-shrink-0">{{ $nurse->created_at->diffForHumans(null, true) }}</small>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted py-3">No nurses yet.</li>
                    @endforelse
                </ul>
                <div class="card-footer py-2 text-center">
                    <a href="{{ route('vendor.nurse.create') }}" class="text-primary">
                        <i class="tio-add"></i> Add Nurse
                    </a>
                </div>
            </div>
        </div>

        {{-- Current IPD admissions --}}
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0">IPD Admissions — {{ translate($preset) }}</h6>
                    <a href="{{ route('vendor.ipd.index') }}" class="btn btn-xs btn-outline-secondary">All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light">
                            <tr><th>Adm. No.</th><th>Patient</th><th>Ward / Bed</th><th>Day</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentAdmissions as $adm)
                            <tr>
                                <td>
                                    <a href="{{ route('vendor.ipd.show', $adm->id) }}" style="font-size:12px;">
                                        {{ $adm->admission_number }}
                                    </a>
                                </td>
                                <td>
                                    <strong style="font-size:13px;">{{ $adm->patient?->name }}</strong>
                                </td>
                                <td style="font-size:12px;">
                                    {{ $adm->ward?->ward_name }}
                                    @if($adm->bed) · Bed {{ $adm->bed->bed_number }} @endif
                                </td>
                                <td>
                                    @if($adm->status === 'admitted')
                                        <span class="badge badge-success">Day {{ $adm->admission_date?->diffInDays(now()) + 1 }}</span>
                                    @else
                                        <span class="badge badge-secondary">Discharged</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No current admissions.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer py-2 text-center">
                    <a href="{{ route('vendor.ipd.create') }}" class="text-primary">
                        <i class="tio-add"></i> Admit Patient
                    </a>
                </div>
            </div>
        </div>

        {{-- Wards & beds summary --}}
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0">Wards &amp; Beds</h6>
                    <a href="{{ route('vendor.ward.index') }}" class="btn btn-xs btn-outline-secondary">Manage</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                                <tr><th>Ward</th><th>Type</th><th>Total</th><th>Available</th><th>Occupied</th></tr>
                            </thead>
                            <tbody>
                                @forelse($wards as $ward)
                                @php $occupied = $ward->beds_count - $ward->available_beds_count; @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('vendor.ward.beds', $ward->id) }}" style="font-size:13px;">
                                            {{ $ward->ward_name }}
                                        </a>
                                    </td>
                                    <td><span class="badge badge-soft-secondary" style="font-size:10px;">{{ \App\Models\Ward::TYPES[$ward->ward_type] ?? $ward->ward_type }}</span></td>
                                    <td>{{ $ward->beds_count }}</td>
                                    <td><span class="text-success font-weight-bold">{{ $ward->available_beds_count }}</span></td>
                                    <td><span class="{{ $occupied > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">{{ $occupied }}</span></td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No wards configured.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer py-2 text-center">
                    <a href="{{ route('vendor.ward.create') }}" class="text-primary">
                        <i class="tio-add"></i> Add Ward
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('script_2')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// OPD Trend chart
new Chart(document.getElementById('opdChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode($opdLabels) !!},
        datasets: [{
            label: 'OPD Visits',
            data: {!! json_encode($opdData) !!},
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37,99,235,0.08)',
            borderWidth: 2,
            pointRadius: 3,
            fill: true,
            tension: 0.3,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } },
            x: { ticks: { maxTicksLimit: 7 } }
        }
    }
});

// IPD Trend chart
new Chart(document.getElementById('ipdChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($ipdLabels) !!},
        datasets: [{
            label: 'IPD Admissions',
            data: {!! json_encode($ipdData) !!},
            backgroundColor: 'rgba(220,38,38,0.7)',
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } },
            x: { ticks: { maxTicksLimit: 10 } }
        }
    }
});
</script>
@include('vendor-views/js/date_range')
@endpush
