{{-- Owner-only: staff (vendor_employee) logins do not see notification settings.
     Toggleable via Menu Preference (slug: notifications); on by default. --}}
@if(selected_menu('notifications') && auth('vendor')->check())
<li class="navbar-vertical-aside-has-menu {{ Request::is('*notification-settings*') ? 'active' : '' }}">
    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="{{ translate('Notifications') }}">
        <i class="tio-notifications-on-outlined nav-link-icon" style="font-size:20px;"></i>
        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('Notifications') }}</span>
    </a>
    <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{ Request::is('*notification-settings*') ? 'd-block' : '' }}">
        <li class="nav-item {{ Request::is('*notification-settings/send*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.notification-settings', ['direction' => 'send']) }}" title="{{ translate('Send Notifications') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Send Notifications') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('*notification-settings/receive*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('vendor.notification-settings', ['direction' => 'receive']) }}" title="{{ translate('Receive Notifications') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Receive Notifications') }}</span>
            </a>
        </li>
    </ul>
</li>
@endif
