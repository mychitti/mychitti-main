@extends('front-views.layout')

@section('title', $store['meta_title'] ?? ($data['store_config']?->webpage_name ?? $store['name']))

@section('meta_keywords', $keywords)
@section('meta_description', $store['meta_description'])


@push('css_or_js') 
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root { 
            --corp-primary: #1a1a1a;
            --corp-secondary: #ff6b35;
            --corp-accent: #f7931e;
            --corp-dark: #0a0a0a;
            --corp-light: #f5f5f5;
            --corp-gray: #666666;
            --corp-border: #e0e0e0;
            --corp-success: #28a745;
            --corp-white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Roboto', 'Arial', sans-serif;
            background: var(--corp-white);
            color: var(--corp-primary);
            line-height: 1.6;
        }

        /* Corporate Navbar */
        .corporate-nav {
            background: var(--corp-dark);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 75px;
        }

        .brand-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .brand-logo {
            height: 50px;
            width: 50px;
            border-radius: 8px;
            object-fit: cover;
        }

        .brand-info h1 {
            color: var(--corp-white);
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin: 0;
        }

        .brand-info p {
            color: var(--corp-accent);
            font-size: 11px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-links {
            display: flex;
            gap: 3rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--corp-white);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            position: relative;
            transition: color 0.3s;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--corp-secondary);
            transition: width 0.3s;
        }

        .nav-links a:hover {
            color: var(--corp-accent);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .cta-button {
            background: linear-gradient(135deg, var(--corp-secondary) 0%, var(--corp-accent) 100%);
            color: white;
            padding: 0.85rem 2rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(255, 107, 53, 0.4);
            color: white;
        }

        /* Hero Banner */
        .hero-banner {
            margin-top: 75px;
            position: relative;
            height: 500px;
            overflow: hidden;
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(26, 26, 26, 0.85) 0%, rgba(255, 107, 53, 0.7) 100%);
            z-index: 1;
        }

        .hero-banner::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="50" height="50" patternUnits="userSpaceOnUse"><path d="M 50 0 L 0 0 0 50" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            z-index: 1;
        }

        .hero-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2.5rem;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .hero-text {
            max-width: 700px;
            color: white;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1.25rem;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .hero-title {
            color:white;
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1rem;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
        }

        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            font-weight: 400;
        }

        .hero-stats {
            display: flex;
            gap: 3rem;
            margin-top: 2rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-info h3 {
            color:wheat;
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            line-height: 1;
        }

        .stat-info p {
            font-size: 12px;
            opacity: 0.9;
            margin: 0;
        }

        /* Breadcrumb */
        .breadcrumb-corp {
            background: var(--corp-light);
            padding: 1rem 0;
            border-bottom: 1px solid var(--corp-border);
        }

        .breadcrumb-corp .breadcrumb {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2.5rem;
            background: transparent;
            font-size: 13px;
        }

        .breadcrumb-item a {
            color: var(--corp-secondary);
            text-decoration: none;
            font-weight: 500;
        }

        /* Info Section */
        .info-section {
            background: white;
            padding: 3rem 0;
            border-bottom: 1px solid var(--corp-border);
        }

        .info-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2.5rem;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
        }

        .company-description {
            background: var(--corp-light);
            padding: 2.5rem;
            border-radius: 12px;
            border-left: 5px solid var(--corp-secondary);
        }

        .company-description h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--corp-dark);
        }

        .description-text {
            color: var(--corp-gray);
            line-height: 1.8;
            font-size: 15px;
        }

        .quick-contact {
            background: var(--corp-dark);
            color: white;
            padding: 2.5rem;
            border-radius: 12px;
        }

        .quick-contact h3 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--corp-accent);
        }

        .contact-item {
            display: flex;
            align-items: start;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .contact-item:last-child {
            border: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .contact-icon-corp {
            width: 40px;
            height: 40px;
            background: rgba(255, 107, 53, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--corp-accent);
            font-size: 18px;
            flex-shrink: 0;
        }

        .contact-details {
            flex: 1;
        }

        .contact-label {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--corp-accent);
            margin-bottom: 0.25rem;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .contact-value {
            font-size: 14px;
            color: white;
        }

        .contact-value a {
            color: white;
            text-decoration: none;
        }

        .rating-box {
            background: rgba(255, 107, 53, 0.1);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            margin-top: 1.5rem;
        }

        .rating-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--corp-accent);
            line-height: 1;
        }

        .stars-corp {
            margin: 0.75rem 0;
        }

        .stars-corp i {
            color: var(--corp-accent);
            font-size: 18px;
        }

        .rating-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
        }

        .share-buttons {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Alert Bar */
        .alert-corp {
            background: linear-gradient(135deg, var(--corp-success) 0%, #20c997 100%);
            color: white;
            padding: 1.25rem 0;
            margin: 2rem 0;
        }

        .alert-corp-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2.5rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .alert-corp-icon {
            font-size: 28px;
        }

        .alert-corp-text {
            font-size: 15px;
            font-weight: 500;
        }

        /* Products Section */
        .products-section {
            padding: 4rem 0;
            background: var(--corp-light);
        }

        .products-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2.5rem;
        }

        .section-header-corp {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-tag {
            display: inline-block;
            background: var(--corp-dark);
            color: var(--corp-accent);
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 1rem;
        }

        .section-title-corp {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--corp-dark);
            margin-bottom: 0.75rem;
        }

        .section-desc {
            color: var(--corp-gray);
            font-size: 1.125rem;
        }

        .category-banner {
            background: linear-gradient(135deg, var(--corp-dark) 0%, var(--corp-primary) 100%);
            color: white;
            padding:10px;
            border-radius: 12px;
            margin: 3rem 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .category-info h3 {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            color: antiquewhite;
        }

        .category-count {
            background: rgba(255, 107, 53, 0.2);
            color: var(--corp-accent);
            padding: 0.75rem 1.5rem;
            border-radius: 30px;
            font-weight: 700;
            font-size: 14px;
        }

        /* Product Cards */
        .products-grid-corp {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .product-card-corp {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            position: relative;
        }

        .product-card-corp:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .product-img-corp {
            position: relative;
            width: 100%;
            height: 280px;
            overflow: hidden;
            background: var(--corp-light);
        }

        .product-img-corp img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .product-card-corp:hover .product-img-corp img {
            transform: scale(1.1);
        }

        .discount-tag {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--corp-secondary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
        }

        .time-tag {
            position: absolute;
            top: 15px;
            right: 15px;
            background: white;
            color: var(--corp-dark);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .wishlist-corp {
            position: absolute;
            bottom: 15px;
            right: 15px;
            width: 45px;
            height: 45px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            transition: all 0.3s;
        }

        .wishlist-corp:hover {
            transform: scale(1.15);
            background: var(--corp-secondary);
        }

        .wishlist-corp:hover i {
            color: white !important;
        }

        .wishlist-corp i {
            font-size: 18px;
        }

        .text_red {
            color: var(--corp-secondary);
        }

        .text_grey {
            color: #d1d5db;
        }

        .product-info-corp {
            padding: 1.5rem;
        }

        .product-title-corp {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--corp-dark);
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 54px;
        }

        .product-variant-corp {
            font-size: 13px;
            color: var(--corp-gray);
            margin-bottom: 0.75rem;
            min-height: 20px;
        }

        .variant-badge-corp {
            display: inline-block;
            background: var(--corp-light);
            color: var(--corp-dark);
            padding: 0.25rem 0.85rem;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 0.25rem;
        }

        .product-price-corp {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.25rem 0;
            padding-top: 1.25rem;
            border-top: 2px solid var(--corp-light);
        }

        .price-now {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--corp-dark);
        }

        .price-was {
            font-size: 1rem;
            color: var(--corp-gray);
            text-decoration: line-through;
        }

        .product-action-corp {
            margin-top: 1rem;
        }

        .btn-corp {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--corp-dark);
            border-radius: 8px;
            background: white;
            color: var(--corp-dark);
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-corp:hover {
            background: var(--corp-dark);
            color: white;
        }

        .btn-corp-remove {
            background: #fff5f5;
            color: var(--corp-secondary);
            border-color: var(--corp-secondary);
        }

        .btn-corp-remove:hover {
            background: var(--corp-secondary);
            color: white;
        }

        .btn-corp-primary {
            background: linear-gradient(135deg, var(--corp-secondary) 0%, var(--corp-accent) 100%);
            color: white;
            border: none;
        }

        .btn-corp-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
        }

        /* Gallery Section */
        .gallery-section-corp {
            padding: 4rem 0;
            background: white;
        }

        .gallery-grid-corp {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(204px, 1fr));
            gap: 2rem;
            margin-top: 2.5rem;
        }

        .gallery-card-corp {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            aspect-ratio: 16/10;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .gallery-card-corp:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .gallery-card-corp img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .gallery-card-corp:hover img {
            transform: scale(1.15);
        }

        .gallery-overlay-corp {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(26, 26, 26, 0.9) 100%);
            display: flex;
            align-items: flex-end;
            padding: 2rem;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .gallery-card-corp:hover .gallery-overlay-corp {
            opacity: 1;
        }

        .gallery-text {
            color: white;
        }

        .gallery-text h4 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .gallery-text p {
            font-size: 13px;
            opacity: 0.9;
        }

        /* Reviews Section */
        .reviews-section-corp {
            padding: 4rem 0;
            background: var(--corp-light);
        }

        .review-card-corp {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }

        .review-card-corp:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            transform: translateY(-3px);
        }

        .review-header-corp {
            display: flex;
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .reviewer-pic-corp {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--corp-secondary);
        }

        .reviewer-details-corp {
            flex: 1;
        }

        .reviewer-name-corp {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--corp-dark);
            margin-bottom: 0.25rem;
        }

        .review-date-corp {
            font-size: 12px;
            color: var(--corp-gray);
            margin-bottom: 0.5rem;
        }

        .review-stars-corp {
            display: flex;
            gap: 0.25rem;
        }

        .review-stars-corp i {
            font-size: 15px;
            color: var(--corp-accent);
        }

        .review-text-corp {
            color: var(--corp-gray);
            line-height: 1.8;
            font-size: 15px;
        }

        .review-images-corp {
            display: flex;
            gap: 1rem;
            margin-top: 1.25rem;
        }

        .review-img-corp {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid var(--corp-border);
            transition: all 0.3s;
            cursor: pointer;
        }

        .review-img-corp:hover {
            transform: scale(1.05);
            border-color: var(--corp-secondary);
        }

        .store-reply-corp {
            margin-top: 1.5rem;
            padding: 10px;
            background: var(--corp-light);
            border-left: 4px solid var(--corp-secondary);
            border-radius: 8px;
        }

        .reply-header-corp {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .store-pic-corp {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .reply-text-corp {
            font-size: 14px;
            color: var(--corp-gray);
            line-height: 1.7;
        }

        /* Contact Section */
        .contact-section-corp {
            padding: 4rem 0;
            background: white;
        }

        .contact-grid-corp {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2.5rem;
        }

        .contact-card-corp {
            background: var(--corp-dark);
            color: white;
            padding: 2.5rem;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .contact-card-corp::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, var(--corp-secondary) 0%, var(--corp-accent) 100%);
        }

        .contact-card-corp:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .contact-icon-corp-card {
            width: 70px;
            height: 70px;
            background: rgba(255, 107, 53, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .contact-icon-corp-card img {
            width: 35px;
            height: 35px;
            filter: brightness(0) invert(1);
        }

        .contact-title-corp {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--corp-accent);
            margin-bottom: 0.75rem;
        }

        .contact-text-corp {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        .contact-text-corp a {
            color: white;
            text-decoration: none;
            font-weight: 600;
        }

        /* About Section */
        .about-section-corp {
            padding: 4rem 0;
            background: var(--corp-light);
        }

        .about-content-corp {
            background: white;
            border-radius: 12px;
            padding: 3rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            border-left: 6px solid var(--corp-secondary);
            line-height: 1.9;
            font-size: 15px;
        }

        /* Banner */
        .banner-corp {
            margin: 2rem 0;
        }

        .banner-corp img {
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        /* Map Modal */
        #map {
            height: 450px;
            width: 100%;
            border-radius: 8px;
        }

        .modal-content {
            border-radius: 12px;
            border: none;
        }

        .modal-header {
            background: var(--corp-dark);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        /* LightGallery */
        .lg-counter {
            background: rgba(26, 26, 26, 0.8) !important;
            padding: 8px 16px !important;
            border-radius: 8px !important;
        }

        .lg-next.lg-icon,
        .lg-prev.lg-icon {
            background: var(--corp-secondary);
            color: white;
            border-radius: 50%;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .info-container {
                grid-template-columns: 1fr;
            }

            .products-grid-corp {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
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
            color: var(--corp-accent);
            width: 0;
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
            .mobile-menu-toggle { display: block; }
            .nav-container {
                position: relative;
                padding: 0 1rem;
                height: 60px;
            }
            .brand-section {
                gap: 0.75rem;
                overflow: hidden;
                flex: 1;
                min-width: 0;
            }
            .brand-logo {
                height: 38px;
                width: 38px;
                flex-shrink: 0;
            }
            .brand-info {
                overflow: hidden;
                min-width: 0;
            }
            .brand-info h1 {
                font-size: 0.85rem;
                display: -webkit-box;
                -webkit-line-clamp: 1;
                -webkit-box-orient: vertical;
                overflow: hidden;
                line-height: 1.3;
            }
            .brand-info p {
                font-size: 8px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                display: none;
            }
            .nav-links {
                gap: 1rem;
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--corp-dark);
                flex-direction: column;
                padding: 10px 0;
                gap: 0;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 10;
            }
            .nav-links.show { display: flex; }
            .nav-links a {
                padding: 10px 20px;
                display: block;
                font-size: 14px;
                border-bottom: 1px solid rgba(255,255,255,0.08);
            }
            .nav-links a::after { display: none; }
            .nav-links .cta-button {
                margin: 10px 20px;
                text-align: center;
                display: block;
            }
            .hero-banner {
                margin-top: 60px;
                height: auto;
                min-height: unset;
            }
            .hero-image {
                position: relative;
                height: auto;
            }
            .hero-banner::before,
            .hero-banner::after {
                z-index: 1;
            }
            .hero-content {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                padding: 2rem 1.25rem;
            }
            .alert-corp {
                padding: 0.6rem 0;
                margin: 1rem 0;
            }
            .alert-corp-content {
                padding: 0 1.25rem;
                gap: 0.75rem;
            }
            .alert-corp-icon {
                font-size: 18px;
            }
            .alert-corp-text {
                font-size: 12px;
            }
            .hero-title {
                font-size: 1.75rem;
            }
            .hero-subtitle {
                font-size: 1rem;
            }
            .hero-badge {
                font-size: 11px;
                padding: 0.35rem 0.85rem;
                margin-bottom: 1rem;
            }
            .hero-stats {
                flex-direction: column;
                gap: 1rem;
                margin-top: 1.5rem;
            }
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 18px;
                border-radius: 10px;
            }
            .stat-info h3 {
                font-size: 1.35rem;
            }
            .stat-info p {
                font-size: 11px;
            }
            .products-container,
            .breadcrumb-corp .breadcrumb {
                padding: 0 1.25rem;
            }
            .info-container {
                padding: 0 1.25rem;
                    display: flex;
    flex-direction: column;
    gap: 1rem;

            }
            .section-title-corp {
                font-size: 1.75rem;
            }
            .section-desc {
                font-size: 0.95rem;
            }
            .company-description {
                padding: 1.5rem;
            }
            .quick-contact {
                padding: 1.5rem;
            }
            .products-grid-corp {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            .product-img-corp {
                height: 160px;
            }
            .product-info-corp {
                padding: 0.75rem;
            }
            .product-title-corp {
                font-size: 0.85rem;
                min-height: 40px;
            }
            .price-now {
                font-size: 1.15rem;
            }
            .price-was {
                font-size: 0.75rem;
            }
            .product-price-corp {
                margin: 0.75rem 0;
                padding-top: 0.75rem;
                gap: 0.5rem;
            }
            .btn-corp {
                padding: 0.6rem;
                font-size: 10px;
                gap: 0.35rem;
            }
            .gallery-grid-corp {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.5rem;
            }
            .contact-grid-corp {
                grid-template-columns: 1fr;
            }
            .contact-value {
                    font-size: 13px;
            }
            .category-banner {
                padding: 8px 12px;
                margin: 1.5rem 0 1rem;
            }
            .category-info h3 {
                font-size: 16px;
            }
            .category-count {
                padding: 0.4rem 0.85rem;
                font-size: 12px;
            }
            .about-content-corp {
                padding: 1.5rem;
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
    <!-- Corporate Navigation -->
    <nav class="corporate-nav">
        <div class="nav-container">
            <div class="brand-section">
                <img loading="lazy" class="brand-logo" src="{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}" alt="{{ $store['name'] }}">
                <div class="brand-info">
                    <h1>{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                    <p>{{ $store['meta_title'] }}</p>
                </div>
            </div>
            <button class="mobile-menu-toggle" onclick="document.querySelector('.nav-links').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <div class="nav-links">
                <a href="#services">Services</a>
                <a href="#reviews">Reviews</a>
                <a href="#contact">Contact</a>
                <a href="#about">About</a>
                <a href="{{ route('store.gallery', [$store['slug']]) }}" class="cta-button">View Gallery</a>
            </div>
        </div>
    </nav>

    <!-- Hero Banner -->
    <div class="hero-banner">
        <img loading="lazy" class="hero-image" src="{{ asset('storage/app/public/store/cover/') . '/' . $store['cover_photo'] }}" alt="Cover">
        <div class="hero-content">
            <div class="hero-text">
                <span class="hero-badge">🏆 Premium Business</span>
                <h1 class="hero-title">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                <p class="hero-subtitle">{{ $store['meta_title'] }}</p>

                <div class="hero-stats">
                    @php $store_rating = number_format($store->average_rating, 1); @endphp
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <h3>{{ $store_rating }}</h3>
                            <p>{{ $store->rating_count }} Reviews</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="stat-info">
                            <h3>{{ count($productdata) }}+</h3>
                            <p>Products/Services</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>1000+</h3>
                            <p>Satisfied Clients</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="breadcrumb-corp">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item active">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</li>
            </ol>
        </nav>
    </div>

    <!-- Info Section -->
    <div class="info-section">
        <div class="info-container">
            <div class="company-description">
                <h2>About Our Business</h2>
                @php
                    $description = $store['meta_description'];
                    $short = Str::limit($description, 300);
                @endphp
                <div class="description-text" id="text-{{ $store['id'] }}">
                    {!! $short !!}
                    @if (strlen($description) > 300)
                        <span id="dots-{{ $store['id'] }}"></span>
                        <span id="more-{{ $store['id'] }}" class="d-none">{!! substr($description, 300) !!}</span>
                        <a class="cursor-pointer" style="color: var(--corp-secondary); font-weight: 700;" onclick="toggleReadMore({{ $store['id'] }})"
                            id="btn-{{ $store['id'] }}">Read more →</a>
                    @endif
                </div>
            </div>

            <div class="quick-contact">
                <h3>Get In Touch</h3>

                <div class="contact-item">
                    <div class="contact-icon-corp">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="contact-details">
                        <div class="contact-label">Phone</div>
                        <div class="contact-value">
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
                </div>

                <div class="contact-item">
                    <div class="contact-icon-corp">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-details">
                        <div class="contact-label">Email</div>
                        <div class="contact-value">
                            <a href="mailto:{{ $store['email'] }}">{{ $data['store_config']?->webpage_email ?? $store->email }}</a>
                        </div>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon-corp">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-details">
                        <div class="contact-label">Address</div>
                        <div class="contact-value">{{ $store['address'] }}</div>
                    </div>
                </div>

                <div class="rating-box">
                    @php $store_rating = number_format($store->average_rating, 1); @endphp
                    <div class="rating-number">{{ $store_rating }}</div>
                    <div class="stars-corp rating-stars" data-rating="{{ $store_rating }}">
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
                    <div class="rating-label">Based on {{ $store->rating_count }} customer reviews</div>
                </div>

                <div class="share-buttons">
                    <div class="sharethis-inline-share-buttons"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Banners -->
    @if(count($data['banners']) > 0)
    <div style="max-width: 1400px; margin: 0 auto; padding: 0 2.5rem;">
        <div class="banner-corp">
            <div class="owl-carousel banner-carousel">
                @foreach ($data['banners'] as $value)
                    <a href="{{ $value->default_link ?? '#' }}" onclick="trackBannerClick({{ $value->id }})">
                        <img loading="lazy" src="{{ asset('storage/app/public/banner/') . '/' . $value->image }}" alt="banner">
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Announcement -->
    @if ($store->announcement)
        <div class="alert-corp">
            <div class="alert-corp-content">
                <i class="fas fa-megaphone alert-corp-icon"></i>
                <div class="alert-corp-text">{{ $store->announcement_message }}</div>
            </div>
        </div>
    @endif

    <!-- Products Section -->
    <div class="products-section" id="services">
        <div class="products-container">

            <div class="section-header-corp">
                <span class="section-tag">Our Offerings</span>
                <h2 class="section-title-corp">Products & Services</h2>
                <p class="section-desc">Discover our premium selection tailored for your needs</p>
            </div>

            @foreach ($productdata as $key => $cat)
                <div class="category-banner">
                    <div class="category-info">
                        <h3>{{ $cat->name }}</h3>
                    </div>
                    <div class="category-count">{{ count($cat->items) }} Items</div>
                </div>

                <div class="products-grid-corp">
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
                        <div class="pr_{{ $pro->id }} product-card-corp">
                            <div class="product-img-corp">
                                <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                    <img loading="lazy" 
                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                        alt="{{ $pro->name }}">
                                </a>

                                @if ($module == 5 && $store->delivery_time)
                                    <div class="time-tag">
                                        <i class="fas fa-clock"></i>
                                        {{ strtoupper($store->delivery_time) }}
                                    </div>
                                @endif

                                @if ($pro->discount > 0)
                                    <div class="discount-tag">
                                        -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                    </div>
                                @endif

                                <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                    class="prHeart_{{ $pro->id }} wishlist-corp">
                                    <i class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                </div>
                            </div>

                            <div class="product-info-corp">
                                <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                    <h4 class="product-title-corp" title="{{ ucfirst($pro->name) }}">
                                        {{ ucfirst($pro->name) }}
                                    </h4>
                                </a>

                                @if ($module == 5)
                                    <p class="product-variant-corp">
                                        {{ !empty($variations) ? $variations[0]->type : '' }}
                                    </p>
                                    @if (count($variations) > 1)
                                        <span class="variant-badge-corp">+{{ count($variations) - 1 }} more options</span>
                                    @endif

                                    <div class="product-price-corp">
                                        <div class="price-now">{{ _price($selling_price) }}</div>
                                        @if ($pro->discount > 0)
                                            <div class="price-was">{{ _price($mrp) }}</div>
                                        @endif
                                    </div>

                                    <div class="product-action-corp cartSec_{{ $pro->id }}">
                                        @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                        @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                            <button onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                class="btn-corp btn-corp-remove">
                                                <i class="fa fa-times"></i> Remove
                                            </button>
                                        @else
                                            <button onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                class="btn-corp">
                                                <i class="fa fa-shopping-cart"></i> Add to Cart
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    @if ($pro->item_type == 'product')
                                        <div class="product-price-corp">
                                            <div class="price-now">{{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0 || $mrp > $selling_price)
                                                <div class="price-was">{{ _price($mrp) }}</div>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="product-action-corp">
                                        @if ($pro->item_type == 'product' && ($pro->stock ?? 1) <= 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif (($data['store_config']->lead_available ?? 1) == 0)
                                            <button disabled class="btn-corp btn-corp-primary" style="opacity:0.5;cursor:not-allowed;">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
                                            </button>
                                            <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                        @elseif (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                class="btn-corp btn-corp-primary">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
                                            </button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="btn-corp btn-corp-primary">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
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
                <div class="category-banner">
                    <div class="category-info">
                        <h3>{{ $cat->name }}</h3>
                    </div>
                    <div class="category-count">{{ count($cat->items) }} Items</div>
                </div>

                <div class="products-grid-corp">
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
                        <div class="pr_{{ $pro->id }} product-card-corp">
                            <div class="product-img-corp">
                                <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                    <img loading="lazy"
                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                        alt="{{ $pro->name }}">
                                </a>

                                @if ($module == 5 && $store->delivery_time)
                                    <div class="time-tag">
                                        <i class="fas fa-clock"></i>
                                        {{ strtoupper($store->delivery_time) }}
                                    </div>
                                @endif

                                @if ($pro->discount > 0)
                                    <div class="discount-tag">
                                        -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                    </div>
                                @endif

                                <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                    class="prHeart_{{ $pro->id }} wishlist-corp">
                                    <i class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                </div>
                            </div>

                            <div class="product-info-corp">
                                <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                    <h4 class="product-title-corp" title="{{ ucfirst($pro->name) }}">
                                        {{ ucfirst($pro->name) }}
                                    </h4>
                                </a>

                                @if ($module == 5)
                                    <p class="product-variant-corp">
                                        {{ !empty($variations) ? $variations[0]->type : '' }}
                                    </p>
                                    @if (count($variations) > 1)
                                        <span class="variant-badge-corp">+{{ count($variations) - 1 }} more options</span>
                                    @endif

                                    <div class="product-price-corp">
                                        <div class="price-now">{{ _price($selling_price) }}</div>
                                        @if ($pro->discount > 0)
                                            <div class="price-was">{{ _price($mrp) }}</div>
                                        @endif
                                    </div>

                                    <div class="product-action-corp cartSec_{{ $pro->id }}">
                                        @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                        @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                            <button onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                class="btn-corp btn-corp-remove">
                                                <i class="fa fa-times"></i> Remove
                                            </button>
                                        @else
                                            <button onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                class="btn-corp">
                                                <i class="fa fa-shopping-cart"></i> Add to Cart
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    @if ($pro->item_type == 'product')
                                        <div class="product-price-corp">
                                            <div class="price-now">{{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0 || $mrp > $selling_price)
                                                <div class="price-was">{{ _price($mrp) }}</div>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="product-action-corp">
                                        @if ($pro->item_type == 'product' && ($pro->stock ?? 1) <= 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif (($data['store_config']->lead_available ?? 1) == 0)
                                            <button disabled class="btn-corp btn-corp-primary" style="opacity:0.5;cursor:not-allowed;">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
                                            </button>
                                            <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                        @elseif (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                class="btn-corp btn-corp-primary">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
                                            </button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="btn-corp btn-corp-primary">
                                                <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
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
                <div style="text-align: center; padding: 5rem 0; color: var(--corp-gray);">
                    <i class="fas fa-box-open" style="font-size: 5rem; margin-bottom: 1.5rem; opacity: 0.3;"></i>
                    <p style="font-size: 1.25rem; font-weight: 600;">No products available</p>
                </div>
            @endif

        </div>
    </div>

    <!-- Gallery -->
    @if (count($store->galleries))
        <div class="gallery-section-corp">
            <div class="products-container">
                <div class="section-header-corp">
                    <span class="section-tag">Portfolio</span>
                    <h2 class="section-title-corp">Our Gallery</h2>
                    <p class="section-desc">A glimpse into our work and workspace</p>
                </div>

                <div class="gallery-grid-corp">
                    @foreach ($data['galleries'] as $key => $value)
                        <a target="_blank"
                            href="{{ asset('storage/app/public/store/gallery') }}/{{ $value->image }}"
                            class="gallery-card-corp lightgallery-item">
                            <img loading="lazy" 
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                    $value['image'] ?? '',
                                    asset('storage/app/public/store/gallery') . '/' . $value['image'] ?? '',
                                    asset('public/assets/admin/img/160x160/img1.jpg'),
                                    'store/gallery/',
                                ) }}"
                                alt="Gallery {{ $key + 1 }}">
                            <div class="gallery-overlay-corp">
                                <div class="gallery-text">
                                    <h4>Gallery Image {{ $key + 1 }}</h4>
                                    <p>Click to view full size</p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Reviews -->
    @if (count($data['reviews']) && $module == 6)
        <div class="reviews-section-corp" id="reviews">
            <div class="products-container">
                <div class="section-header-corp">
                    <span class="section-tag">Testimonials</span>
                    <h2 class="section-title-corp">Client Reviews</h2>
                    <p class="section-desc">What our valued customers say about us</p>
                </div>

                @foreach ($data['reviews'] as $rev)
                    <div class="review-card-corp">
                        <div class="review-header-corp">
                            <img loading="lazy" 
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/') . '/' . $rev->profile_image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                class="reviewer-pic-corp"
                                alt="{{ $rev->f_name }}">
                            <div class="reviewer-details-corp">
                                <div class="reviewer-name-corp">{{ $rev->f_name . ' ' . $rev->l_name }}</div>
                                <div class="review-date-corp">{{ _formatted_datetime($rev->created_at) }}</div>
                                <div class="review-stars-corp">
                                    @for ($i = 1; $i < 6; $i++)
                                        <i class="fas fa-star" style="color: {{ $rev->rating >= $i ? 'var(--corp-accent)' : '#e5e7eb' }};"></i>
                                    @endfor
                                </div>
                            </div>
                        </div>

                        <p class="review-text-corp">{{ $rev->comment }}</p>

                        @if ($rev->attachment)
                            @php $attachments = (array) $rev->attachment; @endphp
                            @if (!empty($attachments))
                                <div class="review-images-corp">
                                    @foreach ($attachments as $img)
                                        <a target="_blank" href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}">
                                            <img loading="lazy" class="review-img-corp"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                alt="review">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        @if ($rev->reply)
                            <div class="store-reply-corp">
                                <div class="reply-header-corp">
                                    <img loading="lazy" 
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store['logo'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                        class="store-pic-corp"
                                        alt="{{ $store->name }}">
                                    <div>
                                        <div style="font-weight: 700; font-size: 14px; color: var(--corp-dark);">Business Response</div>
                                        <div style="font-size: 12px; color: var(--corp-gray);">{{ _formatted_datetime($rev->replied_at) }}</div>
                                    </div>
                                </div>
                                <p class="reply-text-corp">{{ $rev->reply }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($data['review_count'] > 2)
                    <div style="text-align: center; margin-top: 2.5rem;">
                        <a href="{{ route('store.reviews', [$store->slug]) }}" class="cta-button" style="display: inline-block;">
                            View All Reviews <i class="fas fa-arrow-right" style="margin-left: 0.75rem;"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Contact -->
    <div class="contact-section-corp" id="contact">
        <div class="products-container">
            <div class="section-header-corp">
                <span class="section-tag">Contact</span>
                <h2 class="section-title-corp">Get In Touch</h2>
                <p class="section-desc">We're here to answer your questions</p>
            </div>

            <div class="contact-grid-corp">
                <div class="contact-card-corp">
                    <div class="contact-icon-corp-card">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/map.png" alt="Address">
                    </div>
                    <div class="contact-title-corp">Visit Us</div>
                    <div class="contact-text-corp">{{ $store['address'] }}</div>
                </div>

                <div class="contact-card-corp">
                    <div class="contact-icon-corp-card">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/secured-letter.png" alt="Email">
                    </div>
                    <div class="contact-title-corp">Email Us</div>
                    <div class="contact-text-corp">
                        <a href="mailto:{{ $store['email'] }}">{{ $store['email'] }}</a>
                    </div>
                </div>

                <div class="contact-card-corp">
                    <div class="contact-icon-corp-card">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/phone.png" alt="Phone">
                    </div>
                    <div class="contact-title-corp">Call Us</div>
                    <div class="contact-text-corp">
                        <a href="tel:{{ $store['phone'] }}">{{ $store['phone'] }}</a>
                    </div>
                </div>

                <div class="contact-card-corp">
                    <div class="contact-icon-corp-card">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/marker.png" alt="Map">
                    </div>
                    <div class="contact-title-corp">Directions</div>
                    <div class="contact-text-corp">
                        <a class="cursor-pointer" data-bs-toggle="modal" data-bs-target="#exampleModal">View on Map</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- About -->
    <div class="about-section-corp" id="about">
        <div class="products-container">
            <div class="section-header-corp">
                <span class="section-tag">About</span>
                <h2 class="section-title-corp">Our Story</h2>
                <p class="section-desc">Learn more about who we are and what we do</p>
            </div>

            <div class="about-content-corp">
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
        // Rating stars 
        document.querySelectorAll('.rating-stars').forEach(el => {
            const rating = parseFloat(el.getAttribute('data-rating'));
            const percentage = (Math.min(rating, 5) / 5) * 100;
            el.querySelector('.stars-fill').style.width = `${percentage}%`;
        });

        // LightGallery
        if (document.querySelector('.gallery-grid-corp')) {
            lightGallery(document.querySelector('.gallery-grid-corp'), {
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