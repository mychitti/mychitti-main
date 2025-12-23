@extends('front-views.layout')

@section('title','Forgot Password')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')

<div class="spacer" style="height: 153px;"></div>


<!-- Contact Start -->
<div class="container-fluid contact ">
    <div class="container ">
        <div class="p-5 bg-light rounded" style="max-width: 550px;
    margin: 0 auto;">
            <div class="row g-4">
            <form class="loginForm" action="{{route('forgot-password.post')}}" method="post">
                @csrf
               
                <div class="mb-3">
                    <label for="phoneInp" class="form-label">Phone Number</label>
                
                    <input type="text" maxlength="10" class="form-control" name="phone"  id="phoneInp" placeholder="Ex: 9988776655">
                    <div  class="form-text text-danger response__phone"></div>
                </div>
                <button type="submit" class="w-100 btn btn-primary ">Send OTP</button> 

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
                    window.location.href = '/update-password';
                 }, 1000);
            }
           
        },
        complete: function (data) {
        
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