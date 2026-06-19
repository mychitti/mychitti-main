@extends('layouts.vendor.app')
@section('title', $test ? 'Radiology — Edit Scan' : 'Radiology — Add Scan')

@section('content')
<div class="content container-fluid"><div class="radx">
    @include('hmis::vendor.radiology._chrome')
    <div class="rad-body">
        <div class="card" style="max-width:640px">
            <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">🗂</div> {{ $test ? 'Edit Scan' : 'Add Scan' }}</h3>
                <a href="{{ route('vendor.radiology.catalog') }}" class="btn btn-outline btn-sm">← Back</a>
            </div>
            <div class="card-body">
                <form method="post" action="{{ $test ? route('vendor.radiology.catalog.update', $test->id) : route('vendor.radiology.catalog.store') }}">
                    @csrf
                    <div class="frow2">
                        <div class="fg"><label class="fl">Scan Name *</label><input class="fi" name="name" value="{{ old('name', $test->name ?? '') }}" placeholder="e.g. Chest X-Ray PA View" required></div>
                        <div class="fg"><label class="fl">Modality *</label>
                            <select class="fs" name="modality" required>
                                @foreach (['X-Ray','CT Scan','MRI','Ultrasound','ECG','Mammography','PET'] as $m)
                                    <option value="{{ $m }}" {{ old('modality', $test->modality ?? '') === $m ? 'selected' : '' }}>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="frow3">
                        <div class="fg"><label class="fl">Body Part</label><input class="fi" name="body_part" value="{{ old('body_part', $test->body_part ?? '') }}" placeholder="e.g. Chest"></div>
                        <div class="fg"><label class="fl">Price (₹) *</label><input class="fi" type="number" step="0.01" min="0" name="price" value="{{ old('price', $test->price ?? '') }}" required></div>
                        <div class="fg"><label class="fl">TAT (turnaround)</label><input class="fi" name="tat_text" value="{{ old('tat_text', $test->tat_text ?? '') }}" placeholder="e.g. 2 hrs"></div>
                    </div>
                    <div class="fg" style="margin:6px 0 14px">
                        <label class="fl" style="display:flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $test->is_active ?? 1) ? 'checked' : '' }}> Active (available for booking)
                        </label>
                    </div>
                    <div style="display:flex;justify-content:flex-end;gap:8px">
                        <a href="{{ route('vendor.radiology.catalog') }}" class="btn btn-outline">Cancel</a>
                        <button class="btn btn-primary">{{ $test ? 'Update Scan' : 'Save Scan' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div></div>
@endsection
