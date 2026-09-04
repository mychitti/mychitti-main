@extends('layouts.vendor.app')
@section('title', 'Pharmacy — Shared Medicine Catalog')

@push('css_or_js')
<style>
    .pc-table { min-width: 1260px; }
    .pc-thumb {
        width:38px; height:38px; border-radius:8px; object-fit:cover; background:#f1f5f9;
        display:inline-flex; align-items:center; justify-content:center; color:#94a3b8; flex-shrink:0;
    }
    .pc-name { font-size:13px; font-weight:600; color:#0f172a; }
    .pc-meta { font-size:11px; color:#64748b; }
    .pc-bar {
        position:sticky; bottom:0; z-index:5; background:#fff; border-top:1px solid #c8d2e0;
        padding:10px 14px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;
        box-shadow:0 -4px 12px rgba(15,23,42,.06);
    }
    .pc-row-stocked { background:#f8fafc; }
    .pc-qty { display:flex; gap:6px; }
    .pc-qty input { flex:1 1 80px; min-width:0; }
    .pc-qty .pc-basis { flex:0 0 100px; }
    .pc-pack { display:flex; gap:5px; align-items:center; }
    .pc-pack .pc-packunit { flex:1 1 96px; min-width:0; }
    .pc-pack .pc-packqty { flex:0 0 62px; }
    .pc-eq { font-size:11px; color:#94a3b8; white-space:nowrap; }
    .pc-hint { font-size:10px; font-weight:400; color:#94a3b8; text-transform:none; letter-spacing:0; margin-top:2px; }
    .pc-per { font-size:10px; color:#94a3b8; margin-top:3px; }
    .pc-packnote { font-size:10.5px; color:#0891b2; margin-top:4px; min-height:14px; }
    /* The unit and pack columns are hidden until the vendor asks for them. They still submit
       their defaults while hidden, so a plain "tick and add" run needs no unit decisions. */
    .pc-uom-col, .pc-uom-ctl { display:none; }
    .pc-uom-on .pc-uom-ctl { display:flex; }
    table.pc-uom-on th.pc-uom-col, table.pc-uom-on td.pc-uom-col { display:table-cell; }

    .pc-uom-toggle {
        display:flex; align-items:flex-start; gap:9px; cursor:pointer;
        background:#f8fafc; border:1px solid #c8d2e0; border-radius:9px;
        padding:10px 13px; margin-bottom:12px; font-size:12.5px; color:#334155;
    }
    .pc-uom-toggle input { margin-top:3px; }
    .pc-uom-toggle b { color:#0f172a; }
    .pc-uom-note { display:block; font-size:11.5px; color:#64748b; margin-top:2px; line-height:1.5; }
    @media (max-width: 767px) {
        .content.container-fluid { padding: 0.75rem; }
        .pc-filters { flex-wrap: wrap; gap: 8px; }
        .pc-filters .form-control { width: 100% !important; max-width: 100% !important; }
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px;">
        <div>
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-book-opened" style="font-size:22px;"></i></span>
                Shared Medicine Catalog
            </h1>
            <span class="text-muted" style="font-size:12px;">
                One curated record per medicine, maintained for every hospital. Tick what you stock,
                set your own price, and it is added to your pharmacy — no retyping.
            </span>
        </div>
        <a href="{{ route('vendor.pharmacy.medicines') }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-medicine-bottle"></i> My Pharmacy
        </a>
    </div>

    <form method="get" class="card mb-3">
        <div class="card-body py-2 d-flex align-items-center pc-filters" style="gap:10px;">
            <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm"
                   style="max-width:280px;" placeholder="Search medicine or brand...">
            <select name="form" class="form-control form-control-sm" style="max-width:170px;" onchange="this.form.submit()">
                <option value="">All types</option>
                @foreach($forms as $f)
                    <option value="{{ $f }}" {{ $form === $f ? 'selected' : '' }}>{{ $f }}</option>
                @endforeach
            </select>
            <label class="d-flex align-items-center mb-0" style="gap:6px; font-size:12px; font-weight:600; white-space:nowrap;">
                <input type="checkbox" name="hide_stocked" value="1" {{ $hide ? 'checked' : '' }} onchange="this.form.submit()">
                Hide what I already stock
            </label>
            <button class="btn btn-sm btn--primary">Search</button>
        </div>
    </form>

    {{-- Units are set for you from each medicine's type (tablets by the strip, syrups by the
         bottle, injections by the vial), so the common case needs no decisions at all. The
         controls stay out of the way until a vendor says they want them. --}}
    <label class="pc-uom-toggle">
        <input type="checkbox" id="pcUomToggle">
        <span>
            <b>Set my own units &amp; pack sizes</b>
            <span class="pc-uom-note">
                Off, each medicine uses the usual unit for its type — tablets by the Strip, syrups by
                the Bottle, injections by the Vial. Turn on to change that, or to record that you buy
                in bigger packs (<b>1 Box = 20 Strip</b>).
            </span>
        </span>
    </label>

    <form method="post" action="{{ route('vendor.pharmacy.catalog.adopt') }}">
        @csrf
        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm table-align-middle mb-0 pc-table" style="font-size:13px">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:36px;"><input type="checkbox" id="pcAll"></th>
                            <th style="width:54px;"></th>
                            <th>Medicine</th>
                            <th style="width:100px;">Type</th>
                            <th style="width:120px;" class="pc-uom-col">
                                I count this in
                                <div class="pc-hint">what you stock &amp; price by</div>
                            </th>
                            <th style="width:118px;">
                                MRP ({{ \App\CentralLogics\Helpers::currency_symbol() ?? '₹' }})
                                <div class="pc-hint">per unit</div>
                            </th>
                            <th style="width:118px;">
                                Selling price
                                <div class="pc-hint">blank = same as MRP</div>
                            </th>
                            <th style="width:190px;">
                                Opening stock
                                <div class="pc-hint">how much you have now</div>
                            </th>
                            <th style="width:265px;" class="pc-uom-col">
                                Bigger pack <span class="pc-meta">(optional)</span>
                                <div class="pc-hint">if you buy in boxes</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($items as $c)
                        @php $isStocked = isset($stocked[$c->id]); @endphp
                        <tr class="{{ $isStocked ? 'pc-row-stocked' : '' }}">
                            <td>
                                @if($isStocked)
                                    <i class="tio-checkmark-circle text-success" title="Already in your pharmacy"></i>
                                @else
                                    <input type="checkbox" name="adopt[]" value="{{ $c->id }}" class="pc-pick">
                                @endif
                            </td>
                            <td>
                                @if($c->image_url)
                                    <img src="{{ $c->image_url }}" class="pc-thumb" alt="">
                                @else
                                    <span class="pc-thumb"><i class="tio-medicine-bottle"></i></span>
                                @endif
                            </td>
                            <td>
                                <div class="pc-name">{{ $c->name }} {{ $c->strength_text }}</div>
                                <div class="pc-meta">{{ $c->brand ?: 'Generic' }}</div>
                            </td>
                            <td><span class="badge badge-soft-secondary">{{ $c->form ?: '—' }}</span></td>
                            @php $suggested = \App\Services\CatalogPool::defaultUnitFor($c->form); @endphp

                            {{-- The base unit gets its own column: it is what MRP, selling price,
                                 stock and reorder level are all counted in, so burying it inside
                                 the pack setup made two different "unit" boxes look interchangeable. --}}
                            <td class="pc-uom-col">
                                <select name="unit[{{ $c->id }}]" class="form-control form-control-sm pc-unit"
                                        data-row="{{ $c->id }}" {{ $isStocked ? 'disabled' : '' }}>
                                    @foreach($units as $u)
                                        <option value="{{ $u }}" {{ $u === $suggested ? 'selected' : '' }}>{{ $u }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="mrp[{{ $c->id }}]"
                                       class="form-control form-control-sm" placeholder="0.00" {{ $isStocked ? 'disabled' : '' }}>
                                <div class="pc-per">per <span class="pc-per-unit" data-row="{{ $c->id }}">{{ $suggested }}</span></div>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="selling_price[{{ $c->id }}]"
                                       class="form-control form-control-sm" placeholder="= MRP" {{ $isStocked ? 'disabled' : '' }}>
                                <div class="pc-per">per <span class="pc-per-unit" data-row="{{ $c->id }}">{{ $suggested }}</span></div>
                            </td>
                            <td>
                                <div class="pc-qty">
                                    <input type="number" step="0.01" min="0" name="stock[{{ $c->id }}]" value="0"
                                           class="form-control form-control-sm" {{ $isStocked ? 'disabled' : '' }}>
                                    {{-- Counted in the base unit, or in packs once a pack is defined —
                                         3 boxes of 20 is stored as 60 strips either way. --}}
                                    <select name="stock_unit[{{ $c->id }}]" class="form-control form-control-sm pc-basis"
                                            data-row="{{ $c->id }}" {{ $isStocked ? 'disabled' : '' }}>
                                        <option value="base">{{ $suggested }}</option>
                                    </select>
                                </div>
                            </td>
                            <td class="pc-uom-col">
                                {{-- Reads as a sentence - "1 Box = 20 Strip" - with the base unit
                                     echoed as text so there is only one place to change it. --}}
                                <div class="pc-pack">
                                    <span class="pc-eq">1</span>
                                    <select name="pack_unit[{{ $c->id }}]" class="form-control form-control-sm pc-packunit"
                                            data-row="{{ $c->id }}" {{ $isStocked ? 'disabled' : '' }}>
                                        <option value="">— no pack —</option>
                                        @foreach($units as $u)
                                            <option value="{{ $u }}">{{ $u }}</option>
                                        @endforeach
                                    </select>
                                    <span class="pc-eq">=</span>
                                    <input type="number" step="1" min="0" name="pack_qty[{{ $c->id }}]"
                                           class="form-control form-control-sm pc-packqty" data-row="{{ $c->id }}"
                                           placeholder="20" {{ $isStocked ? 'disabled' : '' }}>
                                    <span class="pc-eq pc-per-unit" data-row="{{ $c->id }}">{{ $suggested }}</span>
                                </div>
                                <div class="pc-packnote" data-row="{{ $c->id }}"></div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                Nothing in the catalog matches that.
                                Medicines you add in your pharmacy are reviewed and added here automatically.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if(count($items))
            <div class="pc-bar">
                <span class="text-muted" style="font-size:12px;">
                    <strong id="pcCount">0</strong> selected
                </span>
                <div class="d-flex align-items-center pc-uom-ctl" style="gap:6px;">
                    <label class="mb-0 text-muted" style="font-size:12px; white-space:nowrap;">Set all units to</label>
                    <select id="pcUnitAll" class="form-control form-control-sm" style="width:130px;">
                        <option value="">— per row —</option>
                        @foreach($units as $u)
                            <option value="{{ $u }}">{{ $u }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn--primary ml-auto">
                    <i class="tio-add"></i> Add selected to my pharmacy
                </button>
            </div>
            @endif
        </div>

        <div class="mt-3">{!! $items->links() !!}</div>
    </form>
</div>
@endsection

@push('script_2')
<script>
    (function () {
        const all   = document.getElementById('pcAll');
        const count = document.getElementById('pcCount');
        const picks = () => Array.from(document.querySelectorAll('.pc-pick'));

        function refresh() {
            if (count) count.textContent = picks().filter(p => p.checked).length;
        }

        if (all) all.addEventListener('change', function () {
            picks().forEach(p => { p.checked = this.checked; });
            refresh();
        });

        picks().forEach(p => p.addEventListener('change', refresh));
        refresh();

        // "Set all units to" overwrites every row; leaving it on "per row" keeps the unit each
        // dosage form is normally counted in.
        const unitAll = document.getElementById('pcUnitAll');
        if (unitAll) unitAll.addEventListener('change', function () {
            if (!this.value) return;
            document.querySelectorAll('.pc-unit:not([disabled])').forEach(sel => {
                sel.value = this.value;
                syncRow(sel.dataset.row);
            });
        });

        // Reveal the unit and pack columns only on request. Hidden fields keep submitting their
        // defaults, so turning this on and off never changes what a row would save.
        const uomToggle = document.getElementById('pcUomToggle');
        const table     = document.querySelector('.pc-table');
        const bar       = document.querySelector('.pc-bar');
        if (uomToggle) uomToggle.addEventListener('change', function () {
            if (table) table.classList.toggle('pc-uom-on', this.checked);
            if (bar)   bar.classList.toggle('pc-uom-on', this.checked);
        });

        // Keep every "per <unit>" label on the row showing the unit actually chosen, offer the
        // pack in the "counted in" dropdown only once one is really defined, and spell the
        // conversion out in words. A row can never be saved as "3 boxes" with nothing recording
        // how big a box is.
        function syncRow(row) {
            const base  = document.querySelector('.pc-unit[data-row="' + row + '"]');
            const pack  = document.querySelector('.pc-packunit[data-row="' + row + '"]');
            const qty   = document.querySelector('.pc-packqty[data-row="' + row + '"]');
            const basis = document.querySelector('.pc-basis[data-row="' + row + '"]');
            const note  = document.querySelector('.pc-packnote[data-row="' + row + '"]');
            if (!base) return;

            const baseUnit = base.value;

            document.querySelectorAll('.pc-per-unit[data-row="' + row + '"]')
                .forEach(el => { el.textContent = baseUnit; });

            // The base unit can't also be the pack — "1 Strip = 20 Strip" means nothing.
            if (pack) {
                Array.from(pack.options).forEach(o => {
                    o.hidden = (o.value !== '' && o.value === baseUnit);
                });
                if (pack.value === baseUnit) pack.value = '';
            }

            const packQty = qty ? parseFloat(qty.value) : 0;
            const hasPack = pack && pack.value && packQty > 0;

            if (basis) {
                const chosen = basis.value;
                basis.innerHTML = '<option value="base">' + baseUnit + '</option>'
                    + (hasPack ? '<option value="pack">' + pack.value + '</option>' : '');
                basis.value = (chosen === 'pack' && hasPack) ? 'pack' : 'base';
            }

            if (note) {
                note.textContent = hasPack
                    ? '1 ' + pack.value + ' = ' + packQty + ' ' + baseUnit
                        + ' \u2014 you can enter stock in either'
                    : (pack && pack.value && !(packQty > 0) ? 'Enter how many ' + baseUnit + ' are in one ' + pack.value : '');
            }
        }

        document.querySelectorAll('.pc-unit, .pc-packunit, .pc-packqty').forEach(el => {
            el.addEventListener('change', () => syncRow(el.dataset.row));
            el.addEventListener('input',  () => syncRow(el.dataset.row));
        });
        document.querySelectorAll('.pc-unit').forEach(el => syncRow(el.dataset.row));
    })();
</script>
@endpush
