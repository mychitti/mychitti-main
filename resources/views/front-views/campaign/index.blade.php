@extends('front-views.campaign_layout')

@section('title','Campaign')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
 

    <!-- ══════════════════════════════════════
       PAGE HERO  (optional — use per page)
       ══════════════════════════════════════ -->
    <section class="page-hero">
        <div class="container">
            <span class="hero-eyebrow">2026 Campaign</span>
            <h1 class="hero-title">Win Something<br />Extraordinary</h1>
            <p class="hero-subtitle">Enter for your chance to be part of something remarkable. Simple, fair,
                unforgettable.</p>
            <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:32px;">
                <a href="/enter" class="btn btn-primary btn-lg">Enter now →</a>
                <a href="/how-it-works" class="btn btn-secondary btn-lg"
                    style="color:#fff; border-color:rgba(255,255,255,.3);">How it works</a>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════
       MAIN CONTENT  ← replace with page content
       ══════════════════════════════════════ -->
    <main class="site-main">

        <!-- ─── DELETE THIS BLOCK — it's just a placeholder ─── -->
        <div class="content-placeholder">
            content
        </div>
        <!-- ─────────────────────────────────────────────────── -->

    </main>

@endsection

@push('script_2')

@endpush