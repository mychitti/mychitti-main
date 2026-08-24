{{-- The fields that describe one piece of lab work, shared by the New job form and each job's
     Edit form so the two can never drift apart — a measurement box added to a speciality profile
     has to appear in both, and one copy is how that stays true.

     $labWorkProfile : the speciality profile whose fields are being rendered
     $work           : the job being edited, or null when adding
     $withStatus     : whether to offer the opening stage (add only — an existing job's stage
                       moves through the Update stage form, which owns the dated milestones) --}}
@php
    $work        = $work ?? null;
    $withStatus  = $withStatus ?? false;
    $fieldValues = (array) ($work?->measurements ?? []);
    $lwVendors   = $labVendors ?? collect();

    // old() is global to the page, and every job's edit form posts the same field names — so a
    // rejected New job would repopulate all of them with its values. Only the add form reads it;
    // an edit form always shows what is actually stored. The cost is that a rejected edit loses
    // what was typed, which is far better than nine other jobs quietly offering to save the
    // wrong shade.
    $lwOld = fn(string $field, $stored) => $work ? $stored : old($field, $stored);

    // Which half of the form opens. A new job defaults to sent-out because that is what the tab
    // was built for and what most clinics do; an existing one shows the half it was saved as.
    $lwMode = $work ? ($work->lab_mode ?: 'external') : old('lab_mode', 'external');

    $lwLabTypes     = \App\Models\OpdLabWork::labTypesFor($visit->store_id ?? null);
    $lwLabTypeValue = (string) ($work ? $work->lab_type : old('lab_type', ''));
@endphp

<div class="lw-fields">

{{-- Four sections, because this form asks four unrelated questions — what is being made, to what
     measurements, by whom, and when. Run together they read as one undifferentiated wall of boxes
     where the lab fields look like more measurements. --}}
<style>
    .lw-fields .lw-section { margin-bottom: 14px; }
    .lw-fields .lw-section-title {
        font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
        color: #6b7280; margin-bottom: 8px;
    }
    .lw-fields .lw-panel {
        background: #f8fafc; border: 1px solid #e9eef5; border-radius: 6px;
        padding: 12px 12px 2px; margin-bottom: 14px;
    }
    .lw-fields .lw-panel .lw-section-title { margin-bottom: 10px; }
</style>

<div class="lw-section">
<div class="lw-section-title">The work</div>

<div class="form-row">
    <div class="form-group col-md-{{ $withStatus ? 4 : 6 }}">
        <label class="input-label" style="font-size:12px;">Work <span class="text-danger">*</span></label>
        <input list="lwTypeList" name="work_type" class="form-control form-control-sm" required maxlength="150"
               value="{{ $lwOld('work_type', $work?->work_type) }}">
    </div>
    <div class="form-group col-md-{{ $withStatus ? 4 : 6 }}">
        <label class="input-label" style="font-size:12px;">{{ $labWorkProfile['site']['label'] }}</label>
        <input type="text" name="site" class="form-control form-control-sm" maxlength="190"
               placeholder="{{ $labWorkProfile['site']['placeholder'] }}"
               value="{{ $lwOld('site', $work?->site) }}">
    </div>
    @if($withStatus)
        <div class="form-group col-md-4">
            <label class="input-label" style="font-size:12px;">Stage <span class="text-danger">*</span></label>
            <select name="status" class="form-control form-control-sm" required>
                @foreach($labWorkProfile['statuses'] as $key => $label)
                    <option value="{{ $key }}" @if($key === 'impression') selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endif
</div>
</div>

<div class="lw-section">
<div class="lw-section-title">Measurements</div>

<div class="form-row">
    @foreach($labWorkProfile['fields'] as $key => $field)
        @php $value = $work ? ($fieldValues[$key] ?? '') : old('measurements.' . $key, ''); @endphp
        <div class="form-group col-md-3">
            <label class="input-label" style="font-size:12px;">{{ $field['label'] }}</label>
            @if($field['type'] === 'select')
                <select name="measurements[{{ $key }}]" class="form-control form-control-sm">
                    <option value="">—</option>
                    @foreach($field['options'] as $option)
                        <option value="{{ $option }}" @if((string) $value === (string) $option) selected @endif>{{ $option }}</option>
                    @endforeach
                </select>
            @else
                <input type="{{ $field['type'] === 'number' ? 'number' : 'text' }}"
                       name="measurements[{{ $key }}]" class="form-control form-control-sm" maxlength="190"
                       placeholder="{{ $field['placeholder'] ?? '' }}" value="{{ $value }}">
            @endif
        </div>
    @endforeach
</div>
</div>

{{-- Where the work is done — its own panel, because everything in it answers one question and
     none of it belongs to the job itself. The two halves are mutually exclusive, and the
     controller clears whichever is not chosen: a job moved from an outside lab to the bench must
     not keep a lab phone number that would go on receiving confirmations about work that firm no
     longer touches. --}}
