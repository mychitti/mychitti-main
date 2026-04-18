@extends('layouts.admin.app')
@section('title', 'Hospital Bed Pricing Tiers')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .tier-card { border-left: 4px solid #00868f; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex align-items-center justify-content-between">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-layers-outlined"></i></span>
            Hospital Bed Pricing Tiers
        </h1>
    </div>

    <div class="row">
        {{-- Add New Tier --}}
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">Add New Tier</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.plan.hospital-bed-tiers.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Tier Name <span class="text-danger">*</span></label>
                            <input type="text" name="tier_name" class="form-control" placeholder="e.g. Small Hospital" required>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Min Beds <span class="text-danger">*</span></label>
                                    <input type="number" name="min_beds" class="form-control" min="0" placeholder="1" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Max Beds</label>
                                    <input type="number" name="max_beds" class="form-control" min="1" placeholder="blank = unlimited">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" name="is_custom" id="add_is_custom" class="custom-control-input" value="1"
                                    onchange="document.getElementById('add_price_fields').style.display = this.checked ? 'none' : ''">
                                <label class="custom-control-label" for="add_is_custom">Custom Pricing (Contact Us)</label>
                            </div>
                        </div>

                        <div id="add_price_fields">
                            <div class="form-group">
                                <label>Price / Month (₹) <span class="text-danger">*</span></label>
                                <input type="number" name="price_monthly" class="form-control" min="0" step="0.01" placeholder="e.g. 3500">
                            </div>
                            {{-- <div class="form-group">
                                <label>Price / Year (₹)</label>
                                <input type="number" name="price_yearly" class="form-control" min="0" step="0.01" placeholder="e.g. 35000 (optional)">
                            </div> --}}
                        </div>

                        <button type="submit" class="btn btn--primary btn-block mt-2">Add Tier</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Existing Tiers --}}
        <div class="col-lg-8">
            @if($tiers->isEmpty())
                <div class="card">
                    <div class="card-body text-center text-muted py-5">No pricing tiers configured yet.</div>
                </div>
            @else
                @foreach($tiers as $tier)
                <div class="card mb-3 tier-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1">
                                    {{ $tier->tier_name }}
                                    @if($tier->is_custom) <span class="badge badge-secondary ml-1">Custom Pricing</span> @endif
                                    @if(!$tier->is_active) <span class="badge badge-light ml-1">Inactive</span> @endif
                                </h5>
                                <p class="text-muted mb-0">
                                    <strong>Beds:</strong> {{ $tier->bed_range }}
                                    @if(!$tier->is_custom)
                                        &nbsp;|&nbsp; <strong>₹{{ number_format($tier->price_monthly) }}/month</strong>
                                        @if($tier->price_yearly > 0)
                                            &nbsp;|&nbsp; ₹{{ number_format($tier->price_yearly) }}/year
                                        @endif
                                    @else
                                        &nbsp;|&nbsp; Contact for pricing
                                    @endif
                                </p>
                            </div>
                            <div class="d-flex" style="gap:8px;">
                                <button class="btn btn-sm btn-outline-primary" data-toggle="collapse" data-target="#editTier{{ $tier->id }}">
                                    <i class="tio-edit"></i>
                                </button>
                                <form action="{{ route('admin.plan.hospital-bed-tiers.destroy', $tier->id) }}" method="POST"
                                    onsubmit="return confirm('Delete this tier?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
                                </form>
                            </div>
                        </div>

                        {{-- Inline Edit --}}
                        <div class="collapse mt-3" id="editTier{{ $tier->id }}">
                            <hr>
                            <form action="{{ route('admin.plan.hospital-bed-tiers.update', $tier->id) }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Tier Name</label>
                                            <input type="text" name="tier_name" class="form-control" value="{{ $tier->tier_name }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Min Beds</label>
                                            <input type="number" name="min_beds" class="form-control" value="{{ $tier->min_beds }}" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Max Beds</label>
                                            <input type="number" name="max_beds" class="form-control" value="{{ $tier->max_beds }}" placeholder="blank = unlimited">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" name="is_custom" id="ec_{{ $tier->id }}" class="custom-control-input" value="1"
                                                {{ $tier->is_custom ? 'checked' : '' }}
                                                onchange="document.getElementById('ep_{{ $tier->id }}').style.display = this.checked ? 'none' : ''">
                                            <label class="custom-control-label" for="ec_{{ $tier->id }}">Custom Pricing</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" name="is_active" id="ea_{{ $tier->id }}" class="custom-control-input" value="1"
                                                {{ $tier->is_active ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="ea_{{ $tier->id }}">Active</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label>Sort Order</label>
                                        <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $tier->sort_order }}" min="0">
                                    </div>
                                </div>

                                <div id="ep_{{ $tier->id }}" style="{{ $tier->is_custom ? 'display:none' : '' }}">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Price / Month (₹)</label>
                                                <input type="number" name="price_monthly" class="form-control" value="{{ $tier->price_monthly }}" min="0" step="0.01">
                                            </div>
                                        </div>
                                        {{-- <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Price / Year (₹)</label>
                                                <input type="number" name="price_yearly" class="form-control" value="{{ $tier->price_yearly }}" min="0" step="0.01">
                                            </div>
                                        </div> --}}
                                    </div>
                                </div>

                                <button type="submit" class="btn btn--primary btn-sm">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection
