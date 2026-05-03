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
                <strong>{{ $visit->visit_date?->format('d M Y') }}</strong>
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
            <div class="col-md-6">
                <div class="form-group">
                    <label class="input-label">Visit Date <span class="text-danger">*</span></label>
                    <input type="date" name="visit_date" class="form-control @error('visit_date') is-invalid @enderror"
                        value="{{ old('visit_date', now()->toDateString()) }}" required>
                    @error('visit_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                <div class="form-group">
                    <label class="input-label">Name <span class="text-danger">*</span></label>
                    <input type="text" id="qp_name" class="form-control" placeholder="Full name" >
                </div>
                <div class="form-group">
                    <label class="input-label">Phone Number</label>
                    <input type="text" id="qp_phone" class="form-control" placeholder="Phone number">
                </div>
                <div class="form-group mb-0">
                    <label class="input-label">Address</label>
                    <input type="text" id="qp_address" class="form-control" placeholder="Address">
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
            $('#qp_name, #qp_phone, #qp_address').val('');
            $('#quickPatientError').hide();
            $('#quickPatientModal').modal('show');
        }
    });

    // Save new patient via AJAX
    $('#quickPatientSaveBtn').on('click', function () {
        const name    = $('#qp_name').val().trim();
        const phone   = $('#qp_phone').val().trim();
        const address = $('#qp_address').val().trim();

        if (!name) {
            $('#quickPatientError').text('Name is required.').show();
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Saving...');
        $('#quickPatientError').hide();

        $.ajax({
            url: '{{ route("vendor.patient.quick-save") }}',
            method: 'POST',
            data: {
                _token:  '{{ csrf_token() }}',
                name:    name,
                phone:   phone,
                address: address,
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
