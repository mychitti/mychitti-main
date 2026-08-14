{{-- MC Vendor Hub site header. Shared by the marketing theme layout and the signup layout, so
     the registration pages wear the same chrome as the rest of mcvendorhub.com rather than
     MyChitti's consumer header. Expects $mc_login_url and $mc_signup_url from the layout. --}}
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

{{-- Travels with the markup it drives, so every layout including this header gets a working
     mobile menu without repeating the wiring. --}}
<script>
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
