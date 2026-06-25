@extends('layouts.vendor.app')

@section('title', 'Branch Stock')

@push('css_or_js')
    @include('posretail::vendor.retail-pos._styles')
    <style>
        .loc-cards { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:16px; }
        .loc-card {
            flex:1; min-width:170px; max-width:260px; background:#f7f9fc; border:1px solid #e8eaf0;
            border-radius:10px; padding:12px 14px; text-decoration:none; color:#1a1a2e;
            transition:.15s; box-shadow:0 1px 2px rgba(0,0,0,.03);
        }
        .loc-card.lc-all    { background:#eef4ff; border-color:#d4e2fb; }  /* soft blue   */
        .loc-card.lc-main   { background:#f1eefc; border-color:#e1daf6; }  /* soft violet */
        .loc-card.lc-branch { background:#eafaf1; border-color:#d3f0df; }  /* soft green  */
        .loc-card:hover { box-shadow:0 3px 10px rgba(0,0,0,.09); transform:translateY(-1px); }
        .loc-card.active { border-color:#0f3460; box-shadow:0 0 0 2px rgba(15,52,96,.18); }
        .loc-card .lc-name { font-size:12px; font-weight:700; color:#0f3460; text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px; }
        .loc-card .lc-rem { font-size:24px; font-weight:700; color:#1b7a43; line-height:1; }
        .loc-card .lc-sub { font-size:10px; color:#8a8fa8; text-transform:uppercase; letter-spacing:.5px; margin:2px 0 9px; }
        .loc-card .lc-stats { display:flex; gap:12px; font-size:11px; color:#555; flex-wrap:wrap; }
        .loc-card .lc-stats b { font-weight:700; }
        .loc-card .lc-stats .dmg b { color:#b9770e; }
        .loc-card .lc-stats .thf b { color:#c0392b; }
        .loc-card .lc-view { margin-top:8px; font-size:11px; font-weight:600; color:#0f3460; }
        .loc-card.active .lc-view { color:#1b7a43; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid rp">
        <div class="rp-head">
            <div>
                <h1>Branch Stock</h1>
                <div class="sub">Tap a location card to see its full stock breakdown (total, sold, damaged, theft, remaining).</div>
            </div>
            @if (auth('vendor')->check() || hasPermission('pos_branch_stock', 'edit'))
                <a href="{{ route('vendor.retail-pos.gatepass') }}" class="rp-btn p">➕ Transfer Stock</a>
            @endif
            <form method="get" class="rp-filter">
                <input type="hidden" name="branch" value="{{ $sel }}">
                <input type="text" name="q" value="{{ $search }}" class="rp-input" placeholder="Search item / SKU">
                <button class="rp-btn o">Search</button>
            </form>
        </div>

        @php $fmt = fn($n) => rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.'); @endphp

        {{-- Location summary cards (click to drill into a breakdown) --}}
        <div class="loc-cards">
            @foreach ($cards as $c)
                @php $cls = $c['key'] === 'all' ? 'lc-all' : ($c['key'] === 'main' ? 'lc-main' : 'lc-branch'); @endphp
                <a class="loc-card {{ $cls }} {{ $c['active'] ? 'active' : '' }}"
                    href="{{ route('vendor.retail-pos.branch-stock', array_filter(['branch' => $c['key'], 'q' => $search])) }}">
                    <div class="lc-name">{{ $c['key'] === 'main' ? '🏬 ' : '' }}{{ $c['name'] }}</div>
                    <div class="lc-rem">{{ $fmt($c['remaining']) }}</div>
                    <div class="lc-sub">{{ $c['key'] === 'all' ? 'total remaining' : 'remaining' }}</div>
                    <div class="lc-stats">
                        <span>Sold <b>{{ $fmt($c['sold']) }}</b></span>
                        <span class="dmg">Dmg <b>{{ $fmt($c['damaged']) }}</b></span>
                        <span class="thf">Theft <b>{{ $fmt($c['theft']) }}</b></span>
                    </div>
                    <div class="lc-view">{{ $c['active'] ? '● Viewing' : ($c['key'] === 'all' ? 'View overview →' : 'View breakdown →') }}</div>
                </a>
            @endforeach
        </div>

        @if ($allMode)
            <div class="rp-card">
                <div class="hd"><span class="accent">Stock across all branches (current)</span></div>
                <div class="table-responsive">
                    <table class="rp-table">
                        <thead>
                            <tr>
                                <th>Item</th><th>SKU</th>
                                <th class="text-right">Main store</th>
                                @foreach ($branches as $b)
                                    <th class="text-right">{{ $b->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $it)
                                <tr>
                                    <td><b>{{ $it->item_name }}</b></td>
                                    <td class="text-muted">{{ $it->sku_id }}</td>
                                    <td class="text-right text-muted">{{ $fmt($it->stock) }}</td>
                                    @foreach ($branches as $b)
                                        @php $bs = $matrix[$it->id][$b->id] ?? 0; @endphp
                                        <td class="text-right">
                                            <b style="{{ $bs <= 0 ? 'color:#b0b4c0;font-weight:normal;' : '' }}">{{ $fmt($bs) }}</b>
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="{{ 3 + $branches->count() }}"><div class="rp-empty">No products.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            @php
                $sum = ['total' => 0, 'sold' => 0, 'damaged' => 0, 'theft' => 0, 'remaining' => 0];
                foreach ($detail as $d) { foreach ($sum as $k => $v) { $sum[$k] += $d[$k]; } }
            @endphp
            <div class="rp-card">
                <div class="hd"><span class="accent">{{ $locationName }} — stock breakdown</span></div>
                <div class="table-responsive">
                    <table class="rp-table">
                        <thead>
                            <tr>
                                <th>Item</th><th>SKU</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Sold</th>
                                <th class="text-right">Damaged</th>
                                <th class="text-right">Theft</th>
                                <th class="text-right">Remaining</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $it)
                                @php $d = $detail[$it->id] ?? ['total' => 0, 'sold' => 0, 'damaged' => 0, 'theft' => 0, 'remaining' => 0]; @endphp
                                <tr>
                                    <td><b>{{ $it->item_name }}</b></td>
                                    <td class="text-muted">{{ $it->sku_id }}</td>
                                    <td class="text-right"><b>{{ $fmt($d['total']) }}</b></td>
                                    <td class="text-right"><span style="color:#1565c0;">{{ $fmt($d['sold']) }}</span></td>
                                    <td class="text-right"><span style="color:{{ $d['damaged'] > 0 ? '#b9770e' : '#b0b4c0' }};">{{ $fmt($d['damaged']) }}</span></td>
                                    <td class="text-right"><span style="color:{{ $d['theft'] > 0 ? '#c0392b' : '#b0b4c0' }};">{{ $fmt($d['theft']) }}</span></td>
                                    <td class="text-right"><b style="color:{{ $d['remaining'] > 0 ? '#1b7a43' : '#c0392b' }};">{{ $fmt($d['remaining']) }}</b></td>
                                </tr>
                            @empty
                                <tr><td colspan="7"><div class="rp-empty">No products.</div></td></tr>
                            @endforelse
                        </tbody>
                        @if ($items->count())
                            <tfoot>
                                <tr style="border-top:2px solid #e8eaf0;font-weight:700;">
                                    <td colspan="2">Total</td>
                                    <td class="text-right">{{ $fmt($sum['total']) }}</td>
                                    <td class="text-right" style="color:#1565c0;">{{ $fmt($sum['sold']) }}</td>
                                    <td class="text-right" style="color:#b9770e;">{{ $fmt($sum['damaged']) }}</td>
                                    <td class="text-right" style="color:#c0392b;">{{ $fmt($sum['theft']) }}</td>
                                    <td class="text-right" style="color:#1b7a43;">{{ $fmt($sum['remaining']) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                <div class="bd text-muted" style="font-size:12px;">
                    <b>Total</b> = Remaining + Sold + Damaged + Theft (qty accounted at this location).
                    Sold counts finalized POS bills{{ $mainMode ? ' billed without a branch' : ' billed at this branch' }}.
                </div>
            </div>
        @endif
    </div>
@endsection
