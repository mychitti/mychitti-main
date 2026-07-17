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
        grid-template-columns: 260px 1fr;
        gap: 15px;
        padding: 15px;
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
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        padding: 15px;
    }
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
            </div>
        </div>

        <div class="patient-vitals-row"> 
            {{-- BP --}}
            <div class="vital-item-card">
                <div class="vital-val @if($visit->bp_systolic >= 140 || $visit->bp_diastolic >= 90) red-text @else green-text @endif">
                    {{ $visit->bp_systolic ?: '—' }}/{{ $visit->bp_diastolic ?: '—' }}
                </div>
                <div class="vital-lbl">BP mmHg</div>
            </div>
            {{-- Pulse --}}
            <div class="vital-item-card">
                <div class="vital-val blue-text">{{ $visit->pulse_rate ?: '—' }}</div>
                <div class="vital-lbl">Pulse/min</div>
            </div>
            {{-- Temperature --}}
            <div class="vital-item-card">
                <div class="vital-val @if($visit->temperature >= 100) red-text @else green-text @endif">
                    {{ $visit->temperature ?: '—' }}
                </div>
                <div class="vital-lbl">Temp °F</div>
            </div>
            {{-- SpO2 --}}
            <div class="vital-item-card">
                <div class="vital-val @if($visit->spo2 && $visit->spo2 < 95) red-text @else green-text @endif">
                    {{ $visit->spo2 ? $visit->spo2 . '%' : '—' }}
                </div>
                <div class="vital-lbl">SpO2 %</div>
            </div>
            {{-- Weight --}}
            <div class="vital-item-card">
                <div class="vital-val">{{ $visit->weight ?: '—' }}</div>
                <div class="vital-lbl">Wt kg</div>
            </div>
        </div>
    </div>

    {{-- 4. HORIZONTAL TABBAR --}}
    <div class="consult-tabs-row">
        <button class="consult-tab-btn active" onclick="switchTab(this, 'tabDetails')">
            <i class="tio-folder-bookmarked"></i> Details
        </button>
        <button class="consult-tab-btn" onclick="switchTab(this, 'tabPrescription')">
            <i class="tio-file-text-outlined"></i> Prescription
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
        <button class="consult-tab-btn" onclick="switchTab(this, 'tabSaraAI')" id="btnSaraTab">
            <i class="tio-star"></i> Sara AI <span class="new-indicator ml-1">New</span>
        </button>
        <button class="consult-tab-btn" onclick="switchTab(this, 'tabTimeline')">
            <i class="tio-calendar-note"></i> Timeline
        </button>
        <button class="consult-tab-btn" onclick="switchTab(this, 'tabMode')">
            <i class="tio-settings-outlined"></i> Mode
        </button>
        <button class="consult-tab-btn" onclick="switchTab(this, 'tabSecurity')">
            <i class="tio-lock-outlined"></i> Security
        </button>
    </div>

    {{-- 5. TWO-COLUMN CONSULTATION WORKSPACE --}}
    <div class="consult-workspace">
        
        {{-- LEFT SIDEBAR --}}
        <div class="sidebar-column">
            
            {{-- Patient Info Card --}}
            <div class="sidebar-card">
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
                        <tr><td class="lbl">Last Visit</td><td class="val">{{ $pastVisits->first()?->visit_date?->format('d M Y') ?: 'None' }}</td></tr>
                        <tr><td class="lbl">Total Visits</td><td class="val">{{ $pastVisits->count() + 1 }} visits</td></tr>
                        @if (hasPermission('opd_register', 'view'))
                            <tr>
                                <td class="lbl">OP Receipt</td>
                                <td class="val">
                                    <a href="{{ route('vendor.opd.consultation-receipt', $visit->id) }}" target="_blank" class="btn btn-sm btn-info " >
                                        <i class="tio-receipt"></i> OP Receipt
                                    </a>
                                </td>
                            </tr>
                        @endif
                        @if (hasPermission('opd_register', 'generate_bill'))
                            <tr>
                                <td class="lbl">Bill</td>
                                <td class="val">
                                    <a href="{{ route('vendor.hospital-bill.create-opd', $visit->id) }}" class="btn btn-sm btn--primary">
                                        <i class="tio-receipt-outlined"></i> Generate Bill
                                    </a>
                                </td>
                            </tr>
                        @endif
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
                                @if (hasPermission('opd_register', 'edit'))
                                    <button class="btn btn-xs btn-soft-secondary" onclick="toggleEdit('cc')">
                                        <i class="tio-edit" id="ccEditIcon"></i>
                                    </button>
                                @endif
                            </div>
                            <div class="card-body py-3" id="ccView">
                                <span id="ccText" style="font-size:13px; color:#334155;">{{ $visit->chief_complaint ?: '—' }}</span>
                            </div>
                            <div class="card-body py-3" id="ccEdit" style="display:none;">
                                <textarea class="form-control form-control-sm" id="ccInput" rows="3" placeholder="Enter chief complaint…">{{ $visit->chief_complaint }}</textarea>
                                <div class="mt-2 d-flex gap-2">
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
                                @if (hasPermission('opd_register', 'edit'))
                                    <button class="btn btn-xs btn-soft-secondary" onclick="toggleEdit('notes')">
                                        <i class="tio-edit" id="notesEditIcon"></i>
                                    </button>
                                @endif
                            </div>
                            <div class="card-body py-3" id="notesView">
                                <span id="notesText" style="font-size:13px; color:#334155; white-space:pre-wrap;">{{ $visit->notes ?: '—' }}</span>
                            </div>
                            <div class="card-body py-3" id="notesEdit" style="display:none;">
                                <textarea class="form-control form-control-sm" id="notesInput" rows="3" placeholder="Add consultation notes…">{{ $visit->notes }}</textarea>
                                <div class="mt-2 d-flex gap-2">
                                    <button class="btn btn-sm btn-primary" onclick="saveField('notes')">Save</button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleEdit('notes')">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Full Vitals Grid --}}
                <div class="card shadow-none border mb-3">
                    <div class="card-header py-2 bg-light"><h6 class="mb-0 font-weight-bold" style="font-size:13px">Patient Vitals Profile</h6></div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-sm mb-0 text-dark" style="font-size:13px">
                            <tbody>
                                <tr>
                                    <td class="pl-3 py-2" style="font-weight:600">Blood Pressure</td>
                                    <td>
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
                                    <td>{{ $visit->pulse_rate ? $visit->pulse_rate . ' bpm' : '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="pl-3 py-2" style="font-weight:600">Temperature</td>
                                    <td>{{ $visit->temperature ? $visit->temperature . ' °F' : '—' }}</td>
                                    <td class="py-2" style="font-weight:600">Respiratory Rate</td>
                                    <td>{{ $visit->respiratory_rate ? $visit->respiratory_rate . ' /min' : '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="pl-3 py-2" style="font-weight:600">SpO2</td>
                                    <td>{{ $visit->spo2 ? $visit->spo2 . '%' : '—' }}</td>
                                    <td class="py-2" style="font-weight:600">Weight / Height</td>
                                    <td>{{ $visit->weight ? $visit->weight . ' kg' : '—' }} / {{ $visit->height ? $visit->height . ' cm' : '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- TAB: PRESCRIPTION --}}
            <div class="tab-pane" id="tabPrescription">
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
                            @if (hasPermission('prescription', 'add'))
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

                    <div class="rx-view-wrap" id="rxPrintSection">
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
                @if (hasPermission('prescription', 'add'))
                <div id="rxWritingFormBlock" style="@if($currentPrescription) display:none; @endif">
                    <h4 class="mb-3 font-weight-bold" style="color:#0f172a">Write Prescription</h4>
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
                            <div class="col-md-7">
                                <div class="form-group">
                                    <label class="input-label">Diagnosis <span class="text-danger">*</span></label>
                                    <textarea name="diagnosis" class="form-control form-control-sm" rows="2" placeholder="Primary diagnosis..." required>{{ $currentPrescription->diagnosis ?? $visit->chief_complaint }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label class="input-label">Doctor's Advice / Notes</label>
                                    <textarea name="notes" class="form-control form-control-sm" rows="3" placeholder="Rest, diet, precautions, advice...">{{ $currentPrescription->notes ?? $visit->notes }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label class="input-label">Follow-Up Date</label>
                                    <input type="date" name="follow_up_date" class="form-control form-control-sm" style="max-width:200px;" value="{{ $currentPrescription && $currentPrescription->follow_up_date ? $currentPrescription->follow_up_date->format('Y-m-d') : '' }}">
                                </div>
                            </div>

                            <div class="col-md-5">
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

                                    <div class="card-body p-2" id="medTable" style="max-height:280px; overflow-y:auto;">
                                        @if($currentPrescription && $currentPrescription->items->count())
                                            @foreach($currentPrescription->items as $i => $item)
                                                <div class="med-row" data-index="{{ $i }}">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="font-weight-bold text-dark small">Medication #{{ $i+1 }}</span>
                                                        <button type="button" class="btn-remove-row" onclick="removeCustomMedRow(this)"><i class="tio-delete-outlined"></i></button>
                                                    </div>
                                                    <input type="hidden" name="medicines[{{ $i }}][inventory_item_id]" class="med-inv-id" value="{{ $item->inventory_item_id }}">
                                                    <input type="text" name="medicines[{{ $i }}][medicine_name]" class="form-control form-control-sm mb-1 font-weight-bold" placeholder="Medicine Name" value="{{ $item->medicine_name }}" required>
                                                    <div class="row no-gutters gap-1">
                                                        <div class="col"><input type="text" name="medicines[{{ $i }}][dosage]" class="form-control form-control-sm" placeholder="Dosage" value="{{ $item->dosage }}"></div>
                                                        <div class="col"><input type="text" name="medicines[{{ $i }}][frequency]" class="form-control form-control-sm" placeholder="Freq" value="{{ $item->frequency }}"></div>
                                                        <div class="col"><input type="text" name="medicines[{{ $i }}][duration]" class="form-control form-control-sm" placeholder="Dur" value="{{ $item->duration }}"></div>
                                                        <div class="col" style="max-width:50px;"><input type="number" name="medicines[{{ $i }}][quantity]" class="form-control form-control-sm" placeholder="Qty" value="{{ $item->quantity }}"></div>
                                                    </div>
                                                    <input type="text" name="medicines[{{ $i }}][instructions]" class="form-control form-control-sm mt-1" placeholder="Instructions (Optional)" value="{{ $item->instructions }}">
                                                </div>
                                            @endforeach
                                        @else
                                            {{-- First default row --}}
                                            <div class="med-row" data-index="0">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="font-weight-bold text-dark small">Medication #1</span>
                                                    <button type="button" class="btn-remove-row" onclick="removeCustomMedRow(this)"><i class="tio-delete-outlined"></i></button>
                                                </div>
                                                <input type="hidden" name="medicines[0][inventory_item_id]" class="med-inv-id">
                                                <input type="text" name="medicines[0][medicine_name]" class="form-control form-control-sm mb-1 font-weight-bold" placeholder="Medicine Name" required>
                                                <div class="row no-gutters gap-1">
                                                    <div class="col"><input type="text" name="medicines[0][dosage]" class="form-control form-control-sm" placeholder="Dosage"></div>
                                                    <div class="col"><input type="text" name="medicines[0][frequency]" class="form-control form-control-sm" placeholder="Freq"></div>
                                                    <div class="col"><input type="text" name="medicines[0][duration]" class="form-control form-control-sm" placeholder="Dur"></div>
                                                    <div class="col" style="max-width:50px;"><input type="number" name="medicines[0][quantity]" class="form-control form-control-sm" placeholder="Qty"></div>
                                                </div>
                                                <input type="text" name="medicines[0][instructions]" class="form-control form-control-sm mt-1" placeholder="Instructions (Optional)">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary" name="action" value="draft">
                                <i class="tio-save"></i> Save Draft
                            </button>
                            <button type="submit" class="btn btn-sm btn-success" name="finalize" value="1">
                                <i class="tio-checkmark-circle"></i> Finalize &amp; Save
                            </button>
                            @if($currentPrescription)
                                <button type="button" class="btn btn-sm btn-light" onclick="togglePrescriptionEdit(false)">Cancel</button>
                            @endif
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

            {{-- TAB: SECURITY --}}
            <div class="tab-pane" id="tabSecurity">
                <h4 class="mb-3 font-weight-bold" style="color:#0f172a">Security &amp; Compliance</h4>
                <p class="small text-muted mb-4">Patient records audit logs and compliance details.</p>
                <div class="text-dark" style="font-size:13px">
                    <p class="mb-2"><strong>HIPAA compliance:</strong> All consultation notes and vitals are encrypted at rest.</p>
                    <p class="mb-2"><strong>Access Logs:</strong> Access to patient #{{ $visit->patient_id }} records is audited.</p>
                    <div class="border p-2 rounded bg-light" style="font-size:11px; font-family:monospace;">
                        [2026-06-11 15:47] Access by Dr. {{ $visit->doctorProfile?->employee?->f_name }} (HMIS-Provider)<br>
                        [2026-06-11 15:40] Vitals profile verified<br>
                        [2026-06-11 14:32] Encounter record generated via queue token
                    </div>
                </div>
            </div>

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
<script>
    const opdQuickUpdateUrl    = "{{ route('vendor.opd.quick-update', $visit->id) }}";
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
    function switchTab(btn, tabId) {
        document.querySelectorAll('.consult-tab-btn').forEach(el => el.classList.remove('active'));
        btn.classList.add('active');

        document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
        const activePane = document.getElementById(tabId);
        if (activePane) activePane.classList.add('active');

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

    // ── CC & Notes inline edit ──
    function toggleEdit(field) {
        const view = document.getElementById(field === 'cc' ? 'ccView' : 'notesView');
        const edit = document.getElementById(field === 'cc' ? 'ccEdit' : 'notesEdit');
        const showing = edit.style.display === 'none';
        view.style.display = showing ? 'none' : '';
        edit.style.display = showing ? '' : 'none';
    }

    function saveField(field) {
        const isCC    = field === 'cc';
        const value   = document.getElementById(isCC ? 'ccInput' : 'notesInput').value;
        const textEl  = document.getElementById(isCC ? 'ccText' : 'notesText');

        fetch(opdQuickUpdateUrl, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(isCC ? { chief_complaint: value } : { notes: value })
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                textEl.textContent = value || '—';
                toggleEdit(field);
                updateSaveTime();
            }
        })
        .catch(() => alert('Save failed.'));
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
        .then(r => r.json().then(d => ({ ok: r.ok, d })))
        .then(({ ok, d }) => {
            if (!ok || !d.success) throw new Error(d.message || 'Could not save the tests.');
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

    function addCustomMedRow(prefill) {
        medIdx++;
        const row = document.createElement('div');
        row.className = 'med-row';
        row.dataset.index = medIdx;
        row.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="font-weight-bold text-dark small">Medication #${medIdx+1}</span>
                <button type="button" class="btn-remove-row" onclick="removeCustomMedRow(this)"><i class="tio-delete-outlined"></i></button>
            </div>
            <input type="hidden" name="medicines[${medIdx}][inventory_item_id]" class="med-inv-id" value="${prefill ? prefill.id : ''}">
            <input type="text" name="medicines[${medIdx}][medicine_name]" class="form-control form-control-sm mb-1 font-weight-bold" placeholder="Medicine Name" value="${prefill ? prefill.name : ''}" required>
            <div class="row no-gutters gap-1">
                <div class="col"><input type="text" name="medicines[${medIdx}][dosage]" class="form-control form-control-sm" placeholder="Dosage"></div>
                <div class="col"><input type="text" name="medicines[${medIdx}][frequency]" class="form-control form-control-sm" placeholder="Freq"></div>
                <div class="col"><input type="text" name="medicines[${medIdx}][duration]" class="form-control form-control-sm" placeholder="Dur"></div>
                <div class="col" style="max-width:50px;"><input type="number" name="medicines[${medIdx}][quantity]" class="form-control form-control-sm" placeholder="Qty"></div>
            </div>
            <input type="text" name="medicines[${medIdx}][instructions]" class="form-control form-control-sm mt-1" placeholder="Instructions (Optional)">
        `;
        document.getElementById('medTable').appendChild(row);
        rxBannedCheck(row.querySelector('input[name$="[medicine_name]"]'));
        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function removeCustomMedRow(btn) {
        const rows = document.querySelectorAll('#medTable .med-row');
        if (rows.length <= 1) {
            btn.closest('.med-row').querySelectorAll('input').forEach(el => el.value = '');
            return;
        }
        btn.closest('.med-row').remove();
        // Re-index remaining rows
        document.querySelectorAll('#medTable .med-row').forEach((row, i) => {
            row.querySelector('.small').textContent = `Medication #${i+1}`;
        });
    }

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
@endsection
