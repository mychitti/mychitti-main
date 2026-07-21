@extends('front-views.layout')

@section('title', 'Signup')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div id="spacer" style="height: 78px;"></div>
        <h1 class="text-center display-6">Signup</h1>
        <p class="text-center">Sign up free and unlock everything you need</p>


    <!-- Contact Start -->
    <div class="container-fluid contact py-1 mt-3">
        <div class="container">
            <div class="contact_div bg-light rounded" style="max-width: 550px;
    margin: 20px auto;">
                <div class="row">
                    <form class="formSubmit2 row" action="{{ route('signup.post') }}" method="post">
                        @csrf

                        <div class="mb-3 col-md-6 col-sm-12">
                            <label class="form-label">First Name</label>

                            <input type="text" name="f_name" class="form-control" placeholder="First Name">
                            <div class="form-text text-danger response__f_name"></div>
                        </div>
                        <div class="mb-3 col-md-6 col-sm-12">
                            <label class="form-label">Last Name</label>

                            <input type="text" name="l_name" class="form-control" placeholder="Last Name">
                            <div class="form-text text-danger response__l_name"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>

                            <input type="email" name="email" class="form-control" placeholder="Email">
                            <div class="form-text text-danger response__email"></div>
                        </div>
                        <div class="mb-3">
                            <label for="phoneInp" class="form-label">Phone Number</label>

                            <input type="text" maxlength="10" class="form-control" name="phone" id="phoneInp"
                                placeholder="Ex: 9988776655">
                            <div class="form-text text-danger response__phone"></div>
                        </div>
                        <div class="mb-3">
                            <label for="phoneInp" class="form-label">GST Number <i>(Optional)</i></label>
                            <input type="text" class="form-control" name="gst_number" placeholder="Ex: 07DDIPA9391G1ZC">
                            <div class="form-text text-danger response__gst_number"></div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" placeholder="password"
                                id="password">
                            <div class="form-text text-danger response__password"></div>
                        </div>
                        <!-- <div class="mb-3">
                            <label for="password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" name="password_confirmation" placeholder="password" id="password">
                            <div class="form-text text-danger response__password"></div>
                        </div> -->
                        <!-- <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="exampleCheck1">
                            <label class="form-check-label" for="exampleCheck1">Remember Me</label>
                        </div> -->
                        {{-- Unticked on purpose: consent has to be an affirmative action, and a
                             pre-ticked box would not be valid opt-in for marketing. --}}
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="nearbyOffers" name="nearby_offers" value="1">
                            <label class="form-check-label" for="nearbyOffers" style="font-size:13px;">
                                Send me offers from businesses near me on WhatsApp
                            </label>
                        </div>

                        <button type="submit" class="w-100 btn btn-primary mb-3">Signup</button>

                        <small>Already have an account? <a href="{{ route('user-login') }}">Login</a></small><br>
                        <!-- <small><a href="{{ route('forgot-password') }}">Forgot Password?</a></small> -->

                    </form>
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
        const input = document.querySelector("#phoneInp");
        // var iti = window.intlTelInput(input, {
        //     initialCountry: "IN",
        //     utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/js/utils.js",
        // });

        $('#phoneInp').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        }).on('paste', function(e) {
            e.preventDefault();
            const pasted = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
            const digits = pasted.replace(/\D/g, '').slice(0, 10);
            this.value = digits;
        });

        $(".formSubmit2").on("submit", function(e) {
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
                    if (data.errors && data.errors.length > 0) {

                        toasterNotification(data.errors[0].message);

                        data.errors.forEach(function(error) {
                            $(".response__" + error.code).text(error.message);
                        });
                    } else {
                        toasterNotification(data.message);
                        setTimeout(() => {
                            window.location.href = 'login';
                        }, 1000);
                    }
                },
                complete: function(data) {
                    console.log(data.status);
                    if (data.status == 403) {
                        toasterNotification("Some error occured");
                    }
                },
            });
        });
    </script>
    <style>
        .iti {
            display: block !important;
        }
    </style>
@endpush
