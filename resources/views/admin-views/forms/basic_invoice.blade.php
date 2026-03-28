  <!--  <style>
    .hidden_field {
        display: none;
    }
</style>-->
<form class="w-100 row g-0 invoice-form calc-form"
    action="{{ isset($task) ? route('admin.billing.save-manual-invoice-lead', [$task->id]) : route('admin.billing.save-manual-invoice') }}"
    method="post">
    @csrf
    <div class="col-md-12 p-1">
        <div class="card inv__card">
            <div class="card-body inv__body">
                <input type="hidden" id="service_id" name="service_id" value="">

                <!-- Row 1: Invoice Details -->
                <div class="row mb-2 inv__top-row">

                    <!-- Invoice ID -->
                    <div class="col-md-2 inv__field">
                        <label class="form-check-label d-flex inv__label" for="flexRadioDefault32">Invoice Id</label>
                        <input type="hidden" name="prefixe" value="{{ $bill_num['prefix'] }}">
                        <div class="gst_invoice_num" style="display:none;">
                            <div class="input-group input-group-sm inv__id-group">
                                <span class="input-group-text invoice_prefix inv__id-prefix"
                                    style="border-right: none; padding-right: 0;">{{ $bill_num['prefix'] }}</span>
                                <input type="number" onkeyup="validateInvoiceNum(this, 'gst')"
                                    class="form-control form-control-sm custom-input invoice_num gst_field inv__id-input"
                                    id="editableInput" value="{{ $bill_num['number'] }}">
                            </div>
                            <span class="text-danger invoice_error"></span>
                        </div> 
                        <div class="nongst_invoice_num">
                            <div class="input-group input-group-sm inv__id-group">
                                <span class="input-group-text invoice_prefix2 bg-white inv__id-prefix"
                                    style="border-right: none; padding: 6px 8px; padding-right: 0;">{{ $bill_num['nongst_prefix'] }}</span>
                                <input type="number" onkeyup="validateInvoiceNum(this, 'non_gst')"
                                    class="form-control form-control-sm custom-input invoice_num non_gst_field inv__id-input"
                                    id="editableInput" name="number" value="{{ $bill_num['number'] }}">
                            </div>
                            <span class="text-danger invoice_error"></span>
                        </div>
                    </div>

                    <!-- Bill To Toggle -->
                    <div class=" inv__field" style="justify-content: flex-end; padding-bottom: 2px;">
                        <label class="inv__label">Bill To</label>
                        <div class="inv__bill-toggle">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" value="user" checked id="customRadioInline1" name="bill_to_type"
                                    class="custom-control-input bill_to_type inv__radio-opt">
                                <label class="custom-control-label inv__radio-lbl"
                                    for="customRadioInline1">Customer</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" value="vendor" id="customRadioInline2" name="bill_to_type"
                                    class="custom-control-input bill_to_type inv__radio-opt">
                                <label class="custom-control-label inv__radio-lbl"
                                    for="customRadioInline2">Store</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" value="mychitti_client" id="customRadioInline3"
                                    name="bill_to_type" class="custom-control-input bill_to_type inv__radio-opt">
                                <label class="custom-control-label inv__radio-lbl" for="customRadioInline3">Mychitti
                                    Client</label>
                            </div>
                        </div>
                    </div>

                    <!-- Customer / Store select -->
                    <div class="col-md-2 inv__field" style="flex-direction: column; min-width: 220px;">
                        <div class="form-check " style="padding: 0;">
                            <div id="customer_list">
                                <label class="form-check-label inv__label" for="flexRadioDefault2">Customer</label>
                                <select name="bill_to" id="userSelect" class="form-control inv__select"
                                    data-type="customer">
                                    <option value=""></option> 
                                    <option value="add_new">Add New</option> 
                                </select> 
                            </div>
                            <div style="display:none;" id="store_list">
                                <label class="form-check-label inv__label" for="flexRadioDefault2">Store</label>
                                <select name="" id="vendorSelect" class="form-control  inv__select"
                                    data-type="store">
                                    <option value=""></option>
                                </select>
                            </div>
                            <div style="display:none;" id="mychitti_client_list">
                                <label class="form-check-label inv__label" for="flexRadioDefault2">Mychitti
                                    Client</label>
                                <select name="" id="customer_id" 
                                    class="form-control inv__select mychitti_client_id" data-type="mychitti_client">
                                    <option value=""></option>
                                    <option value="add_new">Add New</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Date -->
                    <div class="col-md-2 inv__field">
                        <label class="form-check-label d-flex inv__label" for="flexRadioDefault2">Invoice Date</label>
                        <input type="date" name="invoice_date" class="form-control form-control-sm inv__input"
                            value="2026-03-12">
                    </div>

                    <!-- Reference Number -->
                    <div class="col-md-2 inv__field">
                        <label class="form-check-label inv__label " for="flexRadioDefault2">
                            Reference Number <i class="tio-info-outined" title="Eg. Bank Transaction Id"></i>
                        </label>
                        <select name="reference_number[]" data-placeholder="Reference Number"
                            class="js-example-tags inv__tags-select" multiple></select>
                    </div>

                    <!-- Tax Type -->
                    <div class="col-md-2 inv__field">
                        <label class="d-block mb-1  inv__label">Tax Type</label>
                        <div class="d-flex d-flex border rounded inv__radio-group p-1">
                            <div class="form-check mr-3 form-check-inline inv__radio-row">
                                <input class="form-check-input tax_type" checked value="gst" name="tax_type"
                                    type="radio" id="gstRadio1">
                                <label class="form-check-label small inv__radio-text" for="gstRadio1">GST</label>
                            </div>
                            {{-- <div class="form-check form-check-inline inv__radio-row">
                                <input class="form-check-input tax_type" value="non-gst" name="tax_type"
                                    type="radio" id="gstRadio2" checked>
                                <label class="form-check-label small inv__radio-text" for="gstRadio2">Non GST</label>
                            </div> --}}
                        </div>
                    </div>

                    <!-- Payment Status -->
                    <div class="col-md-2 inv__field">
                        <label class="d-block mb-1  inv__label">Payment Status</label>
                        <div class="d-flex d-flex border rounded inv__radio-group p-1">
                            <div class="form-check mr-3 form-check-inline inv__radio-row">
                                <input class="form-check-input" value="Paid" name="payment_stts" type="radio"
                                    id="payment_sttsRadio1" checked>
                                <label class="form-check-label small inv__radio-text"
                                    for="payment_sttsRadio1">Paid</label>
                            </div>
                            <div class="form-check form-check-inline inv__radio-row">
                                <input class="form-check-input" value="Unpaid" name="payment_stts" type="radio"
                                    id="payment_sttsRadio2">
                                <label class="form-check-label small inv__radio-text"
                                    for="payment_sttsRadio2">Unpaid</label>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Row 2: Payment Method and Dates -->
                <div class="row ">
                    <div class="col-md-3 ">
                        <div class="pos--payment-options" style="display: flex; align-items: center; gap: 8px;">
                            <label class="">Paid By</label>
                            <ul style="list-style:none; margin:0; padding:0; display:flex; gap:6px;">
                                <li>
                                    <label class="">
                                        <input type="radio" class="payment_mode" name="payment_mode"
                                            value="Cash" hidden checked>
                                        <span>Cash</span>
                                    </label>
                                </li>
                                <li>
                                    <label class="">
                                        <input type="radio" class="payment_mode" name="payment_mode"
                                            value="Online" hidden>
                                        <span>Online</span>
                                    </label>
                                </li>
                                <li>
                                    <label class="">
                                        <input type="radio" class="payment_mode" name="payment_mode"
                                            value="Cash and Online" hidden>
                                        <span>Both</span>
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="row col-md-4">
                        <!-- Cash Amount -->
                        <div class="col-md-6  partial_payment" style="display: none; ">
                            <label class="form-check-label mb-1 inv__sub-label" for="flexRadioDefault2">Cash
                                Amount</label>
                            <div class="inv__amount-wrap">
                                <span class="inv__currency-sym">₹</span>
                                <input class="form-control form-control-sm cash_amount inv__amount-input"
                                    name="cash_amount" type="number" placeholder="0.00" step="0.001">
                            </div>
                        </div>

                        <!-- Online Amount -->
                        <div class="col-md-6  partial_payment" style="display: none; ">
                            <label class="form-check-label mb-1 inv__sub-label" for="flexRadioDefault2">Online
                                Amount</label>
                            <div class="inv__amount-wrap">
                                <span class="inv__currency-sym">₹</span>
                                <input class="form-control form-control-sm online_amount inv__amount-input"
                                    name="online_amount" type="number" placeholder="0.00" step="0.001">
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row pt-3">
                    <!-- Payment Date -->
                    <div class="payment_date_inp col-md-3" style="display:none;">
                        <label class="form-check-label mb-1 inv__sub-label">Payment Date</label>
                        <input class="form-control form-control-sm inv__input" name="payment_date" type="date">
                    </div>

                    <!-- Reminder Start Date -->
                    <div class="reminder_date_inp col-md-3" style="display:none;">
                        <label class="form-check-label mb-1 inv__sub-label">Reminder Start Date</label>
                        <input class="form-control form-control-sm inv__input" name="reminder_date" type="date"
                            value="2026-03-12">
                    </div>

                    <!-- Reminder Frequency -->
                    <div class="reminder_date_inp col-md-3 p-1" style="display:none;">
                        <label class="form-check-label mb-1 inv__sub-label">Reminder Frequency</label>
                        <div class="input-group input-group-sm inv__freq-group">
                            <input type="number" value="1" name="reminder_freq"
                                class="form-control form-control-sm inv__freq-num">
                            <span class="inv__freq-sep"></span>
                            <select name="reminder_freq_unit" class="form-control form-control-sm inv__freq-unit">
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

    <div class="row w-100">
        <div class="col-md-9 p-1">
            <div class="card h-100">
                <div class="card-body p-1">
                    <button type="button" class="btn btn-dark btn-sm" onclick="addMoreRow(null, 'inv')">+ Add
                        Item</button>
                    @if (Route::currentRouteName() === 'admin.billing.manual-bill')
                        <button type="button" class="btn btn-dark btn-sm" data-toggle="modal"
                            data-target="#inventoryItemModal">+ Add From Inventory</button>
                    @endif
                    <table class="table">
                        <thead class="" style=" background: #75b8b8; color: white;">
                            <tr>
                                <th scope="col">Item</th>
                                <th scope="col">Price</th>
                                <th scope="col">Qty</th>
                                <th scope="col ">Unit</th>
                                <th class="tax_inp_data hidden_field" scope="col">Tax <i>(in %)</i></th>
                                <th class="hsn_inp hidden_field" scope="col">HSN</th>
                                <th class="hidden_field" scope="col">Taxable</th>
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
                                                type="number" name="item_qty[]" value="1"
                                                placeholder="Quantity" class="form-control qty"></td>
                                        <td style="width:140px;"><select name="item_unit[]" id=""
                                                class="form-control js-select2-custom">
                                                <option value="">-- Unit --</option>
                                                @foreach (\App\Models\Unit::all() as $unit)
                                                    <option value="{{ $unit->id }}">{{ $unit->unit }}</option>
                                                @endforeach
                                            </select></td>
                                        <td style="width: 58px;" class="tax_inp_data hidden_field"><input
                                                value="{{ $value->tax ?? '' }}" type="number" name="item_tax[]"
                                                placeholder="Tax" class="form-control tax hidden_field"></td>
                                        <td style="width: 93px;" class="hsn_inp hidden_hsn"><input type="text"
                                                name="item_hsn[]" placeholder="HSN" class="form-control"></td>

                                        <td style="width: 93px;" class="hidden_field"><input type="text" readonly
                                                placeholder="Taxable"
                                                value="{{ $value->quantity * $value->unitprice }}"
                                                class="form-control item_taxable hidden_field"></td>
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
                    {{-- <p><strong>Total (Without GST): <span class="currency">₹</span><span class="totalWithoutGST"
                                id="totalWithoutGST">0</span></strong></p> --}}
                    <input type="hidden" name="final_total_amount" id="totalWithGSTHidden_inv">
                    <p class="totalWithGSTInp"><strong>Total (With GST): <span
                                class="currency">₹</span><span class="totalWithGST"
                                id="totalWithGST">0</span></strong></p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 d-flex flex-column align-items-end justify-content-end">
        @if(isset($tncs) && $tncs->count() > 0)
        <div class="w-100 mb-2">
            <label class="form-check-label mb-1 small"><strong>Default Terms & Conditions</strong></label>
            <select name="tnc_id" id="tncSelect" class="form-control form-control-sm">
                <option value="">-- None --</option>
                @foreach($tncs as $tnc)
                    <option value="{{ $tnc->id }}" data-content="{{ $tnc->content }}">{{ $tnc->tnc_for }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <span class="text-danger amount_error"></span>
        <button class="btn btn-primary my-2 submit_btn">Generate Bill</button>
    </div>
    </div>

</form>

<style>
    .table td,
    .table th {
        padding: 0.2rem;
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
<style>
    /* ── UNIQUE PREFIX: inv__ ── all new classes use this prefix to avoid conflicts ── */

    *,
    *::before,
    *::after {
        box-sizing: border-box;
    }

    :root {
        --inv-bg: #f0f2f8;
        --inv-surface: #ffffff;
        --inv-border: #e3e7f0;
        --inv-border-focus: #4361ee;
        --inv-text-1: #1a1d2e;
        --inv-text-2: #5a6278;
        --inv-text-3: #9aa0b4;
        --inv-accent: #4361ee;
        --inv-accent-bg: #eef1fd;
        --inv-radius: 12px;
        --inv-radius-sm: 7px;
        --inv-shadow: 0 2px 8px rgba(0, 0, 0, 0.06), 0 8px 32px rgba(0, 0, 0, 0.04);
        --inv-focus-ring: 0 0 0 3px rgba(67, 97, 238, 0.14);
    }


    /* ── Wrapper card ─────────────────────────────── */
    .inv__card {
        background: var(--inv-surface);
        border-radius: var(--inv-radius);
        box-shadow: var(--inv-shadow);
        border: 1px solid var(--inv-border);
        margin: 0 auto;
    }

    .inv__body {
        padding: 20px 24px 24px;
    }

    /* ── Top row: flex layout ─────────────────────── */
    .inv__top-row {
        display: flex;
        align-items: flex-start;
        gap: 0;
        flex-wrap: wrap;
        padding-bottom: 18px;
        border-bottom: 1px solid var(--inv-border);
        margin-bottom: 18px;
    }

    /* Each field cell */
    .inv__field {
        display: flex;
        flex-direction: column;
        padding: 0 10px;
        flex-shrink: 0;
    }

    .inv__field:first-child {
        padding-left: 0;
    }

    .inv__field:last-child {
        border-right: none;
    }

    /* ── Field label style ────────────────────────── */
    .inv__label {
        display: block;
        font-weight: 600;
        letter-spacing: 0.7px;
        margin-bottom: 8px;
        white-space: nowrap;
    }

    /* ── Invoice ID input group ───────────────────── */
    .inv__id-group {
        display: flex;
        align-items: center;
        border: 1.5px solid var(--inv-border);
        border-radius: var(--inv-radius-sm);
        overflow: hidden;
        height: 42px;
        background: var(--inv-surface);
        transition: border-color 0.18s, box-shadow 0.18s;
    }

    .inv__id-group:focus-within {
        border-color: var(--inv-border-focus);
        box-shadow: var(--inv-focus-ring);
    }

    .inv__id-prefix {
        background: #f4f5fb;
        border-right: 1.5px solid var(--inv-border);
        padding: 0 10px;
        height: 100%;
        display: flex;
        align-items: center;
        font-family: 'DM Mono', monospace;
        font-size: 12px;
        font-weight: 500;
        color: var(--inv-text-2);
        white-space: nowrap;
    }

    .inv__id-input {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        padding: 0 10px !important;
        font-family: 'DM Mono', monospace !important;
        font-size: 13px !important;
        font-weight: 500;
        color: var(--inv-text-1) !important;
        background: transparent !important;
        width: 100%;
        height: 100% !important;
    }

    /* ── Bill-to toggle (Customer / Store) ───────── */
    .inv__bill-toggle {
        display: flex;
        gap: 4px;
        background: #f3f4f8;
        border-radius: 8px;
        padding: 7px 3px;
    }

    .inv__bill-toggle .inv__radio-opt {
        display: none;
    }

    .inv__bill-toggle .inv__radio-lbl {
        display: block;
        padding: 5px 14px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        color: var(--inv-text-2);
        cursor: pointer;
        transition: all 0.18s;
        white-space: nowrap;
        margin: 0;
    }

    .inv__bill-toggle .inv__radio-opt:checked+.inv__radio-lbl {
        background: var(--inv-surface);
        color: var(--inv-text-1);
        font-weight: 600;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    }

    /* ── Customer/Store select ────────────────────── */
    .inv__select {
        height: 36px;
        width: 200px;
        border: 1.5px solid var(--inv-border) !important;
        border-radius: var(--inv-radius-sm) !important;
        padding: 0 28px 0 10px !important;
        font-family: 'DM Sans', sans-serif;
        font-size: 13px;
        color: var(--inv-text-1);
        background: var(--inv-surface) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%239aa0b4' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") no-repeat right 10px center !important;
        appearance: none;
        -webkit-appearance: none;
        outline: none;
        transition: border-color 0.18s, box-shadow 0.18s;
        cursor: pointer;
    }

    .inv__select:focus {
        border-color: var(--inv-border-focus) !important;
        box-shadow: var(--inv-focus-ring) !important;
    }

    /* ── Date / text input ────────────────────────── */
    .inv__input {
        height: 36px;
        border: 1.5px solid var(--inv-border) !important;
        border-radius: var(--inv-radius-sm) !important;
        padding: 0 10px !important;
        font-family: 'DM Sans', sans-serif;
        font-size: 13px;
        color: var(--inv-text-1);
        background: var(--inv-surface);
        outline: none;
        transition: border-color 0.18s, box-shadow 0.18s;
    }

    .inv__input:focus {
        border-color: var(--inv-border-focus) !important;
        box-shadow: var(--inv-focus-ring) !important;
    }

    /* ── Reference number tags select ────────────── */
    .inv__tags-select {
        height: 43px;
        width: 180px;
        border: 1.5px solid var(--inv-border) !important;
        border-radius: var(--inv-radius-sm) !important;
        padding: 4px 8px !important;
        font-family: 'DM Sans', sans-serif;
        font-size: 13px;
        color: var(--inv-text-1);
        outline: none;
        transition: border-color 0.18s, box-shadow 0.18s;
    }

    .inv__tags-select:focus {
        border-color: var(--inv-border-focus) !important;
        box-shadow: var(--inv-focus-ring) !important;
    }

    /* ── Inline radio group (Tax / Payment status) ── */
    .inv__radio-group {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .inv__radio-row {
        display: flex;
        align-items: center;
        gap: 7px;
        cursor: pointer;
    }

    .inv__radio-row input[type="radio"] {
        width: 15px;
        height: 15px;
        accent-color: var(--inv-accent);
        cursor: pointer;
        flex-shrink: 0;
        margin: 0;
    }

    .inv__radio-row .inv__radio-text {
        font-size: 12.5px;
        font-weight: 500;
        color: var(--inv-text-2);
    }

    /* ── Bottom row: Paid By ──────────────────────── */
    .inv__bottom-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .inv__paid-label {
        font-size: 11px;
        font-weight: 600;
        color: var(--inv-text-3);
        text-transform: uppercase;
        letter-spacing: 0.7px;
        white-space: nowrap;
        margin-right: 4px;
    }

    /* Payment mode pill buttons */
    .inv__pm-btn {
        display: inline-flex;
        align-items: center;
        padding: 7px 18px;
        border: 1.5px solid var(--inv-border);
        border-radius: 8px;
        font-family: 'DM Sans', sans-serif;
        font-size: 12.5px;
        font-weight: 500;
        color: var(--inv-text-2);
        cursor: pointer;
        transition: all 0.18s;
        background: var(--inv-surface);
        user-select: none;
    }

    .inv__pm-btn:hover {
        border-color: var(--inv-accent);
        color: var(--inv-accent);
        background: var(--inv-accent-bg);
    }

    /* Active state handled by JS adding a class — we style it here */
    .inv__pm-btn--active {
        background: var(--inv-text-1) !important;
        border-color: var(--inv-text-1) !important;
        color: #fff !important;
    }

    /* ── Partial payment amount inputs ────────────── */
    .inv__amount-wrap {
        display: flex;
        align-items: center;
        border: 1.5px solid var(--inv-border);
        border-radius: var(--inv-radius-sm);
        overflow: hidden;
        height: 36px;
        background: var(--inv-surface);
        transition: border-color 0.18s, box-shadow 0.18s;
    }

    .inv__amount-wrap:focus-within {
        border-color: var(--inv-border-focus);
        box-shadow: var(--inv-focus-ring);
    }

    .inv__currency-sym {
        padding: 0 8px;
        font-size: 12px;
        font-weight: 600;
        color: var(--inv-text-3);
        background: #f4f5fb;
        border-right: 1.5px solid var(--inv-border);
        height: 100%;
        display: flex;
        align-items: center;
    }

    .inv__amount-input {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        padding: 0 8px !important;
        font-family: 'DM Mono', monospace !important;
        font-size: 13px !important;
        background: transparent !important;
        width: 100%;
        color: var(--inv-text-1);
    }

    /* ── Partial / date field small label ─────────── */
    .inv__sub-label {
        margin-bottom: 5px;
        display: block;
    }

    /* ── Reminder frequency group ─────────────────── */
    .inv__freq-group {
        display: flex;
        align-items: center;
        border: 1.5px solid var(--inv-border);
        border-radius: var(--inv-radius-sm);
        overflow: hidden;
        height: 36px;
        background: var(--inv-surface);
        transition: border-color 0.18s, box-shadow 0.18s;
    }

    .inv__freq-group:focus-within {
        border-color: var(--inv-border-focus);
        box-shadow: var(--inv-focus-ring);
    }

    .inv__freq-num {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        width: 52px;
        padding: 0 8px !important;
        font-family: 'DM Mono', monospace !important;
        font-size: 13px !important;
        background: transparent !important;
        color: var(--inv-text-1);
        height: 100%;
    }

    .inv__freq-sep {
        width: 1px;
        height: 100%;
        background: var(--inv-border);
        flex-shrink: 0;
    }

    .inv__freq-unit {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        padding: 0 24px 0 8px !important;
        font-family: 'DM Sans', sans-serif;
        font-size: 13px;
        background: #f4f5fb url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%239aa0b4' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") no-repeat right 6px center !important;
        appearance: none;
        -webkit-appearance: none;
        color: var(--inv-text-2);
        height: 100%;
        cursor: pointer;
    }
</style>
