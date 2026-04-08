@extends('front-views.layout')

@section('title', 'Login')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .or-text {
            margin: 12px 0;

            text-align: center;
            color: var(--login-muted);
            font-size: 13px;
        }

        .google-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-radius: 10px;

            border: 1px solid var(--login-border);
            background: white;
            width: 100%;
            justify-content: center;
            margin-top: 1px;
        }
    </style>
@endpush

@section('content')

    <!-- Single Page Header start -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Login</h1>
        <ol class="breadcrumb justify-content-center mb-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active text-white">Login</li>
        </ol>
    </div>
    <!-- Single Page Header End -->


    <!-- Contact Start -->
    <div class="container-fluid contact py-5">
        <div class="container py-5">
            <div class="p-5 bg-light rounded" style="max-width: 550px;
    margin: 0 auto;">
                <div class="row g-4">
                    <!-- Password Login -->
                    <div id="passwordLogin">
                    
                    <form class="loginForm" 
                        action="{{ request()->getHost() === 'staging.mychitti.net' ? 'https://staging.mychitti.net/login' : route('login.post') }}" 
                        method="post">             
                            @csrf
                            <div class="mb-3">
                                <label for="phoneInp" class="form-label">Phone Number</label>
                                <input type="text" maxlength="10" class="form-control" name="phone" id="phoneInp"
                                    placeholder="Ex: 9988776655">
                                <div class="form-text text-danger response__phone"></div>
                            </div>
                            <div class="mb-3 position-relative">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" placeholder="password"
                                    id="password">
                                <i class="fa fa-eye-slash eye-icon" id="togglePassword"></i>
                                <div class="form-text text-danger response__password"></div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="exampleCheck1">
                                <label class="form-check-label" for="exampleCheck1">Remember Me</label>
                            </div>
                            <button type="submit" class="w-100 btn btn-primary">Login</button>
                        </form>
                        <div class="or-text">OR</div>
                        <div class="row">
                        <div class="col-md-6 px-1">
                            <button type="button" class="w-100 btn btn-outline-primary h-100" id="showOtpLogin">Login with
                                OTP</button>
                        </div>
                        <div class="col-md-6 px-1">
                            <a href="{{ route('google.login') }}" class="google-btn border text-dark" type="button">
                                <img src="https://img.icons8.com/color/48/000000/google-logo.png" style="width: 17px;"
                                    alt="Google">
                                Login with Google
                            </a>
                        </div>
                        </div>
                    </div>

                    <!-- OTP Login -->
                    <div id="otpLogin" style="display: none;">
                        <!-- Step 1: Send OTP -->
                        <div id="otpStep1">
                            <div class="mb-3">
                                <label for="otpPhoneInp" class="form-label">Phone Number</label>
                                <input type="text" maxlength="10" class="form-control" id="otpPhoneInp"
                                    placeholder="Ex: 9988776655">
                                <div class="form-text text-danger" id="otpPhoneError"></div>
                            </div>
                            <button type="button" class="w-100 btn btn-primary" id="sendOtpBtn">Send OTP</button>
                        </div>

                        <!-- Step 2: Verify OTP -->
                        <div id="otpStep2" style="display: none;">
                            <p class="mb-2">Enter OTP sent to <strong id="otpSentPhone"></strong></p>
                            <div class="d-flex gap-2 justify-content-center mb-3">
                                <input type="number" class="form-control text-center otp-digit" maxlength="1"
                                    style="width: 50px;">
                                <input type="number" class="form-control text-center otp-digit" maxlength="1"
                                    style="width: 50px;">
                                <input type="number" class="form-control text-center otp-digit" maxlength="1"
                                    style="width: 50px;">
                                <input type="number" class="form-control text-center otp-digit" maxlength="1"
                                    style="width: 50px;">
                            </div>
                            <div class="form-text text-danger mb-2" id="otpError"></div>
                            <button type="button" class="w-100 btn btn-primary" id="verifyOtpBtn">Verify & Login</button>
                            <button type="button" class="btn btn-link btn-sm mt-2" id="resendOtpBtn">Resend OTP</button>
                        </div>

                        <div class="or-text">OR</div>
                        <button type="button" class="w-100 btn btn-outline-primary mb-2" id="showPasswordLogin">Login
                            with Password</button>
                    </div>



                    <small>Don't have an account? <a href="{{ route('user-signup') }}">Signup</a></small><br>
                    <small><a href="{{ route('forgot-password') }}">Forgot Password?</a></small>
                </div>
            </div>
        </div>
    </div>
    <!-- Contact End -->


