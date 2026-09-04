@extends('layouts.vendor.app')

@section('title', 'Performance Analytics')

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
                <span>Performance Analytics</span>
            </h1>
        </div>

        {{-- Free Trial Banner --}}
        @if (!$hasAccess && !$trialActive && !$trialExpired)
            <div class="card mb-4" style=" background: linear-gradient(135deg, #f3f0ff, #eee4f6);">
                <div class="card-body text-center py-5">
                    <img src="{{ asset('storage/uploaded/util/image (2).png') }}"
                        style="    width: 220px;
    border-radius: 10px;" alt="">
                    <h3 class="mt-3 mb-1">Unlock Performance Analytics</h3>
                    <p class="text-muted mb-4">Get detailed insights — store visits, banner clicks, calls, shares and
                        more.<br>Try it free for <strong>1 month</strong>.</p>
                    <form action="{{ route('vendor.performance-analytics.claim-trial') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-lg"
                            style="background:#6c5ce7; color:#fff; padding: 12px 40px; border-radius:8px;">
                            <i class="tio-gift"></i> Start Free Trial
                        </button>
                    </form>
                </div>
            </div>
        @elseif($trialExpired)
            <div class="alert alert-warning d-flex align-items-center justify-content-between mb-3">
                <span><i class="tio-time"></i> Your 1-month free trial expired on
                    <strong>{{ \Carbon\Carbon::parse($trial->plan_expiry)->format('d M Y') }}</strong>. Upgrade to a plan to
                    continue.</span>
                <a href="{{ route('vendor.subscriptions') }}" class="btn btn-sm btn-warning ml-3">View Plans</a>
            </div>
        @elseif($trialActive)
            <div class="alert alert-soft-success d-flex align-items-center justify-content-between mb-3">
                <span><i class="tio-gift"></i> Free trial active —
                    <strong>{{ \Carbon\Carbon::parse($trial->plan_expiry)->diffInDays(now()) }} day(s) remaining</strong>
                    (expires {{ \Carbon\Carbon::parse($trial->plan_expiry)->format('d M Y') }})</span>
                <a href="{{ route('vendor.subscriptions') }}" class="btn btn-sm btn-success ml-3">Upgrade Plan</a>
            </div>
        @endif

        @if ($hasAccess || $trialActive)
            {{-- Chart --}}
            <div class="card mb-3">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Performance Analytics Overview</h5>
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
        @endif

        {{-- Summary Cards --}}
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-4 col-xl">
                <div class="card card-body py-3 text-center" style="background-color:#004dff21;">
                    <h3 class="mb-0">{{ $counts['store_visits'] }}</h3>
                    <small class="text-muted">Store Visits</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card card-body py-3 text-center" style="background-color:#ff001f21;">
                    <h3 class="mb-0">{{ $counts['banner_clicks'] }}</h3>
                    <small class="text-muted">Banner Clicks</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card card-body py-3 text-center" style="background-color:#ffe80042;">
                    <h3 class="mb-0">{{ $counts['ad_clicks'] }}</h3>
                    <small class="text-muted">Ad Clicks</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card card-body py-3 text-center" style="background-color:#ab00ff42;">
                    <h3 class="mb-0">{{ $counts['location_views'] }}</h3>
                    <small class="text-muted">Location Views</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card card-body py-3 text-center" style="background-color:#40ff0042;">
                    <h3 class="mb-0">{{ $counts['phone_calls'] }}</h3>
                    <small class="text-muted">Phone (Call/Copy)</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card card-body py-3 text-center" style="background-color:#25d3662e;">
                    <h3 class="mb-0">{{ $counts['whatsapp'] }}</h3>
                    <small class="text-muted">WhatsApp Chats</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card card-body py-3 text-center" style="background-color:#00c8ff21;">
                    <h3 class="mb-0">{{ $counts['shares'] }}</h3>
                    <small class="text-muted">Shares</small>
                </div>
            </div>
        </div>

        {{-- Guest (not logged in) store-card actions. These visitors have no account, so there is
             no name or phone to attribute them to — they are reported here on their own. --}}
        <div class="card mb-3">
            <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">Guest Activity &mdash; Not Logged In</h5>
                    <small class="text-muted">Share, Call Now, Enquiry Now &amp; WhatsApp taps on your store card from
                        visitors who were not signed in. No name or phone is captured for these.</small>
                </div>
                <a href="{{ route('vendor.performance-analytics.index', ['tab' => 'guest_actions']) }}"
                    class="btn btn-sm btn-outline-primary">View Details</a>
            </div>
            <div class="card-body pt-0">
                <div class="row g-2">
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card card-body py-3 text-center" style="background-color:#6c5ce71f;">
                            <h3 class="mb-0">{{ $guestCounts['total'] }}</h3>
                            <small class="text-muted">Total Guest Actions</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card card-body py-3 text-center" style="background-color:#00c8ff21;">
                            <h3 class="mb-0">{{ $guestCounts['shares'] }}</h3>
                            <small class="text-muted">Shares</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card card-body py-3 text-center" style="background-color:#40ff0042;">
                            <h3 class="mb-0">{{ $guestCounts['phone_calls'] }}</h3>
                            <small class="text-muted">Call Now</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card card-body py-3 text-center" style="background-color:#ffa50029;">
                            <h3 class="mb-0">{{ $guestCounts['enquiries'] }}</h3>
                            <small class="text-muted">Enquiry Now</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card card-body py-3 text-center" style="background-color:#25d3662e;">
                            <h3 class="mb-0">{{ $guestCounts['whatsapp'] }}</h3>
                            <small class="text-muted">WhatsApp Msg</small>
                        </div>
                    </div>
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
                                    href="{{ route('vendor.performance-analytics.index', ['tab' => 'store_visits']) }}">Store
                                    Visits</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $tab == 'banners' ? 'active' : '' }}"
                                    href="{{ route('vendor.performance-analytics.index', ['tab' => 'banners']) }}">Banner
                                    Clicks</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $tab == 'ads' ? 'active' : '' }}"
                                    href="{{ route('vendor.performance-analytics.index', ['tab' => 'ads']) }}">Ad
                                    Clicks</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $tab == 'location_views' ? 'active' : '' }}"
                                    href="{{ route('vendor.performance-analytics.index', ['tab' => 'location_views']) }}">Location
                                    Views</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $tab == 'phone_calls' ? 'active' : '' }}"
                                    href="{{ route('vendor.performance-analytics.index', ['tab' => 'phone_calls']) }}">Phone (Call/Copy)</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $tab == 'whatsapp' ? 'active' : '' }}"
                                    href="{{ route('vendor.performance-analytics.index', ['tab' => 'whatsapp']) }}">WhatsApp</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $tab == 'shares' ? 'active' : '' }}"
                                    href="{{ route('vendor.performance-analytics.index', ['tab' => 'shares']) }}">Shares</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $tab == 'guest_actions' ? 'active' : '' }}"
                                    href="{{ route('vendor.performance-analytics.index', ['tab' => 'guest_actions']) }}">Guest
                                    Activity</a>
                            </li>
                        </ul>
                                @if ($hasAccess || $trialActive)

                        <form class="row align-items-end g-2 date-range-form mb-2">
                            <input type="hidden" name="tab" value="{{ $tab }}">
                            <div class="col-md-9">
                                <div class="input-group input--group">
                                    <input type="search" name="search" value="{{ $search }}"
                                        class="form-control"
                                        placeholder="{{ $tab == 'guest_actions' ? translate('Ex : search by IP address') : translate('messages.ex_:_search_user_name_or_phone') }}">
                                    <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning"
                                    type="button" data-toggle="modal"
                                    data-target="#dateRangeModal">{{ translate($preset) }}</button>
                                @include('vendor-views/form_modals/date_range')
                            </div>
                        </form>
                        @endif
                    </div>


        @if ($hasAccess || $trialActive)

                    <div class="table-responsive">
                        @if ($tab == 'store_visits' || $tab == 'location_views' || $tab == 'phone_calls' || $tab == 'whatsapp')
                            <table class="table table-borderless table-thead-bordered table-align-middle">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>User</th>
                                        <th>User Phone</th>
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
                                            <td><span class="badge badge-soft-info">{{ $item->screen_type ?? '-' }}</span>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">No data found</td>
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
                                            <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">No data found</td>
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
                                            <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">No data found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        @elseif ($tab == 'shares')
                            <table class="table table-borderless table-thead-bordered table-align-middle">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Type</th>
                                        <th>Name</th>
                                        <th>User</th>
                                        <th>User Phone</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($data['items'] as $key => $item)
                                        <tr>
                                            <td>{{ $key + $data['items']->firstItem() }}</td>
                                            <td>
                                                @if ($item->sub_type == 'store')
                                                    <span class="badge badge-soft-primary">Store</span>
                                                @elseif ($item->sub_type == 'service')
                                                    <span class="badge badge-soft-success">Service</span>
                                                @else
                                                    <span class="text-muted">{{ $item->sub_type ?? '-' }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $item->entity_name }}</td>
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
                                            <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4">No data found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        @elseif ($tab == 'guest_actions')
                            <table class="table table-borderless table-thead-bordered table-align-middle">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Action</th>
                                        <th>Store / Service</th>
                                        <th>IP</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($data['items'] as $key => $item)
                                        <tr>
                                            <td>{{ $key + $data['items']->firstItem() }}</td>
                                            <td>
                                                @if ($item->screen_type == 'share')
                                                    <span class="badge badge-soft-info">Share</span>
                                                @elseif ($item->screen_type == 'whatsapp')
                                                    <span class="badge badge-soft-success">WhatsApp Msg</span>
                                                @elseif ($item->screen_type == 'enquiry')
                                                    <span class="badge badge-soft-warning">Enquiry Now</span>
                                                @elseif ($item->screen_type == 'copy')
                                                    <span class="badge badge-soft-primary">Phone Copied</span>
                                                @else
                                                    <span class="badge badge-soft-primary">Call Now</span>
                                                @endif
                                            </td>
                                            <td>{{ $item->entity_name }}</td>
                                            <td>{{ $item->ip ?? '-' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">No guest activity yet</td>
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
            $.get("{{ route('vendor.performance-analytics.chart-data') }}", {
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
                            },
                            {
                                label: 'WhatsApp Chats',
                                data: data.whatsapp,
                                borderColor: '#25D366',
                                backgroundColor: 'rgba(37,211,102,0.1)',
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
