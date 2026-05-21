{{-- Laundry items as top-level --}}
@if (\App\CentralLogics\Helpers::permission_check('laundry'))
    <li class="nav-item">
        <small class="nav-subtitle"
            title="{{ translate('Laundry Management') }}">{{ translate('Laundry Management') }}</small>
        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
    </li>
    @if (selected_menu('dashboard'))
        <li
            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel') || Request::is('dashboard*') ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.dashboard') }}"
                title="Dashboard">
                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Dashboard_color.png') }}" alt=""
                    class="nav-link-icon">
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Dashboard</span>
            </a>
        </li>
    @endif

    <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/master-dashboard') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link"
            href="{{ route('vendor.master-dashboard') }}"
            title="{{ translate('messages.dashboard') }}">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Dashboard_color.png') }}"
                alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                Master {{ translate('messages.dashboard') }}
            </span>
        </a>
    </li>

    @if (selected_menu('walk_in_orders'))
        <li class="navbar-vertical-aside-has-menu {{ Request::is('laundry/orders*') ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.laundry.orders') }}"
                title="Walk-in Orders">
                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/walk_in_orders.png') }}" alt=""
                    class="nav-link-icon">
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Walk-in Orders</span>
            </a>
        </li>
    @endif
    @if (selected_menu('hotel_challans'))
        <li class="navbar-vertical-aside-has-menu {{ Request::is('laundry/challans*') ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.laundry.challans') }}"
                title="Hotel Challans">
                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/hotel_challans.png') }}" alt=""
                    class="nav-link-icon">
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Hotel Challans</span>
            </a>
        </li>
    @endif
    @if (selected_menu('monthly_register'))
        <li class="navbar-vertical-aside-has-menu {{ Request::is('laundry/register*') ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.laundry.register') }}"
                title="Monthly Register">
                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/monthly_register.png') }}" alt=""
                    class="nav-link-icon">
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Monthly Register</span>
            </a>
        </li>
    @endif
@else
    @if (selected_menu('dashboard'))
        <li
            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel') || Request::is('dashboard*') ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.dashboard') }}"
                title="Dashboard">
                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Dashboard_color.png') }}" alt=""
                    class="nav-link-icon">
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Dashboard</span>
            </a>
        </li>
    @endif
@endif

