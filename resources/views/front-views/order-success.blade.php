@extends('front-views.layout')

@section('title','Order Success')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<style>

</style>
@endpush

@section('content')
<div class="spacer" style="height: 63px;"></div>

<!-- Single Page Header start -->
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Order Success</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a></li>
        <li class="breadcrumb-item active text-white">Order Success</li>
    </ol>
</div>
<!-- Single Page Header End -->


<!-- Contact Start -->
<div class="container-fluid contact py-5"> 
    <div class="container py-5">
        <div class="p-5 bg-light rounded d-flex align-items-center justify-content-center flex-column" >
            <h1 class="text-center text-primary">Order Placed Successfully</h1>
            <p class="text-center  ">Order id : <b>{{ $order_id ?? (session('order_id') ?? '') }}</b></p>
            <a href="{{route('home')}}" class="btn btn-primary">Back to Home</a>
        </div>
    </div>
</div>
<!-- Contact End -->

@endsection

@push('script_2')
<script>
    // fire once page loads
    window.addEventListener('load', function () {
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
        });
    });
    
</script>

@endpush