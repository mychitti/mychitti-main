<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>My Chitti SHOP | @yield('title')</title>
    <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="GKuvdu8PAKO0h9zq-kyGkm1OjAuHY44lWi01iSL40Sk" />
    @php($logo = \App\Models\BusinessSetting::where(['key' => 'icon'])->first()->value)
    <link rel="icon" type="image/x-icon" href="{{ asset('storage/app/public/business/' . $logo ?? '') }}">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <meta content="@yield('meta_keywords')" name="keywords">
    <meta content="@yield('meta_description')" name="description">
    @if (\Illuminate\Support\Str::contains(request()->getHost(), 'staging.mychitti.net'))
        <meta name="robots" content="noindex, nofollow"> <!-- no indexing of staging -->
    @endif

    <!-- Google tag (gtag.js) -->
    @include('front-views/partials/ads')

    @stack('meta_tags')

    <link rel="canonical" href="{{ url()->current() }}" />

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="{{ asset('public/assets/front/lib/lightbox/css/lightbox.min.css') }}" rel="stylesheet">
    <link href="{{ asset('public/assets/front/lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('public/assets/admin/intltelinput/css/intlTelInput.css') }}">

    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/intlTelInput.css') }}" />


    <!-- Customized Bootstrap Stylesheet -->
    <link href="{{ asset('public/assets/front/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="{{ asset('public/assets/front/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('public/assets/front/css/custom-carousel.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/toastr.css') }}">


    @stack('css_or_js')
    <style>
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

        .otp-input-phone {
            width: 55px;
            height: 55px;
            margin: 3px;
            text-align: center;
            font-size: 26px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .otp-input-phone:focus {
            border-color: #007bff;
            outline: none;
        }
    </style>
    <style>
        .base-template__wrapper {
            min-height: calc(100dvh - 300px);
            justify-content: flex-start;
            padding-bottom: 450px;
        }

        .wrapper {
            max-width: 1445px;
        }


        .header {
            display: flex;
            align-items: center;
            position: relative;
            padding: 0 20px 0 40px;
            border-radius: 60px;
            min-height: 82px;
            background-color: rgb(255, 255, 255);
        }

        .header__logo {
            max-width: 90px;
        }

        .header__wrapper {
            width: 100%;
            display: flex;
            align-items: center;
        }

        .header__navigation-wrapper {
            display: flex;
            width: 100%;
            padding-left: 50px;
        }

        .header__list {
            display: flex;
            align-items: center;
            gap: 28px;
            margin: 0;
            padding: 0;
        }

        .header__list-item {
            display: flex;
            padding-bottom: 24px;
            margin-bottom: -24px;
            gap: 8px;
            font-size: 16px;
        }

        .header__list-item>a {
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .header__list-item>a svg path {
            transition: var(--transition);
        }

        .header__list-item .submenu-wrapper {
            position: absolute;
            width: 100%;
            top: 110%;
            left: 0;
            border-radius: 33px;
            padding: 30px 30px 50px 30px;
            background-color: rgb(237 255 248);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: var(--transition);
        }

        .header__buttons-wrapper {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-left: auto;
        }

        .header__button {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            width: max-content;
            padding: 6px 20px;
            border-radius: 100px;
            gap: 8px;
            font-size: 16px;
            font-weight: 400;
            transition: var(--transition);
        }

        .header__burger {
            display: none;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
            width: 24px;
            margin-left: auto;
        }

        .header__burger i {
            width: 100%;
            height: 2px;
            background-color: #fff;
            border-radius: 13px;
            transition: var(--transition);
        }

        .header__burger.active i:nth-child(1) {
            transform: rotate(45deg) translate(4px, 4px);
        }

        .header__burger.active i:nth-child(2) {
            opacity: 0;
        }

        .header__burger.active i:nth-child(3) {
            transform: rotate(-45deg) translate(4px, -5px);
        }

        @media (hover: hover) and (pointer: fine) {
            .header__list-item:hover .submenu-wrapper {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
            }

            .header__list-item:hover~.header__list-item .submenu-wrapper {
                display: none;
            }

            {{-- .header__list-item:hover > a,
	.header__list-item:hover > a svg path {
		color: var(--color-primary);
		fill: var(--color-primary);
	} --}} .header__button:hover {
                background-color: rgba(255, 255, 255, 0.05);
            }

            .submenu-list__item.has-submenu:hover .submenu-list__item-wrapper {
                background-color: rgba(255, 255, 255, 0.04);
            }

            .submenu-list__item.has-submenu:hover .submenu-content,
            .submenu-list__item.has-submenu:hover .submenu-list__item-wrapper>svg {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
            }

            .submenu-content__list-item:hover .submenu-content__link {
                border-color: rgba(255, 255, 255, 0.3);
            }

            .submenu-content__list-item:hover .submenu-content__link-img img {
                transform: scale(1.05);
            }

            .submenu-content__url:hover,
            .submenu-content__url:hover svg path {
                color: var(--color-primary);
                stroke: var(--color-primary);
            }

            .submenu-content__url:hover svg {
                transform: translateX(5px);
            }
        }

        @media screen and (max-width: 1280px) {
            .header__navigation-wrapper {
                padding-left: 25px;
            }

            .submenu-list {
                max-width: 250px;
            }

            .submenu-content {
                max-width: calc(100% - 270px);
            }

            .submenu-content__url {
                margin-bottom: 0;
            }
        }

        @media screen and (max-width: 1024px) {
            .base-template__wrapper {
                min-height: 105vh;
            }

            .header {
                min-height: 64px;
                padding: 0 20px;
            }

            .header__burger {
                display: flex;
            }

            .header__navigation-wrapper {
                flex-direction: column;
                align-items: center;
                position: absolute;
                top: 110%;
                left: 0;
                padding: 20px;
                background-color: rgba(25, 27, 36, 1);
                border-radius: 20px;
                opacity: 0;
                visibility: hidden;
                transition: var(--transition);
            }

            .header__navigation-wrapper.open {
                opacity: 1;
                visibility: visible;
            }

            .header__list {
                flex-direction: column;
                gap: 30px;
            }

            .header__buttons-wrapper {
                flex-direction: column;
                margin-left: unset;
                margin-top: 50px;
                gap: 8px;
            }

            .header__navigation,
            .header__list {
                width: 100%;
            }

            .header__list-item {
                flex-direction: column;
                width: 100%;
                padding: 0;
                gap: 0;
                margin: 0;
            }

            .header__list-item.active a,
            .header__list-item.active a>svg path {
                fill: var(--color-primary);
                color: var(--color-primary);
            }

            .header__list-item .submenu-wrapper {
                position: static;
                padding: 0;
                max-height: 0;
                border-radius: 0;
                opacity: 1;
                visibility: visible;
                pointer-events: all;
                overflow: hidden;
                transition: max-height var(--transition);
            }

            .submenu-list {
                width: 100%;
                max-width: 100%;
                gap: 5px;
            }

            .submenu-list__wrapper {
                margin-top: 30px;
            }

            .submenu-list__item {
                width: 100%;
                padding: 0;
                margin: 0;
            }

            .submenu-list__item:active .submenu-list__item-wrapper {
                background-color: rgba(255, 255, 255, 0.04);
            }

            .submenu-list__item:active .submenu-list__item-wrapper>svg {
                opacity: 1;
                visibility: visible;
            }

            .submenu-list__title {
                display: none;
            }

            .submenu-content {
                display: none;
            }

            .header__button {
                border: 1px solid rgba(255, 255, 255, 1);
            }
        }

        @media screen and (max-width: 767.9px) {

            .header__buttons-wrapper,
            .header__button {
                width: 100%;
            }
        }









        .maintenance-notice {
            background-color: #ffcc00;
            color: #000;
            padding: 15px;
            text-align: center;
            font-size: 1.1rem;
            font-weight: bold;
            border: 2px solid #ffa500;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            position: fixed;
            top: 58px;
            width: 100%;
            z-index: 9999;
        }

        {{-- body {
            margin-top: 70px;
            /* prevent content from being hidden behind the banner */
        } --}} #map2 {
            height: 300px;
            width: 100%;
        }

        .pagination {
            display: flex;
        }

        .pagination span {
            padding: 10px 16px;
        }

        #autocomplete ul li,
        #autocomplete2 ul li {
            padding: 10px 20px;
        }

        #autocomplete ul li:hover,
        #autocomplete2 ul li:hover,
        .autocomplete3 ul li:hover {
            background-color: #edfddd;
        }
    </style>

</head>

<body>
    @if (0 && request()->getHost() != 'staging.mychitti.net')
        <div id="preloader">
            @php($store_logo = \App\Models\BusinessSetting::where(['key' => 'logo'])->first()->value)
            <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/app/public/business/') . '/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'business/') }}"
                alt="Loading..." id="preloader-img">
        </div>
    @endif
    @include('front-views.partials._navbar')

    @yield('content')

    @include('front-views.partials._footer')
    <div id="toast" class="toast">This is a toaster notification!</div>


    <!-- <div class="modal fade show" id="mapModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModalLabel" aria-modal="true" role="dialog" style="display: block;"> -->

    {{-- @include('front-views.partials._searchbar') --}}
    <div class="modal fade " id="mapModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Location</h5>
                    @if (session()->has('latitude') && session()->has('longitude'))
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    @endif
                </div>
                <div class="modal-body">
                    <input type="text" id="searchInput" class="form-control">
                    <div id="map2"></div>
                    <input type="hidden" id="user_city" class="form-control">
                    <input type="hidden" id="user_address" class="form-control">
                    <input type="hidden" id="user_latitude">
                    <input type="hidden" id="user_longitude">
                </div>
                <div class="modal-footer">
                    @if (session()->has('latitude') && session()->has('longitude'))
                        <button type="button" class="btn btn-grey" data-bs-dismiss="modal">Discard</button>
                    @endif
                    <button type="button" onclick="saveLocation()" id="locBtn" class="btn btn-primary">Update
                        Location</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mobile Verification</h5>
                </div>
                <div class="modal-body">

                    <form action="{{ route('phone-save') }}" class="phone_num_save mobile-elem" method="post">
                        <label for="phon_num">Phone Number</label>
                        <input type="number" name="phone" class="form-control" id="phone_num">
                        <span class="phone_err"></span>
                        <div class="d-flex justify-content-end w-100 my-2">
                            <button class="btn btn-primary text-white">Proceed</button>
                        </div>
                    </form>
                    <div class="otp-elem" style="display:none">
                        <form class="phone_num_save verify_otp"
                            style="margin: 0 auto;    display: flex;flex-direction: column;gap: 10px;    align-items: center;"
                            action="{{ route('verify-phone-otp') }}" method="post">
                            @csrf
                            <h4 class="text-center">Enter OTP</h4>
                            <p class="text-center">OTP has been sent to <span id="ver_phone_num2"></span></p>
                            <input type="hidden" name="phone" id="ver_phone_num" value="">
                            <input type="hidden" class="service_enquiry" value="">
                            <input type="hidden" class="storeId" value="">
                            <div class="d-flex justify-content-center">
                                <input type="number" maxlength="1" class="otp-input-phone" name="otp[]" />
                                <input type="number" maxlength="1" class="otp-input-phone" name="otp[]" />
                                <input type="number" maxlength="1" class="otp-input-phone" name="otp[]" />
                                <input type="number" maxlength="1" class="otp-input-phone" name="otp[]" />
                            </div>

                            <button type="submit" class="btn btn-primary"
                                style="width: fit-content;">Submit</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('public/assets/front/lib/easing/easing.min.js') }}"></script>
    <script src="{{ asset('public/assets/front/lib/waypoints/waypoints.min.js') }}"></script>
    <script src="{{ asset('public/assets/front/lib/lightbox/js/lightbox.min.js') }}"></script>
    <script src="{{ asset('public/assets/front/lib/owlcarousel/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('public/assets/admin') }}/js/toastr.js"></script>
    <!-- Template Javascript -->
    <script src="{{ asset('public/assets/front/js/main.js') }}"></script>
    <script src="{{ asset('public/assets/front/js/custom.js') }}"></script>
    <script src="{{ asset('public/assets/front/js/custom-carousel.js') }}"></script>

    <script src="https://mychitti.net/public/assets/admin/intltelinput/js/intlTelInput.min.js"></script>

    <script src="{{ asset('public/assets/landing/js/select2.min.js') }}"></script>
    {!! Toastr::message() !!}

    @if ($errors->any())
        <script>
            @foreach ($errors->all() as $error)
                toastr.error('{{ translate($error) }}', Error, {
                    CloseButton: true,
                    ProgressBar: true
                });
            @endforeach
        </script>
    @endif

    @stack('script')



    @stack('script_2')

    <script>
