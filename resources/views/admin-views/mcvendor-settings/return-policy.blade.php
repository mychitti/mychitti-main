@extends('layouts.admin.app')

@section('title', 'MCVendorHub Return Policy')

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title mr-3">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/business.png') }}" class="w--26" alt="">
                </span>
                <span>MCVendorHub — Return Policy</span>
            </h1>
            @include('admin-views.mcvendor-settings.partials.nav-menu')
        </div>

        <div class="row g-3">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.business-settings.mcvendor-return-policy') }}" method="post" id="ck_editor_form2">
                            @csrf
                            <div class="form-group lang_form" id="default-form">
                                <textarea class="ck_editor2 form-control" name="return_policy_for_mc_vendor">{!! $return_policy_for_mc_vendor?->getRawOriginal('value') ?? '' !!}</textarea>
                            </div>
                            <div class="btn--container justify-content-end">
                                <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&v=3.45.8"></script>
    @include('vendor-views/multiple_ck_editor')
@endpush
