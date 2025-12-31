@extends('layouts.admin.app')

@section('title', 'Update restaurant info')
@push('css_or_js')
    {{-- <link rel="stylesheet" href="{{asset('/public/assets/admin/css/intlTelInput.css')}}" /> --}}
@endpush

@section('content')
    <style>
        .revw_thumbnail {
            width: 41px !important;
            border: 1px solid #dedede;
            height: 38px !important;
            margin: 4px;
            border-radius: 5px;
            cursor: zoom-in;
        }
    </style>
    <!-- LightGallery CSS -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lightgallery.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lightgallery.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-thumbnail.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-video.css">
    <!-- LightGallery JS -->


    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/edit.png') }}" class="w--26" alt="">
                </span>
                <span>{{ translate('messages.update') }} {{ Config::get('module.vendor_role') }} </span>
            </h1>
        </div>
        @php
            $delivery_time_start = preg_match('([0-9]+[\-][0-9]+\s[min|hours|days])', $store->delivery_time ?? '')
                ? explode('-', $store->delivery_time)[0]
                : 10;
            $delivery_time_end = preg_match('([0-9]+[\-][0-9]+\s[min|hours|days])', $store->delivery_time ?? '')
                ? explode(' ', explode('-', $store->delivery_time)[1])[0]
                : 30;
            $delivery_time_type = preg_match('([0-9]+[\-][0-9]+\s[min|hours|days])', $store->delivery_time ?? '')
                ? explode(' ', explode('-', $store->delivery_time)[1])[1]
                : 'min';
        @endphp

        @php($defaultLang = 'en')
        <!-- End Page Header -->
        <form action="{{ route('admin.store.update', [$store['id']]) }}" method="post" class="js-validate"
            enctype="multipart/form-data" id="vendor_form">
            @csrf

            <div class="row g-2">
                <div class="col-lg-6">
                    <div class="card shadow--card-2">
                        <div class="card-body">
                            <div id="default-form">
                                <div class="form-group">
                                    <label class="input-l abel"
                                        for="exampleFormControlInput1">{{ translate('messages.name') }} </label>
                                    <input type="text" name="name[]" class="form-control"
                                        placeholder="{{ Config::get('module.vendor_role') }} {{ translate('messages.name') }}"
                                        value="{{ $store->name }}" required>
                                </div>
                                <input type="hidden" name="lang[]" value="default">
                                <div class="form-group mb-0">
                                    <label class="input-label"
                                        for="exampleFormControlInput1">{{ translate('messages.address') }}
                                    </label>
                                    <textarea type="text" name="address" placeholder="{{ Config::get('module.vendor_role') }} "
                                        class="form-control min-h-90px ckeditor">{{ $store->address }}</textarea>
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
                                <span>{{ Config::get('module.vendor_role') }} {{ translate(' Logo & Covers') }}</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap flex-sm-nowrap __gap-12px">
                                <div class="__custom-upload-img mr-lg-5">
                                    @php($logo = \App\Models\BusinessSetting::where('key', 'logo')->first())
                                    @php($logo = $logo->value ?? '')
                                    <label class="form-label">
                                        {{ translate('logo') }} <span
                                            class="text--primary">({{ translate('1:1') }})</span>
                                    </label>
                                    <label class="text-center position-relative">
                                        <img class="img--110 min-height-170px min-width-170px onerror-image image--border"
                                            id="viewer"
                                            data-onerror-image="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                $store->logo ?? '',
                                                asset('storage/app/public/store') . '/' . $store->logo ?? '',
                                                asset('public/assets/admin/img/upload-img.png'),
                                                'store/',
                                            ) }}"
                                            alt="logo image" />
                                        <div class="icon-file-group">
                                            <div class="icon-file">
                                                <i class="tio-edit"></i>
                                                <input type="file" name="logo" id="customFileEg1"
                                                    class="custom-file-input"
                                                    accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" required>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <div class="__custom-upload-img">
                                    @php($icon = \App\Models\BusinessSetting::where('key', 'icon')->first())
                                    @php($icon = $icon->value ?? '')
                                    <label class="form-label">
                                        {{ Config::get('module.vendor_role') }} {{ translate(' Cover') }} <span
                                            class="text--primary">({{ translate('2:1') }})</span>
                                    </label>
                                    <label class="text-center position-relative">
                                        <img class="img--vertical min-height-170px min-width-170px onerror-image image--border"
                                            id="coverImageViewer"
                                            data-onerror-image="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                $store->cover_photo ?? '',
                                                asset('storage/app/public/store/cover') . '/' . $store->cover_photo ?? '',
                                                asset('public/assets/admin/img/upload-img.png'),
                                                'store/cover/',
                                            ) }}"
                                            alt="Fav icon" />
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
                                <span>{{ Config::get('module.vendor_role') }} {{ translate('information') }}</span>
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 my-0">
                                @if (Config::get('module.current_module_id') != 6)
                                    <!-- <div class="col-md-6">
                                                                                                                                        <div class="form-group mb-0">
                                                                                                                                            <label class="input-label" for="tax">{{ translate('messages.vat/tax') }} (%)</label>
                                                                                                                                            <input type="number" name="tax" class="form-control" placeholder="{{ translate('messages.vat/tax') }}" min="0" step=".01" required value="{{ $store->tax }}">
                                                                                                                                        </div>
                                                                                                                                    </div> -->
                                    <div class="col-md-6">
                                        <div class="position-relative">
                                            <label class="input-label"
                                                for="tax">{{ translate('Estimated Delivery Time ( Min & Maximum Time)') }}</label>
                                            <input type="text" id="time_view"
                                                value="{{ $delivery_time_start }} to {{ $delivery_time_end }} {{ $delivery_time_type }}"
                                                class="form-control" readonly>
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
                                                                    value="{{ $delivery_time_start }}"
                                                                    class="form-control h--45px"
                                                                    placeholder="{{ translate('messages.Ex :') }} 30"
                                                                    pattern="^[0-9]{2}$" required
                                                                    value="{{ old('minimum_delivery_time') }}">
                                                            </div>
                                                            <div class="item">
                                                                <label class="input-label"
                                                                    for="maximum_delivery_time">{{ translate('Maximum Time') }}</label>
                                                                <input id="maximum_delivery_time" type="number"
                                                                    name="maximum_delivery_time"
                                                                    value="{{ $delivery_time_end }}"
                                                                    class="form-control h--45px"
                                                                    placeholder="{{ translate('messages.Ex :') }} 60"
                                                                    pattern="[0-9]{2}" required
                                                                    value="{{ old('maximum_delivery_time') }}">
                                                            </div>
                                                            <div class="item smaller">
                                                                <select name="delivery_time_type" id="delivery_time_type"
                                                                    class="custom-select">
                                                                    <option value="min"
                                                                        {{ $delivery_time_type == 'min' ? 'selected' : '' }}>
                                                                        {{ translate('messages.minutes') }}</option>
                                                                    <option value="hours"
                                                                        {{ $delivery_time_type == 'hours' ? 'selected' : '' }}>
                                                                        {{ translate('messages.hours') }}</option>
                                                                    <option value="days"
                                                                        {{ $delivery_time_type == 'days' ? 'selected' : '' }}>
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
                                @endif
                            </div>
                            <div class="row g-3 my-0">
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label" for="choice_zones">City<span
                                                class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('messages.select_zone_for_map') }}"><img
                                                    src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.select_zone_for_map') }}"></span></label>
                                        <select name="zone_id" id="choice_zones"
                                            data-placeholder="{{ translate('messages.select_zone') }}"
                                            class="form-control js-select2-custom get_zone_data">
                                            @foreach (\App\Models\Zone::active()->get() as $zone)
                                                @if (isset(auth('admin')->user()->zone_id))
                                                    @if (auth('admin')->user()->zone_id == $zone->id)
                                                        <option value="{{ $zone->id }}"
                                                            {{ $store->zone_id == $zone->id ? 'selected' : '' }}>
                                                            {{ $zone->name }}</option>
                                                    @endif
                                                @else
                                                    <option value="{{ $zone->id }}"
                                                        {{ $store->zone_id == $zone->id ? 'selected' : '' }}>
                                                        {{ $zone->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <input type="hidden" name="latitude" id="latitude"
                                        value="{{ $store->latitude }}">
                                    <input type="hidden" name="longitude" id="longitude"
                                        value="{{ $store->longitude }}">
                                    {{-- <div class="invisible" style="height: 0px;">
                                        <label class="input-label"
                                            for="latitude">{{ translate('messages.latitude') }}<span
                                                class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('messages.store_lat_lng_warning') }}"><img
                                                    src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.store_lat_lng_warning') }}"></span></label>
                                        <input type="text" id="latitude" name="latitude" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} -94.22213"
                                            value="{{ $store->latitude }}" required readonly>
                                    </div>
                                    <div class="invisible" style="height: 0px;">
                                        <label class="input-label"
                                            for="longitude">{{ translate('messages.longitude') }}<span
                                                class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('messages.store_lat_lng_warning') }}"><img
                                                    src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.store_lat_lng_warning') }}"></span></label>
                                        <input type="text" name="longitude" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} 103.344322" id="longitude"
                                            value="{{ $store->longitude }}" required readonly>
                                    </div> --}}
                                    <div>
                                        {{-- <div class="my-3">
                                            <label class="input-label fw-bold" for="choice_zones">{{Config::get('module.vendor_role')}} Type</label>
                                            <input type="radio" {{ $store->vendor_type == 'regular' ? 'checked' : '' }}
                                                name="vendor_type" value="regular" id="vendor_regular">
                                            <label style="margin: 16px 0px;" class="input-label d-inline"
                                                for="vendor_regular">Regular</label>

                                            <input type="radio"
                                                {{ $store->vendor_type == 'composition' ? 'checked' : '' }}
                                                name="vendor_type" value="composition" id="vendor_composition">
                                            <label style="margin: 16px 0px;" class="input-label d-inline"
                                                for="vendor_composition">Composition</label> &nbsp; &nbsp;
                                        </div>
                                        <div class="my-3">
                                            <input type="checkbox" name="confirmation"
                                                {{ $store->salary_below != 20 ? '' : 'checked' }} id="confirmation_check">
                                            <label for="confirmation_check">vendor's annual turnover is below 20
                                                lakhs.</label>
                                        </div>
                                        <div class="mb-4" id="gst_inp"
                                            {{ $store->salary_below != 20 ? '' : 'style=display:none' }}>
                                            <div class="form-group">
                                                <label class="input-label" id="timing_label"
                                                    for="minimum_delivery_time">GST (pdf / image)<span>*</span>
                                                    @if ($store->gst_doc)
                                                        <a target="_blank"
                                                            href="{{ asset('storage/app/public/store/docs') . '/' . $store->gst_doc }}">View
                                                            Current</a>
                                                    @endif
                                                </label>
                                                <input type="file" id="gst_doc" name="gst_doc"
                                                    class="form-control __form-control">
                                            </div>
                                        </div>
                                        <div class="mb-4 " id="gst_num"
                                            {{ $store->salary_below != 20 ? '' : 'style=display:none' }}>
                                            <div class="form-group">
                                                <label class="input-label" id="" for="gst_num">GST
                                                    Number<span>*</span></label>
                                                <input type="text" placeholder="GST Number" id=""
                                                    name="gst_num" class="form-control __form-control"
                                                    value="{{ $store->gst_number }}">
                                            </div>
                                        </div> --}}
                                        @if (Config::get('module.current_module_id') == 5)
                                            <div class="mb-4" id="shop_business_type_elem">
                                                <div class="form-group">
                                                    <label class="input-label" id=""
                                                        for="business_type">Type:<span>*</span></label>
                                                    <select name="business_type" class="form-control business_type"
                                                        id="shop_business_type">
                                                        <option value="">--select--</option>
                                                        @foreach ($business_types as $key => $value)
                                                            <option
                                                                {{ $store->business_type == $value->name ? 'selected' : '' }}
                                                                value="{{ $value->name }}">{{ $value->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @else
                                            <div class="mb-4" id="mycity_business_type_elem">
                                                <div class="form-group">
                                                    <label class="input-label" id=""
                                                        for="business_type">Type:<span>*</span></label>
                                                    <select name="business_type" class="form-control business_type"
                                                        id="mycity_business_type">
                                                        <option value="">--select--</option>
                                                        @foreach ($business_types as $key => $value)
                                                            <option
                                                                {{ $store->business_type == $value->name ? 'selected' : '' }}
                                                                value="{{ $value->name }}">{{ $value->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @endif
                                        {{-- <button class="btn btn-outline-primary" type="button" data-toggle="modal"
                                            data-target="#IDModal">ID Proof</button> --}}

                                        <div class="mb-4 verification_inp" id="">
                                            <div class="form-group">
                                                <label class="input-label" id="google_verification1"
                                                    for="google_verification">Your Google Business
                                                    Link<span>*</span></label>
                                                <input type="text" value="{{ $store->google_verification }}"
                                                    placeholder="Your Google Business Link" id="google_verification"
                                                    name="google_verification" class="form-control __form-control">
                                            </div>
                                        </div>
                                        <!-- verification_inp -->
                                        <div class="mb-4 " id="" style="display:none;"
                                            {{ $store->business_type == 'Business' ? '' : 'style=display:none' }}>
                                            <div class="form-group">
                                                <label class="input-label" id="other_verification1"
                                                    for="other_verification">Other Business Link</label>
                                                <input type="text" value="{{ $store->other_verification }}"
                                                    placeholder="Other Business Link" id="other_verification"
                                                    name="other_verification" class="form-control __form-control">
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
                                                                <option
                                                                    {{ in_array($cat->id, explode(',', $store->shop_categories)) ? 'selected' : '' }}
                                                                    value="{{ $cat->id }}">{{ $cat->name }}
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
                                                        1<span>*</span></label>
                                                    <select name="category_1" data-id="1" required
                                                        class="category_select form-control __form-control js-select2-custom js-example-basic-single"
                                                        data-placeholder="Category">
                                                        <option value=""></option>
                                                        @foreach ($module_categories as $cat)
                                                            <option
                                                                {{ $cat->id == $store['category_1'] ? 'selected' : '' }}
                                                                value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group subcategory_1">
                                                <div class="form-group mb-4">
                                                    <label class="input-label" id=""
                                                        for="other_verification">Services
                                                        1</label>
                                                    <select name="services_1[]" multiple="multiple"
                                                        class="select_subcategory_1 form-control __form-control js-select2-custom js-example-basic-multiple"
                                                        data-placeholder="Subcategory">
                                                        <option value=""></option>
                                                        @foreach ($items_1 as $sc)
                                                            <option
                                                                {{ in_array($sc->id, explode(',', $store->services_1)) ? 'selected' : '' }}
                                                                value="{{ $sc->id }}">{{ $sc->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="input-label" id=""
                                                    for="other_verification">Category 2
                                                    <span>(optional)</span></label>
                                                <select name="category_2" data-id="2"
                                                    class="category_select form-control __form-control js-select2-custom js-example-basic-single"
                                                    data-placeholder="Category">
                                                    <option value=""></option>
                                                    @foreach ($module_categories as $cat)
                                                        <option {{ $cat->id == $store['category_2'] ? 'selected' : '' }}
                                                            value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group subcategory_2">
                                                <div class="form-group mb-4">
                                                    <label class="input-label" id=""
                                                        for="other_verification">Services
                                                        2</label>
                                                    <select name="services_2[]" multiple="multiple" id=""
                                                        class="category_select select_subcategory_2 form-control __form-control js-select2-custom js-example-basic-multiple"
                                                        data-placeholder="Subcategory">
                                                        <option value=""></option>
                                                        @foreach ($items_2 as $sc)
                                                            <option
                                                                {{ in_array($sc->id, explode(',', $store->services_2)) ? 'selected' : '' }}
                                                                value="{{ $sc->id }}">{{ $sc->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
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
                                            value="{{ $store->vendor->f_name }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="l_name">{{ translate('messages.last_name') }}</label>
                                        <input type="text" name="l_name" class="form-control"
                                            placeholder="{{ translate('messages.last_name') }}"
                                            value="{{ $store->vendor->l_name }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="phone">{{ translate('messages.phone') }}</label>
                                        <input type="number" id="phone" name="phone" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} 99********"
                                            value="{{ $store->vendor->phone }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="secondary_phone">Secondary
                                            {{ translate('messages.phone') }} <i>(optional)</i></label>
                                        <input type="number" id="secondary_phone" name="secondary_phone"
                                            class="form-control" placeholder="{{ translate('messages.Ex:') }} 99********"
                                            value="{{ $store->vendor->secondary_phone }}">
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
                                    {{ $store->account?->upi_id }}
                                    <label for="inputState">Type</label>
                                    <select id="payment_method_selec" name="payment_method" required id="inputState"
                                        class="form-control">
                                        <option {{ $store->account?->payment_type == 'bank' ? 'selected' : '' }}
                                            value="bank">Bank Account</option>
                                        <option {{ $store->account?->payment_type == 'upi' ? 'selected' : '' }}
                                            value="upi">UPI</option>
                                    </select>
                                </div>
                                <div class="form-row col-6 payment_field bank_field"
                                    {{ $store->account?->payment_type == 'upi' ? 'style=display:none' : '' }}>
                                    <label for="inputState">Account Holder Name</label>
                                    <input type="text" value="{{ $store->account?->account_holder_name }}"
                                        name="acc_holder_name" placeholder="Account Holder Name"
                                        class=" form-control  payment_field_inp bank_field_inp">
                                </div>
                                <div class="form-row col-6 payment_field bank_field"
                                    {{ $store->account?->payment_type == 'upi' ? 'style=display:none' : '' }}>
                                    <label for="inputState">Account Number</label>
                                    <input type="number" value="{{ $store->account?->account_number }}"
                                        name="account_number" placeholder="Account Number"
                                        class=" form-control  payment_field_inp bank_field_inp">
                                </div>
                                <div class="form-row col-6  payment_field bank_field"
                                    {{ $store->account?->payment_type == 'upi' ? 'style=display:none' : '' }}>
                                    <label for="inputState">IFSC</label>
                                    <input type="text" value="{{ $store->account?->ifsc_code }}" name="ifsc"
                                        placeholder="IFSC" class="form-control  payment_field_inp bank_field_inp">
                                </div>
                                <div class="form-row col-6 payment_field upi_field"
                                    {{ !$store->account || $store->account?->payment_type == 'bank' ? 'style=display:none' : '' }}>
                                    <label for="inputState">UPI ID</label>
                                    <input type="text" value="{{ $store->account?->upi_id }}" name="upi_id"
                                        placeholder="UPI ID" class="form-control payment_field_inp upi_field_inp">
                                </div>
                                <div class="form-row col-6">
                                    <label for="documents">Documents</label>
                                    <input type="file" multiple name="documents[]" id="documents"
                                        class="form-control">
                                </div>
                                @if ($store->account?->documents)
                                    <div class="form col-6">

                                        <label for="">Current Documents</label> <br>
                                        <div class=" d-flex">
                                            <div class="lightgallery d-flex">

                                                @foreach (json_decode($store->account?->documents) as $value)
                                                    @if (in_array(pathinfo($value, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                                        <a href="{{ asset('storage/app/public/vendor/documents/' . $value) }}"><img
                                                                src="{{ asset('storage/app/public/vendor/documents/' . $value) }}"
                                                                class="revw_thumbnail" alt=""></a>
                                                    @elseif(in_array(pathinfo($value, PATHINFO_EXTENSION), ['mp4', 'webm', 'ogg']))
                                                        <a class=" position-relative"
                                                            data-video='{"source": [{"src":"{{ asset('storage/app/public/vendor/documents/' . $value) }}", "type":"video/mp4"}], "attributes": {"preload": false, "controls": true}}'>
                                                            <img src="{{ asset('storage/app/public/video.jpg') }}"
                                                                class="revw_thumbnail" alt="Video">
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                            <div class=" d-flex">
                                                @foreach (json_decode($store->account?->documents) as $value)
                                                    @if (pathinfo($value, PATHINFO_EXTENSION) == 'pdf')
                                                        <!-- PDF Preview with Google Docs Viewer -->
                                                        <a href="{{ asset('storage/app/public/vendor/documents/' . $value) }}"
                                                            target="_blank">
                                                            <img src="{{ asset('storage/app/public/pdf.jpg') }}"
                                                                class="revw_thumbnail" alt="pdf">
                                                        </a>
                                                    @elseif(!in_array(pathinfo($value, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'mp4', 'webm', 'ogg']))
                                                        <!-- Other Files (Download Link) -->
                                                        <a href="{{ asset('storage/app/public/vendor/documents/' . $value) }}"
                                                            download>Download
                                                            {{ $value }}</a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>

                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title m-0 d-flex align-items-center">
                                <span class="card-header-icon mr-2"><i class="tio-user"></i></span>
                                <span>{{ translate('messages.account_information') }}</span>
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.email') }}</label>
                                        <input type="email" name="email" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} ex@example.com"
                                            value="{{ $store->email }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="js-form-message form-group mb-0">
                                        <label class="input-label"
                                            for="signupSrPassword">{{ translate('password') }}<span
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
                                                aria-label="8+ characters required"
                                                data-msg="Your password is invalid. Please try again."
                                                data-hs-toggle-password-options='{
                                            "target": [".js-toggle-password-target-1", ".js-toggle-password-target-2"],
                                            "defaultClass": "tio-hidden-outlined",
                                            "showClass": "tio-visible-outlined",
                                            "classChangeTarget": ".js-toggle-passowrd-show-icon-1"
                                            }'>
                                            <div class="js-toggle-password-target-1 input-group-append">
                                                <a class="input-group-text" href="javascript:;">
                                                    <i class="js-toggle-passowrd-show-icon-1 tio-visible-outlined"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="js-form-message form-group mb-0">
                                        <label class="input-label"
                                            for="signupSrConfirmPassword">{{ translate('messages.Confirm Password') }}</label>

                                        <div class="input-group input-group-merge">
                                            <input type="password" class="js-toggle-password form-control"
                                                name="confirmPassword" id="signupSrConfirmPassword"
                                                pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                                title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"
                                                placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}"
                                                aria-label="8+ characters required"
                                                data-msg="Password does not match the confirm password."
                                                data-hs-toggle-password-options='{
                                                "target": [".js-toggle-password-target-1", ".js-toggle-password-target-2"],
                                                "defaultClass": "tio-hidden-outlined",
                                                "showClass": "tio-visible-outlined",
                                                "classChangeTarget": ".js-toggle-passowrd-show-icon-2"
                                                }'>
                                            <div class="js-toggle-password-target-2 input-group-append">
                                                <a class="input-group-text" href="javascript:;">
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

                <div class="col-lg-12">
                    <div class="btn--container justify-content-end">
                        <button type="reset" id="reset_btn"
                            class="btn btn--reset">{{ translate('messages.reset') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Modal -->



        <!-- Modal -->
        <div class="modal fade" id="IDModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form class="formAjax" method="post" action="{{ route('admin.store.update_id') }}"
                            enctype="multipart/form-data">
                            <div class="mb-4 " id="">
                                <div class="form-group">
                                    <label class="input-label" for="otp_input">Send OTP</label>
                                    <div class="d-flex">
                                        <input type="number" disabled placeholder="OTP" id="otp_input" name="otp_input"
                                            class="form-control __form-control">
                                        <button type="button" class="btn btn-primary btn-sm" id="send_otp">Send
                                            OTP</button>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4 " id="">
                                <div class="form-group">
                                    <label class="input-label" for="id_number">ID Number</label>
                                    <input type="text" value="{{ $store->id_number }}" placeholder="ID Number"
                                        id="id_number" name="id_number" class="form-control __form-control">
                                </div>
                            </div>
                            <div class="mb-4" id="">
                                <div class="form-group">
                                    <label class="input-label" id="timing_label" for="minimum_delivery_time">ID document
                                        @if ($store->id_doc)
                                            <a target="_blank"
                                                href="{{ asset('storage/app/public/store/docs') . '/' . $store->id_doc }}">View
                                                Current</a>
                                        @endif
                                    </label>

                                    <input type="file" id="id_doc" name="id_doc"
                                        class="form-control __form-control">
                                </div>
                            </div>

                            <input type="hidden" id = "store_phone" value="{{ $store->phone }}">
                            <input type="hidden" name = "id" value="{{ $store->id }}">
                            <button type="submit" class="btn btn-primary" id="id_update_btn">Update changes</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>


    </div>

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&callback=initMap">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/lightgallery.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/plugins/video/lg-video.umd.min.js"></script>
    <script>
        "use strict";

        $("#send_otp").on('click', function() {
            $("#send_otp").attr('disabled', true);

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('admin.send-vendor-otp') }}",
                data: {
                    store_phone: {{ $store->phone }}
                },
                success: function(data) {
                    console.log(data)
                    // var data = JSON.parse(data)
                    if (data.status) {
                        $("#otp_input").removeAttr('disabled')
                        $("#send_otp").remove();
                        $("#otp_input").focus();
                    } else {

                        $("#send_otp").attr('disabled', true);
                    }
                },

            });
        })

        $(".formAjax").on('submit', function(e) {
            e.preventDefault();
            $("#id_update_btn").attr('disabled', true);
            var formdata = new FormData($(this)[0]);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: $(this).attr('action'),
                data: formdata,
                processData: false, // Important for FormData
                contentType: false, // Important for FormData
                success: function(data) {
                    if (data.errors) {
                        for (var i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    } else {
                        if (data.status) {
                            toastr.success(data.message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);

                        } else {
                            toastr.error(data.message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }

                    }
                },

            });
        })

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
                            $(".select_subcategory_" + dataid).val('')
                        }
                    }
                },
            });
        })


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
        let myLatlng = {
            lat: {{ $store->latitude }},
            lng: {{ $store->longitude }}
        };

        let map = new google.maps.Map(document.getElementById("map"), {
            zoom: 15,
            center: myLatlng,
        });

        let defaultMarker = null;
        let zonePolygon = null;
        let infoWindow = new google.maps.InfoWindow({
            content: "Click the map to get Lat/Lng!",
            position: myLatlng,
        });
        let bounds = new google.maps.LatLngBounds();

        function initMap() {
            // Create marker at default location
            defaultMarker = new google.maps.Marker({
                position: myLatlng,
                map: map,
                title: "Store Location",
                draggable: true
            });

            // Add click listener to marker
            defaultMarker.addListener('click', function() {
                infoWindow.setPosition(defaultMarker.getPosition());
                infoWindow.setContent("Store Location");
                infoWindow.open(map);
            });

            google.maps.event.addListener(defaultMarker, 'dragend', function(event) {
                let lat = event.latLng.lat();
                let lng = event.latLng.lng();

                let latInput = document.getElementById('latitude');
                let lngInput = document.getElementById('longitude');

                if (latInput) {
                    latInput.removeAttribute('readonly');
                    latInput.value = lat;
                    latInput.setAttribute('readonly', true);
                }

                if (lngInput) {
                    lngInput.removeAttribute('readonly');
                    lngInput.value = lng;
                    lngInput.setAttribute('readonly', true);
                }

                // console.log('Marker dragged - Lat: ' + lat + ', Lng: ' + lng);

            });

            // Create the initial InfoWindow.
            infoWindow.open(map);
            //get current location block
            infoWindow = new google.maps.InfoWindow();
            // Try HTML5 geolocation.
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        myLatlng = {
                            lat: myLatlng.lat,
                            lng: myLatlng.lng,
                        };
                        // Update marker position to current location
                        if (defaultMarker) {
                            defaultMarker.setPosition(myLatlng);
                        }

                        {{-- infoWindow.setPosition(myLatlng); --}}
                        {{-- infoWindow.setContent("Location found."); --}}
                        {{-- infoWindow.open(map); --}}
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

                // Move default marker to searched location
                if (places.length > 0 && defaultMarker) {
                    const place = places[0];
                    if (place.geometry && place.geometry.location) {
                        defaultMarker.setPosition(place.geometry.location);
                        document.getElementById('latitude').value = place.geometry.location.lat();
                        document.getElementById('longitude').value = place.geometry.location.lng();
                    }
                }

                // For each place, get the icon, name and location.
                const bounds = new google.maps.LatLngBounds();
                places.forEach((place) => {
                    if (!place.geometry || !place.geometry.location) {
                        console.log("Returned place contains no geometry");
                        return;
                    }

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
        $(document).ready(function() {
            $('#choice_zones').select2();

            $('#choice_zones').on('change', function() {
                let id = $(this).val();
                $.get({
                    url: '{{ url('/') }}/zone/get-coordinates/' + id,
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
                        google.maps.event.addListener(zonePolygon, 'click', function(
                            mapsMouseEvent) {
                            infoWindow.close();
                            // Create a new InfoWindow.
                            infoWindow = new google.maps.InfoWindow({
                                position: mapsMouseEvent.latLng,
                                content: JSON.stringify(mapsMouseEvent.latLng
                                    .toJSON(),
                                    null, 2),
                            });
                            let coordinates = JSON.stringify(mapsMouseEvent.latLng
                                .toJSON(), null,
                                2);
                            coordinates = JSON.parse(coordinates);
                            document.getElementById('latitude').value = coordinates[
                                'lat'];
                            document.getElementById('longitude').value = coordinates[
                                'lng'];

                            // Move marker to clicked location
                            if (defaultMarker) {
                                defaultMarker.setPosition(mapsMouseEvent.latLng);
                            }

                            infoWindow.open(map);
                        });
                    },
                });
            });
            $('#choice_zones').trigger('change');
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
        @if (Config::get('module.current_module_id') == 6)
            $('#confirmation_check').on('change', function() {
                if ($(this).prop('checked') == true) {
                    $('#gst_inp').hide()
                } else {
                    $('#gst_inp').show()

                }
            })
        @endif

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

        $('#payment_method_selec').on('change', function() {
            if ($(this).val() == 'bank') {
                $('.payment_field').hide();
                $('.payment_field_inp').removeAttr('required');
                $('.bank_field').show();
                $('.bank_field_inp').attr('required', true);
            } else if ($(this).val() == 'upi') {
                $('.payment_field').hide();
                $('.payment_field_inp').removeAttr('required');
                $('.upi_field').show();
                $('.upi_field_inp').attr('required', true);
            } else {
                $('.payment_field_inp').removeAttr('required');
                $('.payment_field').hide();
            }
        })
    </script>
    <script>
        document.querySelectorAll('.lightgallery').forEach(gallery => {
            lightGallery(gallery, {
                plugins: [lgVideo], // Add lgVideo plugin
                thumbnail: true,
                animateThumb: true,
                showThumbByDefault: true,
                thumbWidth: 80,
                thumbHeight: "auto",
                videojs: true // Enable video support
            });
        });
    </script>
@endpush
