    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/create_invoice.css') }}">

    <form class="w-100 row" action="{{ route('vendor.invoice.purchase-invoice.save') }}" enctype = 'multipart/form-data'
        method="post">
        @csrf
        <div class="col-md-12 p-1">
            <div class="card h-100">
                <div class="row p-2 card-body align-items-center">
                    <div class=" col-md-4   p-2">
                        <div class="customer_elem_inner">
                            <label>Reference File Upload (Optional)</label>
                            <input type="file" name="file" accept="image/*,application/pdf,.doc,.docx"
                                id="" class="form-control">
                        </div>
                    </div>
                    <div class=" col-md-4 p-2">
                        <div class="customer_elem_inner">
                            <label>Vendor</label>
                            <select name="bill_from" id="customer_id" class="get_vendor" data-placeholder="Search for Vendor"></select>
                        </div>
                    </div>
                    <div class=" col-md-4   p-2">
                        <div class="">
                            <label>Invoice Date</label>
                            <input type="date" name="invoice_date" class="form-control">
                        </div>
                    </div>
                    <div class=" col-md-4  p-2">
                        <div class="">
                            <label>Invoice Id</label>
                            <input type="text" name="invoice_id" class="form-control">
                        </div>
                    </div>
                    <div class=" col-md-3 btn-group btn-group-toggle m-0" style="    margin: 2px auto;"
                        data-toggle="buttons">
                        @if (App\CentralLogics\Helpers::get_store_data()->gst &&
                                json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                            <label class="btn btn-responsive btn-outline-primary ">
                                <input type="radio" class="tax_type" value="gst" name="tax_type" id="option1">
                                GST
                            </label>
                        @else
                            <label onclick="showGSTAlert()" class="btn btn-responsive btn-outline-primary"
                                style="cursor: not-allowed;">
                                <input type="radio" disabled class="tax_type" value="gst" name="tax_type"
                                    id="option1"> GST
                            </label>
                        @endif
                        <label class="btn btn-responsive btn-outline-primary active">
                            <input type="radio" checked class="tax_type" value="non-gst" name="tax_type"
                                id="option3">
                            Non GST
                        </label>
                    </div>
                    <div class="col-md-4 d-flex">

                        <div class="form-check mr-5 ml-4">
                            <input class="form-check-input" value="Paid" name="payment_stts" type="radio"
                                name="flexRadioDefault" id="flexRadioDefault1" checked>
                            <label class="form-check-label" for="flexRadioDefault1">
                                Paid
                            </label>
                        </div>
                        {{-- @php
                             $billingDisabled = !_isSubmoduleEnabled('3')['enabled']
                                 ? 'disabled title="Enable advanced billing for creating unpaid bills and payment reminders"'
                                 : '';
                         @endphp --}}
                        @php $billingDisabled = ''; @endphp
                        <div class="form-check">
                            <input class="form-check-input" value="Unpaid" name="payment_stts" type="radio"
                                {!! $billingDisabled !!} name="flexRadioDefault" id="flexRadioDefault2">
                            <label class="form-check-label" for="flexRadioDefault2">
                                Unpaid
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12  row">
                        <div class="form-check payment_date_inp col-md-4 col-sm-6 p-1" style="display:none;">
                            <label class="form-check-label" for="flexRadioDefault2">Payment Date</label>
                            <input class="form-control" min="{{ date('Y-m-d') }}" name="payment_date" type="date"
                                name="flexRadioDefault" id="flexRadioDefault2">
                        </div>
                        <div class="form-check reminder_date_inp col-md-4 col-sm-6 p-1" style="display:none;">
                            <label class="form-check-label" for="flexRadioDefault2">Reminder Start Date</label>
                            <input class="form-control" min="{{ date('Y-m-d') }}"
                                value="{{ now()->addDay()->format('Y-m-d') }}" name="reminder_date" type="date"
                                name="flexRadioDefault" id="flexRadioDefault2">
                        </div>
                        <div class="form-check reminder_date_inp col-md-4 col-sm-6 p-1" style="display:none;">
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
            <div class="card h-100">
                <div class="card-body  ">
                    <div class="">
                        <button type="button" class="btn text-primary p-1" onclick="addMoreRow()">+ Add
                            Item</button>
                        @if (_isSubscription())
                            <button type="button" class="btn text-primary p-1" data-toggle="modal"
                                data-target="#inventoryItemModal">+ Add From {{ _moduleLabel('inventory') }}</button>
                        @endif
                    </div>
                    <table class="items-table">
                        <thead class="items_head">
                            <tr>
                                <th>Description</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th class="gst_fld hidden_gst_f">Tax</th>
                                <th class="gst_fld hidden_gst_f" scope="col">HSN</th>
                                <th class="gst_fld hidden_gst_f">Taxable</th>
                                <th>Total</th>

                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="rows_parent">

                        </tbody>
                    </table>
                    <div class="empty-state">
                        <div class="empty-icon">📦</div>
                        @if (_isSubscription())
                            <p>Search existing products to add to this list to get started 🚀</p>

                            <button type="button" class="btn btn-outline-primary" data-toggle="modal"
                                data-target="#inventoryItemModal">+ Add From {{ _moduleLabel('inventory') }}</button>
                        @else
                            <p>Start adding products to this list to get started 🚀</p>
                            <button type="button" class="btn btn-outline-primary" onclick="addMoreRow()">+ Add
                                Item</button>
                        @endif
                    </div>
                    <div>
                        <p class="totalWithGSTInp"><strong>Total: <span class="currency">₹</span><span
                                    id="totalWithGST">0</span></strong></p>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end w-100">
                <button class="btn btn-primary my-2">Save Invoice</button>
            </div>
        </div>

    </form>
