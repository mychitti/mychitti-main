@extends('layouts.admin.app')

@section('title', translate('Income Analytics'))

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <style>
        /* General Layout Enhancements */
        .page-header-title {
            font-size: 22px;
            font-weight: 700;
            color: #1e2022;
        }

        /* SaaS-style Premium KPI Card Styling */
        .income-kpi-card {
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
            border-radius: 12px !important;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02) !important;
            transition: all 0.25s ease-in-out;
            position: relative;
        }
        .income-kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06) !important;
            border-color: rgba(0, 0, 0, 0.08) !important;
        }
        .income-kpi-card .card-body {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Soft Background Icon Containers */
        .income-kpi-card .icon-container {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
            transition: all 0.3s;
        }

        /* Color Coding using Platform Accents */
        .kpi-total .icon-container { background: rgba(54, 162, 235, 0.1); color: #36a2eb; }
        .kpi-subscription .icon-container { background: rgba(255, 159, 64, 0.1); color: #ff9f40; }
        .kpi-module .icon-container { background: rgba(153, 102, 255, 0.1); color: #9966ff; }
        .kpi-lead .icon-container { background: rgba(255, 205, 86, 0.15); color: #ffcd56; }
        .kpi-domain .icon-container { background: rgba(201, 203, 207, 0.15); color: #8a94a6; }
        .kpi-wallet .icon-container { background: rgba(6, 182, 212, 0.1); color: #06b6d4; }

        .income-kpi-card .kpi-label {
            font-size: 11px;
            font-weight: 700;
            color: #8a94a6;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }

        .income-kpi-card .kpi-value-container {
            display: flex;
            align-items: baseline;
        }

        .income-kpi-card .currency-symbol {
            font-size: 16px;
            font-weight: 600;
            color: #7f8c8d;
            margin-right: 4px;
        }

        .income-kpi-card .kpi-value {
            font-size: 22px;
            font-weight: 750;
            color: #2c3e50;
            line-height: 1.1;
        }

        /* Tab Customization */
        .nav-tabs-custom {
            border-bottom: 1px solid #eef2f5;
            margin-bottom: 0px;
            padding: 0 1.25rem;
        }
        .nav-tabs-custom .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #7f8c8d;
            font-weight: 600;
            padding: 1rem 1.25rem;
            transition: all 0.2s;
            font-size: 14px;
        }
        .nav-tabs-custom .nav-link:hover {
            color: var(--primary, #0661cb);
        }
        .nav-tabs-custom .nav-link.active {
            border-bottom-color: var(--primary, #0661cb);
            color: var(--primary, #0661cb);
            background: transparent;
        }

        /* Clean Card Layouts */
        .custom-card {
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.015);
            background: #ffffff;
            overflow: hidden;
        }

        .custom-card-header {
            background-color: #ffffff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .custom-card-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1e2022;
            margin: 0;
        }

        /* Form Filter Styling */
        .filter-container {
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.015);
            padding: 1.25rem 1.5rem;
        }
        
        /* Table Styling Updates */
        .table thead th {
            background-color: #f8fafc;
            color: #677788;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e7eaf3;
            padding: 12px 16px;
        }
        .table tbody td {
            padding: 14px 16px;
            font-size: 13px;
            color: #212529;
            border-bottom: 1px solid #e7eaf3;
        }

        /* Legend item styling for doughnut chart */
        .share-legend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 4px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
        }
        .share-legend-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
            display: inline-block;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header mb-4 d-flex justify-content-between align-items-center">
            <h1 class="page-header-title">
                <span class="page-header-icon mr-2">
                    <i class="tio-money-vs text-primary" style="font-size: 26px;"></i>
                </span>
                <span>
                    {{ translate('Income Analytics') }}
                </span>
            </h1>
            
            <div class="d-flex align-items-center" style="gap: 12px;">
                @if (isset($preset) && $preset != 'all_time')
                    <span class="badge badge-soft-success font-weight-bold px-3 py-2" style="font-size: 12px; border-radius: 20px;">
                        <i class="tio-date-range mr-1"></i>
                        {{ $from }} &mdash; {{ $to }}
                    </span>
                @endif

                <form action="" method="GET" class="date-range-form mb-0">
                    @include('admin-views.form_modals.date_range')
                    <button class="btn btn-outline-primary font-weight-bold" type="button" data-toggle="modal" data-target="#dateRangeModal" style="border-radius: 8px; height: 42px; display: flex; align-items: center; gap: 6px;">
                        <i class="tio-date-range"></i>
                        {{ translate($preset) }}
                    </button>
                </form>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- KPI Cards Grid -->
        <div class="row g-3 mb-4">
            <!-- Total Income -->
            <div class="col">
                <div class="card income-kpi-card kpi-total">
                    <div class="card-body">
                        <div>
                            <div class="kpi-label">{{ translate('Total Platform Income') }}</div>
                            <div class="kpi-value-container">
                                <span class="currency-symbol">{{ \App\CentralLogics\Helpers::currency_symbol() }}</span>
                                <span class="kpi-value">{{ number_format($total_income, 2) }}</span>
                            </div>
                        </div>
                        <div class="icon-container">
                            <i class="tio-money-vs"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subscription Plans -->
            <div class="col">
                <div class="card income-kpi-card kpi-subscription">
                    <div class="card-body">
                        <div>
                            <div class="kpi-label">{{ translate('Subscription Plans') }}</div>
                            <div class="kpi-value-container">
                                <span class="currency-symbol">{{ \App\CentralLogics\Helpers::currency_symbol() }}</span>
                                <span class="kpi-value">{{ number_format($subscription_income, 2) }}</span>
                            </div>
                        </div>
                        <div class="icon-container">
                            <i class="tio-crown-outlined"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lead Subscriptions -->
            <div class="col">
                <div class="card income-kpi-card kpi-lead">
                    <div class="card-body">
                        <div>
                            <div class="kpi-label">{{ translate('Lead Subscriptions') }}</div>
                            <div class="kpi-value-container">
                                <span class="currency-symbol">{{ \App\CentralLogics\Helpers::currency_symbol() }}</span>
                                <span class="kpi-value">{{ number_format($lead_sub_income, 2) }}</span>
                            </div>
                        </div>
                        <div class="icon-container">
                            <i class="tio-send"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Domains -->
            <div class="col">
                <div class="card income-kpi-card kpi-domain">
                    <div class="card-body">
                        <div>
                            <div class="kpi-label">{{ translate('Custom Domains') }}</div>
                            <div class="kpi-value-container">
                                <span class="currency-symbol">{{ \App\CentralLogics\Helpers::currency_symbol() }}</span>
                                <span class="kpi-value">{{ number_format($domain_income, 2) }}</span>
                            </div>
                        </div>
                        <div class="icon-container">
                            <i class="tio-globe"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wallet Recharges -->
            <div class="col">
                <div class="card income-kpi-card kpi-wallet">
                    <div class="card-body">
                        <div>
                            <div class="kpi-label">{{ translate('Wallet Recharges') }}</div>
                            <div class="kpi-value-container">
                                <span class="currency-symbol">{{ \App\CentralLogics\Helpers::currency_symbol() }}</span>
                                <span class="kpi-value">{{ number_format($wallet_recharge_income, 2) }}</span>
                            </div>
                        </div>
                        <div class="icon-container">
                            <i class="tio-wallet"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End KPI Cards Grid -->

        <!-- Charts Split Row -->
        <div class="row g-3 mb-4">
            <!-- Left Side: Bar Trend Chart -->
            <div class="col-lg-8">
                <div class="card custom-card h-100">
                    <div class="custom-card-header">
                        <h3>
                            <i class="tio-chart-bar-1 mr-1 text-primary"></i>
                            @if ($preset === 'all_time')
                                {{ translate('Income Trend (Last 6 Months)') }}
                            @elseif ($preset === 'this_year')
                                {{ translate('Income Trend (This Year)') }}
                            @elseif ($preset === 'previous_year')
                                {{ translate('Income Trend (Previous Year)') }}
                            @elseif ($preset === 'this_month')
                                {{ translate('Income Trend (This Month)') }}
                            @elseif ($preset === 'previous_month')
                                {{ translate('Income Trend (Previous Month)') }}
                            @elseif ($preset === 'this_week')
                                {{ translate('Income Trend (This Week)') }}
                            @elseif ($preset === 'previous_week')
                                {{ translate('Income Trend (Previous Week)') }}
                            @else
                                {{ translate('Income Trend (Selected Period)') }}
                            @endif
                        </h3>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="chartjs-custom flex-grow-1 w-100" style="min-height: 350px;">
                            <canvas id="incomeTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Revenue Share Breakdown Chart -->
            <div class="col-lg-4">
                <div class="card custom-card h-100">
                    <div class="custom-card-header">
                        <h3>
                            <i class="tio-chart-pie-1 mr-1 text-primary"></i>
                            {{ translate('Revenue Share Breakdown') }}
                        </h3>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="chartjs-custom height-200px w-100 mb-3">
                            <canvas id="revenueShareChart"></canvas>
                        </div>
                        
                        <!-- Custom Legend / Share Values Breakdown -->
                        <div class="w-100 px-2 mt-2">
                            @php
                                $total_calc = $total_income > 0 ? $total_income : 1;
                                $share_data = [
                                    ['label' => translate('Subscription Plans'), 'val' => $subscription_income, 'pct' => ($subscription_income / $total_calc) * 100, 'clr' => '#ff9f40'],
                                    ['label' => translate('Lead Plans'), 'val' => $lead_sub_income, 'pct' => ($lead_sub_income / $total_calc) * 100, 'clr' => '#ffcd56'],
                                    ['label' => translate('Custom Domains'), 'val' => $domain_income, 'pct' => ($domain_income / $total_calc) * 100, 'clr' => '#8a94a6'],
                                    ['label' => translate('Wallet Recharges'), 'val' => $wallet_recharge_income, 'pct' => ($wallet_recharge_income / $total_calc) * 100, 'clr' => '#06b6d4'],
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
        <!-- End Charts Split Row -->

        <!-- Detailed Tab Breakdowns -->
        <div class="card custom-card">
            <div class="card-header border-0 pb-0 p-0">
                <ul class="nav nav-tabs nav-tabs-custom" id="incomeTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="invoices-tab" data-toggle="tab" href="#invoices" role="tab" aria-controls="invoices" aria-selected="true">
                            <i class="tio-receipt mr-1"></i>
                            {{ translate('Invoices & Billing') }} ({{ count($invoices) }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="leads-tab" data-toggle="tab" href="#leads" role="tab" aria-controls="leads" aria-selected="false">
                            <i class="tio-send mr-1"></i>
                            {{ translate('Lead Subscriptions') }} ({{ count($lead_subscriptions) }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="domains-tab" data-toggle="tab" href="#domains" role="tab" aria-controls="domains" aria-selected="false">
                            <i class="tio-globe mr-1"></i>
                            {{ translate('Custom Domains') }} ({{ count($domains) }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="wallet-tab" data-toggle="tab" href="#wallet" role="tab" aria-controls="wallet" aria-selected="false">
                            <i class="tio-wallet mr-1"></i>
                            {{ translate('Wallet Recharges') }} ({{ count($wallet_recharges) }})
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body p-0">
                <div class="tab-content" id="incomeTabsContent">
                    <!-- Paid Admin Invoices Tab -->
                    <div class="tab-pane fade show active" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
                        <div class="table-responsive">
                            <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                                <thead class="thead-light">
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
                                            @if($invoice->pdf)
                                              <a href="{{asset('storage/invoice') . '/' . $invoice->pdf}}"><span class="font-weight-bold">{{ $invoice->invoice_id }}</span></a>  
                                            @else 
                                                <span class="font-weight-bold text-dark">{{ $invoice->invoice_id }}</span>
                                            @endif
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
                            <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                                <thead class="thead-light">
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
                            <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                                <thead class="thead-light">
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

                    <!-- Wallet Recharges Tab -->
                    <div class="tab-pane fade" id="wallet" role="tabpanel" aria-labelledby="wallet-tab">
                        <div class="table-responsive">
                            <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 60px;">{{ translate('sl') }}</th>
                                        <th>{{ translate('Store Name') }}</th>
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
                                                    <a href="{{ route('admin.store.view', $recharge->from_id) }}" class="font-weight-bold">
                                                        {{ $recharge->store->name }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">{{ translate('N/A') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ \App\CentralLogics\Helpers::date_format($recharge->created_at) }}</td>
                                            <td><span class="text-muted">{{ $recharge->reason }}</span></td>
                                            <td class="text-right">
                                                <span class="font-weight-bold text-dark">{{ \App\CentralLogics\Helpers::format_currency($recharge->amount) }}</span>
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
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    @include('admin-views.js.date_range')
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
            var leadData = trendData.map(item => item.lead);
            var domainData = trendData.map(item => item.domain);
            var walletData = trendData.map(item => item.wallet);

            // Create Premium Gradients
            var subGradient = ctxTrend.createLinearGradient(0, 0, 0, 350);
            subGradient.addColorStop(0, '#ffb366');
            subGradient.addColorStop(1, '#ff9f40');

            var leadGradient = ctxTrend.createLinearGradient(0, 0, 0, 350);
            leadGradient.addColorStop(0, '#ffe080');
            leadGradient.addColorStop(1, '#ffcd56');

            var domGradient = ctxTrend.createLinearGradient(0, 0, 0, 350);
            domGradient.addColorStop(0, '#a4b3c6');
            domGradient.addColorStop(1, '#8a94a6');

            var walletGradient = ctxTrend.createLinearGradient(0, 0, 0, 350);
            walletGradient.addColorStop(0, '#22d3ee');
            walletGradient.addColorStop(1, '#06b6d4');

            var trendChart = new Chart(ctxTrend, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '{{ translate('Subscriptions') }}',
                            data: subscriptionData,
                            backgroundColor: subGradient,
                            hoverBackgroundColor: '#ff8b1a',
                            borderColor: '#ff9f40',
                            hoverBorderColor: '#ff8b1a',
                            borderWidth: 1
                        },
                        {
                            label: '{{ translate('Lead Plans') }}',
                            data: leadData,
                            backgroundColor: leadGradient,
                            hoverBackgroundColor: '#ffc129',
                            borderColor: '#ffcd56',
                            hoverBorderColor: '#ffc129',
                            borderWidth: 1
                        },
                        {
                            label: '{{ translate('Custom Domains') }}',
                            data: domainData,
                            backgroundColor: domGradient,
                            hoverBackgroundColor: '#748094',
                            borderColor: '#8a94a6',
                            hoverBorderColor: '#748094',
                            borderWidth: 1
                        },
                        {
                            label: '{{ translate('Wallet Recharges') }}',
                            data: walletData,
                            backgroundColor: walletGradient,
                            hoverBackgroundColor: '#0891b2',
                            borderColor: '#06b6d4',
                            hoverBorderColor: '#0891b2',
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
                                fontColor: '#8a94a6',
                                fontSize: 11,
                                padding: 8
                            }
                        }],
                        yAxes: [{
                            stacked: true,
                            gridLines: {
                                color: '#e7eaf3',
                                drawBorder: false,
                                zeroLineColor: '#e7eaf3',
                                borderDash: [5, 5]
                            },
                            ticks: {
                                beginAtZero: true,
                                fontColor: '#8a94a6',
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
                        backgroundColor: '#1e2022',
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
                        '{{ translate('Subscriptions') }}',
                        '{{ translate('Lead Plans') }}',
                        '{{ translate('Custom Domains') }}',
                        '{{ translate('Wallet Recharges') }}'
                    ],
                    datasets: [{
                        data: [
                            {{ $subscription_income }},
                            {{ $lead_sub_income }},
                            {{ $domain_income }},
                            {{ $wallet_recharge_income }}
                        ],
                        backgroundColor: ['#ff9f40', '#ffcd56', '#8a94a6', '#06b6d4'],
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
                        backgroundColor: '#1e2022',
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
