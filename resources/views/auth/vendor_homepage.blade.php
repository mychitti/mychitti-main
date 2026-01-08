<!DOCTYPE html>
<?php
$log_email_succ = session()->get('log_email_succ');
?>

<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MC Vendor Hub — Vendor Hub</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/vendor/icon-set/style.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/toastr.css">
    <link href="{{ asset('public/assets/front/lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">


    <style>
        .owl-dots {
            z-index: 1;
            position: relative;
            padding: 10px;
            display: flex !important;
            justify-content: center;
            {{-- margin-top: -34px; --}}
        }

        .category-carousel .owl-dots {
            padding: 0 !important;
        }

        .owl-dot {
            width: 7px;
            height: 7px;
            background-color: #ccc !important;
            border-radius: 50%;
            box-shadow: 0px 0px 2px grey;
            margin: 0 3px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .owl-dot.active {
            background-color: white !important;
        }



        .duration_badge {
            color: black !important;
        }

        .duration_badge.active {
            background: #7c3aed !important;
            color: white !important;
        }

        .duration_badge2.active {
            background: #7c3aed !important;
            color: white !important;
        }

        .my-nav-link {
            position: relative;
            color: #000;
            text-decoration: none;
            padding-bottom: 5px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .my-nav-link::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            height: 2px;
            width: 100%;
            background-color: #81c408;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .my-nav-link.active::after {
            transform: scaleX(1);
        }

        .container {
            max-width: 1200px;
        }

        /* otp element styling  */
        .otp-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 300px;
        }

        .otp-container h2 {
            margin-bottom: 20px;
        }

        .otp-container p {
            margin-bottom: 20px;
            color: #666;
        }

        .otp-form {
            display: flex;
            justify-content: space-between;
        }

        .otp-input {
            width: 55px;
            height: 55px;
            margin: 3px;
            text-align: center;
            font-size: 26px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .otp-input:focus {
            border-color: #007bff;
            outline: none;
        }
    </style>
    <style>
        :root {
            --login-bg: #f7f9ff;
            --login-accent: #6b7cff;
            --login-accent-2: #ff6b6b;
            --login-card: #ffffff;
            --login-muted: #8b93a7;
            --login-border: #e6e9f2;
        }

        /* Scope all styles under .login-page */
        .login-page * {
            box-sizing: border-box;
        }

        .login-page {
            font-family: Inter, system-ui, Arial, sans-serif;
            background: linear-gradient(180deg, var(--login-bg), #ffffff);
            min-height: 95vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }

        .login-page .login-container {
            width: 1100px;
            max-width: 1100px;
            background: transparent;
            display: flex;
            gap: 28px;
            align-items: center;
        }

        .login-page .login-card {
            background: var(--login-card);
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(40, 47, 80, 0.06);
            {{-- padding: 34px; --}} flex: 1;
        }

        .login-page .login-left {
            flex: 0.8;
            padding: 10px 28px;
        }

        .login-page .login-right {
            flex: 0.9;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .login-page .login-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .login-page .login-title h2 {
            margin: 0;
            font-size: 18px;
        }

        .login-page .lock-box {
            width: 56px;
            height: 56px;
            background: linear-gradient(180deg, #fff 0%, #f3f5ff 100%);
            border-radius: 12px;
            border: 2px solid var(--login-border);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-page .login-form {
            margin-top: 18px;
        }

        .login-page .login-form label {
            display: block;
            font-size: 13px;
            color: var(--login-muted);
            margin: 10px 0 6px;
        }

        .login-page .login-input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 24px;
            border: 1px solid var(--login-border);
            background: #fbfbff;
            font-size: 15px;
            outline: none;
        }

        .login-page .login-input:focus {
            box-shadow: 0 4px 18px rgba(107, 124, 255, 0.12);
            border-color: var(--login-accent);
        }

        .login-page .forgot {
            display: block;
            margin-top: 8px;
            text-decoration: none;
            font-size: 13px;
            color: var(--login-muted);
            cursor: pointer;
        }

        .login-page .btn {
            display: inline-block;
            padding: 10px 22px;
            border-radius: 20px;
            border: none;
            font-weight: 600;
            cursor: pointer;
        }

        .login-page .login-btn {
            display: inline-block;
            margin-top: 1px;
            width: 100%;
            background: linear-gradient(90deg, #5f97ffff, #a6edffff);
            color: white;
        }

        .login-page .signup-btn {
            display: inline-block;
            margin-top: 14px;
            width: 100%;
            background: linear-gradient(90deg, #ff5f6d, #ffc371);
            color: white;
        }

        .login-page .or-text {
            margin: 12px 0;

            text-align: center;
            color: var(--login-muted);
            font-size: 13px;
        }

        .login-page .google-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: 18px;
            border: 1px solid var(--login-border);
            background: white;
            width: 100%;
            justify-content: center;
            margin-top: 1px;
        }

        .login-page .image-card {
            width: 100%;
            height: 360px;
            border-radius: 10px;
            border: 3px solid #a28cff22;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, #fff, #faf8ff);
        }

        .login-page .img-100 {
            width: 100%;
        }

        .img-80 {
            width: 80% !important;
        }

        @media (max-width: 880px) {
            .login-page .login-container {
                flex-direction: column;
            }

            .login-page .login-left,
            .login-page .login-right {
                flex: unset;
            }

            .login-page .login-card {
                padding: 22px;
            }

            .login-page .image-card {
                height: 240px;
            }
        }

        .section_content {
            align-items: center;
        }

        .section_img img {
            width: 100%;
        }

        .section_heading {
            margin: 28px auto;
            {{-- text-align: center; --}}
        }

        .hero-wrapper {
            padding: 40px 0;
        }

        .main-illustration {
            background: linear-gradient(135deg, #6b46c1 0%, #9333ea 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
        }

        .browser-window {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .browser-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .browser-dots {
            display: flex;
            gap: 5px;
            margin-right: 15px;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .dot.red {
            background: #ff5f57;
        }

        .dot.yellow {
            background: #ffbd2e;
        }

        .dot.green {
            background: #28ca42;
        }

        .url-bar {
            background: #f1f5f9;
            padding: 8px 15px;
            border-radius: 20px;
            flex: 1;
            font-size: 14px;
        }

        .shop-interface {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            min-height: 200px;
        }

        .shop-left {
            background: linear-gradient(45deg, #8b5cf6, #a855f7);
            border-radius: 10px;
            padding: 20px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .shop-right {
            display: grid;
            grid-template-rows: 1fr 1fr;
            gap: 10px;
        }

        .product-card {
            background: #e0e7ff;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        img {
            max-width: 800px;

        }

        .floating-icons {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .floating-icon {
            position: absolute;
            font-size: 24px;
            opacity: 0.3;
        }

        .person-illustration {
            position: absolute;
            right: 30px;
            bottom: 30px;
            width: 80px;
            height: 100px;
            background: linear-gradient(45deg, #fbbf24, #f59e0b);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bottom-icons {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }

        .bottom-icon {
            background: #fff;
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .hero-content h1 {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #1f2937;
        }

        .rocket-icon {
            color: #f59e0b;
            font-size: 28px;
            margin-right: 10px;
        }

        .hero-text {
            font-size: 16px;
            margin-bottom: 15px;
            color: #4b5563;
        }

        .feature-list {
            list-style: none;
            padding: 0;
        }

        .feature-list li {
            padding: 8px 0;
            position: relative;
            padding-left: 30px;
        }

        .feature-list li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
            font-size: 18px;
        }

        .highlight-text {
            background: black;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
        }

        /* Privacy Section - Image 2 */
        .privacy-section {
            padding: 40px 0;
        }

        .innovation-section {
            padding: 40px 0;
        }

        .privacy-content {
            background: #fff;
        }

        .lock-illustration {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, #4c1d95 0%, #7c3aed 50%, #a855f7 100%);
            border-radius: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin: 0 auto;
        }

        .lock-icon {
            font-size: 120px;
            color: #fff;
        }

        .fingerprint-overlay {
            position: absolute;
            font-size: 80px;
            color: rgba(255, 255, 255, 0.8);
        }

        .privacy-content h2 {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 30px;
            color: #1f2937;
        }

        .privacy-text {
            font-size: 16px;
            color: #4b5563;
            margin-bottom: 0px;
        }

        /* Support Section - Image 2 Bottom */
        .support-section {
            background: #fff;
            padding: 40px 0;
        }

        .support-content h2 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 30px;
            color: #1f2937;
        }

        .support-illustration {
            width: 400px;
            height: 300px;
            background: linear-gradient(45deg, #f3f4f6, #e5e7eb);
            border-radius: 20px;
            position: relative;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .person-support {
            width: 150px;
            height: 200px;
            background: linear-gradient(45deg, #fbbf24, #f59e0b);
            border-radius: 15px;
            position: relative;
        }

        .chat-bubbles {
            position: absolute;
            right: -50px;
            top: 50px;
        }

        .chat-bubble {
            background: #3b82f6;
            color: white;
            padding: 10px 15px;
            border-radius: 15px;
            margin: 10px 0;
            font-size: 14px;
        }

        /* Innovation Section - Image 3 */

        .innovation-card {}

        .innovation-text {
            background: linear-gradient(45deg, #fbbf24, #f59e0b);
            color: #1f2937;
            padding: 20px;
            border-radius: 10px;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }

        .innovation-people {
            position: absolute;
            bottom: -20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }

        .person-mini {
            width: 40px;
            height: 50px;
            background: linear-gradient(45deg, #fbbf24, #f59e0b);
            border-radius: 8px;
        }

        /* Capabilities Section - Image 4 */
        .capabilities-section {
            background: #fff;
            padding: 40px 0;
        }

        .capability-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin: 40px 0;
        }

        .capability-item {
            text-align: center;
        }

        .capability-image {
            width: 100%;
            height: 239px;
            border: 1px solid #e7e7e7;

            border-radius: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            font-weight: bold;
            overflow: hidden;
        }



        .capability-title {
            font-size: 22px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
            text-decoration: none;
        }

        .capability-subtitle {
            font-size: 18px;
            color: #6b7280;
            {{-- font-weight: bold; --}}
        }

        .bottom-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 40px;
        }

        .bottom-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
        }

        /* Pricing Section - Image 5 */
        .pricing-section {
            padding: 40px 0;
        }

        .pricing-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .pricing-header h2 {
            font-size: 32px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }

        .pricing-card {
            background: #fff;
            padding: 30px 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .plan-header {
            margin-bottom: 20px;
        }

        .plan-name {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .plan-price {
            font-size: 32px;
            font-weight: bold;
            color: #7c3aed;
            margin-bottom: 5px;
        }

        .plan-period {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 20px;
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin: 20px 0;
            text-align: left;
        }

        .plan-features li {
            padding: 8px 0;
            position: relative;
            padding-left: 25px;
            font-size: 14px;
            color: #4b5563;
        }

        .plan-features li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }

        .plan-target {
            font-size: 12px;
            color: #6b7280;
            font-style: italic;
            margin: 20px 0;
        }

        .addons-section {
            margin-top: 60px;
        }

        .addons-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .addon-card {
            padding: 30px;
            border-radius: 15px;
        }

        .addon-title {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .pricing-grid {
                grid-template-columns: 1fr;
            }

            .capability-grid {
                grid-template-columns: 1fr;
            }

            .addons-grid {
                grid-template-columns: 1fr;
            }

            .hero-content h1 {
                font-size: 28px;
            }
        }

        /* SCROLLING TITLES */
        .rotating-title-wrapper {
            position: relative;
            height: 120px;
            width: 100%;
            max-width: 800px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .animated-title-text {
            position: absolute;
            font-size: 2rem;
            font-weight: bold;
            {{-- color: #fff; --}} {{-- text-align: center; --}} opacity: 0;
            transform: translateY(50px);
            transition: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            {{-- text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.3); --}} width: 100%;
        }

        .animated-title-text.title-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .animated-title-text.title-exiting {
            opacity: 0;
            transform: translateY(-50px);
        }

        /* Alternative slide animation - uncomment to use */
        /*
        .animated-title-text {
            transform: translateX(100%);
        }

        .animated-title-text.title-visible {
            transform: translateX(0);
        }

        .animated-title-text.title-exiting {
            transform: translateX(-100%);
        }
        */

        /* Progress indicator */
        .title-rotator-indicators {
            position: absolute;
            bottom: -50px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
        }

        .title-indicator-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .title-indicator-dot.indicator-active {
            background: white;
            transform: scale(1.2);
        }

        @media (max-width: 768px) {
            .animated-title-text {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 480px) {
            .animated-title-text {
                font-size: 2rem;
            }
        }
    </style>


    {{-- from page 8  --}}
    <style>
        .kx7mc-testimonial-section {
            background: #f5faff;
            padding: 30px;
            margin: 20px 0;
        }

        .qw9nx-testimonial-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .qw9nx-testimonial-item i {
            color: #28a745;
            margin-right: 10px;
            margin-top: 2px;
        }

        .zr4mp-faq-hero {
            text-align: center;
            color: white;
        }

        .hd8kl-faq-icon {
            background: white;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .mw5ty-faq-content {
            padding: 40px 0;
        }

        .pl9rx-faq-item {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }

        .pl9rx-faq-item:last-child {
            border-bottom: none;
        }

        .jf2nd-feature-list {
            list-style: none;
            padding: 0;
        }

        .jf2nd-feature-list li {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 8px 0;
        }

        .bt6kp-icon {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 14px;
            flex-shrink: 0;
        }

        .nq8vz-checkmark {
            color: #28a745;
            margin-right: 10px;
            font-size: 16px;
        }

        .cy3lx-pricing-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .rh9px-pricing-list {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }

        .rh9px-pricing-list li {
            padding: 3px 0;
        }

        .vs4mc-apps-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            margin: 30px 0;
        }

        .kd7nq-app-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin: 20px 0;
        }

        .mx8wl-app-icon {
            width: 60px;
            height: 60px;
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .mx8wl-app-icon.active {
            border-color: #007bff;
            background: #e3f2fd;
        }

        .tp5bk-app-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }

        .ql7nx-pricing-table {
            font-size: 12px;
            margin: 20px 0;
        }

        .ql7nx-pricing-table .row {
            margin-bottom: 5px;
            padding: 2px 0;
        }

        .wm9kx-savings-highlight {
            background: linear-gradient(45deg, #ffd54f, #ffb74d);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }

        .fd8pk-user-counter {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .fd8pk-counter-btn {
            background: #fff;
            border: 1px solid #ccc;
            width: 30px;
            height: 30px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .fd8pk-counter-input {
            width: 50px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 5px;
        }
    </style>
</head>

<body>
    @include('front-views.partials.mc_nav')

    <div class="container" style="max-width: 1200px;">
        <div class="page_section">
            <div class="section_content row">
                <div class="section_img image-card col-md-6">
                    <img class="img-80" src="{{ asset('storage/app/public/vendor_login/empower_your_business.png') }}"
                        alt="Login">
                </div>
                <div class="content_inner col-md-6">
                    <div class="rotating-title-wrapper">
                        <div class="animated-title-text title-visible">
                            {{ $lines->where('key', 'mc_first_line')->first()->value ?? '' }}</div>
                        <div class="animated-title-text">
                            {{ $lines->where('key', 'mc_second_line')->first()->value ?? '' }}</div>
                        <div class="animated-title-text">
                            {{ $lines->where('key', 'mc_third_line')->first()->value ?? '' }}</div>

                    </div>

                    <p>Start with MC Vendor Hub and fulfill your life’s dream – your all-in-one software tool for daily
                        business needs. Everything begins here. MC Vendor Hub connects your goals to real solutions with
                        our
                        powerful suite of business
                        tools.
                    </p>
                    <p class="text-end">
                        Just <b>List</b> Your Business <b style="font-size:20px ;">Free</b> </p>
                </div>
            </div>
        </div>
    </div>


    <!-- Hero Section - Exact replica of Image 1 -->
    <section class="hero-wrapper">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="w-100 d-flex justify-content-center mb-3">
                        <img style="width:100%; "
                            src="{{ asset('storage/app/public/vendor_login/take_your_business_digital.png') }}"
                            alt="Login">
                    </div>
                </div>
                <div class="col-12">
                    <div class="hero-content">
                        <h2>
                            🚀 Take Your Business Digital with MC Vendor Hub
                        </h2>
                        <p class="hero-text"><strong>Empower your business.</strong></p>
                        <p class="hero-text">
                            MC Vendor Hub is your complete business companion—built to support India's <strong>Micro,
                                Small,
                                and
                                Medium Enterprises (MSMEs)</strong>. Whether you're a manufacturer, shop owner, service
                            provider, or local vendor, <strong>MC Vendor Hub</strong> helps you streamline operations
                            and
                            grow with ease.
                        </p>
                        <p class="hero-text">
                            And the best part? We give you the tools you need to succeed—<strong>without the high
                                cost.</strong>
                        </p>
                        <ul class="feature-list">
                            <li>Free basic functionality</li>
                            <li>Premium at a very nominal price</li>
                            <li>Dedicated business webpage</li>
                            <li>Tools for crm, billing, inventory, leads, vendor app, staff app, and more</li>
                        </ul>
                        <p class="hero-text">👉
                            <span class="highlight-text"> List your business today and start your digital journey
                                with MC Vendor Hub.</span><br>
                            <span class="highlight-text">Your business, your growth—powered digitally.</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Privacy Section - Exact replica of Image 2 top -->
    <section class="privacy-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="privacy-content">
                        <h2>Your Privacy is Our Priority</h2>
                        <p>At MC Vendor Hub, trust is the foundation of everything we do.
                            We do not own or sell your data, and we never share it with third parties without your
                            consent.
                            Your information is secure with us. We use it only to deliver and improve our services, and
                            never for unauthorized sharing or resale.
                            Your data stays your data.
                            Your privacy is our commitment.
                            Your trust is our responsibility.
                            For full details, please see our <a
                                href="https:/vendor.mcvendorhub.com/mc-vendor-hub-privacy-policy">https:/vendor.mcvendorhub.com/mc-vendor-hub-privacy-policy</a>
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img style="width:100%;" src="{{ asset('storage/app/public/vendor_login/privacy.png') }}"
                        alt="Login">
                </div>
            </div>
        </div>
    </section>

    <!-- Support Section - Exact replica of Image 2 bottom -->
    <section class="support-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2>Customer Support You Can Rely On</h2>
                    <p class="hero-text">
                        At <strong>MC Vendor Hub</strong>, we believe that great software should always come with great
                        support. That's
                        why we provide dedicated assistance to help your business run smoothly.
                    </p>
                    <div>
                        <a href="tel:07022806288" class="text-decoration-none text-dark"><i class="fa fa-phone"></i>
                            07022806288</a> <br>
                        <a href="mailto:mychitti@mychitti.net" class="text-decoration-none text-dark"><i
                                class="fa fa-envelope"></i>
                            mychitti@mychitti.net</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img style="width:100%;" src="{{ asset('storage/app/public/vendor_login/customer_support.png') }}"
                        alt="Login">
                </div>
            </div>
        </div>
    </section>

    <!-- Innovation Section - Exact replica of Image 3 -->
    <section class="innovation-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="innovation-card">
                        <h2>🚀 Innovation & Regular Updates</h2>
                        <p class="hero-text">
                            At <strong>MC Vendor Hub</strong>, we believe innovation never stops. That's why our team
                            continuously
                            researches, develops, and enhances our solutions to keep your business ahead of the curve.
                        </p>

                        <h4 style="margin: 30px 0 20px 0; color: #1f2937;">✨ What You Get:</h4>
                        <ul class="feature-list">
                            <li><strong>📋 Regular Updates</strong> – New features, improvements, and security upgrades.
                            </li>
                            <li><strong>⚡ Enhanced Performance</strong> – Faster, smoother, and more reliable
                                operations.</li>
                            <li><strong>🎯 User-Driven Development</strong> – We listen to vendor feedback to shape new
                                features.</li>
                            <li><strong>📊 Future-Ready Tools</strong> – Smart analytics and integrations to match
                                evolving business needs.</li>
                            <li><strong>💪 With every update, you get more power, more efficiency, and more
                                    control</strong>—without extra cost.</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img style="width:100%;" src="{{ asset('storage/app/public/vendor_login/innovation.png') }}"
                        alt="Login">
                </div>
            </div>
        </div>
    </section>

    <!-- Capabilities Section - Exact replica of Image 4 -->
    <section class="capabilities-section" id="products_section">
        <div class="container">
            <h2 style="text-align: center; font-size: 32px; font-weight: bold; color: #1f2937; margin-bottom: 50px;">
                Redefine what's possible with MC Vendor Hub
            </h2>

            <div class="capability-grid">
                @foreach ($vendor_modules as $key => $value)
                    <div class="capability-item">
                        <a href="{{ route('mc-module', [$value->slug]) }}" class="capability-image lead-gen">
                            <img style="width:100%;"
                                src="{{ asset('storage/app/public/vendor_login/') . '/' . $value->image }}"
                                alt="{{ $value->name }}">
                        </a>
                        <a href="{{ route('mc-module', [$value->slug]) }}"
                            class="capability-title">{{ $value->name }}</a>
                    </div>
                @endforeach

            </div>


        </div>
    </section>
    @php $plans =  _vendorSubscriptionPlans(); @endphp

    <!-- Pricing Section - Exact replica of Image 5 -->
    <section class="pricing-section" id="pricing_section">
        <div class="container">
            <div class="pricing-header">
                <h3 style="color: #6b7280; font-size: 24px; margin-bottom: 15px;">Find the plan that's right for you
                </h3>
                <h1 style="font-weight: bolder;">MC Vendor Hub Pricing Plans</h1>
                <p style="color: #6b7280; font-size: 16px;">Choose the plan that fits your business growth journey.</p>
            </div>

            <div class="pricing-grid">
                @foreach ($plans as $key => $value)
                    <div class="pricing-card">
                        <div class="plan-header">

                            <div class="plan-name">{{ $value?->title }}</div>
                            @php
                                $variations = json_decode($value->price_variations);
                            @endphp

                            @if ($variations && is_array($variations))
                                <div class="plan-price">
                                    <span class="plan_price_{{ $value->id }}">
                                        {{ $value->title == 'Starter Plan' ? 'Free' : ($variations && isset($variations[0]->price) ? _price($variations[0]->price) : '') }}
                                    </span>
                                </div>
                                @foreach ($variations as $key2 => $value2)
                                    <span data-plan="{{ $value->id }}" data-price="{{ _price($value2->price) }}"
                                        class="badge badge-dark border duration_badge duration_set_{{ $value->id }} {{ $key2 === 0 ? 'active' : '' }}">
                                        {{ $value2->duration }}
                                    </span>
                                @endforeach
                            @else
                                <div class="plan-price">
                                    <span
                                        class="plan_price_{{ $value->id }}">{{ $value->title == 'Starter Plan' ? 'Free' : $value->price }}
                                    </span>
                                </div>
                                <span class="badge badge-dark border duration_badge2 active">
                                    {{ $value->duration_count . ' ' . $value->duration_type }}
                                </span>
                            @endif


                        </div>
                        @if ($value?->title == 'Starter Plan')
                            <ul class="plan-features">
                                <li>50 OCR scans/month</li>
                                <li>100 Inventory Items</li>
                                <li>5 employees</li>
                                <li>Basic Reports</li>
                                <li>Basic CRM (Contacts)</li>
                            </ul>
                            <div class="plan-target">New business</div>
                        @elseif($value?->title == 'Basic Plan')
                            <ul class="plan-features">
                                <li>HRM + Inventory + Accounting</li>
                                <li>500 OCR scans/month</li>
                                <li>1000 inventory items </li>
                                <li>20 employees </li>
                                <li>Email support </li>
                                <li>Dedicated Business Webpage</li>
                                <li>Customer Enquiries Box</li>
                            </ul>
                            <div class="plan-target">Local shops & service providers</div>
                        @elseif($value?->title == 'Pro Plan')
                            <ul class="plan-features">
                                <li>All Basic features</li>
                                <li>Advanced reports</li>
                                <li>Bulk SMS/ WhatsApp tools</li>
                                <li>2000 OCR scans/month</li>
                                <li>Unlimited Inventory items</li>
                                <li>100 employees</li>
                                <li>Full CRM (Leads + Enquiries + Follow-ups)</li>
                            </ul>
                            <div class="plan-target">Growing SMBs & agencies</div>
                        @endif
                    </div>
                @endforeach
                {{-- <div class="pricing-card">
                    <div class="plan-header">
                        <div class="plan-name">Basic</div>
                        <div class="plan-price">₹999</div>
                        <div class="plan-period">per month</div>

                    </div>
                    <ul class="plan-features">
                        <li>HRM + Inventory + Accounting</li>
                        <li>500 OCR scans/month</li>
                        <li>1000 Inventory Items</li>
                        <li>20 employees</li>
                        <li>Email Support</li>
                        <li>Dedicated Business Webpage</li>
                        <li>Customer Enquiries Box</li>
                    </ul>
                    <div class="plan-target">Local shops & Service providers</div>
                </div>
                <div class="pricing-card">
                    <div class="plan-header">
                        <div class="plan-name">Pro</div>
                        <div class="plan-price">₹1,999</div>
                        <div class="plan-period">per month</div>

                    </div>
                    <ul class="plan-features">
                        <li>All Basic features</li>
                        <li>Advanced reports</li>
                        <li>Bulk SMS/ WhatsApp tools</li>
                        <li>2000 OCR scans/month</li>
                        <li>Unlimited Inventory items</li>
                        <li>100 employees</li>
                        <li>Full CRM (Leads + Enquiries + Follow-ups)</li>
                    </ul>
                    <div class="plan-target">Growing SMBs & agencies</div>
                </div> --}}
                <div class="pricing-card">
                    <div class="plan-header">
                        <div class="plan-name">Enterprise</div>
                        <div class="plan-price">Custom Pricing</div>
                    </div>
                    <ul class="plan-features">
                        <li>All Pro features</li>
                        <li>Unlimited usage</li>
                        <li>API integrations</li>
                        <li>Dedicated Account Manager</li>
                        <li>Priority support</li>
                        <li>Multi-branch CRM & Equity Support</li>
                        <li>Custom workflows</li>
                    </ul>
                    <div class="plan-target">Large businesses & enterprises</div>
                </div>
            </div>

            <!-- Add-ons Section -->
            {{-- <div class="addons-section">
                <div class="addons-grid">
                    <div class="addon-card card p-3">
                        <h3 class="addon-title">🔄 Pay-as-you-go Add-ons</h3>
                        <ul class="feature-list">
                            <li><strong>OCR Invoices</strong><br>₹0.50 or scan</li>
                            <li><strong>Bulk SMS/WhatsApp</strong><br>₹0.20 or message</li>
                            <li><strong>AI Assistant (Chatbot/Reports)</strong><br>per usage charges</li>
                        </ul>
                    </div>
                    <div class="addon-card card p-3">
                        <h3 class="addon-title">💳 Prepaid Credit Packs</h3>
                        <ul class="feature-list">
                            <li><strong>₹500 pack</strong><br>1,000 credits</li>
                            <li><strong>₹1,000 pack</strong><br>2,500 credits</li>
                        </ul>
                        <p style="font-size: 14px; color: #6b7280; margin-top: 20px; font-style: italic;">
                            (Credits usable across OCR, SMS, AI etc.)
                        </p>
                    </div>
                </div>
            </div> --}}

            <div class="card" style="    background: #7c3aed2e;padding: 16px;font-size: 18px;">
                Pay-As-You-Go Add-Ons Are Now Conveniently Available for You</div>
        </div>
    </section>
    <div class="container my-2" id="review_section">
        <h1 class="text-center">
            See how customers accomplish more with MC Vendor Hub .</h1>
        <p>Having the ability to create, collaborate, and communicate in a very seamless way makes our family’s day to
            day more efficient.
        </p>
    </div>
    <div class="d-flex justify-content-center container">
        {{-- <img style="width:100%;" src="{{ asset('storage/app/public/vendor_login/customer_accomplishment.png') }}"
            alt="customers accomplishment"> --}}
        <div class="owl-carousel reviews-carousel ">
            @for ($i = 1; $i <= 6; $i++)
                <img class="" src="{{ asset('storage/app/public/vendor_login/reviews') . '/' . $i . '.png' }}"
                    alt="">
            @endfor
        </div>
    </div>
    {{-- <div class="container p-0">

        <div class="kx7mc-testimonial-section">
            <div class="container">
                <div class="qw9nx-testimonial-item">
                    <i class="fas fa-check-circle"></i>
                    <span>"With MC Vendor Hub, I can manage orders, staff, and payments all in one place — no more juggling
                        multiple apps."</span>
                </div>

                <div class="qw9nx-testimonial-item">
                    <i class="fas fa-check-circle"></i>
                    <span>"The dedicated business webpage helped me attract new customers online without extra marketing
                        costs."</span>
                </div>

                <div class="qw9nx-testimonial-item">
                    <i class="fas fa-check-circle"></i>
                    <span>"Inventory tracking is now automatic. I always know what's in stock and when to
                        reorder."</span>
                </div>

                <div class="qw9nx-testimonial-item">
                    <i class="fas fa-check-circle"></i>
                    <span>"I save at least 2 hours daily since billing and receipts are generated instantly."</span>
                </div>

                <div class="qw9nx-testimonial-item">
                    <i class="fas fa-check-circle"></i>
                    <span>"Managing my service team became easy with task scheduling — fewer delays, happier
                        customers."</span>
                </div>

                <div class="qw9nx-testimonial-item">
                    <i class="fas fa-check-circle"></i>
                    <span>"Customer engagement tools helped me build loyalty and repeat business."</span>
                </div>

                <div class="qw9nx-testimonial-item">
                    <i class="fas fa-check-circle"></i>
                    <span>"For the first time, my small shop feels like it runs on professional software."</span>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- FAQ Hero Section -->
    <section class="zr4mp-faq-hero container p-0
    ">
        <div class="">
            <img style="width:100%;max-width: 100% !important; "
                src="{{ asset('storage/app/public/vendor_login/faq_banner.png') }}" alt="Login">
            {{-- <div class="row g-0 px-5">
                <div class="col-md-6">
                    <img style="width:100%;"
                        src="{{ asset('storage/app/public/vendor_login/faq_1-removebg-preview.png') }}"
                        alt="Login">
                </div>
                <div class="col-md-6">
                    <img style="width:100%;" src="{{ asset('storage/app/public/vendor_login/faq.webp') }}"
                        alt="Login">
                </div>
            </div> --}}
        </div>
    </section>

    <!-- FAQ Content -->
    <div class="container mw5ty-faq-content" id="faq_section">
        <h2 style="margin-bottom: 30px;">Quick FAQ</h2>


        <div style=" margin-bottom: 40px;">
            <h5><strong>1. What is MC Vendor Hub ?</strong></h5>

            <p>MC Vendor Hub is an <strong>all-in-one business management platform</strong> designed for MSMEs. It
                offers
                tools like a dedicated webpage, billing & POS, accounts, inventory, HRM, project management, client
                management, task management, and lead management — everything your business needs in one place.</p>
        </div>

        <div class="pl9rx-faq-item">
            <h5><strong>2. What tools are included in MC Vendor Hub ?</strong></h5>
            <p>With one subscription, you get access to:</p>

            <ul class="jf2nd-feature-list">
                <li>
                    <div class="bt6kp-icon" style="background: #007bff;">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div>
                        <strong>Dedicated Business Webpage</strong> – Showcase your products & services online.
                    </div>
                </li>
                <li>
                    <div class="bt6kp-icon" style="background: #6f42c1;">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div>
                        <strong>Task & Project Management</strong> – Assign, track, and manage workflows.
                    </div>
                </li>
                <li>
                    <div class="bt6kp-icon" style="background: #ffc107;">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div>
                        <strong>Accounts & Billing</strong> – Simplify finances, invoices, and payments.
                    </div>
                </li>
                <li>
                    <div class="bt6kp-icon" style="background: #8B4513;">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div>
                        <strong>Inventory Management</strong> – Track stock in real-time.
                    </div>
                </li>
                <li>
                    <div class="bt6kp-icon" style="background: #fd7e14;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <strong>HRM</strong> – Manage staff, attendance, and payroll.
                    </div>
                </li>
                <li>
                    <div class="bt6kp-icon" style="background: #6f42c1;">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <div>
                        <strong>POS System</strong> – Fast billing with multiple payment options.
                    </div>
                </li>
                <li>
                    <div class="bt6kp-icon" style="background: #e83e8c;">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <strong>Client Management (CRM)</strong> – Build and track customer relationships.
                    </div>
                </li>
                <li>
                    <div class="bt6kp-icon" style="background: #20c997;">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div>
                        <strong>Lead Management</strong> – Receive, organize, and follow up on leads.
                    </div>
                </li>
                <li>
                    <div class="bt6kp-icon" style="background: #8B4513;">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div>
                        <strong>Paid Enquiries (Leads)</strong> – Get customer enquiries directly through the MC Vendor
                        Hub
                        platform to grow your sales.
                    </div>
                </li>
            </ul>
        </div>

        <div class="pl9rx-faq-item">
            <h5><strong>3. Can I get customer leads directly from MC Vendor Hub ?</strong></h5>
            <p><i class="nq8vz-checkmark fas fa-check-circle"></i> <strong>Yes!</strong> MC Vendor Hub provides
                <strong>paid enquiries (leads)</strong> to help vendors and service providers connect with genuine
                customers looking for their products or services. This helps you expand your customer base and increase
                sales opportunities.
            </p>
        </div>

        <div class="pl9rx-faq-item">
            <h5><strong>4. Is MC Vendor Hub cloud-based?</strong></h5>
            <p>Yes, MC Vendor Hub is <strong>100% cloud-based</strong>, accessible from anywhere on mobile, tablet, or
                computer.</p>
        </div>

        <div class="pl9rx-faq-item">
            <h5><strong>5. How secure is my business data?</strong></h5>
            <p>We use <strong>encrypted cloud servers</strong> with advanced security to ensure your business data is
                always safe and accessible only by authorized users.</p>
        </div>

        <div class="pl9rx-faq-item">
            <h5><strong>6. Can I manage multiple stores or branches?</strong></h5>
            <p><i class="nq8vz-checkmark fas fa-check-circle"></i> <strong>Yes,</strong> MC Vendor Hub supports
                <strong>multi-store management</strong>, including branch comparison and consolidated reporting.
            </p>
        </div>

        <div class="pl9rx-faq-item">
            <h5><strong>7. Does MC Vendor Hub support multiple users?</strong></h5>
            <p>Yes. The standard plan includes <strong>1 Admin + 2 Users</strong>, with options to add more users as
                your business grows.</p>
        </div>

        <div class="pl9rx-faq-item">
            <h5><strong>8. Can I generate and share reports?</strong></h5>
            <p>Yes, you can export all reports (sales, accounts, HR, leads, etc.) to PDF or Excel for easy
                record-keeping.</p>
        </div>

        <div class="pl9rx-faq-item">
            <h5><strong>9. Who can use MC Vendor Hub ?</strong></h5>
            <p>It's designed for a wide range of businesses, including:</p>
            <ul>
                <li><strong>Retail & Shops</strong> (kirana, clothing, mobile, stationery, etc.)</li>
                <li><strong>Food & Beverage</strong> (restaurants, cafes, bakeries, parlors)</li>
                <li><strong>Services</strong> (salons, pharmacies, hardware, repair centers)</li>
                <li><strong>Wholesalers, Distributors & Agencies</strong></li>
                <li><strong>Startups & Growing Enterprises</strong></li>
            </ul>
        </div>

        <div class="pl9rx-faq-item">
            <h5><strong>10. Do you provide training and support?</strong></h5>
            <p><i class="nq8vz-checkmark fas fa-check-circle"></i> <strong>Yes!</strong> Free onboarding, staff
                training, and <strong>24/7 chat & email support</strong> are included, with phone support during working
                hours.</p>
        </div>

        <div class="pl9rx-faq-item">
            <h5><strong>11. Will I get updates and new features?</strong></h5>
            <p>Yes. We provide <strong>continuous updates</strong> with new tools, performance improvements, and
                features based on vendor feedback.</p>
        </div>

        <div class="pl9rx-faq-item">
            <h5><strong>12. How do I get started?</strong></h5>
            <p>Simply sign up, add your business details, and your digital tools + <strong>dedicated webpage + lead
                    access</strong> will be ready in minutes.</p>
        </div>
    </div>

    <!-- App Comparison Section -->
    <div class="container p-0">
        <h3 style="margin-bottom: 20px;"><i class="fas fa-star" style="color: #ffc107;"></i> <strong>MC Vendor Hub is
                your complete growth partner — manage, automate, and grow your business while
                getting real customer leads.</strong></h3>
    </div>
    {{-- footer section  --}}
    @include('front-views.partials.mc_footer')

    <!-- JS Implementing Plugins -->
    <script src="{{ asset('public/assets/admin') }}/js/vendor.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
    <script src="{{ asset('public/assets/front/lib/owlcarousel/owl.carousel.min.js') }}"></script>

    <!-- JS Front -->
    <script src="{{ asset('public/assets/admin') }}/js/theme.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/toastr.js"></script>
    {!! Toastr::message() !!}

    @if ($errors->any())
        <script>
            "use strict";
            @foreach ($errors->all() as $error)
                toastr.error('{{ translate($error) }}', Error, {
                    CloseButton: true,
                    ProgressBar: true
                });
            @endforeach
        </script>
    @endif
    @if ($log_email_succ)
        @php(session()->forget('log_email_succ'))
        <script>
            "use strict";
            $('#successMailModal').modal('show');
        </script>
    @endif

    <script>
        "use strict";
        // $("#forget-password").hide();
        $("#role-select").change(function() {
            var selectValue = $(this).val();
            if (selectValue == "admin") {
                $("#forget-password").show();
                $("#forget-password1").hide();
            } else if (selectValue == "vendor") {
                $("#forget-password").hide();
                $("#forget-password1").show();
            } else {
                $("#forget-password").hide();
                $("#forget-password1").hide();
            }
        });

        $(document).on('ready', function() {
            // INITIALIZATION OF SHOW PASSWORD
            // =======================================================
            $('.js-toggle-password').each(function() {
                new HSTogglePassword(this).init()
            });

            // INITIALIZATION OF FORM VALIDATION
            // =======================================================
            $('.js-validate').each(function() {
                $.HSCore.components.HSValidation.init($(this));
            });
        });



        $(document).on('input', '.otp-input', function(e) {
            const $inputs = $('.otp-input');
            const index = $inputs.index(this);

            if (this.value.length === this.maxLength && index < $inputs.length - 1) {
                $inputs.eq(index + 1).focus();
            }
        });


        $(document).ready(function() {
            $('.onerror-image').on('error', function() {
                let img = $(this).data('onerror-image')
                $(this).attr('src', img);
            });
        });
    </script>


    <script>
        $('.send_login_otp').on('submit', function(e) {
            e.preventDefault();
            $('#send_otp_btn').attr('disabled', true)
            var phone = $('#signinSrMobile').val();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('send-vendor-otp') }}",
                data: {
                    phone: phone
                },
                success: function(data) {
                    console.log(data)
                    if (data.status) {
                        if (data.action == 'otp_sent') {
                            $('#ver_phone').val(data.phone)
                            $('#verify_screen').show();
                            $('.send_login_otp').hide();
                        } else {}
                    }
                    toasterNotification(data.message)
                },
                complete: function() {
                    $('#send_otp_btn').removeAttr('disabled')

                }
            });
        })

        $('#switch_login_with_pass').on('click', function() {
            $('.login_with_password_screen').show()
            $('#login_with_otp_screen').hide()
        })

        $('#switch_login_with_otp').on('click', function() {
            $('.login_with_password_screen').hide()
            $('#login_with_otp_screen').show()
        })

        function toasterNotification(msg) {
            $("#toast").text(msg);
            $("#toast").addClass("show");
            setTimeout(function() {
                $("#toast").removeClass("show");
            }, 3000);
        }
    </script>
    <script>
        document.querySelectorAll('.my-nav-link').forEach(link => {
            link.addEventListener('click', function() {
                document.querySelectorAll('.my-nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
        $(".duration_badge").on('click', function() {
            var plan_id = $(this).attr('data-plan');
            var price = $(this).attr('data-price');
            $('.duration_set_' + plan_id).removeClass('active')
            $(this).addClass('active')
            $(".plan_price_" + plan_id).text(price)

        })

        $(".reviews-carousel").owlCarousel({
            autoplay: true,
            smartSpeed: 500,
            center: false,
            dots: true,
            loop: true,
            margin: 0,
            nav: false,
            navText: [
                '<',
                '>'
            ],
            responsiveClass: true,
            responsive: {
                0: {
                    items: 1
                },
                576: {
                    items: 2
                },
                768: {
                    items: 3
                },
                992: {
                    items: 3
                },
                1200: {
                    items: 4
                }
            }
        });
        // scrolling titles
        $(document).ready(function() {
            const titleElements = $('.animated-title-text');
            let currentTitleIndex = 0;
            const totalTitles = titleElements.length;
            const rotationInterval = 5000; // 3 seconds

            function showNextTitle() {
                // Remove active class from current title and dot
                titleElements.eq(currentTitleIndex).removeClass('title-visible').addClass('title-exiting');

                // Update index
                currentTitleIndex = (currentTitleIndex + 1) % totalTitles;

                // After a short delay, reset and show next title
                setTimeout(() => {
                    titleElements.removeClass('title-exiting');
                    titleElements.eq(currentTitleIndex).addClass('title-visible');
                }, 300);
            }

            // Start the animation loop
            let titleRotatorInterval = setInterval(showNextTitle, rotationInterval);

            // Optional: Pause on hover
            $('.rotating-title-wrapper').hover(
                function() {
                    clearInterval(titleRotatorInterval);
                },
                function() {
                    // Resume animation when not hovering
                    setTimeout(() => {
                        titleRotatorInterval = setInterval(showNextTitle, rotationInterval);
                    }, 500);
                }
            );


        });
    </script>
    <!-- IE Support -->
    <script>
        if (/MSIE \d|Trident.*rv:/.test(navigator.userAgent)) document.write(
            '<script src="{{ asset('public//assets/admin') }}/vendor/babel-polyfill/polyfill.min.js"><\/script>');
    </script>

</body>

</html>
