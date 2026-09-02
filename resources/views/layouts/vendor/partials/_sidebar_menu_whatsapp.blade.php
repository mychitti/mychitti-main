{{-- Each entry below is gated the same way its landing route is: any action on that feature
     opens the screen it lives on. Gating a menu item on `list` alone meant granting somebody
     Create on campaigns left them with a permission and no way to reach it.
     The gates are in routes/vendor.php; the feature rows behind them are seeded by
     App\Services\WhatsAppPermissions. The store owner passes every check.
     Toggleable via Menu Preference (slug: whatsapp); on by default.

     PILOT_STORE_IDS is empty, so every store sees this. Put store ids back in that constant to
     narrow it to a pilot group again. --}}
@php
    // Seed the menu masterdata row and, for stores already in explicit Menu Preference mode, the
    // one visibility row this module needs. Without it the block below is invisible to every store
    // that saved its menus before WhatsApp existed. Insert-only, so a deliberate hide survives.
    \App\Services\WhatsAppService::ensureMenuVisible(\App\CentralLogics\Helpers::get_store_id());
    // The permission rows themselves, on the first panel page a store opens after this ships.
    \App\Services\WhatsAppPermissions::ensure();
@endphp
@if(selected_menu('whatsapp')
    && \App\Services\WhatsAppBilling::pilotVisible(\App\CentralLogics\Helpers::get_store_id())
    && hasAnyModulePermission(\App\Services\WhatsAppPermissions::featureNames()))
<li class="navbar-vertical-aside-has-menu {{ Request::is('*whatsapp*') ? 'active' : '' }}">
    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="{{ translate('WhatsApp') }}">
        <i class="tio-chat nav-link-icon" style="font-size:20px;"></i>
        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('WhatsApp') }}</span>
    </a>
    <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{ Request::is('*whatsapp*') ? 'd-block' : '' }}">
        @if (hasPermission('whatsapp', 'dashboard'))
        <li class="nav-item {{ Request::is('*whatsapp/dashboard*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.whatsapp.dashboard') }}" title="{{ translate('Dashboard') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Dashboard') }}</span>
            </a>
        </li>
        @endif
        @if (hasAnyModulePermission(['whatsapp_inbox']))
        <li class="nav-item {{ Request::is('*whatsapp/inbox*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.whatsapp.inbox') }}" title="{{ translate('Chats') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Chats') }}</span>
            </a>
        </li>
        @endif
        {{-- Beside Chats rather than buried under reports: a complaint is work to pick up, and
             the point of taking it off the inbox was that an inbox gets scrolled past. --}}
        @if (hasAnyModulePermission(['whatsapp_complaints']))
        <li class="nav-item {{ Request::is('*whatsapp/complaints*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.whatsapp.complaints') }}" title="{{ translate('Feedback & Complaints') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Feedback & Complaints') }}</span>
            </a>
        </li>
        @endif
        @if (hasAnyModulePermission(['whatsapp_connection', 'whatsapp_billing']))
        <li class="nav-item {{ Request::is('*whatsapp/connect*') || Request::is('*whatsapp/numbers*') || Request::is('*whatsapp/billing*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.whatsapp.connect') }}" title="{{ translate('Connection & Plan') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Connection & Plan') }}</span>
            </a>
        </li>
        @endif
        @if (hasAnyModulePermission(['whatsapp_bulk']))
        <li class="nav-item {{ Request::is('*whatsapp/bulk*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.whatsapp.bulk') }}" title="{{ translate('Bulk Message') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Bulk Message') }}</span>
            </a>
        </li>
        @endif
        @if (hasAnyModulePermission(['whatsapp_campaigns']))
        <li class="nav-item {{ Request::is('*whatsapp/campaigns*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.whatsapp.campaigns') }}" title="{{ translate('Campaign Series') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Campaign Series') }}</span>
            </a>
        </li>
        @endif
        {{-- Above Templates on purpose: when a message doesn't arrive this is the screen that
             says why, and a vendor who starts at Templates is already guessing. --}}
        @if (hasAnyModulePermission(['whatsapp_logs']))
        <li class="nav-item {{ Request::is('*whatsapp/message-log*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.whatsapp.message-log') }}" title="{{ translate('Message Log') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Message Log') }}</span>
            </a>
        </li>
        @endif
        @if (hasAnyModulePermission(['whatsapp_templates']))
        <li class="nav-item {{ Request::is('*whatsapp/templates*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.whatsapp.templates') }}" title="{{ translate('Message Templates') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Message Templates') }}</span>
            </a>
        </li>
        @endif
        @if (hasAnyModulePermission(['whatsapp_automation']))
        <li class="nav-item {{ Request::is('*whatsapp/automation*') || Request::is('*whatsapp/knowledge*') || Request::is('*whatsapp/bot*') || Request::is('*whatsapp/template-roles*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.whatsapp.automation') }}" title="{{ translate('Automation') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Automation') }}</span>
            </a>
        </li>
        @endif
    </ul>
</li>
@endif
 