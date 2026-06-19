@extends('layouts.vendor.app')
@section('title', 'Staff Roles & Permissions')

@push('css_or_js')
    <style>
        .module_th {
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 1;
        }
        .perms-panel td { background: #f9f9fb; }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
            </span>
            <span>Roles &amp; Permissions</span>
        </h1>
        <a href="{{ route('vendor.basic-staff.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> Back to Staff
        </a>
    </div>

    @php
        $actionLabels = [
            'list'        => 'View Staff',
            'add'         => 'Add Staff',
            'edit'        => 'Edit Staff',
            'delete'      => 'Delete Staff',
            'role_manage' => 'Manage Roles',
        ];
        $actionOrder = ['list', 'add', 'edit', 'delete', 'role_manage'];
        // Build an ordered map of action → feature_permission_id
        $fpByAction = [];
        foreach ($featurePerms as $fp) {
            $fpByAction[$fp->action] = $fp->id;
        }
        $orderedActions = array_values(array_filter($actionOrder, fn($a) => isset($fpByAction[$a])));
    @endphp

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
                                <th>Staff</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($roles as $i => $role)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <form action="{{ route('vendor.basic-staff.roles.update', $role->id) }}"
                                            method="POST" class="d-flex align-items-center" style="gap:6px;">
                                            @csrf
                                            <input type="text" name="name"
                                                class="form-control form-control-sm"
                                                value="{{ $role->getRawOriginal('name') ?? $role->name }}"
                                                style="max-width:160px;" required maxlength="100">
                                            <button type="submit" class="btn btn-xs btn--primary" title="Save Name">
                                                <i class="tio-checkmark"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        {{ \App\Models\VendorEmployee::where('store_id', \App\CentralLogics\Helpers::get_store_id())->where('employee_role_id', $role->id)->count() }}
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            @if ($featurePerms->count())
                                                <button class="btn btn-xs btn-outline-secondary"
                                                    type="button"
                                                    data-toggle="collapse"
                                                    data-target="#perms-{{ $role->id }}"
                                                    aria-expanded="false"
                                                    title="Edit Permissions">
                                                    <i class="tio-lock-outlined"></i> Permissions
                                                </button>
                                            @endif
                                            <a href="javascript:" class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                data-id="role-del-{{ $role->id }}"
                                                data-message="Delete role '{{ $role->name }}'? Staff with this role will lose it."
                                                title="Delete">
                                                <i class="tio-delete-outlined"></i>
                                            </a>
                                            <form action="{{ route('vendor.basic-staff.roles.delete', $role->id) }}"
                                                method="POST" id="role-del-{{ $role->id }}">
                                                @csrf @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Permission matrix panel --}}
                                @if ($featurePerms->count())
                                    @php
                                        $assignedIds = $role->permissions->pluck('id')->all();
                                        $rowAllOn = count($orderedActions) > 0
                                            && collect($orderedActions)->every(fn($a) => in_array($fpByAction[$a] ?? null, $assignedIds));
                                    @endphp
                                    <tr class="collapse perms-panel" id="perms-{{ $role->id }}">
                                        <td colspan="4" style="border-top:none; padding: 0 16px 16px;">
                                            <form action="{{ route('vendor.basic-staff.roles.permissions', $role->id) }}" method="POST"
                                                id="perms-form-{{ $role->id }}">
                                                @csrf

                                                <div class="table-responsive mt-2">
                                                    <table class="table table-bordered table-sm align-middle"
                                                        id="perm-table-{{ $role->id }}"
                                                        data-role="{{ $role->id }}">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th style="min-width:160px;">Feature</th>
                                                                @foreach ($orderedActions as $action)
                                                                    <th class="text-center" style="min-width:110px;">
                                                                        <div class="d-flex flex-column align-items-center">
                                                                            <label class="mb-1" style="font-size:.8rem;font-weight:600;">
                                                                                {{ $actionLabels[$action] ?? ucfirst(str_replace('_', ' ', $action)) }}
                                                                            </label>
                                                                            <input class="form-check-input column-toggle"
                                                                                type="checkbox"
                                                                                data-role="{{ $role->id }}"
                                                                                data-action="{{ $action }}"
                                                                                title="Toggle all {{ $action }}">
                                                                        </div>
                                                                    </th>
                                                                @endforeach
                                                                <th class="text-center" style="min-width:60px;">All</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <th class="module_th">Staff Management</th>
                                                                @foreach ($orderedActions as $action)
                                                                    @php
                                                                        $pid = $fpByAction[$action] ?? null;
                                                                        $isChecked = $pid && in_array($pid, $assignedIds);
                                                                    @endphp
                                                                    <td class="text-center">
                                                                        @if ($pid)
                                                                            <input class="form-check-input perm-checkbox"
                                                                                type="checkbox"
                                                                                name="permissions[]"
                                                                                value="{{ $pid }}"
                                                                                data-role="{{ $role->id }}"
                                                                                data-action="{{ $action }}"
                                                                                @if($isChecked) checked @endif>
                                                                        @else
                                                                            <span class="text-muted">—</span>
                                                                        @endif
                                                                    </td>
                                                                @endforeach
                                                                <td class="text-center">
                                                                    <input class="form-check-input row-toggle"
                                                                        type="checkbox"
                                                                        data-role="{{ $role->id }}"
                                                                        @if($rowAllOn) checked @endif>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <div class="d-flex justify-content-end mt-2">
                                                    <button type="submit" class="btn btn-sm btn--primary">
                                                        <i class="tio-save"></i> Save Permissions
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @endif
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
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
(function () {
    function getCheckboxes(role, action) {
        return Array.from(document.querySelectorAll(
            `.perm-checkbox[data-role="${role}"]` + (action ? `[data-action="${action}"]` : '')
        ));
    }

    function refreshStates(role) {
        const allBoxes = getCheckboxes(role, null).filter(cb => !cb.disabled);

        // Column toggles
        document.querySelectorAll(`.column-toggle[data-role="${role}"]`).forEach(col => {
            const action = col.dataset.action;
            const boxes = getCheckboxes(role, action).filter(cb => !cb.disabled);
            col.checked = boxes.length > 0 && boxes.every(cb => cb.checked);
        });

        // Row toggle
        const rowTgl = document.querySelector(`.row-toggle[data-role="${role}"]`);
        if (rowTgl) {
            rowTgl.checked = allBoxes.length > 0 && allBoxes.every(cb => cb.checked);
        }
    }

    document.addEventListener('change', function (e) {
        const role = e.target.dataset.role;
        if (!role) return;

        if (e.target.classList.contains('row-toggle')) {
            getCheckboxes(role, null).forEach(cb => { if (!cb.disabled) cb.checked = e.target.checked; });
            document.querySelectorAll(`.column-toggle[data-role="${role}"]`).forEach(col => col.checked = e.target.checked);
        } else if (e.target.classList.contains('column-toggle')) {
            getCheckboxes(role, e.target.dataset.action).forEach(cb => { if (!cb.disabled) cb.checked = e.target.checked; });
            refreshStates(role);
        } else if (e.target.classList.contains('perm-checkbox')) {
            refreshStates(role);
        }
    });

    // Init states on page load
    document.querySelectorAll('.row-toggle').forEach(t => refreshStates(t.dataset.role));
})();
</script>
@endpush
