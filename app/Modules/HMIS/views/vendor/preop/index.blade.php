@extends('layouts.vendor.app')
@section('title', 'Pre-Op Preparation')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.content.container-fluid{padding:0!important;margin:0!important;max-width:100%!important;width:100%!important}
.preopx{--bg:#07101A;--bg2:#0D1B2A;--bg3:#112236;--bg4:#162B44;--border:#1E3350;--border2:#2A4568;--text:#EBF0F8;--muted:#9CB4CE;--light:#C7D6E6;--blue:#3B9EFF;--blue2:#1A5FA8;--ltblue:#0A1F3D;--green:#2ECC71;--ltgreen:#0A2E1A;--greenA:#27AE60;--red:#E74C3C;--ltred:#2E0A0A;--redA:#C0392B;--amber:#F39C12;--ltamber:#2E1A00;--amberA:#D68910;--purple:#9B59B6;--ltpurple:#1E0A2E;--teal:#1ABC9C;--ltteal:#0A2E26;--cyan:#00BCD4;--ltcyan:#00272E;--gold:#F1C40F;--ltgold:#2E2600;--font:'Outfit',sans-serif;--mono:'DM Mono',monospace;
  background:var(--bg);color:var(--text);font-family:var(--font);font-size:13px;display:block}
.preopx *{box-sizing:border-box}
.preopx .topnav{background:var(--bg2);border-bottom:1px solid var(--border);min-height:52px;display:flex;align-items:center;padding:8px 18px;gap:12px;flex-wrap:wrap}
.preopx .tn-page{font-size:14px;font-weight:800;color:var(--text)}.preopx .tn-page span{color:var(--teal)}
.preopx .tn-sep{width:1px;height:22px;background:var(--border)}
.preopx .tn-right{display:flex;align-items:center;gap:10px;margin-left:auto;flex-wrap:wrap}
.preopx .tn-badge{border-radius:7px;padding:4px 12px;font-size:11px;font-weight:700;border:1px solid}
.preopx .tn-badge.surg{background:var(--ltpurple);border-color:var(--purple);color:var(--purple)}
.preopx .tn-badge.ot{background:var(--ltcyan);border-color:var(--cyan);color:var(--cyan)}
.preopx .tn-badge.stat{background:var(--ltamber);border-color:var(--amberA);color:var(--amber)}
.preopx .nx-sel{background:var(--bg4);border:1px solid var(--border2);border-radius:7px;padding:6px 10px;font-size:12px;color:var(--text);font-family:var(--font);outline:none;cursor:pointer}
.preopx .progress-bar-wrap{background:var(--bg2);border-bottom:1px solid var(--border);padding:12px 18px}
.preopx .progress-title{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;display:flex;justify-content:space-between}
.preopx .pct-label{font-size:12px;font-weight:700;color:var(--teal)}
.preopx .progress-steps{display:flex;align-items:flex-start;overflow-x:auto}
.preopx .progress-steps::-webkit-scrollbar{display:none}
.preopx .pstep{display:flex;align-items:center;flex-shrink:0}
.preopx .pstep-inner{display:flex;flex-direction:column;align-items:center;cursor:pointer}
.preopx .pstep-circle{width:30px;height:30px;border-radius:50%;border:2px solid var(--border2);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;background:var(--bg3);color:var(--muted)}
.preopx .pstep-circle.done{background:var(--ltgreen);border-color:var(--greenA);color:var(--green)}
.preopx .pstep-circle.active{background:var(--ltblue);border-color:var(--blue);color:var(--blue);box-shadow:0 0 0 4px rgba(59,158,255,.15)}
.preopx .pstep-circle.warn{background:var(--ltamber);border-color:var(--amberA);color:var(--amber)}
.preopx .pstep-label{font-size:9px;color:var(--muted);text-align:center;margin-top:4px;white-space:nowrap;max-width:74px}
.preopx .pstep-label.done{color:var(--green)}.preopx .pstep-label.active{color:var(--blue)}.preopx .pstep-label.warn{color:var(--amber)}
.preopx .pstep-line{flex:1;height:2px;background:var(--border);min-width:18px;margin:0 4px;margin-top:14px}
.preopx .pstep-line.done{background:var(--greenA)}
.preopx .tabs{background:var(--bg2);border-bottom:1px solid var(--border);padding:0 14px;display:flex;overflow-x:auto}
.preopx .tabs::-webkit-scrollbar{display:none}
.preopx .tab{padding:11px 12px;font-size:12px;font-weight:600;color:var(--muted);cursor:pointer;border-bottom:2px solid transparent;display:flex;align-items:center;gap:5px;white-space:nowrap}
.preopx .tab:hover{color:var(--light)}.preopx .tab.active{color:var(--teal);border-bottom-color:var(--teal)}
.preopx .tbadge{font-size:9px;font-weight:700;padding:1px 5px;border-radius:10px;background:var(--redA);color:#fff}.preopx .tbadge.am{background:var(--amberA)}
.preopx .layout{display:grid;grid-template-columns:270px 1fr;min-height:480px}
.preopx .left-panel{background:var(--bg2);border-right:1px solid var(--border);padding:14px;max-height:74vh;overflow-y:auto}
.preopx .right-col{padding:16px;background:var(--bg);max-height:74vh;overflow-y:auto}
.preopx .pt-card{background:var(--bg3);border:1px solid var(--border2);border-radius:12px;padding:14px;margin-bottom:12px}
.preopx .pt-av{width:48px;height:48px;border-radius:12px;background:var(--ltpurple);border:2px solid var(--purple);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;color:var(--purple)}
.preopx .pt-name{font-size:15px;font-weight:700;margin-top:8px}
.preopx .pt-meta{font-size:11px;color:var(--muted);margin-top:2px}
.preopx .tag-row{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px}
.preopx .tag{font-size:10px;font-weight:700;padding:2px 8px;border-radius:5px}
.preopx .tag.rd{background:var(--ltred);color:var(--red);border:1px solid var(--redA)}.preopx .tag.am{background:var(--ltamber);color:var(--amber);border:1px solid var(--amberA)}.preopx .tag.bl{background:var(--ltblue);color:var(--blue);border:1px solid var(--blue2)}.preopx .tag.cy{background:var(--ltcyan);color:var(--cyan);border:1px solid var(--cyan)}
.preopx .ot-info{background:linear-gradient(135deg,var(--ltpurple),var(--ltcyan));border:1px solid var(--border2);border-radius:12px;padding:14px;margin-bottom:12px}
.preopx .ot-info-title{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:10px}
.preopx .ot-detail-row{display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid rgba(255,255,255,.05);font-size:11px}
.preopx .ot-detail-row:last-child{border-bottom:none}.preopx .ot-dl{color:var(--muted)}.preopx .ot-dv{font-weight:600;text-align:right}.preopx .ot-dv.cy{color:var(--cyan)}.preopx .ot-dv.am{color:var(--amber)}.preopx .ot-dv.gr{color:var(--green)}
.preopx .cs-title{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;display:flex;justify-content:space-between}
.preopx .cs-pct{font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;background:var(--bg4);color:var(--teal)}
.preopx .cs-item{display:flex;align-items:flex-start;gap:8px;padding:6px 8px;border-radius:7px;border:1px solid var(--border);margin-bottom:4px;background:var(--bg3);cursor:pointer;text-decoration:none}
.preopx .cs-item:hover{border-color:var(--border2)}.preopx .cs-item.done{background:var(--ltgreen);border-color:var(--greenA)}
.preopx .cs-chk{width:16px;height:16px;border-radius:4px;border:1.5px solid var(--border2);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;margin-top:1px}
.preopx .cs-chk.done{background:var(--greenA);border-color:var(--greenA);color:#fff}
.preopx .cs-text{font-size:11px;color:var(--light);flex:1;line-height:1.4}.preopx .cs-item.done .cs-text{color:var(--green)}
.preopx .card{background:var(--bg2);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.preopx .card-hd{display:flex;align-items:center;justify-content:space-between;padding:11px 15px;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:8px}
.preopx .card-hd h3{font-size:12px;font-weight:700;display:flex;align-items:center;gap:8px;margin:0}
.preopx .hd-icon{width:24px;height:24px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:13px}
.preopx .card-body{padding:13px 15px}
.preopx .tab-panel{display:none}.preopx .tab-panel.active{display:block}
.preopx .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px}
.preopx .form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:12px}
.preopx .form-group{display:flex;flex-direction:column;gap:4px}
.preopx .form-label{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px}
.preopx .finput,.preopx .fselect,.preopx .ftextarea{background:var(--bg4);border:1px solid var(--border2);border-radius:7px;padding:7px 10px;font-size:12px;color:var(--text);font-family:var(--font);outline:none;width:100%}
.preopx .finput:focus,.preopx .fselect:focus,.preopx .ftextarea:focus{border-color:var(--teal)}
/* Dark surface, so anything that falls back to the panel's default #1e2022 text — native
   select popups, placeholders, autofilled values, date/time pickers — comes out black on
   navy and is effectively unreadable. Pin the colours to the palette instead. */
.preopx{color-scheme:dark}
/* The panel theme colours headings and small print with its own #1e2022 rule, which beats
   inheritance from .preopx — so card titles came out black on navy. */
.preopx h1,.preopx h2,.preopx h3,.preopx h4,.preopx h5,.preopx h6,.preopx p,.preopx label,
.preopx small,.preopx strong,.preopx b,.preopx th,.preopx td,.preopx li,.preopx span,.preopx div{color:inherit}
.preopx .card-hd h3{color:var(--text)}
.preopx .form-label{color:#DCE6F2;font-weight:800}
.preopx .ot-info-title,.preopx .cs-title,.preopx .progress-title,.preopx .result-name{color:#C7D6E6}
.preopx .pstep-label,.preopx .pt-meta,.preopx .report-meta,.preopx .ot-dl{color:#B4C7DA}
.preopx a:not(.btn){color:var(--blue)}
.preopx input,.preopx select,.preopx textarea,.preopx .form-control,.preopx .custom-select{color:var(--text);-webkit-text-fill-color:var(--text);background-color:var(--bg4);border-color:var(--border2)}
.preopx select option,.preopx select optgroup{background:var(--bg2);color:var(--text)}
.preopx ::placeholder{color:var(--muted);opacity:1}
.preopx input:-webkit-autofill,.preopx input:-webkit-autofill:focus{-webkit-text-fill-color:var(--text);-webkit-box-shadow:0 0 0 1000px var(--bg4) inset;caret-color:var(--text)}
.preopx input[type=date],.preopx input[type=datetime-local],.preopx input[type=time]{color-scheme:dark}
.preopx ::-webkit-calendar-picker-indicator{filter:invert(1) opacity(.7)}
.preopx .ftextarea{resize:none;line-height:1.6}
.preopx .result-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:12px}
.preopx .result-card{background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:11px 13px}
.preopx .result-card.normal{border-color:var(--greenA);background:var(--ltgreen)}.preopx .result-card.abnormal{border-color:var(--redA);background:var(--ltred)}.preopx .result-card.borderline{border-color:var(--amberA);background:var(--ltamber)}
.preopx .result-name{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:4px}
.preopx .result-val{font-family:var(--mono);font-size:16px;color:var(--text)}
.preopx .result-card.abnormal .result-val{color:var(--red)}.preopx .result-card.borderline .result-val{color:var(--amber)}.preopx .result-card.normal .result-val{color:var(--green)}
.preopx .result-ref{font-size:9px;color:var(--muted);margin-top:3px}
.preopx .med-row{display:grid;grid-template-columns:1.8fr .7fr 1fr 1fr 150px;gap:8px;align-items:center;padding:8px 10px;background:var(--bg3);border-radius:8px;margin-bottom:4px;border:1px solid var(--border);font-size:12px}
.preopx .med-row.given{border-color:var(--greenA);background:var(--ltgreen)}.preopx .med-row.held{border-color:var(--amberA);background:var(--ltamber)}.preopx .med-row.stop{border-color:var(--redA);background:var(--ltred);opacity:.75}
.preopx .med-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:5px}
.preopx .med-badge.given{background:var(--ltgreen);color:var(--green);border:1px solid var(--greenA)}.preopx .med-badge.held{background:var(--ltamber);color:var(--amber);border:1px solid var(--amberA)}.preopx .med-badge.stop{background:var(--ltred);color:var(--red);border:1px solid var(--redA)}.preopx .med-badge.due{background:var(--ltblue);color:var(--blue);border:1px solid var(--blue2)}
.preopx .consent-row{display:flex;align-items:center;gap:12px;padding:10px 13px;background:var(--bg3);border:1px solid var(--border);border-radius:9px;margin-bottom:5px}
.preopx .consent-row.signed{border-color:var(--greenA);background:var(--ltgreen)}.preopx .consent-row.pending{border-color:var(--amberA)}
.preopx .consent-status{font-size:10px;font-weight:700;padding:3px 10px;border-radius:5px;white-space:nowrap}
.preopx .consent-status.signed{background:var(--ltgreen);color:var(--green);border:1px solid var(--greenA)}.preopx .consent-status.pending{background:var(--ltamber);color:var(--amber);border:1px solid var(--amberA)}
.preopx .report-row{display:flex;align-items:center;gap:12px;padding:9px 12px;background:var(--bg3);border:1px solid var(--border);border-radius:9px;margin-bottom:5px}
.preopx .report-icon{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;background:var(--ltblue)}
.preopx .report-name{font-size:12px;font-weight:600}.preopx .report-meta{font-size:10px;color:var(--muted);margin-top:2px}
.preopx .rbadge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:5px;margin-left:auto;white-space:nowrap}
.preopx .rbadge.ok{background:var(--ltgreen);color:var(--green);border:1px solid var(--greenA)}.preopx .rbadge.pend{background:var(--ltblue);color:var(--blue);border:1px solid var(--blue2)}
.preopx .asa-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:12px}
.preopx .asa-card{border-radius:10px;border:2px solid var(--border);padding:10px;text-align:center;cursor:pointer;display:block}
.preopx .asa-card.sel{border-color:var(--teal);background:var(--ltteal)}
.preopx .asa-num{font-family:var(--mono);font-size:20px;color:var(--text)}.preopx .asa-card.sel .asa-num{color:var(--teal)}
.preopx .asa-label{font-size:9px;color:var(--muted);margin-top:3px;line-height:1.4}
.preopx .clearance-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:12px}
.preopx .clr-card{background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:12px 14px}
.preopx .clr-card.cleared{border-color:var(--greenA);background:var(--ltgreen)}.preopx .clr-card.blocked{border-color:var(--redA);background:var(--ltred)}
.preopx .clr-title{font-size:11px;font-weight:700;margin-bottom:4px}.preopx .clr-card.cleared .clr-title{color:var(--green)}.preopx .clr-card.blocked .clr-title{color:var(--red)}
.preopx .clr-by{font-size:10px;color:var(--muted)}.preopx .clr-status{font-size:10px;font-weight:700;margin-top:6px;color:var(--amber)}.preopx .clr-card.cleared .clr-status{color:var(--green)}
.preopx .btn{padding:7px 14px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;border:none;font-family:var(--font);white-space:nowrap}
.preopx .btn-teal{background:var(--teal);color:#041A14}.preopx .btn-green{background:var(--greenA);color:#fff}.preopx .btn-red{background:var(--redA);color:#fff}.preopx .btn-purple{background:var(--purple);color:#fff}
.preopx .btn-outline{background:none;border:1px solid var(--border2);color:var(--muted)}.preopx .btn-outline:hover{border-color:var(--teal);color:var(--teal)}
.preopx .btn-xs{padding:3px 9px;font-size:10px;border-radius:6px}
.preopx .bottom-bar{background:var(--bg2);border-top:1px solid var(--border);padding:11px 18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.preopx .empty{text-align:center;color:var(--muted);padding:36px 16px;font-size:13px}
.preopx table.bu{width:100%;border-collapse:collapse;font-size:12px}.preopx table.bu th{text-align:left;color:var(--muted);font-size:10px;text-transform:uppercase;padding:6px 8px;border-bottom:1px solid var(--border)}.preopx table.bu td{padding:7px 8px;border-bottom:1px solid var(--border)}
@media(max-width:900px){.preopx .layout{grid-template-columns:1fr}.preopx .result-grid,.preopx .form-grid,.preopx .form-grid-3,.preopx .clearance-grid{grid-template-columns:1fr 1fr}.preopx .asa-grid{grid-template-columns:repeat(3,1fr)}}
</style>
@endpush

@section('content')
<div class="content container-fluid"><div class="preopx">
    @php
        // Tab visibility (view) per sub-feature
        $vSchedule    = hasPermission('preop_schedule', 'view');
        $vCase        = hasPermission('preop_case', 'view');
        $vResult      = hasPermission('preop_result', 'view');
        $vMed         = hasPermission('preop_med', 'view');
        $vConsent     = hasPermission('preop_consent', 'view');
        $vAnaesthesia = hasPermission('preop_anaesthesia', 'view');
        $vBlood       = hasPermission('preop_blood', 'view');
        $vChecklist   = hasPermission('preop_checklist', 'view');
        $vClearance   = hasPermission('preop_clearance', 'view');
        $vHandover    = hasPermission('preop_handover', 'view');
        // Action permissions
        $canSchedule    = hasPermission('preop_schedule', 'add');
        $canCase        = hasPermission('preop_case', 'edit');
        $canChecklist   = hasPermission('preop_checklist', 'edit');
        $canMedAdd      = hasPermission('preop_med', 'add');
        $canMedStatus   = hasPermission('preop_med', 'edit');
        $canConsentAdd  = hasPermission('preop_consent', 'add');
        $canConsentSign = hasPermission('preop_consent', 'edit');
        $canClearance   = hasPermission('preop_clearance', 'edit');
        $canAnaesthesia = hasPermission('preop_anaesthesia', 'edit');
        $canResult      = hasPermission('preop_result', 'add');
        $canBlood       = hasPermission('preop_blood', 'add');
        $canHandover    = hasPermission('preop_handover', 'edit');
        $c = $current;
        $statusIcon = ['done' => '✓', 'warn' => '!', 'active' => '', 'pending' => ''];
        $cnInvest = $c ? $c->checks->where('category', 'investigation') : collect();
        $cnPrep = $c ? $c->checks->where('category', 'prep') : collect();
        $cnHandover = $c ? $c->checks->where('category', 'handover') : collect();
        $cnQuick = $c ? $c->checks->where('category', 'quick') : collect();
        $quickPct = $cnQuick->count() ? round($cnQuick->where('status', 'done')->count() / $cnQuick->count() * 100) : 0;
        $latestVital = $c && $c->ipd_admission_id ? \App\Models\NursingVital::where('ipd_admission_id', $c->ipd_admission_id)->latest('recorded_at')->latest('id')->first() : null;
    @endphp

    {{-- TOP NAV --}}
    <div class="topnav">
        <div class="tn-page">Pre-Op <span>Preparation</span></div>
        <div class="tn-sep"></div>
        <form method="get" class="mb-0">
            <select name="case" class="nx-sel" onchange="this.form.submit()">
                @forelse($cases as $cs)
                    <option value="{{ $cs->id }}" {{ $c && $cs->id === $c->id ? 'selected' : '' }}>{{ $cs->patient->name ?? 'Patient' }} — {{ \Illuminate\Support\Str::limit($cs->procedure, 26) }}</option>
                @empty
                    <option>No cases scheduled</option>
                @endforelse
            </select>
        </form>
        <div class="tn-right">
            @if($c)
                <div class="tn-badge surg">🔪 {{ \Illuminate\Support\Str::limit($c->procedure, 30) }}</div>
                <div class="tn-badge ot">🏥 {{ $c->ot_room ?: 'OT' }} · {{ optional($c->scheduled_at)->format('H:i') }}</div>
            @endif
            @if($canSchedule)<button class="btn btn-teal btn-xs" onclick="document.getElementById('schedOverlay').style.display='flex'">+ Schedule Surgery</button>@endif
        </div>
    </div>

    @if(!$c)
        <div style="padding:40px 18px">
            <div class="card" style="max-width:760px;margin:0 auto">
                <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltpurple)">🔪</div> Schedule a Surgery for Pre-Op Preparation</h3></div>
                <div class="card-body">@if($canSchedule)@include('hmis::vendor.preop._schedule_form')@else<div class="empty" style="padding:30px">No pre-op cases scheduled.</div>@endif</div>
            </div>
        </div>
    @else
        {{-- PROGRESS STEPS --}}
        <div class="progress-bar-wrap">
            <div class="progress-title"><span>Pre-Op Preparation Progress</span><span class="pct-label">{{ $stepsDone }} / {{ count($steps) }} complete</span></div>
            <div class="progress-steps">
                @foreach($steps as $i => $step)
                    @if($i > 0)<div class="pstep-line {{ $steps[$i-1][1] === 'done' ? 'done' : '' }}"></div>@endif
                    <div class="pstep"><div class="pstep-inner" onclick="preopTab({{ $i }})">
                        <div class="pstep-circle {{ $step[1] }}">{{ $statusIcon[$step[1]] ?: ($i+1) }}</div>
                        <div class="pstep-label {{ $step[1] }}">{{ $step[0] }}</div>
                    </div></div>
                @endforeach
            </div>
        </div>

        {{-- TABS --}}
        <div class="tabs">
            @if($vCase)<div class="tab active" data-tab="0">📋 Admission</div>@endif
            @if($vResult)<div class="tab" data-tab="1">🧪 Investigations</div>@endif
            @if($vResult)<div class="tab" data-tab="2">📁 Reports</div>@endif
            @if($vMed)<div class="tab" data-tab="3">💊 Medicines</div>@endif
            @if($vConsent)<div class="tab" data-tab="4">✍ Consent</div>@endif
            @if($vAnaesthesia)<div class="tab" data-tab="5">😷 Anaesthesia @if($steps[5][1] !== 'done')<span class="tbadge am">!</span>@endif</div>@endif
            @if($vBlood)<div class="tab" data-tab="6">🩸 Blood Bank</div>@endif
            @if($vChecklist)<div class="tab" data-tab="7">🛁 Pre-Op Prep</div>@endif
            @if($vClearance)<div class="tab" data-tab="8">✅ Clearances</div>@endif
            @if($vHandover)<div class="tab" data-tab="9">🚪 OT Handover</div>@endif
        </div>

        <div class="layout">
            {{-- LEFT PANEL --}}
            <div class="left-panel">
                <div class="pt-card">
                    <div class="pt-av">{{ strtoupper(substr($c->patient->name ?? 'P', 0, 1)) }}</div>
                    <div class="pt-name">{{ $c->patient->name ?? 'Patient' }}</div>
                    <div class="pt-meta">{{ $c->patient->dob ? \Carbon\Carbon::parse($c->patient->dob)->age : '' }}{{ $c->patient->gender ? strtoupper(substr($c->patient->gender,0,1)) : '' }} · {{ $c->patient->patient_uid ?? '' }} · {{ $c->admission->bed->bed_number ?? '' }} · Blood: {{ $c->patient->blood_group ?: '—' }}</div>
                    <div class="tag-row">
                        @if($c->patient->allergies)<span class="tag rd">⚠ {{ $c->patient->allergies }}</span>@endif
                        @if($c->diagnosis)<span class="tag am">{{ \Illuminate\Support\Str::limit($c->diagnosis, 24) }}</span>@endif
                        <span class="tag cy">{{ $c->admission->ward->ward_name ?? 'IPD' }}</span>
                    </div>
                </div>

                <div class="ot-info">
                    <div class="ot-info-title">Scheduled Procedure</div>
                    <div class="ot-detail-row"><span class="ot-dl">Procedure</span><span class="ot-dv cy">{{ \Illuminate\Support\Str::limit($c->procedure, 22) }}</span></div>
                    <div class="ot-detail-row"><span class="ot-dl">Surgeon</span><span class="ot-dv">{{ $c->surgeon ?: '—' }}</span></div>
                    <div class="ot-detail-row"><span class="ot-dl">Anaesthetist</span><span class="ot-dv">{{ $c->anaesthetist ?: '—' }}</span></div>
                    <div class="ot-detail-row"><span class="ot-dl">OT</span><span class="ot-dv cy">{{ $c->ot_room ?: '—' }} · {{ optional($c->scheduled_at)->format('d M, H:i') }}</span></div>
                    <div class="ot-detail-row"><span class="ot-dl">Est. Duration</span><span class="ot-dv">{{ $c->est_duration ?: '—' }}</span></div>
                    <div class="ot-detail-row"><span class="ot-dl">Anaesthesia</span><span class="ot-dv am">{{ $c->anaesthesia_type ?: 'Pending' }}</span></div>
                    <div class="ot-detail-row"><span class="ot-dl">NBM Since</span><span class="ot-dv gr">{{ $c->nbm_since ?: '—' }}</span></div>
                    <div class="ot-detail-row"><span class="ot-dl">Status</span><span class="ot-dv am">{{ ucfirst(str_replace('_',' ',$c->status)) }}</span></div>
                </div>

                <div class="cs-title">Quick Checklist <span class="cs-pct">{{ $quickPct }}%</span></div>
                @foreach($cnQuick as $chk)
                    <a href="{{ $canChecklist ? route('vendor.preop.check.toggle', $chk->id) : 'javascript:void(0)' }}" @if(!$canChecklist)style="cursor:default"@endif class="cs-item {{ $chk->status === 'done' ? 'done' : '' }}">
                        <div class="cs-chk {{ $chk->status === 'done' ? 'done' : '' }}">{{ $chk->status === 'done' ? '✓' : '' }}</div>
                        <div class="cs-text">{{ $chk->label }}</div>
                    </a>
                @endforeach
            </div>

            {{-- RIGHT --}}
            <div class="right-col">
                {{-- TAB 0: ADMISSION --}}
                @if($vCase)
                <div class="tab-panel active" data-panel="0">
                    <form method="post" action="{{ route('vendor.preop.update', $c->id) }}">
                        @csrf
                        <div class="card">
                            <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltpurple)">🔪</div> Surgical Details</h3>@if($canCase)<button class="btn btn-teal btn-xs">Save</button>@endif</div>
                            <div class="card-body">
                                <div class="form-grid">
                                    <div class="form-group"><label class="form-label">Patient</label><input class="finput" value="{{ $c->patient->name ?? '' }}" readonly></div>
                                    <div class="form-group"><label class="form-label">Diagnosis</label><input class="finput" value="{{ $c->diagnosis }}" readonly></div>
                                    <div class="form-group"><label class="form-label">Procedure</label><input class="finput" name="procedure" value="{{ $c->procedure }}"></div>
                                    <div class="form-group"><label class="form-label">ICD Procedure Code</label><input class="finput" name="icd_code" value="{{ $c->icd_code }}"></div>
                                    <div class="form-group"><label class="form-label">Surgeon</label><input class="finput" name="surgeon" value="{{ $c->surgeon }}"></div>
                                    <div class="form-group"><label class="form-label">1st Assistant</label><input class="finput" name="assistant" value="{{ $c->assistant }}"></div>
                                    <div class="form-group"><label class="form-label">Anaesthetist</label><input class="finput" name="anaesthetist" value="{{ $c->anaesthetist }}"></div>
                                    <div class="form-group"><label class="form-label">OT Room</label><input class="finput" name="ot_room" value="{{ $c->ot_room }}"></div>
                                    <div class="form-group"><label class="form-label">Est. Duration</label><input class="finput" name="est_duration" value="{{ $c->est_duration }}"></div>
                                    <div class="form-group"><label class="form-label">Anaesthesia Type</label>
                                        <select class="fselect" name="anaesthesia_type">
                                            @foreach(['General Anaesthesia (GA)','Spinal / Regional','Epidural','Local + Sedation'] as $o)
                                                <option {{ $c->anaesthesia_type === $o ? 'selected' : '' }}>{{ $o }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group"><label class="form-label">Special Instructions for OT</label><textarea class="ftextarea" name="special_instructions" rows="2">{{ $c->special_instructions }}</textarea></div>
                            </div>
                        </div>
                    </form>
                </div>

                @endif

                {{-- TAB 1: INVESTIGATIONS --}}
                @if($vResult)
                <div class="tab-panel" data-panel="1">
                    <div class="card">
                        <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltteal)">🧪</div> Pre-Op Investigations</h3></div>
                        <div class="card-body">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                                @foreach($cnInvest as $iv)
                                    <a href="{{ $canChecklist ? route('vendor.preop.check.toggle', $iv->id) : 'javascript:void(0)' }}" class="report-row" style="text-decoration:none;{{ $canChecklist ? '' : 'cursor:default' }}">
                                        <div class="report-icon">{{ $iv->status === 'done' ? '✅' : '🧪' }}</div>
                                        <div><div class="report-name">{{ $iv->label }}</div><div class="report-meta">{{ $iv->meta }}</div></div>
                                        <span class="rbadge {{ $iv->status === 'done' ? 'ok' : 'pend' }}">{{ $iv->status === 'done' ? '✓ Done' : 'Pending' }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                @endif

                {{-- TAB 2: REPORTS --}}
                @if($vResult)
                <div class="tab-panel" data-panel="2">
                    <div class="card">
                        <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">📁</div> Lab Results — Pre-Op Summary</h3></div>
                        <div class="card-body">
                            @if($c->results->count())
                                <div class="result-grid">
                                    @foreach($c->results as $r)
                                        <div class="result-card {{ $r->status }}"><div class="result-name">{{ $r->name }}</div><div class="result-val">{{ $r->value }}</div><div class="result-ref">{{ $r->ref_range }}</div></div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty">No results recorded yet.</div>
                            @endif
                            @if($canResult)
                            <form method="post" action="{{ route('vendor.preop.result.add', $c->id) }}" style="background:var(--bg4);border:1px dashed var(--border2);border-radius:8px;padding:10px;margin-top:8px">
                                @csrf
                                <div class="form-grid-3" style="margin-bottom:6px">
                                    <input class="finput" name="name" placeholder="Parameter (e.g. Haemoglobin)" required>
                                    <input class="finput" name="value" placeholder="Value (e.g. 12.4 g/dL)">
                                    <input class="finput" name="ref_range" placeholder="Ref range">
                                </div>
                                <div style="display:flex;gap:8px;justify-content:flex-end;align-items:center">
                                    <select class="fselect" name="status" style="max-width:160px"><option value="normal">Normal</option><option value="borderline">Borderline</option><option value="abnormal">Abnormal</option></select>
                                    <button class="btn btn-teal">+ Add Result</button>
                                </div>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>

                @endif

                {{-- TAB 3: MEDICINES --}}
                @if($vMed)
                <div class="tab-panel" data-panel="3">
                    <div class="card">
                        <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltgreen)">💊</div> Pre-Op Medicine Orders</h3></div>
                        <div class="card-body">
                            @if($c->patient->allergies)
                                <div style="background:var(--ltred);border:1px solid var(--redA);border-radius:8px;padding:8px 12px;margin-bottom:12px;font-size:11px;color:var(--red);font-weight:600">🚨 ALLERGY: {{ $c->patient->allergies }} — verify before administering.</div>
                            @endif
                            @forelse($c->meds as $m)
                                <div class="med-row {{ $m->status }}">
                                    <div><div style="font-weight:600">{{ $m->name }}</div><div style="font-size:10px;color:var(--muted)">{{ $m->detail }}</div></div>
                                    <div style="font-family:var(--mono);font-size:11px">{{ $m->dose }}</div>
                                    <div style="font-size:11px">{{ $m->route_time }}</div>
                                    <div style="font-size:11px;color:var(--muted)">{{ $m->purpose }}</div>
                                    <div style="display:flex;gap:4px;align-items:center">
                                        <span class="med-badge {{ $m->status }}">{{ ['given'=>'✓ Given','held'=>'⏳ Held','stop'=>'⛔ Stop','due'=>'Due'][$m->status] ?? $m->status }}</span>
                                        @if($canMedStatus)@foreach(['given'=>'✓','held'=>'⏳','stop'=>'⛔'] as $st => $ic)
                                            <form method="post" action="{{ route('vendor.preop.med.status', $m->id) }}" class="mb-0">@csrf<input type="hidden" name="status" value="{{ $st }}"><button class="btn btn-outline btn-xs" title="{{ $st }}">{{ $ic }}</button></form>
                                        @endforeach @endif
                                    </div>
                                </div>
                            @empty
                                <div class="empty">No medicine orders yet.</div>
                            @endforelse
                            @if($canMedAdd)
                            <form method="post" action="{{ route('vendor.preop.med.add', $c->id) }}" style="margin-top:10px;padding:10px;background:var(--bg4);border-radius:8px;border:1px dashed var(--border2)">
                                @csrf
                                <div class="form-grid-3">
                                    <input class="finput" name="name" placeholder="Medicine + strength *" required>
                                    <input class="finput" name="dose" placeholder="Dose (e.g. 1g)">
                                    <input class="finput" name="route_time" placeholder="Route / time (IV · 12:30)">
                                </div>
                                <div style="display:flex;gap:8px;justify-content:flex-end;align-items:center">
                                    <input class="finput" name="purpose" placeholder="Purpose" style="max-width:240px">
                                    <button class="btn btn-teal">+ Add Medicine</button>
                                </div>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>

                @endif

                {{-- TAB 4: CONSENT --}}
                @if($vConsent)
                <div class="tab-panel" data-panel="4">
                    <div class="card">
                        <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltgreen)">✍</div> Consent Forms</h3></div>
                        <div class="card-body">
                            @foreach($c->consents as $cons)
                                <div class="consent-row {{ $cons->status === 'signed' ? 'signed' : 'pending' }}">
                                    <div style="font-size:18px">{{ $cons->status === 'signed' ? '📄' : '📝' }}</div>
                                    <div style="flex:1">
                                        <div style="font-size:12px;font-weight:600">{{ $cons->name }} @if($cons->is_optional)<span style="font-size:9px;color:var(--muted)">(optional)</span>@endif</div>
                                        <div style="font-size:10px;color:var(--muted)">{{ $cons->status === 'signed' ? 'Signed by '.$cons->signed_by.' · '.optional($cons->signed_at)->format('d M, h:i A') : 'Awaiting signature' }}</div>
                                    </div>
                                    @if($cons->status === 'signed')
                                        <span class="consent-status signed">✓ Signed</span>
                                    @else
                                        <span class="consent-status pending">Pending</span>
                                        @if($canConsentSign)<a href="{{ route('vendor.preop.consent.sign', $cons->id) }}" class="btn btn-teal btn-xs">Get Sign</a>@endif
                                    @endif
                                </div>
                            @endforeach
                            @if($canConsentAdd)
                            <form method="post" action="{{ route('vendor.preop.consent.add', $c->id) }}" style="display:flex;gap:8px;margin-top:8px">
                                @csrf
                                <input class="finput" name="name" placeholder="Add consent form name" required>
                                <button class="btn btn-outline">+ Add</button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>

                @endif

                {{-- TAB 5: ANAESTHESIA --}}
                @if($vAnaesthesia)
                <div class="tab-panel" data-panel="5">
                    <form method="post" action="{{ route('vendor.preop.anaesthesia', $c->id) }}">
                        @csrf
                        <div class="card">
                            <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltcyan)">😷</div> Anaesthesia Pre-Op Evaluation</h3></div>
                            <div class="card-body">
                                <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px">ASA Physical Status</div>
                                <div class="asa-grid">
                                    @foreach(['Healthy, no systemic disease','Mild systemic disease','Severe systemic disease','Severe disease, threat to life','Moribund'] as $idx => $lbl)
                                        @php $n = $idx + 1; @endphp
                                        <label class="asa-card {{ $c->asa_class == $n ? 'sel' : '' }}">
                                            <input type="radio" name="asa_class" value="{{ $n }}" {{ $c->asa_class == $n ? 'checked' : '' }} style="display:none" onchange="this.closest('.asa-grid').querySelectorAll('.asa-card').forEach(x=>x.classList.remove('sel'));this.closest('.asa-card').classList.add('sel')">
                                            <div class="asa-num">{{ ['I','II','III','IV','V'][$idx] }}</div><div class="asa-label">{{ $lbl }}</div>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="form-grid">
                                    <div class="form-group"><label class="form-label">Planned Anaesthesia</label>
                                        <select class="fselect" name="anaesthesia_type">@foreach(['General Anaesthesia (GA)','Spinal Anaesthesia','Epidural','Local + Sedation'] as $o)<option {{ $c->anaesthesia_type === $o ? 'selected' : '' }}>{{ $o }}</option>@endforeach</select>
                                    </div>
                                    <div class="form-group"><label class="form-label">Intubation Plan</label>
                                        <select class="fselect" name="intubation_plan">@foreach(['RSI — Rapid Sequence Induction','Standard Induction','Awake Fibreoptic'] as $o)<option {{ $c->intubation_plan === $o ? 'selected' : '' }}>{{ $o }}</option>@endforeach</select>
                                    </div>
                                    <div class="form-group"><label class="form-label">Airway Assessment</label>
                                        <select class="fselect" name="airway">@foreach(['Mallampati Class I — Easy','Mallampati Class II','Mallampati Class III — Difficult'] as $o)<option {{ $c->airway === $o ? 'selected' : '' }}>{{ $o }}</option>@endforeach</select>
                                    </div>
                                    <div class="form-group"><label class="form-label">NBM Status</label><input class="finput" name="nbm_since" value="{{ $c->nbm_since }}" placeholder="NBM since 07:00"></div>
                                </div>
                                <div class="form-group" style="margin-bottom:10px"><label class="form-label">Anaesthesia Notes</label><textarea class="ftextarea" name="anaesthesia_notes" rows="3">{{ $c->anaesthesia_notes }}</textarea></div>
                                @if($canAnaesthesia)
                                <div style="display:flex;gap:8px;justify-content:flex-end">
                                    <button class="btn btn-outline" name="clear" value="0">Save</button>
                                    <button class="btn btn-teal" name="clear" value="1">✓ Save &amp; Give Clearance</button>
                                </div>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>

                @endif

                {{-- TAB 6: BLOOD BANK --}}
                @if($vBlood)
                <div class="tab-panel" data-panel="6">
                    <div class="card">
                        <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltred)">🩸</div> Blood Bank</h3></div>
                        <div class="card-body">
                            <div class="form-grid">
                                <div style="background:var(--bg3);border:1px solid var(--greenA);border-radius:10px;padding:14px"><div style="font-size:10px;color:var(--muted);text-transform:uppercase">Patient Blood Group</div><div style="font-family:var(--mono);font-size:28px;color:var(--teal)">{{ $c->patient->blood_group ?: '—' }}</div></div>
                                <div style="background:var(--bg3);border:1px solid var(--greenA);border-radius:10px;padding:14px"><div style="font-size:10px;color:var(--muted);text-transform:uppercase">Units Reserved</div><div style="font-family:var(--mono);font-size:28px;color:var(--green)">{{ $c->bloodUnits->count() }}</div></div>
                            </div>
                            <div style="background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:12px">
                                <table class="bu">
                                    <thead><tr><th>Unit ID</th><th>Type</th><th>Group</th><th>Expiry</th><th>Status</th></tr></thead>
                                    <tbody>
                                        @forelse($c->bloodUnits as $bu)
                                            <tr><td style="font-family:var(--mono)">{{ $bu->unit_id }}</td><td>{{ $bu->component }}</td><td style="color:var(--teal)">{{ $bu->blood_group }}</td><td>{{ optional($bu->expiry_date)->format('d M Y') }}</td><td><span style="background:var(--ltgreen);color:var(--green);font-size:10px;font-weight:700;padding:2px 8px;border-radius:4px">{{ ucfirst($bu->status) }}</span></td></tr>
                                        @empty
                                            <tr><td colspan="5" style="color:var(--muted);text-align:center;padding:14px">No units reserved.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if($canBlood)
                            <form method="post" action="{{ route('vendor.preop.blood.add', $c->id) }}" style="margin-top:8px;display:grid;grid-template-columns:1fr 100px 90px 1fr 90px;gap:8px;align-items:center">
                                @csrf
                                <input class="finput" name="unit_id" placeholder="Unit ID *" required>
                                <input class="finput" name="component" placeholder="PRBC">
                                <input class="finput" name="blood_group" placeholder="{{ $c->patient->blood_group ?: 'O+' }}">
                                <input class="finput" type="date" name="expiry_date">
                                <button class="btn btn-teal">Reserve</button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>

                @endif

                {{-- TAB 7: PRE-OP PREP --}}
                @if($vChecklist)
                <div class="tab-panel" data-panel="7">
                    <div class="card">
                        <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">🛁</div> Physical Pre-Op Preparation</h3></div>
                        <div class="card-body">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                                <div style="background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:13px">
                                    <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px">Patient Prep Checklist</div>
                                    @foreach($cnPrep as $chk)
                                        <a href="{{ $canChecklist ? route('vendor.preop.check.toggle', $chk->id) : 'javascript:void(0)' }}" @if(!$canChecklist)style="cursor:default"@endif class="cs-item {{ $chk->status === 'done' ? 'done' : '' }}">
                                            <div class="cs-chk {{ $chk->status === 'done' ? 'done' : '' }}">{{ $chk->status === 'done' ? '✓' : '' }}</div><div class="cs-text">{{ $chk->label }}</div>
                                        </a>
                                    @endforeach
                                </div>
                                <div>
                                    @if(hmis_vitals_enabled())
                                    <div style="background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:13px">
                                        <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px">Latest Vitals @if($latestVital)<span style="color:var(--muted)">· {{ optional($latestVital->recorded_at)->format('H:i') }}</span>@endif</div>
                                        @if($latestVital)
                                            <div class="result-grid" style="grid-template-columns:1fr 1fr">
                                                <div class="result-card"><div class="result-name">BP</div><div class="result-val">{{ $latestVital->bp_systolic }}/{{ $latestVital->bp_diastolic }}</div></div>
                                                <div class="result-card"><div class="result-name">HR</div><div class="result-val">{{ $latestVital->hr ?: '—' }}</div></div>
                                                <div class="result-card"><div class="result-name">SpO₂</div><div class="result-val">{{ $latestVital->spo2 ? $latestVital->spo2.'%' : '—' }}</div></div>
                                                <div class="result-card"><div class="result-name">Temp</div><div class="result-val">{{ $latestVital->temp ?: '—' }}</div></div>
                                            </div>
                                        @else
                                            <div style="font-size:11px;color:var(--muted)">No vitals recorded. Record them in the Nursing Station.</div>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @endif

                {{-- TAB 8: CLEARANCES --}}
                @if($vClearance)
                <div class="tab-panel" data-panel="8">
                    <div class="card">
                        <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltgreen)">✅</div> Pre-Op Clearances</h3></div>
                        <div class="card-body">
                            <div style="font-size:11px;color:var(--muted);margin-bottom:12px">All clearances must be obtained before the patient is shifted to OT.</div>
                            <div class="clearance-grid">
                                @foreach($c->clearances as $clr)
                                    <div class="clr-card {{ $clr->status }}">
                                        <div class="clr-title">{{ $clr->status === 'cleared' ? '✓' : ($clr->status === 'blocked' ? '⛔' : '⏳') }} {{ $clr->type_label }}</div>
                                        <div class="clr-by">{{ $clr->by_label ?: '—' }}</div>
                                        <div class="clr-status">{{ $clr->status === 'cleared' ? ($clr->note ?: 'Cleared') : ucfirst($clr->status) }}</div>
                                        @if($clr->status !== 'cleared')
                                            @if($canClearance)
                                            <div style="display:flex;gap:6px;margin-top:8px">
                                                <form method="post" action="{{ route('vendor.preop.clearance.set', $clr->id) }}" class="mb-0">@csrf<input type="hidden" name="status" value="cleared"><button class="btn btn-green btn-xs">Clear</button></form>
                                                <form method="post" action="{{ route('vendor.preop.clearance.set', $clr->id) }}" class="mb-0">@csrf<input type="hidden" name="status" value="blocked"><button class="btn btn-outline btn-xs">Block</button></form>
                                            </div>
                                            @endif
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                @endif

                {{-- TAB 9: OT HANDOVER --}}
                @if($vHandover)
                <div class="tab-panel" data-panel="9">
                    <div class="card">
                        <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltpurple)">🚪</div> OT Handover — Ward to OT</h3></div>
                        <div class="card-body">
                            <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px">WHO Surgical Safety Checklist</div>
                            @foreach($cnHandover as $chk)
                                <a href="{{ $canChecklist ? route('vendor.preop.check.toggle', $chk->id) : 'javascript:void(0)' }}" @if(!$canChecklist)style="cursor:default"@endif class="cs-item {{ $chk->status === 'done' ? 'done' : '' }}">
                                    <div class="cs-chk {{ $chk->status === 'done' ? 'done' : '' }}">{{ $chk->status === 'done' ? '✓' : '' }}</div><div class="cs-text">{{ $chk->label }}</div>
                                </a>
                            @endforeach
                            <form method="post" action="{{ route('vendor.preop.handover', $c->id) }}" style="margin-top:12px">
                                @csrf
                                <div class="form-grid">
                                    <div class="form-group"><label class="form-label">Shifted by (Ward Nurse)</label><input class="finput" name="handover_from" value="{{ $c->handover_from }}"></div>
                                    <div class="form-group"><label class="form-label">Received by (OT Team)</label><input class="finput" name="handover_to" value="{{ $c->handover_to }}" placeholder="OT nurse / anaesthetist"></div>
                                </div>
                                <div class="form-group" style="margin-bottom:10px"><label class="form-label">Handover Notes</label><textarea class="ftextarea" name="handover_notes" rows="3">{{ $c->handover_notes }}</textarea></div>
                                <div style="display:flex;gap:8px;justify-content:flex-end">
                                    @if($c->shifted_at)
                                        <span class="consent-status signed">✓ Shifted to OT · {{ $c->shifted_at->format('d M, H:i') }}</span>
                                    @elseif($canHandover)
                                        <button class="btn btn-outline" name="shift" value="0">Save Draft</button>
                                        <button class="btn btn-purple" name="shift" value="1" onclick="return confirm('Complete handover and shift patient to OT?')">✓ Complete &amp; Shift to OT</button>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- BOTTOM BAR --}}
        <div class="bottom-bar">
            <div style="font-size:12px;color:var(--muted)"><strong style="color:var(--text)">{{ $c->patient->name ?? '' }}</strong> · {{ \Illuminate\Support\Str::limit($c->procedure, 26) }} · {{ $c->ot_room }} · {{ optional($c->scheduled_at)->format('d M H:i') }}
                <span style="margin-left:10px;font-family:var(--mono);color:var(--amber);font-weight:700" id="preopCountdown" data-ot="{{ optional($c->scheduled_at)->toIso8601String() }}"></span>
            </div>
            <div style="display:flex;gap:8px"><button class="btn btn-outline" onclick="preopTab(8)">✅ Clearances</button><button class="btn btn-outline" onclick="preopTab(9)">🚪 OT Handover</button></div>
        </div>
    @endif

    {{-- SCHEDULE OVERLAY --}}
    <div id="schedOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1090;align-items:center;justify-content:center" onclick="if(event.target===this)this.style.display='none'">
        <div class="card" style="max-width:620px;width:92%;margin:0">
            <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltpurple)">🔪</div> Schedule Surgery</h3><button class="btn btn-outline btn-xs" onclick="document.getElementById('schedOverlay').style.display='none'">✕</button></div>
            <div class="card-body">@include('hmis::vendor.preop._schedule_form')</div>
        </div>
    </div>
