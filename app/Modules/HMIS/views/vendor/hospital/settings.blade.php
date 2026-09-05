@extends('layouts.vendor.app')
@section('title', 'Hospital Settings')

@push('css_or_js')
<style>
    .opt-row {
        display: flex; align-items: center; gap: 8px;
        border: 1px solid #eef0f5; border-radius: 8px;
        padding: 5px 10px; margin: 0 6px 6px 0;
    }
    .opt-row:hover { border-color: #bfdbfe; background: #f8fbff; }
    .opt-name { flex: 1; min-width: 0; font-size: 12.5px; font-weight: 600; color: #1f2937; }
    .opt-name.off { color: #9aa5b1; text-decoration: line-through; font-weight: 500; }
    .opt-empty { color: #94a3b8; font-size: 12px; padding: 10px 2px; }

    /* ── Settings tabs ── */
    /* One grid, so every row's controls sit under the same heading instead of each row
       arranging itself and the eye having to re-find them five times. */
    .lh-grid {
        display: grid;
        grid-template-columns: minmax(240px, 1fr) 88px 104px 186px 96px;
        gap: 14px; align-items: center;
    }
    .lh-head {
        padding-bottom: 7px; border-bottom: 1px solid #e6ebf2;
        font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
        color: #94a3b8;
    }
    .lh-row { padding: 11px 0; border-bottom: 1px solid #f1f5f9; }
    .lh-row:last-child { border-bottom: 0; padding-bottom: 0; }
    .lh-c { text-align: center; justify-self: center; }
    .lh-doc { min-width: 0; }
    .lh-name { font-size: 13px; font-weight: 600; color: #0f172a; }
    .lh-hint { font-size: 11.5px; color: #94a3b8; line-height: 1.4; margin-top: 1px; }
    .lh-sec {
        display: inline-flex; align-items: center; gap: 6px; margin: 6px 0 0;
        font-size: 12px; font-weight: 400; color: #475569; cursor: pointer;
    }
    .lh-mm { display: inline-flex; align-items: center; gap: 5px; }
    .lh-mm input { width: 62px; text-align: center; }
    .lh-mm span { font-size: 11px; color: #94a3b8; }

    /* Below the grid's comfortable width it becomes stacked rows, each control carrying the
       heading it lost. */
    @media (max-width: 991px) {
        .lh-head { display: none; }
        .lh-grid { grid-template-columns: 1fr; gap: 8px; }
        .lh-row { padding: 14px 0; }
        .lh-c { text-align: left; justify-self: start; }
        .lh-grid > [data-lh-label]::before {
            content: attr(data-lh-label) '  ';
            font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
            color: #94a3b8; margin-right: 8px;
        }
        .lh-grid > [data-lh-label] { display: flex; align-items: center; }
    }

    .hs-sig-row {
        display: flex; align-items: center; gap: 14px;
        padding: 9px 0; border-top: 1px solid #eef2f7;
    }
    .hs-sig-img {
        flex: 0 0 120px; height: 34px; max-width: 120px;
        object-fit: contain; object-position: left center;
    }
    .hs-sig-name { flex: 1 1 auto; min-width: 0; font-size: 13px; font-weight: 600; color: #0f172a; }
    .hs-sig-src {
        margin-left: 7px; padding: 1px 7px; border-radius: 4px;
        background: #f1f5f9; color: #64748b;
        font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px;
    }
    .hs-sig-default {
        flex: 0 0 auto; display: flex; align-items: center; gap: 6px; margin: 0;
        font-size: 12px; font-weight: 400; color: #64748b; cursor: pointer;
    }
    .hs-sig-del {
        flex: 0 0 64px; text-align: right;
        border: 0; background: none; padding: 0; cursor: pointer;
        font-size: 12px; font-weight: 600; color: #94a3b8;
    }
    .hs-sig-del:hover { color: #dc2626; text-decoration: underline; }
    .hs-sig-del.is-off { color: #cbd5e1; cursor: default; }
    .hs-sig-del.is-off:hover { color: #cbd5e1; text-decoration: none; }

    .hs-tabbar {
        display: flex; flex-wrap: wrap; gap: 2px;
        border-bottom: 1px solid #c8d2e0; margin-bottom: 18px;
    }
    .hs-tab {
        padding: 9px 14px; font-size: 12.5px; font-weight: 600; color: #64748b !important;
        border-bottom: 2px solid transparent; text-decoration: none !important; white-space: nowrap;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .hs-tab:hover { color: #0f172a !important; background: #f8fafc; }
    .hs-tab.active { color: #2563eb !important; border-bottom-color: #2563eb; }
    .hs-tab i { font-size: 15px; }
    /* A tab whose pane holds a field the server rejected. Marked rather than switched to, so a
       second error somewhere else is still findable after the first is fixed. */
    .hs-tab.has-error { color: #dc2626 !important; }
    .hs-tab.has-error::after {
        content: ''; width: 5px; height: 5px; border-radius: 50%; background: #dc2626;
    }

    /* Hidden, not removed: these panes are inside the settings form, and a pane that is not in
       the DOM does not post its fields — switching tabs would silently blank every setting on
       every other tab.

       The first pane is marked active in the markup rather than by the script, so the page paints
       one tab straight away instead of flashing all nine while the footer scripts load. */
    .hs-pane { display: none; }
    .hs-pane.active { display: block; }

    /* Settings read as prose with a few short answers in them, and a form field stretched across
       an ultrawide monitor is neither. Capped here rather than per card so every pane lines up on
       the same left edge; the two that genuinely need the width say so. */
    .hs-pane { max-width: 900px; }
    .hs-pane[data-hs-pane="opTypes"],
    .hs-pane[data-hs-pane="departments"],
    .hs-pane[data-hs-pane="opd"],
    .hs-pane[data-hs-pane="clinical"] { max-width: none; }
    /* Number fields stay legible whatever the card does around them. */
    .hs-num { max-width: 240px; }
    /* Side by side, the inputs sit on one line even where one label wrapped and the other did
       not — the label reserves two lines' worth either way, so nothing steps down. */
    .hs-vf { display: flex; flex-direction: column; }
    .hs-vf > .input-label { margin-bottom: 5px; }
    .hs-vf > small { margin-top: 6px; line-height: 1.45; }
    @media (min-width: 1200px) {
        .hs-vf > .input-label { min-height: 2.6em; }
    }
    /* Checkbox settings carry a paragraph explaining what they do, which wants a shorter measure
       than a form row does. */
    .hs-pane .card-body > label small { max-width: 62ch; }

    @media (max-width: 575px) {
        .hs-tab { padding: 8px 10px; font-size: 12px; }
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-settings-outlined" style="font-size:22px;"></i></span>
            Hospital Settings
        </h1>
    </div>

    {{-- One page, nine unrelated decisions. Tabs rather than a column of cards because nobody
         comes here to read all of it — they come to change one thing, and a stack that scrolls
         past eight cards they did not want is how the ninth never gets found.

         The bar is written once, from this list, so a new settings card means an entry here and a
         pane below rather than markup in three places. --}}
    @php
        $hsTabs = [
            'ids'          => ['label' => 'Patient IDs',       'icon' => 'tio-label'],
            'opd'          => ['label' => 'OP, Prescriptions & Printing','icon' => 'tio-receipt'],
            'clinical'     => ['label' => 'Clinical & Reports','icon' => 'tio-heart-outlined'],
            'discontinue'  => ['label' => 'Abandoned Care',    'icon' => 'tio-time'],
            'opTypes'      => ['label' => 'OP Types',          'icon' => 'tio-card'],
            'departments'  => ['label' => 'Departments',       'icon' => 'tio-city'],
        ];
    @endphp

    {{-- Settings hidden behind a tab bar that never switches would be settings nobody can reach,
         so with scripting off the bar goes and every pane is simply shown, which is the page
         exactly as it was before the tabs. --}}
    <noscript>
        <style>
            .hs-tabbar { display: none; }
            .hs-pane { display: block !important; }
        </style>
    </noscript>

    <div class="hs-tabbar">
        @foreach($hsTabs as $hsKey => $hsTab)
            <a href="#{{ $hsKey }}" class="hs-tab {{ $loop->first ? 'active' : '' }}" data-hs-tab="{{ $hsKey }}">
                <i class="{{ $hsTab['icon'] }}"></i> {{ $hsTab['label'] }}
            </a>
        @endforeach
    </div>

    <form action="{{ route('vendor.hospital.settings.save') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-12">

                {{-- MUID Format --}}
                <div class="hs-pane active" data-hs-pane="ids">
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-label mr-1"></i> MUID Format</h6>
                    </div>
                    <div class="card-body">

                        {{-- Three short fields that describe one identifier, so they sit on one
                             line rather than three full-width boxes a monitor wide. --}}
                        <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="input-label">Prefix <span class="text-danger">*</span></label>
                            <input type="text" name="prefix" class="form-control @error('prefix') is-invalid @enderror"
                                   value="{{ old('prefix', $prefix) }}" maxlength="10"
                                   placeholder="e.g. P, MH, PAT"
                                   oninput="updatePreview()" id="prefixInput">
                            <small class="text-muted">Letters, numbers, hyphens and underscores only.</small>
                            @error('prefix')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group col-md-4">
                            <label class="input-label">Zero-padding digits <span class="text-danger">*</span></label>
                            <input type="number" name="padding" class="form-control @error('padding') is-invalid @enderror"
                                   value="{{ old('padding', $padding) }}" min="1" max="10"
                                   oninput="updatePreview()" id="paddingInput">
                            <small class="text-muted">Number of digits in the serial (e.g. 5 → 00001).</small>
                            @error('padding')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group col-md-4 mb-0">
                            <label class="input-label">Minimum serial number <span class="text-danger">*</span></label>
                            <input type="number" name="serial" class="form-control @error('serial') is-invalid @enderror"
                                   value="{{ old('serial', $serial) }}" min="1"
                                   oninput="updatePreview()" id="serialInput">
                            <small class="text-muted">New patients will start from this number (if current count is lower).</small>
                            @error('serial')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        </div>

                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted" style="font-size:12px;">Next MUID preview: </span>
                            <code id="muidPreview" style="font-size:14px; font-weight:700; color:#1d4ed8;">{{ $previewMuid }}</code>
                        </div>
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </div>

                </div>{{-- /pane ids --}}

                {{-- Clinical Recording — what this hospital actually charts. A dental or
                     physiotherapy practice never takes a BP, and an always-on vitals card is a
                     row of dashes on every screen it appears on. --}}
                {{-- What a prescription carries, whether vitals and lab work are tracked, and
                     whether chart access is logged: four answers to one question — what this
                     hospital records — plus the daily summary of it. One tab, not four. --}}
                <div class="hs-pane" data-hs-pane="clinical">
                <div class="row">

                <div class="col-xl-7 col-lg-6 d-flex">
                <div class="card mb-3 w-100">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-heart-outlined mr-1"></i> What this hospital records</h6>
                    </div>
                    <div class="card-body">
                        <label class="d-flex align-items-start mb-3" style="cursor:pointer;">
                            <input type="checkbox" name="rx_print_clinical" value="1" class="mr-2 mt-1"
                                   {{ old('rx_print_clinical', $rx_print_clinical) ? 'checked' : '' }}>
                            <span>
                                <span style="font-weight:600;">Print prescription with diagnosis and doctor's advice</span>
                                <small class="text-muted d-block" style="font-size:12px;">
                                    Switch off and the printed sheet carries only the medicines — the
                                    condition and the advice are left off it, and off the prescription
                                    card on the consultation screen too, so what your staff see is what
                                    the patient is handed. Both are still recorded on the visit either way.
                                </small>
                            </span>
                        </label>

                        <label class="d-flex align-items-start mb-3" style="cursor:pointer;">
                            <input type="checkbox" name="vitals_enabled" value="1" class="mr-2 mt-1"
                                   {{ old('vitals_enabled', $vitals_enabled) ? 'checked' : '' }}>
                            <span>
                                <span style="font-weight:600;">Record patient vitals</span>
                                <small class="text-muted d-block" style="font-size:12px;">
                                    BP, pulse, temperature, SpO2, respiratory rate, weight and height.
                                    Switch off and the vitals fields and cards are hidden across OPD,
                                    the consultation page, patient records and the nursing station.
                                    Readings already saved are kept.
                                </small>
                            </span>
                        </label>

                        <label class="d-flex align-items-start mb-3" style="cursor:pointer;">
                            <input type="checkbox" name="lab_work_enabled" value="1" class="mr-2 mt-1"
                                   {{ old('lab_work_enabled', $lab_work_enabled) ? 'checked' : '' }}>
                            <span>
                                <span style="font-weight:600;">Track lab work, in-house or sent out</span>
                                <small class="text-muted d-block" style="font-size:12px;">
                                    Adds a Lab Work tab to the consultation screen for crowns, dentures,
                                    lenses, ear moulds and appliances — measurements, whether your own
                                    technician is making it or an outside lab, who handed it over and who
                                    collected it, what stage it has reached, and a WhatsApp update to the
                                    patient when it is ready.
                                </small>
                                <small class="d-block mt-2" style="font-size:11.5px;">
                                    <span class="text-muted">Measurements and stage names used:</span>
                                    <span class="text-dark" style="font-weight:600;">{{ $lab_work_profile['label'] }}</span>
                                    <span class="text-muted">
                                        ({{ implode(', ', array_map(fn($f) => $f['label'], $lab_work_profile['fields'])) }})
                                    </span>
                                </small>
                                @if($lab_work_auto)
                                    <small class="d-block text-muted mt-1" style="font-size:11.5px;">
                                        On by default for your hospital category. Untick to hide it.
                                    </small>
                                @else
                                    <small class="d-block text-muted mt-1" style="font-size:11.5px;">
                                        Set the hospital category on your store profile to get the
                                        measurement set for your speciality instead of the general one.
                                    </small>
                                @endif
                            </span>
                        </label>

                        <label class="d-flex align-items-start mb-0" style="cursor:pointer;">
                            <input type="checkbox" name="security_tab_enabled" value="1" class="mr-2 mt-1"
                                   {{ old('security_tab_enabled', $security_tab_enabled) ? 'checked' : '' }}>
                            <span>
                                <span style="font-weight:600;">Show Security &amp; Compliance tab</span>
                                <small class="text-muted d-block" style="font-size:12px;">
                                    Adds a Security tab to the consultation screen listing who opened and
                                    who edited this patient's records — registrations, consultations,
                                    prescriptions, appointments and admissions.
                                    Switching this on is what starts recording chart access, so the trail
                                    begins from today and earlier visits show only what was already logged.
                                    Off, nothing is recorded and the tab is hidden.
                                </small>
                            </span>
                        </label>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </div>


                </div>

                <div class="col-xl-5 col-lg-6 d-flex">
                <div class="card mb-3 w-100">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-chart-bar-4 mr-1"></i> Daily Report on WhatsApp</h6>
                    </div>
                    <div class="card-body">
                        <label class="d-flex align-items-start mb-0" style="cursor:pointer;">
                            <input type="checkbox" name="daily_report_enabled" value="1" class="mr-2 mt-1"
                                   id="dailyReportToggle"
                                   {{ old('daily_report_enabled', $daily_report['enabled']) ? 'checked' : '' }}>
                            <span>
                                <span style="font-weight:600;">Do you need daily reports?</span>
                                <small class="text-muted d-block" style="font-size:12px;">
                                    One WhatsApp message at the end of each day with the numbers you pick below.
                                    @if($daily_report_phone)
                                        It goes to <strong>{{ $daily_report_phone }}</strong> — the number on your
                                        store profile. Change it there to send it somewhere else.
                                    @else
                                        Add a phone number to your store profile first — there is nowhere to send it yet.
                                    @endif
                                </small>
                            </span>
                        </label>

                        <div id="dailyReportOptions" class="mt-3 pl-4"
                             style="{{ old('daily_report_enabled', $daily_report['enabled']) ? '' : 'display:none;' }}">
                            <div class="mb-2" style="font-size:12px; font-weight:600; color:#475569;">
                                What to include
                            </div>
                            <div class="row">
                                @foreach($daily_report_metrics as $key => $label)
                                    <div class="col-sm-6 col-md-4 mb-2">
                                        <label class="d-flex align-items-center mb-0" style="cursor:pointer; font-size:13px;">
                                            <input type="checkbox" name="daily_report_metrics[]" value="{{ $key }}"
                                                   class="mr-2"
                                                   {{ in_array($key, old('daily_report_metrics', $daily_report['metrics'])) ? 'checked' : '' }}>
                                            {{ $label }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="d-flex flex-wrap align-items-end" style="gap:14px;">
                                <div class="form-group mt-2 mb-0" style="max-width:190px;">
                                    <label class="input-label" style="font-size:12px;">Send at</label>
                                    <input type="time" name="daily_report_time" class="form-control form-control-sm"
                                           value="{{ old('daily_report_time', $daily_report['time']) }}">
                                    <small class="text-muted" style="font-size:11px;">
                                        Sent on the hour after this time. A day with nothing to report is skipped.
                                    </small>
                                </div>

                                {{-- Sends today's figures on the spot, ignoring the hour and the
                                     quiet-day skip, so the vendor can see what will arrive. --}}
                                <div class="form-group mt-2 mb-0">
                                    <button type="submit" class="btn btn-sm btn-outline-primary"
                                            formaction="{{ route('vendor.hospital.daily-report.test') }}"
                                            formnovalidate>
                                        <i class="tio-send"></i> Send me a test now
                                    </button>
                                    <small class="text-muted d-block" style="font-size:11px;">
                                        Uses today's figures so far. Save your settings first.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </div>
                </div>
                </div>{{-- /row --}}
                </div>{{-- /pane clinical --}}

                {{-- Printed documents. A clinic that has its letterhead printed on the pad does
                     not want it printed again on top, and the blank run of paper is what keeps the
                     text clear of what is already there. Per document rather than per hospital:

                {{-- Daily report — a summary of the day on WhatsApp, off unless asked for.
                     Sent from the platform's number rather than the hospital's: this is MyChitti
                     reporting to its customer, not the hospital messaging a patient, so it works
                     whether or not they have connected WhatsApp themselves. --}}


                <div class="hs-pane" data-hs-pane="discontinue">
                {{-- Discontinuing care nobody came back for. On everywhere at the platform's 30
                     days unless a hospital says otherwise, so what it closes is spelled out in
                     full and the box is ticked when they arrive: this runs unattended, and
                     somebody reading this card should never be surprised by it later. --}}
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-time mr-1"></i> Discontinue Abandoned Care</h6>
                    </div>
                    <div class="card-body">
                        <label class="d-flex align-items-start mb-0" style="cursor:pointer;">
                            <input type="checkbox" name="discontinue_enabled" value="1" class="mr-2 mt-1" id="discontinueOn"
                                   {{ old('discontinue_enabled', $discontinue_days ? 1 : 0) ? 'checked' : '' }}
                                   onchange="document.getElementById('discontinueDaysWrap').style.display = this.checked ? '' : 'none';">
                            <span>
                                <span style="font-weight:600;">Close outstanding care when a patient stops coming</span>
                                <small class="text-muted d-block" style="font-size:12px;">
                                    Runs once a night. A patient who has not been in for the number of days
                                    below, and has no future appointment booked, has whatever is still open
                                    on their visits marked <b>Discontinued</b>: planned treatments still
                                    waiting to be done, lab work still out, and follow-up appointments whose
                                    date has passed with nobody attending.
                                </small>
                                <small class="text-muted d-block mt-2" style="font-size:11.5px;">
                                    Nothing is deleted and no bill, receipt or completed treatment is
                                    touched. Every close is written to the activity log, and a job or a
                                    treatment can be moved back to any stage if the patient returns.
                                </small>
                                <small class="d-block mt-1" style="font-size:11.5px;">
                                    <span class="text-muted">On by default at</span>
                                    <span class="text-dark" style="font-weight:600;">{{ \App\Services\OpdDiscontinue::DEFAULT_DAYS }} days</span><span class="text-muted">.
                                    Change the number to suit your speciality, or untick to leave your
                                    records open indefinitely — a practice working to six-month recalls
                                    should switch this off.</span>
                                </small>
                            </span>
                        </label>

                        <div class="form-row mt-3" id="discontinueDaysWrap" style="display:{{ old('discontinue_enabled', $discontinue_days ? 1 : 0) ? '' : 'none' }};">
                            <div class="form-group col-md-4 mb-0">
                                <label class="input-label" style="font-size:12px;">Days without a visit</label>
                                <input type="number" name="discontinue_days" class="form-control form-control-sm"
                                       min="7" max="365"
                                       value="{{ old('discontinue_days', $discontinue_days ?: \App\Services\OpdDiscontinue::DEFAULT_DAYS) }}">
                                <small class="text-muted" style="font-size:11px;">
                                    Between 7 and 365. Counted from the patient's most recent visit, not from
                                    the visit being closed.
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </div>

                </div>{{-- /pane discontinue --}}

                {{-- Records Access & Audit — off unless a hospital asks for it. Switching it on
                     is what starts writing the trail, not just what reveals the tab, so a clinic
                     that will never read it never accumulates the rows. --}}

                {{-- OP Consultation Validity --}}
                <div class="hs-pane" data-hs-pane="opd">
                <div class="row">

                <div class="col-xl-5 col-lg-6 d-flex">
                <div class="card mb-3 w-100">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-receipt mr-1"></i> OP Consultation Validity</h6>
                    </div>
                    <div class="card-body">
                        {{-- Two halves of one rule — how many visits, for how long — so they are
                             read together rather than stacked a screen apart. --}}
                        {{-- col-xl-6, not col-md-4: this card is itself in a half-width column, and
                             an inner column sized against the viewport does not know that. At lg the
                             card halved while the fields did not, so the longer label wrapped, its
                             input dropped a line, and the two stopped lining up. --}}
                        <div class="form-row">
                        <div class="form-group col-xl-6 col-12 hs-vf">
                            <label class="input-label">Consultations per paid OP <span class="text-danger">*</span></label>
                            <input type="number" name="opd_consultation_count"
                                   class="form-control hs-num @error('opd_consultation_count') is-invalid @enderror"
                                   value="{{ old('opd_consultation_count', $opd_consultation_count) }}" min="1" max="50">
                            <small class="text-muted">How many consultations one paid OP receipt covers (e.g. 2).</small>
                            @error('opd_consultation_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group col-xl-6 col-12 mb-0 hs-vf">
                            <label class="input-label">Validity (days) <span class="text-danger">*</span></label>
                            <input type="number" name="opd_consultation_validity_days"
                                   class="form-control hs-num @error('opd_consultation_validity_days') is-invalid @enderror"
                                   value="{{ old('opd_consultation_validity_days', $opd_consultation_validity_days) }}" min="1" max="365">
                            <small class="text-muted">Days a paid OP stays valid for follow-up visits (e.g. 7 = 1 week).</small>
                            @error('opd_consultation_validity_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        </div>

                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </div>


                </div>

                {{-- Prescription languages, on a card of their own: what a paid OP receipt covers
                     and which scripts a doctor may write in are two unrelated decisions, and one
                     card holding both is why this tab read as a wall. --}}
                <div class="col-xl-7 col-lg-6 d-flex">
                <div class="card mb-3 w-100">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-globe mr-1"></i> Prescription Languages</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3" style="font-size:12px;">
                            Tick the languages your doctors write prescriptions in. Only these appear in
                            the language dropdown on the prescription screen, so a clinic that writes
                            English and Tamil never scrolls past twenty others.
                        </p>
                            <div class="row no-gutters" style="max-height:220px; overflow-y:auto;">
                                @foreach (\App\Models\Prescription::LANGUAGES as $code => $label)
                                    <div class="col-md-6">
                                        <label class="d-flex align-items-center mb-1"
                                            style="font-size:12.5px; cursor:{{ $code === 'en' ? 'default' : 'pointer' }};">
                                            <input type="checkbox" name="rx_languages[]" value="{{ $code }}"
                                                class="mr-2"
                                                {{ array_key_exists($code, $rxLanguages ?? []) ? 'checked' : '' }}
                                                {{ $code === 'en' ? 'checked disabled' : '' }}>
                                            {{ $label }}
                                            @if($code === 'en')
                                                <span class="text-muted ml-1" style="font-size:11px;">(always on)</span>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('rx_languages.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </div>

                </div>

                {{-- The two below keep the full line: a document row runs checkbox, hint, blank
                     space, position and signature across it, and a signature row wants its preview
                     and its controls on one line rather than wrapped into a stack. --}}
                <div class="col-12">
                {{-- Letterhead on the documents this hospital prints. Sits with the prescription settings
                     rather than on a tab of its own: it is the same question as the prescription
                     sheet above — what a printed page carries before the clinical content starts. --}}
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-print mr-1"></i> Letterhead on printed documents</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3" style="font-size:12px;">
                            Switch a document off where you print it on paper that already carries your
                            letterhead. The blank space holds the print clear of what is pre-printed at
                            the top of the sheet.
                        </p>

                        {{-- Column headings rather than a label on every control: "Print signature",
                             "on the" and "leave … mm blank" were repeating once per document, five
                             times over, which is most of what made this card hard to read. Said once
                             at the top, each row is then just its answers. --}}
                        <div class="lh-grid lh-head">
                            <span>Document</span>
                            <span class="lh-c">No header</span>
                            <span class="lh-c">Blank top</span>
                            <span>Signature</span>
                            <span class="lh-c">Side</span>
                        </div>

                        @foreach ($print_header_docs as $hsDoc => $hsMeta)
                            @php
                                $hsVal = $print_headers[$hsDoc] ?? [];
                                $hsOff = $hsVal['off'] ?? false;
                                $hsMm  = $hsVal['mm'] ?? 40;
                                $hsSig = $hsVal['sign'] ?? false;
                                $hsSid = $hsVal['sign_id'] ?? 0;
                                $hsPos = ($hsVal['sign_pos'] ?? 'right') === 'left' ? 'left' : 'right';
                                // One control, not a checkbox plus a select saying the same thing:
                                // "off" is a value the signature list can hold like any other.
                                $hsSigVal = old("print_header.{$hsDoc}.sign_id", $hsSig ? $hsSid : 'off');
                            @endphp
                            <div class="lh-grid lh-row">
                                <div class="lh-doc">
                                    <div class="lh-name">{{ $hsMeta['label'] }}</div>
                                    <div class="lh-hint">{{ $hsMeta['hint'] }}</div>
                                    @foreach ($hsMeta['sections'] ?? [] as $hsSecKey => $hsSecLabel)
                                        @php $hsSecOn = $hsVal['secs'][$hsSecKey] ?? true; @endphp
                                        <label class="lh-sec">
                                            <input type="checkbox" name="print_header[{{ $hsDoc }}][secs][{{ $hsSecKey }}]" value="1"
                                                   {{ old("print_header.{$hsDoc}.secs.{$hsSecKey}", $hsSecOn) ? 'checked' : '' }}>
                                            {{ $hsSecLabel }}
                                        </label>
                                    @endforeach
                                </div>

                                <div class="lh-c" data-lh-label="No header">
                                    <input type="checkbox" name="print_header[{{ $hsDoc }}][off]" value="1"
                                           {{ old("print_header.{$hsDoc}.off", $hsOff) ? 'checked' : '' }}>
                                </div>

                                <div class="lh-c" data-lh-label="Blank top">
                                    <span class="lh-mm">
                                        <input type="number" name="print_header[{{ $hsDoc }}][mm]" class="form-control form-control-sm"
                                               min="0" max="120" step="5"
                                               value="{{ old("print_header.{$hsDoc}.mm", $hsMm) }}">
                                        <span>mm</span>
                                    </span>
                                </div>

                                <div data-lh-label="Signature">
                                    <select name="print_header[{{ $hsDoc }}][sign_id]" class="form-control form-control-sm"
                                            {{ $signatures->isEmpty() ? 'disabled' : '' }}>
                                        <option value="off" {{ (string) $hsSigVal === 'off' ? 'selected' : '' }}>Don't print</option>
                                        <option value="0" {{ (string) $hsSigVal === '0' ? 'selected' : '' }}>Hospital default</option>
                                        @foreach ($signatures as $hsSign)
                                            <option value="{{ $hsSign->id }}" {{ (string) $hsSigVal === (string) $hsSign->id ? 'selected' : '' }}>
                                                {{ hmis_signature_label($hsSign) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="lh-c" data-lh-label="Side">
                                    <select name="print_header[{{ $hsDoc }}][sign_pos]" class="form-control form-control-sm">
                                        <option value="right" {{ $hsPos === 'right' ? 'selected' : '' }}>Right</option>
                                        <option value="left"  {{ $hsPos === 'left'  ? 'selected' : '' }}>Left</option>
                                    </select>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </div>

                </div>

                <div class="col-12">
                {{-- The library every signature select above draws from. A card of its own because
                     it is a store of things, not another per-document switch, and reading it as one
                     more row of the letterhead card is what made that card hard to scan. --}}
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-edit mr-1"></i> Signatures</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3" style="font-size:12px;">
                            Used on any document on the card above. The default is what a document uses
                            unless it names one of its own, so a hospital with a single signature never
                            has to choose one.
                        </p>

                        @forelse ($signatures as $hsSign)
                            <div class="hs-sig-row">
                                <img class="hs-sig-img" alt="Signature"
                                     src="{{ asset('storage/app/public/store/signature') . '/' . $hsSign->image }}">
                                <span class="hs-sig-name">
                                    {{ hmis_signature_label($hsSign) }}
                                    {{-- Where it came from, said once beside the name rather than as a
                                         sentence repeated down the whole list. --}}
                                    @if ($hsSign->type !== 'hmis')
                                        <span class="hs-sig-src">{{ $hsSign->type ?: 'billing' }}</span>
                                    @endif
                                </span>
                                <label class="hs-sig-default">
                                    <input type="radio" name="default_signature_id" value="{{ $hsSign->id }}"
                                           {{ (int) old('default_signature_id', $default_signature_id) === (int) $hsSign->id ? 'checked' : '' }}>
                                    Default
                                </label>
                                {{-- Removable only where it was added here. One saved against invoices
                                     is still signing invoices, and this is not the screen on which to
                                     discover that. --}}
                                @if ($hsSign->type === 'hmis')
                                    <button type="submit" form="hmisSignDel{{ $hsSign->id }}" class="hs-sig-del"
                                            onclick="return confirm('Remove this signature? Any document set to use it falls back to the default.')">
                                        Remove
                                    </button>
                                @else
                                    <span class="hs-sig-del is-off" title="Saved for {{ $hsSign->type ?: 'billing' }} — remove it on that screen">&mdash;</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted mb-2" style="font-size:12px;">No signatures saved yet.</p>
                        @endforelse

                            <div class="d-flex align-items-end flex-wrap border-top pt-3 mt-2" style="gap:10px;">
                                <span style="flex:1 1 220px; min-width:0;">
                                    <label class="input-label mb-1" style="font-size:12px;">Signature image</label>
                                    <input type="file" name="image" form="hmisSignForm" accept="image/png,image/jpeg"
                                           class="form-control form-control-sm" required>
                                    <small class="text-muted" style="font-size:11px;">PNG on a transparent background prints best. Under 1 MB.</small>
                                </span>
                                <span style="flex:0 1 220px; min-width:0;">
                                    <label class="input-label mb-1" style="font-size:12px;">Whose signature</label>
                                    <select name="staff" form="hmisSignForm" class="form-control form-control-sm">
                                        <option value="">The hospital (no name)</option>
                                        @if ($signature_owner)
                                            <option value="0">
                                                {{ trim(($signature_owner->f_name ?? '') . ' ' . ($signature_owner->l_name ?? '')) ?: 'Account owner' }}
                                                (owner)
                                            </option>
                                        @endif
                                        @foreach ($signature_staff as $hsGroup => $hsMembers)
                                            <optgroup label="{{ $hsGroup }}">
                                                @foreach ($hsMembers as $hsStaff)
                                                    <option value="{{ $hsStaff->id }}">{{ trim($hsStaff->f_name . ' ' . $hsStaff->l_name) }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </span>
                                <button type="submit" form="hmisSignForm" class="btn btn-sm btn-outline-primary">Add signature</button>
                            </div>
                    </div>
                </div>
                </div>
                </div>{{-- /row --}}
                </div>{{-- /pane opd --}}

            </div>
        </div>
    </form>

    {{-- OP Types — how an OPD visit is paid for. Its own card with its own small forms, because
         the settings form above posts as one block and a nested form is not valid HTML. That is
         also why this pane sits outside the form rather than inside it. --}}
    {{-- Out here on purpose: these post files and deletions of their own, and a form cannot be
         nested inside another. The controls that drive them sit up in the printing card and reach
         them by id through the HTML form attribute. --}}
    <form id="hmisSignForm" action="{{ route('vendor.hospital.signature.save') }}" method="POST"
          enctype="multipart/form-data" class="d-none">@csrf</form>
    @foreach ($signatures->where('type', 'hmis') as $hsSign)
        <form id="hmisSignDel{{ $hsSign->id }}" action="{{ route('vendor.hospital.signature.delete', $hsSign->id) }}"
              method="POST" class="d-none">@csrf</form>
    @endforeach

    <div class="hs-pane" data-hs-pane="opTypes">
    <div class="card mb-3" id="opTypes">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="tio-card mr-1"></i> OP Types &mdash; Insurance, Government Schemes</h6>
            <small class="text-muted">Offered on the OPD registration form</small>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-7">
                    <p class="text-muted mb-2" style="font-size:12px;">
                        Standard types come with the platform. Switch off any this hospital does not
                        use &mdash; nothing is deleted, and visits already recorded under one keep it.
                    </p>
                    <div class="row no-gutters">
                        @foreach ($opTypeDefaults as $opName)
                            @php $opOff = isset($opTypesHidden[mb_strtolower(trim($opName))]); @endphp
                            <div class="col-md-6">
                                <div class="opt-row">
                                    <span class="opt-name {{ $opOff ? 'off' : '' }}">{{ $opName }}</span>
                                    <form method="post" action="{{ route('vendor.opd.op-types.update') }}" class="mb-0">
                                        @csrf
                                        <input type="hidden" name="name" value="{{ $opName }}">
                                        <input type="hidden" name="action" value="{{ $opOff ? 'restore' : 'hide' }}">
                                        <button type="submit" class="btn btn-xs {{ $opOff ? 'btn-outline-primary' : 'btn-outline-secondary' }}">
                                            {{ $opOff ? 'On' : 'Off' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-lg-5">
                    <form method="post" action="{{ route('vendor.opd.op-types.update') }}" class="mb-3">
                        @csrf
                        <input type="hidden" name="action" value="add">
                        <label class="input-label">Add your own</label>
                        <div class="input-group">
                            <input type="text" name="name" class="form-control" maxlength="100" required
                                   placeholder="e.g. Aarogya Raksha, Railway Scheme">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn--primary">Add</button>
                            </div>
                        </div>
                    </form>

                    <label class="input-label">This hospital's types ({{ count($opTypesOwn) }})</label>
                    @forelse ($opTypesOwn as $opName)
                        <div class="opt-row">
                            <span class="opt-name">{{ $opName }}</span>
                            <form method="post" action="{{ route('vendor.opd.op-types.update') }}" class="mb-0"
                                  onsubmit="return confirm('Remove {{ addslashes($opName) }} from the list?');">
                                @csrf
                                <input type="hidden" name="name" value="{{ $opName }}">
                                <input type="hidden" name="action" value="hide">
                                <button type="submit" class="btn btn-xs btn-outline-danger"><i class="tio-delete"></i></button>
                            </form>
                        </div>
                    @empty
                        <div class="opt-empty">Nothing added yet. The standard types are already available.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    </div>{{-- /pane opTypes --}}

    {{-- Department letterheads. A lab, pharmacy or scan centre frequently sits at its own
         address under its own GSTIN and its own registrations, so each keeps a separate
         identity block; anything left blank falls back to the hospital's own details.

         Its own Bootstrap tabs inside this pane are left exactly as they were: the page-level
         tabs below are hand-rolled and only ever touch [data-hs-pane], so the two do not
         fight over the same click. --}}
    <div class="hs-pane" data-hs-pane="departments">
    <div class="card mb-3">
        <div class="card-header py-2">
            <h6 class="mb-0"><i class="tio-city mr-1"></i> Department Details &mdash; Address, GSTIN &amp; Licences</h6>
        </div>
        <div class="card-body p-0">
            <ul class="nav nav-tabs nav--tabs border-0 px-3 pt-3" role="tablist">
                @foreach ($departments as $key => $dept)
                    <li class="nav-item">
                        <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-toggle="tab"
                           href="#dept-{{ $key }}" role="tab">
                            {{ $dept['label'] }}
                            @if ($dept['licenses']->count())
                                <span class="badge badge-soft-info ml-1">{{ $dept['licenses']->count() }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content p-3">
                @foreach ($departments as $key => $dept)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="dept-{{ $key }}" role="tabpanel">
                        <form action="{{ route('vendor.hospital.department.save', $key) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label class="input-label">{{ $dept['label'] }} Name</label>
                                    <input type="text" name="display_name" class="form-control"
                                           value="{{ old('display_name', $dept['profile']->display_name) }}"
                                           placeholder="Prints on the report header">
                                    <small class="text-muted">Blank = the hospital's own name.</small>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label class="input-label">GSTIN</label>
                                    <input type="text" name="gst_no" class="form-control text-uppercase"
                                           value="{{ old('gst_no', $dept['profile']->gst_no) }}"
                                           maxlength="30" placeholder="e.g. 33ABCDE1234F1Z5">
                                </div>
                                <div class="col-md-2 form-group">
                                    <label class="input-label">Phone</label>
                                    <input type="text" name="phone" class="form-control"
                                           value="{{ old('phone', $dept['profile']->phone) }}" maxlength="40">
                                </div>
                                <div class="col-md-2 form-group">
                                    <label class="input-label">Email</label>
                                    <input type="email" name="email" class="form-control"
                                           value="{{ old('email', $dept['profile']->email) }}" maxlength="190">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="input-label">Address</label>
                                    <textarea name="address" class="form-control" rows="2" maxlength="500"
                                              placeholder="Door no., street, area">{{ old('address', $dept['profile']->address) }}</textarea>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label class="input-label">City</label>
                                    <input type="text" name="city" class="form-control"
                                           value="{{ old('city', $dept['profile']->city) }}" maxlength="100">
                                </div>
                                <div class="col-md-2 form-group">
                                    <label class="input-label">State</label>
                                    <select name="state" class="form-control">
                                        <option value="">Select</option>
                                        @foreach ($states as $state)
                                            <option value="{{ $state->id }}"
                                                {{ (string) old('state', $dept['profile']->state) === (string) $state->id ? 'selected' : '' }}>
                                                {{ $state->state_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label class="input-label">PIN Code</label>
                                    <input type="text" name="pincode" class="form-control"
                                           value="{{ old('pincode', $dept['profile']->pincode) }}" maxlength="20">
                                </div>
                            </div>

                            <hr class="mt-1 mb-3">

                            @include('hmis::vendor.hospital._licenses', [
                                'uid'         => $key,
                                'licenses'    => $dept['licenses'],
                                'note'        => \App\Models\HospitalDepartmentProfile::LICENSE_HINTS[$key]['note'] ?? '',
                                'suggestions' => \App\Models\HospitalDepartmentProfile::LICENSE_HINTS[$key]['types'] ?? [],
                            ])

                            <div class="text-right mt-3">
                                <button type="submit" class="btn btn--primary">Save {{ $dept['label'] }} Details</button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    </div>{{-- /pane departments --}}
</div>
@endsection

@push('script_2')
<script>
/**
 * The settings tabs, and remembering which one you were on.
 *
 * Hand-rolled rather than Bootstrap's: half these panes live inside the settings form and half
 * sit outside it, so they are not siblings in one .tab-content and Bootstrap's own deactivation
 * would leave two panes showing at once. This only ever touches [data-hs-pane], which is why the
 * Bootstrap tabs inside the Departments pane keep working untouched.
 *
 * Which tab comes back on a refresh, in order of precedence:
 *   1. a tab whose pane holds a field the server rejected — the error is the reason you are back
 *      on this page at all, and it is worth more than where you were standing
 *   2. the URL hash, so a deep link like /hospital/settings#opTypes still lands on OP Types
 *   3. what was last opened, from localStorage — this is what survives Save, since the redirect
 *      back carries no fragment and the server never sees one
 */
(function () {
    const KEY   = 'hmisSettingsTab';
    const tabs  = Array.from(document.querySelectorAll('[data-hs-tab]'));
    const panes = Array.from(document.querySelectorAll('[data-hs-pane]'));
    if (!tabs.length || !panes.length) return;

    const paneOf = key => panes.find(p => p.dataset.hsPane === key);
    const exists = key => Boolean(key && paneOf(key));

    function show(key, remember) {
        if (!exists(key)) return false;

        tabs.forEach(t => t.classList.toggle('active', t.dataset.hsTab === key));
        panes.forEach(p => p.classList.toggle('active', p.dataset.hsPane === key));

        if (remember) {
            try { localStorage.setItem(KEY, key); } catch (e) { /* private window — the hash still carries it */ }
            // replaceState, not the hash itself: assigning location.hash scrolls the pane under
            // the fixed header, and every tab switch would push another history entry.
            if (window.history && history.replaceState) {
                history.replaceState(null, '', '#' + key);
            }
        }

        return true;
    }

    tabs.forEach(tab => tab.addEventListener('click', function (e) {
        e.preventDefault();
        show(this.dataset.hsTab, true);
    }));

    // Panes carrying a rejected field, marked in the bar so a second error stays findable.
    const failed = panes.filter(p => p.querySelector('.is-invalid, .invalid-feedback'));
    failed.forEach(p => {
        const tab = tabs.find(t => t.dataset.hsTab === p.dataset.hsPane);
        if (tab) tab.classList.add('has-error');
    });

    let stored = null;
    try { stored = localStorage.getItem(KEY); } catch (e) { /* nothing remembered, fall through */ }

    // A hand-typed or truncated fragment can be an invalid percent sequence, and an unguarded
    // decode throwing here would take the whole restore with it.
    let hash = (location.hash || '').replace('#', '');
    try { hash = decodeURIComponent(hash); } catch (e) { /* use it raw */ }

    show(failed.length ? failed[0].dataset.hsPane : null, false)
        || show(hash, true)
        || show(stored, false)
        || show(tabs[0].dataset.hsTab, false);
})();
</script>
@endpush

@push('script_2')
<script>
    (function () {
        const toggle = document.getElementById('dailyReportToggle');
        const opts   = document.getElementById('dailyReportOptions');
        if (toggle && opts) {
            toggle.addEventListener('change', function () {
                opts.style.display = this.checked ? '' : 'none';
            });
        }
    })();
</script>
@endpush

@push('script_2')
<script>
function updatePreview() {
    const prefix  = (document.getElementById('prefixInput').value || 'P').toUpperCase();
    const padding = parseInt(document.getElementById('paddingInput').value) || 5;
    const serial  = parseInt(document.getElementById('serialInput').value) || 1;
    const padded  = String(serial).padStart(padding, '0');
    document.getElementById('muidPreview').textContent = prefix + '-' + padded;
}
</script>
@endpush
