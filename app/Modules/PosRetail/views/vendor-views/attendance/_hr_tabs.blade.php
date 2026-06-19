@php $active = $active ?? 'staff'; @endphp
<style>
    .al-tabs {
        display: flex; gap: 4px; background: #fff; border-radius: 12px 12px 0 0;
        padding: 6px 8px 0; border: 1px solid #eef0f4; border-bottom: none;
        overflow-x: auto; flex-wrap: nowrap; position: sticky; top: 0; z-index: 20;
    }
    .al-tab {
        border: none; background: none; cursor: pointer; white-space: nowrap; text-decoration: none;
        padding: 12px 18px; font-size: 14px; font-weight: 600; color: #6b7280;
        border-bottom: 3px solid transparent; border-radius: 8px 8px 0 0; transition: all .15s;
        display: inline-flex; align-items: center; gap: 7px;
    }
    .al-tab:hover { color: #2563eb; background: #f5f8ff; text-decoration: none; }
    .al-tab.active { color: #1d4ed8; border-bottom-color: #2563eb; background: #f5f8ff; }
</style>
<div class="al-tabs">
    @if (hasPermission('attendance_manage', 'list'))
        <a class="al-tab {{ $active === 'staff' ? 'active' : '' }}" href="{{ route('vendor.attendance.all') }}#staff">
            <i class="tio-group-equal"></i> Staff
        </a>
    @endif
    @if (hasAnyModulePermission(['leave_manage', 'attendance_manage']))
        <a class="al-tab {{ $active === 'leaves' ? 'active' : '' }}" href="{{ route('vendor.attendance.all') }}#leaves">
            <i class="tio-calendar-note"></i> Leave Requests
        </a>
    @endif
    @if (hasAnyModulePermission(['attendance_report', 'attendance_manage']))
        <a class="al-tab {{ $active === 'reports' ? 'active' : '' }}" href="{{ route('vendor.attendance.report') }}">
            <i class="tio-chart-bar-4"></i> Reports
        </a>
    @endif
</div>
