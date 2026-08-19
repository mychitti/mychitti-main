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
                    <select name="doctor_profile_id" class="form-control js-select2 @error('doctor_profile_id') is-invalid @enderror" required>
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
                    <select name="visit_type" class="form-control @error('visit_type') is-invalid @enderror" required>
                        @foreach(\App\Models\OpdVisit::VISIT_TYPES as $key => $label)
                            <option value="{{ $key }}" {{ old('visit_type', 'new') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('visit_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
    <select name="visit_type" class="form-control @error('visit_type') is-invalid @enderror" required>
        @foreach(\App\Models\OpdVisit::VISIT_TYPES as $key => $label)
            <option value="{{ $key }}" {{ old('visit_type', $visit->visit_type) === $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    @error('visit_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                    <div class="form-group col-md-4">
                        <label class="input-label">Age <span class="text-danger">*</span></label>
                        <input type="number" id="qp_age" class="form-control" min="0" max="150"
                            autocomplete="off" placeholder="Years">
                    </div>
                    <div class="form-group col-md-8">
                        <label class="input-label">Gender <span class="text-danger">*</span></label>
                        <select id="qp_gender" class="form-control">
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label">Address <span class="text-danger">*</span></label>
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

    // Intercept "Add New Patient" selection
    $('#patientSelect').on('select2:select', function (e) {
        if (e.params.data.id === 'add_new') {
            // Reset back to empty so the select stays "blank" until patient is created
            $(this).val('').trigger('change');
            $('#qp_name, #qp_phone, #qp_age, #qp_address').val('');
            $('#qp_gender').val('');
            $('#qp_phone').removeClass('is-invalid');
            $('#qp_phone_err').addClass('d-none').text('');
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
    }).on('blur', function () {
        qpPhoneCheck(true);
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
        if (!address) {
            $('#quickPatientError').text('Address is required.').show();
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
});
</script>
@endpush
@endif