$(document).ready(function () {

    const words = [' Service', ' Keyword', ' Category', ' Store'];
    let index = 0;

    const $textEl = $('#changingText');
    const $inputEl = $('#mainSearchbar');
    const $placeholderEl = $('#animatedPlaceholder');

    setInterval(function () {

        // hide animation when user types
        if ($inputEl.val().trim() !== '') {
            $placeholderEl.hide();
            return;
        } else {
            $placeholderEl.css('display', 'flex');
        }

        index = (index + 1) % words.length;

        // restart animation
        $textEl.removeClass('slide-up');

        // force reflow (jQuery-safe)
        $textEl[0].offsetWidth;

        $textEl.text(words[index]).addClass('slide-up');

    }, 2500);

});
</script>


    <script>
        $("#mainSearchbar").on('focus', function() {
            $('#search_placeholder').show()
        });
        var phoneModal = new bootstrap.Modal(document.getElementById('myModal'));

        $('.phone_num_save').on('submit', function(e) {
            e.preventDefault()
            var action = $(this).hasClass('verify_otp') ? 'verify-otp' : 'send-otp';
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: $(this).attr('action'),
                data: {
                    phone: $('#phone_num').val(),
                    action: action,
                    otp: $(".otp-input-phone").map(function() {
                        return $(this).val();
                    }).get().join('')
                },
                beforeSend: function() {
                    action == 'verify-otp' ? $('.verify_otp').css('pointer-events', 'none') : $(
                        ".mobile-elem").css('pointer-events', 'none')
                },
                success: function(data) {
                    console.log(data)
                    $('.phone_err').text('')
                    if (data.message == 'exists') {
                        $('.phone_err').text(
                            'This phone number is already registered with another account.')
                    } else if (data.message == 'otp_sent') {
                        $("#ver_phone_num").val(data.phone)
                        $("#ver_phone_num2").text(data.phone)
                        $(".otp-elem").show()
                        $(".mobile-elem").hide()
                    } else if (data.message == 'verified') {
                        var serviceId = $(".service_enquiry").val()
                        var storeId = $(".storeId").val()
                        phoneModal.hide();

                        bookService(serviceId, null, storeId)
                        $(".phone_num_save").trigger('reset')
                    } else if (data.message == 'invalid_opt') {
                        toasterNotification(data.message);
                    } else {
                        toasterNotification(data.message);
                    }
                    $('.verify_otp').css('pointer-events', 'all')
                    $(".mobile-elem").css('pointer-events', 'all')


                    console.log(data)
                }
            });
        })

        $(document).on('input', '.otp-input-phone', function(e) {
            const $inputs = $('.otp-input-phone');
            const index = $inputs.index(this);

            if (this.value.length === this.maxLength && index < $inputs.length - 1) {
                $inputs.eq(index + 1).focus();
            }
        });

        function bookService(serviceId, elem, storeId = null) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('book-service') }}",
                data: {
                    serviceId: serviceId,
                    storeId: storeId
                },
                beforeSend: function() {
                    $(elem).html(
                        "<img  style='width: 15px;' src='{{ asset('public/assets/front/lib/lightbox/images/loading.gif') }}'>"
                    );
                    {{-- $(elem).css('pointer-events', 'none'); --}}
                },
                success: function(data) {
                    if (data.message == 'phone_missing') {

                        // enter phone and make enquiry
                        phoneModal.show();
                        $(".service_enquiry").val(serviceId)
                        $(".storeId").val(storeId)


                    } else {
                        toasterNotification(data.message);
                        $(elem).html('&check;');
                    }
                    setTimeout(() => {
                        $(elem).html('<i class="fas fa-user-cog"></i> Enquiry Now');
                    }, 1000);
                },
                complete: function() {

                    $(elem).css('pointer-events', 'all');
                }
            });
        }

        $(document).ready(function() {
            $("#togglePassword").click(function() {
                let passwordInput = $("#password");
                let icon = $(this);

                if (passwordInput.attr("type") === "password") {
                    passwordInput.attr("type", "text");
                    icon.removeClass("fa-eye-slash").addClass("fa-eye");
                } else {
                    passwordInput.attr("type", "password");
                    icon.removeClass("fa-eye").addClass("fa-eye-slash");
                }
            });
        });
        $('#keywords').select2()

        let searchTimeout = null;
        let currentRequest = null;

        $("#searchBarBtn").on('keyup', function() {
            searchBar('autocomplete', this);
        });

        function searchBar(resultElem, elem) {
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            if (currentRequest && currentRequest.readyState !== 4) {
                currentRequest.abort();
            }

            if ($(elem).val() == '') {
                $('#' + resultElem).html('');
                $('#loading').hide();
                return;
            }

            if ($(elem).val().length >= 2) {
                searchTimeout = setTimeout(function() {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    currentRequest = $.get({
                        url: "{{ route('searchbar-web') }}",
                        data: {
                            keyword: $(elem).val()
                        },
                        beforeSend: function() {
                            $('#loading').show();
                        },
                        success: function(data) {
                            $('#' + resultElem).html(data.html);
                        },
                        error: function(xhr, status, error) {
                            if (status !== 'abort') {
                                console.error('Search error:', error);
                            }
                        },
                        complete: function(xhr, status) {
                            $("#search_placeholder").hide();

                            if (status !== 'abort') {
                                $('#loading').hide();
                            }
                        }
                    });
                }, 300);
            } else {
                $("#search_placeholder").show();
                $("#search_results3").html('')
            }
        }

        function performSearch(resultElem, keyword) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            currentRequest = $.ajax({
                url: "{{ route('searchbar-web') }}",
                method: 'GET',
                data: {
                    keyword: keyword
                },
                dataType: 'json',
                cache: true, // Enable browser caching for repeated searches
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    if (data.status) {
                        $('#' + resultElem).html(data.html);
                    } else {
                        $('#' + resultElem).html(data.html);
                    }
                },
                error: function(xhr, status, error) {
                    if (status !== 'abort') {
                        console.error('Search error:', error);
                        $('#' + resultElem).html(
                            '<div class="text-center mt-3 text-danger">Search error. Please try again.</div>'
                        );
                    }
                },
                complete: function(xhr, status) {
                    if (status !== 'abort') {
                        $('#loading').hide();
                    }
                }
            });
        }

        // Optional: Add keyboard navigation for results
        $(document).on('keydown', '#search_input', function(e) {
            const $results = $('#search_results3 li');
            const $active = $results.filter('.active');
            let $next;

            if (e.keyCode === 40) { // Down arrow
                e.preventDefault();
                if ($active.length === 0) {
                    $next = $results.first();
                } else {
                    $next = $active.next('li');
                }
                $results.removeClass('active');
                $next.addClass('active');
            } else if (e.keyCode === 38) { // Up arrow
                e.preventDefault();
                if ($active.length !== 0) {
                    $next = $active.prev('li');
                    $results.removeClass('active');
                    $next.addClass('active');
                }
            } else if (e.keyCode === 13) { // Enter
                e.preventDefault();
                if ($active.length !== 0) {
                    $active.find('a')[0].click();
                }
            }
        });

        function searchBar_old(resultElem, elem) {
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            if (currentRequest && currentRequest.readyState !== 4) {
                currentRequest.abort();
            }

            if ($(elem).val() == '') {
                $('#' + resultElem).html('');
                $('#loading').hide();
                return;
            }

            if ($(elem).val().length >= 2) {
                searchTimeout = setTimeout(function() {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    currentRequest = $.get({
                        url: "{{ route('searchbar-web') }}",
                        data: {
                            keyword: $(elem).val()
                        },
                        beforeSend: function() {
                            $('#loading').show();
                        },
                        success: function(data) {
                            $('#' + resultElem).html(data.html);
                            $("#search_placeholder").hide();

                        },
                        error: function(xhr, status, error) {
                            // Ignore aborted requests
                            if (status !== 'abort') {
                                console.error('Search error:', error);
                            }
                        },
                        complete: function(xhr, status) {
                            // Only hide loading if request wasn't aborted
                            if (status !== 'abort') {
                                $('#loading').hide();
                            }
                        }
                    });
                }, 300); // Wait 300ms after last keypress
            } else {
                $("#search_placeholder").show();
                $("#search_results3").html('')
            }
        }
    </script>

    <script>
        function updateCart(prId, action, variation, cart_id) {
            if (action === 'add') {
                // Check store cart
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.post({
                    url: "{{ route('check-cart') }}",
                    data: {
                        cart_id: cart_id,
                        prId: prId
                    },
                    success: function(data) {
                        if (!data) {
                            Swal.fire({
                                title: 'Are you sure you want to reset?',
                                text: 'If you want to change store type it will reset your cart.',
                                type: 'warning',
                                showCancelButton: true,
                                cancelButtonColor: 'default',
                                confirmButtonColor: '#FC6A57',
                                cancelButtonText: 'No',
                                confirmButtonText: 'Yes',
                                reverseButtons: true
                            }).then((result) => {
                                if (result.value) {
                                    proceedToCartUpdate(prId, action, variation, cart_id);
                                }
                            });
                        } else {
                            proceedToCartUpdate(prId, action, variation, cart_id);
                        }
                    }
                });
            } else {
                proceedToCartUpdate(prId, action, variation, cart_id);
            }
        }

        function proceedToCartUpdate(prId, action, variation, cart_id) {
            var url = action === 'add' ? "{{ route('add-to-cart') }}" : "{{ route('remove-from-cart') }}";

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
                    $('#loading').show();
                },
                success: function(data) {
                    console.log(data.firstvr);
                    if (data.status) {
                        toasterNotification(data.message);
                        var cartBtn = action === 'add' ?
                            `<a onclick="updateCart(${prId}, 'remove', '', '${data.cart_id}')" class="btn border border-secondary rounded p-1 px-2 text-primary   fs-5"><i class="fa fa-times me-2 text-primary "></i>Remove</a>` :
                            `<a onclick="updateCart(${prId}, 'add', '${data.firstVr}', '')" class="btn border border-secondary rounded p-1 px-2 text-primary fs-5"><i class="fa fa-shopping-bag me-2 text-primary"></i>Add</a>`;
                        $(".cartSec_" + prId).html(cartBtn);
                        $('.cart-count-outer').load(window.location.href + ' .cart-count-inner');
                        $('.cart-outer').load(window.location.href + ' .cart-inner');
                    } else {
                        toasterNotification(data.message);
                    }
                },
                complete: function() {
                    $('#loading').hide();
                }
            });
        }


        function wishlist(prid, action) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('update-wishlist') }}',
                data: {
                    item_id: prid,
                    store_id: '',
                    type: 'item',
                    action: action
                },
                beforeSend: function() {},
                success: function(data) {
                    if (data.status) {
                        if (action == 'add') {
                            $('.heart_' + prid).addClass('text-danger')
                            $('.prHeart_' + prid).attr('onclick', 'wishlist(' + prid + ', "remove")');
                        } else {
                            $('.heart_' + prid).removeClass('text-danger')
                            $('.prHeart_' + prid).attr('onclick', 'wishlist(' + prid + ', "add")');
                        }
                    }
                    toasterNotification(data.message)
                },
                complete: function() {}
            });
        }


        function updateStoreWishlist(storeId, action) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('update-wishlist') }}',
                data: {
                    store_id: storeId,
                    item_id: '',
                    type: 'store',
                    action: action
                },
                success: function(data) {
                    if (data.status) {
                        if (action == 'add') {
                            $('.Sheart_' + storeId).addClass('text-danger')
                            $('.SprHeart_' + storeId).attr('onclick', 'updateStoreWishlist(' + storeId +
                                ', "remove")');
                        } else {
                            $('.Sheart_' + storeId).removeClass('text-danger')
                            $('.SprHeart_' + storeId).attr('onclick', 'updateStoreWishlist(' + storeId +
                                ', "add")');
                        }
                    }
                    toasterNotification(data.message)
                },
            });
        }
    </script>
