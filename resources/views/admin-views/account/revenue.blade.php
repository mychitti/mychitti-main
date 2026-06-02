@extends('layouts.admin.app')

@section('title', translate('Revenue Analytics'))

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
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .metric-box {
            padding: 18px;
            border-radius: 15px;
            color: white;
            text-align: center;
            transition: transform 0.3s;
        }

        .metric-box:hover {
            transform: translateY(-5px);
        }

        .metric-label {
            font-size: 11px;
            margin-bottom: 6px;
            opacity: 0.95;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .metric-number {
            font-size: 24px;
            font-weight: bold;
        }

        /* Gradient Metrics matching Account Management theme */
        .total-box {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
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

        @media (max-width: 968px) {
            .metrics-row {
                grid-template-columns: repeat(2, 1fr);
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
                <span>{{ translate('Revenue Analytics') }}</span>
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
            <!-- Total Revenue -->
            <div class="metric-box total-box">
                <div class="metric-label">{{ translate('Total Revenue') }}</div>
                <div class="metric-number">{{ \App\CentralLogics\Helpers::format_currency($total_income) }}</div>
            </div>

            <!-- Bills / Subscriptions -->
            <div class="metric-box income-box">
                <div class="metric-label">{{ translate('Bills / Subscriptions') }}</div>
                <div class="metric-number">{{ \App\CentralLogics\Helpers::format_currency($subscription_income) }}</div>
            </div>

            <!-- Module Purchases -->
            <div class="metric-box module-box">
                <div class="metric-label">{{ translate('Module Purchases') }}</div>
                <div class="metric-number">{{ \App\CentralLogics\Helpers::format_currency($module_income) }}</div>
            </div>

            <!-- Lead Subscriptions -->
            <div class="metric-box lead-box">
                <div class="metric-label">{{ translate('Lead Subscriptions') }}</div>
                <div class="metric-number">{{ \App\CentralLogics\Helpers::format_currency($lead_sub_income) }}</div>
            </div>

            <!-- Custom Domains -->
            <div class="metric-box domain-box">
                <div class="metric-label">{{ translate('Custom Domains') }}</div>
                <div class="metric-number">{{ \App\CentralLogics\Helpers::format_currency($domain_income) }}</div>
            </div>
        </div>

        <!-- Charts Split Row -->
        <div class="row g-3 mb-4">
            <!-- Left Side: Bar Trend Chart -->
            <div class="col-lg-8">
                <div class="chart-container h-100">
                    <div class="chart-title-row">
                        <h2 class="chart-heading">
                            <i class="tio-chart-bar-1 mr-1 text-primary"></i>
                            @if ($preset === 'all_time')
                                {{ translate('Revenue Trend (Last 6 Months)') }}
                            @elseif ($preset === 'this_year')
                                {{ translate('Revenue Trend (This Year)') }}
                            @elseif ($preset === 'previous_year')
                                {{ translate('Revenue Trend (Previous Year)') }}
                            @elseif ($preset === 'this_month')
                                {{ translate('Revenue Trend (This Month)') }}
                            @elseif ($preset === 'previous_month')
                                {{ translate('Revenue Trend (Previous Month)') }}
                            @elseif ($preset === 'this_week')
                                {{ translate('Revenue Trend (This Week)') }}
                            @elseif ($preset === 'previous_week')
                                {{ translate('Revenue Trend (Previous Week)') }}
                            @else
                                {{ translate('Revenue Trend (Selected Period)') }}
                            @endif
                        </h2>
                    </div>
                    <div class="chart-body d-flex flex-column" style="min-height: 250px;">
                        <canvas id="incomeTrendChart" style="max-height: 250px;"></canvas>
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
                    <div class="chart-body d-flex flex-column justify-content-between">
                        <div class="chartjs-custom w-100 mb-3">
                            <canvas id="revenueShareChart" style="max-height: 120px;"></canvas>
                        </div>
                        
                        <!-- Custom Legend / Share Values Breakdown -->
                        <div class="w-100 mt-2">
                            @php
                                $total_calc = $total_income > 0 ? $total_income : 1;
                                $share_data = [
                                    ['label' => translate('Bills / Subscriptions'), 'val' => $subscription_income, 'pct' => ($subscription_income / $total_calc) * 100, 'clr' => '#10b981'],
                                    ['label' => translate('Module Purchases'), 'val' => $module_income, 'pct' => ($module_income / $total_calc) * 100, 'clr' => '#8b5cf6'],
                                    ['label' => translate('Lead Plans'), 'val' => $lead_sub_income, 'pct' => ($lead_sub_income / $total_calc) * 100, 'clr' => '#fbbf24'],
                                    ['label' => translate('Custom Domains'), 'val' => $domain_income, 'pct' => ($domain_income / $total_calc) * 100, 'clr' => '#64748b'],
                                ];
                            @endphp

                            @foreach($share_data as $sd)
                                <div class="share-legend-item">
                                    <div>
                                        <span class="share-legend-indicator" style="background-color: {{ $sd['clr'] }};"></span>
                                        <span class="text-muted">{{ $sd['label'] }}</span>
                                    </div>
                                    <div class="text-dark">
                                        <span>{{ number_format($sd['pct'], 1) }}%</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Tab Breakdowns -->
        <div class="table-section">
            <div class="nav nav-tabs nav-tabs-custom" id="incomeTabs" role="tablist">
                <a class="nav-link active" id="invoices-tab" data-toggle="tab" href="#invoices" role="tab" aria-controls="invoices" aria-selected="true">
                    <i class="tio-receipt mr-1"></i>
                    {{ translate('Invoices & Billing') }} ({{ count($invoices) }})
                </a>
                <a class="nav-link" id="leads-tab" data-toggle="tab" href="#leads" role="tab" aria-controls="leads" aria-selected="false">
                    <i class="tio-send mr-1"></i>
                    {{ translate('Lead Subscriptions') }} ({{ count($lead_subscriptions) }})
                </a>
                <a class="nav-link" id="domains-tab" data-toggle="tab" href="#domains" role="tab" aria-controls="domains" aria-selected="false">
                    <i class="tio-globe mr-1"></i>
                    {{ translate('Custom Domains') }} ({{ count($domains) }})
                </a>
            </div>

            <div class="tab-content" id="incomeTabsContent">
                <!-- Paid Admin Invoices Tab -->
                <div class="tab-pane fade show active" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">{{ translate('sl') }}</th>
                                    <th>{{ translate('Invoice ID') }}</th>
                                    <th>{{ translate('Store Name') }}</th>
                                    <th>{{ translate('Invoice Date') }}</th>
                                    <th>{{ translate('Payment Method') }}</th>
                                    <th>{{ translate('Billing Type') }}</th>
                                    <th>{{ translate('Details') }}</th>
                                    <th class="text-right">{{ translate('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoices as $key => $invoice)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>
                                            <span class="font-weight-bold text-dark">{{ $invoice->invoice_id }}</span>
                                        </td>
                                        <td>
                                            @if ($invoice->websiteVendor)
                                                <a href="{{ route('admin.store.view', $invoice->bill_to) }}" class="font-weight-bold">
                                                    {{ $invoice->websiteVendor->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">{{ translate('N/A') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ \App\CentralLogics\Helpers::date_format($invoice->invoice_date ?? $invoice->created_at) }}</td>
                                        <td>
                                            <span class="badge badge-soft-info px-2 py-1 text-capitalize" style="border-radius: 4px;">
                                                {{ str_replace('_', ' ', $invoice->payment_method) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-primary px-2 py-1" style="border-radius: 4px;">
                                                {{ translate($invoice->invoice_type) }}
                                            </span>
                                        </td>
                                        <td>
                                            @foreach ($invoice->invoiceItems as $item)
                                                <div class="text-muted" style="font-size: 12px; line-height: 1.4;">
                                                    &bull; {!! $item->name !!} 
                                                </div>
                                            @endforeach
                                        </td>
                                        <td class="text-right">
                                            <span class="font-weight-bold text-dark">{{ \App\CentralLogics\Helpers::format_currency($invoice->total_amount) }}</span>
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

                <!-- Lead Subscriptions Tab -->
                <div class="tab-pane fade" id="leads" role="tabpanel" aria-labelledby="leads-tab">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">{{ translate('sl') }}</th>
                                    <th>{{ translate('Subscription ID') }}</th>
                                    <th>{{ translate('Store Name') }}</th>
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
                                        <td>
                                            <span class="font-weight-bold text-dark">#LS-{{ $sub->id }}</span>
                                        </td>
                                        <td>
                                            @if ($sub->store)
                                                <a href="{{ route('admin.store.view', $sub->store_id) }}" class="font-weight-bold">
                                                    {{ $sub->store->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">{{ translate('N/A') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ \App\CentralLogics\Helpers::date_format($sub->created_at) }}</td>
                                        <td>
                                            <span class="badge badge-soft-info px-2 py-1 text-capitalize" style="border-radius: 4px;">
                                                {{ translate($sub->type) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-warning px-2 py-1" style="border-radius: 4px;">
                                                {{ $sub->plan ? $sub->plan->name : translate('Custom Plan') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-muted" style="font-size: 12px; line-height: 1.4;">
                                                <strong>Starts:</strong> {{ \App\CentralLogics\Helpers::date_format($sub->starts_at) }} <br/>
                                                <strong>Expires:</strong> {{ \App\CentralLogics\Helpers::date_format($sub->expires_at) }}
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <span class="font-weight-bold text-dark">{{ \App\CentralLogics\Helpers::format_currency($sub->plan ? $sub->plan->price : 0) }}</span>
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

                <!-- Custom Domains Tab -->
                <div class="tab-pane fade" id="domains" role="tabpanel" aria-labelledby="domains-tab">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">{{ translate('sl') }}</th>
                                    <th>{{ translate('Store Name') }}</th>
                                    <th>{{ translate('Domain Name') }}</th>
                                    <th>{{ translate('Purchased Date') }}</th>
                                    <th>{{ translate('Status') }}</th>
                                    <th>{{ translate('Subtotal') }}</th>
                                    <th>{{ translate('Tax') }}</th>
                                    <th class="text-right">{{ translate('Total Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($domains as $key => $domain)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>
                                            @if ($domain->store)
                                                <a href="{{ route('admin.store.view', $domain->store_id) }}" class="font-weight-bold">
                                                    {{ $domain->store->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">{{ translate('N/A') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="https://{{ $domain->domain }}" target="_blank" class="text-primary font-weight-bold">
                                                {{ $domain->domain }}
                                            </a>
                                        </td>
                                        <td>{{ \App\CentralLogics\Helpers::date_format($domain->activated_at ?? $domain->created_at) }}</td>
                                        <td>
                                            <span class="badge {{ $domain->status === 'active' ? 'badge-soft-success' : 'badge-soft-secondary' }} px-2 py-1 text-capitalize" style="border-radius: 4px;">
                                                {{ $domain->status }}
                                            </span>
                                        </td>
                                        <td>{{ \App\CentralLogics\Helpers::format_currency($domain->charge) }}</td>
                                        <td>{{ \App\CentralLogics\Helpers::format_currency($domain->gst_amount) }} <span class="text-muted" style="font-size: 11px;">({{ $domain->gst_percent }}%)</span></td>
                                        <td class="text-right">
                                            <span class="font-weight-bold text-dark">{{ \App\CentralLogics\Helpers::format_currency($domain->total_amount) }}</span>
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

            // ─── Chart 1: Income Trend Bar Chart ───
            var ctxTrend = document.getElementById('incomeTrendChart').getContext('2d');
            var trendData = @json($chart_data);

            var labels = trendData.map(item => item.month);
            var subscriptionData = trendData.map(item => item.subscription);
            var moduleData = trendData.map(item => item.module);
            var leadData = trendData.map(item => item.lead);
            var domainData = trendData.map(item => item.domain);

            // Create Premium Gradients
            var subGradient = ctxTrend.createLinearGradient(0, 0, 0, 350);
            subGradient.addColorStop(0, '#34d399');
            subGradient.addColorStop(1, '#10b981'); // Match Income green theme

            var modGradient = ctxTrend.createLinearGradient(0, 0, 0, 350);
            modGradient.addColorStop(0, '#a78bfa');
            modGradient.addColorStop(1, '#8b5cf6'); // Purple module theme

            var leadGradient = ctxTrend.createLinearGradient(0, 0, 0, 350);
            leadGradient.addColorStop(0, '#fcd34d');
            leadGradient.addColorStop(1, '#fbbf24'); // Yellow lead theme

            var domGradient = ctxTrend.createLinearGradient(0, 0, 0, 350);
            domGradient.addColorStop(0, '#cbd5e1');
            domGradient.addColorStop(1, '#64748b'); // Gray custom domain theme

            var trendChart = new Chart(ctxTrend, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '{{ translate('Bills / Subscriptions') }}',
                            data: subscriptionData,
                            backgroundColor: subGradient,
                            hoverBackgroundColor: '#059669',
                            borderColor: '#10b981',
                            hoverBorderColor: '#059669',
                            borderWidth: 1
                        },
                        {
                            label: '{{ translate('Module Purchases') }}',
                            data: moduleData,
                            backgroundColor: modGradient,
                            hoverBackgroundColor: '#7c3aed',
                            borderColor: '#8b5cf6',
                            hoverBorderColor: '#7c3aed',
                            borderWidth: 1
                        },
                        {
                            label: '{{ translate('Lead Plans') }}',
                            data: leadData,
                            backgroundColor: leadGradient,
                            hoverBackgroundColor: '#d97706',
                            borderColor: '#fbbf24',
                            hoverBorderColor: '#d97706',
                            borderWidth: 1
                        },
                        {
                            label: '{{ translate('Custom Domains') }}',
                            data: domainData,
                            backgroundColor: domGradient,
                            hoverBackgroundColor: '#475569',
                            borderColor: '#64748b',
                            hoverBorderColor: '#475569',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cornerRadius: 6,
                    scales: {
                        xAxes: [{
                            stacked: true,
                            barPercentage: 0.85,
                            categoryPercentage: 0.55,
                            maxBarThickness: 50,
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
                            stacked: true,
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
                        display: false
                    }
                }
            });

            // ─── Chart 2: Revenue Share Doughnut Chart ───
            var ctxShare = document.getElementById('revenueShareChart').getContext('2d');
            var shareChart = new Chart(ctxShare, {
                type: 'doughnut',
                data: {
                    labels: [
                        '{{ translate('Bills / Subscriptions') }}',
                        '{{ translate('Module Purchases') }}',
                        '{{ translate('Lead Plans') }}',
                        '{{ translate('Custom Domains') }}'
                    ],
                    datasets: [{
                        data: [
                            {{ $subscription_income }},
                            {{ $module_income }},
                            {{ $lead_sub_income }},
                            {{ $domain_income }}
                        ],
                        backgroundColor: ['#10b981', '#8b5cf6', '#fbbf24', '#64748b'],
                        hoverOffset: 4,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutoutPercentage: 70,
                    legend: {
                        display: false
                    },
                    tooltips: {
                        backgroundColor: '#1e293b',
                        padding: 10,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var label = data.labels[tooltipItem.index] || '';
                                var val = data.datasets[0].data[tooltipItem.index];
                                return label + ': {{ \App\CentralLogics\Helpers::currency_symbol() }}' + val.toLocaleString();
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
