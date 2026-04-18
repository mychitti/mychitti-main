@extends('layouts.vendor.app')
@section('title', 'Ward Management')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-hospital" style="font-size:22px;"></i></span>
            Ward Management
        </h1>
        <a href="{{ route('vendor.ward.create') }}" class="btn btn--primary btn-sm">
            <i class="tio-add"></i> Add Ward
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        @forelse($wards as $ward)
        <div class="col-md-6 col-xl-4 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2"
                     style="background:linear-gradient(90deg,#eff6ff,#f0fdf4); border-bottom:1px solid #bfdbfe;">
                    <div>
                        <strong>{{ $ward->ward_name }}</strong>
                        <span class="badge badge-soft-primary ml-1">{{ \App\Models\Ward::TYPES[$ward->ward_type] ?? $ward->ward_type }}</span>
                        @if($ward->floor)
                            <small class="text-muted ml-1">Floor {{ $ward->floor }}</small>
                        @endif
                    </div>
                    <div class="d-flex gap-1">
                        <a href="{{ route('vendor.ward.edit', $ward->id) }}" class="btn btn-xs btn-outline-secondary">
                            <i class="tio-edit"></i>
                        </a>
                        <a href="{{ route('vendor.ward.beds', $ward->id) }}" class="btn btn-xs btn--primary">
                            Beds
                        </a>
                    </div>
                </div>
                <div class="card-body py-3">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="font-size-20 font-weight-bold text-primary">{{ $ward->total_beds }}</div>
                            <small class="text-muted">Total</small>
                        </div>
                        <div class="col-4">
                            <div class="font-size-20 font-weight-bold text-success">{{ $ward->available_beds_count }}</div>
                            <small class="text-muted">Available</small>
                        </div>
                        <div class="col-4">
                            <div class="font-size-20 font-weight-bold text-warning">{{ $ward->beds_count - $ward->available_beds_count }}</div>
                            <small class="text-muted">Occupied</small>
                        </div>
                    </div>
                    @if($ward->daily_charge > 0)
                    <div class="text-center mt-2">
                        <small class="text-muted">Daily Charge: <strong>₹{{ number_format($ward->daily_charge, 2) }}</strong></small>
                    </div>
                    @endif
                </div>
                <div class="card-footer py-2 d-flex justify-content-between align-items-center">
                    <span class="badge {{ $ward->is_active ? 'badge-success' : 'badge-secondary' }}">
                        {{ $ward->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <form action="{{ route('vendor.ward.destroy', $ward->id) }}" method="POST"
                          onsubmit="return confirm('Delete this ward and all its beds?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-xs btn-outline-danger">
                            <i class="tio-delete-outlined"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card text-center py-5">
                <div class="text-muted">
                    <i class="tio-hospital" style="font-size:48px; opacity:0.3;"></i>
                    <p class="mt-2">No wards created yet.</p>
                    <a href="{{ route('vendor.ward.create') }}" class="btn btn--primary btn-sm">Add First Ward</a>
                </div>
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection
