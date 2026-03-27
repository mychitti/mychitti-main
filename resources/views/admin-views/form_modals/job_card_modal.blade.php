<div class="modal fade" id="addJobCardModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Add Job Card</h5>
                <button type="button" class="close close_jc" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @include('vendor-views.forms.job_card_add')
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary done_jc" >Save</button>
            </div>
        </div>
    </div>
</div>
