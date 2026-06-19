<div class="modal fade" id="itemEntryModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Item Entry</h5>
                <button type="button" class="close inv_close_btn" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-2">
                @include('vendor-views.forms.inventory_item_entry')
            </div>
        </div>
    </div>
</div>
