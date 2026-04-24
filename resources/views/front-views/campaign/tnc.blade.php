@extends('front-views.campaign_layout')

@section('title', ($campaign?->name ?? 'Campaign') . ' – Terms & Conditions')

@section('content')

<section class="page-hero">
    <div class="container">
        <span class="hero-eyebrow">{{ $campaign?->name ?? 'Campaign' }}</span>
        <h1 class="hero-title">Terms &amp; conditions</h1>
        @if($campaign?->end_date)
        <p class="hero-subtitle">Campaign closes {{ $campaign->end_date->format('d F Y') }}.</p>
        @endif
    </div>
</section>

<main class="site-main">
    <section class="section">
        <div class="container" style="max-width:800px;">
            @if($campaign?->terms)
                <div class="campaign-rich-content" style="line-height:1.8; color:var(--color-text-muted);">
                    {!! $campaign->terms !!}
                </div>
            @else
                <p style="color:var(--color-text-muted);">Terms and conditions coming soon.</p>
            @endif
        </div>
    </section>
</main>

@endsection
