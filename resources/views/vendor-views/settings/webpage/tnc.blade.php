   <style>
        .form-row {
            margin-top: 6px;
        }
        .ck.ck-reset {
            width: 100% !important;
        }
    </style>
     <div class="row g-2">
            <form class="w-100 p-0" action="{{ route('vendor.business-settings.common-tnc-save') }}" method="post">
                @csrf
                <div class="col-md-12">
                    <div class=" h-100">
                        <div class="">
                            <div class="form-row">
                                <label for="">Terms and Conditions</label>
                                <textarea name="content" id="editor2">{{ $tAndCContent ? $tAndCContent->terms_n_conditons : '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end w-100">
                    <button class="btn btn-primary my-2">Update</button>
                </div>
            </form>
        </div>