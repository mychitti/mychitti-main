@extends('layouts.vendor.app')
@section('title', 'New Consent Form')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-document-text" style="font-size:22px;"></i></span>
            New Consent Form
            @if($admission)
                <small class="text-muted font-size-14 ml-2">— {{ $admission->admission_number }}</small>
            @endif
        </h1>
        <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">
            <i class="tio-arrow-backward"></i> Back
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <form method="POST" action="{{ route('vendor.consent.store') }}">
                @csrf

                <input type="hidden" name="ipd_admission_id" value="{{ $admission?->id }}">

                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0">Patient &amp; Template</h6></div>
                    <div class="card-body">
                        <div class="row">
                            {{-- Patient --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">Patient <span class="text-danger">*</span></label>
                                    @if($patient)
                                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                        <input type="text" class="form-control" readonly
                                            value="{{ $patient->name }} ({{ $patient->patient_uid }})">
                                    @else
                                        <select name="patient_id" class="form-control js-select2" required>
                                            <option value="">Select patient...</option>
                                            @foreach($patients as $p)
                                                <option value="{{ $p->id }}"
                                                    {{ old('patient_id') == $p->id ? 'selected' : '' }}>
                                                    {{ $p->name }} ({{ $p->patient_uid }})
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                    @error('patient_id')<div class="text-danger" style="font-size:12px;">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            {{-- Template picker --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">Load from Template</label>
                                    <select id="templatePicker" name="consent_template_id" class="form-control js-select2">
                                        <option value="">— Custom / No Template —</option>
                                        @foreach($templates as $tpl)
                                            <option value="{{ $tpl->id }}">{{ $tpl->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0">Consent Details</h6></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="input-label">Consent Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="consentTitle"
                                class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title') }}"
                                placeholder="e.g. General Consent for Treatment" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="input-label">Consent Text <span class="text-danger">*</span></label>
                            <textarea name="content" id="consentText"
                                class="form-control @error('content') is-invalid @enderror"
                                rows="14" required
                                placeholder="The consent text will appear here — or type a custom consent...">{{ old('content') }}</textarea>
                            @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0">Signatures</h6></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="input-label">Patient / Signatory Name</label>
                                    <input type="text" name="signatory_name"
                                        class="form-control form-control-sm"
                                        value="{{ old('signatory_name', $patient?->name) }}"
                                        placeholder="Name of person signing">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="input-label">Witness Name</label>
                                    <input type="text" name="witness_name"
                                        class="form-control form-control-sm"
                                        value="{{ old('witness_name') }}"
                                        placeholder="Witness name">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="input-label">Date &amp; Time Signed</label>
                                    <input type="datetime-local" name="signed_at"
                                        class="form-control form-control-sm"
                                        value="{{ old('signed_at', now()->format('Y-m-d\TH:i')) }}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group mb-0">
                                    <label class="input-label">Notes</label>
                                    <input type="text" name="notes"
                                        class="form-control form-control-sm"
                                        value="{{ old('notes') }}"
                                        placeholder="Any additional notes…">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end mb-4">
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn--primary">
                        <i class="tio-save"></i> Save Consent
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
const TEMPLATE_URL   = "{{ route('vendor.consent.template-content', ':id') }}".replace(':id', '');
const PATIENT_ID     = "{{ $patient?->id }}";
const ADMISSION_ID   = "{{ $admission?->id }}";

$('#templatePicker').on('change', function () {
    const id = $(this).val();
    if (!id) return;

    $.getJSON(TEMPLATE_URL + id, {
        patient_id:   PATIENT_ID   || $('#patientSelect').val() || '',
        admission_id: ADMISSION_ID || '',
    }, function (data) {
        $('#consentTitle').val(data.title);
        $('#consentText').val(data.content);
    });
});
</script>
@endpush
