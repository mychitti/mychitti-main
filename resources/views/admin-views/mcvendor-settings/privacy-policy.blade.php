@extends('layouts.admin.app')

@section('title', translate('mcvendor_privacy_policy'))


@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title mr-3">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/business.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('messages.mcvendor_privacy_policy') }}
                </span>
            </h1>
            @include('admin-views.mcvendor-settings.partials.nav-menu')
        </div>
        <!-- End Page Header -->


        @php($name = \App\Models\BusinessSetting::where('key', 'business_name')->first())

        <div class="row g-3">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <form action="{{ route('admin.business-settings.privacy-policy') }}" method="post"
                                id="ck_editor_form2">
                                @csrf
                                <div class="form-group lang_form" id="default-form2">
                                    <input type="hidden" name="type" value='for_mc_vendor'>
                                    <input type="hidden" name="lang[]" value="default">
                                    <textarea class="ck_editor2 form-control" name="privacy_policy_for_mc_vendor[]">{!! $privacy_policy_for_mc_vendor?->getRawOriginal('value') ?? '' !!}</textarea>
                                </div>

                                <div class="btn--container justify-content-end">
                                    <button type="submit"
                                        class="btn btn--primary">{{ translate('messages.save_information') }}</button>
                                </div>
                            </form>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('script_2')
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&v=3.45.8">
    </script>
    @include('vendor-views/multiple_ck_editor');
@endpush
