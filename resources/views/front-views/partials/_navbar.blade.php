@if (!Route::is('store.details') && !Request::is('store-terms-and-conditions') && !request()->attributes->get('is_store_domain'))
    <!-- Navbar start -->
    <div class="fixed-top"> 
        <nav
            class="nav-wrapper navbar navbar-light bg-white navbar-expand-xl mx-md-5 mx-0 justify-content-between p-0 pt-2">
            <a href="{{ route('home') }}" class="navbar-brand   nav-link " style=" width: 150px;">
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

                    {{-- <li class="header__list-item has-submenu">
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
                    </li> --}}

                    <a class=" wrapper_link nav-link  position-relative" href="https://vendor.mcvendorhub.com/login">
                        Store Login
                    </a>

                    <a style="width: fit-content;"
                        class=" wrapper_link  nav-link text-primary fw-bold  position-relative"
                        href="{{ _vendorSignupUrl() }}">
                        Business Listing
                        <span
                            class="position-absolute top-0 start-100 translate-middle badge rounded bg-danger free_listing_badge">
                            Free
                        </span>
                    </a>

                </div>
                <!-- {{ session()->has('zone_ids') ? 'zone id : ' . session('zone_ids') : 'zone id : ' . '' }} -->
                {{-- @if (Request::is('/')) --}}


                <div class="d-flex align-items-center">
                     {{-- KEYWORD search (default). The sparkling button switches to AI Search. --}}
                    <div id="mcKeywordSearch" style="display:flex; align-items:center; gap:8px;">
                    <div class="position-relative search_inp_grp">
                        <input onkeyup="searchBar('search_results3', this)" id="mainSearchbar"
                            class="form-control border-2 border-secondary p-2 rounded-pill new_searchbar"
                            autocomplete="off">

                            <a class="search_icon" style="    position: absolute;top: 10px;right: 60px;">
                            <i class="fa fa-search"></i></a>



                        <div class="animated-placeholder" id="animatedPlaceholder">
                            <span class="fixed-text">Search&nbsp;</span>
                            <span class="changing-text" id="changingText">Category</span>
                        </div>

                        <div id="autocomplete3" style="z-index: 2;"
                            class="autocomplete3 w-100 bg-white position-absolute">
                            <div id="search_results3">
                                <ul class="list-unstyled mb-0">

                                </ul>
                            </div>
                            <div id="search_placeholder" class="search-placeholder" style="display:none">
                                <h6 class="recent_search_title" style="display:none">Recent Searches </h6>
                                <div id="recent-searches" class="recent-search-chips">
                                </div>
                                <h6 class="mt-5">Trending Now</h6>
                                <div class="recent-search-chips">
                                    {!! _topsearched() !!}
                                </div>
                            </div>
                        </div>
                    </div>
                        <button type="button" class="mc-ai-magic-btn" data-mc-search-mode="ai" title="Try AI Search — ask in plain words">
                            <span class="mc-spark mc-spark-1">✦</span>
                            <i class="fas fa-wand-magic-sparkles"></i>
                            <span class="mc-ai-magic-label"><i class="fa fa-search"></i> AI Search</span>
                            <span class="mc-spark mc-spark-2">✦</span>
                        </button>
                    </div>

                    {{-- AI search (hidden until toggled from keyword mode) --}}
                    <div id="mcAiSearch" style="display:none; align-items:center; gap:8px;">
                        <form action="{{ route('ai.search') }}" method="GET" class="mc-ai-search" role="search">
                            <span class="mc-ai-badge"><i class="fas fa-wand-magic-sparkles"></i> AI</span>
                            <input type="text" name="q" class="mc-ai-input" id="mcAiInput" autocomplete="off" required
                                value="{{ request('q') }}"
                                placeholder="Describe what you need…">
                            <button type="submit" class="mc-ai-btn"><span>Ask</span> <i class="fas fa-arrow-right"></i></button>
                        </form>
                        <button type="button" class="mc-search-switch" data-mc-search-mode="keyword" title="Switch to keyword search">
                            <i class="fa fa-search"></i> Keyword
                        </button>
                    </div>
                    {{-- <a type="button" class=" search_modal_btn text-primary d-flex" data-bs-toggle="modal"
                            data-bs-target="#searchbarModal">Search Products, Services, Keywords, Stores <i
                                class="fas fa-search p-1 pe-0"></i></a> --}}
                    {{-- <div class="navbar-nav">
                        <div class="position-relative mx-auto search_parent w-100 nav-extra">
                            <a type="button" class=" search_modal_btn text-primary d-flex" data-bs-toggle="modal"
                                data-bs-target="#searchbarModal"><img
                                    src="{{ asset('storage/app/public/util/loupe.png') }}" width="40px" height="40px"
                                    class="  mx-2" alt="search"></a>
                        </div>
                        <div class="nav-cat-searchbar"></div>
                    </div> --}}

                    @if (Config::get('module.current_module_id') == 5)
                        {{-- <div class="cart-count-outer mx-1" style="line-height: 7px;">
                            <a href="{{ route('cart') }}" class="position-relative mx-2 my-auto cart-count-inner">
                                <img src="{{ asset('storage/app/public/util/new_cart.png') }}" width="40px"
                                    height="40px" class="rounded   mx-2" alt="cart">

                                <span
                                    class="position-absolute bg-danger rounded-circle d-flex align-items-center justify-content-center text-light px-1"
                                    style="top: -8px;right: 0px;height: 20px;min-width: 20px;">{{ _cartCount() }}</span>
                            </a>
                        </div> --}}
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
    {{-- <div class=" fixed-top ">
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
    </div> --}}
