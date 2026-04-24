<header class="site-header">
    <div class="container">
        <div class="header-inner">

            <!-- Logo -->
            <a href="{{ $campaign ? route('campaign.index', $campaign->slug) : route('campaigns.list') }}" class="site-logo">
                <div class="logo-mark">
                    <svg viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 2L16 6.5V12L9 16L2 12V6.5L9 2Z" />
                    </svg>
                </div>
                <span class="logo-text">{{ $campaign?->name ?? 'Campaigns' }}</span>
            </a>

            @if($campaign)
            <!-- Desktop nav — campaign-specific -->
            <nav class="site-nav" aria-label="Main navigation">
                <a href="{{ route('campaign.index', $campaign->slug) }}"         class="nav-link">Home</a>
                <a href="{{ route('campaign.how-it-works', $campaign->slug) }}"  class="nav-link">How it works</a>
                @if($campaign->sponsors->isNotEmpty())
                <a href="{{ route('campaign.sponsors', $campaign->slug) }}"       class="nav-link">Sponsors</a>
                @endif
                <a href="{{ route('campaign.results', $campaign->slug) }}"        class="nav-link">Results</a>
                <a href="{{ route('campaign.winners', $campaign->slug) }}"        class="nav-link">Winners</a>
                <a href="{{ route('campaign.faq', $campaign->slug) }}"            class="nav-link">FAQ</a>
                <a href="{{ route('campaign.tnc', $campaign->slug) }}"            class="nav-link">T&amp;C</a>
                <a href="{{ route('campaigns.list') }}"                           class="nav-link">All campaigns</a>
            </nav>
            <a href="{{ route('campaign.enter', $campaign->slug) }}" class="btn-header-cta">Enter now →</a>
            @else
            <!-- Desktop nav — listing page -->
            <nav class="site-nav" aria-label="Main navigation">
                <a href="{{ route('campaigns.list') }}" class="nav-link active">All campaigns</a>
            </nav>
            @endif

            <!-- Mobile toggle -->
            <button class="hamburger" aria-label="Open menu" onclick="openMobileNav()">
                <span></span><span></span><span></span>
            </button>

        </div>
    </div>
</header>

<!-- Mobile nav drawer -->
<div class="mobile-nav" id="mobileNav" onclick="closeMobileNav(event)">
    <div class="mobile-nav-panel">
        <button class="mobile-nav-close" onclick="closeMobileNav()" aria-label="Close menu">✕</button>
        @if($campaign)
            <a href="{{ route('campaign.index', $campaign->slug) }}"        class="mobile-nav-link">Home</a>
            <a href="{{ route('campaign.how-it-works', $campaign->slug) }}" class="mobile-nav-link">How it works</a>
            @if($campaign->sponsors->isNotEmpty())
            <a href="{{ route('campaign.sponsors', $campaign->slug) }}"      class="mobile-nav-link">Sponsors</a>
            @endif
            <a href="{{ route('campaign.results', $campaign->slug) }}"       class="mobile-nav-link">Results</a>
            <a href="{{ route('campaign.winners', $campaign->slug) }}"       class="mobile-nav-link">Winners</a>
            <a href="{{ route('campaign.faq', $campaign->slug) }}"           class="mobile-nav-link">FAQ</a>
            <a href="{{ route('campaign.tnc', $campaign->slug) }}"           class="mobile-nav-link">T&amp;C</a>
            <a href="{{ route('campaigns.list') }}"                          class="mobile-nav-link">All campaigns</a>
            <a href="{{ route('campaign.enter', $campaign->slug) }}" class="btn btn-primary btn-lg" style="margin-top:8px;justify-content:center;">Enter now →</a>
        @else
            <a href="{{ route('campaigns.list') }}" class="mobile-nav-link">All campaigns</a>
        @endif
    </div>
</div>