{{-- Inventory Management — free for all laundry vendors --}}
@if (selected_menu('inventory_manage'))
        <li class="navbar-vertical-aside-has-menu {{ Request::is('inventory*') ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                title="{{ _moduleLabel('inventory_manage') }}">
                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Inventory_management_color.png') }}"
                    alt="" class="nav-link-icon">
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                    {{ _moduleLabel('inventory_manage') }}</span>
            </a>
            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                @if (hasPermission('inventory', 'dashboard'))
                    <li
                        class="navbar-vertical-aside-has-menu {{ Request::is('inventory/dashboard') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('vendor.inventory.dashboard') }}" title="Dashboard">
                            <span class="tio-dashboard nav-icon"></span>
                            <span class="text-truncate">Inventory Dashboard</span>
                        </a>
                    </li>
                @endif
                <li
                    class="navbar-vertical-aside-has-menu {{ Request::is('inventory') || Request::is('inventory/storage-spaces') ? 'active' : '' }}">
                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                        title="Products / Services">
                        <span class="tio-layers nav-icon"></span>
                        <span class="text-truncate">Products / Services</span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                        style="display: {{ Request::is('inventory') || Request::is('inventory/storage-spaces') ? 'block' : 'none' }}">
                        <li class="nav-item {{ Request::is('inventory') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('vendor.inventory.index') }}"
                                title="Inventory Items">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate sidebar--badge-container">Items</span>
                            </a>
                        </li>
                        @if (hasAnyPermission(['inventory_storage_units.list', 'inventory_storage_units.add']))
                            <li class="nav-item {{ Request::is('inventory/storage-spaces') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.inventory.storage-spaces') }}"
                                    title="Storage Units">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Storage Units</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
                @if (hasAnyPermission(['inventory_stock_in_out.list']))
                    <li
                        class="navbar-vertical-aside-has-menu {{ Request::is('inventory/stock*') ? 'active' : '' }}">
                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                            href="javascript:" title="Stock Management">
                            <span class="tio-stack nav-icon"></span>
                            <span class="text-truncate">Stock Management</span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                            style="display: {{ Request::is('inventory/stock*') ? 'block' : 'none' }}">
                            @if (hasPermission('inventory_stock_in_out', 'list'))
                                <li
                                    class="nav-item {{ Request::is('inventory/stock/stock-in-out') ? 'active' : '' }}">
                                    <a class="nav-link"
                                        href="{{ route('vendor.inventory.stock.stock-in-out') }}"
                                        title="Stock in / Stock out">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Stock In / Out</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif
                @if (hasAnyPermission(['inventory_sale_order.list', 'inventory_sale_return.list']))
                    <li
                        class="navbar-vertical-aside-has-menu {{ Request::is('inventory/sale*') ? 'active' : '' }}">
                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                            href="javascript:" title="Sales">
                            <span class="tio-sale nav-icon"></span>
                            <span class="text-truncate">Sales</span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                            style="display: {{ Request::is('inventory/sale*') ? 'block' : 'none' }}">
                            @if (hasPermission('inventory_sale_order', 'list'))
                                <li
                                    class="nav-item {{ Request::is('inventory/sale/orders') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.inventory.sale.orders') }}"
                                        title="Sale Orders">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Sale Orders</span>
                                    </a>
                                </li>
                            @endif
                            @if (hasPermission('inventory_sale_return', 'list'))
                                <li
                                    class="nav-item {{ Request::is('inventory/sale/orders-return') ? 'active' : '' }}">
                                    <a class="nav-link"
                                        href="{{ route('vendor.inventory.sale.orders-return') }}"
                                        title="Return Orders">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Return Orders</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif
                @if (hasAnyPermission(['inventory_purchase_order.list', 'inventory_purchase_return.add']))
                    <li
                        class="navbar-vertical-aside-has-menu {{ Request::is('inventory/purchase*') ? 'active' : '' }}">
                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                            href="javascript:" title="Purchase">
                            <span class="tio-shopping-cart nav-icon"></span>
                            <span class="text-truncate">Purchase</span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                            style="display: {{ Request::is('inventory/purchase*') ? 'block' : 'none' }}">
                            @if (hasPermission('inventory_purchase_order', 'list'))
                                <li
                                    class="nav-item {{ Request::is('inventory/purchase/orders') ? 'active' : '' }}">
                                    <a class="nav-link"
                                        href="{{ route('vendor.inventory.purchase.orders') }}"
                                        title="Purchase Orders">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Purchase Orders</span>
                                    </a>
                                </li>
                            @endif
                            @if (hasPermission('inventory_purchase_return', 'add'))
                                <li
                                    class="nav-item {{ Request::is('inventory/purchase/return') ? 'active' : '' }}">
                                    <a class="nav-link"
                                        href="{{ route('vendor.inventory.purchase.return') }}"
                                        title="Return Purchase">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Return Purchase</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif
                <li
                    class="navbar-vertical-aside-has-menu {{ Request::is('inventory/gatepass*') ? 'active' : '' }}">
                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                        title="Gatepass">
                        <span class="tio-document nav-icon"></span>
                        <span class="text-truncate">Gatepass</span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                        style="display: {{ Request::is('inventory/gatepass*') ? 'block' : 'none' }}">
                        <li class="nav-item">
                            <a class="nav-link"
                                href="{{ route('vendor.inventory.gatepass.list', ['purchase']) }}"
                                title="Purchase Gatepass">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">Purchase Gatepass</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link"
                                href="{{ route('vendor.inventory.gatepass.list', ['sale']) }}"
                                title="Sale Gatepass">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">Sale Gatepass</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li
                    class="navbar-vertical-aside-has-menu {{ Request::is('inventory/report*') ? 'active' : '' }}">
                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                        title="Reports">
                        <span class="tio-chart-bar-2 nav-icon"></span>
                        <span class="text-truncate">Reports</span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                        style="display: {{ Request::is('inventory/report*') ? 'block' : 'none' }}">
                        <li class="nav-item"><a class="nav-link"
                                href="{{ route('vendor.inventory.report.stock') }}"
                                title="Stock Report"><span class="tio-circle nav-indicator-icon"></span><span
                                    class="text-truncate">Stock Report</span></a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="{{ route('vendor.inventory.report.sale') }}" title="Sales Report"><span
                                    class="tio-circle nav-indicator-icon"></span><span class="text-truncate">Sales
                                    Report</span></a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="{{ route('vendor.inventory.report.purchase') }}"
                                title="Purchase Report"><span class="tio-circle nav-indicator-icon"></span><span
                                    class="text-truncate">Purchase Report</span></a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="{{ route('vendor.inventory.report.profit-and-loss') }}"
                                title="Profit & Loss"><span class="tio-circle nav-indicator-icon"></span><span
                                    class="text-truncate">Profit & Loss</span></a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="{{ route('vendor.inventory.report.gst') }}" title="GST Report"><span
                                    class="tio-circle nav-indicator-icon"></span><span class="text-truncate">GST
                                    Report</span></a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="{{ route('vendor.inventory.report.batch-expiry') }}"
                                title="Batch & Expiry"><span class="tio-circle nav-indicator-icon"></span><span
                                    class="text-truncate">Batch & Expiry</span></a></li>
                    </ul>
                </li>

                @if (hasPermission('inventory', 'settings'))
                    <li
                        class="navbar-vertical-aside-has-menu {{ Request::is('inventory/settings') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('vendor.inventory.settings') }}"
                            title="Settings">
                            <span class="tio-settings-outlined nav-icon"></span>
                            <span class="text-truncate">Inventory Settings</span>
                        </a>
                    </li>
                @endif
            </ul>
        </li>
    @endif

