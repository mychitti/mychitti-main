@extends('front-views.layout')

@section('title','Disclaimer')

@section('seo')
@endsection

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div id="spacer" style="height:67px;"></div>


<!-- Contact Start -->
<div class="container-fluid contact ">
    <div class="container py-5">
        {!!$content?->value!!} 
    </div> 
</div>
@endsection

@push('script_2')

@endpush