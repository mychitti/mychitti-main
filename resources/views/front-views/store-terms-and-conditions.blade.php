@extends('front-views.layout')

@section('title','Terms and Conditions')

@section('seo')
@endsection

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush 

@section('content')
<div class="spacer" style="height: 50px;"></div>

<!-- Single Page Header start -->
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Terms and Conditions</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a></li>
        <li class="breadcrumb-item active text-white">Terms and Conditions</li>
    </ol>
</div>
<!-- Single Page Header End -->


<!-- Contact Start -->
<div class="container-fluid contact py-5">
    <div class="container py-5">
        {{-- <h1>Terms and Conditions</h1> --}}
        {!!$terms_and_conditions->value!!} 
    </div> 
</div>
@endsection

@push('script_2')

@endpush