</body>
@if (
    !request()->is('list-your-business') &&
        !request()->is('store*') &&
        !request()->is('checkout') &&
        !request()->is('edit-address/*'))

    <script>
        function saveLocation() {
            console.log('save location');
            $('#user_city2').text($('#user_city').val());
            $('body').css('pointer-events', 'none');

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.post({
                url: "{{ route('update-location') }}",
                data: {
                    latitude: $('#user_latitude').val(),
                    longitude: $('#user_longitude').val(),
                    address: $('#user_address').val(),
                    city: $('#user_city').val(),
                },
                beforeSend: function() {
                    $('#locBtn').html(
                        `<img style='filter: brightness(0.6); width: 26px;' src='{{ asset('public/assets/front/lib/lightbox/images/loading.gif') }}'>`
                    );
                },
                success: function(data) {},
                complete: function() {
                    $('#locBtn').html('Update Location');
                    window.location.reload();
                }
            });
        }

        function loadScript(src, callback) {
            const script = document.createElement('script');
            script.type = 'text/javascript';
            script.async = true;
            script.src = src;
            script.onload = callback;
            document.head.appendChild(script);
        }

        function initMap(latitude, longitude) {
            console.log("🗺️ Initializing map with:", latitude, longitude);

            const initialLocation = {
                lat: latitude,
                lng: longitude
            };
            const map = new google.maps.Map(document.getElementById('map2'), {
                zoom: 10,
                center: initialLocation
            });

            const marker = new google.maps.Marker({
                position: initialLocation,
                map: map,
                draggable: true
            });

            const geocoder = new google.maps.Geocoder();

            function geocodeLatLng(lat, lng) {
                console.log('📍 Geocoding:', lat, lng);
                geocoder.geocode({
                    location: {
                        lat,
                        lng
                    }
                }, function(results, status) {
                    if (status === 'OK' && results[0]) {
                        let locality = null;
                        for (const comp of results[0].address_components) {
                            if (comp.types.includes('locality')) {
                                locality = comp.long_name;
                                break;
                            }
                        }

                        const address = results[0].formatted_address;

                        $('#user_city').val(locality).attr('title', locality);
                        $('#user_location').text(address).attr('title', address);
                        $('#user_address, #searchInput').val(address);
                        $('#user_latitude').val(lat);
                        $('#user_longitude').val(lng);
                    } else {
                        console.warn('Geocoder failed due to:', status);
                    }
                });
            }

            geocodeLatLng(latitude, longitude);

            google.maps.event.addListener(marker, 'dragend', function(event) {
                geocodeLatLng(event.latLng.lat(), event.latLng.lng());
            });

            const input = document.getElementById('searchInput');
            const autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.bindTo('bounds', map);

            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();
                if (!place.geometry) return alert("No details available for input: " + place.name);
                map.setCenter(place.geometry.location);
                map.setZoom(17);
                marker.setPosition(place.geometry.location);
                geocodeLatLng(place.geometry.location.lat(), place.geometry.location.lng());
            });
        }

        function getCurrentLocation() {
            var defaultLat = 13.6287557;
            var defaultLng = 79.4191795;

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        console.log('Device lat:', position.coords.latitude);
                        console.log('Device lng:', position.coords.longitude);

                        @if (session()->has('latitude') && session()->has('longitude'))
                            initMap(parseFloat("{{ session('latitude') }}"), parseFloat(
                                "{{ session('longitude') }}"));
                        @else
                            initMap(position.coords.latitude, position.coords.longitude);
                        @endif
                    },
                    function(error) {
                        console.warn("Geolocation error:", error.message);
                    }
                );
            } else {
                @if (session()->has('latitude') && session()->has('longitude'))
                    initMap(parseFloat("{{ session('latitude') }}"), parseFloat("{{ session('longitude') }}"));
                @else
                    initMap(defaultLat, defaultLng);
                @endif
            }
        }


        function init() {
            loadScript(
                "https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&callback=getCurrentLocation"
            );
        }

        document.addEventListener("DOMContentLoaded", init);


        function changeModule(moduleId) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('change-module') }}",
                data: {
                    moduleId: moduleId
                },
                success: function(data) {
                    toasterNotification(data.message);
                    setTimeout(() => {
                        if (moduleId == 5) {
                            window.location.href = "{{ route('home') }}";

                        } else {
                            window.location.href = "{{ route('home') }}";
                        }
                    }, 100);
                },
                complete: function() {}
            });
        }

        $(document).ready(function() {
            $(".nav-cat-carousel").owlCarousel({
                scrollPerPage: true,
                autoplayHoverPause: false,
                mouseDrag: true,
                touchDrag: true,
                autoplay: true,
                smartSpeed: 500,
                responsive: {
                    0: {
                        items: 6
                    },
                    850: {
                        items: 11
                    },
                    1200: {
                        items: 20
                    }
                },
                loop: true,
                margin: 10,
                // nav: true
            });
            $(".nav-cat-carousel2").owlCarousel({
                scrollPerPage: true,
                autoplayHoverPause: false,
                mouseDrag: true,
                touchDrag: true,
                autoplay: true,
                rtl: true,
                smartSpeed: 500,
                responsive: {
                    0: {
                        items: 6
                    },
                    850: {
                        items: 11
                    },
                    1200: {
                        items: 20
                    }
                },
                loop: true,
                margin: 10,
                // nav: true
            });
            $(".cat-carousel").owlCarousel({
                items: 4.5, // Number of items to display
                loop: true,
                margin: 5,
                // nav: true
            });
            $(".feature_stores").owlCarousel({
                loop: true,
                margin: 10,
                nav: true,
                navText: ["Prev", "Next"],
                responsive: {
                    0: {
                        items: 1.2
                    },
                    850: {
                        items: 2
                    },
                    1200: {
                        items: 3
                    }
                }
            });
        });
    </script>
    @if (!session()->has('latitude') || !session()->has('longitude'))
        <script>
            $(document).ready(function() {
                $('#mapModal').modal('show');
            });
        </script>
    @endif
