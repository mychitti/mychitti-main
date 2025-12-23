@extends('front-views.layout')

@section('title','Terms and Conditions')

@section('seo')
@endsection

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<!-- Single Page Header start -->
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Terms and Conditions</h1>
</div>
<!-- Single Page Header End -->


<!-- Contact Start -->
<div class="container-fluid contact py-5">
    <div class="container py-5">
        <h2>Terms and Conditions</h2>
        {!!$content->value!!} 
    </div> 
</div>
@endsection

@push('script_2')

@endpush