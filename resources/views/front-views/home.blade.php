@extends('front-views.layout')

@section('title', \App\CentralLogics\Helpers::get_settings('meta_title') != 'null' ?
    \App\CentralLogics\Helpers::get_settings('meta_title') : 'Home')
@section('meta_description', \App\CentralLogics\Helpers::get_settings('meta_description') != 'null' ?
    \App\CentralLogics\Helpers::get_settings('meta_description') : '')
@section('meta_keywords', \App\CentralLogics\Helpers::get_settings('meta_keywords') != 'null' ?
    \App\CentralLogics\Helpers::get_settings('meta_keywords') : '')

    @push('css_or_js')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link
            href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&family=Noto+Sans+Telugu:wght@100..900&display=swap"
            rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

        <meta name="csrf-token" content="{{ csrf_token() }}">
        <style>
            /*------------- page specific ------------------ */


            /*------------- page specific ------------------ */

            .owl-stage-outer,
            .owl-stage,
            .owl-item,
            {
            height: 100% !important;
            }

            .unavailable_data {
                background: #fff2f4;
                text-align: center;
                padding-bottom: 75px;
            }

            .eb-garamond-text {
                font-family: "EB Garamond", serif;
                font-optical-sizing: auto;
                font-weight: <weight>;
                font-style: normal;
            }

            .profile-container {
                display: flex;
                {{-- justify-content: center; --}} {{-- gap: 10px; --}} max-width: 800px;
            }

            .profile-circle {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                overflow: hidden;
                position: relative;
            }

            .profile-image {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .bg-circle {
                position: absolute;
                width: 170px;
                height: 170px;
                border-radius: 50%;
                z-index: -1;
                top: -10px;
                left: -10px;
            }

            .bg-purple {
                background-color: #f0e6ff;
            }

            .bg-peach {
                background-color: #ffe6e0;
            }

            .bg-mint {
                background-color: #e6fff0;
            }

            .bg-blue {
                background-color: #e6f5ff;
            }
        </style>
        <style>
            * {
                {{-- border: 1px solid red; --}}
            }

            .nav-cat-carousel .owl-dots {
                display: none !important;
            }

            .text_grey {
                color: #b1b1b1;
            }

            .text_red {
                color: red;
            }
        </style>
        <style>
            .modal-backdrop {
                z-index: 1040;
            }

            .modal {
                z-index: 1050;
            }

            .secondary_nav {
                margin-top: 140px;
            }

            @media (min-width: 600px) {
                .secondary_nav {
                    /* margin-top: 63px !important; */
                }
            }

            .owl-carousel .owl-item img {
                {{-- height: 240px !important; --}} {{-- object-fit: cover; --}} border-radius: 10px;
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


            .tab-content {
                width: 100%;
            }
        </style>
    @endpush

@section('content')
    <div id="spacer2"></div>
    <div id="spacer" style="height: 63px !important;"></div>
    @php $isShop = false; @endphp

    <div class="d-flex g-0 parent_elem">


        <div class="middle_section">
            <div class="row hero_section">
                <div class=" banner_section p-0">
                    <div class="owl-carousel banner-carousel ">
                        @foreach ($data['banners'] as $key => $value)
                            @if ($value['link'])
                                <a href="{{ $value['link'] }}">
                                    <img loading="lazy"
                                        src="{{ asset('storage/app/public/banner/') . '/' . $value['image'] }}"
                                        alt="">
                                </a>
                            @else
                                <img loading="lazy" src="{{ asset('storage/app/public/banner/') . '/' . $value['image'] }}"
                                    alt="">
                            @endif
                        @endforeach
                    </div>
                </div>
                <div class="offer_section zx7m-wrapper">
                    <div class="zx7m-wrapper">
                        <div class="m4q8-container" id="carouselContainer">
                            <div class="w2n5-track" id="carouselTrack">
                                @php

                                @endphp
                                @foreach ($data['offer_banners'] as $key => $banner)
                                    <a href="{{ $banner->url }}" class="r7t1-slide">
                                        <div class="v3l9-content position-relative">
                                           @if($banner->title)
                                            {{-- <span
                                                style="position: absolute;
    top: 7px;
    background: #00000085;
    color: white;
    padding: 3px 5px;
    border-radius: 5px;
     left: 50%;
    transform: translateX(-50%);
   ">
                                                {{$banner->title}}</span> --}}
                                                @endif
                                            <img data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($banner->image, asset('storage/app/public/banners/') . '/' . $banner->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'banners/') }}"
                                                alt="offer banner" class="slide-image">
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                            {{-- <button class="nav-arrow prev" id="prevBtn">‹</button>
                            <button class="nav-arrow next" id="nextBtn">›</button> --}}
                            <div class="x9m1-dots" id="dotsContainer"></div>
                        </div>
                    </div>
                </div>
                {{-- <div class=" offer_section ">
                    <div class="side_banner h-100 rounded ">
                        <div class="bg-white rounded d-flex justify-content-center" >
                            <img src="{{ asset('storage/app/public/util/offer-banner.jpg') }}" alt="offer banner">
                        </div>
                    </div>
                </div> --}}
            </div>
            <div>
                @foreach ($modules as $mod)
                    @if ($mod->id == 6)
                        <div class=" owl-carousel category-carousel mt-3">
                            {{-- <div class=" ">
                                <img loading="lazy" style="width: 30px;"
                                    src="{{ asset('public/assets/front/img/cog-removebg-preview.png') }}" alt="">
                                <h6>{{ $mod->module_name }}</h6>
                            </div> --}}

                            @foreach ($data['service_categories'] as $key => $ct)
                                <div style="">
                                    <a class="nav_cat_card text-center"
                                        href="{{ route('category.listing', [$ct['slug']]) }}">
                                        <img loading="lazy"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($ct['image'], asset('storage/app/public/category/') . '/' . $ct['image'], asset('public/assets/admin/img/160x160/img1.jpg'), 'category/') }}"
                                            class=" rounded-circle module_cat_img lazyload" alt="First slide">

                                        <p class="text-center one-line-ellipsis pb-0">{{ $ct['name'] }}</p>
                                    </a>
                                </div>
                            @endforeach

                        </div>
                    @endif
                @endforeach
            </div>
            {{-- TOP SELLING ITEMS  --}}
            <div class="  m-2 mt-3 ">
                @if (count($data['top_sell_services']))
                    <h1 style="" class="section_heading ">Find Goods, Services, Skilled Labour, and Book
                        Repairs
                        Easily In Your
                        City!
                    </h1>
                    <p style="margin:0 atuo;" class=""><i class="fas fa-fire text-secondary"></i> Discover Our
                        Top-Selling Services - Customer Favorites
                        You Can't Miss! <i class="fas fa-fire text-secondary"></i></p>
                @endif
                <div class="row g-4 ">

                    @foreach ($data['top_sell_services'] as $pro)
                        @php
                            $variations = json_decode($pro->variations);
                        @endphp


                        <div class="col-md-4 col-lg-3 col-xl-2 col-sm-4 product_card ">
                            <div class="rounded position-relative fruite-item">

                                <div class="fruite-img product__img_elem" style=" ">
                                    <a href="{{ route('product.details', [$pro->cat_slug, $pro->slug]) }}"> <img
                                            loading="lazy" style="object-fit:cover"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                            class="img-fluid w-100 rounded-top product__img" alt=""></a>
                                </div>


                                <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                    class="prHeart_{{ $pro->id }}  p-1 rounded  position-absolute"
                                    style="top: 10px; right: 10px;cursor:pointer"><i
                                        class="fa fa-heart  heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }} fs-4"></i>
                                </div>

                                <div class="p-2 border border-top-0 rounded-bottom">

                                    <a href="{{ route('product.details', [$pro->cat_slug, $pro->slug]) }}">
                                        <h4 class="one-line-ellipsis text-start item__name"
                                            title="{{ ucfirst($pro->name) }}" style="">
                                            {{ ucfirst($pro->name) }}</h4>
                                    </a>



                                    <div class="d-flex justify-content-between flex-lg-wrap  align-items-center"
                                        style="min-height: 47px;">


                                        @if (auth('web')->user())
                                            <br> <button onclick="bookService({{ $pro->id }}, this)"
                                                class="btn border border-secondary rounded p-1 px-2 text-primary action__btn"><i
                                                    class="fas fa-user-cog"></i>
                                                Enquiry Now</button>
                                        @else
                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                class="btn border border-secondary rounded p-1 px-2 text-primary action__btn"><i
                                                    class="fas fa-user-cog"></i>
                                                Enquiry Now</button>
                                        @endif

                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @foreach ($modules as $mod)
                @if ($mod->id == 5)
                    @php $isShop = true; @endphp
                    <div class=" m-2 mt-3">
                        @if (count($data['top_sell_products']))
                            <h1 class="section_heading text-end">Shop Products, Accessories, and
                                Order
                                Hotel Food - All in
                                Your
                                City! </h1>
                            <p style="margin:0 atuo;" class="text-end"><i class="fas fa-fire text-secondary"></i>
                                Discover
                                Our
                                Top-Selling Products - Customer Favorites
                                You Can't Miss! <i class="fas fa-fire text-secondary"></i></p>
                        @endif
                        <div class="row g-4">

                            @foreach ($data['top_sell_products'] as $pro)
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


                                <div class="col-md-4 col-lg-3 col-xl-2 col-sm-4 product_card ">
                                    <div class="rounded position-relative fruite-item">

                                        <div class="fruite-img product__img_elem" style="">
                                            <a href="{{ route('product.details', [$pro->cat_slug, $pro->slug]) }}"> <img
                                                    loading="lazy" style="object-fit:cover"
                                                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                                    class="img-fluid w-100 rounded-top product__img" alt=""></a>
                                        </div>


                                        <span class="badge rounded-pill bg-light text-dark  time_badge"><i
                                                class="fas fa-fire text-secondary"></i>
                                            {{ strtoupper($pro->delivery_time) }}
                                        </span>
                                        @if ($pro->discount > 0)
                                            <div class="discount_badge">
                                                {{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '% OFF' : \App\CentralLogics\Helpers::currency_symbol() . ' OFF' }}
                                            </div>
                                        @endif

                                        <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                            class="prHeart_{{ $pro->id }}  p-1 rounded  position-absolute"
                                            style="top: 10px; right: 10px;cursor:pointer"><i
                                                class="fa fa-heart  heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }} fs-4"></i>
                                        </div>

                                        <div class="p-2 border border-top-0 rounded-bottom">

                                            <a href="{{ route('product.details', [$pro->cat_slug, $pro->slug]) }}">
                                                <h4 class="one-line-ellipsis text-start"
                                                    title="{{ ucfirst($pro->name) }}" style="font-size: 18px;">
                                                    {{ ucfirst($pro->name) }}</h4>
                                            </a>
                                            <p class="one-line-ellipsis mb-0"
                                                style="min-height:25px ;font-size: 12px;text-align: start; color:#616161;  margin-bottom:5px;letter-spacing: 1px;">
                                                {{ !empty($variations) ? $variations[0]->type : '' }}

                                            </p>
                                            @if (count($variations) > 1)
                                                <span style="font-size: 9px;"
                                                    class="badge badge-pill badge-light text-dark border">+{{ count($variations) - 1 }}
                                                    more option(s)</span>
                                            @endif


                                            <div class="d-flex justify-content-between flex-lg-wrap  align-items-center"
                                                style="min-height: 47px;">
                                                <div class="">
                                                    <p class="text-dark fs-6 fw-bold mb-0">
                                                        {{ _price($selling_price) }}
                                                    </p>
                                                    @if ($mrp)
                                                        <p class="text-danger text-decoration-line-through mb-0 text-start"
                                                            style="    min-height: 24px;">
                                                            @if ($pro->discount > 0)
                                                                {{ _price($mrp) }}
                                                            @endif
                                                        </p>
                                                    @endif
                                                </div>

                                                {{-- @if (_isStoreActive($pro->id)) --}}
                                                <div class="cart-section cartSec_{{ $pro->id }}">
                                                    @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp

                                                    @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                        <button
                                                            onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                            class="btn border border-secondary rounded p-1 px-2 text-primary action__btn fs-5"><i
                                                                class="fa fa-times me-2 text-primary"></i>Remove</button>
                                                    @else
                                                        <button
                                                            onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                            class="btn border border-secondary rounded p-1 px-2 text-primary action__btn fs-5">
                                                            <i class="fa fa-shopping-bag me-2 text-primary"></i>
                                                            Add</button>
                                                    @endif
                                                </div>
                                                {{-- @endif --}}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
            {{-- TOP SELLING ITEMS END --}}
        </div>

    </div>
    <div class="m-2 mt-4 ">
        @if (count($data['nearby_stores']))
            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <h1 style="font-size: 22px;" class="section_heading ">Nearby Stores
                    </h1>
                    <p style="margin:0 atuo;" class=""><i class="fas fa-store"></i> Find Trusted Stores Near You
                    </p>
                </div>
                <a class="btn btn-primary text-white" href="{{ route('all-stores', ['nearby']) }}">View All </a>
            </div>
        @endif
        <div class="row g-0">

            @foreach ($data['nearby_stores'] as $store)
                <div class="col-lg-4 col-md-6 p-2 mt-0">
                    <div class="card-ui  p-2 rounded shadow d-flex flex-row align-items-start">
                        <div class="card-image me-3">
                            <img loading="lazy"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store->logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                class=" rounded-top" alt="">
                        </div>

                        <div class="card-details flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-light text-dark">{{ round($store->distance, 2) }} km</span>
                                    <h6 class="card-title-cuctom mb-1 one-line-ellipsis"
                                        style="font-size: 14px !important;">{{ ucfirst($store->name) }}</h6>
                                </div>
                                @if (_storeExistInWishlist($store->id))
                                    <div onclick="updateStoreWishlist({{ $store->id }}, 'remove')" class="card-heart">
                                        <i class="fas fa-heart text-danger"></i>
                                    </div>
                                @else
                                    <div onclick="updateStoreWishlist({{ $store->id }}, 'add')" class="card-heart">
                                        <i class="fas fa-heart text-muted"></i>
                                    </div>
                                @endif
                            </div>

                            <p class="card-description mb-2 two-line-ellipsis">{{ $store->address }}</p>

                            <div class="d-flex justify-content-between align-items-center">
                                <div class="card-sizes mb-3">
                                    <span class="badge text-dark p-0 "><i class="fas fa-fire text-secondary"></i>
                                        {{ strtoupper($store->delivery_time) }}
                                    </span>
                                </div>
                                <a href="{{ route('store.details', [$store->slug]) }}"
                                    class="btn btn-add-to-cart store_btn">Explore</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <!-- Banner Section End -->




    @if (isset($data['special_product']))
        <div class="xyZ998 container my-3"
            style="border-radius: 33px;background: #ffffff;box-shadow:  21px 21px 42px #bfbfbf,-21px -21px 42px #ffffff;">
            <div class="uVw654">
                <img src="{{ asset('storage/app/public/product') . '/' . $data['special_product']->image }}"
                    alt="{{ $data['special_product']->name }}">
            </div>
            <div class="aBc789">
                <small style="letter-spacing: 1px; color: #999;">{{ $data['special_product']->store?->name }}</small>
                <h1>{{ $data['special_product']->name }}</h1>
                @if ($data['special_product']->module_id == 5)
                    <p>{{ $data['special_product']->description }}</p>
                @endif
                @php
                    $catSlug = _getCatSlugByItemId($data['special_product']->id);
                $variations = json_decode($data['special_product']->variations); @endphp

                <div class="klM321 flex-wrap">
                    @foreach ($variations as $key => $vr)
                        <div class="pQr456">
                            {{-- <input type="radio" id="meter{{ $key }}" data-id= "{{ $key }}"
                                data-value="{{ $vr->type }}" class="vrtion_select" {{ !$key ? 'checked' : '' }}
                                name="options-outlined"> --}}
                            <label class="variation" for="meter{{ $key }}">
                                <span>{{ preg_replace('/([a-z])([A-Z])/', '$1 $2', $vr->type) }}</span>
                                <span class="price">₹{{ round($vr->price) }}
                                    @if ($vr->mrpprice != $vr->price)
                                        <span class="text-danger text-decoration-line-through mb-0 ">
                                            {{ isset($vr->mrpprice) ? _price($vr->mrpprice) : _price($vr->price) }}</span>
                                    @endif
                                </span>
                            </label>
                        </div>
                    @endforeach

                </div>
                @if ($data['special_product']->module_id == 6)
                    <h5>Providers</h5>
                    <div class="profile-container align-items-center">
                        @php $providers = _getServiceProviders($data['special_product']->id); @endphp
                        @foreach ($providers as $key => $value)
                            @if ($key < 5)
                                <a href="{{ route('store.details', [$value->slug]) }}" style="margin: 0 -7px;"
                                    class="position-relative">
                                    <div class="bg-circle bg-purple"></div>
                                    <div class="profile-circle border border-3 shadow-sm">
                                        <img src="{{ asset('storage/app/public/store') . '/' . $value->logo }}"
                                            alt="Profile 1" class="profile-image">
                                    </div>
                                </a>
                            @endif
                        @endforeach

                        <a class="mx-4 text-dark"
                            href="{{ route('product.details', [$catSlug, $data['special_product']->slug]) }}"> +
                            {{ count($providers) - 5 }} more </a>
                    </div>
                @endif

                <a href="{{ route('product.details', [$catSlug, $data['special_product']->slug]) }}"
                    class="btn btn-primary mt-2">Explore</a>
            </div>


        </div>
    @endif
    @if (!count($data['top_sell_services']) && !count($data['top_sell_products']) && !$data['special_product'])
        <div class="unavailable_data">
            <img style="mix-blend-mode: multiply;" class="img-fluid"
                src="{{ asset('storage/app/public/util/no-result-found-empty-results-popup-design_586724-96.jpg') }}">
            <h1 class="fs-2 eb-garamond-text">Available Locations: Tirupati, Chittoor, Madanapalle<h1>
                    <h3 class="fs-4 eb-garamond-text">Don’t see your city? We’re expanding soon! Stay tuned.</h3>
        </div>
    @endif


    <div class="brand_directory p-2" style="background-color: #f5f5f5;">
        <h6>Brand Directory</h6>
        <div class="d-flex flex-wrap">
            @foreach (_navCats() as $key => $ct1)
                <div class="m-2 d-flex flex-column">
                    <a data-bs-toggle="collapse" href="#collapseExample{{ $key }}" role="button"
                        aria-expanded="false" aria-controls="collapseExample" class="text-dark">
                        <span>
                            {{ $ct1->name }}
                        </span><i class="fa fa-chevron-down px-1 pt-1"></i> </a>
                    <div class="collapse " id="collapseExample{{ $key }}">
                        <div class="d-flex flex-column">
                            @if ($ct1->module_id == 5)
                                @foreach (_navSubCats($ct1->id, $ct1->module_id) as $key => $ct)
                                    <a class="text-dark" href="{{ route('category.listing', [$ct->slug]) }}"
                                        style="font-size: 12px;">{{ $ct->name }}</a>
                                @endforeach
                            @else
                                @foreach (_navSubCats($ct1->id, $ct1->module_id) as $key => $ct)
                                    <a class="text-dark"
                                        href="{{ route('product.details', [$ct->cat_slug, $ct->slug]) }}"
                                        style="font-size: 12px;">{{ $ct->name }}</a>
                                @endforeach
                            @endif
                            <a class="text-dark fw-bold" href="{{ route('category.listing', [$ct1->slug]) }}"
                                style="font-size: 12px;">View All </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
@endsection
@push('script_2')
@endpush
