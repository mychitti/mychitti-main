@extends('mc-vendor.theme.layout')

@section('title', 'Price Calculator — Build Your Plan | MC Vendor Hub')
@section('meta_description', 'Work out exactly what MC Vendor Hub costs for your business. Pick the modules you need, choose monthly, quarterly or yearly, and see the total with GST before you commit.')

@section('styles')
    <style>
        /* Calculator-specific styling. Every pc-* class and id below is a hook the pricing
           script depends on — restyled to the site theme, but never renamed. */
        .pc-heading {
            font-family: 'Sora', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 14px;
        }

        .pc-label {
            display: block;
            margin-bottom: 8px;
            color: var(--ink-soft);
            font-weight: 600;
            font-size: 13px;
        }

        .pc-duration-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 8px;
        }

        .pc-global-duration {
            margin-bottom: 34px;
        }

        .pc-global-duration-card {
            padding: 18px 12px;
            text-align: center;
            background: var(--white);
            border: 1.5px solid var(--line);
            border-radius: 12px;
            font-weight: 700;
            font-size: 14.5px;
            color: var(--ink-soft);
            cursor: pointer;
            transition: border-color .2s, color .2s, box-shadow .2s;
        }

        .pc-global-duration-card:hover {
            border-color: var(--blue);
            color: var(--blue);
        }

        .pc-global-duration-card.pc-selected {
            background: var(--blue);
            border-color: var(--blue);
            color: var(--white);
            box-shadow: 0 10px 22px rgba(19, 32, 56, 0.14);
        }

        .pc-module-item {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 12px;
            transition: border-color .2s, box-shadow .2s;
        }

        .pc-module-item:hover {
            border-color: var(--blue);
        }

        .pc-module-item.pc-selected {
            border-color: var(--blue);
            box-shadow: 0 10px 24px rgba(19, 32, 56, 0.08);
        }

        .pc-module-top {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .pc-checkbox {
            width: 18px;
            height: 18px;
            accent-color: var(--blue);
            cursor: pointer;
            flex-shrink: 0;
        }

        .pc-module-name {
            flex: 1;
            font-weight: 700;
            font-size: 14.5px;
            color: var(--ink);
        }

        .pc-price-amount {
            font-family: 'IBM Plex Mono', monospace;
            color: var(--blue);
            font-weight: 700;
            font-size: 13.5px;
            white-space: nowrap;
        }

        /* Hidden by default; the script reveals it only for tiered modules. */
        .pc-duration-wrap {
            display: none;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid var(--line);
        }

        /* The per-module duration cards carry the pricing data the script reads,
           so they stay in the DOM but collapsed. */
        .height_0 {
            height: 0 !important;
            overflow: hidden;
        }

        .pc-duration-card {
            padding: 10px;
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 8px;
            text-align: center;
            transition: border-color .2s;
        }

        .pc-duration-card:hover {
            border-color: var(--blue);
        }

        .pc-duration-card.pc-selected {
            border-color: var(--blue);
            background: var(--blue-pale);
        }

        .pc-bed-tier-wrap {
            margin-top: 6px;
        }

        .pc-bed-tier-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 8px;
        }

        .pc-bed-tier-card {
            padding: 14px 12px;
            background: var(--white);
            border: 1.5px solid var(--line);
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, box-shadow .2s;
        }

        .pc-bed-tier-card:hover {
            border-color: var(--blue);
        }

        .pc-bed-tier-card.pc-selected {
            border-color: var(--blue);
            background: var(--blue-pale);
            box-shadow: 0 8px 18px rgba(19, 32, 56, 0.08);
        }

        .pc-bed-tier-name {
            font-weight: 700;
            font-size: 13.5px;
            color: var(--ink);
        }

        .pc-bed-tier-range {
            font-size: 12px;
            color: var(--ink-faint);
            margin-top: 2px;
        }

        .pc-bed-tier-price {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 13px;
            font-weight: 700;
            color: var(--blue);
            margin-top: 6px;
        }

        .pc-bed-tier-contact {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--orange-dark);
            margin-top: 6px;
        }

        .pc-tier-selected-bar {
            display: none;
            margin-top: 12px;
            padding: 10px 14px;
            background: var(--ink);
            color: var(--white);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .pc-summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 0;
            font-size: 13.5px;
            color: var(--ink-soft);
        }

        .pc-summary-row span:last-child {
            font-family: 'IBM Plex Mono', monospace;
            font-weight: 600;
            color: var(--ink);
        }

        .pc-summary-row.pc-total {
            border-top: 1px solid var(--line);
            margin-top: 8px;
            padding-top: 14px;
            font-family: 'Sora', sans-serif;
            font-size: 16px;
            font-weight: 800;
            color: var(--ink);
        }

        .pc-summary-row.pc-total span:last-child {
            font-size: 20px;
            font-weight: 800;
            color: var(--blue);
        }

        .pc-breakdown {
            margin-top: 4px;
        }

        .pc-breakdown-item {
            font-size: 12.5px;
            color: var(--ink-soft);
            padding: 7px 0;
            border-top: 1px dashed var(--line);
        }

        .pc-tier-item {
            font-weight: 600;
        }

        .pc-note {
            margin-top: 16px;
            padding: 12px 14px;
            background: var(--bg-soft);
            border-radius: 8px;
            font-size: 12.5px;
            color: var(--ink-soft);
            line-height: 1.5;
        }

        .pc-note a {
            color: var(--blue);
            font-weight: 700;
        }

        .pc-empty {
            text-align: center;
            padding: 48px 20px;
            color: var(--ink-faint);
        }
    </style>
