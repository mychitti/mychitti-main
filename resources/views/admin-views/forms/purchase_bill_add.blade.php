    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/create_invoice.css') }}">

    <form class="w-100 row" action="{{ route('admin.billing.purchase-invoice.save') }}" enctype = 'multipart/form-data'
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
                            <label class="d-block mb-2">Bill From</label>
                            <div class="bill_from_radio_grp mb-2 d-flex">
                                <div class="custom-control custom-radio custom-control-inline mr-3">
                                    <input type="radio" value="vendor" id="vendorRadio" name="bill_from_type"
                                        class="custom-control-input bill_from_type" checked>
                                    <label class="custom-control-label" for="vendorRadio">Mychitti Client</label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" value="store" id="storeRadio" name="bill_from_type"
                                        class="custom-control-input bill_from_type">
                                    <label class="custom-control-label" for="storeRadio">Store</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class=" col-md-4 p-2">
                        <div class="customer_elem_inner">
                            <div id="vendor_grp">
                                <label for="vendorSelect">Mychitti Client</label>
                                <select name="bill_from" id="vendorSelect"
                                    class="form-control js-select2-custom vendor-select" data-type="vendor"
                                    style="width:100%">
                                </select>
                            </div>
                            <div id="store_grp" style="display:none">
                                <label for="storeSelect">Store</label>
                                <select name="bill_from" id="storeSelect"
                                    class="form-control js-select2-custom store-select" data-type="store"
                                    style="width:100%">
                                </select>
                            </div>
                            <input type="hidden" name="bill_from_type_selected" id="bill_from_type_selected">
                            <span id="selected_type_span" class="text-success d-block mt-1"
                                style="font-size: 12px; font-weight: bold;"></span>
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

                        <label class="btn btn-responsive btn-outline-primary ">
                            <input type="radio" class="tax_type" value="gst" name="tax_type" id="option1">
                            GST
                        </label>

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
                            <button type="button" class="btn text-primary p-1" data-toggle="modal"
                                data-target="#inventoryItemModal">+ Add From Inventory</button>
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
                                data-target="#inventoryItemModal">+ Add From Inventory</button>
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


    @push('script_2')
        <script>
            $(document).ready(function() {
                // Vendor Select2
                $('.vendor-select').select2({
                    ajax: {
                        url: '{{ route('admin.billing.purchase-bill.search-bill-from') }}?type=vendor',
                        data: function(params) {
                            return {
                                q: params.term
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results.filter(r => r.type === 'vendor')
                            };
                        }
                    },
                    placeholder: 'Search Mychitti Client (Vendor)...',
                    minimumInputLength: 3,
                    allowClear: true,
                    templateResult: function(data) {
                        if (data.loading) return data.text;
                        return data.text;
                    }
                });

                // Store Select2
                $('.store-select').select2({
                    ajax: {
                        url: '{{ route('admin.billing.purchase-bill.search-bill-from') }}?type=store',
                        data: function(params) {
                            return {
                                q: params.term
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results.filter(r => r.type === 'store')
                            };
                        }
                    },
                    placeholder: 'Search Store...',
                    minimumInputLength: 3,
                    allowClear: true,
                    templateResult: function(data) {
                        if (data.loading) return data.text;
                        return data.text;
                    }
                });

                // Radio toggle
                $('.bill_from_type').on('change', function() {
                    var type = $(this).val();
                    $('#bill_from_type_selected').val(type);

                    if (type === 'vendor') {
                        $('#vendor_grp').show();
                        $('#store_grp').hide();
                        $('#vendorSelect').trigger('focus');
                    } else {
                        $('#store_grp').show();
                        $('#vendor_grp').hide();
                        $('#storeSelect').trigger('focus');
                    }
                });

                // Show selected type
                $('#vendorSelect, #storeSelect').on('select2:select', function(e) {
                    var data = e.params.data;
                    $('#selected_type_span').text('Selected: ' + data.type.toUpperCase()).show();
                });
            });
        </script>
    @endpush
