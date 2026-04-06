<script>
    @include('partials.inventory_stock_notify_js')
    $(".bill_to_type").on('change', function() {
        const val = $(this).val();
          
        if (val === 'user' && $(this).prop('checked') == true) {
            $('#customer_list').show();
            $('#userSelect').attr('name', 'bill_to');

            $('#store_list').hide();
            $('#vendorSelect').attr('name', '');

            $('#mychitti_client_list').hide();
            $('#customer_id').attr('name', '');

        } else if (val === 'vendor') {
            $('#store_list').show();  
            $('#vendorSelect').attr('name', 'bill_to');

            $('#customer_list').hide();
            $('#userSelect').attr('name', ''); 

            $('#mychitti_client_list').hide();
            $('#customer_id').attr('name', '');

        } else if (val === 'mychitti_client') {
            $('#mychitti_client_list').show();
            $('#customer_id').attr('name', 'bill_to');

            $('#customer_list').hide();
            $('#userSelect').attr('name', '');

            $('#store_list').hide();
            $('#vendorSelect').attr('name', '');
        }
    });


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
     var allUnits = @json(\App\Models\Unit::select('id', 'unit')->get());

    function addMoreRow(item = null) {
        $(".items_table").show()
        $(".empty-state").hide()

        if ($('.item_row_inv').length >= 10) {
            toasterNotification('You cannot add more than 10 items1.')
            return false; // Stops further execution
        }

        var $lastItemRow = $('.item_row_inv').last();

        if (!$lastItemRow.length) {
            var dataId = 1;
        } else {
            var dataId = Number($lastItemRow.data('id')) + 1;
        }
        var className = '';
        if ($(".tax_type:checked").val() == 'non-gst') {

            className = 'hidden_tax';
            className2 = 'hidden_hsn';

        } else {
            className2 = '';
            className = '';
        }
        var item_name = '';
        var item_id = '';
        var item_price = '';
        var item_hsn = '';
        var item_unit = '';
        var secondary_unit = '';
        var readonly = '';
        var item_tax = '';
        if (item) {
            item_id = item.id
            item_name = item.item_name;
            item_price = item.selling_price;
            item_unit = item.unit;
            secondary_unit = item.secondary_unit;
            readonly = 'readonly';
            item_hsn = item.hsn;
            item_tax = item.tax;
        }

        console.log(dataId)

        var html = `<tr class="item_row_inv" data-id="` + dataId + `"
                        data-secondary-unit="${item?.secondary_unit ?? ''}"
                        data-primary-qty="${item?.primary_qty ?? 0}"
                        data-secondary-qty="${item?.secondary_qty ?? 0}"
                        data-primary-price="${item?.selling_price ?? 0}"
                        data-inventory-stock="${item && item.id ? (item.stock != null && item.stock !== '' ? item.stock : 0) : ''}">

                       <input type="hidden" name="inventory_item_id_new[]" value="` + item_id + `" >
                       <input type="hidden" name="invoice_item_new[]" value="1" >
                      <td><input type="text" name="item_name_new[]" value="` + item_name + `" placeholder="Item Name" class="form-control"></td>
                      <td style="width: 100px;"><input type="number" value="` + item_price + `" step="0.001" name="item_price_new[]" placeholder="Price" class="form-control price"></td>
                      <td style="width: 58px;"><input type="number"  name="item_qty_new[]" value="1" placeholder="Qunatity" class="form-control qty"></td>

                                <td style="width:140px;"><select name="item_unit_new[]" class="form-control js-select2-custom unit">
                        ${buildUnitOptions(item)}
                    </select>
                    </td>
                      <td style="width: 58px;" class="tax_inp_data ` + className +
            ` tax_field" ><input type="number" value="` + item_tax + `" name="item_tax_new[]" placeholder="Tax" class="form-control tax"></td>
                      <td style="width: 93px;" class="hsn_inp ` + className2 +
            `"><input type="text" name="item_hsn_new[]" value="` + item_hsn + `" placeholder="HSN" class="form-control"></td>
                       <td style="width: 93px;" class="hidden_tax"><input type="text" readonly placeholder="Taxable" class="form-control item_taxable"></td>
                        <td style="width: 93px;" class=""><input type="text"  readonly placeholder="Total" class="form-control item_total"></td>
                       <td><button type="button"  onclick="deleteNewRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>`;

        $('.rows_parent').append(html)
        calculateTotals()
    }

     function buildUnitOptions(item) {
         let options = `<option value="" selected disabled >-- Unit --</option>`;

         // No item → show all units
         if (!item) {
             allUnits.forEach(u => {
                 options += `<option value="${u.id}">${u.unit}</option>`;
             });
             return options;
         }

         // Item has secondary unit → show both
         if (item.secondary_unit) {
             allUnits.forEach(u => {
                 if (u.id == item.unit || u.id == item.secondary_unit) {
                     options +=
                         `<option value="${u.id}" ${u.id == item.unit ? 'selected' : ''}>${u.unit}</option>`;
                 }
             });
             return options;
         }

         // Item has only primary unit → show only that
         allUnits.forEach(u => {
             if (u.id == item.unit) {
                 options += `<option value="${u.id}" selected>${u.unit}</option>`;
             }
         });

         return options;
     }
    {{-- function addMoreRow() {
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
                      <td style="width: 58px;" class='tax_field hidden_field'><input type="number" name="item_tax_new[]" placeholder="Tax" class="form-control tax "></td>
                      <td style="width: 93px;" class="hidden_field"><input type="text" name="item_hsn_new[]" placeholder="HSN" class="form-control "></td>
                       <td style="width: 93px;" class='tax_field hidden_field'><input type="text" readonly placeholder="Taxable" class="form-control item_taxable "></td>
                       <td style="width: 93px;"><input type="text" readonly placeholder="Total" class="form-control item_total"></td>
                       <td><button type="button"  onclick="deleteNewRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>`;

        $('.rows_parent').append(html)
    } --}}

    $(".tax_type").on("change", function() {
        let gst_status = $(".tax_type:checked").val();
        console.log(gst_status)
        if (gst_status == 'non-gst') {
            $('.hidden_field').hide();
            $('.hidden_tax').hide();
            $('.hidden_hsn').hide();
            $('.totalWithGSTInp').hide();
        } else {
            $('.hidden_tax').show();
            $('.hidden_hsn').show();
            $('.hidden_field').show();
            $('.totalWithGSTInp').show();


        }
    })
    $('#vendorSelect').select2({
        ajax: {
            url: '{{ route('admin.ticket.search-vendors') }}',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return {
                    q: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data.results
                };
            },
            cache: true
        },
        minimumInputLength: 1,
        placeholder: 'Search vendor by name or phone...',
        allowClear: true
    });
    $('#customer_id').select2({
        ajax: {
            url: '{{ route('admin.search-mychitti-clients') }}',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return {
                    q: params.term
                };
            },
            processResults: function(data) {
                return { 
                    results: data.results
                };
            },
            cache: true
        },
        minimumInputLength: 1,
        placeholder: 'Search mychitti client by name or phone...',
        allowClear: true
    });

    $(document).ready(function() {
        $('#userSelect').select2({
            ajax: {
                url: '{{ route('admin.ticket.search-customers') }}',
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            },
            minimumInputLength: 1,
            placeholder: 'Search customer by name or phone...',
            allowClear: true
        });
    });

    function calculateTotals() {
        console.log('calculateTotals 4')
        let totalWithoutGST = 0;
        let totalWithGST = 0;

        var rowSelector = '{{ Route::is("admin.billing.manual-bill") || Route::is("admin.billing.edit") ? ".item_row_inv" : ".item_row" }}';
        $(rowSelector).each(function() {
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
        console.log(totalWithoutGST)
        console.log(totalWithGST)
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

    function add_inv_items() {
        var selectedData = $('#inventory_items').select2('data');
        let totalRequests = selectedData.length;
        let completed = 0;

        if (totalRequests === 0) {
            $('#inventoryItemModal').modal('hide');
            return;
        }

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        selectedData.forEach(function(item) {
            $.post({
                url: "{{ route('admin.inventory.get-item-info') }}",
                data: {
                    id: item.id,
                },
                success: function(data) {
                    notifyInventoryStockOnAdd(data);
                    addMoreRow(data);
                },
                complete: function() {
                    completed++;
                    if (completed === totalRequests) {
                        $('#inventory_items').val(null).trigger('change');
                        $('.inv_modal_close').click()
                    }
                }
            });
        });
    }
</script>
