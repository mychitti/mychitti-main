@extends('layouts.vendor.app')
@section('title', 'Discharge — ' . $admission->admission_number)

@section('content')
<div class="content container-fluid">
    @include('hmis::vendor.hospital._hospital_submenu_header')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-hospital" style="font-size:22px;"></i></span>
            Discharge Patient
            <small class="text-muted font-size-14 ml-2">{{ $admission->admission_number }}</small>
        </h1>
        <a href="{{ route('vendor.ipd.show', $admission->id) }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-arrow-backward"></i> Back
        </a>
    </div>
 
    {{-- Patient / bed summary --}}
    <div class="card mb-3" style="background:linear-gradient(90deg,#eff6ff,#f0fdf4); border:1px solid #bfdbfe;">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap gap-4">
                <div>
                    <small class="text-muted d-block">Patient</small>
                    <strong>{{ $admission->patient?->name }}</strong>
                    <span class="text-muted">({{ $admission->patient?->patient_uid }})</span>
                </div>
                <div>
                    <small class="text-muted d-block">Ward / Bed</small>
                    <strong>{{ $admission->ward?->ward_name }}</strong> — Bed {{ $admission->bed?->bed_number }}
                </div>
                <div>
                    <small class="text-muted d-block">Admitted</small>
                    <strong>{{ $admission->admission_date?->format('d M Y') }}</strong>
                </div>
                <div>
                    <small class="text-muted d-block">Days</small>
                    <strong>{{ $admission->admission_date?->diffInDays(now()) + 1 }}</strong>
                </div>
                <div>
                    <small class="text-muted d-block">Est. Charges</small>
                    <strong>₹{{ number_format(($admission->admission_date?->diffInDays(now()) + 1) * $admission->daily_charge, 2) }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form action="{{ route('vendor.ipd.discharge', $admission->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0">Discharge Details</h6></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">Discharge Date <span class="text-danger">*</span></label>
                                    <input type="date" name="discharge_date" class="form-control @error('discharge_date') is-invalid @enderror"
                                        value="{{ old('discharge_date', now()->toDateString()) }}"
                                        min="{{ $admission->admission_date?->toDateString() }}"
                                        max="{{ now()->toDateString() }}" required>
                                    @error('discharge_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">Discharge Type <span class="text-danger">*</span></label>
                                    <select name="discharge_type" class="form-control @error('discharge_type') is-invalid @enderror" required>
                                        @foreach(\App\Models\IpdAdmission::DISCHARGE_TYPES as $key => $label)
                                            <option value="{{ $key }}" {{ old('discharge_type', 'normal') === $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('discharge_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="input-label">Discharge Summary</label>
                            <textarea name="discharge_summary" class="form-control" rows="6"
                                placeholder="Treatment given, current condition, follow-up instructions, medicines prescribed after discharge...">{{ old('discharge_summary') }}</textarea>
                            <small class="text-muted">Include treatment given, patient condition at discharge, medications, and follow-up plan.</small>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning d-flex align-items-center gap-2">
                    <i class="tio-warning-outlined" style="font-size:18px;"></i>
                    <div>
                        <strong>Confirm Discharge:</strong> This will free up <strong>Bed {{ $admission->bed?->bed_number }}</strong>
                        in <strong>{{ $admission->ward?->ward_name }}</strong>. This action cannot be undone.
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning"
                        onclick="return confirm('Confirm discharge of {{ addslashes($admission->patient?->name) }}?')">
                        <i class="tio-checkmark-circle"></i> Confirm Discharge
                    </button>
                    <a href="{{ route('vendor.ipd.show', $admission->id) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
