@extends('layouts.admin.app')

@section('title', $store->name)

@push('css_or_js')
    <!-- Custom styles for this page -->
    <link href="{{ asset('public/assets/admin/css/croppie.css') }}" rel="stylesheet">
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">

        @include('admin-views.vendor.view.partials._header', ['store' => $store])

        <!-- Page Heading -->
        @if ($store->vendor->status && Config::get('module.current_module_id') == 5)
            <div class="row g-3 text-capitalize">
                <!-- Earnings (Monthly) Card Example -->
                <div class="col-md-4">
                    <div class="card h-100 card--bg-1">
                        <div class="card-body text-center d-flex flex-column justify-content-center align-items-center">
                            <h5 class="cash--subtitle text-white">
                                {{ translate('messages.collected_cash_by_store') }}
                            </h5>
                            <div class="d-flex align-items-center justify-content-center mt-3">
                                <div class="cash-icon mr-3">
                                    <img src="{{ asset('public/assets/admin/img/cash.png') }}" alt="img">
                                </div>
                                <h2 class="cash--title text-white">
                                    {{ \App\CentralLogics\Helpers::format_currency($wallet->collected_cash) }}</h2>
                            </div>
                        </div>
                        <div class="card-footer pt-0 bg-transparent border-0">
                            <button class="btn text-white text-capitalize bg--title h--45px w-100" id="collect_cash"
                                type="button" data-toggle="modal" data-target="#collect-cash"
                                title="Collect Cash">{{ translate('messages.collect_cash_from_store') }}
                            </button>
                            {{-- <a class="btn text-white text-capitalize bg--title h--45px w-100" href="{{$store->vendor->status ? route('admin.transactions.account-transaction.index') : '#'}}" title="{{translate('messages.goto_account_transaction')}}">{{translate('messages.collect_cash_from_store')}}</a> --}}
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="row g-3">
                        <!-- Panding Withdraw Card Example -->
                        <div class="col-sm-6">
                            <div class="resturant-card card--bg-2">
                                <h4 class="title">
                                    {{ \App\CentralLogics\Helpers::format_currency($wallet->pending_withdraw) }}</h4>
                                <div class="subtitle">{{ translate('messages.pending_withdraw') }}</div>
                                <img class="resturant-icon w--30"
                                    src="{{ asset('public/assets/admin/img/transactions/pending.png') }}" alt="transaction">
                            </div>
                        </div>

                        <!-- Earnings (Monthly) Card Example -->
                        <div class="col-sm-6">
                            <div class="resturant-card card--bg-3">
                                <h4 class="title">
                                    {{ \App\CentralLogics\Helpers::format_currency($wallet->total_withdrawn) }}</h4>
                                <div class="subtitle">{{ translate('messages.total_withdrawal_amount') }}</div>
                                <img class="resturant-icon w--30"
                                    src="{{ asset('public/assets/admin/img/transactions/withdraw-amount.png') }}"
                                    alt="transaction">
                            </div>
                        </div>

                        <!-- Collected Cash Card Example -->
                        <div class="col-sm-6">
                            <div class="resturant-card card--bg-4">
                                <h4 class="title">
                                    {{ \App\CentralLogics\Helpers::format_currency($wallet->balance > 0 ? $wallet->balance : 0) }}
                                </h4>
                                <div class="subtitle">{{ translate('messages.withdraw_able_balance') }}</div>
                                <img class="resturant-icon w--30"
                                    src="{{ asset('public/assets/admin/img/transactions/withdraw-balance.png') }}"
                                    alt="transaction">
                            </div>
                        </div>

                        <!-- Pending Requests Card Example -->
                        <div class="col-sm-6">
                            <div class="resturant-card card--bg-1">
                                <h4 class="title">
                                    {{ \App\CentralLogics\Helpers::format_currency($wallet->total_earning) }}</h4>
                                <div class="subtitle">{{ translate('messages.total_earning') }}</div>
                                <img class="resturant-icon w--30"
                                    src="{{ asset('public/assets/admin/img/transactions/earning.png') }}"
                                    alt="transaction">
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        @endif
        {{-- Business Intelligence Section --}}
        <div class="card mt-4">
            <div class="card-header d-flex align-items-center">
                <img src="{{ asset('public/assets/admin/img/icons/bi-icon.png') }}" alt="" width="22"
                    height="22" class="mr-2" onerror="this.style.display='none'">
                <h5 class="card-title m-0"><strong>Business Intelligence</strong></h5>
                <div class="ml-auto">
                    <form method="GET" action="{{ url()->current() }}" class="bi-date-range-form d-inline">
                        <input type="hidden" name="bi_date_range" id="biDateRangeInput" value="{{ $biPreset }}">
                        <input type="hidden" name="bi_custom_date_range" id="biCustomDateRangeInput" value="{{ request('bi_custom_date_range') }}">
                        @php
                            $biLabels = ['today'=>'Today','yesterday'=>'Yesterday','this_week'=>'This Week','last_week'=>'Last Week','this_month'=>'This Month','last_month'=>'Last Month','last_3_month'=>'Last 3 Months','last_30_days'=>'Last 30 Days','this_year'=>'This Year','last_year'=>'Last Year','quarter'=>'Quarter','custom'=>'Custom Range'];
                        @endphp
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                            data-toggle="modal" data-target="#biDateRangeModal" style="font-size:12px;">
                            <i class="tio-calendar-month"></i>
                            {{ $biLabels[$biPreset] ?? ucwords(str_replace('_', ' ', $biPreset)) }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- BI Date Range Modal --}}
            <div class="modal fade" id="biDateRangeModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Select Date Range</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="section-title"><i class="fas fa-clock"></i> Quick</div>
                            <div class="preset-grid">
                                @foreach(['today'=>'Today','yesterday'=>'Yesterday','this_week'=>'This Week','last_week'=>'Last Week','this_month'=>'This Month','last_month'=>'Last Month','last_3_month'=>'Last 3 Months'] as $val=>$lbl)
                                    <label class="checkbox-item">
                                        <input type="radio" name="bi_preset_pick" value="{{ $val }}"
                                            {{ $biPreset === $val ? 'checked' : '' }}
                                            onchange="applyBiPreset('{{ $val }}')">
                                        <div class="checkbox-label">{{ $lbl }}</div>
                                    </label>
                                @endforeach
                            </div>
                            <div class="section-title"><i class="fas fa-chart-line"></i> Extended</div>
                            <div class="preset-grid">
                                @foreach(['last_30_days'=>'Last 30 Days','this_year'=>'This Year','last_year'=>'Last Year','quarter'=>'Quarter'] as $val=>$lbl)
                                    <label class="checkbox-item">
                                        <input type="radio" name="bi_preset_pick" value="{{ $val }}"
                                            {{ $biPreset === $val ? 'checked' : '' }}
                                            onchange="applyBiPreset('{{ $val }}')">
                                        <div class="checkbox-label">{{ $lbl }}</div>
                                    </label>
                                @endforeach
                            </div>
                            <label class="custom-section">
                                <input type="radio" name="bi_preset_pick" value="custom"
                                    {{ $biPreset === 'custom' ? 'checked' : '' }}>
                                <div class="custom-content">
                                    <h6><i class="fas fa-calendar-plus me-1"></i>Custom Range</h6>
                                    <input type="text" id="biDaterangePicker" class="form-control"
                                        value="{{ request('bi_custom_date_range') }}" />
                                </div>
                            </label>
                            <div class="action-buttons">
                                <div class="btn-group">
                                    <button type="button" class="close" data-dismiss="modal">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @php
                $biLeadsUrl      = route('admin.service.lead-list', array_filter(['store_id' => $store->id, 'date_range' => $biPreset, 'custom_date_range' => request('bi_custom_date_range')]));
                $biThisMonthUrl  = route('admin.service.lead-list', ['store_id' => $store->id, 'date_range' => 'this_month']);
                $biConversionUrl = route('admin.service.lead-list', array_filter(['store_id' => $store->id, 'type' => 'Completed', 'date_range' => $biPreset, 'custom_date_range' => request('bi_custom_date_range')]));
                $biMonetizationUrl = route('admin.store.view', [$store->id, 'monetization']);
            @endphp
            <div class="card-body">
                {{-- Stats Row --}}
                <div class="row g-2 mb-4">
                    {{-- Total Leads --}}
                    <div class="col">
                        <a href="{{ $biLeadsUrl }}" class="text-decoration-none" style="color:inherit;">
                        <div class="d-flex align-items-center rounded bi-stat-card" style="color:black;min-height:56px;background:#f0f2f3;">
                            <div class="h-100 p-2 rounded-left" style="background:#4e73df;color:#fff;display:flex;align-items:center;justify-content:center">
                                <div style="width:32px;height:32px;display:flex;align-items:center;justify-content:center">
                                    <i class="tio-group-senior" style="font-size:18px"></i>
                                </div>
                            </div>
                            <div class="pl-2">
                                <div style="font-size:18px;font-weight:700;line-height:1.1">{{ $totalLeads }}</div>
                                <div style="font-size:11px;opacity:.85">Total Leads</div>
                            </div>
                        </div>
                        </a>
                    </div>
                    {{-- Leads This Month --}}
                    <div class="col">
                        <a href="{{ $biThisMonthUrl }}" class="text-decoration-none" style="color:inherit;">
                        <div class="d-flex align-items-center rounded bi-stat-card" style="color:black;min-height:56px;background:#f0f2f3;">
                            <div class="h-100 p-2 rounded-left" style="background:#1cc88a;color:#fff;display:flex;align-items:center;justify-content:center">
                                <div style="width:32px;height:32px;display:flex;align-items:center;justify-content:center">
                                    <i class="tio-user-add" style="font-size:18px"></i>
                                </div>
                            </div>
                            <div class="pl-2">
                                <div style="font-size:18px;font-weight:700;line-height:1.1">{{ $leadsThisMonth }}</div>
                                <div style="font-size:11px;opacity:.85">Leads this month</div>
                            </div>
                        </div>
                        </a>
                    </div>
                    {{-- Conversion --}}
                    <div class="col">
                        <a href="{{ $biConversionUrl }}" class="text-decoration-none" style="color:inherit;">
                        <div class="d-flex align-items-center rounded bi-stat-card" style="color:black;min-height:56px;background:#f0f2f3;">
                            <div class="h-100 p-2 rounded-left" style="background:#36b9cc;color:#fff;display:flex;align-items:center;justify-content:center">
                                <div style="width:32px;height:32px;display:flex;align-items:center;justify-content:center">
                                    <i class="tio-trending-up" style="font-size:18px"></i>
                                </div>
                            </div>
                            <div class="pl-2">
                                <div style="font-size:18px;font-weight:700;line-height:1.1">{{ $conversionRate }}%</div>
                                <div style="font-size:11px;opacity:.85">Conversion</div>
                            </div>
                        </div>
                        </a>
                    </div>
                    {{-- Wallet Balance --}}
                    <div class="col">
                        <a href="{{ $biMonetizationUrl }}" class="text-decoration-none" style="color:inherit;">
                        <div class="d-flex align-items-center rounded bi-stat-card" style="color:black;min-height:56px;background:#f0f2f3;">
                            <div class="h-100 p-2 rounded-left" style="background:#e74a3b;color:#fff;display:flex;align-items:center;justify-content:center">
                                <div style="width:32px;height:32px;display:flex;align-items:center;justify-content:center">
                                    <i class="tio-wallet" style="font-size:18px"></i>
                                </div>
                            </div>
                            <div class="pl-2">
                                <div style="font-size:18px;font-weight:700;line-height:1.1">
                                    {{ \App\CentralLogics\Helpers::format_currency($walletBalance) }}</div>
                                <div style="font-size:11px;opacity:.85;color:{{ $walletBalance < 200 ? '#e74a3b' : 'inherit' }}">
                                    {{ $walletBalance < 200 ? 'Recharge Now' : 'Wallet Balance' }}
                                </div>
                            </div>
                        </div>
                        </a>
                    </div>
                    {{-- Last Login --}}
                    <div class="col">
                        <a href="{{ route('admin.store.view', $store->id) }}" class="text-decoration-none" style="color:inherit;">
                        <div class="d-flex align-items-center rounded bi-stat-card" style="color:black;min-height:56px;background:#f0f2f3;">
                            <div class="h-100 p-2 rounded-left" style="background:#5a5c69;color:#fff;display:flex;align-items:center;justify-content:center">
                                <div style="width:32px;height:32px;display:flex;align-items:center;justify-content:center">
                                    <i class="tio-time" style="font-size:18px"></i>
                                </div>
                            </div>
                            <div class="pl-2">
                                <div style="font-size:14px;font-weight:700;line-height:1.1">
                                    @if ($lastLoginDays === null) Never
                                    @elseif ($lastLoginDays === 0) Today
                                    @else {{ $lastLoginDays }} days ago
                                    @endif
                                </div>
                                <div style="font-size:11px;opacity:.85">Last Login</div>
                            </div>
                        </div>
                        </a>
                    </div>
                    {{-- Subscription --}}
                    <div class="col">
                        <a href="{{ $biMonetizationUrl }}" class="text-decoration-none" style="color:inherit;">
                        <div class="d-flex align-items-center rounded bi-stat-card" style="color:black;min-height:56px;background:#f0f2f3;">
                            <div class="h-100 p-2 rounded-left" style="background:{{ $subscriptionExpired ? '#e74a3b' : '#1cc88a' }};color:#fff;display:flex;align-items:center;justify-content:center">
                                <div style="width:32px;height:32px;display:flex;align-items:center;justify-content:center">
                                    <i class="tio-refresh" style="font-size:18px"></i>
                                </div>
                            </div>
                            <div class="pl-2">
                                <div style="font-size:14px;font-weight:700;line-height:1.1">
                                    Subscription<br>
                                    <span style="font-size:12px;color:{{ $subscriptionExpired ? '#e74a3b' : '#1cc88a' }}">{{ $subscriptionExpired ? 'Expired' : 'Active' }}</span>
                                </div>
                            </div>
                        </div>
                        </a>
                    </div>
                </div> 

                {{-- AI Insight + Lead Trend + Reactivation Score --}}
                <div class="row g-3">
                    {{-- AI Insight --}}
                    <div class="col-lg-4">
                        <div class="border rounded p-3 h-100" style="background:#fff5f5">
                            <div class="d-flex align-items-center mb-2">
                                <span
                                    style="background:#e74a3b;color:#fff;border-radius:50%;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;font-size:14px;margin-right:8px">
                                    <i class="tio-comment-text"></i>
                                </span>
                                <strong>AI Insight</strong>
                            </div>
                            <p class="mb-3" style="font-size:14px;color:#555">{{ $biInsightText }}</p>
                            <a href="javascript:;" class="btn btn-outline-danger btn-sm btn-block"
                                id="generateActivationPlan" data-store-id="{{ $store->id }}">
                                Generate Activation Plan
                            </a>
                        </div>
                    </div>
                    {{-- Lead Trend Chart --}}
                    <div class="col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="m-0"><strong>Lead Trend</strong></h6>
                                <div class="d-flex align-items-center" style="gap:12px;font-size:11px">
                                    <span>
                                        <i class="tio-trending-{{ $conversionChange >= 0 ? 'up' : 'down' }}"
                                            style="color:{{ $conversionChange >= 0 ? '#1cc88a' : '#e74a3b' }};font-size:14px"></i>
                                        <strong
                                            style="color:{{ $conversionChange >= 0 ? '#1cc88a' : '#e74a3b' }}">{{ $conversionChange >= 0 ? '+' : '' }}{{ $conversionChange }}%</strong>
                                        <span style="color:#888">Conversion</span>
                                    </span>
                                    <span style="color:#ccc">|</span>
                                    <span>
                                        <span style="color:#888">Revenue</span>
                                        <strong>{{ \App\CentralLogics\Helpers::format_currency($revenueGenerated) }}</strong>
                                    </span>
                                </div>
                            </div>
                            <div style="height:100px">
                                <canvas id="leadTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                    {{-- Reactivation Score --}}
                    <div class="col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-3"><strong>Reactivation Score</strong></h6>
                            <div class="d-flex flex-column align-items-center">
                                {{-- Gauge --}}
                                <div style="position:relative;width:120px;height:72px;overflow:hidden">
                                    <svg width="120" height="72" viewBox="0 0 120 72">
                                        <defs>
                                            <linearGradient id="reactivationGaugeGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" style="stop-color:#e74a3b"/>
                                                <stop offset="30%" style="stop-color:#f6913e"/>
                                                <stop offset="55%" style="stop-color:#f6c23e"/>
                                                <stop offset="80%" style="stop-color:#6dd47e"/>
                                                <stop offset="100%" style="stop-color:#1cc88a"/>
                                            </linearGradient>
                                        </defs>
                                        <path d="M12,65 A48,48 0 0,1 108,65" fill="none" stroke="url(#reactivationGaugeGrad)" stroke-width="10" stroke-linecap="round"/>
                                        @php
                                            $rNeedleAngle = 180 - ($reactivationScore / 100 * 180);
                                            $rNeedleRad = deg2rad($rNeedleAngle);
                                            $rcx = 60; $rcy = 65; $rNeedleLen = 36;
                                            $rnx = $rcx + cos($rNeedleRad) * $rNeedleLen;
                                            $rny = $rcy - sin($rNeedleRad) * $rNeedleLen;
                                        @endphp
                                        <line x1="{{ $rcx }}" y1="{{ $rcy }}" x2="{{ round($rnx, 1) }}" y2="{{ round($rny, 1) }}"
                                            stroke="#333" stroke-width="2" stroke-linecap="round"/>
                                        <circle cx="{{ $rcx }}" cy="{{ $rcy }}" r="4" fill="#333"/>
                                    </svg>
                                    <div style="position:absolute;bottom:0;left:0;right:0;text-align:center;line-height:1">
                                        <span style="font-size:24px;font-weight:700;color:#333">{{ $reactivationScore }}</span><br>
                                        <span style="font-size:11px;color:#888;font-weight:600">{{ $reactivationLabel }}</span>
                                    </div>
                                </div>
                                {{-- Issues --}}
                                <ul class="list-unstyled mb-0 mt-2 w-100" style="font-size:12px">
                                    @foreach($reactivationIssues as $issue)
                                        <li class="mb-1" style="color:#555"> 
                                            <span style="color:#e74a3b;margin-right:4px">&#8226;</span>
                                            {{ $issue }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title m-0 d-flex align-items-center">
                    <span class="card-header-icon mr-2">
                        <i class="tio-shop-outlined"></i>
                    </span>
                    <span class="ml-1">{{ Config::get('module.vendor_role') }} {{ translate('messages.info') }}</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-6">
                        <div class="resturant--info-address">
                            <div class="logo">
                                <img class="onerror-image"
                                    data-onerror-image="{{ asset('public/assets/admin/img/100x100/1.png') }}"
                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                        $store->logo ?? '',
                                        asset('storage/app/public/store') . '/' . $store->logo ?? '',
                                        asset('public/assets/admin/img/100x100/1.png'),
                                        'store/',
                                    ) }}"
                                    alt="{{ $store->name }} Logo">
                            </div>
                            <ul class="address-info list-unstyled list-unstyled-py-3 text-dark">
                                <li>
                                    <h5 class="name">{{ $store->name }}</h5>
                                </li>
                                <li>

                                    <i class="tio-city nav-icon"></i>
                                    <span>{{ translate('messages.address') }}</span> <span>:</span> &nbsp; <span>

                                        <a href="https://www.google.com/maps/search/?api=1&query={{ data_get($store, 'latitude', 0) }},{{ data_get($store, 'longitude', 0) }}"
                                            target="_blank">{{ $store->address }}</a></span>

                                </li>

                                <li>
                                    <i class="tio-email nav-icon"></i>
                                    <span>{{ translate('messages.email') }}</span> <span>:</span> &nbsp; <a
                                        href="mailto:{{ $store->email }}"><span>{{ $store->email }}</span></a>
                                </li>
                                <li>
                                    <i class="tio-call-talking nav-icon"></i>
                                    <span>{{ translate('messages.phone') }}</span> <span>:</span> &nbsp;

                                    <a href="javascript:;" style="cursor:default;"
                                        class="textToCopy">{{ $store->phone }}</a>
                                    <button class="copy-btn bg-transparent outline-none border-0">
                                        <i class="tio-copy"></i>
                                    </button>
                                </li>
                                <li>
                                    <i class="tio-google nav-icon"></i>
                                    <span>{{ translate('messages.Google Business Link') }}</span> <span>:</span> &nbsp;

                                    <a href="{{ $store->google_verification }}"
                                        style="">{{ $store->google_verification }}</a>
                                </li>
                                @if ($store->gst_doc)
                                    <li>
                                        <i class="tio-map nav-icon"></i>
                                        <span>GST</span> <span>:</span> &nbsp; <span><a target="_blank"
                                                href="{{ asset('storage/app/public/store/docs') . '/' . $store->gst_doc }}">View
                                                GST</a></span>
                                    </li>
                                @endif
                                <li>
                                    <i class="tio-map nav-icon"></i>
                                    <span>{{ translate('messages.Zone') }}</span> <span>:</span> &nbsp;
                                    <span>{{ $store?->zone?->name ?? translate('zone_deleted') }}</span>
                                </li>
                                <li>
                                    <i class="tio-money nav-icon"></i>
                                    <span>Vendor Type</span> <span>:</span> &nbsp;
                                    <span>{{ $store->vendor_type ?? translate('not_selected') }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div id="map" class="single-page-map"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row pt-3 g-3">
 
            {{-- Profile Completion Card --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title m-0 d-flex align-items-center">
                            <span class="card-header-icon mr-2">
                                <i class="tio-trending-up"></i>
                            </span>
                            <span class="ml-1">Profile Completion</span>
                        </h5>
                    </div>
                    <div class="card-body d-flex align-items-center" style="gap:16px">
                        {{-- Donut ring --}}
                        <div style="position:relative;width:72px;height:72px;flex-shrink:0">
                            <svg width="72" height="72" viewBox="0 0 72 72">
                                <circle cx="36" cy="36" r="28" fill="none" stroke="#f0f0f5" stroke-width="6"/>
                                <circle cx="36" cy="36" r="28" fill="none"
                                    stroke="{{ $completionRing }}" stroke-width="6" stroke-linecap="round"
                                    stroke-dasharray="{{ $completionCircumf }}"
                                    stroke-dashoffset="{{ $completionOffset }}" transform="rotate(-90 36 36)"/>
                            </svg>
                            <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;line-height:1.1">
                                <span style="font-size:15px;font-weight:700;color:#333">{{ $completionPercent }}%</span>
                                <span style="font-size:9px;color:#aaa">{{ $completionDone }}/{{ $completionTotal }}</span>
                            </div>
                        </div>
                        {{-- Vertical todo list --}}
                        <div style="flex:1">
                            <ul class="list-unstyled mb-0" style="display:flex;flex-direction:column;gap:5px">
                                @foreach ($completionItems as $t)
                                    <li style="display:flex;align-items:center;gap:7px">
                                        <span style="width:15px;height:15px;border-radius:4px;border:1.5px solid {{ $t['done'] ? $completionRing : '#d0d0dc' }};
                                             background:{{ $t['done'] ? $completionRing : 'transparent' }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                            @if ($t['done'])
                                                <svg width="9" height="9" viewBox="0 0 9 9" fill="none">
                                                    <path d="M1.5 4.5L3.5 6.5L7.5 2.5" stroke="#fff" stroke-width="1.5"
                                                        stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            @endif
                                        </span>
                                        <span style="font-size:13px;color:{{ $t['done'] ? '#888' : '#333' }};{{ $t['done'] ? 'text-decoration:line-through' : '' }}">
                                            {{ $t['icon'] }} {{ $t['label'] }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title m-0 d-flex align-items-center">
                            <span class="card-header-icon mr-2">
                                <i class="tio-user"></i>
                            </span>
                            <span class="ml-1">{{ translate('messages.owner_info') }}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="resturant--info-address">
                            <div class="avatar avatar-xxl avatar-circle avatar-border-lg">
                                <img class="avatar-img onerror-image"
                                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                        $store->vendor->image ?? '',
                                        asset('storage/app/public/vendor') . '/' . $store->vendor->image ?? '',
                                        asset('public/assets/admin/img/160x160/img1.jpg'),
                                        'vendor/',
                                    ) }}"
                                    alt="Image Description">
                            </div>
                            <ul class="address-info address-info-2 list-unstyled list-unstyled-py-3 text-dark">
                                <li>
                                    <h5 class="name">{{ $store->vendor->f_name }} {{ $store->vendor->l_name }}</h5>
                                </li>
                                <li>
                                    <i class="tio-call-talking nav-icon"></i>
                                    <span class="pl-1">

                                        <a href="javascript:;" style="cursor:default;"
                                            class="textToCopy">{{ $store->vendor->phone }}</a>
                                        <button class="copy-btn bg-transparent outline-none border-0">
                                            <i class="tio-copy"></i>
                                        </button>

                                    </span>
                                </li>
                                <li>
                                    <i class="tio-email nav-icon"></i>
                                    <span class="pl-1"><a
                                            href="mailto:{{ $store->vendor->email }}">{{ $store->vendor->email }}</a>
                                    </span>
                                </li>
                                @if ($store->gst_doc)
                                    <li>
                                        <b>GST Number : {{ $store->gst_number }} </b>
                                        <a target="_blank"
                                            href="{{ asset('storage/app/public/store/docs/') . '/' . $store->gst_doc }}"
                                            class="btn btn-sm btn-outline-primary">View GST Document</a>
                                    @elseif($store->id_doc)
                                    <li>
                                        <b>ID Number :</b>
                                        <span class="pl-1">{{ $store->id_number }}</span>
                                    </li>
                                    <a target="_blank"
                                        href="{{ asset('storage/app/public/store/docs/') . '/' . $store->id_doc }}"
                                        class="btn btn-sm btn-outline-primary">View ID Document</a>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title m-0 d-flex align-items-center">
                            <span class="card-header-icon mr-2">
                                <i class="tio-user"></i>
                            </span>
                            <span class="ml-1">Categories</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="resturant--info-address">
                            @foreach ($categories as $cat)
                                <span class="badge rounded-pill bg-transparent text-primary border border-secondary m-1"
                                    style="font-size: 15px;">{{ $cat }}</span>
                            @endforeach
                            @if (!count($categories))
                                No categories found...
                            @endif
                        </div>
                        @if (count($services))
                            <hr class="my-2">
                            <p class="text-muted mb-1" style="font-size:12px;font-weight:600;letter-spacing:.4px;">SERVICES</p>
                            <div>
                                @foreach ($services as $svc)
                                    <span class="badge rounded-pill bg-transparent text-success border border-success m-1"
                                        style="font-size:14px;">{{ $svc }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>


        </div>
    </div>



    <div class="modal fade" id="collect-cash" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('messages.collect_cash_from_store') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('admin.transactions.account-transaction.store') }}" method='post'
                        id="add_transaction">
                        @csrf
                        <input type="hidden" name="type" value="store">
                        <input type="hidden" name="store_id" value="{{ $store->id }}">
                        <div class="form-group">
                            <label class="input-label">{{ translate('messages.payment_method') }} <span
                                    class="input-label-secondary text-danger">*</span></label>
                            <input class="form-control" type="text" name="method" id="method" required
                                maxlength="191" placeholder="{{ translate('messages.Ex_:_Card') }}">
                        </div>
                        <div class="form-group">
                            <label class="input-label">{{ translate('messages.reference') }}</label>
                            <input class="form-control" type="text" name="ref" id="ref" maxlength="191">
                        </div>
                        <div class="form-group">
                            <label class="input-label">{{ translate('messages.amount') }} <span
                                    class="input-label-secondary text-danger">*</span></label>
                            <input class="form-control" type="number" min=".01" step="0.01" name="amount"
                                id="amount" max="999999999999.99"
                                placeholder="{{ translate('messages.Ex_:_1000') }}">
                        </div>
                        <div class="btn--container justify-content-end">
                            <button type="submit" id="submit_new_customer"
                                class="btn btn--primary">{{ translate('submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <!-- Page level plugins -->
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&callback=initMap&v=3.45.8">
    </script>
    <script>
        "use strict";
        // Call the dataTables jQuery plugin
        $(document).ready(function() {
            $('#dataTable').DataTable();
        });

        const myLatLng = {
            lat: {{ $store->latitude }},
            lng: {{ $store->longitude }}
        };
        let map;
        initMap();

        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 15,
                center: myLatLng,
            });
            new google.maps.Marker({
                position: myLatLng,
                map,
                title: "{{ $store->name }}",
            });
        }

        $(document).on('ready', function() {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            let datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

            $('#column1_search').on('keyup', function() {
                datatable
                    .columns(1)
                    .search(this.value)
                    .draw();
            });

            $('#column2_search').on('keyup', function() {
                datatable
                    .columns(2)
                    .search(this.value)
                    .draw();
            });

            $('#column3_search').on('change', function() {
                datatable
                    .columns(3)
                    .search(this.value)
                    .draw();
            });

            $('#column4_search').on('keyup', function() {
                datatable
                    .columns(4)
                    .search(this.value)
                    .draw();
            });


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function() {
                let select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });

        function request_alert(url, message) {
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = url;
                }
            })
        }

        $('#add_transaction').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('admin.transactions.account-transaction.store') }}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(data) {
                    if (data.errors) {
                        for (let i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    } else {
                        toastr.success('{{ translate('messages.transaction_saved') }}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        setTimeout(function() {
                            location.href = '{{ route('admin.store.view', $store->id) }}';
                        }, 2000);
                    }
                }
            });
        });
        $(document).ready(function() {
            $(".copy-btn").on("click", function() {
                // Get the previous <p> or span element text
                var text = $(this).prev(".textToCopy").text().trim();
                console.log(text); // Debugging

                if (navigator.clipboard && window.isSecureContext) {
                    // Modern way to copy
                    navigator.clipboard.writeText(text).then(() => {
                        console.log("Copied successfully!");
                    }).catch(err => {
                        console.error("Clipboard copy failed", err);
                    });
                } else {
                    // Fallback for older browsers
                    var tempInput = $("<textarea>"); // Use textarea instead of input
                    $("body").append(tempInput);
                    tempInput.val(text).css({
                        position: "absolute",
                        left: "-9999px", // Hide offscreen
                    }).select();
                    document.execCommand("copy");
                    tempInput.remove();
                }
                $(this).html("Copied!");
                setTimeout(() => $(this).html('<i class="tio-copy"></i>'), 1000);
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        // Lead Trend Chart
        (function() {
            const ctx = document.getElementById('leadTrendChart');
            if (!ctx) return;
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! $leadTrendLabels !!},
                    datasets: [{
                        label: 'Leads',
                        data: {!! $leadTrendData !!},
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78,115,223,0.1)',
                        borderWidth: 2,
                        pointBackgroundColor: '#4e73df',
                        pointRadius: 4,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                precision: 0
                            }
                        }
                    }
                }
            });
        })();

        // Generate Activation Plan
        $('#generateActivationPlan').on('click', function() {
            let btn = $(this);
            let storeId = btn.data('store-id');
            btn.html('<i class="tio-refresh spin"></i> Generating...').prop('disabled', true);

            $.ajax({
                url: '{{ route("admin.store.activation-plan", ":id") }}'.replace(':id', storeId),
                type: 'GET',
                success: function(res) { 
                    let html = '';
                    res.steps.forEach(function(step) {
                        let color = step.priority === 'High' ? '#e74a3b' : (step.priority === 'Medium' ? '#f6c23e' : '#1cc88a');
                        let bg = step.priority === 'High' ? '#fdf0ef' : (step.priority === 'Medium' ? '#fef9ed' : '#edfaf4');
                        html += `<div style="padding:10px 12px;margin-bottom:8px;border-radius:6px;background:${bg};border-left:4px solid ${color}">
                            <span style="font-size:11px;font-weight:700;color:${color};text-transform:uppercase">${step.priority} Priority</span>
                            <div style="font-size:13px;color:#333;margin-top:2px">${step.action}</div>
                        </div>`;
                    });
                    $('#activationPlanBody').html(html);
                    $('#activationPlanModal').modal('show');
                },
                error: function() {
                    toastr.error('Failed to generate activation plan.');
                },
                complete: function() {
                    btn.html('Generate Activation Plan').prop('disabled', false);
                }
            });
        });
    </script>

    {{-- Activation Plan Modal --}}
    <div class="modal fade" id="activationPlanModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center">
                        <span style="background:#e74a3b;color:#fff;border-radius:50%;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;font-size:14px;margin-right:8px">
                            <i class="tio-flash"></i>
                        </span>
                        Activation Plan
                    </h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" id="activationPlanBody" style="max-height:400px;overflow-y:auto">
                </div>
            </div>
        </div>
    </div>

    {{-- BI Date Range Filter JS --}}
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        function applyBiPreset(val) {
            document.getElementById('biDateRangeInput').value = val;
            document.getElementById('biCustomDateRangeInput').value = '';
            document.querySelector('.bi-date-range-form').submit();
        }

        $(function () {
            $('#biDaterangePicker').daterangepicker({
                opens: 'left',
                locale: { format: 'YYYY-MM-DD' }
            }, function (start, end) {
                document.getElementById('biDateRangeInput').value = 'custom';
                document.getElementById('biCustomDateRangeInput').value = start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD');
                $('input[name="bi_preset_pick"][value="custom"]').prop('checked', true);
                document.querySelector('.bi-date-range-form').submit();
            });
        });
    </script>
@endpush
