@extends('front-views.layout')

@section('title','Home')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<!-- Single Page Header start -->
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Contact</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a></li>
        <li class="breadcrumb-item active text-white">Contact</li>
    </ol>
</div>
<!-- Single Page Header End -->


<!-- Contact Start -->
<div class="container-fluid contact py-5">
    <div class="container py-5">
        <div class="contact_div bg-light rounded">
            <div class="row g-4">
                <div class="col-12">
                    <div class="text-center mx-auto" style="max-width: 700px;">
                        <h1 class="text-primary">Get in touch</h1>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="h-100 rounded">
                        @php($default_location = \App\Models\BusinessSetting::where('key', 'default_location')->first())
                        @php($default_location = $default_location?->value ? json_decode($default_location->value, true) : 0)
                        <iframe class="rounded w-100" style="height: 400px;" src="https://www.google.com/maps/embed/v1/view?key={{\App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value}}&center={{ $default_location ? $default_location['lat'] : 0 }},{{ $default_location ? $default_location['lng'] : 0 }}&zoom=14" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
                <div class="col-lg-7">
                    <form class="formSubmit contactForm" action="{{route('send-message')}}" method="post">
                        @csrf
                        <input type="text" name="name" class="w-100 form-control border-0 py-3 mb-4" placeholder="Your Name">
                        <input type="email" name="email" class="w-100 form-control border-0 py-3 mb-4" placeholder="Enter Your Email">
                        <input type="text" name="subject" class="w-100 form-control border-0 py-3 mb-4" placeholder="Subject">
                        <textarea class="w-100 form-control border-0 mb-4" rows="5" name="message" cols="10" placeholder="Your Message"></textarea>
                        <button class="w-100 btn form-control border-secondary py-3 bg-white text-primary " type="submit">Submit</button>
                    </form>
                </div>
                <div class="col-lg-5">
                    <div class="d-flex p-4 rounded mb-4 bg-white">
                        <i class="fas fa-map-marker-alt fa-2x text-primary me-4"></i>
                        <div>
                            <h4>Address</h4>
                            <p class="mb-2">{{_footerInfo('address')}}</p>
                        </div>
                    </div>
                    <div class="d-flex p-4 rounded mb-4 bg-white">
                        <i class="fas fa-envelope fa-2x text-primary me-4"></i>
                        <div>
                            <h4>Mail Us</h4>
                            <p class="mb-2">{{_footerInfo('email_address')}}</p>
                        </div>
                    </div>
                    <div class="d-flex p-4 rounded bg-white">
                        <i class="fa fa-phone-alt fa-2x text-primary me-4"></i>
                        <div>
                            <h4>Telephone</h4>
                            <p class="mb-2">{{_footerInfo('phone')}}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Contact End -->

@endsection

@push('script_2')

@endpush