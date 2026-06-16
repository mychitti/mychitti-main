@extends('layouts.vendor.app')
@section('title', $template ? 'Edit Template' : 'New Consent Template')

@section('content')
<div class="content container-fluid">
    @include('hmis::vendor.hospital._hospital_submenu_header')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-document-text" style="font-size:22px;"></i></span>
            {{ $template ? 'Edit Template' : 'New Consent Template' }}
        </h1>
        <a href="{{ route('vendor.consent.template.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-arrow-backward"></i> Back
        </a>
    </div>
 
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form method="POST"
                  action="{{ $template ? route('vendor.consent.template.update', $template->id) : route('vendor.consent.template.store') }}">
                @csrf
                @if($template) @method('PUT') @endif

                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="input-label">Template Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title', $template?->title) }}"
                                placeholder="e.g. General Consent for Treatment" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="input-label">Consent Text <span class="text-danger">*</span></label>
                            <textarea name="content" id="consentContent"
                                class="form-control @error('content') is-invalid @enderror"
                                rows="16" required
                                placeholder="Enter the full consent form text here...">{{ old('content', $template?->content) }}</textarea>
                            @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">
                                You can use placeholders like <code>&#123;&#123;patient_name&#125;&#125;</code>,
                                <code>&#123;&#123;date&#125;&#125;</code>, <code>&#123;&#123;doctor_name&#125;&#125;</code>
                                — these will be auto-filled when generating a consent for a patient.
                            </small>
                        </div>

                        @if($template)
                        <div class="form-group mb-0">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="isActive"
                                    name="is_active" value="1"
                                    {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="isActive">Active</label>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="card-footer d-flex gap-2">
                        <button type="submit" class="btn btn--primary">
                            <i class="tio-save"></i> {{ $template ? 'Update' : 'Save Template' }}
                        </button>
                        <a href="{{ route('vendor.consent.template.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
