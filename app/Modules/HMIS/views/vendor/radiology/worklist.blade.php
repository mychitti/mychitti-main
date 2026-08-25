@extends('layouts.vendor.app')
@section('title', 'Radiology — Study Worklist')

@section('content')
<div class="content container-fluid"><div class="radx">
    @include('hmis::vendor.radiology._chrome')
    <div class="rad-body">
        @php
            $canStart = hasPermission('radiology_study', 'edit');
            $canWriteReport = hasPermission('radiology_report', 'add');
            $canViewReport = hasPermission('radiology_report', 'view');
            $canNotify = hasPermission('radiology_urgent', 'notify');
            $canSchedule = hasPermission('radiology_schedule', 'view');
            $canEquipment = hasPermission('radiology_equipment', 'view');
            $cols = 'grid-template-columns:100px 1fr 110px 1fr 90px 90px 96px';
            $modPill = fn($m) => ['X-Ray'=>'pill-blue','CT Scan'=>'pill-purple','MRI'=>'pill-purple','Ultrasound'=>'pill-teal','ECG'=>'pill-amber'][$m] ?? 'pill-blue';
        @endphp
        <div class="layout-2col">
            <div>
                <div class="card">
                    <div class="card-hd">
                        <h3><div class="hd-icon" style="background:var(--ltblue)">📋</div> Radiology Study Worklist</h3>
                        <div class="card-actions">
                            <form method="get" class="mb-0"><select name="modality" class="fsel" onchange="this.form.submit()">
                                <option value="">All Modalities</option>
                                @foreach (['X-Ray','CT Scan','MRI','Ultrasound','ECG'] as $m)<option value="{{ $m }}" {{ request('modality')===$m?'selected':'' }}>{{ $m }}</option>@endforeach
                            </select></form>
                            @if($canSchedule)<a href="{{ route('vendor.radiology.schedule') }}" class="btn btn-primary btn-sm">+ Book Study</a>@endif
                        </div>
                    </div>
                    <div class="search-bar">
                        <div class="search-wrap"><input class="si" id="wlSearch" placeholder="🔍 Search patient, study ID, modality..." oninput="radFilter()"></div>
                        <select class="fsel" id="wlStatus" onchange="radFilter()"><option value="">All Status</option><option>pending</option><option>in_progress</option><option>verified</option><option>sent</option></select>
                        <select class="fsel" id="wlPriority" onchange="radFilter()"><option value="">All Priority</option><option>urgent</option><option>stat</option><option>routine</option></select>
                    </div>
                    <div class="tbl-hd" style="{{ $cols }}"><div>Study ID</div><div>Patient</div><div>Modality</div><div>Study</div><div>Priority</div><div>Status</div><div>Action</div></div>
                    <div id="wlBody">
                        @forelse ($studies as $s)
                            @php
                                $urgent = in_array($s->priority, ['urgent','stat']);
                                $rowCls = $s->status==='in_progress' ? 'active-row' : ($urgent && $s->status==='pending' ? 'urgent' : (in_array($s->status,['verified','sent'])?'done':''));
                                $doc = $s->doctorProfile ? 'Dr. '.trim(($s->doctorProfile->employee->f_name ?? '').' '.($s->doctorProfile->employee->l_name ?? '')) : ($s->referred_by ?: '—');
                            @endphp
                            <div class="tbl-row {{ $rowCls }}" style="{{ $cols }}" data-status="{{ $s->status }}" data-priority="{{ $s->priority }}" data-search="{{ strtolower($s->study_no.' '.($s->patient->name ?? '').' '.$s->modality.' '.$s->study_name) }}">
                                <div class="cell-id num" style="font-size:11px;color:var(--blue)">{{ $s->study_no }}</div>
                                <div data-label="Patient"><div style="font-weight:700;font-size:13px">{{ $s->patient->name ?? '—' }}</div><div style="font-size:10px;color:var(--light)">{{ $s->patient->patient_uid ?? '' }}{{ $s->department ? ' · '.$s->department : '' }}</div></div>
                                <div data-label="Modality"><span class="pill {{ $modPill($s->modality) }}">{{ $s->modality }}</span></div>
                                <div data-label="Study" style="font-size:11px">{{ $s->study_name }}</div>
                                <div data-label="Priority">@if($urgent)<span class="pill pill-red">🚨 {{ ucfirst($s->priority) }}</span>@else<span class="pill pill-teal">Routine</span>@endif</div>
                                <div data-label="Status">
                                    @if($s->status==='pending')<span class="pill pill-amber">Pending</span>
                                    @elseif($s->status==='in_progress')<span class="pill pill-navy">● Scanning</span>
                                    @elseif($s->status==='reported')<span class="pill pill-purple">Reported</span>
                                    @elseif($s->status==='verified')<span class="pill pill-teal">✓ Verified</span>
                                    @else<span class="pill pill-green">✓ Sent</span>@endif
                                </div>
                                <div class="cell-action">
                                    @if($s->status==='pending')@if($canStart)<a href="{{ route('vendor.radiology.studies.start', $s->id) }}" class="btn {{ $urgent?'btn-red':'btn-primary' }} btn-xs">Start</a>@else<span class="pill pill-amber">Pending</span>@endif
                                    @elseif($s->status==='in_progress')@if($canWriteReport)<a href="{{ route('vendor.radiology.report', ['study'=>$s->id]) }}" class="btn btn-green btn-xs">Report</a>@endif
                                    @elseif($s->status==='reported')@if($canWriteReport)<a href="{{ route('vendor.radiology.report', ['study'=>$s->id]) }}" class="btn btn-green btn-xs">Verify</a>@endif
                                    @elseif($canViewReport)<a href="{{ route('vendor.radiology.studies.print', $s->id) }}" target="_blank" class="btn btn-outline btn-xs">Report</a>@endif
                                </div>
                            </div>
                        @empty
                            <div class="empty">No studies in the last 3 days.@if($canSchedule) <a href="{{ route('vendor.radiology.schedule') }}">Book a study →</a>@endif</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div>
                <div class="card" style="border-color:#FECDD3">
                    <div class="card-hd" style="background:#FFF5F5"><h3><div class="hd-icon" style="background:var(--ltred)">🚨</div> Urgent Findings</h3><span class="pill pill-red">{{ $urgentAlerts->count() }} Alerts</span></div>
                    @forelse ($urgentAlerts as $a)
                        <div class="alert-row" style="background:#FFF5F5"><div class="alert-dot" style="background:var(--redB)"></div><div style="flex:1"><div class="alert-title">{{ $a->study_no }} — {{ \Illuminate\Support\Str::limit($a->impression ?: $a->study_name, 34) }}</div><div class="alert-sub">{{ $a->patient->name ?? '' }} · {{ $a->modality }}</div></div>
                            @if($canNotify)<form method="post" action="{{ route('vendor.radiology.studies.notify', $a->id) }}" class="mb-0">@csrf<button class="btn btn-red btn-xs">Notify</button></form>@endif</div>
                    @empty
                        <div class="empty" style="padding:18px">No urgent findings.</div>
                    @endforelse
                </div>
                <div class="card">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltgreen)">📊</div> Today's Stats</h3></div>
                    @php $byMod = $studies->groupBy('modality'); @endphp
                    <div class="stat-row"><span class="stat-l">Total Studies</span><span class="stat-v num">{{ $radStats['total_today'] }}</span></div>
                    @foreach (['X-Ray','Ultrasound','CT Scan','MRI'] as $m)
                        <div class="stat-row"><span class="stat-l">{{ $m }}</span><span class="stat-v num">{{ optional($byMod->get($m))->count() ?? 0 }}</span></div>
                    @endforeach
                    <div class="stat-row"><span class="stat-l">Reports Ready</span><span class="stat-v g num">{{ $radStats['reports_ready'] }}</span></div>
                </div>
                <div class="card">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltpurple)">⚙</div> Equipment Status</h3></div>
                    @foreach ($equipment as $e)
                        <div class="alert-row"><div class="equip-dot" style="background:{{ $e->status==='online'?'var(--greenA)':($e->status==='maintenance'?'var(--amberA)':'var(--redA)') }}"></div><div style="flex:1"><div class="alert-title">{{ $e->name }}</div><div class="alert-sub">{{ $e->model }}</div></div><span class="pill {{ $e->status==='online'?'pill-green':($e->status==='maintenance'?'pill-amber':'pill-red') }}">{{ ucfirst($e->status) }}</span></div>
                    @endforeach
                    @if($canEquipment)<div style="padding:10px 14px;border-top:1px solid var(--border)"><a href="{{ route('vendor.radiology.equipment') }}" class="btn btn-outline btn-sm" style="width:100%">Equipment Details →</a></div>@endif
                </div>
            </div>
        </div>
    </div>
</div></div>
@endsection

@push('script_2')
<script>
function radFilter(){
  var q=(document.getElementById('wlSearch').value||'').toLowerCase();
  var st=document.getElementById('wlStatus').value, pr=document.getElementById('wlPriority').value;
  document.querySelectorAll('#wlBody .tbl-row').forEach(function(r){
    var ok=(!q||(r.dataset.search||'').includes(q))&&(!st||r.dataset.status===st)&&(!pr||r.dataset.priority===pr);
    r.style.display=ok?'grid':'none';
  });
}
</script>
@endpush
