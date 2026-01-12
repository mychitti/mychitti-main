@extends('layouts.admin.app')

@section('title', translate('messages.add_store_name'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('/public/assets/admin/css/intlTelInput.css') }}" />
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var radioInputs = document.querySelectorAll('input[name="plan_select"]');
            var uncheckButton = document.getElementById('uncheck_plans');

            if (uncheckButton) {
                uncheckButton.addEventListener('click', function() {
                    radioInputs.forEach(function(input) {
                        input.checked = false;
                    });
                });
            } else {
                console.error("Element with ID 'uncheck_plans' not found.");
            }
        });
    </script>
    <style>
        .check-mark {
            font-weight: bold;
            font-size: 1.2rem;
            color: #00aa6d;
        }

        .cross-mark {
            font-weight: bold;
            font-size: 1.2rem;
            color: #d41a1a;
        }
    </style>
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
                    {{ translate('messages.add_new_store') }}
                </span>
            </h1>
        </div>
        @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
        @php($language = $language->value ?? null)
        @php($defaultLang = 'en')
        <!-- End Page Header -->
        <form action="{{ route('admin.store.store') }}" method="post" enctype="multipart/form-data" class="js-validate"
            id="vendor_form">
            @csrf

            <div class="row g-2">
                <div class="col-lg-6">
                    <div class="card shadow--card-2">
                        <div class="card-body">
                            <div id="default-form">
                                <div class="form-group">
                                    <label class="input-label"
                                        for="exampleFormControlInput1">{{ translate('messages.name') }}</label>
                                    <input type="text" name="name[]" class="form-control"
                                        placeholder="{{ translate('messages.store_name') }}" required>
                                </div>
                                <input type="hidden" name="lang[]" value="default">
                                <div class="form-group mb-0">
                                    <label class="input-label"
                                        for="exampleFormControlInput1">{{ translate('messages.address') }}
                                    </label>
                                    <textarea type="text" name="address[]" placeholder="{{ translate('messages.store address') }}"
                                        class="form-control min-h-90px ckeditor"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow--card-2">
                        <div class="card-header">
                            <h5 class="card-title">
                                <span class="card-header-icon mr-1"><i class="tio-dashboard"></i></span>
                                <span>{{ translate('Store Logo & Covers') }}</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap flex-sm-nowrap __gap-12px">
                                <div class="__custom-upload-img mr-lg-5">
                                    @php($logo = \App\Models\BusinessSetting::where('key', 'logo')->first())
                                    @php($logo = $logo->value ?? '')
                                    <label class="form-label">
                                        {{ translate('logo') }} <span class="text--primary">({{ translate('1:1') }})</span>
                                    </label>
                                    <label class="text-center position-relative">
                                        <img class="img--110 min-height-170px min-width-170px onerror-image image--border"
                                            id="viewer"
                                            data-onerror-image="{{ asset('public/assets/admin/img/upload.png') }}"
                                            src="{{ asset('public/assets/admin/img/upload-img.png') }}" alt="logo image" />
                                        <div class="icon-file-group">
                                            <div class="icon-file">
                                                <i class="tio-edit"></i>
                                                <input type="file" name="logo" id="customFileEg1"
                                                    class="custom-file-input"
                                                    accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <div class="__custom-upload-img">
                                    @php($icon = \App\Models\BusinessSetting::where('key', 'icon')->first())
                                    @php($icon = $icon->value ?? '')
                                    <label class="form-label">
                                        {{ translate('Store Cover') }} <span
                                            class="text--primary">({{ translate('2:1') }})</span>
                                    </label>
                                    <label class="text-center position-relative">
                                        <img class="img--vertical min-height-170px min-width-170px onerror-image image--border"
                                            id="coverImageViewer"
                                            data-onerror-image="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                            src="{{ asset('public/assets/admin/img/upload-img.png') }}" alt="Fav icon" />
                                        <div class="icon-file-group">
                                            <div class="icon-file">
                                                <i class="tio-edit"></i>
                                                <input type="file" name="cover_photo" id="coverImageUpload"
                                                    class="custom-file-input"
                                                    accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title m-0 d-flex align-items-center">
                                <img class="mr-2 align-self-start w--20"
                                    src="{{ asset('public/assets/admin/img/resturant.png') }}" alt="instructions">
                                <span>{{ translate('store_information') }}</span>
                            </h4>
                        </div>
                        <div class="card-body">
                            @if (Config::get('module.current_module_id') != 6)
                                <!-- id 6 is for service module-->
                                <div class="row g-3 my-0">
                                    <!-- <div class="col-md-6">
                                                                    <div class="form-group mb-0">
                                                                        <label class="input-label" for="tax">{{ translate('messages.vat/tax') }} (%)</label>
                                                                        <input type="number" name="tax" class="form-control" placeholder="{{ translate('messages.vat/tax') }}" min="0" step=".01" required value="{{ old('tax') }}">
                                                                    </div>
                                                                </div> -->
                                    <div class="col-md-6">
                                        <div class="position-relative">
                                            <label class="input-label"
                                                for="tax">{{ translate('Estimated Delivery Time ( Min & Maximum Time)') }}</label>
                                            <input type="text" id="time_view" class="form-control" readonly>
                                            <a href="javascript:void(0)" class="floating-date-toggler">&nbsp;</a>
                                            <span class="offcanvas"></span>
                                            <div class="floating--date" id="floating--date">
                                                <div class="card shadow--card-2">
                                                    <div class="card-body">
                                                        <div class="floating--date-inner">
                                                            <div class="item">
                                                                <label class="input-label"
                                                                    for="minimum_delivery_time">{{ translate('Minimum Time') }}</label>
                                                                <input id="minimum_delivery_time" type="number"
                                                                    name="minimum_delivery_time"
                                                                    class="form-control h--45px"
                                                                    placeholder="{{ translate('messages.Ex :') }} 30"
                                                                    pattern="^[0-9]{2}$"
                                                                    value="{{ old('minimum_delivery_time') }}">
                                                            </div>
                                                            <div class="item">
                                                                <label class="input-label"
                                                                    for="maximum_delivery_time">{{ translate('Maximum Time') }}</label>
                                                                <input id="maximum_delivery_time" type="number"
                                                                    name="maximum_delivery_time"
                                                                    class="form-control h--45px"
                                                                    placeholder="{{ translate('messages.Ex :') }} 60"
                                                                    pattern="[0-9]{2}"
                                                                    value="{{ old('maximum_delivery_time') }}">
                                                            </div>
                                                            <div class="item smaller">
                                                                <select name="delivery_time_type" id="delivery_time_type"
                                                                    class="custom-select">
                                                                    <option value="min">
                                                                        {{ translate('messages.minutes') }}</option>
                                                                    <option value="hours">
                                                                        {{ translate('messages.hours') }}</option>
                                                                    <option value="days">
                                                                        {{ translate('messages.days') }}</option>
                                                                </select>
                                                            </div>
                                                            <div class="item smaller">
                                                                <button type="button"
                                                                    class="btn btn--primary delivery-time">{{ translate('done') }}</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="row g-3 my-0">
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label" for="choice_zones">City<span
                                                class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('messages.select_zone_for_map') }}"><img
                                                    src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.select_zone_for_map') }}"></span></label>
                                        <select name="zone_id" id="choice_zones" required
                                            class="form-control js-select2-custom" data-placeholder="Select City">
                                            <option value="" selected disabled>
                                                {{ translate('messages.select_zone') }}</option>
                                            @foreach (\App\Models\Zone::active()->get() as $zone)
                                                @if (isset(auth('admin')->user()->zone_id))
                                                    @if (auth('admin')->user()->zone_id == $zone->id)
                                                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                                    @endif
                                                @else
                                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="invisible" style="height: 0px;">
                                        <label class="input-label"
                                            for="latitude">{{ translate('messages.latitude') }}<span
                                                class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('messages.store_lat_lng_warning') }}"><img
                                                    src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.store_lat_lng_warning') }}"></span></label>
                                        <input type="text" id="latitude" name="latitude" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} -94.22213"
                                            value="{{ old('latitude') }}" required readonly>
                                    </div>
                                    <div class=" invisible" style="height: 0px;">
                                        <label class="input-label"
                                            for="longitude">{{ translate('messages.longitude') }}<span
                                                class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('messages.store_lat_lng_warning') }}"><img
                                                    src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.store_lat_lng_warning') }}"></span></label>
                                        <input type="text" name="longitude" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} 103.344322" id="longitude"
                                            value="{{ old('longitude') }}" required readonly>
                                    </div>

                                    <div>
                                        {{--   <div class="my-3">
                                            <label class="input-label fw-bold" for="choice_zones">Vendor Type</label>
                                            <input type="radio" checked name="vendor_type" value="regular"
                                                id="vendor_regular">
                                            <label style="margin: 16px 0px;" class="input-label d-inline"
                                                for="vendor_regular">Regular</label>

                                            <input type="radio" name="vendor_type" value="composition"
                                                id="vendor_composition">
                                            <label style="margin: 16px 0px;" class="input-label d-inline"
                                                for="vendor_composition">Composition</label> &nbsp; &nbsp;
                                        </div>
                                        <div class="my-3">
                                            <input type="checkbox" name="confirmation" id="confirmation_check">
                                            <label for="confirmation_check">Vendor's annual turnover is below 20
                                                lakhs.</label>
                                        </div>
                                        <div class="mb-4" id="gst_inp">
                                            <div class="form-group">
                                                <label class="input-label" id="timing_label"
                                                    for="minimum_delivery_time">GST (pdf / image)<span>*</span></label>

                                                <input type="file" id="gst_doc" name="gst_doc"
                                                    class="form-control __form-control">

                                            </div>
                                        </div>
                                        <div class="mb-4 " id="gst_num">
                                            <div class="form-group">
                                                <label class="input-label" id="" for="gst_num">GST
                                                    Number<span>*</span></label>
                                                <input type="text" placeholder="GST Number" id=""
                                                    name="gst_num" class="form-control __form-control">
                                            </div>
                                        </div>

                                        <div class="" id="id_elem2">
                                            <div class="mb-4 " id="id_num">
                                                <div class="form-group">
                                                    <label class="input-label" id="" for="id_num">ID
                                                        Number</label>
                                                    <input type="text" placeholder="ID Number" id="id_num"
                                                        name="id_number" class="form-control __form-control">
                                                </div>
                                            </div>
                                            <div class="mb-4" id="id_inp">
                                                <div class="form-group">
                                                    <label class="input-label" id="id_doc"
                                                        for="minimum_delivery_time">ID
                                                        Proof
                                                        (pdf / image)</label>

                                                    <input type="file" id="id_doc" name="id_doc"
                                                        class="form-control __form-control">

                                                </div>
                                            </div>
                                        </div> --}}
                                        @if (Config::get('module.current_module_id') == 5)
                                            <div class="mb-4" id="shop_business_type_elem">
                                                <div class="form-group">
                                                    <label class="input-label" id=""
                                                        for="business_type">Type:<span></span></label>
                                                    <select name="business_type" required class="form-control business_type"
                                                        id="shop_business_type">
                                                        <option value="">--select--</option>
                                                        @foreach ($business_types as $key => $value)
                                                            <option value="{{ $value->name }}">{{ $value->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @else
                                            <div class="mb-4" id="mycity_business_type_elem">
                                                <div class="form-group">
                                                    <label class="input-label" id=""
                                                        for="business_type">Type:<span></span></label>
                                                    <select name="business_type" required class="form-control business_type"
                                                        id="mycity_business_type">
                                                        <option value="">--select--</option>
                                                        @foreach ($business_types as $key => $value)
                                                            <option value="{{ $value->name }}">{{ $value->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="mb-4 verification_inp" id="">
                                            <div class="form-group">
                                                <label class="input-label" id="google_verification1"
                                                    for="google_verification">Your Google Business
                                                    Link<span></span></label>
                                                <input required type="text" placeholder="Your Google Business Link"
                                                    id="google_verification" name="google_verification"
                                                    class="form-control __form-control">
                                            </div>
                                        </div>
                                        <!-- verification_inp -->
                                        <div class="mb-4 " style="display: none;" id="">
                                            <div class="form-group">
                                                <label class="input-label" id="other_verification1"
                                                    for="other_verification">Other Business Link</label>
                                                <input type="text" placeholder="Other Business Link"
                                                    id="other_verification" name="other_verification"
                                                    class="form-control __form-control">
                                            </div>
                                        </div>
                                    </div>
                                      @if (Config::get('module.current_module_id') == 5)
                                        <div class="categroy_set_shop">
                                            <div class="form-group shop_categories">
                                                <div class="form-group mb-4">
                                                    <label class="input-label" id=""
                                                        for="shop_categories">Categories
                                                        (max 20)</label>
                                                    <select name="shop_categories[]" multiple="multiple"
                                                        id="shop_categories"
                                                        class=" form-control __form-control select_2_max_20"
                                                        data-placeholder="Categories">
                                                        <option value=""></option>
                                                        @foreach ($module_categories as $cat)
                                                            <option value="{{ $cat->id }}">{{ $cat->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="form-group">
                                            <div class="form-group mb-4">
                                                <label class="input-label" id=""
                                                    for="other_verification">Category
                                                    1<span></span></label>
                                                <select name="category_1" data-id="1" required
                                                    class="category_select form-control __form-control js-select2-custom js-example-basic-single"
                                                    data-placeholder="Category">
                                                    <option value=""></option>
                                                    @foreach ($module_categories as $cat)
                                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group subcategory_1" style="display: none;">
                                            <div class="form-group mb-4">
                                                <label class="input-label" id=""
                                                    for="other_verification">Services
                                                    1</label>
                                                <select name="services_1[]" multiple="multiple"
                                                    class="select_subcategory_1 form-control __form-control js-select2-custom js-example-basic-multiple"
                                                    data-placeholder="Subcategory">
                                                    <option value=""></option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="input-label" id="" for="other_verification">Category 2
                                                <span>(optional)</span></label>
                                            <select name="category_2" data-id="2"
                                                class="category_select form-control __form-control js-select2-custom js-example-basic-single"
                                                data-placeholder="Category">
                                                <option value=""></option>
                                                @foreach ($module_categories as $cat)
                                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group subcategory_2" style="display: none;">
                                            <div class="form-group mb-4">
                                                <label class="input-label" id=""
                                                    for="other_verification">Services
                                                    2</label>
                                                <select name="services_2[]" multiple="multiple" id="category_select"
                                                    class="select_subcategory_2 form-control __form-control js-select2-custom js-example-basic-multiple"
                                                    data-placeholder="Subcategory">
                                                    <option value=""></option>
                                                </select>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-lg-8">
                                    <input id="pac-input" class="controls rounded" data-toggle="tooltip"
                                        data-placement="right"
                                        data-original-title="{{ translate('messages.search_your_location_here') }}"
                                        type="text" placeholder="{{ translate('messages.search_here') }}" />
                                    <div id="map"></div>
                                  
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title m-0 d-flex align-items-center">
                                <span class="card-header-icon mr-2"><i class="tio-user"></i></span>
                                <span>{{ translate('messages.owner_information') }}</span>
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="f_name">{{ translate('messages.first_name') }}</label>
                                        <input type="text" name="f_name" class="form-control"
                                            placeholder="{{ translate('messages.first_name') }}"
                                            value="{{ old('f_name') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="l_name">{{ translate('messages.last_name') }}</label>
                                        <input type="text" name="l_name" class="form-control"
                                            placeholder="{{ translate('messages.last_name') }}"
                                            value="{{ old('l_name') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="phone">{{ translate('messages.phone') }}</label>
                                        <input type="text" id="phone" name="phone" class="intl_input form-control"
                                            placeholder="{{ translate('messages.Ex:') }} 99********" required>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="secondary_phone">Secondary
                                            {{ translate('messages.phone') }} <i>(Optional)</i></label>
                                        <input type="text" id="secondary_phone" name="secondary_phone"
                                            class="intl_input form-control"
                                            placeholder="{{ translate('messages.Ex:') }} 99********">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title m-0 d-flex align-items-center">
                                <span class="card-header-icon mr-2"><i class="tio-user"></i></span>
                                <span>{{ translate('messages.bank_account_information') }}</span>
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="form-row col-6">
                                    <label for="inputState">Type</label>
                                    <select id="payment_method_selec" name="payment_method" required id="inputState"
                                        class="form-control">
                                        <option value="bank">Bank Account</option>
                                        <option value="upi">UPI</option>
                                    </select>
                                </div>
                                <div class="form-row col-6 payment_field bank_field">
                                    <label for="inputState">Account Holder Name</label>
                                    <input type="text" name="acc_holder_name" placeholder="Account Holder Name"
                                        class=" form-control  payment_field_inp bank_field_inp">
                                </div>
                                <div class="form-row col-6 payment_field bank_field">
                                    <label for="inputState">Account Number</label>
                                    <input type="number" name="account_number" placeholder="Account Number"
                                        class=" form-control  payment_field_inp bank_field_inp">
                                </div>
                                <div class="form-row col-6  payment_field bank_field">
                                    <label for="inputState">IFSC</label>
                                    <input type="number" name="ifsc" placeholder="IFSC"
                                        class="form-control  payment_field_inp bank_field_inp">
                                </div>
                                <div class="form-row col-6 payment_field upi_field" style="display:none">
                                    <label for="inputState">UPI ID</label>
                                    <input type="text" name="upi_id" placeholder="UPI ID"
                                        class="form-control payment_field_inp upi_field_inp">
                                </div>
                                <div class="form-row col-6">
                                    <label for="documents">Documents</label>
                                    <input type="file" multiple name="documents[]" id="documents"
                                        class="form-control">
                                </div>

                            </div>
                        </div>
                    </div>
                </div> --}}
                {{-- <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title m-0 d-flex align-items-center">
                                <span class="card-header-icon mr-2"><i class="tio-user"></i></span>
                                <span>{{ translate('messages.account_information') }}</span>
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4 col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="email">{{ translate('messages.email') }}</label>
                                        <input type="email" name="email" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} ex@example.com"
                                            value="{{ old('email') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4 col-12">
                                    <div class="js-form-message form-group mb-0">
                                        <label class="input-label"
                                            for="signupSrPassword">{{ translate('messages.password') }}<span
                                                class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"><img
                                                    src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"></span></label>

                                        <div class="input-group input-group-merge">
                                            <input type="password" class="js-toggle-password form-control"
                                                name="password" id="signupSrPassword"
                                                pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                                title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"
                                                placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}"
                                                aria-label="8+ characters required" required
                                                data-msg="Your password is invalid. Please try again."
                                                data-hs-toggle-password-options='{
                                            "target": [".js-toggle-password-target-1", ".js-toggle-password-target-2"],
                                            "defaultClass": "tio-hidden-outlined",
                                            "showClass": "tio-visible-outlined",
                                            "classChangeTarget": ".js-toggle-passowrd-show-icon-1"
                                            }'>
                                            <div class="js-toggle-password-target-1 input-group-append">
                                                <a class="input-group-text" href="javascript:">
                                                    <i class="js-toggle-passowrd-show-icon-1 tio-visible-outlined"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-12">
                                    <div class="js-form-message form-group mb-0">
                                        <label class="input-label"
                                            for="signupSrConfirmPassword">{{ translate('messages.confirm_password') }}</label>
                                        <div class="input-group input-group-merge">
                                            <input type="password" class="js-toggle-password form-control"
                                                name="confirmPassword" id="signupSrConfirmPassword"
                                                pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                                title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"
                                                placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}"
                                                aria-label="8+ characters required" required
                                                data-msg="Password does not match the confirm password."
                                                data-hs-toggle-password-options='{
                                                "target": [".js-toggle-password-target-1", ".js-toggle-password-target-2"],
                                                "defaultClass": "tio-hidden-outlined",
                                                "showClass": "tio-visible-outlined",
                                                "classChangeTarget": ".js-toggle-passowrd-show-icon-2"
                                                }'>
                                            <div class="js-toggle-password-target-2 input-group-append">
                                                <a class="input-group-text" href="javascript:">
                                                    <i class="js-toggle-passowrd-show-icon-2 tio-visible-outlined"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> --}}

                @if (0 && Config::get('module.current_module_id') == 6)
                    <div class="col-lg-12">
                        <div id="accordion">
                            <div class="card">
                                <div class="card-header" style="cursor: pointer" data-toggle="collapse"
                                    data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne"
                                    id="headingOne">
                                    <h5 class="mb-0 d-flex w-100 justify-content-between">
                                        <button type="button" class="btn btn-link ">
                                            Subscription Plans
                                        </button>
                                        <button id="uncheck_plans" type="button" class="btn btn-link ">
                                            Select Plan Later
                                        </button>
                                    </h5>
                                </div>



                                <div id="collapseOne" class="collapse show" aria-labelledby="headingOne"
                                    data-parent="#accordion">
                                    <div class="card-body row ">
                                        @foreach ($allPlans as $plan)
                                            <div class="card col-3 shadow shadow-sm mx-2">
                                                <div class="d-flex justify-content-between pt-3">
                                                    @if ($plan->discount)
                                                        <div class="discount_badge">
                                                            {{ $plan->discount }}% Off
                                                        </div>
                                                    @endif
                                                    <input type="radio"
                                                        style="height:20px; width:20px; vertical-align: middle;"
                                                        value="{{ $plan->id }}" name="plan_select" id="">
                                                </div>
                                                <div class="card-body  text-center" style="line-height: 2.5rem;">
                                                    <h3 class="card-title  text-center"
                                                        style=" margin: 0 auto;
                                        width: fit-content;
                                        margin-bottom: 1.5rem;">
                                                        {{ $plan->title }}</h3>

                                                    {{-- <h6 class="card-subtitle mb-2 text-muted">Plan Description</h6> --}}
                                                    <ul style="list-style: none; padding:0;">
                                                        <li>
                                                            @if ($plan->leads_manage)
                                                            <span class="check-mark">✓</span>@else<span
                                                                    class="cross-mark">x</span>
                                                            @endif Leads Manage
                                                        </li>
                                                        <li>
                                                            @if ($plan->projects_manage)
                                                            <span class="check-mark">✓</span>@else<span
                                                                    class="cross-mark">x</span>
                                                            @endif Projects Manage
                                                        </li>
                                                        <li>
                                                            @if ($plan->quotaiton_manage)
                                                            <span class="check-mark">✓</span>@else<span
                                                                    class="cross-mark">x</span>
                                                            @endif Quotation Manage
                                                        </li>
                                                        <li>
                                                            @if ($plan->salary_manage)
                                                            <span class="check-mark">✓</span>@else<span
                                                                    class="cross-mark">x</span>
                                                            @endif Salary Manage
                                                        </li>
                                                        <li>
                                                            @if ($plan->staff_manage)
                                                            <span class="check-mark">✓</span>@else<span
                                                                    class="cross-mark">x</span>
                                                            @endif Staff Manage
                                                        </li>
                                                        <li>
                                                            @if ($plan->att_manage)
                                                            <span class="check-mark">✓</span>@else<span
                                                                    class="cross-mark">x</span>
                                                            @endif Attendance Manage
                                                        </li>
                                                        <li>
                                                            @if ($plan->leave_manage)
                                                            <span class="check-mark">✓</span>@else<span
                                                                    class="cross-mark">x</span>
                                                            @endif Leaves Manage
                                                        </li>
                                                        <li>
                                                            @if ($plan->account_manage)
                                                            <span class="check-mark">✓</span>@else<span
                                                                    class="cross-mark">x</span>
                                                            @endif Accounts Manage
                                                        </li>
                                                        <li>
                                                            @if ($plan->service_leads)
                                                            <span class="check-mark">✓</span>@else<span
                                                                    class="cross-mark">x</span>
                                                            @endif Service Leads
                                                        </li>
                                                    </ul>


                                                    <a href="#" style="pointer-events: none"
                                                        class="btn btn--primary btn-outline-primary p-2"><span
                                                            class="strikethrough">{{ \App\CentralLogics\Helpers::format_currency($plan->price) }}
                                                        </span> &nbsp;
                                                        &nbsp;<span>{{ \App\CentralLogics\Helpers::format_currency(_discountedPrice($plan->price, $plan->discount, 'percent')) }}
                                                        </span></a> <span class="text-secondary"> /
                                                        {{ $plan->duration_count . ' ' . $plan->duration_type }}</span>

                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="col-lg-12">
                    <div class="btn--container justify-content-end">
                        <button type="reset" id="reset_btn"
                            class="btn btn--reset">{{ translate('messages.reset') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&callback=initMap&v=3.45.8">
    </script>
    @include('admin-views.partials.tel_input')

    <script>
        "use strict";

        $('.category_select').on('change', function() {
            var cat_id = $(this).val()
            var dataid = $(this).attr('data-id');

            //fetchsubcategory
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.get({
                url: "{{ route('fetch-subcategory') }}",
                data: {
                    cat_id: cat_id
                },
                success: function(data) {
                    console.log(data)
                    if (data) {
                        if (data.categories.length) {
                            var html = '';
                            data.categories.forEach(element => {
                                html += '<option value="' + element.id + '">' + element.name +
                                    '</option>';
                            });
                            $(".subcategory_" + dataid).show()
                            $(".select_subcategory_" + dataid).html(html)
                        } else {
                            $(".subcategory_" + dataid).hide()
                            $(".select_subcategory_" + dataid).html('')
                        }
                    }
                },
            });
        })


        $(document).on('ready', function() {
            $('.offcanvas').on('click', function() {
                $('.offcanvas, .floating--date').removeClass('active')
            })
            $('.floating-date-toggler').on('click', function() {
                $('.offcanvas, .floating--date').toggleClass('active')
            })
            @if (isset(auth('admin')->user()->zone_id))
                $('#choice_zones').trigger('change');
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
        // $(document).on('change', '.business_type', function() {
        //     console.log($(this).val())
        //     if ($(this).val() == 'Business') {
        //         $(".verification_inp").show()
        //     } else {
        //         $(".verification_inp").hide()
        //     }
        // })
        @if (Config::get('module.current_module_id') == 6)
            $('#confirmation_check').on('change', function() {
                if ($(this).prop('checked') == true) {
                    $('#gst_inp').hide()
                    $('#gst_num').hide()
                } else {
                    $('#gst_inp').show()
                    $('#gst_num').show()

                }
            })
        @endif

        $("#customFileEg1").change(function() {
            readURL(this, 'viewer');
        });

        $("#coverImageUpload").change(function() {
            readURL(this, 'coverImageViewer');
        });

        $(function() {
            $("#coba").spartanMultiImagePicker({
                fieldName: 'identity_image[]',
                maxCount: 5,
                rowHeight: '120px',
                groupClassName: 'col-lg-2 col-md-4 col-sm-4 col-6',
                maxFileSize: '',
                placeholderImage: {
                    image: '{{ asset('public/assets/admin/img/400x400/img2.jpg') }}',
                    width: '100%'
                },
                dropFileLabel: "Drop Here",
                onAddRow: function(index, file) {

                },
                onRenderedPreview: function(index) {

                },
                onRemoveRow: function(index) {

                },
                onExtensionErr: function(index, file) {
                    toastr.error(
                        '{{ translate('messages.please_only_input_png_or_jpg_type_file') }}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                },
                onSizeErr: function(index, file) {
                    toastr.error('{{ translate('messages.file_size_too_big') }}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        });

        @php($default_location = \App\Models\BusinessSetting::where('key', 'default_location')->first())
        @php($default_location = $default_location->value ? json_decode($default_location->value, true) : 0)
        let myLatlng = {
            lat: {{ $default_location ? $default_location['lat'] : '23.757989' }},
            lng: {{ $default_location ? $default_location['lng'] : '90.360587' }}
        };
        let map = new google.maps.Map(document.getElementById("map"), {
            zoom: 13,
            center: myLatlng,
        });
        let zonePolygon = null;
        let infoWindow = new google.maps.InfoWindow({
            content: "Click the map to get Lat/Lng!",
            position: myLatlng,
        });
        let bounds = new google.maps.LatLngBounds();

        function initMap() {
            // Create the initial InfoWindow.
            infoWindow.open(map);
            //get current location block
            infoWindow = new google.maps.InfoWindow();
            // Try HTML5 geolocation.
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        myLatlng = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                        };
                        infoWindow.setPosition(myLatlng);
                        infoWindow.setContent("Location found.");
                        infoWindow.open(map);
                        map.setCenter(myLatlng);
                    },
                    () => {
                        handleLocationError(true, infoWindow, map.getCenter());
                    }
                );
            } else {
                // Browser doesn't support Geolocation
                handleLocationError(false, infoWindow, map.getCenter());
            }
            //-----end block------
            const input = document.getElementById("pac-input");
            const searchBox = new google.maps.places.SearchBox(input);
            map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
            let markers = [];
            searchBox.addListener("places_changed", () => {
                const places = searchBox.getPlaces();

                if (places.length == 0) {
                    return;
                }
                // Clear out the old markers.
                markers.forEach((marker) => {
                    marker.setMap(null);
                });
                markers = [];
                // For each place, get the icon, name and location.
                const bounds = new google.maps.LatLngBounds();
                places.forEach((place) => {
                    document.getElementById('latitude').value = place.geometry.location.lat();
                    document.getElementById('longitude').value = place.geometry.location.lng();
                    if (!place.geometry || !place.geometry.location) {
                        console.log("Returned place contains no geometry");
                        return;
                    }
                    const icon = {
                        url: place.icon,
                        size: new google.maps.Size(71, 71),
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(17, 34),
                        scaledSize: new google.maps.Size(25, 25),
                    };
                    // Create a marker for each place.
                    markers.push(
                        new google.maps.Marker({
                            map,
                            icon,
                            title: place.name,
                            position: place.geometry.location,
                        })
                    );

                    if (place.geometry.viewport) {
                        // Only geocodes have viewport.
                        bounds.union(place.geometry.viewport);
                    } else {
                        bounds.extend(place.geometry.location);
                    }
                });
                map.fitBounds(bounds);
            });
        }
        initMap();

        function handleLocationError(browserHasGeolocation, infoWindow, pos) {
            infoWindow.setPosition(pos);
            infoWindow.setContent(
                browserHasGeolocation ?
                "Error: The Geolocation service failed." :
                "Error: Your browser doesn't support geolocation."
            );
            infoWindow.open(map);
        }
        $('#choice_zones').on('change', function() {
            let id = $(this).val();
            $.get({
                url: '{{ url('/') }}/admin/zone/get-coordinates/' + id,
                dataType: 'json',
                success: function(data) {
                    if (zonePolygon) {
                        zonePolygon.setMap(null);
                    }
                    zonePolygon = new google.maps.Polygon({
                        paths: data.coordinates,
                        strokeColor: "#FF0000",
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: 'white',
                        fillOpacity: 0,
                    });
                    zonePolygon.setMap(map);
                    zonePolygon.getPaths().forEach(function(path) {
                        path.forEach(function(latlng) {
                            bounds.extend(latlng);
                            map.fitBounds(bounds);
                        });
                    });
                    map.setCenter(data.center);
                    google.maps.event.addListener(zonePolygon, 'click', function(mapsMouseEvent) {
                        infoWindow.close();
                        // Create a new InfoWindow.
                        infoWindow = new google.maps.InfoWindow({
                            position: mapsMouseEvent.latLng,
                            content: JSON.stringify(mapsMouseEvent.latLng.toJSON(),
                                null, 2),
                        });
                        let coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null,
                            2);
                        coordinates = JSON.parse(coordinates);
                        document.getElementById('latitude').value = coordinates['lat'];
                        document.getElementById('longitude').value = coordinates['lng'];
                        infoWindow.open(map);
                    });
                },
            });
        });


        $("#vendor_form").on('keydown', function(e) {
            console.log('fds')
            if (e.keyCode === 13) {
                e.preventDefault();
            } else {
                console.log('fssd')
            }
        })

        $('#reset_btn').click(function() {
            $('#viewer').attr('src', "{{ asset('public/assets/admin/img/upload.png') }}");
            $('#customFileEg1').val(null);
            $('#coverImageViewer').attr('src', "{{ asset('public/assets/admin/img/upload-img.png') }}");
            $('#coverImageUpload').val(null);
            $('#choice_zones').val(null).trigger('change');
            $('#module_id').val(null).trigger('change');
            zonePolygon.setMap(null);
            $('#coordinates').val(null);
            $('#latitude').val(null);
            $('#longitude').val(null);
        })

        let zone_id = 0;
        $('#choice_zones').on('change', function() {
            if ($(this).val()) {
                zone_id = $(this).val();
            }
        });

        $('#module_id').select2({
            ajax: {
                url: '{{ url('/') }}/store/get-all-modules',
                data: function(params) {
                    return {
                        q: params.term, // search term
                        page: params.page,
                        zone_id: zone_id
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                __port: function(params, success, failure) {
                    let $request = $.ajax(params);

                    $request.then(success);
                    $request.fail(failure);

                    return $request;
                }
            }
        });


        $('.delivery-time').on('click', function() {
            let min = $("#minimum_delivery_time").val();
            let max = $("#maximum_delivery_time").val();
            let type = $("#delivery_time_type").val();
            $("#floating--date").removeClass('active');
            $("#time_view").val(min + ' to ' + max + ' ' + type);

        })
    </script>
@endpush
