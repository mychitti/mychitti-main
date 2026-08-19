 <script>
        $(document).ready(function() {
            updateInfoDisplay();

            $(document).on('keyup change', "#secondary_qty, #primary_qty, #secondary_unit, #primary_unit",
            function() {
                updateInfoDisplay();
            });

            function updateInfoDisplay() {
                var secondary_qty = $("#secondary_qty").val();
                var primary_qty = $("#primary_qty").val();
                var secondary_unit = $("#secondary_unit option:selected").text();
                var primary_unit = $("#primary_unit option:selected").text();

                var conversionExample = $("#conversion_example");
                var exampleText = $("#example_text");

                if (primary_qty && primary_unit && secondary_qty && secondary_unit &&
                    primary_unit !== "Select Unit" && secondary_unit !== "Select Unit") {

                    //var totalUnits = parseFloat(primary_qty) * parseFloat(secondary_qty);
                    var totalUnits = parseFloat(primary_qty) / parseFloat(secondary_qty);
                    totalUnits = Math.round(totalUnits * 1000) / 1000; // keep decimals (e.g. 22.5)
                   // exampleText.text(totalUnits + ' ' + primary_unit.toLowerCase());
                    exampleText.text(totalUnits + ' ' + primary_unit.toLowerCase()+ ' per ' + secondary_unit.toLowerCase());
                    conversionExample.addClass('show');

                } else {
                    conversionExample.removeClass('show');
                    exampleText.text('Configure units to see conversion details');
                }
            }

            $('input, select').on('focus', function() {
                $(this).closest('.input-field, .select-field').addClass('focused');
            });

            $('input, select').on('blur', function() {
                $(this).closest('.input-field, .select-field').removeClass('focused');
            });

            $('.add-alternate-btn').on('click', function(e) {
                e.preventDefault();
                $(".secondary_unit_elem").show()
                $('.stock-section').removeClass('uom-plain')
                $('.add-alternate-btn').hide()
                $('.remove-alternate-btn').show()
            }); 
            $('.remove-alternate-btn').on('click', function(e) {
                $("#primary_qty").val(1)
               $("#conversion_example").removeClass('show')
                $(".secondary_unit_elem").hide()
                $('.stock-section').addClass('uom-plain')
                $('.add-alternate-btn').show()
                $('.remove-alternate-btn').hide()
                $('#choice_attributes').val(null).trigger('change');
            });
        });
    </script>