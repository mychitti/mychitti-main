@extends('mc-vendor.theme.layout')

@section('title', 'Salon & Spa Software India — Booking, CRM & WhatsApp | MC Vendor Hub')
@section('meta_description', 'Salon and spa software for appointment booking, client history and WhatsApp reminders — built on the MC Vendor Hub Business Bundle with an optional Reception AI.')

@section('content')

    <div class="wrap breadcrumb"><a href="{{ route('vendor.mc-vendor.theme.home') }}">Home</a><span>/</span><a href="{{ route('vendor.mc-vendor.theme.home') }}#verticals">Industries</a><span>/</span>Salon &amp; Spa</div>

    <section class="page-hero">
        <div class="page-hero-inner">
            <div>
                <span class="eyebrow"><span class="dot"></span> For Salons, Spas &amp; Beauty Studios</span>
                <h1>Book appointments, remember every client, and <span>never miss a midnight enquiry</span>.</h1>
                <p class="lede">Booking, client history, and WhatsApp-powered reminders — built by combining MC Vendor Hub's core CRM with an AI Reception Employee, so no enquiry sits unanswered.</p>
                <div class="hero-ctas">
                    <a href="{{ $mc_signup_url }}" class="btn btn-primary">Start Free Trial</a>
                    <a href="#pricing" class="btn btn-ghost">See What's Included →</a>
                </div>
                <p class="hero-note">Built on the core platform + WhatsApp + AI Employees — no separate salon SKU required</p>
            </div>
            <div class="wa-panel" style="background:var(--bg-soft); border:1px solid var(--line); max-width:380px;">
                <div class="chat-meta" style="color:var(--ink-faint);">Today, 11:47 PM</div>
                <div class="chat-line">Are you open tomorrow for a haircut appointment?</div>
                <div class="chat-line out" style="background:var(--green-pale); color:var(--ink);">Yes! We open at 10 AM. I've booked you a slot at 10:30 AM — see you then 🙂</div>
                <div class="chat-meta" style="margin-top:12px; color:var(--ink-faint);">Replied by Reception AI · 0.4 sec</div>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Built for Client-Facing Businesses</span>
                <h2>What a salon actually needs, not a generic POS</h2>
            </div>
            <div class="feature-grid">
                <div class="feature-card"><div class="ic">📅</div><h3>Appointment Booking</h3><p>Clients book slots via WhatsApp — no phone tag, no double-booking.</p></div>
                <div class="feature-card"><div class="ic">👤</div><h3>Client History (CRM)</h3><p>Every client's past visits, preferences, and spend in one profile.</p></div>
                <div class="feature-card"><div class="ic">🤖</div><h3>24×7 Reception AI</h3><p>Answers "are you open?" and booking questions any time of day.</p></div>
                <div class="feature-card"><div class="ic">🧾</div><h3>Billing &amp; Packages</h3><p>Bill services, products, and membership packages from one screen.</p></div>
                <div class="feature-card"><div class="ic">💬</div><h3>Automated Reminders</h3><p>Appointment and follow-up reminders sent automatically via WhatsApp.</p></div>
                <div class="feature-card"><div class="ic">🧑‍🤝‍🧑</div><h3>Staff Scheduling</h3><p>Assign stylists/therapists to slots and track their bookings.</p></div>
            </div>
        </div>
    </section>

    <section style="background:var(--bg-soft); padding:96px 0;" id="pricing">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">How Salons Are Priced</span>
                <h2>Built from the core platform, not a separate product</h2>
            </div>
            <p class="note-banner" style="max-width:700px; margin:0 auto 32px;">📌 There's currently no dedicated "Salon" pricing tier — salons run on the standard business plan plus WhatsApp Business and (optionally) a Reception AI Employee. Numbers below reflect those existing plans.</p>
            <div class="price-table">
                <table>
                    <tr><th>What You Need</th><th>Plan</th><th>Price</th></tr>
                    <tr>
                        <td>Billing, CRM, Client Records</td>
                        <td>{{ $base_plan->name ?? 'Business Plan' }}</td>
                        <td class="amt">@if ($base_plan) ₹{{ number_format($base_plan->price) }}/mo @else On request @endif</td>
                    </tr>
                    <tr>
                        <td>WhatsApp Booking &amp; Reminders</td>
                        <td>{{ $mc_pricing['whatsapp']['name'] ?? 'WhatsApp Business Platform' }}</td>
                        <td class="amt">@if ($mc_pricing['whatsapp']) ₹{{ number_format($mc_pricing['whatsapp']['monthly']) }}/mo @else On request @endif</td>
                    </tr>
                    <tr><td>24×7 Reception AI (optional)</td><td>Standard AI Employee</td><td class="amt">from ₹6,999/mo</td></tr>
                </table>
            </div>
            <div class="hero-ctas" style="justify-content:center; margin-top:26px;">
                <a href="{{ route('vendor.mc-vendor.price-calculator') }}" class="btn btn-primary">Build Your Own Bundle →</a>
            </div>
        </div>
    </section>

    <section class="faq">
        <div class="wrap">
            <div class="section-head"><span class="kicker">FAQ</span><h2>Salon &amp; Spa — common questions</h2></div>
            <div class="faq-list">
                <div class="faq-item"><button class="faq-q">Do I need the AI Employee, or just the base plan? <span class="plus">+</span></button><div class="faq-a">The {{ $base_plan->name ?? 'base plan' }} + WhatsApp covers booking and client records. The AI Employee is optional, best if you get enquiries outside working hours and want them answered automatically.</div></div>
                <div class="faq-item"><button class="faq-q">Can clients book directly through WhatsApp? <span class="plus">+</span></button><div class="faq-a">Yes — clients can message your WhatsApp Business number and get booking confirmations automatically.</div></div>
                <div class="faq-item"><button class="faq-q">Can I track stylist-wise performance? <span class="plus">+</span></button><div class="faq-a">Yes — staff scheduling and billing are linked, so you can see which stylist is generating what revenue.</div></div>
                <div class="faq-item"><button class="faq-q">Will a dedicated Salon plan be available later? <span class="plus">+</span></button><div class="faq-a">We're evaluating a bundled salon-specific plan. Until then, the combination above covers everything a salon needs.</div></div>
            </div>
        </div>
    </section>

    <section style="background:var(--bg-soft);">
        <div class="wrap">
            <div class="section-head"><span class="kicker">Explore More</span><h2>Related solutions</h2></div>
            <div class="related-grid">
                <a href="{{ route('vendor.mc-vendor.theme.whatsapp') }}" class="related-card"><h4>WhatsApp Business</h4><p>Booking &amp; reminders automated</p></a>
                <a href="{{ route('vendor.mc-vendor.theme.ai-employees') }}" class="related-card"><h4>AI Employees</h4><p>24×7 reception &amp; customer support</p></a>
                <a href="{{ route('vendor.mc-vendor.theme.home') }}#modules" class="related-card"><h4>CRM &amp; Client Mgmt</h4><p>Every client, one record</p></a>
                <a href="{{ route('vendor.mc-vendor.theme.home') }}#modules" class="related-card"><h4>Billing &amp; POS</h4><p>Services, products &amp; packages</p></a>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="wrap" style="text-align:center;">
            <h2>Ready to stop missing after-hours bookings?</h2>
            <p>Start free with billing and CRM — add WhatsApp and AI when you're ready.</p>
            <div class="hero-ctas" style="justify-content:center;">
                <a href="{{ $mc_signup_url }}" class="btn btn-primary">Start Free Trial</a>
                <a href="{{ route('vendor.mc-vendor.theme.home') }}" class="btn btn-ghost">Back to Home</a>
            </div>
        </div>
    </section>

@endsection
