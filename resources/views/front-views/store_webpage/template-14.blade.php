@extends('front-views.layout')

@section('title', $store['meta_title'] ?? ($data['store_config']?->webpage_name ?? $store['name']))

@section('meta_keywords', $keywords)
@section('meta_description', $store['meta_description'])


@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --t14-primary: #1e40af;
            --t14-primary-dark: #1e3a8a;
            --t14-primary-light: #93c5fd;
            --t14-primary-bg: #eff6ff;
            --t14-gold: #f59e0b;
            --t14-gold-dark: #d97706;
            --t14-gold-light: #fde68a;
            --t14-dark: #0f172a;
            --t14-gray: #64748b;
            --t14-light: #f8fafc;
            --t14-chalk: #1e293b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #fff;
            color: var(--t14-dark);
            overflow-x: hidden;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--t14-light); }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--t14-primary), var(--t14-gold));
            border-radius: 10px;
        }

        /* ===== SCROLL ANIMATIONS ===== */
        .t14-from-left {
            opacity: 0;
            transform: translateX(-100px) rotate(-2deg);
            transition: all 0.9s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .t14-from-left.active {
            opacity: 1;
            transform: translateX(0) rotate(0);
        }

        .t14-from-right {
            opacity: 0;
            transform: translateX(100px) rotate(2deg);
            transition: all 0.9s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .t14-from-right.active {
            opacity: 1;
            transform: translateX(0) rotate(0);
        }

        .t14-from-bottom {
            opacity: 0;
            transform: translateY(60px);
            transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .t14-from-bottom.active {
            opacity: 1;
            transform: translateY(0);
        }

        .t14-flip-in {
            opacity: 0;
            transform: perspective(600px) rotateY(25deg);
            transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .t14-flip-in.active {
            opacity: 1;
            transform: perspective(600px) rotateY(0);
        }

        /* ===== MAGNETIC TILT CARD ===== */
        .t14-tilt {
            transition: transform 0.15s ease-out;
        }

        /* ===== HEADER ===== */
        .t14-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 998;
            background: white;
            border-bottom: 3px solid var(--t14-gold);
            box-shadow: 0 2px 20px rgba(0,0,0,0.06);
            transition: all 0.3s;
        }
        .t14-header.scrolled {
            box-shadow: 0 4px 30px rgba(30,64,175,0.1);
        }
        .t14-header-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 68px;
        }
        .t14-logo {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }
        .t14-logo-img {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--t14-gold);
            box-shadow: 0 2px 10px rgba(245,158,11,0.3);
            transition: all 0.4s;
        }
        .t14-logo-img:hover {
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 4px 20px rgba(245,158,11,0.5);
        }
        .t14-logo-text h1 {
            font-size: 1.2rem;
            font-weight: 900;
            color: var(--t14-primary);
            line-height: 1.2;
        }
        .t14-logo-text p {
            font-size: 10px;
            color: var(--t14-gold-dark);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .t14-nav { 
            display: flex;
            gap: 1.75rem;
            align-items: center;
        }
        .about_content{
            font-size: 14px; color: var(--t14-gray); line-height: 1.8; padding-left: 1.5rem;
        }
        .t14-nav a {
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.3s;
            position: relative;
              padding: 0.3rem 4px;
        }
        .t14-nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 3px;
            background: var(--t14-gold);
            border-radius: 3px;
            transition: width 0.3s;
        }
        .t14-nav a:hover { color: var(--t14-primary); }
        .t14-nav a:hover::after { width: 100%; }
        .t14-btn-cta {
            background: var(--t14-primary);
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.3s;
            border: 2px solid var(--t14-primary);
            display: inline-block;
        }
        .t14-btn-cta:hover {
            background: var(--t14-gold);
            border-color: var(--t14-gold);
            color: var(--t14-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(245,158,11,0.4);
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--t14-primary);
            padding: 5px;
        }
        @media (max-width: 768px) {
            .mobile-menu-toggle { display: block; }
            .t14-nav {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 15px 0;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                z-index: 10;
                border-top: 3px solid var(--t14-gold);
            }
            .t14-nav.show { display: flex; }
            .t14-nav a { padding: 10px 25px; display: block; }
            .t14-header-inner { position: relative; }
        }

        /* ===== HERO ===== */
        .t14-hero {
            margin-top: 68px;
            position: relative;
            overflow: hidden;
            background: var(--t14-dark);
            min-height: 520px;
            display: flex;
            align-items: center;
        }
        /* Animated gradient mesh */
        .t14-hero-bg {
            position: absolute;
            inset: 0;
            z-index: 0;
        }
        .t14-hero-bg .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
            animation: t14-blob 12s ease-in-out infinite alternate;
        }
        .t14-hero-bg .blob-1 {
            width: 500px; height: 500px;
            background: var(--t14-primary);
            top: -100px; left: -100px;
        }
        .t14-hero-bg .blob-2 {
            width: 400px; height: 400px;
            background: var(--t14-gold);
            bottom: -80px; right: -80px;
            animation-delay: -4s;
        }
        .t14-hero-bg .blob-3 {
            width: 300px; height: 300px;
            background: #7c3aed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -8s;
        }
        @keyframes t14-blob {
            0% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(40px, -30px) scale(1.1); }
            66% { transform: translate(-20px, 30px) scale(0.9); }
            100% { transform: translate(30px, 10px) scale(1.05); }
        }
        /* Diagonal pattern overlay */
        .t14-hero-pattern {
            position: absolute;
            inset: 0;
            background-image:
                repeating-linear-gradient(
                    45deg,
                    transparent,
                    transparent 30px,
                    rgba(255,255,255,0.02) 30px,
                    rgba(255,255,255,0.02) 31px
                );
            z-index: 1;
        }
        .t14-hero-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 4rem 2rem;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 2;
            width: 100%;
        }
        .t14-hero-text { color: white; }
        .t14-hero-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(245,158,11,0.15);
            border: 1px solid rgba(245,158,11,0.3);
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 800;
            color: var(--t14-gold-light);
            margin-bottom: 1.5rem;
        }
        .t14-hero-chip i { color: var(--t14-gold); }
        .t14-hero-title {
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1.08;
            margin-bottom: 1.25rem;
            color: #9b9b9b;
        }
        .t14-typed-text {
            color: var(--t14-gold);
            position: relative;
        }
        .t14-typed-cursor {
            display: inline-block;
            width: 3px;
            height: 0.85em;
            background: var(--t14-gold);
            margin-left: 2px;
            animation: t14-blink 0.7s infinite;
            vertical-align: text-bottom;
        }
        @keyframes t14-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
        .t14-hero-desc {
            font-size: 1.0625rem;
            color: rgba(255,255,255,0.7);
            line-height: 1.7;
            margin-bottom: 2rem;
            max-width: 520px;
        }
        .t14-hero-btns {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .t14-btn-hero-primary {
            background: var(--t14-gold);
            color: var(--t14-dark);
            padding: 0.9rem 2.25rem;
            border-radius: 10px;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .t14-btn-hero-primary:hover {
            background: white;
            color: var(--t14-primary);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .t14-btn-hero-outline {
            color: white;
            padding: 0.9rem 2.25rem;
            border-radius: 10px;
            font-weight: 800;
            text-decoration: none;
            border: 2px solid rgba(255,255,255,0.25);
            transition: all 0.3s;
            font-size: 14px;
        }
        .t14-btn-hero-outline:hover {
            border-color: var(--t14-gold);
            color: var(--t14-gold);
            background: rgba(245,158,11,0.05);
        }
        /* Hero image with floating frame */
        .t14-hero-visual {
            position: relative;
        }
        .t14-hero-visual img {
            width: 100%;
            height: 380px;
            object-fit: cover;
            border-radius: 16px;
            border: 3px solid rgba(245,158,11,0.4);
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
            position: relative;
            z-index: 1;
        }
        .t14-hero-frame {
            position: absolute;
            top: 15px;
            left: 15px;
            right: -15px;
            bottom: -15px;
            border: 3px solid rgba(245,158,11,0.2);
            border-radius: 16px;
            z-index: 0;
        }
        /* Animated counters row */
        .t14-counters {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
            margin-top: 2.5rem;
        }
        .t14-counter {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1.25rem 1rem;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }
        .t14-counter:hover {
            background: rgba(245,158,11,0.1);
            border-color: var(--t14-gold);
            transform: translateY(-4px);
        }
        .t14-counter-val {
            font-size: 2rem;
            font-weight: 900;
            color: var(--t14-gold);
            font-variant-numeric: tabular-nums;
        }
        .t14-counter-lbl {
            font-size: 11px;
            color: rgba(255,255,255,0.6);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 0.25rem;
        }

        /* ===== BREADCRUMB ===== */
        .t14-breadcrumb {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 0;
        }
        .t14-breadcrumb .breadcrumb {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            background: transparent;
            font-size: 13px;
        }

        /* ===== SECTION ===== */
        .t14-section { padding: 5rem 0; }
        .t14-wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        .t14-section-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .t14-section-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--t14-primary-bg);
            color: var(--t14-primary);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            margin-bottom: 0.75rem;
        }
        .t14-section-tag i { color: var(--t14-gold); }
        .t14-section-title {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--t14-dark);
        }
        .t14-section-title span {
            color: var(--t14-primary);
        }

        /* ===== ANNOUNCEMENT ===== */
        .t14-announce {
            background: var(--t14-primary);
            color: white;
            padding: 0.875rem 0;
            position: relative;
            overflow: hidden;
        }
        .t14-announce::before {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(90deg, transparent, transparent 20px, rgba(255,255,255,0.03) 20px, rgba(255,255,255,0.03) 21px);
        }
        .t14-announce-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
        }

        /* ===== INFO GRID ===== */
        .t14-info-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2.5rem;
        }
        .t14-about-card {
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }
        /* Notebook lines effect */
        .t14-about-card::before {
            content: '';
            position: absolute;
            left: 40px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: rgba(30,64,175,0.08);
        }
        .t14-about-card h2 {
            font-size: 1.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            color: var(--t14-dark);
            padding-left: 1.5rem;
        }
        .t14-contact-sidebar {
            background: var(--t14-dark);
            color: white;
            padding: 2.5rem;
            border-radius: 16px;
            position: relative;
            overflow: hidden;
        }
        /* Chalkboard texture */
        .t14-contact-sidebar::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(255,255,255,0.02) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(255,255,255,0.015) 0%, transparent 50%);
        }
        .t14-contact-sidebar h3 {
            font-size: 1.25rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
            color: var(--t14-gold);
            position: relative;
        }
        .t14-contact-item {
            display: flex;
            align-items: start;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px dashed rgba(255,255,255,0.1);
            position: relative;
        }
        .t14-contact-item:last-child { border: none; }
        .t14-contact-ic {
            width: 44px;
            height: 44px;
            background: var(--t14-primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            border: 2px solid rgba(245,158,11,0.3);
        }
        .t14-rating-panel {
            background: linear-gradient(135deg, var(--t14-primary), var(--t14-primary-dark));
            padding: 1.5rem;
            border-radius: 14px;
            text-align: center;
            margin-top: 1.5rem;
            border: 2px solid rgba(245,158,11,0.2);
            position: relative;
        }
        .t14-rating-big {
            font-size: 3rem;
            font-weight: 900;
            color: var(--t14-gold);
            line-height: 1;
        }

        /* ===== PRODUCT CARDS ===== */
        .t14-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.5rem;
        }
        .t14-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }
        .t14-card::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 16px;
            border: 2px solid transparent;
            transition: border-color 0.3s;
            pointer-events: none;
        }
        .t14-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 35px rgba(30,64,175,0.12);
        }
        .t14-card:hover::after {
            border-color: var(--t14-gold);
        }
        .t14-card-img {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
        }
        .t14-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .t14-card:hover .t14-card-img img {
            transform: scale(1.06);
        }
        .t14-card-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--t14-gold);
            color: var(--t14-dark);
            padding: 0.35rem 0.85rem;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 900;
        }
        .t14-card-time {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            padding: 0.35rem 0.85rem;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .t14-card-heart {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 38px;
            height: 38px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .t14-card-heart:hover {
            background: var(--t14-primary);
            transform: scale(1.15);
        }
        .t14-card-heart:hover i { color: white !important; }
        .t14-card-heart i { font-size: 16px; }
        .text_red { color: var(--t14-primary); }
        .text_grey { color: #cbd5e1; }
        .t14-card-body { padding: 1.25rem; }
        .t14-card-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--t14-dark);
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 45px;
        }
        .t14-card-price {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            margin: 0.75rem 0;
        }
        .t14-price-now {
            font-size: 1.375rem;
            font-weight: 900;
            color: var(--t14-primary);
        }
        .t14-price-was {
            font-size: 0.875rem;
            color: var(--t14-gray);
            text-decoration: line-through;
        }
        .t14-btn-card {
            width: 100%;
            padding: 0.7rem;
            border: none;
            border-radius: 10px;
            background: var(--t14-primary);
            color: white;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .t14-btn-card:hover {
            background: var(--t14-gold);
            color: var(--t14-dark);
            box-shadow: 0 4px 15px rgba(245,158,11,0.4);
        }
        .t14-btn-remove {
            background: var(--t14-primary-bg);
            color: var(--t14-primary);
        }
        .t14-btn-remove:hover {
            background: var(--t14-primary);
            color: white;
        }

        /* ===== GALLERY ===== */
        .t14-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.25rem;
        }
        .t14-gallery-item {
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            aspect-ratio: 1;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.4s;
        }
        .t14-gallery-item:hover {
            border-color: var(--t14-gold);
            transform: scale(1.04);
            box-shadow: 0 10px 30px rgba(30,64,175,0.15);
            z-index: 10;
        }
        .t14-gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .t14-gallery-item:hover img { transform: scale(1.12); }
        .t14-gallery-ov {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(30,64,175,0.85), rgba(245,158,11,0.7));
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .t14-gallery-item:hover .t14-gallery-ov { opacity: 1; }
        .t14-gallery-ov i { font-size: 2rem; color: white; }

        /* ===== REVIEWS ===== */
        .t14-review {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            position: relative;
        }
        .t14-review::before {
            content: '\201C';
            position: absolute;
            top: 12px;
            right: 24px;
            font-size: 5rem;
            color: var(--t14-primary-bg);
            font-family: Georgia, serif;
            line-height: 1;
            pointer-events: none;
        }
        .t14-review:hover {
            border-color: var(--t14-primary-light);
            box-shadow: 0 8px 30px rgba(30,64,175,0.08);
        }

        /* ===== CONTACT TILES ===== */
        .t14-contact-tiles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        .t14-tile {
            background: white;
            padding: 2.5rem 2rem;
            border-radius: 16px;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
        }
        .t14-tile::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--t14-primary), var(--t14-gold));
            transform: scaleX(0);
            transition: transform 0.4s;
        }
        .t14-tile:hover::after { transform: scaleX(1); }
        .t14-tile:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 35px rgba(30,64,175,0.1);
        }
        .t14-tile-icon {
            width: 64px;
            height: 64px;
            background: var(--t14-primary-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            border: 2px solid var(--t14-primary-light);
        }

        /* ===== CHALKBOARD ABOUT ===== */
        .t14-chalkboard {
            background: var(--t14-chalk);
            border-radius: 16px;
            padding: 3rem;
            position: relative;
            overflow: hidden;
            color: rgba(255,255,255,0.85);
            line-height: 1.9;
            font-size: 14px;
        }
        .t14-chalkboard::before {
            content: '';
            position: absolute;
            inset: 8px;
            border: 2px dashed rgba(255,255,255,0.08);
            border-radius: 10px;
            pointer-events: none;
        }
        /* Chalk dust spots */
        .t14-chalkboard::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 15% 80%, rgba(255,255,255,0.02) 0%, transparent 40%),
                radial-gradient(ellipse at 75% 30%, rgba(255,255,255,0.02) 0%, transparent 40%),
                radial-gradient(ellipse at 50% 60%, rgba(255,255,255,0.01) 0%, transparent 50%);
            pointer-events: none;
        }

        .cursor-pointer { cursor: pointer; }
        .d-none { display: none; }

        .rating-stars { position: relative; display: inline-block; }
        .stars-base i { color: rgba(255,255,255,0.3); }
        .stars-fill {
            position: absolute; top: 0; left: 0;
            overflow: hidden; white-space: nowrap;
            color: var(--t14-gold); width: 0;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .t14-hero-inner { grid-template-columns: 1fr; }
            .t14-info-grid { grid-template-columns: 1fr; }
            .t14-nav { display: none; }
            .t14-products { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
        }
        @media (max-width: 768px) {
            .t14-hero-title {         font-size: 22px;}
            .t14-products { grid-template-columns: repeat(2, 1fr); }
            .t14-gallery { grid-template-columns: repeat(3, 1fr); }
            .t14-section { padding: 3rem 0; }
            .t14-chalkboard { padding: 2rem; }
             .mobile-menu-toggle {
                display: block;
            }
            .t14-btn-hero-primary, .t14-btn-hero-outline{
                    padding: 8px;
            }

            .dfasdf {
                font-size: 13px;
                padding: 0.85rem 7px;
            }

            .t14-btn-outline {
                padding: 0.75rem 10px;
            }

            .t14-nav {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 15px 0;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                z-index: 10;
            }

            .t14-hero-stat-val {
                font-size: 15px;
            }

            .t14-nav.show {
                display: flex;
            }

            .t14-nav a {
                padding: 10px 20px;
                display: block;
            }

            .t14-header-inner {
                position: relative;
            }

            .t14-topbar {
                display: none;
            }

            .t14-hero {
                padding: 2rem 0 3rem;
            }

            .t14-logo p {
                display: none;
            }

            .t14-logo h1 {
                font-size: 13px;
            }

            .t14-header-inner {
                padding: 0.875rem 1rem;
            }

            .t14-hero-img img {
                height: auto;
            }

            .t14-hero-inner {
                padding: 0 1rem;
            }

            .t14-announce-inner {
                padding: 0 1rem;
            }

            .t14-info-grid {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }

            .t14-wrap {
                padding: 0 1rem;
            }

            .t14-about-card {
                padding: 1.5rem;
            }

            .t14-contact-card {
                padding: 1.5rem;
            }

            .t14-rating-num {
                font-size: 2rem;
                font-weight: 600;
            }

            .t14-rating-box {
                padding: 0.5rem;
            }

            .t14-section {
                padding: 1rem 0;
            }

            .t14-section-title {
                font-size: 23px;
            }

            .cat_name {
                font-size: 17px;
            }

            .product_data {
                margin: 1rem 0;
            }

            .t14-card-img {
                height: 111px;
            }

            .t14-card-body {
                padding: 10px;
            }

            .t14-card-title {
                font-size: 13px;
            }

            .t14-nav {
                gap: 1rem;

            }
            .t14-counter {
    padding: 2px;}
    .t14-counter-val {
    font-size: 20px;}
    .t14-hero-visual img {
    height: auto;}
    .t14-hero-inner {
    gap: 2rem;
    }
    .t14-about-card::before{
        content : none;
    }
    .t14-about-card h2, .about_content{
        padding: 0;
    }
    .t14-contact-sidebar {
    padding: 1rem;

    }
    .t14-rating-big {
    font-size: 2rem;}
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
    <!-- Header -->
    <header class="t14-header" id="t14Header">
        <div class="t14-header-inner">
            <div class="t14-logo">
                <img loading="lazy" class="t14-logo-img" src="{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}" alt="{{ $store['name'] }}">
                <div class="t14-logo-text">
                    <h1>{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                    <p>{{ $store['meta_title'] }}</p>
                </div>
            </div>
            <button class="mobile-menu-toggle" onclick="document.querySelector('.t14-nav').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="t14-nav">
                <a href="#services">Services</a>
                <a href="#reviews">Reviews</a>
                <a href="#contact">Contact</a>
                <a href="#about">About</a>
                <a href="{{ route('store.gallery', [$store['slug']]) }}" class="t14-btn-cta">Gallery</a>
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <div class="t14-hero">
        <div class="t14-hero-bg">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="blob blob-3"></div>
        </div>
        <div class="t14-hero-pattern"></div>
        <div class="t14-hero-inner">
            <div class="t14-hero-text">
                <div class="t14-hero-chip">
                    <i class="fas fa-award"></i> Excellence in Every Detail
                </div>
                <h1 class="t14-hero-title">
                    Welcome to<br>
                    <span class="t14-typed-text" id="t14TypedText"></span><span class="t14-typed-cursor"></span>
                </h1>
                <p class="t14-hero-desc">{{ $store['meta_title'] }}</p>
                <div class="t14-hero-btns">
                    <a href="#services" class="t14-btn-hero-primary"><i class="fas fa-arrow-right"></i> Explore Now</a>
                    <a href="#contact" class="t14-btn-hero-outline">Contact Us</a>
                </div>

                @php $store_rating = number_format($store->average_rating, 1); @endphp
                <div class="t14-counters">
                    <div class="t14-counter">
                        <div class="t14-counter-val" data-target="{{ $store_rating }}" data-decimal="true">0</div>
                        <div class="t14-counter-lbl">Rating</div>
                    </div>
                    <div class="t14-counter">
                        <div class="t14-counter-val" data-target="{{ $store->rating_count }}">0</div>
                        <div class="t14-counter-lbl">Reviews</div>
                    </div>
                    <div class="t14-counter">
                        <div class="t14-counter-val" data-target="{{ count($productdata) }}">0</div>
                        <div class="t14-counter-lbl">Products</div>
                    </div>
                </div>
            </div>

            <div class="t14-hero-visual">
                <div class="t14-hero-frame"></div>
                <img loading="lazy" src="{{ asset('storage/app/public/store/cover/') . '/' . $store['cover_photo'] }}" alt="Cover">
            </div>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="t14-breadcrumb">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" style="color: var(--t14-primary);">Home</a></li>
                <li class="breadcrumb-item">›</li>
                <li class="breadcrumb-item active">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</li>
            </ol>
        </nav>
    </div>

    <!-- Announcement -->
    @if ($store->announcement)
        <div class="t14-announce">
            <div class="t14-announce-inner">
                <i class="fas fa-bullhorn" style="font-size: 20px;"></i>
                <div style="font-size: 14px; font-weight: 700;">{{ $store->announcement_message }}</div>
            </div>
        </div>
    @endif

    <!-- Info Section -->
    <div class="t14-section" style="background: var(--t14-light);">
        <div class="t14-wrap">
            <div class="t14-info-grid">
                <div class="t14-about-card t14-from-left">
                    <h2>About Our Business</h2>
                    @php
                        $description = $store['meta_description'];
                        $short = Str::limit($description, 350);
                    @endphp
                    <div style="" class="about_content" id="text-{{ $store['id'] }}">
                        {!! $short !!}
                        @if (strlen($description) > 350)
                            <span id="dots-{{ $store['id'] }}"></span>
                            <span id="more-{{ $store['id'] }}" class="d-none">{!! substr($description, 350) !!}</span>
                            <a class="cursor-pointer" style="color: var(--t14-primary); font-weight: 800;" onclick="toggleReadMore({{ $store['id'] }})"
                                id="btn-{{ $store['id'] }}">Read more →</a>
                        @endif
                    </div>
                </div>

                <div class="t14-contact-sidebar t14-from-right">
                    <h3>Quick Contact</h3>

                    <div class="t14-contact-item">
                        <div class="t14-contact-ic">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <h4 style="font-size: 10px; text-transform: uppercase; color: var(--t14-gold); margin-bottom: 0.5rem; letter-spacing: 1px;">Phone</h4>
                            @php
                                $phones = $data['store_config']?->webpage_phones;
                                if ($phones) {
                                    $phones = json_decode($phones, true);
                                } else {
                                    $phones = [];
                                }
                            @endphp
                            <p style="font-size: 14px; margin: 0;">
                                @if (!empty($phones))
                                    {{ implode(', ', $phones) }}
                                @else
                                    {{ $store['phone'] }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="t14-contact-item">
                        <div class="t14-contact-ic">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h4 style="font-size: 10px; text-transform: uppercase; color: var(--t14-gold); margin-bottom: 0.5rem; letter-spacing: 1px;">Email</h4>
                            <p style="font-size: 14px; margin: 0;">
                                <a href="mailto:{{ $store['email'] }}" style="color: white; text-decoration: none;">{{ $data['store_config']?->webpage_email ?? $store->email }}</a>
                            </p>
                        </div>
                    </div>

                    <div class="t14-contact-item">
                        <div class="t14-contact-ic">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h4 style="font-size: 10px; text-transform: uppercase; color: var(--t14-gold); margin-bottom: 0.5rem; letter-spacing: 1px;">Address</h4>
                            <p style="font-size: 14px; margin: 0;">{{ $store['address'] }}</p>
                        </div>
                    </div>

                    <div class="t14-rating-panel">
                        @php $store_rating = number_format($store->average_rating, 1); @endphp
                        <div class="t14-rating-big">{{ $store_rating }}</div>
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
                        <div style="font-size: 12px; opacity: 0.9; color: white;">{{ $store->rating_count }} Reviews</div>
                    </div>

                    <div style="margin-top: 1.5rem; position: relative;">
                        <div class="sharethis-inline-share-buttons"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Banners -->
    @if(count($data['banners']) > 0)
    <div style="max-width: 1400px; margin: 2rem auto; padding: 0 2rem;">
        <div class="owl-carousel banner-carousel t14-from-bottom">
            @foreach ($data['banners'] as $value)
                <a href="{{ $value->default_link ?? '#' }}" onclick="trackBannerClick({{ $value->id }})">
                    <img loading="lazy" src="{{ asset('storage/app/public/banner/') . '/' . $value->image }}" alt="banner" style="border-radius: 14px; width: 100%;">
                </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Products Section -->
    <div class="t14-section" id="services" style="background: white;">
        <div class="t14-wrap">
            <div class="t14-section-header">
                <span class="t14-section-tag"><i class="fas fa-th-large"></i> Our Offerings</span>
                <h2 class="t14-section-title">Products & <span>Services</span></h2>
            </div>

            @foreach ($productdata as $key => $cat)
                <div style="margin: 3rem 0;">
                    <h3 class="t14-from-left" style="font-size: 1.375rem; font-weight: 900; margin-bottom: 1.5rem; color: var(--t14-dark); display: inline-flex; align-items: center; gap: 0.75rem;">
                        <span style="display: inline-block; width: 5px; height: 28px; background: var(--t14-gold); border-radius: 3px;"></span>
                        {{ $cat->name }}
                    </h3>

                    <div class="t14-products">
                        @foreach ($cat->items as $index => $pro)
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
                            <div class="pr_{{ $pro->id }} t14-card t14-tilt {{ $index % 2 == 0 ? 't14-from-left' : 't14-from-right' }}" style="transition-delay: {{ $index * 0.06 }}s;">
                                <div class="t14-card-img">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <img loading="lazy"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                            alt="{{ $pro->name }}">
                                    </a>

                                    @if ($module == 5 && $store->delivery_time)
                                        <div class="t14-card-time">
                                            <i class="fas fa-bolt" style="color: var(--t14-gold);"></i>
                                            {{ strtoupper($store->delivery_time) }}
                                        </div>
                                    @endif

                                    @if ($pro->discount > 0)
                                        <div class="t14-card-badge">
                                            -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                        </div>
                                    @endif

                                    <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                        class="prHeart_{{ $pro->id }} t14-card-heart">
                                        <i class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                    </div>
                                </div>

                                <div class="t14-card-body">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <h4 class="t14-card-title" title="{{ ucfirst($pro->name) }}">
                                            {{ ucfirst($pro->name) }}
                                        </h4>
                                    </a>

                                    @if ($module == 5)
                                        <p style="font-size: 11px; color: var(--t14-gray); margin-bottom: 0.5rem; min-height: 16px;">
                                            {{ !empty($variations) ? $variations[0]->type : '' }}
                                        </p>

                                        <div class="t14-card-price">
                                            <div class="t14-price-now">{{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0)
                                                <div class="t14-price-was">{{ _price($mrp) }}</div>
                                            @endif
                                        </div>

                                        <div class="cartSec_{{ $pro->id }}">
                                            @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                            @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                <button onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                    class="t14-btn-card t14-btn-remove">
                                                    <i class="fa fa-times"></i> Remove
                                                </button>
                                            @else
                                                <button onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                    class="t14-btn-card">
                                                    <i class="fa fa-plus"></i> Add
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        @if ($pro->item_type == 'product')
                                            <div class="t14-card-price">
                                                <div class="t14-price-now">{{ _price($selling_price) }}</div>
                                                @if ($pro->discount > 0 || $mrp > $selling_price)
                                                    <div class="t14-price-was">{{ _price($mrp) }}</div>
                                                @endif
                                            </div>
                                        @endif

                                        @if (($data['store_config']->lead_available ?? 1) == 0)
                                            <button disabled class="t14-btn-card" style="opacity:0.5;cursor:not-allowed;">
                                                <i class="fas fa-paper-plane"></i> Enquire
                                            </button>
                                            <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                        @elseif (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                class="t14-btn-card">
                                                <i class="fas fa-paper-plane"></i> Enquire
                                            </button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="t14-btn-card">
                                                <i class="fas fa-paper-plane"></i> Enquire
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
                <div style="margin: 3rem 0;">
                    <h3 class="t14-from-left" style="font-size: 1.375rem; font-weight: 900; margin-bottom: 1.5rem; color: var(--t14-dark); display: inline-flex; align-items: center; gap: 0.75rem;">
                        <span style="display: inline-block; width: 5px; height: 28px; background: var(--t14-gold); border-radius: 3px;"></span>
                        {{ $cat->name }}
                    </h3>

                    <div class="t14-products">
                        @foreach ($cat->items as $index => $pro)
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
                            <div class="pr_{{ $pro->id }} t14-card t14-tilt {{ $index % 2 == 0 ? 't14-from-left' : 't14-from-right' }}" style="transition-delay: {{ $index * 0.06 }}s;">
                                <div class="t14-card-img">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <img loading="lazy"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                            alt="{{ $pro->name }}">
                                    </a>

                                    @if ($module == 5 && $store->delivery_time)
                                        <div class="t14-card-time">
                                            <i class="fas fa-bolt" style="color: var(--t14-gold);"></i>
                                            {{ strtoupper($store->delivery_time) }}
                                        </div>
                                    @endif

                                    @if ($pro->discount > 0)
                                        <div class="t14-card-badge">
                                            -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                        </div>
                                    @endif

                                    <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                        class="prHeart_{{ $pro->id }} t14-card-heart">
                                        <i class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                    </div>
                                </div>

                                <div class="t14-card-body">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <h4 class="t14-card-title" title="{{ ucfirst($pro->name) }}">
                                            {{ ucfirst($pro->name) }}
                                        </h4>
                                    </a>

                                    @if ($module == 5)
                                        <p style="font-size: 11px; color: var(--t14-gray); margin-bottom: 0.5rem; min-height: 16px;">
                                            {{ !empty($variations) ? $variations[0]->type : '' }}
                                        </p>

                                        <div class="t14-card-price">
                                            <div class="t14-price-now">{{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0)
                                                <div class="t14-price-was">{{ _price($mrp) }}</div>
                                            @endif
                                        </div>

                                        <div class="cartSec_{{ $pro->id }}">
                                            @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                            @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                <button onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                    class="t14-btn-card t14-btn-remove">
                                                    <i class="fa fa-times"></i> Remove
                                                </button>
                                            @else
                                                <button onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                    class="t14-btn-card">
                                                    <i class="fa fa-plus"></i> Add
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        @if ($pro->item_type == 'product')
                                            <div class="t14-card-price">
                                                <div class="t14-price-now">{{ _price($selling_price) }}</div>
                                                @if ($pro->discount > 0 || $mrp > $selling_price)
                                                    <div class="t14-price-was">{{ _price($mrp) }}</div>
                                                @endif
                                            </div>
                                        @endif

                                        @if (($data['store_config']->lead_available ?? 1) == 0)
                                            <button disabled class="t14-btn-card" style="opacity:0.5;cursor:not-allowed;">
                                                <i class="fas fa-paper-plane"></i> Enquire
                                            </button>
                                            <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                        @elseif (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                class="t14-btn-card">
                                                <i class="fas fa-paper-plane"></i> Enquire
                                            </button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="t14-btn-card">
                                                <i class="fas fa-paper-plane"></i> Enquire
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
                <div style="text-align: center; padding: 4rem 0; color: var(--t14-gray);">
                    <i class="fas fa-box-open" style="font-size: 4rem; margin-bottom: 1.5rem; opacity: 0.2;"></i>
                    <p style="font-size: 1.125rem; font-weight: 600;">No products available</p>
                </div>
            @endif

        </div>
    </div>

    <!-- Gallery -->
    @if (count($store->galleries))
        <div class="t14-section" style="background: var(--t14-light);">
            <div class="t14-wrap">
                <div class="t14-section-header">
                    <span class="t14-section-tag"><i class="fas fa-images"></i> Portfolio</span>
                    <h2 class="t14-section-title">Our <span>Gallery</span></h2>
                </div>

                <div class="t14-gallery">
                    @foreach ($data['galleries'] as $index => $value)
                        <a target="_blank"
                            href="{{ asset('storage/app/public/store/gallery') }}/{{ $value->image }}"
                            class="t14-gallery-item {{ $index % 2 == 0 ? 't14-from-left' : 't14-from-right' }} lightgallery-item" style="transition-delay: {{ $index * 0.05 }}s;">
                            <img loading="lazy"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                    $value['image'] ?? '',
                                    asset('storage/app/public/store/gallery') . '/' . $value['image'] ?? '',
                                    asset('public/assets/admin/img/160x160/img1.jpg'),
                                    'store/gallery/',
                                ) }}"
                                alt="Gallery {{ $index + 1 }}">
                            <div class="t14-gallery-ov">
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
        <div class="t14-section" id="reviews" style="background: white;">
            <div class="t14-wrap">
                <div class="t14-section-header">
                    <span class="t14-section-tag"><i class="fas fa-star"></i> Testimonials</span>
                    <h2 class="t14-section-title">Customer <span>Reviews</span></h2>
                </div>

                @foreach ($data['reviews'] as $index => $rev)
                    <div class="t14-review {{ $index % 2 == 0 ? 't14-from-left' : 't14-from-right' }}" style="transition-delay: {{ $index * 0.1 }}s;">
                        <div style="display: flex; gap: 1.25rem; margin-bottom: 1.25rem;">
                            <img loading="lazy"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/') . '/' . $rev->profile_image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 3px solid var(--t14-gold);"
                                alt="{{ $rev->f_name }}">
                            <div style="flex: 1;">
                                <div style="font-size: 1.0625rem; font-weight: 800; color: var(--t14-dark); margin-bottom: 0.5rem;">{{ $rev->f_name . ' ' . $rev->l_name }}</div>
                                <div style="font-size: 11px; color: var(--t14-gray); margin-bottom: 0.5rem;">{{ _formatted_datetime($rev->created_at) }}</div>
                                <div style="display: flex; gap: 0.25rem;">
                                    @for ($i = 1; $i < 6; $i++)
                                        <i class="fas fa-star" style="font-size: 14px; color: {{ $rev->rating >= $i ? '#f59e0b' : '#e0e0e0' }};"></i>
                                    @endfor
                                </div>
                            </div>
                        </div>
                        <p style="font-size: 14px; color: var(--t14-gray); line-height: 1.8;">{{ $rev->comment }}</p>

                        @if ($rev->attachment)
                            @php $attachments = json_decode($rev->attachment); @endphp
                            @if (!empty($attachments))
                                <div style="display: flex; gap: 0.75rem; margin-top: 1.25rem;">
                                    @foreach ($attachments as $img)
                                        <a target="_blank" href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}">
                                            <img loading="lazy" style="width: 80px; height: 80px; border-radius: 10px; object-fit: cover; border: 2px solid #e2e8f0; cursor: pointer;"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                alt="review">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        @if ($rev->reply)
                            <div style="margin-top: 1.5rem; padding: 1.5rem; background: var(--t14-primary-bg); border-left: 3px solid var(--t14-gold); border-radius: 10px;">
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                    <img loading="lazy"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store['logo'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                        style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"
                                        alt="{{ $store->name }}">
                                    <div>
                                        <div style="font-weight: 800; font-size: 13px; color: var(--t14-dark);">Store Response</div>
                                        <div style="font-size: 11px; color: var(--t14-gray);">{{ _formatted_datetime($rev->replied_at) }}</div>
                                    </div>
                                </div>
                                <p style="font-size: 13px; color: var(--t14-gray); line-height: 1.7; margin: 0;">{{ $rev->reply }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($data['review_count'] > 2)
                    <div style="text-align: center; margin-top: 2.5rem;">
                        <a href="{{ route('store.reviews', [$store->slug]) }}" class="t14-btn-cta" style="padding: 0.85rem 2.25rem; font-size: 14px;">
                            View All Reviews <i class="fas fa-arrow-right" style="margin-left: 0.75rem;"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Contact -->
    <div class="t14-section" id="contact" style="background: var(--t14-light);">
        <div class="t14-wrap">
            <div class="t14-section-header">
                <span class="t14-section-tag"><i class="fas fa-envelope-open-text"></i> Get In Touch</span>
                <h2 class="t14-section-title">Contact <span>Us</span></h2>
            </div>

            <div class="t14-contact-tiles" style="margin-top: 2.5rem;">
                @php $contacts = [
                    ['icon' => 'map', 'title' => 'Location', 'value' => $store['address']],
                    ['icon' => 'secured-letter', 'title' => 'Email', 'value' => $store['email'], 'link' => 'mailto:'],
                    ['icon' => 'phone', 'title' => 'Phone', 'value' => $store['phone'], 'link' => 'tel:'],
                    ['icon' => 'marker', 'title' => 'Directions', 'value' => 'View Map', 'modal' => true]
                ]; @endphp

                @foreach ($contacts as $index => $contact)
                    <div class="t14-tile t14-flip-in" style="transition-delay: {{ $index * 0.12 }}s;">
                        <div class="t14-tile-icon">
                            <img loading="lazy" src="https://img.icons8.com/ios-filled/50/{{ $contact['icon'] }}.png" alt="{{ $contact['title'] }}" style="width: 28px; height: 28px; filter: sepia(1) saturate(10) hue-rotate(190deg) brightness(0.5);">
                        </div>
                        <div style="font-size: 1.125rem; font-weight: 900; color: var(--t14-dark); margin-bottom: 0.5rem;">{{ $contact['title'] }}</div>
                        <div style="font-size: 13px; color: var(--t14-gray);">
                            @if (isset($contact['link']))
                                <a href="{{ $contact['link'] }}{{ $contact['value'] }}" style="color: var(--t14-primary); text-decoration: none; font-weight: 600;">{{ $contact['value'] }}</a>
                            @elseif (isset($contact['modal']))
                                <a class="cursor-pointer" data-bs-toggle="modal" data-bs-target="#exampleModal" style="color: var(--t14-primary); text-decoration: none; font-weight: 600;">{{ $contact['value'] }}</a>
                            @else
                                {{ $contact['value'] }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- About (Chalkboard Style) -->
    <div class="t14-section" id="about" style="background: white;">
        <div class="t14-wrap">
            <div class="t14-section-header">
                <span class="t14-section-tag"><i class="fas fa-graduation-cap"></i> About</span>
                <h2 class="t14-section-title">Our <span>Story</span></h2>
            </div>

            <div class="t14-chalkboard t14-from-bottom">
                {!! $data['store_config']->about_us ?? 'Information coming soon.' !!}
            </div>
        </div>
    </div>

    <!-- Map Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header" style="background: var(--t14-primary); color: white; border-radius: 16px 16px 0 0; border-bottom: 3px solid var(--t14-gold);">
                    <h5 class="modal-title">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="map" style="height: 450px; border-radius: 8px;"></div>
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
        /* ===== TYPEWRITER EFFECT ===== */
        (function() {
            const text = @json($data['store_config']?->webpage_name ?? $store['name']);
            const el = document.getElementById('t14TypedText');
            let i = 0;
            function type() {
                if (i < text.length) {
                    el.textContent += text.charAt(i);
                    i++;
                    setTimeout(type, 80);
                } else {
                    // Pause, then clear and retype
                    setTimeout(function() {
                        el.textContent = '';
                        i = 0;
                        type();
                    }, 4000);
                }
            }
            setTimeout(type, 600);
        })();

        /* ===== ANIMATED COUNTERS ===== */
        (function() {
            const counters = document.querySelectorAll('.t14-counter-val');
            let started = false;

            function animateCounters() {
                counters.forEach(counter => {
                    const target = parseFloat(counter.getAttribute('data-target'));
                    const isDecimal = counter.hasAttribute('data-decimal');
                    const duration = 2000;
                    const steps = 60;
                    const increment = target / steps;
                    let current = 0;
                    let step = 0;

                    const timer = setInterval(() => {
                        step++;
                        current = (target * step) / steps;
                        if (step >= steps) {
                            current = target;
                            clearInterval(timer);
                        }
                        counter.textContent = isDecimal ? current.toFixed(1) : Math.floor(current);
                    }, duration / steps);
                });
            }

            const counterObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !started) {
                        started = true;
                        animateCounters();
                    }
                });
            }, { threshold: 0.3 });

            if (counters.length) {
                counterObserver.observe(counters[0].closest('.t14-counters'));
            }
        })();

        /* ===== MAGNETIC TILT ON CARDS ===== */
        document.querySelectorAll('.t14-tilt').forEach(card => {
            card.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = ((y - centerY) / centerY) * -5;
                const rotateY = ((x - centerX) / centerX) * 5;
                this.style.transform = `perspective(800px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-6px)`;
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });

        /* ===== HEADER SCROLL EFFECT ===== */
        window.addEventListener('scroll', function() {
            const header = document.getElementById('t14Header');
            if (window.scrollY > 80) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        /* ===== SCROLL ANIMATIONS ===== */
        const t14Observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, { threshold: 0.06, rootMargin: '0px' });

        document.querySelectorAll('.t14-from-left, .t14-from-right, .t14-from-bottom, .t14-flip-in').forEach(el => {
            t14Observer.observe(el);
        });

        /* ===== RATING STARS ===== */
        document.querySelectorAll('.rating-stars').forEach(el => {
            const rating = parseFloat(el.getAttribute('data-rating'));
            const percentage = (Math.min(rating, 5) / 5) * 100;
            const fill = el.querySelector('.stars-fill');
            if (fill) fill.style.width = `${percentage}%`;
        });

        /* ===== LIGHTGALLERY ===== */
        if (document.querySelector('.t14-gallery')) {
            lightGallery(document.querySelector('.t14-gallery'), {
                selector: '.lightgallery-item',
                download: false,
                thumbnail: true,
                speed: 500
            });
        }

        /* ===== READ MORE ===== */
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

        /* ===== SMOOTH SCROLL ===== */
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
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