@extends('layouts.admin.app')

@section('title', $store->name . ' — Staff')

@section('content')
<div class="content container-fluid">

    @include('admin-views.vendor.view.partials._header', ['store' => $store])

    <div class="card mt-3">
        <div class="card-header d-flex align-items-center justify-content-between py-2">
            <h5 class="card-title m-0">
                <i class="tio-group-senior mr-1"></i> Staff Members
                <span class="badge badge-soft-dark ml-2">{{ $staff->count() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:60px"></th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>ID Document</th>
                            <th>Doc Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($staff as $member)
                            <tr>
                                {{-- Photo --}}
                                <td class="text-center py-2">
                                    @if ($member->image)
                                        <img src="{{ asset('storage/vendor/' . $member->image) }}"
                                            class="rounded-circle"
                                            style="width:42px;height:42px;object-fit:cover;"
                                            onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'">
                                    @else
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                                            style="width:42px;height:42px;background:#e9ecef;font-size:16px;color:#6c757d;font-weight:600;">
                                            {{ strtoupper(substr($member->f_name ?? '?', 0, 1)) }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Name --}}
                                <td class="py-2">
                                    <strong>{{ $member->f_name }} {{ $member->l_name }}</strong>
                                </td>

                                {{-- Contact --}}
                                <td class="py-2" style="font-size:13px;">
                                    <div>{{ $member->email }}</div>
                                    <div class="text-muted">{{ $member->phone }}</div>
                                </td>

                                {{-- Role --}}
                                <td class="py-2">
                                    @if ($member->role)
                                        <span class="badge badge-soft-primary">{{ $member->role->name }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="py-2">
                                    <div class="d-flex align-items-center" style="gap:6px;">
                                        @if ($member->status == 1)
                                            <span class="badge badge-soft-success">Active</span>
                                            <button type="button"
                                                class="btn btn-xs btn-soft-danger staff-suspend-btn"
                                                data-id="{{ $member->id }}"
                                                data-name="{{ $member->f_name }} {{ $member->l_name }}"
                                                data-url="{{ route('admin.store.staff.suspend', $member->id) }}"
                                                style="padding:2px 8px;font-size:11px;">
                                                Suspend
                                            </button>
                                        @else
                                            <span class="badge badge-soft-danger" title="{{ $member->suspension_reason }}">Suspended</span>
                                            <form action="{{ route('admin.store.staff.suspend', $member->id) }}" method="POST" class="m-0">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-soft-success"
                                                    onclick="return confirm('Activate this staff member?')"
                                                    style="padding:2px 8px;font-size:11px;">
                                                    Activate
                                                </button>
                                            </form>
                                            @if ($member->suspension_reason)
                                                <i class="tio-info-outined text-muted"
                                                    title="{{ $member->suspension_reason }}"
                                                    data-toggle="tooltip" style="cursor:pointer;"></i>
                                            @endif
                                        @endif
                                    </div>
                                </td>

                                {{-- ID Document --}}
                                <td class="py-2">
                                    @if ($member->id_document)
                                        @php $ext = strtolower(pathinfo($member->id_document, PATHINFO_EXTENSION)); @endphp
                                        @if ($ext === 'pdf')
                                            <a href="{{ asset('storage/app/public/employee/id-proof/' . $member->id_document) }}"
                                                target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="tio-file-text-outlined"></i> View PDF
                                            </a>
                                        @else
                                            <a href="{{ asset('storage/app/public/employee/id-proof/' . $member->id_document) }}"
                                                target="_blank">
                                                <img src="{{ asset('storage/app/public/employee/id-proof/' . $member->id_document) }}"
                                                    class="rounded border"
                                                    style="height:48px;width:auto;max-width:90px;object-fit:cover;"
                                                    onerror="this.style.display='none'">
                                            </a>
                                        @endif
                                    @else
                                        <span class="text-muted" style="font-size:12px;">Not uploaded</span>
                                    @endif
                                </td>

                                {{-- Doc Status --}}
                                <td class="py-2">
                                    @if (!$member->id_document)
                                        <span class="text-muted" style="font-size:12px;">—</span>
                                    @elseif ($member->document_status === 'verified')
                                        <span class="badge badge-soft-success">Verified</span>
                                    @elseif ($member->document_status === 'rejected')
                                        <span class="badge badge-soft-danger">Rejected</span>
                                        <form action="{{ route('admin.store.staff.document-status', $member->id) }}" method="POST" class="mt-1">
                                            @csrf
                                            <input type="hidden" name="status" value="verified">
                                            <button type="submit" class="btn btn-xs btn-soft-success">Verify</button>
                                        </form>
                                    @else
                                        <div class="d-flex" style="gap:4px;">
                                            <form action="{{ route('admin.store.staff.document-status', $member->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="status" value="verified">
                                                <button type="submit" class="btn btn-xs btn-soft-success">Verify</button>
                                            </form>
                                            <form action="{{ route('admin.store.staff.document-status', $member->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="btn btn-xs btn-soft-danger">Reject</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No staff members found for this store.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Suspend Modal --}}
<div class="modal fade" id="suspendStaffModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Suspend Staff Member</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="suspendStaffForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="mb-3">You are suspending <strong id="suspendStaffName"></strong>. Please provide a reason:</p>
                    <div class="form-group mb-0">
                        <textarea name="suspension_reason" class="form-control" rows="3"
                            placeholder="Enter reason for suspension..." required maxlength="500"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Suspend</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();

        $(document).on('click', '.staff-suspend-btn', function () {
            var name = $(this).data('name');
            var url  = $(this).data('url');
            $('#suspendStaffName').text(name);
            $('#suspendStaffForm').attr('action', url);
            $('#suspendStaffForm textarea').val('');
            $('#suspendStaffModal').modal('show');
        });
    });
</script>
@endpush
