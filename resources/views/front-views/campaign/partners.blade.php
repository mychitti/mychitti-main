@extends('front-views.campaign_layout')

@section('title', ($campaign?->name ?? 'Campaign') . ' – Partners')

@section('content')

<section class="page-hero">
    <div class="container">
        <span class="hero-eyebrow">{{ $campaign?->name ?? 'Campaign' }}</span>
        <h1 class="hero-title">Campaign partners</h1>
        <p class="hero-subtitle">Organisations and brands collaborating to make this campaign a success.</p>
    </div>
</section>

<main class="site-main">
    <section class="section">
        <div class="container" style="max-width:860px;">

            @php $collabs = $campaign?->collaborations->where('status', 'active') ?? collect(); @endphp

            @if($collabs->isNotEmpty())
            <div style="display:flex; flex-direction:column; gap:16px;">
                @foreach($collabs as $col)
                <div class="card" style="display:flex; align-items:flex-start; gap:20px;">
                    <div style="width:48px; height:48px; border-radius:var(--radius-md); background:var(--color-primary-light);
                        display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">🤝</div>
                    <div style="flex:1;">
                        <div style="font-family:var(--font-display); font-weight:700; font-size:17px; color:var(--color-accent);">
                            {{ $col->partner_name }}
                        </div>
                        @if($col->type)
                        <div style="font-size:12px; color:var(--color-primary); font-weight:600; text-transform:uppercase; letter-spacing:.06em; margin-top:2px;">
                            {{ $col->type }}
                        </div>
                        @endif
                        @if($col->terms)
                        <p style="font-size:14px; color:var(--color-text-muted); margin-top:8px; line-height:1.65;">
                            {{ $col->terms }}
                        </p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div style="text-align:center; padding:60px 0;">
                <p style="color:var(--color-text-muted);">Partner details coming soon.</p>
            </div>
            @endif

        </div>
    </section>
</main>

@endsection
