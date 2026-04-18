@extends('layouts.vendor.app')
@section('title', 'Bed Dashboard')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-grid-squares" style="font-size:22px;"></i></span>
            Bed Availability Dashboard
        </h1>
        <div class="d-flex gap-2">
            <a href="{{ route('vendor.ipd.create') }}" class="btn btn--primary btn-sm">
                <i class="tio-add"></i> Admit Patient
            </a>
            <a href="{{ route('vendor.ipd.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-list"></i> Admissions List
            </a>
        </div>
    </div>

    {{-- Legend --}}
    <div class="d-flex gap-3 mb-3 flex-wrap">
        <span><span class="badge badge-success">■</span> Available</span>
        <span><span class="badge badge-danger">■</span> Occupied</span>
        <span><span class="badge badge-warning">■</span> Reserved</span>
        <span><span class="badge badge-secondary">■</span> Maintenance</span>
    </div>

    @forelse($wards as $ward)
    @php
        $totalBeds = $ward->beds->count();
        $availableBeds = $ward->beds->where('status','available')->count();
        $occupiedBeds  = $ward->beds->where('status','occupied')->count();
    @endphp
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center py-2"
             style="background:linear-gradient(90deg,#eff6ff,#f0fdf4); border-bottom:1px solid #bfdbfe;">
            <div>
                <strong>{{ $ward->ward_name }}</strong>
                <span class="badge badge-soft-primary ml-1">{{ \App\Models\Ward::TYPES[$ward->ward_type] ?? $ward->ward_type }}</span>
                @if($ward->floor)
                    <small class="text-muted ml-2">Floor {{ $ward->floor }}</small>
                @endif
            </div>
            <div class="text-right">
                <span class="badge badge-success mr-1">{{ $availableBeds }} available</span>
                <span class="badge badge-danger mr-1">{{ $occupiedBeds }} occupied</span>
                <span class="text-muted font-size-12">{{ $totalBeds }} total</span>
            </div>
        </div>
        <div class="card-body p-3">
            @if($ward->beds->isEmpty())
                <p class="text-muted mb-0 text-center">No beds configured.
                    <a href="{{ route('vendor.ward.beds', $ward->id) }}">Add beds</a>
                </p>
            @else
            <div class="d-flex flex-wrap gap-2">
                @foreach($ward->beds as $bed)
                @php
                    $statusClass = match($bed->status) {
                        'available'   => 'border-success text-success',
                        'occupied'    => 'border-danger text-danger',
                        'reserved'    => 'border-warning text-warning',
                        'maintenance' => 'border-secondary text-secondary',
                        default       => 'border-secondary',
                    };
                    $bgClass = match($bed->status) {
                        'available'   => '#f0fdf4',
                        'occupied'    => '#fef2f2',
                        'reserved'    => '#fefce8',
                        'maintenance' => '#f9fafb',
                        default       => '#f9fafb',
                    };
                    $patient = $bed->activeAdmission?->patient;
                @endphp
                <div class="text-center p-2 rounded border {{ $statusClass }}"
                     style="min-width:90px; max-width:110px; background:{{ $bgClass }}; font-size:12px; cursor:{{ $bed->status === 'occupied' ? 'pointer' : 'default' }};"
                     @if($bed->activeAdmission) onclick="window.location='{{ route('vendor.ipd.show', $bed->activeAdmission->id) }}'" @endif
                     title="{{ $bed->status === 'occupied' && $patient ? $patient->name : ucfirst($bed->status) }}">
                    <div class="font-weight-bold font-size-14">{{ $bed->bed_number }}</div>
                    <div style="font-size:11px;">{{ ucfirst($bed->status) }}</div>
                    @if($patient)
                        <div class="text-truncate" style="font-size:10px; max-width:90px;">{{ $patient->name }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="card text-center py-5">
        <div class="text-muted">
            <i class="tio-hospital" style="font-size:48px; opacity:0.3;"></i>
            <p class="mt-2">No active wards found.</p>
            <a href="{{ route('vendor.ward.create') }}" class="btn btn--primary btn-sm">Add Ward</a>
        </div>
    </div>
    @endforelse
</div>
@endsection
