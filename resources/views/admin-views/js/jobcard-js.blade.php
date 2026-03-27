<style>


        .hidden_tax {
            display: none;
        }
</style>
<script>
    function deleteJCRow(rowId) {
        $('[data-jc="' + rowId + '"]').remove()
    }

    function addMoreJCRow(item = null) {
        console.log('addMoreJCRow11')
        var $lastItemRow = $('.item_row_jc').last();
        if (!$lastItemRow.length) {
            var dataId = 1;
        } else {
            var dataId = Number($lastItemRow.data('jc')) + 1;
        }
        if (item) {
            item_name = item.item_name;
            price = item.selling_price;
            readonly = 'readonly';
            item_id = item.id;
        } else {
            item_name = '';
            price = '';
            item_id = null;
            readonly = '';
        }
       
            className = '';

        var html = `<tr class="item_row_jc row_` + dataId + `" data-jc="` + dataId + `">
            <input type="hidden" name="inventory_item_id[]" value="` + item_id +
            `"  class="form-control">
                      <td class="py-1"><input type="text" ` + readonly + ` name="name[]" placeholder="Name" value="` +
            item_name + `" class="form-control"></td>
                <td class="py-1"><input type="number" step="0.001" name="price[]" placeholder="Price" value="` +
            price + `" class="form-control">
                </td>
                <td class=" ` + className + ` py-1"><input type="number" name="tax[]" placeholder="Tax" class="form-control">
                </td>
                      <td class="py-1"><input type="number" name="qty[]" placeholder="Qty" class="form-control"></td>
                      <td class="py-1"><button type="button" onclick="deleteJCRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>`;

        $('.rows_parent_jc').append(html)
    }
</script>
