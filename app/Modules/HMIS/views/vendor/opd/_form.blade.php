{{-- Patient & Doctor (read-only on edit, selectable on create) --}}
@if($visit)
{{-- edit: locked --}}
<div class="card mb-3" style="background:linear-gradient(90deg,#eff6ff,#f0fdf4); border:1px solid #bfdbfe;">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap gap-4">
            <div>
                <small class="text-muted d-block">Patient</small>
                <strong>{{ $visit->patient?->name }}</strong>
                <span class="text-muted">({{ $visit->patient?->patient_uid }})</span>
            </div>
            <div>
                <small class="text-muted d-block">Doctor</small>
                <strong>Dr. {{ $visit->doctorProfile?->employee?->f_name }} {{ $visit->doctorProfile?->employee?->l_name }}</strong>
            </div>
            <div>
                <small class="text-muted d-block">Date</small>
                <strong>
                    {{ $visit->visit_date?->format('d M Y') }}
                    @if ($visit->visit_time)
                        <span class="text-muted">· {{ \Carbon\Carbon::parse($visit->visit_time)->format('h:i A') }}</span>
                    @endif
                </strong>
            </div>
        </div>
    </div>
</div>
@else
<div class="card mb-3">
    <div class="card-header py-2"><h6 class="mb-0">Patient &amp; Doctor</h6></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="input-label">Patient <span class="text-danger">*</span></label>
                    <select name="patient_id" id="patientSelect"
                        class="form-control @error('patient_id') is-invalid @enderror" required>
                        <option value="">Select patient...</option>
                        <option value="add_new">＋ Add New Patient</option>
                        @foreach($patients as $p)
                            <option value="{{ $p->id }}"
                                {{ old('patient_id', $prefillPatient?->id) == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->patient_uid }})
                            </option>
                        @endforeach
                    </select>
                    @error('patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="input-label">Doctor <span class="text-danger">*</span></label>
                    <select name="doctor_profile_id" id="doctorSelect"
                            class="form-control js-select2 @error('doctor_profile_id') is-invalid @enderror" required>
                        <option value="">Select doctor...</option>
                        @foreach($doctors as $doc)
                            <option value="{{ $doc->id }}" {{ old('doctor_profile_id') == $doc->id ? 'selected' : '' }}>
                                Dr. {{ $doc->employee?->f_name }} {{ $doc->employee?->l_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('doctor_profile_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="input-label">Visit Date <span class="text-danger">*</span></label>
                    <input type="date" name="visit_date" class="form-control @error('visit_date') is-invalid @enderror"
                        value="{{ old('visit_date', now()->toDateString()) }}" required>
                    @error('visit_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {{-- Defaults to now because a walk-in is registered as the patient arrives.
                         Editable for the desk that catches up on the register later. --}}
                    <label class="input-label">Visit Time</label>
                    <input type="time" name="visit_time" class="form-control @error('visit_time') is-invalid @enderror"
                        value="{{ old('visit_time', now()->format('H:i')) }}">
                    @error('visit_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="input-label">Token Number</label>
                    <input type="number" name="token_number" class="form-control @error('token_number') is-invalid @enderror"
                        value="{{ old('token_number', $nextToken ?? 1) }}" min="1">
                    @error('token_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="input-label">Visit Type <span class="text-danger">*</span></label>
                    <select name="visit_type" id="visitTypeSelect"
                            class="form-control @error('visit_type') is-invalid @enderror" required>
                        @foreach(\App\Models\OpdVisit::VISIT_TYPES as $key => $label)
                            <option value="{{ $key }}" {{ old('visit_type', 'new') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('visit_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="input-label">OP Type</label>
                    <select name="op_type" id="opTypeSelect"
                            class="form-control @error('op_type') is-invalid @enderror">
                        <option value="">Not specified</option>
                        @foreach($opTypes ?? [] as $type)
                            <option value="{{ $type }}" {{ old('op_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                    @error('op_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-12 d-none" id="opValidityAlertWrap">
                <div class="alert alert-soft-success py-2 px-3 mb-0 small d-flex align-items-center" style="border: 1px solid #bbf7d0; background-color: #f0fdf4;">
                    <i class="tio-checkmark-circle text-success mr-2" style="font-size:1.2rem;"></i>
                    <span id="opValidityText"></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Visit type on edit --}}
@if($visit)
<div class="form-group">
    <label class="input-label">Visit Type <span class="text-danger">*</span></label>
    <select name="visit_type" id="visitTypeSelectEdit"
            class="form-control @error('visit_type') is-invalid @enderror" required>
        @foreach(\App\Models\OpdVisit::VISIT_TYPES as $key => $label)
            <option value="{{ $key }}" {{ old('visit_type', $visit->visit_type) === $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    @error('visit_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="form-group">
    <label class="input-label">OP Type</label>
    <select name="op_type" id="opTypeSelectEdit"
            class="form-control @error('op_type') is-invalid @enderror">
        <option value="">Not specified</option>
        {{-- The visit's own value is merged in: a type the hospital has since removed from the
             list must still show on the visits that were recorded under it. --}}
        @foreach(collect($opTypes ?? [])->merge(array_filter([$visit->op_type]))->unique() as $type)
            <option value="{{ $type }}" {{ old('op_type', $visit->op_type) === $type ? 'selected' : '' }}>{{ $type }}</option>
        @endforeach
    </select>
    @error('op_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
@endif

{{-- ── Quick Add Patient Modal ─────────────────────────────────────── --}}
@if(!$visit)
<div class="modal fade" id="quickPatientModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Patient</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="quickPatientError" class="alert alert-danger" style="display:none;"></div>
                <div class="form-row">
                    <div class="form-group col-md-7">
                        <label class="input-label">Name <span class="text-danger">*</span></label>
                        <input type="text" id="qp_name" class="form-control" placeholder="Full name">
                    </div>
                    <div class="form-group col-md-5">
                        <label class="input-label">Phone Number <span class="text-danger">*</span></label>
                        {{-- Deliberately type="text", not type="tel": the layout attaches
                             intl-tel-input to the first tel input on the page, which puts a country
                             flag on the box and auto-inserts "+91 " — neither of which can live with
                             a hard 10-digit cap. --}}
                        <input type="text" id="qp_phone" class="form-control" inputmode="numeric"
                            maxlength="10" autocomplete="off" placeholder="10-digit mobile">
                        <small class="text-danger d-none" id="qp_phone_err" style="font-size:9.5px;"></small>
                    </div>
                </div>
                <div class="form-row">
                    {{-- Optional, with Age the required one: the desk is usually told "34" and
                         nothing more. A real date fills Age in, and Age stays editable after. --}}
                    <div class="form-group col-md-4">
                        <label class="input-label">Date of Birth</label>
                        <input type="date" id="qp_dob" class="form-control" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label class="input-label">Age <span class="text-danger">*</span></label>
                        <input type="number" id="qp_age" class="form-control" min="0" max="150"
                            autocomplete="off" placeholder="Years">
                    </div>
                    <div class="form-group col-md-4">
                        <label class="input-label">Gender <span class="text-danger">*</span></label>
                        <select id="qp_gender" class="form-control">
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                {{-- Whose phone the number is. Hidden until the number turns out to be shared —
                     on a phone only this patient uses the answer is always "Self", so asking every
                     time is a box the desk skips. The lookup reveals it, with who already holds the
                     number, the moment a match comes back. Free text on purpose: "Self",
                     "S/O Ramesh" and "neighbour" are all answers a desk gives. --}}
                <div class="form-group d-none" id="qp_relation_wrap">
                    <label class="input-label">Relation <span class="text-muted" style="font-weight:400;">— optional</span></label>
                    <input type="text" id="qp_relation" class="form-control" maxlength="100"
                        autocomplete="off" placeholder="Whose phone is this? e.g. Self, Son">
                    <small id="qp_relation_note" class="d-none"
                        style="display:block;font-size:11px;line-height:1.35;color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:5px 8px;margin-top:5px;"></small>
                </div>

                <div class="form-group">
                    <label class="input-label">Address</label>
                    <input type="text" id="qp_address" class="form-control" placeholder="Address">
                </div>

                {{-- More Info — the same named chips the intake and the bill use, so anything
                     recorded here is already in the shape the bill prints.

                     No name= attributes anywhere in this modal on purpose: it sits inside the OPD
                     <form>, so a named input would be posted with the visit as well. The rows are
                     read by class and sent on the quick-save AJAX instead. --}}
                <div class="form-group mb-0">
                    <label class="input-label">More Info <span class="text-muted" style="font-weight:400;">— optional</span></label>
                    <div id="qp-custom-buttons" class="mb-2">
                        @foreach (\App\Modules\HMIS\Controllers\Vendor\DentalIntakeController::PRESET_LABELS as $qpLabel)
                            <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 qp-cf-btn"
                                data-label="{{ $qpLabel }}" style="border-radius:999px;font-size:12px;">+ {{ $qpLabel }}</button>
                        @endforeach
                        <button type="button" class="btn btn-outline-danger btn-sm mr-2 mb-2 qp-cf-btn"
                            data-label="Other" style="border-radius:999px;font-size:12px;">+ Other</button>
                    </div>
                    <div id="qp-custom-fields"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn--primary" id="quickPatientSaveBtn">
                    <i class="tio-add"></i> Save Patient
                </button>
            </div>
        </div>
    </div>
</div>

@push('script_2')
<script>
$(function () {
    // Initialise Select2 on the patient dropdown
    $('#patientSelect').select2({
        placeholder: 'Select patient...',
        width: '100%',
    });

    // Doctor, visit type and OP type get the same treatment as the patient picker — the doctor
    // select already carried a js-select2 class, but nothing on this page ever initialised it.
    // Only the visible registration/edit fields are built here; anything inside the hidden
    // "Already Booked" pane is built when that pane is shown, because Select2 sizes itself
    // against its container and computes a zero width while it is display:none.
    $('#doctorSelect').select2({ placeholder: 'Select doctor...', width: '100%' });
    $('#visitTypeSelect, #visitTypeSelectEdit').select2({ width: '100%', minimumResultsForSearch: Infinity });
    // OP Type takes new entries inline: a desk meeting a scheme for the first time should not
    // have to leave a half-filled registration for the settings screen. A typed value is saved to
    // the hospital's list in the background, so it is on the dropdown for everyone next time; if
    // that save fails the visit still records the type it was given.
    $('#opTypeSelect, #opTypeSelectEdit').select2({
        placeholder: 'Not specified',
        width: '100%',
        tags: true,
        createTag: function (params) {
            const term = $.trim(params.term);
            if (term === '') return null;
            return { id: term, text: term + '  (add to list)', newTag: true };
        },
    }).on('select2:select', function (e) {
        if (!e.params.data.newTag) return;

        const name = e.params.data.id;
        // The option Select2 just created carries the "(add to list)" hint as its label — put the
        // plain name back, so the closed box and the posted value read the same.
        $(this).find('option[value="' + name.replace(/"/g, '\\"') + '"]').text(name);
        $(this).trigger('change.select2');

        $.ajax({
            url: '{{ route("vendor.opd.op-types.quick-add") }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', name: name },
        });
    });

    // Intercept "Add New Patient" selection
    $('#patientSelect').on('select2:select', function (e) {
        if (e.params.data.id === 'add_new') {
            // Reset back to empty so the select stays "blank" until patient is created
            $(this).val('').trigger('change');
            $('#qp_name, #qp_phone, #qp_age, #qp_address, #qp_dob, #qp_relation').val('');
            $('#qp_gender').val('');
            $('#qp_phone').removeClass('is-invalid');
            $('#qp_phone_err').addClass('d-none').text('');
            $('#qp_relation_wrap').addClass('d-none');
            $('#qp_relation_note').addClass('d-none').empty();
            // Clear any rows left from a previous patient and put every chip back.
            $('#qp-custom-fields').empty();
            $('#qp-custom-buttons button').show();
            $('#quickPatientError').hide();
            $('#quickPatientModal').modal('show');
        }
    });

    // Phone — digits only, never more than ten, and told as it is typed whether it is a usable
    // number. The box holds the national number alone (no country code), so the rule is exact:
    // ten digits starting 6-9.
    function qpPhoneCheck(showWhileEmpty) {
        const v   = $('#qp_phone').val();
        const err = $('#qp_phone_err');
        let msg   = '';

        if (v.length === 0) {
            msg = showWhileEmpty ? 'Phone number is required.' : '';
        } else if (v.length < 10) {
            msg = 'Phone number must be 10 digits — ' + (10 - v.length) + ' to go.';
        } else if (!/^[6-9]\d{9}$/.test(v)) {
            msg = 'Not a valid mobile number — it should start with 6, 7, 8 or 9.';
        }

        err.text(msg).toggleClass('d-none', msg === '');
        $('#qp_phone').toggleClass('is-invalid', msg !== '');
        return msg === '';
    }

    // `input` rather than `keyup` so pasting and autofill are caught too. Anything that is not a
    // digit is dropped on the spot, and the value is cut at ten.
    $('#qp_phone').on('input', function () {
        const kept = this.value.replace(/\D/g, '').slice(0, 10);
        if (kept !== this.value) this.value = kept;
        qpPhoneCheck(false);
        qpLookup(kept);
    }).on('blur', function () {
        qpPhoneCheck(true);
    });

    // Who else uses this number. One number covers a whole family, so a match is not an error —
    // it is context, and the only thing the desk has to do about it is say whose phone it is.
    // Same endpoint the dental intake screen uses; this modal always registers a new patient.
    let qpLookupTimer = null;

    function qpEscape(v) { return $('<div>').text(v == null ? '' : v).html(); }

    function qpShowMatches(matches) {
        if (!matches.length) {
            $('#qp_relation_wrap').addClass('d-none');
            $('#qp_relation_note').addClass('d-none').empty();
            // Nothing shares the number, so the answer would only ever be "Self".
            $('#qp_relation').val('');
            return;
        }

        // Two names at most: the desk needs to recognise the household, not read a list.
        const names = matches.slice(0, 2).map(m => qpEscape(m.name)).join(', ');
        const rest  = matches.length - Math.min(matches.length, 2);

        $('#qp_relation_wrap').removeClass('d-none');
        $('#qp_relation_note')
            .html('This number is already registered to <b>' + names + '</b>'
                + (rest > 0 ? ' and ' + rest + ' more' : '')
                + '. Whose phone is it for this patient?')
            .removeClass('d-none');
    }

    function qpLookup(digits) {
        clearTimeout(qpLookupTimer);
        if (digits.length < 10) { qpShowMatches([]); return; }

        qpLookupTimer = setTimeout(function () {
            $.ajax({
                url: '{{ route("vendor.dental-intake.lookup-phone") }}',
                data: { phone: digits },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) { qpShowMatches(res.matches || []); },
                error: function () { qpShowMatches([]); },
            });
        }, 300);
    }

    // Age follows DOB, but stays the desk's to overwrite: a typed age is never clobbered by a
    // later DOB edit, and a patient known only as "about 40" still registers.
    $('#qp_dob').on('change', function () {
        if (!this.value) return;
        const b = new Date(this.value);
        if (isNaN(b)) return;
        const now = new Date();
        let a = now.getFullYear() - b.getFullYear();
        const m = now.getMonth() - b.getMonth();
        if (m < 0 || (m === 0 && now.getDate() < b.getDate())) a--;
        if (a >= 0 && a <= 150) $('#qp_age').val(a);
    });

    // More Info rows — same interaction as the intake screen and the advanced bill: a named chip
    // drops a row with that label fixed, "+ Other" drops one where the label is typed too.
    $('#qp-custom-buttons').on('click', '.qp-cf-btn', function () {
        const label = $(this).data('label');
        let row;

        if (label === 'Other') {
            row = `
            <div class="form-group qp-cf-row" data-label="Other">
                <div class="d-flex">
                    <input type="text" class="form-control form-control-sm mr-2 qp-cf-label" placeholder="Label">
                    <input type="text" class="form-control form-control-sm mr-2 qp-cf-value" placeholder="Value">
                    <a type="button" class="text-danger qp-cf-remove" style="align-self:center;"><i class="tio-delete-outlined"></i></a>
                </div>
            </div>`;
        } else {
            row = `
            <div class="form-group qp-cf-row" data-label="${label}">
                <label style="font-size:12px;font-weight:600;color:#56606e;">${label}</label>
                <div class="d-flex">
                    <input type="hidden" class="qp-cf-label" value="${label}">
                    <input type="text" class="form-control form-control-sm mr-2 qp-cf-value" placeholder="${label}">
                    <a type="button" class="text-danger qp-cf-remove" style="align-self:center;"><i class="tio-delete-outlined"></i></a>
                </div>
            </div>`;

            $(this).hide();
        }

        $('#qp-custom-fields').append(row);
    });

    $('#qp-custom-fields').on('click', '.qp-cf-remove', function () {
        const $row  = $(this).closest('.qp-cf-row');
        const label = $row.data('label');

        $('#qp-custom-buttons button').each(function () {
            if ($(this).data('label') === label) $(this).show();
        });

        $row.remove();
    });

    // Save new patient via AJAX
    $('#quickPatientSaveBtn').on('click', function () {
        const name    = $('#qp_name').val().trim();
        // Separators stripped before checking and before sending, so "+91 98765 43210" and
        // "9876543210" are the same number here, on the intake screen and on the server.
        const phone   = $('#qp_phone').val().trim().replace(/[\s\-()]/g, '');
        const age     = $('#qp_age').val().trim();
        const gender  = $('#qp_gender').val();
        const address = $('#qp_address').val().trim();
        const dob      = $('#qp_dob').val();
        // Only meaningful while the field is showing — a hidden box must not post a stale answer.
        const relation = $('#qp_relation_wrap').hasClass('d-none') ? '' : $('#qp_relation').val().trim();

        // Posted as header_label[] / header_field[] — the same keys the intake form and the bill
        // submit, so the server reads them with one shared helper.
        const headerLabels = [];
        const headerFields = [];
        $('#qp-custom-fields .qp-cf-row').each(function () {
            const l = $(this).find('.qp-cf-label').val().trim();
            const v = $(this).find('.qp-cf-value').val().trim();
            if (l && v) { headerLabels.push(l); headerFields.push(v); }
        });

        if (!name) {
            $('#quickPatientError').text('Name is required.').show();
            return;
        }
        if (!qpPhoneCheck(true)) {
            $('#qp_phone').focus();
            return;
        }
        if (age === '' || isNaN(age) || Number(age) < 0 || Number(age) > 150) {
            $('#quickPatientError').text('Enter an age between 0 and 150.').show();
            $('#qp_age').focus();
            return;
        }
        if (!gender) {
            $('#quickPatientError').text('Gender is required.').show();
            $('#qp_gender').focus();
            return;
        }
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Saving...');
        $('#quickPatientError').hide();

        $.ajax({
            url: '{{ route("vendor.patient.quick-save") }}',
            method: 'POST',
            data: {
                _token:       '{{ csrf_token() }}',
                name:         name,
                phone:        phone,
                age:          age,
                dob:          dob,
                phone_relation: relation,
                gender:       gender,
                address:      address,
                header_label: headerLabels,
                header_field: headerFields,
            },
            success: function (res) {
                if (res.success) {
                    // Add new option to Select2 and select it
                    const option = new Option(res.patient.text, res.patient.id, true, true);
                    $('#patientSelect').append(option).trigger('change');
                    $('#quickPatientModal').modal('hide');
                } else {
                    $('#quickPatientError').text(res.message || 'Failed to save.').show();
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Something went wrong.';
                $('#quickPatientError').text(msg).show();
            },
            complete: function () {
                btn.prop('disabled', false).html('<i class="tio-add"></i> Save Patient');
            }
        });
    });

    // Allow pressing Enter in the modal inputs to trigger save
    $('#quickPatientModal input').on('keydown', function (e) {
        if (e.key === 'Enter') $('#quickPatientSaveBtn').click();
    });

    function checkPatientOpValidity() {
        var pId = $('#patientSelect').val();
        var dId = $('#doctorSelect').val();
        if (!pId || pId === 'add_new') {
            $('#opValidityAlertWrap').addClass('d-none');
            return;
        }
        $.ajax({
            url: "{{ route('vendor.opd.check-patient-validity') }}",
            type: "GET",
            data: { patient_id: pId, doctor_profile_id: dId || '' },
            success: function(res) {
                if (res.active) {
                    var visitsInfo = res.consultations_used ? ' (' + res.consultations_used + ' visit(s) recorded)' : '';
                    $('#opValidityText').html('<strong>Active OP Receipt Valid until ' + res.valid_until + '</strong>' + visitsInfo + ' — <strong>Next Visit / Follow-Up (No Consultation Fee)</strong>');
                    $('#opValidityAlertWrap').removeClass('d-none');
                    if ($('#visitTypeSelect').length) {
                        $('#visitTypeSelect').val('followup').trigger('change').trigger('change.select2');
                    }
                } else {
                    $('#opValidityAlertWrap').addClass('d-none');
                }
            },
            error: function() {
                $('#opValidityAlertWrap').addClass('d-none');
            }
        });
    }

    $(document).on('change select2:select', '#patientSelect, #doctorSelect', checkPatientOpValidity);
    setTimeout(checkPatientOpValidity, 300);
});
</script>
@endpush
@endif
