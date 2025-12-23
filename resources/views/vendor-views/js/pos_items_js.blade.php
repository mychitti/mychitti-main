 <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
 <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
 <script>
     $(document).ready(function() {
         function renderBranchItems() {
             let selectedItems = $('#items').val();
             let selectedBranch = $("#branch").val();
             let container = $('#branchFieldsContainer');
             container.empty();


             if (!selectedBranch || !selectedItems || selectedItems.length === 0) return;

             let selectedOptions = $('#items option:selected');
             if (selectedOptions.length) {
                 $('.items_header').show();
             } else {
                 $('.items_header').hide();
             }

             selectedOptions.each(function() {
                 let itemId = $(this).val();
                 let itemName = $(this).text();

                 container.append(`
                <div class="item-row d-grid align-items-center p-2 mb-2 rounded shadow-sm item_r" >
                    <div class="fw-bold text-truncate">${itemName}</div>
                    <input type="number" id="stock_${itemId}" name="qty[${itemId}]" placeholder="Add to Stock" class="form-control form-control-sm">
                    <input type="number" id="price_${itemId}" name="prices[${itemId}]" placeholder="Price" class="form-control form-control-sm">
                    <input style="display:none;" type="number" id="gst_${itemId}" name="gst[${itemId}]" placeholder="GST" class="form-control form-control-sm">
                </div>`);
             });

             fetchBranchItemData();
         }

         function fetchBranchItemData() {
             let itemIds = $('#items').val();
             let branchId = $('#branch').val();

             if (!branchId || !itemIds || itemIds.length === 0) return;

             $.ajax({
                 url: "{{ route('vendor.pos.product-branch-data') }}",
                 type: 'POST',
                 data: {
                     _token: '{{ csrf_token() }}',
                     item_ids: itemIds,
                     branch_id: branchId
                 },
                 success: function(data) {
                     console.log(data)
                     var gst = false;
                     itemIds.forEach(function(itemId) {
                         if (data.items[itemId]) {
                             $(`#stock_${itemId}`).val(data.items[itemId].stock);
                             $(`#price_${itemId}`).val(data.items[itemId].price);
                             $(`#gst_${itemId}`).val(data.items[itemId].gst_percent);
                           
                         } else {
                             $(`#stock_${itemId}`).val('');
                             $(`#price_${itemId}`).val('');
                             $(`#gst_${itemId}`).val('');
                         }
                     });

                     //hide gst elems if gst not available in branch 
                     if (data.gst_status) {
                         $(".gst_header").show()
                        $("[id^='gst_']").show();
                     } else {
                         $(".gst_header").hide()
                        $("[id^='gst_']").hide();
                     }

                 }
             });
         }

         // 🔹 Bind events
         $('#branch').on('change', renderBranchItems);
         $('#items').on('change', renderBranchItems);
     });


    $(document).ready(function() {
    $('#items').select2({
        placeholder: "Select items",
        language: {
            noResults: function() {
                return "Please add the item to the inventory first, then continue here.";
            }
        }
    });
});

     $(document).ready(function() {
         $('#branch').select2({
             tags: true,
             placeholder: "Select or type branch",
             allowClear: true // optional clear button
         });
     });
 </script>