<div class="lw-panel">
    <div class="lw-section-title">Who is making it</div>

    <div class="form-row align-items-center">
        <div class="form-group col-md-3">
            <label class="input-label" style="font-size:12px;">Done at <span class="text-danger">*</span></label>
            <select name="lab_mode" class="form-control form-control-sm" required onchange="lwMode(this)">
                @foreach(\App\Models\OpdLabWork::MODES as $lwModeKey => $lwModeLabel)
                    <option value="{{ $lwModeKey }}" @if($lwMode === $lwModeKey) selected @endif>{{ $lwModeLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-9">
            <p class="small text-muted mb-0" style="font-size:11.5px;">
                In-house work is recorded against the technician who does it. Work sent out can be
                addressed to a lab from your supplier list and told about the job on WhatsApp.
            </p>
        </div>
    </div>

{{-- In-house: the bench, and the person at it. --}}
<div class="form-row" data-lw-block="internal" style="display:{{ $lwMode === 'internal' ? '' : 'none' }};">
    <div class="form-group col-md-4">
        <label class="input-label" style="font-size:12px;">Technician <span class="text-danger">*</span></label>
        <input type="text" name="technician_name" class="form-control form-control-sm" maxlength="150"
               placeholder="Who is making this" value="{{ $lwOld('technician_name', $work?->technician_name) }}">
    </div>
    <div class="form-group col-md-3">
        <label class="input-label" style="font-size:12px;">Technician phone</label>
        <input type="text" name="technician_phone" class="form-control form-control-sm" maxlength="40"
               value="{{ $lwOld('technician_phone', $work?->technician_phone) }}">
    </div>
</div>

{{-- Sent out: which lab, what kind of lab, and how to reach them. Picking a supplier fills the
     three boxes below rather than replacing them, so a number that has to be right for the job to
     reach anybody is not retyped, and can still be corrected for this one job. --}}
<div data-lw-block="external" style="display:{{ $lwMode === 'internal' ? 'none' : '' }};">
    <div class="form-row">
        {{-- The lab IS its supplier record — its name lives there and is snapshotted onto the job
             on save, so it is not asked for twice. A lab the clinic has not added yet is added
             where every other supplier is, not invented on a consultation screen where nothing
             else would ever see it. --}}
        <div class="form-group col-md-4">
            <label class="input-label d-flex justify-content-between align-items-baseline" style="font-size:12px;">
                <span>Lab</span>
                <a href="{{ route('vendor.customer.add', ['user_type' => 'vendor']) }}" target="_blank"
                   class="text-primary" style="font-size:11px; font-weight:500;">
                    <i class="tio-add"></i> Add a new lab
                </a>
            </label>
            <select name="lab_vendor_id" class="form-control form-control-sm" data-lw-select2="lab"
                    data-placeholder="Choose a lab…" onchange="lwVendor(this)">
                <option value=""></option>
                @foreach($lwVendors as $lwVendor)
                    <option value="{{ $lwVendor->id }}"
                            data-name="{{ $lwVendor->f_name }}"
                            data-phone="{{ $lwVendor->phone }}"
                            data-address="{{ $lwVendor->address }}"
                            @if((int) $lwOld('lab_vendor_id', $work?->lab_vendor_id) === (int) $lwVendor->id) selected @endif>
                        {{ $lwVendor->f_name }}{{ filled($lwVendor->phone) ? ' — ' . $lwVendor->phone : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        {{-- Select2 with tags rather than a datalist: the speciality's own lab types are the list,
             but a clinic using a kind of lab nobody has named yet types it and it is kept. The
             stored value is carried as an option of its own so a job saved with a type that has
             since left the profile still shows what it was actually sent to. --}}
        <div class="form-group col-md-4">
            <label class="input-label" style="font-size:12px;">Lab type</label>
            <select name="lab_type" class="form-control form-control-sm" data-lw-select2="tags"
                    data-placeholder="Select or type a lab type…">
                <option value=""></option>
                @foreach($lwLabTypes as $lwLabTypeOption)
                    <option value="{{ $lwLabTypeOption }}" @if($lwLabTypeValue === (string) $lwLabTypeOption) selected @endif>{{ $lwLabTypeOption }}</option>
                @endforeach
                @if($lwLabTypeValue !== '' && !in_array($lwLabTypeValue, $lwLabTypes, true))
                    <option value="{{ $lwLabTypeValue }}" selected>{{ $lwLabTypeValue }}</option>
                @endif
            </select>
        </div>
        {{-- Name, number and address are the supplier record's, shown here as plain text so staff
             can see where the job — and the WhatsApp message — is actually going, without a box
             inviting them to type a fourth version of a lab's address that only this one job would
             ever know about. Correcting any of it is an edit to the supplier. --}}
        <div class="form-group col-md-4">
            <label class="input-label" style="font-size:12px;">Contact</label>
            <div class="form-control-plaintext lw-lab-contact" style="font-size:12.5px; padding-top:.25rem; min-height:31px;">
                <span class="text-muted">Choose a lab to see its details.</span>
            </div>
        </div>
    </div>
</div>
</div>

<div class="lw-section">
<div class="lw-section-title">Dates &amp; cost</div>

<div class="form-row">
    <div class="form-group col-md-3">
        <label class="input-label" style="font-size:12px;">Sent on</label>
        <input type="date" name="sent_on" class="form-control form-control-sm"
               value="{{ $lwOld('sent_on', $work?->sent_on?->format('Y-m-d')) }}">
    </div>
    <div class="form-group col-md-3">
        <label class="input-label" style="font-size:12px;">Expected</label>
        <input type="date" name="expected_on" class="form-control form-control-sm"
               value="{{ $lwOld('expected_on', $work?->expected_on?->format('Y-m-d')) }}">
    </div>
    <div class="form-group col-md-3">
        <label class="input-label" style="font-size:12px;">Amount</label>
        <input type="number" step="0.01" min="0" name="amount" class="form-control form-control-sm"
               value="{{ $lwOld('amount', $work?->amount) }}">
    </div>
</div>

<div class="form-group mb-0">
    <label class="input-label" style="font-size:12px;">Notes</label>
    <input type="text" name="notes" class="form-control form-control-sm" maxlength="2000"
           value="{{ $lwOld('notes', $work?->notes) }}">
</div>
</div>

</div>
