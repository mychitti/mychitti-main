                    {{-- =============================== DASHBOARD =========================== --}}
                    @if (selected_menu('dashboard'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.dashboard') }}"
                                title="{{ translate('messages.dashboard') }}">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Dashboard_color.png') }}"
                                    alt="" class="nav-link-icon">
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.dashboard') }}
                                </span>
                            </a>
                        </li>
                    @endif

                    {{-- =============================== FLASH SALE =========================== --}}
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('item/flash-sale*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                            href="{{ route('vendor.item.flash_sale') }}"
                            title="{{ translate('messages.flash_sales') }}">
                            <i class="tio-apps nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{ translate('messages.flash_sales') }}
                            </span>
                        </a>
                    </li>

                    {{-- =============================== ITEM MANAGEMENT =========================== --}}
                    <li class="nav-item">
                        <small class="nav-subtitle">{{ translate('messages.item_management') }}</small>
                        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li>

                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('addon'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('addon*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.addon.add-new') }}"
                                title="{{ translate('messages.addons') }}">
                                <i class="tio-add-circle-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.addons') }}
                                </span>
                            </a>
                        </li>
                    @endif

                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('item'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('item*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                href="javascript:" title="{{ translate('messages.items') }}">
                                <i class="tio-premium-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.items') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('item*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('item/add-new') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.item.add-new') }}"
                                        title="{{ translate('messages.add_new_item') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.add_new') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('item/list') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.item.list') }}"
                                        title="{{ translate('messages.items_list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.list') }}</span>
                                    </a>
                                </li>
                                @if (\App\CentralLogics\Helpers::get_mail_status('product_approval'))
                                    <li class="nav-item {{ Request::is('item/pending/item/list') || Request::is('item/requested/item/view/*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.item.pending_item_list') }}"
                                            title="{{ translate('messages.pending_item_list') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('messages.pending_item_list') }}</span>
                                        </a>
                                    </li>
                                @endif
                                @if (\App\CentralLogics\Helpers::get_mail_status('product_gallery'))
                                    <li class="nav-item {{ Request::is('item/product-gallery') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.item.product_gallery') }}"
                                            title="{{ translate('messages.Product_Gallery') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('messages.Product_Gallery') }}</span>
                                        </a>
                                    </li>
                                @endif
                                <li class="nav-item {{ Request::is('item/price-update-list') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.item.price-update-list') }}"
                                        title="{{ translate('messages.price_update_list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Price Update</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('item/stock-limit-list') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.item.stock-limit-list') }}"
                                        title="{{ translate('messages.stock_limit_list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.stock_limit_list') }}</span>
                                    </a>
                                </li>
                                @if (\App\CentralLogics\Helpers::get_store_data()->item_section)
                                    <li class="nav-item {{ Request::is('item/bulk-import') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.item.bulk-import') }}"
                                            title="{{ translate('messages.bulk_import') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate text-capitalize">{{ translate('messages.bulk_import') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('item/bulk-export') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.item.bulk-export-index') }}"
                                            title="{{ translate('messages.bulk_export') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate text-capitalize">{{ translate('messages.bulk_export') }}</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ Request::is('category*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                href="javascript:" title="{{ translate('messages.categories') }}">
                                <i class="tio-category nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.categories') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('category*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('category/list') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.category.add') }}"
                                        title="{{ translate('messages.category') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.category') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('category/sub-category-list') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.category.add-sub-category') }}"
                                        title="{{ translate('messages.sub_category') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.sub_category') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    {{-- =============================== ORDERS =========================== --}}
                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('order'))
                        <li class="nav-item">
                            <small class="nav-subtitle">{{ translate('messages.order_management') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('order*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                href="javascript:" title="{{ translate('messages.orders') }}">
                                <i class="tio-shopping-cart nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.orders') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('order*') ? 'block' : 'none' }}">
                                @foreach(['pending','confirmed','processing','picked_up','delivered','cancelled','all','scheduled'] as $status)
                                    <li class="nav-item {{ Request::is('order/list/' . $status) ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.order.list', [$status]) }}"
                                            title="{{ ucfirst(str_replace('_', ' ', $status)) }} Orders">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif

                    {{-- =============================== DELIVERY MEN =========================== --}}
                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('deliveryman'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('delivery-man/add') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.delivery-man.add') }}"
                                title="{{ translate('messages.add_delivery_man') }}">
                                <i class="tio-running nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.add_delivery_man') }}
                                </span>
                            </a>
                        </li>
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('delivery-man/list') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.delivery-man.list') }}"
                                title="{{ translate('messages.deliveryman') }}">
                                <i class="tio-filter-list nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.deliverymen_list') }}
                                </span>
                            </a>
                        </li>
                    @endif

                    {{-- =============================== CAMPAIGNS =========================== --}}
                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('campaign'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('campaign*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                href="javascript:" title="{{ translate('messages.campaigns') }}">
                                <i class="tio-image nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.campaigns') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('campaign*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('campaign/list') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.campaign.list') }}"
                                        title="{{ translate('messages.basic_campaigns') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.basic_campaigns') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('campaign/item/list') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.campaign.itemlist') }}"
                                        title="{{ translate('messages.Item Campaigns') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.Item Campaigns') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    {{-- =============================== COUPONS =========================== --}}
                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('coupon'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('coupon*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.coupon.add-new') }}"
                                title="{{ translate('messages.coupons') }}">
                                <i class="tio-ticket nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.coupons') }}</span>
                            </a>
                        </li>
                    @endif

                    {{-- =============================== BANNERS =========================== --}}
                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('banner') || auth('vendor')->check())
                        <li class="nav-item">
                            <small class="nav-subtitle">{{ translate('messages.marketing') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        @if (auth('vendor')->check())
                            <li class="navbar-vertical-aside-has-menu {{ Request::is('banner/offer-banner') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link"
                                    href="{{ route('vendor.banner.offer') }}"
                                    title="{{ translate('messages.offer_banners') }}">
                                    <i class="tio-photo-square-outlined nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.offer_banners') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (\App\CentralLogics\Helpers::employee_module_permission_check('banner'))
                            <li class="navbar-vertical-aside-has-menu {{ Request::is('banner*') && !Request::is('banner/offer-banner') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link"
                                    href="{{ route('vendor.banner.list') }}"
                                    title="{{ translate('messages.banners') }}">
                                    <i class="tio-image nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.banners') }}</span>
                                </a>
                            </li>
                        @endif
                    @endif

                    {{-- =============================== BILLING =========================== --}}
                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('billing') && selected_menu('billing'))
                        <li class="nav-item">
                            <small class="nav-subtitle">{{ translate('messages.billing') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('billing*') || Request::is('invoice-list') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="{{ translate('messages.billing') }}">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Billing_management_color.png') }}"
                                    alt="" class="nav-link-icon">
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Billing</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                @if (hasPermission('billing', 'add_basic'))
                                    <li class="nav-item {{ Request::is('billing/manual-bill') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.invoice.manual-bill') }}" title="Add Bill">
                                            <span class="tio-document-text nav-icon"></span>
                                            <span class="text-truncate">Add Bill</span>
                                        </a>
                                    </li>
                                @endif
                                @if (hasAnyPermission(['billing.list', 'billing.export']))
                                    <li class="nav-item {{ Request::is('billing/credit') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.invoice.list') }}" title="Bill List">
                                            <span class="tio-coin nav-icon"></span>
                                            <span class="text-truncate">Bill List</span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('invoice-list') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.order.invoices') }}" title="Invoices">
                                            <span class="tio-document-text nav-icon"></span>
                                            <span class="text-truncate">Invoices</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    {{-- =============================== WALLET =========================== --}}
                    @if (selected_menu('my_wallet') && \App\CentralLogics\Helpers::employee_module_permission_check('wallet'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('wallet/wallet-payment-list') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.wallet.wallet_payment_list') }}"
                                title="{{ translate('messages.my_wallet') }}">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/My%20Wallet_color.png') }}"
                                    alt="" class="nav-link-icon">
                                <span class="text-truncate">{{ translate('messages.my_wallet') }}</span>
                            </a>
                        </li>
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('withdraw-method*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.wallet-method.index') }}"
                                title="{{ translate('messages.disbursement_method') }}">
                                <i class="tio-museum nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.disbursement_method') }}</span>
                            </a>
                        </li>
                    @endif

                    {{-- =============================== MY BUSINESS =========================== --}}
                    @if (selected_menu('my_business') && \App\CentralLogics\Helpers::employee_module_permission_check('my_business'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('business-settings*') || Request::is('settings/general*') || Request::is('store/edit') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="My Business">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/My%20Business_color.png') }}"
                                    alt="" class="nav-link-icon">
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">My Business</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                @if (hasPermission('webpage_settings', 'view'))
                                <li class="nav-item {{ Request::is('settings/settings/webpage') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.settings.webpage') }}" title="Webpage Settings">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Webpage Settings</span>
                                    </a>
                                </li>
                                @endif
                                @if (hasPermission('store_settings', 'view'))
                                <li class="nav-item {{ Request::is('store/edit') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.shop.edit') }}" title="Store Settings">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Store Settings</span>
                                    </a>
                                </li>
                                @endif
                                @if (hasPermission('service_setup', 'view'))
                                <li class="nav-item {{ Request::is('settings/service-setup') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.settings.service-setup') }}" title="Service Setup">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Service Setup</span>
                                    </a>
                                </li>
                                @endif
                                @if (hasPermission('profile_settings', 'view'))
                                <li class="nav-item {{ Request::is('settings/general/profile') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.settings.general.profile') }}" title="Profile Settings">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Profile Settings</span>
                                    </a>
                                </li>
                                @endif
                                @if (hasPermission('reviews', 'view'))
                                <li class="nav-item {{ Request::is('service/reviews*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.service.reviews') }}" title="Reviews">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Reviews</span>
                                    </a>
                                </li>
                                @endif
                                @if (hasPermission('performance_analytics', 'view'))
                                <li class="nav-item {{ Request::is('store-panel/performance-analytics*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.performance-analytics.index') }}" title="Performance Analytics">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Performance Analytics</span>
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @php($primary_color = \App\Models\BusinessSetting::where('key', 'primary_color')->first())
                    @php($secondary_color = \App\Models\BusinessSetting::where('key', 'secondary_color')->first())
                    @php($primary_btn_hover = \App\Models\BusinessSetting::where('key', 'primary_btn_hover')->first())
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('subscriptions') ? 'active' : '' }}">
                        <div style="padding: 10px 11px; display: flex; margin: 0 10px; border-radius: 10px; align-items: center; background-color: color-mix(in srgb, {{ $primary_color ? $primary_color->value : '#754BFF' }} 15%, transparent);">
                            <label class="switch toggle-switch-lg m-0">
                                <input type="checkbox" class="toggle-switch-input keep-minimized"
                                    {{ _isMenuMinimized() ? 'checked' : '' }} value = '1'>
                                <span class="toggle-switch-label">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
                            </label>
                            <span class="text-primary pl-2" style="font-size: 14px;">Keep Menu <br> Minimized</span>
                        </div>
                    </li>
                    <li class="nav-item py-5"></li>
                    {{-- =============================== MENU PREFERENCE =========================== --}}
                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('menu_preference'))
                        <a class="text-truncate"
                            style="position: absolute; bottom: 2px; left: auto; background: #fff4f4; padding: 2px; font-size: 12px; text-align: center; width: 96%;"
                            href="{{ route('vendor.menu_preference') }}" title="Menu Preference">
                            <span class="text-truncate"><i class="tio-settings-outlined"></i> Menu Preferences</span>
                        </a>
                    @endif
