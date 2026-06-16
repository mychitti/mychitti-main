@php
    $rn = Route::currentRouteName();
    $S = $labStats ?? [];
    $alerts = $criticalAlerts ?? collect();
    // Each tab: [route, label, icon, badge, badgeClass, feature, action]
    $tabs = [
        ['vendor.lab.worklist', 'Test Worklist', '📋', $S['testsPending'] ?? 0, 'r', 'lab_worklist', 'view'],
        ['vendor.lab.result-entry', 'Result Entry', '🔬', 0, '', 'lab_result', 'view'],
        ['vendor.lab.reports', 'Lab Reports', '📄', $S['completed'] ?? 0, 'g', 'lab_report', 'view'],
        ['vendor.lab.critical', 'Critical Values', '🚨', $S['criticalOpen'] ?? 0, 'r', 'lab_critical', 'view'],
        ['vendor.lab.order', 'Order New Test', '➕', 0, '', 'lab_order', 'view'],
        ['vendor.lab.reagents', 'Reagents', '🧪', 0, '', 'lab_reagent', 'view'],
        ['vendor.lab.history', 'Test History', '📊', 0, '', 'lab_history', 'view'],
        ['vendor.lab.billing', 'Lab Billing', '💰', 0, '', 'lab_billing', 'view'],
        ['vendor.lab.catalog', 'Test Catalog', '⚙', 0, '', 'lab_catalog', 'view'],
    ];
    $isOwner = auth('vendor')->check();
    $fmt = fn($n) => \App\CentralLogics\Helpers::format_currency($n);
@endphp

