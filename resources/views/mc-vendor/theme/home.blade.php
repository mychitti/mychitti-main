@extends('mc-vendor.theme.layout')

@section('title', 'MC Vendor Hub — 2 India\'s First Business Platform with AI Employees')
@section('meta_description', 'Manage Billing, POS, CRM, WhatsApp and hire AI Employees that work 24×7 for your business — all from one platform built for Indian MSMEs.')

@section('content')

    <section class="hero">
        <div class="hero-inner">
            <div>
                <span class="eyebrow"><span class="dot"></span> India's First Business Platform with AI Employees</span>
                <h1>Run billing, POS, CRM &amp; WhatsApp — then <span>hire an AI Employee</span> to run the rest.</h1>
                <p class="lede">Manage Billing, POS, CRM, WhatsApp and hire AI Employees that work 24×7 for your business — all from one platform built for Indian MSMEs.</p>
                <div class="hero-ctas">
                    <a href="{{ $mc_signup_url }}" class="btn btn-primary">List Your Business — Free</a>
                    <a href="#ai" class="btn btn-ghost">Meet the AI Employees →</a>
                </div>
                <p class="hero-note">Free billing up to 1,000 bills · Free business webpage · No card required</p>
            </div>
            <div class="hero-visual">
                <div class="float-chip chip-1"><span class="ic" style="background:var(--green);">✓</span> MC Verified Vendor</div>
                <div class="float-chip chip-2"><span class="ic" style="background:var(--orange);">₹</span> ₹6,999 first AI hire</div>
                <div class="hero-card">
                    <img src="{{ asset('assets/mcvendorhub/img/logo-mark.png') }}" alt="MC Vendor Hub mascot">
                    <h4>Your first AI Employee</h4>
                    <p>Reception · WhatsApp · Support — live in minutes</p>
                </div>
            </div>
        </div>
    </section>

    <div class="stats-strip">
        <div class="stats-grid">
            <div class="stat-cell"><div class="num">4,900+</div><div class="lbl">Vendors Listed</div></div>
            <div class="stat-cell"><div class="num">2,600+</div><div class="lbl">Active Businesses</div></div>
            <div class="stat-cell"><div class="num">2,000+</div><div class="lbl">Leads Generated</div></div>
            <div class="stat-cell"><div class="num">24×7</div><div class="lbl">AI Employees</div></div>
            <div class="stat-cell"><div class="num">{{ $vendor_modules->count() > 0 ? $vendor_modules->count() . '+' : '12+' }}</div><div class="lbl">Business Modules</div></div>
        </div>
    </div>

    <section class="why">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">The Problem</span>
                <h2>Running a business shouldn't mean running six different apps</h2>
                <p>Billing in one app. Leads in a notebook. Staff attendance on paper. Customer replies on your personal WhatsApp, at midnight.</p>
            </div>
            <div class="why-grid">
                <div class="why-card">
                    <div class="ic">🗂️</div>
                    <h3>Scattered tools, scattered data</h3>
                    <p>Your billing, inventory, and customer records live in different places — and never talk to each other.</p>
                </div>
                <div class="why-card">
                    <div class="ic">🌙</div>
                    <h3>You're the only employee who never sleeps</h3>
                    <p>Every enquiry, every follow-up, every "are you open?" message lands on you — even after closing time.</p>
                </div>
                <div class="why-card">
                    <div class="ic">📈</div>
                    <h3>Growth means hiring you can't afford yet</h3>
                    <p>A salesperson or support agent is a big commitment. An AI Employee costs less than a week of chai.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="oneplatform" id="why-us">
        <div class="wrap">
            <span class="kicker">The MC Vendor Hub Difference</span>
            <h2>One Login. One Platform. Everything Your Business Needs.</h2>
            <p class="sub">Not nine subscriptions stitched together — one system where every part already talks to every other part.</p>
            <div class="op-chip-grid">
                @forelse ($vendor_modules->take(9) as $module)
                    <div class="op-chip"><span class="dot"></span> {{ $module->name }}</div>
                @empty
                    @foreach (['Billing', 'POS', 'CRM', 'Inventory', 'HRM', 'WhatsApp', 'AI Employees', 'Lead Generation', 'Dedicated Website'] as $chip)
                        <div class="op-chip"><span class="dot"></span> {{ $chip }}</div>
                    @endforeach
                @endforelse
            </div>
        </div>
    </section>

    <section class="comparison">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Why Not Just Use Separate Apps?</span>
                <h2>What you'd need without MC Vendor Hub</h2>
                <p>Most billing or POS tools solve one problem. This solves the whole day.</p>
            </div>
            <table>
                <tr><th>&nbsp;</th><th class="others">Other Tools</th><th class="us">MC Vendor Hub</th></tr>
                <tr><td class="label">Billing &amp; Accounting</td><td class="others">Separate app</td><td class="us">Included</td></tr>
                <tr><td class="label">CRM &amp; POS</td><td class="others">Another subscription</td><td class="us">Included</td></tr>
                <tr><td class="label">WhatsApp Messaging</td><td class="others">Third-party workaround</td><td class="us">Native, Meta-verified</td></tr>
                <tr><td class="label">Customer Leads</td><td class="others">Not provided</td><td class="us">Delivered on-platform</td></tr>
                <tr><td class="label">AI Employees</td><td class="others">Not available</td><td class="us">9 dedicated roles</td></tr>
                <tr><td class="label">Monthly Subscriptions</td><td class="others">3–5 separate bills</td><td class="us">One bill</td></tr>
            </table>
        </div>
    </section>

    @php($mc_modules = collect($mc_modules ?? []))
    @if ($mc_modules->count())
        <section class="preview">
            <div class="wrap">
                <div class="section-head">
                    <span class="kicker">What It Costs</span>
                    <h2>Every module, and what it actually costs</h2>
                    <p>Live pricing straight from the platform — nothing here is a sample. Switch on what you need; the rest stays off your bill.</p>
                </div>
                <div class="vert-grid">
                    @foreach ($mc_modules as $module)
                        <div class="vert-card">
                            <h4>{{ $module['name'] }}</h4>
                            <p>
                                @if ($module['trial_days'] > 0)
                                    {{ $module['trial_days'] }}-day free trial, then billed monthly.
                                @else
                                    Billed monthly, cancel when you no longer need it.
                                @endif
                                @if ($module['discount'] > 0)
                                    Pay yearly and save {{ (float) $module['discount'] }}%.
                                @endif
                            </p>
                            <span class="from">₹{{ number_format($module['monthly']) }}/mo</span>
                        </div>
                    @endforeach
                </div>
                <p class="preview-note">Prices exclude GST and update automatically as plans change. <a href="{{ route('vendor.mc-vendor.price-calculator') }}" style="color:var(--blue); font-weight:700;">Price your exact combination →</a></p>
            </div>
        </section>
    @endif

    <section class="ai-section" id="ai">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Not a chatbot. An AI Workforce.</span>
                <h2>Hire AI Employees, not just tools</h2>
                <p>Every AI Employee is a dedicated role with its own memory, its own token budget, and its own job — not one bot pretending to do everything.</p>
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

            <div class="ai-cta"><a href="{{ route('vendor.mc-vendor.theme.ai-employees') }}" class="btn btn-orange">Build Your AI Team →</a></div>
        </div>
    </section>

    <section class="modules" id="modules">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Core Platform</span>
                <h2>Everything your business runs on, in one place</h2>
                <p>One subscription. Every module talks to every other module — no more re-typing the same customer three times.</p>
            </div>
            <div class="ledger-sheet">
                @if ($vendor_modules->count())
                    @php($module_icons = ['🧾', '📒', '📦', '👥', '🧑‍💼', '✅', '🎯', '🌐', '💬', '📊', '🛠️', '🔔'])
                    @foreach ($vendor_modules as $key => $module)
                        <div class="ledger-row" style="animation-delay:{{ number_format(0.03 * ($key + 1), 2) }}s">
                            <span class="ic2">{{ $module_icons[$key % count($module_icons)] }}</span>
                            <a class="name" href="{{ route('vendor.mc-vendor.mc-module', $module->slug) }}">{{ $module->name }}</a>
                            <span class="ledger-dots"></span>
                            <span class="desc">{{ \Illuminate\Support\Str::limit(trim(strip_tags($module->content ?? '')), 60) }}</span>
                            <span class="tag">Bundled</span>
                        </div>
                    @endforeach
                @else
                    @foreach ([
                        ['🧾', 'Billing & POS', 'Fast checkout, multiple payment modes'],
                        ['📒', 'Accounting', 'Invoices, expenses, ledgers'],
                        ['📦', 'Inventory', 'Real-time stock tracking'],
                        ['👥', 'CRM & Client Mgmt', 'Every customer, one record'],
                        ['🧑‍💼', 'HRM', 'Staff, attendance, payroll'],
                        ['✅', 'Task & Project', 'Assign, track, deliver'],
                        ['🎯', 'Lead Management', 'Capture, qualify, convert'],
                        ['🌐', 'Dedicated Webpage', 'Your business, discoverable online'],
                    ] as $key => $row)
                        <div class="ledger-row" style="animation-delay:{{ number_format(0.03 * ($key + 1), 2) }}s">
                            <span class="ic2">{{ $row[0] }}</span>
                            <span class="name">{{ $row[1] }}</span>
                            <span class="ledger-dots"></span>
                            <span class="desc">{{ $row[2] }}</span>
                            <span class="tag">Bundled</span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </section>

    <section class="wa-section" id="whatsapp">
        <div class="wrap">
            <div class="wa-inner">
                <div>
                    <span class="kicker">WhatsApp Business</span>
                    <h2>Answer every customer, even at midnight</h2>
                    <p>Meta-verified WhatsApp Business messaging with an AI Employee reading and replying before your first cup of chai.</p>
                    <ul class="wa-list">
                        <li>Official Meta WhatsApp Business Platform, not a workaround</li>
                        <li>Automated booking, lead capture &amp; FAQ replies</li>
                        <li>Human handover to your own staff, anytime</li>
                        <li>Appointment reminders sent automatically, on your schedule</li>
                    </ul>
                    <a href="{{ route('vendor.mc-vendor.theme.whatsapp') }}" class="btn" style="background:var(--white); color:var(--green-dark); border-color:var(--white); font-weight:800;">Set Up WhatsApp →</a>
                </div>
                <div class="wa-panel">
                    <div class="chat-meta">Today, 11:47 PM</div>
                    <div class="chat-line">Are you open tomorrow for a haircut appointment?</div>
                    <div class="chat-line out">Yes! We open at 10 AM. I've booked you a slot at 10:30 AM — see you then 🙂</div>
                    <div class="chat-meta" style="margin-top:12px;">Replied by Reception AI · 0.4 sec</div>
                </div>
            </div>
        </div>
    </section>

    <section class="verticals" id="verticals">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Specialised Solutions</span>
                <h2>Built for how your industry actually works</h2>
                <p>Beyond the core platform — purpose-built systems for businesses with specific compliance and workflow needs.</p>
            </div>
            @php($hmis_from = $bedTiers->where('is_custom', false)->min('price_monthly') ?? ($mc_pricing['hmis']['monthly'] ?? null))
            @php($school_from = $studentTiers->where('is_custom', false)->min('price_monthly') ?? ($mc_pricing['school']['monthly'] ?? null))
            <div class="vert-grid">
                <a href="{{ route('vendor.mc-vendor.theme.hmis') }}" class="vert-card"><h4>MC HMIS</h4><p>Hospital management for clinics through to multi-ward facilities.</p>@if ($hmis_from)<span class="from">From ₹{{ number_format($hmis_from) }}/mo</span>@endif</a>
                <a href="{{ route('vendor.mc-vendor.theme.retail-pos') }}" class="vert-card"><h4>Retail POS</h4><p>Multi-counter billing built for high-footfall retail stores.</p>@if ($mc_pricing['retail'])<span class="from">From ₹{{ number_format($mc_pricing['retail']['monthly']) }}/mo</span>@endif</a>
                <a href="{{ route('vendor.mc-vendor.theme.school-management') }}" class="vert-card"><h4>School Management</h4><p>Admissions, fees, attendance and parent communication.</p>@if ($school_from)<span class="from">From ₹{{ number_format($school_from) }}/mo</span>@endif</a>
                <a href="{{ route('vendor.mc-vendor.theme.laundry-management') }}" class="vert-card"><h4>Laundry Management</h4><p>Order tracking from drop-off to pickup, with itemised billing.</p>@if ($mc_pricing['laundry'])<span class="from">From ₹{{ number_format($mc_pricing['laundry']['monthly']) }}/mo</span>@endif</a>
            </div>
        </div>
    </section>

    <section class="pricing-teaser" id="pricing">
        <div class="wrap">
            <span class="kicker">Pricing</span>
            <h2>Straightforward pricing, no hidden line items</h2>
            <p>Start free. Add AI Employees, WhatsApp, and specialised modules only when your business is ready for them.</p>
            <div class="price-strip">
                @if ($base_plan)
                    <div class="price-cell"><div class="label">{{ $base_plan->name }}</div><div class="amt">₹{{ number_format($base_plan->price) }}<span>/mo</span></div></div>
                @endif
                @if ($mc_pricing['whatsapp'])
                    <div class="price-cell"><div class="label">{{ $mc_pricing['whatsapp']['name'] }}</div><div class="amt">₹{{ number_format($mc_pricing['whatsapp']['monthly']) }}<span>/mo</span></div></div>
                @endif
                <div class="price-cell"><div class="label">First AI Employee</div><div class="amt">from ₹6,999<span>/mo</span></div></div>
            </div>
            <div class="hero-ctas" style="justify-content:center; margin-top:8px;">
                <a href="{{ route('vendor.mc-vendor.price-calculator') }}" class="btn btn-orange">Open the Price Calculator →</a>
                @if ($mc_plan_action)
                    <a href="#" class="btn btn-ghost" id="openPlanModal">Request a Custom Plan</a>
                @endif
            </div>
        </div>
    </section>

    <section class="testimonials">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">What Vendors Say</span>
                <h2>Trusted by businesses across Andhra Pradesh</h2>
            </div>
            <div class="testi-grid">
                <div class="testi-card">
                    <div class="testi-stars">★★★★★</div>
                    <p class="quote">"Since I added the WhatsApp AI, I stopped losing customers who message after closing time. It books appointments while I sleep."</p>
                    <div class="testi-person"><div class="testi-avatar">R</div><div><div class="nm">Ramesh K.</div><div class="rl">Salon Owner, Tirupati</div></div></div>
                </div>
                <div class="testi-card">
                    <div class="testi-stars">★★★★★</div>
                    <p class="quote">"Billing, inventory and my staff attendance — all in one login. I used to manage three apps before this."</p>
                    <div class="testi-person"><div class="testi-avatar">P</div><div><div class="nm">Priya S.</div><div class="rl">Retail Store Owner, Hyderabad</div></div></div>
                </div>
                <div class="testi-card">
                    <div class="testi-stars">★★★★★</div>
                    <p class="quote">"The leads I get through MC Vendor Hub are genuine — I can see exactly which enquiry became a customer."</p>
                    <div class="testi-person"><div class="testi-avatar">A</div><div><div class="nm">Anand M.</div><div class="rl">Hardware Distributor, Chittoor</div></div></div>
                </div>
            </div>
            <p class="testi-placeholder-note">Sample testimonials shown — replace with real vendor quotes and photos before launch.</p>
        </div>
    </section>

    <section class="partners">
        <div class="wrap">
            <span class="kicker">Integrates With</span>
            <h2 style="font-size:22px; margin-top:14px;">Built on trusted infrastructure</h2>
            <div class="partner-row">
                <div class="partner-chip"><span class="sw" style="background:#25D366;">W</span> WhatsApp Business (Meta)</div>
                <div class="partner-chip"><span class="sw" style="background:#0A2540;">R</span> Razorpay</div>
            </div>
        </div>
    </section>

    <section class="trust">
        <div class="wrap">
            <span class="kicker">Trust &amp; Verification</span>
            <h2>Every vendor can be checked, not just claimed</h2>
            <div class="trust-marks">
                <div class="trust-mark"><div class="trust-circle">MC<br>VERIFIED</div><p>Business identity &amp; documents checked</p></div>
                <div class="trust-mark"><div class="trust-circle" style="border-color:var(--green); color:var(--green-dark);">MC<br>TRUSTED</div><p>Track record meets platform standards</p></div>
                <div class="trust-mark"><div class="trust-circle" style="border-color:var(--orange); color:var(--orange-dark);">STAFF<br>ID</div><p>Every employee verified before a visit</p></div>
            </div>
        </div>
    </section>

    <section class="faq" id="faq">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">Quick FAQ</span>
                <h2>Questions vendors ask us the most</h2>
            </div>
            <div class="faq-list">
                <div class="faq-item">
                    <button class="faq-q">What is MC Vendor Hub? <span class="plus">+</span></button>
                    <div class="faq-a">An all-in-one business platform for MSMEs — billing, POS, CRM, inventory, HRM, WhatsApp messaging, and AI Employees, all in one subscription.</div>
                </div>
                <div class="faq-item">
                    <button class="faq-q">What's an AI Employee? <span class="plus">+</span></button>
                    <div class="faq-a">A dedicated AI role — like Reception, Sales, or Accounts — that works 24×7 on a specific job, not a generic chatbot trying to do everything.</div>
                </div>
                <div class="faq-item">
                    <button class="faq-q">Can I get customer leads directly? <span class="plus">+</span></button>
                    <div class="faq-a">Yes — MC Vendor Hub provides paid enquiries (leads) so vendors can connect with genuine customers actively looking for their products or services.</div>
                </div>
                <div class="faq-item">
                    <button class="faq-q">Is my business data secure? <span class="plus">+</span></button>
                    <div class="faq-a">Yes. We use encrypted cloud infrastructure with access controls, and we never sell your data to third parties.</div>
                </div>
                <div class="faq-item">
                    <button class="faq-q">Do you provide training and support? <span class="plus">+</span></button>
                    <div class="faq-a">Yes — free onboarding, staff training, and 24×7 chat &amp; email support, with phone support during working hours.</div>
                </div>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="wrap" style="text-align:center;">
            <h2>Your first AI Employee is waiting to start.</h2>
            <p>List your business free. Add the tools — and the team — as you grow.</p>
            <div class="hero-ctas" style="justify-content:center;">
                <a href="{{ $mc_signup_url }}" class="btn btn-primary">List Your Business — Free</a>
                <a href="{{ route('vendor.mc-vendor.contact') }}" class="btn btn-ghost">Talk to Sales</a>
            </div>
        </div>
    </section>

    @if ($mc_plan_action)
    <div class="mc-modal" id="planModal" role="dialog" aria-modal="true" aria-labelledby="planModalTitle">
        <div class="mc-modal-box">
            <div class="mc-modal-head">
                <h3 id="planModalTitle">Customised Subscription Plan Request</h3>
                <button type="button" class="mc-modal-close" data-close-plan aria-label="Close">&times;</button>
            </div>
            <div class="mc-modal-body" id="planFormWrap">
                <p class="intro">Tell us about your needs and we'll put together a subscription plan that fits.</p>
                <div class="mc-form-alert" id="planFormAlert"></div>
                <form id="planForm" method="post" action="{{ $mc_plan_action }}">
                    @csrf
                    <div class="mc-field-row">
                        <div class="mc-field">
                            <label for="planCompany">Company Name</label>
                            <input type="text" id="planCompany" name="company_name" placeholder="Enter company name">
                            <div class="err"></div>
                        </div>
                        <div class="mc-field">
                            <label for="planContact" class="required">Contact Name</label>
                            <input type="text" id="planContact" name="contact_name" placeholder="Enter your name" required>
                            <div class="err"></div>
                        </div>
                    </div>
                    <div class="mc-field-row">
                        <div class="mc-field">
                            <label for="planEmail">Email Address</label>
                            <input type="email" id="planEmail" name="email" placeholder="example@gmail.com">
                            <div class="err"></div>
                        </div>
                        <div class="mc-field">
                            <label for="planPhone" class="required">Phone Number</label>
                            <input type="tel" id="planPhone" name="phone" placeholder="9988776655" required>
                            <div class="err"></div>
                        </div>
                    </div>
                    <div class="mc-field">
                        <label for="planBusinessType" class="required">Business Type</label>
                        <select id="planBusinessType" name="business_type" required>
                            <option value="">Select business type</option>
                            @foreach (['Professionals', 'Manufacturer', 'Business', 'Stores', 'Shops', 'Self Employee', 'Skilled Labour', 'Farmer', 'Contractor', 'Hospital', 'School', 'Retail & Wholesaler', 'Other'] as $business_type)
                                <option value="{{ $business_type }}">{{ $business_type }}</option>
                            @endforeach
                        </select>
                        <div class="err"></div>
                    </div>
                    <div class="mc-field" id="planFeaturesField">
                        <label class="required">Required Features</label>
                        <div class="mc-feature-grid">
                            @foreach ($features as $feature)
                                <div class="mc-feature" data-feature="{{ $feature->key }}">
                                    <span class="box"></span>
                                    <span>{{ $feature->key }}</span>
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="features" id="planFeaturesInput">
                        <div class="err"></div>
                    </div>
                    <div class="mc-field">
                        <label for="planNotes">Additional Requirements</label>
                        <textarea id="planNotes" name="additional_requirements" rows="3" placeholder="Any specific requirements, integrations, or customisations you need..."></textarea>
                        <div class="err"></div>
                    </div>
                    <button type="submit" class="btn btn-primary" id="planSubmit" style="width:100%; justify-content:center;">
                        <span id="planSubmitText">Submit Request</span>
                    </button>
                </form>
            </div>
            <div class="mc-modal-body mc-modal-success" id="planSuccess" style="display:none;">
                <div class="tick">✓</div>
                <h3>Request Submitted</h3>
                <p id="planSuccessMsg">Thank you for your interest. Our team will review your requirements and contact you within 1–2 business days.</p>
                <button type="button" class="btn btn-ghost" data-close-plan>Close</button>
            </div>
        </div>
    </div>
    @endif

