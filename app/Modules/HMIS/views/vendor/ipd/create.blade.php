@extends('layouts.vendor.app')
@section('title', 'Admit Patient — IPD')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-hospital" style="font-size:22px;"></i></span>
            Admit Patient (IPD)
        </h1>
        <a href="{{ route('vendor.ipd.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-arrow-backward"></i> Back
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form action="{{ route('vendor.ipd.store') }}" method="POST" id="ipdForm">
                @csrf

                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0">Patient &amp; Doctor</h6></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">Patient <span class="text-danger">*</span></label>
                                    <select name="patient_id" class="form-control js-select2 @error('patient_id') is-invalid @enderror" required>
                                        <option value="">Select patient...</option>
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
                                    <label class="input-label">Attending Doctor <span class="text-danger">*</span></label>
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
                                    <label class="input-label">Admission Date <span class="text-danger">*</span></label>
                                    <input type="date" name="admission_date" class="form-control @error('admission_date') is-invalid @enderror"
                                        value="{{ old('admission_date', now()->toDateString()) }}" required>
                                    @error('admission_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0">Ward &amp; Bed Allotment</h6></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">Ward <span class="text-danger">*</span></label>
                                    <select name="ward_id" id="wardSelect"
                                        class="form-control @error('ward_id') is-invalid @enderror" required
                                        onchange="loadAvailableBeds(this.value)">
                                        <option value="">Select ward...</option>
                                        @foreach($wards as $ward)
                                            <option value="{{ $ward->id }}" {{ old('ward_id') == $ward->id ? 'selected' : '' }}>
                                                {{ $ward->ward_name }}
                                                ({{ \App\Models\Ward::TYPES[$ward->ward_type] ?? $ward->ward_type }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('ward_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">Bed <span class="text-danger">*</span></label>
                                    <select name="bed_id" id="bedSelect"
                                        class="form-control @error('bed_id') is-invalid @enderror" required>
                                        <option value="">Select ward first...</option>
                                    </select>
                                    @error('bed_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0">Clinical Details</h6></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="input-label">Diagnosis</label>
                            <input type="text" name="diagnosis" class="form-control @error('diagnosis') is-invalid @enderror"
                                value="{{ old('diagnosis') }}" placeholder="Primary diagnosis...">
                        </div>
                        <div class="form-group">
                            <label class="input-label">Reason for Admission</label>
                            <textarea name="admission_reason" class="form-control" rows="3"
                                placeholder="Detailed reason for admission...">{{ old('admission_reason') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn--primary">Admit Patient</button>
                    <a href="{{ route('vendor.ipd.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
function loadAvailableBeds(wardId) {
    const bedSel = document.getElementById('bedSelect');
    bedSel.innerHTML = '<option value="">Loading...</option>';

    if (!wardId) {
        bedSel.innerHTML = '<option value="">Select ward first...</option>';
        return;
    }

    fetch(`{{route('vendor.ipd.available-beds')}}?ward_id=${wardId}`)
        .then(r => r.json())
        .then(beds => {
            if (!beds.length) {
                bedSel.innerHTML = '<option value="">No available beds</option>';
                return;
            }
            bedSel.innerHTML = '<option value="">Select bed...</option>';
            beds.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.id;
                opt.textContent = `Bed ${b.bed_number} (${b.bed_type.replace('_', ' ')})${b.daily_charge > 0 ? ' — ₹'+b.daily_charge+'/day' : ''}`;
                bedSel.appendChild(opt);
            });
        })
        .catch(() => {
            bedSel.innerHTML = '<option value="">Error loading beds</option>';
        });
}

// On page load if ward was pre-selected (e.g., validation error)
const selectedWard = document.getElementById('wardSelect').value;
if (selectedWard) loadAvailableBeds(selectedWard);
</script>
@endpush
