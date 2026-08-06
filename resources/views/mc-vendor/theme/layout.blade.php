@php($mc_login_url = $mc_login_url ?? 'https://vendor.mcvendorhub.com/login')
@php($mc_signup_url = $mc_signup_url ?? 'https://mychitti.net/list-your-business')
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

    <header>
        <nav>
            <a href="{{ mcv('vendor.mc-vendor.theme.home') }}"><img src="{{ asset('assets/mcvendorhub/img/logo-full.png') }}" alt="MC Vendor Hub" class="logo-img"></a>
            <div class="navlinks">
                <a href="{{ mcv('vendor.mc-vendor.theme.ai-employees') }}">AI Employees</a>
                <a href="{{ mcv('vendor.mc-vendor.theme.home') }}#modules">Core Platform</a>
                <a href="{{ mcv('vendor.mc-vendor.theme.whatsapp') }}">WhatsApp</a>
                <a href="{{ mcv('vendor.mc-vendor.theme.home') }}#verticals">Industries</a>
                <a href="{{ mcv('vendor.mc-vendor.price-calculator') }}">Pricing</a>
                <a href="{{ mcv('vendor.mc-vendor.theme.home') }}#faq">FAQ</a>
            </div>
            <div class="navcta">
                <a href="{{ $mc_login_url }}" class="btn btn-ghost btn-sm">Log In</a>
                <a href="{{ $mc_signup_url }}" class="btn btn-primary btn-sm">List Business Free</a>
            </div>
            <button class="navtoggle" id="navToggle" type="button" aria-expanded="false" aria-controls="mobileMenu" aria-label="Toggle navigation">
                <span></span><span></span><span></span>
            </button>
        </nav>
        <div class="mobile-menu" id="mobileMenu">
            <a href="{{ mcv('vendor.mc-vendor.theme.ai-employees') }}">AI Employees</a>
            <a href="{{ mcv('vendor.mc-vendor.theme.home') }}#modules">Core Platform</a>
            <a href="{{ mcv('vendor.mc-vendor.theme.whatsapp') }}">WhatsApp</a>
            <a href="{{ mcv('vendor.mc-vendor.theme.home') }}#verticals">Industries</a>
            <a href="{{ mcv('vendor.mc-vendor.price-calculator') }}">Pricing</a>
            <a href="{{ mcv('vendor.mc-vendor.blog-mc-vendor-hub') }}">Blog</a>
            <a href="{{ mcv('vendor.mc-vendor.contact') }}">Contact</a>
            <div class="mobile-cta">
                <a href="{{ $mc_login_url }}" class="btn btn-ghost btn-sm">Log In</a>
                <a href="{{ $mc_signup_url }}" class="btn btn-primary btn-sm">List Business Free</a>
            </div>
        </div>
    </header>

    @yield('content')

    <footer>
        <div class="wrap">
            <div class="footer-grid">
                <div>
                    <img src="{{ asset('assets/mcvendorhub/img/logo-full.png') }}" alt="MC Vendor Hub" class="footer-logo-img">
                    <p style="max-width:260px; color:#7C8AA0;">The all-in-one business operating system for India's MSMEs — built by My Chitti Technologies Pvt Ltd.</p>
                </div>
                <div>
                    <h5>Platform</h5>
                    <a href="{{ mcv('vendor.mc-vendor.theme.ai-employees') }}">AI Employees</a>
                    <a href="{{ mcv('vendor.mc-vendor.theme.home') }}#modules">Core Platform</a>
                    <a href="{{ mcv('vendor.mc-vendor.theme.whatsapp') }}">WhatsApp Business</a>
                    <a href="{{ mcv('vendor.mc-vendor.theme.home') }}#verticals">Industries</a>
                    <a href="{{ mcv('vendor.mc-vendor.price-calculator') }}">Pricing</a>
                </div>
                <div>
                    <h5>Company</h5>
                    <a href="{{ mcv('vendor.mc-vendor.blog-mc-vendor-hub') }}">Blog</a>
                    <a href="{{ mcv('vendor.mc-vendor.contact') }}">Contact</a>
                    <a href="{{ mcv('vendor.mc-vendor.price-calculator') }}">Price Calculator</a>
                    <a href="{{ $mc_signup_url }}">List Your Business</a>
                </div>
                <div>
                    <h5>Legal</h5>
                    <a href="{{ mcv('vendor.mc-vendor.mc-vendor-hub-tnc') }}">Terms &amp; Conditions</a>
                    <a href="{{ mcv('vendor.mc-vendor.mc-vendor-hub-pp') }}">Privacy Policy</a>
                    <a href="{{ mcv('vendor.mc-vendor.mc-vendor-hub-return-policy') }}">Refund &amp; Cancellation Policy</a>
                </div>
            </div>
            <div class="footer-bottom">
                <span>© {{ date('Y') }} My Chitti Technologies Pvt Ltd. All rights reserved.</span>
                <span>support@mychitti.net · 9951968473</span>
            </div>
        </div>
    </footer>

    <div class="float-stack">
        <a href="{{ mcv('vendor.mc-vendor.contact') }}" class="fab fab-demo">📅 Book Demo</a>
        <a href="{{ $mc_wa_url }}" target="_blank" rel="noopener" class="fab fab-wa" aria-label="Chat on WhatsApp">💬</a>
    </div>

    <script>
        document.querySelectorAll('.faq-q').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = btn.parentElement;
                const wasOpen = item.classList.contains('open');
                document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
                if (!wasOpen) item.classList.add('open');
            });
        });

        (function() {
            const toggle = document.getElementById('navToggle');
            const menu = document.getElementById('mobileMenu');
            if (!toggle || !menu) return;
            toggle.addEventListener('click', () => {
                const open = menu.classList.toggle('open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            menu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
                menu.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }));
        })();
    </script>

    @yield('scripts')

</body>

</html>
