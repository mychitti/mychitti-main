@extends('front-views.campaign_layout')

@section('title', ($campaign?->name ?? 'Campaign') . ' – Our Team')

@section('content')

<section class="page-hero">
    <div class="container">
        <span class="hero-eyebrow">{{ $campaign?->name ?? 'Campaign' }}</span>
        <h1 class="hero-title">Meet the team</h1>
        <p class="hero-subtitle">The people behind this campaign.</p>
    </div>
</section>

<main class="site-main">
    <section class="section">
        <div class="container" style="max-width:900px;">

            @php $members = $campaign?->teamMembers ?? collect(); @endphp

            @if($members->isNotEmpty())
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:20px;">
                @foreach($members as $m)
                <div class="card">
                    <div style="width:48px; height:48px; border-radius:50%; background:var(--color-accent);
                        display:flex; align-items:center; justify-content:center;
                        font-family:var(--font-display); font-weight:700; font-size:18px; color:#fff; margin-bottom:12px;">
                        {{ strtoupper(substr($m->member_name, 0, 1)) }}
                    </div>
                    <div style="font-family:var(--font-display); font-weight:700; font-size:17px; color:var(--color-accent);">
                        {{ $m->member_name }}
                    </div>
                    <div style="font-size:13px; color:var(--color-primary); font-weight:600; margin-top:2px;">
                        {{ $m->role }}
                    </div>
                    @if($m->responsibilities)
                    <p style="font-size:13px; color:var(--color-text-muted); margin-top:10px; line-height:1.6;">
                        {{ $m->responsibilities }}
                    </p>
                    @endif
                    @if($m->email || $m->phone)
                    <div style="margin-top:12px; display:flex; flex-direction:column; gap:4px; font-size:13px; color:var(--color-text-muted);">
                        @if($m->email)
                        <a href="mailto:{{ $m->email }}" style="color:var(--color-primary);">{{ $m->email }}</a>
                        @endif
                        @if($m->phone)
                        <span>{{ $m->phone }}</span>
                        @endif
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @else
            <div style="text-align:center; padding:60px 0;">
                <p style="color:var(--color-text-muted);">Team details coming soon.</p>
            </div>
            @endif

        </div>
    </section>
</main>

@endsection
