<!DOCTYPE html>
<?php

$log_email_succ = session()->get('log_email_succ');
?>

<html dir="{{ $site_direction }}" lang="{{ $locale }}" class="{{ $site_direction === 'rtl' ? 'active' : '' }}">

<head>
    <!-- Required Meta Tags Always Come First -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Title -->
    <title>{{ translate('messages.login') }}</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('public/favicon.ico') }}">

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <!-- CSS Implementing Plugins -->
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/vendor.min.css">
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/vendor/icon-set/style.css">
    <!-- CSS Front Template -->
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/theme.minc619.css?v=1.0') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/toastr.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .login_with_password_screen {
            max-width: 390px;
            margin: auto;
        }

        .lovebirds-login-wrapper {
            {{-- width: 62%; --}} {{-- height: 500px; --}} display: flex;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
        }

        .lovebirds-left-panel {
            width: 50%;
            background: #c5fff3;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
        }

        .lovebirds-illustration {
            width: 200px;
            height: 200px;
            background: url('https://i.ibb.co/dB0LsnD/bird-illustration.png') no-repeat center/contain;
            margin-bottom: 30px;
        }

        .lovebirds-caption {
            text-align: center;
            font-size: 14px;
            line-height: 1.6;
            color: #ffffff;
        }

        .lovebirds-right-panel {
            width: 50%;
            padding: 40px 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .lovebirds-logo {
            font-size: 30px;
            margin-bottom: 10px;
            color: #333;
        }

        .lovebirds-subtitle {
            {{-- font-family: 'Brush Script MT', cursive; --}} font-size: 18px;
            {{-- margin-bottom: 30px; --}} color: #333;
        }

        .input-wrapper {
            margin-bottom: 20px;
        }

        .input-wrapper label {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
            color: #555;
        }

        .input-wrapper input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .form-bottom-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .form-bottom-row a {
            text-decoration: none;
            color: #777;
        }

        .sign-in-button {
            background-color: #333;
            color: white;
            padding: 12px;
            width: 100%;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            margin-bottom: 20px;
        }

        .google-login-button {
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 10px;
            cursor: pointer;
            margin-bottom: 20px;
            font-size: 14px;
            color: #444;
            background: #fff;
        }

        .google-login-button img {
            width: 18px;
            margin-right: 10px;
        }

        .signup-link {
            font-size: 13px;
            text-align: center;
        }

        .signup-link a {
            text-decoration: none;
            margin-left: 4px;
        }

        /* otp element styling  */
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

        #toast {
            visibility: hidden;
            min-width: 250px;
            margin-left: -125px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 16px;
            position: fixed;
            z-index: 1111;
            left: 50%;
            bottom: 30px;
            font-size: 17px;
            opacity: 0;
            transition: opacity 0.5s, bottom 0.5s;
        }

        #toast.show {
            visibility: visible;
            opacity: 1;
            bottom: 50px;
        }


        body {
            {{-- background: #f4fffd; --}}
        }
        .text_right{
            display:none;
        }

        @media (max-width: 800px) {
          .lovebirds-login-wrapper{
            flex-direction:column;

          }
          .lovebirds-right-panel,
          .lovebirds-left-panel{
            width: 100%;
          }
          #spacer{
            height:61px;
          }
          .lovebirds-logo{
            display: none;
          }
          .lovebirds-right-panel {
            padding: 16px;
          }
          .lovebirds-left-panel{
            flex-direction:row;
          }
          .lovebirds-left-panel img{
max-height: 302px;
          }
              .text_right{
            display:block;
        }
        }
        @media (max-width: 500px) {
            .text_right{
                display: none;
            }
        }
    </style>
</head>

<body>
    <div id="toast" class="toast">This is a toaster notification!</div>
    <!-- ========== MAIN CONTENT ========== -->
    {{-- <main id="content" role="main" class="main"> --}}

    @php($logo = \App\Models\BusinessSetting::where(['key' => 'logo'])->first()->value)
    <a href="{{ route('home') }}">
        <img style="width: 160px;object-fit: contain; position:absolute; object-position: right center"
            src="{{ asset('storage/app/public/business/' . $logo ?? '') }}" alt="logo">
    </a>
    <div id="spacer"></div>
    <div class="lovebirds-login-wrapper">
        <!-- Left Panel -->
        <div class="lovebirds-left-panel">
            <img src ="{{ asset('storage/app/public/util/login_page.png') }}" class="img-fluid">
            <h5 style="
    padding-right: 10px;
" class="text-dark text_right">Access Your MyChitti Vendor Panel – One Place for Everything You Need to Grow Your Store</h5>
        </div>

        <!-- Right Panel -->
        <div class="lovebirds-right-panel">

            <div class="right_dev">
                <div style="">
                    <div style="margin:auto;max-width:390px;" class="d-flex flex-column align-items-center">
                        <a href="{{ route('home') }}" class="lovebirds-logo">
                            <img style="width: 160px;object-fit: contain;object-position: right center"
                                src="{{ asset('storage/app/public/business/' . $logo ?? '') }}" alt="logo">
                        </a>
                        <h1>{{ translate($role) }} {{ translate('messages.signin') }}
                        </h1>
                        <p class="text-center">Grow Your Business — Safe, Secure, and Simple with <br>My Chitti</p>
                    </div>
                </div>
                <form class="login_with_password_screen" {{ isset($role) && $role == 'vendor' ? '' : '' }}
                    action="{{ route('login_post') }}" method="post" id="form-id">
                    @csrf
                    <input type="hidden" name="role" value="{{ $role ?? null }}">
                    <div class="input-wrapper js-form-message form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="signinSrEmail" tabindex="1"
                            placeholder="email@address.com" value="{{ $email ?? '' }}"
                            data-msg="{{ translate('Please_enter_a_valid_email_address.') }}">
                    </div>
                    <div class="js-form-message input-wrapper">
                        <label class="input-label" for="signupSrPassword" tabindex="0">
                            <span class="d-flex justify-content-between align-items-center">
                                {{ translate('messages.password') }}
                            </span>
                        </label>

                        <div class="input-group input-group-merge">
                            <input type="password" class="js-toggle-password form-control form-control-lg"
                                name="password" id="signupSrPassword"
                                placeholder="{{ translate('messages.password_length_placeholder', ['length' => '6+']) }}"
                                value="{{ $password ?? '' }}"
                                aria-label="{{ translate('messages.password_length_placeholder', ['length' => '6+']) }}"
                                required data-msg="{{ translate('messages.invalid_password_warning') }}"
                                data-hs-toggle-password-options='{
                                                "target": "#changePassTarget",
                                    "defaultClass": "tio-hidden-outlined",
                                    "showClass": "tio-visible-outlined",
                                    "classChangeTarget": "#changePassIcon"
                                    }'>
                            <div id="changePassTarget" class="input-group-append">
                                <a class="input-group-text" href="javascript:">
                                    <i id="changePassIcon" class="tio-visible-outlined"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-5 form-bottom-row">
                        <!-- Checkbox -->
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="termsCheckbox"
                                    {{ $password ? 'checked' : '' }} name="remember" checked>
                                <label class="custom-control-label text-muted" for="termsCheckbox">
                                    {{ translate('messages.remember_me') }}
                                </label>
                            </div>
                        </div>
                        <!-- End Checkbox -->
                        <!-- forget password -->
                        <div class="form-group" id="forget-password"
                            style="display: {{ $role == 'admin' ? '' : 'none' }};">
                            <div class="custom-control">
                                <span type="button" data-toggle="modal" class="text-primary"
                                    data-target="#forgetPassModal">{{ translate('Forget Password') }}?</span>
                            </div>
                        </div>
                        <!-- End forget password -->
                        <div class="form-group" id="forget-password1"
                            style="display: {{ $role == 'vendor' ? '' : 'none' }};">
                            <div class="custom-control">
                                <span type="button" data-toggle="modal" class="text-primary"
                                    data-target="#forgetPassModal1">{{ translate('messages.Forget Password') }}?</span>
                            </div>
                        </div>
                        <!-- End forget password -->
                    </div>
                    <button type="submit" class="sign-in-button">Sign in</button>
                    @if (isset($role) && $role == 'vendor')
                        <div class="google-login-button" id="switch_login_with_otp">
                            <img src="{{ asset('storage/app/public/util/OTP-1024.webp') }}" alt="OTP">
                            {{-- <img src="https://img.icons8.com/color/48/000000/google-logo.png" alt="Google"> --}}
                            Sign in with OTP
                        </div>
                        <div class="signup-link">
                            Don't have a vendor account ? <a href="{{ route('new-store.create') }}">Create Account</a>
                        </div>
                    @endif
                </form>
            </div>


            {{-- // login with otp screen  --}}
            @if (isset($role) && $role == 'vendor')
                <div id="login_with_otp_screen" style="display:none;">

                    <form class="send_login_otp" action="{{ route('send-vendor-otp') }}" method="post"
                        id="form-id">
                        @csrf
                        <input type="hidden" name="role" value="{{ $role ?? null }}">


                        <!-- Form Group -->
                        <div class="js-form-message form-group">
                            <label class="input-label text-capitalize"
                                for="signinSrEmail">{{ translate('messages.mobile_number') }}</label>

                            <input type="text" class="form-control form-control-lg" name="phone"
                                id="signinSrMobile" tabindex="1" placeholder="Ex: 8899779988"
                                aria-label="8899779988" required
                                data-msg="{{ translate('Please_enter_mobile_number.') }}">
                        </div>
                        <!-- End Form Group -->

                        <button type="submit" id="send_otp_btn" class="sign-in-button mt-2">Send OTP</button>
                    </form>
                    <!--  -->
                    <div class="container-fluid contact pt-5" style="display:none;" id="verify_screen">
                        <div class="container py-5">
                            <h2 class="text-center">Enter OTP</h2>
                            <div class=" rounded" style="max-width: 550px; margin: 0 auto;">
                                <div class="row ">
                                    <form class="otpForm" style="margin: 0 auto;" action="{{ route('login_otp') }}"
                                        method="post">
                                        @csrf
                                        <input type="hidden" name="phone" id="ver_phone" value="">
                                        <div class="d-flex justify-content-center">
                                            <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                            <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                            <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                            <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                        </div>

                                        <button type="submit" class="sign-in-button mt-2">Submit</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-center my-2">OR</p>
                    <div class="google-login-button" id="switch_login_with_pass">
                        <img src="{{ asset('storage/app/public/util/OTP-1024.webp') }}" alt="OTP">
                        {{-- <img src="https://img.icons8.com/color/48/000000/google-logo.png" alt="Google"> --}}
                        Sign in with Password
                    </div>
                </div>
            @endif
            {{-- // login with otp screen end --}}

        </div>
    </div>


    <!-- ========== END MAIN CONTENT ========== -->
    <div class="modal fade" id="forgetPassModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header justify-content-end">
                    <span type="button" class="close-modal-icon" data-dismiss="modal">
                        <i class="tio-clear"></i>
                    </span>
                </div>
                <div class="modal-body">
                    <div class="forget-pass-content">
                        <img src="{{ asset('/public/assets/admin/img/send-mail.svg') }}" alt="">
                        <!-- After Succeed -->
                        <!-- <img src="{{ asset('/public/assets/admin/img/sent-mail.svg') }}" alt=""> -->
                        <h4>
                            {{ translate('Send_Mail_to_Your_Email') }} ?
                        </h4>
                        <p>
                            {{ translate('A mail will be send to your registered email with a  link to change passowrd') }}
                        </p>
                        <a class="btn btn-lg btn-block btn--primary mt-3" href="{{ route('reset-password') }}">
                            {{ translate('Send Mail') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="forgetPassModal1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header justify-content-end">
                    <span type="button" class="close-modal-icon" data-dismiss="modal">
                        <i class="tio-clear"></i>
                    </span>
                </div>
                <div class="modal-body">
                    <div class="forget-pass-content">
                        <img src="{{ asset('/public/assets/admin/img/send-mail.svg') }}" alt="">
                        <!-- After Succeed -->
                        <!-- <img src="{{ asset('/public/assets/admin/img/sent-mail.svg') }}" alt=""> -->
                        <h4>
                            {{ translate('messages.Send_Mail_to_Your_Email') }} ?
                        </h4>
                        <form class="" action="{{ route('vendor-reset-password') }}" method="post">
                            @csrf

                            <input type="email" name="email" id="" class="form-control"
                                placeholder="{{ translate('messages.plesae_enter_your_registerd_email') }}" required>
                            <button type="submit"
                                class="btn btn-lg btn-block btn--primary mt-3">{{ translate('messages.Send Mail') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="successMailModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header justify-content-end">
                    <span type="button" class="close-modal-icon" data-dismiss="modal">
                        <i class="tio-clear"></i>
                    </span>
                </div>
                <div class="modal-body">
                    <div class="forget-pass-content">
                        <!-- After Succeed -->
                        <img src="{{ asset('/public/assets/admin/img/sent-mail.svg') }}" alt="">
                        <h4>
                            {{ translate('A mail has been sent to your registered email') }}!
                        </h4>
                        <p>
                            {{ translate('Click the link in the mail description to change password') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- JS Implementing Plugins -->
    <script src="{{ asset('public/assets/admin') }}/js/vendor.min.js"></script>

    <!-- JS Front -->
    <script src="{{ asset('public/assets/admin') }}/js/theme.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/toastr.js"></script>
    {!! Toastr::message() !!}

    @if ($errors->any())
        <script>
            "use strict";
            @foreach ($errors->all() as $error)
                toastr.error('{{ translate($error) }}', Error, {
                    CloseButton: true,
                    ProgressBar: true
                });
            @endforeach
        </script>
    @endif
    @if ($log_email_succ)
        @php(session()->forget('log_email_succ'))
        <script>
            "use strict";
            $('#successMailModal').modal('show');
        </script>
    @endif

    <script>
        "use strict";
        // $("#forget-password").hide();
        $("#role-select").change(function() {
            var selectValue = $(this).val();
            if (selectValue == "admin") {
                $("#forget-password").show();
                $("#forget-password1").hide();
            } else if (selectValue == "vendor") {
                $("#forget-password").hide();
                $("#forget-password1").show();
            } else {
                $("#forget-password").hide();
                $("#forget-password1").hide();
            }
        });

        $(document).on('ready', function() {
            // INITIALIZATION OF SHOW PASSWORD
            // =======================================================
            $('.js-toggle-password').each(function() {
                new HSTogglePassword(this).init()
            });

            // INITIALIZATION OF FORM VALIDATION
            // =======================================================
            $('.js-validate').each(function() {
                $.HSCore.components.HSValidation.init($(this));
            });
        });



        $(document).on('input', '.otp-input', function(e) {
            const $inputs = $('.otp-input');
            const index = $inputs.index(this);

            if (this.value.length === this.maxLength && index < $inputs.length - 1) {
                $inputs.eq(index + 1).focus();
            }
        });


        $(document).ready(function() {
            $('.onerror-image').on('error', function() {
                let img = $(this).data('onerror-image')
                $(this).attr('src', img);
            });
        });
    </script>


    <script>
        $('.send_login_otp').on('submit', function(e) {
            e.preventDefault();
            $('#send_otp_btn').attr('disabled', true)
            var phone = $('#signinSrMobile').val();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('send-vendor-otp') }}",
                data: {
                    phone: phone
                },
                success: function(data) {
                    console.log(data)
                    if (data.status) {
                        if (data.action == 'otp_sent') {
                            $('#ver_phone').val(data.phone)
                            $('#verify_screen').show();
                            $('.send_login_otp').hide();
                        } else {}
                    }
                    toasterNotification(data.message)
                },
                complete: function() {
                    $('#send_otp_btn').removeAttr('disabled')

                }
            });
        })

        $('#switch_login_with_pass').on('click', function() {
            $('.login_with_password_screen').show()
            $('#login_with_otp_screen').hide()
        })

        $('#switch_login_with_otp').on('click', function() {
            $('.login_with_password_screen').hide()
            $('#login_with_otp_screen').show()
        })

        function toasterNotification(msg) {
            $("#toast").text(msg);
            $("#toast").addClass("show");
            setTimeout(function() {
                $("#toast").removeClass("show");
            }, 3000);
        }
    </script>

    <!-- IE Support -->
    <script>
        if (/MSIE \d|Trident.*rv:/.test(navigator.userAgent)) document.write(
            '<script src="{{ asset('public//assets/admin') }}/vendor/babel-polyfill/polyfill.min.js"><\/script>');
    </script>
</body>

</html>
