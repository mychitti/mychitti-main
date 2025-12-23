<!DOCTYPE html>
<?php
$log_email_succ = session()->get('log_email_succ');
?>

<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Chitti — Business Verification</title>
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
        .status-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .status-card {
            background: #fff;
            padding: 40px 30px;
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
            text-align: center;
            max-width: 380px;
            width: 100%;
        }

        .status-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #4CAF50;
            color: white;
            font-size: 35px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
        }

        .status-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #333;
        }

        .status-message {
            font-size: 16px;
            color: #555;
            margin-bottom: 25px;
        }

        .status-btn {
            padding: 12px 20px;
            border-radius: 10px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: 600;
            transition: 0.2s;
        }

        .status-btn:hover {
            background: #0066d6;
        }

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
    @php
    $logo = asset('storage/app/public/vendor_login/mc_vendor_hub_logo.png'); @endphp
    {{-- <div id="toast" class="toast">This is a toaster notification!</div> --}}

    <div class="login-page" id="login-page" style=" margin: 0 auto;">
        <div class="login-container login-card">
            <!-- Left Section -->
            <div class="login-left">

                <div class="d-flex justify-content-center w-100">
                    <a style="    width: 135px; margin:0 auto;" href="{{ route('home') }}">
                        <img style="    width: 135px; margin:0 auto;" src="{{ $logo ?? '' }}" alt="logo" />
                    </a>
                </div>


                <div id="login_with_otp_screen" class=" "
                    {{ request()->has('phone') ? '' : 'style=display:none;' }}>

                    <form class="send_login_otp login-form" action="{{ route('send-vendor-otp') }}" method="post"
                        id="form-id">
                        @csrf
                        <div class="login-title">
                            <div class="lock-box" aria-hidden>
                                <img style="width: 50px;"
                                    src="{{ asset('storage/app/public/vendor_login/login_icon.jpeg') }}" alt="">
                            </div>
                            @if (!request()->has('phone'))
                                <div>
                                    <h2>Login with OTP</h2>
                                    <div style="font-size:13px;color:var(--login-muted);margin-top:6px">
                                        Secure access to My Chitti vendor dashboard
                                    </div>
                                </div>
                            @else
                                <div>
                                    <h2>Business Verification</h2>
                                    <div style="font-size:13px;color:var(--login-muted);margin-top:6px">
                                        Complete your business verification to access My Chitti vendor dashboard
                                    </div>
                                </div>
                            @endif
                        </div>
                        <input type="hidden" name="role" value="{{ $role ?? null }}">

                        <!-- Form Group -->
                        <div class="js-form-message form-group">
                            <label class="" for="signinSrEmail">{{ translate('messages.mobile_number') }}</label>

                            <input type="text" {{ request('phone') ? 'disabled' : '' }}
                                value="{{ request('phone') ?? '' }}" class="login-input mb-2" name="phone"
                                id="signinSrMobile" tabindex="1" placeholder="Ex: 8899779988" aria-label="8899779988"
                                required data-msg="{{ translate('Please_enter_mobile_number.') }}">
                        </div>
                        <!-- End Form Group -->

                        <button type="submit" id="send_otp_btn" class="btn login-btn ">Send OTP</button>
                    </form>
                    <!--  -->
                    <div class="container-fluid contact pt-5" style="display:none;" id="verify_screen">
                        <form class="otpForm login-form  otpscreen" action="{{ route('login_otp') }}" method="post">
                            @csrf
                            <div class="">
                                <div class="login-title">
                                    <div class="lock-box" aria-hidden>
                                        <img style="width: 50px;"
                                            src="{{ asset('storage/app/public/vendor_login/login_icon.jpeg') }}"
                                            alt="">
                                    </div>
                                    <div>
                                        <h2>Enter OTP</h2>
                                        <div style="font-size:13px;color:var(--login-muted);margin-top:6px">
                                            Secure access to My Chitti vendor dashboard
                                        </div>
                                    </div>
                                </div>
                                <div class="container py-5" style="    background: #ecfaff;border-radius: 13px;">
                                    <h4 class="text-center">Enter OTP</h4>
                                    <div class=" rounded" style="max-width: 550px; margin: 0 auto;">
                                        <div class="row ">
                                            <div
                                                style="margin: 0 auto;    display: flex;flex-direction: column;gap: 10px;    align-items: center;">

                                                <input type="hidden" name="phone" id="ver_phone" value="">
                                                <div class="d-flex justify-content-center">
                                                    <input type="number" maxlength="1" class="otp-input"
                                                        name="otp[]" />
                                                    <input type="number" maxlength="1" class="otp-input"
                                                        name="otp[]" />
                                                    <input type="number" maxlength="1" class="otp-input"
                                                        name="otp[]" />
                                                    <input type="number" maxlength="1" class="otp-input"
                                                        name="otp[]" />
                                                </div>
                                                <span class="text-danger otp_error"></span>
                                                @if (!request()->has('phone'))
                                                    <button type="submit" class="btn login-btn"
                                                        style="width: fit-content;">Submit</button>
                                                @else
                                                    <button type="button" id="verify_otp_ajax" class="btn login-btn"
                                                        style="width: fit-content;">Submit</button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <form action="{{ route('business-verify') }}" method="POST" enctype="multipart/form-data"
                            id="documentForm" class="documents_screen" style="display:none">
                            @csrf
                            <input type="hidden" name="phone" id="ver_phone2" value="">
                            <div>
                                <div class="card border-0">
                                    <div class="login-title p-3">
                                        <div class="lock-box" aria-hidden>
                                            <img style="width: 50px;"
                                                src="{{ asset('storage/app/public/vendor_login/login_icon.jpeg') }}"
                                                alt="">
                                        </div>
                                        <div>
                                            <h2>Upload Documents</h2>
                                            <div style="font-size:13px;color:var(--login-muted);margin-top:6px">
                                                Documents are required to verify your business and ensure compliance
                                                with regulations.
                                            </div>
                                        </div>
                                    </div>


                                    <div class="card-body">
                                        <div class="alert alert-warning" role="alert">
                                            <small><strong>Note:</strong> Please upload at least one
                                                document.</small>
                                        </div>

                                        <!-- GST Document -->
                                        <div class="form-group">
                                            <label for="gstFile">GST Document </label>
                                            <input type="file" class="form-control" id="gstFile" name="gst_doc"
                                                style="height: 38px !important;"
                                                accept=".pdf,.png,.jpg,.jpeg,image/*">
                                            <small class="form-text text-muted">Accepted: PDF, PNG, JPG, JPEG
                                                (Max 5MB)</small>
                                        </div>

                                        <!-- ID Proof -->
                                        <div class="form-group">
                                            <label for="idFile">ID Proof </label>
                                            <input type="file" class="form-control" id="idFile" name="id_doc"
                                                style="height: 38px !important;"
                                                accept=".pdf,.png,.jpg,.jpeg,image/*">
                                            <small class="form-text text-muted">Accepted: PDF, PNG, JPG, JPEG
                                                (Max 5MB)</small>
                                        </div>

                                        <!-- Error Message -->
                                        <div class="text-danger" id="errorMsg" style="display: none;"></div>

                                        <!-- Submit Button -->
                                        <button type="submit" class="btn login-btn">Submit
                                            Documents</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div class="verification_success_screen" style="display:none;">
                            <div class="status-container">
                                <div class="status-card">
                                    <div class="status-icon">
                                        ✔
                                    </div>

                                    <h2 class="status-title">Claim Submitted</h2>

                                    <p class="status-message">
                                        Your request is <strong>In Progress</strong>.
                                        We will review it soon and notify you.
                                    </p>

                                    <a href="{{ url('/') }}" class="btn login-btn">Go to Website</a>
                                </div>
                            </div>

                        </div>
                    </div>
                    @if (!request()->has('phone'))
                        <div class="or-text">OR</div>
                        <div class="btn signup-btn" id="switch_login_with_pass">
                            {{-- <img src="{{ asset('storage/app/public/util/OTP-1024.webp') }}" alt="OTP"> --}}
                            {{-- <img src="https://img.icons8.com/color/48/000000/google-logo.png" alt="Google"> --}}
                            Login in with Password
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Section -->
            <div class="login-right">
                <div class="image-card">
                    <img class="img-100" src="{{ asset('storage/app/public/vendor_login/user_w_pc.png') }}"
                        alt="Login">
                </div>
                <div class="px-5 w-100 ">
                </div>

            </div>
        </div>

    </div>


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
        $(document).ready(function() {
            // Form submit
            $('#documentForm').on('submit', function(e) {
                e.preventDefault();

                $('#errorMsg').hide();

                var gstFile = $('#gstFile')[0].files[0];
                var idFile = $('#idFile')[0].files[0];

                // Check if at least one file is uploaded
                if (!gstFile && !idFile) {
                    $('#errorMsg').text('Please upload at least one document.').show();
                    return false;
                }

                // Validate file size (5MB max)
                var maxSize = 5 * 1024 * 1024;
                if (gstFile && gstFile.size > maxSize) {
                    $('#errorMsg').text('GST Certificate file size exceeds 5MB.').show();
                    return false;
                }
                if (idFile && idFile.size > maxSize) {
                    $('#errorMsg').text('ID Proof file size exceeds 5MB.').show();
                    return false;
                }

                // Prepare FormData
                var formData = new FormData();
                if (gstFile) formData.append('gst_doc', gstFile);
                if (idFile) formData.append('id_doc', idFile);
                formData.append('phone', $('#ver_phone2').val());

                // Submit to backend
                $.ajax({
                    url: $(this).attr("action"), // Change to your endpoint
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log(response);
                        if (response.status) {
                            $(".verification_success_screen").show();
                            $(".documents_screen").hide();
                            $('#documentForm')[0].reset();
                        } else {
                            $('#errorMsg').text(response.message).show();
                        }
                    },
                    error: function(xhr) {
                        $('#errorMsg').text('Upload failed. Please try again.').show();
                    }
                });
            });
        });
    </script>
    <script>
        "use strict";
        $("#verify_otp_ajax").on('click', function() {
            var otp = [];
            $('.otp-input').each(function() {
                otp.push($(this).val());
            });
            var phone = $('#ver_phone').val();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('login_otp_ajax') }}",
                data: {
                    phone: phone,
                    otp: otp.join('')
                },
                success: function(data) {
                    console.log(data)
                    if (data.status) {
                        $('.otpscreen').hide()
                        $('.documents_screen').show()
                        $(".documentForm").attr('id', 'documentForm')
                    } else {
                        $(".otp_error").text(data.message)
                    }
                }
            });
        })
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
                            $('#ver_phone2').val(data.phone)
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
    </script>
    <!-- IE Support -->
    <script>
        if (/MSIE \d|Trident.*rv:/.test(navigator.userAgent)) document.write(
            '<script src="{{ asset('public//assets/admin') }}/vendor/babel-polyfill/polyfill.min.js"><\/script>');
    </script>

</body>

</html>
