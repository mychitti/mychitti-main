@extends('layouts.vendor.app')
@section('title', 'Purchase Orders')
@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/inventory_purchase.css') }}" rel="stylesheet">
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content') 
 
    <div class="content container-fluid">
        @include('hmis::vendor-views.partials._pharmacy_header')
        <div class="pharmacy-page-content">
        @if (hasPermission('inventory_purchase_order', 'add'))
            <div class="">
                <div class="pox-wrap p-0">
                    <header class="pox-header">
                        <div class="pox-title">Purchase Order</div>
                        <div class="pox-sub">Create and manage purchase orders quickly</div>
                    </header>
                    <form action="{{ route('vendor.inventory.purchase.order-place') }}" method="post">
                        @csrf
                        <main class="pox-grid">
                            <section>
                                <div class="pox-card">
                                    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-start">
                                        <div style="flex:1;min-width:220px">
                                            <div class="pox-field">
                                                <label class="pox-label">Vendor</label>
                                                <div style="display:flex;gap:8px;    flex-wrap: wrap;">
                                                    <div>
                                                        <select name="bill_to" id="customer_id" required
                                                            data-placeholder="Select or Add New Vendor"
                                                            class="js-select2-custom ">
                                                            <option></option>
                                                            <option value="add_new">&#43; Add New Customer</option>
                                                            @foreach ($vendors as $cust)
                                                                <option data-type="{{ $cust->user_type }}"
                                                                    value="{{ $cust->id }}">
                                                                    {{ $cust->phone . ' | ' . $cust->f_name . ' | ' . ucfirst($cust->user_type) }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <button id="poxAddVendor" class=" btn_sm pox-btn pox-btn--alt modal_btn"
                                                        data-toggle="modal" data-target="#addCustomerModal">Add
                                                        Vendor</button>
                                                    <div class="btn-group btn-group-toggle m-0" style="margin: 2px auto;"
                                                        data-toggle="buttons">

                                                        <label style=" padding: 13px 14px;"
                                                            class="btn btn-responsive btn_sm btn-outline-primary ">
                                                            <input type="radio" class="tax_type" value="gst"
                                                                name="tax_type" id="option1"> GST
                                                        </label>

                                                        <label style=" padding: 13px 14px;"
                                                            class="btn btn-responsive btn_sm btn-outline-primary active">
                                                            <input type="radio" checked class="tax_type" value="non-gst"
                                                                name="tax_type" id="option3">
                                                            Non GST
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div style="min-width:200px">
                                            <div class="pox-field">
                                                <label class="pox-label">PO Number</label>
                                                <input value="{{ $data['po_number_gst'] }}" name="gst_po_number"
                                                    class="pox-input gst_elem" type="text" />
                                                <input value="{{ $data['po_number_nongst'] }}" name="non_gst_po_number"
                                                    class="pox-input non_gst_elem" type="text" />
                                            </div>
                                        </div>

                                        <div style="min-width:220px">
                                            <div class="pox-field">
                                                <label class="pox-label">Purchase Date</label>
                                                <input id="poxDate" name="purchase_date" class="pox-input"
                                                    type="date" />
                                            </div>
                                        </div>


                                    </div>

                                    <div style="margin-top:8px">
                                        <div class="table-responsive datatable-custom" id="table-div1">
                                            <table class="pox-table " id="datatable1">
                                                <thead style="background: #ededed;">
                                                    <tr>
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
                                    <div class="pox-sub">Quick Actions</div>


                                    <div class="pox-sline">
                                        <div class="pox-sub">Subtotal</div>
                                        <div id="poxSubtotal">$0.00</div>
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
                                        <div id="poxGrand" class="pox-grand">$0.00</div>
                                    </div>

                                    <div class="pox-actions">
                                        <button type="submit" id="poxSave" class="pox-btn"
                                            style="color:white !important;"><i class="tio-share-vs"></i>Save
                                            Purchase</button>
                                        <a href="" id="poxReset" class="pox-btn pox-btn--reset"><i
                                                class="tio-restore"></i>Reset Form</a>
                                        <button id="poxPrint" type="submit" class="pox-btn pox-btn--print"><i
                                                class="tio-document-text-outlined"></i>Print / Export PDF</button>
                                    </div>
                                </div>
                            </aside>
                        </main>
                    </form>
                </div>

            </div>
        @endif
        <!-- Page Heading -->
        @if (hasPermission('inventory_purchase_order', 'list'))

            <div class="row g-0 d-flex ">
                <div class="col-md-6 p-1">
                    <div class=" card mx-2 my-2">
                        <h1 class="page-header-title  p-3">
                            <span class="page-header-icon">
                                <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                            </span>
                            <span>
                                Low Stock Items
                                <span class="badge badge-soft-dark ml-2"
                                    id="itemCount">{{ count($low_stock_items) }}</span>
                            </span>
                        </h1>
                        <div class="table-responsive datatable-custom" id="table-div">
                            <table id="datatable"
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('sl') }}</th>
                                        <th class="border-0">Item</th>
                                        <th class="border-0">Stock</th>
                                        <th class="border-0">Action</th>
                                    </tr>
                                </thead>

                                <tbody id="set-rows">
                                    @foreach ($low_stock_items as $key => $order)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <div
                                                    style="width: 300px;text-align: start !important;white-space: normal;">

                                                    {{ $order->inventoryItem?->item_name }}
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-soft-danger rounded ml-1">
                                                    {{ $order->stock }}</span>
                                            </td>
                                            <td>
                                                <div class="btn--container justify-content-center">
                                                    @if (hasPermission('inventory_purchase_order', 'add'))
                                                        <a style="width:fit-content;padding : 0 5px !important;"
                                                            data-item-id="{{ $order->inventoryItem?->id }}"
                                                            data-item-gst="{{ $order->inventoryItem?->gst_rate }}"
                                                            data-item-price="{{ $order->inventoryItem?->selling_price }}"
                                                            data-item-name="{{ $order->inventoryItem?->item_name }}"
                                                            data-order-id="{{ $order->id }}"
                                                            class="add_btn add_btn_{{ $order->id }} btn action-btn btn--primary btn-outline-primary "
                                                            title="{{ translate('messages.delete_purchase_order') }}"><i
                                                                class="tio-add "></i>Add to Purchase Order
                                                        </a>
                                                        <span class="text-success added_item_{{ $order->id }}"
                                                            style="display:none;"><i class="tio-checkmark-circle"></i>
                                                            Added</span>
                                                    @endif
                                                    @if (hasPermission('inventory_purchase_order', 'delete'))
                                                        {{-- form-alert  --}}
                                                        <a class="btn action-btn btn--danger btn-outline-danger "
                                                            href="javascript:" data-id="vendor-{{ $order['id'] }}"
                                                            data-message="{{ translate('If you want to remove this purchase order?') }}"
                                                            title="{{ translate('messages.delete_purchase_order') }}"><i
                                                                class="tio-delete-outlined"></i>
                                                        </a>
                                                        <form action="" method="post"
                                                            id="vendor-{{ $order['id'] }}">
                                                            @csrf @method('post')
                                                        </form>
                                                    @endif

                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            @if (count($low_stock_items) === 0)
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
                <div class="col-md-6 p-1">
                    <div class=" card mx-2 my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="page-header-title  p-3">
                                <span class="page-header-icon">
                                    <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26"
                                        alt="">
                                </span>
                                <span>
                                    Purchase Orders
                                    <span class="badge badge-soft-dark ml-2"
                                        id="itemCount">{{ count($purchase_orders) }}</span>
                                </span>
                            </h1>
                            <div class="d-flex gap-1">
                                <a href="{{ route('vendor.inventory.purchase.export-orders') }}"
                                    class="mx-3 btn btn-primary">Export</a>
                                <form action="" class="date-range-form ">
                                    @include('vendor-views/form_modals/date_range')
                                     <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning"
                                type="button" data-toggle="modal"
                                data-target="#dateRangeModal">{{ translate($preset) }}</button>
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive datatable-custom" id="table-div">
                            <table id="datatable"
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('sl') }}</th>
                                        <th class="border-0">PO Number</th>
                                        <th class="border-0">Invoice Id</th>
                                        <th class="border-0">Total Amount</th>
                                        <th class="border-0">Purchase Date</th>
                                        <th class="border-0">Action</th>
                                    </tr>
                                </thead>

                                <tbody id="set-rows">
                                    @foreach ($purchase_orders as $key => $order)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <a
                                                    href="{{ $order->pdf ? asset('storage/app/public/purchase-order') . '/' . $order->pdf : 'javascript:;' }}">
                                                    {{ $order->po_number }}
                                                </a>
                                            </td>
                                            <td>
                                                {{ $order->invoice_id }}
                                            </td>
                                            <td>
                                                {{ _price($order->total_amount) }}
                                            </td>
                                            <td>
                                                {{ $order->purchase_date }}
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn p-1 dropdown-toggle" type="button"
                                                        data-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa-solid fa-bars"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item text-success"
                                                            href="{{ $order->pdf ? asset('storage/app/public/purchase-order') . '/' . $order->pdf : '#' }}"
                                                            title="{{ translate('messages.details') }}"></i>
                                                            <i class="tio-visible"></i> View PDF
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            @if (count($purchase_orders) === 0)
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

        @endif
        </div>
    </div>

@endsection
@push('script_2')
    @include('vendor-views/js/date_range')

    <script>
        $(".add_btn").on('click', function() {
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
                        <input class="pox-input pox-qty-input qty" value="1" name="qty[]" type="number" min="0"/></td>
                        <td class="pox-col--price">
                            <input class="pox-input  price" name="price[]" value="${itemPrice}" type="number" min="0" />
                        </td>
                        <td class="pox-col--tax gst_elem" ${style}>
                            <input class="pox-input  tax" name="gst[]" value="${itemGst}" type="number" min="0" />
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
    </script>
@endpush
