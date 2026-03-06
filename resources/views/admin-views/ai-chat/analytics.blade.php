@extends('layouts.admin.app')

@section('title', 'AI Chat Analytics')

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Chat Analytics</h4>
            <div class="d-flex gap-2">
                <select class="form-control form-control-sm" style="width:auto"
                    onchange="location.href='{{ route('admin.ai-chat.analytics') }}?days='+this.value">
                    @foreach ([7, 14, 30, 60, 90] as $d)
                        <option value="{{ $d }}" {{ $days == $d ? 'selected' : '' }}>{{ $d }}d</option>
                    @endforeach
                </select>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-success dropdown-toggle" data-toggle="dropdown">Export</button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item"
                            href="{{ route('admin.ai-chat.analytics.export', ['days' => $days, 'type' => 'all']) }}">All</a>
                        <a class="dropdown-item"
                            href="{{ route('admin.ai-chat.analytics.export', ['days' => $days, 'type' => 'user']) }}">Users</a>
                        <a class="dropdown-item"
                            href="{{ route('admin.ai-chat.analytics.export', ['days' => $days, 'type' => 'vendor']) }}">Vendors</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats row --}}
        <div class="row mb-3">
            @php $stats = [['v' => $totalMessages, 'l' => 'Messages', 'c' => '#4f46e5'], ['v' => $totalConversations, 'l' => 'Conversations', 'c' => '#0ea5e9'], ['v' => $totalUsers, 'l' => 'Users', 'c' => '#3b82f6'], ['v' => $totalVendors, 'l' => 'Vendors', 'c' => '#10b981'], ['v' => $voiceMessages, 'l' => 'Voice', 'c' => '#f59e0b'], ['v' => $avgMsgsPerConv, 'l' => 'Avg/Conv', 'c' => '#8b5cf6']]; @endphp
            @foreach ($stats as $s)
                <div class="col-4 col-lg-2 mb-2">
                    <div class="card card-body py-2 px-3 text-center">
                        <div style="font-size:28px;font-weight:700;color:{{ $s['c'] }}">{{ $s['v'] }}</div>
                        <small class="text-muted">{{ $s['l'] }}</small>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Daily chart + Message split --}}
        <div class="row mb-3">
            <div class="col-lg-8 mb-2">
                <div class="card h-100">
                    <div class="card-body py-2">
                        <canvas id="dailyChart" height="65"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-2">
                <div class="card h-100">
                    <div class="card-body py-2">
                        <canvas id="typeChart" height="140"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hourly + User/AI split --}}
        <div class="row mb-3">
            <div class="col-lg-8 mb-2">
                <div class="card h-100">
                    <div class="card-body py-2">
                        <small class="text-muted">Peak Hours</small>
                        <canvas id="hourlyChart" height="55"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-2">
                <div class="card h-100">
                    <div class="card-body py-2">
                        <small class="text-muted">User vs AI</small>
                        @php
                            $total = $userMessages + $assistantMessages;
                            $pct = $total > 0 ? round(($userMessages / $total) * 100) : 0;
                        @endphp
                        <div class="mt-2 mb-1"><strong>{{ number_format($userMessages) }}</strong> user &middot;
                            <strong>{{ number_format($assistantMessages) }}</strong> AI</div>
                        <div class="progress" style="height:18px;border-radius:9px">
                            <div class="progress-bar" style="width:{{ $pct }}%;background:#4f46e5;font-size:11px">
                                {{ $pct }}%</div>
                            <div class="progress-bar" style="width:{{ 100 - $pct }}%;background:#10b981;font-size:11px">
                                {{ 100 - $pct }}%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top users & vendors --}}
        <div class="row mb-3">
            <div class="col-lg-6 mb-2">
                <div class="card">
                    <div class="card-header "><strong>Top Users</strong></div>
                    <table class="table table-hover mb-0">
                        <tbody>
                            @forelse($topUsers->take(5) as $u)
                                <tr>
                                    <td>{{ $topUserNames[$u->user_id] ?? "#$u->user_id" }}</td>
                                    <td class="text-right"><span class="badge badge-info">{{ $u->msg_count }}</span></td>
                                    <td class="text-right"><a
                                            href="{{ route('admin.ai-chat.logs.detail', ['user', $u->user_id]) }}">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-muted">No data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-6 mb-2">
                <div class="card">
                    <div class="card-header "><strong>Top Vendors</strong></div>
                    <table class="table table-hover mb-0">
                        <tbody>
                            @forelse($topVendors->take(5) as $v)
                                <tr>
                                    <td>{{ $topVendorNames[$v->vendor_id] ?? "#$v->vendor_id" }}</td>
                                    <td class="text-right"><span class="badge badge-success">{{ $v->msg_count }}</span>
                                    </td>
                                    <td class="text-right"><a
                                            href="{{ route('admin.ai-chat.logs.detail', ['vendor', $v->vendor_id]) }}">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-muted">No data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        new Chart(document.getElementById('dailyChart'), {
            type: 'line',
            data: {
                labels: {!! json_encode($dailyMessages->pluck('date')) !!},
                datasets: [{
                        label: 'User',
                        data: {!! json_encode($dailyMessages->pluck('user_msgs')) !!},
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79,70,229,0.08)',
                        fill: true,
                        tension: .3,
                        pointRadius: 2,
                        borderWidth: 2
                    },
                    {
                        label: 'AI',
                        data: {!! json_encode($dailyMessages->pluck('ai_msgs')) !!},
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.08)',
                        fill: true,
                        tension: .3,
                        pointRadius: 2,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
        var hd = @json($hourlyDistribution),
            hrs = Array.from({
                length: 24
            }, (_, i) => i);
        new Chart(document.getElementById('hourlyChart'), {
            type: 'bar',
            data: {
                labels: hrs.map(h => h.toString().padStart(2, '0') + ':00'),
                datasets: [{
                    data: hrs.map(h => hd[h] || 0),
                    backgroundColor: 'rgba(79,70,229,0.5)',
                    borderRadius: 3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 9
                            }
                        }
                    }
                }
            }
        });
        var td = @json($typeBreakdown),
            tl = Object.keys(td).map(k => k[0].toUpperCase() + k.slice(1)),
            tc = ['#4f46e5', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6'];
        new Chart(document.getElementById('typeChart'), {
            type: 'doughnut',
            data: {
                labels: tl.length ? tl : ['No data'],
                datasets: [{
                    data: Object.values(td).length ? Object.values(td) : [1],
                    backgroundColor: tl.length ? tc.slice(0, tl.length) : ['#e5e7eb']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    </script>
@endpush
