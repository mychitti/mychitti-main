@extends('layouts.admin.app')
@section('title', translate('Sales Pipeline'))

@push('css_or_js')
<style>
    .pipeline-wrap   { display:flex; gap:14px; overflow-x:auto; padding-bottom:16px; align-items:flex-start; }
    .pipeline-col    { min-width:240px; width:240px; flex-shrink:0; }
    .pipeline-header { border-radius:6px 6px 0 0; padding:10px 14px; color:#fff; display:flex; justify-content:space-between; align-items:center; }
    .pipeline-body   { background:#f0f2f5; border:1px solid #e7eaf3; border-top:none; border-radius:0 0 6px 6px;
                       min-height:120px; max-height:72vh; overflow-y:auto; padding:8px; transition:background .15s; }
    .pipeline-body.drag-over { background:#dde3f0; }
    .pcard           { background:#fff; border:1px solid #e7eaf3; border-radius:6px; padding:10px 12px;
                       margin-bottom:8px; font-size:.82rem; cursor:grab; user-select:none; }
    .pcard:active    { cursor:grabbing; }
    .pcard.sortable-ghost  { opacity:.4; }
    .pcard.sortable-chosen { box-shadow:0 4px 12px rgba(0,0,0,.15); }
    .pcard-ref  { font-weight:700; font-size:.78rem; color:#677788; }
    .pcard-name { font-weight:600; margin:2px 0; }
    .pcard-meta { color:#8c98a4; font-size:.75rem; }
    .badge-high   { background:#dc3545; }
    .badge-medium { background:#fd7e14; }
    .badge-low    { background:#28a745; }
    .col-count    { transition:all .15s; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-header-title">{{ translate('Sales Pipeline') }}</h1>
                <ol class="breadcrumb breadcrumb-no-gutter">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ translate('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ translate('Pipeline') }}</li>
                </ol>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.sales-crm.query.create') }}" class="btn btn-primary btn-sm">
                    <i class="tio-add mr-1"></i>{{ translate('Add Query') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="row gx-2 mb-3 align-items-end">
        <div class="col-auto">
            <select name="assigned_admin_id" class="form-control form-control-sm">
                <option value="">{{ translate('All Assignees') }}</option>
                @foreach($admins as $a)
                    <option value="{{ $a->id }}" {{ request('assigned_admin_id') == $a->id ? 'selected' : '' }}>{{ $a->f_name }} {{ $a->l_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <select name="priority" class="form-control form-control-sm">
                <option value="">{{ translate('All Priorities') }}</option>
                @foreach($priorities as $p)
                    <option value="{{ $p }}" {{ request('priority') == $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-secondary btn-sm">{{ translate('Filter') }}</button>
            <a href="{{ route('admin.sales-crm.pipeline') }}" class="btn btn-white btn-sm">{{ translate('Reset') }}</a>
        </div>
    </form>

    {{-- Kanban board --}}
    <div class="pipeline-wrap" id="pipeline-board">
        @foreach($columns as $status => $col)
        @php $cards = $grouped->get($status, collect()); @endphp
        <div class="pipeline-col">
            <div class="pipeline-header" style="background:{{ $col['color'] }}">
                <span style="font-size:.85rem; font-weight:600">{{ translate($col['label']) }}</span>
                <span class="badge badge-light text-dark col-count" id="count-{{ $status }}">{{ $cards->count() }}</span>
            </div>
            <div class="pipeline-body"
                 data-status="{{ $status }}"
                 data-color="{{ $col['color'] }}"
                 id="col-{{ $status }}">
                @forelse($cards as $q)
                <div class="pcard"
                     data-id="{{ $q->id }}"
                     data-status="{{ $q->status }}"
                     data-ref="{{ $q->ref_no }}"
                     data-name="{{ $q->contact_name }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="pcard-ref">{{ $q->ref_no }}</span>
                        <span class="badge badge-{{ $q->priority }} text-white" style="font-size:.65rem">{{ ucfirst($q->priority) }}</span>
                    </div>
                    <div class="pcard-name">
                        <a href="{{ route('admin.sales-crm.query.show', $q->id) }}" class="text-dark" onclick="event.stopPropagation()">{{ $q->contact_name }}</a>
                    </div>
                    @if($q->company)<div class="pcard-meta">{{ $q->company }}</div>@endif
                    <div class="pcard-meta"><i class="tio-call-outlined" style="font-size:.75rem"></i> {{ $q->phone }}</div>
                    @if($q->assignedAdmin)
                    <div class="pcard-meta"><i class="tio-user" style="font-size:.75rem"></i> {{ $q->assignedAdmin->f_name }}</div>
                    @endif
                </div>
                @empty
                <p class="text-center text-muted small py-4 mb-0 empty-placeholder">—</p>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Lost Reason Modal --}}
<div class="modal fade" id="lostReasonModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-3">
                <h6 class="modal-title">{{ translate('Why was this lost?') }}</h6>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-2">
                    <select id="lost-reason-select" class="form-control">
                        <option value="">— {{ translate('Select reason') }} —</option>
                        @foreach(\App\Modules\SalesCRM\Models\SalesQuery::LOST_REASONS as $r)
                            <option value="{{ $r }}">{{ ucfirst(str_replace('_', ' ', $r)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="other-reason-wrap" class="d-none">
                    <input type="text" id="lost-reason-other" class="form-control" placeholder="{{ translate('Specify...') }}">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" id="lost-cancel-btn">{{ translate('Cancel') }}</button>
                <button type="button" class="btn btn-danger btn-sm" id="lost-confirm-btn">{{ translate('Confirm') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Toast container --}}
<div id="drag-toast" style="position:fixed;bottom:20px;right:20px;z-index:9999;display:none">
    <div class="alert mb-0 py-2 px-3" id="drag-toast-msg" style="font-size:.85rem"></div>
</div>
@endsection

@push('script_2')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    var statusUrl  = '{{ url('admin/sales-crm/queries') }}';
    var csrfToken  = '{{ csrf_token() }}';

    var pendingCard   = null;
    var pendingTarget = null;
    var pendingOrigin = null;

    // Init sortable on every column
    document.querySelectorAll('.pipeline-body').forEach(function (col) {
        Sortable.create(col, {
            group:     'pipeline',
            animation: 150,
            ghostClass:  'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass:   'sortable-drag',
            onEnd: function (evt) {
                var card       = evt.item;
                var newStatus  = evt.to.dataset.status;
                var oldStatus  = card.dataset.status;

                if (newStatus === oldStatus) return;

                if (newStatus === 'lost') {
                    // Hold card reference, show modal
                    pendingCard   = card;
                    pendingTarget = evt.to;
                    pendingOrigin = evt.from;
                    resetLostModal();
                    $('#lostReasonModal').modal('show');
                } else {
                    updateStatus(card, newStatus, null, null);
                }
            }
        });
    });

    // Lost modal confirm
    document.getElementById('lost-confirm-btn').addEventListener('click', function () {
        var reason      = document.getElementById('lost-reason-select').value;
        var reasonOther = document.getElementById('lost-reason-other').value;

        if (!reason) {
            document.getElementById('lost-reason-select').classList.add('is-invalid');
            return;
        }
        $('#lostReasonModal').modal('hide');
        updateStatus(pendingCard, 'lost', reason, reasonOther);
    });

    // Cancel — revert card to original column
    document.getElementById('lost-cancel-btn').addEventListener('click', function () {
        $('#lostReasonModal').modal('hide');
        if (pendingCard && pendingOrigin) {
            pendingOrigin.appendChild(pendingCard);
            refreshCounts();
        }
        pendingCard = pendingTarget = pendingOrigin = null;
    });

    $('#lostReasonModal').on('hidden.bs.modal', function () {
        // If modal closed without confirming, revert
        if (pendingCard) {
            if (pendingOrigin) pendingOrigin.appendChild(pendingCard);
            refreshCounts();
            pendingCard = pendingTarget = pendingOrigin = null;
        }
    });

    // Show/hide "other" text box
    document.getElementById('lost-reason-select').addEventListener('change', function () {
        var wrap = document.getElementById('other-reason-wrap');
        if (this.value === 'other') {
            wrap.classList.remove('d-none');
        } else {
            wrap.classList.add('d-none');
        }
        this.classList.remove('is-invalid');
    });

    function resetLostModal() {
        document.getElementById('lost-reason-select').value = '';
        document.getElementById('lost-reason-select').classList.remove('is-invalid');
        document.getElementById('lost-reason-other').value = '';
        document.getElementById('other-reason-wrap').classList.add('d-none');
    }

    function updateStatus(card, newStatus, lostReason, lostReasonOther) {
        var id   = card.dataset.id;
        var body = { status: newStatus, _token: csrfToken };
        if (lostReason)      body.lost_reason       = lostReason;
        if (lostReasonOther) body.lost_reason_other = lostReasonOther;

        $.ajax({
            url:  statusUrl + '/' + id + '/status',
            type: 'POST',
            data: body,
            success: function () {
                card.dataset.status = newStatus;
                refreshCounts();
                showToast('Moved to ' + newStatus.replace('_', ' '), 'success');
            },
            error: function () {
                // Revert
                var origin = document.querySelector('[data-status="' + card.dataset.status + '"]');
                if (origin) origin.appendChild(card);
                refreshCounts();
                showToast('Failed to update status', 'danger');
            }
        });
    }

    function refreshCounts() {
        document.querySelectorAll('.pipeline-body').forEach(function (col) {
            var status  = col.dataset.status;
            var count   = col.querySelectorAll('.pcard').length;
            var counter = document.getElementById('count-' + status);
            if (counter) counter.textContent = count;

            // Show/hide empty placeholder
            var placeholder = col.querySelector('.empty-placeholder');
            if (count === 0) {
                if (!placeholder) {
                    var p = document.createElement('p');
                    p.className = 'text-center text-muted small py-4 mb-0 empty-placeholder';
                    p.textContent = '—';
                    col.appendChild(p);
                }
            } else if (placeholder) {
                placeholder.remove();
            }
        });
    }

    function showToast(msg, type) {
        var toast = document.getElementById('drag-toast');
        var msgEl = document.getElementById('drag-toast-msg');
        msgEl.className = 'alert mb-0 py-2 px-3 alert-' + type;
        msgEl.textContent = msg;
        toast.style.display = 'block';
        setTimeout(function () { toast.style.display = 'none'; }, 2500);
    }
})();
</script>
@endpush