<li class="nav-item">
    <small class="nav-subtitle" title="{{ translate('Other') }}">{{ translate('Other') }}</small>
    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
</li>

{{-- Basic Staff Management (free tier, laundry) --}}
@if (!\App\CentralLogics\Helpers::permission_check('hr_manage') && selected_menu('staff_manage'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('basic-staff*') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
            title="Staff Management">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/HR_management_color.png') }}"
                alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Staff Management</span>
        </a>
        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
            style="display: {{ Request::is('basic-staff*') ? 'block' : 'none' }}">
            <li class="nav-item {{ Request::is('basic-staff') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('vendor.basic-staff.index') }}" title="Staff List">
                    <span class="tio-circle nav-indicator-icon"></span>
                    <span class="text-truncate">Staff List</span>
                </a>
            </li>
            <li
                class="nav-item {{ Request::is('basic-staff/create') || Request::is('basic-staff/edit/*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('vendor.basic-staff.create') }}" title="Add Staff">
                    <span class="tio-circle nav-indicator-icon"></span>
                    <span class="text-truncate">Add Staff</span>
                </a>
            </li>
            <li class="nav-item {{ Request::is('basic-staff/roles*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('vendor.basic-staff.roles') }}" title="Roles">
                    <span class="tio-circle nav-indicator-icon"></span>
                    <span class="text-truncate">Roles &amp; Permissions</span>
                </a>
            </li>
        </ul>
    </li>
@endif

{{-- HR Management (Laundry) --}}
@if (
    \App\CentralLogics\Helpers::permission_check('laundry') &&
        (selected_menu('staff_manage') ||
            selected_menu('attendance_manage') ||
            selected_menu('leave_manage') ||
            selected_menu('salary_manage')) &&
        hasMasterModulePermission('hr_manage'))
    <li
        class="navbar-vertical-aside-has-menu {{ Request::is('hr*') || Request::is('staff*') || Request::is('attendance*') || Request::is('salary*') || Request::is('shifts*') || Request::is('leave*') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
            title="HR Management">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/HR_management_color.png') }}"
                alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">HR Management</span>
        </a>
        <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
            @if (hasPermission('hr_manage', 'dashboard'))
                <li class="nav-item {{ Request::is('hr/dashboard') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.hr.dashboard') }}" title="HR Dashboard">
                        <span class="tio-dashboard-outlined nav-icon"></span>
                        <span class="text-truncate">Dashboard</span>
                    </a>
                </li>
            @endif
            @if (hasAnyModulePermission(['staff_manage', 'staff_team', 'staff_department']) && selected_menu('staff_manage'))
                <li
                    class="navbar-vertical-aside-has-menu {{ Request::is('staff*') || Request::is('staff-department*') ? 'active' : '' }}">
                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                        href="javascript:;" title="Staff Management">
                        <i class="tio-group-junior nav-icon"></i>
                        <span class="text-truncate">Staff Management</span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                        style="display: {{ Request::is('staff*') || Request::is('staff-department*') ? 'block' : 'none' }}">
                        @if (hasPermission('staff_manage', 'add'))
                            <li class="nav-item {{ Request::is('staff/add-new') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.staff.add-new') }}"
                                    title="Add Staff">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Add Staff</span>
                                </a>
                            </li>
                        @endif
                        @if (hasAnyPermission(['staff_manage.list']))
                            <li class="nav-item {{ Request::is('staff/list') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.staff.list') }}"
                                    title="Staff List">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Staff List</span>
                                </a>
                            </li>
                        @endif
                        @if (hasAnyModulePermission(['staff_team']))
                            <li class="nav-item {{ Request::is('staff/team') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.staff.team.index') }}"
                                    title="Teams">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Teams</span>
                                </a>
                            </li>
                        @endif
                        @if (hasAnyModulePermission(['staff_role']))
                            <li class="nav-item {{ Request::is('custom-role/create') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('vendor.custom-role.create') }}"
                                    title="Staff Role">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Staff Roles (Permissions)</span>
                                </a>
                            </li>
                        @endif
                        @if (hasAnyModulePermission(['staff_department']))
                            <li class="nav-item {{ Request::is('staff-department*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.staff-department.all') }}"
                                    title="Departments">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Staff Department</span>
                                </a>
                            </li>
                        @endif
                        @if (hasPermission('staff_manage', 'settings'))
                            <li class="nav-item {{ Request::is('staff/settings*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.staff.settings') }}"
                                    title="Staff Settings">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Staff Settings</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if (selected_menu('attendance_manage') && hasAnyModulePermission(['attendance_manage', 'attendance_report']))
                <li class="navbar-vertical-aside-has-menu {{ Request::is('attendance*') ? 'active' : '' }}">
                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                        href="javascript:;" title="Attendance">
                        <i class="tio-event nav-icon"></i>
                        <span class="text-truncate">Attendance Management</span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                        style="display: {{ Request::is('attendance*') ? 'block' : 'none' }}">
                        @if (hasPermission('attendance_manage', 'list'))
                            <li class="nav-item {{ Request::is('attendance/list') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.attendance.all') }}"
                                    title="Attendance Manage">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Attendance Manage</span>
                                </a>
                            </li>
                        @endif
                        @if (hasAnyModulePermission(['attendance_report']))
                            <li class="nav-item {{ Request::is('attendance/report') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.attendance.report') }}"
                                    title="Reports">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Attendance Reports</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if (selected_menu('salary_manage') && hasAnyModulePermission(['salary_manage', 'salary_report']))
                <li class="navbar-vertical-aside-has-menu {{ Request::is('salary*') ? 'active' : '' }}">
                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                        href="javascript:;" title="Salary">
                        <i class="tio-user nav-icon"></i>
                        <span class="text-truncate">Salary Management</span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                        style="display: {{ Request::is('salary*') ? 'block' : 'none' }}">
                        @if (hasAnyModulePermission(['salary_manage']))
                            <li class="nav-item {{ Request::is('salary/list') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.salary.list') }}"
                                    title="Salary Manage">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Salary Manage</span>
                                </a>
                            </li>
                        @endif
                        @if (hasAnyModulePermission(['salary_report']))
                            <li class="nav-item {{ Request::is('salary/report') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.salary.report') }}"
                                    title="Salary Report">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Salary Report</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if (hasAnyModulePermission(['shift_manage']))
                <li class="navbar-vertical-aside {{ Request::is('shifts*') ? 'active' : '' }}">
                    <a class="sub-link nav-link" href="{{ route('vendor.shifts.index') }}" title="Shifts">
                        <i class="tio-timer nav-icon"></i>
                        <span class="text-truncate">Shifts Management</span>
                    </a> 
                </li>
            @endif
            @if (selected_menu('leave_manage') && hasAnyModulePermission(['leave_manage']))
                <li class="navbar-vertical-aside {{ Request::is('leave*') ? 'active' : '' }}">
                    <a class="sub-link nav-link" href="{{ route('vendor.leave.all') }}" title="Leave">
                        <i class="tio-category nav-icon"></i>
                        <span class="text-truncate">Leave Management</span>
                    </a>
                </li>
            @endif
        </ul>
    </li>
@endif

{{-- ── Task Management (Laundry) ─────────────────────────────────── --}}
@if (
    \App\CentralLogics\Helpers::permission_check('laundry') &&
        selected_menu('task_management') &&
        hasMasterModulePermission('task_manage'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('task*') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
            title="Task Management">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Tasks_management_color.png') }}"
                alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Task Management</span>
        </a>
        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
            style="display: {{ Request::is('task*') ? 'block' : 'none' }}">
            @if (hasAnyPermission(['task.list', 'task.export', 'task.add']))
                <li class="nav-item {{ Request::is('task/list*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.task.list') }}" title="Tasks">
                        <span class="tio-circle nav-indicator-icon"></span>
                        <span class="text-truncate">Tasks</span>
                    </a>
                </li>
            @endif
            @if (hasPermission('task', 'settings'))
                <li class="nav-item {{ Request::is('task/setting') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.task.setting') }}" title="Task Settings">
                        <span class="tio-circle nav-indicator-icon"></span>
                        <span class="text-truncate">Task Settings</span>
                    </a>
                </li>
            @endif
        </ul>
    </li>
@endif

{{-- ── Project Management (Laundry) ────────────────────────────── --}}
@if (
    \App\CentralLogics\Helpers::permission_check('laundry') &&
        selected_menu('project_manage') &&
        hasMasterModulePermission('projects_manage'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('project*') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
            title="Project Management">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Project%20_management_color.png') }}"
                alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Project Management</span>
        </a>
        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
            style="display: {{ Request::is('project*') ? 'block' : 'none' }}">
            @if (hasPermission('project', 'add'))
                <li class="nav-item {{ Request::is('project/add') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.project.add') }}" title="Add New Project">
                        <span class="tio-circle nav-indicator-icon"></span>
                        <span class="text-truncate">Add New Project</span>
                    </a>
                </li>
            @endif
            @if (hasPermission('project', 'list'))
                <li class="nav-item {{ Request::is('project/list*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.project.all') }}" title="Projects List">
                        <span class="tio-circle nav-indicator-icon"></span>
                        <span class="text-truncate">Projects List</span>
                    </a>
                </li>
            @endif
            @if (hasPermission('project', 'settings'))
                <li class="nav-item {{ Request::is('project/settings') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.project.settings') }}"
                        title="Project Settings">
                        <span class="tio-circle nav-indicator-icon"></span>
                        <span class="text-truncate">Project Settings</span>
                    </a>
                </li>
            @endif
        </ul>
    </li>
@endif



{{-- ── Client Management (Laundry) ─────────────────────────────── --}}
@if (
    \App\CentralLogics\Helpers::permission_check('laundry') &&
        selected_menu('client_manage') &&
        hasMasterModulePermission('client_manage'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('client*') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
            title="Client Management">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Clients_management_color.png') }}"
                alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Client Management</span>
        </a>
        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
            style="display: {{ Request::is('client*') ? 'block' : 'none' }}">
            @if (hasPermission('client_manage', 'add'))
                <li class="nav-item {{ Request::is('client/add-new') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.customer.add') }}" title="Add New Client">
                        <span class="tio-circle nav-indicator-icon"></span>
                        <span class="text-truncate">Add New Client</span>
                    </a>
                </li>
            @endif
            @if (hasAnyPermission(['client_manage.list', 'client_manage.import', 'client_manage.export']))
                <li class="nav-item {{ Request::is('client/list*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.customer.list') }}" title="Clients List">
                        <span class="tio-circle nav-indicator-icon"></span>
                        <span class="text-truncate">Clients List</span>
                    </a>
                </li>
            @endif
        </ul>
    </li>
@endif

{{-- ── Billing (Laundry) ────────────────────────────────────────── --}}
@if (
        \App\CentralLogics\Helpers::employee_module_permission_check('billing') &&
        selected_menu('billing'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('billing*') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="Billing">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Billing_management_color.png') }}"
                alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Billing</span>
        </a>
        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
            style="display: {{ Request::is('billing*') ? 'block' : 'none' }}">
            @if (hasPermission('billing', 'add_basic'))
                <li class="nav-item {{ Request::is('billing/manual-bill') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.invoice.manual-bill') }}"
                        title="Create Invoice">
                        <span class="tio-circle nav-indicator-icon"></span>
                        <span class="text-truncate">Create Invoice</span>
                    </a>
                </li>
            @endif
            @if (hasAnyPermission(['billing.list', 'billing.export', 'billing.import']))
                <li class="nav-item {{ Request::is('billing/list') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.invoice.list') }}" title="Invoice List">
                        <span class="tio-circle nav-indicator-icon"></span>
                        <span class="text-truncate">Invoice List</span>
                    </a>
                </li>
            @endif
            @if (hasMasterModulePermission('billing') || hasMasterModulePermission('advanced_billing'))
                <li class="nav-item {{ Request::is('billing/purchase-bills') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.invoice.my-bills') }}"
                        title="Purchase Bills">
                        <span class="tio-circle nav-indicator-icon"></span>
                        <span class="text-truncate">Purchase Bills</span>
                    </a>
                </li>
            @endif
            @if (hasPermission('billing', 'settings') ||
                    hasAnyModulePermission(['billing_bank_account', 'billing_signatures', 'billing_tnc']))
                <li class="nav-item {{ Request::is('billing/settings') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.invoice.settings') }}"
                        title="Billing Settings">
                        <span class="tio-circle nav-indicator-icon"></span>
                        <span class="text-truncate">Billing Settings</span>
                    </a>
                </li>
            @endif
        </ul>
    </li>
@endif

{{-- ── Quotation Management (Laundry) ──────────────────────────── --}}
@if (
    \App\CentralLogics\Helpers::permission_check('laundry') &&
        hasMasterModulePermission('quotaiton_manage') &&
        selected_menu('quotation_manage'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('quotation*') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
            title="Quotation Management">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Quotations_management_color.png') }}"
                alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Quotation Management</span>
        </a>
        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
            style="display: {{ Request::is('quotation*') ? 'block' : 'none' }}">
            @if (hasPermission('quotaiton_manage', 'add'))
                <li class="nav-item {{ Request::is('quotation/add') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.quotation.add') }}"
                        title="Add New Quotation">
                        <span class="tio-circle nav-indicator-icon"></span>
                        <span class="text-truncate">Add New Quotation</span>
                    </a>
                </li>
            @endif
            @if (hasAnyPermission(['quotaiton_manage.list']))
                <li class="nav-item {{ Request::is('quotation/list') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.quotation.list') }}" title="Quotation List">
                        <span class="tio-circle nav-indicator-icon"></span>
                        <span class="text-truncate">Quotation List</span>
                    </a>
                </li>
            @endif
            @if (hasPermission('quotaiton_manage', 'settings') ||
                    hasAnyModulePermission(['quotation_sign', 'quotation_bank_account']))
                <li class="nav-item {{ Request::is('quotation/settings') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.quotation.settings') }}"
                        title="Quotation Settings">
                        <span class="tio-circle nav-indicator-icon"></span>
                        <span class="text-truncate">Quotation Settings</span>
                    </a>
                </li>
            @endif
        </ul>
    </li>
@endif

{{-- ── Account Management (Laundry) ────────────────────────────── --}}
@if ( selected_menu('account_manage') &&
        hasMasterModulePermission('account_manage'))
    <li
        class="navbar-vertical-aside-has-menu {{ Request::is('account*') || Request::is('asset*') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
            title="Account Management">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Billing_management_color.png') }}"
                alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Account Management</span>
        </a>
        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
            style="display: {{ Request::is('account*') || Request::is('asset*') ? 'block' : 'none' }}">
            @if (hasPermission('dashboard', 'view'))
                <li class="nav-item {{ Request::is('account/dashboard') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.account.dashboard') }}" title="Dashboard">
                        <span class="tio-dashboard nav-icon"></span>
                        <span class="text-truncate">Dashboard</span>
                    </a>
                </li>
            @endif
            @if (hasMasterModulePermission('account_manage'))
                <li
                    class="navbar-vertical-aside-has-menu {{ Request::is('account/management*') ? 'active' : '' }}">
                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                        href="javascript:;" title="Book of Accounts">
                        <span class="tio-book nav-icon"></span>
                        <span class="text-truncate">Book of Accounts</span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                        style="display: {{ Request::is('account/management*') ? 'block' : 'none' }}">
                        <li class="nav-item {{ Request::is('account/management') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('vendor.account.add') }}"
                                title="Master Ledger">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">Master Ledger</span>
                            </a>
                        </li>
                        <li class="nav-item {{ Request::is('account/journal-entry') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('vendor.account.journal-entry.index') }}"
                                title="Journal Entry">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">Journal Entry</span>
                            </a>
                        </li>
                        <li class="nav-item {{ Request::is('account/day-book') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('vendor.account.day-book.index') }}"
                                title="Day Book">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">Day Book</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li
                    class="navbar-vertical-aside-has-menu {{ Request::is('account/banking*') ? 'active' : '' }}">
                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                        href="javascript:;" title="Banking">
                        <span class="tio-bank nav-icon"></span>
                        <span class="text-truncate">Banking</span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                        style="display: {{ Request::is('account/banking*') ? 'block' : 'none' }}">
                        @if (hasAnyModulePermission(['banking_bank_accounts']))
                            <li
                                class="nav-item {{ Request::is('account/banking/bank-account') ? 'active' : '' }}">
                                <a class="nav-link"
                                    href="{{ route('vendor.account.banking.bank-account.index') }}"
                                    title="Bank Accounts">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Bank Accounts</span>
                                </a>
                            </li>
                        @endif
                        @if (hasAnyModulePermission(['banking_cash_book']))
                            <li
                                class="nav-item {{ Request::is('account/banking/cash-book') ? 'active' : '' }}">
                                <a class="nav-link"
                                    href="{{ route('vendor.account.banking.cash-book.index') }}"
                                    title="Cash Book">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Cash Book</span>
                                </a>
                            </li>
                        @endif
                        @if (hasAnyModulePermission(['banking_bank_reconciliation']))
                            <li
                                class="nav-item {{ Request::is('account/banking/bank-reconciliation') ? 'active' : '' }}">
                                <a class="nav-link"
                                    href="{{ route('vendor.account.banking.bank-reconciliation.index') }}"
                                    title="Bank Reconciliation">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Bank Reconciliation</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
                @if (hasAnyModulePermission(['reports_account_report', 'reports_tax_report', 'reports_audit_logs']))
                    <li
                        class="navbar-vertical-aside-has-menu {{ Request::is('account/report*') ? 'active' : '' }}">
                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                            href="javascript:;" title="Reports">
                            <span class="tio-chart-bar-2 nav-icon"></span>
                            <span class="text-truncate">Reports</span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                            style="display: {{ Request::is('account/report*') ? 'block' : 'none' }}">
                            @if (hasAnyModulePermission(['reports_tax_report']))
                                <li class="nav-item {{ Request::is('account/report/tax') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.account.report.tax') }}"
                                        title="Tax Reports">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Tax Reports (GST/VAT)</span>
                                    </a>
                                </li>
                            @endif
                            @if (hasAnyModulePermission(['reports_audit_logs']))
                                <li
                                    class="nav-item {{ Request::is('account/report/audit-logs') ? 'active' : '' }}">
                                    <a class="nav-link"
                                        href="{{ route('vendor.account.report.audit-logs') }}"
                                        title="Audit Logs">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Audit Logs</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif
                @if (hasAnyModulePermission(['settings_account_type', 'settings_common']))
                    <li class="nav-item {{ Request::is('account/setting*') ? 'active' : '' }}">
                        <a class="sub-link nav-link"
                            href="{{ route('vendor.account.setting.common-settings') }}" title="Settings">
                            <span class="tio-settings nav-icon"></span>
                            <span class="text-truncate">Account Settings</span>
                        </a>
                    </li>
                @endif
            @endif
        </ul>
    </li>
@endif

@include('layouts.vendor.partials._sidebar_menu_default', [
    'store_data' => $store_data,
    'skipForLaundry' => true,
])
