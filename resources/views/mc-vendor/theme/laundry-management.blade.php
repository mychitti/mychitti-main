@extends('mc-vendor.theme.layout')

@section('title', 'Laundry Management Software India | MC Vendor Hub')
@section('meta_description', 'Laundry and dry-cleaning management software — order tracking from drop-off to pickup, itemised billing and customer order history for Indian laundry operators.')

@section('content')

    <div class="wrap breadcrumb"><a href="{{ mcv('vendor.mc-vendor.theme.home') }}">Home</a><span>/</span><a href="{{ mcv('vendor.mc-vendor.theme.home') }}#verticals">Industries</a><span>/</span>Laundry Management</div>

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
            @php($laundry_hero = $mc_pricing['laundry'] ?? null)
            <div class="sticky-cta" style="position:static; max-width:380px;">
                <h4>{{ $laundry_hero['name'] ?? 'Laundry Management' }} — at a glance</h4>
                @if ($laundry_hero)
                    <div class="big-price">₹{{ number_format($laundry_hero['monthly']) }}<span>/mo</span></div>
                @else
                    <div class="big-price">On request<span></span></div>
                @endif
                <ul>
                    <li>Every order tracked from drop-off to pickup</li>
                    <li>Itemised bills, challans and receipts</li>
                    <li>Customer order history in one place</li>
                    @if ($laundry_hero && $laundry_hero['discount'] > 0)
                        <li>Save {{ (float) $laundry_hero['discount'] }}% paying yearly</li>
                    @endif
                </ul>
                @if ($laundry_hero && $laundry_hero['trial_days'] > 0)
                    <div class="setup-note">🎁 {{ $laundry_hero['trial_days'] }}-day free trial included</div>
                @endif
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
                <div class="feature-card"><div class="ic">🚚</div><h3>Drop-off &amp; Pickup Dates</h3><p>Record when an order came in, when it's due, and when it left — no paper diary.</p></div>
                <div class="feature-card"><div class="ic">🧾</div><h3>Instant Billing</h3><p>Itemised bills, challans and receipts generated straight from the order.</p></div>
                <div class="feature-card"><div class="ic">🧑‍🔧</div><h3>Staff Assignment</h3><p>Assign each order to the staff member handling it and track it from there.</p></div>
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
                        <li>Customer order history</li>
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
                <div class="faq-item"><button class="faq-q">How are orders priced? <span class="plus">+</span></button><div class="faq-a">Each order is billed line by line — quantity × rate per garment or service — and the total flows straight into your invoice and accounts.</div></div>
                <div class="faq-item"><button class="faq-q">Does it support multiple staff? <span class="plus">+</span></button><div class="faq-a">Yes — each order can be assigned to the staff member handling it, so you always know who has what.</div></div>
                <div class="faq-item"><button class="faq-q">Can I see a customer's past orders? <span class="plus">+</span></button><div class="faq-a">Yes — every order is filed against the customer record, so their full history is one click away at the counter.</div></div>
                <div class="faq-item"><button class="faq-q">Is there a limit on orders per month? <span class="plus">+</span></button><div class="faq-a">No order volume limit is applied on the Laundry Management plan.</div></div>
            </div>
        </div>
    </section>

    <section style="background:var(--bg-soft);">
        <div class="wrap">
            <div class="section-head"><span class="kicker">Explore More</span><h2>Related solutions</h2></div>
            <div class="related-grid">
                <a href="{{ mcv('vendor.mc-vendor.theme.whatsapp') }}" class="related-card"><h4>WhatsApp Business</h4><p>Answer customer enquiries around the clock</p></a>
                <a href="{{ mcv('vendor.mc-vendor.theme.ai-employees') }}" class="related-card"><h4>AI Employees</h4><p>Customer support &amp; reminders</p></a>
                <a href="{{ mcv('vendor.mc-vendor.theme.home') }}#modules" class="related-card"><h4>Accounting</h4><p>Daily billing &amp; expense tracking</p></a>
                <a href="{{ mcv('vendor.mc-vendor.theme.home') }}#modules" class="related-card"><h4>CRM</h4><p>Customer order history in one place</p></a>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="wrap" style="text-align:center;">
            <h2>Ready to stop losing track of orders?</h2>
            <p>Start free — set up your laundry business in minutes.</p>
            <div class="hero-ctas" style="justify-content:center;">
                <a href="{{ $mc_signup_url }}" class="btn btn-primary">Start Free Trial</a>
                <a href="{{ mcv('vendor.mc-vendor.theme.home') }}" class="btn btn-ghost">Back to Home</a>
            </div>
        </div>
    </section>

@endsection
