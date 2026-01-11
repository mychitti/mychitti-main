@extends('front-views.layout')

@section('title', 'Store Registration')
@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/toastr.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/view-pages/vendor-registration.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/landing/css/select2.min.css') }}" />
    <!-- CLAIM ALERT CSS -->

    <style>
        .claim-modal-icon {
            width: 48px;
            height: 48px;
            background: #dbeafe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .claim-modal-icon svg {
            width: 28px;
            height: 28px;
            color: #2563eb;
        }

        .claim-modal-header-content {
            display: flex;
            align-items: start;
        }

        .claim-modal-text {
            flex: 1;
        }

        .claim-business-info {
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
        }

        .claim-business-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .claim-business-name {
            font-size: 16px;
            font-weight: 600;
            color: #212529;
            margin: 0;
        }

        .modal-header {
            border-bottom: 1px solid #e9ecef;
        }

        .modal-footer {
            border-top: none;
            padding-top: 0;
        }
    </style>

    <style>
        .step {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .select2-selection.select2-selection--multiple {
            height: fit-content !important;
        }

        .step-circle {

            cursor: pointer;
        }

        .step-circle:hover {
            background-color: rgb(137, 183, 213);

        }

        .step-header {
            margin-left: 10%;
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }

        #submitBtn,
        #nextBtn {
            background-color: #0e75b8;
            color: white;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #ccc;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
            position: relative;
        }

        .step-circle.active {
            background-color: #0e75b8;
        }

        .step-title {
            text-align: start;
            font-size: 12px;
            margin-top: 0.5rem;
        }

        .step-line {
            position: absolute;
            top: 20px;
            left: 0;
            right: 178px;
            height: 2px;
            background-color: #ccc;
            z-index: 0;
        }
    </style>
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
        <div id="success_elem">
        </div>
        <div class="container mt-5 pt-5">


            <!-- Step Header -->
            <div class="step-header">
                <div class="step-line"></div>
                <div class="text-center" style="width: 25%;">
                    <div class="step-circle active" data-step="1">1</div>
                    <div class="step-title">Phone Verification</div>
                </div>
                <div class="text-center" style="width: 25%;">
                    <div class="step-circle" data-step="2">2</div>
                    <div class="step-title">Basic Info</div>
                </div>
                <div class="text-center" style="width: 25%;">
                    <div class="step-circle" data-step="3">3</div>
                    <div class="step-title">Business Info</div>
                </div>
                <div class="text-center" style="width: 25%;">
                    <div class="step-circle" data-step="4">4</div>
                    <div class="step-title">Services</div>
                </div>
                <div class="text-center" style="width: 25%;">
                    <div class="step-circle" data-step="5">5</div>
                    <div class="step-title">Owner Info</div>
                </div>
                <div class="text-center" style="width: 25%;">
                    <div class="step-circle" data-step="6">6</div>
                    <div class="step-title">Uploads</div>
                </div>
            </div>

            <!-- Step Content -->
            <div id="stepsContainer ">
                <div class="step " data-step="1">
                    <form id="otp_screen" class="otpForm" style="width: 400px;" action="{{ route('send-otp') }}"
                        method="post">
                        @csrf

                        <div class="mb-3">
                            <h4 class="text-center">Phone Verification</h4>

                            <label for="phoneInp" class="form-label w-100 text-center">Phone Number</label>
                            <!-- class=iti__tel-input id="phoneInp" -->
                            <div class="d-flex justify-content-center">
                                {{-- <span
                                    style="background: white; padding: 5px; border: 1px solid #d8d8d8; border-radius: 9px;">+91</span> --}}
                                <input type="tel" class="form-control phone_number" style="width: 100%;" name="phone"
                                    placeholder="Ex: 9988776655">
                            </div>
                            <div class="form-text text-danger response__phone"></div>
                        </div>
                        <div class="d-flex justify-content-center">
                            <button type="submit" class=" btn btn-primary send_otp" style="width: 246px;">Send OTP</button>
                        </div>
                    </form>
                    <form id="verify_screen" style="display: none;" class="otpForm"
                        action="{{ route('verify-vendor-otp') }}" method="post">
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
                <div class="step d-none" data-step="2">
                    <h4 class="text-center">Basic Info</h4><br>
                    <form class="js-validate registration_form2 row col-12" action="{{ route('restaurant.store_ajax') }}"
                        method="post" enctype="multipart/form-data" id="form-id">
                        @csrf
                        <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}" readonly>
                        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}" readonly>
                        <div class="col-md-6" id="default-form">
                            <div class="mb-3">
                                <div class="form-group">
                                    <label class="input-label" for="default_name">{{ translate('messages.name') }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="name[]" id="default_name"
                                        class="form-control __form-control"
                                        placeholder="{{ translate('messages.store_name') }}" required>
                                </div>
                            </div>
                            <input type="hidden" name="lang[]" value="default">
                            <div class="mb-3">
                                <div class="form-group mb-0">
                                    <label class="input-label" for="address">{{ translate('messages.address') }}</label>
                                    <textarea type="text" id="address" name="address[]" placeholder="{{ translate('Ex: ABC Company') }}"
                                        class="form-control __form-control h-120"></textarea>
                                </div>
                            </div>
                            <div class="form-group mb-3 position-relative">
                                <a href="javascript:;" data-bs-toggle="modal" style="right:0;"
                                    class=" position-absolute" data-bs-target="#missingZoneModal">Request Missing
                                    City</a>
                                <label class="input-label" for="choice_zones">City<span
                                        class="text-danger">*</span></label>
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
                            <div class="mb-4">
                                <input type="text" name="locationDisplay" id="locationDisplay"
                                    class="form-control __form-control" placeholder="Location" readonly>
                            </div>
                        </div>
                        <div class="mb-4 col-md-6">

                            <h5> Select your shop / business location</h5>

                            <div class="p-3 border border-success-light rounded mb-3">
                                <input id="pac-input" class="controls rounded" style="height: 3em;width:fit-content;"
                                    title="{{ translate('messages.search_your_location_here') }}" type="text"
                                    placeholder="{{ translate('messages.search_here') }}" />
                                <div class="h-255" id="map"></div>
                            </div>

                        </div>
                    </form>
                </div>
                <div class="step d-none" data-step="3">
                    <h4 class="text-center">Business Info</h4><br>
                    <form class="js-validate registration_form3 row col-12" action="{{ route('restaurant.store_ajax') }}"
                        method="post" enctype="multipart/form-data" id="form-id">
                        @csrf
                        <div class="mb-3 col-md-12 ">
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

                        <div class=" col-md-3 gst_elem" id="">
                            <div class="mb-4 " id="gst_num">
                                <div class="form-group">
                                    <label class="input-label" id="" for="gst_num">GST
                                        Number<span>*</span></label>
                                    <input type="text" placeholder="GST Number" id="gst_num" name="gst_number"
                                        class="form-control __form-control">
                                </div>
                            </div>
                        </div>
                        <div class=" col-md-3 gst_elem" id="">

                            <div class="mb-4" id="gst_inp">
                                <div class="form-group">
                                    <label class="input-label" id="timing_label" for="minimum_delivery_time">GST
                                        (pdf / image)<span>*</span></label>

                                    <input type="file" id="gst_doc" name="gst_doc"
                                        class="form-control __form-control">

                                </div>
                            </div>
                        </div>
                        <div class=" col-md-3 " id="id_num_field" style="display:none;">
                            <div class="mb-4 " id="">
                                <div class="form-group">
                                    <label class="input-label" id="" for="id_num">ID
                                        Number</label>
                                    <input type="text" placeholder="ID Number" id="id_num" name="id_number"
                                        class="form-control __form-control">
                                </div>
                            </div>
                        </div>
                        <div class=" col-md-3 " id="id_doc_field" style="display:none;">

                            <div class="mb-4" id="id_inp">
                                <div class="form-group">
                                    <label class="input-label" id="" for="id_doc">ID
                                        Proof
                                        (pdf / image)</label>

                                    <input type="file" id="id_doc" name="id_doc"
                                        class="form-control __form-control">

                                </div>
                            </div>
                        </div>
                        <div class="mb-4 col-md-3 " id="shop_business_type_elem">
                            <div class="form-group">
                                <label class="input-label" id="" for="business_type">Type<span>*</span></label>
                                <select name="business_type" class="form-select business_type" id="shop_business_type">
                                    <option value=""></option>
                                    @foreach ($shop_stores_type as $key => $value)
                                        <option value="{{ $value->name }}">{{ $value->name }}</option>
                                    @endforeach
                                    <option value="other">Other</option>
                                </select>
                                <input type="text" style="display:none;" name="other_type_fld" id=""
                                    class="form-control other_type_fld mt-2" placeholder="Ex: Event Planner">
                            </div>
                        </div>
                        <div class="mb-4 col-md-3 " id="mycity_business_type_elem" style="display: none;">
                            <div class="form-group">
                                <label class="input-label" id="" for="business_type">Type:<span>*</span></label>
                                <select class="form-select business_type" id="mycity_business_type">
                                    <option value=""></option>
                                    @foreach ($service_stores_type as $key => $value)
                                        <option value="{{ $value->name }}">{{ $value->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-4 col-md-3 verification_inp" id="">
                            <div class="form-group">
                                <label class="input-label" id="google_verification1" for="google_verification">Your
                                    Website or Google Business
                                    Link<span>*</span></label>
                                <input type="text" placeholder="Your Google Business Link" id="google_verification"
                                    name="google_verification" class="form-control __form-control">
                            </div>
                        </div>
                        <div class="mb-4  col-md-4 " style="display: none;" id="">
                            <div class="form-group">
                                <label class="input-label" id="other_verification1" for="other_verification">Other
                                    Business Link</label>
                                <input type="text" placeholder="Other Business Link" id="other_verification"
                                    name="other_verification" class="form-control __form-control">
                            </div>
                        </div>
                        {{-- <div class="mb-4 col-md-3 ">
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
                                    <select name="delivery_time_type" class="form-control __form-control text-capitalize"
                                        required>
                                        <option value="min">{{ translate('messages.minutes') }}</option>
                                        <option value="hours">{{ translate('messages.hours') }}</option>
                                        <option value="days">{{ translate('messages.days') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div> --}}
                    </form>
                </div>
                <div class="step d-none" data-step="4">
                    <h4 class="text-center">Services / Products Offered</h4>
                    <small>Categories selection can be changed later from dashboard</small>
                    <br>
                    <form class="js-validate registration_form4 row col-12" action="{{ route('restaurant.store_ajax') }}"
                        method="post" enctype="multipart/form-data" id="form-id">
                        @csrf
                        <div class="categroy_set_service  row">
                            <div class="col-md-6">
                                <div class="form-group single_cat_inp_group">
                                    <div class="form-group mb-4">
                                        <label class="input-label" id="" for="other_verification">Category
                                            <span>*</span></label>
                                        <select name="category_1" onchange="fetch_subcategories(1)" id="category_1"
                                            data-id="1"
                                            class="category_select form-control __form-control js-select2-custom js-example-basic-single"
                                            data-placeholder="Category">
                                            <option value=""></option>
                                            @foreach ($module_categories as $cat)
                                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                            @endforeach
                                            <option value="other">Other</option>
                                        </select>
                                        <input type="text" style="display:none;" name="other_cat_fld" id=""
                                            class="form-control other_cat_fld mt-2" placeholder="Ex: Tuition">
                                    </div>
                                </div>
                                <div class="form-group subcategory_1" style="display: none;">
                                    <div class="form-group ">
                                        <label class="input-label" id=""
                                            for="other_verification">Services</label>
                                        <select name="services_1[]" id="services_1" multiple="multiple"
                                            class="select_subcategory_1 form-control __form-control js-select2-custom select_2_unlimited"
                                            data-placeholder="Subcategory">
                                            <option value=""></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input add_more_cat" name="second_cat_on" value="1"
                                        type="checkbox" role="switch" id="flexSwitchCheckDefault">
                                    <label class="form-check-label" for="flexSwitchCheckDefault">Add More Categoy
                                    </label>
                                </div>

                                <div class="form-group single_cat_inp_group second_cat_select" style="display: none;">
                                    <label class="input-label" id="" for="other_verification">Category 2
                                        <span>(optional)</span></label>
                                    <select name="category_2" id="category_2" onchange="fetch_subcategories(2)"
                                        data-id="2"
                                        class="category_select form-control __form-control js-select2-custom js-example-basic-single"
                                        data-placeholder="Category">
                                        <option value="-1">None</option>
                                        @foreach ($module_categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                        <option value="other">Other</option>

                                    </select>
                                    <input type="text" style="display:none;" name="other_cat2_fld" id=""
                                        class="form-control other_cat2_fld mt-2" placeholder="Ex: Tuition">
                                </div>
                                <div class="form-group subcategory_2" style="display: none;">
                                    <div class="form-group mb-4">
                                        <label class="input-label" id=""
                                            for="other_verification">Services</label>
                                        <select name="services_2[]" id="services_2" multiple="multiple"
                                            id="category_select"
                                            class="select_subcategory_2 form-control __form-control js-select2-custom js-example-basic-multiple"
                                            data-placeholder="Subcategory">
                                            <option value=""></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="categroy_set_shop col-md-6" style="display: none;">
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
                    </form>

                </div>
                <div class="step d-none" data-step="5">
                    <h4 class="text-center">Owner Info</h4><br>
                    <form class="js-validate registration_form5 row col-12" action="{{ route('restaurant.store_ajax') }}"
                        method="post" enctype="multipart/form-data" id="form-id">
                        @csrf
                        <div class="col-md-3 col-lg-3 col-sm-12">
                            <div class="form-group">
                                <label class="input-label" for="f_name">{{ translate('messages.first_name') }}</label>
                                <input type="text" id="f_name" name="f_name"
                                    class="f_name_inp form-control __form-control"
                                    placeholder="{{ translate('messages.first_name') }}" value="{{ old('f_name') }}"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-3 col-lg-3 col-sm-12">
                            <div class="form-group">
                                <label class="input-label" for="l_name">{{ translate('messages.last_name') }}</label>
                                <input type="text" id="l_name" name="l_name" class="form-control __form-control"
                                    placeholder="{{ translate('messages.last_name') }}" value="{{ old('l_name') }}"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-3 col-lg-3 col-sm-12">
                            <div class="form-group">
                                <input type="hidden" name="otp" id="ver_otp_inp">
                                <label class="input-label" for="ver_phone">{{ translate('messages.phone') }}</label>
                                <input type="text" id="verified_phone" style="pointer-events: none;" name="phone"
                                    class="form-control __form-control ver_phone_input"
                                    placeholder="{{ translate('messages.Ex:') }} 99********" value="{{ old('phone') }}"
                                    readonly>
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
                        <div class="col-md-3 col-sm-12 col-lg-3">
                            <div class="form-group">
                                <label class="input-label" for="email">{{ translate('messages.email') }}</label>
                                <input type="email" id="email" name="email"
                                    class="email_inp form-control __form-control"
                                    placeholder="{{ translate('messages.Ex:') }} ex@example.com"
                                    value="{{ old('email') }}" required>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-12 col-lg-3">
                            <div class="form-group">
                                <label class="input-label" for="passwordInput">
                                    {{ translate('messages.password') }} <span class="form-label-secondary"
                                        data-bs-toggle="tooltip" data-bs-placement="right"
                                        title="Must contain at least one number and symbol  and at least 8 or more characters"><img
                                            src="https://staging.mychitti.net/public/assets/admin/img/info-circle.svg"
                                            alt="Must contain at least one number and one uppercase and lowercase letter and symbol  and at least 8 or more characters"></span>
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

                        <div class="col-md-3 col-sm-12 col-lg-3">
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
                    </form>
                </div>
                <div class="step d-none" data-step="6">
                    <h4 class="text-center">Uploads</h4><br>
                    <form class="js-validate registration_form6 row col-12" action="{{ route('restaurant.store_ajax') }}"
                        method="post" enctype="multipart/form-data" id="form-id">
                        @csrf
                        <input type ="hidden" name="module_id" value = "6">
                        <div class="d-flex gap-4" style="    max-width: 600px;margin: 0 auto;">
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
                                            <input type="file" accept="image/*,android/allowCamera" name="cover_photo"
                                                id="coverImageUpload" class="form-control __form-control"
                                                accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                            <img src="{{ asset('public/assets/admin/img/pen.png') }}" alt="edit">
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="form-group w-140px d-flex flex-grow-1 flex-column justify-content-between">
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
                                                accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" required>
                                            <img src="{{ asset('public/assets/admin/img/pen.png') }}" alt="eidt">
                                        </div>
                                    </div>
                                </label>
                            </div>

                        </div><label style="text-align: center;">
                            <input type="checkbox" name="terms" value="accept" required>
                            I accept the <a id="termsLink" href="{{ route('store-terms-and-conditions', ['shop']) }}"
                                target="_blank">Terms and Conditions</a>.
                        </label>
                    </form>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="d-flex justify-content-between my-4">
                <div class="d-flex">
                    <button type="button" class="btn  mx-2 btn-lg" id="resetBtn"
                        style="display:none; background-color: #e9ecef;">Reset</button>
                </div>
                <div class="d-flex">
                    <button type="button" class="btn  mx-2 btn-lg" id="prevBtn"
                        style="display:none; background-color: #e9ecef;">Back</button>
                    {{-- style="display:none;" --}}
                    <button type="button" class="btn mx-2 btn-lg" style="display:none;" id="nextBtn">Save &
                        Next</button>
                    <button type="button" class="btn  d-none" id="submitBtn">Submit</button>
                </div>
            </div>
        </div>

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
    <div class="modal fade" id="claimBusinessModal" tabindex="-1" role="dialog"
        aria-labelledby="claimBusinessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="claim-modal-header-content w-100">
                        <div class="claim-modal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <circle cx="12" cy="12" r="10" stroke-width="2" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 16v-4m0-4h.01" />
                            </svg>
                        </div>
                        <div class="claim-modal-text">
                            <h5 class="modal-title font-weight-bold mb-1" id="claimBusinessModalLabel">Business Found</h5>
                            <p class="text-muted mb-0 small">This business is already in our records but hasn't been
                                verified yet.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-body pt-3">
                    <div class="claim-business-info" id="claimBusinessInfo" style="display: none;">
                        <div class="claim-business-label">Business Name</div>
                        <p class="claim-business-name" id="claimBusinessName"></p>
                    </div>
                    <p class="mb-0">Would you like to claim this business?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                        id="cancelClaimBtn">Cancel</button>
                    <a href="" type="button" class="btn btn-primary" id="claimBusinessBtn">Claim Business</a>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="claimedBusinessModal" tabindex="-1" role="dialog"
        aria-labelledby="claimedBusinessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="claim-modal-header-content w-100">
                        <div class="claim-modal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <circle cx="12" cy="12" r="10" stroke-width="2" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 16v-4m0-4h.01" />
                            </svg>
                        </div>
                        <div class="claim-modal-text">
                            <h5 class="modal-title font-weight-bold mb-1" id="claimedBusinessModalLabel">Business Under
                                Verification</h5>
                            <p class="text-muted mb-0 small">Business is under verification process. Please wait for admin
                                approval.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-body pt-3">
                    <div class="claim-business-info" id="claimBusinessInfo" style="display: none;">
                        <div class="claim-business-label">Business Name</div>
                        <p class="claim-business-name" id="claimBusinessName"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal" id="cancelClaimBtn">Okay Got it
                        !</button>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('script_2')
    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.jsmin/js/spartan-multi-image-picker.js') }}">
    </script>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=drawing,places">
    </script>
    @include('front-views.partials.tel_input')
    <script>
        $(".phone_number").on("keyup", function() {
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
            });
            $.post({
                url: "{{ route('check-business') }}",
                data: {
                    phone: $(this).val(),
                },
                success: function(data) {
                    if (data.status == 1) {

                        if (!data.in_verification && !data.verified) {
                            $('#claimBusinessBtn').attr('href',
                                "{{ route('business-verification') }}" + '?phone=' + data.phone
                            );
                            showClaimModal(data.name);
                        } else if (data.in_verification) {
                            showClaimedModal(data.name);

                            toasterNotification(
                                "Business is under verification process. Please wait for admin approval."
                            );
                        }
                        {{-- else {
                                toasterNotification("Phone number is already registered.");
                            } --}}
                    } else {}
                },
            });

        });

        function showClaimModal(businessName) {
            if (businessName) {
                $('#claimBusinessName').text(businessName);

                $('#claimBusinessInfo').show();
            } else {
                $('#claimBusinessInfo').hide();
            }
            $('#claimBusinessModal').modal('show');
        }

        function showClaimedModal(businessName) {
            if (businessName) {
                $('#claimedBusinessName').text(businessName);

                $('#claimedBusinessInfo').show();
            } else {
                $('#claimedBusinessInfo').hide();
            }
            $('#claimedBusinessModal').modal('show');
        }
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
            clickableIcons: false, // Disable default place markers
        });
        let zonePolygon = null;
        let userMarker = null;
        let infoWindow = new google.maps.InfoWindow({
            content: "Click the map to get Address!",
            position: myLatlng,
        });
        let bounds = new google.maps.LatLngBounds();
        let geocoder = new google.maps.Geocoder();
        let placesService = new google.maps.places.PlacesService(map);

        // Function to normalize latlng object
        function normalizeLatLng(latlng) {
            let lat = typeof latlng.lat === 'function' ? latlng.lat() : latlng.lat;
            let lng = typeof latlng.lng === 'function' ? latlng.lng() : latlng.lng;
            return {
                lat: lat,
                lng: lng
            };
        }

        // Function to update address from coordinates
        function updateAddress(latlng) {
            let normalizedLatLng = normalizeLatLng(latlng);

            geocoder.geocode({
                location: normalizedLatLng
            }, function(results, status) {
                if (status === "OK" && results[0]) {
                    let address = results[0].formatted_address;
                    document.getElementById('locationDisplay').value = address;
                    document.getElementById('latitude').value = normalizedLatLng.lat;
                    document.getElementById('longitude').value = normalizedLatLng.lng;
                    console.log('Latitude:', normalizedLatLng.lat);
                    console.log('Longitude:', normalizedLatLng.lng);
                } else {
                    document.getElementById('locationDisplay').value = "Address not found.";
                }
            });
        }

        // Function to create or update draggable marker
        function createOrUpdateMarker(position) {
            let normalizedPosition = normalizeLatLng(position);

            if (userMarker) {
                // Remove the old marker completely
                google.maps.event.clearInstanceListeners(userMarker);
                userMarker.setMap(null);
                userMarker = null;
            }

            // Always create a fresh marker to ensure it's draggable
            userMarker = new google.maps.Marker({
                position: normalizedPosition,
                map: map,
                draggable: true,
                title: "Drag me to adjust location",
                icon: {
                    url: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png",
                },
                zIndex: 9999 // Ensure it's on top
            });

            // Add drag end listener
            google.maps.event.addListener(userMarker, 'dragend', function(event) {
                let newPosition = normalizeLatLng(event.latLng);
                updateAddress(newPosition);
            });

            // Update address for the new position
            updateAddress(normalizedPosition);
        }

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

                        // Create draggable marker
                        createOrUpdateMarker(userLatlng);
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

        // Function to aggressively remove all default markers
        function removeDefaultMarkers() {
            // Remove red markers
            let redMarkers = document.querySelectorAll('img[src*="spotlight-poi"]');
            redMarkers.forEach(function(marker) {
                let parent = marker.closest('div');
                if (parent) {
                    parent.style.display = 'none';
                }
            });

            // Remove any other default place markers
            let placeMarkers = document.querySelectorAll('img[src*="maps.gstatic.com"][src*="marker"]');
            placeMarkers.forEach(function(marker) {
                let parent = marker.closest('div');
                if (parent && !parent.querySelector('img[src*="blue-dot"]')) {
                    parent.style.display = 'none';
                }
            });
        }

        // Remove default markers on various events
        google.maps.event.addListener(map, 'idle', function() {
            setTimeout(removeDefaultMarkers, 50);
            setTimeout(removeDefaultMarkers, 200);
        });

        google.maps.event.addListener(map, 'tilesloaded', function() {
            setTimeout(removeDefaultMarkers, 50);
        });

        // Listen for map clicks (this handles search selection)
        google.maps.event.addListener(map, 'click', function(event) {
            if (event.placeId) {
                // Prevent default marker
                event.stop();

                // Get place details
                placesService.getDetails({
                    placeId: event.placeId
                }, function(place, status) {
                    if (status === google.maps.places.PlacesServiceStatus.OK && place.geometry) {
                        let searchLocation = {
                            lat: place.geometry.location.lat(),
                            lng: place.geometry.location.lng()
                        };

                        // Move blue marker to search location
                        createOrUpdateMarker(searchLocation);

                        // Center map
                        map.setCenter(searchLocation);
                        map.setZoom(17);

                        // Remove any red markers that appeared
                        setTimeout(removeDefaultMarkers, 100);
                        setTimeout(removeDefaultMarkers, 300);
                    }
                });
            } else {
                // Regular map click (not on a place)
                let clickPosition = normalizeLatLng(event.latLng);
                createOrUpdateMarker(clickPosition);
            }
        });

        // Zone selection handler
        $('#choice_zones').on('change', function() {
            let id = $(this).val();
            $.get({
                url: 'https://admin.mychitti.net/admin/zone/get-coordinates/' + id,
                dataType: 'json',
                success: function(data) {
                    console.log(data);

                    let normalizedCenter = normalizeLatLng(data.center);

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
                    map.setCenter(normalizedCenter);

                    // Update marker position
                    createOrUpdateMarker(normalizedCenter);

                    // Add click event on the polygon
                    google.maps.event.addListener(zonePolygon, 'click', function(mapsMouseEvent) {
                        infoWindow.close();

                        let clickedPosition = normalizeLatLng(mapsMouseEvent.latLng);

                        // Move marker to clicked location
                        createOrUpdateMarker(clickedPosition);

                        // Create info window
                        infoWindow = new google.maps.InfoWindow({
                            position: mapsMouseEvent.latLng,
                            content: "Fetching address...",
                        });

                        // Convert clicked location to address
                        geocoder.geocode({
                            location: clickedPosition
                        }, function(results, status) {
                            if (status === "OK" && results[0]) {
                                let clickedAddress = results[0].formatted_address;
                                document.getElementById('locationDisplay').value =
                                    clickedAddress;
                                infoWindow.setContent(clickedAddress);
                            } else {
                                document.getElementById('locationDisplay').value =
                                    "Address not found.";
                                infoWindow.setContent("Address not found.");
                            }
                        });
                        infoWindow.open(map);
                    });
                },
            });
        });
    </script>
    <script type="text/javascript">
        'use strict';

        // $(document).on('change', '.business_type', function() {
        //     console.log($(this).val())
        //     if ($(this).val() == 'Business') {
        //         $(".verification_inp").show()
        //     } else {
        //         $(".verification_inp").hide()
        //     }
        // })
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
                    $('.invalid-feedback').show()
                } else {
                    invalidFeedback.hide(); // Hide feedback if passwords match
                    $('#confirmPasswordInput').removeClass('is-invalid');
                    $('.invalid-feedback').hide()

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

                            $("#verified_phone").val($('#ver_phone').val());
                            $("#ver_otp_inp").val(data.otp);
                            showStep(2) // show next step
                            var existingData = JSON.parse(localStorage.getItem('wizardFormData') ||
                                '{}');
                            existingData._currentStep = 1;
                            existingData._verifiedPhone = $('#ver_phone').val();
                            existingData._verifiedOtp = data.otp;
                            localStorage.setItem('wizardFormData', JSON.stringify(existingData));
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
        $('#submitBtn').on('click', function(e) {
            e.preventDefault();

            const combinedFormData = new FormData();

            // Loop through .registration_form1 to .registration_form6
            for (let i = 1; i <= 6; i++) {
                const $form = $('.registration_form' + i);

                $form.find('input[name], select[name], textarea[name]').each(function() {
                    const $el = $(this);
                    const name = $el.attr('name');
                    const type = $el.attr('type');

                    if (type === 'radio') {
                        if ($el.is(':checked')) {
                            combinedFormData.set(name, $el.val());
                        }
                    } else if (type === 'checkbox') {
                        combinedFormData.set(name, $el.is(':checked') ? '1' : '0');
                    } else if ($el.is('select[multiple]')) {
                        const values = $el.val();
                        if (values) {
                            values.forEach(val => combinedFormData.append(name, val));
                        }
                    } else if (type === 'file') {
                        const files = $el[0].files;
                        for (let j = 0; j < files.length; j++) {
                            combinedFormData.append(name, files[j]);
                        }
                    } else {
                        combinedFormData.set(name, $el.val());
                    }
                });
            }

            $.ajax({
                url: "{{ route('restaurant.store_ajax') }}",
                method: 'POST',
                data: combinedFormData,
                processData: false,
                contentType: false,
                success: function(data) {
                    if (data.errors) {
                        data.errors.forEach(element => {
                            toastr.error(element.message);
                        });
                    } else {
                        if (data.status) {
                            toastr.success(data.message);
                            $("#success_elem").show()
                            localStorage.removeItem('wizardFormData');

                            setTimeout(() => {
                                window.location.href =
                                    "{{ route('registration-successfull') }}";
                            }, 500);
                        } else {
                            toastr.error(data.message);
                        }
                    }
                    $('body').css('pointer-events', 'all')
                    $('.form-submit-button').text('Submit')
                },
                error: function(err) {
                    console.error(err);
                }
            });
        });



        function checkboxCheck(checkec) {
            if ($(this).prop('checked') == true && $('#module_select').val() != '5') {
                $('.gst_elem').hide()
                $('#id_elem').hide()
                $('#btn_elem').hide()
            } else {
                $('#btn_elem').show()
                $('.gst_elem').show()
                $('#id_elem').show()

            }
        }

        $('#confirmation_check').on('change', function() {
            if ($(this).prop('checked') == true) {
                $('.gst_elem').hide()
                $('#btn_elem').hide()
                $('#id_num_field').show()
                $('#id_doc_field').show()
            } else {
                $('#btn_elem').show()
                $('.gst_elem').show()
                $('#id_num_field').hide()
                $('#id_doc_field').hide()

            }
        })

        function initAutocomplete() {
            const input = document.querySelector('#placeSearchInp');

            const autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();

                if (!place.geometry) {
                    console.log("No details available for input: '" + place.name + "'");
                    return;
                }
                console.log('Place selected:', place);
                $("#selected_palce").val(place.formatted_address);
            });
        }

        google.maps.event.addDomListener(window, 'load', initAutocomplete);
    </script>
    <script src="{{ asset('public/assets/admin/js/view-pages/vendor-registration.js') }}"></script>

    <script src="{{ asset('public/assets/landing/js/select2.min.js') }}"></script>

    <script>
        function fetch_subcategories(cat_select_num, selected_data = null) {
            var cat_id = $('#category_' + cat_select_num).val();
            var dataid = $('#category_' + cat_select_num).attr('data-id');
            //fetchsubcategory
            if (cat_id) {

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
                                if (selected_data) {

                                    $('#services_' + cat_select_num).val(selected_data).trigger('change')
                                }
                            } else {
                                $(".subcategory_" + dataid).hide()
                                $(".select_subcategory_" + dataid).html('')
                            }
                        }
                    },
                });
            } else {
                console.log('fsjd nooo ')
            }

        }


        $(".add_more_cat").on('change', function() {
            if ($(this).prop('checked') == true) {
                $('.second_cat_select').show()
            } else {
                $('.second_cat_select').hide()
            }
        })
        $("#shop_business_type").on('change', function() {
            if ($(this).val() == 'other') {
                $(".other_type_fld").show()
            } else {
                $(".other_type_fld").hide()
            }
        })
        $("#category_1").on('change', function() {
            if ($(this).val() == 'other') {
                $(".other_cat_fld").show()
                $('.subcategory_1').hide()
            } else {
                $(".other_cat_fld").hide()
            }
        })
        $("#category_2").on('change', function() {
            if ($(this).val() == 'other') {
                $(".other_cat2_fld").show()
                $('.subcategory_2').hide()

            } else {
                $(".other_cat2_fld").hide()
            }
        })


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

        {{-- $(document).on('submit', '.registration_form', function(e) {
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
        }) --}}

        $("input[name=doc_type_check]").on('change', function() {
            console.log('changed');
            if ($(this).prop('checked') == true && $(this).hasClass('GST_inp')) {
                // console.log('gst inp checked')
                $('#id_elem').hide()
                $('.gst_elem').show()

            } else {
                $('#id_elem').show()
                $('.gst_elem').hide()
            }
        })
    </script>
    <script>
        let savedData = JSON.parse(localStorage.getItem('wizardFormData') || '{}');
        let currentStep = savedData._currentStep ? savedData._currentStep + 1 : 1;
        currentStep = Math.min(currentStep, 6);
        const totalSteps = $('.step').length;


        $('#resetBtn').on('click', function() {
            localStorage.removeItem('wizardFormData');
            window.location.reload()
        })

        $(".step-circle").on('click', function() {
            var step = $(this).attr('data-step');
            currentStep = step;
            console.log('on click current step is ' + currentStep)
            showStep(step)
        })

        function showStep(step) {
            $('.step').addClass('d-none');
            $(`.step[data-step=${step}]`).removeClass('d-none');

            $('.step-circle').removeClass('active');
            $(`.step-circle[data-step=${step}]`).addClass('active');
            if (step != 2) {
                $('#prevBtn').toggle(step > 1);
            }
            if (step != 1) {
                $('#nextBtn').toggle(step < totalSteps);
                $('#resetBtn').toggle(step);
            }
            console.log('step ' + step)
            console.log('totalSteps' + totalSteps)
            if (step === totalSteps) {
                $('#submitBtn').removeClass('d-none');
            } else {
                $('#submitBtn').addClass('d-none');
            }
        }

        $(document).ready(function() {
            showStep(currentStep);
            setTimeout(() => {
                loadStepData()

            }, 500);
            $('#prevBtn').click(() => {
                if (currentStep > 1) showStep(--currentStep);
            });
            $('#nextBtn').click(() => {
                if (validateStep(currentStep)) {
                    console.log('fs')
                    saveStepData(currentStep);
                    console.log('fs22')
                    if (currentStep < totalSteps) {
                        showStep(++currentStep);
                    } else {
                        showStep(currentStep);
                    }
                    console.log('fs2254')
                }
            });

            $('#wizardForm').on('submit', function(e) {
                e.preventDefault();
                alert('Form submitted!');
            });
        });

        function saveStepData(currentStep) {
            // Load existing data (or empty object)
            const existingData = JSON.parse(localStorage.getItem('wizardFormData') || '{}');

            // Gather new data from the current form
            const newData = {};
            $('.registration_form' + currentStep).find('input[name], select[name], textarea[name]').each(function() {
                const name = $(this).attr('name');
                const type = $(this).attr('type');

                if ($(this).is('select[multiple]')) {
                    newData[name] = $(this).val() || []; // Keep it as an array
                } else if (type === 'radio') {
                    if ($(this).is(':checked')) newData[name] = $(this).val();
                } else {
                    newData[name] = $(this).val();
                }
            });

            // Merge old + new
            const mergedData = {
                ...existingData,
                ...newData,
                _currentStep: currentStep
            };

            // Save it back
            localStorage.setItem('wizardFormData', JSON.stringify(mergedData));
        }


        function loadStepData() {
            const savedData = JSON.parse(localStorage.getItem('wizardFormData') || '{}');
            console.log(savedData)
            for (const key in savedData) {
                const el = $(`[name="${key}"]`);
                if (el.attr('type') === 'radio') {
                    el.filter(`[value="${savedData[key]}"]`).prop('checked', true);
                } else if (el.attr('type') === 'checkbox') {
                    el.prop('checked', !!savedData[key]);

                    // Handle logic for confirmation_check
                    if (key === 'confirmation_check') {
                        $('#confirmation_check').trigger('change');
                    }

                } else if (el.attr('type') === 'file') {} else if (key == 'category_1') {
                    $("#category_1").val(savedData[key]).trigger('change');
                } else if (key == 'services_1[]') {
                    const arr = Array.isArray(savedData[key]) ? savedData[key] : (typeof savedData[key] === 'string' ?
                        savedData[key].split(',') : []);

                    fetch_subcategories(1, arr)
                } else if (key == 'category_2') {
                    $("#category_2").val(savedData[key]).trigger('change');
                } else if (key == '_verifiedPhone') {
                    $("#verified_phone").val(savedData[key]);
                } else if (key == '_verifiedOtp') {
                    $("#ver_otp_inp").val(savedData[key]);
                } else if (key == 'services_2[]') {
                    const arr2 = Array.isArray(savedData[key]) ? savedData[key] : (typeof savedData[key] === 'string' ?
                        savedData[key].split(',') : []);

                    fetch_subcategories(2, arr2)
                } else if (key == 'zone_id') {
                    $("#choice_zones").val(savedData[key]).trigger('change');
                } else {
                    el.val(savedData[key]).trigger('change');
                }
            }
        }


        function validateStep(currentStep) {
            if (currentStep == 2) {
                const fieldNames = $('.registration_form')
                    .find('input[name], select[name], textarea[name]')
                    .map(function() {
                        return $(this).attr('name');
                    }).get();


                if ($('#default_name').val() == '') {
                    toastr.error("Please Enter Store Name");
                    return false;
                }
                if ($('#choice_zones').val() == '') {
                    toastr.error("Please Select City");
                    return false;
                }
            } else if (currentStep == 3) {
                if ($('#confirmation_check').prop('checked') == true) {
                    if ($('#id_num').val() == '') {
                        toastr.error("Please Enter Id Number");
                        return false;
                    }
                    if ($('#id_doc').get(0).files.length === 0) {
                        toastr.error("Please upload ID proof");
                        return false;
                    }

                } else {
                    if ($('#gst_num').val().trim() === '') {
                        // toastr.error("Please enter Google Verification");
                        // return false;
                    }
                    if ($('#gst_doc').get(0).files.length === 0) {
                        toastr.error("Please upload GST Document");
                        return false;
                    }
                }
                if ($("#shop_business_type").val() == '') {
                    toastr.error("Please select business type");
                    return false;
                } else if ($("#shop_business_type").val() === 'business') {}
                if ($("#google_verification").val().trim() === '') {
                    toastr.error("Please enter Google Verification");
                    return false;
                }
            } else if (currentStep == 4) {
                if ($('#category_1').val().trim() === '') {
                    toastr.error("Please Select Category");
                    return false;
                }
            } else if (currentStep == 5) {
                if ($('.f_name_inp').val().trim() === '') {
                    toastr.error("Please Enter Owner First Name");
                    return false;
                }
                if ($('.email_inp').val().trim() === '') {
                    toastr.error("Please Enter Email");
                    return false;
                }
            }

            return true;
        }
    </script>
@endpush
