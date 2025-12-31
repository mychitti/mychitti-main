@if (!Request::is('store*') && !Request::is('store-terms-and-conditions'))
    <!-- Navbar start -->
    <style>
       
    </style>
    <div class=" fixed-top ">
        <nav
            class="nav-wrapper navbar navbar-light bg-white navbar-expand-xl mx-md-5 mx-0 justify-content-between p-0 pt-2">
            <a href="{{ route('home') }}" class="navbar-brand   nav-link " style=" width: 150px;">
                <!-- <h1 class="text-primary display-6">My Chitti</h1> -->
                @php($store_logo = \App\Models\BusinessSetting::where(['key' => 'logo'])->first()->value)
                <img style="width:100%" class="navbar-brand-logo initial--36 onerror-image onerror-image "
                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/app/public/business/') . '/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'business/') }}"
                    alt="Logo">
            </a>
            <button class="navbar-toggler py-2 px-3" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarCollapse">
                <span class="fa fa-bars text-primary"></span>
            </button>
            <div class="collapse navbar-collapse bg-white justify-content-between" id="navbarCollapse">


                <div class="d-flex mobile_nav_list">
                    @if (!Request::is('list-your-business') && !Request::is('store*') && !Request::is('checkout'))
                        <a type="button" class=" wrapper_link  nav-link  location_btn text-primary d-flex"
                            data-bs-toggle="modal" data-bs-target="#mapModal"><i
                                class="fas fa-map-marker-alt mx-1 pt-1"></i><span id="user_city2"
                                style="text-overflow: ellipsis; white-space: nowrap; display: block; overflow: hidden; "
                                title="{{ session()->has('customer_city') ? session('customer_city') : '' }}">
                                {{ session()->has('customer_city') ? session('customer_city') : '' }} </span></a>
                    @endif

                    <li class="header__list-item has-submenu">
                        <a href="#" class="wrapper_link nav-link ">
                            <span>Categories</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="9" height="5" viewBox="0 0 9 5"
                                fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M0.612712 0.200408C0.916245 -0.081444 1.39079 -0.0638681 1.67265 0.239665L4.37305 3.14779L7.07345 0.239665C7.35531 -0.0638681 7.82986 -0.081444 8.13339 0.200408C8.43692 0.48226 8.4545 0.956809 8.17265 1.26034L4.92265 4.76034C4.78074 4.91317 4.5816 5 4.37305 5C4.1645 5 3.96536 4.91317 3.82345 4.76034L0.573455 1.26034C0.291603 0.956809 0.309179 0.48226 0.612712 0.200408Z"
                                    fill="#81c408" />
                            </svg>
                        </a>
                        <div class="submenu-wrapper">
                            <div class="submenu-list__wrapper">
                                <div class="submenu-list__title">Categories</div>
                                <ul class="submenu-list">
                                    @include('front-views.partials.category_megamenu')
                                </ul>
                            </div>
                        </div>
                    </li>

                    <a class=" wrapper_link nav-link  position-relative" href="https://vendor.mychitti.net">
                        Store Login
                    </a>

                    <a style="width: fit-content;"
                        class=" wrapper_link  nav-link text-primary fw-bold  position-relative"
                        href="{{ route('new-store.create') }}">
                        Business Listing
                        <span
                            class="position-absolute top-0 start-100 translate-middle badge rounded bg-danger free_listing_badge">
                            Free
                        </span>
                    </a>

                </div>
                <!-- {{ session()->has('zone_ids') ? 'zone id : ' . session('zone_ids') : 'zone id : ' . '' }} -->
                {{-- @if (Request::is('/')) --}}


                <div class="d-flex ">
                    <div class="navbar-nav">
                        <div class="position-relative mx-auto search_parent w-100 nav-extra">
                            {{-- <a type="button" class=" search_modal_btn text-primary d-flex" data-bs-toggle="modal"
                            data-bs-target="#searchbarModal">Search Products, Services, Keywords, Stores <i
                                class="fas fa-search p-1 pe-0"></i></a> --}}
                            <a type="button" class=" search_modal_btn text-primary d-flex" data-bs-toggle="modal"
                                data-bs-target="#searchbarModal"><img
                                    src="{{ asset('storage/app/public/util/loupe.png') }}" width="40px" height="40px"
                                    class="  mx-2" alt="search"></a>

                        </div>


                        <div class="nav-cat-searchbar"></div>

                    </div>

                    @if (Config::get('module.current_module_id') == 5)
                        <div class="cart-count-outer mx-1" style="line-height: 7px;">
                            <a href="{{ route('cart') }}" class="position-relative mx-2 my-auto cart-count-inner">
                                <img src="{{ asset('storage/app/public/util/new_cart.png') }}" width="40px"
                                    height="40px" class="rounded   mx-2" alt="cart">

                                <span
                                    class="position-absolute bg-danger rounded-circle d-flex align-items-center justify-content-center text-light px-1"
                                    style="top: -8px;right: 0px;height: 20px;min-width: 20px;">{{ _cartCount() }}</span>
                            </a>
                        </div>
                    @endif
                    <a href="{{ !auth('web')->user() ? route('user-login') : route('dashboard') }}"
                        class="my-auto  mx-1 d-flex align-items-center text-dark">
                        @if (auth('web')->user())
                            @if (auth('web')->user()->image)
                                <img src="{{ asset('storage/app/public/profile/') . '/' . auth('web')->user()->image }}"
                                    width="40px" height="40px" class="rounded   mx-2" alt="user">
                            @else
                                {{-- <span
                                    style="width: 40px;height: 40px; border-radius: 50%; display: flex ; align-items: center;justify-content: center;background-color: #e0e0e0;color: grey;font-weight: bold;"
                                    class=" border  mx-2"></span> --}}
                                <img src="{{ asset('storage/app/public/util/new_user.png') }}" width="40px"
                                    height="40px" class="rounded   mx-2" alt="profile">
                            @endif
                            {{ auth('web')->user()->f_name }}
                        @else
                            <img src="{{ asset('storage/app/public/util/new_user.png') }}" width="40px"
                                height="40px" class="rounded   mx-2" alt="profile">
                        @endif
                    </a>

                </div>
            </div>


        </nav>
    </div>
