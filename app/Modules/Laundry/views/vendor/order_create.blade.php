@extends('layouts.vendor.app')
@section('title', 'New Laundry Order')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex align-items-center justify-content-between">
        <h1 class="page-header-title"><span class="page-header-icon"><i class="tio-add"></i></span> New Walk-in Order</h1>
        <a href="{{ route('vendor.laundry.orders') }}" class="btn btn-outline-secondary"><i class="tio-arrow-backward"></i> Back</a>
    </div>

    <form action="{{ route('vendor.laundry.orders.store') }}" method="POST" id="orderForm">
        @csrf
        <div class="row">
            {{-- Customer Info --}}
            <div class="col-md-5">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Customer</h5></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Existing Customer</label>
                            <select name="store_customer_id" id="customer_id"
                                class="form-control"
                                data-placeholder="Search customer or leave blank for guest">
                                <option></option>
                            </select>
                        </div>
                        <input type="hidden" name="customer_name" id="customerName">
                        <input type="hidden" name="customer_phone" id="customerPhone">
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Order Info</h5></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Drop Date <span class="text-danger">*</span></label>
                            <input type="date" name="drop_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Expected Ready Date</label>
                            <input type="date" name="expected_ready_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Special instructions..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Items --}}
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Items</h5>
                        <button type="button" class="btn btn-sm btn--primary" id="addItemRow"><i class="tio-add"></i> Add Item</button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless table-align-middle mb-0" id="itemsTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width:35%">Item</th>
                                        <th style="width:15%">Qty</th>
                                        <th style="width:20%">Rate</th>
                                        <th style="width:20%">Amount</th>
                                        <th style="width:10%"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemRows">
                                </tbody>
                                <tfoot>
                                    <tr class="thead-light">
                                        <td colspan="3" class="text-right font-weight-bold">Total</td>
                                        <td colspan="2"><strong id="grandTotal">0.00</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn--primary btn-block">Save Order</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Item row template --}}
<template id="itemRowTemplate">
    <tr class="item-row">
        <td>
            <select name="items[__IDX__][inventory_item_id]" class="form-control form-control-sm item-select" data-placeholder="Select or type...">
                <option value="">-- Custom --</option>
                @foreach($items as $item)
                    <option value="{{ $item->id }}" data-name="{{ $item->item_name }}" data-price="{{ $item->selling_price }}">
                        {{ $item->item_name }} — ₹{{ number_format($item->selling_price,2) }}
                    </option>
                @endforeach
            </select>
            <input type="text" name="items[__IDX__][item_name]" class="form-control form-control-sm item-name mt-1" placeholder="Item name" required>
            <input type="text" name="items[__IDX__][notes]" class="form-control form-control-sm mt-1" placeholder="Note (optional)">
        </td>
        <td><input type="number" name="items[__IDX__][qty]" class="form-control form-control-sm item-qty" value="1" min="1" required></td>
        <td><input type="number" name="items[__IDX__][rate]" class="form-control form-control-sm item-rate" value="0" step="0.01" min="0" required></td>
        <td><span class="item-amount">0.00</span></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="tio-delete"></i></button></td>
    </tr>
</template>
@endsection

@push('script_2')
<script>
    var rowIndex = 0;

    function addRow() {
        var tpl = document.getElementById('itemRowTemplate').innerHTML.replace(/__IDX__/g, rowIndex++);
        $('#itemRows').append(tpl);
        var row = $('#itemRows tr:last');
        row.find('.item-select').select2({ placeholder: 'Select item or leave custom' });
        row.find('.item-select').on('change', function () {
            var opt = $(this).find(':selected');
            if (opt.val()) {
                row.find('.item-name').val(opt.data('name'));
                row.find('.item-rate').val(opt.data('price')).trigger('input');
            }
        });
        bindRowCalc(row);
    }

    function bindRowCalc(row) {
        row.find('.item-qty, .item-rate').on('input', function () {
            var qty  = parseFloat(row.find('.item-qty').val()) || 0;
            var rate = parseFloat(row.find('.item-rate').val()) || 0;
            row.find('.item-amount').text((qty * rate).toFixed(2));
            updateTotal();
        });
    }

    function updateTotal() {
        var total = 0;
        $('.item-amount').each(function () { total += parseFloat($(this).text()) || 0; });
        $('#grandTotal').text(total.toFixed(2));
    }

    $(document).on('click', '.remove-row', function () {
        $(this).closest('tr').remove();
        updateTotal();
    });

    $('#addItemRow').on('click', addRow);

    $(document).on('change', '#customer_id', function () {
        var val = $(this).val();
        if (!val || val === 'add_new') return;
        var text = $(this).find(':selected').text();
        var phoneMatch = text.match(/\(([^)]+)\)\s*$/);
        var namePart   = text.replace(/\s*\|.*$/, '').replace(/\s*\([^)]*\)\s*$/, '').trim();
        if (phoneMatch) $('#customerPhone').val(phoneMatch[1]);
        if (namePart)   $('#customerName').val(namePart);
    });

    $(document).on('submit', '.customer_add_form', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('form_type', 'ajax');
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                if (data.status && data.customer) {
                    var c = data.customer;
                    var text = (c.f_name || '') + (c.l_name ? ' ' + c.l_name : '') +
                               (c.user_type ? ' | ' + c.user_type : '') +
                               ' (' + (c.phone || '') + ')';
                    var option = new Option(text, c.id, true, true);
                    $('#customer_id').append(option).trigger('change');
                    $('#customerName').val(c.f_name + (c.l_name ? ' ' + c.l_name : ''));
                    $('#customerPhone').val(c.phone || '');
                    $('#addCustomerModal').modal('hide');
                    toastr.success(data.msg || 'Added successfully');
                }
            }
        });
    });

    addRow();
</script>
@endpush
