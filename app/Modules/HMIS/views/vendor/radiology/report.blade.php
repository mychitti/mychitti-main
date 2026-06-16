@extends('layouts.vendor.app')
@section('title', 'Radiology — Report Writing')

@section('content')
<div class="content container-fluid"><div class="radx">
    @include('hmis::vendor.radiology._chrome')
    <div class="rad-body">
        @if (!$study)
            <div class="card"><div class="empty">No studies awaiting a report. <a href="{{ route('vendor.radiology.worklist') }}">Go to worklist →</a></div></div>
        @else
            @php $locked = in_array($study->status, ['verified','sent']); $canEdit = hasPermission('radiology_report', 'add'); @endphp
            <div class="layout-2col">
                <div>
                    <form method="post" action="{{ route('vendor.radiology.studies.report', $study->id) }}">
                        @csrf
                        <div class="card">
                            <div class="card-hd">
                                <h3><div class="hd-icon" style="background:var(--ltteal)">📝</div> Radiology Report — {{ $study->study_no }}</h3>
                                <div class="card-actions">
                                    <select class="fsel" onchange="if(this.value)location.href='{{ route('vendor.radiology.report') }}?study='+this.value">
                                        @foreach ($pickable as $p)<option value="{{ $p->id }}" {{ $p->id==$study->id?'selected':'' }}>{{ $p->study_no }} — {{ $p->patient->name ?? '' }} ({{ $p->study_name }})</option>@endforeach
                                    </select>
                                </div>
                            </div>
                            <div style="padding:10px 16px;background:#F9FAFB;border-bottom:1px solid var(--border);display:flex;gap:24px;flex-wrap:wrap">
                                <div><span style="font-size:10px;color:var(--light);display:block;text-transform:uppercase">Patient</span><span style="font-size:12px;font-weight:700">{{ $study->patient->name ?? '—' }} · {{ $study->patient->patient_uid ?? '' }}</span></div>
                                <div><span style="font-size:10px;color:var(--light);display:block;text-transform:uppercase">Study</span><span style="font-size:12px;font-weight:700">{{ $study->modality }} — {{ $study->study_name }}</span></div>
                                <div><span style="font-size:10px;color:var(--light);display:block;text-transform:uppercase">Ref. Doctor</span><span style="font-size:12px;font-weight:700">{{ $study->doctorProfile ? 'Dr. '.trim(($study->doctorProfile->employee->f_name ?? '').' '.($study->doctorProfile->employee->l_name ?? '')) : ($study->referred_by ?: '—') }}</span></div>
                            </div>
                            <div style="padding:14px">
                                <div class="fg" style="margin-bottom:12px"><label class="fl">Clinical History / Indication</label><input class="fi" name="clinical_history" value="{{ $study->clinical_history }}" {{ $locked?'readonly':'' }}></div>

                                <div style="background:#F8FAFC;border:1px solid var(--border);border-radius:8px;padding:12px;margin-bottom:10px">
                                    <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px">Quick Findings — click to add to report</div>
                                    @foreach (['normal'=>['Clear Lungs','Normal Heart Size','Normal Bones','No Effusion','Normal Study'],'abnormal'=>['Opacity','Consolidation','Pleural Effusion','Cardiomegaly','Fracture','Mass Lesion'],'suggest'=>['Atelectasis','Osteopenia','Follow-up advised']] as $type=>$tags)
                                        @foreach ($tags as $t)<span class="finding-tag {{ $type }}" onclick="radTag(this,'{{ $t }}')">{{ $t }}</span>@endforeach
                                    @endforeach
                                </div>

                                <div class="fg" style="margin-bottom:10px"><label class="fl">Findings</label><textarea class="fi" name="findings" id="radFindings" rows="5" style="resize:none" {{ $locked?'readonly':'' }}>{{ $study->findings }}</textarea></div>
                                <div class="fg" style="margin-bottom:10px"><label class="fl">Impression / Conclusion</label><textarea class="fi" name="impression" rows="3" style="resize:none" {{ $locked?'readonly':'' }}>{{ $study->impression }}</textarea></div>
                                <div class="fg" style="margin-bottom:12px"><label class="fl">Recommendations</label><textarea class="fi" name="recommendations" rows="2" style="resize:none" {{ $locked?'readonly':'' }}>{{ $study->recommendations }}</textarea></div>
                                <div class="frow3">
                                    <div class="fg"><label class="fl">Reporting Radiologist</label><input class="fi" name="radiologist" value="{{ $study->radiologist }}" {{ $locked?'readonly':'' }}></div>
                                    <div class="fg"><label class="fl">Report Date</label><input class="fi" value="{{ ($study->reported_at ?? now())->format('d M Y · h:i A') }}" readonly style="background:#F9FAFB"></div>
                                    <div class="fg"><label class="fl">Critical Finding?</label>
                                        <select class="fs" name="is_critical" {{ $locked?'disabled':'' }}><option value="0" {{ !$study->is_critical?'selected':'' }}>No</option><option value="1" {{ $study->is_critical?'selected':'' }}>Yes — Notify Doctor</option></select>
                                    </div>
                                </div>
                                @if(!$locked && $canEdit)
                                    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
                                        <button class="btn btn-outline" name="finalize" value="0">Save Draft</button>
                                        <button class="btn btn-green" name="finalize" value="1" onclick="return confirm('Finalize & verify this report?')">✓ Finalize &amp; Send</button>
                                    </div>
                                @elseif($locked)
                                    <div style="display:flex;gap:8px;justify-content:flex-end;align-items:center;margin-top:8px">
                                        <span class="pill pill-teal">✓ Verified — locked</span>
                                        <a href="{{ route('vendor.radiology.studies.print', $study->id) }}" target="_blank" class="btn btn-primary">🖨 Print Report</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
                <div>
                    <div class="card">
                        <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">🖥</div> Imaging</h3></div>
                        <div style="padding:12px 14px">
                            <a href="{{ route('vendor.radiology.viewer', ['study'=>$study->id]) }}" class="btn btn-dark btn-sm" style="width:100%;justify-content:center">🖥 Open DICOM Viewer</a>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">🕐</div> Prior Studies — Same Patient</h3></div>
                        @php $prior = \App\Models\RadiologyStudy::where('store_id', \App\CentralLogics\Helpers::get_store_id())->where('patient_id', $study->patient_id)->where('id','!=',$study->id)->latest()->take(6)->get(); @endphp
                        @forelse ($prior as $pr)
                            <div class="alert-row"><div style="flex:1"><div class="alert-title">{{ $pr->study_no }} — {{ $pr->study_name }}</div><div class="alert-sub">{{ $pr->created_at?->format('d M Y') }} · {{ \Illuminate\Support\Str::limit($pr->impression ?: ucfirst($pr->status), 26) }}</div></div>@if(in_array($pr->status,['verified','sent']))<a href="{{ route('vendor.radiology.studies.print', $pr->id) }}" target="_blank" class="btn btn-outline btn-xs">View</a>@endif</div>
                        @empty
                            <div class="empty" style="padding:18px">No prior studies.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
</div></div>
@endsection

@push('script_2')
<script>
function radTag(el,txt){
  el.classList.toggle('selected');
  var ta=document.getElementById('radFindings');
  if(el.classList.contains('selected')){ ta.value=(ta.value?ta.value.replace(/\s*$/,'')+'\n':'')+'- '+txt+'.'; }
}
</script>
@endpush
