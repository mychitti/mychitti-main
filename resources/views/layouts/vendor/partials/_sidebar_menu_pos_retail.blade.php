{{-- Retail POS sidebar (business_type = pos_retail) --}}
<li class="nav-item">
    <small class="nav-subtitle" title="Retail POS">Retail POS</small>
    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
</li>

@if (selected_menu('dashboard'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('retail-pos/dashboard') || Request::is('store-panel') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.retail-pos.dashboard') }}" title="Dashboard">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Dashboard_color.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Dashboard</span>
        </a>
    </li>
@endif


@if (auth('vendor')->check() || hasPermission('pos_billing', 'create'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('retail-pos') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.retail-pos.index') }}" title="New Sale">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/pos_items.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">New Sale</span>
        </a>
    </li>
@endif

@if (auth('vendor')->check() || hasPermission('pos_bills', 'view'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('retail-pos/today') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.retail-pos.today') }}" title="Today's Bills">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/token_list.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Today's Bills</span>
        </a>
    </li>
@endif

@if (auth('vendor')->check() || hasPermission('pos_gst_report', 'view'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('retail-pos/gst-report') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.retail-pos.gst-report') }}" title="GST Report">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Terms_and_Conditions_color.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">GST Report</span>
        </a>
    </li>
@endif

@if (auth('vendor')->check() || hasPermission('pos_branch', 'view') || hasPermission('pos_branch', 'create') || hasPermission('pos_branch', 'delete') || hasPermission('pos_counter', 'create') || hasPermission('pos_counter', 'delete'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('retail-pos/terminals') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.retail-pos.terminals') }}" title="Branches & Counters">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/branches.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Branches &amp; Counters</span>
        </a>
    </li>
@endif

@if (auth('vendor')->check() || hasPermission('pos_branch_stock', 'view'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('retail-pos/branch-stock') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.retail-pos.branch-stock') }}" title="Branch Stock">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/inventory.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Branch Stock</span>
        </a>
    </li>
@endif

<li class="nav-item">
    <small class="nav-subtitle" title="{{ translate('Other') }}">{{ translate('Other') }}</small>
    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
</li>

<li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel/master-dashboard') ? 'active' : '' }}">
    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.master-dashboard') }}"
        title="{{ translate('messages.dashboard') }}">
        <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Dashboard_color.png') }}" alt="" class="nav-link-icon">
        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
            Master {{ translate('messages.dashboard') }}
        </span>
    </a>
</li>
@include('layouts.vendor.partials._sidebar_menu_default', [
    'store_data' => $store_data,
    'skipForPOS' => true,
])
