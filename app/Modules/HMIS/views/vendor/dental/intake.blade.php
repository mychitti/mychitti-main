@extends('layouts.vendor.app')

{{-- Served in place of the full New Patient form for dental stores (PatientController::index),
     and reachable directly at /dental-intake. Same screen either way. --}}
@section('title', 'New Patient')

@push('css_or_js')
    <style>
        .di-card { background:#fff; border:1px solid #edf0f5; border-radius:10px; box-shadow:0 1px 2px rgba(16,24,40,.04); margin-bottom:12px; }
        .di-card .hd { padding:9px 14px; border-bottom:1px solid #edf0f5; font-weight:700; font-size:13px; }
        .di-card .bd { padding:14px; }
        .di-req { color:#dc3545; }

        /* Compact intake: this screen is filled in at a busy front desk, so every field should be
           reachable without scrolling. Scoped to .di-card so the shared vendor form styles are
           left alone everywhere else. */
        .di-card .form-group { margin-bottom:10px; }
        .di-card .input-label { font-size:11.5px; font-weight:600; color:#56606e; margin-bottom:3px; }
        .di-card .form-control { font-size:13px; padding:5px 10px; height:34px; border-radius:7px; }
        .di-card textarea.form-control { height:auto; min-height:46px; }
        .di-card select.form-control { height:34px; padding-top:3px; padding-bottom:3px; }
        /* The complaint picker is a <select multiple> that Select2 replaces. The fixed height
           above would squash it on the fallback path where Select2 has not loaded, and Select2's
           own container sizes itself, so leave both alone. */
        .di-card select[multiple].form-control { height:auto; }
        .di-page-header { margin-bottom:12px; }
        .di-page-header .page-header-title { font-size:19px; margin-bottom:2px; }
        .di-page-header .page-header-text { font-size:12px; }
        .di-actions .btn { font-size:13px; padding:6px 16px; }

        .custom-header-btn { border-radius:999px; font-size:11.5px; font-weight:600; padding:3px 11px; }
        .custom-field label { font-size:11.5px; font-weight:600; color:#56606e; margin-bottom:3px; }
        .custom-field .remove-field { align-self:center; font-size:17px; padding:0 4px; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header di-page-header">
            <h1 class="page-header-title"><i class="tio-user-add"></i> New Patient</h1>
            <p class="page-header-text mb-0">Registers the patient and opens today's visit in one step.</p>
        </div>

        <form action="{{ route('vendor.dental-intake.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-lg-7">
                    <div class="di-card">
                        <div class="hd">Patient</div>
                        <div class="bd">
                            {{-- Name, phone, age and gender share one row: four short answers that
                                 the desk reads straight off the patient, and stacking them pushed the
                                 problem picker below the fold. --}}
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="input-label">Patient Name <span class="di-req">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name') }}" maxlength="150" placeholder="Full name for the bill"
                                        required autofocus>
                                    @error('name')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="input-label">Phone <span class="di-req">*</span></label>
                                    {{-- Deliberately type="text", not type="tel": the layout attaches
                                         intl-tel-input to the first tel input on the page, which puts a
                                         country flag on the box and auto-inserts "+91 " — neither of
                                         which can live with a hard 10-digit cap. --}}
                                    <input type="text" name="phone" id="di-phone"
                                        class="form-control @error('phone') is-invalid @enderror"
                                        value="{{ old('phone') }}" maxlength="10" inputmode="numeric"
                                        autocomplete="off" placeholder="10-digit mobile" required>
                                    <small class="text-danger d-none" id="di-phone-err" style="font-size:11px;"></small>
                                    @error('phone')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="input-label">Age <span class="di-req">*</span></label>
                                    <input type="number" name="age" class="form-control @error('age') is-invalid @enderror"
                                        value="{{ old('age') }}" min="0" max="150" placeholder="Years" required>
                                    @error('age')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="input-label">Gender <span class="di-req">*</span></label>
                                    <select name="gender" class="form-control @error('gender') is-invalid @enderror" required>
                                        <option value="">Select</option>
                                        @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $key => $label)
                                            <option value="{{ $key }}" {{ old('gender') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('gender')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            {{-- Doctor. A one-doctor clinic is not asked — the visit is theirs by
                                 definition, and the controller resolves it either way. The field
                                 appears only where there is genuinely a choice to make. --}}
                            @if (($doctors ?? collect())->count() > 1)
                                <div class="form-group">
                                    <label class="input-label">Doctor <span class="di-req">*</span></label>
                                    <select name="doctor_profile_id" id="intakeDoctorSelect"
                                            class="form-control @error('doctor_profile_id') is-invalid @enderror" required>
                                        <option value="">Select doctor...</option>
                                        @foreach ($doctors as $doc)
                                            <option value="{{ $doc->id }}" {{ old('doctor_profile_id') == $doc->id ? 'selected' : '' }}>
                                                Dr. {{ $doc->employee?->f_name }} {{ $doc->employee?->l_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('doctor_profile_id')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                                </div>
                            @elseif (($doctors ?? collect())->count() === 1)
                                <input type="hidden" name="doctor_profile_id" value="{{ $doctors->first()->id }}">
                            @endif

                            <div class="form-group">
                                <label class="input-label">Address <span class="di-req">*</span></label>
                                <textarea name="address" class="form-control @error('address') is-invalid @enderror"
                                    rows="1" maxlength="500" placeholder="House / street, area, city and pincode"
                                    required>{{ old('address') }}</textarea>
                                @error('address')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group mb-0">
                                <label class="input-label">Problem <span class="di-req">*</span></label>
                                @include('hmis::vendor.opd._complaint_picker', [
                                    'field'       => 'problem',
                                    'selected'    => old('problem', []),
                                    'options'     => $complaintOptions ?? [],
                                    'groups'      => $complaintGroups ?? [],
                                    'required'    => true,
                                    'placeholder' => 'Select or type what the patient has come in for…',
                                ])
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="di-card">
                        <div class="hd">More Info <span class="text-muted font-weight-normal">— optional</span></div>
                        <div class="bd">
                            {{-- Same interaction as the advanced bill's Custom Headers: a named chip drops a
                                 row with that label fixed, "+ Other" drops one where the label is typed too.
                                 Whatever is filled in here is carried onto the bill. --}}
                            <div id="custom-buttons" class="mb-2">
                                @foreach ($presetLabels as $label)
                                    <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                        data-label="{{ $label }}">+ {{ $label }}</button>
                                @endforeach
                                <button type="button" class="btn btn-outline-danger btn-sm mr-2 mb-2 custom-header-btn"
                                    data-label="Other">+ Other</button>
                            </div>

                            <div id="custom-fields"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap di-actions" style="gap:8px;">
                <button type="submit" name="action" value="visit" class="btn btn--primary">Register Patient</button>
                <button type="submit" name="action" value="bill" class="btn btn-outline-primary">Register &amp; Generate Bill</button>
                <a href="{{ route('vendor.patient.list') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@push('script_2')
    <script>
        // Matches the doctor picker on the OPD register, which is the screen this one shortcuts.
        $(function () {
            if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && $('#intakeDoctorSelect').length) {
                $('#intakeDoctorSelect').select2({ placeholder: 'Select doctor...', width: '100%' });
            }
        });

        // Phone — checked as it is typed, and again on submit so a bad number can't get through by
        // pasting. Separators are allowed while typing and stripped before the check, since people
        // paste "+91 98765 43210" as readily as they key ten digits. The server applies the same
        // rule to the same normalised value (DentalIntakeController::store).
        (function () {
            const box = document.getElementById('di-phone');
            const err = document.getElementById('di-phone-err');
            if (!box) return;

            // The box holds the national number alone (no country code), so the rule is exact:
            // ten digits starting 6-9.
            function check(showWhileEmpty) {
                const v = box.value;
                let msg = '';

                if (v.length === 0) {
                    msg = showWhileEmpty ? 'Phone number is required.' : '';
                } else if (v.length < 10) {
                    msg = 'Phone number must be 10 digits — ' + (10 - v.length) + ' to go.';
                } else if (!/^[6-9]\d{9}$/.test(v)) {
                    msg = 'Not a valid mobile number — it should start with 6, 7, 8 or 9.';
                }

                err.textContent = msg;
                err.classList.toggle('d-none', msg === '');
                box.classList.toggle('is-invalid', msg !== '');
                box.setCustomValidity(msg);
                return msg === '';
            }

            // `input` rather than `keyup` so pasting and autofill are caught too. Anything that is
            // not a digit is dropped on the spot, and the value is cut at ten.
            box.addEventListener('input', function () {
                const kept = this.value.replace(/\D/g, '').slice(0, 10);
                if (kept !== this.value) this.value = kept;
                check(false);
            });

            box.addEventListener('blur', () => check(true));
            box.form.addEventListener('submit', function (e) {
                if (!check(true)) {
                    e.preventDefault();
                    box.focus();
                }
            });
        })();

        $(document).on('click', '.custom-header-btn', function () {
            const label = $(this).data('label');
            let row;

            if (label === 'Other') {
                // Free-form: the label is typed as well, and the chip stays so several can be added.
                row = `
                <div class="form-group custom-field" data-label="Other">
                    <div class="d-flex">
                        <input type="text" class="form-control mr-2" placeholder="Label" name="header_label[]">
                        <input type="text" class="form-control mr-2" placeholder="Value" name="header_field[]">
                        <a type="button" class="text-danger remove-field"><i class="tio-delete-outlined"></i></a>
                    </div>
                </div>`;
            } else {
                row = `
                <div class="form-group custom-field" data-label="${label}">
                    <label>${label}</label>
                    <div class="d-flex">
                        <input type="hidden" name="header_label[]" value="${label}">
                        <input type="text" class="form-control mr-2" placeholder="${label}" name="header_field[]">
                        <a type="button" class="text-danger remove-field"><i class="tio-delete-outlined"></i></a>
                    </div>
                </div>`;

                // A named field is one per patient — hide the chip until the row is removed again.
                $(this).hide();
            }

            $('#custom-fields').append(row);
        });

        $('#custom-fields').on('click', '.remove-field', function () {
            const $row  = $(this).closest('.custom-field');
            const label = $row.data('label');

            $('#custom-buttons button').each(function () {
                if ($(this).data('label') === label) {
                    $(this).show();
                }
            });

            $row.remove();
        });
    </script>
@endpush
