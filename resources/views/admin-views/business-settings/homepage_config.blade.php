@extends('layouts.admin.app')

@section('title', translate('messages.Homepage Config'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/privacy-policy.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('messages.Homepage Config') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form action="{{ route('admin.business-settings.homepage-config.update') }}" method="post" id="tnc-form">
                    @csrf

                    <div class="form-group row ">
                        <div class="col-md-4">
                            <label for="">Homepage Availability Text</label>
                            <input type="text" value="{{ $unavailability_heading_homepage?->value ?? '' }}"
                                class="form-control" placeholder="e.g. Tirupati, Chittoor, Madanapalle" name="unavailability_heading_homepage">
                        </div>

                    </div>

                    <div class="btn--container justify-content-end">
                        <button type="submit" class="btn btn--primary">{{ translate('messages.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
@endpush
