@extends('front-views.layout')

@section('title', $store['meta_title'] ?? ($data['store_config']?->webpage_name ?? $store['name']))

@section('meta_keywords', $keywords)
@section('meta_description', $store['meta_description'])

 
@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style> 
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --secondary-color: #f59e0b;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f9fafb;
            --border-color: #e5e7eb;
            --success-color: #10b981;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* LightGallery Customization */
        .lg-object.lg-image {
            height: 100% !important;
        }

        .lg-counter {
            background: rgba(0, 0, 0, 0.6) !important;
            padding: 8px 16px !important;
            height: fit-content !important;
            border-radius: 20px;
            margin: 10px !important;
            font-size: 14px !important;
            color: white !important;
            font-weight: 500;
        }

        .lg-next.lg-icon,
        .lg-prev.lg-icon {
            background: white;
            border: none;
            color: var(--text-dark);
            border-radius: 50%;
            width: 48px;
            height: 48px;
            box-shadow: var(--shadow-lg);
        }

        /* Modern Navigation */
        .modern-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .modern-nav.scrolled {
            box-shadow: var(--shadow-md);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        .nav-logo {
            height: 50px;
            width: auto;
            object-fit: contain;
        }

        .nav-menu {
            display: flex;
            gap: 2rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-link {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            padding: 0.5rem 0;
            position: relative;
            transition: color 0.3s ease;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color);
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }

        .nav-cta {
            background: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .nav-cta:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .hero-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
        }

        .hero-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .hero-text {
            color: white;
        }

        .hero-title {
            color: white;
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            opacity: 0.95;
        }

        .hero-description {
            font-size: 1rem;
            line-height: 1.8;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .meta-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .meta-text {
            font-size: 14px;
            opacity: 0.9;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 1rem 1.5rem;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .rating-number {
            font-size: 2rem;
            font-weight: 700;
        }

        .rating-stars {
            position: relative;
            display: inline-block;
            font-size: 18px;
            color: #fff;
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
            color: #fbbf24;
            width: 0;
        }

        .rating-count {
            font-size: 14px;
            opacity: 0.9;
        }

        .hero-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
        }

        .hero-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Breadcrumb */
        .breadcrumb-section {
            background: var(--bg-light);
            padding: 1rem 0;
        }

        .breadcrumb-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 14px;
            margin: 0;
            padding: 0;
            background: transparent;
        }

        .breadcrumb-item {
            color: var(--text-light);
        }

        .breadcrumb-item a {
            color: var(--text-dark);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb-item a:hover {
            color: var(--primary-color);
        }

        /* Banner Carousel */
        .banner-section {
            padding: 1rem 0;
            background: white;
        }

        .banner-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Announcement */
        .announcement-bar {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 10px 0;
            margin: 1rem 0;
        }

        .announcement-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .announcement-icon {
            font-size: 24px;
        }

        .announcement-text {
            font-weight: 500;
            margin: 0;
        }

        /* Services Section */
        .services-section {
            background: var(--bg-light);
        }

        .section-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .section-header {
            text-align: center;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            font-size: 1.125rem;
            color: var(--text-light);
        }

        .category-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--secondary-color) 0%, #ea580c 100%);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            margin: 2rem 0;
            box-shadow: var(--shadow-md);
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .product-image-container {
            position: relative;
            width: 100%;
            height: 280px;
            overflow: hidden;
            background: var(--bg-light);
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(255, 255, 255, 0.95);
            color: var(--text-dark);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: var(--shadow-md);
        }

        .discount-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            box-shadow: var(--shadow-md);
        }

        .wishlist-btn {
            position: absolute;
            bottom: 12px;
            right: 12px;
            width: 44px;
            height: 44px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            z-index: 10;
        }

        .wishlist-btn:hover {
            transform: scale(1.1);
        }

        .wishlist-btn i {
            font-size: 20px;
            transition: color 0.3s ease;
        }

        .text_red {
            color: #ef4444;
        }

        .text_grey {
            color: #d1d5db;
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-name {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 54px;
        }

        .product-variant {
            font-size: 13px;
            color: var(--text-light);
            margin-bottom: 0.5rem;
            min-height: 20px;
        }

        .variant-badge {
            display: inline-block;
            background: var(--bg-light);
            color: var(--text-dark);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            margin-top: 0.25rem;
        }

        .product-pricing {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1rem;
        }

        .price-group {
            display: flex;
            flex-direction: column;
        }

        .current-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .original-price {
            font-size: 1rem;
            color: var(--text-light);
            text-decoration: line-through;
            min-height: 24px;
        }

        .product-action {
            margin-top: 1rem;
        }

        .btn-add-cart,
        .btn-remove-cart,
        .btn-enquiry {
            width: 100%;
            padding: 0.875rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-add-cart {
            background: var(--primary-color);
            color: white;
        }

        .btn-add-cart:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-remove-cart {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-remove-cart:hover {
            background: #fecaca;
        }

        .btn-enquiry {
            background: var(--secondary-color);
            color: white;
        }

        .btn-enquiry:hover {
            background: #ea580c;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Gallery Section */
        .gallery-section {
            padding: 4rem 0;
            background: white;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(163px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .gallery-item {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            aspect-ratio: 1;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .gallery-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .gallery-item:hover img {
            transform: scale(1.1);
        }

        /* Reviews Section */
        .reviews-section {
            padding: 4rem 0;
            background: var(--bg-light);
        }

        .review-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .review-card:hover {
            box-shadow: var(--shadow-md);
        }

        .review-header {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }

        .reviewer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--bg-light);
        }

        .reviewer-info {
            flex: 1;
        }

        .reviewer-name {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .review-date {
            font-size: 13px;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .review-rating {
            display: flex;
            gap: 0.25rem;
        }

        .review-rating i {
            font-size: 16px;
        }

        .review-text {
            color: var(--text-dark);
            line-height: 1.7;
            margin-top: 1rem;
        }

        .review-images {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .review-image {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .review-image:hover {
            transform: scale(1.05);
        }

        .store-reply {
            background: var(--bg-light);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
            border-left: 4px solid var(--primary-color);
        }

        .reply-header {
            display: flex;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .store-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .reply-text {
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Contact Section */
        .contact-section {
            padding: 4rem 0;
            background: white;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .contact-card {
            background: var(--bg-light);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .contact-card:hover {
            background: white;
            box-shadow: var(--shadow-md);
            transform: translateY(-4px);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .contact-icon img {
            width: 30px;
            height: 30px;
            filter: brightness(0) invert(1);
        }

        .contact-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .contact-text {
            color: var(--text-light);
            font-size: 15px;
        }

        .contact-text a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .contact-text a:hover {
            color: var(--primary-dark);
        }

        /* About Section */
        .about-section {
            padding: 4rem 0;
            background: var(--bg-light);
        }

        .about-content {
            background: white;
            border-radius: 16px;
            padding: 3rem;
            box-shadow: var(--shadow-sm);
            line-height: 1.8;
        }

        /* Share Buttons */
        .share-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .nav-menu {
                gap: 1rem;
            }

            .hero-content {
                grid-template-columns: 1fr;
            }

            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-dark);
            padding: 5px;
        }

        .mobile-only-gallery {
            display: none;
        }

        @media (max-width: 768px) {
            .product-name {
                    font-size: 14px;
            }
              .btn-add-cart,
        .btn-remove-cart,
        .btn-enquiry {
                padding: 6px;

        }
            .product-image-container {
                height: 106px;
            }

            .product-info {
                padding: 0.5rem;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .nav-container {
                height: 70px;
                padding: 0 1rem;
                position: relative;
            }

            .nav-logo {
                height: 40px;
            }

            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(10px);
                flex-direction: column;
                padding: 15px 0;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                z-index: 10;
                list-style: none;
                margin: 0;
            }

            .nav-menu.show {
                display: flex;
            }

            .nav-menu li {
                padding: 10px 20px;
            }

            .mobile-only-gallery {
                display: list-item;
            }

            .nav-cta-desktop {
                display: none !important;
            }

            .hero-section {
                padding: 2rem 0;
            }

            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(117px, 1fr));
                gap: 1rem;
            }

            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(67px, 1fr));
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }

            .about-content {
                padding: 1.5rem;
            }
        }

        /* Map Modal */
        #map {
            height: 400px;
            width: 100%;
            border-radius: 12px;
        }

        .modal-content {
            border-radius: 16px;
            border: none;
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        /* Utility Classes */
        .one-line-ellipsis {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .text-break {
            word-break: break-word;
        }
    </style>

    <!-- LightGallery CSS -->
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
            img.src =
                "{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}";
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
    <!-- Modern Navigation -->
    <nav class="modern-nav">
        <div class="nav-container">
            <div>
                <img loading="lazy" class="nav-logo" src="{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}"
                    alt="{{ $store['name'] }}">
            </div>
            <button class="mobile-menu-toggle" onclick="document.querySelector('.nav-menu').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="nav-menu">
                <li><a href="#services" class="nav-link">Services</a></li>
                <li><a href="#store-ratings" class="nav-link">Reviews</a></li>
                <li><a href="#contact" class="nav-link">Contact</a></li>
                <li><a href="#about" class="nav-link">About</a></li>
                <li class="mobile-only-gallery"><a href="{{ route('store.gallery', [$store['slug']]) }}"
                        class="nav-link">Gallery</a></li>
            </ul>
            <div class="nav-cta-desktop">
                <a href="{{ route('store.gallery', [$store['slug']]) }}" class="nav-cta">Gallery</a>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="breadcrumb-section">
        <div class="breadcrumb-container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">›</li>
                <li class="breadcrumb-item active">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</li>
            </ol>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="hero-section" id="home">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                    <p class="hero-subtitle">{{ $store['meta_title'] }}</p>

                    @php
                        $description = $store['meta_description'];
                        $short = Str::limit($description, 150);
                    @endphp
                    <div class="hero-description" id="text-{{ $store['id'] }}">
                        {!! $short !!}
                        @if (strlen($description) > 150)
                            <span id="dots-{{ $store['id'] }}"></span>
                            <span id="more-{{ $store['id'] }}" class="d-none">{!! substr($description, 150) !!}</span>
                            <a class="cursor-pointer" style="text-decoration: underline; opacity: 0.9;"
                                onclick="toggleReadMore({{ $store['id'] }})" id="btn-{{ $store['id'] }}">Read more</a>
                        @endif
                    </div>

                    <div class="hero-meta">
                        <div class="meta-item">
                            <div class="meta-icon">
                                <i class="fa fa-phone"></i>
                            </div>
                            <div class="meta-text">
                                @php
                                    $phones = $data['store_config']?->webpage_phones;
                                    if ($phones) {
                                        $phones = json_decode($phones, true);
                                    } else {
                                        $phones = [];
                                    }
                                @endphp
                                @include('front-views.store_webpage.partials.phone-actions', ['phones' => $phones])
                            </div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-icon">
                                <i class="fa fa-envelope"></i>
                            </div>
                            <div class="meta-text">
                                {{ $data['store_config']?->webpage_email ?? $store->email }}
                            </div>
                        </div>
                    </div>

                    <div class="rating-display">
                        @php $store_rating = number_format($store->average_rating, 1); @endphp
                        <div class="rating-number">{{ $store_rating }}</div>
                        <div>
                            <div class="rating-stars" data-rating="{{ $store_rating }}">
                                <div class="stars-base">
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                                <div class="stars-fill">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                            <div class="rating-count">({{ $store->rating_count }} Reviews)</div>
                        </div>
                    </div>

                    <div class="share-section">
                        <div class="sharethis-inline-share-buttons"></div>
                    </div>
                </div>

                <div class="hero-image">
                    <img loading="lazy" src="{{ asset('storage/app/public/store/cover/') . '/' . $store['cover_photo'] }}"
                        alt="Store Cover">
                </div>
            </div>
        </div>
    </div>

    <!-- Banner Section -->
    @if (count($data['banners']) > 0)
        <div class="banner-section">
            <div class="banner-container">
                <div class="owl-carousel banner-carousel">
                    @foreach ($data['banners'] as $key => $value)
                        <a href="{{ $value->default_link ?? '#' }}" onclick="trackBannerClick({{ $value->id }})">
                            <img loading="lazy" src="{{ asset('storage/app/public/banner/') . '/' . $value->image }}"
                                alt="banner" style="border-radius: 16px; width: 100%;">
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Announcement -->
    @if ($store->announcement)
        <div class="announcement-bar">
            <div class="announcement-content">
                <i class="fa fa-solid fa-bullhorn announcement-icon"></i>
                <p class="announcement-text">{{ $store->announcement_message }}</p>
            </div>
        </div>
    @endif

    <!-- Services Section -->
    <div class="services-section" id="services">
        <div class="section-container">

            @foreach ($productdata as $key => $cat)
                <div class="section-header">
                    <div class="category-badge">{{ $cat->name }}</div>
                </div>

                <div class="product-grid">
                    @foreach ($cat->items as $pro)
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
                        <div class="pr_{{ $pro->id }} product-card">
                            <div class="product-image-container">
                                <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                    <img loading="lazy" class="product-image"
                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                        alt="{{ $pro->name }}">
                                </a>

                                @if ($module == 5)
                                    <div class="product-badge">
                                        <i class="fas fa-fire" style="color: #f59e0b;"></i>
                                        <span>{{ strtoupper($store->delivery_time) }}</span>
                                    </div>
                                @endif

                                @if ($pro->discount > 0)
                                    <div class="discount-badge">
                                        {{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '% OFF' : \App\CentralLogics\Helpers::currency_symbol() . ' OFF' }}
                                    </div>
                                @endif

                                <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                    class="prHeart_{{ $pro->id }} wishlist-btn">
                                    <i
                                        class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                </div>
                            </div>

                            <div class="product-info">
                                <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                    <h4 class="product-name" data-id="pr_{{ $pro->id }}"
                                        title="{{ ucfirst($pro->name) }}">
                                        {{ ucfirst($pro->name) }}
                                    </h4>
                                </a>

                                @if ($module == 5)
                                    <p class="product-variant">
                                        {{ !empty($variations) ? $variations[0]->type : '' }}
                                    </p>
                                    @if (count($variations) > 1)
                                        <span class="variant-badge">+{{ count($variations) - 1 }} more option(s)</span>
                                    @endif

                                    <div class="product-pricing">
                                        <div class="price-group">
                                            <div class="current-price">{{ _price($selling_price) }}</div>
                                            <div class="original-price">
                                                @if ($pro->discount > 0)
                                                    {{ _price($mrp) }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="product-action cartSec_{{ $pro->id }}">
                                        @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                        @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                            <button
                                                onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                class="btn-remove-cart">
                                                <i class="fa fa-times"></i>
                                                Remove
                                            </button>
                                        @else
                                            <button
                                                onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                class="btn-add-cart">
                                                <i class="fa fa-shopping-bag"></i>
                                                Add to Cart
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    @if ($pro->item_type == 'product')
                                        <div class="product-pricing">
                                            <div class="price-group">
                                                <div class="current-price">{{ _price($selling_price) }}</div>
                                                <div class="original-price">
                                                    @if ($pro->discount > 0 || $mrp > $selling_price)
                                                        {{ _price($mrp) }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="product-action">
                                        @if ($pro->item_type == 'product' && ($pro->stock ?? 1) <= 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif (($data['store_config']->lead_available ?? 1) == 0)
                                            <button disabled class="btn-enquiry" style="opacity:0.5;cursor:not-allowed;" title="Currently unavailable for enquiries">
                                                <i class="fas fa-user-cog"></i>
                                                {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquiry Now' }}
                                            </button>
                                            <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                        @elseif (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                class="btn-enquiry">
                                                <i class="fas fa-user-cog"></i>
                                                {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquiry Now' }}
                                            </button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="btn-enquiry">
                                                <i class="fas fa-user-cog"></i>
                                                {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquiry Now' }}
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach

            @foreach ($invItemdata as $key => $cat)
                <div class="section-header">
                    <div class="category-badge">{{ $cat->name }}</div>
                </div>

                <div class="product-grid">
                    @foreach ($cat->items as $pro)
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
                        <div class="pr_{{ $pro->id }} product-card">
                            <div class="product-image-container">
                                <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                    <img loading="lazy" class="product-image"
                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                        alt="{{ $pro->name }}">
                                </a>

                                @if ($module == 5)
                                    <div class="product-badge">
                                        <i class="fas fa-fire" style="color: #f59e0b;"></i>
                                        <span>{{ strtoupper($store->delivery_time) }}</span>
                                    </div>
                                @endif

                                @if ($pro->discount > 0)
                                    <div class="discount-badge">
                                        {{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '% OFF' : \App\CentralLogics\Helpers::currency_symbol() . ' OFF' }}
                                    </div>
                                @endif

                                <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                    class="prHeart_{{ $pro->id }} wishlist-btn">
                                    <i
                                        class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                </div>
                            </div>

                            <div class="product-info">
                                <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                    <h4 class="product-name" data-id="pr_{{ $pro->id }}"
                                        title="{{ ucfirst($pro->name) }}">
                                        {{ ucfirst($pro->name) }}
                                    </h4>
                                </a>

                                @if ($module == 5)
                                    <p class="product-variant">
                                        {{ !empty($variations) ? $variations[0]->type : '' }}
                                    </p>
                                    @if (count($variations) > 1)
                                        <span class="variant-badge">+{{ count($variations) - 1 }} more option(s)</span>
                                    @endif

                                    <div class="product-pricing">
                                        <div class="price-group">
                                            <div class="current-price">{{ _price($selling_price) }}</div>
                                            <div class="original-price">
                                                @if ($pro->discount > 0)
                                                    {{ _price($mrp) }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="product-action cartSec_{{ $pro->id }}">
                                        @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                        @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                            <button
                                                onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                class="btn-remove-cart">
                                                <i class="fa fa-times"></i>
                                                Remove
                                            </button>
                                        @else
                                            <button
                                                onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                class="btn-add-cart">
                                                <i class="fa fa-shopping-bag"></i>
                                                Add to Cart
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    @if ($pro->item_type == 'product')
                                        <div class="product-pricing">
                                            <div class="price-group">
                                                <div class="current-price">{{ _price($selling_price) }}</div>
                                                <div class="original-price">
                                                    @if ($pro->discount > 0 || $mrp > $selling_price)
                                                        {{ _price($mrp) }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="product-action">
                                        @if ($pro->item_type == 'product' && ($pro->stock ?? 1) <= 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif (($data['store_config']->lead_available ?? 1) == 0)
                                            <button disabled class="btn-enquiry" style="opacity:0.5;cursor:not-allowed;" title="Currently unavailable for enquiries">
                                                <i class="fas fa-user-cog"></i>
                                                {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquiry Now' }}
                                            </button>
                                            <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                        @elseif (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                class="btn-enquiry">
                                                <i class="fas fa-user-cog"></i>
                                                {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquiry Now' }}
                                            </button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="btn-enquiry">
                                                <i class="fas fa-user-cog"></i>
                                                {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquiry Now' }}
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach

            @if (!count($productdata))
                <div style="text-align: center; padding: 4rem 0; color: var(--text-light);">
                    <i class="fas fa-box-open" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p style="font-size: 1.25rem;">No Products found</p>
                </div>
            @endif

        </div>
    </div>

    <!-- Gallery Section -->
    @if (count($store->galleries))
        <div class="gallery-section">
            <div class="section-container">
                <div class="section-header">
                    <h2 class="section-title">A Peek Into Our Space</h2>
                    <p class="section-subtitle">Explore our beautiful gallery</p>
                </div>

                <div class="gallery-grid">
                    @foreach ($data['galleries'] as $key => $value)
                        <a target="_blank" data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                            href="{{ asset('storage/app/public/store/gallery') }}/{{ $value->image }}"
                            class="gallery-item lightgallery-item">
                            <img loading="lazy"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                    $value['image'] ?? '',
                                    asset('storage/app/public/store/gallery') . '/' . $value['image'] ?? '',
                                    asset('public/assets/admin/img/160x160/img1.jpg'),
                                    'store/gallery/',
                                ) }}"
                                alt="Gallery image {{ $key + 1 }}">
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Reviews Section -->
    @if (count($data['reviews']) && $module == 6)
        <div class="reviews-section" id="store-ratings">
            <div class="section-container">
                <div class="section-header">
                    <h2 class="section-title">What People Are Saying</h2>
                    <p class="section-subtitle">About {{ ucwords($store->name) }}</p>
                </div>

                @foreach ($data['reviews'] as $rev)
                    <div class="review-card">
                        <div class="review-header">
                            <img loading="lazy"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/') . '/' . $rev->profile_image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                class="reviewer-avatar" alt="{{ $rev->f_name . ' ' . $rev->l_name }}">
                            <div class="reviewer-info">
                                <h5 class="reviewer-name">{{ $rev->f_name . ' ' . $rev->l_name }}</h5>
                                <p class="review-date">{{ _formatted_datetime($rev->created_at) }}</p>
                                <div class="review-rating">
                                    @for ($i = 1; $i < 6; $i++)
                                        <i class="fa fa-star {{ $rev->rating >= $i ? 'text-warning' : '' }}"
                                            style="color: {{ $rev->rating >= $i ? '#fbbf24' : '#e5e7eb' }};"></i>
                                    @endfor
                                </div>
                            </div>
                        </div>

                        <p class="review-text">{{ $rev->comment }}</p>

                        @if ($rev->attachment)
                            @php $attachments = (array) $rev->attachment; @endphp
                            @if (!empty($attachments))
                                <div class="review-images">
                                    @foreach ($attachments as $img)
                                        <a target="_blank"
                                            href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}">
                                            <img loading="lazy" class="review-image"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                alt="review">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        @if ($rev->reply)
                            <div class="store-reply">
                                <div class="reply-header">
                                    <img loading="lazy"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store['logo'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                        class="store-avatar" alt="{{ $store->name }}">
                                    <div>
                                        <p class="review-date" style="margin-bottom: 0;">
                                            {{ _formatted_datetime($rev->replied_at) }}</p>
                                    </div>
                                </div>
                                <p class="reply-text">{{ $rev->reply }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($data['review_count'] > 2)
                    <div style="text-align: center; margin-top: 2rem;">
                        <a href="{{ route('store.reviews', [$store->slug]) }}" class="nav-cta">
                            View All Reviews <i class="fa fa-arrow-right" style="margin-left: 0.5rem;"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Contact Section -->
    <div class="contact-section" id="contact">
        <div class="section-container">
            <div class="section-header">
                <h2 class="section-title">Get In Touch</h2>
                <p class="section-subtitle">We'd love to hear from you</p>
            </div>

            <div class="contact-grid">
                <div class="contact-card">
                    <div class="contact-icon">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/map.png" alt="Address Icon" />
                    </div>
                    <div class="contact-title">Address</div>
                    <div class="contact-text">{{ $store['address'] }}</div>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/secured-letter.png"
                            alt="Email Icon" />
                    </div>
                    <div class="contact-title">Email</div>
                    <div class="contact-text">
                        <a href="mailto:{{ $store['email'] }}">{{ $store['email'] }}</a>
                    </div>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/phone.png" alt="Phone Icon" />
                    </div>
                    <div class="contact-title">Phone</div>
                    <div class="contact-text">
                        <a href="tel:{{ $store['phone'] }}">{{ $store['phone'] }}</a>
                    </div>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/marker.png" alt="Location Icon" />
                    </div>
                    <div class="contact-title">Location</div>
                    <div class="contact-text">
                        <a class="cursor-pointer" data-bs-toggle="modal" data-bs-target="#exampleModal">
                            View on Map
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- About Section -->
    <div class="about-section" id="about">
        <div class="section-container">
            <div class="section-header">
                <h2 class="section-title">About Our Store</h2>
                <p class="section-subtitle">Learn more about who we are</p>
            </div>

            <div class="about-content">
                {!! $data['store_config']->about_us ?? '' !!}
            </div>
        </div>
    </div>

    <!-- Map Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">
                        {{ $data['store_config']?->webpage_name ?? $store['name'] }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="map"></div>
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
        // Smooth scrolling for navigation links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Navigation scroll effect
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('.modern-nav');
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });

        // Rating stars animation
        document.querySelectorAll('.rating-stars').forEach(el => {
            const rating = parseFloat(el.getAttribute('data-rating'));
            const percentage = (Math.min(rating, 5) / 5) * 100;
            el.querySelector('.stars-fill').style.width = `${percentage}%`;
        });

        // LightGallery initialization
        if (document.querySelector('.gallery-grid')) {
            lightGallery(document.querySelector('.gallery-grid'), {
                selector: '.lightgallery-item',
                download: false,
                thumbnail: true,
                animateThumb: true,
                showThumbByDefault: false
            });
        }

        // Read more toggle
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
    </script>
@endpush
