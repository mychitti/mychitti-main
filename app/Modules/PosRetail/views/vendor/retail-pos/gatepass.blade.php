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
            @php
                $isOwner = auth('vendor')->check();
                $canTransfer = $isOwner || hasPermission('pos_gatepass', 'create');
                $canDelete = $isOwner || hasPermission('pos_gatepass', 'delete');
            @endphp

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
                                        <th width="200">Deduct from</th>
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
                                                @if (count($it->variations))
                                                    <select name="source[{{ $it->id }}]" class="rp-input" style="width:190px">
                                                        <option value="">Main stock</option>
                                                        @foreach ($it->variations as $v)
                                                            <option value="{{ $v['type'] }}">{{ $v['type'] }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <span class="text-muted">Main stock</span>
                                                @endif
                                            </td>
                                            <td>
                                                <input type="number" step="0.001" min="0" max="{{ (float) $it->stock }}"
                                                    name="qty[{{ $it->id }}]" class="rp-input rp-gp-qty" style="width:140px"
                                                    placeholder="0">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5"><div class="rp-empty">No products.</div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if (count($items))
                            <div class="bd d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
                                <div class="text-muted" style="font-size:12px;">
                                    The branch receives the main product, and the quantity always comes out of
                                    main-store stock. On an item with variations, pick which one is going so the
                                    gatepass records it.
                                </div>
                                <button class="rp-btn p" onclick="return confirm('Transfer the entered quantities to the selected branch? This deducts main-store stock.')">
                                    Transfer &amp; Generate Gatepass
                                </button>
                            </div>
                        @endif
                    </div>
                </form>
            @endif

            <form method="post" action="{{ route('vendor.retail-pos.gatepass.delete') }}" id="gp-delete-form"
                onsubmit="return gpConfirmDelete();">
                @csrf
                <div class="rp-card">
                    <div class="hd d-flex justify-content-between align-items-center">
                        <span class="accent">Recent Gatepasses</span>
                        @if ($canDelete && $gatepasses->count())
                            <button type="submit" class="btn btn-sm btn-outline-danger" id="gp-delete-btn" disabled>
                                🗑 Delete Selected (<span id="gp-sel-count">0</span>)
                            </button>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="rp-table">
                            <thead>
                                <tr>
                                    @if ($canDelete)
                                        <th width="36" class="text-center"><input type="checkbox" id="gp-check-all"></th>
                                    @endif
                                    <th>Gatepass #</th><th>To Branch</th><th>Note</th><th>Date</th><th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($gatepasses as $g)
                                    <tr>
                                        @if ($canDelete)
                                            <td class="text-center">
                                                <input type="checkbox" class="gp-check" name="ids[]" value="{{ $g->id }}">
                                            </td>
                                        @endif
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
                                    <tr><td colspan="{{ $canDelete ? 6 : 5 }}"><div class="rp-empty">No transfers yet.</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>

            @if ($canDelete)
                <script>
                    (function () {
                        var all = document.getElementById('gp-check-all');
                        var boxes = Array.prototype.slice.call(document.querySelectorAll('.gp-check'));
                        var btn = document.getElementById('gp-delete-btn');
                        var count = document.getElementById('gp-sel-count');

                        function refresh() {
                            var sel = boxes.filter(function (b) { return b.checked; }).length;
                            if (count) count.textContent = sel;
                            if (btn) btn.disabled = sel === 0;
                            if (all) all.checked = sel > 0 && sel === boxes.length;
                        }
                        if (all) all.addEventListener('change', function () {
                            boxes.forEach(function (b) { b.checked = all.checked; });
                            refresh();
                        });
                        boxes.forEach(function (b) { b.addEventListener('change', refresh); });
                    })();

                    function gpConfirmDelete() {
                        var sel = document.querySelectorAll('.gp-check:checked').length;
                        if (sel === 0) { return false; }
                        return confirm('Delete ' + sel + ' selected gatepass(es)? This returns the transferred stock to the main store and removes it from the branch.');
                    }
                </script>
            @endif
        @endif
    </div>
@endsection
