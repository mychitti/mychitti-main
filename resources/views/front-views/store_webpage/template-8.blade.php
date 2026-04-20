@extends('front-views.layout')

@section('title', $store['meta_title'] ?? ($data['store_config']?->webpage_name ?? $store['name']))

@section('meta_keywords', $keywords)
@section('meta_description', $store['meta_description'])


@push('css_or_js') 
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --coral: #ff6b6b;
            --navy: #1e3a8a;
            --mint: #2dd4bf;
            --peach: #ffd93d;
            --dark-navy: #0f172a;
            --light-mint: #ccfbf1;
            --light-coral: #ffe5e5;
            --gray-text: #475569;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', 'Arial', sans-serif;
            background: #fafafa;
            color: var(--dark-navy);
            line-height: 1.7;
        }

        /* Unique Side Navigation */
        .side-nav {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 90px;
            background: var(--navy);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem 0;
            gap: 2rem;
        }

        .side-logo {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            object-fit: cover;
            border: 3px solid var(--coral);
        }

        .side-menu {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            margin-top: 2rem;
        }

        .side-menu a {
            color: white;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            padding: 0.75rem;
            border-radius: 12px;
        }

        .side-menu a i {
            font-size: 24px;
        }

        .side-menu a span {
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .side-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--mint);
        }

        .side-cta {
            margin-top: auto;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            background: var(--coral);
            color: white;
            padding: 1.5rem 0.75rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .side-cta:hover {
            background: var(--mint);
            color: var(--navy);
        }

        .mobile-nav-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: var(--navy);
            color: white;
            border: none;
            font-size: 20px;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 90px;
            min-height: 100vh;
        }

        @media (max-width: 768px) {
            .mobile-nav-toggle {
                display: none;
            }

            .side-nav {
                position: fixed;
                top: auto;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                height: auto;
                flex-direction: row;
                padding: 0;
                gap: 0;
                border-radius: 16px 16px 0 0;
                box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
                z-index: 1000;
            }

            .side-logo {
                display: none;
            }

            .side-menu {
                flex-direction: row;
                margin: 0;
                gap: 0;
                flex: 1;
                justify-content: space-around;
                padding: 8px 0;
            }

            .side-menu a {
                padding: 6px 8px;
                border-radius: 8px;
                gap: 2px;
            }

            .side-menu a i {
                font-size: 18px;
            }

            .side-menu a span {
                font-size: 8px;
            }

            .side-cta {
                writing-mode: horizontal-tb;
                text-orientation: initial;
                margin: 0;
                padding: 6px 12px;
                border-radius: 8px;
                font-size: 8px;
                align-self: center;
                margin-right: 8px;
            }

            .main-content {
                margin-left: 0;
                padding-bottom: 65px;
            }
        }

        /* Asymmetric Hero */
        .hero-asymmetric {
            display: grid;
            grid-template-columns: 45% 55%;
            min-height: 600px;
            background: var(--navy);
            position: relative;
            overflow: hidden;
        }

        .hero-asymmetric::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 60%;
            height: 200%;
            background: radial-gradient(circle, var(--coral) 0%, transparent 70%);
            opacity: 0.15;
        }

        .hero-left {
            background: var(--coral);
            padding: 4rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            clip-path: polygon(0 0, 100% 0, 85% 100%, 0 100%);
        }

        .hero-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="60" height="60" xmlns="http://www.w3.org/2000/svg"><circle cx="30" cy="30" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
        }

        .hero-content-left {
            position: relative;
            z-index: 1;
            color: white;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1.25rem;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 2rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .hero-title {
            color: white;
            font-size: 4rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 1.5rem;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .hero-subtitle {
            font-size: 1.25rem;
            opacity: 0.95;
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn-hero-primary {
            background: white;
            color: var(--coral);
            padding: 1.125rem 2.5rem;
            border-radius: 50px;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
            color: var(--coral);
        }

        .btn-hero-secondary {
            background: transparent;
            color: white;
            padding: 1.125rem 2.5rem;
            border-radius: 50px;
            font-weight: 800;
            text-decoration: none;
            border: 3px solid white;
            transition: all 0.3s;
        }

        .btn-hero-secondary:hover {
            background: white;
            color: var(--coral);
        }

        .hero-right {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .hero-image-card {
            background: white;
            padding: 1.5rem;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            transform: rotate(-2deg);
            transition: transform 0.3s;
        }

        .hero-image-card:hover {
            transform: rotate(0deg);
        }

        .hero-cover {
            width: 100%;
            height: 350px;
            object-fit: cover;
            border-radius: 20px;
        }

        .hero-stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .stat-mini {
            background: var(--navy);
            padding: 1rem;
            border-radius: 15px;
            text-align: center;
            color: white;
        }

        .stat-mini-value {
            font-size: 1.75rem;
            font-weight: 900;
            color: var(--mint);
            line-height: 1;
        }

        .stat-mini-label {
            font-size: 10px;
            margin-top: 0.5rem;
            opacity: 0.8;
            text-transform: uppercase;
        }

        /* Breadcrumb */
        .breadcrumb-unique {
            background: white;
            padding: 1.25rem 0;
            border-bottom: 3px solid var(--light-mint);
        }

        .breadcrumb-unique .breadcrumb {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 3rem;
            background: transparent;
            font-size: 13px;
            font-weight: 600;
        }

        .breadcrumb-item a {
            color: var(--coral);
            text-decoration: none;
        }

        /* Split Info Section */
        .split-info {
            display: grid;
            grid-template-columns: 60% 40%;
            gap: 0;
            background: white;
        }

        .info-main {
            padding: 4rem 3rem;
            background: var(--light-mint);
        }

        .info-main h2 {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--navy);
            margin-bottom: 1.5rem;
        }

        .info-text {
            color: var(--gray-text);
            font-size: 15px;
            line-height: 1.9;
        }

        .info-sidebar {
            background: var(--navy);
            color: white;
            padding: 4rem 2.5rem;
        }

        .info-sidebar h3 {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 2rem;
            color: var(--mint);
        }

        .contact-row {
            display: flex;
            align-items: start;
            gap: 1.25rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .contact-row:last-child {
            border: none;
        }

        .contact-icon-box {
            width: 50px;
            height: 50px;
            background: var(--coral);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .contact-info h4 {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--mint);
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
        }

        .contact-info p {
            font-size: 14px;
            margin: 0;
        }

        .contact-info a {
            color: white;
            text-decoration: none;
        }

        .rating-display {
            background: var(--coral);
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            margin-top: 2rem;
        }

        .rating-big {
            font-size: 4rem;
            font-weight: 900;
            line-height: 1;
            color: white;
        }

        .stars-display {
            margin: 1rem 0;
        }

        .stars-display i {
            font-size: 22px;
            color: var(--peach);
        }

        .rating-info {
            font-size: 13px;
            opacity: 0.9;
        }

        /* Announcement */
        .announcement-banner {
            background: linear-gradient(135deg, var(--mint) 0%, #14b8a6 100%);
            color: white;
            padding: 1.5rem 3rem;
            margin: 2rem 0;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .announcement-icon {
            font-size: 32px;
        }

        .announcement-text {
            font-size: 16px;
            font-weight: 700;
        }

        /* Masonry Products Section */
        .products-section-unique {
            padding: 1rem 3rem;
            background: #fafafa;
        }

        .section-header-unique {
            text-align: center;
            margin-bottom: 1rem;
        }

        .section-super-title {
            font-size: 14px;
            font-weight: 800;
            color: var(--coral);
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 1rem;
        }

        .section-main-title {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--navy);
            line-height: 1.1;
        }

        .category-section {
            margin: 4rem 0;
        }

        .category-header-unique {
            background: var(--navy);
            color: white;
            padding: 23px;
            border-radius: 21px;
            margin-bottom: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .category-header-unique::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 100%;
            background: #ffefef;
            opacity: 0.2;
            transform: skewX(-20deg);
        }

        .category-header-unique h3 {
            font-size: 21px;
            font-weight: 900;
            margin: 0;
            position: relative;
            z-index: 1;
            color: white;
        }

        /* Masonry Grid */
        .masonry-grid {
            column-count: 5;
            column-gap: 2rem;
        }

        .masonry-item {
            break-inside: avoid;
            margin-bottom: 2rem;
        }

        .product-card-unique {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.4s;
            position: relative;
        }

        .product-card-unique:hover {
            transform: translateY(-12px) rotate(1deg);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .product-image-unique {
            position: relative;
            width: 100%;
            padding-top: 100%;
            overflow: hidden;
        }

        .product-image-unique img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s;
        }

        .product-card-unique:hover .product-image-unique img {
            transform: scale(1.15);
        }

        .discount-ribbon {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--coral);
            color: white;
            padding: 0.6rem 1.25rem;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 900;
            z-index: 10;
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .time-ribbon {
            position: absolute;
            top: 15px;
            right: 15px;
            background: white;
            color: var(--navy);
            padding: 0.6rem 1.25rem;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .wishlist-heart {
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
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            transition: all 0.3s;
            z-index: 10;
        }

        .wishlist-heart:hover {
            transform: scale(1.2);
            background: var(--coral);
        }

        .wishlist-heart:hover i {
            color: white !important;
        }

        .wishlist-heart i {
            font-size: 20px;
        }

        .text_red {
            color: var(--coral);
        }

        .text_grey {
            color: #cbd5e1;
        }

        .product-details-unique {
            padding: 1.75rem;
        }

        .product-name-unique {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 60px;
        }

        .product-variant-unique {
            font-size: 13px;
            color: var(--gray-text);
            margin-bottom: 1rem;
            min-height: 20px;
        }

        .variant-pill {
            display: inline-block;
            background: var(--light-coral);
            color: var(--coral);
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 0.5rem;
        }

        .product-price-unique {
            display: flex;
            align-items: baseline;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .price-main {
            font-size: 2rem;
            font-weight: 900;
            color: var(--navy);
        }

        .price-strike {
            font-size: 1.125rem;
            color: var(--gray-text);
            text-decoration: line-through;
        }

        .product-cta {
            margin-top: 1.25rem;
        }

        .btn-product {
            width: 100%;
            padding: 1.125rem;
            border: none;
            border-radius: 30px;
            font-weight: 800;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            text-transform: uppercase;
        }

        .btn-add-product {
            background: var(--navy);
            color: white;
        }

        .btn-add-product:hover {
            background: var(--coral);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3);
        }

        .btn-remove-product {
            background: var(--light-coral);
            color: var(--coral);
        }

        .btn-remove-product:hover {
            background: var(--coral);
            color: white;
        }

        .btn-enquiry-product {
            background: var(--mint);
            color: var(--navy);
        }

        .btn-enquiry-product:hover {
            background: var(--navy);
            color: white;
        }

        /* Gallery Mosaic */
        .gallery-section-unique {
            padding: 1rem 3rem;
            background: white;
        }

        .gallery-mosaic {
            margin-top: 3rem;

            display: grid;
            grid-template-columns: repeat(13, 1fr);
            grid-auto-rows: 179px;
            gap: 1rem;
        }

        .gallery-tile {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            cursor: pointer;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s;
        }

        .gallery-tile:nth-child(1) {
            grid-column: span 2;
            grid-row: span 2;
        }

        .gallery-tile:nth-child(2) {
            grid-column: span 2;
        }

        .gallery-tile:nth-child(3) {
            grid-column: span 2;
        }

        .gallery-tile:nth-child(4) {
            grid-column: span 3;
        }

        .gallery-tile:nth-child(5) {
            grid-column: span 3;
        }

        .gallery-tile:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        .gallery-tile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s;
        }

        .gallery-tile:hover img {
            transform: scale(1.2);
        }

        /* Reviews Unique */
        .reviews-section-unique {
            padding: 1rem 3rem;
            background: var(--light-mint);
        }

        .review-card-unique {
            background: white;
            border-radius: 25px;
            padding: 1.5rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
            border-left: 6px solid var(--coral);
            transition: all 0.3s;
        }

        .review-card-unique:hover {
            transform: translateX(10px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
        }

        .review-top {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .reviewer-photo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--coral);
        }

        .reviewer-data {
            flex: 1;
        }

        .reviewer-name-unique {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--navy);
            margin-bottom: 0.5rem;
        }

        .review-date-unique {
            font-size: 12px;
            color: var(--gray-text);
            margin-bottom: 0.75rem;
        }

        .review-stars-unique {
            display: flex;
            gap: 0.25rem;
        }

        .review-stars-unique i {
            font-size: 16px;
            color: var(--peach);
        }

        .review-message {
            color: var(--gray-text);
            font-size: 15px;
            line-height: 1.9;
        }

        .review-photos {
            display: flex;
            gap: 1.25rem;
            margin-top: 1.5rem;
        }

        .review-photo {
            width: 100px;
            height: 100px;
            border-radius: 15px;
            object-fit: cover;
            border: 3px solid var(--light-coral);
            transition: all 0.3s;
            cursor: pointer;
        }

        .review-photo:hover {
            transform: scale(1.1);
            border-color: var(--coral);
        }

        .store-response {
            margin-top: 1rem;
            padding: 0.75rem;
            background: var(--light-coral);
            border-left: 4px solid var(--navy);
            border-radius: 15px;
        }

        .response-top {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .store-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
        }

        .response-message {
            font-size: 14px;
            color: var(--gray-text);
            line-height: 1.8;
        }

        /* Contact Section */
        .contact-section-unique {
            padding: 5rem 3rem;
            background: white;
        }

        .contact-grid-unique {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2.5rem;
            margin-top: 3rem;
        }

        .contact-tile {
            background: var(--navy);
            color: white;
            padding: 3rem;
            border-radius: 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.4s;
        }

        .contact-tile::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, var(--coral) 0%, transparent 60%);
            opacity: 0;
            transition: opacity 0.4s;
        }

        .contact-tile:hover::before {
            opacity: 0.2;
        }

        .contact-tile:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(30, 58, 138, 0.3);
        }

        .contact-icon-unique {
            width: 80px;
            height: 80px;
            background: var(--coral);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.75rem;
            position: relative;
            z-index: 1;
        }

        .contact-icon-unique img {
            width: 40px;
            height: 40px;
            filter: brightness(0) invert(1);
        }

        .contact-heading {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--mint);
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .contact-detail {
            color: white;
            font-size: 15px;
            position: relative;
            z-index: 1;
        }

        .contact-detail a {
            color: white;
            text-decoration: none;
            font-weight: 700;
        }

        /* About Section */
        .about-section-unique {
            padding: 5rem 3rem;
            background: var(--light-mint);
        }

        .about-box-unique {
            background: white;
            border-radius: 25px;
            padding: 3.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
            border-top: 8px solid var(--navy);
            line-height: 2;
            font-size: 15px;
        }

        /* Banner */
        .banner-unique {
            margin: 3rem 0;
        }

        .banner-unique img {
            width: 100%;
            border-radius: 25px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
        }

        /* Map Modal */
        #map {
            height: 500px;
            width: 100%;
            border-radius: 15px;
        }

        .modal-content {
            border-radius: 25px;
            border: none;
        }

        .modal-header {
            background: var(--navy);
            color: white;
            border-radius: 25px 25px 0 0;
        }

        /* LightGallery */
        .lg-counter {
            background: rgba(30, 58, 138, 0.9) !important;
            padding: 10px 20px !important;
            border-radius: 30px !important;
        }

        .lg-next.lg-icon,
        .lg-prev.lg-icon {
            background: var(--coral);
            color: white;
            border-radius: 50%;
        }

        /* Responsive */
        @media (max-width: 1400px) {
            .masonry-grid {
                column-count: 3;
            }
        }

        @media (max-width: 1024px) {


            .hero-asymmetric {
                grid-template-columns: 1fr;
            }

            .hero-left {
                clip-path: none;
            }

            .split-info {
                grid-template-columns: 1fr;
            }

            .masonry-grid {
                column-count: 2;
            }

            .gallery-mosaic {
                grid-template-columns: repeat(3, 1fr);
            }

            .contact-grid-unique {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .wishlist-heart {
                bottom: 6px;
                right: 6px;
                width: 30px;
                height: 30px;

            }

            .hero-asymmetric {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .hero-left {
                clip-path: none;
                padding: 2rem 1.25rem;
            }

            .hero-title {
                font-size: 1.75rem;
                margin-bottom: 1rem;
            }

            .hero-subtitle {
                font-size: 1rem;
                margin-bottom: 1.5rem;
            }

            .hero-badge {
                font-size: 11px;
                padding: 0.35rem 1rem;
                margin-bottom: 1rem;
            }

            .hero-buttons {
                flex-direction: column;
                gap: 0.75rem;
            }

            .btn-hero-primary,
            .btn-hero-secondary {
                padding: 0.85rem 1.5rem;
                text-align: center;
                font-size: 13px;
            }

            .hero-right {
                padding: 1.25rem;
            }

            .hero-image-card {
                padding: 0.75rem;
                border-radius: 16px;
                transform: none;
            }

            .hero-cover {
                height: auto;
                border-radius: 12px;
            }

            .hero-stats-row {
                gap: 0.5rem;
                margin-top: 0.75rem;
            }

            .stat-mini {
                padding: 0.6rem;
                border-radius: 10px;
            }

            .stat-mini-value {
                font-size: 1.25rem;
            }

            .stat-mini-label {
                font-size: 8px;
            }

            .breadcrumb-unique {
                padding: 0.75rem 0;
            }

            .breadcrumb-unique .breadcrumb {
                padding: 0 1.25rem;
            }

            .split-info {
                grid-template-columns: 1fr;
            }

            .info-main {
                padding: 1.5rem 1.25rem;
            }

            .info-main h2 {
                font-size: 1.5rem;
            }

            .info-sidebar {
                padding: 1.5rem 1.25rem;
            }

            .info-sidebar h3 {
                font-size: 1.25rem;
                margin-bottom: 1.25rem;
            }

            .contact-row {
                gap: 1rem;
                margin-bottom: 1.25rem;
                padding-bottom: 1.25rem;
            }

            .contact-icon-box {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .rating-display {
                padding: 1.25rem;
                border-radius: 14px;
            }

            .rating-big {
                font-size: 2.5rem;
            }

            .announcement-banner {
                padding: 0.6rem 1.25rem;
                margin: 1rem 0;
                gap: 0.75rem;
            }

            .announcement-icon {
                font-size: 18px;
            }

            .announcement-text {
                font-size: 12px;
            }

            .products-section-unique,
            .gallery-section-unique,
            .reviews-section-unique {
                padding: 2rem 1.25rem;
            }

            .contact-section-unique,
            .about-section-unique {
                padding: 2rem 1.25rem;
            }

            .section-main-title {
                font-size: 1.5rem;
            }

            .section-super-title {
                font-size: 11px;
                letter-spacing: 2px;
            }

            .category-section {
                margin: 2rem 0;
            }

            .category-header-unique {
                padding: 12px 16px;
                border-radius: 14px;
                margin-bottom: 1.25rem;
            }

            .category-header-unique h3 {
                font-size: 16px;
            }

            .masonry-grid {
                column-count: 2;
                column-gap: 0.75rem;
            }

            .masonry-item {
                margin-bottom: 0.75rem;
            }

            .product-card-unique {
                border-radius: 14px;
            }

            .product-card-unique:hover {
                transform: none;
            }

            .product-details-unique {
                padding: 0.75rem;
            }

            .product-name-unique {
                font-size: 0.85rem;
                min-height: 38px;
                margin-bottom: 0.5rem;
            }

            .product-price-unique {
                margin: 0.75rem 0;
                gap: 0.5rem;
            }

            .price-main {
                font-size: 1.15rem;
            }

            .price-strike {
                font-size: 0.8rem;
            }

            .btn-product {
                padding: 0.6rem;
                border-radius: 20px;
                font-size: 11px;
                gap: 0.35rem;
            }

            .gallery-mosaic {
                grid-template-columns: repeat(3, 1fr);
                grid-auto-rows: 100px;
                gap: 0.5rem;
            }

            .gallery-tile {
                grid-column: span 1 !important;
                grid-row: span 1 !important;
                border-radius: 10px;
            }

            .contact-grid-unique {
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }

            .contact-tile {
                padding: 1.25rem;
                border-radius: 14px;
            }

            .contact-icon-unique {
                width: 50px;
                height: 50px;
                margin-bottom: 0.75rem;
            }

            .contact-icon-unique img {
                width: 24px;
                height: 24px;
            }

            .contact-heading {
                font-size: 1rem;
                margin-bottom: 0.5rem;
            }

            .contact-detail {
                font-size: 12px;
            }

            .about-box-unique {
                padding: 1.5rem;
                border-radius: 14px;
            }

            .review-card-unique {
                padding: 1rem;
                border-radius: 14px;
                margin-bottom: 1.25rem;
            }

            .reviewer-photo {
                width: 50px;
                height: 50px;
            }

            .reviewer-name-unique {
                font-size: 1rem;
            }

            .review-photo {
                width: 60px;
                height: 60px;
                border-radius: 10px;
            }
        }

        /* Utilities */
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
            color: var(--peach);
            width: 0;
        }

        @media (max-width: 992px) {

            .secondary_nav,
            .page-header {
                margin-top: 0px !important;
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
    <!-- Side Navigation -->
    <nav class="side-nav">
        <img loading="lazy" class="side-logo" src="{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}"
            alt="{{ $store['name'] }}">

        <div class="side-menu">
            <a href="#services">
                <i class="fas fa-th-large"></i>
                <span>Services</span>
            </a>
            <a href="#reviews">
                <i class="fas fa-star"></i>
                <span>Reviews</span>
            </a>
            <a href="#contact">
                <i class="fas fa-envelope"></i>
                <span>Contact</span>
            </a>
            <a href="#about">
                <i class="fas fa-info-circle"></i>
                <span>About</span>
            </a>
        </div>

        <a href="{{ route('store.gallery', [$store['slug']]) }}" class="side-cta">GALLERY</a>
    </nav>

    <button class="mobile-nav-toggle" onclick="document.querySelector('.side-nav').classList.toggle('show')">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Asymmetric Hero -->
        <div class="hero-asymmetric">
            <div class="hero-left">
                <div class="hero-content-left">
                    <span class="hero-badge">⭐ Premium Quality</span>
                    <h1 class="hero-title">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                    <p class="hero-subtitle">{{ $store['meta_title'] }}</p>
                    <div class="hero-buttons">
                        <a href="#services" class="btn-hero-primary">Explore Now</a>
                        <a href="#contact" class="btn-hero-secondary">Contact Us</a>
                    </div>
                </div>
            </div>

            <div class="hero-right">
                <div class="hero-image-card">
                    <img loading="lazy" class="hero-cover"
                        src="{{ asset('storage/app/public/store/cover/') . '/' . $store['cover_photo'] }}" alt="Cover">

                    <div class="hero-stats-row">
                        @php $store_rating = number_format($store->average_rating, 1); @endphp
                        <div class="stat-mini">
                            <div class="stat-mini-value">{{ $store_rating }}</div>
                            <div class="stat-mini-label">Rating</div>
                        </div>
                        <div class="stat-mini">
                            <div class="stat-mini-value">{{ $store->rating_count }}+</div>
                            <div class="stat-mini-label">Reviews</div>
                        </div>
                        <div class="stat-mini">
                            <div class="stat-mini-value">{{ count($productdata) }}+</div>
                            <div class="stat-mini-label">Items</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Breadcrumb -->
        <div class="breadcrumb-unique">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</li>
                </ol>
            </nav>
        </div>

        <!-- Split Info Section -->
        <div class="split-info">
            <div class="info-main">
                <h2>Discover Our Story</h2>
                @php
                    $description = $store['meta_description'];
                    $short = Str::limit($description, 350);
                @endphp
                <div class="info-text" id="text-{{ $store['id'] }}">
                    {!! $short !!}
                    @if (strlen($description) > 350)
                        <span id="dots-{{ $store['id'] }}"></span>
                        <span id="more-{{ $store['id'] }}" class="d-none">{!! substr($description, 350) !!}</span>
                        <a class="cursor-pointer" style="color: var(--coral); font-weight: 900; text-decoration: underline;"
                            onclick="toggleReadMore({{ $store['id'] }})" id="btn-{{ $store['id'] }}">Read more →</a>
                    @endif
                </div>
            </div>

            <div class="info-sidebar">
                <h3>Get In Touch</h3>

                <div class="contact-row">
                    <div class="contact-icon-box">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="contact-info">
                        <h4>Phone</h4>
                        @php
                            $phones = $data['store_config']?->webpage_phones;
                            if ($phones) {
                                $phones = json_decode($phones, true);
                            } else {
                                $phones = [];
                            }
                        @endphp
                        <p>
                            @if (!empty($phones))
                                {{ implode(', ', $phones) }}
                            @else
                                {{ $store['phone'] }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="contact-row">
                    <div class="contact-icon-box">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-info">
                        <h4>Email</h4>
                        <p><a
                                href="mailto:{{ $store['email'] }}">{{ $data['store_config']?->webpage_email ?? $store->email }}</a>
                        </p>
                    </div>
                </div>

                <div class="contact-row">
                    <div class="contact-icon-box">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-info">
                        <h4>Address</h4>
                        <p>{{ $store['address'] }}</p>
                    </div>
                </div>

                <div class="rating-display">
                    @php $store_rating = number_format($store->average_rating, 1); @endphp
                    <div class="rating-big">{{ $store_rating }}</div>
                    <div class="stars-display rating-stars" data-rating="{{ $store_rating }}">
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
                    <div class="rating-info">{{ $store->rating_count }} Customer Reviews</div>
                </div>

                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                    <div class="sharethis-inline-share-buttons"></div>
                </div>
            </div>
        </div>

        <!-- Banners -->
        @if (count($data['banners']) > 0)
            <div style="padding: 0 3rem;">
                <div class="banner-unique">
                    <div class="owl-carousel banner-carousel">
                        @foreach ($data['banners'] as $value)
                            <a href="{{ $value->default_link ?? '#' }}" onclick="trackBannerClick({{ $value->id }})">
                                <img loading="lazy" src="{{ asset('storage/app/public/banner/') . '/' . $value->image }}"
                                    alt="banner">
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Announcement -->
        @if ($store->announcement)
            <div class="announcement-banner">
                <i class="fas fa-bullhorn announcement-icon"></i>
                <div class="announcement-text">{{ $store->announcement_message }}</div>
            </div>
        @endif

        <!-- Products Section with Masonry -->
        <div class="products-section-unique" id="services">

            <div class="section-header-unique">
                <div class="section-super-title">WHAT WE OFFER</div>
                <h2 class="section-main-title">Our Products & Services</h2>
            </div>

            @foreach ($productdata as $key => $cat)
                <div class="category-section">
                    <div class="category-header-unique">
                        <h3>{{ $cat->name }}</h3>
                    </div>

                    <div class="masonry-grid">
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
                            <div class="masonry-item pr_{{ $pro->id }}">
                                <div class="product-card-unique">
                                    <div class="product-image-unique">
                                        <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                            <img loading="lazy"
                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                                alt="{{ $pro->name }}">
                                        </a>

                                        @if ($module == 5 && $store->delivery_time)
                                            <div class="time-ribbon">
                                                <i class="fas fa-bolt"></i>
                                                {{ strtoupper($store->delivery_time) }}
                                            </div>
                                        @endif

                                        @if ($pro->discount > 0)
                                            <div class="discount-ribbon">
                                                -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                            </div>
                                        @endif

                                        <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                            class="prHeart_{{ $pro->id }} wishlist-heart">
                                            <i
                                                class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                        </div>
                                    </div>

                                    <div class="product-details-unique">
                                        <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                            <h4 class="product-name-unique" title="{{ ucfirst($pro->name) }}">
                                                {{ ucfirst($pro->name) }}
                                            </h4>
                                        </a>

                                        @if ($module == 5)
                                            <p class="product-variant-unique">
                                                {{ !empty($variations) ? $variations[0]->type : '' }}
                                            </p>
                                            @if (count($variations) > 1)
                                                <span class="variant-pill">+{{ count($variations) - 1 }} options</span>
                                            @endif

                                            <div class="product-price-unique">
                                                <div class="price-main">{{ _price($selling_price) }}</div>
                                                @if ($pro->discount > 0)
                                                    <div class="price-strike">{{ _price($mrp) }}</div>
                                                @endif
                                            </div>

                                            <div class="product-cta cartSec_{{ $pro->id }}">
                                                @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                                @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                    <button
                                                        onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                        class="btn-product btn-remove-product">
                                                        <i class="fa fa-times"></i> Remove
                                                    </button>
                                                @else
                                                    <button
                                                        onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                        class="btn-product btn-add-product">
                                                        <i class="fa fa-cart-plus"></i> Add
                                                    </button>
                                                @endif
                                            </div>
                                        @else
                                            @if ($pro->item_type == 'product')
                                                <div class="product-price-unique">
                                                    <div class="price-main">{{ _price($selling_price) }}</div>
                                                    @if ($pro->discount > 0 || $mrp > $selling_price)
                                                        <div class="price-strike">{{ _price($mrp) }}</div>
                                                    @endif
                                                </div>
                                            @endif

                                            <div class="product-cta">
                                                @if ($pro->item_type == 'product' && ($pro->stock ?? 1) <= 0)
                                                    <span class="badge bg-danger">Out of Stock</span>
                                                @elseif (($data['store_config']->lead_available ?? 1) == 0)
                                                    <button disabled class="btn-product btn-enquiry-product" style="opacity:0.5;cursor:not-allowed;">
                                                        <i class="fas fa-paper-plane"></i> Enquire
                                                    </button>
                                                    <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                                @elseif (auth('web')->user())
                                                    <button
                                                        onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                        class="btn-product btn-enquiry-product">
                                                        <i class="fas fa-paper-plane"></i> Enquire
                                                    </button>
                                                @else
                                                    <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                        class="btn-product btn-enquiry-product">
                                                        <i class="fas fa-paper-plane"></i> Enquire
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @foreach ($invItemdata as $key => $cat)
                <div class="category-section">
                    <div class="category-header-unique">
                        <h3>{{ $cat->name }}</h3>
                    </div>

                    <div class="masonry-grid">
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
                            <div class="masonry-item pr_{{ $pro->id }}">
                                <div class="product-card-unique">
                                    <div class="product-image-unique">
                                        <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                            <img loading="lazy"
                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                                alt="{{ $pro->name }}">
                                        </a>

                                        @if ($module == 5 && $store->delivery_time)
                                            <div class="time-ribbon">
                                                <i class="fas fa-bolt"></i>
                                                {{ strtoupper($store->delivery_time) }}
                                            </div>
                                        @endif

                                        @if ($pro->discount > 0)
                                            <div class="discount-ribbon">
                                                -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                            </div>
                                        @endif

                                        <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                            class="prHeart_{{ $pro->id }} wishlist-heart">
                                            <i
                                                class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                        </div>
                                    </div>

                                    <div class="product-details-unique">
                                        <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                            <h4 class="product-name-unique" title="{{ ucfirst($pro->name) }}">
                                                {{ ucfirst($pro->name) }}
                                            </h4>
                                        </a>

                                        @if ($module == 5)
                                            <p class="product-variant-unique">
                                                {{ !empty($variations) ? $variations[0]->type : '' }}
                                            </p>
                                            @if (count($variations) > 1)
                                                <span class="variant-pill">+{{ count($variations) - 1 }} options</span>
                                            @endif

                                            <div class="product-price-unique">
                                                <div class="price-main">{{ _price($selling_price) }}</div>
                                                @if ($pro->discount > 0)
                                                    <div class="price-strike">{{ _price($mrp) }}</div>
                                                @endif
                                            </div>

                                            <div class="product-cta cartSec_{{ $pro->id }}">
                                                @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                                @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                    <button
                                                        onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                        class="btn-product btn-remove-product">
                                                        <i class="fa fa-times"></i> Remove
                                                    </button>
                                                @else
                                                    <button
                                                        onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                        class="btn-product btn-add-product">
                                                        <i class="fa fa-cart-plus"></i> Add
                                                    </button>
                                                @endif
                                            </div>
                                        @else
                                            @if ($pro->item_type == 'product')
                                                <div class="product-price-unique">
                                                    <div class="price-main">{{ _price($selling_price) }}</div>
                                                    @if ($pro->discount > 0 || $mrp > $selling_price)
                                                        <div class="price-strike">{{ _price($mrp) }}</div>
                                                    @endif
                                                </div>
                                            @endif

                                            <div class="product-cta">
                                                @if ($pro->item_type == 'product' && ($pro->stock ?? 1) <= 0)
                                                    <span class="badge bg-danger">Out of Stock</span>
                                                @elseif (($data['store_config']->lead_available ?? 1) == 0)
                                                    <button disabled class="btn-product btn-enquiry-product" style="opacity:0.5;cursor:not-allowed;">
                                                        <i class="fas fa-paper-plane"></i> Enquire
                                                    </button>
                                                    <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                                @elseif (auth('web')->user())
                                                    <button
                                                        onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                        class="btn-product btn-enquiry-product">
                                                        <i class="fas fa-paper-plane"></i> Enquire
                                                    </button>
                                                @else
                                                    <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                        class="btn-product btn-enquiry-product">
                                                        <i class="fas fa-paper-plane"></i> Enquire
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if (!count($productdata))
                <div style="text-align: center; padding: 6rem 0; color: var(--gray-text);">
                    <i class="fas fa-box-open" style="font-size: 6rem; margin-bottom: 2rem; opacity: 0.2;"></i>
                    <p style="font-size: 1.5rem; font-weight: 800;">No products available</p>
                </div>
            @endif

        </div>

        <!-- Gallery Mosaic -->
        @if (count($store->galleries))
            <div class="gallery-section-unique">
                <div class="section-header-unique">
                    <div class="section-super-title">VISUAL JOURNEY</div>
                    <h2 class="section-main-title">Our Gallery</h2>
                </div>

                <div class="gallery-mosaic">
                    @foreach ($data['galleries'] as $key => $value)
                        <a target="_blank" href="{{ asset('storage/app/public/store/gallery') }}/{{ $value->image }}"
                            class="gallery-tile lightgallery-item">
                            <img loading="lazy"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                    $value['image'] ?? '',
                                    asset('storage/app/public/store/gallery') . '/' . $value['image'] ?? '',
                                    asset('public/assets/admin/img/160x160/img1.jpg'),
                                    'store/gallery/',
                                ) }}"
                                alt="Gallery {{ $key + 1 }}">
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Reviews -->
        @if (count($data['reviews']) && $module == 6)
            <div class="reviews-section-unique" id="reviews">
                <div class="section-header-unique">
                    <div class="section-super-title">TESTIMONIALS</div>
                    <h2 class="section-main-title">Customer Stories</h2>
                </div>

                @foreach ($data['reviews'] as $rev)
                    <div class="review-card-unique">
                        <div class="review-top">
                            <img loading="lazy"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/') . '/' . $rev->profile_image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                class="reviewer-photo" alt="{{ $rev->f_name }}">
                            <div class="reviewer-data">
                                <div class="reviewer-name-unique">{{ $rev->f_name . ' ' . $rev->l_name }}</div>
                                <div class="review-date-unique">{{ _formatted_datetime($rev->created_at) }}</div>
                                <div class="review-stars-unique">
                                    @for ($i = 1; $i < 6; $i++)
                                        <i class="fas fa-star"
                                            style="color: {{ $rev->rating >= $i ? 'var(--peach)' : '#e5e7eb' }};"></i>
                                    @endfor
                                </div>
                            </div>
                        </div>

                        <p class="review-message">{{ $rev->comment }}</p>

                        @if ($rev->attachment)
                            @php $attachments = json_decode($rev->attachment); @endphp
                            @if (!empty($attachments))
                                <div class="review-photos">
                                    @foreach ($attachments as $img)
                                        <a target="_blank"
                                            href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}">
                                            <img loading="lazy" class="review-photo"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                alt="review">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        @if ($rev->reply)
                            <div class="store-response">
                                <div class="response-top">
                                    <img loading="lazy"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store['logo'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                        class="store-avatar" alt="{{ $store->name }}">
                                    <div>
                                        <div style="font-weight: 900; font-size: 15px; color: var(--navy);">Store Response
                                        </div>
                                        <div style="font-size: 12px; color: var(--gray-text);">
                                            {{ _formatted_datetime($rev->replied_at) }}</div>
                                    </div>
                                </div>
                                <p class="response-message">{{ $rev->reply }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($data['review_count'] > 2)
                    <div style="text-align: center; margin-top: 3rem;">
                        <a href="{{ route('store.reviews', [$store->slug]) }}" class="btn-hero-primary"
                            style="display: inline-block;">
                            View All Reviews <i class="fas fa-arrow-right" style="margin-left: 1rem;"></i>
                        </a>
                    </div>
                @endif
            </div>
        @endif

        <!-- Contact Section -->
        <div class="contact-section-unique" id="contact">
            <div class="section-header-unique">
                <div class="section-super-title">REACH OUT</div>
                <h2 class="section-main-title">Contact Us</h2>
            </div>

            <div class="contact-grid-unique">
                <div class="contact-tile">
                    <div class="contact-icon-unique">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/map.png" alt="Address">
                    </div>
                    <div class="contact-heading">Visit Us</div>
                    <div class="contact-detail">{{ $store['address'] }}</div>
                </div>

                <div class="contact-tile">
                    <div class="contact-icon-unique">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/secured-letter.png" alt="Email">
                    </div>
                    <div class="contact-heading">Email Us</div>
                    <div class="contact-detail">
                        <a href="mailto:{{ $store['email'] }}">{{ $store['email'] }}</a>
                    </div>
                </div>

                <div class="contact-tile">
                    <div class="contact-icon-unique">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/phone.png" alt="Phone">
                    </div>
                    <div class="contact-heading">Call Us</div>
                    <div class="contact-detail">
                        <a href="tel:{{ $store['phone'] }}">{{ $store['phone'] }}</a>
                    </div>
                </div>

                <div class="contact-tile">
                    <div class="contact-icon-unique">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/marker.png" alt="Map">
                    </div>
                    <div class="contact-heading">Get Directions</div>
                    <div class="contact-detail">
                        <a class="cursor-pointer" data-bs-toggle="modal" data-bs-target="#exampleModal">View Map</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- About Section -->
        <div class="about-section-unique" id="about">
            <div class="section-header-unique">
                <div class="section-super-title">OUR STORY</div>
                <h2 class="section-main-title">About Us</h2>
            </div>

            <div class="about-box-unique">
                {!! $data['store_config']->about_us ?? 'Information coming soon.' !!}
            </div>
        </div>
    </div>

    <!-- Map Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
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
        if (document.querySelector('.gallery-mosaic')) {
            lightGallery(document.querySelector('.gallery-mosaic'), {
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
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
@endpush
