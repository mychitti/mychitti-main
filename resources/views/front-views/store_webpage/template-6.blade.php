@extends('front-views.layout')

@section('title', $store['meta_title'] ?? ($data['store_config']?->webpage_name ?? $store['name']))

@section('meta_keywords', $keywords)
@section('meta_description', $store['meta_description'])

 
@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}"> 
     <style>
        @media (max-width: 992px) {

            .secondary_nav,
            .page-header {
                margin-top: 0px !important;
            }
        }

        .store-hero {
            position: relative;
        }

        .store_cover {
            height: 500px;
            width: 100%;
            object-fit: cover;
        }

        .utility_div {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .gallery-carousel img {
            object-fit: cover;
            {{-- height: 210px; --}} aspect-ratio: 1/1;
        }

        .store_logo {
            height: 75px;
            {{-- aspect-ratio: 1; --}} padding: 15px 5px;
            {{-- object-fit: cover; --}}
        }

        .spacer {
            height: 78px;
        }

        @media (max-width: 500px) {
            .spacer {
                height: 12px;
            }
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
    </style>
    <style>
        :root {
            /* BRAND COLORS */
            --primary: #0d9488;
            /* Teal (main brand) */
            --primary-dark: #0f766e;
            --secondary: #4338ca;
            /* Indigo accent */

            /* UI COLORS */
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;

            /* STATUS */
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #f59e0b;

            /* SHAPE */
            --radius: 14px;
        }

        /* Reset feel */
        body {
            background: var(--bg);
        }

        body {
            color: var(--text);
            background: var(--bg);
        }

        /* LINKS */
        a {
            color: var(--primary);
        }

        a:hover {
            color: var(--primary-dark);
        }

        /* BUTTONS */
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: #fff;
        }

        /* NAV */
        .my-nav-link {
            color: #334155;
        }

        .my-nav-link.active {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
        }

        /* SECTION HEADING */
        .sec_heading::after {
            background: var(--secondary);
        }

        /* BADGES */
        .discount_badge {
            background: var(--danger);
        }

        /* CARDS */
        .fruite-item,
        .store-hero-card,
        .contact-box {
            background: var(--card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }

        /* PRICE */
        .price,
        .text-price {
            color: var(--primary);
        }


        /* Utility */
        .shadow-soft {
            box-shadow: 0 10px 25px rgba(0, 0, 0, .06);
        }

        .rounded-xl {
            border-radius: var(--radius);
        }

        .text-muted {
            color: var(--muted);
        }

        .section {
            padding: 60px 20px
        }

        /* NAV */
        .store-nav {
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, .9) !important;
        }

        .my-nav-link {
            font-weight: 500;
            color: #334155;
            text-decoration: none;
        }

        .my-nav-link.active {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
        }

        /* HERO */
        .store-hero-wrap {
            background: linear-gradient(120deg, #eef2ff, #f8fafc);
            padding-top: 30px;
        }

        .store-hero-card {
            background: var(--card);
            border-radius: 20px;
            padding: 24px;
        }

        /* PRODUCT CARD */
        .product_card {
            transition: .25s ease;
        }

        .product_card:hover {
            transform: translateY(-4px);
        }

        .fruite-item {
            background: var(--card);
            border-radius: 16px;
            overflow: hidden;
        }

        .product_name {
            font-size: 15px;
            font-weight: 600;
        }

        /* BADGES */
        .discount_badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #dc2626;
            color: #fff;
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 20px;
        }

        /* CONTACT */
        .contact-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        .contact-box {
            background: var(--card);
            padding: 16px;
            border-radius: 16px;
            text-align: center;
            width: 100% !important;
        }

        /* SECTION HEADINGS */
        .sec_heading {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 20px;
            position: relative;
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #333;
            padding: 5px;
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .medical-nav {
                display: none ;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                    gap: 1rem;
                background: #fff;
                flex-direction: column;
                padding: 15px 0;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                z-index: 10;
            }

            .medical-nav.show {
                display: flex;
            }

            .medical-nav a {
                padding: 10px 20px;
                display: block;
            }

            .header-main-content {
                position: relative;
            }

            .logo-text p {
                display: none;
            }

            .header-main-content {
                padding: 0 10px
            }
            .logo-text h1{
                font-size: 15px;    
            }
            .store-hero-wrap{
                padding-top: 0px;
            }
        }
    </style>

    <!-- LightGallery CSS -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lightgallery.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lightgallery.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-thumbnail.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-video.css">
    <!-- LightGallery JS -->
    {{-- <script src="https://maps.googleapis.com/maps/api/js?key={{\App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value}}&libraries=places"></script> --}}
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
            img.src =
                "{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}"; // ✅ Replace with your image URL
            img.style.width = "45px"; // ✅ Adjust size
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
    {{-- <div class="spacer"></div> --}}
    <div class="header-main">
        <div class="header-main-content">
            <div class="medical-logo-section">
                <img loading="lazy" class="medical-logo"
                    src="{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}" alt="{{ $store['name'] }}">
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
    <div class="store-hero-wrap">
        <div class="container section">
            <div class="row g-4 align-items-center">

                <div class="col-md-5">
                    <img class="rounded-xl shadow-soft w-100"
                        src="{{ asset('storage/app/public/store/cover/') . '/' . $store['cover_photo'] }}">
                </div>

                <div class="col-md-7">
                    <div class="store-hero-card shadow-soft">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <img class="rounded-circle" width="60"
                                src="{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}">
                            <div>
                                <h1 class="fs-4 mb-0">
                                    {{ $data['store_config']?->webpage_name ?? $store['name'] }}
                                </h1>
                                <small class="text-muted">{{ $store['meta_title'] }}</small>
                            </div>
                        </div>

                        {{-- Description (UNCHANGED LOGIC) --}}
                        <p class="mt-3">
                            {!! Str::limit($store['meta_description'], 120) !!}
                        </p>

                        <div class="d-flex align-items-center gap-3 mt-3">
                            <strong>{{ number_format($store->average_rating, 1) }}</strong>
                            <div class="rating-stars" data-rating="{{ $store->average_rating }}"></div>
                            <small class="text-muted">({{ $store->rating_count }} reviews)</small>
                        </div>

                        <div class="mt-4 d-flex gap-2 flex-wrap">
                            <a href="#services" class="btn btn-primary px-4">View Services</a>
                            <a href="{{ route('store.gallery', [$store['slug']]) }}"
                                class="btn btn-outline-secondary">Gallery</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>


    <div class="owl-carousel 3banner-carousel justify-content-center mt-2 store_banner">
        @foreach ($data['banners'] as $key => $value)
            <a href="{{ $value->default_link ?? '#' }}" onclick="trackBannerClick({{ $value->id }})"><img loading="lazy"
                    src="{{ asset('storage/app/public/banner/') . '/' . $value->image }}" alt="banner"></a>
        @endforeach
    </div>

    @if ($store->announcement)
        <div class="container">
            <div class="alert alert-success my-3" role="alert">
                <i class="fa fa-solid fa-bullhorn"></i> {{ $store->announcement_message }}
            </div>
        </div>
    @endif

    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog  modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">
                        {{ $data['store_config']?->webpage_name ?? $store['name'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="map"></div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>
    <div class="row " style=" --bs-gutter-x: 0rem !important;">
        <!-- products  -->


        <!-- Fruits Shop Start-->
        <div class="container-fluid fruite">
            <div class="container">

                <div class="" id="">
                    <div class="section bg-white" id="services">
                        <div class="container">

                            <h3 class="sec_heading">Services & Products</h3>

                            @foreach ($productdata as $cat)
                                <h5 class="mt-4">{{ $cat->name }}</h5>

                                <div class="row g-4 mt-1">
                                    @foreach ($cat->items as $pro)
                                        <div class="col-xl-2 col-lg-3 col-md-4 col-6 product_card">
                                            <div class="fruite-item shadow-soft">

                                                <div class="position-relative">
                                                    <img class="w-100" style="height:180px;object-fit:cover"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                            $pro->image,
                                                            asset('storage/app/public/product/') . '/' . $pro->image,
                                                            asset('public/assets/admin/img/160x160/img1.jpg'),
                                                            'product/',
                                                        ) }}">
                                                    @if ($pro->discount > 0)
                                                        <div class="discount_badge">
                                                            {{ floor($pro->discount) }}%
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="p-2">
                                                    <h6 class="product_name one-line-ellipsis">
                                                        {{ ucfirst($pro->name) }}
                                                    </h6>

                                                    {{-- <p class="fw-bold mb-1">{{ _price($pro->price) }}</p> --}}

                                                    @if ($pro->item_type == 'product' && ($pro->stock ?? 1) <= 0)
                                                        <span class="badge bg-danger">Out of Stock</span>
                                                    @elseif (($data['store_config']->lead_available ?? 1) == 0)
                                                        <button disabled class="btn btn-sm btn-outline-primary w-100" style="opacity:0.5;cursor:not-allowed;">
                                                            {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquiry' }}
                                                        </button>
                                                        <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                                    @elseif (auth('web')->user())
                                                        <button
                                                            onclick="bookService({{ $pro->id }},this,{{ $store['id'] }})"
                                                            class="btn btn-sm btn-outline-primary w-100">
                                                            {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquiry' }}
                                                        </button>
                                                    @else
                                                        <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                            class="btn btn-sm btn-outline-primary w-100">
                                                            {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquiry' }}
                                                        </button>
                                                    @endif
                                                </div>

                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach

                            @foreach ($invItemdata as $cat)
                                <h5 class="mt-4">{{ $cat->name }}</h5>

                                <div class="row g-4 mt-1">
                                    @foreach ($cat->items as $pro)
                                        <div class="col-xl-2 col-lg-3 col-md-4 col-6 product_card">
                                            <div class="fruite-item shadow-soft">

                                                <div class="position-relative">
                                                    <img class="w-100" style="height:180px;object-fit:cover"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                            $pro->image,
                                                            asset('storage/app/public/product/') . '/' . $pro->image,
                                                            asset('public/assets/admin/img/160x160/img1.jpg'),
                                                            'product/',
                                                        ) }}">
                                                    @if ($pro->discount > 0)
                                                        <div class="discount_badge">
                                                            {{ floor($pro->discount) }}%
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="p-2">
                                                    <h6 class="product_name one-line-ellipsis">
                                                        {{ ucfirst($pro->name) }}
                                                    </h6>

                                                    {{-- <p class="fw-bold mb-1">{{ _price($pro->price) }}</p> --}}

                                                    @if ($pro->item_type == 'product' && ($pro->stock ?? 1) <= 0)
                                                        <span class="badge bg-danger">Out of Stock</span>
                                                    @elseif (($data['store_config']->lead_available ?? 1) == 0)
                                                        <button disabled class="btn btn-sm btn-outline-primary w-100" style="opacity:0.5;cursor:not-allowed;">
                                                            {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquiry' }}
                                                        </button>
                                                        <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                                    @elseif (auth('web')->user())
                                                        <button
                                                            onclick="bookService({{ $pro->id }},this,{{ $store['id'] }})"
                                                            class="btn btn-sm btn-outline-primary w-100">
                                                            {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquiry' }}
                                                        </button>
                                                    @else
                                                        <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                            class="btn btn-sm btn-outline-primary w-100">
                                                            {{ strtolower($store['business_type'] ?? '') === 'hospital' ? 'Book Now' : 'Enquiry' }}
                                                        </button>
                                                    @endif
                                                </div>

                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach

                        </div>
                    </div>

                    @if (count($store->galleries))
                        <div>
                            <h3 class="sec_heading mt-5">A Peek Into Our Space</h3>

                            <div class="owl-carousel gallery-carousel justify-content-center  ">
                                @foreach ($data['galleries'] as $key => $value)
                                    <a target="_blank"
                                        href="{{ asset('storage/app/public/store/gallery') }}/{{ $value->image }}"
                                        style="cursor:default;"
                                        class="table-rest-info gallery_atag gallery-item lightgallery-item"
                                        alt="Gallery image"><img loading="lazy" style="min-height:100px;" loading="lazy"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                $value['image'] ?? '',
                                                asset('storage/app/public/store/gallery') . '/' . $value['image'] ?? '',
                                                asset('public/assets/admin/img/160x160/img1.jpg'),
                                                'store/gallery/',
                                            ) }}"
                                            alt="">
                                    </a>
                                @endforeach
                            </div>


                        </div>
                    @endif
                    @if (count($data['reviews']))
                        <div class="" id="store-ratings" aria-labelledby="pills-store-ratings-tab">
                            <div class="section_spacing">
                                {{-- <h3 class="sec_heading">Store Ratings</h3> --}}

                                @if ($module == 6)
                                    <div class="col-lg-7" id="nav-mission">
                                        <h5 class="">What People Are Saying About {{ ucwords($store->name) }}</h5>
                                        <br>
                                        @foreach ($data['reviews'] as $rev)
                                            <div class="d-flex border rounded my-2  p-2">
                                                <img loading="lazy"
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/') . '/' . $rev->profile_image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                                    class="img-fluid rounded m-2 r_profile_img" style=""
                                                    alt="{{ $rev->f_name . ' ' . $rev->l_name }}">
                                                <div class="d-flex flex-column w-100">
                                                    <div class="d-flex justify-content-between review_info">
                                                        <div class="">
                                                            <p class="mb-2 date_time" style="">
                                                                {{ _formatted_datetime($rev->created_at) }}</p>
                                                            <div class="d-flex ">
                                                                <h5 class="r_name">{{ $rev->f_name . ' ' . $rev->l_name }}
                                                                </h5>
                                                                <div class="d-flex ">
                                                                    @for ($i = 1; $i < 6; $i++)
                                                                        <i
                                                                            class="rating_star fa fa-star {{ $rev->rating >= $i ? 'text-secondary' : '' }}"></i>
                                                                    @endfor
                                                                </div>
                                                            </div>
                                                            <p class="text-dark">{{ $rev->comment }}</p>
                                                        </div>

                                                        @if ($rev->attachment)
                                                            @php $attachments = (array) $rev->attachment; @endphp
                                                            @if (!empty($attachments))
                                                                <div class="d-flex">
                                                                    @foreach ($attachments as $img)
                                                                        <a target="_blank" class="mx-1"
                                                                            href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"><img
                                                                                loading="lazy" class="rounded"
                                                                                style="width: 55px;"
                                                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                                                alt="review"></a>
                                                                        <a target="_blank"
                                                                            href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"><img
                                                                                loading="lazy" class="rounded"
                                                                                style="width: 55px;"
                                                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                                                alt="review"></a>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        @endif
                                                    </div>
                                                    @if ($rev->reply)
                                                        <div class="d-flex border rounded  p-2">
                                                            <img loading="lazy"
                                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store['logo'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                                                class="img-fluid rounded m-2 reply_img" style=""
                                                                alt="{{ $store->name }}">
                                                            <div class="">
                                                                <p class="mb-0 date_time" style="">
                                                                    {{ _formatted_datetime($rev->replied_at) }}</p>

                                                                <p class="text-dark">{{ $rev->reply }}</p>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>


                                            </div>
                                        @endforeach
                                        @if (!count($data['reviews']))
                                            No Reviews yet...
                                        @elseif($data['review_count'] > 2)
                                            <div class="border p-2 ">
                                                <a href="{{ route('store.reviews', [$store->slug]) }}">View All Reviews <i
                                                        class="fa fa-solid fa-arrow-right"></i></a>
                                            </div>
                                        @endif

                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                    <div class="section" id="contact">
                        <div class="container">
                            <h3 class="sec_heading">Contact</h3>

                            <div class="contact-container">
                                <div class="contact-box shadow-soft">
                                    <strong>Address</strong>
                                    <p class="small">{{ $store['address'] }}</p>
                                </div>

                                <div class="contact-box shadow-soft">
                                    <strong>Email</strong>
                                    <a href="mailto:{{ $store['email'] }}">{{ $store['email'] }}</a>
                                </div>

                                <div class="contact-box shadow-soft">
                                    <strong>Phone</strong>
                                    @include('front-views.store_webpage.partials.phone-actions')
                                </div>

                                <div class="contact-box shadow-soft">
                                    <strong>Location</strong>
                                    <a data-bs-toggle="modal" data-bs-target="#exampleModal">View Map</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="about" class="mt-4">
                        <div class="">
                            <h3 class="sec_heading">About Store</h3>
                            <div class=" rounded p-3 my-3">
                                {!! $data['store_config']->about_us ?? '' !!}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <!-- Fruits Shop End-->
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
        document.querySelectorAll('.my-nav-link').forEach(link => {
            link.addEventListener('click', function() {
                document.querySelectorAll('.my-nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
    <script>
        document.querySelectorAll('.lightgallery').forEach(gallery => {
            lightGallery(gallery, {
                plugins: [lgVideo], // Add lgVideo plugin
                thumbnail: true,
                animateThumb: true,
                showThumbByDefault: true,
                thumbWidth: 80,
                thumbHeight: "auto",
                videojs: true // Enable video support
            });
        });

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
                btnText.innerText = "Less";
            }
        }
    </script>
    <script>
        document.querySelectorAll('.rating-stars').forEach(el => {
            const rating = parseFloat(el.getAttribute('data-rating'));
            const percentage = (Math.min(rating, 5) / 5) * 100;
            el.querySelector('.stars-fill').style.width = `${percentage}%`;
        });
        lightGallery(document.querySelector('.gallery-carousel'), {
            selector: '.lightgallery-item',
            download: false,
            thumbnail: true
        });
    </script>
@endpush
