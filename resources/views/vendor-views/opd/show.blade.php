@extends('layouts.vendor.app')
@section('title', 'OPD Visit #' . $visit->id)

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-document-text" style="font-size:22px;"></i></span>
            OPD Visit
            <span class="badge badge-primary ml-2" style="font-size:16px;">Token {{ $visit->token_number }}</span>
        </h1>
        <div class="d-flex gap-2">
            <a href="{{ route('vendor.hospital-bill.create-opd', $visit->id) }}" class="btn btn-sm btn-outline-success">
                <i class="tio-receipt"></i> Generate Bill
            </a>
            <a href="{{ route('vendor.opd.edit', $visit->id) }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-edit"></i> Edit
            </a>
            <a href="{{ route('vendor.opd.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-arrow-backward"></i> Register
            </a>
        </div>
    </div>

    {{-- Info header --}}
    <div class="card mb-3" style="background:linear-gradient(90deg,#eff6ff,#f0fdf4); border:1px solid #bfdbfe;">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap gap-4">
                <div>
                    <small class="text-muted d-block">Patient</small>
                    <strong>
                        <a href="{{ route('vendor.patient.show', $visit->patient_id) }}">{{ $visit->patient?->name }}</a>
                    </strong>
                    <span class="text-muted">({{ $visit->patient?->patient_uid }})</span>
                </div>
                <div>
                    <small class="text-muted d-block">Doctor</small>
                    <strong>Dr. {{ $visit->doctorProfile?->employee?->f_name }} {{ $visit->doctorProfile?->employee?->l_name }}</strong>
                </div>
                <div>
                    <small class="text-muted d-block">Date</small>
                    <strong>{{ $visit->visit_date?->format('d M Y') }}</strong>
                </div>
                <div>
                    <small class="text-muted d-block">Visit Type</small>
                    <strong>{{ \App\Models\OpdVisit::VISIT_TYPES[$visit->visit_type] ?? $visit->visit_type }}</strong>
                </div>
                @if($visit->recorder)
                <div>
                    <small class="text-muted d-block">Recorded By</small>
                    <strong>{{ $visit->recorder?->f_name }} {{ $visit->recorder?->l_name }}</strong>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Vitals card --}}
        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-header py-2"><h6 class="mb-0">Vitals</h6></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width:50%;">Blood Pressure</td>
                                <td>
                                    @if($visit->bp_systolic && $visit->bp_diastolic)
                                        <strong>{{ $visit->bp_systolic }}/{{ $visit->bp_diastolic }}</strong> mmHg
                                        @php $map = $visit->bp_diastolic + ($visit->bp_systolic - $visit->bp_diastolic) / 3; @endphp
                                        @if($visit->bp_systolic >= 140 || $visit->bp_diastolic >= 90)
                                            <span class="badge badge-soft-danger ml-1">High</span>
                                        @elseif($visit->bp_systolic < 90 || $visit->bp_diastolic < 60)
                                            <span class="badge badge-soft-warning ml-1">Low</span>
                                        @else
                                            <span class="badge badge-soft-success ml-1">Normal</span>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Temperature</td>
                                <td>
                                    @if($visit->temperature)
                                        <strong>{{ $visit->temperature }}</strong> °F
                                        @if($visit->temperature >= 100.4)
                                            <span class="badge badge-soft-danger ml-1">Fever</span>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Pulse</td>
                                <td>{{ $visit->pulse_rate ? $visit->pulse_rate.' bpm' : '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Resp. Rate</td>
                                <td>{{ $visit->respiratory_rate ? $visit->respiratory_rate.' /min' : '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">SpO2</td>
                                <td>
                                    @if($visit->spo2)
                                        <strong class="{{ $visit->spo2 < 95 ? 'text-danger' : 'text-success' }}">{{ $visit->spo2 }}%</strong>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Weight</td>
                                <td>{{ $visit->weight ? $visit->weight.' kg' : '<span class="text-muted">—</span>' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Height</td>
                                <td>{{ $visit->height ? $visit->height.' cm' : '<span class="text-muted">—</span>' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Chief complaint & notes --}}
        <div class="col-md-7">
            @if($visit->chief_complaint)
            <div class="card mb-3">
                <div class="card-header py-2"><h6 class="mb-0">Chief Complaint</h6></div>
                <div class="card-body">{{ $visit->chief_complaint }}</div>
            </div>
            @endif
            @if($visit->notes)
            <div class="card mb-3">
                <div class="card-header py-2"><h6 class="mb-0">Notes</h6></div>
                <div class="card-body" style="white-space:pre-wrap;">{{ $visit->notes }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="d-flex gap-2">
        <a href="{{ route('vendor.patient.show', $visit->patient_id) }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-user"></i> Patient Profile
        </a>
        <a href="{{ route('vendor.prescription.create', ['patient_id' => $visit->patient_id, 'doctor_profile_id' => $visit->doctor_profile_id]) }}"
           class="btn btn-sm btn-outline-primary">
            <i class="tio-file-text"></i> Write Prescription
        </a>
        <a href="{{ route('vendor.ipd.create', ['patient_id' => $visit->patient_id]) }}" class="btn btn-sm btn-outline-warning">
            <i class="tio-hospital"></i> Admit to IPD
        </a>
    </div>
</div>
@endsection
