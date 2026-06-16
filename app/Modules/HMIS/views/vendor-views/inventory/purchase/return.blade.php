@extends('layouts.vendor.app')
@section('title', 'Return Purchase')
@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/inventory_purchase.css') }}" rel="stylesheet">
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush 

@section('content')

    <div class="content container-fluid p-1">
        @include('hmis::vendor-views.partials._pharmacy_header')
        <div class="pharmacy-page-content">
            <div class="page-header">
                <div class="d-flex flex-wrap px-3 w-100">
                    <div class="d-flex w-100 flex-wrap justify-content-between align-items-center">
                        <h1 class="page-header-title d-flex align-items-center gap-2 mb-0">
                            <a href="javascript:history.back()" class="mr-2" style="color: inherit;" title="Back">
                                <i class="tio-chevron-left"></i>
                            </a>
                            Return Purchase
                        </h1>
                    </div>
                </div>
            </div>
            <!-- Page Heading -->
        @if (hasPermission('inventory_purchase_return', 'add'))
            <div class=" p-2">
                <div class="pox-wrap p-0">
                    <header class="pox-header">
                        <div class="pox-title">Return Purchase Slip</div>
                    </header>
                    <form action="{{ route('vendor.inventory.purchase.return-store') }}" method="post">
                        @csrf
                        <main class="pox-grid">
                            <section>
                                <div class="pox-card">
                                    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-start">
                                        <div style="flex:1;min-width:220px">
                                            <div class="pox-field">
                                                <label class="pox-label">Vendor</label>
                                                <div style="display:flex;gap:8px">
                                                    <div>
                                                        <select name="bill_to" id="customer_id" required
                                                            data-placeholder="Select or Add New Vendor"
                                                            class="js-select2-custom get_vendor">
                                                            <option></option>
                                                        </select>
                                                    </div>


                                                    {{-- <div class="btn-group btn-group-toggle m-0" style="margin: 2px auto;"
                                                    data-toggle="buttons">

                                                    <label style=" padding: 13px 14px;"
                                                        class="btn btn-responsive btn-outline-primary ">
                                                        <input type="radio" class="tax_type" value="gst"
                                                            name="tax_type" id="option1"> GST
                                                    </label>

                                                    <label style=" padding: 13px 14px;"
                                                        class="btn btn-responsive btn-outline-primary active">
                                                        <input type="radio" checked class="tax_type" value="non-gst"
                                                            name="tax_type" id="option3">
                                                        Non GST
                                                    </label>
                                                </div> --}}
                                                </div>
                                            </div>
                                        </div>

                                        <div style="max-width: 350px;">
                                            <label class="pox-label">Invoice Id</label>
                                            <select name="invoice_id[]" id="invoice_id" multiple
                                                data-placeholder="Select invoice id" class="js-select2-custom ">
                                                <option></option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="table-responsive datatable-custom" style="display:none;" id="table-div">
                                        <table id="datatable"
                                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th class="border-0">{{ translate('sl') }}</th>
                                                    <th class="border-0">Date</th>
                                                    <th class="border-0">Invoice Id</th>
                                                    <th class="border-0">Item</th>
                                                    <th class="border-0">Qty</th>
                                                    <th class="border-0">Unit Price</th>
                                                    <th class="border-0">Action</th>
                                                </tr>
                                            </thead>

                                            <tbody id="set-rows">
                                            </tbody>
                                        </table>

                                        <div class="empty--data_order">
                                            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                                alt="public">
                                            <h5>
                                                {{ translate('no_data_found') }}
                                            </h5>
                                        </div>
                                    </div>

                                    <div style="margin-top:8px">
                                        <div class="table-responsive datatable-custom" id="table-div1">

                                            <table class="pox-table" id="datatable1">
                                                <thead>
                                                    <tr style="background: #ededed;">
                                                        <th style="width:38px">Sl</th>
                                                        <th class="pox-col--name">Item Name</th>
                                                        <th class="pox-col--qty">Qty</th>
                                                        <th class="pox-col--price">Unit Price</th>
                                                        <th class="pox-col--tax gst_elem" style="display: none;">Tax (%)
                                                        </th>
                                                        <th class="pox-col--total">Total</th>
                                                        <th style="width:72px">Action</th>
                                                    </tr>
                                                </thead>

                                                <tbody id="poxItems"></tbody>
                                            </table>
                                        </div>

                                        <button id="poxAddRow" type="button" class="pox-addrow">+ Add Item</button>
                                        <div style="display:flex;justify-content:flex-end;padding:12px 6px;font-weight:800">
                                            Total
                                            Amount: <div id="poxTotal" style="margin-left:12px">0.00</div>
                                        </div>

                                        <div style="margin-top:16px">
                                            <label class="pox-label">Notes</label><br>
                                            <textarea id="poxNotes" name = "notes" class="pox-textarea" placeholder="Notes (optional)"></textarea>

                                        </div>
                                    </div>
                                </div>

                            </section>

                            <aside>
                                <div class="pox-card pox-side-summary">
                                    <div class="pox-sub">Summary</div>


                                    <div class="pox-sline">
                                        <div class="pox-sub">Subtotal</div>
                                        <div id="poxSubtotal">0.00</div>
                                    </div>

                                    <div class="pox-sline" style="align-items:center">
                                        <div class="pox-sub">Tax Amount</div>
                                        <div id="poxTaxTotal" style="display:flex;gap:8px;align-items:center">
                                            rs 5
                                            {{-- <button id="poxEditTax" class="pox-btn pox-btn--alt">Edit</button> --}}
                                        </div>
                                    </div>

                                    <div class="pox-sline">
                                        <div class="pox-sub">Grand Total</div>
                                        <div id="poxGrand" class="pox-grand">0.00</div>
                                    </div>

                                    <div class="pox-actions">
                                        <button type="submit" id="poxSave" class="pox-btn"
                                            style="color:white !important;"><i class="tio-document-text-outlined"></i>Return
                                            Purchase Slip</button>
                                        <a href="" id="poxReset" class="pox-btn pox-btn--reset"><i
                                                class="tio-restore"></i>Reset Form</a>
                                    </div>
                                </div>
                            </aside>
                        </main>
                    </form>
                </div>
            </div>
        @endif
        <!-- Page Heading -->
        <div class="row">
            @if (hasPermission('inventory_purchase_return', 'list'))
                <div class="col-md-6">
                    <div class="card mx-2 my-2 ">
                        <div class="d-flex justify-content-between align-items-center px-2 py-1 flex-wrap ">
                            <h1 class="page-header-title">
                                <span class="page-header-icon">
                                    <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26"
                                        alt="">
                                </span>
                                <span>
                                    Purchased Items
                                    <span class="badge badge-soft-dark ml-2"
                                        id="itemCount">{{ count($purchased_order_details) }}</span>
                                </span>
                            </h1>
                            <form action="" class="d-flex date-range-form ">
                                <button style="width:fit-content; white-space:nowrap"
                                    class="btn_sm btn btn-outline-warning" type="button" data-toggle="modal"
                                    data-target="#dateRangeModal">{{ translate($preset) }}</button>
                                {{-- date range modal --}}
                                @include('vendor-views/form_modals/date_range')
                            </form>
                        </div>

                        <div class="table-responsive datatable-custom" id="table-div">
                            <table id="datatable"
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('sl') }}</th>
                                        <th class="border-0">Item</th>
                                        <th class="border-0">Stock</th>
                                        <th class="border-0">Purchase At</th>
                                        <th class="border-0">Action</th>
                                    </tr>
                                </thead>

                                <tbody id="set-rows">
                                    @foreach ($purchased_order_details as $key => $detail)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <div
                                                    style="width: 300px;text-align: start !important;white-space: normal;">

                                                    {{ $detail->item?->item_name }}
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-soft-danger rounded ml-1">
                                                    {{ $detail->item?->stock }}</span>
                                            </td>
                                            <td>
                                                {{ $detail->created_at }}
                                            </td>
                                            <td>
                                                <div class="btn--container justify-content-center">

                                                    {{-- <a style="width:fit-content;padding : 0 5px !important;"
                                            data-item-id="{{ $detail->item?->id }}"
                                            data-item-gst="{{ $detail->item?->gst_rate }}"
                                            data-item-price="{{ $detail->item?->selling_price }}"
                                            data-item-name="{{ $detail->item?->item_name }}"
                                            data-order-id="{{ $detail->id }}"
                                            class="add_btn add_btn_{{ $detail->id }} btn action-btn btn--primary btn-outline-primary "
                                            title="{{ translate('messages.delete_purchase_order') }}"><i
                                                class="tio-add "></i>Add to Purchase Order
                                        </a>
                                        <span class="text-success added_item_{{ $detail->id }}"
                                            style="display:none;"><i class="tio-checkmark-circle"></i> Added</span>
                                        <a class="btn action-btn btn--danger btn-outline-danger " href="javascript:"
                                            data-id="vendor-{{ $detail['id'] }}"
                                            data-message="{{ translate('If you want to remove this purchase order?') }}"
                                            title="{{ translate('messages.delete_purchase_order') }}"><i
                                                class="tio-delete-outlined"></i>
                                        </a>
                                        <form action="" method="post" id="vendor-{{ $detail['id'] }}">
                                            @csrf @method('post')
                                        </form> --}}

                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            @if (count($purchased_order_details) === 0)
                                <div class="empty--data">
                                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                        alt="public">
                                    <h5>
                                        {{ translate('no_data_found') }}
                                    </h5>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>

            @endif
            <div class="col-md-6">
                <div class="card mx-2 my-2 ">
                    <div class="d-flex justify-content-between align-items-center px-2 py-1 flex-wrap ">
                        <h1 class="page-header-title">
                            <span class="page-header-icon">
                                <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26"
                                    alt="">
                            </span>
                            <span>
                                Return Slips
                                <span class="badge badge-soft-dark ml-2"
                                    id="itemCount">{{ count($purchased_order_details) }}</span>
                            </span>
                        </h1>
                    </div>

                    <div class="table-responsive datatable-custom" id="table-div">
                        <table id="datatable"
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">{{ translate('sl') }}</th>
                                    <th class="border-0">Invoices</th>
                                    <th class="border-0">Slip PDF</th>
                                    <th class="border-0">Created At</th>
                                    <th class="border-0">Action</th>
                                </tr>
                            </thead>

                            <tbody id="set-rows"> 
                                @foreach ($return_slips as $key => $slip)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div style="text-align: start !important;white-space: normal;">
                                                @if ($slip->invoice_ids && is_array(json_decode($slip->invoice_ids)))
                                                    @foreach (json_decode($slip->invoice_ids) as $key => $value)
                                                        <a
                                                            href="{{ asset('storage/app/public/invoice/') }}/{{ _manualInvoiceByInvoiceId($value)?->pdf }}">{{ $value }}</a>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <a
                                                href="{{ asset('storage/app/public/purchase-order/') }}/{{ $slip->pdf }}">View</a>
                                        </td>
                                        <td>
                                            {{ $slip->created_at }}
                                        </td>
                                        <td>
                                            <div class="btn--container justify-content-center">


                                                <a class="btn action-btn btn--danger btn-outline-danger "
                                                    href="javascript:" data-id="vendor-{{ $slip['id'] }}"
                                                    data-message="{{ translate('If you want to remove this purchase order?') }}"
                                                    title="{{ translate('messages.delete_purchase_order') }}"><i
                                                        class="tio-delete-outlined"></i>
                                                </a>
                                                <form action="" method="post" id="vendor-{{ $slip['id'] }}">
                                                    @csrf @method('post')
                                                </form>

                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @if (count($return_slips) === 0)
                            <div class="empty--data">
                                <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                    alt="public">
                                <h5>
                                    {{ translate('no_data_found') }}
                                </h5>
                            </div>
                        @endif
                    </div>

                </div>
            </div>

        </div> 
    </div>
