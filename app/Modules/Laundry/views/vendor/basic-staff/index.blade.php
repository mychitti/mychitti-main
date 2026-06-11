@extends('layouts.vendor.app')
@section('title', 'Staff Management')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
            </span>
            <span>Staff Management</span>
            {{-- <span class="badge badge-soft-secondary ml-2">Free Plan</span> --}}
        </h1>
        <div class="page-header-select-wrapper">
            <a href="{{ route('vendor.basic-staff.roles') }}" class="btn btn-outline-secondary btn-sm mr-2">
                <i class="tio-settings"></i> Manage Roles
            </a>
            @if ($count < 10)
                <a href="{{ route('vendor.basic-staff.create') }}" class="btn btn--primary">
                    <i class="tio-add"></i> Add Staff
                </a>
            @else
                <span class="badge badge-warning p-2">Limit reached (10/10)</span>
            @endif
        </div>
    </div>

    {{-- Usage bar --}}
    <div class="alert alert-soft-info d-flex align-items-center justify-content-between mb-3">
        <div>
            <i class="tio-info-outined mr-1"></i>
            Free plan includes up to <strong>10 staff members</strong> with basic info (name, contact, photo, ID proof).
            <a href="{{ route('vendor.subscriptions') }}" class="font-weight-bold">Upgrade to HR Management</a> for attendance, leaves, salary &amp; more.
        </div>
        <span class="badge badge-soft-primary ml-3" style="white-space:nowrap;">{{ $count }}/10 used</span>
    </div>

    <div class="card">
        <div class="card-header py-2">
            <h5 class="card-title">Staff List</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>ID Proof</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staff as $i => $s)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($s->image, asset('storage/vendor/' . $s->image), asset('public/assets/admin/img/160x160/img1.jpg'), 'vendor/') }}"
                                    class="rounded-circle"
                                    style="width:44px;height:44px;object-fit:cover;"
                                    alt="">
                            </td>
                            <td>{{ $s->f_name }} {{ $s->l_name }}</td>
                            <td>{{ $s->phone }}</td>
                            <td>{{ $s->email }}</td>
                            <td>{{ $s->role->name ?? '—' }}</td>
                            <td>
                                @if ($s->id_document)
                                    @php $ext = strtolower(pathinfo($s->id_document, PATHINFO_EXTENSION)); @endphp
                                    @if ($ext === 'pdf')
                                        <a href="{{ asset('storage/app/public/employee/id-proof/' . $s->id_document) }}" target="_blank" class="btn btn-xs btn-outline-secondary">
                                            <i class="tio-file-text-outlined"></i> PDF
                                        </a>
                                    @else
                                        <a href="{{ asset('storage/app/public/employee/id-proof/' . $s->id_document) }}" target="_blank">
                                            <img src="{{ asset('storage/app/public/employee/id-proof/' . $s->id_document) }}"
                                                style="width:40px;height:40px;object-fit:cover;border-radius:4px;" alt="ID">
                                        </a>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a href="{{ route('vendor.basic-staff.edit', $s->id) }}"
                                        class="btn action-btn btn--primary btn-outline-primary" title="Edit">
                                        <i class="tio-edit"></i>
                                    </a>
                                    <a href="javascript:" class="btn action-btn btn--danger btn-outline-danger form-alert"
                                        data-id="del-{{ $s->id }}"
                                        data-message="Remove this staff member?" title="Delete">
                                        <i class="tio-delete-outlined"></i>
                                    </a>
                                </div>
                                <form action="{{ route('vendor.basic-staff.delete', $s->id) }}"
                                    method="post" id="del-{{ $s->id }}">
                                    @csrf @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty--data text-center py-4">
                                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="" style="width:80px;">
                                    <h5 class="mt-2">No staff members yet</h5>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
