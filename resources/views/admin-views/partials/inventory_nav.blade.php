{{-- =============================== iNVENTORY Management=========================== --}}
@if ( hasMasterModulePermission('inventory_manage'))
    <li
        class="navbar-vertical-aside-has-menu {{ Request::is('inventory*') || Request::is('item/entry') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
            title=" Inventory Management">
           <i class="tio-rome nav-icon"></i>

            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                Inventory Management</span>
        </a>

        <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
            @if (hasPermission('inventory', 'dashboard'))
                <li
                    class="navbar-vertical-aside-has-menu {{ Request::is('inventory/dashboard') ? 'active' : '' }}">
                    <a class="nav-link " href="{{ route('admin.inventory.dashboard') }}"
                        title="{{ translate('messages.dashboard') }}">
                        <span class="tio-dashboard nav-icon"></span>
                        <span class="text-truncate">Inventory Dashboard</span>
                    </a>
                </li>
            @endif
            <li
                class="navbar-vertical-aside-has-menu {{ Request::is('inventory*') ? 'active' : '' }}">
                <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                    href="javascript:" title="Products / Services">
                    <i class="tio-shopping-cart nav-icon"></i>
                    <span class=" text-truncate">
                        Products / Services
                    </span>
                </a>
                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                    style="display: {{ Request::is('inventory') || Request::is('inventory/storage-spaces') ? 'block' : 'none' }}">
                    <li class="nav-item  {{ Request::is('inventory') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.inventory.index') }}"
                            title=" {{ translate('messages.item_adding_book') }}">
                            <span class="tio-circle nav-indicator-icon"></span>
                            <span class="text-truncate sidebar--badge-container">
                                Item Adding Book
                            </span>
                        </a>
                    </li>

                    @if (hasAnyPermission(['inventory_storage_units.list', 'inventory_storage_units.add']))
                        <li
                            class="nav-item {{ Request::is('inventory/storage-spaces') ? 'active' : '' }}">
                            <a class="nav-link"
                                href="{{ route('admin.inventory.storage-spaces') }}"
                                title="Storage Units">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate sidebar--badge-container">
                                    Storage Units
                                </span>
                            </a>
                        </li>
                    @endif
                    <li
                        class="nav-item {{ Request::is('inventory/item-images') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.inventory.item-images') }}"
                            title="Item Images">
                            <span class="tio-circle nav-indicator-icon"></span>
                            <span class="text-truncate sidebar--badge-container">
                                Item Images
                            </span>
                        </a>
                    </li>
                </ul>
            </li>
            @if (hasAnyPermission(['inventory_stock_in_out.list']))
                <li
                    class="navbar-vertical-aside-has-menu  {{ Request::is('inventory/stock*') ? 'active' : '' }}">
                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                        href="javascript:" title="Stock Management">
                        <i class="tio-folders-outlined nav-icon"></i>
                        <span class=" text-truncate">
                            Stock Management
                        </span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: ">
                        @if (hasPermission('inventory_stock_in_out', 'list'))
                            <li
                                class="nav-item  {{ Request::is('inventory/stock/stock-in-out') ? 'active' : '' }}">
                                <a class="nav-link"
                                    href="{{ route('admin.inventory.stock.stock-in-out') }}"
                                    title="Stock in / Stock out">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        Stock in / Stock out
                                    </span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if (hasAnyPermission([
                    'inventory_sale_order.list',
                    'inventory_sale_order.export',
                    'inventory_sale_return.export',
                    'inventory_sale_return.list',
                ]))
                <li
                    class="navbar-vertical-aside-has-menu {{ Request::is('inventory/sale*') ? 'active' : '' }} ">
                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                        href="javascript:" title="Sales">
                        <i class="tio-chart-bar-1 nav-icon"></i>
                        <span class=" text-truncate">
                            Sales
                        </span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                        style="display: {{ Request::is('inventory/sale*') ? 'block' : 'none' }} ">
                        @if (hasPermission('inventory_sale_order', 'list') || hasPermission('inventory_sale_order', 'export'))
                            <li
                                class="nav-item {{ Request::is('inventory/sale/orders') ? 'active' : '' }}">
                                <a class="nav-link"
                                    href="{{ route('admin.inventory.sale.orders') }}"
                                    title="Sale Orders">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        Sale Orders
                                    </span>
                                </a>
                            </li>
                        @endif

                        @if (hasPermission('inventory_sale_return', 'list') || hasPermission('inventory_sale_return', 'export'))
                            <li
                                class="nav-item {{ Request::is('inventory/sale/orders-return') ? 'active' : '' }}">
                                <a class="nav-link"
                                    href="{{ route('admin.inventory.sale.orders-return') }}"
                                    title="Return Orders">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        Return Orders
                                    </span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if (hasAnyPermission([
                    'inventory_purchase_return.add',
                    'inventory_purchase_return.list',
                    'inventory_purchase_order.add',
                    'inventory_purchase_order.list',
                    'purchase_bill.list',
                ]))

                <li
                    class="navbar-vertical-aside-has-menu {{ Request::is('inventory/purchase*') ? 'active' : '' }}">
                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                        href="javascript:" title="Purchase">
                        <i class="tio-chart-bar-2 nav-icon"></i>
                        <span class=" text-truncate">
                            Purchase
                        </span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                        style="display: {{ Request::is('inventory/purchase*') ? 'block' : 'none' }}">
                        @if (hasPermission('inventory_purchase_order', 'add') || hasPermission('inventory_purchase_order', 'list'))
                            <li
                                class="nav-item {{ Request::is('inventory/purchase/orders') ? 'active' : '' }}">
                                <a class="nav-link"
                                    href="{{ route('admin.inventory.purchase.orders') }}"
                                    title="Purchase Orders">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        Purchase Orders
                                    </span>
                                </a>
                            </li>
                        @endif
                        @if (hasPermission('inventory_purchase_return', 'add') || hasPermission('inventory_purchase_return', 'list'))
                            <li
                                class="nav-item {{ Request::is('inventory/purchase/return') ? 'active' : '' }}">
                                <a class="nav-link"
                                    href="{{ route('admin.inventory.purchase.return') }}"
                                    title="Return Purchase">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        Return Purchase
                                    </span>
                                </a>
                            </li>
                        @endif
                        @if (hasPermission('purchase_bill', 'list'))
                            <li
                                class="nav-item {{ Request::is('billing/purchase-bills') ? 'active' : '' }}">
                                <a class="nav-link"
                                    href="{{ route('admin.billing.my-bills') }}"
                                    title="Purchase Bills">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        Purchase Bills
                                    </span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if (hasAnyPermission([
                    'stock_report.list',
                    'stock_report.export',
                    'sale_report.export',
                    'sale_report.list',
                    'profit_loss_summary.export',
                    'profit_loss_summary.list',
                    'purchase_report.export',
                    'purchase_report.list',
                ]))
                <li class="navbar-vertical-aside-has-menu ">
                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                        href="javascript:" title="Reports">
                        <i class="tio-document-outlined nav-icon"></i>
                        <span class=" text-truncate">
                            Reports
                        </span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: ">
                        @if (hasPermission('stock_report', 'list') || hasPermission('stock_report', 'export'))
                            <li class="nav-item ">
                                <a class="nav-link"
                                    href="{{ route('admin.inventory.report.stock') }}"
                                    title="Stock Report">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        Stock Report
                                    </span>
                                </a>
                            </li>
                        @endif
                        @if (hasPermission('sale_report', 'list') || hasPermission('sale_report', 'export'))
                            <li class="nav-item ">
                                <a class="nav-link"
                                    href="{{ route('admin.inventory.report.sale') }}"
                                    title="Sales Report">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        Sales Report
                                    </span>
                                </a>
                            </li>
                        @endif
                        @if (hasPermission('purchase_report', 'list') || hasPermission('purchase_report', 'export'))
                            <li class="nav-item ">
                                <a class="nav-link"
                                    href="{{ route('admin.inventory.report.purchase') }}"
                                    title="Purchase Report">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        Purchase Report
                                    </span>
                                </a>
                            </li>
                        @endif
                        @if (hasPermission('profit_loss_summary', 'list') || hasPermission('profit_loss_summary', 'export'))
                            <li class="nav-item ">
                                <a class="nav-link"
                                    href="{{ route('admin.inventory.report.profit-and-loss') }}"
                                    title="Profit & Loss Summary">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate sidebar--badge-container">
                                        Profit & Loss Summary
                                    </span>
                                </a>
                            </li>
                        @endif
                        <li class="nav-item ">
                            <a class="nav-link"
                                href="{{ route('admin.inventory.report.gst') }}"
                                title="GST Report">
                                <span class="tio-circle nav-indicator-icon"></span>
                                <span class="text-truncate sidebar--badge-container">
                                    GST Report
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endif
            <li
                class="navbar-vertical-aside-has-menu {{ Request::is('inventory/gatepass*') ? 'active' : '' }}">
                <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                    href="javascript:" title="Gatepass">
                    <i class="tio-shopping-cart nav-icon"></i>
                    <span class=" text-truncate">
                        Gatepass
                    </span>
                </a>
                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                    style="display: {{ Request::is('inventory/gatepass*') ? 'block' : 'none' }}">
                    <li class="nav-item  {{ Request::is('inventory') ? 'active' : '' }}">
                        <a class="nav-link"
                            href="{{ route('admin.inventory.gatepass.list', ['purchase']) }}"
                            title=" {{ translate('messages.purchase gatepass') }}">
                            <span class="tio-circle nav-indicator-icon"></span>
                            <span class="text-truncate sidebar--badge-container">
                                Purchase Gatepass
                            </span>
                        </a>
                    </li>
                    <li class="nav-item  {{ Request::is('inventory') ? 'active' : '' }}">
                        <a class="nav-link"
                            href="{{ route('admin.inventory.gatepass.list', ['sale']) }}"
                            title=" {{ translate('messages.sale gatepass') }}">
                            <span class="tio-circle nav-indicator-icon"></span>
                            <span class="text-truncate sidebar--badge-container">
                                Sale Gatepass
                            </span>
                        </a>
                    </li>
                </ul>
            </li>
            @if (hasPermission('inventory', 'settings'))
                <li
                    class="navbar-vertical-aside-has-menu {{ Request::is('inventory/settings') ? 'active' : '' }}">
                    <a class="nav-link " href="{{ route('admin.inventory.settings') }}"
                        title="{{ translate('messages.dashboard') }}">
                        <span class="tio-settings-outlined nav-icon"></span>
                        <span class="text-truncate">Inventory Settings</span>
                    </a>
                </li>
            @endif
        </ul>
    </li>
    {{-- ===================================== inventory END========================== --}}
@endif