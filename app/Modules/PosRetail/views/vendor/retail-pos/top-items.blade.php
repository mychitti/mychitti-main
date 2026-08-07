@extends('layouts.vendor.app')

@section('title', 'Top Selling Items')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    @include('posretail::vendor.retail-pos._styles')
    <style>
        .ti-bar { position:relative; height:6px; border-radius:4px; background:#eef1f6; overflow:hidden; min-width:70px; }
        .ti-bar span { position:absolute; left:0; top:0; bottom:0; border-radius:4px; background:var(--accent); }
    </style>
@endpush

@section('content')
    <div class="content container-fluid rp">
        <div class="rp-head">
            <div>
                <h1>Top Selling Items</h1>
                <div class="sub">{{ $from }} → {{ $to }}{{ $branch ? ' · ' . optional($branches->firstWhere('id', $branch))->name : '' }}</div>
            </div>
            <div class="d-flex align-items-center" style="gap:8px;">
                <a href="{{ route('vendor.retail-pos.dashboard', ['from' => $from, 'to' => $to, 'branch' => $branch, 'date_range' => $preset, 'custom_date_range' => $custom]) }}"
                    class="rp-btn o">← Dashboard</a>
                <a href="{{ route('vendor.retail-pos.top-items', ['from' => $from, 'to' => $to, 'branch' => $branch, 'sort' => $sort, 'export' => 'excel']) }}"
                    class="rp-btn p">⬇ Download Excel</a>
            </div>
        </div>

        <div class="rp-card">
            <div class="bd">
                <form method="get" class="rp-filter date-range-form" action="{{ route('vendor.retail-pos.top-items') }}">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    @if ($branches->count())
                        <select name="branch" class="rp-input" onchange="this.form.submit()">
                            <option value="">All branches</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ $branch == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    @include('vendor-views.form_modals.date_range')
                    <button type="button" class="btn btn-outline-warning btn-sm" data-toggle="modal" data-target="#dateRangeModal">
                        {{ translate($preset) }}
                    </button>
                </form>
            </div>
        </div>

        <div class="rp-card">
            <div class="hd">
                <span class="accent">{{ count($rows) }} items · {{ rtrim(rtrim(number_format($totalQty, 2), '0'), '.') }} sold · ₹{{ number_format($totalAmount, 2) }}</span>
                <span class="d-flex align-items-center" style="gap:6px;">
                    <a class="rp-btn {{ $sort === 'qty' ? 'p' : 'o' }} sm"
                        href="{{ route('vendor.retail-pos.top-items', ['from' => $from, 'to' => $to, 'branch' => $branch, 'sort' => 'qty']) }}">By Qty</a>
                    <a class="rp-btn {{ $sort === 'amount' ? 'p' : 'o' }} sm"
                        href="{{ route('vendor.retail-pos.top-items', ['from' => $from, 'to' => $to, 'branch' => $branch, 'sort' => 'amount']) }}">By Sales</a>
                </span>
            </div>
            <div class="table-responsive">
                <table class="rp-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>SKU</th>
                            <th class="text-right">Qty Sold</th>
                            <th class="text-right">Bills</th>
                            <th class="text-right">Sales</th>
                            <th class="text-right">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $key => $r)
                            @php $share = $totalAmount > 0 ? $r->amount / $totalAmount * 100 : 0; @endphp
                            <tr>
                                <td class="text-muted">{{ $key + 1 }}</td>
                                <td><b>{{ $r->name }}</b></td>
                                <td>
                                    @if (!empty($skus[$r->inv_id]))
                                        <span class="rp-badge muted">{{ $skus[$r->inv_id] }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-right">{{ rtrim(rtrim(number_format((float) $r->qty, 2), '0'), '.') }}</td>
                                <td class="text-right">{{ $r->bills }}</td>
                                <td class="text-right font-weight-bold">₹{{ number_format((float) $r->amount, 2) }}</td>
                                <td class="text-right">
                                    <div class="d-flex align-items-center justify-content-end" style="gap:8px;">
                                        <div class="ti-bar"><span style="width:{{ min(100, round($share, 2)) }}%"></span></div>
                                        <span class="text-muted">{{ number_format($share, 1) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="rp-empty">No sales in this period.</div></td></tr>
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
