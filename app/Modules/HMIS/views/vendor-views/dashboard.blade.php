@extends('layouts.vendor.app')

@section('title', translate('messages.dashboard'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">

    <title>Business Dashboard</title>
    <style>
        .tab-scrolling {
            height: 300px;
            overflow-y: scroll;
        }

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

        .inner_loader {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(156px, 1fr));
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

        .soft-yellow {
            background-color: #fbff2314;
        }

        .soft-green {
            background-color: #23ffa014;
        }

        .soft-blue {
            background-color: #6c23ff14;
        }

        .soft-red {
            background-color: #ff239c14;
        }

        .soft-purple {
            background-color: #c823ff14;
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
            margin-bottom: 2px;
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
            font-size: 11px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.25rem;
            display: inline;
        }

        /* Main Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .content-grid2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            /* equal width */
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .content-grid2>* {
            min-width: 0;
        }


        /* Activity Section */
        .activity-section {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 13px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-bottom: 1rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .status-new {
            background: #d1e5fa;
            color: #055596;
        }

        .status-completed {
            background: #D1FAE5;
            color: #059669;
        }

        .status-cancelled {
            background: #fad1d1;
            color: #960505;
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

        /* Quick Actions - Minimal Style */
        .quick-actions-container {
            padding: 12px !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quick-actions-grid {
            padding: 12px !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quick-actions-title {
            font-size: 13px;

            font-weight: 700;
            color: var(--text-dark);
            letter-spacing: 0.05em;
            margin-bottom: 0;
        }

        {{-- .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 5px;
        } --}} .quick-action-btn {
            padding: 6px 15px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-dark);
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 2px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            line-height: 1.3;
        }

        .btn-soft-yellow {
            background-color: #ffbc141e;
            border: 1px solid #ffc445;
        }

        .btn-soft-yellow i {
            color: #ffbc14;
            font-size: 25px;
        }

        .btn-soft-blue {
            background-color: #1420ff17;
            border: 1px solid #6468ee;
        }

        .btn-soft-blue i {
            color: #6468ee;
            font-size: 25px;
        }

        .btn-soft-green {
            background-color: #24ff141d;
            border: 1px solid #40c545;
        }

        .btn-soft-green i {
            color: #40c545;
            font-size: 25px;
        }

        .btn-soft-purple {
            background-color: #b814ff17;
            border: 1px solid #bb45ff;
        }

        .btn-soft-purple i {
            color: #bb45ff;
            font-size: 25px;
        }

        .quick-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-color: #D1D5DB;
            color: black;
        }

        .quick-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            flex-shrink: 0;
        }

        .quick-icon-blue {
            background: #BFDBFE;
        }

        .quick-icon-yellow {
            background: #FDE047;
        }

        .quick-icon-purple {
            background: #DDD6FE;
        }

        .quick-icon-green {
            background: #86EFAC;
        }

        @media (max-width: 480px) {
            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
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
            padding: 13px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .smart-table {
            margin-top: 1rem;

        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .chart-container {
            height: 200px;
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

            .content-grid,
            .content-grid2 {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        @media (max-width: 640px) {


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

        #myChart {
            height: 250px;
        }

        .chart_wrapper {
            position: relative;
            width: 100%;
        }

        /* Action Summary Section */
        {{-- .action-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 10px;
            margin-bottom: 1rem;
        } --}} .action-summary-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .summary-section {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 10px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .summary-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .summary-card {
            border-radius: 12px;
            padding: 13px;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .card1 {
            background: linear-gradient(45deg, #e2feff, #00ff8721);
        }

        .card2 {
            background: linear-gradient(45deg, #fff9e2, #ff002621);
        }

        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
            border-color: var(--border-color);
        }

        .summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .summary-content {
            flex: 1;
        }

        .summary-count {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .summary-link {
            font-size: 0.813rem;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            transition: all 0.2s ease;
            justify-content: center;

            width: 100%;
            background: white;
            padding: 5px;
            border-radius: 6px;
            border: 1px solid #c9c9c9;
        }

        .summary-link:hover {
            gap: 0.5rem;
        }

        .summary-link i {
            font-size: 0.75rem;
        }

        @media (max-width: 768px) {
            .action-summary-grid {
                grid-template-columns: 1fr;
            }

            .summary-cards {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }

            .summary-card {
                padding: 10px;
                gap: 6px;
            }

            .summary-count {
                font-size: 13px;
            }

            .summary-link {
                font-size: 11px;
                padding: 4px;
            }

            .summary-title {
                font-size: 14px;
            }
        }

        /* Quick Actions + Profile Completion row */
        .quick-actions-profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        @media (max-width: 768px) {
            .quick-actions-profile-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions-container {
                flex-wrap: wrap;
                gap: 6px;
                padding: 10px !important;
                align-items: stretch;
            }

            .quick-actions-container .quick-actions-title {
                flex: 0 0 100%;
                margin-bottom: 2px;
            }

            .quick-actions-container .quick-action-btn {
                flex: 1 1 calc(50% - 3px);
                min-width: 0;
                justify-content: center;
                padding: 7px 4px;
                font-size: 11px;
                flex-direction: column;
                gap: 2px;
                text-align: center;
            }

            .quick-actions-container .quick-action-btn i {
                font-size: 18px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat-value {
                font-size: 14px;
                word-break: break-word;
            }

            .stat-icon {
                width: 36px;
                height: 36px;
                font-size: 1.1rem;
            }

            .stat-card {
                padding: 8px;
            }

            /* Activity tabs — chip wrap so all are visible */
            #myTab {
                flex-wrap: wrap !important;
                overflow-x: visible;
                gap: 5px;
                border-bottom: none;
                padding-bottom: 8px;
            }

            #myTab .nav-item {
                flex-shrink: 0;
            }

            #myTab .nav-link {
                font-size: 11px;
                padding: 4px 10px;
                white-space: nowrap;
                border-radius: 20px !important;
                border: 1px solid #dee2e6 !important;
                background: #f8f9fa;
                color: #6c757d;
                margin-bottom: 0;
                line-height: 1.4;
            }

            #myTab .nav-link.active {
                background: var(--primary) !important;
                color: #fff !important;
                border-color: var(--primary) !important;
            }

            #myTab .tab-btn::after {
                display: none;
            }

            .section-title {
                font-size: 1rem;
            }
        }
    </style>
@endpush


@section('content')
    <div class="content container-fluid">
        @if (auth('vendor')->check())
            {{-- Inactivity warning banner --}}
            @if(!empty($inactiveWarning))
            <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-3 mb-3" role="alert"
                 style="border-left:4px solid #f6c23e;border-radius:10px">
                <span style="font-size:1.6rem">⚠️</span>
                <div>
                    <strong>{{ $inactiveWarning->title }}</strong><br>
                    <span class="small">{{ $inactiveWarning->description }}</span>
                </div>
                <button type="button" class="close ml-auto" data-dismiss="alert" aria-label="Close"
                        onclick="markInactiveRead({{ $inactiveWarning->id }})">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <script>
            function markInactiveRead(id){
                fetch('{{ route("vendor.mark-inactive-read") }}', {
                    method:'POST',
                    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                    body: JSON.stringify({id})
                });
            }
            </script>
            @endif
            <div class="row align-items-center mb-2">

                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title d-block d-md-none">{{ translate('messages.welcome') }},
                        {{ auth('vendor')->user()->f_name }}.</h1>
                    <p class="page-header-text">Welcome to MyChitti Dashboard</p>
                </div>
                <div class="col-sm d-flex align-items-center" style="gap:8px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-toggle="modal" data-target="#defaultDashboardModal"
                        style="font-size:11px;font-weight:600;white-space:nowrap;">
                       <i class="tio-dashboard-outlined"></i>
                    </button>
                    @include('vendor-views/form_modals/default_dashboard')
                    <button class="d-none d-sm-block btn btn-primary btn_sm" type="button" data-toggle="modal"
                        data-target="#exampleModal">Apply Coupon for Customer</button>
                    <button class="d-block d-sm-none btn btn-primary btn_sm" type="button" data-toggle="modal"
                        data-target="#exampleModal">Apply Coupon</button>
                    <form action="" class="d-flex date-range-form">
                        @include('vendor-views/form_modals/date_range')
                        <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning btn_sm" type="button"
                            data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
                    </form>
                </div>
            </div>
            <div class="dashboard-container">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <a href="{{ route('vendor.wallet.wallet_payment_list') }}" class="stat-card soft-yellow">
                        <div class="stat-icon icon-wallet">
                            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/My%20Wallet_color.png') }}"
                                alt="my wallet" class="dashboard-icon">
                        </div>
                        <div class="stat-label">Wallet Balance</div>
                        <div class="stat-value">{{ _price($data['wallet_balance']) }}</div>
                    </a>
                    <a href="{{route('vendor.service.leads_list')}}" class="stat-card soft-blue">
                        <div class="stat-icon icon-leads">
                            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/leads_management_color.png') }}"
                                alt="my wallet" class="dashboard-icon">
                        </div>
                        <div class="stat-label">Leads</div>
                        <div class="stat-value">{{ $data['total_leads_count'] }} <span
                                style="font-size: 1rem; color: var(--text-muted);">Leads</span>
                            <div class="stat-change">
                                <i class="bi bi-arrow-up"></i>
                                <span>{{ $data['completed_leads_count'] }} completed</span>
                            </div>
                        </div>
                    </a>

                    @if (selected_menu('account_manage'))
                        <a href="{{route('vendor.account.add')}}" class="stat-card soft-green">
                            <div class="stat-icon icon-revenue">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/My%20Salary_color.png') }}"
                                    alt="my wallet" class="dashboard-icon">
                            </div>
                            <div class="stat-label">Revenue</div>
                            <div class="stat-value">{{ _price($data['revenue']) }}</div>
                            <div class="stat-change">
                                <i class="bi bi-arrow-up"></i>
                            </div>
                        </a>
                    @endif
                    @if (selected_menu('client_manage'))
                        <a href="{{route('vendor.customer.list')}}" class="stat-card soft-red">
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
                        </a>
                    @endif
                    @if (selected_menu('leave_manage'))
                        <a href="{{route('vendor.staff.list')}}" class="stat-card soft-purple">
                            <div class="stat-icon icon-employees">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Accounts_management_color.png') }}"
                                    alt="my wallet" class="dashboard-icon">
                            </div>
                            <div class="stat-label">On-duty Employees</div>
                            <div class="stat-value">{{ $data['on_duty_emp'] }}/{{ $data['total_emp'] }} <span
                                    style="font-size: 1rem; color: var(--text-muted);">👥</span>
                              @if($data['leave_requests'])  <div class="stat-change">
                                    <span>{{ $data['leave_requests'] }} Leave Requests</span>
                                </div>
                                @endif
                            </div>

                        </a>
                    @endif

                </div>
                <div class="mt-2 quick-actions-profile-grid">
                    {{-- Quick Actions --}}
                    <div class="activity-section quick-actions-container" >
                        <h3 class="quick-actions-title">QUICK ACTIONS</h3>
                        <a href="{{ route('vendor.invoice.manual-bill') }}" class="quick-action-btn btn-soft-blue">
                            <i class="tio-receipt-outlined"></i>
                            <span>Add Bill</span>
                        </a>
                        <a href="{{ route('vendor.account.add', ['add']) }}" class="quick-action-btn btn-soft-yellow">
                            <i class="tio-wallet-outlined"></i>
                            <span>Add Expense</span>
                        </a>
                        <a href="{{ route('vendor.customer.add') }}" class="quick-action-btn btn-soft-green">
                            <i class="tio-user-add"></i>
                            <span>Add Client</span>
                        </a>
                        <a href="{{ route('vendor.project.add') }}" class="quick-action-btn btn-soft-purple">
                            <i class="tio-briefcase-outlined"></i>
                            <span>Add Project</span>
                        </a>
                    </div>
                    {{-- Profile Completion --}}
                    <div class="activity-section" style="display:flex;align-items:center;gap:16px">
                        {{-- Donut ring --}}
                        <div style="position:relative;width:68px;height:68px;flex-shrink:0">
                            <svg width="68" height="68" viewBox="0 0 68 68">
                                <circle cx="34" cy="34" r="27" fill="none" stroke="#f0f0f5" stroke-width="5"/>
                                <circle cx="34" cy="34" r="27" fill="none"
                                    stroke="{{ $completionRing }}" stroke-width="5"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ round(2*3.14159*27) }}"
                                    stroke-dashoffset="{{ round(2*3.14159*27*(1-$completionPercent/100)) }}"
                                    transform="rotate(-90 34 34)"/>
                            </svg>
                            <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;line-height:1.1">
                                <span style="font-size:14px;font-weight:700;color:#333">{{ $completionPercent }}%</span>
                                <span style="font-size:9px;color:#aaa">{{ $completionDone }}/{{ $completionTotal }}</span>
                            </div>
                        </div>
                        {{-- Checklist --}}
                        <div >
                            <div style="font-size:10px;font-weight:700;letter-spacing:.5px;color:#999;text-transform:uppercase;margin-bottom:6px">Profile Completion</div>
                            <ul class="list-unstyled mb-0" style="display:flex;flex-wrap: wrap;gap:5px">
                                @foreach($completionItems as $t)
                                <li style="display:flex;align-items:center;gap:7px">
                                    <span style="width:14px;height:14px;border-radius:3px;flex-shrink:0;display:flex;align-items:center;justify-content:center;
                                                 border:1.5px solid {{ $t['done'] ? $completionRing : '#d0d0dc' }};
                                                 background:{{ $t['done'] ? $completionRing : 'transparent' }}">
                                        @if($t['done'])
                                            <svg width="8" height="8" viewBox="0 0 9 9" fill="none">
                                                <path d="M1.5 4.5L3.5 6.5L7.5 2.5" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        @endif
                                    </span>
                                    <span style="font-size:12px;color:{{ $t['done'] ? '#888' : '#333' }};{{ $t['done'] ? '' : '' }}">
                                        {{ $t['icon'] }} {{ $t['label'] }}
                                    </span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="content-grid2">
                    <!-- Action Summary Section - Add this after the stats-grid and before content-grid -->
                    <div class="action-summary-grid">
                        <div class="summary-section">
                            <h3 class="summary-title">What happened today?</h3>
                            <div class="summary-cards">
                                <div class="summary-card card1">
                                    {{-- <div class="summary-icon" style="background: linear-gradient(135deg, #FEF3C7, #FDE047);"> --}}

                                    {{-- </div> --}}
                                    <div class="summary-content">
                                        <div class="summary-count"> <span style="font-size: 23px !important;">🔥</span>
                                            {{ $data['new_leads_count'] }} New
                                            Leads</div>
                                        <a href="{{ route('vendor.service.leads_list', array_merge(request()->query(), ['type' => 'New'])) }}"class="summary-link"
                                            style="color: #DC2626;">
                                            New Leads
                                            <i class="tio-arrow-forward"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="summary-card card2 ">

                                    <div class="summary-content">
                                        <div class="summary-count"> <span style="font-size: 23px !important;"> 📄</span>
                                            {{ $data['total_invoices_count'] }}
                                            Invoices</div>
                                        <a href="{{ route('vendor.invoice.list', array_merge(request()->query())) }}"
                                            class="summary-link" style="color: var(--accent-blue);">
                                            Sales
                                            <i class="tio-arrow-forward"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="summary-section">
                            <h3 class="summary-title">What needs my action now?</h3>
                            <div class="summary-cards">
                                <div class="summary-card card1">

                                    <div class="summary-content">
                                        <div class="summary-count"><span style="font-size: 23px !important;"> 💰</span>
                                            {{ $data['pending_payments'] }}
                                            Pending Payment</div>
                                        <a href="{{ route('vendor.invoice.list', array_merge(request()->query(), ['status' => 'Unpaid'])) }}"
                                            class="summary-link" style="color: #CA8A04;">
                                            Review
                                            <i class="tio-arrow-forward"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="summary-card card2">

                                    <div class="summary-content">
                                        <div class="summary-count"><span style="font-size: 23px !important;"> 📋</span> {{$data['pending_leaves']}}
                                            Leaves to Approve (all)</div>
                                        <a href="{{route('vendor.leave.all')}}" class="summary-link" style="color: var(--accent-blue);">
                                            Review
                                            <i class="tio-arrow-forward"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="chart-section">
                        <!-- Bootstrap 4 Nav Tabs -->
                        <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="leads-chart-tab" data-toggle="tab" href="#leads-chart"
                                    role="tab">
                                    <i class="bi bi-graph-up"></i>Leads
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="projects-chart-tab" data-toggle="tab" href="#projects-chart"
                                    role="tab">
                                    <i class="bi bi-folder"></i>Projects
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="sales-chart-tab" data-toggle="tab" href="#sales-chart"
                                    role="tab">
                                    <i class="bi bi-receipt"></i>Sales
                                </a>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="dashboardTabContent">
                            <!-- Leads Tab -->
                            <div class="tab-pane fade show active" id="leads-chart" role="tabpanel">
                                <div class="chart-card">
                                    <div class="chart-header">
                                        <h3>Leads Overview</h3>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="leadsChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Projects Tab -->
                            <div class="tab-pane fade" id="projects-chart" role="tabpanel">
                                <div class="chart-card">
                                    <div class="chart-header">
                                        <h3>Projects Overview</h3>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="projectsChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Bills Tab -->
                            <div class="tab-pane fade" id="sales-chart" role="tabpanel">
                                <div class="chart-card">
                                    <div class="chart-header">
                                        <h3>Sales Overview</h3>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="billsChart"></canvas>
                                        <div class="bills-total">
                                            {{-- <div class="label">Total Bills</div> --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Main Content class="content-grid" -->
                <div>
                    <!-- Activity Section -->
                    <div>
                        <div class="activity-section">
                            <div class="section-header">
                                <h2 class="section-title">Recent Activity</h2>

                            </div>
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="tab-btn nav-link active"
                                        data-url="{{ route('vendor.dashboard.leads') }}" id="leads-tab"
                                        data-toggle="tab" data-target="#leads" type="button" role="tab"
                                        aria-controls="leads" aria-selected="true">Leads</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="tab-btn nav-link"
                                        data-url="{{ route('vendor.dashboard.sales', ['Paid']) }}" id="sales-tab"
                                        data-toggle="tab" data-target="#sales" type="button" role="tab"
                                        aria-controls="sales" aria-selected="false">Sales</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="tab-btn nav-link" id="expenses-tab"
                                        data-url="{{ route('vendor.dashboard.expense') }}" data-toggle="tab"
                                        data-target="#expenses" type="button" role="tab" aria-controls="expenses"
                                        aria-selected="false">Expenses</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="tab-btn nav-link"
                                        data-url="{{ route('vendor.dashboard.sales', ['Unpaid']) }}" id="pending-tab"
                                        data-toggle="tab" data-target="#pending" type="button" role="tab"
                                        aria-controls="pending" aria-selected="false">Pending Payments</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="tab-btn nav-link" data-url="{{ route('vendor.dashboard.tasks') }}"
                                        id="task-tab" data-toggle="tab" data-target="#task" type="button"
                                        role="tab" aria-controls="task" aria-selected="false">Tasks</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="tab-btn nav-link" data-url="{{ route('vendor.dashboard.projects') }}"
                                        id="project-tab" data-toggle="tab" data-target="#project" type="button"
                                        role="tab" aria-controls="project" aria-selected="false">Projects</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="tab-btn nav-link" data-url="{{ route('vendor.dashboard.inventory-sales') }}"
                                        id="inventory-sales-tab" data-toggle="tab" data-target="#inventory-sales" type="button"
                                        role="tab" aria-controls="inventory-sales" aria-selected="false">{{ _moduleLabel('inventory') }} Sales</button>
                                </li>
                            </ul>

                            <div class="tab-content tab-scrolling" id="myTabContent">
                                <div class="tab-pane fade show active" id="leads" role="tabpanel"
                                    aria-labelledby="leads-tab">
                                    {{-- <div class="table-responsive datatable-custom"> --}}
                                    @include('vendor-views.dashboard.leads_list', [
                                        'leads' => $data['leads'],
                                    ])
                                    {{-- </div> --}}
                                </div>
                                <div class="tab-pane fade position-relative" id="sales" role="tabpanel"
                                    aria-labelledby="sales-tab">
                                    <div id="content_sales"></div>
                                </div>
                                <div class="tab-pane fade " id="expenses" role="tabpanel"
                                    aria-labelledby="expenses-tab">
                                    <div id="content_expenses"></div>
                                </div>
                                <div class="tab-pane fade " id="pending" role="tabpanel"
                                    aria-labelledby="pending-tab">
                                    <div id="content_pending"></div>
                                </div>
                                <div class="tab-pane fade " id="task" role="tabpanel" aria-labelledby="task-tab">
                                    <div id="content_task"></div>
                                </div>
                                <div class="tab-pane fade " id="project" role="tabpanel"
                                    aria-labelledby="project-tab">
                                    <div id="content_project"></div>
                                </div>
                                <div class="tab-pane fade" id="inventory-sales" role="tabpanel"
                                    aria-labelledby="inventory-sales-tab">
                                    <div id="content_inventory-sales"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
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

    <div class="modal fade" id="exampleModal" tabindex="-1" data-backdrop="static" data-keyboard="false" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Claim Loyalty Coupon</h5>
                    <button type="button" class="close close_coupon_modal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                {{-- Step 1: Enter coupon code --}}
                <div id="couponStep1">
                    <div class="modal-body">
                        <p class="text-muted mb-3">Ask the customer for their coupon code.</p>
                        <div class="form-group mb-0">
                            <label>Coupon Code <span class="text-danger">*</span></label>
                            <input type="text" id="app_coupon_code" class="form-control" placeholder="Enter coupon code">
                        </div>
                        <span class="text-danger coupon_error d-block mt-2"></span>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="sendOtpBtn">Send OTP to Customer</button>
                    </div>
                </div>

                {{-- Step 2: Verify OTP --}}
                <div id="couponStep2" style="display:none;">
                    <div class="modal-body">
                        <div class="alert alert-info" id="couponOtpInfo"></div>
                        <div class="form-group mb-0">
                            <label>OTP <span class="text-danger">*</span></label>
                            <input type="text" id="app_coupon_otp" class="form-control" maxlength="4" placeholder="4-digit OTP from customer">
                        </div>
                        <span class="text-danger coupon_verify_error d-block mt-2"></span>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" id="backToStep1Btn">Back</button>
                        <button type="button" class="btn btn-success" id="verifyCouponBtn">Verify & Claim</button>
                    </div>
                </div>

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush


@push('script_2')
    <script>
        // Common tooltip config
        const tooltipConfig = {
            backgroundColor: 'rgba(255, 255, 255, 0.95)',
            titleColor: '#1e293b',
            bodyColor: '#64748b',
            borderColor: '#e2e8f0',
            borderWidth: 1,
            cornerRadius: 8,
            padding: 12,
            boxPadding: 6,
            usePointStyle: true
        };

        // Leads Chart - Line Chart
        const leadsCtx = document.getElementById('leadsChart').getContext('2d');
        new Chart(leadsCtx, {
            type: 'line',
            data: {
                labels: @json($chart_data['months']),
                datasets: [{
                        label: 'Completed Leads',
                        data: @json($chart_data['completed_leads']),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        borderWidth: 3,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'New Leads',
                        data: @json($chart_data['new_leads']),
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        fill: true,
                        borderWidth: 3,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 13,
                                weight: 500
                            }
                        }
                    },
                    tooltip: tooltipConfig
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#64748b'
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            color: '#64748b'
                        }
                    }
                }
            }
        });

        // Projects Chart - Bar Chart
        const projectsCtx = document.getElementById('projectsChart').getContext('2d');
        new Chart(projectsCtx, {
            type: 'bar',
            data: {
                labels: @json($chart_data['months']),
                datasets: [{
                        label: 'Completed Projects',
                        data: @json($chart_data['completed_projects']),
                        backgroundColor: '#c52222',
                        borderRadius: 6
                    },
                    {
                        label: 'Active Projects',
                        data: @json($chart_data['active_projects']),
                        backgroundColor: '#f89191',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 13,
                                weight: 500
                            }
                        }
                    },
                    tooltip: tooltipConfig
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#64748b'
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            color: '#64748b'
                        }
                    }
                },
                barPercentage: 0.7,
                categoryPercentage: 0.8
            }
        });

        // Bills Chart - Doughnut Chart
        const billsCtx = document.getElementById('billsChart').getContext('2d');
        new Chart(billsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Unpaid'],
                datasets: [{
                    data: @json($chart_data['bills']),
                    backgroundColor: ['#97ebb6', '#ffa1a1'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 13,
                                weight: 500
                            },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                return data.labels.map((label, i) => ({
                                    text: `${label}: ₹${data.datasets[0].data[i].toLocaleString()}`,
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    strokeStyle: data.datasets[0].backgroundColor[i],
                                    pointStyle: 'circle',
                                    index: i
                                }));
                            }
                        }
                    },
                    tooltip: {
                        ...tooltipConfig,
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = ((value / total) * 100).toFixed(1);
                                return ` $${value.toLocaleString()} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
    {{-- <script>
        const canvas = document.getElementById('myChart');
        const ctx = canvas.getContext('2d');

        // Wait for DOM/canvas to layout first
        const gradient = ctx.createLinearGradient(0, 0, 0, canvas.offsetHeight || 200);
        gradient.addColorStop(0, 'rgba(215, 202, 98, 0.4)'); // green top
        gradient.addColorStop(1, 'rgba(192, 187, 85, 0)'); // transparent bottom

        const gradient2 = ctx.createLinearGradient(0, 0, 0, canvas.offsetHeight || 200);
        gradient2.addColorStop(0, 'rgba(222, 94, 94, 0.4)'); // green top
        gradient2.addColorStop(1, 'rgba(175, 76, 76, 0)'); // transparent bottom

        const gradient3 = ctx.createLinearGradient(0, 0, 0, canvas.offsetHeight || 200);
        gradient3.addColorStop(0, 'rgba(105, 94, 222, 0.4)'); // green top
        gradient3.addColorStop(1, 'rgba(76, 78, 175, 0)'); // transparent bottom

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chart_data['months']),
                datasets: [{
                    label: 'Leads Comp.',
                    data: @json($chart_data['leads']),
                    fill: true,
                    backgroundColor: gradient,
                    borderColor: 'rgba(218, 200, 98, 1)',
                    borderWidth: 2,
                    tension: 0.4
                }, {
                    label: 'Tasks Comp.',
                    data: @json($chart_data['tasks']),
                    fill: true,
                    backgroundColor: gradient2,
                    borderColor: 'rgba(202, 82, 82, 1)',
                    borderWidth: 2,
                    tension: 0.4
                }, {
                    label: 'Projects Comp.',
                    data: @json($chart_data['projects']),
                    fill: true,
                    backgroundColor: gradient3,
                    borderColor: 'rgba(82, 82, 202, 1)',
                    borderWidth: 2,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: false,
                    title: {
                        display: true,
                        text: 'Performance This Year'
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#000',
                        font: {
                            weight: 'bold'
                        },
                        formatter: value => value > 0 ? value : ''
                    }

                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    </script> --}}
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
        $(document).on('click', '.tab-btn', function() {

            var id = $(this).attr('data-target').replace(/^#/, '');
            var url = $(this).attr('data-url');

            const params = new URLSearchParams(window.location.search);

            var date_range = params.get('date_range');
            var custom_date_range = params.get('custom_date_range');

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: url,
                type: 'GET',
                data: {
                    date_range: date_range,
                    custom_date_range: custom_date_range
                },
                beforeSend: function() {
                    // $('.inner_loader').show();
                },
                success: function(data) {
                    if (data.status) {
                        $("#content_" + id).html(data.html);
                    }
                },
                complete: function() {
                    // $('.inner_loader').hide();
                }
            });


        });

        var _activeCouponCode = '';

        function resetCouponModal() {
            $('#couponStep1').show();
            $('#couponStep2').hide();
            $('#app_coupon_code').val('');
            $('#app_coupon_otp').val('');
            $('.coupon_error').text('');
            $('.coupon_verify_error').text('');
            $('#couponOtpInfo').text('');
            _activeCouponCode = '';
        }

        $(document).on('show.bs.modal', '#exampleModal', function () {
            resetCouponModal();
        });

        $(document).on('click', '#sendOtpBtn', function () {
            var code = $('#app_coupon_code').val().trim();
            if (!code) {
                $('.coupon_error').text('Please enter a coupon code.');
                return;
            }
            $('.coupon_error').text('');
            var btn = $(this);
            btn.prop('disabled', true).text('Sending OTP...');

            $.ajax({
                url: '{{ route('vendor.applyCoupon') }}',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    coupon_code: code
                },
                success: function (data) {
                    if (data.status && data.otp_sent) {
                        _activeCouponCode = code;
                        $('#couponOtpInfo').html(
                            '<strong>' + (data.customer_name || 'Customer') + '</strong> &mdash; Coupon worth <strong>&#8377;' + data.discount + '</strong><br><span class="text-muted">' +
                            data.message + '</span>'
                        );
                        $('#couponStep1').hide();
                        $('#couponStep2').show();
                        setTimeout(function(){ $('#app_coupon_otp').focus(); }, 100);
                    } else {
                        $('.coupon_error').text(data.message || 'Failed to send OTP.');
                    }
                },
                error: function () {
                    $('.coupon_error').text('Server error. Please try again.');
                },
                complete: function () {
                    btn.prop('disabled', false).text('Send OTP to Customer');
                }
            });
        });

        $(document).on('click', '#verifyCouponBtn', function () {
            var otp = $('#app_coupon_otp').val().trim();
            if (!otp || otp.length !== 4) {
                $('.coupon_verify_error').text('Please enter the 4-digit OTP.');
                return;
            }
            $('.coupon_verify_error').text('');
            var btn = $(this);
            btn.prop('disabled', true).text('Verifying...');

            $.ajax({
                url: '{{ route('vendor.verifyCoupon') }}',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    coupon_code: _activeCouponCode,
                    otp: otp
                },
                success: function (data) {
                    if (data.status) {
                        toastr.success(data.message);
                        $('#exampleModal').modal('hide');
                        resetCouponModal();
                    } else {
                        $('.coupon_verify_error').text(data.message || 'Verification failed.');
                    }
                },
                error: function () {
                    $('.coupon_verify_error').text('Server error. Please try again.');
                },
                complete: function () {
                    btn.prop('disabled', false).text('Verify & Claim');
                }
            });
        });

        $(document).on('click', '#backToStep1Btn', function () {
            $('#couponStep2').hide();
            $('#couponStep1').show();
            $('.coupon_verify_error').text('');
            $('#app_coupon_otp').val('');
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
