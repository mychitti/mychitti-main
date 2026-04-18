<form class="w-100 row" action="{{ route('admin.quotation.save-info') }}" method="post">
    @csrf
    <div class="col-md-12 p-1">
        <div class="card h-100">
            <div class="card-body row  align-items-start">
                <input type="hidden" id="quote_id" name="quote_id" value="{{ $quote->id }}">

                @php
                    $billToType = $quote->quote_detail?->bill_to_type ?? 'user';
                    $billToId   = $quote->client_name;
                    $billLabel  = null;
                    $billSub1   = null;
                    $billSub2   = null;
                    if ($billToId) {
                        if ($billToType === 'vendor') {
                            $bRec = \App\Models\Store::find($billToId);
                            if ($bRec) {
                                $billLabel = $bRec->name;
                                $billSub1  = $bRec->phone;
                                $billSub2  = $bRec->email;
                            }
                        } elseif ($billToType === 'mychitti_client') {
                            $bRec = \App\Models\StoreCustomer::find($billToId);
                            if ($bRec) {
                                $billLabel = trim($bRec->f_name . ' ' . $bRec->l_name);
                                $billSub1  = $bRec->phone;
                                $billSub2  = $bRec->email;
                            }
                        } else {
                            $bRec = \App\Models\User::find($billToId);
                            if ($bRec) {
                                $billLabel = trim($bRec->f_name . ' ' . $bRec->l_name);
                                $billSub1  = $bRec->phone;
                                $billSub2  = $bRec->email;
                            }
                        }
                    }
                @endphp
                @if ($billToId && $billLabel)
                    <input type="hidden" name="bill_to" value="{{ $billToId }}">
                    <input type="hidden" name="bill_to_type" value="{{ $billToType }}">
                    <div class="upgrade-card cust_det_card col-md-3 p-2 mr-2">
                        <small class="text-muted text-uppercase" style="font-size:10px;letter-spacing:.5px;">
                            {{ $billToType === 'vendor' ? 'Store' : ($billToType === 'mychitti_client' ? 'Mychitti Client' : 'Customer') }}
                        </small>
                        <div class="customer_info mt-1">
                            <h6 class="mb-0">{{ $billLabel }}</h6>
                            @if($billSub1)<p class="mb-0" style="font-size:12px">{{ $billSub1 }}</p>@endif
                            @if($billSub2)<p class="mb-0" style="font-size:12px">{{ $billSub2 }}</p>@endif
                        </div>
                    </div>
                @else
                    <div class="col-md-3 p-1">
                        <label class="form-check-label d-flex mb-1">Bill To</label>
                        <div class="d-flex flex-wrap mb-2">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" value="user" checked id="editQuoteRadioUser" name="bill_to_type"
                                    class="custom-control-input quote_bill_to_type">
                                <label class="custom-control-label" for="editQuoteRadioUser">Customer</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" value="vendor" id="editQuoteRadioVendor" name="bill_to_type"
                                    class="custom-control-input quote_bill_to_type">
                                <label class="custom-control-label" for="editQuoteRadioVendor">Store</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" value="mychitti_client" id="editQuoteRadioMychitti" name="bill_to_type"
                                    class="custom-control-input quote_bill_to_type">
                                <label class="custom-control-label" for="editQuoteRadioMychitti">Mychitti Client</label>
                            </div>
                        </div>
                        <div id="quote_customer_list">
                            <select name="bill_to" id="quote_user_select" class="form-control">
                                <option value=""></option>
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
                            </select>
                        </div>
                    </div>
                @endif
                <div class="form-check  col-md-3  p-1">
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
                            <input class="form-check-input tax_type"
                                {{ $quote->quote_detail?->tax_type == 'gst' ? 'checked' : '' }} value="gst"
                                name="tax_type" type="radio" id="gstRadio1">
                            <label class="form-check-label" for="gstRadio1">
                                GST
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input tax_type" value="non-gst" name="tax_type" type="radio"
                                id="gstRadio2"
                                {{ $quote->quote_detail?->tax_type == 'non-gst' || !$quote->quote_detail?->tax_type ? 'checked' : '' }}>
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
                        <div class="form-check">
                            <input class="form-check-input " value="Unpaid" name="payment_stts" type="radio"
                                id="payment_sttsRadio2">
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
                @if (_isSubscription() && Route::currentRouteName() === 'admin.quotation.add')
                    <button type="button" class="btn btn-dark btn-sm" data-toggle="modal"
                        data-target="#inventoryItemModal">+ Add From Inventory</button>
                @endif
                {{-- GST/non-GST visibility is handled by JS on tax_type change --}}
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
                        @foreach ($quote_items as $item)
                            <tr class="item_row_inv" data-id="{{ $item->id }}">
                                <td><input type="text" value="{{ $item->name }}" name="item_name[]"
                                        placeholder="Item Name" class="form-control"></td>

                                <td style="width: 100px;"><input type="number" step="0.001"
                                        value="{{ $item->price }}" name="item_price[]" placeholder="Price"
                                        class="form-control price">
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
                                <td style="width: 58px;" class="tax_inp_data hidden_tax"><input type="number"
                                        value="{{ $item->tax }}" name="item_tax[]" placeholder="Tax"
                                        class="form-control tax"></td>
                                <td style="width: 93px;" class="hsn_inp hidden_hsn"><input type="text"
                                        value="{{ $item->hsn }}" name="item_hsn[]" placeholder="HSN"
                                        class="form-control"></td>

                                <td style="width: 93px;" class="hidden_tax"><input type="text" value=""
                                        readonly placeholder="Taxable" class="form-control item_taxable"></td>
                                <td style="width: 93px;"><input type="text" value="" readonly
                                        placeholder="Total" class="form-control item_total"></td>
                                <td><button type="button" onclick="deleteQuoteRow({{ $item->id }}, 'quote')"
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
