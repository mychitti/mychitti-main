@extends('front-views.layout')

@section('title', $ad->title)

@push('css_or_js')
<style>
    .ad-detail-page { max-width: 800px; margin: 0 auto; padding: 20px 16px 40px; }

    .ad-detail__back { font-size: 14px; color: #555; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 20px; }
    .ad-detail__back:hover { color: var(--primary); }

    .ad-detail__img-wrap {
        width: 100%;
        {{-- max-height: 480px; --}}
        overflow: hidden;
        border-radius: 14px;
        background: #f0f0f0;
        margin-bottom: 24px;
    }
    .ad-detail__img-wrap img {
        width: 100%;
        {{-- height: 100%; --}}
        object-fit: cover;
        display: block;
        {{-- max-height: 480px; --}}
    }

    .ad-detail__title { font-size: 22px; font-weight: 700; color: #2c3e50; margin-bottom: 8px; }
    .ad-detail__desc  { font-size: 15px; color: #555; line-height: 1.7; margin-bottom: 24px; }

    .ad-detail__store {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 16px;
        background: #f9f9f9;
        border-radius: 10px;
        border: 1px solid #eee;
        text-decoration: none;
    }
    .ad-detail__store:hover { background: #f0f4ff; }
    .ad-detail__store-logo {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e0e0e0;
        flex-shrink: 0;
    }
    .ad-detail__store-name  { font-size: 15px; font-weight: 600; color: #2c3e50; margin: 0 0 2px; }
    .ad-detail__store-label { font-size: 12px; color: #888; margin: 0; }
</style>
@endpush

@section('content')
<div style="height:80px;"></div>
<div class="ad-detail-page">

    <a href="{{ route('front.ads.index') }}" class="ad-detail__back">
        <i class="fas fa-arrow-left"></i> Back to Spotlights
    </a>

    <div class="ad-detail__img-wrap">
        <img src="{{ asset('storage/app/public/notification') . '/' . $ad->image }}"
             onerror="this.src='{{ asset('assets/admin/img/160x160/img1.jpg') }}'"
             alt="{{ $ad->title }}">
    </div>

    <h1 class="ad-detail__title">{{ $ad->title }}</h1>

    @if($ad->description)
    <p class="ad-detail__desc">{{ $ad->description }}</p>
    @endif

    @if($store)
    <a href="{{ route('store.details', [_selectedCity(), $store->slug]) }}" class="ad-detail__store">
        <img src="{{ asset('storage/app/public/store') . '/' . $store->logo }}"
             onerror="this.src='{{ asset('assets/admin/img/160x160/img1.jpg') }}'"
             alt="{{ $store->name }}"
             class="ad-detail__store-logo">
        <div>
            <p class="ad-detail__store-name">{{ ucfirst($store->name) }}</p>
            <p class="ad-detail__store-label"><i class="fas fa-store me-1"></i>View Store</p>
        </div>
        <i class="fas fa-chevron-right ms-auto text-muted"></i>
    </a>
    @endif

</div>
@endsection
