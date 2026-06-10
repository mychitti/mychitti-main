@extends('front-views.layout')

@section('title', $ad->title)

@push('css_or_js')
<style>
    .ad-detail-page { max-width: 800px; margin: 0 auto; padding: 20px 16px 40px; }

    .ad-detail__back { font-size: 14px; color: #555; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 20px; }
    .ad-detail__back:hover { color: var(--primary); }

    .ad-detail__img-wrap {
        width: 100%;
        overflow: hidden;
        border-radius: 14px;
        background: #f0f0f0;
        margin-bottom: 24px;
    }
    .ad-detail__img-wrap img {
        width: 100%;
        object-fit: cover;
        display: block;
    }

    .ad-carousel-container {
        position: relative;
        width: 100%;
        height: 480px;
        overflow: hidden;
        border-radius: 14px;
        background: #f0f0f0;
        margin-bottom: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    .ad-carousel-slides {
        width: 100%;
        height: 100%;
        position: relative;
    }
    .ad-carousel-slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1;
    }
    .ad-carousel-slide.active {
        opacity: 1;
        z-index: 2;
    }
    .ad-carousel-slide img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .ad-carousel-prev, .ad-carousel-next {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.85);
        color: #333;
        border: none;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        opacity: 0;
        transition: all 0.3s ease;
    }
    .ad-carousel-container:hover .ad-carousel-prev,
    .ad-carousel-container:hover .ad-carousel-next {
        opacity: 1;
    }
    .ad-carousel-prev { left: 16px; }
    .ad-carousel-next { right: 16px; }
    .ad-carousel-prev:hover, .ad-carousel-next:hover {
        background: #fff;
        transform: translateY(-50%) scale(1.05);
    }

    .ad-carousel-dots {
        position: absolute;
        bottom: 16px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 8px;
        z-index: 10;
        background: rgba(0,0,0,0.3);
        padding: 6px 12px;
        border-radius: 20px;
        backdrop-filter: blur(4px);
    }
    .ad-carousel-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .ad-carousel-dot.active {
        background: #fff;
        transform: scale(1.2);
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

    @php
        $images = [];
        if ($ad->images) {
            $images = is_array($ad->images) ? $ad->images : json_decode($ad->images, true);
        } elseif ($ad->image) {
            $images = [$ad->image];
        }
    @endphp

    @if(count($images) > 1)
        <div class="ad-carousel-container">
            <div class="ad-carousel-slides">
                @foreach($images as $index => $img)
                    <div class="ad-carousel-slide {{ $index === 0 ? 'active' : '' }}">
                        <img src="{{ asset('storage/app/public/notification') . '/' . $img }}"
                             onerror="this.src='{{ asset('public/assets/admin/img/900x400/img1.jpg') }}'"
                             alt="{{ $ad->title }} - Image {{ $index + 1 }}">
                    </div>
                @endforeach
            </div>
            
            <button class="ad-carousel-prev" onclick="moveSlide(-1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="ad-carousel-next" onclick="moveSlide(1)">
                <i class="fas fa-chevron-right"></i>
            </button>

            <div class="ad-carousel-dots">
                @foreach($images as $index => $img)
                    <span class="ad-carousel-dot {{ $index === 0 ? 'active' : '' }}" onclick="setSlide({{ $index }})"></span>
                @endforeach
            </div>
        </div>
    @else
        <div class="ad-detail__img-wrap">
            <img src="{{ asset('storage/app/public/notification') . '/' . ($ad->image ?? 'def.png') }}"
                 onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                 alt="{{ $ad->title }}">
        </div>
    @endif

    <h1 class="ad-detail__title">{{ $ad->title }}</h1>

    @if($ad->description)
    <p class="ad-detail__desc">{{ $ad->description }}</p>
    @endif

    @if($store)
    <a href="{{ route('store.details', [_storeCity($store), $store->slug]) }}" class="ad-detail__store">
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

@push('script_2')
<script>
    let currentSlide = 0;
    const slides = document.querySelectorAll('.ad-carousel-slide');
    const dots = document.querySelectorAll('.ad-carousel-dot');

    function showSlide(index) {
        if (slides.length === 0) return;
        
        slides[currentSlide].classList.remove('active');
        dots[currentSlide].classList.remove('active');

        currentSlide = (index + slides.length) % slides.length;

        slides[currentSlide].classList.add('active');
        dots[currentSlide].classList.add('active');
    }

    function moveSlide(direction) {
        showSlide(currentSlide + direction);
    }

    function setSlide(index) {
        showSlide(index);
    }

    // Auto rotate every 5 seconds
    if (slides.length > 1) {
        setInterval(() => {
            moveSlide(1);
        }, 5000);
    }
</script>
@endpush
