<style>
    .tab_item {

        padding: 10px 20px !important;
        border-radius: 10px !important;
    }

    .tab_item.active {
        background: var(--primary) !important;
        color: white !important;
    }
</style>
<ul class="nav nav-tabs mb-3">

    <li class="nav-item">
        <a class="tab_item nav-link {{ Request::is('store-panel/settings/webpage/basic-info') || Request::is('store-panel/settings/webpage') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'basic-info']) }}">
            Basic Info
        </a>
    </li>
    <li class="nav-item">
        <a class=" tab_item nav-link {{ Request::is('store-panel/settings/webpage/mychitti-services') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'mychitti-services']) }}">
            Mychitti Services
        </a>
    </li>
    <li class="nav-item">
        <a class=" tab_item nav-link {{ Request::is('store-panel/settings/webpage/my-services') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'my-services']) }}">
            My Services
        </a>
    </li> 
    <li class="nav-item">
        <a class=" tab_item nav-link {{ Request::is('store-panel/settings/webpage/webpage-templates') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'webpage-templates']) }}">
            Webpage Templates
        </a>
    </li>
    <li class="nav-item">
        <a class="tab_item nav-link {{ Request::is('store-panel/settings/webpage/banners') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'banners']) }}">
            Banners
        </a>
    </li>
    <li class="nav-item">
        <a class=" tab_item nav-link {{ Request::is('store-panel/settings/webpage/about-us') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'about-us']) }}">
            About Us
        </a>
    </li>
    <li class="nav-item">
        <a class="tab_item nav-link {{ Request::is('store-panel/settings/webpage/gallery') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'gallery']) }}">
            Gallery
        </a>
    </li>

    <li class="nav-item">
        <a class=" tab_item nav-link {{ Request::is('store-panel/settings/webpage/tnc') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'tnc']) }}">
            T & C
        </a>
    </li>

    <li class="nav-item">
        <a class=" tab_item nav-link {{ Request::is('store-panel/settings/webpage/privacy-policy') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'privacy-policy']) }}">
            Privacy Policy
        </a>
    </li>

    <li class="nav-item">
        <a class=" tab_item nav-link {{ Request::is('store-panel/settings/webpage/domain-setup') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'domain-setup']) }}">
           Domain Setup
        </a>
    </li>
    <li class="nav-item">
        <a class=" tab_item nav-link {{ Request::is('store-panel/settings/webpage/third-party') ? 'active' : '' }}"
            href="{{ route('vendor.settings.webpage', ['tab' => 'third-party']) }}">
           Third Party
        </a>
    </li>

</ul>
