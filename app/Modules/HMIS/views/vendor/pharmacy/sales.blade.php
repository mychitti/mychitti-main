@extends('layouts.vendor.app')
@section('title', 'Pharmacy — Sales')

@push('css_or_js')
<style>
    .ps-kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:16px; }
    .ps-kpi { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; }
    .ps-kpi .lbl { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; }
    .ps-kpi .val { font-size:22px; font-weight:800; color:#0f172a; margin-top:4px; line-height:1.2; }
    .ps-kpi .sub { font-size:11px; color:#64748b; margin-top:2px; }

    .ps-grid { display:grid; grid-template-columns:1.4fr 1fr; gap:16px; align-items:start; }
    .ps-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; margin-bottom:16px; }
    .ps-card-hd { padding:12px 16px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; }
    .ps-card-hd h3 { font-size:13px; font-weight:700; color:#0f172a; margin:0; }

    .ps-tbl { width:100%; border-collapse:collapse; }
    .ps-tbl th { background:#f8fafc; color:#64748b; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; padding:9px 16px; text-align:left; border-bottom:1px solid #e2e8f0; }
    .ps-tbl td { padding:11px 16px; border-bottom:1px solid #f1f5f9; font-size:13px; vertical-align:middle; }
    .ps-tbl tr:last-child td { border-bottom:none; }
    .ps-num { text-align:right; font-variant-numeric:tabular-nums; }

    /* The daily figures are a list with a proportional bar rather than a chart library — it reads
       at a glance, prints, and adds nothing to the page weight. */
    .ps-bar-track { background:#f1f5f9; border-radius:4px; height:6px; overflow:hidden; margin-top:5px; }
    .ps-bar-fill { background:#3b82f6; height:100%; border-radius:4px; }

    .ps-empty { text-align:center; color:#94a3b8; padding:28px 16px; font-size:13px; }
    .ps-range { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
    .ps-range .btn { font-size:11.5px; padding:5px 11px; border-radius:7px; }

    @media (max-width: 991px) {
        .ps-kpis { grid-template-columns:repeat(2,1fr); }
        .ps-grid { grid-template-columns:1fr; }
    }
    @media (max-width: 575.98px) {
        .pharmacy-page-content { padding:12px !important; }
        .ps-kpis { gap:10px; }
        .ps-kpi { padding:11px 12px; }
        .ps-kpi .val { font-size:18px; }

        /* Same restack as the other pharmacy tables: label/value pairs from data-label. */
        .ps-tbl, .ps-tbl tbody, .ps-tbl tr, .ps-tbl td { display:block; width:100%; }
        .ps-tbl thead { display:none; }
        .ps-tbl tr { border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; margin:0 12px 10px; }
        .ps-tbl td { border:none !important; padding:4px 0 !important; display:flex; justify-content:space-between; gap:12px; text-align:right; }
        .ps-tbl td::before { content:attr(data-label); font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#94a3b8; text-align:left; flex:0 0 auto; }
        .ps-tbl td[colspan]{ display:block; text-align:center; }
        .ps-tbl td[colspan]::before { content:none; }
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    @include('hmis::vendor-views.partials._pharmacy_header')

    <div class="pharmacy-page-content">

        @php
            $cur = \App\CentralLogics\Helpers::currency_symbol() ?: '₹';
            $money = fn($v) => $cur . number_format((float) $v, 2);
            // The busiest day sets the bar scale, so the shape of the week is readable even when
            // every day is small in absolute terms.
            $peak = (float) ($daily->max('amount') ?: 0);
        @endphp

        {{-- Range picker. Presets cover what gets asked for; the two date boxes are there for
             the month-end question that never fits a preset. --}}
        <form method="GET" class="ps-card" style="padding:12px 16px; margin-bottom:16px;">
            <div class="ps-range">
                @foreach (['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days', '90d' => 'Last 90 days'] as $key => $label)
                    <a href="{{ route('vendor.pharmacy.sales', ['range' => $key]) }}"
                       class="btn btn-sm {{ !request()->filled('from') && $preset === $key ? 'btn--primary' : 'btn-outline-secondary' }}">
                        {{ $label }}
                    </a>
                @endforeach

                <span class="text-muted ml-2" style="font-size:11.5px;">or</span>
                <input type="date" name="from" value="{{ request('from', $from->toDateString()) }}"
                       class="form-control form-control-sm" style="width:auto; min-width:140px;">
                <input type="date" name="to" value="{{ request('to', $to->toDateString()) }}"
                       class="form-control form-control-sm" style="width:auto; min-width:140px;">
                <button type="submit" class="btn btn-sm btn--primary">Apply</button>
            </div>
            <div class="text-muted mt-2" style="font-size:11.5px;">
                Showing {{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}
            </div>
        </form>

        <div class="ps-kpis">
            <div class="ps-kpi">
                <div class="lbl">Revenue</div>
                <div class="val">{{ $money($stats['revenue']) }}</div>
                <div class="sub">Medicines only</div>
            </div>
            <div class="ps-kpi">
                <div class="lbl">Items sold</div>
                <div class="val">{{ rtrim(rtrim(number_format($stats['items_sold'], 2), '0'), '.') }}</div>
                <div class="sub">Units across all sales</div>
            </div>
            <div class="ps-kpi">
                <div class="lbl">Sales</div>
                <div class="val">{{ number_format($stats['sales']) }}</div>
                <div class="sub">Bills carrying medicines</div>
            </div>
            <div class="ps-kpi">
                <div class="lbl">Average sale</div>
                <div class="val">{{ $money($stats['avg_sale']) }}</div>
                <div class="sub">Medicine value per bill</div>
            </div>
        </div>

        <div class="ps-grid">
            <div>
                {{-- Top sellers --}}
                <div class="ps-card">
                    <div class="ps-card-hd"><h3>Top selling medicines</h3><span class="text-muted" style="font-size:11px;">By value</span></div>
                    <table class="ps-tbl">
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th class="ps-num" style="width:110px;">Qty sold</th>
                                <th class="ps-num" style="width:140px;">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topItems as $it)
                                <tr>
                                    <td data-label="Medicine" style="font-weight:600; color:#0f172a;">{{ $it->name }}</td>
                                    <td data-label="Qty sold" class="ps-num">{{ rtrim(rtrim(number_format((float) $it->qty, 2), '0'), '.') }}</td>
                                    <td data-label="Value" class="ps-num" style="font-weight:700;">{{ $money($it->amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><div class="ps-empty">Nothing sold in this period.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Recent sales --}}
                <div class="ps-card">
                    <div class="ps-card-hd"><h3>Recent sales</h3><span class="text-muted" style="font-size:11px;">Latest 25</span></div>
                    <table class="ps-tbl">
                        <thead>
                            <tr>
                                <th>Bill</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th class="ps-num" style="width:80px;">Qty</th>
                                <th class="ps-num" style="width:120px;">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recent as $r)
                                @php
                                    // meta is a JSON column; a walk-in carries the customer typed at
                                    // the counter, anything else is a patient bill.
                                    $meta = is_array($r->meta) ? $r->meta : (json_decode((string) $r->meta, true) ?: []);
                                    $who  = trim((string) ($meta['customer_name'] ?? '')) ?: null;
                                    $src  = ($meta['source'] ?? null) === 'pharmacy_walkin' ? 'Walk-in' : 'Patient bill';
                                @endphp
                                <tr>
                                    <td data-label="Bill">
                                        <a href="{{ route('vendor.invoice.view-invoice', $r->id) }}" style="font-weight:600;">{{ $r->invoice_id }}</a>
                                        <div class="text-muted" style="font-size:11px;">{{ $src }}</div>
                                    </td>
                                    <td data-label="Date">{{ $r->invoice_date ? \Carbon\Carbon::parse($r->invoice_date)->format('d M Y') : '—' }}</td>
                                    <td data-label="Customer">{{ $who ?: '—' }}</td>
                                    <td data-label="Qty" class="ps-num">{{ rtrim(rtrim(number_format((float) $r->qty, 2), '0'), '.') }}</td>
                                    <td data-label="Value" class="ps-num" style="font-weight:700;">{{ $money($r->amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="ps-empty">No sales in this period.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Day by day --}}
            <div>
                <div class="ps-card">
                    <div class="ps-card-hd"><h3>Day by day</h3><span class="text-muted" style="font-size:11px;">{{ $daily->count() }} days</span></div>
                    <div style="padding:12px 16px;">
                        @forelse ($daily->sortByDesc('d') as $d)
                            <div style="margin-bottom:12px;">
                                <div class="d-flex justify-content-between" style="font-size:12.5px;">
                                    <span style="color:#475569;">{{ \Carbon\Carbon::parse($d->d)->format('d M, D') }}</span>
                                    <span style="font-weight:700; color:#0f172a;">{{ $money($d->amount) }}</span>
                                </div>
                                <div class="ps-bar-track">
                                    <div class="ps-bar-fill" style="width: {{ $peak > 0 ? max(2, round(((float) $d->amount / $peak) * 100)) : 0 }}%;"></div>
                                </div>
                                <div class="text-muted" style="font-size:10.5px; margin-top:2px;">
                                    {{ rtrim(rtrim(number_format((float) $d->qty, 2), '0'), '.') }} units
                                </div>
                            </div>
                        @empty
                            <div class="ps-empty">No sales in this period.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
