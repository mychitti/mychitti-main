{{-- Owner-only: staff (vendor_employee) logins do not see the WhatsApp menu. --}}
@if(auth('vendor')->check())
<li class="navbar-vertical-aside-has-menu {{ Request::is('*whatsapp*') ? 'active' : '' }}">
    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="{{ translate('WhatsApp') }}">
        <i class="tio-chat nav-link-icon" style="font-size:20px;"></i>
        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('WhatsApp') }}</span>
    </a>
    <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{ Request::is('*whatsapp*') ? 'd-block' : '' }}">
        <li class="nav-item {{ Request::is('*whatsapp/dashboard*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.whatsapp.dashboard') }}" title="{{ translate('Dashboard') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Dashboard') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('*whatsapp/inbox*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.whatsapp.inbox') }}" title="{{ translate('Chats') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Chats') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('*whatsapp/connect*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.whatsapp.connect') }}" title="{{ translate('Connection & Bulk Message') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Connection & Bulk Message') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('*whatsapp/templates*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.whatsapp.templates') }}" title="{{ translate('Message Templates') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Message Templates') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('*whatsapp/knowledge*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.whatsapp.knowledge') }}" title="{{ translate('Auto-Reply Knowledge') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Auto-Reply Knowledge') }}</span>
            </a>
        </li>
    </ul>
</li>
@endif
 