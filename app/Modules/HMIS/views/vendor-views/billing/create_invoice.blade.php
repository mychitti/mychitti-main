@extends('layouts.vendor.app')

@section('title', translate('messages. Generate Bill (Advanced)'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/create_invoice.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush

@section('content')
    <div id="toast" class="toast">This is a toaster notification!</div>
    <div class="container">
        <!-- Top Navigation Bar -->
        <div class="top-bar">
            <div class="breadcrumb-custom">
                <h2>Create Invoice</h2>
            </div>
            <div class="top-actions">
                <a href="{{ route('vendor.invoice.manual-bill') }}" class="btn btn-light d-none d-md-block">
                    <i class="tio-document-text"></i> Create Basic Invoice
                </a>
                <button class="btn btn-outline-dark action-btn submit_btn d-block d-md-none" type="button"><i
                        class="fas fa-share-square"></i></button>
                <button class="btn--primary btn submit_btn d-none d-md-block" type="button"><i
                        class="fas fa-share-square"></i> Save and Share</button>
            </div>
        </div>
        <div class="alert_elem" style="display:none">
            <div class="alert alert-warning alert-dismissible fade show gst_alert" role="alert">
                <strong>Oops!</strong> GST billing is currently unavailable. Please update your GST details <a
                    class="btn btn-outline-dark" href="{{ route('vendor.business-settings.store-setup') }}">Update</a>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>

        <div class="main-content">

            <form id="invoice_form" method="post" enctype="multipart/form-data"
                action="{{ route('vendor.invoice.save-new-manual-invoice') }}">
                @csrf <!-- Main Content -->
                <div class="content-area">


                    <!-- Invoice Header -->
                    <div class="invoice-header">
                        <div class="invoice-info">
                            <div class="info-group">
                                <label>Bill Type</label>
                                <div class="btn-group btn-group-toggle m-0" style="    margin: 2px auto;"
                                    data-toggle="buttons">
                                    @if (App\CentralLogics\Helpers::get_store_data()->gst &&
                                            json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                                        <label class="btn btn-responsive btn-outline-primary ">
                                            <input type="radio" class="tax_type" value="gst" name="tax_type"
                                                id="option1"> GST
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
                            </div>
                            <div class="info-group gst_inclusion_wrap gst_fld" style="display:none;">
                                <label>Tax Inclusion</label>
                                <select id="global_gst_status" name="global_gst_status" class="form-control">
                                    <option value="excluding">Excluding GST</option>
                                    <option value="including">Including GST</option>
                                </select>
                            </div>
                            <div class="info-group">
                                <label>Invoice Number</label>
                                <div class="gst_invoice_num gst_fld">
                                    <div class="input-group">
                                        <span class="input-group-text invoice_prefix bg-white"
                                            style="border-right: none; padding-right: 0; ">{{ $bill_num['prefix'] }}</span>
                                        <input type="number" style="width:50px !important; padding-left:0px !important"
                                            onkeyup="validateInvoiceNum(this, 'gst')"
                                            class="custom-input invoice_num gst_field" id="editableInput"
                                            value="{{ $bill_num['number'] }}">
                                    </div>
                                    <span class="text-danger invoice_error"></span>
                                </div>
                                <input type="hidden" name="prefixe" value="{{ $bill_num['prefix'] }}">
                                <input type="hidden" name="number" id="hiddenInvoiceNumber">
                                <div class="nongst_invoice_num nongst_fld">

                                    <div class="input-group">
                                        <span class="input-group-text invoice_prefix2 bg-white"
                                            style="border-right: none; padding: 6px 8px;
                                    padding-right: 0; ">{{ $bill_num['nongst_prefix'] }}</span>
                                        <input type="number"
                                            style="border-left: none;width:50px !important; padding-left:0px !important"
                                            onkeyup="validateInvoiceNum(this, 'non_gst')" class=" invoice_num non_gst_field"
                                            id="editableInput" checked value="{{ $store?->non_gst_sno }}">
                                    </div>

                                    {{-- 

                                        <div class="input-group">
                                            <input type="number" onkeyup="validateInvoiceNum(this, 'non_gst')" 
                                                class=" invoice_num non_gst_field" id="editableInput" name="invoice_serial"
                                                value="{{ $bill_num['non_gst_sno'] }}">
                                        </div> --}}


                                    <span class="text-danger invoice_error"></span>
                                </div>
                            </div>
                            <div class="info-group">
                                <label>Invoice Date</label>
                                <input type="date" name="invoice_date" id="invoice_date" value="{{ date('Y-m-d') }}"
                                    onchange="fetchInvoiceIdForDate(this.value)">
                            </div>
                            @php
                                $paid_unpaid_option = \App\CentralLogics\Helpers::get_store_data()->storeConfig?->paid_unpaid_options ?? 'paid_unpaid';
                            @endphp
                            @if($paid_unpaid_option !== 'paid_only')
                                <div class="info-group">
                                    <label>Payment Status</label>
                                    <div class="btn-group btn-group-toggle m-0" data-toggle="buttons">
                                        <label class="btn btn-responsive btn-outline-primary active">
                                            <input type="radio" class="payment_stts" checked value="Paid"
                                                name="payment_stts" id="option1payment"> Paid
                                        </label>

                                        {{-- @php
                                        $billingDisabled = !_isSubmoduleEnabled('3')['enabled']
                                            ? 'disabled title="Enable advanced billing for creating unpaid bills and payment reminders"'
                                            : '';
                                    @endphp --}}
                                        @php
                                            $billingDisabled = '';
                                        @endphp
                                        <label class="btn btn-responsive btn-outline-primary">
                                            <input {{ $billingDisabled }} type="radio" class="payment_stts"
                                                value="Unpaid" name="payment_stts" id="option3payment">
                                            Unpaid
                                        </label>
                                    </div>

                                </div>
                            @else
                                <input type="hidden" name="payment_stts" value="Paid">
                            @endif
                            <div class="info-group payment_date_inp" style="display:none;">
                                <label for="payment_date_inp">Payment Date</label>
                                <input id="payment_date_inp" value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}"
                                    name="payment_date" type="date">
                            </div>
                            <div class="info-group reminder_date_inp" style="display:none;">
                                <label for="inputreminder_date">Reminder Start Date</label>
                                <input id="inputreminder_date" value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}"
                                    name="reminder_date" type="date">
                            </div>
                            <div class="info-group reminder_date_inp " style="display:none;">
                                <label for="inputGroupFile04">Reminder Frequency</label>
                                <div class="input-group">
                                    <input type="number" value="1" style="   height:36px;" name="reminder_freq"
                                        class="form-control" id="inputGroupFile04"
                                        aria-describedby="inputGroupFileAddon04" aria-label="Upload">
                                    <select name="reminder_freq_unit" style=" height:36px;" class="form-control">
                                        <option value="week">Week</option>
                                        <option value="day">Day</option>
                                        <option value="hour">Hour</option>
                                        <option value="month">Month</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="font-weight-bold d-block mb-2">Custom Headers</label>
                        <div id="custom-buttons">
                            <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                data-label="Vehicle No">+ Vehicle No</button>
                            <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                data-label="PO Number">+
                                PO Number</button>
                            <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                data-label="Challan No.">+ Challan No.</button>
                            <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                data-label="Delivery Date">+ Delivery Date</button>
                            <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                data-label="Sales Person">+ Sales Person</button>
                            <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                data-label="Dispatch Number">+ Dispatch Number</button>
                            <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                data-label="Other">+ Other</button>
                        </div>
                    </div>


                    <!-- Where dynamic fields appear -->
                    <div id="custom-fields"></div>

                    <!-- Customer Section -->
                    <div class="customer-section mt-2">
                        <div class="section-header">
                            <span>📋 Bill To</span>
                            <button class="btn p-0" type="button" id="add_cust_btn" style="font-size: 11px;">+ Add
                                Client</button>
                        </div>
                        <div class="section-content">
                            <div class="customer-grid row">
                                <div class="upgrade-card cust_det_card col-md-3 col-sm-6" style="display:none">
                                    <div class="close-btn card_close_btn">&times;</div>
                                    <div class="customer_info">

                                    </div>
                                </div>

                                <div class="info-group customer_select_grp with_add_new col-md-3 col-sm-6">
                                    <div class="customer_elem_inner">
                                        <label>Client</label>
                                        <select name="bill_to" id="customer_id" class="js-select2-custom ">
                                            <option selected disabled>Select Client</option>
                                            <option value="add_new">&#43; Add New Client</option>
                                            @foreach ($customers as $cust)
                                                <option data-type="{{ $cust->user_type }}" value="{{ $cust->id }}">
                                                    {{ $cust->phone . ' | ' . $cust->f_name . ' | ' . ucfirst($cust->user_type) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <span class="text-success user_type_show"></span>
                                    </div>

                                </div>
                                <div class="info-group col-md-3 col-sm-6">
                                    <label>Comment</label>
                                    <textarea name="customer_comment" placeholder="Ex: add comment to this client" style="height: 46px;"
                                        class="form-control" id=""></textarea>
                                </div>
                                <div class="info-group col-md-3 col-sm-6">
                                    <label>Reference</label>
                                    <textarea name="reference" placeholder="p.o number, sales person names...." style="height: 46px;"
                                        class="form-control" id=""></textarea>
                                </div>
                                {{-- <div class="info-group"> 
                                    <label>Phone</label>
                                    <input type="number" placeholder="99********">
                                </div>
                                <div class="info-group">
                                    <label>Address</label>
                                    <input type="text" placeholder="Enter address">
                                </div> --}}
                            </div>
                        </div>
                    </div>

                    @include('vendor-views.form_modals.inventory_item_select')


                    <!-- Items Section -->
                    <div class="items-section">
                        <div class="section-header">
                            <span>📋 Invoice Items</span>
                            <div class="d-none d-lg-block">

                                <button type="button" class="btn btn--primary" onclick="addMoreRow()">+ Add
                                    Item</button>
                                @if (_isSubscription())
                                    <button type="button" class="btn btn--primary" data-toggle="modal"
                                        data-target="#inventoryItemModal">+ Add From {{ _moduleLabel('inventory') }}</button>
                                @endif
                            </div>
                            <div class=" d-block d-lg-none">

                                <button type="button" class="btn text-primary p-1" onclick="addMoreRow()">+ Add
                                    Item</button>
                                @if (_isSubscription())
                                    <button type="button" class="btn text-primary p-1" data-toggle="modal"
                                        data-target="#inventoryItemModal">+ Add From {{ _moduleLabel('inventory') }}</button>
                                @endif
                            </div>
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
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="items-section mr-0">
                                <div class="section-header">
                                    <span>Additional Charges</span>

                                </div>
                                <div class="d-flex justify-content-end">
                                    <table class="items-table charges_table" style="max-width: 100%">
                                        <thead class="add_chrg_head">
                                            <tr>
                                                <th></th>
                                                <th>Charges</th>
                                                <th class="gst_fld hidden_gst_f">Tax (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody class=" ">
                                            <tr class="add_chrg_row">
                                                <td>
                                                    Delivery / Shipping Charges (+)
                                                </td>
                                                <td>
                                                    <label class="small_label">Charges</label><input type="hidden"
                                                        name="charges_name[]" value="delivery_charges">
                                                    <input type="number"
                                                        class="form-control small_field additional_charges"
                                                        name="add_charges[]" placeholder="Ex: 90">
                                                </td>
                                                <td class="gst_fld hidden_gst_f">
                                                    <label class="small_label">Tax</label>
                                                    <input class="form-control small_field charges_tax" type="number"
                                                        name="charges_tax[]" placeholder="Ex: 12">
                                                </td>
                                            </tr>
                                            <tr class="add_chrg_row">
                                                <td>
                                                    Packaging Charges (+)
                                                </td>
                                                <td>
                                                    <label class="small_label">Charges</label><input type="hidden"
                                                        name="charges_name[]" value="packing_charges">
                                                    <input class="form-control small_field additional_charges"
                                                        type="number" name="add_charges[]" placeholder="Ex: 90">
                                                </td>
                                                <td class="gst_fld hidden_gst_f">
                                                    <label class="small_label">Tax</label>
                                                    <input class="form-control small_field charges_tax" type="number"
                                                        name="charges_tax[]" placeholder="Ex: 12">
                                                </td>
                                            </tr>
                                            <tr class="row2">
                                                <td>
                                                    <button type="button"
                                                        class="btn btn-outline-primary btn-sm more_additional_charges">+</button>
                                                </td>
                                                <td>
                                                </td>
                                                <td>
                                                </td>
                                            </tr>

                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="customer-section ml-0 border-0">
                                @php $gst_enabled = App\CentralLogics\Helpers::get_store_data()->gst && json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status; @endphp
                                @if ($gst_enabled)
                                    <p class="nongst_fld">Available
                                        Tax features: TDS, TDS under GST, TCS</p>
                                @else
                                    <p class="nongst_fld"><a class="text-underline"
                                            href="{{ route('vendor.business-settings.store-setup') }}">Available</a>
                                        Tax features: TDS, TDS under GST, TCS</p>
                                @endif
                                @if ($gst_enabled)
                                    <div id="gst_section" class="gst_fld hidden_gst_f">
                                        <div class="tcs-header">
                                            <label class="font-weight-bold">Apply tax to Invoice</label>
                                            <div class="tcs-checkbox-group">
                                                {{-- <label><input type="checkbox" class="tax_rate" value="cess">
                                                        CESS</label>
                                                    <label><input type="checkbox" class="tax_rate" value="surcharge">
                                                        Surcharge</label> --}}
                                                <label><input type="checkbox" class="tax_rate" value="tds">
                                                    TDS</label>
                                                <label><input type="checkbox" class="tax_rate" value="tds_gst"> TDS
                                                    under GST</label>
                                                <label><input type="checkbox" class="tax_rate" value="tcs">
                                                    TCS</label>
                                            </div>
                                        </div>

                                        <div class="row tcs-controls ">

                                            {{-- style="display:none" --}}
                                            <div class="col-md-9 tds_list tax_elem my-1" style="display:none">
                                                <select name="tds_rate_id" id="tds_rate_id"
                                                    class="form-control js-select2-custom tcs-select ">
                                                    <option selected>Select TDS Rate</option>
                                                    @foreach ($data['tax_rates_tds'] as $key => $value)
                                                        <option value="{{ $value->id }}"
                                                            data-value="{{ $value->rate }}">
                                                            {{ $value->rate . ' ' . $value->section . ' ' . $value->description }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6 tds_under_gst_elem tax_elem my-1" style="display:none">
                                                <input name="tds_under_gst" readonly value="2" id="tds_gst_rate"
                                                    class="form-control">
                                            </div>
                                            <div class="col-md-6 tcs_list tax_elem my-1" style="display:none">
                                                <select name="tcs_rate_id" id="tcs_rate_id"
                                                    class="form-control js-select2-custom tcs-select ">
                                                    <option selected>Select TCS Rate</option>

                                                    @foreach ($data['tax_rates_tcs'] as $key => $value)
                                                        <option value="{{ $value->id }}"
                                                            data-value="{{ $value->rate }}">
                                                            {{ $value->rate . ' ' . $value->section . ' ' . $value->description }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="visible-taxes row mt-2">
                                            <div class="col-md-6 cess_inp pr-1" style="display:none;">
                                                <input name="cess" type="number" id="cess_inp" class="form-control"
                                                    placeholder="CESS (%)">
                                            </div>
                                            <div class="col-md-6 surcharge_inp pl-1" style="display:none;">
                                                <input name="surcharge" type="number" id="surcharge_inp"
                                                    class="form-control" placeholder="Surcharge (%)">
                                            </div>
                                        </div>
                                    </div>

                                @endif

                                <div class="tcs-total-box">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="tcs-label-small">Extra Discount</span>
                                        <div class="tcs-input-group" style="width: 150px;">
                                            <select name="total_discount_type" class="total_discount_type form-control">
                                                <option value="percent">%</option>
                                                <option value="amount">₹</option>
                                            </select>
                                            <input type="number" name="total_discount"
                                                class="form-control total_discount" value="0">
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-6 tcs-label-small gst_fld hidden_gst_f">Taxable Amount</div>
                                        <div class="col-md-6 text-right gst_fld hidden_gst_f">₹ <span
                                                class="taxable_amount_fld">0.00</span>
                                        </div>
                                        <div class="col-md-6 tcs-label-small gst_fld hidden_gst_f">Tax Amount</div>
                                        <div class="col-md-6 text-right gst_fld hidden_gst_f">₹ <span
                                                class="total-tax-field">0.00</span>
                                        </div>
                                        <div class="col-md-6 tcs-label-small">Round Off</div>
                                        <div class="col-md-6 text-right">
                                            0.00
                                        </div>
                                        <div class="col-md-6 tcs-label-small tcs_amount_label gst_fld2 hidden_gst_f">
                                            TCS Amount</div>
                                        <div class="col-md-6 text-right tcs_amount_fld gst_fld2 hidden_gst_f">0.00
                                        </div>
                                        <div class="col-md-6 tcs-label-small gst_fld2 cess_amount_label hidden_gst_f">
                                            CESS Amount</div>
                                        <div class="col-md-6 text-right cess_amount_fld gst_fld2 hidden_gst_f">0.00
                                        </div>
                                        <div class="col-md-6 tcs-label-small gst_fld2 surcharge_amount_label hidden_gst_f">
                                            Surcharge Amount</div>
                                        <div class="col-md-6 text-right surcharge_amount_fld gst_fld2 hidden_gst_f">
                                            0.00</div>
                                        <div class="col-md-6 tcs-label-small gst_fld2 tds_amount_label hidden_gst_f">
                                            TDS Amount</div>
                                        <div class="col-md-6 text-right tds_amount_fld gst_fld2 hidden_gst_f">0.00
                                        </div>
                                        <div class="col-md-6 tcs-label-small gst_fld2 tds_gst_amount_label hidden_gst_f">
                                            TDS Under GST Amount
                                        </div>
                                        <div class="col-md-6 text-right tds_gst_amount_fld gst_fld2 hidden_gst_f">0.00
                                        </div>
                                        <div class="col-md-12 row  g-0">

                                        </div>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <strong>Total Amount</strong>
                                        <span class="invoice-total-amount">₹ 0.00</span>
                                        <input type="hidden" name="inv_total_amount"
                                            class="invoice-total-amount-hidden">
                                    </div>
                                    <div class="d-flex justify-content-between tcs-label-small mt-2">
                                        <span>Total Discount</span>
                                        <span>₹ <span class="total_discount_fld">0.00</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 account_section">
                            <!-- Select Search Section -->
                            <div class="customer-section mr-0 account_section_inner">
                                <div class="section-header">
                                    <span>Add Bank to Invoice (Optional)</span>
                                </div>
                                <div class="section-content p-0">
                                    <table class="items-table charges_table" style="max-width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Bank Name</th>
                                                <th>Account Number</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody class="">
                                            @foreach ($accounts as $key => $account)
                                                <tr>
                                                    <td>{{ $account->bank_name }} </td>
                                                    <td> {{ $account->account_number }}</td>
                                                    <td>
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text">
                                                                <input {{ $key == count($accounts) - 1 ? 'checked' : '' }}
                                                                    type="checkbox" name="bank_account"
                                                                    class="bank_account" value="{{ $account->id }}"
                                                                    aria-label="Checkbox for following text input">
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach

                                        </tbody>
                                    </table>
                                    @if (hasPermission('billing_bank_account', 'add'))
                                        <div
                                            class="border border--primary d-flex p-2 align-items-center justify-content-center">
                                            <a class="text-primary fw-bold" type="button" data-toggle="modal"
                                                data-target="#accountModal">
                                                + Add New Account</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 sign_section">
                            <div class="customer-section ml-0 sign_section_inner" style="background: #ffeef8;">
                                <div class="section-header">
                                    <span>Add Signature to Invoice (Optional)</span>
                                </div>
                                <div class="d-flex flex-column align-items-center justify-content-center p-2"
                                    style="min-height: 84px;">
                                    <select name="sign" id="sign_id2" class="js-select2-custom">
                                        <option>No Signature</option>
                                        @if (hasPermission('billing_signatures', 'add'))
                                            <option value="add_new">&#43; Add New Signature</option>
                                        @endif
                                        @foreach ($singatures as $key => $sign)
                                            <option {{ $key == count($singatures) - 1 ? 'selected' : '' }}
                                                value="{{ $sign->id }}">
                                                {{ $sign->staff_id == 0 ? 'Self' : $sign->employee?->f_name . ' ' . $sign->employee?->l_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div id="selected_sign_preview">
                                        @if (isset($sign))
                                            <img width="200px" style="margin:5px 0"
                                                src="{{ asset('storage/app/public/store/signature/') . '/' . $sign->image }}"
                                                alt="sign">
                                        @endif
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Select Search Section -->
                            <div class="customer-section mr-0">
                                <div class="accordion" id="accordionExample">
                                    <div class="card">
                                        <div class="card-header p-1" id="headingOne">
                                            <h2 class="mb-0 w-100">
                                                <button
                                                    class="btn btn-link btn-block text-left d-flex justify-content-between"
                                                    type="button" data-toggle="collapse" data-target="#collapseOne"
                                                    aria-expanded="true" aria-controls="collapseOne">
                                                    Terms and Conditions
                                                    <span><i class="tio-arrow-drop-down-circle"
                                                            style="font-size:25px;"></i></span>
                                                </button>
                                            </h2>
                                        </div>

                                        <div id="collapseOne" class="collapse " aria-labelledby="headingOne"
                                            data-parent="#accordionExample">
                                            <div class="card-body px-0">
                                                @if (hasPermission('billing_tnc', 'add'))
                                                    <p class="mx-2">Add new Terms & Conditions – <a
                                                            href="{{ route('vendor.business-settings.terms-and-conditions') }}">Add</a>
                                                    </p>
                                                @endif
                                                <div class="mb-2">
                                                    <select name="tnc_id" id="tnc_id"
                                                        class="js-select2-custom px-3 mb-2">
                                                        <option value="">None</option>
                                                        {{-- <option value="add_new">&#43; Add New Terms and Conditions</option> --}}
                                                        @foreach ($data['store_tncs'] as $key => $tnc)
                                                            <option value="{{ $tnc->id }}">
                                                                {{ $tnc->tnc_for }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <textarea name="terms_and_conditions" id="ckeditor2"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="customer-section p-2 mr-0" style="  background: #fff4f0;">
                                <h4 class="mb-0">Coming Soon</h4>
                                <p class="mb-3"><i>In the meantime, check back soon for updates.</i></p>
                                <label style="width:fit-content;" class="toggle-switch toggle-switch-sm my-2"
                                    for="stocksCheckbox">
                                    <input disabled type="checkbox" class="toggle-switch-input " id="stocksCheckbox"
                                        hidden>
                                    <span class="toggle-switch-label mx-auto">
                                        <span class="toggle-switch-indicator"></span>
                                    </span> Create E-Waybill
                                </label>
                                <label style="width:fit-content;" class="toggle-switch toggle-switch-sm my-2"
                                    for="stocksCheckbox2">
                                    <input disabled type="checkbox" class="toggle-switch-input " id="stocksCheckbox2"
                                        hidden>
                                    <span class="toggle-switch-label mx-auto">
                                        <span class="toggle-switch-indicator"></span>
                                    </span> Create E-invoice
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="customer-section ml-0">
                                <div class="section-header mb-0">
                                    <span>Payment Options</span>
                                </div>
                                <div class="section-content p-0">
                                    <div class="payment_mode_grp">
                                        <table class="items-table charges_table" style="max-width: 100%">
                                            <thead class="charges_row_head">
                                                <tr>
                                                    <th>Payment Mode</th>
                                                    <th class="partial_payment" style="display:none;">Online Amt</th>
                                                    <th class="partial_payment" style="display:none;">Cash Amt</th>
                                                </tr>
                                            </thead>
                                            <tbody class=" charges_row_parent">
                                                <tr>
                                                    <td>
                                                        <div class="pos--payment-options">
                                                            <ul class="mb-0 flex-nowrap">
                                                                <li>
                                                                    <label>
                                                                        <input type="radio" name="payment_mode"
                                                                            value="Cash" hidden checked>
                                                                        <span>Cash</span>
                                                                    </label>
                                                                </li>
                                                                <li>
                                                                    <label>
                                                                        <input type="radio" name="payment_mode"
                                                                            value="Online" hidden>
                                                                        <span>Online</span>
                                                                    </label>
                                                                </li>
                                                                <li>
                                                                    <label>
                                                                        <input type="radio" name="payment_mode"
                                                                            value="Cash and Online" hidden>
                                                                        <span>Both</span>
                                                                    </label>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                    <td class="partial_payment" style="display:none;">
                                                        <input name="online_amount" class=" online_amount form-control"
                                                            placeholder="Ex : 1200" id="">
                                                    </td>
                                                    <td class="partial_payment" style="display:none;">
                                                        <input name="cash_amount" class=" cash_amount form-control"
                                                            placeholder="Ex : 1200" id="">
                                                    </td>
                                                </tr>

                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="payment_mode_info p-4" style="display:none">
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center">
                                            Payment options available when bill type is 'Paid'</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="top-actions d-flex justify-content-end  mb-2 bottom-btns" style="">
                        {{-- <button class="btn btn-outline-dark action-btn d-block d-md-none" type="button"><i
                                class="fas fa-eye"></i></button>
                        <button class="btn btn-light d-none d-md-block" type="button">View as Client</button>
                        <button class="btn btn-outline-dark action-btn d-block d-md-none" type="button"> <i
                                class="fas fa-save"></i></button>
                        <button class="btn btn-light d-none d-md-block" type="button">Save as Draft</button> --}}
                        <span class="text-danger amount_error"></span>
                        <button class="btn btn-dark btn-sm submit_btn d-block d-md-none" type="button"><i
                                class="fas fa-share-square"></i> Save &
                            Share</button>
                        <button class="btn--primary btn submit_btn d-none d-md-block" type="button"> <i
                                class="fas fa-share-square"></i> Save and
                            Share</button>
                    </div>
                    <!-- Fixed Bottom Totals Section -->
                </div>
            </form>
            @include('vendor-views/business-settings/partials/_add_bank_account')
            @include('vendor-views/business-settings/partials/_add_new_sign')

            @include('vendor-views/form_modals/inventory_item_add')

        </div>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
    @include('vendor-views/billing/create_invoice_js')
    {{-- <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/create_invoice.js"></script> --}}
    <script>
        // Ensure correct invoice number section shown on page load
        $(document).ready(function() {
            if (typeof recalculateInvoice === 'function') recalculateInvoice();
        });

        // When user switches GST/Non-GST after date was already changed, apply correct cached serial
        $(document).on('change', '.tax_type', function() {
            var isGst = $(this).val() === 'gst';
            var gstSerial = $('.invoice_num.gst_field').data('fy-serial');
            var ngstSerial = $('.invoice_num.non_gst_field').data('fy-serial');
            if (isGst && gstSerial) {
                $('.invoice_num.gst_field').val(gstSerial);
            } else if (!isGst && ngstSerial) {
                $('.invoice_num.non_gst_field').val(ngstSerial);
            }
        });
    </script>

    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#editor'));
    </script>
    <script>
        $('#inventory_items').select2({
            templateResult: formatOption,
            templateSelection: formatOption
        });

        function formatOption(option) {
            if (!option.id) return option.text;

            let image = $(option.element).data('image');
            return $(`
        <span>
            <img src="${image}" style="width: 30px; height: 30px; border-radius: 4px; margin-right: 8px;" />
            ${option.text}
        </span>
    `);
        }
    </script>
    <script>
        function fetchInvoiceIdForDate(date) {
            if (!date) return;
            $.get('{{ route('vendor.invoice.get-invoice-id-for-date') }}', {
                date: date
            }, function(res) {
                var isGst = $('.tax_type:checked').val() === 'gst';
                if (isGst) {
                    // GST
                    $('.invoice_prefix').text(res.prefix);
                    $('input[name="prefixe"]').val(res.prefix);
                    $('.invoice_num.gst_field').val(res.number);
                } else {
                    // Non-GST
                    $('.invoice_num.non_gst_field').val(res.non_gst_serial);
                }
                // Always keep hidden fields in sync too
                $('.invoice_num.gst_field').data('fy-serial', res.number);
                $('.invoice_num.non_gst_field').data('fy-serial', res.non_gst_serial);

                @php
                    $fyYear = date('m') >= 4 ? date('Y') : date('Y') - 1;
                    $currentFy = $fyYear % 100 . '-' . ($fyYear + 1) % 100;
                @endphp
                var currentFy = '{{ $currentFy }}';
                if (res.fy !== currentFy) {
                    if (!$('#fy_warning').length) {
                        $('#invoice_date').after(
                            '<small id="fy_warning" class="text-warning ml-1"><i class="tio-warning"></i> FY ' +
                            res.fy + ' — serial updated.</small>');
                    } else {
                        $('#fy_warning').text('\u26A0 FY ' + res.fy + ' — serial updated.');
                    }
                } else {
                    $('#fy_warning').remove();
                }
            });
        }
    </script>
@endpush
