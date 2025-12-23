@extends('layouts.admin.app')

@section('title', translate('messages.Vendor Homepage Config'))

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
                    {{ translate('messages.Vendor Homepage Config') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form action="{{ route('admin.business-settings.vendor-homepage-config.update') }}" method="post" id="tnc-form">
                    @csrf

                    <div class="form-group row ">
                        <div class="col-md-4">
                        <label for="">First Line</label>
                        <input type="text" value="{{ $lines->where('key', 'mc_first_line')->first()->value ?? '' }}" class="form-control" placeholder="First Line" name="first_line">
                        </div>
                        <div class="col-md-4">
                        <label for="">Second Line</label>
                        <input type="text" value="{{ $lines->where('key', 'mc_second_line')->first()->value ?? '' }}" class="form-control" placeholder="Second Line" name="second_line">
                        </div>
                        <div class="col-md-4">
                        <label for="">Third Line</label>
                        <input type="text" value="{{ $lines->where('key', 'mc_third_line')->first()->value ?? '' }}" class="form-control" placeholder="Third Line" name="third_line">
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
