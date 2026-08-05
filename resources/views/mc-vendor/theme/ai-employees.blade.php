@extends('mc-vendor.theme.layout')

@section('title', 'AI Employees for Business — Hire an AI Workforce | MC Vendor Hub')
@section('meta_description', 'Hire AI Employees — dedicated AI roles for Sales, Marketing, HR, Accounts, and more. Not a chatbot, an AI workforce that works 24×7. Starting from ₹6,999/month.')

@section('content')

    <div class="wrap breadcrumb"><a href="{{ route('vendor.mc-vendor.theme.home') }}">Home</a><span>/</span>AI Employees</div>

    <section class="page-hero">
        <div class="page-hero-inner">
            <div>
                <span class="eyebrow"><span class="dot"></span> Not a Chatbot. An AI Workforce.</span>
                <h1>Hire an <span>AI Employee</span>, not just another tool.</h1>
                <p class="lede">Every AI Employee is a dedicated role — Sales, Marketing, HR, Accounts, and more — with its own memory and its own job. Start with one, build a full AI team as you grow.</p>
                <div class="hero-ctas">
                    <a href="#roles" class="btn btn-primary">See All AI Employees</a>
                    <a href="#pricing" class="btn btn-ghost">Pricing →</a>
                </div>
                <p class="hero-note">First AI Employee from ₹6,999/month · Multi-LLM powered (OpenAI, Claude, Gemini)</p>
            </div>
            <div class="wa-panel" style="background:var(--bg-soft); border:1px solid var(--line); max-width:380px;">
                <div class="chat-meta" style="color:var(--ink-faint);">Reception AI · Live</div>
                <div class="chat-line">Do you have any slots open this Saturday?</div>
                <div class="chat-line out" style="background:var(--blue-pale); color:var(--ink);">Yes — 11 AM and 3 PM are open. Should I book you for 11 AM?</div>
                <div class="chat-meta" style="margin-top:12px; color:var(--ink-faint);">Handled entirely by AI · Handoff to human available anytime</div>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Why AI Employees, Not Chatbots</span>
                <h2>One bot pretending to do everything, or a team that actually knows its job</h2>
            </div>
            <div class="feature-grid">
                <div class="feature-card"><div class="ic">🎯</div><h3>Dedicated Roles</h3><p>Each AI Employee has one job — Sales, HR, Accounts — not a jack-of-all-trades bot.</p></div>
                <div class="feature-card"><div class="ic">🧠</div><h3>Its Own Memory &amp; Budget</h3><p>Every role gets its own token allocation, so busy roles don't starve quiet ones.</p></div>
                <div class="feature-card"><div class="ic">🔀</div><h3>Multi-LLM Routing</h3><p>Tasks are routed across OpenAI, Claude, Gemini and other models based on complexity.</p></div>
                <div class="feature-card"><div class="ic">🤝</div><h3>Human Handoff</h3><p>Every AI Employee can escalate to your own staff, anytime, seamlessly.</p></div>
                <div class="feature-card"><div class="ic">📈</div><h3>Grows With You</h3><p>Start with one role. Add more as your business needs grow — no re-platforming.</p></div>
                <div class="feature-card"><div class="ic">👑</div><h3>CEO AI — The Orchestrator</h3><p>Once you have 2+ AI Employees, CEO AI coordinates across all of them.</p></div>
            </div>
        </div>
    </section>

    <section class="ai-section" id="roles" style="padding:96px 0;">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Meet the Team</span>
                <h2>9 AI Employee roles, ready to hire</h2>
                <p>Every role below is a fully dedicated AI Employee — not a shared bot split across tasks.</p>
            </div>
            <div class="ai-intro-banner">🚀 Start with your first AI Employee from ₹6,999/month — add specialised roles as your business grows.</div>

            <div class="badge-grid">
                <div class="badge-standard">
                    <div class="badge-photo">★</div>
                    <div class="std-content">
                        <h4>Standard AI Employee</h4>
                        <p>Reception · WhatsApp · Data Entry · Customer Support · Task &amp; Lead Management — bundled into one role.</p>
                        <div class="std-tag">Most vendors start here</div>
                    </div>
                    <div class="std-price">₹6,999<span style="font-size:12px; font-weight:400; color:#CFE0F7;">/mo</span></div>
                </div>

                @foreach ([
                    ['S', 'Sales AI', '₹9,999/mo', '3M tokens', 'var(--blue)'],
                    ['IP', 'Inventory & POS AI', '₹15,000/mo', '3M tokens', 'var(--blue)'],
                    ['HR', 'HR AI', '₹15,000/mo', '3M tokens', 'var(--blue)'],
                    ['DM', 'Digital Marketing AI', '₹15,000/mo', '3M tokens', 'var(--blue)'],
                    ['AC', 'Accounts AI', '₹15,000/mo', '3M tokens', 'var(--blue)'],
                    ['PJ', 'Project AI', '₹30,000/mo', '3M tokens', 'var(--blue-dark)'],
                    ['BM', 'Business Manager AI', '₹30,000/mo', '3M tokens', 'var(--blue-dark)'],
                ] as $role)
                    <div class="badge">
                        <div class="badge-clip"></div>
                        <div class="badge-hole"></div>
                        <div class="badge-photo" style="background:{{ $role[4] }};">{{ $role[0] }}</div>
                        <div class="badge-role">{{ $role[1] }}</div>
                        <div class="badge-line"></div>
                        <div class="badge-price">{{ $role[2] }}</div>
                        <div class="badge-tokens">{{ $role[3] }}</div>
                    </div>
                @endforeach

                <div class="badge">
                    <div class="badge-locked">UNLOCKS AT 2+ AI EMPLOYEES</div>
                    <div class="badge-clip"></div>
                    <div class="badge-hole"></div>
                    <div class="badge-photo" style="background:var(--orange);">CEO</div>
                    <div class="badge-role">CEO AI</div>
                    <div class="badge-line"></div>
                    <div class="badge-price">₹50,000/mo</div>
                    <div class="badge-tokens">8M tokens</div>
                </div>
            </div>
            <p style="text-align:center; color:#AFC0D6; font-size:13.5px; margin-top:24px;">Need an industry-specific role? Industry AI (Hospital / School / Retail) is available on request.</p>
        </div>
    </section>

    <section id="pricing">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Onboarding</span>
                <h2>What's included when you build your AI team</h2>
            </div>
            <div class="price-table">
                <table>
                    <tr><th>Service</th><th>Price</th></tr>
                    <tr><td>AI Platform Setup &amp; Business Onboarding</td><td class="amt">₹50,000 (one-time)</td></tr>
                </table>
            </div>
            <div class="feature-grid" style="margin-top:32px;">
                <div class="feature-card"><div class="ic">🔧</div><h3>Business Analysis &amp; AI Configuration</h3><p>We map your workflows before your first AI Employee goes live.</p></div>
                <div class="feature-card"><div class="ic">🔗</div><h3>Module Integration</h3><p>CRM, POS, Inventory — connected so your AI Employees have real context.</p></div>
                <div class="feature-card"><div class="ic">🎓</div><h3>Training &amp; Go-Live Support</h3><p>Your AI Employees are trained on your business data before launch.</p></div>
            </div>
            <p class="note-banner">💡 Additional AI usage beyond each role's included token allocation is billed at the live market LLM rate plus an 18% platform margin — full detail in the Pricing Document.</p>
        </div>
    </section>

    <section class="faq">
        <div class="wrap">
            <div class="section-head"><span class="kicker">FAQ</span><h2>AI Employees — common questions</h2></div>
            <div class="faq-list">
                <div class="faq-item"><button class="faq-q">What happens if an AI Employee runs out of tokens? <span class="plus">+</span></button><div class="faq-a">Each role includes a monthly fair-usage token allocation. Usage beyond that is billed at the live market LLM rate plus an 18% platform margin.</div></div>
                <div class="faq-item"><button class="faq-q">Can an AI Employee hand off to a real person? <span class="plus">+</span></button><div class="faq-a">Yes — every AI Employee can escalate a conversation to your own staff at any point.</div></div>
                <div class="faq-item"><button class="faq-q">Do I need all 9 roles? <span class="plus">+</span></button><div class="faq-a">No — most businesses start with the Standard AI Employee and add specialised roles only as needed.</div></div>
                <div class="faq-item"><button class="faq-q">How is AI-generated output verified? <span class="plus">+</span></button><div class="faq-a">AI Services are automated and should be reviewed before relying on them for business-critical decisions — see our <a href="{{ route('vendor.mc-vendor.mc-vendor-hub-tnc') }}" style="color:var(--blue); font-weight:700;">Terms &amp; Conditions</a> for full detail.</div></div>
            </div>
        </div>
    </section>

    <section style="background:var(--bg-soft);">
        <div class="wrap">
            <div class="section-head"><span class="kicker">Explore More</span><h2>Related solutions</h2></div>
            <div class="related-grid">
                <a href="{{ route('vendor.mc-vendor.theme.whatsapp') }}" class="related-card"><h4>WhatsApp Business</h4><p>The channel most AI Employees work on</p></a>
                <a href="{{ route('vendor.mc-vendor.theme.home') }}#modules" class="related-card"><h4>CRM &amp; Client Mgmt</h4><p>Context your AI Employees use</p></a>
                <a href="{{ route('vendor.mc-vendor.price-calculator') }}" class="related-card"><h4>Full Pricing</h4><p>See the complete pricing document</p></a>
                <a href="{{ route('vendor.mc-vendor.theme.home') }}#verticals" class="related-card"><h4>Industry Solutions</h4><p>HMIS, School, Retail &amp; more</p></a>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="wrap" style="text-align:center;">
            <h2>Your first AI Employee is waiting to start.</h2>
            <p>List your business free. Add the tools — and the team — as you grow.</p>
            <div class="hero-ctas" style="justify-content:center;">
                <a href="{{ route('vendor.mc-vendor.contact') }}" class="btn btn-primary">Build Your AI Team</a>
                <a href="{{ route('vendor.mc-vendor.theme.home') }}" class="btn btn-ghost">Back to Home</a>
            </div>
        </div>
    </section>

@endsection
