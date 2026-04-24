@extends('front-views.campaign_layout')

@section('title', ($campaign?->meta_title ?? $campaign?->name ?? 'Campaign'))

@section('meta_description', $campaign?->meta_description ?? '')

@section('content')

@if($campaign)
<section class="page-hero">
    <div class="container">
        @if($campaign->start_date && $campaign->end_date)
        <span class="hero-eyebrow">
            {{ $campaign->start_date->format('d M Y') }} – {{ $campaign->end_date->format('d M Y') }}
        </span>
        @endif
        <h1 class="hero-title">{{ ucwords($campaign->name) }}</h1>
        @if($campaign->prize_title)
        <p class="hero-subtitle">Win: {{ $campaign->prize_title }}{{ $campaign->prize_value ? ' — worth ₹' . number_format($campaign->prize_value) : '' }}</p>
        @endif
        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:32px;">
            <a href="{{ route('campaign.enter', $campaign->slug) }}" class="btn btn-primary btn-lg">Enter now →</a>
            <a href="{{ route('campaign.how-it-works', $campaign->slug) }}" class="btn btn-secondary btn-lg" style="color:#fff; border-color:rgba(255,255,255,.3);">How it works</a>
        </div>
    </div>
</section>

@if($campaign->page_content)
<main class="site-main">
    <section class="section">
        <div class="container">
            <div class="campaign-rich-content">
                {!! $campaign->page_content !!}
            </div>
        </div>
    </section>
</main>
@endif

@else
<main class="site-main">
    <section class="section">
        <div class="container" style="text-align:center; padding:80px 0;">
            <p style="color:var(--color-text-muted); font-size:18px;">No active campaign at the moment. Check back soon!</p>
        </div>
    </section>
</main>
@endif

@endsection
