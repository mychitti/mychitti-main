@extends('layouts.vendor.app')
@section('title', 'Numbering Settings')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-settings mr-1"></i> Numbering Settings</h1>
        <a href="{{ route('vendor.school.students.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    <div class="row justify-content-center"><div class="col-lg-6">
        <form action="{{ route('vendor.school.students.settings.save') }}" method="POST">
            @csrf
            <div class="card mb-3">
                <div class="card-header py-3"><i class="tio-tag mr-1 text-primary"></i> Serial Numbering Scope</div>
                <div class="card-body">
                    <p class="text-muted" style="font-size:12px;">Applies to admission numbers, fee invoices &amp; receipts, enquiry numbers and certificate serials.</p>
                    <label class="d-flex align-items-start mb-2" style="cursor:pointer;">
                        <input type="radio" name="serial_scope" value="store" class="mt-1 mr-2" @checked(old('serial_scope', $serialScope)==='store')>
                        <span><b>Store-wide</b> — one continuous sequence across all branches (numbers never repeat).</span>
                    </label>
                    <label class="d-flex align-items-start mb-0" style="cursor:pointer;">
                        <input type="radio" name="serial_scope" value="branch" class="mt-1 mr-2" @checked(old('serial_scope', $serialScope)==='branch')>
                        <span><b>Per-branch</b> — each branch keeps its own sequence (every campus starts at 1).</span>
                    </label>
                </div>
            </div>

            <div class="card"><div class="card-body">
                <div class="form-group"><label class="input-label">Prefix *</label>
                    <input name="prefix" class="form-control" value="{{ old('prefix', $prefix) }}" maxlength="20" oninput="prev()" id="p">
                    @error('prefix')<small class="text-danger">{{ $message }}</small>@enderror</div>
                <div class="form-group"><label class="input-label">Zero-padding digits *</label>
                    <input type="number" name="padding" class="form-control" value="{{ old('padding', $padding) }}" min="1" max="10" oninput="prev()" id="pad"></div>
                <div class="form-group mb-0"><label class="input-label">Minimum serial *</label>
                    <input type="number" name="serial" class="form-control" value="{{ old('serial', $serial) }}" min="1" oninput="prev()" id="ser"></div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div><span class="text-muted" style="font-size:12px;">Next preview: </span>
                    <code id="adm" style="font-weight:700;color:#4f46e5;">{{ strtoupper($prefix) }}{{ str_pad($serial, $padding, '0', STR_PAD_LEFT) }}</code></div>
                <button class="btn btn--primary">Save</button>
            </div></div>
        </form>
    </div></div>
</div>
@endsection

@push('script_2')
<script>
function prev(){
    const p=(document.getElementById('p').value||'ADM').toUpperCase();
    const pad=parseInt(document.getElementById('pad').value)||4;
    const s=parseInt(document.getElementById('ser').value)||1;
    document.getElementById('adm').textContent=p+String(s).padStart(pad,'0');
}
</script>
@endpush
