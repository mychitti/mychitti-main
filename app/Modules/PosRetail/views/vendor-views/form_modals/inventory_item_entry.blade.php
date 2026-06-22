<style>
    /* Full-width modal (Bootstrap 4.5.3 has no modal-xxl). Two-class selector outranks
       Bootstrap's .modal-dialog / .modal-xl, so no !important is needed. */
    .modal-dialog.modal-full {
        max-width: 98vw;
        width: 98vw;
        margin: 1rem auto;
    }
</style>
<div class="modal fade" id="itemEntryModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-full">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Stock Management</h5>
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
