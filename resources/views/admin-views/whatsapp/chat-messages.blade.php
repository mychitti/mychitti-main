@extends('layouts.admin.app')

@section('title', translate('WhatsApp Archive'))

@push('css_or_js')
<style>
    .wgm-body { font-size:13px; color:#1e2022; white-space:pre-wrap; word-break:break-word; max-width:720px; }
    .wgm-quote { font-size:11.5px; color:#667781; border-left:2px solid #dfe3e8; padding-left:8px; margin-bottom:4px; }
    .wgm-type { font-size:10.5px; text-transform:uppercase; letter-spacing:.3px; color:#98a2b3; }
    .wgm-pending { font-size:10.5px; color:#a05a00; background:#fff4e0; border-radius:6px; padding:1px 6px; }
    .wgm-me { font-size:10.5px; color:#137a3e; background:#e7f7ec; border-radius:6px; padding:1px 6px; }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <h1 class="page-header-title mb-0"><i class="tio-chat"></i> {{ translate('WhatsApp Archive') }}</h1>
        <div class="d-flex align-items-center" style="gap:8px;">
            <a href="{{ route('admin.business-settings.third-party.wa-chats') }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-users"></i> {{ translate('Chats') }}
            </a>
            <a href="{{ route('admin.business-settings.third-party.wa-chat-feed') }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-chart-bar-4"></i> {{ translate('Extracted intelligence') }}
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="get" class="form-row align-items-center">
                <div class="col-md-4 mb-1">
                    <select name="chat" class="form-control form-control-sm">
                        <option value="">{{ translate('All chats') }}</option>
                        @foreach ($chats as $c)
                            <option value="{{ $c->chat_jid }}" {{ $chat === $c->chat_jid ? 'selected' : '' }}>
                                {{ $c->name ?: $c->chat_jid }} ({{ $c->message_count }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-1">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm"
                           placeholder="{{ translate('Search message text, sender or number') }}">
                </div>
                <div class="col-md-2 mb-1">
                    <button class="btn btn-sm btn-primary btn-block"><i class="tio-search"></i> {{ translate('Search') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-2">
            <h5 class="card-title mb-0">{{ translate('Messages') }} ({{ $messages->total() }})</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:150px;">{{ translate('Sender') }}</th>
                            <th>{{ translate('Message') }}</th>
                            <th style="width:170px;">{{ translate('Chat') }}</th>
                            <th style="width:150px;">{{ translate('Sent') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($messages as $m)
                            <tr>
                                <td>
                                    <div style="font-size:12.5px; font-weight:600;">
                                        {{ $m->from_me ? translate('You') : ($m->sender_name ?: ($m->sender_phone ?: '—')) }}
                                    </div>
                                    @if ($m->sender_phone && !$m->from_me)
                                        <div class="wgm-type">{{ $m->sender_phone }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($m->quoted_body)
                                        <div class="wgm-quote">{{ \Illuminate\Support\Str::limit($m->quoted_body, 150) }}</div>
                                    @endif
                                    <div class="wgm-body">{{ $m->body }}</div>
                                    <div class="mt-1">
                                        <span class="wgm-type">{{ $m->type }}</span>
                                        @if ($m->from_me)
                                            <span class="wgm-me ml-1">{{ translate('sent') }}</span>
                                        @endif
                                        @if (!$m->analyzed_at)
                                            <span class="wgm-pending ml-1">{{ translate('pending analysis') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td style="font-size:12px;">
                                    <i class="{{ $m->chat_type === 'group' ? 'tio-users' : 'tio-user' }}"></i>
                                    {{ $m->chat_name ?: $m->chat_jid }}
                                </td>
                                <td style="font-size:12.5px;">
                                    {{ $m->sent_at ? \Carbon\Carbon::parse($m->sent_at)->timezone('Asia/Kolkata')->format('d M Y, h:i A') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    {{ translate('No messages archived yet. Pair the Baileys bridge and it will start streaming here.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($messages->hasPages())
            <div class="card-footer py-2">
                {!! $messages->links() !!}
            </div>
        @endif
    </div>
</div>
@endsection