@endsection

@push('script_2')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/css/intlTelInput.css">
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/js/intlTelInput.min.js"></script>
    <script>
        //   const input = document.querySelector("#phoneInp");
        //   var iti =  window.intlTelInput(input, {
        //     initialCountry: "IN",
        //     utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/js/utils.js",
        //   });

        $('#phoneInp').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Toggle between password and OTP login
        $('#showOtpLogin').on('click', function() {
            $('#passwordLogin').hide();
            $('#otpLogin').show();
        });
        $('#showPasswordLogin').on('click', function() {
            $('#otpLogin').hide();
            $('#passwordLogin').show();
        });

        // OTP phone input - digits only
        $('#otpPhoneInp').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Auto-focus next OTP digit
        $('.otp-digit').on('input', function() {
            if (this.value.length >= 1) {
                this.value = this.value.slice(0, 1);
                $(this).next('.otp-digit').focus();
            }
        });
        $('.otp-digit').on('keydown', function(e) {
            if (e.key === 'Backspace' && this.value === '') {
                $(this).prev('.otp-digit').focus();
            }
        });

        // Send OTP
        function sendOtp() {
            var phone = $('#otpPhoneInp').val();
            if (phone.length !== 10) {
                $('#otpPhoneError').text('Please enter a valid 10-digit phone number');
                return;
            }
            $('#otpPhoneError').text('');
            $('#sendOtpBtn').prop('disabled', true).text('Sending...');

            $.post({
                url: '{{ route('user.send-login-otp') }}',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    phone: phone
                },
                success: function(data) {
                    if (data.status) {
                        $('#otpStep1').hide();
                        $('#otpStep2').show();
                        $('#otpSentPhone').text(phone);
                        $('.otp-digit').first().focus();
                        toasterNotification(data.message);
                    } else {
                        $('#otpPhoneError').text(data.message);
                    }
                },
                error: function() {
                    $('#otpPhoneError').text('Something went wrong');
                },
                complete: function() {
                    $('#sendOtpBtn').prop('disabled', false).text('Send OTP');
                }
            });
        }

        $('#sendOtpBtn').on('click', sendOtp);
        $('#resendOtpBtn').on('click', sendOtp);

        // Verify OTP
        $('#verifyOtpBtn').on('click', function() {
            var otp = [];
            $('.otp-digit').each(function() {
                otp.push($(this).val());
            });

            if (otp.join('').length !== 4) {
                $('#otpError').text('Please enter the 4-digit OTP');
                return;
            }
            $('#otpError').text('');
            $(this).prop('disabled', true).text('Verifying...');

            $.post({
                url: '{{ route('user.verify-login-otp') }}',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    phone: $('#otpSentPhone').text(),
                    otp: otp
                },
                success: function(data) {
                    if (data.status) {
                        toasterNotification(data.message);
                        setTimeout(function() {
                            window.location.href = '/';
                        }, 1000);
                    } else {
                        $('#otpError').text(data.message);
                        $('#verifyOtpBtn').prop('disabled', false).text('Verify & Login');
                    }
                },
                error: function() {
                    $('#otpError').text('Something went wrong');
                    $('#verifyOtpBtn').prop('disabled', false).text('Verify & Login');
                }
            });
        });

        $('.loginForm').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData($(this)[0]);
            // var dialcode = iti.getSelectedCountryData().dialCode
            // var phoneVal = $("#phoneInp").val().replace(/ /g,'')
            // formData.append('phone', '+' + dialcode + phoneVal );

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: $(this).attr('action'),
                processData: false,
                contentType: false,
                async: false,
                cache: false,
                data: formData,
                beforeSend: function() {},
                success: function(data) {
                    console.log(data)
                    if (data.errors) {
                        msg = data.errors[0].message;
                        toasterNotification(msg);
                    } else {
                        toasterNotification(data.message)
                        setTimeout(() => {
                            window.location.href = '/';
                        }, 1000);
                    }
                },
                complete: function(data) {
                    console.log(data.status);
                    if (data.status == 403) {
                        toasterNotification('Incorrect Credentials')
                    }
                }
            });
        })
    </script>
    <style>
        .iti {
            display: block !important;
        }
    </style>
@endpush
