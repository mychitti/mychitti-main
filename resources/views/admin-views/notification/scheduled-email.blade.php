@extends('layouts.admin.app')

@section('title', 'Schedule Email Broadcast')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <i class="tio-email" style="font-size:24px"></i>
            </span>
            <span>Schedule Email Broadcast</span>
        </h1>
    </div>

    <div class="row g-3">
        {{-- Compose Form --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header py-2">
                    <h5 class="card-title mb-0"><i class="tio-add mr-1"></i> New Scheduled Email</h5>
                </div>
                <div class="card-body">
                    <form id="email_schedule_form">
                        @csrf
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <div class="form-group mb-2">
                                    <label class="input-label">Subject <span class="text-danger">*</span></label>
                                    <input type="text" name="email_subject" class="form-control" placeholder="Email subject" required maxlength="255">
                                </div>
                                <div class="form-group mb-2">
                                    <label class="input-label">Send To <span class="text-danger">*</span></label>
                                    <select name="send_to" class="form-control" required>
                                        <option value="all_users">All Customers</option>
                                        <option value="all_vendors">All Vendors</option>
                                        <option value="all_delivery_men">All Delivery Men</option>
                                    </select>
                                </div>
                                <div class="form-group mb-2">
                                    <label class="input-label">Schedule Date &amp; Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="scheduled_at" class="form-control"
                                        min="{{ now('Asia/Kolkata')->addMinutes(5)->format('Y-m-d\TH:i') }}" required>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group mb-2">
                                    <label class="input-label">Message / Body <span class="text-danger">*</span></label>
                                    <textarea name="message" class="form-control" rows="7" placeholder="Email body..." required></textarea>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="btn--container justify-content-end">
                                    <button type="reset" class="btn btn--reset">Reset</button>
                                    <button type="submit" class="btn btn--primary"><i class="tio-time mr-1"></i> Schedule Email</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Scheduled Emails List --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header py-2">
                    <h5 class="card-title mb-0">Scheduled Emails <span class="badge badge-soft-dark ml-2">{{ $emails->total() }}</span></h5>
                </div>
                <div class="table-responsive datatable-custom">
                    <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Subject</th>
                                <th>Send To</th>
                                <th>Scheduled At</th>
                                <th>Sent At</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($emails as $key => $email)
                            <tr>
                                <td>{{ $key + $emails->firstItem() }}</td>
                                <td title="{{ $email->subject }}">{{ \Str::limit($email->subject, 40) }}</td>
                                <td>
                                    @php
                                        $labels = ['all_users' => 'All Customers', 'all_vendors' => 'All Vendors', 'all_delivery_men' => 'All Delivery Men'];
                                    @endphp
                                    {{ $labels[$email->send_to] ?? $email->send_to }}
                                </td>
                                <td>{{ $email->scheduled_at->setTimezone('Asia/Kolkata')->format('Y-m-d H:i') }}</td>
                                <td>{{ $email->sent_at ? $email->sent_at->setTimezone('Asia/Kolkata')->format('Y-m-d H:i') : '—' }}</td>
                                <td>
                                    @if($email->status === 'sent')
                                        <span class="badge badge-soft-success">Sent</span>
                                    @elseif($email->status === 'failed')
                                        <span class="badge badge-soft-danger" title="{{ $email->error }}">Failed</span>
                                    @else
                                        <span class="badge badge-soft-info">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                        @if($email->status === 'pending')
                                            <form action="{{ route('admin.scheduled-notification.send-now', $email->id) }}" method="post" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn action-btn btn-outline-success"
                                                    title="Send Now" onclick="return confirm('Send this email now?')">
                                                    <i class="tio-send"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.scheduled-notification.destroy', $email->id) }}" method="post" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn action-btn btn--danger btn-outline-danger"
                                                title="Delete" onclick="return confirm('Delete this scheduled email?')">
                                                <i class="tio-delete-outlined"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7">
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
                @if($emails->hasPages())
                    <hr>
                    <div class="page-area">{!! $emails->links() !!}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
"use strict";

$('#email_schedule_form').on('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);

    Swal.fire({
        title: 'Schedule Email?',
        text: 'This email will be sent to the selected group at the scheduled time.',
        type: 'info',
        showCancelButton: true,
        confirmButtonText: 'Schedule',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
            $.post({
                url: '{{ route('admin.scheduled-notification.store-email') }}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(data) {
                    toastr.success(data.message || 'Email scheduled successfully!');
                    $('#email_schedule_form')[0].reset();
                    setTimeout(() => location.reload(), 1500);
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON?.errors;
                    if (errors) {
                        $.each(errors, function(k, v) { toastr.error(v[0]); });
                    } else {
                        toastr.error('Something went wrong.');
                    }
                }
            });
        }
    });
});
</script>
@endpush
