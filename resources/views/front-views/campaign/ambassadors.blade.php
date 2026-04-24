@extends('front-views.campaign_layout')

@section('title', ($campaign?->name ?? 'Campaign') . ' – Ambassadors')

@section('content')

<section class="page-hero">
    <div class="container">
        <span class="hero-eyebrow">{{ $campaign?->name ?? 'Campaign' }}</span>
        <h1 class="hero-title">Ambassadors</h1>
        <p class="hero-subtitle">Meet the voices spreading the word about this campaign.</p>
    </div>
</section>

<main class="site-main">
    <section class="section">
        <div class="container" style="max-width:900px;">

            @php $influencers = $campaign?->influencers ?? collect(); @endphp

            @if($influencers->isNotEmpty())
            @php
                $platformIcons = ['instagram'=>'📸','youtube'=>'🎬','twitter'=>'🐦','tiktok'=>'🎵','facebook'=>'📘','other'=>'🌐'];
            @endphp
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:20px;">
                @foreach($influencers as $inf)
                <div class="card">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                        <div style="width:44px; height:44px; border-radius:50%; background:var(--color-primary-light);
                            display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0;">
                            {{ $platformIcons[$inf->platform] ?? '🌐' }}
                        </div>
                        <div>
                            <div style="font-family:var(--font-display); font-weight:700; color:var(--color-accent);">{{ $inf->name }}</div>
                            @if($inf->handle)
                            <div style="font-size:13px; color:var(--color-primary);">{{ $inf->handle }}</div>
                            @endif
                        </div>
                    </div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <span style="font-size:12px; padding:2px 10px; border-radius:999px; background:var(--color-surface-alt); color:var(--color-text-muted); text-transform:capitalize;">
                            {{ $inf->platform }}
                        </span>
                        @if($inf->followers)
                        <span style="font-size:12px; padding:2px 10px; border-radius:999px; background:var(--color-primary-light); color:var(--color-primary-dark);">
                            {{ $inf->followers }} followers
                        </span>
                        @endif
                    </div>
                    @if($inf->notes)
                    <p style="font-size:13px; color:var(--color-text-muted); margin-top:10px; line-height:1.6;">{{ $inf->notes }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @else
            <div style="text-align:center; padding:60px 0;">
                <p style="color:var(--color-text-muted);">Ambassador details coming soon.</p>
            </div>
            @endif

        </div>
    </section>
</main>

@endsection
