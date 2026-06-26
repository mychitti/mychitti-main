<li class="navbar-vertical-aside-has-menu {{ Request::is('*whatsapp*') ? 'active' : '' }}">
    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="{{ translate('WhatsApp') }}">
        <i class="tio-chat nav-link-icon" style="font-size:20px;"></i>
        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('WhatsApp') }}</span>
    </a>
    <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{ Request::is('*whatsapp*') ? 'd-block' : '' }}">
        <li class="nav-item {{ Request::is('*whatsapp/connect*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.whatsapp.connect') }}" title="{{ translate('Connection & Add-ons') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Connection & Add-ons') }}</span>
            </a>
        </li>
        {{-- <li class="nav-item {{ Request::is('*whatsapp/templates*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.whatsapp.templates') }}" title="{{ translate('Message Templates') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Message Templates') }}</span>
            </a>
        </li> --}}
    </ul>
</li>
