@extends('front-views.layout')

@php
    // City-aware SEO signals: when the page is resolved for a specific city (from the URL),
    // make each city's title/description distinct — "AC Repair in Mumbai" vs "AC Repair in Patna"
    // — instead of an identical "AC Repair" across every city URL.
    $seoSubject = ($seoCityName ?? null) ? ($catDetails->name . ' in ' . $seoCityName) : $catDetails->name;
    // Title carries a CTA (doc P0: city + category + strong CTA).
    $seoTitle = $seoSubject . ' | Compare & Book — My Chitti';
    $seoDesc = ($seoCityName ?? null)
        ? ('Find and book ' . $catDetails->name . ' services in ' . $seoCityName . '. Compare local providers, ratings and prices.')
        : $catDetails->name;
    $catOgImage = !empty($catDetails->image)
        ? asset('storage/app/public/category/' . $catDetails->image)
        : asset('storage/app/public/business/' . (\App\Models\BusinessSetting::where('key', 'logo')->value('value')));
@endphp

@section('title', $seoTitle)

@section('metatitle', $seoTitle)
@section('meta_keywords', $catDetails->keywords)
@section('meta_description', $seoDesc)

@push('meta_tags')
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="My Chitti" />
    <meta property="og:title" content="{{ $seoSubject }}" />
    <meta property="og:description" content="{{ $seoDesc }}" />
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}" />
    <meta property="og:image" content="{{ $catOgImage }}" />
@endpush
@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
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

        @media only screen and (max-width: 995px) {

            #spacer {
                display: block !important;
                height: 8px !important;
            }
        }

        .nav-extra,
        .nav-cat-searchbar {
            transition: all 0.3s ease;
        }

        .nav-cat-searchbar.hidden,
        .nav-extra.hidden {
            display: none;
            /* or visibility: hidden; opacity: 0 */
        }

        .details_page_cont {
            /* margin-top: 145px !important; */
        }

        @media only screen and (max-width: 768px) {
            .details_page_cont {
                {{-- margin-top: 200px; --}}
            }

            .spacer {}
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 4fr));
            gap: 16px;
        }
    </style>
@endpush

