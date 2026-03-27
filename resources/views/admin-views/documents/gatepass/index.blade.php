@extends('layouts.admin.app')

@section('title', ucfirst($gp_type) . ' Gatepass')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .driver_field,
        .salesman_field
         {
            display: none;
        }

        .table td {
            padding: 5px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Generate {{ ucfirst($gp_type) }} Gatepass </h1>
        </div>
        <!-- End Page Header -->

        <div class="">
            <form action="{{ route('admin.library.gatepass.store') }}" method="post">
                @csrf
                <input type="hidden" name="type" value="{{ $gp_type }}">

                <div class="row col-12">

                    <div class="col-md-2 px-1">
                        <label for="">Generate</label>
                        <div class="pos--payment-options mt-3 mb-3">
                            <ul>
                                <li>
                                    <label>
                                        <input type="radio" name="invoice" value="1" hidden checked
                                            class="invoice_av">
                                        <span>For Invoice</span>
                                    </label>
                                </li>
                                <li>
                                    <label>
                                        <input type="radio" name="invoice" value="0" hidden class="invoice_av">
                                        <span>Individual</span>
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-2 px-1 invoice_field">
                        <label for="">Invoice Id</label>
                        <input placeholder="Invoice Id" type="text" name="invoice_id" id=""
                            class="form-control">
                    </div>

                    <div class="col-md-2 px-1">
                        <label for="">Driver</label>
                        <select name="staff_id" data-placeholder="Select Staff" class="js-select2-custom" id="staff_select">
                            <option value=""></option>
                            <option value="add_new">+ Add New</option>
                            @foreach ($staff as $key => $value)
                                <option value="{{ $value->id }}">
                                    {{ $value->f_name . ' ' . $value->l_name . ' #' . $value->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 px-1">
                        <label for="">Salesman</label>
                        <select name="salesman_id" data-placeholder="Select Staff" class="js-select2-custom"
                            id="salesman_select">
                            <option value=""></option>
                            <option value="add_new">+ Add New</option>
                            @foreach ($staff as $key => $value)
                                <option value="{{ $value->id }}">
                                    {{ $value->f_name . ' ' . $value->l_name . ' #' . $value->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 px-1">
                        <label for="">Route</label>
                        <select name="route" data-placeholder="Type or Select" class="js-select2-custom-tags"
                            id="route">
                            <option value=""></option>
                            @foreach ($routes as $key => $value)
                                <option value="{{ $value }}">
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 px-1">
                        <label for="">Vehicle No.</label>
                        <select name="vehicle_number" data-placeholder="Type or Select" class="js-select2-custom-tags"
                            id="vehicle_number">
                            <option value=""></option>
                            @foreach ($vehicles as $key => $value)
                                <option value="{{ $value }}">
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 row mt-3" >


                        <div class="col-md-6 driver_field px-3 bg-light ">
                        <h4>Driver Details</h4>
                            <div class="row p-3  rounded" style="background: #c0ffd9ff">
                                <div class="col-md-4 px-1 ">
                                    <label for=""> Driver Name</label>
                                    <input placeholder="Name" type="text" name="name" id=""
                                        class="form-control">
                                </div>
                                <div class="col-md-4 px-1 ">
                                    <label for=""> Driver Phone</label>
                                    <input placeholder="Phone" type="text" name="phone" id=""
                                        class="form-control">
                                </div>
                                <div class="col-md-4  px-1 ">
                                    <label for=""> Driver Address</label>
                                    <input placeholder="Address" type="text" name="address" id=""
                                        class="form-control">
                                </div>
                            </div>

                        </div>
                        <div class="col-md-6 salesman_field px-3 bg-light">
                        <h4>Salesman Details</h4>

                            <div class="row p-3  rounded" style="background: #fff0c0ff">
                                <div class="col-md-4 px-1 ">
                                    <label for=""> Salesman Name</label>
                                    <input placeholder="Name" type="text" name="salesman_name" id=""
                                        class="form-control">
                                </div>
                                <div class="col-md-4 px-1 ">
                                    <label for=""> Salesman Phone</label>
                                    <input placeholder="Phone" type="text" name="salesman_phone" id=""
                                        class="form-control">
                                </div>
                                {{-- <div class="col-md-4  px-1 ">
                                    <label for=""> Salesman Address</label>
                                    <input placeholder="Address" type="text" name="salesman_address" id=""
                                        class="form-control">
                                </div> --}}
                            </div>
                        </div>

                    </div>
                    <div class="col-md-8 items_group mt-3 " style="background: #ebfffc;padding: 15px; display:none;">
                    <h4>Item Details</h4>
                        <button type="button" class="btn btn-dark btn-sm" onclick="addMoreRow()">+ Add
                            Item</button>
                        <button type="button" class="btn btn-dark btn-sm" data-toggle="modal"
                            data-target="#inventoryItemModal">+ Add From Inventory</button>
                        <table class="table">
                            <thead class="" style=" background: #75b8b8; color: white;">
                                <tr>
                                    <th scope="col">Item</th>
                                    <th scope="col">Price</th>
                                    <th scope="col">Qty</th>
                                    <th scope="col">Unit</th>
                                    <th class=" " scope="col">Total</th>
                                    <th scope="col"></th>
                                </tr>
                            </thead>
                            <tbody class="rows_parent">

                                <tr class="item_row_inv" data-id="1">

                                    <td><input type="text" name="item_name[]" placeholder="Item Name"
                                            class="form-control"></td>
                                    <td style="width: 100px;"><input type="number" step="0.001" name="item_price[]"
                                            placeholder="Price" class="form-control price"></td>
                                    <td style="width: 58px;"><input type="number" name="item_qty[]" value="1"
                                            placeholder="Quantity" class="form-control qty"></td>
                                    <td style="width:140px;"><select name="item_unit[]" id=""
                                            class="form-control js-select2-custom">
                                            <option value="">-- Unit --</option>
                                            @foreach (\App\Models\Unit::all() as $unit)
                                                <option value="{{ $unit->id }}">{{ $unit->unit }}</option>
                                            @endforeach
                                        </select></td>

                                    <td style="width: 93px;" class=""><input type="text" readonly
                                            placeholder="Total" class="form-control item_total"></td>
                                    <td><button type="button" onclick="deleteNewRow(1)"
                                            class="btn action-btn btn--danger btn-outline-danger"><i
                                                class="tio-delete-outlined"></i></button></td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="d-flex w-100 justify-content-end">
                    <button class="btn btn-primary my-2">Generate</button>
                </div>
            </form>
        </div>
    </div>
    @include('vendor-views.form_modals.inventory_item_select')

@endsection
@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>

    <script>
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
        }
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


            var html = `<tr class="item_row_inv" data-id="` + dataId + `"
         data-secondary-unit="${item?.secondary_unit ?? ''}"
    data-primary-qty="${item?.primary_qty ?? 0}"
    data-secondary-qty="${item?.secondary_qty ?? 0}"
    data-primary-price="${item?.selling_price ?? 0}">

                       <input type="hidden" name="inventory_item_id[]" value="` + item_id + `" >
                       <input type="hidden" name="invoice_item[]" value="1" >
                      <td><input type="text" name="item_name[]" value="` + item_name + `" placeholder="Item Name" class="form-control"></td>
                      <td style="width: 100px;"><input type="number" value="` + item_price + `" step="0.001" name="item_price[]" placeholder="Price" class="form-control price"></td>
                      <td style="width: 58px;"><input type="number"  name="item_qty[]" value="1" placeholder="Qunatity" class="form-control qty"></td>

                       <td style="width:140px;"><select name="item_unit[]" class="form-control js-select2-custom unit">
            ${buildUnitOptions(item)}
         </select>
         </td>
                     
                        <td style="width: 93px;" class=""><input type="text"  readonly placeholder="Total" class="form-control item_total"></td>
                       <td><button type="button"  onclick="deleteNewRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>`;

            $('.rows_parent').append(html)
            calculateTotals()

        }

        function deleteNewRow(rowId) {
            $('[data-id="' + rowId + '"]').remove()
        }

        $('.invoice_av').on('change', function() {
            var is_invoice = $(this).val();
            console.log(is_invoice)
            if (is_invoice == 1) {
                $(".invoice_field").show()
                $(".items_group").hide()
            } else {
                $(".invoice_field").hide()
                $(".items_group").show()
            }
        })
        $('#staff_select').on('change', function() {
            if ($(this).val() == 'add_new') {
                $(".driver_field").show()
            } else {
                $(".driver_field").hide()
            }
        })
        $('#salesman_select').on('change', function() {
            if ($(this).val() == 'add_new') {
                $(".salesman_field").show()
            } else {
                $(".salesman_field").hide()
            }
        })
    </script>
@endpush
