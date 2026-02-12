@extends('layouts.vendor.app')

@section('title', 'About Us')

@push('css_or_js')
@include('vendor-views/ck_editor_form')
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
            <h1 class="page-header-title"><i class="tio-filter-list"></i>About Us </h1>

        </div>
        <!-- End Page Header -->



        <div class="row g-2">
          

        </div>
    </div>

@endsection

@push('script_2')
    {{-- <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#editor'));
        ClassicEditor
            .create(document.querySelector('#editor2'));
    </script> --}}
@endpush