@endif
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
{{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> --}}

<script>
    $('#searchbarModal').on('shown.bs.modal', function() {
        setTimeout(function() {

            $('#mainSearchbar').focus();
        }, 50);
    });

    $(document).on('click', '#autocomplete3 ul li a', function(event) {
        event.preventDefault();

        let searchText = $(this).text().trim();
        let url = $(this).attr('href');

        if (searchText !== '') {
            saveSearchItem(searchText, url); // Save the search item and pass both `text` and `url`
        }
        window.location.href = url;
    });

    function saveSearchItem(text, url) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // CSRF token for security
            }
        });

        $.post({
            url: "{{ route('save-recent-search') }}", // Laravel route for saving the recent search
            data: {
                text: text,
                url: url
            }
        });
    }
    $(document).ready(function() {
        fetchRecentSearches();

        // Redirect to search when a chip is clicked
        $(document).on('click', '.recent-search-chips .chip', function() {
            const url = $(this).data('url');
            if (url) window.location.href = url;
        });

        // Clear all recent searches
        $(document).on('click', '.clear-all', function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                        'content') // CSRF token for security
                }
            });
            $.post("{{ route('clear-recent-searches') }}", function(res) {
                $('#recent-searches').empty();
            });
        });
    });

    function fetchRecentSearches() {
        $.get("{{ route('get-recent-searches') }}", function(data) {
            const container = $('#recent-searches');
            container.empty();

            if (data.length > 0) {
                data.forEach(item => {
                    container.append(
                        `<div class="chip" data-url="${item.url}"><i class="fas fa-history"></i> ${item.text}</div>`
                    );
                });
                $(".recent_search_title").show();
                container.append(
                    `<div class="clear-all text-danger"><i class="fas fa-trash "></i>Clear all</div>`);
            }
        });
    }
    $(document).on('click', function(event) {
        if (!$(event.target).closest('#mainSearchbar, #autocomplete3').length) {
            $('#autocomplete3').hide();
        }
    });
    $('#mainSearchbar').on('focus', function() {
        $('#autocomplete3').show(); // or .slideDown()
    });