@section('content')

    <!-- Fruits Shop Start-->
    <div id="spacer" style="height: 90px;"></div>

    <div class="container-fluid fruite details_page_cont">


        <div class="row g-4 ">

            <div class="col-lg-12">

                <div class="row g-4 ">
                    <div class="col-lg-2">
                        <div class="row g-4 fixed_nav">

                            <div class="col-lg-12">
                                <h5 class="mb-1 mb-sm-4">Categories</h5>
                                <div class="scrolling_cate">
                                    @foreach ($data['all_categories'] as $cat)
                                        @php
                                            $class = '';
                                            if (Str::afterLast(request()->path(), '/') == $cat->slug) {
                                                $class = 'visited_category';
                                        } @endphp
                                        <div
                                            class="d-flex align-items-center justify-content-start my-1 {{ $class }}">
                                            <div class="rounded border m-2" style="width: 40px;aspect-ratio: 1/1;">
                                                <img loading="lazy" style="height:100%; width: 100% ; object-fit:cover;"
                                                    src="{{ asset('storage/app/public/category/') . '/' . $cat->image }}"
                                                    class="img-fluid rounded" alt="{{ ucfirst($cat->name) }}">
                                            </div>
                                            <div>
                                                <h6 class="mb-2" style="font-size:14px;"><a class="text-dark"
                                                        href="{{ route('category.listing', [$cat->slug, _selectedCity()]) }}">{{ ucfirst($cat->name) }}</a>
                                                </h6>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @if ($module == 6)
                                    <div class="hide_on_phone">
                                        <div class="owl-carousel nav-cat-carousel   justify-content-center ">
                                            @foreach ($data['all_categories'] as $key => $ct)
                                                <div style="    padding: 5px 0px;">
                                                    <a class="nav_cat_card"
                                                        href="{{ route('category.listing', [$ct->slug, _selectedCity()]) }}">
                                                        <img loading="lazy" loading="lazy" style="height: auto !important; max-width:50px;"
                                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($ct->image, asset('storage/app/public/category/') . '/' . $ct->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'category/') }}"
                                                            class=" rounded module_cat_img" alt="First slide">

                                                        <p class="mb-0"> {{ $ct->name }}</p>
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-10 mt-1">
                        {{-- category banners here --}}
                        <div class="owl-carousel banner-carousel " style="  margin: 10px auto;">
                            @foreach ($data['banners'] as $key => $value)
                                @if ($value['link'])
                                    <a href="{{ $value['link'] }}">
                                        <img loading="lazy" loading="lazy"
                                            src="{{ asset('storage/app/public/banner/') . '/' . $value['image'] }}"
                                            alt="{{ $catDetails->name }}">
                                    </a>
                                    {{-- src="https://mychitti.net/storage/app/public/banner/2025-05-31-683ac67ce0716.png"  --}}
                                @else
                                    <img loading="lazy" loading="lazy"
                                        src="{{ asset('storage/app/public/banner/') . '/' . $value['image'] }}"
                                        alt="{{ $catDetails->name }}">
                                    {{--  --}}
                                @endif
                            @endforeach
                        </div>
                        <div class="position-relative d-flex mb-3 top_elem">
                            <h1 class="fs-3 ">{{ $seoSubject }}</h1>
                            <div class="sec-searchbar">
                                <div id="searchContainer" class="">
                                    <input type="text" id="prSearchInput" placeholder="Type to search..."
                                        autocomplete="off">
                                    <button id="searchButton"><i class="fas fa-search"></i></button>
                                    <button id="crossBtn" style="display:none;"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                        </div>
                        @if (!empty($seoLandingUrl))
                            <a href="{{ $seoLandingUrl }}" class="d-flex align-items-center justify-content-between mb-3 text-decoration-none"
                               style="gap:12px; padding:14px 18px; border-radius:12px; background:linear-gradient(135deg,#fff5f1,#f4f1ef); border:1px solid #eadfd9;">
                                <span style="color:#7a3418; font-weight:600; font-size:14.5px;">
                                    📖 Read our complete {{ $catDetails->name }}{{ ($seoCityName ?? null) ? ' in ' . $seoCityName : '' }} guide — top providers, FAQs &amp; more
                                </span>
                                <span style="color:#C8522A; font-weight:700; white-space:nowrap;">View guide &rarr;</span>
                            </a>
                        @endif
                        @if (isset($seoLinks) && $seoLinks->isNotEmpty())
                            <div class="mb-4">
                                <p class="mb-2 fw-bold text_dark">Popular {{ $catDetails->name }} services{{ ($seoCityName ?? null) ? ' in ' . $seoCityName : '' }}</p>
                                 <div class="d-flex flex-wrap" style="gap:10px;">
                                    @foreach ($seoLinks as $link)
                                        <a href="{{ url($link->slug) }}"
                                           style="display:inline-block; padding:8px 16px; background:#f4f1ef; color:#C8522A; border-radius:999px; font-weight:600; font-size:13.5px; border:1px solid #eadfd9;">
                                            {{ $link->title }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if (isset($otherCityLinks) && $otherCityLinks->isNotEmpty())
                            <div class="mb-4">
                                <p class="mb-2 fw-bold text_dark">{{ $catDetails->name }} in other cities</p>
                                <div class="d-flex flex-wrap" style="gap:10px;">
                                    @foreach ($otherCityLinks as $ocl)
                                        <a href="{{ url($ocl->slug) }}"
                                           style="display:inline-block; padding:8px 16px; background:#ffffff; color:#C8522A; border-radius:999px; font-weight:600; font-size:13.5px; border:1px solid #eadfd9;">
                                            {{ $catDetails->name }} in {{ $ocl->city }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        <div class=" justify-content-center grid-container">

                            @foreach ($catProducts as $pro)
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

                                <div class="pr_{{ $pro->id }}" style="    max-width: 264px !important;">
                                    <div class="rounded position-relative fruite-item">

                                        <div class="fruite-img" style="height: 170px !important;">
                                            <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}"> <img loading="lazy"
                                                    loading="lazy" style="height: 170px !important;object-fit:cover"
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

                                        <div class="p-2 border border-top-0 rounded-bottom">

                                            <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                                                <h4 class="one-line-ellipsis text-start product_name"
                                                    data-id="pr_{{ $pro->id }}" title="{{ ucfirst($pro->name) }}"
                                                    style="font-size: 15px;">
                                                    {{ ucfirst($pro->name) }}</h4>
                                            </a>
                                            @if ($module == 5)
                                                <p class="one-line-ellipsis"
                                                    style="min-height:25px ;font-size: 12px;text-align: start; color:#616161; margin-bottom:5px;letter-spacing: 1px;">
                                                    {{ !empty($variations) ? $variations[0]->type : '' }}
                                                </p>

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
                                                    <div class="cart-section cartSec_{{ $pro->id }}">
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
                                                                <i class="fa fa-shopping-bag me-2 text-primary"></i>
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
                        @if (!count($catProducts))
                            <img loading="lazy" style="width: 400px;"
                                src="{{ asset('public/assets/front/img/sorry-item-not-found-3328225-2809510.webp') }}"
                                alt="no-results">
                            <h4 style="text-align: center;">Oops! No products found</h4>
                        @endif

                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- Fruits Shop End-->


@endsection

@push('script_2')
    <script>
        $(document).ready(function() {
            function generateRandomClassName() {
                return 'search-' + Math.random().toString(36).substring(2, 10);
            }

            const $searchContainer = $('#searchContainer');
            const $searchInput = $('#prSearchInput');
            const randomClass = generateRandomClassName();

            if ($searchContainer.length && $searchInput.length) {
                $searchContainer.addClass('search-style ' + randomClass);
                console.log('Random class applied:', randomClass);

                const placeholderTexts = [];

                document.querySelectorAll('.product_name').forEach(el => {
                    const name = el.textContent.trim();
                    if (name) {
                        placeholderTexts.push(`Search "${name}"`);
                    }
                });

                if (placeholderTexts.length === 0) {
                    placeholderTexts.push("Search products...");
                }

                let index = 0;

                function changePlaceholder() {
                    $searchInput.attr('placeholder', placeholderTexts[index]);
                    index = (index + 1) % placeholderTexts.length;
                }

                changePlaceholder();
                setInterval(changePlaceholder, 2000);
            } else {
                console.warn("Missing #searchContainer or #prSearchInput");
            }
        });


        $(document).ready(function() {
            $('#crossBtn').on('click', function() {
                $('#prSearchInput').val('')
                $("[class^='pr_']").show()
                $('#searchButton').show()
                $('#crossBtn').hide()
            })
            $('#prSearchInput').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                if (value.length) {
                    $('#searchButton').hide()
                    $('#crossBtn').show()
                } else {
                    $('#searchButton').show()
                    $('#crossBtn').hide()
                }

                $('.product_name').filter(function() {
                    var id = $(this).attr('data-id');
                    var text = $(this).text().toLowerCase();
                    if (text.includes(value)) {
                        $('.' + id).show();
                    } else {
                        $('.' + id).hide();
                    }
                });
            });
        });

        $(window).on('scroll', function() {
            if ($(this).scrollTop() >= 100) {
                $('.nav-extra').addClass('hidden');

                if ($('#searchContainer').parent().hasClass('sec-searchbar')) {
                    // Move it only if currently inside .sec-searchbar
                    $('#searchContainer').appendTo('.nav-cat-searchbar');
                }
            } else {
                $('.nav-extra').removeClass('hidden');

                if ($('#searchContainer').parent().hasClass('nav-cat-searchbar')) {
                    // Move it only if currently inside .nav-cat-searchbar
                    $('#searchContainer').appendTo('.sec-searchbar');
                }
            }
        });
    </script>
@endpush
