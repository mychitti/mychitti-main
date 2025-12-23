<script>
    $(document).on('change', 'input[name="payment_stts"]', function() {
        var val = $(this).val();
        if (val == 'Paid') {
            $(".payment_date_inp").hide()
            $(".reminder_date_inp").hide()
            $(".payment_mode_grp").show()
            if ($("input[name='payment_mode'][value='Cash and Online']").prop('checked')) {
                $(".partial_payment").show();
            }
        } else {
            $(".payment_date_inp").show()
            $(".reminder_date_inp").show()
            $(".partial_payment").hide()
            $(".payment_mode_grp").hide()

        }
    })
    $(document).on('change', 'input[name="payment_mode"]', function() {
        var val = $(this).val();
        if (val == 'Cash and Online') {
            $(".partial_payment").show()
        } else {
            $(".partial_payment").hide()
        }
    })


    function deleteQuoteRow(quoteId, type) {
        if (type == 'quote') {
            $('[data-id="' + quoteId + '"]').remove()
        } else {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('vendor.invoice.delete-row') }}",
                data: {
                    type: type,
                    quoteId: quoteId
                },
                success: function(data) {
                    toasterNotification(data.message)
                    window.location.reload()
                },
            });

        }

    }

    function toasterNotification(msg) {
        $("#toast").text(msg);
        $("#toast").addClass("show");
        setTimeout(function() {
            $("#toast").removeClass("show");
        }, 3000);
    }

    function deleteNewRow(rowId) {
        $('[data-id="' + rowId + '"]').remove()
    }

    var unitOptions = `{!! \App\Models\Unit::all()->map(function ($unit) {
            return "<option value='{$unit->id}'>{$unit->unit}</option>";
        })->implode('') !!}`;

    function addMoreRow() {
        if ($('.item_row').length >= 10) {
            toasterNotification('You cannot add more than 10 items6.')
            return false; // Stops further execution
        }
        var $lastItemRow = $('.item_row').last();

        if (!$lastItemRow.length) {
            var dataId = 1;
        } else {
            var dataId = Number($lastItemRow.data('id')) + 1;

        }
        console.log(dataId)

        var html = `<tr class="item_row" data-id="` + dataId + `">
                       <input type="hidden" name="invoice_item_new[]" value="1" placeholder="Item Name" class="form-control">
                      <td><input type="text" name="item_name_new[]" placeholder="Item Name" class="form-control"></td>
                      <td style="width: 100px;"><input type="number" name="item_price_new[]" placeholder="Price" class="form-control price"></td>
                      <td  style="width: 58px;"><input type="number" name="item_qty_new[]" value="1" placeholder="Quantity" class="form-control qty"></td>
                      <td style="width:140px;">
                            <select name="item_unit_new[]" class="form-control js-select2-custom">
                                <option value="">-- Unit --</option>
                                ${unitOptions}
                            </select>
                        </td>
                      <td style="width: 58px;" class='tax_field'><input type="number" name="item_tax_new[]" placeholder="Tax" class="form-control tax"></td>
                      <td style="width: 93px;"><input type="text" name="item_hsn_new[]" placeholder="HSN" class="form-control"></td>
                       <td style="width: 93px;" class='tax_field'><input type="text" readonly placeholder="Taxable" class="form-control item_taxable"></td>
                       <td style="width: 93px;"><input type="text" readonly placeholder="Total" class="form-control item_total"></td>
                       <td><button type="button"  onclick="deleteNewRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>`;

        $('.rows_parent').append(html)
    }

    function calculateTotals() {
        console.log('calculateTotals 4')
        let totalWithoutGST = 0;
        let totalWithGST = 0;

        $('.item_row').each(function() {
            let price = parseFloat($(this).find('.price').val()) || 0;
            let qty = parseFloat($(this).find('.qty').val()) || 0;
            let tax = parseFloat($(this).find('.tax').val()) || 0;

            let lineTotal = price * qty;
            let gstAmount = lineTotal * (tax / 100);
            let lineTotalWithGST = lineTotal + gstAmount;

            totalWithoutGST += lineTotal;
            totalWithGST += lineTotalWithGST;

            $(this).find('.item_taxable').val(lineTotal)
            $(this).find('.item_total').val(lineTotalWithGST)

        });
console.log('thisis incl;')
        $('#totalWithoutGST').text(totalWithoutGST.toFixed(3));
        $('#totalWithoutGST_inv').text(totalWithoutGST.toFixed(3));
        $('#totalWithGSTHidden_inv').val(totalWithGST.toFixed(3));

        $('#totalWithGST').text(totalWithGST.toFixed(3));
        $('#totalWithGSTHidden').val(totalWithGST.toFixed(3));
    }

    // Trigger on input change
    $(document).on('keyup input change', '.price, .qty, .tax', function() {
        console.log('dsa4')
        calculateTotals();
    });

    // Initial calculation on page load
    $(document).ready(function() {
        calculateTotals();
    });
</script>
