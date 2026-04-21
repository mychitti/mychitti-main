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
        .pc-global-duration-card {
            height: 100px;
            padding: 10px;
            background: #f3ffdf;
            margin: 10px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .pc-global-duration-card .pc-selected {
            {{-- background: #d0ff86ff; --}}
        }

        .sidebar-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
        }

        .sidebar-card h3 {
            font-size: 16px;
            margin: 0 0 12px 0;
            color: #333;
        }

        .sidebar-card ul {
            list-style: none;
            padding: 0;
            margin: 0 0 15px 0;
        }

        .sidebar-card li {
            font-size: 14px;
            color: #666;
            margin: 8px 0;
            padding-left: 18px;
            position: relative;
        }

        .sidebar-card li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: #81c408;
        }

        .sidebar-card a {
            display: block;
            text-align: center;
            width: 100%;
            text-decoration: none;
            background: #81c408;
            color: white;
            width: 100%;
            border: none;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }

        .sidebar-card a:hover {
            background: #81c408;
        }

        .pc-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .pc-tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 15px;
        }

        .pc-tab-btn {
            padding: 8px 20px;
            background: white;
            border: 1px solid #ddd;
            color: #333;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .pc-tab-btn.pc-active {
            background: #81c408;
            color: white;
            border-color: #81c408;
            font-weight: 600;
        }

        .pc-tab-btn:hover {
            background: #f0f0f0;
        }

        .pc-tab-btn.pc-active:hover {
            background: #6fa607;
        }

        .pc-panel {
            display: none;
            background: white;
            border-radius: 6px;
            padding: 15px;
        }

        .pc-panel.pc-active {
            display: block;
            animation: pcFadeIn 0.3s;
        }

        @keyframes pcFadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pc-heading {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .pc-calc-section {
            margin-top: 15px;
        }

        .pc-module-item {
            background: #fafafa;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }

        .pc-module-item.pc-selected {
            border-color: #81c408;
            background: #f8fcf0;
        }

        .pc-module-top {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .pc-checkbox {
            width: 18px;
            height: 18px;
            margin-right: 10px;
            cursor: pointer;
        }

        .pc-module-name {
            flex-grow: 1;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .pc-price-amount {
            color: #81c408;
            font-weight: 700;
            font-size: 14px;
        }

        .pc-duration-wrap {
            display: none;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }

        .pc-duration-wrap.pc-show {
            {{-- display: block; --}}
        }

        .pc-label {
            display: block;
            margin-bottom: 6px;
            color: #555;
            font-weight: 500;
            font-size: 13px;
        }

        .pc-duration-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 8px;
            margin-top: 8px;
        }

        .height_0 {

            height: 0 !important;
        }

        .pc-duration-card {
            padding: 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: all 0.3s;
            text-align: center;
        }

        .pc-duration-card:hover {
            border-color: #81c408;
        }

        .pc-global-duration-card.pc-selected,
        .pc-duration-card.pc-selected {
            border-color: #81c408;
            background: #81c408;
            color: white;
        }

        .pc-duration-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .pc-duration-cost {
            font-size: 13px;
        }

        .pc-duration-save {
            font-size: 11px;
            margin-top: 4px;
            color: #28a745;
        }

        .pc-duration-card.pc-selected .pc-duration-save {
            color: #d4f4dd;
        }

        .pc-summary {
            background: #edffcd;
            color: #090909;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            height: fit-content;
            margin-top: 46px;
        }

        .pc-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .pc-summary-row.pc-total {
            border-top: 1px solid rgba(255, 255, 255, 0.3);
            padding-top: 10px;
            margin-top: 10px;
            font-size: 18px;
            font-weight: 700;
        }

        .pc-breakdown {
            margin-top: 8px;
            font-size: 12px;
            opacity: 0.9;
        }

        .pc-breakdown-item {
            background: white;
            padding: 10px;
            border: 1px solid #81c408;
            border-radius: 7px;
            margin: 4px 0;
        }

        .pc-bed-tier-wrap {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e0e0e0;
        }
        .pc-bed-tier-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 8px;
            margin-top: 8px;
        }
        .pc-bed-tier-card {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #fff;
        }
        .pc-bed-tier-card:hover { border-color: #0d6efd; }
        .pc-bed-tier-card.pc-selected {
            border-color: #0d6efd;
            background: #0d6efd;
            color: white;
        }
        .pc-bed-tier-card.pc-selected .pc-bed-tier-range,
        .pc-bed-tier-card.pc-selected .pc-bed-tier-price { color: rgba(255,255,255,0.85); }
        .pc-bed-tier-name { font-weight: 600; font-size: 13px; margin-bottom: 3px; }
        .pc-bed-tier-range { font-size: 11px; color: #888; margin-bottom: 5px; }
        .pc-bed-tier-price { font-size: 12px; font-weight: 600; color: #0d6efd; }
        .pc-bed-tier-contact { font-size: 12px; color: #e65100; font-weight: 600; }
        .pc-tier-selected-bar {
            margin-top: 10px;
            padding: 8px 14px;
            background: #0d6efd;
            color: white;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            display: none;
        }
    </style>
</head>

<body>
    @include('mc-vendor.partials.nav')

    <div class="pc-container">

 
        <!-- Calculator Panel -->
        <div class="pc-panel pc-active" id="calculator">
            @php $plan_durations = _planDurations(); @endphp
            <h2 class="pc-heading">Module Price Calculator</h2>
            <div class="pc-global-duration mb-3">
                <label class="pc-label"><b>Select Plan Duration</b></label>
                <div class="pc-duration-grid">
                    @foreach($plan_durations as $i => $dur)
                    <div class="pc-global-duration-card {{ $i == 0 ? 'pc-selected' : '' }}" data-months="{{ $dur->months }}">{{ $dur->label }}</div>
                    @endforeach
                </div>
            </div>


            <div class="row">
                <div class="pc-calc-section col-md-8">
                    <h3 style="color: #81c408; margin-bottom: 12px; font-size: 16px;">Select Modules & Duration</h3>

                    @if (isset($sub_modules) && count($sub_modules) > 0)
                        @foreach ($sub_modules as $module)
                            @php $isHospital = $bedTiers->count() && stripos($module->name, 'hospital') !== false; @endphp
                            <div class="pc-module-item" data-module-id="{{ $module->id }}" data-is-hospital="{{ $isHospital ? 1 : 0 }}">
                                <div class="pc-module-top">
                                    <input type="checkbox" class="pc-checkbox pc-module-check"
                                        data-module-id="{{ $module->id }}">
                                    <div class="pc-module-name">{{ $module->name }}</div>
                                    <div class="pc-price-amount">₹{{ number_format($module->price_per_month) }}/month
                                    </div>
                                </div>
                                <div class="pc-duration-wrap"
                                    data-module-id="{{ $module->id }}">
                                    <div class="pc-duration-grid height_0">
                                        @foreach ($plan_durations as $duration)
                                            @php
                                                $dur_discount = _moduleDiscount($module->id, $duration->id);
                                                $basePrice = $module->price_per_month * $duration->months;
                                                $discountAmount = ($basePrice * $dur_discount) / 100;
                                                $finalPrice = $basePrice - $discountAmount;
                                            @endphp
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

                                    @if($isHospital)
                                    <div class="pc-bed-tier-wrap">
                                        <label class="pc-label"><b>Select Hospital Tier:</b></label>
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
                        <p style="color: #666; text-align: center; padding: 40px;">No modules available yet. Please try
                            again later.</p>
                    @endif

                </div>
                <div class=" col-md-4">
                    <div class="pc-summary">
                        <div class="pc-summary-row">
                            <span>Subtotal (Base):</span>
                            <span id="pcSubtotal">₹0.00</span>
                        </div>
                        <div class="pc-summary-row">
                            <span>Discount:</span>
                            <span id="pcDiscount">₹0.00</span>
                        </div>
                        <div class="pc-summary-row pc-total">
                            <span>Total:</span>
                            <span id="pcTotal">₹0.00</span>
                        </div>
                        <div style="font-size: 12px;" class="bg-white p-2">
                            <b>Note:</b> This plan supports only 10 users.
                            Need for more users? <a href="{{ route('contact') }}">Contact us</a> for an upgrade.
                        </div>
                        <div class="pc-breakdown" id="pcBreakdown"></div>
                    </div>
                    <div class="sidebar-card mt-4">
                        <h3>Register for FREE on MC VENDOR HUB</h3>
                        <ul>
                            <li>Free Billing – up to 1000 bills</li>
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
                        ${data.name} (${globalMonths} months) —
                        ₹${final.toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                    </div>`;

                    // Add bed tier for hospital modules
                    if (data.isHospital && selectedBedTiers[moduleId]) {
                        const tier = selectedBedTiers[moduleId];
                        if (tier.isCustom) {
                            hasCustomTier = true;
                            breakdownHTML += `<div class="pc-breakdown-item" style="border-color:#0d6efd;">
                                🏥 Hospital Bed Tier: ${tier.name} — <span style="color:#e65100;">Contact us for pricing</span>
                            </div>`;
                        } else {
                            const bedPrice = globalMonths === 12 ? tier.yearly : tier.monthly * globalMonths;
                            totalBase += bedPrice;
                            breakdownHTML += `<div class="pc-breakdown-item" style="border-color:#0d6efd;">
                                🏥 Hospital Bed Tier: ${tier.name} (${tier.bedRange}, ${globalMonths} months) —
                                ₹${bedPrice.toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                            </div>`;
                        }
                    }
                });

                const total = totalBase - totalDiscount;

                $('#pcSubtotal').text('₹' + totalBase.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#pcDiscount').text('₹' + totalDiscount.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
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