@elseif(isset($store) && $store)
    <div class=" fixed-top ">
        <nav
            class="nav-wrapper navbar navbar-light bg-white navbar-expand-xl mx-md-5 mx-0 justify-content-between p-0 pt-2">
            <img class="store_logo" src="{{ asset('storage/app/public/store/') . '/' . $store['logo'] }}">
            <div class="d-flex">
                <div class="d-block d-xl-none">
                    @if ($store['active'])
                        <img style="width:70px;" src="{{ asset('storage/app/public/util/open_store.jpg') }}"
                            alt="Open">
                    @else
                        <img style="width:70px;" src="{{ asset('storage/app/public/util/closed.jpg') }}"
                            alt="Open">
                    @endif
                </div>
                <button class="navbar-toggler py-2 px-3" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarCollapse">
                    <span class="fa fa-bars text-primary"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse bg-white justify-content-between" id="navbarCollapse">


                <div class="d-flex mobile_nav_list list-unstyled">
                    <li class="mx-4">
                        <a href="#services" class="my-nav-link  wrapper_link">Services</a>
                    </li>

                    <li class="mx-4">
                        <a href="#store-ratings" class="my-nav-link wrapper_link">Store Ratings</a>
                    </li>
                    <li class="mx-4">
                        <a href="#contact" class="my-nav-link wrapper_link">Contact</a>
                    </li>
                    <li class="mx-4">
                        <a href="#about" class="my-nav-link wrapper_link">About</a>
                    </li>
                    <li class="mx-4">
                        <a href="{{ route('store.gallery', [$store['slug']]) }}"
                            class="my-nav-link wrapper_link">Gallery</a>
                    </li>
                </div>
                <div class="custom-hide-between">
                    @if ($store['active'])
                        <img style="width:70px;" src="{{ asset('storage/app/public/util/open_store.jpg') }}"
                            alt="Open">
                    @else
                        <img style="width:70px;" src="{{ asset('storage/app/public/util/closed.jpg') }}"
                            alt="Open">
                    @endif
                </div>

    
            </div>


        </nav>
    </div>
@endif

<div class="container-fluid secondary_nav">

</div>
<!-- Navbar End -->
<!-- Modal Search Start -->
<div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content rounded-0"> 
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Search by keyword</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-flex align-items-center flex-column">
                <div class="input-group w-75 mx-auto d-flex">
                    <input type="search" class="form-control p-3 searchBarBtn" 
                        placeholder="keywords" aria-describedby="search-icon-1" autocomplete="off">
                    <span id="search-icon-1" class="input-group-text p-3"><i class="fa fa-search"></i></span>
                </div>
                <div id="autocomplete" class="w-75 bg-white">

                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Search End -->



<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="exampleModalLogoutLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLogoutLabel">Login / Signup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="f-3">You are not logged in</p>
                <p class="f-5">Please Login to Continue</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="{{ route('user-login') }}" class="btn btn-primary">Login</a>
            </div>
        </div>
    </div>
</div>
