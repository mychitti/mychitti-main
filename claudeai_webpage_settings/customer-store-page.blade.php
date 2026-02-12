<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $storeConfig->webpage_name ?? $currentStore->name }} | MyChitti</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="{{ $currentStore->meta_description ?? 'Welcome to our store' }}">
    <meta name="keywords" content="{{ $currentStore->name }}, online store, services">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="{{ $currentStore->meta_title ?? $currentStore->name }}">
    <meta property="og:description" content="{{ $currentStore->meta_description ?? 'Welcome to our store' }}">
    <meta property="og:image" content="{{ $currentStore->meta_image ? asset('storage/app/public/store/' . $currentStore->meta_image) : '' }}">
    <meta property="og:url" content="{{ $storeConfig->website_url }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f9fafb;
        }

        /* Header/Hero Section */
        .store-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .store-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('{{ $currentStore->cover_photo ? asset('storage/app/public/store/' . $currentStore->cover_photo) : '' }}');
            background-size: cover;
            background-position: center;
            opacity: 0.3;
        }

        .store-header-content {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
        }

        .store-logo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 24px;
            background: white;
            padding: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .store-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .store-title {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .store-description {
            font-size: 18px;
            opacity: 0.95;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Navigation */
        .store-nav {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .store-nav-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 16px 20px;
            display: flex;
            gap: 32px;
            justify-content: center;
        }

        .store-nav a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .store-nav a:hover {
            color: #667eea;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        /* Section Titles */
        .section-title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 40px;
            text-align: center;
            color: #1a1a1a;
        }

        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .service-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .service-card h3 {
            font-size: 24px;
            margin-bottom: 12px;
            color: #667eea;
        }

        .service-card p {
            color: #666;
            margin-bottom: 16px;
        }

        .service-price {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }

        /* Inventory Grid */
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 60px;
        }

        .inventory-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }

        .inventory-card:hover {
            transform: translateY(-3px);
        }

        .inventory-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .inventory-info {
            padding: 20px;
        }

        .inventory-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .inventory-price {
            font-size: 22px;
            font-weight: 700;
            color: #667eea;
        }

        /* Contact Section */
        .contact-section {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 40px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .contact-item {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .contact-label {
            font-weight: 600;
            color: #667eea;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .contact-value {
            color: #333;
            font-size: 16px;
        }

        .phone-list {
            list-style: none;
        }

        .phone-list li {
            margin-bottom: 8px;
        }

        /* Map */
        .map-container {
            width: 100%;
            height: 400px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        /* Social Links */
        .social-links {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-top: 40px;
        }

        .social-link {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 24px;
            transition: transform 0.3s, background 0.3s;
        }

        .social-link:hover {
            transform: scale(1.1);
            background: #764ba2;
        }

        /* Footer */
        .store-footer {
            background: #1a1a1a;
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .store-footer p {
            margin-bottom: 8px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .store-title {
                font-size: 32px;
            }

            .store-nav-content {
                flex-direction: column;
                gap: 16px;
            }

            .services-grid,
            .inventory-grid {
                grid-template-columns: 1fr;
            }

            .contact-section {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="store-header">
        <div class="store-header-content">
            @if($currentStore->logo)
            <div class="store-logo">
                <img src="{{ asset('storage/app/public/store/' . $currentStore->logo) }}" alt="{{ $currentStore->name }}">
            </div>
            @endif
            <h1 class="store-title">{{ $storeConfig->webpage_name ?? $currentStore->name }}</h1>
            <p class="store-description">{{ $currentStore->meta_description ?? 'Welcome to our store' }}</p>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="store-nav">
        <div class="store-nav-content">
            <a href="#services">Services</a>
            <a href="#inventory">Products</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <!-- Inventory Section (position controlled by settings) -->
        @if($storeConfig->inventory_items_position === 'above')
            @include('customer-views.partials.inventory-section')
        @endif

        <!-- Services Section -->
        <section id="services" class="container">
            <h2 class="section-title">Our Services</h2>
            <div class="services-grid">
                @foreach($currentStore->items as $service)
                <div class="service-card">
                    <h3>{{ $service->name }}</h3>
                    <p>{{ $service->description }}</p>
                    <div class="service-price">₹{{ number_format($service->price, 2) }}</div>
                </div>
                @endforeach
            </div>
        </section>

        <!-- Inventory Section (if position is below) -->
        @if($storeConfig->inventory_items_position === 'below')
            @include('customer-views.partials.inventory-section')
        @endif

        <!-- Contact Section -->
        <section id="contact" class="container">
            <h2 class="section-title">Get In Touch</h2>
            <div class="contact-section">
                <div class="contact-grid">
                    <div class="contact-item">
                        <span class="contact-label">📧 Email</span>
                        <span class="contact-value">{{ $storeConfig->webpage_email ?? $currentStore->email }}</span>
                    </div>

                    <div class="contact-item">
                        <span class="contact-label">📱 Phone</span>
                        <ul class="phone-list">
                            @foreach($storeConfig->phone_numbers as $phone)
                            <li class="contact-value">{{ $phone }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="contact-item">
                        <span class="contact-label">📍 Address</span>
                        <span class="contact-value">{{ $storeConfig->webpage_address ?? $currentStore->address }}</span>
                    </div>

                    @if($storeConfig->gst_number)
                    <div class="contact-item">
                        <span class="contact-label">🆔 GST Number</span>
                        <span class="contact-value">{{ $storeConfig->gst_number }}</span>
                    </div>
                    @endif
                </div>

                <!-- Map -->
                @if($storeConfig->webpage_latitude && $storeConfig->webpage_longitude)
                <div class="map-container" id="storeMap"></div>
                @endif

                <!-- Social Links -->
                <div class="social-links">
                    @if($currentStore->fb_url)
                    <a href="{{ $currentStore->fb_url }}" target="_blank" class="social-link">📘</a>
                    @endif
                    @if($currentStore->insta_url)
                    <a href="{{ $currentStore->insta_url }}" target="_blank" class="social-link">📸</a>
                    @endif
                    @if($currentStore->twitter_url)
                    <a href="{{ $currentStore->twitter_url }}" target="_blank" class="social-link">🐦</a>
                    @endif
                    @if($currentStore->linkedin_url)
                    <a href="{{ $currentStore->linkedin_url }}" target="_blank" class="social-link">💼</a>
                    @endif
                    @if($currentStore->pinterest_url)
                    <a href="{{ $currentStore->pinterest_url }}" target="_blank" class="social-link">📌</a>
                    @endif
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="store-footer">
        <p>&copy; {{ date('Y') }} {{ $currentStore->name }}. All rights reserved.</p>
        <p>Powered by <a href="https://mychitti.net" style="color: #667eea;">MyChitti</a></p>
    </footer>

    <!-- Google Maps -->
    @if($storeConfig->webpage_latitude && $storeConfig->webpage_longitude)
    <script>
        function initMap() {
            const location = {
                lat: {{ $storeConfig->webpage_latitude }},
                lng: {{ $storeConfig->webpage_longitude }}
            };

            const map = new google.maps.Map(document.getElementById("storeMap"), {
                zoom: 15,
                center: location,
            });

            new google.maps.Marker({
                position: location,
                map: map,
                title: "{{ $currentStore->name }}"
            });
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap" async defer></script>
    @endif
</body>
</html>