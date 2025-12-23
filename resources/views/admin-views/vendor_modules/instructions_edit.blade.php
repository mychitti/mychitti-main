@extends('layouts.admin.app')

@section('title', translate('messages.vendor_modules'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('/public/assets/admin/css/intlTelInput.css') }}" />
    @include('admin-views/partials/_ckeditor_form_submit')
@endpush



@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/store.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ $vendor_module->name }}
                </span>
            </h1>
        </div>

        <!-- End Page Header -->
        <form action="{{ route('admin.business-settings.vendor-module.update') }}" method="post"
            enctype="multipart/form-data" id="about_us-form" class="js-validate">
            @csrf

            <div class="row g-2">
                <div class="card shadow--card-2">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="name">Name</label>
                                <input type="text" placeholder="Name" name="name" value="{{$vendor_module->name}}" class="form-control my-1">
                            </div>
                            <div class="col-md-4">
                                <label  for="name">Image <span>@if($vendor_module->image)<a class="text-underline" href="{{asset('storage/app/public/vendor_login/') . '/' . $vendor_module->image}}">Current Image</a>@endif</span></label>
                                <input type="file" name="image" class="form-control my-1">
                            </div>
                            <div class="col-md-4">
                                <label  for="name">Image 2 <span>@if($vendor_module->image2)<a class="text-underline" href="{{asset('storage/app/public/vendor_login/') . '/' . $vendor_module->image2}}"> Image 2</a>@endif</span></label>
                                <input type="file" name="image2" class="form-control my-1">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="name">Page Content</label>
                            <input type="hidden" name="module_id" value="{{ $vendor_module->id }}">
                            <textarea id="editor" class=" form-control" name="content">{!! $vendor_module?->content ?? '' !!}</textarea>
                        </div>
                    </div>
                    <div class="btn--container justify-content-end">
                        <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
                    </div>
                </div>
            </div>

        </form>
    </div>

@endsection

@push('script_2')
@endpush
