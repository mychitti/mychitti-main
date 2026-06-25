@extends('layouts.vendor.app')

@section('title', 'Inventory Dashboard')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/tags-input.min.css') }}" rel="stylesheet">

    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <link href="{{ asset('public/assets/admin/css/inventory_management.css') }}" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .clickable-row {
            cursor: pointer;
        }

        .clickable-row:hover {
            background: rgba(102, 126, 234, 0.1);
        }

        .azure-gradient {
            background: linear-gradient(135deg, #dde3ff 0%, #f0e1ff 100%);
            color: white;
        }

        .azure-gradient2 {
            background: linear-gradient(135deg, #deffdd 0%, #e1fff9 100%);
            color: white;
        }

        .azure-gradient3 {
            background: linear-gradient(135deg, #fffbdd 0%, #ffe1e1 100%);
            color: white;
        }

        .emerald-glow {
            background: linear-gradient(135deg, #fffbdd 0%, #ffe1e1 100%);
            border-left: 4px solid #eebf30;
            box-shadow: 0 8px 32px rgba(16, 185, 129, 0.1);
        }

        .crimson-accent {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            border-left: 4px solid #ef4444;
            box-shadow: 0 8px 32px rgba(239, 68, 68, 0.1);
        }

        .neon-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .neon-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .quantum-badge {
            background: linear-gradient(45deg, #ff6b6b, #feca57);
            color: white;
            font-weight: 600;
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-size: 0.85rem;
        }

        .velocity-metric {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1e293b;
        }

        .stellar-icon {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .plasma-item {
            background: linear-gradient(135deg, #fbfcff 0%, #f3f3f3 100%);
            border-radius: 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .plasma-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.6), transparent);
            transition: left 0.6s;
        }

        .plasma-item:hover::before {
            left: 100%;
        }

        .plasma-item:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .fusion-img {
                margin-right: 8px;
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #666;
        }

        .cosmic-title {
            color: #1e293b;
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .nebula-stats {
            background: linear-gradient(135deg, #e9edff 0%, #ffffff 100%);
            border: 1px solid #d8d8d8;
            border-radius: 9px;
            padding: 2px 10px;
            font-weight: 600;
        }



        .activity_card,
        .card-header,
        .card-body p {
            color: black !important;
        }

        .inventory-card {
            border-radius: 1rem;
        }

        .stat-bar {}

        .stat-block {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: #fff;
            padding: .75rem 1.25rem;
            border-radius: 1rem;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
            transition: all .3s ease;
        }

        .stat-block:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.3rem;
        }

        /* Gradient backgrounds */
        .bg-gradient-primary {
            background: linear-gradient(135deg, #3f51b5, #5c6bc0);
        }

        .bg-gradient-danger {
            background: linear-gradient(135deg, #e53935, #ef5350);
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #43a047, #66bb6a);
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, #fb8c00, #ffb74d);
        }

        .stat-number {
            font-size: 1.4rem;
            font-weight: bold;
            color: black;
        }

        .stat-label {
            font-size: 14px;
            color: #6c757d;
        }

        .propular_badge {
            background: #ffde71 !important;
            box-shadow: 0px 2px 15px white;
            color: black;
        }

        .charts-wrapper {
            display: flex;
            gap: 50px;
            width: 100%;
        }

        .chart-container {
            width: 350px;
            height: 400px;
            position: relative;
            background: #fff;
            border-radius: 20px;
            width: 100%;
            {{-- box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); --}} {{-- padding-top: 20px; --}}
        }

        .center-icon {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 45px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #636e72;
        }

        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
    }
    @media (max-width: 1000px) {
        .stat-bar{
            gap: 8px;
            padding: 0;
        }
        .stat-bard-card{
            
        }
    }
    </style>
@endpush

@section('content')
    <div class="p-2">
        {{-- @include('vendor-views/sub-module/partials/inventory') --}}
        <div class="page-header d-flex flex-wrap justify-content-between">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Inventory Dashboard</h1>

            <div class="d-flex gap-2 align-items-center flex-wrap">
                @if(auth('vendor')->check())
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-toggle="modal" data-target="#defaultDashboardModal"
                        style="font-size:11px;font-weight:600;white-space:nowrap;">
                        <i class="tio-dashboard-outlined"></i>
                    </button>
                    @include('layouts.vendor.partials._default_dashboard_modal')
                @endif
                <form action="" class="d-flex date-range-form">
                    <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning" type="button"
                        data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
                    {{-- date range modal --}}
                    @include('vendor-views/form_modals/date_range')
                </form>
                <button type="button" class="btn btn--primary mb-0" data-toggle="modal" data-target="#calendarModal">
                    Calendar
                </button>
                @if (hasPermission('inventory_item_entry', 'add'))
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#itemEntryModal">Item
                        Entry</button>
                @endif
                @if (hasPermission('inventory_item', 'add'))
                    <button type="button" class="btn btn-primary" data-toggle="modal"
                        data-target="#addInventoryItemModal">Add
                        Item</button>
                @endif
            </div>
        </div>
        <div class="row g-0 p-2">
            <div class="col-md-8 p-2">
                <div class="card inventory-card shadow-sm border">
                    <div class="card-header bg-white fw-bold border-0"
                        style="border-radius: 15px 15px 0px 0px  !important;background: linear-gradient(135deg, #ffdfdf 0%, #ffecec 100%);">
                        Inventory
                        Snapshot</div>
                    <div class="card-body">
                        <div class="d-flex row col-12 g-0 justify-content-between stat-bar">
                            <div class="col-md-3 stat-bard-card">
                                <a href="{{ route('vendor.inventory.index') }}?type=product" class="stat-block ">
                                    <div class="stat-icon bg-gradient-primary">
                                        <i class="fa-solid fa-box"></i>
                                    </div>
                                    <div>
                                        <div class="stat-number">{{ $data['product_count'] }}</div>
                                        <div class="stat-label">Products</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 stat-bard-card">
                                <a href="{{ route('vendor.inventory.index') }}?type=service" class="stat-block ">
                                    <div class="stat-icon bg-gradient-danger">
                                        <i class="fa-solid fa-gears"></i>
                                    </div>
                                    <div>
                                        <div class="stat-number">{{ $data['service_count'] }}</div>
                                        <div class="stat-label">Services</div>
                                    </div>
                                </a>
                            </div>
                        
                            <div class="col-md-3 stat-bard-card">
                                <div class="stat-block ">
                                    <div class="stat-icon bg-gradient-warning">
                                        <i class="fa-solid fa-file-invoice"></i>
                                    </div>
                                    <div>
                                        <div class="stat-number">{{ _price($data['totalStockValue'], 0) }}</div>
                                        <div class="stat-label">Total Stock Value</div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <!-- Inventory Summary -->
            <div class="col-md-4 p-2">
                <div class="card card-soft ">
                    <div class="card-header bg-pastel-purple fw-bold">Inventory Summary</div>
                    <div class="card-body">
                        <p>Products Quantity in Hand: <span class="fw-semibold">{{ $data['product_stock'] }}</span></p>
                        <p>Services Quantity in Hand: <span class="fw-semibold">{{ $data['service_stock'] }}</span></p>
                    </div>
                </div>
            </div>
            <!-- Stock Overview Card -->
            {{-- <div class="col-md-4 p-2">
                <div class="card neon-card border-0 h-100">
                    <div class="card-header azure-gradient border-0">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Stock Overview
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="d-flex align-items-center crimson-accent p-3 rounded-3">
                                    <div class="stellar-icon me-3">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold">Low Stock Products</h6>
                                        <span class="velocity-metric" style="font-size: 1.8rem;">0</span>
                                    </div>
                                </div>
                            </div>


                            <div class="col-12">
                                <div class="d-flex align-items-center emerald-glow p-3 rounded-3">
                                    <div class="stellar-icon me-3"
                                        style="background: linear-gradient(45deg, #10b981, #34d399);">
                                        <i class="fas fa-boxes"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold">All Products</h6>
                                        <span class="velocity-metric" style="font-size: 1.8rem; color: #059669;">1</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-tools me-2 text-warning"></i>
                                        <span class="fw-semibold">Low Stock Services</span>
                                    </div>
                                    <span class="quantum-badge">1</span>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-concierge-bell me-2 text-info"></i>
                                        <span class="fw-semibold">All Services</span>
                                    </div>
                                    <span class="quantum-badge"
                                        style="background: linear-gradient(45deg, #3b82f6, #8b5cf6);">1</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> --}}

            <!-- Most Popular Items Card -->
            <div class="col-md-4 p-2">
                <div class="card neon-card border-0 h-100">
                    <div class="card-header azure-gradient2 border-0">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Stock Overview
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="space-y-3">
                            <!-- Two Wheeler Accessories -->
                            <div class="plasma-item p-3 mb-3">
                                <a href="{{ route('vendor.inventory.index') }}?filter=low_stock&type=product"
                                    class="d-flex align-items-center">
                                    <div class="stellar-icon  mr-3 me-3">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold">Low Stock Products</h6>
                                        <div class="d-flex align-items-center">
                                            <span class="nebula-stats"> {{ $data['low_stock_product'] }}</span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="plasma-item p-3 mb-3">
                                <a href="{{ route('vendor.inventory.index') }}?type=product"
                                    class="d-flex align-items-center">
                                    <div class="stellar-icon me-3 mr-3"
                                        style="background: linear-gradient(45deg, #10b981, #34d399);">
                                        <i class="fas fa-boxes"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold">All Products</h6>
                                        <div class="d-flex align-items-center">
                                            <span class="nebula-stats">{{ $data['product_count'] }}</span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-12 mb-3">
                                <a href="{{ route('vendor.inventory.index') }}?filter=low_stock&type=service"
                                    class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-tools me-2 mr-2 text-warning"></i>
                                        <span class="fw-semibold">Low Stock Services</span>
                                    </div>
                                    <span class="quantum-badge">{{ $data['low_stock_services'] }}</span>
                                </a>
                            </div>
                            <div class="col-12 mb-3">
                                <a href="{{ route('vendor.inventory.index') }}?type=service"
                                    class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-concierge-bell me-2 mr-2  text-info"></i>
                                        <span class="fw-semibold">All Services</span>
                                    </div>
                                    <span class="quantum-badge"
                                        style="background: linear-gradient(45deg, #3b82f6, #8b5cf6);">{{ $data['service_count'] }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Most Popular Items Card -->
            <div class="col-md-4 p-2">
                <div class="card neon-card border-0 h-100">
                    <div class="card-header azure-gradient border-0">
                        <h5 class="mb-0">
                            <i class="fas fa-fire me-2"></i>
                            Most Popular Items {{ translate($preset) }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="space-y-3">
                            @foreach ($data['top_sell'] as $key => $item)
                                <!-- Two Wheeler Accessories -->
                                <div class="plasma-item p-3 mb-3">
                                    <a href="{{ $item->item?->id ? route('vendor.inventory.item.detail', [$item->item?->id]) : '#'}}" class="d-flex align-items-center">
                                        <div class="fusion-img me-3">
                                            <img class="avatar avatar-lg onerror-image"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                    $item->item?->image ?? '',
                                                    asset('storage/app/public/inventory-item') . '/' . $item->item?->image ?? '',
                                                    asset('public/assets/admin/img/160x160/img2.jpg'),
                                                    'inventory-item/',
                                                ) }}"
                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                alt="{{ $item->item?->item_name }} image">
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold">{{ ucfirst($item->item?->item_name) }}</h6>
                                            <div class="d-flex align-items-center">
                                                <span class="nebula-stats"> {{ $item->total_sold }} sold</span>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="badge propular_badge">
                                                <i class="fas fa-trophy me-1"></i>
                                                Popular
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                            @if (!count($data['top_sell']))
                                <div class="d-flex align-items-center justify-content-center">

                                    <div class="empty-state">
                                        <div class="icon">📦</div>
                                        <p>No items sold {{ translate($preset) }}</p>

                                    </div>

                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Card -->
            <div class="col-md-4 p-2">
                <div class="card neon-card border-0 h-100">
                    <div class="card-header azure-gradient3 border-0">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-bag me-2"></i>
                            Orders by Category
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        {{-- <div class="mb-4">
                            <div class="stellar-icon mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <h3 class="cosmic-title text-center">Order Statistics</h3>
                        </div> --}}

                        <div class="row g-3">
                            <div class="charts-wrapper">
                                <div class="chart-container">
                                    <div id="chart1" style="width:100%;height:100%;"></div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
            @if (hasPermission('inventory_item_entry', 'list'))

                <div class="row g-0 mt-2 col-12 mb-2">
                    <!-- Sales Order -->
                    <div class="col-md-8">
                        <div class="card card-soft">
                            <div class="card-header bg-pastel-pink fw-bold d-flex justify-content-between">
                                <span>Recent Entries</span>
                                <a href="{{ route('vendor.inventory.index') }}?tab=entry" class=" ">View all</a>
                            </div>
                            <div class="card-body p-0">
                                <table id="datatable"
                                    class="table table-responsive table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="border-0">{{ translate('sl') }}</th>
                                            <th class="border-0">Item Name</th>
                                            <th class="border-0 ">Purchase Price</th>
                                            <th class="border-0">Date</th>
                                            <th class="border-0">Bill No.</th>
                                            <th class="border-0 ">Stock</th>
                                            <th class="border-0 ">Total</th>
                                            <th class="border-0 ">Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody id="set-rows">
                                        @foreach ($data['entries'] as $key => $entry)
                                            <tr class="clickable-row"
                                                onclick="window.location='{{ route('vendor.inventory.item.detail', [$entry->item?->id ?? 0]) }}'">
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <img class="avatar avatar-lg mr-3 onerror-image"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($entry->item?->image, asset('storage/app/public/inventory-item/') . '/' . $entry->item?->image, asset('public/assets/admin/img/160x160/img2.jpg'), 'inventory-item/') }}"
                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                        alt="{{ $entry->item?->item_name }} image">
                                                    {{ $entry->item?->item_name }}
                                                    {{ $entry->variation_type ? '(' . $entry->variation_type . ')' : '' }}
                                                </td>
                                                <td>
                                                    {{ _price($entry->landing_price) }}
                                                </td>
                                                <td>
                                                    {{ $entry->date }}
                                                </td>
                                                <td>
                                                    {{ $entry->bill_number }}
                                                </td>

                                                <td><span
                                                        class="badge badge-soft-success  ml-1">+{{ $entry->quantity }}</span>
                                                </td>
                                                <td>
                                                    {{ \App\CentralLogics\Helpers::format_currency($entry->total_amount) }}
                                                </td>
                                                <td>
                                                    {{ $entry->created_at }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                @if (count($data['entries']) === 0)
                                    <div class="empty--data">
                                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                            alt="public">
                                        <h5>
                                            {{ translate('no_data_found') }}
                                        </h5>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    {{-- <div class="col-md-4 mb-3">
                    <div class="card neon-card border-0">
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="p-3">
                                        <div class="stellar-icon mx-auto mb-2"
                                            style="background: linear-gradient(45deg, #06d6a0, #118ab2);">
                                            <i class="fas fa-chart-bar"></i>
                                        </div>
                                        <h6 class="fw-bold">Sales Growth</h6>
                                        <small class="text-muted">This Month</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-3">
                                        <div class="stellar-icon mx-auto mb-2"
                                            style="background: linear-gradient(45deg, #f72585, #b5179e);">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <h6 class="fw-bold">Customer Base</h6>
                                        <small class="text-muted">Growing</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-3">
                                        <div class="stellar-icon mx-auto mb-2"
                                            style="background: linear-gradient(45deg, #ffd60a, #ff8500);">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <h6 class="fw-bold">Reviews</h6>
                                        <small class="text-muted">5.0 Rating</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-3">
                                        <div class="stellar-icon mx-auto mb-2"
                                            style="background: linear-gradient(45deg, #7209b7, #560bad);">
                                            <i class="fas fa-rocket"></i>
                                        </div>
                                        <h6 class="fw-bold">Performance</h6>
                                        <small class="text-muted">Excellent</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> --}}

                </div>
            @endif
        </div>
    </div>
    @include('vendor-views.form_modals.pos_calendar')

    @include('vendor-views/form_modals/inventory_item_add')
    @include('vendor-views/form_modals/inventory_item_entry')
@endsection

@push('script_2')
    <script src="https://cdn.jsdelivr.net/npm/echarts/dist/echarts.min.js"></script>
    @include('vendor-views.salespoint.calendar-js')

    <script>
        function createDonutChart(domId, title, data) {
            var chart = echarts.init(document.getElementById(domId));

            var option = {
                title: {
                    text: title,
                    left: 'center',
                    top: 10,
                    textStyle: {
                        fontSize: 16,
                        fontWeight: 'bold'
                    }
                },
                tooltip: {
                    trigger: 'item',
                    formatter: '{b}<br/>{c} ({d}%)'
                },
                legend: {
                    orient: 'horizontal',
                    bottom: 0,
                    data: data.map(d => d.name)
                },
                series: [{
                        name: title,
                        type: 'pie',
                        radius: ['40%', '65%'],
                        avoidLabelOverlap: true,
                        label: {
                            show: true,
                            position: 'outside',
                            formatter: '{d}%\n{b}',
                            fontSize: 12,
                            fontWeight: 'bold'
                        },
                        labelLine: {
                            length: 5,
                            length2: 5,
                            smooth: true
                        },
                        data: data,
                        animationType: 'scale',
                        animationEasing: 'elasticOut',
                        animationDelay: function(idx) {
                            return idx * 200;
                        }
                    },
                    {
                        // Inner background ring
                        type: 'pie',
                        radius: ['25%', '40%'],
                        silent: true,
                        label: {
                            show: false
                        },
                        data: [{
                            value: 100,
                            itemStyle: {
                                color: '#f0f0f0'
                            }
                        }]
                    }
                ]
            };

            chart.setOption(option);
            return chart;
        }
        let topCategories = @json($data['top_categories']);
        let colors = ["#c5f8ff", "#daffb2", "#bbc7ff", "#ffd6a5", "#ffadad"];
        let chartData = topCategories.map((cat, index) => ({
            value: cat.total_sold,
            name: cat.name,
            itemStyle: {
                color: colors[index % colors.length]
            }
        }));

        // Chart 1
        createDonutChart('chart1', 'Orders by Category', chartData);
    </script>
    @include('vendor-views/js/inventory_management')
    @include('vendor-views/js/date_range')
    @include('vendor-views/js/uom_js')
@endpush
