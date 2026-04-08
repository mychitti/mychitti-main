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

        {{-- Right: Medical History --}}
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
            <div class="card">
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
</div>
@endsection
