@extends('layouts.vendor.app')

@section('title', 'WhatsApp Dashboard')

@push('css_or_js')
    <style>
        .wd-stat { border:1px solid #eef0f4; border-radius:14px; padding:18px 20px; background:#fff; height:100%; }
        .wd-stat-val { font-size:26px; font-weight:800; line-height:1.1; color:#1e293b; }
        .wd-stat-lbl { font-size:12px; text-transform:uppercase; letter-spacing:.4px; color:#8a94a6; margin-top:4px; }
        .wd-stat-ico { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:19px; }
        .wd-card { border:1px solid #eef0f4; border-radius:14px; background:#fff; }
        .wd-card-h { padding:16px 20px; border-bottom:1px solid #f1f3f7; font-weight:700; font-size:14px; color:#1e293b; }
        .wd-card-b { padding:20px; }
        .wd-chip { font-size:11px; padding:3px 10px; border-radius:20px; font-weight:600; }
        .wd-empty { color:#8a94a6; font-size:13px; text-align:center; padding:26px 10px; }
        .wd-ctx-row { display:flex; justify-content:space-between; align-items:center; font-size:13px; padding:8px 0; border-bottom:1px dashed #eef0f4; }
        .wd-ctx-row:last-child { border-bottom:0; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
            <h1 class="page-header-title mb-0"><i class="tio-chat"></i> WhatsApp Dashboard</h1>
            <div class="d-flex" style="gap:8px;">
                @if ($connected)
                    <span class="wd-chip badge-soft-success align-self-center">Your number connected</span>
                @else
                    <span class="wd-chip badge-soft-secondary align-self-center">Using MyChitti number</span>
                @endif
                <a href="{{ route('vendor.whatsapp.connect') }}" class="btn btn-sm btn--primary">
                    <i class="tio-send"></i> Send / Connect
                </a>
            </div>
        </div>

        {{-- ── Stat cards ── --}}
        <div class="row" style="row-gap:16px;">
            <div class="col-sm-6 col-lg-3">
                <div class="wd-stat d-flex justify-content-between align-items-start">
                    <div>
                        <div class="wd-stat-val">{{ number_format($stats['total']) }}</div>
                        <div class="wd-stat-lbl">Messages (all time)</div>
                    </div>
                    <div class="wd-stat-ico badge-soft-primary"><i class="tio-chat"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="wd-stat d-flex justify-content-between align-items-start">
                    <div>
                        <div class="wd-stat-val">{{ $stats['delivery_rate'] }}%</div>
                        <div class="wd-stat-lbl">Delivery rate</div>
                    </div>
                    <div class="wd-stat-ico badge-soft-success"><i class="tio-checkmark-circle-outlined"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="wd-stat d-flex justify-content-between align-items-start">
                    <div>
                        <div class="wd-stat-val">{{ $stats['read_rate'] }}%</div>
                        <div class="wd-stat-lbl">Read rate</div>
                    </div>
                    <div class="wd-stat-ico badge-soft-info"><i class="tio-visible-outlined"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="wd-stat d-flex justify-content-between align-items-start">
                    <div>
                        <div class="wd-stat-val">{{ number_format($stats['failed']) }}</div>
                        <div class="wd-stat-lbl">Failed</div>
                    </div>
                    <div class="wd-stat-ico badge-soft-danger"><i class="tio-warning"></i></div>
                </div>
            </div>
        </div>

        <div class="row mt-3" style="row-gap:16px;">
            {{-- ── Volume line chart ── --}}
            <div class="col-lg-8">
                <div class="wd-card h-100">
                    <div class="wd-card-h">Messages — last 14 days</div>
                    <div class="wd-card-b">
                        @if (array_sum($chart['counts']) === 0)
                            <div class="wd-empty">No messages in the last 14 days yet.</div>
                        @else
                            <div style="height:280px;"><canvas id="wdVolume"></canvas></div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── Status doughnut ── --}}
            <div class="col-lg-4">
                <div class="wd-card h-100">
                    <div class="wd-card-h">Delivery status</div>
                    <div class="wd-card-b">
                        @if ($stats['total'] === 0)
                            <div class="wd-empty">Nothing sent yet.</div>
                        @else
                            <div style="height:220px;"><canvas id="wdStatus"></canvas></div>
                            <div class="d-flex justify-content-around mt-3" style="font-size:12px;">
                                <span><b>{{ number_format($stats['delivered']) }}</b> delivered</span>
                                <span><b>{{ number_format($stats['read']) }}</b> read</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-1" style="row-gap:16px;">
            {{-- ── Breakdown by type ── --}}
            <div class="col-lg-4">
                <div class="wd-card h-100">
                    <div class="wd-card-h">What was sent</div>
                    <div class="wd-card-b">
                        @forelse ($contextRows as $label => $count)
                            <div class="wd-ctx-row">
                                <span>{{ $label }}</span>
                                <b>{{ number_format($count) }}</b>
                            </div>
                        @empty
                            <div class="wd-empty">No activity yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ── Recent messages ── --}}
            <div class="col-lg-8">
                <div class="wd-card h-100">
                    <div class="wd-card-h">Recent messages</div>
                    <div class="wd-card-b" style="padding:0;">
                        @if ($recent->isEmpty())
                            <div class="wd-empty">No messages to show.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm mb-0" style="font-size:13px;">
                                    <thead>
                                        <tr class="text-muted">
                                            <th class="pl-3">To</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>When</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recent as $m)
                                            @php
                                                $d = preg_replace('/[^0-9]/', '', (string) $m->recipient);
                                                $masked = strlen($d) >= 4 ? '••••' . substr($d, -4) : '—';
                                                $st = strtolower($m->status ?? '');
                                                $cls = in_array($st, ['read','delivered']) ? 'success' : ($st === 'failed' ? 'danger' : 'warning');
                                            @endphp
                                            <tr>
                                                <td class="pl-3">{{ $masked }}</td>
                                                <td class="text-muted">{{ $m->context ?: $m->type }}</td>
                                                <td>
                                                    <span class="wd-chip badge-soft-{{ $cls }}">{{ ucfirst($m->status ?: '—') }}</span>
                                                    @if ($st === 'failed' && $m->error)
                                                        <i class="tio-info-outined text-danger" title="{{ $m->error }}"></i>
                                                    @endif
                                                </td>
                                                <td class="text-muted text-nowrap">{{ $m->sent_at ? \Illuminate\Support\Carbon::parse($m->sent_at)->diffForHumans() : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
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
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                    }
                });
            }

            var stEl = document.getElementById('wdStatus');
            if (stEl) {
                var s = @json($chart['status']);
                new Chart(stEl.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Sent', 'Delivered', 'Read', 'Failed'],
                        datasets: [{
                            data: [s.sent, s.delivered, s.read, s.failed],
                            backgroundColor: ['#94a3b8', '#38bdf8', '#22c55e', '#ef4444'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
                    }
                });
            }
        })();
    </script>
@endpush
