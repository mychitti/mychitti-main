@extends('layouts.vendor.app')

@section('title', 'Staff Settings')

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
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Terms and Conditions</h1>
        </div>
        <!-- End Page Header -->

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
    </div>
@endsection

@push('script_2')
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#editor2'));
    </script>
@endpush
