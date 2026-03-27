<style>
    .btn-outline-primary.active {
        background-color: #00868f !important;
    }
</style>
<form class="customer_add_form" enctype="multipart/form-data" class="w-100"
    action="{{ route('admin.business-settings.tnc.save') }}" method="post">
    @csrf
    <div class="row align-items-end">
            @if (Illuminate\Support\Str::endsWith(request()->url(), 'invoice-settings') ||
                    Illuminate\Support\Str::endsWith(request()->url(), 'create-invoice') || 
                     Route::currentRouteName() == 'admin.invoice.settings'
                    )
                <input type="hidden" name="tnc_type" value="invoice">
            @else
                <input type="hidden" name="tnc_type" value="quotation">
            @endif
        <div class="form-row col-md-12 ">
            <label for="">For</label>
            <input type="text" required name="for" class="form-control" placeholder="Ex: Staff">
        </div>
        <div class="form-row col-md-12">
            <textarea name="tnc_content" id="ckeditor"></textarea>
        </div>
        <div class="d-flex justify-content-end w-100">
            <button class="btn btn-primary">Save</button>
        </div>
    </div>
</form>
