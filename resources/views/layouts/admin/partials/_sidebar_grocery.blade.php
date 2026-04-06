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
                
                    @include('admin-views/partials/billing_nav')
                    @include('admin-views/partials/account_nav')
                    @include('admin-views/partials/hr_nav')
                    @include('admin-views/partials/inventory_nav')
                     {{-- =============================== TASK Management=========================== --}}

                    @if (hasMasterModulePermission('task_manage'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('task*') && !Request::is('task-salary-categories') && !Request::is('task/assigned-tasks') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="Task Management">
                                 <i class="tio-all-done nav-icon"></i>


                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Task
                                    Management</span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('task*') && !Request::is('task-salary-categories') ? 'block' : 'none' }}">

                                @if (hasAnyPermission(['task.list', 'task.export', 'task.add']))
                                    <li class="nav-item {{ Request::is('task/list') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('admin.task.list') }}"
                                            title="list Project">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Tasks</span>
                                        </a>
                                    </li>
                                @endif
                                @if (hasPermission('task', 'settings'))
                                    <li class="nav-item {{ Request::is('task/setting') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('admin.task.setting') }}"
                                            title="Task Settings">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Task Settings</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif
                    {{-- =============================== PROJECT Management=========================== --}}
                    @if ( hasMasterModulePermission('projects_manage'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('project*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="Project Management">
                                <i class="tio-tabs nav-icon"></i>

                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Project
                                    Management</span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('project*') ? 'block' : 'none' }}">
                        
                                @if (hasPermission('project', 'add'))
                                    <li class="nav-item {{ Request::is('project/add') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('admin.project.add') }}"
                                            title="{{ translate('messages.add') }} {{ translate('messages.new') }} Project">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('messages.add') }}
                                                {{ translate('messages.new') }} Project</span>
                                        </a>
                                    </li>
                                @endif
                                @if (hasPermission('project', 'list'))
                                    <li class="nav-item {{ Request::is('project/list') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('admin.project.all') }}"
                                            title="list Project">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Projects List</span>
                                        </a>
                                    </li>
                                @endif
                                @if (hasPermission('project', 'settings'))
                                    <li class="nav-item {{ Request::is('project/settings') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('admin.project.settings') }}"
                                            title="list Project">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Project Settings</span>
                                        </a>
                                    </li>
                                @endif
                                
                            </ul>
                        </li>
                    @endif

                    {{-- =============================== CLIENT Management=========================== --}}
                    @if( hasMasterModulePermission('client_manage'))          
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('client*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="Client Management">
                                <i class="tio-user-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Client
                                    Management
                                </span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('client*') ? 'block' : 'none' }}">
                                @if (hasPermission('client_manage', 'add'))
                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('customer/add') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('admin.client.add') }}"
                                            title="Add New Client">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class=" text-truncate">Add New Client</span>
                                        </a>
                                    </li>
                                @endif
                                @if (hasAnyPermission(['client_manage.list', 'client_manage.import', 'client_manage.export']))
                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('client/list') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('admin.client.list') }}"
                                            title="{{ translate('messages.clients') }} Management">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class=" text-truncate">{{ translate('messages.clients') }}
                                                List</span>
                                        </a>
                                    </li>
                                @endif
                              
                            </ul>
                        </li>
                    @endif


                    @if(hasMasterModulePermission('support_ticket'))
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('ticket*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;">
                            <i class="tio-support nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Support Tickets</span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{ Request::is('ticket*') ? 'block' : 'none' }}">
                            @if(hasPermission('support_ticket', 'list'))
                            <li class="nav-item {{ Request::is('ticket') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('admin.ticket.index') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">All Tickets</span>
                                </a>
                            </li>
                            @endif
                            @if(hasPermission('support_ticket', 'add'))
                            <li class="nav-item {{ Request::is('ticket/create') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('admin.ticket.create') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Create Ticket</span>
                                </a>
                            </li>
                            @endif 
                        </ul>
                    </li>
                    @endif

                   
                       {{-- =============================== QUOTATION Management=========================== --}}
                    @if (hasMasterModulePermission('quotaiton_manage') )
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('quotation*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="Quotation Management">
                                <i class="tio-document nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Quotation
                                    Management</span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('quotation*') ? 'block' : 'none' }}">
                                @if (hasPermission('quotaiton_manage', 'add'))
                                    <li class="nav-item {{ Request::is('quotation/add') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('admin.quotation.add') }}"
                                            title="{{ translate('messages.add') }} {{ translate('messages.new') }} Quotation">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('messages.add') }}
                                                {{ translate('messages.new') }} Quotation</span>
                                        </a>
                                    </li>
                                @endif

                                @if (hasAnyPermission(['quotaiton_manage.list']))
                                    <li class="nav-item {{ Request::is('quotation/list') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('admin.quotation.list') }}"
                                            title="Quotation {{ translate('messages.list') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Quotations List</span>
                                        </a>
                                    </li>
                                @endif
                                @if (hasPermission('quotaiton_manage', 'settings') ||
                                        hasAnyModulePermission(['quotation_bank_account', 'quotation_sign', 'quotation_tnc']))
                                    <li class="nav-item {{ Request::is('quotation/settings') ? 'active' : '' }}"
                                        style="margin-top:0 !important;">
                                        <a class="nav-link " href="{{ route('admin.quotation.settings') }}"
                                            title="Quotation Settings">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Quotation Settings</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif


                <!-- Marketing section -->
                 @if (\App\CentralLogics\Helpers::module_permission_check('banner') || \App\CentralLogics\Helpers::module_permission_check('coupon'))
                <li class="nav-item">
                    <small class="nav-subtitle" title="{{ translate('Promotion Management') }}">{{ translate('Promotion Management') }}</small>
                    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                </li>
                @endif
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
                <!-- Coupon -->
                @if (\App\CentralLogics\Helpers::module_permission_check('coupon'))
                    @if (Config::get('module.current_module_id') == 6)
                        <li 
                            class="navbar-vertical-aside-has-menu {{ Request::is('coupon*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.coupon.service-coupon') }}"
                                title="{{ translate('messages.coupons') }}">
                                <i class="tio-gift nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.coupons') }} for Vendors</span>
                            </a>
                        </li>
                    @endif
                    <li
                        class="navbar-vertical-aside-has-menu {{ Request::is('coupon*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                            href="{{ route('admin.coupon.add-new') }}"
                            title="{{ translate('messages.coupons') }}">
                            <i class="tio-gift nav-icon"></i>
                            <span
                                class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.coupons') }} for Customers</span>
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

      

                    <!-- Category -->
                    {{-- @if (\App\CentralLogics\Helpers::module_permission_check('category') && 0)
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('category*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" title="{{ translate('messages.categories') }}">
                            <i class="tio-category nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.categories') }}</span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"  style="display:{{ Request::is('category*') ? 'block' : 'none' }}">
                            <li class="nav-item @yield('main_category')  {{ request()->input('position') == 0 && Request::is('category/add') ? 'active' : '' }}">
                                <a class="nav-link "  href="{{ route('admin.category.add',['position'=>0]) }}" title="{{ translate('messages.category') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">{{ translate('messages.category') }}</span>
                                </a>
                            </li>

                            <li class="nav-item  @yield('sub_category') {{ request()->input('position') == 1 && Request::is('category/add') ? 'active' : '' }}">
                                <a class="nav-link "  href="{{ route('admin.category.add',['position'=>1]) }}" title="{{ translate('messages.sub_category') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">{{ translate('messages.sub_category') }}</span>
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
                @endif --}}
                <!-- End Category -->
                @if (\App\CentralLogics\Helpers::module_permission_check('item'))

                <li class="nav-item">
                    <small class="nav-subtitle" title="{{ translate('messages.item_section') }}">{{ translate('messages.service_management') }}</small>
                    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                </li>
                <!-- Food -->
                <li class="navbar-vertical-aside-has-menu {{ (Request::is('item*')|| Request::is('category*')) ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" title="Service Setup">
                        <i class="tio-premium-outlined nav-icon"></i>
                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate text-capitalize">Service Setup</span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display:{{ (Request::is('item*') || Request::is('category*')) ? 'block' : 'none' }}">
                        @if (\App\CentralLogics\Helpers::module_permission_check('category'))
                        <li class="nav-item @yield('main_category')  {{ request()->input('position') == 0 && Request::is('category/add') ? 'active' : '' }}">
                                <a class="nav-link "  href="{{ route('admin.category.add',['position'=>0]) }}" title="{{ translate('messages.category') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">{{ translate('messages.category') }}</span>
                                </a>
                            </li>
                        @endif
                        <li class="nav-item {{ Request::is('item/add-new') || (Request::is('item/edit/*') && strpos(request()->fullUrl(), 'product_gellary=1') !== false  )  ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.item.add-new') }}" title="{{ translate('messages.add_new') }}">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">{{ translate('messages.add_new') }} Service</span>
                            </a>
                        </li>
                        <li class="nav-item {{ Request::is('item/list') || (Request::is('item/edit/*') && (strpos(request()->fullUrl(), 'temp_product=1') == false && strpos(request()->fullUrl(), 'product_gellary=1') == false  ) ) ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.item.list') }}" title="{{ translate('messages.food_list') }}">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">Service {{ translate('messages.list') }}</span>
                            </a>
                        </li>
                        <li class="nav-item {{ Request::is('item/keywords') || (Request::is('item/keywords/*') )  ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.item.keywords') }}" title="Service Keywords">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate"> Keywords</span>
                            </a>
                        </li>
                        <li class="nav-item {{ Request::is('item/trash') || (Request::is('item/trash/*') )  ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.item.trash.view') }}" title="Service trash">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate"> Trash</span>
                            </a>
                        </li>
                        <li class="nav-item {{  (Request::is('item/terms-and-conditions/*') )  ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.item.terms-and-conditions.index') }}" title="Terms and Conditions">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">Terms and Conditions</span>
                            </a>
                        </li>
                        @if (0 && \App\CentralLogics\Helpers::get_mail_status('product_gallery'))
                        <li class="nav-item {{  Request::is('item/product-gallery') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.item.product_gallery') }}" title="{{ translate('messages.Service_Gallery') }}">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">Service Gallery</span>
                            </a>
                        </li>
                        @endif 
                        @if (0 && \App\CentralLogics\Helpers::get_mail_status('product_approval'))
                        <li class="nav-item {{  Request::is('item/requested/item/view/*') || Request::is('item/new/item/list') || (Request::is('item/edit/*') && strpos(request()->fullUrl(), 'temp_product=1') !== false  ) ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.item.approval_list') }}" title="{{ translate('messages.New_Item_Request') }}">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">{{ translate('messages.New_Item_Request') }}</span>
                            </a>
                        </li>
                        @endif

                    </ul>
                </li>
                <!-- End Food -->

                    <li class="nav-item">
                        <small class="nav-subtitle" title="Clients Management">Clients Section</small>
                        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li>
                    <li class="navbar-vertical-aside-has-menu {{ (Request::is('lead*') || Request::is('service*')) ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="Leads">
                            <i class="tio-group-junior nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Leads Management</span>
                        </a>

                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{ (Request::is('lead*') || Request::is('service*')) ? 'block' : 'none' }}">

                            <li class="nav-item {{  Request::is('service/config')  ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.service.config') }}" title="config">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Config</span>
                                </a>
                            </li>
                            <li class="nav-item {{  Request::is('service/status')  ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.service.request-status') }}" title="Request Status">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Lead Status</span>
                                </a>
                            </li>
                            {{-- <li class="nav-item {{  Request::is('service/new-status-request')  ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.service.new-status-request') }}" title="New Status Request">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">New Status Request</span>
                                </a>
                            </li> --}}
                            <li class="nav-item {{  Request::is('service/lead-charge')  ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.service.lead-charge') }}" title="Lead Charge">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate"> Add Lead Charge</span>
                                </a>
                            </li>
                            <li class="nav-item {{  Request::is('service/lead-charges')  ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.service.lead-charge-list') }}" title="Lead Charge">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate"> View Lead Charges</span>
                                </a>
                            </li>
                         
                            <li class="nav-item {{ Request::is('lead/add') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.lead.add') }}" title="{{ translate('messages.add') }} {{ translate('messages.new') }} Lead">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">{{ translate('messages.add') }}
                                        {{ translate('messages.new') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                
                 @if (0 )
                <li class="navbar-vertical-aside-has-menu {{ Request::is('service*') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" title="Service Request Status">
                        <i class="tio-premium-outlined nav-icon"></i>
                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate text-capitalize">Service Request Setup</span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display:{{ Request::is('service*') ? 'block' : 'none' }}">
                        <li class="nav-item {{  Request::is('service/status')  ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.service.request-status') }}" title="Request Status">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">Request Status</span>
                            </a>
                        </li>
                         <li class="nav-item {{  Request::is('service/lead-charge')  ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.service.lead-charge') }}" title="Lead Charge">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate"> Add Lead Charge</span>
                            </a>
                        </li>
                         <li class="nav-item {{  Request::is('service/lead-charges')  ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.service.lead-charge-list') }}" title="Lead Charge">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate"> View Lead Charges</span>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif
                <li class="navbar-vertical-aside-has-menu {{ Request::is('service/list*') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.service.lead-list') }}" title="Service Leads">
                        <i class="tio-group-junior nav-icon"></i>
                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                          Service Leads 
                        </span>
                    </a>
                </li>
                @endif
                
                @if (\App\CentralLogics\Helpers::module_permission_check('store'))
                <!-- Store Store -->
                <li class="nav-item">
                    <small class="nav-subtitle" title="{{ translate('messages.store_section') }}">{{ translate('messages.store_management') }}</small>
                    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                </li>

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
                @if(hasPermission('store', 'add_basic') || hasPermission('store', 'add_advanced'))

                <li class="navbar-vertical-aside-has-menu {{ Request::is('store/add') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.store.add') }}" title="{{ translate('messages.add_store') }}">
                        <span class="tio-add-circle nav-icon"></span>
                        <span class="text-truncate">
                            {{ translate('messages.add_store') }}
                        </span>
                    </a>
                </li>
                @endif
                @if(hasPermission('store', 'list'))
                <li class="navbar-vertical-aside-has-menu {{ Request::is('store/list')  ||  Request::is('store/view/*')  ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.store.list') }}" title="{{ translate('messages.stores_list') }}">
                        <span class="tio-layout nav-icon"></span>
                        <span class="text-truncate">{{ translate('messages.stores') }}
                            {{ translate('list') }}</span>
                    </a> 
                </li>
                @endif
                <li class="navbar-vertical-aside-has-menu {{ Request::is('store/removal-requests') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.store.removal-requests') }}" title="Removal Requests">
                        <span class="tio-delete nav-icon"></span>
                        <span class="text-truncate position-relative overflow-visible">
                            Removal Requests
                            @php($pending_removals = \Illuminate\Support\Facades\DB::table('store_removal_requests')->where('status', 'pending')->count())
                            @if($pending_removals > 0)
                            <span class="btn-status btn-status-danger border-0 size-8px"></span>
                            @endif
                        </span> 
                    </a>
                </li>
                   <li class="navbar-vertical-aside-has-menu {{ Request::is('store-monetization*') ? 'show active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                            href="{{ route('admin.store-monetization.dashboard') }}" title="Store Monetization">
                            <i class="tio-chart-bar-4 nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                Store Monetization
                            </span>
                        </a>
                    </li>
                <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/store/types')  }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.store.types') }}" title="{{ translate('messages.stores_config') }}">
                        <span class="tio-layout nav-icon"></span>
                        <span class="text-truncate">Business Type
                           Config</span>
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
                {{-- @elseif(\App\CentralLogics\Helpers::module_permission_check('store_add_edit'))
                  <li class="navbar-vertical-aside-has-menu {{ Request::is('store/add') ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.store.add') }}" title="{{ translate('messages.add_store') }}">
                        <span class="tio-add-circle nav-icon"></span>
                        <span class="text-truncate">
                            {{ translate('messages.add_store') }}
                        </span>
                    </a>
                </li>
                 <li class="navbar-vertical-aside-has-menu {{ Request::is('store/list')  ||  Request::is('store/view/*')  ? 'active' : '' }}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.store.list') }}" title="{{ translate('messages.stores_list') }}">
                        <span class="tio-layout nav-icon"></span>
                        <span class="text-truncate">{{ translate('messages.stores') }}
                            {{ translate('list') }}</span>
                    </a>
                </li> --}}
                @endif 
                @if (\App\CentralLogics\Helpers::module_permission_check('terms_and_conditions'))

                <li class="navbar-vertical-aside-has-menu {{ Request::is('store/terms-and-conditions') ? 'active' : '' }}">
                    <a class="nav-link " href="{{ route('admin.store.terms-and-conditions') }}" title="{{ translate('terms-and-conditions') }}">
                        <span class="tio-document-text-outlined mr-2"></span>
                        <span class="text-truncate text-capitalize"> {{ translate('messages.terms-and-conditions') }}</span>
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
                        <li class="nav-item {{ Request::is('plan/stores') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.plan.stores') }}" title="Stores List">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">Store Subscriptions</span>
                            </a>
                        </li>
                        <li class="nav-item {{ Request::is('plan/requests') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.plan.requests') }}" title="requests List">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate"> Subscription Requests</span>
                            </a>
                        </li>
                        <li class="nav-item {{ Request::is('plan/module-pricing') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.plan.module-pricing') }}" title="Module Pricing">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate">Module Pricing</span>
                            </a>
                        </li>
                    </ul>
                </li> 
                @endif
                {{-- STORE WALLET  =========================== --}}
                  @if (\App\CentralLogics\Helpers::module_permission_check('store_wallet'))
                  <li class="navbar-vertical-aside-has-menu {{ Request::is('store/wallet*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.store.wallet.index') }}" title="Store Wallet">
                            <i class="tio-incognito nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Store Wallet</span>
                        </a>
                    </li>
                    @endif
                {{-- STORE WALLET END =========================== --}}

                  
                     {{-- @if (\App\CentralLogics\Helpers::module_permission_check('quotaiton_manage'))
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('quotation*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="Leads">
                            <i class="tio-money nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Quotation Management</span>
                        </a>

                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{ Request::is('quotation*') ? 'block' : 'none' }}">
                            <li class="nav-item {{ Request::is('quotation/add') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.quotation.add') }}" title="{{ translate('messages.add') }} {{ translate('messages.new') }} Quotation">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">{{ translate('messages.add') }}
                                        {{ translate('messages.new') }} Quotation</span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('quotation/list') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.quotation.list') }}" title="Quotation {{ translate('messages.list') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">All Quotations</span>
                                </a>
                            </li>
                             <li class="nav-item {{ Request::is('quotation/new') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.quotation.new') }}" title="Quotation {{ translate('messages.list') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">New Quotations</span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('quotation/accepted') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.quotation.accepted') }}" title="Quotation {{ translate('messages.list') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Accepted Quotations</span>
                                </a>
                            </li>
                             <li class="nav-item {{ Request::is('quotation/declined') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.quotation.declined') }}" title="Quotation {{ translate('messages.list') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Declined Quotations</span>
                                </a>
                            </li>

                        </ul>
                    </li>
                    @endif --}}
                  
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
            {{--const suggestions = ['{{strtolower(translate('messages.order'))  }}', '{{ strtolower(translate('messages.campaign'))  }}', '{{ strtolower(translate('messages.category')) }}', '{{ strtolower(translate('messages.product')) }}','{{ strtolower(translate('messages.store')) }}' ];--}}
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
