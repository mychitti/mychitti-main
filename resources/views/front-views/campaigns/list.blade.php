@extends('front-views.campaign_layout')

@section('title', 'All Campaigns')

@section('content')

<section class="page-hero">
    <div class="container">
        <span class="hero-eyebrow">My Chitti</span>
        <h1 class="hero-title">All Campaigns</h1>
        <p class="hero-subtitle">Browse all our campaigns — past, present and upcoming.</p>
    </div>
</section>

<main class="site-main">
    <section class="section">
        <div class="container">

            @forelse($campaigns as $c)
            @php
                $badgeMap = [
                    'active'    => ['label' => 'Live',        'color' => 'var(--color-success)',  'bg' => 'var(--color-success-bg)'],
                    'ended'     => ['label' => 'Ended',       'color' => '#6B625B',               'bg' => 'var(--color-surface-alt)'],
                    'draft'     => ['label' => 'Coming soon', 'color' => 'var(--color-warning)',   'bg' => 'var(--color-warning-bg)'],
                    'cancelled' => ['label' => 'Cancelled',   'color' => 'var(--color-danger)',    'bg' => 'var(--color-danger-bg)'],
                ];
                $badge = $badgeMap[$c->status] ?? $badgeMap['draft'];
            @endphp

            <div class="campaign-list-card">

                {{-- Image --}}
                <a href="{{ route('campaign.index', $c->slug) }}" class="campaign-list-img-wrap">
                    @if($c->banner_image)
                        <img src="{{ asset('storage/app/public/'.$c->banner_image) }}" alt="{{ $c->name }}">
                    @else
                        <div class="campaign-list-img-placeholder">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9l4-4 4 4 4-5 4 5"/><circle cx="8.5" cy="8.5" r="1.5"/>
                            </svg>
                        </div>
                    @endif
                </a>

                {{-- Content --}}
                <div class="campaign-list-body">

                    <div class="campaign-list-top">
                        <span class="campaign-list-badge" style="color:{{ $badge['color'] }}; background:{{ $badge['bg'] }};">
                            {{ $badge['label'] }}
                        </span>
                        @if($c->end_date && $c->status === 'active')
                        <span class="campaign-list-closes">Closes {{ $c->end_date->format('d M Y') }}</span>
                        @endif
                    </div>

                    <h2 class="campaign-list-title">
                        <a href="{{ route('campaign.index', $c->slug) }}">{{ $c->name }}</a>
                    </h2>

                    <div class="campaign-list-meta">
                        @if($c->start_date && $c->end_date)
                        <span>📅 {{ $c->start_date->format('d M Y') }} – {{ $c->end_date->format('d M Y') }}</span>
                        @endif
                        @if($c->prize_title)
                        <span>🏆 {{ $c->prize_title }}{{ $c->prize_value ? ' (₹'.number_format($c->prize_value).')' : '' }}</span>
                        @endif
                        @if($c->draw_date)
                        <span>🎯 Draw: {{ $c->draw_date->format('d M Y') }}</span>
                        @endif
                    </div>

                    @if($c->prize_description)
                    <p class="campaign-list-desc">
                        {{ Str::limit(strip_tags($c->prize_description), 160) }}
                    </p>
                    @endif

                    <div class="campaign-list-actions">
                        <a href="{{ route('campaign.index', $c->slug) }}" class="btn btn-primary">View campaign →</a>
                        @if($c->status === 'active')
                        <a href="{{ route('campaign.enter', $c->slug) }}" class="btn btn-secondary">Enter now</a>
                        @elseif($c->status === 'ended')
                        <a href="{{ route('campaign.results', $c->slug) }}" class="btn btn-secondary">See results</a>
                        @endif
                    </div>

                </div>
            </div>
            @empty
            <div style="text-align:center; padding:80px 0;">
                <div style="font-size:48px; margin-bottom:16px;">📭</div>
                <p style="font-family:var(--font-display); font-size:22px; font-weight:700; color:var(--color-accent);">No campaigns yet</p>
                <p style="color:var(--color-text-muted); margin-top:8px;">Check back soon for upcoming campaigns!</p>
            </div>
            @endforelse

        </div>
    </section>
</main>

@push('css_or_js')
<style>
.campaign-list-card {
    display: flex;
    gap: 0;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    margin-bottom: 20px;
    transition: box-shadow var(--transition), transform var(--transition);
}
.campaign-list-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}
.campaign-list-img-wrap {
    width: 220px;
    min-width: 220px;
    display: block;
    flex-shrink: 0;
    overflow: hidden;
    background: var(--color-surface-alt);
}
.campaign-list-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform .3s ease;
}
.campaign-list-card:hover .campaign-list-img-wrap img {
    transform: scale(1.04);
}
.campaign-list-img-placeholder {
    width: 100%;
    height: 100%;
    min-height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-border-strong);
    background: var(--color-surface-alt);
}
.campaign-list-body {
    flex: 1;
    padding: 24px 28px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.campaign-list-top {
    display: flex;
    align-items: center;
    gap: 12px;
}
.campaign-list-badge {
    font-size: 12px;
    font-weight: 600;
    padding: 3px 12px;
    border-radius: 999px;
}
.campaign-list-closes {
    font-size: 12px;
    color: var(--color-text-faint);
}
.campaign-list-title {
    font-family: var(--font-display);
    font-size: 22px;
    font-weight: 700;
    color: var(--color-accent);
    letter-spacing: -.02em;
    margin: 0;
}
.campaign-list-title a { color: inherit; }
.campaign-list-title a:hover { color: var(--color-primary); }
.campaign-list-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 13px;
    color: var(--color-text-muted);
}
.campaign-list-desc {
    font-size: 14px;
    color: var(--color-text-muted);
    line-height: 1.65;
    margin: 0;
}
.campaign-list-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 6px;
}
@media (max-width: 640px) {
    .campaign-list-card { flex-direction: column; }
    .campaign-list-img-wrap { width: 100%; min-width: unset; height: 180px; }
    .campaign-list-body { padding: 18px; }
}
</style>
@endpush

@endsection
