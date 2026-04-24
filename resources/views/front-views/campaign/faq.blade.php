@extends('front-views.campaign_layout')

@section('title', ($campaign?->name ?? 'Campaign') . ' – FAQs')

@section('content')

<section class="page-hero">
    <div class="container">
        <span class="hero-eyebrow">{{ $campaign?->name ?? 'Campaign' }}</span>
        <h1 class="hero-title">Frequently asked questions</h1>
        <p class="hero-subtitle">Can't find your answer? <a href="mailto:{{ config('mail.from.address') }}" style="color:var(--color-primary);">Contact us</a>.</p>
    </div>
</section>

<main class="site-main">
    <section class="section">
        <div class="container" style="max-width:760px;">
            @forelse($campaign?->faqs ?? [] as $faq)
            <div class="faq-item" style="border-bottom:1px solid var(--color-border); padding:24px 0;">
                <button class="faq-toggle" onclick="this.nextElementSibling.classList.toggle('open'); this.querySelector('.faq-icon').textContent = this.nextElementSibling.classList.contains('open') ? '−' : '+';"
                    style="width:100%; display:flex; justify-content:space-between; align-items:center; background:none; border:none; cursor:pointer; text-align:left; gap:16px;">
                    <span style="font-family:var(--font-display); font-size:17px; font-weight:600; color:var(--color-accent);">{{ $faq->question }}</span>
                    <span class="faq-icon" style="font-size:22px; color:var(--color-primary); flex-shrink:0; line-height:1;">+</span>
                </button>
                <div class="faq-answer" style="display:none; padding-top:14px; color:var(--color-text-muted); line-height:1.7;">
                    {!! nl2br(e($faq->answer)) !!}
                </div>
            </div>
            @empty
            <p style="color:var(--color-text-muted); text-align:center; padding:60px 0;">No FAQs published yet.</p>
            @endforelse
        </div>
    </section>
</main>

@push('script')
<style>
.faq-answer.open { display:block !important; }
</style>
@endpush

@endsection
