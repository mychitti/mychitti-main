@extends('front-views.campaign_layout')

@section('title', ($campaign?->name ?? 'Campaign') . ' – Results')

@section('content')

<section class="page-hero">
    <div class="container">
        <span class="hero-eyebrow">{{ $campaign?->name ?? 'Campaign' }}</span>
        <h1 class="hero-title">Results</h1>
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
                <div class="section-head">
                    <p class="section-label">Draw results</p>
                    <h2 class="section-title">Congratulations to our winners</h2>
                </div>

                <div style="display:flex; flex-direction:column; gap:16px; margin-bottom:48px;">
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
                            <div style="font-size:14px; color:var(--color-text-muted); margin-top:2px;">{{ $w->prize_detail }}</div>
                            @endif
                        </div>
                        <span class="badge badge-gold">🏆 Winner</span>
                    </div>
                    @endforeach
                </div>

                @if($campaign?->prize_title)
                <div class="card" style="background:var(--color-primary-light); border-color:var(--color-primary);">
                    <p class="section-label">Prize</p>
                    <h3 style="font-family:var(--font-display); font-size:22px; font-weight:700; color:var(--color-accent);">
                        {{ $campaign->prize_title }}
                    </h3>
                    @if($campaign->prize_value)
                    <p style="color:var(--color-primary-dark); font-weight:600; margin-top:4px;">
                        Value: ₹{{ number_format($campaign->prize_value) }}
                    </p>
                    @endif
                    @if($campaign->prize_description)
                    <p style="color:var(--color-text-muted); margin-top:8px;">{{ $campaign->prize_description }}</p>
                    @endif
                </div>
                @endif

            @elseif($campaign && in_array($campaign->status, ['active', 'draft']))
                <div style="text-align:center; padding:60px 0;">
                    <div style="font-size:48px; margin-bottom:16px;">⏳</div>
                    <p style="font-family:var(--font-display); font-size:22px; font-weight:700; color:var(--color-accent); margin-bottom:8px;">
                        Results not yet announced
                    </p>
                    @if($campaign->draw_date)
                    <p style="color:var(--color-text-muted);">
                        Check back on {{ $campaign->draw_date->format('d F Y') }}.
                    </p>
                    @endif
                    <a href="{{ route('campaign.enter', $campaign->slug) }}" class="btn btn-primary btn-lg" style="margin-top:24px;">Enter now →</a>
                </div>
            @else
                <p style="color:var(--color-text-muted); text-align:center; padding:60px 0;">No results published yet.</p>
            @endif

        </div>
    </section>
</main>

@endsection
