{{-- Every lab work job in the building, across every patient, with the counter's handover form on
     each row.

     The consultation card is the per-patient view of these same records. This is the per-DAY view:
     what is late, what came back and is waiting to be fitted, and which delivery nobody has
     confirmed with the lab yet. Those questions are asked of the whole clinic at once, and asking
     them one visit at a time means opening forty records.

     Laid out as one bordered list with a single header strip rather than a card per job: forty
     floating cards, each repeating four column labels, is the same data with three times the ink
     and no alignment to read down. --}}
@extends('layouts.vendor.app')
@section('title', $profile['label'] . ' Register')

@push('css_or_js')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .lwr { font-family: 'Inter', sans-serif; color: #334155; }
        .lwr .page-header-title { font-family: 'Outfit', sans-serif; font-weight: 700; color: #0f172a; letter-spacing: -.4px; }

        /* ── Tiles: the four questions this page answers, each one a filter ── */
        .lwr-tiles { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 16px; }
        .lwr-tile {
            background: #fff; border: 1px solid #e5e9f0; border-radius: 8px; padding: 10px 14px;
            text-decoration: none !important; display: block;
        }
        .lwr-tile:hover { border-color: #cbd5e1; }
        .lwr-tile.is-on { border-color: #3b82f6; background: #f8fbff; }
        .lwr-tile .n { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700; line-height: 1.15; }
        .lwr-tile .t { font-size: 10.5px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-top: 1px; }

        /* ── Tabs ── */
        .lwr-tabs { display: flex; gap: 4px; border-bottom: 1px solid #e5e9f0; margin-bottom: 14px; }
        .lwr-tab {
            padding: 9px 14px; font-size: 12.5px; font-weight: 600; color: #64748b !important;
            border-bottom: 2px solid transparent; text-decoration: none !important; white-space: nowrap;
        }
        .lwr-tab:hover { color: #0f172a !important; }
        .lwr-tab.active { color: #2563eb !important; border-bottom-color: #2563eb; }

        /* ── Filters ── */
        .lwr-filters { background: #fff; border: 1px solid #e5e9f0; border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; }
        .lwr-filters .form-control { font-size: 12.5px; height: 32px; }
        .lwr-filters label { font-size: 10.5px; text-transform: uppercase; letter-spacing: .04em; color: #94a3b8; font-weight: 700; margin-bottom: 3px; }

        /* ── The list: one border round the lot, hairlines between rows, labels once ── */
        .lwr-list { background: #fff; border: 1px solid #e5e9f0; border-radius: 10px; overflow: hidden; }
        .lwr-head, .lwr-row {
            display: grid; grid-template-columns: 1.1fr 1.3fr 1.15fr 1.45fr;
            gap: 16px; padding: 11px 16px; align-items: start;
        }
        .lwr-head {
            background: #f8fafc; border-bottom: 1px solid #e5e9f0;
            font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; font-weight: 700;
        }
        .lwr-row { border-bottom: 1px solid #f1f5f9; }
        .lwr-row:last-child { border-bottom: 0; }
        .lwr-row:hover { background: #fcfdff; }
        .lwr-row.is-late { box-shadow: inset 3px 0 0 #ef4444; }
        .lwr-row.is-closed { background: #fcfcfd; }
        .lwr-row.is-closed .lwr-name { color: #64748b !important; }

        /* ── One type scale, used everywhere ── */
        .lwr-name { font-size: 13px; font-weight: 700; color: #0f172a !important; text-decoration: none !important; }
        a.lwr-name:hover { color: #2563eb !important; }
        .lwr-meta { font-size: 11.5px; color: #64748b; line-height: 1.7; }
        .lwr-meta a { color: #64748b !important; text-decoration: none !important; }
        .lwr-meta a:hover { color: #2563eb !important; }
        .lwr-meta b { color: #334155; font-weight: 600; }
        .lwr-dim { font-weight: 500; color: #94a3b8; }
        .lwr-late, .lwr-meta b.lwr-late { color: #dc2626 !important; font-weight: 700; }
        .lwr-pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10.5px; font-weight: 700; }
        .lwr-tag {
            display: inline-block; padding: 1px 6px; border-radius: 4px; font-size: 10px; font-weight: 700;
            border: 1px solid #e2e8f0; color: #64748b; text-transform: uppercase; letter-spacing: .03em;
        }
        .lwr-tag.is-ext { border-color: #fed7aa; color: #c2410c; background: #fff7ed; }
        .lwr-tag.is-int { border-color: #bfdbfe; color: #1d4ed8; background: #eff6ff; }
        .lwr-ok { color: #16a34a; font-size: 11px; }

        /* ── Actions: one control row, then one row of quiet links. Only the arrow glyphs carry
              colour — four differently coloured links in a 200px column is what made this shout. ── */
        .lwr-move { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .lwr-move select { height: 30px; font-size: 12px; min-width: 146px; width: auto; padding: 2px 6px; }
        .lwr-move .lwr-go {
            height: 30px; padding: 0 14px; font-size: 12px; font-weight: 600; border-radius: 6px;
            border: 0; background: #2563eb; color: #fff;
        }
        .lwr-move .lwr-go:hover { background: #1d4ed8; }
        .lwr-tell { font-size: 11px; color: #64748b; cursor: pointer; margin: 0; display: inline-flex; align-items: center; gap: 4px; }
        .lwr-links { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 7px; }
        .lwr-links form { display: inline; }
        .lwr-link {
            font-size: 11.5px; font-weight: 600; color: #64748b !important; white-space: nowrap;
            background: none; border: 0; padding: 0; text-decoration: none !important;
        }
        .lwr-link:hover { color: #2563eb !important; }
        .lwr-link .g { font-weight: 700; margin-right: 2px; }
        .lwr-link.is-out .g { color: #d97706; }
        .lwr-link.is-in .g { color: #059669; }

        .lwr-warn {
            grid-column: 1 / -1; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px;
            padding: 7px 10px; font-size: 11.5px; color: #991b1b; margin-top: 2px;
        }
        .lwr-warn .form-control { height: 28px; font-size: 11px; }
        .lwr-empty { padding: 44px 20px; text-align: center; color: #94a3b8; font-size: 13px; }

        /* Handover log — same list, different column weights. */
        .lwr-list.is-log .lwr-head, .lwr-list.is-log .lwr-row { grid-template-columns: 135px 1.25fr 1.35fr 1.15fr; }

        /* ── Counter tablet and phone: the header strip cannot fold, so each cell carries its own
              label instead and the row becomes one column. ── */
        @media (max-width: 991px) {
            .lwr-head { display: none; }
            .lwr-list .lwr-row, .lwr-list.is-log .lwr-row { grid-template-columns: 1fr; gap: 10px; padding: 14px 16px; }
            .lwr-cell[data-l]::before {
                content: attr(data-l); display: block; font-size: 10px; text-transform: uppercase;
                letter-spacing: .05em; color: #94a3b8; font-weight: 700; margin-bottom: 2px;
            }
            .lwr-tiles { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
@endpush

@section('content')
@php
    $lwrStatuses = $profile['statuses'];
    $lwrColours  = \App\Models\OpdLabWork::STATUS_COLOURS;
    $lwrCurrency = \App\CentralLogics\Helpers::currency_symbol() ?: '₹';
    $lwrCanEdit  = hasPermission('opd_register', 'edit');
    $lwrScopes   = [
        'open'        => 'Open',
        'overdue'     => 'Overdue',
        'ready'       => 'Ready / back at the desk',
        'unconfirmed' => 'Unconfirmed arrivals',
        'closed'      => 'Closed',
        'all'         => 'All',
    ];
@endphp

<div class="content container-fluid lwr">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-lab" style="font-size:22px;"></i></span>
            {{ $profile['label'] }} Register
            <small class="text-muted font-size-14 ml-2">All patients</small>
        </h1>
        <a href="{{ route('vendor.opd.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-document-text"></i> OPD Register
        </a>
    </div>

    {{-- A stage change made from a row here posts to the same endpoint the consultation card uses,
         so it is refused on the same terms. Shown rather than swallowed: without this a remake
         chosen from the row would bounce back silently and look like a button that does nothing. --}}
    @if($errors->any())
        <div class="alert alert-danger py-2 px-3" style="font-size:12.5px;">
            @foreach($errors->all() as $lwrError)
                <div>{{ $lwrError }}</div>
            @endforeach
        </div>
    @endif

    {{-- The four questions, each one a filter. Counts are of the whole store, not of the page,
         so the numbers hold still while the list underneath is narrowed. --}}
    @php
        $lwrTiles = [
            ['scope' => 'open',        'n' => $counts['open'],        'label' => 'Open jobs',            'colour' => '#2563eb'],
            ['scope' => 'overdue',     'n' => $counts['overdue'],     'label' => 'Past their date',      'colour' => '#dc2626'],
            ['scope' => 'ready',       'n' => $counts['ready'],       'label' => 'Ready / back at desk', 'colour' => '#16a34a'],
            ['scope' => 'unconfirmed', 'n' => $counts['unconfirmed'], 'label' => 'Arrivals unconfirmed', 'colour' => '#ea580c'],
        ];
    @endphp

    <div class="lwr-tiles">
        @foreach($lwrTiles as $lwrTile)
            @php
                $lwrTileOn  = $tab === 'jobs' && $filters['stage'] === '' && $filters['scope'] === $lwrTile['scope'];
                $lwrTileUrl = route('vendor.opd.lab-work.index', ['tab' => 'jobs', 'scope' => $lwrTile['scope']]);
            @endphp
            <a href="{{ $lwrTileUrl }}" class="lwr-tile {{ $lwrTileOn ? 'is-on' : '' }}">
                <div class="n" style="color:{{ $lwrTile['colour'] }};">{{ $lwrTile['n'] }}</div>
                <div class="t">{{ $lwrTile['label'] }}</div>
            </a>
        @endforeach
    </div>

    <div class="lwr-tabs">
        <a href="{{ route('vendor.opd.lab-work.index') }}" class="lwr-tab {{ $tab === 'jobs' ? 'active' : '' }}">Jobs</a>
        <a href="{{ route('vendor.opd.lab-work.index', ['tab' => 'handovers']) }}" class="lwr-tab {{ $tab === 'handovers' ? 'active' : '' }}">Handover log</a>
    </div>

    {{-- One form per tab, because what is worth narrowing by differs: a job has a stage, an
         exchange has a direction and a state of proof. --}}
    <form method="GET" class="lwr-filters">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <div class="form-row">
            <div class="form-group col-lg-3 col-md-6 mb-2">
                <label>Search</label>
                <input type="text" name="q" class="form-control form-control-sm" value="{{ $filters['q'] }}"
                       placeholder="{{ $tab === 'jobs' ? 'Patient, UID, work, lab' : 'Person, lab, patient, staff' }}">
            </div>

            @if($tab === 'jobs')
                <div class="form-group col-lg-2 col-md-3 mb-2">
                    <label>Stage</label>
                    <select name="stage" class="form-control form-control-sm">
                        <option value="">Any stage</option>
                        @foreach($lwrStatuses as $lwrKey => $lwrLabel)
                            <option value="{{ $lwrKey }}" @if($filters['stage'] === $lwrKey) selected @endif>{{ $lwrLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-lg-2 col-md-3 mb-2">
                    <label>Show</label>
                    <select name="scope" class="form-control form-control-sm">
                        @foreach($lwrScopes as $lwrKey => $lwrLabel)
                            <option value="{{ $lwrKey }}" @if($filters['scope'] === $lwrKey) selected @endif>{{ $lwrLabel }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="form-group col-lg-2 col-md-3 mb-2">
                    <label>Direction</label>
                    <select name="dir" class="form-control form-control-sm">
                        <option value="">Both ways</option>
                        @foreach(\App\Models\HmisHandover::DIRECTIONS as $lwrKey => $lwrDir)
                            <option value="{{ $lwrKey }}" @if($filters['dir'] === $lwrKey) selected @endif>{{ $lwrDir['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-lg-2 col-md-3 mb-2">
                    <label>Proof</label>
                    <select name="state" class="form-control form-control-sm">
                        <option value="">Any</option>
                        @foreach(\App\Models\HmisHandover::STATES as $lwrKey => $lwrState)
                            <option value="{{ $lwrKey }}" @if($filters['state'] === $lwrKey) selected @endif>{{ $lwrState['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="form-group col-lg-2 col-md-4 mb-2">
                <label>Lab</label>
                <select name="lab" class="form-control form-control-sm">
                    <option value="">Every lab</option>
                    <option value="internal" @if($filters['lab'] === 'internal') selected @endif>In-house bench</option>
                    @foreach($labOptions as $lwrVendorId => $lwrVendorName)
                        <option value="{{ $lwrVendorId }}" @if($filters['lab'] === (string) $lwrVendorId) selected @endif>{{ $lwrVendorName }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-lg-3 col-md-8 mb-2">
                <label>{{ $tab === 'jobs' ? 'Raised between' : 'Happened between' }}</label>
                <div class="d-flex" style="gap:6px;">
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $filters['from'] }}">
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $filters['to'] }}">
                </div>
            </div>
        </div>

        <div class="d-flex" style="gap:8px;">
            <button type="submit" class="btn btn--primary btn-sm">Filter</button>
            <a href="{{ route('vendor.opd.lab-work.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
    </form>

    @if($tab === 'jobs')
        <div class="lwr-list">
            <div class="lwr-head">
                <div>Patient</div>
                <div>Work</div>
                <div>Lab &amp; dates</div>
                <div>Action</div>
            </div>

            @forelse($jobs as $job)
                @php
                    $lwrColour = $lwrColours[$job->status] ?? ['#475569', '#f1f5f9'];
                    $lwrTrail  = $trails[$job->id] ?? collect();
                    $lwrLast   = $lwrTrail->first();
                    $lwrLate   = $job->is_open && $job->expected_on && $job->expected_on->isPast();
                    $lwrLabPh  = $job->contactPhone();
                    $lwrOpen   = $job->opd_visit_id
                        ? route('vendor.opd.show', $job->opd_visit_id)
                        : route('vendor.patient.show', $job->patient_id);
                    $lwrUnvouched = $lwrTrail->firstWhere('verify_state', 'provisional');
                @endphp

                <div class="lwr-row {{ $job->is_open ? '' : 'is-closed' }} {{ $lwrLate ? 'is-late' : '' }}">
                    {{-- Who it is for. First, because every question asked at the counter starts
                         with a patient standing there or a name on the phone. --}}
                    <div class="lwr-cell" data-l="Patient">
                        <a href="{{ $lwrOpen }}" class="lwr-name">{{ $job->patient?->name ?: 'Patient #' . $job->patient_id }}</a>
                        <div class="lwr-meta">
                            {{ $job->patient?->patient_uid }}
                            @if(filled($job->patient?->phone))
                                · <a href="tel:{{ $job->patient->phone }}">{{ $job->patient->phone }}</a>
                            @endif
                        </div>
                        @if($job->last_notified_status === $job->status && $job->last_notified_at)
                            <div class="lwr-ok"><i class="tio-checkmark-circle"></i> Told {{ $job->last_notified_at->diffForHumans() }}</div>
                        @endif
                    </div>

                    {{-- What it is, and where it has got to. --}}
                    <div class="lwr-cell" data-l="Work">
                        <div class="lwr-name">
                            {{ $job->work_type }}
                            @if(filled($job->site))
                                <span class="lwr-dim">· {{ $job->site }}</span>
                            @endif
                        </div>
                        <div class="lwr-meta">
                            <span class="lwr-pill" style="color:{{ $lwrColour[0] }}; background:{{ $lwrColour[1] }};">
                                {{ $job->statusLabel($profile) }}
                            </span>
                            @if($job->amount)
                                <span class="ml-1">{{ $lwrCurrency }}{{ number_format($job->amount, 2) }}</span>
                            @endif
                        </div>
                        @if(filled($job->remake_reason) && $job->status === 'remake')
                            <div class="lwr-meta" style="color:#b91c1c;">{{ \Illuminate\Support\Str::limit($job->remake_reason, 64) }}</div>
                        @endif
                    </div>

                    {{-- Who has it, and when it was promised. --}}
                    <div class="lwr-cell" data-l="Lab &amp; dates">
                        <div class="lwr-name">
                            {{ $job->labDisplayName() }}
                            <span class="lwr-tag {{ $job->is_internal ? 'is-int' : 'is-ext' }}">{{ $job->is_internal ? 'In-house' : 'External' }}</span>
                        </div>
                        @if(filled($lwrLabPh))
                            <div class="lwr-meta"><a href="tel:{{ $lwrLabPh }}">{{ $lwrLabPh }}</a></div>
                        @endif
                        <div class="lwr-meta">
                            @if($job->sent_on)
                                Sent <b>{{ $job->sent_on->format('d M') }}</b>
                            @endif

                            @if($job->expected_on)
                                @if($job->sent_on) · @endif
                                <span class="{{ $lwrLate ? 'lwr-late' : '' }}">Due</span>
                                <b class="{{ $lwrLate ? 'lwr-late' : '' }}">{{ $job->expected_on->format('d M') }}</b>
                            @endif

                            @if($job->received_on)
                                · Back <b>{{ $job->received_on->format('d M') }}</b>
                            @endif
                        </div>
                        @if($lwrLast)
                            <div class="lwr-meta">
                                {{ $lwrLast->is_inbound ? '↓' : '↑' }} {{ $lwrLast->person_name }}
                                {{ $lwrLast->is_inbound ? 'delivered' : 'collected' }}
                                · {{ optional($lwrLast->happened_at)->format('d M, h:i A') }}
                            </div>
                        @endif
                    </div>

                    {{-- What can be done from here: move the stage, record who is at the counter,
                         tell the lab. Anything else — the specification, the price, a remake and its
                         reason — stays on the consultation record behind Open. --}}
                    <div class="lwr-cell" data-l="Action">
                        @if($lwrCanEdit)
                            <form method="POST" action="{{ route('vendor.opd.lab-work.status', $job->id) }}" class="lwr-move">
                                @csrf
                                {{-- Every stage, including remake, so the box always shows where the
                                     job actually is. Choosing remake from here bounces back asking
                                     for a reason — that belongs on the record with the corrected
                                     specification, not on a one-line row. A job already at remake
                                     carries its stored reason through, so moving it on from here is
                                     not blocked by a box this form does not show. --}}
                                <select name="status" class="form-control form-control-sm"
                                        title="Remakes are recorded on the patient's record, where the reason and the corrected measurements go">
                                    @foreach($lwrStatuses as $lwrKey => $lwrLabel)
                                        <option value="{{ $lwrKey }}" @if($lwrKey === $job->status) selected @endif>{{ $lwrLabel }}</option>
                                    @endforeach
                                </select>

                                @if($job->status === 'remake' && filled($job->remake_reason))
                                    <input type="hidden" name="remake_reason" value="{{ $job->remake_reason }}">
                                @endif

                                @if(filled($job->patient?->phone))
                                    <label class="lwr-tell">
                                        <input type="checkbox" name="notify" value="1">Tell patient
                                    </label>
                                @endif

                                <button type="submit" class="lwr-go">Move</button>
                            </form>
                        @endif

                        <div class="lwr-links">
                            {{-- The counter form, on the two moves that are a physical exchange.
                                 Only for work that leaves the building: an in-house job never has a
                                 stranger at the counter, and offering the form on one invites
                                 handovers that are really staff walking down a corridor. --}}
                            @if($lwrCanEdit && $job->is_external && $job->is_open)
                                <a href="javascript:void(0)" class="lwr-link is-out"
                                   onclick="hoOpen({{ $job->id }}, 'out', 'opd_lab_work')" title="Someone is here to collect this">
                                    <span class="g">↑</span> Going out
                                </a>
                                <a href="javascript:void(0)" class="lwr-link is-in"
                                   onclick="hoOpen({{ $job->id }}, 'in', 'opd_lab_work')" title="Someone has brought this back">
                                    <span class="g">↓</span> Coming in
                                </a>
                            @endif

                            @if($lwrCanEdit && filled($lwrLabPh) && ($job->sent_on || $job->received_on))
                                <form method="POST" action="{{ route('vendor.opd.lab-work.handover', $job->id) }}">
                                    @csrf
                                    <button type="submit" class="lwr-link" title="Send the lab a confirmation of the handover">
                                        Confirm handover
                                    </button>
                                </form>
                            @endif

                            <a href="{{ $lwrOpen }}" class="lwr-link">Open record</a>
                        </div>
                    </div>

                    {{-- An arrival nobody has vouched for, on the row itself rather than only inside
                         the record: the point is that whoever is about to fit this piece sees that
                         its origin was never confirmed before they do. --}}
                    @if($lwrUnvouched)
                        <div class="lwr-warn">
                            <strong>Not yet confirmed</strong> with {{ $lwrUnvouched->lab_name ?: 'the lab' }} —
                            brought by {{ $lwrUnvouched->person_name }}
                            on {{ optional($lwrUnvouched->happened_at)->format('d M, h:i A') }}.
                            @if($lwrCanEdit)
                                <form method="POST" action="{{ route('vendor.handover.confirm', $lwrUnvouched->id) }}" class="form-inline mt-1">
                                    @csrf
                                    <input type="text" name="how" class="form-control form-control-sm" style="max-width:300px;"
                                           maxlength="255" required placeholder="How did you confirm it? e.g. rang lab, spoke to Anil">
                                    <button type="submit" class="lwr-link ml-2">Mark confirmed</button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="lwr-empty">No {{ $profile['unit'] }} matches this filter.</div>
            @endforelse
        </div>

        <div class="d-flex justify-content-end mt-3">{{ $jobs->links() }}</div>

        {{-- One modal for the whole page: it is a single form whose subject is set when it opens,
             and a copy inside every row would duplicate the signature canvas as many times as
             there are jobs on the page. --}}
        @if($lwrCanEdit)
            @include('hmis::vendor.handover._modal', ['hoSubjectType' => 'opd_lab_work'])
        @endif
    @else
        <div class="lwr-list is-log">
            <div class="lwr-head">
                <div>When</div>
                <div>Who was here</div>
                <div>Patient &amp; work</div>
                <div>Proof</div>
            </div>

            @forelse($handovers as $ho)
                @php
                    $lwrHoColour = $ho->stateColour();
                    $lwrHoJob    = $subjects[$ho->subject_id] ?? null;
                    $lwrHoRecord = $lwrHoJob && $lwrHoJob->opd_visit_id
                        ? route('vendor.opd.show', $lwrHoJob->opd_visit_id)
                        : ($ho->patient_id ? route('vendor.patient.show', $ho->patient_id) : null);
                @endphp

                <div class="lwr-row">
                    <div class="lwr-cell" data-l="When">
                        <div class="lwr-name">{{ optional($ho->happened_at)->format('d M Y') }}</div>
                        <div class="lwr-meta">
                            {{ optional($ho->happened_at)->format('h:i A') }} ·
                            <span style="color:{{ $ho->is_inbound ? '#059669' : '#d97706' }}; font-weight:700;">
                                {{ $ho->is_inbound ? '↓ In' : '↑ Out' }}
                            </span>
                        </div>
                    </div>

                    <div class="lwr-cell" data-l="Who was here">
                        <div class="lwr-name">{{ $ho->person_name }}</div>
                        <div class="lwr-meta">
                            @if(filled($ho->lab_name))
                                <b>{{ $ho->lab_name }}</b>
                            @endif

                            @if(filled($ho->person_phone))
                                · {{ $ho->person_phone }}
                            @endif

                            @if(filled($ho->person_id_ref))
                                · ID {{ $ho->person_id_ref }}
                            @endif
                        </div>
                        <div class="lwr-meta">{{ $ho->is_inbound ? 'to' : 'from' }} <b>{{ $ho->staff_name ?: 'Staff' }}</b></div>
                    </div>

                    <div class="lwr-cell" data-l="Patient &amp; work">
                        @if($lwrHoRecord)
                            <a href="{{ $lwrHoRecord }}" class="lwr-name">{{ $ho->patient?->name ?: 'Patient #' . $ho->patient_id }}</a>
                        @else
                            <div class="lwr-name">{{ $ho->patient?->name ?: '—' }}</div>
                        @endif
                        <div class="lwr-meta">{{ $lwrHoJob ? $lwrHoJob->title() : 'Job removed' }}</div>
                        <div class="lwr-meta">
                            {{ trim((string) $ho->purpose) ?: 'Work' }}
                            @if($ho->item_count)
                                · {{ $ho->item_count }} item{{ $ho->item_count > 1 ? 's' : '' }}
                            @endif

                            @if(filled($ho->item_note))
                                · {{ $ho->item_note }}
                            @endif
                        </div>
                    </div>

                    <div class="lwr-cell" data-l="Proof">
                        <span class="lwr-pill" style="color:{{ $lwrHoColour[0] }}; background:{{ $lwrHoColour[1] }};">
                            {{ $ho->stateLabel() }}
                        </span>
                        <div class="lwr-links">
                            @if($ho->mediaUrl('signature_path'))
                                <a href="{{ $ho->mediaUrl('signature_path') }}" target="_blank" class="lwr-link">Signature</a>
                            @endif

                            @if($ho->mediaUrl('photo_path'))
                                <a href="{{ $ho->mediaUrl('photo_path') }}" target="_blank" class="lwr-link">Photo</a>
                            @endif

                            <a href="{{ route('vendor.handover.slip', $ho->id) }}" target="_blank" class="lwr-link">Slip</a>
                        </div>

                        @if($lwrCanEdit && $ho->verify_state === 'provisional')
                            <form method="POST" action="{{ route('vendor.handover.confirm', $ho->id) }}" class="form-inline mt-1">
                                @csrf
                                <input type="text" name="how" class="form-control form-control-sm" style="max-width:240px; height:28px; font-size:11px;"
                                       maxlength="255" required placeholder="How did you confirm it?">
                                <button type="submit" class="lwr-link ml-2">Mark confirmed</button>
                            </form>
                        @endif
                    </div>

                    {{-- Recorded against what the record said should be happening. The single most
                         interesting row in the log when something has gone wrong, so it is never
                         folded away behind the job. --}}
                    @if(!$ho->dispatch_expected && filled($ho->override_reason))
                        <div class="lwr-warn">Recorded against the record: {{ $ho->override_reason }}</div>
                    @endif
                </div>
            @empty
                <div class="lwr-empty">No handovers recorded for this filter.</div>
            @endforelse
        </div>

        <div class="d-flex justify-content-end mt-3">{{ $handovers->links() }}</div>
    @endif
</div>
@endsection