</div></div>
@endsection

@push('script_2')
<script>
(function(){
    var tabs=document.querySelectorAll('.preopx .tab'), panels=document.querySelectorAll('.preopx .tab-panel');
    window.preopTab=function(i){
        tabs.forEach(function(t){t.classList.toggle('active',t.dataset.tab==i);});
        panels.forEach(function(p){p.classList.toggle('active',p.dataset.panel==i);});
        try{localStorage.setItem('preopTab',i);}catch(e){}
    };
    tabs.forEach(function(t){t.addEventListener('click',function(){preopTab(this.dataset.tab);});});
    var s=null;try{s=localStorage.getItem('preopTab');}catch(e){}
    if(s!==null&&document.querySelector('.preopx .tab[data-tab="'+s+'"]')){preopTab(s);}
    else if(!document.querySelector('.preopx .tab.active')&&tabs.length){preopTab(tabs[0].dataset.tab);}

    function cd(){
        var el=document.getElementById('preopCountdown');if(!el||!el.dataset.ot)return;
        var ot=new Date(el.dataset.ot),now=new Date(),d=ot-now;
        if(d<0){el.textContent='🔴 OT TIME';return;}
        var h=Math.floor(d/3600000),m=Math.floor((d%3600000)/60000);
        el.textContent='⏳ T-'+h+':'+String(m).padStart(2,'0')+' to OT';
    }
    cd();setInterval(cd,30000);
})();
</script>
@endpush
