<form class="w-100 row g-0 invoice-form calc-form"
    action="{{ isset($task) ? route('vendor.invoice.save-manual-invoice-lead', [$task->id]) : route('vendor.invoice.save-manual-invoice') }}"
    method="post">
    @csrf
    <div class="col-md-12 p-1">
        <div class="card h-100">
            <div class="card-body">
                <input type="hidden" id="service_id" name="service_id" value="">

                <!-- Row 1: Invoice Details --> 
                <div class="row  mb-2">
                    <div class="col-md-2 p-1">
                        <label class="form-check-label d-flex mb-1" for="flexRadioDefault32">Invoice Id</label>
                        <div class="gst_invoice_num" style="display:none;">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text invoice_prefix"
                                    style="border-right: none; padding-right: 0;">{{ $bill_num['prefix'] }}</span>
                                <input type="number" onkeyup="validateInvoiceNum(this, 'gst')"
                                    class="form-control form-control-sm custom-input invoice_num gst_field"
                                    id="editableInput" value="{{ $bill_num['number'] }}">
                            </div>
                            <span class="text-danger invoice_error"></span>
                        </div>
                        <div class="nongst_invoice_num">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text invoice_prefix2 bg-white"
                                    style="border-right: none; padding: 6px 8px; padding-right: 0;">{{ $bill_num['nongst_prefix'] }}</span>
                                <input type="number" onkeyup="validateInvoiceNum(this, 'non_gst')"
                                    class="form-control form-control-sm custom-input invoice_num non_gst_field"
                                    id="editableInput" name="number" value="{{ $bill_num['non_gst_sno'] }}">
                            </div>
                            <span class="text-danger invoice_error"></span>
                        </div>
                    </div>

                    @if (isset($task) && $task->user_id)
                        <input type="hidden" name="customer_id" value="{{ $task->user_id }}">
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
                                $startOfFinancialYear = (date('m') >= 4 ? date('Y') : date('Y') - 1) . '-04-01';
                            @endphp
                            <input type="date" name="invoice_date" class="form-control form-control-sm"
                                min="{{ $startOfFinancialYear }}" value="{{ $today }}">
                        </div>
                    </div>

                    <div class="col-md-2 p-1">
                        <label class="form-check-label " for="flexRadioDefault2">Reference Number <i
                                class="tio-info-outined" title="Eg. Bank Transaction Id"></i></label>
                        <select name="reference_number[]" data-placeholder="Reference Number" class=" js-example-tags"
                            multiple>
                        </select>
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
                                <input class="form-check-input tax_type" value="non-gst" name="tax_type" type="radio"
                                    id="gstRadio2" checked>
                                <label class="form-check-label small" for="gstRadio2">Non GST</label>
                            </div>
                        </div>

                    </div>
                    <div class="col-md-2 p-1">

                        <label class="d-block mb-1 small">Payment Status</label>
                        <div class="d-flex d-flex border rounded" style="padding: 11px;">
                            <div class="form-check mr-3 form-check-inline">
                                <input class="form-check-input" value="Paid" name="payment_stts" type="radio"
                                    id="payment_sttsRadio1" checked>
                                <label class="form-check-label small" for="payment_sttsRadio1">Paid</label>
                            </div>
                            @php
                                $billingDisabled = '';
                            @endphp
                            <div class="form-check form-check-inline">
                                <input {!! $billingDisabled !!} class="form-check-input" value="Unpaid"
                                    name="payment_stts" type="radio" id="payment_sttsRadio2">
                                <label class="form-check-label small" for="payment_sttsRadio2">Unpaid</label>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Row 2: Payment Method and Dates -->
                <div class="row">
                    <div class="@if(Route::currentRouteName() === 'vendor.task.detail')  'col-md-3' @else 'col-md-2' @endif p-1 payment_mode_grp">
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
                        <input class="form-control form-control-sm cash_amount" name="cash_amount" type="number"
                            placeholder="Ex: 2000" step="0.001" name="flexRadioDefault" id="flexRadioDefault2">
                    </div>
                    <div class="col-md-2 p-1 partial_payment" style="display: none">
                        <label class="form-check-label mb-1" for="flexRadioDefault2">Online Amount</label>
                        <input class="form-control form-control-sm online_amount" name="online_amount" type="number"
                            placeholder="Ex: 3000" step="0.001" name="flexRadioDefault" id="flexRadioDefault2">
                    </div>

                    <div class="payment_date_inp col-md-2 p-1" style="display:none;">
                        <label class="form-check-label mb-1" for="flexRadioDefault2">Payment Date</label>
                        <input class="form-control form-control-sm" min="{{ date('Y-m-d') }}" name="payment_date"
                            type="date" name="flexRadioDefault" id="flexRadioDefault2">
                    </div>

                    <div class="reminder_date_inp col-md-2 p-1" style="display:none;">
                        <label class="form-check-label mb-1" for="flexRadioDefault2">Reminder Start Date</label>
                        <input class="form-control form-control-sm" value="{{ date('Y-m-d') }}"
                            min="{{ date('Y-m-d') }}" name="reminder_date" type="date" name="flexRadioDefault"
                            id="flexRadioDefault2">
                    </div>

                    <div class="reminder_date_inp col-md-2 p-1" style="display:none;">
                        <label class="form-check-label mb-1" for="flexRadioDefault2">Reminder Frequency</label>
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
                        @if (isset($existingItems) && !empty($existingItems))
                            @foreach ($existingItems as $key => $value)
                                <tr class="item_row_inv" data-id="1">
                                    <input type="hidden" name="inventory_item_id[]"
                                        value="{{ $value->inv_item_id ?? '' }}">
                                    {{-- <input type="hidden" name="invoice_item_id[]"> --}}
                                    <td><input type="text" name="item_name[]" value="{{ $value->name ?? '' }}"
                                            placeholder="Item Name" class="form-control"></td>
                                    <td style="width: 100px;"><input type="number" step="0.001"
                                            name="item_price[]" value="{{ $value->unitprice ?? '' }}"
                                            placeholder="Price" class="form-control price"></td>
                                    <td style="width: 58px;"><input value="{{ $value->quantity ?? '' }}"
                                            type="number" name="item_qty[]" value="1" placeholder="Quantity"
                                            class="form-control qty"></td>
                                    <td style="width:140px;"><select name="item_unit[]" id=""
                                            class="form-control js-select2-custom">
                                            <option value="">-- Unit --</option>
                                            @foreach (\App\Models\Unit::all() as $unit)
                                                <option value="{{ $unit->id }}">{{ $unit->unit }}</option>
                                            @endforeach
                                        </select></td>
                                    <td style="width: 58px;" class="tax_inp_data hidden_tax"><input
                                            value="{{ $value->tax ?? '' }}" type="number" name="item_tax[]"
                                            placeholder="Tax" class="form-control tax"></td>
                                    <td style="width: 93px;" class="hsn_inp hidden_hsn"><input type="text"
                                            name="item_hsn[]" placeholder="HSN" class="form-control"></td>

                                    <td style="width: 93px;" class="hidden_tax"><input type="text" readonly
                                            placeholder="Taxable" value="{{ $value->quantity * $value->unitprice }}"
                                            class="form-control item_taxable"></td>
                                    <td style="width: 93px;" class=""><input type="text" readonly
                                            placeholder="Total" value="{{ $value->quantity * $value->unitprice }}"
                                            class="form-control item_total"></td>
                                    <td><button type="button" onclick="deleteNewRow(1)"
                                            class="btn action-btn btn--danger btn-outline-danger"><i
                                                class="tio-delete-outlined"></i></button></td>
                                </tr>
                            @endforeach

                        @endif
                    </tbody>
                </table>

            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body p-1">
                <p><strong>Total (Without GST): <span class="currency">₹</span><span
                        class="totalWithoutGST"  id="totalWithoutGST"  >0</span></strong></p>
                <input type="hidden" name="final_total_amount" id="totalWithGSTHidden_inv">
                <p class="totalWithGSTInp" style="display:none;"><strong>Total (With GST): <span
                            class="currency">₹</span><span class="totalWithGST" id="totalWithGST">0</span></strong></p>
            </div>
        </div>
    </div>

    <input type="hidden" name="tnc_id" value="{{\App\CentralLogics\Helpers::get_store_data()->storeConfig?->default_invoice_tnc_id}}">

    <div class="col-12 d-flex flex-column align-items-end justify-content-end" style=""> 
        {{-- @if(isset($tncs) && $tncs->count() > 0)
        <div class="w-100 mb-2">
            <label class="form-check-label mb-1 small"><strong>Default Terms & Conditions</strong></label>
            <select name="tnc_id" id="tncSelect" class="form-control form-control-sm">
                <option value="">-- None --</option>
                @foreach($tncs as $tnc)
                    <option value="{{ $tnc->id }}" data-content="{{ $tnc->content }}"
                        {{ (string) ( ?? '') === (string) $tnc->id ? 'selected' : '' }}>
                        {{ $tnc->tnc_for }}
                    </option>
                @endforeach
            </select> 
        </div>
        @endif --}}
        <span class="text-danger amount_error"></span>
        <button class="btn btn-primary my-2 submit_btn">Generate Bill</button>
    </div>

</form>
{{-- @include('vendor-views.form_modals.inventory_item_select') --}}


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
