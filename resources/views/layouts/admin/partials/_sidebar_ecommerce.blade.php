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
            {{-- bg--005555 --}}
            <div class="navbar-vertical-content bg-white" id="navbar-vertical-content">
                <form autocomplete="off"   class="sidebar--search-form">
                    <div class="search--form-group">
                        <button type="button" class="btn"><i class="tio-search"></i></button>
                        <input  autocomplete="false" name="qq" type="text" class="form-control form--control" placeholder="{{ translate('Search Menu...') }}" id="search">

                        <div id="search-suggestions" class="flex-wrap mt-1"></div>
                    </div>
                </form>


                <ul class="navbar-nav navbar-nav-lg nav-tabs">
                    <!-- Dashboards -->
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('/') ? 'show active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.dashboard') }}?module_id={{Config::get('module.current_module_id')}}" title="{{ translate('messages.dashboard') }}">
                            <i class="tio-home-vs-1-outlined nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{ translate('messages.dashboard') }}
                            </span>
                        </a>
                    </li>
                    <!-- End Dashboards -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('billing'))
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('billing') ? 'show active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.billing.index') }}" title="{{ translate('messages.dashboard') }}">
                            <i class="tio-home-vs-1-outlined nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                Billing
                            </span>
                        </a>
                    </li>
                    @endif

                    @if (\App\CentralLogics\Helpers::permission_check('account_manage'))
                    <!--<li class="nav-item">-->
                    <!--    <small class="nav-subtitle" title="Accounts Management">Accounts Management</small>-->
                    <!--    <small class="tio-more-horizontal nav-subtitle-replacer"></small>-->
                    <!--</li>-->

                    <li class="navbar-vertical-aside-has-menu {{ Request::is('account*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="Leads">
                            <i class="tio-money-vs nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Account Management</span>
                        </a>

                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{ Request::is('account*') ? 'block' : 'none' }}">
                           
                            <li class="nav-item {{ Request::is('account/report') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.account.report') }}" title="Report">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Report</span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('account/add') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.account.add') }}" title="{{ translate('messages.add') }} {{ translate('messages.new') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">{{ translate('messages.add') }}
                                        {{ translate('messages.new') }}</span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('account/list') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.account.list') }}" title="Lead {{ translate('messages.list') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">{{ translate('messages.list') }}</span>
                                </a>
                            </li>

                        </ul>
                    </li>
                    @endif

                    <!-- Marketing section -->
                    <li class="nav-item">
                        <small class="nav-subtitle" title="{{ translate('messages.employee_handle') }}">{{ translate('pos section') }}</small>
                        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li>
                    <!-- Pos -->
                    @if(\App\CentralLogics\Helpers::module_permission_check('pos'))
                    <li class="navbar-vertical-aside-has-menu {{Request::is('pos*')?'active':''}}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link " href="{{route('admin.pos.index')}}" title="{{translate('New Sale')}}">
                            <i class="tio-shopping-basket-outlined nav-icon"></i>
                            <span class="text-truncate">{{translate('New Sale')}}</span>
                        </a>
                    </li>
                    @endif
                    <!-- Pos -->

                                        <!-- Orders -->
                                       
                    @if (\App\CentralLogics\Helpers::module_permission_check('order'))
                    <li class="nav-item">
                        <small class="nav-subtitle">{{ translate('messages.order_management') }}</small>
                        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li>

                    <li class="navbar-vertical-aside-has-menu {{ Request::is('order') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" title="{{ translate('messages.orders') }}">
                            <i class="tio-shopping-cart nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{ translate('messages.orders') }}
                            </span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display:{{ Request::is('order*') ? 'block' : 'none' }}">
                            <li class="nav-item {{ Request::is('order/list/all') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('admin.order.list', ['all']) }}" title="{{ translate('messages.all_orders') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        {{ translate('messages.all') }}
                                        <span class="badge badge-soft-info badge-pill ml-1">
                                            {{ \App\Models\Order::StoreOrder()->module(Config::get('module.current_module_id'))->count() }}
                                        </span> 
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('order/list/scheduled') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('admin.order.list', ['scheduled']) }}" title="{{ translate('messages.scheduled_orders') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        {{ translate('messages.scheduled') }}
                                        <span class="badge badge-soft-info badge-pill ml-1">
                                            {{ \App\Models\Order::Scheduled()->StoreOrder()->module(Config::get('module.current_module_id'))->count() }}
                                        </span>
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('order/list/pending') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.order.list', ['pending']) }}" title="{{ translate('messages.pending_orders') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        {{ translate('messages.pending') }}
                                        <span class="badge badge-soft-info badge-pill ml-1">
                                            {{ \App\Models\Order::Pending()->OrderScheduledIn(30)->StoreOrder()->module(Config::get('module.current_module_id'))->count() }}
                                        </span>
                                    </span>
                                </a>
                            </li>

                            <li class="nav-item {{ Request::is('order/list/accepted') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.order.list', ['accepted']) }}" title="{{ translate('messages.accepted_orders') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        {{ translate('messages.accepted') }}
                                        <span class="badge badge-soft-success badge-pill ml-1">
                                            {{ \App\Models\Order::AccepteByDeliveryman()->OrderScheduledIn(30)->StoreOrder()->module(Config::get('module.current_module_id'))->count() }}
                                        </span>
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('order/list/processing') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.order.list', ['processing']) }}" title="{{ translate('messages.processing_orders') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        {{ translate('messages.processing') }}
                                        <span class="badge badge-soft-warning badge-pill ml-1">
                                            {{ \App\Models\Order::Preparing()->OrderScheduledIn(30)->StoreOrder()->module(Config::get('module.current_module_id'))->count() }}
                                        </span>
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('order/list/item_on_the_way') ? 'active' : '' }}">
                                <a class="nav-link text-capitalize" href="{{ route('admin.order.list', ['item_on_the_way']) }}" title="{{ translate('messages.order_on_the_way') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        {{ translate('messages.order_on_the_way') }}
                                        <span class="badge badge-soft-warning badge-pill ml-1">
                                            {{ \App\Models\Order::ItemOnTheWay()->OrderScheduledIn(30)->StoreOrder()->module(Config::get('module.current_module_id'))->count() }}
                                        </span>
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('order/list/delivered') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.order.list', ['delivered']) }}" title="{{ translate('messages.delivered_orders') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        {{ translate('messages.delivered') }}
                                        <span class="badge badge-soft-success badge-pill ml-1">
                                            {{ \App\Models\Order::Delivered()->StoreOrder()->module(Config::get('module.current_module_id'))->count() }}
                                        </span>
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('order/list/canceled') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.order.list', ['canceled']) }}" title="{{ translate('messages.canceled_orders') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        {{ translate('messages.canceled') }}
                                        <span class="badge badge-soft-warning bg-light badge-pill ml-1">
                                            {{ \App\Models\Order::Canceled()->StoreOrder()->module(Config::get('module.current_module_id'))->count() }}
                                        </span>
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('order/list/failed') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.order.list', ['failed']) }}" title="{{ translate('messages.payment_failed_orders') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container text-capitalize">
                                        {{ translate('messages.payment_failed') }}
                                        <span class="badge badge-soft-danger bg-light badge-pill ml-1">
                                            {{ \App\Models\Order::failed()->StoreOrder()->module(Config::get('module.current_module_id'))->count() }}
                                        </span>
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('order/list/refunded') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.order.list', ['refunded']) }}" title="{{ translate('messages.refunded_orders') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        {{ translate('messages.refunded') }}
                                        <span class="badge badge-soft-danger bg-light badge-pill ml-1">
                                            {{ \App\Models\Order::Refunded()->StoreOrder()->module(Config::get('module.current_module_id'))->count() }}
                                        </span>
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('order/offline/payment/list*') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.order.offline_verification_list', ['all']) }}" title="{{ translate('Offline_Payments') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        {{ translate('messages.Offline_Payments') }}
                                        <span class="badge badge-soft-danger bg-light badge-pill ml-1">
                                            {{ \App\Models\Order::has('offline_payments')->StoreOrder()->module(Config::get('module.current_module_id'))->count() }}
                                        </span>
                                    </span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Order refund -->
                    <li
                    class="navbar-vertical-aside-has-menu {{ Request::is('refund/*') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                        title="{{ translate('Order Refunds') }}">
                        <i class="tio-receipt nav-icon"></i>
                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                            {{ translate('Order Refunds') }}
                        </span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                        style="display: {{ Request::is('refund*') ? 'block' : 'none' }}">

                        <li class="nav-item {{ Request::is('refund/requested') ||  Request::is('refund/rejected') ||Request::is('refund/refunded') ? 'active' : '' }}">
                            <a class="nav-link "
                                href="{{ route('admin.refund.refund_attr', ['requested']) }}"
                                title="{{ translate('Refund Requests') }} ">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate sidebar--badge-container">
                                    {{ translate('Refund Requests') }}
                                    <span class="badge badge-soft-danger badge-pill ml-1">
                                        {{ \App\Models\Order::Refund_requested()->StoreOrder()->module(Config::get('module.current_module_id'))->count() }}
                                    </span>
                                </span>
                            </a>
                        </li>


                    </ul>
                    </li>
                    <!-- Order refund End-->
                                        <!-- Attributes -->
                    {{-- @if (\App\CentralLogics\Helpers::module_permission_check('attribute')) --}}
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('flash-sale*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.flash-sale.add-new') }}" title="{{ translate('messages.flash_sales') }}">
                            <i class="tio-apps nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{ translate('messages.flash_sales') }}
                            </span>
                        </a>
                    </li>
                    {{-- @endif --}}
                    <!-- End Attributes -->
                    @endif
                    <!-- End Orders -->

                  


                <!-- Marketing section -->
                <li class="nav-item">
                    <small class="nav-subtitle" title="{{ translate('Promotion Management') }}">{{ translate('Promotion Management') }}</small>
                    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                </li>
                  @if (\App\CentralLogics\Helpers::module_permission_check('banner'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('banner*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="banner">
                                <i class="tio-image nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.Banners') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display:{{ Request::is('banner*') ? 'block' : 'none' }}">

                                <li class="nav-item {{ Request::is('blog/banner') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.banner.add-new') }}"
                                        title="Banners">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Banners</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('blog/banner/offer') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.banner.offer') }}"
                                        title="Banners">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Offer Banners</span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('promotional-banner*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.promotional-banner.add-new') }}"
                                title="{{ translate('messages.other_banners') }}">
                                <i class="tio-image nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.other_banners') }}</span>
                            </a>
                        </li>
                    @endif
                <!-- Campaign -->
                @if (\App\CentralLogics\Helpers::module_permission_check('campaign'))
                <li class="navbar-vertical-aside-has-menu {{ Request::is('campaign') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" title="{{ translate('messages.campaigns') }}">
                        <i class="tio-layers-outlined nav-icon"></i>
                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.campaigns') }}</span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display:{{ Request::is('campaign*') ? 'block' : 'none' }}">

                        <li class="nav-item {{ Request::is('campaign/basic/*') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.campaign.list', 'basic') }}" title="{{ translate('messages.basic_campaigns') }}">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">{{ translate('messages.basic_campaigns') }}</span>
                            </a>
                        </li>
                        <li class="nav-item {{ Request::is('campaign/item/*') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.campaign.list', 'item') }}" title="{{ translate('messages.item_campaigns') }}">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">{{ translate('messages.item_campaigns') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif
                <!-- End Campaign -->
              
                <!-- Coupon -->
                @if (\App\CentralLogics\Helpers::module_permission_check('coupon'))
                <li class="navbar-vertical-aside-has-menu {{ Request::is('coupon*') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.coupon.add-new') }}" title="{{ translate('messages.coupons') }}">
                        <i class="tio-gift nav-icon"></i>
                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.coupons') }}</span>
                    </a>
                </li>
                @endif
                <!-- End Coupon -->
                <!-- Notification -->
                @if (\App\CentralLogics\Helpers::module_permission_check('notification'))
                <li class="navbar-vertical-aside-has-menu {{ Request::is('notification*') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.notification.add-new') }}" title="{{ translate('messages.push_notification') }}">
                        <i class="tio-notifications nav-icon"></i>
                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                            {{ translate('messages.push_notification') }}
                        </span>
                    </a>
                </li>
                @endif
                <!-- End Notification -->

                <!-- End marketing section -->

                    <li class="nav-item">
                        <small class="nav-subtitle" title="{{ translate('messages.item_section') }}">{{ Config::get('module.current_module_id') == 6? 'Service Management':  translate('Service Management') }}</small>
                        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li>

                    <!-- Category -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('category'))
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('category*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" title="{{ translate('messages.categories') }}">
                                <i class="tio-category nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.categories') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"  style="display:{{ Request::is('category*') ? 'block' : 'none' }}">
                                <li class="nav-item   @yield('main_category') {{ request()->input('position') == 0 && Request::is('category/add') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.category.add',['position'=>0]) }}" title="{{ translate('messages.category') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.category') }}</span>
                                    </a>
                                </li>

                                <li class="nav-item   @yield('sub_category') {{ request()->input('position') == 1 && Request::is('category/add') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.category.add',['position'=>1]) }}" title="{{ translate('messages.sub_category') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.sub_category') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item   {{  Request::is('category/fee') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.category.category-fee') }}" title="{{ translate('messages.fee_category') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.fee_category') }}</span>
                                    </a>
                                </li>

                                <li class="nav-item {{ Request::is('category/bulk-import') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.category.bulk-import') }}" title="{{ translate('messages.bulk_import') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate text-capitalize">{{ translate('messages.bulk_import') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('category/bulk-export') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.category.bulk-export-index') }}" title="{{ translate('messages.bulk_export') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate text-capitalize">{{ translate('messages.bulk_export') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                @endif
                <!-- End Category -->

                  <!-- Attributes -->
                  @if (\App\CentralLogics\Helpers::module_permission_check('attribute'))
                <!-- <li class="navbar-vertical-aside-has-menu {{ Request::is('keywords*') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.keywords') }}" title="{{ translate('messages.keywords') }}">
                        <i class="tio-apps nav-icon"></i>
                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                       Keywords
                        </span>
                    </a>
                </li> -->
                @endif
                <!-- End Attributes -->

                <!-- Attributes -->
                @if (\App\CentralLogics\Helpers::module_permission_check('attribute'))
                <li class="navbar-vertical-aside-has-menu {{ Request::is('attribute*') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.attribute.add-new') }}" title="{{ translate('messages.attributes') }}">
                        <i class="tio-apps nav-icon"></i>
                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                            {{ translate('messages.attributes') }}
                        </span>
                    </a>
                </li>
                @endif
                <!-- End Attributes -->

                <!-- Unit -->
                @if (\App\CentralLogics\Helpers::module_permission_check('unit'))
                <li class="navbar-vertical-aside-has-menu {{ Request::is('unit*') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.unit.index') }}" title="{{ translate('messages.units') }}">
                        <i class="tio-ruler nav-icon"></i>
                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate text-capitalize">
                            {{ translate('messages.units') }}
                        </span>
                    </a>
                </li>
                @endif
                <!-- End Unit -->


                <!-- End AddOn -->
                <!-- Food -->
                @if (\App\CentralLogics\Helpers::module_permission_check('item'))
                <li class="navbar-vertical-aside-has-menu {{ Request::is('item*') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" title="{{ Config::get('module.current_module_id') == 6? 'Service Setup':  translate('Product Setup') }}">
                        <i class="tio-premium-outlined nav-icon"></i>
                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate text-capitalize">{{ Config::get('module.current_module_id') == 6? 'Service Setup':  translate('Product Setup') }}</span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display:{{ Request::is('item*') ? 'block' : 'none' }}">
                        <li class="nav-item {{ Request::is('item/add-new') || (Request::is('item/edit/*') && strpos(request()->fullUrl(), 'product_gellary=1') !== false  )  ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.item.add-new') }}" title="{{ translate('messages.add_new') }}">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">{{ translate('messages.add_new') }}</span>
                            </a>
                        </li>
                        <li class="nav-item {{ Request::is('item/list') || (Request::is('item/edit/*') && (strpos(request()->fullUrl(), 'temp_product=1') == false && strpos(request()->fullUrl(), 'product_gellary=1') == false  ) ) ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.item.list') }}" title="{{ translate('messages.food_list') }}">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">{{ translate('messages.list') }}</span>
                            </a>
                        </li>
                        <li class="nav-item {{ Request::is('item/keywords') || (Request::is('item/keywords/*') )  ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.item.keywords') }}" title="Product Keywords">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate"> Keywords</span>
                            </a>
                        </li>
                        @if (\App\CentralLogics\Helpers::get_mail_status('product_gallery'))
                        <li class="nav-item {{  Request::is('item/product-gallery') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.item.product_gallery') }}" title="{{ translate('messages.Product_Gallery') }}">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">{{ translate('messages.Product_Gallery') }}</span>
                            </a>
                        </li>
                        @endif

                        @if (\App\CentralLogics\Helpers::get_mail_status('product_approval'))
                        <li class="nav-item {{ Request::is('item/requested/item/view/*') || Request::is('item/new/item/list') || (Request::is('item/edit/*') && strpos(request()->fullUrl(), 'temp_product=1') !== false  ) ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.item.approval_list') }}" title="{{ translate('messages.New_Product_Request') }}">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">{{ translate('messages.New_Product_Request') }}</span>
                            </a>
                        </li>
                        @endif
                        <li class="nav-item {{ Request::is('item/reviews') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.item.reviews') }}" title="{{ translate('messages.review_list') }}">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">{{ translate('messages.review') }}</span>
                            </a>
                        </li>
                        <li class="nav-item {{ Request::is('item/bulk-import') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.item.bulk-import') }}" title="{{ translate('messages.bulk_import') }}">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate text-capitalize">{{ translate('messages.bulk_import') }}</span>
                            </a>
                        </li>
                        <li class="nav-item {{ Request::is('item/bulk-export') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.item.bulk-export-index') }}" title="{{ translate('messages.bulk_export') }}">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate text-capitalize">{{ translate('messages.bulk_export') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif
                <!-- End Food -->
                
              
                
                
                <!-- Store Store -->
                <li class="nav-item">
                    <small class="nav-subtitle" title="{{ translate('messages.store_section') }}">{{ translate('messages.store_management') }}</small>
                    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                </li>

                @if (\App\CentralLogics\Helpers::module_permission_check('store'))

                <li class="navbar-vertical-aside-has-menu {{ Request::is('store/pending-requests') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.store.pending-requests') }}" title="{{ translate('messages.pending_requests') }}">
                        <span class="tio-calendar-note nav-icon"></span>
                        <span class="text-truncate position-relative overflow-visible">
                            {{ translate('messages.new_stores') }}
                            @php($new_str = \App\Models\Store::whereHas('vendor', function($query){
                                return $query->where('status', null);
                            })->module(Config::get('module.current_module_id'))->get())
                            @if (count($new_str)>0)

                            <span class="btn-status btn-status-danger border-0 size-8px"></span>
                            @endif
                        </span>
                    </a>
                </li>
                <li class="navbar-vertical-aside-has-menu {{ Request::is('store/add') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.store.add') }}" title="{{ translate('messages.add_store') }}">
                        <span class="tio-add-circle nav-icon"></span>
                        <span class="text-truncate">
                            {{ translate('messages.add_store') }}
                        </span>
                    </a>
                </li>
                <li class="navbar-vertical-aside-has-menu {{ Request::is('store/list') ||  Request::is('store/view/*')  ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.store.list') }}" title="{{ translate('messages.stores_list') }}">
                        <span class="tio-layout nav-icon"></span>
                        <span class="text-truncate">{{ translate('messages.stores') }}
                            {{ translate('list') }}</span>
                    </a>
                </li>
                  <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/store/types')  }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.store.types') }}" title="{{ translate('messages.store_types') }}">
                        <span class="tio-layout nav-icon"></span>
                        <span class="text-truncate">Business Type
                           Config</span>
                    </a>
                </li>
                <li class="navbar-vertical-aside-has-menu {{ Request::is('store/report') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.store.report') }}" title="{{ translate('messages.report') }}">
                        <span class="tio-layout nav-icon"></span>
                        <span class="text-truncate">Report</span>
                    </a>
                </li>
                <li class="navbar-item {{ Request::is('store/recommended-store') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.store.recommended_store') }}" title="{{ translate('messages.pending_requests') }}">
                        <span class="tio-hot  nav-icon"></span>
                        <span class="text-truncate text-capitalize">{{ translate('Recommended_Store') }}</span>
                    </a>
                </li>
                <li class="navbar-vertical-aside-has-menu {{ Request::is('store/bulk-import') ? 'active' : '' }}">
                    <a class="nav-link " href="{{ route('admin.store.bulk-import') }}" title="{{ translate('messages.bulk_import') }}">
                        <span class="tio-publish nav-icon"></span>
                        <span class="text-truncate text-capitalize">{{ translate('messages.bulk_import') }}</span>
                    </a>
                </li>
                <li class="navbar-vertical-aside-has-menu {{ Request::is('store/bulk-export') ? 'active' : '' }}">
                    <a class="nav-link " href="{{ route('admin.store.bulk-export-index') }}" title="{{ translate('messages.bulk_export') }}">
                        <span class="tio-download-to nav-icon"></span>
                        <span class="text-truncate text-capitalize">{{ translate('messages.bulk_export') }}</span>
                    </a>
                </li> 
                @endif

                @if (\App\CentralLogics\Helpers::module_permission_check('subscription_plan'))
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('plan*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" title="Subscription Plan Management">
                            <i class="tio-calendar-note nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate text-capitalize">Subscription Plan</span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display:{{ Request::is('plan*') ? 'block' : 'none' }}">
                            <li class="nav-item {{ Request::is('plan/add-new') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.plan.add-new') }}" title="{{ translate('messages.add') }} {{ translate('messages.new') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">{{ translate('messages.add') }}
                                        {{ translate('messages.new') }}</span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('plan/list') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.plan.list') }}" title="Plans List">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">View All</span>
                                </a>
                            </li>
                          
                        </ul> 
                    </li>
                    @endif
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('store/terms-and-conditions') ? 'active' : '' }}">
                        <a class="nav-link " href="{{ route('admin.store.terms-and-conditions') }}" title="{{ translate('terms-and-conditions') }}">
                            <span class="tio-document-text-outlined mr-2"></span>
                            <span class="text-truncate text-capitalize"> {{ translate('messages.terms-and-conditions') }}</span>
                        </a>
                    </li> 
                
                 @if (\App\CentralLogics\Helpers::permission_check('leads_manage')&& Config::get('module.current_module_id') == 6 )
                    <li class="nav-item">
                        <small class="nav-subtitle" title="Clients Management">Clients Section</small>
                        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li>
                    @if (\App\CentralLogics\Helpers::permission_check('leads_manage') && Config::get('module.current_module_id') == 6)
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('lead*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="Leads">
                            <i class="tio-group-junior nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Leads Management</span>
                        </a>

                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{ Request::is('lead*') ? 'block' : 'none' }}">
                            <li class="nav-item {{ Request::is('lead/add') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.lead.add') }}" title="{{ translate('messages.add') }} {{ translate('messages.new') }} Lead">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">{{ translate('messages.add') }}
                                        {{ translate('messages.new') }}</span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('lead/list') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.lead.list') }}" title="Lead {{ translate('messages.list') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">{{ translate('messages.list') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif
                   
                  
                    
                @endif
                
                 
                
                <!-- End Store -->



                <li class="nav-item py-5">

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
            const suggestions = ['{{strtolower(translate('messages.order'))  }}', '{{ strtolower(translate('messages.campaign'))  }}', '{{ strtolower(translate('messages.category')) }}', '{{ strtolower(translate('messages.product')) }}','{{ strtolower(translate('messages.store')) }}' ];
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
