@extends('front-views.layout')

@section('title', $store['meta_title'] ?? ($data['store_config']?->webpage_name ?? $store['name']))

@section('meta_keywords', $keywords)
@section('meta_description', $store['meta_description'])

 
@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --neon-pink: #ff006e;
            --neon-blue: #00f5ff;
            --neon-purple: #8338ec;
            --neon-green: #06ffa5;
            --dark-bg: #0a0e27;
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--dark-bg);
            color: white;
            overflow-x: hidden;
        }

        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .wave {
            position: absolute;
            width: 200%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 0, 110, 0.05), transparent);
            animation: waveMove 15s linear infinite;
        }

        .wave:nth-child(2) {
            background: linear-gradient(90deg, transparent, rgba(0, 245, 255, 0.05), transparent);
            animation-delay: -7s;
        }

        @keyframes waveMove {
            0% {
                transform: translateX(-50%);
            }

            100% {
                transform: translateX(0);
            }
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .shape {
            position: absolute;
            opacity: 0.1;
            animation: float 20s infinite ease-in-out;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            background: var(--neon-pink);
            border-radius: 50%;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 60px;
            height: 60px;
            background: var(--neon-blue);
            border-radius: 30%;
            top: 60%;
            right: 20%;
            animation-delay: -5s;
        }

        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            background: var(--neon-purple);
            border-radius: 20%;
            bottom: 10%;
            left: 30%;
            animation-delay: -10s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-50px) rotate(180deg);
            }
        }

        /* Glass Header */
        .glass-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 999;
            background: rgba(10, 14, 39, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .header-content {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0.6rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-glass {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .logo-img-glass {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 2px solid var(--neon-pink);
            box-shadow: 0 0 20px var(--neon-pink);
            animation: glowPulse 2s infinite;
        }

        @keyframes glowPulse {

            0%,
            100% {
                box-shadow: 0 0 20px var(--neon-pink);
            }

            50% {
                box-shadow: 0 0 30px var(--neon-pink), 0 0 40px var(--neon-pink);
            }
        }

        .logo-text-glass {
            font-size: 1rem;
            font-weight: 800;
            background: linear-gradient(90deg, var(--neon-pink), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: 1px solid var(--glass-border);
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .mobile-menu-toggle:hover {
            border-color: var(--neon-pink);
            box-shadow: 0 0 10px rgba(255, 0, 110, 0.3);
        }

        .nav-glass {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-glass a {
            color: white;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            position: relative;
            transition: all 0.3s;
        }

        .nav-glass a::before {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--neon-pink), var(--neon-blue));
            transition: width 0.3s;
        }

        .nav-glass a:hover {
            color: var(--neon-pink);
        }

        .nav-glass a:hover::before {
            width: 100%;
        }

        .btn-neon {
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 0 20px rgba(255, 0, 110, 0.5);
            transition: all 0.3s;
        }

        .btn-neon:hover {
            box-shadow: 0 0 30px rgba(255, 0, 110, 0.8);
            transform: translateY(-2px);
            color: white;
        }

        /* Compact Hero */
        .hero-compact {
            position: relative;
            margin-top: 55px;
            padding: 3rem 0;
            min-height: 400px;
            display: flex;
            align-items: center;
        }

        .hero-grid-compact {
            position: relative;
            z-index: 1;
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            align-items: center;
        }

        .hero-left-compact {
            animation: slideInLeft 0.8s ease-out;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .hero-tag-compact {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 0.8rem;
            color: var(--neon-green);
        }

        .hero-title-compact {
            font-size: 2.5rem;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 0.8rem;
            background: linear-gradient(135deg, white, var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-desc-compact {
            font-size: 0.875rem;
            opacity: 0.8;
            margin-bottom: 1.2rem;
            line-height: 1.6;
        }

        .hero-actions-compact {
            display: flex;
            gap: 0.8rem;
        }

        .btn-primary-compact {
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 0 20px rgba(255, 0, 110, 0.5);
            transition: all 0.3s;
        }

        .btn-primary-compact:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 30px rgba(255, 0, 110, 0.8);
            color: white;
        }

        .btn-secondary-compact {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-secondary-compact:hover {
            border-color: var(--neon-blue);
            color: white;
        }

        .hero-right-compact {
            position: relative;
            animation: slideInRight 0.8s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 0.8rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .hero-img-compact {
            width: 100%;
            height: 280px;
            object-fit: cover;
            border-radius: 12px;
        }

        .stats-mini {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.6rem;
            margin-top: 0.8rem;
        }

        .stat-mini {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 0.6rem;
            border-radius: 10px;
            text-align: center;
        }

        .stat-val-mini {
            font-size: 1.25rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }

        .stat-lbl-mini {
            font-size: 8px;
            margin-top: 0.3rem;
            opacity: 0.7;
            text-transform: uppercase;
        }

        /* Breadcrumb */
        .breadcrumb-glass {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--glass-border);
            padding: 0.5rem 0;
        }

        .breadcrumb-glass .breadcrumb {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 1.5rem;
            background: transparent;
            font-size: 11px;
        }

        /* Minimized Cards Section */
        .cards-section {
            position: relative;
            z-index: 1;
            padding: 2rem 0;
        }

        .cards-wrapper {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .section-head-mini {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .section-tag-mini {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 0.6rem;
            color: var(--neon-green);
        }

        .section-title-mini {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(135deg, white, var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Ultra Minimized Cards */
        .cards-grid-mini {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 0.8rem;
            margin-top: 1.5rem;
        }

        .mini-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            cursor: pointer;
        }

        .mini-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            opacity: 0;
            transition: opacity 0.3s;
        }

        .mini-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 40px rgba(255, 0, 110, 0.4);
        }

        .mini-card:hover::before {
            opacity: 0.1;
        }

        .mini-img {
            position: relative;
            width: 100%;
            height: 150px;
            overflow: hidden;
        }

        .mini-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .mini-card:hover .mini-img img {
            transform: scale(1.15);
        }

        .discount-mini {
            position: absolute;
            top: 6px;
            left: 6px;
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            color: white;
            padding: 0.25rem 0.6rem;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 900;
            box-shadow: 0 0 15px rgba(255, 0, 110, 0.6);
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

        .time-mini {
            position: absolute;
            top: 6px;
            right: 6px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 0.25rem 0.6rem;
            border-radius: 10px;
            font-size: 8px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .heart-mini {
            position: absolute;
            bottom: 6px;
            right: 6px;
            width: 30px;
            height: 30px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .heart-mini:hover {
            transform: scale(1.2);
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            border: none;
        }

        .heart-mini:hover i {
            color: white !important;
        }

        .heart-mini i {
            font-size: 14px;
        }

        .text_red {
            color: var(--neon-pink);
        }

        .text_grey {
            color: rgba(137, 137, 137, 0.64);
        }

        .mini-body {
            padding: 0.6rem;
        }

        .mini-title {
            font-size: 0.8125rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 38px;
        }

        .mini-variant {
            font-size: 9px;
            opacity: 0.7;
            margin-bottom: 0.4rem;
            min-height: 14px;
        }

        .mini-price {
            display: flex;
            align-items: baseline;
            gap: 0.4rem;
            margin: 0.5rem 0;
        }

        .price-now-mini {
            font-size: 1.125rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .price-old-mini {
            font-size: 0.75rem;
            opacity: 0.5;
            text-decoration: line-through;
        }

        .btn-mini {
            width: 100%;
            padding: 0.5rem;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            color: white;
            font-weight: 700;
            font-size: 10px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            box-shadow: 0 0 15px rgba(255, 0, 110, 0.4);
        }

        .btn-mini:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 25px rgba(255, 0, 110, 0.6);
        }

        .btn-remove-mini {
            background: rgba(255, 0, 0, 0.2);
            border: 1px solid rgba(255, 0, 0, 0.5);
        }

        /* Minimized Gallery */
        .gallery-mini {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 0.6rem;
            margin-top: 1.5rem;
        }

        .gallery-item-mini {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            aspect-ratio: 1;
            cursor: pointer;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            transition: all 0.3s;
        }

        .gallery-item-mini:hover {
            transform: scale(1.05);
            box-shadow: 0 0 30px rgba(0, 245, 255, 0.5);
        }

        .gallery-item-mini img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .gallery-item-mini:hover img {
            transform: scale(1.2);
        }

        .gallery-overlay-mini {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 0, 110, 0.8), rgba(131, 56, 236, 0.8));
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .gallery-item-mini:hover .gallery-overlay-mini {
            opacity: 1;
        }

        .gallery-icon-mini {
            font-size: 2rem;
            color: white;
        }

        /* Split Info */
        .split-info-mini {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .about-mini {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            padding: 1.2rem;
            border-radius: 15px;
        }

        .about-mini h2 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.8rem;
            background: linear-gradient(135deg, white, var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .about-text-mini {
            font-size: 0.8125rem;
            line-height: 1.6;
            opacity: 0.8;
        }

        .contact-mini {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            padding: 1.2rem;
            border-radius: 15px;
        }

        .contact-mini h3 {
            font-size: 1.125rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--neon-green);
        }

        .contact-row-mini {
            display: flex;
            align-items: start;
            gap: 0.8rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--glass-border);
        }

        .contact-row-mini:last-child {
            border: none;
        }

        .contact-icon-mini {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
            box-shadow: 0 0 15px rgba(255, 0, 110, 0.4);
        }

        .rating-mini {
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
            margin-top: 1rem;
            box-shadow: 0 0 20px rgba(255, 0, 110, 0.4);
        }

        .rating-num-mini {
            font-size: 2.5rem;
            font-weight: 900;
            line-height: 1;
        }

        /* Announcement */
        .announce-mini {
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            color: white;
            padding: 0.6rem 0;
            box-shadow: 0 0 20px rgba(255, 0, 110, 0.4);
        }

        .announce-wrap-mini {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        /* Category Header */
        .category-mini {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            padding: 0.8rem 1.2rem;
            border-radius: 12px;
            margin: 2rem 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .category-mini h3 {
            font-size: 1.375rem;
            font-weight: 800;
            background: linear-gradient(135deg, white, var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }

        .category-count-mini {
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-weight: 700;
            font-size: 11px;
            box-shadow: 0 0 15px rgba(255, 0, 110, 0.4);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .mobile-menu-toggle {
                display: block;
            }

            .hero-grid-compact {
                grid-template-columns: 1fr;
            }

            .split-info-mini {
                grid-template-columns: 1fr;
            }

            .nav-glass {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: rgba(10, 14, 39, 0.95);
                backdrop-filter: blur(20px);
                flex-direction: column;
                padding: 10px 0;
                gap: 0;
                border-bottom: 1px solid var(--glass-border);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
                z-index: 10;
            }

            .nav-glass.show {
                display: flex;
            }

            .nav-glass a {
                padding: 12px 24px;
                display: block;
                font-size: 14px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            }

            .nav-glass a:last-child {
                border-bottom: none;
            }

            .nav-glass a::before {
                display: none;
            }

            .nav-glass .btn-neon {
                margin: 10px 24px;
                text-align: center;
                border-radius: 10px;
            }

            .cards-grid-mini {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            }

            .gallery-mini {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
        }

        .mini-title {
            color: white !important;
        }

        @media (max-width: 768px) {
            .logo-text-glass {
                max-width: 140px;
                font-size: 0.85rem;
            }

            .hero-title-compact {
                font-size: 1.8rem;
            }

            .hero-desc-compact {
                font-size: 0.8rem;
            }

            .hero-img-compact {
                height: auto;
            }

            .hero-actions-compact {
                flex-wrap: wrap;
            }

            .cards-grid-mini {
                grid-template-columns: repeat(2, 1fr);
            }

            .mini-title {
                font-size: 11px;
            }

            .gallery-mini {
                grid-template-columns: repeat(3, 1fr);
            }

            .section-title-mini {
                font-size: 1.5rem;
            }

            .category-mini h3 {
                font-size: 1.1rem;
            }

            .announce-wrap-mini {
                padding: 0 1rem;
            }

            .announce-wrap-mini i {
                font-size: 14px !important;
            }

            .announce-wrap-mini div {
                font-size: 11px !important;
            }

            .cards-wrapper {
                padding: 0 1rem;
            }

            .hero-grid-compact {
                padding: 0 1rem;
            }

            .stat-val-mini {
                font-size: 1rem;
            }

            .split-info-mini {
                display: flex;
                flex-direction: column;
                gap: 1rem;
                margin: 1rem 0;
            }

            .rating-mini {
                padding: 10px;
            }

            .rating-num-mini {
                font-size: 24px;
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
            color: rgba(255, 255, 255, 0.2);
        }

        .stars-fill {
            position: absolute;
            top: 0;
            left: 0;
            overflow: hidden;
            white-space: nowrap;
            color: var(--neon-green);
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
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="wave"></div>
        <div class="wave"></div>
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
    </div>

    <!-- Glass Header -->
    <header class="glass-header">
        <div class="header-content">
            <div class="logo-glass">
                <img loading="lazy" class="logo-img-glass"
                    src="{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}" alt="{{ $store['name'] }}">
                <div class="logo-text-glass">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</div>
            </div>
            <button class="mobile-menu-toggle" onclick="document.querySelector('.nav-glass').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="nav-glass">
                <a href="#menu">Menu</a>
                <a href="#reviews">Reviews</a>
                <a href="#contact">Contact</a>
                <a href="#about">About</a>
                <a href="{{ route('store.gallery', [$store['slug']]) }}" class="btn-neon">Gallery</a>
            </nav>
        </div>
    </header>

    <!-- Compact Hero -->
    <div class="hero-compact">
        <div class="hero-grid-compact">
            <div class="hero-left-compact">
                <div class="hero-tag-compact">⚡ PREMIUM QUALITY</div>
                <h1 class="hero-title-compact">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                <p class="hero-desc-compact">{{ $store['meta_title'] }}</p>
                <div class="hero-actions-compact">
                    <a href="#menu" class="btn-primary-compact">Explore Menu</a>
                    <a href="#contact" class="btn-secondary-compact">Contact</a>
                </div>
            </div>

            <div class="hero-right-compact">
                <div class="glass-card">
                    <img loading="lazy" class="hero-img-compact"
                        src="{{ asset('storage/app/public/store/cover/') . '/' . $store['cover_photo'] }}" alt="Cover">

                    <div class="stats-mini">
                        @php $store_rating = number_format($store->average_rating, 1); @endphp
                        <div class="stat-mini">
                            <div class="stat-val-mini">{{ $store_rating }}</div>
                            <div class="stat-lbl-mini">Rating</div>
                        </div>
                        <div class="stat-mini">
                            <div class="stat-val-mini">{{ $store->rating_count }}+</div>
                            <div class="stat-lbl-mini">Reviews</div>
                        </div>
                        <div class="stat-mini">
                            <div class="stat-val-mini">{{ count($productdata) }}+</div>
                            <div class="stat-lbl-mini">Items</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="breadcrumb-glass">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" style="color: var(--neon-pink);">Home</a></li>
                <li class="breadcrumb-item active">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</li>
            </ol>
        </nav>
    </div>

    <!-- Announcement -->
    @if ($store->announcement)
        <div class="announce-mini">
            <div class="announce-wrap-mini">
                <i class="fas fa-bolt" style="font-size: 20px;"></i>
                <div style="font-size: 12px; font-weight: 700;">{{ $store->announcement_message }}</div>
            </div>
        </div>
    @endif

    <!-- Split Info -->
    <div class="cards-section">
        <div class="cards-wrapper">
            <div class="split-info-mini">
                <div class="about-mini">
                    <h2>About Us</h2>
                    @php
                        $description = $store['meta_description'];
                        $short = Str::limit($description, 300);
                    @endphp
                    <div class="about-text-mini" id="text-{{ $store['id'] }}">
                        {!! $short !!}
                        @if (strlen($description) > 300)
                            <span id="dots-{{ $store['id'] }}"></span>
                            <span id="more-{{ $store['id'] }}" class="d-none">{!! substr($description, 300) !!}</span>
                            <a class="cursor-pointer" style="color: var(--neon-pink); font-weight: 700;"
                                onclick="toggleReadMore({{ $store['id'] }})" id="btn-{{ $store['id'] }}">Read more →</a>
                        @endif
                    </div>
                </div>

                <div class="contact-mini">
                    <h3>Contact</h3>

                    <div class="contact-row-mini">
                        <div class="contact-icon-mini">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div style="font-size: 12px;">
                            <h4
                                style="font-size: 9px; text-transform: uppercase; opacity: 0.6; margin-bottom: 0.3rem; letter-spacing: 1px;">
                                Phone</h4>
                            @php
                                $phones = $data['store_config']?->webpage_phones;
                                if ($phones) {
                                    $phones = json_decode($phones, true);
                                } else {
                                    $phones = [];
                                }
                            @endphp
                            <p style="margin: 0; font-weight: 600;">
                                @if (!empty($phones))
                                    {{ implode(', ', $phones) }}
                                @else
                                    {{ $store['phone'] }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="contact-row-mini">
                        <div class="contact-icon-mini">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div style="font-size: 12px;">
                            <h4
                                style="font-size: 9px; text-transform: uppercase; opacity: 0.6; margin-bottom: 0.3rem; letter-spacing: 1px;">
                                Email</h4>
                            <p style="margin: 0; font-weight: 600;">
                                <a href="mailto:{{ $store['email'] }}"
                                    style="color: white; text-decoration: none;">{{ $data['store_config']?->webpage_email ?? $store->email }}</a>
                            </p>
                        </div>
                    </div>

                    <div class="contact-row-mini">
                        <div class="contact-icon-mini">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div style="font-size: 12px;">
                            <h4
                                style="font-size: 9px; text-transform: uppercase; opacity: 0.6; margin-bottom: 0.3rem; letter-spacing: 1px;">
                                Address</h4>
                            <p style="margin: 0; font-weight: 600;">{{ $store['address'] }}</p>
                        </div>
                    </div>

                    <div class="rating-mini">
                        @php $store_rating = number_format($store->average_rating, 1); @endphp
                        <div class="rating-num-mini">{{ $store_rating }}</div>
                        <div class="rating-stars" data-rating="{{ $store_rating }}" style="margin: 0.6rem 0;">
                            <div class="stars-base">
                                <i class="fas fa-star" style="font-size: 16px;"></i>
                                <i class="fas fa-star" style="font-size: 16px;"></i>
                                <i class="fas fa-star" style="font-size: 16px;"></i>
                                <i class="fas fa-star" style="font-size: 16px;"></i>
                                <i class="fas fa-star" style="font-size: 16px;"></i>
                            </div>
                            <div class="stars-fill">
                                <i class="fas fa-star" style="font-size: 16px;"></i>
                                <i class="fas fa-star" style="font-size: 16px;"></i>
                                <i class="fas fa-star" style="font-size: 16px;"></i>
                                <i class="fas fa-star" style="font-size: 16px;"></i>
                                <i class="fas fa-star" style="font-size: 16px;"></i>
                            </div>
                        </div>
                        <div style="font-size: 10px; opacity: 0.9;">{{ $store->rating_count }} Reviews</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Banners -->
    @if (count($data['banners']) > 0)
        <div style="max-width: 1600px; margin: 1.5rem auto; padding: 0 1.5rem; position: relative; z-index: 1;">
            <div class="owl-carousel banner-carousel">
                @foreach ($data['banners'] as $value)
                    <a href="{{ $value->default_link ?? '#' }}" onclick="trackBannerClick({{ $value->id }})">
                        <img loading="lazy" src="{{ asset('storage/app/public/banner/') . '/' . $value->image }}"
                            alt="banner" style="border-radius: 15px; width: 100%;">
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Menu Section -->
    <div class="cards-section" id="menu">
        <div class="cards-wrapper">
            <div class="section-head-mini">
                <div class="section-tag-mini">OUR MENU</div>
                <h2 class="section-title-mini">Explore Items</h2>
            </div>

            @foreach ($productdata as $key => $cat)
                <div class="category-mini">
                    <h3>{{ $cat->name }}</h3>
                    <div class="category-count-mini">{{ count($cat->items) }}</div>
                </div>

                <div class="cards-grid-mini">
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
                        <div class="pr_{{ $pro->id }} mini-card">
                            <div class="mini-img">
                                <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                    <img loading="lazy"
                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                        alt="{{ $pro->name }}">
                                </a>

                                @if ($module == 5 && $store->delivery_time)
                                    <div class="time-mini">
                                        <i class="fas fa-bolt" style="color: var(--neon-green);"></i>
                                        {{ strtoupper($store->delivery_time) }}
                                    </div>
                                @endif

                                @if ($pro->discount > 0)
                                    <div class="discount-mini">
                                        -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                    </div>
                                @endif

                                <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                    class="prHeart_{{ $pro->id }} heart-mini">
                                    <i
                                        class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                </div>
                            </div>

                            <div class="mini-body">
                                <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                    <h4 class="mini-title" title="{{ ucfirst($pro->name) }}">
                                        {{ ucfirst($pro->name) }}
                                    </h4>
                                </a>

                                @if ($module == 5)
                                    <p class="mini-variant">
                                        {{ !empty($variations) ? $variations[0]->type : '' }}
                                    </p>

                                    <div class="mini-price">
                                        <div class="price-now-mini">{{ _price($selling_price) }}</div>
                                        @if ($pro->discount > 0)
                                            <div class="price-old-mini">{{ _price($mrp) }}</div>
                                        @endif
                                    </div>

                                    <div class="cartSec_{{ $pro->id }}">
                                        @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                        @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                            <button
                                                onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                class="btn-mini btn-remove-mini">
                                                <i class="fa fa-times"></i> Remove
                                            </button>
                                        @else
                                            <button
                                                onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                class="btn-mini">
                                                <i class="fa fa-plus"></i> Add
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    @if ($pro->item_type == 'product')
                                        <div class="mini-price">
                                            <div class="price-now-mini">{{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0 || $mrp > $selling_price)
                                                <div class="price-old-mini">{{ _price($mrp) }}</div>
                                            @endif
                                        </div>
                                    @endif

                                    @if (($data['store_config']->lead_available ?? 1) == 0)
                                        <button disabled class="btn-mini" style="opacity:0.5;cursor:not-allowed;">
                                            <i class="fas fa-paper-plane"></i> Enquire
                                        </button>
                                        <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                    @elseif (auth('web')->user())
                                        <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                            class="btn-mini">
                                            <i class="fas fa-paper-plane"></i> Enquire
                                        </button>
                                    @else
                                        <button data-bs-toggle="modal" data-bs-target="#loginModal" class="btn-mini">
                                            <i class="fas fa-paper-plane"></i> Enquire
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach

            @foreach ($invItemdata as $key => $cat)
                <div class="category-mini">
                    <h3>{{ $cat->name }}</h3>
                    <div class="category-count-mini">{{ count($cat->items) }}</div>
                </div>

                <div class="cards-grid-mini">
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
                        <div class="pr_{{ $pro->id }} mini-card">
                            <div class="mini-img">
                                <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                    <img loading="lazy"
                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                        alt="{{ $pro->name }}">
                                </a>

                                @if ($module == 5 && $store->delivery_time)
                                    <div class="time-mini">
                                        <i class="fas fa-bolt" style="color: var(--neon-green);"></i>
                                        {{ strtoupper($store->delivery_time) }}
                                    </div>
                                @endif

                                @if ($pro->discount > 0)
                                    <div class="discount-mini">
                                        -{{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '%' : \App\CentralLogics\Helpers::currency_symbol() }}
                                    </div>
                                @endif

                                <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                    class="prHeart_{{ $pro->id }} heart-mini">
                                    <i
                                        class="fa fa-heart heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }}"></i>
                                </div>
                            </div>

                            <div class="mini-body">
                                <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                    <h4 class="mini-title" title="{{ ucfirst($pro->name) }}">
                                        {{ ucfirst($pro->name) }}
                                    </h4>
                                </a>

                                @if ($module == 5)
                                    <p class="mini-variant">
                                        {{ !empty($variations) ? $variations[0]->type : '' }}
                                    </p>

                                    <div class="mini-price">
                                        <div class="price-now-mini">{{ _price($selling_price) }}</div>
                                        @if ($pro->discount > 0)
                                            <div class="price-old-mini">{{ _price($mrp) }}</div>
                                        @endif
                                    </div>

                                    <div class="cartSec_{{ $pro->id }}">
                                        @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                        @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                            <button
                                                onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                class="btn-mini btn-remove-mini">
                                                <i class="fa fa-times"></i> Remove
                                            </button>
                                        @else
                                            <button
                                                onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                class="btn-mini">
                                                <i class="fa fa-plus"></i> Add
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    @if ($pro->item_type == 'product')
                                        <div class="mini-price">
                                            <div class="price-now-mini">{{ _price($selling_price) }}</div>
                                            @if ($pro->discount > 0 || $mrp > $selling_price)
                                                <div class="price-old-mini">{{ _price($mrp) }}</div>
                                            @endif
                                        </div>
                                    @endif

                                    @if (($data['store_config']->lead_available ?? 1) == 0)
                                        <button disabled class="btn-mini" style="opacity:0.5;cursor:not-allowed;">
                                            <i class="fas fa-paper-plane"></i> Enquire
                                        </button>
                                        <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                    @elseif (auth('web')->user())
                                        <button onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                            class="btn-mini">
                                            <i class="fas fa-paper-plane"></i> Enquire
                                        </button>
                                    @else
                                        <button data-bs-toggle="modal" data-bs-target="#loginModal" class="btn-mini">
                                            <i class="fas fa-paper-plane"></i> Enquire
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach

            @if (!count($productdata))
                <div style="text-align: center; padding: 4rem 0; opacity: 0.5;">
                    <i class="fas fa-box-open" style="font-size: 4rem; margin-bottom: 1.5rem;"></i>
                    <p style="font-size: 1rem; font-weight: 600;">No items available</p>
                </div>
            @endif

        </div>
    </div>

    <!-- Minimized Gallery -->
    @if (count($store->galleries))
        <div class="cards-section">
            <div class="cards-wrapper">
                <div class="section-head-mini">
                    <div class="section-tag-mini">GALLERY</div>
                    <h2 class="section-title-mini">Visual Tour</h2>
                </div>

                <div class="gallery-mini">
                    @foreach ($data['galleries'] as $key => $value)
                        <a target="_blank" href="{{ asset('storage/app/public/store/gallery') }}/{{ $value['image'] }}"
                            class="gallery-item-mini lightgallery-item">
                            <img loading="lazy"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($value['image'], asset('storage/app/public/store/gallery/') . '/' . $value['image'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/gallery/') }}"
                                alt="gallery"
                                src="{{ asset('storage/app/public/store/gallery/') . '/' . $value['image'] }}"
                                alt="Gallery {{ $key + 1 }}">
                            <div class="gallery-overlay-mini">
                                <i class="fas fa-search-plus gallery-icon-mini"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Map Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content"
                style="border-radius: 15px; border: none; background: var(--glass-bg); backdrop-filter: blur(20px);">
                <div class="modal-header"
                    style="background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple)); color: white; border-radius: 15px 15px 0 0; border: none;">
                    <h5 class="modal-title">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 0;">
                    <div id="map" style="height: 450px; border-radius: 0 0 15px 15px;"></div>
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
            const fill = el.querySelector('.stars-fill');
            if (fill) {
                fill.style.width = `${percentage}%`;
            }
        });

        // LightGallery
        if (document.querySelector('.gallery-mini')) {
            lightGallery(document.querySelector('.gallery-mini'), {
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
