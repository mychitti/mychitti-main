<div id="sidebarMain" class="d-none">
    <aside class="js-navbar-vertical-aside navbar navbar-vertical-aside navbar-vertical navbar-vertical-fixed navbar-expand-xl navbar-bordered  ">
        <div class="navbar-vertical-container">
            <div class="navbar-brand-wrapper justify-content-between">
                <!-- Logo -->
                @php($store_logo = \App\Models\BusinessSetting::where(['key' => 'logo'])->first()->value)
                <a class="navbar-brand" href="{{ route('admin.dashboard') }}" aria-label="Front">
                       <img class="navbar-brand-logo initial--36 onerror-image onerror-image" data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                    src="{{\App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/app/public/business/').'/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg') ,'business/' )}}"
                    alt="Logo">
                    <img class="navbar-brand-logo-mini initial--36 onerror-image onerror-image" data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                    src="{{\App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/app/public/business/').'/' . $store_logo, asset('public/assets/admin/img/160x160/img2.jpg') ,'business/' )}}"
                    alt="Logo">
                </a>
                <!-- End Logo -->

                <!-- Navbar Vertical Toggle -->
                <button type="button" class="js-navbar-vertical-aside-toggle-invoker navbar-vertical-aside-toggle btn btn-icon btn-xs btn-ghost-dark">
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
                        <input autocomplete="false" name="qq" type="text" class="form-control form--control" placeholder="{{ translate('Search Menu...') }}" id="search">
                        <div id="search-suggestions" class="flex-wrap mt-1"></div>
                    </div>
                </form>
                <ul class="navbar-nav navbar-nav-lg nav-tabs">

                    @if (\App\CentralLogics\Helpers::module_permission_check('sales_crm'))

                    <!-- Pipeline -->
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/sales-crm/pipeline*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.sales-crm.pipeline') }}" title="{{ translate('Pipeline') }}">
                            <i class="tio-format-bullets nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{ translate('Pipeline') }}
                            </span>
                        </a>
                    </li>

                    <!-- Sales Queries -->
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/sales-crm/queries*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.sales-crm.query.index') }}" title="{{ translate('Sales Queries') }}">
                            <i class="tio-chat-outlined nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{ translate('Sales Queries') }}
                            </span>
                        </a>
                    </li>

                    <!-- Follow-ups -->
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/sales-crm/followups') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.sales-crm.followup.index') }}" title="{{ translate('Follow-ups') }}">
                            <i class="tio-calendar nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{ translate('Follow-ups') }}
                            </span>
                        </a>
                    </li>

                    <!-- My Follow-ups -->
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/sales-crm/followups/my*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.sales-crm.followup.my') }}" title="{{ translate('My Follow-ups') }}">
                            <i class="tio-user nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{ translate('My Follow-ups') }}
                            </span>
                        </a>
                    </li>

                    <!-- Support Tickets -->
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/sales-crm/tickets*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.sales-crm.ticket.index') }}" title="{{ translate('Support Tickets') }}">
                            <i class="tio-help-outlined nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{ translate('Support Tickets') }}
                            </span>
                        </a>
                    </li>

                    <!-- Reports section -->
                    <li class="nav-item">
                        <small class="nav-subtitle" title="{{ translate('Reports') }}">{{ translate('Reports') }}</small>
                        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li>

                    <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/sales-crm/reports/zone*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.sales-crm.report.zone') }}" title="{{ translate('Zone Performance') }}">
                            <i class="tio-chart-bar-4 nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{ translate('Zone Performance') }}
                            </span>
                        </a>
                    </li>


                    @endif
                    <!-- End Sales CRM Section -->

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
                                    data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}"
                                    src="{{\App\CentralLogics\Helpers::onerror_image_helper(auth('admin')->user()->image, asset('storage/app/public/admin/').'/'.auth('admin')->user()->image, asset('public/assets/admin/img/160x160/img1.jpg') ,'admin/')}}"
                                    alt="Image Description">
                                    <span class="avatar-status avatar-sm-status avatar-status-success"></span>
                                </div>
                                <div class="media-body pl-3">
                                    <span class="card-title h5">
                                        {{auth('admin')->user()->f_name}}
                                        {{auth('admin')->user()->l_name}}
                                    </span>
                                    <span class="card-text">{{auth('admin')->user()->email}}</span>
                                </div>
                            </div>
                        </a>

                        <div id="accountNavbarDropdown"
                                class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-right navbar-dropdown-menu navbar-dropdown-account min--240">
                            <div class="dropdown-item-text">
                                <div class="media align-items-center">
                                    <div class="avatar avatar-sm avatar-circle mr-2">
                                        <img class="avatar-img onerror-image"
                                    data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}"
                                    src="{{\App\CentralLogics\Helpers::onerror_image_helper(auth('admin')->user()->image, asset('storage/app/public/admin/').'/'.auth('admin')->user()->image, asset('public/assets/admin/img/160x160/img1.jpg') ,'admin/')}}"
                                    alt="Image Description">
                                    </div>
                                    <div class="media-body">
                                        <span class="card-title h5">{{auth('admin')->user()->f_name}}</span>
                                        <span class="card-text">{{auth('admin')->user()->email}}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="dropdown-divider"></div>

                            <a class="dropdown-item" href="{{route('admin.settings')}}">
                                <span class="text-truncate pr-2" title="Settings">{{translate('messages.settings')}}</span>
                            </a>

                            <div class="dropdown-divider"></div>

                           <a class="dropdown-item log-out" href="javascript:">
                                <span class="text-truncate pr-2" title="Sign out">{{translate('messages.sign_out')}}</span>
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
    $(window).on('load' , function() {
        if($(".navbar-vertical-content li.active").length) {
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
                if (typeof suggestions !== 'undefined') {
                    suggestions.forEach(suggestion => {
                        if (suggestion.toLowerCase().includes(val.toLowerCase())) {
                            $suggestionsList.append(
                                `<span class="search-suggestion badge badge-soft-light m-1 fs-14">${suggestion}</span>`
                            );
                        }
                    });
                }
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
