@extends('layouts.vendor.app')
@section('title', 'Pharmacy — Medicines & Stock')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .pill { font-size:10px; font-weight:700; padding:3px 9px; border-radius:100px; }
        .pill.ok{background:#DCFCE7;color:#15803D}.pill.low{background:#FFFBEB;color:#92400E}.pill.out{background:#FFF1F2;color:#DC2626}.pill.exp{background:#F3E8FF;color:#7C3AED}
        .med-empty { text-align:center; color:#9aa1ab; padding:40px 16px; }

        /* Pool-first search box in the Add Medicine modal. */
        .mp-search-wrap { position:relative; }
        .mp-results {
            display:none; position:absolute; left:0; right:0; top:100%; z-index:1056;
            background:#fff; border:1px solid #e2e8f0; border-radius:8px; margin-top:2px;
            max-height:260px; overflow-y:auto; box-shadow:0 8px 24px rgba(15,23,42,.12);
        }
        .mp-opt { display:flex; align-items:center; gap:10px; padding:8px 11px; cursor:pointer; border-bottom:1px solid #f4f6f9; }
        .mp-opt:last-child { border-bottom:0; }
        .mp-opt:hover, .mp-opt.active { background:#f1f5f9; }
        .mp-opt img, .mp-thumb {
            width:34px; height:34px; border-radius:7px; object-fit:cover; flex-shrink:0;
            background:#f1f5f9; display:flex; align-items:center; justify-content:center;
            color:#94a3b8; font-size:14px;
        }
        .mp-opt-name { font-size:13px; font-weight:600; color:#0f172a; }
        .mp-opt-meta { font-size:11px; color:#64748b; }
        .mp-note { font-size:11px; color:#0891b2; font-weight:600; }
        .mp-empty { padding:10px 12px; font-size:12.5px; color:#64748b; }
        .mp-picked {
            display:flex; align-items:center; gap:10px; padding:9px 11px; margin-bottom:12px;
            border:1px solid #bbf7d0; background:#f0fdf4; border-radius:9px;
        }
        .mp-picked img { width:38px; height:38px; border-radius:8px; object-fit:cover; background:#fff; }

        /* The card header's action group is a non-wrapping flex row holding a 240px
           search box and three buttons — wider than a phone, so it pushed the page
           sideways and clipped Add Medicine. */
        @media (max-width: 767px) {
            .med-actions { flex-wrap: wrap; width: 100%; }
            .med-actions form { max-width: 100% !important; width: 100%; }
            .med-actions .btn { flex: 1 1 auto; justify-content: center; }
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">

        {{-- Full pharmacy nav (metrics + tabs) — Medicines & Stock is the default tab --}}
        @include('hmis::vendor-views.partials._pharmacy_header')

        <div class="pharmacy-page-content">
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between" style="gap:10px;">
                    <h3 class="mb-0" style="font-size:15px; font-weight:700;">Medicines &amp; Stock</h3>
                    <div class="d-flex align-items-center med-actions" style="gap:8px;">
                        <form action="" method="get" class="input-group input-group-sm mb-0" style="max-width:240px;">
                            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search medicine / SKU...">
                            <div class="input-group-append"><button class="btn btn-outline-secondary"><i class="tio-search"></i></button></div>
                        </form>
                        <a href="{{ route('vendor.pharmacy.catalog') }}" class="btn btn-sm btn-outline-primary" style="white-space:nowrap;">
                            <i class="tio-book-opened mr-1"></i> Add from Catalog
                        </a>
                        <a href="{{ route('vendor.pharmacy.medicines.export') }}" class="btn btn-sm btn-outline-secondary" style="white-space:nowrap;">
                            <i class="tio-download-to mr-1"></i> Export
                        </a>
                        @if (hasPermission('pharmacy', 'add'))
                            <button class="btn btn-sm btn-outline-primary" style="white-space:nowrap;" data-toggle="modal" data-target="#importMedModal">
                                <i class="tio-upload-on-cloud mr-1"></i> Import
                            </button>
                            <button class="btn btn-sm btn--primary" style="white-space:nowrap; font-weight:600;" data-toggle="modal" data-target="#addMedModal">
                                <i class="tio-add mr-1"></i> Add Medicine
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Medicine</th><th>Unit</th><th>MRP</th><th>Price</th><th>Stock</th><th>Reorder</th><th>Expiry</th><th>Status</th><th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    @php
                                        $unitName = $item->itemunit?->unit;
                                        $stock = (int) $item->stock;
                                        $reorder = (int) ($item->reorder_level ?? 0);
                                        $soon = now()->addDays(60)->toDateString();
                                        $isOut = $stock <= 0;
                                        $isLow = !$isOut && $reorder > 0 && $stock <= $reorder;
                                        $isExp = $item->expiry_date && $item->expiry_date <= $soon;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold">{{ $item->item_name }}
                                                @if (!empty($item->is_banned))
                                                    <span class="pill" style="background:#fee2e2;color:#b91c1c;" title="{{ $item->banned_reason ?: 'Banned / blocked item' }}">⛔ Banned</span>
                                                @endif
                                            </div>
                                            <div class="text-muted" style="font-size:11px;">{{ $item->brand }}{{ $item->sku_id ? ' · ' . $item->sku_id : '' }}</div>
                                        </td>
                                        <td>{{ $unitName ?: '—' }}</td>
                                        <td>{{ _price($item->mrp ?? 0) }}</td>
                                        <td>{{ _price($item->selling_price ?? 0) }}</td>
                                        <td><strong>{{ $stock }}</strong></td>
                                        <td>{{ $reorder ?: '—' }}</td>
                                        <td>{{ $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('d M Y') : '—' }}</td>
                                        <td>
                                            @if ($isOut)<span class="pill out">Out</span>
                                            @elseif ($isLow)<span class="pill low">Low</span>
                                            @elseif ($isExp)<span class="pill exp">Expiring</span>
                                            @else<span class="pill ok">In Stock</span>@endif
                                        </td>
                                        <td class="text-right">
                                            @if (hasPermission('pharmacy', 'edit'))
                                                <button class="btn btn-xs btn-outline-success add-stock-btn" data-id="{{ $item->id }}" data-name="{{ $item->item_name }}"><i class="tio-add"></i> Stock</button>
                                                <button class="btn btn-xs btn-outline-primary edit-med-btn"
                                                    data-id="{{ $item->id }}" data-name="{{ $item->item_name }}" data-brand="{{ $item->brand }}"
                                                    data-sku="{{ $item->sku_id }}" data-unit="{{ $unitName }}" data-mrp="{{ $item->mrp }}"
                                                    data-selling="{{ $item->selling_price }}" data-reorder="{{ $reorder }}" data-expiry="{{ $item->expiry_date }}">
                                                    <i class="tio-edit"></i>
                                                </button>
                                                @if (!empty($item->is_banned))
                                                    <a class="btn btn-xs btn-outline-secondary" href="{{ route('vendor.pharmacy.banned-items.delete', $item->id) }}"
                                                        onclick="return confirm('Un-ban {{ $item->item_name }}?')" title="Remove from banned list">Un-ban</a>
                                                @else
                                                    <form action="{{ route('vendor.pharmacy.banned-items.save') }}" method="post" class="d-inline" onsubmit="return confirm('Mark {{ $item->item_name }} as banned/blocked?')">
                                                        @csrf
                                                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                        <button class="btn btn-xs btn-outline-danger" title="Mark as banned">⛔ Ban</button>
                                                    </form>
                                                @endif
                                            @endif
                                            @if (hasPermission('pharmacy', 'delete'))
                                                <a class="btn btn-xs btn-outline-danger" href="{{ route('vendor.pharmacy.medicines.delete', $item->id) }}"
                                                    onclick="return confirm('Remove this medicine?')"><i class="tio-delete"></i></a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9"><div class="med-empty">
                                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" style="width:90px;opacity:.8;" alt="">
                                        <div class="mt-2">No medicines yet. Click <strong>+ Add Medicine</strong> to add an item, then use <strong>+ Stock</strong> on its row to enter stock.</div>
                                    </div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── Upgrade teaser (compact strip — only for stores without the paid Inventory plan) ── --}}
            @if (!vendorPlanHasModule('inventory_manage'))
                <div class="card">
                    <div style="padding:10px 16px; display:flex; align-items:center; flex-wrap:wrap; gap:8px 14px;">
                        <span style="font-size:12.5px; font-weight:600; color:#475569; white-space:nowrap;">
                            <i class="tio-diamond mr-1" style="color:#1565C0;"></i> Paid add-ons:
                        </span>
                        @php
                            $paidFeatures = ['Purchase Orders', 'Sales & Returns', 'Gatepass', 'Batch & Expiry', 'Reports', 'Storage Units', 'Suppliers', 'Barcode & Labels'];
                        @endphp
                        @foreach ($paidFeatures as $f)
                            <span style="font-size:11px; color:#64748B; background:#F1F5F9; padding:2px 9px; border-radius:100px;">{{ $f }}</span>
                        @endforeach
                        <a href="{{ route('vendor.subscriptions') }}" style="font-size:12px; font-weight:600; color:#1565C0; white-space:nowrap; margin-left:auto;">Upgrade →</a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Add Medicine (item add) ───────────────────────────────────── --}}
    @if (hasPermission('pharmacy', 'add'))
        <div class="modal fade" id="addMedModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add Medicine</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <form action="{{ route('vendor.pharmacy.medicines.save') }}" method="post">
                    @csrf
                    <div class="modal-body">
                        {{-- Pool first: the shared catalog is the default way in, and typing a name
                             by hand is the fallback rather than the other way round. Picking a pooled
                             medicine fills the identity fields so the pharmacist only prices it. --}}
                        <input type="hidden" name="catalog_item_id" id="a_catalog_id">
                        <div class="form-group mp-search-wrap">
                            <label>Find in the shared medicine catalog</label>
                            <input type="text" id="mpSearch" class="form-control" autocomplete="off"
                                   placeholder="Type a medicine or brand — e.g. Pantoprazole, Pan 40">
                            <div id="mpResults" class="mp-results"></div>
                            <small class="text-muted">Not listed? Just fill the fields below — we will add it to the catalog for you.</small>
                        </div>

                        <div id="mpPicked" class="mp-picked" style="display:none;">
                            <img id="mpPickedImg" src="" alt="">
                            <div class="flex-grow-1">
                                <div id="mpPickedName" class="font-weight-bold"></div>
                                <div id="mpPickedMeta" class="text-muted" style="font-size:11.5px;"></div>
                            </div>
                            <button type="button" class="btn btn-xs btn-outline-secondary" id="mpClear">Change</button>
                        </div>

                        <div class="form-group"><label>Medicine Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="a_item_name" class="form-control" required></div>
                        <div class="form-row">
                            <div class="form-group col-4"><label>Brand</label><input type="text" name="brand" id="a_brand" class="form-control"></div>
                            <div class="form-group col-4"><label>Strength</label><input type="text" name="strength" id="a_strength" class="form-control" placeholder="40 mg"></div>
                            <div class="form-group col-4"><label>Type</label>
                                <select name="form" id="a_form" class="form-control">
                                    <option value="">— Select —</option>
                                    @foreach ($forms as $f)
                                        <option value="{{ $f }}">{{ $f }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group"><label>SKU / Code</label><input type="text" name="sku_id" class="form-control"></div>
                        <div class="form-row">
                            <div class="form-group col-4"><label>Unit <span class="text-danger">*</span></label>
                                <select name="unit" id="a_unit" class="form-control med-unit-select" required>
                                    <option value=""></option>
                                    @foreach ($units as $u)
                                        <option value="{{ $u }}">{{ $u }}</option>
                                    @endforeach
                                </select></div>
                            <div class="form-group col-4"><label>MRP (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="mrp" class="form-control" required></div>
                            <div class="form-group col-4"><label>Selling Price (₹)</label>
                                <input type="number" step="0.01" name="selling_price" class="form-control" placeholder="= MRP"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-4"><label>Opening Stock</label><input type="number" name="stock" class="form-control" value="0"></div>
                            <div class="form-group col-4"><label>Reorder Level</label><input type="number" name="reorder_level" class="form-control" value="0"></div>
                            <div class="form-group col-4"><label>Expiry Date</label><input type="date" name="expiry_date" class="form-control"></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button class="btn btn--primary">Add Medicine</button></div>
                </form>
            </div></div>
        </div>

        {{-- ── Import Medicines (CSV / Excel) ───────────────────────────── --}}
        <div class="modal fade" id="importMedModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Import Medicines</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <form action="{{ route('vendor.pharmacy.medicines.import') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>File (CSV or Excel) <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" accept=".csv,.txt,.xls,.xlsx" required>
                        </div>
                        <div class="alert alert-soft-info mb-0" style="font-size:12.5px;">
                            Columns: <strong>item_name, brand, strength, dosage_form, category, sku_id, unit, mrp, selling_price, stock, reorder_level, expiry_date</strong>.
                            Only <strong>item_name</strong> is required; <code>medicine_name</code> and
                            <code>brand_example</code> are recognised too, so a supplier sheet uploads as it is.
                            No <code>unit</code> column? It is taken from the pack — "10 tablets" sells as Tablet,
                            "20 ml vial" as Vial, "60 ml" as ml.
                            <a href="{{ asset('public/assets/pharmacy_medicines_format.csv') }}" download class="d-inline-block mt-2">
                                <i class="tio-download-to"></i> Download a sample sheet
                            </a>
                            Existing medicines are matched by SKU (or name) and updated; new ones are created.
                            <a href="{{ route('vendor.pharmacy.medicines.export') }}">Download current list</a> to use as a template.
                        </div>
                    </div>
                    <div class="modal-footer"><button class="btn btn--primary"><i class="tio-upload-on-cloud mr-1"></i> Import</button></div>
                </form>
            </div></div>
        </div>

        {{-- ── Edit Medicine (shared, JS-populated) ──────────────────── --}}
        <div class="modal fade" id="editMedModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Edit Medicine</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <form id="editMedForm" method="post">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group"><label>Medicine Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="e_name" class="form-control" required></div>
                        <div class="form-row">
                            <div class="form-group col-6"><label>Brand</label><input type="text" name="brand" id="e_brand" class="form-control"></div>
                            <div class="form-group col-6"><label>SKU / Code</label><input type="text" name="sku_id" id="e_sku" class="form-control"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-4"><label>Unit <span class="text-danger">*</span></label>
                                <select name="unit" id="e_unit" class="form-control med-unit-select" required>
                                    <option value=""></option>
                                    @foreach ($units as $u)
                                        <option value="{{ $u }}">{{ $u }}</option>
                                    @endforeach
                                </select></div>
                            <div class="form-group col-4"><label>MRP (₹) <span class="text-danger">*</span></label><input type="number" step="0.01" name="mrp" id="e_mrp" class="form-control" required></div>
                            <div class="form-group col-4"><label>Selling Price (₹)</label><input type="number" step="0.01" name="selling_price" id="e_selling" class="form-control"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-6"><label>Reorder Level</label><input type="number" name="reorder_level" id="e_reorder" class="form-control"></div>
                            <div class="form-group col-6"><label>Expiry Date</label><input type="date" name="expiry_date" id="e_expiry" class="form-control"></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button class="btn btn--primary">Save Changes</button></div>
                </form>
            </div></div>
        </div>

        {{-- ── Add Stock / Stock Entry (shared, JS-populated) ─────────── --}}
        <div class="modal fade" id="addStockModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-sm"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Stock Entry — <span id="s_name"></span></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <form id="addStockForm" method="post">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group"><label>Quantity to add <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="quantity" class="form-control" required></div>
                        <div class="form-group mb-0"><label>Expiry (optional)</label><input type="date" name="expiry_date" class="form-control"></div>
                    </div>
                    <div class="modal-footer"><button class="btn btn-success">Add Stock</button></div>
                </form>
            </div></div>
        </div>
    @endif
@endsection

@push('script_2')
    <script>
        // ── Pool-first medicine search ──────────────────────────────────────
        // The catalog lookup is the primary way to add a medicine; the fields below it stay
        // editable so an unlisted medicine can still be typed and is queued for the pool.
        (function () {
            const url     = "{{ route('vendor.pharmacy.medicines.pool-search') }}";
            const input   = document.getElementById('mpSearch');
            const box     = document.getElementById('mpResults');
            const picked  = document.getElementById('mpPicked');
            if (!input) return;

            let timer = null, lastQuery = '';

            const esc = t => (t || '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
            const hide = () => { box.style.display = 'none'; box.innerHTML = ''; };

            function render(rows) {
                if (!rows.length) {
                    box.innerHTML = '<div class="mp-empty">No match in the catalog — fill the fields below and we will add it.</div>';
                    box.style.display = 'block';
                    return;
                }
                box.innerHTML = rows.map(r => `
                    <div class="mp-opt" data-item='${esc(JSON.stringify(r))}'>
                        ${r.image ? `<img src="${esc(r.image)}" alt="">` : '<span class="mp-thumb"><i class="tio-medicine-bottle"></i></span>'}
                        <div class="flex-grow-1">
                            <div class="mp-opt-name">${esc(r.label)}</div>
                            <div class="mp-opt-meta">${esc(r.meta || r.brand || 'Generic')}${r.in_stock ? ' · <span class="mp-note">already in your pharmacy</span>' : ''}</div>
                        </div>
                    </div>`).join('');
                box.style.display = 'block';
            }

            input.addEventListener('input', function () {
                const q = this.value.trim();
                clearTimeout(timer);
                if (q.length < 2) { hide(); return; }
                timer = setTimeout(() => {
                    if (q === lastQuery) return;
                    lastQuery = q;
                    fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.json()).then(render).catch(hide);
                }, 220);
            });

            box.addEventListener('click', function (e) {
                const opt = e.target.closest('.mp-opt');
                if (!opt) return;
                const r = JSON.parse(opt.dataset.item);

                document.getElementById('a_catalog_id').value = r.id;
                document.getElementById('a_item_name').value  = r.label;
                document.getElementById('a_brand').value      = r.brand || '';
                document.getElementById('a_strength').value   = r.strength || '';
                document.getElementById('a_form').value       = r.form || '';

                document.getElementById('mpPickedName').textContent = r.label;
                document.getElementById('mpPickedMeta').textContent = (r.meta || r.brand || 'Generic') + (r.in_stock ? ' · already in your pharmacy' : '');
                const img = document.getElementById('mpPickedImg');
                img.src = r.image || "{{ asset('public/assets/admin/img/160x160/img2.jpg') }}";
                picked.style.display = 'flex';

                input.value = '';
                hide();
                document.querySelector('#addMedModal [name=mrp]').focus();
            });

            document.getElementById('mpClear').addEventListener('click', function () {
                document.getElementById('a_catalog_id').value = '';
                picked.style.display = 'none';
                input.focus();
            });

            document.addEventListener('click', e => {
                if (!e.target.closest('.mp-search-wrap')) hide();
            });
        })();

        const medUpdateUrl = "{{ route('vendor.pharmacy.medicines.update', ['id' => '__ID__']) }}";
        const medAddStockUrl = "{{ route('vendor.pharmacy.medicines.add-stock', ['id' => '__ID__']) }}";
        document.querySelectorAll('.edit-med-btn').forEach(b => b.addEventListener('click', function () {
            const d = this.dataset;
            document.getElementById('editMedForm').action = medUpdateUrl.replace('__ID__', d.id);
            document.getElementById('e_name').value = d.name || '';
            document.getElementById('e_brand').value = d.brand || '';
            document.getElementById('e_sku').value = d.sku || '';
            setEditUnit(d.unit || '');
            document.getElementById('e_mrp').value = d.mrp || ''; 
            document.getElementById('e_selling').value = d.selling || '';
            document.getElementById('e_reorder').value = d.reorder || '';
            document.getElementById('e_expiry').value = d.expiry || '';
            $('#editMedModal').modal('show');
        }));
        document.querySelectorAll('.add-stock-btn').forEach(b => b.addEventListener('click', function () {
            document.getElementById('addStockForm').action = medAddStockUrl.replace('__ID__', this.dataset.id);
            document.getElementById('s_name').textContent = this.dataset.name || '';
            $('#addStockModal').modal('show');
        }));

        // Unit fields: select2 with tags (pick an existing unit or type a new one). dropdownParent
        // is the modal so the search box stays focusable inside the Bootstrap modal.
        $('#a_unit').select2({
            tags: true, width: '100%', placeholder: 'Tablet, Strip, ml…',
            dropdownParent: $('#addMedModal'),
        });
        $('#e_unit').select2({
            tags: true, width: '100%', placeholder: 'Select or type a unit',
            dropdownParent: $('#editMedModal'),
        });

        // Set the edit modal's unit, adding it as an option first if it isn't already listed
        // (an existing medicine may use a unit that is not in the suggestion list).
        function setEditUnit(unit) {
            const $eu = $('#e_unit');
            if (unit && !$eu.find('option').filter(function () { return this.value === unit; }).length) {
                $eu.append(new Option(unit, unit, true, true));
            }
            $eu.val(unit).trigger('change');
        }
    </script>
@endpush
