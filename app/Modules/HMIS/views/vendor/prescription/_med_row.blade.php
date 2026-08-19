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
    .rx-table { font-size:13px; margin-bottom:0; table-layout:fixed; }
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
</style>
@endpush
@endonce

<tr class="med-row">
    <td class="rx-n text-muted"></td>
    <td>
        <select name="medicines[{{ $i }}][type]" class="form-control form-control-sm">
            <option value=""></option>
            @foreach($typeOptions as $opt)
                <option value="{{ $opt }}" {{ $type === $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="hidden" name="medicines[{{ $i }}][inventory_item_id]" class="med-inv-id"
            value="{{ old("medicines.{$i}.inventory_item_id", $item?->inventory_item_id ?? '') }}">
        <input type="text" name="medicines[{{ $i }}][medicine_name]"
            class="form-control form-control-sm rx-med-name" placeholder="Medicine name *"
            value="{{ old("medicines.{$i}.medicine_name", $item?->medicine_name ?? '') }}">
        {{-- What the brand actually is. Filled from the linked inventory item, and by the pharmacy
             search when a medicine is picked from it, so the doctor sees the salt without having
             to open the item. Display only — nothing is posted from here. --}}
        <div class="rx-composition" {{ $composition ? '' : 'hidden' }}>{{ $composition }}</div>
    </td>
    <td>
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
    <td>
        <select name="medicines[{{ $i }}][instructions]" class="form-control form-control-sm">
            <option value="">—</option>
            @foreach(['Before Food','After Food','With Food','Empty Stomach','At Bedtime','With Water'] as $opt)
                <option value="{{ $opt }}" {{ $when === $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <select name="medicines[{{ $i }}][frequency]" class="form-control form-control-sm">
            <option value="">—</option>
            @foreach($freqOptions as $f)
                <option value="{{ $f }}" {{ $freq === $f ? 'selected' : '' }}>{{ $f }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="text" name="medicines[{{ $i }}][duration]"
            class="form-control form-control-sm" placeholder="5 days"
            value="{{ old("medicines.{$i}.duration", $item?->duration ?? '') }}">
    </td>
    <td>
        {{-- Kept even though the reference layout has no column for it: the pharmacy dispenses
             against this figure and deducts stock by it. --}}
        <input type="number" min="1" name="medicines[{{ $i }}][quantity]"
            class="form-control form-control-sm" placeholder="Qty"
            value="{{ old("medicines.{$i}.quantity", $item?->quantity ?? '') }}">
    </td>
    <td>
        <input type="text" name="medicines[{{ $i }}][notes]"
            class="form-control form-control-sm" placeholder="Notes / instructions"
            value="{{ old("medicines.{$i}.notes", $item?->notes ?? '') }}">
    </td>
    <td class="text-center">
        <a class="rx-del" title="Remove" onclick="removeMedRow(this)"><i class="tio-delete-outlined"></i></a>
    </td>
</tr>
