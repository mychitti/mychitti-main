@extends('layouts.admin.app')

@section('title', translate('business_setup'))


@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title mr-3">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/business.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('messages.business_settings') }}
                </span>
            </h1>
            @include('admin-views.business-settings.partials.nav-menu')
        </div>
        <!-- End Page Header -->

       
        <form action="{{ route('admin.business-settings.update-setup') }}" method="post" enctype="multipart/form-data">
            @csrf
            @php($name = \App\Models\BusinessSetting::where('key', 'business_name')->first())

            <div class="row g-3">
                    
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body p-0">
                            
                            <div class="__bg-F8F9FC-card p-0 mt-4">
                                <div class="border-bottom p-3">
                                    <h4 class="card-title m-0 text--title">{{translate('Additional Charge')}}</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-sm-6 col-lg-4">
                                            @php($additional_charge_status = \App\Models\BusinessSetting::where('key', 'additional_charge_status')->first())
                                            @php($additional_charge_status = $additional_charge_status ? $additional_charge_status->value : 0)
                                            <div class="form-group mb-0">
                                                <label
                                                    class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                    <span class="pr-1 d-flex align-items-center switch--label">
                                                        <span class="line--limit-1">
                                                            {{translate('messages.additional_charge') }}
                                                        </span>
                                                        <span class="form-label-secondary text-danger d-flex"
                                                            data-toggle="tooltip" data-placement="right"
                                                            data-original-title="{{ translate('messages.If_enabled,_customers_need_to_pay_an_extra_charge_while_checking_out_orders.')}}"><img
                                                                src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                                alt="{{ translate('messages.customer_varification_toggle') }}"> *
                                                        </span>
                                                    </span>
                                                    <input type="checkbox"
                                                        data-id="additional_charge_status"
                                                        data-type="toggle"
                                                        data-image-on="{{ asset('/public/assets/admin/img/modal/dm-tips-on.png') }}"
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal/dm-tips-off.png') }}"
                                                        data-title-on="<strong>{{ translate('messages.Want_to_enable_additional_charge?') }}</strong>"
                                                        data-title-off="<strong>{{ translate('messages.Want_to_disable_additional_charge?') }}</strong>"
                                                        data-text-on="<p>{{ translate('messages.If_you_enable_this,_additional_charge_will_be_added_with_order_amount,_it_will_be_added_in_admin_wallet') }}</p>"
                                                        data-text-off="<p>{{ translate('messages.If_you_disable_this,_additional_charge_will_not_be_added_with_order_amount.') }}</p>"
                                                        class="status toggle-switch-input dynamic-checkbox-toggle"
                                                        value="1"
                                                        name="additional_charge_status" id="additional_charge_status"
                                                        {{ $additional_charge_status == 1 ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label text">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">
                                            @php($additional_charge_name = \App\Models\BusinessSetting::where('key', 'additional_charge_name')->first())
                                            <div class="form-group mb-0">
                                                <label class="form-label d-flex justify-content-between text-capitalize mb-1"
                                                    for="additional_charge_name">
                                                    <span class="line--limit-1">{{ translate('messages.additional_charge_name') }}
                                                        <small
                                                        class="text-danger"><span class="form-label-secondary"
                                                            data-toggle="tooltip" data-placement="right"
                                                            data-original-title="{{ translate('messages.Set_a_name_for_the_additional_charge,_e.g._“Processing_Fee”.') }}"><img
                                                                src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                                alt="{{ translate('messages.free_over_delivery_message') }}"></span>
                                                        *</small></span>
                                                </label>

                                                <input type="text" name="additional_charge_name" class="form-control"
                                                    id="additional_charge_name"  placeholder="{{ translate('messages.Ex:_Processing_Fee') }}"
                                                    value="{{ $additional_charge_name ? $additional_charge_name->value : '' }}" {{ isset($additional_charge_status) ? '' : 'readonly' }} required>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">
                                            @php($additional_charge = \App\Models\BusinessSetting::where('key', 'additional_charge')->first())
                                            <div class="form-group mb-0">
                                                <label class="form-label d-flex justify-content-between text-capitalize mb-1"
                                                    for="additional_charge">
                                                    <span class="line--limit-1">{{ translate('messages.charge_amount') }}
                                                        ({{ \App\CentralLogics\Helpers::currency_symbol() }}) <small
                                                        class="text-danger"><span class="form-label-secondary"
                                                            data-toggle="tooltip" data-placement="right"
                                                            data-original-title="{{ translate('messages.Set_the_value_(amount)_customers_need_to_pay_as_additional_charge.') }}"><img
                                                                src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                                alt="{{ translate('messages.free_over_delivery_message') }}"></span>
                                                        *</small></span>
                                                </label>

                                                <input type="number" name="additional_charge" class="form-control"
                                                    id="additional_charge"  placeholder="{{ translate('messages.Ex:_10') }}"
                                                    value="{{ $additional_charge ? $additional_charge->value : 0 }}"
                                                    min="0" step=".01" {{ isset($additional_charge_status) ? '' : 'readonly' }}>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="__bg-F8F9FC-card p-0 mt-4">
                                <div class="border-bottom p-3">
                                    <h4 class="card-title m-0 text--title">{{translate('Shipping Charge')}}</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-sm-6 col-lg-4">
                                            @php($minimum_shipping_charge = \App\Models\BusinessSetting::where('key', 'minimum_shipping_charge')->first())
                                            <div class="form-group mb-0">
                                                <label class="form-label text-capitalize"
                                                    for="minimum_shipping_charge">{{ translate('messages.minimum_shipping_charge') }}</label>
                                                <input type="number" name="minimum_shipping_charge" class="form-control"
                                                    id="minimum_shipping_charge" min="0" step=".01"  placeholder="{{ translate('messages.Ex:_10') }}"
                                                    value="{{ $minimum_shipping_charge ? $minimum_shipping_charge->value : 0 }}"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">
                                            @php($per_km_shipping_charge = \App\Models\BusinessSetting::where('key', 'per_km_shipping_charge')->first())
                                            <div class="form-group mb-0">
                                                <label class="form-label text-capitalize"
                                                    for="per_km_shipping_charge">{{ translate('messages.per_km_shipping_charge') }}</label>
                                                <input type="number" name="per_km_shipping_charge" class="form-control"
                                                    id="per_km_shipping_charge" min="0" step=".01"  placeholder="{{ translate('messages.Ex:_100') }}"
                                                    value="{{ $per_km_shipping_charge ? $per_km_shipping_charge->value : 0 }}"
                                                    required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            
                            <div class="btn--container justify-content-end mt-3">
                                <button type="reset" class="btn btn--reset">{{ translate('messages.reset') }}</button>
                                <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}"
                                    class="btn btn--primary call-demo">{{ translate('save_information') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

@endsection

@push('script_2')
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&v=3.45.8">
    </script>
    <script>
        "use strict";
        $(document).on('ready', function() {
            @php($country = \App\Models\BusinessSetting::where('key', 'country')->first())

            @if ($country)
            $("#country option[value='{{ $country->value }}']").attr('selected', 'selected').change();
            @endif
        });

        @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
        @php($language = $language->value ?? null)
        let language = <?php echo $language; ?>;
        $('[id=language]').val(language);


        $(document).on('click', '.maintenance-mode', function () {
            @if (env('APP_MODE') == 'demo')
            toastr.warning('Sorry! You can not enable maintenance mode in demo!');
            @else
            Swal.fire({
                title: '{{ translate('messages.Are you sure?') }}',
                text: '{{ translate('messages.all_your_apps_and_customer_website_will_be_disabled_until_you_‘Turn_Off’ _maintenance_mode.') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#00868F',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.get({
                        url: '{{ route('admin.maintenance-mode') }}',
                        contentType: false,
                        processData: false,
                        beforeSend: function() {
                            $('#loading').show();
                        },
                        success: function(data) {
                            toastr.success(data.message);
                        },
                        complete: function() {
                            $('#loading').hide();
                        },
                    });
                } else {
                    location.reload();
                }
            })
            @endif

        });


        function readURL(input, viewer) {
            if (input.files && input.files[0]) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    $('#' + viewer).attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function() {
            readURL(this, 'viewer');
        });

        $("#favIconUpload").change(function() {
            readURL(this, 'iconViewer');
        });


        $(document).on('ready', function() {
            initAutocomplete();
        });

        $(document).on("keydown", "input", function(e) {
            if (e.which === 13) e.preventDefault();
        });
    </script>
@endpush
