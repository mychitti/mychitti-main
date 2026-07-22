@extends('layouts.vendor.app')

@section('title', 'WhatsApp Dashboard')

@push('css_or_js')
    <style>
        /* Every card/stat shares one box so rows line up. margin-bottom on the column gives a
           uniform vertical gap; overflow:hidden keeps charts and tables inside the rounded card. */
        .wd-col { margin-bottom:16px; }
        .wd-stat, .wd-card { border:1px solid #eef0f4; border-radius:14px; background:#fff; height:100%; overflow:hidden; }
        .wd-stat { padding:18px 20px; display:flex; justify-content:space-between; align-items:flex-start; }
        .wd-stat-val { font-size:26px; font-weight:800; line-height:1.1; color:#1e293b; }
        .wd-stat-lbl { font-size:12px; text-transform:uppercase; letter-spacing:.4px; color:#8a94a6; margin-top:4px; }
        .wd-stat-ico { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:19px; flex-shrink:0; }
        .wd-card-h { padding:16px 20px; border-bottom:1px solid #f1f3f7; font-weight:700; font-size:14px; color:#1e293b; }
        .wd-card-b { padding:20px; }
        .wd-chart { position:relative; width:100%; }
        .wd-chart canvas { max-width:100%; }
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
        <div class="row">
            <div class="col-sm-6 col-lg-4 wd-col">
                <div class="wd-stat d-flex justify-content-between align-items-start">
                    <div>
                        <div class="wd-stat-val">{{ number_format($stats['total']) }}</div>
                        <div class="wd-stat-lbl">Messages (all time)</div>
                    </div>
                    <div class="wd-stat-ico badge-soft-primary"><i class="tio-chat"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4 wd-col">
                <div class="wd-stat d-flex justify-content-between align-items-start">
                    <div>
                        <div class="wd-stat-val">{{ $stats['delivery_rate'] }}%</div>
                        <div class="wd-stat-lbl">Delivery rate</div>
                    </div>
                    <div class="wd-stat-ico badge-soft-success"><i class="tio-checkmark-circle-outlined"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4 wd-col">
                <div class="wd-stat d-flex justify-content-between align-items-start">
                    <div>
                        <div class="wd-stat-val">{{ number_format($stats['failed']) }}</div>
                        <div class="wd-stat-lbl">Failed</div>
                    </div>
                    <div class="wd-stat-ico badge-soft-danger"><i class="tio-warning"></i></div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- ── Volume line chart ── --}}
            <div class="col-lg-8 wd-col">
                <div class="wd-card h-100">
                    <div class="wd-card-h">Messages — last 14 days</div>
                    <div class="wd-card-b">
                        @if (array_sum($chart['counts']) === 0)
                            <div class="wd-empty">No messages in the last 14 days yet.</div>
                        @else
                            <div class="wd-chart" style="height:280px;"><canvas id="wdVolume"></canvas></div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── Status doughnut ── --}}
            <div class="col-lg-4 wd-col">
                <div class="wd-card h-100">
                    <div class="wd-card-h">Delivery status</div>
                    <div class="wd-card-b">
                        @if ($stats['total'] === 0)
                            <div class="wd-empty">Nothing sent yet.</div>
                        @else
                            <div class="wd-chart" style="height:220px;"><canvas id="wdStatus"></canvas></div>
                            <div class="d-flex justify-content-around mt-3" style="font-size:12px;">
                                <span><b>{{ number_format($stats['delivered']) }}</b> delivered</span>
                                <span><b>{{ number_format($stats['failed']) }}</b> failed</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- ── Breakdown by type ── --}}
            <div class="col-lg-4 wd-col">
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
            <div class="col-lg-8 wd-col">
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

        {{-- ── Your customers (bulk-send audience) + Excel import ── --}}
        <div class="row">
            <div class="col-lg-5 wd-col">
                <div class="wd-card h-100">
                    <div class="wd-card-h d-flex justify-content-between align-items-center">
                        <span>Your customers</span>
                        <span class="wd-chip badge-soft-primary">{{ number_format($customerStats['with_phone']) }} with a phone</span>
                    </div>
                    <div class="wd-card-b">
                        <p class="text-muted" style="font-size:13px;">
                            These are your own customers — the audience for a bulk WhatsApp send.
                            Import a spreadsheet to add more in one go.
                        </p>

                        <form method="post" action="{{ route('vendor.whatsapp.customers.import') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="d-flex align-items-center flex-wrap mb-2" style="gap:8px;">
                                <input type="file" name="file" id="wdCustFile"
                                       class="form-control form-control-sm" style="flex:1 1 200px;min-width:0;"
                                       accept=".xlsx,.xls,.csv" required>
                                <button class="btn btn--primary btn-sm text-nowrap" type="submit">
                                    <i class="tio-upload"></i> Import
                                </button>
                            </div>
                            <small class="text-muted d-block">
                                Columns: <b>Name, Phone, Email, GST, Address</b> — only Name and Phone are required.
                                <a href="{{ route('vendor.whatsapp.customers.template') }}">Download template</a>.
                                Duplicates (same phone) are skipped automatically.
                            </small>
                        </form>

                        <div class="mt-3">
                            <a href="{{ route('vendor.whatsapp.connect') }}" class="btn btn-sm btn-outline-primary">
                                <i class="tio-send"></i> Send a bulk message
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 wd-col">
                <div class="wd-card h-100">
                    <div class="wd-card-h">Recently added customers</div>
                    <div class="wd-card-b" style="padding:0;">
                        @if ($recentCustomers->isEmpty())
                            <div class="wd-empty">No customers yet. Import a sheet to get started.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm mb-0" style="font-size:13px;">
                                    <thead>
                                        <tr class="text-muted">
                                            <th class="pl-3">Name</th>
                                            <th>Phone</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentCustomers as $c)
                                            <tr>
                                                <td class="pl-3">{{ $c->f_name ?: '—' }}</td>
                                                <td class="text-muted">{{ $c->phone ?: '—' }}</td>
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
                        cutout: '62%',
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
                    }
                });
            }
        })();
    </script>
@endpush
