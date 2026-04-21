<!DOCTYPE html>


<html>

<head>
    <meta charset="utf-8" /> 
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MC Vendor Hub — Price Calculator</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/vendor/icon-set/style.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin') }}/css/toastr.css">
    <link rel="stylesheet" href="{{ asset('assets/admin') }}/css/common.css">

    <link href="{{ asset('assets/front/lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --pc-green:       #81c408;
            --pc-green-dark:  #6aa006;
            --pc-green-light: #f3ffdf;
            --pc-green-mid:   #e6f9c4;
            --pc-green-pale:  #f8fdf0;
            --pc-text:        #1e2d00;
            --pc-text-muted:  #5a6a44;
            --pc-border:      #d4e8b0;
        }

        /* ── Page wrapper ── */
        body { background: #f5f7f2; font-family: 'Inter', sans-serif; }

        .pc-page-hero {
            background: linear-gradient(135deg, #1a3300 0%, #2d5a00 60%, #3d7a00 100%);
            color: white;
            padding: 48px 24px 36px;
            text-align: center;
            margin-bottom: 0;
        }
        .pc-page-hero h1 { font-size: 28px; font-weight: 700; margin: 0 0 8px; letter-spacing: -0.3px; }
        .pc-page-hero p  { font-size: 15px; color: rgba(255,255,255,0.75); margin: 0; }

        .pc-container {
            max-width: 1160px;
            margin: 0 auto;
            padding: 28px 16px 60px;
        }

        /* ── Panel ── */
        .pc-panel { display: none; }
        .pc-panel.pc-active { display: block; animation: pcFadeIn 0.3s; }
        @keyframes pcFadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Global duration strip ── */
        .pc-duration-strip {
            background: white;
            border: 1px solid var(--pc-border);
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .pc-strip-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: var(--pc-text-muted);
            margin-bottom: 12px;
        }
        .pc-duration-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
            gap: 10px;
        }
        .pc-global-duration-card {
            padding: 14px 10px;
            background: var(--pc-green-light);
            border: 2px solid transparent;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: var(--pc-text);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .2s;
            text-align: center;
        }
        .pc-global-duration-card:hover { border-color: var(--pc-green); }
        .pc-global-duration-card.pc-selected {
            background: var(--pc-green);
            border-color: var(--pc-green);
            color: white;
            box-shadow: 0 4px 12px rgba(129,196,8,.35);
        }

        /* ── Two-column layout ── */
        .pc-layout { display: grid; grid-template-columns: 1fr 320px; gap: 20px; align-items: start; }
        @media (max-width: 860px) { .pc-layout { grid-template-columns: 1fr; } }

        /* ── Section headings ── */
        .pc-section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .7px;
            color: var(--pc-green-dark);
            margin-bottom: 12px;
        }

        /* ── Module cards ── */
        .pc-module-item {
            background: white;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 10px;
            border: 2px solid #eaeaea;
            transition: all .25s;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
        }
        .pc-module-item:hover { border-color: #c5e37a; }
        .pc-module-item.pc-selected {
            border-color: var(--pc-green);
            background: var(--pc-green-pale);
            box-shadow: 0 2px 10px rgba(129,196,8,.15);
        }
        .pc-module-top {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Custom checkbox */
        .pc-checkbox {
            appearance: none;
            -webkit-appearance: none;
            width: 20px; height: 20px;
            border: 2px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
            flex-shrink: 0;
            transition: all .2s;
            position: relative;
        }
        .pc-checkbox:checked {
            background: var(--pc-green);
            border-color: var(--pc-green);
        }
        .pc-checkbox:checked::after {
            content: '';
            position: absolute;
            left: 4px; top: 1px;
            width: 8px; height: 12px;
            border: 2px solid white;
            border-top: none; border-left: none;
            transform: rotate(45deg);
        }

        .pc-module-name {
            flex: 1;
            font-size: 15px;
            font-weight: 600;
            color: #1a1a1a;
        }
        .pc-module-item.pc-selected .pc-module-name { color: var(--pc-green-dark); }
        .pc-price-tag {
            background: var(--pc-green-light);
            border: 1px solid var(--pc-border);
            color: var(--pc-green-dark);
            font-weight: 700;
            font-size: 13px;
            padding: 3px 10px;
            border-radius: 20px;
        }
        .pc-module-item.pc-selected .pc-price-tag {
            background: var(--pc-green);
            border-color: var(--pc-green);
            color: white;
        }

        /* ── Duration wrap (hidden by default, shown for hospital) ── */
        .pc-duration-wrap { display: none; }
        .height_0 { display: none !important; }

        /* ── Bed tier ── */
        .pc-bed-tier-wrap {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px dashed var(--pc-border);
        }
        .pc-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: var(--pc-text-muted);
            margin-bottom: 10px;
        }
        .pc-bed-tier-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 10px;
        }
        .pc-bed-tier-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 10px;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            background: white;
        }
        .pc-bed-tier-card:hover { border-color: var(--pc-green); background: var(--pc-green-light); }
        .pc-bed-tier-card.pc-selected {
            border-color: var(--pc-green);
            background: var(--pc-green);
            color: white;
            box-shadow: 0 4px 12px rgba(129,196,8,.3);
        }
        .pc-bed-tier-name  { font-weight: 700; font-size: 13px; margin-bottom: 3px; }
        .pc-bed-tier-range { font-size: 11px; color: #999; margin-bottom: 6px; }
        .pc-bed-tier-card.pc-selected .pc-bed-tier-range { color: rgba(255,255,255,.8); }
        .pc-bed-tier-price { font-size: 12px; font-weight: 700; color: var(--pc-green-dark); }
        .pc-bed-tier-card.pc-selected .pc-bed-tier-price { color: rgba(255,255,255,.9); }
        .pc-bed-tier-contact { font-size: 12px; font-weight: 700; color: #d84315; }
        .pc-bed-tier-card.pc-selected .pc-bed-tier-contact { color: #ffe082; }

        .pc-tier-selected-bar {
            margin-top: 12px;
            padding: 10px 16px;
            background: linear-gradient(90deg, var(--pc-green-dark), var(--pc-green));
            color: white;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            display: none;
            box-shadow: 0 2px 8px rgba(129,196,8,.3);
        }

        /* ── Duration cards (inside module, hidden for hospital) ── */
        .pc-duration-card {
            padding: 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: all .25s;
            text-align: center;
        }
        .pc-duration-card:hover { border-color: var(--pc-green); }
        .pc-duration-card.pc-selected {
            border-color: var(--pc-green);
            background: var(--pc-green);
            color: white;
        }
        .pc-duration-title { font-weight: 600; font-size: 14px; margin-bottom: 4px; }
        .pc-duration-cost  { font-size: 13px; }
        .pc-duration-save  { font-size: 11px; margin-top: 4px; color: #28a745; }
        .pc-duration-card.pc-selected .pc-duration-save { color: #d4f4dd; }

        /* ── Summary panel ── */
        .pc-summary-wrap { position: sticky; top: 20px; }
        .pc-summary {
            background: linear-gradient(160deg, #1a3300 0%, #2d5a00 100%);
            color: white;
            padding: 22px 20px;
            border-radius: 14px;
            box-shadow: 0 6px 24px rgba(45,90,0,.3);
            margin-bottom: 16px;
        }
        .pc-summary-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: rgba(255,255,255,.6);
            margin-bottom: 18px;
        }
        .pc-summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-size: 14px;
            color: rgba(255,255,255,.85);
        }
        .pc-summary-row.pc-total {
            border-top: 1px solid rgba(255,255,255,.2);
            padding-top: 14px;
            margin-top: 6px;
            font-size: 22px;
            font-weight: 800;
            color: var(--pc-green);
            letter-spacing: -.3px;
        }
        .pc-summary-note {
            font-size: 11px;
            color: rgba(255,255,255,.5);
            margin-top: 14px;
            line-height: 1.5;
        }
        .pc-summary-note a { color: var(--pc-green); }
        .pc-breakdown { margin-top: 14px; }
        .pc-breakdown-item {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 8px;
            padding: 9px 12px;
            font-size: 12px;
            color: rgba(255,255,255,.85);
            margin: 6px 0;
            line-height: 1.5;
        }
        .pc-breakdown-item.pc-tier-item {
            border-color: var(--pc-green);
            background: rgba(129,196,8,.12);
        }

        /* ── Sidebar free card ── */
        .sidebar-card {
            background: white;
            border: 1px solid var(--pc-border);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .sidebar-card h3 { font-size: 15px; font-weight: 700; color: #1a1a1a; margin: 0 0 12px; }
        .sidebar-card ul { list-style: none; padding: 0; margin: 0 0 16px; }
        .sidebar-card li {
            font-size: 13px; color: #555; margin: 7px 0;
            padding-left: 20px; position: relative;
        }
        .sidebar-card li::before {
            content: '✓'; position: absolute; left: 0;
            color: var(--pc-green); font-weight: 700;
        }
        .sidebar-card a {
            display: block; text-align: center; width: 100%;
            text-decoration: none;
            background: var(--pc-green);
            color: white; border: none;
            padding: 11px; border-radius: 8px;
            font-size: 14px; font-weight: 600;
            transition: background .2s;
        }
        .sidebar-card a:hover { background: var(--pc-green-dark); }
    </style>
</head>

<body>
    @include('mc-vendor.partials.nav')

    <div class="pc-page-hero">
        <h1>Module Price Calculator</h1>
        <p>Select modules and plan duration to estimate your monthly or yearly cost</p>
    </div>

    <div class="pc-container">
        <div class="pc-panel pc-active" id="calculator">
            @php $plan_durations = _planDurations(); @endphp

            {{-- Global Duration Strip --}}
            <div class="pc-duration-strip">
                <div class="pc-strip-label">Select Plan Duration</div>
                <div class="pc-duration-grid">
                    @foreach($plan_durations as $i => $dur)
                    <div class="pc-global-duration-card {{ $i == 0 ? 'pc-selected' : '' }}" data-months="{{ $dur->months }}">
                        {{ $dur->label }}
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="pc-layout">
                {{-- Left: modules --}}
                <div>
                    <div class="pc-section-title">Select Modules</div>

                    @if (isset($sub_modules) && count($sub_modules) > 0)
                        @foreach ($sub_modules as $module)
                            @php $isHospital = $bedTiers->count() && stripos($module->name, 'hospital') !== false; @endphp
                            <div class="pc-module-item" data-module-id="{{ $module->id }}" data-is-hospital="{{ $isHospital ? 1 : 0 }}">
                                <div class="pc-module-top">
                                    <input type="checkbox" class="pc-checkbox pc-module-check" data-module-id="{{ $module->id }}">
                                    <div class="pc-module-name">{{ $module->name }}</div>
                                    <div class="pc-price-tag">₹{{ number_format($module->price_per_month) }}/mo</div>
                                </div>

                                {{-- Hidden data cards for JS pricing (not visible) --}}
                                <div class="pc-duration-wrap" data-module-id="{{ $module->id }}">
                                    <div class="pc-duration-grid height_0" @if($isHospital) style="display:none" @endif>
                                        @foreach ($plan_durations as $duration)
                                            @php
                                                $dur_discount    = _moduleDiscount($module->id, $duration->id);
                                                $basePrice       = $module->price_per_month * $duration->months;
                                                $discountAmount  = ($basePrice * $dur_discount) / 100;
                                                $finalPrice      = $basePrice - $discountAmount;
                                            @endphp
                                            <div class="pc-duration-card"
                                                data-module-id="{{ $module->id }}"
                                                data-price="{{ $finalPrice }}"
                                                data-months="{{ $duration->months }}"
                                                data-base-price="{{ $basePrice }}"
                                                data-discount="{{ $dur_discount }}"
                                                data-discount-amount="{{ $discountAmount }}"
                                                data-final-price="{{ $finalPrice }}">
                                                <div class="pc-duration-title">{{ $duration->label }}</div>
                                                <div class="pc-duration-cost">₹{{ number_format($finalPrice, 2) }}</div>
                                                <div class="pc-duration-save">
                                                    @if ($dur_discount > 0) Save {{ $dur_discount }}% @else No discount @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    @if($isHospital)
                                    <div class="pc-bed-tier-wrap">
                                        <label class="pc-label">Select Hospital Bed Tier</label>
                                        <div class="pc-bed-tier-grid">
                                            @foreach($bedTiers as $tier)
                                            <div class="pc-bed-tier-card"
                                                data-tier-id="{{ $tier->id }}"
                                                data-tier-name="{{ $tier->tier_name }}"
                                                data-bed-range="{{ $tier->bed_range }}"
                                                data-monthly="{{ $tier->price_monthly }}"
                                                data-yearly="{{ $tier->price_yearly }}"
                                                data-is-custom="{{ $tier->is_custom ? 1 : 0 }}"
                                                onclick="selectBedTier(this, {{ $module->id }})">
                                                <div class="pc-bed-tier-name">{{ $tier->tier_name }}</div>
                                                <div class="pc-bed-tier-range">{{ $tier->bed_range }}</div>
                                                @if($tier->is_custom)
                                                    <div class="pc-bed-tier-contact">Contact Us</div>
                                                @else
                                                    <div class="pc-bed-tier-price">₹{{ number_format($tier->price_monthly) }}/month</div>
                                                @endif
                                            </div>
                                            @endforeach
                                        </div>
                                        <div class="pc-tier-selected-bar" id="tierBar_{{ $module->id }}"></div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p style="color:#888;text-align:center;padding:40px 0;">No modules available yet.</p>
                    @endif
                </div>

                {{-- Right: summary + free card --}}
                <div class="pc-summary-wrap">
                    <div class="pc-summary">
                        <div class="pc-summary-title">Price Summary</div>
                        <div class="pc-summary-row">
                            <span>Subtotal</span>
                            <span id="pcSubtotal">₹0.00</span>
                        </div>
                        <div class="pc-summary-row">
                            <span>Discount</span>
                            <span id="pcDiscount" style="color:#a8e063;">−₹0.00</span>
                        </div>
                        <div class="pc-summary-row pc-total">
                            <span>Total</span>
                            <span id="pcTotal">₹0.00</span>
                        </div>
                        <div class="pc-summary-note">
                            Supports up to 10 users. Need more?
                            <a href="{{ route('contact') }}">Contact us</a> for a custom plan.
                        </div>
                        <div class="pc-breakdown" id="pcBreakdown"></div>
                    </div>

                    <div class="sidebar-card">
                        <h3>Register FREE on MC Vendor Hub</h3>
                        <ul>
                            <li>Free Billing – up to 1,000 bills</li>
                            <li>Free Business Webpage</li>
                        </ul>
                        <a href="https://mychitti.net/list-your-business">Register Now</a>
                    </div>
                </div>
            </div>

        </div>
    </div>


    {{-- footer section  --}}
    @include('mc-vendor.partials.footer')

    <script src="{{ asset('assets/admin') }}/js/vendor.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
    <script src="{{ asset('assets/front/lib/owlcarousel/owl.carousel.min.js') }}"></script>

    <!-- JS Front -->
    <script src="{{ asset('assets/admin') }}/js/theme.min.js"></script>
    <script src="{{ asset('assets/admin') }}/js/toastr.js"></script>
    <script>
        // Per-module bed tier selections, keyed by moduleId
        let selectedBedTiers = {};
        // Exposed so selectBedTier (called from inline onclick) can access it
        let _globalMonths = 1;
        let _recalculateAll;

        function selectBedTier(el, moduleId) {
            // Deselect only cards within this module
            const moduleItem = document.querySelector(`.pc-module-item[data-module-id="${moduleId}"]`);
            moduleItem.querySelectorAll('.pc-bed-tier-card').forEach(c => c.classList.remove('pc-selected'));
            el.classList.add('pc-selected');

            const isCustom = el.dataset.isCustom === '1';
            const tierBar = document.getElementById('tierBar_' + moduleId);

            if (isCustom) {
                selectedBedTiers[moduleId] = {
                    name: el.dataset.tierName,
                    bedRange: el.dataset.bedRange,
                    monthly: 0, yearly: 0, isCustom: true
                };
                if (tierBar) {
                    tierBar.style.display = 'block';
                    tierBar.innerHTML = '🏥 ' + el.dataset.tierName + ' — <span style="color:#ffe082;">Contact us for pricing</span>';
                }
            } else {
                const monthly = parseFloat(el.dataset.monthly) || 0;
                const yearly  = parseFloat(el.dataset.yearly)  || 0;
                selectedBedTiers[moduleId] = {
                    name: el.dataset.tierName,
                    bedRange: el.dataset.bedRange,
                    monthly, yearly, isCustom: false
                };
                if (tierBar) {
                    const price = _globalMonths === 12 ? yearly : monthly * _globalMonths;
                    tierBar.style.display = 'block';
                    tierBar.textContent = '🏥 ' + el.dataset.tierName
                        + ' (' + el.dataset.bedRange + ') — ₹'
                        + price.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                }
            }

            if (typeof _recalculateAll === 'function') _recalculateAll();
        }

        $(document).ready(function() {

            let selections = {};
            let globalMonths = 1;
            _globalMonths = globalMonths;

            // GLOBAL DURATION CLICK
            $('.pc-global-duration-card').on('click', function() {
                $('.pc-global-duration-card').removeClass('pc-selected');
                $(this).addClass('pc-selected');

                globalMonths = parseInt($(this).data('months'));
                _globalMonths = globalMonths;

                // Highlight matching duration card for all modules
                $('.pc-duration-card').removeClass('pc-selected');
                $(`.pc-duration-card[data-months="${globalMonths}"]`).addClass('pc-selected');

                // Update tier bars to reflect new duration price
                $.each(selectedBedTiers, function(moduleId, tier) {
                    const tierBar = document.getElementById('tierBar_' + moduleId);
                    if (!tierBar) return;
                    if (tier.isCustom) {
                        tierBar.innerHTML = '🏥 ' + tier.name + ' — <span style="color:#ffe082;">Contact us for pricing</span>';
                    } else {
                        const price = globalMonths === 12 ? tier.yearly : tier.monthly * globalMonths;
                        tierBar.textContent = '🏥 ' + tier.name
                            + ' (' + tier.bedRange + ') — ₹'
                            + price.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                    }
                });

                recalculateAll();
            });

            // MODULE CHECKBOX
            $('.pc-module-check').on('change', function() {
                const moduleId  = $(this).data('module-id');
                const moduleBox = $(`.pc-module-item[data-module-id="${moduleId}"]`);
                const durationWrap = $(`.pc-duration-wrap[data-module-id="${moduleId}"]`);
                const moduleName = moduleBox.find('.pc-module-name').text();
                const isHospital = moduleBox.data('is-hospital') == 1;

                if ($(this).is(':checked')) {
                    moduleBox.addClass('pc-selected');
                    if (isHospital) {
                        durationWrap.show();
                    }
                    selections[moduleId] = {
                        name: moduleName,
                        isHospital,
                        cards: $(`.pc-duration-card[data-module-id="${moduleId}"]`)
                    };
                } else {
                    moduleBox.removeClass('pc-selected');
                    durationWrap.hide();

                    // Clear bed tier selection for this module
                    if (isHospital) {
                        moduleBox.find('.pc-bed-tier-card').removeClass('pc-selected');
                        const tierBar = document.getElementById('tierBar_' + moduleId);
                        if (tierBar) tierBar.style.display = 'none';
                        delete selectedBedTiers[moduleId];
                    }

                    delete selections[moduleId];
                }

                recalculateAll();
            });

            // TOTAL CALCULATION
            function recalculateAll() {
                let totalBase     = 0;
                let totalDiscount = 0;
                let breakdownHTML = '';
                let hasCustomTier = false;

                $.each(selections, function(moduleId, data) {
                    const activeCard    = data.cards.filter(`[data-months="${globalMonths}"]`);
                    const base          = parseFloat(activeCard.data('base-price'))      || 0;
                    const discountAmount= parseFloat(activeCard.data('discount-amount')) || 0;
                    const final         = parseFloat(activeCard.data('price'))           || 0;

                    totalBase     += base;
                    totalDiscount += discountAmount;

                    breakdownHTML += `<div class="pc-breakdown-item">
                        ${data.name} &nbsp;·&nbsp; ${globalMonths} month${globalMonths > 1 ? 's' : ''} &nbsp;—&nbsp;
                        ₹${final.toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                    </div>`;

                    // Add bed tier for hospital modules
                    if (data.isHospital && selectedBedTiers[moduleId]) {
                        const tier = selectedBedTiers[moduleId];
                        if (tier.isCustom) {
                            hasCustomTier = true;
                            breakdownHTML += `<div class="pc-breakdown-item pc-tier-item">
                                🏥 Bed Tier: ${tier.name} — <span style="color:#a8e063;">Contact us for pricing</span>
                            </div>`;
                        } else {
                            const bedPrice = globalMonths === 12 ? tier.yearly : tier.monthly * globalMonths;
                            totalBase += bedPrice;
                            breakdownHTML += `<div class="pc-breakdown-item pc-tier-item">
                                🏥 Bed Tier: ${tier.name} (${tier.bedRange}) &nbsp;—&nbsp;
                                ₹${bedPrice.toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                            </div>`;
                        }
                    }
                });

                const total = totalBase - totalDiscount;

                $('#pcSubtotal').text('₹' + totalBase.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#pcDiscount').text('−₹' + totalDiscount.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#pcTotal').text(hasCustomTier
                    ? 'Contact Us'
                    : '₹' + total.toLocaleString('en-IN', { minimumFractionDigits: 2 }));

                $('#pcBreakdown').html(breakdownHTML
                    ? `<div style="margin-top:15px;">${breakdownHTML}</div>`
                    : '');
            }

            _recalculateAll = recalculateAll;
        });
    </script>


</body>

</html>
