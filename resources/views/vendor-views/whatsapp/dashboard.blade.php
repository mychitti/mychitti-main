@extends('layouts.vendor.app')

@section('title', 'WhatsApp Dashboard')

@push('css_or_js')
    @include('vendor-views.whatsapp.partials._ui')
    <style>
        .wd-chart { position:relative; width:100%; }
        .wd-chart canvas { max-width:100%; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
            <div>
                <h1 class="page-header-title mb-0"><i class="tio-chat"></i> WhatsApp Dashboard</h1>
                <span class="wa-sub">How your WhatsApp messages are performing.</span>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                @if ($connected)
                    <span class="wa-chip badge-soft-success">Sending from your own number</span>
                    <a href="{{ route('vendor.whatsapp.connect') }}" class="btn btn-sm btn--primary">
                        <i class="tio-send"></i> Send a message
                    </a>
                @else
                    {{-- Not a capability, an unfinished setup: there is no sending at all until the
                         vendor connects their own number. The MyChitti number is ours, and only
                         ever carries our alerts TO them. --}}
                    <span class="wa-chip badge-soft-secondary">Number not connected</span>
                    <a href="{{ route('vendor.whatsapp.connect') }}" class="btn btn-sm btn--primary">
                        <i class="tio-add-circle-outlined"></i> Connect number
                    </a>
                @endif
            </div>
        </div>

        {{-- Not connected is the one thing worth interrupting for: everything below stays empty
             until it is done, so the fix leads rather than hiding at the bottom of the page. --}}
        @if (!$connected)
            <div class="wa-card wa-col">
                <div class="wa-card-b d-flex align-items-center flex-wrap" style="gap:14px;">
                    <div class="wa-stat-ico badge-soft-primary"><i class="tio-add-circle-outlined"></i></div>
                    <div style="flex:1 1 280px;">
                        <div style="font-weight:700;font-size:14px;color:#1e293b;">Connect your own WhatsApp number</div>
                        <div class="wa-sub">
                            You cannot send anything to your customers until this is done. Connect your number to
                            send bills, reminders and campaigns under your own business name.
                        </div>
                    </div>
                    <a href="{{ route('vendor.whatsapp.connect') }}" class="btn btn-sm btn--primary text-nowrap">Get started</a>
                </div>
            </div>
        @endif

        {{-- ── Headline numbers ── --}}
        <div class="row">
            <div class="col-sm-6 col-lg-3 wa-col">
                <div class="wa-stat">
                    <div>
                        <div class="wa-stat-val">{{ number_format($stats['total']) }}</div>
                        <div class="wa-stat-lbl">Messages sent</div>
                    </div>
                    <div class="wa-stat-ico badge-soft-primary"><i class="tio-chat"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 wa-col">
                <div class="wa-stat">
                    <div>
                        <div class="wa-stat-val">{{ number_format($stats['delivered']) }}</div>
                        <div class="wa-stat-lbl">Delivered</div>
                    </div>
                    <div class="wa-stat-ico badge-soft-success"><i class="tio-checkmark-circle-outlined"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 wa-col">
                <div class="wa-stat">
                    <div>
                        <div class="wa-stat-val">{{ $stats['delivery_rate'] }}%</div>
                        <div class="wa-stat-lbl">Delivery rate</div>
                    </div>
                    <div class="wa-stat-ico badge-soft-info"><i class="tio-chart-bar-4"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 wa-col">
                <div class="wa-stat">
                    <div>
                        <div class="wa-stat-val">{{ number_format($stats['failed']) }}</div>
                        <div class="wa-stat-lbl">Failed</div>
                    </div>
                    <div class="wa-stat-ico badge-soft-danger"><i class="tio-warning"></i></div>
                </div>
            </div>
        </div>

        {{-- ── Volume + delivery mix ── --}}
        <div class="row">
            <div class="col-lg-8 wa-col">
                <div class="wa-card h-100">
                    <div class="wa-card-h">
                        <span>Messages over time</span>
                        <span class="wa-sub">Last 14 days</span>
                    </div>
                    <div class="wa-card-b">
                        @if (array_sum($chart['counts']) === 0)
                            <div class="wa-empty">
                                <i class="tio-chart-line"></i>
                                <div class="wa-empty-t">No messages in the last 14 days</div>
                                <div class="wa-empty-s">Your sending activity will appear here.</div>
                            </div>
                        @else
                            <div class="wd-chart" style="height:280px;"><canvas id="wdVolume"></canvas></div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4 wa-col">
                <div class="wa-card h-100">
                    <div class="wa-card-h">Delivery status</div>
                    <div class="wa-card-b">
                        @if ($stats['total'] === 0)
                            <div class="wa-empty">
                                <i class="tio-pie-chart"></i>
                                <div class="wa-empty-t">Nothing sent yet</div>
                                <div class="wa-empty-s">Send your first message to see the breakdown.</div>
                            </div>
                        @else
                            <div class="wd-chart" style="height:210px;"><canvas id="wdStatus"></canvas></div>
                            @if ($stats['failed'] > 0)
                                <div class="wa-note mt-3">
                                    <b>{{ number_format($stats['failed']) }} failed.</b>
                                    Open <b>Recent activity</b> below and hover the failed rows for Meta's reason.
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Activity: two views of the same story, tabbed rather than stacked ── --}}
        <div class="row">
            <div class="col-12 wa-col">
                <div class="wa-card">
                    <ul class="nav wa-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#wdRecent" role="tab">Recent activity</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#wdBreakdown" role="tab">
                                What was sent
                                @if (count($contextRows))
                                    <span class="wa-chip badge-soft-secondary ml-1">{{ count($contextRows) }}</span>
                                @endif
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="wdRecent" role="tabpanel">
                            @if ($recent->isEmpty())
                                <div class="wa-empty">
                                    <i class="tio-chat-outlined"></i>
                                    <div class="wa-empty-t">No messages to show</div>
                                    <div class="wa-empty-s">Everything you send appears here with its delivery status.</div>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table wa-table">
                                        <thead>
                                            <tr>
                                                <th>To</th>
                                                <th>What</th>
                                                <th>Status</th>
                                                <th class="text-right">When</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($recent as $m)
                                                @php
                                                    $d = preg_replace('/[^0-9]/', '', (string) $m->recipient);
                                                    $masked = strlen($d) >= 4 ? '••••' . substr($d, -4) : '—';
                                                    $st = strtolower($m->status ?? '');
                                                    $cls = in_array($st, ['read', 'delivered']) ? 'success' : ($st === 'failed' ? 'danger' : 'warning');
                                                @endphp
                                                <tr>
                                                    <td class="text-nowrap">{{ $masked }}</td>
                                                    <td class="text-muted">{{ $m->context ?: $m->type }}</td>
                                                    <td class="text-nowrap">
                                                        <span class="wa-chip badge-soft-{{ $cls }}">{{ ucfirst($m->status ?: '—') }}</span>
                                                        @if ($st === 'failed' && $m->error)
                                                            <i class="tio-info-outined text-danger" title="{{ $m->error }}"></i>
                                                        @endif
                                                    </td>
                                                    <td class="text-muted text-nowrap text-right">
                                                        {{ $m->sent_at ? \Illuminate\Support\Carbon::parse($m->sent_at)->diffForHumans() : '—' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        <div class="tab-pane fade" id="wdBreakdown" role="tabpanel">
                            @if (empty($contextRows))
                                <div class="wa-empty">
                                    <i class="tio-poll"></i>
                                    <div class="wa-empty-t">No activity yet</div>
                                    <div class="wa-empty-s">Once you send, this splits your traffic by type.</div>
                                </div>
                            @else
                                @php $ctxMax = max($contextRows); @endphp
                                <div class="wa-card-b">
                                    @foreach ($contextRows as $label => $count)
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:13px;">
                                                <span>{{ $label }}</span>
                                                <b>{{ number_format($count) }}</b>
                                            </div>
                                            {{-- Relative bars read faster than a column of numbers. --}}
                                            <div style="height:6px;border-radius:6px;background:#f1f3f7;overflow:hidden;">
                                                <span style="display:block;height:100%;background:#25d366;width:{{ $ctxMax ? round($count / $ctxMax * 100) : 0 }}%;"></span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            var volEl = document.getElementById('wdVolume');
            if (volEl) {
                new Chart(volEl.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: @json($chart['days']),
                        datasets: [{
                            label: 'Messages',
                            data: @json($chart['counts']),
                            borderColor: '#25D366',
                            backgroundColor: 'rgba(37, 211, 102, 0.12)',
                            fill: true,
                            borderWidth: 3,
                            tension: 0.4,
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f3f7' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            var stEl = document.getElementById('wdStatus');
            if (stEl) {
                var s = @json($chart['status']);
                new Chart(stEl.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Sent', 'Delivered', 'Failed'],
                        datasets: [{
                            data: [s.sent, s.delivered, s.failed],
                            backgroundColor: ['#94a3b8', '#22c55e', '#ef4444'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '64%',
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 }, padding: 14 } } }
                    }
                });
            }
        })();
    </script>
@endpush
