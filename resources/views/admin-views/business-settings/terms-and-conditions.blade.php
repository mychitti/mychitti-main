@extends('layouts.admin.app')

@section('title',translate('messages.terms_and_condition'))



@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/privacy-policy.png')}}" class="w--26" alt="">
                </span>
                <span>
                    {{translate('messages.terms_and_condition')}} (For Customers)
                </span>
            </h1>
        </div>

        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form action="{{route('admin.business-settings.terms-and-conditions')}}" method="post" id="terms_and_conditions-form">
                    @csrf


                    <div class="form-group lang_form" id="default-form">
                        <input type="hidden" name="lang[]" value="default">
                        <textarea class="ckeditor form-control" name="terms_and_conditions[]">{!! $terms_and_conditions?->getRawOriginal('value') ?? '' !!}</textarea>
                    </div>

                    <div class="btn--container justify-content-end">
                        <button type="submit" class="btn btn--primary">{{translate('messages.submit')}}</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/privacy-policy.png')}}" class="w--26" alt="">
                </span>
                <span>
                    {{translate('messages.terms_and_condition')}} (For Deliveryman)
                </span>
            </h1>
        </div>

        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form action="{{route('admin.business-settings.del-terms-and-conditions')}}" method="post" id="">
                    @csrf

                    <div class="form-group lang_form" id="default-form">
                        <textarea class="ckeditor form-control" name="delivery_man_tnc">{!! $del_terms_and_conditions?->getRawOriginal('value') ?? '' !!}</textarea>
                    </div>

                    <div class="btn--container justify-content-end">
                        <button type="submit" class="btn btn--primary">{{translate('messages.submit')}}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/privacy-policy.png')}}" class="w--26" alt="">
                </span>
                <span>
                    {{translate('messages.terms_and_condition')}} (For MC Vendor Hub)
                </span>
            </h1>
        </div>

        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form action="{{route('admin.business-settings.vendorhub-terms-and-conditions')}}" method="post" id="">
                    @csrf
                    <div class="form-group lang_form" id="default-form">
                        <textarea class="ckeditor form-control" name="vendorhub_terms_and_conditions">{!! $vendorhub_terms_and_conditions?->getRawOriginal('value') ?? '' !!}</textarea>
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
    <script src="{{asset('public/assets/admin/ckeditor/ckeditor.js')}}"></script>
@endpush
