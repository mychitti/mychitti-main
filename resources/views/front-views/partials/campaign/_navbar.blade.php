 <!-- ══════════════════════════════════════
       HEADER
       ══════════════════════════════════════ -->
    <header class="site-header">
        <div class="container">
            <div class="header-inner">

                <!-- Logo -->
                <a href="{{route('campaign.index')}}" class="site-logo">
                    <div class="logo-mark">
                        <svg viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 2L16 6.5V12L9 16L2 12V6.5L9 2Z" />
                        </svg>
                    </div>
                    <span class="logo-text">CampaignName</span>
                </a>

                <!-- Desktop nav -->
                <nav class="site-nav" aria-label="Main navigation">
                    <a href="{{route('campaign.index')}}" class="nav-link active">Home</a>
                    <a href="{{route('campaign.how-it-works')}}" class="nav-link">How it works</a>
                    <a href="{{route('campaign.results')}}" class="nav-link">Results</a>
                    <a href="{{route('campaign.winners')}}" class="nav-link">Winners</a>
                    <a href="{{route('campaign.faq')}}" class="nav-link">FAQ</a>
                    <a href="{{route('campaign.tnc')}}" class="nav-link">T&amp;C</a>
                </nav>

                <!-- CTA -->
                <a href="{{route('campaign.enter')}}" class="btn-header-cta">Enter now →</a>

                <!-- Mobile toggle -->
                <button class="hamburger" aria-label="Open menu" onclick="openMobileNav()">
                    <span></span><span></span><span></span>
                </button>

            </div>
        </div>
    </header>

    <!-- ══════════════════════════════════════
       MOBILE NAV DRAWER
       ══════════════════════════════════════ -->
    <div class="mobile-nav" id="mobileNav" onclick="closeMobileNav(event)">
        <div class="mobile-nav-panel">
            <button class="mobile-nav-close" onclick="closeMobileNav()" aria-label="Close menu">✕</button>
            <a href="{{route('campaign.index')}}" class="mobile-nav-link">Home</a>
            <a href="{{route('campaign.how-it-works')}}" class="mobile-nav-link">How it works</a>
            <a href="{{route('campaign.results')}}" class="mobile-nav-link">Results</a>
            <a href="{{route('campaign.winners')}}" class="mobile-nav-link">Winners</a>
            <a href="{{route('campaign.faq')}}" class="mobile-nav-link">FAQ</a>
            <a href="{{route('campaign.tnc')}}" class="mobile-nav-link">T&amp;C</a>
            <a href="{{route('campaign.enter')}}" class="btn btn-primary btn-lg" style="margin-top:8px;justify-content:center;">Enter now
                →</a>
        </div>
    </div>