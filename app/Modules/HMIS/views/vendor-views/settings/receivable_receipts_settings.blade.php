@extends('layouts.vendor.app')

@section('title', 'Receivable Receipts Settings')

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
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Receivable Receipts Settings</h1>
        </div>
        <!-- End Page Header -->

        <div class="">
            <form class="w-100 p-0 " enctype="multipart/form-data"
                action="{{ route('vendor.business-settings.config.save') }}" method="post">
                @csrf
                <div class="card mb-1">
                    <div class="card-header">
                        <h2 class="card-title h4">
                            <i class="tio-settings"></i>
                            <span>{{ translate('messages.configuration') }}</span>
                        </h2>
                    </div>

                    <!-- Body -->
                    <div class="card-body row align-items-end">
                        <div class="col-md-6 p-2 mb-3">
                            <div class="form-group mb-0 ">
                                <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                    for="returnable_rr_tnc">
                                    <span>Terms and Conditions (Returnable Receivable Receipts)  {{$store?->returnable_rr_tnc . ' 9 '}}   <span
                                            class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                            data-original-title="{{ translate('messages.if enabled, terms and conditions will show in returnable receivable receipts') }}"><img
                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"></span></span>
                                            <input type="hidden" name="returnable_rr_tnc" value="0">  
                                    <input type="checkbox" class="toggle-switch-input" name="returnable_rr_tnc"
                                        id="returnable_rr_tnc" value="1"
                                        {{ $store?->returnable_rr_tnc ? 'checked' : '' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                                <div class="form-row ">
                                    <textarea name="returnable_rr_tnc_content" id="ckeditor2">{{ $store?->returnable_rr_tnc_content ?? '' }}</textarea>
                                </div>

                            </div>
                        </div>
                        <div class="col-md-6 p-2 mb-3">
                            <div class="form-group mb-0 ">

                                <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                    for="non_returnable_rr_tnc">
                                    <span>Terms and Conditions (Non-Returnable Receivable Receipts)<span
                                            class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                            data-original-title="{{ translate('messages.if enabled, terms and conditions will show in non-returnable receivable receipts') }}"><img
                                                src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"></span></span>
                                            <input type="hidden" name="non_returnable_rr_tnc" value="0">  

                                    <input type="checkbox" class="toggle-switch-input" name="non_returnable_rr_tnc"
                                        id="non_returnable_rr_tnc" value="1"
                                        {{ $store?->non_returnable_rr_tnc ? 'checked' : '' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                                <div class="form-row ">
                                    <textarea name="non_returnable_rr_tnc_content" id="ckeditor">{{ $store?->non_returnable_rr_tnc_content ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12  mb-3">
                            <button style="float:right" class="btn btn-primary my-2">Update</button>
                        </div>
                    </div>
                </div>



            </form>

        </div>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>

    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#editor'));
        ClassicEditor
            .create(document.querySelector('#ckeditor'));
        ClassicEditor
            .create(document.querySelector('#editor2'));
        $("#coverImageUpload").change(function() {
            readURL(this, "coverImageViewer");
        });

        let ckeditorInstance = null;

        if (!ckeditorInstance) {
            ClassicEditor
                .create(document.querySelector('#ckeditor2'))
                .then(editor => {
                    ckeditorInstance = editor;
                })
                .catch(error => {
                    console.error(error);
                });
        }


        $(".edit_tnc").on('click', function() {
            var id = $(this).attr('data-id');
            var tnc_for = $('.for_' + id).text().trim();
            var content = $('.content_' + id).html();

            if (ckeditorInstance) {
                ckeditorInstance.setData(content);
            }

            $('.edit_for').val(tnc_for);
            $('.tnc_edit_id').val(id);
        });
    </script>
@endpush
