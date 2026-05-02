@extends('layouts.vendor.app')
@section('title', 'Staff Roles')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
            </span>
            <span>Staff Roles</span>
        </h1>
        <a href="{{ route('vendor.basic-staff.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> Back to Staff
        </a>
    </div>

    <div class="row">
        {{-- Create role --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Create New Role</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('vendor.basic-staff.roles.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="input-label">Role Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" placeholder="e.g. Manager, Cashier, Technician" required maxlength="100">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn--primary w-100">Create Role</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Roles list --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        Roles <span class="badge badge-soft-dark ml-1">{{ count($roles) }}</span>
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Role Name</th>
                                <th>Staff Count</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($roles as $i => $role)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        {{-- Inline edit --}}
                                        <form action="{{ route('vendor.basic-staff.roles.update', $role->id) }}"
                                            method="POST" class="d-flex align-items-center" style="gap:6px;">
                                            @csrf
                                            <input type="text" name="name"
                                                class="form-control form-control-sm"
                                                value="{{ $role->getRawOriginal('name') ?? $role->name }}"
                                                style="max-width:180px;" required maxlength="100">
                                            <button type="submit" class="btn btn-xs btn--primary" title="Save">
                                                <i class="tio-checkmark"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        {{ \App\Models\VendorEmployee::where('store_id', \App\CentralLogics\Helpers::get_store_id())->where('employee_role_id', $role->id)->count() }}
                                    </td>
                                    <td class="text-center">
                                        <a href="javascript:" class="btn action-btn btn--danger btn-outline-danger form-alert"
                                            data-id="role-del-{{ $role->id }}"
                                            data-message="Delete role '{{ $role->name }}'? Staff with this role will have it removed."
                                            title="Delete">
                                            <i class="tio-delete-outlined"></i>
                                        </a>
                                        <form action="{{ route('vendor.basic-staff.roles.delete', $role->id) }}"
                                            method="POST" id="role-del-{{ $role->id }}">
                                            @csrf @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="empty--data text-center py-4">
                                            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="" style="width:70px;">
                                            <h6 class="mt-2">No roles created yet</h6>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Permissions info panel --}}
            <div class="card mt-3">
                <div class="card-header"><h6 class="card-title mb-0">About Roles &amp; Permissions</h6></div>
                <div class="card-body" style="font-size:0.875rem;">
                    <p class="mb-2">
                        In the <strong>free plan</strong>, roles are used to label and group your staff
                        (e.g. Manager, Cashier, Driver). You can assign a role to each staff member.
                    </p>
                    <p class="mb-0 text-muted">
                        <i class="tio-lock-outlined mr-1"></i>
                        <strong>Granular permissions</strong> (restricting what each role can view or do inside the
                        vendor panel) are available in the
                        <a href="{{ route('vendor.subscriptions') }}">HR Management subscription</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
