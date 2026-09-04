@extends('layouts.vendor.app')
@section('title', 'Beds — ' . $ward->ward_name)

@section('content')
<div class="content container-fluid">
    @include('hmis::vendor.hospital._hospital_submenu_header')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-hospital" style="font-size:22px;"></i></span>
            {{ $ward->ward_name }}
            <small class="text-muted font-size-14 ml-2">Bed Management</small>
        </h1>
        <a href="{{ route('vendor.ward.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-arrow-backward"></i> Wards
        </a>
    </div>
 
    {{-- Ward summary --}}
    <div class="card mb-3" style="background:linear-gradient(90deg,#eff6ff,#f0fdf4); border:1px solid #bfdbfe;">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap gap-4">
                <div>
                    <small class="text-muted d-block">Type</small>
                    <strong>{{ \App\Models\Ward::TYPES[$ward->ward_type] ?? $ward->ward_type }}</strong>
                </div>
                @if($ward->floor)
                <div>
                    <small class="text-muted d-block">Floor</small>
                    <strong>{{ $ward->floor }}</strong>
                </div>
                @endif
                <div>
                    <small class="text-muted d-block">Total Beds</small>
                    <strong>{{ $ward->total_beds }}</strong>
                </div>
                <div>
                    <small class="text-muted d-block">Available</small>
                    <strong class="text-success">{{ $beds->where('status','available')->count() }}</strong>
                </div>
                <div>
                    <small class="text-muted d-block">Occupied</small>
                    <strong class="text-danger">{{ $beds->where('status','occupied')->count() }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Add bed form --}}
    @if (hasPermission('bed', 'add'))
    <div class="card mb-3">
        <div class="card-header py-2">
            <h6 class="mb-0">Add Bed</h6>
        </div>
        <div class="card-body py-3">
            <form action="{{ route('vendor.ward.bed.store', $ward->id) }}" method="POST">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="input-label mb-1" style="font-size:12px;">Bed Number *</label>
                        <input type="text" name="bed_number" class="form-control form-control-sm @error('bed_number') is-invalid @enderror"
                            placeholder="e.g. A-101" value="{{ old('bed_number') }}">
                        @error('bed_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="input-label mb-1" style="font-size:12px;">Bed Type</label>
                        <select name="bed_type" class="form-control form-control-sm">
                            @foreach(\App\Models\Bed::TYPES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="input-label mb-1" style="font-size:12px;">Daily Charge (₹) — blank = ward default</label>
                        <input type="number" name="daily_charge" class="form-control form-control-sm"
                            placeholder="{{ $ward->daily_charge }}" min="0" step="0.01">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn--primary btn-sm w-100" style="margin-top:22px;">
                            <i class="tio-add"></i> Add Bed
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if(hasPermission('bed', 'list'))
    {{-- Beds grid --}}
    <div class="row">
        @forelse($beds as $bed)
        @php
            $statusColor = match($bed->status) {
                'available'   => '#d1fae5',
                'occupied'    => '#fee2e2',
                'reserved'    => '#fef9c3',
                'maintenance' => '#f3f4f6',
                default       => '#f3f4f6',
            };
            $admission = $bed->activeAdmission;
        @endphp
        <div class="col-6 col-md-4 col-lg-3 col-xl-2 mb-3">
            <div class="card h-100 text-center" style="border:1.5px solid #c8d2e0; background:{{ $statusColor }};">
                <div class="card-body p-2">
                    <div class="font-size-18 font-weight-bold">{{ $bed->bed_number }}</div>
                    <small class="text-muted d-block">{{ \App\Models\Bed::TYPES[$bed->bed_type] ?? $bed->bed_type }}</small>
                    <span class="badge mt-1
                        {{ $bed->status === 'available' ? 'badge-success' :
                           ($bed->status === 'occupied' ? 'badge-danger' :
                           ($bed->status === 'reserved' ? 'badge-warning' : 'badge-secondary')) }}">
                        {{ ucfirst($bed->status) }}
                    </span>
                    @if($admission?->patient)
                        <div class="mt-1" style="font-size:11px;">
                            <i class="tio-user"></i> {{ $admission->patient->name }}
                        </div>
                    @endif
                    @if($bed->daily_charge > 0)
                        <div style="font-size:11px;" class="text-muted mt-1">₹{{ number_format($bed->daily_charge, 0) }}/day</div>
                    @endif
                </div>
                <div class="card-footer p-1 d-flex justify-content-center gap-1">
                    @if (hasPermission('bed', 'edit'))
                    <button type="button" class="btn btn-xs btn-outline-secondary"
                        onclick="editBed({{ $bed->id }}, '{{ $bed->bed_number }}', '{{ $bed->bed_type }}', '{{ $bed->status }}', {{ $bed->daily_charge }})">
                        <i class="tio-edit"></i>
                    </button>
                    @endif
                    @if($bed->status !== 'occupied' && (hasPermission('bed', 'delete')))
                    <form action="{{ route('vendor.ward.bed.destroy', [$ward->id, $bed->id]) }}" method="POST"
                          onsubmit="return confirm('Delete this bed?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-xs btn-outline-danger"><i class="tio-delete-outlined"></i></button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info">No beds added yet. Use the form above to add beds.</div>
        </div>
        @endforelse
    </div>
    @endif
</div>

{{-- Edit bed modal --}}
<div class="modal fade" id="editBedModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Edit Bed</h6>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editBedForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label class="input-label" style="font-size:12px;">Bed Number</label>
                        <input type="text" name="bed_number" id="editBedNumber" class="form-control form-control-sm">
                    </div>
                    <div class="form-group">
                        <label class="input-label" style="font-size:12px;">Bed Type</label>
                        <select name="bed_type" id="editBedType" class="form-control form-control-sm">
                            @foreach(\App\Models\Bed::TYPES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="input-label" style="font-size:12px;">Status</label>
                        <select name="status" id="editBedStatus" class="form-control form-control-sm">
                            <option value="available">Available</option>
                            <option value="reserved">Reserved</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="input-label" style="font-size:12px;">Daily Charge (₹)</label>
                        <input type="number" name="daily_charge" id="editBedCharge" class="form-control form-control-sm" min="0" step="0.01">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" class="btn btn--primary btn-sm">Update</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
function editBed(bedId, bedNumber, bedType, status, charge) {
    const wardId = {{ $ward->id }};
    document.getElementById('editBedForm').action = `/vendor/ward/${wardId}/bed/${bedId}/update`;
    document.getElementById('editBedNumber').value = bedNumber;
    document.getElementById('editBedType').value   = bedType;
    document.getElementById('editBedStatus').value = status;
    document.getElementById('editBedCharge').value = charge;
    $('#editBedModal').modal('show');
}
</script>
@endpush
