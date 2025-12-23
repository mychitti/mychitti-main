<div class=" mb-3 w_50 row w p-3" style="background: #f6f6f680; margin: 3px;">
    <div class="p-1 col-md-6 my-1">
        <label for="">Service Charges</label>
        <input type="number" name="service_charges" step="0.001" class="form-control" placeholder="Ex: 100">
    </div>
    <div class="p-1 col-md-6 my-1">
        <label for="">Visit Charges</label>
        <input type="number" name="visit_charges" step="0.001" class="form-control" placeholder="Ex: 100">
    </div>
    <div class="p-1 col-md-6 my-1">
        <label for="">Discount (₹)</label>
        <input type="number" name="discount" step="0.001" class="form-control" placeholder="Ex: 100">
    </div>
</div>
{{-- @include('vendor-views.form_modals.inventory_item_select') --}}

<div class=" w_50 w p-3" style="background: #f6f6f680; margin: 3px; ">
    <div class="d-flex w-100 justify-content-end p-2"><button style="width:fit-content; padding: 5px 10px !important"
            type="button" class="btn btn-dark btn-sm action-btn" onclick="addMoreJCRow()">+
            Add More</button>
        @if (_isSubscription())
        @php $attr = Route::currentRouteName() === 'vendor.task.detail' || Route::currentRouteName() === 'vendor.task.subtask.detail' ? 'class' : 'modal'; @endphp
            <button style="width:fit-content; padding: 5px 10px !important" type="button"
                class="btn btn-dark btn-sm action-btn mx-1 {{ $attr == 'class' ? 'add_from_inv' : ''}}" {{$attr == 'modal' ? 'data-toggle=modal data-target=#inventoryItemModal' : ''}}>+
                Add
                From
                Inv.</button>
        @endif
    </div>
    <table class="table">
        <thead class="" style=" background: #b7e1e1ff; ">
            <tr>
                <th scope="col" class="py-1">Title</th>
                <th scope="col" class="py-1">Price</th>
                <th scope="col "class="tax_inp_data hidden_tax py-1">Tax</th>
                <th scope="col" class="py-1">Qty</th>
                <th scope="col" class="py-1"></th>
            </tr>
        </thead>
        <tbody class="rows_parent_jc">
            <tr class="item_row_jc row_1" data-jc="1">
                <input type="hidden" name="inventory_item_id[]" value="" class="form-control">
                <td class="py-1"><input type="text" name="name[]" placeholder="Title" class="form-control">
                </td>
                <td class="py-1"><input type="number" name="price[]" step="0.001" placeholder="Price" class="form-control">
                </td>
                <td class="tax_inp_data hidden_tax py-1"><input type="number" name="tax[]" placeholder="Tax"
                        class="form-control">
                </td>
                <td class="py-1"><input type="number" name="qty[]" placeholder="Qty" class="form-control">
                </td>
                <td class="py-1"><button type="button" onclick="deleteJCRow(1)"
                        class="btn action-btn btn--danger btn-outline-danger"><i
                            class="tio-delete-outlined"></i></button></td>
            </tr>
        </tbody>
    </table>
</div>
<div class=" mb-3 w_50 p-3" style="background: #f6f6f680; margin: 3px;">
    <div id="custom-buttons">
        <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
            data-label="Model Number">+ Model Number</button>
        <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn" data-label="Brand">+
            Brand</button>
        <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn" data-label="Color">+
            Color</button>
        <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn" data-label="Other">+
            Other</button>
    </div>
    <div id="custom-fields"></div>

</div>
<div class="w_50  w p-3" style="background: #f6f6f680; margin: 3px;">
    <div class="p-1 col-md-12 my-1 d-flex">
        @php $methods  = ['cash', 'card', 'upi', 'online']; @endphp
        @foreach ($methods as $key => $value)
            <div class="d-flex align-items-center">
                <input {{ !$key ? 'checked' : '' }} type="radio" name="payment_method" value = "{{ $value }}"
                    class="form-check mx-2" id="check_{{ $value }}">
                <label for="check_{{ $value }}" class="mb-0">{{ ucfirst($value) }}</label>
            </div>
        @endforeach
    </div>
</div>
