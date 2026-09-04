@extends('layouts.admin.app')

@section('title', translate('WhatsApp Intelligence'))

@push('css_or_js')
<style>
    .wg-stat { border:1px solid #e7eaf3; border-radius:10px; background:#fff; padding:14px 16px; height:100%; }
    .wg-stat .lbl { font-size:11.5px; text-transform:uppercase; letter-spacing:.4px; color:#8c98a4; }
    .wg-stat .val { font-size:22px; font-weight:700; color:#1e2022; line-height:1.3; }
    .wg-stat .sub { font-size:11.5px; color:#8c98a4; }
    .wg-chip { display:inline-block; font-size:11px; font-weight:600; padding:2px 9px; border-radius:999px; }
    .wg-chip.sale { background:#e7f7ec; color:#137a3e; }
    .wg-chip.lead { background:#e6f6fb; color:#0b6f8a; }
    .wg-chip.payment { background:#fff4e0; color:#a05a00; }
    .wg-chip.task { background:#e0ecff; color:#1e40af; }
    .wg-chip.task_update { background:#eef0ff; color:#4338ca; }
    .wg-chip.issue { background:#fdeaea; color:#c0392b; }
    .wg-chip.decision { background:#f3e8ff; color:#6b21a8; }
    .wg-chip.followup { background:#fdf2d0; color:#8a6100; }
    .wg-chip.note { background:#f1f3f5; color:#56606e; }
    .wg-status { font-size:10.5px; font-weight:600; padding:1px 7px; border-radius:6px; border:1px solid #dfe3e8; color:#56606e; }
    .wg-status.done { background:#e7f7ec; color:#137a3e; border-color:#bfe6cd; }
    .wg-status.blocked { background:#fdeaea; color:#c0392b; border-color:#f3c7c7; }
    .wg-status.in_progress { background:#fff4e0; color:#a05a00; border-color:#f0dcb8; }
    .wg-quote { font-size:12px; color:#667781; border-left:2px solid #dfe3e8; padding-left:8px; margin-top:5px;
                display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .wg-filters .form-control, .wg-filters .btn { font-size:13px; }
    .wg-conf { font-size:10.5px; color:#98a2b3; }
    .wg-title { font-weight:600; color:#1e2022; font-size:13.5px; }
    .wg-summary { font-size:12.5px; color:#56606e; }
    .wg-where { font-size:11px; color:#8c98a4; }
    .wg-where i { font-size:12px; }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <h1 class="page-header-title mb-0">
            <i class="tio-chart-bar-4"></i> {{ translate('WhatsApp Intelligence') }}
        </h1>
        <div class="d-flex align-items-center" style="gap:8px;">
            <a href="{{ route('admin.business-settings.third-party.wa-chats') }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-users"></i> {{ translate('Chats') }}
            </a>
            <a href="{{ route('admin.business-settings.third-party.wa-chat-messages') }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-chat"></i> {{ translate('Raw messages') }}
            </a>
            <form method="post" action="{{ route('admin.business-settings.third-party.wa-chat-analyze') }}" class="mb-0">
                @csrf
                <input type="hidden" name="chat" value="{{ $chat }}">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="tio-magic-wand"></i> {{ translate('Analyse now') }}
                    @if ($stats['pending'])
                        <span class="badge badge-light ml-1">{{ $stats['pending'] }}</span>
                    @endif
                </button>
            </form>
        </div>
    </div>

    @if ($stats['bridge_stale'])
        <div class="alert alert-warning py-2" style="font-size:13px;">
            <i class="tio-warning"></i>
            {{ translate('No message has arrived in the last 12 hours. Check that the Baileys bridge process is running and still paired.') }}
        </div>
    @endif

    <div class="row mb-3">
        <div class="col-sm-6 col-lg-3 mb-2">
            <div class="wg-stat">
                <div class="lbl">{{ translate('Sales this month') }}</div>
                <div class="val">{{ number_format($stats['sales_month'], 0) }}</div>
                <div class="sub">{{ translate('as read from the chats') }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-2">
            <div class="wg-stat">
                <div class="lbl">{{ translate('Leads this month') }}</div>
                <div class="val">{{ number_format($stats['new_leads']) }}</div>
                <div class="sub">{{ translate('enquiries not yet closed') }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-2">
            <div class="wg-stat">
                <div class="lbl">{{ translate('Open tasks & follow-ups') }}</div>
                <div class="val">{{ number_format($stats['open_items']) }}</div>
                <div class="sub">{{ translate('not marked done') }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-2">
            <div class="wg-stat">
                <div class="lbl">{{ translate('Archived') }}</div>
                <div class="val">{{ number_format($stats['messages']) }}</div>
                <div class="sub">
                    {{ $stats['chats'] }} {{ translate('chats') }} ·
                    {{ $stats['last_message'] ? \Carbon\Carbon::parse($stats['last_message'])->timezone('Asia/Kolkata')->diffForHumans() : translate('nothing yet') }}
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="get" class="form-row align-items-center wg-filters">
                <div class="col-md-2 mb-1">
                    <select name="kind" class="form-control form-control-sm">
                        <option value="">{{ translate('All kinds') }}</option>
                        @foreach (\App\Services\WaChatArchive::KINDS as $k)
                            <option value="{{ $k }}" {{ $kind === $k ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $k)) }} ({{ $counts[$k] ?? 0 }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-1">
                    <select name="chat" class="form-control form-control-sm">
                        <option value="">{{ translate('All chats') }}</option>
                        @foreach ($chats as $c)
                            <option value="{{ $c->chat_jid }}" {{ $chat === $c->chat_jid ? 'selected' : '' }}>
                                {{ $c->name ?: ($c->phone ?: $c->chat_jid) }} ({{ $c->message_count }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-1">
                    <select name="type" class="form-control form-control-sm">
                        <option value="">{{ translate('Groups & DMs') }}</option>
                        <option value="group" {{ $type === 'group' ? 'selected' : '' }}>{{ translate('Groups only') }}</option>
                        <option value="dm" {{ $type === 'dm' ? 'selected' : '' }}>{{ translate('One-to-one only') }}</option>
                    </select>
                </div>
                <div class="col-md-1 mb-1">
                    <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-1 mb-1">
                    <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 mb-1">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm"
                           placeholder="{{ translate('Search') }}">
                </div>
                <div class="col-md-1 mb-1">
                    <button class="btn btn-sm btn-primary btn-block"><i class="tio-filter-list"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-2">
            <h5 class="card-title mb-0">{{ translate('Extracted') }} ({{ $insights->total() }})</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:110px;">{{ translate('Kind') }}</th>
                            <th>{{ translate('What happened') }}</th>
                            <th style="width:150px;">{{ translate('Who') }}</th>
                            <th style="width:110px;" class="text-right">{{ translate('Amount') }}</th>
                            <th style="width:130px;">{{ translate('When') }}</th>
                            <th style="width:120px;" class="text-right">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($insights as $row)
                            <tr>
                                <td>
                                    <span class="wg-chip {{ $row->kind }}">{{ str_replace('_', ' ', $row->kind) }}</span>
                                    @if ($row->status)
                                        <div class="mt-1"><span class="wg-status {{ $row->status }}">{{ str_replace('_', ' ', $row->status) }}</span></div>
                                    @endif
                                </td>
                                <td>
                                    <div class="wg-title">{{ $row->title }}</div>
                                    @if ($row->summary)
                                        <div class="wg-summary">{{ $row->summary }}</div>
                                    @endif
                                    @if ($row->message_body)
                                        <div class="wg-quote">"{{ \Illuminate\Support\Str::limit($row->message_body, 220) }}"</div>
                                    @endif
                                    <div class="wg-where mt-1">
                                        <i class="{{ $row->message_chat_type === 'group' ? 'tio-users' : 'tio-user' }}"></i>
                                        {{ $row->message_chat ?: $row->chat_jid }}
                                        @if ($row->due_date)
                                            &nbsp;·&nbsp;<i class="tio-calendar"></i> {{ translate('due') }} {{ $row->due_date }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:12.5px;">{{ $row->counterparty ?: ($row->assignee ?: ($row->reporter ?: $row->message_sender)) }}</div>
                                    @if ($row->assignee && $row->counterparty)
                                        <div class="wg-conf">{{ translate('owner') }}: {{ $row->assignee }}</div>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if ($row->amount)
                                        <strong>{{ $row->currency ?: 'INR' }} {{ number_format($row->amount, 2) }}</strong>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-size:12.5px;">
                                        {{ $row->occurred_at ? \Carbon\Carbon::parse($row->occurred_at)->timezone('Asia/Kolkata')->format('d M, h:i A') : '—' }}
                                    </div>
                                    @if ($row->confidence !== null)
                                        <div class="wg-conf">{{ translate('confidence') }} {{ number_format($row->confidence * 100) }}%</div>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <form method="post" action="{{ route('admin.business-settings.third-party.wa-chat-insight-update', $row->id) }}" class="d-inline mb-0">
                                        @csrf
                                        <select name="status" class="form-control form-control-sm d-inline-block" style="width:auto;"
                                                onchange="this.form.submit()">
                                            <option value="">{{ translate('status') }}</option>
                                            @foreach (['open', 'in_progress', 'blocked', 'done'] as $s)
                                                <option value="{{ $s }}" {{ $row->status === $s ? 'selected' : '' }}>{{ str_replace('_', ' ', $s) }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                    <form method="post" action="{{ route('admin.business-settings.third-party.wa-chat-insight-delete', $row->id) }}" class="d-inline mb-0"
                                          onsubmit="return confirm('{{ translate('Remove this record?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    {{ translate('Nothing extracted yet. Once the bridge is archiving, analysis runs every 15 minutes.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($insights->hasPages())
            <div class="card-footer py-2">
                {!! $insights->links() !!}
            </div>
        @endif
    </div>
</div>
@endsection
