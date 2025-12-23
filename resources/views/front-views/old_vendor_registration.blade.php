@extends('front-views.layout')

@section('title', 'Store Registration')
@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/toastr.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/view-pages/vendor-registration.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/landing/css/select2.min.css') }}" />
    <style>
        .secondary_nav {
            margin-top: 41px !important;
        }

        .select2-container .select2-selection--single {
            height: 42px;
            padding: 7px;
        }

        .select2-container .select2-selection--multiple {
            border: solid #d2d2d2 1px;
            height: 150px;
            padding: 7px;
            width: 100%;
        }

        .password-container {
            position: relative;
        }

        .password-input {
            padding-right: 40px;
            /* Extra padding for the toggle icon */
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 10;
        }

        .password-toggle i {
            font-size: 18px;
            color: #7a7a7a;
        }

        .password-toggle i:hover {
            color: #333;
        }

        /* Your custom CSS */
        .pac-container {
            z-index: 1050;
        }

        .modal-backdrop {
            z-index: 1040;
        }

        .modal {
            z-index: 1050;
        }

        .select2-container {
            width: 100% !important;
        }

        .otp-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 300px;
        }

        .otp-container h2 {
            margin-bottom: 20px;
        }

        .otp-container p {
            margin-bottom: 20px;
            color: #666;
        }

        .otp-form {
            display: flex;
            justify-content: space-between;
        }

        .otp-input {
            width: 55px;
            height: 55px;
            margin: 3px;
            text-align: center;
            font-size: 26px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .otp-input:focus {
            border-color: #007bff;
            outline: none;
        }
    </style>
@endpush
@section('content')

    <section class="m-0 ">
        <div class="container-fluid contact py-5" id="otp_screen">
            <div class="container py-5">
                <h1 class="text-center">Business Listing</h1>
                <div class="p-5 bg-light rounded" style="max-width: 550px; margin: 0 auto;">
                    <div class="row g-4">
                        <form class="otpForm" action="{{ route('send-otp') }}" method="post">
                            @csrf
                            <div class="mb-3">
                                <h4 class="text-center">Phone Verification</h4>

                                <label for="phoneInp" class="form-label">Phone Number</label>
                                <!-- class=iti__tel-input id="phoneInp" -->
                                <div class="d-flex align-items-center">
                                    <span
                                        style="background: white; padding: 5px; border: 1px solid #d8d8d8; border-radius: 9px;">+91</span>
                                    <input type="number" class="form-control " style="width: 100%;" name="phone"
                                        placeholder="Ex: 9988776655">
                                </div>
                                <div class="form-text text-danger response__phone"></div>
                            </div>
                            <button type="submit" class="w-100 btn btn-primary send_otp">Send OTP</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid contact pb-5" id="verify_screen" style="display: none;">
            <div class="container py-5">
                <h2 class="text-center">Enter OTP</h2>
                <div class="p-5 bg-light rounded" style="max-width: 550px; margin: 0 auto;">
                    <div class="row g-4">
                        <form class="otpForm" action="{{ route('verify-vendor-otp') }}" method="post">
                            @csrf
                            <input type="hidden" name="phone" id="ver_phone" value="">
                            <div class="d-flex justify-content-center">
                                <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                            </div>

                            <button type="submit" class="w-100 btn btn-primary  mt-3">Verify OTP</button>
                            <a href="javascript:;" class="d-flex justify-content-center resend_btn my-2 disabled"
                                id="resendOtpBtn" onclick="resendOTP()">Resend OTP</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--  style="display: none;" -->
        <div class="container  mt-5 pt-5" id="details_sc">
            <!-- Page Header -->
            <div class="section-header">
                <h2 class="title my-4">Store Application</span></h2>
            </div>
            @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
            @php($language = $language->value ?? null)
            @php($defaultLang = 'en')
            <!-- End Page Header -->

            <form class="js-validate registration_form" action="{{ route('restaurant.store_ajax') }}" method="post"
                enctype="multipart/form-data" id="form-id">
                @csrf
                <div class="card __card mb-3">
                    <div class="card-header">
                        <h5 class="card-title">
                            <svg width="20" x="0" y="0" viewBox="0 0 68 68" class="store-svg-logo" xml:space="preserve">
                                <g>
                                    <g>
                                        <path
                                            d="m62.99 57.53h-1.17v-29.22c-1.09-.47-2.02-1.25-2.67-2.23-1.08 1.63-2.93 2.71-5.03 2.71s-3.95-1.08-5.03-2.71c-1.08 1.63-2.93 2.71-5.03 2.71s-3.95-1.08-5.03-2.71c-1.08 1.63-2.93 2.71-5.03 2.71s-3.95-1.08-5.03-2.71c-1.08 1.63-2.92 2.71-5.02 2.71-2.11 0-3.97-1.09-5.05-2.74-1.09 1.61-2.92 2.67-5.01 2.67-2.1 0-3.95-1.08-5.03-2.71-.65.98-1.58 1.77-2.68 2.23v29.29h-1.17c-1.21 0-2.19.98-2.19 2.19v4.16h62.36v-4.16c0-1.21-.98-2.19-2.19-2.19zm-33.55 0h-16.45v-20.29c0-1.36 1.1-2.47 2.47-2.47h11.51c1.36 0 2.47 1.11 2.47 2.47zm24.43-9.54c0 .88-.71 1.59-1.59 1.59h-13.41c-.88 0-1.6-.71-1.6-1.59v-12.13c0-.88.72-1.59 1.6-1.59h13.41c.88 0 1.59.71 1.59 1.59z"
                                            fill="#000000" data-original="#000000"></path>
                                        <path d="m59.86 19.99h7.77l-3.07-6.5c-.33-.7-1.03-1.15-1.81-1.15h-5.46z"
                                            fill="#000000" data-original="#000000"></path>
                                        <path d="m10.72 12.27h-5.46c-.77 0-1.48.45-1.81 1.15l-3.07 6.5h7.76z" fill="#000000"
                                            data-original="#000000"></path>
                                        <path
                                            d="m60.15 21.99v.77c0 2.22 1.8 4.03 4.03 4.03 2.22 0 4.02-1.81 4.02-4.03v-.77z"
                                            fill="#000000" data-original="#000000"></path>
                                        <path
                                            d="m54.12 26.79c2.22 0 4.03-1.81 4.03-4.03v-.77h-8.06v.77c0 2.22 1.81 4.03 4.03 4.03z"
                                            fill="#000000" data-original="#000000"></path>
                                        <path d="m46.71 14.26-.39-1.92h-6.87l.52 7.65h7.9z" fill="#000000"
                                            data-original="#000000">
                                        </path>
                                        <path d="m9.86 22.69c0 2.22 1.81 4.03 4.03 4.03s4.03-1.81 4.03-4.03v-.7h-8.06z"
                                            fill="#000000" data-original="#000000"></path>
                                        <path d="m55.18 12.34h-6.82l1.16 5.73.39 1.92h7.84z" fill="#000000"
                                            data-original="#000000">
                                        </path>
                                        <path
                                            d="m19.92 22.76c0 2.22 1.8 4.03 4.03 4.03 2.22 0 4.02-1.81 4.02-4.03v-.77h-8.05z"
                                            fill="#000000" data-original="#000000"></path>
                                        <path d="m7.86 22.69v-.77h-8.06v.77c0 2.22 1.81 4.03 4.03 4.03s4.03-1.81 4.03-4.03z"
                                            fill="#000000" data-original="#000000"></path>
                                        <path d="m19.64 12.34h-6.81l-2.55 7.58h7.83z" fill="#000000"
                                            data-original="#000000">
                                        </path>
                                        <path d="m30.56 12.34-.52 7.65h7.92l-.51-7.65z" fill="#000000"
                                            data-original="#000000">
                                        </path>
                                        <path
                                            d="m44.06 26.79c2.22 0 4.03-1.81 4.03-4.03v-.77h-8.06v.77c0 2.22 1.81 4.03 4.03 4.03z"
                                            fill="#000000" data-original="#000000"></path>
                                        <path d="m28.55 12.34h-6.86l-1.55 7.65h7.9z" fill="#000000"
                                            data-original="#000000">
                                        </path>
                                        <path d="m29.97 22.76c0 2.22 1.81 4.03 4.03 4.03s4.03-1.81 4.03-4.03v-.77h-8.06z"
                                            fill="#000000" data-original="#000000"></path>
                                        <path
                                            d="m13.49 10.34h48.33v-2.03c0-2.31-1.87-4.19-4.18-4.19h-47.27c-2.31 0-4.19 1.88-4.19 4.19v1.96h7.33z"
                                            fill="#000000" data-original="#000000"></path>
                                    </g>
                                </g>
                            </svg> {{ translate('messages.store_info') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">

                        <div class="row g-4">
                            <div class="col-lg-6">
                                @if ($language)
                                    <div class="lang_form" id="default-form">
                                        <div class="mb-4">
                                            <div class="form-group">
                                                <label class="input-label"
                                                    for="default_name">{{ translate('messages.name') }}

                                                </label>
                                                <input type="text" name="name[]" id="default_name"
                                                    class="form-control __form-control"
                                                    placeholder="{{ translate('messages.store_name') }}" required>
                                            </div>
                                        </div>
                                        <input type="hidden" name="lang[]" value="default">
                                        <div class="mb-4">
                                            <div class="form-group mb-0">
                                                <label class="input-label"
                                                    for="address">{{ translate('messages.address') }}</label>
                                                <textarea type="text" id="address" name="address[]" placeholder="{{ translate('Ex: ABC Company') }}"
                                                    class="form-control __form-control h-120"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Button trigger modal -->





                                <div class="form-group mb-4 position-relative">
                                    <a href="javascript:;" data-bs-toggle="modal" style="right:0;"
                                        class=" position-absolute" data-bs-target="#missingZoneModal">Request Missing
                                        City</a>
                                    <label class="input-label" for="choice_zones">City<span class="form-label-secondary"
                                            data-toggle="tooltip" data-placement="right"
                                            data-original-title="{{ translate('messages.select_zone_for_map') }}"></span></label>
                                    <select name="zone_id" id="choice_zones" required
                                        class="form-control __form-control js-select2-custom js-example-basic-single"
                                        data-placeholder="Select City">
                                        <option value="" selected disabled>Select City</option>
                                        @foreach (\App\Models\Zone::active()->get() as $zone)
                                            @if (isset(auth('admin')->user()->zone_id))
                                                @if (auth('admin')->user()->zone_id == $zone->id)
                                                    <option value="{{ $zone->id }}" selected>{{ $zone->name }}
                                                    </option>
                                                @endif
                                            @else
                                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <input type="hidden" name="module_id" class="module_select" value="6"
                                    id="">
                                <div class="form-group mb-4 d-none">
                                    <label class="input-label fw-bold" for="choice_zones">Module</label><br>

                                    {{-- <input type="radio"  name="module_id" class="module_select" value="5"
                                        id="choice_zones5">
                                    <label style="    margin: 16px 0px;" class="input-label"
                                        for="choice_zones5">SHOPPING</label>
                                    <i class="fas fa-info-circle"
                                        title="List here to showcase sell your products to customers in your city."></i> --}}
                                    {{-- <br> --}}
                                    &nbsp; &nbsp;
                                    <input type="radio" checked name="module_id" class="module_select" value="6"
                                        id="choice_zones6">
                                    <label style="    margin: 16px 0px;" class="input-label" for="choice_zones6">MY
                                        CITY</label>
                                    <i class="fas fa-info-circle"
                                        title="List here to showcase local services, businesses, and skilled professionals in your city."></i>
                                    {{-- <br> --}} &nbsp; &nbsp;

                                </div>

                                <div class="form-group mb-4">
                                    <label class="input-label fw-bold" for="choice_zones">Vendor Type</label><br>
                                    <input type="radio" checked name="vendor_type" value="regular"
                                        id="vendor_regular">
                                    <label style="margin: 16px 0px;" class="input-label"
                                        for="vendor_regular">Regular</label>
                                    <input type="radio" name="vendor_type" value="composition"
                                        id="vendor_composition">
                                    <label style="margin: 16px 0px;" class="input-label"
                                        for="vendor_composition">Composition</label> &nbsp; &nbsp;

                                </div>

                                <div style="visibility: hidden; height:0px">
                                    <div class="form-group mb-4">
                                        <label class="input-label" for="latitude">{{ translate('messages.latitude') }}
                                            <span class="input-label-secondary"
                                                title="{{ translate('messages.store_lat_lng_warning') }}"><img
                                                    src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.store_lat_lng_warning') }}"></span></label>
                                        <input type="text" id="latitude" name="latitude"
                                            class="form-control __form-control"
                                            placeholder="{{ translate('messages.Ex:') }} -94.22213"
                                            value="{{ old('latitude') }}" required readonly>
                                    </div>
                                    <div class="form-group mb-4">
                                        <label class="input-label" for="longitude">{{ translate('messages.longitude') }}
                                            <span class="input-label-secondary"
                                                title="{{ translate('messages.store_lat_lng_warning') }}"><img
                                                    src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.store_lat_lng_warning') }}"></span></label>
                                        <input type="text" name="longitude" class="form-control __form-control"
                                            placeholder="{{ translate('messages.Ex:') }} 103.344322" id="longitude"
                                            value="{{ old('longitude') }}" required readonly>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <div class="form-group">
                                        <label class="input-label" id="timing_label"
                                            for="minimum_delivery_time">{{ translate('messages.approx_service_time') }}</label>
                                        <div class="input-group">
                                            <input type="number" id="minimum_delivery_time" name="minimum_delivery_time"
                                                class="form-control __form-control" placeholder="Min: 10"
                                                value="{{ old('minimum_delivery_time', '10') }}">
                                            <input type="number" name="maximum_delivery_time"
                                                class="form-control __form-control" placeholder="Max: 20"
                                                value="{{ old('maximum_delivery_time', '10') }}">
                                            <select name="delivery_time_type"
                                                class="form-control __form-control text-capitalize" required>
                                                <option value="min">{{ translate('messages.minutes') }}</option>
                                                <option value="hours">{{ translate('messages.hours') }}</option>
                                                <option value="days">{{ translate('messages.days') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <input type="checkbox" name="confirmation" id="confirmation_check">
                                    <label for="confirmation_check">I hereby declare that my annual turnover is below 20
                                        lakhs.</label>
                                </div>

                                {{-- <div class="btn-group mb-4" id="btn_elem" role="group"
                                    aria-label="Basic checkbox toggle button group">
                                    <input type="radio" name="doc_type_check" value="gst" class="btn-check GST_inp" id="btncheck1"
                                        autocomplete="off" checked> 
                                    <label class="btn btn-outline-primary" for="btncheck1">GST</label>

                                    <input type="radio" name="doc_type_check" value="id" class="btn-check doc_inp" id="btncheck2"
                                        autocomplete="off">
                                    <label class="btn btn-outline-primary" for="btncheck2">ID Proof</label>

                                </div> --}}

                                <div class="" id="gst_elem">
                                    <div class="mb-4 " id="gst_num">
                                        <div class="form-group">
                                            <label class="input-label" id="" for="gst_num">GST
                                                Number<span>*</span></label>
                                            <input type="text" placeholder="GST Number" id="gst_num"
                                                name="gst_number" class="form-control __form-control">
                                        </div>
                                    </div>
                                    <div class="mb-4" id="gst_inp">
                                        <div class="form-group">
                                            <label class="input-label" id="timing_label" for="minimum_delivery_time">GST
                                                (pdf / image)<span>*</span></label>

                                            <input type="file" id="gst_doc" name="gst_doc"
                                                class="form-control __form-control">

                                        </div>
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
                                            <label class="input-label" id="id_doc" for="minimum_delivery_time">ID
                                                Proof
                                                (pdf / image)</label>

                                            <input type="file" id="id_doc" name="id_doc"
                                                class="form-control __form-control">

                                        </div>
                                    </div>
                                </div>
                                <div class="mb-4" id="shop_business_type_elem">
                                    <div class="form-group">
                                        <label class="input-label" id=""
                                            for="business_type">Type:<span>*</span></label>
                                        <select name="business_type" class="form-select business_type"
                                            id="shop_business_type">
                                            <option value=""></option>
                                            @foreach ($shop_stores_type as $key => $value)
                                                <option value="{{ $value->name }}">{{ $value->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-4" id="mycity_business_type_elem" style="display: none;">
                                    <div class="form-group">
                                        <label class="input-label" id=""
                                            for="business_type">Type:<span>*</span></label>
                                        <select class="form-select business_type" id="mycity_business_type">
                                            <option value=""></option>
                                            @foreach ($service_stores_type as $key => $value)
                                                <option value="{{ $value->name }}">{{ $value->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-4 verification_inp" id="">
                                    <div class="form-group">
                                        <label class="input-label" id="google_verification1"
                                            for="google_verification">Your Website or Google Business
                                            Link<span>*</span></label>
                                        <input type="text" placeholder="Your Google Business Link"
                                            id="google_verification" name="google_verification"
                                            class="form-control __form-control">
                                    </div>
                                </div>
                                <!-- verification_inp -->
                                <div class="mb-4 " style="display: none;" id="">
                                    <div class="form-group">
                                        <label class="input-label" id="other_verification1"
                                            for="other_verification">Other Business Link</label>
                                        <input type="text" placeholder="Other Business Link" id="other_verification"
                                            name="other_verification" class="form-control __form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <h5> Select your shop / business location</h5>

                                <div class="p-3 border border-success-light rounded mb-3">
                                    <input id="pac-input" class="controls rounded" style="height: 3em;width:fit-content;"
                                        title="{{ translate('messages.search_your_location_here') }}" type="text"
                                        placeholder="{{ translate('messages.search_here') }}" />
                                    <div class="h-255" id="map"></div>
                                </div>
                                <div class="mb-4">
                                    <div id="locationDisplay"></div>
                                </div>
                                <div class="d-flex gap-4" style="display: none;">
                                    <div class="form-group w-140px flex-grow-1 d-flex flex-column justify-content-between">
                                        <label class="input-label pt-2">{{ translate('Upload Cover Photo') }}<small
                                                class="text-danger">
                                                * ({{ translate('messages.ratio') }} 2:1 )</small>
                                        </label>
                                        <label class="image--border pos ition-relative">
                                            <img class="__register-img" id="coverImageViewer"
                                                src="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                                alt="Product thumbnail" />
                                            <div class="icon-file-group">
                                                <div class="icon-file">
                                                    <input type="file" name="cover_photo" id="coverImageUpload"
                                                        class="form-control __form-control"
                                                        accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                    <img src="{{ asset('public/assets/admin/img/pen.png') }}"
                                                        alt="edit">
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="form-group w-140px d-flex flex-column justify-content-between">
                                        <label class="input-label pt-2">{{ translate('messages.store_logo') }}<small
                                                class="text-danger">
                                                * (
                                                {{ translate('messages.ratio') }}
                                                1:1
                                                )</small></label>
                                        <label class="image--border position-relative img--100px">
                                            <img class="__register-img" id="logoImageViewer"
                                                src="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                                alt="Product thumbnail" />

                                            <div class="icon-file-group">
                                                <div class="icon-file">
                                                    <input type="file" name="logo" id="customFileEg1"
                                                        class="form-control __form-control"
                                                        accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*"
                                                        required>
                                                    <img src="{{ asset('public/assets/admin/img/pen.png') }}"
                                                        alt="eidt">
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                {{-- style="display: none;" --}}

                                <div class="categroy_set_service">
                                    <div class="form-group single_cat_inp_group">
                                        <div class="form-group mb-4">
                                            <label class="input-label" id="" for="other_verification">Category
                                                1<span>*</span></label>
                                            <select name="category_1" data-id="1"
                                                class="category_select form-control __form-control js-select2-custom js-example-basic-single"
                                                data-placeholder="Category">
                                                <option value=""></option>
                                                @foreach ($module_categories as $cat)
                                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                            <small>Categories selection can be changed later from dashboard</small>

                                        </div>
                                    </div>
                                    <div class="form-group subcategory_1" style="display: none;">
                                        <div class="form-group mb-4">
                                            <label class="input-label" id=""
                                                for="other_verification">Services</label>
                                            <select name="services_1[]" multiple="multiple"
                                                class="select_subcategory_1 form-control __form-control js-select2-custom js-example-basic-multiple"
                                                data-placeholder="Subcategory">
                                                <option value=""></option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group single_cat_inp_group">
                                        <label class="input-label" id="" for="other_verification">Category 2
                                            <span>(optional)</span></label>
                                        <select name="category_2" data-id="2"
                                            class="category_select form-control __form-control js-select2-custom js-example-basic-single"
                                            data-placeholder="Category">
                                            <option value="-1">None</option>
                                            @foreach ($module_categories as $cat)
                                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group subcategory_2" style="display: none;">
                                        <div class="form-group mb-4">
                                            <label class="input-label" id=""
                                                for="other_verification">Services</label>
                                            <select name="services_2[]" multiple="multiple" id="category_select"
                                                class="select_subcategory_2 form-control __form-control js-select2-custom js-example-basic-multiple"
                                                data-placeholder="Subcategory">
                                                <option value=""></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="categroy_set_shop"  style="display: none;">
                                    <div class="form-group shop_categories">
                                        <div class="form-group mb-4">
                                            <label class="input-label" id="" for="shop_categories">Categories
                                                (max 20)</label>
                                            <select name="shop_categories[]" multiple="multiple" id="shop_categories"
                                                class=" form-control __form-control  select_2_max_20"
                                                data-placeholder="Categories">
                                                <option value=""></option>
                                                @foreach ($module_categories as $cat)
                                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                            <small>Categories selection can be changed later from dashboard</small>
                                        </div>
                                    </div>
                                </div>
                             
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card __card bg-F8F9FC mb-3">
                    <div class="card-header">
                        <h5 class="card-title">
                            <svg width="20" x="0" y="0" viewBox="0 0 460.8 460.8" xml:space="preserve"
                                class="store-svg-logo">
                                <g>
                                    <g>
                                        <g>
                                            <g>
                                                <path
                                                    d="M230.432,239.282c65.829,0,119.641-53.812,119.641-119.641C350.073,53.812,296.261,0,230.432,0
                                                                                                                                                    S110.792,53.812,110.792,119.641S164.604,239.282,230.432,239.282z"
                                                    fill="#020202" data-original="#000000" class=""></path>
                                                <path
                                                    d="M435.755,334.89c-3.135-7.837-7.314-15.151-12.016-21.943c-24.033-35.527-61.126-59.037-102.922-64.784
                                                                                                                                                    c-5.224-0.522-10.971,0.522-15.151,3.657c-21.943,16.196-48.065,24.555-75.233,24.555s-53.29-8.359-75.233-24.555
                                                                                                                                                    c-4.18-3.135-9.927-4.702-15.151-3.657c-41.796,5.747-79.412,29.257-102.922,64.784c-4.702,6.792-8.882,14.629-12.016,21.943
                                                                                                                                                    c-1.567,3.135-1.045,6.792,0.522,9.927c4.18,7.314,9.404,14.629,14.106,20.898c7.314,9.927,15.151,18.808,24.033,27.167
                                                                                                                                                    c7.314,7.314,15.673,14.106,24.033,20.898c41.273,30.825,90.906,47.02,142.106,47.02s100.833-16.196,142.106-47.02
                                                                                                                                                    c8.359-6.269,16.718-13.584,24.033-20.898c8.359-8.359,16.718-17.241,24.033-27.167c5.224-6.792,9.927-13.584,14.106-20.898
                                                                                                                                                    C436.8,341.682,437.322,338.024,435.755,334.89z"
                                                    fill="#020202" data-original="#000000" class=""></path>
                                            </g>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                            {{ translate('messages.owner_info') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-3 col-lg-3 col-sm-12">
                                <div class="form-group">
                                    <label class="input-label"
                                        for="f_name">{{ translate('messages.first_name') }}</label>
                                    <input type="text" id="f_name" name="f_name"
                                        class="form-control __form-control"
                                        placeholder="{{ translate('messages.first_name') }}" value="{{ old('f_name') }}"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-3 col-lg-3 col-sm-12">
                                <div class="form-group">
                                    <label class="input-label"
                                        for="l_name">{{ translate('messages.last_name') }}</label>
                                    <input type="text" id="l_name" name="l_name"
                                        class="form-control __form-control"
                                        placeholder="{{ translate('messages.last_name') }}" value="{{ old('l_name') }}"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-3 col-lg-3 col-sm-12">
                                <div class="form-group">
                                    <input type="hidden" name="otp" id="ver_otp_inp">
                                    <label class="input-label" for="phone">{{ translate('messages.phone') }}</label>
                                    <input type="text" id="phone" style="pointer-events: none;" name="phone"
                                        class="form-control __form-control ver_phone_input"
                                        placeholder="{{ translate('messages.Ex:') }} 99********"
                                        value="{{ old('phone') }}" required>
                                </div>

                            </div>
                            <div class="col-md-3 col-lg-3 col-sm-12">
                                <div class="form-group">
                                    <label class="input-label" for="secondary_phone">Secondary Phone
                                        <i>(optional)</i></label>
                                    <input type="text" id="secondary_phone" name="secondary_phone"
                                        class="form-control __form-control "
                                        placeholder="{{ translate('messages.Ex:') }} 99********"
                                        value="{{ old('secondary_phone') }}">
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="card __card bg-F8F9FC mb-3">
                    <div class="card-header">
                        <h5 class="card-title">
                            <svg width="20" x="0" y="0" viewBox="0 0 460.8 460.8" class="store-svg-logo"
                                xml:space="preserve">
                                <g>
                                    <g>
                                        <g>
                                            <g>
                                                <path
                                                    d="M230.432,239.282c65.829,0,119.641-53.812,119.641-119.641C350.073,53.812,296.261,0,230.432,0
                                                                                                                                                    S110.792,53.812,110.792,119.641S164.604,239.282,230.432,239.282z"
                                                    fill="#020202" data-original="#000000" class=""></path>
                                                <path
                                                    d="M435.755,334.89c-3.135-7.837-7.314-15.151-12.016-21.943c-24.033-35.527-61.126-59.037-102.922-64.784
                                                                                                                                                    c-5.224-0.522-10.971,0.522-15.151,3.657c-21.943,16.196-48.065,24.555-75.233,24.555s-53.29-8.359-75.233-24.555
                                                                                                                                                    c-4.18-3.135-9.927-4.702-15.151-3.657c-41.796,5.747-79.412,29.257-102.922,64.784c-4.702,6.792-8.882,14.629-12.016,21.943
                                                                                                                                                    c-1.567,3.135-1.045,6.792,0.522,9.927c4.18,7.314,9.404,14.629,14.106,20.898c7.314,9.927,15.151,18.808,24.033,27.167
                                                                                                                                                    c7.314,7.314,15.673,14.106,24.033,20.898c41.273,30.825,90.906,47.02,142.106,47.02s100.833-16.196,142.106-47.02
                                                                                                                                                    c8.359-6.269,16.718-13.584,24.033-20.898c8.359-8.359,16.718-17.241,24.033-27.167c5.224-6.792,9.927-13.584,14.106-20.898
                                                                                                                                                    C436.8,341.682,437.322,338.024,435.755,334.89z"
                                                    fill="#020202" data-original="#000000" class=""></path>
                                            </g>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                            {{ translate('messages.login_info') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4 col-sm-12 col-lg-4">
                                <div class="form-group">
                                    <label class="input-label" for="email">{{ translate('messages.email') }}</label>
                                    <input type="email" id="email" name="email"
                                        class="form-control __form-control"
                                        placeholder="{{ translate('messages.Ex:') }} ex@example.com"
                                        value="{{ old('email') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-12 col-lg-4">
                                <div class="form-group">
                                    <label class="input-label" for="passwordInput">
                                        {{ translate('messages.password') }} <i class="fa fa-info-circle"
                                            aria-hidden="true"></i>
                                    </label>
                                    <div class="input-group password-container">
                                        <input type="password" name="password"
                                            placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}"
                                            class="form-control __form-control form-control __form-control-user password-input"
                                            minlength="8" id="passwordInput" required value="{{ old('password') }}">
                                        <span class="password-toggle"><i class="fa fa-eye-slash"></i></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 col-sm-12 col-lg-4">
                                <div class="form-group">
                                    <label class="input-label" for="confirmPasswordInput">
                                        {{ translate('messages.confirm_password') }}
                                    </label>
                                    <div class="input-group password-container">
                                        <input type="password" name="confirm-password"
                                            class="form-control __form-control form-control __form-control-user password-input"
                                            minlength="8" id="confirmPasswordInput"
                                            placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}"
                                            required value="{{ old('confirm-password') }}">
                                        <span class="password-toggle"><i class="fa fa-eye-slash"></i></span>
                                    </div>
                                    <div class="pass invalid-feedback">{{ translate('messages.password_not_matched') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <label>
                    <input type="checkbox" name="terms" value="accept" required>
                    I accept the <a id="termsLink" href="{{ route('store-terms-and-conditions', ['shop']) }}"
                        target="_blank">Terms and Conditions</a>.
                </label>
                <div class="text-end pt-3">
                    <button type="submit"
                        class="btn btn-primary text-white btn-lg form-submit-button">{{ translate('messages.submit') }}</button>
                </div>
        </div>
        </form>
        <!-- Modal -->
        <div class="modal fade" id="missingZoneModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Request Missing City</h5>
                        <button type="button" class="btn-close dismiss-modal" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if (!auth('web')->user())
                            <input type="email" name="user_email" placeholder="Email" id="user_email"
                                class="form-control my-2">
                        @endif
                        <input type="hidden" id="selected_palce">
                        <input type="text" id="placeSearchInp" class="form-control my-2" placeholder="search place">

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" id="missing_zone_btn" class="btn btn-primary">Submit</button>
                    </div>

                </div>
            </div>
        </div>
        </div>
    </section>

@endsection
@push('script_2')

    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.jsmin/js/spartan-multi-image-picker.js') }}"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=drawing,places">
    </script>
    <script type="text/javascript">
        "use strict";
        let myLatlng = {
            lat: 23.757989,
            lng: 90.360587
        };
        let map = new google.maps.Map(document.getElementById("map"), {
            zoom: 13,
            center: myLatlng,
        });
        let zonePolygon = null;
        let userMarker = null;
        let infoWindow = new google.maps.InfoWindow({
            content: "Click the map to get Address!",
            position: myLatlng,
        });
        let bounds = new google.maps.LatLngBounds();
        let geocoder = new google.maps.Geocoder(); // Geocoder for address conversion

        // Function to get and display user's current location
        function getUserLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        let userLatlng = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };

                        // Center map on user location
                        map.setCenter(userLatlng);
                        map.setZoom(15);

                        // Remove old marker if exists
                        if (userMarker) {
                            userMarker.setMap(null);
                        }

                        // Add marker for user location
                        userMarker = new google.maps.Marker({
                            position: userLatlng,
                            map: map,
                            title: "You are here!",
                            icon: {
                                url: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png",
                            }
                        });

                        // Store Lat & Lng in hidden fields
                        document.getElementById('latitude').value = userLatlng.lat;
                        document.getElementById('longitude').value = userLatlng.lng;

                        console.log(document.getElementById('latitude').value)
                        console.log(document.getElementById('longitude').value)

                        // Convert lat/lng to address
                        geocoder.geocode({
                            location: userLatlng
                        }, function(results, status) {
                            if (status === "OK" && results[0]) {
                                let userAddress = results[0].formatted_address;
                                document.getElementById('locationDisplay').innerText =
                                    `Your Location: ${userAddress}`;
                            } else {
                                document.getElementById('locationDisplay').innerText = "Address not found.";
                            }
                        });
                    },
                    function() {
                        alert("Geolocation failed. Please enable location services.");
                    }
                );
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        // Call function to get user location on page load
        getUserLocation();

        // Handle zone selection
        $('#choice_zones').on('change', function() {
            let id = $(this).val();
            $.get({
                url: 'https://admin.mychitti.net/zone/get-coordinates/' + id,
                dataType: 'json',
                success: function(data) {
                    console.log(data);

                    // Remove existing polygon if present
                    if (zonePolygon) {
                        zonePolygon.setMap(null);
                    }

                    // Create new zone polygon
                    zonePolygon = new google.maps.Polygon({
                        paths: data.coordinates,
                        strokeColor: "#FF0000",
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: 'white',
                        fillOpacity: 0,
                    });

                    // Set polygon on map
                    zonePolygon.setMap(map);

                    // Adjust map bounds to fit the zone
                    bounds = new google.maps.LatLngBounds();
                    zonePolygon.getPaths().forEach(function(path) {
                        path.forEach(function(latlng) {
                            bounds.extend(latlng);
                        });
                    });

                    // Fit the map to the selected zone
                    map.fitBounds(bounds);
                    map.setCenter(data.center);

                    // Add click event on the polygon
                    google.maps.event.addListener(zonePolygon, 'click', function(mapsMouseEvent) {
                        infoWindow.close();
                        infoWindow = new google.maps.InfoWindow({
                            position: mapsMouseEvent.latLng,
                            content: "Fetching address...",
                        });

                        // Convert clicked location to address
                        geocoder.geocode({
                            location: mapsMouseEvent.latLng
                        }, function(results, status) {
                            if (status === "OK" && results[0]) {
                                let clickedAddress = results[0].formatted_address;

                                // Store Lat & Lng in hidden fields
                                document.getElementById('latitude').value =
                                    mapsMouseEvent.latLng.lat();
                                document.getElementById('longitude').value =
                                    mapsMouseEvent.latLng.lng();

                                console.log(document.getElementById('latitude').value)
                                console.log(document.getElementById('longitude').value)

                                // Show address in an element
                                document.getElementById('locationDisplay').innerText =
                                    `Selected Location: ${clickedAddress}`;

                                infoWindow.setContent(clickedAddress);
                            } else {
                                document.getElementById('locationDisplay').innerText =
                                    "Address not found.";
                                infoWindow.setContent("Address not found.");
                            }
                        });

                        infoWindow.open(map);
                    });

                    // Keep user's location marker visible
                    if (userMarker) {
                        userMarker.setMap(map);
                    }
                },
            });
        });

        $(".use_current_location").on('click', function() {

        })


        $(document).on('change', '.business_type', function() {
            console.log($(this).val())
            if ($(this).val() == 'Business') {
                $(".verification_inp").show()
            } else {
                $(".verification_inp").hide()
            }
        })
        $(document).ready(function() {
            // Show/Hide Password Toggle (Reused for both fields)
            $('.password-toggle').on('click', function() {
                const passwordInput = $(this).siblings('.password-input');
                const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
                passwordInput.attr('type', type);
                $(this).find('i').toggleClass('fa-eye fa-eye-slash');
            });

            // Password Match Validation (Reused for confirm-password validation)
            $('.password-input').on('keyup', function() {
                const password = $('#passwordInput').val();
                const confirmPassword = $('#confirmPasswordInput').val();
                const invalidFeedback = $('#confirmPasswordInput').siblings('.invalid-feedback');

                if (password && confirmPassword && password !== confirmPassword) {
                    invalidFeedback.show(); // Show feedback if passwords don't match
                    $('#confirmPasswordInput').addClass('is-invalid');
                } else {
                    invalidFeedback.hide(); // Hide feedback if passwords match
                    $('#confirmPasswordInput').removeClass('is-invalid');
                }
            });
        });

        $(".otpForm").on("submit", function(e) {
            e.preventDefault();
            var formData = new FormData($(this)[0]);
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
            });
            $.post({
                url: $(this).attr("action"),
                processData: false,
                contentType: false,
                async: false,
                cache: false,
                data: formData,
                beforeSend: function() {},
                success: function(data) {
                    if (data.status) {
                        if (data.action == 'otp_sent') {
                            $('#verify_screen').show();
                            showTimer();
                            $('#otp_screen').hide();
                            $('#ver_phone').val(data.phone);
                        } else if (data.action == 'verified') {
                            $('#verify_screen').hide();
                            $('.ver_phone_input').val($('#ver_phone').val());
                            $('#ver_otp_inp').val(data.otp);
                            $('#details_sc').show();
                        }
                    }
                    toasterNotification(data.message);
                },
            });
        });

        function showTimer() {
            var resendBtn = document.getElementById("resendOtpBtn");

            // Disable the button and add a timer
            resendBtn.classList.add("disabled");
            resendBtn.style.pointerEvents = "none"; // Prevent clicking
            let timer = 60;

            let countdown = setInterval(function() {
                resendBtn.innerText = `Resend OTP in ${timer}s`;
                timer--;

                if (timer < 0) {
                    clearInterval(countdown);
                    resendBtn.innerText = "Resend OTP";
                    resendBtn.classList.remove("disabled");
                    resendBtn.style.pointerEvents = "auto"; // Re-enable clicking
                }
            }, 1000);
        }

        function toasterNotification(msg) {
            $("#toast").text(msg);
            $("#toast").addClass("show");
            setTimeout(function() {
                $("#toast").removeClass("show");
            }, 3000);
        }

        $(document).ready(function() {
            $('#module_id').select2({
                ajax: {
                    url: '{{ url('/') }}/store/get-all-modules/',
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
        });

        $('#confirmation_check').on('change', function() {
            if ($(this).prop('checked') == true && $('#module_select').val() != '5') {
                $('#gst_elem').hide()
                $('#id_elem').hide()
                $('#btn_elem').hide()
            } else {
                $('#btn_elem').show()
                $('#gst_elem').show()
                $('#id_elem').show()

            }
        })

        function initAutocomplete() {
            // Get the input element where the user will type the place name
            const input = document.querySelector('#placeSearchInp');

            // Initialize the autocomplete service
            const autocomplete = new google.maps.places.Autocomplete(input);

            // Optionally, restrict the results to a specific country or type
            // autocomplete.setComponentRestrictions({'country': ['us']});
            // autocomplete.setTypes(['geocode']); // To restrict to only geographic results

            // Add a listener to handle the event when the user selects a place
            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();

                if (!place.geometry) {
                    console.log("No details available for input: '" + place.name + "'");
                    return;
                }

                // Optionally, you can handle the selected place details here
                console.log('Place selected:', place);
                $("#selected_palce").val(place.formatted_address);
            });
        }

        // Load the autocomplete feature on page load
        google.maps.event.addDomListener(window, 'load', initAutocomplete);
    </script>
    <script src="{{ asset('public/assets/admin/js/view-pages/vendor-registration.js') }}"></script>
    @if (isset($recaptcha) && $recaptcha['status'] == 1)
        <script type="text/javascript">
            "use strict";
            let onloadCallback = function() {
                grecaptcha.render('recaptcha_element', {
                    'sitekey': '{{ \App\CentralLogics\Helpers::get_business_settings('recaptcha')['site_key'] }}'
                });
            };
        </script>
        <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
        <script>
            "use strict";
            $("#form-id").on('submit', function(e) {
                let response = grecaptcha.getResponse();

                if (response.length === 0) {
                    e.preventDefault();
                    toastr.error("{{ translate('messages.Please check the recaptcha') }}");
                }
            });
        </script>
    @endif



    <script src="{{ asset('public/assets/landing/js/select2.min.js') }}"></script>

    <script>
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

        const inputs = document.querySelectorAll('input[type="tel"]');

        inputs.forEach(input => {
            window.intlTelInput(input, {
                initialCountry: "in",
                utilsScript: "https://mychitti.net/public/assets/admin/intltelinput/js/utils.js",
                autoInsertDialCode: true,
                nationalMode: false,
                formatOnDisplay: false,
            });
        });


        $('.module_select').on('change', function() {
            var selectedValue = $(this).val();
            if (selectedValue == 6) {
                // hide tax and del time
                $('#termsLink').attr('href', '/store-terms-and-conditions/my-city')
                $('.categroy_set_shop').hide()
                $('#shop_business_type_elem').hide();
                $('#mycity_business_type_elem').show();
                $('#shop_business_type').removeAttr('name');
                $('#mycity_business_type').attr('name', 'business_type');
                $('.categroy_set_service').show()
                $("#timing_label").text('Approx service time')
            } else {
                // show del time and tax 
                $('#termsLink').attr('href', '/store-terms-and-conditions/shop')
                $('.categroy_set_shop').show()
                $('.categroy_set_service').hide()
                $('#mycity_business_type').removeAttr('name');
                $('#shop_business_type').attr('name', 'business_type');

                $("#timing_label").text('Approx delivery time')
                if (selectedValue == 5) {
                    $('#hospital_business_type_elem').show();
                    $('#shop_business_type_elem').hide();
                    $('#mycity_business_type_elem').hide();
                }
            }

            // fetch module wise categories
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.get({
                url: "{{ route('fetch-categories') }}",
                data: {
                    id: selectedValue
                },
                success: function(data) {
                    if (data) {

                        if (data.categories.length) {
                            var html = '<option ></option>';
                            data.categories.forEach(element => {
                                html += '<option value="' + element.id + '">' + element.name +
                                    '</option>';
                            });
                            $(".category_select").html(html)
                        } else {
                            $(".category_select").html('')
                        }
                    }
                },
            });
        });
        $("#missing_zone_btn").on('click', function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('missing-zone-request') }}",
                data: {
                    user_mail: $("#user_email").val(),
                    place: $('#selected_palce').val()
                },
                success: function(data) {
                    console.log(data)
                    if (data.status) {
                        $('.dismiss-modal').click()
                        toasterNotification(data.message);
                    }
                },

            });
        })

        function resendOTP() {
            showTimer();
            $(".send_otp").click();

        }
        $(document).on('input', '.otp-input', function(e) {
            const $inputs = $('.otp-input');
            const index = $inputs.index(this);

            if (this.value.length === this.maxLength && index < $inputs.length - 1) {
                $inputs.eq(index + 1).focus();
            }
        });

        $(document).on('submit', '.registration_form', function(e) {
            console.log($('input[name="latitude"]').val())
            $('body').css('pointer-events', 'none')
            $('.form-submit-button').text('Please Wait ...')
            console.log('fsd')
            e.preventDefault();
            var formData = new FormData(this);

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: $(this).attr('action'),
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loading').show()
                },
                success: function(data) {
                    {{-- var data  = JSON.parse(data) --}}
                    console.log(data)
                    console.log(data.errors)
                    if (data.errors) {
                        data.errors.forEach(element => {
                            toastr.error(element.message);
                        });
                    } else {
                        if (data.status) {
                            toastr.success(data.message);
                            setTimeout(() => {
                                window.location.reload();
                            }, 500);
                        } else {
                            toastr.error(data.message);
                        }
                    }
                    $('body').css('pointer-events', 'all')
                    $('.form-submit-button').text('Submit')

                },
                complete: function() {
                    $('body').css('pointer-events', 'all')

                    $('#loading').hide()
                    $('.form-submit-button').text('Submit')

                }
            });
        })

        $("input[name=doc_type_check]").on('change', function() {
            console.log('changed');
            if ($(this).prop('checked') == true && $(this).hasClass('GST_inp')) {
                // console.log('gst inp checked')
                $('#id_elem').hide()
                $('#gst_elem').show()

            } else {
                $('#id_elem').show()
                $('#gst_elem').hide()
                // console.log('gst inp not checkeed')
            }
        })
    </script>
@endpush
