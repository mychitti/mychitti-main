 <script>
     let invoiceDeleteRowUrl = "{{ route('admin.billing.delete-row') }}";
     let inventoryGetItemInfoUrl = "{{ route('admin.inventory.get-item-info') }}";
     let customerFetchDetailsUrl = "{{ route('admin.client.fetch-details') }}";
     let invoiceValidateInvoiceNumUrl = "{{ route('admin.billing.validate-invoicenum') }}";
     let businessSettingsTncFetchUrl = "{{ route('admin.business-settings.tnc.fetch', ':id') }}";
     let businessSettingsSignatureFetchUrl = "{{ route('admin.business-settings.signature.fetch') }}";
     $('.submit_btn').on('click', function() {
         if ($('.item_row').length) {
             $("#invoice_form").submit();
         } else {
             toasterNotification("Add Atleast One Item")
         } 
     })
     $(document).on('change', 'input[name="payment_mode"]', function() {
         var val = $(this).val();
         console.log(val)
         if (val == 'Cash and Online') {
             $(".partial_payment").show()
         } else {
             $(".partial_payment").hide()
         }
     })
     let ckeditorInstance = null;

     if (!ckeditorInstance) {
         if (typeof ClassicEditor !== 'undefined') {
             ClassicEditor 
                 .create(document.querySelector('#ckeditor2'))
                 .then(editor => {
                     ckeditorInstance = editor;
                 })
                 .catch(error => {
                     console.error(error);
                 });
         }
     }

     $('#tnc_id').on('change', function() {
         var id = $(this).val();
         if (id == 'add_new') {

         }
         if (!id) return;
         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
             }
         });
         {{-- $.get({
             url: "{{ route('admin.business-settings.tnc.fetch', ':id') }}".replace(':id', id),
             success: function(data) {
                 if (ckeditorInstance) {
                     ckeditorInstance.setData(data);
                 }
             }
         }); --}}
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
             $(".payment_mode_info").hide()

         } else {
             $(".payment_date_inp").show()
             $(".reminder_date_inp").show()
             $(".partial_payment").hide()
             $(".payment_mode_grp").hide()
             $(".payment_mode_info").show()
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
                 url: "{{ route('admin.billing.delete-row') }}",
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
         recalculateInvoice();
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
         $('[data-id="' + rowId + '"]').remove();
         updateEmptyState()

     }
     var unitOptions = `{!! \App\Models\Unit::all()->map(function ($unit) {
             return "<option value='{$unit->id}'>{$unit->unit}</option>";
         })->implode('') !!}`;
     var allUnits = @json(\App\Models\Unit::select('id', 'unit')->get());

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

         if ($('.item_row').length >= 10) {
             toasterNotification('You cannot add more than 10 items3.')
             return false; // Stops further execution
         }

         var $lastItemRow = $('.item_row').last();

         if (!$lastItemRow.length) {
             var dataId = 1;
         } else {
             var dataId = Number($lastItemRow.data('id')) + 1;
         }

         var className = '';
         if ($(".tax_type:checked").val() == 'non-gst') {
             className = 'hidden_tax';
             className2 = 'hidden_hsn';
             classNew = 'gst_fld hidden_gst_f';

         } else {
             className2 = '';
             className = '';
             classNew = 'gst_fld';
         }
         var item_name = '';
         var item_id = '';
         var item_price = '';
         var item_hsn = '';
         var readonly = '';
         var unit = '';
         var tax = '';
         var taxable_amount = '';
         var total = '';
         if (item) {
             item_name = item.item_name;
             item_price = item.selling_price;
             readonly = 'readonly';
             item_id = item.id;
             item_hsn = item.hsn;
             total = item_price + (item_price * tax / 100);
         }
         var html = `<tr class="item_row" data-id="` + dataId + `"
         data-secondary-unit="${item?.secondary_unit ?? ''}"
    data-primary-qty="${item?.primary_qty ?? 0}"
    data-secondary-qty="${item?.secondary_qty ?? 0}"
    data-primary-price="${item?.selling_price ?? 0}">
                       <input type="hidden" name="inventory_item_id[]" value="` + item_id +
             `"  class="form-control">
                       <input type="hidden" name="invoice_item_new[]" value="1"  class="form-control">
                      <td><label class="small_label">Item Name</label><input required type="text" name="item_name_new[]" value="` +
             item_name + `" ` + readonly + ` placeholder="Item Name" class="form-control item_name"></td>
                      <td style="width: 100px;"><label class="small_label">Price</label><input type="number" value="` +
             item_price + `"  step="0.001" name="item_price_new[]" placeholder="Price" class="form-control price item_price"></td>
                      <td style="width: 58px;"><label class="small_label">Qty</label><input type="number" name="item_qty_new[]" value="1" placeholder="Qunatity" class="form-control qty item_qty"></td>
                       <td style="width:140px;">



                       <label class="small_label">Unit</label>
                            <select name="item_unit_new[]" class="form-control js-select2-custom unit_select unit ` +
             dataId + `">
                                           ${buildUnitOptions(item)}

                            </select>
                        </td>
                      <td style="width: 58px;" class="tax_inp_data ` + classNew +
             ` tax_field" ><label class="small_label">Tax</label><input value="` +
             tax + `" type="number" name="item_tax_new[]" placeholder="Tax" class="form-control tax item_tax"></td>
                      <td style="width: 93px;" class="hsn_inp ` + classNew +
             `"><label class="small_label">HSN</label><input value="` + item_hsn + `" type="text" name="item_hsn_new[]" placeholder="HSN" class="form-control"></td>
                       <td style="width: 93px;" class="` + classNew +
             ` item_taxable_td"><label class="small_label">Taxable</label><input type="text" readonly value="` + (
                 item_price ?? '') +
             `"  placeholder="Taxable" class="form-control item_taxable"></td>
                        <td style="width: 93px;" class=""><label class="small_label">Total</label><input type="text"  readonly placeholder="Total" value="` +
             total + `"  class="form-control item_total"></td>
                       <td  style="width: 13px;"><button type="button"  onclick="deleteNewRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>`;

         $('.rows_parent').append(html)
         if (item) {
             $('.unit_select' + dataId).val(item.unit).trigger('change');
         }

         updateEmptyState();
     }

     function updateEmptyState() {
         console.log($('.item_row').length)
         if ($('.item_row').length == 0) {
             $(".empty-state").show()
         } else {
             $(".empty-state").hide()
         }
     }

     function calculateTotals() {
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

         $('#totalWithoutGST').text(totalWithoutGST.toFixed(3));
         $('#totalWithGST').text(totalWithGST.toFixed(3));
     }

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
     // Trigger on input change
     $(document).on('keyup input change', '.price, .qty, .tax, .unit', function() {
         let $row = $(this).closest('.item_row');
         console.log($row)
         if ($(this).hasClass('unit')) {
             updatePriceByUnit($row);
         }
         calculateTotals();
     });

     // Initial calculation on page load
     $(document).ready(function() {
         calculateTotals();
     });
     $('#add_cust_btn').on('click', function() {
         $('#addCustomerModal').modal('show')

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
        minimumInputLength: 3,
        placeholder: 'Search vendor by name or phone...',
        allowClear: true
    });
    $('#mychittiClientSelect').select2({
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
        minimumInputLength: 3,
        placeholder: 'Search mychitti client by name or phone...',
        allowClear: true
    });
 $(".bill_to_type").on('change', function() {
            const val = $(this).val();

            if (val === 'user' && $(this).prop('checked') == true) {
                $('#customer_list').show();
                $('#customerSelect').attr('name', 'bill_to');

                $('#store_list').hide();
                $('#vendorSelect').attr('name', '');

                $('#mychitti_client_list').hide();
                $('#mychittiClientSelect').attr('name', '');

            } else if (val === 'vendor') {
                $('#store_list').show();
                $('#vendorSelect').attr('name', 'bill_to');

                $('#customer_list').hide();
                $('#customerSelect').attr('name', '');

                $('#mychitti_client_list').hide();
                $('#mychittiClientSelect').attr('name', '');

            } else if (val === 'mychitti_client') {
                $('#mychitti_client_list').show();
                $('#mychittiClientSelect').attr('name', 'bill_to');

                $('#customer_list').hide();
                $('#customerSelect').attr('name', '');

                $('#store_list').hide();
                $('#vendorSelect').attr('name', '');
            }
        });
    $('#customerSelect').select2({
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
        minimumInputLength: 3,
        placeholder: 'Search customer by name or phone...',
        allowClear: true
    });

     $(".customer_add_form").on('submit', function(e) {
         e.preventDefault();
         var formData = new FormData($(this).get(0));
         formData.append('form_type', 'ajax');

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
             }
         });

         $.ajax({
             url: $(this).attr('action'),
             type: 'POST',
             data: formData,
             processData: false,
             contentType: false,
             success: function(data) {
                 if (data.status) {
                     $('#addCustomerModal').modal('hide')
                     $('#addSignModal').modal('hide')
                     $('.account_modal_close_btn').click()
                     toasterNotification(data.msg)
                     var url = window.location.href;
                     if (data.action == 'add_customer') {
                         $('.with_add_new').load(url + ' .customer_elem_inner', function() {
                             $("#customer_id").select2();
                             $('#customer_id').val(data.customer_id);
                             showUserCard(data.customer)
                         });
                     } else if (data.action == 'add_sign') {
                         $('.sign_section').load(url + ' .sign_section_inner', function() {
                             $("#sign_id2").select2();
                         })
                     } else if (data.action == 'add_bankaccount') {
                         $('.account_section').load(url + ' .account_section_inner', function() {
                             $("#sign_id2").select2();
                         })
                     } else if (data.action == 'add_inventory') {
                         $('.inventory_section').load(url + ' .inventory_section_inner', function() {
                             $(".inv_close_btn").click()
                             $("#inventory_items").select2();
                             $('#inventory_items').val(data.item_id).trigger('change');
                             add_inv_items()
                         })
                     }
                 } else if (data.errors) {
                     for (let i = 0; i < data.errors.length; i++) {
                         toasterNotification(data.errors[i])

                     }
                 }
             },
             error: function(xhr) {
                 console.log('error', xhr.responseText);
             }
         });
     });


     $("#coverImageUpload").change(function() {
         readURL(this, "coverImageViewer");
     });
     $('#inventory_items').on('change', function() {
         var selectedVal = $(this).val();
         console.log(selectedVal)
         if (selectedVal === 'add_new' || (Array.isArray(selectedVal) && selectedVal.includes('add_new'))) {
             $("#inventory_items").val('').trigger('change')
             $('.inv_modal_close').click()
             $('#addInventoryItemModal').modal('show');
             return;
         }
     });
     $(document).on('change', '#customer_id', function() {
         var selectedVal = $(this).val();
console.log('fasdfasf')
         if (selectedVal === 'add_new') {
             $('#addCustomerModal').modal('show');
             return;
         } else if (!selectedVal) {
             return;

         }

         var selectedOption = $(this).find('option:selected');

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
             }
         });

         $.ajax({
             url: "{{ route('admin.client.fetch-details') }}",
             type: 'POST',
             data: {
                 id: selectedVal,
                 type: selectedOption.data('type')
             },
             success: function(data) {
                 showUserCard(data);
                 console.log('after showUserCard')
                 // $('.user_type_show').html(data.some_value); // example
             }
         });
     });

     function showUserCard(data) {
         console.log('in showUserCard')
         var html = `<h6>` + data.f_name + `</h6>
                                        <p class="mb-0" style="font-size:12px">` + data.phone + `</p> ` +
             (data.email ? `<p class="mb-0" style="font-size:12px">` + data.email + `</p>` : '');
         if (data.billing_address) {
             let billing = data.billing_address;
             html += `<h6 class="mb-0 mt-2">Billing Address</h6><p class="mb-0" style="font-size:12px">` +
                 (billing.address1 ?? '') + ' ' +
                 (billing.address2 ?? '') + ' ' +
                 (billing.state ?? '') + ' ' +
                 (billing.city ?? '') + ' ' +
                 (billing.pincode ?? '') +
                 `</p>`;
         }

         if (data.shipping_address) {
             let shipping = data.shipping_address;
             html += `<h6 class="mb-0 mt-2">Shipping Address</h6><p class="mb-0" style="font-size:12px">` +
                 (shipping.address1 ?? '') + ' ' +
                 (shipping.address2 ?? '') + ' ' +
                 (shipping.state ?? '') + ' ' +
                 (shipping.city ?? '') + ' ' +
                 (shipping.pincode ?? '') +
                 `</p>`;
         }

         $(".customer_select_grp").hide()
         $(".cust_det_card").show()
         $(".customer_info").html(html)
     }


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
             url: "{{ route('admin.billing.validate-invoicenum') }}",
             type: 'POST',
             data: {
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

     document.addEventListener('DOMContentLoaded', function() {
         // Sidebar toggle functionality
         const sidebarHeaders = document.querySelectorAll('.sidebar-header');
         sidebarHeaders.forEach(header => {
             header.addEventListener('click', function() {
                 const content = this.nextElementSibling;
                 const icon = this.querySelector('.dropdown-icon');

                 if (content.style.display === 'none') {
                     content.style.display = 'block';
                     icon.textContent = '▼';
                 } else {
                     content.style.display = 'none';
                     icon.textContent = '▶';
                 }
             });
         });

         // Search functionality
         const searchBox = document.querySelector('.search-box');
         if (searchBox) {
             searchBox.addEventListener('input', function(e) {
                 console.log('Searching for:', e.target.value);
             });
         }

         // Add product buttons
         const addProductBtns = document.querySelectorAll('.btn-primary');
         addProductBtns.forEach(btn => {
             if (btn.textContent.includes('Add New Product')) {
                 btn.addEventListener('click', function() {
                     alert('Add Product modal would open here');
                 });
             }
         });

         // Customer selection
         const selectCustomerBtn = document.querySelector('[data-action="select-customer"]');
         if (selectCustomerBtn) {
             selectCustomerBtn.addEventListener('click', function() {
                 alert('Customer selection modal would open here');
             });
         }

         // Signature functionality
         const signatureBtn = document.querySelector('.add-signature-btn');
         if (signatureBtn) {
             signatureBtn.addEventListener('click', function() {
                 alert('Signature pad would open here');
             });
         }

         // Action buttons
         const actionButtons = document.querySelectorAll('.totals-section .btn, .totals-section .btn-primary');
         actionButtons.forEach(btn => {
             btn.addEventListener('click', function() {
                 const action = this.textContent.trim();
                 console.log('Action:', action);

                 if (action.includes('Send')) {
                     alert('Invoice would be sent to customer');
                 } else if (action.includes('Save as Draft')) {
                     alert('Invoice saved as draft');
                 } else if (action.includes('Save and Print')) {
                     alert('Invoice would be saved and print dialog opened');
                 }
             });
         });

         // Filter checkboxes
         const checkboxes = document.querySelectorAll('.checkbox');
         checkboxes.forEach(checkbox => {
             checkbox.addEventListener('change', function() {
                 console.log('Filter changed:', this.id, this.checked);
             });
         });

         // Progress bar animation
         setTimeout(() => {
             const progressBar = document.querySelector('.progress-bar');
             if (progressBar) {
                 progressBar.style.width = '85%';
             }
         }, 500);

         // Form validation
         const inputs = document.querySelectorAll('input');
         inputs.forEach(input => {
             input.addEventListener('blur', function() {
                 if (this.hasAttribute('required') && !this.value.trim()) {
                     this.style.borderColor = '#d73027';
                 } else {
                     this.style.borderColor = '#dadce0';
                 }
             });
         });
     });

     $(document).ready(function() {
         $('#custom-buttons').on('click', 'button', function() {
             const label = $(this).data('label');
             let inputGroup = '';

             if (label === 'Other') {
                 inputGroup = `
        <div class="form-group custom-field" data-label="${label}">
            <div class="d-flex mb-2">
                <input type="text" class="form-control mr-2" placeholder="Label" name="header_label[]">
                <input type="text" class="form-control mr-2" name="header_field[]">
                <a type="button" class="text-danger remove-field"><i class="tio-delete-outlined"></i></a>
            </div>
        </div>
        `;
             } else {
                 const inputName = label.toLowerCase().replace(/\s+/g, '_'); // e.g., vehicle_no

                 inputGroup = `
        <div class="form-group custom-field" data-label="${label}">
            <label for="${inputName}">${label}</label>
            <div class="d-flex">
                <input type="hidden" name="header_label[]" value="${label}" id="${label}">
                <input type="text" class="form-control mr-2" name="header_field[]" id="${inputName}">
                <a type="button" class="text-danger remove-field"><i class="tio-delete-outlined"></i></a>
            </div>
        </div>
        `;

                 // Hide the clicked button
                 $(this).hide();
             }

             $('#custom-fields').append(inputGroup);
         });

         //  Handle remove
         $('#custom-fields').on('click', '.remove-field', function() {
             const $fieldGroup = $(this).closest('.custom-field');
             const label = $fieldGroup.data('label');

             // Show back the corresponding button
             $('#custom-buttons button').each(function() {
                 if ($(this).data('label') === label) {
                     $(this).show();
                 }
             });

             $fieldGroup.remove();
         });

     });
     $(".more_additional_charges").on('click', function() {
         if ($(".tax_type:checked").val() == 'non-gst') {
             classNew = 'gst_fld hidden_gst_f';
         } else {
             classNew = '';
         }
         var newRow = `
             <tr class="add_chrg_row">
                <td>
                   <label class="small_label">Name</label> <input class="form-control small_field" name="charges_name[]"
                        placeholder="Ex: Handling Charges">
                </td>
                <td>
                   <label class="small_label">Charges</label> <input type="number" class="form-control small_field additional_charges " name="add_charges[]"
                        placeholder="Ex: 90">
                </td>
                <td class="` + classNew + `">
                    <div class="input-group mb-3">
                         <label class="small_label">Tax</label><input type="number" class="form-control small_field charges_tax" name="charges_tax[]"
                            placeholder="Ex: 12">
                        <select name="tax_status[]" class="form-control charges_tax_status" id="">
                            <option value="included">Included</option>
                            <option value="excluded">Excluded</option>
                        </select>
                    </div>
                </td>
                <td>
                    <a class="cursor-pointer"><i class="tio-remove-circle-outlined text-danger remove_add_charge" onclick="removeAddChargeRow(this)"></i></a>
                </td>
             </tr>
              `;

         // Insert before the `.more_additional_charges` button row
         $(this).closest('.row2').before(newRow);
     });

     function removeAddChargeRow(elem) {
         $(elem).closest('tr.add_chrg_row').remove();
     }
     $(".card_close_btn").on('click', function() {
         $(".cust_det_card").hide()
         $(".customer_select_grp").show()
         $("#customer_id").val('').trigger('change')
     })
     $(document).on('change', "#sign_id2", function() {
         console.log('fd')
         var selectedVal = $(this).val();
         if (selectedVal === 'add_new') {
             $('#addSignModal').modal('show');

         } else {
             var sign_id = $(this).val();
             $('.add_new_sign').hide();
             $.ajaxSetup({
                 headers: {
                     'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                 }
             });
             $.post({
                 url: "{{ route('admin.business-settings.signature.fetch') }}",
                 data: {
                     id: sign_id,
                 },
                 success: function(data) {
                     var html = `<img width="200px" style="margin:5px 0" src="` + data +
                         `" alt="sign">`;
                     if (data) {
                         $('#selected_sign_preview').html(html)
                     }
                 },
             });
         }
     })
     let tcs_applied = tds_applied = false;
     $(".tax_rate").on('change', function() {
         // Helper to toggle visibility
         function toggle(show, selectors) {
             selectors.forEach(sel => $(sel).toggle(show));
         }

         // Reset all tax sections
         toggle(false, [
             ".tds_list", ".tcs_list", ".tds_under_gst_elem",
             ".tds_amount_label", ".tds_amount_fld",
             ".tds_gst_amount_fld", ".tds_gst_amount_label",
             ".tcs_amount_label", ".tcs_amount_fld",
             ".cess_inp", ".cess_amount_label", ".cess_amount_fld",
             ".surcharge_inp", ".surcharge_amount_fld", ".surcharge_amount_label"
         ]);

         // TDS logic
         const isTDS = $('.tax_rate[value="tds"]').is(':checked');
         if (isTDS) {
             $('.tax_rate[value="tcs"]')
                 .prop('checked', false)
                 .prop('disabled', true)
                 .attr('title', "TCS and TDS cannot be applied together");

             toggle(true, [".tds_list", ".tds_amount_label", ".tds_amount_fld"]);
             tds_applied = true;
         } else {
             $('#tds_rate_id').val(null).trigger('change');
             $('.tax_rate[value="tcs"]').prop('disabled', false).removeAttr('title');
             tds_applied = false;
         }

         // TCS logic
         const isTCS = $('.tax_rate[value="tcs"]').is(':checked');
         if (isTCS) {
             $('.tax_rate[value="tds"]')
                 .prop('checked', false)
                 .prop('disabled', true)
                 .attr('title', "TCS and TDS cannot be applied together");

             toggle(true, [".tcs_list", ".tcs_amount_label", ".tcs_amount_fld"]);
             tcs_applied = true;
         } else {
             $('#tcs_rate_id').val(null).trigger('change');
             $('.tax_rate[value="tds"]').prop('disabled', false).removeAttr('title');
             tcs_applied = false;
         }

         // TDS under GST logic
         const isTDSGST = $('.tax_rate[value="tds_gst"]').is(':checked');
         if (isTDSGST) {
             $(".tds_gst_rate").val(2).trigger('change')
             toggle(true, [".tds_under_gst_elem", ".tds_gst_amount_fld", ".tds_gst_amount_label"]);
             tds_gst_applied = true;
         } else {
             $(".tds_gst_rate").val(0).trigger('change')

         }

         // CESS logic
         toggle($('.tax_rate[value="cess"]').is(':checked'), [".cess_inp", ".cess_amount_label",
             ".cess_amount_fld"
         ]);

         // SURCHARGE logic
         toggle($('.tax_rate[value="surcharge"]').is(':checked'), [".surcharge_inp", ".surcharge_amount_fld",
             ".surcharge_amount_label"
         ]);
     });




     $(document).ready(function() {

         $('#customer_id, #sign_id2, #inventory_items, #tnc_id').on('select2:open', function() {
             setTimeout(function() {
                 const $addNewOption = $('li.select2-results__option[id$="-add_new"]');
                 console.log($addNewOption);

                 $addNewOption.css({
                     'color': 'rgb(13, 96, 252)',
                     'font-weight': 'bold'
                 });
             }, 0);
         });

     });

     $(".bank_account").on('change', function() {
         var ischecked = $(this).prop('checked')
         $('.bank_account').prop('checked', false);
         $(this).prop('checked', ischecked)
     })


     function recalculateInvoice() {
         if ($('.tax_type[value="gst"]').is(':checked')) {
             $('.nongst_fld').hide();
             $('.gst_fld').show();
             $(".gst_field").attr('name', 'number');
             $(".non_gst_field").removeAttr('name');
             $('#gst_section').show();
             $('#gst_section').show();
         } else {
             $('.nongst_fld').show();
             $('.gst_fld').hide();
             $(".gst_field").removeAttr('name');
             $(".non_gst_field").attr('name', 'number');
             $(".item_tax").val('');
             $('#gst_section').hide();

         }

         let totalItemsAmount = 0;
         let totalItemsTax = 0;
         let additionalChargesTotal = 0;
         let additionalChargesTax = 0;

         $('.item_row').each(function() {
             const unitPrice = parseFloat($(this).find('.item_price').val()) || 0;
             const quantity = parseFloat($(this).find('.item_qty').val()) || 0;
             const taxPercent = parseFloat($(this).find('.item_tax').val()) || 0;

             const amount = unitPrice * quantity;
             const taxAmount = amount * (taxPercent / 100);

             totalItemsAmount += amount;
             totalItemsTax += taxAmount;

             $(this).find('.item_taxable').val(amount.toFixed(3));
             $(this).find('.item_total').val((amount + taxAmount).toFixed(3));
         });

         let add_chrg_html = '';
         $('.add_chrg_row').each(function() {
             const chargeInput = parseFloat($(this).find('.additional_charges').val()) || 0;
             const taxPercent = parseFloat($(this).find('.charges_tax').val()) || 0;
             const taxType = $(this).find('.charges_tax_status').val();

             let charges = 0;
             let taxAmount = 0;

             if (taxType === 'included') {
                 const baseAmount = chargeInput / (1 + (taxPercent / 100));
                 taxAmount = chargeInput - baseAmount;
                 charges = baseAmount;
             } else {
                 charges = chargeInput;
                 taxAmount = chargeInput * (taxPercent / 100);
             }

             additionalChargesTotal += charges;
             additionalChargesTax += taxAmount;

             let chargeNameInput = $(this).closest('tr').find('input[name="charges_name[]"]');
             let chargesInput = $(this).closest('tr').find('input[name="add_charges[]"]');
             if ($(chargeNameInput).val().trim() !== '' && $(chargesInput).val().trim() !== '') {
                 add_chrg_html += `<div class="col-md-6 tcs-label-small">${$(chargeNameInput).val()}</div>
                              <div class="col-md-6 text-right">${charges.toFixed(3)}</div>`;
             }
         });
         $('.additional_charges_show').html(add_chrg_html);

         // Discount
         const discountValue = parseFloat($('.total_discount').val()) || 0;
         const discountType = $('.total_discount_type').val();
         const baseAmount = totalItemsAmount + additionalChargesTotal;

         let discount = discountType === 'percent' ? (baseAmount * discountValue) / 100 : discountValue;
         const taxableAmount = baseAmount - discount;

         // TDS / TCS
         let tdsAmount = 0,
             tdsGstAmount = 0,
             tcsAmount = 0;


         if ($('.tax_rate[value="tds"]').is(':checked')) {
             const tdsPercent = $('#tds_rate_id option:selected').data('value') || 0;
             tdsAmount = taxableAmount * (tdsPercent / 100);
         } else {
             tdsAmount = 0;
         }

         if ($('.tax_rate[value="tds_gst"]').is(':checked')) {
             const tdsGstPercent = $('#tds_gst_rate').val() || 0;
             tdsGstAmount = taxableAmount * (tdsGstPercent / 100);
         }

         if ($('.tax_rate[value="tcs"]').is(':checked')) {
             const tcsPercent = $('#tcs_rate_id option:selected').data('value') || 0;
             tcsAmount = taxableAmount * (tcsPercent / 100);
         }

         // Surcharge on GST
         const gstTaxAmount = totalItemsTax + additionalChargesTax;
         const surchargePercent = parseFloat($('#surcharge_inp').val()) || 0;
         const surchargeAmount = gstTaxAmount * (surchargePercent / 100);
         const taxAfterSurcharge = gstTaxAmount + surchargeAmount;

         // Cess on (GST + Surcharge)
         const cessPercent = parseFloat($('#cess_inp').val()) || 0;
         const cessAmount = taxAfterSurcharge * (cessPercent / 100);

         // Final tax & invoice total
         const finalTax = taxAfterSurcharge + cessAmount;
         const totalAmount = taxableAmount + finalTax + tcsAmount;

         // Update UI
         $('.taxable_amount_fld').text(taxableAmount.toFixed(3));
         $('.total-tax-field').text(finalTax.toFixed(3));
         $('.tds_amount_fld').text(tdsAmount.toFixed(3));
         $('.tcs_amount_fld').text(tcsAmount.toFixed(3));

         $('.cess_amount_fld').text(cessAmount.toFixed(3));
         $('.surcharge_amount_fld').text(surchargeAmount.toFixed(3));

         $('.tds_gst_amount_fld').text(tdsGstAmount.toFixed(3));
         $('.surcharge_amount_fld').text(surchargeAmount.toFixed(3));
         $('.cess_amount_fld').text(cessAmount.toFixed(3));
         $('.invoice-total-amount').text(totalAmount.toFixed(3));
         $('.invoice-total-amount-hidden').val(totalAmount.toFixed(3));
         $('.total_discount_fld').text(discount.toFixed(3));
         $('.add_chrg_fld_totl').val(additionalChargesTotal);

         console.log('totalItemsAmount: ' + totalItemsAmount);

         var cash = parseFloat($(".cash_amount").val()) || 0;
         var online = parseFloat($(".online_amount").val()) || 0;

         var entered = cash + online;
         if ($("input[name='payment_mode']:checked").val() == 'Cash and Online') {
             if (entered > totalAmount) {
                 $(".amount_error").text("Amount is greater than total!");
                 $(".submit_btn").attr('disabled', true)
             } else if (entered < totalAmount) {
                 $(".amount_error").text("Amount is less than total!");
                 $(".submit_btn").prop("disabled", true);
             } else {
                 $(".amount_error").text("");
                 $(".submit_btn").removeAttr("disabled");
             }
         } else {
             $(".amount_error").text("");
             $(".submit_btn").removeAttr("disabled");
         }
     }

     $('body').on('keyup change',
         '.cash_amount, .online_amount, .price, .qty, .payment_mode .tax_type, .item_price, .item_tax, .charges_tax_status, .tds_under_gst, .tax_rate, .item_qty, #cess_inp, #surcharge_inp, .additional_charges, .charges_tax, .remove_add_charge, .tds_under_gst, .charges_tax_status, #tcs_rate_id, #tds_rate_id, .total_discount, .total_discount_type ',
         function() {
             recalculateInvoice();
         });

     function showGSTAlert() {
         $('.alert_elem').show(300)
         setTimeout(() => {
             $('.alert_elem').hide(300)
         }, 10000);
     }
     $(".item_type").on('change', function() {
         if ($(this).val() == 'service') {
             $(".product_inp_group").hide();
             $('.mrp_fld').text('Price')
         } else {
             $(".product_inp_group").show();
             $('.mrp_fld').text('MRP')

         }
     })

     function readURL2(input) {
         if (input.files && input.files[0]) {
             let reader = new FileReader();
             reader.onload = function(e) {
                 $('#viewer3').attr('src', e.target.result);
             }
             reader.readAsDataURL(input.files[0]);
         }
     }

     $("#customFileEg3").change(function() {
         readURL2(this);
     });

     function checkPricing(elem = null) {


         var $elem = $(elem);

         // If item is selected
         if ($elem.attr('id') == 'item_id_select') {

             var item_id = $elem.val();
             fetch_item_details(item_id); //just to show on page

             var landing_price = 0;
             $.ajax({
                 url: "{{ route('admin.inventory.get_my_fee_amount') }}",
                 type: "POST",
                 data: {
                     _token: $('[name="_token"]').val(),
                     item_id: item_id,
                 },
                 success: function(resp) {
                     $("#hidden_inp").val(resp.my_fee);
                     $("#fee_type").val(resp.fee_type);
                     $("#gst_percent_hidden").val(resp.gst_percent);
                     $("#discount_value_hidden").val(resp.discount_value);
                     $("#discount_type_hidden").val(resp.discount_type);
                 }
             });
         }
         if ($("#item_id_select").val() == '') {
             $(".item_error").text('Please select an item');
             $('#landing_price').val('')
             $('#selling_price').val('')
             $('#taxable_amount').val('')
             return false;
         } else {
             $(".item_error").text('');

         }
         var my_fee = parseFloat($("#hidden_inp").val()) || 0;
         var fee_type = $("#fee_type").val() || 'amount';
         var gst_percent = parseFloat($("#gst_percent_hidden").val()) || 0;
         var gst_status = $('#price_gst_status').val(); // 'including_gst' or 'excluding_gst'
         var discount_value = parseFloat($('#discount_value_hidden').val()) || 0; // 'including_gst' or 'excluding_gst'
         var discount_type = $('#discount_type_hidden').val(); // 'including_gst' or 'excluding_gst'

         var landing_price = parseFloat($("#landing_price").val()) || 0;
         var fee = 0;

         if (fee_type === 'percent') {
             fee = (landing_price * my_fee) / 100;
         } else {
             fee = my_fee;
         }

         var base_price = fee + landing_price;
         var final_price = base_price;

         // If GST is to be added on top (excluding_gst)
         if (gst_status == 'excluding_gst') {
             var gst_amount = (base_price * gst_percent) / 100;
             final_price += gst_amount;
         }

         $('#selling_price').val(final_price.toFixed(3));
         $('#sell_price_show').text(final_price.toFixed(3));
         $('#taxable_amount').val(base_price.toFixed(3)); // Taxable amount is always before GST
         validateMRP();

         function validateMRP() {
             var selling_price = parseFloat($('#selling_price').val()) || 0;
             var mrp = parseFloat($('#item_mrp').val()) || 0;
             var landing_price = parseFloat($('#landing_price').val()) || 0;

             $('#discount_amount_show').text(mrp - selling_price);


             if (mrp && landing_price && selling_price > mrp) {
                 $(".save_btn").attr('disabled', true);
                 $("#mrp_inp").addClass('error_inp');
                 $("#landing_price").addClass('error_inp');
                 {{-- $(".landing_price_error").text('Landing Price cannot be greater than MRP'); --}}
                 $(".selling_error").text('Selling Price cannot be greater than MRP');
             } else {
                 $(".save_btn").removeAttr('disabled');
                 $("#mrp_inp").removeClass('error_inp');
                 $("#landing_price").removeClass('error_inp');
                 {{-- $(".landing_price_error").text(''); --}}
                 $(".selling_error").text('');
                 console.log('fd22')
             }
         }
     }
 </script>
