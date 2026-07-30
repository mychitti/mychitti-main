@extends('layouts.vendor.app')

@section('title', 'WhatsApp Chatbot')

@push('css_or_js')
    <style>
        .wc { --wc-green:#25d366; --wc-green-d:#128c7e; --wc-ink:#0f172a; --wc-ink-2:#334155;
              --wc-mute:#7c8798; --wc-line:#e9edf3; --wc-soft:#f6f8fb; }
        .wc-card { border:1px solid var(--wc-line); border-radius:16px; background:#fff;
                   margin-bottom:20px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
        .wc-card-h { padding:16px 22px; border-bottom:1px solid var(--wc-line); display:flex;
                     align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
        .wc-card-t { font-weight:700; font-size:14.5px; color:var(--wc-ink); margin:0; }
        .wc-card-b { padding:22px; }
        .wc-sub { font-size:12px; color:var(--wc-mute); line-height:1.6; }

        .wc-chip { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; padding:4px 11px;
                   border-radius:999px; font-weight:650; }
        .wc-chip-ok { background:#e7f9ef; color:#0f7a43; }
        .wc-chip-off { background:#eef1f6; color:#5b6675; }

        /* live status banner */
        .wc-live { display:flex; align-items:flex-start; gap:14px; padding:18px 20px; border-radius:14px;
                   background:linear-gradient(135deg,#f4fdf8,#fff); border:1.5px solid var(--wc-green); }
        .wc-live.off { background:var(--wc-soft); border-color:var(--wc-line); }
        .wc-live-i { width:42px; height:42px; border-radius:12px; flex-shrink:0; display:flex;
                     align-items:center; justify-content:center; font-size:20px;
                     background:var(--wc-green); color:#fff; }
        .wc-live.off .wc-live-i { background:#cbd5e1; }
        .wc-live-t { font-weight:700; font-size:14px; color:var(--wc-ink); }

        /* permission rows */
        .wc-item { display:flex; align-items:flex-start; gap:14px; padding:16px 0;
                   border-bottom:1px solid #f2f5f9; margin:0; cursor:pointer; }
        .wc-item:last-of-type { border-bottom:0; }
        .wc-item input { margin-top:3px; width:16px; height:16px; flex-shrink:0; cursor:pointer; }
        .wc-item-l { font-size:13.5px; font-weight:650; color:var(--wc-ink); }
        .wc-item-d { font-size:12px; color:var(--wc-mute); line-height:1.6; margin-top:3px; }
        .wc-item.is-action { background:var(--wc-soft); border-radius:12px; padding:16px;
                             border-bottom:0; margin-bottom:6px; }

        .wc-note { font-size:11.5px; color:var(--wc-mute); line-height:1.6; background:var(--wc-soft);
                   border-radius:10px; padding:12px 14px; }
        .wc-empty { text-align:center; padding:34px 16px; color:var(--wc-mute); }
        .wc-empty i { font-size:30px; color:#cbd5e1; display:block; margin-bottom:10px; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid wc">
        @php
            $planMeta = $plans[$currentPlan];
            $endsOn   = $subscription && $subscription->current_period_end
                ? \Carbon\Carbon::parse($subscription->current_period_end)->format('d M Y')
                : null;
        @endphp

        <div class="page-header d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:12px;">
            <div>
                <h1 class="page-header-title mb-1"><i class="tio-android"></i> WhatsApp Chatbot</h1>
                <span class="wc-sub">What your AI Agent is allowed to do, and what it may tell customers.</span>
            </div>
            <a href="{{ route('vendor.whatsapp.billing') }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-wallet"></i> Plan &amp; Billing
            </a>
        </div>

        {{-- ── Is the agent live? ────────────────────────────────────── --}}
        <div class="wc-card">
            <div class="wc-card-b">
                @if ($agentActive)
                    <div class="wc-live">
                        <span class="wc-live-i"><i class="tio-android"></i></span>
                        <div>
                            <div class="wc-live-t">
                                Your AI Agent is answering customers
                                <span class="wc-chip wc-chip-ok ml-2"><i class="tio-checkmark-circle"></i> Live</span>
                            </div>
                            <div class="wc-sub mt-1">
                                On <b>{{ $planMeta['label'] }}</b>@if ($endsOn), active until {{ $endsOn }}@endif.
                                It answers from your
                                <a href="{{ route('vendor.whatsapp.knowledge') }}">Auto-Reply Knowledge</a>
                                and handles leads and appointments — within the limits you set below.
                            </div>
                        </div>
                    </div>
                @else
                    <div class="wc-live off">
                        <span class="wc-live-i"><i class="tio-remove-circle"></i></span>
                        <div>
                            <div class="wc-live-t">
                                {{ $hasPlan ? 'No chatbot on ' . $planMeta['label'] : 'No plan yet, so no chatbot' }}
                                <span class="wc-chip wc-chip-off ml-2">Off</span>
                            </div>
                            <div class="wc-sub mt-1">
                                Incoming messages go straight to your team in the
                                <a href="{{ route('vendor.whatsapp.inbox') }}">Inbox</a> — nothing is answered
                                automatically. Move to <b>AI Agent Starter</b> or <b>Pro</b> on
                                <a href="{{ route('vendor.whatsapp.billing') }}">Plan &amp; Billing</a> to switch it on.
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Permissions ───────────────────────────────────────────── --}}
        <div class="wc-card">
            <div class="wc-card-h">
                <div>
                    <h2 class="wc-card-t">What your AI Agent may do</h2>
                    <span class="wc-sub">Tick only what you are comfortable with — anything unticked never reaches the bot at all.</span>
                </div>
            </div>
            <div class="wc-card-b">
                @if (!$agentActive)
                    <div class="wc-empty">
                        <i class="tio-lock-outlined"></i>
                        <div style="font-weight:650;color:var(--wc-ink-2);font-size:13px;">Nothing to configure yet</div>
                        <div class="wc-sub mt-1" style="max-width:340px;margin:0 auto;">
                            These settings apply once you are on a plan that includes the AI Agent.
                        </div>
                    </div>
                @else
                    <form action="{{ route('vendor.whatsapp.bot.shares') }}" method="post">
                        @csrf
                        @foreach ($shareItems as $key => $meta)
                            <label class="wc-item {{ $key === 'booking' ? 'is-action' : '' }}">
                                <input type="checkbox" name="items[]" value="{{ $key }}"
                                       {{ !empty($shares[$key]) ? 'checked' : '' }}>
                                <span>
                                    <span class="wc-item-l">{{ $meta['label'] }}</span>
                                    <span class="wc-item-d">{{ $meta['desc'] }}</span>
                                </span>
                            </label>
                        @endforeach

                        <div class="wc-note mt-3">
                            The agent can only repeat what it has been shown. An unticked item is never put in
                            front of the model, so it cannot be leaked by a cleverly worded question — the bot
                            simply tells the customer your team will follow up.
                        </div>

                        <button type="submit" class="btn btn--primary mt-3">
                            <i class="tio-save"></i> Save settings
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
