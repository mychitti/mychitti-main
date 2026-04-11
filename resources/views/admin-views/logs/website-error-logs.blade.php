@extends('layouts.admin.app')

@section('title', 'Website Error Logs')

@push('css_or_js')
<style>
    .we-cell { white-space: normal; word-break: break-word; vertical-align: top !important; }
    .we-file  { font-size: 11px; color: #6c757d; }
    .we-msg   { font-size: 12px; }
    .we-url   { font-size: 12px; word-break: break-all; }
    .we-who   { min-width: 90px; }
    #errorDetailModal pre { white-space: pre-wrap; word-break: break-word; font-size: 12px; background:#f8f9fa; padding:12px; border-radius:4px; max-height:300px; overflow-y:auto; }
    #errorDetailModal .detail-label { font-weight:600; font-size:12px; color:#6c757d; margin-bottom:2px; }
    #errorDetailModal .detail-value { font-size:13px; margin-bottom:12px;    overflow: auto; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    @include('admin-views/partials/_action-nav')
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-error-outlined" style="font-size:22px"></i></span>
            <span>Website Error Logs</span>
        </h1>
    </div>

    <div class="card">
        <div class="card-header border-0 py-2">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 w-100">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-danger btn-sm d-none" id="bulk-delete-btn">
                        <i class="tio-delete"></i> Delete Selected (<span id="selected-count">0</span>)
                    </button>
                    <form class="input-group input--group" style="min-width:260px" method="GET" action="{{ route('admin.logs.website-errors.index') }}">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search message, URL, class...">
                        <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                    </form>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="mb-0 mr-1">Status</label>
                    <select class="form-control" id="status-filter" style="min-width:130px">
                        <option value="all"      {{ $status == 'all'      ? 'selected' : '' }}>All</option>
                        <option value="open"     {{ $status == 'open'     ? 'selected' : '' }}>Open</option>
                        <option value="resolved" {{ $status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="reopen"   {{ $status == 'reopen'   ? 'selected' : '' }}>Reopen</option>
                        <option value="closed"   {{ $status == 'closed'   ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle" style="table-layout:fixed; width:100%">
                    <colgroup>
                        <col style="width:36px">
                        <col style="width:36px">
                        <col style="width:24%">
                        <col style="width:17%">
                        <col style="width:17%">
                        <col style="width:10%">
                        <col style="width:8%">
                        <col style="width:110px">
                        <col style="width:110px">
                        <col style="width:80px">
                    </colgroup>
                    <thead class="thead-light">
                        <tr>
                            <th>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="select-all">
                                    <label class="custom-control-label" for="select-all"></label>
                                </div>
                            </th>
                            <th>#</th>
                            <th>Message</th>
                            <th>File / Line</th>
                            <th>URL</th>
                            <th>Who</th>
                            <th>IP</th>
                            <th>Status</th>
                            <th>At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($websiteErrors as $key => $error)
                        <tr>
                            <td class="we-cell">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input row-checkbox" id="row-{{ $error->id }}" value="{{ $error->id }}">
                                    <label class="custom-control-label" for="row-{{ $error->id }}"></label>
                                </div>
                            </td>
                            <td style="white-space:nowrap">{{ $key + $websiteErrors->firstItem() }}</td>
                            <td class="we-cell we-msg">
                                <span class="badge badge-soft-secondary mb-1">{{ class_basename($error->class) }}</span>
                                <div>{{ \Str::limit($error->message, 80) }}</div>
                            </td>
                            <td class="we-cell we-file">
                                {{ \Str::limit($error->file, 50) }}
                                @if($error->line)<div class="text-muted">line {{ $error->line }}</div>@endif
                            </td>
                            <td class="we-cell we-url" title="{{ $error->url }}">
                                <span class="badge badge-soft-info">{{ $error->method }}</span>
                                <div>{{ \Str::limit($error->url, 50) }}</div>
                            </td>
                            <td class="we-cell we-who">
                                @if($error->admin_id)
                                    <span class="badge badge-soft-primary">Admin #{{ $error->admin_id }}</span>
                                @endif
                                @if($error->staff_id)
                                    <span class="badge badge-soft-warning">Staff #{{ $error->staff_id }}</span>
                                @endif
                                @if($error->user_id)
                                    <span class="badge badge-soft-success">User #{{ $error->user_id }}</span>
                                @endif
                                @if($error->store_id)
                                    <span class="badge badge-soft-dark d-block mt-1">Store #{{ $error->store_id }}</span>
                                @endif
                                @if(!$error->admin_id && !$error->staff_id && !$error->user_id && !$error->store_id)
                                    <span class="text-muted">Guest</span>
                                @endif
                            </td>
                            <td class="we-cell" style="font-size:11px">{{ $error->ip }}</td>
                            <td class="we-cell">
                                <select class="form-control form-control-sm change-error-status" data-id="{{ $error->id }}">
                                    <option value="open"     {{ $error->status == 'open'     ? 'selected' : '' }}>Open</option>
                                    <option value="resolved" {{ $error->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="reopen"   {{ $error->status == 'reopen'   ? 'selected' : '' }}>Reopen</option>
                                    <option value="closed"   {{ $error->status == 'closed'   ? 'selected' : '' }}>Closed</option>
                                </select>
                            </td>
                            <td class="we-cell" style="font-size:11px">{{ $error->created_at->format('Y-m-d H:i') }}</td>
                            <td class="we-cell">
                                <div class="btn--container justify-content-center">
                                    <button class="btn action-btn btn--primary btn-outline-primary view-error-btn"
                                        title="View Details"
                                        data-id="{{ $error->id }}"
                                        data-class="{{ $error->class }}"
                                        data-message="{{ e($error->message) }}"
                                        data-file="{{ $error->file }}"
                                        data-line="{{ $error->line }}"
                                        data-url="{{ $error->url }}"
                                        data-method="{{ $error->method }}"
                                        data-ip="{{ $error->ip }}"
                                        data-useragent="{{ e($error->user_agent) }}"
                                        data-admin="{{ $error->admin_id }}"
                                        data-staff="{{ $error->staff_id }}"
                                        data-user="{{ $error->user_id }}"
                                        data-store="{{ $error->store_id }}"
                                        data-trace="{{ e($error->trace) }}"
                                        data-created="{{ $error->created_at->format('Y-m-d H:i:s') }}"
                                        data-requestdata="{{ e($error->request_data) }}" data-referer="{{ e($error->referer) }}">
                                        <i class="tio-visible-outlined"></i>
                                    </button>
                                    <button class="btn action-btn btn--danger btn-outline-danger single-delete-btn" data-id="{{ $error->id }}" title="Delete">
                                        <i class="tio-delete-outlined"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10">
                                <div class="empty--data">
                                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="">
                                    <h5>{{ translate('no_data_found') }}</h5>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer border-0 pt-0">
            {!! $websiteErrors->links() !!}
        </div>
    </div>
</div>

{{-- Error Detail Modal --}}
<div class="modal fade" id="errorDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="tio-error-outlined mr-1"></i> Error Detail</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-label">Exception Class</div>
                        <div class="detail-value" id="md-class"></div>

                        <div class="detail-label">File</div>
                        <div class="detail-value" id="md-file"></div>

                        <div class="detail-label">Line</div>
                        <div class="detail-value" id="md-line"></div>

                        <div class="detail-label">URL</div>
                        <div class="detail-value" id="md-url"></div>

                        <div class="detail-label">Method</div>
                        <div class="detail-value" id="md-method"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">IP Address</div>
                        <div class="detail-value" id="md-ip"></div>

                        <div class="detail-label">User Agent</div>
                        <div class="detail-value" id="md-useragent"></div>

                        <div class="detail-label">Who</div>
                        <div class="detail-value" id="md-who"></div>

                        <div class="detail-label">Logged At</div>
                        <div class="detail-value" id="md-created"></div>
                    </div>
                </div>

                <div class="detail-label mt-2">Message</div>
                <pre id="md-message"></pre>

                <div class="detail-label mt-2">Stack Trace</div>
                <pre id="md-trace"></pre>
            </div>
            {{-- Replay Section --}}
            <div class="modal-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div id="replay-section"></div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>

            {{-- Hidden impersonate form --}}
            <form id="impersonate-form" action="{{ route('admin.impersonate.start') }}" method="POST" class="d-none">
                @csrf
                <input type="hidden" name="guard" id="imp-guard">
                <input type="hidden" name="user_id" id="imp-user-id">
                <input type="hidden" name="redirect_url" id="imp-redirect-url">
                <input type="hidden" name="redirect_method" id="imp-redirect-method">
            </form>

            {{-- Hidden POST replay form --}}
            <form id="replay-post-form" method="POST" class="d-none">
                @csrf
                <div id="replay-post-fields"></div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
"use strict";

// View modal
$(document).on('click', '.view-error-btn', function() {
    var d = $(this).data();

    $('#md-class').text(d.class || '-');
    $('#md-file').text(d.file || '-');
    $('#md-line').text(d.line || '-');
    $('#md-url').html('<span class="badge badge-soft-info mr-1">' + (d.method || '') + '</span>' + (d.url || '-'));
    $('#md-method').text(d.method || '-');
    $('#md-ip').text(d.ip || '-');
    $('#md-useragent').text(d.useragent || '-');
    $('#md-created').text(d.created || '-');
    $('#md-message').text(d.message || '-');
    $('#md-trace').text(d.trace || 'No trace available');

    var who = '';
    if (d.admin)  who += '<span class="badge badge-soft-primary mr-1">Admin #' + d.admin + '</span>';
    if (d.staff)  who += '<span class="badge badge-soft-warning mr-1">Staff #' + d.staff + '</span>';
    if (d.user)   who += '<span class="badge badge-soft-success mr-1">User #' + d.user + '</span>';
    if (d.store)  who += '<span class="badge badge-soft-dark mr-1">Store #' + d.store + '</span>';
    if (!d.admin && !d.staff && !d.user && !d.store) who = '<span class="text-muted">Guest</span>';
    $('#md-who').html(who);

    // Build replay section
    var replayHtml = '';
    var isImpersonating = {{ session()->has('impersonator_id') ? 'true' : 'false' }};

    if (!isImpersonating) {
        // Impersonate buttons
        if (d.admin) {
            replayHtml += '<button class="btn btn-sm btn-warning mr-1 imp-btn" data-guard="admin" data-userid="' + d.admin + '" data-url="' + (d.url || '/admin') + '" data-method="' + (d.method || 'GET') + '" data-referer="' + (d.referer || '') + '">'
                + '<i class="tio-user-big mr-1"></i>Replay as Admin #' + d.admin + '</button>';
        }
        if (d.store) {
            replayHtml += '<button class="btn btn-sm btn-warning mr-1 imp-btn" data-guard="vendor" data-userid="' + d.store + '" data-url="' + (d.url || '/store-panel') + '" data-method="' + (d.method || 'GET') + '" data-referer="' + (d.referer || '') + '">'
                + '<i class="tio-user-big mr-1"></i>Replay as Store #' + d.store + '</button>';
        }
    } else {
        // Already impersonating — offer direct replay
        if (d.method === 'GET' && d.url) {
            replayHtml += '<a href="' + d.url + '" target="_blank" class="btn btn-sm btn-info mr-1">'
                + '<i class="tio-play mr-1"></i>Open URL (GET)</a>';
        } else if (d.method === 'POST' && d.url) {
            replayHtml += '<button class="btn btn-sm btn-info mr-1" id="do-post-replay" data-url="' + d.url + '" data-reqdata=\'' + (d.requestdata || '{}') + '\'>'
                + '<i class="tio-play mr-1"></i>Replay POST</button>';
        }
    }

    if (!replayHtml && d.url) {
        replayHtml = '<a href="' + d.url + '" target="_blank" class="btn btn-sm btn-outline-secondary">'
            + '<i class="tio-open-in-new mr-1"></i>Open URL</a>';
    }

    $('#replay-section').html(replayHtml);
    $('#errorDetailModal').modal('show');
});

// Impersonate + redirect
$(document).on('click', '.imp-btn', function() {
    var guard   = $(this).data('guard');
    var userId  = $(this).data('userid');
    var method  = $(this).data('method') || 'GET';
    var referer = $(this).data('referer') || '';
    var postUrl = $(this).data('url');

    // POST endpoints can't be GET-redirected.
    // Use referer (the form page) if available, otherwise panel root.
    var panelRoot = (guard === 'vendor') ? '/store-panel' : '/admin';
    var redirectUrl = (method === 'POST')
        ? (referer || panelRoot)
        : postUrl;

    if (!confirm('Impersonate this user and open the error page?')) return;

    $('#imp-guard').val(guard);
    $('#imp-user-id').val(userId);
    $('#imp-redirect-url').val(redirectUrl);
    $('#imp-redirect-method').val(method);
    $('#impersonate-form').submit();
});

// POST replay
$(document).on('click', '#do-post-replay', function() {
    var url = $(this).data('url');
    var rawData = $(this).data('reqdata');
    var parsed = {};
    try { parsed = JSON.parse(rawData); } catch(e) {}
    var post = parsed.post || {};

    var form = $('#replay-post-form');
    form.attr('action', url);
    var fields = '';
    $.each(post, function(k, v) {
        fields += '<input type="hidden" name="' + $('<div>').text(k).html() + '" value="' + $('<div>').text(String(v)).html() + '">';
    });
    $('#replay-post-fields').html(fields);

    if (!confirm('This will submit a real POST request to:\n' + url + '\n\nThis may have side effects. Continue?')) return;
    form.submit();
});

// Status filter
$('#status-filter').on('change', function() {
    var url = new URL(window.location.href);
    url.searchParams.set('status', $(this).val());
    url.searchParams.delete('page');
    window.location.href = url.toString();
});

// Select all
$('#select-all').on('change', function() {
    $('.row-checkbox').prop('checked', this.checked);
    updateBulkBtn();
});

$(document).on('change', '.row-checkbox', function() {
    $('#select-all').prop('checked', $('.row-checkbox').length === $('.row-checkbox:checked').length);
    updateBulkBtn();
});

function updateBulkBtn() {
    var count = $('.row-checkbox:checked').length;
    $('#selected-count').text(count);
    $('#bulk-delete-btn').toggleClass('d-none', count === 0);
}

// Bulk delete
$('#bulk-delete-btn').on('click', function() {
    var ids = $('.row-checkbox:checked').map(function() { return $(this).val(); }).get();
    if (!ids.length) return;
    Swal.fire({
        title: 'Are you sure?',
        text: 'Delete ' + ids.length + ' error(s)?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete!',
    }).then((result) => {
        if (result.value) {
            $.post({
                url: '{{ route('admin.logs.website-errors.bulk-delete') }}',
                data: { ids: ids, _token: '{{ csrf_token() }}' },
                success: function(res) { toastr.success(res.message); location.reload(); },
                error: function() { toastr.error('Failed to delete'); }
            });
        }
    });
});

// Single delete
$(document).on('click', '.single-delete-btn', function() {
    var id  = $(this).data('id');
    var row = $(this).closest('tr');
    Swal.fire({
        title: 'Delete this error?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete!',
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: '{{ url('admin/logs/website-errors') }}/' + id,
                type: 'POST',
                data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
                success: function() { row.fadeOut(300, function() { $(this).remove(); }); toastr.success('Deleted'); },
                error: function() { toastr.error('Failed to delete'); }
            });
        }
    });
});

// Status change
$(document).on('change', '.change-error-status', function() {
    var id = $(this).data('id');
    var status = $(this).val();
    $.post({
        url: '{{ url('admin/logs/website-errors') }}/' + id + '/status',
        data: { status: status, _token: '{{ csrf_token() }}' },
        success: function() { toastr.success('Status updated'); },
        error: function() { toastr.error('Failed to update status'); }
    });
});
</script>
@endpush
