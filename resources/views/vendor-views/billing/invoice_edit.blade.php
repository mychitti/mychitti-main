@extends('layouts.vendor.app')

@section('title', 'Edit Bill')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (\App\CentralLogics\Helpers::get_store_data()->module_id == 6)
        <style>
            .hidden_hsn {
                display: none;
            }
        </style>
    @endif
    <style>
        .upgrade-card {
            background-color: #f0f4ff;
            color: #333;
            border-radius: 12px;
            padding: 15px;
            width: 320px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        .upgrade-card h4 {
            margin-top: 0;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 18px;
        }

        .upgrade-card p {
            font-size: 14px;
            margin-bottom: 16px;
            line-height: 1.4;
        }

        .upgrade-card .btn {
            background-color: #5a75f8;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
        }

        .upgrade-card .btn:hover {
            background-color: #4a65e0;
        }

        .upgrade-card .close-btn {
            position: absolute;
            top: 14px;
            right: 16px;
            font-size: 16px;
            color: #777;
            cursor: pointer;
        }

        .upgrade-card .close-btn:hover {
            color: #333;
        }
        .select2-results__option:last-child {
            color: rgb(90, 123, 186) !important;
            font-weight: bold;
        }

        .custom-input {
            padding-left: 0;
            border: 1px solid #e8e6e6;
            box-shadow: none;
            border-left: none;
        }

        .custom-input:focus {
            box-shadow: none;
            border: 1px solid #ececec;
            outline: none;
            border-left: none;
        }

        #totalWithoutGST,
        #totalWithGST,
        .currency {
            font-size: 18px;
            color: black;
        }

        .item_row_inv td {
            padding: 2px !important;
        }

        .form-row {
            margin-top: 6px;
        }

        .hidden_tax {
            display: none;
        }

        @media (max-width: 768px) {
            table {
                display: block;
                /* Make table block */
                border: none;
            }

            thead {
                display: none;
                /* Hide headers */
            }

            tbody tr {
                display: block;
                margin-bottom: 10px;
                border: 1px solid #ddd;
                /* Add border around cards */
                padding: 10px;
            }

            tbody td {
                display: flex;
                justify-content: space-between;
                padding: 5px 10px;
            }

            tbody td::before {
                content: attr(data-label);
                /* Use data-label for headings */
                font-weight: bold;
                flex: 1;
            }

            td {
                flex: 2;
            }
        }

        .table th {
            padding: 5px !important;
        }

        #toast {
            visibility: hidden;
            min-width: 250px;
            margin-left: -125px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 16px;
            position: fixed;
            z-index: 1111;
            left: 50%;
            bottom: 30px;
            font-size: 17px;
            opacity: 0;
            transition: opacity 0.5s, bottom 0.5s;
        }

        #toast.show {
            visibility: visible;
            opacity: 1;
            bottom: 50px;
        }
    </style>
    <style>
        .custom-input {
            padding-left: 0;
            border: 1px solid #e8e6e6;
            box-shadow: none;
            border-left: none;
        }

        .custom-input:focus {
            box-shadow: none;
            border: 1px solid #ececec;
            outline: none;
            border-left: none;
        }

        #totalWithoutGST,
        #totalWithGST,
        .currency {
            font-size: 18px;
            color: black;
        }

        .item_row_inv td {
            padding: 2px !important;
        }

        .item_row_quote td {
            padding: 2px !important;
        }

        .form-row {
            margin-top: 6px;
        }

        .hidden_tax {
            display: none;
        }

        @media (max-width: 768px) {
            table {
                display: block;
                /* Make table block */
                border: none;
            }

            thead {
                display: none;
                /* Hide headers */
            }

            tbody tr {
                display: block;
                margin-bottom: 10px;
                border: 1px solid #ddd;
                /* Add border around cards */
                padding: 10px;
            }

            tbody td {
                display: flex;
                justify-content: space-between;
                padding: 5px 10px;
            }

            tbody td::before {
                content: attr(data-label);
                /* Use data-label for headings */
                font-weight: bold;
                flex: 1;
            }

            td {
                flex: 2;
            }
        }

        .table th {
            padding: 5px !important;
        }

        #toast {
            visibility: hidden;
            min-width: 250px;
            margin-left: -125px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 16px;
            position: fixed;
            z-index: 1111;
            left: 50%;
            bottom: 30px;
            font-size: 17px;
            opacity: 0;
            transition: opacity 0.5s, bottom 0.5s;
        }

        #toast.show {
            visibility: visible;
            opacity: 1;
            bottom: 50px;
        }
    </style>
