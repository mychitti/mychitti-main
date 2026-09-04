@php
    $storeId = \App\CentralLogics\Helpers::get_store_id();

    // 1. Queue Today
    $todayPrescriptions = \App\Models\Prescription::where('store_id', $storeId)
        ->where('is_finalized', true)
        ->whereDate('created_at', today())
        ->get();
    $queueTodayCount = $todayPrescriptions->count();
    $pendingCount = $todayPrescriptions->filter(function($rx) {
        return $rx->items->where('dispensed', false)->count() > 0;
    })->count();
 
    // 2. Dispensed Today
    $dispensedTodayCount = $todayPrescriptions->filter(function($rx) {
        $total = $rx->items->count();
        $dispensed = $rx->items->where('dispensed', true)->count();
        return $total > 0 && $dispensed === $total;
    })->count();

    $dispensedValueToday = \App\Models\PrescriptionItem::whereHas('prescription', function($q) use ($storeId) {
            $q->where('store_id', $storeId)->whereDate('created_at', today());
        })
        ->where('dispensed', true)
        ->get()
        ->sum(function($item) {
            $price = $item->inventoryItem?->selling_price ?? 0;
            return ($item->dispensed_qty ?: $item->quantity ?: 1) * $price;
        });

    // 3. Low Stock Items
    $lowStockCount = \App\Models\InventoryItem::where('store_id', $storeId)
        ->where('item_type', 'product')
        ->where('stock', '>', 0)
        ->where('stock', '<', 5)
        ->count();

    // 4. Out of Stock Items
    $outOfStockCount = \App\Models\InventoryItem::where('store_id', $storeId)
        ->where('item_type', 'product')
        ->where('stock', '<=', 0)
        ->count();

    // 5. Expiring Soon Count (next 30 days)
    $expiringSoonCount = \App\Models\ItemEntry::where('store_id', $storeId)
        ->whereNotNull('expiry_date')
        ->whereDate('expiry_date', '>=', today())
        ->whereDate('expiry_date', '<=', today()->addDays(30))
        ->whereHas('item', function($q) {
            $q->where('stock', '>', 0);
        })
        ->count();

    // 6. Stock Value
    $stockItems = \App\Models\InventoryItem::where('store_id', $storeId)
        ->where('item_type', 'product')
        ->get();
    $totalStockValue = $stockItems->sum(function($item) {
        return $item->stock * $item->selling_price;
    });
    $totalItemsCount = $stockItems->sum('stock');

    // 7. Purchase Orders Count
    $poCount = \App\Models\PurchaseOrder::whereHas('inventoryItem', function($q) use ($storeId) {
        $q->where('store_id', $storeId);
    })->count();

    // Get current pharmacist user
    $currentUser = auth('vendor_employee')->user() ?? auth('vendor')->user();
    $currentUserName = trim(($currentUser?->f_name ?? 'Pharmacist') . ' ' . ($currentUser?->l_name ?? ''));
    $currentUserInitials = '';
    if ($currentUser) {
        $currentUserInitials = strtoupper(substr($currentUser->f_name ?? 'P', 0, 1) . substr($currentUser->l_name ?? 'H', 0, 1));
    } else {
        $currentUserInitials = 'PH';
    }

    // Determine active tabs 
    $currentRouteName = Route::currentRouteName();
    $currentTab = request('tab');
    $currentFilter = request('filter');

    $isDashboardActive = ($currentRouteName === 'vendor.inventory.dashboard');
    $isDispenseQueueActive = ($currentRouteName === 'vendor.prescription.dispense.queue');
    $isProductsServicesActive = (($currentRouteName === 'vendor.inventory.index' && $currentTab !== 'entry' && $currentFilter !== 'low_stock') || ($currentRouteName === 'vendor.inventory.storage-spaces'));
    $isStockManagementActive = ($currentRouteName === 'vendor.inventory.stock.stock-in-out' || ($currentRouteName === 'vendor.inventory.index' && $currentTab === 'entry'));
    $isSalesActive = Str::contains($currentRouteName, 'vendor.inventory.sale');
    $isPurchaseActive = (Str::contains($currentRouteName, 'vendor.inventory.purchase') || $currentRouteName === 'vendor.invoice.my-bills');
    $isGatepassActive = Str::contains($currentRouteName, 'vendor.inventory.gatepass');
    $isReportsActive = Str::contains($currentRouteName, 'vendor.inventory.report');
    $isSettingsActive = ($currentRouteName === 'vendor.inventory.settings');
