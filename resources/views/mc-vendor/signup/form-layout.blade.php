{{-- Layout for the long listing form.

     It wears the MC Vendor Hub header, footer and theme CSS, exactly like the marketing pages and
     the new start page — the form used to sit inside MyChitti's consumer layout, complete with the
     shop search bar and "Top Products" footer, which is the wrong shop window for a page selling
     the platform to a business.

     It is a layout of its own rather than mc-vendor.theme.layout because the form arrived carrying
     MyChitti's plumbing: it pushes to `css_or_js` / `script_2` and expects jQuery, Bootstrap,
     toastr, select2 and intlTelInput to have been loaded for it by the layout above. The theme
     layout offers none of that (it yields `styles` / `scripts` and ships no libraries), so pointing
     the form straight at it would render the markup and break every interaction on the page. Those
     libraries stay here rather than moving into the theme layout, so the marketing pages are not
     made to carry a stack they never use. --}}
@php($mc_login_url = $mc_login_url ?? 'https://vendor.mcvendorhub.com/login')
@php($mc_signup_url = $mc_signup_url ?? _vendorSignupUrl())
@php($mc_wa_url = $mc_wa_url ?? 'https://wa.me/919951968473')
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'List Your Business — MC Vendor Hub')</title>
    <meta name="description" content="@yield('meta_description', 'List your business on My Chitti and reach customers in your city. Free to start.')">
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/mcvendorhub/img/logo-mark.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    {{-- The vendor hub theme, so the header, footer and typography match the rest of the site. --}}
    <link rel="stylesheet" href="{{ asset('assets/mcvendorhub/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/mcvendorhub/app.css') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">

    {{-- The phone field's country picker: the form includes partials.tel_input, which calls
         window.intlTelInput and needs both of these stylesheets to look like anything. --}}
    <link rel="stylesheet" href="{{ asset('assets/admin/intltelinput/css/intlTelInput.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/intlTelInput.css') }}">

    @stack('css_or_js')

    <style>
        /* The form was drawn against a white page; the theme's tinted body would show through
           between its cards. */
        body {
            background: #fff;
        }

        /* The map's "use my location" button is positioned against the map, which the theme's
           own layout rules would otherwise reflow. */
        #currentLocationBtn,
        .current-loc-btn-map {
            position: absolute;
            right: 25px;
            bottom: 160px;
            background: #fff;
            border-radius: 50%;
            padding: 10px 12px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
            z-index: 999;
        }

        /* toasterNotification() in the form writes into this. */
        #toast {
            visibility: hidden;
            min-width: 250px;
            margin-left: -125px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 16px;
            position: fixed;
            z-index: 1111;
            left: 50%;
            bottom: 30px;
            font-size: 17px;
            opacity: 0;
            transition: opacity .5s, bottom .5s;
        }

        #toast.show {
            visibility: visible;
            opacity: 1;
            bottom: 50px;
        }
    </style>
</head>

<body>

    @include('mc-vendor.theme.partials._header')

    @yield('content')

    <div id="toast"></div>

    @include('mc-vendor.theme.partials._footer')

    {{-- Everything below was previously handed to this form by MyChitti's front layout. Removing
         any of it silently breaks a step of the wizard rather than the page as a whole. --}}
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/admin/js/toastr.js') }}"></script>
    <script src="{{ asset('assets/landing/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/admin/intltelinput/js/intlTelInput.min.js') }}"></script>

    @if (session()->has('success'))
        <script>
            toastr.success(@json(session('success')), '', { CloseButton: true, ProgressBar: true });
        </script>
    @endif
    @if (session()->has('error'))
        <script>
            toastr.error(@json(session('error')), '', { CloseButton: true, ProgressBar: true });
        </script>
    @endif
    @if ($errors->any())
        <script>
            @foreach ($errors->all() as $error)
                toastr.error(@json($error), '', { CloseButton: true, ProgressBar: true });
            @endforeach
        </script>
    @endif

    @stack('script')
    @stack('script_2')
</body>

</html>
