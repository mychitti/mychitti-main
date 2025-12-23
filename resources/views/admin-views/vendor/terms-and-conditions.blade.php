@extends('layouts.admin.app')

@section('title', translate('messages.terms_and_condition'))



@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/privacy-policy.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('messages.terms_and_condition') }} (For Store)
                </span>
            </h1>
        </div>

        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form action="{{ route('admin.store.terms-and-conditions.store') }}" method="post">
                    @csrf

                    <div class="form-group ">
                        <textarea class="ckeditor form-control" name="terms_and_conditions">{!! $terms_and_conditions?->getRawOriginal('value') ?? '' !!}</textarea>
                    </div>

                    <div class="btn--container justify-content-end">
                        <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/ckeditor/ckeditor.js') }}"></script>
@endpush
