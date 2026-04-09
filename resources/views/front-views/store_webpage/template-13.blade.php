@extends('front-views.layout')

@section('title', $store['meta_title'] ?? ($data['store_config']?->webpage_name ?? $store['name']))

@section('meta_keywords', $keywords)
@section('meta_description', $store['meta_description'])


@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --t13-primary: #7c3aed;
            --t13-primary-dark: #5b21b6;
            --t13-primary-light: #c4b5fd;
            --t13-primary-bg: #f5f3ff;
            --t13-accent: #f472b6;
            --t13-dark: #1e1b4b;
            --t13-gray: #6b7280;
            --t13-light: #faf5ff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #fff;
            color: var(--t13-dark);
            overflow-x: hidden;
        }

        .product_data {
            margin: 3rem 0;
        }

        .about_section {
            background: var(--t13-primary-bg);
            border-radius: 20px;
            padding: 2.5rem;
            border: 1px solid #ede9fe;
            line-height: 1.9;
            font-size: 14px;
            color: var(--t13-gray);
        }

        /* ===== SPARKLE CURSOR ===== */
        .sparkle-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
        }

        /* ===== SCROLL ANIMATIONS ===== */
        .t13-slide-left {
            opacity: 0;
            transform: translateX(-80px);
            transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .t13-slide-left.active {
            opacity: 1;
            transform: translateX(0);
        }

        .t13-slide-right {
            opacity: 0;
            transform: translateX(80px);
            transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .t13-slide-right.active {
            opacity: 1;
            transform: translateX(0);
        }

        .t13-fade-up {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .t13-fade-up.active {
            opacity: 1;
            transform: translateY(0);
        }

        .t13-zoom-in {
            opacity: 0;
            transform: scale(0.85);
            transition: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .t13-zoom-in.active {
            opacity: 1;
            transform: scale(1);
        }

        /* ===== HEADER ===== */
        .t13-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 998;
            background: rgba(30, 27, 75, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(124, 58, 237, 0.2);
        }

        .t13-header-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0.875rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .t13-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .t13-logo img {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            border: 2px solid var(--t13-primary);
            object-fit: cover;
            transition: all 0.4s;
        }

        .t13-logo img:hover {
            border-color: var(--t13-accent);
            box-shadow: 0 0 20px rgba(124, 58, 237, 0.5);
        }

        .t13-logo h1 {
            font-size: 1.2rem;
            font-weight: 800;
            color: white;
        }

        .t13-logo p {
            font-size: 10px;
            color: var(--t13-primary-light);
        }

        .t13-nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .t13-nav a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
        }

        .t13-nav a::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--t13-primary), var(--t13-accent));
            transition: all 0.3s;
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .t13-nav a:hover {
            color: white;
        }

        .t13-nav a:hover::after {
            width: 100%;
        }

        .t13-btn-glow {
            background: linear-gradient(135deg, var(--t13-primary), var(--t13-accent));
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }

        .t13-btn-glow:hover {
            box-shadow: 0 0 25px rgba(124, 58, 237, 0.6), 0 0 50px rgba(244, 114, 182, 0.3);
            transform: translateY(-2px);
            color: white;
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

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .t13-nav {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: rgba(30, 27, 75, 0.98);
                backdrop-filter: blur(20px);
                flex-direction: column;
                padding: 15px 0;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                z-index: 10;
            }

            .t13-nav.show {
                display: flex;
            }

            .t13-nav a {
                padding: 12px 25px;
                display: block;
            }

            .t13-header-inner {
                position: relative;
            }
        }

        /* ===== HERO ===== */
        .t13-hero {
            margin-top: 62px;
            background: linear-gradient(135deg, var(--t13-dark) 0%, #312e81 50%, var(--t13-primary-dark) 100%);
            padding: 5rem 0 4rem;
            position: relative;
            overflow: hidden;
        }

        .t13-hero::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(124, 58, 237, 0.15) 0%, transparent 70%);
            top: -150px;
            right: -100px;
            border-radius: 50%;
            animation: t13-float 8s ease-in-out infinite;
        }

        .t13-hero::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(244, 114, 182, 0.1) 0%, transparent 70%);
            bottom: -100px;
            left: -100px;
            border-radius: 50%;
            animation: t13-float 10s ease-in-out infinite reverse;
        }

        @keyframes t13-float {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(30px, -30px) scale(1.1);
            }
        }

        .t13-hero-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .t13-hero-text {
            color: white;
        }

        .t13-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(124, 58, 237, 0.3);
            border: 1px solid rgba(124, 58, 237, 0.5);
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            color: var(--t13-primary-light);
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
        }

        .t13-hero-title {
            color: #eed5ff;
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1.08;
            margin-bottom: 1.25rem;
        }

        .t13-hero-title span {
            background: linear-gradient(135deg, var(--t13-primary-light), var(--t13-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .t13-hero-desc {
            font-size: 1.0625rem;
            color: rgba(255, 255, 255, 0.75);
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .t13-hero-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .t13-btn-hero {
            background: linear-gradient(135deg, var(--t13-primary), var(--t13-accent));
            color: white;
            padding: 0.9rem 2.25rem;
            border-radius: 30px;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 14px;
        }

        .t13-btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(124, 58, 237, 0.5);
            color: white;
        }

        .t13-btn-ghost {
            color: white;
            padding: 0.9rem 2.25rem;
            border-radius: 30px;
            font-weight: 800;
            text-decoration: none;
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s;
            font-size: 14px;
        }

        .t13-btn-ghost:hover {
            border-color: var(--t13-primary-light);
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .t13-hero-img {
            position: relative;
        }

        .t13-hero-img img {
            width: 100%;
            height: 380px;
            object-fit: cover;
            border-radius: 20px;
            border: 2px solid rgba(124, 58, 237, 0.3);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
        }

        .t13-stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }

        .t13-stat-box {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }

        .t13-stat-box:hover {
            background: rgba(124, 58, 237, 0.15);
            border-color: var(--t13-primary);
            transform: translateY(-3px);
        }

        .t13-stat-val {
            font-size: 1.75rem;
            font-weight: 900;
            color: white;
        }

        .t13-stat-lbl {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 0.25rem;
        }

        /* ===== BREADCRUMB ===== */
        .t13-breadcrumb {
            background: var(--t13-primary-bg);
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9d5ff;
        }

        .t13-breadcrumb .breadcrumb {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            background: transparent;
            font-size: 13px;
        }

        /* ===== SECTIONS ===== */
        .t13-section {
            padding: 4.5rem 0;
        }

        .t13-wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .t13-section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .t13-section-tag {
            display: inline-block;
            background: linear-gradient(135deg, var(--t13-primary), var(--t13-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 0.5rem;
        }

        .t13-section-title {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--t13-dark);
        }

        /* ===== ANNOUNCEMENT ===== */
        .t13-announce {
            background: linear-gradient(90deg, var(--t13-primary), var(--t13-accent));
            color: white;
            padding: 0.875rem 0;
        }

        .t13-announce-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* ===== INFO GRID ===== */
        .t13-info-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2.5rem;
        }

        .t13-about-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(124, 58, 237, 0.06);
            border: 1px solid #ede9fe;
        }

        .t13-about-card h2 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--t13-dark);
        }

        .t13-contact-card {
            background: linear-gradient(160deg, var(--t13-dark) 0%, #312e81 100%);
            color: white;
            padding: 2.5rem;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
        }

        .t13-contact-card::before {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(124, 58, 237, 0.2) 0%, transparent 70%);
            top: -50px;
            right: -50px;
            border-radius: 50%;
        }

        .t13-contact-card h3 {
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: var(--t13-primary-light);
        }

        .t13-contact-row {
            display: flex;
            align-items: start;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
            z-index: 1;
        }

        .t13-contact-row:last-child {
            border: none;
        }

        .t13-contact-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--t13-primary), var(--t13-accent));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .t13-rating-box {
            background: linear-gradient(135deg, var(--t13-primary), var(--t13-accent));
            padding: 1.5rem;
            border-radius: 16px;
            text-align: center;
            margin-top: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .t13-rating-num {
            font-size: 3rem;
            font-weight: 900;
            line-height: 1;
            color: white;
        }

        /* ===== PRODUCT CARDS ===== */
        .t13-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 1.5rem;
        }

        .t13-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(124, 58, 237, 0.05);
            border: 1px solid #ede9fe;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
        }

        .t13-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(124, 58, 237, 0.15);
            border-color: var(--t13-primary-light);
        }

        .t13-card-img {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .t13-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .t13-card:hover .t13-card-img img {
            transform: scale(1.08);
        }

        .t13-card-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: linear-gradient(135deg, var(--t13-primary), var(--t13-accent));
            color: white;
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
        }

        .t13-card-time {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .t13-card-heart {
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .t13-card-heart:hover {
            background: linear-gradient(135deg, var(--t13-primary), var(--t13-accent));
            transform: scale(1.15);
        }

        .t13-card-heart:hover i {
            color: white !important;
        }

        .t13-card-heart i {
            font-size: 16px;
        }

        .text_red {
            color: var(--t13-primary);
        }

        .text_grey {
            color: #cbd5e1;
        }

        .t13-card-body {
            padding: 1.25rem;
        }

        .t13-card-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--t13-dark);
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 45px;
        }

        .t13-card-price {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            margin: 0.75rem 0;
        }

        .t13-price-now {
            font-size: 1.375rem;
            font-weight: 900;
            color: var(--t13-primary);
        }

        .t13-price-was {
            font-size: 0.875rem;
            color: var(--t13-gray);
            text-decoration: line-through;
        }

        .t13-btn-card {
            width: 100%;
            padding: 0.7rem;
            border: none;
            border-radius: 25px;
            background: linear-gradient(135deg, var(--t13-primary), var(--t13-primary-dark));
            color: white;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .t13-btn-card:hover {
            background: linear-gradient(135deg, var(--t13-primary), var(--t13-accent));
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
            transform: translateY(-2px);
        }

        .t13-btn-remove {
            background: var(--t13-primary-bg);
            color: var(--t13-primary);
        }

        .t13-btn-remove:hover {
            background: linear-gradient(135deg, var(--t13-primary), var(--t13-accent));
            color: white;
        }

        /* ===== GALLERY ===== */
        .t13-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .t13-gallery-item {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            aspect-ratio: 1;
            cursor: pointer;
            transition: all 0.4s;
        }

        .t13-gallery-item:hover {
            transform: scale(1.04);
            box-shadow: 0 10px 30px rgba(124, 58, 237, 0.25);
            z-index: 10;
        }

        .t13-gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .t13-gallery-item:hover img {
            transform: scale(1.15);
        }

        .t13-gallery-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.85), rgba(244, 114, 182, 0.85));
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .t13-gallery-item:hover .t13-gallery-overlay {
            opacity: 1;
        }

        .t13-gallery-overlay i {
            font-size: 2rem;
            color: white;
        }

        /* ===== REVIEWS ===== */
        .t13-review {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 15px rgba(124, 58, 237, 0.05);
            border: 1px solid #ede9fe;
            transition: all 0.3s;
        }

        .t13-review:hover {
            border-color: var(--t13-primary-light);
            box-shadow: 0 8px 30px rgba(124, 58, 237, 0.1);
        }

        /* ===== CONTACT TILES ===== */
        .t13-contact-tiles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .t13-tile {
            background: white;
            padding: 2.5rem 2rem;
            border-radius: 20px;
            text-align: center;
            border: 1px solid #ede9fe;
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
        }

        .t13-tile::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.03), rgba(244, 114, 182, 0.03));
            opacity: 0;
            transition: opacity 0.3s;
        }

        .t13-tile:hover::before {
            opacity: 1;
        }

        .t13-tile:hover {
            border-color: var(--t13-primary-light);
            transform: translateY(-6px);
            box-shadow: 0 12px 35px rgba(124, 58, 237, 0.12);
        }

        .t13-tile-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--t13-primary-bg), #fce7f3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            position: relative;
            z-index: 1;
        }

        .cat_name {
            font-size: 1.375rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: var(--t13-dark);
            padding-bottom: 0.5rem;
            border-bottom: 3px solid;
            border-image: linear-gradient(90deg, var(--t13-primary), var(--t13-accent)) 1;
            display: inline-block;
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

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .t13-hero-inner {
                grid-template-columns: 1fr;
            }

            .t13-info-grid {
                grid-template-columns: 1fr;
            }

            .t13-nav {
                display: none;
            }

            .t13-products {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .t13-hero-title {
                font-size: 23px;
            }

            .t13-products {
                grid-template-columns: repeat(2, 1fr);
            }

            .t13-gallery {
                grid-template-columns: repeat(3, 1fr);
            }

            .t13-section {
                padding: 3rem 0;
            }
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .t13-hero-desc {
                font-size: 12px;
            }

            .t13-btn-ghost,
            .t13-btn-hero {
                padding: 10px;
            }

            .t13-stat-box {
                padding: 6px;
            }

            .t13-stat-val {
                font-size: 19px;
            }

            .t13-hero-inner {
                gap: 2rem;

            }

            .dfasdf {
                font-size: 13px;
                padding: 0.85rem 7px;
            }

            .t13-btn-outline {
                padding: 0.75rem 10px;
            }

            .t13-nav {
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

            .t13-hero-stat-val {
                font-size: 15px;
            }

            .t13-nav.show {
                display: flex;
            }

            .t13-nav a {
                padding: 10px 20px;
                display: block;
            }

            .t13-header-inner {
                position: relative;
            }

            .t13-topbar {
                display: none;
            }

            .t13-hero {
                padding: 2rem 0 3rem;
            }

            .t13-logo p {
                display: none;
            }

            .t13-logo h1 {
                font-size: 13px;
            }

            .t13-header-inner {
                padding: 0.875rem 1rem;
            }

            .t13-hero-img img {
                height: auto;
            }

            .t13-hero-inner {
                padding: 0 1rem;
            }

            .t13-announce-inner {
                padding: 0 1rem;
            }

            .t13-info-grid {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }

            .t13-wrap {
                padding: 0 1rem;
            }

            .t13-about-card {
                padding: 1.5rem;
            }

            .t13-contact-card {
                padding: 10px;
            }

            .t13-rating-num {
                font-size: 2rem;
                font-weight: 600;
            }

            .t13-rating-box {
                padding: 0.5rem;
            }

            .t13-section {
                padding: 1rem 0;
            }

            .t13-section-title {
                font-size: 23px;
            }

            .cat_name {
                font-size: 15px;
            }

            .t13-section-header {
                margin-bottom: 1rem;
            }


            .product_data {
                margin: 1rem 0;
            }

            .about_section {
                   padding: 1rem;
            }

            .t13-card-img {
                height: 111px;
            }

            .t13-card-body {
                padding: 10px;
            }

            .t13-card-title {
                font-size: 13px;
            }.t13-nav {
    gap: 1rem;
            }

            .product_data {
                margin: 1rem 0;
            }
            .t13-nav a {
    color: #9947e0;}
        }
        .t13-btn-glow{
                    color: white;
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
    <!-- Sparkle Canvas -->
    <canvas class="sparkle-canvas" id="sparkleCanvas"></canvas>

    <!-- Header -->
    <header class="t13-header">
        <div class="t13-header-inner">
            <div class="t13-logo">
                <img loading="lazy" src="{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}"
                    alt="{{ $store['name'] }}">
                <div>
                    <h1>{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                    <p>{{ $store['meta_title'] }}</p>
                </div>
            </div>
            <button class="mobile-menu-toggle" onclick="document.querySelector('.t13-nav').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="t13-nav">
                <a href="#services">Services</a>
                <a href="#reviews">Reviews</a>
                <a href="#contact">Contact</a>
                <a href="#about">About</a>
                <a href="{{ route('store.gallery', [$store['slug']]) }}" class="t13-btn-glow">Gallery</a>
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <div class="t13-hero">
        <div class="t13-hero-inner">
            <div class="t13-hero-text">
                <div class="t13-hero-badge">
                    <i class="fas fa-sparkles"></i> Premium Experience
                </div>
                <h1 class="t13-hero-title">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                <p class="t13-hero-desc">{{ $store['meta_title'] }}</p>
                <div class="t13-hero-actions">
                    <a href="#services" class="t13-btn-hero">Explore Now</a>
                    <a href="#contact" class="t13-btn-ghost">Contact Us</a>
                </div>

                @php $store_rating = number_format($store->average_rating, 1); @endphp
                <div class="t13-stats-row">
                    <div class="t13-stat-box">
                        <div class="t13-stat-val">{{ $store_rating }}</div>
                        <div class="t13-stat-lbl">Rating</div>
                    </div>
                    <div class="t13-stat-box">
                        <div class="t13-stat-val">{{ $store->rating_count }}+</div>
                        <div class="t13-stat-lbl">Reviews</div>
                    </div>
                    <div class="t13-stat-box">
                        <div class="t13-stat-val">{{ count($productdata) }}+</div>
                        <div class="t13-stat-lbl">Products</div>
                    </div>
                </div>
            </div>

            <div class="t13-hero-img">
                <img loading="lazy" src="{{ asset('storage/app/public/store/cover/') . '/' . $store['cover_photo'] }}"
                    alt="Cover">
            </div>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="t13-breadcrumb">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" style="color: var(--t13-primary);">Home</a></li>
                <li class="breadcrumb-item">›</li>
                <li class="breadcrumb-item active">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</li>
            </ol>
        </nav>
    </div>

    <!-- Announcement -->
    @if ($store->announcement)
        <div class="t13-announce">
            <div class="t13-announce-inner">
                <i class="fas fa-bullhorn" style="font-size: 20px;"></i>
                <div style="font-size: 14px; font-weight: 700;">{{ $store->announcement_message }}</div>
            </div>
        </div>
    @endif

    <!-- Info Section -->
    <div class="t13-section" style="background: var(--t13-primary-bg);">
        <div class="t13-wrap">
            <div class="t13-info-grid">
                <div class="t13-about-card t13-slide-left">
                    <h2>About Our Business</h2>
                    @php
                        $description = $store['meta_description'];
                        $short = Str::limit($description, 350);
                    @endphp
                    <div style="font-size: 14px; color: var(--t13-gray); line-height: 1.8;" id="text-{{ $store['id'] }}">
                        {!! $short !!}
                        @if (strlen($description) > 350)
                            <span id="dots-{{ $store['id'] }}"></span>
                            <span id="more-{{ $store['id'] }}" class="d-none">{!! substr($description, 350) !!}</span>
                            <a class="cursor-pointer" style="color: var(--t13-primary); font-weight: 800;"
                                onclick="toggleReadMore({{ $store['id'] }})" id="btn-{{ $store['id'] }}">Read more →</a>
                        @endif
                    </div>
                </div>

                <div class="t13-contact-card t13-slide-right">
                    <h3>Quick Contact</h3>

                    <div class="t13-contact-row">
                        <div class="t13-contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <h4
                                style="font-size: 10px; text-transform: uppercase; color: var(--t13-primary-light); margin-bottom: 0.5rem; letter-spacing: 1px;">
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
                                @if (!empty($phones))
                                    {{ implode(', ', $phones) }}
                                @else
                                    {{ $store['phone'] }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="t13-contact-row">
                        <div class="t13-contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h4
                                style="font-size: 10px; text-transform: uppercase; color: var(--t13-primary-light); margin-bottom: 0.5rem; letter-spacing: 1px;">
                                Email</h4>
                            <p style="font-size: 14px; margin: 0;">
                                <a href="mailto:{{ $store['email'] }}"
                                    style="color: white; text-decoration: none;">{{ $data['store_config']?->webpage_email ?? $store->email }}</a>
                            </p>
                        </div>
                    </div>

                    <div class="t13-contact-row">
                        <div class="t13-contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h4
                                style="font-size: 10px; text-transform: uppercase; color: var(--t13-primary-light); margin-bottom: 0.5rem; letter-spacing: 1px;">
                                Address</h4>
                            <p style="font-size: 14px; margin: 0;">{{ $store['address'] }}</p>
                        </div>
                    </div>

                    <div class="t13-rating-box">
                        @php $store_rating = number_format($store->average_rating, 1); @endphp
                        <div class="t13-rating-num">{{ $store_rating }}</div>
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
            <div class="owl-carousel banner-carousel t13-zoom-in">
                @foreach ($data['banners'] as $value)
                    <a href="{{ $value->default_link ?? '#' }}" onclick="trackBannerClick({{ $value->id }})">
                        <img loading="lazy" src="{{ asset('storage/app/public/banner/') . '/' . $value->image }}"
                            alt="banner" style="border-radius: 16px; width: 100%;">
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Products Section -->
    <div class="t13-section" id="services" style="background: white;">
        <div class="t13-wrap">
            <div class="t13-section-header">
                <div class="t13-section-tag">Our Offerings</div>
                <h2 class="t13-section-title">Products & Services</h2>
            </div>

            @foreach ($productdata as $key => $cat)
                <div style="" class="product_data">
                    <h3 class="t13-slide-left cat_name" style="">{{ $cat->name }}</h3>

                    <div class="t13-products">
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
                            <div class="pr_{{ $pro->id }} t13-card {{ $index % 2 == 0 ? 't13-slide-left' : 't13-slide-right' }}"
                                style="transition-delay: {{ $index * 0.06 }}s;">
                                <div class="t13-card-img">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <img loading="lazy"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                            alt="{{ $pro->name }}">
                                    </a>

                                    @if ($module == 5 && $store->delivery_time)
                                        <div class="t13-card-time">
                                            <i class="fas fa-bolt" style="color: var(--t13-accent);"></i>
                                            {{ strtoupper($store->delivery_time) }}
                                        </div>
                                    @endif

                                    @if ($pro->discount > 0)
                                        <div class="t13-card-badge">
                                            -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                        </div>
                                    @endif

                                    <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                        class="prHeart_{{ $pro->id }} t13-card-heart">
                                        <i
                                            class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                    </div>
                                </div>

                                <div class="t13-card-body">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <h4 class="t13-card-title" title="{{ ucfirst($pro->name) }}">
                                            {{ ucfirst($pro->name) }}
                                        </h4>
                                    </a>

                                    @if ($module == 5)
                                        <p
                                            style="font-size: 11px; color: var(--t13-gray); margin-bottom: 0.5rem; min-height: 16px;">
                                            {{ !empty($variations) ? $variations[0]->type : '' }}
                                        </p>

                                        <div class="t13-card-price">
                                            <div class="t13-price-now">{{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0)
                                                <div class="t13-price-was">{{ _price($mrp) }}</div>
                                            @endif
                                        </div>

                                        <div class="cartSec_{{ $pro->id }}">
                                            @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                            @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                    class="t13-btn-card t13-btn-remove">
                                                    <i class="fa fa-times"></i> Remove
                                                </button>
                                            @else
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                    class="t13-btn-card">
                                                    <i class="fa fa-plus"></i> Add
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        @if ($pro->item_type == 'product')
                                            <div class="t13-card-price">
                                                <div class="t13-price-now">{{ _price($selling_price) }}</div>
                                                @if ($pro->discount > 0 || $mrp > $selling_price)
                                                    <div class="t13-price-was">{{ _price($mrp) }}</div>
                                                @endif
                                            </div>
                                        @endif

                                        @if (($data['store_config']->lead_available ?? 1) == 0)
                                            <button disabled class="t13-btn-card" style="opacity:0.5;cursor:not-allowed;">
                                                <i class="fas fa-paper-plane"></i> Enquire
                                            </button>
                                            <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                        @elseif (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                class="t13-btn-card">
                                                <i class="fas fa-paper-plane"></i> Enquire
                                            </button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="t13-btn-card">
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
                <div style="" class="product_data">
                    <h3 class="t13-slide-left cat_name" style="">{{ $cat->name }}</h3>

                    <div class="t13-products">
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
                            <div class="pr_{{ $pro->id }} t13-card {{ $index % 2 == 0 ? 't13-slide-left' : 't13-slide-right' }}"
                                style="transition-delay: {{ $index * 0.06 }}s;">
                                <div class="t13-card-img">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <img loading="lazy"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                            alt="{{ $pro->name }}">
                                    </a>

                                    @if ($module == 5 && $store->delivery_time)
                                        <div class="t13-card-time">
                                            <i class="fas fa-bolt" style="color: var(--t13-accent);"></i>
                                            {{ strtoupper($store->delivery_time) }}
                                        </div>
                                    @endif

                                    @if ($pro->discount > 0)
                                        <div class="t13-card-badge">
                                            -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                        </div>
                                    @endif

                                    <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                        class="prHeart_{{ $pro->id }} t13-card-heart">
                                        <i
                                            class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                    </div>
                                </div>

                                <div class="t13-card-body">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <h4 class="t13-card-title" title="{{ ucfirst($pro->name) }}">
                                            {{ ucfirst($pro->name) }}
                                        </h4>
                                    </a>

                                    @if ($module == 5)
                                        <p
                                            style="font-size: 11px; color: var(--t13-gray); margin-bottom: 0.5rem; min-height: 16px;">
                                            {{ !empty($variations) ? $variations[0]->type : '' }}
                                        </p>

                                        <div class="t13-card-price">
                                            <div class="t13-price-now">{{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0)
                                                <div class="t13-price-was">{{ _price($mrp) }}</div>
                                            @endif
                                        </div>

                                        <div class="cartSec_{{ $pro->id }}">
                                            @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                            @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                    class="t13-btn-card t13-btn-remove">
                                                    <i class="fa fa-times"></i> Remove
                                                </button>
                                            @else
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                    class="t13-btn-card">
                                                    <i class="fa fa-plus"></i> Add
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        @if ($pro->item_type == 'product')
                                            <div class="t13-card-price">
                                                <div class="t13-price-now">{{ _price($selling_price) }}</div>
                                                @if ($pro->discount > 0 || $mrp > $selling_price)
                                                    <div class="t13-price-was">{{ _price($mrp) }}</div>
                                                @endif
                                            </div>
                                        @endif

                                        @if (($data['store_config']->lead_available ?? 1) == 0)
                                            <button disabled class="t13-btn-card" style="opacity:0.5;cursor:not-allowed;">
                                                <i class="fas fa-paper-plane"></i> Enquire
                                            </button>
                                            <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                        @elseif (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                class="t13-btn-card">
                                                <i class="fas fa-paper-plane"></i> Enquire
                                            </button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="t13-btn-card">
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
                <div style="text-align: center; padding: 4rem 0; color: var(--t13-gray);">
                    <i class="fas fa-box-open" style="font-size: 4rem; margin-bottom: 1.5rem; opacity: 0.2;"></i>
                    <p style="font-size: 1.125rem; font-weight: 600;">No products available</p>
                </div>
            @endif

        </div>
    </div>

    <!-- Gallery -->
    @if (count($store->galleries))
        <div class="t13-section" style="background: var(--t13-primary-bg);">
            <div class="t13-wrap">
                <div class="t13-section-header">
                    <div class="t13-section-tag">Portfolio</div>
                    <h2 class="t13-section-title">Gallery</h2>
                </div>

                <div class="t13-gallery">
                    @foreach ($data['galleries'] as $index => $value)
                        <a target="_blank" href="{{ asset('storage/app/public/store/gallery') }}/{{ $value->image }}"
                            class="t13-gallery-item {{ $index % 2 == 0 ? 't13-slide-left' : 't13-slide-right' }} lightgallery-item"
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
                            <div class="t13-gallery-overlay">
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
        <div class="t13-section" id="reviews" style="background: white;">
            <div class="t13-wrap">
                <div class="t13-section-header">
                    <div class="t13-section-tag">Testimonials</div>
                    <h2 class="t13-section-title">Customer Reviews</h2>
                </div>

                @foreach ($data['reviews'] as $index => $rev)
                    <div class="t13-review {{ $index % 2 == 0 ? 't13-slide-left' : 't13-slide-right' }}"
                        style="transition-delay: {{ $index * 0.1 }}s;">
                        <div style="display: flex; gap: 1.25rem; margin-bottom: 1.25rem;">
                            <img loading="lazy"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/') . '/' . $rev->profile_image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 3px solid var(--t13-primary);"
                                alt="{{ $rev->f_name }}">
                            <div style="flex: 1;">
                                <div
                                    style="font-size: 1.0625rem; font-weight: 800; color: var(--t13-dark); margin-bottom: 0.5rem;">
                                    {{ $rev->f_name . ' ' . $rev->l_name }}</div>
                                <div style="font-size: 11px; color: var(--t13-gray); margin-bottom: 0.5rem;">
                                    {{ _formatted_datetime($rev->created_at) }}</div>
                                <div style="display: flex; gap: 0.25rem;">
                                    @for ($i = 1; $i < 6; $i++)
                                        <i class="fas fa-star"
                                            style="font-size: 14px; color: {{ $rev->rating >= $i ? '#f59e0b' : '#e0e0e0' }};"></i>
                                    @endfor
                                </div>
                            </div>
                        </div>
                        <p style="font-size: 14px; color: var(--t13-gray); line-height: 1.8;">{{ $rev->comment }}</p>

                        @if ($rev->attachment)
                            @php $attachments = json_decode($rev->attachment); @endphp
                            @if (!empty($attachments))
                                <div style="display: flex; gap: 0.75rem; margin-top: 1.25rem;">
                                    @foreach ($attachments as $img)
                                        <a target="_blank"
                                            href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}">
                                            <img loading="lazy"
                                                style="width: 80px; height: 80px; border-radius: 10px; object-fit: cover; border: 2px solid #ede9fe; cursor: pointer;"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                alt="review">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        @if ($rev->reply)
                            <div
                                style="margin-top: 1.5rem; padding: 1.5rem; background: var(--t13-primary-bg); border-left: 3px solid var(--t13-primary); border-radius: 10px;">
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                    <img loading="lazy"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store['logo'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                        style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"
                                        alt="{{ $store->name }}">
                                    <div>
                                        <div style="font-weight: 800; font-size: 13px; color: var(--t13-dark);">Store
                                            Response</div>
                                        <div style="font-size: 11px; color: var(--t13-gray);">
                                            {{ _formatted_datetime($rev->replied_at) }}</div>
                                    </div>
                                </div>
                                <p style="font-size: 13px; color: var(--t13-gray); line-height: 1.7; margin: 0;">
                                    {{ $rev->reply }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($data['review_count'] > 2)
                    <div style="text-align: center; margin-top: 2.5rem;">
                        <a href="{{ route('store.reviews', [$store->slug]) }}" class="t13-btn-glow"
                            style="padding: 0.85rem 2.25rem; font-size: 14px;">
                            View All Reviews <i class="fas fa-arrow-right" style="margin-left: 0.75rem;"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Contact -->
    <div class="t13-section" id="contact" style="background: var(--t13-primary-bg);">
        <div class="t13-wrap">
            <div class="t13-section-header">
                <div class="t13-section-tag">Get In Touch</div>
                <h2 class="t13-section-title">Contact Us</h2>
            </div>

            <div class="t13-contact-tiles" style="margin-top: 2.5rem;">
                @php $contacts = [['icon' => 'map', 'title' => 'Location', 'value' => $store['address']], ['icon' => 'secured-letter', 'title' => 'Email', 'value' => $store['email'], 'link' => 'mailto:'], ['icon' => 'phone', 'title' => 'Phone', 'value' => $store['phone'], 'link' => 'tel:'], ['icon' => 'marker', 'title' => 'Directions', 'value' => 'View Map', 'modal' => true]]; @endphp

                @foreach ($contacts as $index => $contact)
                    <div class="t13-tile {{ $index % 2 == 0 ? 't13-slide-left' : 't13-slide-right' }}"
                        style="transition-delay: {{ $index * 0.1 }}s;">
                        <div class="t13-tile-icon">
                            <img loading="lazy" src="https://img.icons8.com/ios-filled/50/{{ $contact['icon'] }}.png"
                                alt="{{ $contact['title'] }}"
                                style="width: 28px; height: 28px; filter: sepia(1) saturate(10) hue-rotate(240deg) brightness(0.6);">
                        </div>
                        <div
                            style="font-size: 1.125rem; font-weight: 800; color: var(--t13-dark); margin-bottom: 0.5rem; position: relative; z-index: 1;">
                            {{ $contact['title'] }}</div>
                        <div style="font-size: 13px; color: var(--t13-gray); position: relative; z-index: 1;">
                            @if (isset($contact['link']))
                                <a href="{{ $contact['link'] }}{{ $contact['value'] }}"
                                    style="color: var(--t13-primary); text-decoration: none; font-weight: 600;">{{ $contact['value'] }}</a>
                            @elseif (isset($contact['modal']))
                                <a class="cursor-pointer" data-bs-toggle="modal" data-bs-target="#exampleModal"
                                    style="color: var(--t13-primary); text-decoration: none; font-weight: 600;">{{ $contact['value'] }}</a>
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
    <div class="t13-section" id="about" style="background: white;">
        <div class="t13-wrap">
            <div class="t13-section-header">
                <div class="t13-section-tag">About</div>
                <h2 class="t13-section-title">Our Story</h2>
            </div>

            <div class="t13-slide-left about_section" style="">
                {!! $data['store_config']->about_us ?? 'Information coming soon.' !!}
            </div>
        </div>
    </div>

    <!-- Map Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 20px; border: none;">
                <div class="modal-header"
                    style="background: linear-gradient(135deg, var(--t13-primary), var(--t13-accent)); color: white; border-radius: 20px 20px 0 0;">
                    <h5 class="modal-title">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="map" style="height: 450px; border-radius: 10px;"></div>
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
        /* ===== SPARKLE CURSOR EFFECT ===== */
        (function() {
            const canvas = document.getElementById('sparkleCanvas');
            const ctx = canvas.getContext('2d');
            let particles = [];
            let mouseX = 0,
                mouseY = 0;
            let animId;

            function resize() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }
            resize();
            window.addEventListener('resize', resize);

            document.addEventListener('mousemove', function(e) {
                mouseX = e.clientX;
                mouseY = e.clientY;
                // Spawn 2-3 sparkles per move
                for (let i = 0; i < 2; i++) {
                    particles.push({
                        x: mouseX + (Math.random() - 0.5) * 20,
                        y: mouseY + (Math.random() - 0.5) * 20,
                        size: Math.random() * 4 + 1.5,
                        speedX: (Math.random() - 0.5) * 2,
                        speedY: (Math.random() - 0.5) * 2 - 1,
                        life: 1,
                        decay: Math.random() * 0.025 + 0.015,
                        color: Math.random() > 0.5 ? '#7c3aed' : '#f472b6',
                        rotation: Math.random() * Math.PI * 2,
                        rotationSpeed: (Math.random() - 0.5) * 0.15
                    });
                }
            });

            function drawStar(cx, cy, size, rotation) {
                ctx.save();
                ctx.translate(cx, cy);
                ctx.rotate(rotation);
                ctx.beginPath();
                for (let i = 0; i < 4; i++) {
                    const angle = (i * Math.PI) / 2;
                    ctx.moveTo(0, 0);
                    ctx.lineTo(Math.cos(angle) * size, Math.sin(angle) * size);
                }
                ctx.stroke();
                // Diamond sparkle shape
                ctx.beginPath();
                ctx.moveTo(0, -size);
                ctx.lineTo(size * 0.35, 0);
                ctx.lineTo(0, size);
                ctx.lineTo(-size * 0.35, 0);
                ctx.closePath();
                ctx.fill();
                ctx.restore();
            }

            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                for (let i = particles.length - 1; i >= 0; i--) {
                    const p = particles[i];
                    p.x += p.speedX;
                    p.y += p.speedY;
                    p.life -= p.decay;
                    p.rotation += p.rotationSpeed;
                    p.speedY += 0.02; // slight gravity

                    if (p.life <= 0) {
                        particles.splice(i, 1);
                        continue;
                    }

                    ctx.globalAlpha = p.life;
                    ctx.fillStyle = p.color;
                    ctx.strokeStyle = p.color;
                    ctx.lineWidth = 0.5;
                    drawStar(p.x, p.y, p.size, p.rotation);
                }

                // Cap particles
                if (particles.length > 120) {
                    particles = particles.slice(-120);
                }

                ctx.globalAlpha = 1;
                animId = requestAnimationFrame(animate);
            }
            animate();
        })();

        /* ===== SCROLL ANIMATIONS (Left/Right) ===== */
        const t13Observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, {
            threshold: 0.08,
            rootMargin: '0px'
        });

        document.querySelectorAll('.t13-slide-left, .t13-slide-right, .t13-fade-up, .t13-zoom-in').forEach(el => {
            t13Observer.observe(el);
        });

        /* ===== RATING STARS ===== */
        document.querySelectorAll('.rating-stars').forEach(el => {
            const rating = parseFloat(el.getAttribute('data-rating'));
            const percentage = (Math.min(rating, 5) / 5) * 100;
            const fill = el.querySelector('.stars-fill');
            if (fill) {
                fill.style.width = `${percentage}%`;
            }
        });

        /* ===== LIGHTGALLERY ===== */
        if (document.querySelector('.t13-gallery')) {
            lightGallery(document.querySelector('.t13-gallery'), {
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
