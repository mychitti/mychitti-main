@extends('layouts.admin.app')

@section('title', 'Bill Edit')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (
        !App\CentralLogics\Helpers::get_store_data()->gst == null &&
            !json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
        <style>
            .tax_field {
                display: none;
            }
        </style>
    @endif
    <style>
        .item_row td { 
            padding: 2px !important;
        }

        .form-row {
            margin-top: 6px;
        }

        #totalWithoutGST,
        #totalWithGST, 
        .currency {
            font-size: 18px;
            color: black;
        }

        @media (max-width: 768px) {
            table {
                display: block;
                border: none;
            }

            thead {
                display: none;
            }

            tbody tr {
                display: block;
                margin-bottom: 10px;
                border: 1px solid #ddd;
                padding: 10px;
            }

            tbody td {
                display: flex;
                justify-content: space-between;
                padding: 5px 10px;
            }

            tbody td::before {
                content: attr(data-label);
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

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">

            <div class="d-flex">
                <h1 class="page-header-title"><i class="tio-filter-list"></i>Bill Edit</h1>
                <h1 class="mx-3">#{{ $invoice->invoice_id }}</h1>

            </div>
        </div>
        <!-- End Page Header -->


        <div class="row g-2">
            <form class="w-100 row" action="{{ route('admin.billing.service-update-invoice') }}" method="post">
                @csrf
                <input type="hidden" name="invoice_id" value="{{ $invoice->invoice_id }}">
                <div class="col-md-12 p-1">
                    <div class="card h-100">
                        <div class="card-body row  align-items-start">
                            <div class="col-md-3">
                                <div class="row w-100 mb-2 ml-4">
                                    @if (App\CentralLogics\Helpers::get_store_data()->gst == null ||
                                            json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                                        <div class="form-check mr-5 ">
                                            <input class="form-check-input tax_type" value="gst" name="tax_type"
                                                type="radio" id="gstRadio1">
                                            <label class="form-check-label" for="gstRadio1">
                                                GST
                                            </label>
                                        </div>
                                    @endif
                                    <div class="form-check">
                                        <input class="form-check-input tax_type" value="non-gst" name="tax_type"
                                            type="radio" id="gstRadio2" checked>
                                        <label class="form-check-label" for="gstRadio2">
                                            Non GST
                                        </label>
                                    </div>
                                </div>
                                <div class="row w-100 mb-2 ml-4">
                                    <div class="form-check mr-5 ">
                                        <input class="form-check-input " value="Paid" name="payment_stts" type="radio"
                                            id="payment_sttsRadio1" checked>
                                        <label class="form-check-label" for="payment_sttsRadio1">
                                            Paid
                                        </label>
                                    </div>
                                    @php
                                            $billingDisabled = '';
                                    @endphp
                                    <div class="form-check">
                                        <input {!! $billingDisabled !!} class="form-check-input " value="Unpaid"
                                            name="payment_stts" type="radio" id="payment_sttsRadio2">
                                        <label class="form-check-label" for="payment_sttsRadio2">
                                            Unpaid
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-check payment_date_inp col-md-2 col-sm-6 p-1" style="display:none;">
                                <label class="form-check-label" for="flexRadioDefault2">Payment Date</label>
                                <input class="form-control" min="{{ date('Y-m-d') }}" name="payment_date" type="date"
                                    id="flexRadioDefault2">
                            </div>
                            <div class="form-check reminder_date_inp col-md-2 col-sm-6 p-1" style="display:none;">
                                <label class="form-check-label" for="flexRadioDefault2">Reminder Start Date</label>
                                <input class="form-control" value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}"
                                    name="reminder_date" type="date" id="flexRadioDefault2">
                            </div>
                            <div class="form-check reminder_date_inp col-md-2 col-sm-6 p-1" style="display:none;">
                                <label class="form-check-label" for="flexRadioDefault2">Reminder Frequency</label>
                                <div class="input-group">
                                    <input type="number" value="1" name="reminder_freq" class="form-control"
                                        id="inputGroupFile04" aria-describedby="inputGroupFileAddon04" aria-label="Upload">
                                    <select name="reminder_freq_unit" class="form-control">
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

                <div class="col-md-12 p-1">
                    <div class="card h-100">
                        <div class="card-body  ">
                            <table class="table mb-0">
                                <button type="button" class="btn btn-dark btn-sm" onclick="addMoreRow()">Add
                                    More</button>
                                <thead class="" style=" background: #75b8b8; color: white;">
                                    <tr>
                                        <th scope="col">Item</th>
                                        <th scope="col">Price <i>(per unit)</i></th>
                                        <th scope="col">Qty</th>
                                        <th scope="col">Unit</th>
                                        @if (App\CentralLogics\Helpers::get_store_data()->gst == null ||
                                                json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                                            <th scope="col">Tax <i>(in %)</i></th>
                                        @endif
                                        <th scope="col">HSN</th>
                                        @if (App\CentralLogics\Helpers::get_store_data()->gst == null ||
                                                json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                                            <th scope="col">Taxable</th>
                                        @endif
                                        <th scope="col">Total</th>
                                        <th scope="col"></th>
                                    </tr>
                                </thead>
                                <tbody class="rows_parent">

                                    @foreach ($invoice_items as $item)
                                        <tr class="item_row" data-id="{{ $item->id }}">
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
                                            @if (App\CentralLogics\Helpers::get_store_data()->gst == null ||
                                                    json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                                                <td style="width: 58px;"><input type="number"
                                                        value="{{ $item->tax }}" name="item_tax[]" placeholder="Tax"
                                                        class="form-control tax"></td>
                                            @endif
                                            <td style="width: 93px;"><input type="text" value="{{ $item->hsn }}"
                                                    name="item_hsn[]" placeholder="HSN" class="form-control"></td>
                                            @if (App\CentralLogics\Helpers::get_store_data()->gst == null ||
                                                    json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                                                <td style="width: 93px;"><input type="text" value="" readonly
                                                        placeholder="Taxable" class="form-control item_taxable"></td>
                                            @endif
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
                            <div>
                                <p class="totalWithGSTInp"><strong>Total: <span class="currency">₹</span><span
                                            id="totalWithGST">0</span></strong></p>
                            </div>
                            <div class="col-md-12">

                                <button class="btn btn-primary my-2" style="float:right;">Update Bill</button>
                            </div>
                        </div>
                    </div>

                </div>

            </form>
        </div>
    </div>
@endsection
@push('script_2')
    <script>
        $(document).on('change', 'input[name="payment_stts"]', function() {
            var val = $(this).val();
            if (val == 'Paid') {
                $(".payment_date_inp").hide()
                $(".reminder_date_inp").hide()
            } else {
                $(".payment_date_inp").show()
                $(".reminder_date_inp").show()
            }
        })

        function deleteQuoteRow(quoteId, type) {
            if (type == 'quote') {
                $('[data-id="' + quoteId + '"]').remove()
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
                toasterNotification('You cannot add more than 10 items.')
                return false;
            }

            var $lastItemRow = $('.item_row').last();

            if (!$lastItemRow.length) {
                var dataId = 1;
            } else {
                var dataId = Number($lastItemRow.data('id')) + 1;
            }

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

        $(document).on('keyup input change', '.price, .qty, .tax', function() {
            calculateTotals();
        });

        $(document).ready(function() {
            calculateTotals();
        });
    </script>
@endpush
