@extends('layouts.admin.app')

@section('title', translate('Finance & Profitability Hub'))

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <style>
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .brand-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: bold;
            color: #1e293b;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .metrics-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .metric-box {
            padding: 20px 10px;
            border-radius: 16px;
            color: #334155;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .metric-box::after {
            content: '';
            position: absolute;
            top: -45%;
            right: -15%;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.45);
            border-radius: 50%;
            pointer-events: none;
        }

        .metric-box>* {
            position: relative;
            z-index: 1;
        }

        .metric-box:hover {
            transform: translateY(-4px);
        }

        .metric-label {
            font-size: 10px;
            margin-bottom: 6px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .metric-number {
            font-size: 18px;
            font-weight: 800;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Gradient Metrics matching Account Management theme */
        .total-box {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            box-shadow: 0 6px 18px rgba(5, 150, 105, 0.12);
        }

        .total-box .metric-number {
            color: #047857;
        }

        .income-box {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .module-box {
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
        }

        .lead-box {
            background: linear-gradient(135deg, #fbbf24, #d97706);
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.3);
        }

        .domain-box {
            background: linear-gradient(135deg, #64748b, #475569);
            box-shadow: 0 4px 15px rgba(100, 116, 139, 0.3);
        }

        .wallet-box {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            box-shadow: 0 6px 18px rgba(2, 132, 199, 0.12);
        }

        .wallet-box .metric-number {
            color: #0369a1;
        }

        .leadval-box {
            background: linear-gradient(135deg, #fefce8 0%, #fef08a 100%);
            box-shadow: 0 6px 18px rgba(202, 138, 4, 0.12);
        }

        .leadval-box .metric-number {
            color: #a16207;
        }

        .template-box {
            background: linear-gradient(135deg, #fdf4ff 0%, #f5d0fe 100%);
            box-shadow: 0 6px 18px rgba(168, 85, 247, 0.12);
        }

        .template-box .metric-number {
            color: #a21caf;
            font-size: 17px;
        }

        .vendorspend-box {
            background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
            box-shadow: 0 6px 18px rgba(13, 148, 136, 0.12);
        }

        .vendorspend-box .metric-number {
            color: #0f766e;
        }

        .expense-box {
            background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
            box-shadow: 0 6px 18px rgba(225, 29, 72, 0.12);
        }

        .expense-box .metric-number {
            color: #be123c;
        }

        .profit-box {
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            box-shadow: 0 6px 18px rgba(79, 70, 229, 0.12);
        }

        .profit-box .metric-number {
            color: #4338ca;
        }

        .receivables-box {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            box-shadow: 0 6px 18px rgba(217, 119, 6, 0.12);
        }

        .receivables-box .metric-number {
            color: #d97706;
        }

        .tax-box {
            background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
            box-shadow: 0 6px 18px rgba(147, 51, 234, 0.12);
        }

        .tax-box .metric-number {
            color: #9333ea;
        }

        /* Content layouts */
        .chart-container {
            background: #f8fafc;
            padding: 18px;
            border-radius: 15px;
            border: 1px solid #e2e8f0;
        }

        .chart-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .chart-heading {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        /* Custom Legend items */
        .share-legend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 6px;
            background: white;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #f1f5f9;
        }

        .share-legend-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
            display: inline-block;
        }

        /* Tabbed table layouts */
        .table-section {
            background: #f8fafc;
            padding: 18px;
            border-radius: 15px;
            border: 1px solid #e2e8f0;
            margin-top: 20px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
        }

        .nav-tabs-custom {
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            background: transparent;
            color: #64748b;
            font-weight: 600;
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .nav-tabs-custom .nav-link:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .nav-tabs-custom .nav-link.active {
            background: #3b82f6;
            color: white;
        }

        .data-table {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            border-collapse: collapse;
        }

        .data-table thead {
            background: linear-gradient(135deg, #cbd5e1, #e2e8f0);
        }

        .data-table th {
            padding: 12px;
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            text-align: center;
            border: none;
        }

        .data-table td {
            padding: 12px;
            font-size: 13px;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
        }

        .data-table tbody tr:hover {
            background: #f8fafc;
        }

        @media (max-width: 1200px) {
            .metrics-row {
                grid-template-columns: repeat(2, 1fr);
            }
            .metric-number {
                font-size: 20px;
            }
        }

        @media (max-width: 576px) {
            .metrics-row {
                grid-template-columns: 1fr;
            }

            .top-header {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="dashboard-wrapper p-3">
        <!-- Page Header -->
        <div class="top-header">
            <div class="brand-title">
                <span>{{ translate('Finance & Profitability Hub') }}</span>
            </div>
            
            <div class="header-actions">
                @if (isset($preset) && $preset != 'all_time')
                    <span class="badge badge-soft-success font-weight-bold px-3 py-2" style="font-size: 12px; border-radius: 20px; display: flex; align-items: center;">
                        <i class="tio-date-range mr-1"></i>
                        {{ $from }} &mdash; {{ $to }}
                    </span>
                @endif

                <form action="" method="GET" class="date-range-form mb-0">
                    @include('vendor-views/form_modals/date_range')
                    <button class="btn btn-outline-warning" type="button" data-toggle="modal" data-target="#dateRangeModal" style="border-radius: 8px; height: 38px; display: flex; align-items: center; gap: 6px;">
                        <i class="tio-date-range"></i>
                        {{ translate($preset) }}
                    </button>
                </form>
            </div>
        </div>

        <!-- KPI Cards Grid -->
        <div class="metrics-row">
            <!-- Total Income -->
            <div class="metric-box total-box">
                <div class="metric-label" style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    {{ translate('Total Income') }}
                    <i class="tio-info-outfield text-muted" data-toggle="tooltip" data-placement="top" title="{{ translate('Sum of all paid store subscriptions, purchased modules, template sales, custom domains, and wallet recharge credits.') }}"></i>
                </div>
                <div class="metric-number">{{ \App\CentralLogics\Helpers::format_currency($total_income) }}</div>
            </div>

            <!-- Total Expenses -->
            <div class="metric-box expense-box">
                <div class="metric-label" style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    {{ translate('Total Expenses') }}
                    <i class="tio-info-outfield text-muted" data-toggle="tooltip" data-placement="top" title="{{ translate('Sum of platform overheads and paid admin purchase invoices.') }}"></i>
                </div>
                <div class="metric-number">{{ \App\CentralLogics\Helpers::format_currency($total_expense) }}</div>
            </div>

            <!-- Net Profit -->
            <div class="metric-box profit-box">
                <div class="metric-label" style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    {{ translate('Net Profit') }}
                    <i class="tio-info-outfield text-muted" data-toggle="tooltip" data-placement="top" title="{{ translate('Net platform earnings (Total Income minus Total Expenses).') }}"></i>
                </div>
                <div class="metric-number">{{ \App\CentralLogics\Helpers::format_currency($net_profit) }}</div>
                <div style="font-size:11px; color:#4338ca; margin-top:4px; font-weight: 600;">
                    {{ translate('Margin') }}: {{ number_format($profit_margin, 1) }}%
                </div>
            </div>

            <!-- Outstanding Receivables -->
            <div class="metric-box receivables-box">
                <div class="metric-label" style="color: #b45309; display: flex; align-items: center; justify-content: center; gap: 4px;">
                    {{ translate('Outstanding Receivables') }}
                    <i class="tio-info-outfield text-muted" data-toggle="tooltip" data-placement="top" title="{{ translate('Invoiced platform charges that are currently unpaid or pending vendor action.') }}"></i>
                </div>
                <div class="metric-number">{{ \App\CentralLogics\Helpers::format_currency($total_receivables) }}</div>
                <div style="font-size:11px; color:#d97706; margin-top:4px;">
                    {{ translate('Unpaid / Pending Invoices') }}
                </div>
            </div>

            <!-- Tax Collected -->
            <div class="metric-box tax-box">
                <div class="metric-label" style="color: #7e22ce; display: flex; align-items: center; justify-content: center; gap: 4px;">
                    {{ translate('Tax Collected') }}
                    <i class="tio-info-outfield text-muted" data-toggle="tooltip" data-placement="top" title="{{ translate('Government tax breakdowns (CGST/SGST/IGST) parsed from paid platform invoices.') }}"></i>
                </div>
                <div class="metric-number" style="font-size: 20px; font-weight: 800;">{{ \App\CentralLogics\Helpers::format_currency($total_tax) }}</div>
                <div style="font-size:9px; color:#9333ea; margin-top:4px; line-height: 1.2;">
                    CGST: {{ \App\CentralLogics\Helpers::format_currency($total_cgst) }} | SGST: {{ \App\CentralLogics\Helpers::format_currency($total_sgst) }} @if($total_igst > 0) | IGST: {{ \App\CentralLogics\Helpers::format_currency($total_igst) }} @endif
                </div>
            </div>

            <!-- Wallet Recharges -->
            <div class="metric-box wallet-box">
                <div class="metric-label" style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    {{ translate('Wallet Recharges') }}
                    <i class="tio-info-outfield text-muted" data-toggle="tooltip" data-placement="top" title="{{ translate('Total platform credits recharged by vendors into store wallets.') }}"></i>
                </div>
                <div class="metric-number">{{ \App\CentralLogics\Helpers::format_currency($wallet_recharge_income) }}</div>
            </div>

            <!-- Average Lead Value -->
            <div class="metric-box leadval-box">
                <div class="metric-label" style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    {{ translate('Avg Lead Value') }}
                    <i class="tio-info-outfield text-muted" data-toggle="tooltip" data-placement="top" title="{{ translate('Average revenue earned per service-request lead generated and accepted.') }}"></i>
                </div>
                <div class="metric-number">{{ \App\CentralLogics\Helpers::format_currency($avg_lead_value) }}</div>
                <div style="font-size:11px; color:#a16207; margin-top:4px;">
                    {{ $lead_count }} {{ translate('leads') }} · {{ \App\CentralLogics\Helpers::format_currency($lead_income) }} {{ translate('total') }}
                </div>
            </div>

            <!-- Popular Template -->
            <div class="metric-box template-box">
                <div class="metric-label" style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    {{ translate('Popular Template') }}
                    <i class="tio-info-outfield text-muted" data-toggle="tooltip" data-placement="top" title="{{ translate('Most purchased webpage template style bought by vendors.') }}"></i>
                </div>
                <div class="metric-number">{{ $popular_template['name'] ?? '—' }}</div>
                <div style="font-size:11px; color:#86198f; margin-top:4px;">
                    @if ($popular_template){{ $popular_template['count'] }} {{ translate('purchases') }} · @endif
                    {{ \App\CentralLogics\Helpers::format_currency($template_income) }} {{ translate('total') }}
                </div>
            </div>

            <!-- Avg Vendor Spend (ARPV across all vendors) -->
            <div class="metric-box vendorspend-box">
                <div class="metric-label" style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    {{ translate('Avg Spend / Vendor') }}
                    <i class="tio-info-outfield text-muted" data-toggle="tooltip" data-placement="top" title="{{ translate('Average Revenue Per Vendor (ARPV) across all stores registered on the platform.') }}"></i>
                </div>
                <div class="metric-number">{{ \App\CentralLogics\Helpers::format_currency($avg_vendor_spend_all) }}</div>
                <div style="font-size:11px; color:#0f766e; margin-top:4px;">
                    {{ translate('across') }} {{ $total_vendors }} {{ translate('vendors') }}
                </div>
            </div>

            <!-- Avg Spend per Paying Vendor -->
            <div class="metric-box vendorspend-box">
                <div class="metric-label" style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    {{ translate('Avg Spend / Payer') }}
                    <i class="tio-info-outfield text-muted" data-toggle="tooltip" data-placement="top" title="{{ translate('Average spend restricted only to vendors who have actually purchased a paid service.') }}"></i>
                </div>
                <div class="metric-number">{{ \App\CentralLogics\Helpers::format_currency($avg_vendor_spend) }}</div>
                <div style="font-size:11px; color:#0f766e; margin-top:4px;">
                    {{ $vendor_count }} {{ translate('paying vendors') }}
                </div>
            </div>
        </div>

        <!-- Executive Summary & Dynamic Financial Insights -->
        @if(isset($insights) && count($insights) > 0)
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-left: 5px solid #3b82f6 !important; box-shadow: 0 4px 12px rgba(0,0,0,0.03) !important;">
                <div class="card-body p-4">
                    <h4 class="card-title h5 mb-3" style="color: #1e293b; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        <i class="tio-chat-outlined text-primary" style="font-size: 20px;"></i>
                        {{ translate('Executive Summary & Insights') }}
                    </h4>
                    <div class="row">
                        @foreach($insights as $insight)
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center mr-3" style="background-color: white; box-shadow: 0 2px 6px rgba(0,0,0,0.03); flex-shrink: 0; width: 34px; height: 34px;">
                                        <i class="{{ $insight['icon'] }} text-{{ $insight['type'] }}" style="font-size: 16px;"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1" style="font-size: 13px; font-weight: 700; color: #334155;">{{ translate($insight['title']) }}</h5>
                                        <p class="text-muted mb-0" style="font-size: 12px; line-height: 1.45;">{{ translate($insight['text']) }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Charts split row -->
        <div class="row g-3 mb-4">
            <!-- Left Side: Bar Trend Chart -->
            <div class="col-lg-8">
                <div class="chart-container h-100">
                    <div class="chart-title-row">
                        <h2 class="chart-heading">
                            <i class="tio-chart-bar-1 mr-1 text-primary"></i>
                            @if ($preset === 'all_time')
                                {{ translate('Financial Trends & Bottom Line (Last 6 Months)') }}
                            @elseif ($preset === 'this_year')
                                {{ translate('Financial Trends & Bottom Line (This Year)') }}
                            @elseif ($preset === 'previous_year')
                                {{ translate('Financial Trends & Bottom Line (Previous Year)') }}
                            @elseif ($preset === 'this_month')
                                {{ translate('Financial Trends & Bottom Line (This Month)') }}
                            @elseif ($preset === 'previous_month')
                                {{ translate('Financial Trends & Bottom Line (Previous Month)') }}
                            @elseif ($preset === 'this_week')
                                {{ translate('Financial Trends & Bottom Line (This Week)') }}
                            @elseif ($preset === 'previous_week')
                                {{ translate('Financial Trends & Bottom Line (Previous Week)') }}
                            @else
                                {{ translate('Financial Trends & Bottom Line (Selected Period)') }}
                            @endif
                        </h2>
                        @if (Route::has('admin.transactions.report.income-sources'))
                            <a href="{{ route('admin.transactions.report.income-sources') }}" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">
                                <i class="tio-chart-pie-1 mr-1"></i> {{ translate('Detailed Income Report') }}
                            </a>
                        @endif
                    </div>
                    <div class="chart-body d-flex flex-column" style="min-height: 320px;">
                        <canvas id="incomeExpenseChart" style="max-height: 320px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- Right Side: Revenue Share Breakdown Chart -->
            <div class="col-lg-4">
                <div class="chart-container h-100">
                    <div class="chart-title-row">
                        <h2 class="chart-heading">
                            <i class="tio-chart-pie-1 mr-1 text-primary"></i>
                            {{ translate('Revenue Share Breakdown') }}
                        </h2>
                    </div>
                    <div class="chart-body d-flex flex-column justify-content-between" style="min-height: 320px;">
                        <div class="w-100 mb-3" style="max-height: 180px; position: relative;">
                            <canvas id="revenueShareChart" style="max-height: 180px;"></canvas>
                        </div>
                        
                        <!-- Custom Legend / Share Values Breakdown -->
                        <div class="w-100 px-2 mt-2" style="font-size: 11px;">
                            @php
                                $total_calc = $total_income > 0 ? $total_income : 1;
                                $share_data = [
                                    ['label' => translate('Subscriptions'), 'val' => $subscription_income, 'pct' => ($subscription_income / $total_calc) * 100, 'clr' => '#8b5cf6'],
                                    ['label' => translate('Modules'), 'val' => $module_income, 'pct' => ($module_income / $total_calc) * 100, 'clr' => '#3b82f6'],
                                    ['label' => translate('Leads'), 'val' => $lead_sub_income, 'pct' => ($lead_sub_income / $total_calc) * 100, 'clr' => '#fbbf24'],
                                    ['label' => translate('Domains'), 'val' => $domain_income, 'pct' => ($domain_income / $total_calc) * 100, 'clr' => '#64748b'],
                                    ['label' => translate('Wallets'), 'val' => $wallet_recharge_income, 'pct' => ($wallet_recharge_income / $total_calc) * 100, 'clr' => '#06b6d4'],
                                    ['label' => translate('Templates'), 'val' => $template_income, 'pct' => ($template_income / $total_calc) * 100, 'clr' => '#ec4899'],
                                ];
                            @endphp

                            <div class="row g-1">
                                @foreach($share_data as $sd)
                                    @if($sd['val'] > 0 || $total_income == 0)
                                        <div class="col-6">
                                            <div class="share-legend-item d-flex align-items-center justify-content-between p-1 px-2 border rounded bg-white mb-1" style="font-size:10px; border-color: #f1f5f9 !important;">
                                                <div class="text-truncate mr-1">
                                                    <span class="share-legend-indicator" style="background-color: {{ $sd['clr'] }}; width: 6px; height: 6px; display: inline-block; border-radius: 50%; margin-right: 4px;"></span>
                                                    <span class="text-muted">{{ $sd['label'] }}</span>
                                                </div>
                                                <span class="font-weight-bold text-dark">{{ number_format($sd['pct'], 1) }}%</span>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Transaction Records -->
        <div class="card custom-card mt-4 mb-4">
            <div class="card-header border-0 pb-0 p-0" style="background: white;">
                <ul class="nav nav-tabs nav-tabs-custom" id="financeTabs" role="tablist" style="border-bottom: 1px solid #e2e8f0; margin-bottom: 0px; padding: 0 1.25rem;">
                    <li class="nav-item">
                        <a class="nav-link active" id="invoices-tab" data-toggle="tab" href="#invoices" role="tab" aria-controls="invoices" aria-selected="true" style="padding: 1rem 1.25rem;">
                            <i class="tio-receipt mr-1"></i>
                            {{ translate('Subscriptions & Modules') }} ({{ count($invoices) }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="leads-tab" data-toggle="tab" href="#leads" role="tab" aria-controls="leads" aria-selected="false" style="padding: 1rem 1.25rem;">
                            <i class="tio-send mr-1"></i>
                            {{ translate('Lead Subscriptions') }} ({{ count($lead_subscriptions) }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="domains-tab" data-toggle="tab" href="#domains" role="tab" aria-controls="domains" aria-selected="false" style="padding: 1rem 1.25rem;">
                            <i class="tio-globe mr-1"></i>
                            {{ translate('Custom Domains') }} ({{ count($domains) }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="wallet-tab" data-toggle="tab" href="#wallet" role="tab" aria-controls="wallet" aria-selected="false" style="padding: 1rem 1.25rem;">
                            <i class="tio-wallet mr-1"></i>
                            {{ translate('Wallet Recharges') }} ({{ count($wallet_recharges) }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="templates-tab" data-toggle="tab" href="#templates" role="tab" aria-controls="templates" aria-selected="false" style="padding: 1rem 1.25rem;">
                            <i class="tio-format-text mr-1"></i>
                            {{ translate('Web Templates') }} ({{ count($template_purchases) }})
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="card-body p-0">
                <div class="tab-content" id="financeTabsContent">
                    <!-- Tab 1: Subscriptions & Modules -->
                    <div class="tab-pane fade show active" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
                        <div class="table-responsive">
                            <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 60px;">{{ translate('sl') }}</th>
                                        <th>{{ translate('Invoice ID') }}</th>
                                        <th>{{ translate('Store / Vendor') }}</th>
                                        <th>{{ translate('Invoice Date') }}</th>
                                        <th>{{ translate('Payment Method') }}</th>
                                        <th>{{ translate('Billing Type') }}</th>
                                        <th>{{ translate('Purchased Items') }}</th>
                                        <th class="text-right">{{ translate('Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($invoices as $key => $invoice)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>
                                                @if($invoice->pdf)
                                                    <a href="{{ asset('storage/app/public/invoice') . '/' . $invoice->pdf }}" target="_blank"><span class="font-weight-bold">{{ $invoice->invoice_id }}</span></a>
                                                @else
                                                    <span class="font-weight-bold text-dark">{{ $invoice->invoice_id }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($invoice->websiteVendor)
                                                    <a href="{{ route('admin.store.view', $invoice->bill_to) }}" class="font-weight-bold">{{ $invoice->websiteVendor->name }}</a>
                                                @else
                                                    <span class="text-muted">{{ translate('N/A') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ \App\CentralLogics\Helpers::date_format($invoice->invoice_date ?? $invoice->created_at) }}</td>
                                            <td>
                                                <span class="badge badge-soft-info px-2 py-1 text-capitalize">{{ str_replace('_', ' ', $invoice->payment_method) }}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-soft-primary px-2 py-1">{{ translate($invoice->invoice_type) }}</span>
                                            </td>
                                            <td>
                                                @foreach ($invoice->invoiceItems as $item)
                                                    <div class="text-muted" style="font-size: 11px; line-height: 1.45;">
                                                        &bull; {!! $item->name !!}
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td class="text-right font-weight-bold text-dark">
                                                {{ \App\CentralLogics\Helpers::format_currency($invoice->total_amount) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" class="mb-3" style="width: 80px;" alt="public">
                                                <h5 class="text-muted">{{ translate('no_data_found') }}</h5>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Lead Subscriptions -->
                    <div class="tab-pane fade" id="leads" role="tabpanel" aria-labelledby="leads-tab">
                        <div class="table-responsive">
                            <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 60px;">{{ translate('sl') }}</th>
                                        <th>{{ translate('Subscription ID') }}</th>
                                        <th>{{ translate('Store / Vendor') }}</th>
                                        <th>{{ translate('Purchase Date') }}</th>
                                        <th>{{ translate('Plan Type') }}</th>
                                        <th>{{ translate('Plan Name') }}</th>
                                        <th>{{ translate('Duration') }}</th>
                                        <th class="text-right">{{ translate('Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($lead_subscriptions as $key => $sub)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td><span class="font-weight-bold text-dark">#LS-{{ $sub->id }}</span></td>
                                            <td>
                                                @if ($sub->store)
                                                    <a href="{{ route('admin.store.view', $sub->store_id) }}" class="font-weight-bold">{{ $sub->store->name }}</a>
                                                @else
                                                    <span class="text-muted">{{ translate('N/A') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ \App\CentralLogics\Helpers::date_format($sub->created_at) }}</td>
                                            <td><span class="badge badge-soft-info px-2 py-1 text-capitalize">{{ translate($sub->type) }}</span></td>
                                            <td><span class="badge badge-soft-warning px-2 py-1">{{ $sub->plan ? $sub->plan->name : translate('Custom Plan') }}</span></td>
                                            <td>
                                                <div class="text-muted" style="font-size: 11px;">
                                                    <strong>Starts:</strong> {{ \App\CentralLogics\Helpers::date_format($sub->starts_at) }} <br/>
                                                    <strong>Expires:</strong> {{ \App\CentralLogics\Helpers::date_format($sub->expires_at) }}
                                                </div>
                                            </td>
                                            <td class="text-right font-weight-bold text-dark">
                                                {{ \App\CentralLogics\Helpers::format_currency($sub->plan ? $sub->plan->price : 0) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" class="mb-3" style="width: 80px;" alt="public">
                                                <h5 class="text-muted">{{ translate('no_data_found') }}</h5>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 3: Custom Domains -->
                    <div class="tab-pane fade" id="domains" role="tabpanel" aria-labelledby="domains-tab">
                        <div class="table-responsive">
                            <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 60px;">{{ translate('sl') }}</th>
                                        <th>{{ translate('Store / Vendor') }}</th>
                                        <th>{{ translate('Domain URL') }}</th>
                                        <th>{{ translate('Purchased Date') }}</th>
                                        <th>{{ translate('Status') }}</th>
                                        <th>{{ translate('Subtotal') }}</th>
                                        <th>{{ translate('Tax Collected') }}</th>
                                        <th class="text-right">{{ translate('Total Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($domains as $key => $domain)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>
                                                @if ($domain->store)
                                                    <a href="{{ route('admin.store.view', $domain->store_id) }}" class="font-weight-bold">{{ $domain->store->name }}</a>
                                                @else
                                                    <span class="text-muted">{{ translate('N/A') }}</span>
                                                @endif
                                            </td>
                                            <td><a href="https://{{ $domain->domain }}" target="_blank" class="text-primary font-weight-bold">{{ $domain->domain }}</a></td>
                                            <td>{{ \App\CentralLogics\Helpers::date_format($domain->activated_at ?? $domain->created_at) }}</td>
                                            <td><span class="badge {{ $domain->status === 'active' ? 'badge-soft-success' : 'badge-soft-secondary' }} px-2 py-1 text-capitalize">{{ $domain->status }}</span></td>
                                            <td>{{ \App\CentralLogics\Helpers::format_currency($domain->charge) }}</td>
                                            <td>{{ \App\CentralLogics\Helpers::format_currency($domain->gst_amount) }} <span class="text-muted" style="font-size: 10px;">({{ $domain->gst_percent }}%)</span></td>
                                            <td class="text-right font-weight-bold text-dark">{{ \App\CentralLogics\Helpers::format_currency($domain->total_amount) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" class="mb-3" style="width: 80px;" alt="public">
                                                <h5 class="text-muted">{{ translate('no_data_found') }}</h5>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 4: Wallet Recharges -->
                    <div class="tab-pane fade" id="wallet" role="tabpanel" aria-labelledby="wallet-tab">
                        <div class="table-responsive">
                            <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 60px;">{{ translate('sl') }}</th>
                                        <th>{{ translate('Store / Vendor') }}</th>
                                        <th>{{ translate('Recharge Date') }}</th>
                                        <th>{{ translate('Reason') }}</th>
                                        <th class="text-right">{{ translate('Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($wallet_recharges as $key => $recharge)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>
                                                @if ($recharge->store)
                                                    <a href="{{ route('admin.store.view', $recharge->from_id) }}" class="font-weight-bold">{{ $recharge->store->name }}</a>
                                                @else
                                                    <span class="text-muted">{{ translate('N/A') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ \App\CentralLogics\Helpers::date_format($recharge->created_at) }}</td>
                                            <td><span class="text-muted">{{ $recharge->reason }}</span></td>
                                            <td class="text-right font-weight-bold text-dark">{{ \App\CentralLogics\Helpers::format_currency($recharge->amount) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-5">
                                                <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" class="mb-3" style="width: 80px;" alt="public">
                                                <h5 class="text-muted">{{ translate('no_data_found') }}</h5>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 5: Web Templates -->
                    <div class="tab-pane fade" id="templates" role="tabpanel" aria-labelledby="templates-tab">
                        <div class="table-responsive">
                            <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 60px;">{{ translate('sl') }}</th>
                                        <th>{{ translate('Store / Vendor') }}</th>
                                        <th>{{ translate('Template ID') }}</th>
                                        <th>{{ translate('Purchase Date') }}</th>
                                        <th>{{ translate('Invoice ID') }}</th>
                                        <th class="text-right">{{ translate('Amount Paid') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($template_purchases as $key => $tpl)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>
                                                @if ($tpl->store)
                                                    <a href="{{ route('admin.store.view', $tpl->vendor_id) }}" class="font-weight-bold">{{ $tpl->store->name }}</a>
                                                @else
                                                    <span class="text-muted">{{ translate('N/A') }}</span>
                                                @endif
                                            </td>
                                            <td><span class="badge badge-soft-warning px-2 py-1">Template #{{ $tpl->template_id }}</span></td>
                                            <td>{{ \App\CentralLogics\Helpers::date_format($tpl->purchased_at) }}</td>
                                            <td><span class="text-muted">{{ $tpl->invoice_id ?? translate('Direct Payment') }}</span></td>
                                            <td class="text-right font-weight-bold text-dark">{{ \App\CentralLogics\Helpers::format_currency($tpl->amount_paid) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" class="mb-3" style="width: 80px;" alt="public">
                                                <h5 class="text-muted">{{ translate('no_data_found') }}</h5>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses (Admin Purchase Invoices) -->
        <div class="table-section mt-4">
            <h2 class="section-title">
                <i class="tio-receipt-outlined mr-1 text-danger"></i>
                {{ translate('Expenses — Purchase Invoices') }}
            </h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">{{ translate('sl') }}</th>
                            <th class="text-left">{{ translate('Invoice ID') }}</th>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Payment Status') }}</th>
                            <th class="text-right">{{ translate('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($expense_invoices as $key => $inv)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td class="text-left">
                                    <span class="font-weight-bold text-dark">{{ $inv->invoice_id }}</span>
                                </td>
                                <td>{{ \App\CentralLogics\Helpers::date_format($inv->invoice_date ?? $inv->created_at) }}</td>
                                <td>
                                    @if (strtolower($inv->payment_status) == 'paid')
                                        <span class="badge badge-soft-success">{{ translate('messages.paid') }}</span>
                                    @else
                                        <span class="badge badge-soft-danger">{{ ucfirst($inv->payment_status) }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <span class="font-weight-bold text-danger">{{ \App\CentralLogics\Helpers::format_currency($inv->total_amount) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" class="mb-3" style="width: 80px;" alt="public">
                                    <h5 class="text-muted">{{ translate('no_data_found') }}</h5>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (count($expense_invoices) > 0)
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-right font-weight-bold text-dark">{{ translate('Total Expenses') }}</td>
                                <td class="text-right font-weight-bold text-danger">{{ \App\CentralLogics\Helpers::format_currency($total_expense) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- Top Spending Vendors -->
        <div class="table-section">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                <h2 class="section-title mb-0">
                    <i class="tio-poi mr-1 text-primary"></i>
                    {{ translate('Top Spending Vendors') }}
                </h2>
                <a href="{{ route('admin.account.spending-vendors', ['date_range' => $preset]) }}"
                    class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
                    {{ translate('View all vendors') }} <i class="tio-chevron-right"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">{{ translate('sl') }}</th>
                            <th class="text-left">{{ translate('Vendor / Store') }}</th>
                            <th class="text-right">{{ translate('Amount Spent') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($top_vendors as $key => $v)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td class="text-left">
                                    @if (Route::has('admin.store.view'))
                                        <a href="{{ route('admin.store.view', $v['id']) }}" class="font-weight-bold text-dark">{{ $v['name'] }}</a>
                                    @else
                                        <span class="font-weight-bold text-dark">{{ $v['name'] }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <span class="font-weight-bold text-success">{{ \App\CentralLogics\Helpers::format_currency($v['amount']) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" class="mb-3" style="width: 80px;" alt="public">
                                    <h5 class="text-muted">{{ translate('no_data_found') }}</h5>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    @include('admin-views/js/date_range')
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js/dist/Chart.min.js"></script>

    <script>
        "use strict";

        $(document).on('ready', function() {
            // Helper to format ticks without cluttering the Y-axis
            function formatCurrencyTicks(value) {
                const symbol = '{{ \App\CentralLogics\Helpers::currency_symbol() }}';
                if (value >= 10000000) {
                    return symbol + (value / 10000000).toFixed(1) + ' Cr';
                } else if (value >= 100000) {
                    return symbol + (value / 100000).toFixed(1) + ' L';
                } else if (value >= 1000) {
                    return symbol + (value / 1000).toFixed(1) + ' K';
                }
                return symbol + value;
            }

            // ─── Income vs Expenses Chart ───
            var ctxIE = document.getElementById('incomeExpenseChart').getContext('2d');
            var trendData = @json($chart_data);

            var labels = trendData.map(item => item.month);
            var incomeData = trendData.map(item =>
                Number(item.subscription) + Number(item.module) + Number(item.lead) + Number(item.domain) + Number(item.wallet) + Number(item.template)
            );
            var expenseData = trendData.map(item => Number(item.expense));
            var netProfitData = trendData.map(item =>
                (Number(item.subscription) + Number(item.module) + Number(item.lead) + Number(item.domain) + Number(item.wallet) + Number(item.template)) - Number(item.expense)
            );

            var incomeGradient = ctxIE.createLinearGradient(0, 0, 0, 300);
            incomeGradient.addColorStop(0, '#34d399');
            incomeGradient.addColorStop(1, '#10b981');

            var expenseGradient = ctxIE.createLinearGradient(0, 0, 0, 300);
            expenseGradient.addColorStop(0, '#f87171');
            expenseGradient.addColorStop(1, '#ef4444');

            new Chart(ctxIE, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '{{ translate('Income') }}',
                            data: incomeData,
                            backgroundColor: incomeGradient,
                            hoverBackgroundColor: '#059669',
                            borderColor: '#10b981',
                            borderWidth: 1
                        },
                        {
                            label: '{{ translate('Expenses') }}',
                            data: expenseData,
                            backgroundColor: expenseGradient,
                            hoverBackgroundColor: '#dc2626',
                            borderColor: '#ef4444',
                            borderWidth: 1
                        },
                        {
                            label: '{{ translate('Net Profit') }}',
                            data: netProfitData,
                            type: 'line',
                            fill: false,
                            backgroundColor: '#4338ca',
                            borderColor: '#4338ca',
                            borderWidth: 2,
                            pointBackgroundColor: '#4338ca',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            lineTension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cornerRadius: 6,
                    scales: {
                        xAxes: [{
                            barPercentage: 0.85,
                            categoryPercentage: 0.6,
                            maxBarThickness: 40,
                            gridLines: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                fontColor: '#64748b',
                                fontSize: 11,
                                padding: 8
                            }
                        }],
                        yAxes: [{
                            gridLines: {
                                color: '#f1f5f9',
                                drawBorder: false,
                                zeroLineColor: '#f1f5f9',
                                borderDash: [5, 5]
                            },
                            ticks: {
                                beginAtZero: true,
                                fontColor: '#64748b',
                                fontSize: 11,
                                maxTicksLimit: 6,
                                padding: 10,
                                callback: function(value) {
                                    return formatCurrencyTicks(value);
                                }
                            }
                        }]
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#1e293b',
                        bodySpacing: 4,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var label = data.datasets[tooltipItem.datasetIndex].label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += '{{ \App\CentralLogics\Helpers::currency_symbol() }}' + tooltipItem.yLabel.toLocaleString();
                                return label;
                            }
                        }
                    },
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            fontColor: '#64748b',
                            fontSize: 11,
                            padding: 12
                        }
                    }
                }
            });

            // ─── Revenue Share Breakdown Donut Chart ───
            var ctxShare = document.getElementById('revenueShareChart').getContext('2d');
            var shareLabels = [];
            var shareDataValues = [];
            var shareColors = [];

            var shareSources = @json($share_data);
            shareSources.forEach(function(item) {
                if (item.val > 0 || {{ $total_income == 0 ? 1 : 0 }}) {
                    shareLabels.push(item.label);
                    shareDataValues.push(Number(item.val));
                    shareColors.push(item.clr);
                }
            });

            new Chart(ctxShare, {
                type: 'doughnut',
                data: {
                    labels: shareLabels,
                    datasets: [{
                        data: shareDataValues,
                        backgroundColor: shareColors,
                        borderWidth: 1,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: false
                    },
                    cutoutPercentage: 65,
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var val = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
                                var label = data.labels[tooltipItem.index] || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += '{{ \App\CentralLogics\Helpers::currency_symbol() }}' + val.toLocaleString();
                                return label;
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
