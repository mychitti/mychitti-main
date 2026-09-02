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
    $isMissed = isset($lead->additional_status) && $lead->additional_status == 'missed';
    $isClickable = !$isMissed && !($lead->status == 'new' && !$isAcceptedReq);

    $class = \Illuminate\Support\Str::slug(
        strtolower($lead->current_status ?? ($lead->additional_status ?? $lead->assigned_status)),
        '_',
    );
    if ($isCancelled) {
        $class = 'cancelled';
    }
    if ($class == '') {
        $class = 'new';
    }

    $user_details = _getUserDetails($lead->uid);
    $jobInfoRows = _getWhere('vendor_emp_jobs', ['service_id' => $lead->id]);
    $jobInfo = $jobInfoRows && count($jobInfoRows) ? $jobInfoRows[0] : null;

    $dt = \Carbon\Carbon::parse($lead->created_at);
    $fmtDate = $dt->format('j M, g:i a');
    $leadNote = \App\Models\LeadNote::where('service_id', $lead->id)
        ->where('store_id', \App\CentralLogics\Helpers::get_store_id())
        ->first();
    $hasGatepass = $lead->acc_id && \App\Models\GatePass::where('accepted_service_id', $lead->acc_id)->exists();
    $hasQuotation = \App\Models\InServiceQuotation::where('service_id', $lead->id)->exists();
@endphp

