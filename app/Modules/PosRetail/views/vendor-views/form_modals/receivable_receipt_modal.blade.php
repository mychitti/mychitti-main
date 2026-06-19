<style>
@media (max-width: 700px) {
    .rr_modal_body{
padding: 5px !important;
    } 
}
</style>
<div class="modal fade" id="addReceivableRModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Add Receivable Receipt</h5>
                <button type="button" class="close close_rr" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body rr_modal_body"> 
                @include('vendor-views.forms.receivable_receipt_add')
            </div>
            <div class="modal-footer"> 
                <button type="button" class="btn btn-primary done_rr" >Save</button>
            </div>
        </div>
    </div>
</div>
