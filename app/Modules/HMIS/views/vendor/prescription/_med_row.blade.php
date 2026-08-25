{{-- One medicine line of the prescription table.

     A <tr>, not a card: a doctor writing five medicines compares them down the columns — same
     dose, same duration — and stacked cards make that impossible.

     Dose is the morning–afternoon–night pattern every Indian prescription is written in, so it is
     picked rather than typed. The list is open (a select with the common patterns) because "1—0—1"
     covers the overwhelming majority and typing it out is pure friction. --}}
@php
    $dose = old("medicines.{$i}.dosage", $item?->dosage ?? '');
    $freq = old("medicines.{$i}.frequency", $item?->frequency ?? '');
    $when = old("medicines.{$i}.instructions", $item?->instructions ?? '');
    $type = old("medicines.{$i}.type", $item?->type ?? '');

    // The salt line under the brand name. inventory_items has no composition column yet, so this
    // is empty for now and the row simply hides it; the markup and the JS hook are in place so the
    // day that field exists (or the pharmacy search starts returning it) it lights up with no
    // further change here.
    $composition = $item?->inventoryItem?->product_note ?: '';

    $doseOptions = ['1 — 0 — 0', '0 — 1 — 0', '0 — 0 — 1', '1 — 0 — 1', '1 — 1 — 1',
                    '1 — 1 — 0', '0 — 1 — 1', '1/2 — 0 — 1/2', '2 — 0 — 2', 'SOS'];
    $typeOptions = ['TAB.', 'CAP.', 'SYR.', 'INJ.', 'DROPS', 'OINT.', 'POWDER', 'SACHET', 'INHALER'];
    $freqOptions = ['Daily', 'Alternate days', 'Weekly', 'Once a day', 'Twice a day',
                    '3 times a day', '4 times a day', 'Every 6 hrs', 'Every 8 hrs',
                    'Every 12 hrs', 'SOS / as needed'];
@endphp
{{-- Table styling lives with the row it styles, emitted once however many tables a page renders,
     so the three screens that use this partial cannot drift apart visually. --}}
