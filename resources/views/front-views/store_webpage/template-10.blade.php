@extends('front-views.layout')

@section('title', $store['meta_title'] ?? ($data['store_config']?->webpage_name ?? $store['name']))

@section('meta_keywords', $keywords)
@section('meta_description', $store['meta_description'])


@push('css_or_js') 
    <meta name="csrf-token" content="{{ csrf_token() }}"> 
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --accent-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --dark: #2d3748;
            --light: #f7fafc;
            --text: #4a5568;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #fff;
            color: var(--dark);
            overflow-x: hidden;
        }

        /* Scroll Animation Base */
        .animate-on-scroll {
            opacity: 0;
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .animate-on-scroll.animated {
            opacity: 1;
        }

        .slide-left {
            transform: translateX(-100px);
        }

        .slide-left.animated {
            transform: translateX(0);
        }

        .slide-right {
            transform: translateX(100px);
        }

        .slide-right.animated {
            transform: translateX(0);
        }

        .slide-up {
            transform: translateY(60px);
        }

        .slide-up.animated {
            transform: translateY(0);
        }

        .scale-in {
            transform: scale(0.9);
        }

        .scale-in.animated {
            transform: scale(1);
        }

        .fade-in {
            opacity: 0;
        }

        .fade-in.animated {
            opacity: 1;
        }

        /* Stagger animation */
        .stagger-item {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stagger-item.animated {
            opacity: 1;
            transform: translateY(0);
        }

        /* Animated Header */
        .animated-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.05);
            transform: translateY(0);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .animated-header.hide {
            transform: translateY(-100%);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.25rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-animated {
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideInLeft 0.6s ease-out;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .logo-img-animated {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .logo-img-animated:hover {
            transform: rotate(5deg) scale(1.05);
        }

        .logo-text-animated h1 {
            font-size: 1.375rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-text-animated p {
            font-size: 11px;
            color: var(--text);
        }

        .nav-animated {
            display: flex;
            gap: 2.5rem;
            align-items: center;
            animation: slideInRight 0.6s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .nav-animated a {
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            position: relative;
            overflow: hidden;
            transition: color 0.3s;
        }

        .nav-animated a::before {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--primary-gradient);
            transition: all 0.3s;
            transform: translateX(-50%);
        }

        .nav-animated a:hover::before {
            width: 100%;
        }

        .btn-animated {
            background: var(--primary-gradient);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-animated::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-animated:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-animated:hover::before {
            width: 300px;
            height: 300px;
        }

        /* Hero with Parallax */
        .hero-parallax {
            position: relative;
            min-height: 650px;
            overflow: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin-top: 73px;
        }

        .hero-bg-layer {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="50" height="50" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="2" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            animation: moveBackground 20s linear infinite;
        }

        @keyframes moveBackground {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(50px, 50px);
            }
        }

        .hero-content-wrapper {
            position: relative;
            max-width: 1400px;
            margin: 0 auto;
            padding: 5rem 3rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-text-animated {
            color: white;
        }

        .hero-badge-animated {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-title-animated {
            color: white;
            font-size: 4rem;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        .hero-desc-animated {
            font-size: 1.25rem;
            opacity: 0.95;
            margin-bottom: 2.5rem;
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }

        .hero-cta-animated {
            display: flex;
            gap: 1.25rem;
            animation: fadeInUp 0.8s ease-out 0.8s both;
        }

        .btn-primary-animated {
            background: white;
            color: #667eea;
            padding: 1.125rem 2.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn-primary-animated:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            color: #667eea;
        }

        .btn-secondary-animated {
            background: transparent;
            color: white;
            padding: 1.125rem 2.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            border: 2px solid white;
            transition: all 0.3s;
        }

        .btn-secondary-animated:hover {
            background: white;
            color: #667eea;
        }

        .hero-visual-animated {
            animation: fadeInRight 1s ease-out 0.5s both;
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .hero-img-animated {
            width: 100%;
            height: 450px;
            object-fit: cover;
            border-radius: 20px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s;
        }

        .hero-img-animated:hover {
            transform: scale(1.02) rotate(1deg);
        }

        .stats-animated {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
            margin-top: 2rem;
        }

        .stat-animated {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-animated:hover {
            transform: translateY(-5px);
        }

        .stat-value-animated {
            font-size: 2rem;
            font-weight: 900;
            line-height: 1;
            color: white;
        }

        .stat-label-animated {
            color: white;
            font-size: 11px;
            margin-top: 0.5rem;
            opacity: 0.9;
            text-transform: uppercase;
        }

        /* Breadcrumb */
        .breadcrumb-animated {
            background: var(--light);
            padding: 1rem 0;
        }

        .breadcrumb-animated .breadcrumb {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 3rem;
            background: transparent;
            font-size: 13px;
        }

        /* Sections */
        .section-animated {
            padding: 1rem 0;
            overflow: hidden;
        }

        .section-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 3rem;
        }

        .section-header-animated {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-tag-animated {
            display: inline-block;
            background: var(--primary-gradient);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
        }

        .section-title-animated {
            font-size: 3rem;
            font-weight: 900;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .section-desc-animated {
            font-size: 1.125rem;
            color: var(--text);
            max-width: 700px;
            margin: 0 auto;
        }

        /* Split Content */
        .split-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5rem;
            align-items: center;
            margin: 4rem 0;
        }

        .content-left {
            padding-right: 2rem;
        }

        .content-right {
            padding-left: 2rem;
        }

        .content-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 1.5rem;
        }

        .content-text {
            font-size: 1.0625rem;
            color: var(--text);
            line-height: 1.9;
        }

        .info-card-animated {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }

        .info-card-animated:hover {
            transform: translateY(-10px);
        }

        .contact-list-animated {
            list-style: none;
        }

        .contact-item-animated {
            display: flex;
            align-items: start;
            gap: 1.25rem;
            padding: 1.25rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .contact-item-animated:last-child {
            border: none;
        }

        .contact-icon-animated {
            width: 50px;
            height: 50px;
            background: var(--primary-gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            flex-shrink: 0;
        }

        /* Products Grid with Animation */
        .products-grid-animated {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2.5rem;
            margin-top: 3rem;
        }

        .product-card-animated {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .product-card-animated:hover {
            transform: translateY(-15px) rotate(1deg);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .product-img-wrapper {
            position: relative;
            width: 100%;
            height: 280px;
            overflow: hidden;
            background: var(--light);
        }

        .product-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s;
        }

        .product-card-animated:hover .product-img-wrapper img {
            transform: scale(1.15);
        }

        .badge-animated {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--secondary-gradient);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 800;
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

        .time-badge-animated {
            position: absolute;
            top: 15px;
            right: 15px;
            background: white;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .heart-animated {
            position: absolute;
            bottom: 15px;
            right: 15px;
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            transition: all 0.3s;
        }

        .heart-animated:hover {
            transform: scale(1.2) rotate(10deg);
        }

        .heart-animated i {
            font-size: 20px;
            transition: color 0.3s;
        }

        .text_red {
            color: #f06292;
        }

        .text_grey {
            color: #cbd5e1;
        }

        .product-details-animated {
            padding: 1.75rem;
        }

        .product-title-animated {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 54px;
        }

        .btn-product-animated {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 50px;
            background: var(--primary-gradient);
            color: white;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
        }

        .btn-product-animated:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        /* Announcement */
        .announcement-animated {
            background: var(--success-gradient);
            color: white;
            padding: 1.5rem 0;
            animation: slideInDown 0.6s ease-out;
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-100%);
            }

            to {
                transform: translateY(0);
            }
        }

        .announcement-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 3rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        /* Gallery with Hover Effects */
        .gallery-grid-animated {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(275px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .gallery-item-animated {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            aspect-ratio: 4/3;
            cursor: pointer;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        }

        .gallery-item-animated img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s;
        }

        .gallery-item-animated:hover img {
            transform: scale(1.2) rotate(2deg);
        }

        .gallery-overlay-animated {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(102, 126, 234, 0.9) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.4s;
        }

        .gallery-item-animated:hover .gallery-overlay-animated {
            opacity: 1;
        }

        .gallery-icon {
            font-size: 3rem;
            color: white;
            animation: zoomIn 0.4s ease-out;
        }
    .banner_parent{
                max-width: 1400px; 
                margin: 3rem auto; 
                padding: 0 3rem;
            }
        @keyframes zoomIn {
            from {
                transform: scale(0);
            }

            to {
                transform: scale(1);
            }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-content-wrapper {
                grid-template-columns: 1fr;
            }

            .split-content {
                grid-template-columns: 1fr;
                gap: 3rem;
            }

            .nav-animated {
                display: none;
            }

            .products-grid-animated {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .hero-title-animated {
                font-size: 2.5rem;
            }

            .section-title-animated {
                font-size: 2rem;
            }

            .products-grid-animated {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
            }

            .gallery-grid-animated {
                grid-template-columns: repeat(2, 1fr);
            }

            .header-container {
                padding: 0.25rem 1rem;
            }

            .hero-content-wrapper {
                padding: 1rem 1rem;
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }

            .hero-desc-animated {
                font-size: 13px;
                margin-bottom: 1.5rem;
            }

            .btn-secondary-animated,
            .btn-primary-animated {
                padding: 9px;
                border-radius: 24px;
                font-size: 14px;
            }

            .hero-parallax {
                margin-top: 63px;
            }

            .hero-title-animated {
                font-size: 1.5rem;
                white-space: break-spaces;
            }

            .hero-img-animated {
                height: auto;
            }

            .stat-animated {
                padding: 0.5rem;
            }

            .announcement-content {
                padding: 0 1rem;
            }

            .section-container {
                padding: 0 1rem;
            }

            .split-content {
                display: flex;
                flex-direction: column;
                margin: 1rem 0;
            }

            .content-left {
                padding-right: 0;
            }
            .content-right {
    padding-left: 0;
}


            .contact-list-animated{
                padding-left : 0;
            }
            .contact-icon-animated {
                    width: 30px;
    height: 30px;
            }
            .banner_parent{
                max-width: 1400px; 
                margin: 0; 
                padding: 0 1rem;
            }
            .product-img-wrapper 
{
    height: 96px;
}
.product-details-animated{
        padding: 0.75rem;
}
.product-title-animated {
        font-size: 14px;
}
.btn-product-animated {
    padding: 3px;
    font-size: 14px;
    border-radius: 15px;
    margin-top: 4px;
}
.heart-animated {
    bottom: 4px;
    right: 4px;
    width: 30px;
    height: 30px;}
        }

        /* Loading Animation */
        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }

            100% {
                background-position: 1000px 0;
            }
        }

        .rating-stars {
            position: relative;
            display: inline-block;
        }

        .stars-base i {
            color: #e0e0e0;
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

        .cursor-pointer {
            cursor: pointer;
        }

        .d-none {
            display: none;
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--dark);
            padding: 5px;
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .nav-animated {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(20px);
                flex-direction: column;
                padding: 15px 0;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                z-index: 10;
                animation: none;
            }

            .nav-animated.show {
                display: flex;
            }

            .nav-animated a {
                padding: 12px 25px;
                display: block;
            }

            .header-container {
                position: relative;
            }

            .logo-text-animated h1 {
                font-size: 1rem;
            }

            .logo-text-animated p {
                display: none;
            }
        }
        /* desktop only offset */
@media (min-width: 768px){

    .timeline-card.tl-right{
        margin-right: 5rem;
    }

    .timeline-card.tl-left{
        margin-left: 5rem;
    }

}

/* mobile & tablet – no side margin */
@media (max-width: 767.98px){

    .timeline-card.tl-right,
    .timeline-card.tl-left{
        margin-left: 0 !important;
        margin-right: 0 !important;
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
    <!-- Animated Header -->
    <header class="animated-header" id="mainHeader">
        <div class="header-container">
            <div class="logo-animated">
                <img loading="lazy" class="logo-img-animated"
                    src="{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}" alt="{{ $store['name'] }}">
                <div class="logo-text-animated">
                    <h1>{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                    <p>{{ $store['meta_title'] }}</p>
                </div>
            </div>
            <button class="mobile-menu-toggle" onclick="document.querySelector('.nav-animated').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="nav-animated">
                <a href="#services">Services</a>
                <a href="#reviews">Reviews</a>
                <a href="#contact">Contact</a>
                <a href="#about">About</a>
                <a href="{{ route('store.gallery', [$store['slug']]) }}" class="btn-animated">Gallery</a>
            </nav>
        </div>
    </header>

    <!-- Hero with Parallax -->
    <div class="hero-parallax">
        <div class="hero-bg-layer"></div>
        <div class="hero-content-wrapper">
            <div class="hero-text-animated">
                <span class="hero-badge-animated">🌟 Premium Quality</span>
                <h1 class="hero-title-animated">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                <p class="hero-desc-animated">{{ $store['meta_title'] }}</p>
                <div class="hero-cta-animated">
                    <a href="#services" class="btn-primary-animated">Explore Now</a>
                    <a href="#contact" class="btn-secondary-animated">Get in Touch</a>
                </div>
            </div>

            <div class="hero-visual-animated">
                <img loading="lazy" class="hero-img-animated"
                    src="{{ asset('storage/app/public/store/cover/') . '/' . $store['cover_photo'] }}" alt="Cover">

                <div class="stats-animated">
                    @php $store_rating = number_format($store->average_rating, 1); @endphp
                    <div class="stat-animated">
                        <div class="stat-value-animated">{{ $store_rating }}</div>
                        <div class="stat-label-animated">Rating</div>
                    </div>
                    <div class="stat-animated">
                        <div class="stat-value-animated">{{ $store->rating_count }}+</div>
                        <div class="stat-label-animated">Reviews</div>
                    </div>
                    <div class="stat-animated">
                        <div class="stat-value-animated">{{ count($productdata) }}+</div>
                        <div class="stat-label-animated">Products</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="breadcrumb-animated">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">›</li>
                <li class="breadcrumb-item active">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</li>
            </ol>
        </nav>
    </div>

    <!-- Announcement -->
    @if ($store->announcement)
        <div class="announcement-animated">
            <div class="announcement-content">
                <i class="fas fa-bullhorn" style="font-size: 28px;"></i>
                <div style="font-size: 15px; font-weight: 600;">{{ $store->announcement_message }}</div>
            </div>
        </div>
    @endif

    <!-- About Section with Slide Animations -->
    <div class="section-animated" style="background: white;">
        <div class="section-container">
            <div class="split-content">
                <div class="content-left animate-on-scroll slide-left">
                    <h2 class="content-title">Discover Our Story</h2>
                    @php
                        $description = $store['meta_description'];
                        $short = Str::limit($description, 400);
                    @endphp
                    <div class="content-text" id="text-{{ $store['id'] }}">
                        {!! $short !!}
                        @if (strlen($description) > 400)
                            <span id="dots-{{ $store['id'] }}"></span>
                            <span id="more-{{ $store['id'] }}" class="d-none">{!! substr($description, 400) !!}</span>
                            <a class="cursor-pointer" style="color: #667eea; font-weight: 700; text-decoration: underline;"
                                onclick="toggleReadMore({{ $store['id'] }})" id="btn-{{ $store['id'] }}">Read more →</a>
                        @endif
                    </div>
                </div>

                <div class="content-right animate-on-scroll slide-right">
                    <div class="info-card-animated">
                        <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 2rem;">Contact Information</h3>

                        <ul class="contact-list-animated">
                            <li class="contact-item-animated">
                                <div class="contact-icon-animated">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <h4
                                        style="font-size: 12px; text-transform: uppercase; color: var(--text); margin-bottom: 0.5rem;">
                                        Phone</h4>
                                    @php
                                        $phones = $data['store_config']?->webpage_phones;
                                        if ($phones) {
                                            $phones = json_decode($phones, true);
                                        } else {
                                            $phones = [];
                                        }
                                    @endphp
                                    <p style="font-size: 15px; font-weight: 600; margin: 0;">
                                        @include('front-views.store_webpage.partials.phone-actions', ['phones' => $phones])
                                    </p>
                                </div>
                            </li>
                            <li class="contact-item-animated">
                                <div class="contact-icon-animated">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <h4
                                        style="font-size: 12px; text-transform: uppercase; color: var(--text); margin-bottom: 0.5rem;">
                                        Email</h4>
                                    <p style="font-size: 15px; font-weight: 600; margin: 0;">
                                        <a href="mailto:{{ $store['email'] }}"
                                            style="color: var(--dark); text-decoration: none;">{{ $data['store_config']?->webpage_email ?? $store->email }}</a>
                                    </p>
                                </div>
                            </li>
                            <li class="contact-item-animated">
                                <div class="contact-icon-animated">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <h4
                                        style="font-size: 12px; text-transform: uppercase; color: var(--text); margin-bottom: 0.5rem;">
                                        Address</h4>
                                    <p style="font-size: 15px; font-weight: 600; margin: 0;">{{ $store['address'] }}</p>
                                </div>
                            </li>
                        </ul>

                        <div
                            style="margin-top: 2rem; padding: 1.5rem; background: var(--primary-gradient); border-radius: 15px; text-align: center; color: white;">
                            @php $store_rating = number_format($store->average_rating, 1); @endphp
                            <div style="font-size: 3.5rem; font-weight: 900; line-height: 1;">{{ $store_rating }}</div>
                            <div class="rating-stars" data-rating="{{ $store_rating }}" style="margin: 1rem 0;">
                                <div class="stars-base">
                                    <i class="fas fa-star" style="font-size: 20px;"></i>
                                    <i class="fas fa-star" style="font-size: 20px;"></i>
                                    <i class="fas fa-star" style="font-size: 20px;"></i>
                                    <i class="fas fa-star" style="font-size: 20px;"></i>
                                    <i class="fas fa-star" style="font-size: 20px;"></i>
                                </div>
                                <div class="stars-fill">
                                    <i class="fas fa-star" style="font-size: 20px;"></i>
                                    <i class="fas fa-star" style="font-size: 20px;"></i>
                                    <i class="fas fa-star" style="font-size: 20px;"></i>
                                    <i class="fas fa-star" style="font-size: 20px;"></i>
                                    <i class="fas fa-star" style="font-size: 20px;"></i>
                                </div>
                            </div>
                            <div style="font-size: 13px; opacity: 0.95;">{{ $store->rating_count }} Customer Reviews</div>
                        </div>

                        <div style="margin-top: 2rem;">
                            <div class="sharethis-inline-share-buttons"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Banners -->
    @if (count($data['banners']) > 0)
        <div style="" class="banner_parent">
            <div class="owl-carousel banner-carousel animate-on-scroll fade-in">
                @foreach ($data['banners'] as $value)
                    <a href="{{ $value->default_link ?? '#' }}" onclick="trackBannerClick({{ $value->id }})">
                        <img loading="lazy" src="{{ asset('storage/app/public/banner/') . '/' . $value->image }}"
                            alt="banner" style="border-radius: 20px; width: 100%;">
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Products Section -->
    <div class="section-animated" id="services" style="background: var(--light);">
        <div class="section-container">
            <div class="section-header-animated animate-on-scroll slide-up">
                <span class="section-tag-animated">Our Offerings</span>
                <h2 class="section-title-animated">Products & Services</h2>
                <p class="section-desc-animated">Discover our premium selection tailored for your needs</p>
            </div>

            @foreach ($productdata as $key => $cat)
                <div style="margin: 4rem 0;">
                    <h3 class="animate-on-scroll slide-left"
                        style="font-size: 2rem; font-weight: 800; margin-bottom: 2.5rem; color: var(--dark);">
                        {{ $cat->name }}</h3>

                    <div class="products-grid-animated">
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
                            <div class="pr_{{ $pro->id }} product-card-animated stagger-item"
                                style="transition-delay: {{ $index * 0.1 }}s;">
                                <div class="product-img-wrapper">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <img loading="lazy"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                            alt="{{ $pro->name }}">
                                    </a>

                                    @if ($module == 5 && $store->delivery_time)
                                        <div class="time-badge-animated">
                                            <i class="fas fa-bolt" style="color: #f59e0b;"></i>
                                            {{ strtoupper($store->delivery_time) }}
                                        </div>
                                    @endif

                                    @if ($pro->discount > 0)
                                        <div class="badge-animated">
                                            -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                        </div>
                                    @endif

                                    <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                        class="prHeart_{{ $pro->id }} heart-animated">
                                        <i
                                            class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                    </div>
                                </div>

                                <div class="product-details-animated">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <h4 class="product-title-animated" title="{{ ucfirst($pro->name) }}">
                                            {{ ucfirst($pro->name) }}
                                        </h4>
                                    </a>

                                    @if ($module == 5)
                                        <p
                                            style="font-size: 13px; color: var(--text); margin-bottom: 0.75rem; min-height: 20px;">
                                            {{ !empty($variations) ? $variations[0]->type : '' }}
                                        </p>
                                        @if (count($variations) > 1)
                                            <span
                                                style="display: inline-block; background: var(--light); padding: 0.3rem 0.85rem; border-radius: 12px; font-size: 11px; font-weight: 600;">+{{ count($variations) - 1 }}
                                                more</span>
                                        @endif

                                        <div style="display: flex; align-items: baseline; gap: 1rem; margin: 1.25rem 0;">
                                            <div style="font-size: 1.75rem; font-weight: 900; color: var(--dark);">
                                                {{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0)
                                                <div
                                                    style="font-size: 1rem; color: var(--text); text-decoration: line-through;">
                                                    {{ _price($mrp) }}</div>
                                            @endif
                                        </div>

                                        <div class="cartSec_{{ $pro->id }}">
                                            @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                            @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                    class="btn-product-animated"
                                                    style="background: var(--secondary-gradient);">
                                                    <i class="fa fa-times"></i> Remove
                                                </button>
                                            @else
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                    class="btn-product-animated">
                                                    <i class="fa fa-shopping-cart"></i> Add to Cart
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        @if ($pro->item_type == 'product')
                                            <div
                                                style="display: flex; align-items: baseline; gap: 1rem; margin: 1.25rem 0;">
                                                <div style="font-size: 1.75rem; font-weight: 900; color: var(--dark);">
                                                    {{ _price($selling_price) }}</div>
                                                @if ($pro->discount > 0 || $mrp > $selling_price)
                                                    <div
                                                        style="font-size: 1rem; color: var(--text); text-decoration: line-through;">
                                                        {{ _price($mrp) }}</div>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($pro->item_type == 'product' && ($pro->stock ?? 1) <= 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif (($data['store_config']->lead_available ?? 1) == 0)
                                            <button disabled class="btn-product-animated" style="background: var(--success-gradient);opacity:0.5;cursor:not-allowed;">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
                                            </button>
                                            <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                        @elseif (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                class="btn-product-animated" style="background: var(--success-gradient);">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
                                            </button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="btn-product-animated" style="background: var(--success-gradient);">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
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
                <div style="margin: 4rem 0;">
                    <h3 class="animate-on-scroll slide-left"
                        style="font-size: 2rem; font-weight: 800; margin-bottom: 2.5rem; color: var(--dark);">
                        {{ $cat->name }}</h3>

                    <div class="products-grid-animated">
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
                            <div class="pr_{{ $pro->id }} product-card-animated stagger-item"
                                style="transition-delay: {{ $index * 0.1 }}s;">
                                <div class="product-img-wrapper">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <img loading="lazy"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                            alt="{{ $pro->name }}">
                                    </a>

                                    @if ($module == 5 && $store->delivery_time)
                                        <div class="time-badge-animated">
                                            <i class="fas fa-bolt" style="color: #f59e0b;"></i>
                                            {{ strtoupper($store->delivery_time) }}
                                        </div>
                                    @endif

                                    @if ($pro->discount > 0)
                                        <div class="badge-animated">
                                            -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                        </div>
                                    @endif

                                    <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                        class="prHeart_{{ $pro->id }} heart-animated">
                                        <i
                                            class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                    </div>
                                </div>

                                <div class="product-details-animated">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <h4 class="product-title-animated" title="{{ ucfirst($pro->name) }}">
                                            {{ ucfirst($pro->name) }}
                                        </h4>
                                    </a>

                                    @if ($module == 5)
                                        <p
                                            style="font-size: 13px; color: var(--text); margin-bottom: 0.75rem; min-height: 20px;">
                                            {{ !empty($variations) ? $variations[0]->type : '' }}
                                        </p>
                                        @if (count($variations) > 1)
                                            <span
                                                style="display: inline-block; background: var(--light); padding: 0.3rem 0.85rem; border-radius: 12px; font-size: 11px; font-weight: 600;">+{{ count($variations) - 1 }}
                                                more</span>
                                        @endif

                                        <div style="display: flex; align-items: baseline; gap: 1rem; margin: 1.25rem 0;">
                                            <div style="font-size: 1.75rem; font-weight: 900; color: var(--dark);">
                                                {{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0)
                                                <div
                                                    style="font-size: 1rem; color: var(--text); text-decoration: line-through;">
                                                    {{ _price($mrp) }}</div>
                                            @endif
                                        </div>

                                        <div class="cartSec_{{ $pro->id }}">
                                            @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                            @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                    class="btn-product-animated"
                                                    style="background: var(--secondary-gradient);">
                                                    <i class="fa fa-times"></i> Remove
                                                </button>
                                            @else
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                    class="btn-product-animated">
                                                    <i class="fa fa-shopping-cart"></i> Add to Cart
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        @if ($pro->item_type == 'product')
                                            <div
                                                style="display: flex; align-items: baseline; gap: 1rem; margin: 1.25rem 0;">
                                                <div style="font-size: 1.75rem; font-weight: 900; color: var(--dark);">
                                                    {{ _price($selling_price) }}</div>
                                                @if ($pro->discount > 0 || $mrp > $selling_price)
                                                    <div
                                                        style="font-size: 1rem; color: var(--text); text-decoration: line-through;">
                                                        {{ _price($mrp) }}</div>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($pro->item_type == 'product' && ($pro->stock ?? 1) <= 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif (($data['store_config']->lead_available ?? 1) == 0)
                                            <button disabled class="btn-product-animated" style="background: var(--success-gradient);opacity:0.5;cursor:not-allowed;">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
                                            </button>
                                            <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                        @elseif (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                class="btn-product-animated" style="background: var(--success-gradient);">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
                                            </button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="btn-product-animated" style="background: var(--success-gradient);">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
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
                <div style="text-align: center; padding: 5rem 0; color: var(--text);">
                    <i class="fas fa-box-open" style="font-size: 5rem; margin-bottom: 2rem; opacity: 0.2;"></i>
                    <p style="font-size: 1.25rem; font-weight: 600;">No products available</p>
                </div>
            @endif

        </div>
    </div>

    <!-- Gallery -->
    @if (count($store->galleries))
        <div class="section-animated" style="background: white;">
            <div class="section-container">
                <div class="section-header-animated animate-on-scroll slide-up">
                    <span class="section-tag-animated">Portfolio</span>
                    <h2 class="section-title-animated">Our Gallery</h2>
                    <p class="section-desc-animated">A visual journey through our work</p>
                </div>

                <div class="gallery-grid-animated">
                    @foreach ($data['galleries'] as $index => $value)
                        <a target="_blank" href="{{ asset('storage/app/public/store/gallery') }}/{{ $value->image }}"
                            class="gallery-item-animated stagger-item lightgallery-item"
                            style="transition-delay: {{ $index * 0.1 }}s;">
                            <img loading="lazy"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                    $value['image'] ?? '',
                                    asset('storage/app/public/store/gallery') . '/' . $value['image'] ?? '',
                                    asset('public/assets/admin/img/160x160/img1.jpg'),
                                    'store/gallery/',
                                ) }}"
                                alt="Gallery {{ $index + 1 }}">
                            <div class="gallery-overlay-animated">
                                <i class="fas fa-search-plus gallery-icon"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Reviews -->
    @if (count($data['reviews']) && $module == 6)
        <div class="section-animated" id="reviews" style="background: var(--light);">
            <div class="section-container">
                <div class="section-header-animated animate-on-scroll slide-up">
                    <span class="section-tag-animated">Testimonials</span>
                    <h2 class="section-title-animated">Customer Reviews</h2>
                    <p class="section-desc-animated">What our valued customers say about us</p>
                </div>

                @foreach ($data['reviews'] as $index => $rev)
                    <div class="animate-on-scroll"
                       style="background: white; border-radius: 20px; padding: 19px; margin-bottom: 2rem; box-shadow: 0 5px 25px rgba(0,0,0,0.08);"
class="timeline-card {{ $index % 2 === 0 ? 'tl-right' : 'tl-left' }}"
                        class="{{ $index % 2 === 0 ? 'slide-left' : 'slide-right' }}">
                        <div style="display: flex; gap: 1.5rem; margin-bottom: 1.5rem;">
                            <img loading="lazy"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/') . '/' . $rev->profile_image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 3px solid #667eea;"
                                alt="{{ $rev->f_name }}">
                            <div style="flex: 1;">
                                <div
                                    style="font-size: 1.375rem; font-weight: 800; color: var(--dark); margin-bottom: 0.5rem;">
                                    {{ $rev->f_name . ' ' . $rev->l_name }}</div>
                                <div style="font-size: 12px; color: var(--text); margin-bottom: 0.75rem;">
                                    {{ _formatted_datetime($rev->created_at) }}</div>
                                <div style="display: flex; gap: 0.25rem;">
                                    @for ($i = 1; $i < 6; $i++)
                                        <i class="fas fa-star"
                                            style="font-size: 16px; color: {{ $rev->rating >= $i ? '#fbbf24' : '#e0e0e0' }};"></i>
                                    @endfor
                                </div>
                            </div>
                        </div>
                        <p style="font-size: 15px; color: var(--text); line-height: 1.9;">{{ $rev->comment }}</p>

                        @if ($rev->attachment)
                            @php $attachments = (array) $rev->attachment; @endphp
                            @if (!empty($attachments))
                                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                                    @foreach ($attachments as $img)
                                        <a target="_blank"
                                            href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}">
                                            <img loading="lazy"
                                                style="width: 100px; height: 100px; border-radius: 12px; object-fit: cover; border: 2px solid #e2e8f0; transition: transform 0.3s; cursor: pointer;"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                alt="review" onmouseover="this.style.transform='scale(1.05)'"
                                                onmouseout="this.style.transform='scale(1)'">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        @if ($rev->reply)
                            <div
                                style="margin-top: 2rem; padding: 1.75rem; background: var(--light); border-left: 4px solid #667eea; border-radius: 12px;">
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                    <img loading="lazy"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store['logo'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                        style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;"
                                        alt="{{ $store->name }}">
                                    <div>
                                        <div style="font-weight: 800; font-size: 15px; color: var(--dark);">Store Response
                                        </div>
                                        <div style="font-size: 12px; color: var(--text);">
                                            {{ _formatted_datetime($rev->replied_at) }}</div>
                                    </div>
                                </div>
                                <p style="font-size: 14px; color: var(--text); line-height: 1.8; margin: 0;">
                                    {{ $rev->reply }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($data['review_count'] > 2)
                    <div style="text-align: center; margin-top: 3rem;" class="animate-on-scroll fade-in">
                        <a href="{{ route('store.reviews', [$store->slug]) }}" class="btn-primary-animated"
                            style="display: inline-block;">
                            View All Reviews <i class="fas fa-arrow-right" style="margin-left: 1rem;"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Contact -->
    <div class="section-animated" id="contact" style="background: white;">
        <div class="section-container">
            <div class="section-header-animated animate-on-scroll slide-up">
                <span class="section-tag-animated">Get In Touch</span>
                <h2 class="section-title-animated">Contact Us</h2>
                <p class="section-desc-animated">We're here to help and answer any question you might have</p>
            </div>

            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2.5rem; margin-top: 3rem;">
                @php $contacts = [['icon' => 'map', 'title' => 'Our Location', 'value' => $store['address'], 'link' => null], ['icon' => 'secured-letter', 'title' => 'Email Address', 'value' => $store['email'], 'link' => 'mailto:' . $store['email']], ['icon' => 'phone', 'title' => 'Phone Number', 'value' => $store['phone'], 'link' => 'tel:' . $store['phone']], ['icon' => 'marker', 'title' => 'Get Directions', 'value' => 'View on Map', 'link' => '#', 'modal' => true]]; @endphp

                @foreach ($contacts as $index => $contact)
                    <div class="stagger-item"
                        style="transition-delay: {{ $index * 0.15 }}s; background: var(--light); padding: 2.5rem; border-radius: 20px; text-align: center; transition: all 0.3s; cursor: pointer;"
                        onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 15px 50px rgba(0,0,0,0.1)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <div
                            style="width: 70px; height: 70px; background: var(--primary-gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                            <img loading="lazy" src="https://img.icons8.com/ios-filled/50/{{ $contact['icon'] }}.png"
                                alt="{{ $contact['title'] }}"
                                style="width: 35px; height: 35px; filter: brightness(0) invert(1);">
                        </div>
                        <div style="font-size: 1.25rem; font-weight: 800; color: var(--dark); margin-bottom: 0.75rem;">
                            {{ $contact['title'] }}</div>
                        <div style="font-size: 14px; color: var(--text);">
                            @if ($contact['link'])
                                @if (isset($contact['modal']))
                                    <a class="cursor-pointer" data-bs-toggle="modal" data-bs-target="#exampleModal"
                                        style="color: #667eea; text-decoration: none; font-weight: 600;">{{ $contact['value'] }}</a>
                                @else
                                    <a href="{{ $contact['link'] }}"
                                        style="color: #667eea; text-decoration: none; font-weight: 600;">{{ $contact['value'] }}</a>
                                @endif
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
    <div class="section-animated" id="about" style="background: var(--light);">
        <div class="section-container">
            <div class="section-header-animated animate-on-scroll slide-up">
                <span class="section-tag-animated">About</span>
                <h2 class="section-title-animated">Our Story</h2>
                <p class="section-desc-animated">Learn more about who we are and what we do</p>
            </div>

            <div class="animate-on-scroll fade-in"
                style="background: white; border-radius: 20px; padding: 1.5rem; box-shadow: 0 5px 25px rgba(0,0,0,0.08); line-height: 2; font-size: 15px; color: var(--text);">
                {!! $data['store_config']->about_us ?? 'Information coming soon.' !!}
            </div>
        </div>
    </div>

    <!-- Map Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 20px; border: none;">
                <div class="modal-header"
                    style="background: var(--primary-gradient); color: white; border-radius: 20px 20px 0 0;">
                    <h5 class="modal-title">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="map" style="height: 450px; border-radius: 12px;"></div>
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
            $.post("{{ route('track.banner.click') }}", {
                banner_id: bannerId,
                _token: '{{ csrf_token() }}'
            });
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/lightgallery.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/plugins/video/lg-video.umd.min.js"></script>

    <script>
        // Optimized Scroll Animation with Intersection Observer
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    // Optionally unobserve after animation
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe all animated elements
        document.querySelectorAll('.animate-on-scroll, .stagger-item').forEach(el => {
            observer.observe(el);
        });

        // Hide/Show Header on Scroll
        let lastScroll = 0;
        const header = document.getElementById('mainHeader');

        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;

            if (currentScroll > 100) {
                if (currentScroll > lastScroll) {
                    header.classList.add('hide');
                } else {
                    header.classList.remove('hide');
                }
            }

            lastScroll = currentScroll;
        }, {
            passive: true
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
        if (document.querySelector('.gallery-grid-animated')) {
            lightGallery(document.querySelector('.gallery-grid-animated'), {
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
                    const target = document.querySelector(href);
                    const headerHeight = document.querySelector('.animated-header').offsetHeight;
                    const targetPosition = target.offsetTop - headerHeight;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
@endpush
