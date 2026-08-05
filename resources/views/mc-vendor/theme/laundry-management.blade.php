@extends('mc-vendor.theme.layout')

@section('title', 'Laundry Management Software India | MC Vendor Hub')
@section('meta_description', 'Laundry and dry-cleaning management software — order tracking, pickup and delivery scheduling, instant billing and WhatsApp ready-alerts for Indian laundry operators.')

@section('content')

    <div class="wrap breadcrumb"><a href="{{ route('vendor.mc-vendor.theme.home') }}">Home</a><span>/</span><a href="{{ route('vendor.mc-vendor.theme.home') }}#verticals">Industries</a><span>/</span>Laundry Management</div>

    <section class="page-hero">
        <div class="page-hero-inner">
            <div>
                <span class="eyebrow"><span class="dot"></span> For Laundry &amp; Dry-Cleaning Businesses</span>
                <h1>Never lose track of <span>whose clothes are whose</span>, again.</h1>
                <p class="lede">Order tracking, pickup/delivery scheduling, and billing — built specifically for laundry operators, at the lowest entry price on the platform.</p>
                <div class="hero-ctas">
                    <a href="{{ $mc_signup_url }}" class="btn btn-primary">Start Free Trial</a>
                    <a href="#pricing" class="btn btn-ghost">See Pricing →</a>
                </div>
                <p class="hero-note">@if ($mc_pricing['laundry'])India's most affordable laundry management software — from ₹{{ number_format($mc_pricing['laundry']['monthly']) }}/month @else Affordable laundry management software, built for Indian operators @endif</p>
            </div>
            <div class="preview-card" style="max-width:380px;">
                <div class="preview-chrome"><span></span><span></span><span></span></div>
                <div class="preview-body">
                    <div class="mock-bar" style="width:38%;"></div>
                    <div class="mock-row"><span>👕 Order #L-2291</span><span class="mock-pill" style="background:var(--blue-pale); color:var(--blue);">In Wash</span></div>
                    <div class="mock-row"><span>👔 Order #L-2292</span><span class="mock-pill" style="background:#FFF3E6; color:var(--orange-dark);">Ready for Pickup</span></div>
                    <div class="mock-row"><span>🧺 Order #L-2293</span><span class="mock-pill" style="background:var(--green-pale); color:var(--green-dark);">Delivered</span></div>
                </div>
                <div class="preview-label"><h4>Laundry — Order Tracker</h4></div>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Built for Laundry Operators</span>
                <h2>Track every order from drop-off to delivery</h2>
            </div>
            <div class="feature-grid">
                <div class="feature-card"><div class="ic">🏷️</div><h3>Order Tracking</h3><p>Tag every order with status — washed, ironed, ready, delivered.</p></div>
                <div class="feature-card"><div class="ic">🚚</div><h3>Pickup &amp; Delivery Scheduling</h3><p>Plan routes and delivery windows without a paper diary.</p></div>
                <div class="feature-card"><div class="ic">🧾</div><h3>Instant Billing</h3><p>Generate customer bills by item, weight, or service type.</p></div>
                <div class="feature-card"><div class="ic">💬</div><h3>WhatsApp Ready Alerts</h3><p>Notify customers automatically the moment their order is ready.</p></div>
                <div class="feature-card"><div class="ic">👥</div><h3>Customer History</h3><p>See every customer's past orders and preferences at a glance.</p></div>
                <div class="feature-card"><div class="ic">💸</div><h3>Lowest Entry Price</h3><p>@if ($mc_pricing['laundry'])Starts at just ₹{{ number_format($mc_pricing['laundry']['monthly']) }}/month — one of the most affordable modules on the platform.@else One of the most affordable modules on the platform.@endif</p></div>
            </div>
        </div>
    </section>

    <section style="background:var(--bg-soft); padding:96px 0;" id="pricing">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Pricing</span>
                <h2>Simple pricing for a simple setup</h2>
            </div>
            @php($laundry = $mc_pricing['laundry'])
            <div class="content-split">
                <div class="price-table">
                    <table>
                        <tr><th>Plan</th><th>Monthly</th><th>Yearly</th>@if ($laundry && $laundry['discount'] > 0)<th>Yearly ({{ _num($laundry['discount']) }}% off)</th>@endif</tr>
                        <tr>
                            <td>{{ $laundry['name'] ?? 'Laundry Management' }}</td>
                            <td class="amt">@if ($laundry) ₹{{ number_format($laundry['monthly']) }} @else On request @endif</td>
                            <td>@if ($laundry) ₹{{ number_format($laundry['yearly']) }} @else Contact sales @endif</td>
                            @if ($laundry && $laundry['discount'] > 0)<td class="amt-off">₹{{ number_format($laundry['yearly_net']) }}</td>@endif
                        </tr>
                    </table>
                </div>
                <div class="sticky-cta">
                    <h4>Get started today</h4>
                    @if ($laundry)
                        <div class="big-price">₹{{ number_format($laundry['monthly']) }}<span>/mo</span></div>
                    @endif
                    <ul>
                        <li>Order tracking end-to-end</li>
                        <li>Pickup &amp; delivery scheduling</li>
                        <li>Instant billing</li>
                        <li>WhatsApp ready-alerts</li>
                    </ul>
                    <a href="{{ $mc_signup_url }}" class="btn btn-primary" style="width:100%; justify-content:center;">Start Free Trial</a>
                    @if ($laundry && $laundry['trial_days'] > 0)
                        <div class="setup-note">🎁 {{ $laundry['trial_days'] }}-day free trial included</div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="faq">
        <div class="wrap">
            <div class="section-head"><span class="kicker">FAQ</span><h2>Laundry Management — common questions</h2></div>
            <div class="faq-list">
                <div class="faq-item"><button class="faq-q">Can I bill by weight instead of per item? <span class="plus">+</span></button><div class="faq-a">Yes — billing supports both per-item and weight-based pricing structures.</div></div>
                <div class="faq-item"><button class="faq-q">Does it support multiple pickup/delivery staff? <span class="plus">+</span></button><div class="faq-a">Yes — you can assign orders to specific delivery staff and track their status.</div></div>
                <div class="faq-item"><button class="faq-q">Can customers get notified automatically? <span class="plus">+</span></button><div class="faq-a">Yes, when paired with WhatsApp Business messaging, customers get automatic "ready for pickup" alerts.</div></div>
                <div class="faq-item"><button class="faq-q">Is there a limit on orders per month? <span class="plus">+</span></button><div class="faq-a">No order volume limit is applied on the Laundry Management plan.</div></div>
            </div>
        </div>
    </section>

    <section style="background:var(--bg-soft);">
        <div class="wrap">
            <div class="section-head"><span class="kicker">Explore More</span><h2>Related solutions</h2></div>
            <div class="related-grid">
                <a href="{{ route('vendor.mc-vendor.theme.whatsapp') }}" class="related-card"><h4>WhatsApp Business</h4><p>Automatic ready-for-pickup alerts</p></a>
                <a href="{{ route('vendor.mc-vendor.theme.ai-employees') }}" class="related-card"><h4>AI Employees</h4><p>Customer support &amp; reminders</p></a>
                <a href="{{ route('vendor.mc-vendor.theme.home') }}#modules" class="related-card"><h4>Accounting</h4><p>Daily billing &amp; expense tracking</p></a>
                <a href="{{ route('vendor.mc-vendor.theme.home') }}#modules" class="related-card"><h4>CRM</h4><p>Customer order history in one place</p></a>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="wrap" style="text-align:center;">
            <h2>Ready to stop losing track of orders?</h2>
            <p>Start free — set up your laundry business in minutes.</p>
            <div class="hero-ctas" style="justify-content:center;">
                <a href="{{ $mc_signup_url }}" class="btn btn-primary">Start Free Trial</a>
                <a href="{{ route('vendor.mc-vendor.theme.home') }}" class="btn btn-ghost">Back to Home</a>
            </div>
        </div>
    </section>

@endsection
