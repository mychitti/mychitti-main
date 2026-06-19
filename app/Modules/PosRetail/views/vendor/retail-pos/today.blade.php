@extends('layouts.vendor.app')

@section('title', "Today's Bills")

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    @include('posretail::vendor.retail-pos._styles')
    <style>
        /* allow the actions dropdown to escape the card/table clipping */
        .rp .rp-card, .rp .table-responsive { overflow: visible; }
        .rp .act-dd .dropdown-menu { border:1px solid var(--line); border-radius:11px; box-shadow:0 10px 28px rgba(16,24,40,.14); padding:6px; min-width:178px; }
        .rp .act-dd .dropdown-item { font-size:13px; padding:8px 12px; border-radius:7px; display:flex; align-items:center; gap:9px; }
        .rp .act-dd .dropdown-item:hover { background:var(--soft); }
        .rp .act-dd .dropdown-item i { font-size:15px; width:18px; text-align:center; color:var(--accent); }
        .rp .act-dd .dropdown-item.text-danger i { color:#dc3545; }
        .rp .act-dd form { margin:0; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid rp">
        @php
            $final = $bills->where('pos_status', 'final');
        @endphp
        <div class="rp-head">
            <div>
                <h1>Bills</h1>
                <div class="sub">{{ $from === $to ? \Carbon\Carbon::parse($from)->format('d M Y') : $from . ' → ' . $to }}{{ $branch ? ' · ' . optional($branches->firstWhere('id', $branch))->name : '' }}</div>
            </div>
            <form method="get" class="rp-filter date-range-form" action="{{ route('vendor.retail-pos.today') }}">
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

        <div class="rp-kpis">
            <div class="rp-kpi"><div class="v">₹{{ number_format((float) $final->sum('total_amount'), 2) }}</div><div class="l">Sales</div></div>
            <div class="rp-kpi"><div class="v">{{ $final->count() }}</div><div class="l">Bills</div></div>
            <div class="rp-kpi"><div class="v">₹{{ number_format((float) $final->sum('cash_amount'), 2) }}</div><div class="l">Cash</div></div>
            <div class="rp-kpi"><div class="v">₹{{ number_format((float) $final->sum('online_amount'), 2) }}</div><div class="l">UPI / Card / Wallet</div></div>
            <div class="rp-kpi"><div class="v">{{ $bills->where('pos_status', 'void')->count() }}</div><div class="l">Voided</div></div>
        </div>

        <div class="rp-card">
            <div class="hd"><span class="accent">All bills today</span></div>
            <div class="table-responsive">
                <table class="rp-table">
                    <thead>
                        <tr>
                            <th>#</th><th>Invoice</th><th>Customer</th><th class="text-right">Amount</th>
                            <th>Payment</th><th>Status</th><th>Time</th><th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bills as $k => $bill)
                            <tr @if ($bill->pos_status === 'void') style="opacity:.55" @endif>
                                <td>{{ $k + 1 }}</td>
                                <td><b>{{ $bill->pos_status === 'void' ? 'V' . $bill->invoice_id : $bill->invoice_id }}</b></td>
                                <td>{{ $bill->customer_name ?? ($bill->bill_to ? 'Customer #' . $bill->bill_to : 'Walk-in') }}</td>
                                <td class="text-right font-weight-bold">₹{{ number_format((float) $bill->total_amount, 2) }}</td>
                                <td><span class="rp-badge muted">{{ $bill->payment_method }}</span></td>
                                <td>
                                    @if ($bill->pos_status === 'void')
                                        <span class="rp-badge bad">Void</span>
                                    @elseif ($bill->payment_status === 'Partial')
                                        <span class="rp-badge info">Partial</span>
                                    @else
                                        <span class="rp-badge ok">Paid</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $bill->created_at->format('h:i A') }}</td>
                                <td class="text-right">
                                    @php $canPrint = hasPermission('pos_bills', 'print'); $canVoid = hasPermission('pos_bills', 'void'); @endphp
                                    @if ($canPrint || $canVoid)
                                    <div class="dropdown act-dd">
                                        <button type="button" class="rp-btn o sm" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" title="Actions"><i class="tio-menu-hamburger" style="font-size: 21px;"></i></button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            @if ($canPrint)
                                                <a class="dropdown-item" href="{{ route('vendor.retail-pos.thermal', $bill->id) }}" target="_blank"><i class="tio-print"></i> Thermal print</a>
                                                @if ($bill->pdf)
                                                    <a class="dropdown-item" href="{{ asset('storage/app/public/invoice') . '/' . $bill->pdf }}" target="_blank"><i class="tio-receipt"></i> A4 invoice</a>
                                                @endif
                                                <a class="dropdown-item" href="{{ route('vendor.retail-pos.email', $bill->id) }}"><i class="tio-send"></i> Email invoice</a>
                                                <a class="dropdown-item" href="{{ route('vendor.retail-pos.whatsapp', $bill->id) }}"><i class="tio-comment"></i> Send on WhatsApp</a>
                                            @endif
                                            @if ($bill->pos_status !== 'void' && $canVoid)
                                                @if ($canPrint)<div class="dropdown-divider"></div>@endif
                                                <form action="{{ route('vendor.retail-pos.void', $bill->id) }}" method="post"
                                                    onsubmit="return confirm('Void this invoice? Stock will be restored.');">
                                                    @csrf
                                                    <input type="hidden" name="reason" value="Voided from Today's Bills">
                                                    <button type="submit" class="dropdown-item text-danger"><i class="tio-delete"></i> Void bill</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><div class="rp-empty">No bills today yet.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    @include('vendor-views.js.date_range')
@endpush
