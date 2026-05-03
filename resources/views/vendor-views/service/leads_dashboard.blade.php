@extends('layouts.vendor.app')
@section('title', 'Leads Dashboard')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <style>
        .ld-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }

        .ld-stat {
            background: #fff;
            border: 1px solid #e4e4e7;
            border-radius: 10px;
            padding: 10px 13px;
            display: block;
            text-decoration: none;
            transition: box-shadow .15s, border-color .15s, transform .12s;
        }

        a.ld-stat:hover {
            box-shadow: 0 4px 14px rgba(0, 0, 0, .09);
            border-color: #18181b;
            transform: translateY(-1px);
            text-decoration: none;
        }

        .ld-stat-label {
            font-size: 12px;
            font-weight: 700;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 3px;
        }

        .ld-stat-val {
            font-size: 22px;
            font-weight: 800;
            color: #18181b;
            line-height: 1;
        }

        .ld-stat-sub {
            font-size: 10px;
            color: #71717a;
            margin-top: 2px;
        }

        .ld-stat.ld-green {
            border-left: 3px solid #22c55e;
        }

        .ld-stat.ld-blue {
            border-left: 3px solid #3b82f6;
        }

        .ld-stat.ld-amber {
            border-left: 3px solid #f59e0b;
        }

        .ld-stat.ld-red {
            border-left: 3px solid #ef4444;
        }

        .ld-stat.ld-zinc {
            border-left: 3px solid #71717a;
        }

        .ld-stat.ld-dark {
            border-left: 3px solid #18181b;
        }

        .ld-card {
            background: #fff;
            border: 1px solid #e4e4e7;
            border-radius: 10px;
            padding: 13px 16px;
            margin-bottom: 10px;
        }

        .ld-card-title {
            font-size: 12px;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 10px;
        }

        .rate-bar {
            height: 5px;
            border-radius: 99px;
            background: #f4f4f5;
            overflow: hidden;
            margin-top: 5px;
        }

        .rate-bar-fill {
            height: 100%;
            border-radius: 99px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">

        <div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <h1 class="page-header-title">Leads Dashboard</h1>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if (auth('vendor')->check())
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-sm" data-toggle="modal"
                        data-target="#defaultDashboardModal" style="font-size:17px;font-weight:600;">
                        <i class="tio-dashboard-outlined"></i>
                    </button>
                    @include('vendor-views/form_modals/default_dashboard')
                @endif
                <form class="date-range-form" method="GET" action="{{ route('vendor.service.leads-dashboard') }}">
                    <input type="hidden" name="date_range" value="{{ $preset }}">
                    <input type="hidden" name="custom_date_range" value="{{ request('custom_date_range') }}">
                    <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning" type="button"
                        data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
                    @include('vendor-views/form_modals/date_range')
                </form>
            </div>
        </div>

        {{-- ── Stat cards ── --}}
        @php
            $leadsBase = route('vendor.service.leads_list');
            $dateQ = http_build_query([
                'date_range' => $preset,
                'custom_date_range' => request('custom_date_range', ''),
            ]);
            $leadsUrl = fn($type) => $leadsBase .
                '?' .
                http_build_query([
                    'type' => $type,
                    'date_range' => $preset,
                    'custom_date_range' => request('custom_date_range', ''),
                ]);
        @endphp
        <div class="ld-stat-grid">
            <a href="{{ $leadsUrl('All') }}" class="ld-stat ld-dark">
                <div class="ld-stat-label">Total Leads</div>
                <div class="ld-stat-val">{{ $total }}</div>
            </a>
            <a href="{{ $leadsUrl('Accepted') }}" class="ld-stat ld-blue">
                <div class="ld-stat-label">Accepted</div>
                <div class="ld-stat-val">{{ $accepted }}</div>
                <div class="ld-stat-sub">{{ $acceptanceRate }}% rate</div>
            </a>
            <a href="{{ $leadsUrl('Completed') }}" class="ld-stat ld-green">
                <div class="ld-stat-label">Completed</div>
                <div class="ld-stat-val">{{ $completed }}</div>
                <div class="ld-stat-sub">{{ $completionRate }}% rate</div>
            </a>
            <a href="{{ $leadsUrl('Cancelled') }}" class="ld-stat ld-red">
                <div class="ld-stat-label">Cancelled</div>
                <div class="ld-stat-val">{{ $cancelled }}</div>
                <div class="ld-stat-sub">{{ $cancellationRate }}% of accepted</div>
            </a>
            <a href="{{ $leadsUrl('Missed') }}" class="ld-stat ld-amber">
                <div class="ld-stat-label">Missed</div>
                <div class="ld-stat-val">{{ $missed }}</div>
            </a>
            <a href="{{ $leadsUrl('Accepted') }}" class="ld-stat ld-zinc">
                <div class="ld-stat-label">In Progress</div>
                <div class="ld-stat-val">{{ max(0, $accepted - $completed - $cancelled) }}</div>
            </a>
        </div>

        {{-- ── Rate bars ── --}}
        <div class="ld-card" style="margin-bottom:10px;">
            <div style="display:flex;gap:24px;flex-wrap:wrap;">
                <div style="flex:1;min-width:80px;">
                    <div style="font-size:10px;font-weight:700;color:#000000;">Acceptance</div>
                    <div style="font-size:17px;font-weight:800;color:#3b82f6;">{{ $acceptanceRate }}%</div>
                    <div class="rate-bar">
                        <div class="rate-bar-fill" style="width:{{ $acceptanceRate }}%;background:#3b82f6;"></div>
                    </div>
                </div>
                <div style="flex:1;min-width:80px;">
                    <div style="font-size:10px;font-weight:700;color:#000000;">Completion</div>
                    <div style="font-size:17px;font-weight:800;color:#22c55e;">{{ $completionRate }}%</div>
                    <div class="rate-bar">
                        <div class="rate-bar-fill" style="width:{{ $completionRate }}%;background:#22c55e;"></div>
                    </div>
                </div>
                <div style="flex:1;min-width:80px;">
                    <div style="font-size:10px;font-weight:700;color:#000000;">Cancellation</div>
                    <div style="font-size:17px;font-weight:800;color:#ef4444;">{{ $cancellationRate }}%</div>
                    <div class="rate-bar">
                        <div class="rate-bar-fill" style="width:{{ $cancellationRate }}%;background:#ef4444;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Charts row ── --}}
        <div class="row" style="margin:0 -5px;">
            <div class="col-md-6" style="padding:0 5px;">
                <div class="ld-card">
                    <div class="ld-card-title">Leads Over Time</div>
                    <canvas id="lineChart" height="140"></canvas>
                </div>
            </div>
            <div class="col-md-3" style="padding:0 5px;">
                <div class="ld-card" style="height:220px;display:flex;flex-direction:column;">
                    <div class="ld-card-title">Status Breakdown</div>
                    <div style="flex:1;position:relative;">
                        <canvas id="donutChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-3" style="padding:0 5px;">
                <div class="ld-card" style="height:220px;display:flex;flex-direction:column;">
                    <div class="ld-card-title">Top Services</div>
                    <div style="flex:1;position:relative;">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Recent Leads ── --}}
        <div class="ld-card" style="margin-top:10px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <div class="ld-card-title" style="margin:0;">Recent Leads</div>
                <a href="{{ route('vendor.service.leads_list') }}?date_range={{ $preset }}&custom_date_range={{ request('custom_date_range', '') }}"
                    style="font-size:11px;font-weight:600;color:#3b82f6;text-decoration:none;">View All →</a>
            </div>
            @if ($recentLeads->isEmpty())
                <p style="font-size:12px;color:#a1a1aa;text-align:center;padding:16px 0;margin:0;">No leads in this period.
                </p>
            @else
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:12px;">
                        <thead>
                            <tr style="border-bottom:1px solid #f0f0f0;">
                                <th
                                    style="padding:6px 10px;text-align:left;font-weight:700;color:#000000;text-transform:uppercase;font-size:10px;letter-spacing:.04em;white-space:nowrap;">
                                    #</th>
                                <th
                                    style="padding:6px 10px;text-align:left;font-weight:700;color:#000000;text-transform:uppercase;font-size:10px;letter-spacing:.04em;">
                                    Service</th>
                                <th
                                    style="padding:6px 10px;text-align:left;font-weight:700;color:#000000;text-transform:uppercase;font-size:10px;letter-spacing:.04em;">
                                    Customer</th>
                                <th
                                    style="padding:6px 10px;text-align:left;font-weight:700;color:#000000;text-transform:uppercase;font-size:10px;letter-spacing:.04em;white-space:nowrap;">
                                    Date</th>
                                <th
                                    style="padding:6px 10px;text-align:left;font-weight:700;color:#000000;text-transform:uppercase;font-size:10px;letter-spacing:.04em;">
                                    Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentLeads as $lead)
                                @php
                                    $statusLabel = $lead->current_status ?: $lead->display_status;
                                    $statusColor = match (strtolower($statusLabel)) {
                                        'completed' => ['bg' => '#f0fdf4', 'color' => '#14532d', 'border' => '#86efac'],
                                        'cancelled' => ['bg' => '#fef2f2', 'color' => '#991b1b', 'border' => '#fecaca'],
                                        'missed' => ['bg' => '#fafafa', 'color' => '#52525b', 'border' => '#e4e4e7'],
                                        'new' => ['bg' => '#f0fdf4', 'color' => '#166534', 'border' => '#bbf7d0'],
                                        'accepted', 'confirmation request sent' => [
                                            'bg' => '#fefce8',
                                            'color' => '#854d0e',
                                            'border' => '#fde68a',
                                        ],
                                        default => ['bg' => '#f4f4f5', 'color' => '#52525b', 'border' => '#e4e4e7'],
                                    };
                                @endphp
                                <tr style="border-bottom:1px solid #f9f9f9;">
                                    <td style="padding:8px 10px;color:#000000;font-weight:600;">#{{ $lead->id }}</td>
                                    <td
                                        style="padding:8px 10px;font-weight:600;color:#18181b;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        {{ $lead->service_name }}</td>
                                    <td style="padding:8px 10px;color:#52525b;white-space:nowrap;">
                                        {{ $lead->f_name ?: '—' }}</td>
                                    <td style="padding:8px 10px;color:#a1a1aa;white-space:nowrap;">
                                        {{ \Carbon\Carbon::parse($lead->created_at)->format('d M, h:i A') }}</td>
                                    <td style="padding:8px 10px;">
                                        <span
                                            style="display:inline-block;padding:2px 9px;border-radius:99px;font-size:10px;font-weight:700;background:{{ $statusColor['bg'] }};color:{{ $statusColor['color'] }};border:1px solid {{ $statusColor['border'] }};">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/vendor/chart.js/dist/Chart.min.js') }}"></script>
    @include('vendor-views/js/date_range')
    <script>
        function updateSelection() {
            var selected = $('input[name="date_range"]:checked').val() || '';
            var custom = $('#daterange').val() || '';
            $('form.date-range-form input[name="date_range"]').val(selected);
            $('form.date-range-form input[name="custom_date_range"]').val(selected === 'custom' ? custom : '');
            $('#dateRangeModal').modal('hide');
            $('form.date-range-form').submit();
        }

        Chart.defaults.global.defaultFontFamily = 'inherit';
        Chart.defaults.global.defaultFontSize = 12;

        new Chart(document.getElementById('lineChart'), {
            type: 'line',
            data: {
                labels: @json($dailyLabels),
                datasets: [{
                        label: 'New Leads',
                        data: @json($dailyNew),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.08)',
                        borderWidth: 2,
                        pointRadius: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Accepted',
                        data: @json($dailyAccepted),
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34,197,94,0.07)',
                        borderWidth: 2,
                        pointRadius: 2,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12
                    }
                },
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false
                        },
                        ticks: {
                            maxTicksLimit: 14,
                            maxRotation: 0
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            precision: 0
                        }
                    }]
                }
            }
        });

        new Chart(document.getElementById('donutChart'), {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Cancelled', 'Missed', 'In Progress'],
                datasets: [{
                    data: [{{ $completed }}, {{ $cancelled }}, {{ $missed }},
                        {{ max(0, $accepted - $completed - $cancelled) }}
                    ],
                    backgroundColor: ['#22c55e', '#ef4444', '#f59e0b', '#3b82f6'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        fontSize: 11
                    }
                },
                cutoutPercentage: 65
            }
        });

        new Chart(document.getElementById('barChart'), {
            type: 'horizontalBar',
            data: {
                labels: @json($topServices->pluck('name')),
                datasets: [{
                    label: 'Leads',
                    data: @json($topServices->pluck('cnt')),
                    backgroundColor: 'rgba(59,130,246,0.75)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    display: false
                },
                scales: {
                    xAxes: [{
                        ticks: {
                            beginAtZero: true,
                            precision: 0
                        }
                    }],
                    yAxes: [{
                        gridLines: {
                            display: false
                        }
                    }]
                }
            }
        });
    </script>
@endpush