@endphp

@push('css_or_js')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ── Layout Integrations ── */
        main.main {
            background-color: #f8fafc !important;
            font-family: 'Inter', sans-serif !important;
        }
        .content.container-fluid { 
            padding: 0 !important;  
            margin: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
        }

        /* ── Branding Top Bar ── */
        .pharmacy-top-bar {
            background-color: #0b2545; /* Navy Blue */
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .pharmacy-top-bar .brand-logo {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 19px;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .pharmacy-top-bar .brand-logo span.highlight {
            color: #60a5fa; /* Vibrant blue accent */
        }
        .pharmacy-top-bar .live-badge {
            background-color: rgba(52, 211, 153, 0.15);
            border: 1px solid #34d399;
            color: #34d399;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .pharmacy-top-bar .live-dot {
            width: 6px;
            height: 6px;
            background-color: #34d399;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 6px #34d399;
        }
        .pharmacy-top-bar .right-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .btn-back-dashboard {
            background-color: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #ffffff !important;
            padding: 5px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none !important;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-back-dashboard:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: #ffffff;
        }
        .pharmacist-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 13px;
        }
        .pharmacist-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background-color: #3b82f6;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 11px;
        }

        /* ── Metrics Grid ── */
        .metrics-row {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            background: #ffffff;
            border-bottom: 1px solid #c8d2e0;
            padding: 12px 24px;
            gap: 10px;
        }
        .metric-card {
            padding: 8px 12px;
            border-right: 1px solid #dfe6ee;
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .metric-card:hover {
            transform: translateY(-2px);
        }
        .metric-card:last-child {
            border-right: none;
        }
        .metric-card .value {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            font-family: 'Outfit', sans-serif;
            line-height: 1.2;
            display: flex;
            align-items: baseline;
            gap: 2px;
        }
        .metric-card .subtext {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
            font-weight: 500;
        }

        /* ── Tabs Row ── */
        .pharmacy-tabs-row {
            background-color: #ffffff;
            border-bottom: 1px solid #c8d2e0;
            padding: 0 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            overflow: visible !important;
        }
        .pharmacy-tab-dropdown {
            position: relative;
            display: inline-block;
        }
        .pharmacy-tab-dropdown .dropdown-content {
            display: none;
            position: absolute;
            background-color: #ffffff;
            min-width: 200px;
            box-shadow: 0px 8px 24px rgba(0,0,0,0.12);
            border: 1px solid #c8d2e0;
            border-radius: 8px;
            z-index: 1000;
            margin-top: 2px;
            padding: 4px 0;
            left: 0;
        }
        .pharmacy-tab-dropdown:hover .dropdown-content {
            display: block;
        }
        .pharmacy-tab-dropdown .dropdown-content a {
            color: #475569;
            padding: 10px 16px;
            text-decoration: none !important;
            display: block;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
            text-align: left;
        }
        .pharmacy-tab-dropdown .dropdown-content a:hover {
            background-color: #f1f5f9;
            color: #0f172a;
        }
        .pharmacy-tab-dropdown .dropdown-content a.active {
            background-color: #eff6ff;
            color: #3b82f6;
            font-weight: 600;
        }
        .pharmacy-tab-btn i.tio-chevron-down {
            transition: transform 0.2s;
        }
        .pharmacy-tab-dropdown:hover .pharmacy-tab-btn i.tio-chevron-down {
            transform: rotate(180deg);
        }
        .pharmacy-tab-btn {
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 12px 14px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            white-space: nowrap;
            text-decoration: none !important;
        }
        .pharmacy-tab-btn:hover {
            color: #0f172a;
            background-color: #f8fafc;
        }
        .pharmacy-tab-btn.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }
        .pharmacy-tab-btn .badge-count {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 9px;
            padding: 1px 5px;
            border-radius: 10px;
            font-weight: 700;
        }
        .pharmacy-tab-btn.active .badge-count {
            background-color: #eff6ff;
            color: #3b82f6;
        }
        .pharmacy-tab-btn .badge-count.red-badge { background-color: #fee2e2; color: #ef4444; }
        .pharmacy-tab-btn .badge-count.orange-badge { background-color: #ffedd5; color: #ea580c; }
        .pharmacy-tab-btn .badge-count.blue-badge { background-color: #dbeafe; color: #2563eb; }
        .pharmacy-tab-btn .badge-count.purple-badge { background-color: #f3e8ff; color: #9333ea; }
        
        /* General page wrapper overrides to match the premium theme */
        .pharmacy-page-content {
            padding: 20px 24px !important;
            box-sizing: border-box;
        }
        .pharmacy-page-content .card {
            border: 1px solid #c8d2e0 !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02) !important;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .pharmacy-page-content .card-header {
            border-bottom: 1px solid #c8d2e0 !important;
            padding: 14px 20px !important;
            background: #ffffff !important;
        }
        .pharmacy-page-content .card-header h3, 
        .pharmacy-page-content .page-header-title {
            font-family: 'Outfit', sans-serif !important;
            font-size: 15px !important;
            font-weight: 700 !important;
            color: #0f172a !important;
        }
        .pharmacy-page-content .btn, 
        .pharmacy-page-content .btn_sm {
            border-radius: 8px !important;
            font-weight: 600 !important;
        }
        .pharmacy-page-content input, 
        .pharmacy-page-content select, 
        .pharmacy-page-content textarea {
            border-radius: 8px !important;
            border-color: #cbd5e1 !important;
        }

        /* ── Phone / small tablet ──
           Six fixed metric columns plus 24px gutters are wider than a phone
           viewport, which scrolls the whole page sideways and clips the tabs and
           dropdowns. Fold the metrics down and shrink the gutters. */
        @media (max-width: 991px) {
            .metrics-row {
                grid-template-columns: repeat(3, 1fr);
                padding: 10px 14px;
            }
            .metric-card:nth-child(3n) { border-right: none; }
            .pharmacy-tabs-row { padding: 0 10px; }
            .pharmacy-top-bar { padding: 10px 14px; }
            .pharmacy-page-content { padding: 14px !important; }
        }
        @media (max-width: 575px) {
            .metrics-row {
                grid-template-columns: repeat(2, 1fr);
                padding: 8px 10px;
                gap: 6px;
            }
            .metric-card {
                padding: 6px 8px;
                border-right: none;
                border-bottom: 1px solid #dfe6ee;
            }
            .metric-card:nth-last-child(-n+2) { border-bottom: none; }
            .metric-card .value { font-size: 16px; }
            .metric-card .subtext { font-size: 10px; line-height: 1.35; }
            .pharmacy-top-bar { flex-wrap: wrap; gap: 8px; }
            .pharmacy-top-bar .brand-logo { font-size: 16px; }
            .pharmacy-top-bar .right-actions { gap: 8px; }
            .pharmacy-tab-btn { padding: 9px 10px; font-size: 11px; }
            .pharmacy-tab-dropdown .dropdown-content { min-width: 170px; }
            .pharmacy-page-content { padding: 10px !important; }
        }
    </style>
@endpush

{{-- 2. METRICS ROW --}}
<div class="metrics-row">
    <div class="metric-card">
        <div class="value" style="color: #2563eb;">{{ $queueTodayCount }}</div>
        <div class="subtext">QUEUE TODAY<br><span style="color: #3b82f6; font-weight: 700;">{{ $pendingCount }} pending</span></div>
    </div>
    <div class="metric-card">
        <div class="value" style="color: #10b981;">{{ $dispensedTodayCount }}</div>
        <div class="subtext">DISPENSED<br><span style="color: #16a34a; font-weight: 700;">{{ \App\CentralLogics\Helpers::format_currency($dispensedValueToday) }} value</span></div>
    </div>
    <div class="metric-card">
        <div class="value" style="color: #ea580c;">{{ $lowStockCount }}</div>
        <div class="subtext">LOW STOCK<br><span style="color: #ea580c; font-weight: 700;">Reorder needed</span></div>
    </div>
    <div class="metric-card">
        <div class="value" style="color: #ef4444;">{{ $outOfStockCount }}</div>
        <div class="subtext">OUT OF STOCK<br><span style="color: #dc2626; font-weight: 700;">Urgent reorder</span></div>
    </div>
    <div class="metric-card"> 
        <div class="value" style="color: #8b5cf6;">{{ $expiringSoonCount }}</div>
        <div class="subtext">EXPIRING SOON<br><span style="color: #7c3aed; font-weight: 700;">&lt;30 days</span></div>
    </div>
    <div class="metric-card">
        <div class="value">{{ \App\CentralLogics\Helpers::format_currency($totalStockValue) }}</div>
        <div class="subtext">STOCK VALUE<br><span style="font-weight: 700;">{{ number_format($totalItemsCount) }} items</span></div>
    </div> 
</div>

{{-- 3. TABS ROW ── --}}
<div class="pharmacy-tabs-row">
    {{-- Sales — what the counter actually took. First and default: it is the question asked on
         arrival every morning, where Medicines & Stock is what somebody opens occasionally to
         correct a price or take stock in. Needs only 'view', so unlike the sale counter itself
         it is safe as a landing page for everyone who can open the module at all. --}}
    @if (Route::has('vendor.pharmacy.sales') && hasPermission('pharmacy', 'view'))
        <a href="{{ route('vendor.pharmacy.sales') }}" class="pharmacy-tab-btn {{ Str::contains($currentRouteName, 'vendor.pharmacy.sales') ? 'active' : '' }}">
            Sales
        </a>
    @endif

    {{-- Medicines & Stock — FREE basic pharmacy --}}
    @if (Route::has('vendor.pharmacy.medicines'))
        <a href="{{ route('vendor.pharmacy.medicines') }}" class="pharmacy-tab-btn {{ Str::contains($currentRouteName, 'vendor.pharmacy.medicines') ? 'active' : '' }}">
            Medicines &amp; Stock
        </a>
    @endif

    {{-- Walk-in Sale — over-the-counter pharmacy sale --}}
    @if (Route::has('vendor.pharmacy.walkin') && hasPermission('pharmacy', 'dispense'))
        <a href="{{ route('vendor.pharmacy.walkin') }}" class="pharmacy-tab-btn {{ Str::contains($currentRouteName, 'vendor.pharmacy.walkin') ? 'active' : '' }}">
            Walk-in Sale
        </a>
    @endif

    {{-- Banned / Blocked Items --}}
    @if (Route::has('vendor.pharmacy.banned-items') && hasPermission('pharmacy', 'view'))
        <a href="{{ route('vendor.pharmacy.banned-items') }}" class="pharmacy-tab-btn {{ Str::contains($currentRouteName, 'vendor.pharmacy.banned-items') ? 'active' : '' }}">
            Banned Items
        </a>
    @endif

    {{-- Sale Orders (free pharmacy view) --}}
    @if (Route::has('vendor.pharmacy.sale-orders') && hasPermission('pharmacy', 'view'))
        <a href="{{ route('vendor.pharmacy.sale-orders') }}" class="pharmacy-tab-btn {{ Str::contains($currentRouteName, 'vendor.pharmacy.sale-orders') ? 'active' : '' }}">
            Sale Orders
        </a>
    @endif

    {{-- Dashboard Tab --}}
    @if (hasPermission('inventory', 'dashboard'))
        <a href="{{ route('vendor.inventory.dashboard') }}" class="pharmacy-tab-btn {{ $isDashboardActive ? 'active' : '' }}">
            Pharmacy Dashboard
        </a>
    @endif

    {{-- Dispense Queue Dropdown Tab --}}
    @if (hasPermission('pharmacy_dispense_queue', 'list'))
        <div class="pharmacy-tab-dropdown">
            <a href="javascript:;" class="pharmacy-tab-btn {{ $isDispenseQueueActive ? 'active' : '' }}">
                Dispense Queue <span class="badge-count red-badge">{{ $pendingCount }}</span> <i class="tio-chevron-down" style="font-size: 10px;"></i>
            </a>
            <div class="dropdown-content">
                <a href="{{ route('vendor.prescription.dispense.queue') }}" class="{{ ($currentRouteName === 'vendor.prescription.dispense.queue' && $currentFilter !== 'all') ? 'active' : '' }}">
                    Active Queue <span class="badge-count red-badge">{{ $pendingCount }}</span>
                </a>
                <a href="{{ route('vendor.prescription.dispense.queue', ['filter' => 'all']) }}" class="{{ ($currentRouteName === 'vendor.prescription.dispense.queue' && $currentFilter === 'all') ? 'active' : '' }}">
                    Dispensing History
                </a>
            </div>
        </div>
    @endif

    {{-- Products & Services Dropdown Tab --}}
    <div class="pharmacy-tab-dropdown">
        <a href="javascript:;" class="pharmacy-tab-btn {{ $isProductsServicesActive ? 'active' : '' }}">
            Products & Services <i class="tio-chevron-down" style="font-size: 10px;"></i>
        </a>
        <div class="dropdown-content">
            <a href="{{ route('vendor.inventory.index') }}" class="{{ ($currentRouteName === 'vendor.inventory.index' && $currentTab !== 'entry' && $currentFilter !== 'low_stock') ? 'active' : '' }}">
                Item Adding Book
            </a>
            @if (hasAnyPermission(['inventory_storage_units.list', 'inventory_storage_units.add']))
                <a href="{{ route('vendor.inventory.storage-spaces') }}" class="{{ ($currentRouteName === 'vendor.inventory.storage-spaces') ? 'active' : '' }}">
                    Storage Units
                </a>
            @endif
        </div>
    </div>

    {{-- Stock Management Dropdown Tab --}}
    @if (hasAnyPermission(['inventory_stock_in_out.list']))
        <div class="pharmacy-tab-dropdown">
            <a href="javascript:;" class="pharmacy-tab-btn {{ $isStockManagementActive ? 'active' : '' }}">
                Stock Management <i class="tio-chevron-down" style="font-size: 10px;"></i>
            </a>
            <div class="dropdown-content">
                @if (hasPermission('inventory_stock_in_out', 'list'))
                    <a href="{{ route('vendor.inventory.stock.stock-in-out') }}" class="{{ ($currentRouteName === 'vendor.inventory.stock.stock-in-out') ? 'active' : '' }}">
                        Stock in / Stock out
                    </a>
                @endif
                <a href="{{ route('vendor.inventory.index', ['tab' => 'entry']) }}" class="{{ ($currentRouteName === 'vendor.inventory.index' && $currentTab === 'entry') ? 'active' : '' }}">
                    Receive Stock
                </a>
            </div>
        </div>
    @endif

    {{-- Sales Dropdown Tab --}}
    @if (hasAnyPermission([
        'inventory_sale_order.list',
        'inventory_sale_order.export',
        'inventory_sale_return.export',
        'inventory_sale_return.list',
    ]))
        <div class="pharmacy-tab-dropdown">
            <a href="javascript:;" class="pharmacy-tab-btn {{ $isSalesActive ? 'active' : '' }}">
                Sales <i class="tio-chevron-down" style="font-size: 10px;"></i>
            </a>
            <div class="dropdown-content">
                @if (hasPermission('inventory_sale_order', 'list') || hasPermission('inventory_sale_order', 'export'))
                    <a href="{{ route('vendor.inventory.sale.orders') }}" class="{{ ($currentRouteName === 'vendor.inventory.sale.orders') ? 'active' : '' }}">
                        Sale Orders
                    </a>
                @endif
                @if (hasPermission('inventory_sale_return', 'list') || hasPermission('inventory_sale_return', 'export'))
                    <a href="{{ route('vendor.inventory.sale.orders-return') }}" class="{{ ($currentRouteName === 'vendor.inventory.sale.orders-return') ? 'active' : '' }}">
                        Return Orders
                    </a>
                @endif
            </div>
        </div>
    @endif

    {{-- Purchase Dropdown Tab --}}
    @if (hasAnyPermission([
        'inventory_purchase_return.add',
        'inventory_purchase_return.list',
        'inventory_purchase_order.add',
        'inventory_purchase_order.list',
        'purchase_bill.list',
    ]))
        <div class="pharmacy-tab-dropdown">
            <a href="javascript:;" class="pharmacy-tab-btn {{ $isPurchaseActive ? 'active' : '' }}">
                Purchase <i class="tio-chevron-down" style="font-size: 10px;"></i>
            </a>
            <div class="dropdown-content">
                @if (hasPermission('inventory_purchase_order', 'add') || hasPermission('inventory_purchase_order', 'list'))
                    <a href="{{ route('vendor.inventory.purchase.orders') }}" class="{{ ($currentRouteName === 'vendor.inventory.purchase.orders') ? 'active' : '' }}">
                        Purchase Orders <span class="badge-count blue-badge">{{ $poCount }}</span>
                    </a>
                @endif
                @if (hasPermission('inventory_purchase_return', 'add') || hasPermission('inventory_purchase_return', 'list'))
                    <a href="{{ route('vendor.inventory.purchase.return') }}" class="{{ ($currentRouteName === 'vendor.inventory.purchase.return') ? 'active' : '' }}">
                        Return Purchase
                    </a>
                @endif
                @if (hasPermission('purchase_bill', 'list'))
                    <a href="{{ route('vendor.invoice.my-bills') }}" class="{{ ($currentRouteName === 'vendor.invoice.my-bills') ? 'active' : '' }}">
                        Purchase Bills
                    </a>
                @endif
            </div>
        </div>
    @endif

    {{-- Gatepass Dropdown Tab --}}
    <div class="pharmacy-tab-dropdown">
        <a href="javascript:;" class="pharmacy-tab-btn {{ $isGatepassActive ? 'active' : '' }}">
            Gatepass <i class="tio-chevron-down" style="font-size: 10px;"></i>
        </a>
        <div class="dropdown-content">
            <a href="{{ route('vendor.inventory.gatepass.list', ['purchase']) }}" class="{{ ($currentRouteName === 'vendor.inventory.gatepass.list' && Str::contains(request()->url(), 'purchase')) ? 'active' : '' }}">
                Purchase Gatepass
            </a>
            <a href="{{ route('vendor.inventory.gatepass.list', ['sale']) }}" class="{{ ($currentRouteName === 'vendor.inventory.gatepass.list' && Str::contains(request()->url(), 'sale')) ? 'active' : '' }}">
                Sale Gatepass
            </a>
        </div>
    </div>

    {{-- Reports Dropdown Tab --}}
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
        <div class="pharmacy-tab-dropdown">
            <a href="javascript:;" class="pharmacy-tab-btn {{ $isReportsActive ? 'active' : '' }}">
                Reports <i class="tio-chevron-down" style="font-size: 10px;"></i>
            </a>
            <div class="dropdown-content">
                @if (hasPermission('stock_report', 'list') || hasPermission('stock_report', 'export'))
                    <a href="{{ route('vendor.inventory.report.stock') }}" class="{{ ($currentRouteName === 'vendor.inventory.report.stock') ? 'active' : '' }}">
                        Stock Report
                    </a>
                @endif
                @if (hasPermission('sale_report', 'list') || hasPermission('sale_report', 'export'))
                    <a href="{{ route('vendor.inventory.report.sale') }}" class="{{ ($currentRouteName === 'vendor.inventory.report.sale') ? 'active' : '' }}">
                        Sales Report
                    </a>
                @endif
                @if (hasPermission('purchase_report', 'list') || hasPermission('purchase_report', 'export'))
                    <a href="{{ route('vendor.inventory.report.purchase') }}" class="{{ ($currentRouteName === 'vendor.inventory.report.purchase') ? 'active' : '' }}">
                        Purchase Report
                    </a>
                @endif
                @if (hasPermission('profit_loss_summary', 'list') || hasPermission('profit_loss_summary', 'export'))
                    <a href="{{ route('vendor.inventory.report.profit-and-loss') }}" class="{{ ($currentRouteName === 'vendor.inventory.report.profit-and-loss') ? 'active' : '' }}">
                        Profit & Loss Summary
                    </a>
                @endif
                <a href="{{ route('vendor.inventory.report.gst') }}" class="{{ ($currentRouteName === 'vendor.inventory.report.gst') ? 'active' : '' }}">
                    GST Report
                </a>
                <a href="{{ route('vendor.inventory.report.batch-expiry') }}" class="{{ ($currentRouteName === 'vendor.inventory.report.batch-expiry') ? 'active' : '' }}">
                    Expiry Tracker <span class="badge-count purple-badge">{{ $expiringSoonCount }}</span>
                </a>
            </div>
        </div>
    @endif

    {{-- Settings Tab --}}
    @if (hasPermission('inventory', 'settings'))
        <a href="{{ route('vendor.inventory.settings') }}" class="pharmacy-tab-btn {{ $isSettingsActive ? 'active' : '' }}">
            Pharmacy Settings
        </a>
    @endif
</div>
