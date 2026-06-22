@extends('layouts.vendor.app')

@section('title', 'Stock Transfer (Gatepass)')

@push('css_or_js')
    @include('posretail::vendor.retail-pos._styles')
@endpush

@section('content')
    <div class="content container-fluid rp">
        <div class="rp-head">
            <div>
                <h1>Stock Transfer — Gatepass</h1>
                <div class="sub">Move stock from the main store to a branch. Branch stock can only be added here.</div>
            </div>
            <form method="get" class="rp-filter">
                <input type="text" name="q" value="{{ $search }}" class="rp-input" placeholder="Search item / SKU">
                <button class="rp-btn o">Search</button>
            </form>
        </div>

        @if (!$branches->count())
            <div class="rp-card">
                <div class="rp-empty">Create a branch first under <b>Branches &amp; Counters</b>.</div>
            </div>
        @else
            @php $canTransfer = auth('vendor')->check() || hasPermission('pos_branch_stock', 'edit'); @endphp

            @if ($canTransfer)
                <form method="post" action="{{ route('vendor.retail-pos.gatepass.store') }}">
                    @csrf
                    <div class="rp-card">
                        <div class="hd">
                            <span class="accent">New Transfer</span>
                        </div>
                        <div class="bd">
                            <div class="d-flex flex-wrap" style="gap:12px;">
                                <div style="min-width:220px;">
                                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;color:#555;">To Branch <span style="color:#c0392b">*</span></label>
                                    <select name="branch_id" class="rp-input" required style="min-width:220px;">
                                        <option value="">Select branch</option>
                                        @foreach ($branches as $b)
                                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="flex:1; min-width:240px;">
                                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;color:#555;">Note (optional)</label>
                                    <input type="text" name="note" class="rp-input" style="width:100%;"
                                        placeholder="e.g. vehicle no / remarks">
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="rp-table">
                                <thead>
                                    <tr>
                                        <th>Item</th><th>SKU</th>
                                        <th class="text-right">Main store stock</th>
                                        <th width="160">Transfer qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($items as $it)
                                        <tr>
                                            <td><b>{{ $it->item_name }}</b></td>
                                            <td class="text-muted">{{ $it->sku_id }}</td>
                                            <td class="text-right text-muted">
                                                {{ rtrim(rtrim(number_format((float) $it->stock, 3), '0'), '.') }}
                                                {{ optional($it->itemunit)->unit }}
                                            </td>
                                            <td>
                                                <input type="number" step="0.001" min="0" max="{{ (float) $it->stock }}"
                                                    name="qty[{{ $it->id }}]" class="rp-input" style="width:140px"
                                                    placeholder="0">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4"><div class="rp-empty">No products.</div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if (count($items))
                            <div class="bd text-right">
                                <button class="rp-btn p" onclick="return confirm('Transfer the entered quantities to the selected branch? This deducts main-store stock.')">
                                    Transfer &amp; Generate Gatepass
                                </button>
                            </div>
                        @endif
                    </div>
                </form>
            @endif

            <div class="rp-card">
                <div class="hd"><span class="accent">Recent Gatepasses</span></div>
                <div class="table-responsive">
                    <table class="rp-table">
                        <thead>
                            <tr><th>Gatepass #</th><th>To Branch</th><th>Note</th><th>Date</th><th class="text-right">Action</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($gatepasses as $g)
                                <tr>
                                    <td><b>{{ $g->gatepass_no }}</b></td>
                                    <td>{{ $g->branch_name ?? '—' }}</td>
                                    <td class="text-muted">{{ $g->note }}</td>
                                    <td class="text-muted">{{ \Carbon\Carbon::parse($g->created_at)->format('d M Y, h:i A') }}</td>
                                    <td class="text-right">
                                        <a class="btn btn-sm btn-outline-warning" target="_blank"
                                            href="{{ route('vendor.retail-pos.gatepass.print', $g->id) }}">🖨 Print</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="rp-empty">No transfers yet.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