@endsection

@section('content')

    <div class="wrap breadcrumb"><a href="{{ mcv('vendor.mc-vendor.theme.home') }}">Home</a><span>/</span>Price Calculator</div>

    <section class="page-hero" style="padding-bottom:40px;">
        <div class="wrap">
            <span class="eyebrow"><span class="dot"></span> Transparent Pricing</span>
            <h1>Build your plan and see <span>the real total</span>.</h1>
            <p class="lede">Tick the modules you need, pick how long you want to pay for, and the total updates as you go — GST included, nothing hidden until checkout.</p>
        </div>
    </section>

    <section style="padding-top:0;">
        <div class="wrap">
            @php($plan_durations = _planDurations())

            <div class="pc-global-duration">
                <label class="pc-label"><b>Select Plan Duration</b></label>
                <div class="pc-duration-grid">
                    @foreach ($plan_durations as $i => $dur)
                        <div class="pc-global-duration-card {{ $i == 0 ? 'pc-selected' : '' }}" data-months="{{ $dur->months }}">{{ $dur->label }}</div>
                    @endforeach
                </div>
            </div>

            <div class="content-split">
                <div>
                    <h3 class="pc-heading">Select Modules &amp; Duration</h3>

                    @if (isset($sub_modules) && count($sub_modules) > 0)
                        @foreach ($sub_modules as $module)
                            @php($isHospital = $bedTiers->count() && stripos($module->name, 'hospital') !== false)
                            @php($isSchool = isset($studentTiers) && $studentTiers->count() && ((($module->Key ?? '') === 'school_manage') || stripos($module->name, 'school') !== false))
                            @php($isTiered = $isHospital || $isSchool)
                            @php($tierList = $isSchool ? $studentTiers : $bedTiers)
                            @php($tierLabel = $isSchool ? 'Select School Plan:' : 'Select Hospital Tier:')
                            <div class="pc-module-item" data-module-id="{{ $module->id }}" data-is-hospital="{{ $isTiered ? 1 : 0 }}">
                                <div class="pc-module-top">
                                    <input type="checkbox" class="pc-checkbox pc-module-check"
                                        data-module-id="{{ $module->id }}">
                                    <div class="pc-module-name">{{ $module->name }}</div>
                                    <div class="pc-price-amount">₹{{ number_format($module->price_per_month) }}/month</div>
                                </div>
                                <div class="pc-duration-wrap" data-module-id="{{ $module->id }}">
                                    <div class="pc-duration-grid height_0" @if ($isTiered) style="display:none" @endif>
                                        @foreach ($plan_durations as $duration)
                                            @php($dur_discount = _moduleDiscount($module->id, $duration->id))
                                            @php($basePrice = $module->price_per_month * $duration->months)
                                            @php($discountAmount = ($basePrice * $dur_discount) / 100)
                                            @php($finalPrice = $basePrice - $discountAmount)
                                            <div class="pc-duration-card" data-module-id="{{ $module->id }}"
                                                data-price="{{ $finalPrice }}"
                                                data-months="{{ $duration->months }}"
                                                data-base-price="{{ $basePrice }}"
                                                data-discount="{{ $dur_discount }}"
                                                data-discount-amount="{{ $discountAmount }}"
                                                data-final-price="{{ $finalPrice }}">
                                                <div class="pc-duration-title">{{ $duration->label }}</div>
                                                <div class="pc-duration-cost">₹{{ number_format($finalPrice, 2) }}</div>
                                                <div class="pc-duration-save">
                                                    @if ($dur_discount > 0) Save {{ $dur_discount }}%
                                                    @else No discount @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    @if ($isTiered)
                                        <div class="pc-bed-tier-wrap">
                                            <label class="pc-label"><b>{{ $tierLabel }}</b></label>
                                            <div class="pc-bed-tier-grid">
                                                @foreach ($tierList as $tier)
                                                    @php($tierRange = $isSchool ? $tier->student_range : $tier->bed_range)
                                                    <div class="pc-bed-tier-card"
                                                        data-tier-id="{{ $tier->id }}"
                                                        data-tier-name="{{ $tier->tier_name }}"
                                                        data-bed-range="{{ $tierRange }}"
                                                        data-monthly="{{ $tier->price_monthly }}"
                                                        data-yearly="{{ $tier->price_yearly }}"
                                                        data-is-custom="{{ $tier->is_custom ? 1 : 0 }}"
                                                        onclick="selectBedTier(this, {{ $module->id }})">
                                                        <div class="pc-bed-tier-name">{{ $tier->tier_name }}</div>
                                                        <div class="pc-bed-tier-range">{{ $tierRange }}</div>
                                                        @if ($tier->is_custom)
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
                        <p class="pc-empty">No modules available yet. Please try again later.</p>
                    @endif
                </div>

                <div>
                    <div class="sticky-cta">
                        <h4>Your plan</h4>
                        <div class="pc-summary-row">
                            <span>Subtotal (Base):</span>
                            <span id="pcSubtotal">₹0.00</span>
                        </div>
                        <div class="pc-summary-row">
                            <span>Discount:</span>
                            <span id="pcDiscount">₹0.00</span>
                        </div>
                        <div class="pc-summary-row">
                            <span>GST (18%):</span>
                            <span id="pcTax">₹0.00</span>
                        </div>
                        <div class="pc-summary-row pc-total">
                            <span>Total:</span>
                            <span id="pcTotal">₹0.00</span>
                        </div>
                        <div class="pc-breakdown" id="pcBreakdown"></div>
                        <div class="pc-note">
                            <b>Note:</b> This plan supports only 10 users. Need more?
                            <a href="{{ mcv('vendor.mc-vendor.contact') }}">Contact us</a> for an upgrade.
                        </div>
                        <a href="{{ $mc_signup_url }}" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:16px;">List Your Business — Free</a>
                        <div class="setup-note">Free billing up to 1000 bills · Free business webpage</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="wrap" style="text-align:center;">
            <h2>Not sure which modules you need?</h2>
            <p>Tell us how your business runs and we'll put together the combination that fits.</p>
            <div class="hero-ctas" style="justify-content:center;">
                <a href="{{ mcv('vendor.mc-vendor.contact') }}" class="btn btn-primary">Talk to Sales</a>
                <a href="{{ mcv('vendor.mc-vendor.theme.home') }}" class="btn btn-ghost">Back to Home</a>
            </div>
        </div>
    </section>

