@php
    // Free-tier staff navigation bar — shown on the basic Staff Management pages
    // (and on the shared custom-role page when the store has no HR plan). Mirrors the
    // premium _hr_nav styling but only exposes the free basic_staff_manage actions.
    $bsTabs = [
        ['label' => 'Staff List',          'icon' => 'tio-group-junior', 'route' => 'vendor.basic-staff.index',  'active' => Request::is('basic-staff') || Request::is('basic-staff/edit/*'), 'show' => hasPermission('basic_staff_manage', 'list')],
        ['label' => 'Add Staff',           'icon' => 'tio-add',          'route' => 'vendor.basic-staff.create', 'active' => Request::is('basic-staff/create'),                                'show' => hasPermission('basic_staff_manage', 'add')],
        ['label' => 'Roles & Permissions', 'icon' => 'tio-lock-outlined','route' => 'vendor.basic-staff.roles',  'active' => Request::is('basic-staff/roles*') || Request::is('custom-role*'),  'show' => hasPermission('basic_staff_manage', 'role_manage')],
    ];
@endphp
<style>
    .bsnav {
        display: flex; gap: 4px; background: #fff; border-radius: 12px 12px 0 0;
        padding: 6px 8px 0; border: 1px solid #eef0f4; border-bottom: none;
        overflow-x: auto; flex-wrap: nowrap; position: sticky; top: 0; z-index: 20;
    }
    .bsnav-tab {
        border: none; background: none; cursor: pointer; white-space: nowrap; text-decoration: none;
        padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280;
        border-bottom: 3px solid transparent; border-radius: 8px 8px 0 0; transition: all .15s;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .bsnav-tab:hover { color: #2563eb; background: #f5f8ff; text-decoration: none; }
    .bsnav-tab.active { color: #1d4ed8; border-bottom-color: #2563eb; background: #f5f8ff; }
</style>
<div class="bsnav">
    @foreach ($bsTabs as $t)
        @if ($t['show'] && Route::has($t['route']))
            <a class="bsnav-tab {{ $t['active'] ? 'active' : '' }}" href="{{ route($t['route']) }}">
                <i class="{{ $t['icon'] }}"></i> {{ $t['label'] }}
            </a>
        @endif
    @endforeach
</div>
