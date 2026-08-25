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

    <form action="{{ route('vendor.hospital.settings.save') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-4">

                {{-- MUID Format --}}
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-label mr-1"></i> MUID Format</h6>
                    </div>
                    <div class="card-body">

                        <div class="form-group">
                            <label class="input-label">Prefix <span class="text-danger">*</span></label>
                            <input type="text" name="prefix" class="form-control @error('prefix') is-invalid @enderror"
                                   value="{{ old('prefix', $prefix) }}" maxlength="10"
                                   placeholder="e.g. P, MH, PAT"
                                   oninput="updatePreview()" id="prefixInput">
                            <small class="text-muted">Letters, numbers, hyphens and underscores only.</small>
                            @error('prefix')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="input-label">Zero-padding digits <span class="text-danger">*</span></label>
                            <input type="number" name="padding" class="form-control @error('padding') is-invalid @enderror"
                                   value="{{ old('padding', $padding) }}" min="1" max="10"
                                   oninput="updatePreview()" id="paddingInput">
                            <small class="text-muted">Number of digits in the serial (e.g. 5 → 00001).</small>
                            @error('padding')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group mb-0">
                            <label class="input-label">Minimum serial number <span class="text-danger">*</span></label>
                            <input type="number" name="serial" class="form-control @error('serial') is-invalid @enderror"
                                   value="{{ old('serial', $serial) }}" min="1"
                                   oninput="updatePreview()" id="serialInput">
                            <small class="text-muted">New patients will start from this number (if current count is lower).</small>
                            @error('serial')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

                {{-- Clinical Recording — what this hospital actually charts. A dental or
                     physiotherapy practice never takes a BP, and an always-on vitals card is a
                     row of dashes on every screen it appears on. --}}
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-heart-outlined mr-1"></i> Clinical Recording</h6>
                    </div>
                    <div class="card-body">
                        <label class="d-flex align-items-start mb-0" style="cursor:pointer;">
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
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </div>

                {{-- Daily report — a summary of the day on WhatsApp, off unless asked for.
                     Sent from the platform's number rather than the hospital's: this is MyChitti
                     reporting to its customer, not the hospital messaging a patient, so it works
                     whether or not they have connected WhatsApp themselves. --}}
                <div class="card mb-3">
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

                {{-- Lab Work — on by default for the specialities that actually send work out,
                     available to everyone else on request. The measurement boxes behind the tab
                     come from the hospital category chosen above, so the card names which set
                     this hospital will get rather than making them save to find out. --}}
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-lab mr-1"></i> Lab Work</h6>
                    </div>
                    <div class="card-body">
                        <label class="d-flex align-items-start mb-0" style="cursor:pointer;">
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
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </div>

                {{-- Records Access & Audit — off unless a hospital asks for it. Switching it on
                     is what starts writing the trail, not just what reveals the tab, so a clinic
                     that will never read it never accumulates the rows. --}}
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-lock-outlined mr-1"></i> Records Access &amp; Audit</h6>
                    </div>
                    <div class="card-body">
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

            <div class="col-lg-8">
                {{-- OP Consultation Validity --}}
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-receipt mr-1"></i> OP Consultation Validity</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="input-label">Consultations per paid OP <span class="text-danger">*</span></label>
                            <input type="number" name="opd_consultation_count"
                                   class="form-control @error('opd_consultation_count') is-invalid @enderror"
                                   value="{{ old('opd_consultation_count', $opd_consultation_count) }}" min="1" max="50">
                            <small class="text-muted">How many consultations one paid OP receipt covers (e.g. 2).</small>
                            @error('opd_consultation_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group mb-0">
                            <label class="input-label">Validity (days) <span class="text-danger">*</span></label>
                            <input type="number" name="opd_consultation_validity_days"
                                   class="form-control @error('opd_consultation_validity_days') is-invalid @enderror"
                                   value="{{ old('opd_consultation_validity_days', $opd_consultation_validity_days) }}" min="1" max="365">
                            <small class="text-muted">Days a paid OP stays valid for follow-up visits (e.g. 7 = 1 week).</small>
                            @error('opd_consultation_validity_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Prescription languages — which ones the doctor is offered when writing
                             an Rx. Everything not ticked stays out of that dropdown, so a clinic
                             that writes English and Tamil never scrolls past twenty others. --}}
                        <div class="col-12">
                            <hr class="mt-1 mb-3">
                            <label class="input-label mb-1">Prescription Languages</label>
                            <p class="text-muted mb-2" style="font-size:12px;">
                                Tick the languages your doctors write prescriptions in. Only these appear
                                in the language dropdown on the prescription screen.
                            </p>
                            <div class="row no-gutters" style="max-height:220px; overflow-y:auto;">
                                @foreach (\App\Models\Prescription::LANGUAGES as $code => $label)
                                    <div class="col-md-4 col-sm-6">
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
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </div>

            </div>
        </div>
    </form>

    {{-- OP Types — how an OPD visit is paid for. Its own card with its own small forms, because
         the settings form above posts as one block and a nested form is not valid HTML. --}}
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

    {{-- Department letterheads. A lab, pharmacy or scan centre frequently sits at its own
         address under its own GSTIN and its own registrations, so each keeps a separate
         identity block; anything left blank falls back to the hospital's own details. --}}
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
</div>
@endsection

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
