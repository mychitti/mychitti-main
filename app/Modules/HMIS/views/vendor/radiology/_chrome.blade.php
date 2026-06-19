@php
    $rn = Route::currentRouteName();
    $S = $radStats ?? [];
    $alerts = $urgentAlerts ?? collect();
    // Each tab: [route, label, icon, badge, badgeClass, feature, action]
    $tabs = [
        ['vendor.radiology.worklist', 'Study Worklist', '📋', $S['pending'] ?? 0, 'r', 'radiology_study', 'view'],
        ['vendor.radiology.viewer', 'DICOM Viewer', '🖥', 0, '', 'radiology_viewer', 'view'],
        ['vendor.radiology.report', 'Report Writing', '📝', 0, '', 'radiology_report', 'add'],
        ['vendor.radiology.reports', 'Reports', '📄', $S['reports_ready'] ?? 0, 'g', 'radiology_report', 'view'],
        ['vendor.radiology.urgent', 'Urgent Findings', '🚨', $S['urgent'] ?? 0, 'r', 'radiology_urgent', 'view'],
        ['vendor.radiology.schedule', 'Schedule', '📅', 0, '', 'radiology_schedule', 'view'],
        ['vendor.radiology.equipment', 'Equipment', '⚙', 0, '', 'radiology_equipment', 'view'],
        ['vendor.radiology.billing', 'Billing', '💰', 0, '', 'radiology_billing', 'view'],
        ['vendor.radiology.catalog', 'Scan Catalog', '🗂', 0, '', 'radiology_catalog', 'view'],
    ];
    $isOwner = auth('vendor')->check();
    $fmt = fn($n) => \App\CentralLogics\Helpers::format_currency($n);
@endphp

