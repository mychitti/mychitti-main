<div class="med-row">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong style="font-size:12px;color:#6b7280;">Medicine</strong>
        <button type="button" class="btn btn-xs btn-outline-danger" onclick="removeMedRow(this)"
            style="padding:1px 7px; font-size:11px;">&times;</button>
    </div>
    <div class="form-group mb-2">
        <input type="hidden" name="medicines[{{ $i }}][inventory_item_id]"
            class="med-inv-id"
            value="{{ old("medicines.{$i}.inventory_item_id", $item?->inventory_item_id ?? '') }}">
        <input type="text" name="medicines[{{ $i }}][medicine_name]"
            class="form-control form-control-sm" placeholder="Medicine name *"
            value="{{ old("medicines.{$i}.medicine_name", $item?->medicine_name ?? '') }}">
    </div>
    <div class="row no-gutters" style="gap:0;">
        <div class="col-6 pr-1">
            <input type="text" name="medicines[{{ $i }}][dosage]"
                class="form-control form-control-sm mb-2" placeholder="Dosage (e.g. 500mg)"
                value="{{ old("medicines.{$i}.dosage", $item?->dosage ?? '') }}">
        </div>
        <div class="col-6">
            <select name="medicines[{{ $i }}][frequency]" class="form-control form-control-sm mb-2">
                @php $freq = old("medicines.{$i}.frequency", $item?->frequency ?? ''); @endphp
                <option value="">Frequency</option>
                @foreach(['Once a day','Twice a day','3 times a day','4 times a day','Every 6 hrs','Every 8 hrs','Every 12 hrs','SOS / as needed'] as $f)
                    <option value="{{ $f }}" {{ $freq == $f ? 'selected' : '' }}>{{ $f }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 pr-1">
            <input type="text" name="medicines[{{ $i }}][duration]"
                class="form-control form-control-sm mb-2" placeholder="Duration (e.g. 5 days)"
                value="{{ old("medicines.{$i}.duration", $item?->duration ?? '') }}">
        </div>
        <div class="col-6">
            <input type="number" min="1" name="medicines[{{ $i }}][quantity]"
                class="form-control form-control-sm mb-2" placeholder="Qty"
                value="{{ old("medicines.{$i}.quantity", $item?->quantity ?? '') }}">
        </div>
    </div>
    <select name="medicines[{{ $i }}][instructions]" class="form-control form-control-sm">
        @php $ins = old("medicines.{$i}.instructions", $item?->instructions ?? ''); @endphp
        <option value="">Instructions (optional)</option>
        @foreach(['Before food','After food','With food','At bedtime','On empty stomach','With water'] as $opt)
            <option value="{{ $opt }}" {{ $ins == $opt ? 'selected' : '' }}>{{ $opt }}</option>
        @endforeach
    </select>
</div>
