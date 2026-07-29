 <script>
     function checkPricing(elem = null) {
         let $elem = $(elem);

         // If selection changed
         if ($elem && ($elem.attr('id') === 'item_id_select' || $elem.attr('id') === 'variation_select')) {
             let item_id = ($elem.attr('id') === 'item_id_select') ? $elem.val() : $('#item_id_select').val();
             let vr_id = ($elem.attr('id') === 'variation_select') ? $elem.val() : null;

             if (!item_id) {
                 $(".item_error").text('Please select an item');
                 $('#landing_price, #selling_price, #taxable_amount').val('');
                 return false;
             }

             $(".item_error").text('');

             // Fetch fee details
             $.ajax({
                 url: "{{ route('vendor.inventory.get_my_fee_amount') }}",
                 type: "POST",
                 dataType: "json",
                 data: {
                     _token: $('[name="_token"]').val(),
                     item_id: item_id,
                     vr_id: vr_id,
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

         // Pricing calculation
         let my_fee = parseFloat($("#hidden_inp").val()) || 0;
         let fee_type = $("#fee_type").val() || 'amount';
         let gst_percent = parseFloat($("#gst_percent_hidden").val()) || 0;
         let gst_status = $('#price_gst_status').val(); // including_gst / excluding_gst
         let discount_val = parseFloat($('#discount_value_hidden').val()) || 0;
         let discount_type = $('#discount_type_hidden').val();
         let landing_price = parseFloat($("#landing_price").val()) || 0;

         // Fee calculation
         let base_price = landing_price;
         let final_price = base_price;

         // GST calculation
         if (gst_status === 'excluding_gst') {
             final_price += (base_price * gst_percent) / 100;
         }

         // Update fields
         $('#taxable_amount').val(base_price.toFixed(2));

         // MRP validation
         validateMRP();
     }

     function validateMRP() {
         let selling_price = parseFloat($('#selling_price').val()) || 0;
         let mrp = parseFloat($('#item_mrp').val()) || 0;
         let landing_price = parseFloat($('#landing_price').val()) || 0;

         $('#discount_amount_show').text(mrp - selling_price);

         if (mrp && landing_price && selling_price > mrp) {
             $(".save_btn").attr('disabled', true);
             $("#mrp_inp, #landing_price").addClass('error_inp');
             $(".selling_error").text('Selling Price cannot be greater than MRP');
         } else {
             $(".save_btn").removeAttr('disabled');
             $("#mrp_inp, #landing_price").removeClass('error_inp');
             $(".selling_error").text('');
         }
     }

     function fetch_variation_dets(vr_id, rowId) {
         var item_id = $(".item_select_" + rowId).val() || 0;
         console.log('itm id ', item_id);
         $.ajax({
             url: '{{ route('vendor.inventory.get_variation_details') }}',
             type: "POST",
             data: {
                 _token: $('[name="_token"]').val(),
                 item_id: item_id,
                 vr_id: vr_id,
             },
             success: function(resp) {
                 let variations = resp.variations || [];

                 if (variations.length) {
                     let selectedVar = null;
                     selectedVar = resp.selected_variation;

                     $('.variation_mrp_' + rowId).val(selectedVar.mrpprice);
                     $('.variation_purchase_' + rowId).val(selectedVar.purchaseprice);
                     $('.variation_sell_' + rowId).val(selectedVar.askingprice);

                 } else {
                     $('#item_mrp').val(resp.mrpprice);
                     $("#displayMrp").text(resp.mrpprice);
                     $("#displaySelling").text(resp.askingprice);
                 }
                 $(".item_details").html(resp.html);



             }
         });
     }

     function fetch_item_details(item_id, rowId) {
         // console.log('item_id', item_id)
         // console.log('rowid', rowId)
         $.ajax({
             url: '{{ route('vendor.inventory.get_item_details') }}',
             type: "POST",
             data: {
                 _token: $('[name="_token"]').val(),
                 item_id: item_id,
             },
             success: function(resp) {
                 let variations = resp.variations || [];
                 if (resp.has_variation) {
                     $(".var_inp_" + rowId).show();
                     $('.variation_select_' + rowId).empty();

                     variations.forEach(v => {
                         let text = `${v.type} - ₹${v.askingprice} (Stock: ${v.stock})`;
                         $('.variation_select_' + rowId).append(
                             new Option(text, v.type)
                         );
                     });
                     $('.variation_select_' + rowId).select2();
                     $('.variation_select_' + rowId).trigger('change');
                 }
                 $(".item_details").html(resp.html);

                 if (resp.item_type === 'service') {
                     $(".product_entry_inp").hide();
                     $(".product_label_inp").text('Price');
                 } else {
                     $(".product_entry_inp").show();
                     $(".product_label_inp").text('Purchase Price');
                 }
                 if (resp.secondary_unit) {
                     $('.secondary_quantity_' + rowId).attr('placeholder', resp.secondary_unit);
                     $('.quantity_' + rowId).attr('placeholder', resp.primary_unit);
                 }
             }
         });
     }
     $(document).on('keyup', '#mrp, #sellingPrice', function() {
         let selling_price = parseFloat($('#sellingPrice').val()) || 0;
         let mrp_price = parseFloat($('#mrp').val()) || 0;

         $("#displayMrp").text(mrp_price);
         $("#displaySelling").text(selling_price);

         let profit = (selling_price !== 0) ? (mrp_price - selling_price) : mrp_price;
         $("#displayProfit").text(profit);
         $("#margin").val(profit);
     });

     // trigger fetch when variation changes
     $(document).on('change', '#variation_select', function() {
         let item_id = $('#item_id_select').val();
         let vr_id = $(this).val();
         fetch_item_details(item_id, vr_id);
     });

     $(".item_type").on('change', function() {
         if ($(this).val() == 'service') {
             $(".product_inp_group").hide();
         } else {
             $(".product_inp_group").show();
         }
     })

     $(".next_btn").on("click", function() {
         var nextTab = $(this).data("next");
         $("#" + nextTab + "-tab").click();
     });

     function deleteInvEntRow(rowId) {
         $('[data-id="' + rowId + '"]').remove()
     }

     function addMoreRowInvEntry() {
         var $lastRow = $('.inv_entry_item_row').last();
         var newDataId = $lastRow.length ? Number($lastRow.data('id')) + 1 : 1;

         let newRow = `
            <tr class="inv_entry_item_row" data-id="${newDataId}">
                <td>
                    <select name="item_id[]"
                        class="form-control item_select item_select_${newDataId} js-select2-custom-class"
                        onchange="fetch_item_details(this.value, ${newDataId})">
                        <option value="">---Select---</option>
                        @foreach ($items as $item)
                            <option value="{{ $item['id'] }}">
                                {{ $item['item_name'] . ' | ' . ($item['company_sku_id'] ?? $item['sku_id']) . ' | ' . $item['model_number'] }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <div class="form-row var_inp_${newDataId}" style="display: none;">
                        <select name="variation_type[]"
                            class="variation_select variation_select_${newDataId} js-select2-custom"
                            data-placeholder="Select Variation"
                            onchange="fetch_variation_dets(this.value, ${newDataId})">
                            <option></option>
                        </select>
                    </div>
                </td>
               
                <td>
                    <input type="number" name="quantity[]" placeholder="Add Stock" class=" quantity_${newDataId} form-control">
                </td>
                <td>
                    <input type="number" placeholder="MRP" step="0.001"  class="variation_mrp_${newDataId} variation_mrp form-control">
                </td>
                <td>
                   <select name="storage_unit_id[]" 
                        class="form-control js-select2-custom storage_unit_${newDataId}">
                        <option value="">---{{ translate('messages.select') }}---
                        </option>
                        @php $storage_units = \App\Models\StorageUnit::with('parent')->where('store_id', \App\CentralLogics\Helpers::get_store_id())->get(); @endphp
                        @foreach ($storage_units as $unit)
                            <option value="{{ $unit['id'] }}">
                                {{ $unit->parent ? $unit->parent->name . ' > ' : '' }}{{ $unit['name'] . ' (' . $unit->type . ')' }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <div class="input-group ">
                        <select class="custom-select"  name="product_conditon[]"
                            class="form-control js-select2-custom">
                            <option value="new">New Product</option>
                            <option value="used">Used Product</option>
                        </select>
                    </div>
                </td>
                <td>
                    <div class="input-group">
                        <input type="number" required name="landing_price[]" placeholder="EX : 1000"
                            onkeyup="checkPricing(this)" class="variation_purchase_${newDataId} form-control" step="0.001">
                        <select class="custom-select" name="price_gst_status[]"
                            onchange="checkPricing(this)">
                            <option value="including_gst">Incl. GST</option>
                            <option value="excluding_gst">Excl. GST</option>
                        </select>
                        <small class="text-danger landing_price_error"></small>
                    </div>
                </td>
                <td>
                    <input type="number" name="selling_price[]" step="0.001" class="variation_sell_${newDataId}  variation_sell form-control">
                    <small class="text-danger selling_error"></small>
                </td>
                <td>
                    <button type="button" onclick="deleteInvEntRow(${newDataId})"
                        class="btn action-btn btn--danger btn-outline-danger item_row_remove_btn">
                        <i class="tio-delete-outlined"></i>
                    </button>
                </td>
            </tr>`;

         $('.rows_parent_inv_item').append(newRow);

         // reinit select2 for new selects
         $('.rows_parent_inv_item').find('.js-select2-custom, .js-select2-custom-class').select2();
     }
     $(document).on("input", "[name='selling_price']", function() {
         let $row = $(this).closest("tr");
         let mrp = parseFloat($row.find("input[class*='variation_mrp_']").val()) || 0;
         let sellingPrice = parseFloat($(this).val()) || 0;
         let $errorBox = $row.find(".selling_error");

         if (mrp && sellingPrice > mrp) {
             $(this).val(mrp); // set to max
             $errorBox.text("Selling Price cannot be greater than MRP");
             setTimeout(() => {
                 $errorBox.text("");
             }, 2000);
         } else {
             $errorBox.text("");
         }
     });

     function validateFileCount(input, maxFiles) {
         if (input.files.length > maxFiles) {
             alert(`You can only select up to ${maxFiles} files.`);
             input.value = ''; // Clear the selection
             return false;
         }
     }


     function selectRadio(value, element) {
         // Remove active class from all items
         document.querySelectorAll('.custom-radio-item').forEach(item => {
             item.classList.remove('active');
             item.querySelector('input[type="radio"]').checked = false;
         });

         // Add active to clicked one
         element.classList.add('active');
         element.querySelector('input[type="radio"]').checked = true;

         console.log("Selected:", value);
         if (value === 'service') {
             document.querySelectorAll('.product_elem').forEach(el => el.style.display = 'none');
             document.querySelectorAll('.service_elem').forEach(el => el.style.display = 'block');
         } else {
             document.querySelectorAll('.product_elem').forEach(el => el.style.display = 'block');
             document.querySelectorAll('.service_elem').forEach(el => el.style.display = 'none');
         }

     }
     document.getElementById('imageUpload').addEventListener('change', function(e) {
         const file = e.target.files[0];
         if (file) {
             const uploadArea = document.querySelector('.upload-area');
             uploadArea.innerHTML = `
                    <div class="upload-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h6 class="text-success mt-3">Image Uploaded Successfully</h6>
                    <p class="mb-2 text-muted">${file.name}</p>
                    <small class="text-muted">Click to change image</small>
                `;
         }
     });
     {{-- document.getElementById('imageUpload').addEventListener('change', function(e) {
         const files = e.target.files;

         if (files.length > 8) {
             alert("You can upload a maximum of 8 images.");
             e.target.value = ""; // reset input
             return;
         }

         if (files.length > 0) {
             const uploadArea = document.querySelector('.upload-area');

             let html = `
            <div class="upload-icon text-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <h6 class="text-success mt-3">${files.length} Image(s) Uploaded Successfully</h6>
            <ul class="list-unstyled">`;

             Array.from(files).forEach(file => {
                 html += `<li class="text-muted">${file.name}</li>`;
             });

             html += `</ul>
                 <small class="text-muted">Click to change images</small>`;

             uploadArea.innerHTML = html;
         }
     }); --}}
     $(".js-select2-custom-class").select2();

     $(document).ready(function() {
         $('#custom-buttons').on('click', 'button', function() {
             const label = $(this).data('label');
             let inputGroup = '';

             if (label === 'Other') {
                 inputGroup = `
        <div class="form-group custom-field" data-label="${label}">
            <div class="d-flex ">
                <input type="text" class="form-control" placeholder="Label" name="header_label[]">
                <input type="text" class="form-control" name="header_field[]">
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
                <input type="text" class="form-control" name="header_field[]" id="${inputName}">
                <a type="button" class="text-danger remove-field"><i class="tio-delete-outlined"></i></a>
            </div>
        </div>
        `;

                 // Hide the clicked button
                 $(this).hide();
             }

             $('#custom-fields').append(inputGroup);
         });

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
 </script>
 <script src="{{ asset('public/assets/admin') }}/js/tags-input.min.js"></script>
 <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
 <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
 <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.css" />
 <script type="importmap">
    {
        "imports": {
            "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.js",
            "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.0.0/"
        }
    }
</script>

 <script type="module">
     import {
         ClassicEditor,
         Essentials,
         Bold,
         Italic,
         Font,
         Paragraph,
         Table,
         TableToolbar
     } from 'ckeditor5';

     let editorInstance;

     ClassicEditor
         .create(document.querySelector('#maineditor'), {
             plugins: [Essentials, Bold, Italic, Font, Paragraph, Table, TableToolbar],
             toolbar: {
                 items: [
                     'undo', 'redo', '|', 'bold', 'italic', '|',
                     'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                     'insertTable', 'tableColumn', 'tableRow', 'mergeTableCells'
                 ]
             }
         })
         .then(editor => {
             editorInstance = editor;
         })
         .catch(error => {
             console.error('There was a problem initializing the editor.', error);
         });

     $('#item_form').on('submit', function(e) {
         $('#submitButton').attr('disabled', true);
         e.preventDefault();
         let formData = new FormData(this);
         if (editorInstance) {
             let encodedData = btoa(unescape(encodeURIComponent(editorInstance.getData())));
             formData.append('specifications', encodedData);
         }

         //variation editors 
         let editors2 = window.ckeditorInstances || {};
         document.querySelectorAll('.editor').forEach(editorElement => {
             if (editors2[editorElement.id]) {
                 let encodedData = btoa(unescape(encodeURIComponent(editors2[editorElement.id]
                     .getData())));
                 formData.append(editorElement.id, encodedData);
             }
         });
         //variation editors end

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
             }
         });
         $.post({
             url: $(this).attr('action'),
             data: $('#item_form').serialize(),
             data: formData,
             cache: false,
             contentType: false,
             processData: false,
             beforeSend: function() {
                 $('#loading').show();
             },
             success: function(data) {
                 $('#loading').hide();
                 if (data.errors) {
                     for (let i = 0; i < data.errors.length; i++) {
                         toastr.error(data.errors[i].message, {
                             CloseButton: true,
                             ProgressBar: true
                         });
                     }
                 }
                 if (data.status) {
                     toastr.success(data.msg, {
                         CloseButton: true,
                         ProgressBar: true
                     });

                     setTimeout(function() {
                         location.href = '{{ route('vendor.inventory.index') }}';
                     }, 1000);
                 }else{
                     toastr.error(data.msg, {
                         CloseButton: true,
                         ProgressBar: true
                     });
                 }
             }
         });
     });
 </script>
 <script>
     "use strict";


     $(document).ready(function() {
         $("#add_new_option_button").click(function(e) {
             $('#empty-variation').hide();
             count++;
             let add_option_view = `
                    <div class="__bg-F8F9FC-card view_new_option mb-2">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <label class="form-check form--check">
                                    <input id="options[` + count + `][required]" name="options[` + count + `][required]" class="form-check-input" type="checkbox">
                                    <span class="form-check-label">{{ translate('Required') }}</span>
                                </label>
                                <div>
                                    <button type="button" class="btn btn-danger btn-sm delete_input_button"
                                        title="{{ translate('Delete') }}">
                                        <i class="tio-add-to-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-xl-4 col-lg-6">
                                    <label for="">{{ translate('name') }}</label>
                                    <input required name=options[` + count +
                 `][name] class="form-control new_option_name" type="text" data-count="` +
                 count + `">
                                </div>

                                <div class="col-xl-4 col-lg-6">
                                    <div>
                                        <label class="input-label text-capitalize d-flex align-items-center"><span class="line--limit-1">{{ translate('messages.selcetion_type') }} </span>
                                        </label>
                                        <div class="resturant-type-group px-0">
                                            <label class="form-check form--check mr-2 mr-md-4">
                                                <input class="form-check-input show_min_max" data-count="` + count + `" type="radio" value="multi"
                                                name="options[` + count + `][type]" id="type` + count +
                 `" checked
                                                >
                                                <span class="form-check-label">
                                                    {{ translate('Multiple Selection') }}
                </span>
            </label>

            <label class="form-check form--check mr-2 mr-md-4">
                <input class="form-check-input hide_min_max" data-count="` + count + `" type="radio" value="single"
                    name="options[` + count + `][type]" id="type` + count +
                 `"
                                                >
                                                <span class="form-check-label">
                                                    {{ translate('Single Selection') }}
                </span>
            </label>
        </div>
    </div>
</div>
<div class="col-xl-4 col-lg-6">
    <div class="row g-2">
        <div class="col-6">
            <label for="">{{ translate('Min') }}</label>
                                            <input id="min_max1_` + count + `" required  name="options[` + count + `][min]" class="form-control" type="number" min="1">
                                        </div>
                                        <div class="col-6">
                                            <label for="">{{ translate('Max') }}</label>
                                            <input id="min_max2_` + count + `"   required name="options[` + count + `][max]" class="form-control" type="number" min="1">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="option_price_` + count + `" >
                                <div class="bg-white border rounded p-3 pb-0 mt-3">
                                    <div  id="option_price_view_` + count + `">
                                        <div class="row g-3 add_new_view_row_class mb-3">
                                            <div class="col-md-4 col-sm-6">
                                                <label for="">{{ translate('Option_name') }}</label>
                                                <input class="form-control" required type="text" name="options[` +
                 count +
                 `][values][0][label]" id="">
                                            </div>
                                            <div class="col-md-4 col-sm-6">
                                                <label for="">{{ translate('Additional_price') }}</label>
                                                <input class="form-control" required type="number" min="0" step="0.001" name="options[` +
                 count + `][values][0][optionPrice]" id="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3 p-3 mr-1 d-flex "  id="add_new_button_` + count +
                 `">
                                        <button type="button" class="btn btn--primary btn-outline-primary add_new_row_button" data-count="` +
                 count + `">{{ translate('Add_New_Option') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;

             $("#add_new_option").append(add_option_view);
         });

         // INITIALIZATION OF SELECT2
         // =======================================================
         $('.js-select2-custom').each(function() {
             let select2 = $.HSCore.components.HSSelect2.init($(this));
         });
     });

     function add_new_row_button(data) {
         count = data;
         countRow = 1 + $('#option_price_view_' + data).children('.add_new_view_row_class').length;
         let add_new_row_view = `
            <div class="row add_new_view_row_class mb-3 position-relative pt-3 pt-sm-0">
                <div class="col-md-4 col-sm-5">
                        <label for="">{{ translate('Option_name') }}</label>
                        <input class="form-control" required type="text" name="options[` + count + `][values][` +
             countRow + `][label]" id="">
                    </div>
                    <div class="col-md-4 col-sm-5">
                        <label for="">{{ translate('Additional_price') }}</label>
                        <input class="form-control"  required type="number" min="0" step="0.001" name="options[` +
             count +
             `][values][` + countRow + `][optionPrice]" id="">
                    </div>
                    <div class="col-sm-2 max-sm-absolute">
                        <label class="d-none d-sm-block">&nbsp;</label>
                        <div class="mt-1">
                            <button type="button" class="btn btn-danger btn-sm deleteRow"
                                title="{{ translate('Delete') }}">
                                <i class="tio-add-to-trash"></i>
                            </button>
                        </div>
                </div>
            </div>`;
         $('#option_price_view_' + data).append(add_new_row_view);

     }

     function add_more_customer_choice_option(i, name) {
         let n = name;

         $('#customer_choice_options').append(
             `<div class="__choos-item"><div><input type="hidden" name="choice_no[]" value="${i}"><input type="text" class="form-control d-none" name="choice[]" value="${n}" placeholder="{{ translate('messages.choice_title') }}" readonly> <label class="form-label">${n}</label> </div><div><input type="text" class="form-control combination_update" name="choice_options_${i}[]" placeholder="{{ translate('messages.enter_choice_values') }}" data-role="tagsinput"></div></div>`
         );
         $("input[data-role=tagsinput], select[multiple][data-role=tagsinput]").tagsinput();
     }

     function combination_update() {
         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
             }
         });

         $.ajax({
             type: "POST",
             url: '{{ route('vendor.inventory.item.variant-combination') }}',
             data: $('#item_form').serialize() + '&stock=1',
             beforeSend: function() {
                 $('#loading').show();
             },
             success: function(data) {
                 $('#loading').hide();
                 $('#variant_combination').html(data.view);
                 if (data.length < 1) {
                     $('input[name="current_stock"]').attr("readonly", false);
                 }
                 autofillVariationPrices();
             }
         });
     }

     // ── Auto-generate variation MRP & selling price from the main product price ──
     // e.g. main 1kg = ₹1000  →  variation "100g" = ₹100, "200g" = ₹200. The weight is
     // parsed from the variation label; vendor can still override any value manually.
     function _unitFactor(name) {
         name = (name || '').toString().trim().toLowerCase();
         const map = {
             mg: 0.001, ct: 0.2, carat: 0.2, g: 1, gm: 1, gms: 1, gram: 1, grams: 1,
             kg: 1000, kgs: 1000, kilogram: 1000, kilograms: 1000,
             ml: 1, milliliter: 1, l: 1000, ltr: 1000, litre: 1000, liter: 1000
         };
         return map[name] || null;
     }

     function autofillVariationPrices() {
         const mainSell = parseFloat($('[name="main_selling_price"]').val()) || 0;
         const mainMrp = parseFloat($('[name="main_mrp"]').val()) || 0;
         const mainLanding = parseFloat($('[name="main_landing_price"]').val()) || 0;
         const baseQty = parseFloat($('#primary_qty').val()) || 1;
         const secQty = parseFloat($('#secondary_qty').val()) || 1;
         const baseUnitName = $('#primary_unit option:selected').text() || '';
         const baseFactor = _unitFactor(baseUnitName);
         if (!baseFactor || baseQty <= 0 || (mainSell <= 0 && mainMrp <= 0 && mainLanding <= 0)) return;
         // "Prices are per" decides what the entered price covers: one base unit (400 per kg
         // → 1000), or one alternate unit (400 per bag → primary_qty/secondary_qty base units).
         // primary_qty on its own is the UOM conversion, not the quantity that was priced.
         const perAlternate = $('#selling_price_basis').val() === 'secondary';
         const baseCanonical = (perAlternate ? baseQty / secQty : 1) * baseFactor;

         $('#variant_combination input[name^="askingprice_"]').each(function () {
             const str = this.name.replace('askingprice_', '');
             const m = str.match(/(\d+(?:\.\d+)?)\s*(mg|kgs|kg|gms|gm|grams|gram|g|kilograms|kilogram|milliliter|ml|litre|liter|ltr|l)\b/i);
             if (!m) return;
             const vQty = parseFloat(m[1]);
             const vFactor = _unitFactor(m[2]);
             if (!vFactor || !vQty) return;
             const ratio = (vQty * vFactor) / baseCanonical;

             const key = window.CSS && CSS.escape ? CSS.escape(str) : str;
             const sellEl = this;
             const mrpEl = document.querySelector('[name="mrpprice_' + key + '"]');
             const buyEl = document.querySelector('[name="purchaseprice_' + key + '"]');
             if (mainSell > 0 && sellEl.dataset.auto !== '0') sellEl.value = +(mainSell * ratio).toFixed(2);
             if (mrpEl && mainMrp > 0 && mrpEl.dataset.auto !== '0') mrpEl.value = +(mainMrp * ratio).toFixed(2);
             if (buyEl && mainLanding > 0 && buyEl.dataset.auto !== '0') buyEl.value = +(mainLanding * ratio).toFixed(2);
         });
     }

     $(document).on('keyup change', '[name="main_selling_price"], [name="main_mrp"], [name="main_landing_price"], #primary_qty, #secondary_qty', autofillVariationPrices);
     $(document).on('change', '#primary_unit, #selling_price_basis', autofillVariationPrices);
     // Recompute when the Variations tab is opened (so prices fill even if the main price
     // was set after the variants were generated).
     $(document).on('shown.bs.tab', '#variations-tab', autofillVariationPrices);
     $(document).on('input', '#variant_combination input[name^="askingprice_"], #variant_combination input[name^="mrpprice_"], #variant_combination input[name^="purchaseprice_"]', function () {
         this.dataset.auto = '0';
     });
     $("#deliveryCalcInp").on('keyup', function() {
         $(this).attr('data-value', $(this).val());
         calculateFinalTotal();
     })
     $("#priceInp").on('keyup', function() {
         $('#priceCalcInp').val($(this).val())
         calculateFinalTotal();
     })
     $("#discount_type").on('change', function() {
         $('#discount_type2').val($(this).val()).trigger('change');
         calculateFinalTotal();
     })
     $("#discountInp").on('keyup', function() {
         $('#discountValueCalcInp').val($(this).val());
         calculateFinalTotal();
     })
     $('#priceCalcInp').on('keyup', function() {
         calculateFinalTotal();
     })
     $('#saleCommCalcInp').on('keyup', function() {
         calculateFinalTotal();
     })
     $('#deliveryCalcInp').on('keyup', function() {
         $(this).attr('data-value', $(this).val())
         calculateFinalTotal();
     })
     $('#discount_type2').on('change', function() {
         calculateFinalTotal();
     })
     $('#discountValueCalcInp').on('keyup', function() {
         calculateFinalTotal();
     })

     function calculateFinalTotal() {
         var finalAmount = 0;
         var discountType = $('#discount_type2').val() ?? 'percent';
         var discountValue = parseFloat($('#discountValueCalcInp').val()) ?? 0;
         var price = parseFloat($('#priceCalcInp').val());
         var discountAmount = 0;
         if (discountType === 'percent') {
             discountAmount = (price * discountValue) / 100;
         } else {
             discountAmount = discountValue;
         }
         var deliveryCharge = parseFloat($('#deliveryCalcInp').attr('data-value'));
         var deliveryChargeCommissionPercent = parseFloat($('#deliveryChargeCommision').val());
         var deliveryChargeCommission = (deliveryCharge * deliveryChargeCommissionPercent) / 100;
         var saleCommisionPercent = parseFloat($('#saleCommisionPercent').val());
         var saleCommission = ((price - discountAmount) * saleCommisionPercent) / 100

         finalAmount += price;
         finalAmount -= discountAmount;
         finalAmount += saleCommission;
         finalAmount += deliveryCharge;
         finalAmount += deliveryChargeCommission;
         $('#saleCommCalcInp').val(saleCommission)
         $("#finalCalcAmount").text(finalAmount.toFixed(2))
     }

     $(".item_images").on("change", function() {
         let files = this.files;

         if (files.length > 8) {
             toastr.error("You can only upload up to 8 images.");
             $(this).val("");
         }
     });

     $(".vr_stock").on('keyup', function() {
         var total_stock = 0;
         $(".vr_stock").each(function() {
             var stock = parseFloat($(this).val()) || 0;
             total_stock += stock;
         });
         $('#openingStock').val(total_stock);
     })

     $("#bill_form").on('submit', function(e) {
         {{-- e.preventDefault(); --}}
         var formData = new FormData(this);
         $.ajax({
             url: $(this).attr('action'),
             type: $(this).attr('method'),
             data: formData,
             contentType: false,
             processData: false,
             success: function(response) {
                 console.log(response.items);
                 response.items.forEach(item => {

                     if (item.item_id != null && item.item_id != '') {
                         addMoreRowInvEntry();
                         var $lastRow = $('.inv_entry_item_row').last();
                         var newDataId = $lastRow.length ? Number($lastRow.data('id')) : 1;
                         $lastRow.find('.item_select').val(item.item_id).trigger('change');

                         fetch_item_details(item.item_id, newDataId);
                     }
                     setTimeout(() => {
                         if (item.type != null) {
                             $lastRow.find('.variation_select').val(item.type)
                                 .trigger('change');
                         }
                         $lastRow.find('input[name="quantity[]"]').val(item
                             .quantity);
                         $lastRow.find('input[name="landing_price[]"]').val(item
                             .landing_price);
                         $lastRow.find('input[name="selling_price[]"]').val(item
                             .selling_price);
                         $lastRow.find(`select[name="price_gst_status[]"]`).val(item
                             .price_gst_status);
                     }, 500);
                 });
             },
             error: function(xhr, status, error) {
                 // Handle error
             }
         });
     })
 </script>
