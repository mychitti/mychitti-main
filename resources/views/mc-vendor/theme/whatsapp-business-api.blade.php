@extends('mc-vendor.theme.layout')

@section('title', 'WhatsApp Business API for India | MC Vendor Hub')
@section('meta_description', 'Official Meta-verified WhatsApp Business API — automated booking, lead capture, and AI-powered replies for your business, on the official Meta platform.')

@section('content')

    <div class="wrap breadcrumb"><a href="{{ mcv('vendor.mc-vendor.theme.home') }}">Home</a><span>/</span>WhatsApp Business API</div>

    <section class="page-hero" style="background:linear-gradient(180deg, var(--green-pale) 0%, var(--bg) 100%);">
        <div class="page-hero-inner">
            <div>
                <span class="eyebrow" style="border-color:var(--green-dark); color:var(--green-dark);"><span class="dot" style="background:var(--green-dark);"></span> Official Meta WhatsApp Business Platform</span>
                <h1>Answer every customer, <span style="color:var(--green-dark);">even at midnight</span>.</h1>
                <p class="lede">Meta-verified WhatsApp Business messaging with an AI Employee reading and replying before your first cup of chai — not a workaround, the real thing.</p>
                <div class="hero-ctas">
                    <a href="{{ mcv('vendor.mc-vendor.contact') }}" class="btn" style="background:var(--green-dark); color:#fff;">Set Up WhatsApp</a>
                    <a href="#pricing" class="btn btn-ghost" style="border-color:var(--green-dark); color:var(--green-dark);">See Pricing →</a>
                </div>
                <p class="hero-note">@if ($mc_pricing['whatsapp'])Platform fee from ₹{{ number_format($mc_pricing['whatsapp']['monthly']) }}/month · @endif Official BSP-enabled messaging</p>
            </div>
            <div class="wa-panel" style="background:var(--green-dark); max-width:380px;">
                <div class="chat-meta">Today, 11:47 PM</div>
                <div class="chat-line">Are you open tomorrow for a haircut appointment?</div>
                <div class="chat-line out">Yes! We open at 10 AM. I've booked you a slot at 10:30 AM — see you then 🙂</div>
                <div class="chat-meta" style="margin-top:12px;">Replied by Reception AI · 0.4 sec</div>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Why Businesses Choose Us</span>
                <h2>Official WhatsApp Business Platform, done right</h2>
            </div>
            <div class="feature-grid">
                <div class="feature-card"><div class="ic">✅</div><h3>Meta-Verified, Not a Workaround</h3><p>Built on the official WhatsApp Business Platform via an authorised Business Solution Provider.</p></div>
                <div class="feature-card"><div class="ic">🤖</div><h3>AI-Powered Replies</h3><p>Menu-based automation and AI Employees handle FAQs, bookings, and lead capture.</p></div>
                <div class="feature-card"><div class="ic">🔁</div><h3>Human Handover</h3><p>Any conversation can be handed to your own staff, anytime.</p></div>
                <div class="feature-card"><div class="ic">📅</div><h3>Appointment Reminders</h3><p>Reminders go out automatically, however many hours ahead you choose.</p></div>
                <div class="feature-card"><div class="ic">📋</div><h3>Template Approval Handled</h3><p>We manage the Meta template approval process for you during setup.</p></div>
                <div class="feature-card"><div class="ic">📊</div><h3>Transparent Billing</h3><p>Actual Meta/BSP charges, shown clearly, plus a flat per-message platform fee.</p></div>
            </div>
        </div>
    </section>

    <section style="background:var(--bg-soft); padding:96px 0;" id="pricing">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Pricing</span>
                <h2>Simple, transparent WhatsApp pricing</h2>
            </div>
            <div class="content-split">
                @php($wa = $mc_pricing['whatsapp'])
                <div class="price-table">
                    <table>
                        <tr><th>Item</th><th>Price / Details</th></tr>
                        <tr><td>Platform Fee</td><td class="amt">@if ($wa) ₹{{ number_format($wa['monthly']) }} + GST / month @else On request @endif</td></tr>
                        @if ($wa && $wa['discount'] > 0)
                            <tr><td>Paid Yearly</td><td class="amt">₹{{ number_format($wa['yearly_net']) }} + GST / year <span style="color:var(--ink-faint); font-size:12px;">(save {{ _num($wa['discount']) }}%)</span></td></tr>
                        @endif
                        <tr><td>WhatsApp Message Charges</td><td>Actual Meta/BSP charges + platform usage fee per message</td></tr>
                        <tr><td>Basic WhatsApp Bot</td><td>Included</td></tr>
                        <tr><td>24×7 Basic Support</td><td>Free</td></tr>
                        @if ($wa && $wa['trial_days'] > 0)
                            <tr><td>Free Trial</td><td class="amt">{{ $wa['trial_days'] }} days</td></tr>
                        @endif
                    </table>
                </div>
                <div class="sticky-cta">
                    <h4>Get started today</h4>
                    @if ($wa)
                        <div class="big-price">₹{{ number_format($wa['monthly']) }}<span>/mo</span></div>
                    @endif
                    <ul>
                        <li>Official Meta WhatsApp Business</li>
                        <li>Basic bot included</li>
                        <li>Template approval assistance</li>
                        <li>24×7 basic support</li>
                    </ul>
                    <a href="{{ mcv('vendor.mc-vendor.contact') }}" class="btn" style="background:var(--green-dark); color:#fff; width:100%; justify-content:center;">Set Up WhatsApp</a>
                    @if ($wa && $wa['trial_days'] > 0)
                        <div class="setup-note">🎁 {{ $wa['trial_days'] }}-day free trial included</div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">What's Included in Setup</span>
                <h2>We handle the Meta side, so you don't have to</h2>
            </div>
            <div class="feature-grid">
                <div class="feature-card"><div class="ic">📱</div><h3>WhatsApp Business Account Setup</h3><p>Full account creation and configuration.</p></div>
                <div class="feature-card"><div class="ic">🏢</div><h3>Meta Business Verification</h3><p>Guidance through Meta's business verification process.</p></div>
                <div class="feature-card"><div class="ic">📝</div><h3>Template Approval Assistance</h3><p>We help draft and submit templates for Meta's approval.</p></div>
                <div class="feature-card"><div class="ic">⚙️</div><h3>API Configuration</h3><p>Full technical setup and integration with your account.</p></div>
                <div class="feature-card"><div class="ic">🧪</div><h3>Initial Testing</h3><p>We test your setup end-to-end before go-live.</p></div>
                <div class="feature-card"><div class="ic">🎓</div><h3>Basic Training</h3><p>Your team is trained on managing conversations and templates.</p></div>
            </div>
        </div>
    </section>

    <section class="faq">
        <div class="wrap">
            <div class="section-head"><span class="kicker">FAQ</span><h2>WhatsApp Business — common questions</h2></div>
            <div class="faq-list">
                <div class="faq-item"><button class="faq-q">Is this the official WhatsApp Business API? <span class="plus">+</span></button><div class="faq-a">Yes — messaging is enabled through Meta's WhatsApp Business Platform via our authorised Business Solution Provider, not a third-party workaround.</div></div>
                <div class="faq-item"><button class="faq-q">How is message billing calculated? <span class="plus">+</span></button><div class="faq-a">You're billed the actual Meta/BSP charge for each message, plus a flat platform usage fee — fully transparent, no hidden markup.</div></div>
                <div class="faq-item"><button class="faq-q">What happens if a message fails to deliver? <span class="plus">+</span></button><div class="faq-a">Billing reflects messages sent/dispatched through the platform. We are not responsible for delivery failures caused by invalid numbers, recipient opt-outs, or Meta-side issues — see our <a href="{{ mcv('vendor.mc-vendor.mc-vendor-hub-return-policy') }}" style="color:var(--blue); font-weight:700;">Refund &amp; Cancellation Policy</a> for details.</div></div>
                <div class="faq-item"><button class="faq-q">Can I connect an AI Employee to WhatsApp? <span class="plus">+</span></button><div class="faq-a">Yes — any AI Employee can be connected to answer and manage WhatsApp conversations automatically.</div></div>
            </div>
        </div>
    </section>

    <section style="background:var(--bg-soft);">
        <div class="wrap">
            <div class="section-head"><span class="kicker">Explore More</span><h2>Related solutions</h2></div>
            <div class="related-grid">
                <a href="{{ mcv('vendor.mc-vendor.theme.ai-employees') }}" class="related-card"><h4>AI Employees</h4><p>Automate WhatsApp conversations</p></a>
                <a href="{{ mcv('vendor.mc-vendor.theme.home') }}#modules" class="related-card"><h4>Lead Management</h4><p>Every WhatsApp enquiry, captured</p></a>
                <a href="{{ mcv('vendor.mc-vendor.theme.salon-software') }}" class="related-card"><h4>Salon &amp; Spa</h4><p>See WhatsApp booking in action</p></a>
                <a href="{{ mcv('vendor.mc-vendor.price-calculator') }}" class="related-card"><h4>Full Pricing</h4><p>See the complete pricing document</p></a>
            </div>
        </div>
    </section>

    <section class="final-cta" style="background:linear-gradient(135deg, var(--green-dark), var(--green));">
        <div class="wrap" style="text-align:center;">
            <h2>Ready to never miss a message again?</h2>
            <p>Official WhatsApp Business, set up and running in days.</p>
            <div class="hero-ctas" style="justify-content:center;">
                <a href="{{ mcv('vendor.mc-vendor.contact') }}" class="btn btn-primary" style="background:#fff; color:var(--green-dark); border-color:#fff;">Set Up WhatsApp</a>
                <a href="{{ mcv('vendor.mc-vendor.theme.home') }}" class="btn btn-ghost" style="color:#fff; border-color:#fff;">Back to Home</a>
            </div>
        </div>
    </section>

@endsection
