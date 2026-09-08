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
                <div class="sub">Move stock from the main store to a branch, or between two branches. Branch stock can only be added here.</div>
            </div>
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
                $canEdit = $isOwner || hasPermission('pos_gatepass', 'edit') || $canTransfer;
                $editing = $editing ?? null;
                $editLines = $editLines ?? [];
                $editFromId = $editing ? (string) ($editing->from_branch_id ?? '') : '';
                $editToId = $editing ? (string) $editing->branch_id : '';
            @endphp

            @if ($canTransfer || $editing)
                <form method="post"
                    action="{{ $editing ? route('vendor.retail-pos.gatepass.update', $editing->id) : route('vendor.retail-pos.gatepass.store') }}">
                    @csrf
                    <div class="rp-card">
                        <div class="hd d-flex justify-content-between align-items-center">
                            <span class="accent">{{ $editing ? 'Edit Gatepass — ' . $editing->gatepass_no : 'New Transfer' }}</span>
                            @if ($editing)
                                <a class="btn btn-sm btn-outline-secondary"
                                    href="{{ route('vendor.retail-pos.gatepass') }}">Cancel edit</a>
                            @endif
                        </div>
                        @if ($editing)
                            <div class="bd" style="padding-bottom:0;">
                                <div class="alert alert-warning" style="font-size:12px;margin-bottom:0;">
                                    Saving undoes this transfer and re-applies it as entered below. Stock at both
                                    locations lands exactly where it would have if the gatepass had always read this
                                    way, and <b>{{ $editing->gatepass_no }}</b> stays the same number.
                                </div>
                            </div>
                        @endif
                        <div class="bd">
                            <div class="d-flex flex-wrap" style="gap:12px;">
                                @if ($hasSource ?? false)
                                    <div style="min-width:220px;">
                                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;color:#555;">From <span style="color:#c0392b">*</span></label>
                                        <select name="from_branch_id" id="gp-from-branch" class="rp-input" style="min-width:220px;">
                                            <option value="">Main Store</option>
                                            @foreach ($branches as $b)
                                                <option value="{{ $b->id }}" @selected($editFromId === (string) $b->id)>{{ $b->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div style="min-width:220px;">
                                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;color:#555;">To Branch <span style="color:#c0392b">*</span></label>
                                    <select name="branch_id" id="gp-to-branch" class="rp-input" required style="min-width:220px;">
                                        <option value="">Select branch</option>
                                        @foreach ($branches as $b)
                                            <option value="{{ $b->id }}" @selected($editToId === (string) $b->id)>{{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="flex:1; min-width:240px;">
                                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;color:#555;">Note (optional)</label>
                                    <input type="text" name="note" class="rp-input" style="width:100%;"
                                        value="{{ $editing->note ?? '' }}"
                                        placeholder="e.g. vehicle no / remarks">
                                </div>
                            </div>

                            {{-- The transfer is built one line at a time, like a bill: search,
                                 pick, and the row appears below. --}}
                            <div style="margin-top:12px;">
                                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;color:#555;">Add item</label>
                                <select id="gp-item-picker" style="width:100%;"></select>
                                <small class="text-muted d-block" style="font-size:11px;margin-top:4px;">
                                    Search by item name or SKU — variation SKUs find their parent item too.
                                </small>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="rp-table">
                                <thead>
                                    <tr>
                                        <th>Item</th><th>SKU</th>
                                        <th class="text-right"><span id="gp-stock-head">Main store stock</span></th>
                                        <th width="200">Deduct from</th>
                                        <th width="160">Transfer qty</th>
                                        <th width="40"></th>
                                    </tr>
                                </thead>
                                <tbody id="gp-lines">
                                    <tr id="gp-empty-row">
                                        <td colspan="6">
                                            <div class="rp-empty">No items yet — search above to add the first one.</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="bd d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
                            <div class="text-muted" style="font-size:12px;" id="gp-foot-note">
                                The branch receives the main product, and the quantity always comes out of
                                main-store stock. On an item with variations, pick which one is going so the
                                gatepass records it — fill a quantity against each one you are sending.
                            </div>
                            <button class="rp-btn p" id="gp-submit" disabled>
                                {{ $editing ? 'Save Changes & Reprint' : 'Transfer & Generate Gatepass' }}
                            </button>
                        </div>
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
                                    <th>Gatepass #</th><th>From</th><th>To Branch</th><th>Note</th><th>Date</th><th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($gatepasses as $g)
                                    <tr @class(['table-warning' => $editing && $editing->id == $g->id])>
                                        @if ($canDelete)
                                            <td class="text-center">
                                                <input type="checkbox" class="gp-check" name="ids[]" value="{{ $g->id }}">
                                            </td>
                                        @endif
                                        <td><b>{{ $g->gatepass_no }}</b></td>
                                        <td>{{ $g->from_branch_name ?? 'Main Store' }}</td>
                                        <td>{{ $g->branch_name ?? '—' }}</td>
                                        <td class="text-muted">{{ $g->note }}</td>
                                        <td class="text-muted">{{ \Carbon\Carbon::parse($g->created_at)->format('d M Y, h:i A') }}</td>
                                        <td class="text-right">
                                            @if ($canEdit)
                                                <a class="btn btn-sm btn-outline-primary"
                                                    href="{{ route('vendor.retail-pos.gatepass.edit', $g->id) }}">✎ Edit</a>
                                            @endif
                                            <a class="btn btn-sm btn-outline-warning" target="_blank"
                                                href="{{ route('vendor.retail-pos.gatepass.print', $g->id) }}">🖨 Print</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $canDelete ? 7 : 6 }}"><div class="rp-empty">No transfers yet.</div></td></tr>
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
                        return confirm('Delete ' + sel + ' selected gatepass(es)? Each transfer is reversed — the stock goes back to wherever it came from and is removed from the branch that received it.');
                    }

                </script>
            @endif

        @endif
    </div>
@endsection

{{-- Select2 and jQuery both arrive with the layout's vendor bundle, which loads after the
     content — so the picker has to be wired up here rather than inline above. --}}
@push('script_2')
    @if (auth("vendor")->check() || hasPermission("pos_gatepass", "create") || hasPermission("pos_gatepass", "edit") || ($editing ?? null))
                <script>
                    (function () {
                        var SEARCH_URL = '{{ route('vendor.retail-pos.gatepass.search') }}';
                        var PREFILL = @json($editLines ?? []);
                        var EDIT_NO = @json($editing->gatepass_no ?? '');
                        var $picker = $('#gp-item-picker');
                        var $lines  = $('#gp-lines');
                        var $from   = $('#gp-from-branch');
                        var $to     = $('#gp-to-branch');

                        // Each pool is one line, and the two cells are stacked in step so a
                        // variation's label sits beside its own quantity box.
                        $('<style>.gp-pool{min-height:56px;display:flex;flex-direction:column;'
                            + 'justify-content:center;}'
                            + '.gp-pool + .gp-pool{border-top:1px solid #eef1f5;}</style>')
                            .appendTo('head');

                        function esc(s) {
                            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                            });
                        }

                        function fromId()   { return ($from.val() || '').trim(); }
                        function fromName() { return fromId() ? $from.find('option:selected').text().trim() : 'Main Store'; }

                        $picker.select2({
                            placeholder: 'Search item name or SKU…',
                            allowClear: false,
                            minimumInputLength: 1,
                            ajax: {
                                url: SEARCH_URL,
                                dataType: 'json',
                                delay: 250,
                                data: function (params) { return { q: params.term, from_branch_id: fromId() }; },
                                processResults: function (data) {
                                    return {
                                        results: (data.results || []).map(function (it) {
                                            return {
                                                id: it.id,
                                                text: it.item_name + (it.sku_id ? ' — ' + it.sku_id : ''),
                                                item: it
                                            };
                                        })
                                    };
                                },
                                cache: true
                            },
                            templateResult: function (o) {
                                if (!o.item) { return o.text; }
                                return $(
                                    '<div><b>' + esc(o.item.item_name) + '</b>'
                                    + (o.item.sku_id ? ' <span class="text-muted">' + esc(o.item.sku_id) + '</span>' : '')
                                    + '<div class="text-muted" style="font-size:11px;">In stock: '
                                    + esc(o.item.stock_text) + ' ' + esc(o.item.unit)
                                    + (o.item.last_sent ? ' &middot; ' + esc(o.item.last_sent) : '')
                                    + '</div></div>'
                                );
                            }
                        });

                        // Each pool carries its own ceiling in ITS OWN units, because a quantity box
                        // counts whatever pool it belongs to — packs for a measured variation, base
                        // units for main stock.
                        //
                        // gatepassParseLines() splits on '-var-', so the pool a line draws on is
                        // carried by the quantity field's own name. That is what lets one item send
                        // several of its variations at once: source[itemId] could only ever hold a
                        // single answer per item.
                        function qtyName(itemId, varType) {
                            return 'qty[' + itemId + (varType ? '-var-' + varType : '') + ']';
                        }

                        function rowsFor(itemId) {
                            return $lines.find('tr[data-item="' + itemId + '"]');
                        }

                        // Compared, not interpolated into a selector: a variation type is whatever
                        // the vendor typed, and a quote in it would break an attribute selector.
                        function qtyInput($row, varType) {
                            return $row.find('.rp-gp-qty').filter(function () {
                                return ($(this).attr('data-var') || '') === (varType || '');
                            });
                        }

                        // Is main stock a pool in its own right on this item? Only on a loose
                        // measured product, where the packs are filled from it — the same rule
                        // _variationSelectionError() applies server-side. On a countable item the
                        // main figure is the SUM of the variations, not a pool, so offering it here
                        // would be offering a source the submit is bound to refuse.
                        function hasMainPool(it) {
                            if (!it.variations || !it.variations.length) { return true; }
                            return it.var_mode === 'measured' && Number(it.sell_loose) === 1;
                        }

                        // Every pool the operator may send from, in the order they are shown.
                        function poolsOf(it) {
                            var pools = [];
                            if (hasMainPool(it)) {
                                pools.push({
                                    varType: '',
                                    label: fromId() ? fromName() : 'Main stock',
                                    max: it.stock,
                                    hint: it.unit
                                });
                            }
                            (it.variations || []).forEach(function (v) {
                                pools.push({ varType: v.type, label: v.type, max: v.max, hint: v.hint || '' });
                            });
                            return pools;
                        }

                        function addRow(it, presetQty, presetSource) {
                            var $row = rowsFor(it.id);

                            // One row per item, every pool stacked inside it. Searching the same
                            // item again fills the row that is already there rather than adding a
                            // second one — an operator sending three variations should not have to
                            // find the item three times.
                            if ($row.length) {
                                if (presetQty != null && presetQty !== '') {
                                    qtyInput($row, presetSource || '').val(presetQty);
                                } else {
                                    $row.find('.rp-gp-qty').first().focus();
                                }
                                refresh();
                                return;
                            }

                            $('#gp-empty-row').remove();

                            var pools = poolsOf(it);
                            var srcHtml = '';
                            var qtyHtml = '';

                            pools.forEach(function (p) {
                                srcHtml += '<div class="gp-pool">' + esc(p.label) + '</div>';
                                qtyHtml += '<div class="gp-pool">'
                                    + '<input type="number" step="0.001" min="0" max="' + p.max + '"'
                                    + ' name="' + qtyName(it.id, p.varType) + '"'
                                    + ' data-var="' + esc(p.varType) + '"'
                                    + ' class="rp-input rp-gp-qty" style="width:130px" placeholder="0">'
                                    + '<small class="text-muted d-block" style="font-size:11px;">max '
                                    + esc(String(p.max)) + (p.hint ? ' ' + esc(p.hint) : '')
                                    + '</small></div>';
                            });

                            $lines.append(
                                '<tr data-item="' + it.id + '">'
                                + '<td><b>' + esc(it.item_name) + '</b>'
                                + (it.last_sent
                                    ? '<small class="text-muted d-block" style="font-size:11px;">' + esc(it.last_sent) + '</small>'
                                    : '<small class="text-muted d-block" style="font-size:11px;">Not transferred before</small>')
                                + '</td>'
                                + '<td class="text-muted">' + esc(it.sku_id) + '</td>'
                                + '<td class="text-right text-muted">' + esc(it.stock_text) + ' ' + esc(it.unit) + '</td>'
                                + '<td>' + srcHtml + '</td>'
                                + '<td>' + qtyHtml + '</td>'
                                + '<td><button type="button" class="rp-btn o gp-remove" title="Remove">&times;</button></td>'
                                + '</tr>'
                            );

                            $row = rowsFor(it.id);

                            if (presetQty != null && presetQty !== '') {
                                qtyInput($row, presetSource || '').val(presetQty);
                            } else {
                                $row.find('.rp-gp-qty').first().focus();
                            }

                            refresh();
                        }

                        var hasDestination = true;

                        function refresh() {
                            var rows = $lines.find('tr[data-item]').length;
                            $('#gp-submit').prop('disabled', rows === 0 || !hasDestination);
                            if (rows === 0 && !$('#gp-empty-row').length) {
                                $lines.append('<tr id="gp-empty-row"><td colspan="6">'
                                    + '<div class="rp-empty">No items yet — search above to add the first one.</div></td></tr>');
                            }
                        }

                        $picker.on('select2:select', function (e) {
                            addRow(e.params.data.item);
                            $picker.val(null).trigger('change');
                        });

                        $lines.on('click', '.gp-remove', function () {
                            $(this).closest('tr').remove();
                            refresh();
                        });

                        // Changing the source invalidates every line already picked — the ceilings,
                        // and on a branch even which items exist, come from the old pool. Clearing
                        // is the honest move; silently keeping rows would post quantities validated
                        // against stock that is somewhere else.
                        function onSourceChange() {
                            var id = fromId();

                            if ($lines.find('tr[data-item]').length) {
                                $lines.find('tr[data-item]').remove();
                            }

                            $('#gp-stock-head').text(id ? (fromName() + ' stock') : 'Main store stock');

                            // A branch cannot send to itself.
                            var open = 0;
                            $to.find('option').each(function () {
                                if (!this.value) { return; }
                                var same = id && this.value === id;
                                this.disabled = !!same;
                                if (same && $to.val() === this.value) { $to.val(''); }
                                if (!same) { open++; }
                            });
                            hasDestination = open > 0;

                            $('#gp-foot-note').text(!hasDestination
                                ? fromName() + ' is your only branch, so there is nowhere to send stock. '
                                  + 'Add a second branch, or transfer from the Main Store instead.'
                                : (id
                                    ? 'Stock moves straight from ' + fromName() + ' to the destination branch. '
                                      + 'The store total is unchanged and main-store stock is not touched.'
                                    : 'The branch receives the main product, and the quantity always comes out of '
                                      + 'main-store stock. On an item with variations, pick which one is going so '
                                      + 'the gatepass records it — fill a quantity against each one you are sending.'));

                            refresh();
                        }

                        $from.on('change', onSourceChange);

                        $('#gp-submit').closest('form').on('submit', function () {
                            if (fromId() && $to.val() === fromId()) {
                                alert('Source and destination branch cannot be the same.');
                                return false;
                            }
                            var dest = $to.find('option:selected').text().trim() || 'the selected branch';
                            if (EDIT_NO) {
                                return confirm('Re-issue ' + EDIT_NO + '? The original transfer is undone and '
                                    + 'replaced by the quantities entered here, from ' + fromName()
                                    + ' to ' + dest + '.');
                            }
                            return confirm('Transfer the entered quantities from ' + fromName()
                                + ' to ' + dest + '?');
                        });

                        onSourceChange();

                        // Seeded last: onSourceChange() clears every row by design, so anything
                        // added before this point would be wiped on the way in.
                        PREFILL.forEach(function (it) { addRow(it, it.edit_qty, it.edit_source); });

                        refresh();
                    })();
                </script>
    @endif
@endpush
