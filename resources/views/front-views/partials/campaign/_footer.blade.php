<footer class="site-footer">
    <div class="container">

        <div class="footer-top">

            <!-- Brand col -->
            <div class="footer-brand">
                <a href="{{ $campaign ? route('campaign.index', $campaign->slug) : route('campaigns.list') }}" class="site-logo">
                    <div class="logo-mark">
                        <svg viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 2L16 6.5V12L9 16L2 12V6.5L9 2Z" />
                        </svg>
                    </div>
                    <span class="logo-text">{{ $campaign?->name ?? 'Campaigns' }}</span>
                </a>
                <p class="footer-tagline">A fair, transparent campaign open to all eligible participants. Good luck!</p>
            </div>

            <!-- Campaign links -->
            <div>
                <p class="footer-col-title">Campaign</p>
                <ul class="footer-links">
                    @if($campaign)
                        <li><a href="{{ route('campaign.index', $campaign->slug) }}"        class="footer-link">Home</a></li>
                        <li><a href="{{ route('campaign.how-it-works', $campaign->slug) }}" class="footer-link">How it works</a></li>
                        <li><a href="{{ route('campaign.enter', $campaign->slug) }}"        class="footer-link">Enter now</a></li>
                        <li><a href="{{ route('campaign.results', $campaign->slug) }}"      class="footer-link">Results</a></li>
                        <li><a href="{{ route('campaign.winners', $campaign->slug) }}"      class="footer-link">Winners</a></li>
                        @if($campaign->sponsors->isNotEmpty())
                        <li><a href="{{ route('campaign.sponsors', $campaign->slug) }}"     class="footer-link">Sponsors</a></li>
                        @endif
                    @endif
                    <li><a href="{{ route('campaigns.list') }}" class="footer-link">All campaigns</a></li>
                </ul>
            </div>

            <!-- Info links -->
            <div>
                <p class="footer-col-title">Info</p>
                <ul class="footer-links">
                    @if($campaign)
                        <li><a href="{{ route('campaign.faq', $campaign->slug) }}" class="footer-link">FAQ</a></li>
                        <li><a href="{{ route('campaign.tnc', $campaign->slug) }}" class="footer-link">Terms &amp; conditions</a></li>
                    @endif
                    <li><a href="#" class="footer-link">Contact us</a></li>
                </ul>
            </div>

        </div>

        <div class="footer-bottom">
            <p class="footer-copy">© {{ date('Y') }} {{ $campaign?->name ?? 'My Chitti' }}. All rights reserved.</p>
            <nav class="footer-legal-links" aria-label="Legal">
                @if($campaign)
                <a href="{{ route('campaign.tnc', $campaign->slug) }}" class="footer-legal-link">Terms</a>
                @endif
            </nav>
        </div>

    </div>
</footer>
