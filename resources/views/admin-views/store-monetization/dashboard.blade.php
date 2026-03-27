@extends('layouts.admin.app')

@section('title', 'Store Monetization Dashboard')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">

    <style>
        /* Hero Stats */
        .hero-stat {
            border-radius: 14px;
            padding: 22px 20px;
            position: relative;
            overflow: hidden;
            color: #fff;
            min-height: 130px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .hero-stat:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .hero-stat .hero-icon {
            position: absolute;
            right: 16px;
            top: 16px;
            font-size: 38px;
            opacity: 0.25;
        }

        .hero-stat h2 {
            font-size: 28px;
            font-weight: 800;
            margin: 0 0 2px;
        }

        .hero-stat .hero-label {
            font-size: 13px;
            font-weight: 500;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .hero-stat .hero-sub {
            font-size: 13px;
            margin-top: 6px;
        }

        .hero-stores {
            background: linear-gradient(135deg, #1565c0, #1e88e5);
        }

        .hero-pos {
            background: linear-gradient(135deg, #2e7d32, #43a047);
        }

        .hero-leads {
            background: linear-gradient(135deg, #e65100, #ff8f00);
        }

        .hero-staff {
            background: linear-gradient(135deg, #6a1b9a, #8e24aa);
        }

        /* Module Mini Cards */
        .module-card {
            background: #fff;
            border-radius: 10px;
            padding: 16px 14px;
            text-align: center;
            {{-- border-left: 4px solid #dee2e6; --}}
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .module-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .module-card h4 {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 2px;
            color: #334257;
        }

        .module-card p {
            color: #737883;
            margin: 0;
            font-size: 12px;
            font-weight: 500;
        }

        .module-card .module-icon {
            font-size: 18px;
            margin-bottom: 6px;
            display: block;
        }

        .mc-clients {
            background-color: #4ea6ff36;
        }

        .mc-clients .module-icon {
            color: #1976d2;
        }

        .mc-projects {
            background-color: #4cffed38;
        }

        .mc-projects .module-icon {
            color: #00897b;
        }

        .mc-tasks {
            background-color: #ffa54b38;
        }

        .mc-tasks .module-icon {
            color: #f57c00;
        }

        .mc-quotations {
            background-color: #8041ff38;
        }

        .mc-quotations .module-icon {
            color: #5e35b1;
        }

        .mc-inventory {
            background-color: #ff6b3e38;
        }

        .mc-inventory .module-icon {
            color: #d84315;
        }

        .mc-services {
            background-color: #4cf0ff38;
        }

        .mc-services .module-icon {
            color: #00838f;
        }

        .mc-banners {
            background-color: #ff575738;
        }

        .mc-banners .module-icon {
            color: #c62828;
        }

        .mc-templates {
            background-color: #7345ff38;
        }

        .mc-templates .module-icon {
            color: #4527a0;
        }

        .mc-ads {
            background-color: #ff953e38;
        }

        .mc-ads .module-icon {
            color: #ef6c00;
        }

        .mc-accounts {
            background-color: #55ff5d38;
        }

        .mc-accounts .module-icon {
            color: #2e7d32;
        }

        /* Top Stores Cards */
        .top-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .top-card .card-header {
            border-bottom: 2px solid;
            padding: 14px 18px;
            background: #fff;
        }

        .top-card .card-header h6 {
            font-weight: 700;
            font-size: 14px;
            margin: 0;
        }

        .tc-pos .card-header {
            border-color: #43a047;
            color: #2e7d32;
        }

        .tc-revenue .card-header {
            border-color: #1e88e5;
            color: #1565c0;
        }

        .tc-staff .card-header {
            border-color: #8e24aa;
            color: #6a1b9a;
        }

        .tc-leads .card-header {
            border-color: #ff8f00;
            color: #e65100;
        }

        .tc-wallet .card-header {
            border-color: #00897b;
            color: #00695c;
        }

        .tc-clients .card-header {
            border-color: #1976d2;
            color: #0d47a1;
        }

        .tc-projects .card-header {
            border-color: #5e35b1;
            color: #4527a0;
        }

        .tc-inventory .card-header {
            border-color: #d84315;
            color: #bf360c;
        }

        .top-store-item {
            display: flex;
            align-items: center;
            padding: 10px 18px;
            border-bottom: 1px solid #f5f5f5;
            transition: background 0.15s;
        }

        .top-store-item:hover {
            background: #fafafa;
        }

        .top-store-item:last-child {
            border-bottom: none;
        }

        .top-store-rank {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .rank-1 {
            background: #fff8e1;
            color: #f9a825;
        }

        .rank-2 {
            background: #eceff1;
            color: #546e7a;
        }

        .rank-3 {
            background: #fbe9e7;
            color: #d84315;
        }

        .rank-default {
            background: #f5f5f5;
            color: #9e9e9e;
        }

        .top-store-name {
            flex: 1;
            font-weight: 500;
            color: #334257;
            font-size: 13px;
            text-decoration: none;
        }

        .top-store-name:hover {
            color: #1565c0;
        }

        .top-store-value {
            font-weight: 700;
            font-size: 13px;
            color: #334257;
            white-space: nowrap;
        }

        /* Store Table */
        .store-table-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .store-table-card .card-header {
            background: #fff;
            border-bottom: 1px solid #eee;
            padding: 16px 20px;
        }

        .store-table-card .table thead th {
            background: #f8f9fa;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #737883;
            padding: 10px 12px;
            border: none;
        }

        .store-table-card .table tbody td {
            padding: 12px;
            vertical-align: middle;
            font-size: 13px;
            border-bottom: 1px solid #f5f5f5;
        }

        .store-table-card .table tbody tr:hover {
            background: #f8fbff;
        }

        .section-heading {
            font-size: 16px;
            font-weight: 700;
            color: #334257;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-heading i {
            color: #737883;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center py-2">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">Store Monetization Dashboard</h1>
                    <p class="page-header-text m-0">Monitor store performance, revenue, and engagement across the platform
                    </p>
                </div>
                <!-- Date Range Filter -->
                <form method="GET" action="{{ route('admin.store-monetization.dashboard') }}" class="date-range-form mb-3">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning" type="button"
                        data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
                    @include('vendor-views/form_modals/date_range')
                </form>
            </div>
        </div>



        <!-- Hero Stats -->
        <div class="row mb-4" style="gap: 0;">
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="hero-stat hero-stores">
                    <i class="tio-shop hero-icon"></i>
                    <h2 class="text-white">{{ $platformStats['active_stores'] }}</h2>
                    <div class="hero-label">Active Stores</div>
                    <div class="hero-sub">{{ $platformStats['total_stores'] }} total registered</div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="hero-stat hero-pos">
                    <i class="tio-receipt hero-icon"></i>
                    <h2 class="text-white">{{ number_format($platformStats['total_pos_tokens']) }}</h2>
                    <div class="hero-label">POS Tokens</div>
                    <div class="hero-sub">
                        {{ \App\CentralLogics\Helpers::format_currency($platformStats['total_pos_revenue']) }} revenue</div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="hero-stat hero-leads">
                    <i class="tio-user-outlined hero-icon"></i>
                    <h2 class="text-white">{{ number_format($platformStats['total_leads']) }}</h2>
                    <div class="hero-label">Total Leads</div>
                    <div class="hero-sub">{{ $platformStats['completed_leads'] }} completed</div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="hero-stat hero-staff">
                    <i class="tio-group-senior hero-icon"></i>
                    <h2 class="text-white">{{ number_format($platformStats['total_staff']) }}</h2>
                    <div class="hero-label">Total Staff</div>
                    <div class="hero-sub">
                        {{ \App\CentralLogics\Helpers::format_currency($platformStats['total_wallet_balance']) }} wallet
                        balance</div>
                </div>
            </div>
        </div>

        <!-- Module Stats -->
        <div class="section-heading"><i class="tio-layers-outlined"></i> Module Overview</div>
        <div class="row mb-4">
            <div class="col-6 col-md-4 col-lg mb-3">
                <div class="module-card mc-clients">
                    <i class="tio-user module-icon"></i>
                    <h4>{{ number_format($platformStats['total_clients']) }}</h4>
                    <p>Clients</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg mb-3">
                <div class="module-card mc-projects">
                    <i class="tio-folder-outlined module-icon"></i>
                    <h4>{{ number_format($platformStats['total_projects']) }}</h4>
                    <p>Projects</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg mb-3">
                <div class="module-card mc-tasks">
                    <i class="tio-checkmark-square-outlined module-icon"></i>
                    <h4>{{ number_format($platformStats['total_tasks']) }}</h4>
                    <p>Tasks</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg mb-3">
                <div class="module-card mc-quotations">
                    <i class="tio-document-text-outlined module-icon"></i>
                    <h4>{{ number_format($platformStats['total_quotations']) }}</h4>
                    <p>Quotations</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg mb-3">
                <div class="module-card mc-inventory">
                    <i class="tio-archive module-icon"></i>
                    <h4>{{ number_format($platformStats['total_inventory_items']) }}</h4>
                    <p>Inventory</p>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            {{-- <div class="col-6 col-md-4 col-lg mb-3">
                <div class="module-card mc-services">
                    <i class="tio-settings-outlined module-icon"></i>
                    <h4>{{ number_format($platformStats['total_services']) }}</h4>
                    <p>Services</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg mb-3">
                <div class="module-card mc-banners">
                    <i class="tio-image module-icon"></i>
                    <h4>{{ number_format($platformStats['total_banners']) }}</h4>
                    <p>Banners</p>
                </div>
            </div> --}}
            <div class="col-6 col-md-4 col-lg mb-3">
                <div class="module-card mc-templates">
                    <i class="tio-browser-windows module-icon"></i>
                    <h4>{{ number_format($platformStats['total_template_purchases']) }}</h4>
                    <p>Templates Purchased</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg mb-3">
                <div class="module-card mc-ads">
                    <i class="tio-notifications module-icon"></i>
                    <h4>{{ number_format($platformStats['total_ads_posted']) }}</h4>
                    <p>Ads Posted</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg mb-3">
                <div class="module-card mc-accounts">
                    <i class="tio-calculator module-icon"></i>
                    <h4>{{ number_format($platformStats['total_vouchers']) }}</h4>
                    <p>Vouchers</p>
                </div>
            </div>
        </div>

        <!-- Top Stores Section -->
        <div class="section-heading"><i class="tio-star-outlined"></i> Top Performing Stores</div>
        <div class="row mb-4">
            <!-- Highest POS Usage -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card top-card tc-pos h-100">
                    <div class="card-header">
                        <h6><i class="tio-receipt mr-1"></i> Highest POS Usage</h6>
                    </div>
                    <div class="card-body p-0">
                        @forelse($topPosByUsage as $index => $store)
                            <div class="top-store-item">
                                <span
                                    class="top-store-rank {{ $index < 3 ? 'rank-' . ($index + 1) : 'rank-default' }}">{{ $index + 1 }}</span>
                                <a href="{{ route('admin.store-monetization.store-detail', $store->store_id) }}"
                                    class="top-store-name">{{ $store->store_name }}</a>
                                <span class="top-store-value">{{ number_format($store->token_count) }}</span>
                            </div>
                        @empty
                            <p class="text-muted text-center py-4 m-0">No data</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Highest Revenue -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card top-card tc-revenue h-100">
                    <div class="card-header">
                        <h6><i class="tio-money mr-1"></i> Highest POS Revenue</h6>
                    </div>
                    <div class="card-body p-0">
                        @forelse($topByRevenue as $index => $store)
                            <div class="top-store-item">
                                <span
                                    class="top-store-rank {{ $index < 3 ? 'rank-' . ($index + 1) : 'rank-default' }}">{{ $index + 1 }}</span>
                                <a href="{{ route('admin.store-monetization.store-detail', $store->store_id) }}"
                                    class="top-store-name">{{ $store->store_name }}</a>
                                <span
                                    class="top-store-value">{{ \App\CentralLogics\Helpers::format_currency($store->total_revenue) }}</span>
                            </div>
                        @empty
                            <p class="text-muted text-center py-4 m-0">No data</p>
                        @endforelse
                    </div>
                </div>
            </div>

              <!-- Highest Bill Revenue -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card top-card tc-projects h-100">
                    <div class="card-header">
                        <h6><i class="tio-folder-outlined mr-1"></i> Highest Bills Revenue</h6>
                    </div>
                    <div class="card-body p-0">
                        @forelse($topByBillRevenue as $index => $store)
                            <div class="top-store-item">
                                <span
                                    class="top-store-rank {{ $index < 3 ? 'rank-' . ($index + 1) : 'rank-default' }}">{{ $index + 1 }}</span>
                                <a href="{{ $store->vendor_id ? route('admin.store-monetization.store-detail', $store->vendor_id)  : '#' }}"
                                    class="top-store-name">{{ $store->store_name }}</a>
                                <span class="top-store-value">{{ number_format($store->total_revenue) }}</span>
                            </div>
                        @empty
                            <p class="text-muted text-center py-4 m-0">No data</p>
                        @endforelse
                    </div>
                </div>
            </div>


            <!-- Most Staff -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card top-card tc-staff h-100">
                    <div class="card-header">
                        <h6><i class="tio-group-senior mr-1"></i> Most Staff</h6>
                    </div>
                    <div class="card-body p-0">
                        @forelse($topByStaff as $index => $store)
                            <div class="top-store-item">
                                <span
                                    class="top-store-rank {{ $index < 3 ? 'rank-' . ($index + 1) : 'rank-default' }}">{{ $index + 1 }}</span>
                                <a href="{{ route('admin.store-monetization.store-detail', $store->store_id) }}"
                                    class="top-store-name">{{ $store->store_name }}</a>
                                <span class="top-store-value">{{ $store->staff_count }}</span>
                            </div>
                        @empty
                            <p class="text-muted text-center py-4 m-0">No data</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Most Leads -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card top-card tc-leads h-100">
                    <div class="card-header">
                        <h6><i class="tio-user-outlined mr-1"></i> Most Leads</h6>
                    </div>
                    <div class="card-body p-0">
                        @forelse($topByLeads as $index => $store)
                            <div class="top-store-item">
                                <span
                                    class="top-store-rank {{ $index < 3 ? 'rank-' . ($index + 1) : 'rank-default' }}">{{ $index + 1 }}</span>
                                <a href="{{ route('admin.store-monetization.store-detail', $store->store_id) }}"
                                    class="top-store-name">{{ $store->store_name }}</a>
                                <span class="top-store-value">{{ number_format($store->lead_count) }}</span>
                            </div>
                        @empty
                            <p class="text-muted text-center py-4 m-0">No data</p>
                        @endforelse
                    </div>
                </div>
            </div>
     
            <!-- Highest Wallet Balance -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card top-card tc-wallet h-100">
                    <div class="card-header">
                        <h6><i class="tio-wallet mr-1"></i> Highest Wallet</h6>
                    </div>
                    <div class="card-body p-0">
                        @forelse($topByWallet as $index => $store)
                            <div class="top-store-item">
                                <span
                                    class="top-store-rank {{ $index < 3 ? 'rank-' . ($index + 1) : 'rank-default' }}">{{ $index + 1 }}</span>
                                <a href="{{ route('admin.store-monetization.store-detail', $store->store_id) }}"
                                    class="top-store-name">{{ $store->store_name }}</a>
                                <span
                                    class="top-store-value">{{ \App\CentralLogics\Helpers::format_currency($store->total_earning) }}</span>
                            </div>
                        @empty
                            <p class="text-muted text-center py-4 m-0">No data</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Most Clients -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card top-card tc-clients h-100">
                    <div class="card-header">
                        <h6><i class="tio-user mr-1"></i> Most Clients</h6>
                    </div>
                    <div class="card-body p-0">
                        @forelse($topByClients as $index => $store)
                            <div class="top-store-item">
                                <span
                                    class="top-store-rank {{ $index < 3 ? 'rank-' . ($index + 1) : 'rank-default' }}">{{ $index + 1 }}</span>
                                <a href="{{ route('admin.store-monetization.store-detail', $store->store_id) }}"
                                    class="top-store-name">{{ $store->store_name }}</a>
                                <span class="top-store-value">{{ number_format($store->client_count) }}</span>
                            </div>
                        @empty
                            <p class="text-muted text-center py-4 m-0">No data</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- <!-- Most Projects -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card top-card tc-projects h-100">
                    <div class="card-header">
                        <h6><i class="tio-folder-outlined mr-1"></i> Most Projects</h6>
                    </div>
                    <div class="card-body p-0">
                        @forelse($topByProjects as $index => $store)
                            <div class="top-store-item">
                                <span
                                    class="top-store-rank {{ $index < 3 ? 'rank-' . ($index + 1) : 'rank-default' }}">{{ $index + 1 }}</span>
                                <a href="{{ route('admin.store-monetization.store-detail', $store->store_id) }}"
                                    class="top-store-name">{{ $store->store_name }}</a>
                                <span class="top-store-value">{{ number_format($store->project_count) }}</span>
                            </div>
                        @empty
                            <p class="text-muted text-center py-4 m-0">No data</p>
                        @endforelse
                    </div>
                </div>
            </div> --}}

            <!-- Most Inventory -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card top-card tc-inventory h-100">
                    <div class="card-header">
                        <h6><i class="tio-archive mr-1"></i> Most Inventory</h6>
                    </div>
                    <div class="card-body p-0">
                        @forelse($topByInventory as $index => $store)
                            <div class="top-store-item">
                                <span
                                    class="top-store-rank {{ $index < 3 ? 'rank-' . ($index + 1) : 'rank-default' }}">{{ $index + 1 }}</span>
                                <a href="{{ route('admin.store-monetization.store-detail', $store->store_id) }}"
                                    class="top-store-name">{{ $store->store_name }}</a>
                                <span class="top-store-value">{{ number_format($store->item_count) }}</span>
                            </div>
                        @empty
                            <p class="text-muted text-center py-4 m-0">No data</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Store List -->
        <div class="section-heading"><i class="tio-shop"></i> All Stores</div>
        <div class="card store-table-card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h5 class="card-title mb-0" style="font-size:15px; font-weight:700;">Store Directory</h5>
                    <form class="d-flex" method="GET" action="{{ route('admin.store-monetization.dashboard') }}">
                        <input type="hidden" name="date_range" value="{{ $preset }}">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text" name="search" class="form-control" placeholder="Search store..."
                                value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit"><i class="tio-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-nowrap table-align-middle card-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Store Name</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th class="text-center">POS</th>
                            <th class="text-center">Revenue</th>
                            <th class="text-center">Leads</th>
                            <th class="text-center">Staff</th>
                            <th class="text-center">Clients</th>
                            <th class="text-center">Projects</th>
                            <th class="text-center">Inventory</th>
                            <th class="text-center">Services</th>
                            <th class="text-center">Wallet</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stores as $key => $store)
                            <tr>
                                <td>{{ $stores->firstItem() + $key }}</td>
                                <td>
                                    <a href="{{ route('admin.store-monetization.store-detail', $store->id) }}"
                                        class="font-weight-bold" style="color: #1565c0;">
                                        {{ Str::limit($store->name, 30) }}
                                    </a>
                                </td>
                                <td class="text-muted">{{ $store->phone }}</td>
                                <td>
                                    @if ($store->status)
                                        <span class="badge"
                                            style="background: #e8f5e9; color: #2e7d32; padding: 5px 10px; border-radius: 20px; font-size: 11px;">Active</span>
                                    @else
                                        <span class="badge"
                                            style="background: #ffebee; color: #c62828; padding: 5px 10px; border-radius: 20px; font-size: 11px;">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center font-weight-bold">{{ number_format($store->pos_count) }}</td>
                                <td class="text-center" style="color: #2e7d32; font-weight: 600;">
                                    {{ \App\CentralLogics\Helpers::format_currency($store->pos_revenue) }}</td>
                                <td class="text-center">{{ number_format($store->lead_count) }}</td>
                                <td class="text-center">{{ number_format($store->staff_count) }}</td>
                                <td class="text-center">{{ number_format($store->client_count) }}</td>
                                <td class="text-center">{{ number_format($store->project_count) }}</td>
                                <td class="text-center">{{ number_format($store->inventory_count) }}</td>
                                <td class="text-center">{{ number_format($store->service_count) }}</td>
                                <td class="text-center" style="color: #00695c; font-weight: 600;">
                                    {{ \App\CentralLogics\Helpers::format_currency($store->wallet_balance) }}</td>
                                <td class="text-center">
                                    <a href="{{ route('admin.store-monetization.store-detail', $store->id) }}"
                                        class="btn btn-sm"
                                        style="background: #e3f2fd; color: #1565c0; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                        <i class="tio-visible"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center py-4 text-muted">No stores found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($stores->hasPages())
                <div class="card-footer border-0">
                    {{ $stores->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
@endpush
