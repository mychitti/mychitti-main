@php $description_attributes = $description_attributes ?? []; @endphp
<div class="product_elem col-md-8 my-2 mx-3"
    style="background: #fffdf6; border-radius: 10px; border: 2px dashed #e2e2e2;">
    <div class="mb-2 d-flex justify-content-between align-items-center">
        <label class="font-weight-bold mb-0">Description Attributes</label>
        <button type="button" class="btn btn-outline-primary btn-sm" id="add-desc-attr">
            <i class="fas fa-plus-circle mr-1"></i>Add Attribute
        </button>
    </div>
    <small class="text-muted d-block mb-2">These attributes are printed under the product in quotations &amp; bills
        (e.g. Material: Stainless Steel, Warranty: 2 Years).</small>

    <div id="desc-attr-fields">
        @foreach ($description_attributes as $dLabel => $dValue)
            <div class="form-group desc-attr-row">
                <div class="d-flex">
                    <input type="text" class="form-control mr-2" name="desc_attr_label[]"
                        placeholder="Attribute (e.g. Material)" value="{{ $dLabel }}">
                    <input type="text" class="form-control mr-2" name="desc_attr_value[]"
                        placeholder="Value (e.g. Stainless Steel)" value="{{ $dValue }}">
                    <a type="button" class="text-danger remove-desc-attr">
                        <i class="tio-delete-outlined"></i>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>

@once
    <script>
        (function () {
            function descAttrRow() {
                return '<div class="form-group desc-attr-row"><div class="d-flex">' +
                    '<input type="text" class="form-control mr-2" name="desc_attr_label[]" placeholder="Attribute (e.g. Material)">' +
                    '<input type="text" class="form-control mr-2" name="desc_attr_value[]" placeholder="Value (e.g. Stainless Steel)">' +
                    '<a type="button" class="text-danger remove-desc-attr"><i class="tio-delete-outlined"></i></a>' +
                    '</div></div>';
            }
            document.addEventListener('click', function (e) {
                var addBtn = e.target.closest && e.target.closest('#add-desc-attr');
                if (addBtn) {
                    var holder = document.getElementById('desc-attr-fields');
                    if (holder) holder.insertAdjacentHTML('beforeend', descAttrRow());
                    return;
                }
                var rem = e.target.closest && e.target.closest('.remove-desc-attr');
                if (rem) {
                    var row = rem.closest('.desc-attr-row');
                    if (row) row.remove();
                }
            });
        })();
    </script>
@endonce
