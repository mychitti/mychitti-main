@extends('layouts.vendor.app')
@section('title', 'School Settings')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header">
        <h1 class="page-header-title mb-0"><i class="tio-settings mr-1"></i> School Settings</h1>
    </div>

    <div class="row">
        {{-- Numbering --}}
        <div class="col-lg-7">
            <form action="{{ route('vendor.school.settings.save') }}" method="POST">
                @csrf
                <div class="card mb-3">
                    <div class="card-header justify-content-start py-3"><i class="tio-monitor mr-1 text-primary"></i> Website Template</div>
                    <div class="card-body">
                        <p class="text-muted" style="font-size:12px;">Choose the public webpage design shown to parents at your school's web address.</p>
                        <div class="row" id="schoolTplPicker">
                            @php
                                $tpls = [
                                    '1' => ['Classic', 'Clean indigo layout — hero, classes, facilities, notices.', 'linear-gradient(135deg,#4f46e5,#7c3aed)'],
                                    '2' => ['Premium', 'Elegant editorial design with gold accents & serif headings.', 'linear-gradient(135deg,#0f172a,#b08d57)'],
                                ];
                            @endphp
                            @foreach($tpls as $tk => $t)
                                <div class="col-md-6 mb-2">
                                    <label class="d-block mb-0" style="cursor:pointer;">
                                        <input type="radio" name="school_template" value="{{ $tk }}" class="d-none tpl-radio" @checked(old('school_template', $schoolTemplate) === $tk)>
                                        <div class="tpl-card border rounded p-2">
                                            <div class="tpl-thumb mb-2" style="height:60px;border-radius:6px;background:{{ $t[2] }};"></div>
                                            <small class="font-weight-bold d-block">Template {{ $tk }} — {{ $t[0] }}</small>
                                            <small class="text-muted d-block" style="font-size:11px;line-height:1.3;">{{ $t[1] }}</small>
                                            @if($webpageUrl)
                                                <a href="{{ $webpageUrl }}?school_template={{ $tk }}" target="_blank" class="d-inline-block mt-1" style="font-size:11px;" onclick="event.stopPropagation();"><i class="tio-visible"></i> Preview</a>
                                            @endif
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header justify-content-start py-3"><i class="tio-hashtag mr-1 text-primary"></i> Serial Numbering Scope</div>
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

                <div class="card mb-3">
                    <div class="card-header justify-content-start py-3"><i class="tio-poll mr-1 text-primary"></i> Admission Number Format</div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-4"><label class="input-label">Prefix *</label>
                                <input name="prefix" class="form-control" value="{{ old('prefix', $prefix) }}" maxlength="20" oninput="prev()" id="p"></div>
                            <div class="form-group col-md-4"><label class="input-label">Zero-padding *</label>
                                <input type="number" name="padding" class="form-control" value="{{ old('padding', $padding) }}" min="1" max="10" oninput="prev()" id="pad"></div>
                            <div class="form-group col-md-4 mb-0"><label class="input-label">Minimum serial *</label>
                                <input type="number" name="serial" class="form-control" value="{{ old('serial', $serial) }}" min="1" oninput="prev()" id="ser"></div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <div><span class="text-muted" style="font-size:12px;">Next admission no: </span>
                            <code id="adm" style="font-weight:700;color:#4f46e5;">{{ strtoupper($prefix) }}{{ str_pad($serial, $padding, '0', STR_PAD_LEFT) }}</code></div>
                        <button class="btn btn--primary"><i class="tio-save"></i> Save Settings</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Other settings quick links --}}
        <div class="col-lg-5">
            <div class="card"><div class="card-header justify-content-start py-3"><i class="tio-apps mr-1 text-primary"></i> More Settings</div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('vendor.school.academic.index') }}" class="list-group-item list-group-item-action d-flex align-items-center" style="gap:12px;">
                        <i class="tio-book text-primary" style="font-size:20px;"></i>
                        <span><b class="d-block">Academic Setup</b><small class="text-muted">Sessions, classes, sections, subjects, teachers</small></span>
                    </a>
                    <a href="{{ route('vendor.school.certificates.settings') }}" class="list-group-item list-group-item-action d-flex align-items-center" style="gap:12px;">
                        <i class="tio-receipt-outlined text-primary" style="font-size:20px;"></i>
                        <span><b class="d-block">Certificate Templates &amp; Design</b><small class="text-muted">TC / Bonafide / Character text + layout</small></span>
                    </a>
                    @if (school_can_switch_branch())
                        <a href="{{ route('vendor.school.branches.index') }}" class="list-group-item list-group-item-action d-flex align-items-center" style="gap:12px;">
                            <i class="tio-city text-primary" style="font-size:20px;"></i>
                            <span><b class="d-block">Branches</b><small class="text-muted">Manage campuses &amp; the active branch</small></span>
                        </a>
                    @endif
                    <a href="{{ route('vendor.school.fees.heads') }}" class="list-group-item list-group-item-action d-flex align-items-center" style="gap:12px;">
                        <i class="tio-money text-primary" style="font-size:20px;"></i>
                        <span><b class="d-block">Fee Heads &amp; Structure</b><small class="text-muted">Fee components and per-class structure</small></span>
                    </a>
                    <a href="{{ route('vendor.school.settings.notification-preferences') }}" class="list-group-item list-group-item-action d-flex align-items-center" style="gap:12px;">
                        <i class="tio-notifications text-primary" style="font-size:20px;"></i>
                        <span><b class="d-block">Notification Preferences</b><small class="text-muted">WhatsApp, SMS &amp; Push settings per action</small></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div> 
@endsection

@push('css_or_js')
<style>
    .school-page .tpl-card{ transition:.15s; }
    .school-page .tpl-card:hover{ border-color:#a5b4fc !important; }
    .school-page .tpl-radio:checked + .tpl-card{ border-color:#4f46e5 !important; box-shadow:0 0 0 2px rgba(79,70,229,.25); }
</style>
@endpush

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
