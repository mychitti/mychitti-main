@extends('layouts.admin.app')

@section('title', 'Marketing Dashboard')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <style>
        .stat-card { border-left: 4px solid; }
        .stat-card.visits  { border-color: #377dff; }
        .stat-card.leads   { border-color: #00c9a7; }
        .stat-card.banners { border-color: #f5ca99; }
        .stat-card.loc     { border-color: #de4437; }
        .stat-card.ads     { border-color: #9b59b6; }
        .stat-icon { width: 48px; height: 48px; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-size:20px; }
        .rank-badge { display:inline-block; width:24px; height:24px; border-radius:50%; background:#eee; text-align:center; line-height:24px; font-size:12px; font-weight:700; }
        .rank-1 { background:#FFD700; color:#333; }
        .rank-2 { background:#C0C0C0; color:#333; }
        .rank-3 { background:#CD7F32; color:#fff; }
        .progress-sm { height: 6px; border-radius: 3px; }
        .section-title { font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; color: #8c98a4; font-weight: 600; margin-bottom: .75rem; }
        .btn-outline-secondary.active{
            background: grey !important;
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">

    {{-- Page header --}}
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <i class="tio-chart-bar-4 nav-icon text-primary" style="font-size:22px;"></i>
            </span>
            <span>Marketing Dashboard</span>
        </h1>
        <form class="d-flex align-items-center gap-2 date-range-form" method="GET">
            <button type="button" class="btn btn-sm btn-outline-warning"
                data-toggle="modal" data-target="#dateRangeModal" style="white-space:nowrap">
                {{ translate($preset) }}
            </button>
            {{-- <a href="{{ route('admin.marketing-dashboard.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a> --}}
            @include('vendor-views/form_modals/date_range')
        </form>
    </div>

    {{-- ── Summary Cards ───────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card visits h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="stat-icon bg-soft-primary"><i class="tio-eye text-primary"></i></div>
                    <div>
                        <div class="h4 mb-0">{{ number_format($totalVisits) }}</div>
                        <div class="text-muted small">Store Visits</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card leads h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="stat-icon bg-soft-success"><i class="tio-call text-success"></i></div>
                    <div>
                        <div class="h4 mb-0">{{ number_format($totalLeads) }}</div>
                        <div class="text-muted small">Leads (Calls)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card banners h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="stat-icon" style="background:#fff8ec;"><i class="tio-image" style="color:#f5ca99;"></i></div>
                    <div>
                        <div class="h4 mb-0">{{ number_format($totalBannerClicks) }}</div>
                        <div class="text-muted small">Banner Clicks</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card loc h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="stat-icon bg-soft-danger"><i class="tio-map-arrow-right text-danger"></i></div>
                    <div>
                        <div class="h4 mb-0">{{ number_format($totalLocationViews) }}</div>
                        <div class="text-muted small">Location Views</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card ads h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="stat-icon" style="background:#f5eefb;"><i class="tio-notifications" style="color:#9b59b6;"></i></div>
                    <div>
                        <div class="h4 mb-0">{{ number_format($totalAdClicks) }}</div>
                        <div class="text-muted small">Ad Clicks</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Trend Chart ─────────────────────────────────────────────────────── --}}
    <div class="card mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Activity Trend</h5>
            <div class="btn-group-sm" id="trendDays">
                <button class="btn btn-outline-secondary active" data-days="7">7D</button>
                <button class="btn btn-outline-secondary" data-days="30">30D</button>
                <button class="btn btn-outline-secondary" data-days="60">60D</button>
                <button class="btn btn-outline-secondary" data-days="90">90D</button>
            </div>
        </div>
        <div class="card-body pt-0">
            <div id="trendChart"></div>
        </div>
    </div>

    {{-- ── Row: Categories by Visits | Categories by Leads ─────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header py-3">
                    <h5 class="mb-0">Top Categories by Store Visits</h5>
                    <div class="section-title mt-1">Stores with most traffic, grouped by service category</div>
                </div>
                <div class="card-body pt-0 px-0">
                    @if($topCategoriesByVisits->isEmpty())
                        <div class="text-center text-muted py-4">No data for this period</div>
                    @else
                    @php $maxV = $topCategoriesByVisits->max('visit_count') ?: 1; @endphp
                    <table class="table table-borderless table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th width="28">#</th>
                                <th>Category</th>
                                <th class="text-right">Stores</th>
                                <th width="160">Visits</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($topCategoriesByVisits as $k => $row)
                            <tr>
                                <td><span class="rank-badge {{ $k < 3 ? 'rank-'.($k+1) : '' }}">{{ $k+1 }}</span></td>
                                <td class="font-weight-medium">{{ $row->name }}</td>
                                <td class="text-right text-muted small">{{ $row->store_count }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1 progress-sm">
                                            <div class="progress-bar bg-primary" style="width:{{ round($row->visit_count/$maxV*100) }}%"></div>
                                        </div>
                                        <span class="small font-weight-bold text-dark" style="min-width:40px;text-align:right">{{ number_format($row->visit_count) }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header py-3">
                    <h5 class="mb-0">Top Categories by Leads</h5>
                    <div class="section-title mt-1">Categories whose stores receive the most phone call enquiries</div>
                </div>
                <div class="card-body pt-0 px-0">
                    @if($topCategoriesByLeads->isEmpty())
                        <div class="text-center text-muted py-4">No data for this period</div>
                    @else
                    @php $maxL = $topCategoriesByLeads->max('lead_count') ?: 1; @endphp
                    <table class="table table-borderless table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th width="28">#</th>
                                <th>Category</th>
                                <th class="text-right">Stores</th>
                                <th width="160">Leads</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($topCategoriesByLeads as $k => $row)
                            <tr>
                                <td><span class="rank-badge {{ $k < 3 ? 'rank-'.($k+1) : '' }}">{{ $k+1 }}</span></td>
                                <td class="font-weight-medium">{{ $row->name }}</td>
                                <td class="text-right text-muted small">{{ $row->store_count }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1 progress-sm">
                                            <div class="progress-bar bg-success" style="width:{{ round($row->lead_count/$maxL*100) }}%"></div>
                                        </div>
                                        <span class="small font-weight-bold text-dark" style="min-width:40px;text-align:right">{{ number_format($row->lead_count) }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Row: Areas by Leads | Areas by Visits ───────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header py-3">
                    <h5 class="mb-0">Top Areas by Leads</h5>
                    <div class="section-title mt-1">Zones / localities generating the most phone enquiries</div>
                </div>
                <div class="card-body pt-0 px-0">
                    @if($topAreasByLeads->isEmpty())
                        <div class="text-center text-muted py-4">No data for this period</div>
                    @else
                    @php $maxAL = $topAreasByLeads->max('lead_count') ?: 1; @endphp
                    <table class="table table-borderless table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th width="28">#</th>
                                <th>Area / Zone</th>
                                <th class="text-right">Stores</th>
                                <th width="160">Leads</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($topAreasByLeads as $k => $row)
                            <tr>
                                <td><span class="rank-badge {{ $k < 3 ? 'rank-'.($k+1) : '' }}">{{ $k+1 }}</span></td>
                                <td class="font-weight-medium">{{ $row->name }}</td>
                                <td class="text-right text-muted small">{{ $row->store_count }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1 progress-sm">
                                            <div class="progress-bar bg-danger" style="width:{{ round($row->lead_count/$maxAL*100) }}%"></div>
                                        </div>
                                        <span class="small font-weight-bold text-dark" style="min-width:40px;text-align:right">{{ number_format($row->lead_count) }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header py-3">
                    <h5 class="mb-0">Top Areas by Store Visits</h5>
                    <div class="section-title mt-1">Zones generating the most store page views</div>
                </div>
                <div class="card-body pt-0 px-0">
                    @if($topAreasByVisits->isEmpty())
                        <div class="text-center text-muted py-4">No data for this period</div>
                    @else
                    @php $maxAV = $topAreasByVisits->max('visit_count') ?: 1; @endphp
                    <table class="table table-borderless table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th width="28">#</th>
                                <th>Area / Zone</th>
                                <th class="text-right">Stores</th>
                                <th width="160">Visits</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($topAreasByVisits as $k => $row)
                            <tr>
                                <td><span class="rank-badge {{ $k < 3 ? 'rank-'.($k+1) : '' }}">{{ $k+1 }}</span></td>
                                <td class="font-weight-medium">{{ $row->name }}</td>
                                <td class="text-right text-muted small">{{ $row->store_count }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1 progress-sm">
                                            <div class="progress-bar bg-primary" style="width:{{ round($row->visit_count/$maxAV*100) }}%"></div>
                                        </div>
                                        <span class="small font-weight-bold text-dark" style="min-width:40px;text-align:right">{{ number_format($row->visit_count) }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Store Lead Activity (full width) ────────────────────────────────── --}}
    <div class="card mb-4">
        <div class="card-header py-3">
            <h5 class="mb-0">Store Lead Activity</h5>
            <div class="section-title mt-1">Stores ranked by phone enquiries received in the selected period</div>
        </div>
        <div class="table-responsive">
            @if($storeLeadActivity->isEmpty())
                <div class="text-center text-muted py-4">No lead activity for this period</div>
            @else
            @php $maxSL = $storeLeadActivity->max('lead_count') ?: 1; @endphp
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th width="28">#</th>
                        <th>Store</th>
                        <th>Total Leads</th>
                        <th>Unique Users</th>
                        <th width="200">Lead Volume</th>
                        <th class="text-center">Detail</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($storeLeadActivity as $k => $row)
                    <tr>
                        <td><span class="rank-badge {{ $k < 3 ? 'rank-'.($k+1) : '' }}">{{ $k+1 }}</span></td>
                        <td class="font-weight-medium">{{ $row->name }}</td>
                        <td><span class="badge badge-soft-success font-size-sm">{{ number_format($row->lead_count) }}</span></td>
                        <td class="text-muted">{{ number_format($row->unique_leads) }}</td>
                        <td>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-success" style="width:{{ round($row->lead_count/$maxSL*100) }}%"></div>
                            </div>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.analytics.detail', ['type'=>'call','id'=>$row->id]) }}"
                               class="btn btn-xs btn-outline-primary" title="View detail">
                                <i class="tio-open-in-new"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    {{-- ── Stores with Most Banners ─────────────────────────────────────────── --}}
    <div class="card mb-4">
        <div class="card-header py-3">
            <h5 class="mb-0">Stores with Most Banners</h5>
            <div class="section-title mt-1">Vendors actively promoting — banner count and total click performance</div>
        </div>
        <div class="table-responsive">
            @if($topBannerStores->isEmpty())
                <div class="text-center text-muted py-4">No vendor banners found</div>
            @else
            @php $maxBan = $topBannerStores->max('banner_count') ?: 1; @endphp
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th width="28">#</th>
                        <th>Store</th>
                        <th>Banners Posted</th>
                        <th>Total Banner Clicks</th>
                        <th width="200">Banner Volume</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($topBannerStores as $k => $row)
                    <tr>
                        <td><span class="rank-badge {{ $k < 3 ? 'rank-'.($k+1) : '' }}">{{ $k+1 }}</span></td>
                        <td class="font-weight-medium">{{ $row->name }}</td>
                        <td><span class="badge badge-soft-warning">{{ $row->banner_count }}</span></td>
                        <td class="text-muted">{{ number_format($row->total_clicks ?? 0) }}</td>
                        <td>
                            <div class="progress progress-sm">
                                <div class="progress-bar" style="width:{{ round($row->banner_count/$maxBan*100) }}%; background:#f5ca99;"></div>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

</div>
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')

<script src="{{ asset('/public/assets/admin/js/apex-charts/apexcharts.js') }}"></script>
<script>
"use strict";

const chartDataUrl = "{{ route('admin.marketing-dashboard.chart-data') }}";
let trendChart;

function initChart(days) {
    fetch(chartDataUrl + '?days=' + days)
        .then(r => r.json())
        .then(d => {
            const options = {
                chart: { type: 'area', height: 300, toolbar: { show: false }, zoom: { enabled: false } },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
                series: [
                    { name: 'Store Visits', data: d.visits },
                    { name: 'Leads (Calls)', data: d.leads },
                    { name: 'Banner Clicks', data: d.bannerClicks },
                ],
                colors: ['#377dff', '#00c9a7', '#f5ca99'],
                xaxis: { categories: d.labels, labels: { rotate: -45, style: { fontSize: '11px' } }, tickAmount: Math.min(14, days) },
                yaxis: { labels: { formatter: v => Number(v).toLocaleString() } },
                tooltip: { shared: true, intersect: false, y: { formatter: v => Number(v).toLocaleString() } },
                legend: { position: 'top' },
                grid: { borderColor: '#e7eaf3', strokeDashArray: 3 },
            };

            if (trendChart) {
                trendChart.destroy();
            }
            trendChart = new ApexCharts(document.querySelector('#trendChart'), options);
            trendChart.render();
        });
}

document.querySelectorAll('#trendDays button').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#trendDays button').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        initChart(parseInt(this.dataset.days));
    });
});

initChart(7);
</script>
@include('admin-views.js.date_range')
@endpush