@push('css_or_js')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
.content.container-fluid{padding:0!important;margin:0!important;max-width:100%!important;width:100%!important}
.radx{--navy:#0A2463;--blue:#1565C0;--ltblue:#E3F2FD;--ltblue2:#EFF6FF;--green:#1B5E20;--ltgreen:#E8F5E9;--greenA:#2E7D32;--red:#B71C1C;--ltred:#FFEBEE;--redA:#C62828;--redB:#E53E3E;--amber:#B45309;--ltamber:#FFF8E1;--amberA:#F57C00;--purple:#4527A0;--ltpurple:#EDE7F6;--purpleA:#6D28D9;--teal:#004D40;--ltteal:#E0F2F1;--tealA:#00897B;--dark:#0D1B2A;--dark2:#1A2B3C;--dark3:#243447;--text:#0D1117;--muted:#4B5563;--light:#9CA3AF;--border:#E5E7EB;--bg:#F3F4F6;--white:#fff;--ff:'DM Sans',sans-serif;--ffm:'DM Mono',monospace}
.radx{font-family:var(--ff);font-size:13px;color:var(--text);background:var(--bg)}
.radx .kpi-strip{background:var(--white);border-bottom:1px solid var(--border);display:grid;grid-template-columns:repeat(7,1fr)}
.radx .kpi-item{padding:11px 14px;text-align:center;border-right:1px solid var(--border)}.radx .kpi-item:last-child{border-right:none}
.radx .kpi-val{font-size:21px;font-weight:800;line-height:1;font-family:var(--ffm)}
.radx .kpi-lbl{font-size:10px;color:var(--light);margin-top:3px;text-transform:uppercase;letter-spacing:.4px}
.radx .kpi-sub{font-size:10px;font-weight:600;margin-top:2px}
.radx .tabs{background:var(--white);border-bottom:1px solid var(--border);padding:0 16px;display:flex;overflow-x:auto}
.radx .tabs::-webkit-scrollbar{display:none}
.radx .tab{padding:11px 14px;font-size:12px;font-weight:600;color:var(--light);cursor:pointer;border-bottom:2.5px solid transparent;display:flex;align-items:center;gap:6px;white-space:nowrap;text-decoration:none!important}
.radx .tab:hover{color:var(--blue)}.radx .tab.active{color:var(--blue);border-bottom-color:var(--blue)}
.radx .tbadge{font-size:9px;font-weight:700;padding:1px 5px;border-radius:100px;background:var(--redB);color:#fff}.radx .tbadge.g{background:var(--greenA)}
.radx .rad-body{padding:16px 20px}
.radx .layout-2col{display:grid;grid-template-columns:1fr 300px;gap:14px;align-items:start}
.radx .layout-3col{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
.radx .card{background:var(--white);border-radius:12px;border:1px solid var(--border);overflow:hidden;margin-bottom:14px;box-shadow:0 1px 3px rgba(0,0,0,.06)}.radx .card:last-child{margin-bottom:0}
.radx .card-hd{display:flex;align-items:center;justify-content:space-between;padding:11px 16px;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:8px}
.radx .card-hd h3{font-size:12px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px}
.radx .hd-icon{width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:12px}
.radx .card-actions{display:flex;gap:7px;align-items:center}
.radx .card-body{padding:13px 15px}
.radx .btn{padding:7px 15px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;border:none;font-family:var(--ff);white-space:nowrap;text-decoration:none!important;display:inline-flex;align-items:center;gap:5px;line-height:1.2}
.radx .btn-primary{background:var(--blue);color:#fff}.radx .btn-primary:hover{background:var(--navy);color:#fff}
.radx .btn-green{background:var(--greenA);color:#fff}.radx .btn-amber{background:var(--amberA);color:#fff}.radx .btn-red{background:var(--redA);color:#fff}.radx .btn-teal{background:var(--tealA);color:#fff}.radx .btn-purple{background:var(--purpleA);color:#fff}.radx .btn-dark{background:var(--dark);color:#fff}
.radx .btn-outline{background:none;border:1.5px solid var(--border);color:var(--muted)}.radx .btn-outline:hover{border-color:var(--blue);color:var(--blue)}
.radx .btn-ghost{background:var(--ltblue2);color:var(--blue)}
.radx .btn-sm{padding:5px 11px;font-size:11px;border-radius:7px}.radx .btn-xs{padding:3px 8px;font-size:10px;border-radius:6px}
.radx .fl{font-size:11px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block}
.radx .fi,.radx .fs,.radx textarea.fi{padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;color:var(--text);outline:none;background:var(--white);width:100%;font-family:var(--ff)}
.radx .fi:focus,.radx .fs:focus{border-color:var(--blue)}
.radx .fg{display:flex;flex-direction:column;gap:4px}
.radx .frow2{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px}.radx .frow3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:10px}
.radx .pill{font-size:10px;font-weight:700;padding:2px 9px;border-radius:100px;white-space:nowrap;display:inline-block}
.radx .pill-blue{background:var(--ltblue);color:var(--blue)}.radx .pill-green{background:var(--ltgreen);color:var(--greenA)}.radx .pill-red{background:var(--ltred);color:var(--redA)}.radx .pill-amber{background:var(--ltamber);color:var(--amber)}.radx .pill-purple{background:var(--ltpurple);color:var(--purple)}.radx .pill-navy{background:var(--navy);color:#fff}.radx .pill-teal{background:var(--ltteal);color:var(--teal)}
.radx .search-bar{display:flex;align-items:center;gap:8px;padding:10px 16px;border-bottom:1px solid var(--border);background:#FAFAFA}
.radx .search-wrap{position:relative;flex:1}
.radx .si{width:100%;padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;outline:none;font-family:var(--ff);background:var(--white)}
.radx .fsel{padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;outline:none;background:var(--white);cursor:pointer;font-family:var(--ff)}
.radx .tbl-hd{padding:8px 16px;font-size:10px;font-weight:700;color:var(--light);text-transform:uppercase;letter-spacing:.6px;background:#F9FAFB;border-bottom:1px solid var(--border);display:grid;gap:8px}
.radx .tbl-row{padding:11px 16px;border-bottom:1px solid #F3F4F6;display:grid;gap:8px;align-items:center}.radx .tbl-row:last-child{border-bottom:none}.radx .tbl-row:hover{background:#F9FAFB}
.radx .tbl-row.urgent{background:#FFF5F5;border-left:3px solid var(--redB)}.radx .tbl-row.active-row{background:linear-gradient(90deg,#EFF6FF,#FAFAFA);border-left:3px solid var(--blue)}.radx .tbl-row.done{opacity:.65}
.radx .stat-row{display:flex;justify-content:space-between;align-items:center;padding:7px 16px;border-bottom:1px solid #F3F4F6}.radx .stat-row:last-child{border-bottom:none}
.radx .stat-l{font-size:11px;color:var(--light)}.radx .stat-v{font-size:12px;font-weight:700}.radx .stat-v.g{color:var(--greenA)}.radx .stat-v.r{color:var(--redA)}.radx .stat-v.a{color:var(--amber)}.radx .stat-v.b{color:var(--blue)}
.radx .alert-row{display:flex;align-items:flex-start;gap:10px;padding:10px 14px;border-bottom:1px solid #F3F4F6}.radx .alert-row:last-child{border-bottom:none}
.radx .alert-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:4px}
.radx .alert-title{font-size:12px;font-weight:700}.radx .alert-sub{font-size:10px;color:var(--muted);margin-top:2px}
.radx .empty{text-align:center;color:var(--light);padding:40px 16px;font-size:13px}
.radx .finding-tag{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;padding:3px 9px;border-radius:100px;cursor:pointer;margin:2px;border:1.5px solid transparent;user-select:none}
.radx .finding-tag.normal{background:var(--ltgreen);color:var(--greenA);border-color:var(--greenA)}.radx .finding-tag.abnormal{background:var(--ltred);color:var(--redA);border-color:var(--redA)}.radx .finding-tag.suggest{background:var(--ltamber);color:var(--amber);border-color:var(--amberA)}
.radx .finding-tag.selected{box-shadow:0 0 0 2px var(--blue)}
.radx .equip-card{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:14px;display:flex;align-items:flex-start;gap:12px;margin-bottom:10px}
.radx .equip-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;background:var(--ltblue)}
.radx .equip-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.radx .dicom-viewer{background:var(--dark);border-radius:12px;overflow:hidden;margin-bottom:14px}
.radx .dicom-toolbar{background:var(--dark2);padding:8px 14px;display:flex;align-items:center;gap:8px;border-bottom:1px solid rgba(255,255,255,.08);flex-wrap:wrap}
.radx .dicom-tool-btn{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:6px;padding:5px 10px;font-size:11px;color:rgba(255,255,255,.8);cursor:pointer;font-weight:500}
.radx .dicom-tool-btn:hover,.radx .dicom-tool-btn.active{background:rgba(21,101,192,.5);border-color:var(--blue);color:#fff}
.radx .dicom-body{display:grid;grid-template-columns:1fr 220px;min-height:420px}
.radx .dicom-canvas{position:relative;background:var(--dark);min-height:420px;display:flex;align-items:center;justify-content:center}
.radx .dicom-sidebar{background:var(--dark2);border-left:1px solid rgba(255,255,255,.06);padding:12px;overflow-y:auto}
.radx .dicom-info-title{font-size:10px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px}
.radx .dicom-info-row{display:flex;justify-content:space-between;padding:3px 0;border-bottom:1px solid rgba(255,255,255,.04);font-size:10px}
.radx .dicom-info-row .di-label{color:rgba(255,255,255,.4)}.radx .dicom-info-row .di-val{color:rgba(255,255,255,.85);font-family:var(--ffm)}
.radx .slot{padding:10px 6px;border-radius:8px;text-align:center;border:1.5px solid transparent;font-size:12px;font-weight:600}
.radx .slot.available{background:var(--ltgreen);color:var(--greenA);border-color:var(--greenA)}.radx .slot.booked{background:var(--ltblue);color:var(--blue);border-color:var(--blue)}.radx .slot.urgent{background:var(--ltred);color:var(--redA);border-color:var(--redA)}
@media(max-width:1100px){.radx .layout-2col,.radx .layout-3col{grid-template-columns:1fr}.radx .kpi-strip{grid-template-columns:repeat(4,1fr)}.radx .dicom-body{grid-template-columns:1fr}}
</style>
@endpush

<div class="kpi-strip">
    <div class="kpi-item"><div class="kpi-val" style="color:var(--blue)">{{ $S['pending'] ?? 0 }}</div><div class="kpi-lbl">Pending Scans</div><div class="kpi-sub" style="color:var(--blue)">Today</div></div>
    <div class="kpi-item"><div class="kpi-val" style="color:var(--amberA)">{{ $S['in_progress'] ?? 0 }}</div><div class="kpi-lbl">In Progress</div><div class="kpi-sub" style="color:var(--amberA)">Scanning now</div></div>
    <div class="kpi-item"><div class="kpi-val" style="color:var(--greenA)">{{ $S['reports_ready'] ?? 0 }}</div><div class="kpi-lbl">Reports Ready</div><div class="kpi-sub" style="color:var(--greenA)">Sent to doctors</div></div>
    <div class="kpi-item"><div class="kpi-val" style="color:var(--redA)">{{ $S['urgent'] ?? 0 }}</div><div class="kpi-lbl">Urgent Findings</div><div class="kpi-sub" style="color:var(--redA)">Alert required</div></div>
    <div class="kpi-item"><div class="kpi-val">{{ $S['total_today'] ?? 0 }}</div><div class="kpi-lbl">Total Today</div><div class="kpi-sub" style="color:var(--muted)">All modalities</div></div>
    <div class="kpi-item"><div class="kpi-val">{{ $S['equip_online'] ?? 0 }}</div><div class="kpi-lbl">Equipment Online</div><div class="kpi-sub" style="color:var(--greenA)">{{ $S['equip_maint'] ?? 0 }} in maint.</div></div>
    <div class="kpi-item"><div class="kpi-val">{{ $fmt($S['revenue'] ?? 0) }}</div><div class="kpi-lbl">Revenue</div><div class="kpi-sub" style="color:var(--greenA)">Today</div></div>
</div>

@if ($alerts->count())
    <div style="background:linear-gradient(90deg,#B71C1C,#C62828);padding:10px 16px;display:flex;align-items:center;justify-content:space-between">
        <div>
            <div style="color:#fff;font-size:12px;font-weight:700">🚨 URGENT FINDINGS — {{ $alerts->count() }} require doctor notification</div>
            <div style="color:rgba(255,255,255,.78);font-size:11px;margin-top:2px">{{ $alerts->map(fn($a) => ($a->patient->name ?? 'Patient') . ' · ' . $a->study_no . ' · ' . $a->modality)->implode(' · ') }}</div>
        </div>
        @if ($isOwner || hasPermission('radiology_urgent', 'view'))<a href="{{ route('vendor.radiology.urgent') }}" class="btn btn-outline btn-sm" style="border-color:rgba(255,255,255,.45);color:#fff">View &amp; Notify</a>@endif
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
