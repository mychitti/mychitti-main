<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module Price Calculator</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        } 

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            padding: 10px;
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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .pc-panel.pc-active {
            display: block;
            animation: pcFadeIn 0.3s;
        }

        @keyframes pcFadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
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
            display: block;
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

        .pc-duration-card {
            padding: 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .pc-duration-card:hover {
            border-color: #81c408;
        }

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
            background: #81c408;
            color: white;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
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
            padding: 3px 0;
        }
    </style>
</head>
<body>
    <div class="pc-container">
        <div class="pc-tabs">
            <button class="pc-tab-btn pc-active" data-tab="calculator">Price Calculator</button>
        </div>

        <!-- Calculator Panel -->
        <div class="pc-panel pc-active" id="calculator">
            <h2 class="pc-heading">Module Price Calculator</h2>
            
            <div class="pc-calc-section">
                <h3 style="color: #81c408; margin-bottom: 12px; font-size: 16px;">Select Modules & Duration</h3>
                
                @if(isset($sub_modules) && count($sub_modules) > 0)
                    @foreach($sub_modules as $module)
                        <div class="pc-module-item" data-module-id="{{ $module->id }}">
                            <div class="pc-module-top">
                                <input type="checkbox" class="pc-checkbox pc-module-check" data-module-id="{{ $module->id }}">
                                <div class="pc-module-name">{{ $module->name }}</div>
                                <div class="pc-price-amount">₹{{ number_format($module->price_per_month) }}/month</div>
                            </div>
                            <div class="pc-duration-wrap" data-module-id="{{ $module->id }}">
                                <label class="pc-label">Select Duration:</label>
                                <div class="pc-duration-grid">
                                    @php $plan_durations = _planDurations(); @endphp

                                    @foreach($plan_durations as $duration)
                                        @php
                                            $dur_discount = _moduleDiscount($module->id, $duration->id);
                                            $basePrice = $module->price_per_month * $duration->months;
                                            $discountAmount = ($basePrice * $dur_discount) / 100;
                                            $finalPrice = $basePrice - $discountAmount;
                                        @endphp
                                        <div class="pc-duration-card"
                                             data-module-id="{{ $module->id }}"
                                             data-months="{{ $duration->months }}"
                                             data-base-price="{{ $basePrice }}"
                                             data-discount="{{ $dur_discount }}"
                                             data-discount-amount="{{ $discountAmount }}"
                                             data-final-price="{{ $finalPrice }}">
                                            <div class="pc-duration-title">{{ $duration->label }}</div>
                                            <div class="pc-duration-cost">₹{{ number_format($finalPrice, 2) }}</div>
                                            <div class="pc-duration-save">
                                                @if($dur_discount > 0)
                                                    Save {{ $dur_discount }}%
                                                @else
                                                    No discount
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p style="color: #666; text-align: center; padding: 40px;">No modules available. Please add modules in the admin panel.</p>
                @endif

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
                    <div class="pc-breakdown" id="pcBreakdown"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            let selections = {};

            // Handle module checkbox
            $('.pc-module-check').on('change', function() {
                const moduleId = $(this).data('module-id');
                const isChecked = $(this).is(':checked');
                
                if (isChecked) {
                    $(`.pc-module-item[data-module-id="${moduleId}"]`).addClass('pc-selected');
                    $(`.pc-duration-wrap[data-module-id="${moduleId}"]`).addClass('pc-show');
                } else {
                    $(`.pc-module-item[data-module-id="${moduleId}"]`).removeClass('pc-selected');
                    $(`.pc-duration-wrap[data-module-id="${moduleId}"]`).removeClass('pc-show');
                    $(`.pc-duration-card[data-module-id="${moduleId}"]`).removeClass('pc-selected');
                    delete selections[moduleId];
                    calculateTotal();
                }
            });

            // Handle duration selection
            $('.pc-duration-card').on('click', function() {
                const moduleId = $(this).data('module-id');
                const months = $(this).data('months');
                const basePrice = parseFloat($(this).data('base-price'));
                const discount = parseFloat($(this).data('discount'));
                const discountAmount = parseFloat($(this).data('discount-amount'));
                const finalPrice = parseFloat($(this).data('final-price'));
                
                // Get module name
                const moduleName = $(`.pc-module-item[data-module-id="${moduleId}"] .pc-module-name`).text();
                
                // Remove selected class from all duration cards of this module
                $(`.pc-duration-card[data-module-id="${moduleId}"]`).removeClass('pc-selected');
                
                // Add selected class to clicked card
                $(this).addClass('pc-selected');
                
                // Store selection
                selections[moduleId] = {
                    name: moduleName,
                    months: months,
                    basePrice: basePrice,
                    discount: discount,
                    discountAmount: discountAmount,
                    finalPrice: finalPrice
                };
                
                calculateTotal();
            });

            // Calculate total
            function calculateTotal() {
                let totalBase = 0;
                let totalDiscount = 0;
                let breakdown = [];

                $.each(selections, function(moduleId, data) {
                    totalBase += data.basePrice;
                    totalDiscount += data.discountAmount;
                    
                    breakdown.push({
                        name: data.name,
                        months: data.months,
                        price: data.finalPrice,
                        discount: data.discount
                    });
                });

                const total = totalBase - totalDiscount;

                // Update display
                $('#pcSubtotal').text('₹' + totalBase.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#pcDiscount').text('-₹' + totalDiscount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#pcTotal').text('₹' + total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                // Show breakdown
                let breakdownHTML = '';
                if (breakdown.length > 0) {
                    breakdownHTML = '<div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.3);">';
                    $.each(breakdown, function(index, item) {
                        breakdownHTML += `<div class="pc-breakdown-item">${item.name} (${item.months} month${item.months > 1 ? 's' : ''}) - ₹${item.price.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>`;
                    });
                    breakdownHTML += '</div>';
                }
                $('#pcBreakdown').html(breakdownHTML);
            }

            // Tab switching (if you add more tabs later)
            $('.pc-tab-btn').on('click', function() {
                const tabName = $(this).data('tab');
                $('.pc-tab-btn').removeClass('pc-active');
                $(this).addClass('pc-active');
                $('.pc-panel').removeClass('pc-active');
                $('#' + tabName).addClass('pc-active');
            });
        });
    </script>
</body>
</html>