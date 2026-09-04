{{-- Documentation menu, included by every admin sidebar so the SRS / API library is reachable
     whichever module panel the admin happens to be in.
     The /admin prefix is present in the request path on staging but stripped on the production
     admin host, so every check matches both spellings. --}}
@php
    $docActive = Request::is('documentation*', 'admin/documentation*');
    $apiActive = Request::is('api-endpoints*', 'admin/api-endpoints*');
@endphp
<li class="nav-item">
    <small class="nav-subtitle" title="{{ translate('Documentation') }}">{{ translate('Documentation') }}</small>
    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
</li>
<li class="navbar-vertical-aside-has-menu {{ $docActive ? 'active' : '' }}">
    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
        title="{{ translate('Documentation') }}">
        <i class="tio-book nav-icon"></i>
        <span
            class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('Documentation') }}</span>
    </a>
    <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display:{{ $docActive ? 'block' : 'none' }}">
        <li class="nav-item {{ Request::is('documentation', 'admin/documentation') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.documentation.index') }}"
                title="{{ translate('All Documents') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('All Documents') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('documentation/create', 'admin/documentation/create') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.documentation.create') }}"
                title="{{ translate('New Document') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('New Document') }}</span>
            </a>
        </li>
        <li
            class="nav-item {{ Request::is('documentation/categories', 'admin/documentation/categories') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.documentation.categories') }}"
                title="{{ translate('Categories') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Categories') }}</span>
            </a>
        </li>
    </ul>
</li>
<li class="navbar-vertical-aside-has-menu {{ $apiActive ? 'active' : '' }}">
    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
        title="{{ translate('API Endpoints') }}">
        <i class="tio-code nav-icon"></i>
        <span
            class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('API Endpoints') }}</span>
    </a>
    <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display:{{ $apiActive ? 'block' : 'none' }}">
        <li class="nav-item {{ Request::is('api-endpoints', 'admin/api-endpoints') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.api-endpoints.index') }}" title="{{ translate('Projects') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Projects') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('api-endpoints/all', 'admin/api-endpoints/all') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.api-endpoints.all') }}"
                title="{{ translate('All Endpoints') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('All Endpoints') }}</span>
            </a>
        </li>
    </ul>
</li>
