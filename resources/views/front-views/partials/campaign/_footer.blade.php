 <!-- ══════════════════════════════════════
       FOOTER
       ══════════════════════════════════════ -->
    <footer class="site-footer">
        <div class="container">

            <div class="footer-top">

                <!-- Brand col -->
                <div class="footer-brand">
                    <a href="{{route('campaign.index')}}" class="site-logo">
                        <div class="logo-mark">
                            <svg viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 2L16 6.5V12L9 16L2 12V6.5L9 2Z" />
                            </svg>
                        </div>
                        <span class="logo-text">CampaignName</span>
                    </a>
                    <p class="footer-tagline">A fair, transparent campaign open to all eligible participants. Good
                        luck!</p>
                </div>

                <!-- Campaign links -->
                <div>
                    <p class="footer-col-title">Campaign</p>
                    <ul class="footer-links">
                        <li><a href="{{route('campaign.index')}}" class="footer-link">Home</a></li>
                        <li><a href="{{route('campaign.how-it-works')}}" class="footer-link">How it works</a></li>
                        <li><a href="{{route('campaign.enter')}}" class="footer-link">Enter now</a></li>
                        <li><a href="{{route('campaign.results')}}" class="footer-link">Results</a></li>
                        <li><a href="{{route('campaign.winners')}}" class="footer-link">Winners</a></li>
                    </ul>
                </div>

                <!-- Info links -->
                <div>
                    <p class="footer-col-title">Info</p>
                    <ul class="footer-links">
                        <li><a href="{{route('campaign.faq')}}" class="footer-link">FAQ</a></li>
                        <li><a href="{{route('campaign.tnc')}}" class="footer-link">Terms &amp; conditions</a></li>
                        <li><a href="#" class="footer-link">Privacy policy</a></li>
                        <li><a href="#" class="footer-link">Contact us</a></li>
                    </ul>
                </div>

                <!-- Contact / social -->
                <div>
                    <p class="footer-col-title">Follow</p>
                    <ul class="footer-links">
                        <li><a href="#" class="footer-link">Instagram</a></li>
                        <li><a href="#" class="footer-link">Facebook</a></li>
                        <li><a href="#" class="footer-link">X / Twitter</a></li>
                        <li><a href="mailto:hello@example.com" class="footer-link">hello@example.com</a></li>
                    </ul>
                </div>

            </div>

            <div class="footer-bottom">
                <p class="footer-copy">© 2026 CampaignName. All rights reserved. Promoter: Company Ltd.</p>
                <nav class="footer-legal-links" aria-label="Legal">
                    <a href="{{route('campaign.tnc')}}" class="footer-legal-link">Terms</a>
                    <a href="/privacy" class="footer-legal-link">Privacy</a>
                    <a href="/cookies" class="footer-legal-link">Cookies</a>
                </nav>
            </div>

        </div>
    </footer>