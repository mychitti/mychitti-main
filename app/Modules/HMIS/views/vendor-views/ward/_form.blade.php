<div class="form-group">
    <label class="input-label">Ward Name <span class="text-danger">*</span></label>
    <input type="text" name="ward_name" class="form-control @error('ward_name') is-invalid @enderror"
        value="{{ old('ward_name', $ward->ward_name ?? '') }}" placeholder="e.g. General Ward A">
    @error('ward_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label class="input-label">Ward Type <span class="text-danger">*</span></label>
            <select name="ward_type" class="form-control @error('ward_type') is-invalid @enderror">
                @foreach(\App\Models\Ward::TYPES as $key => $label)
                    <option value="{{ $key }}"
                        {{ old('ward_type', $ward->ward_type ?? '') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('ward_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="input-label">Floor / Location</label>
            <input type="text" name="floor" class="form-control @error('floor') is-invalid @enderror"
                value="{{ old('floor', $ward->floor ?? '') }}" placeholder="e.g. Ground, 1st, 2nd">
            @error('floor')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label class="input-label">Total Beds <span class="text-danger">*</span></label>
            <input type="number" name="total_beds" class="form-control @error('total_beds') is-invalid @enderror"
                value="{{ old('total_beds', $ward->total_beds ?? '') }}" min="1" placeholder="10">
            @error('total_beds')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="input-label">Daily Charge (₹)</label>
            <input type="number" name="daily_charge" class="form-control @error('daily_charge') is-invalid @enderror"
                value="{{ old('daily_charge', $ward->daily_charge ?? '') }}" min="0" step="0.01" placeholder="0.00">
            @error('daily_charge')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
