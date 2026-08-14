{{-- MC Vendor Hub site footer + floating actions. Shared by the marketing theme layout and the
     signup layout. Expects $mc_signup_url and $mc_wa_url from the layout. --}}
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
