@extends('layouts.vendor.app')
@section('title', 'IPD — ' . $admission->admission_number)

@section('content')
    <div class="content container-fluid">
        @include('hmis::vendor.hospital._hospital_submenu_header')
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-hospital" style="font-size:22px;"></i></span>
                IPD Admission
                <small class="text-muted font-size-14 ml-2">{{ $admission->admission_number }}</small>
            </h1> 
            <div class="d-flex gap-2">
                @if (hasPermission('ipd_admission', 'generate_bill'))
                    <a href="{{ route('vendor.hospital-bill.create-ipd', $admission->id) }}"
                        class="btn btn-sm btn-outline-success">
                        <i class="tio-receipt"></i> Generate Bill
                    </a>
                @endif
                @if ($admission->status === 'admitted' && hasPermission('ipd_admission', 'discharge'))
                    <a href="{{ route('vendor.ipd.discharge-form', $admission->id) }}"
                        class="btn btn-sm btn-outline-warning">
                        <i class="tio-hospital"></i> Discharge
                    </a>
                @endif
                <a href="{{ route('vendor.ipd.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="tio-arrow-backward"></i> Back
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- Status banner --}}
        @if ($admission->status === 'admitted')
            @php $days = $admission->admission_date?->diffInDays(now()); @endphp
            <div class="alert alert-success d-flex align-items-center gap-3">
                <i class="tio-checkmark-circle" style="font-size:20px;"></i>
                <div>
                    <strong>Currently Admitted</strong> — Day {{ $days + 1 }} of admission.
                    Daily charge: <strong>₹{{ number_format($admission->daily_charge, 2) }}</strong>
                    | Estimated bill: <strong>₹{{ number_format(($days + 1) * $admission->daily_charge, 2) }}</strong>
                </div>
            </div>
        @else
            <div class="alert alert-secondary d-flex align-items-center gap-3">
                <i class="tio-checkmark-circle-outlined" style="font-size:20px;"></i>
                <div>
                    <strong>Discharged</strong> on {{ $admission->discharge_date?->format('d M Y') }}
                    ({{ \App\Models\IpdAdmission::DISCHARGE_TYPES[$admission->discharge_type] ?? $admission->discharge_type }})
                </div>
            </div>
        @endif

        <div class="row">
            {{-- Left column: patient + bed --}}
            <div class="col-md-5">
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0">Patient</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted" style="width:45%;">Name</td>
                                    <td>
                                        <strong>
                                            <a href="{{ route('vendor.patient.show', $admission->patient_id) }}">
                                                {{ $admission->patient?->name }}
                                            </a>
                                        </strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">MUID</td>
                                    <td>{{ $admission->patient?->patient_uid }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Phone</td>
                                    <td>{{ $admission->patient?->phone ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Blood Group</td>
                                    <td>{{ $admission->patient?->blood_group ?: '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0">Bed &amp; Ward</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted" style="width:45%;">Ward</td>
                                    <td><strong>{{ $admission->ward?->ward_name }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Ward Type</td>
                                    <td>{{ \App\Models\Ward::TYPES[$admission->ward?->ward_type] ?? $admission->ward?->ward_type }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Bed Number</td>
                                    <td><strong>{{ $admission->bed?->bed_number ?: '—' }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Floor</td>
                                    <td>{{ $admission->ward?->floor ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Daily Charge</td>
                                    <td><strong>₹{{ number_format($admission->daily_charge, 2) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Right column: clinical --}}
            <div class="col-md-7">
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0">Clinical Details</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted" style="width:40%;">Attending Doctor</td>
                                    <td>Dr. {{ $admission->doctorProfile?->employee?->f_name }}
                                        {{ $admission->doctorProfile?->employee?->l_name }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Admission Date</td>
                                    <td>{{ $admission->admission_date?->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Diagnosis</td>
                                    <td>{{ $admission->diagnosis ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Reason</td>
                                    <td>{{ $admission->admission_reason ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Admitted By</td>
                                    <td>{{ $admission->admittedBy?->f_name . ' ' . $admission->admittedBy?->l_name ?: '—' }}
                                    </td>
                                </tr>
                                @if ($admission->status === 'discharged')
                                    <tr>
                                        <td class="text-muted">Discharge Date</td>
                                        <td>{{ $admission->discharge_date?->format('d M Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Discharge Type</td>
                                        <td>{{ \App\Models\IpdAdmission::DISCHARGE_TYPES[$admission->discharge_type] ?? $admission->discharge_type }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Discharged By</td>
                                        <td>{{ $admission->dischargedBy?->f_name . ' ' . $admission->dischargedBy?->l_name ?: '—' }}
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($admission->discharge_summary)
                    <div class="card mb-3">
                        <div class="card-header py-2">
                            <h6 class="mb-0">Discharge Summary</h6>
                        </div>
                        <div class="card-body" style="white-space:pre-wrap; font-size:13px;">
                            {{ $admission->discharge_summary }}</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="d-flex gap-2 flex-wrap mb-4">
            <a href="{{ route('vendor.patient.show', $admission->patient_id) }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-user"></i> Patient Profile
            </a>
            @if (hasPermission('prescription', 'add'))
                <a href="{{ route('vendor.prescription.create', ['patient_id' => $admission->patient_id, 'doctor_profile_id' => $admission->doctor_profile_id]) }}"
                    class="btn btn-sm btn-outline-primary">
                    <i class="tio-file-text"></i> Write Prescription
                </a>
            @endif
        </div>

        {{-- ── Consent Forms ──────────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center" style="cursor:pointer;"
                data-toggle="collapse" data-target="#consentPanel">
                <h6 class="mb-0"><i class="tio-document-text mr-2"></i>Consent Forms
                    <span class="badge badge-soft-warning ml-1">{{ $consents->count() }}</span>
                </h6>
                <div class="d-flex align-items-center gap-2">
                    @if (hasPermission('ipd_admission', 'consent'))
                        <a href="{{ route('vendor.consent.create', ['admission_id' => $admission->id]) }}"
                            class="btn btn-xs btn--primary" onclick="event.stopPropagation()">
                            <i class="tio-add"></i> Add Consent
                        </a>
                    @endif
                    <i class="tio-chevron-down"></i>
                </div>
            </div>
            <div id="consentPanel" class="collapse show">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Title</th>
                                <th style="width:160px;">Signed By</th>
                                <th style="width:160px;">Witness</th>
                                <th style="width:160px;">Signed At</th>
                                <th style="width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($consents as $consent)
                                <tr>
                                    <td>
                                        <a href="{{ route('vendor.consent.show', $consent->id) }}">
                                            {{ $consent->title }}
                                        </a>
                                    </td>
                                    <td style="font-size:12px;">{{ $consent->signatory_name ?: '—' }}</td>
                                    <td style="font-size:12px;">{{ $consent->witness_name ?: '—' }}</td>
                                    <td style="font-size:12px;">{{ $consent->signed_at?->format('d M Y H:i') }}</td>
                                    <td>
                                        @if (hasPermission('consent_form', 'view'))
                                            <a href="{{ route('vendor.consent.show', $consent->id) }}"
                                                class="btn btn-xs btn-outline-primary mr-1">
                                                <i class="tio-visible"></i>
                                            </a>
                                        @endif
                                        @if (hasPermission('consent_form', 'delete'))
                                            <form action="{{ route('vendor.consent.destroy', $consent->id) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Delete consent?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-outline-danger">
                                                    <i class="tio-delete-outlined"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No consent forms recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @if (hasPermission('nursing_notes', 'list') || hasPermission('nursing_notes', 'add'))
            {{-- ── Nursing Notes ──────────────────────────────────────────────── --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center" style="cursor:pointer;"
                    data-toggle="collapse" data-target="#nursingNotesPanel">
                    <h6 class="mb-0"><i class="tio-document-text mr-2"></i>Nursing Notes
                        <span class="badge badge-soft-primary ml-1">{{ $nursingNotes->count() }}</span>
                    </h6>
                    <i class="tio-chevron-down"></i>
                </div>
                <div id="nursingNotesPanel" class="collapse show">
                    @if (hasPermission('nursing_notes', 'add') && $admission->status === 'admitted')
                        <div class="card-body border-bottom pb-3">
                            <form action="{{ route('vendor.ipd.nursing-note.store', $admission->id) }}" method="POST">
                                @csrf
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <label class="input-label mb-1" style="font-size:12px;">Note Type</label>
                                        <select name="note_type" class="form-control form-control-sm">
                                            @foreach (\App\Models\NursingNote::TYPES as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="input-label mb-1" style="font-size:12px;">Date &amp; Time</label>
                                        <input type="datetime-local" name="recorded_at"
                                            class="form-control form-control-sm"
                                            value="{{ now()->format('Y-m-d\TH:i') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="input-label mb-1" style="font-size:12px;">Note</label>
                                        <textarea name="note" class="form-control form-control-sm" rows="1" placeholder="Enter nursing note..."
                                            required></textarea>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn--primary btn-sm w-100">
                                            <i class="tio-add"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif
                    @if (hasPermission('nursing_notes', 'list'))
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width:160px;">Date &amp; Time</th>
                                        <th style="width:140px;">Type</th>
                                        <th>Note</th>
                                        <th style="width:130px;">Recorded By</th>
                                        <th style="width:60px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($nursingNotes as $note)
                                        <tr>
                                            <td>{{ $note->recorded_at?->format('d M Y H:i') }}</td>
                                            <td>
                                                <span class="badge badge-soft-secondary">
                                                    {{ \App\Models\NursingNote::TYPES[$note->note_type] ?? $note->note_type }}
                                                </span>
                                            </td>
                                            <td style="white-space:pre-wrap;">{{ $note->note }}</td>
                                            <td style="font-size:12px;">{{ $note->recorder?->f_name }}
                                                {{ $note->recorder?->l_name }}</td>
                                            <td>
                                             @if (hasPermission('nursing_notes', 'delete'))
                                                <form
                                                    action="{{ route('vendor.ipd.nursing-note.destroy', [$admission->id, $note->id]) }}"
                                                    method="POST" onsubmit="return confirm('Delete this note?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-xs btn-outline-danger">
                                                        <i class="tio-delete-outlined"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">No nursing notes
                                                recorded.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif
                @if (hasPermission('diet_chart', 'list') || hasPermission('diet_chart', 'add'))

        {{-- ── Diet Chart ──────────────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center" style="cursor:pointer;"
                data-toggle="collapse" data-target="#dietPanel">
                <h6 class="mb-0"><i class="tio-restaurant mr-2"></i>Diet Chart
                    <span class="badge badge-soft-success ml-1">{{ $dietCharts->count() }}</span>
                </h6>
                <i class="tio-chevron-down"></i>
            </div>
            <div id="dietPanel" class="collapse show">
                @if (hasPermission('diet_chart', 'add') && $admission->status === 'admitted')
                    <div class="card-body border-bottom pb-3">
                        <form action="{{ route('vendor.ipd.diet.store', $admission->id) }}" method="POST">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-2">
                                    <label class="input-label mb-1" style="font-size:12px;">Date</label>
                                    <input type="date" name="date" class="form-control form-control-sm"
                                        value="{{ now()->toDateString() }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="input-label mb-1" style="font-size:12px;">Meal</label>
                                    <select name="meal_type" class="form-control form-control-sm">
                                        @foreach (\App\Models\DietChart::MEAL_TYPES as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="input-label mb-1" style="font-size:12px;">Diet Type</label>
                                    <select name="diet_type" class="form-control form-control-sm">
                                        @foreach (\App\Models\DietChart::DIET_TYPES as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="input-label mb-1" style="font-size:12px;">Instructions</label>
                                    <input type="text" name="instructions" class="form-control form-control-sm"
                                        placeholder="e.g. No spicy food, 1500 kcal...">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn--primary btn-sm w-100">
                                        <i class="tio-add"></i> Add
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endif
 @if (hasPermission('diet_chart', 'list'))
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:120px;">Date</th>
                                <th style="width:120px;">Meal</th>
                                <th style="width:160px;">Diet Type</th>
                                <th>Instructions</th>
                                <th style="width:130px;">Recorded By</th>
                                <th style="width:60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dietCharts as $diet)
                                <tr>
                                    <td>{{ $diet->date?->format('d M Y') }}</td>
                                    <td>{{ \App\Models\DietChart::MEAL_TYPES[$diet->meal_type] ?? $diet->meal_type }}</td>
                                    <td>
                                        <span class="badge badge-soft-success">
                                            {{ \App\Models\DietChart::DIET_TYPES[$diet->diet_type] ?? $diet->diet_type }}
                                        </span>
                                    </td>
                                    <td>{{ $diet->instructions ?: '—' }}</td>
                                    <td style="font-size:12px;">{{ $diet->recorder?->f_name }}
                                        {{ $diet->recorder?->l_name }}</td>
                                    <td>
                                     @if (hasPermission('diet_chart', 'delete'))
                                        <form action="{{ route('vendor.ipd.diet.destroy', [$admission->id, $diet->id]) }}"
                                            method="POST" onsubmit="return confirm('Delete this diet entry?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-outline-danger">
                                                <i class="tio-delete-outlined"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">No diet entries recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
@endsection
