{{-- Retail POS sidebar (business_type = pos_retail) --}}
@php
    // Seed the grouped Retail POS menu masterdata (so it appears on the Menu Preference page).
    \App\Modules\PosRetail\Controllers\Vendor\RetailPosController::ensureMenuMasterdata();

    // Retail POS is a PAID module — the whole block is hidden unless the store holds an active
    // subscription that includes `pos_retail` (matches the route-level guard intent).
    $hasPosRetail = \App\CentralLogics\Helpers::permission_check('pos_retail');

    // On first render after the subscription is active, enable + save all Retail POS sidebar
    // options once (covers every purchase path). Later manual toggles are preserved.
    if ($hasPosRetail) {
        \App\Modules\PosRetail\Controllers\Vendor\RetailPosController::syncMenusOnSubscription(\App\CentralLogics\Helpers::get_store_id());
    }

    // Owner sees everything; staff see the Retail POS block only if they hold at least one of
    // its permissions (otherwise the section header would render with no items beneath it).
    $canPosRetail = $hasPosRetail && (auth('vendor')->check()
        || hasPermission('pos_billing', 'create')
        || hasPermission('pos_bills', 'view')
        || hasPermission('pos_gst_report', 'view')
        || hasPermission('pos_branch', 'view')
        || hasPermission('pos_branch', 'create')
        || hasPermission('pos_branch', 'delete')
        || hasPermission('pos_counter', 'create')
        || hasPermission('pos_counter', 'delete')
        || hasPermission('pos_branch_stock', 'view')
        || hasPermission('pos_gatepass', 'view')
        || hasPermission('pos_writeoff', 'view'));
@endphp

@if ($hasPosRetail)

@if ($canPosRetail)
    <li class="nav-item">
        <small class="nav-subtitle" title="Retail POS">Retail POS</small>
        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
    </li>
@endif

@if ((auth('vendor')->check() || hasPermission('pos_bills', 'view')) && selected_menu('retail_dashboard'))
    <li class="navbar-vertical-aside-has-menu {{  Request::is('dashboard') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.dashboard') }}" title="Dashboard">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Dashboard_color.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Dashboard</span>
        </a>
    </li>
@endif


@if ((auth('vendor')->check() || hasPermission('pos_billing', 'create')) && selected_menu('retail_new_sale'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('retail-pos') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.retail-pos.index') }}" title="New Sale">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/pos_items.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">New Sale</span>
        </a>
    </li>
@endif

@if ((auth('vendor')->check() || hasPermission('pos_bills', 'view')) && selected_menu('retail_bills'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('retail-pos/today') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.retail-pos.today') }}" title="Today's Bills">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/token_list.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Today's Bills</span>
        </a>
    </li>
@endif

@if ((auth('vendor')->check() || hasPermission('pos_gst_report', 'view')) && selected_menu('retail_gst_report'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('retail-pos/gst-report') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.retail-pos.gst-report') }}" title="GST Report">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Terms_and_Conditions_color.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">GST Report</span>
        </a>
    </li>
@endif

@if ((auth('vendor')->check() || hasPermission('pos_branch', 'view') || hasPermission('pos_branch', 'create') || hasPermission('pos_branch', 'delete') || hasPermission('pos_counter', 'create') || hasPermission('pos_counter', 'delete')) && selected_menu('retail_branches'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('retail-pos/terminals') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.retail-pos.terminals') }}" title="Branches & Counters">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/branches.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Branches &amp; Counters</span>
        </a>
    </li>
@endif

@if ((auth('vendor')->check() || hasPermission('pos_branch_stock', 'view')) && selected_menu('retail_branch_stock'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('retail-pos/branch-stock') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.retail-pos.branch-stock') }}" title="Branch Stock">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/inventory.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Branch Stock</span>
        </a>
    </li>
@endif

@if ((auth('vendor')->check() || hasPermission('pos_gatepass', 'view')) && selected_menu('retail_gatepass'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('retail-pos/gatepass*') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.retail-pos.gatepass') }}" title="Stock Transfer">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/inventory.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Stock Transfer</span>
        </a>
    </li>
@endif

@if ((auth('vendor')->check() || hasPermission('pos_writeoff', 'view')) && selected_menu('retail_writeoff'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('retail-pos/writeoff*') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.retail-pos.writeoff') }}" title="Damaged / Theft">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/inventory.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Damaged / Theft</span>
        </a>
    </li>
@endif

@if ((auth('vendor')->check() || hasPermission('pos_cash', 'view')) && selected_menu('retail_cash_flow'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('retail-pos/cash-flow*') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.retail-pos.cash-flow') }}" title="Cash Flow">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/settings.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Cash Flow</span>
        </a>
    </li>
@endif

@if (auth('vendor')->check())
    <li class="navbar-vertical-aside-has-menu {{ Request::is('retail-pos/settings') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.retail-pos.settings') }}" title="Settings">
            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/settings.png') }}" alt="" class="nav-link-icon">
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Settings</span>
        </a>
    </li>
@endif

@endif {{-- $hasPosRetail --}}

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
    'showWhatsappAbovePostAds' => true,
])
