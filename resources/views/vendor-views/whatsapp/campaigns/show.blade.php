@extends('layouts.vendor.app')

@section('title', 'Campaign — ' . $campaign->name)

@push('css_or_js')
    @include('vendor-views.whatsapp.partials._ui')
    <style>
        .cs-funnel { display:flex; gap:6px; align-items:center; }
        .cs-bar { height:8px; border-radius:6px; background:#f1f3f7; overflow:hidden; flex:1; min-width:70px; }
        .cs-bar span { display:block; height:100%; }
        .cs-pill { font-size:11px; font-weight:600; padding:2px 9px; border-radius:20px; display:inline-block; }
        .cs-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:5px; }
        .cs-step-chip { font-size:10px; font-weight:700; padding:2px 6px; border-radius:5px; margin-right:3px; display:inline-block; }
    </style>
@endpush

@section('content')
    @php
        $chip = [
            'draft'     => 'secondary',
            'running'   => 'success',
            'paused'    => 'warning',
            'completed' => 'info',
            'cancelled' => 'danger',
        ][$campaign->status] ?? 'secondary';
    @endphp

    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap" style="gap:10px;">
            <div>
                <h1 class="page-header-title mb-0">{{ $campaign->name }}</h1>
                <span class="wa-sub">
                    <span class="wa-chip badge-soft-{{ $chip }}">{{ ucfirst($campaign->status) }}</span>
                    {{ $campaign->audience === 'platform' ? 'MyChitti customers' : 'My customers' }} ·
                    {{ $summary['recipients'] }} {{ $summary['recipients'] == 1 ? 'person' : 'people' }} ·
                    {{ count($stats) }} {{ count($stats) == 1 ? 'template' : 'templates' }} in the series
                </span>
                @if ($campaign->last_error)
                    <div class="wa-sub text-warning mt-1">{{ $campaign->last_error }}</div>
                @endif
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                <a href="{{ route('vendor.whatsapp.campaigns') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="tio-back-ui"></i> All campaigns
                </a>
                @if (hasPermission('whatsapp_campaigns', 'export'))
                    <a href="{{ route('vendor.whatsapp.campaigns.export', $campaign->id) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="tio-download-to"></i> Export CSV
                    </a>
                @endif
                @if (in_array($campaign->status, ['draft', 'paused']) && hasPermission('whatsapp_campaigns', 'status_change'))
                    <form action="{{ route('vendor.whatsapp.campaigns.start', $campaign->id) }}" method="post" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn--primary">
                            <i class="tio-play"></i> {{ $campaign->status === 'paused' ? 'Resume series' : 'Start series' }}
                        </button>
                    </form>
                @endif
                @if ($campaign->status === 'running' && hasPermission('whatsapp_campaigns', 'status_change'))
                    <form action="{{ route('vendor.whatsapp.campaigns.run-now', $campaign->id) }}" method="post" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-outline-primary"><i class="tio-flash"></i> Send due batch now</button>
                    </form>
                    <form action="{{ route('vendor.whatsapp.campaigns.pause', $campaign->id) }}" method="post" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-outline-warning"><i class="tio-pause"></i> Pause</button>
                    </form>
                @endif
                @if (!in_array($campaign->status, ['cancelled', 'completed']) && hasPermission('whatsapp_campaigns', 'status_change'))
                    <form action="{{ route('vendor.whatsapp.campaigns.cancel', $campaign->id) }}" method="post" class="d-inline"
                          onsubmit="return confirm('Stop this campaign for good? Tracking is kept.');">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger">Cancel</button>
                    </form>
                @endif
            </div>
        </div>

        @if ($campaign->status === 'running' && !$active)
            <div class="alert alert-warning" style="font-size:13px;">
                Your subscription isn’t active, so sending is paused at the gateway.
                @if (hasAnyModulePermission(['whatsapp_billing']))<a href="{{ route('vendor.whatsapp.billing') }}">Activate your plan</a> to let the series continue.@else Ask the owner to activate the plan to let the series continue.@endif
            </div>
        @endif

        <div class="row mb-1">
            <div class="col-sm-6 col-lg-3 wa-col">
                <div class="wa-stat">
                    <div>
                        <div class="wa-stat-val">{{ $summary['sent'] }}</div>
                        <div class="wa-stat-lbl">Messages sent</div>
                        <div class="wa-sub mt-1">{{ $summary['delivered'] }} delivered · {{ $summary['read'] }} read</div>
                    </div>
                    <div class="wa-stat-ico" style="background:#e8f7ef;color:#128c7e;"><i class="tio-send"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 wa-col">
                <div class="wa-stat">
                    <div>
                        <div class="wa-stat-val text-success">{{ $summary['interested'] }}</div>
                        <div class="wa-stat-lbl">Interested</div>
                        <div class="wa-sub mt-1">Still receiving the series</div>
                    </div>
                    <div class="wa-stat-ico" style="background:#e8f7ef;color:#128c7e;"><i class="tio-thumbs-up"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 wa-col">
                <div class="wa-stat">
                    <div>
                        <div class="wa-stat-val text-danger">{{ $summary['not_interested'] }}</div>
                        <div class="wa-stat-lbl">Not interested</div>
                        <div class="wa-sub mt-1">Excluded from later steps</div>
                    </div>
                    <div class="wa-stat-ico" style="background:#fdeaea;color:#d9534f;"><i class="tio-thumbs-down"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 wa-col">
                <div class="wa-stat">
                    <div>
                        <div class="wa-stat-val">{{ $summary['silent'] }}</div>
                        <div class="wa-stat-lbl">No reply yet</div>
                        <div class="wa-sub mt-1">They keep getting the follow-ups</div>
                    </div>
                    <div class="wa-stat-ico" style="background:#eef2f7;color:#64748b;"><i class="tio-time"></i></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 wa-col">
                <div class="wa-card h-100">
                    <div class="wa-card-h">
                        Step by step
                        <span class="wa-sub">{{ _price($estimate) }} worst-case · {{ _price($rate) }} per message</span>
                    </div>
                    <div class="wa-card-b flush">
                        <div class="table-responsive">
                            <table class="table wa-table">
                                <thead>
                                    <tr>
                                        <th>Step</th>
                                        <th class="text-center">Sent</th>
                                        <th class="text-center">Delivered</th>
                                        <th class="text-center">Read</th>
                                        <th class="text-center">Interested</th>
                                        <th class="text-center">Not int.</th>
                                        <th class="text-center">No reply</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($stats as $row)
                                        @php $step = $row['step']; @endphp
                                        <tr>
                                            <td>
                                                <b>{{ $step->step_no }}. {{ $step->template_name }}</b>
                                                <div class="wa-sub">
                                                    {{ $targets[$step->target] ?? $step->target }}
                                                    @if ($step->step_no > 1)
                                                        · after {{ $step->delay_hours }}h
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">{{ $row['sent'] }}</td>
                                            <td class="text-center">{{ $row['delivered'] }}</td>
                                            <td class="text-center">{{ $row['read'] }}</td>
                                            <td class="text-center text-success font-weight-bold">{{ $row['interested'] }}</td>
                                            <td class="text-center text-danger font-weight-bold">{{ $row['not_interested'] }}</td>
                                            <td class="text-center">{{ $row['no_reply'] }}</td>
                                            <td>
                                                @if ($step->status === 'sent')
                                                    <span class="cs-pill badge-soft-info">Done</span>
                                                @elseif ($step->status === 'sending')
                                                    <span class="cs-pill badge-soft-success">Sending</span>
                                                    <div class="wa-sub">{{ $row['pending'] }} to go</div>
                                                @elseif ($step->due_at)
                                                    <span class="cs-pill badge-soft-warning">
                                                        {{ \Carbon\Carbon::parse($step->due_at)->isPast() ? 'Due now' : 'Due ' . \Carbon\Carbon::parse($step->due_at)->diffForHumans() }}
                                                    </span>
                                                @else
                                                    <span class="cs-pill badge-soft-secondary">Waiting</span>
                                                @endif
                                                @if ($row['failed'] > 0)
                                                    <div class="wa-sub text-danger">{{ $row['failed'] }} failed</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 wa-col">
                <div class="wa-card h-100">
                    <div class="wa-card-h">Series rules</div>
                    <div class="wa-card-b">
                        <div class="wa-row">
                            <span>In the series</span>
                            <b>{{ $summary['active'] }}</b>
                        </div>
                        <div class="wa-row">
                            <span>Dropped — said no</span>
                            <b>{{ $summary['excluded'] }}</b>
                        </div>
                        <div class="wa-row">
                            <span>Dropped — opted out</span>
                            <b>{{ $summary['opted_out'] }}</b>
                        </div>
                        @if ($campaign->audience === 'platform')
                            <div class="wa-row">
                                <span>Dropped — monthly offer cap</span>
                                <b>{{ $summary['capped'] }}</b>
                            </div>
                        @endif
                        <div class="wa-row">
                            <span>Other replies</span>
                            <b>{{ $summary['other_reply'] }}</b>
                        </div>
                        <div class="wa-row">
                            <span>Failed sends</span>
                            <b>{{ $summary['failed'] }}</b>
                        </div>

                        <div class="wa-eyebrow mt-3 mb-1">How replies are read</div>
                        <div style="font-size:12px;">
                            Button taps are read exactly.
                            @if ($aiReading)
                                Typed replies are read by AI, in any language — those are marked
                                <span class="cs-pill badge-soft-info">AI</span> in the list below.
                            @else
                                Typed replies are matched against the word lists.
                            @endif
                        </div>

                        <div class="wa-eyebrow mt-3 mb-1">Counts as interested</div>
                        <div style="font-size:12px;">{{ implode(', ', $positive) }}</div>

                        <div class="wa-eyebrow mt-3 mb-1">Removes them from the series</div>
                        <div style="font-size:12px;">{{ implode(', ', $negative) }}</div>

                        <div class="wa-note mt-3">
                            Silence is not a no. Anyone who hasn’t answered keeps receiving the follow-ups; only an
                            explicit <b>no</b> — or a <b>STOP</b> — takes them out.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wa-card">
            <div class="wa-card-h">
                Who’s in it
                <span class="wa-sub" id="cs-total"></span>
            </div>
            <div class="wa-card-b">
                <div class="d-flex flex-wrap align-items-center mb-3" style="gap:8px;">
                    <ul class="nav nav-pills" style="gap:6px;">
                        <li class="nav-item"><a href="javascript:;" class="nav-link active cs-filter" data-filter="" style="font-size:12px;padding:5px 12px;">Everyone</a></li>
                        <li class="nav-item"><a href="javascript:;" class="nav-link cs-filter" data-filter="interested" style="font-size:12px;padding:5px 12px;">Interested</a></li>
                        <li class="nav-item"><a href="javascript:;" class="nav-link cs-filter" data-filter="not_interested" style="font-size:12px;padding:5px 12px;">Not interested</a></li>
                        <li class="nav-item"><a href="javascript:;" class="nav-link cs-filter" data-filter="no_reply" style="font-size:12px;padding:5px 12px;">No reply</a></li>
                        <li class="nav-item"><a href="javascript:;" class="nav-link cs-filter" data-filter="excluded" style="font-size:12px;padding:5px 12px;">Dropped</a></li>
                    </ul>
                    <input id="cs-search" type="text" class="form-control form-control-sm ml-auto" style="max-width:260px;"
                           placeholder="Search {{ $campaign->audience === 'platform' ? 'by name…' : 'name or phone…' }}">
                </div>

                <div class="table-responsive">
                    <table class="table wa-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Steps received</th>
                                <th>Answer</th>
                                <th>When</th>
                                <th>State</th>
                            </tr>
                        </thead>
                        <tbody id="cs-rows">
                            <tr><td colspan="5" class="text-center text-muted" style="font-size:13px;">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-2">
                    <button id="cs-prev" class="btn btn-sm btn-outline-secondary" disabled>Previous</button>
                    <small class="text-muted" id="cs-page"></small>
                    <button id="cs-next" class="btn btn-sm btn-outline-secondary" disabled>Next</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        (function () {
            var URL = '{{ route('vendor.whatsapp.campaigns.recipients', $campaign->id) }}';
            var page = 1, pages = 1, filter = '', search = '';

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            var STATE_LABEL = {
                active:    'In the series',
                excluded:  'Dropped — said no',
                opted_out: 'Opted out',
                capped:    'Dropped — monthly offer cap'
            };
            var REPLY_STYLE = {
                interested:     { cls: 'badge-soft-success', text: 'Interested' },
                not_interested: { cls: 'badge-soft-danger',  text: 'Not interested' },
                other:          { cls: 'badge-soft-info',    text: 'Replied' }
            };
            var SEND_STYLE = {
                read:      'badge-soft-success',
                delivered: 'badge-soft-success',
                sent:      'badge-soft-secondary',
                queued:    'badge-soft-secondary',
                failed:    'badge-soft-danger'
            };

            function load() {
                $.get(URL, { filter: filter, search: search, page: page }, function (res) {
                    if (!res.success) { return; }
                    pages = res.pages || 1;

                    var html = '';
                    (res.rows || []).forEach(function (r) {
                        var reply = REPLY_STYLE[r.reply];
                        var steps = (r.sends || []).map(function (s) {
                            return '<span class="cs-step-chip ' + (SEND_STYLE[s.status] || 'badge-soft-secondary') + '"'
                                + ' title="Step ' + s.step + ': ' + esc(s.status) + '">' + s.step + '</span>';
                        }).join('') || '<span class="text-muted">—</span>';

                        html += '<tr>'
                            + '<td><b>' + esc(r.name) + '</b><div class="wa-sub">' + esc(r.phone) + '</div></td>'
                            + '<td>' + steps + '</td>'
                            + '<td>' + (reply
                                ? '<span class="cs-pill ' + reply.cls + '">' + reply.text + '</span>'
                                  + (r.verdict_by === 'ai' ? ' <span class="cs-pill badge-soft-info" title="Read by AI">AI</span>' : '')
                                  + (r.reply_label ? '<div class="wa-sub">“' + esc(r.reply_label) + '”</div>' : '')
                                : '<span class="text-muted">No reply</span>') + '</td>'
                            + '<td class="wa-sub">' + esc(r.reply_at || '—') + '</td>'
                            + '<td class="wa-sub">' + esc(STATE_LABEL[r.state] || r.state) + '</td>'
                            + '</tr>';
                    });

                    if (!html) {
                        html = '<tr><td colspan="5" class="text-center text-muted" style="font-size:13px;">Nobody here.</td></tr>';
                    }

                    $('#cs-rows').html(html);
                    $('#cs-total').text(res.total + (res.total === 1 ? ' person' : ' people'));
                    $('#cs-page').text('Page ' + res.page + ' of ' + pages);
                    $('#cs-prev').prop('disabled', res.page <= 1);
                    $('#cs-next').prop('disabled', res.page >= pages);
                });
            }

            $('.cs-filter').on('click', function () {
                $('.cs-filter').removeClass('active');
                $(this).addClass('active');
                filter = $(this).data('filter') || '';
                page = 1;
                load();
            });

            $('#cs-search').on('input', function () {
                var term = $(this).val();
                clearTimeout(window.csTimer);
                window.csTimer = setTimeout(function () { search = term; page = 1; load(); }, 300);
            });

            $('#cs-prev').on('click', function () { if (page > 1) { page--; load(); } });
            $('#cs-next').on('click', function () { if (page < pages) { page++; load(); } });

            load();
        })();
    </script>
@endpush
