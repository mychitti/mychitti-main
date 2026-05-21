{{-- POS items as top-level --}}
<li class="nav-item">
    <small class="nav-subtitle" title="POS Management">POS Management</small>
    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
</li>

@if (selected_menu('dashboard'))
    @php $hasPOS = \App\CentralLogics\Helpers::permission_check('pos'); @endphp
    <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel') || Request::is('pos/dashboard*') || Request::is('service/leads-dashboard*') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link"
            href="{{ $hasPOS ? route('vendor.pos.dashboard') : route('vendor.dashboard') }}"
            title="Dashboard">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Dashboard_color.png') }}" alt="" class="nav-link-icon">
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


@if (selected_menu('pos') && hasMasterModulePermission('pos'))

    @if (hasPermission('pos_token', 'generate'))
        <li class="navbar-vertical-aside-has-menu {{ Request::is('pos/token') ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.pos.token') }}" title="Token Generate">
                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/token_generate.png') }}" alt="" class="nav-link-icon">
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Token Generate</span>
            </a>
        </li>
    @endif

    @if (hasPermission('restaurant_tables', 'list') || hasPermission('restaurant_tables', 'add'))
        <li class="navbar-vertical-aside-has-menu {{ Request::is('pos/restaurant-tables*') ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="Restaurant Tables">
                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/restaurant_tables.png') }}" alt="" class="nav-link-icon">
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Restaurant Tables</span>
            </a>
            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                style="display: {{ Request::is('pos/restaurant-tables*') ? 'block' : 'none' }}">
                @if (hasPermission('restaurant_tables', 'list'))
                    <li class="nav-item {{ Request::is('pos/restaurant-tables/index') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('vendor.pos.restaurant-tables.index') }}" title="{{ translate('messages.List') }}">
                            <span class="tio-circle nav-indicator-icon"></span>
                            <span class="text-truncate">List</span>
                        </a>
                    </li>
                @endif
                @if (hasPermission('restaurant_tables', 'add'))
                    <li class="nav-item {{ Request::is('pos/restaurant-tables/create') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('vendor.pos.restaurant-tables.create') }}" title="{{ translate('messages.Add New') }}">
                            <span class="tio-circle nav-indicator-icon"></span>
                            <span class="text-truncate">Add new</span>
                        </a>
                    </li>
                @endif
            </ul>
        </li>
    @endif

    @if (hasPermission('pos_token', 'list'))
        <li class="navbar-vertical-aside-has-menu {{ Request::is('pos/token-list') ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.pos.token.list') }}" title="Tokens List">
                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/token_list.png') }}" alt="" class="nav-link-icon">
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Tokens List</span>
            </a>
        </li>
    @endif

    @if (hasPermission('pos_items', 'list') || hasPermission('pos_items', 'add'))
        <li class="navbar-vertical-aside-has-menu {{ Request::is('pos/items') ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.pos.items') }}" title="POS Items">
                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/pos_items.png') }}" alt="" class="nav-link-icon">
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">POS Items</span>
            </a>
        </li>
    @endif

    @if (hasPermission('pos_branch', 'list') || hasPermission('pos_branch', 'add'))
        <li class="navbar-vertical-aside-has-menu {{ Request::is('pos/branch*') ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.pos.branch.index') }}" title="Branches">
                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/branches.png') }}" alt="" class="nav-link-icon">
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Branches</span>
            </a>
        </li>
    @endif

    @if (hasPermission('pos', 'settings'))
        <li class="navbar-vertical-aside-has-menu {{ Request::is('pos/settings') ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.pos.settings') }}" title="POS Settings">
                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/settings.png') }}" alt="" class="nav-link-icon">
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Settings</span>
            </a>
        </li>
    @endif

    @if (hasPermission('pos', 'report'))
        <li class="navbar-vertical-aside-has-menu {{ Request::is('pos/report') ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.pos.report') }}" title="POS Report">
                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Terms_and_Conditions_color.png') }}" alt="" class="nav-link-icon">
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Report</span>
            </a>
        </li>
    @endif
 @endif

{{-- Leads Management — always visible for POS vendors regardless of POS subscription --}}
@if (selected_menu('leads_manage') && hasMasterModulePermission('leads_manage'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('service/report') || Request::is('lead*') || Request::is('service/leads*') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="{{ _moduleLabel('leads_manage') }}">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/leads_management_color.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ _moduleLabel('leads_manage') }}</span>
        </a>
        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
            style="display: {{ Request::is('service/report') || Request::is('lead*') || Request::is('service/leads*') ? 'block' : 'none' }}">
            <li class="nav-item {{ Request::is('service/leads-dashboard*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('vendor.service.leads-dashboard') }}" title="Leads Dashboard">
                    <span class="tio-circle nav-indicator-icon"></span>
                    <span class="text-truncate">Leads Dashboard</span>
                </a>
            </li>
            @if (hasAnyPermission(['leads_manage.list', 'leads_manage.add', 'leads_manage.statuses', 'leads_manage.export']))
                <li class="nav-item {{ Request::is('service/leads*') && !Request::is('service/leads-dashboard*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.service.leads_list') }}" title="Leads">
                        <span class="tio-circle nav-indicator-icon"></span>
                        <span class="text-truncate">Leads</span>
                    </a>
                </li>
            @endif
            @if (hasAnyPermission(['leads_manage.report']))
                <li class="nav-item {{ Request::is('service/report') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.service.report') }}" title="Leads Report">
                        <span class="tio-circle nav-indicator-icon"></span>
                        <span class="text-truncate">Leads Report</span>
                    </a>
                </li>
            @endif
            <li class="nav-item {{ Request::is('service/lead-settings') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('vendor.service.lead-settings') }}" title="Lead Settings">
                    <span class="tio-circle nav-indicator-icon"></span>
                    <span class="text-truncate">Lead Settings</span>
                </a>
            </li>
        </ul>
    </li>
@endif

<li class="nav-item">
    <small class="nav-subtitle" title="{{ translate('Other') }}">{{ translate('Other') }}</small>
    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
</li>

@include('layouts.vendor.partials._sidebar_menu_default', [
    'store_data' => $store_data,
    'skipForPOS' => true,
])
