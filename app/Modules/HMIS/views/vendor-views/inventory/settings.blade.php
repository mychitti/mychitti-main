@extends('layouts.vendor.app')

@section('title', 'Purchase Settings')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        }
        .ck.ck-reset {
            width: 100% !important;
        }
    </style>
@endpush

@section('content') 
    <div class="content container-fluid">
        @include('hmis::vendor-views.partials._pharmacy_header')
        <div class="pharmacy-page-content">
        <!-- Page Header -->
        <div class="page-header"> 
            <h1 class="page-header-title">
                <a href="javascript:history.back()" class="mr-2" style="color: inherit;" title="Back">
                    <i class="tio-chevron-left"></i>
                </a>
                Pharmacy Settings
            </h1>
        </div>
        <!-- End Page Header -->

        <div class="row g-2">
            <form class="w-100 p-0" action="{{ route('vendor.inventory.settings-save') }}" method="post">
                @csrf
                <div class="col-md-12">
                    <div class=" h-100">
                        <div class="">
                            <div class="form-row">
                                <label for="">Terms and Conditions</label>
                                <textarea name="content" id="editor2">{{ $tnc ? $tnc->terms_n_conditons : '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pharmacy dispensing rule --}}
                <div class="col-md-12 mt-4">
                    <div class="card">
                        <div class="card-header"><h4 class="card-header-title"><i class="tio-medicine mr-1"></i> Pharmacy</h4></div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <label class="mb-0 font-weight-bold">Medicines should be given to whoever brings the prescription</label>
                                    <div class="text-muted" style="font-size:12px;">
                                        When enabled, the pharmacy may dispense/sell against a prescription to any person presenting it
                                        (not restricted to the patient). This is shown as guidance on the dispense &amp; walk-in screens.
                                    </div>
                                </div>
                                <label class="toggle-switch ml-3 mb-0" for="pharmacy_dispense_to_bearer">
                                    <input type="checkbox" class="toggle-switch-input" id="pharmacy_dispense_to_bearer"
                                        name="pharmacy_dispense_to_bearer" value="1" {{ !empty($pharmacyDispenseToBearer) ? 'checked' : '' }}>
                                    <span class="toggle-switch-label"><span class="toggle-switch-indicator"></span></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end w-100">
                    <button class="btn btn-primary my-2">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#editor2'));
    </script>
@endpush
