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
                onsubmit="return confirm('Submit this write-off request for manager approval? Stock is not deducted until a manager accepts it.');">
                @csrf
                <div class="rp-card">
                    <div class="hd"><span class="accent">New Damaged / Theft Request</span></div>
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
                                    <option value="leaked">Leaked</option>
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
                        <button class="rp-btn p">Submit Request</button>
                    </div>
                </div>
            </form>
        @endif

        <div class="rp-card">
            <div class="hd"><span class="accent">Write-off Requests</span></div>
            <div class="table-responsive">
                <table class="rp-table">
                    <thead>
                        <tr>
                            <th>Item</th><th>Location</th><th>Type</th>
                            <th class="text-right">Qty</th><th>Status</th><th>Details</th><th>Date</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $r)
                            @php
                                $st = $r->status ?? 'pending';
                                $stMap = ['pending' => ['#fff4e5', '#b9770e'], 'accepted' => ['#e6f7ec', '#1b7a43'], 'rejected' => ['#fdecea', '#c0392b']];
                                [$sbg, $sfg] = $stMap[$st] ?? ['#eee', '#555'];
                                $disp = $dispositions[$r->id] ?? collect();
                                $dispLabels = ['return_supplier' => 'Return to Supplier', 'resell' => 'Convert to Resell', 'scrap' => 'Scrap'];
                            @endphp
                            <tr>
                                <td><b>{{ $r->item_name ?? '—' }}</b> <span class="text-muted">{{ $r->sku_id }}</span></td>
                                <td>{{ $r->branch_name ?? 'Main Store' }}</td>
                                <td>{{ ucfirst($r->type) }}</td>
                                <td class="text-right"><b>{{ $fmt($r->qty) }}</b></td>
                                <td><span style="background:{{ $sbg }};color:{{ $sfg }};padding:2px 8px;border-radius:10px;font-size:11px;">{{ ucfirst($st) }}</span></td>
                                <td class="text-muted" style="font-size:12px;">
                                    @if ($r->note)<div>📝 {{ $r->note }}</div>@endif
                                    @if ($r->manager_note)<div>👤 {{ $r->manager_note }}</div>@endif
                                    @foreach ($disp as $d)
                                        <div>• {{ $dispLabels[$d->disposition] ?? $d->disposition }}: {{ $fmt($d->qty) }}{{ $d->damage_category ? ' (' . $d->damage_category . ')' : '' }}@if ($d->attachment) — <a href="{{ asset('storage/app/public/writeoff/' . $d->attachment) }}" target="_blank">doc</a>@endif</div>
                                    @endforeach
                                </td>
                                <td class="text-muted">{{ \Carbon\Carbon::parse($r->created_at)->format('d M Y, h:i A') }}</td>
                                <td class="text-right">
                                    @if (!empty($r->can_approve))
                                        <button class="rp-btn p sm wo-accept" data-url="{{ route('vendor.retail-pos.writeoff.decide', $r->id) }}" data-qty="{{ $fmt($r->qty) }}" data-item="{{ $r->item_name }}">Accept</button>
                                        <button class="rp-btn o sm wo-reject" style="color:#c0392b;" data-url="{{ route('vendor.retail-pos.writeoff.decide', $r->id) }}" data-item="{{ $r->item_name }}">Reject</button>
                                    @endif
                                    @if ($canDelete && $st === 'pending')
                                        <form method="post" action="{{ route('vendor.retail-pos.writeoff.delete', $r->id) }}" style="display:inline"
                                            onsubmit="return confirm('Delete this pending request? Stock was never deducted, so nothing changes.');">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><div class="rp-empty">No write-off requests yet.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Accept modal — split dispositions --}}
    <div class="modal fade" id="woAcceptModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <form method="post" id="woAcceptForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="action" value="accept">
                    <div class="modal-header">
                        <h5 class="modal-title">Accept Write-off — <span id="woAcceptItem"></span></h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted" style="font-size:13px;">Total to dispose: <b id="woAcceptQty"></b>. Split across one or more dispositions — the quantities must add up to the total.</p>
                        <div id="woDispRows"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="woAddDisp">+ Add disposition</button>
                        <div class="mt-3">
                            <label class="fl">Manager note</label>
                            <textarea name="manager_note" class="form-control" rows="2" placeholder="Note while accepting…"></textarea>
                        </div>
                        <div class="mt-2 small">Allocated: <b id="woAllocated">0</b> / <span id="woAcceptQtyView"></span></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn--primary">Accept &amp; Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reject modal --}}
    <div class="modal fade" id="woRejectModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="post" id="woRejectForm">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Write-off — <span id="woRejectItem"></span></h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted" style="font-size:13px;">Rejecting returns the held quantity to normal inventory.</p>
                        <label class="fl">Manager note</label>
                        <textarea name="manager_note" class="form-control" rows="2" placeholder="Reason for rejection…"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject &amp; Return Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <datalist id="woDamageCats">
        @foreach ($damageCategories as $dc)
            <option value="{{ $dc }}">
        @endforeach
    </datalist>
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

            // ---- Accept (split dispositions) ----
            var woQty = 0, woIdx = 0;
            function woRowHtml(i) {
                return '<div class="wo-disp-row" style="border:1px solid #eee;border-radius:8px;padding:8px;margin-bottom:8px;">'
                    + '<div class="d-flex flex-wrap align-items-center" style="gap:8px;">'
                    + '<select name="disp[' + i + '][type]" class="form-control form-control-sm wo-disp-type" style="max-width:190px;">'
                    + '<option value="resell">Convert to Resell</option>'
                    + '<option value="return_supplier">Return to Supplier</option>'
                    + '<option value="scrap">Scrap</option>'
                    + '</select>'
                    + '<input type="number" step="0.001" min="0.001" name="disp[' + i + '][qty]" class="form-control form-control-sm wo-disp-qty" style="max-width:110px;" placeholder="Qty">'
                    + '<button type="button" class="btn btn-sm btn-outline-danger wo-disp-del">&times;</button>'
                    + '</div>'
                    + '<div class="wo-supplier-fields mt-2" style="display:none;">'
                    + '<input type="text" name="disp[' + i + '][damage_category]" list="woDamageCats" class="form-control form-control-sm" placeholder="Damage category (select or type)">'
                    + '<input type="text" name="disp[' + i + '][reason]" class="form-control form-control-sm mt-1" placeholder="Reason">'
                    + '<input type="file" name="disp_file[' + i + ']" class="form-control-file form-control-sm mt-1" accept="image/*,.pdf">'
                    + '</div></div>';
            }
            function woRecalc() {
                var t = 0;
                $('#woDispRows .wo-disp-qty').each(function () { t += parseFloat($(this).val()) || 0; });
                $('#woAllocated').text(Math.round(t * 1000) / 1000);
            }
            function woAddRow() { $('#woDispRows').append(woRowHtml(woIdx++)); }

            $(document).on('click', '.wo-accept', function () {
                $('#woAcceptForm').attr('action', $(this).data('url'));
                $('#woAcceptItem').text($(this).data('item') || '');
                woQty = parseFloat($(this).data('qty')) || 0;
                $('#woAcceptQty,#woAcceptQtyView').text($(this).data('qty'));
                $('#woDispRows').empty(); woIdx = 0; woAddRow(); woRecalc();
                $('#woAcceptModal').modal('show');
            });
            $('#woAddDisp').on('click', woAddRow);
            $(document).on('click', '.wo-disp-del', function () {
                if ($('#woDispRows .wo-disp-row').length > 1) { $(this).closest('.wo-disp-row').remove(); woRecalc(); }
            });
            $(document).on('input', '.wo-disp-qty', woRecalc);
            $(document).on('change', '.wo-disp-type', function () {
                $(this).closest('.wo-disp-row').find('.wo-supplier-fields').toggle($(this).val() === 'return_supplier');
            });
            $('#woAcceptForm').on('submit', function (e) {
                var t = 0; $('#woDispRows .wo-disp-qty').each(function () { t += parseFloat($(this).val()) || 0; });
                if (Math.abs(t - woQty) > 0.001) { e.preventDefault(); alert('Disposition quantities must total ' + woQty + '.'); }
            });

            // ---- Reject ----
            $(document).on('click', '.wo-reject', function () {
                $('#woRejectForm').attr('action', $(this).data('url'));
                $('#woRejectItem').text($(this).data('item') || '');
                $('#woRejectModal').modal('show');
            });
        });
    </script>
@endpush
