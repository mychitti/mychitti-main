@extends('layouts.vendor.app')
@section('title', 'Radiology — DICOM Viewer')

@section('content')
<div class="content container-fluid"><div class="radx">
    @include('hmis::vendor.radiology._chrome')
    <div class="rad-body">
        @if (!$study)
            <div class="card"><div class="empty">No study selected.</div></div>
        @else
            <div class="card-hd" style="border:none;padding:0 0 10px">
                <h3 style="font-size:13px"><div class="hd-icon" style="background:var(--ltblue)">🖥</div> {{ $study->study_no }} · {{ $study->patient->name ?? '' }} · {{ $study->study_name }}</h3>
                <div class="card-actions">
                    <select class="fsel" onchange="if(this.value)location.href='{{ route('vendor.radiology.viewer') }}?study='+this.value">
                        @foreach ($studies as $p)<option value="{{ $p->id }}" {{ $p->id==$study->id?'selected':'' }}>{{ $p->study_no }} — {{ $p->patient->name ?? '' }}</option>@endforeach
                    </select>
                    @if(hasPermission('radiology_report', 'add'))<a href="{{ route('vendor.radiology.report', ['study'=>$study->id]) }}" class="btn btn-primary btn-sm">📝 Write Report →</a>@endif
                </div>
            </div>
            <div class="dicom-viewer">
                <div class="dicom-toolbar">
                    <span style="font-size:11px;color:rgba(255,255,255,.5);font-family:var(--ffm)">{{ $study->study_no }} · {{ $study->modality }}</span>
                    <div style="flex:1"></div>
                    @foreach (['🔍 Zoom','🪟 W/L','📏 Measure','📐 Angle','✏ Annotate','🔄 Rotate','🔲 Invert'] as $i=>$t)
                        <button class="dicom-tool-btn {{ $i===0?'active':'' }}" onclick="this.parentElement.querySelectorAll('.dicom-tool-btn').forEach(b=>b.classList.remove('active'));this.classList.add('active')">{{ $t }}</button>
                    @endforeach
                </div>
                <div class="dicom-body">
                    <div class="dicom-canvas">
                        <div style="position:absolute;inset:0;background:radial-gradient(ellipse at center,#3a3a3a 0%,#1a1a1a 40%,#0d0d0d 100%)"></div>
                        <svg width="100%" height="100%" viewBox="0 0 500 420" style="position:absolute;inset:0;opacity:.65">
                            <ellipse cx="250" cy="210" rx="16" ry="175" fill="rgba(200,200,200,0.35)"/>
                            <path d="M232 110 Q170 122 130 150" stroke="rgba(200,200,200,0.45)" stroke-width="3" fill="none"/>
                            <path d="M232 150 Q160 165 116 205" stroke="rgba(200,200,200,0.45)" stroke-width="3" fill="none"/>
                            <path d="M232 190 Q160 208 120 250" stroke="rgba(200,200,200,0.45)" stroke-width="3" fill="none"/>
                            <path d="M268 110 Q330 122 370 150" stroke="rgba(200,200,200,0.45)" stroke-width="3" fill="none"/>
                            <path d="M268 150 Q340 165 384 205" stroke="rgba(200,200,200,0.45)" stroke-width="3" fill="none"/>
                            <path d="M268 190 Q340 208 380 250" stroke="rgba(200,200,200,0.45)" stroke-width="3" fill="none"/>
                            <ellipse cx="165" cy="210" rx="64" ry="95" fill="rgba(60,60,60,0.6)"/>
                            <ellipse cx="320" cy="210" rx="58" ry="95" fill="rgba(60,60,60,0.6)"/>
                            <ellipse cx="225" cy="255" rx="58" ry="72" fill="rgba(160,160,160,0.28)"/>
                        </svg>
                        <div style="position:absolute;top:10px;left:12px;color:rgba(255,255,255,.5);font-size:10px;font-family:var(--ffm);line-height:1.7">{{ $study->study_no }}<br>{{ $study->patient->name ?? '' }}<br>{{ $study->created_at?->format('d-M-Y') }}<br>{{ $study->study_name }}</div>
                        <div style="position:absolute;top:10px;right:12px;color:rgba(255,255,255,.5);font-size:10px;font-family:var(--ffm);text-align:right;line-height:1.7">{{ $study->modality }}<br>{{ $study->equipment->model ?? 'DR System' }}<br>{{ $study->radiologist ?: 'Radiologist' }}</div>
                        <div style="position:absolute;bottom:24px;right:12px;color:rgba(255,255,255,.3);font-size:26px;font-family:var(--ffm)">R</div>
                        <div style="position:absolute;bottom:24px;left:12px;color:rgba(255,255,255,.3);font-size:26px;font-family:var(--ffm)">L</div>
                    </div>
                    <div class="dicom-sidebar">
                        <div style="margin-bottom:14px">
                            <div class="dicom-info-title">Patient Info</div>
                            <div class="dicom-info-row"><span class="di-label">Name</span><span class="di-val">{{ $study->patient->name ?? '—' }}</span></div>
                            <div class="dicom-info-row"><span class="di-label">ID</span><span class="di-val">{{ $study->patient->patient_uid ?? '' }}</span></div>
                            <div class="dicom-info-row"><span class="di-label">Age/Sex</span><span class="di-val">{{ $study->patient->dob ? \Carbon\Carbon::parse($study->patient->dob)->age : '—' }}/{{ $study->patient->gender ? strtoupper(substr($study->patient->gender,0,1)) : '' }}</span></div>
                            <div class="dicom-info-row"><span class="di-label">Study</span><span class="di-val">{{ \Illuminate\Support\Str::limit($study->study_name, 18) }}</span></div>
                        </div>
                        <div style="margin-bottom:14px">
                            <div class="dicom-info-title">Image Properties</div>
                            <div class="dicom-info-row"><span class="di-label">Modality</span><span class="di-val">{{ $study->modality }}</span></div>
                            <div class="dicom-info-row"><span class="di-label">Body Part</span><span class="di-val">{{ $study->body_part ?: '—' }}</span></div>
                            <div class="dicom-info-row"><span class="di-label">Priority</span><span class="di-val">{{ ucfirst($study->priority) }}</span></div>
                            <div class="dicom-info-row"><span class="di-label">Status</span><span class="di-val">{{ ucfirst(str_replace('_',' ',$study->status)) }}</span></div>
                        </div>
                        <div style="margin-bottom:8px">
                            <div class="dicom-info-title">Window</div>
                            <label style="font-size:10px;color:rgba(255,255,255,.4);display:flex;justify-content:space-between">Window Width <span id="wwv">1800</span></label>
                            <input type="range" min="100" max="4000" value="1800" style="width:100%;accent-color:var(--blue)" oninput="document.getElementById('wwv').textContent=this.value">
                            <label style="font-size:10px;color:rgba(255,255,255,.4);display:flex;justify-content:space-between;margin-top:6px">Window Level <span id="wlv">-600</span></label>
                            <input type="range" min="-1000" max="1000" value="-600" style="width:100%;accent-color:var(--blue)" oninput="document.getElementById('wlv').textContent=this.value">
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div></div>
@endsection
