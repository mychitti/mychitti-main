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
                    Add New Vendor Module
                </span>
            </h1>
        </div>

        <!-- End Page Header -->
        <form action="{{ route('admin.business-settings.vendor-module.store') }}" method="post"
            enctype="multipart/form-data" id="about_us-form" class="js-validate">
            @csrf

            <div class="">
                <div class="card shadow--card-2">
                    <div class="card-body">
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="name">Name</label>
                                    <input type="text" placeholder="Name" name="name" class="form-control my-1">
                                </div>
                                <div class="col-md-4">
                                    <label for="name">Image</label>
                                    <input type="file" name="image" class="form-control my-1">
                                </div>
                                <div class="col-md-4">
                                    <label for="name">Image 2</label>
                                    <input type="file" name="image2" class="form-control my-1">
                                </div>
                            </div>
                            <label for="name">Page Content</label>
                            <textarea id="editor" class=" form-control" name="content"></textarea>
                        </div>
                    </div>
                    <div class="btn--container justify-content-end p-2">
                        <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
                    </div>
                </div>
            </div>

        </form>
    </div>
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/store.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('messages.vendor modules') }}
                </span>
            </h1>
        </div>

        <!-- End Page Header -->
        <form action="{{ route('admin.store.store') }}" method="post" enctype="multipart/form-data" class="js-validate"
            id="vendor_form">
            @csrf

            <div class="">
                <div class="">
                    <div class="card shadow--card-2">
                        <div class="card-body d-flex gap-1 flex-wrap">

                            @foreach ($vendor_modules as $key => $value)
                                <a class="p-3 shadow m-1 d-block border card" style="    width: 170px;"
                                    href="{{ route('admin.business-settings.vendor-module.edit', [$value->id]) }}"
                                    class="card">
                                    <img style="width: 100%;"
                                        src="{{ asset('storage/app/public/vendor_login/') . '/' . $value->image }}"
                                        alt="">
                                    <div> {{ $value->name }}</div>
                                </a>
                            @endforeach

                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

@endsection

@push('script_2')
@endpush
