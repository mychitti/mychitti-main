@extends('front-views.layout')

@section('title', $store['meta_title'] ?: (($data['store_config']?->webpage_name ?? $store['name']) . ' in ' . _storeCityDisplay($store) . ' | My Chitti'))

@section('meta_keywords', $keywords)
@section('meta_description', $store['meta_description'] ?: (($data['store_config']?->webpage_name ?? $store['name']) . ' — trusted local business in ' . _storeCityDisplay($store) . '. View services, ratings and contact details on My Chitti.'))


@push('css_or_js') 
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --t12-primary: #0d9488;
            --t12-primary-dark: #115e59;
            --t12-primary-light: #99f6e4;
            --t12-primary-bg: #f0fdfa;
            --t12-accent: #f59e0b;
            --t12-dark: #0f172a;
            --t12-gray: #64748b;
            --t12-light: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--t12-light);
            color: var(--t12-dark);
            overflow-x: hidden;
        }

        .dfasdf {
            padding: 0.85rem 2rem;
            font-size: 17px;
        }

        /* Fade up animation */
        .t12-fade-up {
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.6s ease-out;
        }

        .product_data {
            margin: 3rem 0;
        }

        .cat_name {
            font-size: 1.375rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: var(--t12-dark);
            padding-bottom: 0.5rem;
            border-bottom: 3px solid var(--t12-primary);
            display: inline-block;
        }

        .t12-fade-up.active {
            opacity: 1;
            transform: translateY(0);
        }

        .t12-scale-in {
            opacity: 0;
            transform: scale(0.9);
            transition: all 0.5s ease-out;
        }

        .t12-scale-in.active {
            opacity: 1;
            transform: scale(1);
        }

        /* Top Bar */
        .t12-topbar {
            background: var(--t12-dark);
            padding: 0.5rem 0;
            font-size: 12px;
            color: #94a3b8;
        }

        .t12-topbar-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .t12-topbar a {
            color: var(--t12-primary-light);
            text-decoration: none;
        }

        /* Header */
        .t12-header {
            position: sticky;
            top: 0;
            z-index: 999;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .t12-header-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0.875rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .t12-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .t12-logo img {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            object-fit: cover;
        }

        .t12-logo h1 {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--t12-dark);
        }

        .t12-logo p {
            font-size: 11px;
            color: var(--t12-gray);
        }

        .t12-nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .t12-nav a {
            color: var(--t12-dark);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.3s;
        }

        .t12-nav a:hover {
            color: var(--t12-primary);
        }

        .t12-btn-primary {
            background: var(--t12-primary);
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }

        .t12-btn-primary:hover {
            background: var(--t12-primary-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.4);
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--t12-dark);
            padding: 5px;
        }

        @media (max-width: 992px) {

            .secondary_nav,
            .page-header {
                margin-top: 0 !important;
            }
        }

        /* Hero */
        .t12-hero {
            background: linear-gradient(160deg, var(--t12-primary-bg) 0%, white 50%, #fef3c7 100%);
            padding: 4rem 0 3rem;
            position: relative;
        }

        .t12-hero-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .t12-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            border: 1px solid #e2e8f0;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            color: var(--t12-primary);
            margin-bottom: 1.25rem;
        }

        .t12-hero-title {
            font-size: 3.25rem;
            font-weight: 900;
            line-height: 1.1;
            color: var(--t12-dark);
            margin-bottom: 1rem;
        }

        .t12-hero-title span {
            color: var(--t12-primary);
        }

        .t12-hero-desc {
            font-size: 1.0625rem;
            color: var(--t12-gray);
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .t12-hero-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .t12-btn-outline {
            color: var(--t12-dark);
            padding: 0.75rem 1.75rem;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
            font-size: 14px;
        }

        .t12-btn-outline:hover {
            border-color: var(--t12-primary);
            color: var(--t12-primary);
        }

        .t12-hero-img {
            position: relative;
        }

        .t12-hero-img img {
            width: 100%;
            height: 380px;
            object-fit: cover;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
        }

        .t12-hero-stats {
            display: flex;
            gap: 2rem;
            margin-top: 2rem;
        }

        .t12-hero-stat {
            text-align: center;
        }

        .t12-hero-stat-val {
            font-size: 1.75rem;
            font-weight: 900;
            color: var(--t12-primary);
        }

        .t12-hero-stat-lbl {
            font-size: 12px;
            color: var(--t12-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Breadcrumb */
        .t12-breadcrumb {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 0;
        }

        .t12-breadcrumb .breadcrumb {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            background: transparent;
            font-size: 13px;
        }

        /* Section */
        .t12-section {
            padding: 4rem 0;
        }

        .t12-wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .t12-section-header {
            margin-bottom: 2.5rem;
        }

        .t12-section-header.center {
            text-align: center;
        }

        .t12-section-tag {
            display: inline-block;
            color: var(--t12-primary);
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
        }

        .t12-section-title {
            font-size: 2.25rem;
            font-weight: 900;
            color: var(--t12-dark);
        }

        /* Info Grid */
        .t12-info-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2rem;
        }

        .t12-about-card {
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }

        .t12-about-card h2 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--t12-dark);
        }

        .t12-contact-card {
            background: var(--t12-dark);
            color: white;
            padding: 2.5rem;
            border-radius: 16px;
        }

        .t12-contact-card h3 {
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: var(--t12-primary-light);
        }

        .t12-contact-row {
            display: flex;
            align-items: start;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .t12-contact-row:last-child {
            border: none;
        }

        .t12-contact-icon {
            width: 42px;
            height: 42px;
            background: var(--t12-primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .t12-rating-box {
            background: linear-gradient(135deg, var(--t12-primary), var(--t12-primary-dark));
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            margin-top: 1.5rem;
        }

        .t12-rating-num {
            font-size: 3rem;
            font-weight: 900;
            line-height: 1;
            color: white;
        }

        /* Announcement */
        .t12-announce {
            background: linear-gradient(90deg, var(--t12-primary), var(--t12-primary-dark));
            color: white;
            padding: 0.875rem 0;
        }

        .t12-announce-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Products */
        .t12-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.5rem;
        }

        .t12-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .t12-card:hover {
            border-color: var(--t12-primary);
            box-shadow: 0 8px 30px rgba(13, 148, 136, 0.12);
            transform: translateY(-4px);
        }

        .t12-card-img {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .t12-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s;
        }

        .t12-card:hover .t12-card-img img {
            transform: scale(1.05);
        }

        .t12-card-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--t12-accent);
            color: white;
            padding: 0.3rem 0.75rem;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
        }

        .t12-card-time {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            padding: 0.3rem 0.75rem;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .t12-card-heart {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 36px;
            height: 36px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .t12-card-heart:hover {
            background: var(--t12-primary);
        }

        .t12-card-heart:hover i {
            color: white !important;
        }

        .t12-card-heart i {
            font-size: 16px;
        }

        .text_red {
            color: var(--t12-primary);
        }

        .text_grey {
            color: #cbd5e1;
        }

        .t12-card-body {
            padding: 1.25rem;
        }

        .t12-card-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--t12-dark);
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 45px;
        }

        .t12-card-price {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            margin: 0.75rem 0;
        }

        .t12-price-now {
            font-size: 1.375rem;
            font-weight: 900;
            color: var(--t12-primary);
        }

        .t12-price-was {
            font-size: 0.875rem;
            color: var(--t12-gray);
            text-decoration: line-through;
        }

        .t12-btn-card {
            width: 100%;
            padding: 0.7rem;
            border: none;
            border-radius: 8px;
            background: var(--t12-primary);
            color: white;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .t12-btn-card:hover {
            background: var(--t12-primary-dark);
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.4);
        }

        .t12-btn-remove {
            background: var(--t12-primary-bg);
            color: var(--t12-primary);
        }

        .t12-btn-remove:hover {
            background: var(--t12-primary);
            color: white;
        }

        /* Gallery */
        .t12-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .t12-gallery-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            aspect-ratio: 1;
            cursor: pointer;
            transition: all 0.3s;
        }

        .t12-gallery-item:hover {
            transform: scale(1.03);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            z-index: 10;
        }

        .t12-gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s;
        }

        .t12-gallery-item:hover img {
            transform: scale(1.1);
        }

        .t12-gallery-overlay {
            position: absolute;
            inset: 0;
            background: rgba(13, 148, 136, 0.85);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .t12-gallery-item:hover .t12-gallery-overlay {
            opacity: 1;
        }

        .t12-gallery-overlay i {
            font-size: 2rem;
            color: white;
        }

        /* Reviews */
        .t12-review {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }

        .t12-review:hover {
            border-color: var(--t12-primary);
        }

        /* Contact Cards */
        .t12-contact-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .t12-contact-tile {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }

        .t12-contact-tile:hover {
            border-color: var(--t12-primary);
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(13, 148, 136, 0.1);
        }

        .t12-contact-tile-icon {
            width: 60px;
            height: 60px;
            background: var(--t12-primary-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
        }

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
            color: rgba(255, 255, 255, 0.3);
        }

        .stars-fill {
            position: absolute;
            top: 0;
            left: 0;
            overflow: hidden;
            white-space: nowrap;
            color: white;
            width: 0;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .mobile-menu-toggle {
                display: block;
            }

            .t12-hero-inner {
                grid-template-columns: 1fr;
            }

            .t12-info-grid {
                grid-template-columns: 1fr;
            }

            .t12-nav {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 10px 0;
                gap: 0;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                border-bottom: 1px solid #e2e8f0;
                z-index: 10;
            }

            .t12-nav.show {
                display: flex;
            }

            .t12-nav a {
                padding: 12px 24px;
                display: block;
                font-size: 14px;
                border-bottom: 1px solid #f1f5f9;
            }

            .t12-nav a:last-child {
                border-bottom: none;
            }

            .t12-header-inner {
                position: relative;
            }

            .t12-products {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .t12-topbar {
                display: none;
            }

            .t12-logo h1 {
                font-size: 14px;
                max-width: 150px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .t12-logo p {
                display: none;
            }

            .t12-header-inner {
                padding: 0.75rem 1rem;
            }

            .t12-hero {
                padding: 2rem 0;
            }

            .t12-hero-title {
                font-size: 1.8rem;
            }

            .t12-hero-desc {
                font-size: 0.9rem;
            }

            .t12-hero-actions {
                flex-wrap: wrap;
            }

            .dfasdf {
                font-size: 13px;
                padding: 0.7rem 1.2rem;
            }

            .t12-btn-outline {
                padding: 0.65rem 1rem;
                font-size: 13px;
            }

            .t12-hero-img img {
                height: auto;
            }

            .t12-hero-inner {
                padding: 0 1rem;
                gap: 1.5rem;
            }

            .t12-hero-stat-val {
                font-size: 1.25rem;
            }

            .t12-products {
                grid-template-columns: repeat(2, 1fr);
            }

            .t12-gallery {
                grid-template-columns: repeat(3, 1fr);
            }

            .t12-card-img {
                height: 120px;
            }

            .t12-card-body {
                padding: 10px;
            }

            .t12-card-title {
                font-size: 13px;
                min-height: 36px;
            }

            .t12-price-now {
                font-size: 1.1rem;
            }

            .t12-section {
                padding: 1.5rem 0;
            }

            .t12-wrap {
                padding: 0 1rem;
            }

            .t12-section-title {
                font-size: 1.5rem;
            }

            .t12-info-grid {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }

            .t12-about-card {
                padding: 1.25rem;
            }

            .t12-contact-card {
                padding: 1.25rem;
            }

            .t12-rating-box {
                padding: 0.75rem;
            }

            .t12-rating-num {
                font-size: 2rem;
            }

            .cat_name {
                font-size: 1rem;
                margin-bottom: 0.75rem;
            }

            .product_data {
                margin: 1rem 0;
            }

            .t12-announce-inner {
                padding: 0 1rem;
            }

            .t12-announce-inner i {
                font-size: 16px !important;
            }

            .t12-announce-inner div {
                font-size: 12px !important;
            }
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
    <!-- Top Bar -->
    <div class="t12-topbar">
        <div class="t12-topbar-inner">
            <span><i class="fas fa-phone" style="margin-right: 0.5rem;"></i> {{ $store['phone'] }}</span>
            <span><a href="mailto:{{ $store['email'] }}"><i class="fas fa-envelope" style="margin-right: 0.5rem;"></i>
                    {{ $store['email'] }}</a></span>
        </div>
    </div>

    <!-- Header -->
    <header class="t12-header">
        <div class="t12-header-inner">
            <div class="t12-logo">
                <img loading="lazy" src="{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}"
                    alt="{{ $store['name'] }}">
                <div>
                    <h1>{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                    <p>{{ $store['meta_title'] }}</p>
                </div>
            </div>
            <button class="mobile-menu-toggle" onclick="document.querySelector('.t12-nav').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="t12-nav">
                <a href="#services">Services</a>
                <a href="#reviews">Reviews</a>
                <a href="#contact">Contact</a>
                <a href="#about">About</a>
                <a href="{{ route('store.gallery', [$store['slug']]) }}" class="t12-btn-primary">Gallery</a>
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <div class="t12-hero">
        <div class="t12-hero-inner">
            <div>
                <div class="t12-hero-badge">
                    <i class="fas fa-check-circle"></i> Trusted Business
                </div>
                <h1 class="t12-hero-title">Welcome to
                    <span>{{ $data['store_config']?->webpage_name ?? $store['name'] }}</span>
                </h1>
                <p class="t12-hero-desc">{{ $store['meta_title'] }}</p>
                <div class="t12-hero-actions">
                    <a href="#services" class="t12-btn-primary dfasdf" style="">Explore
                        Services</a>
                    <a href="#contact" class="t12-btn-outline">Get in Touch</a>
                </div>
                @php $store_rating = number_format($store->average_rating, 1); @endphp
                <div class="t12-hero-stats">
                    <div class="t12-hero-stat">
                        <div class="t12-hero-stat-val">{{ $store_rating }}</div>
                        <div class="t12-hero-stat-lbl">Rating</div>
                    </div>
                    <div class="t12-hero-stat">
                        <div class="t12-hero-stat-val">{{ $store->rating_count }}+</div>
                        <div class="t12-hero-stat-lbl">Reviews</div>
                    </div>
                    @if ($inventoryProductCount > 0)
                        <div class="t12-hero-stat">
                            <div class="t12-hero-stat-val">{{ $inventoryProductCount }}+</div>
                            <div class="t12-hero-stat-lbl">Products</div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="t12-hero-img">
                <img loading="lazy" src="{{ asset('storage/app/public/store/cover/') . '/' . $store['cover_photo'] }}"
                    alt="Cover">
            </div>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="t12-breadcrumb">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" style="color: var(--t12-primary);">Home</a></li>
                <li class="breadcrumb-item active">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</li>
            </ol>
        </nav>
    </div>

    <!-- Announcement -->
    @if ($store->announcement)
        <div class="t12-announce">
            <div class="t12-announce-inner">
                <i class="fas fa-bullhorn" style="font-size: 20px;"></i>
                <div style="font-size: 14px; font-weight: 700;">{{ $store->announcement_message }}</div>
            </div>
        </div>
    @endif

    <!-- Info Section -->
    <div class="t12-section" style="background: white;">
        <div class="t12-wrap">
            <div class="t12-info-grid">
                <div class="t12-about-card t12-fade-up">
                    <h2>About Our Business</h2>
                    @php
                        $description = $store['meta_description'];
                        $short = Str::limit($description, 350);
                    @endphp
                    <div style="font-size: 14px; color: var(--t12-gray); line-height: 1.8;" id="text-{{ $store['id'] }}">
                        {!! $short !!}
                        @if (strlen($description) > 350)
                            <span id="dots-{{ $store['id'] }}"></span>
                            <span id="more-{{ $store['id'] }}" class="d-none">{!! substr($description, 350) !!}</span>
                            <a class="cursor-pointer" style="color: var(--t12-primary); font-weight: 800;"
                                onclick="toggleReadMore({{ $store['id'] }})" id="btn-{{ $store['id'] }}">Read more →</a>
                        @endif
                    </div>
                </div>

                <div class="t12-contact-card t12-fade-up" style="transition-delay: 0.2s;">
                    <h3>Quick Contact</h3>

                    <div class="t12-contact-row">
                        <div class="t12-contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <h4
                                style="font-size: 10px; text-transform: uppercase; color: var(--t12-primary-light); margin-bottom: 0.5rem; letter-spacing: 1px;">
                                Phone</h4>
                            @php
                                $phones = $data['store_config']?->webpage_phones;
                                if ($phones) {
                                    $phones = json_decode($phones, true);
                                } else {
                                    $phones = [];
                                }
                            @endphp
                            <p style="font-size: 14px; margin: 0;">
                                @include('front-views.store_webpage.partials.phone-actions', ['phones' => $phones])
                            </p>
                        </div>
                    </div>

                    <div class="t12-contact-row">
                        <div class="t12-contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h4
                                style="font-size: 10px; text-transform: uppercase; color: var(--t12-primary-light); margin-bottom: 0.5rem; letter-spacing: 1px;">
                                Email</h4>
                            <p style="font-size: 14px; margin: 0;">
                                <a href="mailto:{{ $store['email'] }}"
                                    style="color: white; text-decoration: none;">{{ $data['store_config']?->webpage_email ?? $store->email }}</a>
                            </p>
                        </div>
                    </div>

                    <div class="t12-contact-row">
                        <div class="t12-contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h4
                                style="font-size: 10px; text-transform: uppercase; color: var(--t12-primary-light); margin-bottom: 0.5rem; letter-spacing: 1px;">
                                Address</h4>
                            <p style="font-size: 14px; margin: 0;">{{ $store['address'] }}</p>
                        </div>
                    </div>

                    <div class="t12-rating-box">
                        @php $store_rating = number_format($store->average_rating, 1); @endphp
                        <div class="t12-rating-num">{{ $store_rating }}</div>
                        <div class="rating-stars" data-rating="{{ $store_rating }}" style="margin: 1rem 0;">
                            <div class="stars-base">
                                <i class="fas fa-star" style="font-size: 18px;"></i>
                                <i class="fas fa-star" style="font-size: 18px;"></i>
                                <i class="fas fa-star" style="font-size: 18px;"></i>
                                <i class="fas fa-star" style="font-size: 18px;"></i>
                                <i class="fas fa-star" style="font-size: 18px;"></i>
                            </div>
                            <div class="stars-fill">
                                <i class="fas fa-star" style="font-size: 18px;"></i>
                                <i class="fas fa-star" style="font-size: 18px;"></i>
                                <i class="fas fa-star" style="font-size: 18px;"></i>
                                <i class="fas fa-star" style="font-size: 18px;"></i>
                                <i class="fas fa-star" style="font-size: 18px;"></i>
                            </div>
                        </div>
                        <div style="font-size: 12px; opacity: 0.95; color: white;">{{ $store->rating_count }} Reviews
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <div class="sharethis-inline-share-buttons"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Banners -->
    @if (count($data['banners']) > 0)
        <div style="max-width: 1400px; margin: 2rem auto; padding: 0 2rem;">
            <div class="owl-carousel banner-carousel t12-scale-in">
                @foreach ($data['banners'] as $value)
                    <a href="{{ $value->default_link ?? '#' }}" onclick="trackBannerClick({{ $value->id }})">
                        <img loading="lazy" src="{{ asset('storage/app/public/banner/') . '/' . $value->image }}"
                            alt="banner" style="border-radius: 12px; width: 100%;">
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Products Section -->
    <div class="t12-section" id="services" style="background: var(--t12-light);">
        <div class="t12-wrap">
            <div class="t12-section-header center">
                <div class="t12-section-tag">Our Offerings</div>
                <h2 class="t12-section-title">Products & Services</h2>
            </div>

            @foreach ($productdata as $key => $cat)
                <div class="product_data" style="f">
                    <h3 class="cat_name" style="">
                        {{ $cat->name }}</h3>

                    <div class="t12-products">
                        @foreach ($cat->items as $index => $pro)
                            @php
                                $variations = json_decode($pro->variations);
                                $firstVr = !empty($variations) ? json_encode($variations[0]) : '';
                                if ($firstVr && $module != 5 && ($pro->item_type ?? '') != 'product') {
                                    $selling_price = json_decode($firstVr)->price;
                                    $mrp = json_decode($firstVr)->mrpprice ?? json_decode($firstVr)->price;
                                } else {
                                    $selling_price = $pro->price;
                                    $mrp = $pro->mrp_price;
                                }
                            @endphp
                            <div class="pr_{{ $pro->id }} t12-card t12-fade-up"
                                style="transition-delay: {{ $index * 0.05 }}s;">
                                <div class="t12-card-img">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <img loading="lazy"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                            alt="{{ $pro->name }}">
                                    </a>

                                    @if ($module == 5 && $store->delivery_time)
                                        <div class="t12-card-time">
                                            <i class="fas fa-bolt" style="color: var(--t12-accent);"></i>
                                            {{ strtoupper($store->delivery_time) }}
                                        </div>
                                    @endif

                                    @if ($pro->discount > 0)
                                        <div class="t12-card-badge">
                                            -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                        </div>
                                    @endif

                                    <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                        class="prHeart_{{ $pro->id }} t12-card-heart">
                                        <i
                                            class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                    </div>
                                </div>

                                <div class="t12-card-body">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <h4 class="t12-card-title" title="{{ ucfirst($pro->name) }}">
                                            {{ ucfirst($pro->name) }}
                                        </h4>
                                    </a>

                                    @if ($module == 5)
                                        <p
                                            style="font-size: 11px; color: var(--t12-gray); margin-bottom: 0.5rem; min-height: 16px;">
                                            {{ !empty($variations) ? $variations[0]->type : '' }}
                                        </p>

                                        <div class="t12-card-price">
                                            <div class="t12-price-now">{{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0)
                                                <div class="t12-price-was">{{ _price($mrp) }}</div>
                                            @endif
                                        </div>

                                        <div class="cartSec_{{ $pro->id }}">
                                            @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                            @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                    class="t12-btn-card t12-btn-remove">
                                                    <i class="fa fa-times"></i> Remove
                                                </button>
                                            @else
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                    class="t12-btn-card">
                                                    <i class="fa fa-plus"></i> Add
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        @if ($pro->item_type == 'product')
                                            <div class="t12-card-price">
                                                <div class="t12-price-now">{{ _price($selling_price) }}</div>
                                                @if ($pro->discount > 0 || $mrp > $selling_price)
                                                    <div class="t12-price-was">{{ _price($mrp) }}</div>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($pro->item_type == 'product' && ($pro->stock ?? 1) <= 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif (($data['store_config']->lead_available ?? 1) == 0)
                                            <button disabled class="t12-btn-card" style="opacity:0.5;cursor:not-allowed;">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire' }}
                                            </button>
                                            <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                        @elseif (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                class="t12-btn-card">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire' }}
                                            </button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="t12-btn-card">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire' }}
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @foreach ($invItemdata as $key => $cat)
                <div class="product_data" style="f">
                    <h3 class="cat_name" style="">
                        {{ $cat->name }}</h3>

                    <div class="t12-products">
                        @foreach ($cat->items as $index => $pro)
                            @php
                                $variations = json_decode($pro->variations);
                                $firstVr = !empty($variations) ? json_encode($variations[0]) : '';
                                if ($firstVr && $module != 5 && ($pro->item_type ?? '') != 'product') {
                                    $selling_price = json_decode($firstVr)->price;
                                    $mrp = json_decode($firstVr)->mrpprice ?? json_decode($firstVr)->price;
                                } else {
                                    $selling_price = $pro->price;
                                    $mrp = $pro->mrp_price;
                                }
                            @endphp
                            <div class="pr_{{ $pro->id }} t12-card t12-fade-up"
                                style="transition-delay: {{ $index * 0.05 }}s;">
                                <div class="t12-card-img">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <img loading="lazy"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                            alt="{{ $pro->name }}">
                                    </a>

                                    @if ($module == 5 && $store->delivery_time)
                                        <div class="t12-card-time">
                                            <i class="fas fa-bolt" style="color: var(--t12-accent);"></i>
                                            {{ strtoupper($store->delivery_time) }}
                                        </div>
                                    @endif

                                    @if ($pro->discount > 0)
                                        <div class="t12-card-badge">
                                            -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                        </div>
                                    @endif

                                    <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                        class="prHeart_{{ $pro->id }} t12-card-heart">
                                        <i
                                            class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                    </div>
                                </div>

                                <div class="t12-card-body">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <h4 class="t12-card-title" title="{{ ucfirst($pro->name) }}">
                                            {{ ucfirst($pro->name) }}
                                        </h4>
                                    </a>

                                    @if ($module == 5)
                                        <p
                                            style="font-size: 11px; color: var(--t12-gray); margin-bottom: 0.5rem; min-height: 16px;">
                                            {{ !empty($variations) ? $variations[0]->type : '' }}
                                        </p>

                                        <div class="t12-card-price">
                                            <div class="t12-price-now">{{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0)
                                                <div class="t12-price-was">{{ _price($mrp) }}</div>
                                            @endif
                                        </div>

                                        <div class="cartSec_{{ $pro->id }}">
                                            @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                            @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                    class="t12-btn-card t12-btn-remove">
                                                    <i class="fa fa-times"></i> Remove
                                                </button>
                                            @else
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                    class="t12-btn-card">
                                                    <i class="fa fa-plus"></i> Add
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        @if ($pro->item_type == 'product')
                                            <div class="t12-card-price">
                                                <div class="t12-price-now">{{ _price($selling_price) }}</div>
                                                @if ($pro->discount > 0 || $mrp > $selling_price)
                                                    <div class="t12-price-was">{{ _price($mrp) }}</div>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($pro->item_type == 'product' && ($pro->stock ?? 1) <= 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif (($data['store_config']->lead_available ?? 1) == 0)
                                            <button disabled class="t12-btn-card" style="opacity:0.5;cursor:not-allowed;">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire' }}
                                            </button>
                                            <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                        @elseif (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                class="t12-btn-card">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire' }}
                                            </button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="t12-btn-card">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire' }}
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if (!count($productdata))
                <div style="text-align: center; padding: 4rem 0; color: var(--t12-gray);">
                    <i class="fas fa-box-open" style="font-size: 4rem; margin-bottom: 1.5rem; opacity: 0.2;"></i>
                    <p style="font-size: 1.125rem; font-weight: 600;">No products available</p>
                </div>
            @endif

        </div>
    </div>

    <!-- Gallery -->
    @if (count($store->galleries))
        <div class="t12-section" style="background: white;">
            <div class="t12-wrap">
                <div class="t12-section-header center">
                    <div class="t12-section-tag">Portfolio</div>
                    <h2 class="t12-section-title">Gallery</h2>
                </div>

                <div class="t12-gallery">
                    @foreach ($data['galleries'] as $index => $value)
                        <a target="_blank" href="{{ asset('storage/app/public/store/gallery') }}/{{ $value->image }}"
                            class="t12-gallery-item t12-scale-in lightgallery-item"
                            style="transition-delay: {{ $index * 0.05 }}s;">
                            <img loading="lazy"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                    $value['image'] ?? '',
                                    asset('storage/app/public/store/gallery') . '/' . $value['image'] ?? '',
                                    asset('public/assets/admin/img/160x160/img1.jpg'),
                                    'store/gallery/',
                                ) }}"
                                alt="Gallery {{ $index + 1 }}">
                            <div class="t12-gallery-overlay">
                                <i class="fas fa-search-plus"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Reviews -->
    @if (count($data['reviews']) && $module == 6)
        <div class="t12-section" id="reviews" style="background: var(--t12-light);">
            <div class="t12-wrap">
                <div class="t12-section-header center">
                    <div class="t12-section-tag">Testimonials</div>
                    <h2 class="t12-section-title">Customer Reviews</h2>
                </div>

                @foreach ($data['reviews'] as $index => $rev)
                    <div class="t12-review t12-fade-up" style="transition-delay: {{ $index * 0.1 }}s;">
                        <div style="display: flex; gap: 1.25rem; margin-bottom: 1.25rem;">
                            <img loading="lazy"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/') . '/' . $rev->profile_image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 3px solid var(--t12-primary);"
                                alt="{{ $rev->f_name }}">
                            <div style="flex: 1;">
                                <div
                                    style="font-size: 1.0625rem; font-weight: 800; color: var(--t12-dark); margin-bottom: 0.5rem;">
                                    {{ $rev->f_name . ' ' . $rev->l_name }}</div>
                                <div style="font-size: 11px; color: var(--t12-gray); margin-bottom: 0.5rem;">
                                    {{ _formatted_datetime($rev->created_at) }}</div>
                                <div style="display: flex; gap: 0.25rem;">
                                    @for ($i = 1; $i < 6; $i++)
                                        <i class="fas fa-star"
                                            style="font-size: 14px; color: {{ $rev->rating >= $i ? '#f59e0b' : '#e0e0e0' }};"></i>
                                    @endfor
                                </div>
                            </div>
                        </div>
                        <p style="font-size: 14px; color: var(--t12-gray); line-height: 1.8;">{{ $rev->comment }}</p>

                        @if ($rev->attachment)
                            @php $attachments = (array) $rev->attachment; @endphp
                            @if (!empty($attachments))
                                <div style="display: flex; gap: 0.75rem; margin-top: 1.25rem;">
                                    @foreach ($attachments as $img)
                                        <a target="_blank"
                                            href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}">
                                            <img loading="lazy"
                                                style="width: 80px; height: 80px; border-radius: 8px; object-fit: cover; border: 2px solid #e5e7eb; cursor: pointer;"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                alt="review">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        @if ($rev->reply)
                            <div
                                style="margin-top: 1.5rem; padding: 1.5rem; background: var(--t12-primary-bg); border-left: 3px solid var(--t12-primary); border-radius: 8px;">
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                    <img loading="lazy"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store['logo'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                        style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"
                                        alt="{{ $store->name }}">
                                    <div>
                                        <div style="font-weight: 800; font-size: 13px; color: var(--t12-dark);">Store
                                            Response</div>
                                        <div style="font-size: 11px; color: var(--t12-gray);">
                                            {{ _formatted_datetime($rev->replied_at) }}</div>
                                    </div>
                                </div>
                                <p style="font-size: 13px; color: var(--t12-gray); line-height: 1.7; margin: 0;">
                                    {{ $rev->reply }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($data['review_count'] > 2)
                    <div style="text-align: center; margin-top: 2.5rem;">
                        <a href="{{ route('store.reviews', [$store->slug]) }}" class="t12-btn-primary"
                            style="padding: 0.85rem 2rem;">
                            View All Reviews <i class="fas fa-arrow-right" style="margin-left: 0.75rem;"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Contact -->
    <div class="t12-section" id="contact" style="background: white;">
        <div class="t12-wrap">
            <div class="t12-section-header center">
                <div class="t12-section-tag">Get In Touch</div>
                <h2 class="t12-section-title">Contact Us</h2>
            </div>

            <div class="t12-contact-cards" style="margin-top: 2.5rem;">
                @php $contacts = [['icon' => 'map', 'title' => 'Location', 'value' => $store['address']], ['icon' => 'secured-letter', 'title' => 'Email', 'value' => $store['email'], 'link' => 'mailto:'], ['icon' => 'phone', 'title' => 'Phone', 'value' => $store['phone'], 'link' => 'tel:'], ['icon' => 'marker', 'title' => 'Directions', 'value' => 'View Map', 'modal' => true]]; @endphp

                @foreach ($contacts as $index => $contact)
                    <div class="t12-contact-tile t12-fade-up" style="transition-delay: {{ $index * 0.1 }}s;">
                        <div class="t12-contact-tile-icon">
                            <img loading="lazy" src="https://img.icons8.com/ios-filled/50/{{ $contact['icon'] }}.png"
                                alt="{{ $contact['title'] }}"
                                style="width: 28px; height: 28px; filter: sepia(1) saturate(5) hue-rotate(140deg) brightness(0.7);">
                        </div>
                        <div style="font-size: 1.125rem; font-weight: 800; color: var(--t12-dark); margin-bottom: 0.5rem;">
                            {{ $contact['title'] }}</div>
                        <div style="font-size: 13px; color: var(--t12-gray);">
                            @if (isset($contact['link']))
                                <a href="{{ $contact['link'] }}{{ $contact['value'] }}"
                                    style="color: var(--t12-primary); text-decoration: none; font-weight: 600;">{{ $contact['value'] }}</a>
                            @elseif (isset($contact['modal']))
                                <a class="cursor-pointer" data-bs-toggle="modal" data-bs-target="#exampleModal"
                                    style="color: var(--t12-primary); text-decoration: none; font-weight: 600;">{{ $contact['value'] }}</a>
                            @else
                                {{ $contact['value'] }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- About -->
    <div class="t12-section" id="about" style="background: var(--t12-light);">
        <div class="t12-wrap">
            <div class="t12-section-header center">
                <div class="t12-section-tag">About</div>
                <h2 class="t12-section-title">Our Story</h2>
            </div>

            <div class="t12-fade-up"
                style="background: white; border-radius: 16px; padding: 2.5rem; border: 1px solid #e2e8f0; line-height: 1.9; font-size: 14px; color: var(--t12-gray);">
                {!! $data['store_config']->about_us ?? 'Information coming soon.' !!}
            </div>
        </div>
    </div>

    <!-- Map Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header"
                    style="background: var(--t12-primary); color: white; border-radius: 16px 16px 0 0;">
                    <h5 class="modal-title">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="map" style="height: 450px; border-radius: 8px;"></div>
                </div>
            </div>
        </div>
    </div>
                                    <!-- STORE REVIEW SECTION -->
                                    <section class="reviews-section mt-5" id="give-review-section">
                                        <div class="section-container">
                                            <div class="text-center mb-4">
                                                <button class="btn btn-primary btn-lg px-5 py-3 shadow-lg" data-bs-toggle="modal" data-bs-target="#storeReviewModal{{ $store['id'] }}">
                                                    <i class="fas fa-star me-2"></i> <strong>Share Your Review</strong>
                                                </button>
                                            </div>
                                            @include('front-views.partials._store-review-form', ['store' => $store])
                                        </div>
                                    </section>

@include('front-views.partials._appointment_booking')
@include('front-views.partials._hospital_booking_modal')
@include('front-views.partials._claim_remove_business')
@endsection

@push('script_2')
    <script>
        function trackBannerClick(bannerId) {
            // Use sendBeacon so the click is recorded even when the banner (e.g. a
            // store banner) immediately navigates away — a plain $.post gets aborted.
            try {
                var fd = new FormData();
                fd.append('banner_id', bannerId);
                fd.append('_token', '{{ csrf_token() }}');
                if (navigator.sendBeacon) {
                    navigator.sendBeacon("{{ route('track.banner.click') }}", fd);
                    return;
                }
            } catch (e) {}
            $.post("{{ route('track.banner.click') }}", {
                banner_id: bannerId,
                _token: '{{ csrf_token() }}'
            });
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/lightgallery.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/plugins/video/lg-video.umd.min.js"></script>

    <script>
        // Scroll animations
        const t12Observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, {
            threshold: 0.05
        });

        document.querySelectorAll('.t12-fade-up, .t12-scale-in').forEach(el => {
            t12Observer.observe(el);
        });

        // Rating stars
        document.querySelectorAll('.rating-stars').forEach(el => {
            const rating = parseFloat(el.getAttribute('data-rating'));
            const percentage = (Math.min(rating, 5) / 5) * 100;
            const fill = el.querySelector('.stars-fill');
            if (fill) {
                fill.style.width = `${percentage}%`;
            }
        });

        // LightGallery
        if (document.querySelector('.t12-gallery')) {
            lightGallery(document.querySelector('.t12-gallery'), {
                selector: '.lightgallery-item',
                download: false,
                thumbnail: true,
                speed: 500
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
                btnText.innerText = "Read more →";
            } else {
                dots.style.display = "none";
                moreText.classList.remove("d-none");
                btnText.innerText = "Show less ←";
            }
        }

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== '#' && document.querySelector(href)) {
                    e.preventDefault();
                    document.querySelector(href).scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
@endpush