@endsection

@section('scripts')
    <script>
        (function() {
            const modal = document.getElementById('planModal');
            const trigger = document.getElementById('openPlanModal');
            if (!modal || !trigger) return;

            const form = document.getElementById('planForm');
            const formWrap = document.getElementById('planFormWrap');
            const success = document.getElementById('planSuccess');
            const alertBox = document.getElementById('planFormAlert');
            const featuresInput = document.getElementById('planFeaturesInput');
            const featuresField = document.getElementById('planFeaturesField');
            const submitBtn = document.getElementById('planSubmit');
            const submitText = document.getElementById('planSubmitText');
            const selected = new Set();

            function openModal(e) {
                if (e) e.preventDefault();
                modal.classList.add('open');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                modal.classList.remove('open');
                document.body.style.overflow = '';
            }

            trigger.addEventListener('click', openModal);
            modal.querySelectorAll('[data-close-plan]').forEach(b => b.addEventListener('click', closeModal));
            modal.addEventListener('click', e => {
                if (e.target === modal) closeModal();
            });
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && modal.classList.contains('open')) closeModal();
            });

            modal.querySelectorAll('.mc-feature').forEach(chip => {
                chip.addEventListener('click', () => {
                    const key = chip.dataset.feature;
                    if (selected.has(key)) {
                        selected.delete(key);
                        chip.classList.remove('selected');
                    } else {
                        selected.add(key);
                        chip.classList.add('selected');
                    }
                    featuresInput.value = JSON.stringify([...selected]);
                    featuresField.classList.remove('invalid');
                });
            });

            function showError(message) {
                alertBox.textContent = message;
                alertBox.classList.add('show');
            }

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                alertBox.classList.remove('show');
                form.querySelectorAll('.mc-field').forEach(f => f.classList.remove('invalid'));

                if (selected.size === 0) {
                    featuresField.classList.add('invalid');
                    featuresField.querySelector('.err').textContent = 'Select at least one feature.';
                    showError('Please select at least one required feature.');
                    return;
                }

                submitBtn.disabled = true;
                submitText.textContent = 'Submitting...';

                const payload = new FormData(form);
                payload.delete('features');
                selected.forEach(key => payload.append('features[]', key));

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: payload
                    });

                    const data = await response.json().catch(() => ({}));

                    if (response.status === 422 && data.errors) {
                        Object.entries(data.errors).forEach(([field, messages]) => {
                            const input = form.querySelector('[name="' + field + '"]');
                            const wrap = input ? input.closest('.mc-field') : null;
                            if (wrap) {
                                wrap.classList.add('invalid');
                                wrap.querySelector('.err').textContent = messages[0];
                            }
                        });
                        showError('Please correct the highlighted fields.');
                        return;
                    }

                    if (!response.ok || data.status === false) {
                        showError(data.message || 'Something went wrong. Please try again.');
                        return;
                    }

                    if (data.message) document.getElementById('planSuccessMsg').textContent = data.message;
                    formWrap.style.display = 'none';
                    success.style.display = 'block';
                } catch (err) {
                    showError('Network error. Please check your connection and try again.');
                } finally {
                    submitBtn.disabled = false;
                    submitText.textContent = 'Submit Request';
                }
            });
        })();
    </script>
@endsection
