@extends('layouts.admin.app')

@section('title', 'Scheduled Notifications')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .sched-type  { font-size:11px; font-weight:600; padding:3px 8px; border-radius:20px; }
    .type-push   { background:#e8f4fd; color:#1a73e8; }
    .type-email  { background:#fce8f3; color:#c0185a; }
    .type-both   { background:#e8fdf4; color:#0f6e3d; }
    .st-pending    { background:#fff3cd; color:#856404; }
    .st-processing { background:#cff4fc; color:#0c5460; }
    .st-sent       { background:#d1e7dd; color:#0f5132; }
    .st-failed     { background:#f8d7da; color:#721c24; }
    .preview-img { width:60px; height:40px; object-fit:cover; border-radius:4px; }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-time"></i></span>
            Scheduled Notifications
        </h1>
    </div>

    <div class="row g-3">

        {{-- ADD FORM --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="tio-add-circle-outlined mr-1"></i> Schedule New</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.scheduled-notification.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label class="input-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="Notification title" required maxlength="255">
                        </div>

                        <div class="form-group">
                            <label class="input-label">Message <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="3" placeholder="Notification message..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label class="input-label">Image <small class="text-muted">(optional)</small></label>
                            <div class="custom-file">
                                <input type="file" name="image" class="custom-file-input" id="notif_image" accept="image/*">
                                <label class="custom-file-label" for="notif_image">Choose image</label>
                            </div>
                            <img id="image_preview" src="#" alt="" class="mt-2 rounded" style="display:none;max-height:80px;">
                        </div>

                        <div class="form-group">
                            <label class="input-label">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-control" required>
                                <option value="push">Push Notification only</option>
                                <option value="email">Email only</option>
                                <option value="both">Both (Push + Email)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="input-label">Send To <span class="text-danger">*</span></label>
                            <select name="send_to" class="form-control" required>
                                <option value="all_users">All Customers</option>
                                <option value="all_vendors">All Vendors</option>
                                <option value="all_delivery_men">All Delivery Men</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="input-label">Schedule Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="scheduled_at" class="form-control"
                                min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="tio-time mr-1"></i> Schedule
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- LIST --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="card-title mb-0">Scheduled List</h5>
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Search title..." style="width:180px;">
                        <select name="status" class="form-control form-control-sm" style="width:130px;" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="pending"    {{ $status == 'pending'    ? 'selected' : '' }}>Pending</option>
                            <option value="processing" {{ $status == 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="sent"       {{ $status == 'sent'       ? 'selected' : '' }}>Sent</option>
                            <option value="failed"     {{ $status == 'failed'     ? 'selected' : '' }}>Failed</option>
                        </select>
                        <button class="btn btn-sm btn-primary"><i class="tio-search"></i></button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Audience</th>
                                    <th>Scheduled At</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schedules as $s)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td style="max-width:180px;">
                                        <div class="font-weight-bold text-truncate" style="max-width:160px;" title="{{ $s->title }}">{{ $s->title }}</div>
                                        <small class="text-muted text-truncate d-block" style="max-width:160px;">{{ Str::limit($s->message, 50) }}</small>
                                        @if($s->status === 'failed' && $s->fail_reason)
                                            <small class="text-danger d-block">{{ Str::limit($s->fail_reason, 60) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="sched-type type-{{ $s->type }}">
                                            @if($s->type === 'push') <i class="tio-notifications-on"></i> Push
                                            @elseif($s->type === 'email') <i class="tio-email"></i> Email
                                            @else <i class="tio-layers-outlined"></i> Both
                                            @endif
                                        </span>
                                    </td>
                                    <td style="font-size:12px;">
                                        @if($s->send_to === 'all_users') <i class="tio-user-outlined mr-1"></i>All Customers
                                        @elseif($s->send_to === 'all_vendors') <i class="tio-store mr-1"></i>All Vendors
                                        @else <i class="tio-bike mr-1"></i>Delivery Men
                                        @endif
                                    </td>
                                    <td style="font-size:12px;">
                                        {{ \Carbon\Carbon::parse($s->scheduled_at)->setTimezone('Asia/Kolkata')->format('d M Y H:i') }}
                                        @if($s->sent_at)
                                            <div class="text-muted" style="font-size:11px;">Sent: {{ \Carbon\Carbon::parse($s->sent_at)->setTimezone('Asia/Kolkata')->format('d M H:i') }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge st-{{ $s->status }}">{{ ucfirst($s->status) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            @if(in_array($s->status, ['pending','failed']))
                                            <form action="{{ route('admin.scheduled-notification.send-now', $s->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-xs btn-soft-success mr-1" title="Send Now" onclick="return confirm('Send this notification now?')">
                                                    <i class="tio-send"></i>
                                                </button>
                                            </form>
                                            @endif
                                            @if($s->status !== 'sent')
                                            <form action="{{ route('admin.scheduled-notification.destroy', $s->id) }}" method="POST" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-xs btn-soft-danger" title="Delete" onclick="return confirm('Delete this scheduled notification?')">
                                                    <i class="tio-delete"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="text-center py-5 text-muted">
                                            <i class="tio-time" style="font-size:40px;display:block;margin-bottom:8px;color:#ccc;"></i>
                                            No scheduled notifications found.
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($schedules->hasPages())
                    <div class="px-3 py-2">{{ $schedules->links() }}</div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('script_2')
<script>
// Image preview
$('#notif_image').on('change', function () {
    var file = this.files[0];
    if (file) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#image_preview').attr('src', e.target.result).show();
        };
        reader.readAsDataURL(file);
        $(this).next('.custom-file-label').text(file.name);
    }
});
</script>
@endpush
