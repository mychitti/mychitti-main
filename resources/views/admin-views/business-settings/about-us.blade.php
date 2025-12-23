@extends('layouts.admin.app')

@section('title',translate('messages.about_us'))

@push('css_or_js')
@include('admin-views/partials/_ckeditor_form_submit')
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/privacy-policy.png')}}" class="w--26" alt="">
                </span>
                <span>
                    {{translate('messages.about_us')}}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form action="{{route('admin.business-settings.about-us')}}" method="post" id="about_us-form">
                    @csrf
                    <div class="lang_form" id="default-form">
                        <div class="form-group">
                            <label for="about_title">{{ translate('messages.about_title') }}</label>
                            <input type="text" id="about_title" name="about_title[]" class="form-control"
                              value="{{ $about_title?->getRawOriginal('value') ?? '' }}" >
                        </div>

                        <div class="form-group">
                            <label for="about_us">{{ translate('messages.about_us_description') }}</label>
                            <textarea id="editor" class=" form-control" name="about_us[]">{!! $about_us?->getRawOriginal('value') ?? '' !!}</textarea>
                        </div>
                        <input type="hidden" name="lang[]" value="default">
                    </div>
                    <div class="btn--container justify-content-end">
                        <button type="submit" class="btn btn--primary">{{translate('messages.submit')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    {{-- <script src="{{asset('public/assets/admin/ckeditor/ckeditor.js')}}"></script> --}}
@endpush
