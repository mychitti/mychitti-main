@extends('mc-vendor.theme.layout')

@section('title', 'Retail POS Software India — Multi-Counter Billing | MC Vendor Hub')
@section('meta_description', 'Retail POS software with multi-counter billing, real-time inventory sync and daily sales reports — built for high-footfall Indian retail stores.')

@section('content')

    <div class="wrap breadcrumb"><a href="{{ mcv('vendor.mc-vendor.theme.home') }}">Home</a><span>/</span><a href="{{ mcv('vendor.mc-vendor.theme.home') }}#verticals">Industries</a><span>/</span>Retail POS</div>

    <section class="page-hero">
        <div class="page-hero-inner">
            <div>
                <span class="eyebrow"><span class="dot"></span> Built for High-Footfall Retail</span>
                <h1>Retail POS software that keeps <span>every counter</span> moving.</h1>
                <p class="lede">Multi-counter billing, real-time inventory sync, and staff-level access — built for Indian retail stores that can't afford a queue at checkout.</p>
                <div class="hero-ctas">
                    <a href="{{ $mc_signup_url }}" class="btn btn-primary">Start Free Trial</a>
                    <a href="#pricing" class="btn btn-ghost">See Pricing →</a>
                </div>
                <p class="hero-note">@if ($mc_pricing['retail'] && $mc_pricing['retail']['trial_days'] > 0){{ $mc_pricing['retail']['trial_days'] }}-day free trial · @endif Setup in under a day</p>
            </div>
            @php($retail_hero = $mc_pricing['retail'] ?? null)
            <div class="sticky-cta" style="position:static; max-width:380px;">
                <h4>{{ $retail_hero['name'] ?? 'Retail POS' }} — at a glance</h4>
                @if ($retail_hero)
                    <div class="big-price">₹{{ number_format($retail_hero['monthly']) }}<span>/mo</span></div>
                @else
                    <div class="big-price">On request<span></span></div>
                @endif
                <ul>
                    <li>Barcode-ready billing across multiple counters</li>
                    <li>Stock updated on every sale, no reconciliation</li>
                    <li>Cash, UPI and card into one daily report</li>
                    @if ($retail_hero && $retail_hero['discount'] > 0)
                        <li>Save {{ (float) $retail_hero['discount'] }}% paying yearly</li>
                    @endif
                </ul>
                @if ($retail_hero && $retail_hero['trial_days'] > 0)
                    <div class="setup-note">🎁 {{ $retail_hero['trial_days'] }}-day free trial included</div>
                @endif
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Why Retailers Choose Us</span>
                <h2>Built for the speed a retail counter actually needs</h2>
            </div>
            <div class="feature-grid">
                <div class="feature-card"><div class="ic">⚡</div><h3>Fast Billing</h3><p>Barcode-ready checkout designed to keep queues short during peak hours.</p></div>
                <div class="feature-card"><div class="ic">🔄</div><h3>Real-Time Inventory Sync</h3><p>Every sale updates stock instantly across all counters — no manual reconciliation.</p></div>
                <div class="feature-card"><div class="ic">🧑‍🤝‍🧑</div><h3>Multi-Counter, Multi-Staff</h3><p>Run several counters at once, each staff login with its own access level.</p></div>
                <div class="feature-card"><div class="ic">💳</div><h3>Every Payment Mode</h3><p>Cash, UPI, card — all reconciled into one daily report automatically.</p></div>
                <div class="feature-card"><div class="ic">📊</div><h3>Daily Sales Reports</h3><p>See what sold, what didn't, and which counter is your top performer.</p></div>
                <div class="feature-card"><div class="ic">🤖</div><h3>Pairs With AI Employees</h3><p>Add Inventory &amp; POS AI to auto-flag low stock and reorder suggestions.</p></div>
            </div>
        </div>
    </section>

    <section id="pricing" style="background:var(--bg-soft); padding:96px 0;">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Pricing</span>
                <h2>Simple, single-tier Retail POS pricing</h2>
            </div>
            @php($retail = $mc_pricing['retail'])
            <div class="content-split">
                <div class="price-table">
                    <table>
                        <tr><th>Plan</th><th>Monthly</th><th>Yearly</th>@if ($retail && $retail['discount'] > 0)<th>Yearly ({{ _num($retail['discount']) }}% off)</th>@endif</tr>
                        <tr>
                            <td>{{ $retail['name'] ?? 'Retail POS' }}</td>
                            <td class="amt">@if ($retail) ₹{{ number_format($retail['monthly']) }} @else On request @endif</td>
                            <td>@if ($retail) ₹{{ number_format($retail['yearly']) }} @else Contact sales @endif</td>
                            @if ($retail && $retail['discount'] > 0)<td class="amt-off">₹{{ number_format($retail['yearly_net']) }}</td>@endif
                        </tr>
                    </table>
                </div>
                <div class="sticky-cta">
                    <h4>Get started today</h4>
                    @if ($retail)
                        <div class="big-price">₹{{ number_format($retail['monthly']) }}<span>/mo</span></div>
                    @endif
                    <ul>
                        <li>Multi-counter billing</li>
                        <li>Real-time inventory sync</li>
                        <li>All payment modes</li>
                        <li>Daily sales reporting</li>
                    </ul>
                    <a href="{{ $mc_signup_url }}" class="btn btn-primary" style="width:100%; justify-content:center;">Start Free Trial</a>
                    @if ($retail && $retail['trial_days'] > 0)
                        <div class="setup-note">🎁 {{ $retail['trial_days'] }}-day free trial included</div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="faq">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">FAQ</span>
                <h2>Retail POS — common questions</h2>
            </div>
            <div class="faq-list">
                <div class="faq-item"><button class="faq-q">How many counters and staff logins do I get? <span class="plus">+</span></button><div class="faq-a">Your plan covers multiple counters and staff logins; additional users can be added on request. Contact sales for the exact allocation on your plan.</div></div>
                <div class="faq-item"><button class="faq-q">What does Retail POS need to run? <span class="plus">+</span></button><div class="faq-a">Any browser on a desktop, laptop or tablet with an internet connection — nothing to install, and a barcode scanner works out of the box.</div></div>
                <div class="faq-item"><button class="faq-q">Can I connect Retail POS to my inventory and accounting? <span class="plus">+</span></button><div class="faq-a">Yes — Retail POS is part of the MC Vendor Hub platform, so it syncs directly with Inventory, Accounting, and CRM.</div></div>
                <div class="faq-item"><button class="faq-q">How long does setup take? <span class="plus">+</span></button><div class="faq-a">Most retail stores are fully set up and billing within a day of signup.</div></div>
            </div>
        </div>
    </section>

    <section style="background:var(--bg-soft);">
        <div class="wrap">
            <div class="section-head"><span class="kicker">Explore More</span><h2>Related solutions</h2></div>
            <div class="related-grid">
                <a href="{{ mcv('vendor.mc-vendor.theme.home') }}#modules" class="related-card"><h4>Inventory</h4><p>Real-time stock tracking across your store</p></a>
                <a href="{{ mcv('vendor.mc-vendor.theme.ai-employees') }}" class="related-card"><h4>AI Employees</h4><p>Automate reordering &amp; customer replies</p></a>
                <a href="{{ mcv('vendor.mc-vendor.theme.whatsapp') }}" class="related-card"><h4>WhatsApp Business</h4><p>Answer customer enquiries around the clock</p></a>
                <a href="{{ mcv('vendor.mc-vendor.theme.home') }}#modules" class="related-card"><h4>Accounting</h4><p>Invoices and expenses, synced with POS</p></a>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="wrap" style="text-align:center;">
            <h2>Ready to speed up your checkout counter?</h2>
            <p>Start free. Setup takes under a day.</p>
            <div class="hero-ctas" style="justify-content:center;">
                <a href="{{ $mc_signup_url }}" class="btn btn-primary">Start Free Trial</a>
                <a href="{{ mcv('vendor.mc-vendor.theme.home') }}" class="btn btn-ghost">Back to Home</a>
            </div>
        </div>
    </section>

@endsection
