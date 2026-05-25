@extends('front-views.layout')

@section('title', $store['meta_title'] ?? ($data['store_config']?->webpage_name ?? $store['name']))

@section('meta_keywords', $keywords)
@section('meta_description', $store['meta_description'])


@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style> 
        :root {
            --soft-lavender: #e9e4f0;
            --light-blue: #e3f2fd; 
            --cream: #fef7e5;
            --pale-pink: #fce4ec;
            --soft-gray: #f5f5f5;
            --text-primary: #424242;
            --text-secondary: #757575;
            --accent-lavender: #9c88b8;
            --accent-blue: #64b5f6;
            --accent-peach: #ffab91;
            --white: #ffffff;
            --border-light: #eeeeee;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Helvetica Neue', sans-serif;
            background: var(--white);
            color: var(--text-primary);
            line-height: 1.8;
            font-weight: 300;
        }

        /* Minimal Top Bar */
        .minimal-top-bar {
            background: var(--white);
            border-bottom: 1px solid var(--border-light);
            padding: 0.75rem 0;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .top-bar-content {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-contact {
            display: flex;
            gap: 2rem;
        }

        .top-contact-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .top-contact-item i {
            color: var(--accent-lavender);
            font-size: 11px;
        }

        /* Minimal Header */
        .minimal-header {
            background: var(--white);
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }

        .header-content {
            max-width: 1300px;
            margin: 0 auto;
            padding: 1.5rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .brand-logo {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid var(--border-light);
        }

        .brand-text h1 {
            font-size: 1.25rem;
            font-weight: 400;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }

        .brand-text p {
            font-size: 11px;
            color: var(--text-secondary);
            font-weight: 300;
            margin-bottom: 0;
        }

        .nav-minimal {
            display: flex;
            gap: 3rem;
            align-items: center;
        }

        .nav-minimal a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            transition: color 0.3s;
            position: relative;
        }

        .nav-minimal a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 1px;
            background: var(--accent-lavender);
            transition: width 0.3s;
        }

        .nav-minimal a:hover {
            color: var(--text-primary);
        }

        .nav-minimal a:hover::after {
            width: 100%;
        }

        .btn-minimal {
            background: var(--soft-lavender);
            color: var(--text-primary);
            padding: 0.75rem 1.75rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 400;
            transition: all 0.3s;
        }

        .btn-minimal:hover {
            background: var(--accent-lavender);
            color: white;
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-primary);
            padding: 5px;
        }


        /* Hero Minimal */
        .hero-minimal {
            background: linear-gradient(180deg, var(--light-blue) 0%, var(--white) 100%);
            padding: 5rem 0 3rem;
        }

        .hero-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 3rem;
        }

        .hero-layout {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 5rem;
            align-items: center;
        }

        .hero-left-minimal {
            padding: 2rem 0;
        }

        .hero-tag {
            display: inline-block;
            background: white;
            color: var(--accent-lavender);
            padding: 0.5rem 1.25rem;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 2rem;
            border: 1px solid var(--border-light);
        }

        .hero-title-minimal {
            font-size: 3.5rem;
            font-weight: 300;
            line-height: 1.2;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            letter-spacing: -1px;
        }

        .hero-title-minimal strong {
            font-weight: 500;
        }

        .hero-description {
            font-size: 1.125rem;
            color: var(--text-secondary);
            margin-bottom: 3rem;
            line-height: 1.8;
        }

        .hero-actions {
            display: flex;
            gap: 1.25rem;
        }

        .btn-primary-minimal {
            background: var(--text-primary);
            color: white;
            padding: 1rem 2.25rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            transition: all 0.3s;
        }

        .btn-primary-minimal:hover {
            background: var(--accent-lavender);
            color: white;
        }

        .btn-secondary-minimal {
            background: white;
            color: var(--text-primary);
            padding: 1rem 2.25rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            border: 1px solid var(--border-light);
            transition: all 0.3s;
        }

        .btn-secondary-minimal:hover {
            border-color: var(--accent-lavender);
            color: var(--accent-lavender);
        }

        .hero-right-minimal {
            position: relative;
        }

        .hero-image-minimal {
            width: 100%;
            height: 450px;
            object-fit: cover;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.06);
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }

        .stat-box-minimal {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            border: 1px solid var(--border-light);
        }

        .stat-value-minimal {
            font-size: 2rem;
            font-weight: 400;
            color: var(--text-primary);
            line-height: 1;
        }

        .stat-label-minimal {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Breadcrumb */
        .breadcrumb-minimal {
            background: var(--soft-gray);
            padding: 1rem 0;
        }

        .breadcrumb-minimal .breadcrumb {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 3rem;
            background: transparent;
            font-size: 12px;
        }

        .breadcrumb-item {
            color: var(--text-secondary);
        }

        .breadcrumb-item a {
            color: var(--accent-lavender);
            text-decoration: none;
        }

        /* Info Section */
        .info-minimal {
            padding: 5rem 0;
            background: white;
        }

        .info-grid {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 3rem;
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 4rem;
        }

        .about-minimal {
            padding-right: 2rem;
        }

        .about-minimal h2 {
            font-size: 2rem;
            font-weight: 400;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            letter-spacing: -0.5px;
        }

        .about-minimal h2::after {
            content: '';
            display: block;
            width: 60px;
            height: 2px;
            background: var(--accent-lavender);
            margin-top: 1rem;
        }

        .about-text-minimal {
            font-size: 15px;
            color: var(--text-secondary);
            line-height: 1.9;
        }

        .contact-minimal {
            background: var(--soft-lavender);
            padding: 2.5rem;
            border-radius: 12px;
        }

        .contact-minimal h3 {
            font-size: 1.25rem;
            font-weight: 400;
            color: var(--text-primary);
            margin-bottom: 2rem;
        }

        .contact-item-minimal {
            display: flex;
            align-items: start;
            gap: 1rem;
            margin-bottom: 1.75rem;
            padding-bottom: 1.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.5);
        }

        .contact-item-minimal:last-child {
            border: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .contact-icon-minimal {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-lavender);
            font-size: 16px;
            flex-shrink: 0;
        }

        .contact-detail {
            flex: 1;
        }

        .contact-detail h4 {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
            font-weight: 500;
        }

        .contact-detail p {
            font-size: 14px;
            color: var(--text-primary);
            margin: 0;
        }

        .contact-detail a {
            color: var(--text-primary);
            text-decoration: none;
        }

        .rating-minimal {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            margin-top: 2rem;
        }

        .rating-num {
            font-size: 3.5rem;
            font-weight: 300;
            color: var(--text-primary);
            line-height: 1;
        }

        .stars-minimal {
            margin: 1rem 0;
        }

        .stars-minimal i {
            font-size: 18px;
            color: var(--accent-peach);
        }

        .rating-count {
            font-size: 12px;
            color: var(--text-secondary);
        }

        /* Announcement */
        .announcement-minimal {
            background: var(--cream);
            padding: 1.5rem 0;
            border-top: 1px solid var(--border-light);
            border-bottom: 1px solid var(--border-light);
            margin: 3rem 0;
        }

        .announcement-content-minimal {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 3rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .announcement-icon-minimal {
            font-size: 24px;
            color: var(--accent-peach);
        }

        .announcement-msg {
            font-size: 14px;
            color: var(--text-primary);
        }

        /* Products Section */
        .products-minimal {
            padding: 5rem 0;
            background: var(--soft-gray);
        }

        .products-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 3rem;
        }

        .section-header-minimal {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-label-minimal {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-secondary);
            letter-spacing: 2px;
            margin-bottom: 1rem;
        }

        .section-title-minimal {
            font-size: 2.5rem;
            font-weight: 300;
            color: var(--text-primary);
            letter-spacing: -1px;
        }

        .category-minimal {
            margin: 4rem 0;
        }

        .category-title-minimal {
            font-size: 1.75rem;
            font-weight: 400;
            color: var(--text-primary);
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-light);
        }

        /* Product Grid */
        .products-grid-minimal {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2.5rem;
        }

        .product-minimal {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid var(--border-light);
        }

        .product-minimal:hover {
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
            transform: translateY(-5px);
        }

        .product-image-minimal {
            position: relative;
            width: 100%;
            height: 280px;
            overflow: hidden;
            background: var(--soft-gray);
        }

        .product-image-minimal img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .product-minimal:hover .product-image-minimal img {
            transform: scale(1.05);
        }

        .discount-tag-minimal {
            position: absolute;
            top: 12px;
            left: 12px;
            background: var(--accent-peach);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }

        .time-tag-minimal {
            position: absolute;
            top: 12px;
            right: 12px;
            background: white;
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .like-minimal {
            position: absolute;
            bottom: 12px;
            right: 12px;
            width: 42px;
            height: 42px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }

        .like-minimal:hover {
            background: var(--soft-lavender);
        }

        .like-minimal i {
            font-size: 18px;
        }

        .text_red {
            color: #f06292;
        }

        .text_grey {
            color: #bdbdbd;
        }

        .product-body-minimal {
            padding: 1.75rem;
        }

        .product-name-minimal {
            font-size: 1.125rem;
            font-weight: 400;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 54px;
        }

        .product-variant-minimal {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 0.75rem;
            min-height: 20px;
        }

        .variant-tag-minimal {
            display: inline-block;
            background: var(--soft-gray);
            color: var(--text-secondary);
            padding: 0.3rem 0.85rem;
            border-radius: 12px;
            font-size: 11px;
            margin-top: 0.5rem;
        }

        .product-price-minimal {
            display: flex;
            align-items: baseline;
            gap: 1rem;
            margin: 1.25rem 0;
        }

        .price-current-minimal {
            font-size: 1.75rem;
            font-weight: 400;
            color: var(--text-primary);
        }

        .price-old-minimal {
            font-size: 1rem;
            color: var(--text-secondary);
            text-decoration: line-through;
        }

        .product-action-minimal {
            margin-top: 1.25rem;
        }

        .btn-product-minimal {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--border-light);
            border-radius: 6px;
            background: white;
            color: var(--text-primary);
            font-weight: 400;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-product-minimal:hover {
            background: var(--text-primary);
            color: white;
            border-color: var(--text-primary);
        }

        .btn-remove-minimal {
            background: var(--pale-pink);
            border-color: var(--pale-pink);
            color: #e91e63;
        }

        .btn-remove-minimal:hover {
            background: #e91e63;
            color: white;
            border-color: #e91e63;
        }

        .btn-enquiry-minimal {
            background: var(--soft-lavender);
            border-color: var(--soft-lavender);
        }

        .btn-enquiry-minimal:hover {
            background: var(--accent-lavender);
            color: white;
            border-color: var(--accent-lavender);
        }

        /* Gallery Section */
        .gallery-minimal {
            padding: 5rem 0;
            background: white;
        }

        .gallery-grid-minimal {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(165px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .gallery-item-minimal {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            aspect-ratio: 4/3;
            cursor: pointer;
            border: 1px solid var(--border-light);
            transition: all 0.3s;
        }

        .gallery-item-minimal:hover {
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
        }

        .gallery-item-minimal img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .gallery-item-minimal:hover img {
            transform: scale(1.08);
        }

        /* Reviews Section */
        .reviews-minimal {
            padding: 5rem 0;
            background: var(--soft-gray);
        }

        .review-minimal {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-light);
            transition: all 0.3s;
        }

        .review-minimal:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
        }

        .review-header-minimal {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .reviewer-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border-light);
        }

        .reviewer-info-minimal {
            flex: 1;
        }

        .reviewer-name-minimal {
            font-size: 1.125rem;
            font-weight: 400;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .review-date-minimal {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 0.75rem;
        }

        .review-stars-minimal {
            display: flex;
            gap: 0.25rem;
        }

        .review-stars-minimal i {
            font-size: 14px;
            color: var(--accent-peach);
        }

        .review-text-minimal {
            font-size: 15px;
            color: var(--text-secondary);
            line-height: 1.9;
        }

        .review-images-minimal {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .review-img-minimal {
            width: 90px;
            height: 90px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid var(--border-light);
            transition: all 0.3s;
            cursor: pointer;
        }

        .review-img-minimal:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .reply-minimal {
            margin-top: 0.75rem;
            padding: 0.75rem;
            background: var(--soft-gray);
            border-radius: 8px;
            border-left: 3px solid var(--accent-lavender);
        }

        .reply-header-minimal {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .reply-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .reply-text-minimal {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.8;
        }

        /* Contact Section */
        .contact-section-minimal {
            padding: 5rem 0;
            background: white;
        }

        .contact-grid-minimal {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .contact-card-minimal {
            background: var(--soft-gray);
            padding: 2.5rem;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid var(--border-light);
        }

        .contact-card-minimal:hover {
            background: white;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
        }

        .contact-icon-card {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            border: 1px solid var(--border-light);
        }

        .contact-icon-card img {
            width: 28px;
            height: 28px;
            opacity: 0.6;
        }

        .contact-title-minimal {
            font-size: 1.125rem;
            font-weight: 400;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
        }

        .contact-text-minimal {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .contact-text-minimal a {
            color: var(--accent-lavender);
            text-decoration: none;
        }

        /* About Section */
        .about-section-minimal {
            padding: 5rem 0;
            background: var(--soft-gray);
        }

        .about-content-minimal {
            background: white;
            border-radius: 12px;
            padding: 3rem;
            border: 1px solid var(--border-light);
            line-height: 2;
            font-size: 15px;
            color: var(--text-secondary);
        }

        /* Banner */
        .banner-minimal {
            margin: 3rem 0;
        }

        .banner-minimal img {
            width: 100%;
            border-radius: 12px;
            border: 1px solid var(--border-light);
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
            background: var(--soft-lavender);
            color: var(--text-primary);
            border-radius: 12px 12px 0 0;
            border-bottom: 1px solid var(--border-light);
        }

        /* LightGallery */
        .lg-counter {
            background: rgba(0, 0, 0, 0.6) !important;
            padding: 8px 16px !important;
            border-radius: 6px !important;
        }

        .lg-next.lg-icon,
        .lg-prev.lg-icon {
            background: var(--accent-lavender);
            color: white;
            border-radius: 50%;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-layout {
                grid-template-columns: 1fr;
                gap: 3rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 3rem;
            }

            .products-grid-minimal {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }

            .nav-minimal {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .brand-text h1 {
                font-size: 15px;
            }

            .hero-layout {
                display: flex;
                flex-direction: column;
            }

            .hero-minimal {
                padding: 0;
            }

            .hero-left-minimal {
                padding: 1rem 0;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .nav-minimal {
                display: none;
                gap: 1rem;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--white);
                flex-direction: column;
                padding: 15px 0;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
                z-index: 10;
            }

            .nav-minimal.show {
                display: flex;
            }

            .nav-minimal a {
                padding: 10px 25px;
                display: block;
            }

            .header-content {
                position: relative;
            }

            .minimal-top-bar {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .brand-text p {
                display: none;
            }

            .top-bar-content {
                padding: 0 1.5rem;
            }

            .header-content {
                padding: 15px 1.5rem;
            }

            .hero-container,
            .info-grid,
            .products-container {
                padding: 0 1.5rem;
            }

            .hero-title-minimal {
                font-size: 1.5rem;
            }

            .products-grid-minimal {
                grid-template-columns: repeat(2, 1fr);
                        gap: 1rem;
            }

            .gallery-grid-minimal {
                grid-template-columns: repeat(2, 1fr);
            }

            .contact-grid-minimal {
                grid-template-columns: 1fr;
            }

            .hero-description {
                font-size: 12px;
                margin-bottom: 1rem;
            }

            .btn-primary-minimal,
            .btn-secondary-minimal {
                padding: 10px;
                font-size: 11px;

            }

            .hero-image-minimal {
                height: auto;
            }

            .stat-box-minimal {
                padding: 0.5rem;
            }

            .stat-value-minimal {
                font-size: 1rem;
            }

            .info-minimal {
                padding: 1rem 0;
            }

            .products-minimal {
                padding: 1rem 0;
            }

            .section-title-minimal {
                font-size: 2rem;
            }

            .info-grid {
                display: flex;
                flex-direction: column;
            }

            .contact-minimal {
                padding: 1rem;
            }

            .contact-detail p {
                font-size: 12px;
            }

            .product-image-minimal {
                height: 107px;
            }

            .product-body-minimal {
                    padding: 10px;
            }
            .product-name-minimal {
                    font-size: 14px;
            }
            .btn-product-minimal
 {
        padding: 5px;
            font-size: 11px;
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
            color: #e0e0e0;
        }

        .stars-fill {
            position: absolute;
            top: 0;
            left: 0;
            overflow: hidden;
            white-space: nowrap;
            color: var(--accent-peach);
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
    <!-- Minimal Top Bar -->
    <div class="minimal-top-bar">
        <div class="top-bar-content">
            <div class="top-contact">
                <div class="top-contact-item">
                    <i class="fas fa-phone"></i>
                    @php
                        $phones = $data['store_config']?->webpage_phones;
                        if ($phones) {
                            $phones = json_decode($phones, true);
                        } else {
                            $phones = [];
                        }
                    @endphp
                    <span>
                        @if (!empty($phones))
                            {{ implode(', ', $phones) }}
                        @else
                            {{ $store['phone'] }}
                        @endif
                    </span>
                </div>
                <div class="top-contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>{{ $data['store_config']?->webpage_email ?? $store->email }}</span>
                </div>
            </div>
            <div>
            </div>
        </div>
    </div>

    <!-- Minimal Header -->
    <header class="minimal-header">
        <div class="header-content">
            <div class="brand">
                <img loading="lazy" class="brand-logo" src="{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}"
                    alt="{{ $store['name'] }}">
                <div class="brand-text">
                    <h1>{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                    <p>{{ $store['meta_title'] }}</p>
                </div>
            </div>
            <button class="mobile-menu-toggle" onclick="document.querySelector('.nav-minimal').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="nav-minimal">
                <a href="#services">Services</a>
                <a href="#reviews">Reviews</a>
                <a href="#contact">Contact</a>
                <a href="#about">About</a>
                <a href="{{ route('store.gallery', [$store['slug']]) }}" class="btn-minimal">Gallery</a>
            </nav>
        </div>
    </header>

    <!-- Hero Minimal -->
    <div class="hero-minimal">
        <div class="hero-container">
            <div class="hero-layout">
                <div class="hero-left-minimal">
                    <span class="hero-tag">Welcome</span>
                    <h1 class="hero-title-minimal">
                        <strong>{{ $data['store_config']?->webpage_name ?? $store['name'] }}</strong>
                    </h1>
                    <p class="hero-description">{{ $store['meta_title'] }}</p>
                    <div class="hero-actions">
                        <a href="#services" class="btn-primary-minimal">Explore Services</a>
                        <a href="#contact" class="btn-secondary-minimal">Get in Touch</a>
                    </div>
                </div>

                <div class="hero-right-minimal">
                    <img loading="lazy" class="hero-image-minimal"
                        src="{{ asset('storage/app/public/store/cover/') . '/' . $store['cover_photo'] }}" alt="Cover">

                    <div class="hero-stats">
                        @php $store_rating = number_format($store->average_rating, 1); @endphp
                        <div class="stat-box-minimal">
                            <div class="stat-value-minimal">{{ $store_rating }}</div>
                            <div class="stat-label-minimal">Rating</div>
                        </div>
                        <div class="stat-box-minimal">
                            <div class="stat-value-minimal">{{ $store->rating_count }}+</div>
                            <div class="stat-label-minimal">Reviews</div>
                        </div>
                        <div class="stat-box-minimal">
                            <div class="stat-value-minimal">{{ count($productdata) }}+</div>
                            <div class="stat-label-minimal">Products</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="breadcrumb-minimal">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">›</li>
                <li class="breadcrumb-item active">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</li>
            </ol>
        </nav>
    </div>

    <!-- Info Section -->
    <div class="info-minimal">
        <div class="info-grid">
            <div class="about-minimal">
                <h2>Our Story</h2>
                @php
                    $description = $store['meta_description'];
                    $short = Str::limit($description, 400);
                @endphp
                <div class="about-text-minimal" id="text-{{ $store['id'] }}">
                    {!! $short !!}
                    @if (strlen($description) > 400)
                        <span id="dots-{{ $store['id'] }}"></span>
                        <span id="more-{{ $store['id'] }}" class="d-none">{!! substr($description, 400) !!}</span>
                        <a class="cursor-pointer" style="color: var(--accent-lavender); text-decoration: underline;"
                            onclick="toggleReadMore({{ $store['id'] }})" id="btn-{{ $store['id'] }}">Read more</a>
                    @endif
                </div>
            </div>

            <div class="contact-minimal">
                <h3>Contact Information</h3>

                <div class="contact-item-minimal">
                    <div class="contact-icon-minimal">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="contact-detail">
                        <h4>Phone</h4>
                        <p>
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
                        </p>
                    </div>
                </div>

                <div class="contact-item-minimal">
                    <div class="contact-icon-minimal">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-detail">
                        <h4>Email</h4>
                        <p><a
                                href="mailto:{{ $store['email'] }}">{{ $data['store_config']?->webpage_email ?? $store->email }}</a>
                        </p>
                    </div>
                </div>

                <div class="contact-item-minimal">
                    <div class="contact-icon-minimal">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-detail">
                        <h4>Address</h4>
                        <p>{{ $store['address'] }}</p>
                    </div>
                </div>

                <div class="rating-minimal">
                    @php $store_rating = number_format($store->average_rating, 1); @endphp
                    <div class="rating-num">{{ $store_rating }}</div>
                    <div class="stars-minimal rating-stars" data-rating="{{ $store_rating }}">
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
                    <div class="rating-count">{{ $store->rating_count }} customer reviews</div>
                </div>

                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(255, 255, 255, 0.5);">
                    <div class="sharethis-inline-share-buttons"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Banners -->
    @if (count($data['banners']) > 0)
        <div style="max-width: 1300px; margin: 0 auto; padding: 0 3rem;">
            <div class="banner-minimal">
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
        <div class="announcement-minimal">
            <div class="announcement-content-minimal">
                <i class="fas fa-info-circle announcement-icon-minimal"></i>
                <div class="announcement-msg">{{ $store->announcement_message }}</div>
            </div>
        </div>
    @endif

    <!-- Products Section -->
    <div class="products-minimal" id="services">
        <div class="products-container">
            <div class="section-header-minimal">
                <div class="section-label-minimal">Explore</div>
                <h2 class="section-title-minimal">Our Products & Services</h2>
            </div>

            @foreach ($productdata as $key => $cat)
                <div class="category-minimal">
                    <h3 class="category-title-minimal">{{ $cat->name }}</h3>

                    <div class="products-grid-minimal">
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
                            <div class="pr_{{ $pro->id }} product-minimal">
                                <div class="product-image-minimal">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <img loading="lazy"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                            alt="{{ $pro->name }}">
                                    </a>

                                    @if ($module == 5 && $store->delivery_time)
                                        <div class="time-tag-minimal">
                                            <i class="fas fa-clock"></i>
                                            {{ strtoupper($store->delivery_time) }}
                                        </div>
                                    @endif

                                    @if ($pro->discount > 0)
                                        <div class="discount-tag-minimal">
                                            -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                        </div>
                                    @endif

                                    <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                        class="prHeart_{{ $pro->id }} like-minimal">
                                        <i
                                            class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                    </div>
                                </div>

                                <div class="product-body-minimal">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <h4 class="product-name-minimal" title="{{ ucfirst($pro->name) }}">
                                            {{ ucfirst($pro->name) }}
                                        </h4>
                                    </a>

                                    @if ($module == 5)
                                        <p class="product-variant-minimal">
                                            {{ !empty($variations) ? $variations[0]->type : '' }}
                                        </p>
                                        @if (count($variations) > 1)
                                            <span class="variant-tag-minimal">+{{ count($variations) - 1 }} more</span>
                                        @endif

                                        <div class="product-price-minimal">
                                            <div class="price-current-minimal">{{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0)
                                                <div class="price-old-minimal">{{ _price($mrp) }}</div>
                                            @endif
                                        </div>

                                        <div class="product-action-minimal cartSec_{{ $pro->id }}">
                                            @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                            @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                    class="btn-product-minimal btn-remove-minimal">
                                                    <i class="fa fa-times"></i> Remove
                                                </button>
                                            @else
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                    class="btn-product-minimal">
                                                    <i class="fa fa-plus"></i> Add to Cart
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        @if ($pro->item_type == 'product')
                                            <div class="product-price-minimal">
                                                <div class="price-current-minimal">{{ _price($selling_price) }}</div>
                                                @if ($pro->discount > 0 || $mrp > $selling_price)
                                                    <div class="price-old-minimal">{{ _price($mrp) }}</div>
                                                @endif
                                            </div>
                                        @endif

                                        <div class="product-action-minimal">
                                            @if ($pro->item_type == 'product' && ($pro->stock ?? 1) <= 0)
                                                <span class="badge bg-danger">Out of Stock</span>
                                            @elseif (($data['store_config']->lead_available ?? 1) == 0)
                                                <button disabled class="btn-product-minimal btn-enquiry-minimal" style="opacity:0.5;cursor:not-allowed;">
                                                    <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
                                                </button>
                                                <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                            @elseif (auth('web')->user())
                                                <button
                                                    onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                    class="btn-product-minimal btn-enquiry-minimal">
                                                    <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
                                                </button>
                                            @else
                                                <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                    class="btn-product-minimal btn-enquiry-minimal">
                                                    <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @foreach ($invItemdata as $key => $cat)
                <div class="category-minimal">
                    <h3 class="category-title-minimal">{{ $cat->name }}</h3>

                    <div class="products-grid-minimal">
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
                            <div class="pr_{{ $pro->id }} product-minimal">
                                <div class="product-image-minimal">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <img loading="lazy"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                            alt="{{ $pro->name }}">
                                    </a>

                                    @if ($module == 5 && $store->delivery_time)
                                        <div class="time-tag-minimal">
                                            <i class="fas fa-clock"></i>
                                            {{ strtoupper($store->delivery_time) }}
                                        </div>
                                    @endif

                                    @if ($pro->discount > 0)
                                        <div class="discount-tag-minimal">
                                            -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                        </div>
                                    @endif

                                    <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                        class="prHeart_{{ $pro->id }} like-minimal">
                                        <i
                                            class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                    </div>
                                </div>

                                <div class="product-body-minimal">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <h4 class="product-name-minimal" title="{{ ucfirst($pro->name) }}">
                                            {{ ucfirst($pro->name) }}
                                        </h4>
                                    </a>

                                    @if ($module == 5)
                                        <p class="product-variant-minimal">
                                            {{ !empty($variations) ? $variations[0]->type : '' }}
                                        </p>
                                        @if (count($variations) > 1)
                                            <span class="variant-tag-minimal">+{{ count($variations) - 1 }} more</span>
                                        @endif

                                        <div class="product-price-minimal">
                                            <div class="price-current-minimal">{{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0)
                                                <div class="price-old-minimal">{{ _price($mrp) }}</div>
                                            @endif
                                        </div>

                                        <div class="product-action-minimal cartSec_{{ $pro->id }}">
                                            @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                            @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                    class="btn-product-minimal btn-remove-minimal">
                                                    <i class="fa fa-times"></i> Remove
                                                </button>
                                            @else
                                                <button
                                                    onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                    class="btn-product-minimal">
                                                    <i class="fa fa-plus"></i> Add to Cart
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        @if ($pro->item_type == 'product')
                                            <div class="product-price-minimal">
                                                <div class="price-current-minimal">{{ _price($selling_price) }}</div>
                                                @if ($pro->discount > 0 || $mrp > $selling_price)
                                                    <div class="price-old-minimal">{{ _price($mrp) }}</div>
                                                @endif
                                            </div>
                                        @endif

                                        <div class="product-action-minimal">
                                            @if ($pro->item_type == 'product' && ($pro->stock ?? 1) <= 0)
                                                <span class="badge bg-danger">Out of Stock</span>
                                            @elseif (($data['store_config']->lead_available ?? 1) == 0)
                                                <button disabled class="btn-product-minimal btn-enquiry-minimal" style="opacity:0.5;cursor:not-allowed;">
                                                    <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
                                                </button>
                                                <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                            @elseif (auth('web')->user())
                                                <button
                                                    onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                    class="btn-product-minimal btn-enquiry-minimal">
                                                    <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
                                                </button>
                                            @else
                                                <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                    class="btn-product-minimal btn-enquiry-minimal">
                                                    <i class="fas fa-paper-plane"></i> {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquire Now' }}
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if (!count($productdata))
                <div style="text-align: center; padding: 5rem 0; color: var(--text-secondary);">
                    <i class="fas fa-box-open" style="font-size: 5rem; margin-bottom: 2rem; opacity: 0.2;"></i>
                    <p style="font-size: 1.25rem;">No products available</p>
                </div>
            @endif

        </div>
    </div>

    <!-- Gallery -->
    @if (count($store->galleries))
        <div class="gallery-minimal">
            <div class="products-container">
                <div class="section-header-minimal">
                    <div class="section-label-minimal">Portfolio</div>
                    <h2 class="section-title-minimal">Gallery</h2>
                </div>

                <div class="gallery-grid-minimal">
                    @foreach ($data['galleries'] as $key => $value)
                        <a target="_blank" href="{{ asset('storage/app/public/store/gallery') }}/{{ $value->image }}"
                            class="gallery-item-minimal lightgallery-item">
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
        </div>
    @endif

    <!-- Reviews -->
    @if (count($data['reviews']) && $module == 6)
        <div class="reviews-minimal" id="reviews">
            <div class="products-container">
                <div class="section-header-minimal">
                    <div class="section-label-minimal">Testimonials</div>
                    <h2 class="section-title-minimal">Customer Reviews</h2>
                </div>

                @foreach ($data['reviews'] as $rev)
                    <div class="review-minimal">
                        <div class="review-header-minimal">
                            <img loading="lazy"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/') . '/' . $rev->profile_image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                class="reviewer-img" alt="{{ $rev->f_name }}">
                            <div class="reviewer-info-minimal">
                                <div class="reviewer-name-minimal">{{ $rev->f_name . ' ' . $rev->l_name }}</div>
                                <div class="review-date-minimal">{{ _formatted_datetime($rev->created_at) }}</div>
                                <div class="review-stars-minimal">
                                    @for ($i = 1; $i < 6; $i++)
                                        <i class="fas fa-star"
                                            style="color: {{ $rev->rating >= $i ? 'var(--accent-peach)' : '#e0e0e0' }};"></i>
                                    @endfor
                                </div>
                            </div>
                        </div>

                        <p class="review-text-minimal">{{ $rev->comment }}</p>

                        @if ($rev->attachment)
                            @php $attachments = (array) $rev->attachment; @endphp
                            @if (!empty($attachments))
                                <div class="review-images-minimal">
                                    @foreach ($attachments as $img)
                                        <a target="_blank"
                                            href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}">
                                            <img loading="lazy" class="review-img-minimal"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                alt="review">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        @if ($rev->reply)
                            <div class="reply-minimal">
                                <div class="reply-header-minimal">
                                    <img loading="lazy"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store['logo'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                        class="reply-avatar" alt="{{ $store->name }}">
                                    <div>
                                        <div style="font-weight: 500; font-size: 14px; color: var(--text-primary);">Store
                                            Response</div>
                                        <div style="font-size: 12px; color: var(--text-secondary);">
                                            {{ _formatted_datetime($rev->replied_at) }}</div>
                                    </div>
                                </div>
                                <p class="reply-text-minimal">{{ $rev->reply }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($data['review_count'] > 2)
                    <div style="text-align: center; margin-top: 3rem;">
                        <a href="{{ route('store.reviews', [$store->slug]) }}" class="btn-primary-minimal"
                            style="display: inline-block;">
                            View All Reviews
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Contact Section -->
    <div class="contact-section-minimal" id="contact">
        <div class="products-container">
            <div class="section-header-minimal">
                <div class="section-label-minimal">Reach Out</div>
                <h2 class="section-title-minimal">Contact Us</h2>
            </div>

            <div class="contact-grid-minimal">
                <div class="contact-card-minimal">
                    <div class="contact-icon-card">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/map.png" alt="Address">
                    </div>
                    <div class="contact-title-minimal">Our Location</div>
                    <div class="contact-text-minimal">{{ $store['address'] }}</div>
                </div>

                <div class="contact-card-minimal">
                    <div class="contact-icon-card">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/secured-letter.png" alt="Email">
                    </div>
                    <div class="contact-title-minimal">Email Address</div>
                    <div class="contact-text-minimal">
                        <a href="mailto:{{ $store['email'] }}">{{ $store['email'] }}</a>
                    </div>
                </div>

                <div class="contact-card-minimal">
                    <div class="contact-icon-card">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/phone.png" alt="Phone">
                    </div>
                    <div class="contact-title-minimal">Phone Number</div>
                    <div class="contact-text-minimal">
                        <a href="tel:{{ $store['phone'] }}">{{ $store['phone'] }}</a>
                    </div>
                </div>

                <div class="contact-card-minimal">
                    <div class="contact-icon-card">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/marker.png" alt="Map">
                    </div>
                    <div class="contact-title-minimal">Get Directions</div>
                    <div class="contact-text-minimal">
                        <a class="cursor-pointer" data-bs-toggle="modal" data-bs-target="#exampleModal">View on Map</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- About Section -->
    <div class="about-section-minimal" id="about">
        <div class="products-container">
            <div class="section-header-minimal">
                <div class="section-label-minimal">About</div>
                <h2 class="section-title-minimal">Our Story</h2>
            </div>

            <div class="about-content-minimal">
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
        if (document.querySelector('.gallery-grid-minimal')) {
            lightGallery(document.querySelector('.gallery-grid-minimal'), {
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
                btnText.innerText = "Read more";
            } else {
                dots.style.display = "none";
                moreText.classList.remove("d-none");
                btnText.innerText = "Show less";
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
