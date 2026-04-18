@extends('layouts.vendor.app')
@section('title', 'Patient Profile')
@use('Illuminate\Support\Facades\Storage')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="page-header-title">
                <i class="tio-user mr-2"></i> Patient Profile
            </h1>
            <div>
                <a href="{{ route('vendor.patient.edit', $patient->id) }}" class="btn btn-sm btn--warning">
                    <i class="tio-edit"></i> Edit
                </a>
                <a href="{{ route('vendor.patient.list') }}" class="btn btn-sm btn-soft-secondary ml-1">
                    <i class="tio-arrow-backward"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Left: Personal Info --}}
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body text-center">
                    @if($patient->photo)
                        <img src="{{ asset('storage/patient/' . $patient->photo) }}"
                            class="rounded-circle mb-3" width="90" height="90" style="object-fit:cover;">
                    @else
                        <div class="rounded-circle bg-soft-primary d-inline-flex align-items-center justify-content-center mb-3"
                            style="width:90px;height:90px;font-size:32px;">
                            <i class="tio-user"></i>
                        </div>
                    @endif
                    <h5 class="mb-0">{{ $patient->name }}</h5>
                    <span class="badge badge-soft-info mt-1">{{ $patient->patient_uid }}</span>

                    <hr>

                    <table class=" table-sm table-borderless text-left">
                        <tr><th><b>Phone</b></th><td>{{ $patient->phone ?? '—' }}</td></tr>
                        <tr><th><b>Email</b></th><td>{{ $patient->email ?? '—' }}</td></tr>
                        <tr><th><b>Gender</b></th><td>{{ ucfirst($patient->gender ?? '—') }}</td></tr>
                        <tr><th><b>DOB</b></th><td>
                            @if($patient->dob)
                                {{ \Carbon\Carbon::parse($patient->dob)->format('d M Y') }}
                                ({{ \Carbon\Carbon::parse($patient->dob)->age }} yrs)
                            @else —
                            @endif
                        </td></tr>
                        <tr><th><b>Blood Group </b></th><td>{{ $patient->blood_group ?? '—' }}</td></tr>
                        <tr><th><b>Address </b></th><td>{{ implode(', ', array_filter([$patient->address, $patient->city, $patient->state, $patient->pincode])) ?: '—' }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Emergency Contact</h6></div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th>Name</th><td>{{ $patient->emergency_contact_name ?? '—' }}</td></tr>
                        <tr><th>Phone</th><td>{{ $patient->emergency_contact_phone ?? '—' }}</td></tr>
                        <tr><th>Relation</th><td>{{ $patient->emergency_contact_relation ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right: Medical History + History --}}
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Medical History</h6></div>
                <div class="card-body">
                    @if($patient->medicalHistory)
                        @php $h = $patient->medicalHistory; @endphp
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Allergies:</strong><br>{{ $patient->allergies ?? '—' }}</p>
                                <p><strong>Chronic Conditions:</strong><br>{{ $h->chronic_conditions ?? '—' }}</p>
                                <p><strong>Past Surgeries:</strong><br>{{ $h->past_surgeries ?? '—' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Current Medications:</strong><br>{{ $h->current_medications ?? '—' }}</p>
                                <p><strong>Family History:</strong><br>{{ $h->family_history ?? '—' }}</p>
                                <p>
                                    <strong>Smoking:</strong>
                                    <span class="badge badge-soft-{{ $h->smoking ? 'danger' : 'success' }}">
                                        {{ $h->smoking ? 'Yes' : 'No' }}
                                    </span>
                                    &nbsp;
                                    <strong>Alcohol:</strong>
                                    <span class="badge badge-soft-{{ $h->alcohol ? 'danger' : 'success' }}">
                                        {{ $h->alcohol ? 'Yes' : 'No' }}
                                    </span>
                                </p>
                                <p><strong>Notes:</strong><br>{{ $h->notes ?? '—' }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">No medical history recorded.</p>
                    @endif
                </div>
            </div>

            {{-- Documents --}}
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Documents</h6></div>
                <div class="card-body">
                    @if($patient->documents->count())
                        <ul class="list-group list-group-flush">
                            @foreach($patient->documents as $doc)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <i class="tio-file mr-2"></i>{{ $doc->document_name }}
                                    <span class="badge badge-soft-{{ $doc->document_type == 'id_proof' ? 'warning' : 'info' }} ml-1">
                                        {{ $doc->document_type == 'id_proof' ? 'ID Proof' : 'Report' }}
                                    </span>
                                </span>
                                <a href="{{ asset('storage/') . '/'. $doc->file_path }}"
                                    target="_blank" class="btn btn-xs btn-soft-primary">
                                    <i class="tio-visible mr-1"></i> View
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">No documents uploaded.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Patient History ─────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Patient History</h6>
        </div>
        <div class="card-body p-0">
            {{-- Tabs --}}
            <ul class="nav nav-tabs nav-tabs-line px-3 pt-2" id="historyTabs">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#tab-appointments">
                        Appointments
                        <span class="badge badge-soft-primary ml-1">{{ $appointments->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-opd">
                        OPD Visits
                        <span class="badge badge-soft-info ml-1">{{ $opdVisits->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-ipd">
                        IPD Admissions
                        <span class="badge badge-soft-warning ml-1">{{ $ipdAdmissions->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-rx">
                        Prescriptions
                        <span class="badge badge-soft-success ml-1">{{ $prescriptions->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-consents">
                        Consent Forms
                        <span class="badge badge-soft-warning ml-1">{{ $consents->count() }}</span>
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                {{-- Appointments --}}
                <div class="tab-pane fade show active" id="tab-appointments">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr><th>Date</th><th>Doctor</th><th>Reason</th><th>Status</th><th></th></tr>
                            </thead>
                            <tbody>
                                @forelse($appointments as $appt)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($appt->appointment_date)->format('d M Y') }}
                                        @if($appt->appointment_time)
                                            <br><small class="text-muted">{{ $appt->appointment_time }}</small>
                                        @endif
                                    </td>
                                    <td>Dr. {{ $appt->doctorProfile?->employee?->f_name }} {{ $appt->doctorProfile?->employee?->l_name }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($appt->reason ?? '—', 50) }}</td>
                                    <td>
                                        @php
                                            $badgeMap = [
                                                'confirmed'  => 'success',
                                                'completed'  => 'primary',
                                                'cancelled'  => 'danger',
                                                'no_show'    => 'secondary',
                                                'pending'    => 'warning',
                                            ];
                                        @endphp
                                        <span class="badge badge-soft-{{ $badgeMap[$appt->status] ?? 'secondary' }}">
                                            {{ ucfirst(str_replace('_', ' ', $appt->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('vendor.appointment.show', $appt->id) }}"
                                           class="btn btn-xs btn-outline-secondary">View</a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No appointments.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- OPD Visits --}}
                <div class="tab-pane fade" id="tab-opd">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr><th>Date</th><th>Token</th><th>Doctor</th><th>Type</th><th>Complaint</th><th>Vitals</th><th></th></tr>
                            </thead>
                            <tbody>
                                @forelse($opdVisits as $visit)
                                <tr>
                                    <td>{{ $visit->visit_date?->format('d M Y') }}</td>
                                    <td><span class="badge badge-primary">{{ $visit->token_number }}</span></td>
                                    <td>Dr. {{ $visit->doctorProfile?->employee?->f_name }} {{ $visit->doctorProfile?->employee?->l_name }}</td>
                                    <td>
                                        <span class="badge badge-soft-info">
                                            {{ \App\Models\OpdVisit::VISIT_TYPES[$visit->visit_type] ?? $visit->visit_type }}
                                        </span>
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit($visit->chief_complaint ?? '—', 40) }}</td>
                                    <td style="font-size:11px; white-space:nowrap;">
                                        @if($visit->bp_systolic) BP: {{ $visit->bp_systolic }}/{{ $visit->bp_diastolic }}<br>@endif
                                        @if($visit->temperature) T: {{ $visit->temperature }}°F<br>@endif
                                        @if($visit->spo2) SpO2: {{ $visit->spo2 }}%@endif
                                    </td>
                                    <td>
                                        <a href="{{ route('vendor.opd.show', $visit->id) }}"
                                           class="btn btn-xs btn-outline-secondary">View</a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted py-3">No OPD visits.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- IPD Admissions --}}
                <div class="tab-pane fade" id="tab-ipd">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr><th>Adm. No.</th><th>Admitted</th><th>Ward / Bed</th><th>Doctor</th><th>Diagnosis</th><th>Status</th><th></th></tr>
                            </thead>
                            <tbody>
                                @forelse($ipdAdmissions as $adm)
                                <tr>
                                    <td><strong>{{ $adm->admission_number }}</strong></td>
                                    <td>{{ $adm->admission_date?->format('d M Y') }}
                                        @if($adm->discharge_date)
                                            <br><small class="text-muted">→ {{ $adm->discharge_date->format('d M Y') }}</small>
                                            <br><small class="text-muted">{{ $adm->admission_date->diffInDays($adm->discharge_date) }} days</small>
                                        @else
                                            <br><small class="text-success">Day {{ $adm->admission_date->diffInDays(now()) + 1 }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $adm->ward?->ward_name }}
                                        @if($adm->bed)<br><small class="text-muted">Bed {{ $adm->bed->bed_number }}</small>@endif
                                    </td>
                                    <td>Dr. {{ $adm->doctorProfile?->employee?->f_name }} {{ $adm->doctorProfile?->employee?->l_name }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($adm->diagnosis ?? '—', 40) }}</td>
                                    <td>
                                        <span class="badge {{ $adm->status === 'admitted' ? 'badge-success' : 'badge-secondary' }}">
                                            {{ ucfirst($adm->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('vendor.ipd.show', $adm->id) }}"
                                           class="btn btn-xs btn-outline-secondary">View</a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted py-3">No IPD admissions.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Prescriptions --}}
                <div class="tab-pane fade" id="tab-rx">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr><th>Date</th><th>Doctor</th><th>Diagnosis</th><th>Medicines</th><th>Status</th><th></th></tr>
                            </thead>
                            <tbody>
                                @forelse($prescriptions as $rx)
                                <tr>
                                    <td>{{ $rx->created_at->format('d M Y') }}</td>
                                    <td>Dr. {{ $rx->doctorProfile?->employee?->f_name }} {{ $rx->doctorProfile?->employee?->l_name }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($rx->diagnosis ?? '—', 40) }}</td>
                                    <td>{{ $rx->items_count ?? $rx->items->count() }} medicine(s)</td>
                                    <td>
                                        <span class="badge {{ $rx->is_finalized ? 'badge-success' : 'badge-soft-warning' }}">
                                            {{ $rx->is_finalized ? 'Finalized' : 'Draft' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('vendor.prescription.show', $rx->id) }}"
                                           class="btn btn-xs btn-outline-secondary">View</a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">No prescriptions.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Consent Forms --}}
                <div class="tab-pane fade" id="tab-consents">
                    <div class="d-flex justify-content-end px-3 pt-2">
                        <a href="{{ route('vendor.consent.create', ['patient_id' => $patient->id]) }}"
                           class="btn btn-xs btn--primary">
                            <i class="tio-add"></i> Add Consent
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr><th>Title</th><th>Admission</th><th>Signed By</th><th>Date Signed</th><th></th></tr>
                            </thead>
                            <tbody>
                                @forelse($consents as $c)
                                <tr>
                                    <td>{{ $c->title }}</td>
                                    <td>
                                        @if($c->admission)
                                            <a href="{{ route('vendor.ipd.show', $c->ipd_admission_id) }}"
                                               style="font-size:12px;">{{ $c->admission->admission_number }}</a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td style="font-size:12px;">{{ $c->signatory_name ?: '—' }}</td>
                                    <td style="font-size:12px;">{{ $c->signed_at?->format('d M Y') }}</td>
                                    <td>
                                        <a href="{{ route('vendor.consent.show', $c->id) }}"
                                           class="btn btn-xs btn-outline-primary">
                                            <i class="tio-visible"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No consent forms.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
