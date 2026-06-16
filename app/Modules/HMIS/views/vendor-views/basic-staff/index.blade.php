@extends('layouts.vendor.app')
@section('title', 'Staff Management')

@section('content')
<div class="content container-fluid staff-mgmt-page">
    {{-- Stats header (pharmacy metrics-row style) --}}
    @php
        $statActive   = $staff->where('status', 1)->count();
        $statInactive = $staff->where('status', 0)->count();
        $statRoles    = $staff->whereNotNull('employee_role_id')->where('employee_role_id', '!=', '')->count();
        $statIdProof  = $staff->whereNotNull('id_document')->where('id_document', '!=', '')->count();
        $statFree     = max(0, 10 - $count);
    @endphp
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&display=swap');
        /* Full-bleed header exactly like the pharmacy metrics row */
        .staff-mgmt-page.content.container-fluid { padding: 0 !important; }
        .staff-metrics-row {
            display: grid; grid-template-columns: repeat(6, 1fr);
            background: #ffffff; border-bottom: 1px solid #e2e8f0;
            padding: 12px 24px; gap: 10px;
        }
        .staff-metrics-row .metric-card {
            padding: 8px 12px; border-right: 1px solid #f1f5f9;
            display: flex; flex-direction: column; justify-content: center;
        }
        .staff-metrics-row .metric-card:last-child { border-right: none; }
        .staff-metrics-row .metric-card .value {
            font-size: 20px; font-weight: 800; color: #0f172a;
            font-family: 'Outfit', sans-serif; line-height: 1.2;
            display: flex; align-items: baseline; gap: 2px;
        }
        .staff-metrics-row .metric-card .subtext {
            font-size: 11px; color: #64748b; margin-top: 2px; font-weight: 500;
        }
        /* Make the shared tabs nav a flat full-width bar flush under the stats row */
        .staff-mgmt-page .bsnav {
            border: none !important; border-bottom: 1px solid #e2e8f0 !important;
            border-radius: 0 !important; padding: 0 24px !important; background: #fff;
        }
        .staff-page-body { padding: 20px 24px; }
    </style>
    <div class="staff-metrics-row">
        <div class="metric-card">
            <div class="value" style="color:#2563eb;">{{ $count }}</div>
            <div class="subtext">TOTAL STAFF<br><span style="color:#3b82f6;font-weight:700;">{{ $statActive }} active</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color:#10b981;">{{ $statActive }}</div>
            <div class="subtext">ACTIVE<br><span style="color:#16a34a;font-weight:700;">on board</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color:#ef4444;">{{ $statInactive }}</div>
            <div class="subtext">INACTIVE<br><span style="color:#dc2626;font-weight:700;">disabled</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color:#8b5cf6;">{{ $statRoles }}</div>
            <div class="subtext">WITH ROLE<br><span style="color:#7c3aed;font-weight:700;">{{ max(0, $count - $statRoles) }} unassigned</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color:#ea580c;">{{ $statIdProof }}</div>
            <div class="subtext">ID VERIFIED<br><span style="color:#ea580c;font-weight:700;">proof uploaded</span></div>
        </div>
        <div class="metric-card">
            <div class="value">{{ $statFree }}</div>
            <div class="subtext">FREE SLOTS<br><span style="font-weight:700;">{{ $count }}/10 used</span></div>
        </div>
    </div>

    @include('vendor-views.partials._basic_staff_nav')

    <div class="staff-page-body">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
            </span>
            <span>Staff Management</span>
            {{-- <span class="badge badge-soft-secondary ml-2">Free Plan</span> --}}
        </h1>
        <div class="page-header-select-wrapper">
            @if (hasPermission('basic_staff_manage', 'role_manage'))
                <a href="{{ route('vendor.basic-staff.roles') }}" class="btn btn-outline-secondary btn-sm mr-2">
                    <i class="tio-settings"></i> Manage Roles
                </a>
            @endif
            @if (hasPermission('basic_staff_manage', 'add'))
                @if ($count < 10)
                    <a href="{{ route('vendor.basic-staff.create') }}" class="btn btn--primary">
                        <i class="tio-add"></i> Add Staff
                    </a>
                @else
                    <span class="badge badge-warning p-2">Limit reached (10/10)</span>
                @endif
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
                                    @if (hasPermission('basic_staff_manage', 'edit'))
                                        <a href="{{ route('vendor.basic-staff.edit', $s->id) }}"
                                            class="btn action-btn btn--primary btn-outline-primary" title="Edit">
                                            <i class="tio-edit"></i>
                                        </a>
                                    @endif
                                    @if (hasPermission('basic_staff_manage', 'delete'))
                                        <a href="javascript:" class="btn action-btn btn--danger btn-outline-danger form-alert"
                                            data-id="del-{{ $s->id }}"
                                            data-message="Remove this staff member?" title="Delete">
                                            <i class="tio-delete-outlined"></i>
                                        </a>
                                        <form action="{{ route('vendor.basic-staff.delete', $s->id) }}"
                                            method="post" id="del-{{ $s->id }}">
                                            @csrf @method('DELETE')
                                        </form>
                                    @endif
                                </div>
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
    </div>{{-- /.staff-page-body --}}
</div>
@endsection
