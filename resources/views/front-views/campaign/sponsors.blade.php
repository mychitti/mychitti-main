@extends('front-views.campaign_layout')

@section('title', ($campaign?->name ?? 'Campaign') . ' – Sponsors')

@section('content')

<section class="page-hero">
    <div class="container">
        <span class="hero-eyebrow">{{ $campaign?->name ?? 'Campaign' }}</span>
        <h1 class="hero-title">Our sponsors</h1>
        <p class="hero-subtitle">This campaign is made possible by the generous support of our sponsors.</p>
    </div>
</section>

<main class="site-main">
    <section class="section">
        <div class="container">

            @php
                $tierOrder = ['title','gold','silver','bronze','partner'];
                $tierLabels = ['title'=>'Title Sponsor','gold'=>'Gold','silver'=>'Silver','bronze'=>'Bronze','partner'=>'Partners'];
                $tierColors = ['title'=>'#FFD700','gold'=>'#FFA500','silver'=>'#A8A9AD','bronze'=>'#CD7F32','partner'=>'#6c757d'];
                $grouped = $campaign?->sponsors->groupBy('tier') ?? collect();
            @endphp

            @foreach($tierOrder as $tier)
                @if($grouped->has($tier))
                <div style="margin-bottom:56px;">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:24px;">
                        <span style="display:inline-block; width:14px; height:14px; border-radius:50%; background:{{ $tierColors[$tier] }};"></span>
                        <h2 style="font-family:var(--font-display); font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:var(--color-text-muted);">
                            {{ $tierLabels[$tier] }}
                        </h2>
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:20px;">
                        @foreach($grouped[$tier] as $s)
                        <div class="card" style="display:flex; align-items:center; gap:16px; min-width:220px; flex:1; max-width:340px;">
                            @if($s->logo)
                            <img src="{{ asset('storage/app/public/'.$s->logo) }}" alt="{{ $s->name }}"
                                style="width:60px; height:60px; object-fit:contain; border-radius:var(--radius-md); border:1px solid var(--color-border);">
                            @else
                            <div style="width:60px; height:60px; border-radius:var(--radius-md); background:var(--color-surface-alt);
                                display:flex; align-items:center; justify-content:center; font-size:24px; flex-shrink:0;">🏢</div>
                            @endif
                            <div>
                                <div style="font-family:var(--font-display); font-weight:700; font-size:16px; color:var(--color-accent);">
                                    {{ $s->name }}
                                </div>
                                @if($s->website)
                                <a href="{{ $s->website }}" target="_blank" rel="noopener"
                                    style="font-size:13px; color:var(--color-primary);">Visit website →</a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            @endforeach

            @if(!$campaign || $campaign->sponsors->isEmpty())
            <div style="text-align:center; padding:60px 0;">
                <p style="color:var(--color-text-muted);">Sponsor details coming soon.</p>
            </div>
            @endif

        </div>
    </section>
</main>

@endsection
