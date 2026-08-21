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

    <form action="{{ route('vendor.hospital.settings.save') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-4">

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

            </div>

            <div class="col-lg-8">
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

                        {{-- Prescription languages — which ones the doctor is offered when writing
                             an Rx. Everything not ticked stays out of that dropdown, so a clinic
                             that writes English and Tamil never scrolls past twenty others. --}}
                        <div class="col-12">
                            <hr class="mt-1 mb-3">
                            <label class="input-label mb-1">Prescription Languages</label>
                            <p class="text-muted mb-2" style="font-size:12px;">
                                Tick the languages your doctors write prescriptions in. Only these appear
                                in the language dropdown on the prescription screen.
                            </p>
                            <div class="row no-gutters" style="max-height:220px; overflow-y:auto;">
                                @foreach (\App\Models\Prescription::LANGUAGES as $code => $label)
                                    <div class="col-md-4 col-sm-6">
                                        <label class="d-flex align-items-center mb-1"
                                            style="font-size:12.5px; cursor:{{ $code === 'en' ? 'default' : 'pointer' }};">
                                            <input type="checkbox" name="rx_languages[]" value="{{ $code }}"
                                                class="mr-2"
                                                {{ array_key_exists($code, $rxLanguages ?? []) ? 'checked' : '' }}
                                                {{ $code === 'en' ? 'checked disabled' : '' }}>
                                            {{ $label }}
                                            @if($code === 'en')
                                                <span class="text-muted ml-1" style="font-size:11px;">(always on)</span>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('rx_languages.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </div>

            </div>
        </div>
    </form>

    {{-- Department letterheads. A lab, pharmacy or scan centre frequently sits at its own
         address under its own GSTIN and its own registrations, so each keeps a separate
         identity block; anything left blank falls back to the hospital's own details. --}}
    <div class="card mb-3">
        <div class="card-header py-2">
            <h6 class="mb-0"><i class="tio-city mr-1"></i> Department Details &mdash; Address, GSTIN &amp; Licences</h6>
        </div>
        <div class="card-body p-0">
            <ul class="nav nav-tabs nav--tabs border-0 px-3 pt-3" role="tablist">
                @foreach ($departments as $key => $dept)
                    <li class="nav-item">
                        <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-toggle="tab"
                           href="#dept-{{ $key }}" role="tab">
                            {{ $dept['label'] }}
                            @if ($dept['licenses']->count())
                                <span class="badge badge-soft-info ml-1">{{ $dept['licenses']->count() }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content p-3">
                @foreach ($departments as $key => $dept)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="dept-{{ $key }}" role="tabpanel">
                        <form action="{{ route('vendor.hospital.department.save', $key) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label class="input-label">{{ $dept['label'] }} Name</label>
                                    <input type="text" name="display_name" class="form-control"
                                           value="{{ old('display_name', $dept['profile']->display_name) }}"
                                           placeholder="Prints on the report header">
                                    <small class="text-muted">Blank = the hospital's own name.</small>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label class="input-label">GSTIN</label>
                                    <input type="text" name="gst_no" class="form-control text-uppercase"
                                           value="{{ old('gst_no', $dept['profile']->gst_no) }}"
                                           maxlength="30" placeholder="e.g. 33ABCDE1234F1Z5">
                                </div>
                                <div class="col-md-2 form-group">
                                    <label class="input-label">Phone</label>
                                    <input type="text" name="phone" class="form-control"
                                           value="{{ old('phone', $dept['profile']->phone) }}" maxlength="40">
                                </div>
                                <div class="col-md-2 form-group">
                                    <label class="input-label">Email</label>
                                    <input type="email" name="email" class="form-control"
                                           value="{{ old('email', $dept['profile']->email) }}" maxlength="190">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="input-label">Address</label>
                                    <textarea name="address" class="form-control" rows="2" maxlength="500"
                                              placeholder="Door no., street, area">{{ old('address', $dept['profile']->address) }}</textarea>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label class="input-label">City</label>
                                    <input type="text" name="city" class="form-control"
                                           value="{{ old('city', $dept['profile']->city) }}" maxlength="100">
                                </div>
                                <div class="col-md-2 form-group">
                                    <label class="input-label">State</label>
                                    <select name="state" class="form-control">
                                        <option value="">Select</option>
                                        @foreach ($states as $state)
                                            <option value="{{ $state->id }}"
                                                {{ (string) old('state', $dept['profile']->state) === (string) $state->id ? 'selected' : '' }}>
                                                {{ $state->state_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label class="input-label">PIN Code</label>
                                    <input type="text" name="pincode" class="form-control"
                                           value="{{ old('pincode', $dept['profile']->pincode) }}" maxlength="20">
                                </div>
                            </div>

                            <hr class="mt-1 mb-3">

                            @include('hmis::vendor.hospital._licenses', [
                                'uid'         => $key,
                                'licenses'    => $dept['licenses'],
                                'note'        => \App\Models\HospitalDepartmentProfile::LICENSE_HINTS[$key]['note'] ?? '',
                                'suggestions' => \App\Models\HospitalDepartmentProfile::LICENSE_HINTS[$key]['types'] ?? [],
                            ])

                            <div class="text-right mt-3">
                                <button type="submit" class="btn btn--primary">Save {{ $dept['label'] }} Details</button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
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
