  <script>
        $(document).ready(function() {

            let selections = {};
            let globalMonths = 1;
            @php $gst_settings_js = _planGstSettings(); @endphp
            const gstPercent = {{ $gst_settings_js['gst_percent'] ?? 0 }};
            const gstMode = '{{ $gst_settings_js['gst_mode'] ?? 'exclude' }}';

            $('.pc-global-duration-card').on('click', function() {
 
                $('.pc-global-duration-card').removeClass('pc-selected');
                $(this).addClass('pc-selected'); 

                globalMonths = parseInt($(this).data('months')); 

                $('.pc-duration-card').removeClass('pc-selected');
                $(`.pc-duration-card[data-months="${globalMonths}"]`).addClass('pc-selected');

                recalculateAll();
            });


            $('.pc-module-check').on('change', function() {

                const moduleId = $(this).data('module-id');
                const moduleBox = $(`.pc-module-item[data-module-id="${moduleId}"]`);
                const durationWrap = $(`.pc-duration-wrap[data-module-id="${moduleId}"]`);
                const moduleName = moduleBox.find('.pc-module-name').text();

                if ($(this).is(':checked')) {

                    durationWrap.addClass('pc-show');
                    moduleBox.addClass('pc-selected');

                    selections[moduleId] = {
                        name: moduleName,
                        cards: $(`.pc-duration-card[data-module-id="${moduleId}"]`)
                    };

                } else {

                    durationWrap.removeClass('pc-show');
                    moduleBox.removeClass('pc-selected');

                    delete selections[moduleId];
                }

                recalculateAll();
            });


            function recalculateAll() {

                let totalBase = 0;
                let totalDiscount = 0;
                let breakdownHTML = '';
                let selectedModules = [];

                $.each(selections, function(moduleId, data) {

                    const activeCard = data.cards.filter(`[data-months="${globalMonths}"]`);

                    const base = parseFloat(activeCard.data('base-price'));
                    const discountAmount = parseFloat(activeCard.data('discount-amount'));
                    const final = parseFloat(activeCard.data('final-price'));

                    totalBase += base;
                    totalDiscount += discountAmount;
                    if (globalMonths == 12) {
                        monthLabel = 'Annaully';
                    } else if (globalMonths == 3) {
                        monthLabel = 'Quarterly';
                    } else {
                        monthLabel = globalMonths + ' month';
                    }
                    breakdownHTML += `
                <div class="pc-breakdown-item">
                    ${data.name} (${monthLabel}) - 
                    ₹${final.toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                </div>`;

                    selectedModules.push({
                        module_id: moduleId,
                        months: globalMonths,
                        price: final 
                    });
                });

                let subtotal = totalBase - totalDiscount;
                let gstAmount = 0;
                let total = subtotal;

                if (gstPercent > 0) {
                    if (gstMode === 'exclude') {
                        gstAmount = (subtotal * gstPercent) / 100;
                        total = subtotal + gstAmount;
                    } else {
                        // GST inclusive: extract GST from the total
                        gstAmount = subtotal - (subtotal * 100) / (100 + gstPercent);
                        total = subtotal;
                    }
                }

                $('#pcSubtotal').text('₹' + totalBase.toLocaleString('en-IN', { 
                    minimumFractionDigits: 2
                }));
                $('#pcDiscount').text('₹' + totalDiscount.toLocaleString('en-IN', {
                    minimumFractionDigits: 2
                }));
                if (gstPercent > 0) {
                    $('#pcGst').text('₹' + gstAmount.toLocaleString('en-IN', {
                        minimumFractionDigits: 2
                    }));
                }
                $('#pcTotal').text('₹' + total.toLocaleString('en-IN', {
                    minimumFractionDigits: 2
                }));

                $('#pcBreakdown').html(breakdownHTML ? `<div style="margin-top:15px;">${breakdownHTML}</div>` : '');

                $('#selectedModulesInput').val(JSON.stringify(selectedModules));
                $('#grandTotalInput').val(total.toFixed(2));
                $('#gstAmountInput').val(gstAmount.toFixed(2));
            }

        });
    </script>