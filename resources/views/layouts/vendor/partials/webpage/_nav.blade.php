<style>
    .tab_item {
        padding: 10px 20px !important;
        border-radius: 10px !important;
        font-weight: 500;
        font-size: 16px;
        border: 1px solid #cecece !important;
        margin: 5px !important;
        white-space: nowrap;
    }

    .tab_item.active {
        background: var(--primary) !important;
        color: white !important;
    }

    @media (max-width: 768px) {
        .webpage-nav {
            flex-wrap: wrap !important;
            gap: 4px;
            border-bottom: none;
            padding-bottom: 4px;
        }

        .webpage-nav .nav-item {
            flex: 0 0 auto;
        }

        .webpage-nav .tab_item {
            padding: 5px 12px !important;
            font-size: 12px !important;
            margin: 0 !important;
            border-radius: 20px !important;
        }
    }
</style>
<ul class="nav nav-tabs mb-3 mx-2 webpage-nav">

    <li class="nav-item">
        <a class="tab_item nav-link {{ Request::is('settings/webpage/basic-info') || Request::is('store-panel/settings/webpage') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'basic-info']) }}">
            Basic Info
        </a>
    </li>
   
    <li class="nav-item">
        <a class=" tab_item nav-link {{ Request::is('settings/webpage/webpage-templates') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'webpage-templates']) }}">
            Webpage Templates
        </a>
    </li>
    <li class="nav-item">
        <a class="tab_item nav-link {{ Request::is('settings/webpage/banners') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'banners']) }}">
            Banners
        </a>
    </li>
    <li class="nav-item">
        <a class=" tab_item nav-link {{ Request::is('settings/webpage/about-us') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'about-us']) }}">
            About Us
        </a>
    </li>
    <li class="nav-item">
        <a class="tab_item nav-link {{ Request::is('settings/webpage/gallery') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'gallery']) }}">
            Gallery
        </a>
    </li>

    <li class="nav-item">
        <a class=" tab_item nav-link {{ Request::is('settings/webpage/tnc') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'tnc']) }}">
            T & C
        </a>
    </li>

    <li class="nav-item">
        <a class=" tab_item nav-link {{ Request::is('settings/webpage/privacy-policy') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'privacy-policy']) }}">
            Privacy Policy
        </a>
    </li>

    <li class="nav-item">
        <a class=" tab_item nav-link {{ Request::is('settings/webpage/domain-setup') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'domain-setup']) }}">
           Domain Setup
        </a>
    </li>
    <li class="nav-item">
        <a class=" tab_item nav-link {{ Request::is('settings/webpage/third-party') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'third-party']) }}">
           Third Party
        </a>
    </li>

</ul>
