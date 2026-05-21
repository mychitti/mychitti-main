 <div class="modal fade" id="inventoryItemModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
     <div class="modal-dialog">
         <div class="modal-content">
             <div class="modal-header">
                 <h5 class="modal-title" id="exampleModalLabel">Select Items from Inventory</h5>
                 <button type="button" class="close inv_modal_close" data-dismiss="modal" aria-label="Close">
                     <span aria-hidden="true">&times;</span>
                 </button>
             </div>
             <div class="modal-body">
                 <div class="inventory_section">
                     <div class="inventory_section_inner">
                         <select name="inventory_items[]" id="inventory_items" class="form-control js-select2-custom"
                             multiple="multiple">
                             
                             @php
                                 use Illuminate\Support\Str;
                             @endphp

                             @if (!Str::endsWith(request()->route()->getName(), 'vendor.task.add') && !Str::endsWith(request()->route()->getName(), 'vendor.invoice.my-bills') )
                                 <option value="add_new">&#43; Add New Item</option>
                             @endif
                             @php $inventory_items = _getInventoryItems(); @endphp
                             @foreach ($inventory_items as $key => $item)
                                 <option data-image="https://via.placeholder.com/40x40?text=A"
                                     value="{{ $item->id }}">{{ $item->item_name }}
                                 </option>
                             @endforeach
                         </select>
                     </div>
                 </div>
             </div>
             <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                 <button type="button" onclick="add_inv_items()" class="btn btn-primary inv_item_add_btn">Add</button>
             </div>
         </div>
     </div>
 </div>
