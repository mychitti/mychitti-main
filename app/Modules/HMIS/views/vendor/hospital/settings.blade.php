@extends('layouts.vendor.app')
@section('title', 'Hospital Settings')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-settings-outlined" style="font-size:22px;"></i></span>
            Hospital Settings
        </h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <form action="{{ route('vendor.hospital.settings.save') }}" method="POST">
                @csrf

                {{-- MUID Format --}}
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-label mr-1"></i> MUID Format</h6>
                    </div>
                    <div class="card-body">

                        <div class="form-group">
                            <label class="input-label">Prefix <span class="text-danger">*</span></label>
                            <input type="text" name="prefix" class="form-control @error('prefix') is-invalid @enderror"
                                   value="{{ old('prefix', $prefix) }}" maxlength="10"
                                   placeholder="e.g. P, MH, PAT"
                                   oninput="updatePreview()" id="prefixInput">
                            <small class="text-muted">Letters, numbers, hyphens and underscores only.</small>
                            @error('prefix')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="input-label">Zero-padding digits <span class="text-danger">*</span></label>
                            <input type="number" name="padding" class="form-control @error('padding') is-invalid @enderror"
                                   value="{{ old('padding', $padding) }}" min="1" max="10"
                                   oninput="updatePreview()" id="paddingInput">
                            <small class="text-muted">Number of digits in the serial (e.g. 5 → 00001).</small>
                            @error('padding')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group mb-0">
                            <label class="input-label">Minimum serial number <span class="text-danger">*</span></label>
                            <input type="number" name="serial" class="form-control @error('serial') is-invalid @enderror"
                                   value="{{ old('serial', $serial) }}" min="1"
                                   oninput="updatePreview()" id="serialInput">
                            <small class="text-muted">New patients will start from this number (if current count is lower).</small>
                            @error('serial')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted" style="font-size:12px;">Next MUID preview: </span>
                            <code id="muidPreview" style="font-size:14px; font-weight:700; color:#1d4ed8;">{{ $previewMuid }}</code>
                        </div>
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </div>

                {{-- OP Consultation Validity --}}
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-receipt mr-1"></i> OP Consultation Validity</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="input-label">Consultations per paid OP <span class="text-danger">*</span></label>
                            <input type="number" name="opd_consultation_count"
                                   class="form-control @error('opd_consultation_count') is-invalid @enderror"
                                   value="{{ old('opd_consultation_count', $opd_consultation_count) }}" min="1" max="50">
                            <small class="text-muted">How many consultations one paid OP receipt covers (e.g. 2).</small>
                            @error('opd_consultation_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group mb-0">
                            <label class="input-label">Validity (days) <span class="text-danger">*</span></label>
                            <input type="number" name="opd_consultation_validity_days"
                                   class="form-control @error('opd_consultation_validity_days') is-invalid @enderror"
                                   value="{{ old('opd_consultation_validity_days', $opd_consultation_validity_days) }}" min="1" max="365">
                            <small class="text-muted">Days a paid OP stays valid for follow-up visits (e.g. 7 = 1 week).</small>
                            @error('opd_consultation_validity_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
function updatePreview() {
    const prefix  = (document.getElementById('prefixInput').value || 'P').toUpperCase();
    const padding = parseInt(document.getElementById('paddingInput').value) || 5;
    const serial  = parseInt(document.getElementById('serialInput').value) || 1;
    const padded  = String(serial).padStart(padding, '0');
    document.getElementById('muidPreview').textContent = prefix + '-' + padded;
}
</script>
@endpush
