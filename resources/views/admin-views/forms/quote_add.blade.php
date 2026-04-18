<form class="w-100 row quote_form" action="{{ route('admin.quotation.save-info') }}" method="post">
    @csrf
    <div class="col-md-12 p-1">
        <div class="card h-100">
            <div class="card-body row  align-items-start">
                <input type="hidden" id="service_id" name="service_id" value="">

                <div class="form-check  col-md-2  p-1">
                    <label class="form-check-label d-flex " for="flexRadioDefault2">Quotation Number</label>
                    <div id="">
                        @php $next_quotation_num = \App\CentralLogics\Helpers::quoteId(0) @endphp
                        <input type="number" name="quotation_id" class="form-control quotation_number" min="1"
                            value="{{ $next_quotation_num }}">
                        <span class="text-danger quote_num_text"></span>
                    </div>
                </div>

                @if (isset($task) && $task->user_id)
                    <input type="hidden" name="customer_id" value="{{ $task->user_id }}">
                @else
                    <div class="col-md-3 p-1">
                        <label class="form-check-label d-flex mb-1">Bill To Type</label>
                        <div class="d-flex flex-wrap mb-2">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" value="user" checked id="quoteRadioUser" name="bill_to_type"
                                    class="custom-control-input quote_bill_to_type">
                                <label class="custom-control-label" for="quoteRadioUser">Customer</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" value="vendor" id="quoteRadioVendor" name="bill_to_type"
                                    class="custom-control-input quote_bill_to_type">
                                <label class="custom-control-label" for="quoteRadioVendor">Store</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" value="mychitti_client" id="quoteRadioMychitti"
                                    name="bill_to_type" class="custom-control-input quote_bill_to_type">
                                <label class="custom-control-label" for="quoteRadioMychitti">Mychitti Client</label>
                            </div>
                        </div>

                    </div>
                    <div class="col-md-3 p-1">
                        <label class="form-check-label d-flex mb-1">Bill To </label>

                        <div id="quote_customer_list">
                            <select name="bill_to" id="quote_user_select" class="form-control">
                                <option value=""></option>
                                <option value="add_new">&#43; Add New Customer</option>
                            </select>
                        </div>
                        <div id="quote_store_list" style="display:none;">
                            <select name="" id="quote_vendor_select" class="form-control">
                                <option value=""></option>
                            </select>
                        </div>
                        <div id="quote_mychitti_client_list" style="display:none;">
                            <select name="" id="quote_mychitti_select" class="form-control">
                                <option value=""></option>
                                <option value="add_new">&#43; Add New Customer</option>
                            </select>
                        </div>
                        <span class="text-success user_type_show"></span>
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
                <div class="col-md-3">
                    <div class="row w-100 mb-2 ml-4">

                        <div class="form-check mr-5 ">
                            <input class="form-check-input tax_type" value="gst" name="tax_type" type="radio"
                                id="gstRadio1">
                            <label class="form-check-label" for="gstRadio1">
                                GST
                            </label>
                        </div>
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
                            <input {!! $billingDisabled !!} class="form-check-input " value="Unpaid"
                                name="payment_stts" type="radio" id="payment_sttsRadio2">
                            <label class="form-check-label" for="payment_sttsRadio2">
                                Unpaid
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 row">
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
                <button type="button" class="btn btn-dark btn-sm" data-toggle="modal"
                    data-target="#inventoryItemModal">+ Add From Inventory</button>
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
                <p class="totalWithGSTInp" style="display:none;"><strong>Total (With GST): <span
                            class="currency">₹</span><span class="totalWithGST" id="totalWithGST">0</span></strong>
                </p>
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
        .item_row_quote td {
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
    } --}} #toast {
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
