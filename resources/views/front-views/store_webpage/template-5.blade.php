@extends('front-views.layout')

@section('title', $store['meta_title'] ?? ($data['store_config']?->webpage_name ?? $store['name']))

@section('meta_keywords', $keywords)
@section('meta_description', $store['meta_description'])


@push('css_or_js') 
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --medical-blue: #0369a1;
            --medical-teal: #0891b2;
            --medical-light: #e0f2fe;
            --medical-bg: #f0f9ff;
            --medical-white: #ffffff;
            --medical-text: #0c4a6e;
            --medical-gray: #64748b;
            --medical-success: #059669;
            --medical-warning: #dc2626;
            --medical-accent: #0ea5e9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--medical-bg);
            color: var(--medical-text);
            line-height: 1.6;
        }

        /* Medical Header */
        .medical-header {
            background: linear-gradient(135deg, var(--medical-blue) 0%, var(--medical-teal) 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(3, 105, 161, 0.2);
        }

        .header-top {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 0;
            font-size: 12px;
        }

        .header-top-content {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .emergency-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .emergency-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .emergency-item i {
            color: #fbbf24;
        }

        .header-main {
            padding: 1rem 0;
        }

        .header-main-content {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .medical-logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .medical-logo {
            height: 60px;
            width: 60px;
            border-radius: 12px;
            background: white;
            padding: 8px;
            object-fit: contain;
        }

        .logo-text h1 {
            color:white;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .logo-text p {
            font-size: 12px;
            opacity: 0.9;
            margin: 0;
        }

        .medical-nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .medical-nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;  
            font-size: 14px; 
            transition: all 0.3s;
            padding: 0.5rem 0;
            border-bottom: 2px solid transparent;
        }
 
        .medical-nav a:hover {
            border-bottom-color: white;
        } 

        .appointment-btn {
            background: white;
            color: var(--medical-blue)  !important;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .appointment-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
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

     

        /* Breadcrumb Medical */
        .breadcrumb-medical {
            margin-top: 120px;
            background: white;
            padding: 1rem 0;
            border-bottom: 2px solid var(--medical-light);
        }

        .breadcrumb-content {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .breadcrumb-medical .breadcrumb {
            margin: 0;
            padding: 0;
            background: transparent;
            font-size: 13px;
        }

        .breadcrumb-item a {
            color: var(--medical-teal);
            text-decoration: none;
        }

        /* Hero Medical */
        .medical-hero {
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
            padding: 3rem 0;
            position: relative;
            overflow: hidden;
        }

        .medical-hero::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/><circle cx="50" cy="50" r="30" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/><circle cx="50" cy="50" r="20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></svg>');
            background-size: 200px;
            opacity: 0.3;
        }

        .hero-content {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
            position: relative;
            z-index: 1;
        }

        .hero-info {
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(3, 105, 161, 0.1);
        }

        .facility-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--medical-teal) 0%, var(--medical-accent) 100%);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .facility-name {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--medical-blue);
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }

        .facility-tagline {
            font-size: 1.125rem;
            color: var(--medical-gray);
            margin-bottom: 1.5rem;
        }

        .facility-description {
            color: var(--medical-text);
            line-height: 1.8;
            margin-bottom: 2rem;
            font-size: 14px;
        }

        .certifications {
            display: flex;
            gap: 1.5rem;
                flex-wrap: wrap;
            padding: 1.5rem 0;
            border-top: 2px solid var(--medical-light);
            border-bottom: 2px solid var(--medical-light);
            margin: 1.5rem 0;
        }

        .cert-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .cert-icon {
            width: 40px;
            height: 40px;
            background: var(--medical-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--medical-blue);
            font-size: 18px;
        }

        .cert-text {
            font-size: 13px;
            font-weight: 600;
            color: var(--medical-text);
        }

        .contact-quick {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .contact-card-quick {
            background: var(--medical-bg);
            padding: 1rem;
            border-radius: 10px;
            border-left: 4px solid var(--medical-teal);
        }

        .contact-label-quick {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--medical-gray);
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .contact-value-quick {
            font-size: 13px;
            color: var(--medical-text);
            font-weight: 600;
        }

        .contact-value-quick a {
            color: var(--medical-teal);
            text-decoration: none;
        }

        .hero-stats {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(3, 105, 161, 0.1);
        }

        .cover-medical {
            width: 100%;
            height: 200px;
            border-radius: 12px;
            object-fit: cover;
            margin-bottom: 1.5rem;
        }

        .rating-medical {
            text-align: center;
            padding: 1.5rem;
            background: var(--medical-bg);
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }

        .rating-score {
            font-size: 3rem;
            font-weight: 700;
            color: var(--medical-blue);
            line-height: 1;
        }

        .rating-stars-medical {
            margin: 0.5rem 0;
        }

        .rating-stars-medical i {
            color: #fbbf24;
            font-size: 18px;
        }

        .rating-reviews {
            font-size: 13px;
            color: var(--medical-gray);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .stat-box {
            text-align: center;
            padding: 1rem;
            background: var(--medical-bg);
            border-radius: 10px;
        }

        .stat-number {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--medical-teal);
            line-height: 1;
        }

        .stat-label {
            font-size: 11px;
            color: var(--medical-gray);
            margin-top: 0.25rem;
            text-transform: uppercase;
        }

        .share-medical {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--medical-light);
        }

        /* Alert Bar */
        .alert-medical {
            background: linear-gradient(135deg, var(--medical-success) 0%, #047857 100%);
            color: white;
            padding: 1rem 0;
            margin: 2rem 0;
        }

        .alert-content {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .alert-icon {
            font-size: 24px;
        }

        .alert-text {
            font-size: 14px;
            font-weight: 500;
        }

        /* Services Medical */
        .services-medical {
            padding: 3rem 0;
            background: white;
        }

        .services-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .section-header-medical {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .section-badge {
            display: inline-block;
            background: var(--medical-light);
            color: var(--medical-blue);
            padding: 0.5rem 1.25rem;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .section-title-medical {
            font-size: 2rem;
            font-weight: 700;
            color: var(--medical-blue);
            margin-bottom: 0.5rem;
        }

        .section-subtitle-medical {
            color: var(--medical-gray);
            font-size: 14px;
        }

        .department-header {
            background: linear-gradient(135deg, var(--medical-blue) 0%, var(--medical-teal) 100%);
            color: white;
            padding: 10px;
            border-radius: 12px;
            margin: 2.5rem 0 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 15px rgba(3, 105, 161, 0.2);
        }

        .department-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .department-name {
                color: white;
    font-size: 18px;
    font-weight: 700;
    margin: 0;
        }

        /* Medical Service Cards */
        .services-grid-medical {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.5rem;
        }

        .service-card-medical {
            background: white;
            border: 2px solid var(--medical-light);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            position: relative;
        }

        .service-card-medical:hover {
            border-color: var(--medical-teal);
            box-shadow: 0 8px 30px rgba(3, 105, 161, 0.15);
            transform: translateY(-4px);
        }

        .service-image-medical {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
            background: var(--medical-bg);
        }

        .service-image-medical img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .service-badge-medical {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--medical-warning);
            color: white;
            padding: 0.4rem 0.9rem;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }

        .service-time-medical {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            color: var(--medical-text);
            padding: 0.4rem 0.9rem;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .service-favorite {
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

        .service-favorite:hover {
            transform: scale(1.1);
        }

        .service-favorite i {
            font-size: 16px;
        }

        .text_red {
            color: var(--medical-warning);
        }

        .text_grey {
            color: #cbd5e1;
        }

        .service-body-medical {
            padding: 1.25rem;
        }

        .service-title-medical {
            font-size: 1rem;
            font-weight: 700;
            color: var(--medical-blue);
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 48px;
        }

        .service-variant-medical {
            font-size: 12px;
            color: var(--medical-gray);
            margin-bottom: 0.75rem;
            min-height: 18px;
        }

        .variant-more {
            display: inline-block;
            background: var(--medical-light);
            color: var(--medical-blue);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            margin-top: 0.25rem;
        }

        .service-pricing-medical {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 1rem 0;
            padding-top: 1rem;
            border-top: 2px solid var(--medical-light);
        }

        .price-current-medical {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--medical-teal);
        }

        .price-old-medical {
            font-size: 13px;
            color: var(--medical-gray);
            text-decoration: line-through;
            margin-top: 0.25rem;
        }

        .service-action-medical {
            margin-top: 1rem;
        }

        .btn-medical {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid var(--medical-teal);
            border-radius: 8px;
            background: white;
            color: var(--medical-teal);
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-medical:hover {
            background: var(--medical-teal);
            color: white;
        }

        .btn-medical-remove {
            background: #fee2e2;
            color: var(--medical-warning);
            border-color: #fee2e2;
        }

        .btn-medical-remove:hover {
            background: var(--medical-warning);
            color: white;
            border-color: var(--medical-warning);
        }

        .btn-medical-enquiry {
            background: linear-gradient(135deg, var(--medical-blue) 0%, var(--medical-teal) 100%);
            color: white;
            border: none;
        }

        .btn-medical-enquiry:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(3, 105, 161, 0.2);
        }

        /* Gallery Medical */
        .gallery-medical {
            padding: 3rem 0;
            background: var(--medical-bg);
        }

        .gallery-grid-medical{
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(148px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .gallery-item-medical {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            aspect-ratio: 4/3;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(3, 105, 161, 0.1);
            transition: all 0.3s;
        }

        .gallery-item-medical:hover {
            box-shadow: 0 8px 30px rgba(3, 105, 161, 0.2);
            transform: translateY(-4px);
        }

        .gallery-item-medical img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gallery-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(3, 105, 161, 0.9) 0%, transparent 100%);
            padding: 1.5rem;
            color: white;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .gallery-item-medical:hover .gallery-overlay {
            opacity: 1;
        }

        /* Reviews Medical */
        .reviews-medical {
            padding: 3rem 0;
            background: white;
        }

        .review-card-medical {
            background: white;
            border: 2px solid var(--medical-light);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }

        .review-card-medical:hover {
            border-color: var(--medical-teal);
            box-shadow: 0 6px 25px rgba(3, 105, 161, 0.1);
        }

        .review-header-medical {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .patient-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid var(--medical-light);
            object-fit: cover;
        }

        .patient-info {
            flex: 1;
        }

        .patient-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--medical-blue);
            margin-bottom: 0.25rem;
        }

        .review-date-medical {
            font-size: 12px;
            color: var(--medical-gray);
            margin-bottom: 0.5rem;
        }

        .review-stars-medical {
            display: flex;
            gap: 0.25rem;
        }

        .review-stars-medical i {
            font-size: 14px;
            color: #fbbf24;
        }

        .review-text-medical {
            color: var(--medical-text);
            line-height: 1.7;
            font-size: 14px;
            margin-top: 1rem;
        }

        .review-images-medical {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .review-image-medical {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid var(--medical-light);
        }

        .facility-reply {
            margin-top: 1rem;
            padding: 1rem;
            background: var(--medical-bg);
            border-left: 4px solid var(--medical-teal);
            border-radius: 8px;
        }

        .reply-header-medical {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .facility-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }

        .reply-text-medical {
            font-size: 13px;
            color: var(--medical-text);
            line-height: 1.6;
        }

        /* Contact Medical */
        .contact-medical {
            padding: 3rem 0;
            background: var(--medical-bg);
        }

        .contact-grid-medical {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .contact-card-medical {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            border: 2px solid var(--medical-light);
            transition: all 0.3s;
        }

        .contact-card-medical:hover {
            border-color: var(--medical-teal);
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(3, 105, 161, 0.15);
        }

        .contact-icon-medical {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--medical-blue) 0%, var(--medical-teal) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .contact-icon-medical img {
            width: 35px;
            height: 35px;
            filter: brightness(0) invert(1);
        }

        .contact-title-medical {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--medical-blue);
            margin-bottom: 0.5rem;
        }

        .contact-text-medical {
            color: var(--medical-text);
            font-size: 14px;
        }

        .contact-text-medical a {
            color: var(--medical-teal);
            text-decoration: none;
            font-weight: 600;
        }

        /* About Medical */
        .about-medical {
            padding: 3rem 0;
            background: white;
        }

        .about-content-medical {
            background: var(--medical-bg);
            border-radius: 12px;
            padding: 2.5rem;
            border-left: 6px solid var(--medical-teal);
            line-height: 1.8;
            font-size: 14px;
        }

        /* Banner */
        .banner-medical {
            margin: 2rem 0;
        }

        .banner-medical img {
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(3, 105, 161, 0.1);
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
            background: linear-gradient(135deg, var(--medical-blue) 0%, var(--medical-teal) 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        /* LightGallery */
        .lg-counter {
            background: rgba(3, 105, 161, 0.8) !important;
            padding: 8px 16px !important;
            border-radius: 8px !important;
        }

        .lg-next.lg-icon,
        .lg-prev.lg-icon {
            background: var(--medical-teal);
            color: white;
            border-radius: 50%;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-content {
                grid-template-columns: 1fr;
            }

            .services-grid-medical {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }

            .medical-nav {
                display: none;
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
            color: #fbbf24;
            width: 0;
        }
           @media (max-width: 768px) {
      .contact-quick{
            display: flex;
    flex-direction: column;
      }
            .mobile-menu-toggle { display: block; }
            .header-top { display: none; }
            .header-main {
                padding: 0.5rem 0;
            }
            .header-main-content {
                position: relative;
                padding: 0 1rem;
            }
            .medical-logo-section {
                flex: 1;
                min-width: 0;
                overflow: hidden;
                gap: 0.75rem;
            }
            .medical-logo {
                height: 40px;
                width: 40px;
                padding: 5px;
                flex-shrink: 0;
            }
            .logo-text {
                overflow: hidden;
                min-width: 0;
            }
            .logo-text h1 {
                font-size: 0.85rem;
                display: -webkit-box;
                -webkit-line-clamp: 1;
                -webkit-box-orient: vertical;
                overflow: hidden;
                line-height: 1.3;
            }
            .logo-text p {
                display: none;
            }
            .medical-nav {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, var(--medical-blue) 0%, var(--medical-teal) 100%);
                flex-direction: column;
                padding: 8px 0;
                gap: 0;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                z-index: 10;
            }
            .medical-nav.show { display: flex; }
            .medical-nav a {
                padding: 10px 20px;
                display: block;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
            .medical-nav a:hover {
                border-bottom-color: rgba(255,255,255,0.1);
            }
            .medical-nav .appointment-btn {
                margin: 10px 20px;
                text-align: center;
                display: block;
            }
            .breadcrumb-medical {
                margin-top: 65px;
                padding: 0.75rem 0;
            }
            .breadcrumb-content {
                padding: 0 1rem;
            }
            .medical-hero {
                padding: 1.5rem 0;
            }
            .hero-content {
                padding: 0 1rem;
                gap: 1.5rem; 
                display: block;
            }
            .hero-info {
                padding: 1.5rem;
            }
            .facility-name {
                font-size: 1.5rem;
            }
            .facility-tagline {
                font-size: 0.95rem;
            }
            .hero-stats {
                padding: 1.5rem;
                margin: 10px 0 ;
            }
            .cover-medical {
                height: auto;
            }
            .alert-medical {
                padding: 0.5rem 0;
                margin: 1rem 0;
            }
            .alert-content {
                padding: 0 1rem;
                gap: 0.75rem;
            }
            .alert-icon {
                font-size: 16px;
            }
            .alert-text {
                font-size: 12px;
            }
            .services-container {
                padding: 0 1rem;
            }
            .services-medical,
            .gallery-medical,
            .reviews-medical,
            .contact-medical,
            .about-medical {
                padding: 2rem 0;
            }
            .section-title-medical {
                font-size: 1.5rem;
            }
            .section-subtitle-medical {
                font-size: 13px;
            }
            .department-header {
                padding: 8px 12px;
                margin: 1.5rem 0 1rem;
                gap: 0.75rem;
            }
            .department-icon {
                width: 36px;
                height: 36px;
                font-size: 18px;
            }
            .department-name {
                font-size: 15px;
            }
            .services-grid-medical {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            .service-image-medical {
                height: 140px;
            }
            .service-body-medical {
                padding: 0.75rem;
            }
            .service-title-medical {
                font-size: 0.8rem;
                min-height: 36px;
            }
            .price-current-medical {
                font-size: 1.1rem;
            }
            .service-pricing-medical {
                margin: 0.5rem 0;
                padding-top: 0.5rem;
            }
            .btn-medical {
                padding: 0.5rem;
                font-size: 11px;
                gap: 0.35rem;
            }
            .gallery-grid-medical {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.5rem;
            }
            .contact-grid-medical {
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }
            .contact-card-medical {
                padding: 1.25rem;
            }
            .contact-icon-medical {
                width: 50px;
                height: 50px;
                margin-bottom: 0.75rem;
            }
            .contact-icon-medical img {
                width: 24px;
                height: 24px;
            }
            .contact-title-medical {
                font-size: 0.95rem;
            }
            .contact-text-medical {
                font-size: 12px;
            }
            .about-content-medical {
                padding: 1.5rem;
            }
            .review-card-medical {
                padding: 1rem;
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
    <!-- Medical Header -->
    <header class="medical-header">
        <div class="header-top">
            <div class="header-top-content">
                <div class="emergency-info">
                    <div class="emergency-item">
                        <i class="fas fa-ambulance"></i>
                        <span>24/7 Emergency Services</span>
                    </div>
                    <div class="emergency-item">
                        <i class="fas fa-phone-alt"></i>
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
                </div>
                <div>
                    <span>📍 {{ Str::limit($store['address'], 50) }}</span>
                </div>
            </div>
        </div>
        <div class="header-main">
            <div class="header-main-content">
                <div class="medical-logo-section">
                    <img loading="lazy" class="medical-logo" src="{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}" alt="{{ $store['name'] }}">
                    <div class="logo-text">
                        <h1>{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                        <p>{{ $store['meta_title'] }}</p>
                    </div>
                </div>
                <button class="mobile-menu-toggle" onclick="document.querySelector('.medical-nav').classList.toggle('show')">
                    <i class="fas fa-bars"></i>
                </button>
                <nav class="medical-nav">
                    <a href="#services">Services</a>
                    <a href="#reviews">Reviews</a>
                    <a href="#contact">Contact</a>
                    <a href="#about">About</a>
                    <a href="{{ route('store.gallery', [$store['slug']]) }}" class="appointment-btn">View Gallery</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Breadcrumb -->
    <div class="breadcrumb-medical">
        <div class="breadcrumb-content">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Medical Hero -->
    <div class="medical-hero">
        <div class="hero-content">
            <div class="hero-info">
                <span class="facility-badge">
                    <i class="fas fa-hospital"></i> Healthcare Facility
                </span>
                <h1 class="facility-name">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                <p class="facility-tagline">{{ $store['meta_title'] }}</p>

                @php
                    $description = $store['meta_description'];
                    $short = Str::limit($description, 250);
                @endphp
                <div class="facility-description" id="text-{{ $store['id'] }}">
                    {!! $short !!}
                    @if (strlen($description) > 250)
                        <span id="dots-{{ $store['id'] }}"></span>
                        <span id="more-{{ $store['id'] }}" class="d-none">{!! substr($description, 250) !!}</span>
                        <a class="cursor-pointer" style="color: var(--medical-teal); font-weight: 600;" onclick="toggleReadMore({{ $store['id'] }})"
                            id="btn-{{ $store['id'] }}">Read more</a>
                    @endif
                </div>

                <div class="certifications">
                    <div class="cert-item">
                        <div class="cert-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="cert-text">Certified</div>
                    </div>
                    <div class="cert-item">
                        <div class="cert-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="cert-text">Expert Staff</div>
                    </div>
                    <div class="cert-item">
                        <div class="cert-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="cert-text">24/7 Available</div>
                    </div>
                </div>

                <div class="contact-quick">
                    <div class="contact-card-quick">
                        <div class="contact-label-quick">Phone</div>
                        <div class="contact-value-quick">
                            <a href="tel:{{ $store['phone'] }}">{{ $store['phone'] }}</a>
                        </div>
                    </div>
                    <div class="contact-card-quick">
                        <div class="contact-label-quick">Email</div>
                        <div class="contact-value-quick">
                            <a href="mailto:{{ $store['email'] }}">{{ $data['store_config']?->webpage_email ?? $store->email }}</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hero-stats">
                <img loading="lazy" class="cover-medical" src="{{ asset('storage/app/public/store/cover/') . '/' . $store['cover_photo'] }}" alt="Facility">
                
                <div class="rating-medical">
                    @php $store_rating = number_format($store->average_rating, 1); @endphp
                    <div class="rating-score">{{ $store_rating }}</div>
                    <div class="rating-stars-medical rating-stars" data-rating="{{ $store_rating }}">
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
                    <div class="rating-reviews">Based on {{ $store->rating_count }} patient reviews</div>
                </div>

                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-number">{{ $store->rating_count }}+</div>
                        <div class="stat-label">Patients Served</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number">{{ count($productdata) }}+</div>
                        <div class="stat-label">Services</div>
                    </div>
                </div>

                <div class="share-medical">
                    <div class="sharethis-inline-share-buttons"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Banners -->
    @if(count($data['banners']) > 0)
    <div style="max-width: 1300px; margin: 0 auto; padding: 0 2rem;">
        <div class="banner-medical">
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
        <div class="alert-medical">
            <div class="alert-content">
                <i class="fas fa-bullhorn alert-icon"></i>
                <div class="alert-text">{{ $store->announcement_message }}</div>
            </div>
        </div>
    @endif

    <!-- Services -->
    <div class="services-medical" id="services">
        <div class="services-container">

            <div class="section-header-medical">
                <span class="section-badge">Our Services</span>
                <h2 class="section-title-medical">Medical Services & Treatments</h2>
                <p class="section-subtitle-medical">Quality healthcare services tailored to your needs</p>
            </div>

            @foreach ($productdata as $key => $cat)
                <div class="department-header">
                    <div class="department-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <h3 class="department-name">{{ $cat->name }}</h3>
                </div>

                <div class="services-grid-medical">
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
                        <div class="pr_{{ $pro->id }} service-card-medical">
                            <div class="service-image-medical">
                                <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                    <img loading="lazy" 
                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                        alt="{{ $pro->name }}">
                                </a>

                                @if ($module == 5 && $store->delivery_time)
                                    <div class="service-time-medical">
                                        <i class="fas fa-clock"></i>
                                        {{ strtoupper($store->delivery_time) }}
                                    </div>
                                @endif

                                @if ($pro->discount > 0)
                                    <div class="service-badge-medical">
                                        {{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '% OFF' : \App\CentralLogics\Helpers::currency_symbol() . ' OFF' }}
                                    </div>
                                @endif

                                <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                    class="prHeart_{{ $pro->id }} service-favorite">
                                    <i class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                </div>
                            </div>

                            <div class="service-body-medical">
                                <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                    <h4 class="service-title-medical" title="{{ ucfirst($pro->name) }}">
                                        {{ ucfirst($pro->name) }}
                                    </h4>
                                </a>

                                @if ($module == 5)
                                    <p class="service-variant-medical">
                                        {{ !empty($variations) ? $variations[0]->type : '' }}
                                    </p>
                                    @if (count($variations) > 1)
                                        <span class="variant-more">+{{ count($variations) - 1 }} options</span>
                                    @endif

                                    <div class="service-pricing-medical">
                                        <div>
                                            <div class="price-current-medical">{{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0)
                                                <div class="price-old-medical">{{ _price($mrp) }}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="service-action-medical cartSec_{{ $pro->id }}">
                                        @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                        @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                            <button onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                class="btn-medical btn-medical-remove">
                                                <i class="fa fa-times"></i> Remove from Cart
                                            </button>
                                        @else
                                            <button onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                class="btn-medical">
                                                <i class="fa fa-shopping-bag"></i> Add to Cart
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    @if ($pro->item_type == 'product')
                                        <div class="service-pricing-medical">
                                            <div>
                                                <div class="price-current-medical">{{ _price($selling_price) }}</div>
                                                @if ($pro->discount > 0 || $mrp > $selling_price)
                                                    <div class="price-old-medical">{{ _price($mrp) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    <div class="service-action-medical">
                                        @if (($data['store_config']->lead_available ?? 1) == 0)
                                            <button disabled class="btn-medical btn-medical-enquiry" style="opacity:0.5;cursor:not-allowed;">
                                                <i class="fas fa-calendar-check"></i> Book Appointment
                                            </button>
                                            <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                        @elseif (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                class="btn-medical btn-medical-enquiry">
                                                <i class="fas fa-calendar-check"></i> Book Appointment
                                            </button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="btn-medical btn-medical-enquiry">
                                                <i class="fas fa-calendar-check"></i> Book Appointment
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
                <div class="department-header">
                    <div class="department-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <h3 class="department-name">{{ $cat->name }}</h3>
                </div>

                <div class="services-grid-medical">
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
                        <div class="pr_{{ $pro->id }} service-card-medical">
                            <div class="service-image-medical">
                                <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                    <img loading="lazy"
                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                        alt="{{ $pro->name }}">
                                </a>

                                @if ($module == 5 && $store->delivery_time)
                                    <div class="service-time-medical">
                                        <i class="fas fa-clock"></i>
                                        {{ strtoupper($store->delivery_time) }}
                                    </div>
                                @endif

                                @if ($pro->discount > 0)
                                    <div class="service-badge-medical">
                                        {{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '% OFF' : \App\CentralLogics\Helpers::currency_symbol() . ' OFF' }}
                                    </div>
                                @endif

                                <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                    class="prHeart_{{ $pro->id }} service-favorite">
                                    <i class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                </div>
                            </div>

                            <div class="service-body-medical">
                                <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                    <h4 class="service-title-medical" title="{{ ucfirst($pro->name) }}">
                                        {{ ucfirst($pro->name) }}
                                    </h4>
                                </a>

                                @if ($module == 5)
                                    <p class="service-variant-medical">
                                        {{ !empty($variations) ? $variations[0]->type : '' }}
                                    </p>
                                    @if (count($variations) > 1)
                                        <span class="variant-more">+{{ count($variations) - 1 }} options</span>
                                    @endif

                                    <div class="service-pricing-medical">
                                        <div>
                                            <div class="price-current-medical">{{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0)
                                                <div class="price-old-medical">{{ _price($mrp) }}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="service-action-medical cartSec_{{ $pro->id }}">
                                        @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                        @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                            <button onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                class="btn-medical btn-medical-remove">
                                                <i class="fa fa-times"></i> Remove from Cart
                                            </button>
                                        @else
                                            <button onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                class="btn-medical">
                                                <i class="fa fa-shopping-bag"></i> Add to Cart
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    @if ($pro->item_type == 'product')
                                        <div class="service-pricing-medical">
                                            <div>
                                                <div class="price-current-medical">{{ _price($selling_price) }}</div>
                                                @if ($pro->discount > 0 || $mrp > $selling_price)
                                                    <div class="price-old-medical">{{ _price($mrp) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    <div class="service-action-medical">
                                        @if (($data['store_config']->lead_available ?? 1) == 0)
                                            <button disabled class="btn-medical btn-medical-enquiry" style="opacity:0.5;cursor:not-allowed;">
                                                <i class="fas fa-calendar-check"></i> Book Appointment
                                            </button>
                                            <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                        @elseif (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                class="btn-medical btn-medical-enquiry">
                                                <i class="fas fa-calendar-check"></i> Book Appointment
                                            </button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="btn-medical btn-medical-enquiry">
                                                <i class="fas fa-calendar-check"></i> Book Appointment
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
                <div style="text-align: center; padding: 4rem 0; color: var(--medical-gray);">
                    <i class="fas fa-box-open" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p style="font-size: 1.125rem;">No services available at the moment</p>
                </div>
            @endif

        </div>
    </div>

    <!-- Gallery -->
    @if (count($store->galleries))
        <div class="gallery-medical">
            <div class="services-container">
                <div class="section-header-medical">
                    <span class="section-badge">Facility Tour</span>
                    <h2 class="section-title-medical">Our Facilities & Infrastructure</h2>
                    <p class="section-subtitle-medical">Take a virtual tour of our modern healthcare facility</p>
                </div>

                <div class="gallery-grid-medical">
                    @foreach ($data['galleries'] as $value)
                        <a target="_blank"
                            href="{{ asset('storage/app/public/store/gallery') }}/{{ $value->image }}"
                            class="gallery-item-medical lightgallery-item">
                            <img loading="lazy" 
                                 data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                    $value['image'] ?? '',
                                    asset('storage/app/public/store/gallery') . '/' . $value['image'] ?? '',
                                    asset('public/assets/admin/img/160x160/img1.jpg'),
                                    'store/gallery/',
                                ) }}"
                                alt="Facility">
                            <div class="gallery-overlay">
                                <i class="fas fa-search-plus" style="font-size: 24px;"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Reviews -->
    @if (count($data['reviews']) && $module == 6)
        <div class="reviews-medical" id="reviews">
            <div class="services-container">
                <div class="section-header-medical">
                    <span class="section-badge">Testimonials</span>
                    <h2 class="section-title-medical">Patient Reviews & Feedback</h2>
                    <p class="section-subtitle-medical">What our patients say about us</p>
                </div>

                @foreach ($data['reviews'] as $rev)
                    <div class="review-card-medical">
                        <div class="review-header-medical">
                            <img loading="lazy" 
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/') . '/' . $rev->profile_image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                class="patient-avatar"
                                alt="{{ $rev->f_name }}">
                            <div class="patient-info">
                                <div class="patient-name">{{ $rev->f_name . ' ' . $rev->l_name }}</div>
                                <div class="review-date-medical">{{ _formatted_datetime($rev->created_at) }}</div>
                                <div class="review-stars-medical">
                                    @for ($i = 1; $i < 6; $i++)
                                        <i class="fas fa-star" style="color: {{ $rev->rating >= $i ? '#fbbf24' : '#e5e7eb' }};"></i>
                                    @endfor
                                </div>
                            </div>
                        </div>

                        <p class="review-text-medical">{{ $rev->comment }}</p>

                        @if ($rev->attachment)
                            @php $attachments = json_decode($rev->attachment); @endphp
                            @if (!empty($attachments))
                                <div class="review-images-medical">
                                    @foreach ($attachments as $img)
                                        <a target="_blank" href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}">
                                            <img loading="lazy" class="review-image-medical"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                alt="review">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        @if ($rev->reply)
                            <div class="facility-reply">
                                <div class="reply-header-medical">
                                    <img loading="lazy" 
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store['logo'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                        class="facility-avatar"
                                        alt="{{ $store->name }}">
                                    <div>
                                        <div style="font-weight: 600; font-size: 13px; color: var(--medical-blue);">Facility Response</div>
                                        <div style="font-size: 11px; color: var(--medical-gray);">{{ _formatted_datetime($rev->replied_at) }}</div>
                                    </div>
                                </div>
                                <p class="reply-text-medical">{{ $rev->reply }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($data['review_count'] > 2)
                    <div style="text-align: center; margin-top: 2rem;">
                        <a href="{{ route('store.reviews', [$store->slug]) }}" class="appointment-btn" style="display: inline-block;">
                            View All Patient Reviews <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Contact -->
    <div class="contact-medical" id="contact">
        <div class="services-container">
            <div class="section-header-medical">
                <span class="section-badge">Get In Touch</span>
                <h2 class="section-title-medical">Contact Information</h2>
                <p class="section-subtitle-medical">We're here to help you 24/7</p>
            </div>

            <div class="contact-grid-medical">
                <div class="contact-card-medical">
                    <div class="contact-icon-medical">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/map.png" alt="Address">
                    </div>
                    <div class="contact-title-medical">Our Location</div>
                    <div class="contact-text-medical">{{ $store['address'] }}</div>
                </div>

                <div class="contact-card-medical">
                    <div class="contact-icon-medical">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/secured-letter.png" alt="Email">
                    </div>
                    <div class="contact-title-medical">Email Us</div>
                    <div class="contact-text-medical">
                        <a href="mailto:{{ $store['email'] }}">{{ $store['email'] }}</a>
                    </div>
                </div>

                <div class="contact-card-medical">
                    <div class="contact-icon-medical">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/phone.png" alt="Phone">
                    </div>
                    <div class="contact-title-medical">Call Us</div>
                    <div class="contact-text-medical">
                        <a href="tel:{{ $store['phone'] }}">{{ $store['phone'] }}</a>
                    </div>
                </div>

                <div class="contact-card-medical">
                    <div class="contact-icon-medical">
                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/marker.png" alt="Map">
                    </div>
                    <div class="contact-title-medical">View on Map</div>
                    <div class="contact-text-medical">
                        <a class="cursor-pointer" data-bs-toggle="modal" data-bs-target="#exampleModal">Get Directions</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- About -->
    <div class="about-medical" id="about">
        <div class="services-container">
            <div class="section-header-medical">
                <span class="section-badge">About Us</span>
                <h2 class="section-title-medical">Our Story & Mission</h2>
                <p class="section-subtitle-medical">Learn more about our healthcare facility</p>
            </div>

            <div class="about-content-medical">
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
        if (document.querySelector('.gallery-grid-medical')) {
            lightGallery(document.querySelector('.gallery-grid-medical'), {
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