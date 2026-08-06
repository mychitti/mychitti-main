@extends('mc-vendor.theme.layout')

@section('title', 'Hospital Management Software (HMIS) India | MC Vendor Hub')
@section('meta_description', 'MC HMIS — hospital management software for clinics to 100-bed facilities. Patient records, billing, and staff management, built for India\'s healthcare compliance needs.')

@section('content')

    <div class="wrap breadcrumb"><a href="{{ route('vendor.mc-vendor.theme.home') }}">Home</a><span>/</span><a href="{{ route('vendor.mc-vendor.theme.home') }}#verticals">Industries</a><span>/</span>HMIS</div>

    <section class="page-hero">
        <div class="page-hero-inner">
            <div>
                @php($top_tier = $bedTiers->last())
                <span class="eyebrow"><span class="dot"></span> @if ($top_tier && !$top_tier->is_custom && $top_tier->max_beds) For Clinics to {{ $top_tier->max_beds }}-Bed Hospitals @else For Clinics and Hospitals @endif</span>
                <h1>Hospital management software that <span>scales with your beds</span>, not against your budget.</h1>
                <p class="lede">Patient records, billing, and staff workflows in one system — priced by bed count, so a small clinic isn't paying enterprise-hospital rates.</p>
                <div class="hero-ctas">
                    <a href="{{ route('vendor.mc-vendor.contact') }}" class="btn btn-primary">Request a Demo</a>
                    <a href="#pricing" class="btn btn-ghost">See Pricing →</a>
                </div>
                <p class="hero-note">DPDP Act &amp; data-protection aware patient records</p>
            </div>
            @php($hmis_hero = $mc_pricing['hmis'] ?? null)
            <div class="sticky-cta" style="position:static; max-width:380px;">
                <h4>{{ $hmis_hero['name'] ?? 'Hospital Management' }} — at a glance</h4>
                @if ($hmis_hero)
                    <div class="big-price">₹{{ number_format($hmis_hero['monthly']) }}<span>/mo</span></div>
                @else
                    <div class="big-price">On request<span></span></div>
                @endif
                <ul>
                    <li>OP &amp; IP patient records, wards and beds</li>
                    <li>OP/IP billing with pharmacy dispensing</li>
                    <li>Doctor &amp; nursing staff with duty slots</li>
                    @if ($hmis_hero && $hmis_hero['discount'] > 0)
                        <li>Save {{ (float) $hmis_hero['discount'] }}% paying yearly</li>
                    @endif
                </ul>
                @if ($hmis_hero && $hmis_hero['trial_days'] > 0)
                    <div class="setup-note">🎁 {{ $hmis_hero['trial_days'] }}-day free trial included</div>
                @endif
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Built for Healthcare</span>
                <h2>Everything from OP registration to discharge billing</h2>
            </div>
            <div class="feature-grid">
                <div class="feature-card"><div class="ic">🩺</div><h3>Patient Records</h3><p>Complete, searchable patient history — OP and IP, in one system.</p></div>
                <div class="feature-card"><div class="ic">🛏️</div><h3>Bed &amp; Ward Management</h3><p>Live bed availability across wards, updated in real time.</p></div>
                <div class="feature-card"><div class="ic">🧾</div><h3>Billing &amp; Insurance</h3><p>OP/IP billing, pharmacy charges, and discharge summaries in one flow.</p></div>
                <div class="feature-card"><div class="ic">👨‍⚕️</div><h3>Staff &amp; Duty Rosters</h3><p>Doctor and nursing staff scheduling built into the same system.</p></div>
                <div class="feature-card"><div class="ic">🔒</div><h3>Data Protection Aware</h3><p>Built with the Digital Personal Data Protection Act, 2023 in mind for patient data.</p></div>
                <div class="feature-card"><div class="ic">🤖</div><h3>WhatsApp Appointment Reminders</h3><p>Automated appointment and follow-up reminders sent directly to patients.</p></div>
            </div>
        </div>
    </section>

    <section style="background:var(--bg-soft); padding:96px 0;" id="pricing">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Pricing by Bed Count</span>
                <h2>Pay for the capacity you actually run</h2>
            </div>
            <div class="price-table">
                <table>
                    <tr><th>Facility Size</th><th>Monthly</th><th>Yearly</th></tr>
                    @forelse ($bedTiers as $tier)
                        <tr>
                            <td>{{ $tier->tier_name }} <span style="color:var(--ink-faint); font-size:12px;">({{ $tier->bed_range }})</span></td>
                            <td class="amt">@if ($tier->is_custom) On request @else ₹{{ number_format($tier->price_monthly) }} @endif</td>
                            <td>@if ($tier->is_custom) Contact sales @else ₹{{ number_format($tier->price_yearly ?: $tier->price_monthly * 12) }} @endif</td>
                        </tr>
                    @empty
                        <tr>
                            <td>{{ $mc_pricing['hmis']['name'] ?? 'Hospital Management' }}</td>
                            <td class="amt">@if ($mc_pricing['hmis']) ₹{{ number_format($mc_pricing['hmis']['monthly']) }} @else On request @endif</td>
                            <td>@if ($mc_pricing['hmis']) ₹{{ number_format($mc_pricing['hmis']['yearly_net']) }} @else Contact sales @endif</td>
                        </tr>
                    @endforelse
                </table>
            </div>
            <p class="note-banner">⚠️ Vendors operating HMIS are responsible for obtaining valid patient consent and complying with applicable healthcare data regulations. See our <a href="{{ route('vendor.mc-vendor.mc-vendor-hub-pp') }}" style="color:inherit; font-weight:800; text-decoration:underline;">Privacy Policy</a> for details on how patient data is handled.</p>
            <div class="hero-ctas" style="justify-content:center; margin-top:26px;">
                <a href="{{ route('vendor.mc-vendor.price-calculator') }}" class="btn btn-primary">Calculate Your Exact Price →</a>
            </div>
        </div>
    </section>

    <section class="faq">
        <div class="wrap">
            <div class="section-head"><span class="kicker">FAQ</span><h2>HMIS — common questions</h2></div>
            <div class="faq-list">
                <div class="faq-item"><button class="faq-q">Which plan fits my clinic? <span class="plus">+</span></button><div class="faq-a">Choose the tier matching your bed count. Above the largest listed tier, contact sales for a custom enterprise plan.</div></div>
                <div class="faq-item"><button class="faq-q">Is patient data secure and compliant? <span class="plus">+</span></button><div class="faq-a">MC HMIS is built with the Digital Personal Data Protection Act, 2023 in mind. As the facility, you remain responsible for obtaining patient consent — see our Privacy Policy for the full data-handling breakdown.</div></div>
                <div class="faq-item"><button class="faq-q">Can I upgrade my bed tier later? <span class="plus">+</span></button><div class="faq-a">Yes — you can move up a tier anytime as your facility grows; only the incremental setup fee difference applies.</div></div>
                <div class="faq-item"><button class="faq-q">Does HMIS include billing and pharmacy? <span class="plus">+</span></button><div class="faq-a">Yes — OP/IP billing and pharmacy charges are included in every tier.</div></div>
            </div>
        </div>
    </section>

    <section style="background:var(--bg-soft);">
        <div class="wrap">
            <div class="section-head"><span class="kicker">Explore More</span><h2>Related solutions</h2></div>
            <div class="related-grid">
                <a href="{{ route('vendor.mc-vendor.theme.whatsapp') }}" class="related-card"><h4>WhatsApp Business</h4><p>Automated appointment reminders</p></a>
                <a href="{{ route('vendor.mc-vendor.theme.ai-employees') }}" class="related-card"><h4>AI Employees</h4><p>Reception &amp; scheduling automation</p></a>
                <a href="{{ route('vendor.mc-vendor.theme.home') }}#modules" class="related-card"><h4>HRM</h4><p>Doctor &amp; nursing staff management</p></a>
                <a href="{{ route('vendor.mc-vendor.theme.home') }}#modules" class="related-card"><h4>Accounting</h4><p>Facility-wide financial reporting</p></a>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="wrap" style="text-align:center;">
            <h2>Ready to modernise your facility's workflow?</h2>
            <p>Talk to our team about your bed count and rollout timeline.</p>
            <div class="hero-ctas" style="justify-content:center;">
                <a href="{{ route('vendor.mc-vendor.contact') }}" class="btn btn-primary">Request a Demo</a>
                <a href="{{ route('vendor.mc-vendor.theme.home') }}" class="btn btn-ghost">Back to Home</a>
            </div>
        </div>
    </section>

@endsection
