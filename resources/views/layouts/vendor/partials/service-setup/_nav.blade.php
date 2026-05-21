<style>
    .tab_item {

        padding: 10px 20px !important;
        border-radius: 10px !important;
        font-weight: 500;
    font-size: 16px;
        border: 1px solid #cecece !important;
        margin: 5px  !important;
    }

    .tab_item.active {
        background: var(--primary) !important;
        color: white !important;
    }
</style>
<ul class="nav nav-tabs mb-3 mx-2">

   
    <li class="nav-item">
        <a class=" tab_item nav-link {{ Request::is('settings/service-setup/mychitti-services') || Request::is('store-panel/settings/service-setup') ? 'active' : '' }}"
            href="{{ route('vendor.settings.service-setup', ['tab' => 'mychitti-services']) }}">
            Mychitti Services
        </a>
    </li>
    <li class="nav-item">
        <a class=" tab_item nav-link {{ Request::is('settings/service-setup/my-services') ? 'active' : '' }}"
            href="{{ route('vendor.settings.service-setup', ['tab' => 'my-services']) }}">
            My Services
        </a>
    </li> 
   
</ul>