</div>

@endsection
@push('script_2')
    <script>
        $(document).on('click', ".add_btn", function() {
            let itemName = $(this).attr('data-item-name')
            let orderId = $(this).attr('data-order-id')

            $(".add_btn_" + orderId).hide();
            $(".added_item_" + orderId).show();

            addItemToForm(orderId, this);
            calculateTotals();

        })
        $("#poxAddRow").on('click', function() {
            addItemToForm(null, null)
        })

        function addItemToForm(orderId, elem) {

            let itemName = $(elem).attr('data-item-name') || '';
            let itemGst = $(elem).attr('data-item-gst') || '';
            let itemId = $(elem).attr('data-item-id') || '';
            let itemPrice = $(elem).attr('data-item-price') || 0;
            let itemStock = $(elem).attr('data-item-stock') || 0;

            let dataId = 1;

            if ($(".added_row").length) {
                dataId = parseInt($(".added_row").last().data("id")) + 1;
            }
            let gst_status = $(".tax_type:checked").val();
            let style = "";

            if (gst_status === "non-gst") {
                style = 'style="display:none;"';
            } else {
                style = 'style="display:block;"';
            }
            if (!itemName) {
                itemName = `<input class="pox-input"  name="item_name[]" type="text" placeholder="Item Name"/>`;
            } else {
                itemName = `<input  name="item_name[]" value="` + itemName + `" type="hidden" />` + itemName;
            }

            let html = `<tr class="added_row" data-id="` + dataId + `">
                        <td>${dataId}</td>
                        <td class="pox-col--name">` + itemName + `</td>
                        <td class="pox-col--qty">
                        <input type="hidden" name="order_id[]" class="order_id_${dataId}" value="${orderId}">
                        <input type="hidden" name="item_id[]" class="item_id_${dataId}" value="${itemId}">
                        <input class="pox-input pox-qty-input qty" value="${itemStock}" name="qty[]" type="number" min="0"/></td>
                        <td class="pox-col--price">
                            <input class="pox-input  price" name="price[]" value="${itemPrice}" type="number" min="0" />
                        </td>
                     
                        <td class="pox-col--total amount total_amount_${dataId}">${itemPrice}</td>
                        <td><button class="pox-trash" data-id="${dataId}">🗑</button></td>
                    </tr>`;
            $("#poxItems").append(html)

        }
        $(document).on('keyup input change', '.price, .qty, .tax, .tax_type', function() {
            calculateTotals();
        });
        $(document).ready(function() {
            calculateTotals();
        });

        function calculateTotals() {
            let totalWithoutGST = 0;
            let totalWithGST = 0;
            let totalGstAmount = 0;
            let gst_status = $(".tax_type:checked").val();
            let gstAmount = 0;


            $('.added_row').each(function() {
                let price = parseFloat($(this).find('.price').val()) || 0;
                let qty = parseFloat($(this).find('.qty').val()) || 0;
                let tax = parseFloat($(this).find('.tax').val()) || 0;

                let lineTotal = price * qty;
                if (gst_status == 'gst') {
                    gstAmount = lineTotal * (tax / 100);
                }
                let lineTotalWithGST = lineTotal + gstAmount;

                totalWithoutGST += lineTotal;
                totalWithGST += lineTotalWithGST;
                totalGstAmount += gstAmount;

                // $(this).find('.item_taxable').val(lineTotal)
                $(this).find('.amount').text(lineTotalWithGST)
            });

            $('#poxSubtotal').text(totalWithoutGST.toFixed(3));
            $('#poxGrand').text(totalWithGST.toFixed(3));
            $('#poxTotal').text(totalWithGST.toFixed(3));
            $('#poxTaxTotal').text(totalGstAmount.toFixed(3));
        }
        $(document).on('click', ".pox-trash", function() {
            let id = $(this).attr('data-id')
            if ($(".order_id_" + id).val()) {
                let orderId = $(".order_id_" + id).val()
                $(".add_btn_" + orderId).show()
                $(".added_item_" + orderId).hide()
            }
            $(".added_row[data-id='" + id + "']").remove();
            calculateTotals();
        })

        $(".tax_type").on("change", function() {
            let gst_status = $(".tax_type:checked").val();
            console.log(gst_status)
            if (gst_status == 'non-gst') {
                $('.gst_elem').hide();
                $('.non_gst_elem').show();
            } else {
                $('.gst_elem').show();
                $('.non_gst_elem').hide();
            }
        })
        $(document).ready(function() {
            $('.modal_btn').on('click', function() {
                console.log("fsasfasdfd")
                $('#add_user_type').val('vendor');
                $(".user_typ_text").text('Vendor')
            });
        });

        $("#customer_id").on('change', function() {
            let vendor_id = $(this).val();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: "{{ route('vendor.invoice.get-invoices-by-vendor') }}",
                type: 'POST',
                data: {
                    vendor_id: vendor_id
                },
                success: function(data) {
                    console.log(data.invoices)
                    if (data.status) {
                        let $select = $("#invoice_id");

                        $select.empty();

                        $select.append('<option ></option>');

                        $.each(data.invoices, function(index, invoice) {
                            $select.append(
                                $('<option>', {
                                    value: invoice,
                                    text: invoice
                                })
                            );
                        });

                        $select.select2();
                    }
                },
            });
        })

        $('#invoice_id').on('change', function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: "{{ route('vendor.inventory.purchase.items-in-invoice') }}",
                data: {
                    invoice_ids: $(this).val()
                },
                type: 'post',
                success: function(response) {
                    console.table(response)
                    console.table(response.data)
                    $("#table-div").show()
                    let html = response.data;
                    $("#set-rows").html(html)
                    if (html != '') {
                        $(".empty--data_order").hide()
                    } else {
                        $(".empty--data").show()

                    }
                }
            });
        })
    </script>
    @include('vendor-views/js/date_range')
@endpush
