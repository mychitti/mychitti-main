@extends('layouts.vendor.app')

@section('title', 'Retail POS Dashboard')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    @include('posretail::vendor.retail-pos._styles')
    <style>
        .dash-kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:16px; }
        @media(max-width:991px){ .dash-kpis{ grid-template-columns:repeat(2,1fr); } }
        @media(max-width:575px){ .dash-kpis{ grid-template-columns:1fr; } }
        .dash-kpi { position:relative; overflow:hidden; background:#fff; border:1px solid var(--line); border-radius:14px; padding:15px 16px 15px 18px; display:flex; align-items:center; gap:13px; box-shadow:0 1px 3px rgba(16,24,40,.05); transition:.16s; }
        .dash-kpi::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--g); }
        .dash-kpi:hover { transform:translateY(-2px); box-shadow:0 10px 22px rgba(16,24,40,.10); }
        .dash-kpi .ic { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:21px; flex:0 0 auto; background:var(--g); color:#fff; box-shadow:0 4px 10px var(--sh); }
        .dash-kpi .v { font-size:21px; font-weight:800; line-height:1.05; color:#1f2733; }
        .dash-kpi .l { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; margin-top:3px; font-weight:600; }
        /* white cards with vibrant gradient icon + accent */
        .t-blue{--g:linear-gradient(135deg,#4f7cf0,#3aa0e8);--sh:rgba(79,124,240,.35)}
        .t-mint{--g:linear-gradient(135deg,#22c39a,#15a8c0);--sh:rgba(34,195,154,.35)}
        .t-amber{--g:linear-gradient(135deg,#ff9d2e,#f5683b);--sh:rgba(245,104,59,.35)}
        .t-pink{--g:linear-gradient(135deg,#f76aa0,#ec5f7e);--sh:rgba(236,95,126,.35)}
        .t-violet{--g:linear-gradient(135deg,#8a5cf0,#6a3fd6);--sh:rgba(138,92,240,.35)}
        .t-sky{--g:linear-gradient(135deg,#39b6e8,#1fc7c0);--sh:rgba(57,182,232,.35)}
        .t-rose{--g:linear-gradient(135deg,#fb6f7e,#e0436a);--sh:rgba(224,67,106,.35)}
        .t-grey{--g:linear-gradient(135deg,#9aa6b6,#76828f);--sh:rgba(118,130,143,.30)}
        .dash-grid { display:grid; gap:16px; }
        .dash-grid.g73 { grid-template-columns:1.6fr 1fr; }
        .dash-grid.g55 { grid-template-columns:1fr 1fr; }
        @media(max-width:991px){ .dash-grid.g73,.dash-grid.g55{ grid-template-columns:1fr; } }
        .chart-box { position:relative; height:260px; }
        .chart-box.sm { height:230px; }
        .rp-mini { font-size:11px; color:var(--muted); }
    </style>
@endpush

@section('content')
    <div class="content container-fluid rp">
        @php
            $days = [];
            for ($i = 13; $i >= 0; $i--) {
                $d = \Carbon\Carbon::today()->subDays($i);
                $days[$d->format('d M')] = (float) ($trend[$d->toDateString()] ?? 0);
            }
        @endphp

        <div class="rp-head">
            <div>
                <h1>Retail POS Dashboard</h1>
                <div class="sub">{{ $from }} → {{ $to }}{{ $branch ? ' · ' . optional($branches->firstWhere('id', $branch))->name : '' }}</div>
            </div>
            <form method="get" class="rp-filter date-range-form" action="{{ route('vendor.retail-pos.dashboard') }}">
                @if ($branches->count())
                    <select name="branch" class="rp-input" onchange="this.form.submit()">
                        <option value="">All branches</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" {{ $branch == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                @endif
                @include('vendor-views.form_modals.date_range')
                <button type="button" class="btn btn-sm btn-outline-warning" data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
                @if (hasPermission('pos_billing', 'create'))
                    <a href="{{ route('vendor.retail-pos.index') }}" class="rp-btn p">+ New Sale</a>
                @endif
            </form>
        </div>

        <div class="dash-kpis">
            <div class="dash-kpi t-violet"><div class="ic"><i class="tio-poll"></i></div><div><div class="v">₹{{ number_format((float) $stats['sales'], 2) }}</div><div class="l">Sales</div></div></div>
            <div class="dash-kpi t-blue"><div class="ic"><i class="tio-receipt"></i></div><div><div class="v">{{ $stats['bills'] }}</div><div class="l">Bills</div></div></div>
            <div class="dash-kpi t-sky"><div class="ic"><i class="tio-chart-bar-4"></i></div><div><div class="v">₹{{ number_format((float) $stats['avg'], 2) }}</div><div class="l">Avg Bill</div></div></div>
            <div class="dash-kpi t-mint"><div class="ic"><i class="tio-percent"></i></div><div><div class="v">₹{{ number_format((float) $stats['tax'], 2) }}</div><div class="l">GST Collected</div></div></div>
            <div class="dash-kpi t-amber"><div class="ic"><i class="tio-wallet"></i></div><div><div class="v">₹{{ number_format((float) $creditOutstanding, 2) }}</div><div class="l">Credit Due</div></div></div>
            <div class="dash-kpi t-rose"><div class="ic"><i class="tio-clear-circle"></i></div><div><div class="v">{{ $stats['voids'] }}</div><div class="l">Voided</div></div></div>
            <div class="dash-kpi {{ $inv['out'] ? 't-rose' : 't-grey' }}"><div class="ic"><i class="tio-remove-from-cart"></i></div><div><div class="v">{{ $inv['out'] }}</div><div class="l">Out of Stock</div></div></div>
            <div class="dash-kpi {{ $inv['low'] || $inv['expiring'] ? 't-amber' : 't-grey' }}"><div class="ic"><i class="tio-warning"></i></div><div><div class="v">{{ $inv['low'] }} / {{ $inv['expiring'] }}</div><div class="l">Low / Expiring</div></div></div>
        </div>

        <div class="dash-grid g73">
            <div class="rp-card">
                <div class="hd"><span class="accent">Sales — last 14 days</span><span class="rp-mini">₹{{ number_format(array_sum($days), 0) }}</span></div>
                <div class="bd"><div class="chart-box"><canvas id="chTrend"></canvas></div></div>
            </div>
            <div class="rp-card">
                <div class="hd"><span class="accent">Payment modes</span></div>
                <div class="bd"><div class="chart-box sm"><canvas id="chPay"></canvas></div></div>
            </div>
        </div>

        <div class="dash-grid g55">
            @if ($branchSales->count())
                <div class="rp-card">
                    <div class="hd"><span class="accent">Branch performance</span></div>
                    <div class="bd"><div class="chart-box sm"><canvas id="chBranch"></canvas></div></div>
                </div>
            @endif
            <div class="rp-card">
                <div class="hd"><span class="accent">Top items</span></div>
                <table class="rp-table">
                    <thead><tr><th>Item</th><th class="text-right">Qty</th><th class="text-right">Sales</th></tr></thead>
                    <tbody>
                        @forelse ($topItems as $it)
                            <tr><td>{{ $it->name }}</td><td class="text-right">{{ rtrim(rtrim(number_format((float) $it->qty, 2), '0'), '.') }}</td><td class="text-right">₹{{ number_format((float) $it->amount, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="3"><div class="rp-empty">No sales in this period.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rp-card">
            <div class="hd"><span class="accent">Recent bills</span></div>
            <div class="table-responsive">
                <table class="rp-table">
                    <thead><tr><th>Invoice</th><th>Customer</th><th>Mode</th><th class="text-right">Amount</th><th>Time</th></tr></thead>
                    <tbody>
                        @forelse ($recent as $b)
                            <tr>
                                <td><b>{{ $b->invoice_id }}</b></td>
                                <td>{{ ($b->bill_to ? ($recentNames[$b->bill_to] ?? null) : null) ?: 'Walk-in' }}</td>
                                <td><span class="rp-badge muted">{{ $b->payment_method }}</span></td>
                                <td class="text-right">₹{{ number_format((float) $b->total_amount, 2) }}</td>
                                <td class="text-muted">{{ \Carbon\Carbon::parse($b->created_at)->format('h:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="rp-empty">No bills in this period.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    @include('vendor-views.js.date_range')
    <script>
        (function () {
            if (typeof Chart === 'undefined') return;
            Chart.defaults.color = '#8893a3';
            const soft = ['#8FB9FF', '#88D9C9', '#FFD08A', '#FF9FB0', '#C3A6F0', '#A8DDA8', '#FFB793', '#9BD0F0'];
            let theme = (getComputedStyle(document.documentElement).getPropertyValue('--primary') || '#754BFF').trim();
            if (!/^#?[0-9a-fA-F]{3,6}$/.test(theme)) theme = '#754BFF';
            if (theme[0] !== '#') theme = '#' + theme;
            function rgba(hex, a) { const m = hex.replace('#', ''); const n = parseInt(m.length === 3 ? m.replace(/./g, '$&$&') : m, 16); return `rgba(${(n >> 16) & 255},${(n >> 8) & 255},${n & 255},${a})`; }

            const t = document.getElementById('chTrend');
            if (t) new Chart(t, {
                type: 'line',
                data: { labels: @json(array_keys($days)), datasets: [{ data: @json(array_values($days)), borderColor: theme, backgroundColor: rgba(theme, .12), fill: true, tension: .4, borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#fff', pointBorderColor: theme }] },
                options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#f1f3f7' } }, x: { grid: { display: false } } }, maintainAspectRatio: false }
            });

            const pmLabels = @json($payModes->keys()).map(s => s.toUpperCase());
            const p = document.getElementById('chPay');
            if (p) new Chart(p, {
                type: 'doughnut',
                data: { labels: pmLabels, datasets: [{ data: @json($payModes->values()), backgroundColor: soft, borderWidth: 2, borderColor: '#fff' }] },
                options: { cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12, usePointStyle: true } } }, maintainAspectRatio: false }
            });

            const bc = document.getElementById('chBranch');
            if (bc) new Chart(bc, {
                type: 'bar',
                data: { labels: @json($branchSales->pluck('name')), datasets: [{ data: @json($branchSales->pluck('total')), backgroundColor: soft, borderRadius: 6, barThickness: 22 }] },
                options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, grid: { color: '#f1f3f7' } }, y: { grid: { display: false } } }, maintainAspectRatio: false }
            });
        })();
    </script>
@endpush
