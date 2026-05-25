@extends('layouts.admin.app')
@section('title', translate('Follow-ups'))

@push('css_or_js')
<link rel="stylesheet" href="{{ asset('public/assets/admin/vendor/fontawesome-free/css/all.min.css') }}">
<style>
    .badge-pending   { background:#fd7e14; }
    .badge-done      { background:#28a745; }
    .badge-missed    { background:#dc3545; }
    .badge-cancelled { background:#6c757d; }

    .fu-count-card {
        border-radius:10px; padding:10px 16px; text-decoration:none; display:inline-flex;
        align-items:center; gap:12px; border:1px solid transparent; transition:box-shadow .15s;
        width:fit-content;
    }
    .fu-count-card:hover { box-shadow:0 2px 10px rgba(0,0,0,.08); text-decoration:none; }
    .fu-count-card .fu-icon { width:38px; height:38px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .fu-count-card .fu-num { font-size:1.3rem; font-weight:700; line-height:1; }
    .fu-count-card .fu-lbl { font-size:.75rem; margin-top:2px; font-weight:500; }
    .fu-count-card.active-filter { box-shadow:0 2px 10px rgba(0,0,0,.1); font-weight:700; }

    /* overdue indicator — just a left border, no full-row blush */
    .fu-overdue-row td:first-child { border-left: 3px solid #dc3545; }

    /* three-dot menu */
    .fu-menu { position:relative; display:inline-block; }
    .fu-menu-btn {
        width:30px; height:30px; border:none; border-radius:7px;
        background:none; cursor:pointer; display:flex; align-items:center; justify-content:center;
        font-size:13px; color:#71717a; transition: background .15s;
        line-height:1;
    }
    .fu-menu-btn:hover { background:#f4f4f5; }
    .fu-menu .dropdown-menu {
        min-width:170px; border-radius:10px; border:1px solid #e4e4e7;
        box-shadow:0 8px 24px rgba(0,0,0,.11); padding:4px 0;
        top:34px; right:0; left:auto;
    }
    .fu-menu .dropdown-item {
        font-size:13px; font-weight:500; padding:9px 14px;
        display:flex; align-items:center; gap:8px;
    }
    .fu-menu .dropdown-item:hover { background:#fafafa; }
    .fu-menu .dropdown-divider { margin:3px 0; }

    /* assign history timeline */
    .assign-timeline { border-left:2px solid #e7eaf3; padding-left:14px; }
    .assign-timeline .ati { position:relative; margin-bottom:12px; font-size:.83rem; }
    .assign-timeline .ati::before { content:''; position:absolute; left:-19px; top:5px; width:8px; height:8px; border-radius:50%; background:#377dff; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-header-title">{{ translate('Follow-ups') }}</h1>
                <ol class="breadcrumb breadcrumb-no-gutter">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ translate('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ translate('Follow-ups') }}</li>
                </ol>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.sales-crm.followup.create') }}" class="btn btn-primary btn-sm">
                    <i class="tio-add mr-1"></i>{{ translate('Add Follow-up') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Count cards --}}
    <div class="d-flex gap-2 mb-3" style="gap:10px;">
        <div>
            <a href="{{ route('admin.sales-crm.followup.index', ['filter' => 'today']) }}"
                class="fu-count-card {{ $filter === 'today' ? 'active-filter' : '' }}"
                style="background:#e8f7fb; border-color:#b6e5f0; color:#0d7a93;">
                <div class="fu-icon" style="background:#c5ecf5;"><i class="tio-today" style="color:#0d7a93;"></i></div>
                <div>
                    <div class="fu-num">{{ $counts['today'] }}</div>
                    <div class="fu-lbl">{{ translate('Today') }}</div>
                </div>
            </a>
        </div>
        <div>
            <a href="{{ route('admin.sales-crm.followup.index', ['filter' => 'overdue']) }}"
                class="fu-count-card {{ $filter === 'overdue' ? 'active-filter' : '' }}"
                style="background:#fef0ee; border-color:#f5c5bc; color:#c0392b;">
                <div class="fu-icon" style="background:#fad6d1;"><i class="tio-time" style="color:#c0392b;"></i></div>
                <div>
                    <div class="fu-num">{{ $counts['overdue'] }}</div>
                    <div class="fu-lbl">{{ translate('Overdue') }}</div>
                </div>
            </a>
        </div>
        <div>
            <a href="{{ route('admin.sales-crm.followup.index', ['filter' => 'upcoming']) }}"
                class="fu-count-card {{ $filter === 'upcoming' ? 'active-filter' : '' }}"
                style="background:#edfaf2; border-color:#b5e8c8; color:#1a7a42;">
                <div class="fu-icon" style="background:#c5f0d8;"><i class="tio-calendar" style="color:#1a7a42;"></i></div>
                <div>
                    <div class="fu-num">{{ $counts['upcoming'] }}</div>
                    <div class="fu-lbl">{{ translate('Upcoming') }}</div>
                </div>
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-2">
            <span class="font-weight-bold">
                @if($filter === 'today') {{ translate("Today's Follow-ups") }}
                @elseif($filter === 'overdue') {{ translate("Overdue Follow-ups") }}
                @else {{ translate("Upcoming Follow-ups") }}
                @endif
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Due') }}</th>
                        <th>{{ translate('Title') }}</th>
                        <th>{{ translate('Query') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Assigned To') }}</th>
                        <th class="text-right">{{ translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($followups as $fu)
                    @php $isOverdue = $fu->status === 'pending' && $fu->due_date->isPast(); @endphp
                    <tr class="{{ $isOverdue ? 'fu-overdue-row' : '' }}">
                        <td>
                            <div class="font-weight-bold {{ $isOverdue ? 'text-danger' : '' }}">
                                {{ $fu->due_date->format('d M Y') }}
                            </div>
                            @if($fu->due_time)
                                <small class="text-muted">{{ \Carbon\Carbon::parse($fu->due_time)->format('h:i A') }}</small>
                            @endif
                            @if($isOverdue)
                                <small class="d-block text-danger" style="font-size:.7rem;font-weight:600;">OVERDUE</small>
                            @endif
                        </td>
                        <td>{{ $fu->title }}</td>
                        <td>
                            @if($fu->salesQuery)
                                <a href="{{ route('admin.sales-crm.query.show', $fu->query_id) }}">
                                    {{ $fu->salesQuery->ref_no }} — {{ $fu->salesQuery->contact_name }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $fu->status }} text-white">{{ ucfirst($fu->status) }}</span>
                        </td>
                        <td>
                            @php $assignee = $fu->assignedTo ?? $fu->admin; @endphp
                            <span class="fu-assignee-{{ $fu->id }}">{{ $assignee?->f_name }} {{ $assignee?->l_name }}</span>
                        </td>
                        <td class="text-right">
                            <div class="fu-menu dropdown">
                                <button class="fu-menu-btn" type="button" data-toggle="dropdown"
                                    data-boundary="window" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-bars"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right" onclick="event.stopPropagation()">

                                    @if($fu->status === 'pending')
                                    <form action="{{ route('admin.sales-crm.followup.status', $fu->id) }}" method="POST">
                                        @csrf <input type="hidden" name="status" value="done">
                                        <button class="dropdown-item text-success" type="submit">
                                            <i class="tio-checkmark-circle-outlined"></i> {{ translate('Mark Done') }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.sales-crm.followup.status', $fu->id) }}" method="POST">
                                        @csrf <input type="hidden" name="status" value="missed">
                                        <button class="dropdown-item text-warning" type="submit">
                                            <i class="tio-time"></i> {{ translate('Mark Missed') }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.sales-crm.followup.status', $fu->id) }}" method="POST">
                                        @csrf <input type="hidden" name="status" value="cancelled">
                                        <button class="dropdown-item text-secondary" type="submit">
                                            <i class="tio-clear-circle-outlined"></i> {{ translate('Cancel') }}
                                        </button>
                                    </form>
                                    <div class="dropdown-divider"></div>
                                    @endif

                                    <button class="dropdown-item text-primary" type="button"
                                        onclick="openAssignModal({{ $fu->id }}, '{{ addslashes($fu->title) }}')">
                                        <i class="tio-forward"></i> {{ translate('Assign / Reassign') }}
                                    </button>
                                    <button class="dropdown-item text-secondary" type="button"
                                        onclick="openHistoryModal({{ $fu->id }}, '{{ addslashes($fu->title) }}')">
                                        <i class="tio-history"></i> {{ translate('Assignment History') }}
                                    </button>

                                    <div class="dropdown-divider"></div>
                                    <form action="{{ route('admin.sales-crm.followup.destroy', $fu->id) }}" method="POST"
                                        onsubmit="return confirm('{{ translate('Delete this follow-up?') }}')">
                                        @csrf @method('DELETE')
                                        <button class="dropdown-item text-danger" type="submit">
                                            <i class="tio-delete-outlined"></i> {{ translate('Delete') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">{{ translate('No follow-ups found.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($followups->hasPages())
        <div class="card-footer">{{ $followups->links() }}</div>
        @endif
    </div>
</div>

{{-- Assign Modal --}}
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="tio-forward-outlined mr-1 text-primary"></i> {{ translate('Assign Follow-up') }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="assignForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="assignModalSubtitle"></p>
                    <div class="form-group">
                        <label class="input-label">{{ translate('Assign To') }} <span class="text-danger">*</span></label>
                        <select name="assigned_to" id="assignTo" class="form-control" required>
                            <option value="">— {{ translate('Select admin') }} —</option>
                            @foreach(\App\Models\Admin::orderBy('f_name')->get() as $adm)
                                <option value="{{ $adm->id }}">{{ $adm->f_name }} {{ $adm->l_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label class="input-label">{{ translate('Note (optional)') }}</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="{{ translate('e.g. Please call before visiting') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="tio-forward-outlined mr-1"></i>{{ translate('Assign') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- History Modal --}}
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="tio-history mr-1 text-secondary"></i> {{ translate('Assignment History') }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3" id="historyModalSubtitle"></p>
                <div id="historyTimeline" class="assign-timeline">
                    <p class="text-muted text-center">{{ translate('Loading...') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script_2')
<script>
    var assignUrl = '{{ url('admin/sales-crm/followups') }}';
    var currentFuId = null;

    function openAssignModal(id, title) {
        currentFuId = id;
        $('#assignModalSubtitle').text(title);
        $('#assignForm').attr('action', assignUrl + '/' + id + '/assign');
        $('#assignTo').val('').trigger('change');
        $('textarea[name=note]').val('');
        $('#assignModal').modal('show');
    }

    function openHistoryModal(id, title) {
        $('#historyModalSubtitle').text(title);
        $('#historyTimeline').html('<p class="text-muted text-center">{{ translate("Loading...") }}</p>');
        $('#historyModal').modal('show');

        $.getJSON(assignUrl + '/' + id + '/assignments', function (data) {
            if (!data.length) {
                $('#historyTimeline').html('<p class="text-muted text-center py-2">{{ translate("No assignments yet.") }}</p>');
                return;
            }
            var html = '';
            data.forEach(function (a) {
                html += '<div class="ati">';
                html += '<div class="font-weight-bold">' + a.assigned_to + '</div>';
                html += '<div class="text-muted">{{ translate("By") }}: ' + a.assigned_by + ' &bull; ' + a.at + '</div>';
                if (a.note) html += '<div class="mt-1 text-secondary small">' + a.note + '</div>';
                html += '</div>';
            });
            $('#historyTimeline').html(html);
        });
    }

    $('#assignForm').on('submit', function (e) {
        e.preventDefault();
        var $btn = $(this).find('[type=submit]').prop('disabled', true);
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function (res) {
                $('#assignModal').modal('hide');
                if (res.ok) {
                    $('.fu-assignee-' + currentFuId).text(res.assignee);
                    toastr.success('{{ translate("Follow-up assigned successfully.") }}');
                }
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message ?? '{{ translate("Error. Please try again.") }}';
                toastr.error(msg);
            },
            complete: function () { $btn.prop('disabled', false); }
        });
    });

    $(document).ready(function () {
        $('#assignTo').select2({
            dropdownParent: $('#assignModal'),
            width: '100%',
            placeholder: '— {{ translate("Select admin") }} —'
        });
    });
</script>
@endpush
