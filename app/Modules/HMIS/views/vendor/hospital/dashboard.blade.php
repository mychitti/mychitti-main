@extends('layouts.vendor.app')
@section('title', 'Hospital Dashboard')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <style>
        /* ── HMIS theme (scoped to .hdash) ─────────────────────────────── */
        .hdash { font-size: 13px; color: #0D1117; }
        .hdash .hd-topbar { background:#fff; border:1px solid #c8d2e0; border-radius:12px; padding:12px 18px;
            display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:14px; }
        .hdash .hd-topbar h2 { font-size:17px; font-weight:800; color:#0D1117; margin:0; }
        .hdash .hd-topbar p { font-size:11px; color:#718096; margin:2px 0 0; }
        .hdash .hd-alert { display:inline-flex; align-items:center; gap:6px; background:#FEF3C7; border:1px solid #F59E0B;
            border-radius:7px; padding:6px 11px; font-size:11px; color:#92400E; font-weight:600; }
        .hdash .sec-title { font-size:11px; font-weight:700; color:#A0AEC0; letter-spacing:.8px; text-transform:uppercase; margin:0 0 10px; }
        .hdash .quick-actions { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:18px; }
        .hdash .qa { background:#fff; border-radius:10px; border:1px solid #c8d2e0; padding:13px; display:flex; align-items:center;
            gap:11px; cursor:pointer; text-decoration:none; transition:.15s; }
        .hdash .qa:hover { border-color:#1565C0; background:#EFF6FF; }
        .hdash .qa-ic { width:36px; height:36px; border-radius:9px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:17px; }
        .hdash .qa-label { font-size:12.5px; font-weight:700; color:#0D1117; }
        .hdash .qa-sub { font-size:10px; color:#A0AEC0; }
        .hdash .kpi-row { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:14px; }
        .hdash .kpi { background:#fff; border-radius:12px; border:1px solid #c8d2e0; padding:15px; position:relative; overflow:hidden;
            text-decoration:none; display:block; transition:.15s; }
        .hdash .kpi:hover { box-shadow:0 6px 18px rgba(13,71,161,.08); transform:translateY(-1px); }
        .hdash .kpi::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
        .hdash .kpi.blue::before{background:#1565C0}.hdash .kpi.green::before{background:#16A34A}.hdash .kpi.purple::before{background:#7C3AED}
        .hdash .kpi.amber::before{background:#D97706}.hdash .kpi.red::before{background:#DC2626}.hdash .kpi.teal::before{background:#0F766E}
        .hdash .kpi.indigo::before{background:#4338CA}
        .hdash .kpi-top { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:10px; }
        .hdash .kpi-ic { width:38px; height:38px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:18px; }
        .hdash .kpi-ic.blue{background:#EFF6FF;color:#1565C0}.hdash .kpi-ic.green{background:#F0FDF4;color:#16A34A}.hdash .kpi-ic.purple{background:#F5F3FF;color:#7C3AED}
        .hdash .kpi-ic.amber{background:#FFFBEB;color:#D97706}.hdash .kpi-ic.red{background:#FFF1F2;color:#DC2626}.hdash .kpi-ic.teal{background:#F0FDFA;color:#0F766E}
        .hdash .kpi-ic.indigo{background:#EEF2FF;color:#4338CA}
        .hdash .kpi-trend { font-size:10px; font-weight:700; padding:2px 8px; border-radius:100px; background:#F1F5F9; color:#64748B; }
        .hdash .kpi-val { font-size:27px; font-weight:800; color:#0D1117; line-height:1; margin-bottom:3px; }
        .hdash .kpi-label { font-size:11.5px; color:#718096; font-weight:600; }
        .hdash .kpi-sub { font-size:10px; color:#A0AEC0; margin-top:4px; }
        .hdash .mid { display:grid; grid-template-columns:1fr 360px; gap:14px; margin-bottom:14px; }
        .hdash .hcard { background:#fff; border-radius:12px; border:1px solid #c8d2e0; padding:15px 17px; margin-bottom:14px; }
        .hdash .hcard:last-child { margin-bottom:0; }
        .hdash .hcard-hd { display:flex; align-items:center; justify-content:space-between; margin-bottom:13px; }
        .hdash .hcard-hd h3 { font-size:13px; font-weight:700; color:#0D1117; margin:0; }
        .hdash .view-all { font-size:11px; color:#1565C0; font-weight:600; text-decoration:none; }
        .hdash .bars { display:flex; align-items:flex-end; gap:4px; height:110px; padding-top:14px; }
        .hdash .b { flex:1; min-width:4px; border-radius:3px 3px 0 0; position:relative; background:#DBEAFE; }
        .hdash .b.tall { background:#1565C0; }
        .hdash .b-val { position:absolute; top:-13px; left:50%; transform:translateX(-50%); font-size:9px; font-weight:700; color:#1565C0; }
        .hdash .ward-row { display:grid; grid-template-columns:1fr 56px 56px 56px; gap:4px; align-items:center; padding:9px 0; border-bottom:1px solid #dfe6ee; font-size:12px; }
        .hdash .ward-row:last-child { border-bottom:none; }
        .hdash .ward-row.hd { font-size:10px; font-weight:700; color:#A0AEC0; text-transform:uppercase; letter-spacing:.5px; }
        .hdash .w-name { font-weight:600; color:#0D1117; }
        .hdash .occ-bar-wrap { height:4px; background:#F1F5F9; border-radius:2px; margin-top:4px; }
        .hdash .occ-bar { height:100%; border-radius:2px; }
        .hdash .w-num { text-align:center; font-weight:700; }
        .hdash .avail-num{color:#16A34A}.hdash .occ-num{color:#DC2626}.hdash .total-num{color:#718096}
        .hdash .qi { display:grid; grid-template-columns:42px 1fr auto; align-items:center; gap:9px; padding:9px 0; border-bottom:1px solid #dfe6ee; text-decoration:none; }
        .hdash .qi:last-child { border-bottom:none; }
        .hdash .q-tok { width:38px; height:38px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; background:#EFF6FF; color:#1565C0; flex-shrink:0; }
        .hdash .q-pname { font-size:12px; font-weight:600; color:#0D1117; }
        .hdash .q-info { font-size:10px; color:#A0AEC0; }
        .hdash .sp { font-size:10px; font-weight:600; padding:3px 9px; border-radius:100px; background:#EFF6FF; color:#1565C0; white-space:nowrap; }
        .hdash .pt-row { display:flex; align-items:center; gap:9px; padding:9px 0; border-bottom:1px solid #dfe6ee; text-decoration:none; }
        .hdash .pt-row:last-child { border-bottom:none; }
        .hdash .pt-av { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; background:#EFF6FF; color:#1565C0; flex-shrink:0; }
        .hdash .pt-name { font-size:12px; font-weight:600; color:#0D1117; }
        .hdash .pt-id { font-size:10px; color:#A0AEC0; }
        .hdash .pt-time { font-size:10px; color:#718096; margin-left:auto; }
        .hdash .adm-row { display:grid; grid-template-columns:84px 1fr 86px 64px; gap:5px; align-items:center; padding:9px 0; border-bottom:1px solid #dfe6ee; font-size:12px; }
        .hdash .adm-row:last-child { border-bottom:none; }
        .hdash .adm-row.hd { font-size:10px; font-weight:700; color:#A0AEC0; text-transform:uppercase; letter-spacing:.5px; }
        .hdash .adm-id { font-size:10px; color:#1565C0; font-weight:700; }
        .hdash .day-badge { font-size:10px; font-weight:700; padding:2px 8px; border-radius:100px; background:#DBEAFE; color:#1E40AF; }
        .hdash .hd-empty { text-align:center; color:#A0AEC0; font-size:12px; padding:18px 0; }
        @media (max-width: 992px){ .hdash .mid{grid-template-columns:1fr} .hdash .kpi-row,.hdash .quick-actions{grid-template-columns:repeat(2,1fr)} }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="hdash">

            {{-- ── Header ─────────────────────────────────────────────── --}}
            <div class="hd-topbar">
                <div>
                    <h2><i class="tio-hospital mr-2" style="color:#1565C0;"></i>Hospital Dashboard</h2>
                    <p>{{ \Carbon\Carbon::now()->format('l, d M Y') }} ·
                        {{ \Carbon\Carbon::parse($from)->format('d M') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
                </div>
                <div class="d-flex align-items-center" style="gap:8px;">
                    @if (($stats['beds_available'] ?? 0) <= 2)
                        <span class="hd-alert"><i class="tio-warning"></i> {{ $stats['beds_available'] }} beds available</span>
                    @endif
                    @if (auth('vendor')->check())
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#defaultDashboardModal" title="Dashboard settings">
                            <i class="tio-dashboard-outlined"></i>
                        </button>
                        @include('hmis::vendor.hospital.form_modals.default_dashboard')
                    @endif
                    <form class="date-range-form mb-0">
                        @include('vendor-views/form_modals/date_range')
                        <button type="button" class="btn btn-outline-warning btn-sm" data-toggle="modal" data-target="#dateRangeModal">
                            <i class="tio-calendar"></i> {{ translate($preset) }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- ── Quick Actions ─────────────────────────────────────────── --}}
            <div class="sec-title">Quick Actions</div>
            <div class="quick-actions">
                @if (hasPermission('patient', 'add'))
                    <a class="qa" href="{{ route('vendor.patient.add') }}"><span class="qa-ic" style="background:#EFF6FF">👤</span><span><span class="qa-label d-block">Add Patient</span><span class="qa-sub">New registration</span></span></a>
                @endif
                @if (hasPermission('opd_register', 'add'))
                    <a class="qa" href="{{ route('vendor.opd.create') }}"><span class="qa-ic" style="background:#F0FDFA">🚶</span><span><span class="qa-label d-block">New OPD Visit</span><span class="qa-sub">Register token</span></span></a>
                @endif
                @if (hasPermission('ipd_admission', 'add'))
                    <a class="qa" href="{{ route('vendor.ipd.create') }}"><span class="qa-ic" style="background:#FFFBEB">🛏</span><span><span class="qa-label d-block">Admit Patient</span><span class="qa-sub">IPD admission</span></span></a>
                @endif
                @if (hasPermission('prescription', 'list'))
                    <a class="qa" href="{{ route('vendor.prescription.list') }}"><span class="qa-ic" style="background:#FFF0F6">💊</span><span><span class="qa-label d-block">Prescriptions</span><span class="qa-sub">View / write Rx</span></span></a>
                @endif
            </div>

            {{-- ── KPIs ──────────────────────────────────────────────────── --}}
            @php
                // The cards count whatever window the date picker is on, so the heading names that
                // window instead of always claiming today. Anything not listed -- a custom range --
                // falls back to the dates themselves.
                $overviewLabels = [
                    'today'        => "Today's Overview",
                    'yesterday'    => "Yesterday's Overview",
                    'this_week'    => "This Week's Overview",
                    'last_week'    => "Last Week's Overview",
                    'this_month'   => "This Month's Overview",
                    'last_month'   => "Last Month's Overview",
                    'last_3_month' => "Last 3 Months' Overview",
                    'last_30_days' => "Last 30 Days' Overview",
                    'quarter'      => "This Quarter's Overview",
                    'this_year'    => "This Year's Overview",
                    'last_year'    => "Last Year's Overview",
                ];
                $overviewTitle = $overviewLabels[$preset]
                    ?? \Carbon\Carbon::parse($from)->format('d M') . ' – ' . \Carbon\Carbon::parse($to)->format('d M Y') . ' Overview';

                // A card that counted a range opens its list on that same range, so the number on
                // the card and the rows on the screen agree. Query params because that is what the
                // OPD / IPD / prescription lists already read.
                $rangeQuery = ['date_range' => $preset];
                if ($preset === 'custom') {
                    $rangeQuery['custom_date_range'] = request('custom_date_range');
                }

                // The cumulative cards keep a bare link: patients registered, doctors and nurses on
                // record and beds free right now are not range figures, and the screens they open
                // have no date filter to hand one to.
                $kpis = [
                    ['OPD Visits', $stats['opd_in_range'], 'teal', '🚶', route('vendor.opd.index', $rangeQuery), 'This range', 'opd_register', 'list'],
                    ['Total Patients', $stats['patients'], 'blue', '👥', route('vendor.patient.list'), 'Registered', 'patient', 'list'],
                    ['Doctors', $stats['doctors'], 'green', '🩺', route('vendor.doctor.list'), 'On record', 'staff_doctor', 'list'],
                    ['Nurses', $stats['nurses'], 'purple', '👩‍⚕️', route('vendor.nurse.list'), 'On record', 'staff_nurse', 'list'],
                    ['IPD Admitted', $stats['ipd_admitted'], 'red', '🏥', route('vendor.ipd.index', $rangeQuery), 'Currently in', 'ipd_admission', 'list'],
                    ['IPD Admissions', $stats['ipd_in_range'], 'amber', '📋', route('vendor.ipd.index', $rangeQuery), 'This range', 'ipd_admission', 'list'],
                    ['Beds Available', $stats['beds_available'] . '/' . $stats['beds_total'], 'green', '🛏', route('vendor.ipd.bed-dashboard'), 'Free / total', 'ipd_admission', 'list'],
                    ['Prescriptions', $stats['prescriptions_in_range'], 'indigo', '📝', route('vendor.prescription.list', $rangeQuery), 'This range', 'prescription', 'list'],
                ];

                $kpis = array_values(array_filter($kpis, fn($k) => hasPermission($k[6], $k[7])));
            @endphp
            <div class="sec-title">{{ $overviewTitle }}</div>
            @foreach (array_chunk($kpis, 4) as $row)
                <div class="kpi-row">
                    @foreach ($row as $k)
                        <a class="kpi {{ $k[2] }}" href="{{ $k[4] }}">
                            <div class="kpi-top">
                                <span class="kpi-ic {{ $k[2] }}">{{ $k[3] }}</span>
                            </div>
                            <div class="kpi-val">{{ $k[1] }}</div>
                            <div class="kpi-label">{{ $k[0] }}</div>
                            <div class="kpi-sub">{{ $k[5] }}</div>
                        </a>
                    @endforeach
                </div>
            @endforeach

            {{-- ── Middle: chart + wards | queue + patients + admissions ── --}}
            <div class="mid" style="margin-top:4px;">
                <div>
                    {{-- OPD trend --}}
                    <div class="hcard">
                        <div class="hcard-hd">
                            <h3>OPD Visits — Trend</h3>
                            <a class="view-all" href="{{ route('vendor.opd.index', $rangeQuery) }}">View All</a>
                        </div>
                        @php $maxOpd = max(1, count($opdData) ? max($opdData) : 1); @endphp
                        @if (array_sum($opdData))
                            <div class="bars">
                                @foreach ($opdData as $i => $v)
                                    @php $h = round(($v / $maxOpd) * 100); @endphp
                                    <div class="b {{ $v == $maxOpd ? 'tall' : '' }}" style="height:{{ max($v ? 8 : 3, $h) }}%;" title="{{ $opdLabels[$i] ?? '' }}: {{ $v }}">
                                        @if ($v == $maxOpd && $v > 0)<span class="b-val">{{ $v }}</span>@endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="hd-empty">No OPD visits in this range.</div>
                        @endif
                    </div>

                    {{-- Wards --}}
                    <div class="hcard">
                        <div class="hcard-hd">
                            <h3>Wards &amp; Bed Occupancy</h3>
                            <a class="view-all" href="{{ route('vendor.ward.index') }}">Manage</a>
                        </div>
                        <div class="ward-row hd"><div>Ward</div><div class="text-center">Total</div><div class="text-center">Avail</div><div class="text-center">Occ</div></div>
                        @forelse ($wards as $ward)
                            @php
                                $total = max($ward->beds_count, 1);
                                $occupied = $ward->beds_count - $ward->available_beds_count;
                                $pct = round(($occupied / $total) * 100);
                                $barColor = $pct >= 90 ? '#DC2626' : ($pct >= 50 ? '#D97706' : '#16A34A');
                            @endphp
                            <div class="ward-row">
                                <div>
                                    <div class="w-name">{{ $ward->ward_name }}</div>
                                    <div class="occ-bar-wrap"><div class="occ-bar" style="width:{{ $pct }}%;background:{{ $barColor }}"></div></div>
                                </div>
                                <div class="w-num total-num">{{ $ward->beds_count }}</div>
                                <div class="w-num avail-num">{{ $ward->available_beds_count }}</div>
                                <div class="w-num occ-num">{{ $occupied }}</div>
                            </div>
                        @empty
                            <div class="hd-empty">No wards configured.</div>
                        @endforelse
                    </div>
                </div>

                <div>
                    {{-- OPD Queue --}}
                    <div class="hcard">
                        <div class="hcard-hd">
                            <h3>OPD Queue</h3>
                            <a class="view-all" href="{{ route('vendor.opd.create') }}">+ Register</a>
                        </div>
                        @forelse ($recentOpdVisits as $visit)
                            <a class="qi" href="{{ route('vendor.opd.show', $visit->id) }}">
                                <div class="q-tok">{{ $visit->token_number }}</div>
                                <div>
                                    <div class="q-pname">{{ $visit->patient?->name ?? '—' }}</div>
                                    <div class="q-info">{{ $visit->patient?->patient_uid }} · Dr. {{ $visit->doctorProfile?->employee?->f_name }}</div>
                                </div>
                                <span class="sp">{{ ucfirst(str_replace('_', ' ', $visit->status ?? 'OPD')) }}</span>
                            </a>
                        @empty
                            <div class="hd-empty">No OPD visits yet.</div>
                        @endforelse
                    </div>

                    {{-- New Patients --}}
                    <div class="hcard">
                        <div class="hcard-hd">
                            <h3>New Patients</h3>
                            <a class="view-all" href="{{ route('vendor.patient.list') }}">All</a>
                        </div>
                        @forelse ($recentPatients as $p)
                            <a class="pt-row" href="{{ route('vendor.patient.show', $p->id) }}">
                                <div class="pt-av">{{ strtoupper(substr($p->name ?? '?', 0, 1)) }}</div>
                                <div><div class="pt-name">{{ $p->name }}</div><div class="pt-id">{{ $p->patient_uid }}</div></div>
                                <div class="pt-time">{{ $p->created_at?->diffForHumans(null, true) }} ago</div>
                            </a>
                        @empty
                            <div class="hd-empty">No new patients in this range.</div>
                        @endforelse
                    </div>

                    {{-- IPD Admissions --}}
                    <div class="hcard">
                        <div class="hcard-hd">
                            <h3>IPD Admissions</h3>
                            <a class="view-all" href="{{ route('vendor.ipd.index', $rangeQuery) }}">All</a>
                        </div>
                        <div class="adm-row hd"><div>Adm. No.</div><div>Patient</div><div>Ward/Bed</div><div>Day</div></div>
                        @forelse ($recentAdmissions as $adm)
                            <div class="adm-row">
                                <div><a class="adm-id" href="{{ route('vendor.ipd.show', $adm->id) }}">{{ $adm->admission_number }}</a></div>
                                <div style="font-weight:600;color:#0D1117;">{{ $adm->patient?->name }}</div>
                                <div style="font-size:10px;color:#718096;">{{ $adm->ward?->ward_name }}{{ $adm->bed?->bed_number ? ' – ' . $adm->bed->bed_number : '' }}</div>
                                <span class="day-badge">Day {{ $adm->admission_date?->diffInDays(now()) + 1 }}</span>
                            </div>
                        @empty
                            <div class="hd-empty">No admissions in this range.</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
@endpush
