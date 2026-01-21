<div class="d-flex flex-wrap justify-content-between align-items-center mb-5 mt-4 __gap-12px">
    <div class="js-nav-scroller hs-nav-scroller-horizontal mt-2">
        <!-- Nav -->
        <ul class="nav nav-tabs border-0 nav--tabs nav--pills">
            <li class="nav-item">
                <a class="nav-link  {{ Request::is('business-settings/mcvendor-setup') ?'active':'' }}" href="{{ route('admin.business-settings.mcvendor-setup') }}"   aria-disabled="true">{{translate('messages.business_information')}}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link  {{ Request::is('business-settings/mcvendor-setup/privacy-policy') ?'active':'' }}" href="{{ route('admin.business-settings.mcvendor-setup',  ['tab' => 'privacy-policy']) }}"   aria-disabled="true">{{translate('messages.Privacy Poclicy')}}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link  {{ Request::is('business-settings/mcvendor-setup/terms-and-conditions') ?'active':'' }}" href="{{ route('admin.business-settings.mcvendor-setup',  ['tab' => 'terms-and-conditions']) }}"   aria-disabled="true">{{translate('messages.Terms and Conditions')}}</a>
            </li>
        </ul>
        <!-- End Nav -->
    </div>
</div>
