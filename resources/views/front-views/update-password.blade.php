@extends('front-views.layout')

@section('title','Forgot Password')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    
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



<!-- Contact Start -->
<div class="container-fluid contact mt-4">
    <div class="container ">
        <div class="p-5 bg-light rounded" style="max-width: 550px;
    margin: 0 auto;">
            <div class="row g-4">
            <form class="loginForm" action="{{route('reset-password')}}" method="post">
                @csrf
               
                <div class="mb-3">
                    <label for="phoneInp" class="form-label w-100 text-center">OTP</label>
                    <div class="d-flex justify-content-center">
                        <input type="text" maxlength="1"  class="otp-input" name="otp[]"/>
                        <input type="text" maxlength="1"  class="otp-input" name="otp[]"/>
                        <input type="text" maxlength="1"  class="otp-input" name="otp[]"/>
                        <input type="text" maxlength="1"  class="otp-input" name="otp[]"/>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" placeholder="password" id="password">
                    <div class="form-text text-danger response__password"></div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="confirm_password" placeholder="password" id="password">
                    <div class="form-text text-danger response__password"></div>
                </div>
                <button type="submit" class="w-100 btn btn-primary ">Update</button> 

                <small>Back to <a href="{{route('user-login')}}">Login</a></small>

                </form>
            </div>
        </div>
    </div>
</div>
<!-- Contact End -->


@endsection

@push('script_2')

 
<!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/css/intlTelInput.css"> -->
<!-- <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/js/intlTelInput.min.js"></script> -->
<script>
  const input = document.querySelector("#phoneInp");
//   var iti =  window.intlTelInput(input, {
//     initialCountry: "IN",
//     utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/js/utils.js",
//   });

    $('#phoneInp').on('input', function() { 
        this.value = this.value.replace(/[^0-9]/g, '');
    });

  $('.loginForm').on('submit', function (e){
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
        beforeSend: function () {
        },
        success: function (data) {
            if(data.errors && data.errors.length){
                 for (var i = 0; i < data.errors.length; i++) {
                  toasterNotification(data.errors[i].message)
               }
            }else{
                toasterNotification(data.message);
                setTimeout(() => {
                    window.location.href = '/login';
                 }, 1000);
            }
           
        },
        complete: function (data) {
        
        }
    });
})

$(document).on('input', '.otp-input', function (e) {
    const $inputs = $('.otp-input');
    const index = $inputs.index(this);

    if (this.value.length === this.maxLength && index < $inputs.length - 1) {
        $inputs.eq(index + 1).focus();
    }
});
</script>
<style>
    .iti {
        display: block !important;
    }
</style>



@endpush