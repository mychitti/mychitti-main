@extends('mc-vendor.theme.layout')

@section('title', 'School Management Software India | MC Vendor Hub')
@section('meta_description', 'School management software for admissions, fee collection, attendance and parent communication — built for Indian schools, with WhatsApp updates included.')

@section('content')

    <div class="wrap breadcrumb"><a href="{{ route('vendor.mc-vendor.theme.home') }}">Home</a><span>/</span><a href="{{ route('vendor.mc-vendor.theme.home') }}#verticals">Industries</a><span>/</span>School Management</div>

    <section class="page-hero">
        <div class="page-hero-inner">
            <div>
                <span class="eyebrow"><span class="dot"></span> For Schools of Every Size</span>
                <h1>Run admissions, fees, and parent communication <span>without a spreadsheet</span> in sight.</h1>
                <p class="lede">Everything a school office needs — admissions, fee collection, attendance, and parent updates — in one system built for Indian schools.</p>
                <div class="hero-ctas">
                    <a href="{{ route('vendor.mc-vendor.contact') }}" class="btn btn-primary">Request a Demo</a>
                    <a href="#pricing" class="btn btn-ghost">See Pricing →</a>
                </div>
                <p class="hero-note">Priced by student count · {{ $studentTiers->count() ?: 'Flexible' }} {{ $studentTiers->count() ? \Illuminate\Support\Str::plural('plan', $studentTiers->count()) . ' to choose from' : 'plans' }}</p>
            </div>
            <div class="preview-card" style="max-width:380px;">
                <div class="preview-chrome"><span></span><span></span><span></span></div>
                <div class="preview-body">
                    <div class="mock-bar" style="width:45%;"></div>
                    <div class="mock-row"><span>🎓 Fee Collection</span><span class="mock-pill" style="background:var(--green-pale); color:var(--green-dark);">82% Paid</span></div>
                    <div class="mock-row"><span>🧑‍🎓 Attendance Today</span><span class="mock-pill" style="background:var(--blue-pale); color:var(--blue);">96%</span></div>
                    <div class="mock-row"><span>📩 Parent Notices Sent</span><span class="mock-pill" style="background:#F1F1F1; color:var(--ink-faint);">124</span></div>
                </div>
                <div class="preview-label"><h4>School Dashboard</h4></div>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Built for School Offices</span>
                <h2>From admission enquiry to report card</h2>
            </div>
            <div class="feature-grid">
                <div class="feature-card"><div class="ic">📝</div><h3>Admissions</h3><p>Track enquiries through to enrolment without losing a single lead.</p></div>
                <div class="feature-card"><div class="ic">💰</div><h3>Fee Management</h3><p>Track dues, send reminders, and collect fees online or offline.</p></div>
                <div class="feature-card"><div class="ic">✅</div><h3>Attendance</h3><p>Daily attendance tracking, class-wise and student-wise.</p></div>
                <div class="feature-card"><div class="ic">💬</div><h3>Parent Communication</h3><p>Notices, fee reminders, and updates sent straight to parents via WhatsApp.</p></div>
                <div class="feature-card"><div class="ic">🧑‍🏫</div><h3>Staff Management</h3><p>Teacher records, attendance, and role-based access, all included.</p></div>
                <div class="feature-card"><div class="ic">📈</div><h3>Scales With You</h3><p>Add students or staff beyond the base plan anytime as you grow.</p></div>
            </div>
        </div>
    </section>

    <section style="background:var(--bg-soft); padding:96px 0;" id="pricing">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Pricing</span>
                <h2>Priced by the number of students you teach</h2>
            </div>
            <div class="content-split">
                <div class="price-table">
                    <table>
                        <tr><th>Plan</th><th>Monthly</th><th>Yearly</th></tr>
                        @forelse ($studentTiers as $tier)
                            <tr>
                                <td>{{ $tier->tier_name }}<br><span style="color:var(--ink-faint); font-size:12px;">{{ $tier->student_range }}</span></td>
                                <td class="amt">@if ($tier->is_custom) On request @else ₹{{ number_format($tier->price_monthly) }} @endif</td>
                                <td>@if ($tier->is_custom) Contact sales @else ₹{{ number_format($tier->price_yearly ?: $tier->price_monthly * 12) }} @endif</td>
                            </tr>
                        @empty
                            <tr>
                                <td>{{ $mc_pricing['school']['name'] ?? 'School Management' }}</td>
                                <td class="amt">@if ($mc_pricing['school']) ₹{{ number_format($mc_pricing['school']['monthly']) }} @else On request @endif</td>
                                <td>@if ($mc_pricing['school']) ₹{{ number_format($mc_pricing['school']['yearly_net']) }} @else Contact sales @endif</td>
                            </tr>
                        @endforelse
                    </table>
                </div>
                @php($entry_tier = $studentTiers->first(fn($t) => !$t->is_custom))
                <div class="sticky-cta">
                    <h4>Get started today</h4>
                    @if ($entry_tier)
                        <div class="big-price">₹{{ number_format($entry_tier->price_monthly) }}<span>/mo</span></div>
                    @elseif ($mc_pricing['school'])
                        <div class="big-price">₹{{ number_format($mc_pricing['school']['monthly']) }}<span>/mo</span></div>
                    @endif
                    <ul>
                        @if ($entry_tier)
                            <li>{{ $entry_tier->student_range }}</li>
                            @if ($entry_tier->max_branches)
                                <li>Up to {{ $entry_tier->max_branches }} {{ \Illuminate\Support\Str::plural('branch', $entry_tier->max_branches) }}</li>
                            @endif
                        @endif
                        <li>Admissions, fees &amp; attendance included</li>
                        <li>WhatsApp parent communication ready</li>
                    </ul>
                    <a href="{{ route('vendor.mc-vendor.contact') }}" class="btn btn-primary" style="width:100%; justify-content:center;">Request a Demo</a>
                    @if ($mc_pricing['school'] && $mc_pricing['school']['trial_days'] > 0)
                        <div class="setup-note">🎁 {{ $mc_pricing['school']['trial_days'] }}-day free trial included</div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="faq">
        <div class="wrap">
            <div class="section-head"><span class="kicker">FAQ</span><h2>School Management — common questions</h2></div>
            <div class="faq-list">
                <div class="faq-item"><button class="faq-q">What if my school outgrows its plan? <span class="plus">+</span></button><div class="faq-a">Move up to the next student tier at any time — you only pay the difference from your renewal date. For very large schools, contact sales for a custom plan.</div></div>
                <div class="faq-item"><button class="faq-q">Can parents receive fee reminders on WhatsApp? <span class="plus">+</span></button><div class="faq-a">Yes — fee reminders, notices, and attendance alerts can be sent directly via WhatsApp Business messaging.</div></div>
                <div class="faq-item"><button class="faq-q">Is training provided for office staff? <span class="plus">+</span></button><div class="faq-a">Yes — onboarding includes free training sessions for your administrative staff.</div></div>
                <div class="faq-item"><button class="faq-q">How is student data protected? <span class="plus">+</span></button><div class="faq-a">Student data is processed under our <a href="{{ route('vendor.mc-vendor.mc-vendor-hub-pp') }}" style="color:var(--blue); font-weight:700;">Privacy Policy</a>, with the school responsible for parental/guardian consent for enrolled minors.</div></div>
            </div>
        </div>
    </section>

    <section style="background:var(--bg-soft);">
        <div class="wrap">
            <div class="section-head"><span class="kicker">Explore More</span><h2>Related solutions</h2></div>
            <div class="related-grid">
                <a href="{{ route('vendor.mc-vendor.theme.whatsapp') }}" class="related-card"><h4>WhatsApp Business</h4><p>Automated parent notifications</p></a>
                <a href="{{ route('vendor.mc-vendor.theme.ai-employees') }}" class="related-card"><h4>AI Employees</h4><p>Admissions enquiry automation</p></a>
                <a href="{{ route('vendor.mc-vendor.theme.home') }}#modules" class="related-card"><h4>Accounting</h4><p>Fee collection &amp; financial reporting</p></a>
                <a href="{{ route('vendor.mc-vendor.theme.home') }}#modules" class="related-card"><h4>HRM</h4><p>Teacher &amp; staff management</p></a>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="wrap" style="text-align:center;">
            <h2>Ready to simplify your school office?</h2>
            <p>See how MC Vendor Hub handles admissions to report cards.</p>
            <div class="hero-ctas" style="justify-content:center;">
                <a href="{{ route('vendor.mc-vendor.contact') }}" class="btn btn-primary">Request a Demo</a>
                <a href="{{ route('vendor.mc-vendor.theme.home') }}" class="btn btn-ghost">Back to Home</a>
            </div>
        </div>
    </section>

@endsection
