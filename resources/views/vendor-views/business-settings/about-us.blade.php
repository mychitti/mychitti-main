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
            <form class="w-100 p-0" id="about_us-form" action="{{ route('vendor.business-settings.about-us.save') }}" method="post">
                @csrf

                <div class="col-md-12">
                    <div class="form-row ">
                        <textarea placeholder="Start Typing ..."  id="editor" class="form-control" name="content" >{!! $about_us ?? '' !!}</textarea>
                    </div>
                    <input type="hidden" class="upload_url" value="{{route('vendor.business-settings.image-upload')}}">
                    <button class="btn btn-primary my-2">Update</button>
                </div>
            </form> 


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
