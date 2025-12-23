@extends('layouts.vendor.app')

@section('title', 'Service Bill Generate')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (
        !App\CentralLogics\Helpers::get_store_data()->gst == null &&
            !(App\CentralLogics\Helpers::get_store_data()->gst &&
                json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status
            ))
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

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Bill Generate</h1>
            <div class="page-header-select-wrapper">

            </div>
        </div>
        <!-- End Page Header -->

        <div class="row g-2">
            <form class="w-100 row" action="{{ route('vendor.invoice.save-invoice') }}" method="post">
                @csrf
                <div class="col-md-12 p-1">
                    <div class="card h-100">
                        <div class="row p-2 card-body ">
                            <div class=" col-md-2 row p-2">
                                <div class="form-check mr-5 ml-4">
                                    <input class="form-check-input" value="Paid" name="payment_stts" type="radio"
                                        name="flexRadioDefault" id="flexRadioDefault1" {{($invoice && $invoice->payment_status == 'Paid') || !$invoice ? 'checked' : ''}} >
                                    <label class="form-check-label" for="flexRadioDefault1">
                                        Paid
                                    </label>
                                </div>
                                {{-- @php
                                    $billingDisabled = !_isSubmoduleEnabled('3')['enabled']
                                        ? 'disabled title="Enable advanced billing for creating unpaid bills and payment reminders"'
                                        : '';
                                @endphp --}}
                                @php $billingDisabled = '';@endphp
                                <div class="form-check">
                                    <input class="form-check-input" value="Unpaid" name="payment_stts" type="radio"
                                        {!! $billingDisabled !!} name="flexRadioDefault" id="flexRadioDefault2" {{($invoice && $invoice->payment_status == 'Unpaid') ? 'checked' : ''}}>
                                    <label class="form-check-label" for="flexRadioDefault2">
                                        Unpaid
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2 p-1 payment_mode_grp " {{$invoice?->payment_status == 'Unpaid'  ? "style=display:none" : ''}}>
                                <div class="pos--payment-options">
                                    <label class="">{{ translate('paid_By') }}</label>
                                    <ul class="mb-0">
                                        <li>
                                            <label>
                                                <input type="radio" name="payment_mode" value="Cash" hidden {{!$invoice || ($invoice?->payment_status == 'Paid' && $invoice->payment_mode == 'Cash') ? 'checked' : ''}}>
                                                <span>Cash</span>
                                            </label>
                                        </li>
                                        <li>
                                            <label>
                                                <input type="radio" name="payment_mode" value="Online" {{$invoice?->payment_status == 'Paid' && $invoice->payment_mode == 'Online' ? 'checked' : ''}} hidden>
                                                <span>Online</span>
                                            </label>
                                        </li>
                                        <li>
                                            <label>
                                                <input type="radio" name="payment_mode" value="Cash and Online" {{$invoice?->payment_status == 'Paid' && $invoice->payment_mode == 'Cash and Online' ? 'checked' : ''}} hidden>
                                                <span>Both</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-md-2 p-1 partial_payment" {{$invoice?->payment_status == 'Paid' && $invoice->payment_mode == 'Cash and Online' ? '' : "style=display: none"}}>
                                <label class="form-check-label mb-1" for="flexRadioDefault2">Cash Amount</label>
                                <input class="form-control form-control-sm cash_amount" name="cash_amount" value="{{$invoice?->cash_amount}}" type="number"
                                    placeholder="Ex: 2000" name="flexRadioDefault" id="flexRadioDefault2">
                            </div>
                            <div class="col-md-2 p-1 partial_payment" {{$invoice?->payment_status == 'Paid' && $invoice->payment_mode == 'Cash and Online' ? '' : "style=display: none"}}>
                                <label class="form-check-label mb-1" for="flexRadioDefault2">Online Amount</label>
                                <input class="form-control form-control-sm online_amount" value="{{$invoice?->online_amount}}" name="online_amount"
                                    type="number" placeholder="Ex: 3000" name="flexRadioDefault" id="flexRadioDefault2">
                            </div>
                            <div class="col-md-9  row">
                                <div class="form-check payment_date_inp col-md-4 col-sm-6 p-1" {{$invoice?->payment_status == 'Paid' || !$invoice  ? "style=display:none" : ''}}>
                                    <label class="form-check-label" for="flexRadioDefault2">Payment Date</label>
                                    <input class="form-control" min="{{ date('Y-m-d') }}" name="payment_date" type="date"
                                        name="flexRadioDefault" id="flexRadioDefault2">
                                </div>
                                <div class="form-check reminder_date_inp col-md-4 col-sm-6 p-1"  {{$invoice?->payment_status == 'Paid' || !$invoice  ? "style=display:none" : ''}}>
                                    <label class="form-check-label" for="flexRadioDefault2">Reminder Start Date</label>
                                    <input class="form-control" min="{{ date('Y-m-d') }}"
                                        value="{{ now()->addDay()->format('Y-m-d') }}" name="reminder_date" type="date"
                                        name="flexRadioDefault" id="flexRadioDefault2">
                                </div>
                                <div class="form-check reminder_date_inp col-md-4 col-sm-6 p-1"  {{$invoice?->payment_status == 'Paid' || !$invoice  ? "style=display:none" : ''}}>
                                    <label class="form-check-label" for="flexRadioDefault2">Reminder Frequency</label>
                                    <div class="input-group">
                                        <input type="number" value="1" name="reminder_freq" class="form-control"
                                            id="inputGroupFile04" aria-describedby="inputGroupFileAddon04"
                                            aria-label="Upload">
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
                </div>
                <div class="col-md-12 p-1">
                    <input type="hidden" id="service_id" name="service_id" value="{{ $service_id }}">
                    <div class="card h-100">
                        <div class="card-body  ">
                            <table class="table">
                                <button type="button" class="btn btn-dark btn-sm" onclick="addMoreRow()">Add
                                    More</button>
                                <thead class="" style=" background: #75b8b8; color: white;">
                                    <tr>
                                        <th scope="col">Item</th>
                                        <th scope="col">Price <i>(per unit)</i></th>
                                        <th scope="col">Qty</th>
                                        <th scope="col">Unit</th>
                                        @if (App\CentralLogics\Helpers::get_store_data()->gst &&
                                                json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                                            <th scope="col">Tax <i>(in %)</i></th>
                                        @endif
                                        <th scope="col">HSN</th>
                                        @if (App\CentralLogics\Helpers::get_store_data()->gst &&
                                                json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                                            <th scope="col">Taxable</th>
                                        @endif
                                        <th scope="col">Total</th>
                                        <th scope="col"></th>
                                    </tr>
                                </thead>
                                <tbody class="rows_parent">
                                    @if (!$is_invoice)
                                        <tr class="item_row" data-id="1">
                                            <td><input type="text" value="{{ $service_det->name }}" name="item_name[]"
                                                    placeholder="Item Name" class="form-control"></td>
                                            <td style="width: 100px;"><input type="number"
                                                    value="{{ $service_det->quoted_price }}" name="item_price[]"
                                                    placeholder="Price" class="form-control price"></td>
                                            <td style="width: 58px;"><input type="number"
                                                    value="{{ $service_det->qty ?? 1 }}" name="item_qty[]"
                                                    placeholder="Quantity" class="form-control qty"></td>
                                            <td style="width:140px;"><select name="item_unit[]" id=""
                                                    class="form-control js-select2-custom">
                                                    <option value="">-- Unit --</option>
                                                    @foreach (\App\Models\Unit::all() as $unit)
                                                        <option value="{{ $unit->id }}">{{ $unit->unit }}</option>
                                                    @endforeach
                                                </select></td>
                                            @if (App\CentralLogics\Helpers::get_store_data()->gst &&
                                                    json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                                                <td style="width: 58px;"><input type="number"
                                                        value="{{ $service_det->tax }}" name="item_tax[]"
                                                        placeholder="Tax" class="form-control tax"></td>
                                            @endif
                                            <td style="width: 93px;"><input type="text" name="item_hsn[]"
                                                    placeholder="HSN" class="form-control"></td>
                                            @if (App\CentralLogics\Helpers::get_store_data()->gst &&
                                                    json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                                                <td style="width: 93px;"><input type="text" readonly
                                                        placeholder="Taxable" class="form-control item_taxable"></td>
                                            @endif
                                            <td style="width: 93px;"><input type="text" readonly placeholder="Total"
                                                    class="form-control item_total"></td>
                                            <td>-</td>
                                            <!-- <td><button type="button" onclick="deleteQuoteRow(1,'quote')" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td> -->
                                        </tr>
                                        @foreach ($quotations as $qt)
                                            <tr class="item_row" data-id="{{ $qt->id }}">
                                                <td><input type="text" value="{{ $qt->name }}" name="item_name[]"
                                                        placeholder="Item Name" class="form-control"></td>
                                                <td style="width: 100px;"><input type="number"
                                                        value="{{ $qt->price }}" name="item_price[]"
                                                        placeholder="Price" class="form-control price"></td>
                                                <td style="width: 58px;"><input type="number"
                                                        value="{{ $qt->qty ?? 1 }}" name="item_qty[]"
                                                        placeholder="Quantity" class="form-control qty"></td>
                                                <td style="width:140px;"><select name="item_unit[]" id=""
                                                        class="form-control js-select2-custom">
                                                        <option value="">-- Unit --</option>
                                                        @foreach (\App\Models\Unit::all() as $unit)
                                                            <option value="{{ $unit->id }}">{{ $unit->unit }}
                                                            </option>
                                                        @endforeach
                                                    </select></td>
                                                @if (App\CentralLogics\Helpers::get_store_data()->gst &&
                                                        json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                                                    <td style="width: 58px;"><input type="number"
                                                            value="{{ $qt->tax }}" name="item_tax[]"
                                                            placeholder="Tax" class="form-control tax"></td>
                                                @endif
                                                <td style="width: 93px;"><input type="text"
                                                        value="{{ $qt->hsn }}" name="item_hsn[]" placeholder="HSN"
                                                        class="form-control"></td>
                                                @if (App\CentralLogics\Helpers::get_store_data()->gst &&
                                                        json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                                                    <td style="width: 93px;"><input type="text" value=""
                                                            readonly placeholder="Taxable"
                                                            class="form-control item_taxable"></td>
                                                @endif
                                                <td style="width: 93px;"><input type="text" value="" readonly
                                                        placeholder="Total" class="form-control item_total"></td>
                                                <td><button type="button"
                                                        onclick="deleteQuoteRow({{ $qt->id }}, 'quote')"
                                                        class="btn action-btn btn--danger btn-outline-danger"><i
                                                            class="tio-delete-outlined"></i></button></td>
                                            </tr>
                                        @endforeach
                                    @else
                                        @foreach ($quotations as $key => $qt)
                                            <tr class="item_row" data-id="1">
                                                <input type="hidden" value="{{ $qt->id }}"
                                                    name="invoice_item_id[]">
                                                <td><input type="text" value="{{ $qt->name }}" name="item_name[]"
                                                        placeholder="Item Name" class="form-control"></td>
                                                <td style="width: 100px;"><input type="number"
                                                        value="{{ $qt->price }}" name="item_price[]"
                                                        placeholder="Price" class="form-control price">
                                                </td>
                                                <td style="width: 58px;"><input type="number"
                                                        value="{{ $qt->qty ?? 1 }}" name="item_qty[]"
                                                        placeholder="Quantity" class="form-control qty"></td>
                                                <td style="width:140px;"><select name="item_unit[]" id=""
                                                        class="form-control js-select2-custom">
                                                        <option value="">-- Unit --</option>
                                                        @foreach (\App\Models\Unit::all() as $unit)
                                                            <option value="{{ $unit->id }}">{{ $unit->unit }}
                                                            </option>
                                                        @endforeach
                                                    </select></td>
                                                @if (App\CentralLogics\Helpers::get_store_data()->gst &&
                                                        json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                                                    <td style="width: 58px;"><input type="number"
                                                            value="{{ $qt->tax }}" name="item_tax[]"
                                                            placeholder="Tax" class="form-control tax">
                                                    </td>
                                                @endif
                                                <td style="width: 93px;"><input type="text"
                                                        value="{{ $qt->hsn }}" name="item_hsn[]" placeholder="HSN"
                                                        class="form-control"></td>
                                                @if (App\CentralLogics\Helpers::get_store_data()->gst &&
                                                        json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                                                    <td style="width: 93px;"><input type="text" readonly
                                                            placeholder="Taxable" class="form-control item_taxable"></td>
                                                @endif
                                                <td style="width: 93px;"><input type="text" readonly
                                                        placeholder="Total" class="form-control item_total"></td>
                                                <td>
                                                    @if ($key)
                                                        <button type="button"
                                                            onclick="deleteQuoteRow({{ $qt->id }}, 'invoice')"
                                                            class="btn action-btn btn--danger btn-outline-danger"><i
                                                                class="tio-delete-outlined"></i></button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif


                                </tbody>
                            </table>
                            <div>
                                <p class="totalWithGSTInp"><strong>Total: <span class="currency">₹</span><span
                                            id="totalWithGST">0</span></strong></p>
                                            <input type="hidden" name="total_amt" id="totalWithGSTHidden">
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary my-2">Generate Bill</button>
                </div>

            </form>
        </div>
    </div>
@endsection
@push('script_2')
    @include('vendor-views/js.basic-invoice-js')
   
@endpush
