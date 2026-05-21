@extends('front-views.layout')

@section('title', $store['meta_title'] ?? ($data['store_config']?->webpage_name ?? $store['name']))
@section('meta_keywords', $keywords)
@section('meta_description', $store['meta_description'])

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lightgallery.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lg-thumbnail.min.css">
<style>
    /* ── Reset / Base ── */
    :root {
        --ec-primary:   #81c408;
        --ec-primary-d: #6aaa04;
        --ec-accent:    #f59e0b;
        --ec-dark:      #1a1a1a;
        --ec-muted:     #6b7280;
        --ec-border:    #e5e7eb;
        --ec-bg:        #f9fafb;
    }

    /* ── Top Sticky Nav ── */
    .ec-store-nav {
        position: fixed; top: 0; left: 0; right: 0; z-index: 999;
        background: #fff; box-shadow: 0 1px 8px rgba(0,0,0,.1);
        height: 64px; display: flex; align-items: center; padding: 0 24px;
        gap: 20px;
    }
    .ec-store-nav .store-logo { height: 44px; width: 44px; object-fit: cover; border-radius: 8px; }
    .ec-store-nav .store-name { font-weight: 700; font-size: 16px; color: var(--ec-dark); }
    .ec-nav-links { display: flex; gap: 24px; list-style: none; margin: 0; padding: 0; }
    .ec-nav-links a { font-size: 14px; color: var(--ec-muted); text-decoration: none; transition: color .2s; }
    .ec-nav-links a:hover, .ec-nav-links a.active { color: var(--ec-primary); font-weight: 600; }
    .ec-spacer { height: 64px; }

    /* ── Hero Banner ── */
    .ec-hero { position: relative; overflow: hidden; max-height: 320px; }
    .ec-hero-cover { width: 100%; height: 320px; object-fit: cover; display: block; }
    .ec-hero-overlay {
        position: absolute; inset: 0;
        background: linear-gradient(to right, rgba(0,0,0,.55) 0%, rgba(0,0,0,.1) 60%, transparent 100%);
        display: flex; align-items: flex-end; padding: 28px;
    }
    .ec-hero-info { color: #fff; }
    .ec-hero-info .store-avatar {
        width: 70px; height: 70px; border-radius: 12px; object-fit: cover;
        border: 3px solid #fff; margin-bottom: 10px;
    }
    .ec-hero-info h1 { font-size: 24px; font-weight: 700; margin: 0 0 4px; }
    .ec-hero-info .hero-meta { font-size: 13px; opacity: .85; display: flex; gap: 14px; flex-wrap: wrap; margin-top: 6px; }
    .ec-hero-info .hero-meta span { display: flex; align-items: center; gap: 4px; }
    .ec-rating-pill {
        display: inline-flex; align-items: center; gap: 4px;
        background: var(--ec-accent); color: #fff; border-radius: 20px;
        padding: 3px 10px; font-weight: 700; font-size: 13px;
    }

    /* ── Layout: sidebar + main ── */
    .ec-shop-wrap { display: flex; gap: 0; min-height: 60vh; }

    /* ── Category Sidebar ── */
    .ec-cat-sidebar {
        width: 200px; flex-shrink: 0;
        position: sticky; top: 64px; align-self: flex-start;
        max-height: calc(100vh - 64px); overflow-y: auto;
        background: #fff; border-right: 1px solid var(--ec-border);
        padding: 16px 0;
    }
    .ec-cat-sidebar::-webkit-scrollbar { width: 4px; }
    .ec-cat-sidebar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
    .ec-cat-link {
        display: block; padding: 9px 18px;
        font-size: 14px; color: var(--ec-muted); text-decoration: none;
        border-left: 3px solid transparent; transition: all .18s;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .ec-cat-link:hover { color: var(--ec-dark); background: var(--ec-bg); }
    .ec-cat-link.active { color: var(--ec-primary); border-left-color: var(--ec-primary); background: #f0fbe6; font-weight: 600; }

    /* ── Product Area ── */
    .ec-product-area { flex: 1; min-width: 0; padding: 24px; background: var(--ec-bg); }

    /* ── Section Heading ── */
    .ec-section-heading {
        font-size: 16px; font-weight: 700; color: var(--ec-dark);
        padding: 6px 0 10px; margin-bottom: 14px;
        border-bottom: 2px solid var(--ec-primary); display: inline-block;
    }

    /* ── Product Card ── */
    .ec-card {
        background: #fff; border-radius: 12px; overflow: hidden;
        border: 1px solid var(--ec-border); position: relative;
        transition: box-shadow .2s, transform .2s;
        display: flex; flex-direction: column;
    }
    .ec-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.1); transform: translateY(-2px); }
    .ec-card-img-wrap { position: relative; height: 190px; overflow: hidden; }
    .ec-card-img { width: 100%; height: 190px; object-fit: cover; transition: transform .3s; }
    .ec-card:hover .ec-card-img { transform: scale(1.04); }

    .ec-card-body { padding: 10px 12px 12px; flex: 1; display: flex; flex-direction: column; }
    .ec-card-name {
        font-size: 14px; font-weight: 600; color: var(--ec-dark);
        display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2;
        overflow: hidden; margin-bottom: 4px; min-height: 40px;
    }
    .ec-card-var-hint { font-size: 11px; color: var(--ec-muted); margin-bottom: 6px; }
    .ec-card-price { font-size: 16px; font-weight: 700; color: var(--ec-dark); }
    .ec-card-mrp   { font-size: 12px; text-decoration: line-through; color: #9ca3af; margin-left: 5px; }

    /* Badges */
    .ec-badge-discount {
        position: absolute; top: 8px; left: 8px;
        background: #e53e3e; color: #fff; font-size: 10px; font-weight: 700;
        padding: 2px 7px; border-radius: 4px; z-index: 1;
    }
    .ec-badge-delivery {
        position: absolute; bottom: 8px; left: 8px;
        background: rgba(255,255,255,.9); color: var(--ec-dark);
        font-size: 10px; padding: 2px 7px; border-radius: 4px;
    }
    .ec-wishlist-btn {
        position: absolute; top: 8px; right: 8px; z-index: 2;
        background: rgba(255,255,255,.9); border: none; border-radius: 50%;
        width: 30px; height: 30px; cursor: pointer; display: flex;
        align-items: center; justify-content: center; font-size: 14px;
    }

    /* Add to Cart */
    .ec-cart-row { display: flex; align-items: center; justify-content: space-between; margin-top: auto; padding-top: 8px; }
    .ec-btn-add {
        font-size: 12px; padding: 5px 12px; border-radius: 6px;
        background: var(--ec-primary); color: #fff; border: none; cursor: pointer;
        transition: background .2s; white-space: nowrap;
    }
    .ec-btn-add:hover { background: var(--ec-primary-d); }
    .ec-btn-remove {
        font-size: 12px; padding: 5px 12px; border-radius: 6px;
        background: #fff; color: #e53e3e; border: 1px solid #e53e3e; cursor: pointer;
        transition: all .2s;
    }
    .ec-btn-remove:hover { background: #fef2f2; }

    /* ── Banners carousel ── */
    .ec-banners { margin: 16px 0; }
    .ec-banners img { border-radius: 10px; width: 100%; object-fit: cover; max-height: 180px; }

    /* ── Gallery ── */
    .ec-gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 8px; }
    .ec-gallery-item img { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 8px; cursor: pointer; transition: opacity .2s; }
    .ec-gallery-item img:hover { opacity: .85; }

    /* ── Reviews ── */
    .ec-review-card { background: #fff; border: 1px solid var(--ec-border); border-radius: 10px; padding: 14px; margin-bottom: 12px; }
    .ec-reviewer-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
    .ec-stars { color: var(--ec-accent); font-size: 13px; }

    /* ── Contact boxes ── */
    .ec-contact-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; margin-top: 14px; }
    .ec-contact-box { background: #fff; border: 1px solid var(--ec-border); border-radius: 10px; padding: 16px; text-align: center; }
    .ec-contact-box .icon { font-size: 22px; color: var(--ec-primary); margin-bottom: 8px; }
    .ec-contact-box .label { font-size: 11px; color: var(--ec-muted); margin-bottom: 4px; }
    .ec-contact-box .value { font-size: 13px; font-weight: 600; color: var(--ec-dark); word-break: break-word; }

    /* ── Announcement ── */
    .ec-announce { background: #ecfdf5; border-left: 4px solid #10b981; padding: 10px 16px; border-radius: 6px; font-size: 14px; }

    /* ── Info sections (reviews / contact / about) ── */
    .ec-section { padding: 32px 24px; background: #fff; border-top: 1px solid var(--ec-border); }
    .ec-section-title { font-size: 20px; font-weight: 700; margin-bottom: 20px; }

    /* ── Map modal ── */
    #ec-map { height: 300px; width: 100%; }

    /* ── Mobile ── */
    @media (max-width: 768px) {
        .ec-cat-sidebar  { display: none; }
        .ec-hero-cover   { height: 220px; }
        .ec-store-nav .ec-nav-links { display: none; }
        .ec-store-nav .store-name { font-size: 14px; }
        .ec-product-area { padding: 14px; }
    }
</style>

<script>
    function loadScript(src, cb) {
        if (document.querySelector('script[src="' + src + '"]')) return;
        var s = document.createElement('script');
        s.async = true; s.defer = true; s.src = src; s.onload = cb;
        document.head.appendChild(s);
    }
    function initMap() {
        var pos = { lat: {{ $store['latitude'] }}, lng: {{ $store['longitude'] }} };
        var map = new google.maps.Map(document.getElementById('ec-map'), { zoom: 14, center: pos, mapId: 'b2c6179556df0b45' });
        var marker = new google.maps.marker.AdvancedMarkerElement({ position: pos, map: map });
        var img = document.createElement('img');
        img.src = "{{ asset('storage/app/public/store/' . $store['logo']) }}";
        img.style.cssText = 'width:45px;height:45px;border-radius:50%;border:3px solid white;';
        marker.content.appendChild(img);
    }
    window.addEventListener('load', function () {
        loadScript("https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key','map_api_key')->first()->value }}&libraries=places,marker&callback=initMap&loading=async");
    });
</script>
@endpush

@section('content')

@php
    $storeName   = $data['store_config']?->webpage_name ?? $store['name'];
    $storeRating = number_format($store->average_rating, 1);
    $currSymbol  = \App\CentralLogics\Helpers::currency_symbol();
    $phones      = $data['store_config']?->webpage_phones ? json_decode($data['store_config']->webpage_phones, true) : [];
    $phone       = !empty($phones) ? implode(', ', $phones) : $store['phone'];
@endphp

<div class="ec-spacer"></div>

{{-- ── TOP NAV ── --}}
<nav class="ec-store-nav">
    <img src="{{ asset('storage/app/public/store/' . $store['logo']) }}" class="store-logo" alt="{{ $storeName }}">
    <span class="store-name d-none d-md-inline">{{ $storeName }}</span>
    <ul class="ec-nav-links ms-auto">
        <li><a href="#ec-products" class="active">Products</a></li>
        @if (count($store->galleries)) <li><a href="#ec-gallery">Gallery</a></li> @endif
        @if (count($data['reviews'])) <li><a href="#ec-reviews">Reviews</a></li> @endif
        <li><a href="#ec-contact">Contact</a></li>
        @if ($data['store_config']?->about_us) <li><a href="#ec-about">About</a></li> @endif
    </ul>
    <a href="{{ route('cart') }}" class="btn btn-outline-primary btn-sm ms-3 d-none d-md-inline-flex align-items-center gap-1">
        <i class="fa fa-shopping-cart"></i>
        <span class="cart-count-outer"><span class="cart-count-inner">0</span></span>
    </a>
</nav>

{{-- ── HERO ── --}}
<div class="ec-hero">
    @if ($store['cover_photo'])
        <img src="{{ asset('storage/app/public/store/cover/' . $store['cover_photo']) }}"
             class="ec-hero-cover" alt="{{ $storeName }}">
    @else
        <div class="ec-hero-cover" style="background: linear-gradient(135deg, #81c408, #6aaa04);"></div>
    @endif
    <div class="ec-hero-overlay">
        <div class="ec-hero-info">
            <img src="{{ asset('storage/app/public/store/' . $store['logo']) }}" class="store-avatar" alt="{{ $storeName }}">
            <h1>{{ $storeName }}</h1>
            <div class="hero-meta">
                @if ($store->rating_count)
                    <span><span class="ec-rating-pill"><i class="fa fa-star"></i> {{ $storeRating }}</span> ({{ $store->rating_count }} reviews)</span>
                @endif
                @if ($store->delivery_time)
                    <span><i class="fa fa-clock-o"></i> {{ $store->delivery_time }}</span>
                @endif
                @if ($store['address'])
                    <span><i class="fa fa-map-marker"></i> {{ Str::limit($store['address'], 60) }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── ANNOUNCEMENT ── --}}
@if ($store->announcement)
<div class="container py-2">
    <div class="ec-announce"><i class="fa fa-bullhorn me-2"></i>{{ $store->announcement_message }}</div>
</div>
@endif

{{-- ── STORE BANNERS ── --}}
@if (count($data['banners']))
<div class="container ec-banners">
    <div class="owl-carousel ec-store-banner-carousel">
        @foreach ($data['banners'] as $banner)
            <a href="{{ $banner->default_link ?? '#' }}" onclick="trackBannerClick({{ $banner->id }})">
                <img src="{{ asset('storage/app/public/banner/' . $banner->image) }}" alt="banner">
            </a>
        @endforeach
    </div>
</div>
@endif

{{-- ── MAIN SHOP: SIDEBAR + PRODUCTS ── --}}
<div id="ec-products">
<div class="ec-shop-wrap">

    {{-- Category Sidebar --}}
    <div class="ec-cat-sidebar">
        @foreach ($productdata as $key => $cat)
            <a href="#cat-{{ $key }}" class="ec-cat-link {{ $key === 0 ? 'active' : '' }}" data-cat="{{ $key }}">
                {{ $cat->name }} <span class="badge badge-pill badge-light text-muted" style="font-size:10px;">{{ count($cat->items) }}</span>
            </a>
        @endforeach
    </div>

    {{-- Product Grid --}}
    <div class="ec-product-area">
        @forelse ($productdata as $key => $cat)
            <div id="cat-{{ $key }}" class="ec-cat-section mb-5">
                <span class="ec-section-heading">{{ $cat->name }}</span>
                <div class="row g-3">
                    @foreach ($cat->items as $pro)
                        @php
                            $variations  = json_decode($pro->variations) ?? [];
                            $firstVr     = !empty($variations) ? $variations[0] : null;
                            $sellPrice   = $firstVr ? $firstVr->price    : $pro->price;
                            $mrpPrice    = $firstVr ? ($firstVr->mrpprice ?? $firstVr->price) : ($pro->mrp_price ?? $pro->price);
                            $varIdx0     = !empty($variations) ? 0 : '';
                            $inCart      = _itemExistInCart($pro->id, json_encode('[' . (!empty($variations) ? json_encode($variations[0]) : '') . ']'));
                        @endphp
                        <div class="col-6 col-sm-4 col-md-3 col-xl-2 pr_{{ $pro->id }}">
                            <div class="ec-card">
                                {{-- Image --}}
                                <div class="ec-card-img-wrap">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <img class="ec-card-img"
                                             src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/' . $pro->image), asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                             alt="{{ $pro->name }}"
                                             loading="lazy">
                                    </a>
                                    {{-- Discount badge --}}
                                    @if ($pro->discount > 0)
                                        <span class="ec-badge-discount">
                                            {{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : $currSymbol }} OFF
                                        </span>
                                    @endif
                                    {{-- Delivery time --}}
                                    @if ($store->delivery_time)
                                        <span class="ec-badge-delivery">
                                            <i class="fa fa-clock-o"></i> {{ strtoupper($store->delivery_time) }}
                                        </span>
                                    @endif
                                    {{-- Wishlist --}}
                                    <button class="ec-wishlist-btn prHeart_{{ $pro->id }}"
                                            onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                            title="Wishlist">
                                        <i class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                    </button>
                                </div>

                                {{-- Body --}}
                                <div class="ec-card-body">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}" class="text-decoration-none">
                                        <p class="ec-card-name" title="{{ ucfirst($pro->name) }}">{{ ucfirst($pro->name) }}</p>
                                    </a>

                                    {{-- Variation hint --}}
                                    @if (!empty($variations))
                                        <p class="ec-card-var-hint">
                                            {{ $variations[0]->type }}
                                            @if (count($variations) > 1)
                                                &nbsp;<span class="badge badge-pill badge-light text-muted border" style="font-size:9px;">+{{ count($variations)-1 }} more</span>
                                            @endif
                                        </p>
                                    @endif

                                    {{-- Price row --}}
                                    <div class="ec-cart-row">
                                        <div>
                                            <span class="ec-card-price">{{ _price($sellPrice) }}</span>
                                            @if ($pro->discount > 0)
                                                <span class="ec-card-mrp">{{ _price($mrpPrice) }}</span>
                                            @endif
                                        </div>
                                        {{-- Add / Remove cart --}}
                                        <div class="cart-section cartSec_{{ $pro->id }}">
                                            @if ($inCart)
                                                <button onclick="updateCart({{ $pro->id }}, 'remove', '{{ $varIdx0 }}', {{ $inCart }})"
                                                        class="ec-btn-remove">
                                                    <i class="fa fa-times"></i> Remove
                                                </button>
                                            @else
                                                <button onclick="updateCart({{ $pro->id }}, 'add', '{{ $varIdx0 }}', '')"
                                                        class="ec-btn-add">
                                                    <i class="fa fa-plus"></i> Add
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <i class="fa fa-box-open fa-3x text-muted mb-3"></i>
                <p class="text-muted">No products found.</p>
            </div>
        @endforelse
    </div>
</div>
</div>

{{-- ── GALLERY ── --}}
@if (count($store->galleries))
<div class="ec-section" id="ec-gallery">
    <div class="container">
        <p class="ec-section-title">Gallery</p>
        <div class="ec-gallery-grid" id="ec-gallery-grid">
            @foreach ($data['galleries'] as $img)
                <a class="ec-gallery-item ec-lg-item"
                   href="{{ asset('storage/app/public/store/gallery/' . $img->image) }}">
                    <img src="{{ asset('storage/app/public/store/gallery/' . $img->image) }}"
                         alt="Gallery" loading="lazy">
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── REVIEWS ── --}}
@if (count($data['reviews']))
<div class="ec-section" id="ec-reviews">
    <div class="container">
        <p class="ec-section-title">Customer Reviews</p>
        @foreach ($data['reviews'] as $rev)
            <div class="ec-review-card">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/' . $rev->profile_image), asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                         class="ec-reviewer-avatar" alt="{{ $rev->f_name }}">
                    <div>
                        <p class="fw-600 mb-0">{{ $rev->f_name . ' ' . $rev->l_name }}</p>
                        <div class="ec-stars">
                            @for ($s = 1; $s <= 5; $s++)
                                <i class="fa fa-star{{ $rev->rating >= $s ? '' : '-o' }}"></i>
                            @endfor
                        </div>
                    </div>
                    <span class="ms-auto" style="font-size:12px; color:#9ca3af;">{{ _formatted_datetime($rev->created_at) }}</span>
                </div>
                <p class="mb-1 text-muted" style="font-size:14px;">{{ $rev->comment }}</p>
                @if ($rev->reply)
                    <div class="d-flex gap-2 mt-2 p-2 rounded" style="background:#f0fbe6;">
                        <img src="{{ asset('storage/app/public/store/' . $store['logo']) }}"
                             style="width:30px;height:30px;border-radius:50%;object-fit:cover;" alt="store">
                        <p class="mb-0 text-dark" style="font-size:13px;">{{ $rev->reply }}</p>
                    </div>
                @endif
            </div>
        @endforeach
        @if ($data['review_count'] > 3)
            <a href="{{ route('store.reviews', [$store->slug]) }}" class="btn btn-outline-primary btn-sm">
                View all {{ $data['review_count'] }} reviews <i class="fa fa-arrow-right ms-1"></i>
            </a>
        @endif
    </div>
</div>
@endif

{{-- ── CONTACT ── --}}
<div class="ec-section" id="ec-contact">
    <div class="container">
        <p class="ec-section-title">Contact Us</p>
        <div class="ec-contact-grid">
            <div class="ec-contact-box">
                <div class="icon"><i class="fa fa-map-marker"></i></div>
                <div class="label">Address</div>
                <div class="value">{{ $store['address'] }}</div>
            </div>
            <div class="ec-contact-box">
                <div class="icon"><i class="fa fa-phone"></i></div>
                <div class="label">Phone</div>
                <div class="value"><a href="tel:{{ $phone }}" class="text-dark text-decoration-none">{{ $phone }}</a></div>
            </div>
            @if ($store['email'])
            <div class="ec-contact-box">
                <div class="icon"><i class="fa fa-envelope"></i></div>
                <div class="label">Email</div>
                <div class="value"><a href="mailto:{{ $store['email'] }}" class="text-dark text-decoration-none">{{ $store['email'] }}</a></div>
            </div>
            @endif
            <div class="ec-contact-box" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#ec-map-modal">
                <div class="icon"><i class="fa fa-location-arrow"></i></div>
                <div class="label">Location</div>
                <div class="value" style="color: var(--ec-primary);">View on Map</div>
            </div>
        </div>
    </div>
</div>

{{-- ── ABOUT ── --}}
@if ($data['store_config']?->about_us)
<div class="ec-section" id="ec-about">
    <div class="container">
        <p class="ec-section-title">About {{ $storeName }}</p>
        <div class="text-muted lh-lg">{!! $data['store_config']->about_us !!}</div>
    </div>
</div>
@endif

{{-- ── Map Modal ── --}}
<div class="modal fade" id="ec-map-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $storeName }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0"><div id="ec-map"></div></div>
        </div>
    </div>
</div>

@endsection

@push('script_2')
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/lightgallery.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/plugins/thumbnail/lg-thumbnail.umd.min.js"></script>
<script>
$(function () {

    // ── Banner carousel ──────────────────────────────────────────
    $('.ec-store-banner-carousel').owlCarousel({
        loop: true, margin: 10, nav: false, dots: true, autoplay: true, autoplayTimeout: 4000,
        responsive: { 0: { items: 1 }, 768: { items: 2 }, 1200: { items: 3 } }
    });

    // ── Sidebar active highlight on scroll ───────────────────────
    var sections = document.querySelectorAll('.ec-cat-section');
    var navLinks  = document.querySelectorAll('.ec-cat-link');

    window.addEventListener('scroll', function () {
        var scrollY = window.scrollY + 100;
        sections.forEach(function (sec) {
            if (sec.offsetTop <= scrollY && sec.offsetTop + sec.offsetHeight > scrollY) {
                navLinks.forEach(function (l) { l.classList.remove('active'); });
                var match = document.querySelector('.ec-cat-link[href="#' + sec.id + '"]');
                if (match) match.classList.add('active');
            }
        });
    });

    // ── Smooth scroll for sidebar links ──────────────────────────
    document.querySelectorAll('.ec-cat-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                window.scrollTo({ top: target.offsetTop - 80, behavior: 'smooth' });
            }
        });
    });

    // ── Nav link active on click ──────────────────────────────────
    document.querySelectorAll('.ec-nav-links a').forEach(function (a) {
        a.addEventListener('click', function () {
            document.querySelectorAll('.ec-nav-links a').forEach(function (x) { x.classList.remove('active'); });
            this.classList.add('active');
        });
    });

    // ── LightGallery ─────────────────────────────────────────────
    var galleryEl = document.getElementById('ec-gallery-grid');
    if (galleryEl) {
        lightGallery(galleryEl, {
            selector: '.ec-lg-item',
            plugins: [lgThumbnail],
            speed: 300,
            download: false
        });
    }

    // ── Banner click tracking ─────────────────────────────────────
    window.trackBannerClick = function (id) {
        $.post("{{ route('track.banner.click') }}", { banner_id: id, _token: '{{ csrf_token() }}' });
    };

    // ── Cart count refresh ────────────────────────────────────────
    $(document).on('cart:updated', function () {
        $('.cart-count-outer').load(window.location.href + ' .cart-count-inner');
    });
});
</script>
@endpush
