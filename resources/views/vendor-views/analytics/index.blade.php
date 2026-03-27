@extends('layouts.vendor.app')

@section('title', 'Analytics')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/module.png') }}" alt="">
                </span>
                <span>Analytics</span>
            </h1>
        </div>

        {{-- Chart --}}
        <div class="card mb-3">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Analytics Overview</h5>
                <select id="chartDays" class="form-control" style="width:150px;">
                    <option value="7">Last 7 Days</option>
                    <option value="30" selected>Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                </select>
            </div>
            <div class="card-body">
                <canvas id="analyticsChart" style="height:300px; max-height:250px;"></canvas>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="row g-2 mb-3">
            <div class="col " >
                <div class="card card-body py-3 text-center" style="background-color:#004dff21;">
                    <h3 class="mb-0">{{ $counts['store_visits'] }}</h3>
                    <small class="text-muted">Store Visits</small>
                </div>
            </div>
            <div class="col">
                <div class="card card-body py-3 text-center" style="background-color:#ff001f21;">
                    <h3 class="mb-0">{{ $counts['banner_clicks'] }}</h3>
                    <small class="text-muted">Banner Clicks</small>
                </div>
            </div>
            <div class="col">
                <div class="card card-body py-3 text-center" style="background-color:#ffe80042;">
                    <h3 class="mb-0">{{ $counts['ad_clicks'] }}</h3>
                    <small class="text-muted">Ad Clicks</small>
                </div>
            </div>
            <div class="col">
                <div class="card card-body py-3 text-center" style="background-color:#ab00ff42;">
                    <h3 class="mb-0">{{ $counts['location_views'] }}</h3>
                    <small class="text-muted">Location Views</small>
                </div>
            </div>
            <div class="col">
                <div class="card card-body py-3 text-center" style="background-color:#40ff0042;">
                    <h3 class="mb-0">{{ $counts['phone_calls'] }}</h3>
                    <small class="text-muted">Phone Calls</small>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        

        {{-- Search & Date Filter --}}
        <div class="card mb-3">
            <div class="card-body">
            <div class="d-flex justify-content-between w-100">
            <ul class="nav nav-tabs border-0 nav--tabs nav--pills mb-3">
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'store_visits' ? 'active' : '' }}"
                    href="{{ route('vendor.analytics.index', ['tab' => 'store_visits']) }}">Store Visits</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'banners' ? 'active' : '' }}"
                    href="{{ route('vendor.analytics.index', ['tab' => 'banners']) }}">Banner Clicks</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'ads' ? 'active' : '' }}"
                    href="{{ route('vendor.analytics.index', ['tab' => 'ads']) }}">Ad Clicks</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'location_views' ? 'active' : '' }}"
                    href="{{ route('vendor.analytics.index', ['tab' => 'location_views']) }}">Location Views</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'phone_calls' ? 'active' : '' }}"
                    href="{{ route('vendor.analytics.index', ['tab' => 'phone_calls']) }}">Phone Calls</a>
            </li>
        </ul>
                <form class="row align-items-end g-2 date-range-form mb-2">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <div class="col-md-9">
                        <div class="input-group input--group">
                            <input type="search" name="search" value="{{ $search }}" class="form-control"
                                placeholder="{{ translate('messages.ex_:_search_user_name_or_phone') }}">
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning" type="button"
                            data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
                        @include('vendor-views/form_modals/date_range')
                    </div>
                </form>
            </div>



                <div class="table-responsive">
                    @if ($tab == 'store_visits' || $tab == 'location_views' || $tab == 'phone_calls')
                        <table class="table table-borderless table-thead-bordered table-align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>User Phone</th>
                                    <th>IP</th>
                                    <th>Screen Type</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data['items'] as $key => $item)
                                    <tr>
                                        <td>{{ $key + $data['items']->firstItem() }}</td>
                                        <td>
                                            @if ($item->f_name)
                                                {{ $item->f_name . ' ' . $item->l_name }}
                                            @elseif ($item->user_id)
                                                <span class="text-muted">Deleted User #{{ $item->user_id }}</span>
                                            @else
                                                <span class="text-muted">Guest</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->user_phone ?? '-' }}</td>
                                        <td><code>{{ $item->ip }}</code></td>
                                        <td><span class="badge badge-soft-info">{{ $item->screen_type ?? '-' }}</span></td>
                                        <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">No data found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @elseif ($tab == 'banners')
                        <table class="table table-borderless table-thead-bordered table-align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Banner</th>
                                    <th>User</th>
                                    <th>User Phone</th>
                                    <th>IP</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data['items'] as $key => $item)
                                    <tr>
                                        <td>{{ $key + $data['items']->firstItem() }}</td>
                                        <td>{{ $item->banner_title ?? 'Deleted Banner' }}</td>
                                        <td>
                                            @if ($item->f_name)
                                                {{ $item->f_name . ' ' . $item->l_name }}
                                            @elseif ($item->user_id)
                                                <span class="text-muted">Deleted User #{{ $item->user_id }}</span>
                                            @else
                                                <span class="text-muted">Guest</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->user_phone ?? '-' }}</td>
                                        <td><code>{{ $item->ip }}</code></td>
                                        <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">No data found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @elseif ($tab == 'ads')
                        <table class="table table-borderless table-thead-bordered table-align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Notification</th>
                                    <th>User</th>
                                    <th>User Phone</th>
                                    <th>IP</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data['items'] as $key => $item)
                                    <tr>
                                        <td>{{ $key + $data['items']->firstItem() }}</td>
                                        <td>{{ $item->notif_title ?? 'Deleted Notification' }}</td>
                                        <td>
                                            @if ($item->f_name)
                                                {{ $item->f_name . ' ' . $item->l_name }}
                                            @elseif ($item->user_id)
                                                <span class="text-muted">Deleted User #{{ $item->user_id }}</span>
                                            @else
                                                <span class="text-muted">Guest</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->user_phone ?? '-' }}</td>
                                        <td><code>{{ $item->ip }}</code></td>
                                        <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">No data found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif
                </div>

                @if (!empty($data['items']))
                    <div class="card-footer border-0 pt-0">
                        <div class="d-flex justify-content-center justify-content-sm-end">
                            {!! $data['items']->links() !!}
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection

@push('script_2')
    @include('admin-views.js.date_range')
    <script src="{{ asset('public/assets/admin/vendor/chart.js/dist/Chart.min.js') }}"></script>
    <script>
        var analyticsChart = null;

        function loadAnalyticsChart(days) {
            $.get("{{ route('vendor.analytics.chart-data') }}", {
                days: days
            }, function(data) {
                if (analyticsChart) {
                    analyticsChart.destroy();
                }

                var ctx = document.getElementById('analyticsChart');
                if (!ctx) return;

                analyticsChart = new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                                label: 'Store Visits',
                                data: data.store_visits,
                                borderColor: '#45b6fe',
                                backgroundColor: 'rgba(69,182,254,0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                            },
                            {
                                label: 'Banner Clicks',
                                data: data.banner_clicks,
                                borderColor: '#ff6b6b',
                                backgroundColor: 'rgba(255,107,107,0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                            },
                            {
                                label: 'Ad Clicks',
                                data: data.ad_clicks,
                                borderColor: '#ffa500',
                                backgroundColor: 'rgba(255,165,0,0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                            },
                            {
                                label: 'Location Views',
                                data: data.location_views,
                                borderColor: '#9b59b6',
                                backgroundColor: 'rgba(155,89,182,0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                            },
                            {
                                label: 'Phone Calls',
                                data: data.phone_calls,
                                borderColor: '#51cf66',
                                backgroundColor: 'rgba(81,207,102,0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                    precision: 0
                                }
                            }]
                        },
                        tooltips: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                });
            });
        }

        $(document).ready(function() {
            loadAnalyticsChart(30);
            $('#chartDays').on('change', function() {
                loadAnalyticsChart($(this).val());
            });
        });
    </script>
@endpush
