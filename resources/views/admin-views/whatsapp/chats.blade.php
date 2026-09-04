@extends('layouts.admin.app')

@section('title', translate('WhatsApp Chats'))

@push('css_or_js')
<style>
    .wc-type { font-size:10.5px; font-weight:600; padding:1px 8px; border-radius:999px; }
    .wc-type.group { background:#e0ecff; color:#1e40af; }
    .wc-type.dm { background:#f1f3f5; color:#56606e; }
    .wc-name { font-weight:600; font-size:13.5px; color:#1e2022; }
    .wc-jid { font-size:11px; color:#98a2b3; }
    .wc-off td { background:#fbfbfc; }
    .wc-off .wc-name { color:#8c98a4; }
    .wc-tot { border:1px solid #e7eaf3; border-radius:10px; background:#fff; padding:10px 14px; }
    .wc-tot .lbl { font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:#8c98a4; }
    .wc-tot .val { font-size:19px; font-weight:700; color:#1e2022; }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <h1 class="page-header-title mb-0"><i class="tio-users"></i> {{ translate('WhatsApp Chats') }}</h1>
        <a href="{{ route('admin.business-settings.third-party.wa-chat-feed') }}" class="btn btn-sm btn-outline-primary">
            <i class="tio-chart-bar-4"></i> {{ translate('Extracted intelligence') }}
        </a>
    </div>

    <p class="text-muted" style="font-size:13px;">
        {{ translate('Every conversation the paired WhatsApp account can see. Archiving is decided by the bridge; AI analysis is per chat — turn it off for anything personal, and nothing in that chat is ever sent to a model.') }}
    </p>

    <div class="row mb-3">
        <div class="col-6 col-lg-3 mb-2"><div class="wc-tot"><div class="lbl">{{ translate('Chats') }}</div><div class="val">{{ number_format($totals['chats']) }}</div></div></div>
        <div class="col-6 col-lg-3 mb-2"><div class="wc-tot"><div class="lbl">{{ translate('Groups') }}</div><div class="val">{{ number_format($totals['groups']) }}</div></div></div>
        <div class="col-6 col-lg-3 mb-2"><div class="wc-tot"><div class="lbl">{{ translate('One-to-one') }}</div><div class="val">{{ number_format($totals['dms']) }}</div></div></div>
        <div class="col-6 col-lg-3 mb-2"><div class="wc-tot"><div class="lbl">{{ translate('AI off') }}</div><div class="val">{{ number_format($totals['excluded']) }}</div></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="get" class="form-row align-items-center">
                <div class="col-md-3 mb-1">
                    <select name="type" class="form-control form-control-sm">
                        <option value="">{{ translate('Groups & DMs') }}</option>
                        <option value="group" {{ $type === 'group' ? 'selected' : '' }}>{{ translate('Groups only') }}</option>
                        <option value="dm" {{ $type === 'dm' ? 'selected' : '' }}>{{ translate('One-to-one only') }}</option>
                    </select>
                </div>
                <div class="col-md-7 mb-1">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm"
                           placeholder="{{ translate('Search by name or number') }}">
                </div>
                <div class="col-md-2 mb-1">
                    <button class="btn btn-sm btn-primary btn-block"><i class="tio-search"></i> {{ translate('Search') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-2">
            <h5 class="card-title mb-0">{{ translate('Conversations') }} ({{ $chats->total() }})</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Chat') }}</th>
                            <th style="width:100px;">{{ translate('Type') }}</th>
                            <th style="width:110px;" class="text-right">{{ translate('Messages') }}</th>
                            <th style="width:160px;">{{ translate('Last activity') }}</th>
                            <th style="width:190px;" class="text-right">{{ translate('AI analysis') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($chats as $c)
                            <tr class="{{ $c->ai_enabled ? '' : 'wc-off' }}">
                                <td>
                                    <div class="wc-name">{{ $c->name ?: ($c->phone ?: $c->chat_jid) }}</div>
                                    <div class="wc-jid">{{ $c->phone ?: $c->chat_jid }}</div>
                                </td>
                                <td><span class="wc-type {{ $c->chat_type }}">{{ $c->chat_type === 'group' ? translate('group') : translate('direct') }}</span></td>
                                <td class="text-right">{{ number_format($c->message_count) }}</td>
                                <td style="font-size:12.5px;">
                                    {{ $c->last_message_at ? \Carbon\Carbon::parse($c->last_message_at)->timezone('Asia/Kolkata')->format('d M Y, h:i A') : '—' }}
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.business-settings.third-party.wa-chat-messages', ['chat' => $c->chat_jid]) }}"
                                       class="btn btn-sm btn-outline-secondary" title="{{ translate('View messages') }}">
                                        <i class="tio-chat"></i>
                                    </a>
                                    <form method="post" action="{{ route('admin.business-settings.third-party.wa-chat-toggle', $c->id) }}" class="d-inline mb-0">
                                        @csrf
                                        <button class="btn btn-sm {{ $c->ai_enabled ? 'btn-success' : 'btn-outline-secondary' }}">
                                            <i class="{{ $c->ai_enabled ? 'tio-toggle-on' : 'tio-toggle-off' }}"></i>
                                            {{ $c->ai_enabled ? translate('On') : translate('Off') }}
                                        </button>
                                    </form>
                                    <form method="post" action="{{ route('admin.business-settings.third-party.wa-chat-delete', $c->id) }}" class="d-inline mb-0"
                                          onsubmit="return confirm('{{ translate('Delete this chat and every message archived from it? This cannot be undone.') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="{{ translate('Delete from archive') }}"><i class="tio-delete"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    {{ translate('No chats archived yet. Pair the Baileys bridge and conversations will appear here.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($chats->hasPages())
            <div class="card-footer py-2">
                {!! $chats->links() !!}
            </div>
        @endif
    </div>
</div>
@endsection
