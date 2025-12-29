<!DOCTYPE html>

  
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Chitti — Privacy Policy</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/vendor/icon-set/style.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin') }}/css/toastr.css">
    <link href="{{ asset('assets/front/lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">

    <style>
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


        .my-nav-link {
            position: relative;
            color: #000;
            text-decoration: none;
            padding-bottom: 5px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .my-nav-link::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            height: 2px;
            width: 100%;
            background-color: #81c408;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .my-nav-link.active::after {
            transform: scaleX(1);
        }
    </style>
</head>

<body>
    @include('mc-vendor.partials.nav')


    <!-- Contact Start -->
    <div class="container-fluid contact py-5">
        <div class="container pb-5 pt-2">
            <div class="contact_div  rounded">
                <div class="row g-4">
                    <div class="col-12 mt-0">
                        <div class="text-center mx-auto" style="max-width: 700px;">
                            <h1 class="text-primary">Get in touch</h1>
                            <p >We're here to Support Every Step of your Business Journey.</p>
                        </div>
                    </div>
                </div>

                <div class="row bg-light">
<style>
.custom-file::file-selector-button {
    background: #0d6efd;   /* green */
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
}

.custom-file::file-selector-button:hover {
    background: #0d6efd; /* darker green on hover */
}</style>
                    <div class="col-lg-7 mt-0">
                        <form class="formSubmit contactForm row p-2" action="{{ route('send-message') }}"
                            method="post">
                            @csrf
                            <input type="hidden" name="form_type" value="vendor_contact">

                            <div class="col-md-6">
                                <input type="text" name="name" class="w-100 form-control border-0 py-3 mb-4"
                                    placeholder="Your Name">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="business_name" class="w-100 form-control border-0 py-3 mb-4"
                                    placeholder="Business Name">
                            </div>
                            <div class="col-md-6">
                                <input type="number" name="phone" class="w-100 form-control border-0 py-3 mb-4"
                                    placeholder="Phone Number">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="subject" class="w-100 form-control border-0 py-3 mb-4"
                                    placeholder="Subject">
                            </div>
                            <div class="col-12">
                                <textarea class="w-100 form-control border-0 mb-4" rows="5" name="message" cols="10"
                                    placeholder="Your Message"></textarea>
                            </div>
                            <div class="col-12">
                                <input type="file" name="file" class="w-100 custom-file mb-4">
                            </div>
                            <div class="d-flex justify-content-center">
                                <button class=" btn form-control border-secondary  btn-primary "
                                    type="submit">Submit</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-5 mt-0 p-2">
                        <div class="d-flex p-4 rounded mb-4 bg-white">
                            <i class="fas fa-map-marker-alt text-primary me-4"></i>
                            <div>
                                <b>Address</b>
                                <p class="mb-2">Tirupati</p>
                            </div>
                        </div>
                        <div class="d-flex p-4 rounded mb-4 bg-white">
                            <i class="fas fa-envelope text-primary me-4"></i>
                            <div>
                                <b>Mail Us</b>
                                <p class="mb-2">mychitti@mychitti.net</p>
                            </div>
                        </div>
                        <div class="d-flex p-4 rounded bg-white">
                            <i class="fa fa-phone-alt text-primary me-4"></i>
                            <div>
                                <b>Telephone</b>
                                <p class="mb-2">070228 06288</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="toast" class="toast mb-0"> </div>

    {{-- footer section  --}}
    @include('mc-vendor.partials.footer')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>

    <script>
        function toasterNotification(msg) {
            $("#toast").text(msg);
            $("#toast").addClass("show");
            setTimeout(function() {
                $("#toast").removeClass("show");
            }, 3000);
        }
        $(".formSubmit").on("submit", function(e) {
            e.preventDefault();

            var formData = new FormData($(this)[0]);
            var profile_form = false;
            if ($(this).hasClass("profile_form")) {
                profile_form = true;
            }

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
                    if (profile_form) {
                        window.location.reload();
                    }
                    try {
                        data = JSON.parse(data);
                    } catch (error) {
                        console.error("JSON Parse Error:", error);
                    }
                    console.log(data);
                    if (data.errors && data.errors.length > 0) {
                        toasterNotification(data.errors[0].message);
                    } else {
                        $(".contactForm").trigger("reset");

                        if ($(this).hasClass("addressForm")) {
                            toasterNotification(data.message);
                            setTimeout(() => {
                                window.location.reload();
                            }, 500);
                        } else {
                            toasterNotification(data.message);
                        }

                        $(".btn-close").click();
                        $("#coupon_elem").show();
                        var html = `<div class="bg-light">
                                    <span>
                                        Voucher applied : Off20 
                                    </span> 
                                    <button class="btn btn-primary" onclick="removeCoupon()">Remove</button>
                                </div>`;
                        $(".voucher_div").html(html);
                        $(".coupon_amount").text(data.coupon.discount);
                        $("#total_amount").text(
                            Number($("#total_amount").text()) - data.coupon.discount
                        );
                        $("#order_amount").val(
                            Number($("#total_amount").text()) - data.coupon.discount
                        );
                        $("#coupon_code").val(data.coupon.code);
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
</body>

</html>
