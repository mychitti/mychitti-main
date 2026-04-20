@extends('front-views.layout')

@section('title', 'About')

@section('seo')
@endsection

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .image-style-side img {
            float: right;
        }
    </style>
@endpush

@section('content')
    <!-- Single Page Header start -->
    <div id="spacer" style="height:67px;"></div>
    <!-- Single Page Header End -->


    <!-- Contact Start -->
    <div class="container-fluid contact ">
        <div class="container py-5">
            <h1>{{ $title->value }} </h1>
            {!! $content->value !!}
        </div>
    </div>
@endsection

@push('script_2')
@endpush
