@extends('layouts.vendor.app')
@section('title', 'Nursing Station')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
.content.container-fluid{padding:0!important;margin:0!important;max-width:100%!important;width:100%!important}
.nursex{--bg:#0B0F1A;--bg2:#111827;--bg3:#1A2235;--bg4:#1F2D42;--border:#243047;--border2:#2E3D58;--text:#E8EDF5;--muted:#7A8FAD;--light:#A8B8CE;--blue:#3B82F6;--ltblue:#1E3A5F;--green:#22C55E;--ltgreen:#14532D;--greenA:#16A34A;--red:#EF4444;--ltred:#450A0A;--redA:#DC2626;--amber:#F59E0B;--ltamber:#451A03;--amberA:#D97706;--purple:#A78BFA;--ltpurple:#2E1065;--teal:#2DD4BF;--ltteal:#042F2E;--font:'Outfit',sans-serif;--mono:'DM Mono',monospace;
  background:var(--bg);color:var(--text);font-family:var(--font);font-size:13px;min-height:calc(100vh - 0px);display:block}
.nursex *{box-sizing:border-box}
.nursex .topnav{background:var(--bg2);border-bottom:1px solid var(--border);min-height:50px;display:flex;align-items:center;padding:8px 16px;gap:12px;flex-wrap:wrap}
.nursex .tn-page{font-size:13px;font-weight:700;color:var(--text)}.nursex .tn-page span{color:var(--teal)}
.nursex .tn-sep{width:1px;height:20px;background:var(--border)}
.nursex .tn-right{display:flex;align-items:center;gap:8px;margin-left:auto;flex-wrap:wrap}
.nursex .tn-ward{background:var(--ltblue);border:1px solid #1D4ED8;border-radius:6px;padding:4px 12px;font-size:11px;color:var(--blue);font-weight:600}
.nursex .tn-shift{background:var(--ltteal);border:1px solid #065F46;border-radius:6px;padding:4px 12px;font-size:11px;color:var(--teal);font-weight:600}
.nursex .tn-avatar{width:30px;height:30px;border-radius:8px;background:var(--bg4);border:1.5px solid var(--border2);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--teal)}
.nursex .tn-name{font-size:12px;color:var(--light);font-weight:500}
.nursex .tn-clock{font-family:var(--mono);font-size:12px;color:var(--muted);min-width:72px;text-align:right}
.nursex .tabs{background:var(--bg2);border-bottom:1px solid var(--border);padding:0 12px;display:flex;overflow-x:auto}
.nursex .tabs::-webkit-scrollbar{display:none}
.nursex .tab{padding:10px 13px;font-size:12px;font-weight:600;color:var(--muted);cursor:pointer;border-bottom:2px solid transparent;display:flex;align-items:center;gap:5px;white-space:nowrap}
.nursex .tab:hover{color:var(--light)}.nursex .tab.active{color:var(--teal);border-bottom-color:var(--teal)}
.nursex .tbadge{font-size:9px;font-weight:700;padding:1px 5px;border-radius:10px;background:var(--redA);color:#fff}
.nursex .tbadge.am{background:var(--amberA)}
.nursex .layout{display:grid;grid-template-columns:260px 1fr;gap:0;min-height:520px}
.nursex .left-panel{background:var(--bg2);border-right:1px solid var(--border);padding:12px;max-height:78vh;overflow-y:auto}
.nursex .right-col{padding:14px 16px;background:var(--bg);max-height:78vh;overflow-y:auto}
.nursex .ward-hd{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1.2px;margin-bottom:8px;padding:0 2px}
.nursex .pt-row{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;border:1px solid var(--border);margin-bottom:6px;cursor:pointer;background:var(--bg3);text-decoration:none}
.nursex .pt-row:hover{border-color:var(--border2);background:var(--bg4)}
.nursex .pt-row.active{border-color:var(--teal);background:var(--ltteal)}
.nursex .pt-row.crit{border-color:var(--redA);background:var(--ltred)}
.nursex .pt-row.warn{border-color:var(--amberA);background:var(--ltamber)}
.nursex .bed-num{font-family:var(--mono);font-size:13px;color:var(--muted);width:42px;flex-shrink:0}
.nursex .pt-info{flex:1;min-width:0}
.nursex .pt-rowname{font-size:12px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.nursex .pt-rowmeta{font-size:10px;color:var(--muted);margin-top:1px}
.nursex .sev-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-left:auto}
.nursex .sev-dot.crit{background:var(--red)}.nursex .sev-dot.warn{background:var(--amber)}.nursex .sev-dot.stable{background:var(--green)}.nursex .sev-dot.obs{background:var(--blue)}
.nursex .card{background:var(--bg2);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:12px}
.nursex .card-hd{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:8px}
.nursex .card-hd h3{font-size:12px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:7px;margin:0}
.nursex .hd-icon{width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:12px}
.nursex .card-body{padding:12px 14px}
.nursex .pt-banner{background:var(--bg2);border:1px solid var(--border);border-radius:12px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:12px}
.nursex .pt-av{width:44px;height:44px;border-radius:10px;background:var(--bg4);border:1.5px solid var(--border2);display:flex;align-items:center;justify-content:center;font-size:17px;font-weight:700;color:var(--teal)}
.nursex .pt-n{font-size:15px;font-weight:700;color:var(--text)}
.nursex .pt-m{display:flex;align-items:center;gap:6px;margin-top:4px;flex-wrap:wrap}
.nursex .ptag{font-size:10px;font-weight:600;padding:2px 8px;border-radius:10px}
.nursex .ptag.bl{background:var(--ltblue);color:var(--blue)}.nursex .ptag.rd{background:var(--ltred);color:var(--red)}.nursex .ptag.gr{background:var(--ltgreen);color:var(--green)}.nursex .ptag.am{background:var(--ltamber);color:var(--amber)}.nursex .ptag.tl{background:var(--ltteal);color:var(--teal)}
.nursex .pdot{width:3px;height:3px;border-radius:50%;background:var(--border2)}
.nursex .vstrip{display:flex;background:var(--bg3);border:1px solid var(--border);border-radius:10px;overflow:hidden;flex-wrap:wrap}
.nursex .vcell{flex:1;min-width:80px;padding:8px 12px;border-right:1px solid var(--border);cursor:pointer}
.nursex .vcell:last-child{border-right:none}.nursex .vcell:hover{background:var(--bg4)}
.nursex .vval{font-family:var(--mono);font-size:15px;color:var(--text);line-height:1}
.nursex .vval.red{color:var(--red)}.nursex .vval.am{color:var(--amber)}.nursex .vval.gr{color:var(--green)}
.nursex .vlbl{font-size:9px;color:var(--muted);margin-top:3px;text-transform:uppercase;letter-spacing:.5px}
.nursex .tab-panel{display:none}.nursex .tab-panel.active{display:block}
.nursex .mar-time-hd,.nursex .mar-row{display:grid;grid-template-columns:200px repeat(8,1fr);gap:4px;align-items:center}
.nursex .mar-time-hd{padding:0 8px 6px;font-size:9px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.6px}
.nursex .mar-row{padding:6px 8px;background:var(--bg3);border-radius:8px;margin-bottom:4px;border:1px solid var(--border)}
.nursex .med-name{font-size:12px;font-weight:600;color:var(--text)}
.nursex .med-dose{font-size:10px;color:var(--muted);margin-top:1px}
.nursex .med-route{font-size:9px;font-weight:700;padding:1px 6px;border-radius:4px;display:inline-block;margin-top:2px}
.nursex .med-route.iv{background:var(--ltblue);color:var(--blue)}.nursex .med-route.po{background:var(--ltgreen);color:var(--green)}.nursex .med-route.im{background:var(--ltpurple);color:var(--purple)}.nursex .med-route.sc{background:var(--ltamber);color:var(--amber)}
.nursex .mar-cell{display:flex;align-items:center;justify-content:center;height:32px;border-radius:6px;font-size:10px;font-weight:700;border:1px solid transparent;background:var(--bg4);color:var(--muted)}
.nursex form.mar-cellform{margin:0}
.nursex .mar-cell.given{background:var(--ltgreen);border-color:#166534;color:var(--green)}
.nursex .mar-cell.missed{background:var(--ltred);border-color:var(--redA);color:var(--red)}
.nursex .mar-cell.due{background:var(--ltamber);border-color:var(--amberA);color:var(--amber);cursor:pointer;width:100%}
.nursex .mar-cell.future{background:var(--bg4);color:var(--muted);cursor:pointer;width:100%}
.nursex .mar-cell.na{background:transparent;color:var(--border2)}
.nursex .fb-summary{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:12px}
.nursex .fb-card{background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:12px 14px}
.nursex .fb-val{font-family:var(--mono);font-size:22px;line-height:1;margin-bottom:4px}
.nursex .fb-val.in{color:var(--blue)}.nursex .fb-val.out{color:var(--amber)}.nursex .fb-val.pos{color:var(--green)}.nursex .fb-val.neg{color:var(--red)}
.nursex .fb-lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.8px}
.nursex .fb-row{display:grid;grid-template-columns:80px 1fr 70px 80px 90px;gap:8px;align-items:center;padding:7px 10px;background:var(--bg3);border-radius:7px;margin-bottom:4px;border:1px solid var(--border);font-size:12px}
.nursex .fb-type{font-size:9px;font-weight:700;padding:2px 7px;border-radius:4px}
.nursex .fb-type.in{background:var(--ltblue);color:var(--blue)}.nursex .fb-type.out{background:var(--ltamber);color:var(--amber)}
.nursex .nx-input,.nursex .nx-select,.nursex textarea.nx-input{background:var(--bg4);border:1px solid var(--border2);border-radius:6px;padding:6px 9px;font-size:12px;color:var(--text);font-family:var(--font);outline:none;width:100%}
.nursex .nx-input:focus,.nursex .nx-select:focus{border-color:var(--teal)}
.nursex .note-item{padding:10px 12px;background:var(--bg3);border-radius:8px;border:1px solid var(--border);margin-bottom:6px}
.nursex .note-meta{display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:10px;color:var(--muted)}
.nursex .note-nurse{font-weight:600;color:var(--teal)}
.nursex .note-text{font-size:12px;color:var(--light);line-height:1.6}
.nursex .task-row{display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--bg3);border-radius:8px;border:1px solid var(--border);margin-bottom:5px}
.nursex .task-row.urgent{border-color:var(--redA);background:var(--ltred)}
.nursex .task-row.soon{border-color:var(--amberA)}
.nursex .task-row.done{opacity:.55}
.nursex .task-time{font-family:var(--mono);font-size:11px;color:var(--muted);width:50px;flex-shrink:0}
.nursex .task-time.red{color:var(--red)}.nursex .task-time.am{color:var(--amber)}
.nursex .task-desc{flex:1;font-size:12px;color:var(--text)}
.nursex .task-bed{font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;background:var(--bg4);color:var(--muted)}
.nursex .task-done{width:24px;height:24px;border-radius:6px;background:var(--bg4);border:1px solid var(--border2);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--muted);text-decoration:none}
.nursex .task-done.done{background:var(--ltgreen);border-color:var(--greenA);color:var(--green)}
.nursex .vchart-row{display:flex;align-items:center;gap:8px;margin-bottom:6px}
.nursex .vchart-lbl{font-size:10px;color:var(--muted);width:46px;flex-shrink:0;text-align:right;font-family:var(--mono)}
.nursex .vchart-bar-wrap{flex:1;height:20px;background:var(--bg4);border-radius:4px;overflow:hidden}
.nursex .vchart-bar{height:100%;border-radius:4px;display:flex;align-items:center;justify-content:flex-end;padding-right:6px}
.nursex .vchart-val{font-family:var(--mono);font-size:10px;color:#fff}
.nursex .btn{padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;white-space:nowrap;font-family:var(--font)}
.nursex .btn-teal{background:var(--teal);color:#022C22}.nursex .btn-blue{background:var(--blue);color:#fff}.nursex .btn-red{background:var(--redA);color:#fff}
.nursex .btn-outline{background:none;border:1px solid var(--border2);color:var(--muted)}.nursex .btn-outline:hover{border-color:var(--teal);color:var(--teal)}
.nursex .divider{height:1px;background:var(--border);margin:10px 0}
.nursex .ho-sec-title{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin:14px 0 8px;display:flex;align-items:center;gap:6px}
.nursex .ho-sec-title::before{content:'';width:3px;height:12px;border-radius:2px;background:var(--teal)}
.nursex .ho-item{display:flex;align-items:flex-start;gap:8px;padding:7px 10px;background:var(--bg3);border-radius:7px;border:1px solid var(--border);margin-bottom:4px}
.nursex .ho-text{font-size:12px;color:var(--text);flex:1}
.nursex .ho-meta{font-size:10px;color:var(--muted);margin-top:1px}
.nursex .empty{text-align:center;color:var(--muted);padding:36px 16px;font-size:13px}
.nursex .complete-bar{background:var(--bg2);border-top:1px solid var(--border);padding:10px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
@media(max-width:900px){.nursex .layout{grid-template-columns:1fr}.nursex .mar-time-hd,.nursex .mar-row{grid-template-columns:140px repeat(8,minmax(28px,1fr))}}
</style>
@endpush

@section('content')
<div class="content container-fluid">
<div class="nursex">
    @php
        // Tab visibility (view) per sub-feature
        $vVitals   = hasPermission('nursing_vitals', 'view');
        $vMar      = hasPermission('nursing_mar', 'view');
        $vFluid    = hasPermission('nursing_fluid', 'view');
        $vNote     = hasPermission('nursing_note', 'view');
        $vTask     = hasPermission('nursing_task', 'view');
        $vHandover = hasPermission('nursing_handover', 'view');
        // Action permissions
        $canVitals   = hasPermission('nursing_vitals', 'add');
        $canMar      = hasPermission('nursing_mar', 'add');
        $canMarGive  = hasPermission('nursing_mar', 'edit');
        $canFluid    = hasPermission('nursing_fluid', 'add');
        $canNote     = hasPermission('nursing_note', 'add');
        $canTask     = hasPermission('nursing_task', 'add');
        $canTaskDone = hasPermission('nursing_task', 'edit');
        $canHandover = hasPermission('nursing_handover', 'edit');
        $admParam = $current->id ?? '';
        $nowTime = now()->format('H:i');
        $vbp = $vital ? (($vital->bp_systolic ?? '—') . '/' . ($vital->bp_diastolic ?? '—')) : '—';
        $vbpCls = ($vital && $vital->bp_systolic >= 140) ? 'red' : '';
        $vspo2Cls = $vital ? ($vital->spo2 >= 95 ? 'gr' : ($vital->spo2 >= 92 ? 'am' : 'red')) : '';
        $vtempCls = ($vital && $vital->temp >= 100.4) ? 'am' : '';
    @endphp

    {{-- TOP NAV --}}
    <div class="topnav">
        <div class="tn-page">Nursing <span>Station</span></div>
        <div class="tn-sep"></div>
        <span style="font-size:11px;color:var(--muted);font-family:var(--mono)">{{ $current->ward->ward_name ?? 'No ward' }}</span>
        <div class="tn-right">
            <div class="tn-ward">🛏 {{ $summary['occupied'] }} Occupied</div>
            <div class="tn-shift">🌅 {{ $shift }} Shift</div>
            @isset($duty)
                @if($duty['has'])
                    <div class="tn-sep"></div>
                    <div class="tn-shift" title="Today's duty">🟢 In {{ $duty['in_time'] ? $duty['in_time']->format('h:i A') : '—' }}
                        · 🔴 Out {{ $duty['out_time'] ? $duty['out_time']->format('h:i A') : '—' }}@if($duty['extra_label']) · ⏱ Extra {{ $duty['extra_label'] }}@endif</div>
                @endif
            @endisset
            <div class="tn-sep"></div>
            <div class="tn-avatar">{{ strtoupper(substr($nurseName ?: 'N', 0, 2)) }}</div>
            <div class="tn-name">{{ $nurseName ?: 'Nurse' }}</div>
            <div class="tn-clock" id="nxClock">{{ now()->format('H:i:s') }}</div>
        </div>
    </div>

    {{-- TABS --}}
    <div class="tabs">
        <div class="tab active" data-tab="0">🏥 Ward Overview</div>
        @if($vMar)<div class="tab" data-tab="1">💊 MAR</div>@endif
        @if($vFluid)<div class="tab" data-tab="2">🧴 Fluid Balance</div>@endif
        @if($vNote)<div class="tab" data-tab="3">📝 Nursing Notes</div>@endif
        @if($vHandover)<div class="tab" data-tab="4">🔁 Shift Handover</div>@endif
        @if($vTask)<div class="tab" data-tab="5">📋 Task Queue @if($patientTasks->where('status','pending')->count())<span class="tbadge">{{ $patientTasks->where('status','pending')->count() }}</span>@endif</div>@endif
        @if($vVitals)<div class="tab" data-tab="6">📈 Vitals Trend</div>@endif
    </div>

    <div class="layout">
        {{-- LEFT: WARD PATIENT LIST --}}
        <div class="left-panel">
            <div class="ward-hd">Active Patients ({{ $admissions->count() }})</div>
            @forelse($admissions as $a)
                <a href="{{ route('vendor.nursing.index', ['admission' => $a->id]) }}"
                   class="pt-row {{ $current && $a->id === $current->id ? 'active' : '' }} {{ $a->severity === 'crit' ? 'crit' : ($a->severity === 'warn' ? 'warn' : '') }}">
                    <div class="bed-num">{{ $a->bed->bed_number ?? '—' }}</div>
                    <div class="pt-info">
                        <div class="pt-rowname">{{ $a->patient->name ?? 'Patient' }}</div>
                        <div class="pt-rowmeta">{{ $a->patient->gender ? strtoupper(substr($a->patient->gender,0,1)) : '' }} · {{ \Carbon\Carbon::parse($a->admission_date)->diffInDays(now()) + 1 }}d · {{ \Illuminate\Support\Str::limit($a->diagnosis ?: 'IPD', 14) }}</div>
                    </div>
                    <div class="sev-dot {{ $a->severity }}"></div>
                </a>
            @empty
                <div class="empty">No admitted patients. <a href="{{ route('vendor.ipd.index') }}" style="color:var(--teal)">Admit a patient →</a></div>
            @endforelse

            @if($emptyBeds->count())
                <div class="divider"></div>
                <div class="ward-hd">Empty Beds ({{ $emptyBeds->count() }})</div>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                    @foreach($emptyBeds as $b)
                        <div style="background:var(--bg3);border:1px dashed var(--border);border-radius:6px;padding:4px 9px;font-size:11px;color:var(--muted)">{{ $b }}</div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- RIGHT --}}
        <div class="right-col">
            @if(!$current)
                <div class="card"><div class="empty">Select an admitted patient from the ward list to begin.</div></div>
            @else
            {{-- PATIENT BANNER --}}
            <div class="pt-banner">
                <div style="display:flex;align-items:center;gap:12px">
                    <div class="pt-av">{{ strtoupper(substr($current->patient->name ?? 'P', 0, 1)) }}</div>
                    <div>
                        <div class="pt-n">{{ $current->patient->name ?? 'Patient' }}</div>
                        <div class="pt-m">
                            <span class="ptag bl">{{ $current->bed->bed_number ?? '—' }}</span>
                            <div class="pdot"></div>
                            <span style="font-size:11px;color:var(--muted)">{{ $current->patient->dob ? \Carbon\Carbon::parse($current->patient->dob)->age : '—' }}{{ $current->patient->gender ? strtoupper(substr($current->patient->gender,0,1)) : '' }} · {{ $current->patient->blood_group ?: '—' }}</span>
                            @if($current->patient->allergies)<div class="pdot"></div><span class="ptag rd">⚠ {{ $current->patient->allergies }}</span>@endif
                            @if($current->diagnosis)<span class="ptag am">{{ $current->diagnosis }}</span>@endif
                            <div class="pdot"></div>
                            <span class="ptag tl">Day {{ \Carbon\Carbon::parse($current->admission_date)->diffInDays(now()) + 1 }} IPD</span>
                        </div>
                    </div>
                </div>
                <div class="vstrip">
                    <div class="vcell" onclick="nxVital('bp','BP (e.g. 138/88)','{{ $vbp }}')"><div class="vval {{ $vbpCls }}">{{ $vbp }}</div><div class="vlbl">BP mmHg</div></div>
                    <div class="vcell" onclick="nxVital('hr','Heart rate','{{ $vital->hr ?? '' }}')"><div class="vval">{{ $vital->hr ?? '—' }}</div><div class="vlbl">HR /min</div></div>
                    <div class="vcell" onclick="nxVital('temp','Temperature °F','{{ $vital->temp ?? '' }}')"><div class="vval {{ $vtempCls }}">{{ $vital->temp ?? '—' }}</div><div class="vlbl">Temp °F</div></div>
                    <div class="vcell" onclick="nxVital('spo2','SpO₂ %','{{ $vital->spo2 ?? '' }}')"><div class="vval {{ $vspo2Cls }}">{{ $vital ? $vital->spo2.'%' : '—' }}</div><div class="vlbl">SpO₂</div></div>
                    <div class="vcell" onclick="nxVital('rr','Respiratory rate','{{ $vital->rr ?? '' }}')"><div class="vval">{{ $vital->rr ?? '—' }}</div><div class="vlbl">RR /min</div></div>
                    <div class="vcell" onclick="nxVital('pain','Pain score 0-10','{{ $vital->pain ?? '' }}')"><div class="vval">{{ $vital ? $vital->pain.'/10' : '—' }}</div><div class="vlbl">Pain</div></div>
                </div>
            </div>

            {{-- TAB 0: WARD OVERVIEW --}}
            <div class="tab-panel active" data-panel="0">
                <div class="card">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:#042F2E">🏥</div> {{ $current->ward->ward_name ?? 'Ward' }} — Patient Summary</h3></div>
                    <div class="card-body">
                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px">
                            <div style="background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:12px;text-align:center"><div style="font-family:var(--mono);font-size:26px;color:var(--text)">{{ $summary['occupied'] }}</div><div style="font-size:10px;color:var(--muted);margin-top:3px;text-transform:uppercase">Occupied</div></div>
                            <div style="background:var(--ltred);border:1px solid var(--redA);border-radius:10px;padding:12px;text-align:center"><div style="font-family:var(--mono);font-size:26px;color:var(--red)">{{ $summary['critical'] }}</div><div style="font-size:10px;color:var(--red);margin-top:3px;text-transform:uppercase">Critical</div></div>
                            <div style="background:var(--ltamber);border:1px solid var(--amberA);border-radius:10px;padding:12px;text-align:center"><div style="font-family:var(--mono);font-size:26px;color:var(--amber)">{{ $summary['warning'] }}</div><div style="font-size:10px;color:var(--amber);margin-top:3px;text-transform:uppercase">Warning</div></div>
                            <div style="background:var(--ltgreen);border:1px solid var(--greenA);border-radius:10px;padding:12px;text-align:center"><div style="font-family:var(--mono);font-size:26px;color:var(--green)">{{ $summary['stable'] }}</div><div style="font-size:10px;color:var(--green);margin-top:3px;text-transform:uppercase">Stable</div></div>
                        </div>
                        <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px">Pending Tasks This Shift</div>
                        @forelse($tasks->where('status','pending')->take(6) as $t)
                            <div class="task-row {{ $t->due_time < $nowTime ? 'urgent' : ($t->priority==='soon'?'soon':'') }}">
                                <div class="task-time {{ $t->due_time < $nowTime ? 'red':'' }}">{{ $t->due_time }}</div>
                                <div class="task-desc">{{ $t->description }}</div>
                                <div class="task-bed">{{ $t->bed_label }}</div>
                                @if($canTaskDone)<a href="{{ route('vendor.nursing.task.complete', $t->id) }}" class="task-done">✓</a>@endif
                            </div>
                        @empty
                            <div class="empty" style="padding:18px">No pending tasks. 🎉</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- TAB 1: MAR --}}
            @if($vMar)
            <div class="tab-panel" data-panel="1">
                <div class="card">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:#14532D">💊</div> Medication Administration Record</h3>
                        @if($canMar)<button class="btn btn-teal" style="padding:5px 11px;font-size:11px" onclick="document.getElementById('nxAddMar').style.display='block'">+ Add Medication</button>@endif
                    </div>
                    <div style="padding:12px 14px">
                        @if($current->patient->allergies)
                            <div style="background:var(--ltred);border:1px solid var(--redA);border-radius:8px;padding:8px 12px;margin-bottom:12px;font-size:11px;color:var(--red);font-weight:600">🚨 Allergy alert: {{ $current->patient->allergies }} — verify before administering</div>
                        @endif

                        @if($canMar)
                        <form id="nxAddMar" method="post" action="{{ route('vendor.nursing.mar.order', $current->id) }}" style="display:none;background:var(--bg4);border:1px solid var(--border2);border-radius:8px;padding:10px;margin-bottom:10px">
                            @csrf
                            <div style="display:grid;grid-template-columns:2fr 1fr 80px 1fr;gap:8px;margin-bottom:8px">
                                <input class="nx-input" name="medicine_name" placeholder="Medicine name *" required>
                                <input class="nx-input" name="dose" placeholder="Dose (1 Tab)">
                                <select class="nx-select" name="route"><option>PO</option><option>IV</option><option>IM</option><option>SC</option></select>
                                <input class="nx-input" name="frequency" placeholder="Frequency">
                            </div>
                            <div style="display:flex;gap:8px;align-items:center">
                                <input class="nx-input" name="schedule_times" placeholder="Times (comma sep): 06:00,18:00">
                                <button class="btn btn-teal">Add</button>
                            </div>
                        </form>
                        @endif

                        @if(count($marRows))
                            <div class="mar-time-hd"><div>Medicine</div>@foreach($marTimes as $t)<div>{{ $t }}</div>@endforeach</div>
                            @foreach($marRows as $row)
                                @php $o = $row['order']; $rt = strtolower($o->route ?: 'po'); @endphp
                                <div class="mar-row">
                                    <div>
                                        <div class="med-name">{{ $o->medicine_name }}</div>
                                        <div class="med-dose">{{ $o->dose }}{{ $o->frequency ? ' · '.$o->frequency : '' }}</div>
                                        <span class="med-route {{ $rt }}">{{ strtoupper($o->route ?: 'PO') }}</span>
                                    </div>
                                    @foreach($marTimes as $t)
                                        @php $c = $row['cells'][$t]; @endphp
                                        @if($c['state'] === 'given')
                                            <div class="mar-cell given">✓</div>
                                        @elseif($c['state'] === 'missed')
                                            <div class="mar-cell missed">✗</div>
                                        @elseif($c['state'] === 'due')
                                            @if($canMarGive)
                                                <form class="mar-cellform" method="post" action="{{ route('vendor.nursing.mar.give', $o->id) }}" onsubmit="return confirm('Mark {{ $o->medicine_name }} as GIVEN at {{ $t }}?')">
                                                    @csrf<input type="hidden" name="time" value="{{ $t }}">
                                                    <button type="submit" class="mar-cell due" title="Mark given">DUE</button>
                                                </form>
                                            @else<div class="mar-cell due">DUE</div>@endif
                                        @elseif($c['state'] === 'future')
                                            @if($canMarGive)
                                                <form class="mar-cellform" method="post" action="{{ route('vendor.nursing.mar.give', $o->id) }}" onsubmit="return confirm('Give {{ $o->medicine_name }} early at {{ $t }}?')">
                                                    @csrf<input type="hidden" name="time" value="{{ $t }}">
                                                    <button type="submit" class="mar-cell future">{{ $t }}</button>
                                                </form>
                                            @else<div class="mar-cell future">{{ $t }}</div>@endif
                                        @else
                                            <div class="mar-cell na">—</div>
                                        @endif
                                    @endforeach
                                </div>
                            @endforeach
                        @else
                            <div class="empty">No medications scheduled. Click <strong>+ Add Medication</strong>.</div>
                        @endif
                    </div>
                </div>
            </div>

            @endif

            {{-- TAB 2: FLUID BALANCE --}}
            @if($vFluid)
            <div class="tab-panel" data-panel="2">
                <div class="card">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:#1E3A5F">🧴</div> Fluid Balance — Today</h3></div>
                    <div class="card-body">
                        <div class="fb-summary">
                            <div class="fb-card"><div class="fb-val in">{{ number_format($fbIn) }} ml</div><div class="fb-lbl">Total Intake</div></div>
                            <div class="fb-card"><div class="fb-val out">{{ number_format($fbOut) }} ml</div><div class="fb-lbl">Total Output</div></div>
                            <div class="fb-card"><div class="fb-val {{ $fbNet >= 0 ? 'pos':'neg' }}">{{ $fbNet >= 0 ? '+' : '' }}{{ number_format($fbNet) }} ml</div><div class="fb-lbl">Net Balance</div></div>
                        </div>
                        @forelse($fluids as $f)
                            <div class="fb-row">
                                <div style="font-family:var(--mono);font-size:11px;color:var(--muted)">{{ $f->entry_time }}</div>
                                <div>{{ $f->description }}</div>
                                <div><span class="fb-type {{ $f->type }}">{{ strtoupper($f->type) }}</span></div>
                                <div style="font-family:var(--mono);color:{{ $f->type==='in'?'var(--blue)':'var(--amber)' }}">{{ number_format($f->volume_ml) }} ml</div>
                                <div style="font-size:10px;color:var(--muted)">{{ $nurseNames[$f->recorded_by] ?? '—' }}</div>
                            </div>
                        @empty
                            <div class="empty" style="padding:18px">No fluid entries today.</div>
                        @endforelse
                        @if($canFluid)
                        <form method="post" action="{{ route('vendor.nursing.fluid', $current->id) }}" style="margin-top:8px;padding:10px;background:var(--bg4);border:1px dashed var(--border2);border-radius:8px">
                            @csrf
                            <div style="display:grid;grid-template-columns:1fr 90px 90px 80px;gap:8px;align-items:center">
                                <input class="nx-input" name="description" placeholder="Description (Urine output, NS 500ml…)" required>
                                <select class="nx-select" name="type"><option value="in">IN</option><option value="out">OUT</option></select>
                                <input class="nx-input" name="volume_ml" type="number" step="1" placeholder="ml" required>
                                <button class="btn btn-teal">+ Add</button>
                            </div>
                        </form>
                        @endif
                    </div>
                </div>
            </div>

            @endif

            {{-- TAB 3: NURSING NOTES --}}
            @if($vNote)
            <div class="tab-panel" data-panel="3">
                <div class="card">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:#1E3A5F">📝</div> Nursing Notes</h3>
                        @if($canNote)<button class="btn btn-teal" style="padding:5px 11px;font-size:11px" onclick="var f=document.getElementById('nxAddNote');f.style.display=f.style.display==='none'?'block':'none'">+ Add Note</button>@endif
                    </div>
                    <div class="card-body">
                        @if($canNote)
                        <form id="nxAddNote" method="post" action="{{ route('vendor.nursing.note', $current->id) }}" style="display:none;background:var(--bg4);border:1px solid var(--border2);border-radius:10px;padding:12px;margin-bottom:10px">
                            @csrf
                            <textarea class="nx-input" name="note" rows="4" placeholder="Document patient condition, vitals, interventions, response…" style="resize:none;margin-bottom:8px;line-height:1.6" required></textarea>
                            <div style="display:flex;gap:8px;justify-content:flex-end"><button type="button" class="btn btn-outline" onclick="document.getElementById('nxAddNote').style.display='none'">Cancel</button><button class="btn btn-teal">Save Note</button></div>
                        </form>
                        @endif
                        @forelse($notes as $n)
                            <div class="note-item">
                                <div class="note-meta"><span class="note-nurse">{{ $nurseNames[$n->recorded_by] ?? 'Nurse' }}</span><span>{{ optional($n->recorded_at)->format('d M, h:i A') }}</span><span>{{ $n->note_type }}</span></div>
                                <div class="note-text">{{ $n->note }}</div>
                            </div>
                        @empty
                            <div class="empty">No nursing notes yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            @endif

            {{-- TAB 4: SHIFT HANDOVER --}}
            @if($vHandover)
            <div class="tab-panel" data-panel="4">
                <div class="card">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:#042F2E">🔁</div> Shift Handover</h3></div>
                    <div class="card-body">
                        <form method="post" action="{{ route('vendor.nursing.handover') }}">
                            @csrf
                            <input type="hidden" name="ward_id" value="{{ $current->ward_id }}">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
                                <div><div class="ho-sec-title">Outgoing nurse</div><input class="nx-input" name="outgoing_nurse" value="{{ $handover->outgoing_nurse ?? $nurseName }}"></div>
                                <div><div class="ho-sec-title">Incoming nurse</div><input class="nx-input" name="incoming_nurse" value="{{ $handover->incoming_nurse ?? '' }}" placeholder="Name of nurse taking over"></div>
                            </div>

                            <div class="ho-sec-title">Critical / warning patients</div>
                            @forelse($admissions->whereIn('severity', ['crit','warn']) as $a)
                                <div class="ho-item"><div class="ho-text">{{ $a->bed->bed_number ?? '' }} — {{ $a->patient->name ?? '' }} — {{ $a->diagnosis ?: 'IPD' }} <span style="color:{{ $a->severity==='crit'?'var(--red)':'var(--amber)' }}">({{ strtoupper($a->severity) }})</span></div></div>
                            @empty
                                <div class="ho-item"><div class="ho-text" style="color:var(--muted)">No critical/warning patients this shift.</div></div>
                            @endforelse

                            <div class="ho-sec-title">Pending tasks for next shift</div>
                            @forelse($tasks->where('status','pending') as $t)
                                <div class="ho-item"><div class="ho-text">{{ $t->due_time }} · {{ $t->description }} <span class="ho-meta">({{ $t->bed_label }})</span></div></div>
                            @empty
                                <div class="ho-item"><div class="ho-text" style="color:var(--muted)">No pending tasks.</div></div>
                            @endforelse

                            <div class="ho-sec-title">Additional notes for incoming shift</div>
                            <textarea class="nx-input" name="notes" rows="3" style="resize:none">{{ $handover->notes ?? '' }}</textarea>

                            @if($canHandover)
                            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
                                <button class="btn btn-outline" name="complete" value="0">Save Draft</button>
                                <button class="btn btn-teal" name="complete" value="1">Complete Handover</button>
                            </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>

            @endif

            {{-- TAB 5: TASK QUEUE --}}
            @if($vTask)
            <div class="tab-panel" data-panel="5">
                <div class="card">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:#042F2E">📋</div> Task Queue — {{ $current->patient->name ?? 'Patient' }} · {{ $current->bed->bed_number ?? '' }}</h3>
                        @if($canTask)<button class="btn btn-teal" style="padding:5px 11px;font-size:11px" onclick="var f=document.getElementById('nxAddTask');f.style.display=f.style.display==='none'?'block':'none'">+ Add Task</button>@endif
                    </div>
                    <div class="card-body">
                        @if($canTask)
                        <form id="nxAddTask" method="post" action="{{ route('vendor.nursing.task.add') }}" style="display:none;background:var(--bg4);border:1px solid var(--border2);border-radius:8px;padding:10px;margin-bottom:10px">
                            @csrf
                            <input type="hidden" name="ipd_admission_id" value="{{ $current->id }}">
                            <div style="display:grid;grid-template-columns:1fr 90px 110px 80px;gap:8px;align-items:center">
                                <input class="nx-input" name="description" placeholder="Task description *" required>
                                <input class="nx-input" name="due_time" placeholder="HH:MM" value="{{ $nowTime }}">
                                <select class="nx-select" name="priority"><option value="normal">Normal</option><option value="soon">Due soon</option><option value="urgent">Urgent</option></select>
                                <button class="btn btn-teal">+ Add</button>
                            </div>
                        </form>
                        @endif
                        @forelse($patientTasks as $t)
                            <div class="task-row {{ $t->status==='done' ? 'done' : ($t->status==='pending' && $t->due_time < $nowTime ? 'urgent' : ($t->priority==='soon'?'soon':'')) }}">
                                <div class="task-time {{ $t->status==='pending' && $t->due_time < $nowTime ? 'red':'' }}">{{ $t->due_time }}</div>
                                <div class="task-desc">{{ $t->description }}</div>
                                <div class="task-bed">{{ $t->bed_label }}</div>
                                @if($canTaskDone)<a href="{{ route('vendor.nursing.task.complete', $t->id) }}" class="task-done {{ $t->status==='done'?'done':'' }}">✓</a>@endif
                            </div>
                        @empty
                            <div class="empty">No tasks for this patient today. Click <strong>+ Add Task</strong>.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            @endif

            {{-- TAB 6: VITALS TREND --}}
            @if($vVitals)
            <div class="tab-panel" data-panel="6">
                <div class="card">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:#1E3A5F">📈</div> Vitals Trend — Today</h3></div>
                    <div class="card-body">
                        @if($vitalsTrend->count())
                            <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px">Blood Pressure — Systolic (mmHg)</div>
                            @foreach($vitalsTrend as $v)
                                @if($v->bp_systolic)
                                    <div class="vchart-row"><div class="vchart-lbl">{{ optional($v->recorded_at)->format('H:i') }}</div><div class="vchart-bar-wrap"><div class="vchart-bar" style="width:{{ min(100,$v->bp_systolic/2) }}%;background:{{ $v->bp_systolic>=140?'var(--red)':'var(--greenA)' }}"><span class="vchart-val">{{ $v->bp_systolic }}</span></div></div></div>
                                @endif
                            @endforeach
                            <div class="divider"></div>
                            <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px">SpO₂ (%)</div>
                            @foreach($vitalsTrend as $v)
                                @if($v->spo2)
                                    <div class="vchart-row"><div class="vchart-lbl">{{ optional($v->recorded_at)->format('H:i') }}</div><div class="vchart-bar-wrap"><div class="vchart-bar" style="width:{{ $v->spo2 }}%;background:{{ $v->spo2>=95?'var(--greenA)':'var(--amberA)' }}"><span class="vchart-val">{{ $v->spo2 }}%</span></div></div></div>
                                @endif
                            @endforeach
                            <div class="divider"></div>
                            <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px">Heart Rate (/min)</div>
                            @foreach($vitalsTrend as $v)
                                @if($v->hr)
                                    <div class="vchart-row"><div class="vchart-lbl">{{ optional($v->recorded_at)->format('H:i') }}</div><div class="vchart-bar-wrap"><div class="vchart-bar" style="width:{{ min(100,$v->hr/2) }}%;background:var(--blue)"><span class="vchart-val">{{ $v->hr }}</span></div></div></div>
                                @endif
                            @endforeach
                        @else
                            <div class="empty">No vitals recorded today. Tap a vital on the patient banner to record one.</div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            @endif
        </div>
    </div>

    {{-- COMPLETE BAR --}}
    <div class="complete-bar">
        <div style="font-size:12px;color:var(--muted)">
            <strong style="color:var(--text)">{{ $current->ward->ward_name ?? 'Ward' }} · {{ $shift }} Shift</strong> · {{ $nurseName }}
            <span style="margin-left:10px;font-size:11px;color:var(--teal);font-weight:600">{{ $tasks->where('status','done')->count() }} of {{ $tasks->count() }} tasks completed</span>
        </div>
    </div>

    {{-- VITAL MODAL --}}
    @if($current && $canVitals)
    <form id="nxVitalForm" method="post" action="{{ route('vendor.nursing.vitals', $current->id) }}" style="position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1090;display:none;align-items:center;justify-content:center">
        @csrf
        <input type="hidden" name="metric" id="nxVitalMetric">
        <div class="card" style="max-width:360px;width:90%;margin:0" onclick="event.stopPropagation()">
            <div class="card-hd"><h3 id="nxVitalTitle">Record Vital</h3></div>
            <div class="card-body">
                <input class="nx-input" name="value" id="nxVitalValue" placeholder="Enter value" style="margin-bottom:14px">
                <div style="display:flex;gap:8px;justify-content:flex-end"><button type="button" class="btn btn-outline" onclick="document.getElementById('nxVitalForm').style.display='none'">Cancel</button><button class="btn btn-teal">Save</button></div>
            </div>
        </div>
    </form>
    @endif
