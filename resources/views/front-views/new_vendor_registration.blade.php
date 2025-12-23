@extends('front-views.layout')

@section('title', 'Store Registration')
@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/toastr.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/view-pages/vendor-registration.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/landing/css/select2.min.css') }}" />
    <style>
        .step {
            display: flex;
            flex-direction:column;
            justify-content: center;
            align-items:center;
        }

        .step-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
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
            background-color: #007bff;
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
            right: 0;
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
    <div class="container mt-5 pt-5">
        <div class="container mt-5">
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
                    <div class="step-title">Family Info</div>
                </div>
                <div class="text-center" style="width: 25%;">
                    <div class="step-circle" data-step="4">4</div>
                    <div class="step-title">Education Info</div>
                </div>
            </div>

            <!-- Step Content -->
            <div id="stepsContainer ">
                <div class="step d-none" data-step="2">
                    <form id="otp_screen" class="otpForm" style="width: 400px;" action="{{ route('send-otp') }}"
                        method="post">
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

                <div class="step " data-step="1">
                    <h4 class="text-center">Basic Info</h4><br>
                    <form class="js-validate registration_form row col-12" action="{{ route('restaurant.store_ajax') }}"
                        method="post" enctype="multipart/form-data" id="form-id">
                        @csrf
                        <div class="col-md-6" id="default-form">
                            <div class="mb-4">
                                <div class="form-group">
                                    <label class="input-label" for="default_name">{{ translate('messages.name') }}

                                    </label>
                                    <input type="text" name="name[]" id="default_name"
                                        class="form-control __form-control"
                                        placeholder="{{ translate('messages.store_name') }}" required>
                                </div>
                            </div>
                            <input type="hidden" name="lang[]" value="default">
                            <div class="mb-4">
                                <div class="form-group mb-0">
                                    <label class="input-label" for="address">{{ translate('messages.address') }}</label>
                                    <textarea type="text" id="address" name="address[]" placeholder="{{ translate('Ex: ABC Company') }}"
                                        class="form-control __form-control h-120"></textarea>
                                </div>
                            </div>
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
                        </div>
                        <div class="mb-4 col-md-6">

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
                        </div>
                    </form>
                </div>

                <div class="step d-none" data-step="3">
                    <p>Family Information Form</p>
                </div>

                <div class="step d-none" data-step="4">
                    <p>Education Information Form</p>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="d-flex justify-content-end my-4">
                <button type="button" class="btn btn-secondary" id="prevBtn">Back</button>
                <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
                <button type="submit" class="btn btn-success d-none" id="submitBtn">Submit</button>
            </div>
        </div>

    </div>

@endsection
@push('script_2')

    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
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
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        let currentStep = 1;
        const totalSteps = $('.step').length;

        function showStep(step) {
            $('.step').addClass('d-none');
            $(`.step[data-step=${step}]`).removeClass('d-none');

            $('.step-circle').removeClass('active');
            $(`.step-circle[data-step=${step}]`).addClass('active');

            $('#prevBtn').toggle(step > 1);
            $('#nextBtn').toggle(step < totalSteps);
            $('#submitBtn').toggle(step === totalSteps);
        }

        $(document).ready(function() {
            showStep(currentStep);

            $('#prevBtn').click(() => {
                if (currentStep > 1) showStep(--currentStep);
            });

            $('#nextBtn').click(() => {
                if (currentStep < totalSteps) showStep(++currentStep);
            });

            $('#wizardForm').on('submit', function(e) {
                e.preventDefault();
                alert('Form submitted!');
            });
        });
    </script>


@endpush
