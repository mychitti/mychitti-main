@extends('layouts.admin.app')

@section('title', 'Suspicious Activity')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .sa-stat { border-radius: 14px; padding: 18px 22px; color: #fff; }
    .sa-stat h3 { font-size: 28px; font-weight: 800; margin: 0; }
    .sa-stat p  { font-size: 13px; opacity: .85; margin: 4px 0 0; }
    .badge-otp      { background: #fff3cd; color: #856404; }
    .badge-enquiry  { background: #cff4fc; color: #0c5460; }
    .badge-open     { background: #f8d7da; color: #721c24; }
    .badge-reviewed { background: #d1e7dd; color: #0f5132; }
    .badge-dismissed{ background: #e2e3e5; color: #41464b; }
    .act-btn { border: none; background: none; padding: 3px 8px; border-radius: 6px; font-size: 12px; cursor: pointer; }
    .act-btn:hover { opacity: .8; }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-warning-outlined"></i></span>
            Suspicious Activity
            @if($openCount > 0)
                <span class="badge badge-danger ml-2">{{ $openCount }} open</span>
            @endif
        </h1>
    </div>

    {{-- Stats --}}
    <div class="row g-2 mb-4">
        <div class="col-md-4">
            <div class="sa-stat" style="background:linear-gradient(135deg,#e74c3c,#c0392b);">
                <h3>{{ $openCount }}</h3>
                <p>Open Alerts</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sa-stat" style="background:linear-gradient(135deg,#f39c12,#e67e22);">
                <h3>{{ $otpCount }}</h3>
                <p>OTP Abuse (open)</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sa-stat" style="background:linear-gradient(135deg,#2980b9,#1a6fa3);">
                <h3>{{ $enquiryCount }}</h3>
                <p>Enquiry Abuse (open)</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="d-flex flex-wrap align-items-center gap-3">
                <div>
                    <label class="mb-0 mr-1" style="font-size:13px;font-weight:600;">Type</label>
                    <select name="type" class="form-control form-control-sm d-inline-block" style="width:160px;" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="otp_abuse"     {{ $type == 'otp_abuse'     ? 'selected' : '' }}>OTP Abuse</option>
                        <option value="enquiry_abuse" {{ $type == 'enquiry_abuse' ? 'selected' : '' }}>Enquiry Abuse</option>
                    </select>
                </div>
                <div>
                    <label class="mb-0 mr-1" style="font-size:13px;font-weight:600;">Status</label>
                    <select name="status" class="form-control form-control-sm d-inline-block" style="width:160px;" onchange="this.form.submit()">
                        <option value="open"     {{ $status == 'open'     ? 'selected' : '' }}>Open</option>
                        <option value="reviewed" {{ $status == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                        <option value="dismissed"{{ $status == 'dismissed'? 'selected' : '' }}>Dismissed</option>
                        <option value="all"      {{ $status == 'all'      ? 'selected' : '' }}>All</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>User / Phone</th>
                            <th>Detail</th>
                            <th class="text-center">Count</th>
                            <th>IP</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        @php
                            $user = $log->user_id ? ($users[$log->user_id] ?? null) : null;
                        @endphp
                        <tr id="row-{{ $log->id }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @if($log->type === 'otp_abuse')
                                    <span class="badge badge-otp"><i class="tio-lock-outlined mr-1"></i>OTP Abuse</span>
                                @else
                                    <span class="badge badge-enquiry"><i class="tio-shopping-cart-outlined mr-1"></i>Enquiry Abuse</span>
                                @endif
                            </td>
                            <td>
                                @if($user)
                                    <a href="{{ route('admin.users.customer.view', $user->id) }}" class="font-semibold text-dark">
                                        {{ $user->f_name }}
                                    </a>
                                    <div class="text-muted" style="font-size:12px;">{{ $user->phone }}</div>
                                @elseif($log->phone)
                                    <span class="text-muted">{{ $log->phone }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td style="max-width:220px;white-space:normal;font-size:13px;">
                                {{ $log->detail }}
                            </td>
                            <td class="text-center">
                                <span class="badge badge-soft-danger" style="font-size:14px;font-weight:700;">{{ $log->count }}</span>
                            </td>
                            <td style="font-size:12px;color:#6c757d;">{{ $log->ip ?? '—' }}</td>
                            <td style="font-size:12px;">
                                {{ \Carbon\Carbon::parse($log->updated_at)->setTimezone('Asia/Kolkata')->format('d M Y H:i') }}
                            </td>
                            <td>
                                <span class="badge badge-{{ $log->status }} status-badge-{{ $log->id }}">
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($user)
                                <a href="{{ route('admin.users.customer.status', [$user->id, 0]) }}"
                                    class="act-btn text-danger" style="background:#fff0f0;"
                                    title="Block User"
                                    onclick="return confirm('Block {{ $user->f_name }}?')">
                                    <i class="tio-block"></i> Block
                                </a>
                                @endif
                                @if($log->status === 'open')
                                <button class="act-btn text-success ml-1" style="background:#f0fff4;"
                                    onclick="updateStatus({{ $log->id }}, 'reviewed')">
                                    <i class="tio-checkmark"></i> Review
                                </button>
                                @endif
                                @if($log->status !== 'dismissed')
                                <button class="act-btn text-secondary ml-1" style="background:#f0f0f0;"
                                    onclick="updateStatus({{ $log->id }}, 'dismissed')">
                                    <i class="tio-clear"></i> Dismiss
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9">
                                <div class="text-center py-5 text-muted">
                                    <i class="tio-checkmark-circle" style="font-size:40px;display:block;margin-bottom:8px;color:#ccc;"></i>
                                    No suspicious activity found.
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
            <div class="px-3 py-2">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>

</div>
@endsection

@push('script_2')
<script>
var csrfToken = $('meta[name="csrf-token"]').attr('content');

function updateStatus(id, status) {
    $.ajax({
        url: "{{ route('admin.suspicious.update-status') }}",
        method: 'POST',
        data: { _token: csrfToken, id: id, status: status },
        success: function() {
            var badge = $('.status-badge-' + id);
            badge.text(status.charAt(0).toUpperCase() + status.slice(1));
            badge.removeClass('badge-open badge-reviewed badge-dismissed').addClass('badge-' + status);
            if (status !== 'open') {
                $('#row-' + id + ' .act-btn').filter(function() {
                    return $(this).text().trim() === 'Review';
                }).remove();
            }
            toastr.success('Status updated.');
        },
        error: function() { toastr.error('Failed.'); }
    });
}
</script>
@endpush
