@extends('layouts.vendor.app')
@section('title', 'Certificate Templates')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-settings mr-1"></i> Certificate Templates</h1>
        <a href="{{ route('vendor.school.certificates.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    <form action="{{ route('vendor.school.certificates.settings.save') }}" method="POST">
        @csrf 
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3"><div class="card-body">
                    <label class="input-label mb-2">Default Design Template</label>
                    <div class="row" id="designPicker">
                        @foreach($designs as $k => $label)
                            <div class="col-md-4 mb-2">
                                <label class="d-block mb-0" style="cursor:pointer;">
                                    <input type="radio" name="design" value="{{ $k }}" class="d-none design-radio" @checked(old('design', $design) === $k)>
                                    <div class="design-card border rounded text-center p-2" data-design="{{ $k }}">
                                        <div class="design-thumb design-thumb-{{ $k }} mb-2"></div>
                                        <small class="font-weight-bold d-block">{{ $label }}</small>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div></div>

                <div class="card mb-3"><div class="card-body">
                    <div class="form-group">
                        <label class="input-label">Transfer Certificate (TC)</label>
                        <textarea name="tc" class="form-control" rows="4" required>{{ old('tc', $tc) }}</textarea>
                        @error('tc')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label class="input-label">Bonafide Certificate</label>
                        <textarea name="bonafide" class="form-control" rows="4" required>{{ old('bonafide', $bonafide) }}</textarea>
                        @error('bonafide')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label class="input-label">Character Certificate</label>
                        <textarea name="character" class="form-control" rows="4" required>{{ old('character', $character) }}</textarea>
                        @error('character')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>
                <div class="card-footer text-right">
                    @if(hasPermission("certificates","edit"))<button class="btn btn--primary"><i class="tio-save"></i> Save Templates</button>@endif
                </div></div>
            </div>
            <div class="col-lg-4">
                <div class="card"><div class="card-body">
                    <h5 class="mb-2"><i class="tio-info-outined"></i> Available Tokens</h5>
                    <p class="text-muted" style="font-size:12px;">Click a token to copy. These are replaced with the student's details when a certificate is issued.</p>
                    <div>
                        @foreach($tokens as $t)
                            <code class="d-inline-block mb-1 mr-1 token" style="cursor:pointer; background:#eef2ff; color:#4338ca; padding:2px 6px; border-radius:4px;">{{ $t }}</code>
                        @endforeach
                    </div>
                </div></div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('css_or_js')
<style>
    .design-card { transition:.15s; }
    .design-card:hover { border-color:#a5b4fc !important; }
    .design-radio:checked + .design-card { border-color:#4f46e5 !important; box-shadow:0 0 0 2px rgba(79,70,229,.25); }
    .design-thumb { height:62px; border-radius:4px; }
    .design-thumb-classic { border:3px double #1f2937; background:#fff; }
    .design-thumb-elegant { border:2px solid #b8860b; background:#fffdf6; box-shadow:inset 0 0 0 3px #fffdf6, inset 0 0 0 4px #b8860b; }
    .design-thumb-modern { background:#fff; border:1px solid #e5e7eb; }
    .design-thumb-modern { background:linear-gradient(#4f46e5 0 26%, #fff 26% 100%); }
</style>
@endpush

@push('script_2')
<script>
document.querySelectorAll('.token').forEach(function (el) {
    el.addEventListener('click', function () {
        navigator.clipboard && navigator.clipboard.writeText(el.textContent);
        el.style.background = '#bbf7d0';
        setTimeout(() => el.style.background = '#eef2ff', 500);
    });
});
</script>
@endpush
