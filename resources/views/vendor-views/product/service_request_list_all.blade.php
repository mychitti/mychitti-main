@extends('layouts.vendor.app')

@section('title', $type .  (_isHospital() ? 'Appointments' : ' Leads'))

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">

    <style>
        @media (max-width:550px) {

            .mini-date {
                width: 30px;
                padding: 5px;
            }
        }
    </style>
    {{-- card design new  --}}
    <style>
        .card-container {
            background: linear-gradient(135deg, #1db9c3 0%, #2196f3 100%);
            border-radius: 32px;
            padding: 8px;
            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.2),
                0 5px 15px rgba(0, 0, 0, 0.1);
            height: 100%;
        }

        .btn_sm {
            padding: 2px 5px;
            font-size: 12px;
        }

        .card-new {
            background: linear-gradient(to bottom, #ffffff 0%, #e8f4f8 100%);
            border-radius: 26px;
            padding: 12px;
            height: 100%;
        }

        .badge-missed,
        .stat_card-missed {
            background: #ff442cff;
            color: white;
        }

        .badge-completed,
        .stat_card-completed {
            background: #518f00ff;
            color: white;
        }

        .badge-cancelled,
        .stat_card-cancelled {
            background: #fb8c7bff;
            color: white;
        }

        .badge-confirmation_request_sent,
        .stat_card-confirmation_request_sent,
        .badge-new {
            background: #d483ffff;
            color: white;
        }

        .badge-assigned,
        .stat_card-confirmed {
            background: #e17b14ff;
            color: white;
        }

        .badge-confirmed,
        .stat_card- {
            background: #4492ffff;
            color: white;
        }

        .stat_card-alotted {
            background: #f6bb84ff;
            color: black;
        }

        .stat_card-new {
            background: #72bfaaff;
            color: black;
        }

        .stat_card-unassigned {
            background: #e0e47aff;
            color: black;
        }

        .stat_card-in_progress {
            background: #f6bb84ff;
            color: black;
        }

        .info-row {
            color: black;
            font-size: 11px;
        }

        .info-label {
            font-weight: 600;
            /* min-width: 140px; */
            /* word-break: unset; */
            {{-- white-space: nowrap; --}} {{-- width: 50%; --}}
        }

        .info-value {
            font-weight: 700;
            {{-- white-space: nowrap; --}} text-align: right;
            {{-- width: 50%; --}}
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 15px;
        }

        .btn-new {
            padding: 5px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            color: white;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .btn-accept {
            background: #4caf50;
            color: #ffffffff;
            padding: 2px 10px;
        }

        .btn-accept:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }

        .btn-reject {
            background: #f44336;
            color: #e0dedeff;
        }

        .btn-reject:hover {
            background: #da190b;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }

        .btn-new:active {
            transform: translateY(0);
        }
    </style>
    <style>
        .dropdown-toggle:not(.dropdown-toggle-empty)::after {
            display: none !important;
        }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        // Enable pusher logging - don't include this in production
        Pusher.logToConsole = true;

        var pusher = new Pusher('45bfc10c466a1d72f474', {
            cluster: 'ap2'
        });

        var channel = pusher.subscribe('my-channel');
        channel.bind('my-event', function(data) {
            alert(JSON.stringify(data));
        });
    </script> --}}
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>{{ $type . (_isHospital() ? 'Appointments' : ' Leads') }} <span
                    class="badge badge-soft-dark ml-2" id="itemCount">{{ count($product) }}</span></h1>
            <div class="page-header-select-wrapper">
                <button onclick="window.location.reload()" class="btn btn-outline-secondary btn-sm mr-1" title="Refresh">
                    <i class="tio-refresh"></i>
                </button>
                @if (hasPermission('leads_manage', 'statuses') && !$empId)
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#sttsMOdal">
                        Available Statuses
                    </button>
                @endif
            </div>
        </div>
        <!-- End Page Header -->
        @if (hasPermission('leads_manage', 'list'))
            <div class="d-flex gap-4 row p-5 pt-0" id="statusCards"></div>
        @endif
        <!-- Card -->
        <div class="card ">
            <!-- Header -->
            <div class="card-header p-1 py-2 ">
                <div class="search--button-wrapper d-flex justify-content-center">
                    @if (!$empId)
                        @if (hasPermission('leads_manage', 'list'))
                            <form action="" class="d-flex gap-2 date-range-form">
                                <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning"
                                    type="button" data-toggle="modal"
                                    data-target="#dateRangeModal">{{ translate($preset) }}</button>
                                {{-- date range modal --}}
                                @include('vendor-views/form_modals/date_range')
                                <!-- Search -->
                                <div class="input-group input--group">
                                    <input type="search" name="search" value="{{ request()?->search ?? null }}"
                                        class="form-control min-height-45"
                                        placeholder="{{ translate('messages.search by service name') }}">
                                    <button type="submit" class="btn btn--secondary min-height-45"><i
                                            class="tio-search"></i></button>
                                </div>
                            </form>
                            <form action="" class="">
                                <select class="form-control mx-1" name="type" onchange="this.form.submit()">
                                    <option {{ $type == 'All' ? 'selected' : '' }} value="All">All</option>
                                    <option {{ $type == 'New' ? 'selected' : '' }} value="New">New</option>
                                    {{-- <option {{ $type == 'Missed' ? 'selected' : '' }} value="Missed">Missed</option> --}}
                                    <option {{ $type == 'Accepted' ? 'selected' : '' }} value="Accepted">Accepted</option>
                                    {{-- <option {{ $type == 'Confirmed' ? 'selected' : '' }} value="Confirmed">Confirmed
                                    </option> --}}
                                    {{-- <option {{ $type == 'In Progress' ? 'selected' : '' }} value="In Progress">In Progress
                                    </option> --}}
                                    <option {{ $type == 'Cancelled' ? 'selected' : '' }} value="Cancelled">Cancelled
                                    </option>
                                    <option {{ $type == 'Completed' ? 'selected' : '' }} value="Completed">Completed
                                    </option>
                                </select>
                            </form>
                        @endif
                        {{-- @if (hasPermission('leads_manage', 'export'))
                            <a href="{{ route('vendor.service.leads_list', [0, 'export']) . '?' . http_build_query(array_filter(['search' => request('search'), 'type' => request('type'), 'date_range' => request('date_range'), 'custom_date_range' => request('custom_date_range')])) }}"
                                class="btn btn--primary">Export</a>
                        @endif --}}
                    @endif
                </div>
            </div>  
            <!-- End Header -->
            @php
                $statusCounts = [
                    'new' => 0,
                    'accepted' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                ];
            @endphp
            @if (hasPermission('leads_manage', 'list'))
                <div class="row p-3 d-flex g-4" style="padding-top: 30px;">


                    @foreach ($product as $key => $lead)
                        @php
                            $status = $lead->current_status;
                            $invoiceStatus = _serviceInvoiceStatus($lead->id);
                            $isCompleted = $status === 'Completed'; 
                            $isCancelled = _isCancelled($lead->id);
                            $isConfirmed = $status === 'Confirmed';
                            $canViewDetails = ($isConfirmed || $isCancelled || $isCompleted) && $lead->status != 'cancelled';
                            $isConfirmed2 = $status === 'Confirmed' || $canViewDetails;
                            $isAcceptedReq = _acceptedReq($lead->id);
                            $canAccept = !isset($lead->additional_status) || $lead->additional_status !== 'missed';
                            $currentServiceStatus = _getCurrentServiceStatus($lead->id);
                            $class = \Illuminate\Support\Str::slug(
                                strtolower(
                                    $lead->current_status ?? ($lead->additional_status ?? $lead->assigned_status),
                                ),
                                '_',
                            );
                            if ($isCancelled) {
                                $class = 'cancelled';
                            }
                            if ($class == '') {
                                $class = 'new';  
                            } 
                            $user_details = _getUserDetails($lead->uid);
                        @endphp
                        <div class="col-md-2">
                            <div @if ($class != 'missed' && $class != 'new' && $class != 'cancelled') onclick="handleClick('{{ route('vendor.service.lead-details', [$lead->id]) }}', event)" style="cursor:pointer;" @endif
                                class="card-container">

                                <div class="card-new pt-4 {{ $class }}">
                                    @if ($class == 'new' || $lead->status == 'cancelled' || (isset($lead->additional_status) && $lead->additional_status == 'missed'))
                                    @else
                                        <div class="dropdown">
                                            <button class="btn p-1 dropdown-toggle"
                                                style="position: absolute; right: -5px; top: -22px;" type="button"
                                                data-toggle="dropdown" aria-expanded="false">
                                                  <i class="fa-solid fa-bars" style="font-size:16px;"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                @if ($isCompleted)
                                                    @if (in_array($invoiceStatus, ['new', 'editable']))
                                                        <a href="{{ route('vendor.business-settings.generate-bill', [$lead->id]) }}"
                                                            class="dropdown-item {{ $invoiceStatus === 'new' ? 'text-primary' : 'text-danger' }}"
                                                            title="Bill Management">
                                                            <i class="fas fa-file-invoice"></i>
                                                            {{ $invoiceStatus === 'new' ? 'Generate' : 'Edit' }} Bill
                                                        </a>
                                                    @else
                                                        <a target="_blank"
                                                            href="{{ asset('storage/app/public/invoice/' . $invoiceStatus) }}"
                                                            class="dropdown-item text-success" title="View Bill">
                                                            <i class="tio-document-outlined"></i>
                                                            View Bill
                                                        </a>
                                                    @endif
                                                @endif


                                                @if ($canViewDetails)
                                                    <a href="{{ route('vendor.service.lead-details', [$lead->id]) }}"
                                                        class="dropdown-item text-warning" title="View Details">
                                                        <i class="tio-visible-outlined"></i>
                                                        View Details
                                                    </a>
                                                @endif

                                                @if ($isConfirmed2 && !$isCompleted && !$isCancelled)
                                                    @if (hasPermission('leads_manage', 'cancel'))
                                                        <a onclick="cancelLead({{ $lead->id }}, {{ $lead->acc_id }})"
                                                            class="dropdown-item text-danger" title="Cancel Lead"
                                                            style="cursor: pointer;">
                                                            <i class="fas fa-times"></i>
                                                            Cancel
                                                        </a>
                                                    @endif
                                                    {{-- @if (hasPermission('leads_manage', 'convert_to_task'))
                                                        <a href="{{ route('vendor.lead.convert-to-task', [$lead->id]) }}"
                                                            class="dropdown-item text-primary" title="Convert to Task"
                                                            style="cursor: pointer;">
                                                            <i class="tio-image-rotate-right"></i>
                                                            Convert to Task
                                                        </a>
                                                    @endif --}}
                                                @endif
                                                @if (
                                                    $lead->current_status === 'Confirmed' &&
                                                        $lead->assigned_status !== 'Unassigned' &&
                                                        $lead->assigned_type === 'staff' &&
                                                        isset($lead->assigned_to))
                                                    <a href="{{ route('vendor.track-location', [$lead->assigned_to]) }}"
                                                        target="_blank" class="dropdown-item text-primary"
                                                        title="{{ translate('messages.Track Location') }}">
                                                        <i class="tio-location-search"></i> Track Location
                                                    </a>
                                                @endif

                                                @if (_isHospital() && ($isConfirmed || $isCompleted))
                                                    @php
                                                        $leadRx = \App\Models\Prescription::where('service_request_id', $lead->id)->first();
                                                    @endphp
                                                    @if($leadRx)
                                                        <a href="{{ route('vendor.prescription.show', $leadRx->id) }}"
                                                            class="dropdown-item text-success">
                                                            <i class="tio-print"></i> View Prescription
                                                        </a>
                                                        @if(!$leadRx->is_finalized)
                                                        <a href="{{ route('vendor.prescription.edit', $leadRx->id) }}"
                                                            class="dropdown-item text-primary">
                                                            <i class="tio-edit"></i> Edit Prescription
                                                        </a>
                                                        @endif
                                                    @else
                                                        <a href="{{ route('vendor.prescription.create', ['service_request_id' => $lead->id]) }}"
                                                            class="dropdown-item text-success">
                                                            <i class="tio-medicine"></i> Write Prescription
                                                        </a>
                                                    @endif
                                                @endif

                                                @if (!$canViewDetails)
                                                    @if ($isAcceptedReq)
                                                        <a href="#" class="dropdown-item text-primary"
                                                            data-toggle="modal"
                                                            data-target="#exampleModal33-{{ $lead->id }}"
                                                            title="Contact Details">
                                                            <i class="fas fa-user"></i>
                                                            User Details
                                                        </a>
                                                    @elseif ($canAccept)
                                                        @if ($currentServiceStatus && $currentServiceStatus === 'Confirmation Request Sent')
                                                            <span class="dropdown-item text-muted">
                                                                <i class="fas fa-clock"></i>
                                                                {{ $currentServiceStatus }}
                                                            </span>
                                                        @endif
                                                    @endif
                                                @endif

                                            </div>
                                        </div>
                                    @endif
                                    <div class="info-row">
                                        <span class="info-label">Service Name:</span>
                                        <span class="info-value">{{ Str::limit($lead->item_name, 20, '...') }}</span>
                                    </div>

                                    @if ($isAcceptedReq)
                                        <div class="info-row">
                                            <span class="info-label">Client Name:</span>
                                            <span
                                                class="info-value">{{ $user_details->f_name . ' ' . $user_details->l_name }}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Client Phone:</span>
                                            <span class="info-value">{{ $user_details->phone }}</span>
                                        </div>
                                    @else
                                        <div class="info-row">
                                            <span class="info-label">Request at:</span>
                                            <span class="info-value">{{ $lead->created_at }}</span>
                                        </div>
                                    @endif


                                    <div class="button-container">

                                        @if (isset($lead->additional_status) && $lead->additional_status == 'missed')
                                            <span class="badge badge-{{ $class }}">
                                                <i class="tio-info-outined" data-toggle="tooltip" data-placement="left"
                                                    data-html="true"
                                                    title="<b class='text-danger' >You missed this lead.</b><br> Leads are available for maximum {{ \App\CentralLogics\Helpers::get_lead_exp_time() }}">
                                                </i>
                                                Missed
                                            </span>
                                            @php addStatus($statusCounts, 'missed'); @endphp
                                        @else
                                            @if ($isCompleted || $isCancelled)
                                                @if ($isCompleted)
                                                    <span class="badge badge-{{ $class }}">Completed</span>
                                                    @php addStatus($statusCounts, 'completed'); @endphp
                                                @elseif ($isCancelled)
                                                    <span class="badge badge-{{ $class }}">Cancelled</span>
                                                    @php addStatus($statusCounts, 'cancelled'); @endphp
                                                @endif
                                            @elseif ($lead->current_status != 'Confirmed' && $isAcceptedReq)
                                                <a class="btn btn-primary btn_sm" data-toggle="modal"
                                                    data-target="#exampleModal33-{{ $lead->id }}"
                                                    title="Contact Details">
                                                    <i class="Send Confirmation Request"></i>
                                                    Send Confirmation Request
                                                </a>
                                                @php addStatus($statusCounts, 'accepted'); @endphp
                                            @elseif (
                                                $lead->current_status == 'Confirmed' ||
                                                    $lead->assigned_status == 'Assigned' ||
                                                    $lead->assigned_status == 'Unassigned')
                                                @if (hasPermission('leads_manage', 'alot'))
                                                    @if ($lead->assigned_status == 'Unassigned')
                                                        @php addStatus($statusCounts, 'unassigned'); @endphp

                                                        <a style="width:fit-content; padding: 2px 8px !important"
                                                            type="button" data-toggle="modal"
                                                            data-target="#assignModal{{ $key }}"
                                                            class="btn action-btn btn--primary">Unassigned
                                                        </a>
                                                    @else
                                                        @php addStatus($statusCounts, 'alotted'); @endphp

                                                        <a style="width:fit-content; padding: 2px 8px !important"
                                                            type="button" data-toggle="modal"
                                                            data-target="#assignModal{{ $key }}"
                                                            class="btn action-btn btn--primary">
                                                            Assigned
                                                            {{ $lead->assigned_type == 'vendor' ? ' to self' : '' }}
                                                        </a>
                                                    @endif
                                                @else
                                                    <span
                                                        class="badge badge-soft-{{ $lead->assigned_status == 'Unassigned' ? 'danger' : 'success' }}">{{ $lead->assigned_status }}
                                                        {{ $lead->assigned_type == 'vendor' ? ' to self' : '' }}</span>
                                                @endif
                                            @else
                                                @if ($lead->current_status == 'Confirmation Request Sent')
                                                    <span class="badge badge-{{ $class }}">
                                                        {{ $lead->current_status }}
                                                    </span>
                                                    @php addStatus($statusCounts, 'new'); @endphp
                                                @elseif ($lead->current_status != '')
                                                    <span class="badge badge-{{ $class }}">
                                                        {{ $lead->current_status }}
                                                    </span>
                                                    @php addStatus($statusCounts, $lead->current_status); @endphp
                                                @elseif ($lead->assigned_status != '')
                                                    <span class="badge badge-{{ $class }}">
                                                        {{ $lead->assigned_status }}
                                                    </span>
                                                    @php addStatus($statusCounts, $lead->assigned_status); @endphp
                                                @elseif ($lead->status != '')
                                                    <span class="badge badge-{{ $class }}">
                                                        {{ $lead->status }}
                                                    </span>
                                                    @php addStatus($statusCounts, $lead->status); @endphp
                                                @endif
                                            @endif
                                            {{-- ============ ACCEPT OPTION ================= --}}
                                            @if (!$canViewDetails && !$isCancelled && !$isCompleted)
                                                @if ($isAcceptedReq)
                                                @elseif (hasPermission('leads_manage', 'accept') && $canAccept)
                                                    <a href="{{ route('vendor.service.accept', [$lead->id]) }}"
                                                        class="btn btn-accept" title="Accept Request">
                                                        <i class="fas fa-check"></i>
                                                        Accept
                                                    </a>
                                                @endif
                                            @endif
                                        @endif


                                    </div>

                                    @if ($isCompleted)
                                        <div class="d-flex flex-wrap gap-1 mt-3">
                                            {{-- @if (hasAnyModulePermission(['leads_gatepass']))
                                                <a href="{{ route('vendor.service.gatepass-details', [$lead->id]) }}"
                                                    class="btn btn_sm btn-primary">
                                                    Gatepass</a>
                                            @endif
                                            @if (hasAnyModulePermission(['leads_quotation']))
                                                <a href="{{ route('vendor.service.quotations', [$lead->id]) }}"
                                                    class="btn btn_sm btn-primary">
                                                    Quotation</a>
                                            @endif --}}
                                            @if (hasPermission('leads_manage', 'edit') && in_array($invoiceStatus, ['new', 'editable']))
                                                <a href="{{ route('vendor.business-settings.generate-bill', [$lead->id]) }}"
                                                    class="btn btn_sm btn-primary"
                                                    title="{{ $invoiceStatus === 'new' ? 'Generate' : 'Edit' }} Bill">
                                                    {{ $invoiceStatus === 'new' ? 'Generate' : 'Edit' }} Bill
                                                </a>
                                            @elseif(hasPermission('leads_manage', 'invoice'))
                                                <a target="_blank"
                                                    href="{{ asset('storage/app/public/invoice/' . $invoiceStatus) }}"
                                                    class="btn btn_sm btn-primary" title="View Bill">
                                                    <i class="tio-document-outlined"></i>
                                                    View Bill
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="assignModal{{ $key }}" tabindex="-1" role="dialog"
                            aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="indicator"></div>
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="exampleModalLabel">Assign Staff to Service
                                        </h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        @if ($lead->assigned_status == 'Unassigned' || $lead->assigned_type == 'vendor')
                                            @if ($lead->assigned_type == 'vendor')
                                                <b>Assigned To : </b>
                                                Self (Vendor)
                                            @endif
                                            <form action="{{ route('vendor.service.save-assignment') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="service_id" value = "{{ $lead->id }}"
                                                    hidden>
                                                <input type="hidden" name="id" value = "{{ $lead->acc_id }}"
                                                    hidden>

                                                <div class="form-group">
                                                    <div class="custom-file">
                                                        <label class="form-label"
                                                            for="staff_id">{{ $lead->assigned_status == 'Assigned' ? 'Reassign' : 'Assign' }}
                                                            To</label><br>

                                                        <select name="staff_id" id="staff_id"
                                                            class="js-select2-custom form-control">
                                                            <option></option>
                                                            <option value="vendor">Self (Vendor)</option>
                                                            @foreach ($allStaff as $staff)
                                                                <option value="{{ $staff->id }}">
                                                                    {{ $staff->f_name . ' ' . $staff->l_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group mb-0">
                                                    <input class="btn btn--primary text-white" type="submit"
                                                        value="Assign">
                                                </div>
                                            </form>
                                        @else
                                            <span style="font-size: 17px">
                                                <b>Assigned To : </b>
                                                @if ($lead->assigned_type == 'vendor')
                                                    Self
                                                @else
                                                    @php
                                                        $empInfo = _getWhereOne('vendor_employees', [
                                                            'id' => $lead->assigned_to,
                                                        ]);
                                                    @endphp

                                                    @if ($empInfo)
                                                        <span>
                                                            {{ $empInfo->f_name . ' ' . $empInfo->l_name . ' #' . $lead->assigned_to }}
                                                            ({{ !$lead->accepted_by_staff ? 'Acceptance Pending' : ($lead->accepted_by_staff == 2 ? 'Rejected' : 'Accepted') }})
                                                        </span>
                                                    @endif
                                                @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>


                        @php
                            $user_details = _getUserDetails($lead->uid);

                        @endphp
                        @if ($user_details)
                            <!--modal -->
                            <div class="modal fade" id="exampleModal33-{{ $lead->id }}" tabindex="-1"
                                role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content lead-modal">
                                        <!-- Header -->
                                        <div class="modal-header border-bottom-0">
                                            <div>
                                                <small class="text-muted d-block">Service Request</small>
                                                <h5 class="modal-title mb-0 font-weight-bold" id="exampleModalLabel">
                                                    {{ $lead->item_name }}
                                                </h5>
                                            </div>
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>

                                        <div class="modal-body p-4">
                                            <!-- Customer Info -->
                                            <div class="customer-info-box mb-4">
                                                <div class="d-flex align-items-center mb-3">
                                                    <div class="customer-avatar mr-3">
                                                        {{ strtoupper(substr($user_details->f_name, 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 font-weight-bold">
                                                            {{ $user_details->f_name . ' ' . $user_details->l_name }}</h6>
                                                        <small class="text-muted">Customer Details</small>
                                                    </div>
                                                </div>

                                                <div class="contact-list">

                                                    <div class="contact-item">
                                                        <i class="tio-email"></i>
                                                        <div class="flex-grow-1">
                                                            <small class="text-muted">Email</small>
                                                            <a
                                                                href="mailto:{{ $user_details->email }}">{{ $user_details->email }}</a>
                                                        </div>
                                                    </div>

                                                    <div class="contact-item">
                                                        <i class="tio-call"></i>
                                                        <div class="flex-grow-1">
                                                            <small class="text-muted">Mobile</small>
                                                            <span class="textToCopy">{{ $user_details->phone }}</span>
                                                        </div>
                                                        <button class="copy-btn" title="Copy">
                                                            <i class="tio-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Action Section -->
                                            @if (!_getCurrentServiceStatus($lead->id))
                                                @if (hasPermission('leads_manage', 'send_confirmation_request'))
                                                    <form
                                                        action="{{ route('vendor.service.send-confirmation-notification', ['id' => $lead->id]) }}"
                                                        class="action-form">
                                                        @csrf
                                                        <input type="hidden" name="id"
                                                            value="{{ $lead->id }}">

                                                        <div class="form-group">
                                                            <label for="lead_price" class="font-weight-600">Visiting
                                                                Charges</label>
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i
                                                                            class="tio-money"></i></span>
                                                                </div>
                                                                <input type="number" name="price" id="lead_price"
                                                                    class="form-control" placeholder="Enter amount"
                                                                    required>
                                                            </div>
                                                        </div>

                                                        <button type="submit" class="btn btn--primary btn-block">
                                                            <i class="tio-checkmark-circle mr-1"></i>
                                                            Send Confirmation Request
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <div class="status-box">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center">
                                                            <i class="tio-checkmark-circle-outlined"></i>
                                                            <div class="ml-3">
                                                                <small class="text-muted d-block">Status</small>
                                                                <h6 class="mb-0 font-weight-bold">
                                                                    {{ _getCurrentServiceStatus($lead->id) }}</h6>
                                                            </div>
                                                        </div>

                                                        @if (_getCurrentServiceStatus($lead->id) == 'Confirmed')
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
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                {{ translate('Close') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <style>
                                .lead-modal .modal-body {
                                    background: #f8f9fa;
                                }

                                .customer-info-box {
                                    background: white;
                                    padding: 1.25rem;
                                    border-radius: 8px;
                                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                                }

                                .customer-avatar {
                                    width: 48px;
                                    height: 48px;
                                    background: var(--primary);
                                    color: white;
                                    border-radius: 50%;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-weight: bold;
                                    font-size: 18px;
                                }

                                .contact-list {
                                    display: flex;
                                    flex-direction: column;
                                    gap: 0.75rem;
                                }

                                .contact-item {
                                    display: flex;
                                    align-items: center;
                                    gap: 0.75rem;
                                    padding: 0.5rem;
                                    border-radius: 6px;
                                    transition: background 0.2s;
                                }

                                .contact-item:hover {
                                    background: #f8f9fa;
                                }

                                .contact-item i {
                                    font-size: 20px;
                                    color: var(--primary);
                                }

                                .contact-item small {
                                    display: block;
                                    font-size: 11px;
                                }

                                .contact-item a,
                                .contact-item span {
                                    display: block;
                                    font-weight: 500;
                                    color: #333;
                                }

                                .copy-btn {
                                    border: 1px solid #ddd;
                                    background: white;
                                    padding: 6px 10px;
                                    border-radius: 6px;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                }

                                .copy-btn:hover {
                                    background: var(--primary);
                                    color: white;
                                    border-color: var(--primary);
                                }

                                .action-form {
                                    background: white;
                                    padding: 1.5rem;
                                    border-radius: 8px;
                                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                                }

                                .status-box {
                                    background: white;
                                    padding: 1.25rem;
                                    border-radius: 8px;
                                    border-left: 4px solid var(--bs-success, #28a745);
                                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                                }

                                .status-box i {
                                    font-size: 32px;
                                    color: var(--bs-success, #28a745);
                                }

                                .font-weight-600 {
                                    font-weight: 600;
                                }
                            </style>
                        @endif
                    @endforeach
                </div>
            @endif
            <!-- End Table -->
        </div>
        <!-- End Card -->
    </div>
    @if (hasPermission('leads_manage', 'statuses'))
        <!-- Modal -->
        <div class="modal fade" id="sttsMOdal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Available Statuses</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <b>Default Statuses : </b>
                        @foreach ($default_statuses as $key => $stts)
                            <a href="javascript:;" class="badge badge-light">{{ $stts->status }}</a>
                        @endforeach
                        @if (count($approval_pending))
                            <br><br> <b>Approval Pending : </b>
                            @foreach ($approval_pending as $key => $stts)
                                <a href="javascript:;" class="badge badge-light">{{ $stts->serviceStatus->status }}</a>
                            @endforeach
                        @endif

                        <form action="{{ route('vendor.business-settings.update-statuses') }}" method="post">
                            @csrf
                            <div class="form-group subcategory_1">
                                <div class="form-group mb-4">
                                    <label class="input-label" id="" for="other_verification">Selected</label>
                                    <select name="statuses[]" multiple="multiple"
                                        class="select_subcategory_1 form-control __form-control js-select2-custom js-example-basic-multiple"
                                        data-placeholder="Subcategory">
                                        <option value=""></option>
                                        @foreach ($statuses as $sc)
                                            <option
                                                {{ in_array($sc->id, explode(',', $store_data->lead_statuses)) ? 'selected' : '' }}
                                                value="{{ $sc->id }}">{{ $sc->status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button class="btn btn-primary">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('script_2')
    <script>
        function handleClick(url, e) {
            if ($(e.target).closest('.menu_section, .dropdown-menu, .copy-btn, .dropdown-toggle, button, a')
                .length) {} else {
                window.location.href = url;
            }
        }

        let statusCounts = @json($statusCounts);
        console.log(statusCounts); // check values
        let leadCounts = @json($product).length;
        console.log(statusCounts); // check values
function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}
        $(document).ready(function() {

            let container = $("#statusCards");
            let card = `
            <a href="{{ route('vendor.service.leads_list') }}?type=All" class="d-flex p-2 flex-column col-md-1 card shadow text-center stat_card-all">
                <span>All</span>
                <h1>${leadCounts}</h1>
            </a>
        `;
            container.append(card);


            $.each(statusCounts, function(status, count) {

                let classname = status;
                let statusText = status.toUpperCase();

                let card = `
            <a href="{{ route('vendor.service.leads_list') }}?type=${capitalize(status)}" class="d-flex p-2 flex-column col-md-1 card shadow text-center stat_card-${classname}">
                <span>${statusText}</span>
                <h1>${count}</h1>
            </a>
        `;
                container.append(card);
            });
        });
    </script>


    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        // Enable pusher logging - don't include this in production
        Pusher.logToConsole = true;

        var pusher = new Pusher('45bfc10c466a1d72f474', {
            cluster: 'ap2'
        });

        var channel = pusher.subscribe('my-channel');
        channel.bind('my-event', function(data) {
            alert(JSON.stringify(data));
        });
    </script>

    <script>
        function cancelLead(serviceId, accId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You want to cancel this lead?',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: 'No',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.post({
                        url: '{{ route('vendor.service.cancel') }}',
                        data: {
                            service_id: serviceId,
                            id: accId
                        },
                        beforeSend: function() {
                            $('#loading').show();
                        },
                        success: function(data) {
                            if (data.status) {
                                toastr.success(data.message);
                            } else {
                                toastr.error(data.message);
                            }
                            setTimeout(() => {

                                window.location.reload()
                            }, 1000)
                        },
                        complete: function() {
                            $('#loading').hide();
                        },
                    });
                }
            })
        }


        function status_change_alert(url, message, e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: 'No',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = url;
                }
            })
        }
        $(document).on('ready', function() {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            var datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

            $('#column1_search').on('keyup', function() {
                datatable
                    .columns(1)
                    .search(this.value)
                    .draw();
            });

            $('#column2_search').on('keyup', function() {
                datatable
                    .columns(2)
                    .search(this.value)
                    .draw();
            });

            $('#column3_search').on('keyup', function() {
                datatable
                    .columns(3)
                    .search(this.value)
                    .draw();
            });

            $('#column4_search').on('keyup', function() {
                datatable
                    .columns(4)
                    .search(this.value)
                    .draw();
            });


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function() {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });

        $(document).ready(function() {
            $(".copy-btn").on("click", function() {
                // Get the previous <p> or span element text
                var text = $(this).prev(".textToCopy").text().trim();
                console.log(text); // Debugging

                if (navigator.clipboard && window.isSecureContext) {
                    // Modern way to copy
                    navigator.clipboard.writeText(text).then(() => {
                        console.log("Copied successfully!");
                    }).catch(err => {
                        console.error("Clipboard copy failed", err);
                    });
                } else {
                    // Fallback for older browsers
                    var tempInput = $("<textarea>"); // Use textarea instead of input
                    $("body").append(tempInput);
                    tempInput.val(text).css({
                        position: "absolute",
                        left: "-9999px", // Hide offscreen
                    }).select();
                    document.execCommand("copy");
                    tempInput.remove();
                }
                $(this).html("Copied!");
                setTimeout(() => $(this).html('<i class="tio-copy"></i>'), 1000);
            });
        });
    </script>
    @include('vendor-views/js/date_range')
@endpush
