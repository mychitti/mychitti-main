@extends('front-views.campaign_layout')

@section('title', ($campaign?->name ?? 'Campaign') . ' – How it works')

@section('content')

<section class="page-hero">
    <div class="container">
        <span class="hero-eyebrow">{{ $campaign?->name ?? 'Campaign' }}</span>
        <h1 class="hero-title">How it works</h1>
        <p class="hero-subtitle">Everything you need to know about entering and winning.</p>
    </div>
</section>

<main class="site-main">
    <section class="section">
        <div class="container">
            @if($campaign?->how_it_works)
                <div class="campaign-rich-content">
                    {!! $campaign->how_it_works !!}
                </div>
            @else
                <p style="color:var(--color-text-muted);">Details coming soon.</p>
            @endif

            <div style="margin-top:48px; display:flex; gap:16px; flex-wrap:wrap;">
                <a href="{{ route('campaign.enter', $campaign->slug) }}" class="btn btn-primary btn-lg">Enter now →</a>
                <a href="{{ route('campaign.faq', $campaign->slug) }}" class="btn btn-secondary btn-lg">View FAQs</a>
            </div>
        </div>
    </section>
</main>

@endsection
