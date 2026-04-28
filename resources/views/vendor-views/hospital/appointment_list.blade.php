@extends('layouts.vendor.app')

@section('title', $type . ' Appointments')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .appt-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            padding: 14px;
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 9px;
            position: relative;
            overflow: hidden;
            transition: box-shadow .2s, transform .15s;
        }
        .appt-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
            border-radius: 14px 0 0 14px;
        }
        .appt-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.1); transform: translateY(-1px); }

        .appt-new::before                       { background: #3b82f6; }
        .appt-accepted::before                  { background: #f59e0b; }
        .appt-confirmed::before                 { background: #10b981; }
        .appt-completed::before                 { background: #6b7280; }
        .appt-cancelled::before                 { background: #ef4444; }
        .appt-missed::before                    { background: #ef4444; }
        .appt-assigned::before                  { background: #f59e0b; }
        .appt-unassigned::before                { background: #fbbf24; }
        .appt-alotted::before                   { background: #f59e0b; }
        .appt-confirmation_request_sent::before { background: #a855f7; }

        .appt-new .appt-status-dot                       { background: #3b82f6; }
        .appt-accepted .appt-status-dot                  { background: #f59e0b; }
        .appt-confirmed .appt-status-dot                 { background: #10b981; }
        .appt-completed .appt-status-dot                 { background: #6b7280; }
        .appt-cancelled .appt-status-dot                 { background: #ef4444; }
        .appt-missed .appt-status-dot                    { background: #ef4444; }
        .appt-assigned .appt-status-dot                  { background: #f59e0b; }
        .appt-unassigned .appt-status-dot                { background: #fbbf24; }
        .appt-confirmation_request_sent .appt-status-dot { background: #a855f7; }

        .appt-card__top {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 600;
        }
        .appt-status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .appt-status-label { text-transform: capitalize; color: #374151; }

        .appt-card__service {
            background: #eff6ff; color: #1d4ed8;
            border-radius: 8px; padding: 9px 11px;
            font-size: 13px; font-weight: 700;
            display: flex; align-items: center; gap: 7px;
        }

        .appt-card__doctor {
            background: #f0fdf4;
            border-radius: 8px; padding: 8px 11px;
            font-size: 12px;
        }
        .appt-doctor-name {
            font-weight: 700; color: #15803d;
            display: flex; align-items: center; gap: 5px;
        }
        .appt-doctor-spec { color: #6b7280; font-size: 11px; margin-top: 2px; padding-left: 18px; }

        .appt-card__timing {
            display: flex; flex-wrap: wrap; gap: 5px;
            font-size: 11px; color: #374151;
        }
        .appt-card__timing span {
            display: flex; align-items: center; gap: 4px;
            background: #f3f4f6; padding: 3px 9px;
            border-radius: 20px; white-space: nowrap;
        }

        .appt-divider { margin: 2px 0; border-color: #f3f4f6; }

        .appt-card__patient {
            font-size: 12px; color: #374151;
            display: flex; flex-direction: column; gap: 4px;
            flex: 1;
        }
        .appt-card__patient-locked {
            color: #9ca3af; font-size: 11px;
            display: flex; align-items: center; gap: 5px;
        }

        .appt-card__actions {
            display: flex; gap: 6px; flex-wrap: wrap;
            align-items: center; margin-top: auto;
            padding-top: 8px; border-top: 1px solid #f3f4f6;
        }

        .badge-missed  { background: #ff442c; color: white; }
        .badge-completed { background: #518f00; color: white; }
        .badge-cancelled { background: #fb8c7b; color: white; }
        .badge-new     { background: #d483ff; color: white; }
        .badge-confirmed { background: #4492ff; color: white; }
        .badge-assigned  { background: #e17b14; color: white; }
        .badge-confirmation_request_sent { background: #a855f7; color: white; }

        .stat-strip { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 1rem; }
        .stat-chip {
            background: #fff; border: 1px solid #e5e7eb;
            border-radius: 20px; padding: 4px 14px;
            font-size: 12px; font-weight: 600; color: #374151;
            display: flex; align-items: center; gap: 6px;
        }
        .stat-chip .dot { width: 8px; height: 8px; border-radius: 50%; }

        .btn-accept { background: #4caf50; color: white; padding: 2px 10px; }
        .btn-accept:hover { background: #45a049; color: white; }
        .btn_sm { padding: 2px 5px; font-size: 12px; }

        .dropdown-toggle:not(.dropdown-toggle-empty)::after { display: none !important; }
        .dropdown-menu.show {     left: -156px !important; }
    </style>
@endpush

@section('content') 
<div class="content container-fluid">

    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <i class="tio-hospital" style="font-size:20px;"></i>
            {{ $type ?: 'All' }} Appointments
            <span class="badge badge-soft-dark ml-2">{{ count($product) }}</span>
        </h1>
        <div class="d-flex gap-2 align-items-center">
            <button onclick="window.location.reload()" class="btn btn-outline-secondary btn-sm" title="Refresh">
                <i class="tio-refresh"></i>
            </button>
            @if(hasPermission('leads_manage', 'statuses') && !$empId)
                <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#sttsMOdal">
                    <i class="tio-settings"></i> Statuses
                </button>
            @endif
            {{-- @if(hasPermission('leads_manage', 'export'))
                <a href="{{ route('vendor.service.leads_list', [0, 'export']) . '?' . http_build_query(array_filter(['search' => request('search'), 'type' => request('type'), 'date_range' => request('date_range'), 'custom_date_range' => request('custom_date_range')])) }}"
                    class="btn btn-outline-success btn-sm">
                    <i class="tio-download"></i> Export
                </a>
            @endif --}}
        </div>
    </div>

    {{-- Filters --}}
    @if(!$empId && hasPermission('leads_manage', 'list'))
    <div class="card mb-3 py-2 px-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <form action="" class="d-flex gap-2 date-range-form">
                @include('vendor-views/form_modals/date_range')
                <button style="white-space:nowrap" class="btn btn-outline-warning btn-sm" type="button"
                    data-toggle="modal" data-target="#dateRangeModal">
                    <i class="tio-calendar"></i> {{ translate($preset) }}
                </button>
                <div class="input-group" style="max-width:260px;">
                    <input type="search" name="search" value="{{ request('search') }}"
                        class="form-control form-control-sm"
                        placeholder="Search by service name...">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-sm btn--secondary"><i class="tio-search"></i></button>
                    </div>
                </div>
            </form>
            <form action="">
                <select class="form-control form-control-sm" name="type" onchange="this.form.submit()" style="min-width:130px;">
                    <option {{ $type == 'All'       ? 'selected' : '' }} value="All">All</option>
                    <option {{ $type == 'New'       ? 'selected' : '' }} value="New">New</option>
                    <option {{ $type == 'Accepted'  ? 'selected' : '' }} value="Accepted">Accepted</option>
                    <option {{ $type == 'Cancelled' ? 'selected' : '' }} value="Cancelled">Cancelled</option>
                    <option {{ $type == 'Completed' ? 'selected' : '' }} value="Completed">Completed</option>
                </select>
            </form>
        </div>
    </div>
    @endif

    {{-- Cards grid --}}
    @if(hasPermission('leads_manage', 'list'))
    @php
        $statusCounts = ['new' => 0, 'accepted' => 0, 'completed' => 0, 'cancelled' => 0];
    @endphp
    <div class="row g-3">
        @forelse($product as $key => $lead)
        @php
            $status            = $lead->current_status;
            $invoiceStatus     = _serviceInvoiceStatus($lead->id);
            $isCompleted       = $status === 'Completed';
            $isCancelled       = _isCancelled($lead->id);
            $isConfirmed       = $status === 'Confirmed';
            $canViewDetails    = ($isConfirmed || $isCancelled || $isCompleted) && $lead->status != 'cancelled';
            $isConfirmed2      = $status === 'Confirmed' || $canViewDetails;
            $isAcceptedReq     = _acceptedReq($lead->id);
            $canAccept         = !isset($lead->additional_status) || $lead->additional_status !== 'missed';
            $user_details      = _getUserDetails($lead->uid);

            $class = \Illuminate\Support\Str::slug(
                strtolower($lead->current_status ?? ($lead->additional_status ?? $lead->assigned_status)), '_'
            );
            if ($isCancelled)  $class = 'cancelled';
            if ($class == '')  $class = 'new';

            // Doctor
            $hDocProfile = $lead->preferred_doctor_id
                ? \App\Models\DoctorProfile::with('employee')->find($lead->preferred_doctor_id)
                : null;
            $hDoctorName = $hDocProfile?->employee
                ? 'Dr. ' . trim($hDocProfile->employee->f_name . ' ' . $hDocProfile->employee->l_name)
                : 'Not specified';
            $hDoctorSpec = $hDocProfile?->specialization ?? null;

            // Slot
            $hSlot = $lead->preferred_slot_id
                ? \App\Models\DoctorSlot::find($lead->preferred_slot_id)
                : null;
            $hSlotLabel = $hSlot
                ? substr($hSlot->slot_start, 0, 5) . ' – ' . substr($hSlot->slot_end, 0, 5)
                : ($lead->preferred_time ?? null);

            // Patient
            $hIsOther      = ($lead->patient_for ?? '') === 'other' && ($lead->patient_name ?? '');
            $hPatientName  = $hIsOther
                ? $lead->patient_name
                : ($user_details ? trim($user_details->f_name . ' ' . $user_details->l_name) : '—');
            $hPatientPhone = $hIsOther
                ? ($lead->patient_phone ?? '—')
                : ($user_details?->phone ?? '—');

            // OPD visit linked to this service request
            $hOpdVisit = null;
            try {
                $hOpdVisit = \App\Models\OpdVisit::where('service_request_id', $lead->id)->first();
            } catch (\Exception $e) {}

            // Fallback for visits registered before service_request_id column existed:
            // match by preferred doctor + preferred date + patient (via user_id)
            if (!$hOpdVisit && $lead->preferred_doctor_id && $lead->preferred_date) {
                $hStoreId = \App\CentralLogics\Helpers::get_store_id();
                $hPatient = \App\Models\Patient::where('store_id', $hStoreId)
                    ->where('user_id', $lead->uid)
                    ->first();
                if ($hPatient) {
                    $hOpdVisit = \App\Models\OpdVisit::where('store_id', $hStoreId)
                        ->where('doctor_profile_id', $lead->preferred_doctor_id)
                        ->where('patient_id', $hPatient->id)
                        ->whereDate('visit_date', $lead->preferred_date)
                        ->first();
                }
            }
        @endphp

        <div class="col-sm-6 col-md-4 col-lg-3 mb-1">
        
            <div class="appt-card appt-{{ $class }}" @if($hOpdVisit?->id)  onclick="handleClick('{{ route('vendor.opd.show', [$hOpdVisit->id]) }}', event)" style="cursor:pointer;" @endif
                 >

                {{-- Top: status dot + label + ID + ⋮ dropdown --}}
                <div class="appt-card__top">
                    <span class="appt-status-dot"></span>
                    <span class="appt-status-label">
                        @if(isset($lead->additional_status) && $lead->additional_status === 'missed') Missed
                        @elseif($isCancelled) Cancelled
                        @elseif($isCompleted) Completed
                        @elseif($lead->current_status) {{ $lead->current_status }}
                        @elseif($lead->assigned_status) {{ $lead->assigned_status }}
                        @else New
                        @endif
                    </span>
                    <span class="ml-auto text-muted" style="font-size:11px; font-weight:400;">#{{ $lead->id }}</span>

                    @if($class !== 'missed' && $class !== 'new' && $lead->status !== 'cancelled')
                    <div class="dropdown">
                        <button class="btn p-0 btn-sm dropdown-toggle" type="button"
                            data-toggle="dropdown" aria-expanded="false" >
                            <i class="fa-solid fa-bars" style="font-size:16px;"></i>
                        </button>
                        <div class="dropdown-menu" style="font-size:13px;">
                            @if($hOpdVisit)
                                <a href="{{ route('vendor.opd.show', $hOpdVisit->id) }}" class="dropdown-item">
                                    <i class="tio-document-text"></i> View OPD Visit
                                </a>
                                @else 
                                <a href="{{ route('vendor.opd.create', [$lead->id]) }}" class="dropdown-item">
                                    <i class="tio-add"></i> Register OPD Visit
                                    </a>
                            @endif
                            @if($isCompleted)
                                @if(in_array($invoiceStatus, ['new', 'editable']))
                                    <a href="{{ route('vendor.business-settings.generate-bill', [$lead->id]) }}" class="dropdown-item">
                                        <i class="fas fa-file-invoice"></i> {{ $invoiceStatus === 'new' ? 'Generate' : 'Edit' }} Bill
                                    </a>
                                @else
                                    <a target="_blank" href="{{ asset('storage/app/public/invoice/' . $invoiceStatus) }}" class="dropdown-item text-success">
                                        <i class="tio-document-outlined"></i> View Bill
                                    </a>
                                @endif
                            @endif
                            @if(($isConfirmed || $isCompleted ) && $hOpdVisit)
                                @php $leadRx = \App\Models\Prescription::where('service_request_id', $lead->id)->first(); @endphp
                                @if($leadRx)
                                    <a href="{{ route('vendor.prescription.show', $leadRx->id) }}" class="dropdown-item text-success">
                                        <i class="tio-print"></i> View Prescription
                                    </a>
                                    @if(!$leadRx->is_finalized)
                                    <a href="{{ route('vendor.prescription.edit', $leadRx->id) }}" class="dropdown-item text-primary">
                                        <i class="tio-edit"></i> Edit Prescription
                                    </a>
                                    @endif
                                @else
                                    <a href="{{ route('vendor.prescription.create', ['service_request_id' => $lead->id]) }}" class="dropdown-item text-success">
                                        <i class="tio-document-text"></i> Write Prescription
                                    </a>
                                @endif
                            @endif
                            {{-- @if($isConfirmed2 && !$isCompleted && !$isCancelled && hasPermission('leads_manage', 'cancel'))
                                <a onclick="cancelLead({{ $lead->id }}, {{ $lead->acc_id }}); event.stopPropagation();"
                                    class="dropdown-item text-danger" style="cursor:pointer;">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            @endif --}}
                            @if($isAcceptedReq && !$canViewDetails)
                                <a href="#" class="dropdown-item" data-toggle="modal"
                                    data-target="#exampleModal33-{{ $lead->id }}" onclick="event.stopPropagation()">
                                    <i class="fas fa-user"></i> Patient Details
                                </a>
                            @endif
                         
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Service name (highlighted in blue) --}}
                <div class="appt-card__service">
                    <i class="tio-stethoscope" style="font-size:15px; flex-shrink:0;"></i>
                    <span>{{ $lead->item_name }}</span>
                </div>

                {{-- Preferred doctor (highlighted in green) --}}
                <div class="appt-card__doctor">
                    <div class="appt-doctor-name">
                        <i class="tio-user-add"></i> {{ $hDoctorName }}
                    </div>
                    @if($hDoctorSpec)
                        <div class="appt-doctor-spec">{{ $hDoctorSpec }}</div>
                    @endif
                </div>

                {{-- Date + slot timing --}}
                <div class="appt-card__timing">
                    <span>
                        <i class="tio-calendar"></i>
                        {{ $lead->preferred_date ? \Carbon\Carbon::parse($lead->preferred_date)->format('d M Y') : 'No date' }}
                    </span>
                    @if($hSlotLabel)
                        <span><i class="tio-time"></i> {{ $hSlotLabel }}</span>
                    @endif
                </div>

                <hr class="appt-divider">

                {{-- Patient info --}}
                <div class="appt-card__patient">
                    @if($isAcceptedReq)
                        <div><i class="tio-user text-muted"></i>&nbsp;<strong>{{ $hPatientName }}</strong></div>
                        @if($hPatientPhone && $hPatientPhone !== '—')
                            <div><i class="tio-call text-muted"></i>&nbsp;{{ $hPatientPhone }}</div>
                        @endif
                    @else
                        <div class="appt-card__patient-locked">
                            <i class="tio-lock-outlined"></i> Accept to view patient details
                        </div>
                    @endif
                </div>

                {{-- Action buttons --}}
                <div class="appt-card__actions">
                    @if(isset($lead->additional_status) && $lead->additional_status === 'missed')
                        <span class="badge badge-missed px-3 py-1 w-100 text-center">Missed</span>
                        @php addStatus($statusCounts, 'missed'); @endphp

                    @elseif($isCancelled)
                        <span class="badge badge-cancelled px-3 py-1">Cancelled</span>
                        @php addStatus($statusCounts, 'cancelled'); @endphp

                    @elseif($isCompleted)
                        <span class="badge badge-completed px-3 py-1">Completed</span>
                        <a href="{{ route('vendor.opd.show', [$lead->id]) }}"
                            class="btn btn-sm btn-outline-secondary ml-auto" onclick="event.stopPropagation()">
                            Details
                        </a>
                        @php addStatus($statusCounts, 'completed'); @endphp

                    @elseif($lead->current_status === 'Confirmed' || $lead->assigned_status === 'Assigned' || $lead->assigned_status === 'Unassigned')
                        @if($hOpdVisit)
                            <a href="{{ route('vendor.opd.show', $hOpdVisit->id) }}"
                                class="btn btn-sm btn--primary w-100" onclick="event.stopPropagation()">
                                <i class="tio-document-text"></i> View OPD Visit
                            </a>
                        @else
                            <a href="{{ route('vendor.opd.create', [$lead->id]) }}"
                                class="btn btn-sm btn-outline-secondary w-100" onclick="event.stopPropagation()">
                                <i class="tio-add"></i> Register OPD Visit
                            </a>
                        @endif
                        @php addStatus($statusCounts, $lead->assigned_status === 'Unassigned' ? 'unassigned' : 'alotted'); @endphp

                    @elseif($isAcceptedReq)
                        <a class="btn btn-sm btn--primary w-100" data-toggle="modal"
                            data-target="#exampleModal33-{{ $lead->id }}" onclick="event.stopPropagation()">
                            Confirm Appointment
                        </a>
                        @php addStatus($statusCounts, 'accepted'); @endphp

                    @elseif(!$isCancelled && !$isCompleted)
                        @if(hasPermission('leads_manage', 'accept') && $canAccept)
                            <a href="{{ route('vendor.service.accept', [$lead->id]) }}"
                                class="btn btn-accept btn-sm w-100" onclick="event.stopPropagation()">
                                <i class="fas fa-check"></i> Accept Appointment
                            </a>
                        @elseif($lead->current_status)
                            <span class="badge badge-{{ $class }} px-3 py-1">{{ $lead->current_status }}</span>
                        @else
                            <span class="badge badge-soft-secondary px-3 py-1">New</span>
                        @endif
                        @php addStatus($statusCounts, 'new'); @endphp
                    @endif
                </div>

            </div>
        </div>

        {{-- Assign modal --}}
        <div class="modal fade" id="assignModal{{ $key }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Assign Doctor to Appointment</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @if($lead->assigned_status == 'Unassigned' || $lead->assigned_type == 'vendor')
                            <form action="{{ route('vendor.service.save-assignment') }}" method="post">
                                @csrf
                                <input type="hidden" name="service_id" value="{{ $lead->id }}">
                                <input type="hidden" name="id" value="{{ $lead->acc_id }}">
                                <div class="form-group">
                                    <label class="form-label">{{ $lead->assigned_status == 'Assigned' ? 'Reassign' : 'Assign' }} To</label>
                                    <select name="staff_id" class="js-select2-custom form-control">
                                        <option></option>
                                        <option value="vendor">Self (Vendor)</option>
                                        @foreach($allStaff as $staff)
                                            <option value="{{ $staff->id }}">{{ $staff->f_name . ' ' . $staff->l_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <input class="btn btn--primary text-white" type="submit" value="Assign">
                            </form>
                        @else
                            <b>Assigned To: </b>
                            @if($lead->assigned_type == 'vendor')
                                Self
                            @else
                                @php $empInfo = _getWhereOne('vendor_employees', ['id' => $lead->assigned_to]); @endphp
                                @if($empInfo)
                                    {{ $empInfo->f_name . ' ' . $empInfo->l_name . ' #' . $lead->assigned_to }}
                                    ({{ !$lead->accepted_by_staff ? 'Acceptance Pending' : ($lead->accepted_by_staff == 2 ? 'Rejected' : 'Accepted') }})
                                @endif
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Patient details modal --}}
        @if($user_details)
        <div class="modal fade" id="exampleModal33-{{ $lead->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header border-bottom-0">
                        <div>
                            <small class="text-muted d-block">Appointment</small>
                            <h5 class="modal-title font-weight-bold">{{ $lead->item_name }}</h5>
                        </div>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3 p-3" style="background:#fff; border-radius:8px; border:1px solid #e5e7eb;">
                            <div class="d-flex align-items-center mb-3">
                                <div style="width:44px; height:44px; background:var(--primary); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:18px; flex-shrink:0;" class="mr-3">
                                    {{ strtoupper(substr($hPatientName, 0, 1)) }}
                                </div>
                                <div>
                                    <h6 class="mb-0 font-weight-bold">{{ $hPatientName }}</h6>
                                    <small class="text-muted">Patient</small>
                                </div>
                            </div>
                            @if($hPatientPhone && $hPatientPhone !== '—')
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="tio-call text-muted"></i>
                                <span>{{ $hPatientPhone }}</span>
                            </div>
                            @endif
                            @if($user_details->email)
                            <div class="d-flex align-items-center gap-2">
                                <i class="tio-email text-muted"></i>
                                <a href="mailto:{{ $user_details->email }}">{{ $user_details->email }}</a>
                            </div>
                            @endif
                        </div>

                        {{-- Appointment details summary --}}
                        <div class="p-3" style="background:#f8fafc; border-radius:8px; font-size:13px;">
                            <div class="mb-1"><span class="text-muted">Doctor:</span> <strong>{{ $hDoctorName }}</strong></div>
                            @if($hDoctorSpec)<div class="mb-1"><span class="text-muted">Specialization:</span> {{ $hDoctorSpec }}</div>@endif
                            <div class="mb-1"><span class="text-muted">Date:</span>
                                {{ $lead->preferred_date ? \Carbon\Carbon::parse($lead->preferred_date)->format('d M Y') : '—' }}
                            </div>
                            @if($hSlotLabel)<div><span class="text-muted">Slot:</span> {{ $hSlotLabel }}</div>@endif
                        </div>

                        {{-- Confirmation action --}}
                        @if(!_getCurrentServiceStatus($lead->id))
                            @if(hasPermission('leads_manage', 'send_confirmation_request'))
                            <form action="{{ route('vendor.service.send-confirmation-notification', ['id' => $lead->id]) }}"
                                class="mt-3">
                                @csrf
                                <input type="hidden" name="id" value="{{ $lead->id }}">
                                <div class="form-group">
                                    <label class="font-weight-600">Visiting Charges</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="tio-money"></i></span>
                                        </div>
                                        <input type="number" name="price" class="form-control"
                                            placeholder="Enter amount" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn--primary btn-block">
                                    <i class="tio-checkmark-circle mr-1"></i> Send Confirmation Request
                                </button>
                            </form>
                            @endif
                        @else
                            <div class="mt-3 p-3" style="background:#f0fdf4; border-radius:8px;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted d-block">Status</small>
                                        <strong>{{ _getCurrentServiceStatus($lead->id) }}</strong>
                                    </div>
                                    @if(_getCurrentServiceStatus($lead->id) == 'Confirmed')
                                        <a href="{{ route('vendor.service.cancel', [$lead->id]) }}"
                                            class="btn btn-outline-danger btn-sm">
                                            <i class="tio-clear"></i> Cancel
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @empty
        <div class="col-12">
            <div class="text-center py-5 text-muted">
                <i class="tio-hospital" style="font-size:48px; opacity:.25; display:block; margin-bottom:12px;"></i>
                No appointments found for the selected filters.
            </div>
        </div>
        @endforelse
    </div>
    @endif

</div>
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
    <script>
        function handleClick(url, e) {
            if (e.target.closest('a, button, .dropdown')) return;
            window.location = url;
        }
        function cancelLead(id, accId) {
            if (!confirm('Cancel this appointment?')) return;
            window.location = '{{ url("vendor/service/cancel") }}/' + id;
        }
    </script>
@endpush
