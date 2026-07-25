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
            <div class="d-flex gap-2 align-items-center">
                @if(hasPermission('patient_documents', 'list') || hasPermission('patient_documents', 'add'))
                <button onclick="openDocsPanel()" class="btn btn-sm btn-outline-info">
                    <i class="tio-file mr-1"></i> Documents
                    @if($patient->documents->count())
                        <span class="badge badge-soft-info ml-1">{{ $patient->documents->count() }}</span>
                    @endif
                </button>
                @endif
                @if (hasPermission('patient', 'edit'))
                <a href="{{ route('vendor.patient.edit', $patient->id) }}" class="btn btn-sm btn--warning">
                    <i class="tio-edit"></i> Edit
                </a>
                @endif
                <a href="{{ route('vendor.patient.list') }}" class="btn btn-sm btn-soft-secondary">
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

            {{-- Documents inline card --}}
            @if(hasPermission('patient_documents', 'list') || hasPermission('patient_documents', 'add'))
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between py-2">
                    <h6 class="mb-0">
                        <i class="tio-file mr-1"></i> Documents & Prescriptions
                        @if($patient->documents->count())
                            <span class="badge badge-soft-info ml-1">{{ $patient->documents->count() }}</span>
                        @endif
                    </h6>
                    @if(hasPermission('patient_documents', 'add'))
                    <button onclick="openDocsPanel()" class="btn btn-xs btn-outline-info">
                        <i class="tio-upload mr-1"></i> Upload / Manage
                    </button>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if($patient->documents->count())
                    @php
                        $typeLabels = ['id_proof'=>'ID Proof','report'=>'Report','prescription'=>'Prescription','other'=>'Other','arogyasri'=>'Arogya Sri','insurance'=>'Insurance','aadhaar'=>'Aadhaar Card','pan'=>'PAN Card','ration_card'=>'Ration Card','abha'=>'ABHA / Health ID','govt_other'=>'Other Govt'];
                        $typeBg     = ['id_proof'=>'#fef3c7','report'=>'#dbeafe','prescription'=>'#d1fae5','other'=>'#f3f4f6','arogyasri'=>'#fce7f3','insurance'=>'#e0e7ff','aadhaar'=>'#fef9c3','pan'=>'#ccfbf1','ration_card'=>'#ffedd5','abha'=>'#dcfce7','govt_other'=>'#f3f4f6'];
                    @endphp
                    <ul class="list-group list-group-flush mb-0" id="docListInline">
                        @foreach($patient->documents as $doc)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span style="font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:320px;">
                                <i class="tio-file mr-1 text-muted"></i>
                                <span class="badge" style="font-size:11px; background:{{ $typeBg[$doc->document_type] ?? '#f3f4f6' }}; color:#374151; font-weight:600;">
                                    {{ $typeLabels[$doc->document_type] ?? $doc->document_type }}
                                </span>
                                @if($doc->document_name)
                                    <span class="text-muted ml-1" style="font-size:12px;">({{ $doc->document_name }})</span>
                                @endif
                            </span>
                            @if(hasPermission('patient_documents', 'view'))
                            <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-xs btn-soft-primary" style="flex-shrink:0;">
                                <i class="tio-visible"></i>
                            </a>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <div class="text-center text-muted py-4">
                        <i class="tio-file" style="font-size:28px;opacity:.35;display:block;margin-bottom:6px;"></i>
                        No documents yet.
                        @if(hasPermission('patient_documents', 'add'))
                        <br><button onclick="openDocsPanel()" class="btn btn-xs btn-outline-primary mt-2">
                            <i class="tio-add mr-1"></i> Upload documents
                        </button>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif

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
                       Documents & Prescriptions
                        <span class="badge badge-soft-success ml-1">{{ $prescriptions->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-consents">
                        Consent Forms
                        <span class="badge badge-soft-warning ml-1">{{ $consents->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-tests">
                        Tests &amp; Scans
                        <span class="badge badge-soft-danger ml-1">{{ $labOrders->count() + $radiologyStudies->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-bills">
                        Bills
                        <span class="badge badge-soft-dark ml-1">{{ $invoices->count() }}</span>
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
                                                'scheduled'  => 'warning',
                                                'checked_in' => 'info',
                                                'consulting' => 'primary',
                                                'completed'  => 'success',
                                                'cancelled'  => 'danger',
                                                'no_show'    => 'secondary',
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
                    @if (hasPermission('pharmacy_dispense_queue', 'list'))
                        <div class="d-flex justify-content-end mb-2">
                            <a href="{{ route('vendor.prescription.dispense.queue') }}"
                               class="btn btn-xs btn-outline-secondary" title="Open the full dispense queue">
                                <i class="tio-filter-list"></i> Dispense Queue
                            </a>
                        </div>
                    @endif
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
                                        {{-- Only a finalized Rx can be dispensed (dispenseProcess requires it). --}}
                                        @if ($rx->is_finalized && hasPermission('pharmacy_dispense_queue', 'view'))
                                            <a href="{{ route('vendor.prescription.dispense.show', $rx->id) }}"
                                               class="btn btn-xs btn-outline-primary"
                                               title="Dispense this prescription">Dispense</a>
                                        @endif
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
                        @if (hasPermission('consent_form', 'add'))
                        <a href="{{ route('vendor.consent.create', ['patient_id' => $patient->id]) }}"
                           class="btn btn-xs btn--primary">
                            <i class="tio-add"></i> Add Consent
                        </a>
                        @endif
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

                {{-- Tests & Scans --}}
                <div class="tab-pane fade" id="tab-tests">
                    <div class="p-3">

                        {{-- Already raised for this patient --}}
                        <h6 class="font-weight-bold mb-2" style="font-size:13px;">Ordered Tests &amp; Scans</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="thead-light">
                                    <tr><th>Order</th><th>Type</th><th>Tests</th><th>Doctor</th><th>Status</th><th>Ordered</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @forelse($labOrders as $o)
                                    @php $resulted = $o->results->whereNotNull('result_value'); @endphp
                                    <tr>
                                        <td style="font-size:12px;">{{ $o->order_no }}</td>
                                        <td><span class="badge badge-soft-info">Lab</span></td>
                                        <td style="font-size:12px;">{{ $o->items->pluck('test_name')->implode(', ') ?: '—' }}</td>
                                        <td style="font-size:12px;">
                                            {{ $o->doctorProfile?->employee ? trim($o->doctorProfile->employee->f_name . ' ' . $o->doctorProfile->employee->l_name) : '—' }}
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-{{ in_array($o->status, ['verified','sent']) ? 'success' : 'secondary' }}">{{ ucfirst($o->status) }}</span>
                                            @if($o->results->where('is_critical', 1)->isNotEmpty())
                                                <span class="badge badge-soft-danger">Critical</span>
                                            @endif
                                        </td>
                                        <td style="font-size:12px;">{{ $o->created_at?->format('d M Y') }}</td>
                                        <td class="text-right">
                                            @if($resulted->isNotEmpty())
                                                <button type="button" class="btn btn-xs btn-outline-secondary"
                                                        data-toggle="collapse" data-target="#labres-{{ $o->id }}">
                                                    <i class="tio-visible"></i> Results
                                                </button>
                                                <a href="{{ route('vendor.lab.orders.report', $o->id) }}" class="btn btn-xs btn-outline-primary">
                                                    <i class="tio-print"></i>
                                                </a>
                                            @else
                                                <span class="text-muted" style="font-size:11px;">Awaiting result</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($resulted->isNotEmpty())
                                    <tr class="p-0">
                                        <td colspan="7" class="p-0" style="border-top:0;">
                                            <div class="collapse" id="labres-{{ $o->id }}">
                                                <div class="p-3" style="background:#f8fafc;">
                                                    <table class="table table-sm mb-0" style="font-size:12px;">
                                                        <thead><tr><th>Parameter</th><th>Result</th><th>Unit</th><th>Reference</th><th>Flag</th></tr></thead>
                                                        <tbody>
                                                            @foreach($resulted as $r)
                                                            <tr @if($r->is_critical) style="background:#fef2f2;" @endif>
                                                                <td>{{ $r->parameter_name }}</td>
                                                                <td class="font-weight-bold">{{ $r->result_value }}</td>
                                                                <td>{{ $r->unit ?: '—' }}</td>
                                                                <td>{{ $r->ref_range_text ?: trim(($r->normal_low ?? '') . ' – ' . ($r->normal_high ?? '')) ?: '—' }}</td>
                                                                <td>
                                                                    @if($r->is_critical)
                                                                        <span class="badge badge-soft-danger">Critical</span>
                                                                    @elseif($r->result_flag)
                                                                        <span class="badge badge-soft-warning">{{ strtoupper($r->result_flag) }}</span>
                                                                    @else
                                                                        <span class="text-muted">Normal</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                    @if($o->verified_by_name)
                                                        <div class="text-muted mt-2" style="font-size:11px;">
                                                            Verified by {{ $o->verified_by_name }}
                                                            @if($o->reported_at) on {{ $o->reported_at->format('d M Y, h:i a') }} @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                    @empty
                                    @endforelse

                                    @forelse($radiologyStudies as $s)
                                    @php $reported = $s->findings || $s->impression; @endphp
                                    <tr>
                                        <td style="font-size:12px;">{{ $s->study_no }}</td>
                                        <td><span class="badge badge-soft-warning">Radiology</span></td>
                                        <td style="font-size:12px;">{{ $s->study_name }} @if($s->modality)<span class="text-muted">· {{ $s->modality }}</span>@endif</td>
                                        <td style="font-size:12px;">
                                            {{ $s->doctorProfile?->employee ? trim($s->doctorProfile->employee->f_name . ' ' . $s->doctorProfile->employee->l_name) : '—' }}
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-{{ in_array($s->status, ['verified','sent']) ? 'success' : 'secondary' }}">{{ ucfirst($s->status) }}</span>
                                            @if($s->is_critical)<span class="badge badge-soft-danger">Critical</span>@endif
                                        </td>
                                        <td style="font-size:12px;">{{ $s->created_at?->format('d M Y') }}</td>
                                        <td class="text-right">
                                            @if($reported)
                                                <button type="button" class="btn btn-xs btn-outline-secondary"
                                                        data-toggle="collapse" data-target="#radres-{{ $s->id }}">
                                                    <i class="tio-visible"></i> Report
                                                </button>
                                                <a href="{{ route('vendor.radiology.studies.print', $s->id) }}" class="btn btn-xs btn-outline-primary">
                                                    <i class="tio-print"></i>
                                                </a>
                                            @else
                                                <span class="text-muted" style="font-size:11px;">Awaiting report</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($reported)
                                    <tr class="p-0">
                                        <td colspan="7" class="p-0" style="border-top:0;">
                                            <div class="collapse" id="radres-{{ $s->id }}">
                                                <div class="p-3" style="background:#f8fafc; font-size:12px;">
                                                    @if($s->findings)
                                                        <div class="mb-2"><span class="font-weight-bold">Findings:</span>
                                                            <div style="white-space:pre-wrap;">{{ $s->findings }}</div>
                                                        </div>
                                                    @endif
                                                    @if($s->impression)
                                                        <div class="mb-2"><span class="font-weight-bold">Impression:</span>
                                                            <div style="white-space:pre-wrap;">{{ $s->impression }}</div>
                                                        </div>
                                                    @endif
                                                    @if($s->recommendations)
                                                        <div class="mb-2"><span class="font-weight-bold">Recommendations:</span>
                                                            <div style="white-space:pre-wrap;">{{ $s->recommendations }}</div>
                                                        </div>
                                                    @endif
                                                    @if($s->radiologist)
                                                        <div class="text-muted" style="font-size:11px;">
                                                            Reported by {{ $s->radiologist }}
                                                            @if($s->reported_at) on {{ $s->reported_at->format('d M Y, h:i a') }} @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                    @empty
                                    @endforelse

                                    @if($labOrders->isEmpty() && $radiologyStudies->isEmpty())
                                    <tr><td colspan="7" class="text-center text-muted py-3">Nothing ordered for this patient yet.</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        {{-- Order new --}}
                        <h6 class="font-weight-bold mb-2" style="font-size:13px;">Order Tests &amp; Scans</h6>

                        @if($labTests->isEmpty() && $radiologyTests->isEmpty())
                            <div class="alert py-2 px-3" style="background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:12.5px;">
                                No tests are set up yet. Add them in
                                <a href="{{ route('vendor.lab.catalog') }}">Laboratory → Test Catalog</a> or
                                <a href="{{ route('vendor.radiology.catalog') }}">Radiology → Catalog</a>, and they will appear here.
                            </div>
                        @else
                        <form action="{{ route('vendor.patient.order-tests', $patient->id) }}" method="post">
                            @csrf
                            <div class="row">
                                {{-- Lab catalog --}}
                                <div class="col-md-6 mb-3">
                                    <div class="font-weight-bold mb-2" style="font-size:12.5px; color:#0f172a;">
                                        <i class="tio-labs"></i> Lab Tests
                                    </div>
                                    @forelse($labTests as $department => $tests)
                                        <div class="mb-2">
                                            <div class="text-muted mb-1" style="font-size:11px; text-transform:uppercase;">{{ $department }}</div>
                                            @foreach($tests as $t)
                                                <label class="d-flex align-items-center mb-1" style="font-size:12.5px;">
                                                    <input type="checkbox" name="lab_tests[]" value="{{ $t->id }}" class="mr-2">
                                                    {{ $t->name }}
                                                    <span class="text-muted ml-1">
                                                        @if($t->price > 0) · {{ \App\CentralLogics\Helpers::format_currency($t->price) }} @endif
                                                        @if($t->sample_type) · {{ $t->sample_type }} @endif
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @empty
                                        <div class="text-muted" style="font-size:12px;">No lab tests in the catalog.</div>
                                    @endforelse
                                </div>

                                {{-- Radiology catalog --}}
                                <div class="col-md-6 mb-3">
                                    <div class="font-weight-bold mb-2" style="font-size:12.5px; color:#0f172a;">
                                        <i class="tio-heart-outlined"></i> Radiology Scans
                                    </div>
                                    @forelse($radiologyTests as $modality => $tests)
                                        <div class="mb-2">
                                            <div class="text-muted mb-1" style="font-size:11px; text-transform:uppercase;">{{ $modality }}</div>
                                            @foreach($tests as $t)
                                                <label class="d-flex align-items-center mb-1" style="font-size:12.5px;">
                                                    <input type="checkbox" name="radiology_tests[]" value="{{ $t->id }}" class="mr-2">
                                                    {{ $t->name }}
                                                    <span class="text-muted ml-1">
                                                        @if($t->price > 0) · {{ \App\CentralLogics\Helpers::format_currency($t->price) }} @endif
                                                        @if($t->body_part) · {{ $t->body_part }} @endif
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @empty
                                        <div class="text-muted" style="font-size:12px;">No radiology scans in the catalog.</div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label style="font-size:12px;">Referring Doctor</label>
                                    <select name="doctor_profile_id" class="form-control form-control-sm">
                                        <option value="">— Select —</option>
                                        @foreach($doctors as $d)
                                            <option value="{{ $d->id }}">
                                                {{ $d->employee ? trim($d->employee->f_name . ' ' . $d->employee->l_name) : ('Doctor #' . $d->id) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label style="font-size:12px;">Priority</label>
                                    <select name="priority" class="form-control form-control-sm">
                                        <option value="routine">Routine</option>
                                        <option value="urgent">Urgent</option>
                                        <option value="stat">STAT</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label style="font-size:12px;">Clinical Notes</label>
                                    <input type="text" name="clinical_notes" class="form-control form-control-sm"
                                           placeholder="Indication / provisional diagnosis (optional)">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-sm btn--primary">
                                <i class="tio-checkmark-circle"></i> Order Selected
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

                {{-- Bills --}}
                <div class="tab-pane fade" id="tab-bills">
                    <div class="px-3 pt-3">
                        <div class="d-flex mb-3" style="gap:24px;">
                            <div>
                                <div class="text-muted" style="font-size:11px; text-transform:uppercase;">Total Billed</div>
                                <div class="font-weight-bold" style="font-size:18px;">
                                    {{ \App\CentralLogics\Helpers::format_currency($invoiceTotals['billed']) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size:11px; text-transform:uppercase;">Outstanding</div>
                                <div class="font-weight-bold" style="font-size:18px; color:{{ $invoiceTotals['due'] > 0 ? '#b91c1c' : '#15803d' }};">
                                    {{ \App\CentralLogics\Helpers::format_currency($invoiceTotals['due']) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr><th>Invoice</th><th>Date</th><th>Items</th><th>Amount</th><th>Payment</th><th>Status</th><th></th></tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $inv)
                                <tr>
                                    <td style="font-size:12px;">{{ $inv->invoice_id }}</td>
                                    <td style="font-size:12px;">{{ $inv->invoice_date ?: $inv->created_at?->format('Y-m-d') }}</td>
                                    <td style="font-size:12px;">{{ $inv->invoice_items_count }}</td>
                                    <td style="font-size:12px;">{{ \App\CentralLogics\Helpers::format_currency($inv->total_amount) }}</td>
                                    <td style="font-size:12px;">{{ $inv->payment_method ?: '—' }}</td>
                                    <td>
                                        <span class="badge badge-soft-{{ $inv->payment_status === 'Paid' ? 'success' : 'danger' }}">
                                            {{ $inv->payment_status ?: 'Unpaid' }}
                                        </span>
                                    </td>
                                    <td>
                                        {{-- Route name prefix is invoice., though the URI reads billing/veiw-invoice --}}
                                        <a href="{{ route('vendor.invoice.view-invoice', $inv->invoice_id) }}"
                                           class="btn btn-xs btn-outline-primary">
                                            <i class="tio-visible"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted py-3">No bills raised for this patient yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
{{-- ── Floating Documents Panel ───────────────────────────────────────────────── --}}
<div id="docsModalDialog" style="display:none; position:fixed; top:80px; left:50%; transform:translateX(-50%);
     width:520px; max-width:95vw; background:#fff; border-radius:10px; box-shadow:0 8px 32px rgba(0,0,0,.22);
     overflow:hidden; z-index:9999;">

    <div id="docsModalHandle" title="Drag to move"
         style="background:#f8fafc; border-bottom:1px solid #e5e7eb;
         padding:10px 16px; display:flex; align-items:center; justify-content:space-between; cursor:grab; user-select:none;">
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="display:inline-grid; grid-template-columns:repeat(2,4px); gap:3px; opacity:.4; flex-shrink:0;">
                <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
                <span style="width:4px;height:4px;border-radius:50%;background:#374151;"></span>
            </span>
            <span style="font-weight:600; font-size:14px;">
                <i class="tio-file mr-1"></i>
                Patient Documents
                @if($patient->documents->count())
                    <span class="badge badge-soft-info ml-1" id="docBadgeCount">{{ $patient->documents->count() }}</span>
                @endif
            </span>
        </div>
        <button onclick="closeDocsModal()" style="background:none;border:none;font-size:18px;line-height:1;cursor:pointer;color:#6b7280;">&times;</button>
    </div>

    @php
        // type => [label, badge background, category(medical|govt)]
        $docTypeMeta = [
            'report'       => ['Report', '#dbeafe', 'medical'],
            'id_proof'     => ['ID Proof', '#fef3c7', 'medical'],
            'prescription' => ['Prescription', '#d1fae5', 'medical'],
            'other'        => ['Other', '#f3f4f6', 'medical'],
            'arogyasri'    => ['Arogya Sri', '#fce7f3', 'govt'],
            'insurance'    => ['Insurance', '#e0e7ff', 'govt'],
            'aadhaar'      => ['Aadhaar Card', '#fef9c3', 'govt'],
            'pan'          => ['PAN Card', '#ccfbf1', 'govt'],
            'ration_card'  => ['Ration Card', '#ffedd5', 'govt'],
            'abha'         => ['ABHA / Health ID', '#dcfce7', 'govt'],
            'govt_other'   => ['Other Govt', '#f3f4f6', 'govt'],
        ];
        $catOf       = fn($type) => $docTypeMeta[$type][2] ?? 'medical';
        $medicalDocs = $patient->documents->filter(fn($d) => $catOf($d->document_type) === 'medical');
        $govtDocs    = $patient->documents->filter(fn($d) => $catOf($d->document_type) === 'govt');
    @endphp

    {{-- Tabs --}}
    <ul class="nav nav-tabs nav-tabs-line px-3 pt-2" id="docCatTabs" style="font-size:13px;">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#docTab-medical">
                <i class="tio-file mr-1"></i> Medical
                <span class="badge badge-soft-secondary ml-1" id="medDocCount">{{ $medicalDocs->count() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#docTab-govt">
                <i class="tio-shield-outlined mr-1"></i> Government
                <span class="badge badge-soft-secondary ml-1" id="govtDocCount">{{ $govtDocs->count() }}</span>
            </a>
        </li>
    </ul>

    <div class="tab-content">
        {{-- Medical documents --}}
        <div class="tab-pane fade show active" id="docTab-medical">
            @include('hmis::vendor.patient._docs_pane', [
                'cat' => 'med', 'list_id' => 'docList', 'docs' => $medicalDocs,
                'types' => ['report','id_proof','prescription','other'],
                'placeholder' => 'e.g. X-ray', 'meta' => $docTypeMeta,
            ])
        </div>
        {{-- Government documents --}}
        <div class="tab-pane fade" id="docTab-govt">
            @include('hmis::vendor.patient._docs_pane', [
                'cat' => 'govt', 'list_id' => 'govtDocList', 'docs' => $govtDocs,
                'types' => ['arogyasri','insurance','aadhaar','pan','ration_card','abha','govt_other'],
                'placeholder' => 'e.g. policy no.', 'meta' => $docTypeMeta,
            ])
        </div>
    </div>
</div>

@push('script_2')
<script>
const docUploadUrl     = "{{ route('vendor.patient.upload-documents', $patient->id) }}";
const docDeleteUrlTpl  = "{{ route('vendor.patient.delete-document', ['id' => $patient->id, 'docId' => '__DOC__']) }}";
const csrfToken        = "{{ csrf_token() }}";

// type => [label, badge color, category]
const DOC_TYPE_META = {
    report:       ['Report', '#dbeafe', 'med'],
    id_proof:     ['ID Proof', '#fef3c7', 'med'],
    prescription: ['Prescription', '#d1fae5', 'med'],
    other:        ['Other', '#f3f4f6', 'med'],
    arogyasri:    ['Arogya Sri', '#fce7f3', 'govt'],
    insurance:    ['Insurance', '#e0e7ff', 'govt'],
    aadhaar:      ['Aadhaar Card', '#fef9c3', 'govt'],
    pan:          ['PAN Card', '#ccfbf1', 'govt'],
    ration_card:  ['Ration Card', '#ffedd5', 'govt'],
    abha:         ['ABHA / Health ID', '#dcfce7', 'govt'],
    govt_other:   ['Other Govt', '#f3f4f6', 'govt'],
};

(function() {
    const panel  = document.getElementById('docsModalDialog');
    const handle = document.getElementById('docsModalHandle');
    let dragging = false, ox = 0, oy = 0;
    handle.addEventListener('mousedown', function(e) {
        dragging = true; handle.style.cursor = 'grabbing';
        const r = panel.getBoundingClientRect();
        if (panel.style.transform) { panel.style.left = r.left + 'px'; panel.style.transform = 'none'; }
        ox = e.clientX - r.left; oy = e.clientY - r.top; e.preventDefault();
    });
    document.addEventListener('mousemove', function(e) {
        if (!dragging) return;
        panel.style.left = (e.clientX - ox) + 'px'; panel.style.top = (e.clientY - oy) + 'px';
    });
    document.addEventListener('mouseup', function() { dragging = false; handle.style.cursor = 'grab'; });
})();

function openDocsPanel() {
    const panel = document.getElementById('docsModalDialog');
    panel.style.left = '50%'; panel.style.top = '80px'; panel.style.transform = 'translateX(-50%)';
    panel.style.display = '';
}
function closeDocsModal() { document.getElementById('docsModalDialog').style.display = 'none'; }

function toggleUploadForm(cat) {
    const form = document.getElementById(cat + 'UploadForm');
    const btn  = document.getElementById(cat + 'ToggleBtn');
    if (!form || !btn) return;
    const open = form.style.display === 'none';
    form.style.display = open ? '' : 'none';
    btn.innerHTML = open ? '<i class="tio-close mr-1"></i> Cancel' : '<i class="tio-add-circle mr-1"></i> Upload documents';
}

function updateDocBadge(total) {
    const handle = document.getElementById('docsModalHandle');
    let badge = document.getElementById('docBadgeCount');
    if (total > 0) {
        if (!badge) {
            handle.querySelector('span[style*="font-weight"]').insertAdjacentHTML('beforeend',
                '<span class="badge badge-soft-info ml-1" id="docBadgeCount">' + total + '</span>');
        } else { badge.textContent = total; }
    } else if (badge) { badge.remove(); }
}

// Recompute per-tab counts, the header badge, and restore empty-state placeholders.
function refreshDocCounts() {
    let total = 0;
    [['med', 'docList', 'medDocCount'], ['govt', 'govtDocList', 'govtDocCount']].forEach(([cat, listId, countId]) => {
        const list = document.getElementById(listId);
        if (!list) return;
        const n = list.querySelectorAll('.doc-item').length;
        total += n;
        const countEl = document.getElementById(countId);
        if (countEl) countEl.textContent = n;
        const empty = list.querySelector('.doc-empty-state');
        if (n === 0 && !empty) {
            list.insertAdjacentHTML('beforeend',
                `<li class="list-group-item text-center text-muted py-4 px-0 doc-empty-state">
                    <i class="tio-file" style="font-size:28px;opacity:.35;display:block;margin-bottom:6px;"></i>
                    No documents yet.
                </li>`);
        } else if (n > 0 && empty) {
            empty.remove();
        }
    });
    updateDocBadge(total);
}

function uploadDocs(cat) {
    const input    = document.getElementById(cat + 'FileInput');
    const type     = document.getElementById(cat + 'TypeSelect').value;
    const name     = document.getElementById(cat + 'NameInput').value.trim();
    const btn      = document.getElementById(cat + 'UploadBtn');
    const errEl    = document.getElementById(cat + 'UploadErr');
    const progress = document.getElementById(cat + 'UploadProgress');

    errEl.style.display = 'none';
    if (!input.files.length) { errEl.textContent = 'Please select at least one file.'; errEl.style.display = ''; return; }

    const form = new FormData();
    form.append('document_type', type);
    if (name) form.append('document_name', name);
    Array.from(input.files).forEach(f => form.append('files[]', f));

    btn.disabled = true; progress.style.display = '';

    fetch(docUploadUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
        body: form
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) throw new Error(data.message || 'Upload failed');

        data.documents.forEach(doc => {
            const meta     = DOC_TYPE_META[doc.document_type] || [doc.document_type, '#f3f4f6', 'med'];
            const list     = document.getElementById(meta[2] === 'govt' ? 'govtDocList' : 'docList');
            if (!list) return;
            const empty    = list.querySelector('.doc-empty-state');
            if (empty) empty.remove();
            const namePart = doc.document_name ? `<span class="text-muted ml-1" style="font-size:12px;">(${doc.document_name})</span>` : '';
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center px-0 py-2 doc-item';
            li.dataset.id = doc.id;
            li.innerHTML = `
                <span style="font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:300px;">
                    <i class="tio-file mr-1 text-muted"></i>
                    <span class="badge" style="font-size:11px;background:${meta[1]};color:#374151;font-weight:600;">${meta[0]}</span>
                    ${namePart}
                </span>
                <div class="d-flex gap-1" style="flex-shrink:0;">
                    <a href="${doc.url}" target="_blank" class="btn btn-xs btn-soft-primary"><i class="tio-visible"></i></a>
                    <button class="btn btn-xs btn-soft-danger" onclick="deleteDoc(${doc.id}, this)" title="Delete"><i class="tio-delete"></i></button>
                </div>`;
            list.appendChild(li);
        });

        refreshDocCounts();
        input.value = ''; document.getElementById(cat + 'NameInput').value = '';
        toggleUploadForm(cat);
    })
    .catch(err => { errEl.textContent = err.message; errEl.style.display = ''; })
    .finally(() => { btn.disabled = false; progress.style.display = 'none'; });
}

function deleteDoc(docId, btn) {
    if (!confirm('Delete this document?')) return;
    btn.disabled = true;
    fetch(docDeleteUrlTpl.replace('__DOC__', docId), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) throw new Error();
        btn.closest('.doc-item').remove();
        refreshDocCounts();
    })
    .catch(() => { btn.disabled = false; alert('Failed to delete document.'); });
}
</script>
@endpush

@endsection
