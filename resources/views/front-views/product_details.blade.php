@extends('front-views.layout')
@php
    $service_name = ucwords($item->name);
    $city_name = session('customer_city') ?? 'Tirupati';
    $same_category_services = '';

    $title = str_replace(
        ['{SERVICE_NAME}', '{CITY_NAME}', '{LOCALITIES}'],
        [$service_name, $city_name, $item_area_keywords ?? ''],
        $item->meta_title,
    );
    $desc = str_replace(
        ['{SERVICE_NAME}', '{CITY_NAME}', '{LOCALITIES}'],
        [$service_name, $city_name, $item_area_keywords ?? ''],
        $item->meta_desc,
    );
@endphp

@section('title', $title ?? $item->name)

@section('meta_keywords', $keywords)
@section('meta_description', $desc ?? $item->description)

@push('meta_tags')
    <meta property="og:title" content="{{ $item->name }}">
    <meta property="og:description" content="{{ $item->description }}">
    <meta property="og:image" content="{{ asset('storage/app/public/product/') . '/' . $item->image }}">
    <meta property="og:url" content="{{ asset('storage/app/public/product/') . '/' . $item->image }}">
    <meta property="og:type" content="service">
@endpush
@push('css_or_js')
    <script type="text/javascript"
        src="https://platform-api.sharethis.com/js/sharethis.js#property=673458a1da72090013b84006&product=inline-share-buttons&source=platform"
        async="async"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
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

        h2,
        h2>* {
            font-size: 24px !important;
            font-weight: 600 !important;
        }

        .rating-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stars {
            position: relative;
            display: inline-flex;
            font-size: 20px;
            gap: 2px;
        }

        .stars-outer {
            position: relative;
            display: inline-flex;
            gap: 2px;
        }

        .stars-outer::before {
            content: "★★★★★";
            color: #ddd;
            letter-spacing: 2px;
        }

        .stars-inner {
            position: absolute;
            top: 0;
            left: 0;
            white-space: nowrap;
            overflow: hidden;
            color: #ffd700;
        }

        .stars-inner::before {
            content: "★★★★★";
            letter-spacing: 2px;
        }

        .rating-value {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        .owl-carousel .owl-item img {
            border-radius: 10px !important;
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

        .owl-dots {
            z-index: 1;
            position: relative;
            padding: 10px;
            display: flex !important;
            justify-content: center;
            {{-- margin-top: -34px; --}}
        }

        .owl-dot.active {
            background-color: white !important;
        }

        .related-carousel {
            z-index: 0 !important;
        }

        .inactive_overlay {
            content: "Not Available ";
            position: absolute;
            height: 100%;
            width: 100%;
            opacity: 0.85;
            z-index: 1;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .vesitable-img img {
            object-fit: cover;
        }

        .variation-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .variation {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            {{-- width: 120px; --}} padding: 10px;
            border: 2px solid #ccc;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
        }

        input[type="radio"] {
            display: none;
        }

        input[type="radio"]:checked+label {
            border-color: #81c408;
            box-shadow: 0 0 5px rgba(130, 196, 8, 0.72);
        }

        .price {
            font-size: 16px;
            font-weight: bold;
        }

        .old-price {
            text-decoration: line-through;
            color: gray;
            font-size: 14px;
        }

        .random-container {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .random-image {
            flex: 1;
            max-width: 300px;
            height: 100%;
        }

        .random-image img {
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        .random-description {
            flex: 2;
            max-height: 100%;
            overflow: hidden;
            position: relative;
        }

        .random-description-content {
            display: block;
            max-height: 300px;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
        }

        .random-show-more-btn {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            background: linear-gradient(to top, white, rgb(255 255 255 / 72%));
            cursor: pointer;
            color: #95D03A;
            padding: 10px 0;
            width: 247px;
            margin: 0 auto;
        }

        .expanded .random-description-content {
            max-height: none;
        }

        .expanded .random-show-more-btn {
            background: none;
            padding: 3px 0;
        }

        .address_elem {
            {{-- white-space: nowrap; --}} overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            max-width: 300px;
            /* Adjust width as needed */
        }

        .st-btn {
            margin: 3px !important;
        }

        .share_buttons_div {
            /* position: absolute; */
            /* top: 10px; */
            /* right: 10px; */
        }

        .table>:not(caption)>*>* {
            border-bottom-width: 0px !important;
        }

        .specs table td {
            padding: 4px 30px 4px 0px !important;
        }

        .banner-carousel22 .owl-nav .owl-next,
        .banner-carousel22 .owl-nav .owl-prev {
            position: absolute;
            bottom: -38px;
            right: 0px;
            color: var(--bs-primary);
            padding: 5px 25px;
            border: 1px solid var(--bs-secondary);
            border-radius: 20px;
            transition: 0.5s;
        }

        .banner-carousel22 .owl-nav .owl-prev {
            right: 88px;
        }

        .details_page_cont {
            margin: 50px 0 0 0;
        }

        @media only screen and (max-width: 768px) {
            .details_page_cont {
                margin-top: 30px;
            }
        }

        .page_loader {
            height: 100vh;
            width: 100%;
            background: #1f1f1f;
            opacity: 0.8;
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            z-index: 8;
        }


        .back-to-top {
            display: none !important;

        }

        /* read more section */
        .rm-description-wrapper {
            position: relative;
        }

        .rm-content-container {
            position: relative;
            overflow: hidden;
            transition: max-height 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .rm-content-collapsed {
            max-height: 200px;
        }

        .rm-content-expanded {
            max-height: none;
        }

        .rm-fade-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(transparent, white);
            pointer-events: none;
            opacity: 1;
            transition: opacity 0.4s ease;
            z-index: 1;
        }

        .rm-fade-hidden {
            opacity: 0;
        }

        .rm-btn-custom {
            background: #81c40869;
            border: none;
            font-weight: 500;
            color: black;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            position: relative;
            z-index: 2;
            width: fit-content;
        }

        .rm-btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .rm-btn-custom:active {
            transform: translateY(0);
        }

        .rm-chevron-icon {
            width: 16px;
            height: 16px;
            transition: transform 0.3s ease;
            margin-left: 8px;
        }

        .rm-chevron-rotated {
            transform: rotate(180deg);
        }

        .rm-highlight-title {
            color: #2d3748;
            font-weight: 600;
            position: relative;
            {{-- padding-left: 15px; --}}
        }

        .one-line-ellipsis {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
            -webkit-line-clamp: 1;
            text-overflow: ellipsis;
        }

        .two-line-ellipsis {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
            -webkit-line-clamp: 2;
            text-overflow: ellipsis;
        }

        @media (max-width: 768px) {
            .rm-content-collapsed {
                max-height: 150px;
            }

            .rm-fade-overlay {
                height: 40px;
            }
        }
    </style>
@endpush

@section('content')

    <!-- <div class="spacer" style="height: 153px;"></div> -->
    {{-- style="display:none;" --}}

    <div class="page_loader" style="display:none;">
        <img loading="lazy" style="width: 300px;filter: hue-rotate(300deg);"
            src="{{ asset('storage/app/public/util/charlie-loader.gif') }}">
    </div>

    <!-- Single Product Start -->
    <div class="  mt-5">
        <div class="container ">
            <div class="row g-4 mb-5">
                <div class="col-12">
                    @if (count($data['banners']))
                        <div class="owl-carousel banner-carousel " style="  margin: 10px auto;">
                            @foreach ($data['banners'] as $key => $value)
                                @if ($value['link'])
                                    <a href="{{ $value['link'] }}">
                                        <img loading="lazy" loading="lazy"
                                            src="{{ asset('storage/app/public/banner/') . '/' . $value['image'] }}"
                                            alt="{{ $value['title'] }}">
                                    </a>
                                    {{--  --}}
                                @else
                                    <img loading="lazy" loading="lazy"
                                        src="{{ asset('storage/app/public/banner/') . '/' . $value['image'] }}"
                                        alt="{{ $value['title'] }}">
                                    {{--  --}}
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="{{ $is_inventory_product ? 'col-12' : 'col-12' }} row  mt-5">
                    {{-- banner --}}

                    <div class="<?= $module == 5 ? 'col-md-6' : 'col-md-4' ?>">
                        <div class=" rounded mb-3" style="position: sticky;top: 130px;z-index:2;">
                            <div class="owl-carousel banner-carousel22 justify-content-center">
                                <img loading="lazy" src="{{ asset('storage/app/public/product/') . '/' . $item->image }}"
                                    {{-- --}}
                                    style="<?= $module == 5 ? 'height: 600px;' : 'height: 300px;' ?>object-fit:contain ;width=100% ; {{ !$item->status || (isset($item->suspended) && $item->suspended) ? 'filter:grayscale(1);' : '' }}"
                                    alt="{{ $item->name }}">

                                @if ($item->images)
                                    @foreach (json_decode($item->images, true) as $key => $value)
                                        <img loading="lazy" src="{{ asset('storage/app/public/product/') . '/' . $value }}"
                                            style="<?= $module == 5 ? 'height: 600px;' : 'height: 300px;' ?>object-fit:contain ;width=100% ;  {{ !$item->status || (isset($item->suspended) && $item->suspended) ? 'filter:grayscale(1);' : '' }}"
                                            class="rounded" alt="{{ $item->name }}">
                                    @endforeach
                                @endif
                            </div>

                            @php $variations = json_decode($item->variations); @endphp

                            @if (isset($item->suspended) && $item->suspended)
                                Not Available
                            @else
                                @if ($item->status)
                                    @if ($module == 5)
                                        @if ($item->store_open == 1)
                                            @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                            <br>
                                            <input type="hidden" value="{{ !empty($variations) ? 0 : '' }}"
                                                id="selected_variation">
                                            {{-- <a onclick="updateCartVr({{ $item->id }}, 'add',  '')"
                                                    class="btn border border-secondary rounded p-1 px-2 text-primary"><i
                                                        class="fa fa-shopping-bag me-2 text-primary"></i>
                                                    Add to cart</a> --}}


                                            <a onclick="updateCartVr({{ $item->id }}, 'add',  '')"
                                                class="btn btn-primary border-3 border-primary rounded sticky_action_btn text-light btn-lg"><i
                                                    class="fa fa-shopping-bag me-2 text-light"></i>
                                                Add to cart</a>
                                        @else
                                            <h6 class="text-danger">Store Closed</h6>
                                        @endif
                                    @endif
                                @else
                                    Not Available
                                @endif
                            @endif
                        </div>
                        <p class="text-center">
                            @php
                                $content = str_replace(
                                    ['{SERVICE_NAME}', '{CITY_NAME}', '{LOCALITIES}'],
                                    [$service_name, $city_name, $item_area_keywords ?? ''],
                                    $item->short_desc,
                                );
                            @endphp
                            {{ $content }}</p>
                    </div>
                    <div class="<?= $module == 5 ? 'col-md-6' : 'col-md-4' ?> position-relative" style="z-index:2;">
                        <div onclick="wishlist({{ $item->id }}, '{{ _itemExistInWishlist($item->id) ? 'remove' : 'add' }}')"
                            class="prHeart_{{ $item->id }}  p-1 rounded  position-absolute wishlist_btn"
                            style="top: 10px; right: 10px;cursor:pointer"><i
                                class="fa fa-heart  heart_{{ $item->id }} {{ _itemExistInWishlist($item->id) ? 'text_red' : 'text_grey' }} fs-4"></i>
                        </div>
                        @if (!$item->status || (isset($item->suspended) && $item->suspended))
                            <div class="inactive_overlay">
                                <h3>Not Available </h3>
                            </div>
                        @endif
                        <div style="position: sticky;top: 130px;">
                            <h1 style="font-size: 26px;" class="fw-bold mb-2">
                                @if ($item->seo_heading)
                                    @php
                                        $content = str_replace(
                                            ['{SERVICE_NAME}', '{CITY_NAME}', '{LOCALITIES}'],
                                            [$service_name, $city_name, $item_area_keywords ?? ''],
                                            $item->seo_heading,
                                        );
                                    @endphp
                                    {{ $content }}
                                @else
                                    {{ $item->name }}
                                @endif
                            </h1>

                            @php $variations = json_decode($item->variations) ?? []; @endphp
                            @if ($module == 5 || $is_inventory_product)
                                @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp
                                <div class="d-flex align-items-end">
                                    <p class="text-dark fs-5 fw-bold mb-0 mx-1">
                                        {{ \App\CentralLogics\Helpers::currency_symbol() }}
                                        <span id="actual_price">
                                            {{ $firstVr ? round(json_decode($firstVr)->price) : round($item->price) }}
                                        </span>
                                    </p>
                                    @if ($item->discount)
                                        <span class="text-danger text-decoration-line-through mb-0 mr_price">
                                            {{ \App\CentralLogics\Helpers::currency_symbol() }}
                                            {{ $firstVr ? (isset(json_decode($firstVr)->mrpprice) ? number_format(json_decode($firstVr)->mrpprice) : number_format(round(json_decode($firstVr)->price))) : ($item->mrp_price ? number_format(round($item->mrp_price)) : round($item->price)) }}</span>
                                    @endif

                                </div>

                                <p style="font-size: 11px;">Inclusive of all taxes</p>
                                <div class="d-flex mb-4">

                                    <span class="badge bg-success"><i class="fa fa-star text-secondary"></i>
                                        {{ number_format($item->avg_rating, 1) }} </span>

                                    &nbsp; ({{ $item->rating_count }} Ratings)
                                </div>
                                <div class="variation-container">
                                    @foreach ($variations as $key => $vr)
                                        {{-- @php print_r($vr); @endphp  --}}
                                        <input type="radio" id="meter{{ $key }}" data-id= "{{ $key }}"
                                            data-value="{{ $vr->type }}" class="vrtion_select"
                                            {{ !$key ? 'checked' : '' }} name="options-outlined">
                                        <label class="variation" for="meter{{ $key }}">
                                            <span>{{ preg_replace('/([a-z])([A-Z])/', '$1 $2', $vr->type) }}</span>
                                            <span class="price">₹{{ round($vr->price) }}
                                                @if ($item->discount)
                                                    <span class="text-danger text-decoration-line-through mb-0 ">
                                                        {{ isset($vr->mrpprice) ? _price($vr->mrpprice) : _price($vr->price) }}</span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="random-description">
                                    <div class="random-description ">
                                        <div class="random-description-content mb-3 rm-content-container rm-content-collapsed"
                                            id="descriptionContent">
                                            <h5 class="mt-3 rm-highlight-title">Highlights</h5>
                                            <p class="mb-4 description_elem text-muted lh-lg">
                                                <!-- Your blade code:  -->
                                                {{ $item->description }}
                                            </p>

                                            <h5 class="mt-2 rm-highlight-title">Specifications</h5>
                                            <div class="specs specs_elem text-muted">
                                                <!-- Your blade code:  -->
                                                {!! $item->specifications !!}
                                            </div>
                                        </div>

                                        <div class="rm-fade-overlay" id="fadeOverlay"></div>

                                        <div class="random-show-more-btn btn rm-btn-custom px-4 py-2 mt-1 mb-2"
                                            onclick="">
                                            <span id="buttonText">Read More</span>
                                            <svg class="rm-chevron-icon" id="chevron" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                @if ($module == 5)
                                    Seller: <a href="{{ route('store.details', [_selectedCity(), $item->store_slug]) }}"
                                        class="mb-1">{{ $item->store_name }}</a>
                                @endif
                            @else
                                @if (auth('web')->user())
                                    {{-- <a onclick="bookService({{ $item->id }}, this)"
                                            class="btn btn-lg btn-primary rounded text-light px-3 my-3"><i
                                                class="fas fa-user-cog"></i>
                                            Enquiry Now</a> --}}

                                    <a onclick="bookService({{ $item->id }}, this)"
                                        class="btn btn-primary border-3 border-primary rounded sticky_action_btn text-light btn-lg"><i
                                            class="fas fa-user-cog"></i> Enquiry
                                        Now</a>
                                @else
                                    <a data-bs-toggle="modal" data-bs-target="#loginModal"
                                        class="btn btn-primary border-3 border-primary rounded sticky_action_btn text-light btn-lg"><i
                                            class="fas fa-user-cog"></i> Enquiry
                                        Now</a>

                                    <a data-bs-toggle="modal" data-bs-target="#loginModal"
                                        class="btn btn-lg btn-primary rounded text-light  px-3 my-3"><i
                                            class="fas fa-user-cog"></i>
                                        Enquiry Now</a>
                                @endif
                            @endif

                            <div class="share_buttons_div ">
                                Know someone who might need this? Share with friends and family!

                                <div class="sharethis-inline-share-buttons"></div>
                            </div>
                        </div>

                    </div>
                    <div class="col-md-4 mt-5">
                        @if ($module == 6 && !$is_inventory_product)
                            <div class="">
                                @if (count($stores))
                                    <h2 style="    font-size: 16px;font-family: revert;">Available {{ $item->name }}
                                        Providers <span class="location_name_show"></span> ({{ count($stores) }})</h2>

                                    @foreach ($stores as $store)
                                        <a href="{{ route('store.details', [_selectedCity(), $store->slug]) }}"
                                            class="d-flex gap-2 position-relative align-items-center justify-content-start my-2 p-2 shadow-sm rounded">
                                            <div class=" mx-2">
                                                <img loading="lazy"
                                                    style="height: 75px; width: 75px; object-fit:cover; font-size: 9px;"
                                                    src="{{ asset('storage/app/public/store/') . '/' . $store->logo }}"
                                                    class="border rounded" alt="{{ $store->name }}">
                                            </div>
                                            <div style=" width: 65%;">

                                                <h6 class="mb-2 d-flex align-items-center">
                                                    <span class="two-line-ellipsis">{{ ucfirst($store->name) }}</span>
                                                    {!! _verifiedStoreBadge($store) !!}
                                                </h6>
                                                <p class="address_elem text-dark one-line-ellipsis mb-1"
                                                    style="font-size: 12px;"><i class="fas fa-map-marker-alt"></i>
                                                    {{ $store->address }}</p>
                                                @if ($store->rating_count)
                                                    <div class="rating-container">
                                                        <div class="stars-outer">
                                                            <div class="stars-inner"
                                                                style="width: {{ ($store->average_rating / 5) * 100 }}%">
                                                            </div>
                                                        </div>
                                                        <span
                                                            class="rating-value">{{ number_format($store->average_rating, 1) }}
                                                            ({{ $store->rating_count }} Reviews)
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </a>
                                    @endforeach
                                @else
                                    <div class="h-100 flex-column justify-content-center d-flex align-items-center">
                                        <img loading="lazy" style=" width: 200px; object-fit:cover;"
                                            src="{{ asset('storage/app/public/util/undraw_file-search_cbur (1).png') }}"
                                            class=" rounded" alt="Image">

                                        No providers available yet. Please visit again later.
                                    </div>
                                @endif
                            </div>

                        @endif
                    </div>
                </div>


                <div class="col-lg-8 col-xl-12 details_page_cont ">

                    <div class="row g-4">
                        @if ($module == 5)
                            <div class="col-lg-12">
                                <nav>
                                    <div class="nav nav-tabs mb-3">
                                        <!-- <button class="nav-link active border-white border-bottom-0" type="button" role="tab" id="nav-about-tab" data-bs-toggle="tab" data-bs-target="#nav-about" aria-controls="nav-about" aria-selected="true">Description</button> -->
                                        <button class="nav-link border-white border-bottom-0" type="button"
                                            role="tab" id="nav-mission-tab" data-bs-toggle="tab"
                                            data-bs-target="#nav-mission" aria-controls="nav-mission"
                                            aria-selected="false">Reviews</button>
                                    </div>
                                </nav>
                                <div class="tab-content mb-5">
                                    <div class="tab-pane " id="nav-about" role="tabpanel"
                                        aria-labelledby="nav-about-tab">


                                    </div>
                                    <div class="tab-pane active" id="nav-mission" role="tabpanel"
                                        aria-labelledby="nav-mission-tab">
                                        @foreach ($data['reviews'] as $rev)
                                            <div class="d-flex">
                                                <img loading="lazy"
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/') . '/' . $rev->profile_image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                                    class="img-fluid rounded-circle p-3"
                                                    style="width: 100px; height: 100px;" alt="user">
                                                <div class="">
                                                    <p class="mb-2" style="font-size: 14px;">
                                                        {{ _formatted_datetime($rev->created_at) }}</p>
                                                    <div class="d-flex justify-content-between">
                                                        <h5>{{ $rev->f_name . ' ' . $rev->l_name }}</h5>
                                                        <div class="d-flex mb-3">
                                                            @for ($i = 1; $i < 6; $i++)
                                                                <i
                                                                    class="fa fa-star {{ $rev->rating >= $i ? 'text-secondary' : '' }}"></i>
                                                            @endfor
                                                        </div>
                                                    </div>
                                                    <p class="text-dark">{{ $rev->comment }}</p>
                                                    @if ($rev->attachment)
                                                        @php $attachments = json_decode($rev->attachment); @endphp
                                                        @if (!empty($attachments))
                                                            @foreach ($attachments as $img)
                                                                <a target="_blank"
                                                                    href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"><img
                                                                        loading="lazy" class="rounded"
                                                                        style="width: 75px;"
                                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                                        alt="review image"></a>
                                                            @endforeach
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                        @if (!count($data['reviews']))
                                            No Reviews yet...
                                        @endif

                                    </div>

                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            <h2 class="mt-5">Related {{ $module == 5 ? 'Products' : 'Services' }}</h2>
            <div class="">
                <div class="owl-carousel related-carousel justify-content-center">
                    @foreach ($data['related_products'] as $pro)
                        @php
                            $variations = json_decode($pro->variations) ?? [];
                            $firstVr = !empty($variations) ? json_encode($variations[0]) : '';
                            if ($firstVr) {
                                $selling_price = json_decode($firstVr)->price;
                                $mrp = json_decode($firstVr)->mrpprice ?? json_decode($firstVr)->price;
                            } else {
                                $selling_price = $pro->price;
                                $mrp = $pro->mrp_price;
                            }
                        @endphp

                        <div class="">
                            <div class="rounded position-relative fruite-item">

                                <div class="fruite-img" style="height: 200px !important;">
                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}"> <img
                                            loading="lazy" loading="lazy"
                                            style="height: 200px !important;object-fit:cover"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                            class="img-fluid w-100 rounded-top" alt=""></a>
                                </div>

                                @if ($module == 5)
                                    <span class="badge rounded-pill bg-light text-dark time_badge"><i
                                            class="fas fa-fire text-secondary"></i>
                                        {{ strtoupper($pro->delivery_time) }}
                                    </span>
                                @endif

                                @if ($pro->discount > 0)
                                    <div class=" discount_badge">
                                        <span class="">
                                            {{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '% OFF' : \App\CentralLogics\Helpers::currency_symbol() . ' OFF' }}<span>
                                    </div>
                                @endif

                                <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                    class="prHeart_{{ $pro->id }}  p-1 rounded  position-absolute"
                                    style="top: 10px; right: 10px;cursor:pointer"><i
                                        class="fa fa-heart  heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }} fs-4"></i>
                                </div>

                                <div class="p-2 border border-top-0 rounded-bottom">

                                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                        <h4 class="two-line-ellipsis text-start" title="{{ ucfirst($pro->name) }}"
                                            style="    height: 57px;font-size: 18px;">
                                            {{ ucfirst($pro->name) }}</h4>
                                    </a>

                                    @if ($module == 5)
                                        <p class="one-line-ellipsis"
                                            style="min-height:25px ;font-size: 15px;text-align: start; color:#000000; margin-bottom:5px;letter-spacing: 1px;">
                                            {{ !empty($variations) ? $variations[0]->type : '' }}
                                        </p>

                                        <div class="d-flex justify-content-between flex-lg-wrap  align-items-center">
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
                                            <div class="cart-section cartSec_{{ $pro->id }}">
                                                @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp

                                                @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                    <button
                                                        onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                        class="btn border border-secondary rounded p-1 px-2 text-primary fs-5"><i
                                                            class="fa fa-times me-2 text-light"></i>Remove</button>
                                                @else
                                                    <button
                                                        onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                        class="btn border border-secondary rounded p-1 px-2 text-primary fs-5">
                                                        <i class="fa fa-shopping-bag me-2 text-light"></i>
                                                        Add</button>
                                                @endif
                                            </div>
                                            {{-- @endif --}}

                                        </div>
                                    @else
                                        @if (auth('web')->user())
                                            <button onclick="bookService({{ $pro->id }}, this)"
                                                class="btn border border-secondary rounded p-1 px-2 text-primary"><i
                                                    class="fas fa-user-cog"></i>
                                                Enquiry Now</button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="btn border border-secondary rounded p-1 px-2 text-primary"><i
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
            <h2 class="my-3">Featured {{ $module == 5 ? 'Products' : 'Services' }}</h2>
            <div class="owl-carousel featured-carousel justify-content-center">
                @foreach ($data['featured_products'] as $fPro)
                    <div class="d-flex border rounded align-items-center justify-content-start my-1 p-1">
                        <div class="rounded border  " style="">
                            <img loading="lazy" style="width: 80px; height:80px; object-fit:cover;"
                                src="{{ asset('storage/app/public/product/') . '/' . $fPro->image }}" class="rounded"
                                alt="Image">
                        </div>
                        <div class="mx-2">
                            <h6 class="mb-2 two-line-ellipsis"><a
                                    href="{{ route('product.details', [_selectedCity(), $fPro->slug]) }}">{{ ucfirst($fPro->name) }}</a>
                            </h6>
                            @if ($module == 5)
                                <div class="d-flex mb-2">
                                    @for ($i = 1; $i < 6; $i++)
                                        @if ($fPro->avg_rating >= $i)
                                            <i class="fa fa-star text-secondary"></i>
                                        @else
                                            <i class="fa fa-star"></i>
                                        @endif
                                    @endfor
                                </div>
                                <div class="d-flex mb-2">
                                    @php
                                        $variations = json_decode($fPro->variations);
                                        $firstVr = !empty($variations) ? json_encode($variations[0]) : '';
                                        if ($firstVr) {
                                            $selling_price = json_decode($firstVr)->price;
                                            $mrp = json_decode($firstVr)->mrpprice ?? json_decode($firstVr)->price;
                                        } else {
                                            $selling_price = $fPro->price;
                                            $mrp = $fPro->mrp_price;
                                        }
                                    @endphp

                                    <h5 class="fw-bold me-2">
                                        {{ \App\CentralLogics\Helpers::currency_symbol() . number_format($selling_price) }}
                                    </h5>
                                    @if ($mrp)
                                        <h5 class="text-danger text-decoration-line-through">
                                            {{ \App\CentralLogics\Helpers::currency_symbol() . number_format($mrp) }}</h5>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </div>
    <style>
        .area_seo {
            background-color: white !important;
            padding: 0 23px;
        }

        .area_seo>* {
            color: black !important;
                overflow-wrap: break-word;
        }

        .stores_table th,
        .stores_table td {
            border: 1px solid #dedede;
            width: 138px !important;
        }

        .stores_table>* {
            font-size: 12px;
        }
        .table-scroll {
    width: 100%;
    overflow-x: auto;        /* horizontal scroll */
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
}

.stores_table {
    width: max-content;      /* table expands only as needed */
    min-width: 100%;         /* but never smaller than screen */
    border-collapse: collapse;
}
    </style>
    <!-- Single Product End -->
    <div class="area_seo">


        {{-- FOOTER SEO CONTENT ============================ --}}
        @if (!empty($data['seoContent']) && !empty($data['seoContent']->content))
            @php
                $content = $data['seoContent']->content;
                $content = str_replace(
                    ['{SERVICE_NAME}', '{CITY_NAME}', '{LOCALITIES}', '{SAME_CATEGORY_SERVICES}'],
                    [$service_name, $city_name, $item_area_keywords ?? '', $same_category_services],
                    $content,
                );
            @endphp

            {!! $content !!}
        @endif
        {{-- REVIEWS =================================== --}}
        <div class="col-6">
            <h2>Customer Reviews</h2>
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
                                @php $attachments = json_decode($rev->attachment); @endphp
                                @if (!empty($attachments))
                                    <div class="d-flex">
                                        @foreach ($attachments as $img)
                                            <a target="_blank" class="mx-1"
                                                href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"><img
                                                    loading="lazy" class="rounded" style="width: 55px;"
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                    alt="review"></a>
                                            <a target="_blank"
                                                href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"><img
                                                    loading="lazy" class="rounded" style="width: 55px;"
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
                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->store_logo, asset('storage/app/public/store/') . '/' . $rev->store_logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                    class="img-fluid rounded m-2 reply_img" style=""
                                    alt="{{ $rev->store_name }}">
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
        </div>

        {{-- FAQs ================================== --}}
        <h5>Frequently Asked Questions</h5>
        @if (!empty($data['faqContent']) && !empty($data['faqContent']->content))
            @php
                $content = $data['faqContent']->content;

                $content = str_replace(
                    ['{SERVICE_NAME}', '{CITY_NAME}', '{LOCALITIES}', '{SAME_CATEGORY_SERVICES}'],
                    [$service_name, $city_name, $item_area_keywords ?? '', $same_category_services],
                    $content,
                );
            @endphp

            {!! $content !!}
        @endif

        {{-- SEO KEYWORDS ========================== --}}
        @if ($item_area_keywords_arr->isNotEmpty() && !empty(trim($keywords)))
            <h3 style="    font-size: 16px;">{{ $item->name }} in {{ $city_name }}</h3>

            @php
                $common_keywords = array_filter(array_map('trim', explode(',', $keywords)));
                $location_keywords = $item_area_keywords_arr;
            @endphp

            @foreach (array_slice($common_keywords, 0, 5) as $common_keyword)
                @foreach ($location_keywords->take(5) as $loc_keyword)
                    <span>
                        <a class="text-dark" style="font-size:10px;" href="">
                            {{ $common_keyword }} in {{ $loc_keyword }}
                        </a>
                    </span> |
                @endforeach
            @endforeach
        @endif

    </div>

    <div class="top_stores_section">
    <h2 class="mx-3">Top Rated Stores</h2>
    <div class="table-scroll">
        <table class="table table-responsive stores_table mx-3">
            <tr>
                <th style="width: 98px !important;">Name</th>
                @foreach ($data['top_stores'] as $store)
                    <th><a href="{{ route('store.details', [_selectedCity(), $store->slug]) }}">{{ $store->name }}</a>
                    </th>
                @endforeach
            </tr>
            <tr>
                <th style="width: 98px !important;">Rating</th>

                @foreach ($data['top_stores'] as $store)
                    <td>
                        @php $store_rating = number_format($store->average_rating, 1); @endphp
                        <div class="rating-stars" data-rating="{{ $store_rating }}">
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
                        </div>&nbsp;{{ $store_rating }} <br>({{$store->rating_count}} reviews)
                    </td>
                @endforeach
            </tr>
            <tr>
                <th style="width: 98px !important;">Offered Services</th>
                @foreach ($data['top_stores'] as $store)
                    <td>
                        @if (!empty($store->item_names_array))
                            @foreach ($store->item_names_array as $i => $name)
                                <a href="{{ route('product.details', [_selectedCity(), $store->item_slugs_array[$i] ?? '']) }}"
                                    target="_blank">
                                    {{ $name }}
                                </a>
                                @if ($i < count($store->item_names_array) - 1)
                                    ,
                                @endif
                            @endforeach
                        @else
                            -
                        @endif
                    </td>
                @endforeach
            </tr>
        
            
           
             <tr>
                <th style="width: 98px !important;">Address</th>
                @foreach ($data['top_stores'] as $store)
                    <td><span class="two-line-ellipsis">{{ $store->address }}</span></td>
                @endforeach
            </tr>
        </table>
    </div>
    </div>
@endsection

@push('script_2')
    <script>
        document.querySelectorAll('.rating-stars').forEach(el => {
            const rating = parseFloat(el.getAttribute('data-rating'));
            const percentage = (Math.min(rating, 5) / 5) * 100;
            el.querySelector('.stars-fill').style.width = `${percentage}%`;
        });

        function toggleDescription() {
            const description = document.querySelector('.random-description');
            const content = document.querySelector('.random-description-content');
            const btn = description.querySelector('.random-show-more-btn');

            // Toggle the expanded class
            const isExpanded = description.classList.toggle('expanded');
            btn.textContent = isExpanded ? 'Read Less' : 'Read More';

            // Scroll to top of the description when "Read Less" is clicked
            if (!isExpanded) {
                content.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        function updateCartVr(prId, action, cart_id) {
            var variation = $('#selected_variation').val();
            console.log(variation)
            if (action == 'add') {
                var url = "{{ route('add-to-cart') }}";
            } else {
                var url = "{{ route('remove-from-cart') }}";
            }
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: url,
                data: {
                    prId: prId,
                    cart_id: cart_id,
                    variation: variation
                },
                beforeSend: function() {
                    $('#loading').show()
                },
                success: function(data) {
                    console.log(data.firstvr)
                    if (data.status) {
                        toasterNotification(data.message)
                        if (action == 'add') {
                            var cartBtn = ` <a onclick="updateCart(` + prId + `, 'remove', '', '` + data
                                .cart_id +
                                `')" class="btn border border-secondary rounded p-1 px-2 text-primary"><i class="fa fa-shopping-bag me-2 text-primary"></i>Remove</a>`
                        } else {
                            var cartBtn = ` <a onclick="updateCart(` + prId +
                                `, 'add',  '` + data.firstVr +
                                `', '')" class="btn border border-secondary rounded p-1 px-2 text-primary"><i class="fa fa-shopping-bag me-2 text-primary"></i>Add to cart</a>`

                        }
                        $(".cartSec_" + prId).html(cartBtn);
                        $('.cart-count-outer').load(window.location.href + ' .cart-count-inner')

                    } else {
                        toasterNotification(data.message)
                    }
                },
                complete: function() {
                    $('#loading').hide()
                }
            });
        }
        $('.vrtion_select').on('change', function() {
            $('.page_loader').show()
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
            });

            $.ajax({
                url: "{{ route('change-variation') }}",
                type: 'get',
                data: {
                    type: $(this).attr('data-value'),
                    id: "{{ $item->id }}",
                },
                beforeSend: function() {
                    $("#loading").show();
                },
                success: function(data) {
                    if (data.status) { // Assuming the response contains an 'image_url'
                        $("#loading").hide();

                        $('#actual_price').text(data.data.discounted_price)
                        $('.mr_price').text(data.data.mrpprice)
                        var path =
                            "{{ $is_inventory_product ? asset('storage/app/public/inventory-variations/') . '/' : asset('storage/app/public/product-variations/') . '/' }}";
                        var image_url = data.data.img;
                        var vr_details = data.data.variation_details;
                        console.log(vr_details)

                        $('.description_elem').html(vr_details.description)
                        $('.specs_elem').html(vr_details.specifications)

                        if (vr_details.images) {
                            let owl = $(".banner-carousel22"); // Targeting the correct carousel
                            // Destroy existing carousel
                            owl.owlCarousel('destroy');

                            // Clear existing items
                            owl.empty();
                            var images = JSON.parse(vr_details.images);
                            console.log(images)
                            // Append new images
                            images.forEach(function(image) {
                                owl.append('<div class="item"><img loading="lazy" src="' +
                                    path + image +
                                    '" alt="Carousel Image"></div>');
                            });

                            // Reinitialize Owl Carousel
                            owl.owlCarousel({
                                items: 1,
                                loop: true,
                                margin: 10,
                                nav: true,
                                dots: true,
                                autoplay: true,
                            });
                        }
                    }
                },
                complete: function() {
                    $('.page_loader').hide()

                    $("#loading").hide();
                },
            });

            $('#selected_variation').val($(this).attr('data-id'));
        });

        $(document).ready(function() {
            var $content = $('#descriptionContent');
            var $button = $('.random-show-more-btn');
            var $buttonText = $('#buttonText');
            var $chevron = $('#chevron');
            var $fadeOverlay = $('#fadeOverlay');
            var isExpanded = false;

            // Check if content needs read more button
            function checkContentHeight() {
                // Temporarily expand to measure full height
                $content.removeClass('rm-content-collapsed').addClass('rm-content-expanded');
                var fullHeight = $content[0].scrollHeight;
                $content.removeClass('rm-content-expanded').addClass('rm-content-collapsed');

                var collapsedHeight = 200; // Should match CSS

                // Hide button if content is short enough
                if (fullHeight <= collapsedHeight) {
                    $button.hide();
                    $fadeOverlay.hide();
                }
            }

            function toggleContent() {
                isExpanded = !isExpanded;

                if (isExpanded) {
                    expandContent();
                } else {
                    collapseContent();
                }
            }

            function expandContent() {
                $content.removeClass('rm-content-collapsed').addClass('rm-content-expanded');
                $fadeOverlay.addClass('rm-fade-hidden');
                $chevron.addClass('rm-chevron-rotated');
                $buttonText.text('Read Less');

                // Smooth scroll to show more content
                setTimeout(function() {
                    $('html, body').animate({
                        scrollTop: $button.offset().top - $(window).height() + $button
                            .outerHeight() + 20
                    }, 500);
                }, 300);
            }

            function collapseContent() {
                $content.removeClass('rm-content-expanded').addClass('rm-content-collapsed');
                $fadeOverlay.removeClass('rm-fade-hidden');
                $chevron.removeClass('rm-chevron-rotated');
                $buttonText.text('Read More');

                // Scroll back to top of content
                setTimeout(function() {
                    $('html, body').animate({
                        scrollTop: $content.offset().top - 20
                    }, 500);
                }, 100);
            }

            // Initialize
            checkContentHeight();

            // Button click handler
            $button.on('click', function(e) {
                e.preventDefault();
                toggleContent();
            });
        });
    </script>
@endpush