@once
@push('css_or_js')
<style>
    .rx-table { font-size:13px; margin-bottom:0; table-layout:fixed; min-width:1180px; }
    /* table-layout:fixed scales the px column widths down proportionally once the viewport is
       narrower than their sum, which crushed the selects and ran the Medicine heading into
       Dose. The min-width pins the columns so .table-responsive scrolls sideways instead. */
    .rx-table thead th {
        font-size:10px; text-transform:uppercase; letter-spacing:.04em; color:#8d97a5;
        background:#fafbfc; border-bottom:1px solid #e9edf2; border-top:0;
        padding:9px 10px; font-weight:700; white-space:nowrap;
    }
    .rx-table td { padding:7px 10px; border-top:1px solid #f2f4f8; vertical-align:top; }
    .rx-table tbody tr:first-child td { border-top:0; }
    .rx-table tbody tr:hover td { background:#fcfdff; }

    /* Every control fills its column — the columns are sized here, not by the browser guessing
       from content, which is what left the selects rattling around in wide cells. */
    .rx-table .form-control {
        width:100%; height:34px; font-size:12.5px; padding:4px 9px;
        border-radius:7px; border-color:#e3e7ef;
    }
    .rx-table select.form-control { padding-right:22px; }
    .rx-table .form-control:focus { border-color:#93b4fd; box-shadow:0 0 0 3px rgba(59,130,246,.09); }
    .rx-table .form-control::placeholder { color:#c2cad6; }

    .rx-table .rx-n { font-size:12px; font-weight:700; color:#b3bcc9; text-align:center; padding-top:14px; }
    /* Numbered in CSS so inserting or deleting a row never leaves a gap to renumber. */
    .rx-table tbody { counter-reset: rxrow; }
    .rx-table tbody tr { counter-increment: rxrow; }
    .rx-table .rx-n::before { content: counter(rxrow); }

    .rx-table .rx-med-name { font-weight:600; }
    .rx-composition { font-size:10.5px; color:#9aa4b2; margin-top:3px; padding-left:2px; line-height:1.3; }
    .rx-table .rx-dose { text-align:center; font-weight:600; }
    .rx-table .rx-del { color:#cfd6e0; cursor:pointer; font-size:16px; line-height:34px; }
    .rx-table .rx-del:hover { color:#dc3545; }

    /* Action bar under the table — buttons left, language on the right. */
    .rx-actions { display:flex; align-items:flex-end; gap:10px; flex-wrap:wrap; }
    .rx-lang { margin-left:auto; min-width:200px; max-width:250px; }
    .rx-lang label { font-size:11px; font-weight:600; color:#8d97a5; margin-bottom:3px; display:block; }
    .rx-lang select { height:34px; font-size:12.5px; border-radius:7px; border-color:#e3e7ef; }

    /* select2 has to look like the plain controls beside it — it renders its own box, which by
       default is a different height, radius and border colour from .form-control. */
    .rx-table .rx-med-select + .select2-container { width:100% !important; }
    /* Outlined, icon-only: the row auto-advances, so this is the fallback, not the main path. */
    .rx-add-row {
        width:28px; height:28px; padding:0; line-height:26px; text-align:center;
        border:1px solid #d7dde7; border-radius:7px; background:#fff; color:#6b7280;
    }
    .rx-add-row i { font-size:14px; vertical-align:middle; }
    .rx-add-row:hover { border-color:#93b4fd; color:#2563eb; background:#f8faff; }
    .rx-table .select2-container--default .select2-selection--single {
        height:34px; border:1px solid #e3e7ef; border-radius:7px;
    }
    /* Centred by flex, not by a line-height guess: the theme overrides the 34px height on some
       screens and a fixed line-height then sits the name off the middle of the box. */
    .rx-table .select2-container--default .select2-selection--single {
        display:flex; align-items:center;
    }
    .rx-table .select2-container--default .select2-selection--single .select2-selection__rendered {
        flex:1 1 auto; min-width:0; line-height:1.3; font-size:12.5px;
        padding-left:9px; padding-right:22px; color:#1f2937; font-weight:600;
    }
    .rx-table .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color:#c2cad6; font-weight:400;
    }
    .rx-table .select2-container--default .select2-selection--single .select2-selection__arrow { height:32px; }
    .rx-table .select2-container--default.select2-container--focus .select2-selection--single,
    .rx-table .select2-container--default.select2-container--open .select2-selection--single {
        border-color:#93b4fd; box-shadow:0 0 0 3px rgba(59,130,246,.09);
    }
    /* The dropdown is attached to <body>, so it cannot be scoped by .rx-table above. */
    .rx-med-dropdown .select2-results__option { font-size:12.5px; padding:7px 12px; }
    .rx-med-banned { color:#b91c1c; font-weight:700; font-size:10px; margin-left:6px; }

    /* Below phone width ten columns are not worth side-scrolling through, so every row becomes
       its own card and each field carries the heading the hidden thead used to give it. */
    @media (max-width: 767.98px) {
        .rx-table { min-width:0; table-layout:auto; display:block; }
        .rx-table thead { display:none; }
        .rx-table tbody, .rx-table tbody tr, .rx-table td { display:block; width:auto; }
        .rx-table tbody tr {
            position:relative; background:#fff; margin-bottom:10px;
            border:1px solid #e9edf2; border-radius:9px; padding:8px 10px 10px;
        }
        .rx-table tbody tr:hover td, .rx-table tbody tr:first-child td { background:transparent; }
        .rx-table td { border-top:0; padding:5px 0; }
        .rx-table td::before {
            content:attr(data-label); display:block; margin-bottom:3px;
            font-size:10px; font-weight:700; text-transform:uppercase;
            letter-spacing:.04em; color:#8d97a5;
        }
        .rx-table .rx-n {
            padding:0 0 6px; margin-bottom:4px; text-align:left;
            font-size:12px; color:#8d97a5; border-bottom:1px solid #f2f4f8;
        }
        .rx-table .rx-n::before { content:"Medicine " counter(rxrow); }
        .rx-table .rx-del-cell { position:absolute; top:6px; right:10px; padding:0; }
        .rx-table .rx-del-cell::before { content:none; }
        .rx-table .rx-del { line-height:1; }
        .rx-table .form-control { height:36px; font-size:13px; }
        .rx-table .rx-dose { text-align:left; }
    }
</style>
@endpush
@endonce

{{-- The row's own inventory search. Emitted once per page however many rows render, and it wires
     itself to rows added later through a MutationObserver, so no screen has to remember to
     re-initialise anything after appending a row. --}}
@once
@push('script')
<script>
(function () {
    const RX_MED_SEARCH_URL = "{{ route('vendor.prescription.search-medicines') }}";

    function rxMedAddRowFor(tbody) {
        // Each screen names its add-row function differently; take whichever this page defines.
        const fn = (tbody && tbody.id === 'apptMedTable')
            ? window.apptAddMedRow
            : (window.addCustomMedRow || window.addMedRow || window.apptAddMedRow);
        if (typeof fn === 'function') fn();
    }

    function rxMedInit(scope) {
        if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
        (scope || document).querySelectorAll('.rx-med-select').forEach(function (el) {
            if (el.dataset.rxMedReady) return;
            el.dataset.rxMedReady = '1';

            jQuery(el).select2({
                width: '100%',
                tags: true,
                placeholder: el.dataset.placeholder || 'Medicine *',
                allowClear: false,
                minimumInputLength: 0,
                dropdownCssClass: 'rx-med-dropdown',
                ajax: {
                    url: RX_MED_SEARCH_URL,
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ q: params.term || '' }),
                    processResults: data => ({
                        results: (data || []).map(it => ({
                            id: it.name, text: it.name, invId: it.id, banned: it.banned
                        }))
                    }),
                    cache: true
                },
                // A medicine the pharmacy does not stock still has to be prescribable, so an
                // unmatched term becomes its own option with no inventory id behind it.
                createTag: params => {
                    const term = (params.term || '').trim();
                    return term === '' ? null : { id: term, text: term, invId: '', newTag: true };
                },
                templateResult: function (d) {
                    if (!d.id) return d.text;
                    return jQuery('<span>').text(d.text).append(
                        d.banned ? jQuery('<span class="rx-med-banned">').text('BANNED') : ''
                    );
                }
            }).on('select2:select', function (e) {
                const row = el.closest('.med-row');
                const inv = row && row.querySelector('.med-inv-id');
                if (inv) inv.value = e.params.data.invId || '';
                if (typeof window.rxBannedCheck === 'function') window.rxBannedCheck(el);

                // Picking a medicine on the last line opens the next one, so a doctor writing
                // five medicines never reaches for the Add row button.
                const tbody = row && row.parentElement;
                if (tbody && row === tbody.querySelector('.med-row:last-child')) {
                    rxMedAddRowFor(tbody);
                }
            });
        });
    }

    window.rxMedInitSelects = rxMedInit;

    // Emptying the only remaining row means clearing its select2 too — setting select.value alone
    // leaves the old medicine still painted in the box.
    window.rxMedClearRow = function (row) {
        if (!row) return;
        row.querySelectorAll('.rx-med-select').forEach(function (el) {
            el.innerHTML = '<option value=""></option>';
            if (window.jQuery && jQuery.fn.select2) jQuery(el).val(null).trigger('change.select2');
        });
        const inv = row.querySelector('.med-inv-id');
        if (inv) inv.value = '';
    };

    document.addEventListener('DOMContentLoaded', function () {
        rxMedInit(document);
        // Rows are appended by plain DOM code on three different screens; observing the table is
        // simpler than patching each of their add-row functions.
        document.querySelectorAll('#medTable, #apptMedTable').forEach(function (tbody) {
            new MutationObserver(function () { rxMedInit(tbody); }).observe(tbody, { childList: true });
        });
    });
})();
</script>
@endpush
@endonce

<tr class="med-row">
    <td class="rx-n text-muted"></td>
    <td data-label="Type">
        <select name="medicines[{{ $i }}][type]" class="form-control form-control-sm">
            <option value=""></option>
            @foreach($typeOptions as $opt)
                <option value="{{ $opt }}" {{ $type === $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
        </select>
    </td>
    <td data-label="Medicine">
        <input type="hidden" name="medicines[{{ $i }}][inventory_item_id]" class="med-inv-id"
            value="{{ old("medicines.{$i}.inventory_item_id", $item?->inventory_item_id ?? '') }}">
        {{-- The inventory search lives in the row itself, not in a separate box above the table:
             the doctor writes down the line they are on. Tagging is on, so a medicine the
             pharmacy does not stock is still typed straight in and posts as free text. --}}
        <select name="medicines[{{ $i }}][medicine_name]"
            class="form-control form-control-sm rx-med-name rx-med-select"
            data-placeholder="Medicine *">
            <option value=""></option>
            @php $medName = old("medicines.{$i}.medicine_name", $item?->medicine_name ?? ''); @endphp
            @if($medName !== '')
                <option value="{{ $medName }}" selected>{{ $medName }}</option>
            @endif
        </select>
        {{-- What the brand actually is. Filled from the linked inventory item, and by the pharmacy
             search when a medicine is picked from it, so the doctor sees the salt without having
             to open the item. Display only — nothing is posted from here. --}}
        <div class="rx-composition" {{ $composition ? '' : 'hidden' }}>{{ $composition }}</div>
    </td>
    <td data-label="Dose">
        {{-- A plain dropdown: morning–afternoon–night is how every prescription here is written,
             and the ten patterns below cover it. A value already on the record that is not one of
             them (an old free-text dosage, a tapering course) is added to its own list so editing
             a prescription never silently rewrites what the doctor chose. --}}
        <select name="medicines[{{ $i }}][dosage]" class="form-control form-control-sm rx-dose">
            <option value="">—</option>
            @foreach($doseOptions as $opt)
                <option value="{{ $opt }}" {{ $dose === $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
            @if($dose !== '' && !in_array($dose, $doseOptions, true))
                <option value="{{ $dose }}" selected>{{ $dose }}</option>
            @endif
        </select>
    </td>
    <td data-label="When">
        <select name="medicines[{{ $i }}][instructions]" class="form-control form-control-sm">
            <option value="">—</option>
            @foreach(['Before Food','After Food','With Food','Empty Stomach','At Bedtime','With Water'] as $opt)
                <option value="{{ $opt }}" {{ $when === $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
        </select>
    </td>
    <td data-label="Frequency">
        <select name="medicines[{{ $i }}][frequency]" class="form-control form-control-sm">
            <option value="">—</option>
            @foreach($freqOptions as $f)
                <option value="{{ $f }}" {{ $freq === $f ? 'selected' : '' }}>{{ $f }}</option>
            @endforeach
        </select>
    </td>
    <td data-label="Duration">
        <input type="text" name="medicines[{{ $i }}][duration]"
            class="form-control form-control-sm" placeholder="5 days"
            value="{{ old("medicines.{$i}.duration", $item?->duration ?? '') }}">
    </td>
    <td data-label="Qty">
        {{-- Kept even though the reference layout has no column for it: the pharmacy dispenses
             against this figure and deducts stock by it. --}}
        <input type="number" min="1" name="medicines[{{ $i }}][quantity]"
            class="form-control form-control-sm" placeholder="Qty"
            value="{{ old("medicines.{$i}.quantity", $item?->quantity ?? '') }}">
    </td>
    <td data-label="Notes">
        <input type="text" name="medicines[{{ $i }}][notes]"
            class="form-control form-control-sm" placeholder="Notes / instructions"
            value="{{ old("medicines.{$i}.notes", $item?->notes ?? '') }}">
    </td>
    <td class="text-center rx-del-cell">
        <a class="rx-del" title="Remove" onclick="removeMedRow(this)"><i class="tio-delete-outlined"></i></a>
    </td>
</tr>
