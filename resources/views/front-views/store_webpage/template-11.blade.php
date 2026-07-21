@extends('front-views.layout')

@section('title', $store['meta_title'] ?? ($data['store_config']?->webpage_name ?? $store['name']))

@section('meta_keywords', $keywords)
@section('meta_description', $store['meta_description'])


@push('css_or_js') 
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root { 
            --red-primary: #dc2626;
            --red-dark: #991b1b;
            --red-light: #fca5a5;
            --red-bg: #fee2e2;
            --dark: #1f2937;
            --gray: #6b7280;
            --light: #f9fafb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #fff;
            color: var(--dark);
            overflow-x: hidden;
        }

        /* Active Animations */
        .slide-in-right {
            opacity: 0;
            transform: translateX(100px) rotate(5deg);
            transition: all 0.7s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .slide-in-right.active {
            opacity: 1;
            transform: translateX(0) rotate(0);
        }

        .bounce-in {
            opacity: 0;
            transform: scale(0.3) rotate(-15deg);
            transition: all 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .bounce-in.active {
            opacity: 1;
            transform: scale(1) rotate(0);
        }

        .pop-in {
            opacity: 0;
            transform: scale(0);
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .pop-in.active {
            opacity: 1;
            transform: scale(1);
        }

        /* Compact Header */
        .compact-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 999;
            background: var(--dark);
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.3);
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
            }

            to {
                transform: translateY(0);
            }
        }

        .header-wrap {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0.75rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-compact {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: bounceIn 0.8s ease-out;
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .logo-img-compact {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 2px solid var(--red-primary);
            transition: all 0.3s;
        }

        .logo-img-compact:hover {
            transform: rotate(360deg) scale(1.1);
            border-color: var(--red-light);
        }

        .logo-text-compact h1 {
            font-size: 1.125rem;
            font-weight: 800;
            color: white;
        }

        .logo-text-compact p {
            font-size: 9px;
            color: var(--red-light);
        }

        .nav-compact {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-compact a {
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            position: relative;
            transition: all 0.3s;
        }

        .nav-compact a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--red-primary);
            transition: width 0.3s;
        }

        .nav-compact a:hover {
            color: var(--red-light);
            transform: translateY(-2px);
        }

        .nav-compact a:hover::after {
            width: 100%;
        }

        .btn-red {
            background: var(--red-primary);
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn-red::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: var(--red-dark);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.5s;
        }

        .btn-red:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.5);
            color: white;
        }

        .btn-red:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-red span {
            position: relative;
            z-index: 1;
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: white;
            padding: 5px;
        }

        /* Compact Hero */
        .compact-hero {
            margin-top: 60px;
            background: linear-gradient(135deg, var(--red-primary) 0%, var(--red-dark) 100%);
            padding: 3rem 0;
            position: relative;
            overflow: hidden;
        }

        .compact-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 70%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .hero-compact-wrap {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-text-compact {
            color: white;
            animation: slideInLeft 1s ease-out;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-100px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .hero-tag-compact {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.4rem 1rem;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .hero-title-compact {
            color: white;
            font-size: 3rem;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 1rem;
        }

        .hero-desc-compact {
            font-size: 1rem;
            margin-bottom: 1.5rem;
            opacity: 0.95;
        }

        .hero-actions-compact {
            display: flex;
            gap: 1rem;
        }

        .btn-white-red {
            background: white;
            color: var(--red-primary);
            padding: 0.85rem 2rem;
            border-radius: 25px;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-white-red:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            color: var(--red-primary);
        }

        .btn-outline-red {
            background: transparent;
            color: white;
            padding: 0.85rem 2rem;
            border-radius: 25px;
            font-weight: 800;
            text-decoration: none;
            border: 2px solid white;
            transition: all 0.3s;
        }

        .btn-outline-red:hover {
            background: white;
            color: var(--red-primary);
            transform: translateY(-5px);
        }

        .hero-img-compact {
            animation: slideInRight 1s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .hero-image {
            width: 100%;
            height: 350px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s;
        }

        .hero-image:hover {
            transform: scale(1.03) rotate(2deg);
        }

        .stats-compact {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .stat-compact {
            background: rgba(255, 255, 255, 0.15);
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s;
        }

        .stat-compact:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-5px);
        }

        .stat-val {
            font-size: 1.75rem;
            font-weight: 900;
            line-height: 1;
            color: antiquewhite;
        }

        .stat-lbl {
            color: white;
            font-size: 10px;
            margin-top: 0.5rem;
            text-transform: uppercase;
        }

        /* Breadcrumb */
        .breadcrumb-compact {
            background: var(--light);
            padding: 0.75rem 0;
        }

        .breadcrumb-compact .breadcrumb {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 2rem;
            background: transparent;
            font-size: 12px;
        }

        /* Minimized Cards Section */
        .section-compact {
            padding: 3rem 0;
        }

        .section-wrap {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .section-head-compact {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .section-tag-compact {
            display: inline-block;
            background: var(--red-primary);
            color: white;
            padding: 0.4rem 1.25rem;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 1rem;
            animation: bounceIn 0.8s;
        }

        .section-title-compact {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--dark);
        }

        /* Minimized Product Cards */
        .products-compact {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-top: 2rem;
        }

        .card-compact {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            position: relative;
        }

        .card-compact:hover {
            transform: translateY(-10px) rotate(-2deg);
            box-shadow: 0 15px 40px rgba(220, 38, 38, 0.3);
        }

        .card-img-compact {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .card-img-compact img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .card-compact:hover .card-img-compact img {
            transform: scale(1.2) rotate(5deg);
        }

        .badge-compact {
            position: absolute;
            top: 8px;
            left: 8px;
            background: var(--red-primary);
            color: white;
            padding: 0.35rem 0.85rem;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 900;
            animation: shake 3s infinite;
        }

        @keyframes shake {

            0%,
            100% {
                transform: rotate(0deg);
            }

            25% {
                transform: rotate(-5deg);
            }

            75% {
                transform: rotate(5deg);
            }
        }

        .time-compact {
            position: absolute;
            top: 8px;
            right: 8px;
            background: white;
            padding: 0.35rem 0.85rem;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .heart-compact {
            position: absolute;
            bottom: 8px;
            right: 8px;
            width: 36px;
            height: 36px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s;
        }

        .heart-compact:hover {
            transform: scale(1.3) rotate(15deg);
            background: var(--red-primary);
        }

        .heart-compact:hover i {
            color: white !important;
        }

        .heart-compact i {
            font-size: 16px;
        }

        .text_red {
            color: var(--red-primary);
        }

        .text_grey {
            color: #cbd5e1;
        }

        .card-body-compact {
            padding: 1rem;
        }

        .card-title-compact {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 45px;
        }

        .card-price-compact {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            margin: 0.75rem 0;
        }

        .price-now-compact {
            font-size: 1.375rem;
            font-weight: 900;
            color: var(--red-primary);
        }

        .price-was-compact {
            font-size: 0.875rem;
            color: var(--gray);
            text-decoration: line-through;
        }

        .btn-card-compact {
            width: 100%;
            padding: 0.7rem;
            border: none;
            border-radius: 20px;
            background: var(--dark);
            color: white;
            font-weight: 700;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
        }

        .btn-card-compact:hover {
            background: var(--red-primary);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.4);
        }

        .btn-remove-compact {
            background: var(--red-bg);
            color: var(--red-primary);
        }

        .btn-remove-compact:hover {
            background: var(--red-primary);
            color: white;
        }

        /* Minimized Gallery */
        .gallery-minimized {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .gallery-mini-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            aspect-ratio: 1;
            cursor: pointer;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .gallery-mini-item:hover {
            transform: scale(1.08) rotate(3deg);
            box-shadow: 0 12px 35px rgba(220, 38, 38, 0.3);
            z-index: 10;
        }

        .gallery-mini-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .gallery-mini-item:hover img {
            transform: scale(1.3);
        }

        .gallery-overlay-mini {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.9) 0%, rgba(153, 27, 27, 0.9) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .gallery-mini-item:hover .gallery-overlay-mini {
            opacity: 1;
        }

        .gallery-icon-mini {
            font-size: 2rem;
            color: white;
            animation: zoomPulse 0.5s ease-out;
        }

        @keyframes zoomPulse {
            0% {
                transform: scale(0);
            }

            50% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Info Section */
        .split-info-compact {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2rem;
            margin: 3rem 0;
        }

        .about-compact {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--red-primary);
        }

        .about-compact h2 {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .contact-compact {
            background: var(--dark);
            color: white;
            padding: 2rem;
            border-radius: 12px;
        }

        .contact-compact h3 {
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: var(--red-light);
        }

        .contact-row-compact {
            display: flex;
            align-items: start;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .contact-row-compact:last-child {
            border: none;
        }

        .contact-icon-compact {
            width: 40px;
            height: 40px;
            background: var(--red-primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .rating-compact {
            background: var(--red-primary);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            margin-top: 1.5rem;
        }

        .rating-num-compact {
            font-size: 3rem;
            font-weight: 900;
            line-height: 1;
            color: white;
        }

        /* Announcement */
        .announce-compact {
            background: var(--red-primary);
            color: white;
            padding: 1rem 0;
            animation: slideInDown 0.6s;
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-100%);
            }

            to {
                transform: translateY(0);
            }
        }

        .announce-wrap {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cat_name {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: var(--dark);
            border-bottom: 3px solid var(--red-primary);
            display: inline-block;
            padding-bottom: 0.5rem;
        }

        .product_data {
            margin: 3rem 0;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-compact-wrap {
                grid-template-columns: 1fr;
            }

            .split-info-compact {
                grid-template-columns: 1fr;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .nav-compact {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--dark);
                flex-direction: column;
                padding: 10px 0;
                gap: 0;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                z-index: 10;
            }

            .nav-compact.show {
                display: flex;
            }

            .nav-compact a {
                padding: 12px 25px;
                display: block;
                font-size: 14px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            }

            .nav-compact a:last-child {
                border-bottom: none;
            }

            .nav-compact a::after {
                display: none;
            }

            .header-wrap {
                position: relative;
            }

            .products-compact {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            }

            .gallery-minimized {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .logo-text-compact h1 {
                font-size: 14px;
                max-width: 140px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .logo-text-compact p {
                display: none;
            }

            .hero-title-compact {
                font-size: 1.8rem;
            }

            .hero-desc-compact {
                font-size: 0.85rem;
            }

            .hero-image {
                height: auto;
            }

            .hero-actions-compact {
                flex-wrap: wrap;
            }

            .btn-white-red,
            .btn-outline-red {
                padding: 6px 20px;
                font-size: 13px;
            }

            .hero-compact-wrap {
                padding: 0 1rem;
                gap: 1.5rem;
            }

            .stat-compact {
                padding: 8px;
            }

            .stat-val {
                font-size: 1.2rem;
            }

            .section-compact {
                padding: 1.5rem 0;
            }

            .section-wrap {
                padding: 0 1rem;
            }

            .section-title-compact {
                font-size: 1.5rem;
            }

            .section-head-compact {
                margin-bottom: 1.5rem;
            }

            .products-compact {
                grid-template-columns: repeat(2, 1fr);
            }

            .gallery-minimized {
                grid-template-columns: repeat(3, 1fr);
            }

            .card-img-compact {
                height: 120px;
            }

            .card-body-compact {
                padding: 8px;
            }

            .card-title-compact {
                font-size: 13px;
                min-height: 36px;
            }

            .card-price-compact {
                margin: 0.5rem 0;
            }

            .price-now-compact {
                font-size: 1.1rem;
            }

            .split-info-compact {
                display: flex;
                flex-direction: column;
                gap: 1rem;
                margin: 1rem 0;
            }

            .about-compact {
                padding: 1rem;
            }

            .contact-compact {
                padding: 1rem;
            }

            .rating-compact {
                padding: 0.75rem;
                margin-top: 0.75rem;
            }

            .rating-num-compact {
                font-size: 2rem;
            }

            .cat_name {
                font-size: 1rem;
                margin-bottom: 0.5rem;
                padding-bottom: 5px;
            }

            .product_data {
                margin: 1rem 0;
            }

            .announce-compact {
                padding: 0.5rem 0;
            }

            .announce-wrap {
                padding: 0 1rem;
            }

            .announce-wrap i {
                font-size: 16px !important;
            }

            .announce-wrap div {
                font-size: 12px !important;
            }
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
    <!-- Compact Header -->
    <header class="compact-header">
        <div class="header-wrap">
            <div class="logo-compact">
                <img loading="lazy" class="logo-img-compact"
                    src="{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}" alt="{{ $store['name'] }}">
                <div class="logo-text-compact">
                    <h1>{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                    <p>{{ $store['meta_title'] }}</p>
                </div>
            </div>
            <button class="mobile-menu-toggle" onclick="document.querySelector('.nav-compact').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="nav-compact">
                <a href="#services">Services</a>
                <a href="#reviews">Reviews</a>
                <a href="#contact">Contact</a>
                <a href="#about">About</a>
                <a href="{{ route('store.gallery', [$store['slug']]) }}" class="btn-red"><span>Gallery</span></a>
            </nav>
        </div>
    </header>

    <!-- Compact Hero -->
    <div class="compact-hero">
        <div class="hero-compact-wrap">
            <div class="hero-text-compact">
                <span class="hero-tag-compact">⚡ PREMIUM QUALITY</span>
                <h1 class="hero-title-compact">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                <p class="hero-desc-compact">{{ $store['meta_title'] }}</p>
                <div class="hero-actions-compact">
                    <a href="#services" class="btn-white-red">Explore Now</a>
                    <a href="#contact" class="btn-outline-red">Contact Us</a>
                </div>
            </div>

            <div class="hero-img-compact">
                <img loading="lazy" class="hero-image"
                    src="{{ asset('storage/app/public/store/cover/') . '/' . $store['cover_photo'] }}" alt="Cover">

                <div class="stats-compact">
                    @php $store_rating = number_format($store->average_rating, 1); @endphp
                    <div class="stat-compact">
                        <div class="stat-val">{{ $store_rating }}</div>
                        <div class="stat-lbl">Rating</div>
                    </div>
                    <div class="stat-compact">
                        <div class="stat-val">{{ $store->rating_count }}+</div>
                        <div class="stat-lbl">Reviews</div>
                    </div>
                    @if ($inventoryProductCount > 0)
                        <div class="stat-compact">
                            <div class="stat-val">{{ $inventoryProductCount }}+</div>
                            <div class="stat-lbl">Products</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="breadcrumb-compact">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" style="color: var(--red-primary);">Home</a></li>
                <li class="breadcrumb-item active">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</li>
            </ol>
        </nav>
    </div>

    <!-- Announcement -->
    @if ($store->announcement)
        <div class="announce-compact">
            <div class="announce-wrap">
                <i class="fas fa-bullhorn" style="font-size: 24px;"></i>
                <div style="font-size: 14px; font-weight: 700;">{{ $store->announcement_message }}</div>
            </div>
        </div>
    @endif

    <!-- Info Section -->
    <div class="section-compact" style="background: white;">
        <div class="section-wrap">
            <div class="split-info-compact">
                <div class="about-compact slide-in-right">
                    <h2>About Our Business</h2>
                    @php
                        $description = $store['meta_description'];
                        $short = Str::limit($description, 350);
                    @endphp
                    <div style="font-size: 14px; color: var(--gray); line-height: 1.8;" id="text-{{ $store['id'] }}">
                        {!! $short !!}
                        @if (strlen($description) > 350)
                            <span id="dots-{{ $store['id'] }}"></span>
                            <span id="more-{{ $store['id'] }}" class="d-none">{!! substr($description, 350) !!}</span>
                            <a class="cursor-pointer" style="color: var(--red-primary); font-weight: 800;"
                                onclick="toggleReadMore({{ $store['id'] }})" id="btn-{{ $store['id'] }}">Read more →</a>
                        @endif
                    </div>
                </div>

                <div class="contact-compact slide-in-right" style="transition-delay: 0.2s;">
                    <h3>Quick Contact</h3>

                    <div class="contact-row-compact">
                        <div class="contact-icon-compact">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <h4
                                style="font-size: 10px; text-transform: uppercase; color: var(--red-light); margin-bottom: 0.5rem; letter-spacing: 1px;">
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

                    <div class="contact-row-compact">
                        <div class="contact-icon-compact">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h4
                                style="font-size: 10px; text-transform: uppercase; color: var(--red-light); margin-bottom: 0.5rem; letter-spacing: 1px;">
                                Email</h4>
                            <p style="font-size: 14px; margin: 0;">
                                <a href="mailto:{{ $store['email'] }}"
                                    style="color: white; text-decoration: none;">{{ $data['store_config']?->webpage_email ?? $store->email }}</a>
                            </p>
                        </div>
                    </div>

                    <div class="contact-row-compact">
                        <div class="contact-icon-compact">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h4
                                style="font-size: 10px; text-transform: uppercase; color: var(--red-light); margin-bottom: 0.5rem; letter-spacing: 1px;">
                                Address</h4>
                            <p style="font-size: 14px; margin: 0;">{{ $store['address'] }}</p>
                        </div>
                    </div>

                    <div class="rating-compact">
                        @php $store_rating = number_format($store->average_rating, 1); @endphp
                        <div class="rating-num-compact">{{ $store_rating }}</div>
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
                        <div style="font-size: 12px; opacity: 0.95;">{{ $store->rating_count }} Reviews</div>
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
        <div style="max-width: 1600px; margin: 2rem auto; padding: 0 2rem;">
            <div class="owl-carousel banner-carousel bounce-in">
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
    <div class="section-compact" id="services" style="background: var(--light);">
        <div class="section-wrap">
            <div class="section-head-compact">
                <span class="section-tag-compact">OUR OFFERINGS</span>
                <h2 class="section-title-compact">Products & Services</h2>
            </div>

            @foreach ($productdata as $key => $cat)
                <div class="product_data">
                    <h3 class="cat_name" style="">
                        {{ $cat->name }}</h3>

                    <div class="products-compact">
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
                            <div class="pr_{{ $pro->id }} card-compact slide-in-right"
                                style="transition-delay: {{ $index * 0.05 }}s;">
                                <div class="card-img-compact">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <img loading="lazy"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                            alt="{{ $pro->name }}">
                                    </a>

                                    @if ($module == 5 && $store->delivery_time)
                                        <div class="time-compact">
                                            <i class="fas fa-bolt" style="color: var(--red-primary);"></i>
                                            {{ strtoupper($store->delivery_time) }}
                                        </div>
                                    @endif

                                    @if ($pro->discount > 0)
                                        <div class="badge-compact">
                                            -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                        </div>
                                    @endif

                                    <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                        class="prHeart_{{ $pro->id }} heart-compact">
                                        <i
                                            class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                    </div>
                                </div>

                                <div class="card-body-compact">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <h4 class="card-title-compact" title="{{ ucfirst($pro->name) }}">
                                            {{ ucfirst($pro->name) }}
                                        </h4>
                                    </a>

                                    @if ($module == 5)
                                        <p
                                            style="font-size: 11px; color: var(--gray); margin-bottom: 0.5rem; min-height: 16px;">
                                            {{ !empty($variations) ? $variations[0]->type : '' }}
                                        </p>

                                        <div class="card-price-compact">
                                            <div class="price-now-compact">{{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0)
                                                <div class="price-was-compact">{{ _price($mrp) }}</div>
                                            @endif
                                        </div>

                                        <div class="cartSec_{{ $pro->id }}">
                                            @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                            @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                    class="btn-card-compact btn-remove-compact">
                                                    <i class="fa fa-times"></i> Remove
                                                </button>
                                            @else
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                    class="btn-card-compact">
                                                    <i class="fa fa-plus"></i> Add
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        @if ($pro->item_type == 'product')
                                            <div class="card-price-compact">
                                                <div class="price-now-compact">{{ _price($selling_price) }}</div>
                                                @if ($pro->discount > 0 || $mrp > $selling_price)
                                                    <div class="price-was-compact">{{ _price($mrp) }}</div>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($pro->item_type == 'product' && ($pro->stock ?? 1) <= 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif (($data['store_config']->lead_available ?? 1) == 0)
                                            <button disabled class="btn-card-compact" style="opacity:0.5;cursor:not-allowed;">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire' }}
                                            </button>
                                            <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                        @elseif (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                class="btn-card-compact">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire' }}
                                            </button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="btn-card-compact">
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
                <div class="product_data">
                    <h3 class="cat_name" style="">
                        {{ $cat->name }}</h3>

                    <div class="products-compact">
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
                            <div class="pr_{{ $pro->id }} card-compact slide-in-right"
                                style="transition-delay: {{ $index * 0.05 }}s;">
                                <div class="card-img-compact">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <img loading="lazy"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                            alt="{{ $pro->name }}">
                                    </a>

                                    @if ($module == 5 && $store->delivery_time)
                                        <div class="time-compact">
                                            <i class="fas fa-bolt" style="color: var(--red-primary);"></i>
                                            {{ strtoupper($store->delivery_time) }}
                                        </div>
                                    @endif

                                    @if ($pro->discount > 0)
                                        <div class="badge-compact">
                                            -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                        </div>
                                    @endif

                                    <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                        class="prHeart_{{ $pro->id }} heart-compact">
                                        <i
                                            class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                    </div>
                                </div>

                                <div class="card-body-compact">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <h4 class="card-title-compact" title="{{ ucfirst($pro->name) }}">
                                            {{ ucfirst($pro->name) }}
                                        </h4>
                                    </a>

                                    @if ($module == 5)
                                        <p
                                            style="font-size: 11px; color: var(--gray); margin-bottom: 0.5rem; min-height: 16px;">
                                            {{ !empty($variations) ? $variations[0]->type : '' }}
                                        </p>

                                        <div class="card-price-compact">
                                            <div class="price-now-compact">{{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0)
                                                <div class="price-was-compact">{{ _price($mrp) }}</div>
                                            @endif
                                        </div>

                                        <div class="cartSec_{{ $pro->id }}">
                                            @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                            @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                    class="btn-card-compact btn-remove-compact">
                                                    <i class="fa fa-times"></i> Remove
                                                </button>
                                            @else
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                    class="btn-card-compact">
                                                    <i class="fa fa-plus"></i> Add
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        @if ($pro->item_type == 'product')
                                            <div class="card-price-compact">
                                                <div class="price-now-compact">{{ _price($selling_price) }}</div>
                                                @if ($pro->discount > 0 || $mrp > $selling_price)
                                                    <div class="price-was-compact">{{ _price($mrp) }}</div>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($pro->item_type == 'product' && ($pro->stock ?? 1) <= 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif (($data['store_config']->lead_available ?? 1) == 0)
                                            <button disabled class="btn-card-compact" style="opacity:0.5;cursor:not-allowed;">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire' }}
                                            </button>
                                            <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                        @elseif (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                class="btn-card-compact">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire' }}
                                            </button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="btn-card-compact">
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
                <div style="text-align: center; padding: 4rem 0; color: var(--gray);">
                    <i class="fas fa-box-open" style="font-size: 4rem; margin-bottom: 1.5rem; opacity: 0.2;"></i>
                    <p style="font-size: 1.125rem; font-weight: 600;">No products available</p>
                </div>
            @endif

        </div>
    </div>

    <!-- Minimized Gallery -->
    @if (count($store->galleries))
        <div class="section-compact" style="background: white;">
            <div class="section-wrap">
                <div class="section-head-compact">
                    <span class="section-tag-compact">PORTFOLIO</span>
                    <h2 class="section-title-compact">Gallery</h2>
                </div>

                <div class="gallery-minimized">
                    @foreach ($data['galleries'] as $index => $value)
                        <a target="_blank" href="{{ asset('storage/app/public/store/gallery') }}/{{ $value->image }}"
                            class="gallery-mini-item pop-in lightgallery-item"
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
                            <div class="gallery-overlay-mini">
                                <i class="fas fa-search-plus gallery-icon-mini"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Reviews -->
    @if (count($data['reviews']) && $module == 6)
        <div class="section-compact" id="reviews" style="background: var(--light);">
            <div class="section-wrap">
                <div class="section-head-compact">
                    <span class="section-tag-compact">TESTIMONIALS</span>
                    <h2 class="section-title-compact">Customer Reviews</h2>
                </div>

                @foreach ($data['reviews'] as $index => $rev)
                    <div class="slide-in-right"
                        style="transition-delay: {{ $index * 0.1 }}s; background: white; border-radius: 12px; padding: 2rem; margin-bottom: 1.5rem; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border-left: 4px solid var(--red-primary);">
                        <div style="display: flex; gap: 1.25rem; margin-bottom: 1.25rem;">
                            <img loading="lazy"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/') . '/' . $rev->profile_image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 3px solid var(--red-primary);"
                                alt="{{ $rev->f_name }}">
                            <div style="flex: 1;">
                                <div
                                    style="font-size: 1.125rem; font-weight: 800; color: var(--dark); margin-bottom: 0.5rem;">
                                    {{ $rev->f_name . ' ' . $rev->l_name }}</div>
                                <div style="font-size: 11px; color: var(--gray); margin-bottom: 0.5rem;">
                                    {{ _formatted_datetime($rev->created_at) }}</div>
                                <div style="display: flex; gap: 0.25rem;">
                                    @for ($i = 1; $i < 6; $i++)
                                        <i class="fas fa-star"
                                            style="font-size: 14px; color: {{ $rev->rating >= $i ? '#fbbf24' : '#e0e0e0' }};"></i>
                                    @endfor
                                </div>
                            </div>
                        </div>
                        <p style="font-size: 14px; color: var(--gray); line-height: 1.8;">{{ $rev->comment }}</p>

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
                                style="margin-top: 1.5rem; padding: 1.5rem; background: var(--light); border-left: 3px solid var(--red-primary); border-radius: 8px;">
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                    <img loading="lazy"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store['logo'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                        style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"
                                        alt="{{ $store->name }}">
                                    <div>
                                        <div style="font-weight: 800; font-size: 13px; color: var(--dark);">Store Response
                                        </div>
                                        <div style="font-size: 11px; color: var(--gray);">
                                            {{ _formatted_datetime($rev->replied_at) }}</div>
                                    </div>
                                </div>
                                <p style="font-size: 13px; color: var(--gray); line-height: 1.7; margin: 0;">
                                    {{ $rev->reply }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($data['review_count'] > 2)
                    <div style="text-align: center; margin-top: 2.5rem;">
                        <a href="{{ route('store.reviews', [$store->slug]) }}" class="btn-white-red"
                            style="display: inline-block;">
                            View All Reviews <i class="fas fa-arrow-right" style="margin-left: 0.75rem;"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Contact -->
    <div class="section-compact" id="contact" style="background: white;">
        <div class="section-wrap">
            <div class="section-head-compact">
                <span class="section-tag-compact">GET IN TOUCH</span>
                <h2 class="section-title-compact">Contact Us</h2>
            </div>

            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 2.5rem;">
                @php $contacts = [['icon' => 'map', 'title' => 'Location', 'value' => $store['address']], ['icon' => 'secured-letter', 'title' => 'Email', 'value' => $store['email'], 'link' => 'mailto:'], ['icon' => 'phone', 'title' => 'Phone', 'value' => $store['phone'], 'link' => 'tel:'], ['icon' => 'marker', 'title' => 'Directions', 'value' => 'View Map', 'modal' => true]]; @endphp

                @foreach ($contacts as $index => $contact)
                    <div class="bounce-in"
                        style="transition-delay: {{ $index * 0.1 }}s; background: var(--red-bg); padding: 2rem; border-radius: 12px; text-align: center; transition: all 0.3s; cursor: pointer;"
                        onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 12px 35px rgba(220,38,38,0.2)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <div
                            style="width: 60px; height: 60px; background: var(--red-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem;">
                            <img loading="lazy" src="https://img.icons8.com/ios-filled/50/{{ $contact['icon'] }}.png"
                                alt="{{ $contact['title'] }}"
                                style="width: 30px; height: 30px; filter: brightness(0) invert(1);">
                        </div>
                        <div style="font-size: 1.125rem; font-weight: 800; color: var(--dark); margin-bottom: 0.5rem;">
                            {{ $contact['title'] }}</div>
                        <div style="font-size: 13px; color: var(--gray);">
                            @if (isset($contact['link']))
                                <a href="{{ $contact['link'] }}{{ $contact['value'] }}"
                                    style="color: var(--red-primary); text-decoration: none; font-weight: 600;">{{ $contact['value'] }}</a>
                            @elseif (isset($contact['modal']))
                                <a class="cursor-pointer" data-bs-toggle="modal" data-bs-target="#exampleModal"
                                    style="color: var(--red-primary); text-decoration: none; font-weight: 600;">{{ $contact['value'] }}</a>
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
    <div class="section-compact" id="about" style="background: var(--light);">
        <div class="section-wrap">
            <div class="section-head-compact">
                <span class="section-tag-compact">ABOUT</span>
                <h2 class="section-title-compact">Our Story</h2>
            </div>

            <div class="slide-in-right"
                style="background: white; border-radius: 12px; padding: 2.5rem; box-shadow: 0 2px 12px rgba(0,0,0,0.08); line-height: 1.9; font-size: 14px; color: var(--gray); border-left: 4px solid var(--red-primary);">
                {!! $data['store_config']->about_us ?? 'Information coming soon.' !!}
            </div>
        </div>
    </div>

    <!-- Map Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 12px; border: none;">
                <div class="modal-header"
                    style="background: var(--red-primary); color: white; border-radius: 12px 12px 0 0;">
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
        // Aggressive Scroll Animation with Intersection Observer
        const animateOptions = {
            threshold: 0.05,
            rootMargin: '0px'
        };

        const animateObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, animateOptions);

        // Observe all animated elements
        document.querySelectorAll('.slide-in-right, .bounce-in, .pop-in').forEach(el => {
            animateObserver.observe(el);
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
        if (document.querySelector('.gallery-minimized')) {
            lightGallery(document.querySelector('.gallery-minimized'), {
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
