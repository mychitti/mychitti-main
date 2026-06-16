@extends('layouts.vendor.app')
@section('title', $heading)

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-users mr-2"></i>{{ $heading }}</h1>
        <p class="text-muted mb-0" style="font-size:13px;">
            {{ $role === 'doctor' ? 'Patients currently under your care, with the nursing notes & diet charts recorded for them.' : 'Patients assigned to you, with their nursing notes & diet charts.' }}
        </p>
    </div>

    {{-- Today's duty: punch in / out + extra duty --}}
    @isset($duty)
        <div class="card mb-3">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap align-items-center" style="gap:24px;">
                    <div style="font-weight:700; font-size:13px;"><i class="tio-time mr-1"></i> Today's Duty
                        @if($duty['shift_name'])<span class="badge badge-soft-info ml-1">{{ $duty['shift_name'] }}</span>@endif
                    </div>
                    <div><span class="text-muted" style="font-size:12px;">Punch In</span><br>
                        <strong style="color:#16a34a;">{{ $duty['in_time'] ? $duty['in_time']->format('h:i A') : '—' }}</strong></div>
                    <div><span class="text-muted" style="font-size:12px;">Punch Out</span><br>
                        <strong style="color:#dc2626;">{{ $duty['out_time'] ? $duty['out_time']->format('h:i A') : '—' }}</strong></div>
                    <div><span class="text-muted" style="font-size:12px;">Worked</span><br>
                        <strong>{{ $duty['worked_label'] ?: '—' }}</strong></div>
                    <div><span class="text-muted" style="font-size:12px;">Extra Duty</span><br>
                        <strong style="color:{{ $duty['extra_label'] ? '#b45309' : '#9ca3af' }};">{{ $duty['extra_label'] ?: 'None' }}</strong></div>
                </div>
            </div>
        </div>
    @endisset

    @forelse ($admissions as $adm)
        @php
            $isActive = $adm->status === 'active';
        @endphp
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center py-2" style="gap:8px;">
                <h6 class="mb-0">
                    <i class="tio-user mr-1"></i> {{ $adm->patient?->name ?? 'Patient' }}
                    <span class="text-muted" style="font-size:12px;">({{ $adm->patient?->patient_uid }})</span>
                    <span class="badge badge-soft-{{ $isActive ? 'success' : 'secondary' }} ml-1">{{ ucfirst($adm->status) }}</span>
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size:12px;">
                        {{ $adm->ward?->ward_name ?? '—' }} · Bed {{ $adm->bed?->bed_number ?? '—' }} · Adm. {{ $adm->admission_date?->format('d M Y') }}
                    </span>
                    @if (auth('vendor')->check() || hasPermission('ipd_admission', 'view'))
                        <a href="{{ route('vendor.ipd.show', $adm->id) }}" class="btn btn-xs btn--primary">
                            <i class="tio-visible mr-1"></i> Open / Add Notes
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- Clinical summary --}}
                    <div class="col-md-4">
                        <table class="table table-sm table-borderless mb-2" style="font-size:13px;">
                            <tr><td class="text-muted" style="width:42%;">Diagnosis</td><td>{{ $adm->diagnosis ?: '—' }}</td></tr>
                            <tr><td class="text-muted">Reason</td><td>{{ $adm->admission_reason ?: '—' }}</td></tr>
                            @if ($role === 'nurse')
                                <tr><td class="text-muted">Doctor</td><td>Dr. {{ trim(($adm->doctorProfile?->employee?->f_name ?? '') . ' ' . ($adm->doctorProfile?->employee?->l_name ?? '')) ?: '—' }}</td></tr>
                            @else
                                <tr><td class="text-muted">Nurses</td><td>{{ $adm->assignedNurses->map(fn($n) => trim(($n->employee->f_name ?? '') . ' ' . ($n->employee->l_name ?? '')))->filter()->implode(', ') ?: '—' }}</td></tr>
                            @endif
                        </table>
                    </div>

                    {{-- Nursing notes --}}
                    <div class="col-md-4">
                        <div class="text-muted text-uppercase mb-2" style="font-size:11px; font-weight:700; letter-spacing:.5px;">Nursing Notes</div>
                        @forelse ($adm->nursingNotes->take(6) as $note)
                            <div class="mb-2 pb-2 border-bottom">
                                <div style="font-size:13px;">{{ $note->note }}</div>
                                <div class="text-muted" style="font-size:11px;">
                                    <span class="badge badge-soft-info">{{ \App\Models\NursingNote::TYPES[$note->note_type] ?? $note->note_type }}</span>
                                    {{ optional($note->recorded_at)->format('d M, h:i A') }}
                                    @if ($note->recorder) · {{ trim(($note->recorder->f_name ?? '') . ' ' . ($note->recorder->l_name ?? '')) }} @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">No nursing notes yet.</p>
                        @endforelse
                    </div>

                    {{-- Diet charts --}}
                    <div class="col-md-4">
                        <div class="text-muted text-uppercase mb-2" style="font-size:11px; font-weight:700; letter-spacing:.5px;">Diet Charts</div>
                        @forelse ($adm->dietCharts->take(6) as $diet)
                            <div class="mb-2 pb-2 border-bottom">
                                <div style="font-size:13px;">
                                    <span class="badge badge-soft-warning">{{ \App\Models\DietChart::MEAL_TYPES[$diet->meal_type] ?? $diet->meal_type }}</span>
                                    {{ \App\Models\DietChart::DIET_TYPES[$diet->diet_type] ?? $diet->diet_type }}
                                </div>
                                @if ($diet->instructions)<div style="font-size:12px;">{{ $diet->instructions }}</div>@endif
                                <div class="text-muted" style="font-size:11px;">{{ optional($diet->date)->format('d M Y') }}</div>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">No diet charts yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="tio-users" style="font-size:34px; opacity:.4; display:block; margin-bottom:8px;"></i>
                No patients are currently {{ $role === 'doctor' ? 'admitted under you' : 'assigned to you' }}.
            </div>
        </div>
    @endforelse
</div>
@endsection
