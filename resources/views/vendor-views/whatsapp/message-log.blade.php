@extends('layouts.vendor.app')

@section('title', 'Message Log')

@push('css_or_js')
    @include('vendor-views.whatsapp.partials._ui')
    <style>
        .ml-row { display:grid; grid-template-columns:150px 1fr 190px 110px; gap:12px; align-items:start;
            padding:12px 14px; border-bottom:1px solid var(--wa-line); font-size:13px; }
        .ml-row:last-child { border-bottom:0; }
        .ml-head { font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:var(--wa-mute);
            font-weight:700; background:#fbfcfe; }
        .ml-when { color:var(--wa-mute); font-size:12px; }
        .ml-what { font-weight:600; }
        .ml-why { color:var(--wa-mute); font-size:12px; margin-top:2px; }
        .ml-who { font-size:12px; }
        .ml-tpl { font-size:11px; color:var(--wa-mute); font-family:ui-monospace,SFMono-Regular,Menlo,monospace; }
        .ml-sum { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; }
        .ml-sum a { text-decoration:none; }
        @media (max-width: 767px) {
            .ml-row { grid-template-columns:1fr; gap:4px; }
            .ml-head { display:none; }
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid wa-page">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
            <div>
                <h1 class="page-header-title mb-0"><i class="tio-history"></i> Message Log</h1>
                <span class="wa-sub">Every automatic message this store tried to send, and what came of it — including the ones that were skipped.</span>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                @if (hasAnyModulePermission(['whatsapp_automation']))
                    <a href="{{ route('vendor.notification-settings', 'send') }}" class="btn btn-sm btn-outline-primary">
                        <i class="tio-settings"></i> Message settings
                    </a>
                @endif
            </div>
        </div>

        @if (!$store['connected'])
            <div class="alert alert-warning" style="font-size:13px;">
                Your own WhatsApp number isn't connected, so nothing can send at all.
                @if (hasAnyModulePermission(['whatsapp_connection']))<a href="{{ route('vendor.whatsapp.connect') }}">Connect it under WhatsApp → Connection</a>.@else Ask the owner to connect it.@endif
            </div>
        @elseif (!$store['subscription'])
            <div class="alert alert-warning" style="font-size:13px;">
                Your WhatsApp subscription isn't active, so nothing is sending.
                @if (hasAnyModulePermission(['whatsapp_billing']))<a href="{{ route('vendor.whatsapp.billing') }}">Activate it under Plan &amp; Billing</a>.@else Ask the owner to activate the plan.@endif
            </div>
        @endif

        @php
            $tones = ['sent' => 'success', 'skipped' => 'warning', 'failed' => 'danger', 'queued' => 'info'];
            $labels = ['sent' => 'Sent', 'skipped' => 'Skipped', 'failed' => 'Failed', 'queued' => 'Queued'];
        @endphp

        <div class="ml-sum">
            @foreach ($summary as $state => $count)
                <a href="{{ request()->fullUrlWithQuery(['status' => $filters['status'] === $state ? null : $state, 'page' => null]) }}"
                   class="wa-chip badge-soft-{{ $tones[$state] }} {{ $filters['status'] === $state ? 'font-weight-bold' : '' }}"
                   title="{{ $filters['status'] === $state ? 'Clear this filter' : 'Show only these' }}">
                    {{ $count }} {{ $labels[$state] }} <span style="opacity:.7">· 7 days</span>
                </a>
            @endforeach
        </div>

        <div class="wa-card mb-3">
            <form method="get" class="d-flex flex-wrap align-items-center" style="gap:8px; padding:12px 14px;">
                <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control form-control-sm"
                       placeholder="Customer, number or reason…" style="max-width:260px;">

                <select name="key" class="form-control form-control-sm" style="max-width:230px;">
                    <option value="">All message types</option>
                    @foreach ($keys as $key => $label)
                        <option value="{{ $key }}" {{ $filters['key'] === $key ? 'selected' : '' }}>{{ $label ?: $key }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-control form-control-sm" style="max-width:150px;">
                    <option value="">Any outcome</option>
                    @foreach ($labels as $state => $label)
                        <option value="{{ $state }}" {{ $filters['status'] === $state ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <button class="btn btn-sm btn--primary" type="submit"><i class="tio-search"></i> Filter</button>
                @if ($filters['search'] || $filters['key'] || $filters['status'])
                    <a href="{{ route('vendor.whatsapp.message-log') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </form>
        </div>

        <div class="wa-card">
            <div class="ml-row ml-head">
                <div>When</div>
                <div>Message</div>
                <div>Customer</div>
                <div>Outcome</div>
            </div>

            @forelse ($entries as $entry)
                <div class="ml-row">
                    <div class="ml-when">
                        {{ \Carbon\Carbon::parse($entry->created_at)->format('d M, h:i A') }}
                        <div style="font-size:11px;">{{ \Carbon\Carbon::parse($entry->created_at)->diffForHumans() }}</div>
                    </div>

                    <div>
                        <div class="ml-what">
                            {{ $entry->label ?: $entry->message_key ?: 'Message' }}
                            @if (!$entry->automatic)
                                <span class="wa-chip badge-soft-secondary" style="font-size:10px;">by hand</span>
                            @endif
                        </div>
                        @if ($entry->reason)
                            <div class="ml-why">{{ $entry->reason }}</div>
                        @endif
                        @if ($entry->template)
                            <div class="ml-tpl">{{ $entry->template }}</div>
                        @endif
                    </div>

                    <div class="ml-who">
                        {{ $entry->recipient ?: '—' }}
                        @if ($entry->sent_to)
                            <div class="ml-tpl">{{ $entry->sent_to }}</div>
                        @endif
                    </div>

                    <div>
                        <span class="wa-chip badge-soft-{{ $tones[$entry->status] ?? 'secondary' }}">
                            {{ $labels[$entry->status] ?? ucfirst($entry->status) }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="wa-empty" style="padding:36px 16px;">
                    <i class="tio-history"></i>
                    <p class="mb-1">Nothing logged yet.</p>
                    <span class="wa-sub">
                        Automatic messages appear here the moment one is attempted — sent, skipped or failed.
                    </span>
                </div>
            @endforelse
        </div>

        @if ($entries->hasPages())
            <div class="d-flex justify-content-end mt-3">{{ $entries->links() }}</div>
        @endif
    </div>
@endsection
