<form class="w-100 row quote_form" action="{{ route('vendor.quotation.save-info') }}" method="post">
    @csrf
    <div class="col-md-12 p-1">
        <div class="card h-100">
            <div class="card-body row  align-items-start">
                <input type="hidden" id="service_id" name="service_id" value="">

                <div class="form-check  col-md-2  p-1">
                    <label class="form-check-label d-flex " for="flexRadioDefault2">Quotation Number</label>
                    <div id="">
                      @php $next_quotation_num = \App\CentralLogics\Helpers::quoteId() @endphp
                        <input type="number" name="quotation_id" class="form-control quotation_number" min="1"
                            value="{{ $next_quotation_num }}">
                            <span class="text-danger quote_num_text"></span>
                    </div>
                </div>

                @if (isset($task) && $task->user_id)
                    <input type="hidden" name="customer_id" value="{{ $task->user_id }}">
                @else
                    <div class="form-check  col-md-2  p-1">
                        <label class="form-check-label d-flex " for="flexRadioDefault2">Client</label>
                        <div id="customer_id_elem">
                            <select name="bill_to" class="customer_id2 form-control js-select2-custom" id="customer_id">
                                <option value=""></option>
                                @if (!isset($task))
                                    <option value="add_new">&#43; Add New Customer</option>
                                @endif
                            </select>
                            <span class="text-success user_type_show"></span>
                        </div>
                    </div>
                @endif
                <div class="form-check  col-md-2  p-1">
                    <label class="form-check-label d-flex " for="flexRadioDefault2">Quotation Date</label>
                    <div id="">
                        @php
                            $today = date('Y-m-d');
                            $startOfFinancialYear = (date('m') >= 4 ? date('Y') : date('Y') - 1) . '-04-01';
                        @endphp
 
                        <input type="date" name="invoice_date" class="form-control" min="{{ $startOfFinancialYear }}"
                            value="{{ $today }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="row w-100 mb-2 ml-4">
                    <label for=""></label>
                    </div>
                    <div class="row w-100 mb-2 ml-4">
                        @if (App\CentralLogics\Helpers::get_store_data()->gst &&
                                json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                            <div class="form-check mr-5 ">
                                <input class="form-check-input tax_type" value="gst" name="tax_type" type="radio"
                                    id="gstRadio1">
                                <label class="form-check-label" for="gstRadio1">
                                    GST
                                </label>
                            </div>
                        @endif
                        <div class="form-check">
                            <input class="form-check-input tax_type" value="non-gst" name="tax_type" type="radio"
                                id="gstRadio2" checked>
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
                            <input {!! $billingDisabled !!} class="form-check-input " value="Unpaid" name="payment_stts"
                                type="radio" id="payment_sttsRadio2">
                            <label class="form-check-label" for="payment_sttsRadio2">
                                Unpaid
                            </label>
                        </div>
                    </div>
                   
                </div>
                <div class="col-md-3">
                 <div class="payment_mode_grp">
                        <div class="pos--payment-options">
                            <label>{{ translate('paid_By') }}</label>
                            <ul class="mb-0">
                                <li>
                                    <label>
                                        <input type="radio" class="payment_mode" name="payment_mode" value="Cash" hidden checked>
                                        <span>Cash</span>
                                    </label>
                                </li>
                                <li>
                                    <label>
                                        <input type="radio" class="payment_mode" name="payment_mode" value="Online" hidden>
                                        <span>Online</span>
                                    </label>
                                </li>
                                <li>
                                    <label>
                                        <input type="radio" class="payment_mode" name="payment_mode" value="Cash and Online" hidden>
                                        <span>Both</span>
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div></div>
                <div class="col-md-6 row">
                    <div class="col-md-3 p-1 partial_payment" style="display: none">
                        <label class="form-check-label mb-1">Cash Amount</label>
                        <input class="form-control form-control-sm cash_amount" name="cash_amount" type="number"
                            placeholder="Ex: 2000" step="0.001">
                    </div>
                    <div class="col-md-3 p-1 partial_payment" style="display: none">
                        <label class="form-check-label mb-1">Online Amount</label>
                        <input class="form-control form-control-sm online_amount" name="online_amount" type="number"
                            placeholder="Ex: 3000" step="0.001">
                    </div>
                    <div class="col-12 partial_payment" style="display: none">
                        <small class="text-danger partial_payment_error"></small>
                    </div>
                    <div class="form-check payment_date_inp col-md-4 col-sm-6 p-1" style="display:none;">
                        <label class="form-check-label" for="flexRadioDefault2">Payment Date</label>
                        <input class="form-control" min="{{ date('Y-m-d') }}" name="payment_date" type="date"
                            name="flexRadioDefault" id="flexRadioDefault2">
                    </div>
                    <div class="form-check reminder_date_inp col-md-4 col-sm-6 p-1" style="display:none;">
                        <label class="form-check-label" for="flexRadioDefault2">Reminder Start Date</label>
                        <input class="form-control" value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}"
                            name="reminder_date" type="date" name="flexRadioDefault" id="flexRadioDefault2">
                    </div>
                    <div class="form-check reminder_date_inp col-md-4 col-sm-6 p-1" style="display:none;">
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
    </div>
    <div class="col-md-9 p-1">
        <div class="card h-100">
            <div class="card-body p-1">
                <button type="button" class="btn btn-dark btn-sm" onclick="addMoreRowQuote(null)">Add More</button>
                @if (_isSubscription() && Route::currentRouteName() === 'vendor.quotation.add')
                    <button type="button" class="btn btn-dark btn-sm" data-toggle="modal"
                        data-target="#inventoryItemModal">+ Add From {{ _moduleLabel('inventory') }}</button>
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
                    <tbody class="rows_parent_quote">
                        {{-- <tr class="item_row_inv" data-id="1">
                            <input type="hidden" name="invoice_item_id[]">
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
                            <td style="width: 58px;" class="tax_inp_data hidden_tax"><input type="number"
                                    name="item_tax[]" placeholder="Tax" class="form-control tax"></td>
                            <td style="width: 93px;" class="hsn_inp hidden_hsn"><input type="text"
                                    name="item_hsn[]" placeholder="HSN" class="form-control"></td>

                            <td style="width: 93px;" class="hidden_tax"><input type="text" readonly
                                    placeholder="Taxable" class="form-control item_taxable"></td>
                            <td style="width: 93px;" class=""><input type="text" readonly
                                    placeholder="Total" class="form-control item_total"></td>
                            <td><button type="button" onclick="deleteNewRow('invoice')"
                                    class="btn action-btn btn--danger btn-outline-danger"><i
                                        class="tio-delete-outlined"></i></button></td>
                        </tr> --}}
                    </tbody>
                </table>

            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body p-1">
                <p><strong>Total (Without GST): <span class="currency">₹</span><span
                         class="totalWithoutGST"   id="totalWithoutGST">0</span></strong></p>
                <p class="totalWithGSTInp" style="display:none;"><strong>Total (With GST): <span
                            class="currency">₹</span><span class="totalWithGST" id="totalWithGST">0</span></strong></p>
            </div>
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end">
        <button class="btn btn-primary my-2 submit_btn">Save</button>
    </div>

</form>


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

    .form-row {
        margin-top: 6px;
    }

    .hidden_tax {
        display: none;
    }

    @media (max-width: 768px) {
        .item_row_quote td{
            width: 100% !important;
        }
        {{-- table {
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
        } --}}
    }

    {{-- .table th {
        padding: 5px !important;
    } --}}

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
