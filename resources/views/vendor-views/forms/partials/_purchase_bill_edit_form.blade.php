@php
    $showMrp = $showMrp ?? false;
    $isGst = $invoice->tax_type == 'gst';
    $gstClass = $isGst ? 'gst_fld' : 'gst_fld hidden_gst_f';
    $isPaid = $invoice->payment_status != 'Unpaid';
    $referenceFile = $invoice->reference_file;
@endphp

<link rel="stylesheet" href="{{ asset('public/assets/admin/css/create_invoice.css') }}">

<form class="w-100 row" action="{{ route('vendor.invoice.purchase-bill.update', [$invoice->id]) }}"
    enctype='multipart/form-data' method="post">
    @csrf
    <div class="col-md-12 p-1">
        <div class="card h-100">
            <div class="row p-2 card-body align-items-center">
                <div class="col-md-4 p-2">
                    <div class="customer_elem_inner">
                        <label>Reference File Upload (Optional)</label>
                        <input type="file" name="file" accept="image/*,application/pdf,.doc,.docx"
                            class="form-control">
                        @if ($referenceFile)
                            <small class="d-block mt-1">
                                Current:
                                <a href="{{ asset('storage/app/public/store/docs/' . $referenceFile) }}"
                                    target="_blank">View File</a>
                                — uploading a new file replaces it.
                            </small>
                        @endif
                    </div>
                </div>
                <div class="col-md-4 p-2">
                    <div class="customer_elem_inner">
                        <label>Vendor</label>
                        <select name="bill_from" id="customer_id" class="get_vendor"
                            data-placeholder="Search for Vendor">
                            @if ($seller)
                                <option value="{{ $seller->id }}" selected>
                                    {{ trim($seller->f_name . ' ' . $seller->l_name) }}
                                    {{ $seller->user_type ? '| ' . $seller->user_type : '' }}
                                    ({{ $seller->phone }})
                                </option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-md-4 p-2">
                    <div class="">
                        <label>Invoice Date</label>
                        <input type="date" name="invoice_date" class="form-control"
                            value="{{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : '' }}">
                    </div>
                </div>
                <div class="col-md-4 p-2">
                    <div class="">
                        <label>Invoice Id</label>
                        <input type="text" name="invoice_id" class="form-control"
                            value="{{ $invoice->invoice_id }}">
                    </div>
                </div>
                <div class="col-md-3 btn-group btn-group-toggle m-0" style="margin: 2px auto;" data-toggle="buttons">
                    @if (App\CentralLogics\Helpers::get_store_data()->gst &&
                            json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                        <label class="btn btn-responsive btn-outline-primary {{ $isGst ? 'active' : '' }}">
                            <input type="radio" class="tax_type" value="gst" name="tax_type"
                                {{ $isGst ? 'checked' : '' }}>
                            GST
                        </label>
                    @else
                        <label onclick="showGSTAlert()" class="btn btn-responsive btn-outline-primary"
                            style="cursor: not-allowed;">
                            <input type="radio" disabled class="tax_type" value="gst" name="tax_type"> GST
                        </label>
                    @endif
                    <label class="btn btn-responsive btn-outline-primary {{ $isGst ? '' : 'active' }}">
                        <input type="radio" class="tax_type" value="non-gst" name="tax_type"
                            {{ $isGst ? '' : 'checked' }}>
                        Non GST
                    </label>
                </div>
                <div class="col-md-4 d-flex">
                    <div class="form-check mr-5 ml-4">
                        <input class="form-check-input" value="Paid" name="payment_stts" type="radio"
                            id="editPaymentPaid" {{ $isPaid ? 'checked' : '' }}>
                        <label class="form-check-label" for="editPaymentPaid">
                            Paid
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" value="Unpaid" name="payment_stts" type="radio"
                            id="editPaymentUnpaid" {{ $isPaid ? '' : 'checked' }}>
                        <label class="form-check-label" for="editPaymentUnpaid">
                            Unpaid
                        </label>
                    </div>
                </div>
                <div class="col-md-12 row">
                    <div class="form-check payment_date_inp col-md-4 col-sm-6 p-1"
                        style="{{ $isPaid ? 'display:none;' : '' }}">
                        <label class="form-check-label">Payment Date</label>
                        <input class="form-control" name="payment_date" type="date"
                            value="{{ $invoice->payment_date ? \Carbon\Carbon::parse($invoice->payment_date)->format('Y-m-d') : '' }}">
                    </div>
                    <div class="form-check reminder_date_inp col-md-4 col-sm-6 p-1"
                        style="{{ $isPaid ? 'display:none;' : '' }}">
                        <label class="form-check-label">Reminder Start Date</label>
                        <input class="form-control" name="reminder_date" type="date"
                            value="{{ $invoice->reminder_date ? \Carbon\Carbon::parse($invoice->reminder_date)->format('Y-m-d') : now()->addDay()->format('Y-m-d') }}">
                    </div>
                    <div class="form-check reminder_date_inp col-md-4 col-sm-6 p-1"
                        style="{{ $isPaid ? 'display:none;' : '' }}">
                        <label class="form-check-label">Reminder Frequency</label>
                        <div class="input-group">
                            <input type="number" value="{{ $invoice->reminder_freq ?? 1 }}" name="reminder_freq"
                                class="form-control">
                            <select name="reminder_freq_unit" class="form-control">
                                @foreach (['week' => 'Week', 'day' => 'Day', 'hour' => 'Hour', 'month' => 'Month'] as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ $invoice->reminder_freq_unit == $value ? 'selected' : '' }}>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12 p-1">
        <div class="card h-100">
            <div class="card-body">
                <div class="">
                    <button type="button" class="btn text-primary p-1" onclick="addMoreRow()">+ Add Item</button>
                    @if (_isSubscription())
                        <button type="button" class="btn text-primary p-1" data-toggle="modal"
                            data-target="#inventoryItemModal">+ Add From Inventory</button>
                    @endif
                </div>
                <table class="items-table" @if ($showMrp) data-mrp="1" @endif>
                    <thead class="items_head">
                        <tr>
                            <th>Description</th>
                            <th>Unit Price</th>
                            @if ($showMrp)
                                <th>MRP</th>
                            @endif
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
                        @foreach ($items as $item)
                            @php
                                $inv = $item->item;
                                $rowId = $loop->iteration;
                                $lineTaxable = (float) $item->price * (float) $item->qty;
                                $lineTotal = $lineTaxable + $lineTaxable * ((float) $item->tax / 100);
                                // Offer every unit of the item's dimension, so a kg item can be
                                // billed in tonnes or grams, plus the item's own legacy secondary —
                                // a pack unit like "bag" belongs to no dimension, so filtering by
                                // dimension alone would drop the unit the item is bought in.
                                // Unclassified items keep the old behaviour: their own unit plus
                                // any legacy secondary.
                                $itemUnit = $inv ? $units->firstWhere('id', $inv->unit) : null;
                                $dimension = $itemUnit->dimension ?? null;
                                if ($dimension) {
                                    $allowedUnits = $units->where('dimension', $dimension)->pluck('id')->all();
                                    if ($inv && $inv->secondary_unit && !in_array($inv->secondary_unit, $allowedUnits)) {
                                        $allowedUnits[] = $inv->secondary_unit;
                                    }
                                } elseif ($inv) {
                                    $allowedUnits = array_values(array_filter([$inv->unit, $inv->secondary_unit]));
                                } else {
                                    $allowedUnits = $units->pluck('id')->all();
                                }
                                if ($item->unit && !in_array($item->unit, $allowedUnits)) {
                                    $allowedUnits[] = $item->unit;
                                }

                                // updatePriceByUnit() rewrites the price from data-primary-price whenever the
                                // unit changes, so seed it as the price in the ITEM's own unit — back-converted
                                // from whatever unit this line was saved in.
                                $primaryPrice = (float) $item->price;
                                $lineUnit = $units->firstWhere('id', $item->unit);
                                if ($dimension && $lineUnit && ($lineUnit->dimension ?? null) === $dimension && (float) $lineUnit->factor > 0) {
                                    $primaryPrice = (float) $item->price / ((float) $lineUnit->factor / (float) $itemUnit->factor);
                                } elseif (
                                    $inv &&
                                    $inv->secondary_unit &&
                                    $item->unit == $inv->secondary_unit &&
                                    (float) $inv->primary_qty > 0 &&
                                    (float) $inv->secondary_qty > 0
                                ) {
                                    $primaryPrice =
                                        (float) $item->price / ((float) $inv->primary_qty / (float) $inv->secondary_qty);
                                }
                            @endphp
                            <tr class="item_row" data-id="{{ $rowId }}"
                                data-secondary-unit="{{ $inv->secondary_unit ?? '' }}"
                                data-primary-qty="{{ $inv->primary_qty ?? 0 }}"
                                data-secondary-qty="{{ $inv->secondary_qty ?? 0 }}"
                                data-primary-price="{{ $primaryPrice }}"
                                data-item-unit="{{ $inv->unit ?? '' }}"
                                data-inventory-stock="{{ $inv ? $inv->stock ?? 0 : '' }}">
                                <input type="hidden" name="inventory_item_id[]" value="{{ $item->inv_id }}"
                                    class="form-control">
                                <input type="hidden" name="invoice_item_new[]" value="1" class="form-control">
                                <td>
                                    <label class="small_label">Item Name</label>
                                    <input required type="text" name="item_name_new[]"
                                        value="{{ $item->name }}" {{ $item->inv_id ? 'readonly' : '' }}
                                        placeholder="Item Name" class="form-control item_name">
                                </td>
                                <td style="width: 100px;">
                                    <label class="small_label">Price</label>
                                    <input type="number" step="0.001" name="item_price_new[]"
                                        value="{{ $item->price }}" placeholder="Price"
                                        class="form-control price item_price">
                                </td>
                                @if ($showMrp)
                                    <td style="width: 100px;">
                                        <label class="small_label">MRP</label>
                                        <input type="number" step="0.001" min="0" name="item_mrp_new[]"
                                            value="{{ $item->mrp }}" placeholder="MRP"
                                            class="form-control item_mrp">
                                    </td>
                                @endif
                                <td style="width: 58px;">
                                    <label class="small_label">Qty</label>
                                    <input type="number" step="any" min="0" name="item_qty_new[]"
                                        value="{{ $item->qty }}" placeholder="Quantity"
                                        class="form-control qty item_qty">
                                </td>
                                <td style="width:140px;">
                                    <label class="small_label">Unit</label>
                                    <select name="item_unit_new[]"
                                        class="form-control js-select2-custom unit_select unit {{ $rowId }}">
                                        <option value="" disabled {{ $item->unit ? '' : 'selected' }}>-- Unit --
                                        </option>
                                        @foreach ($units as $unit)
                                            @if (in_array($unit->id, $allowedUnits))
                                                <option value="{{ $unit->id }}"
                                                    {{ $item->unit == $unit->id ? 'selected' : '' }}>
                                                    {{ $unit->unit }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </td>
                                <td style="width: 58px;" class="tax_inp_data {{ $gstClass }} tax_field">
                                    <label class="small_label">Tax</label>
                                    <input value="{{ $item->tax }}" type="number" name="item_tax_new[]"
                                        placeholder="Tax" class="form-control tax item_tax">
                                </td>
                                <td style="width: 93px;" class="hsn_inp {{ $gstClass }}">
                                    <label class="small_label">HSN</label>
                                    <input value="{{ $item->hsn }}" type="text" name="item_hsn_new[]"
                                        placeholder="HSN" class="form-control">
                                </td>
                                <td style="width: 93px;" class="{{ $gstClass }} item_taxable_td">
                                    <label class="small_label">Taxable</label>
                                    <input type="text" readonly value="{{ number_format($lineTaxable, 3, '.', '') }}"
                                        placeholder="Taxable" class="form-control item_taxable">
                                </td>
                                <td style="width: 93px;">
                                    <label class="small_label">Total</label>
                                    <input type="text" readonly value="{{ number_format($lineTotal, 3, '.', '') }}"
                                        placeholder="Total" class="form-control item_total">
                                </td>
                                <td style="width: 13px;">
                                    <button type="button" onclick="deleteNewRow({{ $rowId }})"
                                        class="btn action-btn btn--danger btn-outline-danger"><i
                                            class="tio-delete-outlined"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="empty-state" style="display:none;">
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
            <a href="{{ route('vendor.invoice.my-bills') }}" class="btn btn-secondary my-2 mr-2">Cancel</a>
            <button class="btn btn-primary my-2">Update Invoice</button>
        </div>
    </div>
</form>
