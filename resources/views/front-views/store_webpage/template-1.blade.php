@extends('front-views.layout')

@section('title', $store['meta_title'] ?? ($data['store_config']?->webpage_name ?? $store['name']))

@section('meta_keywords', $keywords)
@section('meta_description', $store['meta_description'])


@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .lg-object.lg-image {
            height: 100% !important;
        }
 
        .lg-counter {
            background: #ffffff24 !important;
            padding: 7px !important;
            height: fit-content !important;
            border-radius: 10px;
            margin: 10px !important;
            font-size: 23px !important;
            color: white !important;
        }

        .lg-next.lg-icon,
        .lg-prev.lg-icon {
            background: #ffffffb3;
            border: 1px solid white;
            color: black;
            border-radius: 50%;
        }

        .carousel-shell-r1x {
            overflow: hidden;
            position: relative;
            width: 100%;
        }

        .carousel-line-r1x {
            display: flex;
            transition: transform 0.3s ease;
            cursor: grab;
        }

        .carousel-cell-r1x {
            flex: 0 0 auto;
            width: calc(100% / 10);
            /* Default: 10 items per view */
            padding: 10px;
            box-sizing: border-box;
            background: #eee;
            text-align: center;
            font-size: 24px;
            border-radius: 8px;
        }

        /* Tablet View (6 items) */
        @media (max-width: 1024px) {
            .carousel-cell-r1x {
                width: calc(100% / 6);
            }
        }

        /* Mobile View (3 items) */
        @media (max-width: 768px) {
            .carousel-cell-r1x {
                width: calc(100% / 3);
            }
        }



        .rating-stars {
            position: relative;
            display: inline-block;
            font-size: 15px;
            color: #ccc;
        }

        .stars-base i {
            color: #ccc;
        }

        .stars-fill {
            position: absolute;
            top: 0;
            left: 0;
            overflow: hidden;
            white-space: nowrap;
            color: #f7a103;
            width: 0;
        }


        .fruite .fruite-item {
            margin: 10px 0;
        }

        .st-btn {
            margin: 3px !important;
        }

        #map {
            height: 300px;
            width: 100%;
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

    <style>
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

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #333;
            padding: 5px;
        }

        .mobile-only-gallery {
            display: none;
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
            .my-nav {
                display: none !important;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #fff;
                flex-direction: column; 
                padding: 15px 0;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                z-index: 10;
            } 
            .my-nav.show { 
                display: flex !important;
            }
            .my-nav li {
                margin: 0 !important;
                padding: 8px 20px;
            }
            .mobile-only-gallery {
                display: list-item;
            }
            .gallery-desktop {
                display: none !important;
            }
            .store-nav {
                padding-left: 15px !important;
                padding-right: 15px !important;
                position: relative;
            }
            .spacer {
                height: 12px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="spacer"></div>

    <div class="store-hero">
        <nav style=" width: 100%;position: fixed; top: 0px; z-index: 2;"
            class="store-nav px-5 d-flex justify-content-between align-items-center  bg-white shadow-sm">
            <div class="">
                <img loading="lazy" class="store_logo" src="{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}">

            </div>
            <button class="mobile-menu-toggle" onclick="document.querySelector('.my-nav').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="my-nav mb-0 justify-content-center d-flex list-unstyled">
                <li class="mx-4">
                    <a href="#services" class="my-nav-link ">Services</a>
                </li>
                <li class="mx-4">
                    <a href="#store-ratings" class="my-nav-link">Store Ratings</a>
                </li>
                <li class="mx-4">
                    <a href="#contact" class="my-nav-link">Contact</a>
                </li>
                <li class="mx-4">
                    <a href="#about" class="my-nav-link">About</a>
                </li>
                <li class="mx-4 mobile-only-gallery">
                    <a href="{{ route('store.gallery', [$store['slug']]) }}" class="my-nav-link">Gallery</a>
                </li>
            </ul>

            <div class="d-flex gallery-desktop">
                <a href="{{ route('store.gallery', [$store['slug']]) }}" class="btn btn-primary mx-3 text-light">Gallery</a>
            </div>
        </nav>
        <nav class="px-3"
            style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='currentColor'/%3E%3C/svg%3E&#34;);"
            aria-label="breadcrumb">
            <ol class="breadcrumb d-flex align-items-center" style="font-size: 12px;">
                <li class="breadcrumb-item"><a class="fs-6 text-dark" href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    {{ $data['store_config']?->webpage_name ?? $store['name'] }}</li>
            </ol>
        </nav>
        <div class="container my-3 " id="home">

            <div class="row">
                <div class="col-md-5">
                    <img loading="lazy" width="100%" style="aspect-ratio: 2 / 1; object-fit: cover;"
                        src="{{ asset('storage/app/public/store/cover/') . '/' . $store['cover_photo'] }}" alt="cover">
                </div>
                <div class="col-md-6">
                    <div class="">
                        <h1 class="fs-4">{{ $data['store_config']?->webpage_name ?? $store['name'] }}</h1>
                        <p>{{ $store['meta_title'] }}</p>

                        @php
                            $description = $store['meta_description'];
                            $short = Str::limit($description, 100); // show first 100 characters
                        @endphp
                        <p id="text-{{ $store['id'] }}">
                            {!! $short !!}
                            @if (strlen($description) > 100)
                                <span id="dots-{{ $store['id'] }}"></span>
                                <span id="more-{{ $store['id'] }}" class="d-none">{!! substr($description, 100) !!}</span>
                                <a class="cursor-pointer btn-link p-0" onclick="toggleReadMore({{ $store['id'] }})"
                                    id="btn-{{ $store['id'] }}">Read more</a>
                            @endif
                        </p>
                        <p class="mb-0"><i class="fa fa-phone"></i>
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
                        <p class="mb-0"><i class="fa fa-envelope"></i>
                            {{ $data['store_config']?->webpage_email ?? $store->email }}</p>
                        <div class="d-flex my-3">
                            @php $store_rating = number_format($store->average_rating, 1); @endphp
                            {{ $store_rating }} &nbsp; <div class="rating-stars" data-rating="{{ $store_rating }}">
                                <div class="stars-base">
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                                <div class="stars-fill">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>

                            &nbsp; ({{ $store->rating_count }} Reviews)
                        </div>
                        <div class="share_buttons_div ">
                            <div class="sharethis-inline-share-buttons"></div>
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
                    <div class="" id="services" aria-labelledby="pills-category-tab">
                        <div class="section_spacing">

                            <div class="tab-class text-center">
                                {{-- <div class="row g-4" style=" --bs-gutter-x: 0rem !important;">
                                    <div class="col-lg-8 text-start">
                                        <ul class="nav nav-pills d-inline-flex text-center ">
                                            @foreach ($productdata as $key => $cat)
                                                <li class="nav-item">
                                                    <a class="d-flex m-2 p-2 bg-light rounded-pill {{ !$key ? 'active' : '' }}"
                                                        data-bs-toggle="pill" href="#tab--{{ $key }}">
                                                        <span class="text-dark"
                                                            style="white-space: nowrap;">{{ $cat->name }}</span>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div> --}}
                                <div class="tab-content">
                                    @foreach ($productdata as $key => $cat)
                                        <a class="d-flex m-2 p-2 bg-warning rounded-pill active"
                                            style="width:fit-content;" data-bs-toggle="pill">
                                            <span class="text-dark"
                                                style="white-space: nowrap;">{{ $cat->name }}</span>
                                        </a>
                                        <div id="tab--{{ $key }}" class="tab-pane p-0   show active">
                                            <div class="row g-0">
                                                <div class="col-lg-12">
                                                    <div class="row g-3">
                                                        @foreach ($cat->items as $pro)
                                                            @php
                                                                $variations = json_decode($pro->variations);
                                                                $firstVr = !empty($variations)
                                                                    ? json_encode($variations[0])
                                                                    : '';
                                                                if ($firstVr) {
                                                                    $selling_price = json_decode($firstVr)->price;
                                                                    $mrp =
                                                                        json_decode($firstVr)->mrpprice ??
                                                                        json_decode($firstVr)->price;
                                                                } else {
                                                                    $selling_price = $pro->price;
                                                                    $mrp = $pro->mrp_price;
                                                                }
                                                            @endphp
                                                            <div class="pr_{{ $pro->id }} col-md-4 col-lg-3 col-xl-2 col-sm-4 product_card"
                                                                style="    max-width: 264px !important;">
                                                                <div class="rounded position-relative fruite-item">

                                                                    <div class="fruite-img"
                                                                        style="height: 200px !important;">
                                                                        <a
                                                                            href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                                                            <img loading="lazy" loading="lazy"
                                                                                style="height: 200px !important;object-fit:cover"
                                                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                                                                class="img-fluid w-100 rounded-top"
                                                                                alt=""></a>
                                                                    </div>

                                                                    @if ($module == 5)
                                                                        <span
                                                                            class="badge rounded-pill bg-light text-dark time_badge"><i
                                                                                class="fas fa-fire text-secondary"></i>
                                                                            {{ strtoupper($store->delivery_time) }}
                                                                        </span>
                                                                    @endif

                                                                    @if ($pro->discount > 0)
                                                                        <div class="discount_badge">
                                                                            <span class="">
                                                                                {{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '% OFF' : \App\CentralLogics\Helpers::currency_symbol() . ' OFF' }}<span>
                                                                        </div>
                                                                    @endif

                                                                    <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                                                        class="prHeart_{{ $pro->id }}  p-1 rounded  position-absolute"
                                                                        style="top: 10px; right: 10px;cursor:pointer"><i
                                                                            class="fa fa-heart  heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }} fs-4"></i>
                                                                    </div>

                                                                    <div
                                                                        class="p-2 border border-top-0 rounded-bottom text-start">

                                                                        <a
                                                                            href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                                                            <h4 class="one-line-ellipsis text-start product_name"
                                                                                data-id="pr_{{ $pro->id }}"
                                                                                title="{{ ucfirst($pro->name) }}"
                                                                                style="font-size: 18px;">
                                                                                {{ ucfirst($pro->name) }}</h4>
                                                                        </a>
                                                                        @if ($module == 5)
                                                                            <p class="one-line-ellipsis"
                                                                                style="min-height:25px ;font-size: 12px;text-align: start; color:#616161; margin-bottom:5px;letter-spacing: 1px;">
                                                                                {{ !empty($variations) ? $variations[0]->type : '' }}
                                                                            </p>
                                                                            @if (count($variations) > 1)
                                                                                <span style="font-size: 9px;"
                                                                                    class="badge badge-pill badge-light text-dark border">+{{ count($variations) - 1 }}
                                                                                    more option(s)</span>
                                                                            @endif

                                                                            <div
                                                                                class="d-flex justify-content-between flex-lg-wrap  align-items-center">
                                                                                <div class="">
                                                                                    <p class="text-dark fs-5 fw-bold mb-0">
                                                                                        {{ _price($selling_price) }}
                                                                                    </p>

                                                                                    <p class="text-danger text-decoration-line-through mb-0 text-start"
                                                                                        style="    min-height: 24px;">
                                                                                        @if ($pro->discount > 0)
                                                                                            {{ _price($mrp) }}
                                                                                        @endif
                                                                                    </p>
                                                                                </div>

                                                                                {{-- @if (_isStoreActive($pro->id)) --}}
                                                                                <div
                                                                                    class="cart-section cartSec_{{ $pro->id }}">
                                                                                    @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp

                                                                                    @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                                                        <button
                                                                                            onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                                                            class="btn border border-secondary rounded p-1 px-2 text-primary fs-5"><i
                                                                                                class="fa fa-times me-2 text-primary"></i>Remove</button>
                                                                                    @else
                                                                                        <button
                                                                                            onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                                                            class="btn border border-secondary rounded p-1 px-2 text-primary fs-5">
                                                                                            <i
                                                                                                class="fa fa-shopping-bag me-2 text-primary"></i>
                                                                                            Add</button>
                                                                                    @endif
                                                                                </div>
                                                                                {{-- @endif --}}

                                                                            </div>
                                                                        @else
                                                                            @if ($pro->item_type == 'product')
                                                                                <div
                                                                                    class="d-flex justify-content-between flex-lg-wrap  align-items-center">
                                                                                    <div class="">
                                                                                        <p
                                                                                            class="text-dark fs-5 fw-bold mb-0">
                                                                                            {{ _price($selling_price) }}
                                                                                        </p>
                                                                                        <p class="text-danger text-decoration-line-through mb-0 text-start"
                                                                                            style="min-height: 24px;">
                                                                                            @if ($pro->discount > 0 || $mrp > $selling_price)
                                                                                                {{ _price($mrp) }}
                                                                                            @endif
                                                                                        </p>
                                                                                    </div>
                                                                                </div>
                                                                            @endif

                                                                            @if (($data['store_config']->lead_available ?? 1) == 0)
                                                                                <button disabled
                                                                                    class="btn border border-secondary rounded p-1 px-2 text-muted action__btn" title="Currently unavailable for enquiries"><i
                                                                                        class="fas fa-user-cog"></i>
                                                                                    Enquiry Now</button>
                                                                                <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                                                            @elseif (auth('web')->user())
                                                                                <button
                                                                                    onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                                                    class="btn border border-secondary rounded p-1 px-2 text-primary action__btn"><i
                                                                                        class="fas fa-user-cog"></i>
                                                                                    Enquiry Now</button>
                                                                            @else
                                                                                <button data-bs-toggle="modal"
                                                                                    data-bs-target="#loginModal"
                                                                                    class="btn border border-secondary rounded p-1 px-2 text-primary action__btn"><i
                                                                                        class="fas fa-user-cog"></i>
                                                                                    Enquiry Now</button>
                                                                            @endif
                                                                        @endif


                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    @foreach ($invItemdata as $key => $cat)
                                        <a class="d-flex m-2 p-2 bg-warning rounded-pill active"
                                            style="width:fit-content;" data-bs-toggle="pill">
                                            <span class="text-dark"
                                                style="white-space: nowrap;">{{ $cat->name }}</span>
                                        </a>
                                        <div id="tab--{{ $key }}" class="tab-pane p-0   show active">
                                            <div class="row g-0">
                                                <div class="col-lg-12">
                                                    <div class="row g-3">
                                                        @foreach ($cat->items as $pro)
                                                            @php
                                                                $variations = json_decode($pro->variations);
                                                                $firstVr = !empty($variations)
                                                                    ? json_encode($variations[0])
                                                                    : '';
                                                                if ($firstVr) {
                                                                    $selling_price = json_decode($firstVr)->price;
                                                                    $mrp =
                                                                        json_decode($firstVr)->mrpprice ??
                                                                        json_decode($firstVr)->price;
                                                                } else {
                                                                    $selling_price = $pro->price;
                                                                    $mrp = $pro->mrp_price;
                                                                }
                                                            @endphp
                                                            <div class="pr_{{ $pro->id }} col-md-4 col-lg-3 col-xl-2 col-sm-4 product_card"
                                                                style="    max-width: 264px !important;">
                                                                <div class="rounded position-relative fruite-item">

                                                                    <div class="fruite-img"
                                                                        style="height: 200px !important;">
                                                                        <a
                                                                            href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                                                            <img loading="lazy" loading="lazy"
                                                                                style="height: 200px !important;object-fit:cover"
                                                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                                                                class="img-fluid w-100 rounded-top"
                                                                                alt=""></a>
                                                                    </div>

                                                                    @if ($module == 5)
                                                                        <span
                                                                            class="badge rounded-pill bg-light text-dark time_badge"><i
                                                                                class="fas fa-fire text-secondary"></i>
                                                                            {{ strtoupper($store->delivery_time) }}
                                                                        </span>
                                                                    @endif

                                                                    @if ($pro->discount > 0)
                                                                        <div class="discount_badge">
                                                                            <span class="">
                                                                                {{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '% OFF' : \App\CentralLogics\Helpers::currency_symbol() . ' OFF' }}<span>
                                                                        </div>
                                                                    @endif

                                                                    <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                                                        class="prHeart_{{ $pro->id }}  p-1 rounded  position-absolute"
                                                                        style="top: 10px; right: 10px;cursor:pointer"><i
                                                                            class="fa fa-heart  heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }} fs-4"></i>
                                                                    </div>

                                                                    <div
                                                                        class="p-2 border border-top-0 rounded-bottom text-start">

                                                                        <a
                                                                            href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                                                            <h4 class="one-line-ellipsis text-start product_name"
                                                                                data-id="pr_{{ $pro->id }}"
                                                                                title="{{ ucfirst($pro->name) }}"
                                                                                style="font-size: 18px;">
                                                                                {{ ucfirst($pro->name) }}</h4>
                                                                        </a>
                                                                        @if ($module == 5)
                                                                            <p class="one-line-ellipsis"
                                                                                style="min-height:25px ;font-size: 12px;text-align: start; color:#616161; margin-bottom:5px;letter-spacing: 1px;">
                                                                                {{ !empty($variations) ? $variations[0]->type : '' }}
                                                                            </p>
                                                                            @if (count($variations) > 1)
                                                                                <span style="font-size: 9px;"
                                                                                    class="badge badge-pill badge-light text-dark border">+{{ count($variations) - 1 }}
                                                                                    more option(s)</span>
                                                                            @endif

                                                                            <div
                                                                                class="d-flex justify-content-between flex-lg-wrap  align-items-center">
                                                                                <div class="">
                                                                                    <p class="text-dark fs-5 fw-bold mb-0">
                                                                                        {{ _price($selling_price) }}
                                                                                    </p>

                                                                                    <p class="text-danger text-decoration-line-through mb-0 text-start"
                                                                                        style="    min-height: 24px;">
                                                                                        @if ($pro->discount > 0)
                                                                                            {{ _price($mrp) }}
                                                                                        @endif
                                                                                    </p>
                                                                                </div>

                                                                                {{-- @if (_isStoreActive($pro->id)) --}}
                                                                                <div
                                                                                    class="cart-section cartSec_{{ $pro->id }}">
                                                                                    @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp

                                                                                    @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                                                        <button
                                                                                            onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                                                            class="btn border border-secondary rounded p-1 px-2 text-primary fs-5"><i
                                                                                                class="fa fa-times me-2 text-primary"></i>Remove</button>
                                                                                    @else
                                                                                        <button
                                                                                            onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                                                            class="btn border border-secondary rounded p-1 px-2 text-primary fs-5">
                                                                                            <i
                                                                                                class="fa fa-shopping-bag me-2 text-primary"></i>
                                                                                            Add</button>
                                                                                    @endif
                                                                                </div>
                                                                                {{-- @endif --}}

                                                                            </div>
                                                                        @else
                                                                            @if ($pro->item_type == 'product')
                                                                                <div
                                                                                    class="d-flex justify-content-between flex-lg-wrap  align-items-center">
                                                                                    <div class="">
                                                                                        <p
                                                                                            class="text-dark fs-5 fw-bold mb-0">
                                                                                            {{ _price($selling_price) }}
                                                                                        </p>
                                                                                        <p class="text-danger text-decoration-line-through mb-0 text-start"
                                                                                            style="min-height: 24px;">
                                                                                            @if ($pro->discount > 0 || $mrp > $selling_price)
                                                                                                {{ _price($mrp) }}
                                                                                            @endif
                                                                                        </p>
                                                                                    </div>
                                                                                </div>
                                                                            @endif

                                                                            @if (($data['store_config']->lead_available ?? 1) == 0)
                                                                                <button disabled
                                                                                    class="btn border border-secondary rounded p-1 px-2 text-muted action__btn" title="Currently unavailable for enquiries"><i
                                                                                        class="fas fa-user-cog"></i>
                                                                                    Enquiry Now</button>
                                                                                <small class="text-muted d-block" style="font-size:11px;">Not accepting enquiries currently</small>
                                                                            @elseif (auth('web')->user())
                                                                                <button
                                                                                    onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                                                    class="btn border border-secondary rounded p-1 px-2 text-primary action__btn"><i
                                                                                        class="fas fa-user-cog"></i>
                                                                                    Enquiry Now</button>
                                                                            @else
                                                                                <button data-bs-toggle="modal"
                                                                                    data-bs-target="#loginModal"
                                                                                    class="btn border border-secondary rounded p-1 px-2 text-primary action__btn"><i
                                                                                        class="fas fa-user-cog"></i>
                                                                                    Enquiry Now</button>
                                                                            @endif
                                                                        @endif


                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    @if (!count($productdata))
                                        No Products found ...
                                    @endif
                                </div>
                            </div>
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
                                            src="{{ asset('storage/app/public/store/gallery/') . '/' . $value['image'] }}"
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
                                                <img loading="lazy" src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/') . '/' . $rev->profile_image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
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
                                                            @php $attachments = json_decode($rev->attachment); @endphp
                                                            @if (!empty($attachments))
                                                                <div class="d-flex">
                                                                    @foreach ($attachments as $img)
                                                                        <a target="_blank" class="mx-1"
                                                                            href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"><img loading="lazy"
                                                                                class="rounded" style="width: 55px;"
                                                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                                                alt="review"></a>
                                                                        <a target="_blank"
                                                                            href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"><img loading="lazy"
                                                                                class="rounded" style="width: 55px;"
                                                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                                                alt="review"></a>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        @endif
                                                    </div>
                                                    @if ($rev->reply)
                                                        <div class="d-flex border rounded  p-2">
                                                            <img loading="lazy" src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store['logo'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
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
                    <div class="" id="contact" aria-labelledby="pills-contact-tab">
                        <div class="section_spacing">
                            <h3 class="sec_heading">Contact</h3>

                            <div class="contact-container">
                                <!-- Address -->
                                <div class="contact-box ">
                                    <div class="icon-circle">
                                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/map.png" alt="Address Icon" />
                                    </div>
                                    <div class="contact-title">Address:</div>
                                    <div class="contact-text" style="font-size: 9px;">
                                        {{ $store['address'] }}
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="contact-box ">
                                    <div class="icon-circle">
                                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/secured-letter.png"
                                            alt="Email Icon" />
                                    </div>
                                    <div class="contact-title">Email:</div>
                                    <div class="contact-text" style="font-size: 9px;">
                                        <a class="text-break"
                                            href="mailto:{{ $store['email'] }}">{{ $store['email'] }}</a>
                                    </div>
                                </div>
                                <!-- Email -->
                                <div class="contact-box ">
                                    <div class="icon-circle">
                                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/phone.png" alt="Email Icon" />
                                    </div>
                                    <div class="contact-title">Phone:</div>
                                    <div class="contact-text">
                                        <a href="tel:{{ $store['phone'] }}">{{ $store['phone'] }}</a>
                                    </div>
                                </div>
                                <!-- Email -->
                                <div class="contact-box ">
                                    <div class="icon-circle">
                                        <img loading="lazy" src="https://img.icons8.com/ios-filled/50/marker.png" alt="Email Icon" />
                                    </div>
                                    <div class="contact-title">Location:</div>
                                    <div class="contact-text">
                                        <a type="button" class="cursor-pointer" data-bs-toggle="modal"
                                            data-bs-target="#exampleModal">
                                            <i class="fas fa-map-marker-alt"></i>
                                            Location</a>
                                    </div>
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
