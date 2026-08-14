@php($mc_login_url = $mc_login_url ?? 'https://vendor.mcvendorhub.com/login')
@php($mc_signup_url = $mc_signup_url ?? _vendorSignupUrl())
@php($mc_wa_url = $mc_wa_url ?? 'https://wa.me/919951968473')
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MC Vendor Hub — India\'s First Business Platform with AI Employees')</title>
    <meta name="description" content="@yield('meta_description', 'Manage Billing, POS, CRM, WhatsApp and hire AI Employees that work 24×7 for your business — all from one platform built for Indian MSMEs.')">
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/mcvendorhub/img/logo-mark.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/mcvendorhub/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/mcvendorhub/app.css') }}">
    @yield('styles')
</head>

<body>

    @include('mc-vendor.theme.partials._header')

    @yield('content')

    @include('mc-vendor.theme.partials._footer')

    <script>
        document.querySelectorAll('.faq-q').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = btn.parentElement;
                const wasOpen = item.classList.contains('open');
                document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
                if (!wasOpen) item.classList.add('open');
            });
        });
    </script>

    @yield('scripts')

</body>

</html>
