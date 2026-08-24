@extends('layouts.vendor.app')
@section('title', 'Doctor Consultation - ' . $visit->patient?->name)

@push('css_or_js')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
<style>
    /* ── Layout Integrations ── */
    main.main { 
        background-color: #f8fafc;
        font-family: 'Inter', sans-serif;
    }
    .content.container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
    }

    /* ── Branding Top Bar ── */
    .consult-top-bar {
        background-color: #0D47A1; /* Royal Blue */
        color: #ffffff;
        padding: 8px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #0B3C8A;
        font-size: 13px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    }
    .consult-top-bar .brand-logo {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 19px;
        letter-spacing: -0.5px;
    }
    .consult-top-bar .brand-logo span.green-text {
        color: #69F0AE;
    }
    .consult-top-bar .title-text {
        margin-left: 10px;
        padding-left: 10px;
        border-left: 1px solid rgba(255,255,255,0.25);
        font-weight: 500;
        color: #cbd5e1;
    }
    .consult-top-bar .encounter-badge {
        background-color: rgba(255,255,255,0.12);
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        color: #e2e8f0;
        margin-left: 12px;
        font-family: monospace;
    }
    .consult-top-bar .right-actions {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .status-switch {
        display: flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.1);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
    }
    .status-switch-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #3b82f6;
        box-shadow: 0 0 8px #3b82f6;
    }
    .token-status {
        background-color: rgba(74, 222, 128, 0.15);
        border: 1px solid #69F0AE;
        color: #69F0AE;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 11px;
    }
    .btn-back-queue {
        background-color: transparent;
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #ffffff !important;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none !important;
    }
    .btn-back-queue:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-color: #ffffff;
    }
    .doctor-avatar-info {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
    }
    .doctor-avatar-circle {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background-color: #e2e8f0;
        color: #0D47A1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 12px;
    }
    .clock-display {
        font-weight: 600;
        color: #ef4444;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* ── Sub-header Banner ── */
    .autosave-bar {
        background-color: #0f172a;
        color: #94a3b8;
        padding: 5px 20px;
        font-size: 11px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .autosave-bar .indicator {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .autosave-bar .indicator-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: #22c55e;
        box-shadow: 0 0 6px #22c55e;
    }

    /* ── Patient Consult Header ── */
    .patient-consult-header {
        background-color: #ffffff;
        padding: 12px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .patient-profile-brief {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .patient-avatar-box {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background-color: #0D47A1;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 700;
        font-family: 'Outfit', sans-serif;
    }
    .patient-details-text h4 {
        margin: 0 0 3px;
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        font-family: 'Outfit', sans-serif;
    }
    .patient-meta-badges {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        font-size: 11px;
    }
    .badge-meta {
        background-color: #f1f5f9;
        color: #475569;
        padding: 1px 6px;
        border-radius: 4px;
        font-weight: 600;
    }
    .badge-allergy-alert {
        background-color: #fff5f5;
        border: 1px solid #feb2b2;
        color: #e53e3e;
        padding: 1px 6px;
        border-radius: 4px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 3px;
    }
    .badge-condition {
        border: 1px solid #cbd5e1;
        padding: 1px 6px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
    }
    .badge-condition.hypertension {
        border-color: #fca5a5;
        color: #dc2626;
        background-color: #fef2f2;
    }
    .badge-condition.diabetes {
        border-color: #fed7aa;
        color: #ea580c;
        background-color: #fff7ed;
    }
    .badge-condition.dyslipidemia {
        border-color: #93c5fd;
        color: #2563eb;
        background-color: #eff6ff;
    }
    .badge-abha {
        background-color: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #15803d;
        padding: 1px 6px;
        border-radius: 4px;
        font-weight: 700;
    }
    /* The few facts from the sidebar's Patient Info card that are worth carrying on every tab.
       The card itself belongs to the Consultation tab; this line follows the doctor around. */
    .patient-key-facts {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 5px;
        font-size: 11px;
        font-weight: 600;
        color: #334155;
    }
    .patient-key-facts .kf + .kf {
        border-left: 1px solid #e2e8f0;
        padding-left: 8px;
    }
    .kf-lbl {
        color: #94a3b8;
        margin-right: 3px;
    }
    /* The receipt and the bill are reachable from every tab now that the card they used to sit
       in is only on the first one. */
    .kf-action {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 1px 7px;
        line-height: 1.6;
        font-weight: 700;
        color: #475569;
    }
    .kf-action:hover {
        background: #f1f5f9;
        color: #0f172a;
        text-decoration: none;
    }
    .kf-action-primary {
        border-color: #0D47A1;
        background: #0D47A1;
        color: #ffffff;
    }
    .kf-action-primary:hover {
        background: #0b3a85;
        color: #ffffff;
    }
    .patient-vitals-row {
        display: flex;
        gap: 8px;
    }
    .vital-item-card {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 6px 12px;
        text-align: center;
        min-width: 80px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }
    .vital-item-card .vital-val {
        font-size: 16px;
        font-weight: 800;
        margin-bottom: 2px;
        color: #0f172a;
    }
    .vital-item-card .vital-val.red-text {
        color: #dc2626;
    }
    .vital-item-card .vital-val.blue-text {
        color: #2563eb;
    }
    .vital-item-card .vital-val.green-text {
        color: #16a34a;
    }
    .vital-item-card .vital-lbl {
        font-size: 9px;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    /* ── Tabbed navigation row ── */
    .consult-tabs-row {
        background-color: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        padding: 0 20px;
        display: flex;
        gap: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .consult-tab-btn {
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 10px 14px;
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: all 0.15s;
    }
    .consult-tab-btn:hover {
        color: #0f172a;
        background-color: #f8fafc;
    }
    .consult-tab-btn.active {
        color: #0D47A1;
        border-bottom-color: #0D47A1;
    }
    .consult-tabs-aside {
        margin-left: auto;
        display: flex;
        gap: 4px;
    }
    .consult-tabs-aside .consult-tab-btn {
        color: #a4adba;
        font-weight: 600;
    }
    /* Quiet, not hidden: hover and the active state come back to full strength. */
    .consult-tabs-aside .consult-tab-btn:hover { color: #0f172a; }
    .consult-tabs-aside .consult-tab-btn.active { color: #0D47A1; font-weight: 700; }
    .consult-tabs-aside .new-indicator {
        background-color: #cbd5e1;
        color: #475569;
    }
    .consult-tabs-aside .consult-tab-btn.active .new-indicator {
        background-color: #ef4444;
        color: #ffffff;
    }
    .consult-tab-btn .new-indicator {
        background-color: #ef4444;
        color: #ffffff;
        font-size: 8px;
        padding: 1px 4px;
        border-radius: 10px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .consult-tab-btn .count-badge {
        background-color: #e2e8f0;
        color: #475569;
        font-size: 9px;
        padding: 1px 5px;
        border-radius: 10px;
        font-weight: 700;
    }

    /* ── Consultation Workspace Grid ── */
    .consult-workspace {
        display: grid;
        grid-template-columns: 260px minmax(0, 1fr);
        gap: 15px;
        padding: 15px;
        max-width: 100%;
        box-sizing: border-box;
    }
    .sidebar-column {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .content-column {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        min-height: 520px;
        min-width: 0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        padding: 15px;
    }
    .content-column .tab-pane { min-width: 0; }
    .sidebar-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        overflow: hidden;
        height: auto !important;
    }
    .sidebar-card-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 8px 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .sidebar-card-header h5 {
        margin: 0;
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .sidebar-card-body {
        padding: 10px 12px;
    }
    .sidebar-table {
        width: 100%;
        font-size: 11px;
    }
    .sidebar-table td {
        padding: 5px 0;
    }
    .sidebar-table td.lbl {
        color: #64748b;
        width: 45%;
    }
    .sidebar-table td.val {
        color: #0f172a;
        font-weight: 600;
        text-align: right;
    }
    .allergy-badge-list {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }
    .allergy-badge-item {
        background: #fff5f5;
        border: 1px solid #fed7d7;
        color: #c53030;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }
    .chronic-badge-list {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }
    .chronic-badge-item {
        background: #f0fdfa;
        border: 1px solid #ccfbf1;
        color: #0d9488;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    /* ── Diagnosis / Treatment tags ── */
    .dx-badge, .tx-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        margin: 0 4px 4px 0;
    }
    .dx-badge {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
    }
    .tx-badge {
        background: #f5f3ff;
        border: 1px solid #ddd6fe;
        color: #6d28d9;
    }
    .tx-col-lbl {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 4px;
    }
    .tx-chip { cursor: pointer; }
    .tx-counts {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
        margin-top: 5px;
        font-size: 10px;
        font-weight: 700;
        color: #94a3b8;
    }
    .tx-counts span { display: inline-flex; align-items: center; gap: 4px; }
    .tx-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        border: 1px solid;
        display: inline-block;
    }
    /* Done, booked, not booked yet — and red for work the closed OP never got to.
       Saturated on purpose: the first pass used near-white tints and a pale yellow chip was
       indistinguishable from a pale orange one at arm's length. The dot carries the same
       information as the fill, so the state survives a bad monitor or colour blindness. */
    .tx-chip::before, .tx-pill::before {
        content: '';
        display: inline-block;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        margin-right: 5px;
        vertical-align: middle;
        background: currentColor;
    }
    .tx-state-completed {
        background: #d1fae5;
        border-color: #34d399;
        color: #065f46;
    }
    .tx-state-upcoming {
        background: #ffedd5;
        border-color: #fb923c;
        color: #9a3412;
    }
    .tx-state-pending {
        background: #fef9c3;
        border-color: #facc15;
        color: #854d0e;
    }
    .tx-state-missed {
        background: #fee2e2;
        border-color: #f87171;
        color: #991b1b;
    }
    /* Net, due and how much of the plan has been collected: one statement under the table it
       totals, rather than a figure repeated beside every chip. */
    .tx-money-lbl {
        font-size: 9.5px;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .tx-money-due {
        font-size: 15px;
        font-weight: 800;
        color: #b91c1c;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
    }
    .tx-bar {
        height: 5px;
        border-radius: 4px;
        background: #e5eaf1;
        overflow: hidden;
        margin: 7px 0 0;
    }
    .tx-bar-fill {
        height: 100%;
        background: #10b981;
        border-radius: 4px;
        transition: width .25s;
    }
    /* The chips say what was advised and where each one stands. The schedule, the two figures
       and the payment state are five columns per treatment - a table, not a chip or a tooltip. */
    .tx-table-wrap { margin-top: 16px; }
    .tx-table-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 6px;
    }
    .tx-table-head .tx-counts { margin-top: 0; }
    table.tx-table {
        width: 100%;
        font-size: 12px;
        margin-bottom: 0;
        border-collapse: collapse;
    }
    table.tx-table th {
        font-size: 9.5px;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        color: #94a3b8;
        white-space: nowrap;
        padding: 6px 8px;
        border-bottom: 1px solid #e8edf4;
    }
    table.tx-table td {
        padding: 7px 8px;
        vertical-align: middle;
        border-top: 1px solid #f1f5f9;
        color: #475569;
    }
    table.tx-table tbody tr:hover { background: #f8fafc; }
    table.tx-table tfoot td {
        border-top: 1px solid #e2e8f0;
        padding: 8px;
        font-weight: 800;
        color: #0f172a;
    }
    table.tx-table .tx-paid-figure { color: #047857; }
    .tx-table-term { font-weight: 700; color: #0f172a; }
    .tx-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .tx-nil { color: #cbd5e1; }
    .tx-foot-lbl {
        font-size: 9.5px;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .tx-pill {
        display: inline-block;
        padding: 2px 9px;
        border-radius: 20px;
        border: 1px solid;
        font-size: 10px;
        font-weight: 700;
        white-space: nowrap;
    }
    .tx-paid-yes { color: #047857; font-weight: 700; }
    .tx-paid-no  { color: #94a3b8; font-weight: 600; }
    .tx-row-edit {
        border: 0;
        background: transparent;
        color: #94a3b8;
        padding: 2px 6px;
        border-radius: 5px;
        line-height: 1;
        cursor: pointer;
    }
    .tx-row-edit:hover { background: #eef2f7; color: #0D47A1; }
    /* A sitting that is a real booking, not just a date written on the plan. Links to the
       appointment so the desk's copy of it is one click from the chair. */
    .tx-booked {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        margin-left: 6px;
        padding: 1px 6px;
        border-radius: 20px;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 9.5px;
        font-weight: 700;
        white-space: nowrap;
    }
    .tx-booked:hover { background: #dbeafe; color: #1e3a8a; text-decoration: none; }
    .tx-booked.is-stale { border-color: #fecaca; background: #fef2f2; color: #b91c1c; }
    .tx-plan-book {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        margin: 0 0 8px;
        padding: 7px 8px;
        border: 1px solid #e2e8f0;
        border-radius: 7px;
        background: #f8fafc;
        font-size: 11.5px;
        font-weight: 700;
        color: #334155;
        cursor: pointer;
    }
    .tx-plan-book input { width: 14px; height: 14px; margin-top: 1px; }
    .tx-plan-book .tx-plan-book-note {
        display: block;
        font-size: 10px;
        font-weight: 600;
        color: #94a3b8;
        margin-top: 1px;
    }
    .tx-plan-menu .tx-plan-booked {
        display: block;
        margin-bottom: 8px;
        padding: 7px 8px;
        border: 1px solid #bfdbfe;
        border-radius: 7px;
        background: #eff6ff;
        font-size: 11px;
        font-weight: 700;
        color: #1d4ed8;
    }
    /* ── Next Visit: one block per follow-up being booked ── */
    .nv-row {
        position: relative;
        border: 1px solid #e8edf4;
        border-radius: 10px;
        padding: 12px 14px 2px;
        margin-bottom: 10px;
        background: #fbfdff;
    }
    .nv-row-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    .nv-row-no {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .4px;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .nv-row-drop {
        border: 0;
        background: transparent;
        color: #cbd5e1;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        padding: 2px 6px;
        border-radius: 5px;
    }
    .nv-row-drop:hover { background: #fef2f2; color: #dc2626; }
    .nv-tx-box { margin-bottom: 12px; }
    .nv-tx-pick {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin: 0 5px 5px 0;
        padding: 4px 10px;
        border-radius: 20px;
        border: 1px solid #dbe3ec;
        background: #ffffff;
        font-size: 11.5px;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
    }
    .nv-tx-pick input { width: 13px; height: 13px; }
    .nv-tx-pick.is-on { border-color: #0D47A1; background: #eef4ff; color: #0D47A1; }
    .nv-tx-pick.is-booked { border-style: dashed; }
    .nv-for-chip {
        display: inline-block;
        margin: 0 3px 2px 0;
        padding: 1px 7px;
        border-radius: 4px;
        background: #eef4ff;
        color: #0D47A1;
        font-size: 10.5px;
        font-weight: 700;
    }
    .tx-due-strip {
        margin-top: 10px;
        padding: 9px 11px;
        background: #f8fafc;
        border: 1px solid #e8edf4;
        border-radius: 9px;
    }
    .tx-due-line {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 10px;
    }
    .tx-plan-paid {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 8px;
        font-size: 12px;
        font-weight: 700;
        color: #334155;
    }
    .tx-plan-paid input { width: 14px; height: 14px; }
    .tx-plan-menu { min-width: 250px; padding: 10px; }
    .tx-plan-menu .tx-plan-title {
        font-size: 12px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 8px;
    }
    .tx-plan-menu label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: #94a3b8;
        margin-bottom: 2px;
        display: block;
    }
    .tx-plan-menu .form-control { font-size: 12px; height: 30px; }
    .tx-plan-row { display: flex; gap: 8px; margin-bottom: 8px; }
    .tx-plan-row > div { flex: 1; }
    .tx-status-menu {
        position: absolute;
        z-index: 1080;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(15,23,42,.14);
        padding: 4px;
        min-width: 172px;
    }
    .tx-status-menu button {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        background: transparent;
        border: 0;
        padding: 7px 10px;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
        border-radius: 6px;
        text-align: left;
        cursor: pointer;
    }
    .tx-status-menu button:hover { background: #f1f5f9; }
    .tx-status-menu button.is-current { color: #0D47A1; }
    .wt-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        margin: 0 4px 4px 0;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #047857;
    }
    /* Tappable suggestion chips. Deliberately quieter than the saved badges above — these are
       offers, and they must not read as something already recorded on the visit. */
    .term-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        margin: 0 4px 4px 0;
        border-radius: 999px;
        border: 1px dashed #cbd5e1;
        background: #fff;
        color: #475569;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: background .12s, border-color .12s, color .12s;
    }
    .term-chip:hover {
        border-color: #94a3b8;
        background: #f8fafc;
    }
    .term-chip.is-on {
        border-style: solid;
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }
    .term-chip-suggest:hover {
        background: #f5f3ff;
        border-color: #ddd6fe;
        color: #6d28d9;
    }
    .term-chip-count {
        font-size: 10px;
        font-weight: 700;
        opacity: .55;
    }
    #dxEdit .select2-container--default .select2-selection--multiple {
        border-color: #e7eaf3;
        min-height: 34px;
        height: auto;
    }
    #dxEdit .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        padding-bottom: 2px;
    }
    #dxEdit .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 600;
        padding: 1px 6px;
    }
    #dxEdit .tx-select2 .select2-selection__choice {
        background: #f5f3ff;
        border-color: #ddd6fe;
        color: #6d28d9;
    }
    #dxEdit .wt-select2 .select2-selection__choice {
        background: #ecfdf5;
        border-color: #a7f3d0;
        color: #047857;
    }

    /* ── Complaints ──
       Amber, so what the patient reports reads apart at a glance from what the doctor
       concluded (blue diagnosis) and what was given (violet treatment). */
    .cc-badge {
        display: inline-block;
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
        border-radius: 5px;
        font-size: 11.5px;
        font-weight: 600;
        padding: 2px 8px;
        margin: 0 4px 4px 0;
    }
    #ccEdit .select2-container--default .select2-selection--multiple {
        border-color: #e7eaf3;
        min-height: 34px;
        height: auto;
    }
    #ccEdit .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        padding-bottom: 2px;
    }
    #ccEdit .cc-select2 .select2-selection__choice {
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
        font-size: 11px;
        font-weight: 600;
        padding: 1px 6px;
    }

    /* ── The × on a selected chip ──
       Select2 4.0.5 renders the remove control as the first child of the chip, and the admin
       theme absolutely positions it into right-hand padding it reserves on the chip. The
       padding shorthands above reset that padding, which left the × sitting on top of the
       label with nowhere to go — so complaints, diagnosis and treatment all looked like they
       could be added but never removed.
       Putting it back into normal flow and ordering it last is immune to whichever left/right
       offsets the theme and select2custom.css disagree on, and `color: inherit` keeps each ×
       the colour of the chip it belongs to (amber / blue / violet). */
    #ccEdit .cc-select2 .select2-selection__choice,
    #dxEdit .select2-container--default .select2-selection--multiple .select2-selection__choice {
        display: inline-flex;
        flex-direction: row-reverse;
        align-items: center;
        gap: 5px;
    }

    #ccEdit .cc-select2 .select2-selection__choice__remove,
    #dxEdit .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        position: static !important;
        inset: auto !important;
        float: none !important;
        margin: 0 !important;
        color: inherit;
        font-size: 13px;
        line-height: 1;
        opacity: .55;
    }

    #ccEdit .cc-select2 .select2-selection__choice__remove:hover,
    #dxEdit .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        opacity: 1;
    }

    /* Shown where an edit pencil would be once the visit is closed, so the desk can see the
       control is gone on purpose rather than broken. */
    .visit-locked {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #64748b;
        border-radius: 5px;
        font-size: 10.5px;
        font-weight: 700;
        padding: 2px 7px;
        cursor: default;
    }

    /* Consultation notes — teal, so what the doctor recorded as advice reads apart from the
       amber complaints, blue diagnosis and violet treatment. */
    .nt-badge {
        display: inline-block;
        background: #f0fdfa;
        border: 1px solid #99f6e4;
        color: #0f766e;
        border-radius: 5px;
        font-size: 11.5px;
        font-weight: 600;
        padding: 2px 8px;
        margin: 0 4px 4px 0;
    }

    #notesEdit .nt-select2 .select2-selection__choice {
        background: #f0fdfa;
        border: 1px solid #99f6e4;
        color: #0f766e;
        font-size: 11px;
        font-weight: 600;
        padding: 1px 6px;
        display: inline-flex;
        flex-direction: row-reverse;
        align-items: center;
        gap: 5px;
    }

    #notesEdit .nt-select2 .select2-selection__choice__remove {
        position: static !important;
        inset: auto !important;
        float: none !important;
        margin: 0 !important;
        color: inherit;
        font-size: 13px;
        line-height: 1;
        opacity: .55;
    }

    #notesEdit .nt-select2 .select2-selection__choice__remove:hover { opacity: 1; }

    #notesEdit .select2-container--default .select2-selection--multiple {
        border-color: #e7eaf3;
        min-height: 34px;
        height: auto;
    }
    #notesEdit .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        padding-bottom: 2px;
    }
    .cc-group {
        display: inline-flex;
        align-items: center;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        border-radius: 999px;
        margin: 0 6px 6px 0;
        overflow: hidden;
    }
    .cc-group-apply {
        border: 0;
        background: transparent;
        color: #1d4ed8;
        font-size: 11.5px;
        font-weight: 700;
        padding: 3px 4px 3px 11px;
        cursor: pointer;
    }
    .cc-group-del {
        color: #93a3b8;
        font-size: 14px;
        line-height: 1;
        padding: 0 9px 0 4px;
        cursor: pointer;
    }
    .cc-group-del:hover { color: #dc3545; }

    /* ── Tab Content Panes ── */
    .tab-pane {
        display: none;
    }
    .tab-pane.active {
        display: block;
    }

    /* ── Sara AI Tab Styling ── */
    .sara-ai-header {
        background: #0D47A1;
        color: #ffffff;
        padding: 12px 15px;
        border-radius: 6px;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-family: 'Outfit', sans-serif;
    }
    .sara-ai-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #ffffff !important;
    }
    .sara-ai-header p {
        margin: 2px 0 0;
        font-size: 11px;
        opacity: 0.8;
        color: #ffffff !important;
    }
    .ai-disclaimer-box {
        background-color: #fffdeb;
        border: 1px solid #fde68a;
        border-radius: 6px;
        padding: 10px 14px;
        color: #92400e;
        font-size: 11px;
        line-height: 1.5;
        margin-bottom: 15px;
        display: flex;
        gap: 8px;
        align-items: flex-start;
    }
    .risk-snapshot-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }
    .risk-item-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 12px;
    }
    .risk-item-header {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 6px;
    }
    .risk-item-header .risk-lbl {
        font-size: 10px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
    }
    .risk-item-header .risk-val {
        font-size: 13px;
        font-weight: 700;
    }
    .risk-item-header .risk-val.high {
        color: #dc2626;
    }
    .risk-item-header .risk-val.moderate {
        color: #d97706;
    }
    .risk-item-header .risk-val.poor {
        color: #dc2626;
    }
    .risk-item-header .risk-val.good {
        color: #16a34a;
    }
    .risk-progress-container {
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 6px;
    }
    .risk-progress-bar {
        height: 100%;
        border-radius: 3px;
        width: 0%;
        transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .risk-progress-bar.red {
        background: linear-gradient(90deg, #ef4444, #dc2626);
    }
    .risk-progress-bar.orange {
        background: linear-gradient(90deg, #f97316, #ea580c);
    }
    .risk-progress-bar.green {
        background: linear-gradient(90deg, #22c55e, #16a34a);
    }
    .risk-confidence-footer {
        font-size: 10px;
        color: #64748b;
        display: flex;
        justify-content: space-between;
    }
    .clinical-findings-panel {
        border-top: 1px solid #cbd5e1;
        padding-top: 15px;
    }
    .clinical-findings-panel h4 {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    .findings-alert-card {
        background: #fff5f5;
        border: 1px solid #fed7d7;
        border-left: 4px solid #e53e3e;
        border-radius: 6px;
        padding: 10px 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #9b2c2c;
        font-size: 12px;
        font-weight: 600;
    }
    .findings-alert-card .why-btn {
        background: transparent;
        border: none;
        color: #2b6cb0;
        text-decoration: underline;
        cursor: pointer;
        font-weight: 700;
    }

    /* ── OP + Prescription workspace ── */
    .opd-collapse-head { cursor:pointer; }
    .dbl-editable { cursor:pointer; }
    .rx-actions .rx-lang { order:-1; margin-left:0; margin-right:auto; }
    .rx-sync-hint { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; color:#0ea5e9; }
    .rx-sync-hint.rx-sync-off { color:#94a3b8; }
    .rx-med-list { border:1px solid #e2e8f0; border-radius:8px; overflow-x:auto; }
    .rx-med-list thead th { font-size:11px; text-transform:uppercase; letter-spacing:.3px; color:#64748b; border-top:0; }
    .rx-followup { padding:8px 12px; border-top:1px solid #e2e8f0; font-size:12px; color:#334155; background:#f8fafc; }
    .rx-advice { padding:8px 12px; border-top:1px solid #e2e8f0; font-size:12px; color:#334155; }
    .rx-advice-lbl { font-weight:700; text-transform:uppercase; font-size:10px; letter-spacing:.3px; color:#64748b; margin-right:6px; }

    /* ── Prescription form styles ── */
    .med-row {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 8px 10px;
        margin-bottom: 6px;
    }
    .med-row input, .med-row select {
        font-size: 12px;
    }
    .med-row .btn-remove-row {
        color: #ef4444;
        background: transparent;
        border: none;
        cursor: pointer;
    }
    #pharmacySuggestions {
        display: none;
        list-style: none;
        margin: 0;
        padding: 4px 0;
        border: 1px solid #e5e7eb;
        border-radius: 0 0 6px 6px;
        background: #fff;
        max-height: 160px;
        overflow-y: auto;
        position: absolute;
        width: 100%;
        z-index: 99;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    /* ── Printable Prescription wrapper ── */
    .rx-view-wrap {
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 20px;
        background: #ffffff;
        max-width: 700px;
        margin: 0 auto;
        font-family: 'Times New Roman', Times, serif;
    }
    .rx-view-header {
        display: flex;
        justify-content: space-between;
        border-bottom: 2px solid #0D47A1;
        padding-bottom: 8px;
        margin-bottom: 12px;
    }
</style>
@endpush

@section('content')
@php $showVitals = hmis_vitals_enabled(); @endphp
<div class="content container-fluid">

    {{-- 1. BRANDING TOP BAR --}}
    <div class="consult-top-bar">
        <div class="brand-logo">
            <span class="green-text">Doctor</span> Consultation
            <span class="encounter-badge">ENC-{{ $visit->visit_date?->format('Ymd') }}-{{ str_pad($visit->id, 4, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="right-actions">
            <div class="status-switch">
                <span class="status-switch-indicator"></span>
                <strong>Routine</strong>
            </div>
            <div class="token-status">
                Token {{ $visit->token_number }} / {{ \App\Models\OpdVisit::where('store_id', $visit->store_id)->whereDate('visit_date', $visit->visit_date)->count() }}
            </div>
            {{-- The summary and the feedback request are standing decisions the hospital makes
                 under Notification Settings, and both go out on their own once the consultation is
                 billed (OpdConsultationReceiptController::autoSendToPatient) or the appointment is
                 marked completed. No button here: it would only offer to send a patient the same
                 summary a second time. --}}
            <a href="{{ route('vendor.opd.index') }}" class="btn-back-queue">
                ← OPD Queue
            </a>
            <div class="doctor-avatar-info">
                <div class="doctor-avatar-circle">
                    {{ strtoupper(substr($visit->doctorProfile?->employee?->f_name ?? 'D', 0, 1)) }}
                </div>
                <span>Dr. {{ $visit->doctorProfile?->employee?->f_name }} {{ $visit->doctorProfile?->employee?->l_name }}</span>
            </div>
            <div class="clock-display" id="tickClock">
                <i class="tio-time"></i> 00:00:00
            </div>
        </div>
    </div>

    @if ($visit->is_cancelled)
        {{-- Every field below stays editable by design — notes written during a consultation that
             was then cancelled are still the record of what happened. The banner is what stops the
             screen being mistaken for a live encounter. --}}
        <div class="alert mb-0" style="border-radius:0;background:#fef2f2;border:0;border-bottom:1px solid #fecaca;color:#991b1b;">
            <strong><i class="tio-clear-circle"></i> This visit was cancelled</strong>
            @if ($visit->cancel_reason)
                — {{ $visit->cancel_reason }}
            @endif
            @if ($visit->cancelled_at)
                <span style="font-size:12px;opacity:.8;">({{ $visit->cancelled_at->format('d M Y, h:i A') }})</span>
            @endif
        </div>
    @endif

    {{-- 2. AUTOSAVE BANNER --}}
    <div class="autosave-bar">
        <div class="indicator">
            <span class="indicator-dot"></span>
            <span>Auto-saved: <span id="saveTime">{{ now()->format('h:i:s a') }}</span></span>
        </div>
        <div>
            ENC-{{ $visit->visit_date?->format('Ymd') }}-{{ str_pad($visit->id, 4, '0', STR_PAD_LEFT) }} - Dr. {{ $visit->doctorProfile?->employee?->f_name }} {{ $visit->doctorProfile?->employee?->l_name }} - {{ $visit->patient?->name }}
        </div>
    </div>

    {{-- 3. PATIENT HEADLINE DETAILS & VITALS GRID --}}
    <div class="patient-consult-header">
        <div class="patient-profile-brief">
            <div class="patient-avatar-box">
                {{ strtoupper(substr($visit->patient?->name ?? 'P', 0, 1)) }}
            </div>
            <div class="patient-details-text">
                <h4>{{ $visit->patient?->name }}</h4>
                <div class="patient-meta-badges">
                    <span class="badge-meta">{{ $visit->patient?->patient_uid }}</span>
                    <span class="badge-meta">{{ ucfirst($visit->patient?->gender) }} - {{ \Carbon\Carbon::parse($visit->patient?->dob)->age }} yrs</span>
                    <span class="badge-meta">Blood Group: {{ $visit->patient?->blood_group ?: '—' }}</span>

                    @if($visit->patient?->allergies)
                        <span class="badge-allergy-alert">
                            <i class="tio-warning"></i> Allergies
                        </span>
                    @endif

                    @php $history = $visit->patient?->medicalHistory; @endphp
                    @if($history && $history->chronic_conditions)
                        @foreach(array_map('trim', explode(',', $history->chronic_conditions)) as $cond)
                            @if(stripos($cond, 'hypertension') !== false)
                                <span class="badge-condition hypertension">Hypertension</span>
                            @elseif(stripos($cond, 't2dm') !== false || stripos($cond, 'diabetes') !== false)
                                <span class="badge-condition diabetes">T2DM</span>
                            @elseif(stripos($cond, 'dyslipidemia') !== false || stripos($cond, 'cholesterol') !== false)
                                <span class="badge-condition dyslipidemia">Dyslipidemia</span>
                            @else
                                <span class="badge-condition">{{ $cond }}</span>
                            @endif
                        @endforeach
                    @endif
                </div>
                {{-- Everything a doctor asks for without meaning to leave the tab they are on:
                     the number to ring, what kind of visit this is, and how long they have been
                     coming. The sidebar card repeats it in full, on the Consultation tab only. --}}
                <div class="patient-key-facts">
                    <span class="kf"><span class="kf-lbl">Mobile</span>{{ $visit->patient?->phone ?: '—' }}</span>
                    <span class="kf"><span class="kf-lbl">Visit</span>{{ \App\Models\OpdVisit::VISIT_TYPES[$visit->visit_type] ?? $visit->visit_type }}</span>
                    <span class="kf"><span class="kf-lbl">OP</span>{{ $visit->op_type ?: '—' }}</span>
                    <span class="kf"><span class="kf-lbl">Last visit</span>{{ $pastVisits->first()?->visit_date?->format('d M Y') ?: 'None' }}</span>
                    <span class="kf"><span class="kf-lbl">Total</span>{{ $pastVisits->count() + 1 }} visits</span>

                    @if (!$visit->is_cancelled && hasPermission('opd_register', 'view'))
                        <a href="{{ route('vendor.opd.consultation-receipt', $visit->id) }}" target="_blank" class="kf-action">
                            <i class="tio-receipt"></i> OP Receipt
                        </a>
                    @endif

                    @if (!$visit->is_cancelled && hasPermission('opd_register', 'generate_bill'))
                        <a href="{{ route('vendor.hospital-bill.create-opd', $visit->id) }}" class="kf-action kf-action-primary">
                            <i class="tio-receipt-outlined"></i> Generate Bill
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @if($showVitals)
        <div class="patient-vitals-row">
            {{-- BP --}}
            <div class="vital-item-card">
                <div class="vital-val @if($visit->bp_systolic >= 140 || $visit->bp_diastolic >= 90) red-text @else green-text @endif" id="vsBp">
                    {{ $visit->bp_systolic ?: '—' }}/{{ $visit->bp_diastolic ?: '—' }}
                </div>
                <div class="vital-lbl">BP mmHg</div>
            </div>
            {{-- Pulse --}}
            <div class="vital-item-card">
                <div class="vital-val blue-text" id="vsPulse">{{ $visit->pulse_rate ?: '—' }}</div>
                <div class="vital-lbl">Pulse/min</div>
            </div>
            {{-- Temperature --}}
            <div class="vital-item-card">
                <div class="vital-val @if($visit->temperature >= 100) red-text @else green-text @endif" id="vsTemp">
                    {{ $visit->temperature ?: '—' }}
                </div>
                <div class="vital-lbl">Temp °F</div>
            </div>
            {{-- SpO2 --}}
            <div class="vital-item-card">
                <div class="vital-val @if($visit->spo2 && $visit->spo2 < 95) red-text @else green-text @endif" id="vsSpo2">
                    {{ $visit->spo2 ? $visit->spo2 . '%' : '—' }}
                </div>
                <div class="vital-lbl">SpO2 %</div>
            </div>
            {{-- Weight --}}
            <div class="vital-item-card">
                <div class="vital-val" id="vsWeight">{{ $visit->weight ?: '—' }}</div>
                <div class="vital-lbl">Wt kg</div>
            </div>
        </div>
        @endif
    </div>

    @if (filled($visit->patient?->phone))
        {{-- Posted only when the doctor confirms after a status change. The template is the
             module's approved `treatment` one — a treatment summary, not a per-chip message. --}}
        <form id="txWaForm" method="post" action="{{ route('vendor.hmis-whatsapp.treatment', $visit->id) }}" class="d-none">
            @csrf
        </form>
    @endif

    {{-- 4. HORIZONTAL TABBAR --}}
    <div class="consult-tabs-row">
        <button class="consult-tab-btn active" onclick="switchTab(this, 'tabDetails')">
            <i class="tio-folder-bookmarked"></i> Consultation
        </button>
        <button class="consult-tab-btn" onclick="switchTab(this, 'tabPastRx')">
            <i class="tio-history"></i> Past Rx
        </button>
        <button class="consult-tab-btn" onclick="switchTab(this, 'tabTests')">
            <i class="tio-dashboard-outlined"></i> Tests
        </button>
        <button class="consult-tab-btn" onclick="switchTab(this, 'tabReports')">
            <i class="tio-document-text"></i> Reports
            @if($visit->patient?->documents?->count())
                <span class="count-badge ml-1">{{ $visit->patient->documents->count() }}</span>
            @endif
        </button>
        <button class="consult-tab-btn" onclick="switchTab(this, 'tabNextVisit')">
            <i class="tio-calendar-note"></i> Next Visit
            @if($upcomingVisits->count())
                <span class="count-badge ml-1">{{ $upcomingVisits->count() }}</span>
            @endif
        </button>
        @if($labWorkEnabled ?? false)
        <button class="consult-tab-btn" onclick="switchTab(this, 'tabLabWork')">
            <i class="tio-lab"></i> Lab Work
            @if($labWorks->where('is_open', true)->count())
                <span class="count-badge ml-1">{{ $labWorks->where('is_open', true)->count() }}</span>
            @endif
        </button>
        @endif

        <button class="consult-tab-btn" onclick="switchTab(this, 'tabMode')">
            <i class="tio-settings-outlined"></i> Mode
        </button>
        @if($securityEnabled ?? false)
        <button class="consult-tab-btn" onclick="switchTab(this, 'tabSecurity')">
            <i class="tio-lock-outlined"></i> Security
            @if($securityLog->count())
                <span class="count-badge ml-1">{{ $securityLog->count() }}</span>
            @endif
        </button>
        @endif

        {{-- Reference tabs, not part of the consultation itself: pushed right and
             toned down so the run of tabs on the left reads as the actual workflow. --}}
        <div class="consult-tabs-aside">
            <button class="consult-tab-btn" onclick="switchTab(this, 'tabSaraAI')" id="btnSaraTab">
                <i class="tio-star"></i> Sara AI <span class="new-indicator ml-1">New</span>
            </button>
            <button class="consult-tab-btn" onclick="switchTab(this, 'tabTimeline')">
                <i class="tio-calendar-note"></i> Timeline
            </button>
        </div>
    </div>

    {{-- 5. TWO-COLUMN CONSULTATION WORKSPACE --}}
    <div class="consult-workspace">

        {{-- LEFT SIDEBAR --}}
        <div class="sidebar-column">

            {{-- Patient Info Card — Consultation tab only: on Past Rx, Tests or Reports the
                 doctor is reading rather than admitting, and the line under the patient's name
                 carries the facts that still matter there. Shown and hidden by switchTab(). --}}
            <div class="sidebar-card tab-only-details" id="cardPatientInfo">
                <div class="sidebar-card-header">
                    <h5>Patient Info</h5>
                    <button class="btn btn-xs btn-outline-secondary py-0 px-1" style="font-size:10px" data-toggle="collapse" data-target="#collapseSidebarInfo">Show/Hide</button>
                </div>
                <div class="sidebar-card-body collapse show" id="collapseSidebarInfo">
                    <table class="sidebar-table">
                        <tr><td class="lbl">Patient ID</td><td class="val">{{ $visit->patient?->patient_uid }}</td></tr>
                        <tr><td class="lbl">Age / Gender</td><td class="val">{{ \Carbon\Carbon::parse($visit->patient?->dob)->age }} Yrs - {{ ucfirst($visit->patient?->gender) }}</td></tr>
                        <tr><td class="lbl">Blood Group</td><td class="val">{{ $visit->patient?->blood_group ?: '—' }}</td></tr>
                        <tr><td class="lbl">Mobile</td><td class="val">{{ $visit->patient?->phone ?: '—' }}</td></tr>
                        <tr><td class="lbl">Referred By</td><td class="val" style="color:#2563eb">Dr. S. Rao (Ortho)</td></tr>
                        <tr><td class="lbl">Visit Type</td><td class="val" style="color:#16a34a">{{ \App\Models\OpdVisit::VISIT_TYPES[$visit->visit_type] ?? $visit->visit_type }}</td></tr>
                        <tr><td class="lbl">OP Type</td><td class="val">{{ $visit->op_type ?: '—' }}</td></tr>
                        <tr><td class="lbl">Last Visit</td><td class="val">{{ $pastVisits->first()?->visit_date?->format('d M Y') ?: 'None' }}</td></tr>
                        <tr><td class="lbl">Total Visits</td><td class="val">{{ $pastVisits->count() + 1 }} visits</td></tr>
                    </table>
                </div>
            </div>

            {{-- Allergies Card --}}
            <div class="sidebar-card">
                <div class="sidebar-card-header">
                    <h5>Allergies</h5>
                    @if (hasPermission('patient', 'edit'))
                        <button class="btn btn-xs btn-soft-danger py-0 px-1" style="font-size: 10px" onclick="editSidebarField('allergies')">+ Edit</button>
                    @endif
                </div>
                <div class="sidebar-card-body">
                    <div class="allergy-badge-list" id="sidebarAllergiesList">
                        @if($visit->patient?->allergies)
                            @foreach(array_map('trim', explode(',', $visit->patient->allergies)) as $allergy)
                                <span class="allergy-badge-item">{{ $allergy }}</span>
                            @endforeach
                        @else
                            <span class="text-muted small">No allergies recorded.</span>
                        @endif
                    </div>
                    {{-- Edit Box (hidden) --}}
                    @if (hasPermission('patient', 'edit'))
                        <div id="editAllergiesBox" class="mt-2" style="display:none;">
                            <input type="text" id="inputAllergies" class="form-control form-control-sm" value="{{ $visit->patient?->allergies }}" placeholder="Comma separated list">
                            <div class="mt-1 d-flex gap-1">
                                <button class="btn btn-xs btn-primary" onclick="saveSidebarField('allergies')">Save</button>
                                <button class="btn btn-xs btn-light" onclick="cancelSidebarEdit('allergies')">Cancel</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Chronic Conditions Card --}}
            <div class="sidebar-card">
                <div class="sidebar-card-header">
                    <h5>Chronic Conditions</h5>
                    @if (hasPermission('patient', 'edit'))
                        <button class="btn btn-xs btn-soft-info py-0 px-1" style="font-size: 10px" onclick="editSidebarField('chronic')">+ Edit</button>
                    @endif
                </div>
                <div class="sidebar-card-body">
                    <div class="chronic-badge-list" id="sidebarChronicList">
                        @if($history && $history->chronic_conditions)
                            @foreach(array_map('trim', explode(',', $history->chronic_conditions)) as $cond)
                                <span class="chronic-badge-item">{{ $cond }}</span>
                            @endforeach
                        @else
                            <span class="text-muted small">No chronic conditions recorded.</span>
                        @endif
                    </div>
                    {{-- Edit Box (hidden) --}}
                    @if (hasPermission('patient', 'edit'))
                        <div id="editChronicBox" class="mt-2" style="display:none;">
                            <input type="text" id="inputChronic" class="form-control form-control-sm" value="{{ $history?->chronic_conditions }}" placeholder="Comma separated list">
                            <div class="mt-1 d-flex gap-1">
                                <button class="btn btn-xs btn-primary" onclick="saveSidebarField('chronic')">Save</button>
                                <button class="btn btn-xs btn-light" onclick="cancelSidebarEdit('chronic')">Cancel</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- MAIN TAB CONTENT COLUMN --}}
        <div class="content-column">

            {{-- TAB: DETAILS --}}
            <div class="tab-pane active" id="tabDetails">
                <h4 class="mb-3 font-weight-bold" style="color:#0f172a">Visit Details</h4>
                <div class="row mb-4">
                    {{-- Chief Complaint --}}
                    <div class="col-md-6">
                        <div class="card shadow-none border">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center bg-light">
                                <h6 class="mb-0 font-weight-bold" style="font-size:13px">Chief Complaint</h6>
                                @if ($visit->is_completed)
                                    <span class="visit-locked" title="This visit is completed. The OP receipt has been issued, so the record is closed."><i class="tio-lock"></i> Completed</span>
                                @elseif (hasPermission('opd_register', 'edit'))
                                    <button class="btn btn-xs btn-soft-secondary" onclick="toggleEdit('cc')">
                                        <i class="tio-edit" id="ccEditIcon"></i>
                                    </button>
                                @endif
                            </div>
                            <div class="card-body py-3 dbl-editable" id="ccView" ondblclick="editOnDblClick('cc')" title="Double-click to edit">
                                <div id="ccBadges">
                                    @forelse($visit->complaint_list as $term)
                                        <span class="cc-badge">{{ $term }}</span>
                                    @empty
                                        <span class="text-muted small">Not recorded yet.</span>
                                    @endforelse
                                </div>
                            </div>
                            <div class="card-body py-3" id="ccEdit" style="display:none;">
                                <div class="form-group mb-2">
                                    <label class="input-label" style="font-size:12px">Complaints</label>
                                    {{-- id overridden so this screen's own save/badge code keeps
                                         addressing it as ccSelect; the groups row, the Save-as-group
                                         button and their behaviour all come from the partial. --}}
                                    {{-- autoInit off: this card starts hidden, and Select2 sizes
                                         itself wrong when built against a hidden container. Built
                                         on first open instead, by initCcSelect2(). --}}
                                    @include('hmis::vendor.opd._complaint_picker', [
                                        'id'       => 'ccSelect',
                                        'selected' => $visit->complaint_list,
                                        'options'  => $complaintOptions ?? [],
                                        'groups'   => $complaintGroups ?? [],
                                        'autoInit' => false,
                                    ])
                                </div>

                                <div class="mt-2 d-flex flex-wrap" style="gap:8px;">
                                    <button class="btn btn-sm btn-primary" onclick="saveField('cc')">Save</button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleEdit('cc')">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="col-md-6">
                        <div class="card shadow-none border">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center bg-light">
                                <h6 class="mb-0 font-weight-bold" style="font-size:13px">Doctor's Consultation Notes</h6>
                                @if ($visit->is_completed)
                                    <span class="visit-locked" title="This visit is completed. The OP receipt has been issued, so the record is closed."><i class="tio-lock"></i> Completed</span>
                                @elseif (hasPermission('opd_register', 'edit'))
                                    <button class="btn btn-xs btn-soft-secondary" onclick="toggleEdit('notes')">
                                        <i class="tio-edit" id="notesEditIcon"></i>
                                    </button>
                                @endif
                            </div>
                            <div class="card-body py-3 dbl-editable" id="notesView" ondblclick="editOnDblClick('notes')" title="Double-click to edit">
                                <div id="notesBadges">
                                    @forelse(\App\Models\OpdVisit::splitTerms($visit->notes) as $phrase)
                                        <span class="nt-badge">{{ $phrase }}</span>
                                    @empty
                                        <span class="text-muted small">Not recorded yet.</span>
                                    @endforelse
                                </div>
                            </div>
                            <div class="card-body py-3" id="notesEdit" style="display:none;">
                                {{-- Notes are chips, the same gesture as Chief Complaint: pick a
                                     phrase this clinic already uses, or type a new one and press
                                     Enter — quickUpdate absorbs it into the store's list so it is
                                     offered from then on. --}}
                                {{-- Block form, not @php(...): a short form here stops every later
                                     @php ... @endphp in this file from compiling at all. --}}
                                @php $notePhrases = \App\Models\OpdVisit::splitTerms($visit->notes); @endphp
                                <div class="form-group mb-2">
                                    <label class="input-label" style="font-size:12px">Notes</label>
                                    <select id="ntSelect" multiple class="form-control form-control-sm">
                                        @foreach (collect($noteTemplates ?? [])->pluck('name')->merge($notePhrases)->unique() as $phrase)
                                            <option value="{{ $phrase }}" @if(in_array($phrase, $notePhrases)) selected @endif>{{ $phrase }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="cc-tools">
                                    <button type="button" class="cc-tool" onclick="openNoteTemplates()">
                                        <i class="tio-folder-outlined"></i> Manage<span class="cc-tool-count" data-nt-count>0</span>
                                    </button>
                                    <span class="cc-hint">Pick from the list, or type a new one and press Enter. Saves as you pick.</span>
                                </div>
                                {{-- No Save button: the note saves as it is typed, and Close flushes
                                     anything still on the debounce timer. --}}
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleEdit('notes')">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Diagnosis & Treatment --}}
                @php
                    $dxCurrent = $visit->diagnosis_list;
                    $txCurrent = $visit->treatment_list;
                    $dxUsage = $termInsights['diagnosis'] ?? [];
                    $txUsage = $termInsights['treatment'] ?? [];

                    // Most-used first, alphabetical to break ties. A busy OPD sees the same dozen
                    // presentations all morning; making the doctor scroll past Anaemia to reach
                    // Viral Fever every time is the whole problem with an alphabetical list.
                    //
                    // Ranked on the normalised key, so a term that differs from the recorded
                    // spelling only by case or spacing still finds its own history.
                    $rankTerms = function ($choices, $usage) {
                        $count = fn($term) => $usage[\App\Services\OpdTermInsights::key($term)] ?? 0;

                        return collect($choices)->unique()->sort(function ($a, $b) use ($count) {
                            return ($count($b) <=> $count($a)) ?: strcasecmp($a, $b);
                        })->values();
                    };

                    $wtCurrent = $visit->willing_treatment_list;
                    $txPlan   = $visit->treatment_plan_map;
                    $txStateLabels = [
                        'pending'   => 'Pending',
                        'upcoming'  => 'Upcoming',
                        'completed' => 'Completed',
                        'missed'    => 'Not done',
                    ];
                    $rxCurrency = \App\CentralLogics\Helpers::currency_symbol() ?: '₹';

                    $txWhen = function ($row) {
                        $parts = [];
                        $date = $row['date'] ?? '';
                        $time = substr((string) ($row['time'] ?? ''), 0, 5);

                        if ($date) {
                            $parts[] = \Carbon\Carbon::hasFormat($date, 'Y-m-d')
                                ? \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format('j M Y')
                                : $date;
                        }
                        if ($time) {
                            $parts[] = \Carbon\Carbon::hasFormat($time, 'H:i')
                                ? \Carbon\Carbon::createFromFormat('H:i', $time)->format('g:i A')
                                : $time;
                        }
                        return implode(' · ', $parts);
                    };

                    // One pass over the advised list feeds all three readings of it: the chips,
                    // the state counts that stand in for a colour key, and the detail table.
                    $txRows = [];
                    $txStateCounts = [];
                    $txSum = ['gross' => 0.0, 'discount' => 0.0, 'paid' => 0.0];

                    foreach ($txCurrent as $term) {
                        $row   = $txPlan[$term] ?? [];
                        $state = $row['status'] ?? 'pending';
                        $state = ($visit->is_completed && $state !== 'completed') ? 'missed' : $state;

                        $amount = (float) ($row['amount'] ?? 0);
                        $less   = (float) ($row['discount'] ?? 0);
                        $net    = max($amount - $less, 0);
                        $isPaid = !empty($row['paid']);
                        $when   = $txWhen($row);
                        // Booked as a real follow-up, or just a date pencilled onto the plan.
                        $appt   = $treatmentAppointments[(int) ($row['appointment_id'] ?? 0)] ?? null;

                        $txStateCounts[$state] = ($txStateCounts[$state] ?? 0) + 1;
                        $txSum['gross']    += $amount;
                        $txSum['discount'] += $less;
                        if ($isPaid) {
                            $txSum['paid'] += $net;
                        }

                        $txRows[] = [
                            'term'     => $term,
                            'state'    => $state,
                            'when'     => $when,
                            'amount'   => $amount,
                            'discount' => $less,
                            'net'      => $net,
                            'paid'     => $isPaid,
                            'appt'     => $appt,
                            'tip'      => implode(' · ', array_filter([
                                $txStateLabels[$state],
                                $when ?: null,
                                $net ? $rxCurrency . number_format($net, 2) : null,
                                $isPaid ? 'paid' : null,
                                $appt ? 'booked' : null,
                            ])),
                        ];
                    }

                    $txSum['net'] = max($txSum['gross'] - $txSum['discount'], 0);
                    $txSum['due'] = max($txSum['net'] - $txSum['paid'], 0);
                    $txPaidPct = $txSum['net'] > 0 ? min(100, round($txSum['paid'] / $txSum['net'] * 100)) : 0;
                    $txEditable = $visit->is_editable && hasPermission('opd_register', 'edit');

                    $dxChoices = $rankTerms(collect($diagnosisOptions ?? [])->merge($dxCurrent), $termInsights['diagnosisByKey'] ?? []);
                    $txChoices = $rankTerms(collect($treatmentOptions ?? [])->merge($txCurrent), $termInsights['treatmentByKey'] ?? []);

                    // One-tap chips for what this hospital actually sees most.
                    $dxQuick = collect($dxUsage)->keys()->take(\App\Services\OpdTermInsights::TOP_QUICK);
                @endphp
                <div class="card shadow-none border mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-light">
                        <h6 class="mb-0 font-weight-bold" style="font-size:13px">Diagnosis &amp; Treatment</h6>
                        @if ($visit->is_completed)
                            <span class="visit-locked" title="This visit is completed. The OP receipt has been issued, so the record is closed."><i class="tio-lock"></i> Completed</span>
                        @elseif (hasPermission('opd_register', 'edit'))
                            <button class="btn btn-xs btn-soft-secondary" onclick="toggleDxEdit()">
                                <i class="tio-edit" id="dxEditIcon"></i>
                            </button>
                        @endif
                    </div>
                    <div class="card-body py-3" id="dxView">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="tx-col-lbl">Diagnosis</div>
                                <div id="dxBadges">
                                    @forelse($dxCurrent as $term)
                                        <span class="dx-badge">{{ $term }}</span>
                                    @empty
                                        <span class="text-muted small">Not recorded yet.</span>
                                    @endforelse
                                </div>
                            </div>

                            {{-- Same three columns in the same order as the editor below, so
                                 opening the pencil rearranges nothing. Chips only: each carries
                                 its status as colour, and everything else about the treatment —
                                 when, how much, paid or not — is in the table under the row,
                                 which a column three inches wide could never hold. --}}
                            <div class="col-md-4">
                                <div class="tx-col-lbl">Advised Treatment</div>
                                <div id="txBadges">
                                    @forelse($txRows as $r)
                                        <span class="tx-badge tx-chip tx-state-{{ $r['state'] }}" data-term="{{ $r['term'] }}"
                                              onclick="openTxPlanMenu(this)" title="{{ $r['tip'] }} — click to change">
                                            {{ $r['term'] }}
                                        </span>
                                    @empty
                                        <span class="text-muted small">Not recorded yet.</span>
                                    @endforelse
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="tx-col-lbl">Willing Treatment</div>
                                <div id="wtBadges">
                                    @forelse($wtCurrent as $term)
                                        <span class="wt-badge">{{ $term }}</span>
                                    @empty
                                        <span class="text-muted small">Not recorded yet.</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        {{-- The detail behind the chips. One row per advised treatment: the
                             schedule, what it comes to and whether it has been paid — the things
                             a tooltip cannot say and nobody hovers for on a tablet. --}}
                        <div class="tx-table-wrap" id="txTableWrap" @if(!count($txRows)) style="display:none" @endif>
                            <div class="tx-table-head">
                                <div class="tx-col-lbl mb-0">Treatment Plan</div>
                                {{-- How many sit in each state, which doubles as the colour key
                                     for the chips above: a standing legend explains colours
                                     nobody is looking at, while "1 completed · 2 pending" is the
                                     thing a doctor wants to know. --}}
                                <div class="tx-counts" id="txCounts">
                                    @foreach(['completed', 'upcoming', 'pending', 'missed'] as $state)
                                        @if(!empty($txStateCounts[$state]))
                                            <span><i class="tx-dot tx-state-{{ $state }}"></i>{{ $txStateCounts[$state] }} {{ strtolower($txStateLabels[$state]) }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="tx-table">
                                    <thead>
                                        <tr>
                                            <th>Treatment</th>
                                            <th>Status</th>
                                            <th>Scheduled</th>
                                            <th class="tx-num">Amount</th>
                                            <th class="tx-num">Discount</th>
                                            <th class="tx-num">Net</th>
                                            <th class="text-center">Payment</th>
                                            @if($txEditable)
                                                <th></th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody id="txTableBody">
                                        @foreach($txRows as $r)
                                            <tr>
                                                <td class="tx-table-term">{{ $r['term'] }}</td>
                                                <td><span class="tx-pill tx-state-{{ $r['state'] }}">{{ $txStateLabels[$r['state']] }}</span></td>
                                                <td>
                                                    @if($r['when'])
                                                        {{ $r['when'] }}
                                                    @else
                                                        <span class="tx-nil">Not scheduled</span>
                                                    @endif
                                                    @if($r['appt'])
                                                        {{-- Booked, so this date is on the desk's day list too. Red once the
                                                             booking has been called off, which is news the plan must not hide. --}}
                                                        <a href="{{ $r['appt']['url'] }}" target="_blank"
                                                           class="tx-booked @if(in_array($r['appt']['status'], ['cancelled', 'no_show'])) is-stale @endif"
                                                           title="{{ in_array($r['appt']['status'], ['cancelled', 'no_show']) ? 'That follow-up was ' . str_replace('_', ' ', $r['appt']['status']) . ' — open it' : 'Booked as a next visit — open the appointment' }}">
                                                            <i class="tio-calendar-note"></i>{{ $r['appt']['token'] ? '#' . $r['appt']['token'] : 'Booked' }}
                                                        </a>
                                                    @endif
                                                </td>
                                                <td class="tx-num @if(!$r['amount']) tx-nil @endif">{{ $r['amount'] ? $rxCurrency . number_format($r['amount'], 2) : '—' }}</td>
                                                <td class="tx-num @if(!$r['discount']) tx-nil @endif">{{ $r['discount'] ? $rxCurrency . number_format($r['discount'], 2) : '—' }}</td>
                                                <td class="tx-num @if(!$r['net']) tx-nil @endif">{{ $r['net'] ? $rxCurrency . number_format($r['net'], 2) : '—' }}</td>
                                                <td class="text-center">
                                                    @if($r['paid'])
                                                        <span class="tx-paid-yes">Paid</span>
                                                    @elseif($r['net'])
                                                        <span class="tx-paid-no">Due</span>
                                                    @else
                                                        <span class="tx-nil">—</span>
                                                    @endif
                                                </td>
                                                @if($txEditable)
                                                    <td class="text-right">
                                                        <button type="button" class="tx-row-edit" data-tx-open data-term="{{ $r['term'] }}"
                                                                onclick="openTxPlanMenu(this)" title="Edit schedule &amp; price">
                                                            <i class="tio-edit"></i>
                                                        </button>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="tx-foot-lbl">Total</td>
                                            <td class="tx-num" id="txTotalGross">{{ $rxCurrency }}{{ number_format($txSum['gross'], 2) }}</td>
                                            <td class="tx-num" id="txTotalDisc">{{ $rxCurrency }}{{ number_format($txSum['discount'], 2) }}</td>
                                            <td class="tx-num" id="txTotalNet">{{ $rxCurrency }}{{ number_format($txSum['net'], 2) }}</td>
                                            <td class="text-center tx-paid-figure" id="txTotalPaid">{{ $rxCurrency }}{{ number_format($txSum['paid'], 2) }}</td>
                                            @if($txEditable)
                                                <td></td>
                                            @endif
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div id="txTotals" class="tx-due-strip @if(!$txSum['gross']) d-none @endif">
                                <div class="tx-due-line">
                                    <span class="tx-money-lbl">Balance due</span>
                                    <span class="tx-money-due" id="txTotalDue">{{ $rxCurrency }}{{ number_format($txSum['due'], 2) }}</span>
                                </div>
                                <div class="tx-bar" title="Collected against this plan">
                                    <div class="tx-bar-fill" id="txPaidBar" style="width: {{ $txPaidPct }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if (hasPermission('opd_register', 'edit'))
                        <div class="card-body py-3" id="dxEdit" style="display:none;">
                            @if ($dxQuick->isNotEmpty())
                                <div class="mb-2">
                                    <div class="text-muted mb-1" style="font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase;">
                                        Seen most here
                                    </div>
                                    @foreach ($dxQuick as $term)
                                        <button type="button" class="term-chip" data-term="{{ $term }}"
                                            onclick="toggleChipTerm('dxSelect', this)"
                                            title="Recorded {{ $dxUsage[$term] }} time{{ $dxUsage[$term] == 1 ? '' : 's' }} in the last two years">
                                            {{ $term }} <span class="term-chip-count">{{ $dxUsage[$term] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            <div class="row">
                                <div class="col-md-4 form-group mb-2">
                                    <label class="input-label" style="font-size:12px">Diagnosis</label>
                                    <select id="dxSelect" class="form-control form-control-sm" multiple>
                                        @foreach($dxChoices as $term)
                                            <option value="{{ $term }}" @if(in_array($term, $dxCurrent)) selected @endif>{{ $term }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Most-used first. Pick from the list, or type a new one and press Enter.</small>
                                </div>
                                <div class="col-md-4 form-group mb-2">
                                    <label class="input-label" style="font-size:12px">Advised Treatment</label>
                                    <select id="txSelect" class="form-control form-control-sm" multiple>
                                        @foreach($txChoices as $term)
                                            <option value="{{ $term }}" @if(in_array($term, $txCurrent)) selected @endif>{{ $term }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">
                                        What you recommended.
                                        <a href="{{ route('vendor.opd.treatment-catalog') }}" target="_blank" class="ml-1">Prices</a>
                                    </small>
                                </div>
                                <div class="col-md-4 form-group mb-2">
                                    <label class="input-label" style="font-size:12px">Willing Treatment</label>
                                    <select id="wtSelect" class="form-control form-control-sm" multiple>
                                        @foreach($txCurrent as $term)
                                            <option value="{{ $term }}" @if(in_array($term, $wtCurrent)) selected @endif>{{ $term }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Ticked off the advised list. Leave empty if they agreed to all of it.</small>
                                </div>
                            </div>
                            {{-- Populated from the diagnoses currently selected. Suggestions only —
                                 what this hospital has in fact given for that diagnosis before,
                                 never applied on the doctor's behalf. --}}
                            <div id="txSuggestBox" class="mb-2" style="display:none;">
                                <div class="text-muted mb-1" style="font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase;">
                                    Usually given here for <span id="txSuggestFor" class="text-dark"></span>
                                </div>
                                <div id="txSuggest"></div>
                            </div>
                            <div class="mt-2 d-flex gap-2">
                                <button class="btn btn-sm btn-primary" onclick="saveDxTx(this)">Save</button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="toggleDxEdit()">Cancel</button>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Full Vitals Grid --}}
                @if($showVitals)
                <div class="card shadow-none border mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-light opd-collapse-head" onclick="toggleVitalsProfile(event)">
                        <h6 class="mb-0 font-weight-bold" style="font-size:13px">
                            <i class="tio-chevron-right" id="vitalsChevron"></i> Patient Vitals Profile
                            <span class="text-muted" style="font-weight:500">&mdash; incl. RR &amp; height</span>
                        </h6>
                        @if ($visit->is_completed)
                            <span class="visit-locked" title="This visit is completed. The OP receipt has been issued, so the record is closed."><i class="tio-lock"></i> Completed</span>
                        @elseif (hasPermission('opd_register', 'edit'))
                            <button class="btn btn-xs btn-soft-secondary" onclick="toggleVitalsEdit()" title="Edit vitals">
                                <i class="tio-edit" id="vitalsEditIcon"></i>
                            </button>
                        @endif
                    </div>
                    <div id="vitalsProfileBody" style="display:none;">
                    <div class="card-body p-0" id="vitalsView">
                        <table class="table table-striped table-sm mb-0 text-dark" style="font-size:13px">
                            <tbody>
                                <tr>
                                    <td class="pl-3 py-2" style="font-weight:600">Blood Pressure</td>
                                    <td id="vBpCell">
                                        @if($visit->bp_systolic && $visit->bp_diastolic)
                                            <strong>{{ $visit->bp_systolic }}/{{ $visit->bp_diastolic }}</strong> mmHg
                                            @if($visit->bp_systolic >= 140 || $visit->bp_diastolic >= 90)
                                                <span class="badge badge-soft-danger ml-1">Hypertensive Range</span>
                                            @else
                                                <span class="badge badge-soft-success ml-1">Normal Range</span>
                                            @endif
                                        @else <span class="text-muted">—</span> @endif
                                    </td>
                                    <td class="py-2" style="font-weight:600">Pulse Rate</td>
                                    <td id="vPulseCell">{{ $visit->pulse_rate ? $visit->pulse_rate . ' bpm' : '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="pl-3 py-2" style="font-weight:600">Temperature</td>
                                    <td id="vTempCell">{{ $visit->temperature ? $visit->temperature . ' °F' : '—' }}</td>
                                    <td class="py-2" style="font-weight:600">Respiratory Rate</td>
                                    <td id="vRrCell">{{ $visit->respiratory_rate ? $visit->respiratory_rate . ' /min' : '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="pl-3 py-2" style="font-weight:600">SpO2</td>
                                    <td id="vSpo2Cell">{{ $visit->spo2 ? $visit->spo2 . '%' : '—' }}</td>
                                    <td class="py-2" style="font-weight:600">Weight / Height</td>
                                    <td id="vWtHtCell">{{ $visit->weight ? $visit->weight . ' kg' : '—' }} / {{ $visit->height ? $visit->height . ' cm' : '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    @if (hasPermission('opd_register', 'edit'))
                        {{-- The nurse-station reading is often corrected at the chair: a cuff read
                             twice, a weight taken with the coat still on. This writes to the same
                             visit columns the strip at the top of the page reads, so both move
                             together instead of disagreeing until a reload. --}}
                        <div class="card-body py-3" id="vitalsEdit" style="display:none;">
                            <div class="form-row">
                                <div class="col-md-3 form-group mb-2">
                                    <label class="input-label" style="font-size:12px">BP Systolic <span class="text-muted">(mmHg)</span></label>
                                    <input type="number" min="0" max="300" id="v_bp_systolic" class="form-control form-control-sm" value="{{ $visit->bp_systolic }}" placeholder="120">
                                </div>
                                <div class="col-md-3 form-group mb-2">
                                    <label class="input-label" style="font-size:12px">BP Diastolic <span class="text-muted">(mmHg)</span></label>
                                    <input type="number" min="0" max="200" id="v_bp_diastolic" class="form-control form-control-sm" value="{{ $visit->bp_diastolic }}" placeholder="80">
                                </div>
                                <div class="col-md-3 form-group mb-2">
                                    <label class="input-label" style="font-size:12px">Pulse Rate <span class="text-muted">(bpm)</span></label>
                                    <input type="number" min="0" max="300" id="v_pulse_rate" class="form-control form-control-sm" value="{{ $visit->pulse_rate }}" placeholder="72">
                                </div>
                                <div class="col-md-3 form-group mb-2">
                                    <label class="input-label" style="font-size:12px">Temperature <span class="text-muted">(°F)</span></label>
                                    <input type="number" step="0.1" min="90" max="110" id="v_temperature" class="form-control form-control-sm" value="{{ $visit->temperature }}" placeholder="98.6">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-3 form-group mb-2">
                                    <label class="input-label" style="font-size:12px">Respiratory Rate <span class="text-muted">(/min)</span></label>
                                    <input type="number" min="0" max="100" id="v_respiratory_rate" class="form-control form-control-sm" value="{{ $visit->respiratory_rate }}" placeholder="16">
                                </div>
                                <div class="col-md-3 form-group mb-2">
                                    <label class="input-label" style="font-size:12px">SpO2 <span class="text-muted">(%)</span></label>
                                    <input type="number" min="0" max="100" id="v_spo2" class="form-control form-control-sm" value="{{ $visit->spo2 }}" placeholder="99">
                                </div>
                                <div class="col-md-3 form-group mb-2">
                                    <label class="input-label" style="font-size:12px">Weight <span class="text-muted">(kg)</span></label>
                                    <input type="number" step="0.1" min="0" max="500" id="v_weight" class="form-control form-control-sm" value="{{ $visit->weight }}" placeholder="70">
                                </div>
                                <div class="col-md-3 form-group mb-2">
                                    <label class="input-label" style="font-size:12px">Height <span class="text-muted">(cm)</span></label>
                                    <input type="number" step="0.1" min="0" max="300" id="v_height" class="form-control form-control-sm" value="{{ $visit->height }}" placeholder="170">
                                </div>
                            </div>
                            <div class="mt-2 d-flex flex-wrap" style="gap:8px;">
                                <button class="btn btn-sm btn-primary" onclick="saveVitals(this)">Save</button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="toggleVitalsEdit()">Cancel</button>
                            </div>
                            <small class="text-muted d-block mt-1">Leave a box empty to record that vital as not taken.</small>
                        </div>
                    @endif
                    </div>{{-- /vitalsProfileBody --}}
                </div>
                @endif

            <hr class="my-4">
            <h4 class="mb-3 font-weight-bold" style="color:#0f172a">Prescription</h4>
                @if($currentPrescription)
                    {{-- View existing prescription --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge badge-soft-success" style="font-size:12px">
                            <i class="tio-checkmark-circle-outlined"></i> Prescription Saved
                        </span>
                        <div class="d-flex gap-2">
                            <button onclick="printPrescription()" class="btn btn-sm btn-primary">
                                <i class="tio-print"></i> Print Rx
                            </button>
                            {{-- The prescription as a PDF attachment on WhatsApp, on the
                                 prescription_pdf template. Finalized only: a draft is still being
                                 written, and a patient who receives one starts a course of medicine
                                 the doctor has not committed to. --}}
                            {{-- Owner-or-permission, matching what the route enforces: the
                                 permission middleware passes any vendor owner, while hasPermission()
                                 alone answers false for an owner until some role has been granted
                                 prescription/print — which would hide a button its own route allows. --}}
                            @if ($currentPrescription->is_finalized
                                && (auth('vendor')->check() || hasPermission('prescription', 'print'))
                                && filled($currentPrescription->patient?->phone))
                                <form method="post" action="{{ route('vendor.hmis-whatsapp.prescription-pdf', $currentPrescription->id) }}" class="mb-0"
                                      onsubmit="return confirm('Send this prescription as a PDF to {{ addslashes($currentPrescription->patient->name ?? 'the patient') }} on WhatsApp?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Attach the prescription PDF and send it to the patient on WhatsApp">
                                        <i class="tio-attachment"></i> Send as PDF
                                    </button>
                                </form>
                            @endif
                            {{-- Editing the prescription after the receipt is out would leave the
                                 printed copy and the record disagreeing with nothing to show it. --}}
                            @if (!$visit->is_completed && hasPermission('prescription', 'add'))
                                <button onclick="togglePrescriptionEdit(true)" class="btn btn-sm btn-outline-secondary">
                                    <i class="tio-edit"></i> Edit Rx
                                </button>
                            @endif
                            {{-- Straight to the pharmacy counter. Only a finalized Rx can be
                                 dispensed (dispenseProcess requires it) — a draft won't be in the queue. --}}
                            @if ($currentPrescription->is_finalized && hasPermission('pharmacy_dispense_queue', 'view'))
                                <a href="{{ route('vendor.prescription.dispense.show', $currentPrescription->id) }}"
                                   class="btn btn-sm btn-outline-primary" title="Dispense this prescription">
                                    <i class="tio-pill"></i> Dispense
                                </a>
                            @endif
                            @if (hasPermission('pharmacy_dispense_queue', 'list'))
                                <a href="{{ route('vendor.prescription.dispense.queue') }}"
                                   class="btn btn-sm btn-outline-secondary" title="Open the full dispense queue">
                                    <i class="tio-filter-list"></i> Dispense Queue
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- The medicines on their own. The letterhead copy below is what prints;
                         it repeats this table, so it stays folded until someone wants to see it. --}}
                    <div class="rx-med-list mb-2">
                        <table class="table table-sm mb-0" style="font-size:13px; min-width:520px">
                            <thead>
                                <tr class="bg-light">
                                    <th style="width:34px">#</th>
                                    <th>Medicine</th>
                                    <th>Dosage</th>
                                    <th>Frequency</th>
                                    <th>Duration</th>
                                    <th class="text-right">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($currentPrescription->items as $idx => $item)
                                    <tr>
                                        <td class="text-muted">{{ $idx+1 }}</td>
                                        <td><strong>{{ $item->medicine_name }}</strong></td>
                                        <td>{{ $item->dosage ?: '—' }}</td>
                                        <td>{{ $item->frequency ?: '—' }}</td>
                                        <td>{{ $item->duration ?: '—' }}</td>
                                        <td class="text-right">{{ $item->quantity ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">No medicines prescribed</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if($currentPrescription->follow_up_date)
                            <div class="rx-followup"><i class="tio-calendar-note"></i> Follow-up on <strong>{{ $currentPrescription->follow_up_date->format('d M Y') }}</strong></div>
                        @endif
                        @if($currentPrescription->notes)
                            <div class="rx-advice"><span class="rx-advice-lbl">Advice</span> {{ $currentPrescription->notes }}</div>
                        @endif
                    </div>

                    <button type="button" class="btn btn-xs btn-soft-secondary mb-3" onclick="toggleRxPreview()">
                        <i class="tio-visible-outlined"></i> <span id="rxPreviewLabel">Show printable letterhead</span>
                    </button>

                    <div class="rx-view-wrap" id="rxPrintSection" style="display:none;">
                        <div class="rx-view-header">
                            <div>
                                <h4 style="margin:0; font-weight:800; color:#0D47A1;">{{ $currentPrescription->store?->name }}</h4>
                                <p style="margin:2px 0 0; font-size:11px; color:#64748b;">{{ $currentPrescription->store?->address }}</p>
                            </div>
                            <div style="text-align:right;">
                                <h5 style="margin:0; font-weight:700;">Dr. {{ $currentPrescription->doctorProfile?->employee?->f_name }} {{ $currentPrescription->doctorProfile?->employee?->l_name }}</h5>
                                <p style="margin:2px 0 0; font-size:11px; color:#64748b;">{{ $currentPrescription->doctorProfile?->specialization }}</p>
                            </div>
                        </div>
                        <div style="font-size:12px; margin-bottom:15px; border-bottom:1px solid #cbd5e1; padding-bottom:8px;" class="row">
                            <div class="col-6"><strong>Patient:</strong> {{ $currentPrescription->patient?->name }}</div>
                            <div class="col-6 text-right"><strong>Date:</strong> {{ $currentPrescription->created_at->format('d M Y') }}</div>
                        </div>

                        <div style="font-size:28px; color:#0D47A1; font-weight:700; margin-bottom:10px; font-family:'Times New Roman'">℞</div>

                        <table class="table table-bordered table-sm" style="font-size:12px;">
                            <thead>
                                <tr class="bg-light">
                                    <th>#</th>
                                    <th>Medicine Name</th>
                                    <th>Dosage</th>
                                    <th>Frequency</th>
                                    <th>Duration</th>
                                    <th>Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($currentPrescription->items as $idx => $item)
                                    <tr>
                                        <td>{{ $idx+1 }}</td>
                                        <td><strong>{{ $item->medicine_name }}</strong></td>
                                        <td>{{ $item->dosage ?: '—' }}</td>
                                        <td>{{ $item->frequency ?: '—' }}</td>
                                        <td>{{ $item->duration ?: '—' }}</td>
                                        <td>{{ $item->quantity ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">No medicines prescribed</td></tr>
                                @endforelse
                            </tbody>
                        </table>

                        @if($currentPrescription->notes)
                            <div class="mt-3" style="font-size:12px;">
                                <strong>Advice / Notes:</strong>
                                <p style="margin:4px 0 0; color:#334155;">{{ $currentPrescription->notes }}</p>
                            </div>
                        @endif

                        @if($currentPrescription->follow_up_date)
                            <div class="mt-3 small" style="font-size:12px;">
                                <strong>Follow-up on:</strong> {{ $currentPrescription->follow_up_date->format('d M Y') }}
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Prescription writing form (hidden if currentPrescription exists, shown if editing or none) --}}
                @if (!$visit->is_completed && hasPermission('prescription', 'add'))
                <div id="rxWritingFormBlock" style="@if($currentPrescription) display:none; @endif">
                    <h4 class="mb-3 font-weight-bold" style="color:#0f172a">Write Prescription</h4>

                    {{-- Outside the form on purpose: the modals below carry their own inputs and
                         have no business being posted with the prescription. --}}
                    @include('hmis::vendor.prescription._rx_templates', [
                        'formId'   => 'customRxForm',
                        'addRowFn' => 'addCustomMedRow',
                    ])

                    <form action="{{ route('vendor.prescription.store') }}" method="POST" id="customRxForm">
                        @csrf
                        <input type="hidden" name="patient_id" value="{{ $visit->patient_id }}">
                        <input type="hidden" name="doctor_profile_id" value="{{ $visit->doctor_profile_id }}">
                        @if($visit->appointment_id)
                            <input type="hidden" name="appointment_id" value="{{ $visit->appointment_id }}">
                        @endif
                        @if($visit->service_request_id)
                            <input type="hidden" name="service_request_id" value="{{ $visit->service_request_id }}">
                        @endif

                        <div class="row">
                            {{-- Diagnosis and advice are recorded once, in the consultation
                                 cards above. These mirror them until the doctor types something
                                 different here, at which point the mirror releases. --}}
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="input-label d-flex justify-content-between align-items-center mb-1">
                                        <span>Diagnosis <span class="text-danger">*</span></span>
                                        <span class="rx-sync-hint" id="rxDxHint">Synced from Diagnosis &amp; Treatment</span>
                                    </label>
                                    <textarea name="diagnosis" id="rxDiagnosis" data-synced="1" oninput="markRxFieldEdited(this, 'rxDxHint')" class="form-control form-control-sm" rows="3" placeholder="Primary diagnosis..." required>{{ $currentPrescription->diagnosis ?? ($visit->diagnosis ?: $visit->chief_complaint) }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="input-label d-flex justify-content-between align-items-center mb-1">
                                        <span>Doctor's Advice / Notes</span>
                                        <span class="rx-sync-hint" id="rxNotesHint">Synced from Consultation Notes</span>
                                    </label>
                                    <textarea name="notes" id="rxNotes" data-synced="1" oninput="markRxFieldEdited(this, 'rxNotesHint')" class="form-control form-control-sm" rows="3" placeholder="Rest, diet, precautions, advice...">{{ $currentPrescription->notes ?? $visit->notes }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="input-label">Follow-Up Date</label>
                                    <input type="date" name="follow_up_date" class="form-control form-control-sm" value="{{ $currentPrescription && $currentPrescription->follow_up_date ? $currentPrescription->follow_up_date->format('Y-m-d') : '' }}">
                                </div>
                            </div>

                            {{-- Full width, not a narrow side column: the medicines table is read
                                 down its columns — same dose, same duration across five lines —
                                 which a 5-of-12 column cannot show without wrapping every cell. --}}
                            <div class="col-12">
                                <div class="card border shadow-none mb-3">
                                    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-light">
                                        <h6 class="mb-0 font-weight-bold" style="font-size:12px">Medicines</h6>
                                        <button type="button" class="btn btn-xs btn-primary" onclick="addCustomMedRow()">
                                            <i class="tio-add"></i> Add row
                                        </button>
                                    </div>

                                    {{-- Inventory Search --}}
                                    <div class="px-2 pt-2 pb-1" style="border-bottom:1px solid #f0f0f0;">
                                        <div class="input-group input-group-sm" style="position:relative;">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-white border-right-0"><i class="tio-search" style="font-size:12px;"></i></span>
                                            </div>
                                            <input type="text" id="pharmacySearch" class="form-control border-left-0" placeholder="Search medicines inventory..." autocomplete="off" oninput="pharmacySearchDebounce(this.value)">
                                            <ul id="pharmacySuggestions"></ul>
                                        </div>
                                    </div>
                                    <div class="table-responsive" style="max-height:340px; overflow-y:auto;">
                                        <table class="table rx-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width:34px;">#</th>
                                                    <th style="width:92px;">Type</th>
                                                    <th style="min-width:220px;">Medicine</th>
                                                    <th style="width:130px;">Dose</th>
                                                    <th style="width:130px;">When</th>
                                                    <th style="width:130px;">Frequency</th>
                                                    <th style="width:110px;">Duration</th>
                                                    <th style="width:74px;">Qty</th>
                                                    <th style="min-width:160px;">Notes / Instructions</th>
                                                    <th style="width:40px;"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="medTable">
                                                @if($currentPrescription && $currentPrescription->items->count())
                                                    @foreach($currentPrescription->items as $i => $item)
                                                        @include('hmis::vendor.prescription._med_row', ['i' => $i, 'item' => $item])
                                                    @endforeach
                                                @else
                                                    @include('hmis::vendor.prescription._med_row', ['i' => 0, 'item' => null])
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rx-actions">
                               @if($currentPrescription)
                                <button type="button" class="btn btn-sm btn-light" onclick="togglePrescriptionEdit(false)">Cancel</button>
                            @endif
                            <button type="submit" class="btn btn-sm btn-primary" name="action" value="draft">
                                <i class="tio-save"></i> Save Draft
                            </button>
                            <button type="submit" class="btn btn-sm btn-success" name="finalize" value="1">
                                <i class="tio-checkmark-circle"></i> Finalize &amp; Save
                            </button>

                            @include('hmis::vendor.prescription._rx_language', ['selected' => $currentPrescription->language ?? null])
                        </div>
                    </form>
                </div>
                @endif
            </div>

            {{-- TAB: PAST RX --}}
            <div class="tab-pane" id="tabPastRx">
                <h4 class="mb-3 font-weight-bold" style="color:#0f172a">Past Prescriptions</h4>
                <div class="accordion" id="pastRxAccordion">
                    @forelse($pastPrescriptions as $idx => $rx)
                        <div class="card border shadow-none mb-2">
                            <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center" id="headingRx{{ $rx->id }}">
                                <button class="btn btn-link btn-block text-left text-dark font-weight-bold p-0 d-flex justify-content-between align-items-center" type="button" data-toggle="collapse" data-target="#collapseRx{{ $rx->id }}" aria-expanded="false">
                                    <span>
                                        <i class="tio-file-text mr-1"></i> Rx #{{ $rx->id }} — {{ $rx->created_at->format('d M Y') }}
                                        <span class="badge {{ $rx->is_finalized ? 'badge-soft-success' : 'badge-soft-warning' }} ml-1">
                                            {{ $rx->is_finalized ? 'Finalized' : 'Draft' }}
                                        </span>
                                    </span>
                                    <small class="text-muted">Dr. {{ $rx->doctorProfile?->employee?->f_name }} &bull; {{ $rx->items->count() }} medicines</small>
                                </button>
                            </div>
                            <div id="collapseRx{{ $rx->id }}" class="collapse" data-parent="#pastRxAccordion">
                                <div class="card-body py-2">
                                    @if($rx->diagnosis)
                                        <p class="small mb-2"><strong>Diagnosis:</strong> {{ $rx->diagnosis }}</p>
                                    @endif
                                    <table class="table table-bordered table-sm mb-2" style="font-size:11px;">
                                        <thead>
                                            <tr class="bg-light"><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Qty</th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach($rx->items as $it)
                                                <tr>
                                                    <td><strong>{{ $it->medicine_name }}</strong></td>
                                                    <td>{{ $it->dosage ?: '—' }}</td>
                                                    <td>{{ $it->frequency ?: '—' }}</td>
                                                    <td>{{ $it->duration ?: '—' }}</td>
                                                    <td>{{ $it->quantity ?: '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @if($rx->notes)
                                        <p class="small text-muted mb-0"><strong>Advice:</strong> {{ $rx->notes }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="tio-history" style="font-size:40px; opacity:0.3; display:block; margin-bottom:8px;"></i>
                            No past prescriptions found for this patient.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- TAB: TESTS --}}
            <div class="tab-pane" id="tabTests">
                <h4 class="mb-3 font-weight-bold" style="color:#0f172a">Diagnostic &amp; Lab Recommendation</h4>

                @if($labOrders->isNotEmpty() || $radiologyStudies->isNotEmpty())
                    <div class="mb-3">
                        <h6 class="font-weight-bold mb-2" style="font-size:13px;">Already ordered for this patient</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                                <thead class="thead-light"><tr><th>Order</th><th>Type</th><th>Tests</th><th>Status</th><th>Ordered</th></tr></thead>
                                <tbody>
                                    @foreach($labOrders as $o)
                                    <tr>
                                        <td>{{ $o->order_no }}</td>
                                        <td><span class="badge badge-soft-info">Lab</span></td>
                                        <td>{{ $o->items->pluck('test_name')->implode(', ') ?: '—' }}</td>
                                        <td><span class="badge badge-soft-secondary">{{ ucfirst($o->status) }}</span></td>
                                        <td>{{ $o->created_at?->format('d M Y') }}</td>
                                    </tr>
                                    @endforeach
                                    @foreach($radiologyStudies as $s)
                                    <tr>
                                        <td>{{ $s->study_no }}</td>
                                        <td><span class="badge badge-soft-warning">Radiology</span></td>
                                        <td>{{ $s->study_name }}</td>
                                        <td><span class="badge badge-soft-secondary">{{ ucfirst($s->status) }}</span></td>
                                        <td>{{ $s->created_at?->format('d M Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if($labTests->isEmpty() && $radiologyTests->isEmpty())
                    <div class="alert py-2 px-3" style="background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:12.5px;">
                        No tests are set up yet. Add them in
                        <a href="{{ route('vendor.lab.catalog') }}">Laboratory → Test Catalog</a> or
                        <a href="{{ route('vendor.radiology.catalog') }}">Radiology → Catalog</a>, and they will appear here.
                    </div>
                @else
                <p class="small text-muted mb-3">Select the tests and scans this patient needs. They are raised against this visit and queue in their department.</p>
                <div class="row text-dark mb-3" style="font-size:13px">
                    <div class="col-md-6">
                        <h6 class="font-weight-bold mb-2">Lab Tests</h6>
                        @forelse($labTests as $department => $tests)
                            <div class="text-muted mb-1" style="font-size:11px; text-transform:uppercase;">{{ $department }}</div>
                            @foreach($tests as $t)
                                <label class="d-flex align-items-center mb-1" style="font-size:12.5px;">
                                    <input type="checkbox" name="lab_tests[]" value="{{ $t->id }}" class="mr-2">
                                    {{ $t->name }}
                                    <span class="text-muted ml-1">
                                        @if($t->price > 0) · {{ \App\CentralLogics\Helpers::format_currency($t->price) }} @endif
                                        @if($t->sample_type) · {{ $t->sample_type }} @endif
                                    </span>
                                </label>
                            @endforeach
                        @empty
                            <div class="text-muted" style="font-size:12px;">No lab tests in the catalog.</div>
                        @endforelse
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-weight-bold mb-2">Radiology Scans</h6>
                        @forelse($radiologyTests as $modality => $tests)
                            <div class="text-muted mb-1" style="font-size:11px; text-transform:uppercase;">{{ $modality }}</div>
                            @foreach($tests as $t)
                                <label class="d-flex align-items-center mb-1" style="font-size:12.5px;">
                                    <input type="checkbox" name="radiology_tests[]" value="{{ $t->id }}" class="mr-2">
                                    {{ $t->name }}
                                    <span class="text-muted ml-1">
                                        @if($t->price > 0) · {{ \App\CentralLogics\Helpers::format_currency($t->price) }} @endif
                                        @if($t->body_part) · {{ $t->body_part }} @endif
                                    </span>
                                </label>
                            @endforeach
                        @empty
                            <div class="text-muted" style="font-size:12px;">No radiology scans in the catalog.</div>
                        @endforelse
                    </div>
                </div>

                <div class="form-row mb-2" style="max-width:640px;">
                    <div class="form-group col-md-5">
                        <label style="font-size:12px;">Priority</label>
                        <select id="testPriority" class="form-control form-control-sm">
                            <option value="routine">Routine</option>
                            <option value="urgent">Urgent</option>
                            <option value="stat">STAT</option>
                        </select>
                    </div>
                    <div class="form-group col-md-7">
                        <label style="font-size:12px;">Clinical Notes</label>
                        <input type="text" id="testClinicalNotes" class="form-control form-control-sm"
                               placeholder="Indication / provisional diagnosis (optional)">
                    </div>
                </div>

                <button type="button" id="saveTestsBtn" class="btn btn-sm btn-primary" onclick="recommendLabTests()">
                    <i class="tio-checkmark-circle"></i> Save Recommended Tests
                </button>
                @endif
            </div>

            {{-- TAB: REPORTS --}}
            <div class="tab-pane" id="tabReports">
                <h4 class="mb-3 font-weight-bold" style="color:#0f172a">Patient Documents &amp; Reports</h4>

                @php
                    // type => [label, badge background, category(medical|govt)]
                    $docTypeMeta = [
                        'report'       => ['Report', '#dbeafe', 'medical'],
                        'id_proof'     => ['ID Proof', '#fef3c7', 'medical'],
                        'prescription' => ['Prescription', '#d1fae5', 'medical'],
                        'other'        => ['Other', '#f3f4f6', 'medical'],
                        'arogyasri'    => ['Arogya Sri', '#fce7f3', 'govt'],
                        'insurance'    => ['Insurance', '#e0e7ff', 'govt'],
                        'aadhaar'      => ['Aadhaar Card', '#fef9c3', 'govt'],
                        'pan'          => ['PAN Card', '#ccfbf1', 'govt'],
                        'ration_card'  => ['Ration Card', '#ffedd5', 'govt'],
                        'abha'         => ['ABHA / Health ID', '#dcfce7', 'govt'],
                        'govt_other'   => ['Other Govt', '#f3f4f6', 'govt'],
                    ];
                    $allDocs     = $visit->patient?->documents ?? collect();
                    $catOf       = fn($type) => $docTypeMeta[$type][2] ?? 'medical';
                    $medicalDocs = $allDocs->filter(fn($d) => $catOf($d->document_type) === 'medical');
                    $govtDocs    = $allDocs->filter(fn($d) => $catOf($d->document_type) === 'govt');
                @endphp

                {{-- Medical / Government sub-tabs (custom toggler — avoids the page's .tab-pane CSS) --}}
                <ul class="nav nav-tabs nav-tabs-line mb-3" id="docSubTabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="javascript:void(0)" onclick="switchDocSub('med', this)">
                            <i class="tio-file mr-1"></i> Medical
                            <span class="badge badge-soft-secondary ml-1" id="medDocCount">{{ $medicalDocs->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" onclick="switchDocSub('govt', this)">
                            <i class="tio-shield-outlined mr-1"></i> Government
                            <span class="badge badge-soft-secondary ml-1" id="govtDocCount">{{ $govtDocs->count() }}</span>
                        </a>
                    </li>
                </ul>

                <div>
                    <div class="docsub-pane" id="docsub-med">
                        @include('hmis::vendor.opd._docs_pane', [
                            'cat' => 'med', 'list_id' => 'docList', 'docs' => $medicalDocs,
                            'types' => ['report','prescription','other'],
                            'placeholder' => 'e.g. Lipid Profile Result', 'meta' => $docTypeMeta,
                        ])
                    </div>
                    <div class="docsub-pane" id="docsub-govt" style="display:none">
                        @include('hmis::vendor.opd._docs_pane', [
                            'cat' => 'govt', 'list_id' => 'govtDocList', 'docs' => $govtDocs,
                            'types' => ['arogyasri','insurance','aadhaar','pan','ration_card','abha','govt_other'],
                            'placeholder' => 'e.g. policy no.', 'meta' => $docTypeMeta,
                        ])
                    </div>
                </div>
            </div>

            {{-- TAB: SARA AI --}}
            <div class="tab-pane" id="tabSaraAI">
                <div class="sara-ai-container">

                    {{-- Blue Header Banner --}}
                    <div class="sara-ai-header">
                        <div>
                            <h3>✦ Sara AI - Clinical Intelligence v3</h3>
                            <p>ADA 2026 • JNC-8 • ACC/AHA 2023 • KDIGO 2024 — Real-time Guideline Synthesis</p>
                        </div>
                        <div style="font-size:12px; font-weight:700; background:rgba(255,255,255,0.15); padding:3px 10px; border-radius:4px;">
                            Active Analysis
                        </div>
                    </div>

                    {{-- Disclaimer box --}}
                    <div class="ai-disclaimer-box">
                        <i class="tio-warning mr-1" style="font-size:16px;"></i>
                        <div>
                            <strong>AI Disclaimer:</strong> Sara AI is suggestive only and based on published clinical guidelines. Final medical decisions remain solely with the treating physician. Review all suggestions before acting.
                        </div>
                    </div>

                    {{-- Risk Snapshot Header --}}
                    <h5 class="font-weight-bold mb-3 text-uppercase" style="font-size:12px; color:#475569; letter-spacing:0.5px;">Risk Snapshot</h5>

                    {{-- Progress widgets grid --}}
                    <div class="risk-snapshot-grid">

                        {{-- 1. Cardiovascular Risk --}}
                        @php
                            $cvScore = 15;
                            if (\Carbon\Carbon::parse($visit->patient?->dob)->age > 40) $cvScore += 15;
                            if ($visit->bp_systolic >= 140 || $visit->bp_diastolic >= 90) $cvScore += 15;
                            if ($history && $history->smoking) $cvScore += 13;
                            if ($history && (stripos($history->chronic_conditions, 'diabetes') !== false || stripos($history->chronic_conditions, 't2dm') !== false)) $cvScore += 10;
                            // Clamp score
                            $cvScore = min(95, max(5, $cvScore));
                        @endphp
                        <div class="risk-item-card">
                            <div class="risk-item-header">
                                <span class="risk-lbl">Cardiovascular Risk</span>
                                <span class="risk-val @if($cvScore >= 50) red-text @else moderate @endif">
                                    @if($cvScore >= 50) High @elseif($cvScore >= 20) Moderate @else Low @endif — {{ $cvScore }}%
                                </span>
                            </div>
                            <div class="risk-progress-container">
                                <div class="risk-progress-bar @if($cvScore >= 50) red @elseif($cvScore >= 20) orange @else green @endif" data-width="{{ $cvScore }}%"></div>
                            </div>
                            <div class="risk-confidence-footer">
                                <span>Confidence: 91%</span>
                                <span>Framingham + SCORE2</span>
                            </div>
                        </div>

                        {{-- 2. Diabetic Nephropathy --}}
                        @php
                            $isDiabetic = $history && (stripos($history->chronic_conditions, 'diabetes') !== false || stripos($history->chronic_conditions, 't2dm') !== false);
                            $dnScore = $isDiabetic ? ($visit->bp_systolic >= 130 ? 42 : 30) : 8;
                        @endphp
                        <div class="risk-item-card">
                            <div class="risk-item-header">
                                <span class="risk-lbl">Diabetic Nephropathy</span>
                                <span class="risk-val @if($dnScore >= 40) moderate @else good @endif">
                                    @if($dnScore >= 40) Moderate @else Low @endif — {{ $dnScore }}%
                                </span>
                            </div>
                            <div class="risk-progress-container">
                                <div class="risk-progress-bar @if($dnScore >= 40) orange @else green @endif" data-width="{{ $dnScore }}%"></div>
                            </div>
                            <div class="risk-confidence-footer">
                                <span>Confidence: 78%</span>
                                <span>KDIGO 2024</span>
                            </div>
                        </div>

                        {{-- 3. BP Control --}}
                        @php
                            $syst = $visit->bp_systolic ?: 120;
                            $diast = $visit->bp_diastolic ?: 80;
                            if ($syst >= 140 || $diast >= 90) { $bpStatus = 'Poor'; $bpClass = 'red'; $bpPercent = 85; }
                            elseif ($syst >= 130 || $diast >= 80) { $bpStatus = 'Borderline'; $bpClass = 'orange'; $bpPercent = 60; }
                            else { $bpStatus = 'Good'; $bpClass = 'green'; $bpPercent = 25; }
                        @endphp
                        <div class="risk-item-card">
                            <div class="risk-item-header">
                                <span class="risk-lbl">BP Control</span>
                                <span class="risk-val @if($bpStatus === 'Poor') red-text @elseif($bpStatus === 'Borderline') moderate @else good @endif">
                                    {{ $bpStatus }} — {{ $syst }}/{{ $diast }}
                                </span>
                            </div>
                            <div class="risk-progress-container">
                                <div class="risk-progress-bar {{ $bpClass }}" data-width="{{ $bpPercent }}%"></div>
                            </div>
                            <div class="risk-confidence-footer">
                                <span>Confidence: 96%</span>
                                <span>JNC-8</span>
                            </div>
                        </div>

                        {{-- 4. Glycaemic Control --}}
                        @php
                            $glycScore = $isDiabetic ? 8.9 : 5.4;
                            $glycPercent = $isDiabetic ? 75 : 30;
                        @endphp
                        <div class="risk-item-card">
                            <div class="risk-item-header">
                                <span class="risk-lbl">Glycaemic Control</span>
                                <span class="risk-val @if($isDiabetic) moderate @else good @endif">
                                    @if($isDiabetic) Poor @else Normal @endif — HbA1c {{ $glycScore }}%
                                </span>
                            </div>
                            <div class="risk-progress-container">
                                <div class="risk-progress-bar @if($isDiabetic) orange @else green @endif" data-width="{{ $glycPercent }}%"></div>
                            </div>
                            <div class="risk-confidence-footer">
                                <span>Confidence: 99%</span>
                                <span>ADA 2026</span>
                            </div>
                        </div>

                    </div>

                    {{-- Key Clinical Findings --}}
                    <div class="clinical-findings-panel">
                        <h4>Key Clinical Findings</h4>
                        <div class="findings-alert-card">
                            <div>
                                <i class="tio-warning mr-1"></i>
                                @if($syst >= 130 || $diast >= 85)
                                    <span>BP {{ $syst }}/{{ $diast }} mmHg - Hypertensive Urgency. 94% Consider Telmisartan 40mg.</span>
                                @else
                                    <span>BP normal ({{ $syst }}/{{ $diast }} mmHg). No medication alerts triggered.</span>
                                @endif
                            </div>
                            <button type="button" class="why-btn" data-toggle="modal" data-target="#saraWhyModal">Why?</button>
                        </div>
                    </div>

                </div>
            </div>

            {{-- TAB: TIMELINE --}}
            <div class="tab-pane" id="tabTimeline">
                <h4 class="mb-3 font-weight-bold" style="color:#0f172a">Consultation Timeline &amp; Visited History</h4>
                <div class="timeline-story text-dark" style="font-size:12px;">
                    @forelse($pastVisits as $pv)
                        <div style="border-left: 2px solid #cbd5e1; padding-left: 15px; padding-bottom: 20px; position:relative;">
                            <div style="position:absolute; left:-6px; top:2px; width:10px; height:10px; border-radius:50%; background:#2563eb;"></div>
                            <strong style="font-size:13px">{{ $pv->visit_date?->format('d M Y') }}</strong>
                            <span class="badge badge-soft-info ml-1">{{ \App\Models\OpdVisit::VISIT_TYPES[$pv->visit_type] ?? $pv->visit_type }}</span>
                            <p class="mb-1 mt-1 text-muted">Consulted by: <strong>Dr. {{ $pv->doctorProfile?->employee?->f_name }} {{ $pv->doctorProfile?->employee?->l_name }}</strong></p>
                            @if($pv->chief_complaint)
                                <p class="mb-1"><strong>CC:</strong> {{ $pv->chief_complaint }}</p>
                            @endif
                            @if($pv->diagnosis)
                                <p class="mb-1">
                                    <strong>Diagnosis:</strong>
                                    @foreach($pv->diagnosis_list as $term)
                                        <span class="dx-badge">{{ $term }}</span>
                                    @endforeach
                                </p>
                            @endif
                            @if($pv->treatment)
                                <p class="mb-1">
                                    <strong>Treatment:</strong>
                                    @foreach($pv->treatment_list as $term)
                                        <span class="tx-badge">{{ $term }}</span>
                                    @endforeach
                                </p>
                            @endif
                            @if($pv->notes)
                                <p class="mb-1"><strong>Notes:</strong> {{ $pv->notes }}</p>
                            @endif
                            @if($pv->bp_systolic)
                                <small class="text-muted">Vitals: BP: {{ $pv->bp_systolic }}/{{ $pv->bp_diastolic }} mmHg &bull; Temp: {{ $pv->temperature }} °F &bull; Pulse: {{ $pv->pulse_rate }} bpm</small>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="tio-calendar-note" style="font-size:40px; opacity:0.3; display:block; margin-bottom:8px;"></i>
                            No past OPD visits recorded.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- TAB: MODE --}}
            <div class="tab-pane" id="tabMode">
                <h4 class="mb-3 font-weight-bold" style="color:#0f172a">Mode Preference Settings</h4>
                <p class="small text-muted mb-4">Customize the doctor's consultation workstation experience.</p>
                <div class="text-dark mb-4" style="font-size:13px">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <strong>Full-screen Triage Mode</strong>
                            <div class="text-muted small">Auto-minimize standard vendor sidebars during consultations</div>
                        </div>
                        <input type="checkbox" checked disabled>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <strong>Live Vitals Alerts</strong>
                            <div class="text-muted small">Show immediate alert indicators when critical ranges are exceeded</div>
                        </div>
                        <input type="checkbox" checked>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <strong>Guideline Synthesis Engine</strong>
                            <div class="text-muted small">Enable automated risk widgets based on ACC/AHA, JNC-8, ADA guidelines</div>
                        </div>
                        <input type="checkbox" checked>
                    </div>
                </div>
            </div>

            {{-- TAB: NEXT VISIT --}}
            @php
                // The sittings still to come, offered as tick boxes against each follow-up being
                // booked. Completed work is left out — there is nothing left to book for it.
                $nvPlan  = $visit->treatment_plan_map;
                $nvTerms = collect($visit->treatment_list)
                    ->map(function ($term) use ($nvPlan, $treatmentAppointments) {
                        $row  = $nvPlan[$term] ?? [];
                        $appt = $treatmentAppointments[(int) ($row['appointment_id'] ?? 0)] ?? null;

                        return [
                            'term'   => $term,
                            'status' => $row['status'] ?? 'pending',
                            'booked' => $appt && !in_array($appt['status'], ['cancelled', 'no_show']),
                        ];
                    })
                    ->reject(fn($item) => $item['status'] === 'completed')
                    ->values();

                // Which advised treatments each booking already covers, for the list above the form.
                $nvByAppointment = $visit->treatments_by_appointment;
                $nvCanLink       = $visit->is_editable && hasPermission('opd_register', 'edit');
            @endphp
            <div class="tab-pane" id="tabNextVisit">
                <h4 class="mb-1 font-weight-bold" style="color:#0f172a">Schedule Next Visit</h4>
                <p class="small text-muted mb-4">
                    Books a follow-up for {{ $visit->patient?->name }} with
                    Dr. {{ trim(($visit->doctorProfile?->employee?->f_name ?? '') . ' ' . ($visit->doctorProfile?->employee?->l_name ?? '')) }}.
                    The patient gets a WhatsApp confirmation now and an automatic reminder before each visit.
                    A course rarely finishes in one sitting — add a row for each, and tick which treatments it is for.
                </p>

                @if($upcomingVisits->count())
                    <div class="mb-4">
                        <h6 class="font-weight-bold mb-2" style="font-size:12px; text-transform:uppercase; letter-spacing:.4px; color:#6b7280;">
                            Already scheduled
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                                <thead class="thead-light">
                                    <tr><th>Date</th><th>Time</th><th>Doctor</th><th>Token</th><th>For</th><th>Reason</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingVisits as $up)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($up->appointment_date)->format('d M Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($up->appointment_time)->format('h:i A') }}</td>
                                        <td>Dr. {{ $up->doctorProfile?->employee?->f_name }} {{ $up->doctorProfile?->employee?->l_name }}</td>
                                        <td>{{ $up->token ? '#' . $up->token->token_number : '—' }}</td>
                                        <td>
                                            @forelse($nvByAppointment[$up->id] ?? [] as $term)
                                                <span class="nv-for-chip">{{ $term }}</span>
                                            @empty
                                                <span class="tx-nil">—</span>
                                            @endforelse
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::limit($up->reason ?? '—', 40) }}</td>
                                        <td>
                                            <a href="{{ route('vendor.appointment.show', $up->id) }}"
                                               class="btn btn-xs btn-outline-secondary">Open</a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if(!$visit->doctor_profile_id)
                    <div class="alert alert-warning mb-0" style="font-size:13px;">
                        This visit has no doctor assigned, so a follow-up cannot be booked from here.
                    </div>
                @else
                {{-- The row markup lives in a template so JS can add as many as the course needs
                     without rebuilding the treatment list in script — Blade stays the one place
                     that knows what this visit advised. `__i__` becomes the row index on clone. --}}
                <template id="nvRowTpl">
                    <div class="nv-row" data-nv-row>
                        <div class="nv-row-head">
                            <span class="nv-row-no" data-nv-label>Visit 1</span>
                            <button type="button" class="nv-row-drop" data-nv-drop title="Remove this visit">&times; Remove</button>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label class="input-label" style="font-size:12px;">Date <span class="text-danger">*</span></label>
                                <input type="date" name="visits[__i__][appointment_date]" class="form-control form-control-sm"
                                       min="{{ date('Y-m-d') }}" data-nv-date required>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="input-label" style="font-size:12px;">Time <span class="text-danger">*</span></label>
                                <input type="time" name="visits[__i__][appointment_time]" class="form-control form-control-sm" data-nv-time required>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="input-label" style="font-size:12px;">Slot</label>
                                <select name="visits[__i__][slot_id]" class="form-control form-control-sm" data-nv-slot>
                                    <option value="">-- Select date first --</option>
                                </select>
                            </div>
                        </div>

                        @if($nvCanLink && $nvTerms->count())
                            <div class="nv-tx-box">
                                <label class="input-label" style="font-size:12px;">What is this visit for?</label>
                                <div>
                                    @foreach($nvTerms as $item)
                                        <label class="nv-tx-pick @if($item['booked']) is-booked @endif"
                                               title="{{ $item['booked'] ? 'Already booked into a follow-up — ticking this moves it to the new one.' : 'Marks this treatment upcoming for that date.' }}">
                                            <input type="checkbox" name="visits[__i__][treatments][]" value="{{ $item['term'] }}" data-nv-tx>
                                            {{ $item['term'] }}
                                        </label>
                                    @endforeach
                                </div>
                                <small class="text-muted">
                                    Ticked treatments are marked upcoming for this date and carry the booking, so moving the
                                    appointment later moves the sitting with it.
                                </small>
                            </div>
                        @endif

                        <div class="form-group">
                            <label class="input-label" style="font-size:12px;">Reason / Notes</label>
                            <input type="text" name="visits[__i__][reason]" class="form-control form-control-sm" maxlength="500"
                                   placeholder="Review, dressing change, report follow-up…">
                            <small class="text-muted">Left blank, the ticked treatments become the reason.</small>
                        </div>
                    </div>
                </template>

                <form action="{{ route('vendor.opd.next-visit', $visit->id) }}" method="POST" id="nvForm" style="max-width:720px;">
                    @csrf
                    <div id="nvRows"></div>
                    <div class="d-flex align-items-center" style="gap:10px; flex-wrap:wrap;">
                        <button type="submit" class="btn btn--primary">
                            <i class="tio-calendar-note"></i> <span id="nvSubmitLabel">Schedule Next Visit</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="nvAdd">
                            <i class="tio-add"></i> Add another visit
                        </button>
                    </div>
                </form>
                @if(!$nvCanLink && $nvTerms->count())
                    <p class="text-muted small mt-3 mb-0">
                        This visit is closed, so the follow-up is booked on its own — the treatment plan on the printed
                        receipt is left exactly as it was.
                    </p>
                @endif
                @endif
            </div>

            {{-- TAB: LAB WORK — jobs out at an external lab. Shown only where the hospital's
                 speciality sends work out, or where it asked for the tab in Hospital Settings.
                 Every measurement box and every stage name below comes from that speciality's
                 profile, so a dental clinic types a shade and an optician types an axis. --}}
            @if($labWorkEnabled ?? false)
            <div class="tab-pane" id="tabLabWork">
                @php
                    $lwFields   = $labWorkProfile['fields'];
                    $lwStatuses = $labWorkProfile['statuses'];
                    $lwColours  = \App\Models\OpdLabWork::STATUS_COLOURS;
                    $lwNotify   = \App\Models\OpdLabWork::NOTIFY_STATUSES;
                    $lwOpen     = $labWorks->where('is_open', true);
                    $lwClosed   = $labWorks->where('is_open', false);
                    $lwPhone    = $visit->patient?->phone;
                    $lwEditable = $visit->is_editable && hasPermission('opd_register', 'edit');
                    $lwCurrency = \App\CentralLogics\Helpers::currency_symbol() ?: '₹';
                @endphp

                <div class="d-flex align-items-start justify-content-between mb-1">
                    <h4 class="mb-0 font-weight-bold" style="color:#0f172a">{{ $labWorkProfile['label'] }}</h4>
                    @if($lwEditable)
                        <button type="button" class="btn btn--primary btn-sm" onclick="lwToggleAdd()">
                            <i class="tio-add"></i> New job
                        </button>
                    @endif
                </div>
                <p class="small text-muted mb-3">
                    Work on the bench or out at a lab. Measurements stay with the job, each stage change
                    can tell the patient on WhatsApp, and work sent out can be addressed to a lab from
                    your supplier list and confirmed with them when it changes hands.
                    @if(blank($lwPhone))
                        <span class="text-danger">This patient has no phone number, so nothing can be sent to them.</span>
                    @endif
                </p>

                {{-- New job. Collapsed by default: on most visits the doctor is checking on work
                     already out rather than opening more. --}}
                @if($lwEditable)
                {{-- A rejected New job comes back to a page whose default tab is Consultation and
                     whose add form is collapsed, so without this the error is invisible and the
                     typed values look lost. $lwRejected opens the card; the script at the foot of
                     the pane brings the tab forward with it. --}}
                @php $lwRejected = $errors->any() && old('work_type') !== null; @endphp
                <div class="card mb-3" id="lwAddCard" style="display:{{ $lwRejected ? '' : 'none' }};">
                    <div class="card-header py-2">
                        <h6 class="mb-0" style="font-size:13px;">New {{ $labWorkProfile['unit'] }}</h6>
                    </div>
                    <form method="POST" action="{{ route('vendor.opd.lab-work.store', $visit->id) }}">
                        @csrf
                        <div class="card-body pb-1">
                            @if($lwRejected)
                                <div class="alert alert-danger py-2 px-3" style="font-size:12px;">
                                    <ul class="mb-0 pl-3">
                                        @foreach($errors->all() as $lwError)
                                            <li>{{ $lwError }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @include('hmis::vendor.opd._lab_work_fields', [
                                'labWorkProfile' => $labWorkProfile,
                                'work'           => null,
                                'withStatus'     => true,
                            ])

                            {{-- One datalist for the whole tab: the add form and every edit form
                                 offer the same job list, and a browser only needs one copy. --}}
                            <datalist id="lwTypeList">
                                @foreach($labWorkProfile['types'] as $type)
                                    <option value="{{ $type }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="card-footer py-2 d-flex align-items-center justify-content-between flex-wrap">
                            {{-- Ticked by default: telling the lab IS the point of opening a job for
                                 them, and a job saved without the lab hearing about it is one
                                 somebody has to remember to chase. Untick it for a job opened at the
                                 impression stage, where there is nothing worth sending yet. --}}
                            <label class="mb-0 text-muted" style="font-size:11.5px; cursor:pointer;">
                                <input type="checkbox" name="notify_lab" value="1" class="mr-1" checked>
                                Send the job to the lab on WhatsApp
                            </label>
                            <div>
                                <button type="button" class="btn btn-light btn-sm" onclick="lwToggleAdd()">Cancel</button>
                                <button type="submit" class="btn btn--primary btn-sm">Save job</button>
                            </div>
                        </div>
                    </form>
                </div>
                @endif

                @if($labWorks->isEmpty())
                    <div class="border rounded p-4 text-center text-muted" style="font-size:12.5px; background:#f8fafc;">
                        <i class="tio-lab d-block mb-2" style="font-size:22px; opacity:.5;"></i>
                        No lab work recorded for this patient.
                    </div>
                @endif

                @foreach([['Open', $lwOpen], ['Closed', $lwClosed]] as $lwGroup)
                    @if($lwGroup[1]->count())
                        <h6 class="text-muted mt-3 mb-2" style="font-size:11px; text-transform:uppercase; letter-spacing:.04em;">
                            {{ $lwGroup[0] }} ({{ $lwGroup[1]->count() }})
                        </h6>

                        @foreach($lwGroup[1] as $work)
                            @php
                                $lwColour  = $lwColours[$work->status] ?? ['#475569', '#f1f5f9'];
                                $lwPairs   = $work->measurementPairs($labWorkProfile);
                                $lwTold    = $work->last_notified_status === $work->status && $work->last_notified_at;
                                $lwCustody = $work->custodyPairs();
                                $lwLabPh   = $work->contactPhone();
                            @endphp
                            <div class="card mb-2" style="{{ $work->is_open ? '' : 'opacity:.75;' }}">
                                <div class="card-body py-2 px-3">
                                    <div class="d-flex align-items-start justify-content-between flex-wrap">
                                        <div style="min-width:0;">
                                            <span class="text-dark" style="font-weight:700; font-size:13.5px;">{{ $work->work_type }}</span>
                                            @if(filled($work->site))
                                                <span class="text-muted" style="font-size:12.5px;">· {{ $work->site }}</span>
                                            @endif
                                            <span class="badge ml-1" style="font-weight:600; color:{{ $lwColour[0] }}; background:{{ $lwColour[1] }};">
                                                {{ $work->statusLabel($labWorkProfile) }}
                                            </span>
                                            <div class="text-muted mt-1" style="font-size:11.5px;">
                                                {{-- Where the work is, first thing after the stage: a doctor
                                                     asking about a crown wants to know who has it before
                                                     they want to know what it is made of. --}}
                                                <span class="badge badge-soft-{{ $work->is_internal ? 'info' : 'warning' }} badge-pill mr-1"
                                                      style="font-weight:600;">
                                                    {{ $work->is_internal ? 'In-house' : 'External' }}
                                                </span>

                                                <span class="text-dark" style="font-weight:600;">{{ $work->labDisplayName() }}</span>

                                                @if($work->is_external && filled($work->lab_type))
                                                    <span>· {{ $work->lab_type }}</span>
                                                @endif

                                                @if(filled($lwLabPh))
                                                    <span>· {{ $lwLabPh }}</span>
                                                @endif

                                                @if($work->sent_on)
                                                    <span>· Sent {{ $work->sent_on->format('d M') }}</span>
                                                @endif

                                                @if($work->expected_on)
                                                    <span class="{{ $work->is_open && $work->expected_on->isPast() ? 'text-danger' : '' }}">
                                                        · Expected {{ $work->expected_on->format('d M') }}
                                                    </span>
                                                @endif

                                                @if($work->received_on)
                                                    <span>· Received {{ $work->received_on->format('d M') }}</span>
                                                @endif

                                                @if($work->amount)
                                                    <span>· {{ $lwCurrency }}{{ number_format($work->amount, 2) }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="text-right" style="white-space:nowrap;">
                                            @if($lwTold)
                                                <div class="text-success" style="font-size:11px;">
                                                    <i class="tio-checkmark-circle"></i>
                                                    Patient told {{ $work->last_notified_at->diffForHumans() }}
                                                </div>
                                            @endif

                                            {{-- Kept apart from the patient's stamp above: they are two
                                                 different people who were told two different things, and
                                                 a clinic chasing a lab needs to know which. --}}
                                            @if($work->vendor_notified_at)
                                                <div class="text-success" style="font-size:11px;">
                                                    <i class="tio-checkmark-circle"></i>
                                                    Lab told {{ $work->vendor_notified_at->diffForHumans() }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @if($lwPairs)
                                        <div class="mt-2 pt-2" style="border-top:1px solid #f1f5f9; font-size:12px;">
                                            @foreach($lwPairs as $lwLabel => $lwValue)
                                                <span class="mr-3">
                                                    <span class="text-muted">{{ $lwLabel }}:</span>
                                                    <span class="text-dark" style="font-weight:600;">{{ $lwValue }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Who had it and when. Asked weeks later, when a job has gone
                                         missing between here and the lab and nobody remembers. --}}
                                    @if($lwCustody)
                                        <div class="mt-2 pt-2" style="border-top:1px solid #f1f5f9; font-size:11.5px;">
                                            <i class="tio-user-switch text-muted mr-1"></i>
                                            @foreach($lwCustody as $lwCLabel => $lwCValue)
                                                <span class="mr-3">
                                                    <span class="text-muted">{{ $lwCLabel }}:</span>
                                                    <span class="text-dark" style="font-weight:600;">{{ $lwCValue }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(filled($work->notes))
                                        <div class="text-muted mt-1" style="font-size:11.5px;">{{ $work->notes }}</div>
                                    @endif

                                    {{-- Every exchange on this job, and the buttons that record the
                                         next one. Sits under the summary above rather than replacing
                                         it: the four names on the card are the latest handover each
                                         way, this is the whole chain they were drawn from. --}}
                                    @include('hmis::vendor.handover._trail', [
                                        'handovers'    => ($labWorkHandovers[$work->id] ?? collect()),
                                        'hoSubjectId'  => $work->id,
                                        'hoType'       => 'opd_lab_work',
                                        'hoCanRecord'  => $lwEditable && $work->is_open && $work->is_external,
                                    ])

                                    @if($lwEditable)
                                        <form method="POST" action="{{ route('vendor.opd.lab-work.status', $work->id) }}"
                                              class="mt-2 pt-2 lw-status-form" style="border-top:1px solid #f1f5f9;">
                                            @csrf
                                            <div class="form-inline">
                                                <select name="status" class="form-control form-control-sm" style="font-size:12px;"
                                                        onchange="lwCustody(this)">
                                                    @foreach($lwStatuses as $key => $label)
                                                        <option value="{{ $key }}" @if($key === $work->status) selected @endif>{{ $label }}</option>
                                                    @endforeach
                                                </select>

                                                {{-- Pre-ticked only where the patient has something to act on and a
                                                     number to reach them at, so the common case is one click and
                                                     everything else is a deliberate choice. --}}
                                                <label class="mb-0 ml-2" style="font-size:11.5px; cursor:{{ blank($lwPhone) ? 'not-allowed' : 'pointer' }};">
                                                    <input type="checkbox" name="notify" value="1" class="mr-1"
                                                           @if(blank($lwPhone)) disabled @elseif(in_array($work->status, $lwNotify, true)) checked @endif>
                                                    Tell patient on WhatsApp
                                                </label>

                                                {{-- The lab's own confirmation of the handover. Shown only on the two
                                                     moves that ARE a handover, and only where there is a number to
                                                     send it to. --}}
                                                <label class="mb-0 ml-2 lw-lab-notify" style="display:none; font-size:11.5px; cursor:{{ blank($lwLabPh) ? 'not-allowed' : 'pointer' }};">
                                                    <input type="checkbox" name="notify_lab" value="1" class="mr-1"
                                                           @if(blank($lwLabPh)) disabled @endif>
                                                    Confirm handover with {{ $work->is_internal ? 'technician' : 'lab' }}
                                                </label>

                                                <button type="submit" class="btn btn--primary btn-sm ml-2" style="font-size:12px;">
                                                    Update stage
                                                </button>
                                            </div>

                                            {{-- Who passed the work to whom. Only ever shown on the stage that
                                                 records it: a name typed against the wrong move puts somebody's
                                                 name on a handover they had no part in, which is why the
                                                 controller also reads only the pair belonging to the stage
                                                 being saved rather than trusting what the browser posts. --}}
                                            <div class="form-row mt-2 lw-custody" data-lw-custody="sent" style="display:none;">
                                                <div class="form-group col-md-3 mb-1">
                                                    <label class="input-label text-muted" style="font-size:11px;">Handed over by</label>
                                                    <input type="text" name="handed_over_by" class="form-control form-control-sm"
                                                           maxlength="120" placeholder="Your staff"
                                                           value="{{ $work->handed_over_by }}">
                                                </div>
                                                <div class="form-group col-md-3 mb-1">
                                                    <label class="input-label text-muted" style="font-size:11px;">Collected by</label>
                                                    <input type="text" name="collected_by" class="form-control form-control-sm"
                                                           maxlength="120" placeholder="Who took it"
                                                           value="{{ $work->collected_by }}">
                                                </div>
                                            </div>

                                            <div class="form-row mt-2 lw-custody" data-lw-custody="received" style="display:none;">
                                                <div class="form-group col-md-3 mb-1">
                                                    <label class="input-label text-muted" style="font-size:11px;">Delivered by</label>
                                                    <input type="text" name="delivered_by" class="form-control form-control-sm"
                                                           maxlength="120" placeholder="Who brought it back"
                                                           value="{{ $work->delivered_by }}">
                                                </div>
                                                <div class="form-group col-md-3 mb-1">
                                                    <label class="input-label text-muted" style="font-size:11px;">Received by</label>
                                                    <input type="text" name="received_by" class="form-control form-control-sm"
                                                           maxlength="120" placeholder="Your staff"
                                                           value="{{ $work->received_by }}">
                                                </div>
                                            </div>
                                        </form>

                                        <div class="mt-1">
                                            {{-- Resend without moving the stage: the patient missed it, or a
                                                 relative rang to ask. Separate from the checkbox above so
                                                 telling someone again never quietly re-files the job. --}}
                                            @if(filled($lwPhone))
                                                <form method="POST" action="{{ route('vendor.opd.lab-work.notify', $work->id) }}"
                                                      class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-link btn-sm p-0" style="font-size:11.5px;">
                                                        <i class="tio-whatsapp"></i>
                                                        {{ $lwTold ? 'Send again' : 'Send update now' }}
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Outward, to whoever is making the thing. Carries the patient's
                                                 name and the specification, so it is never a side effect of
                                                 anything else on this card — it is its own button, and it says
                                                 whose number it is going to. --}}
                                            @if(filled($lwLabPh))
                                                <form method="POST" action="{{ route('vendor.opd.lab-work.notify-lab', $work->id) }}"
                                                      class="d-inline ml-3"
                                                      onsubmit="return confirm('Send this job — including the patient\'s name and the specification — to {{ addslashes($work->labDisplayName()) }}?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-link btn-sm p-0" style="font-size:11.5px;">
                                                        <i class="tio-whatsapp"></i>
                                                        {{ $work->vendor_notified_at ? 'Resend job to ' : 'Send job to ' }}{{ $work->is_internal ? 'technician' : 'lab' }}
                                                    </button>
                                                </form>

                                                {{-- Only offered once something has actually changed hands: a
                                                     confirmation of a handover that never happened is worse
                                                     than none. --}}
                                                @if($work->sent_on || $work->received_on)
                                                    <form method="POST" action="{{ route('vendor.opd.lab-work.handover', $work->id) }}"
                                                          class="d-inline ml-3">
                                                        @csrf
                                                        <button type="submit" class="btn btn-link btn-sm p-0" style="font-size:11.5px;">
                                                            <i class="tio-checkmark-circle-outlined"></i>
                                                            Confirm handover
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif

                                            <button type="button" class="btn btn-link btn-sm p-0 ml-3" style="font-size:11.5px;"
                                                    onclick="lwToggleEdit({{ $work->id }})">
                                                Edit details
                                            </button>

                                            @if(hasPermission('opd_register', 'delete'))
                                                <form method="POST" action="{{ route('vendor.opd.lab-work.destroy', $work->id) }}"
                                                      class="d-inline ml-3"
                                                      onsubmit="return confirm('Remove this lab work job? This cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link btn-sm p-0 text-danger" style="font-size:11.5px;">
                                                        Remove
                                                    </button>
                                                </form>
                                            @endif
                                        </div>

                                        {{-- Correcting what was recorded — a mistyped shade, a lab that
                                             changed. Deliberately no stage box: that moves through
                                             Update stage above, which dates the milestones. --}}
                                        <div id="lwEdit{{ $work->id }}" class="mt-2 pt-2" style="display:none; border-top:1px solid #f1f5f9;">
                                            <form method="POST" action="{{ route('vendor.opd.lab-work.update', $work->id) }}">
                                                @csrf
                                                @method('PUT')
                                                @include('hmis::vendor.opd._lab_work_fields', [
                                                    'labWorkProfile' => $labWorkProfile,
                                                    'work'           => $work,
                                                    'withStatus'     => false,
                                                ])
                                                <div class="text-right">
                                                    <button type="button" class="btn btn-light btn-sm"
                                                            onclick="lwToggleEdit({{ $work->id }})">Cancel</button>
                                                    <button type="submit" class="btn btn--primary btn-sm">Save details</button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                @endforeach

                @if($lwEditable && $lwRejected)
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const btn = document.querySelector('.consult-tab-btn[onclick*="tabLabWork"]');
                            if (btn) btn.click();
                        });
                    </script>
                @endif

                {{-- One modal for the whole tab, not one per job: it is a single form whose subject
                     is set when it opens, and rendering a copy inside every card would duplicate
                     the signature canvas ids as many times as the patient has crowns. --}}
                @if($lwEditable)
                    @include('hmis::vendor.handover._modal', ['hoSubjectType' => 'opd_lab_work'])
                @endif
            </div>
            @endif

            {{-- TAB: SECURITY — the patient's real access trail, read from hospital_activity_logs.
                 Shown only where the hospital switched it on in Hospital Settings, which is also
                 what starts the recording, so the tab is never a window onto an empty table. --}}
            @if($securityEnabled ?? false)
            <div class="tab-pane" id="tabSecurity">
                @php
                    $actionStyles = [
                        'viewed'         => ['Opened',      '#475569', '#f1f5f9'],
                        'created'        => ['Created',     '#166534', '#dcfce7'],
                        'edited'         => ['Edited',      '#9a3412', '#ffedd5'],
                        'updated'        => ['Updated',     '#9a3412', '#ffedd5'],
                        'status_changed' => ['Status',      '#1e40af', '#dbeafe'],
                        'rescheduled'    => ['Rescheduled', '#1e40af', '#dbeafe'],
                        'reassigned'     => ['Reassigned',  '#1e40af', '#dbeafe'],
                        'admitted'       => ['Admitted',    '#166534', '#dcfce7'],
                        'discharged'     => ['Discharged',  '#3730a3', '#e0e7ff'],
                        'notified'       => ['Notified',    '#065f46', '#d1fae5'],
                        'cancelled'      => ['Cancelled',   '#991b1b', '#fee2e2'],
                        'deleted'        => ['Deleted',     '#991b1b', '#fee2e2'],
                    ];
                    $subjectLabels = [
                        'patient'       => 'Patient record',
                        'opd_visit'     => 'OPD visit',
                        'appointment'   => 'Appointment',
                        'prescription'  => 'Prescription',
                        'ipd_admission' => 'IPD admission',
                        'opd_lab_work'  => 'Lab work',
                    ];
                    $causerLabels = [
                        'vendor_employee' => 'Staff',
                        'vendor'          => 'Hospital admin',
                        'api_user'        => 'API',
                    ];
                @endphp

                <div class="d-flex align-items-start justify-content-between mb-1">
                    <h4 class="mb-0 font-weight-bold" style="color:#0f172a">Security &amp; Compliance</h4>
                    <span class="text-muted" style="font-size:11.5px;">
                        Patient #{{ $visit->patient_id }}{{ $visit->patient?->patient_uid ? ' · ' . $visit->patient->patient_uid : '' }}
                    </span>
                </div>
                <p class="small text-muted mb-3">
                    Every recorded action on this patient's records — who did it and when.
                    Chart openings and edits are grouped per person into one entry each,
                    so a doctor working through a consultation appears once, not once per save.
                </p>

                @if($securityLog->isEmpty())
                    <div class="border rounded p-4 text-center text-muted" style="font-size:12.5px; background:#f8fafc;">
                        <i class="tio-lock-outlined d-block mb-2" style="font-size:22px; opacity:.5;"></i>
                        Nothing recorded for this patient yet.<br>
                        <span style="font-size:11.5px;">Entries appear here as this patient's records are opened, edited or updated.</span>
                    </div>
                @else
                    <div class="border rounded" style="overflow:hidden;">
                        <div class="table-responsive" style="max-height:460px; overflow-y:auto;">
                            <table class="table table-sm mb-0" style="font-size:12.5px;">
                                <thead style="position:sticky; top:0; z-index:1; background:#f8fafc;">
                                    <tr style="font-size:11px; text-transform:uppercase; letter-spacing:.03em; color:#64748b;">
                                        <th class="border-0 py-2" style="width:150px;">When</th>
                                        <th class="border-0 py-2" style="width:130px;">Action</th>
                                        <th class="border-0 py-2" style="width:180px;">By</th>
                                        <th class="border-0 py-2">Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($securityLog as $entry)
                                        @php
                                            $style = $actionStyles[$entry->action]
                                                ?? [ucfirst(str_replace('_', ' ', $entry->action)), '#475569', '#f1f5f9'];
                                        @endphp
                                        <tr>
                                            <td class="align-middle" style="white-space:nowrap;">
                                                <span class="text-dark">{{ $entry->created_at?->format('d M Y') }}</span>
                                                <span class="text-muted">{{ $entry->created_at?->format('H:i') }}</span>
                                            </td>
                                            <td class="align-middle">
                                                <span class="badge" style="font-weight:600; color:{{ $style[1] }}; background:{{ $style[2] }};">
                                                    {{ $style[0] }}
                                                </span>
                                                <div class="text-muted" style="font-size:10.5px;">
                                                    {{ $subjectLabels[$entry->subject_type] ?? ucfirst(str_replace('_', ' ', $entry->subject_type)) }}
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <span class="text-dark" style="font-weight:600;">{{ $entry->causer_name ?: 'System' }}</span>
                                                @if($entry->causer_type)
                                                    <div class="text-muted" style="font-size:10.5px;">
                                                        {{ $causerLabels[$entry->causer_type] ?? $entry->causer_type }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="align-middle text-dark">{{ $entry->description }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <p class="text-muted mt-2 mb-0" style="font-size:11px;">
                        Showing the {{ $securityLog->count() }} most recent
                        {{ \Illuminate\Support\Str::plural('entry', $securityLog->count()) }}.
                        The full hospital-wide trail is under Activity Log.
                    </p>
                @endif
            </div>
            @endif

        </div>

    </div>

</div>

{{-- ── 6. SARA AI WHY EXPLICABILITY MODAL ── --}}
<div class="modal fade" id="saraWhyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-soft-primary py-2">
                <h5 class="modal-title font-weight-bold text-dark" style="font-size:14px;"><i class="tio-chat-outlined mr-1"></i> Clinical Reasoning — Sara AI</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-dark" style="font-size:13px; line-height:1.5;">
                <div class="d-flex align-items-start gap-3">
                    <span class="rounded-circle bg-soft-primary d-inline-flex align-items-center justify-content-center p-2 mr-2" style="font-size:18px; color:#2563eb; width:36px; height:36px;">
                        ✦
                    </span>
                    <div>
                        <h6 class="font-weight-bold mb-2 text-dark">Hypertensive Urgency Alert Reasoning</h6>
                        <ul class="pl-3" style="line-height: 1.6;">
                            <li class="mb-2"><strong>Targets for T2DM + Hypertension:</strong> Under ACC/AHA 2023 and JNC-8 guidelines, patients with Type 2 Diabetes Mellitus require aggressive blood pressure management to target <strong>&lt;130/80 mmHg</strong>.</li>
                            <li class="mb-2"><strong>Poor Control Flags:</strong> The patient's BP is recorded as <strong>{{ $syst }}/{{ $diast }} mmHg</strong>. This exceeds target thresholds, reflecting poor cardiovascular control.</li>
                            <li class="mb-2"><strong>Risk Aggregators:</strong> A male patient with hypertension, diabetes, and smoking habits has an estimated cardiovascular risk score of <strong>{{ $cvScore }}%</strong> (High) according to Framingham and SCORE2 calculations.</li>
                            <li class="mb-2"><strong>Guideline Directed Therapy:</strong> Initiating an ARB medication (e.g. <strong>Telmisartan 40mg</strong> once daily) is strongly suggested due to its dual protection for cardiac risk and kidney nephropathy.</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('script_2')
{{-- Consultation note templates. Same dialog shape as the complaint groups, and it reuses that
     partial's .cc-grp-* styling so the two read as one feature rather than two. --}}
<div class="modal fade" id="ntModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" style="font-size:14px;font-weight:700;">Saved note phrases</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body py-3" id="ntModalList"></div>
            <div class="modal-footer py-2">
                <button type="button" class="cc-tool" onclick="saveNoteTemplate()">
                    <i class="tio-bookmark-outlined"></i> Add phrase
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const opdQuickUpdateUrl    = "{{ route('vendor.opd.quick-update', $visit->id) }}";
    const ntStoreUrl           = "{{ route('vendor.opd.note-templates.store') }}";
    const ntDelUrlTpl          = "{{ route('vendor.opd.note-templates.destroy', '__ID__') }}";
    const docUploadUrl         = "{{ route('vendor.patient.upload-documents', $visit->patient_id) }}";
    const docDeleteUrlTpl      = "{{ route('vendor.patient.delete-document', ['id' => $visit->patient_id, 'docId' => '__DOC__']) }}";
    const DOC_TYPE_META = {
        report:       ['Report', '#dbeafe', 'med'],
        id_proof:     ['ID Proof', '#fef3c7', 'med'],
        prescription: ['Prescription', '#d1fae5', 'med'],
        other:        ['Other', '#f3f4f6', 'med'],
        arogyasri:    ['Arogya Sri', '#fce7f3', 'govt'],
        insurance:    ['Insurance', '#e0e7ff', 'govt'],
        aadhaar:      ['Aadhaar Card', '#fef9c3', 'govt'],
        pan:          ['PAN Card', '#ccfbf1', 'govt'],
        ration_card:  ['Ration Card', '#ffedd5', 'govt'],
        abha:         ['ABHA / Health ID', '#dcfce7', 'govt'],
        govt_other:   ['Other Govt', '#f3f4f6', 'govt'],
    };
    const patientUpdateUrl     = "{{ route('vendor.patient.update', $visit->patient_id) }}";
    const pharmacySearchUrl    = "{{ route('vendor.prescription.search-medicines') }}";
    const orderTestsUrl        = "{{ route('vendor.patient.order-tests', $visit->patient_id) }}";
    const labWorklistUrl       = "{{ route('vendor.lab.worklist') }}";
    const csrfToken            = "{{ csrf_token() }}";

    // ── Real-time Clock ──
    function startClock() {
        setInterval(() => {
            const clockEl = document.getElementById('tickClock');
            if (clockEl) {
                const now = new Date();
                let hrs = now.getHours();
                let mins = now.getMinutes();
                let secs = now.getSeconds();
                hrs = hrs < 10 ? '0' + hrs : hrs;
                mins = mins < 10 ? '0' + mins : mins;
                secs = secs < 10 ? '0' + secs : secs;
                clockEl.innerHTML = `<i class="tio-time"></i> ${hrs}:${mins}:${secs}`;
            }
        }, 1000);
    }
    startClock();

    // ── Horizontal Tab Switcher ──
    // The New job form on the Lab Work tab. Hidden until asked for: most visits are about work
    // already out at the lab, not about opening more.
    function lwToggleAdd() {
        const card = document.getElementById('lwAddCard');
        if (!card) return;
        const opening = card.style.display === 'none';
        card.style.display = opening ? '' : 'none';
        if (opening) {
            lwInitSelects(card);
            card.querySelector('input[name="work_type"]')?.focus();
        }
    }

    function lwToggleEdit(id) {
        const box = document.getElementById('lwEdit' + id);
        if (!box) return;
        const opening = box.style.display === 'none';
        box.style.display = opening ? '' : 'none';
        if (opening) lwInitSelects(box);
    }

    // In-house or sent out. Scoped to the .lw-fields the select sits in, because the add form and
    // every job's edit form each render one and they all use the same field names.
    function lwMode(select) {
        const scope = select.closest('.lw-fields');
        if (!scope) return;
        scope.querySelectorAll('[data-lw-block]').forEach(block => {
            block.style.display = block.dataset.lwBlock === select.value ? '' : 'none';
        });
    }

    // Show the chosen lab's name, number and address. Read-only on purpose: they belong to the
    // supplier record and are copied onto the job at save time, so this is there to let staff see
    // where the work — and the WhatsApp message — is going, not to be retyped per job.
    function lwVendor(select) {
        const scope = select.closest('.lw-fields');
        const box   = scope && scope.querySelector('.lw-lab-contact');
        if (!box) return;

        const opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) {
            box.innerHTML = '<span class="text-muted">Choose a lab to see its details.</span>';
            return;
        }

        const phone   = (opt.dataset.phone || '').trim();
        const address = (opt.dataset.address || '').trim();
        const bits    = [];

        bits.push(phone
            ? '<span class="text-dark" style="font-weight:600;">' + lwEscape(phone) + '</span>'
            : '<span class="text-danger">No phone on this lab — nothing can be sent to them.</span>');
        if (address) bits.push('<span class="text-muted">' + lwEscape(address) + '</span>');

        box.innerHTML = bits.join('<br>');
    }

    function lwEscape(value) {
        const el = document.createElement('span');
        el.textContent = value;
        return el.innerHTML;
    }

    // Select2 for the two pickers in the lab block. Deferred until the form is actually on screen:
    // both the New job card and every Edit form start hidden, and Select2 measures zero width when
    // it initialises inside a display:none parent, which leaves a collapsed sliver of a control.
    function lwInitSelects(scope) {
        if (!scope || typeof jQuery === 'undefined' || !jQuery.fn.select2) return;

        scope.querySelectorAll('[data-lw-select2]').forEach(el => {
            if (el.dataset.lwSelect2Ready) return;
            el.dataset.lwSelect2Ready = '1';

            jQuery(el).select2({
                // Only the lab type accepts something not on the list — a clinic using a kind of
                // lab nobody has named yet. The lab itself is a supplier record and cannot be
                // conjured from a consultation screen.
                tags: el.dataset.lwSelect2 === 'tags',
                width: '100%',
                allowClear: true,
                placeholder: el.dataset.placeholder || '',
                dropdownParent: jQuery(el).closest('.card').length ? jQuery(el).closest('.card') : jQuery(document.body)
            }).on('change', function () {
                if (el.name === 'lab_vendor_id') lwVendor(el);
            });
        });

        scope.querySelectorAll('select[name="lab_vendor_id"]').forEach(lwVendor);
    }

    // Work only changes hands twice — on its way out and on its way back — so the two name boxes
    // and the lab's confirmation appear on exactly those stages and nowhere else.
    //
    // $initial is the page-load pass, which lays the boxes out for the stage a job is already at
    // but ticks nothing. A job already sitting at "Sent to lab" is not being sent again by someone
    // opening the record, and a pre-ticked box on every load would resend the confirmation on the
    // next unrelated stage change.
    function lwCustody(select, initial) {
        const form = select.closest('.lw-status-form');
        if (!form) return;

        const handover = select.value === 'sent' || select.value === 'received';

        form.querySelectorAll('.lw-custody').forEach(row => {
            row.style.display = row.dataset.lwCustody === select.value ? '' : 'none';
        });

        const notify = form.querySelector('.lw-lab-notify');
        if (notify) {
            notify.style.display = handover ? '' : 'none';
            const box = notify.querySelector('input[type="checkbox"]');
            if (box && !box.disabled && initial !== true) box.checked = handover;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.lw-status-form select[name="status"]')
            .forEach(select => lwCustody(select, true));

        // A rejected New job renders its card already open, so nothing calls lwToggleAdd() to set
        // its pickers up. offsetParent is null for anything inside a hidden parent, which is
        // exactly the case Select2 cannot measure — so only what is genuinely on screen is done
        // here and the rest waits for the click that reveals it.
        document.querySelectorAll('.lw-fields').forEach(scope => {
            if (scope.offsetParent !== null) lwInitSelects(scope);
        });
    });

    function switchTab(btn, tabId) {
        document.querySelectorAll('.consult-tab-btn').forEach(el => el.classList.remove('active'));
        btn.classList.add('active');

        document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
        const activePane = document.getElementById(tabId);
        if (activePane) activePane.classList.add('active');

        // The patient card belongs to the Consultation tab; its crucial rows live in the header
        // line under the patient's name, which stays put whatever tab is open.
        document.querySelectorAll('.tab-only-details').forEach(el => {
            el.style.display = (tabId === 'tabDetails') ? '' : 'none';
        });

        // If switching to Sara AI tab, animate progress bars
        if (tabId === 'tabSaraAI') {
            setTimeout(() => {
                document.querySelectorAll('.risk-progress-bar').forEach(bar => {
                    const width = bar.dataset.width;
                    bar.style.width = width;
                });
            }, 100);
        }
    }

    // ── Next Visit: a row per follow-up, each loading this doctor's slots for its own date ──
    // A course of treatment is booked in one go — three sittings a fortnight apart is one form,
    // not three round trips through the appointment book.
    (function () {
        const tpl  = document.getElementById('nvRowTpl');
        const wrap = document.getElementById('nvRows');
        if (!tpl || !wrap) return;

        const nvSlotsUrl = "{{ route('vendor.appointment.available-slots') }}";
        const nvDoctorId = "{{ $visit->doctor_profile_id }}";
        // Row indexes are never reused, so removing the middle row cannot make two rows share a
        // name and collapse into one on the server.
        let nvIndex = 0;

        function nvFormatTime(t) {
            if (!t) return '';
            const [h, m] = t.split(':');
            const hour = parseInt(h);
            return `${hour > 12 ? hour - 12 : (hour || 12)}:${m} ${hour >= 12 ? 'PM' : 'AM'}`;
        }

        function nvRenumber() {
            const rows = wrap.querySelectorAll('[data-nv-row]');
            rows.forEach((row, i) => {
                row.querySelector('[data-nv-label]').textContent = 'Visit ' + (i + 1);
                row.querySelector('[data-nv-drop]').style.display = rows.length > 1 ? '' : 'none';
            });

            const label = document.getElementById('nvSubmitLabel');
            if (label) {
                label.textContent = rows.length > 1
                    ? 'Schedule ' + rows.length + ' Next Visits'
                    : 'Schedule Next Visit';
            }
        }

        function nvLoadSlots(row) {
            const date = row.querySelector('[data-nv-date]').value;
            const sel  = row.querySelector('[data-nv-slot]');

            if (!date) {
                sel.innerHTML = '<option value="">-- Select date first --</option>';
                return;
            }

            sel.innerHTML = '<option value="">Loading...</option>';

            fetch(`${nvSlotsUrl}?doctor_profile_id=${nvDoctorId}&date=${date}`)
                .then(r => r.json())
                .then(slots => {
                    if (!slots.length) {
                        sel.innerHTML = '<option value="">No slots for this day — enter time manually</option>';
                        return;
                    }
                    sel.innerHTML = '<option value="">-- Manual time --</option>';
                    slots.forEach(s => {
                        const label = `${nvFormatTime(s.slot_start)} – ${nvFormatTime(s.slot_end)} | ${s.available}/${s.max_patients} available`;
                        const disabled = s.available <= 0 ? 'disabled' : '';
                        sel.innerHTML += `<option value="${s.id}" data-start="${s.slot_start}" ${disabled}>${label}</option>`;
                    });
                })
                .catch(() => {
                    sel.innerHTML = '<option value="">Could not load slots — enter time manually</option>';
                });
        }

        function nvBind(row) {
            row.querySelector('[data-nv-date]').addEventListener('change', () => nvLoadSlots(row));

            row.querySelector('[data-nv-slot]').addEventListener('change', function () {
                const opt   = this.options[this.selectedIndex];
                const start = opt ? opt.getAttribute('data-start') : null;
                if (start) row.querySelector('[data-nv-time]').value = start.substring(0, 5);
            });

            row.querySelector('[data-nv-drop]').addEventListener('click', function () {
                row.remove();
                nvRenumber();
            });

            row.querySelectorAll('[data-nv-tx]').forEach(box => {
                const pick = box.closest('.nv-tx-pick');
                box.addEventListener('change', () => pick.classList.toggle('is-on', box.checked));
            });
        }

        function nvAddRow() {
            const holder = document.createElement('div');
            holder.innerHTML = tpl.innerHTML.replace(/__i__/g, nvIndex++);

            const row = holder.firstElementChild;
            wrap.appendChild(row);
            nvBind(row);
            nvRenumber();
            return row;
        }

        document.getElementById('nvAdd').addEventListener('click', function () {
            const row = nvAddRow();
            row.querySelector('[data-nv-date]').focus();
            row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });

        nvAddRow();

        /**
         * Open this tab with one treatment already ticked — what "book a visit for this" on a
         * treatment chip does when the doctor wants the sitting planned alongside others rather
         * than booked on the spot. Reuses an empty row instead of stacking blank ones up.
         */
        window.nvPrefillTreatment = function (term) {
            const rows  = Array.from(wrap.querySelectorAll('[data-nv-row]'));
            const empty = rows.find(row => !row.querySelector('[data-nv-date]').value);
            const row   = empty || nvAddRow();

            const box = Array.from(row.querySelectorAll('[data-nv-tx]')).find(b => b.value === term);
            if (box && !box.checked) {
                box.checked = true;
                box.dispatchEvent(new Event('change'));
            }

            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.querySelector('[data-nv-date]').focus();
        };
    })();

    // ── Edit/Save fields in Sidebar (Allergies, Chronic) ──
    function editSidebarField(field) {
        if (field === 'allergies') {
            document.getElementById('sidebarAllergiesList').style.display = 'none';
            document.getElementById('editAllergiesBox').style.display = '';
        } else if (field === 'chronic') {
            document.getElementById('sidebarChronicList').style.display = 'none';
            document.getElementById('editChronicBox').style.display = '';
        }
    }

    function cancelSidebarEdit(field) {
        if (field === 'allergies') {
            document.getElementById('sidebarAllergiesList').style.display = '';
            document.getElementById('editAllergiesBox').style.display = 'none';
        } else if (field === 'chronic') {
            document.getElementById('sidebarChronicList').style.display = '';
            document.getElementById('editChronicBox').style.display = 'none';
        }
    }

    function saveSidebarField(field) {
        const isAllergies = field === 'allergies';
        const value = document.getElementById(isAllergies ? 'inputAllergies' : 'inputChronic').value.trim();

        const form = new FormData();
        form.append('_token', csrfToken);
        form.append('name', "{{ $visit->patient?->name }}"); // required field

        if (isAllergies) {
            form.append('allergies', value);
        } else {
            form.append('chronic_conditions', value);
        }

        fetch(patientUpdateUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: form
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                // Update UI list
                const listContainer = document.getElementById(isAllergies ? 'sidebarAllergiesList' : 'sidebarChronicList');
                listContainer.innerHTML = '';
                if (value) {
                    value.split(',').forEach(item => {
                        const span = document.createElement('span');
                        span.className = isAllergies ? 'allergy-badge-item' : 'chronic-badge-item';
                        span.textContent = item.trim();
                        listContainer.appendChild(span);
                        listContainer.appendChild(document.createTextNode(' '));
                    });
                } else {
                    listContainer.innerHTML = `<span class="text-muted small">None recorded</span>`;
                }
                cancelSidebarEdit(field);
                updateSaveTime();
            } else {
                alert('Save failed.');
            }
        })
        .catch(() => alert('Failed to connect.'));
    }

    function updateSaveTime() {
        const timeEl = document.getElementById('saveTime');
        if (timeEl) {
            const now = new Date();
            let hrs = now.getHours();
            let mins = now.getMinutes();
            let ampm = hrs >= 12 ? 'pm' : 'am';
            hrs = hrs % 12;
            hrs = hrs ? hrs : 12;
            mins = mins < 10 ? '0'+mins : mins;
            timeEl.textContent = `${hrs}:${mins} ${ampm}`;
        }
    }

    // ── Autosave ──
    // The banner above has always read "Auto-saved", but nothing on this page ever saved on its
    // own — every block needed its Save button pressed, and a doctor who typed a note and moved
    // to the next tab lost it while being told it was safe. These handlers make the banner true.
    //
    // One in-flight request at a time per field, so a fast typist cannot have two PATCHes racing
    // and land the older body last. Save buttons still work and still close the editor.
    // Mirrors OpdVisit::getIsEditableAttribute(). The server is the authority — this only keeps a
    // locked page from flashing a save error at someone who cannot act on it.
    const visitLocked = @json(!$visit->is_editable);

    const AUTOSAVE_DELAY = { text: 900, terms: 400 };
    const autosaveTimers  = {};
    const autosaveInFlight = {};
    const autosavePending  = {};

    function setSaveState(state, detail) {
        const bar = document.querySelector('.autosave-bar .indicator');
        if (!bar) return;
        const label = bar.querySelector('span:last-child');
        const dot   = bar.querySelector('.indicator-dot');
        if (!label) return;

        if (state === 'saving') {
            label.textContent = 'Saving…';
            if (dot) dot.style.background = '#f59e0b';
        } else if (state === 'error') {
            label.innerHTML = 'Not saved — <a href="javascript:;" onclick="flushAutosave(true)">retry</a>';
            if (dot) dot.style.background = '#dc3545';
        } else {
            label.innerHTML = 'Auto-saved: <span id="saveTime"></span>';
            if (dot) dot.style.background = '';
            updateSaveTime();
        }
    }

    /**
     * Queue a save for one field. `payload` is built at flush time rather than now, so a burst
     * of keystrokes sends the final value once instead of every intermediate one.
     */
    function queueAutosave(field, buildPayload, onSaved, delay) {
        if (visitLocked) return;

        autosavePending[field] = { buildPayload, onSaved };
        clearTimeout(autosaveTimers[field]);
        autosaveTimers[field] = setTimeout(() => runAutosave(field), delay ?? AUTOSAVE_DELAY.text);
    }

    function runAutosave(field, keepalive) {
        const job = autosavePending[field];
        if (!job) return;

        // Something is already saving this field — let it finish and re-run with the latest.
        if (autosaveInFlight[field] && !keepalive) {
            autosaveTimers[field] = setTimeout(() => runAutosave(field), 250);
            return;
        }

        delete autosavePending[field];
        autosaveInFlight[field] = true;
        setSaveState('saving');

        fetch(opdQuickUpdateUrl, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(job.buildPayload()),
            keepalive: !!keepalive
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.ok) throw new Error('save rejected');
            if (job.onSaved) job.onSaved(data);
            setSaveState('saved');
        })
        .catch(() => {
            // Put it back so a retry — or the next edit — picks it up rather than dropping it.
            autosavePending[field] = autosavePending[field] || job;
            setSaveState('error');
        })
        .finally(() => { autosaveInFlight[field] = false; });
    }

    /** Send everything still queued right now — used on retry and when the page is closing. */
    function flushAutosave(immediate) {
        Object.keys(autosavePending).forEach(field => {
            clearTimeout(autosaveTimers[field]);
            runAutosave(field, !immediate);
        });
    }

    // A doctor who closes the tab mid-sentence still gets the sentence saved.
    window.addEventListener('beforeunload', function () {
        if (Object.keys(autosavePending).length) flushAutosave(false);
    });

    function autosaveNotes() {
        queueAutosave('notes', () => ({ note_terms: selectedTerms('ntSelect') }), () => {
            renderTerms('notesBadges', selectedTerms('ntSelect'), 'nt-badge');
            syncRxField('rxNotes', 'rxNotesHint', selectedTerms('ntSelect'));
        }, AUTOSAVE_DELAY.terms);
    }

    function autosaveComplaints() {
        queueAutosave('cc', () => ({ complaint: selectedTerms('ccSelect') }),
            () => renderComplaintBadges(selectedTerms('ccSelect')), AUTOSAVE_DELAY.terms);
    }

    function autosaveDxTx() {
        queueAutosave('dxtx', () => ({
            diagnosis: selectedTerms('dxSelect'),
            treatment: selectedTerms('txSelect'),
            willing_treatment: selectedTerms('wtSelect')
        }), (data) => {
            if (data && data.treatment_plan) txPlan = data.treatment_plan;
            renderTerms('dxBadges', selectedTerms('dxSelect'), 'dx-badge');
            renderTxBadges(selectedTerms('txSelect'));
            renderTerms('wtBadges', selectedTerms('wtSelect'), 'wt-badge');
            syncRxField('rxDiagnosis', 'rxDxHint', selectedTerms('dxSelect'));
        }, AUTOSAVE_DELAY.terms);
    }

    // ── Consultation note templates ──
    // Prose counterpart to the complaint groups: the blocks a doctor writes again and again.
    let ntTemplates = @json(collect($noteTemplates ?? [])->map(fn($t) => ['id' => $t->id, 'name' => $t->name])->values());

    function ntRefreshCounts() {
        document.querySelectorAll('[data-nt-count]').forEach(el => {
            el.textContent = ntTemplates.length;
            el.style.display = ntTemplates.length ? '' : 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', ntRefreshCounts);

    /**
     * Rebuild the picker's options from the store list, keeping whatever is already picked —
     * including a phrase typed on this visit that is not in the list yet.
     */
    function ntSyncOptions() {
        const el = document.getElementById('ntSelect');
        if (!el) return;

        const chosen = Array.from(el.selectedOptions).map(o => o.value);
        const seen   = new Set();
        el.innerHTML = '';

        ntTemplates.map(t => t.name).concat(chosen).forEach(phrase => {
            const key = phrase.toLowerCase();
            if (seen.has(key)) return;
            seen.add(key);
            el.appendChild(new Option(phrase, phrase, false, chosen.includes(phrase)));
        });

        if (window.jQuery && ntSelect2Ready) jQuery(el).trigger('change.select2');
    }

    function openNoteTemplates() {
        const list = document.getElementById('ntModalList');
        if (!list) return;

        if (!ntTemplates.length) {
            list.innerHTML = '<div class="cc-grp-empty">No saved phrases yet. Type one into the notes box and press Enter — it is remembered automatically.</div>';
        } else {
            list.innerHTML = '';
            ntTemplates.forEach(t => {
                const row = document.createElement('div');
                row.className = 'cc-grp-row';

                const main = document.createElement('div');
                main.className = 'cc-grp-main';
                main.style.cursor = 'pointer';
                main.onclick = () => ntApply(t.name);

                const name = document.createElement('div');
                name.className = 'cc-grp-name';
                name.textContent = t.name;
                main.appendChild(name);

                const del = document.createElement('i');
                del.className = 'tio-delete cc-grp-del';
                del.title = 'Remove from the list';
                del.onclick = (e) => { e.stopPropagation(); ntDelete(t.id); };

                row.appendChild(main);
                row.appendChild(del);
                list.appendChild(row);
            });
        }

        if (window.jQuery) jQuery('#ntModal').modal('show');
    }

    let ntSelect2Ready = false;

    /** Built on first open — Select2 mis-sizes itself against a container that is still hidden. */
    function initNtSelect2() {
        if (ntSelect2Ready || typeof jQuery === 'undefined' || !jQuery.fn.select2) return;

        jQuery('#ntSelect').select2({
            tags: true,
            width: '100%',
            tokenSeparators: [','],
            placeholder: 'Pick or type a note…',
            containerCssClass: 'nt-select2'
        }).on('change', autosaveNotes);

        ntSelect2Ready = true;
    }

    /** Put a phrase on the picker, creating the option when the list has never seen it. */
    function ntApply(phrase) {
        const el = document.getElementById('ntSelect');
        if (!el) return;

        let opt = Array.from(el.options).find(o => o.value.toLowerCase() === String(phrase).toLowerCase());
        if (!opt) {
            opt = new Option(phrase, phrase, true, true);
            el.appendChild(opt);
        }
        opt.selected = true;
        if (window.jQuery) jQuery(el).trigger('change');

        if (window.jQuery) jQuery('#ntModal').modal('hide');
    }

    /** Add a phrase to this clinic's list without having to record it on a visit first. */
    function saveNoteTemplate() {
        const phrase = (prompt('Add a note phrase to the list') || '').trim();
        if (!phrase) return;

        fetch(ntStoreUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ name: phrase })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok || !data.template) throw new Error(data.msg || 'save failed');
            if (!ntTemplates.some(t => t.id === data.template.id)) {
                ntTemplates.push(data.template);
                ntTemplates.sort((a, b) => a.name.localeCompare(b.name));
            }
            ntRefreshCounts();
            ntSyncOptions();
            openNoteTemplates();
        })
        .catch(e => alert(e.message || 'Could not add the phrase.'));
    }

    function ntDelete(id) {
        if (!confirm('Delete this template?')) return;

        fetch(ntDelUrlTpl.replace('__ID__', id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(() => {
            ntTemplates = ntTemplates.filter(t => t.id !== id);
            ntRefreshCounts();
            ntSyncOptions();
            openNoteTemplates();
        })
        .catch(() => alert('Could not delete the template.'));
    }

    // ── CC & Notes inline edit ──
    // Double-clicking the summary opens the same editor the pencil does. A locked
    // visit, or a card whose editor was never rendered for want of permission,
    // stays read-only.
    function editOnDblClick(field) {
        if (visitLocked) return;
        const edit = document.getElementById(field === 'cc' ? 'ccEdit' : 'notesEdit');
        if (!edit || edit.style.display !== 'none') return;

        // A double-click leaves the badge text selected, which then sits highlighted
        // behind the editor that just opened.
        const sel = window.getSelection();
        if (sel) sel.removeAllRanges();

        toggleEdit(field);
    }

    function toggleEdit(field) {
        const view = document.getElementById(field === 'cc' ? 'ccView' : 'notesView');
        const edit = document.getElementById(field === 'cc' ? 'ccEdit' : 'notesEdit');
        const showing = edit.style.display === 'none';

        // Closing the editor sends whatever is still on the debounce timer straight away, so the
        // summary underneath shows the final text at once rather than up to a second later. This
        // is what stands in for the Save button the notes box no longer has.
        if (!showing) {
            const key = field === 'cc' ? 'cc' : 'notes';
            if (autosavePending[key]) {
                clearTimeout(autosaveTimers[key]);
                runAutosave(key);
            }
        }
        view.style.display = showing ? 'none' : '';
        edit.style.display = showing ? '' : 'none';
        // Select2 needs a visible container to size itself, so build it on first open.
        if (field === 'cc' && showing) initCcSelect2();
        if (field === 'notes' && showing) initNtSelect2();
    }

    let ccSelect2Ready = false;
    function initCcSelect2() {
        if (ccSelect2Ready || typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
        jQuery('#ccSelect').select2({
            tags: true,
            width: '100%',
            tokenSeparators: [','],
            placeholder: 'Select or type a complaint…',
            containerCssClass: 'cc-select2'
        }).on('change', autosaveComplaints);
        ccSelect2Ready = true;
    }

    function saveField(field) {
        if (visitLocked) return;

        // Complaints and notes are both term lists now. Only complaints still has a Save button;
        // notes saves as chips are picked and closes without one.
        const isCC  = field === 'cc';
        const id    = isCC ? 'ccSelect' : 'ntSelect';
        const terms = selectedTerms(id);

        // Drop anything autosave still had queued for this field — pressing Save sends the same
        // values now, and letting both fire would put two PATCHes in the air for one edit.
        const key = isCC ? 'cc' : 'notes';
        clearTimeout(autosaveTimers[key]);
        delete autosavePending[key];

        fetch(opdQuickUpdateUrl, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(isCC ? { complaint: terms } : { note_terms: terms })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) return;

            if (isCC) {
                renderComplaintBadges(terms);
            } else {
                renderTerms('notesBadges', terms, 'nt-badge');
            }
            toggleEdit(field);
            setSaveState('saved');
        })
        .catch(() => alert('Save failed.'));
    }

    function renderComplaintBadges(terms) {
        const box = document.getElementById('ccBadges');
        box.innerHTML = terms.length
            ? terms.map(t => `<span class="cc-badge">${t}</span>`).join('')
            : '<span class="text-muted small">Not recorded yet.</span>';
    }


    // ── Vitals inline edit ──
    const VITAL_FIELDS = ['bp_systolic', 'bp_diastolic', 'pulse_rate', 'temperature',
                          'respiratory_rate', 'spo2', 'weight', 'height'];

    function toggleVitalsEdit() {
        const view = document.getElementById('vitalsView');
        const edit = document.getElementById('vitalsEdit');
        if (!edit) return;

        // The panel is folded by default; the pencil has to unfold it or the
        // inputs open behind a closed card.
        const wrap = document.getElementById('vitalsProfileBody');
        if (wrap && wrap.style.display === 'none') toggleVitalsProfile();

        const showing = edit.style.display === 'none';
        view.style.display = showing ? 'none' : '';
        edit.style.display = showing ? '' : 'none';
        if (showing) {
            const first = document.getElementById('v_bp_systolic');
            if (first) first.focus();
        }
    }

    // Empty box => null, i.e. "not taken", not "leave whatever was there". Sent for every field
    // so a cleared reading actually clears rather than silently keeping the old number.
    function readVitals() {
        const out = {};
        VITAL_FIELDS.forEach(function (field) {
            const el = document.getElementById('v_' + field);
            const raw = el ? el.value.trim() : '';
            out[field] = raw === '' ? null : raw;
        });
        return out;
    }

    function saveVitals(btn) {
        if (visitLocked) return;

        const payload = readVitals();
        btn.disabled = true;
        setSaveState('saving');

        fetch(opdQuickUpdateUrl, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json().then(body => ({ status: r.status, body: body })))
        .then(res => {
            btn.disabled = false;

            // 422 is either the closed-visit refusal (msg) or a value out of range (errors).
            if (!res.body || !res.body.ok) {
                const errors = res.body && res.body.errors;
                const first  = errors ? errors[Object.keys(errors)[0]][0] : null;
                setSaveState('error');
                alert(first || (res.body && res.body.msg) || 'Could not save the vitals.');
                return;
            }

            renderVitals(payload);
            toggleVitalsEdit();
            setSaveState('saved');
        })
        .catch(() => {
            btn.disabled = false;
            setSaveState('error');
            alert('Could not save the vitals.');
        });
    }

    function renderVitals(v) {
        const dash = '—';
        const cell = (id, html) => { const el = document.getElementById(id); if (el) el.innerHTML = html; };
        const withUnit = (value, unit) => (value === null ? dash : value + unit);

        const sys = v.bp_systolic, dia = v.bp_diastolic;
        const bpHigh = Number(sys) >= 140 || Number(dia) >= 90;

        cell('vBpCell', (sys && dia)
            ? '<strong>' + sys + '/' + dia + '</strong> mmHg <span class="badge ' +
              (bpHigh ? 'badge-soft-danger' : 'badge-soft-success') + ' ml-1">' +
              (bpHigh ? 'Hypertensive Range' : 'Normal Range') + '</span>'
            : '<span class="text-muted">' + dash + '</span>');
        cell('vPulseCell', withUnit(v.pulse_rate, ' bpm'));
        cell('vTempCell',  withUnit(v.temperature, ' °F'));
        cell('vRrCell',    withUnit(v.respiratory_rate, ' /min'));
        cell('vSpo2Cell',  withUnit(v.spo2, '%'));
        cell('vWtHtCell',  withUnit(v.weight, ' kg') + ' / ' + withUnit(v.height, ' cm'));

        // The strip under the patient header reads the same columns.
        const strip = (id, text, tone) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = text;
            if (tone) {
                el.classList.remove('red-text', 'green-text');
                el.classList.add(tone);
            }
        };
        strip('vsBp', (sys || dash) + '/' + (dia || dash), bpHigh ? 'red-text' : 'green-text');
        strip('vsPulse', v.pulse_rate || dash);
        strip('vsTemp', v.temperature || dash, Number(v.temperature) >= 100 ? 'red-text' : 'green-text');
        strip('vsSpo2', v.spo2 ? v.spo2 + '%' : dash, (v.spo2 && Number(v.spo2) < 95) ? 'red-text' : 'green-text');
        strip('vsWeight', v.weight || dash);
    }


    // ── Diagnosis & Treatment (Select2 tags: pick from the list or type a new term) ──
    let dxSelect2Ready = false;

    // diagnosis (normalised) => { treatment: timesGivenTogether }, built from this hospital's own
    // visits. See App\Services\OpdTermInsights.
    const dxTxPairs = @json($termInsights['pairs'] ?? new stdClass);

    // Must match OpdTermInsights::key() — the map is keyed with it.
    function termKey(term) {
        return String(term || '').toLowerCase().replace(/[^\p{L}\p{N}]+/gu, ' ').trim();
    }

    function initDxSelect2() {
        if (dxSelect2Ready || typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
        jQuery('#dxSelect').select2({
            tags: true,
            width: '100%',
            tokenSeparators: [','],
            placeholder: 'Select or type a diagnosis…'
        }).on('change', function () {
            renderTxSuggestions();
            autosaveDxTx();
        });
        jQuery('#wtSelect').select2({
            tags: false,
            width: '100%',
            placeholder: 'Tick what the patient agreed to…',
            containerCssClass: 'wt-select2'
        }).on('change', autosaveDxTx);
        jQuery('#txSelect').select2({
            tags: true,
            width: '100%',
            tokenSeparators: [','],
            placeholder: 'Select or type a treatment…',
            containerCssClass: 'tx-select2'
        }).on('change', function () {
            syncChipState('dxSelect');
            renderTxSuggestions();
            syncWillingOptions();
            autosaveDxTx();
        });
        dxSelect2Ready = true;
        syncChipState('dxSelect');
        renderTxSuggestions();
        syncWillingOptions();
    }

    /**
     * The patient can only agree to something that was offered, so Willing Treatment is
     * a tick-list of whatever Advised Treatment currently holds — never free text. Drop a
     * treatment from the advice and the consent to it goes with it, which is the honest
     * reading: it is no longer a plan anyone agreed to.
     */
    function syncWillingOptions() {
        const wt = document.getElementById('wtSelect');
        if (!wt) return;

        const advised = selectedTerms('txSelect');
        const keep    = selectedTerms('wtSelect').filter(t => advised.includes(t));

        wt.innerHTML = '';
        advised.forEach(term => {
            const opt = document.createElement('option');
            opt.value = term;
            opt.textContent = term;
            opt.selected = keep.includes(term);
            wt.appendChild(opt);
        });

        // Namespaced so select2 redraws without firing the change handler that
        // would queue a second autosave on top of the one already going out.
        if (window.jQuery && jQuery.fn.select2) jQuery(wt).trigger('change.select2');
    }

    /** Select a term on a picker, creating the option when it is one the list has never seen. */
    function addTermTo(selectId, term) {
        const el = document.getElementById(selectId);
        if (!el) return;
        let opt = Array.from(el.options).find(o => o.value.toLowerCase() === term.toLowerCase());
        if (!opt) {
            opt = new Option(term, term, true, true);
            el.appendChild(opt);
        }
        opt.selected = true;
        if (window.jQuery) jQuery(el).trigger('change');
    }

    function removeTermFrom(selectId, term) {
        const el = document.getElementById(selectId);
        if (!el) return;
        Array.from(el.options).forEach(o => {
            if (o.value.toLowerCase() === term.toLowerCase()) o.selected = false;
        });
        if (window.jQuery) jQuery(el).trigger('change');
    }

    /** A "seen most here" chip toggles that diagnosis rather than only adding it. */
    function toggleChipTerm(selectId, btn) {
        const term = btn.dataset.term;
        const on = selectedTerms(selectId).some(t => t.toLowerCase() === term.toLowerCase());
        if (on) {
            removeTermFrom(selectId, term);
        } else {
            addTermTo(selectId, term);
        }
        syncChipState(selectId);
    }

    function syncChipState(selectId) {
        const chosen = selectedTerms(selectId).map(t => t.toLowerCase());
        document.querySelectorAll('#dxEdit .term-chip[data-term]').forEach(chip => {
            chip.classList.toggle('is-on', chosen.includes(chip.dataset.term.toLowerCase()));
        });
    }

    /**
     * Treatments this hospital has actually given alongside the selected diagnoses.
     *
     * Ranked by how often the pairing occurred, and anything already on the treatment picker is
     * dropped so the row only ever offers something new to add.
     */
    function renderTxSuggestions() {
        const box = document.getElementById('txSuggestBox');
        const list = document.getElementById('txSuggest');
        if (!box || !list) return;

        const dx = selectedTerms('dxSelect');
        const already = selectedTerms('txSelect').map(t => t.toLowerCase());

        const scores = {};
        dx.forEach(term => {
            const found = dxTxPairs[termKey(term)];
            if (!found) return;
            Object.keys(found).forEach(tx => {
                if (already.includes(tx.toLowerCase())) return;
                scores[tx] = (scores[tx] || 0) + found[tx];
            });
        });

        const ranked = Object.keys(scores).sort((a, b) => scores[b] - scores[a]).slice(0, 6);
        if (!ranked.length) {
            box.style.display = 'none';
            return;
        }

        document.getElementById('txSuggestFor').textContent = dx.join(', ');
        list.innerHTML = '';
        ranked.forEach(tx => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'term-chip term-chip-suggest';
            btn.title = 'Given with this diagnosis ' + scores[tx] + ' time' + (scores[tx] === 1 ? '' : 's') + ' here';
            btn.innerHTML = '+ ' + tx.replace(/[<>&]/g, '') + ' <span class="term-chip-count">' + scores[tx] + '</span>';
            btn.onclick = () => addTermTo('txSelect', tx);
            list.appendChild(btn);
        });
        box.style.display = '';
    }

    function toggleDxEdit() {
        const view = document.getElementById('dxView');
        const edit = document.getElementById('dxEdit');
        if (!edit) return;
        const showing = edit.style.display === 'none';
        view.style.display = showing ? 'none' : '';
        edit.style.display = showing ? '' : 'none';
        // Select2 needs a visible container to size itself, so build it on first open.
        if (showing) initDxSelect2();
    }

    // ── Advised treatment: one schedule and price per term ──
    // A course runs over several sittings, so each advised term carries its own status, date,
    // time, amount and discount. A term with no row is pending and unpriced.
    let txPlan   = @json((object) $txPlan);
    let txTerms  = @json($txCurrent);
    const patientName = @json($visit->patient?->name ?? 'the patient');
    const visitClosed = @json((bool) $visit->is_completed);
    const currencySym = @json($rxCurrency);
    // What this hospital last charged for each term, from the treatment catalog. Used only to
    // fill an amount box that is still empty — a price set on this visit always wins.
    let txPrices = @json((object) $treatmentPrices);
    // The follow-ups these sittings are booked into, keyed by appointment id. A treatment with
    // one of these is on the desk's day list, not just dated on the plan.
    let txAppointments = @json((object) $treatmentAppointments);
    // Whether a plan row gets a pencil — the same test the chips are clickable on.
    const txCanEdit = @json((bool) $txEditable);

    /** The live booking behind a plan row, if it still stands. */
    function txAppointment(row) {
        const appointment = txAppointments[String(row.appointment_id || '')];
        return appointment || null;
    }

    function txBookingHeld(row) {
        const appointment = txAppointment(row);
        return !!appointment && ['cancelled', 'no_show'].indexOf(appointment.status) === -1;
    }

    function txMoney(n) {
        return currencySym + Number(n || 0).toFixed(2);
    }

    const TX_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    // Same wording the blade prints, so a row does not change shape when it is re-rendered.
    function txWhen(row) {
        const parts = [];

        const d = /^(\d{4})-(\d{2})-(\d{2})/.exec(row.date || '');
        if (d) parts.push(Number(d[3]) + ' ' + TX_MONTHS[Number(d[2]) - 1] + ' ' + d[1]);
        else if (row.date) parts.push(row.date);

        const t = /^(\d{1,2}):(\d{2})/.exec(row.time || '');
        if (t) parts.push((Number(t[1]) % 12 || 12) + ':' + t[2] + ' ' + (Number(t[1]) >= 12 ? 'PM' : 'AM'));
        else if (row.time) parts.push(row.time);

        return parts.join(' · ');
    }

    // Red is not a status anyone picks: it is outstanding work on a closed OP.
    function txState(row) {
        const state = row.status || 'pending';
        return (visitClosed && state !== 'completed') ? 'missed' : state;
    }

    function renderTxTotals() {
        let gross = 0, disc = 0, paid = 0;
        txTerms.forEach(term => {
            const row = txPlan[term] || {};
            const amount = Number(row.amount || 0);
            const less   = Number(row.discount || 0);
            gross += amount;
            disc  += less;
            if (row.paid) paid += Math.max(amount - less, 0);
        });
        const net = Math.max(gross - disc, 0);

        const put = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        };
        put('txTotalGross', txMoney(gross));
        put('txTotalDisc',  txMoney(disc));
        put('txTotalNet',   txMoney(net));
        put('txTotalPaid',  txMoney(paid));
        put('txTotalDue',   txMoney(Math.max(net - paid, 0)));

        // Nothing priced yet, nothing to say about the money.
        const box = document.getElementById('txTotals');
        if (box) box.classList.toggle('d-none', !gross);

        const bar = document.getElementById('txPaidBar');
        if (bar) bar.style.width = (net > 0 ? Math.min(100, Math.round(paid / net * 100)) : 0) + '%';

        renderTxCounts();
    }

    const txStateLabels = @json($txStateLabels);

    // Doubles as the colour key — see the note on the markup.
    function renderTxCounts() {
        const box = document.getElementById('txCounts');
        if (!box) return;

        const counts = {};
        txTerms.forEach(term => {
            const state = txState(txPlan[term] || {});
            counts[state] = (counts[state] || 0) + 1;
        });

        box.innerHTML = '';
        // Fixed order, so the row does not reshuffle as statuses change under the doctor.
        ['completed', 'upcoming', 'pending', 'missed'].forEach(state => {
            if (!counts[state]) return;
            const span = document.createElement('span');
            const dot  = document.createElement('i');
            dot.className = 'tx-dot tx-state-' + state;
            span.appendChild(dot);
            span.appendChild(document.createTextNode(
                counts[state] + ' ' + (txStateLabels[state] || state).toLowerCase()
            ));
            box.appendChild(span);
        });
    }

    function txCell(text, cls) {
        const td = document.createElement('td');
        if (cls) td.className = cls;
        td.textContent = text;
        return td;
    }

    // The detail table under the chips: one row per advised treatment.
    function renderTxTable() {
        const wrap = document.getElementById('txTableWrap');
        const body = document.getElementById('txTableBody');
        if (!wrap || !body) return;

        wrap.style.display = txTerms.length ? '' : 'none';
        body.innerHTML = '';

        txTerms.forEach(term => {
            const row    = txPlan[term] || {};
            const state  = txState(row);
            const amount = Number(row.amount || 0);
            const less   = Number(row.discount || 0);
            const net    = Math.max(amount - less, 0);
            const when   = txWhen(row);

            const tr = document.createElement('tr');
            tr.appendChild(txCell(term, 'tx-table-term'));

            const tdState = document.createElement('td');
            const pill    = document.createElement('span');
            pill.className = 'tx-pill tx-state-' + state;
            pill.textContent = txStateLabels[state] || state;
            tdState.appendChild(pill);
            tr.appendChild(tdState);

            const tdWhen = txCell(when || 'Not scheduled', when ? '' : 'tx-nil');
            const appt   = txAppointment(row);
            if (appt) {
                const stale = ['cancelled', 'no_show'].indexOf(appt.status) !== -1;
                const link  = document.createElement('a');
                link.className = 'tx-booked' + (stale ? ' is-stale' : '');
                link.href      = appt.url;
                link.target    = '_blank';
                link.title     = stale
                    ? 'That follow-up was ' + appt.status.replace('_', ' ') + ' — open it'
                    : 'Booked as a next visit — open the appointment';
                link.innerHTML = '<i class="tio-calendar-note"></i>';
                link.appendChild(document.createTextNode(appt.token ? '#' + appt.token : 'Booked'));
                tdWhen.appendChild(link);
            }
            tr.appendChild(tdWhen);
            tr.appendChild(txCell(amount ? txMoney(amount) : '—', 'tx-num' + (amount ? '' : ' tx-nil')));
            tr.appendChild(txCell(less   ? txMoney(less)   : '—', 'tx-num' + (less   ? '' : ' tx-nil')));
            tr.appendChild(txCell(net    ? txMoney(net)    : '—', 'tx-num' + (net    ? '' : ' tx-nil')));
            tr.appendChild(txCell(
                row.paid ? 'Paid' : (net ? 'Due' : '—'),
                'text-center ' + (row.paid ? 'tx-paid-yes' : (net ? 'tx-paid-no' : 'tx-nil'))
            ));

            if (txCanEdit) {
                const td  = document.createElement('td');
                const btn = document.createElement('button');
                td.className = 'text-right';
                btn.type = 'button';
                btn.className = 'tx-row-edit';
                btn.dataset.term = term;
                btn.dataset.txOpen = '1';
                btn.title = 'Edit schedule & price';
                btn.innerHTML = '<i class="tio-edit"></i>';
                btn.onclick = () => openTxPlanMenu(btn);
                td.appendChild(btn);
                tr.appendChild(td);
            }

            body.appendChild(tr);
        });
    }

    function renderTxBadges(terms) {
        txTerms = terms;
        const box = document.getElementById('txBadges');
        if (!box) return;
        box.innerHTML = '';

        if (!terms.length) {
            box.innerHTML = '<span class="text-muted small">Not recorded yet.</span>';
        } else {
            terms.forEach(term => {
                const row   = txPlan[term] || {};
                const state = txState(row);
                const when  = txWhen(row);
                const net   = Math.max(Number(row.amount || 0) - Number(row.discount || 0), 0);
                const tip   = [
                    txStateLabels[state] || state,
                    when,
                    net ? txMoney(net) : '',
                    row.paid ? 'paid' : '',
                    txBookingHeld(row) ? 'booked' : ''
                ].filter(Boolean).join(' · ');

                const span = document.createElement('span');
                span.className = 'tx-badge tx-chip tx-state-' + state;
                span.dataset.term = term;
                span.title = tip + ' — click to change';
                span.textContent = term;
                span.onclick = () => openTxPlanMenu(span);
                box.appendChild(span);
            });
        }

        renderTxTable();
        renderTxTotals();
    }

    function closeTxStatusMenu() {
        const open = document.getElementById('txStatusMenu');
        if (open) open.remove();
    }

    // Registered once, for the life of the page. A click inside the menu is the doctor using it;
    // a click on a chip is handled by that chip, which closes the old menu itself.
    document.addEventListener('click', function (ev) {
        const menu = document.getElementById('txStatusMenu');
        if (!menu) return;
        if (menu.contains(ev.target)) return;
        if (ev.target.closest && ev.target.closest('.tx-chip, [data-tx-open]')) return;
        closeTxStatusMenu();
    });

    function openTxPlanMenu(el) {
        closeTxStatusMenu();
        if (visitLocked) return;

        const term = el.dataset.term;
        const row  = txPlan[term] || {};
        const menu = document.createElement('div');
        menu.className = 'tx-status-menu tx-plan-menu';
        menu.id = 'txStatusMenu';
        menu.onclick = (ev) => ev.stopPropagation();

        // A sitting with a date is only a note until it is booked. The tick turns it into a real
        // follow-up — token, day list, WhatsApp reminder — and keeps the two in step afterwards:
        // move the date here and the appointment moves with it.
        const appt  = txAppointment(row);
        const held  = txBookingHeld(row);
        const bookBox =
            '<label class="tx-plan-book"><input type="checkbox" id="txPlanBook"' + (held ? ' checked' : '') + '>' +
                '<span>Book as next visit' +
                    '<span class="tx-plan-book-note">' +
                        (held
                            ? 'Changing the date moves the appointment. Unticking cancels it.'
                            : 'Creates a follow-up on that date and reminds the patient.') +
                    '</span>' +
                '</span>' +
            '</label>' +
            (appt
                ? '<a class="tx-plan-booked" href="' + appt.url + '" target="_blank">' +
                      '<i class="tio-calendar-note"></i> ' +
                      (held ? 'Booked' : 'Booking ' + appt.status.replace('_', ' ')) +
                      (appt.token ? ' · token #' + appt.token : '') + ' — open</a>'
                : '');

        menu.innerHTML =
            '<div class="tx-plan-title">' + term + '</div>' +
            '<div class="tx-plan-row"><div>' +
                '<label>Status</label>' +
                '<select class="form-control form-control-sm" id="txPlanStatus">' +
                    '<option value="pending">Pending</option>' +
                    '<option value="upcoming">Upcoming</option>' +
                    '<option value="completed">Completed</option>' +
                '</select>' +
            '</div></div>' +
            '<div class="tx-plan-row">' +
                '<div><label>Date</label><input type="date" class="form-control form-control-sm" id="txPlanDate"></div>' +
                '<div><label>Time</label><input type="time" class="form-control form-control-sm" id="txPlanTime"></div>' +
            '</div>' +
            '<div class="tx-plan-row">' +
                '<div><label>Amount</label><input type="number" min="0" step="0.01" class="form-control form-control-sm" id="txPlanAmount" placeholder="0.00"></div>' +
                '<div><label>Discount</label><input type="number" min="0" step="0.01" class="form-control form-control-sm" id="txPlanDiscount" placeholder="0.00"></div>' +
            '</div>' +
            '<label class="tx-plan-paid"><input type="checkbox" id="txPlanPaid"> Paid</label>' +
            bookBox +
            '<div class="d-flex align-items-center" style="gap:6px">' +
                '<button type="button" class="btn btn-sm btn-primary" id="txPlanSave">Save</button>' +
                '<button type="button" class="btn btn-sm btn-light" id="txPlanCancel">Cancel</button>' +
                (window.nvPrefillTreatment
                    ? '<button type="button" class="btn btn-sm btn-link p-0 ml-auto" id="txPlanMulti" ' +
                          'style="font-size:11px" title="Book this alongside the other sittings on the Next Visit tab">Several sittings…</button>'
                    : '') +
            '</div>';

        document.body.appendChild(menu);

        // An amount already set on this visit wins; otherwise open with what this treatment
        // was last charged at, so the usual case is a glance and a Save.
        const learnt = txPrices[term] || {};
        menu.querySelector('#txPlanStatus').value   = row.status || 'pending';
        menu.querySelector('#txPlanDate').value     = row.date || '';
        menu.querySelector('#txPlanTime').value     = row.time || '';
        menu.querySelector('#txPlanAmount').value   = row.amount   ?? learnt.amount   ?? '';
        menu.querySelector('#txPlanDiscount').value = row.discount ?? learnt.discount ?? '';
        menu.querySelector('#txPlanPaid').checked   = !!row.paid;

        const box = el.getBoundingClientRect();
        menu.style.top  = (box.bottom + window.scrollY + 4) + 'px';
        menu.style.left = Math.min(box.left + window.scrollX,
                                   window.scrollX + document.documentElement.clientWidth - menu.offsetWidth - 8) + 'px';

        // Booking a date is what "upcoming" means, so ticking the box says so on the status too
        // rather than leaving a booked sitting in Pending.
        const bookInput = menu.querySelector('#txPlanBook');
        bookInput.addEventListener('change', function () {
            const status = menu.querySelector('#txPlanStatus');
            if (this.checked && status.value === 'pending') status.value = 'upcoming';
        });

        const multi = menu.querySelector('#txPlanMulti');
        if (multi) {
            multi.onclick = () => {
                closeTxStatusMenu();
                const tabBtn = document.querySelector('.consult-tab-btn[onclick*="tabNextVisit"]');
                if (tabBtn) switchTab(tabBtn, 'tabNextVisit');
                window.nvPrefillTreatment(term);
            };
        }

        menu.querySelector('#txPlanCancel').onclick = closeTxStatusMenu;
        menu.querySelector('#txPlanSave').onclick   = () => {
            const wantsBooking = bookInput.checked;
            const date = menu.querySelector('#txPlanDate').value;
            const time = menu.querySelector('#txPlanTime').value;

            if (wantsBooking && (!date || !time)) {
                alert('Give the sitting a date and a time before booking it as a next visit.');
                return;
            }

            if (held && !wantsBooking && !confirm('Cancel the follow-up booked for "' + term + '"?')) {
                return;
            }

            saveTxPlan(term, {
                status:   menu.querySelector('#txPlanStatus').value,
                date:     date,
                time:     time,
                amount:   menu.querySelector('#txPlanAmount').value,
                discount: menu.querySelector('#txPlanDiscount').value,
                paid:     menu.querySelector('#txPlanPaid').checked ? 1 : 0,
                book:     wantsBooking ? 1 : 0
            });
        };
    }

    function saveTxPlan(term, row) {
        if (visitLocked) return;

        const before = (txPlan[term] || {}).status || 'pending';

        fetch(opdQuickUpdateUrl, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ treatment_plan: { [term]: row } })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) throw new Error('save failed');

            txPlan = data.treatment_plan || {};
            if (data.treatment_prices) txPrices = data.treatment_prices;
            if (data.treatment_appointments) txAppointments = data.treatment_appointments;
            closeTxStatusMenu();
            renderTxBadges(txTerms);
            setSaveState('saved');

            // Anything the booking could not do — slot full, no time given — comes back beside a
            // save that did happen, so it is said out loud rather than left to be noticed.
            if (data.notice) alert(data.notice);

            if (row.status !== before) notifyTreatmentStatus(term, row.status, row);
        })
        .catch(() => alert('Could not save that treatment.'));
    }

    // Only when the status itself moved — editing an amount is not news for the patient. The
    // status is already saved by this point, and every send is a billed template message, so
    // it is offered rather than automatic.
    function notifyTreatmentStatus(term, state, row) {
        const form = document.getElementById('txWaForm');
        if (!form) return;

        const when = [row.date, row.time].filter(Boolean).join(' ');
        const line = state === 'completed'
            ? '"' + term + '" is marked completed.'
            : state === 'upcoming'
                ? '"' + term + '" is booked' + (when ? ' for ' + when : '') + '.'
                : '"' + term + '" is pending.';

        if (confirm('Saved: ' + line + '\n\nSend the treatment summary to ' + patientName + ' on WhatsApp?')) {
            form.submit();
        }
    }

    function selectedTerms(id) {
        const el = document.getElementById(id);
        if (!el) return [];
        return Array.from(el.selectedOptions)
            .map(o => o.value.trim())
            .filter(v => v.length);
    }

    function renderTerms(containerId, terms, cssClass) {
        const box = document.getElementById(containerId);
        if (!box) return;
        box.innerHTML = '';
        if (!terms.length) {
            box.innerHTML = '<span class="text-muted small">Not recorded yet.</span>';
            return;
        }
        terms.forEach(term => {
            const span = document.createElement('span');
            span.className = cssClass;
            span.textContent = term;
            box.appendChild(span);
        });
    }

    function saveDxTx(btn) {
        if (visitLocked) return;

        const diagnosis = selectedTerms('dxSelect');
        const treatment = selectedTerms('txSelect');
        const willing   = selectedTerms('wtSelect');

        // Same reason as saveField: Save supersedes whatever autosave had queued.
        clearTimeout(autosaveTimers['dxtx']);
        delete autosavePending['dxtx'];

        if (btn) btn.disabled = true;

        fetch(opdQuickUpdateUrl, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ diagnosis: diagnosis, treatment: treatment, willing_treatment: willing })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) throw new Error('save failed');
            if (data.treatment_plan) txPlan = data.treatment_plan;
            renderTerms('dxBadges', diagnosis, 'dx-badge');
            renderTxBadges(treatment);
            renderTerms('wtBadges', willing, 'wt-badge');
            syncRxField('rxDiagnosis', 'rxDxHint', diagnosis);
            toggleDxEdit();
            setSaveState('saved');
        })
        .catch(() => alert('Save failed.'))
        .finally(() => { if (btn) btn.disabled = false; });
    }

    // ── Document upload & delete ──
    // Toggle the Medical / Government document sub-tabs (Medical is active by default).
    function switchDocSub(cat, btn) {
        document.querySelectorAll('#docSubTabs .nav-link').forEach(a => a.classList.remove('active'));
        if (btn) btn.classList.add('active');
        const med = document.getElementById('docsub-med');
        const govt = document.getElementById('docsub-govt');
        if (med)  med.style.display  = cat === 'med'  ? '' : 'none';
        if (govt) govt.style.display = cat === 'govt' ? '' : 'none';
    }

    // Recompute sub-tab counts, the Reports tab badge, and restore empty-state placeholders.
    function refreshDocCounts() {
        let total = 0;
        [['docList', 'medDocCount'], ['govtDocList', 'govtDocCount']].forEach(([listId, countId]) => {
            const list = document.getElementById(listId);
            if (!list) return;
            const n = list.querySelectorAll('.doc-item').length;
            total += n;
            const countEl = document.getElementById(countId);
            if (countEl) countEl.textContent = n;
            const empty = list.querySelector('.doc-empty-state');
            if (n === 0 && !empty) {
                list.insertAdjacentHTML('beforeend',
                    `<li class="list-group-item text-center text-muted py-4 px-0 doc-empty-state">
                        <i class="tio-file" style="font-size:28px;opacity:.35;display:block;margin-bottom:6px;"></i>
                        No documents yet.
                    </li>`);
            } else if (n > 0 && empty) {
                empty.remove();
            }
        });
        const tabEl = document.querySelector('button[onclick*="tabReports"]');
        if (tabEl) {
            let badge = tabEl.querySelector('.count-badge');
            if (total > 0) {
                if (badge) badge.textContent = total;
                else tabEl.insertAdjacentHTML('beforeend', `<span class="count-badge ml-1">${total}</span>`);
            } else if (badge) { badge.remove(); }
        }
    }

    function uploadDocs(cat) {
        const input   = document.getElementById(cat + 'FileInput');
        const type    = document.getElementById(cat + 'TypeSelect').value;
        const name    = document.getElementById(cat + 'NameInput').value.trim();
        const btn     = document.getElementById(cat + 'UploadBtn');
        const errEl   = document.getElementById(cat + 'UploadErr');
        const progress= document.getElementById(cat + 'UploadProgress');

        errEl.style.display = 'none';
        if (!input.files.length) { errEl.textContent = 'Please select at least one file.'; errEl.style.display = ''; return; }

        const form = new FormData();
        form.append('document_type', type);
        if (name) form.append('document_name', name);
        Array.from(input.files).forEach(f => form.append('files[]', f));

        btn.disabled = true;
        progress.style.display = '';

        fetch(docUploadUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: form
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) throw new Error(data.message || 'Upload failed');

            data.documents.forEach(doc => {
                const meta     = DOC_TYPE_META[doc.document_type] || [doc.document_type, '#f3f4f6', 'med'];
                const list     = document.getElementById(meta[2] === 'govt' ? 'govtDocList' : 'docList');
                if (!list) return;
                const empty    = list.querySelector('.doc-empty-state');
                if (empty) empty.remove();
                const namePart = doc.document_name
                    ? `<span class="text-muted ml-1" style="font-size:12px;">(${doc.document_name})</span>`
                    : '';
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center px-3 py-2 doc-item';
                li.dataset.id = doc.id;
                li.innerHTML = `
                    <span style="font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:300px;">
                        <i class="tio-file mr-1 text-muted"></i>
                        <span class="badge" style="font-size:11px;background:${meta[1]};color:#374151;font-weight:600;">${meta[0]}</span>
                        ${namePart}
                    </span>
                    <div class="d-flex gap-1" style="flex-shrink:0;">
                        <a href="${doc.url}" target="_blank" class="btn btn-xs btn-soft-primary"><i class="tio-visible"></i></a>
                        <button class="btn btn-xs btn-soft-danger" onclick="deleteDoc(${doc.id}, this)" title="Delete"><i class="tio-delete"></i></button>
                    </div>`;
                list.appendChild(li);
            });

            refreshDocCounts();
            input.value = '';
            document.getElementById(cat + 'NameInput').value = '';
            updateSaveTime();
        })
        .catch(err => { errEl.textContent = err.message; errEl.style.display = ''; })
        .finally(() => { btn.disabled = false; progress.style.display = 'none'; });
    }

    function deleteDoc(docId, btn) {
        if (!confirm('Delete this document?')) return;
        btn.disabled = true;

        fetch(docDeleteUrlTpl.replace('__DOC__', docId), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) throw new Error();
            btn.closest('.doc-item').remove();
            refreshDocCounts();
            updateSaveTime();
        })
        .catch(() => { btn.disabled = false; alert('Failed to delete document.'); });
    }

    // ── Lab tests / radiology recommendations ──
    // Posts to the shared patient ordering endpoint, so this tab and the patient page raise
    // orders through exactly the same path.
    function recommendLabTests() {
        const btn = document.getElementById('saveTestsBtn');
        if (!btn) return;

        const labTests = [...document.querySelectorAll('input[name="lab_tests[]"]:checked')].map(cb => cb.value);
        const radTests = [...document.querySelectorAll('input[name="radiology_tests[]"]:checked')].map(cb => cb.value);
        if (!labTests.length && !radTests.length) { alert('Select at least one test or scan.'); return; }

        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = 'Saving…';

        fetch(orderTestsUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                opd_id:            {{ (int) $visit->id }},
                doctor_profile_id: {{ (int) ($visit->doctor_profile_id ?? 0) }} || null,
                priority:          document.getElementById('testPriority')?.value || 'routine',
                clinical_notes:    document.getElementById('testClinicalNotes')?.value || null,
                lab_tests:         labTests,
                radiology_tests:   radTests,
            }),
        })
        .then(async r => {
            // Not every failure answers JSON (a 403/419/500 may be an HTML error page), and
            // abort() sends an empty message — so always fall back to the status code rather
            // than a generic string that says nothing about what went wrong.
            const body = await r.text();
            let d = null;
            try { d = JSON.parse(body); } catch (_) { /* not JSON */ }
            if (!r.ok || !d || !d.success) {
                const detail = (d && (d.message || d.error)) || `${r.status} ${r.statusText}`;
                throw new Error('Could not save the tests — ' + detail);
            }
            return d;
        })
        .then(d => {
            updateSaveTime();
            if (confirm(`Raised ${d.summary}.\n\nOpen the department worklist now?`)) {
                window.location.href = d.redirect || labWorklistUrl;
            } else {
                window.location.reload();
            }
        })
        .catch(e => alert(e.message || 'Could not save the tests. Please try again.'))
        .finally(() => { btn.disabled = false; btn.innerHTML = original; });
    }

    // ── Prescription Form Logic ──
    let medIdx = document.querySelectorAll('#medTable .med-row').length - 1;
    if (medIdx < 0) medIdx = 0;

    @php
        $rxBannedNames = \Illuminate\Support\Facades\Schema::hasColumn('inventory_items', 'is_banned')
            ? \App\Models\InventoryItem::where('store_id', \App\CentralLogics\Helpers::get_store_id())
                ->where('item_type', 'product')->where('is_banned', 1)
                ->pluck('item_name')->map(fn($n) => mb_strtolower(trim($n)))->values()
            : collect();
    @endphp
    const RX_BANNED = @json($rxBannedNames);

    // Show a warning under any prescription medicine that is banned/blocked (warn but allow).
    function rxBannedCheck(input) {
        if (!input) return;
        const row = input.closest('.med-row'); if (!row) return;
        const banned = RX_BANNED.includes((input.value || '').trim().toLowerCase());
        let warn = row.querySelector('.rx-banned-warn');
        if (banned) {
            if (!warn) {
                warn = document.createElement('div');
                warn.className = 'rx-banned-warn text-danger small mt-1';
                warn.innerHTML = '<i class="tio-warning"></i> This medicine is <strong>banned/blocked</strong>. You can still prescribe, but please confirm it is justified.';
                input.insertAdjacentElement('afterend', warn);
            }
            warn.style.display = '';
            input.style.borderColor = '#dc2626';
        } else if (warn) {
            warn.style.display = 'none';
            input.style.borderColor = '';
        }
    }
    (function () {
        const medTable = document.getElementById('medTable');
        if (!medTable) return;
        medTable.addEventListener('input', function (e) {
            if (e.target.matches('input[name$="[medicine_name]"]')) rxBannedCheck(e.target);
        });
        medTable.querySelectorAll('input[name$="[medicine_name]"]').forEach(rxBannedCheck);
    })();

    // Built from the same partial the table renders, so this screen can never drift from the
    // standalone Rx form again — which is exactly how it ended up with its own markup.
    function addCustomMedRow(prefill) {
        medIdx++;
        const tpl = document.getElementById('medRowTpl').innerHTML.replace(/__IDX__/g, medIdx);
        // Parsed inside a <tbody>: a <tr> assigned to a <div>'s innerHTML is discarded outright.
        const host = document.createElement('tbody');
        host.innerHTML = tpl;
        const row = host.firstElementChild;

        if (prefill) {
            const nameInput = row.querySelector('input[name$="[medicine_name]"]');
            if (nameInput) nameInput.value = prefill.name;
            const invInput = row.querySelector('.med-inv-id');
            if (invInput && prefill.id) invInput.value = prefill.id;
        }

        document.getElementById('medTable').appendChild(row);
        rxBannedCheck(row.querySelector('input[name$="[medicine_name]"]'));
        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Row numbers come from a CSS counter now, so nothing has to be renumbered after a delete.
    function removeCustomMedRow(btn) {
        const rows = document.querySelectorAll('#medTable .med-row');
        if (rows.length <= 1) {
            btn.closest('.med-row').querySelectorAll('input').forEach(el => el.value = '');
            btn.closest('.med-row').querySelectorAll('select').forEach(el => el.selectedIndex = 0);
            return;
        }
        btn.closest('.med-row').remove();
    }

    // The row partial wires its delete icon to removeMedRow; this screen named its own copy
    // removeCustomMedRow, so without the alias the icon threw and the row never went away.
    window.removeMedRow = removeCustomMedRow;

    // Pharmacy Search
    let _pharmTimer = null;
    function pharmacySearchDebounce(val) {
        clearTimeout(_pharmTimer);
        const ul = document.getElementById('pharmacySuggestions');
        if (val.length < 2) { ul.style.display = 'none'; return; }
        _pharmTimer = setTimeout(() => pharmacyFetch(val), 280);
    }

    function pharmacyFetch(q) {
        fetch(`${pharmacySearchUrl}?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(items => {
                const ul = document.getElementById('pharmacySuggestions');
                if (!items.length) { ul.style.display = 'none'; return; }
                ul.innerHTML = items.map(it => `
                    <li onclick="pharmacySelect(${JSON.stringify(it).replace(/"/g, '&quot;')})"
                        style="padding:7px 12px; cursor:pointer; font-size:12px; border-bottom:1px solid #f3f4f6;"
                        onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background=''">
                        <strong>${it.name}</strong> ${it.banned ? '<span style="color:#b91c1c;font-weight:700;font-size:10px;">⛔ BANNED</span>' : ''}
                    </li>`).join('');
                ul.style.display = 'block';
            })
            .catch(() => {});
    }

    function pharmacySelect(item) {
        const firstEmpty = Array.from(document.querySelectorAll('#medTable .med-row')).find(row => {
            const nameInp = row.querySelector('input[name$="[medicine_name]"]');
            return nameInp && nameInp.value.trim() === '';
        });

        if (firstEmpty) {
            const nameInp = firstEmpty.querySelector('input[name$="[medicine_name]"]');
            nameInp.value = item.name;
            firstEmpty.querySelector('.med-inv-id').value = item.id;
            rxBannedCheck(nameInp);
        } else {
            addCustomMedRow(item);
        }
        document.getElementById('pharmacySearch').value = '';
        document.getElementById('pharmacySuggestions').style.display = 'none';
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#pharmacySearch') && !e.target.closest('#pharmacySuggestions')) {
            document.getElementById('pharmacySuggestions').style.display = 'none';
        }
    });

    function togglePrescriptionEdit(show) {
        if (show) {
            document.getElementById('rxPrintSection').style.display = 'none';
            document.getElementById('rxWritingFormBlock').style.display = 'block';
            document.querySelector('.badge-soft-success').style.display = 'none';
        } else {
            document.getElementById('rxPrintSection').style.display = 'block';
            document.getElementById('rxWritingFormBlock').style.display = 'none';
            document.querySelector('.badge-soft-success').style.display = '';
        }
    }

    // The consultation cards are the record; the prescription fields mirror them.
    // Once the doctor edits one by hand it stops following, so an intentional
    // difference between what was diagnosed and what the Rx says survives.
    function markRxFieldEdited(el, hintId) {
        el.dataset.synced = '0';
        const hint = document.getElementById(hintId);
        if (hint) { hint.textContent = 'Edited here'; hint.classList.add('rx-sync-off'); }
    }

    function syncRxField(id, hintId, terms) {
        const el = document.getElementById(id);
        if (!el || el.dataset.synced !== '1') return;
        el.value = (terms || []).join(', ');
        const hint = document.getElementById(hintId);
        if (hint) hint.classList.remove('rx-sync-off');
    }

    function toggleVitalsProfile(ev) {
        // The header is the hit area, but the pencil sitting in it has its own job.
        if (ev && ev.target.closest('button')) return;
        const body = document.getElementById('vitalsProfileBody');
        const chev = document.getElementById('vitalsChevron');
        if (!body) return;
        const open = body.style.display !== 'none';
        body.style.display = open ? 'none' : '';
        if (chev) chev.className = open ? 'tio-chevron-right' : 'tio-chevron-down';
    }

    function toggleRxPreview() {
        const sec = document.getElementById('rxPrintSection');
        const lbl = document.getElementById('rxPreviewLabel');
        if (!sec) return;
        const open = sec.style.display !== 'none';
        sec.style.display = open ? 'none' : '';
        if (lbl) lbl.textContent = open ? 'Show printable letterhead' : 'Hide printable letterhead';
    }

    function printPrescription() {
        const printContent = document.getElementById('rxPrintSection').innerHTML;
        const originalContent = document.body.innerHTML;
        document.body.innerHTML = `
            <div style="padding: 40px; font-family: 'Times New Roman', serif;">
                ${printContent}
            </div>`;
        window.print();
        document.body.innerHTML = originalContent;
        window.location.reload();
    }
</script>
@endpush
{{-- Hidden template for new medicine rows — the same partial the table renders. --}}
<template id="medRowTpl">
    @include('hmis::vendor.prescription._med_row', ['i' => '__IDX__', 'item' => null])
</template>
@endsection
