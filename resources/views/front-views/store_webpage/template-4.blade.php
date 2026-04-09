@extends('front-views.layout')

@section('title', $store['meta_title'] ?? ($data['store_config']?->webpage_name ?? $store['name']))

@section('meta_keywords', $keywords)
@section('meta_description', $store['meta_description'])


@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --primary: #0f172a;
            --accent: #3b82f6;
            --text: #334155;
            --light: #f8fafc;
            --border: #e2e8f0;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', sans-serif;
            color: var(--text);
            background: #fff;
            line-height: 1.5;
        }

        /* Minimal Header */
        .minimal-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: saturate(180%) blur(20px);
            border-bottom: 1px solid var(--border);
            z-index: 999;
            height: 60px;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo-mini {
            height: 36px;
            width: auto;
        }

        .header-nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .header-nav a {
            color: var(--text);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .header-nav a:hover {
            color: var(--accent);
        }

        .btn-gallery {
            background: var(--accent);
            color: white !important;
            padding: 0.5rem 1.25rem;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-gallery:hover {
            background: #2563eb;
            color: white;
            transform: translateY(-1px);
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none; 
            font-size: 24px;
            cursor: pointer;
            color: var(--text);
            padding: 5px;
        }

        /* Compact Hero */
        .hero-compact {
            padding: 2rem 0;
            background: linear-gradient(180deg, var(--light) 0%, #fff 100%);
        }

        .hero-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 2.5rem;
            align-items: start;
        }

        .hero-visual {
            position: sticky;
            top: 80px;
        }

        .cover-image {
            width: 100%;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .quick-info {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border);
        }

        .store-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        .store-subtitle {
            font-size: 13px;
            color: var(--text);
            margin-bottom: 1rem;
        }

        .rating-compact {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .rating-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stars-mini {
            display: flex;
            gap: 2px;
        }

        .stars-mini i {
            font-size: 14px;
            color: #fbbf24;
        }

        .rating-text {
            font-size: 12px;
            color: var(--text);
        }

        .info-list {
            list-style: none;
        }

        .info-item {
            display: flex;
            align-items: start;
            gap: 0.75rem;
            padding: 0.75rem 0;
            font-size: 13px;
            border-bottom: 1px solid var(--border);
        }

        .info-item:last-child {
            border: none;
        }

        .info-icon {
            color: var(--accent);
            margin-top: 2px;
            font-size: 14px;
        }

        .info-text {
            flex: 1;
            color: var(--text);
        }

        .share-compact {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }

        /* Main Content */
        .main-content {
            background: white;
            width: 100%;
    overflow: hidden;
        }

        .content-section {
            padding: 1.5rem 10px;
        }

        .section-title-mini {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text);
            opacity: 0.6;
            margin-bottom: 1rem;
        }

        /* Breadcrumb */
        .breadcrumb-mini {
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }

        .breadcrumb-mini .breadcrumb {
            font-size: 12px;
            margin: 0;
            padding: 0;
            background: transparent;
        }

        .breadcrumb-mini .breadcrumb-item {
            color: var(--text);
        }

        .breadcrumb-mini .breadcrumb-item a {
            color: var(--accent);
            text-decoration: none;
        }

        /* Minimal Product Cards */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .product-mini {
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.2s;
            position: relative;
        }

        .product-mini:hover {
            border-color: var(--accent);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .product-thumb {
            position: relative;
            width: 100%;
            aspect-ratio: 1;
            overflow: hidden;
            background: var(--light);
        }

        .product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .badge-mini {
            position: absolute;
            top: 6px;
            left: 6px;
            background: var(--danger);
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .time-badge-mini {
            position: absolute;
            top: 6px;
            right: 6px;
            background: white;
            color: var(--text);
            font-size: 9px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .heart-mini {
            position: absolute;
            bottom: 6px;
            right: 6px;
            width: 28px;
            height: 28px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .heart-mini i {
            font-size: 12px;
        }

        .text_red {
            color: var(--danger);
        }

        .text_grey {
            color: #cbd5e1;
        }

        .product-details {
            padding: 0.75rem;
        }

        .product-name-mini {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--primary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 36px;
            line-height: 1.4;
        }

        .product-variant-mini {
            font-size: 10px;
            color: var(--text);
            margin-bottom: 0.5rem;
            min-height: 14px;
        }

        .variant-count {
            font-size: 9px;
            background: var(--light);
            padding: 2px 6px;
            border-radius: 3px;
            display: inline-block;
            margin-top: 2px;
        }

        .price-mini {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .current-price-mini {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary);
        }

        .old-price-mini {
            font-size: 11px;
            color: var(--text);
            text-decoration: line-through;
            opacity: 0.6;
        }

        .btn-action-mini {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: white;
            color: var(--accent);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
        }

        .btn-action-mini:hover {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .btn-remove-mini {
            background: #fee2e2;
            color: var(--danger);
            border-color: #fee2e2;
        }

        .btn-remove-mini:hover {
            background: var(--danger);
            color: white;
            border-color: var(--danger);
        }

        .btn-enquiry-mini {
            background: var(--warning);
            color: white;
            border-color: var(--warning);
        }

        .btn-enquiry-mini:hover {
            background: #ea580c;
            border-color: #ea580c;
        }

        /* Category Badge */
        .category-header {
            background: var(--light);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin: 2rem 0 1rem;
        }

        .category-name {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }

        /* Gallery Minimal */
        .gallery-minimal {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(121px, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .gallery-thumb {
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
        }

        .gallery-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .gallery-thumb:hover img {
            transform: scale(1.05);
        }

        /* Reviews Compact */
        .review-mini {
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .review-header-mini {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .reviewer-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .reviewer-details {
            flex: 1;
        }

        .reviewer-name-mini {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .review-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 11px;
            color: var(--text);
        }

        .stars-inline {
            display: flex;
            gap: 2px;
        }

        .stars-inline i {
            font-size: 11px;
            color: #fbbf24;
        }

        .review-content {
            font-size: 13px;
            line-height: 1.6;
            color: var(--text);
        }

        .review-images-mini {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .review-img {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            object-fit: cover;
        }

        .reply-mini {
            margin-top: 0.75rem;
            padding: 0.75rem;
            background: var(--light);
            border-radius: 6px;
            border-left: 3px solid var(--accent);
        }

        .reply-header-mini {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .store-pic {
            width: 24px;
            height: 24px;
            border-radius: 50%;
        }

        .reply-content {
            font-size: 12px;
            color: var(--text);
        }

        /* Contact Grid */
        .contact-grid-mini {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .contact-item-mini {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }

        .contact-icon-mini {
            width: 40px;
            height: 40px;
            margin: 0 auto 0.5rem;
            opacity: 0.7;
        }

        .contact-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text);
            opacity: 0.6;
            margin-bottom: 0.25rem;
        }

        .contact-value {
            font-size: 12px;
            color: var(--text);
        }

        .contact-value a {
            color: var(--accent);
            text-decoration: none;
        }

        /* About */
        .about-box {
            background: var(--light);
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1rem;
            font-size: 13px;
            line-height: 1.7;
        }

        /* Announcement */
        .announcement-mini {
            background: linear-gradient(90deg, var(--success) 0%, #16a34a 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Banner */
        .banner-mini {
            margin: 1rem 0;
        }

        .banner-mini img {
            width: 100%;
            border-radius: 8px;
        }

        /* Map Modal */
        #map {
            height: 400px;
            width: 100%;
            border-radius: 8px;
        }

        .modal-content {
            border-radius: 12px;
            border: none;
        }

        /* LightGallery */
        .lg-counter {
            background: rgba(0, 0, 0, 0.6) !important;
            padding: 6px 12px !important;
            border-radius: 6px !important;
            font-size: 12px !important;
        }

        .lg-next.lg-icon,
        .lg-prev.lg-icon {
            background: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .hero-visual {
                position: relative;
                top: 0;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            }

            .mobile-menu-toggle { display: block; }
            .header-nav {
                display: none;
                position: absolute;
                top: 100%;
                    gap: 1rem;
                left: 0;
                right: 0;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(20px);
                flex-direction: column;
                padding: 15px 0;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                z-index: 10;
            }
            .header-nav.show { display: flex; }
            .header-nav a { padding: 10px 20px; display: block; }
            .header-content { position: relative; }
        }

        @media (max-width: 640px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }

            .gallery-minimal {
                grid-template-columns: repeat(3, 1fr);
            }

            .contact-grid-mini {
                grid-template-columns: 1fr;
            }
        }

        /* Utility */
        .cursor-pointer {
            cursor: pointer;
        }

        .d-none {
            display: none;
        }

        .rating-stars {
            position: relative;
            display: inline-block;
        }

        .stars-base i {
            color: #e5e7eb;
        }

        .stars-fill {
            position: absolute;
            top: 0;
            left: 0;
            overflow: hidden;
            white-space: nowrap;
            color: #fbbf24;
            width: 0;
        }
    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lightgallery.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lightgallery.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-thumbnail.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-video.css">
    
    <script type="text/javascript"
        src="https://platform-api.sharethis.com/js/sharethis.js#property=673458a1da72090013b84006&product=inline-share-buttons&source=platform"
        async="async"></script>
    
    <script>
        function loadScript(src, callback) {
            if (document.querySelector(`script[src="${src}"]`)) {
                console.log("Google Maps API already loaded.");
                return;
            }
            var script = document.createElement("script");
            script.type = "text/javascript";
            script.async = true;
            script.defer = true;
            script.src = src;
            script.onload = callback;
            document.head.appendChild(script);
        }

        function initMap() {
            var initialLocation = {
                lat: @php echo $store['latitude'] @endphp,
                lng: @php echo  $store['longitude'] @endphp
            };

            var map = new google.maps.Map(document.getElementById('map'), {
                zoom: 14,
                center: initialLocation,
                mapId: "b2c6179556df0b45"
            });

            const {
                AdvancedMarkerElement
            } = google.maps.marker;

            const marker = new AdvancedMarkerElement({
                position: initialLocation,
                map: map,
                title: "Your Location",
                content: document.createElement("div")
            });
            const img = document.createElement("img");
            img.src = "{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}";
            img.style.width = "45px";
            img.style.height = "45px";
            img.style.borderRadius = "50%";
            img.style.border = "3px solid white";

            marker.content.appendChild(img);

            var geocoder = new google.maps.Geocoder();
            var infoWindow = new google.maps.InfoWindow();

            google.maps.event.addListener(marker, 'dragend', function(event) {
                var lat = event.latLng.lat();
                var lng = event.latLng.lng();

                geocoder.geocode({
                    'location': {
                        lat: lat,
                        lng: lng
                    }
                }, function(results, status) {
                    if (status === 'OK') {
                        if (results[0]) {
                            map.setZoom(11);
                            var address = results[0].formatted_address;
                            $('#address_field').val(address);
                            $('#address_hid').val(address);
                            $('#latitude').val(lat);
                            $('#longitude').val(lng);
                        } else {
                            window.alert('No results found');
                        }
                    } else {
                        window.alert('Geocoder failed due to: ' + status);
                    }
                });
            });
        }

        function init() {
            loadScript(
                "https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places,marker&callback=initMap&loading=async"
            );
        }

        window.onload = init;
    </script>
@endpush

@section('content')
    <!-- Minimal Header -->
    <header class="minimal-header">
        <div class="header-content">
            <img loading="lazy" class="logo-mini" src="{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}" alt="{{ $store['name'] }}">
            <button class="mobile-menu-toggle" onclick="document.querySelector('.header-nav').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="header-nav">
                <a href="#services">Services</a>
                <a href="#reviews">Reviews</a>
                <a href="#contact">Contact</a>
                <a href="#about">About</a>
                <a href="{{ route('store.gallery', [$store['slug']]) }}" class="btn-gallery">Gallery</a>
            </nav>
        </div>
    </header>

    <!-- Breadcrumb -->
    <div class="breadcrumb-mini">
        <div class="hero-container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item">›</li>
                    <li class="breadcrumb-item active">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Compact Hero -->
    <div class="hero-compact">
        <div class="hero-container">
            <div class="hero-grid">
                <!-- Sidebar Info -->
                <div class="hero-visual">
                    <img loading="lazy" class="cover-image" src="{{ asset('storage/app/public/store/cover/') . '/' . $store['cover_photo'] }}" alt="Store Cover">
                    
                    <div class="quick-info">
                        <h1 class="store-title">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                        <p class="store-subtitle">{{ $store['meta_title'] }}</p>

                        <div class="rating-compact">
                            @php $store_rating = number_format($store->average_rating, 1); @endphp
                            <span class="rating-value">{{ $store_rating }}</span>
                            <div>
                                <div class="stars-mini rating-stars" data-rating="{{ $store_rating }}">
                                    <div class="stars-base">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div class="stars-fill">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                                <div class="rating-text">{{ $store->rating_count }} reviews</div>
                            </div>
                        </div>

                        <ul class="info-list">
                            <li class="info-item">
                                <i class="fa fa-phone info-icon"></i>
                                <div class="info-text">
                                    @php
                                        $phones = $data['store_config']?->webpage_phones;
                                        if ($phones) {
                                            $phones = json_decode($phones, true);
                                        } else {
                                            $phones = [];
                                        }
                                    @endphp
                                    @if (!empty($phones))
                                        {{ implode(', ', $phones) }}
                                    @else
                                        {{ $store['phone'] }}
                                    @endif
                                </div>
                            </li>
                            <li class="info-item">
                                <i class="fa fa-envelope info-icon"></i>
                                <div class="info-text">{{ $data['store_config']?->webpage_email ?? $store->email }}</div>
                            </li>
                            <li class="info-item">
                                <i class="fa fa-map-marker-alt info-icon"></i>
                                <div class="info-text">{{ $store['address'] }}</div>
                            </li>
                        </ul>

                        <div class="share-compact">
                            <div class="sharethis-inline-share-buttons"></div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="main-content">
                    <!-- Description -->
                    @php
                        $description = $store['meta_description'];
                        $short = Str::limit($description, 200);
                    @endphp
                    @if($description)
                    <div class="content-section">
                        <div id="text-{{ $store['id'] }}" style="font-size: 13px; line-height: 1.7; color: var(--text);">
                            {!! $short !!}
                            @if (strlen($description) > 200)
                                <span id="dots-{{ $store['id'] }}"></span>
                                <span id="more-{{ $store['id'] }}" class="d-none">{!! substr($description, 200) !!}</span>
                                <a class="cursor-pointer" style="color: var(--accent); font-weight: 600;" onclick="toggleReadMore({{ $store['id'] }})"
                                    id="btn-{{ $store['id'] }}">Read more</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Banners -->
                    @if(count($data['banners']) > 0)
                    <div class="banner-mini">
                        <div class="owl-carousel banner-carousel">
                            @foreach ($data['banners'] as $value)
                                <a href="{{ $value->default_link ?? '#' }}" onclick="trackBannerClick({{ $value->id }})">
                                    <img loading="lazy" src="{{ asset('storage/app/public/banner/') . '/' . $value->image }}" alt="banner">
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Announcement -->
                    @if ($store->announcement)
                        <div class="announcement-mini">
                            <i class="fa fa-bullhorn"></i>
                            <span>{{ $store->announcement_message }}</span>
                        </div>
                    @endif

                    <!-- Services -->
                    <div class="content-section" id="services">

                        @foreach ($productdata as $key => $cat)
                            <div class="category-header">
                                <h2 class="category-name">{{ $cat->name }}</h2>
                            </div>

                            <div class="products-grid">
                                @foreach ($cat->items as $pro)
                                    @php
                                        $variations = json_decode($pro->variations);
                                        $firstVr = !empty($variations) ? json_encode($variations[0]) : '';
                                        if ($firstVr) {
                                            $selling_price = json_decode($firstVr)->price;
                                            $mrp = json_decode($firstVr)->mrpprice ?? json_decode($firstVr)->price;
                                        } else {
                                            $selling_price = $pro->price;
                                            $mrp = $pro->mrp_price;
                                        }
                                    @endphp
                                    <div class="pr_{{ $pro->id }} product-mini">
                                        <div class="product-thumb">
                                            <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                                <img loading="lazy" 
                                                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                                    alt="{{ $pro->name }}">
                                            </a>

                                            @if ($module == 5 && $store->delivery_time)
                                                <div class="time-badge-mini">
                                                    <i class="fas fa-fire" style="color: var(--warning);"></i>
                                                    {{ strtoupper($store->delivery_time) }}
                                                </div>
                                            @endif

                                            @if ($pro->discount > 0)
                                                <div class="badge-mini">
                                                    {{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                                </div>
                                            @endif

                                            <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                                class="prHeart_{{ $pro->id }} heart-mini">
                                                <i class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                            </div>
                                        </div>

                                        <div class="product-details">
                                            <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                                <h4 class="product-name-mini" title="{{ ucfirst($pro->name) }}">
                                                    {{ ucfirst($pro->name) }}
                                                </h4>
                                            </a>

                                            @if ($module == 5)
                                                <p class="product-variant-mini">
                                                    {{ !empty($variations) ? $variations[0]->type : '' }}
                                                </p>
                                                @if (count($variations) > 1)
                                                    <span class="variant-count">+{{ count($variations) - 1 }} more</span>
                                                @endif

                                                <div class="price-mini">
                                                    <span class="current-price-mini">{{ _price($selling_price) }}</span>
                                                    @if ($pro->discount > 0)
                                                        <span class="old-price-mini">{{ _price($mrp) }}</span>
                                                    @endif
                                                </div>

                                                <div class="cartSec_{{ $pro->id }}">
                                                    @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                                    @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                        <button onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                            class="btn-action-mini btn-remove-mini">
                                                            <i class="fa fa-times"></i> Remove
                                                        </button>
                                                    @else
                                                        <button onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                            class="btn-action-mini">
                                                            <i class="fa fa-plus"></i> Add
                                                        </button>
                                                    @endif
                                                </div>
                                            @else
                                                @if ($pro->item_type == 'product')
                                                    <div class="price-mini">
                                                        <span class="current-price-mini">{{ _price($selling_price) }}</span>
                                                        @if ($pro->discount > 0 || $mrp > $selling_price)
                                                            <span class="old-price-mini">{{ _price($mrp) }}</span>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if (($data['store_config']->lead_available ?? 1) == 0)
                                                    <button disabled class="btn-action-mini btn-enquiry-mini" style="opacity:0.5;cursor:not-allowed;">
                                                        <i class="fas fa-user-cog"></i> Enquiry
                                                    </button>
                                                    <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                                @elseif (auth('web')->user())
                                                    <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                        class="btn-action-mini btn-enquiry-mini">
                                                        <i class="fas fa-user-cog"></i> Enquiry
                                                    </button>
                                                @else
                                                    <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                        class="btn-action-mini btn-enquiry-mini">
                                                        <i class="fas fa-user-cog"></i> Enquiry
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach

                        @foreach ($invItemdata as $key => $cat)
                            <div class="category-header">
                                <h2 class="category-name">{{ $cat->name }}</h2>
                            </div>

                            <div class="products-grid">
                                @foreach ($cat->items as $pro)
                                    @php
                                        $variations = json_decode($pro->variations);
                                        $firstVr = !empty($variations) ? json_encode($variations[0]) : '';
                                        if ($firstVr) {
                                            $selling_price = json_decode($firstVr)->price;
                                            $mrp = json_decode($firstVr)->mrpprice ?? json_decode($firstVr)->price;
                                        } else {
                                            $selling_price = $pro->price;
                                            $mrp = $pro->mrp_price;
                                        }
                                    @endphp
                                    <div class="pr_{{ $pro->id }} product-mini">
                                        <div class="product-thumb">
                                            <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                                <img loading="lazy"
                                                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                                    alt="{{ $pro->name }}">
                                            </a>

                                            @if ($module == 5 && $store->delivery_time)
                                                <div class="time-badge-mini">
                                                    <i class="fas fa-fire" style="color: var(--warning);"></i>
                                                    {{ strtoupper($store->delivery_time) }}
                                                </div>
                                            @endif

                                            @if ($pro->discount > 0)
                                                <div class="badge-mini">
                                                    {{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                                </div>
                                            @endif

                                            <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                                class="prHeart_{{ $pro->id }} heart-mini">
                                                <i class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                            </div>
                                        </div>

                                        <div class="product-details">
                                            <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                                <h4 class="product-name-mini" title="{{ ucfirst($pro->name) }}">
                                                    {{ ucfirst($pro->name) }}
                                                </h4>
                                            </a>

                                            @if ($module == 5)
                                                <p class="product-variant-mini">
                                                    {{ !empty($variations) ? $variations[0]->type : '' }}
                                                </p>
                                                @if (count($variations) > 1)
                                                    <span class="variant-count">+{{ count($variations) - 1 }} more</span>
                                                @endif

                                                <div class="price-mini">
                                                    <span class="current-price-mini">{{ _price($selling_price) }}</span>
                                                    @if ($pro->discount > 0)
                                                        <span class="old-price-mini">{{ _price($mrp) }}</span>
                                                    @endif
                                                </div>

                                                <div class="cartSec_{{ $pro->id }}">
                                                    @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                                    @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                        <button onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                            class="btn-action-mini btn-remove-mini">
                                                            <i class="fa fa-times"></i> Remove
                                                        </button>
                                                    @else
                                                        <button onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                            class="btn-action-mini">
                                                            <i class="fa fa-plus"></i> Add
                                                        </button>
                                                    @endif
                                                </div>
                                            @else
                                                @if ($pro->item_type == 'product')
                                                    <div class="price-mini">
                                                        <span class="current-price-mini">{{ _price($selling_price) }}</span>
                                                        @if ($pro->discount > 0 || $mrp > $selling_price)
                                                            <span class="old-price-mini">{{ _price($mrp) }}</span>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if (($data['store_config']->lead_available ?? 1) == 0)
                                                    <button disabled class="btn-action-mini btn-enquiry-mini" style="opacity:0.5;cursor:not-allowed;">
                                                        <i class="fas fa-user-cog"></i> Enquiry
                                                    </button>
                                                    <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                                @elseif (auth('web')->user())
                                                    <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                        class="btn-action-mini btn-enquiry-mini">
                                                        <i class="fas fa-user-cog"></i> Enquiry
                                                    </button>
                                                @else
                                                    <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                        class="btn-action-mini btn-enquiry-mini">
                                                        <i class="fas fa-user-cog"></i> Enquiry
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach

                        @if (!count($productdata))
                            <div style="text-align: center; padding: 3rem 0; color: var(--text); opacity: 0.5;">
                                <p>No products available</p>
                            </div>
                        @endif

                    </div>

                    <!-- Gallery -->
                    @if (count($store->galleries))
                        <div class="content-section">
                            <h3 class="section-title-mini">Gallery</h3>
                            <div class="gallery-minimal">
                                @foreach ($data['galleries'] as $value)
                                    <a target="_blank"
                                        href="{{ asset('storage/app/public/store/gallery') }}/{{ $value->image }}"
                                        class="gallery-thumb lightgallery-item">
                                        <img loading="lazy" 
                                              data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                    $value['image'] ?? '',
                                    asset('storage/app/public/store/gallery') . '/' . $value['image'] ?? '',
                                    asset('public/assets/admin/img/160x160/img1.jpg'),
                                    'store/gallery/',
                                ) }}"
                                            alt="Gallery">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Reviews -->
                    @if (count($data['reviews']) && $module == 6)
                        <div class="content-section" id="reviews">
                            <h3 class="section-title-mini">Customer Reviews</h3>

                            @foreach ($data['reviews'] as $rev)
                                <div class="review-mini">
                                    <div class="review-header-mini">
                                        <img loading="lazy" 
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/') . '/' . $rev->profile_image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                            class="reviewer-pic"
                                            alt="{{ $rev->f_name }}">
                                        <div class="reviewer-details">
                                            <div class="reviewer-name-mini">{{ $rev->f_name . ' ' . $rev->l_name }}</div>
                                            <div class="review-meta">
                                                <div class="stars-inline">
                                                    @for ($i = 1; $i < 6; $i++)
                                                        <i class="fa fa-star {{ $rev->rating >= $i ? '' : '' }}" style="color: {{ $rev->rating >= $i ? '#fbbf24' : '#e5e7eb' }};"></i>
                                                    @endfor
                                                </div>
                                                <span>•</span>
                                                <span>{{ _formatted_datetime($rev->created_at) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <p class="review-content">{{ $rev->comment }}</p>

                                    @if ($rev->attachment)
                                        @php $attachments = json_decode($rev->attachment); @endphp
                                        @if (!empty($attachments))
                                            <div class="review-images-mini">
                                                @foreach ($attachments as $img)
                                                    <a target="_blank" href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}">
                                                        <img loading="lazy" class="review-img"
                                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                            alt="review">
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif

                                    @if ($rev->reply)
                                        <div class="reply-mini">
                                            <div class="reply-header-mini">
                                                <img loading="lazy" 
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store['logo'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                                    class="store-pic"
                                                    alt="{{ $store->name }}">
                                                <span style="font-size: 11px; color: var(--text);">{{ _formatted_datetime($rev->replied_at) }}</span>
                                            </div>
                                            <p class="reply-content">{{ $rev->reply }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            @if ($data['review_count'] > 2)
                                <div style="text-align: center; margin-top: 1rem;">
                                    <a href="{{ route('store.reviews', [$store->slug]) }}" class="btn-gallery" style="display: inline-block;">
                                        View All Reviews →
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Contact -->
                    <div class="content-section" id="contact">
                        <h3 class="section-title-mini">Contact Information</h3>
                        <div class="contact-grid-mini">
                            <div class="contact-item-mini">
                                <img loading="lazy" class="contact-icon-mini" src="https://img.icons8.com/ios-filled/50/map.png" alt="Address">
                                <div class="contact-label">Address</div>
                                <div class="contact-value">{{ $store['address'] }}</div>
                            </div>
                            <div class="contact-item-mini">
                                <img loading="lazy" class="contact-icon-mini" src="https://img.icons8.com/ios-filled/50/secured-letter.png" alt="Email">
                                <div class="contact-label">Email</div>
                                <div class="contact-value"><a href="mailto:{{ $store['email'] }}">{{ $store['email'] }}</a></div>
                            </div>
                            <div class="contact-item-mini">
                                <img loading="lazy" class="contact-icon-mini" src="https://img.icons8.com/ios-filled/50/phone.png" alt="Phone">
                                <div class="contact-label">Phone</div>
                                <div class="contact-value"><a href="tel:{{ $store['phone'] }}">{{ $store['phone'] }}</a></div>
                            </div>
                            <div class="contact-item-mini">
                                <img loading="lazy" class="contact-icon-mini" src="https://img.icons8.com/ios-filled/50/marker.png" alt="Location">
                                <div class="contact-label">Location</div>
                                <div class="contact-value">
                                    <a class="cursor-pointer" data-bs-toggle="modal" data-bs-target="#exampleModal">View Map</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- About -->
                    <div class="content-section" id="about">
                        <h3 class="section-title-mini">About Us</h3>
                        <div class="about-box">
                            {!! $data['store_config']->about_us ?? 'No information available.' !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </div>
@include('front-views.partials._appointment_booking')
@include('front-views.partials._claim_remove_business')
@endsection

@push('script_2')
    <script>
        function trackBannerClick(bannerId) {
            $.post("{{ route('track.banner.click') }}", {
                banner_id: bannerId,
                _token: '{{ csrf_token() }}'
            });
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/lightgallery.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/plugins/video/lg-video.umd.min.js"></script>
    
    <script>
        // Rating stars
        document.querySelectorAll('.rating-stars').forEach(el => {
            const rating = parseFloat(el.getAttribute('data-rating'));
            const percentage = (Math.min(rating, 5) / 5) * 100;
            el.querySelector('.stars-fill').style.width = `${percentage}%`;
        });

        // LightGallery
        if (document.querySelector('.gallery-minimal')) {
            lightGallery(document.querySelector('.gallery-minimal'), {
                selector: '.lightgallery-item',
                download: false,
                thumbnail: true
            });
        }

        // Read more
        function toggleReadMore(id) {
            var dots = document.getElementById("dots-" + id);
            var moreText = document.getElementById("more-" + id);
            var btnText = document.getElementById("btn-" + id);

            if (dots.style.display === "none") {
                dots.style.display = "inline";
                moreText.classList.add("d-none");
                btnText.innerText = "Read more";
            } else {
                dots.style.display = "none";
                moreText.classList.remove("d-none");
                btnText.innerText = "Show less";
            }
        }

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href !== '#' && document.querySelector(href)) {
                    e.preventDefault();
                    document.querySelector(href).scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
@endpush