<div id="lead-wrap-{{ $lead->id }}">

    <div class="lead-card {{ $isClickable ? 'clickable' : '' }}"
        @if ($isClickable) onclick="handleClick('{{ route('vendor.service.lead-details', [$lead->id]) }}', event)" @endif>

        <div class="lc-body">

            {{-- Head --}}
            <div class="lc-head">
                <div style="flex:1;min-width:0;">
                    <div class="lc-id">#{{ $lead->id }}</div>
                    <div class="lc-name" title="{{ $lead->item_name }}">{{ $lead->item_name }}</div>
                </div>
                <div class="lc-head-right">
                    {{-- Badge --}}
                    @if ($isMissed)
                        <span class="status-pill pill-missed text-danger"><b>Missed Lead</b></span>
                    @elseif ($isCompleted)
                        <span class="status-pill pill-completed">Completed</span>
                    @elseif ($isCancelled)
                        <span class="status-pill pill-cancelled">Cancelled</span>
                    @elseif ($lead->current_status == 'Confirmation Request Sent')
                        <span class="status-pill pill-confirmation_request_sent">Req. Sent</span>
                    @elseif ($isAcceptedReq && !$isConfirmed)
                        <span class="status-pill pill-accepted">Accepted</span>
                    @elseif (
                        $lead->current_status == 'Confirmed' ||
                            $lead->assigned_status == 'Assigned' ||
                            $lead->assigned_status == 'Unassigned')
                        @if ($lead->assigned_status == 'Unassigned')
                            <span class="status-pill pill-unassigned">Unassigned</span>
                        @else
                            <span
                                class="status-pill pill-alotted">Assigned{{ $lead->assigned_type == 'vendor' ? ' (Self)' : '' }}</span>
                        @endif
                    @elseif ($lead->current_status != '')
                        <span class="status-pill pill-{{ $class }}">{{ $lead->current_status }}</span>
                    @elseif ($lead->status != '')
                        <span class="status-pill pill-{{ $class }}">{{ ucfirst($lead->status) }}</span>
                    @endif

                    {{-- Menu --}}
                    @if (!$isMissed && $class != 'new')
                        <div class="lc-menu" style="position:relative;">

                            <button class="btn btn-transparent p-0"
                                onclick="event.stopPropagation(); toggleLeadMenu(this)"><i
                                    class="fa fa-bars"></i></button>
                            <div class="dropdown-menu dropdown-menu-right" onclick="event.stopPropagation()">
                                {{-- Quotation --}}
                                @if ($isAcceptedReq && hasAnyModulePermission(['leads_quotation']))
                                    @if ($hasQuotation)
                                        <a href="{{ route('vendor.service.quotations', [$lead->id]) }}"
                                            class="dropdown-item text-success">
                                            <i class="tio-visible"></i> View Quotation
                                        </a>
                                    @elseif (!$isCompleted && !$isCancelled)
                                        <a href="{{ route('vendor.service.quotations', [$lead->id]) }}"
                                            class="dropdown-item text-primary">
                                            <i class="tio-document-text-outlined"></i>
                                            Add Quotation
                                        </a>
                                    @endif
                                @endif

                                {{-- Gatepass --}}
                                @if ($isConfirmed2 && !$isCancelled && hasAnyModulePermission(['leads_gatepass']))
                                    @if ($hasGatepass)
                                        <a href="{{ route('vendor.service.gatepass-details', [$lead->id]) }}"
                                            class="dropdown-item text-success">
                                            <i class="tio-eye-outlined"></i> View Gatepass
                                        </a>
                                    @elseif (!$isCompleted)
                                        <a href="{{ route('vendor.service.gatepass-details', [$lead->id]) }}"
                                            class="dropdown-item text-primary">
                                            <i class="tio-document-outlined"></i>
                                            Add Gatepass
                                        </a>
                                    @endif
                                @endif

                                {{-- Bill --}}
                                @if ($isConfirmed2 && hasPermission('leads_manage', 'edit'))
                                    <a href="{{ route('vendor.business-settings.generate-bill', [$lead->id]) }}"
                                        class="dropdown-item text-primary">
                                        <i class="fas fa-file-invoice"></i>
                                        {{ $invoiceStatus === 'new' ? 'Add Bill' : 'Edit Bill' }}
                                    </a>
                                    @php $existingPdf = _getServiceInvoice($lead->id); @endphp
                                    @if ($existingPdf)
                                        <a target="_blank"
                                            href="{{ asset('storage/app/public/invoice/' . $existingPdf) }}"
                                            class="dropdown-item text-success">
                                            <i class="tio-document-outlined"></i> View Bill
                                        </a>
                                    @endif
                                @endif

                                @if ($canViewDetails)
                                    <a href="{{ route('vendor.service.lead-details', [$lead->id]) }}"
                                        class="dropdown-item" style="color:#18181b;">
                                        <i class="tio-visible-outlined"></i> View Details
                                    </a>
                                @endif
                                @if ($isConfirmed2 && !$isCompleted && !$isCancelled)
                                    @if (hasPermission('leads_manage', 'cancel'))
                                        <a onclick="cancelLead({{ $lead->id }}, {{ $lead->acc_id }})"
                                            class="dropdown-item text-danger" style="cursor:pointer;">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    @endif
                                @endif
                                @if (
                                    $lead->current_status === 'Confirmed' &&
                                        $lead->assigned_status !== 'Unassigned' &&
                                        $lead->assigned_type === 'staff' &&
                                        isset($lead->assigned_to))
                                    <a href="{{ route('vendor.track-location', [$lead->assigned_to]) }}"
                                        target="_blank" class="dropdown-item text-primary">
                                        <i class="tio-location-search"></i> Track Location
                                    </a>
                                @endif
                                @if (_isHospital() && ($isConfirmed || $isCompleted))
                                    @php $leadRx = \App\Models\Prescription::where('service_request_id',$lead->id)->first(); @endphp
                                    @if ($leadRx)
                                        <a href="{{ route('vendor.prescription.show', $leadRx->id) }}"
                                            class="dropdown-item text-success"><i class="tio-print"></i> View
                                            Prescription</a>
                                        @if (!$leadRx->is_finalized)
                                            <a href="{{ route('vendor.prescription.edit', $leadRx->id) }}"
                                                class="dropdown-item text-primary"><i class="tio-edit"></i> Edit
                                                Prescription</a>
                                        @endif
                                    @else
                                        <a href="{{ route('vendor.prescription.create', ['service_request_id' => $lead->id]) }}"
                                            class="dropdown-item text-success"><i class="tio-medicine"></i> Write
                                            Prescription</a>
                                    @endif
                                @endif
                                @if (!$canViewDetails && $isAcceptedReq && $user_details)
                                    <a href="#" class="dropdown-item text-primary"
                                        onclick="event.preventDefault(); $('#userModal-{{ $lead->id }}').modal('show');">
                                        <i class="fas fa-user"></i> User Details
                                    </a>
                                @endif
                                @if ($isAcceptedReq && hasPermission('leads_manage', 'alot'))
                                    <a href="#" class="dropdown-item text-secondary"
                                        onclick="event.preventDefault(); loadAssignmentHistory({{ $lead->id }});">
                                        <i class="tio-history"></i> Assignment History
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Divider --}}
            <div class="lc-divider"></div>

            {{-- Client row. The name is shown before acceptance too; the phone number below it
                 still is not — the contact detail is what accepting buys. --}}
            <div class="lc-client">
                @if ($user_details)
                    <div class="lc-avatar">{{ strtoupper(substr($user_details->f_name, 0, 1) . substr($user_details->l_name, 0, 1)) }}</div>
                    <div style="flex:1;min-width:0;">
                        <div class="lc-client-name">{{ trim($user_details->f_name . ' ' . $user_details->l_name) }}</div>
                    </div>
                @else
                    <div class="lc-avatar unknown" style="font-size:14px;">?</div>
                    <div style="flex:1;min-width:0;">
                        <div class="lc-client-unknown">Client not revealed</div>
                    </div>
                @endif
                <div class="lc-date">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                    {{ $fmtDate }}
                </div>
            </div>

            {{-- Phone actions --}}
            @if ($isAcceptedReq && $user_details && $user_details->phone)
                <div class="lc-phone-actions" onclick="event.stopPropagation()">

                    <button class="lc-btn-copy textToCopyBtn" data-phone="{{ $user_details->phone }}"
                        onclick="event.stopPropagation(); copyPhone(this)">
                        <span class="num">{{ $user_details->phone }}</span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                            <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1" />
                        </svg>
                    </button>
                    <a href="tel:{{ preg_replace('/\s+/', '', $user_details->phone) }}" class="lc-btn-call"
                        onclick="event.stopPropagation()">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.68A2 2 0 012 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z" />
                        </svg>
                        Call
                    </a>
                </div>
            @elseif (!$isAcceptedReq && $user_details && $user_details->phone)
                <div class="lc-phone-locked"
                    style="display:flex;align-items:center;gap:6px;padding:6px 10px;border:1px dashed #e4e4e7;border-radius:7px;font-size:11.5px;color:#a1a1aa;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0110 0v4" />
                    </svg>
                    Accept lead to reveal phone number
                </div>
            @endif

            {{-- Date + assigned --}}
            <div class="lc-meta">
                @if ($lead->assigned_status == 'Assigned' && isset($lead->assigned_to))
                    @php
                        if ($lead->assigned_type == 'staff') {
                            $empInfo = _getWhereOne('vendor_employees', ['id' => $lead->assigned_to]);
                        } else {
                            $empInfo = _getWhereOne('vendors', ['id' => \App\CentralLogics\Helpers::get_vendor_id()]);
                        }
                    @endphp
                    @if ($empInfo)
                        <div class="lc-assigned">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            <strong>{{ $empInfo->f_name . ' ' . $empInfo->l_name }}</strong>
                            <span
                                style="color:#a1a1aa;font-size:10px;">({{ !$lead->accepted_by_staff ? 'Pending' : ($lead->accepted_by_staff == 2 ? 'Rejected' : 'Accepted') }})</span>
                        </div>
                    @endif
                @endif
            </div>

            {{-- cancellation details --}}

            @if ($isCancelled)
            
                <div class="lc-cancelled-info">
                    <strong>Cancelled By:</strong>
                    {{ ucfirst($lead->cancelled_by) }}
                    @if ($lead->cancelled_by == 'staff')
                        #{{ $lead->cancelled_by_id }}
                    @endif
                    <br>
                    <strong>Reason:</strong>
                    {{ $lead->reason ?? 'N/A' }}
                </div>
            @endif

            {{-- Note --}}
            @if (!$isMissed && !$isCancelled)
                <div class="lc-notes-bar" onclick="event.stopPropagation()">
                    <div class="lc-note-input-row">
                        <input type="text" id="noteText-{{ $lead->id }}" class="lc-note-input"
                            placeholder="Note…" value="{{ $leadNote->note ?? '' }}"
                            oninput="scheduleNoteSave({{ $lead->id }})">
                        <input type="datetime-local" id="noteRemind-{{ $lead->id }}"
                            class="lc-note-remind-input"
                            value="{{ $leadNote?->remind_at ? $leadNote->remind_at->format('Y-m-d\TH:i') : '' }}"
                            onchange="scheduleNoteSave({{ $lead->id }})"
                            style="display:{{ $leadNote?->remind_at ? 'inline-block' : 'none' }};">
                        <button class="lc-note-bell-btn{{ $leadNote?->remind_at ? ' bell-active' : '' }}"
                            onclick="toggleRemindInput({{ $lead->id }})" title="Set reminder">🔔</button>
                    </div>
                </div>
            @endif

            {{-- Doc quick-links --}}
            @if (!$isMissed && $isAcceptedReq)
                @php
                    $isDone         = $isCompleted || $isCancelled;
                    $showQuotation  = hasAnyModulePermission(['leads_quotation']) && (!$isDone || $hasQuotation);
                    $showGatepass   = $isConfirmed2 && !$isCancelled && hasAnyModulePermission(['leads_gatepass']) && (!$isCompleted || $hasGatepass);
                    $showBill       = $isConfirmed2;
                @endphp
                @if ($showQuotation || $showGatepass || $showBill)
                    <div class="lc-doc-bar" onclick="event.stopPropagation()">
                        @if ($showQuotation)
                            <a href="{{ route('vendor.service.quotations', [$lead->id]) }}"
                                class="lc-doc-btn {{ $hasQuotation ? 'lc-doc-exists' : '' }}">
                                📄 Quotation
                            </a>
                        @endif
                        @if ($showGatepass)
                            <a href="{{ route('vendor.service.gatepass-details', [$lead->id]) }}"
                                class="lc-doc-btn {{ $hasGatepass ? 'lc-doc-exists' : '' }}">
                                📦 Gatepass
                            </a>
                        @endif
                        @if ($showBill)
                            @php $billPdf = _getServiceInvoice($lead->id); @endphp
                            @if ($billPdf)
                                <a href="{{ asset('storage/app/public/invoice/' . $billPdf) }}" target="_blank"
                                    class="lc-doc-btn lc-doc-exists" title="View bill PDF">
                                    🧾 Bill
                                </a>
                            @else
                                <a href="{{ route('vendor.business-settings.generate-bill', [$lead->id]) }}"
                                    class="lc-doc-btn {{ $invoiceStatus !== 'new' ? 'lc-doc-exists' : '' }}">
                                    🧾 Add Bill
                                </a>
                            @endif
                        @endif
                    </div>
                @endif
            @endif

        </div>{{-- /lc-body --}}

        {{-- Footer action pinned to bottom --}}
        @if (!$isMissed)
            @if (
                !$canViewDetails &&
                    !$isCancelled &&
                    !$isCompleted &&
                    !$isAcceptedReq &&
                    hasPermission('leads_manage', 'accept') &&
                    $canAccept &&
                    $lead->status == 'new')
                <div class="lc-footer" onclick="event.stopPropagation()">
                    <button type="button" class="btn-accept-lead" id="acceptBtn-{{ $lead->id }}"
                        onclick="event.stopPropagation(); acceptLead({{ $lead->id }}, this)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Accept Lead
                    </button>
                </div>
            @elseif ($isAcceptedReq && $user_details && $lead->current_status != 'Confirmed' && !$isCompleted && !$isCancelled)
                <div class="lc-footer" onclick="event.stopPropagation()">
                    <button type="button" class="btn-send-req {{ $currentServiceStatus ? '' : 'btn-send-req--blink' }}"
                        onclick="event.stopPropagation(); $('#userModal-{{ $lead->id }}').modal('show');">
                        {{ $currentServiceStatus ? 'View Confirmation Status' : 'Send Confirmation Request' }}
                    </button>
                </div>
            @elseif ($isCompleted && hasPermission('leads_manage', 'edit'))
                <div class="lc-footer" onclick="event.stopPropagation()">
                    <a href="{{ route('vendor.business-settings.generate-bill', [$lead->id]) }}" class="btn-gen-bill"
                        style="text-decoration:none;">
                        🧾 {{ $invoiceStatus === 'new' ? 'Add Bill' : 'Edit Bill' }}
                    </a>
                </div>
            @elseif (hasPermission('leads_manage', 'alot') &&
                    $isConfirmed &&
                    !$isCompleted &&
                    !$isCancelled)
                <div class="lc-footer lc-footer-row" onclick="event.stopPropagation()">
                    <button type="button" class="btn-send-req"
                        onclick="event.stopPropagation(); $('#assignModal-{{ $lead->id }}').modal('show');">
                        {{ $lead->assigned_status == 'Unassigned' ? '+ Assign' : '✎ Reassign' }}
                    </button>
                    @if ($lead->assigned_status == 'Assigned' && hasPermission('leads_manage', 'status_change'))
                        <select id="statusSelect-{{ $lead->id }}" class="lc-status-select"
                            data-service-id="{{ $lead->id }}" data-acc-id="{{ $lead->acc_id ?? '' }}"
                            onclick="event.stopPropagation()">
                            <option value="">Status…</option>
                            @foreach ($default_statuses as $st)
                                <option value="{{ $st->id }}"
                                    {{ $jobInfo && $jobInfo->status == $st->id ? 'selected' : '' }}>
                                    {{ $st->status }}</option>
                            @endforeach
                            @foreach ($statuses as $st)
                                <option value="{{ $st->id }}"
                                    {{ $jobInfo && $jobInfo->status == $st->id ? 'selected' : '' }}>
                                    {{ $st->status }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            @endif
        @endif

    </div>{{-- /lead-card --}}

    {{-- ── Assign modal ── --}}
    <div class="modal fade" id="assignModal-{{ $lead->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Assign Staff</h5>
                    </div>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    @if ($lead->assigned_status == 'Assigned')
                        @php
                            $empInfo = $lead->assigned_type == 'staff'
                                ? _getWhereOne('vendor_employees', ['id' => $lead->assigned_to])
                                : _getWhereOne('vendors', ['id' => \App\CentralLogics\Helpers::get_vendor_id()]);
                        @endphp
                        @if ($empInfo)
                            <div style="background:#fff7ed;border-radius:8px;padding:10px 14px;font-size:13px;font-weight:600;color:#9a3412;margin-bottom:14px;">
                                Currently: {{ $empInfo->f_name . ' ' . $empInfo->l_name }}
                                <span style="font-weight:400;color:#71717a;">({{ !$lead->accepted_by_staff ? 'Acceptance Pending' : ($lead->accepted_by_staff == 2 ? 'Rejected' : 'Accepted') }})</span>
                            </div>
                        @endif
                    @endif
                    <div class="form-group">
                        <label style="font-size:12px;font-weight:700;color:#52525b;">
                            {{ $lead->assigned_status == 'Assigned' ? 'Reassign' : 'Assign' }} To
                        </label>
                        <select id="staffSelect-{{ $lead->id }}" class="form-control js-select2-custom"
                            style="border-radius:8px;font-family:inherit;">
                            <option></option>
                            <option value="vendor">Self (Vendor)</option>
                            @foreach ($allStaff as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->f_name . ' ' . $staff->l_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn-lp"
                        onclick="saveAssignment({{ $lead->id }}, {{ $lead->acc_id }}, 'assignModal-{{ $lead->id }}', this)">
                        {{ $lead->assigned_status == 'Assigned' ? 'Reassign' : 'Assign' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── User details / confirmation modal ── --}}
    @if ($user_details)
        <div class="modal fade" id="userModal-{{ $lead->id }}" tabindex="-1" role="dialog"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">{{ $lead->item_name }}</h5>
                        </div>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="ci-box">
                            <div class="d-flex align-items-center mb-3">
                                <div class="ci-avatar mr-3">
                                    {{ strtoupper(substr($user_details->f_name, 0, 1)) }}</div>
                                <div>
                                    <h6 style="font-weight:800;margin:0;">
                                        {{ $user_details->f_name . ' ' . $user_details->l_name }}</h6>
                                    <small style="color:#a1a1aa;">Customer Details</small>
                                </div>
                            </div>
                            <div class="ci-contact-item">
                                <i class="tio-email"></i>
                                <div>
                                    <small>Email</small>
                                    <a href="mailto:{{ $user_details->email }}"
                                        class="val">{{ $user_details->email }}</a>
                                </div>
                            </div>
                            <div class="ci-contact-item">
                                <i class="tio-call"></i>
                                <div class="flex-grow-1">
                                    <small>Mobile</small>
                                    <span class="val textToCopy">{{ $user_details->phone }}</span>
                                </div>
                                <button class="copy-btn"><i class="tio-copy"></i></button>
                            </div>
                            <div class="mt-2" style="display:flex;gap:8px;">
                                <a href="tel:{{ preg_replace('/\s+/', '', $user_details->phone) }}" class="btn-lp"
                                    style="flex:1;justify-content:center;text-decoration:none;">
                                    📞 Call
                                </a>
                            </div>
                        </div>

                        @if (!_getCurrentServiceStatus($lead->id))
                            @if (hasPermission('leads_manage', 'send_confirmation_request'))
                                <div class="action-form">
                                    <div class="form-group">
                                        <label for="lp_{{ $lead->id }}">Visiting Charges</label>
                                        <div class="input-group mt-1">
                                            <div class="input-group-prepend"><span class="input-group-text"><i
                                                        class="tio-money"></i></span></div>
                                            <input type="number" id="lp_{{ $lead->id }}" class="form-control"
                                                placeholder="Enter amount">
                                        </div>
                                    </div>
                                    <button type="button" class="btn-lp" style="width:100%;justify-content:center;"
                                        onclick="sendConfirmation({{ $lead->id }}, this)">
                                        ✓ Send Confirmation Request
                                    </button>
                                </div>
                            @endif
                        @else
                            <div class="status-box">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="tio-checkmark-circle-outlined"></i>
                                        <div>
                                            <small style="color:#a1a1aa;display:block;">Status</small>
                                            <strong>{{ _getCurrentServiceStatus($lead->id) }}</strong>
                                        </div>
                                    </div>
                                    @if (_getCurrentServiceStatus($lead->id) == 'Confirmed')
                                        <a href="{{ route('vendor.service.cancel', [$lead->id]) }}"
                                            class="btn btn-outline-danger btn-sm"><i class="tio-clear"></i> Cancel</a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-lp-outline" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Assignment History modal ── --}}
    @if ($isAcceptedReq && hasPermission('leads_manage', 'alot'))
        <div class="modal fade" id="assignHistoryModal-{{ $lead->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header" style="color:#fff;">
                        <h5 class="modal-title">Assignment History</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-1" id="assignHistoryBody-{{ $lead->id }}">
                        <div class="text-center py-3"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @php
        if (isset($statusCounts)) {
            addStatus(
                $statusCounts,
                $isMissed
                    ? 'missed'
                    : ($isCompleted
                        ? 'completed'
                        : ($isCancelled
                            ? 'cancelled'
                            : ($isAcceptedReq
                                ? 'accepted'
                                : 'new'))),
            );
        }
    @endphp
</div>{{-- /lead-wrap --}}