@endif

<div class="container-fluid secondary_nav">
    @if (!Route::is('store.details') && !Request::is('store*') && !request()->attributes->get('is_store_domain'))
        @once
             <style>  
                .mc-ai-search { display:flex; align-items:center; gap:6px;
                    background:#fff; border:2px solid #7c3aed; border-radius:999px;
                    padding:3px 4px 3px 12px; box-shadow:0 4px 18px -8px rgba(124,58,237,.6); }
                .mc-ai-badge { display:inline-flex; align-items:center; gap:4px; flex:none;
                    font-weight:700; font-size:11px; letter-spacing:.02em; color:#fff;
                    background:linear-gradient(135deg,#2563eb,#7c3aed); padding:5px 9px; border-radius:999px; }
                .mc-ai-input { border:0; outline:0; font-size:14px; background:transparent; color:#1d2333;
                    width:240px; max-width:42vw; }
                .mc-ai-input::placeholder { color:#8688a8; }
                .mc-ai-btn { flex:none; border:0; border-radius:999px; font-weight:700; font-size:13px;
                    color:#fff; background:#7c3aed; padding:7px 13px; cursor:pointer; transition:background .15s; }
                .mc-ai-btn:hover { background:#6d28d9; }
                .mc-search-switch { border:1px solid #d8e6c0; background:#f3f9ea; color:#5a9e00;
                    font-weight:600; font-size:12.5px; border-radius:999px; padding:7px 12px; cursor:pointer;
                    white-space:nowrap; transition:background .15s,border-color .15s; }
                .mc-search-switch:hover { background:#e9f4d8; border-color:#81c408; }

                /* Sparkling, magical AI Search button — blue → purple */
                .mc-ai-magic-btn { position:relative; display:inline-flex; align-items:center; gap:6px;
                    border:0; border-radius:9px; font-weight:700; font-size:13.5px; color:#fff; cursor:pointer;
                       padding: 10px 8px; white-space:nowrap;
                    background:linear-gradient(120deg,#2563eb,#7c3aed,#4f46e5,#8b5cf6);
                    background-size:280% 280%;
                    animation: mcShimmer 4s ease infinite, mcGlow 2.4s ease-in-out infinite; }
                .mc-ai-magic-btn:hover { filter:brightness(1.08) saturate(1.1); }
                .mc-spark { font-size:10px; line-height:1; color:#fde68a; animation: mcTwinkle 1.6s ease-in-out infinite; }
                .mc-spark-2 { animation-delay:.8s; }
                @keyframes mcShimmer { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
                @keyframes mcGlow { 0%,100%{ box-shadow:0 0 6px rgba(124,58,237,.5) } 50%{ box-shadow:0 0 22px rgba(124,58,237,.95) } }
                @keyframes mcTwinkle { 0%,100%{ opacity:.35; transform:scale(.8) } 50%{ opacity:1; transform:scale(1.25) } }
                @media (prefers-reduced-motion: reduce){ .mc-ai-magic-btn,.mc-spark{ animation:none } }
                @media (max-width:575px){
                    .mc-ai-input{ width:150px; }
                    .mc-ai-btn span{ display:none; } .mc-ai-btn{ padding:8px 11px; }
                    .mc-ai-magic-label{ display:none; }
                }
            </style>
        @endonce
        @once
            <script>
                (function () {
                    var el = document.getElementById('mcAiInput');
                    if (!el || el.value) return;
                    var ex = [
                        'my AC is not cooling',
                        'need a plumber for a leaking tap',
                        'best salon for a haircut near me',
                        'RO water purifier service in my area',
                        'electrician for a fan installation'
                    ], i = 0;
                    setInterval(function () {
                        if (document.activeElement === el || el.value) return;
                        i = (i + 1) % ex.length;
                        el.setAttribute('placeholder', 'Describe what you need — e.g. “' + ex[i] + '”');
                    }, 2600);
                })();

                // Search-mode toggle: swap between the keyword bar and the AI bar (one visible at a time).
                (function () {
                    var KEY = 'mcSearchMode';
                    var ai = document.getElementById('mcAiSearch');
                    var kw = document.getElementById('mcKeywordSearch');
                    if (!ai || !kw) return;

                    function apply(mode) {
                        var isAi = mode === 'ai';
                        ai.style.display = isAi ? 'flex' : 'none';
                        kw.style.display = isAi ? 'none' : 'flex';
                        try { localStorage.setItem(KEY, isAi ? 'ai' : 'keyword'); } catch (e) {}
                    }
                    // Default to KEYWORD search; honour the visitor's last choice.
                    var saved;
                    try { saved = localStorage.getItem(KEY); } catch (e) {}
                    apply(saved || 'keyword');

                    document.querySelectorAll('[data-mc-search-mode]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            var mode = btn.getAttribute('data-mc-search-mode');
                            apply(mode);
                            var focusEl = document.getElementById(mode === 'keyword' ? 'mainSearchbar' : 'mcAiInput');
                            if (focusEl) focusEl.focus();
                        });
                    });
                })();
            </script>
        @endonce
    @endif
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
                    <input type="search" class="form-control p-3 searchBarBtn" placeholder="keywords"
                        aria-describedby="search-icon-1" autocomplete="off">
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
