 <script>
     @include('partials.inventory_stock_notify_js')
     $(document).ready(function() {
         $('#customer_id').select2({
             placeholder: 'Search for a customer',
             minimumInputLength: 3,
             ajax: {
                 url: "{{ route('vendor.customer.get-matches') }}", // Change to your endpoint
                 dataType: 'json',
                 delay: 250,
                 data: function(params) {
                     return {
                         q: params.term // search term
                     };
                 },
                 processResults: function(data) {
                     // Map your real customers
                     let results = data.map(customer => ({
                         id: customer.id,
                         text: customer.f_name + ' (' + customer.phone + ')'
                     }));

                     // Push the "+ Add New Client" option at the end
                     results.push({
                         id: 'add_new',
                         text: '+ Add New Client'
                     });

                     return {
                         results: results
                     };
                 },
                 cache: true
             }
         });
     });
     $(document).ready(function() {
         $('.customer_id2').select2({
             placeholder: 'Search for a customer',
             minimumInputLength: 3,
             ajax: {
                 url: "{{ route('vendor.customer.get-matches') }}", // Change to your endpoint
                 dataType: 'json',
                 delay: 250,
                 data: function(params) {
                     return {
                         q: params.term // search term
                     };
                 },
                 processResults: function(data) {
                     // Map your real customers
                     let results = data.map(customer => ({
                         id: customer.id,
                         text: customer.f_name + ' (' + customer.phone + ')'
                     }));

                     // Push the "+ Add New Client" option at the end
                     results.push({
                         id: 'add_new',
                         text: '+ Add New Client'
                     });

                     return {
                         results: results
                     };
                 },
                 cache: true
             }
         });
     });
     $("#customer_id").on('change', function() {
         if ($(this).val() == 'add_new') {
             $('#addCustomerModal').modal('show')
         }
     })
     $("#customer_id2").on('change', function() {
         if ($(this).val() == 'add_new') {
             $('#addCustomerModal').modal('show')
         }
     })
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
                     window.location.reload();
                 },
             });

         }

     }

     function hideCustomerInput() {
         $("#customer_id").val('').select2()
         $('#customer_id_elem').hide()
         $('.delete_icon').hide()
         $(".add_icon").show()
     }

     function showCustomerInput() {
         $('#customer_id_elem').show()
         $('.delete_icon').show()
         $(".add_icon").hide()
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
     $(".tax_type").on('change', function() {
         if ($(this).val() == 'non-gst') {
             $(".gst_invoice_num").hide()
             $(".gst_field").removeAttr('name')

             $(".nongst_invoice_num").show()
             $(".non_gst_field").attr('name', 'number')

             $('.tax_inp_data').addClass('hidden_tax')
             $('.hsn_inp').addClass('hidden_hsn')
             $('.totalWithGSTInp').hide()
         } else {
             $(".gst_invoice_num").show()
             $(".gst_field").attr('name', 'number')

             $(".nongst_invoice_num").hide()
             $(".non_gst_field").removeAttr('name')

             $('.tax_inp_data').removeClass('hidden_tax')
             $('.totalWithGSTInp').show()
             $('.hsn_inp').removeClass('hidden_hsn')
         }
     })
     var unitOptions = `{!! \App\Models\Unit::all()->map(function ($unit) {
             return "<option value='{$unit->id}'>{$unit->unit}</option>";
         })->implode('') !!}`;
     var allUnits = @json(\App\Models\Unit::select('id', 'unit')->get());


     function updatePriceByUnit($row) {
         {{-- console.log($row) --}}
         let selectedUnit = $row.find('.unit').val();

         let primaryUnit = $row.data('primary-unit');
         let secondaryUnit = $row.data('secondary-unit');

         let primaryPrice = parseFloat($row.data('primary-price')) || 0;
         let primaryQty = parseFloat($row.data('primary-qty')) || 0;
         let secondaryQty = parseFloat($row.data('secondary-qty')) || 0;

         {{-- console.log('primaryUnit' + primaryUnit)
         console.log('secondaryUnit' + secondaryUnit)
         console.log('primaryPrice' + primaryPrice)
         console.log('primaryQty' + primaryQty)
         console.log('secondaryQty' + secondaryQty) --}}

         let $priceInput = $row.find('.price');
         // Secondary unit selected → derive price
         if (
             secondaryUnit &&
             selectedUnit == secondaryUnit &&
             primaryQty > 0 &&
             secondaryQty > 0
         ) {
             let conversionRate = primaryQty / secondaryQty;
             let secondaryPrice = primaryPrice * conversionRate;
             $priceInput.val(secondaryPrice.toFixed(3));
         } else {
             $priceInput.val(primaryPrice.toFixed(3));
         }
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
                        data-primary-price="${item?.selling_price ?? 0}">

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

     function calculateTotals() {
         let totalWithoutGST = 0;
         let totalWithGST = 0;
         let totalGST = 0;
         let delivery_charges = parseFloat($('#delivery_charges').val()) || 0;


         type = 'inv';
         $('.item_row_' + type).each(function() {
             let price = parseFloat($(this).find('.price').val()) || 0;
             let qty = parseFloat($(this).find('.qty').val()) || 0;
             let tax = parseFloat($(this).find('.tax').val()) || 0;

             let lineTotal = price * qty;
             let gstAmount = lineTotal * (tax / 100);
             let lineTotalWithGST = lineTotal + gstAmount;

             totalWithoutGST += lineTotal;
             totalGST += gstAmount;
             totalWithGST += lineTotalWithGST;

             $(this).find('.item_taxable').val(lineTotal)
             $(this).find('.item_total').val(lineTotalWithGST)
         });

         totalWithoutGSTSubtotal = totalWithoutGST;
         totalWithoutGST = totalWithoutGST + delivery_charges;
         totalWithGST = totalWithGST + delivery_charges;
         $('#taxable_amount').text(totalWithoutGSTSubtotal.toFixed(3));
         $('#tax_amount').text(totalGST.toFixed(3));
         $('#totalWithoutGST').text(totalWithoutGST.toFixed(3));
         $('#totalWithoutGST_inv').text(totalWithoutGST.toFixed(3));
         $('#totalWithGSTHidden').val(totalWithGST.toFixed(3));
         $('#totalWithGST').text(totalWithGST.toFixed(3));

         $('#totalWithGSTHidden_inv').val(totalWithGST.toFixed(3));
     }

     // Trigger on input change
     $(document).on('keyup input change', '.price, .qty, .tax, #delivery_charges, .unit', function() {
         let $row = $(this).closest('.item_row_inv');

         if ($(this).hasClass('unit')) {
             updatePriceByUnit($row);
         }
         calculateTotals();
     });

     // Initial calculation on page load
     $(document).ready(function() {
         calculateTotals();
     });

     $('#customer_id').on('change', function() {
         var selectedOption = $(this).find('option:selected');
         var userType = selectedOption.data('type'); // Gets data-type attribute

         if (userType) {
             $('.user_type_show').text(userType.charAt(0).toUpperCase() + userType.slice(1));
         } else {
             $('.user_type_show').text('');
         }
     });

     function validateInvoiceNum(elem, tax_type) {
         if ($(elem).val() == '') {
             if (tax_type == 'gst') {
                 $('.invoice_error').text('Invoice Id Required');
             } else {
                 $('.invoice_error2').text('Invoice Id Required');
             }
             $('.submit_btn').attr('disabled', true)
             return false;
         } else {
             if (tax_type == 'gst') {
                 $('.invoice_error').text('');
                 var prefix = $('.invoice_prefix').text()

             } else {
                 $('.invoice_error2').text('');
                 var prefix = $('.invoice_prefix2').text()

             }
             $('.submit_btn').attr('disabled', true);
         }

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
             }
         });
         $.ajax({
             url: "{{ route('vendor.invoice.validate-invoicenum') }}",
             type: 'POST',
             data: {
                 number: $(elem).val(),
                 number: $(elem).val(),
                 prefixe: prefix,
                 tax_type: $('.tax_type:checked').val()
             },
             success: function(data) {
                 if (data == 'exists') {
                     $('.invoice_error').text('This Invoice Serial is Already Used');
                     $('.submit_btn').attr('disabled', true)
                 } else {
                     $('.invoice_error').text('');
                     $('.submit_btn').removeAttr('disabled')

                 }
             },
         });
     }

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
                 url: "{{ route('vendor.inventory.get-item-info') }}",
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
