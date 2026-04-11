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
    <link rel="stylesheet" href="{{ asset('assets/admin/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/vendor/icon-set/style.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="icon" type="image/x-icon" href="{{ asset('storage/vendor_login/mc_vendor_hub_logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin') }}/css/toastr.css">
    <link rel="stylesheet" href="{{ asset('assets/admin') }}/css/customize_plan.css">
    <link rel="stylesheet" href="{{ asset('assets/admin') }}/css/common.css">
    <link href="{{ asset('assets/front/lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">
    <style>
        .mc-vendor-banner {
            background: #e9f6da;
            border-radius: 12px;
            padding: 40px 50px;
            max-width: 1000px;
            margin: 0 auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
        }

        .mc-vendor-content {
            flex: 1;
        }

        .mc-vendor-banner h2 {
            font-size: 28px;
            margin: 0 0 20px 0;
            font-weight: 600;
        }

        .mc-vendor-benefits {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .mc-vendor-benefits li {
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .mc-vendor-benefits li::before {
            content: "✓";
           background: rgb(255 255 255);
    color: #81c408;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }

        .mc-vendor-cta {
            flex-shrink: 0;
        }

        .mc-vendor-btn {
            background: white;
            color: #81c408;
            border: none;
            padding: 16px 40px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .mc-vendor-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            .mc-vendor-banner {
                flex-direction: column;
                padding: 30px 25px;
                text-align: center;
            }

            .mc-vendor-banner h2 {
                font-size: 22px;
            }

            .mc-vendor-benefits {
                flex-direction: column;
                gap: 15px;
            }

            .mc-vendor-benefits li {
                justify-content: center;
            }

            .mc-vendor-btn {
                width: 100%;
            }
        }

        .pricing-section {
            max-width: 1200px;
            margin: 40px auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .pricing-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        .pricing-content {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .pricing-badge {
            display: inline-block;
            background: #f0f9e6;
            color: #81c408;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
            width: fit-content;
        }

        .pricing-title {
            font-size: 32px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 700;
            line-height: 1.2;
        }

        .pricing-subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .pricing-features {
            list-style: none;
            margin-bottom: 30px;
        }

        .pricing-features li {
            padding: 10px 0;
            color: #555;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .pricing-features li:before {
            content: "✓";
            color: #81c408;
            font-weight: bold;
            font-size: 18px;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .pricing-cta {
            display: inline-block;
            background: #81c408;
            color: white;
            padding: 14px 32px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
            width: fit-content;
            border: none;
            cursor: pointer;
        }

        .pricing-cta:hover {
            background: #6fa607;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(129, 196, 8, 0.3);
        }

        .pricing-visual {
            background: linear-gradient(135deg, #f0f9e6 0%, #e8f5d8 100%);
            padding: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .calculator-icon {
            width: 100%;
            max-width: 300px;
            height: auto;
        }

        .price-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .price-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .price-card-title {
            font-size: 14px;
            color: #333;
            font-weight: 600;
        }

        .price-card-badge {
            background: #81c408;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .price-modules {
            margin-bottom: 15px;
        }

        .price-module {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 13px;
            color: #666;
        }

        .price-module-name {
            display: flex;
            align-items: center;
        }

        .price-module-name:before {
            content: "□";
            color: #81c408;
            margin-right: 8px;
            font-size: 14px;
        }

        .price-total {
            border-top: 2px solid #81c408;
            padding-top: 15px;
            margin-top: 10px;
        }

        .price-total-row {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }

        .price-amount {
            color: #81c408;
        }

        @media (max-width: 768px) {
            .pricing-container {
                grid-template-columns: 1fr;
            }

            .pricing-content {
                padding: 30px 20px;
            }

            .pricing-title {
                font-size: 24px;
            }

            .pricing-visual {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>
    @include('mc-vendor.partials.nav')

    <div class="container" style="max-width: 1200px;">
        <div class="page_section">
            <div class="section_content row">
                <div class="section_img image-card col-md-6">
                    <img class="img-80" src="{{ asset('storage/vendor_login/empower_your_business.png') }}"
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
                            src="{{ asset('storage/vendor_login/take_your_business_digital.jpeg') }}" alt="Login">
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
                                href="https://mcvendorhub.com/privacy-policy">https://mcvendorhub.com/privacy-policy</a>
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img style="width:100%;" src="{{ asset('storage/vendor_login/privacy.jpeg') }}" alt="Login">
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
                    <img style="width:100%;" src="{{ asset('storage/vendor_login/customer_support.png') }}"
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
                    <img style="width:100%;" src="{{ asset('storage/vendor_login/innovation.png') }}" alt="Login">
                </div>
            </div>
        </div>
    </section>

    <section class="capabilities-section pb-0" id="products_section">
        <div class="container">
            <h2 style="text-align: center; font-size: 32px; font-weight: bold; color: #1f2937; margin-bottom: 50px;">
                Price Calculator
            </h2>
            <div>
                <section class="pricing-section p-0">
                    <div class="pricing-container">
                        <div class="pricing-content">
                            <span class="pricing-badge">Pricing Tool</span>
                            <h2 class="pricing-title">Calculate Your Custom Pricing</h2>
                            <p class="pricing-subtitle">
                                Get instant pricing estimates for your business needs. Our intelligent calculator helps
                                you choose the right modules and duration with automatic discounts.
                            </p>

                            <ul class="pricing-features">
                                <li>Select multiple modules based on your requirements</li>
                                <li>Choose from flexible billing cycles (1, 3, 6, or 12 months)</li>
                                <li>Get automatic discounts on longer commitments</li>
                                <li>See real-time price calculations and breakdowns</li>
                                <li>Transparent pricing with no hidden fees</li>
                            </ul>

                            <!-- Laravel Blade Button -->
                            <a href="https://mcvendorhub.com/price-calculator" class="pricing-cta">
                                Calculate Your Price →
                            </a>
                        </div>

                        <div class="pricing-visual">
                            <img style="width: 100%;   background: transparent;filter: drop-shadow(0 0 0 transparent);mix-blend-mode: multiply;"
                                src="{{ asset('storage/vendor_login') }}/price_calculator_illustration.png"
                                alt="">
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </section>

    <div class="mc-vendor-banner">
        <div class="mc-vendor-content">
            <h2>Register for FREE on MC VENDOR HUB</h2>
            <ul class="mc-vendor-benefits">
                <li>Free Billing – up to 1000 bills</li>
                <li>Free Business Webpage</li>
            </ul>
        </div>
        <div class="mc-vendor-cta">
            <a href="https://mychitti.net/list-your-business" class="mc-vendor-btn">Register Now</a>
        </div>
    </div>

    <!-- Capabilities Section - Exact replica of Image 4 -->
    <section class="capabilities-section" id="products_section">
        <div class="container">
            <h2 style="text-align: center; font-size: 32px; font-weight: bold; color: #1f2937; margin-bottom: 50px;">
                Redefine what's possible with MC Vendor Hub2
            </h2>

            <div class="capability-grid">
                @foreach ($vendor_modules as $key => $value)
                    <div class="capability-item">
                        <a href="https://mcvendorhub.com/mc-module/{{$value->slug}}" class="capability-image lead-gen">
                            <img style="width:100%;" src="{{ asset('storage/vendor_login/') . '/' . $value->image }}"
                                alt="{{ $value->name }}">
                        </a>
                        <a href="https://mcvendorhub.com/mc-module/{{$value->slug}}"
                            class="capability-title">{{ $value->name }}</a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Pricing Section - Exact replica of Image 5 -->
    {{-- <section class="pricing-section" id="pricing_section">
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
            <div class="landing-section">
                <h1>Unlock Your Perfect Plan</h1>
                <p>Our standard plans are great, but we know every business is unique. Whether you need specific
                    features, custom integrations, or flexible pricing, we're here to build a subscription plan that
                    fits your exact needs. Let's create something tailored just for you.</p>
                <button class="cta-button" data-bs-toggle="modal" data-bs-target="#subscriptionModal">
                    Want a Customized Plan?
                </button>
            </div>

           

            <div class="card" style="    background: #7c3aed2e;padding: 16px;font-size: 18px;">
                Pay-As-You-Go Add-Ons Are Now Conveniently Available for You</div>
        </div>
    </section> --}}
    <div class="container my-2" id="review_section">
        <h1 class="text-center">
            See how customers accomplish more with MC Vendor Hub .</h1>
        <p>Having the ability to create, collaborate, and communicate in a very seamless way makes our family’s day to
            day more efficient.
        </p>
    </div>
    <div class="d-flex justify-content-center container">
        {{-- <img style="width:100%;" src="{{ asset('storage/vendor_login/customer_accomplishment.png') }}"
            alt="customers accomplishment"> --}}
        <div class="owl-carousel reviews-carousel ">
            @for ($i = 1; $i <= 6; $i++)
                <img class="" src="{{ asset('storage/vendor_login/reviews') . '/' . $i . '.png' }}"
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
                src="{{ asset('storage/vendor_login/faq_banner.jpeg') }}" alt="Login">
            {{-- <div class="row g-0 px-5">
                <div class="col-md-6">
                    <img style="width:100%;"
                        src="{{ asset('storage/vendor_login/faq_1-removebg-preview.png') }}"
                        alt="Login">
                </div>
                <div class="col-md-6">
                    <img style="width:100%;" src="{{ asset('storage/vendor_login/faq.webp') }}"
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

    <div class="modal fade" id="subscriptionModal" tabindex="-1" aria-labelledby="subscriptionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="subscriptionModalLabel">Customized Subscription Plan Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="formContent">
                        <p class="text-muted mb-4">Tell us about your needs and we'll create a tailored subscription
                            plan for you.</p>

                        <form id="subscriptionForm" method="post"
                            action= "{{ route('vendor.request-subscription-plan') }}">
                            @csrf
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label ">Company Name</label>
                                    <input type="text" class="form-control" name="company_name" id="companyName"
                                        placeholder="Enter company name">
                                    <div class="invalid-feedback" id="companyNameError"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Contact Name</label>
                                    <input type="text" class="form-control" name="contact_name" id="contactName"
                                        placeholder="Enter your name" required>
                                    <div class="invalid-feedback" id="contactNameError"></div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label ">Email Address</label>
                                    <input type="email" class="form-control" name="email" id="email"
                                        placeholder="example@gmail.com">
                                    <div class="invalid-feedback" id="emailError"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" id="phone"
                                        placeholder="9988776655" required>
                                    <div class="invalid-feedback" id="phoneError"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Business Type</label>
                                <select name="business_type" required class="form-select business_type"
                                    id="shop_business_type" fdprocessedid="ugp9m">
                                    <option value="">Business Type</option>
                                    <option value="Professionals">Professionals</option>
                                    <option value="Manufacturer">Manufacturer</option>
                                    <option value="Business">Business</option>
                                    <option value="Stores">Stores</option>
                                    <option value="Shops">Shops</option>
                                    <option value="Self Employee">Self Employee</option>
                                    <option value="Skilled Labour">Skilled Labour</option>
                                    <option value="Farmer">Farmer</option>
                                    <option value="Contractor">Contractor</option>
                                    <option value="Hospital">Hospital</option>
                                    <option value="School">School</option>
                                    <option value="Retail &amp; Wholesaler">Retail &amp; Wholesaler</option>
                                    <option value="Hospital">Hospital</option>
                                    <option value="other">Other</option>
                                </select>
                                <div class="invalid-feedback" id="industryError"></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Required Features</label>
                                <div class="features-grid" id="featuresGrid">
                                    {{-- Features will be populated from backend --}}
                                    @foreach ($features as $key => $feature)
                                        <div class="feature-option" data-feature-id="{{ $feature->key }}"
                                            onclick="toggleFeature('{{ $feature->key }}', this)">
                                            <div class="checkbox"></div>
                                            <span>{{ $feature->key }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                <input type="hidden" name="features" id="featuresInput">
                                <div class="invalid-feedback" id="featuresError"></div>
                            </div>


                            <div class="mb-4">
                                <label class="form-label">Additional Requirements</label>
                                <textarea class="form-control" name="additional_requirements" id="additionalRequirements" rows="3"
                                    placeholder="Tell us about any specific requirements, integrations, or customizations you need..."></textarea>
                                <div class="invalid-feedback" id="additionalRequirementsError"></div>
                            </div>

                            <button type="button" class="submit-btn" id="submitBtn" onclick="handleSubmit()">
                                <span id="submitBtnText">Submit Request</span>
                                <span id="submitBtnSpinner" class="spinner-border spinner-border-sm ms-2"
                                    style="display: none;"></span>
                            </button>
                        </form>
                    </div>

                    <div id="successContent" class="success-content" style="display: none;">
                        <div class="success-icon">✓</div>
                        <h3>Request Submitted!</h3>
                        <p>Thank you for your interest. Our team will review your requirements and contact you within
                            1-2 business days.</p>
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- footer section  --}}
    @include('mc-vendor.partials.footer')

    <!-- JS Implementing Plugins -->
    <script src="{{ asset('assets/admin') }}/js/vendor.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
    <script src="{{ asset('assets/front/lib/owlcarousel/owl.carousel.min.js') }}"></script>

    <!-- JS Front -->
    <script src="{{ asset('assets/admin') }}/js/theme.min.js"></script>
    <script src="{{ asset('assets/admin') }}/js/toastr.js"></script>
    {!! Toastr::message() !!}

    @if ($errors->any())
        <script>
            "use strict";
            @foreach ($errors->all() as $error)
                toastr.error("{{ $error }}", Error, {
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
        let selectedFeatures = [];

        function toggleFeature(featureId, element) {
            if (selectedFeatures.includes(featureId)) {
                selectedFeatures = selectedFeatures.filter(f => f !== featureId);
                element.classList.remove('selected');
            } else {
                selectedFeatures.push(featureId);
                element.classList.add('selected');
            }

            // Update hidden input
            document.getElementById('featuresInput').value = JSON.stringify(selectedFeatures);

            // Clear error if features are selected
            if (selectedFeatures.length > 0) {
                document.getElementById('featuresInput').classList.remove('is-invalid');
                document.getElementById('featuresError').textContent = '';
            }
        }

        function clearErrors() {
            document.querySelectorAll('.form-control, .form-select').forEach(el => {
                el.classList.remove('is-invalid');
            });
            document.querySelectorAll('.invalid-feedback').forEach(el => {
                el.textContent = '';
            });
        }

        function showError(fieldName, message) {
            const field = document.getElementById(fieldName);
            const errorDiv = document.getElementById(fieldName + 'Error');

            if (field) {
                field.classList.add('is-invalid');
            }
            if (errorDiv) {
                errorDiv.textContent = message;
            }
        }
        async function handleSubmit() {
            clearErrors();
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnText = document.getElementById('submitBtnText');
            const submitBtnSpinner = document.getElementById('submitBtnSpinner');

            // Disable button and show spinner
            submitBtn.disabled = true;
            submitBtnSpinner.style.display = 'inline-block';
            submitBtnText.textContent = 'Submitting...'
            //const form = document.getElementById('subscriptionForm');
            //form.submit();



            const formData = new FormData(document.getElementById('subscriptionForm'));
            //  formData.append('features', JSON.stringify(selectedFeatures));
            selectedFeatures.forEach(feature => {
                formData.append('features[]', feature);
            });
            try {
                const response = await fetch('{{ route('vendor.request-subscription-plan') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok) {
                    // Show success message
                    document.getElementById('formContent').style.display = 'none';
                    document.getElementById('successContent').style.display = 'block';
                } else {
                    // Handle validation errors
                    if (data.status) {
                        Object.keys(data.errors).forEach(key => {
                            const fieldName = key.replace('_', '');
                            const camelCaseKey = fieldName.replace(/_([a-z])/g, (g) => g[1].toUpperCase());
                            showError(camelCaseKey, data.errors[key][0]);
                        });
                    } else {
                        alert('An error occurred. Please try again.');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            } finally {
                // Re-enable button and hide spinner
                submitBtn.disabled = false;
                submitBtnSpinner.style.display = 'none';
                submitBtnText.textContent = 'Submit Request';
            }

        };

        function resetForm() {
            document.getElementById('subscriptionForm').reset();
            selectedFeatures = [];
            document.querySelectorAll('.feature-option').forEach(option => {
                option.classList.remove('selected');
            });
            document.getElementById('featuresInput').value = '';
            clearErrors();

            document.getElementById('formContent').style.display = 'block';
            document.getElementById('successContent').style.display = 'none';
        }

        // Reset form when modal is closed
        document.getElementById('subscriptionModal').addEventListener('hidden.bs.modal', function() {
            resetForm();
        });
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
