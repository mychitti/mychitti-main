<div id="sidebarMain" class="d-none">
    <aside
        class="js-navbar-vertical-aside navbar navbar-vertical-aside navbar-vertical navbar-vertical-fixed navbar-expand-xl navbar-bordered  ">
        <div class="navbar-vertical-container">
            <div class="navbar-brand-wrapper justify-content-between">
                <!-- Logo -->
                @php($store_logo = \App\Models\BusinessSetting::where(['key' => 'logo'])->first()->value)
                <a class="navbar-brand" href="{{ route('admin.dashboard') }}" aria-label="Front">
                    <img class="navbar-brand-logo initial--36 onerror-image onerror-image"
                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/app/public/business/') . '/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'business/') }}"
                        alt="Logo">
                    <img class="navbar-brand-logo-mini initial--36 onerror-image onerror-image"
                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/app/public/business/') . '/' . $store_logo, asset('public/assets/admin/img/160x160/img2.jpg'), 'business/') }}"
                        alt="Logo">
                </a>
                <!-- End Logo -->

                <!-- Navbar Vertical Toggle -->
                <button type="button"
                    class="js-navbar-vertical-aside-toggle-invoker navbar-vertical-aside-toggle btn btn-icon btn-xs btn-ghost-dark">
                    <i class="tio-clear tio-lg"></i>
                </button>
                <!-- End Navbar Vertical Toggle -->

                <div class="navbar-nav-wrap-content-left">
                    <!-- Navbar Vertical Toggle -->
                    <button type="button" class="js-navbar-vertical-aside-toggle-invoker close">
                        <i class="tio-first-page navbar-vertical-aside-toggle-short-align" data-toggle="tooltip"
                            data-placement="right" title="Collapse"></i>
                        <i class="tio-last-page navbar-vertical-aside-toggle-full-align"
                            data-template='<div class="tooltip d-none d-sm-block" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>'></i>
                    </button>
                    <!-- End Navbar Vertical Toggle -->
                </div>

            </div> 

            <!-- Content -->
            <div class="navbar-vertical-content bg-white" id="navbar-vertical-content">
                <form autocomplete="off" class="sidebar--search-form">
                    <div class="search--form-group">
                        <button type="button" class="btn"><i class="tio-search"></i></button>
                        <input autocomplete="false" name="qq" type="text" class="form-control form--control"
                            placeholder="{{ translate('Search Menu...') }}" id="search">

                        <div id="search-suggestions" class="flex-wrap mt-1"></div>
                    </div>
                </form>
                <ul class="navbar-nav navbar-nav-lg nav-tabs">
                    <!-- Dashboards -->
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('/') ? 'show active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                            href="{{ route('admin.common-dashboard') }}" title="{{ translate('messages.dashboard') }}">
                            <i class="tio-home-vs-1-outlined nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{ translate('messages.dashboard') }}
                            </span>
                        </a>
                    </li>


                    @if (\App\CentralLogics\Helpers::permission_check('account_manage'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('account*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="Leads">
                                <i class="tio-money-vs nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Account
                                    Management</span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('account*') ? 'block' : 'none' }}">

                                <li class="nav-item {{ Request::is('account/report') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.account.report') }}" title="Report">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Report</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('account/add') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.account.add') }}"
                                        title="{{ translate('messages.add') }} {{ translate('messages.new') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.add') }}
                                            {{ translate('messages.new') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('account/list') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.account.list') }}"
                                        title="Lead {{ translate('messages.list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.list') }}</span>
                                    </a>
                                </li>

                            </ul>
                        </li>
                    @endif


                    <li class="navbar-vertical-aside-has-menu {{ Request::is('services-billing*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                            title="blog">
                            <i class="tio-pages nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Services
                                Billing</span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                            style="display:{{ Request::is('services-billing*') ? 'block' : 'none' }}">

                            <li class="nav-item {{ Request::is('services-billing') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.modules-billing.index') }}"
                                    title="Configuration">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Configuration</span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('services-billing/store-billing') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.modules-billing.store-billing') }}"
                                    title="Stores Billing">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Stores Billing</span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('services-billing/history') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.modules-billing.history') }}"
                                    title="Free Trial History">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Free Trial History</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('google-ads') ? 'show active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                            href="{{ route('admin.common-dashboard.google-ads') }}" title="Google Ads">
                            <i class="tio-launch-outlined nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                Google Ads
                            </span>
                        </a>
                    </li>


                    <li class="nav-item">
                        <small class="nav-subtitle"
                            title="{{ translate('Promotion Management') }}">{{ translate('Promotion Management') }}</small>
                        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li>
                    <!-- Campaign -->

                    @if (\App\CentralLogics\Helpers::module_permission_check('blog'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('blog*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="blog">
                                <i class="tio-layers-outlined nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.blog') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display:{{ Request::is('campaign*') ? 'block' : 'none' }}">

                                <li class="nav-item {{ Request::is('blog/category*') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.blog.category') }}" title="category">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Blog Category</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('blog/list*') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.blog.list') }}" title="List">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Blog Posts</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('blog/add-new*') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.blog.add-new') }}" title="Add New">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Add New Post</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                    <!-- Banner -->

                    <!-- End Banner -->

                    @if (\App\CentralLogics\Helpers::module_permission_check('settings'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('business-settings/pages*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.pages_setup') }}">
                                <i class="tio-pages nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.pages_&_social_media') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display:{{ Request::is('business-settings/pages*') ? 'block' : 'none' }}">

                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('business-settings/pages/homepage') ? 'active' : '' }}">
                                    <a class="nav-link "
                                        href="{{ route('admin.business-settings.homepage-config') }}"
                                        title="{{ translate('messages.Homepage') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Homepage</span>
                                    </a>
                                </li>
                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('business-settings/pages/social-media') ? 'active' : '' }}">
                                    <a class="nav-link "
                                        href="{{ route('admin.business-settings.social-media.index') }}"
                                        title="{{ translate('messages.Social Media') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.Social Media') }}</span>
                                    </a>
                                </li>

                                {{-- <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('business-settings/pages/admin-landing-page-settings*') ? 'active' : '' }}">
                                    <a class="nav-link "
                                        href="{{ route('admin.business-settings.admin-landing-page-settings.get', 'fixed-data') }}"
                                        title="{{ translate('messages.admin_landing_page_settings') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate">{{ translate('messages.admin_landing_page') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('business-settings/pages/react-landing-page-settings*') ? 'active' : '' }}">
                                    <a class="nav-link "
                                        href="{{ route('admin.business-settings.react-landing-page-settings.get', 'header') }}"
                                        title="{{ translate('messages.react_landing_page') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate">{{ translate('messages.react_landing_page') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('business-settings/pages/flutter-landing-page-settings*') ? 'active' : '' }}">
                                    <a class="nav-link "
                                        href="{{ route('admin.business-settings.flutter-landing-page-settings.get', 'fixed-data') }}"
                                        title="{{ translate('messages.flutter_landing_page') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate">{{ translate('messages.flutter_landing_page') }}</span>
                                    </a>
                                </li> --}}

                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('business-settings/pages/business-page*') ? 'active' : '' }}">
                                    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                        href="javascript:" title="{{ translate('messages.business_pages') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.business_pages') }}</span>
                                    </a>
                                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                        style="display:{{ Request::is('business-settings/pages/business-page*') ? 'block' : 'none' }}">
                                        <li
                                            class="nav-item {{ Request::is('business-settings/pages/business-page/faq') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('admin.business-settings.faq') }}"
                                                title="FAQs">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">FAQs</span>
                                            </a>
                                        </li>

                                        <li
                                            class="nav-item {{ Request::is('business-settings/pages/business-page/terms-and-conditions') ? 'active' : '' }}">
                                            <a class="nav-link "
                                                href="{{ route('admin.business-settings.terms-and-conditions') }}"
                                                title="{{ translate('messages.terms_and_condition') }}">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span
                                                    class="text-truncate">{{ translate('messages.terms_and_condition') }}</span>
                                            </a>
                                        </li>

                                        <li
                                            class="nav-item {{ Request::is('business-settings/pages/business-page/privacy-policy') ? 'active' : '' }}">
                                            <a class="nav-link "
                                                href="{{ route('admin.business-settings.privacy-policy') }}"
                                                title="{{ translate('messages.privacy_policy') }}">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span
                                                    class="text-truncate">{{ translate('messages.privacy_policy') }}</span>
                                            </a>
                                        </li>

                                        <li
                                            class="nav-item {{ Request::is('business-settings/pages/business-page/about-us') ? 'active' : '' }}">
                                            <a class="nav-link "
                                                href="{{ route('admin.business-settings.about-us') }}"
                                                title="{{ translate('messages.about_us') }}">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span
                                                    class="text-truncate">{{ translate('messages.about_us') }}</span>
                                            </a>
                                        </li>
                                        <li
                                            class="nav-item {{ Request::is('business-settings/pages/business-page/refund') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('admin.business-settings.refund') }}"
                                                title="{{ translate('messages.Refund Policy') }}">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">{{ translate('Refund Policy') }}</span>
                                            </a>
                                        </li>

                                        <li
                                            class="nav-item {{ Request::is('business-settings/pages/business-page/cancelation') ? 'active' : '' }}">
                                            <a class="nav-link "
                                                href="{{ route('admin.business-settings.cancelation') }}"
                                                title="{{ translate('messages.Cancelation Policy') }}">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span
                                                    class="text-truncate">{{ translate('Cancelation Policy') }}</span>
                                            </a>
                                        </li>

                                        <li
                                            class="nav-item {{ Request::is('business-settings/pages/business-page/shipping-policy') ? 'active' : '' }}">
                                            <a class="nav-link "
                                                href="{{ route('admin.business-settings.shipping-policy') }}"
                                                title="{{ translate('messages.shipping_policy') }}">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">{{ translate('Shipping Policy') }}</span>
                                            </a>
                                        </li>
                                        <li
                                            class="nav-item {{ Request::is('business-settings/pages/business-page/disclaimer') ? 'active' : '' }}">
                                            <a class="nav-link "
                                                href="{{ route('admin.business-settings.disclaimer') }}"
                                                title="disclaimer">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Disclaimer</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>

                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('business-settings/pages/vendor-modules') ? 'active' : '' }}">
                                    <a class="nav-link "
                                        href="{{ route('admin.business-settings.vendor-modules') }}"
                                        title="{{ translate('messages.vendor_modules') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.vendor_modules') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('business-settings/pages/vendor-modules') ? 'active' : '' }}">
                                    <a class="nav-link "
                                        href="{{ route('admin.business-settings.vendor-homepage-config') }}"
                                        title="{{ translate('messages.vendor_homepage_config') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.vendor_homepage_config') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                    {{-- file  --}}
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('file/add') ? 'show active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                            href="{{ route('admin.file.add') }}" title="Upload Files">
                            <i class="tio-file-add-outlined"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                               Upload Files
                            </span>
                        </a>
                    </li>


                    <li class="__sidebar-hs-unfold px-2" id="tourb-9">
                        <div class="hs-unfold w-100">
                            <a class="js-hs-unfold-invoker navbar-dropdown-account-wrapper" href="javascript:;"
                                data-hs-unfold-options='{
                                    "target": "#accountNavbarDropdown",
                                    "type": "css-animation"
                                }'>
                                <div class="cmn--media right-dropdown-icon d-flex align-items-center">
                                    <div class="avatar avatar-sm avatar-circle">
                                        <img class="avatar-img onerror-image"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper(auth('admin')->user()->image, asset('storage/app/public/admin/') . '/' . auth('admin')->user()->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'admin/') }}"
                                            alt="Image Description">
                                        <span class="avatar-status avatar-sm-status avatar-status-success"></span>
                                    </div>
                                    <div class="media-body pl-3">
                                        <span class="card-title h5">
                                            {{ auth('admin')->user()->f_name }}
                                            {{ auth('admin')->user()->l_name }}
                                        </span>
                                        <span class="card-text">{{ auth('admin')->user()->email }}</span>
                                    </div>
                                </div>
                            </a>

                            <div id="accountNavbarDropdown"
                                class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-right navbar-dropdown-menu navbar-dropdown-account min--240">
                                <div class="dropdown-item-text">
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-sm avatar-circle mr-2">
                                            <img class="avatar-img onerror-image"
                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper(auth('admin')->user()->image, asset('storage/app/public/admin/') . '/' . auth('admin')->user()->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'admin/') }}"
                                                alt="Image Description">
                                        </div>
                                        <div class="media-body">
                                            <span class="card-title h5">{{ auth('admin')->user()->f_name }}</span>
                                            <span class="card-text">{{ auth('admin')->user()->email }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="dropdown-divider"></div>

                                <a class="dropdown-item" href="{{ route('admin.settings') }}">
                                    <span class="text-truncate pr-2"
                                        title="Settings">{{ translate('messages.settings') }}</span>
                                </a>

                                <div class="dropdown-divider"></div>

                                <a class="dropdown-item log-out" href="javascript:">
                                    <span class="text-truncate pr-2"
                                        title="Sign out">{{ translate('messages.sign_out') }}</span>
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <!-- End Content -->
        </div>
    </aside>
</div>

<div id="sidebarCompact" class="d-none">

</div>


@push('script_2')
    <script>
        $(window).on('load', function() {
            if ($(".navbar-vertical-content li.active").length) {
                $('.navbar-vertical-content').animate({
                    scrollTop: $(".navbar-vertical-content li.active").offset().top - 150
                }, 10);
            }
        });

        var $rows = $('#navbar-vertical-content li');
        $('#search-sidebar-menu').keyup(function() {
            var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();

            $rows.show().filter(function() {
                var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
                return !~text.indexOf(val);
            }).hide();
        });

        $(document).ready(function() {
            const $searchInput = $('#search');
            const $suggestionsList = $('#search-suggestions');
            const $rows = $('#navbar-vertical-content li');
            const $subrows = $('#navbar-vertical-content li ul li');
            {{-- const suggestions = ['{{strtolower(translate('messages.order'))  }}', '{{ strtolower(translate('messages.campaign'))  }}', '{{ strtolower(translate('messages.category')) }}', '{{ strtolower(translate('messages.product')) }}','{{ strtolower(translate('messages.store')) }}' ]; --}}
            const focusInput = () => updateSuggestions($searchInput.val());
            const hideSuggestions = () => $suggestionsList.slideUp(700);
            const showSuggestions = () => $suggestionsList.slideDown(700);
            let clickSuggestion = function() {
                let suggestionText = $(this).text();
                $searchInput.val(suggestionText);
                hideSuggestions();
                filterItems(suggestionText.toLowerCase());
                updateSuggestions(suggestionText);
            };
            let filterItems = (val) => {
                let unmatchedItems = $rows.show().filter((index, element) => !~$(element).text().replace(
                    /\s+/g, ' ').toLowerCase().indexOf(val));
                let matchedItems = $rows.show().filter((index, element) => ~$(element).text().replace(/\s+/g,
                    ' ').toLowerCase().indexOf(val));
                unmatchedItems.hide();
                matchedItems.each(function() {
                    let $submenu = $(this).find($subrows);
                    let keywordCountInRows = 0;
                    $rows.each(function() {
                        let rowText = $(this).text().toLowerCase();
                        let valLower = val.toLowerCase();
                        let keywordCountRow = rowText.split(valLower).length - 1;
                        keywordCountInRows += keywordCountRow;
                    });
                    if ($submenu.length > 0) {
                        $subrows.show();
                        $submenu.each(function() {
                            let $submenu2 = !~$(this).text().replace(/\s+/g, ' ')
                                .toLowerCase().indexOf(val);
                            if ($submenu2 && keywordCountInRows <= 2) {
                                $(this).hide();
                            }
                        });
                    }
                });
            };
            let updateSuggestions = (val) => {
                $suggestionsList.empty();
                suggestions.forEach(suggestion => {
                    if (suggestion.toLowerCase().includes(val.toLowerCase())) {
                        $suggestionsList.append(
                            `<span class="search-suggestion badge badge-soft-light m-1 fs-14">${suggestion}</span>`
                        );
                    }
                });
                // showSuggestions();
            };
            $searchInput.focus(focusInput);
            $searchInput.on('input', function() {
                updateSuggestions($(this).val());
            });
            $suggestionsList.on('click', '.search-suggestion', clickSuggestion);
            $searchInput.keyup(function() {
                filterItems($(this).val().toLowerCase());
            });
            $searchInput.on('focusout', hideSuggestions);
            $searchInput.on('focus', showSuggestions);
        });
    </script>
@endpush