@endpush

@section('content')
    <div id="toast" class="toast">This is a toaster notification!</div>

    {{-- @include('vendor-views/sub-module/partials/billing') --}}

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Edit Bill</h1>
            <div class="page-header-select-wrapper">
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row g-2">
            <form class="w-100 row g-0 invoice-form calc-form" action="{{ route('vendor.invoice.update-invoice') }}"
                method="post">
                @csrf
                <div class="col-md-12 p-1">
                    <div class="card h-100">
                        <div class="card-body">
                            <input type="hidden" id="service_id" name="service_id" value="">
                            <input type="hidden" name="invoice_id" value="{{ $invoice->invoice_id }}">

                            <!-- Row 1: Invoice Details -->
                            <div class="row  mb-2">

                                @if (isset($invoice) && $invoice->bill_to)
                                    <div class="col-md-3 p-1">

                                    <input type="hidden" name="bill_to" value="{{ $invoice->bill_to }}"> 
                                        <label class="form-check-label d-flex mb-1" for="flexRadioDefault2">Bill to</label>

                                    <div class="upgrade-card cust_det_card" >
                                        <div class="customer_info">
                                            <h6>{{$invoice->storeCustomer?->f_name .' '.$invoice->storeCustomer?->l_name}}</h6>
                                            <p class="mb-0" style="font-size:12px">{{$invoice->storeCustomer?->phone}}</p>
                                            <p class="mb-0" style="font-size:12px">{{$invoice->storeCustomer?->email}}</p>
                                        </div>
                                    </div>
                                    </div>
                                @else
                                    <div class="col-md-2 p-1">
                                        <label class="form-check-label d-flex mb-1" for="flexRadioDefault2">Bill to</label>
                                        <div id="customer_id_elem">
                                            <select name="bill_to" id="customer_id"
                                                class="form-control form-control-sm js-select2-custom">
                                                <option value=""></option>
                                                @if (!isset($task))
                                                    <option value="add_new">&#43; Add New Customer</option>
                                                @endif
                                            </select>
                                            <span class="text-success user_type_show"></span>
                                        </div>
                                    </div>
                                @endif

                                <div class="col-md-2 p-1">
                                    <label class="form-check-label d-flex mb-1" for="flexRadioDefault2">Invoice Date</label>
                                    <div>
                                        @php
                                            $today = date('Y-m-d');
                                            $startOfFinancialYear = date('Y') -1  . '-04-01';
                                        @endphp
                                        <input type="date" name="invoice_date" class="form-control form-control-sm"
                                            min="{{ $startOfFinancialYear }}" value="{{ $today }}">
                                    </div>
                                </div>


                                <div class="col-md-2 p-1">
                                    <label class="d-block mb-1 small">Tax Type</label>
                                    <div class="d-flex d-flex border rounded" style="padding: 11px;">
                                        @if (App\CentralLogics\Helpers::get_store_data()->gst &&
                                                json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                                            <div class="form-check mr-3 form-check-inline">
                                                <input class="form-check-input tax_type" value="gst" name="tax_type"
                                                    type="radio" id="gstRadio1">
                                                <label class="form-check-label small" for="gstRadio1">GST</label>
                                            </div>
                                        @endif
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input tax_type" value="non-gst" name="tax_type"
                                                type="radio" id="gstRadio2" checked>
                                            <label class="form-check-label small" for="gstRadio2">Non GST</label>
                                        </div>
                                    </div>

                                </div>


                            </div>

                            <!-- Row 2: Payment Method and Dates -->
                            <div class="row">
                                <div
                                    class="@if (Route::currentRouteName() === 'vendor.task.detail') 'col-md-3' @else 'col-md-2' @endif p-1 payment_mode_grp">
                                    <div class="pos--payment-options">
                                        <label class="">{{ translate('paid_By') }}</label>
                                        <ul class="mb-0">
                                            <li>
                                                <label>
                                                    <input type="radio" class="payment_mode" name="payment_mode"
                                                        value="Cash" hidden checked>
                                                    <span>Cash</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label>
                                                    <input type="radio" class="payment_mode" name="payment_mode"
                                                        value="Online" hidden>
                                                    <span>Online</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label>
                                                    <input type="radio" class="payment_mode" name="payment_mode"
                                                        value="Cash and Online" hidden>
                                                    <span>Both</span>
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-2 p-1 partial_payment" style="display: none">
                                    <label class="form-check-label mb-1" for="flexRadioDefault2">Cash Amount</label>
                                    <input class="form-control form-control-sm cash_amount" name="cash_amount"
                                        type="number" step="0.001" placeholder="Ex: 2000" name="flexRadioDefault"
                                        id="flexRadioDefault2">
                                </div>
                                <div class="col-md-2 p-1 partial_payment" style="display: none">
                                    <label class="form-check-label mb-1" for="flexRadioDefault2">Online Amount</label>
                                    <input class="form-control form-control-sm online_amount" name="online_amount"
                                        type="number" step="0.001" placeholder="Ex: 3000" name="flexRadioDefault"
                                        id="flexRadioDefault2">
                                </div>

                                <div class="payment_date_inp col-md-2 p-1" style="display:none;">
                                    <label class="form-check-label mb-1" for="flexRadioDefault2">Payment Date</label>
                                    <input class="form-control form-control-sm" min="{{ date('Y-m-d') }}"
                                        name="payment_date" type="date" name="flexRadioDefault"
                                        id="flexRadioDefault2">
                                </div>

                                <div class="reminder_date_inp col-md-2 p-1" style="display:none;">
                                    <label class="form-check-label mb-1" for="flexRadioDefault2">Reminder Start
                                        Date</label>
                                    <input class="form-control form-control-sm" value="{{ date('Y-m-d') }}"
                                        min="{{ date('Y-m-d') }}" name="reminder_date" type="date"
                                        name="flexRadioDefault" id="flexRadioDefault2">
                                </div>

                                <div class="reminder_date_inp col-md-2 p-1" style="display:none;">
                                    <label class="form-check-label mb-1" for="flexRadioDefault2">Reminder
                                        Frequency</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" value="1" name="reminder_freq"
                                            class="form-control form-control-sm" id="inputGroupFile04"
                                            aria-describedby="inputGroupFileAddon04" aria-label="Upload">
                                        <select name="reminder_freq_unit" class="form-control form-control-sm">
                                            <option value="week">Week</option>
                                            <option value="day">Day</option>
                                            <option value="hour">Hour</option>
                                            <option value="month">Month</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-9 p-1">
                    <div class="card h-100">
                        <div class="card-body p-1">
                            <button type="button" class="btn btn-dark btn-sm" onclick="addMoreRow(null, 'inv')">+ Add
                                Item</button>
                            @if (_isSubscription() && Route::currentRouteName() === 'vendor.invoice.manual-bill')
                                <button type="button" class="btn btn-dark btn-sm" data-toggle="modal"
                                    data-target="#inventoryItemModal">+ Add From Inventory</button>
                            @endif
                            <table class="table">
                                <thead class="" style=" background: #75b8b8; color: white;">
                                    <tr>
                                        <th scope="col">Item</th>
                                        <th scope="col">Price</th>
                                        <th scope="col">Qty</th>
                                        <th scope="col">Unit</th>
                                        <th class="tax_inp_data hidden_tax" scope="col">Tax <i>(in %)</i></th>
                                        <th class="hsn_inp hidden_hsn" scope="col">HSN</th>
                                        <th class="hidden_tax" scope="col">Taxable</th>
                                        <th class=" " scope="col">Total</th>
                                        <th scope="col"></th>
                                    </tr>
                                </thead>
                                <tbody class="rows_parent">
                                    @foreach ($invoice_items as $item)
                                        <tr class="item_row_inv" data-id="{{ $item->id }}">
                                            <td><input type="text" value="{{ $item->name }}" name="item_name[]"
                                                    placeholder="Item Name" class="form-control"></td>

                                            <td style="width: 100px;"><input type="number" value="{{ $item->price }}"
                                                    name="item_price[]" placeholder="Price" class="form-control price">
                                            </td>

                                            <td style="width: 58px;"><input type="number" value="{{ $item->qty ?? 1 }}"
                                                    name="item_qty[]" placeholder="Quantity" class="form-control qty">
                                            </td>

                                            <td style="width:140px;"><select name="item_unit[]" id=""
                                                    class="form-control js-select2-custom">
                                                    <option value="">-- Unit --</option>
                                                    @foreach (\App\Models\Unit::all() as $unit)
                                                        <option value="{{ $unit->id }}">{{ $unit->unit }}
                                                        </option>
                                                    @endforeach
                                                </select></td>
                                            <td style="width: 58px;" class="tax_inp_data hidden_tax"><input
                                                    type="number" value="{{ $item->tax }}" name="item_tax[]"
                                                    placeholder="Tax" class="form-control tax"></td>
                                            <td style="width: 93px;" class="hsn_inp hidden_hsn"><input type="text"
                                                    value="{{ $item->hsn }}" name="item_hsn[]" placeholder="HSN"
                                                    class="form-control"></td>

                                            <td style="width: 93px;" class="hidden_tax"><input type="text"
                                                    value="" readonly placeholder="Taxable"
                                                    class="form-control item_taxable"></td>
                                            <td style="width: 93px;"><input type="text" value="" readonly
                                                    placeholder="Total" class="form-control item_total"></td>
                                            <td><button type="button"
                                                    onclick="deleteQuoteRow({{ $item->id }}, 'quote')"
                                                    class="btn action-btn btn--danger btn-outline-danger"><i
                                                        class="tio-delete-outlined"></i></button></td>
                                        </tr>
                                    @endforeach


                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body p-1">
                            <p><strong>Total (Without GST): <span class="currency">₹</span><span class="totalWithoutGST"
                                        id="totalWithoutGST">0</span></strong></p>
                            <input type="hidden" name="final_total_amount" id="totalWithGSTHidden_inv">
                            <p class="totalWithGSTInp" style="display:none;"><strong>Total (With GST): <span
                                        class="currency">₹</span><span class="totalWithGST"
                                        id="totalWithGST">0</span></strong></p>
                        </div>
                    </div>
                </div>

                <div class="col-12 d-flex flex-column align-items-end justify-content-end">
                    <span class="text-danger amount_error"></span>
                    <button class="btn btn-primary my-2 submit_btn">Update Bill</button>
                </div>

            </form>
            {{-- @include('vendor-views.form_modals.inventory_item_select') --}}




        </div>
    </div>
@endsection
@push('script_2')
    @include('vendor-views/billing/basic-inoice-js')
    <script>
        $(document).on('keyup change', ".cash_amount, .online_amount, .price, .qty, .payment_mode", function() {

            validateAmount();

        });

        function validateAmount() {
            if ($("input[name='payment_mode']:checked").val() == 'Cash and Online') {
                var totalAmt = parseFloat($("#totalWithGST").text().replace(/,/g, '')) || 0;

                var cash = parseFloat($(".cash_amount").val()) || 0;
                var online = parseFloat($(".online_amount").val()) || 0;

                var entered = cash + online;

                if (entered > totalAmt) {
                    $(".amount_error").text("Amount is greater than total!");
                    $(".submit_btn").attr('disabled', true)
                } else if (entered < totalAmt) {
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
    </script>
@endpush
