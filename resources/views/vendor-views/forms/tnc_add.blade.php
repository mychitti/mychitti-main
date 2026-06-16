<style>
    .btn-outline-primary.active {
        background-color: #00868f !important;
    }
</style>
<form class="customer_add_form" enctype="multipart/form-data" class="w-100"
    action="{{ route('vendor.business-settings.tnc.save') }}" method="post">
    @csrf
    <div class="">
        @if (Illuminate\Support\Str::endsWith(request()->url(), 'invoice-settings') ||
                Illuminate\Support\Str::endsWith(request()->url(), 'create-invoice') || 
                    Route::currentRouteName() == 'vendor.invoice.settings'
                )
            <input type="hidden" name="tnc_type" value="invoice">
        @else
            <input type="hidden" name="tnc_type" value="quotation">
        @endif
        <input type="hidden" name="for" id="tnc_id" value="bill">

        {{-- <div class="form-row col-md-12 ">
            <label for="">For</label>
            <input type="text" required name="for" class="form-control" placeholder="Ex: Staff">
        </div> --}}
        <div class="">
            <textarea name="tnc_content" rows="5" id="ckeditor"></textarea>
        </div>
        <div class="d-flex justify-content-end w-100">
            <button class="btn btn-primary mt-2">Save</button>
        </div>
    </div>
</form>