@endsection

@section('scripts')
    <script src="{{ asset('assets/admin/vendor/jquery/jquery.min.js') }}"></script>
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
                    tierBar.innerHTML = ''+el.dataset.tierName + ' — <span style="color:#ffe082;">Contact us for pricing</span>';
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
                    tierBar.textContent = ''+el.dataset.tierName
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
                        tierBar.innerHTML = ''+tier.name + ' — <span style="color:#ffe082;">Contact us for pricing</span>';
                    } else {
                        const price = globalMonths === 12 ? tier.yearly : tier.monthly * globalMonths;
                        tierBar.textContent = ''+tier.name
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
                    const activeCard     = data.cards.filter(`[data-months="${globalMonths}"]`);
                    const base           = parseFloat(activeCard.data('base-price'))      || 0;
                    const discountAmount = parseFloat(activeCard.data('discount-amount')) || 0;
                    const final          = parseFloat(activeCard.data('price'))           || 0;
                    const tier           = data.isHospital ? selectedBedTiers[moduleId] : null;

                    if (data.isHospital && tier) {
                        // Hospital: use only bed tier price, skip module base price
                        if (tier.isCustom) {
                            hasCustomTier = true;
                            breakdownHTML += `<div class="pc-breakdown-item pc-tier-item">
                                ${data.name}: ${tier.name} (${tier.bedRange}) — <span style="color:#e65100;">Contact us for pricing</span>
                            </div>`;
                        } else {
                            const bedPrice = globalMonths === 12 ? tier.yearly : tier.monthly * globalMonths;
                            totalBase += bedPrice;
                            breakdownHTML += `<div class="pc-breakdown-item pc-tier-item">
                                ${data.name}: ${tier.name} (${tier.bedRange}) —
                                ₹${bedPrice.toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                            </div>`;
                        }
                    } else if (!data.isHospital) {
                        // Regular module
                        totalBase     += base;
                        totalDiscount += discountAmount;
                        breakdownHTML += `<div class="pc-breakdown-item">
                            ${data.name} (${globalMonths} months) —
                            ₹${final.toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                        </div>`;
                    } else {
                        // Hospital with no tier selected yet — show base price
                        totalBase     += base;
                        totalDiscount += discountAmount;
                        breakdownHTML += `<div class="pc-breakdown-item">
                            ${data.name} (${globalMonths} months) —
                            ₹${final.toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                        </div>`;
                    }
                });

                const afterDiscount = totalBase - totalDiscount;
                const gst   = hasCustomTier ? 0 : afterDiscount * 0.18;
                const total = afterDiscount + gst;

                $('#pcSubtotal').text('₹' + totalBase.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#pcDiscount').text('₹' + totalDiscount.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#pcTax').text(hasCustomTier
                    ? '—'
                    : '₹' + gst.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
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
@endsection