@push('css_or_js')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
.content.container-fluid{padding:0!important;margin:0!important;max-width:100%!important;width:100%!important}
.labx{--navy:#0A2463;--blue:#1565C0;--blue2:#1976D2;--ltblue:#E3F2FD;--ltblue2:#EFF6FF;--green:#1B5E20;--ltgreen:#E8F5E9;--greenA:#2E7D32;--greenB:#43A047;--red:#B71C1C;--ltred:#FFEBEE;--redA:#C62828;--redB:#E53E3E;--amber:#B45309;--ltamber:#FFF8E1;--amberA:#F57C00;--purple:#4527A0;--ltpurple:#EDE7F6;--purpleA:#6D28D9;--teal:#004D40;--ltteal:#E0F2F1;--tealA:#00897B;--pink:#880E4F;--ltpink:#FCE4EC;--pinkA:#C2185B;--text:#0D1117;--muted:#4B5563;--light:#9CA3AF;--border:#E5E7EB;--bg:#F3F4F6;--white:#fff;--ff:'DM Sans',sans-serif;--ffm:'DM Mono',monospace}
.labx{font-family:var(--ff);font-size:13px;color:var(--text);background:var(--bg)}
.labx .num{font-family:var(--ffm);font-weight:700}
.labx .kpi-strip{background:var(--white);border-bottom:1px solid var(--border);display:grid;grid-template-columns:repeat(7,1fr)}
.labx .kpi-item{padding:11px 14px;text-align:center;border-right:1px solid var(--border)}
.labx .kpi-item:last-child{border-right:none}
.labx .kpi-val{font-size:21px;font-weight:800;line-height:1;font-family:var(--ffm)}
.labx .kpi-lbl{font-size:10px;color:var(--light);margin-top:3px;text-transform:uppercase;letter-spacing:.4px}
.labx .kpi-sub{font-size:10px;font-weight:600;margin-top:2px}
.labx .critical-banner{background:linear-gradient(90deg,#B71C1C,#C62828);padding:10px 16px;display:flex;align-items:center;justify-content:space-between}
.labx .cb-text{color:#fff;font-size:12px;font-weight:700}
.labx .cb-sub{color:rgba(255,255,255,.78);font-size:11px;margin-top:2px}
.labx .tabs{background:var(--white);border-bottom:1px solid var(--border);padding:0 16px;display:flex;overflow-x:auto}
.labx .tabs::-webkit-scrollbar{display:none}
.labx .tab{padding:11px 14px;font-size:12px;font-weight:600;color:var(--light);cursor:pointer;border-bottom:2.5px solid transparent;display:flex;align-items:center;gap:6px;white-space:nowrap;text-decoration:none!important}
.labx .tab:hover{color:var(--blue)}
.labx .tab.active{color:var(--blue);border-bottom-color:var(--blue)}
.labx .tbadge{font-size:9px;font-weight:700;padding:1px 5px;border-radius:100px;background:var(--redB);color:#fff}
.labx .tbadge.g{background:var(--greenA)}.labx .tbadge.b{background:var(--blue)}
.labx .lab-body{padding:16px 20px}
.labx .layout-2col{display:grid;grid-template-columns:1fr 300px;gap:14px;align-items:start}
.labx .layout-3col{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
.labx .lcard{background:var(--white);border-radius:12px;border:1px solid var(--border);overflow:hidden;margin-bottom:14px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.labx .lcard:last-child{margin-bottom:0}
.labx .card-hd{display:flex;align-items:center;justify-content:space-between;padding:11px 16px;border-bottom:1px solid var(--border)}
.labx .card-hd h3{font-size:12px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px}
.labx .hd-icon{width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:12px}
.labx .card-actions{display:flex;gap:7px;align-items:center}
.labx .btn{padding:7px 15px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;border:none;font-family:var(--ff);white-space:nowrap;text-decoration:none!important;display:inline-flex;align-items:center;gap:5px;line-height:1.2}
.labx .btn-primary{background:var(--blue);color:#fff}.labx .btn-primary:hover{background:var(--navy);color:#fff}
.labx .btn-green{background:var(--greenA);color:#fff}.labx .btn-amber{background:var(--amberA);color:#fff}
.labx .btn-red{background:var(--redA);color:#fff}.labx .btn-teal{background:var(--tealA);color:#fff}.labx .btn-purple{background:var(--purpleA);color:#fff}
.labx .btn-outline{background:none;border:1.5px solid var(--border);color:var(--muted)}.labx .btn-outline:hover{border-color:var(--blue);color:var(--blue)}
.labx .btn-ghost{background:var(--ltblue2);color:var(--blue)}
.labx .btn-sm{padding:5px 11px;font-size:11px;border-radius:7px}
.labx .btn-xs{padding:3px 8px;font-size:10px;border-radius:6px}
.labx .fl{font-size:11px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block}
.labx .fi,.labx .fs{padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;color:var(--text);outline:none;background:var(--white);width:100%;font-family:var(--ff)}
.labx .fi:focus,.labx .fs:focus{border-color:var(--blue)}
.labx .fg{display:flex;flex-direction:column;gap:4px}
.labx .frow2{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px}
.labx .frow3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:10px}
.labx .frow4{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:10px}
.labx .pill{font-size:10px;font-weight:700;padding:2px 9px;border-radius:100px;white-space:nowrap;display:inline-block}
.labx .pill-blue{background:var(--ltblue);color:var(--blue)}.labx .pill-green{background:var(--ltgreen);color:var(--greenA)}
.labx .pill-red{background:var(--ltred);color:var(--redA)}.labx .pill-amber{background:var(--ltamber);color:var(--amber)}
.labx .pill-purple{background:var(--ltpurple);color:var(--purple)}.labx .pill-navy{background:var(--navy);color:#fff}
.labx .pill-teal{background:var(--ltteal);color:var(--teal)}.labx .pill-pink{background:var(--ltpink);color:var(--pink)}
.labx .search-bar{display:flex;align-items:center;gap:8px;padding:10px 16px;border-bottom:1px solid var(--border);background:#FAFAFA}
.labx .search-wrap{position:relative;flex:1}
.labx .si{width:100%;padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;outline:none;font-family:var(--ff);background:var(--white)}
.labx .fsel{padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;outline:none;background:var(--white);cursor:pointer;font-family:var(--ff)}
.labx .tbl-hd{font-size:10px;font-weight:700;color:var(--light);text-transform:uppercase;letter-spacing:.6px;background:#F9FAFB;border-bottom:1px solid var(--border);padding:8px 16px}
.labx .tbl-row{padding:11px 16px;border-bottom:1px solid #F3F4F6;align-items:center}
.labx .tbl-row:last-child{border-bottom:none}
.labx .tbl-row:hover{background:#F9FAFB}
.labx .tbl-row.urgent{background:#FFF5F5;border-left:3px solid var(--redB)}
.labx .tbl-row.active-row{background:linear-gradient(90deg,#EFF6FF,#FAFAFA);border-left:3px solid var(--blue)}
.labx .tbl-row.done{opacity:.7}
.labx .result-section-title{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;padding:8px 16px;background:#F9FAFB;border-bottom:1px solid var(--border);border-top:1px solid var(--border)}
.labx .result-hd,.labx .result-row{display:grid;grid-template-columns:2fr 1fr 1fr 1.4fr 110px;gap:8px;align-items:center}
.labx .result-hd{padding:7px 16px;font-size:10px;font-weight:700;color:var(--light);text-transform:uppercase;letter-spacing:.5px;background:#F9FAFB;border-bottom:1px solid var(--border)}
.labx .result-row{padding:9px 16px;border-bottom:1px solid #F3F4F6;font-size:12px}
.labx .result-input{padding:5px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:12px;width:100%;outline:none;font-family:var(--ffm);text-align:center;font-weight:700}
.labx .result-input.high{border-color:var(--redB);background:var(--ltred);color:var(--redA)}
.labx .result-input.low{border-color:var(--amberA);background:var(--ltamber);color:var(--amber)}
.labx .result-input.normal{border-color:var(--greenB);background:var(--ltgreen);color:var(--greenA)}
.labx .ref-range{font-size:10px;color:var(--light);font-family:var(--ffm)}
.labx .flag-high{color:var(--redA);font-weight:700;font-size:11px}
.labx .flag-low{color:var(--amber);font-weight:700;font-size:11px}
.labx .flag-norm{color:var(--greenA);font-weight:700;font-size:11px}
.labx .stat-row{display:flex;justify-content:space-between;align-items:center;padding:7px 16px;border-bottom:1px solid #F3F4F6}
.labx .stat-row:last-child{border-bottom:none}
.labx .stat-l{font-size:11px;color:var(--light)}.labx .stat-v{font-size:12px;font-weight:700}
.labx .stat-v.g{color:var(--greenA)}.labx .stat-v.r{color:var(--redA)}.labx .stat-v.a{color:var(--amber)}.labx .stat-v.b{color:var(--blue)}
.labx .alert-row{display:flex;align-items:flex-start;gap:10px;padding:10px 14px;border-bottom:1px solid #F3F4F6}
.labx .alert-row:last-child{border-bottom:none}
.labx .alert-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:4px}
.labx .alert-title{font-size:12px;font-weight:700}.labx .alert-sub{font-size:10px;color:var(--muted);margin-top:2px}
.labx .empty{text-align:center;color:var(--light);padding:40px 16px;font-size:13px}
.labx .test-opt{display:flex;align-items:center;gap:8px;padding:8px 11px;border:1.5px solid var(--border);border-radius:8px;cursor:pointer}
.labx .test-opt.sel{border-color:var(--blue);background:var(--ltblue2)}
@media(max-width:1100px){.labx .layout-2col{grid-template-columns:1fr}.labx .kpi-strip{grid-template-columns:repeat(4,1fr)}}
</style>
@endpush

<div class="kpi-strip">
    <div class="kpi-item"><div class="kpi-val" style="color:var(--blue)">{{ $S['testsPending'] ?? 0 }}</div><div class="kpi-lbl">Tests Pending</div><div class="kpi-sub" style="color:var(--blue)">Today</div></div>
    <div class="kpi-item"><div class="kpi-val" style="color:var(--amberA)">{{ $S['inProgress'] ?? 0 }}</div><div class="kpi-lbl">In Progress</div><div class="kpi-sub" style="color:var(--amberA)">Processing now</div></div>
    <div class="kpi-item"><div class="kpi-val" style="color:var(--greenA)">{{ $S['completed'] ?? 0 }}</div><div class="kpi-lbl">Completed</div><div class="kpi-sub" style="color:var(--greenA)">Reports ready</div></div>
    <div class="kpi-item"><div class="kpi-val" style="color:var(--redA)">{{ $S['criticalOpen'] ?? 0 }}</div><div class="kpi-lbl">Critical Values</div><div class="kpi-sub" style="color:var(--redA)">Alert doctor</div></div>
    <div class="kpi-item"><div class="kpi-val" style="color:var(--purpleA)">{{ $S['totalToday'] ?? 0 }}</div><div class="kpi-lbl">Total Today</div><div class="kpi-sub" style="color:var(--muted)">All tests</div></div>
    <div class="kpi-item"><div class="kpi-val">{{ $S['patientsToday'] ?? 0 }}</div><div class="kpi-lbl">Patients</div><div class="kpi-sub" style="color:var(--muted)">Active today</div></div>
    <div class="kpi-item"><div class="kpi-val">{{ $fmt($S['revenueToday'] ?? 0) }}</div><div class="kpi-lbl">Revenue</div><div class="kpi-sub" style="color:var(--greenA)">Today</div></div>
</div>

@if ($alerts->count())
    <div class="critical-banner">
        <div>
            <div class="cb-text">🚨 CRITICAL VALUES ALERT — {{ $alerts->count() }} result(s) require immediate doctor notification</div>
            <div class="cb-sub">{{ $alerts->map(fn($a) => ($a->order->patient->name ?? 'Patient') . ': ' . $a->parameter_name . ' = ' . $a->result_value . ($a->unit ? ' ' . $a->unit : ''))->implode(' · ') }}</div>
        </div>
        @if ($isOwner || hasPermission('lab_critical', 'view'))<a href="{{ route('vendor.lab.critical') }}" class="btn btn-outline btn-sm" style="border-color:rgba(255,255,255,.45);color:#fff">View &amp; Notify Doctors</a>@endif
    </div>
@endif

<div class="tabs">
    @foreach ($tabs as [$route, $label, $icon, $count, $cls, $feat, $act])
        @if ($isOwner || hasPermission($feat, $act))
            <a href="{{ route($route) }}" class="tab {{ $rn === $route ? 'active' : '' }}">
                {{ $icon }} {{ $label }}
                @if ($count > 0)<span class="tbadge {{ $cls }}">{{ $count }}</span>@endif
            </a>
        @endif
    @endforeach
</div>
