@extends('layouts.vendor.app')

@section('title', translate('messages.dashboard'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">

    <title>Business Dashboard</title>
    <style>
        .app_dwnld_div img {
            width: 150px;
        }

        .app_dwnld_div {
            text-align: center;
        }

        .btn-analysis2 {
            border-radius: 10px;
            padding: 7px;
            margin: 0 2px;

        }
    </style>
    <style>
        :root {
            --accent-orange: #F97316;
            --accent-blue: #3B82F6;
            --accent-pink: #EC4899;
            --bg-light: #F8FAFC;
            --bg-card: #FFFFFF;
            --text-dark: #0F172A;
            --text-muted: #64748B;
            --border-color: #E2E8F0;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.12);
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            ;
            margin-bottom: 1rem;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 10px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .icon-wallet {
            background: linear-gradient(135deg, #FEF3C7, #FDE047);
            color: #CA8A04;
        }

        .icon-revenue {
            background: linear-gradient(135deg, #DCFCE7, #86EFAC);
            color: var(--primary);
        }

        .icon-leads {
            background: linear-gradient(135deg, #E0E7FF, #C7D2FE);
            color: var(--accent-blue);
        }

        .icon-customers {
            background: linear-gradient(135deg, #FCE7F3, #FBCFE8);
            color: var(--accent-pink);
        }

        .icon-employees {
            background: linear-gradient(135deg, #DDD6FE, #C4B5FD);
            color: var(--primary-light-theme);
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .stat-value {
            font-size: 19px;

            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-family: 'Space Mono', monospace;
        }

        .stat-change {
            font-size: 0.813rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Main Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            margin-bottom: 1rem;
        }

        /* Activity Section */
        .activity-section {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* Custom Tabs */
        .custom-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .custom-tabs::-webkit-scrollbar {
            display: none;
        }

        .tab-btn {
            padding: 0.75rem 1.25rem;
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.938rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            white-space: nowrap;
            border-radius: 8px 8px 0 0;
        }

        .tab-btn::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .tab-btn.active {
            color: var(--primary);
            background: rgba(16, 185, 129, 0.05);
        }

        .tab-btn.active::after {
            transform: scaleX(1);
        }

        .tab-btn:hover:not(.active) {
            color: var(--text-dark);
            background: rgba(0, 0, 0, 0.02);
        }



        .sno-cell {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sno-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary);
        }

        .status-badge {
            padding: 0.375rem 0.875rem;
            border-radius: 20px;
            font-size: 0.813rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background: #FEF3C7;
            color: #CA8A04;
        }

        .status-completed {
            background: #D1FAE5;
            color: #059669;
        }

        .type-expense {
            color: var(--accent-orange);
            font-weight: 600;
        }

        .type-income {
            color: var(--primary);
            font-weight: 600;
        }

        /* Pagination */
        .pagination-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .pagination-controls button {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pagination-controls button:hover:not(:disabled) {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(16, 185, 129, 0.05);
        }

        .pagination-controls button:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .pagination-info {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .action2-btn {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            border: none;
            color: white;
            font-weight: 600;
            font-size: 0.938rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
        }

        .action2-btn:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow-md);
        }

        .action2-btn i {
            font-size: 1.25rem;
        }

        .btn-expense {
            background: linear-gradient(135deg, var(--primary), #b10909);
        }

        .btn-task {
            background: linear-gradient(135deg, var(--accent-blue), #2563EB);
        }

        .btn-bill {
            background: linear-gradient(135deg, var(--primary-light-theme), #7C3AED);
        }

        .btn-client {
            background: linear-gradient(135deg, var(--accent-orange), #EA580C);
        }

        .btn-project {
            background: linear-gradient(135deg, var(--accent-pink), #DB2777);
        }

        /* Chart Section */
        .chart-section {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-top: 1rem;
        }

        .smart-table {
            margin-top: 1rem;

        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .chart-period {
            padding: 0.5rem 1rem;
            background: rgba(16, 185, 129, 0.05);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 8px;
            font-size: 0.813rem;
            color: var(--primary);
            font-weight: 600;
        }

        .chart-canvas {
            height: 250px;
            display: flex;
            align-items: flex-end;
            gap: 1rem;
            padding: 1rem 0;
        }

        .chart-bar-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-bars {
            display: flex;
            gap: 4px;
            align-items: flex-end;
            height: 200px;
            width: 100%;
            justify-content: center;
        }

        .chart-bar {
            width: 20px;
            border-radius: 4px 4px 0 0;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .chart-bar:hover {
            opacity: 0.8;
            transform: translateY(-4px);
        }

        .bar-income {
            background: linear-gradient(180deg, #FDE047, #F59E0B);
        }

        .bar-expense {
            background: linear-gradient(180deg, #C4B5FD, var(--primary-light-theme));
        }

        .chart-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .chart-legend {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-top: 1rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.813rem;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        @media (max-width: 640px) {
            body {
                padding: 1rem;
            }

            .stat-card {
                padding: 1rem;
            }

            .stat-value {
                font-size: 1.5rem;
            }

        }

        .dashboard-icon {
            width: 33px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        @if (auth('vendor')->check())
            <div class="row align-items-center mb-2">

                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">{{ translate('messages.welcome') }},
                        {{ auth('vendor')->user()->f_name }}.</h1>
                    <p class="page-header-text">{{ translate('messages.employee_welcome_message') }}</p>
                </div>
                <form action="" class="d-flex date-range-form">
                    @include('vendor-views/form_modals/date_range')
                    <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning" type="button"
                        data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>


                </form>
            </div>
            <div class="dashboard-container">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon icon-wallet">
                            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/My%20Wallet_color.png') }}"
                                alt="my wallet" class="dashboard-icon">
                        </div>
                        <div class="stat-label">Wallet Balance</div>
                        <div class="stat-value">{{ _price($data['wallet_balance']) }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-leads">
                            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/leads_management_color.png') }}"
                                alt="my wallet" class="dashboard-icon">
                        </div>
                        <div class="stat-label">Leads</div>
                        <div class="stat-value">{{ $data['total_leads_count'] }} <span
                                style="font-size: 1rem; color: var(--text-muted);">Leads</span>
                        </div>
                        <div class="stat-change">
                            <i class="bi bi-arrow-up"></i>
                            <span>{{ $data['completed_leads_count'] }} completed</span>
                        </div>
                    </div>

                    @if (selected_menu('account_manage'))
                        <div class="stat-card">
                            <div class="stat-icon icon-revenue">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/My%20Salary_color.png') }}"
                                    alt="my wallet" class="dashboard-icon">
                            </div>
                            <div class="stat-label">Revenue</div>
                            <div class="stat-value">{{ _price($data['revenue'] ) }}</div>
                            <div class="stat-change">
                                <i class="bi bi-arrow-up"></i>
                            </div>
                        </div>
                    @endif
                    @if (selected_menu('client_manage'))
                        <div class="stat-card">
                            <div class="stat-icon icon-customers">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Clients_management_color.png') }}"
                                    alt="my wallet" class="dashboard-icon">
                            </div>
                            <div class="stat-label">My Customers</div>
                            <div class="stat-value">{{ $data['my_customers'] }}
                                {{-- <span style="font-size: 1rem; color: var(--text-muted);">+88</span> --}}
                            </div>
                            {{-- <div class="stat-change">
                            <i class="bi bi-arrow-up"></i>
                            <span>88 new</span>
                        </div> --}}
                        </div>
                    @endif
                    @if (selected_menu('leave_manage'))
                        <div class="stat-card">
                            <div class="stat-icon icon-employees">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Accounts_management_color.png') }}"
                                    alt="my wallet" class="dashboard-icon">
                            </div>
                            <div class="stat-label">On-duty Employs</div>
                            <div class="stat-value">4 <span style="font-size: 1rem; color: var(--text-muted);">👥</span>
                            </div>
                            <div class="stat-change">
                                <i class="bi bi-arrow-up"></i>
                                <span>{{ $data['leave_requests'] }} Leave Requests</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Main Content -->
                <div class="content-grid">
                    <!-- Activity Section -->
                    <div>
                        <div class="activity-section">
                            <div class="section-header">
                                <h2 class="section-title">Recent Activity</h2>

                            </div>
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="tab-btn nav-link active" id="leads-tab" data-toggle="tab"
                                        data-target="#leads" type="button" role="tab" aria-controls="leads"
                                        aria-selected="true">Leads</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="tab-btn nav-link" id="sales-tab" data-toggle="tab" data-target="#sales"
                                        type="button" role="tab" aria-controls="sales"
                                        aria-selected="false">Sales</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="tab-btn nav-link" id="expenses-tab" data-toggle="tab"
                                        data-target="#expenses" type="button" role="tab" aria-controls="expenses"
                                        aria-selected="false">Expenses</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="tab-btn nav-link" id="pending-tab" data-toggle="tab"
                                        data-target="#pending" type="button" role="tab" aria-controls="pending"
                                        aria-selected="false">Pending Payments</button>
                                </li>
                            </ul> 

                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="leads" role="tabpanel"
                                    aria-labelledby="leads-tab">
                                    @include('vendor-views.dashboard.leads_list', ['leads' => $leads])
                                   
                                </div>
                                <div class="tab-pane fade " id="sales" role="tabpanel" aria-labelledby="sales-tab">
                                    sales
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>S No</th>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="activityTableBody">
                                            <tr>
                                                <td>
                                                    <div class="sno-cell">
                                                        <span class="sno-indicator"></span>
                                                        1
                                                    </div>
                                                </td>
                                                <td>01-10-2025</td>
                                                <td>Office Rent</td>
                                                <td class="type-expense">Expense</td>
                                                <td>₹5,000</td>
                                                <td><span class="status-badge status-pending">Pending</span></td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="sno-cell">
                                                        <span class="sno-indicator"></span>
                                                        2
                                                    </div>
                                                </td>
                                                <td>02-10-2025</td>
                                                <td>Sale</td>
                                                <td class="type-income">Income</td>
                                                <td>₹5,000</td>
                                                <td><span class="status-badge status-pending">Pending</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="tab-pane fade " id="expenses" role="tabpanel"
                                    aria-labelledby="expenses-tab">
                                    expenses
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>S No</th>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="activityTableBody">
                                            <tr>
                                                <td>
                                                    <div class="sno-cell">
                                                        <span class="sno-indicator"></span>
                                                        1
                                                    </div>
                                                </td>
                                                <td>01-10-2025</td>
                                                <td>Office Rent</td>
                                                <td class="type-expense">Expense</td>
                                                <td>₹5,000</td>
                                                <td><span class="status-badge status-pending">Pending</span></td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="sno-cell">
                                                        <span class="sno-indicator"></span>
                                                        2
                                                    </div>
                                                </td>
                                                <td>02-10-2025</td>
                                                <td>Sale</td>
                                                <td class="type-income">Income</td>
                                                <td>₹5,000</td>
                                                <td><span class="status-badge status-pending">Pending</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="tab-pane fade " id="pending" role="tabpanel"
                                    aria-labelledby="pending-tab">
                                    pending
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>S No</th>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="activityTableBody">
                                            <tr>
                                                <td>
                                                    <div class="sno-cell">
                                                        <span class="sno-indicator"></span>
                                                        1
                                                    </div>
                                                </td>
                                                <td>01-10-2025</td>
                                                <td>Office Rent</td>
                                                <td class="type-expense">Expense</td>
                                                <td>₹5,000</td>
                                                <td><span class="status-badge status-pending">Pending</span></td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="sno-cell">
                                                        <span class="sno-indicator"></span>
                                                        2
                                                    </div>
                                                </td>
                                                <td>02-10-2025</td>
                                                <td>Sale</td>
                                                <td class="type-income">Income</td>
                                                <td>₹5,000</td>
                                                <td><span class="status-badge status-pending">Pending</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>


                        <div class="activity-section mt-2">
                            <div class="section-header">
                                <h2 class="section-title">Smart Table </h2>

                            </div>
                            <ul class="nav nav-tabs" id="myTab2" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="tab-btn nav-link active" id="leads2-tab" data-toggle="tab"
                                        data-target="#leads2" type="button" role="tab" aria-controls="leads"
                                        aria-selected="true">Leads</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="tab-btn nav-link" id="sales2-tab" data-toggle="tab"
                                        data-target="#sales2" type="button" role="tab" aria-controls="sales"
                                        aria-selected="false">Sales</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="tab-btn nav-link" id="expenses2-tab" data-toggle="tab"
                                        data-target="#expenses2" type="button" role="tab" aria-controls="expenses"
                                        aria-selected="false">Expenses</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="tab-btn nav-link" id="pending2-tab" data-toggle="tab"
                                        data-target="#pending2" type="button" role="tab" aria-controls="pending"
                                        aria-selected="false">Pending Payments</button>
                                </li>
                            </ul>

                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="leads2" role="tabpanel"
                                    aria-labelledby="leads-tab">
                                    leads
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>S No</th>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="activityTableBody">
                                            <tr>
                                                <td>
                                                    <div class="sno-cell">
                                                        <span class="sno-indicator"></span>
                                                        1
                                                    </div>
                                                </td>
                                                <td>01-10-2025</td>
                                                <td>Office Rent</td>
                                                <td class="type-expense">Expense</td>
                                                <td>₹5,000</td>
                                                <td><span class="status-badge status-pending">Pending</span></td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="sno-cell">
                                                        <span class="sno-indicator"></span>
                                                        2
                                                    </div>
                                                </td>
                                                <td>02-10-2025</td>
                                                <td>Sale</td>
                                                <td class="type-income">Income</td>
                                                <td>₹5,000</td>
                                                <td><span class="status-badge status-pending">Pending</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="tab-pane fade " id="sales2" role="tabpanel" aria-labelledby="sales-tab">
                                    sales
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>S No</th>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="activityTableBody">
                                            <tr>
                                                <td>
                                                    <div class="sno-cell">
                                                        <span class="sno-indicator"></span>
                                                        1
                                                    </div>
                                                </td>
                                                <td>01-10-2025</td>
                                                <td>Office Rent</td>
                                                <td class="type-expense">Expense</td>
                                                <td>₹5,000</td>
                                                <td><span class="status-badge status-pending">Pending</span></td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="sno-cell">
                                                        <span class="sno-indicator"></span>
                                                        2
                                                    </div>
                                                </td>
                                                <td>02-10-2025</td>
                                                <td>Sale</td>
                                                <td class="type-income">Income</td>
                                                <td>₹5,000</td>
                                                <td><span class="status-badge status-pending">Pending</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="tab-pane fade " id="expenses2" role="tabpanel"
                                    aria-labelledby="expenses-tab">
                                    expenses
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>S No</th>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="activityTableBody">
                                            <tr>
                                                <td>
                                                    <div class="sno-cell">
                                                        <span class="sno-indicator"></span>
                                                        1
                                                    </div>
                                                </td>
                                                <td>01-10-2025</td>
                                                <td>Office Rent</td>
                                                <td class="type-expense">Expense</td>
                                                <td>₹5,000</td>
                                                <td><span class="status-badge status-pending">Pending</span></td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="sno-cell">
                                                        <span class="sno-indicator"></span>
                                                        2
                                                    </div>
                                                </td>
                                                <td>02-10-2025</td>
                                                <td>Sale</td>
                                                <td class="type-income">Income</td>
                                                <td>₹5,000</td>
                                                <td><span class="status-badge status-pending">Pending</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="tab-pane fade " id="pending2" role="tabpanel"
                                    aria-labelledby="pending-tab">
                                    pending
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>S No</th>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="activityTableBody">
                                            <tr>
                                                <td>
                                                    <div class="sno-cell">
                                                        <span class="sno-indicator"></span>
                                                        1
                                                    </div>
                                                </td>
                                                <td>01-10-2025</td>
                                                <td>Office Rent</td>
                                                <td class="type-expense">Expense</td>
                                                <td>₹5,000</td>
                                                <td><span class="status-badge status-pending">Pending</span></td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="sno-cell">
                                                        <span class="sno-indicator"></span>
                                                        2
                                                    </div>
                                                </td>
                                                <td>02-10-2025</td>
                                                <td>Sale</td>
                                                <td class="type-income">Income</td>
                                                <td>₹5,000</td>
                                                <td><span class="status-badge status-pending">Pending</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>


                    </div>


                    <!-- Quick Actions -->
                    <div>
                        <div class="activity-section">
                            <h3 class="section-title" style="margin-bottom: 1rem;">Quick Actions</h3>
                            <div class="quick-actions">
                                <button class="action2-btn btn-expense">
                                    <span>Add Expense</span>
                                    <i class="bi bi-plus-circle"></i>
                                </button>
                                <button class="action2-btn btn-task">
                                    <span>Add Task</span>
                                    <i class="bi bi-list-check"></i>
                                </button>
                                <button class="action2-btn btn-bill">
                                    <span>Add Bill</span>
                                    <i class="bi bi-receipt"></i>
                                </button>
                                <button class="action2-btn btn-client">
                                    <span>Add Client</span>
                                    <i class="bi bi-person-plus"></i>
                                </button>
                                <button class="action2-btn btn-project">
                                    <span>Add Project</span>
                                    <i class="bi bi-folder-plus"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Chart -->
                        <div class="chart-section">
                            <div class="chart-header">
                                <h3 class="section-title">chart</h3>
                                <span class="chart-period">Weekly</span>
                            </div>

                            <div class="chart-canvas" id="chartCanvas">
                                <!-- Chart will be populated by JavaScript -->
                            </div>

                            <div class="chart-legend">
                                <div class="legend-item">
                                    <div class="legend-color bar-income"></div>
                                    <span>Income</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color bar-expense"></div>
                                    <span>Expense</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        @endif
    </div>


    <div class="p-2">
        <h3>Download Android Apps</h3>
        <div class="d-flex flex-wrap">
            <div class="app_dwnld_div">
                <a target="_blank" href="https://play.google.com/store/apps/details?id=com.mcvendor">
                    <img src="{{ asset('storage/app/public/util/android_app_download.png') }}">
                    <p>Vendor App</p>
                </a>
            </div>
            <div class="app_dwnld_div">
                <a target="_blank"
                    href="https://play.google.com/store/apps/details?id=com.mychitti.staff&pcampaignid=web_share">
                    <img src="{{ asset('storage/app/public/util/android_app_download.png') }}">
                    <p>Staff App</p>
                </a>
            </div>
            <div class="app_dwnld_div">
                <a target="_blank" href="https://play.google.com/store/apps/details?id=com.mychittiappuser">
                    <img src="{{ asset('storage/app/public/util/android_app_download.png') }}">
                    <p>User App</p>
                </a>
            </div>
            {{-- <div class="app_dwnld_div">
                <a target="_blank" href="https://play.google.com/store/apps/details?id=com.mychitti_delivery_app">
                    <img src="{{ asset('storage/app/public/util/android_app_download.png') }}">
                    <p>Delivery App</p>
                </a>
            </div> --}}
        </div>
    </div>

    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Apply Coupon</h5>
                    <button type="button" class="close close_coupon_modal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form class="applyCouponForm">
                    @csrf
                    <div class="modal-body">
                        <label>Coupon Code</label>

                        <input type="text" name="coupon_code" id="app_coupon_code" class="form-control" required>

                        <span class="text-danger coupon_error"></span>
                        <span class="text-success coupon_success"></span>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

                        <button type="button" class="btn btn-primary applyCouponBtn">Apply Coupon</button>
                    </div>
                </form>


            </div>
        </div>
    </div>
    @include('vendor-views.form_modals.customer_add')

@endsection

@push('script')
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js.extensions/chartjs-extensions.js"></script>
    <script
        src="{{ asset('public/assets/admin') }}/vendor/chartjs-plugin-datalabels/dist/chartjs-plugin-datalabels.min.js">
    </script>
@endpush


@push('script_2')
    <script>
        @if (auth('vendor')->check())
            window.ReactNativeWebView?.postMessage(
                JSON.stringify({
                    type: 'USER_LOGIN',
                    vendor_id: {{ auth('vendor')->id() }}
                })
            );
        @endif
    </script>
    <script>
        $(document).on('click', '.applyCouponBtn', function(e) {
            console.log('fsdf')
            e.preventDefault();
            e.stopImmediatePropagation();
            $(".applyCouponBtn").attr('disabled', true)

            $('.coupon_error').text('');
            $('.coupon_success').text('');

            let btn = $(this);
            btn.prop('disabled', true).text('Applying...');

            $.ajax({
                url: '{{ route('vendor.applyCoupon') }}',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    coupon_code: $("#app_coupon_code").val()
                },
                success: function(data) {
                    console.log(data);

                    if (data.status) {
                        $('.coupon_success').text(data.message);
                        setTimeout(() => {
                            $(".applyCouponForm").trigger('reset')

                            $(".close_coupon_modal").click()
                            $(".applyCouponBtn").removeAttr('disabled')
                        }, 1000);
                    } else {
                        $('.coupon_error').text(data.message);
                    }
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                    $('.coupon_error').text('Server error');
                },
                complete: function() {
                    btn.prop('disabled', false).text('Apply Coupon');
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#custom-buttons').on('click', 'button', function() {
                const label = $(this).data('label');
                let inputGroup = '';

                if (label === 'Other') {
                    inputGroup = `
        <div class="form-group custom-field" data-label="${label}">
            <div class="d-flex mb-2">
                <input type="text" class="form-control mr-2" placeholder="Label" name="header_label[]">
                <input type="text" class="form-control mr-2" name="header_field[]">
                <a type="button" class="text-danger remove-field"><i class="tio-delete-outlined"></i></a>
            </div>
        </div>
        `;

                } else {
                    $('.' + label)
                        .show() // Hide the clicked button
                    $(this).hide();
                }
                console.log(label)

                $('#custom-fields').append(inputGroup);
            });

            //Handle remove
            $('#custom-fields').on('click', '.remove-field', function() {
                console.log('remove')
                const $fieldGroup = $(this).closest('.custom-field');
                const label = $fieldGroup.data('label');

                // Show back the corresponding button
                $('#custom-buttons button').each(function() {
                    if ($(this).data('label') === label) {
                        $(this).show();
                    }
                });

                $fieldGroup.remove();
            });

        });
        // INITIALIZATION OF CHARTJS
        // =======================================================
        Chart.plugins.unregister(ChartDataLabels);

        $('.js-chart').each(function() {
            $.HSCore.components.HSChartJS.init($(this));
        });

        let updatingChart = $.HSCore.components.HSChartJS.init($('#updatingData'));

        $('.order_stats_update').on('change', function() {
            let type = $(this).val();
            order_stats_update(type);
        })

        function clock(action) {

            if (action == 'in') {
                var url = '{{ route('vendor.clockin') }}'
            } else {
                var url = '{{ route('vendor.clockout') }}'
            }

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.get({
                url: url,
                data: {
                    action: action
                },
                beforeSend: function() {
                    $('#loading').show()
                },
                success: function(data) {
                    console.log(data)
                    if (data.status) {
                        $('.time_det_outer').load(window.location.href + ' .timing_det');
                    }
                    $('#loading').hide()
                },
                complete: function() {

                }
            });
        }

        function order_stats_update(type) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('vendor.dashboard.order-stats') }}',
                data: {
                    statistics_type: type
                },
                beforeSend: function() {
                    $('#loading').show()
                },
                success: function(data) {
                    insert_param('statistics_type', type);
                    $('#order_stats').html(data.view)
                },
                complete: function() {
                    $('#loading').hide()
                }
            });
        }

        function insert_param(key, value) {
            key = encodeURIComponent(key);
            value = encodeURIComponent(value);
            // kvp looks like ['key1=value1', 'key2=value2', ...]
            let kvp = document.location.search.substr(1).split('&');
            let i = 0;

            for (; i < kvp.length; i++) {
                if (kvp[i].startsWith(key + '=')) {
                    let pair = kvp[i].split('=');
                    pair[1] = value;
                    kvp[i] = pair.join('=');
                    break;
                }
            }
            if (i >= kvp.length) {
                kvp[kvp.length] = [key, value].join('=');
            }
            // can return this or...
            let params = kvp.join('&');
            // change url page with new params
            window.history.pushState('page2', 'Title', '{{ url()->current() }}?' + params);
        }

        function filterPNL(elem) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "@php echo route('vendor.dashboard.filter-pnl') @endphp",
                data: {
                    month: $(elem).val()
                },
                success: function(data) {
                    $('.final_pl').html(data.html)
                    $('.earning_elem').html(data.earning)
                    $('.commission_elem').html(data.commission)
                },

            });
        }
    </script>
    @include('vendor-views/js/date_range')
@endpush
