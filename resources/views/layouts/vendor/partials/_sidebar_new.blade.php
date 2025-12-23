<style>
    .show_on_hover {
        position: absolute;
        top: 0px;
        left: 60px;
        background: #005555;
        border-radius: 10px;
        background-color: #005555;
    }

    .navbar-vertical-aside-has-menu {
        margin: 5px 0px;
    }

    .nav-link-icon {
        width: 27px;
        margin-right: 3px;
    }
</style>
<div id="sidebarMain" class="d-none">
    <aside
        class="js-navbar-vertical-aside navbar navbar-vertical-aside navbar-vertical navbar-vertical-fixed navbar-expand-xl navbar-bordered">
        <div class="navbar-vertical-container">
            <div class="navbar-brand-wrapper justify-content-between">
                <!-- Logo -->

                @php($store_data = \App\CentralLogics\Helpers::get_store_data())
                <a class="navbar-brand" href="{{ route('vendor.dashboard') }}" aria-label="Front">
                    <img class="navbar-brand-logo initial--36  onerror-image"
                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_data->logo, asset('storage/app/public/store/') . '/' . $store_data->logo, asset('public/assets/admin/img/160x160/img2.jpg'), 'store/') }}"
                        alt="Logo">
                    <img class="navbar-brand-logo-mini initial--36 onerror-image"
                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_data->logo, asset('storage/app/public/store/') . '/' . $store_data->logo, asset('public/assets/admin/img/160x160/img2.jpg'), 'store/') }}"
                        alt="Logo">
                </a>
                <!-- End Logo -->

                <!-- Navbar Vertical Toggle -->
                <button type="button"
                    class="js-navbar-vertical-aside-toggle-invoker navbar-vertical-aside-toggle btn btn-icon btn-xs btn-ghost-dark">
                    <i class="tio-clear tio-lg"></i>
                </button>
                <!-- End Navbar Vertical Toggle -->

                <div class="navbar-nav-wrap-content-left">
                    <!-- Navbar Vertical Toggle -->
                    <button type="button" class="js-navbar-vertical-aside-toggle-invoker close">
                        <i class="tio-first-page navbar-vertical-aside-toggle-short-align" data-toggle="tooltip"
                            data-placement="right" title="Collapse"></i>
                        <i class="tio-last-page navbar-vertical-aside-toggle-full-align"
                            data-template='<div class="tooltip d-none d-sm-block" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>'></i>
                    </button>
                    <!-- End Navbar Vertical Toggle -->
                </div>

            </div>

            <!-- Content -->
            {{-- bg--005555 --}}
            <div class="navbar-vertical-content text-capitalize bg-white" id="navbar-vertical-content">
                <form class="sidebar--search-form">
                    <div class="search--form-group">
                        <button type="button" class="btn"><i class="tio-search"></i></button>
                        <input type="text" class="form-control form--control"
                            placeholder="{{ translate('messages.Search Menu...') }}" id="search-sidebar-menu">
                    </div>
                </form>
                <ul class="navbar-nav navbar-nav-lg nav-tabs">
                    <!-- Dashboards -->
                    @if (selected_menu('dashboard'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.dashboard') }}" title="{{ translate('messages.dashboard') }}">
                                <img src="{{ asset('storage/app/public/nav/menu.png') }}" alt=""
                                    class="nav-link-icon">
                                {{-- <img src="{{ asset('storage/app/public/util/new_icons/Dashboard.png') }}" alt=""
                                class="nav-link-icon"> --}}

                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.dashboard') }}
                                </span>
                            </a>
                        </li>
                    @endif

                    @if (!auth('vendor')->check() && \App\CentralLogics\Helpers::employee_module_permission_check('assigned_leads'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/service/assigned-services*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.service.assigned_services') }}" title="Assigned Leads">
                                <img src="{{ asset('storage/app/public/nav/assignment.png') }}" alt=""
                                    class="nav-link-icon">
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Assigned Leads
                                </span>
                            </a>
                        </li>
                    @endif
                    @if (!auth('vendor')->check())
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/service/assigned-tasks*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.task.assigned_tasks') }}" title="Assigned Tasks">
                                <img src="{{ asset('storage/app/public/nav/task (1).png') }}" alt=""
                                    class="nav-link-icon">
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Assigned Tasks
                                </span>
                            </a>
                        </li>
                    @endif
                    @if (!auth('vendor')->check() && \App\CentralLogics\Helpers::employee_module_permission_check('assigned_projects'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/service/assigned-projects*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.service.assigned_projects') }}" title="Assigned projects">
                                <img src="{{ asset('storage/app/public/nav/project.png') }}" alt=""
                                    class="nav-link-icon">
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Assigned Projects
                                </span>
                            </a>
                        </li>
                    @endif
                    @if (selected_menu('leads_manage') && auth('vendor')->check() && $store_data->module->id == 6)
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/service/report') || Request::is('store-panel/lead*') || Request::is('store-panel/service/leads*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="Leads Management">
                                <img src="{{ asset('storage/app/public/nav/lead-generation.png') }}" alt=""
                                    class="nav-link-icon">
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Leads Management</span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('store-panel/service/report') || Request::is('store-panel/lead*') || Request::is('store-panel/service/leads*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('store-panel/lead/add') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.lead.add') }}"
                                        title="{{ translate('messages.add') }} {{ translate('messages.new') }} Lead">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.add') }}
                                            {{ translate('messages.new') }} Lead</span>
                                    </a>
                                </li>
                                <li class="nav-item  {{ Request::is('store-panel/service/leads*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.service.leads_list') }}" title=" Leads">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            Leads
                                        </span>
                                    </a>
                                </li>

                                <li class="nav-item {{ Request::is('store-panel/service/report') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.service.report') }}" title="report">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            Leads Report
                                        </span>
                                    </a>
                                </li>

                            </ul>
                        </li>
                    @endif

                    {{-- =============================== TASK Management=========================== --}}
                    @if (selected_menu('task_management') && hasAnyPermission(['task.list', 'task.export', 'task.add', 'task.settings']))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/task*')  && !Request::is('store-panel/task-salary-categories')  ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="Task Management">
                                <img src="{{ asset('storage/app/public/nav/task (1).png') }}" alt=""
                                    class="nav-link-icon">

                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Task
                                    Management</span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('store-panel/task*') && !Request::is('store-panel/task-salary-categories') ? 'block' : 'none' }}">

                                {{-- <li class="nav-item {{ Request::is('store-panel/task/add') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.task.add') }}"
                                            title="{{ translate('messages.add') }} {{ translate('messages.new') }} task">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('messages.add') }}
                                                {{ translate('messages.new') }} Task</span>
                                        </a>
                                    </li> --}}
                                @if (hasAnyPermission(['task.list', 'task.export', 'task.add']))
                                    <li class="nav-item {{ Request::is('store-panel/task/list') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.task.list') }}"
                                            title="list Project">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Tasks</span>
                                        </a>
                                    </li>
                                @endif
                                @if (hasPermission('task', 'settings'))
                                    <li class="nav-item {{ Request::is('store-panel/task/setting') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.task.setting') }}"
                                            title="Task Settings">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Task Settings</span>
                                        </a>
                                    </li>
                                @endif
                                {{-- <li class="nav-item {{ Request::is('store-panel/task/setting/workflow-form') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.task.setting.workflow-form') }}"
                                            title="Task Settings">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Create Workflow Form</span>
                                        </a>
                                    </li> --}}
                            </ul>
                        </li>
                    @endif
                    {{-- =============================== PROJECT Management=========================== --}}
                    @if (selected_menu('project_manage') && \App\CentralLogics\Helpers::employee_module_permission_check('project_manage'))
                        @if (\App\CentralLogics\Helpers::permission_check('projects_manage'))
                            <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/project*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                    href="javascript:;" title="Project Management">
                                    <i class="tio-money nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Project
                                        Management</span>
                                </a>

                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: {{ Request::is('store-panel/project*') ? 'block' : 'none' }}">
                                    <li class="nav-item {{ Request::is('store-panel/project/dashboard') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.project.dashboard') }}"
                                            title=" Project dashboard">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Projects Dashboard</span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('store-panel/project/add') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.project.add') }}"
                                            title="{{ translate('messages.add') }} {{ translate('messages.new') }} Project">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('messages.add') }}
                                                {{ translate('messages.new') }} Project</span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('store-panel/project/list') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.project.all') }}"
                                            title="list Project">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Projects List</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                          
                        @endif
                    @endif
                    {{-- =============================== ORDER Management=========================== --}}
                    @if (selected_menu('order_manage') && \App\CentralLogics\Helpers::employee_module_permission_check('order_manage'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/order*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="Orders">
                                <i class="tio-money nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Orders
                                </span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('store-panel/order/add') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('store-panel/order/dashboard') ? 'active' : '' }}">
                                    <a class="nav-link " href="#" title=" Project dashboard">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Order Dashboard</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('store-panel/order/add') ? 'active' : '' }}">
                                    <a class="nav-link " href="#"
                                        title="{{ translate('messages.add') }} {{ translate('messages.new') }} Project">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.add') }}
                                            {{ translate('messages.new') }} Order</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('store-panel/order/list') ? 'active' : '' }}">
                                    <a class="nav-link " href="#" title="list order">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Order List</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        {{-- ============= SHOP ORDERS ============================== --}}
                        @if (
                            \App\CentralLogics\Helpers::employee_module_permission_check('order') &&
                                $store_data->module->module_type == 'ecommerce')
                            <li class="nav-item">
                                <small class="nav-subtitle"
                                    title="{{ translate('messages.order_section') }}">{{ translate('messages.order_section') }}</small>
                                <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                            </li>

                            <!-- Order -->
                            <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/order*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                    href="javascript:" title="{{ translate('messages.orders') }}">
                                    <i class="tio-shopping-cart nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        {{ translate('messages.orders') }}
                                    </span>
                                </a>
                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: {{ Request::is('store-panel/order*') ? 'block' : 'none' }}">
                                    <li class="nav-item {{ Request::is('store-panel/order/list/all') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.order.list', ['all']) }}"
                                            title="{{ translate('messages.all_orders') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                {{ translate('messages.all') }}
                                                <span class="badge badge-soft-info badge-pill ml-1">
                                                    {{ \App\Models\Order::where('store_id', \App\CentralLogics\Helpers::get_store_id())->where(function ($query) {
                                                            return $query->whereNotIn(
                                                                    'order_status',
                                                                    config('order_confirmation_model') == 'store' ||
                                                                    \App\CentralLogics\Helpers::get_store_data()->self_delivery_system
                                                                        ? ['failed', 'canceled', 'refund_requested', 'refunded']
                                                                        : ['pending', 'failed', 'canceled', 'refund_requested', 'refunded'],
                                                                )->orWhere(function ($query) {
                                                                    return $query->where('order_status', 'pending')->where('order_type', 'take_away');
                                                                });
                                                        })->StoreOrder()->NotDigitalOrder()->count() }}
                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('store-panel/order/list/pending') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.order.list', ['pending']) }}"
                                            title="{{ translate('messages.pending_orders') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                {{ translate('messages.pending') }}
                                                {{ config('order_confirmation_model') == 'store' || \App\CentralLogics\Helpers::get_store_data()->self_delivery_system ? '' : translate('messages.take_away') }}
                                                <span class="badge badge-soft-success badge-pill ml-1">
                                                    @if (config('order_confirmation_model') == 'store' || \App\CentralLogics\Helpers::get_store_data()->self_delivery_system)
                                                        {{ \App\Models\Order::where(['order_status' => 'pending', 'store_id' => \App\CentralLogics\Helpers::get_store_id()])->StoreOrder()->OrderScheduledIn(30)->NotDigitalOrder()->count() }}
                                                    @else
                                                        {{ \App\Models\Order::where(['order_status' => 'pending', 'store_id' => \App\CentralLogics\Helpers::get_store_id(), 'order_type' => 'take_away'])->StoreOrder()->OrderScheduledIn(30)->NotDigitalOrder()->count() }}
                                                    @endif
                                                </span>
                                            </span>
                                        </a>
                                    </li>

                                    <li class="nav-item {{ Request::is('store-panel/order/list/confirmed') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.order.list', ['confirmed']) }}"
                                            title="{{ translate('messages.confirmed_orders') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                {{ translate('messages.confirmed') }}
                                                <span class="badge badge-soft-success badge-pill ml-1">
                                                    {{ \App\Models\Order::whereIn('order_status', ['confirmed', 'accepted'])->StoreOrder()->whereNotNull('confirmed')->where('store_id', \App\CentralLogics\Helpers::get_store_id())->OrderScheduledIn(30)->NotDigitalOrder()->count() }}
                                                </span>
                                            </span>
                                        </a>
                                    </li>

                                    <li class="nav-item {{ Request::is('store-panel/order/list/cooking') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.order.list', ['cooking']) }}"
                                            title="{{ translate('messages.processing_orders') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                @if ($store_data->module->module_type == 'food')
                                                    {{ translate('messages.cooking') }}
                                                @else
                                                    {{ translate('messages.processing') }}
                                                @endif
                                                <span class="badge badge-soft-info badge-pill ml-1">
                                                    {{ \App\Models\Order::where(['order_status' => 'processing', 'store_id' => \App\CentralLogics\Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count() }}
                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <li
                                        class="nav-item {{ Request::is('store-panel/order/list/ready_for_delivery') ? 'active' : '' }}">
                                        <a class="nav-link"
                                            href="{{ route('vendor.order.list', ['ready_for_delivery']) }}"
                                            title="{{ translate('messages.ready_for_delivery') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                {{ translate('messages.ready_for_delivery') }}
                                                <span class="badge badge-soft-info badge-pill ml-1">
                                                    {{ \App\Models\Order::where(['order_status' => 'handover', 'store_id' => \App\CentralLogics\Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count() }}
                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <li
                                        class="nav-item {{ Request::is('store-panel/order/list/item_on_the_way') ? 'active' : '' }}">
                                        <a class="nav-link"
                                            href="{{ route('vendor.order.list', ['item_on_the_way']) }}"
                                            title="{{ translate('messages.items_on_the_way') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                {{ translate('messages.item_on_the_way') }}
                                                <span class="badge badge-soft-info badge-pill ml-1">
                                                    {{ \App\Models\Order::where(['order_status' => 'picked_up', 'store_id' => \App\CentralLogics\Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count() }}
                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('store-panel/order/list/delivered') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.order.list', ['delivered']) }}"
                                            title="{{ translate('messages.delivered_orders') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                {{ translate('messages.delivered') }}
                                                <span class="badge badge-soft-success badge-pill ml-1">
                                                    {{ \App\Models\Order::where(['order_status' => 'delivered', 'store_id' => \App\CentralLogics\Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count() }}
                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('store-panel/order/list/refunded') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.order.list', ['refunded']) }}"
                                            title="{{ translate('messages.refunded_orders') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                {{ translate('messages.refunded') }}
                                                <span class="badge badge-soft-danger bg-light badge-pill ml-1">
                                                    {{ \App\Models\Order::Refunded()->where(['store_id' => \App\CentralLogics\Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count() }}
                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('store-panel/order/list/scheduled') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.order.list', ['scheduled']) }}"
                                            title="{{ translate('messages.scheduled_orders') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                {{ translate('messages.scheduled') }}
                                                <span class="badge badge-soft-info badge-pill ml-1">
                                                    {{ \App\Models\Order::where('store_id', \App\CentralLogics\Helpers::get_store_id())->StoreOrder()->Scheduled()->where(function ($q) {
                                                            if (
                                                                config('order_confirmation_model') == 'store' ||
                                                                \App\CentralLogics\Helpers::get_store_data()->self_delivery_system
                                                            ) {
                                                                $q->whereNotIn('order_status', ['failed', 'canceled', 'refund_requested', 'refunded']);
                                                            } else {
                                                                $q->whereNotIn('order_status', ['pending', 'failed', 'canceled', 'refund_requested', 'refunded'])->orWhere(
                                                                    function ($query) {
                                                                        $query->where('order_status', 'pending')->where('order_type', 'take_away');
                                                                    },
                                                                );
                                                            }
                                                        })->count() }}
                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!-- End Order -->
                        @endif
                        {{-- ============= SHOP ORDERS END ============================== --}}

                    @endif
                    {{-- =============================== CLIENT Management=========================== --}}
                    @if (selected_menu('client_manage') && \App\CentralLogics\Helpers::employee_module_permission_check('client_manage'))
                        @if (\App\CentralLogics\Helpers::employee_module_permission_check('client_manage'))
                            <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/client*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                    href="javascript:;" title="Client Management">
                                    <img src="{{ asset('storage/app/public/nav/client (1).png') }}" alt=""
                                        class="nav-link-icon">
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Client
                                        Management
                                    </span>
                                </a>

                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: {{ Request::is('store-panel/client*') ? 'block' : 'none' }}">

                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/customer/add') ? 'active' : '' }}">
                                        <a class="nav-link" href="#" title="Add New Client">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class=" text-truncate">Add New Client</span>
                                        </a>
                                    </li>

                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/client/list') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.customer.list') }}"
                                            title="{{ translate('messages.clients') }} Management">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class=" text-truncate">{{ translate('messages.clients') }}
                                                List</span>
                                        </a>
                                    </li>
                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/customer/overview') ? 'active' : '' }}">
                                        <a class="nav-link" href="#"
                                            title="{{ translate('messages.clients_overview') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class=" text-truncate">{{ translate('messages.clients_overview') }}
                                            </span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    @endif
                    {{-- ===============================CUSTOMER SUPPORT=========================== --}}
                    @if (selected_menu('customer_support') &&
                            \App\CentralLogics\Helpers::employee_module_permission_check('customer_support'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/order*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="Customer Support">
                                <img src="{{ asset('storage/app/public/nav/client (1).png') }}" alt=""
                                    class="nav-link-icon">
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Customer
                                    Support
                                </span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('store-panel/order/add') ? 'block' : 'none' }}">

                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/customer/support') ? 'active' : '' }}">
                                    <a class="nav-link" href="#" title="Calls">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class=" text-truncate">Calls</span>
                                    </a>
                                </li>

                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/customer/support') ? 'active' : '' }}">
                                    <a class="nav-link" href="#"
                                        title="{{ translate('messages.Feedbacks') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class=" text-truncate">Feedbacks</span>
                                    </a>
                                </li>
                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/customer/support') ? 'active' : '' }}">
                                    <a class="nav-link" href="#"
                                        title="{{ translate('messages.Call Marketing') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class=" text-truncate">Call Marketing
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                    {{-- ===================================== BILLING ========================== --}}
                    @if (selected_menu('billing') && \App\CentralLogics\Helpers::employee_module_permission_check('billing'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/business-settings/manual-bill') || Request::is('store-panel/invoice-list') || Request::is('store-panel/invoices') || Request::is('store-panel/billing*') || Request::is('store-panel/business-settings/generate-bill') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="Billing">
                                <img src="{{ asset('storage/app/public/nav/bill (1).png') }}" alt=""
                                    class="nav-link-icon">
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Billing</span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">

                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/business-settings/manual-bill') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.business-settings.manual-bill') }}"
                                        title="{{ translate('messages.Generate Bill') }}">
                                        <span class="tio-document-text nav-icon"></span>
                                        <span class="text-truncate">Generate Bill</span>
                                    </a>
                                </li>
                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/billing/create-invoice') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.invoice.create-invoice') }}"
                                        title="{{ translate('messages.Generate Advanced Invoice') }}">
                                        <span class="tio-document-text nav-icon"></span>
                                        <span class="text-truncate">Generate Advanced Invoice</span>
                                    </a>
                                </li>

                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/billing/credit') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.invoice.list') }}"
                                        title="{{ translate('messages.Bill') }}">
                                        <span class="tio-coin nav-icon"></span>
                                        <span class="text-truncate">Bills</span>
                                    </a>
                                </li>

                                @if ($store_data->module->id == 5)
                                    <li class="nav-item {{ Request::is('store-panel/invoice-list') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.order.invoices') }}"
                                            title="invoices">
                                            <span class="tio-document-text nav-icon"></span>
                                            <span class="text-truncate">Invoices</span>
                                        </a>
                                    </li>
                                @endif
                                <li class="nav-item  {{ Request::is('store-panel/billing/purchase-bills') ? 'active' : '' }}"
                                    style="margin-top:0 !important;">
                                    <a class="nav-link " href="{{ route('vendor.invoice.my-bills') }}"
                                        title="Purchase Bills">
                                        <span class="tio-money-vs nav-icon"></span>
                                        <span class="text-truncate">Purchase Bills</span>
                                    </a>
                                </li>
                                <li class="nav-item  {{ Request::is('store-panel/billing/settings') ? 'active' : '' }}"
                                    style="margin-top:0 !important;">
                                    <a class="nav-link " href="{{ route('vendor.invoice.settings') }}"
                                        title="Billing Settings">
                                        <span class="tio-money-vs nav-icon"></span>
                                        <span class="text-truncate">Billing Settings</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @if (_offeredModule('reciepts'))
                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/business-settings/manual-bill') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                    href="javascript:;" title="Leads">
                                    <i class="tio-receipt nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        Reciepts</span>
                                </a>

                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                    <li class="nav-item  {{ Request::is('store-panel/receipt/templates') ? 'active' : '' }}"
                                        style="margin-top:0 !important;">
                                        <a class="nav-link " href="" title="Templates">
                                            <span class="tio-money-vs nav-icon"></span>
                                            <span class="text-truncate">Templates</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    @endif
                    {{-- =============================== QUOTATION Management=========================== --}}
                    @if (selected_menu('quotation_manage') &&
                            \App\CentralLogics\Helpers::employee_module_permission_check('quotation_manage'))
                        @if (\App\CentralLogics\Helpers::permission_check('quotaiton_manage'))
                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/quotation*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                    href="javascript:;" title="Quotation Management">
                                    <i class="tio-money nav-icon"></i>
                                    <span
                                        class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Quotation
                                        Management</span>
                                </a>

                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: {{ Request::is('store-panel/quotation*') ? 'block' : 'none' }}">
                                    <li class="nav-item {{ Request::is('store-panel/quotation/add') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.quotation.add') }}"
                                            title="{{ translate('messages.add') }} {{ translate('messages.new') }} Quotation">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('messages.add') }}
                                                {{ translate('messages.new') }} Quotation</span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('store-panel/quotation/list') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.quotation.list') }}"
                                            title="Quotation {{ translate('messages.list') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Quotations List</span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('store-panel/quotation/settings') ? 'active' : '' }}"
                                        style="margin-top:0 !important;">
                                        <a class="nav-link " href="{{ route('vendor.quotation.settings') }}"
                                            title="Quotation Settings">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Quotation Settings</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    @endif
                    {{-- =============================== POS Management=========================== --}}
                    @if (selected_menu('pos') &&
                            \App\CentralLogics\Helpers::employee_module_permission_check('pos') &&
                            $store_data->module->module_type == 'ecommerce')
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/pos') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link  "
                                href="{{ route('vendor.pos.index') }}" title="{{ translate('messages.pos') }}">
                                <i class="tio-shopping-basket-outlined nav-icon"></i>
                                <span class="text-truncate">{{ translate('messages.pos') }}</span>
                            </a>
                        </li>
                    @endif
                    @if (selected_menu('pos') &&
                            $store_data->module->id == 6 &&
                            \App\CentralLogics\Helpers::get_store_data()->pos_system &&
                            \App\CentralLogics\Helpers::employee_module_permission_check('pos'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/pos*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="POS">
                                <img src="{{ asset('storage/app/public/nav/pos.png') }}" alt=""
                                    class="nav-link-icon">

                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">POS</span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                @if (hasPermission('pos', 'dashboard'))
                                    <li class="nav-item {{ Request::is('store-panel/pos/dashboard') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.pos.dashboard') }}"
                                            title="POS Dashboard">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Dashboard</span>
                                        </a>
                                    </li>
                                @endif
                                @if (hasPermission('pos_token', 'generate'))
                                    <li class="nav-item {{ Request::is('store-panel/pos/token') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.pos.token') }}"
                                            title="POS Token">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Token Generate</span>
                                        </a>
                                    </li>
                                @endif
                                @if (hasPermission('pos_token', 'list'))
                                    <li class="nav-item {{ Request::is('store-panel/pos/token-list') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.pos.token.list') }}"
                                            title="Tokens List">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Tokens List</span>
                                        </a>
                                    </li>
                                @endif
                                @if (hasPermission('pos_items', 'list') || hasPermission('pos_items', 'add'))
                                    <li class="nav-item {{ Request::is('store-panel/pos/items') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.pos.items') }}"
                                            title="POS Items">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">POS Items</span>
                                        </a>
                                    </li>
                                @endif
                                @if (hasPermission('pos_branch', 'list') || hasPermission('pos_branch', 'add'))
                                    <li class="nav-item {{ Request::is('store-panel/pos/branch') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.pos.branch.index') }}"
                                            title="Branches">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Branches</span>
                                        </a>
                                    </li>
                                @endif
                                @if (hasPermission('pos', 'settings'))
                                    <li class="nav-item {{ Request::is('store-panel/pos/settings') ? 'active' : '' }}"
                                        style="margin-top:0 !important;">
                                        <a class="nav-link " href="{{ route('vendor.pos.settings') }}"
                                            title="POS Settings">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Settings</span>
                                        </a>
                                    </li>
                                @endif
                                @if (hasPermission('pos', 'report'))
                                    <li class="nav-item {{ Request::is('store-panel/pos/report') ? 'active' : '' }}"
                                        style="margin-top:0 !important;">
                                        <a class="nav-link " href="{{ route('vendor.pos.report') }}"
                                            title="POS Report">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Report</span>
                                        </a>
                                    </li>
                                @endif

                            </ul>
                        </li>
                    @endif
                    {{-- =============================== ACCOUNT Management=========================== --}}
                    @if (selected_menu('account_manage') && \App\CentralLogics\Helpers::employee_module_permission_check('account_manage'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/account*') || Request::is('store-panel/asset*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title=" Account Management">
                                <img src="{{ asset('storage/app/public/nav/budget.png') }}" alt=""
                                    class="nav-link-icon">

                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Account Management</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                @if (_storeAccountType() == 'ledger')
                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/account/dashboard') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.account.dashboard') }}"
                                            title="{{ translate('messages.dashboard') }}">
                                            <span class="tio-dashboard nav-icon"></span>
                                            <span class="text-truncate">Dashboard</span>
                                        </a>
                                    </li>
                                @endif
                                @if (\App\CentralLogics\Helpers::permission_check('account_manage'))
                                    @if (_storeAccountType() == 'ledger')
                                        <li
                                            class="navbar-vertical-aside {{ Request::is('store-panel/account/approvals') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.account.approvals') }}"
                                                title="{{ translate('messages.Approvals') }}">
                                                <span class="tio-dashboard nav-icon"></span>
                                                <span class="text-truncate">Approvals</span>
                                            </a>
                                        </li>

                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/account/request-form*') ? 'active' : '' }}">
                                            <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:;" title="Approval Forms">
                                                <span class="tio-notebook-bookmarked nav-icon"></span>
                                                <span class="text-truncate ">
                                                    Approval Forms</span>
                                            </a>

                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/request-form/journal-entry') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.account.request-form.journal-entry.index') }}"
                                                        title="{{ translate('messages.Request Form') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Journal Entry Request Form</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/request-form/master-ledger') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.account.request-form.master-ledger.index') }}"
                                                        title="{{ translate('messages.Request Form') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Master Leger Request Form</span>
                                                    </a>
                                                </li>
                                                @if (auth('vendor_employee')->check())
                                                    <li
                                                        class="navbar-vertical-aside {{ Request::is('store-panel/account/ds') ? 'active' : '' }}">
                                                        <a class="nav-link "
                                                            href="{{ route('vendor.account.request-form.incoming-requests') }}"
                                                            title="{{ translate('messages.Request Form') }}">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate">Incoming Requests</span>
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </li>
                                    @endif
   
                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/account/management')
                                        || Request::is('store-panel/account/journal-entry')
                                        ||  Request::is('store-panel/account/day-book')
                                        ||  Request::is('store-panel/account/petty-cashbook')
                                        ||  Request::is('store-panel/account/maintenance')
                                         ? 'active' : '' }}">
                                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                            href="javascript:;" title="Books of Accounts">
                                            <span class="tio-notebook-bookmarked nav-icon"></span>
                                            <span class="text-truncate ">
                                                Books of Accounts</span>
                                        </a>


                                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub">

                                            <li
                                                class="navbar-vertical-aside {{ Request::is('store-panel/account/management') ? 'active' : '' }}">
                                                <a class="nav-link " href="{{ route('vendor.account.add') }}"
                                                    title="{{ translate('messages.Master Ledger Book') }}">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate">Master Ledger Book</span>
                                                </a>
                                            </li>
                                            @if (_storeAccountType() == 'ledger')
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/journal-entry') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.account.journal-entry.index') }}"
                                                        title="{{ translate('messages.Journal Entry Book') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Journal Entry Book</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/day-book') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.account.day-book.index') }}"
                                                        title="{{ translate('messages.Day Book') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Day Book</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/petty-cashbook') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.account.petty-cashbook.index') }}"
                                                        title="{{ translate('messages.Petty CashBook') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Petty CashBook</span>
                                                    </a>
                                                </li>

                                                <li
                                                    class="navbar-vertical-aside  {{ Request::is('store-panel/account/maintenance') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.account.maintenance.index') }}"
                                                        title="{{ translate('messages.monthly_maintenance') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Monthly Maintenance</span>
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                                    @if (_storeAccountType() == 'ledger')
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/account/banking*') ? 'active' : '' }}">
                                            <a class=" sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:;" title="Banking">
                                                <span class="tio-credit-cards nav-icon"></span>
                                                <span class=" text-truncate">
                                                    Banking</span>
                                            </a>

                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/banking/bank-account') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.account.banking.bank-account.index') }}"
                                                        title="{{ translate('messages.bank_accounts') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Bank Accounts</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/banking/cash-book') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.account.banking.cash-book.index') }}"
                                                        title="{{ translate('messages.Cash Book') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Cash Book</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/banking/bank-reconciliation') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.account.banking.bank-reconciliation.index') }}"
                                                        title="{{ translate('messages.bank_reconciliation') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Bank Reconciliation</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </li>
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/account/statement*') ? 'active' : '' }}">
                                            <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:;" title="Statements">
                                                <span class="tio-file-text-outlined nav-icon"></span>
                                                <span class="text-truncate">
                                                    Statements</span>
                                            </a>

                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/statement/trial-balance') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.account.statement.trial-balance') }}"
                                                        title="{{ translate('messages.trial_balance') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Trial Balance</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/statement/balance-sheet') ? 'active' : '' }}">
                                                    <a class="nav-link " href="javascript:;"
                                                        title="{{ translate('messages.balance_sheet') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Balance Sheet</span>
                                                    </a>
                                                </li>

                                            </ul>
                                        </li>
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/account/monthly-finance*') ? 'active' : '' }}">
                                            <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:;" title="Reports">
                                                <span class="tio-chart-bar-2 nav-icon"></span>
                                                <span class="text-truncate">
                                                    Recurring Monthly Finance</span>
                                            </a>

                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/monthly-finance/monthly-maintanance') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.account.monthly-finance.monthly-maintanance') }}"
                                                        title="Maintenance Requests">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Maintenance Requests</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/tax-report') ? 'active' : '' }}">
                                                    <a class="nav-link " href="javascript:;"
                                                        title="{{ translate('messages.Bill Payments') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Bill Payments
                                                        </span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/monthly-finance/property-valuation') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.account.monthly-finance.property-valuation') }}"
                                                        title="{{ translate('messages. Property Valuation ') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Property Valuation</span>
                                                    </a>
                                                </li>

                                            </ul>
                                        </li>

                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/asset*') ? 'active' : '' }}">
                                            <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:;" title="Assets">
                                                <span class="tio-chart-bar-2 nav-icon"></span>
                                                <span class="text-truncate">
                                                    Assets</span>
                                            </a>

                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                                @if (auth('vendor')->check())
                                                    <li
                                                        class="navbar-vertical-aside {{ Request::is('store-panel/asset') ? 'active' : '' }}">
                                                        <a class=" nav-link"
                                                            href="{{ route('vendor.asset.index') }}" title="Assets">
                                                            <span class="tio-circle nav-indicator-icon"></span>

                                                            <span class=" text-truncate">Company Assets
                                                                (properties)</span>
                                                        </a>
                                                    </li>
                                                @else
                                                    <li
                                                        class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/asset/alotted') ? 'active' : '' }}">
                                                        <a class=" nav-link"
                                                            href="{{ route('vendor.asset.alotted') }}"
                                                            title="Assets">
                                                            <span class="tio-circle nav-indicator-icon"></span>

                                                            <span class=" text-truncate">Company Assets
                                                                (properties)</span>
                                                        </a>
                                                    </li>
                                                @endif
                                                <li
                                                    class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/account/settings') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.account.setting.chart-of-account.index') }}"
                                                        title="{{ translate('messages.Chart of Accounts') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-account-square">Chart of Accounts</span>
                                                    </a>
                                                </li>


                                            </ul>
                                        </li>

                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/account/report*') ? 'active' : '' }}">
                                            <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:;" title="Reports">
                                                <span class="tio-chart-bar-2 nav-icon"></span>
                                                <span class="text-truncate">
                                                    Reports</span>
                                            </a>

                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/report') ? 'active' : '' }}">
                                                    <a class="nav-link " href="{{ route('vendor.account.report') }}"
                                                        title="{{ translate('messages.report') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Accounts Report</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/report/tax') ? 'active' : '' }}">
                                                    <a class="nav-link " 
                                                        href="{{ route('vendor.account.report.tax') }}"
                                                        title="{{ translate('messages. Tax Reports (GST/VAT) ') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Tax Reports (GST/VAT)</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/report/audit-logs') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.account.report.audit-logs') }}"
                                                        title="{{ translate('messages. Audit Logs ') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Audit Logs</span>
                                                    </a>
                                                </li>

                                            </ul>
                                        </li>
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/account/taxation*') ? 'active' : '' }}">
                                            <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:;" title="Taxation">
                                                <span class="tio-dollar-outlined nav-icon"></span>
                                                <span class="text-truncate">
                                                    Taxation</span>
                                            </a>

                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">

                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/taxation/gst') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.account.taxation.gst') }}"
                                                        title="{{ translate('messages.GST') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">GST</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/tax') ? 'active' : '' }}">
                                                    <a class="nav-link " href="javascript:;"
                                                        title="{{ translate('messages. TDS') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">TDS</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/tax') ? 'active' : '' }}">
                                                    <a class="nav-link " href="javascript:;"
                                                        title="{{ translate('messages. TCS ') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">TCS</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="navbar-vertical-aside {{ Request::is('store-panel/account/tax') ? 'active' : '' }}">
                                                    <a class="nav-link " href="javascript:;"
                                                        title="{{ translate('messages. Other Taxes ') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Other Taxes</span>
                                                    </a>
                                                </li>

                                            </ul>
                                        </li>
                                    @endif
                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/account/setting*') ? 'active' : '' }}">
                                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                            href="javascript:;" title="Settings">
                                            <span class="tio-settings nav-icon"></span>
                                            <span class=" text-truncate">
                                                Settings</span>
                                        </a>

                                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub">


                                            <li
                                                class="navbar-vertical-aside {{ Request::is('store-panel/account/setting') ? 'active' : '' }}">
                                                <a class="nav-link " href="{{ route('vendor.account.setting') }}"
                                                    title="{{ translate('messages. Account Type ') }}">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate">Account Type</span>
                                                </a>
                                            </li>
                                            <li
                                                class="navbar-vertical-aside {{ Request::is('store-panel/account/setting/common-settings') ? 'active' : '' }}">
                                                <a class="nav-link "
                                                    href="{{ route('vendor.account.setting.common-settings') }}"
                                                    title="{{ translate('messages.Account Settings') }}">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate">Common Settings</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </li>

                                @endif
                            </ul>
                        </li>
                    @endif
                    {{-- =============================== iNVENTORY Management=========================== --}}
                    @if (selected_menu('inventory_manage') &&
                            \App\CentralLogics\Helpers::employee_module_permission_check('inventory_manage'))

                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/inventory*') || Request::is('store-panel/item/entry') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title=" Inventory Management">
                                <img src="{{ asset('storage/app/public/nav/inventory-management.png') }}"
                                    alt="" class="nav-link-icon">

                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Inventory Management</span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                @if (hasPermission('inventory', 'dashboard'))
                                    <li
                                        class="navbar-vertical-aside {{ Request::is('store-panel/inventory/dashboard') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.inventory.dashboard') }}"
                                            title="{{ translate('messages.dashboard') }}">
                                            <span class="tio-dashboard nav-icon"></span>
                                            <span class="text-truncate">Inventory Dashboard</span>
                                        </a>
                                    </li>
                                @endif
                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/inventory*')  ? 'active' : '' }}">
                                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                        href="javascript:" title="Products / Services">
                                        <i class="tio-shopping-cart nav-icon"></i>
                                        <span class=" text-truncate">
                                            Products / Services
                                        </span>
                                    </a>
                                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                        style="display: {{ Request::is('store-panel/inventory') || Request::is('store-panel/inventory/storage-spaces') ? 'block' : 'none' }}">
                                        <li class="nav-item  {{ Request::is('store-panel/inventory') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.inventory.index') }}"
                                                title=" {{ translate('messages.item_adding_book') }}">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate sidebar--badge-container">
                                                    Item Adding Book
                                                </span>
                                            </a>
                                        </li>

                                        @if (hasAnyPermission(['inventory_storage_units.list', 'inventory_storage_units.add']))
                                            <li
                                                class="nav-item {{ Request::is('store-panel/inventory/storage-spaces') ? 'active' : '' }}">
                                                <a class="nav-link"
                                                    href="{{ route('vendor.inventory.storage-spaces') }}"
                                                    title="Storage Units">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate sidebar--badge-container">
                                                        Storage Units
                                                    </span>
                                                </a>
                                            </li>
                                        @endif
                                        <li
                                            class="nav-item {{ Request::is('store-panel/inventory/item-images') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.inventory.item-images') }}"
                                                title="Item Images">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate sidebar--badge-container">
                                                    Item Images
                                                </span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                @if (hasAnyPermission(['inventory_stock_in_out.list']))
                                    <li class="navbar-vertical-aside-has-menu  {{ Request::is('store-panel/inventory/stock*') ? 'active' : '' }}">
                                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                            href="javascript:" title="Stock Management">
                                            <i class="tio-folders-outlined nav-icon"></i>
                                            <span class=" text-truncate">
                                                Stock Management
                                            </span>
                                        </a>
                                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: ">
                                            @if (hasPermission('inventory_stock_in_out', 'list'))
                                                <li class="nav-item  {{ Request::is('store-panel/inventory/stock/stock-in-out') ? 'active' : '' }}">
                                                    <a class="nav-link"
                                                        href="{{ route('vendor.inventory.stock.stock-in-out') }}"
                                                        title="Stock in / Stock out">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate sidebar--badge-container">
                                                            Stock in / Stock out
                                                        </span>
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                                @endif
                                @if (hasAnyPermission([
                                        'inventory_sale_order.list',
                                        'inventory_sale_order.export',
                                        'inventory_sale_return.export',
                                        'inventory_sale_return.list',
                                    ]))
                                    <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/inventory/sale*') ? 'active' : '' }} ">
                                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                            href="javascript:" title="Sales">
                                            <i class="tio-chart-bar-1 nav-icon"></i>
                                            <span class=" text-truncate">
                                                Sales
                                            </span>
                                        </a>
                                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{ Request::is('store-panel/inventory/sale*') ? 'block' : 'none' }} ">
                                            @if (hasPermission('inventory_sale_order', 'list') || hasPermission('inventory_sale_order', 'export'))
                                                <li class="nav-item {{ Request::is('store-panel/inventory/sale/orders') ? 'active' : '' }}">
                                                    <a class="nav-link"
                                                        href="{{ route('vendor.inventory.sale.orders') }}"
                                                        title="Sale Orders">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate sidebar--badge-container">
                                                            Sale Orders
                                                        </span>
                                                    </a>
                                                </li>
                                            @endif

                                            @if (hasPermission('inventory_sale_return', 'list') || hasPermission('inventory_sale_return', 'export'))
                                                <li class="nav-item {{ Request::is('store-panel/inventory/sale/orders-return') ? 'active' : '' }}">
                                                    <a class="nav-link"
                                                        href="{{ route('vendor.inventory.sale.orders-return') }}"
                                                        title="Return Orders">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate sidebar--badge-container">
                                                            Return Orders
                                                        </span>
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                                @endif
                                @if (hasAnyPermission([
                                        'inventory_purchase_return.add',
                                        'inventory_purchase_return.list',
                                        'inventory_purchase_order.add',
                                        'inventory_purchase_order.list',
                                        'purchase_bill.list',
                                    ]))

                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/inventory/purchase*') ? 'active' : '' }}">
                                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                            href="javascript:" title="Purchase">
                                            <i class="tio-chart-bar-2 nav-icon"></i>
                                            <span class=" text-truncate">
                                                Purchase
                                            </span>
                                        </a>
                                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                            style="display: {{ Request::is('store-panel/inventory/purchase*') ? 'block' : 'none' }}">
                                            @if (hasPermission('inventory_purchase_order', 'add') || hasPermission('inventory_purchase_order', 'list'))
                                                <li
                                                    class="nav-item {{ Request::is('store-panel/inventory/purchase/orders') ? 'active' : '' }}">
                                                    <a class="nav-link"
                                                        href="{{ route('vendor.inventory.purchase.orders') }}"
                                                        title="Purchase Orders">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate sidebar--badge-container">
                                                            Purchase Orders
                                                        </span>
                                                    </a>
                                                </li>
                                            @endif
                                            @if (hasPermission('inventory_purchase_return', 'add') || hasPermission('inventory_purchase_return', 'list'))
                                                <li
                                                    class="nav-item {{ Request::is('store-panel/inventory/purchase/return') ? 'active' : '' }}">
                                                    <a class="nav-link"
                                                        href="{{ route('vendor.inventory.purchase.return') }}"
                                                        title="Return Purchase">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate sidebar--badge-container">
                                                            Return Purchase
                                                        </span>
                                                    </a>
                                                </li>
                                            @endif
                                            @if (hasPermission('purchase_bill', 'list'))
                                                <li class="nav-item {{ Request::is('store-panel/inventory/purchase/orders') ? 'active' : '' }}">
                                                    <a class="nav-link"
                                                        href="{{ route('vendor.invoice.my-bills') }}"
                                                        title="Purchase Bills">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate sidebar--badge-container">
                                                            Purchase Bills
                                                        </span>
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                                @endif
                                @if (hasAnyPermission([
                                        'stock_report.list',
                                        'stock_report.export',
                                        'sale_report.export',
                                        'sale_report.list',
                                        'profit_loss_summary.export',
                                        'profit_loss_summary.list',
                                        'purchase_report.export',
                                        'purchase_report.list',
                                    ]))
                                    <li class="navbar-vertical-aside-has-menu ">
                                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                            href="javascript:" title="Reports">
                                            <i class="tio-document-outlined nav-icon"></i>
                                            <span class=" text-truncate">
                                                Reports
                                            </span>
                                        </a>
                                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: ">
                                            @if (hasPermission('stock_report', 'list') || hasPermission('stock_report', 'export'))
                                                <li class="nav-item ">
                                                    <a class="nav-link"
                                                        href="{{ route('vendor.inventory.report.stock') }}"
                                                        title="Stock Report">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate sidebar--badge-container">
                                                            Stock Report
                                                        </span>
                                                    </a>
                                                </li>
                                            @endif
                                            @if (hasPermission('sale_report', 'list') || hasPermission('sale_report', 'export'))
                                                <li class="nav-item ">
                                                    <a class="nav-link"
                                                        href="{{ route('vendor.inventory.report.sale') }}"
                                                        title="Sales Report">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate sidebar--badge-container">
                                                            Sales Report
                                                        </span>
                                                    </a>
                                                </li>
                                            @endif
                                            @if (hasPermission('purchase_report', 'list') || hasPermission('purchase_report', 'export'))
                                                <li class="nav-item ">
                                                    <a class="nav-link"
                                                        href="{{ route('vendor.inventory.report.purchase') }}"
                                                        title="Purchase Report">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate sidebar--badge-container">
                                                            Purchase Report
                                                        </span>
                                                    </a>
                                                </li>
                                            @endif
                                            @if (hasPermission('profit_loss_summary', 'list') || hasPermission('profit_loss_summary', 'export'))
                                                <li class="nav-item ">
                                                    <a class="nav-link"
                                                        href="{{ route('vendor.inventory.report.profit-and-loss') }}"
                                                        title="Profit & Loss Summary">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate sidebar--badge-container">
                                                            Profit & Loss Summary
                                                        </span>
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                                @endif
                            </ul>
                        </li>
                        {{-- ===================================== inventory END========================== --}}
                    @endif
                    {{-- =============================== HR Management=========================== --}}
                    @if (
                        (selected_menu('staff_manage') ||
                            selected_menu('attendance_manage') ||
                            selected_menu('leave_manage') ||
                            selected_menu('salary_manage')) &&
                            (\App\CentralLogics\Helpers::employee_module_permission_check('staff_manage') ||
                                \App\CentralLogics\Helpers::employee_module_permission_check('att_manage') ||
                                \App\CentralLogics\Helpers::employee_module_permission_check('leave_manage') ||
                                \App\CentralLogics\Helpers::employee_module_permission_check('salary_manage')))
                        {{-- @if (\App\CentralLogics\Helpers::employee_module_permission_check('project_manage')) --}}
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/hr*') || Request::is('store-panel/task-salary-categories')   || Request::is('store-panel/shifts*')  ||Request::is('store-panel/custom-role*')  ||  Request::is('store-panel/staff*') || Request::is('store-panel/salary*') || Request::is('store-panel/leave*') || Request::is('store-panel/attendance*') ? 'active' : '' }} ">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="HR Management">
                                <img src="{{ asset('storage/app/public/nav/hr-manager (1).png') }}" alt=""
                                    class="nav-link-icon">

                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    HR Management</span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                <li class="nav-item {{ Request::is('store-panel/hr/dashboard') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.hr.dashboard') }}"
                                        title="HR Management Dashboard">
                                        <span class="tio-dashboard-outlined nav-icon"></span>
                                        <span class="text-truncate">Dashboard</span>
                                    </a>
                                </li>
                                @if (selected_menu('staff_manage') && \App\CentralLogics\Helpers::employee_module_permission_check('hr_manage'))
                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/staff*') || Request::is('store-panel/custom-role*')  ? 'active' : '' }}">
                                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                            href="javascript:;" title="Staff
                                                Management">
                                            <i class="tio-group-junior nav-icon"></i>
                                            <span class=" text-truncate">Staff
                                                Management</span>
                                        </a>

                                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                            style="display: {{ Request::is('store-panel/staff*') || Request::is('store-panel/custom-role*') ? 'block' : 'none' }}">

                                            <li class="nav-item {{ Request::is('store-panel/staff/add-new') ? 'active' : '' }}">
                                                <a class="nav-link " href="{{ route('vendor.staff.add-new') }}"
                                                    title="{{ translate('messages.add') }} {{ translate('messages.new') }} {{ translate('messages.Employee') }}">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate">{{ translate('messages.add') }}
                                                        {{ translate('messages.new') }} Staff</span>
                                                </a>
                                            </li>
                                            <li class="nav-item {{ Request::is('store-panel/staff/list') ? 'active' : '' }}">
                                                <a class="nav-link " href="{{ route('vendor.staff.list') }}"
                                                    title="Staff {{ translate('messages.list') }}">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate">Staff
                                                        {{ translate('messages.list') }} </span>
                                                </a>
                                            </li>
                                            <li class="nav-item {{ Request::is('store-panel/staff/team') ? 'active' : '' }}">
                                                <a class="nav-link " href="{{ route('vendor.staff.team.index') }}"
                                                    title="Teams">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate">Teams </span>
                                                </a>
                                            </li>

                                            <li
                                                class="nav-item {{ Request::is('store-panel/staff-department') ? 'active' : '' }}">
                                                <a class="nav-link "
                                                    href="{{ route('vendor.staff-department.all') }}"
                                                    title="Staff Department">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate">Staff Department</span>
                                                </a>
                                            </li>

                                            <li class="nav-item {{ Request::is('store-panel/custom-role/create') ? 'active' : '' }}">
                                                <a class="nav-link " href="{{ route('vendor.custom-role.create') }}"
                                                    title="Staff Role">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate">Staff Roles (Permissions)</span>
                                                </a>
                                            </li>
                                            <li
                                                class="nav-item {{ Request::is('store-panel/staff/settings*') ? 'active' : '' }}">
                                                <a class="nav-link " href="{{ route('vendor.staff.settings') }}"
                                                    title="Staff T&C">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate">Staff Settings</span>
                                                </a>
                                            </li>

                                        </ul>
                                    </li>
                                @endif
                                @if (selected_menu('attendance_manage') && \App\CentralLogics\Helpers::employee_module_permission_check('hr_manage'))
                                    @if (\App\CentralLogics\Helpers::permission_check('hr_manage'))
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/attendance*') ? 'active' : '' }}">
                                            <a class="sub-link  js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:;" title="Attendance Management">
                                                <i class="tio-event nav-icon"></i>
                                                <span class="text-truncate">Attendance
                                                    Management</span>
                                            </a>

                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                                style="display: {{ Request::is('store-panel/attendance*') ? 'block' : 'none' }}">

                                                <li
                                                    class="nav-item {{ Request::is('store-panel/attendance/list') ? 'active' : '' }}">
                                                    <a class="nav-link " href="{{ route('vendor.attendance.all') }}"
                                                        title="{{ translate('messages.manage') }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Attendance Manage</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="nav-item {{ Request::is('store-panel/attendance/report') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.attendance.report') }}"
                                                        title="Reports">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Attendance Reports</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </li>
                                    @endif
                                @endif
                                @if (\App\CentralLogics\Helpers::employee_module_permission_check('hr_manage'))
                                    <li
                                        class="navbar-vertical-aside {{ Request::is('store-panel/shifts*') ? 'active' : '' }}">
                                        <a class="sub-link  js-navbar-vertical-aside nav-link"
                                            href="{{ route('vendor.shifts.index') }}" title="Shifts Management">
                                            <i class="tio-timer nav-icon"></i>
                                            <span
                                                class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Shifts
                                                Management</span>
                                        </a>
                                    </li>
                                @endif

                                @if (selected_menu('leave_manage') && \App\CentralLogics\Helpers::employee_module_permission_check('hr_manage'))
                                    @if (\App\CentralLogics\Helpers::permission_check('hr_manage'))
                                        <li
                                            class="navbar-vertical-aside {{ Request::is('store-panel/leave*') ? 'active' : '' }}">
                                            <a class="sub-link js-navbar-vertical-aside nav-link"
                                                href="{{ route('vendor.leave.all') }}" title="Leave Management">
                                                <i class="tio-category nav-icon"></i>
                                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Leave
                                                    Management</span>
                                            </a> 
                                        </li>
                                    @endif
                                @endif

                                @if (selected_menu('salary_manage') && \App\CentralLogics\Helpers::employee_module_permission_check('hr_manage'))
                                    @if (\App\CentralLogics\Helpers::permission_check('hr_manage'))
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/salary*') || Request::is('store-panel/task-salary-categories')  ? 'active' : '' }}">
                                            <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:;" title="Salary">
                                                <i class="tio-user nav-icon"></i>
                                                <span class=" text-truncate">Salary
                                                    Management</span>
                                            </a>
                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                                style="display: {{ Request::is('store-panel/salary*') || Request::is('store-panel/task-salary-categories') ? 'block' : 'none' }}">

                                                <li
                                                    class="nav-item {{ Request::is('store-panel/salary/list') ? 'active' : '' }}">
                                                    <a class="nav-link " href="{{ route('vendor.salary.list') }}"
                                                        title="manage">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Salary Manage</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="nav-item {{ Request::is('store-panel/task-salary-categories') ? 'active' : '' }}">
                                                    <a class="nav-link "
                                                        href="{{ route('vendor.task-salary-categories.index') }}"
                                                        title="report">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Task Salary Category</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="nav-item {{ Request::is('store-panel/salary/report') ? 'active' : '' }}">
                                                    <a class="nav-link " href="{{ route('vendor.salary.report') }}"
                                                        title="report">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">Salary Report</span>
                                                    </a>
                                                </li>


                                            </ul>
                                        </li>
                                    @endif
                                @endif

                            </ul>
                        </li>
                    @endif
                    {{-- =============================== MENU PREFERENCE =========================== --}}
                    <li
                        class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/menu-preference') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link "
                            href="{{ route('vendor.menu_preference') }}" title="Menu Preference">
                            <img src="{{ asset('storage/app/public/util/app.png') }}" alt=""
                                class="nav-link-icon">
                            <span class="text-truncate"> Menu Preferences</span>
                        </a>
                    </li>

                    {{-- =============================== MY BUSINESS =========================== --}}
                    @if (selected_menu('my_business'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/business-settings*') || Request::is('store-panel/withdraw-method*') || Request::is('store-panel/wallet/wallet-payment-list') || Request::is('store-panel/settings/general*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="My Business">
                                <img src="{{ asset('storage/app/public/nav/business-model.png') }}" alt=""
                                    class="nav-link-icon">

                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    My Business</span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                <li class="nav-item {{ Request::is('store-panel/settings/general/profile') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.settings.webpage') }}"
                                        title="Webpage Settings">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Webpage Settings</span>
                                    </a>
                                </li>

                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/business-settings*') || Request::is('store-panel/withdraw-method*') || Request::is('store-panel/wallet/wallet-payment-list') || Request::is('store-panel/settings/general*') ? 'active' : '' }}">
                                    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                        href="javascript:;" title="Business Section">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            Business Section</span>
                                    </a>
                                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                        <li
                                            class="nav-item {{ Request::is('store-panel/settings/general/profile') ? 'active' : '' }}">
                                            <a class="nav-link "
                                                href="{{ route('vendor.settings.general.profile') }}"
                                                title="Profile Settings">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Profile Settings</span>
                                            </a>
                                        </li>
                                        @if (\App\CentralLogics\Helpers::employee_module_permission_check('store_setup'))
                                            <li
                                                class="nav-item {{ Request::is('store-panel/business-settings/store-setup') ? 'active' : '' }}">
                                                <a class="nav-link "
                                                    href="{{ route('vendor.business-settings.store-setup') }}"
                                                    title="Store Settings">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate">Store Settings</span>
                                                </a>
                                            </li>
                                            <li class="nav-item {{ Request::is('store-panel/business-settings/about-us') ? 'active' : '' }}"
                                                style="margin-top:0 !important;">
                                                <a class="nav-link "
                                                    href="{{ route('vendor.business-settings.about-us') }}"
                                                    title="Terms and Conditions">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate">About Us</span>
                                                </a>
                                            </li>
                                            <li class="nav-item {{ Request::is('store-panel/general/holidays') ? 'active' : '' }}"
                                                style="margin-top:0 !important;">
                                                <a class="nav-link "
                                                    href="{{ route('vendor.settings.general.holidays') }}"
                                                    title="Holidays">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate">Holidays</span>
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </li>
                                @if (auth('vendor')->check())
                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/offer-banner') ? 'active' : '' }}">
                                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                                            href="{{ route('vendor.banner.offer') }}"
                                            title="{{ translate('messages.offer_banners') }}">
                                            <img src="{{ asset('storage/app/public/nav/sale.png') }}"
                                                alt="" class="nav-link-icon">

                                            <span
                                                class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.offer_banners') }}</span>
                                        </a>
                                    </li>
                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/gallery') ? 'active' : '' }}">
                                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                                            href="{{ route('vendor.gallery.all') }}"
                                            title="{{ translate('messages.gallery') }}">
                                            <img src="{{ asset('storage/app/public/nav/gallery.png') }}"
                                                alt="" class="nav-link-icon">

                                            <span class=" text-truncate">{{ translate('messages.gallery') }}</span>
                                        </a>
                                    </li>
                                @endif
                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/service/reviews*') ? 'active' : '' }}">
                                    <a class="js-navbar-vertical-aside-menu-link nav-link"
                                        href="{{ route('vendor.service.reviews') }}" title="Reviews">
                                        <img src="{{ asset('storage/app/public/nav/review.png') }}" alt=""
                                            class="nav-link-icon">
                                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            Reviews
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                    {{-- =============================== MY WALLET =========================== --}}
                    @if (selected_menu('my_wallet') && \App\CentralLogics\Helpers::employee_module_permission_check('wallet'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/wallet/wallet-payment-list') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.wallet.wallet_payment_list') }}"
                                title="{{ translate('messages.my_wallet') }}">
                                <img src="{{ asset('storage/app/public/nav/wallet (1).png') }}" alt=""
                                    class="nav-link-icon">
                                <span class=" text-truncate">{{ translate('messages.my_wallet') }}</span>
                            </a>
                        </li>

                        @if (
                            \App\CentralLogics\Helpers::employee_module_permission_check('wallet') &&
                                \App\CentralLogics\Helpers::get_store_data()->module_id == 5)
                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/withdraw-method*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link"
                                    href="{{ route('vendor.wallet-method.index') }}"
                                    title="{{ translate('messages.my_wallet') }}">
                                    <i class="tio-museum nav-icon"></i>
                                    <span
                                        class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.disbursement_method') }}</span>
                                </a>
                            </li>
                        @endif
                    @endif
                    @if (auth('vendor')->check())
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/terms-and-conditions') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link "
                                href="{{ route('vendor.terms-and-conditions.view') }}" title="My Chitti T&C">
                                <img src="{{ asset('storage/app/public/nav/file.png') }}" alt=""
                                    class="nav-link-icon">

                                <span class="text-truncate">My Chitti T&C</span>
                            </a>
                        </li>
                    @elseif(auth('vendor_employee')->check())
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/terms-n-conditions') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link "
                                href="{{ route('vendor.staff.terms-n-conditions') }}"
                                title="{{ $store_data->name }} T&C">
                                <img src="{{ asset('storage/app/public/nav/file.png') }}" alt=""
                                    class="nav-link-icon">

                                <span class="text-truncate">{{ $store_data->name }} T&C</span>
                            </a>
                        </li>
                    @endif
                    @if (selected_menu('notifications'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/notifications*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.notifications') }}" title="notifications">
                                <img src="{{ asset('storage/app/public/nav/ringing.png') }}" alt=""
                                    class="nav-link-icon">

                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Notifications
                                </span>
                            </a>
                        </li>
                    @endif

                    {{-- @if (selected_menu('client_manage') && \App\CentralLogics\Helpers::employee_module_permission_check('client_manage'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/customer') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.customer.list') }}"
                                title="{{ translate('messages.clients') }} Management">
                                <img src="{{ asset('storage/app/public/nav/client (1).png') }}" alt=""
                                    class="nav-link-icon">

                                <span class=" text-truncate">{{ translate('messages.clients') }} Management</span>
                            </a>
                        </li>
                    @endif --}}

                    @if (selected_menu('smart_calendar'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/smart-calendar*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.smart-calendar.all') }}" title="Smart Calendar">
                                <img src="{{ asset('storage/app/public/nav/reminder.png') }}" alt=""
                                    class="nav-link-icon">
                                <span class=" text-truncate">Smart Calendar</span>
                            </a>
                        </li>
                    @endif

                    <!-- End Dashboards -->

                    @if (auth('vendor_employee')->check())
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/attendance*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.employee-attendance') }}" title="attendance">
                                <img src="{{ asset('storage/app/public/nav/attendance.png') }}" alt=""
                                    class="nav-link-icon">
                                <span class=" text-truncate">
                                    Attendance
                                </span>
                            </a>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/salary-history*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.salary-history') }}" title="My Salary">
                                <img src="{{ asset('storage/app/public/nav/salary.png') }}" alt=""
                                    class="nav-link-icon">
                                <span class=" text-truncate">
                                    My Salary
                                </span>
                            </a>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/leaves*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.employee-leave') }}" title="My Leaves">
                                <img src="{{ asset('storage/app/public/nav/leave (1).png') }}" alt=""
                                    class="nav-link-icon">
                                <span class=" text-truncate">
                                    My Leaves
                                </span>
                            </a>
                        </li>
                    @endif





                    @if (in_array($store_data->module->module_type, ['ecommerce']))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/item/flash-sale*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.item.flash_sale') }}"
                                title="{{ translate('messages.flash_sales') }}">
                                <i class="tio-apps nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.flash_sales') }}
                                </span>
                            </a>
                        </li>
                    @endif

                    @if ($store_data->module->module_type == 'ecommerce')
                        <li class="nav-item">
                            <small
                                class="nav-subtitle">{{ $store_data->module->module_type == 'ecommerce' ? translate('messages.item_management') : 'Service Management' }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                    @endif

                    <!-- AddOn -->
                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('addon'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/addon*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.addon.add-new') }}"
                                title="{{ translate('messages.addons') }}">
                                <i class="tio-add-circle-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.addons') }}
                                </span>
                            </a>
                        </li>
                    @endif

                    <!-- End AddOn -->
                    @if (
                        \App\CentralLogics\Helpers::employee_module_permission_check('item') &&
                            $store_data->module->module_type == 'ecommerce')
                        <!-- Food -->
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/item*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                href="javascript:" title="{{ translate('messages.items') }}">
                                <i class="tio-premium-outlined nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.items') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('store-panel/item*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('store-panel/item/add-new') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.item.add-new') }}"
                                        title="{{ translate('messages.add_new_item') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.add_new') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('store-panel/item/list') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.item.list') }}"
                                        title="{{ translate('messages.items_list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.list') }}</span>
                                    </a>
                                </li>

                                @if (\App\CentralLogics\Helpers::get_mail_status('product_approval'))
                                    <li
                                        class="nav-item {{ Request::is('store-panel/item/pending/item/list') || Request::is('store-panel/item/requested/item/view/*') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.item.pending_item_list') }}"
                                            title="{{ translate('messages.pending_item_list') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span
                                                class="text-truncate">{{ translate('messages.pending_item_list') }}</span>
                                        </a>
                                    </li>
                                @endif
                                @if (\App\CentralLogics\Helpers::get_mail_status('product_gallery'))
                                    <li class="nav-item {{ Request::is('store-panel/item/product-gallery') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.item.product_gallery') }}"
                                            title="{{ translate('messages.Product_Gallery') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span
                                                class="text-truncate">{{ translate('messages.Product_Gallery') }}</span>
                                        </a>
                                    </li>
                                @endif
                                <li class="nav-item {{ Request::is('store-panel/item/price-update-list') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.item.price-update-list') }}"
                                        title="{{ translate('messages.price_update_list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Price Update</span>
                                    </a>
                                </li>

                                @if ($store_data->module->module_type != 'food')
                                    <li class="nav-item {{ Request::is('store-panel/item/stock-limit-list') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.item.stock-limit-list') }}"
                                            title="{{ translate('messages.stock_limit_list') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span
                                                class="text-truncate">{{ translate('messages.stock_limit_list') }}</span>
                                        </a>
                                    </li>
                                @endif
                                @if (\App\CentralLogics\Helpers::get_store_data()->item_section)
                                    <li class="nav-item {{ Request::is('store-panel/item/bulk-import') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.item.bulk-import') }}"
                                            title="{{ translate('messages.bulk_import') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span
                                                class="text-truncate text-capitalize">{{ translate('messages.bulk_import') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('store-panel/item/bulk-export') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.item.bulk-export-index') }}"
                                            title="{{ translate('messages.bulk_export') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span
                                                class="text-truncate text-capitalize">{{ translate('messages.bulk_export') }}</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                        <!-- End Food -->
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/category*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                href="javascript:" title="{{ translate('messages.categories') }}">
                                <i class="tio-category nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.categories') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('store-panel/category*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('store-panel/category/list') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.category.add') }}"
                                        title="{{ translate('messages.category') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.category') }}</span>
                                    </a>
                                </li>

                                <li
                                    class="nav-item {{ Request::is('store-panel/category/sub-category-list') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.category.add-sub-category') }}"
                                        title="{{ translate('messages.sub_category') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.sub_category') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    <!-- DeliveryMan -->
                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('deliveryman'))
                        <li class="nav-item">
                            <small class="nav-subtitle"
                                title="{{ translate('messages.deliveryman_section') }}">{{ translate('messages.deliveryman_section') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/delivery-man/add') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.delivery-man.add') }}"
                                title="{{ translate('messages.add_delivery_man') }}">
                                <i class="tio-running nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.add_delivery_man') }}
                                </span>
                            </a>
                        </li>

                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/delivery-man/list') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.delivery-man.list') }}"
                                title="{{ translate('messages.deliveryman') }}">
                                <i class="tio-filter-list nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.deliverymen_list') }}
                                </span>
                            </a>
                        </li>
                    @endif


                    <!-- Campaign -->
                    @if ($store_data->module->id == 5 && \App\CentralLogics\Helpers::employee_module_permission_check('campaign'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/campaign*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                href="javascript:" title="{{ translate('messages.campaigns') }}">
                                <i class="tio-image nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.campaigns') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('store-panel/campaign*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('store-panel/campaign/list') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.campaign.list') }}"
                                        title="{{ translate('messages.basic_campaigns') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate">{{ translate('messages.basic_campaigns') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('store-panel/campaign/item/list') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.campaign.itemlist') }}"
                                        title="{{ translate('messages.Item Campaigns') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate">{{ translate('messages.Item Campaigns') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                    <!-- End Campaign -->

                    <!-- Coupon -->
                    @if ($store_data->module->id == 5 && \App\CentralLogics\Helpers::employee_module_permission_check('coupon'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/coupon*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.coupon.add-new') }}"
                                title="{{ translate('messages.coupons') }}">
                                <i class="tio-ticket nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.coupons') }}</span>
                            </a>
                        </li>
                    @endif
                    <!-- End Coupon -->
                    @if (selected_menu('patients_manage') && _offeredModule('patient_manage'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/patient*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.patient.add') }}" title="Patients Management">
                                <i class="tio-notifications nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Patients Management
                                </span>
                            </a>
                        </li>
                    @endif






                    <!-- Business Section-->

                    @if (selected_menu('reports') && \App\CentralLogics\Helpers::employee_module_permission_check('reports'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/business-settings/reports2*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                href="javascript:;" title="Reports">
                                <img src="{{ asset('storage/app/public/nav/report.png') }}" alt=""
                                    class="nav-link-icon">
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Reports</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                <li class="nav-item {{ Request::is('store-panel/business-settings/reports2') ? 'active' : '' }}"
                                    style="margin-top:0 !important;">
                                    <a class="nav-link " href="javascript:;" title="Coming Soon">
                                        <span class="tio-settings nav-icon"></span>
                                        <span class="text-truncate">Coming Soon</span>
                                    </a>
                                </li>

                            </ul>
                        </li>
                    @endif



                    <!-- End StoreWallet -->
                    @if ($store_data->module->id == 5 && \App\CentralLogics\Helpers::employee_module_permission_check('reviews'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/reviews') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.reviews') }}"
                                title="{{ translate('messages.reviews') }}">
                                <i class="tio-star-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.reviews') }}
                                </span>
                            </a>
                        </li>
                    @endif
                    <!-- End Business Settings -->
                    @if (
                        \App\CentralLogics\Helpers::employee_module_permission_check('chat') &&
                            \App\CentralLogics\Helpers::get_store_data()->module_id == 5)
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/message*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.message.list') }}"
                                title="{{ translate('messages.chat') }}">
                                <i class="tio-chat nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.Chat') }}
                                </span>
                            </a>
                        </li>
                    @endif

                    {{-- <li class="nav-item">
                        <small class="nav-subtitle"
                            title="{{ translate('messages.Report_section') }}">{{ translate('messages.Report_section') }}</small>
                        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li> --}}

                    @if (
                        \App\CentralLogics\Helpers::employee_module_permission_check('report') &&
                            \App\CentralLogics\Helpers::get_store_data()->module_id == 5)
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/vendor/report/expense-report') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('vendor.report.expense-report') }}"
                                title="{{ translate('messages.expense_report') }}">
                                <span class="tio-history nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.expense_report') }}</span>
                            </a>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/report/disbursement-report') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('vendor.report.disbursement-report') }}"
                                title="{{ translate('messages.disbursement_report') }}">
                                <span class="tio-saving nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.disbursement_report') }}</span>
                            </a>
                        </li>
                    @endif







                    @if (auth('vendor')->check())

                        @if (selected_menu('library'))
                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/library*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link"
                                    href="{{ route('vendor.library.all') }}" title="Library">
                                    <img src="{{ asset('storage/app/public/nav/contract.png') }}" alt=""
                                        class="nav-link-icon">
                                    <span class=" text-truncate">Library</span>
                                </a>
                            </li>
                        @endif
                        @if (selected_menu('documents'))
                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/documents*') ||  Request::is('store-panel/business-settings/settings/receivable-receipts') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                    href="javascript:;" title="Documents">
                                    <img src="{{ asset('storage/app/public/nav/task (1).png') }}" alt=""
                                        class="nav-link-icon">

                                    <span
                                        class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Documents</span>
                                </a>

                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: {{ Request::is('store-panel/documents*') ||  Request::is('store-panel/business-settings/settings/receivable-receipts') ? 'block' : 'none' }}">

                                    <li
                                        class="nav-item {{ Request::is('store-panel/documents/receivable-receipt/list') ? 'active' : '' }}">
                                        <a class="nav-link "
                                            href="{{ route('vendor.documents.receivable-receipt.list') }}"
                                            title="list Project">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Receivable Receipts List</span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('store-panel/business-settings/settings/receivable-receipts') ? 'active' : '' }}"
                                        style="margin-top:0 !important;">
                                        <a class="nav-link "
                                            href="{{ route('vendor.business-settings.settings.receivable-receipts') }}"
                                            title="Receivable Receipt Settings">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Receivable Receipt Settings</span>
                                        </a>
                                    </li>
                                    <li
                                        class="nav-item {{ Request::is('store-panel/documents/job-card/list') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.documents.job-card.list') }}"
                                            title="list Project">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Jobcards List</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    @endif
 
                    @if (selected_menu('subscriptions') && auth('vendor')->check())
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/subscriptions') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link "
                                href="{{ route('vendor.subscriptions') }}" title="Subscriptions">
                                <img src="{{ asset('storage/app/public/nav/subscription.png') }}" alt=""
                                    class="nav-link-icon">
                                <span class="text-truncate">Subscriptions</span>
                            </a>
                        </li>
                    @endif
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/subscriptions') ? 'active' : '' }}">
                        <div style="padding: 10px 25px; display: flex; align-items: center;">
                            <label class="switch toggle-switch-lg m-0">
                                <input type="checkbox" class="toggle-switch-input keep-minimized"
                                 {{_isMenuMinimized() ? 'checked' : ''}}   value = '1'>
                                <span class="toggle-switch-label">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
                            </label>
                            <span class="text-primary pl-2" style="font-size: 14px;">Keep Menu <br> Minimized</span>
                        </div>
                    </li>

                    <li class="nav-item py-5">

                    </li>
                </ul>
            </div>
            <!-- End Content -->
        </div>
    </aside>
</div>

<div id="sidebarCompact" class="d-none">

</div>

@push('script_2')
    <script>
        $(window).on('load', function() {
            if ($(".navbar-vertical-content li.active").length) {
                $('.navbar-vertical-content').animate({
                    scrollTop: $(".navbar-vertical-content li.active").offset().top - 150
                }, 10);
            }
        });

        var $rows = $('#navbar-vertical-content li');
        $('#search-sidebar-menu').keyup(function() {
            var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();

            $rows.show().filter(function() {
                var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
                return !~text.indexOf(val);
            }).hide();
        });
    </script>
@endpush
