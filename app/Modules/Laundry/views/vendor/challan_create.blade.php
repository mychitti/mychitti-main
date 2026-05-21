@extends('layouts.vendor.app')
@section('title', 'New Challan')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex align-items-center justify-content-between">
        <h1 class="page-header-title"><span class="page-header-icon"><i class="tio-add"></i></span> New Hotel Challan</h1>
        <a href="{{ route('vendor.laundry.challans') }}" class="btn btn-outline-secondary"><i class="tio-arrow-backward"></i> Back</a>
    </div>

    <form action="{{ route('vendor.laundry.challans.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Challan Info</h5></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Hotel / Client <span class="text-danger">*</span></label>
                            <select name="store_customer_id" id="customer_id"
                                class="get_vendor form-control"
                                data-placeholder="Search for Vendor"
                                required>
                                <option></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Outward Date <span class="text-danger">*</span></label>
                            <input type="date" name="outward_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Expected Inward Date</label>
                            <input type="date" name="expected_inward_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Items</h5>
                        <button type="button" class="btn btn-sm btn--primary" id="addItemRow"><i class="tio-add"></i> Add Item</button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless table-align-middle mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width:30%">Item</th>
                                        <th style="width:10%">Prev Balance</th>
                                        <th style="width:10%">Sent Qty</th>
                                        <th style="width:10%">Total</th>
                                        <th style="width:12%">Rate (₹)</th>
                                        <th style="width:13%">Remarks</th>
                                        <th style="width:5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemRows"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn--primary btn-block">Create Challan</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="challanRowTemplate">
    <tr class="item-row">
        <td>
            <select name="items[__IDX__][inventory_item_id]" class="form-control form-control-sm item-select">
                <option value="" data-name="" data-rate="">-- Custom --</option>
                @foreach($items as $item)
                    <option value="{{ $item->id }}" data-name="{{ $item->item_name }}" data-rate="{{ $item->selling_price }}">
                        {{ $item->item_name }}
                    </option>
                @endforeach
            </select>
            <input type="text" name="items[__IDX__][item_name]" class="form-control form-control-sm item-name mt-1" placeholder="Item name" required>
        </td>
        <td><span class="prev-balance text-muted">0</span></td>
        <td>
            <input type="number" name="items[__IDX__][sent_qty]" class="form-control form-control-sm sent-qty" value="0" min="0" required>
        </td>
        <td><span class="row-total text-muted">0</span></td>
        <td><input type="number" name="items[__IDX__][rate]" class="form-control form-control-sm item-rate" value="0" min="0" step="0.01" placeholder="Rate"></td>
        <td><input type="text" name="items[__IDX__][remarks]" class="form-control form-control-sm" placeholder="Remarks"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="tio-delete"></i></button></td>
    </tr>
</template>
@endsection

@push('script_2')
<script>
    var rowIndex = 0;

    function addRow() {
        var tpl = document.getElementById('challanRowTemplate').innerHTML.replace(/__IDX__/g, rowIndex++);
        $('#itemRows').append(tpl);
        var row = $('#itemRows tr:last');

        row.find('.item-select').on('change', function () {
            var opt = $(this).find(':selected');
            if (opt.val()) {
                row.find('.item-name').val(opt.data('name'));
                row.find('.item-rate').val(opt.data('rate') || 0);
            } else {
                row.find('.item-name').val('');
                row.find('.item-rate').val(0);
            }
        });

        row.find('.sent-qty').on('input', function () {
            var prev = parseInt(row.find('.prev-balance').text()) || 0;
            var sent = parseInt($(this).val()) || 0;
            row.find('.row-total').text(prev + sent);
        });
    }

    $(document).on('click', '.remove-row', function () {
        $(this).closest('tr').remove();
    });

    $('#addItemRow').on('click', addRow);
    addRow();

    $(document).on('submit', '.customer_add_form', function(e) {
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
            success: function(data) {
                if (data.status && data.customer) {
                    var c = data.customer;
                    var text = (c.f_name || '') + (c.l_name ? ' ' + c.l_name : '') +
                               (c.user_type ? ' | ' + c.user_type : '') +
                               ' (' + (c.phone || '') + ')';
                    var option = new Option(text, c.id, true, true);
                    $('#customer_id').append(option).trigger('change');
                    $('#addCustomerModal').modal('hide');
                    toastr.success(data.msg || 'Added successfully');
                }
            }
        });
    });
</script>
@endpush
