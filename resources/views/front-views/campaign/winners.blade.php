@extends('front-views.campaign_layout')

@section('title', ($campaign?->name ?? 'Campaign') . ' – Winners')

@section('content')

<section class="page-hero">
    <div class="container">
        <span class="hero-eyebrow">{{ $campaign?->name ?? 'Campaign' }}</span>
        <h1 class="hero-title">🏆 Winners</h1>
        @if($campaign?->draw_date)
        <p class="hero-subtitle">Draw date: {{ $campaign->draw_date->format('d F Y') }}</p>
        @endif
    </div>
</section>

<main class="site-main">
    <section class="section">
        <div class="container" style="max-width:700px;">

            @php $winners = $campaign?->winners ?? collect(); @endphp

            @if($winners->isNotEmpty())
                <div style="display:flex; flex-direction:column; gap:16px;">
                    @foreach($winners->sortBy('position') as $w)
                    <div class="card" style="display:flex; align-items:center; gap:20px;">
                        <div style="width:52px; height:52px; border-radius:50%; background:var(--color-gold-light);
                            display:flex; align-items:center; justify-content:center;
                            font-family:var(--font-display); font-weight:800; font-size:20px; color:var(--color-gold); flex-shrink:0;">
                            {{ $w->position }}
                        </div>
                        <div style="flex:1;">
                            <div style="font-family:var(--font-display); font-size:18px; font-weight:700; color:var(--color-accent);">
                                {{ $w->winner_name }}
                            </div>
                            @if($w->prize_detail)
                            <div style="font-size:14px; color:var(--color-text-muted); margin-top:2px;">
                                Prize: {{ $w->prize_detail }}
                            </div>
                            @endif
                            @if($w->drawn_at)
                            <div style="font-size:13px; color:var(--color-text-faint); margin-top:2px;">
                                Drawn: {{ \Carbon\Carbon::parse($w->drawn_at)->format('d M Y') }}
                            </div>
                            @endif
                        </div>
                        <span class="badge badge-gold">Winner</span>
                    </div>
                    @endforeach
                </div>
            @elseif($campaign && in_array($campaign->status, ['active', 'draft']))
                <div style="text-align:center; padding:60px 0;">
                    <div style="font-size:48px; margin-bottom:16px;">🎯</div>
                    <p style="font-family:var(--font-display); font-size:22px; font-weight:700; color:var(--color-accent); margin-bottom:8px;">
                        The draw hasn't happened yet!
                    </p>
                    @if($campaign->draw_date)
                    <p style="color:var(--color-text-muted);">
                        Winners will be announced on {{ $campaign->draw_date->format('d F Y') }}.
                    </p>
                    @endif
                    <a href="{{ route('campaign.enter', $campaign->slug) }}" class="btn btn-primary btn-lg" style="margin-top:24px;">Enter now →</a>
                </div>
            @else
                <p style="color:var(--color-text-muted); text-align:center; padding:60px 0;">No winners announced yet.</p>
            @endif

        </div>
    </section>
</main>

@endsection
