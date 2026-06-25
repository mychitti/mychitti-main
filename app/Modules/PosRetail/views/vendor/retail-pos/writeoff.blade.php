@extends('layouts.vendor.app')

@section('title', 'Damaged / Theft Stock')

@push('css_or_js')
    @include('posretail::vendor.retail-pos._styles')
@endpush

@section('content')
    <div class="content container-fluid rp">
        <div class="rp-head">
            <div>
                <h1>Damaged / Theft Stock</h1>
                <div class="sub">Remove unsellable stock (damaged or stolen) from the main store or a branch. Each entry is logged and reversible.</div>
            </div>
        </div>

        @php
            $isOwner = auth('vendor')->check();
            $canCreate = $isOwner || hasPermission('pos_writeoff', 'create');
            $canDelete = $isOwner || hasPermission('pos_writeoff', 'delete');
            $fmt = fn($n) => rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.');
        @endphp

        @if ($canCreate)
            <form method="post" action="{{ route('vendor.retail-pos.writeoff.store') }}"
                onsubmit="return confirm('Record this write-off? It deducts the quantity from stock.');">
                @csrf
                <div class="rp-card">
                    <div class="hd"><span class="accent">New Write-off</span></div>
                    <div class="bd">
                        <div class="d-flex flex-wrap" style="gap:12px;">
                            <div style="min-width:200px;">
                                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;color:#555;">Location</label>
                                <select name="branch_id" id="wo-branch" class="rp-input" style="min-width:200px;">
                                    <option value="">🏬 Main Store</option>
                                    @foreach ($branches as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="flex:1; min-width:260px;">
                                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;color:#555;">Item <span style="color:#c0392b">*</span></label>
                                <select name="inventory_item_id" id="wo-item" class="rp-input" required style="width:100%;">
                                    <option value="">Search item by name / SKU</option>
                                </select>
                            </div>
                            <div style="min-width:150px;">
                                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;color:#555;">Type <span style="color:#c0392b">*</span></label>
                                <select name="type" class="rp-input" required style="min-width:150px;">
                                    <option value="damaged">Damaged</option>
                                    <option value="theft">Theft</option>
                                </select>
                            </div>
                            <div style="min-width:140px;">
                                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;color:#555;">Quantity <span style="color:#c0392b">*</span></label>
                                <input type="number" step="0.001" min="0.001" name="qty" class="rp-input" style="width:140px" placeholder="0" required>
                            </div>
                            <div style="flex:1; min-width:200px;">
                                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;color:#555;">Note (optional)</label>
                                <input type="text" name="note" class="rp-input" style="width:100%;" placeholder="e.g. spoilt / expired / shoplifting">
                            </div>
                        </div>
                    </div>
                    <div class="bd text-right">
                        <button class="rp-btn p">Save Write-off</button>
                    </div>
                </div>
            </form>
        @endif

        <div class="rp-card">
            <div class="hd"><span class="accent">Recent Write-offs</span></div>
            <div class="table-responsive">
                <table class="rp-table">
                    <thead>
                        <tr>
                            <th>Item</th><th>Location</th><th>Type</th>
                            <th class="text-right">Qty</th><th>Note</th><th>Date</th>
                            @if ($canDelete)<th class="text-right">Action</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $r)
                            <tr>
                                <td><b>{{ $r->item_name ?? '—' }}</b> <span class="text-muted">{{ $r->sku_id }}</span></td>
                                <td>{{ $r->branch_name ?? 'Main Store' }}</td>
                                <td>
                                    @if ($r->type === 'theft')
                                        <span class="badge badge-soft" style="background:#fdecea;color:#c0392b;padding:2px 8px;border-radius:10px;font-size:11px;">Theft</span>
                                    @else
                                        <span class="badge badge-soft" style="background:#fff4e5;color:#b9770e;padding:2px 8px;border-radius:10px;font-size:11px;">Damaged</span>
                                    @endif
                                </td>
                                <td class="text-right"><b>{{ $fmt($r->qty) }}</b></td>
                                <td class="text-muted">{{ $r->note }}</td>
                                <td class="text-muted">{{ \Carbon\Carbon::parse($r->created_at)->format('d M Y, h:i A') }}</td>
                                @if ($canDelete)
                                    <td class="text-right">
                                        <form method="post" action="{{ route('vendor.retail-pos.writeoff.delete', $r->id) }}" style="display:inline"
                                            onsubmit="return confirm('Reverse this write-off and restore {{ $fmt($r->qty) }} to stock?');">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger">↩ Reverse</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canDelete ? 7 : 6 }}"><div class="rp-empty">No write-offs yet.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        $(function () {
            function woBadge(item) {
                var pos = parseFloat(item.stock_num) > 0;
                var color = pos ? '#1b7a43' : '#c0392b';
                var bg = pos ? '#e6f7ec' : '#fdecea';
                var name = $('<span>').text(item.name).html();
                return $(
                    '<span style="display:flex;justify-content:space-between;align-items:center;gap:8px;">' +
                        '<span>' + name + '</span>' +
                        '<span style="background:' + bg + ';color:' + color + ';padding:1px 8px;border-radius:10px;' +
                            'font-size:11px;font-weight:600;white-space:nowrap;">' +
                            item.loc + ': ' + item.stock + ' ' + (item.unit || '') +
                        '</span>' +
                    '</span>'
                );
            }

            function woResult(item) {
                if (!item.id) { return item.text; }
                return woBadge(item);
            }

            function woSelection(item) {
                if (!item.id || item.stock_num === undefined) { return item.text || item.name; }
                return woBadge(item);
            }

            var $item = $('#wo-item').select2({
                placeholder: 'Search item by name / SKU',
                allowClear: true,
                minimumInputLength: 1,
                width: '100%',
                escapeMarkup: function (m) { return m; },
                templateResult: woResult,
                templateSelection: woSelection,
                ajax: {
                    url: "{{ route('vendor.retail-pos.writeoff.items') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term, branch_id: $('#wo-branch').val() };
                    },
                    processResults: function (data) {
                        return { results: data.results || [] };
                    },
                    cache: false
                }
            });

            // Switching location changes which stock applies — clear the picked item.
            $('#wo-branch').on('change', function () {
                $item.val(null).trigger('change');
            });
        });
    </script>
@endpush
