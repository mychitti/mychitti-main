@extends('layouts.vendor.app')

@section('title', 'Retail POS Dashboard')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    @include('posretail::vendor.retail-pos._styles')
    <style>
        .dash-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(165px,1fr)); gap:14px; margin-bottom:16px; }
        .dash-kpi { background:#fff; border:1px solid var(--line); border-radius:16px; padding:15px 16px; display:flex; align-items:center; gap:13px; box-shadow:0 1px 3px rgba(16,24,40,.05); transition:.15s; }
        .dash-kpi:hover { transform:translateY(-2px); box-shadow:0 10px 24px rgba(16,24,40,.08); }
        .dash-kpi .ic { width:46px; height:46px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:20px; flex:0 0 auto; }
        .dash-kpi .v { font-size:20px; font-weight:700; line-height:1.05; }
        .dash-kpi .l { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; margin-top:3px; }
        /* soft pastel tiles */
        .t-blue{background:#eaf1ff;color:#5b86e5}.t-mint{background:#e7f8f3;color:#3bb39b}.t-amber{background:#fff4e3;color:#e0922f}
        .t-pink{background:#ffeef1;color:#ec6a86}.t-violet{background:#f1ecfe;color:#8a6be0}.t-sky{background:#e9f5fe;color:#4aa3df}
        .t-rose{background:#fdeef0;color:#e0567a}.t-grey{background:#eef1f6;color:#7b8794}
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
            <div class="dash-kpi"><div class="ic t-violet"><i class="tio-poll"></i></div><div><div class="v">₹{{ number_format((float) $stats['sales'], 2) }}</div><div class="l">Sales</div></div></div>
            <div class="dash-kpi"><div class="ic t-blue"><i class="tio-receipt"></i></div><div><div class="v">{{ $stats['bills'] }}</div><div class="l">Bills</div></div></div>
            <div class="dash-kpi"><div class="ic t-sky"><i class="tio-chart-bar-4"></i></div><div><div class="v">₹{{ number_format((float) $stats['avg'], 2) }}</div><div class="l">Avg Bill</div></div></div>
            <div class="dash-kpi"><div class="ic t-mint"><i class="tio-percent"></i></div><div><div class="v">₹{{ number_format((float) $stats['tax'], 2) }}</div><div class="l">GST Collected</div></div></div>
            <div class="dash-kpi"><div class="ic t-amber"><i class="tio-wallet"></i></div><div><div class="v">₹{{ number_format((float) $creditOutstanding, 2) }}</div><div class="l">Credit Due</div></div></div>
            <div class="dash-kpi"><div class="ic t-rose"><i class="tio-clear-circle"></i></div><div><div class="v">{{ $stats['voids'] }}</div><div class="l">Voided</div></div></div>
            <div class="dash-kpi"><div class="ic {{ $inv['out'] ? 't-rose' : 't-grey' }}"><i class="tio-remove-from-cart"></i></div><div><div class="v">{{ $inv['out'] }}</div><div class="l">Out of Stock</div></div></div>
            <div class="dash-kpi"><div class="ic {{ $inv['low'] || $inv['expiring'] ? 't-amber' : 't-grey' }}"><i class="tio-warning"></i></div><div><div class="v">{{ $inv['low'] }} / {{ $inv['expiring'] }}</div><div class="l">Low / Expiring</div></div></div>
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
                                <td>{{ $b->customer_name ?? ($b->bill_to ? '#' . $b->bill_to : 'Walk-in') }}</td>
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
