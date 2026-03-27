<div class="modal fade" id="addPOSItemModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Add POS Item</h5>
                <button type="button" class="close close_pi" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @include('vendor-views.forms.pos_item_add')
            </div>
        </div>
    </div>
</div>
