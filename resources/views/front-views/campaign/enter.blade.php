@extends('front-views.campaign_layout')

@section('title', ($campaign?->name ?? 'Campaign') . ' – Enter')

@section('content')

<section class="page-hero">
    <div class="container">
        <span class="hero-eyebrow">{{ $campaign?->name ?? 'Campaign' }}</span>
        <h1 class="hero-title">Enter the campaign</h1>
        @if($campaign?->end_date)
        <p class="hero-subtitle">Entries close {{ $campaign->end_date->format('d F Y') }}.</p>
        @endif
    </div>
</section>

<main class="site-main">
    <section class="section">
        <div class="container" style="max-width:600px;">

            @if(!$campaign || $campaign->status === 'ended' || $campaign->status === 'cancelled')
                <div style="text-align:center; padding:60px 0;">
                    <div style="font-size:48px; margin-bottom:16px;">🚫</div>
                    <p style="font-family:var(--font-display); font-size:22px; font-weight:700; color:var(--color-accent); margin-bottom:8px;">
                        Entries are closed
                    </p>
                    <p style="color:var(--color-text-muted);">This campaign is no longer accepting entries.</p>
                    <a href="{{ route('campaign.results', $campaign->slug) }}" class="btn btn-primary btn-lg" style="margin-top:24px;">View results →</a>
                </div>

            @elseif($campaign->require_login && !auth('web')->check())
                <div class="card" style="text-align:center; padding:48px 32px;">
                    <div style="font-size:48px; margin-bottom:16px;">🔐</div>
                    <h2 style="font-family:var(--font-display); font-size:24px; font-weight:700; color:var(--color-accent); margin-bottom:12px;">
                        Login required
                    </h2>
                    <p style="color:var(--color-text-muted); margin-bottom:28px;">
                        You need to be logged in to enter this campaign.
                    </p>
                    <a href="{{ route('user-login') }}" class="btn btn-primary btn-lg">Login to enter →</a>
                </div>

            @else
                {{-- Campaign details summary --}}
                @if($campaign->prize_title)
                <div class="card" style="margin-bottom:32px; background:var(--color-primary-light); border-color:var(--color-primary);">
                    <p class="section-label">Prize</p>
                    <h3 style="font-family:var(--font-display); font-size:20px; font-weight:700; color:var(--color-accent);">
                        {{ $campaign->prize_title }}
                    </h3>
                    @if($campaign->prize_value)
                    <p style="color:var(--color-primary-dark); font-weight:600; margin-top:4px;">
                        ₹{{ number_format($campaign->prize_value) }}
                    </p>
                    @endif
                </div>
                @endif

                {{-- Entry info --}}
                <div class="card" style="margin-bottom:24px;">
                    <p class="section-label">Entry details</p>
                    <ul style="display:flex; flex-direction:column; gap:12px; margin-top:12px; color:var(--color-text-muted);">
                        @if($campaign->max_entries_per_person)
                        <li style="display:flex; gap:10px;">
                            <span>🎟️</span>
                            <span>Maximum <strong>{{ $campaign->max_entries_per_person }}</strong> {{ Str::plural('entry', $campaign->max_entries_per_person) }} per person</span>
                        </li>
                        @endif
                        @if($campaign->end_date)
                        <li style="display:flex; gap:10px;">
                            <span>📅</span>
                            <span>Entries close <strong>{{ $campaign->end_date->format('d F Y') }}</strong></span>
                        </li>
                        @endif
                        @if($campaign->draw_date)
                        <li style="display:flex; gap:10px;">
                            <span>🏆</span>
                            <span>Draw on <strong>{{ $campaign->draw_date->format('d F Y') }}</strong></span>
                        </li>
                        @endif
                        @if($campaign->show_entry_count && $campaign->total_entry_limit)
                        <li style="display:flex; gap:10px;">
                            <span>👥</span>
                            <span>Total entries limited to <strong>{{ number_format($campaign->total_entry_limit) }}</strong></span>
                        </li>
                        @endif
                    </ul>
                </div>

                <p style="font-size:13px; color:var(--color-text-faint); margin-bottom:24px;">
                    By entering you agree to our <a href="{{ route('campaign.tnc', $campaign->slug) }}" style="color:var(--color-primary);">Terms &amp; conditions</a>.
                </p>

                <a href="{{ route('campaign.how-it-works', $campaign->slug) }}" class="btn btn-secondary">Learn how it works</a>
            @endif

        </div>
    </section>
</main>

@endsection
