@extends('layouts.admin.app')
@section('title', $salesQuery->ref_no . ' — ' . $salesQuery->contact_name)

@push('css_or_js')
<style>
    .badge-new          { background:#5bc4d8; }
    .badge-in_progress  { background:#f4a65e; }
    .badge-proposal_sent{ background:#a585d4; }
    .badge-converted    { background:#52c48a; }
    .badge-lost         { background:#e87878; }
    .badge-on_hold      { background:#a0b3bf; }
    .badge-high         { background:#e87878; }
    .badge-medium       { background:#f4a65e; }
    .badge-low          { background:#52c48a; }
    .badge-pending      { background:#f4a65e; }
    .badge-done         { background:#52c48a; }
    .badge-missed       { background:#e87878; }
    .badge-cancelled    { background:#a0b3bf; }
    .detail-label { font-size:.75rem; font-weight:600; color:#8c98a4; text-transform:uppercase; letter-spacing:.04em; }
    .quick-status-select {
        color:#fff; border:none; appearance:none; -webkit-appearance:none;
        padding:0 12px; height:31px; line-height:31px;
        font-size:.8rem; font-weight:600; border-radius:5px; cursor:pointer;
        box-shadow:none; outline:none;
    }
    .quick-status-select option { color:#333; background:#fff; font-weight:normal; }
    .status-bg-new            { background:#5bc4d8; }
    .status-bg-in_progress    { background:#f4a65e; }
    .status-bg-proposal_sent  { background:#a585d4; }
    .status-bg-converted      { background:#52c48a; }
    .status-bg-lost           { background:#e87878; }
    .status-bg-on_hold        { background:#a0b3bf; }

    /* Soft header action buttons */
    .btn-soft-secondary { background:#eef0f3; color:#5c6878; border:1px solid #dee2e6; }
    .btn-soft-secondary:hover { background:#e2e6ea; color:#3d4a58; }
    .btn-soft-primary   { background:#e8f0fe; color:#3d6fd4; border:1px solid #c9d9f7; }
    .btn-soft-primary:hover { background:#d6e6fd; color:#2a55b8; }
    .btn-soft-success   { background:#e6f6ed; color:#2e9e5e; border:1px solid #b8e4cc; }
    .btn-soft-success:hover { background:#d1efdf; color:#1f7a47; }

    /* Ticket status badges */
    .badge-ticket-open        { background:#5bc4d8; }
    .badge-ticket-in_progress { background:#f4a65e; }
    .badge-ticket-resolved    { background:#52c48a; }
    .badge-ticket-closed      { background:#a0b3bf; }
    .badge-ticket-on_hold     { background:#a585d4; }

    /* Activity timeline */
    .activity-timeline { position:relative; padding-left:32px; }
    .activity-timeline::before { content:''; position:absolute; left:11px; top:0; bottom:0; width:2px; background:#e7eaf3; }
    .activity-item { position:relative; margin-bottom:16px; }
    .activity-dot  { position:absolute; left:-32px; width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.65rem; color:#fff; top:1px; }
    .activity-body { background:#f8f9fa; border:1px solid #e7eaf3; border-radius:6px; padding:8px 12px; font-size:.82rem; }
    .activity-meta { font-size:.74rem; color:#8c98a4; margin-top:2px; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-header-title">{{ $salesQuery->ref_no }} — {{ $salesQuery->contact_name }}</h1>
                <ol class="breadcrumb breadcrumb-no-gutter">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ translate('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.sales-crm.query.index') }}">{{ translate('Sales Queries') }}</a></li>
                    <li class="breadcrumb-item active">{{ $salesQuery->ref_no }}</li>
                </ol>
            </div>
            <div class="col-auto d-flex align-items-center">
                <select id="quickStatusSelect" class="mr-2 quick-status-select status-bg-{{ $salesQuery->status }}" style="min-width:120px;">
                    @foreach(\App\Modules\SalesCRM\Models\SalesQuery::STATUSES as $s)
                        <option value="{{ $s }}" {{ $salesQuery->status == $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
                <a href="{{ route('admin.sales-crm.query.edit', $salesQuery->id) }}" class="btn btn-soft-secondary btn-sm mr-1">
                    <i class="tio-edit mr-1"></i>{{ translate('Edit') }}
                </a>
                <a href="{{ route('admin.sales-crm.followup.create', ['query_id' => $salesQuery->id]) }}" class="btn btn-soft-primary btn-sm mr-1">
                    <i class="tio-add mr-1"></i>{{ translate('Add Follow-up') }}
                </a>
                <a href="{{ route('admin.quotation.add', ['from_query' => $salesQuery->id]) }}" class="btn btn-soft-success btn-sm">
                    <i class="tio-file-text-outlined mr-1"></i>{{ translate('Create Quotation') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Left: details + status --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-6">
                            <p class="detail-label mb-1">{{ translate('Status') }}</p>
                            <span class="badge badge-{{ $salesQuery->status }} text-white">{{ ucfirst(str_replace('_', ' ', $salesQuery->status)) }}</span>
                        </div>
                        <div class="col-6">
                            <p class="detail-label mb-1">{{ translate('Priority') }}</p>
                            <span class="badge badge-{{ $salesQuery->priority }} text-white">{{ ucfirst($salesQuery->priority) }}</span>
                        </div>
                        @if($salesQuery->status === 'lost' && $salesQuery->lost_reason)
                        <div class="col-12">
                            <p class="detail-label mb-1">{{ translate('Lost Reason') }}</p>
                            <span class="text-danger font-weight-bold">
                                {{ ucfirst(str_replace('_', ' ', $salesQuery->lost_reason)) }}
                                @if($salesQuery->lost_reason === 'other' && $salesQuery->lost_reason_other)
                                    — {{ $salesQuery->lost_reason_other }}
                                @endif
                            </span>
                        </div>
                        @endif
                        <div class="col-6">
                            <p class="detail-label mb-1">{{ translate('Source') }}</p>
                            <span>{{ ucfirst($salesQuery->source) }}</span>
                        </div>
                        <div class="col-6">
                            <p class="detail-label mb-1">{{ translate('Zone') }}</p>
                            <span>{{ $salesQuery->zone?->name ?? '—' }}</span>
                        </div>
                        @if($salesQuery->sub_module)
                        <div class="col-12">
                            <p class="detail-label mb-1">{{ translate('Module / Service') }}</p>
                            <span class="badge badge-soft-info">{{ $salesQuery->sub_module }}</span>
                        </div>
                        @endif
                        <div class="col-12">
                            <p class="detail-label mb-1">{{ translate('Contact') }}</p>
                            <div class="font-weight-bold">{{ $salesQuery->contact_name }}</div>
                            @if($salesQuery->company)<small class="text-muted d-block">{{ $salesQuery->company }}</small>@endif
                            <div><i class="tio-call-outlined mr-1"></i>{{ $salesQuery->phone }}</div>
                            @if($salesQuery->email)<div><i class="tio-email-outlined mr-1"></i>{{ $salesQuery->email }}</div>@endif
                        </div>
                        <div class="col-12">
                            <p class="detail-label mb-1">{{ translate('Assigned To') }}</p>
                            <span>{{ $salesQuery->assignedAdmin ? ($salesQuery->assignedAdmin->f_name . ' ' . $salesQuery->assignedAdmin->l_name) : '—' }}</span>
                        </div>
                        <div class="col-12">
                            <p class="detail-label mb-1">{{ translate('Created') }}</p>
                            <span>{{ $salesQuery->created_at->format('d M Y, h:i A') }}</span>
                        </div>
                    </div>

                    <hr>
                    <p class="detail-label mb-2">{{ translate('Update Status') }}</p>
                    <form action="{{ route('admin.sales-crm.query.status', $salesQuery->id) }}" method="POST" id="status-form">
                        @csrf
                        <div class="d-flex mb-2">
                            <select name="status" class="form-control form-control-sm" id="status-select" onchange="toggleLostReason(this.value)">
                                @foreach(\App\Modules\SalesCRM\Models\SalesQuery::STATUSES as $s)
                                    <option value="{{ $s }}" {{ $salesQuery->status == $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-primary ml-2">{{ translate('Set') }}</button>
                        </div>
                        <div id="lost-reason-wrap" class="{{ $salesQuery->status === 'lost' ? '' : 'd-none' }}">
                            <select name="lost_reason" id="lost-reason-select" class="form-control form-control-sm mb-1" onchange="toggleOtherReason(this.value)">
                                <option value="">— {{ translate('Select reason') }} —</option>
                                @foreach(\App\Modules\SalesCRM\Models\SalesQuery::LOST_REASONS as $r)
                                    <option value="{{ $r }}" {{ $salesQuery->lost_reason === $r ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $r)) }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="lost_reason_other" id="lost-reason-other"
                                class="form-control form-control-sm {{ ($salesQuery->lost_reason !== 'other') ? 'd-none' : '' }}"
                                placeholder="{{ translate('Specify...') }}"
                                value="{{ $salesQuery->lost_reason_other }}">
                        </div>
                    </form>
                </div>
            </div>

            @if($salesQuery->description)
            <div class="card mb-3">
                <div class="card-header py-2"><h6 class="mb-0">{{ translate('Description') }}</h6></div>
                <div class="card-body"><p class="mb-0" style="white-space:pre-wrap">{{ $salesQuery->description }}</p></div>
            </div>
            @endif

            @if($salesQuery->notes)
            <div class="card mb-3">
                <div class="card-header py-2"><h6 class="mb-0">{{ translate('Internal Notes') }}</h6></div>
                <div class="card-body"><p class="mb-0" style="white-space:pre-wrap">{{ $salesQuery->notes }}</p></div>
            </div>
            @endif
        </div>

        {{-- Right: Activity log + Follow-ups --}}
        <div class="col-lg-8">

            {{-- Activity Log --}}
            <div class="card mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ translate('Activity Log') }} <span class="badge badge-soft-dark ml-1">{{ $salesQuery->activities->count() }}</span></h6>
                </div>

                {{-- Log new activity --}}
                <div class="card-body border-bottom py-3">
                    <form action="{{ route('admin.sales-crm.query.activity', $salesQuery->id) }}" method="POST" class="row gx-2">
                        @csrf
                        <div class="col-md-3">
                            <select name="type" class="form-control form-control-sm">
                                @foreach($activityTypes as $t)
                                    <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-7">
                            <input type="text" name="description" class="form-control form-control-sm @error('description') is-invalid @enderror"
                                placeholder="{{ translate('What happened? e.g. Called, discussed pricing...') }}" required>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary btn-sm btn-block">Save</button>
                        </div>
                    </form>
                </div>

                {{-- Timeline --}}
                <div class="card-body" style="max-height:380px; overflow-y:auto">
                    @php
                        $icons  = \App\Modules\SalesCRM\Models\QueryActivity::TYPE_ICONS;
                        $colors = \App\Modules\SalesCRM\Models\QueryActivity::TYPE_COLORS;
                    @endphp
                    @forelse($salesQuery->activities as $act)
                    <div class="activity-timeline">
                        <div class="activity-item">
                            <div class="activity-dot" style="background:{{ $colors[$act->type] ?? '#6c757d' }}">
                                <i class="{{ $icons[$act->type] ?? 'tio-dot' }}"></i>
                            </div>
                            <div class="activity-body">
                                <div>{{ $act->description }}</div>
                                <div class="activity-meta">
                                    <span class="mr-2">{{ ucfirst($act->type) }}</span>·
                                    <span class="mx-2">{{ $act->admin?->f_name }}</span>·
                                    <span class="ml-1">{{ $act->created_at->format('d M Y, h:i A') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-muted py-3 mb-0">{{ translate('No activity yet.') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- Quotations --}}
            <div class="card mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ translate('Quotations') }} <span class="badge badge-soft-dark ml-1">{{ $salesQuery->quotations->count() }}</span></h6>
                    <a href="{{ route('admin.quotation.add', ['from_query' => $salesQuery->id]) }}" class="btn btn-xs btn-success">
                        <i class="tio-add"></i> {{ translate('Create') }}
                    </a>
                </div>
                <div style="max-height:320px; overflow-y:auto;">
                @forelse($salesQuery->quotations as $quote)
                <div class="card-body border-bottom py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="font-weight-bold">#{{ $quote->quotation_id }}</span>
                            @if($quote->subject)
                                <span class="text-muted ml-2">{{ $quote->subject }}</span>
                            @endif
                            <div class="mt-1">
                                <span class="badge badge-soft-{{ $quote->status === 'Accepted' ? 'success' : ($quote->status === 'Declined' ? 'danger' : ($quote->status === 'Converted to Bill' ? 'info' : 'secondary')) }}">
                                    {{ $quote->status ?? 'New' }}
                                </span>
                                @if($quote->quote_detail?->total_amount)
                                    <small class="text-muted ml-2">₹{{ number_format($quote->quote_detail->total_amount, 2) }}</small>
                                @endif
                                <small class="text-muted ml-2">{{ $quote->created_at->format('d M Y') }}</small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.quotation.manage', $quote->id) }}" class="btn btn-xs btn-outline-primary">
                                <i class="tio-edit"></i> Manage
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="card-body text-center text-muted py-3">{{ translate('No quotations yet.') }}</div>
                @endforelse
                </div>
            </div>

            {{-- Support Tickets --}}
            <div class="card mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ translate('Support Tickets') }} <span class="badge badge-soft-dark ml-1">{{ $salesQuery->tickets->count() }}</span></h6>
                    <a href="{{ route('admin.sales-crm.ticket.create', ['from_query' => $salesQuery->id]) }}" class="btn btn-xs btn-soft-warning" style="background:#fff4e0;color:#c47c0a;border:1px solid #f5d68a;">
                        <i class="tio-add"></i> {{ translate('New Ticket') }}
                    </a>
                </div>
                @forelse($salesQuery->tickets as $ticket)
                <div class="card-body border-bottom py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="font-weight-bold text-dark">{{ $ticket->ticket_no }}</span>
                            <span class="text-muted ml-2" style="font-size:.85rem;">{{ $ticket->subject }}</span>
                            <div class="mt-1">
                                <span class="badge badge-ticket-{{ $ticket->status }} text-white" style="font-size:.72rem;">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                                <span class="badge badge-{{ $ticket->priority === 'urgent' ? 'high' : $ticket->priority }} text-white ml-1" style="font-size:.72rem;">{{ ucfirst($ticket->priority) }}</span>
                                @if($ticket->assignedAdmin)
                                    <small class="text-muted ml-2"><i class="tio-user mr-1"></i>{{ $ticket->assignedAdmin->f_name }}</small>
                                @endif
                                <small class="text-muted ml-2">{{ $ticket->created_at->format('d M Y') }}</small>
                            </div>
                        </div>
                        <a href="{{ route('admin.sales-crm.ticket.show', $ticket->id) }}" class="btn btn-xs btn-outline-secondary flex-shrink-0">
                            <i class="tio-open-in-new"></i> {{ translate('View') }}
                        </a>
                    </div>
                </div>
                @empty
                <div class="card-body text-center text-muted py-3">{{ translate('No support tickets yet.') }}</div>
                @endforelse
            </div>

            {{-- Follow-ups --}}
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ translate('Follow-ups') }} <span class="badge badge-soft-dark ml-1">{{ $salesQuery->followUps->count() }}</span></h6>
                    <a href="{{ route('admin.sales-crm.followup.create', ['query_id' => $salesQuery->id]) }}" class="btn btn-xs btn-primary">
                        <i class="tio-add"></i> {{ translate('Add') }}
                    </a>
                </div>
                @forelse($salesQuery->followUps->sortByDesc('due_date') as $fu)
                <div class="card-body border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="font-weight-bold">{{ $fu->title }}</span>
                            <span class="badge badge-{{ $fu->status }} text-white ml-2">{{ ucfirst($fu->status) }}</span>
                            <div class="mt-1">
                                <small class="text-muted"><i class="tio-calendar mr-1"></i>{{ \Carbon\Carbon::parse($fu->due_date)->format('d M Y') }}{{ $fu->due_time ? ' ' . \Carbon\Carbon::parse($fu->due_time)->format('h:i A') : '' }}</small>
                                <small class="text-muted ml-3"><i class="tio-user mr-1"></i>{{ $fu->admin?->f_name }}</small>
                            </div>
                            @if($fu->notes)<p class="mt-1 mb-0 text-muted small" style="white-space:pre-wrap">{{ $fu->notes }}</p>@endif
                        </div>
                        @if($fu->status === 'pending')
                        <form action="{{ route('admin.sales-crm.followup.status', $fu->id) }}" method="POST" class="d-flex align-items-center">
                            @csrf
                            <input type="hidden" name="status" value="done">
                            <button class="btn btn-xs btn-success">{{ translate('Mark Done') }}</button>
                        </form>
                        @endif
                    </div>
                </div>
                @empty
                <div class="card-body text-center text-muted py-4">{{ translate('No follow-ups yet.') }}</div>
                @endforelse
            </div>

        </div>
    </div>
</div>

{{-- Lost reason modal for quick status change --}}
<div class="modal fade" id="lostReasonModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Mark as Lost — Reason</h6>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <select id="quickLostReason" class="form-control form-control-sm mb-2">
                    <option value="">— Select reason —</option>
                    @foreach(\App\Modules\SalesCRM\Models\SalesQuery::LOST_REASONS as $r)
                        <option value="{{ $r }}">{{ ucfirst(str_replace('_', ' ', $r)) }}</option>
                    @endforeach
                </select>
                <input type="text" id="quickLostReasonOther" class="form-control form-control-sm d-none" placeholder="Specify...">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" id="lostReasonCancel">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="lostReasonConfirm">Confirm Lost</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
var _prevQuickStatus = '{{ $salesQuery->status }}';
var _statusUrl = '{{ route('admin.sales-crm.query.status', $salesQuery->id) }}';
var _csrf = '{{ csrf_token() }}';

var statusColors = {
    'new':           '#3db8cc',
    'in_progress':   '#f0943a',
    'proposal_sent': '#8967c5',
    'converted':     '#3dab6e',
    'lost':          '#e05f5f',
    'on_hold':       '#8e9fae',
};

function applyQuickSelectColor(val) {
    var $sel = $('#quickStatusSelect');
    $sel.css('background', statusColors[val] || '#6c757d');
    $sel.removeClass(function(i, c){ return (c.match(/\bstatus-bg-\S+/g) || []).join(' '); });
}

function submitQuickStatus(status, lostReason, lostReasonOther) {
    $.ajax({
        url: _statusUrl,
        method: 'POST',
        data: {
            _token: _csrf,
            status: status,
            lost_reason: lostReason || '',
            lost_reason_other: lostReasonOther || '',
        },
        success: function() {
            _prevQuickStatus = status;
            applyQuickSelectColor(status);
            // update left panel badge
            var label = status.replace(/_/g, ' ').replace(/\b\w/g, function(c){ return c.toUpperCase(); });
            $('.badge-{{ $salesQuery->status }}').attr('class', 'badge badge-' + status + ' text-white').text(label);
        },
        error: function() {
            $('#quickStatusSelect').val(_prevQuickStatus);
            applyQuickSelectColor(_prevQuickStatus);
        }
    });
}

$('#quickStatusSelect').on('change', function() {
    var newStatus = $(this).val();
    if (newStatus === 'lost') {
        $('#lostReasonModal').modal('show');
    } else {
        submitQuickStatus(newStatus, null, null);
    }
});

$('#quickLostReason').on('change', function() {
    if ($(this).val() === 'other') {
        $('#quickLostReasonOther').removeClass('d-none');
    } else {
        $('#quickLostReasonOther').addClass('d-none').val('');
    }
});

$('#lostReasonConfirm').on('click', function() {
    var reason = $('#quickLostReason').val();
    if (!reason) { alert('Please select a reason.'); return; }
    var other = $('#quickLostReasonOther').val();
    $('#lostReasonModal').modal('hide');
    submitQuickStatus('lost', reason, other);
});

$('#lostReasonCancel').on('click', function() {
    $('#quickStatusSelect').val(_prevQuickStatus);
    applyQuickSelectColor(_prevQuickStatus);
    $('#lostReasonModal').modal('hide');
    $('#quickLostReason').val('');
    $('#quickLostReasonOther').addClass('d-none').val('');
});

// Left panel form helpers (unchanged)
function toggleLostReason(val) {
    var wrap = document.getElementById('lost-reason-wrap');
    if (val === 'lost') { wrap.classList.remove('d-none'); }
    else { wrap.classList.add('d-none'); }
}
function toggleOtherReason(val) {
    var el = document.getElementById('lost-reason-other');
    if (val === 'other') { el.classList.remove('d-none'); }
    else { el.classList.add('d-none'); }
}
@if(request('highlight_lost'))
document.getElementById('status-select').value = 'lost';
toggleLostReason('lost');
document.getElementById('status-select').closest('.card-body').scrollIntoView({behavior:'smooth'});
@endif
</script>
@endpush