</div>
</div>
@endsection

@push('script_2')
<script>
(function(){
    // Tab switching with persistence across reloads (forms redirect back)
    var tabs = document.querySelectorAll('.nursex .tab');
    var panels = document.querySelectorAll('.nursex .tab-panel');
    function activate(i){
        tabs.forEach(function(t){ t.classList.toggle('active', t.dataset.tab == i); });
        panels.forEach(function(p){ p.classList.toggle('active', p.dataset.panel == i); });
        try { localStorage.setItem('nxTab', i); } catch(e){}
    }
    tabs.forEach(function(t){ t.addEventListener('click', function(){ activate(this.dataset.tab); }); });
    var saved = null; try { saved = localStorage.getItem('nxTab'); } catch(e){}
    // Fall back to Ward Overview if the saved tab is no longer permitted (button removed).
    var savedExists = saved !== null && document.querySelector('.nursex .tab[data-tab="' + saved + '"]');
    if (saved !== null && !savedExists) saved = '0';
    if (saved !== null) activate(saved);

    // Clock
    setInterval(function(){
        var el = document.getElementById('nxClock');
        if (el) el.textContent = new Date().toLocaleTimeString('en-GB');
    }, 1000);

    // Vital modal
    window.nxVital = function(metric, label, val){
        var form = document.getElementById('nxVitalForm'); if (!form) return; // no edit permission
        document.getElementById('nxVitalMetric').value = metric;
        document.getElementById('nxVitalTitle').textContent = 'Record ' + label;
        var v = document.getElementById('nxVitalValue'); v.value = val || ''; v.placeholder = label;
        form.style.display = 'flex';
        v.focus();
    };
})();
</script>
@endpush
