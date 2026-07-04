@php
    // Shared HR navigation bar — included on every HR page. Each tab is its own page (link),
    // the bar highlights the active section from the URL, and items are permission-gated.
    $hrTabs = [
        ['label' => 'Dashboard',   'icon' => 'tio-dashboard-outlined', 'route' => 'vendor.hr.dashboard',            'active' => Request::is('hr/dashboard'),                                                                   'show' => hasPermission('hr_manage', 'dashboard')],
        ['label' => 'Staff',       'icon' => 'tio-group-junior',  'route' => 'vendor.staff.list',                  'active' => Request::is('staff/list', 'staff/add-new', 'staff/view*', 'staff/edit*', 'staff/settings*'), 'show' => hasAnyModulePermission(['staff_manage'])],
        ['label' => 'Departments', 'icon' => 'tio-folder',         'route' => 'vendor.staff-department.all',         'active' => Request::is('staff-department*'),                                                              'show' => hasAnyModulePermission(['staff_department'])],
        ['label' => 'Roles',       'icon' => 'tio-lock-outlined',  'route' => 'vendor.custom-role.create',           'active' => Request::is('custom-role*'),                                                                   'show' => hasAnyModulePermission(['staff_role'])],
        ['label' => 'Teams',       'icon' => 'tio-users',          'route' => 'vendor.staff.team.index',             'active' => Request::is('staff/team*'),                                                                    'show' => hasAnyModulePermission(['staff_team'])],
        ['label' => 'Shifts',      'icon' => 'tio-timer',          'route' => 'vendor.shifts.index',                 'active' => Request::is('shifts*'),                                                                        'show' => hasAnyModulePermission(['shift_manage'])],
        ['label' => 'Attendance',  'icon' => 'tio-event',          'route' => 'vendor.attendance.all',               'active' => Request::is('attendance/list', 'attendance/manage*'),                                          'show' => hasAnyModulePermission(['attendance_manage'])],
        ['label' => 'Leave',       'icon' => 'tio-calendar-note',  'route' => 'vendor.leave.all',                    'active' => Request::is('leave*'),                                                                         'show' => hasAnyModulePermission(['leave_manage', 'attendance_manage'])],
        ['label' => 'Salary',      'icon' => 'tio-money',          'route' => 'vendor.salary.list',                  'active' => Request::is('salary/list', 'salary/edit*', 'salary/manage*'),                                  'show' => hasAnyModulePermission(['salary_manage'])],
        ['label' => 'Advances',    'icon' => 'tio-wallet',         'route' => 'vendor.salary.all-advance-requests',  'active' => Request::is('salary/all-advance*'),                                                            'show' => hasAnyModulePermission(['advance_requests'])],
        ['label' => 'Reports',     'icon' => 'tio-chart-bar-4',    'route' => 'vendor.attendance.report',            'active' => Request::is('attendance/report', 'salary/report'),                                             'show' => hasAnyModulePermission(['attendance_report', 'salary_report'])],
    ];
@endphp
<style>
    .hrnav {
        display: flex; gap: 4px; background: #fff; border-radius: 12px 12px 0 0;
        padding: 6px 8px 0; border: 1px solid #eef0f4; border-bottom: none;
        overflow-x: auto; flex-wrap: nowrap; position: sticky; top: 0; z-index: 20;
    }
    .hrnav-tab {
        border: none; background: none; cursor: pointer; white-space: nowrap; text-decoration: none;
        padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280;
        border-bottom: 3px solid transparent; border-radius: 8px 8px 0 0; transition: all .15s;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .hrnav-tab:hover { color: #2563eb; background: #f5f8ff; text-decoration: none; }
    .hrnav-tab.active { color: #1d4ed8; border-bottom-color: #2563eb; background: #f5f8ff; }
</style>
<div class="hrnav">
    @foreach ($hrTabs as $t)
        @if ($t['show'] && Route::has($t['route']))
            <a class="hrnav-tab {{ $t['active'] ? 'active' : '' }}" href="{{ route($t['route']) }}">
                <i class="{{ $t['icon'] }}"></i> {{ $t['label'] }}
            </a>
        @endif
    @endforeach
</div>
