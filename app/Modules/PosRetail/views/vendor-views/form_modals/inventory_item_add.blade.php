{{-- @php
    $offer = _isSubmoduleEnabled('11')['offer'];
    $pay_warning = _isSubmoduleEnabled('11')['pay_warning'];
    $free_trial = _isSubmoduleEnabled('11')['free_trial'];
@endphp
@if ($free_trial)
    <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize">Free Trial</span>
@endif --}}

<div class="modal fade" id="addInventoryItemModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="exampleModalLabel">Add New Inventory Item</h5>
                    <i class="tio-info-outlined"></i> Marked Fields Are Required
                </div>
                <button type="button" class="close inv_close_btn" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-1">
                <div class="">
                    @include('vendor-views.forms.inventory_item_add')
                </div>

            </div>
        </div>
    </div>
</div>