</script>
<script>
    $(window).on('load', function() {
        const $preloader = $('#preloader');
        $preloader.addClass('hidden');
        setTimeout(() => $preloader.remove(), 500); // delay must match transition duration
    });





    window.addEventListener("DOMContentLoaded", () => {
        const burger = document.querySelector(".header__burger");
        const navWrapper = document.querySelector(".header__navigation-wrapper");

        if (burger && navWrapper) {
            burger.addEventListener("click", () => {
                burger.classList.toggle("active");
                navWrapper.classList.toggle("open");
            });
        }

        document.querySelectorAll(".header__list-item.has-submenu").forEach((item) => {
            item.addEventListener("click", () => {
                if (window.innerWidth > 1025) return;

                item.classList.toggle("active");

                const submenuWrapper = item.querySelector(".submenu-wrapper");

                if (submenuWrapper.style.maxHeight) {
                    submenuWrapper.style.maxHeight = null;
                } else {
                    submenuWrapper.style.maxHeight = `${submenuWrapper.scrollHeight}px`;
                }
            });
        });

        window.addEventListener("resize", () => {
            if (window.innerWidth <= 1025) {
                document
                    .querySelectorAll(".header__list-item.has-submenu.active")
                    .forEach((item) => {
                        const submenuWrapper = item.querySelector(".submenu-wrapper");

                        if (submenuWrapper) {
                            submenuWrapper.style.maxHeight = `${submenuWrapper.scrollHeight}px`;
                        }
                    });
            } else {
                document.querySelectorAll(".submenu-wrapper").forEach((wrapper) => {
                    wrapper.style.maxHeight = null;
                });
            }
        });

        document.querySelectorAll('a[href="#"]').forEach((link) => {
            link.addEventListener("click", (e) => {
                e.preventDefault();
            });
        });

        const submenuWrappers = document.querySelectorAll(".submenu-wrapper");

        submenuWrappers.forEach((submenuWrapper) => {
            const submenuItems = submenuWrapper.querySelectorAll(
                ".submenu-list__item.has-submenu"
            );
            const defaultActiveItem = submenuWrapper.querySelector(
                ".submenu-list__item.has-submenu.active"
            );

            let returnTimeout;

            submenuItems.forEach((item) => {
                item.addEventListener("mouseenter", () => {
                    clearTimeout(returnTimeout);

                    submenuItems.forEach((i) => i.classList.remove("active"));

                    item.classList.add("active");
                });
            });

            submenuWrapper.addEventListener("mouseleave", () => {
                submenuItems.forEach((i) => i.classList.remove("active"));

                returnTimeout = setTimeout(() => {
                    if (defaultActiveItem) {
                        defaultActiveItem.classList.add("active");
                    }
                }, 300);
            });
        });
    });
</script>



</html>
